<?php

declare(strict_types=1);

namespace Z2MAutomations;

use InvalidArgumentException;

trait AutomationOverview
{
    public function FilterAutomations(string $Area, string $Query, string $Enabled): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($Area, $Query, $Enabled): bool
        {
            if (!in_array($Area, ['device', 'time', 'advanced'], true) || !in_array($Enabled, ['all', 'enabled', 'disabled'], true)
                || strlen($Query) > 640 || preg_match('//u', $Query) !== 1 || preg_match_all('/./us', $Query) > 160) {
                if (in_array($Area, ['device', 'time', 'advanced'], true)) {
                    $this->UpdateEditorFormField('Editor' . ucfirst($Area) . 'Results', 'caption', $this->Translate('Search was not applied. Use at most 160 characters and choose a listed filter.'));
                }
                $this->DebugEvent('FEHLER', 'Suchfilter abgelehnt: Bereich, Regelschalter oder Suchlänge ist ungültig. Entwurf und Eingaben bleiben erhalten.');
                return false;
            }
            $query = preg_replace('/\s+/u', ' ', trim($Query));
            $filters = $this->AutomationOverviewFilters();
            $filters[$Area] = ['query' => $query, 'enabled' => $Enabled];
            $this->WriteEditorBuffer('AutomationOverviewFilters', json_encode(['scope' => $this->RefreshState()['scope'], 'areas' => $filters], JSON_THROW_ON_ERROR));
            $draft = $this->ReadEditorDraft();
            if (($draft['scope'] ?? null) !== $this->RefreshState()['scope']) {
                $draft = null;
            }
            $fields = $this->AutomationOverviewFields($draft, $this->EditorSelectionFields($draft));
            $this->UpdateAutomationOverview($fields);
            $stem = ucfirst($Area);
            $this->DebugEvent('AKTION', 'Automationsübersicht gefiltert; Bearbeitung, Entwurf und Z2M-Betrieb bleiben unverändert.', [
                'Bereich' => $this->EditorTypeLabel($Area), 'SucheGesetzt' => $query !== '', 'Regelfilter' => match ($Enabled) {
                    'enabled' => 'Eingeschaltet', 'disabled' => 'Ausgeschaltet', default => 'Alle'
                },
                'Treffer' => count($fields['Editor' . $stem . 'Rules']['values'])]);
            return true;
        }));
    }

    public function CopyAutomation(string $RuleID, string $SelectionToken, string $CurrentDraft, bool $CheckDraft, string $Area): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->WithTransportLock(function () use ($RuleID, $SelectionToken, $CurrentDraft, $CheckDraft, $Area): bool
        {
            if (!$this->CheckEditorArea($Area)) {
                return false;
            }
            $state = $this->RefreshState();
            $draft = $this->ReadEditorDraft();
            $error = $this->EditorContextError($state, $draft, $CurrentDraft, $CheckDraft);
            $index = $this->EditorRuleIndex($draft, $RuleID);
            if ($error === '' && ($this->EditorWorkDirty('rule') || $this->EditorWorkDirty('sequence'))) {
                $error = 'editor_unapplied';
            }
            if ($error === '' && ($index === null || !$this->MatchesEditorSelection($draft, $RuleID, $SelectionToken))) {
                $error = 'editor_changed';
            }
            if ($error === '' && count($draft['configuration']['automations']) >= 100) {
                $error = 'editor_limit';
            }
            if ($error === '' && !$this->ValidEditorName($draft['configuration']['automations'][$index]['name'])) {
                $error = 'editor_invalid';
            }
            if ($error !== '') {
                return $this->RejectEditorChange($state, $error);
            }
            $copy = $draft['configuration']['automations'][$index];
            do {
                $newID = 'rule_' . bin2hex(random_bytes(8));
            } while ($this->EditorRuleIndex($draft, $newID) !== null);
            $copy['id'] = $newID;
            $copy['name'] = $this->AutomationCopyName($copy['name'], $draft);
            $copy['enabled'] = false;
            $draft['configuration']['automations'][] = $copy;
            if (!$this->CommitEditorChange($state, $draft, 'draft_copied', $newID)) {
                return false;
            }
            $type = $this->EditorRuleType($copy);
            $filters = $this->AutomationOverviewFilters();
            unset($filters[$type]);
            $this->WriteEditorBuffer('AutomationOverviewFilters', json_encode(['scope' => $state['scope'], 'areas' => $filters], JSON_THROW_ON_ERROR));
            $this->RefreshAutomationSections($this->ReadEditorDraft());
            $this->DebugEvent('AKTION', 'Automation als ausgeschaltete Kopie im lokalen Entwurf angelegt und zur Bearbeitung geöffnet. Suchfilter dieses Bereichs zurückgesetzt; Z2M bleibt unverändert.', [
                'Bereich'  => $this->EditorTypeLabel($type), 'Automationen' => count($draft['configuration']['automations']),
                'Auslöser' => count($copy['triggers']), 'Bedingungen' => count($copy['conditions']), 'Aktionsschritte' => count($copy['actions']), 'RegelEingeschaltet' => false]);
            return true;
        }));
    }

    private function AutomationCopyName(string $name, array $draft): string
    {
        $characters = preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY);
        $names = array_column($draft['configuration']['automations'], 'name');
        for ($number = 1; ; $number++) {
            $suffix = ' (' . $this->Translate('copy') . ($number === 1 ? '' : ' ' . $number) . ')';
            $length = preg_match_all('/./us', $suffix);
            $candidate = rtrim(implode('', array_slice($characters, 0, 120 - $length))) . $suffix;
            if (!in_array($candidate, $names, true)) {
                return $candidate;
            }
        }
    }

    private function AutomationOverviewFilters(): array
    {
        $text = $this->ReadEditorBuffer('AutomationOverviewFilters');
        $filters = $text === '' ? [] : json_decode($text, true, 8, JSON_THROW_ON_ERROR);
        return ($filters['scope'] ?? null) === $this->RefreshState()['scope'] ? $filters['areas'] : [];
    }

    private function AutomationOverviewFields(?array $draft, array $selection): array
    {
        $draft ??= ['configuration' => ['automations' => []]];
        $filters = $this->AutomationOverviewFilters();
        $snapshot = $this->EditorSnapshot();
        $fields = [];
        foreach (['device' => 'Device', 'time' => 'Time', 'advanced' => 'Advanced'] as $type => $stem) {
            $filter = $filters[$type] ?? ['query' => '', 'enabled' => 'all'];
            $rules = array_values(array_filter($draft['configuration']['automations'], fn (array $r): bool => $this->EditorRuleType($r) === $type));
            $rows = $this->EditorRuleRows($draft, $type);
            $visible = $ids = [];
            foreach ($rules as $i => $rule) {
                if (($filter['enabled'] === 'enabled' && !$rule['enabled']) || ($filter['enabled'] === 'disabled' && $rule['enabled'])
                    || !$this->AutomationSearchMatches($filter['query'], $rule, $rows[$i], $draft, $snapshot)) {
                    continue;
                }
                $visible[] = $rows[$i];
                $ids[$rule['id']] = true;
            }
            $selected = $this->ActiveEditorArea() === $type ? $selection['EditorRule'] : '';
            $outside = $selected !== '' && !isset($ids[$selected]);
            $options = [];
            foreach ($this->EditorRuleOptions($draft, $type) as $option) {
                if ($option['value'] === '' || isset($ids[$option['value']]) || $option['value'] === $selected) {
                    if ($outside && $option['value'] === $selected) {
                        $option['caption'] = sprintf($this->Translate('%s — currently being edited'), $option['caption']);
                    }
                    $options[] = $option;
                }
            }
            $fields['Editor' . $stem . 'Rules'] = ['values' => $visible];
            $fields['Editor' . $stem . 'Rule'] = ['options' => $options, 'value' => $selected];
            $fields['Editor' . $stem . 'Search'] = ['value' => $filter['query']];
            $fields['Editor' . $stem . 'StateFilter'] = ['value' => $filter['enabled']];
            $fields['Editor' . $stem . 'Results'] = ['caption' => sprintf($this->Translate('%d of %d automations shown.'), count($visible), count($rules))
                . ($outside ? ' ' . $this->Translate('The open automation does not match the filter and remains available for editing.') : '')];
            if ($type === 'advanced') {
                $fields['EditorAdvancedSection'] = ['visible' => $rules !== []];
            }
        }
        return $fields;
    }

    private function AutomationSearchMatches(string $query, array $rule, array $row, array $draft, array $snapshot): bool
    {
        if ($query === '') {
            return true;
        }
        $text = implode(' ', [$row['Name'], $row['Source'], $row['Target']]);
        foreach (array_merge($rule['triggers'], $rule['conditions']) as $trigger) {
            $text .= ' ' . ($snapshot['devices'][$trigger['device'] ?? '']['name'] ?? '');
        }
        try {
            $actions = array_column(SequencePlan::Expand($draft['configuration'], $rule['actions']), 'action');
        } catch (InvalidArgumentException) {
            $actions = $rule['actions'];
        }
        foreach ($actions as $action) {
            if (isset($action['target'])) {
                $text .= ' ' . ($snapshot['devices'][$this->EditorTargetKey($action['target'])]['name'] ?? '');
            }
        }
        foreach (explode(' ', $query) as $word) {
            if (preg_match('/' . preg_quote($word, '/') . '/iu', $text) !== 1) {
                return false;
            }
        }
        return true;
    }

    private function UpdateAutomationOverview(array $fields): void
    {
        foreach ($fields as $name => $properties) {
            foreach ($properties as $key => $value) {
                $this->UpdateEditorFormField($name, $key, is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : $value);
            }
        }
    }
}
