<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;

require_once __DIR__ . '/WorkspaceState.php';

/** Convert a correlated editor response without rewriting a stored YAML document. */
final class WorkspaceSetupSnapshot
{
    public static function Read(stdClass $data, array $target): ?array
    {
        $metadata = ExtensionContract::Status($data);
        if ($metadata === null || $metadata !== $target || ($data->editor_version ?? null) !== 1
            || !($data->configuration ?? null) instanceof stdClass || !in_array($metadata['storage'], ['ok', 'empty'], true)) return null;
        $configuration = json_decode(json_encode($data->configuration, WorkspaceState::JSON_FLAGS), true, 32, JSON_THROW_ON_ERROR);
        if (($configuration['schema_version'] ?? null) !== 1 || ($configuration['revision'] ?? null) !== $metadata['storedRevision']
            || !is_array($configuration['automations'] ?? null) || count($configuration['automations']) !== $metadata['automationCount']) return null;
        $empty = $metadata['storedRevision'] === 0;
        if ($empty) {
            if (($data->yaml ?? null) !== null || $configuration['automations'] !== [] || ($configuration['sequences'] ?? []) !== []) return null;
            $configuration['revision'] = 1;
        }
        $yaml = $empty ? json_encode($configuration, WorkspaceState::JSON_FLAGS | JSON_PRETTY_PRINT) . "\n" : ($data->yaml ?? null);
        if (!is_string($yaml) || !ExtensionContract::ValidYamlText($yaml) || (!$empty && hash('sha256', $yaml) !== $metadata['checksum'])) return null;
        $document = ['yaml' => $yaml, 'editorDraft' => ['configuration' => $configuration, 'baseRevision' => $metadata['storedRevision'],
            'baseChecksum'                                              => $metadata['checksum'], 'draftChecksum' => hash('sha256', $yaml)]];
        $data = WorkspaceState::Data();
        $data['document'] = $document;
        return WorkspaceState::DataError($data) === '' ? ['data' => $data, 'metadata' => $metadata, 'empty' => $empty] : null;
    }

    public static function SameStorage(array $left, array $right): bool
    {
        foreach (['session', 'storedRevision', 'checksum', 'automationCount', 'storage'] as $key) if (($left[$key] ?? null) !== ($right[$key] ?? null)) return false;
        return ($right['ready'] ?? false) && in_array($right['storage'] ?? '', ['ok', 'empty'], true);
    }
}
