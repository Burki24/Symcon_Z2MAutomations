<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

/** Explicit remote-to-local reads, guarded by the draft's remote base and a local CAS. */
trait WorkspaceDraftSync
{
    public function StartWorkspaceDraftSync(string $SystemID, int $Generation, string $Fingerprint, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $OperationID): string
        {
            try {
                $operation = $this->WorkspaceOperation($OperationID, ['draft_sync', $SystemID, $Generation, $Fingerprint]);
                $jobs = $this->ReadWorkspaceDraftSync();
                if (isset($jobs['receipts'][$OperationID])) {
                    if ($jobs['receipts'][$OperationID] !== $operation['request']) throw new RuntimeException('operation_conflict');
                    return $this->GetWorkspaceDraftSyncStatus($SystemID);
                }
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                $info = $this->WorkspaceRuntimeAccess($record); // Shared ownership checks; this performs no runtime command.
                if (WorkspaceState::Dirty($record)) throw new RuntimeException('open_input');
                if (!($info['state']['mqttConnected'] ?? false)) throw new RuntimeException('not_connected');
                if (in_array($jobs['systems'][$SystemID]['phase'] ?? '', ['reading', 'verifying'], true)
                    || ($info['state']['operation']['status'] ?? '') === 'pending' || ($info['state']['health'] ?? '') === 'pending') throw new RuntimeException('busy');
                $jobs['systems'] = array_intersect_key($jobs['systems'], $this->ReadWorkspace()['systems']);
                $jobs['systems'][$SystemID] = ['binding' => $record['binding'], 'epoch' => $record['ownershipEpoch'], 'operation' => $OperationID,
                    'fingerprint'                        => $Fingerprint, 'after' => '', 'phase' => 'reading', 'error' => '', 'baseRevision' => $record['data']['document']['editorDraft']['baseRevision'] ?? 0,
                    'remoteRevision'                     => null, 'counts' => ['added' => 0, 'changed' => 0, 'removed' => 0], 'permissionsChanged' => false,
                    'deadline'                           => $this->Now() + min(600, max(15, 3 * (int) IPS_GetProperty($record['binding']['connectionInstance'], 'RequestTimeout') + 15))];
                $jobs['receipts'][$OperationID] = $operation['request'];
                $jobs['receipts'] = array_slice($jobs['receipts'], -32, null, true);
                $this->WriteWorkspaceDraftSync($jobs);
                $started = true;
                $this->DebugEvent('SENDEN', 'Aktuellen Z2M-Stand für den lokalen Entwurfsabgleich lesen. Andere Systeme und laufender Betrieb bleiben unverändert.', ['Systembezug' => $SystemID, 'Auftrag' => $OperationID]);
                $reply = json_decode(Z2MC_RequestWorkspaceDraftSnapshot($record['binding']['connectionInstance'], $this->InstanceID, $OperationID, false), true, 48, JSON_THROW_ON_ERROR);
                $this->AcceptWorkspaceDraftSnapshot($SystemID, $reply);
                $this->SetTimerInterval('WorkspaceDraftSyncRefresh', 1000);
                return $this->GetWorkspaceDraftSyncStatus($SystemID);
            } catch (Throwable $exception) {
                if ($started ?? false) $this->FailWorkspaceDraftSync($SystemID, $exception);
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function ResolveWorkspaceDraftSync(string $SystemID, int $Generation, string $Fingerprint, string $OperationID, string $Action): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $OperationID, $Action): string
        {
            try {
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint);
                $this->WorkspaceRuntimeAccess($record);
                $jobs = $this->ReadWorkspaceDraftSync();
                $job = $jobs['systems'][$SystemID] ?? null;
                if ($job === null || $job['operation'] !== $OperationID || $job['binding'] !== $record['binding']) throw new RuntimeException('draft_sync_changed');
                if ($Action === 'replace' && $job['phase'] === 'conflict' && $Fingerprint === $job['fingerprint']) {
                    if (WorkspaceState::Dirty($record)) throw new RuntimeException('open_input');
                    $jobs['systems'][$SystemID]['phase'] = 'verifying';
                    $jobs['systems'][$SystemID]['deadline'] = $this->Now() + min(600, max(15, 3 * (int) IPS_GetProperty($record['binding']['connectionInstance'], 'RequestTimeout') + 15));
                    $this->WriteWorkspaceDraftSync($jobs);
                    $started = true;
                    $reply = json_decode(Z2MC_RequestWorkspaceDraftSnapshot($record['binding']['connectionInstance'], $this->InstanceID, $OperationID, true), true, 48, JSON_THROW_ON_ERROR);
                    $this->AcceptWorkspaceDraftSnapshot($SystemID, $reply);
                    $this->SetTimerInterval('WorkspaceDraftSyncRefresh', 1000);
                } elseif ($Action === 'restore' && $job['phase'] === 'updated' && $Fingerprint === $job['after']) {
                    $archive = $this->WorkspaceDraftArchive($job);
                    $this->WithWorkspaceLock(function () use ($SystemID, $Generation, $Fingerprint, $archive): void
                    {
                        $workspace = $this->ReadWorkspace();
                        $record = $this->WorkspaceRecord($workspace, $SystemID, $Generation, $Fingerprint);
                        $record['data'] = $archive['before']['data'];
                        $workspace['systems'][$SystemID] = $record;
                        $this->WriteWorkspace($workspace);
                    });
                    $jobs['systems'][$SystemID]['phase'] = 'restored';
                    $this->WriteWorkspaceDraftSync($jobs);
                    $this->DebugEvent('AKTION', 'Vorherigen lokalen Entwurf aus der geprüften Sicherung wiederhergestellt. Kein MQTT-Auftrag.', ['Systembezug' => $SystemID, 'Auftrag' => $OperationID]);
                } elseif (!(($Action === 'replace' && in_array($job['phase'], ['updated', 'verifying'], true))
                    || ($Action === 'restore' && $job['phase'] === 'restored'))) {
                    throw new RuntimeException('draft_sync_changed');
                }
                return $this->GetWorkspaceDraftSyncStatus($SystemID);
            } catch (Throwable $exception) {
                if ($started ?? false) $this->FailWorkspaceDraftSync($SystemID, $exception);
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function GetWorkspaceDraftSyncAuthority(string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($OperationID): string
        {
            try {
                foreach ($this->ReadWorkspaceDraftSync()['systems'] as $system => $job) {
                    if ($job['operation'] !== $OperationID || !in_array($job['phase'], ['reading', 'verifying'], true) || $this->Now() >= $job['deadline']) continue;
                    $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system, $job['binding']['generation'], $job['fingerprint']);
                    if ($record['binding'] !== $job['binding'] || WorkspaceState::Dirty($record)) throw new RuntimeException('draft_sync_changed');
                    $marker = $this->OwnershipMarker();
                    if (($marker['role'] ?? '') !== 'manager' || $marker['operation'] !== $job['epoch'] || ($record['ownershipEpoch'] ?? '') !== $job['epoch']) throw new RuntimeException('ownership_required');
                    return json_encode(['ok' => true, 'phase' => $job['phase'], 'epoch' => $job['epoch'], 'binding' => $job['binding']], WorkspaceState::JSON_FLAGS);
                }
            } catch (Throwable) {
                // Deny stale or foreign reads without disclosing document contents.
            }
            return '{"ok":false}';
        });
    }

    public function GetWorkspaceDraftSyncStatus(string $SystemID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID): string
        {
            try {
                $this->SyncWorkspaceDraft($SystemID);
                $record = $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID);
                return $this->WorkspaceResult('', ['draftSync' => $this->WorkspaceDraftSyncView($record)]);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function RefreshWorkspaceDrafts(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            foreach ($this->ReadWorkspaceDraftSync()['systems'] as $system => $job) if (in_array($job['phase'], ['reading', 'verifying'], true)) $this->SyncWorkspaceDraft($system);
            $pending = array_filter($this->ReadWorkspaceDraftSync()['systems'], static fn (array $job): bool => in_array($job['phase'], ['reading', 'verifying'], true));
            $this->SetTimerInterval('WorkspaceDraftSyncRefresh', $pending === [] ? 0 : 1000);
        });
    }

    private function SyncWorkspaceDraft(string $system): void
    {
        $job = $this->ReadWorkspaceDraftSync()['systems'][$system] ?? null;
        if ($job === null || !in_array($job['phase'], ['reading', 'verifying'], true)) return;
        try {
            $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system, $job['binding']['generation'], $job['fingerprint']);
            if ($record['binding'] !== $job['binding']) throw new RuntimeException('binding_changed');
            if ($this->Now() >= $job['deadline']) throw new RuntimeException('draft_sync_timeout');
            $this->WorkspaceRuntimeAccess($record);
            $reply = json_decode(Z2MC_GetWorkspaceSnapshotResult($job['binding']['connectionInstance'], $this->InstanceID, $job['operation']), true, 48, JSON_THROW_ON_ERROR);
            $this->AcceptWorkspaceDraftSnapshot($system, $reply);
        } catch (Throwable $exception) {
            $this->FailWorkspaceDraftSync($system, $exception);
        }
    }

    private function AcceptWorkspaceDraftSnapshot(string $system, array $reply): void
    {
        $jobs = $this->ReadWorkspaceDraftSync();
        $job = $jobs['systems'][$system];
        if ($this->Now() >= $job['deadline']) throw new RuntimeException('draft_sync_timeout');
        if (!($reply['ok'] ?? false) || ($reply['purpose'] ?? '') !== 'draft' || ($reply['owner'] ?? null) !== $this->InstanceID
            || ($reply['operation'] ?? '') !== $job['operation'] || ($reply['binding'] ?? null) !== $job['binding']) throw new RuntimeException('draft_sync_changed');
        if ($reply['status'] === 'pending') return;
        if ($reply['status'] !== ($job['phase'] === 'reading' ? 'ready' : 'verified')) throw new RuntimeException('draft_sync_failed');
        $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system, $job['binding']['generation'], $job['fingerprint']);
        if (WorkspaceState::Dirty($record)) throw new RuntimeException('open_input');
        $snapshot = $reply['snapshot'];
        if (!is_array($snapshot) || WorkspaceState::DataError($snapshot['data'] ?? null) !== '') throw new RuntimeException('invalid_data');
        if ($job['phase'] === 'reading') {
            $catalog = $this->WorkspaceCatalog($job['binding']);
            $bindings = $this->WorkspaceNativeBindings($job['binding']);
            if ($catalog === null) throw new RuntimeException('catalog_unavailable');
            // The read renews the connection catalog too. Keep the editing model usable,
            // including when its local document is current or needs conflict review.
            $record = $this->WithWorkspaceLock(function () use ($system, $job, $catalog, $bindings): array
            {
                $workspace = $this->ReadWorkspace();
                $record = $this->WorkspaceRecord($workspace, $system, $job['binding']['generation'], $job['fingerprint']);
                if (!$this->WorkspaceConnectionCurrent($job['binding'])) throw new RuntimeException('binding_changed');
                $record['catalog'] = $catalog;
                $record['nativeBindings'] = $bindings;
                $workspace['systems'][$system] = $record;
                $this->WriteWorkspace($workspace);
                return $record;
            });
            $job['fingerprint'] = WorkspaceState::Fingerprint($record);
            $remote = $snapshot['data']['document'];
            $local = $record['data']['document'];
            $job['remoteRevision'] = $snapshot['metadata']['storedRevision'];
            $job['counts'] = $this->WorkspaceDraftDifference($local['editorDraft']['configuration'] ?? [], $remote['editorDraft']['configuration']);
            $job['permissionsChanged'] = WorkspaceState::Permissions($record['data']) !== WorkspaceState::Permissions($snapshot['data']);
            $same = static function (array $config): array
            {
                unset($config['revision']);
                return $config;
            };
            $equivalent = $local !== null && $same($local['editorDraft']['configuration']) === $same($remote['editorDraft']['configuration']);
            $clean = $local === null || $local['editorDraft']['draftChecksum'] === $local['editorDraft']['baseChecksum'];
            $sameBase = $local !== null && $local['editorDraft']['baseRevision'] === $job['remoteRevision'] && $local['editorDraft']['baseChecksum'] === $remote['editorDraft']['baseChecksum'];
            $job['phase'] = match (true) {
                $job['remoteRevision'] < $job['baseRevision']                                      => 'older',
                $sameBase                                                                          => 'current',
                $local !== null && $job['remoteRevision'] === $job['baseRevision'] && !$equivalent => 'conflict',
                $clean || $equivalent                                                              => 'verifying',
                default                                                                            => 'conflict'
            };
            if (in_array($job['phase'], ['verifying', 'conflict'], true)) {
                $this->MigrationArchive()->Write($job['operation'] . '-draft', ['binding' => $job['binding'], 'before' => $record, 'snapshot' => $snapshot], true);
            }
            $jobs['systems'][$system] = $job;
            $this->WriteWorkspaceDraftSync($jobs);
            $this->DebugEvent(
                'EMPFANG',
                'Z2M-Basis und lokalen Entwurf verglichen. Revisionszähler des Entwurfs sind kein Altersnachweis; lokale Änderungen werden nicht still ersetzt.',
                ['Systembezug' => $system, 'Auftrag' => $job['operation'], 'Basisrevision' => $job['baseRevision'], 'Z2MRevision' => $job['remoteRevision'], 'Ergebnis' => $job['phase']]
            );
            if ($job['phase'] === 'verifying') {
                $reply = json_decode(Z2MC_RequestWorkspaceDraftSnapshot($job['binding']['connectionInstance'], $this->InstanceID, $job['operation'], true), true, 48, JSON_THROW_ON_ERROR);
                $this->AcceptWorkspaceDraftSnapshot($system, $reply);
            }
            return;
        }
        $archive = $this->WorkspaceDraftArchive($job);
        if (!WorkspaceSetupSnapshot::SameStorage($archive['snapshot']['metadata'], $snapshot['metadata'])) throw new RuntimeException('draft_sync_changed');
        $catalog = $this->WorkspaceCatalog($job['binding']);
        $bindings = $this->WorkspaceNativeBindings($job['binding']);
        $this->WithWorkspaceLock(function () use ($system, &$job, $archive, $catalog, $bindings): void
        {
            $workspace = $this->ReadWorkspace();
            $record = $this->WorkspaceRecord($workspace, $system, $job['binding']['generation'], $job['fingerprint']);
            if (!$this->WorkspaceConnectionCurrent($job['binding'])) throw new RuntimeException('binding_changed');
            $record['data'] = $archive['snapshot']['data'];
            foreach (['rule' => 'automations', 'sequence' => 'sequences'] as $kind => $collection) {
                $selected = $archive['before']['data']['selection'][$kind];
                if (in_array($selected, array_column($record['data']['document']['editorDraft']['configuration'][$collection] ?? [], 'id'), true)) $record['data']['selection'][$kind] = $selected;
            }
            $record['catalog'] = $catalog;
            $record['nativeBindings'] = $bindings;
            $workspace['systems'][$system] = $record;
            $this->WriteWorkspace($workspace);
            $job['after'] = WorkspaceState::Fingerprint($record);
        });
        $job['phase'] = 'updated';
        $jobs['systems'][$system] = $job;
        $this->WriteWorkspaceDraftSync($jobs);
        $this->DebugEvent('AKTION', 'Frisch bestätigten Z2M-Stand im lokalen Entwurf übernommen. Vorheriger Entwurf privat gesichert; Z2M-Datei, Freigaben und Betrieb nicht verändert.', ['Systembezug' => $system, 'Auftrag' => $job['operation'], 'Z2MRevision' => $job['remoteRevision']]);
    }

    private function WorkspaceDraftDifference(array $local, array $remote): array
    {
        $counts = ['added' => 0, 'changed' => 0, 'removed' => 0];
        foreach (['automations', 'sequences'] as $kind) {
            $before = array_column($local[$kind] ?? [], null, 'id');
            $after = array_column($remote[$kind] ?? [], null, 'id');
            $counts['added'] += count(array_diff_key($after, $before));
            $counts['removed'] += count(array_diff_key($before, $after));
            foreach (array_intersect_key($after, $before) as $id => $entry) if ($entry !== $before[$id]) $counts['changed']++;
        }
        return $counts;
    }

    private function WorkspaceDraftArchive(array $job): array
    {
        $archive = $this->MigrationArchive()->Read($job['operation'] . '-draft');
        if (($archive['binding'] ?? null) !== $job['binding'] || WorkspaceState::Fingerprint($archive['before'] ?? []) !== $job['fingerprint']
            || WorkspaceState::DataError($archive['snapshot']['data'] ?? null) !== '') throw new RuntimeException('draft_sync_failed');
        return $archive;
    }

    private function WorkspaceDraftSyncView(array $record): array
    {
        $job = $this->ReadWorkspaceDraftSync()['systems'][$record['binding']['systemId']] ?? null;
        $fingerprint = WorkspaceState::Fingerprint($record);
        $current = $job !== null && $job['binding'] === $record['binding']
            && ($job['phase'] === 'error' || in_array($fingerprint, [$job['fingerprint'], $job['after']], true));
        $phase = $current ? $job['phase'] : 'idle';
        $blocked = '';
        try {
            $info = $this->WorkspaceRuntimeAccess($record);
            if (!($info['state']['mqttConnected'] ?? false)) $blocked = 'not_connected';
            elseif (($info['state']['operation']['status'] ?? '') === 'pending' || ($info['state']['health'] ?? '') === 'pending') $blocked = 'busy';
            elseif (WorkspaceState::Dirty($record)) $blocked = 'open_input';
        } catch (Throwable $exception) {
            $blocked = $exception instanceof RuntimeException && isset(WorkspaceState::MESSAGES[$exception->getMessage()]) ? $exception->getMessage() : 'draft_sync_failed';
        }
        return ['systemId'       => $record['binding']['systemId'], 'generation' => $record['binding']['generation'], 'fingerprint' => $fingerprint,
            'operation'          => $current ? $job['operation'] : '', 'phase' => $phase, 'pending' => in_array($phase, ['reading', 'verifying'], true),
            'canStart'           => $blocked === '' && !in_array($phase, ['reading', 'verifying'], true), 'canReplace' => $phase === 'conflict' && $blocked === '',
            'canRestore'         => $phase === 'updated' && $fingerprint === $job['after'] && $blocked === '',
            'baseRevision'       => $current ? $job['baseRevision'] : ($record['data']['document']['editorDraft']['baseRevision'] ?? 0),
            'remoteRevision'     => $current ? $job['remoteRevision'] : null, 'counts' => $current ? $job['counts'] : ['added' => 0, 'changed' => 0, 'removed' => 0],
            'permissionsChanged' => $current && $job['permissionsChanged'], 'error' => $phase === 'error' ? $job['error'] : $blocked];
    }

    private function FailWorkspaceDraftSync(string $system, Throwable $exception): void
    {
        $jobs = $this->ReadWorkspaceDraftSync();
        if (!isset($jobs['systems'][$system])) return;
        $code = $exception instanceof RuntimeException && isset(WorkspaceState::MESSAGES[$exception->getMessage()]) ? $exception->getMessage() : 'draft_sync_failed';
        $jobs['systems'][$system]['phase'] = 'error';
        $jobs['systems'][$system]['error'] = $code;
        $this->WriteWorkspaceDraftSync($jobs);
        $this->DebugEvent('FEHLER', WorkspaceState::MESSAGES[$code], ['Systembezug' => $system, 'Auftrag' => $jobs['systems'][$system]['operation']]);
    }

    private function ReadWorkspaceDraftSync(): array
    {
        $raw = $this->ReadAttributeString('WorkspaceDraftSync');
        if (strlen($raw) > 65536) throw new RuntimeException('draft_sync_failed');
        $jobs = $raw === '' ? ['systems' => [], 'receipts' => []] : json_decode($raw, true, 24, JSON_THROW_ON_ERROR);
        if (!is_array($jobs['systems'] ?? null) || count($jobs['systems']) > 8 || !is_array($jobs['receipts'] ?? null) || count($jobs['receipts']) > 32) throw new RuntimeException('draft_sync_failed');
        foreach ($jobs['receipts'] as $operation => $request) if (!DraftTransferProtocol::Operation($operation) || !ExtensionContract::Checksum($request)) throw new RuntimeException('draft_sync_failed');
        foreach ($jobs['systems'] as $system => $job) {
            if (!SystemContract::SystemID($system) || !is_array($job) || !is_array($job['binding'] ?? null)
                || SystemContract::RegistryError([$job['binding']]) !== '' || $job['binding']['systemId'] !== $system || $job['binding']['ownerInstance'] !== $this->InstanceID
                || !DraftTransferProtocol::Operation($job['operation'] ?? null) || !DraftTransferProtocol::Operation($job['epoch'] ?? null)
                || !ExtensionContract::Checksum($job['fingerprint'] ?? null) || (($job['after'] ?? '') !== '' && !ExtensionContract::Checksum($job['after']))
                || !in_array($job['phase'] ?? null, ['reading', 'verifying', 'conflict', 'updated', 'current', 'older', 'restored', 'error'], true)
                || !is_string($job['error'] ?? null) || !is_int($job['deadline'] ?? null) || !ExtensionContract::Revision($job['baseRevision'] ?? null)
                || !array_key_exists('remoteRevision', $job) || ($job['remoteRevision'] !== null && !ExtensionContract::Revision($job['remoteRevision']))
                || !is_bool($job['permissionsChanged'] ?? null) || !is_array($job['counts'] ?? null)) throw new RuntimeException('draft_sync_failed');
            foreach (['added', 'changed', 'removed'] as $key) if (!is_int($job['counts'][$key] ?? null) || $job['counts'][$key] < 0 || $job['counts'][$key] > 200) throw new RuntimeException('draft_sync_failed');
        }
        return $jobs;
    }

    private function WriteWorkspaceDraftSync(array $jobs): void
    {
        $raw = json_encode($jobs, WorkspaceState::JSON_FLAGS);
        if (strlen($raw) > 65536) throw new RuntimeException('response_limit');
        $this->WriteAttributeString('WorkspaceDraftSync', $raw);
    }

    private function ResetWorkspaceDraftSync(): void
    {
        $jobs = $this->ReadWorkspaceDraftSync();
        foreach ($jobs['systems'] as &$job) if (in_array($job['phase'], ['reading', 'verifying'], true)) {
            $job['phase'] = 'error';
            $job['error'] = 'draft_sync_changed';
        }
        unset($job);
        $this->WriteWorkspaceDraftSync($jobs);
        $this->SetTimerInterval('WorkspaceDraftSyncRefresh', 0);
    }
}
