<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/InstallationProtocol.php';

/** The owner approves one pinned bundled artifact; the existing installer owns MQTT. */
trait ConnectionInstallation
{
    public function RequestOwnedInstallation(int $Owner, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID): string
        {
            try {
                if (!DraftTransferProtocol::Operation($OperationID) || !IPS_InstanceExists($Owner)
                    || IPS_GetInstance($Owner)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE) throw new RuntimeException('ownership_required');
                $authority = json_decode(Z2MA_GetWorkspaceInstallationAuthority($Owner, $OperationID), true, 24, JSON_THROW_ON_ERROR);
                $r = $authority['request'] ?? null;
                if (($authority['ok'] ?? false) !== true || !InstallationProtocol::Valid($r) || $r['owner'] !== $Owner || $r['operation'] !== $OperationID
                    || !$this->InstallationBindingCurrent($r)) throw new RuntimeException('ownership_required');
                $frame = $this->WithTransportLock(function () use ($r): array
                {
                    $entries = $this->ReadConnectionInstallations();
                    if (isset($entries[$r['operation']])) return [];
                    $state = $this->RefreshState();
                    if (!$state['mqttConnected'] || !$state['bridgeOnline']) throw new RuntimeException('not_connected');
                    if ($state['workflow'] !== null) throw new RuntimeException('busy');
                    if ($state['externalJS'] === false) throw new RuntimeException('external_js_disabled');
                    if ($state['health'] !== 'healthy' || $state['transaction'] !== $r['health']) throw new RuntimeException('installation_changed');
                    if ($r['before'] !== null && InstallationProtocol::Before($this->PublicTransportState($state, true)) !== $r['before']) throw new RuntimeException('installation_changed');
                    $code = $this->BundledExtensionCode();
                    if ($code === '' || ExtensionContract::BuildFromHeader($code) !== $r['build'] || hash('sha256', $code) !== $r['artifact']) throw new RuntimeException('artifact_unavailable');
                    $entries[$r['operation']] = ['request' => $r, 'scope' => $state['scope'], 'status' => 'pending', 'error' => '', 'mayHaveUploaded' => false];
                    $this->WriteConnectionInstallations($entries);
                    $state['workflow'] = ['id' => $r['operation'], 'kind' => 'install', 'notify' => false, 'installationRequest' => $r,
                        'previousSession'      => $state['extension']['session'] ?? null, 'target' => null];
                    $state['operation'] = ['kind' => 'install', 'status' => 'pending', 'transaction' => $r['operation']];
                    $state['lastError'] = '';
                    $state['extensionConfirmed'] = false;
                    $state['runtimeConfirmed'] = false;
                    $frame = $this->ExtensionFrame($state, 'install', ['name' => ExtensionContract::NAME, 'code' => $code], $r['operation']);
                    $this->SaveState($state);
                    $this->DebugEvent('SENDEN', 'Bestätigte mitgelieferte Erweiterung für genau dieses System vorbereitet. Automationsdateien werden nicht übertragen.', ['Systembezug' => $r['binding']['systemId'], 'Build' => substr($r['build'], 0, 12)]);
                    return $frame;
                });
                if ($frame !== []) $this->DispatchExtensionFrame($frame);
                return $this->GetOwnedInstallationResult($Owner, $OperationID);
            } catch (Throwable $e) {
                $code = $e instanceof RuntimeException ? $e->getMessage() : 'installation_unavailable';
                if (!isset(InstallationProtocol::MESSAGES[$code]) && !in_array($code, ['ownership_required', 'not_connected', 'busy', 'external_js_disabled', 'artifact_unavailable'], true)) $code = 'installation_unavailable';
                $this->DebugEvent('FEHLER', 'Zentrale Installation nicht gestartet. Systemzuordnung und Voraussetzungen prüfen; kein automatischer Neuversand.', ['Ursache' => $code]);
                return json_encode(['ok' => false, 'error' => $code], JSON_THROW_ON_ERROR);
            }
        });
    }

    public function GetOwnedInstallationResult(int $Owner, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID): string
        {
            try {
                return $this->WithTransportLock(function () use ($Owner, $OperationID): string
                {
                    $state = $this->RefreshState();
                    $entries = $this->ReadConnectionInstallations();
                    $entry = $entries[$OperationID] ?? null;
                    if ($entry === null || $entry['request']['owner'] !== $Owner) return '{"ok":false,"error":"installation_interrupted"}';
                    if (!$this->InstallationBindingCurrent($entry['request']) || $state['scope'] !== $entry['scope']) return '{"ok":false,"error":"installation_changed"}';
                    if ($entry['status'] === 'pending' && ($state['workflow']['id'] ?? '') !== $OperationID) {
                        $entry['status'] = 'interrupted';
                        $entry['error'] = 'installation_interrupted';
                        $entries[$OperationID] = $entry;
                        $this->WriteConnectionInstallations($entries);
                    }
                    $this->SaveState($state);
                    return json_encode(['ok' => true, 'operation' => $OperationID, 'status' => $entry['status'], 'error' => $entry['error'],
                        'mayHaveUploaded'    => $entry['mayHaveUploaded']], JSON_THROW_ON_ERROR);
                });
            } catch (Throwable) {
                $this->DebugEvent('FEHLER', 'Installationsquittung konnte nicht gelesen werden. Bitte den Erweiterungsstatus prüfen; es wird keine Datei erneut übertragen.');
                return '{"ok":false,"error":"installation_unavailable"}';
            }
        });
    }

    private function InstallationBindingCurrent(array $r): bool
    {
        $b = $r['binding'];
        $m = $this->OwnershipMarker();
        $ownerReady = !$r['owned'] ? $m === [] : ($m['role'] ?? '') === 'connection' && $m['owner'] === $r['owner'] && $m['operation'] === $r['epoch'] && $m['systemId'] === $b['systemId'] && $m['generation'] === $b['generation'];
        return $b['connectionInstance'] === $this->InstanceID && $b['mqttParent'] === $this->GetParentID()
            && $b['baseTopic'] === $this->ReadPropertyString('BaseTopic') && $b['systemId'] === $this->ReadPropertyString('SystemID')
            && $b['generation'] === $this->ReadPropertyInteger('Generation') && $ownerReady;
    }

    private function InstallationFrameAllowed(array &$state): bool
    {
        $r = $state['workflow']['installationRequest'] ?? null;
        if ($r === null) return true;
        if (!$this->InstallationBindingCurrent($r)) {
            $this->FinishExtensionWorkflow($state, 'error', 'ownership_required');
            return false;
        }
        if ($state['workflow']['wire'] === 'install') {
            $entries = $this->ReadConnectionInstallations();
            $entries[$r['operation']]['mayHaveUploaded'] = true;
            $this->WriteConnectionInstallations($entries);
        }
        return true;
    }

    private function InstallationMetadataError(array $state, array $m): string
    {
        $r = $state['workflow']['installationRequest'] ?? null;
        if ($r === null) return '';
        if ($m['build'] !== $r['build']) return 'installation_build_mismatch';
        if ($r['before'] !== null && ($m['storedRevision'] !== $r['before']['revision'] || $m['checksum'] !== $r['before']['checksum'])) return 'installation_storage_changed';
        return '';
    }

    private function FinishOwnedInstallation(array $state, string $status, string $error): void
    {
        $r = $state['workflow']['installationRequest'] ?? null;
        if ($r === null) return;
        $entries = $this->ReadConnectionInstallations();
        if (!isset($entries[$r['operation']])) return;
        $entries[$r['operation']]['status'] = $status === 'installed' ? 'installed' : 'error';
        $entries[$r['operation']]['error'] = $error;
        $this->WriteConnectionInstallations($entries);
        $this->DebugEvent($error === '' ? 'EMPFANG' : 'FEHLER', 'Installationsauftrag für dieses System abgeschlossen. Kein automatischer Neuversand.', ['Systembezug' => $r['binding']['systemId'], 'Ergebnis' => $status, 'Ursache' => $error]);
    }

    private function ReadConnectionInstallations(): array
    {
        $raw = $this->ReadAttributeString('ConnectionInstallations');
        $entries = $raw === '' ? [] : json_decode($raw, true, 24, JSON_THROW_ON_ERROR);
        if (!is_array($entries) || count($entries) > 32 || strlen($raw) > 262144) throw new RuntimeException('installation_unavailable');
        foreach ($entries as $key => $e) if (!InstallationProtocol::Valid($e['request'] ?? null) || $e['request']['operation'] !== $key
            || !in_array($e['status'] ?? null, InstallationProtocol::STATES, true) || !is_bool($e['mayHaveUploaded'] ?? null)
            || !is_string($e['scope'] ?? null) || !is_string($e['error'] ?? null)) throw new RuntimeException('installation_unavailable');
        return $entries;
    }

    private function WriteConnectionInstallations(array $entries): void
    {
        $raw = json_encode(array_slice($entries, -32, null, true), JSON_THROW_ON_ERROR);
        if (strlen($raw) > 262144) throw new RuntimeException('installation_unavailable');
        $this->WriteAttributeString('ConnectionInstallations', $raw);
    }
}
