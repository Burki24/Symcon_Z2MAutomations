<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait EditorScenes
{
    private function AddEditorScenes(array $snapshot): array
    {
        foreach ($snapshot['scenes'] ?? [] as $scene) {
            $key = $scene['key'];
            if (!isset($snapshot['devices'][$key])) {
                continue;
            }
            $property = $scene['endpoint'] === 0 ? 'z2ma_scene' : 'z2ma_scene_' . $scene['endpoint'];
            $meta = &$snapshot['devices'][$key]['properties'][$property];
            $meta ??= ['property' => $property, 'type' => 'enum', 'access' => 2, 'label' => 'Recall scene', 'endpoint' => '', 'values' => [],
                'sceneEndpoint'   => $scene['endpoint'], 'controlProperty' => $scene['control'], 'sceneNames' => []];
            if (($meta['controlProperty'] ?? '') !== $scene['control']) {
                return [];
            }
            $meta['values'][] = $scene['id'];
            $meta['sceneNames'][$scene['id']] = $scene['name'];
            unset($meta);
            $choice = ['property' => $property, 'value' => $scene['id']];
            $snapshot['devices'][$key]['actions'][hash('sha256', json_encode($choice, JSON_THROW_ON_ERROR))] = $choice;
        }
        return $snapshot;
    }

    private function EditorPropertyToken(array $snapshot, string $device, string $property, string $side): string
    {
        $binding = $side === 'Target' ? $this->EditorActionVariable($snapshot, $device, $property) : null;
        return $binding === null || isset($snapshot['devices'][$device]['properties'][$property]['sceneEndpoint']) ? $property : (string) $binding['id'];
    }

    private function EditorChoiceValueLabel(array $snapshot, string $device, string $property, mixed $value): string
    {
        $meta = $snapshot['devices'][$device]['properties'][$property] ?? [];
        if (isset($meta['sceneEndpoint']) && is_int($value)) {
            $name = $meta['sceneNames'][$value] ?? '';
            return $name === '' ? sprintf($this->Translate('Scene %d'), $value) : $name . ' (' . $value . ')';
        }
        return $this->EditorValueLabel($value);
    }
}
