<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait AutomationEditorSolar
{
    public function ConfigureEditorSolar(string $Mode, string $Area = ''): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Mode, $Area): void
        {
            if (!$this->CheckEditorArea($Area) || !in_array($Mode, ['clock', 'sunrise', 'sunset'], true)
                || ($Area !== '' && $Area !== 'time')) {
                $this->DebugEvent('FEHLER', 'Astro-Auswahl abgelehnt; einen unterstützten Zeit-Auslöser wählen.');
                return;
            }
            $text = $this->ReadEditorAttribute('EditorModes');
            $modes = $text === '' ? [] : json_decode($text, true, 8, JSON_THROW_ON_ERROR);
            $modes['EditorTimeMode'] = $Mode;
            $this->WriteEditorAttribute('EditorModes', json_encode($modes, JSON_THROW_ON_ERROR));
            $this->ConfigureEditorSchedule('time', $modes['EditorWindow'] ?? false, $modes['EditorConditionType'] ?? 'none', $Area);
            $this->DebugEvent('AKTION', 'Zeit-Auswahl im Editor angepasst; Standortdaten bleiben geschützt.', ['Auswahl' => $Mode]);
        });
    }

    private function SolarEditorCoordinate(string $text, float $limit): ?float
    {
        $text = trim($text);
        if (strlen($text) > 48 || preg_match('/\A[+-]?(?:[0-9]+(?:[.,][0-9]*)?|[.,][0-9]+)(?:[eE][+-]?[0-9]{1,3})?\z/D', $text) !== 1) {
            return null;
        }
        $value = (float) str_replace(',', '.', $text);
        return is_finite($value) && abs($value) <= $limit ? $value : null;
    }

    private function BuildSolarEditorTrigger(array $values): ?array
    {
        $latitude = $this->SolarEditorCoordinate($values['EditorLatitude'], 90);
        $longitude = $this->SolarEditorCoordinate($values['EditorLongitude'], 180);
        if (!in_array($values['EditorTimeMode'], ['sunrise', 'sunset'], true) || $latitude === null || $longitude === null
            || !is_int($values['EditorSolarOffset']) || abs($values['EditorSolarOffset']) > 720) {
            $this->DebugEvent('FEHLER', 'Astro-Auslöser abgelehnt: Breitengrad von -90 bis 90, Längengrad von -180 bis 180 und Zeitversatz von -720 bis 720 Minuten erforderlich.');
            return null;
        }
        $this->DebugEvent('AKTION', 'Astro-Auslöser mit Standort und Zeitversatz vorbereitet; keine Koordinaten im Debug.', ['Sonnenereignis' => $values['EditorTimeMode'], 'ZeitversatzMinuten' => $values['EditorSolarOffset']]);
        return ['type'       => 'sun', 'event' => $values['EditorTimeMode'], 'latitude' => $latitude, 'longitude' => $longitude,
            'offset_minutes' => $values['EditorSolarOffset'], 'timezone' => $values['EditorTimezone']];
    }

    private function SolarEditorFields(array $trigger): ?array
    {
        if (count($trigger) !== 6 || !in_array($trigger['event'] ?? null, ['sunrise', 'sunset'], true)
            || !(is_int($trigger['latitude'] ?? null) || is_float($trigger['latitude'] ?? null))
            || !is_finite((float) $trigger['latitude']) || abs($trigger['latitude']) > 90
            || !(is_int($trigger['longitude'] ?? null) || is_float($trigger['longitude'] ?? null))
            || !is_finite((float) $trigger['longitude']) || abs($trigger['longitude']) > 180
            || !is_int($trigger['offset_minutes'] ?? null) || abs($trigger['offset_minutes']) > 720
            || !is_string($trigger['timezone'] ?? null) || !in_array($trigger['timezone'], \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)) {
            return null;
        }
        return ['EditorTriggerType' => 'time', 'EditorTimeMode' => $trigger['event'], 'EditorLatitude' => json_encode($trigger['latitude'], JSON_THROW_ON_ERROR),
            'EditorLongitude'       => json_encode($trigger['longitude'], JSON_THROW_ON_ERROR), 'EditorSolarOffset' => $trigger['offset_minutes'], 'EditorTimezone' => $trigger['timezone']];
    }

    private function SolarEditorLabel(array $trigger): string
    {
        return sprintf($this->Translate('Daily: %s, offset %+d min (%s)'), $this->Translate(($trigger['event'] ?? '') === 'sunrise' ? 'Sunrise' : 'Sunset'), $trigger['offset_minutes'] ?? 0, $trigger['timezone'] ?? '');
    }
}
