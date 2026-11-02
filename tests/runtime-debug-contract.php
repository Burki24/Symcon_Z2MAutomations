<?php

declare(strict_types=1);

require __DIR__ . '/../libs/RuntimeDebugContract.php';

use Z2MAutomations\RuntimeDebugContract;

$client = '11111111-1111-4111-8111-111111111111';
$session = '22222222-2222-4222-8222-222222222222';
$lease = ['client' => $client, 'session' => $session];
$valid = ['protocol_version' => 1, 'debug_version' => 1, 'session' => $session, 'clients' => [$client], 'dropped' => 0,
    'record'                 => ['id' => $client, 'at' => 1800000000000, 'event' => 'event_35', 'run' => $client, 'origin' => str_repeat('a', 64),
        'rule'                        => 1, 'trigger' => 8, 'condition' => 16, 'step' => 16, 'revision' => 1, 'delay' => 86400, 'passed' => false, 'reason' => 'conditions_not_met']];
$decode = static fn (array $data): object => json_decode(json_encode($data, JSON_THROW_ON_ERROR), false, 16, JSON_THROW_ON_ERROR);
$check = static function (bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
};
$check(RuntimeDebugContract::Record($decode($valid), $lease, 1800000000) !== null, 'Valid bounded diagnostic record is accepted');
foreach (['session' => $client, 'clients' => [], 'dropped' => '0', 'protocol_version' => 2, 'debug_version' => false, 'record' => null, 'message' => 'unsafe'] as $key => $value) {
    $check(RuntimeDebugContract::Record($decode(array_replace($valid, [$key => $value])), $lease, 1800000000) === null, 'Invalid envelope field: ' . $key);
}
foreach (['id'  => 'unsafe', 'at' => 1800000121000, 'event' => 'unsafe', 'run' => null, 'origin' => '../unsafe', 'rule' => 0, 'trigger' => 9,
    'condition' => 17, 'step' => 17, 'revision' => 0, 'delay' => -1, 'passed' => 1, 'reason' => 'unsafe', 'message' => 'unsafe'] as $key => $value) {
    $record = $valid;
    $record['record'][$key] = $value;
    $check(RuntimeDebugContract::Record($decode($record), $lease, 1800000000) === null, 'Invalid record field: ' . $key);
}
$record = $valid;
unset($record['record']);
$check(RuntimeDebugContract::Record($decode($record), $lease, 1800000000) === null, 'Missing record is rejected without a property warning');
$check(str_contains(RuntimeDebugContract::Reason('mesh_timeout'), 'nicht rechtzeitig'), 'Fixed mesh reason is understandable');
echo "Strict runtime debug contract checks passed.\n";
