<?php

declare(strict_types=1);

use Z2MAutomations\Z2MProtocol;

$importBase = $detail($b)['data'];
$reply = $decode($manager->WorkspaceEditorAction($b, 1, $detail($b)['fingerprint'], 'ScanEventImports', '[]'));
$check($reply['result'] === true, 'Central native form can scan events within its selected system without a direct MQTT parent');
$importOther = $detail($a)['data'];
$importEvents = [];
$importDevices = [];
foreach (IPS_GetInstanceListByModuleID('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}') as $instance) $importDevices[IPS_GetProperty($instance, 'MQTTBaseTopic')] = $instance;
$importRecord = static function (int $source, int $target, string $name = ''): array
{
    return ['name' => $name, 'parent' => $target, 'event' => ['EventType' => 0, 'TriggerType' => 4, 'TriggerVariableID' => $source, 'TriggerValue' => true,
        'TriggerSubsequentExecution'                                      => false, 'EventLimit' => 0, 'EventConditions' => [], 'EventScript' => '', 'EventActive' => true,
        'EventActionID'                                                   => '{EF078D00-FBD5-8857-F5DC-F5B54B107DD3}', 'EventActionParameters' => []]];
};
foreach ($systems as $index => $system) {
    $instance = $importDevices['synthetic/system_' . $index];
    $importEvents[920000 + $index] = $importRecord(IPS_GetObjectIDByIdent('display_flag', $instance), IPS_GetObjectIDByIdent('state', $groups[$index]));
}
$manager->importEvents = $importEvents;
$check($manager->ScanEventImports(), 'Unscoped public scan delegates only to the selected central system: ' . implode(' ', array_slice($manager->debugMessages, -6)));
$importFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$check(isset($importFields['EventImportSection']) && array_column($importFields['ImportEventID']['options'], 'value') === [0, 920001], 'Native central import shows only its exact MQTT/base-topic source and no events from seven other systems');
$check(str_contains($importFields['ImportEventID']['options'][1]['caption'], (string) $importDevices['synthetic/system_1']), 'Unnamed events show the real triggering device and instance ID');
$reply = $decode($manager->WorkspaceEditorAction($b, 1, $detail($b)['fingerprint'], 'PreviewEventImport', '[920001]'));
$check($reply['result'] === true, 'Readable sensor variables remain valid import sources without switch actions');
$importFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$check($importFields['ImportButton']['enabled'], 'A valid central preview enables the native import button');
$importToken = $importFields['ImportToken']['value'];
$reply = $decode($manager->WorkspaceEditorAction($b, 1, $detail($b)['fingerprint'], 'ImportSymconEvent', json_encode([920001, $importToken, $importFields['YamlInput']['value'], true])));
$check($reply['result'] === true, 'Native central import commits a disabled local rule');
$imported = $detail($b)['data']['document']['editorDraft']['configuration']['automations'][2];
$check(!$imported['enabled'] && $imported['triggers'][0]['device'] === $deviceKey && $imported['triggers'][0]['on_change'] === true
    && $imported['actions'][0]['type'] === 'toggle' && $imported['actions'][0]['target'] === ['type' => 'group', 'id' => 7], 'Native sensor mapping, repeated-trigger semantics and group toggle are preserved');
$check($manager->importEvents === $importEvents && $detail($a)['data'] === $importOther && count($parent->sent) === $sent, 'Import preserves original events, other system, partner grants and MQTT');
$check(!$manager->PreviewEventImport(920001), 'Already imported events are clearly rejected instead of overwritten');
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $detail($b)['fingerprint'], json_encode($importBase), $op()))['ok'], 'Restore only synthetic local import data');
// The browser uses a fixed, capability-guarded route, never a general action API.
$importCounter = 0;
$importPost = static function (string $action, int $event = 0, string $preview = '', ?array $request = null, ?array $headers = null) use ($manager, $b, $token, $detail, $decode, &$importCounter): array
{
    $view = $decode($manager->GetDesignerData());
    $body = ['requestId' => 'dddd0000-0000-4000-8000-' . sprintf('%012d', ++$importCounter), 'context' => $view['createContext'], 'action' => $action];
    if ($action !== 'scan') $body['eventId'] = $event;
    if ($action === 'import') $body['previewToken'] = $preview;
    $manager->requestBody = json_encode($request ?? $body);
    return $manager->Hook('event-import', $token, $headers ?? ['HTTP_X_Z2MA_SYSTEM_ID' => $b, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $detail($b)['fingerprint']], 'POST');
};
$rawBeforeScan = $manager->RawWorkspace();
[$code, $raw] = $importPost('scan');
$check($code === 200 && $decode($raw)['view']['rows'][0]['id'] === 920001, 'Browser scan offers exactly the selected system events');
$check($manager->RawWorkspace() === $rawBeforeScan, 'Scanning changes no persistent document or event');
[$code, $raw] = $importPost('preview', 920001);
$check($code === 200 && $decode($raw)['view']['selected']['canImport'], 'Browser preview returns an explicit single-event import approval');
$browserPreview = $decode($raw)['view'];
$manager->importEvents[920001]['event']['TriggerValue'] = false;
$beforeRejected = $manager->RawWorkspace();
[$code] = $importPost('import', 920001, $browserPreview['selected']['token']);
$check($code === 422 && $manager->RawWorkspace() === $beforeRejected, 'Changed native events invalidate the approval without partial import');
$manager->importEvents = $importEvents;
[$code, $raw] = $importPost('preview', 920001);
$check($code === 200 && $decode($raw)['view']['selected']['canImport'], 'A fresh preview can approve the unchanged original again');
$browserPreview = $decode($raw)['view'];
$importRequest = ['requestId' => 'eeee0000-0000-4000-8000-000000000001', 'context' => $browserPreview['context'], 'action' => 'import', 'eventId' => 920001, 'previewToken' => $browserPreview['selected']['token']];
[$code, $raw] = $importPost('import', 0, '', $importRequest);
$check($code === 200 && $decode($raw)['ok'], 'Browser imports the current native event as a disabled system-local rule');
$importedID = $decode($raw)['ruleId'];
$afterBrowser = $manager->RawWorkspace();
[$code] = $importPost('import', 0, '', $importRequest);
$check($code === 200 && $manager->RawWorkspace() === $afterBrowser, 'A lost browser response can be confirmed without duplicate import');
$importRequest['eventId'] = 920002;
[$code] = $importPost('import', 0, '', $importRequest);
$check($code === 409 && $manager->RawWorkspace() === $afterBrowser, 'A receipt cannot be replayed for another event');
$check($detail($a)['data'] === $importOther && $manager->importEvents === $importEvents && count($parent->sent) === $sent, 'Browser import leaves all other systems, source events and runtime untouched');
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $detail($b)['fingerprint'], json_encode($importBase), $op()))['ok'], 'Restore synthetic browser import data');
$check($manager->ChooseWorkspaceSystem($a, $overview()['fingerprint'], $b, 1, $detail($b)['fingerprint'], ''), 'Select the source system for a directed remote import');
$remoteEvent = $importRecord(IPS_GetObjectIDByIdent('display_flag', $importDevices['synthetic/system_0']), IPS_GetObjectIDByIdent('state', $groups[1]), 'Synthetic remote event');
$manager->importEvents[920100] = $remoteEvent;
$check($manager->PreviewEventImport(920100), 'A native group action can target the explicitly allowed second system on the same broker');
$nativeFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$variables = ['id' => $managerID];
foreach ($nativeFields as $name => $field) $variables[$name] = $field['value'] ?? $field['values'] ?? '';
$runNative($nativeFields['ImportButton']['onClick'], $variables);
$remoteRule = $detail($a)['data']['document']['editorDraft']['configuration']['automations'][1];
$check(!$remoteRule['enabled'] && $remoteRule['actions'][0]['target'] === ['type' => 'group', 'id' => 7, 'system' => 'synthetic/system_1'], 'The actual native callback imports the remote target into the source system only');
$check($detail($b)['data'] === $importBase, 'The target-system document and incoming partner grant stay byte-identical');
$check($decode($manager->UpdateWorkspaceSystem($a, 1, $detail($a)['fingerprint'], json_encode($importOther), $op()))['ok'], 'Restore the synthetic source document');
$far = $systems[3];
$farOriginal = $detail($far)['data'];
$farAllowed = $farOriginal;
$farAllowed['document']['editorDraft']['configuration']['mesh_peers'] = ['synthetic/system_0'];
$farAllowed['document']['yaml'] = json_encode($farAllowed['document']['editorDraft']['configuration']);
$farAllowed['document']['editorDraft']['draftChecksum'] = hash('sha256', $farAllowed['document']['yaml']);
$check($decode($manager->UpdateWorkspaceSystem($far, 1, $detail($far)['fingerprint'], json_encode($farAllowed), $op(), true))['ok'], 'Synthetic explicit permission for a target on the second broker');
$farEvent = $remoteEvent;
$farEvent['parent'] = IPS_GetObjectIDByIdent('state', $groups[3]);
$manager->importEvents[920105] = $farEvent;
$secondarySent = count($secondaryParent->sent);
$check($manager->PreviewEventImport(920105), 'A target on a different MQTT broker is resolved using its own system binding');
$nativeFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$check($manager->ImportSymconEvent(920105, $nativeFields['ImportToken']['value']), 'Import to the explicitly allowed second broker succeeds locally');
$farRule = $detail($a)['data']['document']['editorDraft']['configuration']['automations'][1];
$check($farRule['actions'][0]['target'] === ['type' => 'group', 'id' => 7, 'system' => 'synthetic/system_3']
    && $detail($a)['binding']['mqttParent'] !== $detail($far)['binding']['mqttParent'] && !$farRule['enabled'], 'Same group ID on two brokers retains the exact destination and disabled state');
$check($detail($far)['data'] === $farAllowed && count($secondaryParent->sent) === $secondarySent, 'Cross-broker import neither writes the target document nor publishes through its broker');
$check($decode($manager->UpdateWorkspaceSystem($a, 1, $detail($a)['fingerprint'], json_encode($importOther), $op()))['ok'], 'Restore the source after the second-broker test');
$check($decode($manager->UpdateWorkspaceSystem($far, 1, $detail($far)['fingerprint'], json_encode($farOriginal), $op(), true))['ok'], 'Restore the synthetic target permission');
$check($manager->ChooseWorkspaceSystem($b, $overview()['fingerprint'], $a, 1, $detail($a)['fingerprint'], ''), 'Restore the selected system');
$denied = $importRecord(IPS_GetObjectIDByIdent('display_flag', $importDevices['synthetic/system_1']), IPS_GetObjectIDByIdent('state', $groups[0]));
$manager->importEvents[920101] = $denied;
$check(!$manager->PreviewEventImport(920101), 'A remote source-to-target direction without its own permission is not imported');
$manager->importEvents[920102] = $importEvents[920001];
$manager->importEvents[920102]['event']['EventScript'] = '<?php must_never_run();';
$check(!$manager->PreviewEventImport(920102), 'PHP scripts cannot be translated or executed by central import');
$targetVariable = IPS_GetObjectIDByIdent('state', $groups[1]);
$manager->importEvents[920103] = $importEvents[920001];
$check($manager->PreviewEventImport(920103), 'A valid target can initially be previewed');
$nativeFields = $fieldsOf($decode($manager->GetConfigurationForm()));
IPS_SetVariableCustomAction($targetVariable, 1);
$rawBeforeRevoked = $manager->RawWorkspace();
$check(!$manager->ImportSymconEvent(920103, $nativeFields['ImportToken']['value']) && $manager->RawWorkspace() === $rawBeforeRevoked, 'Revoked native action rights invalidate the preview without altering the document');
IPS_SetVariableCustomAction($targetVariable, 0);
// One same-topic device on a different broker must not leak into the source list.
$foreignGateway = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
IPS_ApplyChanges($foreignGateway);
$foreignDevice = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
IPS_ConnectInstance($foreignDevice, $foreignGateway);
IPS_SetProperty($foreignDevice, 'IEEE', $deviceKey);
IPS_SetProperty($foreignDevice, 'MQTTBaseTopic', 'synthetic/system_1');
IPS_ApplyChanges($foreignDevice);
$manager->importEvents[920104] = $importRecord(IPS_GetObjectIDByIdent('display_flag', $foreignDevice), $targetVariable);
$check($manager->ScanEventImports(), 'Rescan after an unrelated broker appears');
$nativeFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$check(!in_array(920104, array_column($nativeFields['ImportEventID']['options'], 'value'), true), 'Same topic and IEEE on another broker never match this system');
$check($manager->importEvents[920102]['event']['EventScript'] === '<?php must_never_run();' && count($parent->sent) === $sent, 'All central import paths remain read-only for source events and produce no MQTT commands');
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $detail($b)['fingerprint'], json_encode($importBase), $op()))['ok'], 'Restore original synthetic selections after negative scenarios');
$noImportToken = substr(strrchr($manager->OpenAutomationDesigner(), '#'), 1);
$manager->RemoveImportAccess($noImportToken);
$headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $b, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $detail($b)['fingerprint']];
$manager->requestBody = json_encode(['requestId' => 'ffff0000-0000-4000-8000-000000000001', 'context' => $decode($manager->GetDesignerData())['createContext'], 'action' => 'scan']);
$check($manager->Hook('event-import', $noImportToken, $headers, 'POST')[0] === 403, 'A designer session without the import capability cannot inspect native events');
$check($manager->Hook('event-import', $token, $headers, 'GET')[0] === 405, 'A GET never scans or imports native events');
$rawNegative = $manager->RawWorkspace();
$headers['HTTP_X_Z2MA_GENERATION'] = '2';
$check($manager->Hook('event-import', $token, $headers, 'POST')[0] === 409 && $manager->RawWorkspace() === $rawNegative, 'Changed generations cannot reuse an import request');
$headers['HTTP_X_Z2MA_GENERATION'] = '1';
$manager->requestBody = str_repeat('x', 4097);
$check($manager->Hook('event-import', $token, $headers, 'POST')[0] === 413, 'Import bodies are bounded before conversion');
$manager->requestBody = '{invalid';
$check($manager->Hook('event-import', $token, $headers, 'POST')[0] === 400 && $manager->RawWorkspace() === $rawNegative, 'Malformed JSON cannot change a document');
$manager->requestBody = json_encode(['requestId' => 'ffff0000-0000-4000-8000-000000000002', 'context' => $decode($manager->GetDesignerData())['createContext'], 'action' => 'scan', 'EventScript' => 'must_never_run']);
$check($manager->Hook('event-import', $token, $headers, 'POST')[0] === 400 && $manager->RawWorkspace() === $rawNegative, 'Import never accepts event content or PHP from the browser');
// Missing catalogs still permit source discovery, without granting import approval.
$withCatalog = $manager->RawWorkspace();
$withoutCatalog = $decode($withCatalog);
$withoutCatalog['systems'][$b]['catalog'] = null;
$manager->ReplaceWorkspace(json_encode($withoutCatalog));
$check($manager->ScanEventImports(), 'Events can be discovered before device metadata is loaded');
$nativeFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$check(count($nativeFields['ImportEventID']['options']) > 1 && !$manager->PreviewEventImport(920001) && !$nativeFields['ImportButton']['enabled'], 'Missing device data produces an explicit non-importable state, not a false gateway mismatch');
$manager->ReplaceWorkspace($withCatalog);
$manager->ClearViews();
$check($decode($manager->WorkspaceEditorAction($b, 1, $detail($b)['fingerprint'], 'SelectAutomation', json_encode(['same_rule', $detail($b)['data']['document']['yaml'], true, 'time'])))['result'], 'Open the actual clock rule before checking unsaved native inputs');
$nativeFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$variables = ['id' => $managerID];
foreach ($nativeFields as $name => $field) $variables[$name] = $field['value'] ?? $field['values'] ?? '';
$variables['TimeEditorName'] = 'Synthetic pending native edit';
$runNative($nativeFields['TimeEditorName']['onChange'], $variables);
$check($detail($b)['data']['work']['rule']['dirty'], 'Synthetic native changes are genuinely pending');
$check($manager->PreviewEventImport(920001), 'Pending edits allow a read-only preview');
$nativeFields = $fieldsOf($decode($manager->GetConfigurationForm()));
$dirtyBefore = $manager->RawWorkspace();
$check(!$nativeFields['ImportButton']['enabled'] && !$manager->ImportSymconEvent(920001, $nativeFields['ImportToken']['value']) && $manager->RawWorkspace() === $dirtyBefore, 'Import cannot discard pending native edits');
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $detail($b)['fingerprint'], json_encode($importBase), $op()))['ok'], 'Restore pending synthetic work');
$manager->importEvents = array_fill_keys(range(930000, 934199), $importEvents[920001]);
[$code, $raw] = $importPost('scan');
$check($code === 200 && count($decode($raw)['view']['rows']) === 256 && $decode($raw)['view']['limited'] && strlen($raw) < 524288, 'Large event collections produce bounded results and an explicit search-limit notice');
$check(strlen($manager->GetConfigurationForm()) < 1048576, 'Native central form stays within the Symcon output budget with 256 import rows');
$importDebug = implode("\n", $manager->debugMessages);
$check(str_contains($importDebug, 'als deaktivierte Regel') && str_contains($importDebug, 'Dieses Ereignis wurde bereits übernommen')
    && !str_contains($importDebug, 'Synthetic remote event') && !str_contains($importDebug, 'must_never_run') && !str_contains($importDebug, $importToken), 'German debug explains import and rejection without event names, scripts or preview approvals');
$manager->importEvents = [];
