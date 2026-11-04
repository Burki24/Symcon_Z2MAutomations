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

    private function WorkspaceDesignerOpen(): string
    {
        if (!$this->RegisterHook($this->DesignerHookName())) return '';
        $token = bin2hex(random_bytes(32));
        $sessions = $this->DesignerSessions();
        $sessions[] = ['hash' => hash('sha256', $token), 'expires' => $this->Now() + 900, 'scope' => $this->DesignerAccessScope(),
            'create'          => true, 'edit' => true, 'sequences' => true, 'receipts' => []];
        $this->SetBuffer('DesignerSessions', json_encode(array_slice($sessions, -4), JSON_THROW_ON_ERROR));
        $this->DebugEvent('AKTION', 'Gemeinsamen Browserdesigner für die zugeordneten Systeme geöffnet. Jeder Auftrag benötigt den passenden Systementwurf; keine Z2M-Aktion.', ['Systeme' => count($this->ReadWorkspace()['systems'])]);
        return 'link:/hook/' . $this->DesignerHookName() . '/index.html#' . $token;
    }

    private function WorkspaceDesignerData(string $system = ''): string
    {
        $state = $this->ReadWorkspace();
        $system = $system !== '' ? $system : $state['selectedSystem'];
        if ($system === '') return json_encode(['contractVersion' => 1, 'mode' => 'read', 'workspace' => json_decode($this->GetWorkspaceOverview(), true),
            'module'                                              => ['id' => $this->InstanceID, 'name' => IPS_GetName($this->InstanceID), 'baseTopic' => '', 'gateway' => 0],
            'status'                                              => ['confirmed' => false], 'devices' => [], 'configuration' => null, 'draftAvailable' => false, 'createAllowed' => false, 'editAllowed' => false], JSON_THROW_ON_ERROR);
        $this->SyncWorkspaceTransfer($system);
        unset($state);
        return $this->WithWorkspaceEditor($system, null, null, false, fn (): string => $this->DesignerData());
    }

    private function WorkspaceDesignerRequest(string $asset): array
    {
        try {
            $token = $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '';
            if (!$this->DesignerTokenAllowed($token)) return [403, ['message' => 'Der Designerzugang ist abgelaufen. Bitte den Designer erneut im Modul öffnen.']];
            if ($asset === 'data') return [200, json_decode($this->WorkspaceDesignerData($_SERVER['HTTP_X_Z2MA_SYSTEM_ID'] ?? ''), true, 32, JSON_THROW_ON_ERROR)];
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
            if ($asset === 'transfer') {
                $body = $this->DecodeWorkspaceInput($this->DesignerRequestBody(), 4096);
                if (count($body) !== 3 || !in_array($body['action'] ?? null, ['validate', 'store'], true)
                    || !RuntimeDebugContract::UUID($body['requestId'] ?? null) || !ExtensionContract::Checksum($body['context'] ?? null)) throw new RuntimeException('invalid_request');
                $result = json_decode($this->RequestWorkspaceTransfer($system, (int) $rawGeneration, $fingerprint, $body['action'], str_replace('-', '', $body['requestId']), $body['context']), true, 32, JSON_THROW_ON_ERROR);
                return [$result['ok'] ? 200 : 409, $result];
            }
            $sessions = $this->GetBuffer('DesignerSessions');
            try {
                return $this->WithWorkspaceEditor($system, (int) $rawGeneration, $fingerprint, true, function () use ($asset): array
                {
                    return match ($asset) {
                        'create' => $this->DesignerCreateRequest(), 'edit' => $this->DesignerEditRequest(), 'sequence' => $this->DesignerSequenceRequest(), default => [404, ['message' => 'Diese Designeraktion ist nicht verfügbar.']]
                    };
                });
            } catch (Throwable $exception) {
                $this->SetBuffer('DesignerSessions', $sessions);
                throw $exception;
            }
        } catch (Throwable $exception) {
            $failure = json_decode($this->WorkspaceFailure($exception), true, 16, JSON_THROW_ON_ERROR);
            return [409, $failure];
        }
    }
}
