<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/RuntimeControlProtocol.php';

/** Per-system explicit runtime requests; document storage is never part of control. */
trait WorkspaceRuntime
{
    public function RequestWorkspaceRuntime(string $SystemID, int $Generation, string $Fingerprint, string $Action, string $RuleID, string $RunID, bool $ResumeAfterRestart, string $OperationID, string $RuntimeContext): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $Action, $RuleID, $RunID, $ResumeAfterRestart, $OperationID, $RuntimeContext): string
        {
            try {
                if (!in_array($Action, RuntimeControlProtocol::KINDS, true) || !ExtensionContract::Checksum($RuntimeContext)) throw new RuntimeException('invalid_request');
                $op = $this->WorkspaceOperation($OperationID, [$SystemID, $Generation, $Fingerprint, $Action, $RuleID, $RunID, $ResumeAfterRestart, $RuntimeContext]);
                $journal = $this->ReadWorkspaceRuntime();
                if (isset($journal['receipts'][$OperationID])) {
                    if ($journal['receipts'][$OperationID] !== $op['request']) throw new RuntimeException('operation_conflict');
                    $this->DebugEvent('AKTION', 'Bereits angenommenen Betriebsauftrag nur gelesen. Kein zweiter Start und kein erneuter Stopp.', ['Systembezug' => $SystemID]);
                    return $this->GetWorkspaceRuntimeStatus($SystemID);
                }
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                $info = $this->WorkspaceRuntimeAccess($record);
                $view = $this->WorkspaceRuntimeView($record);
                if ($view['context'] !== $RuntimeContext) throw new RuntimeException('runtime_changed');
                $rule = $view['rules'][$RuleID] ?? null;
                $allowed = match ($Action) {
                    'status'   => $view['canRefresh'], 'activate' => $view['canActivate'], 'deactivate' => $view['canStop'],
                    'run_once' => ($rule['canRun'] ?? false), 'stop_rule' => ($rule['canStop'] ?? false) && $RunID === $rule['run']
                };
                if (!$allowed) throw new RuntimeException(in_array($Action, ['run_once', 'stop_rule'], true) ? 'runtime_selection' : ($view['blocked'] ?: 'runtime_draft'));
                if (in_array($Action, ['activate', 'run_once'], true)) $this->CheckWorkspaceTransferActions($record);
                $m = $info['state']['extension'];
                $request = ['owner' => $this->InstanceID, 'operation' => $OperationID, 'epoch' => $record['ownershipEpoch'], 'binding' => $record['binding'],
                    'kind'          => $Action, 'rule' => $RuleID, 'run' => $RunID, 'resume' => $ResumeAfterRestart,
                    'session'       => $m['session'] ?? '', 'revision' => $m['storedRevision'] ?? 0, 'checksum' => $m['checksum'] ?? null, 'scopeProof' => $RuntimeContext];
                if (!RuntimeControlProtocol::RequestValid($request)) throw new RuntimeException('invalid_request');
                $this->WithWorkspaceLock(function () use ($SystemID, $Generation, $Fingerprint, $request, $op): void
                {
                    $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                    $j = $this->ReadWorkspaceRuntime();
                    $j['systems'] = array_intersect_key($j['systems'], $this->ReadWorkspace()['systems']);
                    $j['systems'][$SystemID] = ['request' => $request, 'status' => 'prepared', 'error' => '', 'mayHaveControlled' => false];
                    $j['receipts'][$request['operation']] = $op['request'];
                    $j['receipts'] = array_slice($j['receipts'], -32, null, true);
                    // Persist before delegation: a lost response must never permit a blind local rollback.
                    if ($request['kind'] !== 'status') $j['controlledEpochs'][$request['epoch']] = true;
                    $this->WriteWorkspaceRuntime($j);
                });
                $this->DebugEvent('SENDEN', 'Betriebsauftrag ausdrücklich an die zugeordnete Z2M-Verbindung übergeben. Kein Entwurf wird gespeichert.', ['Systembezug' => $SystemID, 'Auftrag' => $Action, 'Verbindung' => $record['binding']['connectionInstance']]);
                try {
                    $reply = json_decode(Z2MC_RequestRuntimeControl($record['binding']['connectionInstance'], $this->InstanceID, $OperationID), true, 24, JSON_THROW_ON_ERROR);
                    $this->AcceptWorkspaceRuntimeResult($SystemID, $OperationID, $reply);
                } catch (Throwable) {
                    $this->DebugEvent('FEHLER', 'Übergabe des Betriebsauftrags nicht eindeutig bestätigt. Kein erneuter Versand; zuerst den Status prüfen.', ['Systembezug' => $SystemID]);
                }
                $this->SetTimerInterval('WorkspaceRuntimeRefresh', 1000);
                return $this->GetWorkspaceRuntimeStatus($SystemID);
            } catch (Throwable $e) {
                return $this->WorkspaceRuntimeFailure($e);
            }
        });
    }

    /** Pure cross-entry authority; never an unbound start permission. */
    public function GetWorkspaceRuntimeAuthority(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                if (!DraftTransferProtocol::Operation($OperationID)) throw new RuntimeException();
                $jobs = array_values(array_filter($this->ReadWorkspaceRuntime()['systems'], static fn (array $j): bool => $j['request']['operation'] === $OperationID));
                if (count($jobs) !== 1 || !in_array($jobs[0]['status'], ['prepared', 'pending'], true)) throw new RuntimeException();
                $r = $jobs[0]['request'];
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $r['binding']['systemId']);
                // Called while the connection holds its transport lock. Read only local
                // authority here; calling back into that connection would self-lock.
                $marker = $this->OwnershipMarker();
                if ($record['binding'] !== $r['binding'] || $record['ownershipEpoch'] !== $r['epoch']
                    || ($marker['role'] ?? '') !== 'manager' || $marker['owner'] !== $this->InstanceID
                    || $marker['operation'] !== $r['epoch'] || !$this->MigrationWorkspaceWritable()) throw new RuntimeException();
                $current = $this->RuntimeDocumentMatches($record, $r);
                return json_encode(['ok' => true, 'request' => $r, 'documentCurrent' => $current], JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                return '{"ok":false}';
            }
        });
    }

    public function GetWorkspaceRuntimeStatus(string $SystemID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID): string
        {
            try {
                $this->SyncWorkspaceRuntime($SystemID);
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID);
                $view = $this->WorkspaceRuntimeView($record);
                $this->UpdateWorkspaceRuntimeDisplay($SystemID, $view);
                return $this->WorkspaceResult('', ['runtime' => $view]);
            } catch (Throwable $e) {
                return $this->WorkspaceRuntimeFailure($e);
            }
        });
    }

    public function SelectWorkspaceRuntimeRule(string $SystemID, int $Generation, string $Fingerprint, string $RuleID): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $RuleID): bool
        {
            try {
                $state = $this->ReadWorkspace();
                $record = $this->WorkspaceRecord($state, $SystemID, $Generation, $Fingerprint);
                if ($state['selectedSystem'] !== $SystemID) throw new RuntimeException('stale_workspace');
                $view = $this->WorkspaceRuntimeView($record);
                if ($RuleID !== '' && !isset($view['rules'][$RuleID])) throw new RuntimeException('runtime_selection');
                $selected = json_decode($this->GetBuffer('WorkspaceRuntimeRules') ?: '[]', true, 8, JSON_THROW_ON_ERROR);
                $selected[$SystemID] = $RuleID;
                $this->SetBuffer('WorkspaceRuntimeRules', json_encode($selected, JSON_THROW_ON_ERROR));
                $this->UpdateWorkspaceRuntimeDisplay($SystemID, $view);
                $this->DebugEvent('AKTION', 'Gespeicherte Automation für die Einzelsteuerung ausgewählt. Kein Start und keine Entwurfsänderung.', ['Systembezug' => $SystemID]);
                return true;
            } catch (Throwable $e) {
                $this->WorkspaceRuntimeFailure($e);
                return false;
            }
        });
    }

    public function RefreshWorkspaceRuntime(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            $pending = false;
            foreach ($this->ReadWorkspace()['systems'] as $id => $record) {
                $this->SyncWorkspaceRuntime($id);
                $view = $this->WorkspaceRuntimeView($record);
                $this->UpdateWorkspaceRuntimeDisplay($id, $view);
                $pending = $pending || $view['pending'] || array_any($view['rules'], static fn (array $r): bool => $r['state'] === 'running');
            }
            $this->SetTimerInterval('WorkspaceRuntimeRefresh', $pending ? 2000 : 0);
        });
    }

    private function SyncWorkspaceRuntime(string $system): void
    {
        $job = $this->ReadWorkspaceRuntime()['systems'][$system] ?? null;
        if ($job === null || !in_array($job['status'], ['prepared', 'pending'], true)) return;
        try {
            $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
            if ($record['binding'] !== $job['request']['binding'] || !$this->WorkspaceConnectionCurrent($record['binding'])) {
                $reply = ['ok' => false, 'error' => 'binding_changed'];
            } else $reply = json_decode(Z2MC_GetRuntimeControlResult($record['binding']['connectionInstance'], $this->InstanceID, $job['request']['operation']), true, 24, JSON_THROW_ON_ERROR);
            if (($reply['error'] ?? '') === 'runtime_missing') $reply = ['ok' => false, 'error' => 'runtime_interrupted'];
            $this->AcceptWorkspaceRuntimeResult($system, $job['request']['operation'], $reply);
        } catch (Throwable) {
            $this->DebugEvent('AKTION', 'Betriebsrückmeldung noch nicht lesbar. Bisherigen Status behalten; keinen Befehl erneut senden.', ['Systembezug' => $system]);
        }
    }

    private function AcceptWorkspaceRuntimeResult(string $system, string $operation, array $reply): void
    {
        $this->WithWorkspaceLock(function () use ($system, $operation, $reply): void
        {
            $j = $this->ReadWorkspaceRuntime();
            $job = $j['systems'][$system] ?? null;
            if ($job === null || $job['request']['operation'] !== $operation) return;
            if (($reply['ok'] ?? false) !== true) $reply = ['status' => 'error', 'error' => $reply['error'] ?? 'control_unavailable', 'mayHaveControlled' => false];
            elseif (($reply['operation'] ?? '') !== $operation || ($reply['kind'] ?? '') !== $job['request']['kind']) throw new RuntimeException('runtime_changed');
            if (!in_array($reply['status'] ?? null, RuntimeControlProtocol::STATES, true) || !is_string($reply['error'] ?? null) || !is_bool($reply['mayHaveControlled'] ?? null)) throw new RuntimeException('control_unavailable');
            $old = $job;
            foreach (['status', 'error', 'mayHaveControlled'] as $key) $job[$key] = $reply[$key];
            $j['systems'][$system] = $job;
            $this->WriteWorkspaceRuntime($j);
            if ($old !== $job) $this->DebugEvent($job['error'] === '' ? 'EMPFANG' : 'FEHLER', 'Betriebsergebnis dem ausgewählten Z2M-System zugeordnet. Andere Systeme bleiben unabhängig.', ['Systembezug' => $system, 'Ergebnis' => $job['status'], 'Ursache' => $job['error']]);
        });
    }

    private function WorkspaceRuntimeAccess(array $record): array
    {
        if (!$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
        $m = $this->OwnershipMarker();
        $o = json_decode(Z2MC_GetSystemOwnership($record['binding']['connectionInstance']), true, 16, JSON_THROW_ON_ERROR);
        if (($m['role'] ?? '') !== 'manager' || ($m['owner'] ?? 0) !== $this->InstanceID || !$this->MigrationWorkspaceWritable()
            || ($record['ownershipEpoch'] ?? '') !== $m['operation'] || ($o['role'] ?? '') !== 'connection' || $o['owner'] !== $this->InstanceID
            || $o['operation'] !== $m['operation']) throw new RuntimeException('ownership_required');
        return $this->WorkspaceInfo($record['binding']);
    }

    private function RuntimeDocumentMatches(array $record, array $r): bool
    {
        $doc = $record['data']['document'];
        return $doc !== null && !WorkspaceState::Dirty($record) && ($doc['editorDraft']['configuration']['revision'] ?? 0) === $r['revision']
            && hash('sha256', $doc['yaml']) === $r['checksum'] && $doc['editorDraft']['draftChecksum'] === $r['checksum'];
    }

    private function WorkspaceRuntimeView(array $record): array
    {
        $system = $record['binding']['systemId'];
        $job = $this->ReadWorkspaceRuntime()['systems'][$system] ?? null;
        $blocked = '';
        try {
            $info = $this->WorkspaceRuntimeAccess($record);
        }
        catch (Throwable $e) {
            $info = [];
            $blocked = $e instanceof RuntimeException ? $e->getMessage() : 'control_unavailable';
        }
        $s = $info['state'] ?? [];
        $m = $s['extension'] ?? [];
        $connected = $blocked === '' && ($s['mqttConnected'] ?? false);
        $confirmed = $connected && ($s['extensionConfirmed'] ?? false) && ($s['runtimeConfirmed'] ?? false);
        $pending = $job !== null && in_array($job['status'], ['prepared', 'pending'], true);
        $matches = $confirmed && ($m['storage'] ?? '') === 'ok' && $this->RuntimeDocumentMatches($record, ['revision' => $m['storedRevision'], 'checksum' => $m['checksum']]);
        $canStart = $matches && !$pending && ($m['runtimeVersion'] ?? 0) === 1;
        $runs = $confirmed && ($s['execution']['session'] ?? '') === ($m['session'] ?? null)
            && ($s['execution']['revision'] ?? null) === ($m['storedRevision'] ?? 0) && ($s['execution']['checksum'] ?? '') === ($m['checksum'] ?? null) ? $s['execution']['runs'] : [];
        $names = [];
        foreach ($record['data']['document']['editorDraft']['configuration']['automations'] ?? [] as $rule) $names[$rule['id']] = $rule['name'];
        $localRules = $names;
        foreach ($runs as $id => $run) $names[$id] ??= 'Gespeicherte Automation ' . $id;
        $rules = [];
        foreach ($names as $id => $name) {
            $r = $runs[$id] ?? null;
            $rules[$id] = ['name' => $name, 'run' => $r['run'] ?? '', 'state' => $r['state'] ?? '', 'step' => $r['step'] ?? 0,
                'message'         => RuntimeControlProtocol::RunText($r) . (($r['state'] ?? '') === 'failed' && $r['error'] !== '' ? ' ' . $this->WorkspaceRuntimeError($r['error']) : ''), 'error' => $r['error'] ?? '',
                'canRun'          => isset($localRules[$id]) && $canStart && ($m['executionVersion'] ?? 0) === 1 && ($r['state'] ?? '') !== 'running',
                'canStop'         => $connected && ($r['state'] ?? '') === 'running' && !($pending && $job['request']['kind'] === 'deactivate')];
        }
        $status = $job['status'] ?? 'idle';
        $error = $job['error'] ?? '';
        $message = $blocked !== '' ? $this->WorkspaceRuntimeError($blocked) : ($pending ? 'Betriebsauftrag für dieses System läuft. Die Bestätigung von Z2M steht noch aus.'
            : ($error !== '' ? $this->WorkspaceRuntimeError($error) : match ($status) {
                'activated'   => $confirmed && $m['executing'] ? 'Automatikbetrieb in diesem Z2M-System aktiviert und durch frischen Status bestätigt.' : 'Der Betriebsstatus hat sich seit dem letzten Aktivierungsauftrag geändert. Bitte den aktuellen Status beachten.',
                'stopped'     => $confirmed && !$m['executing'] ? 'Automatikbetrieb in diesem Z2M-System gestoppt und durch frischen Status bestätigt.' : 'Der Betriebsstatus hat sich seit dem letzten Stoppauftrag geändert. Bitte den aktuellen Status beachten.',
                'run_started' => 'Einzelstart bestätigt. Bedingungen gelten weiterhin; der Automatikbetrieb bleibt unverändert.',
                'run_stopped' => 'Genau der ausgewählte Lauf wurde gestoppt. Andere Automationen bleiben unverändert.',
                default       => $confirmed ? 'Betriebsstatus von Z2M bestätigt.' : 'Bitte den Betriebsstatus dieses Systems aktualisieren.'
            }));
        return ['systemId'       => $system, 'generation' => $record['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($record),
            'context'            => WorkspaceState::Fingerprint([$job, $m['session'] ?? '', $m['storedRevision'] ?? 0, $m['checksum'] ?? null]),
            'operation'          => $job['request']['operation'] ?? '', 'kind' => $job['request']['kind'] ?? '', 'status' => $status, 'message' => $message,
            'blocked'            => $blocked, 'pending' => $pending, 'confirmed' => $confirmed, 'executing' => $confirmed ? ($m['executing'] ?? false) : null,
            'storedRevision'     => $confirmed ? $m['storedRevision'] : null, 'activeRevision' => $confirmed ? $m['activeRevision'] : null,
            'resumeAfterRestart' => $confirmed ? ($m['resumeEnabled'] ?? false) : true,
            'canRefresh'         => $connected && !$pending, 'canActivate' => $canStart, 'canStop' => $connected, 'rules' => $rules,
            'startNotice'        => $matches ? '' : 'Zum Starten muss der Entwurf dem gespeicherten Z2M-Stand entsprechen. Offene Änderungen zuerst übernehmen, prüfen und speichern. Stoppen bleibt möglich.'];
    }

    private function WorkspaceRuntimeError(string $code): string
    {
        return RuntimeControlProtocol::MESSAGES[$code] ?? WorkspaceState::MESSAGES[$code]
            ?? (in_array($code, RuntimeControlProtocol::ERRORS, true) ? $this->Translate(ExtensionContract::ErrorText($code)) : RuntimeControlProtocol::MESSAGES['control_unavailable']);
    }

    private function WorkspaceRuntimeFailure(Throwable $e): string
    {
        $code = $e instanceof RuntimeException ? $e->getMessage() : 'control_unavailable';
        if (!isset(RuntimeControlProtocol::MESSAGES[$code]) && !isset(WorkspaceState::MESSAGES[$code]) && !in_array($code, RuntimeControlProtocol::ERRORS, true)) $code = 'control_unavailable';
        $message = $this->WorkspaceRuntimeError($code);
        $this->DebugEvent('FEHLER', $message);
        return json_encode(['version' => 1, 'ok' => false, 'error' => $code, 'message' => $message], WorkspaceState::JSON_FLAGS);
    }

    private function ReadWorkspaceRuntime(): array
    {
        $raw = $this->ReadAttributeString('WorkspaceRuntime');
        $j = $raw === '' ? ['version' => 1, 'systems' => [], 'receipts' => [], 'controlledEpochs' => []] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($j) || ($j['version'] ?? null) !== 1 || !is_array($j['systems'] ?? null) || count($j['systems']) > 8
            || !is_array($j['receipts'] ?? null) || count($j['receipts']) > 32 || !is_array($j['controlledEpochs'] ?? null) || strlen($raw) > 1048576) throw new RuntimeException('control_unavailable');
        foreach ($j['systems'] as $id => $job) if (!RuntimeControlProtocol::RequestValid($job['request'] ?? null) || $job['request']['binding']['systemId'] !== $id
            || !in_array($job['status'] ?? null, RuntimeControlProtocol::STATES, true) || !is_string($job['error'] ?? null) || !is_bool($job['mayHaveControlled'] ?? null)) throw new RuntimeException('control_unavailable');
        foreach ($j['receipts'] as $key => $digest) if (!DraftTransferProtocol::Operation($key) || !ExtensionContract::Checksum($digest)) throw new RuntimeException('control_unavailable');
        foreach ($j['controlledEpochs'] as $key => $used) if (!DraftTransferProtocol::Operation($key) || $used !== true) throw new RuntimeException('control_unavailable');
        return $j;
    }

    private function WriteWorkspaceRuntime(array $j): void
    {
        $raw = json_encode($j, WorkspaceState::JSON_FLAGS);
        if (strlen($raw) > 1048576) throw new RuntimeException('control_unavailable');
        if ($raw !== $this->ReadAttributeString('WorkspaceRuntime')) $this->WriteAttributeString('WorkspaceRuntime', $raw);
    }
}
