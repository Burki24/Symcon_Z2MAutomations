<?php

declare(strict_types=1);

namespace Z2MAutomations;

use DateTimeZone;
use JsonException;

trait AutomationEditorSchedule
{
    public function ConfigureEditorSchedule(string $TriggerType, bool $Window, string $ConditionType, string $Area = ''): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($TriggerType, $Window, $ConditionType, $Area): void
        {
            if (!$this->CheckEditorArea($Area)) {
                return;
            }
            if (!in_array($TriggerType, ['device', 'time'], true) || !in_array($ConditionType, ['none', 'state', 'numeric'], true) || ($Area !== '' && $TriggerType !== $Area)) {
                $this->DebugEvent('FEHLER', 'Unbekannte Zeit- oder Bedingungsauswahl abgewiesen.');
                return;
            }
            $modes = ['EditorTriggerType' => $TriggerType, 'EditorWindow' => $Window, 'EditorConditionType' => $ConditionType];
            $previous = $this->ReadEditorAttribute('EditorModes');
            $previous = $previous === '' ? [] : json_decode($previous, true, 8, JSON_THROW_ON_ERROR);
            $modes['EditorTimeMode'] = $previous['EditorTimeMode'] ?? 'clock';
            $modes['EditorWeekdays'] = $previous['EditorWeekdays'] ?? false;
            $this->WriteEditorAttribute('EditorModes', json_encode($modes, JSON_THROW_ON_ERROR));
            $this->UpdateEditorField('EditorCompare', 'options', json_encode($this->EditorComparisonOptions($ConditionType), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            if ($ConditionType === 'state' && ($previous['EditorConditionType'] ?? '') !== 'state') {
                $this->UpdateEditorField('EditorCompare', 'value', 'eq');
            }
            $selection = $this->EditorSelectionFields($this->ReadEditorDraft());
            foreach ($this->ScheduleVisibility($modes) as $name => $visible) {
                $this->UpdateEditorField($name, 'visible', $visible);
                if (!in_array($name, ['WindowHelp', 'ConditionHelp', 'EditorCalendarHelp', 'EditorDeviceTrigger', 'EditorTimeTrigger'], true)) {
                    $this->UpdateEditorField($name, 'enabled', $selection['simple']);
                }
            }
            $this->DebugEvent('AKTION', 'Editor für Auslöser und Bedingungen angepasst.', ['Bereich' => $this->EditorTypeLabel($TriggerType), 'Zeitfenster' => $Window, 'Bedingungsart' => $ConditionType]);
        });
    }

    public function SaveScheduledAutomation(string $RuleID, string $SelectionToken, string $Name, bool $Enabled, string $Source, string $Trigger, string $Target, string $Action, string $Options, string $CurrentDraft = '', bool $CheckDraft = false, string $ActionSteps = '', string $Area = '', string $TriggerSteps = '', string $ConditionSteps = ''): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($RuleID, $SelectionToken, $Name, $Enabled, $Source, $Trigger, $Target, $Action, $Options, $CurrentDraft, $CheckDraft, $ActionSteps, $Area, $TriggerSteps, $ConditionSteps): bool
        {
            if (!$this->CheckEditorArea($Area)) {
                return false;
            }
            $state = $this->RefreshState();
            $draft = $this->ReadEditorDraft();
            $error = $this->EditorContextError($state, $draft, $CurrentDraft, $CheckDraft);
            $index = $this->EditorRuleIndex($draft, $RuleID);
            if ($error === '' && $Area !== '' && $RuleID === '' && $this->EditorSelectionFields($draft)['selected']) {
                $error = 'editor_changed';
            }
            if ($error === '' && $RuleID !== '' && ($index === null || !$this->MatchesEditorSelection($draft, $RuleID, $SelectionToken))) {
                $error = 'editor_changed';
            }
            if ($error === '' && (!$this->ValidEditorName(trim($Name)) || ($RuleID === '' && count($draft['configuration']['automations']) >= 100))) {
                $error = 'editor_invalid';
            }
            if ($error !== '') {
                return $this->RejectEditorChange($state, $error);
            }
            $rule = $index === null ? null : $draft['configuration']['automations'][$index];
            if ($rule !== null && $this->ScheduledEditorFields($rule) === null) {
                // Unsupported structures stay intact; only explicit metadata changes apply.
                if ($Source !== '' || $Trigger !== '' || $Target !== '' || $Action !== '' || ($ActionSteps !== '' && $ActionSteps !== '[]') || ($TriggerSteps !== '' && $TriggerSteps !== '[]') || ($ConditionSteps !== '' && $ConditionSteps !== '[]')) {
                    return $this->RejectEditorChange($state, 'editor_advanced');
                }
                $parts = array_intersect_key($rule, array_flip(['triggers', 'conditions', 'actions']));
            } else {
                if ($rule !== null && count($rule['triggers']) > 1 && $rule['triggers'][0]['type'] === 'device' && $TriggerSteps === '') {
                    return $this->RejectEditorChange($state, 'editor_triggers');
                }
                if ($rule !== null && count($rule['triggers']) > 1 && $rule['triggers'][0]['type'] === 'time') {
                    $clockOptions = strlen($Options) <= 8192 ? json_decode($Options, true) : null;
                    if (!is_array($clockOptions) || !array_key_exists('EditorTimes', $clockOptions)) {
                        return $this->RejectEditorChange($state, 'editor_times');
                    }
                }
                if ($rule !== null && $ActionSteps === '' && (count($rule['actions']) > 1 || count(array_filter($rule['actions'], static fn (array $a): bool => count($a['values'] ?? []) > 1)) > 0)) {
                    return $this->RejectEditorChange($state, 'editor_actions');
                }
                if ($rule !== null && $ConditionSteps === '' && (count(array_filter($rule['conditions'], static fn (array $c): bool => $c['type'] === 'device_value')) > 1
                    || count(array_filter($rule['conditions'], static fn (array $c): bool => $c['start_only'] ?? false)) > 0)) {
                    return $this->RejectEditorChange($state, 'editor_condition');
                }
                $reason = 'editor_schedule';
                $parts = $this->BuildScheduledRule($Source, $Trigger, $Target, $Action, $Options, $reason, $ActionSteps, $TriggerSteps, $ConditionSteps, $rule['retrigger'] ?? 'ignore', $rule['restart_policy'] ?? 'abort');
                if ($parts === null) {
                    return $this->RejectEditorChange($state, $reason);
                }
            }
            if ($Area !== '' && $this->EditorRuleType(['triggers' => $parts['triggers']]) !== $Area) {
                return $this->RejectEditorChange($state, 'editor_changed');
            }
            $rule = array_replace($rule ?? ['id' => 'rule_' . bin2hex(random_bytes(8))], $parts, ['name' => trim($Name), 'enabled' => $Enabled]);
            if ($index === null) {
                $draft['configuration']['automations'][] = $rule;
            } else {
                $draft['configuration']['automations'][$index] = $rule;
            }
            $this->DebugEvent('AKTION', 'Auslöser und Bedingungen für den lokalen Entwurf vorbereitet.', ['Bereich' => $this->EditorTypeLabel($this->EditorRuleType($rule)), 'Auslöser' => count($rule['triggers']), 'Verknüpfung' => 'ODER', 'Bedingungen' => count($rule['conditions']),
                'NurBeimStart'                                                                                      => count(array_filter($rule['conditions'], static fn (array $c): bool => $c['start_only'] ?? false)), 'Bedingungsverknüpfung' => 'UND']);
            $this->DebugEditorActionSteps($rule['actions']);
            $this->DebugRestartPolicy($rule);
            $this->DebugEvent('AKTION', 'Verhalten bei erneuter Auslösung für den lokalen Entwurf übernommen.', ['ErneuteAuslösung' => match ($rule['retrigger'] ?? 'ignore') {
                'restart' => 'Von vorn beginnen', 'extend' => 'Laufende Wartezeit zurücksetzen', default => 'Ignorieren'
            }]);
            $this->DebugEvent('AKTION', 'Zeit- und Wochentagsangaben für den Entwurf geprüft; in Z2M wird noch nichts geändert.', [
                'FesteUhrzeiten'   => count(array_filter($rule['triggers'], static fn (array $t): bool => $t['type'] === 'time')),
                'Wochentagsfilter' => count(array_filter($rule['conditions'], static fn (array $c): bool => $c['type'] === 'weekday')) > 0,
                'Zeitfenster'      => count(array_filter($rule['conditions'], static fn (array $c): bool => $c['type'] === 'time_window')) > 0]);
            return $this->CommitEditorChange($state, $draft, $index === null ? 'draft_created' : 'draft_updated', $index === null ? '' : $RuleID);
        }));
    }

    public function SelectEditorConditionProperty(string $Property, string $Area = ''): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Property, $Area): void
        {
            if (!$this->CheckEditorArea($Area)) {
                return;
            }
            $snapshot = $this->EditorSnapshot();
            $device = $this->ReadEditorAttribute('EditorCondition');
            $meta = $this->EditorProperties($snapshot, $device, 'Source')[$Property] ?? null;
            if (($meta['type'] ?? 'numeric') === 'numeric') {
                $Property = '';
                $meta = null;
            }
            $this->WriteEditorAttribute('EditorConditionProperty', $Property);
            $this->UpdateEditorField('EditorConditionChoice', 'options', json_encode($this->EditorItemValues($snapshot, $device, $meta, 'Source'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $this->UpdateEditorField('EditorConditionChoice', 'value', '');
            $this->DebugEvent('AKTION', 'Eigenschaft der Zustandsbedingung ausgewählt; Wertauswahl zurückgesetzt.', ['EigenschaftVerfügbar' => $meta !== null]);
        });
    }

    private function ConditionPropertyOptions(array $snapshot, string $device): array
    {
        return array_values(array_filter($this->EditorItemProperties($snapshot, $device, 'Source'), fn (array $option): bool => $option['value'] === '' || ($snapshot['devices'][$device]['properties'][$option['value']]['type'] ?? 'numeric') !== 'numeric'));
    }

    private function ConditionMeta(array $snapshot, array $fields): ?array
    {
        $device = $fields['EditorCondition'];
        $choice = $this->FindEditorChoice($snapshot, $device, $fields['EditorConditionChoice'], 'Source');
        $property = $choice['property'] ?? $this->ReadEditorAttribute('EditorConditionProperty');
        return $this->EditorProperties($snapshot, $device, 'Source')[$property] ?? null;
    }

    private function ScheduleDefaults(): array
    {
        return ['EditorRestartPolicy' => 'abort', 'EditorRetrigger'   => 'ignore', 'EditorTriggerType' => 'device', 'EditorTimeMode' => 'clock', 'EditorLatitude' => '', 'EditorLongitude' => '', 'EditorSolarOffset' => 0, 'EditorAt' => '18:00:00', 'EditorTimezone' => 'Europe/Berlin', 'EditorHold' => 0, 'EditorDelay' => 0,
            'EditorWindow'          => false, 'EditorAfter' => '18:00:00', 'EditorBefore' => '06:00:00', 'EditorWindowTimezone' => 'Europe/Berlin',
            'EditorMonday'          => true, 'EditorTuesday' => true, 'EditorWednesday' => true, 'EditorThursday' => true, 'EditorFriday' => true, 'EditorSaturday' => true, 'EditorSunday' => true,
            'EditorConditionType'   => 'none', 'EditorCondition' => '', 'EditorConditionChoice' => '', 'EditorNumericProperty' => '', 'EditorCompare' => 'eq', 'EditorNumber' => 0.0] + $this->CalendarDefaults();
    }

    private function DebugRestartPolicy(array $rule): void
    {
        $this->DebugEvent('AKTION', 'Fortsetzung nach Z2M-Neustart für den lokalen Entwurf geprüft; kein Lauf gestartet.', [
            'Verhalten' => match ($rule['restart_policy'] ?? 'abort') {
                'resume' => 'Wartezeit fortsetzen; bei Ablauf den Lauf beenden',
                'resume_expired' => 'Wartezeit fortsetzen; bei Ablauf die ausstehende Aktion nachholen',
                default => 'Unterbrochenen Lauf beenden'
            }]);
    }

    private function EditorClockTime(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        return preg_match('/\A(?:[01][0-9]|2[0-3]):[0-5][0-9](?::[0-5][0-9])?\z/D', $value) === 1 ? (strlen($value) === 5 ? $value . ':00' : $value) : null;
    }

    private function BuildScheduledRule(string $sourceID, string $triggerKey, string $targetID, string $actionKey, string $options, string &$reason, string $actionSteps = '', string $triggerSteps = '', string $conditionSteps = '', string $retrigger = 'ignore', string $restartPolicy = 'abort'): ?array
    {
        if (strlen($options) > 8192) {
            return null;
        }
        try {
            $input = json_decode($options, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        $defaults = $this->ScheduleDefaults();
        $defaults['EditorRetrigger'] = $retrigger;
        $defaults['EditorRestartPolicy'] = $restartPolicy;
        if (!is_array($input) || array_diff(array_keys($input), array_keys($defaults)) !== []) {
            return null;
        }
        $v = array_replace($defaults, $input);
        if (!in_array($v['EditorRetrigger'], ['ignore', 'restart', 'extend'], true)) {
            $reason = 'editor_retrigger';
            return null;
        }
        if (!in_array($v['EditorRestartPolicy'], ['abort', 'resume', 'resume_expired'], true)) {
            $reason = 'editor_restart_policy';
            return null;
        }
        foreach ($defaults as $key => $default) {
            if ((is_bool($default) && !is_bool($v[$key])) || (is_string($default) && !is_string($v[$key]))) {
                return null;
            }
        }
        if (!in_array($v['EditorTriggerType'], ['device', 'time'], true) || !in_array($v['EditorConditionType'], ['none', 'state', 'numeric'], true)
            || !in_array($v['EditorTimeMode'], ['clock', 'sunrise', 'sunset'], true)
            || !in_array($v['EditorCompare'], ['eq', 'ne', 'gt', 'gte', 'lt', 'lte'], true)) {
            return null;
        }
        foreach (['EditorHold', 'EditorDelay'] as $key) {
            if (!is_int($v[$key]) || $v[$key] < 0 || $v[$key] > 86400) {
                $reason = 'editor_duration';
                return null;
            }
        }
        $snapshot = $this->EditorSnapshot();
        if ($actionSteps === '') {
            $target = $this->FindEditorChoice($snapshot, $targetID, $actionKey, 'Target');
            if ($target === null) {
                return null;
            }
            $actions = [$this->BuildEditorAction($targetID, $target, $v['EditorDelay'])];
        } else {
            $reason = 'editor_actions';
            $actions = $this->BuildEditorActions($actionSteps, $reason);
            if ($actions === null) {
                return null;
            }
            $reason = 'editor_schedule';
        }
        $triggers = null;
        if ($v['EditorTriggerType'] === 'time' && $triggerSteps !== '' && $triggerSteps !== '[]') {
            $reason = 'editor_triggers';
            return null;
        }
        if ($v['EditorTriggerType'] === 'device' && $triggerSteps !== '') {
            $reason = 'editor_triggers';
            $triggers = $this->BuildEditorTriggers($triggerSteps);
            if ($triggers === null) {
                return null;
            }
            $trigger = $triggers[0];
        } elseif ($v['EditorTriggerType'] === 'time') {
            if (!in_array($v['EditorTimezone'], DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
                $reason = 'editor_timezone';
                return null;
            }
            if ($v['EditorTimeMode'] === 'clock') {
                $rows = $this->NormalizeClockEditorRows(array_key_exists('EditorTimes', $input) ? $v['EditorTimes'] : [['At' => $v['EditorAt']]]);
                if ($rows === null) {
                    $reason = 'editor_times';
                    return null;
                }
                $triggers = array_map(static fn (array $row): array => ['type' => 'time', 'at' => $row['At'], 'timezone' => $v['EditorTimezone']], $rows);
                $trigger = $triggers[0];
            } else {
                $trigger = $this->BuildSolarEditorTrigger($v);
                if ($trigger === null) {
                    $reason = 'editor_solar';
                    return null;
                }
            }
        } else {
            $source = $this->FindEditorChoice($snapshot, $sourceID, $triggerKey, 'Source');
            if ($source === null) {
                return null;
            }
            $trigger = ['type' => 'device', 'device' => $sourceID, 'property' => $source['property'], 'operator' => 'eq', 'value' => $source['value'], 'for_seconds' => $v['EditorHold']];
        }
        $conditions = [];
        if ($v['EditorWeekdays']) {
            $weekday = $this->BuildWeekdayCondition($v, $reason);
            if ($weekday === null) {
                return null;
            }
            $conditions[] = $weekday;
        }
        if ($v['EditorWindow']) {
            $after = $this->EditorClockTime($v['EditorAfter']);
            $before = $this->EditorClockTime($v['EditorBefore']);
            $days = [];
            foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day) {
                if ($v['Editor' . $day]) {
                    $days[] = $i + 1;
                }
            }
            if ($after === null || $before === null || $after === $before) {
                $reason = 'editor_window';
                return null;
            }
            if ($days === []) {
                $reason = 'editor_weekdays';
                return null;
            }
            if (!in_array($v['EditorWindowTimezone'], DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
                $reason = 'editor_timezone';
                return null;
            }
            $conditions[] = ['type' => 'time_window', 'after' => $after, 'before' => $before, 'timezone' => $v['EditorWindowTimezone'], 'weekdays' => $days];
        }
        if ($conditionSteps !== '') {
            $deviceConditions = $this->BuildEditorConditions($conditionSteps);
            if ($deviceConditions === null || count($deviceConditions) + count($conditions) > 16) {
                $reason = 'editor_conditions';
                $this->DebugEvent('FEHLER', 'Bedingungsliste ungültig; einschließlich Wochentagen und Zeitfenster sind höchstens 16 Bedingungen zulässig.');
                return null;
            }
            $conditions = array_merge($conditions, $deviceConditions);
        } elseif ($v['EditorConditionType'] !== 'none') {
            $device = $v['EditorCondition'];
            if ($v['EditorConditionType'] === 'state') {
                $choice = $this->FindEditorChoice($snapshot, $device, $v['EditorConditionChoice'], 'Source');
                if ($choice === null || !in_array($v['EditorCompare'], ['eq', 'ne'], true)) {
                    $reason = 'editor_condition';
                    return null;
                }
                $property = $choice['property'];
                $value = $choice['value'];
            } else {
                $property = $v['EditorNumericProperty'];
                $value = $v['EditorNumber'];
                if (!array_key_exists($property, $snapshot['devices'][$device]['numeric'] ?? []) || !(is_int($value) || is_float($value)) || !is_finite((float) $value)) {
                    $reason = 'editor_condition';
                    return null;
                }
            }
            $conditions[] = ['type' => 'device_value', 'device' => $device, 'property' => $property, 'operator' => $v['EditorCompare'], 'value' => $value];
        }
        if (count($conditions) > 16) {
            $reason = 'editor_conditions';
            return null;
        }
        return ['restart_policy' => $v['EditorRestartPolicy'], 'retrigger' => $v['EditorRetrigger'], 'triggers' => $triggers ?? [$trigger], 'conditions' => $conditions, 'actions' => $actions];
    }

    private function ScheduledEditorFields(array $rule): ?array
    {
        if ((count($rule['triggers'] ?? []) !== 1 && $this->EditorTriggerRows($rule) === null && $this->ClockEditorRows($rule['triggers']) === null) || $this->EditorActionRows($rule) === null || count($rule['conditions'] ?? []) > 16 || $this->EditorConditionRows($rule) === null) {
            return null;
        }
        $t = $rule['triggers'][0];
        $a = SequencePlan::Expand($this->ReadEditorDraft()['configuration'], $rule['actions'])[0]['action'];
        $v = $this->ScheduleDefaults();
        $v['EditorRetrigger'] = $rule['retrigger'] ?? 'ignore';
        $v['EditorRestartPolicy'] = $rule['restart_policy'] ?? 'abort';
        if (!in_array($v['EditorRetrigger'], ['ignore', 'restart', 'extend'], true)) {
            return null;
        }
        $v['EditorTriggerType'] = $t['type'];
        $v['EditorHold'] = $t['for_seconds'] ?? 0;
        $v['EditorDelay'] = $a['delay_seconds'] ?? 0;
        $snapshot = $this->EditorSnapshot();
        $find = static function (array $choices, string $property, mixed $value): string
        {
            foreach ($choices as $key => $choice) {
                if ($choice['property'] === $property && $choice['value'] === $value) {
                    return $key;
                }
            }
            return '';
        };
        if ($t['type'] === 'sun') {
            $solar = $this->SolarEditorFields($t);
            if ($solar === null) {
                return null;
            }
            $v = array_replace($v, $solar);
            $source = $sourceChoice = '';
        } elseif ($t['type'] === 'time') {
            $rows = $this->ClockEditorRows($rule['triggers']);
            if ($rows === null) {
                return null;
            }
            $v['EditorTimes'] = $rows;
            $v['EditorAt'] = $t['at'];
            $v['EditorTimezone'] = $t['timezone'];
            $source = $sourceChoice = '';
        } elseif ($t['type'] === 'device' && $this->EditorTriggerRows($rule) !== null) {
            $source = $t['device'];
            $sourceChoice = $this->EditorValueKey($snapshot, $source, $t['property'], $t['value'], 'Source') ?? '';
            if ($sourceChoice === '') {
                return null;
            }
        } else {
            return null;
        }
        $values = $this->EditorActionValues($a);
        if (!in_array($a['type'], ['set', 'toggle', 'scene'], true) || !in_array($a['target']['type'], ['device', 'group'], true) || $values === []) {
            return null;
        }
        $property = array_key_first($values);
        $targetChoice = $this->EditorValueKey($snapshot, $this->EditorTargetKey($a['target']), $property, $values[$property], 'Target') ?? '';
        if ($targetChoice === '') {
            return null;
        }
        foreach ($rule['conditions'] as $c) {
            if ($c['type'] === 'weekday' && !$v['EditorWeekdays']) {
                if (count($c) !== 3 || !is_array($c['weekdays'] ?? null) || !array_is_list($c['weekdays']) || $c['weekdays'] === []
                    || count($c['weekdays']) > 7 || count(array_unique($c['weekdays'], SORT_REGULAR)) !== count($c['weekdays'])
                    || array_filter($c['weekdays'], static fn (mixed $d): bool => !is_int($d) || $d < 1 || $d > 7) !== []
                    || !in_array($c['timezone'] ?? '', DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
                    return null;
                }
                $v['EditorWeekdays'] = true;
                $v['EditorWeekdayTimezone'] = $c['timezone'];
                foreach (self::CALENDAR_DAYS as $i => $day) {
                    $v['EditorDay' . $day] = in_array($i + 1, $c['weekdays'], true);
                }
            } elseif ($c['type'] === 'time_window' && !$v['EditorWindow']) {
                $v['EditorWindow'] = true;
                $v['EditorAfter'] = $c['after'];
                $v['EditorBefore'] = $c['before'];
                $v['EditorWindowTimezone'] = $c['timezone'];
                foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $i => $day) {
                    $v['Editor' . $day] = in_array($i + 1, $c['weekdays'] ?? [1, 2, 3, 4, 5, 6, 7], true);
                }
            } elseif ($c['type'] === 'device_value' && $v['EditorConditionType'] === 'none') {
                $v['EditorCondition'] = $c['device'];
                $v['EditorCompare'] = $c['operator'];
                $choice = $find($snapshot['devices'][$c['device']]['triggers'] ?? [], $c['property'], $c['value']);
                if ($choice !== '' && in_array($c['operator'], ['eq', 'ne'], true)) {
                    $v['EditorConditionType'] = 'state';
                    $v['EditorConditionChoice'] = $choice;
                } elseif (array_key_exists($c['property'], $snapshot['devices'][$c['device']]['numeric'] ?? []) && (is_int($c['value']) || is_float($c['value']))) {
                    $v['EditorConditionType'] = 'numeric';
                    $v['EditorNumericProperty'] = $c['property'];
                    $v['EditorNumber'] = $c['value'];
                } else {
                    return null;
                }
            } elseif ($c['type'] !== 'device_value') {
                return null;
            }
        }
        return $v + ['EditorSource' => $source, 'EditorSourceChoice' => $sourceChoice, 'EditorTarget' => $this->EditorTargetKey($a['target']), 'EditorTargetChoice' => $targetChoice];
    }

    private function ScheduleVisibility(array $v): array
    {
        $device = $v['EditorTriggerType'] === 'device';
        $solar = !$device && ($v['EditorTimeMode'] ?? 'clock') !== 'clock';
        $window = $v['EditorWindow'];
        $condition = $v['EditorConditionType'];
        $fields = ['EditorDeviceTrigger' => $device, 'EditorTimeTrigger' => !$device, 'EditorTriggers' => $device, 'EditorSource' => false, 'EditorSourceChoice' => false, 'EditorHold' => false, 'EditorTimeMode' => !$device, 'EditorLatitude' => $solar, 'EditorLongitude' => $solar, 'EditorSolarOffset' => $solar, 'EditorAt' => false, 'EditorTimes' => !$device && !$solar, 'EditorTimezone' => !$device,
            'EditorAfter'                => $window, 'EditorBefore' => $window, 'EditorWindowTimezone' => $window,
            'EditorCondition'            => $condition !== 'none', 'EditorConditionChoice' => $condition === 'state', 'EditorConditionProperty' => $condition === 'state',
            'EditorNumericProperty'      => $condition === 'numeric', 'EditorNumber' => $condition === 'numeric', 'EditorCompare' => $condition !== 'none',
            'WindowHelp'                 => $window, 'ConditionHelp' => $condition !== 'none'];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day) {
            $fields['Editor' . $day] = $window;
        }
        foreach (['EditorConditionType', 'EditorCondition', 'EditorConditionProperty', 'EditorConditionChoice', 'EditorNumericProperty', 'EditorNumber', 'EditorCompare'] as $legacyField) {
            $fields[$legacyField] = false;
        }
        return $fields + $this->CalendarVisibility($v);
    }

    private function EditorNumericOptions(array $snapshot, string $device): array
    {
        $options = [['caption' => $this->Translate('Choose a measured value'), 'value' => '']];
        foreach ($snapshot['devices'][$device]['numeric'] ?? [] as $property => $unit) {
            $label = $this->EditorPropertyLabel($snapshot, $device, $property, 'Source');
            $options[] = ['caption' => $label . ($unit === '' ? '' : ' (' . $unit . ')'), 'value' => $property];
        }
        return $options;
    }

    private function EditorComparisonOptions(string $type): array
    {
        $options = [];
        foreach (['eq' => 'Equals', 'ne' => 'Does not equal', 'gt' => 'Greater than', 'gte' => 'At least', 'lt' => 'Less than', 'lte' => 'At most'] as $value => $caption) {
            if ($type !== 'state' || in_array($value, ['eq', 'ne'], true)) {
                $options[] = ['caption' => $this->Translate($caption), 'value' => $value];
            }
        }
        return $options;
    }

    private function ApplyScheduleFields(array $fields): void
    {
        $this->WriteEditorAttribute('EditorCondition', $fields['EditorCondition']);
        $this->WriteEditorAttribute('EditorModes', json_encode(array_intersect_key($fields, array_flip(['EditorTriggerType', 'EditorWindow', 'EditorConditionType', 'EditorTimeMode', 'EditorWeekdays'])), JSON_THROW_ON_ERROR));
        $snapshot = $this->EditorSnapshot();
        $this->UpdateEditorField('EditorCompare', 'options', json_encode($this->EditorComparisonOptions($fields['EditorConditionType']), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $meta = $this->ConditionMeta($snapshot, $fields);
        $this->WriteEditorAttribute('EditorConditionProperty', $meta['property'] ?? '');
        $this->UpdateEditorField('EditorConditionProperty', 'options', json_encode($this->ConditionPropertyOptions($snapshot, $fields['EditorCondition']), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $this->UpdateEditorField('EditorConditionProperty', 'value', $meta['property'] ?? '');
        $this->UpdateEditorField('EditorConditionProperty', 'enabled', $fields['simple']);
        $this->UpdateEditorField('EditorConditionChoice', 'options', json_encode($this->EditorItemValues($snapshot, $fields['EditorCondition'], $meta, 'Source'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $this->UpdateEditorField('EditorNumericProperty', 'options', json_encode($this->EditorNumericOptions($snapshot, $fields['EditorCondition']), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        foreach ($this->ScheduleVisibility($fields) as $name => $visible) {
            $this->UpdateEditorField($name, 'visible', $visible);
        }
        foreach (array_keys($this->ScheduleDefaults()) as $name) {
            $this->UpdateEditorField($name, 'enabled', $fields['simple']);
        }
    }

    private function FillScheduleForm(array &$form, ?array $draft, array $selection): void
    {
        $modes = $this->ReadEditorAttribute('EditorModes');
        $v = $selection['selected'] ? $selection : array_replace($this->ScheduleDefaults(), $modes === '' ? [] : json_decode($modes, true, 8, JSON_THROW_ON_ERROR));
        $v['EditorCondition'] = $selection['selected'] ? $selection['EditorCondition'] : $this->ReadEditorAttribute('EditorCondition');
        $visibility = $this->ScheduleVisibility($v);
        $snapshot = $this->EditorSnapshot();
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            $name = $field['name'] ?? '';
            if (array_key_exists($name, $this->ScheduleDefaults())) {
                $field[$name === 'EditorTimes' ? 'values' : 'value'] = $v[$name];
                $field['enabled'] = $selection['simple'];
            }
            if (array_key_exists($name, $visibility)) {
                $field['visible'] = $visibility[$name];
            }
            if ($name === 'EditorCondition') {
                $field['options'] = $this->EditorDevices($snapshot, 'Condition');
            } elseif ($name === 'EditorConditionProperty') {
                $field['options'] = $this->ConditionPropertyOptions($snapshot, $v['EditorCondition']);
                $field['value'] = $this->ConditionMeta($snapshot, $v)['property'] ?? '';
                $field['enabled'] = $selection['simple'];
            } elseif ($name === 'EditorConditionChoice') {
                $field['options'] = $this->EditorItemValues($snapshot, $v['EditorCondition'], $this->ConditionMeta($snapshot, $v), 'Source');
            } elseif ($name === 'EditorNumericProperty') {
                $field['options'] = $this->EditorNumericOptions($snapshot, $v['EditorCondition']);
            } elseif ($name === 'EditorCompare') {
                $field['options'] = $this->EditorComparisonOptions($v['EditorConditionType']);
            } elseif (in_array($name, ['EditorTimezone', 'EditorWindowTimezone', 'EditorWeekdayTimezone'], true)) {
                $field['options'] = array_map(static fn (string $zone): array => ['caption' => $zone, 'value' => $zone], DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC));
            }
        }
        unset($field);
    }
}
