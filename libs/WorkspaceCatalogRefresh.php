<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

/** Bounded, explicit device-only observations. Drafts and runtime commands are excluded. */
trait WorkspaceCatalogRefresh
{
    public function RequestWorkspaceDeviceCatalog(string $SystemID, int $Generation, string $Fingerprint, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $OperationID): string
        {
            try {
                $op = $this->WorkspaceOperation($OperationID, ['device_catalog', $SystemID, $Generation, $Fingerprint]);
                $jobs = $this->ReadWorkspaceCatalogJobs();
                if (isset($jobs['receipts'][$OperationID])) {
                    if ($jobs['receipts'][$OperationID] !== $op['request']) throw new RuntimeException('operation_conflict');
                    $this->DebugEvent('AKTION', 'Gerätedatenauftrag erneut bestätigt; keine zweite MQTT-Anfrage gestartet.', ['Systembezug' => $SystemID]);
                    return $this->GetWorkspaceDeviceCatalogStatus($SystemID);
                }
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                $view = $this->WorkspaceCatalogView($record);
                if (!$view['canRefresh']) throw new RuntimeException($view['blocked'] ?: 'busy');
                $timeout = min(600, max(15, 3 * (int) IPS_GetProperty($record['binding']['connectionInstance'], 'RequestTimeout') + 15));
                $jobs['systems'] = array_intersect_key($jobs['systems'], $this->ReadWorkspace()['systems']);
                $jobs['systems'][$SystemID] = ['binding' => $record['binding'], 'operation' => $OperationID,
                    'phase'                              => 'health', 'transaction' => '', 'deadline' => $this->Now() + $timeout, 'error' => ''];
                $jobs['receipts'][$OperationID] = $op['request'];
                $jobs['receipts'] = array_slice($jobs['receipts'], -32, null, true);
                $this->WriteWorkspaceCatalogJobs($jobs);
                $this->DebugEvent('SENDEN', 'Gerätedatenaktualisierung für genau dieses System gestartet: zuerst Verbindung prüfen. Entwurf und Automatikbetrieb bleiben erhalten.', ['Systembezug' => $SystemID, 'Verbindung' => $record['binding']['connectionInstance']]);
                try {
                    $transaction = Z2MC_CheckHealth($record['binding']['connectionInstance'], false);
                    if ($transaction === '') throw new RuntimeException('catalog_refresh_failed');
                    $this->SetWorkspaceCatalogTransaction($SystemID, $transaction);
                } catch (Throwable) {
                    $this->FinishWorkspaceCatalogJob($SystemID, 'error', 'catalog_refresh_failed');
                }
                $this->SetTimerInterval('WorkspaceCatalogRefresh', 1000);
                return $this->GetWorkspaceDeviceCatalogStatus($SystemID);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function GetWorkspaceDeviceCatalogStatus(string $SystemID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID): string
        {
            try {
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID);
                return $this->WorkspaceResult('', ['catalogRefresh' => $this->WorkspaceCatalogView($record)]);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function RefreshWorkspaceDeviceCatalogs(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            foreach ($this->ReadWorkspaceCatalogJobs()['systems'] as $system => $job) {
                if (!in_array($job['phase'], ['health', 'catalog'], true)) continue;
                try {
                    $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
                    if ($job['binding'] !== $record['binding'] || !$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
                    if ($this->Now() >= $job['deadline']) throw new RuntimeException('catalog_refresh_timeout');
                    $info = $this->WorkspaceInfo($record['binding']);
                    if ($info === null || !$info['state']['mqttConnected']) throw new RuntimeException('not_connected');
                    $state = $info['state'];
                    if ($job['phase'] === 'health') {
                        if ($state['transaction'] !== $job['transaction']) throw new RuntimeException('catalog_refresh_changed');
                        if ($state['health'] === 'pending') continue;
                        if ($state['health'] !== 'healthy') throw new RuntimeException('catalog_refresh_failed');
                        if (($state['operation']['status'] ?? '') === 'pending') throw new RuntimeException('busy');
                        // Mark the transition before dispatch; a failed or ambiguous send is never retried.
                        $jobs = $this->ReadWorkspaceCatalogJobs();
                        $jobs['systems'][$system]['phase'] = 'catalog';
                        $jobs['systems'][$system]['transaction'] = '';
                        $this->WriteWorkspaceCatalogJobs($jobs);
                        $this->DebugEvent('SENDEN', 'Verbindung bestätigt. Aktuelle Geräte, Kanäle, Gruppen und Szenen von Z2M anfordern; keine Automationsdatei laden oder speichern.', ['Systembezug' => $system]);
                        $transaction = Z2MC_RequestDeviceCatalog($record['binding']['connectionInstance']);
                        if ($transaction === '') throw new RuntimeException('catalog_refresh_failed');
                        $this->SetWorkspaceCatalogTransaction($system, $transaction);
                        continue;
                    }
                    if (($state['operation']['transaction'] ?? '') !== $job['transaction'] || ($state['operation']['kind'] ?? '') !== 'catalog') throw new RuntimeException('catalog_refresh_changed');
                    if ($state['operation']['status'] === 'pending') continue;
                    if ($state['operation']['status'] !== 'catalog_loaded') throw new RuntimeException('catalog_refresh_failed');
                    $result = json_decode($this->RefreshWorkspaceCatalog($system, $record['binding']['generation'], WorkspaceState::Fingerprint($record), bin2hex(random_bytes(16))), true, 16, JSON_THROW_ON_ERROR);
                    if (!$result['ok']) throw new RuntimeException($result['error'] ?? 'catalog_refresh_failed');
                    $this->FinishWorkspaceCatalogJob($system, 'loaded', '');
                } catch (Throwable $exception) {
                    $error = $exception instanceof RuntimeException && isset(WorkspaceState::MESSAGES[$exception->getMessage()]) ? $exception->getMessage() : 'catalog_refresh_failed';
                    $this->FinishWorkspaceCatalogJob($system, 'error', $error);
                }
            }
            $pending = array_filter($this->ReadWorkspaceCatalogJobs()['systems'], static fn (array $job): bool => in_array($job['phase'], ['health', 'catalog'], true));
            $this->SetTimerInterval('WorkspaceCatalogRefresh', $pending === [] ? 0 : 1000);
        });
    }

    private function WorkspaceCatalogView(array $record, ?bool $editorAvailable = null): array
    {
        $system = $record['binding']['systemId'];
        // A full designer response uses the exact observation that built its editor snapshot.
        $catalog = $editorAvailable === null ? $this->WorkspaceUsableCatalog($record) : ($editorAvailable ? $record['catalog'] : null);
        $storedAt = $record['catalog']['loadedAt'] ?? null;
        $job = $this->ReadWorkspaceCatalogJobs()['systems'][$system] ?? null;
        $matches = $job === null || $job['binding'] === $record['binding'];
        $pending = $matches && in_array($job['phase'] ?? '', ['health', 'catalog'], true);
        $info = $this->WorkspaceInfo($record['binding']);
        $blocked = match (true) {
            $info === null                                                                                                            => 'binding_changed', !($info['state']['mqttConnected'] ?? false) => 'not_connected',
            $pending || ($info['state']['health'] ?? '') === 'pending' || ($info['state']['operation']['status'] ?? '') === 'pending' => 'busy', default => ''
        };
        $phase = !$matches ? 'error' : ($job['phase'] ?? 'idle');
        $error = !$matches ? 'binding_changed' : ($job['error'] ?? '');
        $message = match ($phase) {
            'health'  => 'Verbindung wird geprüft … Vorhandene Gerätedaten und Entwürfe bleiben erhalten.',
            'catalog' => 'Geräte, Kanäle, Gruppen und Szenen werden aus Z2M geladen …',
            'loaded'  => 'Gerätedaten erfolgreich aktualisiert. Entwürfe und laufende Automationen bleiben erhalten.',
            'error'   => 'Gerätedaten nicht aktualisiert: ' . (WorkspaceState::MESSAGES[$error] ?? WorkspaceState::MESSAGES['catalog_refresh_failed']),
            default   => 'Gerätedaten können für dieses System direkt aus Z2M nachgeladen werden.'
        };
        if ($phase === 'idle' && $blocked !== '') $message = WorkspaceState::MESSAGES[$blocked];
        if (!$pending && $error === '' && $catalog === null && $blocked === '') {
            $message = $storedAt === null ? 'Für dieses System sind noch keine Gerätedaten geladen. Bitte „Gerätedaten aktualisieren“ wählen. Entwürfe bleiben erhalten.'
                : 'Gespeicherte Gerätedaten sind derzeit nicht bestätigt. Bitte „Gerätedaten aktualisieren“ wählen. Entwürfe bleiben erhalten.';
        }
        $this->DebugEvent(
            'AKTION',
            $catalog !== null ? 'Gerätedatenstatus: Der gespeicherte Katalog ist für die aktuelle Verbindung bestätigt und im Editor verwendbar.'
            : ($storedAt === null ? 'Gerätedatenstatus: Für dieses System fehlt ein bestätigter Katalog. Entwürfe bleiben erhalten.'
                : 'Gerätedatenstatus: Ein früherer Katalog bleibt gespeichert, ist derzeit aber nicht für den Editor bestätigt. Historischen Stand nicht als geladen anzeigen.'),
            ['Systembezug' => $system, 'KatalogBestätigt' => $catalog !== null, 'GespeicherterStandVorhanden' => $storedAt !== null]
        );
        return ['systemId' => $system, 'generation' => $record['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($record),
            'operation'    => $job['operation'] ?? '', 'phase' => $phase, 'error' => $error, 'pending' => $pending, 'canRefresh' => $blocked === '', 'blocked' => $blocked,
            'message'      => $message, 'loadedAt' => $catalog['loadedAt'] ?? null, 'storedAt' => $storedAt,
            'counts'       => ['devices' => count($catalog['devices'] ?? []), 'groups' => count($catalog['groups'] ?? []), 'scenes' => count($catalog['scenes'] ?? [])]];
    }

    private function FinishWorkspaceCatalogJob(string $system, string $phase, string $error): void
    {
        $jobs = $this->ReadWorkspaceCatalogJobs();
        if (!isset($jobs['systems'][$system])) return;
        $jobs['systems'][$system]['phase'] = $phase;
        $jobs['systems'][$system]['error'] = $error;
        $this->WriteWorkspaceCatalogJobs($jobs);
        $this->DebugEvent($error === '' ? 'AKTION' : 'FEHLER', $error === ''
            ? 'Frisch bestätigte Gerätedaten und aktuelle Variablenzuordnungen eingelesen. Entwurf, Auswahl, Partnerfreigaben und offene Eingaben unverändert erhalten.'
            : 'Gerätedatenaktualisierung beendet. Bisheriger Katalog und alle Entwürfe bleiben erhalten; kein automatischer Neuversand.', ['Systembezug' => $system, 'Grund' => $error]);
    }

    private function SetWorkspaceCatalogTransaction(string $system, string $transaction): void
    {
        $jobs = $this->ReadWorkspaceCatalogJobs();
        $jobs['systems'][$system]['transaction'] = $transaction;
        $this->WriteWorkspaceCatalogJobs($jobs);
    }

    private function ReadWorkspaceCatalogJobs(): array
    {
        $raw = $this->GetBuffer('WorkspaceCatalogJobs');
        $jobs = $raw === '' ? ['systems' => [], 'receipts' => []] : json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($jobs) || !is_array($jobs['systems'] ?? null) || count($jobs['systems']) > 8 || !is_array($jobs['receipts'] ?? null) || count($jobs['receipts']) > 32 || strlen($raw) > 65536) throw new RuntimeException('catalog_refresh_failed');
        return $jobs;
    }

    private function WriteWorkspaceCatalogJobs(array $jobs): void
    {
        $this->SetBuffer('WorkspaceCatalogJobs', json_encode($jobs, JSON_THROW_ON_ERROR));
    }

    private function ResetWorkspaceCatalogRefresh(): void
    {
        if ($this->GetBuffer('WorkspaceCatalogJobs') !== '') $this->DebugEvent('AKTION', 'Gerätedatenaufträge nach Modulupdate oder Einstellungsübernahme verworfen. Entwürfe und vorhandene Kataloge bleiben erhalten; keine automatische Wiederholung.');
        $this->SetBuffer('WorkspaceCatalogJobs', '');
        $this->SetTimerInterval('WorkspaceCatalogRefresh', 0);
    }
}
