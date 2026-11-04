<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/SystemContract.php';

/** The persistent fence is local to this Symcon installation, not MQTT authority. */
trait SystemOwnership
{
    private bool $ownershipEntry = false;
    private ?array $workspaceEditorContext = null;

    public function GetSystemOwnership(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => json_encode($this->OwnershipMarker(), JSON_THROW_ON_ERROR));
    }

    private function OwnershipMarker(): array
    {
        $raw = $this->ReadAttributeString('SystemOwnership');
        if ($raw === '') return [];
        $value = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($value) || ($value['version'] ?? null) !== 1 || !in_array($value['role'] ?? '', ['legacy', 'connection', 'manager'], true)
            || !is_int($value['owner'] ?? null) || $value['owner'] < 10000 || $value['owner'] > 59999
            || preg_match('/\A[a-f0-9]{32}\z/D', $value['operation'] ?? '') !== 1) throw new RuntimeException('ownership_conflict');
        $keys = ['version', 'role', 'owner', 'operation'];
        if ($value['role'] === 'legacy') {
            $keys[] = 'fingerprint';
            if (!is_string($value['fingerprint'] ?? null) || preg_match('/\A[a-f0-9]{64}\z/D', $value['fingerprint']) !== 1) throw new RuntimeException('ownership_conflict');
        }
        if ($value['role'] === 'connection') {
            array_push($keys, 'systemId', 'generation', 'baseTopic');
            if (!SystemContract::SystemID($value['systemId'] ?? null) || !SystemContract::Topic($value['baseTopic'] ?? null)
                || !is_int($value['generation'] ?? null) || $value['generation'] < 1) throw new RuntimeException('ownership_conflict');
        }
        if (count($value) !== count($keys) || array_diff(array_keys($value), $keys) !== []) throw new RuntimeException('ownership_conflict');
        return $value;
    }

    private function WithOwnershipEntry(string $function, callable $callback): mixed
    {
        if (in_array($function, ['Create', 'GetSystemOwnership', 'GetSystemMigrationAuthority', 'GetWorkspaceTransferAuthority', 'GetWorkspaceSetupAuthority', 'GetCompatibleParents'], true)) return $callback();
        $nested = $this->ownershipEntry;
        // Replies may arrive while SendDataToParent is still on another entry's stack.
        $receive = $function === 'ReceiveData';
        $lock = 'Z2MA.Owner.' . $this->InstanceID;
        if (!$nested && !$receive && !IPS_SemaphoreEnter($lock, 0)) {
            $asset = $function === 'ProcessHookData' && method_exists($this, 'DesignerAssetRequest') && $this->DesignerAssetRequest();
            if (!$asset) return $this->OwnershipRejected($function);
            $this->DebugEvent('AKTION', 'Paralleler Designer-Dateiaufruf wartet kurz auf den laufenden Aufruf. Entwürfe und Z2M bleiben unverändert.', ['HöchstensMillisekunden' => 5000]);
            if (!IPS_SemaphoreEnter($lock, 5000)) return $this->OwnershipRejected($function, true);
            $this->DebugEvent('AKTION', 'Designer-Dateiaufruf nach der Wartezeit freigegeben; Besitzerprüfung folgt unverändert.');
        }
        $this->ownershipEntry = $nested || !$receive;
        try {
            try {
                $marker = $this->OwnershipMarker();
            } catch (Throwable) {
                return $this->OwnershipRejected($function);
            }
            if ($this->workspaceEditorContext === null && method_exists($this, 'WorkspaceMode') && in_array($function, self::WORKSPACE_EDITOR_ACTIONS, true) && $this->WorkspaceMode()) return $this->OwnershipRejected($function);
            $migration = in_array($function, ['BackupSystemMigration', 'PrepareSystemMigration', 'ApplySystemMigration', 'RollbackSystemMigration', 'GetSystemMigration', 'ExportSystemMigration', 'FenceSystemMigration', 'GetSystemMigrationSource', 'PreviewSystemMigration', 'ClaimSystemConnection'], true);
            $workspace = str_contains($function, 'Workspace');
            $setup = method_exists($this, 'ReadWorkspaceSetup') && in_array($function, ['GetWorkspaceSetupStatus', 'RefreshWorkspaceSetup', 'ConfirmWorkspaceSetup', 'CancelWorkspaceSetup', 'RollbackWorkspaceSetup', 'GetConfigurationForm'], true);
            if (method_exists($this, 'WorkspaceSetupLocked') && $this->WorkspaceSetupLocked() && !$setup) return $this->OwnershipRejected($function);
            try {
                $editor = $this->workspaceEditorContext !== null && in_array($function, self::WORKSPACE_EDITOR_ACTIONS, true);
                $ui = in_array($function, ['GetConfigurationForm', 'OpenAutomationDesigner', 'GetDesignerData', 'ProcessHookData', 'ChooseWorkspaceSystem', 'ApplyWorkspaceConnections', 'ReadWorkspaceCatalog'], true);
                $allowed = $marker === [] || $migration || ($marker['role'] === 'manager' && ($setup || (($workspace || $editor || $ui) && $this->MigrationWorkspaceWritable())));
            }
            catch (Throwable) {
                return $this->OwnershipRejected($function);
            }
            // Connection observations remain available; installation is a separate explicit owner operation in the later transport stage.
            if (($marker['role'] ?? '') === 'connection') $allowed = $function !== 'InstallExtension';
            if (($marker['role'] ?? '') === 'legacy' && $function === 'GetActionCatalog') $allowed = true;
            if (!$allowed) return $this->OwnershipRejected($function);
            return $callback();
        } finally {
            $this->ownershipEntry = $nested;
            if (!$nested && !$receive) IPS_SemaphoreLeave($lock);
        }
    }

    private function TransportOwnershipBlocked(): bool
    {
        try {
            return in_array($this->OwnershipMarker()['role'] ?? '', ['legacy', 'manager'], true);
        }
        catch (Throwable) {
            return true;
        }
    }

    private function OwnershipRejected(string $function, bool $assetTimeout = false): mixed
    {
        $message = $assetTimeout
            ? 'Die Designerdatei konnte noch nicht geladen werden, weil ein anderer Aufruf länger läuft. Bitte den Designer erneut öffnen. Ihre Entwürfe bleiben erhalten.'
            : 'Diese Instanz ist für eine lokale Übernahme gesperrt oder gehört bereits zur zentralen Verwaltung. Bitte dort weiterarbeiten oder den gesicherten Rückweg verwenden.';
        try {
            $owner = $this->OwnershipMarker()['owner'] ?? 0;
            if ($owner > 0) $message .= ' Zentrale Verwaltungsinstanz: ' . $owner . '.';
        } catch (Throwable) { /* Keep the safe generic explanation for unreadable markers. */
        }
        $this->DebugEvent('FEHLER', $message, ['Aufruf' => $function]);
        if ($function === 'ProcessHookData') {
            http_response_code(409);
            echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
            return null;
        }
        if ($function === 'GetConfigurationForm') return json_encode(['elements' => [['type' => 'Label', 'caption' => $message]], 'actions' => []], JSON_UNESCAPED_UNICODE);
        return match ((string) (new \ReflectionMethod($this, $function))->getReturnType()) {
            'void'  => null, 'bool' => false,
            default => json_encode(['ok' => false, 'error' => 'ownership_conflict', 'message' => $message], JSON_UNESCAPED_UNICODE)
        };
    }

    private function WriteOwnership(array $marker): void
    {
        $this->WriteAttributeString('SystemOwnership', $marker === [] ? '' : json_encode($marker, JSON_THROW_ON_ERROR));
    }

    private function QuiesceMigrationSource(): void
    {
        foreach (['RequestTimeout', 'RuntimeDebugExpiry', 'ExecutionRefresh', 'StatusRefresh'] as $timer) $this->SetTimerInterval($timer, 0);
        foreach (['DesignerSessions', 'TransportState', 'RuntimeDebug', 'AutomaticStatusRefresh'] as $buffer) $this->SetBuffer($buffer, '');
        $this->SetReceiveDataFilter(Z2MProtocol::BLOCK_RECEIVE_FILTER);
    }
}
