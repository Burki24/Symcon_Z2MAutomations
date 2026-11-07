<?php

declare(strict_types=1);

$module->Seed(['synthetic/retained-peer']);
$activeSource = json_decode($module->GetDesignerData(), true)['configuration'];
$activeSource['automations'][1]['enabled'] = true;
$module->Seed(['synthetic/retained-peer'], $activeSource);
$module->ReadyForCreation();
$manageToken = explode('#', $module->OpenAutomationDesigner())[1];
$manageCounter = 0;
$managePost = static function (string $operation, string $entity, ?array $body = null) use ($module, $manageToken, &$manageCounter): array
{
    $view = json_decode($module->GetDesignerData(), true);
    $module->requestBody = json_encode($body ?? ['requestId' => '12345678-1234-4234-8234-' . sprintf('%012d', ++$manageCounter),
        'context'                                            => $view['createContext'], 'operation' => $operation, 'id' => $entity]);
    return $module->Hook('manage', 'POST', $manageToken);
};
$manageBefore = json_decode($module->GetDesignerData(), true)['configuration'];
$managePackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$response = $managePost('copy-rule', 'synthetic_clock');
$copyReply = json_decode($response[1], true);
$check($response[0] === 200 && $copyReply['ruleId'] !== 'synthetic_clock', 'Browser management copies an existing rule with a new identity');
$after = json_decode($module->GetDesignerData(), true)['configuration'];
$copied = array_pop($after['automations']);
$expectedCopy = $manageBefore['automations'][1];
$expectedCopy['id'] = $copyReply['ruleId'];
$expectedCopy['name'] = $copied['name'];
$expectedCopy['enabled'] = false;
$check($copied === $expectedCopy && $copied['name'] !== $manageBefore['automations'][1]['name'] && $after === $manageBefore, 'Copy preserves all typed fields, shared references, original rules, revision and partner permissions');
$fingerprint = $module->Fingerprint();
$check($module->Hook('manage', 'POST', $manageToken)[0] === 200 && $module->Fingerprint() === $fingerprint, 'Repeated management request never creates a second copy');
$oldRequest = json_decode($module->requestBody, true);
$oldRequest['operation'] = 'delete-rule';
$check($managePost('', '', $oldRequest)[0] === 409 && $module->Fingerprint() === $fingerprint, 'A receipt cannot be reused for a different destructive operation');
$oldRequest['requestId'] = 'abcdefab-abcd-4bcd-8bcd-abcdefabcdef';
$check($managePost('', '', $oldRequest)[0] === 409 && $module->Fingerprint() === $fingerprint, 'An old draft context cannot delete a rule');
$response = $managePost('delete-sequence', 'shared');
$blocked = json_decode($response[1], true);
$check($response[0] === 422 && count($blocked['users']) === 2 && $module->Fingerprint() === $fingerprint, 'Used sequence stays protected and lists all dependent automations, including a disabled copy');
$check($managePost('delete-rule', $copyReply['ruleId'])[0] === 200, 'Copied rule can be deleted locally');
$check($managePost('delete-rule', 'synthetic_clock')[0] === 200 && $managePost('delete-sequence', 'shared')[0] === 200, 'Sequence can be deleted after all referencing rules have been removed');
$deletedFingerprint = $module->Fingerprint();
$check($module->Hook('manage', 'POST', $manageToken)[0] === 200 && $module->Fingerprint() === $deletedFingerprint, 'Lost-response retry confirms an already removed sequence');
$check($managePost('delete-rule', 'missing')[0] === 409 && $module->Fingerprint() === $deletedFingerprint, 'Unknown entity never deletes another rule');
$check($managePost('filter', 'disabled')[0] === 200 && $module->Fingerprint() === $deletedFingerprint, 'Filter reports local counts without altering the draft or selection');
$check($managePost('filter', 'invalid')[0] === 400, 'Unknown filter rejected');
$check($module->Hook('manage', 'GET', $manageToken)[0] === 405 && $module->Hook('manage', 'POST')[0] === 403, 'Management requires POST and a valid capability');
$readOnlyManage = explode('#', $module->OpenAutomationDesigner())[1];
$module->MakeReadOnly($readOnlyManage);
$check($module->Hook('manage', 'POST', $readOnlyManage)[0] === 403, 'A read-only session cannot manage the draft');
$module->requestBody = str_repeat(' ', 4097);
$check($module->Hook('manage', 'POST', $manageToken)[0] === 413, 'Oversized management body rejected before parsing');
$module->requestBody = '{';
$check($module->Hook('manage', 'POST', $manageToken)[0] === 400, 'Malformed management JSON rejected without a change');
$_SERVER['HTTP_ORIGIN'] = 'https://synthetic.invalid';
$check($module->Hook('manage', 'POST', $manageToken)[0] === 403, 'Foreign browser origin cannot issue management commands');
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:3777';
$_SERVER['CONTENT_TYPE'] = 'text/plain';
$check($module->Hook('manage', 'POST', $manageToken)[0] === 415, 'Management requires the JSON content type');
$_SERVER['CONTENT_TYPE'] = 'application/json';
$module->Seed();
$pendingManage = $module->Fingerprint();
$check($managePost('delete-rule', 'synthetic_device')[0] === 409 && $module->Fingerprint() === $pendingManage, 'Unapplied native inputs block browser deletion');
$module->ReadyForCreation();
$fullManage = json_decode($module->GetDesignerData(), true)['configuration'];
while (count($fullManage['automations']) < 100) {
    $rule = $fullManage['automations'][0];
    $rule['id'] = 'synthetic_capacity_' . count($fullManage['automations']);
    $fullManage['automations'][] = $rule;
}
$module->Seed(null, $fullManage);
$module->ReadyForCreation();
$capacityBefore = $module->Fingerprint();
$check($managePost('copy-rule', 'synthetic_device')[0] === 422 && $module->Fingerprint() === $capacityBefore, 'Rule limit rejects a copy while preserving the document');
$check($managePost('delete-rule', 'synthetic_device')[0] === 200, 'Deletion remains available at the rule limit');
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $managePackets, 'All management paths send zero MQTT or runtime commands');
$module->Seed();
$module->ReadyForCreation();

$managementDebug = implode("\n", $module->debug);
$check(str_contains($managementDebug, 'Browserverwaltung') && str_contains($managementDebug, 'Statusfilter')
    && !str_contains($managementDebug, $manageToken) && !str_contains($managementDebug, 'Synthetic clock rule'), 'Management debug explains decisions without tokens or dependent rule names');
