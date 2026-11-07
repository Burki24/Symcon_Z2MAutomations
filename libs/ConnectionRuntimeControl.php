<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use stdClass;
use Throwable;

require_once __DIR__ . '/RuntimeControlProtocol.php';

/** Exclusive, journal-authorized runtime control over the existing MQTT transport. */
trait ConnectionRuntimeControl
{
    public function RequestRuntimeControl(int $Owner, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID): string
        {
            try {
                $authority = $this->RuntimeAuthority($Owner, $OperationID);
                $request = $authority['request'];
                $frame = $this->WithTransportLock(function () use ($request): array
                {
                    $entries = $this->ReadRuntimeControls();
                    if (isset($entries[$request['operation']])) return [];
                    $state = $this->RefreshState();
                    if (!$state['mqttConnected']) throw new RuntimeException('not_connected');
                    if ($state['workflow'] !== null) {
                        if ($request['kind'] !== 'deactivate' && !($request['kind'] === 'stop_rule' && $state['workflow']['kind'] !== 'deactivate')) throw new RuntimeException('busy');
                        $this->FinishExtensionWorkflow($state, 'cancelled', 'cancelled');
                        $this->DebugEvent('AKTION', 'Stopp hat für dieses System Vorrang. Der bisherige Auftrag wird nicht erneut gesendet.');
                        $entries = $this->ReadRuntimeControls();
                    }
                    $entries[$request['operation']] = ['request' => $request, 'scope' => $state['scope'], 'status' => 'pending', 'error' => '', 'mayHaveControlled' => false];
                    $this->WriteRuntimeControls($entries);
                    $state['workflow'] = ['id' => $request['operation'], 'kind' => $request['kind'], 'runtimeRequest' => $request,
                        'target'               => null, 'verifyRuntime' => false];
                    $state['operation'] = ['kind' => $request['kind'], 'status' => 'pending', 'transaction' => $request['operation']];
                    $state['lastError'] = '';
                    $frame = $this->ExtensionFrame($state, 'status');
                    $this->SaveState($state);
                    $this->DebugEvent('SENDEN', 'Betriebsauftrag für genau dieses Z2M-System vorbereitet. Gespeicherte Regeln und andere Systeme bleiben erhalten.', ['Systembezug' => $request['binding']['systemId'], 'Auftrag' => $request['kind']]);
                    return $frame;
                });
                if ($frame !== []) $this->DispatchExtensionFrame($frame);
                return $this->GetRuntimeControlResult($Owner, $OperationID);
            } catch (Throwable $exception) {
                $error = $exception instanceof RuntimeException && in_array($exception->getMessage(), ['not_connected', 'busy', 'ownership_required', 'runtime_draft'], true) ? $exception->getMessage() : 'control_unavailable';
                $this->DebugEvent('FEHLER', 'Betriebsauftrag nicht gestartet. Bitte Systemstatus prüfen; kein automatischer neuer Versuch.', ['Ursache' => $error]);
                return json_encode(['ok' => false, 'error' => $error], JSON_THROW_ON_ERROR);
            }
        });
    }

    public function GetRuntimeControlResult(int $Owner, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID): string
        {
            try {
                return $this->WithTransportLock(function () use ($Owner, $OperationID): string
                {
                    $state = $this->RefreshState();
                    $entries = $this->ReadRuntimeControls();
                    $entry = $entries[$OperationID] ?? null;
                    if ($entry === null || $entry['request']['owner'] !== $Owner) return '{"ok":false,"error":"runtime_missing"}';
                    if ($entry['status'] === 'pending' && (($state['workflow']['id'] ?? '') !== $OperationID || $state['scope'] !== $entry['scope'])) {
                        $entry['status'] = 'interrupted';
                        $entry['error'] = 'runtime_interrupted';
                        $entries[$OperationID] = $entry;
                        $this->WriteRuntimeControls($entries);
                    }
                    $this->SaveState($state);
                    return json_encode(['ok' => true, 'operation' => $OperationID, 'kind' => $entry['request']['kind'],
                        'status'             => $entry['status'], 'error' => $entry['error'], 'mayHaveControlled' => $entry['mayHaveControlled']], JSON_THROW_ON_ERROR);
                });
            } catch (Throwable) {
                return '{"ok":false,"error":"control_unavailable"}';
            }
        });
    }

    private function RuntimeAuthority(int $owner, string $operation): array
    {
        if (!IPS_InstanceExists($owner) || IPS_GetInstance($owner)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE || !DraftTransferProtocol::Operation($operation)) throw new RuntimeException('ownership_required');
        $a = json_decode(Z2MA_GetWorkspaceRuntimeAuthority($owner, $operation), true, 24, JSON_THROW_ON_ERROR);
        $r = $a['request'] ?? null;
        $m = $this->OwnershipMarker();
        if (($a['ok'] ?? false) !== true || !RuntimeControlProtocol::RequestValid($r) || $r['owner'] !== $owner || $r['operation'] !== $operation
            || $r['binding']['connectionInstance'] !== $this->InstanceID || $r['binding']['mqttParent'] !== $this->GetParentID()
            || $r['binding']['systemId'] !== $this->ReadPropertyString('SystemID') || $r['binding']['generation'] !== $this->ReadPropertyInteger('Generation')
            || $r['binding']['baseTopic'] !== $this->ReadPropertyString('BaseTopic') || ($m['role'] ?? '') !== 'connection'
            || $m['owner'] !== $owner || $m['operation'] !== $r['epoch'] || $m['systemId'] !== $r['binding']['systemId'] || $m['generation'] !== $r['binding']['generation']) throw new RuntimeException('ownership_required');
        if (in_array($r['kind'], ['activate', 'run_once'], true) && ($a['documentCurrent'] ?? false) !== true) throw new RuntimeException('runtime_draft');
        return $a;
    }

    private function RuntimeFrameAllowed(array &$state): bool
    {
        $r = $state['workflow']['runtimeRequest'] ?? null;
        if ($r === null) return true;
        try {
            if ($this->RuntimeAuthority($r['owner'], $r['operation'])['request'] !== $r) throw new RuntimeException();
        } catch (Throwable) {
            $this->FinishExtensionWorkflow($state, 'cancelled', 'cancelled');
            $this->DebugEvent('FEHLER', 'Betriebsauftrag vor dem nächsten MQTT-Schritt gesperrt: Besitzer, Ziel oder Startfreigabe geändert.');
            return false;
        }
        if ($state['workflow']['wire'] !== 'status') {
            $entries = $this->ReadRuntimeControls();
            $entries[$r['operation']]['mayHaveControlled'] = true;
            $this->WriteRuntimeControls($entries);
        }
        return true;
    }

    private function ReceiveRuntimeControl(array &$state, stdClass $payload): ?array
    {
        $w = $state['workflow'];
        $r = $w['runtimeRequest'];
        if (($payload->status ?? '') !== 'ok' || !($payload->data ?? null) instanceof stdClass) {
            $error = $payload->error->code ?? 'invalid_response';
            $this->FinishExtensionWorkflow($state, 'error', is_string($error) && in_array($error, ExtensionContract::ERROR_CODES, true) ? $error : 'invalid_response');
            return null;
        }
        $data = $payload->data;
        if ($w['wire'] === 'status') {
            $m = ExtensionContract::Status($data);
            if ($m === null || !$m['ready'] || $m['runtimeVersion'] !== 1) {
                $this->FinishExtensionWorkflow($state, 'error', 'runtime_unavailable');
                return null;
            }
            $state['extension'] = $m;
            $state['extensionConfirmed'] = $state['runtimeConfirmed'] = true;
            $state['bridgeState'] = 'online';
            $this->AcceptConnectionRuns($state, $data->execution ?? null);
            if ($r['kind'] === 'status') {
                $this->FinishExtensionWorkflow($state, 'status_received');
                return null;
            }
            if ($w['verifyRuntime']) {
                $ok = RuntimeControlProtocol::RuntimeMatches($m, $w['target'], $r['kind'] === 'activate')
                    && ($r['kind'] === 'activate' ? $m['resumeEnabled'] === $r['resume'] : ($m['resumeStatus'] ?? '') === 'stopped');
                $this->FinishExtensionWorkflow($state, $ok ? ($r['kind'] === 'activate' ? 'activated' : 'stopped') : 'error', $ok ? '' : 'runtime_mismatch');
                return null;
            }
            if ($r['kind'] !== 'deactivate' && !RuntimeControlProtocol::SameStored($m, $r)) {
                $this->FinishExtensionWorkflow($state, 'error', 'runtime_changed');
                return null;
            }
            $state['workflow']['target'] = $m;
            $command = ['session' => $m['session']];
            if (in_array($r['kind'], ['activate', 'run_once'], true)) {
                if ($m['storage'] !== 'ok' || $m['storedRevision'] === 0 || !RuntimeDebugContract::UUID($data->activation_token ?? null)) {
                    $this->FinishExtensionWorkflow($state, 'error', 'activation_expired');
                    return null;
                }
                $command += ['activation_token' => $data->activation_token, 'expected_revision' => $m['storedRevision'], 'checksum' => $m['checksum']];
            }
            if (in_array($r['kind'], ['run_once', 'stop_rule'], true)) {
                if (($m['executionVersion'] ?? 0) !== 1 || !$this->ConnectionRunsCurrent($state)) {
                    $this->FinishExtensionWorkflow($state, 'error', 'execution_unavailable');
                    return null;
                }
                $command += ['rule_id' => $r['rule'], 'expected_revision' => $m['storedRevision'], 'checksum' => $m['checksum']];
                if ($r['kind'] === 'stop_rule') {
                    $run = $state['execution']['runs'][$r['rule']] ?? null;
                    if (($run['state'] ?? '') !== 'running' || $run['run'] !== $r['run']) {
                        $this->FinishExtensionWorkflow($state, 'error', 'run_conflict');
                        return null;
                    }
                    $command['run'] = $r['run'];
                }
            }
            if ($r['kind'] === 'activate') $command['resume_after_restart'] = $r['resume'];
            $this->DebugEvent('SENDEN', 'Frischer Z2M-Status bestätigt. Genau den ausgewählten Betriebsauftrag senden; andere Systeme bleiben unverändert.', ['Systembezug' => $r['binding']['systemId'], 'Auftrag' => $r['kind'], 'Regelbezug' => $r['rule'] === '' ? '' : substr(hash('sha256', $r['rule']), 0, 16)]);
            if (in_array($r['kind'], ['activate', 'deactivate'], true)) $state['runtimeConfirmed'] = false;
            return $this->ExtensionFrame($state, $r['kind'], $command);
        }
        if (in_array($w['wire'], ['activate', 'deactivate'], true)) {
            $m = ExtensionContract::Status($data);
            if ($m === null || !RuntimeControlProtocol::RuntimeMatches($m, $w['target'], $r['kind'] === 'activate')) {
                $this->FinishExtensionWorkflow($state, 'error', 'runtime_mismatch');
                return null;
            }
            $state['workflow']['verifyRuntime'] = true;
            return $this->ExtensionFrame($state, 'status');
        }
        $snapshot = ExecutionContract::Snapshot($data);
        $run = $snapshot['runs'][$r['rule']] ?? null;
        if ($snapshot === null || $snapshot['session'] !== $w['target']['session'] || $snapshot['revision'] !== $r['revision'] || $snapshot['checksum'] !== $r['checksum']
            || $run === null || ($r['kind'] === 'stop_rule' && ($run['run'] !== $r['run'] || $run['state'] !== 'stopped'))) {
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return null;
        }
        $this->AcceptConnectionRuns($state, $data);
        $this->FinishExtensionWorkflow($state, $r['kind'] === 'run_once' ? 'run_started' : 'run_stopped');
        return null;
    }

    private function ConnectionRunsCurrent(array $state): bool
    {
        return $state['extensionConfirmed'] && ($state['execution']['session'] ?? '') === ($state['extension']['session'] ?? null)
            && ($state['execution']['revision'] ?? null) === ($state['extension']['storedRevision'] ?? 0)
            && ($state['execution']['checksum'] ?? '') === ($state['extension']['checksum'] ?? null);
    }

    private function AcceptConnectionRuns(array &$state, mixed $data): void
    {
        $s = ExecutionContract::Snapshot($data);
        $m = $state['extension'];
        if ($s === null || !$state['extensionConfirmed'] || $s['session'] !== ($m['session'] ?? '') || $s['revision'] !== $m['storedRevision'] || $s['checksum'] !== $m['checksum']) return;
        $old = $state['execution'];
        if ($this->ConnectionRunsCurrent($state) && $old['sequence'] >= $s['sequence']) return;
        $state['execution'] = $s;
        $this->DebugEvent('EMPFANG', 'Bestätigten Laufstatus dieses Z2M-Systems übernommen. Einzellauf und Automatikbetrieb bleiben getrennt.', ['Systembezug' => $this->ReadPropertyString('SystemID'), 'Folgenummer' => $s['sequence'], 'Läufe' => count($s['runs'])]);
    }

    private function FinishRuntimeControl(array $state, string $status, string $error): void
    {
        $r = $state['workflow']['runtimeRequest'] ?? null;
        if ($r === null) return;
        $entries = $this->ReadRuntimeControls();
        if (!isset($entries[$r['operation']])) return;
        $entries[$r['operation']]['status'] = $status;
        $entries[$r['operation']]['error'] = $error;
        $this->WriteRuntimeControls($entries);
        $this->DebugEvent($error === '' ? 'AKTION' : 'FEHLER', 'Betriebsauftrag abgeschlossen und genau diesem System zugeordnet. Kein automatischer Wiederholungsversuch.', ['Systembezug' => $r['binding']['systemId'], 'Ergebnis' => $status, 'Ursache' => $error]);
    }

    private function ReadRuntimeControls(): array
    {
        $raw = $this->ReadAttributeString('ConnectionRuntimeControls');
        $entries = $raw === '' ? [] : json_decode($raw, true, 24, JSON_THROW_ON_ERROR);
        if (!is_array($entries) || count($entries) > 32 || strlen($raw) > 262144) throw new RuntimeException('control_unavailable');
        foreach ($entries as $key => $entry) if (!RuntimeControlProtocol::RequestValid($entry['request'] ?? null) || $entry['request']['operation'] !== $key
            || !in_array($entry['status'] ?? null, RuntimeControlProtocol::STATES, true) || !is_string($entry['error'] ?? null)
            || !is_bool($entry['mayHaveControlled'] ?? null) || !is_string($entry['scope'] ?? null)) throw new RuntimeException('control_unavailable');
        return $entries;
    }

    private function WriteRuntimeControls(array $entries): void
    {
        $raw = json_encode(array_slice($entries, -32, null, true), JSON_THROW_ON_ERROR);
        if (strlen($raw) > 262144) throw new RuntimeException('control_unavailable');
        $this->WriteAttributeString('ConnectionRuntimeControls', $raw);
    }
}
