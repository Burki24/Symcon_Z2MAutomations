<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait ReusableSequences
{
    public function SelectReusableSequence(string $SequenceID, string $CurrentDraft = '', bool $CheckDraft = false): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($SequenceID, $CurrentDraft, $CheckDraft): bool
        {
            $state = $this->RefreshState();
            $draft = $this->ReadEditorDraft();
            $error = $this->EditorContextError($state, $draft, $CurrentDraft, $CheckDraft, false);
            if ($error !== '' || ($SequenceID !== '' && $this->ReusableSequence($SequenceID) === null)) {
                return $this->RejectEditorChange($state, $error ?: 'sequence_missing');
            }
            $this->RememberReusableSelection($SequenceID, $draft);
            $this->UpdateReusableSequenceForm();
            $this->DebugEvent('AKTION', 'Wiederverwendbare Aktionsfolge zur Bearbeitung ausgewählt; Z2M unverändert.', ['Vorhanden' => $SequenceID !== '']);
            $this->SaveState($state);
            return true;
        }));
    }

    public function SaveReusableSequence(string $SequenceID, string $SelectionToken, string $Name, string $Steps, string $CurrentDraft = '', bool $CheckDraft = false): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($SequenceID, $SelectionToken, $Name, $Steps, $CurrentDraft, $CheckDraft): bool
        {
            $state = $this->RefreshState();
            $draft = $this->ReadEditorDraft();
            $error = $this->EditorContextError($state, $draft, $CurrentDraft, $CheckDraft);
            $selection = $this->ReusableSelection();
            $selected = ($SequenceID === '' && $SelectionToken === '') || (($selection['checksum'] ?? '') === ($draft['draftChecksum'] ?? null) && ($selection['id'] ?? '') === $SequenceID
                && $SelectionToken !== '' && ($selection['token'] ?? '') === $SelectionToken);
            if ($error !== '' || !$selected || !$this->ValidEditorName(trim($Name))) {
                return $this->RejectEditorChange($state, $error ?: (!$selected ? 'editor_changed' : 'editor_invalid'));
            }
            $reason = 'editor_actions';
            $actions = $this->BuildEditorActions($Steps, $reason, false);
            if ($actions === null) {
                return $this->RejectEditorChange($state, $reason);
            }
            $definitions = $draft['configuration']['sequences'] ?? [];
            $index = array_search($SequenceID, array_column($definitions, 'id'), true);
            if ($SequenceID !== '' && $index === false) {
                return $this->RejectEditorChange($state, 'sequence_missing');
            }
            if ($SequenceID === '' && count($definitions) >= 32) {
                return $this->RejectEditorChange($state, 'sequence_invalid');
            }
            $new = $SequenceID === '';
            $id = $new ? 'sequence_' . bin2hex(random_bytes(8)) : $SequenceID;
            $definitions[$index === false ? count($definitions) : $index] = ['id' => $id, 'name' => trim($Name), 'actions' => $actions];
            $draft['configuration']['sequences'] = $definitions;
            $this->DebugEvent('AKTION', 'Wiederverwendbare Aktionsfolge im Entwurf vorbereitet; verwendende Regeln werden gemeinsam geprüft.', ['Neu' => $new, 'Schritte' => count($actions), 'Verwendungen' => $this->SequenceUsage($id, $draft)]);
            if (!$this->CommitEditorChange($state, $draft, $new ? 'sequence_created' : 'sequence_updated', $this->EditorSelection()['id'] ?? '')) {
                return false;
            }
            $this->RememberReusableSelection($id, $this->ReadEditorDraft());
            $this->UpdateReusableSequenceForm();
            return true;
        }));
    }

    public function DeleteReusableSequence(string $SequenceID, string $SelectionToken, string $CurrentDraft = '', bool $CheckDraft = false): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($SequenceID, $SelectionToken, $CurrentDraft, $CheckDraft): bool
        {
            $state = $this->RefreshState();
            $draft = $this->ReadEditorDraft();
            $error = $this->EditorContextError($state, $draft, $CurrentDraft, $CheckDraft);
            $selection = $this->ReusableSelection();
            if ($error !== '' || $SequenceID === '' || $SelectionToken === '' || ($selection['id'] ?? '') !== $SequenceID
                || ($selection['checksum'] ?? '') !== ($draft['draftChecksum'] ?? null) || ($selection['token'] ?? '') !== $SelectionToken) {
                return $this->RejectEditorChange($state, $error ?: 'editor_changed');
            }
            if ($this->SequenceUsage($SequenceID, $draft) > 0) {
                $this->DebugEvent('FEHLER', 'Aktionsfolge wird noch von Automationen verwendet und deshalb nicht gelöscht.', ['Verwendungen' => $this->SequenceUsage($SequenceID, $draft)]);
                return $this->RejectEditorChange($state, 'sequence_in_use');
            }
            $draft['configuration']['sequences'] = array_values(array_filter($draft['configuration']['sequences'] ?? [], static fn (array $entry): bool => $entry['id'] !== $SequenceID));
            if (!$this->CommitEditorChange($state, $draft, 'sequence_deleted', $this->EditorSelection()['id'] ?? '')) {
                return false;
            }
            $this->RememberReusableSelection('', $this->ReadEditorDraft());
            $this->UpdateReusableSequenceForm();
            return true;
        }));
    }

    public function InspectReusableSequenceActions(string $Steps, string $Change): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Steps, $Change): bool
        {
            $reason = 'editor_actions';
            $actions = $this->BuildEditorActions($Steps, $reason, false);
            $valid = $actions !== null && in_array($Change, ['add', 'edit', 'delete', 'order'], true);
            $this->DebugEvent($valid ? 'AKTION' : 'FEHLER', 'Schrittliste einer wiederverwendbaren Aktionsfolge bearbeitet; erst Übernehmen ändert den Entwurf.', ['Gültig' => $valid, 'Änderung' => $Change, 'SchritteVorÄnderung' => count($actions ?? [])]);
            return $valid;
        });
    }

    public function ConfigureSequenceCall(string $List, string $Kind, string $SequenceID, string $Device = '', string $Choice = ''): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($List, $Kind, $SequenceID, $Device, $Choice): void
        {
            $context = $this->EditorItemContext($List);
            if ($context === null || $context['side'] !== 'Target' || ($List === 'SequenceEditorActions' && $Kind === 'sequence') || !$this->CheckEditorArea($context['area']) || !in_array($Kind, ['device', 'sequence', 'combined'], true)) {
                return;
            }
            $sequence = $Kind === 'sequence';
            foreach (['RowDevice', 'RowProperty', 'RowValue'] as $field) {
                $this->UpdateFormField($field, 'visible', !$sequence);
            }
            $this->UpdateFormField('RowNumber', 'visible', false);
            $this->UpdateFormField('RowColor', 'visible', false);
            foreach (['RowCommandSummary', 'RowCommandAdd', 'RowCommandRemove', 'RowCommandHelp', 'RowCommandHint'] as $field) {
                $this->UpdateFormField($field, 'visible', $Kind === 'combined');
            }
            $snapshot = $this->EditorSnapshot();
            $values = [];
            $parts = explode(':', $Choice);
            if ($Kind === 'combined' && count($parts) === 2 && $parts[0] === $Device) {
                $values = $this->DecodeCommand($snapshot, $Device, $parts[1]) ?? [];
                $single = $this->FindEditorChoice($snapshot, $Device, $parts[1], 'Target');
                if ($values === [] && $single !== null) {
                    $values = [$single['property'] => $single['value']];
                }
                if ($values !== [] && $this->CommandChoice($snapshot, $Device, $values) === null) {
                    $values = [];
                }
            }
            $this->UpdateCommandSummary($snapshot, $Device, $values);
            $this->UpdateFormField('RowProperty', 'options', json_encode($this->EditorItemProperties($snapshot, $Device, 'Target', $Kind === 'combined'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $this->UpdateFormField('RowProperty', 'value', '');
            $this->UpdateFormField('RowValue', 'value', '');
            $this->UpdateFormField('RowValue', 'options', json_encode($this->EditorItemValues($snapshot, $Device, null, 'Target'), JSON_THROW_ON_ERROR));
            $this->UpdateFormField('RowSequence', 'visible', $sequence);
            $this->UpdateFormField('RowSequence', 'options', json_encode($this->ReusableOptions(true), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $valid = $sequence && $this->ReusableSequenceAllowed($SequenceID);
            $commandKey = $values === [] ? null : $this->CommandChoice($snapshot, $Device, $values);
            $encoded = $valid ? 'sequence:' . $SequenceID : ($commandKey === null ? '' : $Device . ':' . $commandKey);
            $this->UpdateFormField('Choice', 'value', $encoded);
            if ($encoded !== '') {
                $this->RememberEditorListChoices('Target', [['Choice' => $encoded]], 'pending');
            }
            $this->UpdateEditorField($List, 'columns', json_encode($this->EditorActionColumns($this->EditorSnapshot()), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $this->DebugEvent('AKTION', 'Aktionsart im Schrittdialog gewählt; enthaltene Schaltrechte erneut geprüft.', ['WiederverwendbareFolge' => $sequence, 'FolgeVerfügbar' => $valid]);
        });
    }

    private function ReusableSelection(): array
    {
        return json_decode($this->ReadAttributeString('SequenceSelection') ?: '[]', true, 8, JSON_THROW_ON_ERROR);
    }

    private function RememberReusableSelection(string $id, array $draft): void
    {
        $this->WriteAttributeString('SequenceSelection', json_encode(['id' => $id, 'checksum' => $draft['draftChecksum'], 'token' => $id === '' ? '' : bin2hex(random_bytes(16))], JSON_THROW_ON_ERROR));
    }

    private function ReusableSequence(string $id): ?array
    {
        foreach ($this->ReadEditorDraft()['configuration']['sequences'] ?? [] as $sequence) {
            if ($sequence['id'] === $id) {
                return $sequence;
            }
        }
        return null;
    }

    private function ReusableSequenceAllowed(string $id): bool
    {
        $sequence = $this->ReusableSequence($id);
        return $sequence !== null && $this->EditorActionRows($sequence, false) !== null;
    }

    private function ReusableOptions(bool $callsOnly = false): array
    {
        $options = [['caption' => $this->Translate($callsOnly ? 'Choose a reusable action sequence' : 'Create a new action sequence'), 'value' => '']];
        foreach ($this->ReadEditorDraft()['configuration']['sequences'] ?? [] as $sequence) {
            if (!$callsOnly || $this->ReusableSequenceAllowed($sequence['id'])) {
                $options[] = ['caption' => $sequence['name'], 'value' => $sequence['id']];
            }
        }
        return $options;
    }

    private function SequenceUsage(string $id, ?array $draft): int
    {
        return count(array_filter($draft['configuration']['automations'] ?? [], static fn (array $rule): bool => in_array($id, array_column($rule['actions'], 'sequence_id'), true)));
    }

    private function ReusableFormUpdates(): array
    {
        $draft = $this->ReadEditorDraft();
        $selected = $this->ReusableSelection();
        $id = ($selected['checksum'] ?? '') === ($draft['draftChecksum'] ?? null) ? ($selected['id'] ?? '') : '';
        $sequence = $id === '' ? null : $this->ReusableSequence($id);
        $rows = $sequence === null ? [] : $this->EditorActionRows($sequence, false);
        $editable = $rows !== null;
        $list = [];
        foreach ($draft['configuration']['sequences'] ?? [] as $entry) {
            $list[] = ['Name' => $entry['name'], 'Steps' => count($entry['actions']), 'Usage' => $this->SequenceUsage($entry['id'], $draft)];
        }
        $usage = $this->SequenceUsage($id, $draft);
        return ['SequenceEditorList' => ['values' => $list], 'SequenceEditorID' => ['value' => $id, 'options' => $this->ReusableOptions()],
            'SequenceEditorToken'    => ['value' => $id === '' ? '' : $selected['token']], 'SequenceEditorName' => ['value' => $sequence['name'] ?? ''],
            'SequenceEditorActions'  => ['values' => $rows ?? [], 'columns' => $this->EditorActionColumns($this->EditorSnapshot(), $rows ?? [], false), 'enabled' => $editable],
            'SequenceEditorInfo'     => ['caption' => $editable ? sprintf($this->Translate('%d automations use this sequence. Saving changes affects all of them after transfer to Z2M.'), $usage)
                : $this->Translate('This sequence contains unavailable or advanced actions. It remains unchanged; reload device data or use the YAML editor.')],
            'SequenceSaveButton' => ['enabled' => $draft !== null && $editable], 'SequenceDeleteButton' => ['enabled' => $sequence !== null && $usage === 0]];
    }

    private function FillReusableSequenceForm(array &$form): void
    {
        $updates = $this->ReusableFormUpdates();
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            $field = array_replace($field, $updates[$field['name'] ?? ''] ?? []);
        }
        unset($field);
    }

    private function UpdateReusableSequenceForm(): void
    {
        foreach ($this->ReusableFormUpdates() as $name => $fields) {
            foreach ($fields as $key => $value) {
                $this->UpdateFormField($name, $key, is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : $value);
            }
        }
        $this->UpdateEditorWorkStatus($this->RefreshState());
    }
}
