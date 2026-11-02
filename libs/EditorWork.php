<?php

declare(strict_types=1);

namespace Z2MAutomations;

use InvalidArgumentException;
use JsonException;

require_once __DIR__ . '/EditorWorkInput.php';

trait EditorWork
{
    public function TrackEditorInput(string $Input, bool $Unsettled = false): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($Input, $Unsettled): bool
        {
            return $this->RememberEditorInput($Input, $Unsettled);
        }));
    }

    public function NavigateEditor(string $Kind, string $Area, string $ID, string $CurrentDraft, string $Input): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Kind, $Area, $ID, $CurrentDraft, $Input): bool
        {
            $ready = $this->WithTransportLock(function () use ($Kind, $Area, $ID, $CurrentDraft, $Input): bool
            {
                if (!in_array($Kind, ['rule', 'sequence'], true) || !in_array($Area, ['device', 'time', 'advanced', 'sequence'], true)
                    || !$this->RememberEditorInput($Input, false)) {
                    return false;
                }
                $context = $this->EditorWorkContext($Kind);
                if ($context['id'] === $ID && $context['area'] === $Area) {
                    return true;
                }
                if ($this->EditorWorkDirty($Kind)) {
                    $token = bin2hex(random_bytes(16));
                    $this->SetBuffer('EditorNavigation', json_encode(['token' => $token, 'kind' => $Kind, 'area' => $Area, 'id' => $ID,
                        'context'                                             => $context, 'draft' => hash('sha256', $CurrentDraft), 'work' => $this->GetBuffer('EditorWork.' . $Kind)], JSON_THROW_ON_ERROR));
                    $this->UpdateFormField('EditorNavigationToken', 'value', $token);
                    $this->UpdateFormField('EditorNavigationPopup', 'visible', true);
                    $this->RestoreEditorSelectors();
                    $this->DebugEvent('AKTION', 'Wechsel angehalten: Formular enthält noch nicht übernommene Änderungen. Weiterbearbeiten oder ausdrücklich verwerfen.', ['Bearbeitung' => $Kind === 'rule' ? 'Automation' : 'Aktionsfolge']);
                    return false;
                }
                return true;
            });
            if (!$ready) {
                return false;
            }
            $current = $this->EditorWorkContext($Kind);
            return ($current['id'] === $ID && $current['area'] === $Area) || $this->PerformEditorNavigation($Kind, $Area, $ID, $CurrentDraft);
        });
    }

    public function ResolveEditorNavigation(string $Token, string $Input): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Token, $Input): bool
        {
            $next = $this->WithTransportLock(function () use ($Token, $Input): ?array
            {
                $text = $this->GetBuffer('EditorNavigation');
                $pending = $text === '' ? null : json_decode($text, true, 16, JSON_THROW_ON_ERROR);
                if (!$this->RememberEditorInput($Input, false, false) || $pending === null || $Token === '' || $pending['token'] !== $Token
                    || $pending['context'] !== $this->EditorWorkContext($pending['kind'])
                    || $pending['draft'] !== hash('sha256', $this->ReadAttributeString('YamlDraft'))
                    || $pending['work'] !== $this->GetBuffer('EditorWork.' . $pending['kind'])) {
                    $this->DebugEvent('FEHLER', 'Veraltete Bestätigung verworfen; Eingaben bleiben erhalten. Gewünschte Automation erneut auswählen.');
                    return null;
                }
                $this->SetBuffer('EditorNavigation', '');
                $this->SetBuffer('EditorWork.' . $pending['kind'], '');
                $this->UpdateFormField('EditorNavigationPopup', 'visible', false);
                $this->DebugEvent('AKTION', 'Nicht übernommene Formularänderungen nach ausdrücklicher Bestätigung verworfen; Entwurf und Z2M bleiben unverändert.', ['Bearbeitung' => $pending['kind'] === 'rule' ? 'Automation' : 'Aktionsfolge']);
                return $pending;
            });
            return $next !== null && $this->PerformEditorNavigation($next['kind'], $next['area'], $next['id'], $this->ReadAttributeString('YamlDraft'));
        });
    }

    public function EditorWorkReady(string $Input): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($Input): bool
        {
            $ready = $this->RememberEditorInput($Input, false) && !$this->EditorWorkDirty('rule') && !$this->EditorWorkDirty('sequence');
            if (!$ready) {
                $this->UpdateFormField('EditorWorkPopup', 'visible', true);
                $this->DebugEvent('AKTION', 'Bedienauftrag angehalten: Änderungen zuerst in den Entwurf übernehmen. Keine Übertragung und keine Aktivierung.');
            }
            return $ready;
        }));
    }

    private function PerformEditorNavigation(string $kind, string $area, string $id, string $draft): bool
    {
        $ok = $kind === 'sequence' ? $this->SelectReusableSequence($id, $draft, true) : $this->SelectAutomation($id, $draft, true, $area === 'advanced' ? 'device' : $area);
        if ($ok) {
            $this->SetBuffer('EditorWork.' . $kind, '');
            $this->UpdateEditorWorkStatus($this->RefreshState());
        }
        return $ok;
    }

    private function EditorWorkContext(string $kind): array
    {
        $draft = $this->ReadEditorDraft();
        if ($kind === 'sequence') {
            $selected = $this->ReusableSelection();
            if (($selected['checksum'] ?? null) !== ($draft['draftChecksum'] ?? null)) {
                $selected = [];
            }
            $id = $selected['id'] ?? '';
            $record = $this->ReusableSequence($id);
            $area = 'sequence';
        } else {
            $selected = $this->EditorSelection();
            if (($selected['checksum'] ?? null) !== ($draft['draftChecksum'] ?? null)) {
                $selected = [];
            }
            $id = $selected['id'] ?? '';
            $index = $this->EditorRuleIndex($draft, $id);
            $record = $index === null ? null : $draft['configuration']['automations'][$index];
            $area = $this->ActiveEditorArea();
        }
        return ['scope' => $draft['scope'] ?? '', 'id' => $id, 'area' => $area,
            'record'    => hash('sha256', json_encode($record, JSON_THROW_ON_ERROR)), 'token' => $selected['token'] ?? ''];
    }

    private function RememberEditorInput(string $input, bool $unsettled, bool $invalidate = true): bool
    {
        try {
            if (strlen($input) > 131072) {
                throw new InvalidArgumentException('editor_work_input');
            }
            $values = json_decode($input, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($values)) {
                throw new InvalidArgumentException('editor_work_input');
            }
            foreach (['rule', 'sequence'] as $kind) {
                $context = $this->EditorWorkContext($kind);
                $incoming = $values[$context['area']] ?? null;
                if ($incoming === null || $this->ReadEditorDraft() === null || $context['scope'] !== $this->RefreshState()['scope']) {
                    continue;
                }
                if (!is_array($incoming) || !is_string($values['_deleted'] ?? '')) {
                    throw new InvalidArgumentException('editor_work_input');
                }
                if (($incoming['_id'] ?? '') !== $context['id'] || ($incoming['_token'] ?? '') !== $context['token']) {
                    throw new InvalidArgumentException('editor_work_stale');
                }
                $actual = EditorWorkInput::Normalize($incoming, $context['area']);
                $base = $this->EditorWorkBase($kind, $context['area']);
                $uncertain = $unsettled && $kind === (str_starts_with($values['_deleted'] ?? '', 'Sequence') ? 'sequence' : 'rule');
                $dirty = $actual !== $base || $uncertain;
                $prior = $this->EditorWorkDirty($kind);
                $this->SetBuffer('EditorWork.' . $kind, json_encode(['context' => $context, 'input' => $actual, 'dirty' => $dirty,
                    'unsettled'                                                => $uncertain ? ($values['_deleted'] ?? '') : ''], JSON_THROW_ON_ERROR));
                if ($dirty !== $prior) {
                    $this->DebugEvent('AKTION', $dirty ? 'Formular geändert; Angaben sind noch nicht im Entwurf und noch nicht in Z2M.' : 'Formulareingaben entsprechen wieder dem Entwurf.', ['Bearbeitung' => $kind === 'rule' ? 'Automation' : 'Aktionsfolge']);
                }
            }
        } catch (JsonException | InvalidArgumentException) {
            $this->DebugEvent('FEHLER', 'Formulareingaben ungültig oder veraltet; kein Wechsel und keine Übertragung. Aktuelle Bearbeitung weiterführen.');
            return false;
        }
        if ($invalidate) {
            $this->SetBuffer('EditorNavigation', '');
        }
        $this->UpdateEditorWorkStatus($this->RefreshState());
        return true;
    }

    private function EditorWorkBase(string $kind, string $area): array
    {
        if ($kind === 'sequence') {
            $sequence = $this->ReusableSequence($this->EditorWorkContext('sequence')['id']);
            return EditorWorkInput::Normalize(['EditorName' => $sequence['name'] ?? '', 'EditorActions' => $sequence === null ? [] : ($this->EditorActionRows($sequence, false) ?? [])], $area);
        }
        $fields = $this->EditorSelectionFields($this->ReadEditorDraft());
        $fields['EditorActions'] = $fields['EditorActionRows'] ?? [];
        $fields['EditorTriggers'] = $fields['EditorTriggerRows'] ?? [];
        $fields['EditorConditionList'] = $fields['EditorConditionRows'] ?? [];
        return EditorWorkInput::Normalize($fields, $area);
    }

    private function EditorWorkDirty(string $kind): bool
    {
        $text = $this->GetBuffer('EditorWork.' . $kind);
        $work = $text === '' ? [] : json_decode($text, true, 16, JSON_THROW_ON_ERROR);
        return ($work['dirty'] ?? false) && ($work['context']['scope'] ?? '') === $this->RefreshState()['scope'] && ($work['context'] ?? null) === $this->EditorWorkContext($kind);
    }

    private function RestoreEditorSelectors(): void
    {
        $context = $this->EditorWorkContext('rule');
        foreach (['device' => 'EditorDeviceRule', 'time' => 'EditorTimeRule', 'advanced' => 'EditorAdvancedRule'] as $area => $name) {
            $this->UpdateFormField($name, 'value', $context['area'] === $area ? $context['id'] : '');
        }
        $this->UpdateFormField('SequenceEditorID', 'value', $this->EditorWorkContext('sequence')['id']);
    }

    private function EditorWorkLabels(array $state): array
    {
        $draft = $this->ReadEditorDraft();
        $dirty = $this->EditorWorkDirty('rule') || $this->EditorWorkDirty('sequence');
        $current = $draft !== null && $draft['scope'] === $state['scope'];
        $same = $current && $state['extensionConfirmed'] && ($state['extension']['checksum'] ?? null) === $draft['draftChecksum'];
        $text = !$current ? 'Draft: Load devices and saved automations first.' : ($same ? 'Draft: Stored in Z2M.'
            : (($state['validation']['checksum'] ?? null) === $draft['draftChecksum'] ? 'Draft: Validated. Store it in Z2M next.' : 'Draft: Changes are local in Symcon. Validate and store them in Z2M.'));
        $runtime = !$state['runtimeConfirmed'] ? 'Z2M operation: Not yet confirmed. Request extension status.'
            : (($state['extension']['executing'] ?? false) ? ($same ? 'Z2M operation: The saved automations are running.' : 'Z2M operation: The saved automations are running. Draft changes do not apply yet.')
                : 'Z2M operation: Stopped. Activate the saved automations to start them.');
        return ['EditorWorkInfo' => $dirty ? 'Form changed: Not yet included in the draft. Use the button to apply your changes to the draft.' : 'Form: No pending edits. Changes must be applied to the draft before transfer.',
            'DraftWorkInfo'      => $text, 'RuntimeWorkInfo' => $runtime];
    }

    private function UpdateEditorWorkStatus(array $state): void
    {
        foreach ($this->EditorWorkLabels($state) as $name => $text) {
            $this->UpdateFormField($name, 'caption', $this->Translate($text));
        }
        $this->UpdateFormField('SequenceWorkID', 'value', $this->EditorWorkContext('sequence')['id']);
    }
}
