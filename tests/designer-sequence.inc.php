<?php

declare(strict_types=1);

$module->Seed(['synthetic/retained-peer']);
$module->ReadyForCreation();
$sequenceToken = explode('#', $module->OpenAutomationDesigner())[1];
$sequenceInitial = json_decode($module->GetDesignerData(), true);
$sequencePackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$sequenceCounter = 0;
$sequencePost = static function (string $id, array $changes, ?string $context = null) use ($module, $sequenceToken, &$sequenceCounter): array
{
    $view = json_decode($module->GetDesignerData(), true);
    $module->requestBody = json_encode(['requestId' => 'cdcdcdcd-cdcd-4dcd-8dcd-' . sprintf('%012d', ++$sequenceCounter),
        'context'                                   => $context ?? $view['createContext'], 'sequenceId' => $id, 'changes' => $changes]);
    return $module->Hook('sequence', 'POST', $sequenceToken);
};
$leaf = ['type' => 'toggle', 'target' => ['type' => 'device', 'id' => '0x0000000000000001'], 'property' => 'state_l2', 'delay_seconds' => 5];
$sequenceResponse = $sequencePost('', ['name' => 'Synthetic browser sequence', 'actions' => [$leaf]]);
$check($sequenceResponse[0] === 200, 'Browser can create a reusable sequence through the guarded sequence route');
$sequenceID = json_decode($sequenceResponse[1], true)['sequenceId'];
$sequenceCreated = json_decode($module->GetDesignerData(), true);
$check(count($sequenceCreated['configuration']['sequences']) === 2 && $sequenceCreated['configuration']['sequences'][1] === ['id' => $sequenceID, 'name' => 'Synthetic browser sequence', 'actions' => [$leaf]]
    && $sequenceCreated['configuration']['automations'] === $sequenceInitial['configuration']['automations']
    && $sequenceCreated['configuration']['mesh_peers'] === ['synthetic/retained-peer'], 'Only the new sequence changes; rules and peer permissions survive');
$stableSequence = $module->Fingerprint();
$check($module->Hook('sequence', 'POST', $sequenceToken)[0] === 200 && $module->Fingerprint() === $stableSequence, 'A lost creation response cannot duplicate a sequence');
$reusedRequest = json_decode($module->requestBody, true);
$reusedRequest['changes']['name'] = 'Different retry intent';
$module->requestBody = json_encode($reusedRequest);
$check($module->Hook('sequence', 'POST', $sequenceToken)[0] === 409 && $module->Fingerprint() === $stableSequence, 'Reusing a sequence request identity with different changes is rejected');
$oldSequenceContext = $sequenceCreated['createContext'];
$compoundLeaf = ['type' => 'set', 'target' => $leaf['target'], 'values' => ['state' => 'OFF', 'level' => 0, 'color' => ['hex' => '#123456']], 'delay_seconds' => 0];
$sceneLeaf = ['type' => 'scene', 'target' => $leaf['target'], 'endpoint' => 2, 'scene_id' => 7, 'delay_seconds' => 3];
$check($sequencePost('shared', ['name' => 'Synthetic edited shared', 'actions' => [$compoundLeaf, $sceneLeaf, $leaf]])[0] === 200, 'Existing shared sequences support combined commands, scenes, channels, order and delays');
$sequenceEdited = json_decode($module->GetDesignerData(), true);
$check($sequenceEdited['configuration']['automations'] === $sequenceInitial['configuration']['automations']
    && $sequenceEdited['configuration']['sequences'][0]['id'] === 'shared'
    && $sequenceEdited['configuration']['sequences'][0]['actions'] === [$compoundLeaf, $sceneLeaf, $leaf]
    && $sequenceEdited['sequenceSections']['shared'], 'Definition editing retains caller identities and exact typed values');
$sequenceFingerprint = $module->Fingerprint();
$check($sequencePost('shared', [])[0] === 200 && $module->Fingerprint() === $sequenceFingerprint, 'A no-op prefilled sequence does not rewrite the draft');
$check($sequencePost('shared', ['name' => 'Stale'], $oldSequenceContext)[0] === 409 && $module->Fingerprint() === $sequenceFingerprint, 'Stale sequence views cannot overwrite current work');
foreach ([['id' => 'replacement'], ['enabled'                                      => true], ['name' => ''], ['actions' => []], ['actions' => array_fill(0, 17, $leaf)],
    ['actions' => [['type' => 'sequence', 'sequence_id' => 'shared']]], ['actions' => [array_replace($leaf, ['delay_seconds' => -1])]],
    ['actions' => [array_replace($leaf, ['delay_seconds' => 0.5])]], ['actions'    => [array_replace($leaf, ['property' => 'display_flag'])]]] as $badSequence) {
    $check(in_array($sequencePost('shared', $badSequence)[0], [400, 422], true) && $module->Fingerprint() === $sequenceFingerprint, 'Invalid sequence changes preserve the entire existing draft');
}
$check($sequencePost('missing', ['name' => 'Missing'])[0] === 409, 'An unknown sequence is not silently created');
$indexedFailure = $sequencePost('shared', ['actions' => [$leaf, array_replace($leaf, ['property' => 'display_flag'])]]);
$check($indexedFailure[0] === 422 && str_contains($indexedFailure[1], 'Aktion 2') && $module->Fingerprint() === $sequenceFingerprint, 'Validation identifies the failing action without exposing its payload');
$check($module->Hook('sequence', 'GET', $sequenceToken)[0] === 405 && $module->Hook('sequence', 'POST')[0] === 403, 'Sequence route is POST-only and requires its own session capability');
$readToken = explode('#', $module->OpenAutomationDesigner())[1];
$module->MakeReadOnly($readToken);
$check($module->Hook('sequence', 'POST', $readToken)[0] === 403, 'Read-only sessions never acquire sequence write access');
$legacyToken = explode('#', $module->OpenAutomationDesigner())[1];
$module->MakeRuleOnly($legacyToken);
$check($module->Hook('sequence', 'POST', $legacyToken)[0] === 403, 'Existing rule-write sessions cannot acquire the new sequence capability implicitly');
$module->requestBody = str_repeat(' ', 65537);
$check($module->Hook('sequence', 'POST', $sequenceToken)[0] === 413, 'Sequence input is bounded before JSON decoding');
$_SERVER['HTTP_ORIGIN'] = 'http://different.invalid';
$check($module->Hook('sequence', 'POST', $sequenceToken)[0] === 403, 'Sequence writes reject foreign origins');
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:3777';

$fullDocument = $sequenceInitial['configuration'];
for ($number = 1; $number < 32; $number++) {
    $fullDocument['sequences'][] = ['id' => 'synthetic_' . $number, 'name' => 'Synthetic limit', 'actions' => [$leaf]];
}
$module->Seed(null, $fullDocument);
$module->ReadyForCreation();
$sequenceFingerprint = $module->Fingerprint();
$check($sequencePost('', ['name' => 'Too many', 'actions' => [$leaf]])[0] === 422 && $module->Fingerprint() === $sequenceFingerprint, 'A full sequence collection rejects new definitions without losing any data');
$check($sequencePost('shared', ['name' => 'Still editable at limit'])[0] === 200, 'Existing sequence definitions remain editable at the collection limit');

$limitedDocument = $sequenceInitial['configuration'];
$limitedDocument['automations'][1]['actions'] = [['type' => 'sequence', 'sequence_id' => 'shared'], ...array_fill(0, 15, $leaf)];
$module->Seed(null, $limitedDocument);
$module->ReadyForCreation();
$sequenceFingerprint = $module->Fingerprint();
$limitResponse = $sequencePost('shared', ['actions' => [$leaf, $leaf]]);
$check($limitResponse[0] === 422 && str_contains($limitResponse[1], '16') && $module->Fingerprint() === $sequenceFingerprint, 'Editing a used sequence cannot exceed its caller automation step limit');
$delayDocument = $sequenceInitial['configuration'];
$delayDocument['automations'][1]['actions'][0]['delay_seconds'] = 86400;
$module->Seed(null, $delayDocument);
$module->ReadyForCreation();
$sequenceFingerprint = $module->Fingerprint();
$delayFailure = $sequencePost('shared', ['actions' => [$leaf]]);
$check($delayFailure[0] === 422 && str_contains($delayFailure[1], '24 Stunden') && $module->Fingerprint() === $sequenceFingerprint, 'Caller and definition delays are checked together before changing any draft data');
$module->Seed();
$sequenceFingerprint = $module->Fingerprint();
$check($sequencePost('shared', ['name' => 'Pending work'])[0] === 409 && $module->Fingerprint() === $sequenceFingerprint, 'Native sequence/rule input remains protected');
$module->ReadyForCreation();
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 1);
$sequenceFingerprint = $module->Fingerprint();
$check($sequencePost('shared', ['actions' => [$compoundLeaf]])[0] === 422 && $module->Fingerprint() === $sequenceFingerprint, 'Sequence actions recheck current native switching rights');
$check($sequencePost('shared', ['name' => 'Metadata preserves unavailable steps'])[0] === 200, 'Unavailable existing actions remain protected during a name-only edit');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 0);
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $sequencePackets, 'Sequence creation and editing never send MQTT, save to Z2M or execute a device command');
$sequenceDebug = implode("\n", $module->debug);
$check(str_contains($sequenceDebug, 'Browser-Aktionsfolge') && !str_contains($sequenceDebug, 'Synthetic browser sequence')
    && !str_contains($sequenceDebug, '#123456') && !str_contains($sequenceDebug, $sequenceToken), 'Sequence debug explains local changes without payloads, names or access tokens');
unset($sequencePost, $sequenceDebug);
