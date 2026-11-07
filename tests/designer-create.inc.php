<?php

declare(strict_types=1);

$createToken = $links[4];
$check($module->Hook('create', 'GET', $createToken)[0] === 405, 'Creation route never accepts a GET');
$module->Seed(['synthetic/retained-peer']);
$module->ReadyForCreation();
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 0);
$createView = json_decode($module->GetDesignerData(), true);
$request = ['requestId' => '22222222-2222-4222-8222-222222222222', 'context' => $createView['createContext'], 'name' => 'Synthetic browser rule',
    'source'            => ['device' => '0x0000000000000001', 'property' => 'action', 'operator' => 'eq', 'value' => 'single'],
    'target'            => ['device' => '0x0000000000000001', 'property' => 'state', 'value' => 'ON']];
$module->requestBody = json_encode($request);
$createBefore = $module->Fingerprint();
$createPackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$readOnlyToken = explode('#', $module->OpenAutomationDesigner())[1];
$module->MakeReadOnly($readOnlyToken);
$check($module->Hook('data', 'GET', $readOnlyToken)[0] === 200 && $module->Hook('create', 'POST', $readOnlyToken)[0] === 403
    && $module->Fingerprint() === $createBefore, 'Previously issued read-only access never gains permission to create drafts');
$check($module->Hook('create', 'POST')[0] === 403 && $module->Fingerprint() === $createBefore, 'Creation requires the scoped browser capability');
unset($_SERVER['CONTENT_TYPE']);
$_SERVER['HTTP_CONTENT_TYPE'] = 'Application/JSON; charset=utf-8';
$check($module->Hook('create', 'POST')[0] === 403 && $module->Fingerprint() === $createBefore, 'Symcon HTTP_CONTENT_TYPE reaches the capability check without an incorrect 415 rejection');
$_SERVER['HTTP_CONTENT_TYPE'] = 'text/plain';
$check($module->Hook('create', 'POST', $createToken)[0] === 415 && $module->Fingerprint() === $createBefore, 'The Symcon-specific header must also enforce JSON only');
$_SERVER['HTTP_CONTENT_TYPE'] = '';
$check($module->Hook('create', 'POST', $createToken)[0] === 415 && $module->Fingerprint() === $createBefore, 'Missing content type is rejected without mutating the draft');
$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
$_SERVER['CONTENT_TYPE'] = 'text/plain';
$check($module->Hook('create', 'POST', $createToken)[0] === 415 && $module->Fingerprint() === $createBefore, 'Simple form submissions cannot bypass the JSON-only endpoint');
$_SERVER['CONTENT_TYPE'] = 'application/json';
$module->requestBody = '{broken JSON';
$check($module->Hook('create', 'POST', $createToken)[0] === 400 && $module->Fingerprint() === $createBefore, 'Malformed JSON cannot change a draft');
$module->requestBody = json_encode($request);
$_SERVER['HTTP_ORIGIN'] = 'http://other.invalid';
$check($module->Hook('create', 'POST', $createToken)[0] === 403 && $module->Fingerprint() === $createBefore, 'Foreign origins cannot create drafts');
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:3777';
$module->requestBody = str_repeat('x', 65537);
$check($module->Hook('create', 'POST', $createToken)[0] === 413, 'Oversized creation requests are bounded');
$module->requestBody = json_encode($request);
$check(isset($_SERVER['HTTP_CONTENT_TYPE']), 'Real Symcon header remains present during creation');
unset($_SERVER['CONTENT_TYPE']);
$createdResponse = $module->Hook('create', 'POST', $createToken);
$check($createdResponse[0] === 200, 'Browser creates a local rule through the real hook');
$created = json_decode($createdResponse[1], true);
$afterCreate = json_decode($module->GetDesignerData(), true);
$check($afterCreate['configuration']['mesh_peers'] === ['synthetic/retained-peer'], 'Browser creation preserves existing peer permissions when no peer module is selected');
$check(str_contains(implode("\n", $module->debug), 'Vorhandene Partnerfreigaben bleiben im lokalen Entwurf erhalten')
    && !str_contains(implode("\n", $module->debug), 'synthetic/retained-peer'), 'Safe German debug explains retained permissions without logging partner topics');
$newRule = $afterCreate['configuration']['automations'][2];
$check(count($afterCreate['configuration']['automations']) === 3 && !$newRule['enabled'] && $newRule['id'] === $created['ruleId']
    && $newRule['triggers'][0]['property'] === 'action' && $newRule['actions'][0]['values']['state'] === 'ON', 'Existing rules remain, and the selected source/channel/value becomes a disabled rule');
$createdFingerprint = $module->Fingerprint();
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $createPackets, 'Creation sends no MQTT requests, device actions or runtime commands');
$check($module->Hook('create', 'POST', $createToken)[0] === 200 && $module->Fingerprint() === $createdFingerprint, 'Retrying the same request does not create a duplicate');
$request['context'] = $afterCreate['createContext'];
$module->requestBody = json_encode($request);
$check($module->Hook('create', 'POST', $createToken)[0] === 200 && $module->Fingerprint() === $createdFingerprint, 'A refresh after a lost success response still cannot duplicate the intended rule');
$request['context'] = $createView['createContext'];
$request['requestId'] = '33333333-3333-4333-8333-333333333333';
$module->requestBody = json_encode($request);
$check($module->Hook('create', 'POST', $createToken)[0] === 409 && $module->Fingerprint() === $createdFingerprint, 'A stale browser cannot overwrite a changed draft');

$request['context'] = $afterCreate['createContext'];
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 1);
$module->requestBody = json_encode($request);
$check($module->Hook('create', 'POST', $createToken)[0] === 422 && $module->Fingerprint() === $createdFingerprint, 'Revoked Symcon action rights are checked again when the browser submits');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 0);
$request['target']['property'] = 'display_flag';
$module->requestBody = json_encode($request);
$check($module->Hook('create', 'POST', $createToken)[0] === 422 && $module->Fingerprint() === $createdFingerprint, 'Display-only variables cannot be used as actions');
$request['target']['property'] = 'state';
$request['source']['device'] = 'group.1';
$module->requestBody = json_encode($request);
$check($module->Hook('create', 'POST', $createToken)[0] === 422, 'Only local device events can trigger this initial creation path');
$request['source']['device'] = '0x0000000000000001';
$request['enabled'] = true;
$module->requestBody = json_encode($request);
$check($module->Hook('create', 'POST', $createToken)[0] === 400 && $module->Fingerprint() === $createdFingerprint, 'Additional fields cannot enable or otherwise widen the new rule');
unset($request['enabled']);
$request['target']['value'] = true;
$module->requestBody = json_encode($request);
$check($module->Hook('create', 'POST', $createToken)[0] === 422, 'A string-valued device property never accepts a coerced boolean');
$request['target']['value'] = 'TOGGLE';
$module->requestBody = json_encode($request);
$toggleResponse = $module->Hook('create', 'POST', $createToken);
$toggleView = json_decode($module->GetDesignerData(), true);
$check($toggleResponse[0] === 200 && $toggleView['configuration']['automations'][3]['actions'][0]['type'] === 'toggle', 'Existing native toggle semantics are used by browser creation');

foreach ([null, []] as $peerIndex => $permissions) {
    $module->Seed($permissions);
    $module->ReadyForCreation();
    $permissionView = json_decode($module->GetDesignerData(), true);
    $request['context'] = $permissionView['createContext'];
    $request['requestId'] = $peerIndex === 0 ? '55555555-5555-4555-8555-555555555555' : '66666666-6666-4666-8666-666666666666';
    $module->requestBody = json_encode($request);
    $check($module->Hook('create', 'POST', $createToken)[0] === 200, 'Creation supports drafts without peer permissions');
    $permissionAfter = json_decode($module->GetDesignerData(), true)['configuration'];
    $check($permissions === null ? !array_key_exists('mesh_peers', $permissionAfter) : $permissionAfter['mesh_peers'] === [], 'Creation preserves the distinction between absent and explicitly empty permissions');
}
unset($permissionView, $permissionAfter, $permissions, $peerIndex);

$module->Seed();
$pendingView = json_decode($module->GetDesignerData(), true);
$check(!$pendingView['createAllowed'] && $pendingView['openFormEdits'], 'Pending native input blocks browser creation with an explanatory notice');
$request['context'] = $pendingView['createContext'];
$request['requestId'] = '44444444-4444-4444-8444-444444444444';
$module->requestBody = json_encode($request);
$pendingFingerprint = $module->Fingerprint();
$check($module->Hook('create', 'POST', $createToken)[0] === 409 && $module->Fingerprint() === $pendingFingerprint, 'Pending native input remains byte-identical after a rejected browser submission');
$module->clock += 901;
$check($module->Hook('create', 'POST', $createToken)[0] === 403 && $module->Fingerprint() === $pendingFingerprint, 'Expired creation access cannot mutate the draft');
$links[4] = explode('#', $module->OpenAutomationDesigner())[1];
