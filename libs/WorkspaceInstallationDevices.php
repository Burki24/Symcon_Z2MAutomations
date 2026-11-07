<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

/** The confirmed installer delegates one device-only read to the existing catalog workflow. */
trait WorkspaceInstallationDevices
{
    private function RefreshInstallationDevices(string $system, bool $allowStart): void
    {
        $job = $this->ReadWorkspaceInstallations()['systems'][$system] ?? null;
        if (($job['status'] ?? '') !== 'installed' || !in_array($job['devices']['phase'] ?? '', ['waiting', 'loading'], true)) return;
        $operation = $job['request']['operation'];
        try {
            if ($job['devices']['phase'] === 'waiting' && !$allowStart) return;
            $record = $this->WorkspaceRecord($this->ReadWorkspace(), $system);
            if ($record['binding'] !== $job['request']['binding']) throw new RuntimeException('binding_changed');
            $info = $this->WorkspaceInstallationAccess($record);
            $session = $info['state']['extension']['session'] ?? '';
            if (!ExtensionContract::Checksum($session)) throw new RuntimeException('catalog_refresh_changed');
            if ($job['devices']['phase'] === 'waiting') {
                if (!($info['state']['extensionConfirmed'] ?? false)) throw new RuntimeException('catalog_refresh_changed');
                $current = $this->ReadWorkspaceCatalogJobs()['systems'][$system] ?? null;
                $joining = ($current['binding'] ?? null) === $record['binding'] && in_array($current['phase'] ?? '', ['health', 'catalog'], true);
                $catalogOperation = $joining ? $current['operation'] : bin2hex(random_bytes(16));
                $claimed = $this->WithWorkspaceLock(function () use ($system, $operation, $session, $catalogOperation): bool
                {
                    $j = $this->ReadWorkspaceInstallations();
                    $latest = $j['systems'][$system] ?? null;
                    if (($latest['request']['operation'] ?? '') !== $operation || $latest['status'] !== 'installed' || $latest['devices']['phase'] !== 'waiting') return false;
                    $j['systems'][$system]['devices'] = ['phase' => 'loading', 'operation' => $catalogOperation, 'session' => $session, 'error' => ''];
                    $this->WriteWorkspaceInstallations($j);
                    return true;
                });
                if (!$claimed) return;
                $this->DebugEvent('SENDEN', $joining
                    ? 'Erweiterung erfolgreich installiert. Bereits laufenden Gerätedatenabruf dieses Systems abwarten; keine zweite Anfrage senden.'
                    : 'Erweiterung erfolgreich installiert. Gerätedaten dieses Systems automatisch nachladen; Entwürfe, Partnerfreigaben und offene Eingaben bleiben erhalten.', ['Systembezug' => $system, 'Verbindung' => $record['binding']['connectionInstance']]);
                if (!$joining) {
                    $reply = json_decode($this->RequestWorkspaceDeviceCatalog($system, $record['binding']['generation'], WorkspaceState::Fingerprint($record), $catalogOperation), true, 24, JSON_THROW_ON_ERROR);
                    if (!($reply['ok'] ?? false)) throw new RuntimeException($reply['error'] ?? 'catalog_refresh_failed');
                }
                return;
            }
            if ($session !== $job['devices']['session']) throw new RuntimeException('catalog_refresh_changed');
            $catalog = $this->ReadWorkspaceCatalogJobs()['systems'][$system] ?? null;
            if (($catalog['binding'] ?? null) !== $record['binding'] || ($catalog['operation'] ?? '') !== $job['devices']['operation']) throw new RuntimeException('catalog_refresh_changed');
            if (in_array($catalog['phase'], ['health', 'catalog'], true)) return;
            if ($catalog['phase'] !== 'loaded') throw new RuntimeException($catalog['error'] ?: 'catalog_refresh_failed');
            $confirmed = $this->WorkspaceCatalog($record['binding']);
            if (!($info['state']['extensionConfirmed'] ?? false) || $confirmed === null || $record['catalog'] === null || WorkspaceState::Fingerprint($confirmed) !== WorkspaceState::Fingerprint($record['catalog'])) throw new RuntimeException('catalog_refresh_changed');
            $this->FinishInstallationDevices($system, $operation, 'loaded', '');
        } catch (Throwable $e) {
            $error = $e instanceof RuntimeException && isset(WorkspaceState::MESSAGES[$e->getMessage()]) ? $e->getMessage() : 'catalog_refresh_failed';
            $this->FinishInstallationDevices($system, $operation, 'error', $error);
        }
    }

    private function FinishInstallationDevices(string $system, string $operation, string $phase, string $error): void
    {
        $changed = $this->WithWorkspaceLock(function () use ($system, $operation, $phase, $error): bool
        {
            $j = $this->ReadWorkspaceInstallations();
            $job = $j['systems'][$system] ?? null;
            if (($job['request']['operation'] ?? '') !== $operation || $job['status'] !== 'installed'
                || !in_array($job['devices']['phase'] ?? '', ['waiting', 'loading'], true)) return false;
            $j['systems'][$system]['devices']['phase'] = $phase;
            $j['systems'][$system]['devices']['error'] = $error;
            $this->WriteWorkspaceInstallations($j);
            $this->DebugEvent($error === '' ? 'EMPFANG' : 'FEHLER', $error === ''
                ? 'Gerätedaten nach der Erweiterungsinstallation frisch bestätigt. Entwurf, Auswahl, Partnerfreigaben und offene Eingaben sind erhalten; keine Automation gespeichert oder gestartet.'
                : 'Erweiterung installiert, Gerätedaten jedoch nicht aktualisiert: ' . (WorkspaceState::MESSAGES[$error] ?? WorkspaceState::MESSAGES['catalog_refresh_failed']) . ' Bitte die Gerätedaten bei Bedarf erneut laden; kein erneuter Erweiterungsupload.', ['Systembezug' => $system, 'Ursache' => $error]);
            return true;
        });
        if ($changed && $phase === 'loaded') $this->RefreshInstallationFormReferences();
    }

    /** Rebase only catalog-induced changes; never let an old form overwrite another edit. */
    private function RefreshInstallationFormReferences(): void
    {
        $workspace = $this->ReadWorkspace();
        $shown = json_decode($this->GetBuffer('WorkspaceInstallationForm') ?: '[]', true, 16, JSON_THROW_ON_ERROR);
        $record = $workspace['systems'][$workspace['selectedSystem']] ?? null;
        if ($record === null || ($shown['system'] ?? '') !== $workspace['selectedSystem'] || ($shown['binding'] ?? null) !== $record['binding']
            || ($shown['dataHash'] ?? '') !== WorkspaceState::Fingerprint($record['data'])) return;
        $this->UpdateFormField('WorkspaceEditorFingerprint', 'value', WorkspaceState::Fingerprint($record));
        $this->UpdateFormField('WorkspaceFingerprint', 'value', WorkspaceState::Fingerprint($workspace));
        $this->DebugEvent('AKTION', 'Formularbezug nach Gerätedatenaktualisierung erneuert. Unveränderte Entwürfe und offene Formulareingaben werden nicht neu aufgebaut oder ersetzt.', ['Systembezug' => $workspace['selectedSystem']]);
        $this->GetWorkspaceRuntimeStatus($workspace['selectedSystem']);
    }

    private function InstallationDevicesPending(array $job): bool
    {
        return ($job['status'] ?? '') === 'installed' && in_array($job['devices']['phase'] ?? '', ['waiting', 'loading'], true);
    }

    private function InstallationDevicesView(?array $job, array $record): array
    {
        $phase = ($job['status'] ?? '') === 'installed' ? ($job['devices']['phase'] ?? 'none') : 'none';
        $error = $job['devices']['error'] ?? '';
        $message = match ($phase) {
            'waiting', 'loading' => 'Erweiterung installiert. Gerätedaten werden automatisch nachgeladen … Vorhandene Entwürfe bleiben erhalten.',
            'loaded'             => 'Gerätedaten nach der Installation erfolgreich aktualisiert. Entwürfe und offene Eingaben sind erhalten.',
            'error'              => 'Erweiterung installiert. Gerätedaten nicht aktualisiert: ' . (WorkspaceState::MESSAGES[$error] ?? WorkspaceState::MESSAGES['catalog_refresh_failed']) . ' Bitte „Gerätedaten aktualisieren“ wählen.',
            'interrupted'        => 'Erweiterung installiert. Das Nachladen der Gerätedaten wurde durch ein Modulupdate unterbrochen. Bitte die Gerätedaten erneut laden; die Erweiterung wird nicht nochmals übertragen.',
            default              => 'Nach erfolgreicher Installation werden die Gerätedaten dieses Systems automatisch aktualisiert.'
        };
        return ['phase' => $phase, 'pending' => in_array($phase, ['waiting', 'loading'], true), 'message' => $message,
            'counts'    => ['devices' => count($record['catalog']['devices'] ?? []), 'groups' => count($record['catalog']['groups'] ?? []), 'scenes' => count($record['catalog']['scenes'] ?? [])]];
    }
}
