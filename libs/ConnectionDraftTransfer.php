<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use stdClass;
use Throwable;

require_once __DIR__ . '/ValidationFeedback.php';

/** System-scoped transport; the manager remains the only owner of draft documents. */
trait ConnectionDraftTransfer
{
    public function RequestDraftTransfer(int $Owner, string $OperationID, string $Yaml): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID, $Yaml): string
        {
            try {
                $authority = $this->DraftAuthority($Owner, $OperationID);
                $request = $authority['request'];
                if (!$authority['documentCurrent'] || !ExtensionContract::ValidYamlText($Yaml) || hash('sha256', $Yaml) !== $request['checksum']) throw new RuntimeException('stale_transfer');
                $frame = $this->WithTransportLock(function () use ($request, $Yaml): array
                {
                    $entries = $this->ReadDraftTransfers();
                    $key = $request['operation'];
                    if (isset($entries[$key])) {
                        if ($entries[$key]['request'] !== $request) throw new RuntimeException('operation_conflict');
                        return [];
                    }
                    $state = $this->RefreshState();
                    if ($state['workflow'] !== null) throw new RuntimeException('busy');
                    if (!$state['mqttConnected'] || !$state['bridgeOnline']) throw new RuntimeException('not_connected');
                    if ($request['kind'] === 'store' && (!$this->DraftValidationCurrent($state, $request['owner'], $request['validatedRequest'], $request['checksum']))) throw new RuntimeException('not_validated');
                    $entries[$key] = ['request' => $request, 'status' => 'pending', 'error' => '', 'details' => null, 'mayHaveStored' => false, 'scope' => $state['scope']];
                    $this->WriteDraftTransfers($entries);
                    if ($request['kind'] === 'validate') {
                        $state['validation'] = null;
                        $state['validationDetails'] = null;
                    }
                    $state['workflow'] = ['id' => $key, 'kind' => $request['kind'], 'checksum' => $request['checksum'], 'yaml' => $Yaml,
                        'workspaceRequest'     => $request, 'target' => null, 'verifyStorage' => false];
                    $state['operation'] = ['kind' => $request['kind'], 'status' => 'pending', 'transaction' => $key];
                    $state['lastError'] = '';
                    $frame = $this->ExtensionFrame($state, 'status');
                    $this->SaveState($state);
                    $this->DebugEvent('AKTION', 'Systemgebundenen Prüf- oder Übertragungsauftrag vorbereitet. Keine Aktivierung und kein Gerätebefehl.', ['Systembezug' => $request['binding']['systemId'], 'Verwaltung' => $request['owner'], 'Auftrag' => $request['kind']]);
                    return $frame;
                });
                if ($frame !== []) $this->DispatchExtensionFrame($frame);
                return $this->GetDraftTransferResult($Owner, $OperationID);
            } catch (Throwable $exception) {
                $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'transfer_unavailable';
                if (!in_array($error, ['stale_transfer', 'ownership_required', 'operation_conflict', 'busy', 'not_connected', 'not_validated'], true)) $error = 'transfer_unavailable';
                $this->DebugEvent('FEHLER', 'Systemgebundener Prüf- oder Übertragungsauftrag vor dem Senden abgewiesen. Entwurf bleibt in der Zentrale erhalten.', ['Ursache' => $error]);
                return json_encode(['ok' => false, 'error' => $error], JSON_THROW_ON_ERROR);
            }
        });
    }

    public function GetDraftTransferResult(int $Owner, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID): string
        {
            try {
                return $this->WithTransportLock(function () use ($Owner, $OperationID): string
                {
                    $state = $this->RefreshState();
                    $entries = $this->ReadDraftTransfers();
                    $entry = $entries[$OperationID] ?? null;
                    if ($entry === null || $entry['request']['owner'] !== $Owner) return '{"ok":false,"error":"transfer_missing"}';
                    if ($entry['status'] === 'pending' && (($state['workflow']['id'] ?? '') !== $OperationID || $entry['scope'] !== $state['scope'])) {
                        $entry['status'] = 'interrupted';
                        $entry['error'] = 'transfer_interrupted';
                        $entries[$OperationID] = $entry;
                        $this->WriteDraftTransfers($entries);
                        $this->DebugEvent('FEHLER', 'Unterbrochenen Systemauftrag erkannt. Keine automatische Wiederholung; bestätigten Stand prüfen.', ['Systembezug' => $entry['request']['binding']['systemId'], 'MöglicherweiseGespeichert' => $entry['mayHaveStored']]);
                    }
                    $this->SaveState($state);
                    $request = $entry['request'];
                    return json_encode(['ok' => true, 'operation' => $OperationID, 'kind' => $request['kind'], 'status' => $entry['status'],
                        'error'              => $entry['error'], 'details' => $entry['details'], 'mayHaveStored' => $entry['mayHaveStored'],
                        'checksum'           => $request['checksum'], 'revision' => $request['revision'], 'automationCount' => $request['automationCount'],
                        'canStore'           => $entry['scope'] === $state['scope'] && $this->DraftValidationCurrent($state, $Owner, $OperationID, $request['checksum'])], JSON_THROW_ON_ERROR);
                });
            } catch (Throwable) {
                $this->DebugEvent('FEHLER', 'Gespeicherte Auftragsrückmeldung nicht lesbar. Kein Auftrag wurde erneut gesendet.');
                return '{"ok":false,"error":"transfer_unavailable"}';
            }
        });
    }

    private function DraftAuthority(int $owner, string $operation): array
    {
        if (!IPS_InstanceExists($owner) || IPS_GetInstance($owner)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE
            || preg_match('/\A[a-f0-9]{32}\z/D', $operation) !== 1) throw new RuntimeException('ownership_required');
        $authority = json_decode(Z2MA_GetWorkspaceTransferAuthority($owner, $operation), true, 16, JSON_THROW_ON_ERROR);
        $request = $authority['request'] ?? [];
        $binding = $request['binding'] ?? [];
        $marker = $this->OwnershipMarker();
        if (($authority['ok'] ?? false) !== true || !is_bool($authority['documentCurrent'] ?? null) || !DraftTransferProtocol::RequestValid($request)
            || ($request['owner'] ?? 0) !== $owner || ($request['operation'] ?? '') !== $operation
            || !in_array($request['kind'] ?? '', ['validate', 'store'], true) || SystemContract::RegistryError([$binding]) !== ''
            || $binding['connectionInstance'] !== $this->InstanceID || $binding['mqttParent'] !== $this->GetParentID()
            || $binding['systemId'] !== $this->ReadPropertyString('SystemID') || $binding['generation'] !== $this->ReadPropertyInteger('Generation')
            || $binding['baseTopic'] !== $this->ReadPropertyString('BaseTopic') || ($marker['role'] ?? '') !== 'connection'
            || $marker['owner'] !== $owner || $marker['operation'] !== ($request['epoch'] ?? '')
            || $marker['systemId'] !== $binding['systemId'] || $marker['generation'] !== $binding['generation']) throw new RuntimeException('ownership_required');
        return $authority;
    }

    private function DraftFrameAllowed(array &$state, array $frame): bool
    {
        $request = $state['workflow']['workspaceRequest'] ?? null;
        if ($request === null) return true;
        try {
            $authority = $this->DraftAuthority($request['owner'], $request['operation']);
            if ($authority['request'] !== $request || (!$authority['documentCurrent'] && $state['workflow']['wire'] !== 'status')) throw new RuntimeException();
        } catch (Throwable) {
            $this->FinishExtensionWorkflow($state, 'cancelled', 'cancelled');
            $this->DebugEvent('FEHLER', 'Auftrag vor dem nächsten MQTT-Schritt gestoppt: Entwurf, Besitzer oder Systemzuordnung geändert. Kein falsches System angesprochen.');
            return false;
        }
        if ($state['workflow']['wire'] === 'store') {
            $entries = $this->ReadDraftTransfers();
            $entries[$request['operation']]['mayHaveStored'] = true;
            $this->WriteDraftTransfers($entries);
        }
        return true;
    }

    private function DraftValidationCurrent(array $state, int $owner, string $operation, string $checksum): bool
    {
        $receipt = $state['validation'];
        return $state['extensionConfirmed'] && $state['mqttConnected'] && $state['bridgeOnline'] && $receipt !== null
            && ($receipt['workspaceOwner'] ?? 0) === $owner && ($receipt['workspaceRequest'] ?? '') === $operation
            && $receipt['checksum'] === $checksum && DraftTransferProtocol::ReceiptMatches($receipt, $state['extension']);
    }

    private function FinishDraftTransfer(array $state, string $status, string $error): void
    {
        $request = $state['workflow']['workspaceRequest'] ?? null;
        if ($request === null) return;
        $entries = $this->ReadDraftTransfers();
        $key = $request['operation'];
        if (!isset($entries[$key])) return;
        $entries[$key]['status'] = ($state['workflow']['verifyStorage'] ?? false) && $status !== 'stored' ? 'stored_unconfirmed' : $status;
        $entries[$key]['error'] = $error;
        $entries[$key]['details'] = $state['validationDetails'] === null ? null : array_intersect_key($state['validationDetails'], ['issues' => true, 'truncated' => true]);
        $this->WriteDraftTransfers($entries);
        $this->DebugEvent($error === '' ? 'AKTION' : 'FEHLER', 'Prüf- oder Übertragungsergebnis genau diesem System zugeordnet. Andere Verbindungen bleiben unabhängig.', ['Systembezug' => $request['binding']['systemId'], 'Ergebnis' => $entries[$key]['status'], 'Ursache' => $error]);
    }

    private function ReceiveDraftTransfer(array &$state, stdClass $payload): ?array
    {
        $workflow = $state['workflow'];
        $request = $workflow['workspaceRequest'];
        if (($payload->status ?? null) !== 'ok' || !($payload->data ?? null) instanceof stdClass) {
            $code = $payload->error->code ?? 'invalid_response';
            if (!is_string($code) || !in_array($code, ExtensionContract::ERROR_CODES, true)) $code = 'invalid_response';
            if (in_array($workflow['wire'], ['validate', 'store'], true) && in_array($code, ['invalid_yaml', 'invalid_schema', 'invalid_semantics'], true)) {
                $details = ValidationFeedback::Normalize($payload->error->details ?? null);
                if ($details !== null) $state['validationDetails'] = $details + ['names' => ValidationFeedback::Names($workflow['yaml'])];
                foreach ($details['issues'] ?? [] as $issue) $this->DebugEvent('FEHLER', 'Z2M hat einen Eintrag abgelehnt. Die genaue Stelle steht im Systemstatus.', ['Systembezug' => $request['binding']['systemId'], 'Position' => $issue['path'], 'Ursache' => $issue['reason']]);
            }
            $state['validation'] = null;
            $this->FinishExtensionWorkflow($state, 'error', $code);
            return null;
        }
        if ($workflow['wire'] === 'status') {
            $metadata = ExtensionContract::Status($payload->data);
            if ($metadata === null || !$metadata['ready']) {
                $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
                return null;
            }
            if ($workflow['verifyStorage']) {
                $error = DraftTransferProtocol::StoredStatusError($metadata, $workflow['storedSnapshot']);
                if ($error === '') {
                    $state['extension'] = $metadata;
                    $state['extensionConfirmed'] = true;
                    $state['runtimeConfirmed'] = true;
                }
                $this->FinishExtensionWorkflow($state, $error === '' ? 'stored' : 'stored_unconfirmed', $error);
                return null;
            }
            $state['extension'] = $metadata;
            $state['extensionConfirmed'] = true;
            $state['runtimeConfirmed'] = $metadata['runtimeVersion'] === 1;
            $state['workflow']['target'] = $metadata;
            if (!in_array($metadata['storage'], ['ok', 'empty'], true) || !DraftTransferProtocol::BaseMatches($request, $metadata)) {
                $state['validation'] = null;
                $this->FinishExtensionWorkflow($state, 'error', 'revision_conflict');
                return null;
            }
            if ($request['kind'] === 'validate') return $this->ExtensionFrame($state, 'validate', ['yaml' => $workflow['yaml']]);
            if (!$this->DraftValidationCurrent($state, $request['owner'], $request['validatedRequest'], $request['checksum'])) {
                $this->FinishExtensionWorkflow($state, 'error', 'not_validated');
                return null;
            }
            $receipt = $state['validation'];
            if ($receipt['revision'] !== $metadata['storedRevision'] + 1 && !($receipt['revision'] === $metadata['storedRevision'] && $receipt['checksum'] === $metadata['checksum'])) {
                $this->FinishExtensionWorkflow($state, 'error', 'revision_reused');
                return null;
            }
            return $this->ExtensionFrame($state, 'store', ['yaml' => $workflow['yaml'], 'session' => $metadata['session'], 'expected_revision' => $metadata['storedRevision']]);
        }
        if ($workflow['wire'] === 'validate') {
            $receipt = DraftTransferProtocol::Validation($payload->data, $request['checksum'], $workflow['target'], $state['extension']);
            if ($receipt === null || $receipt['revision'] !== $request['revision'] || $receipt['automationCount'] !== $request['automationCount']) {
                $state['validation'] = null;
                $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
                return null;
            }
            $state['validation'] = $receipt + ['workspaceOwner' => $request['owner'], 'workspaceRequest' => $request['operation']];
            $this->FinishExtensionWorkflow($state, 'validated');
            return null;
        }
        $metadata = DraftTransferProtocol::Storage($payload->data, $state['validation'], $request['checksum']);
        if ($metadata === null) {
            $state['validation'] = null;
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return null;
        }
        $state['extensionConfirmed'] = false;
        $state['runtimeConfirmed'] = false;
        $state['validation'] = null;
        $state['workflow']['verifyStorage'] = true;
        $state['workflow']['storedSnapshot'] = $metadata + ['session' => $workflow['target']['session']];
        $this->DebugEvent('AKTION', 'Z2M hat die Datei angenommen. Gespeicherten Stand jetzt durch eine frische Statusantwort bestätigen; keine Aktivierung.', ['Systembezug' => $request['binding']['systemId'], 'Revision' => $metadata['storedRevision']]);
        return $this->ExtensionFrame($state, 'status');
    }

    private function ReadDraftTransfers(): array
    {
        $raw = $this->ReadAttributeString('ConnectionDraftTransfers');
        $entries = $raw === '' ? [] : json_decode($raw, true, 24, JSON_THROW_ON_ERROR);
        if (!is_array($entries) || count($entries) > 32 || strlen($raw) > 262144) throw new RuntimeException('transfer_unavailable');
        foreach ($entries as $key => $entry) {
            if (!is_array($entry) || !DraftTransferProtocol::RequestValid($entry['request'] ?? null) || $entry['request']['operation'] !== $key
                || !in_array($entry['status'] ?? null, ['pending', 'validated', 'stored', 'stored_unconfirmed', 'error', 'cancelled', 'timeout', 'interrupted'], true)
                || !is_string($entry['error'] ?? null) || !is_bool($entry['mayHaveStored'] ?? null) || !is_string($entry['scope'] ?? null)
                || !DraftTransferProtocol::DetailsValid($entry['details'] ?? null)) throw new RuntimeException('transfer_unavailable');
        }
        return $entries;
    }

    private function WriteDraftTransfers(array $entries): void
    {
        $raw = json_encode(array_slice($entries, -32, null, true), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if (strlen($raw) > 262144) throw new RuntimeException('transfer_unavailable');
        $this->WriteAttributeString('ConnectionDraftTransfers', $raw);
    }
}
