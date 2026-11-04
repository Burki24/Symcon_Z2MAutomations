<?php

declare(strict_types=1);

namespace Z2MAutomations;

use Throwable;

require_once __DIR__ . '/Z2MInstanceIdentity.php';

final class NativeVariableBindings
{
    public static function Collect(array $instances): array
    {
        $result = [];
        foreach ($instances as $instance => $scope) {
            try {
                if (!IPS_InstanceExists($instance)) continue;
                foreach (IPS_GetChildrenIDs($instance) as $id) {
                    if (!IPS_ObjectExists($id)) continue;
                    $object = IPS_GetObject($id);
                    if ($object['ObjectType'] !== 2 || $object['ObjectIdent'] === '') continue;
                    $property = $object['ObjectIdent'];
                    $key = $scope['key'];
                    $binding = ['id' => $id, 'instance' => $instance, 'name' => $object['ObjectName']];
                    $result[$key][$property] = array_key_exists($property, $result[$key] ?? []) ? null : $binding;
                }
            } catch (Throwable) {
                // A concurrently deleted object cannot supply a usable binding.
            }
        }
        return $result;
    }

    public static function Switchable(int $id, bool $booleanOnly = false): bool
    {
        try {
            if (!IPS_VariableExists($id)) return false;
            $variable = IPS_GetVariable($id);
            if (($variable['VariableIsLocked'] ?? false) || ($booleanOnly && $variable['VariableType'] !== 0)) return false;
            $custom = $variable['VariableCustomAction'];
            $action = $custom !== 0 ? $custom : $variable['VariableAction'];
            return $action > 1 && ($custom > 1 ? IPS_ScriptExists($action) : IPS_InstanceExists($action));
        } catch (Throwable) {
            return false;
        }
    }

    public static function Resolve(array $binding, array $system, string $key, string $property): ?array
    {
        try {
            if (!is_int($binding['id'] ?? null) || !is_int($binding['instance'] ?? null) || !IPS_ObjectExists($binding['id'])) return null;
            $scope = Z2MInstanceIdentity::Read($binding['instance'], $system['mqttParent'], $system['baseTopic']);
            $object = IPS_GetObject($binding['id']);
            return ($scope['key'] ?? null) === $key && $object['ParentID'] === $binding['instance'] && $object['ObjectIdent'] === $property
                && self::Switchable($binding['id'], ($scope['type'] ?? '') === 'group') ? $binding : null;
        } catch (Throwable) {
            return null;
        }
    }
}
