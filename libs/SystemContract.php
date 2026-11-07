<?php

declare(strict_types=1);

namespace Z2MAutomations;

use InvalidArgumentException;

/** Local management identities. No registration, module creation or MQTT side effects. */
final class SystemContract
{
    public const VERSION = 1;
    public const MAX_SYSTEMS = 8;
    public const SOURCE_MODULE = '{23D57681-8424-48DC-9FF0-25C110C533A9}';
    public const CONNECTION_MODULE = '{BF8E1981-7C41-4F2B-BCBB-1744A5D7D5FC}';
    public const CONNECTION_PREFIX = 'Z2MC';

    public static function SystemID(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}\z/D', $value) === 1;
    }

    public static function Topic(mixed $value): bool
    {
        return is_string($value) && strlen($value) <= 128 && preg_match('/\A[A-Za-z0-9_-]+(?:\/[A-Za-z0-9_-]+)*\z/D', $value) === 1;
    }

    public static function Role(mixed $marker, bool $hasLegacyData): string
    {
        if ($marker === null) return $hasLegacyData ? 'legacy' : 'unassigned';
        return is_array($marker) && ($marker['version'] ?? null) === self::VERSION && in_array($marker['role'] ?? '', ['legacy', 'manager', 'connection'], true)
            ? $marker['role'] : 'unsupported';
    }

    public static function RegistryError(array $systems): string
    {
        if (count($systems) > self::MAX_SYSTEMS) return 'system_limit';
        if (!array_is_list($systems)) return 'invalid_registry';
        $seen = ['systemId' => [], 'baseTopic' => [], 'sourceInstance' => [], 'connectionInstance' => []];
        $owner = null;
        foreach ($systems as $record) {
            if (!is_array($record) || count($record) !== 8 || !self::SystemID($record['systemId'] ?? null) || !self::Topic($record['baseTopic'] ?? null)) return 'invalid_system';
            foreach (['sourceInstance', 'connectionInstance', 'mqttParent', 'ownerInstance', 'generation', 'systemId', 'baseTopic', 'version'] as $key) {
                if (!array_key_exists($key, $record)) return 'invalid_system';
            }
            if ($record['version'] !== self::VERSION) return 'unsupported_version';
            foreach (['sourceInstance', 'connectionInstance', 'mqttParent', 'ownerInstance'] as $key) {
                if (!is_int($record[$key]) || $record[$key] < (in_array($key, ['sourceInstance', 'connectionInstance'], true) ? 0 : 10000) || $record[$key] > 59999) return 'invalid_system';
                if ($record[$key] > 0 && $record[$key] < 10000) return 'invalid_system';
            }
            if (!is_int($record['generation']) || $record['generation'] < 1 || $record['generation'] > 9007199254740991) return 'invalid_generation';
            if ($owner !== null && $owner !== $record['ownerInstance']) return 'owner_conflict';
            $owner = $record['ownerInstance'];
            foreach ($seen as $key => &$values) {
                if ($record[$key] === 0) continue;
                if (isset($values[$record[$key]])) return 'duplicate_' . $key;
                $values[$record[$key]] = true;
            }
            unset($values);
        }
        return '';
    }

    public static function BindingMatches(array $system, int $owner, string $systemID, int $generation): bool
    {
        return self::RegistryError([$system]) === '' && $system['ownerInstance'] === $owner && $system['systemId'] === $systemID && $system['generation'] === $generation;
    }

    public static function EntityKey(string $systemID, string $kind, string $identity): string
    {
        $valid = match ($kind) {
            'device'           => preg_match('/\A0x[a-f0-9]{16}\z/D', $identity) === 1,
            'group'            => ctype_digit($identity) && (string) (int) $identity === $identity && (int) $identity >= 1 && (int) $identity <= 65535,
            'rule', 'sequence' => preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $identity) === 1,
            default            => false
        };
        if (!self::SystemID($systemID) || !$valid) throw new InvalidArgumentException('invalid_entity');
        return json_encode([$systemID, $kind, $identity], JSON_THROW_ON_ERROR);
    }
}
