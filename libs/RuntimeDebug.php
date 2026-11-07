<?php

declare(strict_types=1);

namespace Z2MAutomations;

require_once __DIR__ . '/RuntimeDebugContract.php';

trait RuntimeDebug
{
    public function StartRuntimeDebug(int $Seconds = 600): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->RequestRuntimeDebug($Seconds));
    }

    public function StopRuntimeDebug(): bool
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): bool => $this->RequestRuntimeDebug(0));
    }

    public function ExpireRuntimeDebug(): void
    {
        $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): void
        {
            $this->WithTransportLock(function (): void
            {
                $this->SyncRuntimeDebug($this->RefreshState());
                $this->UpdateRuntimeDebugForm();
            });
        });
    }

    private function RuntimeDebugLease(): array
    {
        $text = $this->GetBuffer('RuntimeDebug');
        return $text === '' ? [] : json_decode($text, true, 16, JSON_THROW_ON_ERROR);
    }

    /** Cached observation only. Never expose the receiver session, client or transaction. */
    private function RuntimeDebugView(array $state): array
    {
        $this->SyncRuntimeDebug($state);
        $lease = $this->RuntimeDebugLease();
        $pending = ($lease['pending'] ?? '') !== '';
        $phase = $lease === [] ? 'idle' : ($pending ? ($lease['seconds'] === 0 ? 'stopping' : 'starting') : 'active');
        return ['phase' => $phase, 'pending' => $pending, 'active' => ($lease['active'] ?? false),
            'until'     => ($lease['seconds'] ?? 0) > 0 ? $lease['until'] : null,
            'remaining' => ($lease['seconds'] ?? 0) > 0 ? max(0, $lease['until'] - $this->Now()) : 0,
            'context'   => hash('sha256', json_encode([$state['scope'], $state['extension']['session'] ?? '', $lease['client'] ?? '', $lease['until'] ?? 0, $lease['seconds'] ?? 0], JSON_THROW_ON_ERROR)),
            'canStart'  => $lease === [] && $state['mqttConnected'] && $state['extensionConfirmed'] && ($state['extension']['debugVersion'] ?? 0) === 1,
            'canStop'   => $lease !== [] && $state['mqttConnected'] && $phase !== 'stopping',
            'message'   => $this->RuntimeDebugText()];
    }

    private function ResetRuntimeDebug(string $message): void
    {
        $lease = $this->RuntimeDebugLease();
        $this->SetBuffer('RuntimeDebug', '');
        $this->SetTimerInterval('RuntimeDebugExpiry', 0);
        $this->SetBuffer('RuntimeDebugInfo', $message);
        $this->UpdateRuntimeDebugForm();
        if ($lease !== []) {
            $this->DebugEvent('AKTION', $message);
        }
    }

    private function SyncRuntimeDebug(array $state): void
    {
        $lease = $this->RuntimeDebugLease();
        if ($lease === []) {
            return;
        }
        // Snapshot confirmation can be pending after storage while this lease still belongs to the same session.
        if ($lease['scope'] !== $state['scope'] || !$state['mqttConnected']
            || $lease['session'] !== ($state['extension']['session'] ?? null)) {
            $this->ResetRuntimeDebug('Aufzeichnung beendet: Verbindung oder Z2M-Sitzung hat sich geändert. Bei Bedarf erneut starten.');
        } elseif ($lease['deadline'] <= $this->Now()) {
            $this->ResetRuntimeDebug($lease['pending'] !== '' ? 'Keine Bestätigung für die Aufzeichnung erhalten. Erweiterungsstatus prüfen und erneut starten.'
                : 'Aufzeichnung nach Ablauf der gewählten Zeit automatisch beendet.');
        }
    }

    private function RequestRuntimeDebug(int $seconds): bool
    {
        $frame = $this->WithTransportLock(function () use ($seconds): ?array
        {
            $state = $this->RefreshState();
            $this->SyncRuntimeDebug($state);
            $previous = $this->RuntimeDebugLease();
            if ($seconds < 0 || $seconds > 900 || !$state['mqttConnected']
                || ($seconds > 0 && (!$state['extensionConfirmed'] || ($state['extension']['debugVersion'] ?? 0) !== 1))
                || ($seconds === 0 && $previous === [])) {
                $this->DebugEvent('FEHLER', 'Aufzeichnung nicht gestartet: Dauer von 1 bis 900 Sekunden und bestätigte Erweiterung mit Debugkanal erforderlich; zum Beenden muss eine eigene Aufzeichnung bestehen.');
                $this->SetBuffer('RuntimeDebugInfo', ($previous['active'] ?? false)
                    ? 'Aufzeichnung läuft unverändert weiter. Für einen neuen Start zuerst den Erweiterungsstatus prüfen.'
                    : 'Aufzeichnung nicht verfügbar. Erweiterungsstatus abfragen und bei Bedarf die Erweiterung aktualisieren.');
                $this->UpdateRuntimeDebugForm();
                return null;
            }
            $bytes = bin2hex(random_bytes(16));
            $client = $previous['client'] ?? substr($bytes, 0, 8) . '-' . substr($bytes, 8, 4) . '-' . substr($bytes, 12, 4) . '-' . substr($bytes, 16, 4) . '-' . substr($bytes, 20);
            $transaction = bin2hex(random_bytes(16));
            $lease = ['scope' => $state['scope'], 'session' => $state['extension']['session'], 'client' => $client,
                'seconds'     => $seconds, 'until' => $this->Now() + $seconds, 'active' => false, 'pending' => $transaction,
                'deadline'    => $this->Now() + $this->ReadPropertyInteger('RequestTimeout'), 'seen' => [], 'window' => $this->Now(), 'count' => 0, 'warned' => false];
            $this->SetBuffer('RuntimeDebug', json_encode($lease, JSON_THROW_ON_ERROR));
            $this->SetBuffer('RuntimeDebugInfo', 'Bestätigung von Z2M steht aus.');
            $this->SetTimerInterval('RuntimeDebugExpiry', max(1, ($lease['deadline'] - $this->Now()) * 1000));
            $this->UpdateRuntimeDebugForm();
            return ['scope' => $state['scope'], 'transaction' => $transaction,
                'topic'     => $this->ReadPropertyString('BaseTopic') . '/symcon/automations/request/debug',
                'payload'   => ['protocol_version' => 1, 'transaction' => $transaction, 'session' => $lease['session'], 'client' => $client, 'seconds' => $seconds]];
        });
        if ($frame === null) {
            return false;
        }
        try {
            $current = $this->WithTransportLock(function () use ($frame): bool
            {
                $state = $this->RefreshState();
                $this->SyncRuntimeDebug($state);
                return $state['scope'] === $frame['scope'] && ($this->RuntimeDebugLease()['pending'] ?? '') === $frame['transaction'];
            });
            if (!$current) {
                return false;
            }
            // The parent may deliver a synchronous reply: never send while holding the semaphore.
            $this->DebugEvent('SENDEN', 'Zeitlich begrenzte Aufzeichnung angefragt; Automationen und andere Aufzeichnungen bleiben unverändert.', ['DauerSekunden' => $seconds]);
            $this->SendDataToParent($this->EncodeDataFlowMessage(Z2MProtocol::MQTT_TX, [
                'PacketType' => 3, 'QualityOfService' => 0, 'Retain' => false, 'Topic' => $frame['topic'],
                'Payload'    => bin2hex(json_encode($frame['payload'], JSON_THROW_ON_ERROR))
            ]));
            return true;
        } catch (\Throwable) {
            $this->WithTransportLock(function () use ($frame): void
            {
                if (($this->RuntimeDebugLease()['pending'] ?? '') === $frame['transaction']) {
                    $this->ResetRuntimeDebug('Aufzeichnung konnte nicht angefragt werden. MQTT-Verbindung prüfen.');
                    $this->UpdateRuntimeDebugForm();
                }
            });
            return false;
        }
    }

    private function ReceiveRuntimeDebug(string $suffix, mixed $payload, bool $retained): void
    {
        $this->WithTransportLock(function () use ($suffix, $payload, $retained): void
        {
            if ($this->TransportOwnershipBlocked()) return;
            $this->SyncRuntimeDebug($this->RefreshState());
            $lease = $this->RuntimeDebugLease();
            if ($retained || $lease === [] || !$payload instanceof \stdClass) {
                return;
            }
            if ($suffix === 'symcon/automations/response/debug') {
                if ($lease['pending'] === '' || ($payload->protocol_version ?? null) !== 1 || ($payload->transaction ?? null) !== $lease['pending']) {
                    return;
                }
                $data = $payload->data ?? null;
                if (($payload->status ?? null) !== 'ok' || !$data instanceof \stdClass || ($data->debug_version ?? null) !== 1
                    || ($data->client ?? null) !== $lease['client'] || ($data->seconds ?? null) !== $lease['seconds']
                    || ($data->active ?? null) !== ($lease['seconds'] > 0)) {
                    $this->ResetRuntimeDebug('Z2M hat die Aufzeichnung nicht bestätigt. Erweiterungsstatus prüfen und erneut starten.');
                } elseif ($lease['seconds'] === 0) {
                    $this->ResetRuntimeDebug('Eigene Aufzeichnung beendet. Andere Aufzeichnungen bleiben unverändert.');
                } else {
                    $lease['pending'] = '';
                    $lease['active'] = true;
                    $lease['deadline'] = $lease['until'];
                    $this->SetBuffer('RuntimeDebug', json_encode($lease, JSON_THROW_ON_ERROR));
                    $this->SetTimerInterval('RuntimeDebugExpiry', max(1, ($lease['until'] - $this->Now()) * 1000));
                    $this->SetBuffer('RuntimeDebugInfo', 'Aufzeichnung läuft und endet automatisch. Meldungen erscheinen im Debugfenster dieser Instanz.');
                    $this->DebugEvent('EMPFANG', 'Z2M hat die Aufzeichnung bestätigt; sie endet automatisch.', ['DauerSekunden' => $lease['seconds']]);
                }
                $this->UpdateRuntimeDebugForm();
                return;
            }
            if (!$lease['active']) {
                return;
            }
            if ($lease['window'] !== $this->Now()) {
                $lease['window'] = $this->Now();
                $lease['count'] = 0;
                $lease['warned'] = false;
            }
            $record = RuntimeDebugContract::Record($payload, $lease, $this->Now());
            if ($record === null || $lease['count'] >= 80) {
                if (!$lease['warned']) {
                    $this->DebugEvent('FEHLER', 'Ungültige oder zu viele Ablaufmeldungen verworfen; die Aufzeichnung kann Lücken enthalten.');
                }
                $lease['warned'] = true;
                $this->SetBuffer('RuntimeDebug', json_encode($lease, JSON_THROW_ON_ERROR));
                return;
            }
            if (in_array($record['id'], $lease['seen'], true)) {
                return;
            }
            $lease['count']++;
            $lease['seen'][] = $record['id'];
            $lease['seen'] = array_slice($lease['seen'], -128);
            $this->SetBuffer('RuntimeDebug', json_encode($lease, JSON_THROW_ON_ERROR));
            $entry = RuntimeDebugContract::Catalog()[$record['event']];
            $details = ['Systembezug' => substr(hash('sha256', $this->ReadPropertyString('BaseTopic')), 0, 16), 'ZeitpunktUTC' => gmdate('Y-m-d H:i:s', intdiv($record['at'], 1000))];
            foreach (['sequence' => 'Aktionsfolge', 'sequence_step' => 'SchrittInAktionsfolge', 'run' => 'Regellauf', 'rule' => 'Regelnummer', 'trigger' => 'Auslösernummer', 'condition' => 'Bedingungsnummer', 'step' => 'Aktionsschritt', 'revision' => 'Revision', 'delay' => 'WartezeitSekunden', 'passed' => 'Erfüllt'] as $key => $label) {
                if (array_key_exists($key, $record)) {
                    $details[$label] = $record[$key];
                }
            }
            if (isset($record['origin'])) {
                $details['Ursprungssystem'] = substr($record['origin'], 0, 16);
            }
            if (isset($record['reason'])) {
                $details['Grund'] = RuntimeDebugContract::Reason($record['reason']);
            }
            if ($record['dropped'] > 0) {
                $this->DebugEvent('FEHLER', 'Z2M konnte vorherige Ablaufmeldungen nicht übertragen; die Aufzeichnung enthält Lücken.', ['FehlendeMeldungen' => $record['dropped']]);
            }
            // Remote lifecycle records are domain events within this ReceiveData call;
            // they must not open or close the canonical local DebugFlowHelper scope.
            $details['Z2MPhase'] = $entry['event'];
            $this->DebugEvent(in_array($entry['event'], ['START', 'ENDE'], true) ? 'AKTION' : $entry['event'], 'Z2M: ' . $entry['message'], $details);
        });
    }

    private function FillRuntimeDebugForm(array &$form): void
    {
        foreach ($this->EditorFormFields($form['actions']) as &$field) {
            if (($field['name'] ?? '') === 'RuntimeDebugInfo') {
                $field['caption'] = $this->RuntimeDebugText();
            } elseif (($field['name'] ?? '') === 'RuntimeDebugStop') {
                $field['enabled'] = $this->RuntimeDebugLease() !== [];
            }
        }
        unset($field);
    }

    private function RuntimeDebugText(): string
    {
        return $this->Translate($this->GetBuffer('RuntimeDebugInfo') ?: 'Keine Aufzeichnung aktiv. Debugfenster öffnen und Aufzeichnung starten.');
    }

    private function UpdateRuntimeDebugForm(): void
    {
        $this->UpdateFormField('RuntimeDebugInfo', 'caption', $this->RuntimeDebugText());
        $this->UpdateFormField('RuntimeDebugStop', 'enabled', $this->RuntimeDebugLease() !== []);
    }
}
