<?php

declare(strict_types=1);

namespace Z2MAutomations;

use Throwable;

require_once __DIR__ . '/NativeVariableBindings.php';

trait EditorSymconVariables
{
    private ?array $editorActionVariables = null;

    private function EditorActionVariables(): array
    {
        if ($this->editorActionVariables !== null) {
            return $this->editorActionVariables;
        }
        $instances = [];
        foreach ($this->GatewayZ2MInstances() as $instance => $scope) {
            if ($scope['type'] === 'device' && $scope['topic'] === $this->ReadPropertyString('BaseTopic')) $instances[$instance] = $scope;
        }
        return $this->editorActionVariables = NativeVariableBindings::Collect($instances);
    }

    private function EditorActionVariable(array $snapshot, string $device, string $property): ?array
    {
        $property = $snapshot['devices'][$device]['properties'][$property]['controlProperty'] ?? $property;
        if (str_starts_with($device, 'remote.')) {
            return $this->RemoteActionVariable($snapshot, $device, $property);
        }
        if (str_starts_with($device, 'group.')) {
            return $this->EditorGroupVariable($snapshot, $device, $property);
        }
        $binding = $this->EditorActionVariables()[$device][$property] ?? null;
        if ($binding === null) {
            return null;
        }
        try {
            if (!IPS_ObjectExists($binding['id']) || !IPS_VariableExists($binding['id'])) {
                return null;
            }
            $scope = $this->ReadZ2MInstanceScope($binding['instance']);
            $object = IPS_GetObject($binding['id']);
            if ($object['ParentID'] !== $binding['instance'] || $object['ObjectIdent'] !== $property
                || ($scope['type'] ?? '') !== 'device' || ($scope['key'] ?? '') !== $device
                || ($scope['topic'] ?? '') !== $this->ReadPropertyString('BaseTopic')) {
                return null;
            }
            if (!NativeVariableBindings::Switchable($binding['id'])) {
                return null;
            }
            return $binding;
        } catch (Throwable) {
            return null;
        }
    }

    private function FilterEditorActions(array $snapshot): array
    {
        if (!isset($snapshot['devices'])) {
            return $snapshot;
        }
        foreach ($snapshot['devices'] as $device => &$entry) {
            if (!isset($entry['properties'])) {
                $entry['properties'] = $this->ReadEditorProperties((object) [], $entry) ?? [];
                foreach ($entry['numeric'] ?? [] as $property => $unit) {
                    $entry['properties'][$property] ??= ['property' => $property, 'type' => 'numeric', 'access' => 1, 'label' => $property, 'endpoint' => '', 'values' => []];
                }
            }
            $entry['actions'] = array_filter($entry['actions'], fn (array $choice): bool => $this->EditorActionVariable($snapshot, $device, $choice['property']) !== null);
        }
        unset($entry);
        return $snapshot;
    }
}
