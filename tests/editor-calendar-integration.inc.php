<?php

declare(strict_types=1);

$module->LoadAutomationEditor();
$module->SelectAutomation('', '', false, 'time');
$calendarFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$calendarBase = json_decode($calendarFields['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$clockChoice = 'group.7:' . hash('sha256', json_encode(['property' => 'state', 'value' => 'ON'], JSON_THROW_ON_ERROR));
$nativeCalendar = ['TimeEditorName' => 'Synthetic calendar integration', 'TimeEditorEnabled' => false, 'TimeEditorTimeMode' => 'clock',
    'TimeEditorRetrigger'           => 'restart',
    'TimeEditorTimes'               => new ArrayIterator([['At' => '06:20'], ['At' => '20:10:05']]), 'TimeEditorTimezone' => 'Europe/Berlin',
    'TimeEditorWeekdays'            => true, 'TimeEditorWeekdayTimezone' => 'UTC', 'TimeEditorWindow' => false,
    'TimeEditorConditionList'       => new ArrayIterator([]), 'TimeEditorActions' => new ArrayIterator([['Choice' => $clockChoice, 'Delay' => 2]])];
foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day) {
    $nativeCalendar['TimeEditorDay' . $day] = in_array($i + 1, [2, 4], true);
}
$runEditorScript($calendarFields['TimeCreateRuleButton']['onClick'], $calendarFields, $nativeCalendar);
$check($state()['operation']['status'] === 'draft_created', 'Native time list and weekdays create a disabled rule');
$calendarFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$calendarCreated = array_slice(json_decode($calendarFields['YamlInput']['value'], true)['automations'], -1)[0];
$check(!$calendarCreated['enabled'] && $calendarCreated['retrigger'] === 'restart' && count($calendarCreated['triggers']) === 2 && $calendarCreated['conditions'] === [
    ['type' => 'weekday', 'weekdays' => [2, 4], 'timezone' => 'UTC']], 'Generated calendar rule has typed triggers and independent weekday condition');
$check($module->ValidateAutomationDraft() !== '' && $state()['operation']['status'] === 'validated', 'Real JavaScript receiver validates native calendar output');
$check($module->StoreAutomationDraft() !== '' && $state()['operation']['status'] === 'stored', 'Calendar output is persisted without activation');
$module->LoadAutomationEditor();
$module->SelectAutomation($calendarCreated['id'], '', false, 'time');
$calendarFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($calendarFields['TimeEditorTimes']['values'] === [['At' => '06:20:00'], ['At' => '20:10:05']]
    && $calendarFields['TimeEditorTimezone']['value'] === 'Europe/Berlin' && $calendarFields['TimeEditorWeekdayTimezone']['value'] === 'UTC'
    && $calendarFields['TimeEditorDayTuesday']['value'] && !$calendarFields['TimeEditorDayMonday']['value']
    && !$calendarFields['TimeEditorWindow']['value'] && $calendarFields['TimeEditorRetrigger']['value'] === 'restart', 'Reload from JavaScript persistence prefills calendar fields and retrigger policy');
$nativeCalendar['TimeEditorTimes'] = new ArrayIterator([['At' => '21:05']]);
$nativeCalendar['TimeEditorRetrigger'] = 'extend';
$nativeCalendar['TimeEditorDayTuesday'] = $nativeCalendar['TimeEditorDayThursday'] = false;
$nativeCalendar['TimeEditorDaySunday'] = true;
$runEditorScript($calendarFields['TimeUpdateRuleButton']['onClick'], $calendarFields, $nativeCalendar);
$check($state()['operation']['status'] === 'draft_updated' && $module->ValidateAutomationDraft() !== '' && $state()['operation']['status'] === 'validated'
    && $module->StoreAutomationDraft() !== '' && $state()['operation']['status'] === 'stored', 'Edited clock list and weekday values validate and persist again');
$module->LoadAutomationEditor();
$module->SelectAutomation($calendarCreated['id'], '', false, 'time');
$calendarFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($calendarFields['TimeEditorTimes']['values'] === [['At' => '21:05:00']] && $calendarFields['TimeEditorDaySunday']['value']
    && !$calendarFields['TimeEditorDayThursday']['value'] && $calendarFields['TimeEditorRetrigger']['value'] === 'extend', 'Second reload reflects edited calendar fields and policy');
$runEditorScript($calendarFields['TimeDeleteRuleButton']['onClick'], $calendarFields, []);
$calendarFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(json_decode($calendarFields['YamlInput']['value'], true)['automations'] === $calendarBase['automations'], 'Deleting the synthetic calendar rule preserves all previous rules');
$check($module->ValidateAutomationDraft() !== '' && $module->StoreAutomationDraft() !== '', 'Restore the previous synthetic stored rule set');
$module->LoadAutomationEditor();
unset($calendarFields, $calendarBase, $clockChoice, $nativeCalendar, $calendarCreated);
