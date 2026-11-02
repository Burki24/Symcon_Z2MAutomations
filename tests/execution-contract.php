<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ExtensionContract.php';
require_once __DIR__ . '/../libs/RuntimeDebugContract.php';
require_once __DIR__ . '/../libs/ExecutionContract.php';

use Z2MAutomations\ExecutionContract;

$snapshot = ['protocol_version' => 1, 'execution_version' => 1, 'session' => '11111111-1111-4111-8111-111111111111',
    'revision'                  => 1, 'checksum' => str_repeat('a', 64), 'sequence' => 2, 'runs' => [['rule_id' => 'synthetic_rule',
        'run'                                                                                                   => '22222222-2222-4222-8222-222222222222', 'revision' => 1, 'checksum' => str_repeat('a', 64), 'state' => 'running', 'error' => '', 'step' => 1]]];
$parse = static fn (array $value): ?array => ExecutionContract::Snapshot(json_decode(json_encode($value, JSON_THROW_ON_ERROR), false, 16, JSON_THROW_ON_ERROR));
if (($parse($snapshot)['runs']['synthetic_rule']['state'] ?? '') !== 'running') {
    throw new RuntimeException('Valid bounded execution status must parse.');
}
foreach (['running', 'completed', 'skipped', 'failed', 'stopped'] as $state) {
    $value = $snapshot;
    $value['runs'][0]['state'] = $state;
    if ($parse($value) === null) {
        throw new RuntimeException('Supported execution state must parse.');
    }
}
$invalid = [];
foreach (['session' => 'invalid', 'sequence' => -1, 'revision' => 0, 'checksum' => null, 'protocol_version' => 2, 'extra' => 'private'] as $key => $value) {
    $candidate = $snapshot;
    $candidate[$key] = $value;
    $invalid[] = $candidate;
}
foreach (['run' => 'invalid', 'rule_id' => '../private', 'revision' => 2, 'checksum' => str_repeat('b', 64), 'state' => 'unknown', 'step' => 17,
    'error'     => 'private-error-body', 'extra' => 'private'] as $key => $value) {
    $candidate = $snapshot;
    $candidate['runs'][0][$key] = $value;
    $invalid[] = $candidate;
}
$candidate = $snapshot;
$candidate['runs'][] = $candidate['runs'][0];
$invalid[] = $candidate;
foreach ($invalid as $candidate) {
    if ($parse($candidate) !== null) {
        throw new RuntimeException('Malformed or arbitrary execution data must be rejected.');
    }
}
echo "Execution status bounds, identity and safe state contract passed.\n";
