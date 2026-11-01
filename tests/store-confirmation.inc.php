<?php

declare(strict_types=1);

$beforeStoredStatus = $remote + ['runtime_version' => 1, 'active_revision' => 2, 'active_checksum' => $remote['checksum'], 'enabled_count' => 0, 'runtime_error' => '',
    'mesh_version'                                 => 1, 'mesh_ready' => true, 'mesh_peer_count' => 1, 'resume_version' => 1, 'resume_enabled' => true, 'resume_status' => 'armed', 'resume_error' => ''];
$beforeStoredStatus['executing'] = true;
$afterStoredStatus = array_replace($beforeStoredStatus, ['stored_revision' => 3, 'checksum' => hash('sha256', $source), 'executing' => false,
    'active_revision'                                                      => null, 'active_checksum' => null, 'enabled_count' => 0, 'mesh_ready' => false, 'resume_status' => 'stopped']);
$storeAck = ['stored_revision' => 3, 'checksum' => hash('sha256', $source), 'automation_count' => 0, 'storage' => 'ok', 'executing' => false, 'unchanged' => false];
$beginVerifiedStore = static function () use ($controlled, $source, $replyStatus, $beforeStoredStatus, $answer, $storeAck, $check, $last, $controlledState): void
{
    $controlled->ValidateYaml($source);
    $replyStatus($beforeStoredStatus);
    $answer(['protocol_version' => 1, 'status' => 'ok', 'data' => (object) ['valid' => true, 'revision' => 3,
        'checksum'                                                                  => hash('sha256', $source), 'automation_count' => 0, 'executing' => false]]);
    $controlled->StoreYaml($source);
    $replyStatus($beforeStoredStatus);
    $answer(['protocol_version' => 1, 'status' => 'ok', 'data' => (object) $storeAck]);
    $check(str_ends_with($last()['Topic'], '/request/status') && $controlledState()['operation']['status'] === 'pending'
        && !$controlledState()['runtimeConfirmed'], 'A valid store acknowledgement automatically requests fresh runtime status before enabling activation');
};
$beginVerifiedStore();
$pendingStoredFields = formFields(json_decode($controlled->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(
    !$pendingStoredFields['ActivateButton']['enabled'] && str_contains($pendingStoredFields['OperationInfo']['caption'], 'confirming'),
    'Store confirmation is explicitly pending and activation stays disabled'
);
$parent->publish('synthetic/z2m/symcon/automations/status', (object) $afterStoredStatus, true);
$check($controlledState()['operation']['status'] === 'pending' && !$controlledState()['runtimeConfirmed'], 'Retained storage status cannot complete the fresh confirmation');
$replyStatus($afterStoredStatus);
$confirmedStoredFields = formFields(json_decode($controlled->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(
    $controlledState()['operation']['status'] === 'stored' && $controlledState()['runtimeConfirmed'] && $confirmedStoredFields['ActivateButton']['enabled'],
    'Fresh matching storage status enables activation without another manual status request'
);
$parent->publish('synthetic/z2m/symcon/automations/status', (object) $afterStoredStatus, true);
$check($controlledState()['runtimeConfirmed'] && $controlledState()['extensionConfirmed'], 'Identical retained follow-up no longer invalidates the fully refreshed metadata');
$check(
    str_contains($confirmedStoredFields['ValidationInfo']['caption'], 'already stored') && !str_contains($confirmedStoredFields['ValidationInfo']['caption'], 'No current'),
    'The saved unchanged draft is recognised even though its one-use validation receipt was consumed'
);
$idempotentStatus = array_replace($afterStoredStatus, ['executing' => true, 'active_revision' => 3, 'active_checksum' => hash('sha256', $source), 'mesh_ready' => true, 'resume_status' => 'armed']);
$controlled->ValidateYaml($source);
$replyStatus($idempotentStatus);
$answer(['protocol_version' => 1, 'status' => 'ok', 'data' => (object) ['valid' => true, 'revision' => 3,
    'checksum'                                                                  => hash('sha256', $source), 'automation_count' => 0, 'executing' => false]]);
$beforeIdempotentPackets = count($parent->sent);
$controlled->StoreYaml($source);
$replyStatus($idempotentStatus);
$answer(['protocol_version' => 1, 'status' => 'ok', 'data' => (object) array_replace($storeAck, ['executing' => true, 'unchanged' => true])]);
$replyStatus($idempotentStatus);
$check(
    $controlledState()['operation']['status'] === 'stored' && $controlledState()['runtimeConfirmed'] && $controlledState()['extension']['executing'],
    'Unchanged storage confirms and preserves the already active runtime'
);
$idempotentPackets = array_slice($parent->sent, $beforeIdempotentPackets);
$check(
    count($idempotentPackets) === 3 && array_map(static fn (array $p): string => basename($p['Topic']), $idempotentPackets) === ['status', 'store', 'status'],
    'Automatic post-store confirmation never sends activate, deactivate or a second store'
);

foreach ([
    ['session' => '22222222-2222-4222-8222-222222222222'],
    ['stored_revision' => 4], ['checksum' => str_repeat('a', 64)], ['automation_count' => 1], ['storage' => 'recovered_read_only'],
    ['executing' => true, 'active_revision' => 3, 'active_checksum' => hash('sha256', $source)]
] as $mutation) {
    $beginVerifiedStore();
    $replyStatus(array_replace($afterStoredStatus, $mutation));
    $failedFields = formFields(json_decode($controlled->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
    $check(
        $controlledState()['operation']['status'] === 'stored_unconfirmed' && !$controlledState()['runtimeConfirmed'] && !$failedFields['ActivateButton']['enabled'],
        'A different session, revision, checksum, storage mode or execution state cannot confirm the stored snapshot'
    );
}
$beginVerifiedStore();
$countBeforeStoreTimeout = count($parent->sent);
$controlled->clock += 11;
$controlled->ExpireRequest();
$timeoutStoreFields = formFields(json_decode($controlled->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(
    $controlledState()['operation']['status'] === 'stored_unconfirmed' && $controlledState()['lastError'] === 'timeout'
    && !$timeoutStoreFields['ActivateButton']['enabled'] && count($parent->sent) === $countBeforeStoreTimeout,
    'A confirmation timeout reports that storage succeeded but status remains unknown, with no repeated write'
);
$replyStatus($afterStoredStatus);
$check($controlledState()['operation']['status'] === 'stored_unconfirmed', 'Late status cannot complete the expired confirmation');
$controlled->RequestExtensionStatus();
$replyStatus($afterStoredStatus);
$check($controlledState()['runtimeConfirmed'], 'A later explicit read-only status request can recover the runtime confirmation');
$newDraft = str_replace('revision: 3', 'revision: 4', $source);
$controlled->ValidateYaml($newDraft);
$replyStatus($afterStoredStatus);
$answer(['protocol_version' => 1, 'status' => 'error', 'error' => (object) ['code' => 'invalid_schema']]);
$changedDraftFields = formFields(json_decode($controlled->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(!str_contains($changedDraftFields['ValidationInfo']['caption'], 'already stored'), 'Edited draft bytes cannot inherit the saved-draft message');
