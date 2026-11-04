<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

trait WorkspaceForm
{
    public function ApplyWorkspaceConnections(string $Connections, string $Fingerprint): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Connections, $Fingerprint): bool
        {
            $result = json_decode($this->ConfigureWorkspace($Connections, $Fingerprint, bin2hex(random_bytes(16))), true, 16, JSON_THROW_ON_ERROR);
            $this->UpdateFormField('WorkspaceNotice', 'caption', $result['ok'] ? 'Systemzuordnung übernommen. Die Entwürfe bleiben getrennt; Z2M wurde nicht verändert.' : $result['message']);
            if ($result['ok']) $this->ReloadForm();
            return $result['ok'];
        });
    }

    public function ChooseWorkspaceSystem(string $SystemID, string $Fingerprint, string $FromSystem, int $Generation, string $EditorFingerprint, string $Input): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Fingerprint, $FromSystem, $Generation, $EditorFingerprint, $Input): bool
        {
            try {
                if (WorkspaceState::Fingerprint($this->ReadWorkspace()) !== $Fingerprint) throw new RuntimeException('stale_workspace');
                if ($FromSystem !== '' && $Input !== '') {
                    $ok = $this->WithWorkspaceEditor($FromSystem, $Generation, $EditorFingerprint, true, fn (): bool => $this->TrackEditorInput($Input, false), true);
                    if (!$ok) throw new RuntimeException('invalid_data');
                }
                $result = json_decode($this->SelectWorkspaceSystem($SystemID, WorkspaceState::Fingerprint($this->ReadWorkspace()), bin2hex(random_bytes(16))), true, 16, JSON_THROW_ON_ERROR);
                if (!$result['ok']) throw new RuntimeException($result['error']);
                $this->ReloadForm();
                return true;
            } catch (Throwable $exception) {
                $result = json_decode($this->WorkspaceFailure($exception), true, 16, JSON_THROW_ON_ERROR);
                $this->UpdateFormField('WorkspaceNotice', 'caption', $result['message']);
                $this->UpdateFormField('WorkspaceSystem', 'value', $this->ReadWorkspace()['selectedSystem']);
                $this->UpdateFormField('WorkspaceFingerprint', 'value', WorkspaceState::Fingerprint($this->ReadWorkspace()));
                return false;
            }
        });
    }

    public function ReadWorkspaceCatalog(string $SystemID, int $Generation, string $Fingerprint): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint): bool
        {
            $result = json_decode($this->RefreshWorkspaceCatalog($SystemID, $Generation, $Fingerprint, bin2hex(random_bytes(16))), true, 16, JSON_THROW_ON_ERROR);
            $this->UpdateFormField('WorkspaceNotice', 'caption', $result['ok'] ? 'Bestätigte Gerätedaten eingelesen. Entwurf erhalten.' : $result['message']);
            if ($result['ok']) $this->ReloadForm();
            return $result['ok'];
        });
    }
    private function WorkspaceConfigurationForm(): string
    {
        $state = $this->ReadWorkspace();
        $overview = json_decode($this->WorkspaceOverview($state), true, 32, JSON_THROW_ON_ERROR);
        $rows = [];
        $options = [];
        foreach ($overview['systems'] ?? [] as $system) {
            $binding = $system['binding'];
            $rows[] = ['Connection' => $binding['connectionInstance'], 'System' => $system['name'], 'Topic' => $binding['baseTopic'],
                'Status'            => !$system['connectionCurrent'] ? 'Zuordnung prüfen' : (!$system['confirmed'] ? 'Noch nicht bestätigt' : ($system['online'] ? 'Erreichbar' : 'Nicht erreichbar')),
                'Stored'            => $system['storedRevision'] ?? 0, 'Active' => $system['activeRevision'] ?? 0];
            $options[] = ['caption' => $system['name'] . ' · ' . $binding['baseTopic'], 'value' => $binding['systemId']];
        }
        $rootFingerprint = WorkspaceState::Fingerprint($state);
        $actions = [
            ['type' => 'ExpansionPanel', 'caption' => 'Z2M-Systeme', 'expanded' => true, 'items' => [
                ['type' => 'Label', 'caption' => 'Bis zu acht vorhandene Z2M-Verbindungen zuordnen. Jedes System behält seinen eigenen Entwurf. Diese Zuordnung übernimmt keine bisherigen Automationen.'],
                ['type'       => 'List', 'name' => 'WorkspaceConnections', 'rowCount' => 8, 'values' => $rows, 'add' => true, 'delete' => true,
                    'columns' => [['name' => 'Connection', 'caption' => 'Z2M-Verbindung', 'width' => '220px', 'add' => 0, 'edit' => ['type' => 'SelectInstance', 'moduleID' => SystemContract::CONNECTION_MODULE]],
                        ['name' => 'System', 'caption' => 'Name', 'width' => 'auto'], ['name'                => 'Topic', 'caption' => 'Basistopic', 'width' => '150px'],
                        ['name' => 'Status', 'caption' => 'Letzte Bestätigung', 'width' => '180px'], ['name' => 'Stored', 'caption' => 'Gespeichert', 'width' => '100px'], ['name' => 'Active', 'caption' => 'Aktiv', 'width' => '80px']]],
                ['type' => 'Button', 'caption' => 'Systemzuordnung übernehmen', 'onClick' => 'Z2MA_ApplyWorkspaceConnections($id, json_encode(array_column($WorkspaceConnections, "Connection")), $WorkspaceFingerprint);']]],
            ['type' => 'ValidationTextBox', 'name' => 'WorkspaceFingerprint', 'value' => $rootFingerprint, 'visible' => false],
            ['type' => 'Label', 'name' => 'WorkspaceNotice', 'caption' => 'System auswählen und dessen lokalen Entwurf bearbeiten. Z2M wird durch die Bearbeitung nicht verändert.']
        ];
        if ($state['systems'] === []) {
            $actions[] = ['type' => 'Label', 'caption' => 'Noch kein System zugeordnet. Bitte zuerst vorhandene Z2M-Verbindungen eintragen. Vorhandene Einzelentwürfe können über den gesicherten Übernahmeablauf übernommen werden.'];
            return $this->EncodeConfigurationForm(['elements' => [], 'actions' => $actions]);
        }
        $id = $state['selectedSystem'];
        $record = $state['systems'][$id];
        $fingerprint = WorkspaceState::Fingerprint($record);
        $source = var_export($id, true);
        $generation = $record['binding']['generation'];
        $input = $record['data']['document'] === null ? "''" : EditorWorkInput::Callback();
        $actions[] = ['type' => 'Select', 'name' => 'WorkspaceSystem', 'caption' => 'Entwurf dieses Systems bearbeiten', 'options' => $options, 'value' => $id,
            'onChange'       => 'Z2MA_ChooseWorkspaceSystem($id, $WorkspaceSystem, $WorkspaceFingerprint, ' . $source . ', ' . $generation . ', $WorkspaceEditorFingerprint, ' . $input . ');'];
        $actions[] = ['type' => 'ValidationTextBox', 'name' => 'WorkspaceEditorFingerprint', 'value' => $fingerprint, 'visible' => false];
        $actions[] = ['type' => 'Button', 'caption' => 'Automationsdesigner öffnen', 'onClick' => 'echo Z2MA_OpenAutomationDesigner($id);'];
        $actions[] = ['type' => 'Button', 'caption' => 'Bestätigte Gerätedaten dieses Systems einlesen',
            'onClick'        => 'Z2MA_ReadWorkspaceCatalog($id, ' . $source . ', ' . $generation . ', $WorkspaceEditorFingerprint);'];
        if ($record['data']['document'] === null) {
            $actions[] = ['type' => 'Label', 'caption' => 'Für dieses System fehlt noch ein lokaler Entwurf. Ein vorhandener Entwurf muss zuerst über den gesicherten Übernahmeablauf zugeordnet werden. Es wird kein leerer Entwurf über bestehende Daten geschrieben.'];
        } else {
            $editing = $this->WithWorkspaceEditor($id, $generation, $fingerprint, false, function (): array
            {
                $form = $this->LoadConfigurationForm();
                $keep = ['EditorDeviceSection', 'EditorTimeSection', 'EditorAdvancedSection', 'ReusableSequencePanel', 'EditorExpert',
                    'SequenceWorkID', 'EditorNavigationPopup', 'EditorWorkPopup', 'ErrorInfo'];
                $form['actions'] = array_values(array_filter($form['actions'], static fn (array $field): bool => in_array($field['name'] ?? '', $keep, true)));
                $this->FillEditorForm($form, $this->RefreshState());
                $this->PrepareEditorItemForms($form);
                $this->FillReusableSequenceForm($form);
                $this->FillEditorWorkForm($form, $this->RefreshState());
                foreach ($this->EditorFormFields($form['actions']) as &$field) {
                    if (($field['name'] ?? '') === 'YamlInput') {
                        $field['value'] = $this->ReadEditorAttribute('YamlDraft');
                        $field['enabled'] = false;
                    }
                }
                unset($field);
                return $form['actions'];
            }, false, $state);
            $this->BindWorkspaceCallbacks($editing, $id, $generation);
            array_push($actions, ...$editing);
        }
        $actions[] = ['type' => 'Label', 'caption' => 'Prüfen, Speichern, Aktivieren und Stopp werden im nächsten Schritt systemgebunden angebunden. Diese Oberfläche ändert ausschließlich lokale Entwürfe.'];
        $this->DebugEvent('AKTION', 'Gemeinsames Formular mit eindeutig ausgewähltem Systementwurf aufgebaut. Kein Gatewaywechsel und keine Übertragung.', ['Systembezug' => $id, 'Systeme' => count($rows)]);
        return $this->EncodeConfigurationForm(['elements' => [], 'actions' => $actions]);
    }

    /** Transform only our trusted callback source, never PHP submitted by a client. */
    private function BindWorkspaceCallbacks(array &$items, string $id, int $generation): void
    {
        foreach ($items as &$value) {
            if (is_array($value)) $this->BindWorkspaceCallbacks($value, $id, $generation);
            elseif (is_string($value) && str_contains($value, 'Z2MA_')) {
                $offset = 0;
                while (preg_match('/Z2MA_([A-Za-z]+)\(\$id\s*,?/', $value, $match, PREG_OFFSET_CAPTURE, $offset)) {
                    $method = $match[1][0];
                    if ($method === 'WorkspaceEditorAction') {
                        $offset = $match[0][1] + strlen($match[0][0]);
                        continue;
                    }
                    if (!in_array($method, self::WORKSPACE_EDITOR_ACTIONS, true)) throw new RuntimeException('invalid_request');
                    $start = $match[0][1];
                    $arguments = $start + strlen($match[0][0]);
                    $depth = 1;
                    $quote = '';
                    for ($end = $arguments; $end < strlen($value); $end++) {
                        $char = $value[$end];
                        if ($quote !== '') {
                            if ($char === '\\') {
                                $end++;
                                continue;
                            }
                            if ($char === $quote) $quote = '';
                        } elseif ($char === '"' || $char === "'") $quote = $char;
                        elseif ($char === '(') $depth++;
                        elseif ($char === ')' && --$depth === 0) break;
                    }
                    if ($depth !== 0) throw new RuntimeException('invalid_request');
                    $call = 'Z2MA_WorkspaceEditorAction($id, ' . var_export($id, true) . ', ' . $generation . ', $WorkspaceEditorFingerprint, '
                        . var_export($method, true) . ', json_encode([' . substr($value, $arguments, $end - $arguments) . ']))';
                    $call = '(static function (string $reply, string &$fingerprint) { $result = json_decode($reply, true, 32, JSON_THROW_ON_ERROR); $fingerprint = $result["fingerprint"]; return $result["result"]; })(' . $call . ', $WorkspaceEditorFingerprint)';
                    $value = substr_replace($value, $call, $start, $end - $start + 1);
                    $offset = $start + strlen($call);
                }
            }
        }
        unset($value);
    }
}
