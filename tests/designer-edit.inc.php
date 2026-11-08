<?php

declare(strict_types=1);

$module->Seed(['synthetic/retained-peer']);
$module->ReadyForCreation();
$editToken = explode('#', $module->OpenAutomationDesigner())[1];
$editView = json_decode($module->GetDesignerData(), true);
$editRequest = ['requestId' => '77777777-7777-4777-8777-777777777777', 'context' => $editView['createContext'],
    'ruleId'                => 'synthetic_device', 'changes' => ['name' => 'Synthetic renamed browser rule']];
$module->requestBody = json_encode($editRequest);
$editPackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$editResponse = $module->Hook('edit', 'POST', $editToken);
$check($editResponse[0] === 200, 'Existing automation can be edited through the bounded browser route');
$editedView = json_decode($module->GetDesignerData(), true);
$expectedEdit = $editView['configuration'];
$expectedEdit['automations'][0]['name'] = 'Synthetic renamed browser rule';
$check($editedView['configuration'] === $expectedEdit, 'Metadata edit preserves every trigger, condition, action, peer, sequence and existing revision');
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $editPackets, 'Browser editing sends no MQTT or runtime commands');
$editedFingerprint = $module->Fingerprint();
$check($module->Hook('edit', 'POST', $editToken)[0] === 200 && $module->Fingerprint() === $editedFingerprint, 'Lost-response retry applies an edit only once');
$editRequest['changes']['name'] = 'Different retry intent';
$module->requestBody = json_encode($editRequest);
$check($module->Hook('edit', 'POST', $editToken)[0] === 409 && $module->Fingerprint() === $editedFingerprint, 'A reused edit identity cannot change its intent');
$editRequest['requestId'] = '99999999-9999-4999-8999-999999999999';
$module->requestBody = json_encode($editRequest);
$check($module->Hook('edit', 'POST', $editToken)[0] === 409, 'An old view cannot overwrite an already edited rule');
$check($module->Hook('edit', 'GET', $editToken)[0] === 405 && $module->Hook('edit', 'POST')[0] === 403, 'Edit route requires POST and an edit capability');
$createOnly = explode('#', $module->OpenAutomationDesigner())[1];
$module->MakeCreateOnly($createOnly);
$check($module->Hook('edit', 'POST', $createOnly)[0] === 403, 'An old creation-only session never gains editing access');
$module->MakeReadOnly($createOnly);
$check($module->Hook('edit', 'POST', $createOnly)[0] === 403, 'A read-only session cannot edit rules');

$editCounter = 0;
$editPost = static function (array $changes, string $rule = 'synthetic_device') use ($module, $editToken, &$editCounter): array
{
    $view = json_decode($module->GetDesignerData(), true);
    $body = ['requestId' => '88888888-8888-4888-8888-' . sprintf('%012d', ++$editCounter), 'context' => $view['createContext'], 'ruleId' => $rule, 'changes' => $changes];
    $module->requestBody = json_encode($body);
    return $module->Hook('edit', 'POST', $editToken);
};
$device = '0x0000000000000001';
foreach (['resume', 'resume_expired', 'abort'] as $policy) {
    $check($editPost(['restart_policy' => $policy])[0] === 200, 'Browser accepts the selected restart policy');
    $policyView = json_decode($module->GetDesignerData(), true);
    $check($policyView['configuration']['automations'][0]['restart_policy'] === $policy, 'Browser data prefills the saved restart policy');
}
$policyFingerprint = $module->Fingerprint();
$check($editPost(['restart_policy' => 'retry_all'])[0] === 422 && $module->Fingerprint() === $policyFingerprint, 'Invalid recovery policy cannot change the draft');
$noOpFingerprint = $module->Fingerprint();
$check($editPost([])[0] === 200 && $module->Fingerprint() === $noOpFingerprint, 'Unchanged prefilled fields do not rewrite the draft');
$fullDraft = $expectedEdit;
while (count($fullDraft['automations']) < 100) {
    $extra = $fullDraft['automations'][0];
    $extra['id'] = 'synthetic_extra_' . count($fullDraft['automations']);
    $fullDraft['automations'][] = $extra;
}
$module->Seed(null, $fullDraft);
$module->ReadyForCreation();
$fullView = json_decode($module->GetDesignerData(), true);
$check(
    !$fullView['createAllowed'] && $fullView['editAllowed'] && $editPost(['name' => 'Existing rule at capacity'])[0] === 200,
    'A full draft prevents creation but still permits editing an existing rule'
);
$module->Seed(['synthetic/retained-peer']);
$module->ReadyForCreation();
$triggers = [['type' => 'device', 'device' => $device, 'property' => 'state_l2', 'operator' => 'eq', 'value' => 'ON', 'for_seconds' => 0, 'on_change' => true],
    ['type' => 'device', 'device' => $device, 'property' => 'temperature', 'operator' => 'lt', 'value' => 0]];
$conditions = [['type' => 'device_value', 'device' => $device, 'property' => 'temperature', 'operator' => 'lt', 'value' => 0, 'start_only' => true],
    ['type' => 'weekday', 'weekdays' => [1, 5, 7], 'timezone' => 'UTC'],
    ['type' => 'time_window', 'after' => '22:00:00', 'before' => '06:00:00', 'timezone' => 'UTC']];
$actions = [['type' => 'set', 'target' => ['type' => 'device', 'id' => $device], 'values' => ['state' => 'OFF', 'level' => 0, 'color' => ['hex' => '#123456']], 'delay_seconds' => 0],
    ['type' => 'scene', 'target' => ['type' => 'device', 'id' => $device], 'endpoint' => 2, 'scene_id' => 7, 'delay_seconds' => 5],
    ['type' => 'sequence', 'sequence_id' => 'shared', 'delay_seconds' => 2],
    ['type' => 'toggle', 'target' => ['type' => 'device', 'id' => $device], 'property' => 'state_l2', 'delay_seconds' => 3]];
$check($editPost(['triggers' => $triggers, 'conditions' => $conditions, 'actions' => $actions, 'retrigger' => 'extend', 'enabled' => true])[0] === 200, 'Multiple triggers, conditions, compound color command, scene, sequence and delays use native validation');
$complexView = json_decode($module->GetDesignerData(), true);
$complex = $complexView['configuration']['automations'][0];
$check($complex['triggers'] === $triggers && $complex['conditions'] === $conditions && $complex['actions'] === $actions
    && $complex['enabled'] && $complex['retrigger'] === 'extend' && $complexView['configuration']['mesh_peers'] === ['synthetic/retained-peer'], 'All selected fields and exact zero values survive local editing');
$check($complexView['editSections']['synthetic_device'] === ['triggers' => true, 'conditions' => true, 'actions' => true], 'The browser receives explicit section availability for full prefill');
$clock = [['type' => 'time', 'at' => '07:15:00', 'timezone' => 'Europe/Berlin'], ['type' => 'time', 'at' => '23:45:00', 'timezone' => 'Europe/Berlin']];
$check($editPost(['triggers' => $clock], 'synthetic_clock')[0] === 200, 'Existing time rules accept all clock slots without changing device rules');
$sun = [['type' => 'sun', 'event' => 'sunrise', 'latitude' => 0.0, 'longitude' => 0.0, 'offset_minutes' => -30, 'timezone' => 'UTC']];
$check($editPost(['triggers' => $sun], 'synthetic_clock')[0] === 200, 'Astro prefill can retain coordinates at zero and negative offsets');

$invalidEdits = [['id' => 'replacement'], ['mesh_peers'                               => []], ['enabled' => 1], ['retrigger' => 'unsupported'], ['triggers' => []],
    ['triggers' => [array_replace($triggers[0], ['on_change' => null])]], ['triggers' => [array_replace($triggers[0], ['for_seconds' => null])]],
    ['conditions' => [['type' => 'weekday', 'weekdays' => [], 'timezone' => 'UTC']]],
    ['conditions' => [['type' => 'time_window', 'after' => null, 'before' => '06:00:00', 'timezone' => 'UTC']]],
    ['actions' => [array_replace($actions[0], ['delay_seconds' => null])]], ['actions' => [array_replace($actions[0], ['values' => [1 => 'ON']])]],
    ['actions' => [array_replace($actions[0], ['target' => ['type' => 'device', 'id' => $device, 'system' => 'synthetic/not-selected']])]],
    ['actions' => [['type' => 'sequence', 'sequence_id' => 'missing']]], ['actions' => array_fill(0, 17, $actions[0])]];
foreach ($invalidEdits as $invalid) {
    $fingerprint = $module->Fingerprint();
    $response = $editPost($invalid);
    $check(in_array($response[0], [400, 422], true) && $module->Fingerprint() === $fingerprint, 'Malformed edits are rejected without changing the draft');
}
$fingerprint = $module->Fingerprint();
$check($editPost(['triggers' => [$clock[0], $clock[0]]], 'synthetic_clock')[0] === 422 && $module->Fingerprint() === $fingerprint, 'Duplicate clock times cannot be introduced through browser editing');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 1);
$revoked = $actions;
$revoked[0]['delay_seconds'] = 6;
$fingerprint = $module->Fingerprint();
$check($editPost(['actions' => $revoked])[0] === 422 && $module->Fingerprint() === $fingerprint, 'Current native action rights are checked again when submitting changed actions');
$check($editPost(['name' => 'Synthetic metadata only'])[0] === 200, 'A protected unavailable section remains intact during metadata-only editing');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 0);
$module->Seed();
$fingerprint = $module->Fingerprint();
$check($editPost(['name' => 'Must not discard native work'])[0] === 409 && $module->Fingerprint() === $fingerprint, 'Pending native edits block browser edits');
$module->ReadyForCreation();
$_SERVER['HTTP_ORIGIN'] = 'http://other.invalid';
$check($editPost(['name' => 'Foreign origin'])[0] === 403, 'Foreign origins cannot submit an edit');
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:3777';
$_SERVER['HTTP_CONTENT_TYPE'] = 'text/plain';
$check($editPost(['name' => 'Wrong media type'])[0] === 415, 'Editing keeps strict JSON-only headers');
$_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
$module->requestBody = str_repeat('x', 65537);
$check($module->Hook('edit', 'POST', $editToken)[0] === 413, 'Editing body size is bounded independently of creation');
$module->requestBody = '{broken';
$check($module->Hook('edit', 'POST', $editToken)[0] === 400, 'Invalid JSON cannot reach the editor');
$check($editPost(['name' => 'Missing rule'], 'missing')[0] === 409, 'Editing cannot create a missing automation or replace another object');
$module->clock += 901;
$check($editPost(['name' => 'Expired'])[0] === 403, 'Edit access ends at its original expiry');
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $editPackets, 'All edit scenarios leave MQTT and Z2M untouched');
$editDebug = implode("\n", $module->debug);
$check(str_contains($editDebug, 'Browserbearbeitung im lokalen Entwurf übernommen') && !str_contains($editDebug, 'Synthetic renamed browser rule')
    && !str_contains($editDebug, $editToken) && !str_contains($editDebug, '#123456'), 'Editing debug is understandable and contains no names, tokens or action payloads');
unset($editDebug, $editPost, $invalidEdits, $editCounter);
