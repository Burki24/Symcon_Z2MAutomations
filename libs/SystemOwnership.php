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
    private bool $workspaceFormReloadRequested = false;
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
        if (in_array($function, ['Create', 'GetSystemOwnership', 'GetSystemMigrationAuthority', 'GetWorkspaceTransferAuthority', 'GetWorkspaceSetupAuthority', 'GetWorkspaceRuntimeAuthority', 'GetWorkspaceInstallationAuthority', 'GetCompatibleParents'], true)) return $callback();
        $nested = $this->ownershipEntry;
        // Replies may arrive while SendDataToParent is still on another entry's stack.
        $receive = $function === 'ReceiveData';
        $lock = 'Z2MA.Owner.' . $this->InstanceID;
        if (!$nested && !$receive && !IPS_SemaphoreEnter($lock, 0)) {
            $asset = $function === 'ProcessHookData' && method_exists($this, 'DesignerAssetRequest') && $this->DesignerAssetRequest();
            if (!$asset) return $this->OwnershipBusy($function);
            $this->DebugEvent('AKTION', 'Paralleler Designer-Dateiaufruf wartet kurz auf den laufenden Aufruf. Entwürfe und Z2M bleiben unverändert.', ['HöchstensMillisekunden' => 5000]);
            if (!IPS_SemaphoreEnter($lock, 5000)) return $this->OwnershipBusy($function, true);
            $this->DebugEvent('AKTION', 'Designer-Dateiaufruf nach der Wartezeit freigegeben; Besitzerprüfung folgt unverändert.');
        }
        $this->ownershipEntry = $nested || !$receive;
        try {
            try {
                $marker = $this->OwnershipMarker();
            } catch (Throwable) {
                return $this->OwnershipRejected($function);
            }
            $import = in_array($function, ['ScanEventImports', 'PreviewEventImport', 'ImportSymconEvent', 'LoadImportDevices'], true);
            if (!$import && $this->workspaceEditorContext === null && method_exists($this, 'WorkspaceMode') && in_array($function, self::WORKSPACE_EDITOR_ACTIONS, true) && $this->WorkspaceMode()) return $this->OwnershipRejected($function);
            $migration = in_array($function, ['BackupSystemMigration', 'PrepareSystemMigration', 'ApplySystemMigration', 'RollbackSystemMigration', 'GetSystemMigration', 'ExportSystemMigration', 'FenceSystemMigration', 'GetSystemMigrationSource', 'PreviewSystemMigration', 'ClaimSystemConnection'], true);
            $workspace = str_contains($function, 'Workspace');
            $setup = method_exists($this, 'ReadWorkspaceSetup') && in_array($function, ['GetWorkspaceSetupStatus', 'RefreshWorkspaceSetup', 'ConfirmWorkspaceSetup', 'CancelWorkspaceSetup', 'RollbackWorkspaceSetup', 'GetConfigurationForm'], true);
            if (method_exists($this, 'WorkspaceSetupLocked') && $this->WorkspaceSetupLocked() && !$setup) return $this->OwnershipRejected($function);
            try {
                $editor = $this->workspaceEditorContext !== null && in_array($function, self::WORKSPACE_EDITOR_ACTIONS, true);
                $ui = $import || in_array($function, ['ApplyChanges', 'GetConfigurationForm', 'OpenAutomationDesigner', 'GetDesignerData', 'ProcessHookData', 'ChooseWorkspaceSystem', 'ApplyWorkspaceConnections', 'ReadWorkspaceCatalog'], true);
                $allowed = $marker === [] || $migration || ($marker['role'] === 'manager' && ($setup || (($workspace || $editor || $ui) && $this->MigrationWorkspaceWritable())));
            }
            catch (Throwable) {
                return $this->OwnershipRejected($function);
            }
            // Connection observations remain available; installation requires its separate owner-authorized entry.
            if (($marker['role'] ?? '') === 'connection') $allowed = $function !== 'InstallExtension';
            if (($marker['role'] ?? '') === 'legacy' && $function === 'GetActionCatalog') $allowed = true;
            if (!$allowed) return $this->OwnershipRejected($function);
            return $callback();
        } finally {
            $this->ownershipEntry = $nested;
            if (!$nested && !$receive) {
                IPS_SemaphoreLeave($lock);
                $this->FinishWorkspaceFormReload($function);
            }
        }
    }

    private function RequestWorkspaceFormReload(): void
    {
        if (!$this->workspaceFormReloadRequested) $this->DebugEvent('AKTION', 'Formularaktualisierung vorgemerkt. Erst den laufenden Aufruf abschließen und die Instanzsperre freigeben; danach die Anzeige neu aufbauen.');
        $this->workspaceFormReloadRequested = true;
    }

    private function FinishWorkspaceFormReload(string $function): void
    {
        if (!$this->workspaceFormReloadRequested) return;
        $this->workspaceFormReloadRequested = false;
        if ($function === 'GetConfigurationForm') {
            $this->DebugEvent('AKTION', 'Der laufende Formularaufbau enthält bereits den aktuellen Stand. Kein weiterer Neuaufbau erforderlich.');
            return;
        }
        try {
            $this->DebugEvent('AKTION', 'Instanzsperre freigegeben. Die vorgemerkte Formularaktualisierung wird jetzt an Symcon übergeben; Entwürfe und Z2M bleiben unverändert.');
            if (!$this->ReloadForm()) $this->DebugEvent('FEHLER', 'Symcon hat die Formularaktualisierung nicht bestätigt. Bitte das Formular erneut öffnen; der abgeschlossene Vorgang bleibt erhalten.');
        } catch (Throwable $exception) {
            $this->DebugEvent('FEHLER', 'Das Formular konnte nicht aktualisiert werden. Bitte erneut öffnen; der abgeschlossene Vorgang bleibt erhalten.', ['Ausnahmeklasse' => get_class($exception)]);
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

    private function OwnershipBusy(string $function, bool $assetTimeout = false): mixed
    {
        $message = $assetTimeout
            ? 'Die Designerdatei konnte noch nicht geladen werden, weil ein anderer Aufruf länger läuft. Bitte den Designer erneut öffnen. Ihre Entwürfe bleiben erhalten.'
            : 'Das Modul ist gerade beschäftigt. Ein anderer Vorgang läuft noch. Diese Aktion wurde nicht ausgeführt und wird nicht automatisch wiederholt. Bitte kurz warten und erneut versuchen.';
        return $this->OwnershipFailure($function, 'instance_busy', $message);
    }

    private function OwnershipRejected(string $function): mixed
    {
        $message = 'Diese Instanz ist für eine lokale Übernahme gesperrt oder gehört bereits zur zentralen Verwaltung. Bitte dort weiterarbeiten oder den gesicherten Rückweg verwenden.';
        try {
            $owner = $this->OwnershipMarker()['owner'] ?? 0;
            if ($owner > 0) $message .= ' Zentrale Verwaltungsinstanz: ' . $owner . '.';
        } catch (Throwable) { /* Keep the safe generic explanation for unreadable markers. */
        }
        return $this->OwnershipFailure($function, 'ownership_conflict', $message);
    }

    private function OwnershipFailure(string $function, string $error, string $message): mixed
    {
        $this->DebugEvent('FEHLER', $message, ['Aufruf' => $function, 'Grund' => $error]);
        if ($function === 'ProcessHookData') {
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => $error, 'message' => $message], JSON_UNESCAPED_UNICODE);
            return null;
        }
        if ($function === 'GetConfigurationForm') return json_encode(['elements' => [['type' => 'Label', 'caption' => $message]], 'actions' => []], JSON_UNESCAPED_UNICODE);
        return match ((string) (new \ReflectionMethod($this, $function))->getReturnType()) {
            'void'  => null, 'bool' => false,
            default => json_encode(['ok' => false, 'error' => $error, 'message' => $message], JSON_UNESCAPED_UNICODE)
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
