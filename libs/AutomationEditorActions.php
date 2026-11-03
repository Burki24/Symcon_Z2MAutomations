<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;

trait AutomationEditorActions
{
    public function InspectEditorActions(string $Steps, string $Change, string $Area = ''): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Steps, $Change, $Area): bool
        {
            if (!$this->CheckEditorArea($Area)) {
                return false;
            }
            $reason = 'editor_actions';
            $actions = $this->BuildEditorActions($Steps, $reason);
            if (!in_array($Change, ['add', 'edit', 'delete', 'order'], true) || $actions === null) {
                $this->DebugEvent('FEHLER', 'Aktionsliste noch nicht gültig; vor der Übernahme korrigieren.', ['Grund' => $reason]);
                return false;
            }
            if ($Change === 'delete') {
                // Native onDelete supplies the list before the selected row is removed.
                $this->DebugEvent('AKTION', 'Löschen eines Aktionsschritts gemeldet; übergebene Anzahl ist der Vorbestand. Die verbleibende Liste wird beim Übernehmen geprüft.', ['Bereich' => $this->EditorTypeLabel($this->ActiveEditorArea()), 'AktionenVorLöschen' => count($actions)]);
                return true;
            }
            $this->RememberEditorListChoices('Target', json_decode($Steps, true, 8, JSON_THROW_ON_ERROR), 'current');
            $this->DebugEvent('AKTION', 'Aktionsliste im Formular bearbeitet; noch nicht in den Regelentwurf übernommen.', ['Bereich' => $this->EditorTypeLabel($this->ActiveEditorArea()), 'Änderung' => $Change, 'Aktionen' => count($actions)]);
            $this->DebugEditorActionSteps($actions);
            return true;
        });
    }

    private function BuildEditorActions(string $steps, string &$reason, bool $allowSequences = true): ?array
    {
        if (strlen($steps) > 65536) {
            return null;
        }
        try {
            $rows = json_decode($steps, true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (!is_array($rows) || !array_is_list($rows) || count($rows) < 1 || count($rows) > 16) {
            return null;
        }
        $snapshot = $this->EditorSnapshot();
        $actions = [];
        foreach ($rows as $row) {
            if (!is_array($row) || count($row) !== 2 || !is_string($row['Choice'] ?? null) || !is_int($row['Delay'] ?? null)) {
                return null;
            }
            if ($row['Delay'] < 0 || $row['Delay'] > 86400) {
                $reason = 'editor_duration';
                return null;
            }
            if (str_starts_with($row['Choice'], 'sequence:')) {
                $id = substr($row['Choice'], 9);
                if (!$allowSequences || !$this->ReusableSequenceAllowed($id)) {
                    $reason = $allowSequences ? 'sequence_missing' : 'sequence_nested';
                    return null;
                }
                $actions[] = ['type' => 'sequence', 'sequence_id' => $id, 'delay_seconds' => $row['Delay']];
                continue;
            }
            $parts = explode(':', $row['Choice']);
            if (count($parts) !== 2) {
                return null;
            }
            [$device, $key] = $parts;
            if (str_starts_with($key, 'command.')) {
                $values = $this->DecodeCommand($snapshot, $device, $key);
                if ($values === null) {
                    return null;
                }
                $actions[] = ['type' => 'set', 'target' => $this->EditorTargetIdentity($device), 'values' => $values, 'delay_seconds' => $row['Delay']];
                continue;
            }
            $choice = $this->FindEditorChoice($snapshot, $device, $key, 'Target');
            if ($choice === null) {
                return null;
            }
            $actions[] = $this->BuildEditorAction($device, $choice, $row['Delay']);
        }
        return $actions;
    }

    private function BuildEditorAction(string $key, array $choice, int $delay = 0): array
    {
        $target = $this->EditorTargetIdentity($key);
        $meta = $this->EditorProperties($this->EditorSnapshot(), $key, 'Target')[$choice['property']] ?? [];
        if (isset($meta['sceneEndpoint'])) {
            return ['type' => 'scene', 'target' => $target, 'scene_id' => $choice['value'], 'delay_seconds' => $delay]
                + ($meta['sceneEndpoint'] > 0 ? ['endpoint' => $meta['sceneEndpoint']] : []);
        }
        return $choice['value'] === 'TOGGLE' && ($meta['type'] ?? '') === 'binary'
            && preg_match('/\Astate(?:_[A-Za-z0-9_]+)?\z/D', $choice['property']) === 1
            ? ['type' => 'toggle', 'target' => $target, 'property' => $choice['property'], 'delay_seconds' => $delay]
            : ['type' => 'set', 'target' => $target, 'values' => [$choice['property'] => $choice['value']], 'delay_seconds' => $delay];
    }

    private function EditorActionValues(array $action): array
    {
        if (($action['type'] ?? '') === 'scene') {
            return ['z2ma_scene' . (isset($action['endpoint']) ? '_' . $action['endpoint'] : '') => $action['scene_id']];
        }
        return ($action['type'] ?? '') === 'toggle' ? [$action['property'] => 'TOGGLE'] : ($action['values'] ?? []);
    }

    private function EditorActionRows(array $rule, bool $allowSequences = true): ?array
    {
        $actions = $rule['actions'] ?? [];
        if (count($actions) < 1 || count($actions) > 16) {
            return null;
        }
        $snapshot = $this->EditorSnapshot();
        $rows = [];
        foreach ($actions as $action) {
            if (($action['type'] ?? '') === 'sequence') {
                if (!$allowSequences || !is_int($action['delay_seconds'] ?? 0) || ($action['delay_seconds'] ?? 0) < 0 || ($action['delay_seconds'] ?? 0) > 86400
                    || !$this->ReusableSequenceAllowed($action['sequence_id'] ?? '')) {
                    return null;
                }
                $rows[] = ['Choice' => 'sequence:' . $action['sequence_id'], 'Delay' => $action['delay_seconds'] ?? 0];
                continue;
            }
            $values = $this->EditorActionValues($action);
            if (!in_array($action['type'] ?? '', ['set', 'toggle', 'scene'], true) || !in_array($action['target']['type'] ?? '', ['device', 'group'], true)
                || $values === [] || !is_int($action['delay_seconds'] ?? 0)
                || ($action['delay_seconds'] ?? 0) < 0 || ($action['delay_seconds'] ?? 0) > 86400) {
                return null;
            }
            $device = $this->EditorTargetKey($action['target']);
            if (count($values) > 1) {
                $key = $action['type'] === 'set' ? $this->CommandChoice($snapshot, $device, $values) : null;
                if ($key === null) {
                    return null;
                }
                $rows[] = ['Choice' => $device . ':' . $key, 'Delay' => $action['delay_seconds'] ?? 0];
                continue;
            }
            $property = array_key_first($values);
            if ($action['type'] === 'set' && ($values[$property] ?? null) === 'TOGGLE'
                && $this->BuildEditorAction($device, ['property' => $property, 'value' => 'TOGGLE'])['type'] === 'toggle') {
                // Preserve an explicitly authored native TOGGLE as an advanced rule;
                // editing must not change it into aggregate-state inversion.
                return null;
            }
            $found = null;
            foreach ($snapshot['devices'][$device]['actions'] ?? [] as $key => $choice) {
                if ($choice['property'] === $property && $choice['value'] === $values[$property]
                    && $this->EditorValueAllowed($snapshot, $device, $property, $choice['value'], 'Target')) {
                    $found = $device . ':' . $key;
                    break;
                }
            }
            if ($found === null) {
                $key = $this->EditorValueKey($snapshot, $device, $property, $values[$property], 'Target');
                $found = $key === null ? null : $device . ':' . $key;
            }
            if ($found === null) {
                return null;
            }
            $rows[] = ['Choice' => $found, 'Delay' => $action['delay_seconds'] ?? 0];
        }
        return $rows;
    }

    private function EditorActionColumns(array $snapshot, array $rows = [], bool $allowSequences = true): array
    {
        $options = [['caption' => $this->Translate('Choose device and action'), 'value' => '']];
        $options = $this->ExtraEditorChoiceOptions($snapshot, 'Target', $options, $rows);
        if ($allowSequences) {
            foreach ($this->ReusableOptions(true) as $option) {
                if ($option['value'] !== '') {
                    $options[] = ['caption' => $this->Translate('Action sequence') . ': ' . $option['caption'], 'value' => 'sequence:' . $option['value']];
                }
            }
        }
        return [['name' => 'Choice', 'caption' => $this->Translate('Device and action'), 'width' => 'auto', 'add' => '',
            'edit'      => ['type' => 'Select', 'options' => $options]],
            ['name'    => 'Delay', 'caption' => $this->Translate('Delay (s)'), 'width' => '140px', 'add' => 0,
                'edit' => ['type' => 'NumberSpinner', 'minimum' => 0, 'maximum' => 86400]]];
    }

    private function FillActionSequenceForm(array &$form, array $selection, array $snapshot): void
    {
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            if (($field['name'] ?? '') === 'EditorActions') {
                $field['columns'] = $this->EditorActionColumns($snapshot, $selection['EditorActionRows']);
                $field['values'] = $selection['EditorActionRows'];
                $field['enabled'] = $selection['simple'];
            }
        }
        unset($field);
    }

    private function DebugEditorActionSteps(array $actions): void
    {
        foreach ($actions as $index => $action) {
            $this->DebugEvent(
                'AKTION',
                $action['type'] === 'sequence' ? 'Aufruf einer wiederverwendbaren Aktionsfolge vorbereitet; Bedingungen und Stopp gelten für den gesamten Lauf.' : ($action['type'] === 'scene' ? 'Aufruf einer vorhandenen Szene mit Wartezeit vorbereitet.' : 'Aktionsschritt mit Wartezeit vor der Ausführung vorbereitet.'),
                ['Schritt' => $index + 1, 'VerzögerungSekunden' => $action['delay_seconds'] ?? 0, 'ZweitesSystem' => isset($action['target']['system']), 'Eigenschaften' => count($action['values'] ?? [])]
            );
        }
    }
}
