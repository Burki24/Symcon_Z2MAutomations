<?php

declare(strict_types=1);

// Real PHP/MQTT/generated-extension boundary; no private installation data.
$catalogOriginalTransport = $module->ReplaceTransportBuffer('');
$module->ReplaceTransportBuffer($catalogOriginalTransport);
$catalogOriginalProtected = $module->CatalogProtectedState();
$catalogWithError = json_decode($catalogOriginalTransport, true);
$catalogWithError['validationDetails'] = ['issues' => [['path' => '/automations/0/name', 'reason' => 'required']], 'truncated' => false, 'names' => []];
$module->ReplaceTransportBuffer(json_encode($catalogWithError, JSON_THROW_ON_ERROR));
$catalogFields = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$catalogInput = array_map(static fn (array $field): mixed => $field['value'] ?? ($field['values'] ?? ''), $catalogFields);
$catalogInput['DeviceEditorName'] = 'Synthetic pending catalog edit';
$check($module->TrackEditorInput(Z2MAutomations\EditorWorkInput::Capture($catalogInput)), 'Pending input is tracked before refreshing devices');
$catalogProtected = $module->CatalogProtectedState();
$catalogBefore = $module->CatalogSnapshot();
$catalogPackets = count($parent->sent);
$check($module->RefreshDeviceCatalog() !== '' && $state()['operation']['status'] === 'catalog_loaded', 'Standalone catalog workflow completes against the real extension');
$check($module->CatalogProtectedState() === $catalogProtected, 'Catalog refresh preserves exact drafts, selections, input buffers, navigation and debug lease');
$check($state()['validationDetails']['issues'] === $catalogWithError['validationDetails']['issues'], 'Catalog status confirmation retains detailed validation errors');
$catalogBefore = $module->CatalogSnapshot();
$catalogCommands = array_slice($parent->sent, $catalogPackets);
$check(array_column($catalogCommands, 'Topic') === ['synthetic/z2m/symcon/automations/request/status', 'synthetic/z2m/symcon/automations/request/catalog'], 'Refresh sends only status and catalog reads, never store or runtime commands');
$catalogView = json_decode($module->GetDesignerData(), true);
$check($catalogView['catalogAvailable'] && count($catalogView['devices']) >= 4 && is_int($catalogView['catalogLoadedAt']) && $catalogView['openFormEdits'], 'Designer receives catalog, refresh time and pending input warning');
$catalogForm = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$check($catalogForm['DeviceEditorName']['value'] === 'Synthetic pending catalog edit' && str_contains($catalogForm['CatalogRefreshInfo']['caption'], 'last refreshed'), 'Reopening the form retains pending input and displays successful catalog time');

$catalogForeignDraft = json_decode($catalogProtected['EditorDraft'], true);
$catalogForeignDraft['scope'] = 'synthetic/previous-connection';
$catalogForeignDraft['configuration']['automations'][] = ['id' => 'previous_connection', 'name' => 'Previous connection rule', 'enabled' => false,
    'triggers'                                                 => [], 'conditions' => [], 'actions' => []];
$catalogForeignText = json_encode($catalogForeignDraft, JSON_THROW_ON_ERROR);
$module->ReplaceCatalogDraft($catalogForeignText);
$check($module->RefreshDeviceCatalog() !== '' && $state()['operation']['status'] === 'catalog_loaded', 'Device refresh is independent of a preserved draft from another connection');
$catalogForeignView = json_decode($module->GetDesignerData(), true);
$catalogForeignForm = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$check($module->CatalogProtectedState()['EditorDraft'] === $catalogForeignText && $catalogForeignView['catalogAvailable']
    && $catalogForeignView['draftMismatch'] && !$catalogForeignView['draftAvailable'] && $catalogForeignForm['EditorDeviceRules']['values'] === [], 'Foreign draft bytes are retained but never offered as rules of the current connection');
$module->ReplaceCatalogDraft($catalogProtected['EditorDraft']);
$catalogBefore = $module->CatalogSnapshot();

$catalogGoodPublish = $parent->onPublish;
$parent->onPublish = static function (array $packet) use ($exchange, $deliver): void
{
    $messages = $exchange(['topic' => $packet['Topic'], 'payload' => $packet['Payload']]);
    foreach ($messages as $message) {
        if (str_ends_with($message->topic, '/response/catalog')) $message->payload->data->devices = [(object) ['id' => 'invalid']];
    }
    $deliver($messages);
};
$check($module->RefreshDeviceCatalog() !== '' && $state()['lastError'] === 'invalid_response', 'Malformed catalog is rejected');
$check($module->CatalogSnapshot() === $catalogBefore && $module->CatalogProtectedState() === $catalogProtected, 'Rejected refresh keeps the previous catalog, timestamp and edits');
$parent->onPublish = $catalogGoodPublish;

// Old extensions must produce an actionable message rather than a timeout.
$parent->onPublish = static function (array $packet) use ($exchange, $deliver): void
{
    $messages = $exchange(['topic' => $packet['Topic'], 'payload' => $packet['Payload']]);
    foreach ($messages as $message) {
        if (str_ends_with($message->topic, '/response/status')) unset($message->payload->data->catalog_version);
    }
    $deliver($messages);
};
$check($module->RefreshDeviceCatalog() !== '' && $state()['lastError'] === 'catalog_unavailable', 'Old extensions request an update before the new catalog command');
$check($module->CatalogProtectedState() === $catalogProtected && $module->CatalogSnapshot() === $catalogBefore, 'Unsupported refresh never replaces input or catalog');
$parent->onPublish = $catalogGoodPublish;
// Return the harness to its original input and transport state for subsequent editor checks.
$module->RestoreCatalogWork($catalogOriginalProtected);
$module->ReplaceTransportBuffer($catalogOriginalTransport);
