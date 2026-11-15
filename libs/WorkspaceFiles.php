<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;

/** Portable local workspace/draft files; no broker secrets or runtime commands. */
trait WorkspaceFiles
{
    private function WorkspaceFileRequest(array $body, string $system, int $generation, string $fingerprint): array
    {
        if (!RuntimeDebugContract::UUID($body['requestId'] ?? null)) throw new RuntimeException('invalid_request');
        $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system, $generation, $fingerprint);
        if (!$this->WorkspaceConnectionCurrent($record['binding']) || WorkspaceState::Dirty($record)) throw new RuntimeException('open_input');
        if (($body['action'] ?? '') === 'project') {
            if (!$this->DesignerEditShape($body, ['requestId', 'action'])) throw new RuntimeException('invalid_request');
            $files = [];
            foreach ($this->ReadWorkspace()['systems'] as $source) {
                if ($source['data']['document'] === null) continue;
                $files[] = ['format' => 'z2m-automations-draft', 'version' => 1,
                    'system'         => ['id' => $source['binding']['systemId'], 'name' => $source['name'], 'baseTopic' => $source['binding']['baseTopic']],
                    'configuration'  => DraftMerge::Normalize($source['data']['document']['editorDraft']['configuration']), 'base' => $source['mergeBase'] ?? null];
            }
            $this->DebugEvent('AKTION', 'Lokale Projektdatei mit systembezogenen Entwürfen vorbereitet. Keine Zugangsdaten und kein MQTT-Auftrag.', ['Systeme' => count($files)]);
            return ['ok' => true, 'file' => ['format' => 'z2m-automations-workspace', 'version' => 1, 'name' => IPS_GetName($this->InstanceID), 'systems' => $files]];
        }
        if (($body['action'] ?? '') !== 'import' || !$this->DesignerEditShape($body, ['requestId', 'action', 'file', 'choices']) || !is_array($body['choices'])) throw new RuntimeException('invalid_request');
        $file = $body['file'];
        if (!is_array($file) || !$this->DesignerEditShape($file, ['format', 'version', 'system', 'configuration', 'base'])
            || $file['format'] !== 'z2m-automations-draft' || $file['version'] !== 1 || !is_array($file['system']) || !is_array($file['configuration'])
            || !$this->DesignerEditShape($file['system'], ['id', 'name', 'baseTopic'])) throw new RuntimeException('invalid_data');
        foreach ($file['system'] as $value) if (!is_string($value) || $value === '' || strlen($value) > 480) throw new RuntimeException('invalid_data');
        if (!DraftMerge::SafeConfiguration($file['configuration'])) throw new RuntimeException('invalid_data');
        $incoming = DraftMerge::Normalize($file['configuration']);
        $local = DraftMerge::Normalize($record['data']['document']['editorDraft']['configuration'] ?? []);
        // Importing a rule cannot silently widen this network's partner grants.
        $incoming['mesh_peers'] = $local['mesh_peers'];
        $known = array_column($local['automations'], null, 'id');
        foreach ($incoming['automations'] as &$rule) if (!isset($known[$rule['id'] ?? ''])) $rule['enabled'] = false;
        unset($rule);
        $conflicts = DraftMerge::Plan($local, $incoming)['conflicts'];
        foreach ($body['choices'] as $key => $value) if (!in_array($key, array_column($conflicts, 'key'), true) || !in_array($value, ['local', 'remote'], true)) throw new RuntimeException('invalid_request');
        $plan = DraftMerge::Plan($local, $incoming, null, $body['choices']);
        if ($plan['conflicts'] !== []) return ['ok' => true, 'applied' => false, 'conflicts' => $plan['conflicts']];
        $configuration = $plan['configuration'];
        $configuration['revision'] = max(1, $local['revision'] + 1);
        $yaml = json_encode($configuration, WorkspaceState::JSON_FLAGS | JSON_PRETTY_PRINT) . "\n";
        $data = $record['data'];
        $data['document'] = ['yaml' => $yaml, 'editorDraft' => ['configuration' => $configuration,
            'baseRevision'                                                      => $record['data']['document']['editorDraft']['baseRevision'] ?? 0,
            'baseChecksum'                                                      => $record['data']['document']['editorDraft']['baseChecksum'] ?? null, 'draftChecksum' => hash('sha256', $yaml)]];
        if (WorkspaceState::DataError($data) !== '') throw new RuntimeException('invalid_data');
        $operation = $this->WorkspaceOperation(str_replace('-', '', $body['requestId']), ['draft_file', $system, $generation, $fingerprint, hash('sha256', json_encode($body, WorkspaceState::JSON_FLAGS))]);
        $archiveID = str_replace('-', '', $body['requestId']);
        $response = $this->WithWorkspaceLock(function () use ($system, $generation, $fingerprint, $data, $operation, $archiveID): string
        {
            $workspace = $this->ReadWorkspace();
            $replay = $this->WorkspaceReplay($workspace, $operation);
            if ($replay !== null) return $replay;
            $record = $this->WorkspaceRecord($workspace, $system, $generation, $fingerprint);
            if (!$this->WorkspaceConnectionCurrent($record['binding']) || WorkspaceState::Dirty($record)) throw new RuntimeException('binding_changed');
            $before = $record;
            $this->MigrationArchive()->Write($archiveID . '-draft', ['binding' => $record['binding'], 'before' => $record, 'snapshot' => ['data' => $data]], true);
            $record['data'] = $data;
            $workspace['systems'][$system] = $record;
            $jobs = $this->ReadWorkspaceDraftSync();
            $jobs['systems'][$system] = ['binding' => $record['binding'], 'epoch' => $record['ownershipEpoch'], 'operation' => $archiveID,
                'fingerprint'                      => WorkspaceState::Fingerprint($before), 'after' => WorkspaceState::Fingerprint($record), 'phase' => 'updated', 'source' => 'file',
                'error'                            => '', 'baseRevision' => $data['document']['editorDraft']['baseRevision'], 'remoteRevision' => $data['document']['editorDraft']['baseRevision'],
                'counts'                           => $this->WorkspaceDraftDifference($before['data']['document']['editorDraft']['configuration'] ?? [], $data['document']['editorDraft']['configuration']),
                'permissionsChanged'               => false, 'deadline' => $this->Now()];
            $this->WriteWorkspaceDraftSync($jobs);
            return $this->CommitWorkspace($workspace, $operation, ['applied' => true]);
        });
        $this->DebugEvent('AKTION', 'Automationen aus einer Draft-Datei lokal zusammengeführt. Neue Regeln ausgeschaltet; bisheriger Arbeitsstand gesichert. Kein MQTT-Auftrag.', ['Systembezug' => $system]);
        return json_decode($response, true, 32, JSON_THROW_ON_ERROR);
    }
}
