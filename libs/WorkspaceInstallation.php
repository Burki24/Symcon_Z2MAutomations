<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/InstallationProtocol.php';

trait WorkspaceInstallation
{
    public function RequestWorkspaceInstallation(string $SystemID, int $Generation, string $Fingerprint, string $OperationID, string $Context, bool $Confirmed = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $OperationID, $Context, $Confirmed): string
        {
            try {
                if (!$Confirmed) throw new RuntimeException('installation_confirmation');
                if (!ExtensionContract::Checksum($Context)) throw new RuntimeException('invalid_request');
                $op = $this->WorkspaceOperation($OperationID, [$SystemID, $Generation, $Fingerprint, $Context]);
                $j = $this->ReadWorkspaceInstallations();
                if (isset($j['receipts'][$OperationID])) {
                    if ($j['receipts'][$OperationID] !== $op['request']) throw new RuntimeException('operation_conflict');
                    if (($j['systems'][$SystemID]['request']['operation'] ?? '') !== $OperationID) throw new RuntimeException('installation_superseded');
                    $this->DebugEvent('AKTION', 'Installationsauftrag bereits angenommen. Nur dessen Status lesen; keine Datei erneut hochladen.', ['Systembezug' => $SystemID]);
                    return $this->GetWorkspaceInstallationStatus($SystemID);
                }
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                $info = $this->WorkspaceInstallationAccess($record);
                $view = $this->WorkspaceInstallationView($record);
                if (!hash_equals($view['context'], $Context)) throw new RuntimeException('installation_changed');
                if (!$view['canInstall']) throw new RuntimeException($view['blocked'] ?: 'installation_unavailable');
                $artifact = $this->BundledExtensionIdentity();
                $owned = ($record['ownershipEpoch'] ?? '') !== '';
                $r = ['owner' => $this->InstanceID, 'operation' => $OperationID, 'owned' => $owned, 'epoch' => $owned ? $record['ownershipEpoch'] : $OperationID, 'binding' => $record['binding'],
                    'build'   => $artifact['build'], 'artifact' => $artifact['artifact'], 'fingerprint' => $Fingerprint, 'before' => InstallationProtocol::Before($info['state']), 'health' => ''];
                $accepted = $this->WithWorkspaceLock(function () use ($SystemID, $Generation, $Fingerprint, $OperationID, $op, $r): bool
                {
                    $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                    $j = $this->ReadWorkspaceInstallations();
                    if (isset($j['receipts'][$OperationID])) {
                        if ($j['receipts'][$OperationID] !== $op['request']) throw new RuntimeException('operation_conflict');
                        return false;
                    }
                    if (in_array($j['systems'][$SystemID]['status'] ?? '', InstallationProtocol::PENDING, true)
                        || $this->InstallationDevicesPending($j['systems'][$SystemID] ?? [])) throw new RuntimeException('installation_pending');
                    $j['systems'] = array_intersect_key($j['systems'], $this->ReadWorkspace()['systems']);
                    $j['systems'][$SystemID] = ['request' => $r, 'status' => 'checking', 'error' => '', 'mayHaveUploaded' => false,
                        'deadline'                        => $this->Now() + (int) IPS_GetProperty($r['binding']['connectionInstance'], 'RequestTimeout') + 5,
                        'devices'                         => ['phase' => 'waiting', 'operation' => '', 'session' => '', 'error' => '']];
                    $j['receipts'][$OperationID] = $op['request'];
                    $j['receipts'] = array_slice($j['receipts'], -32, null, true);
                    $this->WriteWorkspaceInstallations($j);
                    return true;
                });
                if (!$accepted) return $this->GetWorkspaceInstallationStatus($SystemID);
                try {
                    $transaction = Z2MC_CheckHealth($r['binding']['connectionInstance'], false);
                    if (!DraftTransferProtocol::Operation($transaction)) throw new RuntimeException('not_connected');
                    $this->WithWorkspaceLock(function () use ($SystemID, $OperationID, $transaction): void
                    {
                        $j = $this->ReadWorkspaceInstallations();
                        if (($j['systems'][$SystemID]['request']['operation'] ?? '') !== $OperationID || $j['systems'][$SystemID]['status'] !== 'checking') return;
                        $j['systems'][$SystemID]['request']['health'] = $transaction;
                        $this->WriteWorkspaceInstallations($j);
                    });
                    $this->DebugEvent('SENDEN', 'Bestätigte Installation für dieses System vorgemerkt. Zuerst Zigbee2MQTT frisch prüfen; noch keine Erweiterungsdatei gesendet.', ['Systembezug' => $SystemID, 'Verbindung' => $r['binding']['connectionInstance'], 'Build' => substr($r['build'], 0, 12)]);
                } catch (Throwable) {
                    $this->FinishWorkspaceInstallation($SystemID, 'error', 'not_connected', $OperationID);
                }
                $this->SetTimerInterval('WorkspaceInstallationRefresh', 1000);
                return $this->GetWorkspaceInstallationStatus($SystemID);
            } catch (Throwable $e) {
                return $this->WorkspaceInstallationFailure($e);
            }
        });
    }

    /** Called by the connection under its transport lock: read local authority only. */
    public function GetWorkspaceInstallationAuthority(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $jobs = array_values(array_filter($this->ReadWorkspaceInstallations()['systems'], static fn (array $j): bool => $j['request']['operation'] === $OperationID));
                if (count($jobs) !== 1 || !in_array($jobs[0]['status'], ['prepared', 'pending'], true)) throw new RuntimeException();
                $r = $jobs[0]['request'];
                $m = $this->OwnershipMarker();
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $r['binding']['systemId']);
                $ownerReady = $r['owned']
                    ? ($record['ownershipEpoch'] ?? '') === $r['epoch'] && ($m['role'] ?? '') === 'manager' && $m['owner'] === $this->InstanceID && $m['operation'] === $r['epoch'] && $this->MigrationWorkspaceWritable()
                    : ($record['ownershipEpoch'] ?? '') === '' && !$this->WorkspaceSetupLocked() && ($m === [] || (($m['role'] ?? '') === 'manager' && $this->MigrationWorkspaceWritable()));
                if ($record['binding'] !== $r['binding'] || !$ownerReady || WorkspaceState::Fingerprint($record) !== $r['fingerprint']
                    || $this->BundledExtensionIdentity() !== ['build' => $r['build'], 'artifact' => $r['artifact']]) throw new RuntimeException();
                return json_encode(['ok' => true, 'request' => $r], JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                return '{"ok":false}';
            }
        });
    }

    public function GetWorkspaceInstallationStatus(string $SystemID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID): string
        {
            try {
                $this->SyncWorkspaceInstallation($SystemID);
                $this->RefreshInstallationDevices($SystemID, false);
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID);
                $view = $this->WorkspaceInstallationView($record);
                $this->UpdateWorkspaceInstallationDisplay($SystemID, $view);
                if ($view['pending']) $this->SetTimerInterval('WorkspaceInstallationRefresh', 1000);
                return $this->WorkspaceResult('', ['installation' => $view]);
            } catch (Throwable $e) {
                return $this->WorkspaceInstallationFailure($e);
            }
        });
    }

    public function RefreshWorkspaceInstallations(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            foreach ($this->ReadWorkspaceInstallations()['systems'] as $system => $job) {
                if ($job['status'] === 'checking') {
                    try {
                        $r = $job['request'];
                        $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system, $r['binding']['generation'], $r['fingerprint']);
                        if ($this->Now() >= $job['deadline']) throw new RuntimeException('timeout');
                        if ($r['health'] === '' && $this->Now() < $job['deadline']) continue;
                        if ($record['binding'] !== $r['binding']) throw new RuntimeException('binding_changed');
                        $info = $this->WorkspaceInstallationAccess($record);
                        if (!$info['state']['mqttConnected']) throw new RuntimeException('not_connected');
                        if ($info['state']['transaction'] !== $r['health']) throw new RuntimeException('installation_changed');
                        if ($info['state']['health'] === 'pending' && $this->Now() < $job['deadline']) continue;
                        if ($info['state']['health'] !== 'healthy') throw new RuntimeException('timeout');
                        $prepared = $this->WithWorkspaceLock(function () use ($system, $r): bool
                        {
                            $j = $this->ReadWorkspaceInstallations();
                            if (($j['systems'][$system]['request']['operation'] ?? '') !== $r['operation'] || $j['systems'][$system]['status'] !== 'checking') return false;
                            $this->WorkspaceRecord($this->ReadWorkspace(), $system, $r['binding']['generation'], $r['fingerprint']);
                            $j['systems'][$system]['status'] = 'prepared';
                            $j['systems'][$system]['mayHaveUploaded'] = true;
                            // After this durable boundary an upload may happen, even if its reply is lost.
                            $j['changedEpochs'][$r['epoch']] = true;
                            $this->WriteWorkspaceInstallations($j);
                            return true;
                        });
                        if (!$prepared) continue;
                        $this->DebugEvent('SENDEN', 'Verbindung frisch bestätigt. Genau die freigegebene mitgelieferte Erweiterung an das zugeordnete System übergeben.', ['Systembezug' => $system, 'Verbindung' => $r['binding']['connectionInstance'], 'Build' => substr($r['build'], 0, 12)]);
                        $reply = json_decode(Z2MC_RequestOwnedInstallation($r['binding']['connectionInstance'], $this->InstanceID, $r['operation']), true, 24, JSON_THROW_ON_ERROR);
                        $this->AcceptWorkspaceInstallation($system, $reply, $r['operation']);
                    } catch (Throwable $e) {
                        $this->FinishWorkspaceInstallation($system, 'error', $this->InstallationErrorCode($e), $job['request']['operation']);
                    }
                } else $this->SyncWorkspaceInstallation($system);
                $this->RefreshInstallationDevices($system, true);
                $this->UpdateWorkspaceInstallationDisplay($system, $this->WorkspaceInstallationView($this->WorkspaceRecord($this->ReadWorkspace(), $system)));
            }
            $pending = array_any($this->ReadWorkspaceInstallations()['systems'], fn (array $j): bool => in_array($j['status'], InstallationProtocol::PENDING, true) || $this->InstallationDevicesPending($j));
            $this->SetTimerInterval('WorkspaceInstallationRefresh', $pending ? 1000 : 0);
        });
    }

    private function SyncWorkspaceInstallation(string $system): void
    {
        $job = $this->ReadWorkspaceInstallations()['systems'][$system] ?? null;
        if ($job === null || !in_array($job['status'], ['prepared', 'pending', 'interrupted'], true) || $job['request']['health'] === '') return;
        try {
            $r = $job['request'];
            $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
            if ($record['binding'] !== $r['binding'] || !$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
            $reply = json_decode(Z2MC_GetOwnedInstallationResult($record['binding']['connectionInstance'], $this->InstanceID, $r['operation']), true, 24, JSON_THROW_ON_ERROR);
            if ($job['status'] === 'interrupted' && ($reply['error'] ?? '') === 'installation_interrupted' && !($reply['ok'] ?? false)) return;
            $this->AcceptWorkspaceInstallation($system, $reply, $r['operation']);
        } catch (Throwable $e) {
            $this->FinishWorkspaceInstallation($system, 'interrupted', $this->InstallationErrorCode($e), $job['request']['operation']);
        }
    }

    private function AcceptWorkspaceInstallation(string $system, array $reply, string $operation): void
    {
        $this->WithWorkspaceLock(function () use ($system, $reply, $operation): void
        {
            $j = $this->ReadWorkspaceInstallations();
            $job = $j['systems'][$system];
            if ($job['request']['operation'] !== $operation) return;
            if (isset($reply['operation']) && $reply['operation'] !== $job['request']['operation']) return;
            if (($reply['ok'] ?? false) !== true) {
                $j['systems'][$system]['status'] = 'error';
                $j['systems'][$system]['error'] = $reply['error'] ?? 'installation_unavailable';
                $this->WriteWorkspaceInstallations($j);
                $this->DebugEvent('FEHLER', $this->InstallationErrorText($j['systems'][$system]['error']), ['Systembezug' => $system]);
                return;
            }
            if (($reply['operation'] ?? '') !== $job['request']['operation'] || !in_array($reply['status'] ?? '', ['pending', 'installed', 'error', 'interrupted'], true)
                || !is_string($reply['error'] ?? null) || !is_bool($reply['mayHaveUploaded'] ?? null)) throw new RuntimeException('installation_unavailable');
            $old = $job;
            foreach (['status', 'error', 'mayHaveUploaded'] as $key) $job[$key] = $reply[$key];
            $j['systems'][$system] = $job;
            $this->WriteWorkspaceInstallations($j);
            if ($old !== $job) $this->DebugEvent($job['error'] === '' ? 'EMPFANG' : 'FEHLER', $job['error'] === '' ? 'Installationsrückmeldung eindeutig diesem System zugeordnet. Lokale Entwürfe und andere Systeme bleiben erhalten.' : $this->InstallationErrorText($job['error']), ['Systembezug' => $system, 'Ergebnis' => $job['status'], 'Ursache' => $job['error']]);
        });
    }

    private function FinishWorkspaceInstallation(string $system, string $status, string $error, string $operation): void
    {
        $this->WithWorkspaceLock(function () use ($system, $status, $error, $operation): void
        {
            $j = $this->ReadWorkspaceInstallations();
            if (!isset($j['systems'][$system])) return;
            if ($j['systems'][$system]['request']['operation'] !== $operation) return;
            $old = $j['systems'][$system];
            $j['systems'][$system]['status'] = $status;
            $j['systems'][$system]['error'] = $error;
            $this->WriteWorkspaceInstallations($j);
            if ($old !== $j['systems'][$system]) $this->DebugEvent('FEHLER', $this->InstallationErrorText($error), ['Systembezug' => $system, 'Ursache' => $error]);
        });
    }

    private function WorkspaceInstallationView(array $record): array
    {
        $blocked = '';
        $info = null;
        try {
            $info = $this->WorkspaceInstallationAccess($record);
        }
        catch (Throwable $e) {
            $blocked = $this->InstallationErrorCode($e);
        }
        $state = $info['state'] ?? [];
        $artifact = $this->BundledExtensionIdentity();
        $job = $this->ReadWorkspaceInstallations()['systems'][$record['binding']['systemId']] ?? null;
        $matches = $job === null || $job['request']['binding'] === $record['binding'];
        $devices = $this->InstallationDevicesView($matches ? $job : null, $record);
        $pending = $matches && (in_array($job['status'] ?? '', InstallationProtocol::PENDING, true) || $devices['pending']);
        if ($blocked === '') $blocked = match (true) {
            !($state['mqttConnected'] ?? false)                                                           => 'not_connected', $artifact === null => 'artifact_unavailable',
            ($state['externalJS'] ?? null) === false                                                      => 'external_js_disabled', $pending => 'installation_pending',
            ($state['health'] ?? '') === 'pending' || ($state['operation']['status'] ?? '') === 'pending' => 'busy', default => ''
        };
        $status = !$matches ? 'interrupted' : ($devices['pending'] ? 'refreshing' : ($job['status'] ?? 'idle'));
        $error = !$matches ? 'binding_changed' : ($job['error'] ?? '');
        $message = match ($status) {
            'checking'             => 'Installation bestätigt. Zigbee2MQTT-Verbindung wird frisch geprüft; noch keine Datei hochgeladen.',
            'prepared', 'pending'  => 'Erweiterung wird übertragen und neu geladen. Die Bestätigung des neuen Erweiterungsstands steht noch aus.',
            'installed'            => 'Installation erfolgreich: Neue Erweiterungssitzung und erwarteter Build von Z2M bestätigt.',
            'refreshing'           => 'Erweiterung erfolgreich installiert. Die automatische Gerätedatenaktualisierung läuft noch.',
            'error', 'interrupted' => $this->InstallationErrorText($error ?: 'installation_interrupted'),
            default                => 'Die mitgelieferte Erweiterung kann für genau dieses System installiert oder aktualisiert werden.'
        };
        if ($status === 'installed' && $devices['phase'] !== 'none') $message = $devices['phase'] === 'loaded'
            ? 'Installation erfolgreich: Neue Erweiterungssitzung, erwarteter Build und aktuelle Gerätedaten bestätigt.'
            : 'Installation erfolgreich; Gerätedaten nicht aktualisiert. Bitte den folgenden Hinweis beachten.';
        if ($status === 'idle' && $blocked !== '') $message = $this->InstallationErrorText($blocked);
        $confirmed = ($state['extensionConfirmed'] ?? false) && ($state['extension']['ready'] ?? false);
        $executing = $confirmed ? ($state['extension']['executing'] ?? false) : null;
        return ['systemId'   => $record['binding']['systemId'], 'generation' => $record['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($record),
            'context'        => hash('sha256', json_encode([$record['binding'], $record['ownershipEpoch'] ?? '', WorkspaceState::Fingerprint($record), $artifact, InstallationProtocol::Before($state)], JSON_THROW_ON_ERROR)),
            'status'         => $status, 'pending' => $pending, 'canInstall' => $blocked === '', 'blocked' => $blocked, 'error' => $error, 'message' => $message,
            'operation'      => $job['request']['operation'] ?? '', 'mayHaveUploaded' => $job['mayHaveUploaded'] ?? false, 'devices' => $devices,
            'bundledBuild'   => $artifact['build'] ?? null, 'installedBuild' => $confirmed ? ($state['extension']['build'] ?? null) : null,
            'bundledVersion' => ExtensionContract::BundledVersion(), 'installedVersion' => $confirmed ? ($state['extension']['version'] ?? null) : null,
            'executing'      => $executing, 'runtimeMessage' => $executing === null ? 'Automatikbetrieb noch nicht bestätigt.' : ($executing ? 'Automatikbetrieb von Z2M als aktiv bestätigt.' : 'Automatikbetrieb von Z2M als gestoppt bestätigt.'),
            'notice'         => 'Die Datei „' . ExtensionContract::NAME . '“ wird ersetzt. Laufende Abläufe und Aufzeichnungen können enden. Bei eingeschaltetem Wiederanlauf können freigegebene Automationen danach wieder bereit sein. Nach der Installation werden die Gerätedaten automatisch aktualisiert. Automationsdateien und lokale Entwürfe werden nicht übertragen.'];
    }

    private function InstallationErrorCode(Throwable $e): string
    {
        $code = $e instanceof RuntimeException ? $e->getMessage() : 'installation_unavailable';
        return isset(InstallationProtocol::MESSAGES[$code]) || isset(WorkspaceState::MESSAGES[$code]) || in_array($code, ['external_js_disabled', 'artifact_unavailable', 'timeout'], true) ? $code : 'installation_unavailable';
    }

    /** Bootstrap is permitted before handover only while the connection has no owner. */
    private function WorkspaceInstallationAccess(array $record): array
    {
        if (($record['ownershipEpoch'] ?? '') !== '') return $this->WorkspaceRuntimeAccess($record);
        if (!$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
        $m = $this->OwnershipMarker();
        if ($this->WorkspaceSetupLocked() || ($m !== [] && (($m['role'] ?? '') !== 'manager' || !$this->MigrationWorkspaceWritable()))
            || json_decode(Z2MC_GetSystemOwnership($record['binding']['connectionInstance']), true, 16, JSON_THROW_ON_ERROR) !== []) throw new RuntimeException('ownership_required');
        $info = $this->WorkspaceInfo($record['binding']);
        if ($info === null) throw new RuntimeException('not_connected');
        return $info;
    }

    private function InstallationErrorText(string $code): string
    {
        return InstallationProtocol::MESSAGES[$code] ?? WorkspaceState::MESSAGES[$code] ?? $this->Translate(ExtensionContract::ErrorText($code));
    }

    private function WorkspaceInstallationFailure(Throwable $e): string
    {
        $code = $this->InstallationErrorCode($e);
        $message = $this->InstallationErrorText($code);
        $this->DebugEvent('FEHLER', $message, ['Ursache' => $code]);
        return json_encode(['ok' => false, 'error' => $code, 'message' => $message], JSON_THROW_ON_ERROR);
    }

    private function ReadWorkspaceInstallations(): array
    {
        $raw = $this->ReadAttributeString('WorkspaceInstallations');
        $j = $raw === '' ? ['version' => 1, 'systems' => [], 'receipts' => [], 'changedEpochs' => []] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (($j['version'] ?? null) !== 1 || !is_array($j['systems'] ?? null) || count($j['systems']) > 8 || !is_array($j['receipts'] ?? null)
            || count($j['receipts']) > 32 || !is_array($j['changedEpochs'] ?? null) || strlen($raw) > 1048576) throw new RuntimeException('installation_unavailable');
        foreach ($j['systems'] as $id => $job) if (!InstallationProtocol::Valid($job['request'] ?? null) || $job['request']['binding']['systemId'] !== $id
            || !in_array($job['status'] ?? null, InstallationProtocol::STATES, true) || !is_string($job['error'] ?? null) || !is_bool($job['mayHaveUploaded'] ?? null)
            || !is_int($job['deadline'] ?? null) || (isset($job['devices']) && !InstallationProtocol::ValidDevices($job['devices']))) throw new RuntimeException('installation_unavailable');
        foreach ($j['receipts'] as $key => $value) if (!DraftTransferProtocol::Operation($key) || !ExtensionContract::Checksum($value)) throw new RuntimeException('installation_unavailable');
        foreach ($j['changedEpochs'] as $key => $value) if (!DraftTransferProtocol::Operation($key) || $value !== true) throw new RuntimeException('installation_unavailable');
        return $j;
    }

    private function WriteWorkspaceInstallations(array $j): void
    {
        $raw = json_encode($j, JSON_THROW_ON_ERROR);
        if (strlen($raw) > 1048576) throw new RuntimeException('installation_unavailable');
        if ($raw !== $this->ReadAttributeString('WorkspaceInstallations')) $this->WriteAttributeString('WorkspaceInstallations', $raw);
    }

    private function InterruptWorkspaceInstallations(): void
    {
        $this->WithWorkspaceLock(function (): void
        {
            $j = $this->ReadWorkspaceInstallations();
            $interrupted = array_any($j['systems'], static fn (array $job): bool => in_array($job['status'], InstallationProtocol::PENDING, true));
            foreach ($j['systems'] as &$job) if (in_array($job['status'], InstallationProtocol::PENDING, true)) {
                $job['status'] = 'interrupted';
                $job['error'] = 'installation_interrupted';
            }
            unset($job);
            foreach ($j['systems'] as &$job) if ($this->InstallationDevicesPending($job)) {
                $job['devices']['phase'] = 'interrupted';
                $this->DebugEvent('AKTION', 'Nachladen der Gerätedaten nach Modulupdate als unterbrochen markiert. Installationserfolg und Entwürfe bleiben erhalten; kein automatischer Neuversand.', ['Systembezug' => $job['request']['binding']['systemId']]);
            }
            unset($job);
            if ($this->ReadAttributeString('WorkspaceInstallations') !== '') $this->WriteWorkspaceInstallations($j);
            if ($interrupted) $this->DebugEvent('AKTION', 'Offenen Installationsauftrag nach Modulupdate als unterbrochen markiert. Nur Rückmeldungen dürfen nachgelesen werden; keine automatische Upload-Wiederholung.');
            $this->SetTimerInterval('WorkspaceInstallationRefresh', 0);
        });
    }
}
