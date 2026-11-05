<?php

declare(strict_types=1);

$module->Seed(['synthetic/retained-peer']);
$module->ReadyForCreation();
$fullToken = explode('#', $module->OpenAutomationDesigner())[1];
$fullBefore = json_decode($module->GetDesignerData(), true);
$fullFields = ['name' => 'Synthetic complete browser rule', 'retrigger' => 'extend',
    'triggers'        => [['type' => 'time', 'at' => '07:00:00', 'timezone' => 'UTC'], ['type' => 'time', 'at' => '19:00:00', 'timezone' => 'UTC']],
    'conditions'      => [['type' => 'weekday', 'weekdays' => [1, 3, 5], 'timezone' => 'UTC']],
    'actions'         => [['type' => 'sequence', 'sequence_id' => 'shared', 'delay_seconds' => 5]]];
$fullRequest = ['requestId' => 'abcdabcd-abcd-4bcd-8bcd-abcdabcdabcd', 'context' => $fullBefore['createContext'], 'rule' => $fullFields];
$module->requestBody = json_encode($fullRequest);
$fullResponse = $module->Hook('create', 'POST', $fullToken);
$check($fullResponse[0] === 200, 'Complete creation accepts triggers, conditions and actions before its first local commit: ' . $fullResponse[1]);
$fullAfter = json_decode($module->GetDesignerData(), true);
$fullCreated = $fullAfter['configuration']['automations'][2];
$check(!$fullCreated['enabled'] && array_replace($fullFields, array_intersect_key($fullCreated, $fullFields)) === $fullFields, 'Complete creation preserves every typed field and always starts disabled');
$fullExpected = $fullBefore['configuration'];
$fullExpected['automations'][] = $fullCreated;
$check($fullAfter['configuration'] === $fullExpected, 'Complete creation retains all existing rules, sequences, revision and directed partner grants');
$fullFingerprint = $module->Fingerprint();
$check($module->Hook('create', 'POST', $fullToken)[0] === 200 && $module->Fingerprint() === $fullFingerprint, 'Full-rule retry never duplicates a rule');
$fullRequest['rule']['name'] = 'Changed retry';
$module->requestBody = json_encode($fullRequest);
$check($module->Hook('create', 'POST', $fullToken)[0] === 409 && $module->Fingerprint() === $fullFingerprint, 'Full-rule receipt cannot be reused for different content');
$fullCounter = 0;
$fullPost = static function (array $fields, ?string $context = null) use ($module, $fullToken, &$fullCounter): array
{
    $view = json_decode($module->GetDesignerData(), true);
    $module->requestBody = json_encode(['requestId' => 'aaaa0000-0000-4000-8000-' . sprintf('%012d', ++$fullCounter), 'context' => $context ?? $view['createContext'], 'rule' => $fields]);
    return $module->Hook('create', 'POST', $fullToken);
};
$fullPackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$fullDevice = '0x0000000000000001';
$fullComplex = $fullFields;
$fullComplex['triggers'][] = ['type' => 'device', 'device' => $fullDevice, 'property' => 'action', 'operator' => 'eq', 'value' => 'single', 'on_change' => true];
$fullComplex['triggers'][] = ['type' => 'sun', 'event' => 'sunrise', 'latitude' => 0, 'longitude' => 0, 'offset_minutes' => -30, 'timezone' => 'UTC'];
$fullComplex['conditions'][] = ['type' => 'device_value', 'device' => $fullDevice, 'property' => 'temperature', 'operator' => 'gt', 'value' => 0, 'start_only' => true];
$fullComplex['conditions'][] = ['type' => 'time_window', 'after' => '18:00:00', 'before' => '06:00:00', 'timezone' => 'UTC'];
$fullComplex['actions'] = [
    ['type' => 'set', 'target' => ['type' => 'device', 'id' => $fullDevice], 'values' => ['state' => 'OFF', 'level' => 0, 'color' => ['hex' => '#123456']], 'delay_seconds' => 0],
    ['type' => 'scene', 'target' => ['type' => 'device', 'id' => $fullDevice], 'endpoint' => 2, 'scene_id' => 7, 'delay_seconds' => 5],
    ['type' => 'sequence', 'sequence_id' => 'shared', 'delay_seconds' => 2]];
$response = $fullPost($fullComplex);
$check($response[0] === 200, 'One complete rule can combine device, clock and sun triggers, all conditions and multiple typed actions: ' . $response[1]);
$view = json_decode($module->GetDesignerData(), true);
$complexCreated = $view['configuration']['automations'][3];
$check(array_replace($fullComplex, array_intersect_key($complexCreated, $fullComplex)) === $fullComplex && !$complexCreated['enabled'], 'Complete rule creation and edit prefill are lossless');
$editContext = $view['createContext'];
$reordered = [$fullComplex['actions'][2], $fullComplex['actions'][0], $fullComplex['actions'][1]];
$module->requestBody = json_encode(['requestId' => 'bbbb0000-0000-4000-8000-000000000001', 'context' => $editContext, 'ruleId' => $complexCreated['id'], 'changes' => ['actions' => $reordered]]);
$check($module->Hook('edit', 'POST', $fullToken)[0] === 200, 'Whole-step reordering is accepted through shared validation');
$view = json_decode($module->GetDesignerData(), true);
$check($view['configuration']['automations'][3]['actions'] === $reordered, 'Reordering retains each step delay and payload exactly');
$sequenceActions = [$fullComplex['actions'][0], $fullComplex['actions'][1], array_replace($fullComplex['actions'][0], ['delay_seconds' => 9])];
foreach ([$sequenceActions, [$sequenceActions[2], $sequenceActions[0], $sequenceActions[1]]] as $index => $ordered) {
    $context = json_decode($module->GetDesignerData(), true)['createContext'];
    $module->requestBody = json_encode(['requestId' => 'cccc0000-0000-4000-8000-' . sprintf('%012d', $index), 'context' => $context, 'sequenceId' => 'shared', 'changes' => ['actions' => $ordered]]);
    $response = $module->Hook('sequence', 'POST', $fullToken);
    $check($response[0] === 200, 'Reusable sequence accepts complete-step order updates: ' . $response[1]);
}
$view = json_decode($module->GetDesignerData(), true);
$check($view['configuration']['sequences'][0]['actions'] === [$sequenceActions[2], $sequenceActions[0], $sequenceActions[1]], 'Reusable sequence reordering preserves each step and delay');
$fullDebug = implode("\n", $module->debug);
$check(str_contains($fullDebug, 'Wartezeiten bleiben bei ihrem jeweiligen Schritt') && str_contains($fullDebug, '[3,1,2]')
    && !str_contains($fullDebug, 'Synthetic complete browser rule') && !str_contains($fullDebug, '#123456') && !str_contains($fullDebug, $fullToken), 'Debug identifies committed order without names, tokens or payload values');
$fullInvalid = [array_replace($fullFields, ['enabled' => true]), array_replace($fullFields, ['id' => 'injected']),
    array_replace($fullFields, ['conditions' => [['type' => 'weekday', 'weekdays' => [], 'timezone' => 'UTC']]]),
    array_replace($fullFields, ['triggers' => [$fullFields['triggers'][0], $fullFields['triggers'][0]]]),
    array_replace($fullFields, ['actions' => array_fill(0, 17, $fullFields['actions'][0])]),
    array_replace($fullFields, ['actions' => [['type' => 'set', 'target' => ['type' => 'device', 'id' => $fullDevice], 'values' => ['display_flag' => 'ON']]]]),
    array_replace($fullFields, ['actions' => [['type' => 'sequence', 'sequence_id' => 'missing']]])];
foreach ($fullInvalid as $invalid) {
    $fingerprint = $module->Fingerprint();
    $response = $fullPost($invalid);
    $check(in_array($response[0], [400, 422], true) && $module->Fingerprint() === $fingerprint, 'Invalid complete creation never partially changes the draft');
}
$fingerprint = $module->Fingerprint();
$check($fullPost($fullFields, $editContext)[0] === 409 && $module->Fingerprint() === $fingerprint, 'Stale full-rule requests cannot overwrite another commit');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 1);
$check($fullPost($fullComplex)[0] === 422 && $module->Fingerprint() === $fingerprint, 'Complete creation checks current native action rights on submission');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 0);
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $fullPackets, 'Creating, rejecting and reordering complete rules sends no MQTT or switching commands');
unset($fullPost, $fullInvalid);
