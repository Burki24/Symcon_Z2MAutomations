<?php

declare(strict_types=1);

$parent->publish('synthetic/z2m/bridge/state', ['state' => 'online']);
$module->LoadAutomationEditor();
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$sceneChoice = 'group.7:' . hash('sha256', json_encode(['property' => 'z2ma_scene', 'value' => 5], JSON_THROW_ON_ERROR));
$sceneDialog = formFields(json_decode($module->GetEditorItemForm('DeviceEditorActions', $sceneChoice, 4), true, 512, JSON_THROW_ON_ERROR));
$sceneOptions = $sceneDialog['RowValue']['options'];
$sceneKey = substr($sceneChoice, strpos($sceneChoice, ':') + 1);
// The real kernel wrapper requires every argument, including PHP optional ones.
foreach (['DeviceEditorActions', 'TimeEditorActions', 'EditorActions'] as $actionList) {
    $module->SelectAutomation('', '', false, str_starts_with($actionList, 'Time') ? 'time' : 'device');
    $dialog = formFields(json_decode($module->GetEditorItemForm($actionList, '', 0), true, 512, JSON_THROW_ON_ERROR));
    foreach (['RowDevice', 'RowProperty', 'RowValue', 'RowNumber'] as $fieldName) {
        $runEditorScript($dialog[$fieldName]['onChange'], $dialog, ['RowDevice' => 'group.7', 'RowProperty' => 'z2ma_scene', 'RowValue' => $sceneKey, 'RowNumber' => 0]);
    }
}
$module->SelectAutomation('', '', false, 'device');
$check(in_array($sceneKey, array_column($sceneOptions, 'value'), true), 'Existing group scenes are offered in the action dialog');
$sceneCaption = array_values(array_filter($sceneOptions, static fn (array $option): bool => $option['value'] === $sceneKey))[0]['caption'];
$check(str_contains($sceneCaption, 'Synthetic group scene'), 'Scene choices display the existing scene name');
$check($sceneDialog['RowDevice']['value'] === 'group.7' && $sceneDialog['RowProperty']['value'] === 'z2ma_scene'
    && $sceneDialog['RowValue']['value'] === substr($sceneChoice, strpos($sceneChoice, ':') + 1) && $sceneDialog['Delay']['value'] === 4, 'Scene target, property, name selection and delay are prefilled');
$module->ConfigureEditorItem('value', 'DeviceEditorActions', 'group.7', 'z2ma_scene', json_encode($sceneDialog['RowValue']['value']));
$check($module->formUpdates['Choice']['value'] === $sceneChoice, 'Native scene selection callback retains the scene identity');
$sceneRows = [['Choice' => $sceneChoice, 'Delay' => 4]];
$module->SelectAutomation('', '', false, 'device');
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$runEditorScript($sceneFields['DeviceCreateRuleButton']['onClick'], $sceneFields, [
    'DeviceEditorName'     => 'Synthetic scene automation', 'DeviceEditorEnabled' => false, 'DeviceEditorWindow' => false, 'DeviceEditorConditionType' => 'none',
    'DeviceEditorTriggers' => new ArrayIterator([['Choice' => '0x0000000000000001:' . $trigger, 'Compare' => 'eq', 'Hold' => 0]]),
    'DeviceEditorActions'  => new ArrayIterator([array_merge($sceneRows[0], ['RowDevice' => 'group.7', 'RowProperty' => 'z2ma_scene', 'RowValue' => $sceneDialog['RowValue']['value']])])]);
$check($state()['operation']['status'] === 'draft_created', 'Actual native button creates a scene rule without YAML editing');
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$sceneDoc = json_decode($sceneFields['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$sceneRule = $sceneDoc['automations'][array_key_last($sceneDoc['automations'])];
$check($sceneRule['actions'] === [['type' => 'scene', 'target' => ['type' => 'group', 'id' => 7], 'scene_id' => 5, 'delay_seconds' => 4]], 'Generated scene action contains only stable Z2M identity and delay');
$check($module->ValidateAutomationDraft() !== '' && $state()['lastError'] === '' && $module->StoreAutomationDraft() !== '', 'Scene draft validates and persists through the real extension');
$module->LoadAutomationEditor();
$check($module->SelectAutomation($sceneRule['id']), 'Stored scene rule can be edited');
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($sceneFields['DeviceEditorActions']['values'] === $sceneRows && $sceneFields['DeviceEditorActions']['enabled'], 'Stored scene action is completely prefilled and editable');
$channelScene = '0x0000000000000004:' . hash('sha256', json_encode(['property' => 'z2ma_scene_2', 'value' => 3], JSON_THROW_ON_ERROR));
$timedSceneRows = array_merge($sceneRows, [['Choice' => $channelScene, 'Delay' => 1]]);
$module->SelectAutomation('', '', false, 'time');
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$runEditorScript($sceneFields['TimeCreateRuleButton']['onClick'], $sceneFields, [
    'TimeEditorName'   => 'Synthetic timed scene', 'TimeEditorEnabled' => false, 'TimeEditorTimes' => new ArrayIterator([['At' => '19:20:00']]), 'TimeEditorTimezone' => 'Europe/Berlin',
    'TimeEditorWindow' => false, 'TimeEditorConditionType' => 'none', 'TimeEditorActions' => new ArrayIterator($timedSceneRows)]);
$check($state()['operation']['status'] === 'draft_created', 'Time editor creates scene actions with the same native controls');
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$timedSceneDoc = json_decode($sceneFields['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$timedSceneRule = $timedSceneDoc['automations'][array_key_last($timedSceneDoc['automations'])];
$module->SelectAutomation($timedSceneRule['id']);
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($sceneFields['TimeEditorActions']['values'] === $timedSceneRows && $sceneFields['TimeEditorAt']['value'] === '19:20:00', 'Time editor restores the scenes, separate endpoint, delays and switching time');
$check($timedSceneRule['actions'][1] === ['type' => 'scene', 'target' => ['type' => 'device', 'id' => '0x0000000000000004'], 'scene_id' => 3, 'delay_seconds' => 1, 'endpoint' => 2], 'Device scene YAML keeps the numeric endpoint and typed scene ID');
$module->SelectAutomation($sceneRule['id']);
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$channelDialog = formFields(json_decode($module->GetEditorItemForm('DeviceEditorActions', $channelScene, 0), true, 512, JSON_THROW_ON_ERROR));
$check($channelDialog['RowProperty']['value'] === 'z2ma_scene_2', 'Scenes on the second device endpoint are offered separately');
$channelVariable = IPS_GetObjectIDByIdent('state_l2', $syntheticDevices[4]);
IPS_SetVariableCustomAction($channelVariable, 1);
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$channelDialog = formFields(json_decode($module->GetEditorItemForm('DeviceEditorActions', $channelScene, 0), true, 512, JSON_THROW_ON_ERROR));
$check($channelDialog['Choice']['value'] === '', 'Revoked channel rights also revoke its scene actions');
IPS_SetVariableCustomAction($channelVariable, 0);

IPS_SetProperty($id, 'PeerModuleID', $otherModuleID);
IPS_ApplyChanges($id);
$parent->publish('synthetic/z2m/bridge/state', ['state' => 'online']);
$module->LoadAutomationEditor();
$otherModule->CopySyntheticImportCatalog($module);
$remoteSceneKey = 'remote.' . hash('sha256', 'synthetic/Z2M-2') . '.group.7';
$remoteScene = $remoteSceneKey . substr($sceneChoice, strpos($sceneChoice, ':'));
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$remoteAvailableDialog = formFields(json_decode($module->GetEditorItemForm('DeviceEditorActions', $remoteScene, 0), true, 512, JSON_THROW_ON_ERROR));
$check($remoteAvailableDialog['Choice']['value'] === $remoteScene, 'Selected peer exports named scenes with separate system identity');
$module->SelectAutomation($sceneRule['id']);
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$remoteRows = [['Choice' => $remoteScene, 'Delay' => 2]];
$runEditorScript($sceneFields['DeviceUpdateRuleButton']['onClick'], $sceneFields, ['DeviceEditorActions' => new ArrayIterator($remoteRows)]);
$check($state()['operation']['status'] === 'draft_updated', 'Native update button changes the action to the scene on the selected peer');
$check($module->ValidateAutomationDraft() !== '' && $module->StoreAutomationDraft() !== '', 'Cross-system scene draft validates and persists');
$module->LoadAutomationEditor();
$module->SelectAutomation($sceneRule['id']);
$sceneFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($sceneFields['DeviceEditorActions']['values'] === $remoteRows, 'Remote scene and delay survive storage and reload');
$sceneDoc = json_decode($sceneFields['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$storedScene = array_values(array_filter($sceneDoc['automations'], static fn (array $rule): bool => $rule['id'] === $sceneRule['id']))[0];
$check($storedScene['actions'][0]['target'] === ['type' => 'group', 'id' => 7, 'system' => 'synthetic/Z2M-2'] && !$storedScene['enabled'], 'Remote scene stays disabled and retains the explicit system');
$remoteDialog = formFields(json_decode($module->GetEditorItemForm('DeviceEditorActions', $remoteScene, 2), true, 512, JSON_THROW_ON_ERROR));
$check($remoteDialog['RowDevice']['value'] === $remoteSceneKey && $remoteDialog['RowProperty']['value'] === 'z2ma_scene', 'Remote scene dialog preselects the correct system and scene property');
IPS_SetVariableCustomAction($otherTarget, 1);
$check(!$module->InspectEditorActions(json_encode($remoteRows), 'edit', 'device'), 'Revoked peer rights block an already selected scene');
IPS_SetVariableCustomAction($otherTarget, 0);
IPS_SetProperty($id, 'PeerModuleID', 0);
IPS_ApplyChanges($id);
$parent->publish('synthetic/z2m/bridge/state', ['state' => 'online']);
$module->LoadAutomationEditor();
$missingSceneDoc = json_decode($sceneFields['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$missingSceneDoc['revision'] = $state()['extension']['storedRevision'] + 1;
$missingSceneDoc['automations'] = [$sceneRule];
$missingSceneDoc['automations'][0]['actions'][0]['scene_id'] = 250;
unset($missingSceneDoc['mesh_peers']);
$module->ValidateYaml(json_encode($missingSceneDoc, JSON_THROW_ON_ERROR));
$check($state()['lastError'] === 'invalid_semantics' && $state()['validationDetails']['issues'][0]['reason'] === 'scene_unavailable', 'Missing scene validation retains the detailed position in the Symcon module');
$sceneFeedbackForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(str_contains($sceneFeedbackForm['ErrorInfo']['caption'], 'scene') && str_contains($sceneFeedbackForm['ErrorInfo']['caption'], '1'), 'Missing scene feedback identifies the affected action for the user');
$sceneDebug = json_encode($module->debugMessages, JSON_THROW_ON_ERROR);
$check(!str_contains($sceneDebug, 'Synthetic group scene') && !str_contains($sceneDebug, 'Synthetic scene automation'), 'Scene debug protects names and user-authored rule text');
unset($sceneDebug, $sceneFields, $sceneDialog, $sceneFeedbackForm);
