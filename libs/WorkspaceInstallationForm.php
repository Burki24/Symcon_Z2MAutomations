<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait WorkspaceInstallationForm
{
    private function WorkspaceInstallationPanel(array $record): array
    {
        $v = $this->WorkspaceInstallationView($record);
        $id = $record['binding']['systemId'];
        $this->SetBuffer('WorkspaceInstallationForm', json_encode(['system' => $id, 'context' => $v['context'], 'binding' => $record['binding'], 'dataHash' => WorkspaceState::Fingerprint($record['data'])], JSON_THROW_ON_ERROR));
        $prefix = 'Z2MA_RequestWorkspaceInstallation($id, ' . var_export($id, true) . ', ' . $record['binding']['generation'] . ', '
            . var_export($v['fingerprint'], true) . ', bin2hex(random_bytes(16)), ' . var_export($v['context'], true) . ', true)';
        $input = $record['data']['document'] === null ? "''" : EditorWorkInput::Callback();
        $reload = 'Z2MA_ChooseWorkspaceSystem($id, ' . var_export($id, true) . ', $WorkspaceFingerprint, ' . var_export($id, true) . ', '
            . $record['binding']['generation'] . ', $WorkspaceEditorFingerprint, ' . $input . ');';
        return ['type' => 'ExpansionPanel', 'name' => 'WorkspaceInstallationPanel', 'caption' => 'Z2M-Erweiterung dieses Systems installieren oder aktualisieren', 'expanded' => false, 'items' => [
            ['type' => 'Label', 'caption' => $record['name'] . ' · Basistopic ' . $record['binding']['baseTopic']],
            ['type' => 'Label', 'name' => 'WorkspaceInstallationInstalled', 'caption' => $this->InstallationBuildLabel('Installierter Build', $v['installedBuild'])],
            ['type' => 'Label', 'name' => 'WorkspaceInstallationBundled', 'caption' => $this->InstallationBuildLabel('Mitgelieferter Build', $v['bundledBuild'])],
            ['type' => 'Label', 'name' => 'WorkspaceInstallationMessage', 'caption' => $v['message']],
            ['type' => 'Label', 'name' => 'WorkspaceInstallationDevices', 'caption' => $v['devices']['message']],
            ['type' => 'Label', 'name' => 'WorkspaceInstallationRuntime', 'caption' => $v['runtimeMessage']],
            ['type' => 'Label', 'caption' => $v['notice']],
            ['type'       => 'Button', 'name' => 'WorkspaceInstallationStart', 'caption' => 'Mitgelieferte Erweiterung auf diesem System installieren oder aktualisieren', 'enabled' => $v['canInstall'],
                'confirm' => 'Erweiterung für „' . $record['binding']['baseTopic'] . '“ installieren und die vorhandene Datei ersetzen? Mitgelieferter Build: ' . substr($v['bundledBuild'] ?? '', 0, 12) . '. ' . $v['notice'],
                'onClick' => '$reply = json_decode(' . $prefix . ', true); if (!($reply["ok"] ?? false)) echo $reply["message"];'],
            ['type' => 'Button', 'caption' => 'Installationsstatus aktualisieren', 'onClick' => 'Z2MA_GetWorkspaceInstallationStatus($id, ' . var_export($id, true) . ');'],
            ['type' => 'Button', 'name' => 'WorkspaceInstallationReload', 'caption' => 'Installationsansicht neu laden', 'onClick' => $reload],
            ['type' => 'Label', 'caption' => 'Nach einer Änderung des angezeigten Erweiterungsstands zuerst die Installationsansicht neu laden. Die Bestätigung gilt nur für den darin angezeigten Stand.']]];
    }

    private function InstallationBuildLabel(string $label, ?string $build): string
    {
        return $label . ': ' . ($build === null ? 'Noch nicht bestätigt oder nicht verfügbar.' : substr($build, 0, 12));
    }

    private function UpdateWorkspaceInstallationDisplay(string $system, array $v): void
    {
        if ($this->ReadWorkspace()['selectedSystem'] !== $system) return;
        $shown = json_decode($this->GetBuffer('WorkspaceInstallationForm') ?: '[]', true, 8, JSON_THROW_ON_ERROR);
        $same = ($shown['system'] ?? '') === $system && ($shown['context'] ?? '') === $v['context'];
        foreach (['WorkspaceInstallationMessage' => $v['message'], 'WorkspaceInstallationDevices' => $v['devices']['message'], 'WorkspaceInstallationRuntime' => $v['runtimeMessage'],
            'WorkspaceInstallationInstalled'     => $this->InstallationBuildLabel('Installierter Build', $v['installedBuild']),
            'WorkspaceInstallationBundled'       => $this->InstallationBuildLabel('Mitgelieferter Build', $v['bundledBuild'])] as $field => $text) $this->UpdateFormField($field, 'caption', $text);
        $this->UpdateFormField('WorkspaceInstallationStart', 'enabled', $same && $v['canInstall']);
    }
}
