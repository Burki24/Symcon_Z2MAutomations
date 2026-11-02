<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;
use Throwable;

trait ExtensionIntegration
{
    public function InstallExtension(bool $Notify = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Notify)
        {
            $code = @file_get_contents(__DIR__ . '/z2m/' . ExtensionContract::NAME, false, null, 0, Z2MProtocol::MAX_PAYLOAD_BYTES + 1);
            $valid = is_string($code) && $code !== '' && strlen($code) <= Z2MProtocol::MAX_PAYLOAD_BYTES && preg_match('//u', $code) === 1;
            return $this->StartExtensionWorkflow('install', '', $valid ? $code : '', $Notify);
        });
    }

    public function RequestExtensionStatus(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function ()
        {
            return $this->StartExtensionWorkflow('status');
        });
    }

    public function ValidateYaml(string $Yaml): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Yaml)
        {
            return $this->StartExtensionWorkflow('validate', $Yaml);
        });
    }

    public function StoreYaml(string $Yaml): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Yaml)
        {
            return $this->StartExtensionWorkflow('store', $Yaml);
        });
    }

    public function ActivateAutomations(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->StartExtensionWorkflow('activate'));
    }

    public function StopAutomations(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->StartExtensionWorkflow('deactivate'));
    }

    private function NewExtensionState(): array
    {
        return ['extension' => null, 'extensionConfirmed' => false, 'runtimeConfirmed' => false, 'workflow' => null, 'validation' => null,
            'operation'     => ['kind' => 'none', 'status' => 'idle', 'transaction' => ''], 'lastError' => '', 'validationDetails' => null, 'execution' => null];
    }

    private function StartExtensionWorkflow(string $kind, string $yaml = '', string $code = '', bool $notify = false, bool $automatic = false, ?int $importEvent = null, bool $preserveDraft = false, string $executionRule = ''): string
    {
        $this->DebugEvent('AKTION', 'Erweiterungsauftrag prüfen.', ['Auftrag' => $kind, 'YAMLBytes' => strlen($yaml), 'Artefaktbytes' => strlen($code)]);
        $frame = $this->WithTransportLock(function () use ($kind, $yaml, $code, $notify, $automatic, $importEvent, $preserveDraft, $executionRule): array
        {
            $state = $this->RefreshState();
            if (in_array($kind, ['run_once', 'stop_rule'], true) && preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $executionRule) !== 1) {
                $state['lastError'] = 'rule_missing';
                $this->DebugEvent('FEHLER', 'Einzelauftrag ohne gültige Regelauswahl abgewiesen; laufender Auftrag bleibt unverändert.');
                $this->SaveState($state);
                return [];
            }
            if (($kind === 'deactivate' || ($kind === 'stop_rule' && ($state['workflow']['kind'] ?? '') !== 'deactivate'))
                && $state['workflow'] !== null && $state['workflow']['kind'] !== $kind) {
                $this->FinishExtensionWorkflow($state, 'cancelled', 'cancelled');
                $this->DebugEvent('AKTION', 'Stopp hat Vorrang; vorherigen Auftrag nicht erneut senden.');
            }
            $checksum = strlen($yaml) <= ExtensionContract::MAX_YAML_BYTES ? hash('sha256', $yaml) : '';
            if ($automatic && ($state['workflow'] !== null || !$state['mqttConnected'] || !$state['bridgeOnline'])) {
                $this->DebugEvent('AKTION', 'Automatische Erweiterungsabfrage zugunsten eines laufenden Auftrags oder geänderter Verbindung ausgelassen.');
                return [];
            }
            if ($importEvent !== null && $state['workflow'] !== null) {
                $this->DebugEvent('AKTION', 'Import-Gerätedaten wegen laufendem Auftrag nicht erneut angefordert.');
                return [];
            }
            if ($state['workflow'] !== null) {
                if ($state['workflow']['kind'] === $kind && $state['workflow']['checksum'] === $checksum
                    && ($state['workflow']['preserveDraft'] ?? false) === $preserveDraft && ($state['workflow']['executionRule'] ?? '') === $executionRule) {
                    if ($notify) {
                        $state['workflow']['notify'] = true;
                        $this->SaveState($state);
                    }
                    return ['existing' => $state['workflow']['id']];
                }
                if ($notify) {
                    $this->NotifyExtensionInstallation('busy');
                }
                $state['lastError'] = 'busy';
                $this->SaveState($state);
                return [];
            }
            $error = '';
            if ($kind === 'validate') {
                if (($state['validationDetails'] ?? null) !== null) {
                    $this->DebugEvent('AKTION', 'Neue Entwurfsprüfung gestartet; bisherige detaillierte Prüfungsfehler werden durch das neue Ergebnis ersetzt.');
                }
                $state['validationDetails'] = null;
            }
            if (in_array($kind, ['validate', 'store'], true)) {
                if (!ExtensionContract::ValidYamlText($yaml)) {
                    $error = 'invalid_yaml';
                } else {
                    $this->WriteAttributeString('YamlDraft', $yaml);
                    if (($state['validation']['checksum'] ?? null) !== $checksum) {
                        $state['validation'] = null;
                    }
                }
            }
            if ($error === '') {
                $error = !$state['mqttConnected'] ? 'not_connected' : (!$state['bridgeOnline'] && $kind !== 'deactivate' ? 'bridge_offline' : '');
            }
            if ($error === '' && $kind === 'install') {
                if ($state['externalJS'] === null) {
                    $this->DebugEvent('AKTION', 'Freigabe für externes JavaScript noch unbekannt; Installation wird durch die offizielle Z2M-Schnittstelle geprüft.');
                }
                $error = $state['externalJS'] === false ? 'external_js_disabled' : ($code === '' ? 'artifact_unavailable' : '');
            }
            if ($kind === 'store' && $error === '' && $state['validation'] === null) {
                $error = 'not_validated';
            }
            if ($error !== '') {
                $this->DebugEvent('FEHLER', 'Erweiterungsauftrag abgelehnt.', ['Auftrag' => $kind, 'Grund' => $error]);
                if ($notify) {
                    $this->NotifyExtensionInstallation($error);
                }
                $state['operation'] = ['kind' => $kind, 'status' => 'error', 'transaction' => ''];
                $state['lastError'] = $error;
                $this->SaveState($state);
                return [];
            }
            $id = bin2hex(random_bytes(16));
            $state['operation'] = ['kind' => $kind, 'status' => 'pending', 'transaction' => $id];
            $state['lastError'] = '';
            $state['workflow'] = ['id' => $id, 'kind' => $kind, 'checksum' => $checksum, 'yaml' => $yaml,
                'notify'               => $notify, 'previousSession'      => $state['extension']['session'] ?? null, 'target' => null, 'verifyRuntime' => false];
            if (in_array($kind, ['run_once', 'stop_rule'], true)) {
                $state['workflow']['executionRule'] = $executionRule;
                $state['workflow']['executionRun'] = $state['execution']['runs'][$executionRule]['run'] ?? '';
            }
            if ($kind === 'editor' && $importEvent !== null) {
                $state['workflow']['importEvent'] = $importEvent;
                $state['workflow']['preserveDraft'] = $preserveDraft;
            }
            if (in_array($kind, ['activate', 'deactivate'], true)) {
                $state['runtimeConfirmed'] = false;
            }
            if ($kind === 'install') {
                $state['extensionConfirmed'] = false;
                $state['validation'] = null;
            }
            if ($kind === 'validate') {
                $state['validation'] = null;
            }
            $frame = $kind === 'install'
                ? $this->ExtensionFrame($state, 'install', ['name' => ExtensionContract::NAME, 'code' => $code], $id)
                : $this->ExtensionFrame($state, 'status', [], $id);
            $this->SaveState($state);
            return $frame;
        });
        if ($frame === []) {
            return '';
        }
        if (isset($frame['existing'])) {
            return $frame['existing'];
        }
        return $this->DispatchExtensionFrame($frame) ? $frame['id'] : '';
    }

    private function ExtensionFrame(array &$state, string $wire, array $payload = [], ?string $transaction = null): array
    {
        $transaction ??= bin2hex(random_bytes(16));
        $state['workflow']['wire'] = $wire;
        $state['workflow']['transaction'] = $transaction;
        $state['workflow']['deadline'] = $this->Now() + $this->ReadPropertyInteger('RequestTimeout');
        if ($wire === 'store') {
            $state['runtimeConfirmed'] = false;
        }
        $payload = ['transaction' => $transaction] + $payload;
        if ($wire !== 'install') {
            $payload = ['protocol_version' => 1] + $payload;
        }
        return ['id'  => $state['workflow']['id'], 'transaction' => $transaction, 'scope' => $state['scope'],
            'topic'   => $this->ReadPropertyString('BaseTopic') . '/' . ($wire === 'install' ? 'bridge/request/extension/save' : ExtensionContract::PREFIX . 'request/' . $wire),
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    private function DispatchExtensionFrame(array $frame): bool
    {
        try {
            $send = $this->WithTransportLock(function () use ($frame): bool
            {
                $state = $this->RefreshState();
                $this->SaveState($state);
                return $state['scope'] === $frame['scope'] && $state['mqttConnected'] && ($state['bridgeOnline'] || ($state['workflow']['kind'] ?? '') === 'deactivate')
                    && ($state['workflow']['transaction'] ?? null) === $frame['transaction'];
            });
            if (!$send) {
                return false;
            }
            if (strlen($frame['payload']) > Z2MProtocol::MAX_PAYLOAD_BYTES) {
                throw new \RuntimeException('Request too large');
            }
            // Never hold the state semaphore while the parent dispatches replies.
            $this->DebugEvent('SENDEN', 'Erweiterungsanfrage an MQTT-Gateway übergeben; Bestätigung steht aus.', ['Gateway' => $this->GetParentID(), 'Topic' => $frame['topic'], 'Nutzdatenbytes' => strlen($frame['payload']), 'Anfragebezug' => substr(hash('sha256', $frame['transaction']), 0, 16)]);
            $this->SendDataToParent($this->EncodeDataFlowMessage(Z2MProtocol::MQTT_TX, [
                'PacketType' => 3, 'QualityOfService' => 0, 'Retain' => false,
                'Topic'      => $frame['topic'], 'Payload' => bin2hex($frame['payload'])
            ]));
            return true;
        } catch (Throwable $exception) {
            $this->WithTransportLock(function () use ($frame): void
            {
                $state = $this->RefreshState();
                if (($state['workflow']['transaction'] ?? null) === $frame['transaction']) {
                    $this->FinishExtensionWorkflow($state, 'error', 'send_failed');
                }
                $this->SaveState($state);
            });
            return false;
        }
    }

    private function FinishExtensionWorkflow(array &$state, string $status, string $error = ''): void
    {
        if (($state['workflow']['verifyStorage'] ?? false) && $status !== 'stored') {
            $status = 'stored_unconfirmed';
            $state['extensionConfirmed'] = false;
            $state['runtimeConfirmed'] = false;
            $this->DebugEvent('FEHLER', 'Speicherung wurde bestätigt, der anschließende Status jedoch nicht. Keine erneute Speicherung senden; Erweiterungsstatus abfragen.', ['Grund' => $error]);
        }
        if ($error !== '' && in_array($state['workflow']['kind'] ?? '', ['activate', 'deactivate'], true)) {
            $state['runtimeConfirmed'] = false;
        }
        $this->DebugEvent($error === '' ? 'AKTION' : 'FEHLER', 'Erweiterungsauftrag abgeschlossen.', ['Auftrag' => $state['workflow']['kind'] ?? 'none', 'Ergebnis' => $status, 'Grund' => $error]);
        if (($state['workflow']['kind'] ?? '') === 'install' && ($state['workflow']['notify'] ?? false)) {
            $this->NotifyExtensionInstallation($error);
        }
        $importEvent = $state['workflow']['importEvent'] ?? null;
        $state['operation']['status'] = $status;
        $state['lastError'] = $error;
        if (($state['workflow']['kind'] ?? '') === 'status' && ($state['validationDetails'] ?? null) !== null) {
            $this->DebugEvent('AKTION', 'Statusabfrage beendet; der detaillierte Prüfungsfehler bleibt bis zur nächsten Entwurfsprüfung erhalten.', ['Prüfungsfehler' => count($state['validationDetails']['issues'])]);
        }
        $state['workflow'] = null;
        if ($importEvent !== null) {
            $this->FinishImportDeviceLoad($importEvent, $status, $error, $state);
        }
    }

    private function NotifyExtensionInstallation(string $error): void
    {
        try {
            $message = $error === '' ? $this->Translate('Extension installed or updated successfully. The receiver in Z2M has confirmed readiness.')
                : $this->Translate('Extension installation or update failed:') . ' ' . $this->Translate(ExtensionContract::ErrorText($error));
            $this->UpdateFormField('InstallResultMessage', 'caption', $message);
            $this->UpdateFormField('InstallResultPopup', 'visible', false);
            $this->UpdateFormField('InstallResultPopup', 'visible', true);
            $this->DebugEvent($error === '' ? 'AKTION' : 'FEHLER', 'Installationsrückmeldung an das geöffnete Formular übergeben.', ['Erfolgreich' => $error === '', 'Grund' => $error]);
        } catch (Throwable) {
            $this->DebugEvent('FEHLER', 'Installationsrückmeldung konnte im Formular nicht angezeigt werden. Statusanzeige und Debug beachten.');
        }
    }

    private function ObserveExtensionStatus(array &$state, array $metadata): void
    {
        $prior = $state['extension'];
        $changedSession = $prior !== null && $prior['session'] !== $metadata['session'];
        if ($changedSession || ($prior['checksum'] ?? null) !== $metadata['checksum']) {
            $state['execution'] = null;
        }
        if ($prior !== $metadata) {
            $state['validation'] = null;
            $state['extensionConfirmed'] = false;
            $state['runtimeConfirmed'] = false;
        }
        $freshStopProbe = $state['workflow'] !== null && $state['workflow']['kind'] === 'deactivate'
            && $state['workflow']['wire'] === 'status' && !($state['workflow']['verifyRuntime'] ?? false);
        if (($changedSession || !$metadata['ready']) && $state['workflow'] !== null && $state['workflow']['kind'] !== 'install' && !$freshStopProbe) {
            $this->FinishExtensionWorkflow($state, 'cancelled', 'session_mismatch');
        }
        $state['extension'] = $metadata;
    }

    private function ReceiveExtensionMessage(array &$state, string $suffix, mixed $payload, bool $retained): ?array
    {
        if ($suffix === ExtensionContract::PREFIX . 'runs') {
            if (!$retained) {
                $this->AcceptExecutionSnapshot($state, $payload);
            }
            return null;
        }
        if ($suffix === ExtensionContract::PREFIX . 'status') {
            $metadata = ExtensionContract::Status($payload);
            if ($metadata !== null) {
                $this->ObserveExtensionStatus($state, $metadata);
            }
            return null;
        }
        $workflow = $state['workflow'];
        if ($workflow === null || $retained || !$payload instanceof stdClass || ($payload->transaction ?? null) !== $workflow['transaction']) {
            $this->DebugEvent('AKTION', 'Antwort ohne passenden laufenden Auftrag verworfen.', ['Retain' => $retained, 'AuftragVorhanden' => $workflow !== null]);
            return null;
        }
        $expected = $workflow['wire'] === 'install' ? 'bridge/response/extension/save' : ExtensionContract::PREFIX . 'response/' . $workflow['wire'];
        if ($suffix !== $expected) {
            $this->DebugEvent('AKTION', 'Antworttopic passt nicht zur erwarteten Auftragsphase.');
            return null;
        }
        $this->DebugEvent('AKTION', 'Antwort dem laufenden Auftrag zugeordnet.', ['Phase' => $workflow['wire'], 'Anfragebezug' => substr(hash('sha256', $workflow['transaction']), 0, 16)]);
        if ($workflow['wire'] !== 'install' && ($payload->protocol_version ?? null) !== 1) {
            $state['extensionConfirmed'] = false;
            $state['validation'] = null;
            $this->FinishExtensionWorkflow($state, 'error', 'protocol_mismatch');
            return null;
        }
        if (($payload->status ?? null) === 'error') {
            $code = $workflow['wire'] === 'install' ? 'installation_failed' : ($payload->error->code ?? 'extension_error');
            if ($workflow['wire'] !== 'install' && (!is_string($code) || !in_array($code, ExtensionContract::ERROR_CODES, true))) {
                $code = 'extension_error';
            }
            if (in_array($code, ['session_mismatch', 'revision_conflict', 'revision_reused', 'storage_unavailable'], true)) {
                $state['validation'] = null;
                $state['extensionConfirmed'] = false;
            }
            $this->FinishExtensionWorkflow($state, 'error', $code);
            if (in_array($workflow['wire'], ['validate', 'store'], true) && in_array($code, ['invalid_yaml', 'invalid_schema', 'invalid_semantics'], true)) {
                $details = ValidationFeedback::Normalize($payload->error->details ?? null);
                if ($details !== null) {
                    $state['validationDetails'] = $details + ['names' => ValidationFeedback::Names($workflow['yaml'])];
                    foreach ($details['issues'] as $issue) {
                        $this->DebugEvent('FEHLER', 'Z2M hat diese Stelle im Entwurf abgelehnt; Details stehen im Formular.', ['Position' => $issue['path'], 'Ursache' => $issue['reason'], 'Hinweis' => ValidationFeedback::Reason($issue, fn (string $text): string => $this->Translate($text))] + array_intersect_key($issue, array_flip(['limit', 'line', 'column'])));
                    }
                }
            }
            return null;
        }
        if (($payload->status ?? null) !== 'ok' || !($payload->data ?? null) instanceof stdClass) {
            $state['extensionConfirmed'] = false;
            $state['validation'] = null;
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return null;
        }
        if ($workflow['wire'] === 'install') {
            $state['extensionInstalled'] = true;
            return $this->ExtensionFrame($state, 'status');
        }
        if ($workflow['wire'] === 'status') {
            $metadata = ExtensionContract::Status($payload->data);
            if ($metadata === null || !$metadata['ready']) {
                $state['extensionConfirmed'] = false;
                $state['validation'] = null;
                $this->FinishExtensionWorkflow($state, 'error', $metadata === null ? 'protocol_mismatch' : 'not_ready');
                return null;
            }
            if ($workflow['kind'] === 'install' && $metadata['session'] === $workflow['previousSession']) {
                $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
                return null;
            }
            if ($workflow['verifyStorage'] ?? false) {
                $stored = $workflow['storedSnapshot'];
                $error = match (true) {
                    $metadata['session'] !== $stored['session'] => 'session_mismatch',
                    $metadata['storedRevision'] !== $stored['storedRevision'] || $metadata['checksum'] !== $stored['checksum']
                        || $metadata['automationCount'] !== $stored['automationCount'] => 'revision_conflict',
                    $metadata['storage'] !== 'ok'                                      => 'storage_unavailable',
                    $metadata['runtimeVersion'] !== 1                                  => 'runtime_unavailable',
                    $metadata['executing'] !== $stored['executing']                    => 'runtime_mismatch',
                    default                                                            => ''
                };
                if ($error !== '') {
                    $this->FinishExtensionWorkflow($state, 'error', $error);
                    return null;
                }
                $state['extension'] = $metadata;
                $state['extensionConfirmed'] = true;
                $state['runtimeConfirmed'] = true;
                $this->AcceptExecutionSnapshot($state, $payload->data->execution ?? null);
                $this->DebugEvent('AKTION', 'Gespeicherter Stand und Laufzeitstatus durch frische Z2M-Antwort bestätigt; Aktivierungsbutton wird freigegeben. Keine Automation wurde aktiviert.', ['Revision' => $metadata['storedRevision'], 'Ausführung' => $metadata['executing']]);
                $this->FinishExtensionWorkflow($state, 'stored');
                return null;
            }
            // A fresh, correlated status response authorizes only this snapshot.
            $receipt = $state['validation'];
            $state['extension'] = $metadata;
            $state['extensionConfirmed'] = true;
            $state['runtimeConfirmed'] = $metadata['runtimeVersion'] === 1;
            if (($state['execution']['session'] ?? null) !== $metadata['session'] || ($state['execution']['checksum'] ?? null) !== $metadata['checksum']) {
                $state['execution'] = null;
            }
            $this->AcceptExecutionSnapshot($state, $payload->data->execution ?? null);
            if (in_array($workflow['kind'], ['run_once', 'stop_rule'], true)) {
                $state['workflow']['target'] = $metadata;
                return $this->ExecutionCommand($state, $workflow, $payload->data);
            }
            if ($workflow['kind'] === 'runs') {
                $this->FinishExtensionWorkflow($state, 'runs_ready');
                return null;
            }
            if (in_array($workflow['kind'], ['activate', 'deactivate'], true)) {
                $state['bridgeState'] = 'online';
            }
            $state['workflow']['target'] = $metadata;
            if (in_array($workflow['kind'], ['activate', 'deactivate'], true)) {
                if ($metadata['runtimeVersion'] !== 1) {
                    $this->FinishExtensionWorkflow($state, 'error', 'runtime_unavailable');
                    return null;
                }
                $wantActive = $workflow['kind'] === 'activate';
                if ($workflow['verifyRuntime']) {
                    if (!$this->RuntimeMatches($metadata, $workflow['target'], $wantActive)) {
                        $this->FinishExtensionWorkflow($state, 'error', 'runtime_mismatch');
                    } else {
                        $this->FinishExtensionWorkflow($state, $wantActive ? 'activated' : 'stopped');
                    }
                    return null;
                }
                if ($wantActive && ($metadata['storage'] !== 'ok' || $metadata['storedRevision'] === 0)) {
                    $this->FinishExtensionWorkflow($state, 'error', 'storage_unavailable');
                    return null;
                }
                $state['runtimeConfirmed'] = false;
                $command = ['session' => $metadata['session']];
                if ($wantActive) {
                    $token = $payload->data->activation_token ?? null;
                    if (!is_string($token) || preg_match('/\A[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}\z/D', $token) !== 1) {
                        $this->FinishExtensionWorkflow($state, 'error', 'activation_expired');
                        return null;
                    }
                    $command += ['activation_token' => $token, 'expected_revision' => $metadata['storedRevision'], 'checksum' => $metadata['checksum']];
                    $command['resume_after_restart'] = $this->ReadPropertyBoolean('ResumeAfterRestart');
                    $this->DebugEvent('AKTION', 'Wiederanlauf-Einstellung mit ausdrücklicher Aktivierung an Z2M übertragen.', ['NachNeustartFortsetzen' => $command['resume_after_restart']]);
                }
                return $this->ExtensionFrame($state, $workflow['kind'], $command);
            }
            if ($workflow['kind'] === 'editor') {
                return $this->ExtensionFrame($state, 'editor');
            }
            if ($workflow['kind'] === 'status' || $workflow['kind'] === 'install') {
                if ($receipt !== null && !$this->ReceiptMatches($receipt, $metadata)) {
                    $state['validation'] = null;
                }
                $this->FinishExtensionWorkflow(
                    $state,
                    $workflow['kind'] === 'install' ? 'installed' : 'status_received',
                    in_array($metadata['storage'], ['ok', 'empty'], true) ? '' : 'storage_unavailable'
                );
                return null;
            }
            if ($workflow['kind'] === 'validate') {
                return $this->ExtensionFrame($state, 'validate', ['yaml' => $workflow['yaml']]);
            }
            if ($receipt === null || $receipt['checksum'] !== $workflow['checksum'] || !$this->ReceiptMatches($receipt, $metadata)) {
                $state['validation'] = null;
                $this->FinishExtensionWorkflow($state, 'error', $receipt !== null && $receipt['session'] !== $metadata['session'] ? 'session_mismatch' : 'revision_conflict');
                return null;
            }
            if (!in_array($metadata['storage'], ['ok', 'empty'], true)) {
                $this->FinishExtensionWorkflow($state, 'error', 'storage_unavailable');
                return null;
            }
            $idempotent = $receipt['revision'] === $metadata['storedRevision'] && $receipt['checksum'] === $metadata['checksum'];
            if (!$idempotent && $receipt['revision'] !== $metadata['storedRevision'] + 1) {
                $this->FinishExtensionWorkflow($state, 'error', 'revision_reused');
                return null;
            }
            return $this->ExtensionFrame($state, 'store', ['yaml' => $workflow['yaml'], 'session' => $metadata['session'], 'expected_revision' => $metadata['storedRevision']]);
        }
        if (in_array($workflow['wire'], ['run_once', 'stop_rule'], true)) {
            $receipt = ExecutionContract::Snapshot($payload->data);
            $run = $receipt['runs'][$workflow['executionRule']] ?? null;
            if ($receipt === null || $receipt['session'] !== $workflow['target']['session'] || $receipt['revision'] !== $workflow['target']['storedRevision']
                || $receipt['checksum'] !== $workflow['target']['checksum'] || $run === null
                || ($workflow['wire'] === 'stop_rule' && ($run['run'] !== $workflow['executionRun'] || $run['state'] !== 'stopped'))) {
                $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
                return null;
            }
            // A newer asynchronous state may already have arrived; never roll it back to this receipt.
            $this->AcceptExecutionSnapshot($state, $payload->data);
            $this->FinishExtensionWorkflow($state, $workflow['wire'] === 'run_once' ? 'run_started' : 'run_stopped');
            return null;
        }
        if (in_array($workflow['wire'], ['activate', 'deactivate'], true)) {
            $metadata = ExtensionContract::Status($payload->data);
            if ($metadata === null || !$this->RuntimeMatches($metadata, $workflow['target'], $workflow['wire'] === 'activate')) {
                $this->FinishExtensionWorkflow($state, 'error', 'runtime_mismatch');
                return null;
            }
            $state['extension'] = $metadata;
            $state['runtimeConfirmed'] = false;
            $state['workflow']['verifyRuntime'] = true;
            return $this->ExtensionFrame($state, 'status');
        }
        if ($workflow['wire'] === 'editor') {
            $this->ReceiveEditorData($state, $payload->data, $workflow['target']);
            return null;
        }
        if ($workflow['wire'] === 'validate') {
            $data = $payload->data;
            $target = $workflow['target'];
            if (($data->valid ?? null) !== true || !ExtensionContract::Revision($data->revision ?? null) || $data->revision < 1
                || ($data->checksum ?? null) !== $workflow['checksum'] || !is_int($data->automation_count ?? null)
                || $data->automation_count < 0 || $data->automation_count > 100 || ($data->executing ?? null) !== false
                || $target !== $state['extension']) {
                $state['validation'] = null;
                $state['extensionConfirmed'] = false;
                $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
                return null;
            }
            $state['validation'] = ['checksum' => $workflow['checksum'], 'revision' => $data->revision,
                'automationCount'              => $data->automation_count, 'session' => $target['session'],
                'expectedRevision'             => $target['storedRevision'], 'previousChecksum' => $target['checksum']];
            $this->FinishExtensionWorkflow($state, 'validated');
            return null;
        }
        $metadata = ExtensionContract::Storage($payload->data);
        $receipt = $state['validation'];
        if ($metadata === null || $receipt === null || $metadata['storedRevision'] !== $receipt['revision']
            || $metadata['checksum'] !== $workflow['checksum'] || $metadata['automationCount'] !== $receipt['automationCount']
            || $metadata['storage'] !== 'ok' || !is_bool($payload->data->unchanged ?? null)) {
            $state['extensionConfirmed'] = false;
            $state['validation'] = null;
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return null;
        }
        $state['extension'] = array_replace($workflow['target'], $metadata);
        if (!$metadata['executing']) {
            $state['extension']['activeRevision'] = null;
            $state['extension']['activeChecksum'] = null;
            $state['extension']['enabledCount'] = 0;
        }
        $state['extensionConfirmed'] = false;
        $state['runtimeConfirmed'] = false;
        $state['validation'] = null;
        $state['workflow']['verifyStorage'] = true;
        $state['workflow']['storedSnapshot'] = $metadata + ['session' => $workflow['target']['session']];
        $this->DebugEvent('AKTION', 'Z2M hat die Speicherung bestätigt; vollständigen Laufzeitstatus automatisch nachfragen. Aktivierungsbutton bleibt bis zur passenden Antwort gesperrt.', ['Revision' => $metadata['storedRevision'], 'Unverändert' => $payload->data->unchanged]);
        if ($this->RuntimeDebugLease()['active'] ?? false) {
            $this->DebugEvent('AKTION', 'Speicherstatus wird bestätigt; laufende Ablaufaufzeichnung bleibt mit ihrer bisherigen Endzeit erhalten.');
        }
        return $this->ExtensionFrame($state, 'status');
    }

    private function ReceiptMatches(array $receipt, array $metadata): bool
    {
        return $receipt['session'] === $metadata['session'] && $receipt['expectedRevision'] === $metadata['storedRevision']
            && $receipt['previousChecksum'] === $metadata['checksum'];
    }

    private function RuntimeMatches(array $metadata, array $target, bool $active): bool
    {
        return $metadata['ready'] && $metadata['runtimeVersion'] === 1 && $metadata['session'] === $target['session']
            && $metadata['executing'] === $active && (!$active || ($metadata['activeRevision'] === $target['storedRevision']
                && $metadata['activeChecksum'] === $target['checksum']));
    }

    private function RefreshExtensionWorkflow(array $state): array
    {
        $state += $this->NewExtensionState();
        if ($state['workflow'] !== null && $state['workflow']['deadline'] <= $this->Now()) {
            $this->FinishExtensionWorkflow($state, 'timeout', 'timeout');
            $state['extensionConfirmed'] = false;
        }
        if (!$state['mqttConnected'] || !$state['bridgeOnline'] || !$state['extensionConfirmed']) {
            $state['runtimeConfirmed'] = false;
        }
        return $state;
    }

    private function SaveExtensionState(array $state): void
    {
        $this->UpdateExtensionBuildInfo($state);
        $ready = $state['extensionConfirmed'] && ($state['extension']['ready'] ?? false);
        $this->SetValue('ExtensionReady', $ready);
        $this->SetValue('StoredRevision', $state['extension']['storedRevision'] ?? 0);
        $confirmed = $state['runtimeConfirmed'] && $state['extensionConfirmed'];
        $this->SetValue('MeshReady', $confirmed && ($state['extension']['meshReady'] ?? false));
        $executing = $confirmed && ($state['extension']['executing'] ?? false);
        $this->DebugFlowWriteValue('ExecutionActive', $executing, __FUNCTION__, __LINE__);
        $this->DebugFlowWriteValue('ActiveRevision', $executing ? $state['extension']['activeRevision'] : 0, __FUNCTION__, __LINE__);
        $this->DebugFlowWriteValue('EnabledAutomations', $executing ? $state['extension']['enabledCount'] : 0, __FUNCTION__, __LINE__);
        $runtime = $this->ExecutionText($state);
        $this->DebugFlowWriteValue('ExecutionStatus', $runtime, __FUNCTION__, __LINE__);
        $runtimeError = $this->Translate(ExtensionContract::ErrorText($state['extension']['runtimeError'] ?? ''));
        $this->DebugFlowWriteValue('RuntimeError', $runtimeError, __FUNCTION__, __LINE__);
        $this->UpdateFormField('RuntimeErrorInfo', 'caption', $runtimeError);
        $this->UpdateFormField('ExecutionInfo', 'caption', $runtime);
        $restart = $this->RestartText($state);
        $this->UpdateFormField('RestartInfo', 'caption', $restart);
        if ($this->GetBuffer('RestartStatusText') !== $restart) {
            $this->SetBuffer('RestartStatusText', $restart);
            $blocked = $state['extensionConfirmed'] && ($state['extension']['resumeStatus'] ?? '') === 'blocked';
            $this->DebugEvent(
                $blocked ? 'FEHLER' : 'AKTION',
                $blocked
                ? 'Z2M meldet einen Fehler beim Wiederanlauf oder beim dauerhaften Speichern des Betriebszustands; Statusanzeige und Speicher prüfen.'
                : 'Anzeige des von Z2M bestätigten Wiederanlaufverhaltens aktualisiert.',
                ['Bestätigt'      => $state['extensionConfirmed'], 'Zustand' => $state['extension']['resumeStatus'] ?? 'unknown',
                    'Freigegeben' => $state['extension']['resumeEnabled'] ?? false, 'Fehlercode' => $state['extension']['resumeError'] ?? '']
            );
        }
        $this->UpdateFormField('ActivateButton', 'enabled', $confirmed && ($state['extension']['storedRevision'] ?? 0) > 0 && $state['workflow'] === null);
        $this->UpdateFormField('StopButton', 'enabled', $state['mqttConnected']);
        $status = $this->OperationText($state);
        $error = $this->ExtensionErrorText($state);
        $this->SetValue('OperationStatus', $status);
        $this->SetValue('LastError', $error);
        $this->UpdateFormField('OperationInfo', 'caption', $status);
        $this->UpdateFormField('ErrorInfo', 'caption', $error);
        $this->UpdateFormField('ValidationInfo', 'caption', $this->ValidationPreview($state));
    }

    private function ExtensionBuildInfo(array $state): array
    {
        $bundled = ExtensionContract::BundledBuild();
        $confirmed = $state['mqttConnected'] && $state['extensionConfirmed'] && ($state['extension']['ready'] ?? false);
        $installed = $confirmed ? ($state['extension']['build'] ?? null) : null;
        $comparison = $bundled === null ? 'missing' : (!$confirmed ? 'unconfirmed' : ($installed === null ? 'legacy' : ($installed === $bundled ? 'current' : 'different')));
        $installedText = $confirmed
            ? sprintf($this->Translate('Installed extension: %s'), $state['extension']['version'] . ' (' . ($installed === null ? $this->Translate('without build identifier') : 'Build ' . substr($installed, 0, 12)) . ')')
            : $this->Translate('Installed extension: Not yet confirmed.');
        $bundledText = $bundled === null ? $this->Translate('Bundled extension: Build identifier unavailable. Check the module files.')
            : sprintf($this->Translate('Bundled extension: %s'), ExtensionContract::VERSION . ' (Build ' . substr($bundled, 0, 12) . ')');
        $hint = match ($comparison) {
            'current'   => 'Up to date. The installed and bundled extensions have the same build.',
            'different' => 'Update available: The installed build differs from the bundled build. Choose Install or update bundled extension to use the bundled version.',
            'legacy'    => 'The installed extension cannot be compared because it reports no build identifier. Update the bundled extension, then request its status again.',
            'missing'   => 'Build comparison unavailable. Update the module files and reopen this form.',
            default     => 'Not yet checked. Request the extension status to compare builds.'
        };
        return ['comparison' => $comparison, 'installed' => $installed, 'bundled' => $bundled, 'confirmed' => $confirmed,
            'fields'         => ['InstalledExtensionInfo' => $installedText, 'BundledExtensionInfo' => $bundledText, 'ExtensionUpdateInfo' => $this->Translate($hint)]];
    }

    private function UpdateExtensionBuildInfo(array $state): void
    {
        $info = $this->ExtensionBuildInfo($state);
        $key = json_encode($info, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if ($this->GetBuffer('ExtensionBuildDisplay') === $key) {
            return;
        }
        $this->SetBuffer('ExtensionBuildDisplay', $key);
        foreach ($info['fields'] as $field => $text) {
            $this->UpdateFormField($field, 'caption', $text);
        }
        $message = match ($info['comparison']) {
            'current'   => 'Installierte und mitgelieferte Erweiterung haben denselben Buildstand; kein Update erforderlich.',
            'different' => 'Installierte Erweiterung weicht vom mitgelieferten Buildstand ab. Zum Angleichen die mitgelieferte Erweiterung installieren oder aktualisieren.',
            'legacy'    => 'Z2M-Erweiterung meldet keine Buildkennung; Vergleich nicht möglich. Mitgelieferte Erweiterung aktualisieren und Status erneut abfragen.',
            'missing'   => 'Buildkennung der mitgelieferten Erweiterung nicht lesbar; Moduldateien prüfen.',
            default     => 'Erweiterungsstand noch nicht bestätigt; Updatebedarf bleibt offen. Erweiterungsstatus abfragen.'
        };
        $this->DebugEvent(
            $info['comparison'] === 'missing' ? 'FEHLER' : 'AKTION',
            $message,
            ['Vergleich'             => $info['comparison'], 'Bestätigt' => $info['confirmed'],
                'InstallierterBuild' => substr($info['installed'] ?? '', 0, 12), 'MitgelieferterBuild' => substr($info['bundled'] ?? '', 0, 12)]
        );
    }

    private function ExtensionErrorText(array $state): string
    {
        $error = $this->Translate(ExtensionContract::ErrorText($state['lastError']));
        if (($state['validationDetails'] ?? null) !== null) {
            $details = ValidationFeedback::Text($state['validationDetails'], fn (string $text): string => $this->Translate($text));
            return $details . ($error !== '' && !in_array($state['lastError'], ['invalid_yaml', 'invalid_schema', 'invalid_semantics'], true)
                ? "\n\n" . $this->Translate('Additional operation message:') . ' ' . $error : '');
        }
        return $error;
    }

    private function RestartText(array $state): string
    {
        $status = $state['extensionConfirmed'] ? ($state['extension']['resumeStatus'] ?? '') : '';
        if ($status === 'blocked' && ($state['extension']['resumeError'] ?? '') === 'storage_error') {
            return $this->Translate('The operating state could not be saved permanently. After restarting Z2M, the previously saved state may apply. Resolve the storage error and confirm stop or activation again.');
        }
        $text = match ($status) {
            'armed'        => 'Automatic restart is enabled for the activated rules.',
            'resumed'      => 'Automations were automatically activated after restart.',
            'stopped'      => 'Automations were stopped and will remain stopped after restart.',
            'disabled'     => 'Automatic restart is disabled. Activate manually after restarting Z2M.',
            'unconfigured' => 'No restart approval is stored in Z2M. Activate once to transfer this setting.',
            'blocked'      => 'Automatic restart failed. Automations remain stopped.',
            default        => 'Restart behavior has not yet been confirmed by Z2M.'
        };
        $error = $status === 'blocked' ? $this->Translate(ExtensionContract::ErrorText($state['extension']['resumeError'] ?? '')) : '';
        return $this->Translate($text) . ($error === '' ? '' : ' ' . $error);
    }

    private function ExecutionText(array $state): string
    {
        if ($state['extensionConfirmed'] && ($state['extension']['runtimeVersion'] ?? 0) !== 1) {
            return $this->Translate('Update the bundled extension to use execution controls.');
        }
        if (!$state['runtimeConfirmed'] || !$state['extensionConfirmed']) {
            return $this->Translate('Execution state unknown. Refresh extension status.');
        }
        return !$state['extension']['executing'] ? $this->Translate('Automations are stopped in Z2M.')
            : sprintf(
                $this->Translate('Active revision %d with %d enabled automations. Z2M runs independently of Symcon.'),
                $state['extension']['activeRevision'],
                $state['extension']['enabledCount']
            );
    }

    private function OperationText(array $state): string
    {
        if ($state['workflow']['verifyStorage'] ?? false) {
            return $this->Translate('Automations are stored in Z2M. Automatically confirming the runtime status; please wait.');
        }
        return $this->Translate(match ($state['operation']['status']) {
            'installed'          => 'Extension installed and receiver confirmed.',
            'validated'          => 'YAML validated. Review the preview before storing.',
            'stored'             => 'YAML stored. Activation is a separate step.',
            'stored_unconfirmed' => 'Z2M confirmed storage, but its current runtime status could not be confirmed. Request the extension status; do not store again just for this message.',
            'status_received'    => 'Receiver status confirmed.',
            'activated'          => 'Stored revision activated and confirmed by Z2M.',
            'stopped'            => 'Execution stopped and confirmed by Z2M.',
            'sequence_created'   => 'Action sequence added to the draft. Validate and store the draft next.',
            'sequence_updated'   => 'Action sequence updated. All referencing automations use the changes after storage in Z2M.',
            'sequence_deleted'   => 'Action sequence removed from the draft. Z2M is unchanged until storing.',
            'run_started'        => 'Single run accepted by Z2M. See the selected automation for its result.',
            'run_stopped'        => 'The selected run was stopped. Other automations are unchanged.',
            'runs_ready'         => 'Individual run status refreshed.',
            'editor_loaded'      => 'Devices and stored automations loaded. Choose source and target.',
            'draft_created'      => 'Automation added to the draft. Validate, store and activate it next.',
            'rule_selected'      => 'Automation selected. Changes affect the local draft only.',
            'draft_copied'       => 'A disabled copy was added to the draft and opened for editing. Z2M is unchanged.',
            'draft_updated'      => 'Automation updated in the draft. Validate and store it next.',
            'draft_deleted'      => 'Automation removed from the draft. Validate and store it next.',
            default              => ucfirst($state['operation']['status'])
        });
    }

    private function ValidationPreview(array $state): string
    {
        if ($state['workflow']['verifyStorage'] ?? false) {
            return $this->Translate('The draft has been stored. Its runtime status is being confirmed automatically.');
        }
        $receipt = $state['validation'];
        if ($receipt === null && ($state['extension']['storage'] ?? '') === 'ok'
            && ($state['extension']['storedRevision'] ?? 0) > 0 && ($state['extension']['checksum'] ?? null) === hash('sha256', $this->ReadAttributeString('YamlDraft'))) {
            return sprintf($this->Translate('This draft is already stored in Z2M (revision %d). Further changes must be validated and stored again.'), $state['extension']['storedRevision']);
        }
        return $receipt === null ? $this->Translate('No current YAML validation.')
            : sprintf(
                $this->Translate('Validated revision %d, %d automations. Stored revision: %d. SHA-256: %s'),
                $receipt['revision'],
                $receipt['automationCount'],
                $receipt['expectedRevision'],
                $receipt['checksum']
            );
    }
}
