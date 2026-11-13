<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

trait WorkspaceDesigner
{
    private function DesignerAccessScope(): string
    {
        if (!$this->WorkspaceMode()) return $this->TransportScope();
        $state = $this->workspaceEditorContext['workspace'] ?? $this->ReadWorkspace();
        return json_encode(['workspace', $this->OwnershipMarker(), array_column($state['systems'], 'binding')], JSON_THROW_ON_ERROR);
    }

    private function WorkspaceDesignerData(string $system = ''): string
    {
        $state = $this->ReadWorkspace();
        $system = $system !== '' ? $system : $state['selectedSystem'];
        if ($system === '') return json_encode(['contractVersion' => 1, 'mode' => 'read', 'workspace' => json_decode($this->GetWorkspaceOverview(), true),
            'module'                                              => ['id' => $this->InstanceID, 'name' => IPS_GetName($this->InstanceID), 'baseTopic' => '', 'gateway' => 0],
            'status'                                              => ['confirmed' => false], 'devices' => [], 'configuration' => null, 'draftAvailable' => false, 'createAllowed' => false, 'editAllowed' => false], JSON_THROW_ON_ERROR);
        $this->SyncWorkspaceTransfer($system);
        $this->SyncWorkspaceRuntime($system);
        unset($state);
        return $this->WithWorkspaceEditor($system, null, null, false, fn (): string => $this->DesignerData());
    }

    private function WorkspaceDesignerRequest(string $asset): array
    {
        try {
            $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
            if (!$this->DesignerTokenAllowed($token)) return [403, ['message' => 'Der Designerzugang ist abgelaufen. Bitte den Designer erneut im Modul öffnen.']];
            if (in_array($asset, ['workspace-status', 'workspace-configure'], true)) {
                if (!$this->DesignerWriteAccess($token, 'setup')) return [403, ['message' => 'Bitte den Designer für die Systemzuordnung erneut im Modul öffnen.']];
                $state = $this->ReadWorkspace();
                $choices = $this->WorkspaceConnectionChoices($state);
                if ($asset === 'workspace-configure') {
                    $failure = $this->DesignerWriteHeaders();
                    if ($failure !== null) return $failure;
                    $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                    if (!$this->DesignerEditShape($body, ['requestId', 'fingerprint', 'candidates', 'connections']) || !RuntimeDebugContract::UUID($body['requestId'])
                        || !ExtensionContract::Checksum($body['fingerprint']) || !ExtensionContract::Checksum($body['candidates'])
                        || !is_array($body['connections']) || !array_is_list($body['connections']) || count($body['connections']) > SystemContract::MAX_SYSTEMS
                        || !hash_equals($choices['fingerprint'], $body['candidates'])) throw new RuntimeException('invalid_request');
                    foreach ($body['connections'] as $id) if (!is_int($id) || !array_key_exists($id, $choices['availability']) || $choices['availability'][$id] !== '') throw new RuntimeException('connection_unavailable');
                    $sessions = $this->DesignerSessions();
                    if (!hash_equals(WorkspaceState::Fingerprint($state), $body['fingerprint'])) throw new RuntimeException('stale_workspace');
                    $assigned = array_map(static fn (array $record): int => $record['binding']['connectionInstance'], array_values($state['systems']));
                    $selected = $body['connections'];
                    sort($assigned);
                    sort($selected);
                    if ($assigned !== $selected) {
                        $result = json_decode($this->ConfigureWorkspace(json_encode($body['connections'], JSON_THROW_ON_ERROR), $body['fingerprint'], str_replace('-', '', $body['requestId'])), true, 32, JSON_THROW_ON_ERROR);
                        if (!$result['ok']) return [409, $result];
                    }
                    // Only the session that explicitly assigned these systems receives the new scope.
                    foreach ($sessions as &$session) if (hash_equals($session['hash'], hash('sha256', $token))) $session['scope'] = $this->DesignerAccessScope();
                    unset($session);
                    $this->WriteEditorBuffer('DesignerSessions', json_encode($sessions, JSON_THROW_ON_ERROR));
                    $state = $this->ReadWorkspace();
                    $choices = $this->WorkspaceConnectionChoices($state);
                    $this->DebugEvent('AKTION', 'Systemzuordnung aus dem Designer ausdrücklich übernommen. Nur der ausführende Zugang wurde an die bestätigte Zuordnung gebunden. Keine MQTT-Anfrage.');
                }
                return [200, ['ok' => true, 'fingerprint' => WorkspaceState::Fingerprint($state), 'candidates' => $choices['fingerprint'], 'choices' => $choices['rows']]];
            }
            if ($asset === 'data') return [200, json_decode($this->WorkspaceDesignerData($_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? $this->DesignerInitialSystem($token)), true, 32, JSON_THROW_ON_ERROR)];
            if ($asset === 'permissions-status') return $this->WorkspacePermissionsRequest(false);
            if ($asset === 'installation-status') {
                if (!$this->DesignerWriteAccess($token, 'installation')) return [403, ['message' => 'Bitte den Designer für die Installation erneut im Modul öffnen.']];
                $system = $_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? '';
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
                if ((string) $record['binding']['generation'] !== ($_SERVER['HTTP_X_Z2MA_GENERATION'] ?? '')) throw new RuntimeException('binding_changed');
                $result = json_decode($this->GetWorkspaceInstallationStatus($system), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'diagnostics-status') {
                if (!$this->DesignerWriteAccess($token, 'diagnostics')) return [403, ['message' => 'Bitte den Designer für die Diagnose erneut im Modul öffnen.']];
                $system = $_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? '';
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
                if ((string) $record['binding']['generation'] !== ($_SERVER['HTTP_X_Z2MA_GENERATION'] ?? '')) throw new RuntimeException('binding_changed');
                $result = json_decode($this->GetWorkspaceDiagnostics($system), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'catalog-status') {
                $system = $_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? '';
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
                if ((string) $record['binding']['generation'] !== ($_SERVER['HTTP_X_Z2MA_GENERATION'] ?? '')) throw new RuntimeException('binding_changed');
                $result = json_decode($this->GetWorkspaceDeviceCatalogStatus($system), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'runtime-status') {
                $system = $_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? '';
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
                if ((string) $record['binding']['generation'] !== ($_SERVER['HTTP_X_Z2MA_GENERATION'] ?? '')) throw new RuntimeException('binding_changed');
                $result = json_decode($this->GetWorkspaceRuntimeStatus($system), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'transfer-status') {
                $result = json_decode($this->GetWorkspaceTransferStatus($_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? ''), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'devices') {
                $offset = $_SERVER['HTTP_X_Z2MA_OFFSET'] ?? '';
                if (!is_string($offset) || preg_match('/\A(?:0|[1-9][0-9]{0,4})\z/D', $offset) !== 1) throw new RuntimeException('invalid_request');
                $system = $_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? '';
                $fingerprint = $_SERVER['HTTP_X_Z2MA_FINGERPRINT'] ?? '';
                $workspace = $this->ReadWorkspace();
                if (($_SERVER['HTTP_X_Z2MA_WORKSPACE_FINGERPRINT'] ?? '') !== WorkspaceState::Fingerprint($workspace)) throw new RuntimeException('stale_workspace');
                return $this->WithWorkspaceEditor($system, null, $fingerprint, false, function () use ($offset): array
                {
                    $this->workspaceEditorContext['catalogOnly'] = true;
                    $this->workspaceEditorContext['catalogOffset'] = (int) $offset;
                    return [200, json_decode($this->DesignerData(), true, 32, JSON_THROW_ON_ERROR)];
                }, false, $workspace);
            }
            $failure = $this->DesignerWriteHeaders();
            if ($failure !== null) return $failure;
            if ($asset === 'permissions') return $this->WorkspacePermissionsRequest(true);
            if ($asset === 'select-system') {
                $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                if (count($body) !== 3 || !is_string($body['systemId'] ?? null) || !is_string($body['fingerprint'] ?? null)
                    || !is_string($body['requestId'] ?? null) || !RuntimeDebugContract::UUID($body['requestId'])) throw new RuntimeException('invalid_request');
                $result = json_decode($this->SelectWorkspaceSystem($body['systemId'], $body['fingerprint'], str_replace('-', '', $body['requestId'])), true, 16, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            $system = $_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? '';
            $rawGeneration = $_SERVER['HTTP_X_Z2MA_GENERATION'] ?? '';
            $fingerprint = $_SERVER['HTTP_X_Z2MA_FINGERPRINT'] ?? '';
            if (!SystemContract::SystemID($system) || !is_string($rawGeneration) || preg_match('/\A[1-9][0-9]{0,15}\z/D', $rawGeneration) !== 1
                || !ExtensionContract::Checksum($fingerprint)) throw new RuntimeException('invalid_request');
            if ($asset === 'catalog-refresh') {
                if (!$this->DesignerWriteAccess($token, 'catalog')) return [403, ['message' => 'Bitte den Designer zum Aktualisieren der Gerätedaten erneut im Modul öffnen.']];
                $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                if (!$this->DesignerEditShape($body, ['requestId']) || !RuntimeDebugContract::UUID($body['requestId'])) throw new RuntimeException('invalid_request');
                $result = json_decode($this->RequestWorkspaceDeviceCatalog($system, (int) $rawGeneration, $fingerprint, str_replace('-', '', $body['requestId'])), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'installation') {
                if (!$this->DesignerWriteAccess($token, 'installation')) return [403, ['message' => 'Bitte den Designer für die Installation erneut im Modul öffnen.']];
                $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                if (!$this->DesignerEditShape($body, ['requestId', 'context', 'confirmed']) || !RuntimeDebugContract::UUID($body['requestId'])
                    || !ExtensionContract::Checksum($body['context']) || $body['confirmed'] !== true) throw new RuntimeException('installation_confirmation');
                $result = json_decode($this->RequestWorkspaceInstallation($system, (int) $rawGeneration, $fingerprint, str_replace('-', '', $body['requestId']), $body['context'], true), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'diagnostics') {
                if (!$this->DesignerWriteAccess($token, 'diagnostics')) return [403, ['message' => 'Bitte den Designer für die Diagnose erneut im Modul öffnen.']];
                $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                if (!$this->DesignerEditShape($body, ['requestId', 'action', 'seconds', 'context']) || !RuntimeDebugContract::UUID($body['requestId'])
                    || !is_string($body['action']) || !is_int($body['seconds']) || !ExtensionContract::Checksum($body['context'])) throw new RuntimeException('invalid_request');
                $result = json_decode($this->RequestWorkspaceDiagnostics($system, (int) $rawGeneration, $fingerprint, $body['action'], $body['seconds'], str_replace('-', '', $body['requestId']), $body['context']), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'runtime') {
                $allowed = array_any($this->DesignerSessions(), static fn (array $s): bool => ($s['runtime'] ?? false) === true && hash_equals($s['hash'], hash('sha256', $token)));
                if (!$allowed) return [403, ['message' => 'Bitte den Designer für die Betriebssteuerung erneut im Modul öffnen.']];
                $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                if (count($body) !== 6 || !in_array($body['action'] ?? null, RuntimeControlProtocol::KINDS, true)
                    || !RuntimeDebugContract::UUID($body['requestId'] ?? null) || !ExtensionContract::Checksum($body['context'] ?? null)
                    || !is_string($body['rule'] ?? null) || !is_string($body['run'] ?? null) || !is_bool($body['resume'] ?? null)) throw new RuntimeException('invalid_request');
                $result = json_decode($this->RequestWorkspaceRuntime(
                    $system,
                    (int) $rawGeneration,
                    $fingerprint,
                    $body['action'],
                    $body['rule'],
                    $body['run'],
                    $body['resume'],
                    str_replace('-', '', $body['requestId']),
                    $body['context']
                ), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            if ($asset === 'transfer') {
                $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                if (count($body) !== 3 || !in_array($body['action'] ?? null, ['validate', 'store'], true)
                    || !RuntimeDebugContract::UUID($body['requestId'] ?? null) || !ExtensionContract::Checksum($body['context'] ?? null)) throw new RuntimeException('invalid_request');
                $result = json_decode($this->RequestWorkspaceTransfer($system, (int) $rawGeneration, $fingerprint, $body['action'], str_replace('-', '', $body['requestId']), $body['context']), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            $sessions = $this->GetBuffer('DesignerSessions');
            try {
                $export = $asset === 'create-yaml' || $asset === 'edit-yaml';
                $result = $this->WithWorkspaceEditor($system, (int) $rawGeneration, $fingerprint, !$export, function () use ($asset): array
                {
                    return match ($asset) {
                        'create', 'create-yaml' => $this->DesignerCreateRequest($asset === 'create-yaml'),
                        'edit', 'edit-yaml'     => $this->DesignerEditRequest($asset === 'edit-yaml'),
                        'sequence'              => $this->DesignerSequenceRequest(),
                        'manage'                => $this->DesignerManageRequest(),
                        'event-import'          => $this->DesignerImportRequest(),
                        default                 => [404, ['message' => 'Diese Designeraktion ist nicht verfügbar.']]
                    };
                });
                if ($export && $result[0] === 200) {
                    $this->WithWorkspaceLock(function () use ($system, $rawGeneration, $fingerprint): void
                    {
                        $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system, (int) $rawGeneration, $fingerprint);
                        if (!$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
                    });
                }
                return $result;
            } catch (Throwable $exception) {
                $this->SetBuffer('DesignerSessions', $sessions);
                throw $exception;
            }
        } catch (Throwable $exception) {
            $failure = json_decode($asset === 'installation' ? $this->WorkspaceInstallationFailure($exception) : $this->WorkspaceFailure($exception), true, 16, JSON_THROW_ON_ERROR);
            return [409, $failure];
        }
    }
}
