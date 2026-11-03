<?php

declare(strict_types=1);

$module->Seed();
$check($module->SelectAutomation('first', '', false, 'time'), 'Open a clock rule for calendar editing');
$calendar = $form();
$check(isset($calendar['TimeEditorTimes'], $calendar['TimeEditorWeekdays'], $calendar['DeviceEditorWeekdays']), 'Time list and independent weekdays are available in the native editors');
foreach (['EditorTimes', 'TimeEditorTimes', 'DeviceEditorTimes'] as $clockList) {
    $check(($calendar[$clockList]['add'] ?? false) && ($calendar[$clockList]['delete'] ?? false), 'Native time lists must expose their add and delete controls');
}
$check($calendar['TimeEditorTimes']['values'] === [['At' => '07:00:00']] && !$calendar['TimeEditorWeekdays']['value'], 'An existing single time is prefilled without inventing a weekday restriction');
$originalCalendarYaml = $calendar['YamlInput']['value'];
$calendarOverrides = ['TimeEditorTimes' => new ArrayIterator([['At' => '07:30'], ['At' => '18:45:12']]),
    'TimeEditorTimezone'                => 'UTC', 'TimeEditorWeekdays' => true, 'TimeEditorWeekdayTimezone' => 'Pacific/Auckland', 'TimeEditorWindow' => false];
foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day) {
    $calendarOverrides['TimeEditorDay' . $day] = in_array($i + 1, [1, 3, 5], true);
}
$run($calendar['TimeEditorWeekdays']['onChange'], $calendar, $calendarOverrides);
$check($module->updates['TimeEditorDayMonday']['visible'] && $module->updates['TimeEditorWeekdayTimezone']['visible'], 'Independent weekdays are visible while the time window stays disabled');
$pendingCalendar = $form();
$check($pendingCalendar['TimeEditorTimes']['values'] === [['At' => '07:30:00'], ['At' => '18:45:12']]
    && $pendingCalendar['TimeEditorWeekdays']['value'] && !$pendingCalendar['TimeEditorDayTuesday']['value']
    && $pendingCalendar['YamlInput']['value'] === $originalCalendarYaml, 'Reopening retains fresh time rows and weekday input without touching the draft');
$run($pendingCalendar['TimeUpdateRuleButton']['onClick'], $pendingCalendar);
$calendar = $form();
$calendarDoc = json_decode($calendar['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$calendarRule = $calendarDoc['automations'][0];
$check($calendarRule['triggers'] === [['type' => 'time', 'at' => '07:30:00', 'timezone' => 'UTC'], ['type' => 'time', 'at' => '18:45:12', 'timezone' => 'UTC']]
    && $calendarRule['conditions'] === [['type' => 'weekday', 'weekdays' => [1, 3, 5], 'timezone' => 'Pacific/Auckland']], 'Native update emits independent weekday condition and OR clock triggers');
$check(!$calendarRule['enabled'] && $calendarDoc['automations'][1] === json_decode($originalCalendarYaml, true)['automations'][1], 'Other rules and disabled state remain unchanged');
$check($module->SelectAutomation('second', '', false, 'time') && $module->SelectAutomation('first', '', false, 'time'), 'Leave and reopen the multiple-time rule');
$calendar = $form();
$check($calendar['TimeEditorTimes']['values'] === [['At' => '07:30:00'], ['At' => '18:45:12']] && $calendar['TimeEditorTimezone']['value'] === 'UTC'
    && $calendar['TimeEditorWeekdayTimezone']['value'] === 'Pacific/Auckland' && !$calendar['TimeEditorDaySunday']['value']
    && $calendar['TimeEditorTimes']['enabled'], 'All schedule fields are editable and correctly prefilled after selection');
$calendarYaml = $calendar['YamlInput']['value'];
$options = ['EditorTriggerType' => 'time', 'EditorTimes' => [['At' => '07:30'], ['At' => '18:45:12']], 'EditorTimezone' => 'UTC', 'EditorWeekdays' => true,
    'EditorWeekdayTimezone'     => 'Pacific/Auckland'];
foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day) {
    $options['EditorDay' . $day] = in_array($i + 1, [1, 3, 5], true);
}
$calendarSave = static function (array $options, string $conditions = '[]') use ($module, $form): bool
{
    $fields = $form();
    return $module->SaveScheduledAutomation(
        'first',
        $fields['TimeEditorEditToken']['value'],
        'Synthetic first',
        false,
        '',
        '',
        '',
        '',
        json_encode($options, JSON_THROW_ON_ERROR),
        $fields['YamlInput']['value'],
        true,
        json_encode($fields['TimeEditorActions']['values'], JSON_THROW_ON_ERROR),
        'time',
        '',
        $conditions
    );
};
foreach ([[], [['At' => '24:00']], [['At' => '07:30'], ['At' => '07:30:00']], array_fill(0, 9, ['At' => '07:30']), [['At' => 730]], [['At' => '07:30', 'extra' => true]]] as $invalidTimes) {
    $check(!$calendarSave(array_replace($options, ['EditorTimes' => $invalidTimes])) && $form()['YamlInput']['value'] === $calendarYaml, 'Invalid, duplicate or excessive clock rows are rejected atomically');
}
$noDays = array_replace($options, array_fill_keys(array_map(static fn (string $day): string => 'EditorDay' . $day, ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']), false));
$check(!$calendarSave($noDays) && !$calendarSave(array_replace($options, ['EditorWeekdayTimezone' => 'invalid-zone']))
    && $form()['YamlInput']['value'] === $calendarYaml, 'Empty weekdays and invalid timezone do not overwrite the draft');
$check(!$module->InspectEditorTimes('{bad', 'edit', 'time') && !$module->InspectEditorTimes('[]', 'delete', 'time'), 'Time inspection contains malformed or empty input');
$staleYaml = $form()['YamlInput']['value'];
$run($pendingCalendar['TimeUpdateRuleButton']['onClick'], $pendingCalendar);
$check($form()['YamlInput']['value'] === $staleYaml, 'Stale native callbacks cannot overwrite a newer multiple-time draft');

// The time-window start weekdays and current calendar weekdays remain separate clauses.
$options['EditorWindow'] = true;
$options['EditorAfter'] = '22:00';
$options['EditorBefore'] = '06:00';
$options['EditorWindowTimezone'] = 'UTC';
foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day) {
    $options['Editor' . $day] = $i === 0;
}
$check($calendarSave($options), 'Independent weekdays combine with an overnight window');
$windowCalendar = $form();
$windowRule = json_decode($windowCalendar['YamlInput']['value'], true)['automations'][0];
$check($windowRule['conditions'][0]['weekdays'] === [1, 3, 5] && $windowRule['conditions'][1]['weekdays'] === [1]
    && $windowCalendar['TimeEditorDayWednesday']['value'] && !$windowCalendar['TimeEditorWednesday']['value'], 'Prefill does not confuse calendar days with the window anchor days');
$conditionChoice = $windowCalendar['TimeEditorActions']['values'][0]['Choice'];
$conditionRows = array_fill(0, 14, ['Choice' => $conditionChoice, 'Compare' => 'eq']);
$check($calendarSave($options, json_encode($conditionRows)), 'Fourteen device conditions plus weekday and window fit the shared bound');
$beforeOverflow = $form()['YamlInput']['value'];
$conditionRows[] = $conditionRows[0];
$check(!$calendarSave($options, json_encode($conditionRows)) && $form()['YamlInput']['value'] === $beforeOverflow, 'Weekdays count towards the sixteen-condition limit');
$check(!$module->HasPendingWorkflow() && str_contains(implode('', $module->debug), 'Zeit- und Wochentagsangaben'), 'Schedule changes have understandable local debug output and do not start MQTT workflows');

$module->Seed();
$check($module->SelectAutomation('', '', false, 'time'), 'Open a fresh timed rule');
$freshCalendar = $form();
$freshRows = new ArrayIterator([['At' => '06:15'], ['At' => '21:40']]);
$run($freshCalendar['TimeEditorTimes']['onAdd'], $freshCalendar, ['TimeEditorTimes' => $freshRows]);
$freshCalendar = $form();
$run($freshCalendar['TimeCreateRuleButton']['onClick'], $freshCalendar, ['TimeEditorName' => 'Synthetic two times', 'TimeEditorEnabled' => false,
    'TimeEditorActions'                                                                   => new ArrayIterator($freshCalendar['TimeEditorActions']['values'] ?: $calendar['TimeEditorActions']['values']), 'TimeEditorTimes' => $freshRows]);
$freshCalendar = $form();
$createdCalendarDoc = json_decode($freshCalendar['YamlInput']['value'], true);
$createdClock = $createdCalendarDoc['automations'][2] ?? null;
$check($createdClock !== null && array_column($createdClock['triggers'], 'at') === ['06:15:00', '21:40:00'], 'Native create serializes the complete times list');
$check($module->SelectAutomation($createdClock['id'], '', false, 'time'), 'Open the newly created multiple-time rule');
$deleteClock = $form();
$run($deleteClock['TimeEditorTimes']['onDelete'], $deleteClock);
$run($deleteClock['TimeUpdateRuleButton']['onClick'], $deleteClock, ['TimeEditorTimes' => new ArrayIterator([['At' => '21:40']])]);
$afterClockDeletion = $form();
$check($afterClockDeletion['TimeEditorTimes']['values'] === [['At' => '21:40:00']], 'Pre-delete callbacks do not resurrect a removed time when the actual remaining list is applied');
$beforeEmptyClock = $afterClockDeletion['YamlInput']['value'];
$run($afterClockDeletion['TimeUpdateRuleButton']['onClick'], $afterClockDeletion, ['TimeEditorTimes' => new ArrayIterator([])]);
$check($form()['YamlInput']['value'] === $beforeEmptyClock, 'Deleting the last time must be corrected before the draft can change');

$calendarAction = json_decode($originalCalendarYaml, true)['automations'][0]['actions'];
$weekdayOnly = ['type' => 'weekday', 'weekdays' => [6, 7], 'timezone' => 'Europe/Berlin'];
$calendarDevice = ['id' => 'calendar_device', 'name' => 'Synthetic weekend device', 'enabled' => false,
    'triggers'          => [['type' => 'device', 'device' => '0x0000000000000001', 'property' => 'state', 'operator' => 'eq', 'value' => 'ON']],
    'conditions'        => [$weekdayOnly], 'actions' => $calendarAction];
$calendarSolar = array_replace($calendarDevice, ['id' => 'calendar_solar', 'triggers' => [['type' => 'sun', 'event' => 'sunset', 'latitude' => 0.0,
    'longitude'                                                                                   => 0.0, 'offset_minutes' => -15, 'timezone' => 'UTC']]]);
$differentZones = array_replace($calendarDevice, ['id' => 'calendar_zones', 'triggers' => [
    ['type' => 'time', 'at' => '07:00:00', 'timezone' => 'UTC'], ['type' => 'time', 'at' => '08:00:00', 'timezone' => 'Europe/Berlin']]]);
$module->Seed([$calendarDevice, $calendarSolar, $differentZones]);
foreach (['calendar_device' => 'Device', 'calendar_solar' => 'Time'] as $ruleID => $prefix) {
    $check($module->SelectAutomation($ruleID, '', false, strtolower($prefix)), 'Open an existing weekday device or solar rule');
    $weekdayForm = $form();
    $check($weekdayForm[$prefix . 'EditorWeekdays']['value'] && !$weekdayForm[$prefix . 'EditorWindow']['value']
        && $weekdayForm[$prefix . 'EditorDaySaturday']['value'] && !$weekdayForm[$prefix . 'EditorDayFriday']['value'], 'Weekdays prefill independently for device and solar triggers');
    $run($weekdayForm[$prefix . 'UpdateRuleButton']['onClick'], $weekdayForm);
    $afterWeekday = json_decode($form()['YamlInput']['value'], true);
    $check($afterWeekday['automations'][$ruleID === 'calendar_device' ? 0 : 1]['conditions'] === [$weekdayOnly], 'Native save preserves an existing independent weekday condition');
}
$check($module->SelectAutomation('calendar_zones', '', false, 'time'), 'Open a YAML schedule with different trigger zones');
$unrepresentedCalendar = $form();
$check(!$unrepresentedCalendar['TimeEditorTimes']['enabled'], 'Different per-trigger zones are not silently collapsed into one editor timezone');
$originalZones = json_decode($unrepresentedCalendar['YamlInput']['value'], true)['automations'][2]['triggers'];
$run($unrepresentedCalendar['TimeUpdateRuleButton']['onClick'], $unrepresentedCalendar, ['TimeEditorName' => 'Synthetic zone metadata']);
$check(json_decode($form()['YamlInput']['value'], true)['automations'][2]['triggers'] === $originalZones, 'Metadata-only editing preserves unsupported per-trigger zone semantics');
echo "Multiple clock times and independent weekday editor checks passed.\n";
