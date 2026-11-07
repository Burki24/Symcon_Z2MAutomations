<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** Native controls use the same explicit, system-bound entry as the browser. */
trait WorkspaceRuntimeForm
{
    private function WorkspaceRuntimePanel(array $record, array $v): array
    {
        $system = var_export($record['binding']['systemId'], true);
        $prefix = 'Z2MA_RequestWorkspaceRuntime($id, ' . $system . ', ' . $record['binding']['generation'] . ', $WorkspaceEditorFingerprint, ';
        $call = static fn (string $action, bool $single = false): string => '$reply = json_decode(' . $prefix . var_export($action, true) . ', '
            . ($single ? '$WorkspaceRuntimeRule' : '""') . ', ' . ($action === 'stop_rule' ? '$WorkspaceRuntimeRun' : '""')
            . ', $WorkspaceRuntimeResume, bin2hex(random_bytes(16)), $WorkspaceRuntimeContext), true); if (!($reply["ok"] ?? false)) echo $reply["message"];';
        return ['type' => 'ExpansionPanel', 'name' => 'WorkspaceRuntimePanel', 'caption' => 'Gespeicherte Automationen dieses Systems steuern', 'expanded' => true, 'items' => [
            ['type' => 'Label', 'caption' => $record['name'] . ' · Basistopic ' . $record['binding']['baseTopic']],
            ['type' => 'Label', 'name' => 'WorkspaceRuntimeMessage', 'caption' => $v['message']],
            ['type' => 'Label', 'name' => 'WorkspaceRuntimeState', 'caption' => $this->WorkspaceRuntimeStateText($v)],
            ['type' => 'Label', 'name' => 'WorkspaceRuntimeNotice', 'caption' => $v['startNotice']],
            ['type' => 'ValidationTextBox', 'name' => 'WorkspaceRuntimeContext', 'value' => $v['context'], 'visible' => false],
            ['type' => 'ValidationTextBox', 'name' => 'WorkspaceRuntimeRun', 'value' => '', 'visible' => false],
            ['type' => 'CheckBox', 'name' => 'WorkspaceRuntimeResume', 'caption' => 'Nach einem Z2M-Neustart automatisch wieder starten', 'value' => $v['resumeAfterRestart']],
            ['type'       => 'Button', 'name' => 'WorkspaceRuntimeActivate', 'caption' => 'Automatik dieses Systems aktivieren', 'enabled' => $v['canActivate'],
                'confirm' => 'Alle eingeschalteten gespeicherten Automationen von „' . $record['binding']['baseTopic'] . '“ aktivieren? Geräte können dadurch geschaltet werden.', 'onClick' => $call('activate')],
            ['type'       => 'Button', 'name' => 'WorkspaceRuntimeStop', 'caption' => 'Automatik dieses Systems stoppen', 'enabled' => $v['canStop'],
                'confirm' => 'Automatik und laufende Abläufe von „' . $record['binding']['baseTopic'] . '“ stoppen? Andere Systeme bleiben unverändert.', 'onClick' => $call('deactivate')],
            ['type' => 'Button', 'name' => 'WorkspaceRuntimeRefresh', 'caption' => 'Betriebsstatus in Z2M aktualisieren', 'enabled' => $v['canRefresh'], 'onClick' => $call('status')],
            ['type'        => 'Select', 'name' => 'WorkspaceRuntimeRule', 'caption' => 'Eine gespeicherte Automation auswählen', 'options' => [['caption' => 'Bitte auswählen', 'value' => '']], 'value' => '',
                'onChange' => 'Z2MA_SelectWorkspaceRuntimeRule($id, ' . $system . ', ' . $record['binding']['generation'] . ', $WorkspaceEditorFingerprint, $WorkspaceRuntimeRule);'],
            ['type' => 'Label', 'name' => 'WorkspaceRuntimeRuleInfo', 'caption' => 'Bitte eine gespeicherte Automation auswählen.'],
            ['type'       => 'Button', 'name' => 'WorkspaceRuntimeRunOnce', 'caption' => 'Ausgewählte Automation einmal starten', 'enabled' => false,
                'confirm' => 'Ausgewählte Automation in „' . $record['binding']['baseTopic'] . '“ einmal starten? Bedingungen werden geprüft; Geräte können geschaltet werden. Der Automatikbetrieb bleibt unverändert.', 'onClick' => $call('run_once', true)],
            ['type' => 'Button', 'name' => 'WorkspaceRuntimeStopRule', 'caption' => 'Nur den ausgewählten Lauf stoppen', 'enabled' => false, 'onClick' => $call('stop_rule', true)],
            ['type' => 'Label', 'caption' => 'Einzelstart prüft dieselben Bedingungen wie eine automatische Auslösung. Er ändert weder die gespeicherte Regel noch den Automatikbetrieb. Ein Stopp nimmt bereits gesendete Gerätebefehle nicht zurück.']]];
    }

    private function WorkspaceRuntimeStateText(array $v): string
    {
        return !$v['confirmed'] ? 'Automatikbetrieb noch nicht bestätigt.' : 'Automatikbetrieb: ' . ($v['executing'] ? 'Aktiv' : 'Gestoppt')
            . '. Gespeicherte Revision: ' . $v['storedRevision'] . '. Wiederanlauf nach Neustart: ' . ($v['resumeAfterRestart'] ? 'Ein' : 'Aus') . '.';
    }

    private function UpdateWorkspaceRuntimeDisplay(string $system, array $v): void
    {
        $workspace = $this->ReadWorkspace();
        if ($workspace['selectedSystem'] !== $system) return;
        $selection = json_decode($this->GetBuffer('WorkspaceRuntimeRules') ?: '[]', true, 8, JSON_THROW_ON_ERROR)[$system] ?? '';
        if (!isset($v['rules'][$selection])) $selection = '';
        $options = [['caption' => 'Bitte auswählen', 'value' => '']];
        foreach ($v['rules'] as $id => $r) $options[] = ['caption' => $r['name'], 'value' => $id];
        $updates = ['WorkspaceRuntimeContext' => ['value' => $v['context']], 'WorkspaceRuntimeMessage' => ['caption' => $v['message']],
            'WorkspaceRuntimeState'           => ['caption' => $this->WorkspaceRuntimeStateText($v)], 'WorkspaceRuntimeNotice' => ['caption' => $v['startNotice']],
            'WorkspaceRuntimeActivate'        => ['enabled' => $v['canActivate']], 'WorkspaceRuntimeStop' => ['enabled' => $v['canStop']],
            'WorkspaceRuntimeRefresh'         => ['enabled' => $v['canRefresh']], 'WorkspaceRuntimeRule' => ['options' => $options, 'value' => $selection],
            'WorkspaceRuntimeRun'             => ['value' => $v['rules'][$selection]['run'] ?? ''],
            'WorkspaceRuntimeRuleInfo'        => ['caption' => $v['rules'][$selection]['message'] ?? 'Bitte eine gespeicherte Automation auswählen.'],
            'WorkspaceRuntimeRunOnce'         => ['enabled' => $v['rules'][$selection]['canRun'] ?? false],
            'WorkspaceRuntimeStopRule'        => ['enabled' => $v['rules'][$selection]['canStop'] ?? false]];
        foreach ($updates as $field => $properties) foreach ($properties as $key => $value) $this->UpdateFormField($field, $key, is_array($value) ? json_encode($value, WorkspaceState::JSON_FLAGS) : $value);
        $this->DebugFlowWriteValue('RuntimeSystem', $workspace['systems'][$system]['name'] . ' · ' . $workspace['systems'][$system]['binding']['baseTopic'], __FUNCTION__, __LINE__);
        $this->DebugFlowWriteValue('RuntimeStatus', $this->WorkspaceRuntimeStateText($v) . ' ' . $v['message'], __FUNCTION__, __LINE__);
        if ($v['pending'] || array_any($v['rules'], static fn (array $r): bool => $r['state'] === 'running')) $this->SetTimerInterval('WorkspaceRuntimeRefresh', 2000);
    }

    private function FillWorkspaceRuntimePanel(array &$panel, array $record, array $v): void
    {
        $selected = json_decode($this->GetBuffer('WorkspaceRuntimeRules') ?: '[]', true, 8, JSON_THROW_ON_ERROR)[$record['binding']['systemId']] ?? '';
        $options = [['caption' => 'Bitte auswählen', 'value' => '']];
        foreach ($v['rules'] as $id => $r) $options[] = ['caption' => $r['name'], 'value' => $id];
        foreach ($panel['items'] as &$item) {
            if (($item['name'] ?? '') === 'WorkspaceRuntimeRule') $item = array_replace($item, ['options' => $options, 'value' => isset($v['rules'][$selected]) ? $selected : '']);
            if (($item['name'] ?? '') === 'WorkspaceRuntimeRun') $item['value'] = $v['rules'][$selected]['run'] ?? '';
            if (($item['name'] ?? '') === 'WorkspaceRuntimeRuleInfo') $item['caption'] = $v['rules'][$selected]['message'] ?? 'Bitte eine gespeicherte Automation auswählen.';
            if (($item['name'] ?? '') === 'WorkspaceRuntimeRunOnce') $item['enabled'] = $v['rules'][$selected]['canRun'] ?? false;
            if (($item['name'] ?? '') === 'WorkspaceRuntimeStopRule') $item['enabled'] = $v['rules'][$selected]['canStop'] ?? false;
        }
        unset($item);
    }
}
