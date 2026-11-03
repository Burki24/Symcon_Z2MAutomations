<?php

declare(strict_types=1);

$module->LoadAutomationEditor();
$commandFields = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$commandBase = json_decode($commandFields['YamlInput']['value'], true);
$module->SelectAutomation('', '', false, 'time');
$commandDevice = '0x0000000000000004';
$commandChoice = '';
foreach (['state_l1' => hash('sha256', json_encode(['property' => 'state_l1', 'value' => 'ON'])), 'level' => 60, 'color' => 0x547CFF] as $property => $value) {
    $check($module->ChangeEditorCommand('TimeEditorActions', $commandDevice, $commandChoice, (string) IPS_GetObjectIDByIdent($property, $syntheticDevices[4]), json_encode($value)), 'Compose an action from the real synthetic catalog and native variable bindings');
    $commandChoice = $module->formUpdates['Choice']['value'];
}
$commandFields = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$runEditorScript($commandFields['TimeCreateRuleButton']['onClick'], $commandFields, ['TimeEditorName' => 'Synthetic compound color', 'TimeEditorEnabled' => false,
    'TimeEditorActions'                                                                               => new ArrayIterator([['Choice' => $commandChoice, 'Delay' => 2]])]);
$commandFields = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$commandRule = array_slice(json_decode($commandFields['YamlInput']['value'], true)['automations'], -1)[0];
$check(count($commandRule['actions']) === 1 && $commandRule['actions'][0]['values'] === ['color' => ['hex' => '#547CFF'], 'level' => 60, 'state_l1' => 'ON'], 'Native create emits exactly one typed multi-property command');
$check($module->ValidateAutomationDraft() !== '' && $state()['operation']['status'] === 'validated' && $module->StoreAutomationDraft() !== '' && $state()['operation']['status'] === 'stored', 'Real JavaScript receiver validates and persists the new command');
$module->LoadAutomationEditor();
$module->SelectAutomation($commandRule['id'], '', false, 'time');
$commandFields = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$commandDialog = formFields(json_decode($module->GetEditorItemForm('TimeEditorActions', $commandFields['TimeEditorActions']['values'][0]['Choice'], 2), true));
$check($commandDialog['RowActionKind']['value'] === 'combined' && $commandDialog['RowColor']['value'] === 0x547CFF && count($commandDialog['RowCommandSummary']['values']) === 3, 'Reloaded combined command and exact RGB color are prefilled');
$runEditorScript($commandFields['TimeDeleteRuleButton']['onClick'], $commandFields, []);
$commandFields = formFields(json_decode($module->GetConfigurationForm(), true)['actions']);
$check(json_decode($commandFields['YamlInput']['value'], true)['automations'] === $commandBase['automations'], 'Removing the new rule preserves all pre-existing rules');
$check($module->ValidateAutomationDraft() !== '' && $module->StoreAutomationDraft() !== '', 'Restore the synthetic stored rule set');
unset($commandFields, $commandBase, $commandRule, $commandDialog, $commandChoice);
