<?php

declare(strict_types=1);

namespace Z2MAutomations;

use DateTimeZone;
use JsonException;

trait AutomationEditorCalendar
{
    private const CALENDAR_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

    public function ConfigureEditorWeekdays(bool $Enabled, string $Area = ''): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Enabled, $Area): void
        {
            if (!$this->CheckEditorArea($Area)) {
                return;
            }
            $text = $this->ReadAttributeString('EditorModes');
            $modes = array_replace($this->ScheduleDefaults(), $text === '' ? [] : json_decode($text, true, 8, JSON_THROW_ON_ERROR));
            $modes['EditorWeekdays'] = $Enabled;
            $this->WriteAttributeString('EditorModes', json_encode(array_intersect_key($modes, array_flip(['EditorTriggerType', 'EditorWindow', 'EditorConditionType', 'EditorTimeMode', 'EditorWeekdays'])), JSON_THROW_ON_ERROR));
            foreach ($this->CalendarVisibility($modes) as $name => $visible) {
                $this->UpdateEditorField($name, 'visible', $visible);
            }
            $this->DebugEvent('AKTION', 'Wochentagsauswahl unabhängig vom Zeitfenster angepasst; Änderungen betreffen zunächst nur das Formular.', ['Wochentagsfilter' => $Enabled]);
        });
    }

    public function InspectEditorTimes(string $Times, string $Change, string $Area = ''): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Times, $Change, $Area): bool
        {
            if (!$this->CheckEditorArea($Area)) {
                return false;
            }
            if (!in_array($Change, ['add', 'edit', 'delete'], true)) {
                $this->DebugEvent('FEHLER', 'Uhrzeitenliste nicht geprüft: unbekannte Bearbeitungsaktion. Entwurf bleibt unverändert.');
                return false;
            }
            $rows = $this->ClockEditorRowsFromJson($Times);
            if ($rows === null) {
                $this->DebugEvent('FEHLER', 'Uhrzeitenliste ungültig: eine bis acht unterschiedliche Uhrzeiten im Format HH:MM oder HH:MM:SS eintragen. Entwurf bleibt unverändert.');
                return false;
            }
            $this->DebugEvent('AKTION', $Change === 'delete'
                ? 'Uhrzeit zum Löschen ausgewählt; Anzahl beschreibt den Vorbestand. Verbleibende Liste wird beim Übernehmen geprüft.'
                : 'Uhrzeitenliste im Formular bearbeitet; jede Uhrzeit kann dieselbe Automation starten.', ['Uhrzeiten' => count($rows)]);
            return true;
        });
    }

    private function CalendarDefaults(): array
    {
        $values = ['EditorTimes' => [['At' => '18:00:00']], 'EditorWeekdays' => false, 'EditorWeekdayTimezone' => 'Europe/Berlin'];
        foreach (self::CALENDAR_DAYS as $day) {
            $values['EditorDay' . $day] = true;
        }
        return $values;
    }

    private function ClockEditorRowsFromJson(string $text): ?array
    {
        if (strlen($text) > 2048) {
            return null;
        }
        try {
            return $this->NormalizeClockEditorRows(json_decode($text, true, 4, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            return null;
        }
    }

    private function NormalizeClockEditorRows(mixed $rows): ?array
    {
        if (!is_array($rows) || !array_is_list($rows) || count($rows) < 1 || count($rows) > 8) {
            return null;
        }
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row) || array_keys($row) !== ['At'] || ($at = $this->EditorClockTime($row['At'])) === null
                || in_array(['At' => $at], $result, true)) {
                return null;
            }
            $result[] = ['At' => $at];
        }
        return $result;
    }

    private function ClockEditorRows(array $triggers): ?array
    {
        $rows = [];
        $zone = $triggers[0]['timezone'] ?? '';
        foreach ($triggers as $trigger) {
            if (count($trigger) !== 3 || ($trigger['type'] ?? '') !== 'time' || ($trigger['timezone'] ?? '') !== $zone) {
                return null;
            }
            $rows[] = ['At' => $trigger['at'] ?? null];
        }
        return in_array($zone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true) ? $this->NormalizeClockEditorRows($rows) : null;
    }

    private function BuildWeekdayCondition(array $values, string &$reason): ?array
    {
        $days = [];
        foreach (self::CALENDAR_DAYS as $i => $day) {
            if ($values['EditorDay' . $day]) {
                $days[] = $i + 1;
            }
        }
        if ($days === []) {
            $reason = 'editor_calendar_days';
            return null;
        }
        if (!in_array($values['EditorWeekdayTimezone'], DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            $reason = 'editor_timezone';
            return null;
        }
        return ['type' => 'weekday', 'weekdays' => $days, 'timezone' => $values['EditorWeekdayTimezone']];
    }

    private function CalendarVisibility(array $values): array
    {
        $fields = ['EditorWeekdayTimezone' => $values['EditorWeekdays'] ?? false, 'EditorCalendarHelp' => $values['EditorWeekdays'] ?? false];
        foreach (self::CALENDAR_DAYS as $day) {
            $fields['EditorDay' . $day] = $values['EditorWeekdays'] ?? false;
        }
        return $fields;
    }
}
