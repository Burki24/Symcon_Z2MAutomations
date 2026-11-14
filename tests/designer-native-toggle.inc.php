<?php

declare(strict_types=1);

$module->Seed();
$module->ReadyForCreation();
$nativeToggleToken = explode('#', $module->OpenAutomationDesigner())[1];
$nativeTogglePackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$nativeToggleSerial = 0;
$nativeTogglePost = static function (string $route, array $fields) use ($module, $nativeToggleToken, &$nativeToggleSerial): array
{
    $view = json_decode($module->GetDesignerData(), true);
    $module->requestBody = json_encode(['requestId' => 'cccc0000-0000-4000-8000-' . sprintf('%012d', ++$nativeToggleSerial), 'context' => $view['createContext']] + $fields);
    return $module->Hook($route, 'POST', $nativeToggleToken);
};
$nativeToggleRule = ['name' => 'Native channel toggle', 'retrigger' => 'ignore',
    'triggers'              => [['type' => 'time', 'at' => '08:00:00', 'timezone' => 'UTC']], 'conditions' => [],
    'actions'               => [['type' => 'set', 'target' => ['type' => 'device', 'id' => '0x0000000000000001'], 'values' => ['state_l1' => 'TOGGLE'], 'delay_seconds' => 0]]];
[$code, $body] = $nativeTogglePost('create', ['rule' => $nativeToggleRule]);
$check($code === 200, 'A writable L1 channel offered as native TOGGLE must be accepted: ' . $body);
$nativeToggleID = json_decode($body, true)['ruleId'];
$view = json_decode($module->GetDesignerData(), true);
$nativeToggleCreated = array_values(array_filter($view['configuration']['automations'], static fn (array $rule): bool => $rule['id'] === $nativeToggleID))[0];
$check(!$nativeToggleCreated['enabled'] && $nativeToggleCreated['actions'] === $nativeToggleRule['actions'] && $view['editSections'][$nativeToggleID]['actions'], 'Creation and reopening preserve literal TOGGLE without converting it to aggregate inversion');
foreach ([['state' => 'TOGGLE'], ['state_l2' => 'TOGGLE'], ['state_l1' => 'TOGGLE', 'level' => 50]] as $values) {
    $actions = [array_replace($nativeToggleRule['actions'][0], ['values' => $values, 'delay_seconds' => 7])];
    [$code, $body] = $nativeTogglePost('edit', ['ruleId' => $nativeToggleID, 'changes' => ['actions' => $actions]]);
    $check($code === 200, 'Native switch values and compound TOGGLE remain editable: ' . $body);
    [$code, $body] = $nativeTogglePost('edit-yaml', ['ruleId' => $nativeToggleID, 'changes' => ['actions' => $actions]]);
    $document = json_decode(json_decode($body, true)['yaml'] ?? 'null', true);
    $exported = array_values(array_filter($document['automations'] ?? [], static fn (array $rule): bool => $rule['id'] === $nativeToggleID));
    $check($code === 200 && $exported[0]['actions'] === $actions, 'YAML preserves native TOGGLE, other values, ownership and delay');
}
$nativeToggleBefore = $module->Fingerprint();
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state_l1', $deviceID), 1);
[$code] = $nativeTogglePost('create', ['rule' => $nativeToggleRule]);
$check($code === 422 && $module->Fingerprint() === $nativeToggleBefore, 'Revoking the native action still blocks TOGGLE without changing drafts');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state_l1', $deviceID), 0);
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $nativeTogglePackets, 'Creation, editing and export never send the toggle command');
unset($nativeTogglePost);
