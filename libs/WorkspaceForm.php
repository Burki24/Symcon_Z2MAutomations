<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

trait WorkspaceForm
{
    public function ApplyWorkspaceConnections(string $Connections, string $Fingerprint, string $CandidatesFingerprint): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Connections, $Fingerprint, $CandidatesFingerprint): bool
        {
            try {
                $ids = $this->DecodeWorkspaceInput($Connections, 1024);
                if (!array_is_list($ids)) throw new RuntimeException('invalid_request');
                if ($ids === []) throw new RuntimeException('no_connections_selected');
                $choices = $this->WorkspaceConnectionChoices($this->ReadWorkspace());
                if (!hash_equals($choices['fingerprint'], $CandidatesFingerprint)) throw new RuntimeException('connection_selection_changed');
                foreach ($ids as $id) {
                    if (!is_int($id)) throw new RuntimeException('invalid_request');
                    $error = $choices['availability'][$id] ?? 'connection_unavailable';
                    if ($error !== '') {
                        $this->DebugEvent('FEHLER', 'Angehakte Verbindung nicht zugeordnet. Vorhandene Entwürfe bleiben erhalten.', ['Verbindung' => $id, 'Grund' => $error]);
                        throw new RuntimeException($error);
                    }
                }
                $this->DebugEvent('EMPFANG', 'Angehakte Z2M-Verbindungen erneut geprüft. Nur die bestätigte Auswahl wird lokal zugeordnet.', ['Ausgewählt' => count($ids), 'Verbindungen' => $ids]);
                $result = json_decode($this->ConfigureWorkspace($Connections, $Fingerprint, bin2hex(random_bytes(16))), true, 16, JSON_THROW_ON_ERROR);
            } catch (Throwable $exception) {
                $result = json_decode($this->WorkspaceFailure($exception), true, 16, JSON_THROW_ON_ERROR);
            }
            $this->UpdateFormField('WorkspaceNotice', 'caption', $result['ok'] ? 'Systemzuordnung übernommen. Die Entwürfe bleiben getrennt; Z2M wurde nicht verändert.' : $result['message']);
            if ($result['ok']) $this->RequestWorkspaceFormReload();
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
                $this->RequestWorkspaceFormReload();
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
            if ($result['ok']) $this->RequestWorkspaceFormReload();
            return $result['ok'];
        });
    }

    /** Installation-local discovery; no transport request or implicit assignment. */
    private function WorkspaceConnectionChoices(array $state): array
    {
        $configured = [];
        foreach ($state['systems'] as $record) $configured[$record['binding']['connectionInstance']] = $record;
        $found = IPS_GetInstanceListByModuleID(SystemContract::CONNECTION_MODULE);
        $ids = array_values(array_unique(array_merge($found, array_keys($configured))));
        sort($ids, SORT_NUMERIC);
        $rows = $identities = $availability = [];
        foreach ($ids as $id) {
            $assigned = isset($configured[$id]);
            $exists = false;
            $binding = null;
            $name = $configured[$id]['name'] ?? 'Verbindung nicht mehr vorhanden';
            $topic = $configured[$id]['binding']['baseTopic'] ?? '';
            $mqtt = 'Keine MQTT-Schnittstelle zugeordnet';
            try {
                $exists = in_array($id, $found, true) && IPS_InstanceExists($id);
                if ($exists) {
                    $name = IPS_GetName($id);
                    $topic = IPS_GetProperty($id, 'BaseTopic');
                    $parent = IPS_GetInstance($id)['ConnectionID'];
                    if ($parent > 0 && IPS_InstanceExists($parent)) $mqtt = IPS_GetName($parent) . ' (#' . $parent . ')';
                    $binding = $this->WorkspaceConnectionBinding($id);
                }
            } catch (Throwable) {
                $exists = false;
            }
            $owner = null;
            $error = $binding === null ? 'connection_unavailable' : '';
            try {
                if ($exists) $owner = json_decode(Z2MC_GetSystemOwnership($id), true, 16, JSON_THROW_ON_ERROR);
                if ($exists && !is_array($owner)) throw new RuntimeException('connection_unavailable');
                if (($owner['owner'] ?? $this->InstanceID) !== $this->InstanceID) $error = 'connection_owned';
                elseif ($binding !== null && !$this->WorkspaceConnectionCurrent($binding)) $error = 'binding_changed';
            } catch (Throwable) {
                $error = 'connection_unavailable';
            }
            $availability[$id] = $error;
            $identities[] = [$id, $binding, $owner, $error];
            $status = match (true) {
                !$exists                      => 'Gespeicherte Verbindung fehlt. Zuordnung bleibt erhalten.',
                $error === 'connection_owned' => 'Bereits einer anderen Zentrale zugeordnet.',
                $error !== ''                 => 'MQTT-Zuordnung, Basistopic und Besitzer prüfen.',
                $assigned                     => 'Bereits zugeordnet; bleibt erhalten.',
                default                       => 'Verfügbar – zum Zuordnen anhaken.'
            };
            $rows[] = ['Connection' => $id, 'Use' => $assigned, 'Name' => $name . ' (#' . $id . ')', 'Topic' => $topic,
                'MQTT'              => $mqtt, 'Status' => $status, 'editable' => !$assigned && $error === ''];
        }
        $this->DebugEvent(
            'AKTION',
            'Vorhandene Z2M-Verbindungen für die Häkchenauswahl gelesen. Keine automatische Zuordnung und keine MQTT-Anfrage.',
            ['Gefunden' => count($found), 'BereitsZugeordnet' => count($configured), 'NichtVerfügbar' => count(array_filter($availability, static fn (string $error): bool => $error !== ''))]
        );
        return ['rows' => $rows, 'availability' => $availability, 'fingerprint' => WorkspaceState::Fingerprint($identities)];
    }
    private function WorkspaceConfigurationForm(): string
    {
        $setup = json_decode($this->GetWorkspaceSetupStatus(), true, 32, JSON_THROW_ON_ERROR);
        if (!$setup['ok']) return $this->EncodeConfigurationForm(['elements' => [], 'actions' => [['type' => 'Label', 'caption' => $setup['message']]]]);
        $state = $this->ReadWorkspace();
        if ($this->WorkspaceSetupLocked()) return $this->EncodeConfigurationForm(['elements' => [], 'actions' => [$this->WorkspaceSetupPanel($state['systems'][$setup['setup']['systemId']], $setup['setup'])]]);
        if ($state['selectedSystem'] !== '') {
            $this->SyncWorkspaceTransfer($state['selectedSystem']);
            $state = $this->ReadWorkspace();
        }
        $overview = json_decode($this->WorkspaceOverview($state), true, 32, JSON_THROW_ON_ERROR);
        $choices = $this->WorkspaceConnectionChoices($state);
        $rows = $choices['rows'];
        $options = [];
        foreach ($overview['systems'] ?? [] as $system) {
            $binding = $system['binding'];
            $options[] = ['caption' => $system['name'] . ' · ' . $binding['baseTopic'], 'value' => $binding['systemId']];
        }
        $rootFingerprint = WorkspaceState::Fingerprint($state);
        $actions = [
            ['type' => 'ExpansionPanel', 'caption' => 'Z2M-Systeme', 'expanded' => true, 'items' => [
                ['type' => 'Label', 'caption' => 'Vorhandene Verbindungen werden automatisch angezeigt. Gewünschte Systeme unter „Verwenden“ anhaken und anschließend die Systemzuordnung übernehmen. Höchstens acht Systeme; vorhandene Entwürfe werden dadurch nicht übernommen. Bereits zugeordnete Systeme bleiben angehakt und geschützt.'],
                ['type'       => 'List', 'name' => 'WorkspaceConnections', 'rowCount' => 8, 'values' => $rows, 'add' => false, 'delete' => false,
                    'columns' => [['name' => 'Use', 'caption' => 'Verwenden', 'width' => '90px', 'save' => true, 'edit' => ['type' => 'CheckBox']],
                        ['name' => 'Connection', 'caption' => 'Instanz-ID', 'width' => '80px', 'visible' => false, 'save' => true],
                        ['name' => 'Name', 'caption' => 'Z2M-Verbindung', 'width' => 'auto'], ['name'  => 'Topic', 'caption' => 'Basistopic', 'width' => '140px'],
                        ['name' => 'MQTT', 'caption' => 'MQTT-Zuordnung', 'width' => '220px'], ['name' => 'Status', 'caption' => 'Zuordnung', 'width' => '230px']]],
                ['type' => 'Label', 'caption' => $rows === [] ? 'Keine Z2M-Verbindung gefunden. Bitte zuerst eine Instanz „Zigbee2MQTT Verbindung“ anlegen und deren MQTT-Schnittstelle und Basistopic einrichten.' : 'Neue Verbindungen erscheinen nach erneutem Öffnen dieses Formulars. Die Anzeige fragt Z2M nicht ab.'],
                ['type'       => 'Button', 'name' => 'WorkspaceApplyConnections', 'caption' => 'Systemzuordnung übernehmen', 'enabled' => $rows !== [],
                    'onClick' => '$connectionRows = $WorkspaceConnections instanceof \Traversable ? iterator_to_array($WorkspaceConnections) : (array) $WorkspaceConnections; Z2MA_ApplyWorkspaceConnections($id, json_encode(array_column(array_filter($connectionRows, static fn ($row) => (((array) $row)["Use"] ?? false) === true), "Connection")), $WorkspaceFingerprint, $WorkspaceCandidatesFingerprint);']]],
            ['type' => 'ValidationTextBox', 'name' => 'WorkspaceCandidatesFingerprint', 'value' => $choices['fingerprint'], 'visible' => false],
            ['type' => 'ValidationTextBox', 'name' => 'WorkspaceFingerprint', 'value' => $rootFingerprint, 'visible' => false],
            ['type' => 'Label', 'name' => 'WorkspaceNotice', 'caption' => 'System auswählen und dessen lokalen Entwurf bearbeiten. Z2M wird durch die Bearbeitung nicht verändert.']
        ];
        if ($state['systems'] === []) {
            $actions[] = ['type' => 'Label', 'caption' => 'Noch kein System zugeordnet. Bitte oben die gewünschten Verbindungen anhaken und die Systemzuordnung übernehmen. Vorhandene Einzelentwürfe bleiben bis zur gesicherten Übernahme erhalten.'];
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
        $actions[] = $this->WorkspaceSetupPanel($record, $setup['setup']);
        $actions[] = ['type' => 'Button', 'caption' => 'Automationsdesigner öffnen', 'onClick' => 'echo Z2MA_OpenAutomationDesigner($id);'];
        $actions[] = ['type' => 'Button', 'caption' => 'Bestätigte Gerätedaten dieses Systems einlesen',
            'onClick'        => 'Z2MA_ReadWorkspaceCatalog($id, ' . $source . ', ' . $generation . ', $WorkspaceEditorFingerprint);'];
        if ($record['data']['document'] === null) {
            $actions[] = ['type' => 'Label', 'caption' => 'Für dieses System fehlt noch ein lokaler Entwurf. Oben „Z2M-Automationen sicher einlesen“ wählen, die Vorschau prüfen und anschließend die Verbindung übernehmen. Bestehende Z2M-Dateien werden nicht ersetzt.'];
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
        $transfer = $this->WorkspaceTransferView($record);
        $callback = ' $reply = json_decode(Z2MA_RequestWorkspaceTransfer($id, ' . $source . ', ' . $generation
            . ', $WorkspaceEditorFingerprint, %s, bin2hex(random_bytes(16)), $WorkspaceTransferContext), true);'
            . ' if (!($reply["ok"] ?? false)) echo $reply["message"] ?? "Auftrag nicht bestätigt. Bitte Status und Debug prüfen.";';
        $actions[] = ['type' => 'ExpansionPanel', 'caption' => 'Entwurf für dieses System prüfen und übertragen', 'expanded' => true, 'items' => [
            ['type' => 'Label', 'caption' => $record['name'] . ' · Basistopic ' . $record['binding']['baseTopic']],
            ['type' => 'ValidationTextBox', 'name' => 'WorkspaceTransferContext', 'value' => $transfer['context'], 'visible' => false],
            ['type' => 'Label', 'name' => 'WorkspaceTransferMessage', 'caption' => $transfer['message']],
            ['type' => 'Label', 'name' => 'WorkspaceTransferDetails', 'caption' => $transfer['details']],
            ['type'       => 'Button', 'name' => 'WorkspaceValidate', 'caption' => 'Entwurf in Z2M prüfen', 'enabled' => $transfer['canValidate'],
                'onClick' => sprintf($callback, '"validate"')],
            ['type'       => 'Button', 'name' => 'WorkspaceStore', 'caption' => 'Geprüften Entwurf in Z2M speichern', 'enabled' => $transfer['canStore'],
                'confirm' => 'Geprüften Entwurf für „' . $record['binding']['baseTopic'] . '“ in Z2M speichern? Bei Änderungen stoppt dort der bisherige Automatikbetrieb. Es wird keine Automation aktiviert.',
                'onClick' => sprintf($callback, '"store"')],
            ['type' => 'Button', 'caption' => 'Übertragungsstatus aktualisieren', 'onClick' => 'Z2MA_GetWorkspaceTransferStatus($id, ' . $source . ');'],
            ['type' => 'Label', 'caption' => 'Jeder Entwurf wird getrennt geprüft und gespeichert. Speichern ersetzt nur die Automationsdatei dieses Systems und stoppt dort bei Änderungen die bisherige Ausführung. Zum Starten anschließend den Automatikbetrieb dieses Systems aktivieren.']]];
        $runtime = $this->WorkspaceRuntimeView($record);
        $runtimePanel = $this->WorkspaceRuntimePanel($record, $runtime);
        $this->FillWorkspaceRuntimePanel($runtimePanel, $record, $runtime);
        $actions[] = $runtimePanel;
        $this->UpdateWorkspaceRuntimeDisplay($id, $runtime);
        $this->UpdateWorkspaceTransferDisplay($id, $transfer);
        $this->DebugEvent('AKTION', 'Gemeinsames Formular mit eindeutig ausgewähltem Systementwurf aufgebaut. Kein Gatewaywechsel und keine Übertragung.', ['Systembezug' => $id, 'Systeme' => count($options)]);
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
