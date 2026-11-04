<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait EditorPropertyCatalog
{
    private function EditorProperties(array $snapshot, string $device, string $side, ?callable $bindingResolver = null): array
    {
        $properties = $snapshot['devices'][$device]['properties'] ?? [];
        if ($side === 'Target') {
            foreach ($properties as &$meta) {
                if ($meta['type'] === 'binary' && preg_match('/\Astate(?:_[A-Za-z0-9_]+)?\z/D', $meta['property']) === 1
                    && in_array('ON', $meta['values'], true) && in_array('OFF', $meta['values'], true)) {
                    $meta['values'][] = 'TOGGLE';
                }
            }
            unset($meta);
        }
        $bindingResolver ??= fn (string $property): ?array => $this->EditorActionVariable($snapshot, $device, $property);
        return array_filter($properties, fn (array $p): bool => ($p['access'] & ($side === 'Target' ? 2 : 1)) !== 0
            && ($side !== 'Target' || $bindingResolver($p['property']) !== null));
    }

    private function EditorPropertyLabel(array $snapshot, string $device, string $property, string $side): string
    {
        $sceneEndpoint = $snapshot['devices'][$device]['properties'][$property]['sceneEndpoint'] ?? null;
        if ($sceneEndpoint !== null) {
            return $this->Translate('Recall scene') . ($sceneEndpoint === 0 ? '' : ' (' . sprintf($this->Translate('Channel %s'), $sceneEndpoint) . ')');
        }
        if ($side === 'Target') {
            $binding = $this->EditorActionVariable($snapshot, $device, $property);
            if ($binding !== null) {
                return $binding['name'] . ' [' . $property . ']';
            }
        }
        $meta = $snapshot['devices'][$device]['properties'][$property] ?? [];
        $label = $meta['label'] ?? $property;
        $label = $this->Translate(match ($label) {
            'state' => 'State', 'action' => 'Button event', 'occupancy' => 'Motion', 'contact' => 'Contact', default => $label
        });
        $endpoint = $meta['endpoint'] ?? '';
        if ($endpoint !== '') {
            $endpoint = preg_match('/\Al[0-9]+\z/D', $endpoint) ? strtoupper($endpoint) : $endpoint;
            return $label . ' (' . sprintf($this->Translate('Channel %s'), $endpoint) . ')';
        }
        return $label;
    }

    private function EditorValueAllowed(array $snapshot, string $device, string $property, mixed $value, string $side): bool
    {
        $meta = $this->EditorProperties($snapshot, $device, $side)[$property] ?? null;
        if ($meta === null) {
            return false;
        }
        if ($meta['type'] === 'color') {
            return $side === 'Target' && is_array($value) && array_keys($value) === ['hex'] && is_string($value['hex'])
                && preg_match('/\A#[A-Fa-f0-9]{6}\z/D', $value['hex']) === 1;
        }
        if ($meta['type'] !== 'numeric') {
            return in_array($value, $meta['values'], true);
        }
        // A source comparison threshold is not a device command: it may lie between
        // report steps or outside the sensor's measurement range.
        if ($side === 'Source') {
            return (is_int($value) || is_float($value)) && is_finite((float) $value);
        }
        if (!(is_float($value) || is_int($value)) || !is_finite((float) $value)
            || (isset($meta['minimum']) && $value < $meta['minimum']) || (isset($meta['maximum']) && $value > $meta['maximum'])) {
            return false;
        }
        if (isset($meta['step'])) {
            $step = ($value - ($meta['minimum'] ?? 0)) / $meta['step'];
            if (abs($step - round($step)) > 0.00000001 * max(1, abs($step))) {
                return false;
            }
        }
        return true;
    }

    private function EditorValueKey(array $snapshot, string $device, string $property, mixed $value, string $side): ?string
    {
        if (!$this->EditorValueAllowed($snapshot, $device, $property, $value, $side)) {
            return null;
        }
        foreach ($snapshot['devices'][$device][$side === 'Target' ? 'actions' : 'triggers'] ?? [] as $key => $choice) {
            if ($choice['property'] === $property && $choice['value'] === $value) {
                return $key;
            }
        }
        return 'value.' . rtrim(strtr(base64_encode(json_encode(['property' => $property, 'value' => $value], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
    }

    private function DecodeEditorValue(array $snapshot, string $device, string $key, string $side): ?array
    {
        if (!str_starts_with($key, 'value.') || strlen($key) > 2048) {
            return null;
        }
        $raw = base64_decode(strtr(substr($key, 6), '-_', '+/'), true);
        $choice = $raw === false ? null : json_decode($raw, true, 8);
        return is_array($choice) && count($choice) === 2 && is_string($choice['property'] ?? null) && array_key_exists('value', $choice)
            && $this->EditorValueAllowed($snapshot, $device, $choice['property'], $choice['value'], $side) ? $choice : null;
    }
}
