<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** System adapters for the existing converter; no event execution or transport. */
trait WorkspaceEventImport
{
    private function ImportGateway(): int
    {
        return $this->workspaceEditorContext['record']['binding']['mqttParent'] ?? $this->GetParentID();
    }

    private function SelectedWorkspaceImport(string $action, array $arguments): bool
    {
        $state = $this->ReadWorkspace();
        $id = $state['selectedSystem'];
        if ($id === '' || !isset($state['systems'][$id])) return false;
        $record = $state['systems'][$id];
        $reply = json_decode($this->WorkspaceEditorAction($id, $record['binding']['generation'], WorkspaceState::Fingerprint($record), $action, json_encode($arguments, JSON_THROW_ON_ERROR)), true, 32, JSON_THROW_ON_ERROR);
        return ($reply['result'] ?? false) === true;
    }

    private function ImportInstanceScope(int $id, bool $allowTargets = false): ?array
    {
        if ($this->workspaceEditorContext === null) return $this->ReadZ2MInstanceScope($id, $allowTargets);
        $ctx = $this->workspaceEditorContext;
        foreach ($ctx['workspace']['systems'] as $system => $record) {
            $local = $system === $ctx['id'];
            if (!$local && (!$allowTargets || !WorkspaceState::Allows($ctx['record'], $record))) continue;
            if (!$this->WorkspaceConnectionCurrent($record['binding'])) continue;
            $scope = Z2MInstanceIdentity::Read($id, $record['binding']['mqttParent'], $record['binding']['baseTopic']);
            if ($scope === null || $scope['type'] === 'bridge') continue;
            if (!$local) $scope['key'] = $this->EditorTargetKey(['type' => $scope['type'], 'id' => $scope['type'] === 'group' ? (int) substr($scope['key'], 6) : $scope['key'], 'system' => $scope['topic']]);
            return $scope;
        }
        return null;
    }

    private function EventImportView(): array
    {
        $report = $this->EventImportBuffer('EventImportReport');
        $preview = $this->EventImportBuffer('EventImportPreview');
        $context = $this->EventImportContext();
        if (($report['context'] ?? null) !== $context) $report = [];
        if (($preview['context'] ?? null) !== $context) $preview = [];
        $draft = $this->ReadEditorDraft();
        $rows = [];
        foreach ($report['rows'] ?? [] as $row) {
            $imported = ($row['RuleID'] ?? '') !== '' && $this->EditorRuleIndex($draft, $row['RuleID']) !== null;
            $rows[] = ['id'  => $row['Event'], 'name' => $row['Name'], 'device' => $row['Device'], 'instanceId' => $row['Instance'],
                'importable' => $row['Supported'] && !$imported, 'imported' => $imported,
                'assessment' => $imported ? $this->Translate('This event is already present in the draft. Edit the imported rule instead.') : $row['Result']];
        }
        $result = $preview['result'] ?? [];
        $blocked = $this->DesignerCreateBlock();
        $selected = (int) ($preview['event'] ?? 0);
        $row = array_find($rows, static fn (array $entry): bool => $entry['id'] === $selected);
        $canImport = ($result['rule'] ?? null) !== null && ($preview['token'] ?? '') !== '' && $blocked === '' && count($draft['configuration']['automations'] ?? []) < 100;
        return ['systemId' => $this->workspaceEditorContext['id'], 'generation' => $this->workspaceEditorContext['record']['binding']['generation'],
            'canEvaluate'  => $this->ImportCatalogMatchesContext($this->EditorSnapshot()),
            'scanned'      => $report !== [], 'limited' => ($report['limited'] ?? false) === true, 'rows' => $rows,
            'selected'     => ['id' => $selected, 'name' => $row['name'] ?? '', 'summary' => $result['summary'] ?? '',
                'assessment'        => isset($result['reason']) ? $this->ImportAssessmentText($result['reason'], $result['arguments'] ?? []) : '',
                'canImport'         => $canImport, 'token' => $canImport ? $preview['token'] : ''],
            'context' => $this->DesignerCreateContext(), 'blocked' => $blocked,
            'message' => $blocked !== '' ? $blocked : ($report === [] ? 'Zuerst „Vorhandene Ereignisse prüfen“ wählen. Die bisherigen Symcon-Ereignisse bleiben unverändert.'
                : ($rows === [] ? 'Für dieses System wurden keine passenden Symcon-Ereignisse gefunden.' : count($rows) . ' Ereignisse dieses Systems geprüft. Einen Eintrag für die Vorschau auswählen.'))];
    }
}
