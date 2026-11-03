<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait EditorItemForms
{
    public function GetEditorItemForm(string $List, string $Choice, int $Duration, string $Compare = 'eq', bool $Changed = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($List, $Choice, $Duration, $Compare, $Changed): string
        {
            $context = $this->EditorItemContext($List);
            if ($context === null || !$this->CheckEditorArea($context['area'])) {
                return '[]';
            }
            $form = $this->LoadConfigurationForm();
            $template = [];
            foreach ($this->EditorFormFields($form['actions']) as &$field) {
                if (($field['name'] ?? '') === $List) {
                    $template = $field['form'];
                    break;
                }
            }
            unset($field);
            $snapshot = $this->EditorSnapshot();
            $parts = explode(':', $Choice);
            $device = count($parts) === 2 ? $parts[0] : '';
            $choice = count($parts) === 2 ? $this->FindEditorChoice($snapshot, $device, $parts[1], $context['side']) : null;
            $command = $context['side'] === 'Target' && count($parts) === 2 ? $this->DecodeCommand($snapshot, $device, $parts[1]) : null;
            $combined = $command !== null;
            if ($combined) {
                $first = array_key_first($command);
                $choice = ['property' => $first, 'value' => $command[$first]];
            }
            $property = $choice === null ? '' : $this->EditorPropertyToken($snapshot, $device, $choice['property'], $context['side']);
            $meta = $this->EditorItemProperty($snapshot, $device, $property, $context['side']);
            $numeric = ($meta['type'] ?? '') === 'numeric';
            $color = ($meta['type'] ?? '') === 'color';
            $sequenceID = str_starts_with($Choice, 'sequence:') ? substr($Choice, 9) : '';
            $sequence = $context['side'] === 'Target' && $List !== 'SequenceEditorActions' && $this->ReusableSequenceAllowed($sequenceID);
            foreach ($template as &$field) {
                $name = $field['name'] ?? '';
                if ($name === 'RowActionKind') {
                    $field['value'] = $sequence ? 'sequence' : ($combined ? 'combined' : 'device');
                    if ($List === 'SequenceEditorActions') {
                        $field['options'] = array_values(array_filter($field['options'], static fn (array $o): bool => $o['value'] !== 'sequence'));
                    }
                } elseif ($name === 'RowSequence') {
                    $field['options'] = $this->ReusableOptions(true);
                    $field['value'] = $sequence ? $sequenceID : '';
                    $field['visible'] = $sequence;
                } elseif ($name === 'RowDevice') {
                    $field['options'] = $this->EditorDevices($snapshot, $context['side']);
                    $field['value'] = $device;
                } elseif ($name === 'RowProperty') {
                    $field['options'] = $this->EditorItemProperties($snapshot, $device, $context['side'], $combined);
                    $field['value'] = $property;
                } elseif ($name === 'RowValue') {
                    $field['caption'] = $this->Translate(isset($meta['sceneEndpoint']) ? 'Scene' : 'Value');
                    $field['options'] = $this->EditorItemValues($snapshot, $device, $meta, $context['side'], $combined);
                    $field['value'] = !$numeric && !$color && $choice !== null ? ($this->EditorValueKey($snapshot, $device, $choice['property'], $choice['value'], $context['side']) ?? '') : '';
                    $field['visible'] = !$numeric && !$color;
                } elseif ($name === 'RowType') {
                    $field['value'] = $meta['type'] ?? '';
                } elseif ($name === 'RowColor') {
                    $field['value'] = $color && isset($choice['value']['hex']) ? hexdec(substr($choice['value']['hex'], 1)) : 0;
                    $field['visible'] = $color;
                } elseif ($name === 'RowCommandSummary') {
                    $field['values'] = $this->CommandSummary($snapshot, $device, $command ?? []);
                    $field['visible'] = $combined;
                } elseif (in_array($name, ['RowCommandAdd', 'RowCommandRemove', 'RowCommandHelp', 'RowCommandHint'], true)) {
                    $field['visible'] = $combined;
                } elseif ($name === 'Changed') {
                    $field['value'] = $Changed;
                } elseif ($name === 'Compare') {
                    $field['options'] = $this->EditorComparisonOptions($numeric ? 'numeric' : 'state');
                    $field['value'] = $Compare;
                } elseif ($name === 'RowNumber') {
                    $field['value'] = $numeric ? $choice['value'] : 0;
                    $field['visible'] = $numeric;
                    if ($context['side'] === 'Target') {
                        $field += array_intersect_key($meta ?? [], array_flip(['minimum', 'maximum']));
                    }
                } elseif ($name === 'Choice') {
                    $field['value'] = $sequence || $choice !== null ? $Choice : '';
                } elseif ($name === $context['duration']) {
                    $field['value'] = $Duration;
                }
                if ($sequence && in_array($name, ['RowDevice', 'RowProperty', 'RowValue', 'RowNumber', 'RowColor'], true)) {
                    $field['visible'] = false;
                }
            }
            unset($field);
            $this->DebugEvent('AKTION', 'Zeilenbearbeitung geöffnet; ausgewählte Angaben vorbelegt.', ['Auswahlart' => $context['side'], 'Vorbelegt' => $sequence || $choice !== null, 'WiederverwendbareFolge' => $sequence, 'GemeinsamerBefehl' => $combined, 'Farbwahl' => $color]);
            return json_encode($template, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        });
    }

    public function ConfigureEditorItem(string $Change, string $List, string $Device, string $Property, string $Value, string $Compare = 'eq', string $Kind = 'device', string $Choice = ''): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Change, $List, $Device, $Property, $Value, $Compare, $Kind, $Choice): void
        {
            $context = $this->EditorItemContext($List);
            if ($context === null || !$this->CheckEditorArea($context['area']) || !in_array($Change, ['device', 'property', 'value', 'compare'], true) || !in_array($Kind, ['device', 'combined'], true)) {
                $this->DebugEvent('FEHLER', 'Unbekannte Auswahl im Zeilendialog abgewiesen.');
                return;
            }
            $snapshot = $this->EditorSnapshot();
            $combined = $Kind === 'combined' && $context['side'] === 'Target';
            $commandParts = explode(':', $Choice);
            $command = $Change !== 'device' && count($commandParts) === 2 && $commandParts[0] === $Device ? $this->DecodeCommand($snapshot, $Device, $commandParts[1]) : null;
            if ($combined && $Change === 'device') {
                $this->UpdateFormField('Choice', 'value', '');
                $this->UpdateCommandSummary($snapshot, $Device, []);
            }
            if ($Change === 'device') {
                $Property = '';
                $this->UpdateFormField('RowProperty', 'options', json_encode($this->EditorItemProperties($snapshot, $Device, $context['side'], $combined), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $this->UpdateFormField('RowProperty', 'value', '');
            }
            $meta = $this->EditorItemProperty($snapshot, $Device, $Property, $context['side']);
            $numeric = ($meta['type'] ?? '') === 'numeric';
            $color = ($meta['type'] ?? '') === 'color';
            $choice = null;
            if ($context['side'] === 'Source') {
                $this->UpdateFormField('Compare', 'options', json_encode($this->EditorComparisonOptions($numeric ? 'numeric' : 'state'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                if (in_array($Change, ['device', 'property'], true)) {
                    $Compare = 'eq';
                    $this->UpdateFormField('Compare', 'value', 'eq');
                }
            }
            if (in_array($Change, ['device', 'property'], true)) {
                $this->UpdateFormField('RowValue', 'options', json_encode($this->EditorItemValues($snapshot, $Device, $meta, $context['side'], $combined), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $this->UpdateFormField('RowValue', 'value', '');
                $number = max(($context['side'] === 'Target' ? ($meta['minimum'] ?? -PHP_FLOAT_MAX) : -PHP_FLOAT_MAX), min(($context['side'] === 'Target' ? ($meta['maximum'] ?? PHP_FLOAT_MAX) : PHP_FLOAT_MAX), 0));
                $this->UpdateFormField('RowNumber', 'value', $number);
                // Reset bounds when switching between different numeric properties.
                $this->UpdateFormField('RowNumber', 'minimum', ($context['side'] === 'Target' ? ($meta['minimum'] ?? -PHP_FLOAT_MAX) : -PHP_FLOAT_MAX));
                $this->UpdateFormField('RowNumber', 'maximum', ($context['side'] === 'Target' ? ($meta['maximum'] ?? PHP_FLOAT_MAX) : PHP_FLOAT_MAX));
                if ($color) {
                    $this->UpdateFormField('RowColor', 'value', 0);
                    $key = $this->EditorValueKey($snapshot, $Device, $meta['property'], ['hex' => '#000000'], 'Target');
                    $choice = $key === null ? null : $Device . ':' . $key;
                } elseif ($numeric) {
                    $key = $this->EditorValueKey($snapshot, $Device, $meta['property'], $number, $context['side']);
                    $choice = $key === null ? null : $Device . ':' . $key;
                }
            } elseif ($meta !== null && strlen($Value) <= 4096) {
                $value = json_decode($Value, true, 8);
                if ($color) {
                    $key = $this->EditorValueKey($snapshot, $Device, $meta['property'], $this->EditorColorValue($value), 'Target');
                } elseif ($numeric) {
                    $key = $this->EditorValueKey($snapshot, $Device, $meta['property'], $value, $context['side']);
                } else {
                    $candidate = is_string($value) ? $this->FindEditorChoice($snapshot, $Device, $value, $context['side']) : null;
                    $key = ($candidate['property'] ?? '') === $meta['property'] ? $value : null;
                }
                $choice = $key === null ? null : $Device . ':' . $key;
            }
            if ($context['side'] === 'Source' && !in_array($Compare, array_column($this->EditorComparisonOptions($numeric ? 'numeric' : 'state'), 'value'), true)) {
                $choice = null;
            }
            $this->UpdateFormField('RowValue', 'visible', !$numeric && !$color);
            if ($context['side'] === 'Target') {
                $this->UpdateFormField('RowColor', 'visible', $color);
                $this->UpdateFormField('RowType', 'value', $meta['type'] ?? '');
            }
            $this->UpdateFormField('RowValue', 'caption', $this->Translate(isset($meta['sceneEndpoint']) ? 'Scene' : 'Value'));
            $this->UpdateFormField('RowNumber', 'visible', $numeric);
            if ($combined) {
                $this->UpdateFormField('Choice', 'value', $command === null ? '' : $Choice);
                $this->UpdateCommandSummary($snapshot, $Device, $command ?? []);
                if ($Change === 'property' && $meta !== null && array_key_exists($meta['property'], $command ?? [])) {
                    $saved = $command[$meta['property']];
                    $field = $color ? 'RowColor' : ($numeric ? 'RowNumber' : 'RowValue');
                    $value = $color ? hexdec(substr($saved['hex'], 1)) : ($numeric ? $saved : $this->EditorValueKey($snapshot, $Device, $meta['property'], $saved, 'Target'));
                    $this->UpdateFormField($field, 'value', $value);
                }
            } else {
                $this->UpdateFormField('Choice', 'value', $choice ?? '');
            }
            if ($choice !== null && !$combined) {
                $bucket = $context['duration'] === '' ? 'Condition' : $context['side'];
                $this->RememberEditorListChoices($bucket, [['Choice' => $choice]], 'pending');
                $columns = str_ends_with($List, 'ConditionList') ? $this->EditorConditionColumns($snapshot) : ($context['side'] === 'Target' ? $this->EditorActionColumns($snapshot, [], $List !== 'SequenceEditorActions') : $this->EditorTriggerColumns($snapshot));
                $this->UpdateEditorField($List, 'columns', json_encode($columns, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            }
            $this->DebugEvent('AKTION', $combined ? 'Eigenschaftsauswahl für gemeinsamen Befehl vorbereitet; Hinzufügen oder Ersetzen übernimmt den Wert.' : 'Abhängige Eigenschafts- und Wertauswahl aktualisiert; alte Auswahl verworfen.', ['Änderung' => $Change, 'Auswahlart' => $context['side'], 'EigenschaftVerfügbar' => $meta !== null, 'WertGültig' => $choice !== null]);
        });
    }
    private function EditorItemContext(string $list): ?array
    {
        if (!in_array($list, ['EditorActions', 'EditorTriggers', 'DeviceEditorActions', 'DeviceEditorTriggers', 'TimeEditorActions', 'TimeEditorTriggers', 'EditorConditionList', 'DeviceEditorConditionList', 'TimeEditorConditionList', 'SequenceEditorActions'], true)) {
            return null;
        }
        return ['side' => str_ends_with($list, 'Actions') ? 'Target' : 'Source',
            'duration' => str_ends_with($list, 'ConditionList') ? '' : (str_ends_with($list, 'Triggers') ? 'Hold' : 'Delay'),
            'area'     => str_starts_with($list, 'Device') ? 'device' : (str_starts_with($list, 'Time') ? 'time' : '')];
    }

    private function EditorItemProperties(array $snapshot, string $device, string $side, bool $combined = false): array
    {
        $options = [['caption' => $this->Translate('Choose property or channel'), 'value' => '']];
        foreach ($this->EditorProperties($snapshot, $device, $side) as $property => $meta) {
            if ($combined && isset($meta['sceneEndpoint'])) {
                continue;
            }
            $options[] = ['caption' => $this->EditorPropertyLabel($snapshot, $device, $property, $side),
                'value'             => $this->EditorPropertyToken($snapshot, $device, $property, $side)];
        }
        return $options;
    }

    private function EditorItemProperty(array $snapshot, string $device, string $token, string $side): ?array
    {
        foreach ($this->EditorProperties($snapshot, $device, $side) as $property => $meta) {
            if ($this->EditorPropertyToken($snapshot, $device, $property, $side) === $token) {
                return $meta;
            }
        }
        return null;
    }

    private function EditorItemValues(array $snapshot, string $device, ?array $meta, string $side, bool $combined = false): array
    {
        $options = [['caption' => $this->Translate('Choose value'), 'value' => '']];
        foreach ($meta['values'] ?? [] as $value) {
            if ($combined && $meta['type'] === 'binary' && $value === 'TOGGLE') {
                continue;
            }
            $key = $this->EditorValueKey($snapshot, $device, $meta['property'], $value, $side);
            if ($key !== null) {
                $options[] = ['caption' => $this->EditorChoiceValueLabel($snapshot, $device, $meta['property'], $value), 'value' => $key];
            }
        }
        return $options;
    }

    private function EditorValueLabel(mixed $value): string
    {
        if (is_array($value) && array_keys($value) === ['hex'] && is_string($value['hex'])) {
            return strtoupper($value['hex']);
        }
        return $this->Translate(match ($value) {
            'ON' => 'On', 'OFF' => 'Off', 'TOGGLE' => 'Toggle', 'single' => 'Single press', 'double' => 'Double press', 'hold' => 'Hold', 'release' => 'Release',
            true => 'Yes', false => 'No', default => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        });
    }

    private function RememberEditorListChoices(string $bucket, array $rows, string $origin = 'selection'): array
    {
        $saved = $this->GetBuffer('EditorRowChoices');
        $saved = $saved === '' ? [] : json_decode($saved, true, 16, JSON_THROW_ON_ERROR);
        // This is a short-lived display cache, not the device catalog or rule storage.
        if (array_is_list($saved)) {
            $saved = [];
        }
        if ($rows !== [] || $origin === 'current') {
            $saved[$bucket][$origin] = array_slice(array_column($rows, 'Choice'), 0, $origin === 'pending' ? 1 : ($bucket === 'Source' ? 8 : 16));
        }
        $this->SetBuffer('EditorRowChoices', json_encode($saved, JSON_THROW_ON_ERROR));
        return array_values(array_unique(array_merge($saved[$bucket]['selection'] ?? [], $saved[$bucket]['current'] ?? [], $saved[$bucket]['pending'] ?? [])));
    }

    private function ExtraEditorChoiceOptions(array $snapshot, string $side, array $options, array $rows, string $bucket = ''): array
    {
        foreach ($this->RememberEditorListChoices($bucket === '' ? $side : $bucket, $rows) as $encoded) {
            $parts = explode(':', $encoded);
            if (count($parts) !== 2 || in_array($encoded, array_column($options, 'value'), true)) {
                continue;
            }
            [$device, $key] = $parts;
            $command = $side === 'Target' ? $this->DecodeCommand($snapshot, $device, $key) : null;
            if ($command !== null) {
                // Keep the repeated native options bounded; the row dialog shows every property/value.
                $options[] = ['caption' => $this->EditorDeviceLabel($snapshot['devices'][$device], $device) . ' — ' . sprintf($this->Translate('Set %s properties together'), count($command)), 'value' => $encoded];
                continue;
            }
            $choice = $this->FindEditorChoice($snapshot, $device, $key, $side);
            if ($choice !== null) {
                $options[] = ['caption'                                                                                                                                                                     => $this->EditorDeviceLabel($snapshot['devices'][$device], $device) . ' — '
                    . $this->EditorPropertyLabel($snapshot, $device, $choice['property'], $side) . ': ' . $this->EditorChoiceValueLabel($snapshot, $device, $choice['property'], $choice['value']), 'value' => $encoded];
            }
        }
        return $options;
    }

    private function PrepareEditorItemForms(array &$form): void
    {
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            $name = $field['name'] ?? '';
            $context = $this->EditorItemContext($name);
            if ($context !== null) {
                $duration = $context['duration'] === '' ? '0' : '$' . $name . "['" . $context['duration'] . "'] ?? 0";
                $field['form'] = "return json_decode(Z2MA_GetEditorItemForm(\$id, '" . $name . "', $" . $name . "['Choice'] ?? '', " . $duration . ', $' . $name . "['Compare'] ?? 'eq', $" . $name . "['Changed'] ?? false), true);";
                if ($context['duration'] === '') {
                    $field['form'] = str_replace('return json_decode(', '$conditionForm = json_decode(', $field['form'])
                        . ' foreach ($conditionForm as &$conditionField) { if (($conditionField[\'name\'] ?? \'\') === \'StartOnly\') { $conditionField[\'value\'] = $'
                        . $name . "['StartOnly'] ?? false; } } unset(\$conditionField); return \$conditionForm;";
                }
            }
        }
        unset($field);
    }
}
