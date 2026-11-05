<?php

declare(strict_types=1);

// Integration continuation: real installation/catalog entry points, synthetic MQTT only.
$startWaiting = static function () use ($manager, $request, $read, $first, &$mode, &$held, $check): array
{
    $mode = 'hold_install_status';
    $held = [];
    $check($request($first)['ok'], 'Prepare an installation with delayed readiness confirmation');
    $manager->RefreshWorkspaceInstallations();
    $check($read($first)['status'] === 'pending' && count($held) === 1, 'Only the install readiness reply is held');
    [$parent, $packet] = $held[0];
    $mode = 'auto';
    return [$parent, $packet];
};
$releaseInstall = static function (array $heldStatus) use (&$metadata, $topic, $read, $first, $packets, $check): void
{
    [$parent, $packet] = $heldStatus;
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $metadata[$topic]]);
    $n = $packets();
    $v = $read($first);
    $read($first);
    $check($v['status'] === 'refreshing' && $v['devices']['phase'] === 'waiting' && $packets() === $n, 'GET observes readiness but never starts the automatic catalog request');
};
// An existing explicit request is joined, not replaced or duplicated.
$heldStatus = $startWaiting();
$releaseInstall($heldStatus);
$n = $packets();
$job = $read($first);
$check($request($first, $job['operation'])['ok'] && $packets() === $n, 'Exact replay during device refresh reads status without a second upload or catalog request');
$d = $detail($first);
$before = count($catalogReads);
$check($decode($manager->RequestWorkspaceDeviceCatalog($first, 1, $d['fingerprint'], $op()))['ok'], 'A manual device read can already be in progress');
$finish($first);
$check($read($first)['devices']['phase'] === 'loaded' && count($catalogReads) === $before + 1, 'The installation joins exactly one current device-only read');
// Error and invalid-response cases preserve installation success and never re-upload.
foreach (['invalid_catalog', 'reject_catalog'] as $scenario) {
    $mode = $scenario;
    $beforeUploads = count($uploads);
    $beforeReads = count($catalogReads);
    $check($request($first)['ok'], 'The upload itself is allowed');
    $finish($first);
    $v = $read($first);
    $check($v['status'] === 'installed' && !$v['pending'] && $v['devices']['phase'] === 'error' && str_contains($v['message'], 'nicht aktualisiert'), 'Device failure is reported separately from a successful installation');
    $n = $packets();
    $manager->RefreshWorkspaceInstallations();
    $read($first);
    $check($packets() === $n && count($uploads) === $beforeUploads + 1 && count($catalogReads) === $beforeReads + 1 && $unchanged(), 'Failed device reads do not loop, re-upload or overwrite drafts');
    $mode = 'auto';
    Z2MC_RequestExtensionStatus($connections[0]);
}
// A slow data read stays visible, allows intervening edits, and updates only its own catalog.
$mode = 'auto';
$manager->GetConfigurationForm();
$manager->formUpdates = [];
$beforeReloads = $manager->reloads;
$mode = 'hold_catalog';
$held = [];
$beforeUploads = count($uploads);
$beforeReads = count($catalogReads);
$check($request($first)['ok'], 'Start one delayed automatic refresh');
$manager->RefreshWorkspaceInstallations();
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceInstallations();
$check($read($first)['status'] === 'refreshing' && count($held) === 1, 'The slow catalog remains pending in the installation view');
$originalData = $detail($first)['data'];
$d = $detail($first);
$d['data']['work']['rule']['input']['PRIVATE_DURING_FETCH'] = true;
$check($decode($manager->UpdateWorkspaceSystem($first, 1, $d['fingerprint'], json_encode($d['data']), $op()))['ok'], 'Editing can continue while data is loading');
[$parent, $packet] = $held[0];
$mode = 'auto';
$parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok',
    'data'                                                                                     => $metadata[$topic] + ['devices' => $catalogDevices, 'groups' => [], 'scenes' => []]]);
$finish($first);
$check($detail($first)['data'] === $d['data'] && $read($first)['devices']['phase'] === 'loaded', 'Catalog completion preserves all intervening editor work');
$check(!isset($manager->formUpdates['WorkspaceEditorFingerprint']) && $manager->reloads === $beforeReloads, 'A changed document is not silently rebased or reloaded');
$d = $detail($first);
$check($decode($manager->UpdateWorkspaceSystem($first, 1, $d['fingerprint'], json_encode($originalData), $op()))['ok'], 'Restore only the synthetic edit made by this check');
$check($unchanged() && count($uploads) === $beforeUploads + 1 && count($catalogReads) === $beforeReads + 1, 'All system drafts remain intact after a slow read');
// Without intervening edits native hidden references can follow the new catalog, without reloading inputs.
$manager->GetConfigurationForm();
$manager->formUpdates = [];
$mode = 'auto';
$request($first);
$finish($first);
$check(($manager->formUpdates['WorkspaceEditorFingerprint']['value'] ?? '') === $detail($first)['fingerprint']
    && ($manager->formUpdates['WorkspaceFingerprint']['value'] ?? '') === $overview()['fingerprint'] && $manager->reloads === $beforeReloads, 'Native form references are updated only when their document is unchanged');
// The existing catalog timeout is retained; nothing is retried automatically.
$mode = 'hold_catalog';
$held = [];
$request($first);
$manager->RefreshWorkspaceInstallations();
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->clock += 100;
$connection->clock += 100;
$beforeUploads = count($uploads);
$beforeReads = count($catalogReads);
$finish($first);
$check($read($first)['devices']['phase'] === 'error' && str_contains($read($first)['devices']['message'], 'Wartezeit'), 'An expired catalog read has a useful error without losing the install result');
$check(count($uploads) === $beforeUploads && count($catalogReads) === $beforeReads && $unchanged(), 'Catalog timeout never resends the upload or changes documents');
$mode = 'auto';
Z2MC_ExpireRequest($connections[0]);
Z2MC_RequestExtensionStatus($connections[0]);
// A new receiver session during the read cannot complete the old follow-up.
$mode = 'hold_catalog';
$held = [];
$request($first);
$manager->RefreshWorkspaceInstallations();
$manager->RefreshWorkspaceDeviceCatalogs();
$metadata[$topic]['session'] = '33333333-3333-4333-8333-333333333333';
$parents[0]->publish($topic . '/symcon/automations/status', $metadata[$topic]);
$beforePackets = $packets();
$manager->RefreshWorkspaceInstallations();
$check($read($first)['devices']['phase'] === 'error' && $packets() === $beforePackets && $unchanged(), 'Another receiver session cannot complete or repeat the old automatic read');
$mode = 'auto';
$connection->clock += 100;
Z2MC_ExpireRequest($connections[0]);
Z2MC_RequestExtensionStatus($connections[0]);
// Lifecycle interruption is final until an explicit new user action.
$mode = 'hold_catalog';
$held = [];
$request($first);
$manager->RefreshWorkspaceInstallations();
$manager->RefreshWorkspaceDeviceCatalogs();
$beforePackets = $packets();
$manager->ApplyChanges();
$manager->RefreshWorkspaceInstallations();
$v = $read($first);
$check($v['status'] === 'installed' && $v['devices']['phase'] === 'interrupted' && !$v['pending'] && $packets() === $beforePackets, 'Module update stops the follow-up without losing success or resending data');
$mode = 'auto';
$connection->clock += 100;
Z2MC_ExpireRequest($connections[0]);
Z2MC_RequestExtensionStatus($connections[0]);
$check($unchanged() && $documents === $originalDocuments, 'Automatic refresh never stores or activates an automation');
