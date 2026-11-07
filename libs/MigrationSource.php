<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

trait MigrationSource
{
    /** Private kernel-to-kernel export, never attached to a browser route or debug payload. */
    public function ExportSystemMigration(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, fn (): string => $this->WithTransportLock(function (): string
        {
            $attributes = [];
            foreach (['YamlDraft', 'EditorDraft', 'EditorSnapshot', 'EditorSource', 'EditorTarget', 'EditorSelection', 'SequenceSelection',
                'ExecutionRule', 'EditorCondition', 'EditorConditionProperty', 'EditorModes'] as $name) $attributes[$name] = $this->ReadAttributeString($name);
            $work = [];
            foreach (['EditorWork.rule', 'EditorWork.sequence', 'EditorNavigation'] as $name) $work[$name] = $this->GetBuffer($name);
            $objects = [];
            foreach (IPS_GetChildrenIDs($this->InstanceID) as $id) {
                $object = IPS_GetObject($id);
                $entry = ['id' => $id, 'ident' => $object['ObjectIdent'], 'type' => $object['ObjectType'], 'parent' => $object['ParentID']];
                if ($object['ObjectType'] === 2) $entry['historicalValue'] = GetValue($id);
                $objects[] = $entry;
            }
            $this->DebugEvent('AKTION', 'Quellstand für eine private lokale Sicherung gelesen. Inhalte und Zugangsdaten werden nicht im Debug ausgegeben.');
            $imports = [];
            foreach (['EventImportPreview', 'EventImportNotice', 'EventImportReport'] as $name) $imports[$name] = $this->GetBuffer($name);
            $instances = [];
            foreach (array_keys(Z2MInstanceIdentity::TYPES) as $type) foreach (IPS_GetInstanceListByModuleID($type) as $id) {
                $scope = Z2MInstanceIdentity::Read($id, $this->GetParentID(), $this->ReadPropertyString('BaseTopic'));
                if ($scope !== null && $scope['type'] !== 'bridge') $instances[$id] = $scope;
            }
            $value = ['version'  => 1, 'summary' => $this->InspectMigrationSource(), 'configuration' => IPS_GetConfiguration($this->InstanceID),
                'attributes'     => $attributes, 'showYaml' => $this->ReadAttributeBoolean('ShowYaml'), 'work' => $work, 'objects' => $objects, 'historicalImports' => $imports,
                'nativeBindings' => NativeVariableBindings::Collect($instances)];
            $library = json_decode(file_get_contents(__DIR__ . '/../library.json'), true, 8, JSON_THROW_ON_ERROR);
            $value['libraryVersion'] = $library['version'];
            $json = json_encode($value, WorkspaceState::JSON_FLAGS);
            if (strlen($json) > WorkspaceState::MAX_RESPONSE * 4) throw new RuntimeException('archive_failed');
            return $json;
        }));
    }

    public function FenceSystemMigration(int $Owner, string $OperationID, string $Fingerprint, bool $Release): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Owner, $OperationID, $Fingerprint, $Release): string
        {
            try {
                if (!IPS_InstanceExists($Owner) || IPS_GetInstance($Owner)['ModuleInfo']['ModuleID'] !== SystemContract::SOURCE_MODULE || $Owner === $this->InstanceID
                    || preg_match('/\A[a-f0-9]{32}\z/D', $OperationID) !== 1 || !ExtensionContract::Checksum($Fingerprint)) throw new RuntimeException('invalid_request');
                $authority = json_decode(Z2MA_GetSystemMigrationAuthority($Owner, $OperationID), true, 16, JSON_THROW_ON_ERROR);
                $authorized = array_filter($authority['sources'] ?? [], fn (array $row): bool => $row['binding']['sourceInstance'] === $this->InstanceID && $row['fingerprint'] === $Fingerprint);
                if (($authority['ok'] ?? false) !== true || ($authority['phase'] ?? '') !== ($Release ? 'rolling_back' : 'applying') || count($authorized) !== 1) throw new RuntimeException('ownership_conflict');
                return $this->WithTransportLock(function () use ($Owner, $OperationID, $Fingerprint, $Release): string
                {
                    $expected = ['version' => 1, 'role' => 'legacy', 'owner' => $Owner, 'operation' => $OperationID, 'fingerprint' => $Fingerprint];
                    $marker = $this->OwnershipMarker();
                    if ($marker !== [] && $marker !== $expected) throw new RuntimeException('ownership_conflict');
                    if ($Release && $marker === []) return '{"ok":true}';
                    $source = $this->InspectMigrationSource();
                    if (!hash_equals($source['fingerprint'], $Fingerprint)) throw new RuntimeException('source_changed');
                    if ($Release) {
                        if ($marker !== []) {
                            // Preserve selections but invalidate their former editing capabilities.
                            foreach (['EditorSelection', 'SequenceSelection'] as $name) {
                                $raw = $this->ReadAttributeString($name);
                                if ($raw === '') continue;
                                $selected = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
                                if (isset($selected['token'])) $selected['token'] = '';
                                $this->WriteAttributeString($name, json_encode($selected, WorkspaceState::JSON_FLAGS));
                            }
                            $this->QuiesceMigrationSource();
                            $this->WriteOwnership([]);
                            $this->SetReceiveDataFilter($this->ReadPropertyBoolean('Active') && $this->ValidConfiguration()
                                ? Z2MProtocol::ReceiveFilter($this->ReadPropertyString('BaseTopic')) : Z2MProtocol::BLOCK_RECEIVE_FILTER);
                        }
                        $this->DebugEvent('AKTION', 'Bisherige Instanz wieder zur lokalen Bearbeitung freigegeben. Alte Browserzugänge und Betriebsbestätigungen bleiben ungültig; Z2M wurde nicht verändert.');
                    } else {
                        $issues = array_diff(array_column($source['issues'], 'code'), ['owned', 'catalog_missing']);
                        if ($issues !== []) throw new RuntimeException('source_blocked');
                        if ($marker === []) {
                            $work = [];
                            foreach (['EditorWork.rule', 'EditorWork.sequence', 'EditorNavigation'] as $name) $work[$name] = $this->GetBuffer($name);
                            $this->WriteAttributeString('MigrationSourceWork', json_encode($work, WorkspaceState::JSON_FLAGS));
                        }
                        $this->WriteOwnership($expected);
                        $this->QuiesceMigrationSource();
                        $this->DebugEvent('AKTION', 'Bisherigen Editor vor dem Besitzerwechsel gesperrt. Entwürfe und Objekt-IDs bleiben erhalten; keine Nachricht an Z2M gesendet.', ['Verwaltung' => $Owner]);
                    }
                    return json_encode(['ok' => true], JSON_THROW_ON_ERROR);
                });
            } catch (Throwable $exception) {
                return $this->MigrationFailure($exception);
            }
        });
    }
}
