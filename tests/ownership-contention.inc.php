<?php

declare(strict_types=1);

// A different public entry holds the semaphore; no callback may run on rejection.
$attribute = new ReflectionMethod($manager, 'ReadAttributeString');
$buffer = new ReflectionMethod($manager, 'GetBuffer');
$snapshot = static function () use ($manager, $attribute, $buffer): string
{
    $parts = [];
    foreach (['SystemWorkspace', 'SystemOwnership', 'WorkspaceRuntime', 'WorkspaceTransfers', 'WorkspaceSetup', 'YamlDraft', 'EditorDraft'] as $name) $parts[] = $attribute->invoke($manager, $name);
    foreach (['DesignerSessions', 'WorkspaceRuntimeRules', 'EditorWork.rule', 'EditorWork.sequence'] as $name) $parts[] = $buffer->invoke($manager, $name);
    return hash('sha256', json_encode($parts));
};
$packetCount = static fn (): int => array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
$link = $manager->OpenAutomationDesigner();
$token = substr($link, strrpos($link, '#') + 1);
$current = $details($first);
$runtime = $view($first);
$headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $first['systemId'], 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $current['fingerprint']];
$args = [$first['systemId'], 1, $current['fingerprint'], 'activate', '', '', true, $op(), $runtime['context']];
$entryLock = 'Z2MA.Owner.' . $id;
$stateBefore = $snapshot();
$packetsBefore = $packetCount();
$debugStart = count($manager->debug);
$check(IPS_SemaphoreEnter($entryLock, 0), 'A separate entry holds the central instance lock.');
try {
    $busy = $decode($manager->GetWorkspaceOverview());
    $check(!$busy['ok'] && $busy['error'] === 'instance_busy', 'A busy instance is not reported as an ownership conflict.');
    $check($decode($manager->RequestWorkspaceRuntime(...$args))['error'] === 'instance_busy', 'Blocked activation has a distinct busy result.');
    $check($decode($manager->UpdateWorkspaceSystem($first['systemId'], 1, $current['fingerprint'], json_encode($current['data']), $op()))['error'] === 'instance_busy', 'Blocked local writes use the same busy contract.');
    $form = $decode($manager->GetConfigurationForm());
    $check($form['actions'] === [] && str_contains($form['elements'][0]['caption'], 'anderer Vorgang'), 'A busy native form explains the competing entry.');
    $check(!str_contains(json_encode($form), 'Zentrale Verwaltungsinstanz'), 'A busy form does not suggest changing ownership.');
    $manager->RefreshWorkspaceRuntime();
    $check($manager->StartRuntimeDebug(10) === false, 'Boolean and void entries preserve their declared return contracts.');
    foreach (['data', 'runtime-status', 'transfer-status', 'devices'] as $route) {
        [$http, $body] = $manager->Hook($route, $token, $headers);
        $reply = $decode($body);
        $check($http === 409 && !$reply['ok'] && $reply['error'] === 'instance_busy', 'Contended browser reads have an explicit busy response: ' . $route);
    }
    foreach (['activate', 'deactivate', 'run_once', 'stop_rule', 'status'] as $action) {
        $manager->requestBody = json_encode(['action' => $action, 'context' => $runtime['context'], 'requestId' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'rule' => '', 'run' => '', 'resume' => true]);
        [$http, $body] = $manager->Hook('runtime', $token, $headers, 'POST');
        $reply = $decode($body);
        $check($http === 409 && $reply['error'] === 'instance_busy' && str_contains($reply['message'], 'nicht ausgeführt'), 'Every blocked browser command clearly reports that it did not execute: ' . $action);
    }
    $check($snapshot() === $stateBefore && $packetCount() === $packetsBefore && $metadata === $before, 'Contention preserves all documents, permissions, journals, selection and receiver state without MQTT.');
    $check(!IPS_SemaphoreEnter($entryLock, 0), 'Rejected calls do not release the competing caller lock.');
} finally {
    IPS_SemaphoreLeave($entryLock);
}
$busyDebug = implode(' ', array_slice($manager->debug, $debugStart));
$check(str_contains($busyDebug, 'anderer Vorgang') && str_contains($busyDebug, 'instance_busy') && !str_contains($busyDebug, 'lokale Übernahme') && !str_contains($busyDebug, 'Zentrale Verwaltungsinstanz'), 'German busy debug distinguishes contention from migration and owner conflicts.');
$check(!str_contains($busyDebug, $token) && !str_contains($busyDebug, 'PRIVATE_RUNTIME_RULE') && !str_contains($busyDebug, $args[7]), 'Busy debug excludes capabilities, operation identifiers and private rules.');
$check(substr_count($busyDebug, ' | START | ') > 0 && substr_count($busyDebug, ' | START | ') === substr_count($busyDebug, ' | ENDE | '), 'Rejected public entries retain balanced canonical debug flows.');
$check(IPS_SemaphoreEnter($entryLock, 0), 'The owner lock is available after the competing entry finishes.');
IPS_SemaphoreLeave($entryLock);
$check($decode($manager->GetWorkspaceOverview())['ok'], 'A new explicit read succeeds after contention ends.');
$check($packetCount() === $packetsBefore, 'No queued retry or deferred activation occurs when the lock is released.');

// A real foreign owner must retain the ownership diagnosis after acquiring the lock.
$writeAttribute = new ReflectionMethod($manager, 'WriteAttributeString');
$ownerBefore = $attribute->invoke($manager, 'SystemOwnership');
$writeAttribute->invoke($manager, 'SystemOwnership', json_encode(['version' => 1, 'role' => 'legacy', 'owner' => 54321, 'operation' => $op(), 'fingerprint' => str_repeat('a', 64)]));
try {
    $foreign = $decode($manager->GetWorkspaceOverview());
    $check($foreign['error'] === 'ownership_conflict' && str_contains($foreign['message'], '54321') && !str_contains($foreign['message'], 'anderer Vorgang'), 'A genuine foreign owner still identifies the responsible manager.');
    [$http, $body] = $manager->Hook('runtime', $token, $headers, 'POST');
    $check($http === 409 && $decode($body)['error'] === 'ownership_conflict', 'HTTP distinguishes actual ownership denial from temporary contention.');
    $writeAttribute->invoke($manager, 'SystemOwnership', '{PRIVATE_INVALID_OWNER');
    $check($decode($manager->GetWorkspaceOverview())['error'] === 'ownership_conflict', 'Corrupt ownership metadata is not mislabeled as temporary contention.');
} finally {
    $writeAttribute->invoke($manager, 'SystemOwnership', $ownerBefore);
}
$check($snapshot() === $stateBefore && $packetCount() === $packetsBefore, 'Both rejection reasons leave original state and external requests unchanged.');
