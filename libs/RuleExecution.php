<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait RuleExecution
{
    public function SelectExecutionRule(string $RuleID): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($RuleID): void
        {
            $this->WithTransportLock(function () use ($RuleID): void
            {
                $draft = $this->ReadEditorDraft();
                $valid = $RuleID === '' || ($draft !== null && $this->EditorRuleIndex($draft, $RuleID) !== null);
                $this->WriteAttributeString('ExecutionRule', $valid ? $RuleID : '');
                $this->DebugEvent('AKTION', 'Automation für einen einzelnen Lauf ausgewählt; noch keine Aktion ausgeführt.', ['AuswahlGültig' => $valid]);
                $this->SaveState($this->RefreshState());
            });
        });
    }

    public function RunAutomationOnce(string $RuleID, string $CurrentDraft = '', bool $CheckDraft = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($RuleID, $CurrentDraft, $CheckDraft): string
        {
            $valid = $this->WithTransportLock(function () use ($RuleID, $CurrentDraft, $CheckDraft): bool
            {
                $state = $this->RefreshState();
                $draft = $this->ReadEditorDraft();
                $valid = $draft !== null && $this->EditorRuleIndex($draft, $RuleID) !== null
                    && $this->SavedExecutionDraft($state) && (!$CheckDraft || $CurrentDraft === $this->ReadAttributeString('YamlDraft'));
                if (!$valid) {
                    $state['lastError'] = 'execution_draft';
                    $this->DebugEvent('FEHLER', 'Einmaligen Start abgelehnt: Automation fehlt oder der Entwurf weicht vom gespeicherten Stand ab. Erst Geräte und gespeicherte Automationen laden oder Änderungen prüfen und speichern.');
                    $this->SaveState($state);
                }
                return $valid;
            });
            return $valid ? $this->StartExtensionWorkflow('run_once', executionRule: $RuleID) : '';
        });
    }

    public function StopAutomationRun(string $RuleID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->StartExtensionWorkflow('stop_rule', executionRule: $RuleID));
    }

    public function RefreshExecutionStatus(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            $this->StartExtensionWorkflow('runs', automatic: true);
        });
    }

    private function SavedExecutionDraft(array $state): bool
    {
        $draft = $this->ReadEditorDraft();
        return $draft !== null && ($draft['scope'] ?? '') === $state['scope'] && ($state['extension']['storage'] ?? '') === 'ok'
            && ($draft['configuration']['revision'] ?? 0) === ($state['extension']['storedRevision'] ?? null)
            && ($state['extension']['checksum'] ?? null) === hash('sha256', $this->ReadAttributeString('YamlDraft'))
            && ($draft['draftChecksum'] ?? '') === ($state['extension']['checksum'] ?? null);
    }

    private function AcceptExecutionSnapshot(array &$state, mixed $data): bool
    {
        $snapshot = ExecutionContract::Snapshot($data);
        $metadata = $state['extension'];
        if ($snapshot === null || !$state['extensionConfirmed'] || $snapshot['session'] !== ($metadata['session'] ?? null)
            || $snapshot['revision'] !== $metadata['storedRevision'] || $snapshot['checksum'] !== $metadata['checksum']) {
            return false;
        }
        $previous = $state['execution'] ?? null;
        if ($previous !== null && $previous['session'] === $snapshot['session'] && $previous['checksum'] === $snapshot['checksum']
            && $previous['revision'] === $snapshot['revision'] && $previous['sequence'] >= $snapshot['sequence']) {
            return $previous === $snapshot;
        }
        if ($previous !== $snapshot) {
            foreach ($snapshot['runs'] as $id => $run) {
                if (($previous['runs'][$id] ?? null) !== $run) {
                    $this->DebugEvent(
                        $run['state'] === 'failed' ? 'FEHLER' : 'EMPFANG',
                        'Z2M hat den Zustand eines einzelnen Regellaufs gemeldet.',
                        ['Regelbezug'     => substr(hash('sha256', $id), 0, 16), 'Lauf' => $run['run'], 'Zustand' => match ($run['state']) {
                            'running' => 'Läuft', 'completed' => 'Abgeschlossen; Gerätewirkung nicht bestätigt', 'skipped' => 'Wegen einer Bedingung ausgelassen',
                            'failed'  => 'Fehlgeschlagen; restliche Aktionen abgebrochen', 'stopped' => 'Gestoppt; andere Regeln bleiben unverändert'
                        },
                            'Regelnummer' => $this->SavedExecutionDraft($state) ? ($this->EditorRuleIndex($this->ReadEditorDraft(), $id) ?? -1) + 1 : 0,
                            'Schritt'     => $run['step'], 'Grund' => $run['error'] === '' ? '' : RuntimeDebugContract::Reason($run['error'])]
                    );
                }
            }
        }
        $state['execution'] = $snapshot;
        return true;
    }

    private function ExecutionCommand(array &$state, array $workflow, mixed $data): ?array
    {
        $metadata = $state['extension'];
        $rule = $workflow['executionRule'];
        $fresh = ExecutionContract::Snapshot($data->execution ?? null);
        $command = ['session'   => $metadata['session'], 'rule_id' => $rule,
            'expected_revision' => $metadata['storedRevision'], 'checksum' => $metadata['checksum']];
        $error = ($metadata['executionVersion'] ?? 0) !== 1 || $fresh === null || $fresh['session'] !== $metadata['session']
            || $fresh['revision'] !== $metadata['storedRevision'] || $fresh['checksum'] !== $metadata['checksum'] ? 'execution_unavailable' : '';
        if ($error === '' && $workflow['kind'] === 'run_once') {
            $error = !$this->SavedExecutionDraft($state) ? 'execution_draft'
                : (!RuntimeDebugContract::UUID($data->activation_token ?? null) ? 'activation_expired' : '');
            $command['activation_token'] = $data->activation_token ?? '';
        } elseif ($error === '') {
            $run = $state['execution']['runs'][$rule] ?? null;
            $error = ($run['state'] ?? '') !== 'running' || ($run['run'] ?? '') !== ($workflow['executionRun'] ?? '') ? 'run_conflict' : '';
            $command['run'] = $run['run'] ?? '';
        }
        if ($error !== '') {
            $this->DebugEvent('FEHLER', 'Einzelauftrag nicht gesendet.', ['Grund' => match ($error) {
                'execution_unavailable' => 'Die Erweiterung hat die Einzelsteuerung nicht bestätigt. Erweiterung aktualisieren und Status abfragen.',
                'execution_draft'       => 'Entwurf und gespeicherter Stand unterscheiden sich. Änderungen erst prüfen und speichern oder den gespeicherten Stand laden.',
                'activation_expired'    => 'Die einmalige Startfreigabe fehlt oder ist abgelaufen. Status erneut abfragen.',
                default                 => 'Der angezeigte Ablauf ist beendet oder inzwischen neu gestartet. Status aktualisieren und erneut auswählen.'
            }]);
            $this->FinishExtensionWorkflow($state, 'error', $error);
            return null;
        }
        $this->DebugEvent(
            'SENDEN',
            $workflow['kind'] === 'run_once'
            ? 'Einmaligen Start für genau eine gespeicherte Automation angefordert; Bedingungen gelten, Automatikbetrieb bleibt unverändert.'
            : 'Stopp für genau den bestätigten Regellauf angefordert; andere Automationen bleiben unverändert.',
            ['Regelbezug' => substr(hash('sha256', $rule), 0, 16), 'Revision' => $metadata['storedRevision']]
        );
        return $this->ExtensionFrame($state, $workflow['kind'], $command);
    }

    private function RunStateText(string $state): string
    {
        return $this->Translate(match ($state) {
            'running' => 'Running', 'completed' => 'Completed; device effects are not confirmed.',
            'skipped' => 'Not executed: a condition is not met or its value is unknown.',
            'failed'  => 'Failed; the remaining actions were cancelled.', 'stopped' => 'Stopped; already sent commands cannot be undone.',
            default   => 'No run reported for this automation in the current Z2M session.'
        });
    }

    private function RuleExecutionFields(array $state): array
    {
        $options = [['caption' => $this->Translate('Choose a saved automation'), 'value' => '']];
        $draft = $this->ReadEditorDraft();
        foreach (($draft['scope'] ?? '') === $state['scope'] ? ($draft['configuration']['automations'] ?? []) : [] as $rule) {
            $options[] = ['caption' => $rule['name'], 'value' => $rule['id']];
        }
        $selected = $this->ReadAttributeString('ExecutionRule');
        if (!in_array($selected, array_column($options, 'value'), true)) {
            $selected = '';
        }
        $confirmed = $state['mqttConnected'] && $state['bridgeOnline'] && $state['extensionConfirmed']
            && ($state['extension']['executionVersion'] ?? 0) === 1;
        $snapshot = $state['execution'] ?? null;
        $current = $confirmed && ($snapshot['session'] ?? '') === $state['extension']['session']
            && $snapshot['checksum'] === $state['extension']['checksum'];
        $run = $current ? ($snapshot['runs'][$selected] ?? null) : null;
        $info = !$confirmed ? $this->Translate('Request the extension status. Update the extension if individual controls remain unavailable.')
            : (!$this->SavedExecutionDraft($state) ? $this->Translate('Load the saved automations or validate and store your changes before a single run.')
                : $this->RunStateText($run['state'] ?? ''));
        if ($run !== null) {
            $info = $this->RunStateText($run['state']);
            if ($run['step'] > 0) {
                $info .= ' ' . sprintf($this->Translate('Action step: %d.'), $run['step']);
            }
            if ($run['error'] !== '') {
                $info .= ' ' . $this->Translate(ExtensionContract::ErrorText($run['error']));
            }
        }
        $this->SetTimerInterval('ExecutionRefresh', $confirmed && ($run['state'] ?? '') === 'running' ? 5000 : 0);
        return ['ExecutionRule' => ['options' => $options, 'value' => $selected], 'SingleRunInfo' => ['caption' => $info],
            'RunOnceButton'     => ['enabled' => $confirmed && $selected !== '' && $this->SavedExecutionDraft($state) && $state['workflow'] === null && ($run['state'] ?? '') !== 'running'],
            'StopRuleButton'    => ['enabled' => $confirmed && ($run['state'] ?? '') === 'running']];
    }

    private function FillRuleExecutionForm(array &$form, array $state): void
    {
        $updates = $this->RuleExecutionFields($state);
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            $field = array_replace($field, $updates[$field['name'] ?? ''] ?? []);
        }
        unset($field);
    }

    private function UpdateRuleExecutionForm(array $state): void
    {
        foreach ($this->RuleExecutionFields($state) as $name => $fields) {
            foreach ($fields as $key => $value) {
                $this->UpdateFormField($name, $key, is_array($value) ? json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : $value);
            }
        }
    }
}
