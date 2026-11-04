<?php

declare(strict_types=1);

namespace Z2MAutomations;

use Throwable;

require_once __DIR__ . '/Z2MProtocol.php';

/** Native identity adapter shared by the existing editor and the workspace. */
final class Z2MInstanceIdentity
{
    public const TYPES = [
        '{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}' => 'device',
        '{11BF3773-E940-469B-9DD7-FB9ACD7199A2}' => 'group',
        '{00160D82-9E2F-D1BD-6D0B-952F945332C5}' => 'bridge'
    ];

    public static function Read(int $id, int $gateway, ?string $topic = null): ?array
    {
        if ($id <= 0 || $gateway <= 0 || !IPS_InstanceExists($id) || !IPS_InstanceExists($gateway)) return null;
        try {
            $instance = IPS_GetInstance($id);
            $type = self::TYPES[$instance['ModuleInfo']['ModuleID'] ?? ''] ?? null;
            if ($type === null || $instance['ConnectionID'] !== $gateway) return null;
            $configuration = json_decode(IPS_GetConfiguration($id), true, 32, JSON_THROW_ON_ERROR);
            $base = $configuration['MQTTBaseTopic'] ?? null;
            if (!is_string($base) || !Z2MProtocol::IsValidBaseTopic($base) || ($topic !== null && $base !== $topic)) return null;
            $key = '';
            if ($type === 'device') {
                $key = $configuration['IEEE'] ?? null;
                if (!is_string($key) || preg_match('/\A0x[a-f0-9]{16}\z/D', $key) !== 1) return null;
            } elseif ($type === 'group') {
                $group = $configuration['GroupId'] ?? null;
                if (!is_int($group) || $group < 1 || $group > 65535) return null;
                $key = 'group.' . $group;
            }
            return ['type' => $type, 'key' => $key, 'topic' => $base];
        } catch (Throwable) {
            return null;
        }
    }
}
