<?php

declare(strict_types=1);

namespace Z2MAutomations;

final class RuntimeDebugContract
{
    public static function UUID(mixed $value): bool
    {
        return is_string($value) && preg_match('/\A[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}\z/D', $value) === 1;
    }

    public static function Catalog(): array
    {
        static $catalog = null;
        return $catalog ??= json_decode(file_get_contents(__DIR__ . '/z2m/debug-events.json'), true, 8, JSON_THROW_ON_ERROR);
    }

    public static function Record(mixed $payload, array $lease, int $now): ?array
    {
        if (!$payload instanceof \stdClass || ($payload->protocol_version ?? null) !== 1 || ($payload->debug_version ?? null) !== 1
            || ($payload->session ?? null) !== $lease['session'] || !is_array($payload->clients ?? null) || count($payload->clients) > 8
            || !in_array($lease['client'], $payload->clients, true) || count(get_object_vars($payload)) !== 6
            || !is_int($payload->dropped ?? null) || $payload->dropped < 0 || $payload->dropped > 1000000 || !($payload->record ?? null) instanceof \stdClass) {
            return null;
        }
        foreach ($payload->clients as $client) {
            if (!self::UUID($client)) {
                return null;
            }
        }
        $record = get_object_vars($payload->record);
        if (!self::UUID($record['id'] ?? null) || !is_int($record['at'] ?? null) || abs($record['at'] / 1000 - $now) > 120
            || !is_string($record['event'] ?? null) || !isset(self::Catalog()[$record['event']])
            || array_diff(array_keys($record), ['id', 'at', 'event', 'run', 'origin', 'rule', 'trigger', 'condition', 'step', 'revision', 'sequence', 'sequence_step', 'delay', 'passed', 'reason']) !== []) {
            return null;
        }
        foreach (['run' => '/\A[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}\z/D', 'origin' => '/\A[a-f0-9]{64}\z/D'] as $key => $pattern) {
            if (array_key_exists($key, $record) && (!is_string($record[$key]) || preg_match($pattern, $record[$key]) !== 1)) {
                return null;
            }
        }
        foreach (['sequence' => 32, 'sequence_step' => 16, 'rule' => 100, 'trigger' => 8, 'condition' => 16, 'step' => 16, 'revision' => 9007199254740991, 'delay' => 86400] as $key => $maximum) {
            if (array_key_exists($key, $record) && (!is_int($record[$key]) || $record[$key] < ($key === 'delay' ? 0 : 1) || $record[$key] > $maximum)) {
                return null;
            }
        }
        if (array_key_exists('passed', $record) && !is_bool($record['passed'])) {
            return null;
        }
        if (array_key_exists('reason', $record) && !in_array($record['reason'], ['storage_error', 'inactive', 'already_running', 'concurrency_limit', 'cooldown', 'conditions_not_met', 'action_failed', 'action_timeout', 'unknown_entity', 'state_unavailable', 'rate_limited', 'scene_unavailable', 'mesh_timeout', 'mesh_unavailable', 'mesh_not_allowed', 'mesh_stopped', 'mesh_rejected', 'mesh_invalid', 'mesh_busy'], true)) {
            return null;
        }
        return $record + ['dropped' => $payload->dropped];
    }

    public static function Reason(string $reason): string
    {
        return match ($reason) {
            'storage_error'                             => 'Der Fortsetzungspunkt konnte nicht sicher gespeichert werden. Der Lauf wurde beendet.',
            'inactive', 'mesh_stopped'                   => 'Ausführung gestoppt.',
            'already_running'                            => 'Diese Automation läuft bereits.',
            'concurrency_limit'                          => 'Zu viele Automationen laufen gleichzeitig.',
            'cooldown'                                   => 'Kurze Sperrzeit nach dem letzten Start ist noch aktiv.',
            'conditions_not_met'                         => 'Mindestens eine Bedingung ist nicht erfüllt.',
            'mesh_timeout'                               => 'Das zweite Z2M-System hat nicht rechtzeitig geantwortet.',
            'scene_unavailable'                          => 'Die ausgewählte Szene ist nicht mehr verfügbar.',
            'unknown_entity'                             => 'Das benötigte Gerät oder die Gruppe ist nicht verfügbar.',
            'state_unavailable'                          => 'Der aktuelle Gerätezustand zum Umschalten fehlt.',
            'rate_limited'                               => 'Zu viele Gerätebefehle innerhalb kurzer Zeit.',
            'mesh_unavailable'                           => 'Die Verbindung zum zweiten Z2M-System fehlt.',
            'mesh_not_allowed'                           => 'Die Aktion ist zwischen diesen Z2M-Systemen nicht freigegeben.',
            'mesh_rejected', 'mesh_invalid', 'mesh_busy' => 'Das zweite Z2M-System hat die Aktion abgelehnt.',
            default                                      => 'Die Befehlsübergabe ist fehlgeschlagen oder hat zu lange gedauert.'
        };
    }
}
