<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/MigrationArchive.php';
require_once __DIR__ . '/MigrationSource.php';

/** Explicit local migration. No Z2M command and no automatic conversion on update. */
trait SystemMigration
{
    use MigrationSource;

    private const MIGRATION_MESSAGES = [
        'invalid_request'       => 'Die Angaben für die Übernahme sind ungültig. Bitte Quellen und Verbindungen neu auswählen.',
        'archive_failed'        => 'Die private Sicherung konnte nicht sicher geschrieben oder geprüft werden. Bitte Speicherplatz und Dateirechte prüfen; die Übernahme bleibt gesperrt.',
        'archive_conflict'      => 'Diese Sicherungskennung gehört bereits zu anderen Daten. Die vorhandene Sicherung wird nicht überschrieben.',
        'ownership_conflict'    => 'Die Instanz oder Verbindung ist bereits belegt oder gerade in Bearbeitung. Bitte den bestehenden Übernahmevorgang prüfen.',
        'source_changed'        => 'Ein Quellstand hat sich seit der Vorschau geändert. Bitte neu prüfen. Vorhandene Sicherungen und Entwürfe bleiben erhalten.',
        'source_blocked'        => 'Die Quelle enthält offene Eingaben oder widersprüchliche Daten. Bitte diese im bisherigen Modul klären und eine neue Vorschau erstellen.',
        'target_changed'        => 'Der zentrale Arbeitsbereich wurde zwischenzeitlich geändert. Er wird nicht überschrieben. Bitte den Übernahmebericht prüfen.',
        'binding_changed'       => 'Die MQTT-Verbindung, das Basistopic oder die Verbindungsgeneration wurde geändert. Bitte die Zuordnung prüfen.',
        'manager_not_empty'     => 'Bitte eine eigene zentrale Verwaltungsinstanz ohne bisherigen Einzelentwurf verwenden. Vorhandene Entwürfe werden nicht ersetzt.',
        'migration_interrupted' => 'Die lokale Übernahme wurde unterbrochen. Bitte denselben Vorgang fortsetzen oder den gesicherten Rückweg wählen. Z2M wurde nicht verändert.',
        'z2m_write_started'     => 'Für dieses System wurde eine Speicherung, Betriebssteuerung oder Erweiterungsinstallation beauftragt. Der lokale Rückweg stellt den Z2M-Betrieb nicht wieder her. Bitte zuerst den tatsächlichen Z2M-Stand prüfen und eine gesonderte Wiederherstellung planen.',
        'rollback_conflict'     => 'Die bisherige Quelle wurde nach der Übernahme verändert. Der Rückweg bleibt gesperrt, damit keine Änderungen verloren gehen. Sicherung und zentraler Stand bleiben erhalten.'
    ];

    public function BackupSystemMigration(string $SourceInstanceIDs, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SourceInstanceIDs, $OperationID): string
        {
            try {
                $this->MigrationOperationID($OperationID);
                $ids = $this->DecodeWorkspaceInput($SourceInstanceIDs, 1024);
                if (!array_is_list($ids) || count($ids) < 1 || count($ids) > 8 || count(array_unique($ids, SORT_REGULAR)) !== count($ids)) throw new RuntimeException('invalid_request');
                $archive = $this->MigrationArchive();
                $manifest = ['version' => 1, 'owner' => $this->InstanceID, 'operation' => $OperationID, 'sources' => []];
                foreach ($ids as $id) {
                    $export = $this->MigrationExport($id);
                    $archive->Write($OperationID . '-backup-' . $id, $export, true);
                    $manifest['sources'][] = ['instance' => $id, 'checksum' => WorkspaceState::Fingerprint($export), 'fingerprint' => $export['summary']['fingerprint']];
                }
                $archive->Write($OperationID . '-backup', $manifest, true);
                $this->DebugEvent('AKTION', 'Private Sicherung aller ausgewählten Quellstände geschrieben und anhand der Prüfsummen erneut gelesen. Auch offene Eingaben bleiben gesichert; nichts umgeschaltet.', ['Quellen' => count($ids)]);
                return $this->MigrationResult(['operation' => $OperationID, 'phase' => 'backed_up', 'sources' => count($ids)]);
            } catch (Throwable $exception) {
                return $this->MigrationFailure($exception);
            }
        });
    }

    public function PrepareSystemMigration(string $Mapping, string $WorkspaceFingerprint, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Mapping, $WorkspaceFingerprint, $OperationID): string
        {
            try {
                $this->MigrationOperationID($OperationID);
                $request = WorkspaceState::Fingerprint([$Mapping, $WorkspaceFingerprint]);
                $marker = $this->OwnershipMarker();
                $archive = $this->MigrationArchive();
                if ($archive->Exists($OperationID . '-journal')) {
                    $journal = $this->ReadMigrationJournal($OperationID);
                    if ($journal['request'] !== $request) throw new RuntimeException('archive_conflict');
                    // A completed operation identifier can never create a new owner epoch.
                    return $this->MigrationReport($journal);
                }
                if ($marker !== []) {
                    throw new RuntimeException('ownership_conflict');
                }
                if ($this->ReadAttributeString('YamlDraft') !== '' || $this->ReadAttributeString('EditorDraft') !== ''
                    || $this->GetBuffer('EditorWork.rule') !== '' || $this->GetBuffer('EditorWork.sequence') !== '') throw new RuntimeException('manager_not_empty');
                $before = $this->ReadWorkspace();
                if (!hash_equals(WorkspaceState::Fingerprint($before), $WorkspaceFingerprint)) throw new RuntimeException('target_changed');
                foreach ($before['systems'] as $record) if ($record['data'] !== WorkspaceState::Data()) throw new RuntimeException('manager_not_empty');
                $rows = $this->DecodeWorkspaceInput($Mapping, 8192);
                if (!array_is_list($rows) || count($rows) < 1 || count($rows) > 8) throw new RuntimeException('invalid_request');
                $after = WorkspaceState::Empty();
                $sources = $summaries = [];
                foreach ($rows as $row) {
                    if (!is_array($row) || count($row) !== 3 || !isset($row['sourceInstance'], $row['connectionInstance'], $row['fingerprint'])
                        || $row['sourceInstance'] === $this->InstanceID || !is_int($row['connectionInstance'])) throw new RuntimeException('invalid_request');
                    $export = $this->MigrationExport($row['sourceInstance']);
                    $summary = $export['summary'];
                    // Back up raw YAML and all editor variants before readiness is evaluated.
                    $archive->Write($OperationID . '-source-' . $row['sourceInstance'], $export, true);
                    $this->MigrationCheckpoint('archive-' . count($sources));
                    if (!is_string($row['fingerprint']) || !hash_equals($summary['fingerprint'], $row['fingerprint'])) throw new RuntimeException('source_changed');
                    if (array_diff(array_column($summary['issues'], 'code'), ['catalog_missing']) !== []) throw new RuntimeException('source_blocked');
                    $binding = $this->WorkspaceConnectionBinding($row['connectionInstance']);
                    if ($binding === null || !$this->WorkspaceConnectionCurrent($binding)
                        || $binding['mqttParent'] !== $summary['mqttParent'] || $binding['baseTopic'] !== $summary['baseTopic']) throw new RuntimeException('binding_changed');
                    $binding['sourceInstance'] = $row['sourceInstance'];
                    $record = $this->MigrationRecord($export, $binding);
                    $record['ownershipEpoch'] = $OperationID;
                    $after['systems'][$binding['systemId']] = $record;
                    $sources[] = ['binding' => $binding, 'fingerprint' => $summary['fingerprint'], 'checksum' => WorkspaceState::Fingerprint($export),
                        'peerInstance'      => $summary['peerInstance'],
                        'legacyOptions'     => array_intersect_key(
                            json_decode($export['configuration'], true, 8, JSON_THROW_ON_ERROR),
                            ['Active' => true, 'ResumeAfterRestart' => true, 'RequestTimeout' => true]
                        )];
                    $summaries[] = $summary;
                }
                // Never drop preparatory entries or silently alias duplicate systems.
                if (array_diff(array_keys($before['systems']), array_keys($after['systems'])) !== []
                    || SystemContract::RegistryError(array_column($sources, 'binding')) !== '') throw new RuntimeException('binding_changed');
                $preview = SystemMigrationPreview::Build($summaries);
                foreach ($preview['systems'] as $system) if (array_diff(array_column($system['issues'], 'code'), ['catalog_missing', 'peer_missing']) !== []) throw new RuntimeException('source_blocked');
                $after['selectedSystem'] = $before['selectedSystem'] !== '' ? $before['selectedSystem'] : array_key_first($after['systems']);
                if (WorkspaceState::Error($after) !== '') throw new RuntimeException('source_blocked');
                $manifest = ['version' => 1, 'owner' => $this->InstanceID, 'operation' => $OperationID, 'request' => $request,
                    'before'           => $before, 'beforeRaw' => $this->ReadAttributeString('SystemWorkspace'), 'beforeReferences' => $this->GetReferenceList(), 'after' => $after, 'sources' => $sources];
                $archive->Write($OperationID . '-manifest', $manifest, true);
                $this->MigrationCheckpoint('manifest');
                $journal = ['version' => 1, 'owner' => $this->InstanceID, 'operation' => $OperationID, 'request' => $request,
                    'manifest'        => WorkspaceState::Fingerprint($manifest), 'sources' => $sources, 'phase' => 'prepared', 'createdAt' => time()];
                $this->SaveMigrationJournal($journal);
                $this->MigrationCheckpoint('prepared');
                $this->WriteOwnership(['version' => 1, 'role' => 'manager', 'owner' => $this->InstanceID, 'operation' => $OperationID]);
                $this->MigrationCheckpoint('owner');
                $this->DebugEvent('AKTION', 'Übernahme vorbereitet und vollständig privat gesichert. Bisherige Instanzen bleiben bedienbar. Erst die ausdrückliche Übernahme wechselt den Besitzer.', ['Systeme' => count($sources)]);
                return $this->MigrationReport($journal);
            } catch (Throwable $exception) {
                return $this->MigrationFailure($exception);
            }
        });
    }

    public function ApplySystemMigration(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $journal = $this->ReadMigrationJournal($OperationID);
                $this->MigrationOwner($journal);
                if ($journal['phase'] === 'committed') return $this->MigrationReport($journal);
                if (!in_array($journal['phase'], ['prepared', 'applying'], true)) throw new RuntimeException('ownership_conflict');
                $manifest = $this->MigrationManifest($journal);
                $current = $this->ReadWorkspace();
                if ($current !== $manifest['before'] && $current !== $manifest['after']) throw new RuntimeException('target_changed');
                $journal['phase'] = 'applying';
                $this->SaveMigrationJournal($journal);
                $this->MigrationCheckpoint('applying');
                foreach ($manifest['sources'] as $index => $source) {
                    $binding = $source['binding'];
                    if (!$this->WorkspaceConnectionCurrent($binding)) throw new RuntimeException('binding_changed');
                    $this->MigrationAck(Z2MA_FenceSystemMigration($binding['sourceInstance'], $this->InstanceID, $OperationID, $source['fingerprint'], false));
                    $this->MigrationCheckpoint('source-' . $index);
                    $this->MigrationAck(Z2MC_ClaimSystemConnection($binding['connectionInstance'], $this->InstanceID, $OperationID, json_encode($binding, WorkspaceState::JSON_FLAGS), false));
                    $this->MigrationCheckpoint('connection-' . $index);
                }
                // Sources are fenced before publishing a writable target. Recheck every binding and source at the boundary.
                foreach ($manifest['sources'] as $source) {
                    if (!$this->WorkspaceConnectionCurrent($source['binding'])) throw new RuntimeException('binding_changed');
                    $this->MigrationAck(Z2MA_FenceSystemMigration($source['binding']['sourceInstance'], $this->InstanceID, $OperationID, $source['fingerprint'], false));
                }
                $this->WriteWorkspace($manifest['after']);
                $this->MigrationCheckpoint('workspace');
                foreach ($manifest['sources'] as $source) $this->RegisterReference($source['binding']['connectionInstance']);
                $this->MigrationCheckpoint('references');
                $journal['phase'] = 'committed';
                $this->SaveMigrationJournal($journal);
                $this->MigrationCheckpoint('committed');
                $this->DebugEvent('AKTION', 'Lokale Übernahme abgeschlossen: bisherige Editoren gesperrt, zentrale Entwürfe bytegleich übernommen. Betriebsstatus und Gerätedaten müssen neu bestätigt werden. Laufende Z2M-Automationen bleiben unverändert.', ['Systeme' => count($manifest['sources'])]);
                return $this->MigrationReport($journal);
            } catch (Throwable $exception) {
                return $this->MigrationFailure($exception);
            }
        });
    }

    public function RollbackSystemMigration(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $journal = $this->ReadMigrationJournal($OperationID);
                if ($journal['phase'] === 'rolled_back') {
                    $marker = $this->OwnershipMarker();
                    if (($marker['operation'] ?? null) === $OperationID) $this->WriteOwnership([]);
                    return $this->MigrationReport($journal);
                }
                $this->MigrationOwner($journal);
                if ($this->WorkspaceTransferBlocksRollback($OperationID)) throw new RuntimeException('z2m_write_started');
                $manifest = $this->MigrationManifest($journal);
                foreach ($this->ReadWorkspace()['systems'] as $id => $record) {
                    if (!isset($manifest['after']['systems'][$id]) && isset($record['ownershipEpoch'])) throw new RuntimeException('rollback_conflict');
                }
                if ($journal['phase'] !== 'rolling_back') {
                    $journal['phase'] = 'rolling_back';
                    $this->SaveMigrationJournal($journal);
                }
                // Once rollback starts, central writes stay fenced even if archiving fails.
                $archive = $this->MigrationArchive();
                if (!$archive->Exists($OperationID . '-return')) $archive->Write($OperationID . '-return', ['version' => 1, 'workspace' => $this->ReadWorkspace()], true);
                $savedReturn = $archive->Read($OperationID . '-return');
                if ($this->ReadWorkspace() !== $savedReturn['workspace'] && $this->ReadWorkspace() !== $manifest['before']) throw new RuntimeException('target_changed');
                $this->DebugEvent('AKTION', 'Aktuellen zentralen Arbeitsstand vor dem Rückweg zusätzlich privat gesichert und geprüft. Keine Quellinstanz wird mit dieser Kopie überschrieben.');
                $this->MigrationCheckpoint('return-backup');
                foreach ($manifest['sources'] as $source) {
                    $binding = $source['binding'];
                    $owner = json_decode(Z2MA_GetSystemOwnership($binding['sourceInstance']), true, 16, JSON_THROW_ON_ERROR);
                    if ($owner !== [] && (($owner['owner'] ?? null) !== $this->InstanceID || ($owner['operation'] ?? '') !== $OperationID)) throw new RuntimeException('ownership_conflict');
                    if ($owner !== []) {
                        $summary = json_decode(Z2MA_GetSystemMigrationSource($binding['sourceInstance']), true, 16, JSON_THROW_ON_ERROR);
                        if (($summary['fingerprint'] ?? '') !== $source['fingerprint']) throw new RuntimeException('rollback_conflict');
                    }
                }
                // Restore the target preimage before releasing even one legacy writer.
                $this->WriteAttributeString('SystemWorkspace', $manifest['beforeRaw']);
                foreach ($manifest['sources'] as $source) {
                    $id = $source['binding']['connectionInstance'];
                    if (!in_array($id, $manifest['beforeReferences'], true) && in_array($id, $this->GetReferenceList(), true)) $this->UnregisterReference($id);
                }
                $this->MigrationCheckpoint('return-workspace');
                foreach ($manifest['sources'] as $index => $source) {
                    $binding = $source['binding'];
                    $this->MigrationAck(Z2MC_ClaimSystemConnection($binding['connectionInstance'], $this->InstanceID, $OperationID, json_encode($binding, WorkspaceState::JSON_FLAGS), true));
                    $this->MigrationCheckpoint('return-connection-' . $index);
                    $this->MigrationAck(Z2MA_FenceSystemMigration($binding['sourceInstance'], $this->InstanceID, $OperationID, $source['fingerprint'], true));
                    $this->MigrationCheckpoint('return-source-' . $index);
                }
                $journal['phase'] = 'rolled_back';
                $this->SaveMigrationJournal($journal);
                $this->MigrationCheckpoint('rolled_back');
                $this->WriteOwnership([]);
                $this->DebugEvent('AKTION', 'Lokalen Rückweg abgeschlossen. Zentrale Änderungen sind zusätzlich privat gesichert; bisherige Quellen wieder freigegeben. Geräte, MQTT-Einstellungen und laufende Z2M-Automationen bleiben unverändert.');
                return $this->MigrationReport($journal);
            } catch (Throwable $exception) {
                return $this->MigrationFailure($exception);
            }
        });
    }

    public function GetSystemMigration(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                return $this->MigrationReport($this->ReadMigrationJournal($OperationID));
            }
            catch (Throwable $exception) {
                return $this->MigrationFailure($exception);
            }
        });
    }

    /** Bounded read-only handshake; no owner semaphore, avoiding reverse lock acquisition. */
    public function GetSystemMigrationAuthority(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                $marker = $this->OwnershipMarker();
                if (($marker['role'] ?? '') !== 'manager' || ($marker['operation'] ?? '') !== $OperationID) throw new RuntimeException('ownership_conflict');
                $journal = $this->ReadMigrationJournal($OperationID);
                // Only compact, checksummed authority metadata crosses this reverse handshake.
                return $this->MigrationResult(['owner' => $this->InstanceID, 'operation' => $OperationID, 'phase' => $journal['phase'], 'sources' => $journal['sources']]);
            } catch (Throwable $exception) {
                return $this->MigrationFailure($exception);
            }
        });
    }

    protected function MigrationStorageDirectory(): string
    {
        return IPS_GetKernelDir() . 'Z2MAutomations-private' . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . $this->InstanceID;
    }

    /** Fault boundary used by offline recovery tests; it has no production side effect. */
    protected function MigrationCheckpoint(string $phase): void
    {
    }

    private function MigrationArchive(): MigrationArchive
    {
        return new MigrationArchive($this->MigrationStorageDirectory());
    }

    private function MigrationExport(mixed $id): array
    {
        if (!is_int($id) || !IPS_InstanceExists($id) || IPS_GetInstance($id)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE) throw new RuntimeException('invalid_request');
        $raw = Z2MA_ExportSystemMigration($id);
        $value = $this->DecodeWorkspaceInput($raw, WorkspaceState::MAX_RESPONSE * 4);
        if (($value['version'] ?? null) !== 1 || !$this->ValidMigrationSourceSummary($value['summary'] ?? null, $id)) throw new RuntimeException('source_blocked');
        return $value;
    }

    private function MigrationRecord(array $export, array $binding): array
    {
        $record = WorkspaceState::Record($binding, IPS_GetObject($binding['sourceInstance'])['ObjectName']);
        $draft = json_decode($export['attributes']['EditorDraft'], true, 32, JSON_THROW_ON_ERROR);
        $record['data']['document'] = ['yaml' => $export['attributes']['YamlDraft'], 'editorDraft' => array_intersect_key($draft, array_flip(['configuration', 'baseRevision', 'baseChecksum', 'draftChecksum']))];
        foreach (['rule' => 'EditorSelection', 'sequence' => 'SequenceSelection'] as $kind => $attribute) {
            $selected = json_decode($export['attributes'][$attribute] ?: '[]', true, 16, JSON_THROW_ON_ERROR);
            $record['data']['selection'][$kind] = $selected['id'] ?? '';
            $work = json_decode($export['work']['EditorWork.' . $kind] ?: 'null', true, 32, JSON_THROW_ON_ERROR);
            if ($work !== null) $record['data']['work'][$kind] = array_intersect_key($work, ['dirty' => true, 'input' => true]);
        }
        if (WorkspaceState::DataError($record['data']) !== '') throw new RuntimeException('source_blocked');
        $instances = [];
        foreach (array_keys(Z2MInstanceIdentity::TYPES) as $type) foreach (IPS_GetInstanceListByModuleID($type) as $id) {
            $scope = Z2MInstanceIdentity::Read($id, $binding['mqttParent'], $binding['baseTopic']);
            if ($scope !== null && $scope['type'] !== 'bridge') $instances[$id] = $scope;
        }
        $record['nativeBindings'] = NativeVariableBindings::Collect($instances);
        // No historical session/catalog is a new permission. Fresh connection confirmation is required.
        return $record;
    }

    private function MigrationOperationID(string $id): void
    {
        if (preg_match('/\A[a-f0-9]{32}\z/D', $id) !== 1) throw new RuntimeException('invalid_request');
    }

    private function SaveMigrationJournal(array $journal): void
    {
        $this->MigrationArchive()->Write($journal['operation'] . '-journal', $journal);
    }

    private function ReadMigrationJournal(string $operation): array
    {
        $this->MigrationOperationID($operation);
        $journal = $this->MigrationArchive()->Read($operation . '-journal');
        if (($journal['version'] ?? null) !== 1 || ($journal['owner'] ?? null) !== $this->InstanceID || ($journal['operation'] ?? null) !== $operation
            || !in_array($journal['phase'] ?? '', ['prepared', 'applying', 'committed', 'rolling_back', 'rolled_back'], true)
            || !ExtensionContract::Checksum($journal['request'] ?? null) || !ExtensionContract::Checksum($journal['manifest'] ?? null)
            || !is_array($journal['sources'] ?? null) || !array_is_list($journal['sources']) || count($journal['sources']) < 1 || count($journal['sources']) > 8
            || SystemContract::RegistryError(array_column($journal['sources'], 'binding')) !== '') throw new RuntimeException('archive_failed');
        foreach ($journal['sources'] as $source) if (!ExtensionContract::Checksum($source['fingerprint'] ?? null) || !ExtensionContract::Checksum($source['checksum'] ?? null)
            || $source['binding']['ownerInstance'] !== $this->InstanceID) throw new RuntimeException('archive_failed');
        return $journal;
    }

    private function MigrationManifest(array $journal): array
    {
        $manifest = $this->MigrationArchive()->Read($journal['operation'] . '-manifest');
        if (!hash_equals($journal['manifest'], WorkspaceState::Fingerprint($manifest)) || $manifest['sources'] !== $journal['sources']) throw new RuntimeException('archive_failed');
        foreach ($manifest['sources'] as $source) {
            $export = $this->MigrationArchive()->Read($journal['operation'] . '-source-' . $source['binding']['sourceInstance']);
            if (!hash_equals($source['checksum'], WorkspaceState::Fingerprint($export))) throw new RuntimeException('archive_failed');
        }
        return $manifest;
    }

    private function MigrationWorkspaceWritable(): bool
    {
        $marker = $this->OwnershipMarker();
        if ($this->WorkspaceSetupLocked()) return false;
        $direct = $marker !== [] ? $this->DirectWorkspaceOwnerReady($marker) : null;
        if ($direct !== null) return $marker['role'] === 'manager' && $direct;
        return $marker['role'] === 'manager' && $this->ReadMigrationJournal($marker['operation'])['phase'] === 'committed';
    }

    private function MigrationOwner(array $journal): void
    {
        $expected = ['version' => 1, 'role' => 'manager', 'owner' => $this->InstanceID, 'operation' => $journal['operation']];
        $marker = $this->OwnershipMarker();
        if ($marker === [] && $journal['phase'] === 'prepared') {
            $this->WriteOwnership($expected);
            return;
        }
        if ($marker !== $expected) throw new RuntimeException('ownership_conflict');
    }

    private function MigrationAck(string $raw): void
    {
        $result = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (($result['ok'] ?? false) !== true) throw new RuntimeException(isset(self::MIGRATION_MESSAGES[$result['error'] ?? '']) ? $result['error'] : 'migration_interrupted');
    }

    private function MigrationReport(array $journal): string
    {
        $manifest = $this->MigrationManifest($journal);
        $systems = [];
        foreach ($manifest['sources'] as $source) {
            $peer = $source['peerInstance'];
            $matched = array_values(array_filter($manifest['sources'], static fn (array $other): bool => $other['binding']['sourceInstance'] === $peer));
            $systems[] = ['binding'      => $source['binding'], 'peerSystemId' => $matched[0]['binding']['systemId'] ?? null,
                'unresolvedPeerInstance' => $peer > 0 && $matched === [] ? $peer : 0, 'requiresValidation' => true, 'catalogConfirmed' => false,
                'bindingsVerified'       => false, 'legacyOptions' => $source['legacyOptions']];
        }
        $message = match ($journal['phase']) {
            'prepared'     => 'Sicherung geprüft. Die bisherigen Instanzen bleiben bedienbar; die lokale Übernahme kann gestartet werden.',
            'applying'     => 'Die Übernahme ist noch nicht abgeschlossen. Bitte denselben Vorgang fortsetzen oder den Rückweg wählen.',
            'committed'    => 'Die zentrale Verwaltung hat übernommen. Die bisherigen Editoren bleiben gesperrt; Z2M läuft unverändert weiter.',
            'rolling_back' => 'Der Rückweg ist noch nicht abgeschlossen. Bitte denselben Rückweg fortsetzen; die zentrale Bearbeitung bleibt gesperrt.',
            'rolled_back'  => 'Der Rückweg ist abgeschlossen. Die bisherigen Instanzen sind wieder zuständig; zentrale Änderungen bleiben zusätzlich gesichert.'
        };
        $this->DebugEvent('AKTION', $message, ['Phase' => $journal['phase'], 'Systeme' => count($systems)]);
        return $this->MigrationResult(['operation' => $journal['operation'], 'phase' => $journal['phase'], 'message' => $message, 'systems' => $systems]);
    }

    private function MigrationResult(array $fields): string
    {
        return json_encode(['version' => 1, 'ok' => true, 'z2mChanged' => false] + $fields, WorkspaceState::JSON_FLAGS);
    }

    private function MigrationFailure(Throwable $exception): string
    {
        $error = $exception instanceof RuntimeException && isset(self::MIGRATION_MESSAGES[$exception->getMessage()]) ? $exception->getMessage() : 'migration_interrupted';
        $message = self::MIGRATION_MESSAGES[$error];
        $this->DebugEvent('FEHLER', $message, ['Ursache' => $error]);
        return json_encode(['version' => 1, 'ok' => false, 'error' => $error, 'message' => $message, 'z2mChanged' => false], WorkspaceState::JSON_FLAGS);
    }
}
