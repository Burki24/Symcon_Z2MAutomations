<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

/** Explicit per-system validate/store jobs; opening a view never sends an MQTT command. */
trait WorkspaceTransfer
{
    public function RequestWorkspaceTransfer(string $SystemID, int $Generation, string $Fingerprint, string $Action, string $OperationID, string $TransferContext): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $Action, $OperationID, $TransferContext): string
        {
            try {
                if (!in_array($Action, ['validate', 'store'], true)) throw new RuntimeException('invalid_request');
                $operation = $this->WorkspaceOperation($OperationID, [$SystemID, $Generation, $Fingerprint, $Action, $TransferContext]);
                $journal = $this->ReadWorkspaceTransfers();
                if (isset($journal['receipts'][$OperationID])) {
                    if ($journal['receipts'][$OperationID] !== $operation['request']) throw new RuntimeException('operation_conflict');
                    $this->DebugEvent('AKTION', 'Bereits angenommenen Systemauftrag erneut gelesen. Keine zweite Prüfung oder Übertragung gesendet.', ['Systembezug' => $SystemID, 'Auftrag' => $Action]);
                    return $this->GetWorkspaceTransferStatus($SystemID);
                }
                $record = $this->WithWorkspaceLock(fn (): array => $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint));
                $view = $this->WorkspaceTransferView($record);
                if ($view['context'] !== $TransferContext) throw new RuntimeException('stale_transfer');
                if (!$view[$Action === 'validate' ? 'canValidate' : 'canStore']) throw new RuntimeException($view['blocked'] !== '' ? $view['blocked'] : 'not_validated');
                if ($Action === 'store') $this->CheckWorkspaceTransferActions($record);
                $document = $record['data']['document'];
                $editor = $document['editorDraft'];
                $request = ['owner'    => $this->InstanceID, 'operation' => $OperationID, 'binding' => $record['binding'], 'epoch' => $record['ownershipEpoch'],
                    'kind'             => $Action, 'checksum' => $editor['draftChecksum'], 'revision' => $editor['configuration']['revision'], 'automationCount' => count($editor['configuration']['automations']),
                    'baseRevision'     => $editor['baseRevision'], 'baseChecksum' => $editor['baseChecksum'],
                    'validatedRequest' => $Action === 'store' ? ($journal['systems'][$SystemID]['request']['operation'] ?? '') : ''];
                $this->WithWorkspaceLock(function () use ($SystemID, $Generation, $Fingerprint, $request, $operation, $document): void
                {
                    $current = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                    if (!$this->WorkspaceConnectionCurrent($current['binding'])) throw new RuntimeException('binding_changed');
                    $journal = $this->ReadWorkspaceTransfers();
                    $journal['systems'][$SystemID] = ['request' => $request, 'status' => 'prepared', 'error' => '', 'details' => null,
                        'names'                                 => ValidationFeedback::Names($document['yaml']), 'canStore' => false, 'mayHaveStored' => false];
                    $journal['receipts'][$request['operation']] = $operation['request'];
                    $journal['receipts'] = array_slice($journal['receipts'], -32, null, true);
                    $this->WriteWorkspaceTransfers($journal);
                });
                $this->DebugEvent('SENDEN', 'Entwurf ausdrücklich an genau die zugeordnete Z2M-Verbindung übergeben. Keine automatische Aktivierung.', ['Systembezug' => $SystemID, 'Verbindung' => $record['binding']['connectionInstance'], 'Auftrag' => $Action, 'Revision' => $request['revision'], 'YAMLBytes' => strlen($document['yaml'])]);
                try {
                    $reply = json_decode(Z2MC_RequestDraftTransfer($record['binding']['connectionInstance'], $this->InstanceID, $OperationID, $document['yaml']), true, 24, JSON_THROW_ON_ERROR);
                    $this->AcceptWorkspaceTransferResult($SystemID, $OperationID, $reply);
                } catch (Throwable) {
                    $this->DebugEvent('FEHLER', 'Übergabe an die Verbindung wurde nicht eindeutig bestätigt. Auftrag bleibt nachvollziehbar; keine automatische Wiederholung.', ['Systembezug' => $SystemID]);
                }
                $this->SetTimerInterval('WorkspaceTransferRefresh', 1000);
                return $this->GetWorkspaceTransferStatus($SystemID);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    /** Pure authority snapshot, deliberately callable across the current owner's entry fence. */
    public function GetWorkspaceTransferAuthority(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                if (preg_match('/\A[a-f0-9]{32}\z/D', $OperationID) !== 1) throw new RuntimeException();
                $journal = $this->ReadWorkspaceTransfers();
                $matches = array_values(array_filter($journal['systems'], static fn (array $job): bool => $job['request']['operation'] === $OperationID));
                if (count($matches) !== 1) throw new RuntimeException();
                $job = $matches[0];
                $request = $job['request'];
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $request['binding']['systemId']);
                $marker = $this->OwnershipMarker();
                if (!in_array($job['status'], ['prepared', 'pending'], true) || $record['binding'] !== $request['binding']
                    || ($record['ownershipEpoch'] ?? '') !== $request['epoch'] || ($marker['role'] ?? '') !== 'manager'
                    || $marker['owner'] !== $this->InstanceID || $marker['operation'] !== $request['epoch'] || !$this->MigrationWorkspaceWritable()) throw new RuntimeException();
                return json_encode(['ok' => true, 'request' => $request, 'documentCurrent' => !WorkspaceState::Dirty($record)
                    && ($record['data']['document']['editorDraft']['draftChecksum'] ?? '') === $request['checksum']], JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                $this->DebugEvent('FEHLER', 'Systemauftrag besitzt keine aktuelle Freigabe dieser Zentrale. Es wird kein weiterer Übertragungsschritt erlaubt.');
                return '{"ok":false}';
            }
        });
    }

    public function GetWorkspaceTransferStatus(string $SystemID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID): string
        {
            try {
                $this->SyncWorkspaceTransfer($SystemID);
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID);
                $view = $this->WorkspaceTransferView($record);
                $this->UpdateWorkspaceTransferDisplay($SystemID, $view);
                return $this->WorkspaceResult('', ['transfer' => $view]);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function RefreshWorkspaceTransfers(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            $journal = $this->ReadWorkspaceTransfers();
            foreach (array_keys($journal['systems']) as $system) $this->SyncWorkspaceTransfer($system);
            $state = $this->ReadWorkspace();
            if ($state['selectedSystem'] !== '') $this->UpdateWorkspaceTransferDisplay($state['selectedSystem'], $this->WorkspaceTransferView($state['systems'][$state['selectedSystem']]));
            $pending = array_filter($this->ReadWorkspaceTransfers()['systems'], static fn (array $job): bool => in_array($job['status'], ['prepared', 'pending'], true));
            $this->SetTimerInterval('WorkspaceTransferRefresh', $pending === [] ? 0 : 1000);
        });
    }

    private function SyncWorkspaceTransfer(string $system): void
    {
        $journal = $this->ReadWorkspaceTransfers();
        $job = $journal['systems'][$system] ?? null;
        if ($job === null) return;
        try {
            $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
            if ($record['binding'] !== $job['request']['binding'] || ($record['ownershipEpoch'] ?? '') !== $job['request']['epoch']
                || !$this->WorkspaceConnectionCurrent($record['binding'])) {
                if (in_array($job['status'], ['prepared', 'pending'], true)) {
                    $this->AcceptWorkspaceTransferResult($system, $job['request']['operation'], ['ok' => true, 'operation' => $job['request']['operation'], 'kind' => $job['request']['kind'],
                        'checksum'                                                                    => $job['request']['checksum'], 'revision' => $job['request']['revision'], 'automationCount' => $job['request']['automationCount'],
                        'status'                                                                      => 'interrupted', 'error' => 'binding_changed', 'details' => null, 'canStore' => false,
                        'mayHaveStored'                                                               => $job['mayHaveStored'] || $job['request']['kind'] === 'store']);
                }
                return;
            }
            $reply = json_decode(Z2MC_GetDraftTransferResult($record['binding']['connectionInstance'], $this->InstanceID, $job['request']['operation']), true, 24, JSON_THROW_ON_ERROR);
            if (($reply['ok'] ?? false) === true) $this->AcceptWorkspaceTransferResult($system, $job['request']['operation'], $reply);
            elseif (($reply['error'] ?? '') === 'transfer_missing' && in_array($job['status'], ['prepared', 'pending'], true)) {
                $this->AcceptWorkspaceTransferResult($system, $job['request']['operation'], ['ok' => true, 'operation' => $job['request']['operation'], 'kind' => $job['request']['kind'],
                    'checksum'                                                                    => $job['request']['checksum'], 'revision' => $job['request']['revision'], 'automationCount' => $job['request']['automationCount'],
                    'status'                                                                      => 'interrupted', 'error' => 'transfer_interrupted', 'details' => null, 'canStore' => false, 'mayHaveStored' => false]);
            }
        } catch (Throwable) {
            $this->DebugEvent('AKTION', 'Systemrückmeldung momentan nicht lesbar. Vorhandenes Ergebnis bleibt erhalten; kein Auftrag erneut gesendet.', ['Systembezug' => $system]);
        }
    }

    private function AcceptWorkspaceTransferResult(string $system, string $operation, array $reply): void
    {
        $changedBase = false;
        $changed = $this->WithWorkspaceLock(function () use ($system, $operation, $reply, &$changedBase): bool
        {
            $journal = $this->ReadWorkspaceTransfers();
            $job = $journal['systems'][$system] ?? null;
            if ($job === null || $job['request']['operation'] !== $operation) return false;
            if (($reply['ok'] ?? false) !== true) {
                $error = $reply['error'] ?? 'transfer_unavailable';
                if (!is_string($error) || !isset(WorkspaceState::MESSAGES[$error])) $error = 'transfer_unavailable';
                $reply = ['status' => 'error', 'error' => $error, 'canStore' => false, 'mayHaveStored' => false, 'details' => null];
            } else {
                foreach (['operation', 'kind', 'checksum', 'revision', 'automationCount'] as $key) if (($reply[$key] ?? null) !== $job['request'][$key]) throw new RuntimeException('stale_transfer');
                if (!in_array($reply['status'] ?? '', ['pending', 'validated', 'stored', 'stored_unconfirmed', 'error', 'cancelled', 'timeout', 'interrupted'], true)
                    || !is_bool($reply['canStore'] ?? null) || !is_bool($reply['mayHaveStored'] ?? null) || !is_string($reply['error'] ?? null)
                    || !DraftTransferProtocol::DetailsValid($reply['details'] ?? null)) throw new RuntimeException('stale_transfer');
            }
            $before = $job;
            foreach (['status', 'error', 'canStore', 'mayHaveStored', 'details'] as $key) $job[$key] = $reply[$key] ?? null;
            $journal['systems'][$system] = $job;
            if ($job['request']['kind'] === 'store' && $job['mayHaveStored']) $journal['writtenEpochs'][$job['request']['epoch']] = true;
            $this->WriteWorkspaceTransfers($journal);
            if ($job['status'] === 'stored') {
                $workspace = $this->ReadWorkspace();
                $record = $this->WorkspaceRecord($workspace, $system);
                if ($record['binding'] === $job['request']['binding'] && ($record['ownershipEpoch'] ?? '') === $job['request']['epoch']
                    && ($record['data']['document']['editorDraft']['draftChecksum'] ?? '') === $job['request']['checksum']
                    && ($record['data']['document']['editorDraft']['baseRevision'] !== $job['request']['revision']
                        || $record['data']['document']['editorDraft']['baseChecksum'] !== $job['request']['checksum'])) {
                    $record['data']['document']['editorDraft']['baseRevision'] = $job['request']['revision'];
                    $record['data']['document']['editorDraft']['baseChecksum'] = $job['request']['checksum'];
                    $workspace['systems'][$system] = $record;
                    $this->WriteWorkspace($workspace);
                    $changedBase = true;
                }
            }
            return $job !== $before;
        });
        if ($changed) $this->DebugEvent('AKTION', 'Prüf- oder Übertragungsstatus genau dieses Systems aktualisiert. Andere Entwürfe bleiben erhalten.', ['Systembezug' => $system, 'Ergebnis' => $reply['status'] ?? 'error']);
        if ($changedBase) {
            $state = $this->ReadWorkspace();
            $this->DebugEvent('AKTION', 'Bestätigte Speicherrevision als neue Bearbeitungsbasis übernommen. YAML, Regeln und offene Eingaben bleiben erhalten; keine Aktivierung.', ['Systembezug' => $system]);
            if ($state['selectedSystem'] === $system && !WorkspaceState::Dirty($state['systems'][$system])) $this->RequestWorkspaceFormReload();
        }
    }

    private function WorkspaceTransferView(array $record): array
    {
        $system = $record['binding']['systemId'];
        $job = $this->ReadWorkspaceTransfers()['systems'][$system] ?? null;
        $current = $job !== null && $job['request']['binding'] === $record['binding'] && $job['request']['epoch'] === ($record['ownershipEpoch'] ?? '')
            && $job['request']['checksum'] === ($record['data']['document']['editorDraft']['draftChecksum'] ?? '');
        $available = true;
        try {
            $owner = json_decode(Z2MC_GetSystemOwnership($record['binding']['connectionInstance']), true, 16, JSON_THROW_ON_ERROR);
            $info = $this->WorkspaceInfo($record['binding']);
        } catch (Throwable) {
            $available = false;
            $owner = $info = [];
        }
        $marker = $this->OwnershipMarker();
        $pending = $job !== null && in_array($job['status'], ['prepared', 'pending'], true);
        $blocked = match (true) {
            !$available                                            => 'connection_unavailable',
            !$this->WorkspaceConnectionCurrent($record['binding']) => 'binding_changed',
            ($marker['role'] ?? '') !== 'manager' || ($marker['owner'] ?? 0) !== $this->InstanceID || !$this->MigrationWorkspaceWritable()
                || ($marker['operation'] ?? '') !== ($record['ownershipEpoch'] ?? '')
                || ($owner['role'] ?? '') !== 'connection' || ($owner['owner'] ?? 0) !== $this->InstanceID || ($owner['operation'] ?? '') !== ($record['ownershipEpoch'] ?? '') => 'ownership_required',
            $record['data']['document'] === null                                                                                                                                => 'no_document',
            WorkspaceState::Dirty($record)                                                                                                                                      => 'open_input',
            $pending                                                                                                                                                            => 'transfer_pending',
            !($info['state']['mqttConnected'] ?? false) || !($info['state']['bridgeOnline'] ?? false)                                                                           => 'not_connected',
            default                                                                                                                                                             => ''
        };
        $status = $job['status'] ?? 'idle';
        $message = match (true) {
            $pending                                                                      => ($job['request']['kind'] === 'validate' ? 'Entwurf wird in diesem Z2M-System geprüft.' : 'Entwurf wird an dieses Z2M-System übertragen. Bestätigung steht noch aus.'),
            $blocked !== ''                                                               => $this->WorkspaceTransferError($blocked),
            $job !== null && !$current                                                    => 'Das angezeigte Ergebnis gehört zu einem früheren Entwurfsstand oder einer früheren Zuordnung. Bitte den aktuellen Entwurf erneut prüfen.',
            $status === 'validated' && ($job['canStore'] ?? false)                        => 'Dieser Entwurf wurde in Z2M erfolgreich geprüft und kann jetzt übertragen werden.',
            $status === 'validated'                                                       => 'Die frühere Prüfung ist nicht mehr zur Übertragung freigegeben. Bitte den Entwurf erneut prüfen.',
            $status === 'stored'                                                          => 'Übertragung und gespeicherter Stand wurden von Z2M bestätigt. Es wurde keine Automation aktiviert.',
            $status === 'stored_unconfirmed'                                              => 'Z2M hat die Datei angenommen, der anschließende Status wurde jedoch nicht bestätigt. Nicht erneut übertragen; zunächst den Verbindungsstatus prüfen.',
            $job !== null && $job['request']['kind'] === 'store' && $job['mayHaveStored'] => 'Die Übertragung wurde nicht sicher bestätigt. Die Datei könnte bereits in Z2M gespeichert sein. Es wird nichts automatisch wiederholt. Bitte zuerst den Verbindungsstatus und den gespeicherten Stand prüfen.',
            $job !== null && ($job['error'] ?? '') !== ''                                 => $this->WorkspaceTransferError($job['error']),
            default                                                                       => 'Für diesen Entwurf liegt noch keine erfolgreiche Prüfung in Z2M vor. Bitte „Entwurf in Z2M prüfen“ wählen.'
        };
        $details = $current && is_array($job['details'] ?? null) ? ValidationFeedback::Text($job['details'] + ['names' => $job['names']], fn (string $text): string => $this->Translate($text)) : '';
        return ['systemId'      => $system, 'generation' => $record['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($record),
            'context'           => WorkspaceState::Fingerprint($job ?? []), 'operation' => $job['request']['operation'] ?? '', 'kind' => $job['request']['kind'] ?? '',
            'status'            => $status, 'message' => $message, 'details' => $details, 'blocked' => $blocked, 'pending' => $pending,
            'canValidate'       => $blocked === '', 'canStore' => $blocked === '' && $current && $status === 'validated' && $job['canStore'],
            'receiverConfirmed' => $info['state']['extensionConfirmed'] ?? false,
            'storedRevision'    => $info['state']['extension']['storedRevision'] ?? null,
            'activeRevision'    => ($info['state']['runtimeConfirmed'] ?? false) ? ($info['state']['extension']['activeRevision'] ?? null) : null];
    }

    private function WorkspaceTransferError(string $error): string
    {
        return isset(WorkspaceState::MESSAGES[$error]) ? WorkspaceState::MESSAGES[$error] : $this->Translate(ExtensionContract::ErrorText($error));
    }

    private function CheckWorkspaceTransferActions(array $record): void
    {
        $this->WithWorkspaceEditor($record['binding']['systemId'], $record['binding']['generation'], WorkspaceState::Fingerprint($record), false, function (): void
        {
            if (!$this->workspaceEditorContext['catalogAvailable']) throw new RuntimeException('catalog_unavailable');
            $configuration = $this->ReadEditorDraft()['configuration'];
            foreach (['automations', 'sequences'] as $kind) foreach ($configuration[$kind] ?? [] as $index => $entry) {
                if ($this->DesignerEditSectionError('actions', $entry['actions'] ?? null) !== '') {
                    $this->DebugEvent('FEHLER', 'Übertragung vor dem Senden gesperrt: Aktionsziel, Kanal, Wert oder Schaltrecht nicht mehr verfügbar.', ['Bereich' => $kind, 'Eintrag' => $index + 1]);
                    throw new RuntimeException('actions_unavailable');
                }
            }
        });
    }

    private function UpdateWorkspaceTransferDisplay(string $system, array $view): void
    {
        $state = $this->ReadWorkspace();
        if ($state['selectedSystem'] !== $system) return;
        $this->UpdateFormField('WorkspaceTransferMessage', 'caption', $view['message']);
        $this->UpdateFormField('WorkspaceTransferDetails', 'caption', $view['details']);
        $this->UpdateFormField('WorkspaceTransferContext', 'value', $view['context']);
        $this->UpdateFormField('WorkspaceValidate', 'enabled', $view['canValidate']);
        $this->UpdateFormField('WorkspaceStore', 'enabled', $view['canStore']);
        if ($view['pending']) $this->SetTimerInterval('WorkspaceTransferRefresh', 1000);
        $this->SetValue('TransferSystem', $state['systems'][$system]['name'] . ' · ' . $state['systems'][$system]['binding']['baseTopic']);
        $this->DebugFlowWriteValue('TransferStatus', $view['message'], __FUNCTION__, __LINE__);
        $this->SetValue('TransferError', $view['details']);
    }

    private function WorkspaceTransferBlocksRollback(string $epoch): bool
    {
        if ($this->ReadWorkspaceRuntime()['controlledEpochs'][$epoch] ?? false) return true;
        $journal = $this->ReadWorkspaceTransfers();
        if ($journal['writtenEpochs'][$epoch] ?? false) return true;
        foreach ($journal['systems'] as $job) if ($job['request']['epoch'] === $epoch && $job['request']['kind'] === 'store'
            && in_array($job['status'], ['prepared', 'pending'], true)) return true;
        return false;
    }

    private function ReadWorkspaceTransfers(): array
    {
        $raw = $this->ReadAttributeString('WorkspaceTransfers');
        $journal = $raw === '' ? ['version' => 1, 'systems' => [], 'receipts' => [], 'writtenEpochs' => []] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($journal) || ($journal['version'] ?? null) !== 1 || !is_array($journal['systems'] ?? null) || count($journal['systems']) > 8
            || !is_array($journal['receipts'] ?? null) || count($journal['receipts']) > 32 || !is_array($journal['writtenEpochs'] ?? null) || strlen($raw) > 1048576) throw new RuntimeException('transfer_unavailable');
        foreach ($journal['systems'] as $system => $job) {
            if (!is_array($job) || !DraftTransferProtocol::RequestValid($job['request'] ?? null) || $job['request']['binding']['systemId'] !== $system
                || !in_array($job['status'] ?? null, ['prepared', 'pending', 'validated', 'stored', 'stored_unconfirmed', 'error', 'cancelled', 'timeout', 'interrupted'], true)
                || !is_string($job['error'] ?? null) || !is_bool($job['canStore'] ?? null) || !is_bool($job['mayHaveStored'] ?? null)
                || !is_array($job['names'] ?? null) || !DraftTransferProtocol::DetailsValid($job['details'] ?? null)) throw new RuntimeException('transfer_unavailable');
            foreach ($job['names'] as $index => $names) {
                if ($index === 'sequences') {
                    if (!is_array($names)) throw new RuntimeException('transfer_unavailable');
                    foreach ($names as $name) if (!is_string($name)) throw new RuntimeException('transfer_unavailable');
                } elseif (!is_string($names)) throw new RuntimeException('transfer_unavailable');
            }
        }
        foreach ($journal['receipts'] as $operation => $digest) if (!DraftTransferProtocol::Operation($operation) || !ExtensionContract::Checksum($digest)) throw new RuntimeException('transfer_unavailable');
        foreach ($journal['writtenEpochs'] as $epoch => $written) if (!DraftTransferProtocol::Operation($epoch) || $written !== true) throw new RuntimeException('transfer_unavailable');
        return $journal;
    }

    private function WriteWorkspaceTransfers(array $journal): void
    {
        $raw = json_encode($journal, WorkspaceState::JSON_FLAGS);
        if (strlen($raw) > 1048576) throw new RuntimeException('transfer_unavailable');
        if ($raw !== $this->ReadAttributeString('WorkspaceTransfers')) $this->WriteAttributeString('WorkspaceTransfers', $raw);
    }
}
