<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

/** Explicit observations through the existing connection; never stores or controls rules. */
trait WorkspaceDiagnostics
{
    public function GetWorkspaceDiagnostics(string $SystemID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID): string
        {
            try {
                $state = $this->ReadWorkspace();
                $record = $this->WorkspaceRecord($state, $SystemID);
                $rows = [];
                $selected = [];
                foreach ($state['systems'] as $other) {
                    $view = $this->WorkspaceDiagnosticsView($other);
                    if ($view['systemId'] === $record['binding']['systemId']) $selected = $view;
                    $rows[] = array_intersect_key($view, array_flip(['systemId', 'generation', 'name', 'baseTopic', 'connection', 'mqttParent', 'mqttConnected', 'online', 'healthText', 'build', 'debug']));
                }
                return $this->WorkspaceResult('', ['diagnostics' => $selected, 'systems' => $rows]);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function RequestWorkspaceDiagnostics(string $SystemID, int $Generation, string $Fingerprint, string $Action, int $Seconds, string $OperationID, string $Context): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $Action, $Seconds, $OperationID, $Context): string
        {
            try {
                if (!in_array($Action, ['health', 'extension', 'debug_start', 'debug_stop'], true) || !ExtensionContract::Checksum($Context)
                    || ($Action === 'debug_start' ? ($Seconds < 1 || $Seconds > 900) : $Seconds !== 0)) throw new RuntimeException('invalid_request');
                $op = $this->WorkspaceOperation($OperationID, [$SystemID, $Generation, $Fingerprint, $Action, $Seconds, $Context]);
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                if (!$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
                $jobs = $this->ReadWorkspaceDiagnosticsJobs();
                if (isset($jobs['receipts'][$OperationID])) {
                    $receipt = $jobs['receipts'][$OperationID];
                    if ($receipt['request'] !== $op['request']) throw new RuntimeException('operation_conflict');
                    if ($receipt['error'] !== '') throw new RuntimeException($receipt['error']);
                    $this->DebugEvent('AKTION', 'Diagnoseauftrag bereits angenommen; keine zweite Prüfung und keine Verlängerung der Aufzeichnung.', ['Systembezug' => $SystemID]);
                    return $this->GetWorkspaceDiagnostics($SystemID);
                }
                $view = $this->WorkspaceDiagnosticsView($record);
                if (!hash_equals($view['context'], $Context)) throw new RuntimeException('diagnostics_changed');
                $allowed = match ($Action) {
                    'debug_start' => $view['debug']['canStart'], 'debug_stop' => $view['debug']['canStop'], default => $view['canCheck']
                };
                if (!$allowed) throw new RuntimeException($view['blocked'] ?: 'diagnostics_unavailable');
                $jobs['receipts'][$OperationID] = ['request' => $op['request'], 'error' => ''];
                $jobs['receipts'] = array_slice($jobs['receipts'], -32, null, true);
                $this->SetBuffer('WorkspaceDiagnosticsJobs', json_encode($jobs, JSON_THROW_ON_ERROR));
                $connection = $record['binding']['connectionInstance'];
                $this->DebugEvent('SENDEN', 'Ausdrücklichen Diagnoseauftrag an die zugeordnete Z2M-Verbindung übergeben. Entwürfe, Freigaben und Automatikbetrieb bleiben erhalten.', ['Systembezug' => $SystemID, 'Verbindung' => $connection, 'Aktion' => match ($Action) {
                    'health' => 'Zigbee2MQTT-Verbindung prüfen', 'extension' => 'Erweiterungsstand abfragen', 'debug_start' => 'Ablaufaufzeichnung starten', default => 'Eigene Ablaufaufzeichnung stoppen'
                }, 'DauerSekunden' => $Seconds]);
                // Send outside transport/workspace semaphores: the reply may arrive synchronously.
                $result = match ($Action) {
                    'health'      => Z2MC_CheckHealth($connection, false), 'extension' => Z2MC_RequestExtensionStatus($connection),
                    'debug_start' => Z2MC_StartRuntimeDebug($connection, $Seconds), 'debug_stop' => Z2MC_StopRuntimeDebug($connection)
                };
                $jobs = $this->ReadWorkspaceDiagnosticsJobs();
                if ($result === '' || $result === false) {
                    $jobs['receipts'][$OperationID]['error'] = 'diagnostics_unavailable';
                    $this->SetBuffer('WorkspaceDiagnosticsJobs', json_encode($jobs, JSON_THROW_ON_ERROR));
                    throw new RuntimeException('diagnostics_unavailable');
                }
                if (in_array($Action, ['health', 'extension'], true)) {
                    $jobs['systems'] = array_intersect_key($jobs['systems'], $this->ReadWorkspace()['systems']);
                    $jobs['systems'][$SystemID] = ['binding' => $record['binding'], 'kind' => $Action, 'transaction' => $result, 'at' => $this->Now()];
                    $this->SetBuffer('WorkspaceDiagnosticsJobs', json_encode($jobs, JSON_THROW_ON_ERROR));
                }
                return $this->GetWorkspaceDiagnostics($SystemID);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    private function WorkspaceDiagnosticsView(array $record): array
    {
        $binding = $record['binding'];
        $info = $this->WorkspaceInfo($binding);
        $state = $info['state'] ?? [];
        $extra = null;
        if ($info !== null) {
            try {
                $candidate = json_decode(Z2MC_GetConnectionDiagnostics($binding['connectionInstance']), true, 16, JSON_THROW_ON_ERROR);
                if (($candidate['version'] ?? null) === 1 && ($candidate['systemId'] ?? null) === $binding['systemId']
                    && ($candidate['generation'] ?? null) === $binding['generation'] && ($candidate['mqttParent'] ?? null) === $binding['mqttParent']
                    && ($candidate['baseTopic'] ?? null) === $binding['baseTopic'] && $this->WorkspaceConnectionCurrent($binding)) $extra = $candidate;
            } catch (Throwable) { /* An older connection module cannot grant diagnostic commands. */
            }
        }
        $job = $this->ReadWorkspaceDiagnosticsJobs()['systems'][$binding['systemId']] ?? null;
        $health = $state['health'] ?? 'idle';
        $healthText = match ($health) {
            'healthy' => 'Gesund', 'unhealthy' => 'Zigbee2MQTT meldet ein Problem', 'pending' => 'Prüfung läuft',
            'timeout' => 'Keine Antwort innerhalb der Wartezeit', 'cancelled' => 'Prüfung abgebrochen', 'error' => 'Prüfung fehlgeschlagen', default => 'Noch nicht geprüft'
        };
        $blocked = $info === null ? 'binding_changed' : ($extra === null ? 'diagnostics_unavailable' : (!($state['mqttConnected'] ?? false) ? 'not_connected' : ''));
        $busy = $health === 'pending' || ($state['operation']['status'] ?? '') === 'pending';
        $phase = 'idle';
        $message = 'Zuletzt bekannter Stand aus Symcon. Eine neue Z2M-Abfrage startet nur über die Prüfbuttons.';
        if ($job !== null) {
            $matches = $job['binding'] === $binding && $info !== null;
            $probe = $job['kind'] === 'health';
            $same = $matches && ($probe ? ($state['transaction'] ?? '') === $job['transaction']
                : (($state['operation']['transaction'] ?? '') === $job['transaction'] && ($state['operation']['kind'] ?? '') === 'status'));
            $pending = $same && ($probe ? $health === 'pending' : ($state['operation']['status'] ?? '') === 'pending');
            $success = $same && ($probe ? $health === 'healthy' : (($state['operation']['status'] ?? '') === 'status_received' && ($state['extensionConfirmed'] ?? false)));
            $phase = $pending ? 'pending' : ($success ? 'confirmed' : 'error');
            $message = $pending ? 'Antwort von Z2M steht noch aus …' : ($success ? ($probe ? 'Zigbee2MQTT hat die Verbindung als gesund bestätigt.' : 'Erweiterungsstatus von Z2M bestätigt; die angezeigten Stände sind aktualisiert.')
                : (!$same ? 'Die Prüfung gehört nicht mehr zum aktuellen Verbindungsauftrag. Bitte dessen Abschluss abwarten und bei Bedarf erneut prüfen.' : 'Die Prüfung war nicht erfolgreich. Bitte MQTT-Verbindung, Z2M und die Debug-Ausgaben der Verbindung prüfen.'));
        }
        $lastError = $this->Translate(ExtensionContract::ErrorText($state['lastError'] ?? ''));
        if ($phase === 'error' && $lastError !== '') $message .= ' ' . $lastError;
        $debug = $extra['debug'] ?? ['phase' => 'idle', 'active' => false, 'pending' => false, 'until' => null, 'remaining' => 0, 'context' => '', 'canStart' => false, 'canStop' => false, 'message' => 'Aufzeichnung nicht verfügbar. Bitte Verbindungsmodul aktualisieren.'];
        if ($blocked !== '') $debug['canStart'] = $debug['canStop'] = false;
        $debug['canStart'] = $debug['canStart'] && !$busy;
        return ['systemId'  => $binding['systemId'], 'generation' => $binding['generation'], 'fingerprint' => WorkspaceState::Fingerprint($record),
            'name'          => $record['name'], 'baseTopic' => $binding['baseTopic'], 'connection' => $binding['connectionInstance'], 'mqttParent' => $binding['mqttParent'],
            'mqttConnected' => ($state['mqttConnected'] ?? false), 'online' => ($state['bridgeOnline'] ?? false), 'healthText' => $healthText,
            'z2mVersion'    => $state['version'] ?? '', 'lastError' => $lastError, 'phase' => $phase, 'pending' => $phase === 'pending', 'requestedAt' => $job['at'] ?? null,
            'canCheck'      => $blocked === '' && !$busy, 'blocked' => $blocked, 'message' => $blocked === '' ? $message : (WorkspaceState::MESSAGES[$blocked] ?? WorkspaceState::MESSAGES['diagnostics_unavailable']),
            'context'       => hash('sha256', json_encode([$binding, $debug['context']], JSON_THROW_ON_ERROR)), 'build' => $extra['build'] ?? null, 'debug' => $debug];
    }

    private function ReadWorkspaceDiagnosticsJobs(): array
    {
        $raw = $this->GetBuffer('WorkspaceDiagnosticsJobs');
        $jobs = $raw === '' ? ['systems' => [], 'receipts' => []] : json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (strlen($raw) > 65536 || !is_array($jobs['systems'] ?? null) || count($jobs['systems']) > 8
            || !is_array($jobs['receipts'] ?? null) || count($jobs['receipts']) > 32) throw new RuntimeException('diagnostics_unavailable');
        return $jobs;
    }
}
