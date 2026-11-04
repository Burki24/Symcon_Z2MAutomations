<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait EditorWorkForm
{
    private function CompleteEditorWork(string $kind): void
    {
        $this->WriteEditorBuffer('EditorWork.' . $kind, '');
        $this->WriteEditorBuffer('EditorNavigation', '');
        $other = $kind === 'rule' ? 'sequence' : 'rule';
        $text = $this->ReadEditorBuffer('EditorWork.' . $other);
        if ($text === '') {
            return;
        }
        $work = json_decode($text, true, 16, JSON_THROW_ON_ERROR);
        if ($other === 'sequence' && $this->ReusableSequence($work['context']['id']) !== null) {
            $this->RememberReusableSelection($work['context']['id'], $this->ReadEditorDraft());
            $this->UpdateReusableSequenceForm();
        }
        $context = $this->EditorWorkContext($other);
        $old = $work['context'];
        unset($old['token']);
        $same = $context;
        unset($same['token']);
        if ($old === $same) {
            $work['context'] = $context;
            $this->WriteEditorBuffer('EditorWork.' . $other, json_encode($work, JSON_THROW_ON_ERROR));
            $this->RestoreEditorWorkValues($other);
        }
    }

    private function RestoreEditorWorkValues(string $kind, ?array &$form = null): void
    {
        if (!$this->EditorWorkDirty($kind)) {
            return;
        }
        $work = json_decode($this->ReadEditorBuffer('EditorWork.' . $kind), true, 16, JSON_THROW_ON_ERROR);
        $prefix = $kind === 'sequence' ? 'Sequence' : match ($work['context']['area']) {
            'device' => 'Device', 'time' => 'Time', default => ''
        };
        $updates = [];
        foreach ($work['input'] as $key => $value) {
            if (($work['unsettled'] ?? '') === $prefix . $key) {
                // Native delete events may still carry the pre-delete rows. Navigation captures the actual remaining list.
                continue;
            }
            $updates[$prefix . $key][is_array($value) ? 'values' : 'value'] = $value;
        }
        if ($kind === 'rule' && $work['context']['area'] !== 'advanced') {
            $modes = array_replace($this->ScheduleDefaults(), $work['input'], ['EditorTriggerType' => $work['context']['area']]);
            foreach ($this->ScheduleVisibility($modes) as $key => $visible) {
                $updates[$prefix . $key]['visible'] = $visible;
            }
        }
        if ($form !== null) {
            foreach ($this->EditorFormFields($form['actions']) as &$field) {
                $field = array_replace($field, $updates[$field['name'] ?? ''] ?? []);
            }
            unset($field);
        } else {
            foreach ($updates as $name => $properties) {
                foreach ($properties as $key => $value) {
                    $this->UpdateEditorFormField($name, $key, is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR) : $value);
                }
            }
        }
    }

    private function FillEditorWorkForm(array &$form, array $state): void
    {
        $labels = $this->EditorWorkLabels($state);
        $capture = EditorWorkInput::Callback();
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            $name = $field['name'] ?? '';
            if (isset($labels[$name])) {
                $field['caption'] = $this->Translate($labels[$name]);
            }
            if ($name === 'SequenceWorkID') {
                $field['value'] = $this->EditorWorkContext('sequence')['id'];
            }
            if ($name === 'EditorNavigationToken') {
                $pending = $this->ReadEditorBuffer('EditorNavigation');
                $field['value'] = $pending === '' ? '' : (json_decode($pending, true, 16, JSON_THROW_ON_ERROR)['token'] ?? '');
            }
            if ($name === 'DiscardEditorNavigation') {
                $field['onClick'] = 'Z2MA_ResolveEditorNavigation($id, $EditorNavigationToken, ' . $capture . ');';
            }
            $navigation = match ($name) {
                'EditorDeviceRule'   => ['rule', 'device', '$EditorDeviceRule'], 'EditorTimeRule' => ['rule', 'time', '$EditorTimeRule'],
                'EditorAdvancedRule' => ['rule', 'advanced', '$EditorAdvancedRule'], 'SequenceEditorID' => ['sequence', 'sequence', '$SequenceEditorID'], default => null
            };
            if ($navigation !== null) {
                [$kind, $area, $target] = $navigation;
                $field['onChange'] = 'Z2MA_NavigateEditor($id, "' . $kind . '", "' . $area . '", ' . $target . ', $YamlInput, ' . $capture . ');';
            }
            if (in_array($name, ['DeviceEditorDetails', 'TimeEditorDetails'], true)) {
                $area = $name === 'DeviceEditorDetails' ? 'device' : 'time';
                $target = $area === 'device' ? '$EditorDeviceRule' : '$EditorTimeRule';
                $field['onClick'] = 'Z2MA_NavigateEditor($id, "rule", "' . $area . '", ' . $target . ', $YamlInput, ' . $capture . ');';
            }
            $canonical = preg_replace('/\A(?:Device|Time|Sequence)(?=Editor)/', '', $name);
            if (in_array($canonical, EditorWorkInput::FIELDS, true)) {
                if (($field['type'] ?? '') !== 'List') {
                    $field['onChange'] = ($field['onChange'] ?? '') . ' Z2MA_TrackEditorInput($id, ' . $capture . ', false);';
                } else {
                    foreach (['onAdd', 'onEdit', 'onDelete', 'onChangeOrder'] as $event) {
                        if (isset($field[$event])) {
                            $snapshot = $event === 'onDelete' ? EditorWorkInput::Callback($name) : $capture;
                            $field[$event] .= ' Z2MA_TrackEditorInput($id, ' . $snapshot . ', ' . ($event === 'onDelete' ? 'true' : 'false') . ');';
                        }
                    }
                }
            }
            if (isset($field['onChange']) && str_contains($field['onChange'], 'Z2MA_FilterAutomations(')) {
                $field['onChange'] = 'if (!Z2MA_TrackEditorInput($id, ' . $capture . ', false)) { return; } ' . $field['onChange'];
            }
            if (isset($field['onClick']) && is_string($field['onClick'])) {
                $script = $field['onClick'];
                if (preg_match('/Z2MA_(?:ValidateYaml|StoreYaml|ActivateAutomations|LoadAutomationEditor|ImportSymconEvent|DeleteAutomation|DeleteReusableSequence|CopyAutomation)\(/', $script)) {
                    $field['onClick'] = 'if (!Z2MA_EditorWorkReady($id, ' . $capture . ')) { return; } ' . $script;
                } elseif (preg_match('/Z2MA_(?:SaveScheduledAutomation|SaveReusableSequence|UpdateAutomation|CreateSimpleAutomation)\(/', $script)) {
                    $field['onClick'] = 'if (!Z2MA_TrackEditorInput($id, ' . $capture . ', false)) { return; } ' . $script;
                }
            }
        }
        unset($field);
        $this->RestoreEditorWorkValues('rule', $form);
        $this->RestoreEditorWorkValues('sequence', $form);
    }
}
