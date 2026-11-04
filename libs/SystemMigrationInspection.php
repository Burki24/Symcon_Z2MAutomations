<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;
use Throwable;

require_once __DIR__ . '/SystemContract.php';
require_once __DIR__ . '/SystemMigrationPreview.php';

trait SystemMigrationInspection
{
    /** Metadata only; this entry never grants ownership. */
    public function GetSystemMigrationSource(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->WithTransportLock(function (): string
        {
            $source = $this->InspectMigrationSource();
            $this->DebugEvent(
                'AKTION',
                'Bestandsdaten für die Übernahmevorschau nur gelesen. Entwurf, Verbindung und Z2M bleiben unverändert.',
                ['Quelle' => $this->InstanceID, 'Regeln' => $source['rules'], 'Aktionsfolgen' => $source['sequences'], 'Probleme' => count($source['issues'])]
            );
            return json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        }));
    }

    public function PreviewSystemMigration(string $SourceInstanceIDs): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SourceInstanceIDs): string
        {
            try {
                $ids = strlen($SourceInstanceIDs) <= 1024 ? json_decode($SourceInstanceIDs, true, 4, JSON_THROW_ON_ERROR) : null;
                $valid = is_array($ids) && array_is_list($ids) && count($ids) >= 1 && count($ids) <= SystemContract::MAX_SYSTEMS
                    && count(array_unique($ids, SORT_REGULAR)) === count($ids)
                    && array_filter($ids, static fn (mixed $id): bool => !is_int($id) || $id < 10000 || $id > 59999) === [];
            } catch (JsonException) {
                $valid = false;
            }
            $sources = [];
            if ($valid) foreach ($ids as $id) {
                try {
                    if (!IPS_InstanceExists($id) || IPS_GetInstance($id)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE) throw new \RuntimeException();
                    $raw = Z2MA_GetSystemMigrationSource($id);
                    $source = is_string($raw) && strlen($raw) <= 16384 ? json_decode($raw, true, 16, JSON_THROW_ON_ERROR) : null;
                    if (!$this->ValidMigrationSourceSummary($source, $id)) throw new \RuntimeException();
                    $sources[] = $source;
                } catch (Throwable) {
                    $source = $this->EmptyMigrationSource($id);
                    $source['issues'][] = SystemMigrationPreview::Issue('source_unavailable');
                    $sources[] = $source;
                }
            }
            $report = SystemMigrationPreview::Build($sources);
            foreach ($report['issues'] as $issue) $this->DebugEvent('FEHLER', $issue['message']);
            foreach ($report['systems'] as $system) foreach ($system['issues'] as $issue) {
                $this->DebugEvent('FEHLER', 'Übernahmevorschau: ' . $issue['message'], ['Quelle' => $system['sourceInstance']]);
            }
            $this->DebugEvent(
                'AKTION',
                $report['status'] === 'blocked'
                ? 'Übernahmevorschau enthält Konflikte. Es wird nichts angelegt, übernommen oder an Z2M gesendet.'
                : 'Übernahmevorschau erstellt. Die Quellen können weiter geprüft werden; eine Übernahme wird noch nicht angeboten.',
                ['Quellen' => count($report['systems']), 'NurLesend' => true]
            );
            return json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        });
    }

    private function EmptyMigrationSource(int $id): array
    {
        return ['contractVersion' => SystemContract::VERSION, 'sourceInstance' => $id, 'role' => 'legacy', 'mqttParent' => 0, 'baseTopic' => '', 'peerInstance' => 0,
            'rules'               => 0, 'sequences' => 0, 'localChanges' => false, 'catalogAvailable' => false, 'fingerprint' => '', 'yamlChecksum' => '',
            'requiresValidation'  => true, 'bindingsVerified' => false, 'issues' => []];
    }

    private function InspectMigrationSource(): array
    {
        $source = $this->EmptyMigrationSource($this->InstanceID);
        $attributes = [];
        foreach (['YamlDraft', 'EditorDraft', 'EditorSnapshot', 'EditorSource', 'EditorTarget', 'EditorSelection', 'SequenceSelection',
            'ExecutionRule', 'EditorCondition', 'EditorConditionProperty', 'EditorModes'] as $key) $attributes[$key] = $this->ReadAttributeString($key);
        $work = [];
        foreach (['EditorWork.rule', 'EditorWork.sequence', 'EditorNavigation'] as $key) $work[$key] = $this->GetBuffer($key);
        if (($this->OwnershipMarker()['role'] ?? '') === 'legacy') {
            $work = json_decode($this->ReadAttributeString('MigrationSourceWork'), true, 32, JSON_THROW_ON_ERROR);
        }
        $source['mqttParent'] = $this->GetParentID();
        $source['baseTopic'] = $this->ReadPropertyString('BaseTopic');
        $source['peerInstance'] = $this->ReadPropertyInteger('PeerModuleID');
        $settings = [$source['mqttParent'], $source['baseTopic'], $source['peerInstance'], $this->ReadPropertyBoolean('Active'),
            $this->ReadPropertyBoolean('ResumeAfterRestart'), $this->ReadPropertyInteger('RequestTimeout'), $this->ReadAttributeBoolean('ShowYaml')];
        $fingerprintAttributes = $attributes;
        // Editing capabilities are not content and are deliberately invalidated on return.
        foreach (['EditorSelection', 'SequenceSelection'] as $name) {
            $selected = json_decode($fingerprintAttributes[$name], true);
            if (is_array($selected)) {
                unset($selected['token']);
                $fingerprintAttributes[$name] = json_encode($selected, JSON_THROW_ON_ERROR);
            }
        }
        $source['fingerprint'] = hash('sha256', serialize([$settings, $fingerprintAttributes, $work]));
        $source['yamlChecksum'] = hash('sha256', $attributes['YamlDraft']);
        $source['role'] = SystemContract::Role(null, $attributes['YamlDraft'] !== '' || $attributes['EditorSnapshot'] !== '' || $source['mqttParent'] > 0);
        $issue = static function (string $code) use (&$source): void
        { $source['issues'][] = SystemMigrationPreview::Issue($code); };
        if ($this->OwnershipMarker() !== []) $issue('owned');
        $parent = $source['mqttParent'] > 0 && IPS_InstanceExists($source['mqttParent']) ? IPS_GetInstance($source['mqttParent']) : null;
        if (!in_array($parent['ModuleInfo']['ModuleID'] ?? '', [Z2MProtocol::MQTT_CLIENT, Z2MProtocol::MQTT_SERVER], true) || !SystemContract::Topic($source['baseTopic'])
            || ($source['peerInstance'] !== 0 && ($source['peerInstance'] < 10000 || $source['peerInstance'] > 59999))
            || $settings[5] < 1 || $settings[5] > 120) $issue('invalid_binding');
        try {
            $decode = static function (string $raw): array
            {
                if ($raw === '' || strlen($raw) > 2097152) throw new \RuntimeException();
                $value = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
                if (!is_array($value)) throw new \RuntimeException();
                return $value;
            };
            $draft = $decode($attributes['EditorDraft']);
            $configuration = $draft['configuration'] ?? [];
            if (!ExtensionContract::ValidYamlText($attributes['YamlDraft']) || ($configuration['schema_version'] ?? null) !== 1
                || !ExtensionContract::Revision($configuration['revision'] ?? null) || $configuration['revision'] < 1
                || !ExtensionContract::Revision($draft['baseRevision'] ?? null)
                || ($draft['baseRevision'] === 0 ? !array_key_exists('baseChecksum', $draft) || $draft['baseChecksum'] !== null : !ExtensionContract::Checksum($draft['baseChecksum'] ?? null))
                || !is_array($configuration['automations'] ?? null) || !array_is_list($configuration['automations']) || count($configuration['automations']) > 100
                || SequencePlan::Error($configuration) !== '') throw new \RuntimeException();
            $source['rules'] = count($configuration['automations']);
            $source['sequences'] = count($configuration['sequences'] ?? []);
            $source['localChanges'] = $source['yamlChecksum'] !== $draft['baseChecksum'];
            if (($draft['scope'] ?? '') !== $this->TransportScope()) $issue('scope_mismatch');
            if (($draft['draftChecksum'] ?? '') !== $source['yamlChecksum']) $issue('draft_mismatch');
            $snapshot = $attributes['EditorSnapshot'] !== '' ? $decode($attributes['EditorSnapshot']) : [];
            $source['catalogAvailable'] = ($snapshot['scope'] ?? '') === $this->TransportScope();
            if (!$source['catalogAvailable']) $issue('catalog_missing');
            foreach (['EditorWork.rule', 'EditorWork.sequence'] as $kind) if ($work[$kind] !== '' && ($decode($work[$kind])['dirty'] ?? false)) {
                $issue('open_input');
                break;
            }
            if ($work['EditorNavigation'] !== '' && !in_array('open_input', array_column($source['issues'], 'code'), true)) $issue('open_input');
            $state = $this->GetBuffer('TransportState');
            if ($state !== '' && (($decode($state)['workflow'] ?? null) !== null || ($decode($state)['pending'] ?? null) !== null)) $issue('busy');
            if ($this->GetBuffer('TransportDispatches') !== '' && json_decode($this->GetBuffer('TransportDispatches'), true, 8, JSON_THROW_ON_ERROR) !== []) $issue('busy');
        } catch (Throwable) {
            $issue('invalid_source');
        }
        return $source;
    }

    private function ValidMigrationSourceSummary(mixed $source, int $id): bool
    {
        $empty = $this->EmptyMigrationSource($id);
        if (!is_array($source) || count($source) !== count($empty) || array_diff(array_keys($source), array_keys($empty)) !== [] || $source['contractVersion'] !== SystemContract::VERSION || $source['sourceInstance'] !== $id) return false;
        foreach ($empty as $key => $value) if (gettype($source[$key]) !== gettype($value)) return false;
        if (!in_array($source['role'], ['legacy', 'unassigned'], true) || $source['rules'] < 0 || $source['rules'] > 100 || $source['sequences'] < 0 || $source['sequences'] > 32
            || !$source['requiresValidation'] || $source['bindingsVerified'] || !array_is_list($source['issues'])
            || !ExtensionContract::Checksum($source['fingerprint']) || !ExtensionContract::Checksum($source['yamlChecksum'])) return false;
        foreach ($source['issues'] as $issue) if (!is_array($issue) || !isset(SystemMigrationPreview::MESSAGES[$issue['code'] ?? ''])
            || $issue !== SystemMigrationPreview::Issue($issue['code'])) return false;
        return true;
    }
}
