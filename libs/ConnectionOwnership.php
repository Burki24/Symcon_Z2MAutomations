<?php

declare(strict_types=1);

namespace Z2MAutomations;

use Throwable;

trait ConnectionOwnership
{
    public function ClaimSystemConnection(int $Owner, string $OperationID, string $Binding, bool $Release): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->ClaimConnection($Owner, $OperationID, $Binding, $Release, false));
    }

    public function ClaimWorkspaceConnection(int $Owner, string $OperationID, string $Binding, bool $Release): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->ClaimConnection($Owner, $OperationID, $Binding, $Release, true));
    }

    private function ClaimConnection(int $Owner, string $OperationID, string $Binding, bool $Release, bool $direct): string
    {
        $lock = 'Z2MA.OwnershipRegistry';
        if (!IPS_SemaphoreEnter($lock, 0)) return '{"ok":false,"error":"ownership_conflict"}';
        try {
            $binding = strlen($Binding) <= 2048 ? json_decode($Binding, true, 8, JSON_THROW_ON_ERROR) : [];
            if (!is_array($binding) || SystemContract::RegistryError([$binding]) !== '' || $binding['ownerInstance'] !== $Owner
                || !IPS_InstanceExists($Owner) || IPS_GetInstance($Owner)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE
                || preg_match('/\A[a-f0-9]{32}\z/D', $OperationID) !== 1 || $binding['connectionInstance'] !== $this->InstanceID) throw new \RuntimeException();
            if (!$Release && ($binding['mqttParent'] !== $this->GetParentID() || $binding['baseTopic'] !== $this->ReadPropertyString('BaseTopic')
                || $binding['systemId'] !== $this->ReadPropertyString('SystemID') || $binding['generation'] !== $this->ReadPropertyInteger('Generation'))) throw new \RuntimeException();
            $authority = json_decode($direct ? Z2MA_GetWorkspaceSetupAuthority($Owner, $OperationID) : Z2MA_GetSystemMigrationAuthority($Owner, $OperationID), true, 16, JSON_THROW_ON_ERROR);
            $authorized = $direct ? ((($authority['binding'] ?? null) === $binding) ? [true] : []) : array_filter($authority['sources'] ?? [], static fn (array $row): bool => $row['binding'] === $binding);
            if (($authority['ok'] ?? false) !== true || ($authority['phase'] ?? '') !== ($Release ? 'rolling_back' : ($direct ? 'committing' : 'applying')) || count($authorized) !== 1) throw new \RuntimeException();
            $epoch = $direct ? ($authority['epoch'] ?? '') : $OperationID;
            if (!DraftTransferProtocol::Operation($epoch)) throw new \RuntimeException();
            $expected = ['version' => 1, 'role' => 'connection', 'owner' => $Owner, 'operation' => $epoch,
                'systemId'         => $binding['systemId'], 'generation' => $binding['generation'], 'baseTopic' => $binding['baseTopic']];
            $marker = $this->OwnershipMarker();
            if ($marker !== [] && $marker !== $expected) throw new \RuntimeException();
            if (!$Release) foreach (IPS_GetInstanceListByModuleID(SystemContract::CONNECTION_MODULE) as $id) {
                if ($id === $this->InstanceID) continue;
                $other = json_decode(Z2MC_GetSystemOwnership($id), true, 16, JSON_THROW_ON_ERROR);
                if ($other !== [] && (($other['systemId'] ?? '') === $binding['systemId'] || ($other['baseTopic'] ?? '') === $binding['baseTopic'])) throw new \RuntimeException();
            }
            $this->WithTransportLock(function () use ($Release, $expected, $marker, $direct): void
            {
                if (!$Release && $marker === []) {
                    $state = json_decode($this->GetBuffer('TransportState') ?: '[]', true, 32, JSON_THROW_ON_ERROR);
                    if (($state['pending'] ?? null) !== null || ($state['workflow'] ?? null) !== null || $this->GetBuffer('TransportDispatches') !== '') throw new \RuntimeException();
                }
                if ($marker !== ($Release ? [] : $expected) && !$direct) {
                    foreach (['TransportState', 'RuntimeDebug'] as $buffer) $this->SetBuffer($buffer, '');
                    foreach (['RequestTimeout', 'RuntimeDebugExpiry'] as $timer) $this->SetTimerInterval($timer, 0);
                    // Keep historical catalog bytes but invalidate their receiver confirmation.
                    $this->SaveState($this->RefreshState());
                }
                $this->WriteOwnership($Release ? [] : $expected);
            });
            $this->DebugEvent('AKTION', $Release ? 'Lokale Besitzerzuordnung der Verbindung freigegeben; MQTT-Einstellungen bleiben erhalten.'
                : 'Verbindung ausschließlich der gewählten zentralen Verwaltung zugeordnet. Z2M wurde nicht verändert.', ['Verwaltung' => $Owner]);
            return '{"ok":true}';
        } catch (Throwable) {
            $this->DebugEvent('FEHLER', 'Besitzerwechsel der Verbindung abgelehnt: Zuordnung geändert oder bereits von einer anderen Verwaltung belegt. Bitte den Übernahmebericht prüfen.');
            return '{"ok":false,"error":"ownership_conflict"}';
        } finally {
            IPS_SemaphoreLeave($lock);
        }
    }
}
