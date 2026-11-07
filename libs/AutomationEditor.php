<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;

require_once __DIR__ . '/DeviceCatalog.php';

trait AutomationEditor
{
    use DeviceCatalog;

    public function ValidateAutomationDraft(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->ValidateYaml($this->ReadEditorAttribute('YamlDraft')));
    }

    public function StoreAutomationDraft(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->StoreYaml($this->ReadEditorAttribute('YamlDraft')));
    }

    public function LoadAutomationEditor(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->StartExtensionWorkflow('editor'));
    }

    public function RefreshDeviceCatalog(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            $this->DebugEvent('AKTION', 'Nur Gerätedaten aus Z2M anfordern; Entwürfe, offene Eingaben und laufende Automationen bleiben erhalten.');
            return $this->StartExtensionWorkflow('catalog');
        });
    }

    public function SelectEditorDevice(string $Side, string $Device, string $Area = ''): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Side, $Device, $Area): void
        {
            if (!$this->CheckEditorArea($Area)) {
                return;
            }
            if (!in_array($Side, ['Source', 'Target', 'Condition'], true)) {
                $this->DebugEvent('FEHLER', 'Unbekannte Geräteauswahl verworfen.');
                return;
            }
            $snapshot = $this->EditorSnapshot();
            if (!isset($snapshot['devices'][$Device])) {
                $Device = '';
            }
            $this->WriteEditorAttribute('Editor' . $Side, $Device);
            $this->UpdateEditorField('Editor' . $Side . 'Choice', 'options', json_encode($this->EditorChoices($snapshot, $Device, $Side), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $this->UpdateEditorField('Editor' . $Side . 'Choice', 'value', '');
            if ($Side === 'Condition') {
                $this->WriteEditorAttribute('EditorConditionProperty', '');
                $this->UpdateEditorField('EditorConditionProperty', 'options', json_encode($this->ConditionPropertyOptions($snapshot, $Device), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $this->UpdateEditorField('EditorConditionProperty', 'value', '');
                $this->UpdateEditorField('EditorConditionChoice', 'options', json_encode($this->EditorItemValues($snapshot, $Device, null, 'Source'), JSON_THROW_ON_ERROR));
                $this->UpdateEditorField('EditorNumericProperty', 'options', json_encode($this->EditorNumericOptions($snapshot, $Device), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
                $this->UpdateEditorField('EditorNumericProperty', 'value', '');
            }
            $this->DebugEvent('AKTION', 'Geräteauswahl im Editor aktualisiert.', ['Bereich' => $this->EditorTypeLabel($this->ActiveEditorArea()), 'Auswahl' => $Side, 'GerätGefunden' => $Device !== '']);
        });
    }

    public function ShowYamlEditor(bool $Visible): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Visible): void
        {
            $this->WriteAttributeBoolean('ShowYaml', $Visible);
            $this->UpdateEditorField('EditorExpert', 'expanded', $Visible);
            $this->DebugEvent('AKTION', 'YAML-Bereich auf- oder zugeklappt.', ['Aufgeklappt' => $Visible]);
        });
    }

    public function CreateSimpleAutomation(string $Name, string $Source, string $Trigger, string $Target, string $Action, string $CurrentDraft = '', bool $CheckDraft = false, bool $Enabled = true): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Name, $Source, $Trigger, $Target, $Action, $CurrentDraft, $CheckDraft, $Enabled): bool
        {
            return $this->WithTransportLock(function () use ($Name, $Source, $Trigger, $Target, $Action, $CurrentDraft, $CheckDraft, $Enabled): bool
            {
                $state = $this->RefreshState();
                $snapshot = $this->EditorSnapshot();
                $draft = $this->ReadEditorDraft();
                $error = $this->EditorContextError($state, $draft, $CurrentDraft, $CheckDraft);
                $source = $this->FindEditorChoice($snapshot, $Source, $Trigger, 'Source');
                $target = $this->FindEditorChoice($snapshot, $Target, $Action, 'Target');
                $name = trim($Name);
                if ($error === '' && (!$this->ValidEditorName($name) || $source === null || $target === null
                    || count($draft['configuration']['automations']) >= 100)) {
                    $error = 'editor_invalid';
                }
                if ($error !== '') {
                    $state['lastError'] = $error;
                    $this->DebugEvent('FEHLER', 'Automation nicht zum Entwurf hinzugefügt.', ['Grund' => $error]);
                    $this->SaveState($state);
                    return false;
                }
                $draft['configuration']['revision'] = $draft['baseRevision'] + 1;
                $draft['configuration']['automations'][] = $this->SimpleEditorRule($name, $Source, $source, $Target, $target, $Enabled);
                $yaml = json_encode($draft['configuration'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
                if (!ExtensionContract::ValidYamlText($yaml)) {
                    $state['lastError'] = 'editor_invalid';
                    $this->SaveState($state);
                    return false;
                }
                $this->SaveEditorDraft($draft, $yaml);
                $this->SetEditorSelection($this->ReadEditorDraft(), '');
                $this->CompleteEditorWork('rule');
                $state['validation'] = null;
                $state['lastError'] = '';
                $state['operation'] = ['kind' => 'editor', 'status' => 'draft_created', 'transaction' => ''];
                $this->DebugEvent(
                    'AKTION',
                    'Automation im lokalen Entwurf ergänzt; Z2M noch nicht verändert.',
                    ['Automationen' => count($draft['configuration']['automations']), 'Revision' => $draft['configuration']['revision']]
                );
                $this->SaveState($state);
                return true;
            });
        });
    }

    private function SimpleEditorRule(string $name, string $source, array $trigger, string $target, array $action, bool $enabled, string $operator = 'eq'): array
    {
        return $this->NewEditorRule(
            $name,
            $enabled,
            [['type' => 'device', 'device' => $source, 'property' => $trigger['property'], 'operator' => $operator, 'value' => $trigger['value']]],
            [$this->BuildEditorAction($target, $action)]
        );
    }

    private function NewEditorRule(string $name, bool $enabled, array $triggers, array $actions): array
    {
        return ['id'   => 'rule_' . bin2hex(random_bytes(8)), 'name' => $name, 'enabled' => $enabled,
            'triggers' => $triggers, 'conditions' => [], 'actions' => $actions];
    }

    private function ReceiveEditorData(array &$state, stdClass $data, array $target, bool $catalogOnly = false): void
    {
        $metadata = ExtensionContract::Status($data);
        $configuration = $data->configuration ?? null;
        if ($metadata === null || $metadata !== $target || ($data->{$catalogOnly ? 'catalog_version' : 'editor_version'} ?? null) !== 1 || !is_array($data->devices ?? null)
            || count($data->devices) > 256 || (!$catalogOnly && (!$configuration instanceof stdClass || ($configuration->schema_version ?? null) !== 1
            || ($configuration->revision ?? null) !== $metadata['storedRevision'] || !is_array($configuration->automations ?? null)
            || SequencePlan::Error(json_decode(json_encode($configuration, JSON_THROW_ON_ERROR), true, 32, JSON_THROW_ON_ERROR)) !== ''
            || count($configuration->automations) !== $metadata['automationCount'] || !in_array($metadata['storage'], ['ok', 'empty'], true)))) {
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return;
        }
        $catalog = $this->ReadDeviceCatalog($data);
        if ($catalog === null) {
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return;
        }
        [$devices, $groups, $scenes] = $catalog;
        if ($catalogOnly) {
            $this->WriteEditorCatalog($state['scope'], $devices, $groups, $scenes);
            $this->DebugEvent(
                'EMPFANG',
                'Gerätedaten erfolgreich aktualisiert. Vorhandene Entwürfe und offene Eingaben bleiben unverändert; die Browseransicht kann jetzt aktualisiert werden.',
                ['Geräte' => count($devices), 'Gruppen' => count($groups), 'Szenen' => count($scenes)]
            );
            $this->FinishExtensionWorkflow($state, 'catalog_loaded');
            return;
        }
        // JSON is a valid YAML document; the authoritative schema check stays in Z2M.
        $yaml = $metadata['storedRevision'] === 0
            ? json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"
            : ($data->yaml ?? null);
        if (!is_string($yaml) || strlen($yaml) > ExtensionContract::MAX_YAML_BYTES
            || ($metadata['storedRevision'] > 0 && hash('sha256', $yaml) !== $metadata['checksum'])) {
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return;
        }
        $preserved = ($state['workflow']['preserveDraft'] ?? false) ? $this->ReadEditorDraft() : null;
        if ($preserved !== null && ($preserved['scope'] !== $state['scope']
            || $preserved['baseRevision'] !== $metadata['storedRevision'] || $preserved['baseChecksum'] !== $metadata['checksum']
            || $preserved['draftChecksum'] !== hash('sha256', $this->ReadEditorAttribute('YamlDraft')))) {
            $this->DebugEvent('FEHLER', 'Gerätedaten nicht übernommen: Entwurf und gespeicherter Z2M-Stand passen nicht zusammen. Vorhandener Entwurf bleibt unverändert.');
            $this->FinishExtensionWorkflow($state, 'error', 'editor_changed');
            return;
        }
        $this->WriteEditorCatalog($state['scope'], $devices, $groups, $scenes);
        if ($preserved !== null) {
            // Only renew the receiver session. Keep YAML bytes, rules and current selection.
            $preserved['session'] = $metadata['session'];
            $this->WriteEditorAttribute('EditorDraft', json_encode($preserved, JSON_THROW_ON_ERROR));
            $this->DebugEvent('AKTION', 'Gerätedaten aktualisiert; vorhandener Entwurf einschließlich ungespeicherter Regeln unverändert erhalten.', ['Automationen' => count($preserved['configuration']['automations'])]);
        } else {
            $this->WriteEditorAttribute('EditorSource', '');
            $this->WriteEditorAttribute('EditorTarget', '');
            $draft = ['scope'  => $state['scope'], 'session' => $metadata['session'], 'baseRevision' => $metadata['storedRevision'],
                'baseChecksum' => $metadata['checksum'], 'configuration' => json_decode(json_encode($configuration, JSON_THROW_ON_ERROR), true, 32, JSON_THROW_ON_ERROR)];
            $this->SaveEditorDraft($draft, $yaml, false);
            $this->SetEditorSelection($this->ReadEditorDraft(), '');
        }
        $state['validation'] = null;
        $this->UpdateEditorField('EditorSource', 'options', json_encode($this->EditorDevices(['devices' => $devices], 'Source'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $this->UpdateEditorField('EditorTarget', 'options', json_encode($this->EditorDevices(['devices' => $devices], 'Target'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $this->UpdateEditorField('EditorCondition', 'options', json_encode($this->EditorDevices(['devices' => $devices], 'Condition'), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        if ($preserved === null) {
            foreach (['EditorSource', 'EditorTarget', 'EditorSourceChoice', 'EditorTargetChoice'] as $field) {
                $this->UpdateEditorField($field, 'value', '');
            }
        }
        $readable = $writable = 0;
        $filtered = $this->EditorSnapshot();
        foreach ($devices as $id => $device) {
            $readable += count($this->EditorProperties($filtered, $id, 'Source'));
            $writable += count($this->EditorProperties($filtered, $id, 'Target'));
        }
        $this->DebugEvent('EMPFANG', 'Gerätekatalog übernommen; Aktionsziele anhand aktiver Symcon-Aktionen geprüft.', ['Geräte' => count($devices), 'Gruppen' => count($groups), 'Szenen' => count($scenes), 'Automationen' => $metadata['automationCount'], 'LesbareEigenschaften' => $readable, 'SchaltbareEigenschaften' => $writable]);
        $this->FinishExtensionWorkflow($state, 'editor_loaded');
    }

    private function WriteEditorCatalog(string $scope, array $devices, array $groups, array $scenes): void
    {
        $this->WriteEditorAttribute('EditorSnapshot', json_encode(['scope' => $scope, 'devices' => $devices, 'groups' => $groups, 'scenes' => $scenes, 'loadedAt' => $this->Now()], JSON_THROW_ON_ERROR));
        $this->UpdateEditorFormField('CatalogRefreshInfo', 'caption', $this->CatalogRefreshText());
    }

    private function CatalogRefreshText(): string
    {
        $raw = $this->ReadEditorAttribute('EditorSnapshot');
        $snapshot = $raw === '' ? [] : json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
        $loadedAt = ($snapshot['scope'] ?? '') === $this->TransportScope() ? ($snapshot['loadedAt'] ?? null) : null;
        return is_int($loadedAt) ? sprintf($this->Translate('Device data last refreshed: %s'), date('d.m.Y H:i:s', $loadedAt))
            : $this->Translate('Device data has not yet been refreshed for this connection.');
    }

    private function SaveEditorDraft(array $draft, string $yaml, bool $synchronizePeer = true): void
    {
        $peer = $this->workspaceEditorContext === null ? $this->PeerContext() : null;
        if (!$synchronizePeer) {
            $this->DebugEvent('AKTION', 'Gespeicherten Z2M-Stand vollständig in den lokalen Entwurf geladen. Regeln, Partnerfreigaben und YAML-Text bleiben unverändert; Z2M wurde nicht verändert.', ['Automationen' => count($draft['configuration']['automations']), 'Revision' => $draft['configuration']['revision'], 'Partnerfreigaben' => count($draft['configuration']['mesh_peers'] ?? [])]);
        } elseif ($peer === null) {
            $this->DebugEvent('AKTION', 'Kein verfügbares Partnermodul ausgewählt. Vorhandene Partnerfreigaben bleiben im lokalen Entwurf erhalten; Z2M wurde nicht verändert.', ['Partnerfreigaben' => count($draft['configuration']['mesh_peers'] ?? [])]);
        } else {
            $peers = [$peer['system']];
            if (($draft['configuration']['mesh_peers'] ?? []) !== $peers) {
                $draft['configuration']['revision'] = $draft['baseRevision'] + 1;
                $draft['configuration']['mesh_peers'] = $peers;
                $yaml = json_encode($draft['configuration'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
                $this->DebugEvent('AKTION', 'Partnerfreigaben im Entwurf geändert; neue Revision vorbereitet. Z2M bleibt bis zum Speichern unverändert.', ['Freigaben' => count($peers)]);
            } else {
                $this->DebugEvent('AKTION', 'Partnerfreigaben unverändert; YAML-Text ohne zusätzliche Neuformatierung übernommen.', ['Revision' => $draft['configuration']['revision'], 'YAMLBytes' => strlen($yaml)]);
            }
        }
        $draft['draftChecksum'] = hash('sha256', $yaml);
        $this->WriteEditorAttribute('EditorDraft', json_encode($draft, JSON_THROW_ON_ERROR));
        $this->WriteEditorAttribute('YamlDraft', $yaml);
        $this->UpdateEditorField('YamlInput', 'value', $yaml);
        $this->UpdateEditorField('EditorPreview', 'caption', sprintf($this->Translate('Draft contains %d automations. Z2M is unchanged until storing.'), count($draft['configuration']['automations'])));
        $this->UpdateEditorField('EditorRules', 'values', json_encode($this->EditorRuleRows($draft), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $this->UpdateEditorField('EditorRule', 'options', json_encode($this->EditorRuleOptions($draft), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $this->RefreshAutomationSections($draft);
        $this->UpdateReusableSequenceForm();
    }

    private function EditorSnapshot(): array
    {
        if ($this->workspaceEditorContext !== null) return $this->workspaceEditorContext['snapshot'];
        return $this->AddRemoteTargets($this->LocalEditorSnapshot());
    }

    private function LocalEditorSnapshot(): array
    {
        $json = $this->ReadEditorAttribute('EditorSnapshot');
        return $json === '' ? [] : $this->FilterEditorActions($this->AddEditorScenes($this->AddEditorGroupTargets(json_decode($json, true, 32, JSON_THROW_ON_ERROR))));
    }

    private function EditorDevices(array $snapshot, string $side): array
    {
        $options = [['caption' => $this->Translate('Choose device'), 'value' => '']];
        foreach ($snapshot['devices'] ?? [] as $id => $device) {
            if ($this->EditorProperties($snapshot, $id, $side) !== [] || ($side === 'Condition' && ($device['numeric'] ?? []) !== [])) {
                $options[] = ['caption' => $this->EditorDeviceLabel($device, $id), 'value' => $id];
            }
        }
        return $options;
    }

    private function EditorDeviceLabel(array $device, string $id): string
    {
        return $device['name'] . ' (' . ($device['remoteTarget']['id'] ?? $id) . ')';
    }

    private function EditorChoices(array $snapshot, string $id, string $side): array
    {
        $options = [['caption' => $this->Translate('Choose event or action'), 'value' => '']];
        foreach ($snapshot['devices'][$id][$side === 'Target' ? 'actions' : 'triggers'] ?? [] as $key => $choice) {
            $options[] = ['caption' => $this->EditorPropertyLabel($snapshot, $id, $choice['property'], $side) . ': ' . $this->EditorChoiceValueLabel($snapshot, $id, $choice['property'], $choice['value']), 'value' => $key];
        }
        return $options;
    }

    private function FindEditorChoice(array $snapshot, string $id, string $key, string $side): ?array
    {
        $choice = $snapshot['devices'][$id][$side === 'Source' ? 'triggers' : 'actions'][$key] ?? null;
        if ($choice !== null) {
            return $this->EditorValueAllowed($snapshot, $id, $choice['property'], $choice['value'], $side) ? $choice : null;
        }
        return $this->DecodeEditorValue($snapshot, $id, $key, $side);
    }

    private function EditorFormFields(array &$items): array
    {
        $fields = [];
        foreach ($items as &$item) {
            $fields[] = &$item;
            if (isset($item['items'])) {
                $children = $this->EditorFormFields($item['items']);
                foreach ($children as &$child) {
                    $fields[] = &$child;
                }
                unset($child);
            }
            if (isset($item['popup']['items'])) {
                $children = $this->EditorFormFields($item['popup']['items']);
                foreach ($children as &$child) {
                    $fields[] = &$child;
                }
                unset($child);
            }
        }
        unset($item);
        return $fields;
    }

    private function FillEditorForm(array &$form, array $state): void
    {
        $snapshot = $this->EditorSnapshot();
        if (($snapshot['scope'] ?? null) !== $state['scope']) {
            $snapshot = [];
        }
        $draft = $snapshot === [] ? null : $this->ReadEditorDraft(true);
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            if (($field['name'] ?? '') === 'CatalogRefreshInfo') {
                $field['caption'] = $this->CatalogRefreshText();
            }
            if (($field['name'] ?? '') === 'EditorRules' && $snapshot !== []) {
                $field['values'] = $draft === null ? [] : $this->EditorRuleRows($draft);
            }
            if (($field['name'] ?? '') === 'EditorPreview') {
                if ($draft !== null) {
                    $configuration = $draft['configuration'];
                    $field['caption'] = sprintf($this->Translate('Draft contains %d automations. Z2M is unchanged until storing.'), count($configuration['automations']));
                }
            }
            foreach (['Source', 'Target'] as $side) {
                $selected = $this->ReadEditorAttribute('Editor' . $side);
                if (($field['name'] ?? '') === 'Editor' . $side) {
                    $field['options'] = $this->EditorDevices($snapshot, $side);
                    $field['value'] = isset($snapshot['devices'][$selected]) ? $selected : '';
                } elseif (($field['name'] ?? '') === 'Editor' . $side . 'Choice') {
                    $field['options'] = $this->EditorChoices($snapshot, $selected, $side);
                }
            }
        }
        unset($field);
        $selection = $this->EditorSelectionFields($draft);
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            $name = $field['name'] ?? '';
            if ($name === 'EditorRule') {
                $field['options'] = $this->EditorRuleOptions($draft ?? ['configuration' => ['automations' => []]]);
            }
            if ($selection['selected'] && array_key_exists($name, $selection)) {
                $field[$name === 'EditorTimes' ? 'values' : 'value'] = $selection[$name];
            }
            if ($name === 'CreateRuleButton' || $name === 'UpdateRuleButton') {
                $field['visible'] = $name === 'UpdateRuleButton' ? $selection['selected'] : !$selection['selected'];
            }
            if (in_array($name, ['DeleteRuleButton', 'CopyRuleButton'], true)) {
                $field['enabled'] = $selection['selected'];
            }
            if (in_array($name, ['EditorSource', 'EditorSourceChoice', 'EditorTarget', 'EditorTargetChoice'], true)) {
                $field['enabled'] = $selection['simple'];
            }
            if ($name === 'EditorEditInfo') {
                $field['caption'] = $this->EditorEditInfo($selection);
            }
        }
        unset($field);
        $this->FillScheduleForm($form, $draft, $selection);
        $this->FillActionSequenceForm($form, $selection, $snapshot);
        $this->FillTriggerSequenceForm($form, $selection, $snapshot);
        $this->FillConditionSequenceForm($form, $selection, $snapshot);
        $this->FillAutomationSections($form, $draft, $selection);
        $this->DebugEvent('AKTION', 'Listenanzeige aus verwendeten Einträgen aufgebaut; die vollständige Geräteauswahl bleibt in den Zeilendialogen verfügbar.', ['Geräte' => count($snapshot['devices'] ?? [])]);
    }

    private function EditorRuleRows(array $draft, string $type = ''): array
    {
        $snapshot = $this->EditorSnapshot();
        $rows = [];
        foreach ($draft['configuration']['automations'] ?? [] as $rule) {
            if (!is_array($rule) || !is_string($rule['name'] ?? null) || ($type !== '' && $this->EditorRuleType($rule) !== $type)) {
                continue;
            }
            $source = is_array($rule['triggers'][0] ?? null) && is_string($rule['triggers'][0]['device'] ?? null) ? $rule['triggers'][0]['device'] : '';
            $target = isset($rule['actions'][0]['target']) ? $this->EditorTargetKey($rule['actions'][0]['target']) : '';
            $sourceLabel = ($rule['triggers'][0]['type'] ?? '') === 'time'
                ? sprintf($this->Translate('Daily at %s (%s)'), $rule['triggers'][0]['at'], $rule['triggers'][0]['timezone'])
                : ($snapshot['devices'][$source]['name'] ?? $this->Translate('Advanced rule'));
            if (($rule['triggers'][0]['type'] ?? '') === 'sun') {
                $sourceLabel = $this->SolarEditorLabel($rule['triggers'][0]);
            }
            if (count($rule['triggers'] ?? []) > 1 && $this->EditorRuleType($rule) === 'device') {
                $sourceLabel = sprintf($this->Translate('%d device triggers (OR)'), count($rule['triggers']));
            }
            if ($this->EditorRuleType($rule) === 'time' && ($rule['triggers'][0]['type'] ?? '') === 'time') {
                $sourceLabel = implode(', ', array_column($rule['triggers'], 'at')) . ' (' . $rule['triggers'][0]['timezone'] . ')';
            }
            $rows[] = ['Name' => $rule['name'], 'Source' => $sourceLabel,
                'Target'      => ($rule['actions'][0]['type'] ?? '') === 'sequence' ? $this->Translate('Action sequence') . ': ' . ($this->ReusableSequence($rule['actions'][0]['sequence_id'])['name'] ?? '') : ($snapshot['devices'][$target]['name'] ?? $this->Translate('Advanced rule')),
                'Enabled'     => $this->Translate(($rule['enabled'] ?? false) === true ? 'Yes' : 'No')];
        }
        return $rows;
    }
}
