<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;

/** Connection policy only: no draft ownership, storage or execution commands. */
trait ConnectionWorkflow
{
    public function RequestDeviceCatalog(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->StartExtensionWorkflow('catalog'));
    }

    private function StartExtensionWorkflow(string $kind, string $yaml = '', string $code = '', bool $notify = false, bool $automatic = false): string
    {
        $frame = $this->WithTransportLock(function () use ($kind, $code, $notify): array
        {
            $state = $this->RefreshState();
            if (!in_array($kind, ['status', 'catalog', 'install'], true)) {
                $this->DebugEvent('FEHLER', 'Dieser Verbindungsbaustein unterstützt noch keine Entwurfs- oder Ausführungsaufträge.');
                return [];
            }
            if ($state['workflow'] !== null) {
                $this->DebugEvent('AKTION', 'Auftrag nicht erneut gesendet: Für dieses Z2M-System steht noch eine Antwort aus. Andere Systeme bleiben unabhängig.');
                if ($notify) $this->NotifyExtensionInstallation('busy');
                return [];
            }
            $error = match (true) {
                !$state['mqttConnected']                              => 'not_connected',
                !$state['bridgeOnline']                               => 'bridge_offline',
                $kind === 'install' && $state['externalJS'] === false => 'external_js_disabled',
                $kind === 'install' && $code === ''                   => 'artifact_unavailable',
                default                                               => ''
            };
            if ($error !== '') {
                $state['operation'] = ['kind' => $kind, 'status' => 'error', 'transaction' => ''];
                $state['lastError'] = $error;
                if ($notify) $this->NotifyExtensionInstallation($error);
                $this->DebugEvent('FEHLER', 'Verbindungsauftrag nicht gestartet.', ['Auftrag' => $kind, 'Grund' => $error]);
                $this->SaveState($state);
                return [];
            }
            $id = bin2hex(random_bytes(16));
            $state['workflow'] = ['id' => $id, 'kind' => $kind, 'notify' => $notify,
                'previousSession'      => $state['extension']['session'] ?? null, 'target' => null];
            $state['operation'] = ['kind' => $kind, 'status' => 'pending', 'transaction' => $id];
            $state['lastError'] = '';
            if ($kind === 'install') {
                $state['extensionConfirmed'] = false;
                $state['runtimeConfirmed'] = false;
            }
            $frame = $kind === 'install'
                ? $this->ExtensionFrame($state, 'install', ['name' => ExtensionContract::NAME, 'code' => $code], $id)
                : $this->ExtensionFrame($state, 'status', [], $id);
            $this->DebugEvent('AKTION', 'Auftrag für genau dieses Z2M-System vorbereitet.', ['Auftrag' => $kind, 'Systembezug' => $this->ReadPropertyString('SystemID')]);
            $this->SaveState($state);
            return $frame;
        });
        return $frame !== [] && $this->DispatchExtensionFrame($frame) ? $frame['id'] : '';
    }

    private function ReceiveExtensionMessage(array &$state, string $suffix, mixed $payload, bool $retained): ?array
    {
        if ($suffix === ExtensionContract::PREFIX . 'status') {
            $metadata = ExtensionContract::Status($payload);
            if ($metadata !== null) $this->ObserveExtensionStatus($state, $metadata);
            return null;
        }
        if (!$this->MatchExtensionResponse($state, $suffix, $payload, $retained)) return null;
        $workflow = $state['workflow'];
        if (($payload->status ?? null) !== 'ok' || !($payload->data ?? null) instanceof stdClass) {
            $code = $workflow['wire'] === 'install' ? 'installation_failed' : ($payload->error->code ?? 'invalid_response');
            if (!is_string($code) || !in_array($code, array_merge(ExtensionContract::ERROR_CODES, ['installation_failed', 'invalid_response']), true)) $code = 'invalid_response';
            $state['extensionConfirmed'] = false;
            $this->FinishExtensionWorkflow($state, 'error', $code);
            return null;
        }
        if ($workflow['wire'] === 'install') {
            $state['extensionInstalled'] = true;
            return $this->ExtensionFrame($state, 'status');
        }
        $metadata = ExtensionContract::Status($payload->data);
        if ($metadata === null || !$metadata['ready']) {
            $state['extensionConfirmed'] = false;
            $this->FinishExtensionWorkflow($state, 'error', $metadata === null ? 'invalid_response' : 'not_ready');
            return null;
        }
        if ($workflow['wire'] === 'status') {
            if ($workflow['kind'] === 'install' && $metadata['session'] === $workflow['previousSession']) {
                $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
                return null;
            }
            $state['extension'] = $metadata;
            $state['extensionConfirmed'] = true;
            $state['runtimeConfirmed'] = $metadata['runtimeVersion'] === 1;
            if ($workflow['kind'] === 'catalog') {
                if ($metadata['catalogVersion'] !== 1) {
                    $this->FinishExtensionWorkflow($state, 'error', 'catalog_unavailable');
                    return null;
                }
                $state['workflow']['target'] = $metadata;
                return $this->ExtensionFrame($state, 'catalog');
            }
            $this->FinishExtensionWorkflow($state, $workflow['kind'] === 'install' ? 'installed' : 'status_received');
            return null;
        }
        $catalog = $metadata === $workflow['target'] && ($payload->data->catalog_version ?? null) === 1
            ? $this->ReadDeviceCatalog($payload->data) : null;
        if ($catalog === null) {
            $state['extensionConfirmed'] = false;
            $this->FinishExtensionWorkflow($state, 'error', 'invalid_response');
            return null;
        }
        [$devices, $groups, $scenes] = $catalog;
        $this->WriteAttributeString('ConnectionCatalog', json_encode(['scope' => $state['scope'], 'session' => $metadata['session'],
            'loadedAt'                                                        => $this->Now(), 'devices' => $devices, 'groups' => $groups, 'scenes' => $scenes], JSON_THROW_ON_ERROR));
        $this->DebugEvent(
            'EMPFANG',
            'Geräte, Kanäle, Gruppen und Szenen für dieses System übernommen. Entwürfe und laufende Automationen bleiben unverändert.',
            ['Geräte' => count($devices), 'Gruppen' => count($groups), 'Szenen' => count($scenes)]
        );
        $this->FinishExtensionWorkflow($state, 'catalog_loaded');
        return null;
    }

    private function FinishExtensionWorkflow(array &$state, string $status, string $error = ''): void
    {
        $this->DebugEvent(
            $error === '' ? 'AKTION' : 'FEHLER',
            'Verbindungsauftrag abgeschlossen.',
            ['Auftrag' => $state['workflow']['kind'] ?? 'none', 'Ergebnis' => $status, 'Grund' => $error]
        );
        if (($state['workflow']['kind'] ?? '') === 'install' && ($state['workflow']['notify'] ?? false)) $this->NotifyExtensionInstallation($error);
        $state['operation']['status'] = $status;
        $state['lastError'] = $error;
        $state['workflow'] = null;
    }

}
