<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

require_once __DIR__ . '/WorkspaceState.php';
require_once __DIR__ . '/NativeVariableBindings.php';

/** Preparation APIs for the central editor. They never publish or claim a connection. */
trait SystemWorkspace
{
    public function GetWorkspaceOverview(): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function (): string
        {
            try {
                $state = $this->WithWorkspaceLock(fn (): array => $this->ReadWorkspace());
                $systems = [];
                foreach ($state['systems'] as $id => $record) {
                    $current = $this->WorkspaceConnectionCurrent($record['binding']);
                    $info = $current ? $this->WorkspaceInfo($record['binding']) : null;
                    $configuration = $record['data']['document']['editorDraft']['configuration'] ?? [];
                    $systems[] = ['systemId' => $id, 'name' => $record['name'], 'binding' => $record['binding'],
                        'fingerprint'        => WorkspaceState::Fingerprint($record), 'connectionCurrent' => $current && $info !== null,
                        'online'             => $info['state']['bridgeOnline'] ?? false, 'confirmed' => $info['state']['extensionConfirmed'] ?? false,
                        'storedRevision'     => $info['state']['extension']['storedRevision'] ?? null,
                        'activeRevision'     => ($info['state']['runtimeConfirmed'] ?? false) ? ($info['state']['extension']['activeRevision'] ?? null) : null,
                        'rules'              => count($configuration['automations'] ?? []), 'sequences' => count($configuration['sequences'] ?? []),
                        'hasDocument'        => $record['data']['document'] !== null, 'openInput' => WorkspaceState::Dirty($record)];
                }
                $this->DebugEvent('AKTION', 'Zentrale Systemübersicht gelesen. Entwürfe und Z2M bleiben unverändert.', ['Systeme' => count($systems)]);
                return $this->WorkspaceResult('', ['fingerprint' => WorkspaceState::Fingerprint($state), 'selectedSystem' => $state['selectedSystem'], 'systems' => $systems]);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function ConfigureWorkspace(string $Connections, string $Fingerprint, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($Connections, $Fingerprint, $OperationID): string
        {
            try {
                $operation = $this->WorkspaceOperation($OperationID, ['configure', $Connections, $Fingerprint]);
                $replay = $this->WithWorkspaceLock(fn (): ?string => $this->WorkspaceReplay($this->ReadWorkspace(), $operation));
                if ($replay !== null) return $replay;
                $ids = $this->DecodeWorkspaceInput($Connections, 1024);
                if (!array_is_list($ids) || count($ids) < 1 || count($ids) > SystemContract::MAX_SYSTEMS) throw new RuntimeException(count($ids) > SystemContract::MAX_SYSTEMS ? 'system_limit' : 'invalid_request');
                foreach ($ids as $id) if (!is_int($id) || $id < 10000 || $id > 59999) throw new RuntimeException('invalid_request');
                if (count(array_unique($ids, SORT_REGULAR)) !== count($ids)) throw new RuntimeException('duplicate_connectionInstance');
                $records = [];
                foreach ($ids as $id) {
                    $binding = $this->WorkspaceConnectionBinding($id);
                    if ($binding === null) throw new RuntimeException('connection_unavailable');
                    $records[] = WorkspaceState::Record($binding, IPS_GetObject($id)['ObjectName']);
                }
                $error = SystemContract::RegistryError(array_column($records, 'binding'));
                if ($error !== '') throw new RuntimeException($error);
                return $this->WithWorkspaceLock(function () use ($records, $Fingerprint, $operation): string
                {
                    $state = $this->ReadWorkspace();
                    $replay = $this->WorkspaceReplay($state, $operation);
                    if ($replay !== null) return $replay;
                    if (!hash_equals(WorkspaceState::Fingerprint($state), $Fingerprint)) throw new RuntimeException('stale_workspace');
                    $next = [];
                    foreach ($records as $record) {
                        $binding = $record['binding'];
                        if (!$this->WorkspaceConnectionCurrent($binding)) throw new RuntimeException('binding_changed');
                        $id = $binding['systemId'];
                        if (isset($state['systems'][$id])) {
                            $prior = $state['systems'][$id];
                            if ($prior['binding'] !== $binding) {
                                if ($binding['generation'] <= $prior['binding']['generation']) throw new RuntimeException('binding_changed');
                                if (WorkspaceState::Dirty($prior)) throw new RuntimeException('open_input');
                            }
                            $prior['binding'] = $binding;
                            $prior['name'] = $record['name'];
                            $record = $prior;
                        }
                        $next[$id] = $record;
                    }
                    if (array_diff(array_keys($state['systems']), array_keys($next)) !== []) throw new RuntimeException('remove_blocked');
                    $state['systems'] = $next;
                    $state['selectedSystem'] = $state['selectedSystem'] ?: array_key_first($next);
                    $response = $this->CommitWorkspace($state, $operation, ['fingerprint' => WorkspaceState::Fingerprint($state)]);
                    foreach ($records as $record) $this->RegisterReference($record['binding']['connectionInstance']);
                    $this->DebugEvent('AKTION', 'Systemverbindungen im lokalen Arbeitsbereich zugeordnet. Es wurden keine Altinstanzen übernommen und keine Freigaben erteilt.', ['Systeme' => count($next)]);
                    return $response;
                });
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function GetWorkspaceSystem(string $SystemID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID): string
        {
            try {
                $record = $this->WithWorkspaceLock(fn (): array => $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID));
                $this->DebugEvent('AKTION', 'Lokalen Systementwurf samt Auswahl und offenen Eingaben gelesen; nichts übertragen.', ['Systembezug' => $SystemID]);
                return $this->WorkspaceResult('', ['systemId' => $SystemID, 'generation' => $record['binding']['generation'],
                    'fingerprint'                             => WorkspaceState::Fingerprint($record), 'binding' => $record['binding'], 'name' => $record['name'],
                    'connectionCurrent'                       => $this->WorkspaceConnectionCurrent($record['binding']), 'requiresValidation' => true, 'data' => $record['data']]);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function UpdateWorkspaceSystem(string $SystemID, int $Generation, string $Fingerprint, string $Data, string $OperationID, bool $ChangePermissions = false): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $Data, $OperationID, $ChangePermissions): string
        {
            try {
                $operation = $this->WorkspaceOperation($OperationID, ['update', $SystemID, $Generation, $Fingerprint, $Data, $ChangePermissions]);
                $data = $this->DecodeWorkspaceInput($Data, WorkspaceState::MAX_DATA);
                if (WorkspaceState::DataError($data) !== '') throw new RuntimeException('invalid_data');
                return $this->WithWorkspaceLock(function () use ($SystemID, $Generation, $Fingerprint, $data, $operation, $ChangePermissions): string
                {
                    $state = $this->ReadWorkspace();
                    $replay = $this->WorkspaceReplay($state, $operation);
                    if ($replay !== null) return $replay;
                    $record = $this->WorkspaceRecord($state, $SystemID, $Generation, $Fingerprint);
                    if (!$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
                    if ($record['data']['document'] !== null && !$ChangePermissions
                        && WorkspaceState::Permissions($record['data']) !== WorkspaceState::Permissions($data)) throw new RuntimeException('permission_changed');
                    $record['data'] = $data;
                    $state['systems'][$SystemID] = $record;
                    $response = $this->CommitWorkspace($state, $operation, ['fingerprint' => WorkspaceState::Fingerprint($record), 'requiresValidation' => true]);
                    $this->DebugEvent('AKTION', 'Systementwurf und Bearbeitungsdaten lokal übernommen. Andere Systeme und vorhandene Empfangsfreigaben wurden nicht automatisch geändert; Z2M bleibt unverändert.', ['Systembezug' => $SystemID, 'FreigabenänderungBestätigt' => $ChangePermissions]);
                    return $response;
                });
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function SelectWorkspaceSystem(string $SystemID, string $Fingerprint, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Fingerprint, $OperationID): string
        {
            try {
                $operation = $this->WorkspaceOperation($OperationID, ['select', $SystemID, $Fingerprint]);
                return $this->WithWorkspaceLock(function () use ($SystemID, $Fingerprint, $operation): string
                {
                    $state = $this->ReadWorkspace();
                    $replay = $this->WorkspaceReplay($state, $operation);
                    if ($replay !== null) return $replay;
                    if (!hash_equals(WorkspaceState::Fingerprint($state), $Fingerprint)) throw new RuntimeException('stale_workspace');
                    $this->WorkspaceRecord($state, $SystemID);
                    if ($state['selectedSystem'] !== $SystemID && WorkspaceState::Dirty($state['systems'][$state['selectedSystem']])) throw new RuntimeException('open_input');
                    $state['selectedSystem'] = $SystemID;
                    $response = $this->CommitWorkspace($state, $operation, ['fingerprint' => WorkspaceState::Fingerprint($state)]);
                    $this->DebugEvent('AKTION', 'Systemauswahl geändert. Die getrennten Entwürfe und Bearbeitungen bleiben erhalten.', ['Systembezug' => $SystemID]);
                    return $response;
                });
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function RefreshWorkspaceCatalog(string $SystemID, int $Generation, string $Fingerprint, string $OperationID): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $OperationID): string
        {
            try {
                $operation = $this->WorkspaceOperation($OperationID, ['catalog', $SystemID, $Generation, $Fingerprint]);
                $replay = $this->WithWorkspaceLock(fn (): ?string => $this->WorkspaceReplay($this->ReadWorkspace(), $operation));
                if ($replay !== null) return $replay;
                $record = $this->WithWorkspaceLock(fn (): array => $this->WorkspaceRecord($this->ReadWorkspace(), $SystemID, $Generation, $Fingerprint));
                $catalog = $this->WorkspaceCatalog($record['binding']);
                if ($catalog === null) throw new RuntimeException('catalog_unavailable');
                $instances = [];
                foreach (array_keys(Z2MInstanceIdentity::TYPES) as $type) foreach (IPS_GetInstanceListByModuleID($type) as $id) {
                    $scope = Z2MInstanceIdentity::Read($id, $record['binding']['mqttParent'], $record['binding']['baseTopic']);
                    if ($scope !== null && $scope['type'] !== 'bridge') $instances[$id] = $scope;
                }
                $bindings = NativeVariableBindings::Collect($instances);
                // Never call another module while holding the workspace semaphore.
                return $this->WithWorkspaceLock(function () use ($SystemID, $Generation, $Fingerprint, $catalog, $bindings, $operation): string
                {
                    $state = $this->ReadWorkspace();
                    $replay = $this->WorkspaceReplay($state, $operation);
                    if ($replay !== null) return $replay;
                    $record = $this->WorkspaceRecord($state, $SystemID, $Generation, $Fingerprint);
                    if (!$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
                    $record['catalog'] = $catalog;
                    $record['nativeBindings'] = $bindings;
                    $state['systems'][$SystemID] = $record;
                    $response = $this->CommitWorkspace($state, $operation, ['fingerprint' => WorkspaceState::Fingerprint($record)]);
                    $this->DebugEvent(
                        'AKTION',
                        'Bestätigten Verbindungskatalog und native Variablenzuordnungen lokal eingelesen. Entwurf, Auswahl und offene Eingaben bleiben erhalten. Es wurde keine MQTT-Anfrage gesendet.',
                        ['Systembezug' => $SystemID, 'Geräte' => count($catalog['devices']), 'Gruppen' => count($catalog['groups'])]
                    );
                    return $response;
                });
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    public function GetWorkspaceEntities(string $SourceSystemID, string $TargetSystemID, string $Use, int $Offset = 0, int $Limit = 50): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SourceSystemID, $TargetSystemID, $Use, $Offset, $Limit): string
        {
            try {
                if (!in_array($Use, ['trigger', 'condition', 'action'], true) || $Offset < 0 || $Limit < 1 || $Limit > 100) throw new RuntimeException('invalid_request');
                $state = $this->WithWorkspaceLock(fn (): array => $this->ReadWorkspace());
                $source = $this->WorkspaceRecord($state, $SourceSystemID);
                $target = $this->WorkspaceRecord($state, $TargetSystemID);
                if ($SourceSystemID !== $TargetSystemID) {
                    if ($Use !== 'action') throw new RuntimeException('remote_source');
                    if (!WorkspaceState::Allows($source, $target)) throw new RuntimeException('permission_missing');
                }
                if (!$this->WorkspaceConnectionCurrent($source['binding'])) throw new RuntimeException('binding_changed');
                $catalog = $this->WorkspaceCatalog($target['binding']);
                if ($catalog === null || $target['catalog'] === null || WorkspaceState::Fingerprint($catalog) !== WorkspaceState::Fingerprint($target['catalog'])) throw new RuntimeException('catalog_changed');
                $snapshot = $this->AddEditorScenes($this->AddEditorGroupTargets($catalog));
                $items = [];
                foreach ($snapshot['devices'] as $key => $entry) {
                    $resolver = static function (string $property) use ($target, $entry, $key): ?array
                    {
                        $control = $entry['properties'][$property]['controlProperty'] ?? $property;
                        $binding = $target['nativeBindings'][$key][$control] ?? null;
                        return $binding === null ? null : NativeVariableBindings::Resolve($binding, $target['binding'], (string) $key, $control);
                    };
                    $properties = $this->EditorProperties($snapshot, (string) $key, $Use === 'action' ? 'Target' : 'Source', $resolver);
                    if ($properties === []) continue;
                    $group = str_starts_with((string) $key, 'group.');
                    $identity = $group ? substr($key, 6) : (string) $key;
                    $items[] = ['key' => SystemContract::EntityKey($TargetSystemID, $group ? 'group' : 'device', $identity),
                        'systemId'    => $TargetSystemID, 'kind' => $group ? 'group' : 'device', 'id' => $group ? (int) $identity : $identity,
                        'name'        => $entry['name'], 'properties' => $properties];
                }
                $this->DebugEvent(
                    'AKTION',
                    'Systemgebundene Geräteauswahl mit aktuellen Schaltrechten erstellt; nur ausdrücklich freigegebene fremde Ziele berücksichtigt.',
                    ['Quellsystem' => $SourceSystemID, 'Zielsystem' => $TargetSystemID, 'Verwendung' => $Use, 'Treffer' => count($items)]
                );
                return $this->WorkspaceResult('', ['sourceSystemId' => $SourceSystemID, 'targetSystemId' => $TargetSystemID,
                    'generation'                                    => $target['binding']['generation'], 'fingerprint' => WorkspaceState::Fingerprint($target),
                    'offset'                                        => $Offset, 'total' => count($items), 'hasMore' => $Offset + $Limit < count($items), 'items' => array_slice($items, $Offset, $Limit)]);
            } catch (Throwable $exception) {
                return $this->WorkspaceFailure($exception);
            }
        });
    }

    protected function ReadWorkspaceConnectionInfo(int $id): array
    {
        return $this->DecodeWorkspaceInput(Z2MC_GetConnectionInfo($id), 16384);
    }

    protected function ReadWorkspaceConnectionCatalog(int $id): array
    {
        return $this->DecodeWorkspaceInput(Z2MC_GetDeviceCatalog($id), WorkspaceState::MAX_RESPONSE);
    }

    private function WorkspaceConnectionBinding(int $id): ?array
    {
        try {
            if (!IPS_InstanceExists($id)) return null;
            $instance = IPS_GetInstance($id);
            if ($instance['ModuleInfo']['ModuleID'] !== SystemContract::CONNECTION_MODULE) return null;
            $configuration = json_decode(IPS_GetConfiguration($id), true, 16, JSON_THROW_ON_ERROR);
            $parent = $instance['ConnectionID'];
            if (!IPS_InstanceExists($parent) || !in_array(IPS_GetInstance($parent)['ModuleInfo']['ModuleID'], [Z2MProtocol::MQTT_CLIENT, Z2MProtocol::MQTT_SERVER], true)) return null;
            $binding = ['version' => SystemContract::VERSION, 'systemId' => $configuration['SystemID'] ?? null,
                'sourceInstance'  => 0, 'connectionInstance' => $id, 'mqttParent' => $parent, 'baseTopic' => $configuration['BaseTopic'] ?? null,
                'ownerInstance'   => $this->InstanceID, 'generation' => $configuration['Generation'] ?? null];
            return SystemContract::RegistryError([$binding]) === '' ? $binding : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function WorkspaceConnectionCurrent(array $binding): bool
    {
        return $this->WorkspaceConnectionBinding($binding['connectionInstance']) === $binding;
    }

    private function WorkspaceInfo(array $binding): ?array
    {
        try {
            if (!$this->WorkspaceConnectionCurrent($binding)) return null;
            $info = $this->ReadWorkspaceConnectionInfo($binding['connectionInstance']);
            return ($info['version'] ?? null) === SystemContract::VERSION && ($info['systemId'] ?? null) === $binding['systemId']
                && ($info['generation'] ?? null) === $binding['generation'] && ($info['mqttParent'] ?? null) === $binding['mqttParent']
                && ($info['baseTopic'] ?? null) === $binding['baseTopic'] && is_array($info['state'] ?? null)
                && $this->WorkspaceConnectionCurrent($binding) ? $info : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function WorkspaceCatalog(array $binding): ?array
    {
        try {
            $info = $this->WorkspaceInfo($binding);
            if ($info === null || !($info['state']['extensionConfirmed'] ?? false) || !($info['state']['bridgeOnline'] ?? false)) return null;
            $catalog = $this->ReadWorkspaceConnectionCatalog($binding['connectionInstance']);
            return ($catalog['version'] ?? null) === SystemContract::VERSION && ($catalog['systemId'] ?? null) === $binding['systemId']
                && ($catalog['generation'] ?? null) === $binding['generation'] && ($catalog['available'] ?? null) === true
                && is_int($catalog['loadedAt'] ?? null) && is_array($catalog['devices'] ?? null) && count($catalog['devices']) <= 256
                && is_array($catalog['groups'] ?? null) && count($catalog['groups']) <= 256 && is_array($catalog['scenes'] ?? null) && count($catalog['scenes']) <= 512
                && $this->WorkspaceConnectionCurrent($binding) ? $catalog : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function WorkspaceRecord(array $state, string $id, ?int $generation = null, ?string $fingerprint = null): array
    {
        if (!SystemContract::SystemID($id) || !isset($state['systems'][$id])) throw new RuntimeException('system_missing');
        $record = $state['systems'][$id];
        if (($generation !== null && $record['binding']['generation'] !== $generation)
            || ($fingerprint !== null && !hash_equals(WorkspaceState::Fingerprint($record), $fingerprint))) throw new RuntimeException('stale_system');
        return $record;
    }

    private function WorkspaceOperation(string $id, array $request): array
    {
        if (preg_match('/\A[a-f0-9]{32}\z/D', $id) !== 1) throw new RuntimeException('invalid_request');
        return ['key' => hash('sha256', $id), 'request' => WorkspaceState::Fingerprint($request)];
    }

    private function WorkspaceReplay(array $state, array $operation): ?string
    {
        $receipt = $state['receipts'][$operation['key']] ?? null;
        if ($receipt === null) return null;
        if (!hash_equals($receipt['request'], $operation['request'])) throw new RuntimeException('operation_conflict');
        $this->DebugEvent('AKTION', 'Bereits abgeschlossenen lokalen Auftrag erneut bestätigt; keine Daten ein zweites Mal übernommen.');
        return $receipt['response'];
    }

    private function CommitWorkspace(array $state, array $operation, array $fields): string
    {
        $response = $this->WorkspaceResult('', $fields);
        $state['receipts'][$operation['key']] = ['request' => $operation['request'], 'response' => $response];
        $state['receipts'] = array_slice($state['receipts'], -32, null, true);
        $this->WriteWorkspace($state);
        return $response;
    }

    private function DecodeWorkspaceInput(string $text, int $limit): array
    {
        if (strlen($text) > $limit) throw new RuntimeException('invalid_request');
        $value = json_decode($text, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($value)) throw new RuntimeException('invalid_request');
        return $value;
    }

    private function ReadWorkspace(): array
    {
        $raw = $this->ReadAttributeString('SystemWorkspace');
        if ($raw === '') return WorkspaceState::Empty();
        try {
            $state = $this->DecodeWorkspaceInput($raw, WorkspaceState::MAX_BYTES);
        } catch (Throwable) {
            throw new RuntimeException('workspace_corrupt');
        }
        $error = WorkspaceState::Error($state);
        if ($error !== '') throw new RuntimeException($error);
        foreach ($state['systems'] as $record) if ($record['binding']['ownerInstance'] !== $this->InstanceID) throw new RuntimeException('workspace_corrupt');
        return $state;
    }

    private function WriteWorkspace(array $state): void
    {
        $error = WorkspaceState::Error($state);
        if ($error !== '') throw new RuntimeException($error);
        $json = json_encode($state, WorkspaceState::JSON_FLAGS);
        if (strlen($json) > WorkspaceState::MAX_BYTES) throw new RuntimeException('invalid_request');
        if ($json !== $this->ReadAttributeString('SystemWorkspace')) $this->WriteAttributeString('SystemWorkspace', $json);
    }

    private function WorkspaceResult(string $error, array $fields = []): string
    {
        $json = json_encode(['version' => WorkspaceState::VERSION, 'ok' => $error === '', 'error' => $error,
            'message'                  => WorkspaceState::MESSAGES[$error] ?? WorkspaceState::MESSAGES['invalid_request']] + $fields, WorkspaceState::JSON_FLAGS);
        return strlen($json) <= WorkspaceState::MAX_RESPONSE ? $json : $this->WorkspaceResult('response_limit');
    }

    private function WorkspaceFailure(Throwable $exception): string
    {
        $error = $exception instanceof RuntimeException && isset(WorkspaceState::MESSAGES[$exception->getMessage()]) ? $exception->getMessage() : 'invalid_request';
        $this->DebugEvent('FEHLER', WorkspaceState::MESSAGES[$error], ['Ursache' => $error]);
        return $this->WorkspaceResult($error);
    }

    private function WithWorkspaceLock(callable $callback): mixed
    {
        $name = 'Z2MA.Workspace.' . $this->InstanceID;
        if (!IPS_SemaphoreEnter($name, 1000)) throw new RuntimeException('stale_workspace');
        try {
            return $callback();
        } finally {
            IPS_SemaphoreLeave($name);
        }
    }
}
