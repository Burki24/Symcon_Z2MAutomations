<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/WorkspaceSetupSnapshot.php';

/** Explicit read/preview/claim workflow for installations without legacy instances. */
trait WorkspaceSetup
{
    public function StartWorkspaceSetup(string $SystemID, int $Generation, string $Fingerprint, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $OperationID): string
        {
            try {
                $this->MigrationOperationID($OperationID);
                $job = $this->ReadWorkspaceSetup();
                if (($job['operation'] ?? '') === $OperationID) {
                    if ($job['binding']['systemId'] !== $SystemID || $job['fingerprint'] !== $Fingerprint || $job['binding']['generation'] !== $Generation) throw new RuntimeException('operation_conflict');
                    return $this->GetWorkspaceSetupStatus();
                }
                if ($job !== null && in_array($job['phase'], ['reading', 'preview', 'verifying', 'committing', 'rolling_back'], true)) throw new RuntimeException('setup_busy');
                if ($this->ReadAttributeString('YamlDraft') !== '' || $this->ReadAttributeString('EditorDraft') !== '') throw new RuntimeException('setup_legacy');
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                $this->CheckWorkspaceSetupTarget($record);
                $marker = $this->OwnershipMarker();
                if ($marker !== [] && (($marker['role'] ?? '') !== 'manager' || !$this->MigrationWorkspaceWritable())) throw new RuntimeException('setup_owner');
                $direct = $marker === [] || ($job !== null && $job['directOwner'] && $job['epoch'] === $marker['operation']);
                $epoch = $marker['operation'] ?? $OperationID;
                $job = ['version' => 1, 'operation' => $OperationID, 'epoch' => $epoch, 'directOwner' => $direct,
                    'ready'       => $marker !== [], 'beforeReady' => $marker !== [], 'beforeOwner' => $marker,
                    'binding'     => $record['binding'], 'fingerprint' => $Fingerprint, 'afterFingerprint' => '',
                    'phase'       => 'reading', 'error' => '', 'summary' => null, 'archiveChecksum' => ''];
                $this->WriteWorkspaceSetup($job);
                $started = true;
                $this->DebugEvent('SENDEN', 'Direkte Einrichtung gestartet: vorhandenen Z2M-Stand zunächst nur für eine Vorschau lesen. Lokale Entwürfe werden nicht ersetzt.', ['Systembezug' => $SystemID]);
                $reply = json_decode(Z2MC_RequestWorkspaceSnapshot($record['binding']['connectionInstance'], $this->InstanceID, $OperationID, false), true, 48, JSON_THROW_ON_ERROR);
                $this->AcceptWorkspaceSetupRead($reply);
                $this->SetTimerInterval('WorkspaceSetupRefresh', 1000);
                return $this->GetWorkspaceSetupStatus();
            } catch (Throwable $exception) {
                if ($started ?? false) $this->RememberWorkspaceSetupError($exception);
                return $this->WorkspaceSetupFailure($exception);
            }
        });
    }

    public function ConfirmWorkspaceSetup(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $job = $this->WorkspaceSetupOperation($OperationID);
                if ($job['phase'] === 'complete' || $job['phase'] === 'verifying' || $job['phase'] === 'committing') return $this->GetWorkspaceSetupStatus();
                if ($job['phase'] !== 'preview') throw new RuntimeException('setup_stale');
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $job['binding']['systemId'], $job['binding']['generation'], $job['fingerprint']);
                $this->CheckWorkspaceSetupTarget($record);
                $this->WorkspaceSetupArchive($job);
                $job['phase'] = 'verifying';
                $this->WriteWorkspaceSetup($job);
                $started = true;
                $reply = json_decode(Z2MC_RequestWorkspaceSnapshot($record['binding']['connectionInstance'], $this->InstanceID, $OperationID, true), true, 48, JSON_THROW_ON_ERROR);
                $this->AcceptWorkspaceSetupRead($reply);
                $this->SetTimerInterval('WorkspaceSetupRefresh', 1000);
                return $this->GetWorkspaceSetupStatus();
            } catch (Throwable $exception) {
                if ($started ?? false) $this->RememberWorkspaceSetupError($exception);
                return $this->WorkspaceSetupFailure($exception);
            }
        });
    }

    public function CancelWorkspaceSetup(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $job = $this->WorkspaceSetupOperation($OperationID);
                if (!in_array($job['phase'], ['reading', 'preview', 'verifying', 'error', 'cancelled'], true)) throw new RuntimeException('setup_stale');
                $job['phase'] = 'cancelled';
                $job['error'] = '';
                $this->WriteWorkspaceSetup($job);
                Z2MC_CancelWorkspaceSnapshot($job['binding']['connectionInstance'], $this->InstanceID, $OperationID);
                $this->SetTimerInterval('WorkspaceSetupRefresh', 0);
                $this->DebugEvent('AKTION', 'Einrichtung vor dem Besitzerwechsel verworfen. Entwürfe, Besitzer und Z2M bleiben unverändert.');
                return $this->GetWorkspaceSetupStatus();
            } catch (Throwable $exception) {
                return $this->WorkspaceSetupFailure($exception);
            }
        });
    }

    public function RollbackWorkspaceSetup(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $job = $this->WorkspaceSetupOperation($OperationID);
                if ($job['phase'] === 'rolled_back') return $this->GetWorkspaceSetupStatus();
                if (!in_array($job['phase'], ['complete', 'committing', 'rolling_back'], true)) throw new RuntimeException('setup_stale');
                if ($this->WorkspaceTransferBlocksRollback($job['epoch'])) throw new RuntimeException('z2m_write_started');
                $this->WorkspaceSetupArchive($job);
                if ($job['phase'] !== 'rolling_back') {
                    $record = $this->WorkspaceRecord($this->ReadWorkspace(), $job['binding']['systemId']);
                    if ($record['binding'] !== $job['binding']) throw new RuntimeException('binding_changed');
                    $this->MigrationArchive()->Write($OperationID . '-direct-return', ['record' => $record], true);
                    $job['rollbackFingerprint'] = WorkspaceState::Fingerprint($record);
                    $job['phase'] = 'rolling_back';
                    $job['error'] = '';
                    $this->WriteWorkspaceSetup($job);
                }
                $this->RestoreWorkspaceSetup($job);
                return $this->GetWorkspaceSetupStatus();
            } catch (Throwable $exception) {
                return $this->WorkspaceSetupFailure($exception);
            }
        });
    }

    public function GetWorkspaceSetupAuthority(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $job = $this->WorkspaceSetupOperation($OperationID);
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $job['binding']['systemId']);
                $fingerprint = WorkspaceState::Fingerprint($record);
                $marker = $this->OwnershipMarker();
                if ($record['binding'] !== $job['binding'] || ($job['phase'] !== 'cancelled' && !in_array($fingerprint, [$job['fingerprint'], $job['afterFingerprint']], true))
                    || ($marker !== [] && $marker !== ['version' => 1, 'role' => 'manager', 'owner' => $this->InstanceID, 'operation' => $job['epoch']])
                    || !in_array($job['phase'], ['reading', 'verifying', 'committing', 'rolling_back', 'cancelled'], true)) throw new RuntimeException('setup_stale');
                return json_encode(['ok' => true, 'phase' => $job['phase'], 'epoch' => $job['epoch'], 'binding' => $job['binding']], WorkspaceState::JSON_FLAGS);
            } catch (Throwable) {
                return '{"ok":false}';
            }
        });
    }

    public function GetWorkspaceSetupStatus(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            try {
                $this->SyncWorkspaceSetup();
                $job = $this->ReadWorkspaceSetup();
                $phase = $job['phase'] ?? 'idle';
                $message = ($job['error'] ?? '') !== '' ? $this->WorkspaceSetupError($job['error']) : match ($phase) {
                    'reading'      => 'Gespeicherte Automationen und Gerätedaten werden aus diesem Z2M-System gelesen. Es wird nichts gespeichert oder aktiviert.',
                    'preview'      => 'Vorschau und private Sicherung sind bereit. Zum Einrichten die Verbindung ausdrücklich übernehmen. Z2M läuft unverändert weiter.',
                    'verifying'    => 'Z2M-Stand wird vor der lokalen Übernahme erneut bestätigt. Bitte die Antwort abwarten.',
                    'committing'   => 'Lokale Übernahme noch nicht abgeschlossen. Denselben Vorgang fortsetzen oder den gesicherten Rückweg wählen.',
                    'complete'     => 'Verbindung übernommen und gespeicherte Automationen im lokalen Entwurf eingerichtet. Z2M-Datei und laufender Betrieb wurden nicht verändert.',
                    'rolling_back' => 'Der lokale Rückweg ist noch nicht abgeschlossen. Bitte denselben Rückweg fortsetzen.',
                    'rolled_back'  => 'Lokaler Rückweg abgeschlossen. Spätere Entwurfsänderungen bleiben zusätzlich privat gesichert; Z2M wurde nicht verändert.',
                    'cancelled'    => 'Einrichtung verworfen. Besitzer und vorhandene Entwürfe bleiben unverändert.',
                    default        => 'Zuerst das gewünschte System auswählen und dessen Z2M-Automationen sicher einlesen.'
                };
                $view = ['operation' => $job['operation'] ?? '', 'systemId' => $job['binding']['systemId'] ?? '', 'baseTopic' => $job['binding']['baseTopic'] ?? '', 'phase' => $phase,
                    'message'        => $message, 'summary' => $job['summary'] ?? null,
                    'canConfirm'     => $phase === 'preview', 'canCancel' => in_array($phase, ['reading', 'preview', 'verifying', 'error'], true),
                    'canRollback'    => $job !== null && in_array($phase, ['complete', 'committing', 'rolling_back'], true) && !$this->WorkspaceTransferBlocksRollback($job['epoch'])];
                $this->SetTimerInterval('WorkspaceSetupRefresh', in_array($phase, ['reading', 'verifying'], true) ? 1000 : 0);
                $this->UpdateFormField('WorkspaceSetupInfo', 'caption', $message);
                $this->UpdateFormField('WorkspaceSetupOperation', 'value', $view['operation']);
                $same = $view['systemId'] === $this->ReadWorkspace()['selectedSystem'];
                $this->UpdateFormField('WorkspaceSetupConfirm', 'enabled', $same && $view['canConfirm']);
                $this->UpdateFormField('WorkspaceSetupCancel', 'enabled', $view['canCancel']);
                $this->UpdateFormField('WorkspaceSetupRollback', 'enabled', $same && $view['canRollback']);
                $this->UpdateFormField('WorkspaceSetupSummary', 'caption', $this->WorkspaceSetupSummary($view));
                $selected = $this->ReadWorkspace()['selectedSystem'];
                $selectedRecord = $this->ReadWorkspace()['systems'][$selected] ?? null;
                $this->UpdateFormField('WorkspaceSetupRead', 'enabled', $selectedRecord !== null && $selectedRecord['data'] === WorkspaceState::Data()
                    && !in_array($phase, ['reading', 'preview', 'verifying', 'committing', 'rolling_back'], true));
                return $this->WorkspaceResult('', ['setup' => $view]);
            } catch (Throwable $exception) {
                return $this->WorkspaceSetupFailure($exception);
            }
        });
    }

    public function RefreshWorkspaceSetup(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        { $this->GetWorkspaceSetupStatus(); });
    }

    protected function WorkspaceSetupCheckpoint(string $Step): void
    {
    }

    private function RestoreWorkspaceSetup(array $job): void
    {
        if ($this->WorkspaceTransferBlocksRollback($job['epoch'])) throw new RuntimeException('z2m_write_started');
        $archive = $this->WorkspaceSetupArchive($job);
        $backup = $this->MigrationArchive()->Read($job['operation'] . '-direct-return');
        if (WorkspaceState::Fingerprint($backup['record']) !== $job['rollbackFingerprint']) throw new RuntimeException('archive_failed');
        $workspace = $this->ReadWorkspace();
        $record = $this->WorkspaceRecord($workspace, $job['binding']['systemId']);
        if (!in_array(WorkspaceState::Fingerprint($record), [$job['rollbackFingerprint'], $job['fingerprint']], true)) throw new RuntimeException('setup_stale');
        $workspace['systems'][$job['binding']['systemId']] = $archive['beforeRecord'];
        if (WorkspaceState::Fingerprint($record) !== $job['fingerprint']) $this->WriteWorkspace($workspace);
        $this->WorkspaceSetupCheckpoint('restored');
        $this->MigrationAck(Z2MC_ClaimWorkspaceConnection($job['binding']['connectionInstance'], $this->InstanceID, $job['operation'], json_encode($job['binding'], WorkspaceState::JSON_FLAGS), true));
        $this->WorkspaceSetupCheckpoint('released');
        $this->WriteOwnership($job['beforeOwner']);
        $job['ready'] = $job['beforeReady'];
        $job['phase'] = 'rolled_back';
        $job['error'] = '';
        $this->WriteWorkspaceSetup($job);
        $this->DebugEvent('AKTION', 'Direkte Einrichtung lokal zurückgenommen. Spätere Entwurfsänderungen bleiben privat gesichert. Andere Systeme und der Z2M-Betrieb bleiben erhalten.', ['Systembezug' => $job['binding']['systemId']]);
        $this->RequestWorkspaceFormReload();
    }

    private function SyncWorkspaceSetup(): void
    {
        $job = $this->ReadWorkspaceSetup();
        if ($job === null) return;
        try {
            if ($job['phase'] === 'committing') {
                $this->CommitWorkspaceSetup($job);
                return;
            }
            if ($job['phase'] === 'rolling_back') {
                $this->RestoreWorkspaceSetup($job);
                return;
            }
            if (!in_array($job['phase'], ['reading', 'verifying', 'preview'], true)) return;
            if (!$this->WorkspaceConnectionCurrent($job['binding'])) throw new RuntimeException('binding_changed');
            $this->WorkspaceRecord($this->ReadWorkspace(), $job['binding']['systemId'], $job['binding']['generation'], $job['fingerprint']);
            if ($job['phase'] === 'preview') return;
            $reply = json_decode(Z2MC_GetWorkspaceSnapshotResult($job['binding']['connectionInstance'], $this->InstanceID, $job['operation']), true, 48, JSON_THROW_ON_ERROR);
            $this->AcceptWorkspaceSetupRead($reply);
        } catch (Throwable $exception) {
            $job = $this->ReadWorkspaceSetup();
            if (!in_array($job['phase'], ['committing', 'rolling_back'], true)) $job['phase'] = 'error';
            $job['error'] = $this->WorkspaceSetupErrorCode($exception);
            $this->WriteWorkspaceSetup($job);
            $this->DebugEvent('FEHLER', $this->WorkspaceSetupError($job['error']));
        }
    }

    private function AcceptWorkspaceSetupRead(array $reply): void
    {
        $job = $this->ReadWorkspaceSetup();
        if (($reply['ok'] ?? false) !== true) throw new RuntimeException(in_array($reply['error'] ?? '', ['busy', 'not_connected', 'setup_stale'], true) ? $reply['error'] : 'setup_interrupted');
        if ($reply['owner'] !== $this->InstanceID || $reply['operation'] !== $job['operation'] || $reply['binding'] !== $job['binding']) throw new RuntimeException('setup_stale');
        if ($reply['status'] === 'pending') return;
        if ($reply['status'] !== ($job['phase'] === 'reading' ? 'ready' : 'verified')) throw new RuntimeException($reply['error'] !== '' ? $reply['error'] : 'setup_interrupted');
        if ($job['phase'] === 'verifying') {
            $record = $this->WorkspaceRecord($this->ReadWorkspace(), $job['binding']['systemId'], $job['binding']['generation'], $job['fingerprint']);
            $this->CheckWorkspaceSetupTarget($record);
            $candidate = $this->WorkspaceSetupArchive($job)['candidate'];
            $catalog = $this->WorkspaceCatalog($job['binding']);
            if ($catalog === null || WorkspaceState::Fingerprint($catalog) !== WorkspaceState::Fingerprint($candidate['catalog'])) throw new RuntimeException('setup_stale');
            $job['summary']['executing'] = $reply['snapshot']['metadata']['executing'];
            $job['phase'] = 'committing';
            $this->WriteWorkspaceSetup($job);
            $this->CommitWorkspaceSetup($job);
            return;
        }
        $workspace = $this->ReadWorkspace();
        $record = $this->WorkspaceRecord($workspace, $job['binding']['systemId'], $job['binding']['generation'], $job['fingerprint']);
        $this->CheckWorkspaceSetupTarget($record);
        $snapshot = $reply['snapshot'];
        if (!is_array($snapshot) || WorkspaceState::DataError($snapshot['data'] ?? null) !== '') throw new RuntimeException('invalid_response');
        $candidate = $record;
        $candidate['data'] = $snapshot['data'];
        $catalog = $this->WorkspaceCatalog($record['binding']);
        if ($catalog === null || $catalog['loadedAt'] !== $snapshot['catalog']['loadedAt'] || $catalog['devices'] !== $snapshot['catalog']['devices']
            || $catalog['groups'] !== $snapshot['catalog']['groups'] || $catalog['scenes'] !== $snapshot['catalog']['scenes']) throw new RuntimeException('setup_stale');
        $candidate['catalog'] = $catalog;
        $candidate['nativeBindings'] = $this->WorkspaceNativeBindings($record['binding']);
        $candidate['ownershipEpoch'] = $job['epoch'];
        $check = $workspace;
        $check['systems'][$record['binding']['systemId']] = $candidate;
        if (WorkspaceState::Error($check) !== '') throw new RuntimeException('invalid_data');
        $archive = ['version' => 1, 'owner' => $this->InstanceID, 'operation' => $job['operation'], 'binding' => $job['binding'],
            'beforeWorkspace' => $workspace, 'beforeRecord' => $record, 'candidate' => $candidate, 'beforeOwner' => $job['beforeOwner']];
        $this->MigrationArchive()->Write($job['operation'] . '-direct', $archive, true);
        $job['archiveChecksum'] = WorkspaceState::Fingerprint($archive);
        $job['afterFingerprint'] = WorkspaceState::Fingerprint($candidate);
        $configuration = $candidate['data']['document']['editorDraft']['configuration'];
        $job['summary'] = ['revision' => $snapshot['metadata']['storedRevision'], 'rules' => count($configuration['automations']),
            'sequences'               => count($configuration['sequences'] ?? []), 'peers' => count($configuration['mesh_peers'] ?? []),
            'empty'                   => $snapshot['empty'], 'executing' => $snapshot['metadata']['executing']];
        $job['phase'] = 'preview';
        $this->WriteWorkspaceSetup($job);
        $this->DebugEvent('AKTION', 'Vorschau vollständig privat gesichert. Erst die Bestätigung übernimmt Verbindung und lokalen Entwurf; vorhandene Regeln bleiben in Z2M unverändert.', ['Systembezug' => $job['binding']['systemId'], 'Automationen' => $job['summary']['rules'], 'Revision' => $job['summary']['revision']]);
        $this->WorkspaceSetupCheckpoint('preview');
    }

    private function CommitWorkspaceSetup(array $job): void
    {
        $archive = $this->WorkspaceSetupArchive($job);
        $workspace = $this->ReadWorkspace();
        $record = $this->WorkspaceRecord($workspace, $job['binding']['systemId']);
        if (!in_array(WorkspaceState::Fingerprint($record), [$job['fingerprint'], $job['afterFingerprint']], true) || !$this->WorkspaceConnectionCurrent($job['binding'])) throw new RuntimeException('setup_stale');
        try {
            $this->MigrationAck(Z2MC_ClaimWorkspaceConnection($job['binding']['connectionInstance'], $this->InstanceID, $job['operation'], json_encode($job['binding'], WorkspaceState::JSON_FLAGS), false));
        } catch (Throwable $exception) {
            $owner = json_decode(Z2MC_GetSystemOwnership($job['binding']['connectionInstance']), true, 16, JSON_THROW_ON_ERROR);
            if (($owner['owner'] ?? 0) !== $this->InstanceID) {
                $job['phase'] = 'error';
                $job['error'] = $owner === [] ? 'ownership_conflict' : 'setup_owner';
                $this->WriteWorkspaceSetup($job);
            }
            throw $exception;
        }
        $this->WorkspaceSetupCheckpoint('claimed');
        $this->WriteOwnership(['version' => 1, 'role' => 'manager', 'owner' => $this->InstanceID, 'operation' => $job['epoch']]);
        $this->WorkspaceSetupCheckpoint('owner');
        $workspace['systems'][$job['binding']['systemId']] = $archive['candidate'];
        $this->WriteWorkspace($workspace);
        $this->WorkspaceSetupCheckpoint('published');
        $job['phase'] = 'complete';
        $job['ready'] = true;
        $job['error'] = '';
        $this->WriteWorkspaceSetup($job);
        $this->DebugEvent('AKTION', 'Direkte Einrichtung abgeschlossen. Verbindung gehört dieser Zentrale; gespeicherte Regeln und Partnerfreigaben liegen unverändert im lokalen Entwurf. Keine Z2M-Schreibanfrage.', ['Systembezug' => $job['binding']['systemId']]);
        $this->RequestWorkspaceFormReload();
    }

    private function CheckWorkspaceSetupTarget(array $record): void
    {
        if ($record['data'] !== WorkspaceState::Data()) throw new RuntimeException('setup_has_draft');
        if (!$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
        $owner = json_decode(Z2MC_GetSystemOwnership($record['binding']['connectionInstance']), true, 16, JSON_THROW_ON_ERROR);
        if ($owner !== []) throw new RuntimeException('setup_owner');
        foreach (IPS_GetInstanceListByModuleID(SystemContract::SOURCE_MODULE) as $id) {
            if ($id === $this->InstanceID || IPS_GetInstance($id)['ConnectionID'] !== $record['binding']['mqttParent']) continue;
            if (IPS_GetProperty($id, 'BaseTopic') !== $record['binding']['baseTopic']) continue;
            $summary = json_decode(Z2MA_GetSystemMigrationSource($id), true, 24, JSON_THROW_ON_ERROR);
            if (!isset($summary['role'], $summary['rules'])) throw new RuntimeException('setup_owner');
            if ($summary['role'] !== 'unassigned' || $summary['rules'] > 0) throw new RuntimeException('setup_legacy');
        }
    }

    private function WorkspaceSetupOperation(string $operation): array
    {
        $job = $this->ReadWorkspaceSetup();
        if ($job === null || $job['operation'] !== $operation) throw new RuntimeException('setup_stale');
        return $job;
    }

    private function ReadWorkspaceSetup(): ?array
    {
        $raw = $this->ReadAttributeString('WorkspaceSetup');
        if ($raw === '') return null;
        $job = strlen($raw) <= 32768 ? json_decode($raw, true, 24, JSON_THROW_ON_ERROR) : null;
        if (!is_array($job) || ($job['version'] ?? null) !== 1 || !DraftTransferProtocol::Operation($job['operation'] ?? null) || !DraftTransferProtocol::Operation($job['epoch'] ?? null)
            || !is_array($job['binding'] ?? null) || SystemContract::RegistryError([$job['binding']]) !== '' || $job['binding']['ownerInstance'] !== $this->InstanceID
            || !ExtensionContract::Checksum($job['fingerprint'] ?? null) || !is_array($job['beforeOwner'] ?? null)
            || !is_bool($job['directOwner'] ?? null) || !is_bool($job['ready'] ?? null) || !is_bool($job['beforeReady'] ?? null)
            || !in_array($job['phase'] ?? null, ['reading', 'preview', 'verifying', 'committing', 'complete', 'rolling_back', 'rolled_back', 'cancelled', 'error'], true)
            || !is_string($job['error'] ?? null) || !is_string($job['afterFingerprint'] ?? null) || !is_string($job['archiveChecksum'] ?? null)) throw new RuntimeException('setup_unavailable');
        return $job;
    }

    private function WriteWorkspaceSetup(array $job): void
    {
        $this->WriteAttributeString('WorkspaceSetup', json_encode($job, WorkspaceState::JSON_FLAGS));
    }

    private function WorkspaceSetupLocked(): bool
    {
        $job = $this->ReadWorkspaceSetup();
        return $job !== null && in_array($job['phase'], ['committing', 'rolling_back'], true);
    }

    private function DirectWorkspaceOwnerReady(array $marker): ?bool
    {
        $job = $this->ReadWorkspaceSetup();
        return $job !== null && $job['directOwner'] && $job['epoch'] === $marker['operation'] ? $job['ready'] : null;
    }

    private function WorkspaceSetupArchive(array $job): array
    {
        $archive = $this->MigrationArchive()->Read($job['operation'] . '-direct');
        if (!hash_equals($job['archiveChecksum'], WorkspaceState::Fingerprint($archive)) || $archive['owner'] !== $this->InstanceID
            || $archive['binding'] !== $job['binding'] || $archive['operation'] !== $job['operation']) throw new RuntimeException('archive_failed');
        return $archive;
    }

    private function WorkspaceSetupSummary(array $view): string
    {
        $summary = $view['summary'];
        return $summary === null ? '' : 'Vorschau für „' . $view['baseTopic'] . '“: ' . ($summary['empty'] ? 'In Z2M sind noch keine Automationen gespeichert. Ein leerer lokaler Entwurf wird vorbereitet.'
            : sprintf('Gespeicherter Stand: Revision %d, %d Automationen, %d Aktionsfolgen und %d Partnerfreigaben. Betrieb in Z2M: %s.', $summary['revision'], $summary['rules'], $summary['sequences'], $summary['peers'], $summary['executing'] ? 'aktiv' : 'gestoppt'));
    }

    private function WorkspaceSetupPanel(array $record, array $view): array
    {
        $system = $record['binding']['systemId'];
        $same = $view['systemId'] === $system;
        $busy = in_array($view['phase'], ['reading', 'preview', 'verifying', 'committing', 'rolling_back'], true);
        $callback = ' $reply = json_decode(%s, true); if (!($reply["ok"] ?? false)) echo $reply["message"] ?? "Einrichtung nicht bestätigt. Bitte Status und Debug prüfen.";';
        return ['type' => 'ExpansionPanel', 'caption' => 'Direkt aus Z2M einrichten', 'expanded' => $record['data']['document'] === null || $busy, 'items' => [
            ['type' => 'Label', 'caption' => 'Ausgewähltes System: ' . $record['name'] . ' · ' . $record['binding']['baseTopic']],
            ['type' => 'Label', 'caption' => 'Zuerst vorhandene Automationen und Gerätedaten nur lesen. Danach Vorschau prüfen und die Verbindung ausdrücklich übernehmen. Keine alte Einzelinstanz nötig. Vorhandene lokale Entwürfe bleiben geschützt. Z2M-Datei und Betrieb werden nicht verändert.'],
            ['type'       => 'Button', 'name' => 'WorkspaceSetupRead', 'caption' => 'Z2M-Automationen sicher einlesen', 'enabled' => !$busy && $record['data'] === WorkspaceState::Data(),
                'onClick' => sprintf($callback, 'Z2MA_StartWorkspaceSetup($id, ' . var_export($system, true) . ', ' . $record['binding']['generation'] . ', $WorkspaceEditorFingerprint, bin2hex(random_bytes(16)))')],
            ['type' => 'Label', 'name' => 'WorkspaceSetupInfo', 'caption' => !$same && $busy ? 'Für ein anderes System läuft eine Einrichtung. Diese zuerst bestätigen oder verwerfen.' : $view['message']],
            ['type' => 'Label', 'name' => 'WorkspaceSetupSummary', 'caption' => $this->WorkspaceSetupSummary($view)],
            ['type' => 'ValidationTextBox', 'name' => 'WorkspaceSetupOperation', 'value' => $view['operation'], 'visible' => false],
            ['type'       => 'Button', 'name' => 'WorkspaceSetupConfirm', 'caption' => 'Verbindung übernehmen und Entwurf einrichten', 'enabled' => $same && $view['canConfirm'],
                'confirm' => 'Verbindung für „' . $record['binding']['baseTopic'] . '“ exklusiv dieser Zentrale zuordnen und den gelesenen Stand lokal übernehmen? Z2M bleibt unverändert.',
                'onClick' => sprintf($callback, 'Z2MA_ConfirmWorkspaceSetup($id, $WorkspaceSetupOperation)')],
            ['type'       => 'Button', 'name' => 'WorkspaceSetupCancel', 'caption' => 'Vorschau verwerfen', 'enabled' => $view['canCancel'],
                'onClick' => sprintf($callback, 'Z2MA_CancelWorkspaceSetup($id, $WorkspaceSetupOperation)')],
            ['type' => 'Button', 'caption' => 'Einrichtungsstatus aktualisieren oder fortsetzen', 'onClick' => 'Z2MA_GetWorkspaceSetupStatus($id);'],
            ['type'       => 'Button', 'name' => 'WorkspaceSetupRollback', 'caption' => 'Letzte Einrichtung lokal zurücknehmen', 'enabled' => $same && $view['canRollback'],
                'confirm' => 'Letzte lokale Einrichtung zurücknehmen? Spätere lokale Änderungen werden vorher zusätzlich privat gesichert. In Z2M wird nichts gespeichert oder aktiviert.',
                'onClick' => sprintf($callback, 'Z2MA_RollbackWorkspaceSetup($id, $WorkspaceSetupOperation)')]]];
    }

    private function WorkspaceSetupError(string $error): string
    {
        return match ($error) {
            'setup_has_draft'                                                      => 'Für dieses System gibt es bereits einen lokalen Entwurf oder offene Eingaben. Diese werden nicht durch den Z2M-Stand ersetzt.',
            'setup_owner'                                                          => 'Diese Verbindung gehört bereits einer Verwaltung. Ein bestehender Besitzer wird nicht überschrieben.',
            'setup_busy'                                                           => 'Für die Zentrale läuft noch eine Einrichtung. Bitte diese bestätigen, verwerfen oder den Rückweg abschließen.',
            'setup_legacy'                                                         => 'Für dieses System existiert noch eine bisherige Einzelinstanz. Deren Entwurf bleibt erhalten; dafür den gesicherten Übernahmeablauf für Einzelinstanzen verwenden.',
            'storage_unavailable'                                                  => 'Der Automationsspeicher in Z2M ist gesperrt oder muss repariert werden. Der vorhandene Stand wird nicht durch einen leeren Entwurf ersetzt. Bitte zuerst den Z2M-Speicher prüfen.',
            'not_ready'                                                            => 'Die Z2M-Erweiterung ist noch nicht zum Einlesen bereit. Bitte ihren Status im Verbindungsmodul prüfen.',
            'setup_stale', 'stale_system', 'revision_conflict', 'session_mismatch' => 'Entwurf, Z2M-Stand oder Zuordnung wurde inzwischen geändert. Bitte die Vorschau neu einlesen. Es wird nichts ersetzt.',
            'setup_interrupted', 'timeout', 'send_failed', 'cancelled'             => 'Das Einlesen wurde unterbrochen oder nicht rechtzeitig bestätigt. Es wird nichts automatisch erneut gesendet. Bitte verwerfen und bei Bedarf neu einlesen.',
            'invalid_response', 'invalid_data', 'protocol_mismatch'                => 'Der gelesene Z2M-Stand ist unvollständig oder passt nicht zum erwarteten Format. Es wird kein leerer Entwurf als Ersatz übernommen. Bitte Erweiterung und Debug prüfen.',
            default                                                                => WorkspaceState::MESSAGES[$error] ?? self::MIGRATION_MESSAGES[$error] ?? 'Die Einrichtung konnte nicht sicher abgeschlossen werden. Vorhandene Daten bleiben erhalten; bitte Status, Sicherung und Debug prüfen.'
        };
    }

    private function WorkspaceSetupFailure(Throwable $exception): string
    {
        $error = $this->WorkspaceSetupErrorCode($exception);
        $message = $this->WorkspaceSetupError($error);
        $this->DebugEvent('FEHLER', $message);
        return json_encode(['version' => 1, 'ok' => false, 'error' => isset(WorkspaceState::MESSAGES[$error]) || in_array($error, ['setup_has_draft', 'setup_owner', 'setup_busy', 'setup_legacy', 'setup_stale'], true) ? $error : 'setup_unavailable', 'message' => $message], WorkspaceState::JSON_FLAGS);
    }

    private function WorkspaceSetupErrorCode(Throwable $exception): string
    {
        $code = $exception instanceof RuntimeException ? $exception->getMessage() : '';
        return $code !== '' && (isset(WorkspaceState::MESSAGES[$code]) || isset(self::MIGRATION_MESSAGES[$code]) || in_array(
            $code,
            ['setup_stale', 'setup_unavailable', 'setup_has_draft', 'setup_owner', 'setup_busy', 'setup_legacy', 'setup_interrupted',
                'timeout', 'send_failed', 'cancelled', 'invalid_response', 'protocol_mismatch', 'storage_unavailable', 'not_ready', 'session_mismatch', 'revision_conflict'],
            true
        ))
            ? $code : 'setup_unavailable';
    }

    private function RememberWorkspaceSetupError(Throwable $exception): void
    {
        $job = $this->ReadWorkspaceSetup();
        if ($job === null) return;
        if (in_array($job['phase'], ['reading', 'verifying'], true)) $job['phase'] = 'error';
        $job['error'] = $this->WorkspaceSetupErrorCode($exception);
        $this->WriteWorkspaceSetup($job);
        $this->SetTimerInterval('WorkspaceSetupRefresh', 0);
    }
}
