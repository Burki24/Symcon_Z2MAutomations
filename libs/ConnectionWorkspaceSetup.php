<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use stdClass;
use Throwable;

require_once __DIR__ . '/WorkspaceSetupSnapshot.php';

/** Read-only Z2M requests for direct central setup. Never sends store or activation. */
trait ConnectionWorkspaceSetup
{
    public function RequestWorkspaceSnapshot(int $Owner, string $OperationID, bool $Verify = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->RequestWorkspaceSnapshotFor($Owner, $OperationID, $Verify, 'setup'));
    }

    public function RequestWorkspaceDraftSnapshot(int $Owner, string $OperationID, bool $Verify): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->RequestWorkspaceSnapshotFor($Owner, $OperationID, $Verify, 'draft'));
    }

    public function GetWorkspaceSnapshotResult(int $Owner, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID): string
        {
            try {
                return $this->WithTransportLock(function () use ($Owner, $OperationID): string
                {
                    $state = $this->RefreshState();
                    $entry = $this->ReadWorkspaceSnapshot();
                    if ($entry === null || $entry['owner'] !== $Owner || $entry['operation'] !== $OperationID) return '{"ok":false,"error":"setup_missing"}';
                    if ($entry['status'] === 'pending' && (($state['workflow']['id'] ?? '') !== $OperationID || $entry['scope'] !== $state['scope'])) {
                        $entry['status'] = 'interrupted';
                        $entry['error'] = 'setup_interrupted';
                        $this->WriteWorkspaceSnapshot($entry);
                    }
                    $this->SaveState($state);
                    return json_encode(['ok' => true] + $entry, WorkspaceState::JSON_FLAGS);
                });
            } catch (Throwable) {
                return '{"ok":false,"error":"setup_unavailable"}';
            }
        });
    }

    public function CancelWorkspaceSnapshot(int $Owner, string $OperationID): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID): bool
        {
            try {
                $purpose = $this->ReadWorkspaceSnapshot()['purpose'] ?? 'setup';
                $authority = $this->WorkspaceSnapshotAuthority($Owner, $OperationID, $purpose);
                if ($authority['phase'] !== 'cancelled') return false;
                return $this->WithTransportLock(function () use ($Owner, $OperationID): bool
                {
                    $state = $this->RefreshState();
                    if (($state['workflow']['id'] ?? '') === $OperationID && ($state['workflow']['setupRequest']['owner'] ?? 0) === $Owner) {
                        $this->FinishExtensionWorkflow($state, 'cancelled', 'cancelled');
                        $this->SaveState($state);
                    }
                    $this->DebugEvent('AKTION', 'Nur den eigenen Leseauftrag lokal beendet. Keine MQTT-Anfrage, keine Aktivierung und kein Stopp laufender Regeln.');
                    return true;
                });
            } catch (Throwable) {
                return false;
            }
        });
    }

    private function RequestWorkspaceSnapshotFor(int $Owner, string $OperationID, bool $Verify, string $Purpose): string
    {
        try {
            $authority = $this->WorkspaceSnapshotAuthority($Owner, $OperationID, $Purpose);
            if ($authority['phase'] !== ($Verify ? 'verifying' : 'reading')) throw new RuntimeException('setup_stale');
            $frame = $this->WithTransportLock(function () use ($Owner, $OperationID, $Verify, $Purpose, $authority): array
            {
                $entry = $this->ReadWorkspaceSnapshot();
                if ($entry !== null && $entry['owner'] === $Owner && $entry['operation'] === $OperationID
                    && ($entry['purpose'] ?? 'setup') === $Purpose && (!$Verify || $entry['verify'])) return [];
                if ($Verify && ($entry === null || $entry['owner'] !== $Owner || $entry['operation'] !== $OperationID || $entry['status'] !== 'ready' || ($entry['purpose'] ?? 'setup') !== $Purpose)) throw new RuntimeException('setup_stale');
                $state = $this->RefreshState();
                if ($state['workflow'] !== null || $state['pending'] !== null) throw new RuntimeException('busy');
                if (!$state['mqttConnected']) throw new RuntimeException('not_connected');
                $entry = ['owner' => $Owner, 'operation' => $OperationID, 'binding' => $authority['binding'], 'scope' => $state['scope'],
                    'verify'      => $Verify, 'purpose' => $Purpose, 'status' => 'pending', 'error' => '', 'snapshot' => $Verify ? $entry['snapshot'] : null];
                $this->WriteWorkspaceSnapshot($entry);
                $state['workflow'] = ['id' => $OperationID, 'kind' => 'setup_read', 'target' => null, 'setupRequest' => ['owner' => $Owner, 'operation' => $OperationID, 'binding' => $authority['binding'], 'verify' => $Verify, 'purpose' => $Purpose]];
                $state['operation'] = ['kind' => 'setup_read', 'status' => 'pending', 'transaction' => $OperationID];
                $state['lastError'] = '';
                $frame = $this->ExtensionFrame($state, 'status');
                $this->SaveState($state);
                $this->DebugEvent('SENDEN', $Verify ? 'Gespeicherten Z2M-Stand vor der lokalen Übernahme erneut prüfen. Keine Dateiänderung und keine Aktivierung.'
                    : 'Vorhandene Z2M-Automationen für die Einrichtung nur lesen. Keine alte Einzelinstanz erforderlich.', ['Systembezug' => $authority['binding']['systemId']]);
                return $frame;
            });
            if ($frame !== []) $this->DispatchExtensionFrame($frame);
            return $this->GetWorkspaceSnapshotResult($Owner, $OperationID);
        } catch (Throwable $error) {
            $code = $error instanceof RuntimeException && in_array($error->getMessage(), ['setup_stale', 'ownership_required', 'busy', 'not_connected'], true) ? $error->getMessage() : 'setup_unavailable';
            $this->DebugEvent('FEHLER', 'Einlesen für die Zentrale nicht gestartet. Bestehender Betrieb und Entwürfe bleiben erhalten.', ['Ursache' => $code]);
            return json_encode(['ok' => false, 'error' => $code], JSON_THROW_ON_ERROR);
        }
    }

    private function WorkspaceSnapshotAuthority(int $owner, string $operation, string $purpose = 'setup'): array
    {
        if (!in_array($purpose, ['setup', 'draft'], true) || !DraftTransferProtocol::Operation($operation) || !IPS_InstanceExists($owner)
            || IPS_GetInstance($owner)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE) throw new RuntimeException('ownership_required');
        $reply = json_decode($purpose === 'draft' ? Z2MA_GetWorkspaceDraftSyncAuthority($owner, $operation) : Z2MA_GetWorkspaceSetupAuthority($owner, $operation), true, 16, JSON_THROW_ON_ERROR);
        $binding = $reply['binding'] ?? [];
        if (($reply['ok'] ?? false) !== true || !is_array($binding) || SystemContract::RegistryError([$binding]) !== ''
            || $binding['ownerInstance'] !== $owner || $binding['connectionInstance'] !== $this->InstanceID
            || $binding['mqttParent'] !== $this->GetParentID() || $binding['baseTopic'] !== $this->ReadPropertyString('BaseTopic')
            || $binding['systemId'] !== $this->ReadPropertyString('SystemID') || $binding['generation'] !== $this->ReadPropertyInteger('Generation')) throw new RuntimeException('ownership_required');
        $marker = $this->OwnershipMarker();
        if ($purpose === 'draft' && $marker === []) throw new RuntimeException('ownership_required');
        if ($marker !== [] && (($marker['role'] ?? '') !== 'connection' || ($marker['owner'] ?? 0) !== $owner || ($marker['operation'] ?? '') !== ($reply['epoch'] ?? '')
            || $marker['systemId'] !== $binding['systemId'] || $marker['generation'] !== $binding['generation'])) throw new RuntimeException('ownership_required');
        return $reply;
    }

    private function WorkspaceSnapshotFrameAllowed(array &$state): bool
    {
        $request = $state['workflow']['setupRequest'] ?? null;
        if ($request === null) return true;
        try {
            $authority = $this->WorkspaceSnapshotAuthority($request['owner'], $request['operation'], $request['purpose'] ?? 'setup');
            if ($authority['binding'] !== $request['binding'] || $authority['phase'] !== ($request['verify'] ? 'verifying' : 'reading')) throw new RuntimeException();
            return true;
        } catch (Throwable) {
            $this->FinishExtensionWorkflow($state, 'error', 'setup_stale');
            return false;
        }
    }

    private function ReceiveWorkspaceSnapshot(array &$state, stdClass $payload): ?array
    {
        $workflow = $state['workflow'];
        $metadata = ($payload->status ?? '') === 'ok' ? ExtensionContract::Status($payload->data ?? null) : null;
        if ($metadata === null || !$metadata['ready'] || !in_array($metadata['storage'], ['ok', 'empty'], true)) {
            $this->FinishExtensionWorkflow($state, 'error', $metadata === null ? 'invalid_response' : (!$metadata['ready'] ? 'not_ready' : 'storage_unavailable'));
            return null;
        }
        $entry = $this->ReadWorkspaceSnapshot();
        if ($entry === null || $entry['operation'] !== $workflow['id']) {
            $this->FinishExtensionWorkflow($state, 'error', 'setup_stale');
            return null;
        }
        $state['bridgeState'] = 'online';
        $state['bridgeOnline'] = true;
        $state['extension'] = $metadata;
        $state['extensionConfirmed'] = true;
        $state['runtimeConfirmed'] = $metadata['runtimeVersion'] === 1;
        if ($workflow['wire'] === 'status') {
            if ($entry['verify']) {
                $same = WorkspaceSetupSnapshot::SameStorage($entry['snapshot']['metadata'], $metadata);
                if ($same) {
                    $entry['snapshot']['metadata'] = $metadata;
                    $this->WriteWorkspaceSnapshot($entry);
                }
                $this->FinishExtensionWorkflow($state, $same ? 'verified' : 'error', $same ? '' : 'setup_stale');
                return null;
            }
            $state['workflow']['target'] = $metadata;
            return $this->ExtensionFrame($state, 'editor');
        }
        $snapshot = WorkspaceSetupSnapshot::Read($payload->data, $workflow['target']);
        $catalog = $this->ReadDeviceCatalog($payload->data);
        if ($snapshot === null || $catalog === null) {
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return null;
        }
        [$devices, $groups, $scenes] = $catalog;
        $snapshot['catalog'] = ['scope' => $state['scope'], 'session' => $metadata['session'], 'loadedAt' => $this->Now(), 'devices' => $devices, 'groups' => $groups, 'scenes' => $scenes];
        $entry['snapshot'] = $snapshot;
        $this->WriteWorkspaceSnapshot($entry);
        $this->WriteAttributeString('ConnectionCatalog', json_encode($snapshot['catalog'], WorkspaceState::JSON_FLAGS));
        $this->DebugEvent('EMPFANG', 'Gespeicherte Automationen und Gerätedaten für die Vorschau gelesen. YAML und Partnerfreigaben bleiben vollständig erhalten.', ['Systembezug' => $entry['binding']['systemId'], 'Revision' => $metadata['storedRevision'], 'Automationen' => $metadata['automationCount']]);
        $this->FinishExtensionWorkflow($state, 'ready');
        return null;
    }

    private function FinishWorkspaceSnapshot(array $state, string $status, string $error): void
    {
        if (!isset($state['workflow']['setupRequest'])) return;
        $entry = $this->ReadWorkspaceSnapshot();
        if ($entry === null || $entry['operation'] !== $state['workflow']['id']) return;
        $entry['status'] = $status;
        $entry['error'] = $error;
        $this->WriteWorkspaceSnapshot($entry);
    }

    private function ReadWorkspaceSnapshot(): ?array
    {
        $raw = $this->ReadAttributeString('ConnectionWorkspaceSnapshot');
        if ($raw === '') return null;
        $entry = strlen($raw) <= WorkspaceState::MAX_RESPONSE ? json_decode($raw, true, 48, JSON_THROW_ON_ERROR) : null;
        if (!is_array($entry) || !is_int($entry['owner'] ?? null) || !DraftTransferProtocol::Operation($entry['operation'] ?? null)
            || !is_array($entry['binding'] ?? null) || SystemContract::RegistryError([$entry['binding']]) !== '' || !is_bool($entry['verify'] ?? null)
            || !is_string($entry['status'] ?? null) || !is_string($entry['error'] ?? null) || !is_string($entry['scope'] ?? null)
            || !array_key_exists('snapshot', $entry) || ($entry['snapshot'] !== null && !is_array($entry['snapshot']))) throw new RuntimeException('setup_unavailable');
        if ($entry['snapshot'] !== null && (!is_array($entry['snapshot']['metadata'] ?? null)
            || WorkspaceState::DataError($entry['snapshot']['data'] ?? null) !== '' || !is_array($entry['snapshot']['catalog'] ?? null))) throw new RuntimeException('setup_unavailable');
        return $entry;
    }

    private function WriteWorkspaceSnapshot(array $entry): void
    {
        $raw = json_encode($entry, WorkspaceState::JSON_FLAGS);
        if (strlen($raw) > WorkspaceState::MAX_RESPONSE) throw new RuntimeException('setup_unavailable');
        $this->WriteAttributeString('ConnectionWorkspaceSnapshot', $raw);
    }
}
