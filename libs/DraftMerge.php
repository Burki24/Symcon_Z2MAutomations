<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** Three-way reconciliation by stable rule/sequence identity; never performs I/O. */
final class DraftMerge
{
    public static function SafeConfiguration(mixed $value, int $depth = 0): bool
    {
        if ($depth > 16) return false;
        if (is_string($value)) return strlen($value) <= 16384;
        if (!is_array($value)) return $value === null || is_bool($value) || is_int($value) || (is_float($value) && is_finite($value));
        if (count($value) > 512) return false;
        foreach ($value as $key => $item) {
            if (is_string($key) && preg_match('/\A(?:__proto__|prototype|constructor|password|username|credentials?|(?:access_?|refresh_?|owner_?)?token|session(?:_?id)?|instance_?id|object_?id|variable_?id|private_?key|certificate|symcon.*)\z/iD', $key) === 1) return false;
            if (!self::SafeConfiguration($item, $depth + 1)) return false;
        }
        return true;
    }

    public static function Normalize(array $configuration): array
    {
        return $configuration + ['schema_version' => 1, 'revision' => 1, 'automations' => [], 'sequences' => [], 'mesh_peers' => []];
    }

    public static function Same(mixed $left, mixed $right): bool
    {
        $normalize = static function (mixed $value) use (&$normalize): mixed
        {
            if (!is_array($value)) return $value;
            if (!array_is_list($value)) ksort($value);
            return array_map($normalize, $value);
        };
        return json_encode($normalize($left), JSON_THROW_ON_ERROR) === json_encode($normalize($right), JSON_THROW_ON_ERROR);
    }

    public static function Plan(array $local, array $remote, ?array $base = null, array $choices = []): array
    {
        $local = self::Normalize($local);
        $remote = self::Normalize($remote);
        $base = $base === null ? null : self::Normalize($base);
        $conflicts = [];
        $choose = static function (string $key, string $kind, string $id, mixed $before, mixed $ours, mixed $theirs, bool $known) use ($choices, &$conflicts): mixed
        {
            if (($choices[$key] ?? '') === 'local') return $ours;
            if (($choices[$key] ?? '') === 'remote') return $theirs;
            if (self::Same($ours, $theirs)) return $ours;
            if ($known && self::Same($ours, $before)) return $theirs;
            if ($known && self::Same($theirs, $before)) return $ours;
            if (!$known && $ours === null) return $theirs;
            if (!$known && $theirs === null) return $ours;
            $conflicts[] = ['key' => $key, 'kind' => $kind, 'id' => $id, 'name' => $ours['name'] ?? $theirs['name'] ?? $id, 'local' => $ours, 'remote' => $theirs, 'base' => $before];
            return $ours;
        };
        $result = $local;
        $result['revision'] = max(1, $local['revision'], $remote['revision']);
        foreach (['automations', 'sequences'] as $kind) {
            $before = array_column($base[$kind] ?? [], null, 'id');
            $ours = array_column($local[$kind], null, 'id');
            $theirs = array_column($remote[$kind], null, 'id');
            $result[$kind] = [];
            foreach (array_unique([...array_keys($ours), ...array_keys($theirs), ...array_keys($before)]) as $id) {
                $value = $choose($kind . ':' . $id, $kind, (string) $id, $before[$id] ?? null, $ours[$id] ?? null, $theirs[$id] ?? null, $base !== null);
                if ($value !== null) $result[$kind][] = $value;
            }
        }
        $sequences = array_column($result['sequences'], null, 'id');
        foreach ($result['automations'] as $rule) foreach (is_array($rule['actions'] ?? null) ? $rule['actions'] : [] as $action) {
            if (($action['type'] ?? '') !== 'sequence' || !is_string($action['sequence_id'] ?? null) || isset($sequences[$action['sequence_id']])) continue;
            $id = $action['sequence_id'];
            $key = 'sequences:' . $id;
            $ours = array_column($local['sequences'], null, 'id')[$id] ?? null;
            $theirs = array_column($remote['sequences'], null, 'id')[$id] ?? null;
            if (($ours === null && $theirs === null) || in_array($choices[$key] ?? '', ['local', 'remote'], true) || in_array($key, array_column($conflicts, 'key'), true)) continue;
            $conflicts[] = ['key' => $key, 'kind' => 'sequences', 'id' => $id, 'name' => ($ours ?? $theirs)['name'], 'local' => $ours, 'remote' => $theirs,
                'base'            => array_column($base['sequences'] ?? [], null, 'id')[$id] ?? null, 'dependency' => true];
            $result['sequences'][] = $ours ?? $theirs;
            $sequences[$id] = $ours ?? $theirs;
        }
        $ours = $local['mesh_peers'];
        $theirs = $remote['mesh_peers'];
        $before = $base['mesh_peers'] ?? [];
        sort($ours);
        sort($theirs);
        sort($before);
        $result['mesh_peers'] = $choose(
            'mesh_peers',
            'permissions',
            'mesh_peers',
            $base === null ? null : $before,
            $base === null && $ours === [] ? null : $ours,
            $base === null && $theirs === [] ? null : $theirs,
            $base !== null
        ) ?? [];
        return ['configuration' => $result, 'conflicts' => $conflicts];
    }
}
