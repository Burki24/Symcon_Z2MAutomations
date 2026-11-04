<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;

trait DeviceCatalog
{
    private function ReadDeviceCatalog(object $data): ?array
    {
        if (!is_array($data->devices ?? null) || count($data->devices) > 256) return null;
        $devices = [];
        foreach ($data->devices as $device) {
            if (!$device instanceof stdClass || !is_string($device->id ?? null) || preg_match('/\A0x[a-f0-9]{16}\z/D', $device->id) !== 1
                || !is_string($device->name ?? null) || strlen($device->name) > 480 || isset($devices[$device->id])) {
                return null;
            }
            $entry = ['name' => $device->name];
            foreach (['triggers', 'actions'] as $kind) {
                if (!is_array($device->$kind ?? null) || count($device->$kind) > 64) {
                    return null;
                }
                $entry[$kind] = [];
                foreach ($device->$kind as $option) {
                    if (!$option instanceof stdClass || !is_string($option->property ?? null)
                        || preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/D', $option->property) !== 1
                        || in_array($option->property, ['constructor', 'prototype', '__proto__'], true) || !property_exists($option, 'value')
                        || !(is_scalar($option->value) || $option->value === null) || (is_string($option->value) && strlen($option->value) > 1024)) {
                        return null;
                    }
                    $value = ['property' => $option->property, 'value' => $option->value];
                    $key = hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
                    $entry[$kind][$key] = $value;
                }
            }
            $entry['properties'] = $this->ReadEditorProperties($device, $entry);
            if ($entry['properties'] === null) {
                return null;
            }
            $devices[$device->id] = $entry;
            $devices[$device->id]['numeric'] = [];
            if (!is_array($device->numeric ?? []) || count($device->numeric ?? []) > 64) {
                return null;
            }
            foreach ($device->numeric ?? [] as $number) {
                if (!$number instanceof stdClass || !is_string($number->property ?? null) || preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/D', $number->property) !== 1
                    || in_array($number->property, ['constructor', 'prototype', '__proto__'], true) || !is_string($number->unit ?? null) || strlen($number->unit) > 96) {
                    return null;
                }
                $devices[$device->id]['numeric'][$number->property] = $number->unit;
            }
        }
        $groups = $this->ReadEditorGroups($data);
        $scenes = $groups === null ? null : $this->ReadEditorScenes($data, $devices, $groups);
        if ($groups === null || $scenes === null) {
            return null;
        }
        return [$devices, $groups, $scenes];
    }

    private function ReadEditorProperties(object $device, array $entry): ?array
    {
        $properties = [];
        if (isset($device->properties)) {
            if (!is_array($device->properties) || count($device->properties) > 64) {
                return null;
            }
            foreach ($device->properties as $p) {
                if (!is_object($p) || !is_string($p->property ?? null) || preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/D', $p->property) !== 1
                    || in_array($p->property, ['constructor', 'prototype', '__proto__'], true) || isset($properties[$p->property])
                    || !in_array($p->type ?? '', ['binary', 'enum', 'numeric', 'color'], true) || !is_int($p->access ?? null) || $p->access < 1 || $p->access > 3
                    || (($p->type ?? '') === 'color' && ($p->access !== 2 || preg_match('/\Acolor(?:_[A-Za-z0-9_]+)?\z/D', $p->property) !== 1 || ($p->values ?? []) !== []))
                    || !is_string($p->label ?? null) || strlen($p->label) > 320 || preg_match('/[\x00-\x1f\x7f]/', $p->label)
                    || !is_string($p->endpoint ?? null) || ($p->endpoint !== '' && preg_match('/\A[A-Za-z0-9_-]{1,32}\z/D', $p->endpoint) !== 1)
                    || !is_array($p->values ?? null) || count($p->values) > 32) {
                    return null;
                }
                $meta = ['property' => $p->property, 'type' => $p->type, 'access' => $p->access, 'label' => $p->label, 'endpoint' => $p->endpoint, 'values' => $p->values];
                foreach ($p->values as $value) {
                    if (!(is_scalar($value) || $value === null) || (is_string($value) && strlen($value) > 1024)) {
                        return null;
                    }
                }
                foreach (['minimum', 'maximum', 'step'] as $key) {
                    if (isset($p->$key)) {
                        if (!(is_float($p->$key) || is_int($p->$key)) || !is_finite((float) $p->$key) || ($key === 'step' && $p->$key <= 0)) {
                            return null;
                        }
                        $meta[$key] = $p->$key;
                    }
                }
                if (isset($meta['minimum'], $meta['maximum']) && $meta['minimum'] > $meta['maximum']) {
                    return null;
                }
                $properties[$p->property] = $meta;
            }
        } else {
            foreach (['triggers' => 1, 'actions' => 2] as $kind => $access) {
                foreach ($entry[$kind] as $choice) {
                    $property = $choice['property'];
                    $properties[$property] ??= ['property' => $property, 'type' => 'enum', 'access' => 0, 'label' => $property, 'endpoint' => '', 'values' => []];
                    $properties[$property]['access'] |= $access;
                    if (!in_array($choice['value'], $properties[$property]['values'], true)) {
                        $properties[$property]['values'][] = $choice['value'];
                    }
                }
            }
        }
        return $properties;
    }

    private function ReadEditorGroups(object $data): ?array
    {
        if (!is_array($data->groups ?? []) || count($data->groups ?? []) > 256) {
            return null;
        }
        $groups = [];
        foreach ($data->groups ?? [] as $group) {
            if (!is_object($group) || !is_int($group->id ?? null) || $group->id < 1 || $group->id > 65535
                || !is_string($group->name ?? null) || $group->name === '' || strlen($group->name) > 480
                || preg_match('/[\x00-\x1f\x7f+#]/', $group->name) || isset($groups[$group->id])) {
                return null;
            }
            $groups[$group->id] = $group->name;
        }
        return $groups;
    }

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

}
