<?php

declare(strict_types=1);

$module->LoadAutomationEditor();
$module->SelectAutomation('', '', false, 'time');
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($solarForm['TimeEditorTimeMode']['value'] === 'clock', 'New time rules default to the existing fixed clock');
$runEditorScript($solarForm['TimeEditorTimeMode']['onChange'], $solarForm, ['TimeEditorTimeMode' => 'sunset']);
$check($module->formUpdates['TimeEditorLatitude']['visible'] && !$module->formUpdates['TimeEditorAt']['visible'], 'Selecting solar time reveals coordinates and hides the fixed clock');
$module->ConfigureEditorSchedule('time', false, 'none', 'time');
$check($module->formUpdates['TimeEditorLatitude']['visible'], 'Condition changes preserve the chosen solar mode');
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$nativeSolarValues = ['TimeEditorName' => 'Synthetic solar rule', 'TimeEditorEnabled' => false, 'TimeEditorTimeMode' => 'sunset',
    'TimeEditorLatitude'               => '0,125', 'TimeEditorLongitude' => '-0.25', 'TimeEditorSolarOffset' => -30, 'TimeEditorTimezone' => 'Europe/Berlin',
    'TimeEditorWindow'                 => false, 'TimeEditorConditionType' => 'none', 'TimeEditorConditionList' => new ArrayIterator([]),
    'TimeEditorActions'                => new ArrayIterator([['Choice' => 'group.7:' . hash('sha256', json_encode(['property' => 'state', 'value' => 'ON'], JSON_THROW_ON_ERROR)), 'Delay' => 3]])];
$runEditorScript($solarForm['TimeCreateRuleButton']['onClick'], $solarForm, $nativeSolarValues);
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$solarDraft = json_decode($solarForm['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$solarRule = array_values(array_filter($solarDraft['automations'], static fn (array $r): bool => $r['name'] === 'Synthetic solar rule'))[0] ?? null;
$check($solarRule !== null && !$solarRule['enabled'] && $solarRule['triggers'][0] === ['type' => 'sun', 'event' => 'sunset', 'latitude' => 0.125,
    'longitude'                                                                               => -0.25, 'offset_minutes' => -30, 'timezone' => 'Europe/Berlin'], 'Native form callback creates a typed solar trigger and accepts German decimal commas');
$check($module->ValidateAutomationDraft() !== '' && $state()['operation']['status'] === 'validated', 'Generated solar rule validates in the real JavaScript receiver');
$check($module->StoreAutomationDraft() !== '' && $state()['operation']['status'] === 'stored', 'Generated solar rule is persisted without activating it');
$module->LoadAutomationEditor();
$check($module->SelectAutomation($solarRule['id'], '', false, 'time'), 'Stored solar rule is recognised in the time section');
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($solarForm['TimeEditorTimeMode']['value'] === 'sunset' && $solarForm['TimeEditorLatitude']['value'] === '0.125'
    && $solarForm['TimeEditorLongitude']['value'] === '-0.25' && $solarForm['TimeEditorSolarOffset']['value'] === -30
    && $solarForm['TimeEditorTimezone']['value'] === 'Europe/Berlin' && $solarForm['TimeEditorActions']['values'][0]['Delay'] === 3, 'Reload restores event, coordinates, timezone, offset and action delays');
$nativeSolarValues['TimeEditorTimeMode'] = 'sunrise';
$nativeSolarValues['TimeEditorSolarOffset'] = 45;
$runEditorScript($solarForm['TimeUpdateRuleButton']['onClick'], $solarForm, $nativeSolarValues);
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$editedSolarDraft = json_decode($solarForm['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$editedSolarRule = array_values(array_filter($editedSolarDraft['automations'], static fn (array $r): bool => $r['id'] === $solarRule['id']))[0];
$check($editedSolarRule['triggers'][0]['event'] === 'sunrise' && $editedSolarRule['triggers'][0]['offset_minutes'] === 45, 'Editing keeps the rule identity and applies solar changes');
$nativeSolarValues['TimeEditorLatitude'] = '1e-8';
$runEditorScript($solarForm['TimeUpdateRuleButton']['onClick'], $solarForm, $nativeSolarValues);
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$nativeSolarValues['TimeEditorLatitude'] = $solarForm['TimeEditorLatitude']['value'];
$runEditorScript($solarForm['TimeUpdateRuleButton']['onClick'], $solarForm, $nativeSolarValues);
$check($state()['lastError'] === '' && (float) $nativeSolarValues['TimeEditorLatitude'] === 1e-8, 'Very small valid YAML coordinates remain editable after prefill');
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$beforeInvalidSolar = $solarForm['YamlInput']['value'];
$nativeSolarValues['TimeEditorLatitude'] = '91';
$runEditorScript($solarForm['TimeUpdateRuleButton']['onClick'], $solarForm, $nativeSolarValues);
$check($state()['lastError'] === 'editor_solar', 'Invalid coordinates have a specific understandable error');
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($solarForm['YamlInput']['value'] === $beforeInvalidSolar, 'Rejected solar editing does not change the draft');
$nativeSolarValues['TimeEditorLatitude'] = '';
$nativeSolarValues['TimeEditorTimeMode'] = 'clock';
$nativeSolarValues['TimeEditorAt'] = '17:45';
$runEditorScript($solarForm['TimeUpdateRuleButton']['onClick'], $solarForm, $nativeSolarValues);
$solarForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$clockRule = array_values(array_filter(json_decode($solarForm['YamlInput']['value'], true)['automations'], static fn (array $r): bool => $r['id'] === $solarRule['id']))[0];
$check($clockRule['triggers'] === [['type' => 'time', 'at' => '17:45:00', 'timezone' => 'Europe/Berlin']], 'Changing back to a clock removes solar-only data');
unset($solarForm, $nativeSolarValues, $solarDraft, $editedSolarDraft, $solarRule, $editedSolarRule, $beforeInvalidSolar, $clockRule);
