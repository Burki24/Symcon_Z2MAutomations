<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/ParentConnectionHelper.php';
require_once __DIR__ . '/../libs/helper/VariablePresentationHelper.php';
require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DebugFlowHelper.php';
require_once __DIR__ . '/../libs/helper/VisualizationAssetHelper.php';
require_once __DIR__ . '/../libs/helper/HttpResponseHelper.php';
require_once __DIR__ . '/../libs/AutomationDesigner.php';
require_once __DIR__ . '/../libs/SystemMigrationInspection.php';
require_once __DIR__ . '/../libs/SystemWorkspace.php';
require_once __DIR__ . '/../libs/WorkspaceEditorContext.php';
require_once __DIR__ . '/../libs/WorkspaceForm.php';
require_once __DIR__ . '/../libs/WorkspaceDesigner.php';
require_once __DIR__ . '/../libs/WorkspaceTransfer.php';
require_once __DIR__ . '/../libs/WorkspaceSetup.php';
require_once __DIR__ . '/../libs/WorkspaceRuntime.php';
require_once __DIR__ . '/../libs/WorkspaceCatalogRefresh.php';
require_once __DIR__ . '/../libs/WorkspaceRuntimeForm.php';
require_once __DIR__ . '/../libs/SystemMigration.php';
require_once __DIR__ . '/../libs/SystemOwnership.php';
require_once __DIR__ . '/../libs/MQTTTransport.php';
require_once __DIR__ . '/../libs/ModuleDiagnostics.php';
require_once __DIR__ . '/../libs/RuntimeDebug.php';
require_once __DIR__ . '/../libs/RuleExecution.php';
require_once __DIR__ . '/../libs/ReusableSequences.php';
require_once __DIR__ . '/../libs/EditorWork.php';
require_once __DIR__ . '/../libs/EditorWorkForm.php';
require_once __DIR__ . '/../libs/SequencePlan.php';
require_once __DIR__ . '/../libs/ExecutionContract.php';
require_once __DIR__ . '/../libs/AutomaticStatusRefresh.php';
require_once __DIR__ . '/../libs/EditorGroupTargets.php';
require_once __DIR__ . '/../libs/EditorScenes.php';
require_once __DIR__ . '/../libs/Z2MInstanceDiscovery.php';
require_once __DIR__ . '/../libs/SymconEventImport.php';
require_once __DIR__ . '/../libs/WorkspaceEventImport.php';
require_once __DIR__ . '/../libs/Z2MProtocol.php';
require_once __DIR__ . '/../libs/ExtensionContract.php';
require_once __DIR__ . '/../libs/ValidationFeedback.php';
require_once __DIR__ . '/../libs/ExtensionIntegration.php';
require_once __DIR__ . '/../libs/AutomationEditor.php';
require_once __DIR__ . '/../libs/AutomationOverview.php';
require_once __DIR__ . '/../libs/AutomationEditorManagement.php';
require_once __DIR__ . '/../libs/AutomationEditorSchedule.php';
require_once __DIR__ . '/../libs/AutomationEditorCalendar.php';
require_once __DIR__ . '/../libs/AutomationEditorSolar.php';
require_once __DIR__ . '/../libs/AutomationEditorActions.php';
require_once __DIR__ . '/../libs/AutomationEditorAreas.php';
require_once __DIR__ . '/../libs/AutomationEditorTriggers.php';
require_once __DIR__ . '/../libs/AutomationEditorConditions.php';
require_once __DIR__ . '/../libs/EditorSymconVariables.php';
require_once __DIR__ . '/../libs/EditorPropertyCatalog.php';
require_once __DIR__ . '/../libs/EditorItemForms.php';
require_once __DIR__ . '/../libs/EditorCommands.php';
require_once __DIR__ . '/../libs/RemoteAutomationTargets.php';

use Z2MAutomations\Z2MProtocol;

class Z2MAutomations extends IPSModuleStrict
{
    use Burki24\SymconModuleHelper\DataFlowHelper;
    use Burki24\SymconModuleHelper\ParentConnectionHelper;
    use Burki24\SymconModuleHelper\VariablePresentationHelper;
    use Burki24\SymconModuleHelper\ConfigurationFormHelper;
    use Burki24\SymconModuleHelper\DebugFlowHelper;
    use Burki24\SymconModuleHelper\VisualizationAssetHelper;
    use Burki24\SymconModuleHelper\HttpResponseHelper;
    use Z2MAutomations\AutomationDesigner;
    use Z2MAutomations\SystemMigrationInspection;
    use Z2MAutomations\SystemWorkspace;
    use Z2MAutomations\WorkspaceEditorContext;
    use Z2MAutomations\WorkspaceForm;
    use Z2MAutomations\WorkspaceDesigner;
    use Z2MAutomations\WorkspaceTransfer;
    use Z2MAutomations\WorkspaceSetup;
    use Z2MAutomations\WorkspaceRuntime;
    use Z2MAutomations\WorkspaceCatalogRefresh;
    use Z2MAutomations\WorkspaceRuntimeForm;
    use Z2MAutomations\SystemMigration;
    use Z2MAutomations\SystemOwnership;
    use Z2MAutomations\MQTTTransport;
    use Z2MAutomations\ModuleDiagnostics;
    use Z2MAutomations\RuntimeDebug;
    use Z2MAutomations\RuleExecution;
    use Z2MAutomations\ReusableSequences;
    use Z2MAutomations\EditorWork;
    use Z2MAutomations\EditorWorkForm;
    use Z2MAutomations\AutomaticStatusRefresh;
    use Z2MAutomations\EditorGroupTargets;
    use Z2MAutomations\EditorScenes;
    use Z2MAutomations\Z2MInstanceDiscovery;
    use Z2MAutomations\SymconEventImport;
    use Z2MAutomations\WorkspaceEventImport;
    use Z2MAutomations\ExtensionIntegration;
    use Z2MAutomations\AutomationEditor;
    use Z2MAutomations\AutomationOverview;
    use Z2MAutomations\AutomationEditorManagement;
    use Z2MAutomations\AutomationEditorSchedule;
    use Z2MAutomations\AutomationEditorCalendar;
    use Z2MAutomations\AutomationEditorSolar;
    use Z2MAutomations\AutomationEditorActions;
    use Z2MAutomations\AutomationEditorAreas;
    use Z2MAutomations\AutomationEditorTriggers;
    use Z2MAutomations\AutomationEditorConditions;
    use Z2MAutomations\EditorSymconVariables;
    use Z2MAutomations\EditorPropertyCatalog;
    use Z2MAutomations\EditorItemForms;
    use Z2MAutomations\EditorCommands;
    use Z2MAutomations\RemoteAutomationTargets;

    public function Create(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::Create();
            $this->SetBuffer('ModulePropertiesReady', '');
            $this->SetReceiveDataFilter(Z2MProtocol::BLOCK_RECEIVE_FILTER);
            $this->RegisterPropertyBoolean('Active', false);
            $this->RegisterPropertyBoolean('ResumeAfterRestart', true);
            $this->RegisterPropertyString('BaseTopic', 'zigbee2mqtt');
            $this->RegisterPropertyInteger('PeerModuleID', 0);
            $this->RegisterPropertyInteger('RequestTimeout', 10);
            $this->RegisterAttributeInteger('ObservedParentID', 0);
            $this->RegisterAttributeString('SystemWorkspace', '');
            $this->RegisterAttributeString('WorkspaceTransfers', '');
            $this->RegisterAttributeString('WorkspaceSetup', '');
            $this->RegisterAttributeString('WorkspaceRuntime', '');
            $this->RegisterAttributeString('SystemOwnership', '');
            $this->RegisterAttributeString('MigrationSourceWork', '');
            $this->RegisterAttributeString('YamlDraft', '');
            $this->RegisterAttributeString('EditorSnapshot', '');
            $this->RegisterAttributeString('EditorDraft', '');
            $this->RegisterAttributeString('EditorSource', '');
            $this->RegisterAttributeString('EditorTarget', '');
            $this->RegisterAttributeString('EditorSelection', '');
            $this->RegisterAttributeString('ExecutionRule', '');
            $this->RegisterAttributeString('SequenceSelection', '');
            $this->RegisterAttributeString('EditorCondition', '');
            $this->RegisterAttributeString('EditorConditionProperty', '');
            $this->RegisterAttributeString('EditorModes', '');
            $this->RegisterAttributeBoolean('ShowYaml', false);
            $this->SetBuffer('DesignerSessions', '');
            if (!$this->RegisterHook($this->DesignerHookName())) {
                $this->DebugEvent('FEHLER', 'Lesender Designerzugang konnte nicht registriert werden. Das native Formular bleibt verfügbar.');
            }
            $this->RegisterTimer('RuntimeDebugExpiry', 0, 'Z2MA_ExpireRuntimeDebug($_IPS[\'TARGET\']);');
            $this->RegisterTimer('ExecutionRefresh', 0, 'Z2MA_RefreshExecutionStatus($_IPS[\'TARGET\']);');
            $this->RegisterTimer('StatusRefresh', 0, 'Z2MA_RefreshStatus($_IPS[\'TARGET\']);');
            $this->RegisterTimer('WorkspaceTransferRefresh', 0, 'Z2MA_RefreshWorkspaceTransfers($_IPS[\'TARGET\']);');
            $this->RegisterTimer('WorkspaceSetupRefresh', 0, 'Z2MA_RefreshWorkspaceSetup($_IPS[\'TARGET\']);');
            $this->RegisterTimer('WorkspaceRuntimeRefresh', 0, 'Z2MA_RefreshWorkspaceRuntime($_IPS[\'TARGET\']);');
            $this->RegisterTimer('WorkspaceCatalogRefresh', 0, 'Z2MA_RefreshWorkspaceDeviceCatalogs($_IPS[\'TARGET\']);');
            $this->RegisterVariableString('RuntimeSystem', 'Gewähltes Z2M-System für Betriebssteuerung', $this->TextPresentation(), 230);
            $this->RegisterVariableString('RuntimeStatus', 'Betriebssteuerung und Einzelstart', $this->TextPresentation(), 240);
            $this->RegisterVariableString('TransferSystem', 'Gewähltes Z2M-System für Prüfung und Übertragung', $this->TextPresentation(), 200);
            $this->RegisterVariableString('TransferStatus', 'Prüfung und Übertragung', $this->TextPresentation(), 210);
            $this->RegisterVariableString('TransferError', 'Einzelheiten zur Entwurfsprüfung', $this->TextPresentation(), 220);
            $this->RegisterTimer('RequestTimeout', 0, 'Z2MA_ExpireRequest($_IPS[\'TARGET\']);');
            $this->RegisterMessage($this->InstanceID, FM_CONNECT);
            $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->RegisterVariableBoolean('MQTTConnected', $this->Translate('MQTT connected'), $this->BooleanPresentation($this->Translate('Connected'), $this->Translate('Disconnected')), 10);
            $this->RegisterVariableBoolean('BridgeOnline', $this->Translate('Zigbee2MQTT online'), $this->BooleanPresentation($this->Translate('Online'), $this->Translate('Offline or unknown')), 20);
            $this->RegisterVariableString('Z2MVersion', $this->Translate('Zigbee2MQTT version'), $this->TextPresentation(), 30);
            $this->RegisterVariableBoolean('ExtensionInstalled', $this->Translate('Automation extension installed'), $this->BooleanPresentation($this->Translate('Installed'), $this->Translate('Not detected')), 40);
            $this->RegisterVariableString('HealthStatus', $this->Translate('Health check'), $this->TextPresentation(), 50);
            $this->RegisterVariableBoolean('ExtensionReady', $this->Translate('Extension receiver confirmed'), $this->BooleanPresentation($this->Translate('Ready'), $this->Translate('Not confirmed')), 60);
            $this->RegisterVariableInteger('StoredRevision', $this->Translate('Stored YAML revision'), $this->ValuePresentation(), 70);
            $this->RegisterVariableString('OperationStatus', $this->Translate('Extension operation'), $this->TextPresentation(), 80);
            $this->RegisterVariableString('LastError', $this->Translate('Last extension error'), $this->TextPresentation(), 90);
            $this->RegisterVariableBoolean('ExecutionActive', $this->Translate('Execution confirmed active'), $this->BooleanPresentation($this->Translate('Active'), $this->Translate('Stopped or unknown')), 100);
            $this->RegisterVariableInteger('ActiveRevision', $this->Translate('Active automation revision'), $this->ValuePresentation(), 110);
            $this->RegisterVariableInteger('EnabledAutomations', $this->Translate('Enabled automations'), $this->ValuePresentation(), 120);
            $this->RegisterVariableString('ExecutionStatus', $this->Translate('Automation execution'), $this->TextPresentation(), 130);
            $this->RegisterVariableString('RuntimeError', $this->Translate('Last execution error'), $this->TextPresentation(), 140);
            $this->RegisterVariableBoolean('MeshReady', $this->Translate('Incoming actions from second Z2M enabled'), $this->BooleanPresentation($this->Translate('Active'), $this->Translate('Stopped or unknown')), 150);
            $this->SetBuffer('ModulePropertiesReady', '1');
        });
    }

    public function ApplyChanges(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::ApplyChanges();
            $this->ResetWorkspaceCatalogRefresh();
            $this->SetBuffer('DesignerSessions', '');
            $this->SetBuffer('GatewayTopicReview', '');
            $this->SetReceiveDataFilter($this->ReadPropertyBoolean('Active') ? Z2MProtocol::ReceiveFilter($this->ReadPropertyString('BaseTopic')) : Z2MProtocol::BLOCK_RECEIVE_FILTER);
            $this->WithTransportLock(function (): void
            {
                $this->ResetRuntimeDebug('Aufzeichnung nach Übernahme der Einstellungen beendet. Bei Bedarf erneut starten.');
                $this->ObserveParent();
                $this->SaveState($this->RefreshState());
            });
            $this->RefreshGatewayTopicFields();
            $this->QueueAutomaticStatusRefresh('Einstellungen oder Modulupdate');
        });
    }

    public function GetCompatibleParents(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            $this->DebugEvent('AKTION', 'Automationszentrale ohne eigene MQTT-Schnittstelle anlegen. Die Z2M-Systeme werden später über ihre Verbindungsinstanzen zugeordnet. Vorhandene Verbindungen bleiben unverändert.');
            return '{}';
        });
    }

    public function GetConfigurationForm(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function ()
        {
            try {
                if ($this->WorkspaceMode() || ($this->ReadAttributeString('EditorDraft') === '' && $this->ReadAttributeString('YamlDraft') === '' && $this->GetParentID() === 0)) return $this->WorkspaceConfigurationForm();
            } catch (\Throwable $exception) {
                $failure = json_decode($this->WorkspaceFailure($exception), true, 16, JSON_THROW_ON_ERROR);
                return $this->EncodeConfigurationForm(['elements' => [['type' => 'Label', 'caption' => $failure['message']]], 'actions' => []]);
            }
            $form = $this->LoadConfigurationForm();
            $state = $this->WithTransportLock(function (): array
            {
                $state = $this->RefreshState();
                $this->SaveState($state);
                return $state;
            });
            $buildInfo = $this->ExtensionBuildInfo($state);
            foreach ($this->EditorFormFields($form['actions']) as &$field) {
                if (isset($buildInfo['fields'][$field['name'] ?? ''])) {
                    $field['caption'] = $buildInfo['fields'][$field['name']];
                } elseif (($field['name'] ?? '') === 'YamlInput') {
                    $field['value'] = $this->ReadAttributeString('YamlDraft');
                    $field['visible'] = true;
                } elseif (($field['name'] ?? '') === 'RestartInfo') {
                    $field['caption'] = $this->RestartText($state);
                } elseif (($field['name'] ?? '') === 'ExecutionInfo') {
                    $field['caption'] = $this->ExecutionText($state);
                } elseif (($field['name'] ?? '') === 'RuntimeErrorInfo') {
                    $field['caption'] = $this->Translate(\Z2MAutomations\ExtensionContract::ErrorText($state['extension']['runtimeError'] ?? ''));
                } elseif (($field['name'] ?? '') === 'ActivateButton') {
                    $field['enabled'] = $state['runtimeConfirmed'] && ($state['extension']['storedRevision'] ?? 0) > 0 && $state['workflow'] === null;
                } elseif (($field['name'] ?? '') === 'StopButton') {
                    $field['enabled'] = $state['mqttConnected'];
                } elseif (($field['name'] ?? '') === 'ValidationInfo') {
                    $field['caption'] = $this->ValidationPreview($state);
                } elseif (($field['name'] ?? '') === 'ErrorInfo') {
                    $field['caption'] = $this->ExtensionErrorText($state);
                } elseif (($field['name'] ?? '') === 'OperationInfo') {
                    $field['caption'] = $this->OperationText($state);
                }
            }
            unset($field);
            $this->FillEditorForm($form, $state);
            $this->PrepareEditorItemForms($form);
            $this->FillEventImportForm($form);
            $this->FillGatewayTopics($form);
            $this->FillRuntimeDebugForm($form);
            $this->FillRuleExecutionForm($form, $state);
            $this->FillReusableSequenceForm($form);
            $this->FillEditorWorkForm($form, $state);
            $this->QueueAutomaticStatusRefresh('Formular geöffnet');
            return $this->EncodeConfigurationForm($form);
        });
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($TimeStamp, $SenderID, $Message, $Data): void
        {
            if (($SenderID === $this->InstanceID && in_array($Message, [FM_CONNECT, FM_DISCONNECT], true))
                || ($SenderID === $this->ReadAttributeInteger('ObservedParentID') && $SenderID > 0 && in_array($Message, [IM_CHANGESTATUS, IM_DELETE], true))
                || ($SenderID === 0 && $Message === IPS_KERNELSTARTED)) {
                $this->WithTransportLock(function () use ($Message): void
                {
                    if ($Message === IPS_KERNELSTARTED) {
                        $this->SetBuffer('TransportState', '');
                    }
                    $this->ObserveParent();
                    $this->SaveState($this->RefreshState());
                });
                if ($SenderID === $this->InstanceID && in_array($Message, [FM_CONNECT, FM_DISCONNECT], true)) {
                    $this->RefreshGatewayTopicFields();
                }
                $this->QueueAutomaticStatusRefresh('Verbindung oder Kernelstart');
            }
        });
    }

    protected function VisualizationAssetDirectory(): string
    {
        return __DIR__ . '/visualization';
    }

    private function ValidConfiguration(): bool
    {
        return Z2MProtocol::IsValidBaseTopic($this->ReadPropertyString('BaseTopic'))
            && ($this->ReadPropertyInteger('PeerModuleID') === 0 || ($this->PeerContext() !== null
                && strlen($this->ReadPropertyString('BaseTopic')) <= 128
                && preg_match('/\A[A-Za-z0-9_-]+(?:\/[A-Za-z0-9_-]+)*\z/D', $this->ReadPropertyString('BaseTopic')) === 1))
            && $this->ReadPropertyInteger('RequestTimeout') >= 1 && $this->ReadPropertyInteger('RequestTimeout') <= 120;
    }

    private function BeforeTransportSave(array $state): void
    {
        $this->SyncRuntimeDebug($state);
        $this->UpdateRuntimeDebugForm();
    }

    private function SaveTransportDetails(array $state): void
    {
        $this->SaveExtensionState($state);
        $this->UpdateRuleExecutionForm($state);
        $this->UpdateEditorWorkStatus($state);
    }

    private function TransportParentChanged(int $parent): void
    {
        $this->SetBuffer('DesignerSessions', '');
        $this->SetBuffer('GatewayTopicReview', json_encode(['parent' => $parent, 'base' => $this->ReadPropertyString('BaseTopic')], JSON_THROW_ON_ERROR));
    }

    private function TransportScope(): string
    {
        if ($this->workspaceEditorContext !== null) return $this->workspaceEditorContext['scope'];
        $context = [$this->GetParentID(), $this->ReadPropertyString('BaseTopic'), $this->ReadPropertyBoolean('Active'), $this->ReadPropertyInteger('RequestTimeout')];
        if ($this->ReadPropertyInteger('PeerModuleID') !== 0) {
            $context[] = $this->PeerContext();
        }
        return json_encode($context, JSON_THROW_ON_ERROR);
    }

}
