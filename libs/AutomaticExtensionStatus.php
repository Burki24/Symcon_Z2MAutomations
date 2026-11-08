<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** Bounded, read-only receiver confirmation while Z2M is starting. */
trait AutomaticExtensionStatus
{
    private function QueueExtensionConfirmation(array &$state): void
    {
        if (($state['automaticStatus'] ?? null) !== null) return;
        $state['automaticStatus'] = ['attempts' => 0, 'due' => $this->Now() + 1];
        $this->DebugEvent('AKTION', 'Zigbee2MQTT ist wieder online. Erweiterungsstatus verzögert und höchstens dreimal prüfen; keine Automationen übertragen oder starten.');
    }

    private function BeginExtensionConfirmation(array &$state): void
    {
        $state['automaticStatus'] ??= ['attempts' => 0, 'due' => null];
        $state['automaticStatus']['attempts']++;
        $state['automaticStatus']['due'] = null;
        $state['workflow']['automaticStatus'] = true;
        $this->DebugEvent('SENDEN', 'Erweiterungsstatus während des Z2M-Anlaufs lesend prüfen. Eine frische passende Antwort ist zur Bestätigung erforderlich.', ['Versuch' => $state['automaticStatus']['attempts'], 'Höchstens' => 3]);
    }

    private function RetryExtensionConfirmation(array &$state, string $error): bool
    {
        if (!($state['workflow']['automaticStatus'] ?? false) || $state['workflow']['kind'] !== 'status'
            || $state['workflow']['wire'] !== 'status' || !in_array($error, ['timeout', 'not_ready'], true)) return false;
        $attempts = $state['automaticStatus']['attempts'] ?? 3;
        if (!$state['mqttConnected'] || !$state['bridgeOnline'] || $attempts >= 3) {
            $this->DebugEvent('FEHLER', 'Automatische Erweiterungsprüfung beendet: keine Bestätigung erhalten oder Verbindung nicht mehr verfügbar. Bitte Verbindung und Erweiterung prüfen.', ['Versuche' => $attempts]);
            return false;
        }
        $state['workflow'] = null;
        $state['automaticStatus']['due'] = $this->Now() + 2;
        $state['operation']['status'] = 'pending';
        $state['lastError'] = '';
        $state['extensionConfirmed'] = false;
        $state['runtimeConfirmed'] = false;
        $this->DebugEvent('AKTION', 'Zigbee2MQTT antwortet, die Erweiterung ist möglicherweise noch im Anlauf. Nur die lesende Statusabfrage in zwei Sekunden erneut senden.', ['NächsterVersuch' => $attempts + 1, 'Höchstens' => 3, 'Grund' => $error]);
        return true;
    }

    private function FinishExtensionConfirmation(array &$state, string $status): void
    {
        if (!($state['workflow']['automaticStatus'] ?? false)) return;
        if ($status === 'status_received') $this->DebugEvent('EMPFANG', 'Erweiterungsstatus nach Z2M-Anlauf durch frische passende Antwort bestätigt. Weitere automatische Versuche entfallen.', ['Versuche' => $state['automaticStatus']['attempts'] ?? 1]);
        $state['automaticStatus'] = null;
    }

    private function DispatchExtensionConfirmation(): void
    {
        $start = $this->WithTransportLock(function (): bool
        {
            $state = $this->RefreshState();
            $probe = $state['automaticStatus'] ?? null;
            if ($probe === null) return false;
            if (!$state['mqttConnected'] || !$state['bridgeOnline'] || IPS_GetKernelRunlevel() !== KR_READY || $this->TransportOwnershipBlocked()
                || ($state['workflow'] !== null && !($state['workflow']['automaticStatus'] ?? false)) || $state['pending'] !== null) {
                $state['automaticStatus'] = null;
                $this->DebugEvent('AKTION', 'Vorgemerkte Statuswiederholung entfällt: Verbindung oder Zuständigkeit geändert oder anderer Auftrag hat Vorrang. Kein Schreib- oder Schaltauftrag wird wiederholt.');
                $this->SaveState($state);
                return false;
            }
            return $state['workflow'] === null && is_int($probe['due']) && $probe['due'] <= $this->Now();
        });
        if ($start) $this->StartExtensionWorkflow('status', '', '', false, true);
    }
}
