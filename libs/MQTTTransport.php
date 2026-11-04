<?php

declare(strict_types=1);

namespace Z2MAutomations;

use InvalidArgumentException;
use JsonException;
use RuntimeException;
use stdClass;
use Throwable;
use UnexpectedValueException;

trait MQTTTransport
{
    public function GetCompatibleParents(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function ()
        {
            return json_encode(['type' => 'connect', 'moduleIDs' => [Z2MProtocol::MQTT_CLIENT, Z2MProtocol::MQTT_SERVER]], JSON_THROW_ON_ERROR);
        });
    }

    public function ReceiveData(string $JSONString): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($JSONString)
        {
            if ($this->GetBuffer('ModulePropertiesReady') !== '1') {
                $this->DebugEvent('AKTION', 'Frühe MQTT-Nachricht während der Modulinitialisierung verworfen; Einstellungen noch nicht vollständig registriert.');
                return '';
            }
            try {
                if (strlen($JSONString) > Z2MProtocol::MAX_PAYLOAD_BYTES * 2 + 8192) {
                    $this->DebugEvent('FEHLER', 'MQTT-Nachricht wegen Übergröße verworfen.');
                    return '';
                }
                $packet = $this->DecodeDataFlowMessage($JSONString, Z2MProtocol::MQTT_RX);
                if (($packet['PacketType'] ?? null) !== 3 || !is_string($packet['Topic'] ?? null)
                    || !is_string($packet['Payload'] ?? null) || !is_bool($packet['Retain'] ?? null)) {
                    $this->DebugEvent('FEHLER', 'MQTT-Umschlag ungültig; PUBLISH mit Topic, HEX-Payload und Retain erwartet.');
                    return '';
                }
                $base = $this->ReadPropertyString('BaseTopic');
                $suffix = substr($packet['Topic'], strlen($base) + 1);
                if (!str_starts_with($packet['Topic'], $base . '/') || !in_array($suffix, Z2MProtocol::TOPICS, true)) {
                    $this->DebugEvent('AKTION', 'MQTT-Nachricht außerhalb der abonnierten Modultopics verworfen.');
                    return '';
                }
                if (in_array($suffix, ['symcon/automations/debug', 'symcon/automations/response/debug'], true)) {
                    if (strlen($packet['Payload']) <= 16384) {
                        $this->ReceiveRuntimeDebug($suffix, Z2MProtocol::DecodePayload($packet['Payload']), $packet['Retain']);
                    }
                    return '';
                }
                $this->DebugEvent('EMPFANG', 'MQTT-PUBLISH empfangen.', ['Basistopic' => $base, 'Untertopic' => $suffix, 'Retain' => $packet['Retain'], 'Nutzdatenbytes' => intdiv(strlen($packet['Payload']), 2)]);
                $payload = Z2MProtocol::DecodePayload($packet['Payload']);
                $next = null;
                $refreshExtension = false;
                $this->WithTransportLock(function () use ($suffix, $payload, $packet, &$next, &$refreshExtension): void
                {
                    $state = $this->RefreshState();
                    if (!$state['mqttConnected']) {
                        $this->DebugEvent('AKTION', 'Empfang ohne aktive MQTT-Anbindung verworfen.');
                        $this->SaveState($state);
                        return;
                    }
                    if ($suffix === 'bridge/state') {
                        $bridge = is_object($payload) ? ($payload->state ?? null) : $payload;
                        if (in_array($bridge, ['online', 'offline'], true)) {
                            $state['bridgeState'] = $bridge;
                            if ($bridge === 'offline' && $state['pending'] !== null) {
                                $this->FinishHealthCheck($state, 'cancelled');
                            }
                            if ($bridge === 'offline') {
                                $state['validation'] = null;
                                $state['extensionConfirmed'] = false;
                                $state['extension'] = null;
                                if ($state['workflow'] !== null) {
                                    $this->FinishExtensionWorkflow($state, 'cancelled', 'cancelled');
                                }
                            }
                        }
                    } elseif ($suffix === 'bridge/info' && $payload instanceof stdClass) {
                        if (!is_string($payload->version ?? null) || $payload->version === '' || strlen($payload->version) > 128) {
                            return;
                        }
                        $state['version'] = $payload->version;
                        $externalJS = $payload->config->advanced->enable_external_js ?? null;
                        $state['externalJS'] = is_bool($externalJS) ? $externalJS : null;
                    } elseif ($suffix === 'bridge/extensions' && is_array($payload)) {
                        $installed = false;
                        foreach ($payload as $extension) {
                            if (!$extension instanceof stdClass || !is_string($extension->name ?? null)) {
                                return;
                            }
                            $installed = $installed || $extension->name === 'z2m-automations.mjs';
                        }
                        $state['extensionInstalled'] = $installed;
                        if (!$installed) {
                            $state['extensionConfirmed'] = false;
                            $state['validation'] = null;
                            $state['extension'] = null;
                            if ($state['workflow'] !== null && $state['workflow']['kind'] !== 'install') {
                                $this->FinishExtensionWorkflow($state, 'cancelled', 'not_ready');
                            }
                        }
                    } elseif ($suffix === 'bridge/response/health_check' && !$packet['Retain'] && $payload instanceof stdClass
                        && $state['pending'] !== null && ($payload->transaction ?? null) === $state['pending']['transaction']) {
                        if (($payload->status ?? null) === 'error') {
                            $this->FinishHealthCheck($state, 'error');
                        } elseif (($payload->status ?? null) === 'ok' && is_bool($payload->data->healthy ?? null)) {
                            $refreshExtension = ($state['pending']['refreshExtension'] ?? false) && $payload->data->healthy && $state['workflow'] === null;
                            $health = $payload->data->healthy ? 'healthy' : 'unhealthy';
                            $state['bridgeState'] = 'online';
                            $this->DebugEvent('AKTION', 'Frische passende Health-Antwort bestätigt die Erreichbarkeit von Zigbee2MQTT.', ['Gesund' => $payload->data->healthy]);
                            $this->FinishHealthCheck($state, $health);
                        }
                    } else {
                        $next = $this->ReceiveExtensionMessage($state, $suffix, $payload, $packet['Retain']);
                    }
                    $this->SaveState($state);
                });
                if ($next !== null) {
                    $this->DispatchExtensionFrame($next);
                } elseif ($refreshExtension) {
                    $this->DebugEvent('AKTION', 'Automatische Health-Prüfung bestätigt; Erweiterungsstatus einmalig abfragen.');
                    $this->StartExtensionWorkflow('status', '', '', false, true);
                }
            } catch (JsonException | InvalidArgumentException | UnexpectedValueException $exception) {
                $this->DebugEvent('FEHLER', 'Ungültige MQTT-Nachricht verworfen.', ['Ausnahmeklasse' => get_class($exception)]);
            }
            return '';
        });
    }

    public function GetTransportState(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function ()
        {
            return $this->WithTransportLock(function (): string
            {
                $state = $this->RefreshState();
                $this->SaveState($state);
                unset($state['scope'], $state['pending'], $state['workflow']);
                unset($state['validationDetails']['names']);
                if ($state['extension'] !== null) {
                    unset($state['extension']['session']);
                }
                if ($state['validation'] !== null) {
                    unset($state['validation']['session'], $state['validation']['previousChecksum']);
                }
                return json_encode($state, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            });
        });
    }

    public function CheckHealth(bool $RefreshExtension = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->RequestHealthCheck($RefreshExtension, false));
    }

    public function CheckConnection(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->RequestHealthCheck(false, true));
    }

    public function ExpireRequest(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            $this->WithTransportLock(function (): void
            {
                $this->SaveState($this->RefreshState());
            });
        });
    }

    protected function Now(): int
    {
        return time();
    }

    private function RequestHealthCheck(bool $RefreshExtension, bool $Notify): string
    {
        $request = $this->WithTransportLock(function () use ($RefreshExtension, $Notify): array
        {
            $state = $this->RefreshState();
            if (!$state['mqttConnected'] || ($RefreshExtension && $state['workflow'] !== null)) {
                $this->SaveState($state);
                if ($Notify) $this->NotifyHealthCheck('not_connected');
                return [];
            }
            if ($state['pending'] !== null) {
                if ($Notify) {
                    $state['pending']['notify'] = true;
                    $this->SaveState($state);
                }
                return ['transaction' => $state['pending']['transaction'], 'send' => false];
            }
            $transaction = bin2hex(random_bytes(16));
            $state['pending'] = ['transaction' => $transaction, 'deadline' => $this->Now() + $this->ReadPropertyInteger('RequestTimeout'), 'notify' => $Notify];
            if ($RefreshExtension) {
                $state['pending']['refreshExtension'] = true;
            }
            $state['transaction'] = $transaction;
            $state['health'] = 'pending';
            $this->SaveState($state);
            return ['transaction' => $transaction, 'send' => true, 'scope' => $state['scope'], 'base' => $this->ReadPropertyString('BaseTopic')];
        });
        if ($request === []) {
            return '';
        }
        if (!$request['send']) {
            return $request['transaction'];
        }
        try {
            $send = $this->WithTransportLock(function () use ($request): bool
            {
                $current = $this->RefreshState();
                $valid = $current['scope'] === $request['scope'] && $current['mqttConnected']
                    && ($current['pending']['transaction'] ?? null) === $request['transaction'];
                if (!$valid) {
                    $this->SaveState($current);
                }
                return $valid;
            });
            if (!$send) {
                return '';
            }
            $this->DebugEvent('SENDEN', 'Health-Anfrage an MQTT-Gateway übergeben; Antwort steht aus.', ['Gateway' => $this->GetParentID(), 'Topic' => $request['base'] . '/bridge/request/health_check', 'Anfragebezug' => substr(hash('sha256', $request['transaction']), 0, 16)]);
            // Publish outside the lock: a response can arrive during the parent call.
            $this->SendDataToParent($this->EncodeDataFlowMessage(Z2MProtocol::MQTT_TX, [
                'PacketType' => 3, 'QualityOfService' => 0, 'Retain' => false,
                'Topic'      => $request['base'] . '/bridge/request/health_check',
                'Payload'    => bin2hex(json_encode(['transaction' => $request['transaction']], JSON_THROW_ON_ERROR))
            ]));
        } catch (Throwable $exception) {
            $this->WithTransportLock(function () use ($request): void
            {
                $state = $this->RefreshState();
                if (($state['pending']['transaction'] ?? null) === $request['transaction']) {
                    $this->FinishHealthCheck($state, 'error');
                }
                $this->SaveState($state);
            });
            $this->DebugEvent('FEHLER', 'Health-Anfrage konnte nicht an das MQTT-Gateway übergeben werden.', ['Gateway' => $this->GetParentID()]);
            return '';
        }
        return $request['transaction'];
    }

    private function FinishHealthCheck(array &$state, string $result): void
    {
        $notify = $state['pending']['notify'] ?? false;
        $state['pending'] = null;
        $state['health'] = $result;
        if ($notify) {
            $this->NotifyHealthCheck($result);
        }
    }

    private function NotifyHealthCheck(string $result): void
    {
        $message = match ($result) {
            'healthy'       => 'Connection checked successfully. Zigbee2MQTT is reachable and reports no problems.',
            'unhealthy'     => 'Zigbee2MQTT responds but reports a problem. Please check its status and logs.',
            'timeout'       => 'Zigbee2MQTT did not respond within the configured waiting time. Please check the MQTT connection and base topic.',
            'cancelled'     => 'The connection check was cancelled because the connection or settings changed. Please check again.',
            'not_connected' => 'The connection check could not be started. Please enable the MQTT connection and check the gateway connection.',
            default         => 'The connection check failed. Please check the MQTT connection, base topic and Zigbee2MQTT status.'
        };
        try {
            $this->UpdateFormField('HealthResultMessage', 'caption', $this->Translate($message));
            $this->UpdateFormField('HealthResultPopup', 'visible', false);
            $this->UpdateFormField('HealthResultPopup', 'visible', true);
            $this->DebugEvent($result === 'healthy' ? 'AKTION' : 'FEHLER', 'Popup-Rückmeldung zur manuellen Verbindungsprüfung an die Konsole übergeben.', ['Erfolgreich' => $result === 'healthy', 'Ergebnis' => $result]);
        } catch (Throwable) {
            $this->DebugEvent('FEHLER', 'Popup-Rückmeldung zur Verbindungsprüfung konnte nicht angezeigt werden. Statusanzeige und Debug beachten.');
        }
    }

    private function ObserveParent(): void
    {
        $previous = $this->ReadAttributeInteger('ObservedParentID');
        $parent = $this->GetParentID();
        if ($parent === $previous) {
            return;
        }
        foreach ([IM_CHANGESTATUS, IM_DELETE] as $message) {
            if ($previous > 0) {
                $this->UnregisterMessage($previous, $message);
            }
            if ($parent > 0) {
                $this->RegisterMessage($parent, $message);
            }
        }
        $this->WriteAttributeInteger('ObservedParentID', $parent);
        $this->TransportParentChanged($parent);
    }

    private function RefreshState(): array
    {
        $parent = $this->GetParentID();
        $scope = $this->TransportScope();
        $connected = $this->ReadPropertyBoolean('Active') && $this->ValidConfiguration() && $this->HasParent()
            && in_array(IPS_GetInstance($parent)['ModuleInfo']['ModuleID'], [Z2MProtocol::MQTT_CLIENT, Z2MProtocol::MQTT_SERVER], true)
            && $this->HasActiveParent();
        $cached = $this->GetBuffer('TransportState');
        $state = $cached === '' ? [] : json_decode($cached, true, 32, JSON_THROW_ON_ERROR);
        if (($state['scope'] ?? null) !== $scope || (($state['mqttConnected'] ?? false) && !$connected)) {
            $cancelled = ($state['pending'] ?? null) !== null;
            $extensionCancelled = ($state['workflow'] ?? null) !== null;
            if ($cancelled) $this->FinishHealthCheck($state, 'cancelled');
            $state = ['scope' => $scope, 'mqttConnected' => false, 'bridgeState' => 'unknown', 'bridgeOnline' => false,
                'version'     => '', 'externalJS' => null, 'extensionInstalled' => false, 'pending' => null,
                'transaction' => '', 'health' => $cancelled ? 'cancelled' : 'idle'] + $this->NewExtensionState();
            if ($extensionCancelled) {
                $state['operation']['status'] = 'cancelled';
                $state['lastError'] = 'cancelled';
            }
        }
        $state['mqttConnected'] = $connected;
        $state['bridgeOnline'] = $connected && $state['bridgeState'] === 'online';
        if ($state['pending'] !== null && $state['pending']['deadline'] <= $this->Now()) {
            $this->FinishHealthCheck($state, 'timeout');
            $this->DebugEvent('FEHLER', 'Zeitlimit der Health-Anfrage ohne passende Antwort erreicht.');
        }
        return $this->RefreshExtensionWorkflow($state);
    }

    private function SaveState(array $state): void
    {
        $state['bridgeOnline'] = $state['mqttConnected'] && $state['bridgeState'] === 'online';
        $this->BeforeTransportSave($state);
        $this->SetBuffer('TransportState', json_encode($state, JSON_THROW_ON_ERROR));
        $this->SetValue('MQTTConnected', $state['mqttConnected']);
        $this->SetValue('BridgeOnline', $state['bridgeOnline']);
        $this->SetValue('Z2MVersion', $state['version']);
        $this->SetValue('ExtensionInstalled', $state['extensionInstalled']);
        $this->SetValue('HealthStatus', $this->Translate(ucfirst($state['health'])));
        $this->SaveTransportDetails($state);
        $deadlines = [];
        foreach ([$state['pending'], $state['workflow']] as $request) {
            if ($request !== null) {
                $deadlines[] = $request['deadline'];
            }
        }
        $this->SetTimerInterval('RequestTimeout', $deadlines === [] ? 0 : max(1, (min($deadlines) - $this->Now()) * 1000));
        $status = !$this->ReadPropertyBoolean('Active') ? 104 : (!$this->ValidConfiguration() ? 200 : (!$state['mqttConnected'] ? 201 : (!$state['bridgeOnline'] ? 202 : 102)));
        $this->SetStatus($status);
        $this->DebugEvent('AKTION', 'Transportstatus und Anzeigevariablen aktualisiert.', ['Gateway' => $this->GetParentID(), 'Aktiviert' => $this->ReadPropertyBoolean('Active'), 'KonfigurationGültig' => $this->ValidConfiguration(), 'Basistopic' => $this->ReadPropertyString('BaseTopic'), 'MQTTVerbunden' => $state['mqttConnected'], 'Z2MZustand' => $state['bridgeState'], 'Gesundheitsprüfung' => $state['health'], 'ExternesJavaScript' => $state['externalJS'], 'ErweiterungInstalliert' => $state['extensionInstalled'], 'Statuscode' => $status]);
    }

    private function WithTransportLock(callable $callback): mixed
    {
        $name = 'Z2MA.Transport.' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($name, 1000)) {
            throw new RuntimeException('MQTT state is busy.');
        }
        try {
            return $callback();
        } finally {
            IPS_SemaphoreLeave($name);
        }
    }
}
