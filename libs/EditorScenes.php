<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait EditorScenes
{
    private function ReadEditorScenes(object $data, array $devices, array $groups): ?array
    {
        if (!is_array($data->scenes ?? []) || count($data->scenes ?? []) > 512) {
            return null;
        }
        $scenes = [];
        $seen = [];
        foreach ($data->scenes ?? [] as $scene) {
            if (!is_object($scene) || !is_object($scene->target ?? null) || !is_int($scene->id ?? null) || $scene->id < 0 || $scene->id > 255
                || !is_string($scene->name ?? null) || strlen($scene->name) > 480 || preg_match('/[\x00-\x1f\x7f]/', $scene->name)
                || !is_string($scene->control_property ?? null) || preg_match('/\Astate(?:_[A-Za-z0-9_]+)?\z/D', $scene->control_property) !== 1) {
                return null;
            }
            $target = $scene->target;
            if (($target->type ?? '') === 'device' && is_string($target->id ?? null) && isset($devices[$target->id])
                && is_int($scene->endpoint ?? null) && $scene->endpoint >= 1 && $scene->endpoint <= 240) {
                $key = $target->id;
                $endpoint = $scene->endpoint;
            } elseif (($target->type ?? '') === 'group' && is_int($target->id ?? null) && isset($groups[$target->id])
                && !isset($scene->endpoint) && $scene->control_property === 'state') {
                $key = 'group.' . $target->id;
                $endpoint = 0;
            } else {
                return null;
            }
            $identity = $key . '.' . $endpoint . '.' . $scene->id;
            if (isset($seen[$identity])) {
                return null;
            }
            $seen[$identity] = true;
            $scenes[] = ['key' => $key, 'endpoint' => $endpoint, 'id' => $scene->id, 'name' => $scene->name, 'control' => $scene->control_property];
        }
        return $scenes;
    }

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
