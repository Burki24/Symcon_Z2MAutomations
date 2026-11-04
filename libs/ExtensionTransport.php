<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;
use Throwable;

trait ExtensionTransport
{
    public function InstallExtension(bool $Notify = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Notify)
        {
            $code = @file_get_contents(__DIR__ . '/z2m/' . ExtensionContract::NAME, false, null, 0, Z2MProtocol::MAX_PAYLOAD_BYTES + 1);
            $valid = is_string($code) && $code !== '' && strlen($code) <= Z2MProtocol::MAX_PAYLOAD_BYTES && preg_match('//u', $code) === 1;
            return $this->StartExtensionWorkflow('install', '', $valid ? $code : '', $Notify);
        });
    }

    public function RequestExtensionStatus(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function ()
        {
            return $this->StartExtensionWorkflow('status');
        });
    }
    private function NewExtensionState(): array
    {
        return ['extension' => null, 'extensionConfirmed' => false, 'runtimeConfirmed' => false, 'workflow' => null, 'validation' => null,
            'operation'     => ['kind' => 'none', 'status' => 'idle', 'transaction' => ''], 'lastError' => '', 'validationDetails' => null, 'execution' => null];
    }

    private function ExtensionFrame(array &$state, string $wire, array $payload = [], ?string $transaction = null): array
    {
        $transaction ??= bin2hex(random_bytes(16));
        $state['workflow']['wire'] = $wire;
        $state['workflow']['transaction'] = $transaction;
        $state['workflow']['deadline'] = $this->Now() + $this->ReadPropertyInteger('RequestTimeout');
        if ($wire === 'store') {
            $state['runtimeConfirmed'] = false;
        }
        $payload = ['transaction' => $transaction] + $payload;
        if ($wire !== 'install') {
            $payload = ['protocol_version' => 1] + $payload;
        }
        return ['id'  => $state['workflow']['id'], 'transaction' => $transaction, 'scope' => $state['scope'],
            'topic'   => $this->ReadPropertyString('BaseTopic') . '/' . ($wire === 'install' ? 'bridge/request/extension/save' : ExtensionContract::PREFIX . 'request/' . $wire),
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
    }

    private function DispatchExtensionFrame(array $frame): bool
    {
        try {
            $send = $this->WithTransportLock(function () use ($frame): bool
            {
                $state = $this->RefreshState();
                $this->SaveState($state);
                return $state['scope'] === $frame['scope'] && $state['mqttConnected'] && ($state['bridgeOnline'] || ($state['workflow']['kind'] ?? '') === 'deactivate')
                    && ($state['workflow']['transaction'] ?? null) === $frame['transaction'];
            });
            if (!$send) {
                return false;
            }
            if (strlen($frame['payload']) > Z2MProtocol::MAX_PAYLOAD_BYTES) {
                throw new \RuntimeException('Request too large');
            }
            // Never hold the state semaphore while the parent dispatches replies.
            $this->DebugEvent('SENDEN', 'Erweiterungsanfrage an MQTT-Gateway übergeben; Bestätigung steht aus.', ['Gateway' => $this->GetParentID(), 'Topic' => $frame['topic'], 'Nutzdatenbytes' => strlen($frame['payload']), 'Anfragebezug' => substr(hash('sha256', $frame['transaction']), 0, 16)]);
            $this->SendDataToParent($this->EncodeDataFlowMessage(Z2MProtocol::MQTT_TX, [
                'PacketType' => 3, 'QualityOfService' => 0, 'Retain' => false,
                'Topic'      => $frame['topic'], 'Payload' => bin2hex($frame['payload'])
            ]));
            return true;
        } catch (Throwable $exception) {
            $this->WithTransportLock(function () use ($frame): void
            {
                $state = $this->RefreshState();
                if (($state['workflow']['transaction'] ?? null) === $frame['transaction']) {
                    $this->FinishExtensionWorkflow($state, 'error', 'send_failed');
                }
                $this->SaveState($state);
            });
            return false;
        }
    }

    private function ObserveExtensionStatus(array &$state, array $metadata): void
    {
        $prior = $state['extension'];
        $changedSession = $prior !== null && $prior['session'] !== $metadata['session'];
        if ($changedSession || ($prior['checksum'] ?? null) !== $metadata['checksum']) {
            $state['execution'] = null;
        }
        if ($prior !== $metadata) {
            $state['validation'] = null;
            $state['extensionConfirmed'] = false;
            $state['runtimeConfirmed'] = false;
        }
        $freshStopProbe = $state['workflow'] !== null && $state['workflow']['kind'] === 'deactivate'
            && $state['workflow']['wire'] === 'status' && !($state['workflow']['verifyRuntime'] ?? false);
        if (($changedSession || !$metadata['ready']) && $state['workflow'] !== null && $state['workflow']['kind'] !== 'install' && !$freshStopProbe) {
            $this->FinishExtensionWorkflow($state, 'cancelled', 'session_mismatch');
        }
        $state['extension'] = $metadata;
    }

    private function NotifyExtensionInstallation(string $error): void
    {
        try {
            $message = $error === '' ? $this->Translate('Extension installed or updated successfully. The receiver in Z2M has confirmed readiness.')
                : $this->Translate('Extension installation or update failed:') . ' ' . $this->Translate(ExtensionContract::ErrorText($error));
            $this->UpdateFormField('InstallResultMessage', 'caption', $message);
            $this->UpdateFormField('InstallResultPopup', 'visible', false);
            $this->UpdateFormField('InstallResultPopup', 'visible', true);
            $this->DebugEvent($error === '' ? 'AKTION' : 'FEHLER', 'Installationsrückmeldung an das geöffnete Formular übergeben.', ['Erfolgreich' => $error === '', 'Grund' => $error]);
        } catch (Throwable) {
            $this->DebugEvent('FEHLER', 'Installationsrückmeldung konnte im Formular nicht angezeigt werden. Statusanzeige und Debug beachten.');
        }
    }

    private function ExtensionBuildInfo(array $state): array
    {
        $bundled = ExtensionContract::BundledBuild();
        $confirmed = $state['mqttConnected'] && $state['extensionConfirmed'] && ($state['extension']['ready'] ?? false);
        $installed = $confirmed ? ($state['extension']['build'] ?? null) : null;
        $comparison = $bundled === null ? 'missing' : (!$confirmed ? 'unconfirmed' : ($installed === null ? 'legacy' : ($installed === $bundled ? 'current' : 'different')));
        $installedText = $confirmed
            ? sprintf($this->Translate('Installed extension: %s'), $state['extension']['version'] . ' (' . ($installed === null ? $this->Translate('without build identifier') : 'Build ' . substr($installed, 0, 12)) . ')')
            : $this->Translate('Installed extension: Not yet confirmed.');
        $bundledText = $bundled === null ? $this->Translate('Bundled extension: Build identifier unavailable. Check the module files.')
            : sprintf($this->Translate('Bundled extension: %s'), ExtensionContract::VERSION . ' (Build ' . substr($bundled, 0, 12) . ')');
        $hint = match ($comparison) {
            'current'   => 'Up to date. The installed and bundled extensions have the same build.',
            'different' => 'Update available: The installed build differs from the bundled build. Choose Install or update bundled extension to use the bundled version.',
            'legacy'    => 'The installed extension cannot be compared because it reports no build identifier. Update the bundled extension, then request its status again.',
            'missing'   => 'Build comparison unavailable. Update the module files and reopen this form.',
            default     => 'Not yet checked. Request the extension status to compare builds.'
        };
        return ['comparison' => $comparison, 'installed' => $installed, 'bundled' => $bundled, 'confirmed' => $confirmed,
            'fields'         => ['InstalledExtensionInfo' => $installedText, 'BundledExtensionInfo' => $bundledText, 'ExtensionUpdateInfo' => $this->Translate($hint)]];
    }

    private function UpdateExtensionBuildInfo(array $state): void
    {
        $info = $this->ExtensionBuildInfo($state);
        $key = json_encode($info, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        if ($this->GetBuffer('ExtensionBuildDisplay') === $key) {
            return;
        }
        $this->SetBuffer('ExtensionBuildDisplay', $key);
        foreach ($info['fields'] as $field => $text) {
            $this->UpdateFormField($field, 'caption', $text);
        }
        $message = match ($info['comparison']) {
            'current'   => 'Installierte und mitgelieferte Erweiterung haben denselben Buildstand; kein Update erforderlich.',
            'different' => 'Installierte Erweiterung weicht vom mitgelieferten Buildstand ab. Zum Angleichen die mitgelieferte Erweiterung installieren oder aktualisieren.',
            'legacy'    => 'Z2M-Erweiterung meldet keine Buildkennung; Vergleich nicht möglich. Mitgelieferte Erweiterung aktualisieren und Status erneut abfragen.',
            'missing'   => 'Buildkennung der mitgelieferten Erweiterung nicht lesbar; Moduldateien prüfen.',
            default     => 'Erweiterungsstand noch nicht bestätigt; Updatebedarf bleibt offen. Erweiterungsstatus abfragen.'
        };
        $this->DebugEvent(
            $info['comparison'] === 'missing' ? 'FEHLER' : 'AKTION',
            $message,
            ['Vergleich'             => $info['comparison'], 'Bestätigt' => $info['confirmed'],
                'InstallierterBuild' => substr($info['installed'] ?? '', 0, 12), 'MitgelieferterBuild' => substr($info['bundled'] ?? '', 0, 12)]
        );
    }

    private function MatchExtensionResponse(array &$state, string $suffix, mixed $payload, bool $retained): bool
    {
        $workflow = $state['workflow'];
        if ($workflow === null || $retained || !$payload instanceof stdClass || ($payload->transaction ?? null) !== $workflow['transaction']) {
            $this->DebugEvent('AKTION', 'Antwort ohne passenden laufenden Auftrag verworfen.', ['Retain' => $retained, 'AuftragVorhanden' => $workflow !== null]);
            return false;
        }
        $expected = $workflow['wire'] === 'install' ? 'bridge/response/extension/save' : ExtensionContract::PREFIX . 'response/' . $workflow['wire'];
        if ($suffix !== $expected) {
            $this->DebugEvent('AKTION', 'Antworttopic passt nicht zur erwarteten Auftragsphase.');
            return false;
        }
        $this->DebugEvent('AKTION', 'Antwort dem laufenden Auftrag zugeordnet.', ['Phase' => $workflow['wire'], 'Anfragebezug' => substr(hash('sha256', $workflow['transaction']), 0, 16)]);
        if ($workflow['wire'] !== 'install' && ($payload->protocol_version ?? null) !== 1) {
            $state['extensionConfirmed'] = false;
            $state['validation'] = null;
            $this->FinishExtensionWorkflow($state, 'error', 'protocol_mismatch');
            return false;
        }
        return true;
    }

    private function RefreshExtensionWorkflow(array $state): array
    {
        $state += $this->NewExtensionState();
        if ($state['workflow'] !== null && $state['workflow']['deadline'] <= $this->Now()) {
            $this->FinishExtensionWorkflow($state, 'timeout', 'timeout');
            $state['extensionConfirmed'] = false;
        }
        if (!$state['mqttConnected'] || !$state['bridgeOnline'] || !$state['extensionConfirmed']) {
            $state['runtimeConfirmed'] = false;
        }
        return $state;
    }

}
