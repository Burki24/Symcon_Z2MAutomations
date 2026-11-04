<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/SystemOwnership.php';
require_once __DIR__ . '/../libs/ConnectionOwnership.php';

require_once __DIR__ . '/../libs/helper/DataFlowHelper.php';
require_once __DIR__ . '/../libs/helper/ParentConnectionHelper.php';
require_once __DIR__ . '/../libs/helper/VariablePresentationHelper.php';
require_once __DIR__ . '/../libs/helper/ConfigurationFormHelper.php';
require_once __DIR__ . '/../libs/helper/DebugFlowHelper.php';
require_once __DIR__ . '/../libs/Z2MProtocol.php';
require_once __DIR__ . '/../libs/SystemContract.php';
require_once __DIR__ . '/../libs/ExtensionContract.php';
require_once __DIR__ . '/../libs/MQTTTransport.php';
require_once __DIR__ . '/../libs/ExtensionTransport.php';
require_once __DIR__ . '/../libs/DeviceCatalog.php';
require_once __DIR__ . '/../libs/RuntimeDebug.php';
require_once __DIR__ . '/../libs/ConnectionWorkflow.php';

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\Z2MProtocol;

class Z2MConnection extends IPSModuleStrict
{
    use Z2MAutomations\SystemOwnership;
    use Z2MAutomations\ConnectionOwnership;
    use Burki24\SymconModuleHelper\DataFlowHelper;
    use Burki24\SymconModuleHelper\ParentConnectionHelper;
    use Burki24\SymconModuleHelper\VariablePresentationHelper;
    use Burki24\SymconModuleHelper\ConfigurationFormHelper;
    use Burki24\SymconModuleHelper\DebugFlowHelper;
    use Z2MAutomations\MQTTTransport;
    use Z2MAutomations\ExtensionTransport;
    use Z2MAutomations\DeviceCatalog;
    use Z2MAutomations\RuntimeDebug;
    use Z2MAutomations\ConnectionWorkflow;

    public function Create(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::Create();
            $this->RegisterAttributeString('SystemOwnership', '');
            $this->SetBuffer('ModulePropertiesReady', '');
            $this->SetReceiveDataFilter(Z2MProtocol::BLOCK_RECEIVE_FILTER);
            $identity = bin2hex(random_bytes(16));
            $systemID = substr($identity, 0, 8) . '-' . substr($identity, 8, 4) . '-4' . substr($identity, 13, 3)
                . '-8' . substr($identity, 17, 3) . '-' . substr($identity, 20);
            $this->RegisterPropertyString('SystemID', $systemID);
            $this->RegisterPropertyInteger('Generation', 1);
            $this->RegisterPropertyBoolean('Active', false);
            $this->RegisterPropertyString('BaseTopic', 'zigbee2mqtt');
            $this->RegisterPropertyInteger('RequestTimeout', 10);
            $this->RegisterAttributeInteger('ObservedParentID', 0);
            $this->RegisterAttributeString('ConnectionCatalog', '');
            $this->RegisterTimer('RequestTimeout', 0, 'Z2MC_ExpireRequest($_IPS[\'TARGET\']);');
            $this->RegisterTimer('RuntimeDebugExpiry', 0, 'Z2MC_ExpireRuntimeDebug($_IPS[\'TARGET\']);');
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
            $this->SetBuffer('ModulePropertiesReady', '1');
        });
    }

    public function ApplyChanges(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            parent::ApplyChanges();
            $this->SetReceiveDataFilter($this->ReadPropertyBoolean('Active') && $this->ValidConfiguration()
                ? Z2MProtocol::ReceiveFilter($this->ReadPropertyString('BaseTopic')) : Z2MProtocol::BLOCK_RECEIVE_FILTER);
            $this->WithTransportLock(function (): void
            {
                $this->ObserveParent();
                $this->SaveState($this->RefreshState());
            });
            $this->DebugEvent('AKTION', 'Verbindungseinstellungen übernommen. Es wurden keine Geräte angelegt, Entwürfe übertragen oder Automationen gestartet.');
        });
    }

    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SenderID, $Message): void
        {
            if (($SenderID === $this->InstanceID && in_array($Message, [FM_CONNECT, FM_DISCONNECT], true))
                || ($SenderID > 0 && $SenderID === $this->ReadAttributeInteger('ObservedParentID') && in_array($Message, [IM_CHANGESTATUS, IM_DELETE], true))
                || ($SenderID === 0 && $Message === IPS_KERNELSTARTED)) {
                $this->WithTransportLock(function () use ($Message): void
                {
                    if ($Message === IPS_KERNELSTARTED) {
                        $this->SetBuffer('TransportState', '');
                        $this->ResetRuntimeDebug('Aufzeichnung nach Kernelstart beendet. Bei Bedarf erneut starten.');
                    }
                    $this->ObserveParent();
                    $this->SaveState($this->RefreshState());
                });
            }
        });
    }

    public function GetConfigurationForm(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            $form = $this->LoadConfigurationForm();
            $state = $this->WithTransportLock(function (): array
            {
                $state = $this->RefreshState();
                $this->SaveState($state);
                return $state;
            });
            $values = $this->ConnectionFormValues($state);
            $fill = static function (array &$items) use (&$fill, $values): void
            {
                foreach ($items as &$field) {
                    foreach ($values[$field['name'] ?? ''] ?? [] as $key => $value) $field[$key] = $value;
                    if (isset($field['items'])) $fill($field['items']);
                }
                unset($field);
            };
            $fill($form['actions']);
            return $this->EncodeConfigurationForm($form);
        });
    }

    public function GetConnectionInfo(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            $state = json_decode($this->GetTransportState(), true, 32, JSON_THROW_ON_ERROR);
            return json_encode(['version' => SystemContract::VERSION, 'systemId' => $this->ReadPropertyString('SystemID'),
                'generation'              => $this->ReadPropertyInteger('Generation'), 'mqttParent' => $this->GetParentID(),
                'baseTopic'               => $this->ReadPropertyString('BaseTopic'), 'state' => $state], JSON_THROW_ON_ERROR);
        });
    }

    public function GetDeviceCatalog(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            return $this->WithTransportLock(function (): string
            {
                $state = $this->RefreshState();
                $this->SaveState($state);
                $catalog = $this->CurrentConnectionCatalog($state);
                $this->DebugEvent('AKTION', 'Gespeicherten Gerätekatalog lesend angefordert.', ['AktuellBestätigt' => $catalog !== null]);
                return json_encode(['version' => SystemContract::VERSION, 'systemId' => $this->ReadPropertyString('SystemID'),
                    'generation'              => $this->ReadPropertyInteger('Generation'), 'available' => $catalog !== null,
                    'loadedAt'                => $catalog['loadedAt'] ?? null, 'devices' => $catalog['devices'] ?? [],
                    'groups'                  => $catalog['groups'] ?? [], 'scenes' => $catalog['scenes'] ?? []], JSON_THROW_ON_ERROR);
            });
        });
    }

    private function ValidConfiguration(): bool
    {
        return SystemContract::SystemID($this->ReadPropertyString('SystemID')) && SystemContract::Topic($this->ReadPropertyString('BaseTopic'))
            && $this->ReadPropertyInteger('Generation') >= 1 && $this->ReadPropertyInteger('Generation') <= 9007199254740991
            && $this->ReadPropertyInteger('RequestTimeout') >= 1 && $this->ReadPropertyInteger('RequestTimeout') <= 120;
    }

    private function TransportScope(): string
    {
        return json_encode([$this->GetParentID(), $this->ReadPropertyString('BaseTopic'), $this->ReadPropertyBoolean('Active'),
            $this->ReadPropertyInteger('RequestTimeout'), $this->ReadPropertyString('SystemID'), $this->ReadPropertyInteger('Generation')], JSON_THROW_ON_ERROR);
    }

    private function BeforeTransportSave(array $state): void
    {
        $this->SyncRuntimeDebug($state);
        $this->UpdateRuntimeDebugForm();
    }

    private function TransportParentChanged(int $parent): void
    {
        $this->DebugEvent('AKTION', 'Zugeordnetes MQTT-Gateway geändert; bisherige Antworten werden für diese Verbindung nicht weiterverwendet.', ['Gateway' => $parent]);
    }

    private function SaveTransportDetails(array $state): void
    {
        $ready = $state['extensionConfirmed'] && ($state['extension']['ready'] ?? false);
        $this->SetValue('ExtensionReady', $ready);
        $this->SetValue('StoredRevision', $ready ? $state['extension']['storedRevision'] : 0);
        $this->SetValue('ExecutionActive', $ready && $state['runtimeConfirmed'] && $state['extension']['executing']);
        $this->SetValue('OperationStatus', $this->ConnectionOperationText($state));
        $this->SetValue('LastError', $this->Translate(ExtensionContract::ErrorText($state['lastError'])));
        $this->UpdateExtensionBuildInfo($state);
        foreach ($this->ConnectionFormValues($state) as $field => $values) {
            foreach ($values as $key => $value) $this->UpdateFormField($field, $key, $value);
        }
    }

    private function ConnectionFormValues(array $state): array
    {
        $values = [];
        foreach ($this->ExtensionBuildInfo($state)['fields'] as $name => $caption) $values[$name] = ['caption' => $caption];
        $values['ConnectionOperationInfo'] = ['caption' => $this->ConnectionOperationText($state)];
        $values['ConnectionErrorInfo'] = ['caption' => $this->Translate(ExtensionContract::ErrorText($state['lastError']))];
        $values['ConnectionCatalogInfo'] = ['caption' => $this->Translate($this->CurrentConnectionCatalog($state) === null
            ? 'Device data are not yet confirmed for this connection. Choose Refresh device data.' : 'Device data loaded. Existing drafts and running automations are unchanged.')];
        $values['RuntimeDebugInfo'] = ['caption' => $this->RuntimeDebugText()];
        $values['RuntimeDebugStop'] = ['enabled' => $this->RuntimeDebugLease() !== []];
        return $values;
    }

    private function ConnectionOperationText(array $state): string
    {
        return $this->Translate(match (true) {
            $state['workflow'] !== null                         => 'Waiting for confirmation from Zigbee2MQTT.',
            $state['lastError'] !== ''                          => 'Request unsuccessful. See the explanation below.',
            $state['operation']['status'] === 'catalog_loaded'  => 'Device data loaded successfully.',
            $state['operation']['status'] === 'installed'       => 'Extension installed and confirmed.',
            $state['operation']['status'] === 'status_received' => 'Extension status confirmed.',
            default                                             => 'No request pending.'
        });
    }

    private function CurrentConnectionCatalog(array $state): ?array
    {
        $text = $this->ReadAttributeString('ConnectionCatalog');
        $catalog = $text === '' ? null : json_decode($text, true, 32, JSON_THROW_ON_ERROR);
        return $catalog !== null && $catalog['scope'] === $state['scope'] && $state['mqttConnected'] && $state['bridgeOnline']
            && $state['extensionConfirmed'] && $catalog['session'] === ($state['extension']['session'] ?? null) ? $catalog : null;
    }

    private function TraceModuleCall(string $function, int $line, callable $callback): mixed
    {
        return $this->DebugFlowCall(
            $function,
            $line,
            fn (): mixed => $this->WithOwnershipEntry($function, $callback),
            in_array($function, ['Create', 'ApplyChanges', 'MessageSink', 'ExpireRequest', 'ExpireRuntimeDebug'], true)
        );
    }

    private function DebugEvent(string $event, string $message, array $details = []): void
    {
        $source = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->DebugFlowEvent($source[1]['function'] ?? __FUNCTION__, $source[0]['line'] ?? __LINE__, $event, $message, $details);
    }
}
