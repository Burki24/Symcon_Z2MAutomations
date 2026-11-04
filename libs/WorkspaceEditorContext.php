<?php

declare(strict_types=1);

namespace Z2MAutomations;

use RuntimeException;
use Throwable;

/** An editor projection; legacy attributes are never used as a switching buffer. */
trait WorkspaceEditorContext
{
    private const WORKSPACE_EDITOR_ACTIONS = ['SelectAutomation', 'OpenAutomationEditor', 'UpdateAutomation', 'DeleteAutomation', 'CopyAutomation',
        'SaveScheduledAutomation', 'CreateSimpleAutomation', 'SelectReusableSequence', 'SaveReusableSequence', 'DeleteReusableSequence',
        'InspectReusableSequenceActions', 'ConfigureSequenceCall', 'TrackEditorInput', 'NavigateEditor', 'ResolveEditorNavigation', 'EditorWorkReady',
        'SelectEditorDevice', 'ConfigureEditorItem', 'ConfigureEditorSchedule', 'ConfigureEditorSolar', 'ConfigureEditorWeekdays',
        'SelectEditorConditionProperty', 'InspectEditorTimes', 'InspectEditorActions', 'InspectEditorTriggers', 'InspectEditorConditions',
        'ChangeEditorCommand', 'GetEditorItemForm', 'FilterAutomations'];

    public function WorkspaceEditorAction(string $SystemID, int $Generation, string $Fingerprint, string $Action, string $Arguments): string
    {
        return $this->TraceModuleCall(__FUNCTION__, __LINE__, function () use ($SystemID, $Generation, $Fingerprint, $Action, $Arguments): string
        {
            try {
                if (!in_array($Action, self::WORKSPACE_EDITOR_ACTIONS, true) || strlen($Arguments) > WorkspaceState::MAX_RESPONSE) throw new RuntimeException('invalid_request');
                $args = json_decode($Arguments, true, 32, JSON_THROW_ON_ERROR);
                if (!is_array($args) || !array_is_list($args)) throw new RuntimeException('invalid_request');
                if ($this->ReadWorkspace()['selectedSystem'] !== $SystemID) throw new RuntimeException('stale_system');
                $result = $this->WithWorkspaceEditor($SystemID, $Generation, $Fingerprint, true, fn (): mixed => $this->{$Action}(...$args), true);
                if ($Action === 'GetEditorItemForm' && is_string($result)) {
                    $fields = json_decode($result, true, 32, JSON_THROW_ON_ERROR);
                    $this->BindWorkspaceCallbacks($fields, $SystemID, $Generation);
                    $result = json_encode($fields, JSON_THROW_ON_ERROR);
                }
                return json_encode(['result' => $result, 'fingerprint' => WorkspaceState::Fingerprint($this->ReadWorkspace()['systems'][$SystemID])], JSON_THROW_ON_ERROR);
            } catch (Throwable $exception) {
                $this->WorkspaceFailure($exception);
                $this->UpdateFormField('WorkspaceNotice', 'caption', 'Die Bearbeitung wurde nicht übernommen. Bitte System und aktuellen Entwurf neu auswählen. Ihre Eingaben werden nicht in ein anderes System geschrieben.');
                return json_encode(['result' => false, 'fingerprint' => $Fingerprint], JSON_THROW_ON_ERROR);
            }
        });
    }

    private function WorkspaceMode(): bool
    {
        return $this->workspaceEditorContext !== null || $this->ReadWorkspace()['systems'] !== [] || ($this->OwnershipMarker()['role'] ?? '') === 'manager';
    }

    private function ReadEditorAttribute(string $name): string
    {
        return $this->workspaceEditorContext === null ? $this->ReadAttributeString($name) : ($this->workspaceEditorContext['attributes'][$name] ?? '');
    }

    private function WriteEditorAttribute(string $name, string $value): void
    {
        if ($this->workspaceEditorContext === null) $this->WriteAttributeString($name, $value);
        else $this->workspaceEditorContext['attributes'][$name] = $value;
    }

    private function ReadEditorBuffer(string $name): string
    {
        if ($this->workspaceEditorContext === null) return $this->GetBuffer($name);
        if ($name === 'DesignerSessions') {
            $sessions = json_decode($this->GetBuffer($name) ?: '[]', true, 16, JSON_THROW_ON_ERROR);
            foreach ($sessions as &$session) $session['receipts'] = array_values(array_filter($session['receipts'] ?? [], fn (array $receipt): bool => ($receipt['workspaceScope'] ?? '') === $this->workspaceEditorContext['scope']));
            unset($session);
            return json_encode($sessions, JSON_THROW_ON_ERROR);
        }
        if ($name === 'TransportState') return json_encode($this->workspaceEditorContext['state'], JSON_THROW_ON_ERROR);
        return $this->workspaceEditorContext['buffers'][$name] ?? '';
    }

    private function WriteEditorBuffer(string $name, string $value): void
    {
        if ($this->workspaceEditorContext !== null && $name === 'DesignerSessions') {
            $old = json_decode($this->GetBuffer($name) ?: '[]', true, 16, JSON_THROW_ON_ERROR);
            $sessions = json_decode($value, true, 16, JSON_THROW_ON_ERROR);
            foreach ($sessions as &$session) {
                $index = array_search($session['hash'], array_column($old, 'hash'), true);
                $other = array_values(array_filter($old[$index]['receipts'] ?? [], fn (array $receipt): bool => ($receipt['workspaceScope'] ?? '') !== $this->workspaceEditorContext['scope']));
                foreach ($session['receipts'] as &$receipt) $receipt['workspaceScope'] = $this->workspaceEditorContext['scope'];
                unset($receipt);
                $session['receipts'] = array_slice(array_merge($other, $session['receipts']), -64);
            }
            unset($session);
            $this->SetBuffer($name, json_encode($sessions, JSON_THROW_ON_ERROR));
        }
        elseif ($this->workspaceEditorContext === null) $this->SetBuffer($name, $value);
        else $this->workspaceEditorContext['buffers'][$name] = $value;
    }

    private function UpdateEditorFormField(string $name, string $parameter, mixed $value): void
    {
        if ($this->workspaceEditorContext !== null && $this->workspaceEditorContext['native']) {
            $values = [$value];
            if (is_string($value) && in_array($parameter, ['items', 'columns'], true)) $values = [json_decode($value, true, 64, JSON_THROW_ON_ERROR)];
            $this->BindWorkspaceCallbacks($values, $this->workspaceEditorContext['id'], $this->workspaceEditorContext['record']['binding']['generation']);
            $value = is_array($values[0]) && is_string($value) ? json_encode($values[0], JSON_THROW_ON_ERROR) : $values[0];
        }
        if ($this->workspaceEditorContext === null || $this->workspaceEditorContext['native']) $this->UpdateFormField($name, $parameter, $value);
    }

    private function EditorBaseTopic(): string
    {
        return $this->workspaceEditorContext['record']['binding']['baseTopic'] ?? $this->ReadPropertyString('BaseTopic');
    }

    private function WorkspaceEditorScope(array $record): string
    {
        return json_encode(['workspace', $this->InstanceID, $record['binding'], $record['ownershipEpoch'] ?? ''], JSON_THROW_ON_ERROR);
    }

    private function WithWorkspaceEditor(string $id, ?int $generation, ?string $fingerprint, bool $write, callable $callback, bool $native = false, ?array $readWorkspace = null): mixed
    {
        if ($this->workspaceEditorContext !== null) throw new RuntimeException('invalid_request');
        $workspace = !$write && $readWorkspace !== null ? $readWorkspace : $this->WithWorkspaceLock(fn (): array => $this->ReadWorkspace());
        $record = $this->WorkspaceRecord($workspace, $id, $generation, $fingerprint);
        if ($write && !$this->WorkspaceConnectionCurrent($record['binding'])) throw new RuntimeException('binding_changed');
        $before = WorkspaceState::Fingerprint($record);
        $scope = $this->WorkspaceEditorScope($record);
        $this->DebugEvent('EMPFANG', 'Lokalen Entwurf für genau dieses Z2M-System geöffnet. Keine Anfrage an Z2M.', ['Systembezug' => $id, 'Generation' => $record['binding']['generation'], 'Bearbeitung' => $write]);
        $views = json_decode($this->GetBuffer('WorkspaceEditorViews') ?: '[]', true, 32, JSON_THROW_ON_ERROR);
        $view = ($views[$id]['fingerprint'] ?? '') === $before ? $views[$id] : ['attributes' => [], 'buffers' => []];
        $info = $this->WorkspaceInfo($record['binding']);
        $state = ['scope'  => $scope, 'mqttConnected' => $info['state']['mqttConnected'] ?? false, 'bridgeState' => 'unknown',
            'bridgeOnline' => false, 'version' => '', 'externalJS' => null, 'extensionInstalled' => false, 'pending' => null,
            'transaction'  => '', 'health' => 'idle'] + $this->NewExtensionState();
        $this->workspaceEditorContext = ['id' => $id, 'record' => $record, 'workspace' => $workspace, 'state' => $state,
            'attributes'                      => $view['attributes'], 'buffers' => $view['buffers'], 'info' => $info, 'scope' => $scope, 'catalogAvailable' => false, 'native' => $native];
        try {
            $document = $record['data']['document'];
            if ($document !== null) {
                $this->WriteEditorAttribute('YamlDraft', $document['yaml']);
                $draft = $document['editorDraft'] + ['scope' => $scope, 'session' => ''];
                $this->WriteEditorAttribute('EditorDraft', json_encode($draft, WorkspaceState::JSON_FLAGS));
                if ($view['attributes'] === []) foreach (['rule' => 'EditorSelection', 'sequence' => 'SequenceSelection'] as $kind => $name) {
                    $selected = $record['data']['selection'][$kind];
                    $this->WriteEditorAttribute($name, $selected === '' ? '' : json_encode(['id' => $selected, 'checksum' => $draft['draftChecksum'], 'token' => bin2hex(random_bytes(16))], JSON_THROW_ON_ERROR));
                }
            }
            $snapshot = $this->WorkspaceEditorSnapshot($workspace, $record);
            $snapshot = $this->FilterEditorActions($snapshot);
            $this->workspaceEditorContext['snapshot'] = $snapshot;
            $this->WriteEditorAttribute('EditorSnapshot', json_encode($snapshot, JSON_THROW_ON_ERROR));
            if ($view['attributes'] === [] && $document !== null) foreach (['rule', 'sequence'] as $kind) {
                $work = $record['data']['work'][$kind];
                if ($work !== null) $this->WriteEditorBuffer('EditorWork.' . $kind, json_encode($work + ['context' => $this->EditorWorkContext($kind), 'unsettled' => ''], JSON_THROW_ON_ERROR));
            }
            $result = $callback();
            $attributes = $this->workspaceEditorContext['attributes'];
            unset($attributes['EditorSnapshot']);
            $views[$id] = ['fingerprint' => $before, 'attributes' => $attributes, 'buffers' => $this->workspaceEditorContext['buffers']];
            if (strlen(json_encode($views, JSON_THROW_ON_ERROR)) > WorkspaceState::MAX_BYTES) throw new RuntimeException('response_limit');
            if ($write) {
                $data = $this->WorkspaceEditorData($record['data']);
                $changed = $data !== $record['data'];
                $this->WithWorkspaceLock(function () use ($id, $generation, $before, $data, &$record): void
                {
                    $current = $this->ReadWorkspace();
                    $record = $this->WorkspaceRecord($current, $id, $generation, $before);
                    if (WorkspaceState::Permissions($record['data']) !== WorkspaceState::Permissions($data)) throw new RuntimeException('permission_changed');
                    if (WorkspaceState::DataError($data) !== '') throw new RuntimeException('invalid_data');
                    $record['data'] = $data;
                    $current['systems'][$id] = $record;
                    $this->WriteWorkspace($current);
                });
                $this->DebugEvent('AKTION', $changed ? 'Bearbeitung ausschließlich im gewählten Systementwurf übernommen. Andere Systeme und Z2M bleiben unverändert.' : 'Systemgebundenen Editoraufruf abgeschlossen; Entwurf und Z2M bleiben unverändert.', ['Systembezug' => $id]);
            }
            $views[$id]['fingerprint'] = WorkspaceState::Fingerprint($record);
            $this->SetBuffer('WorkspaceEditorViews', json_encode($views, JSON_THROW_ON_ERROR));
            if ($write && $native) {
                $this->UpdateFormField('WorkspaceEditorFingerprint', 'value', WorkspaceState::Fingerprint($record));
                $this->UpdateFormField('WorkspaceFingerprint', 'value', WorkspaceState::Fingerprint($this->ReadWorkspace()));
            }
            return $result;
        } finally {
            $this->workspaceEditorContext = null;
        }
    }

    private function WorkspaceEditorData(array $data): array
    {
        $raw = $this->ReadEditorAttribute('EditorDraft');
        if ($raw !== '') {
            $draft = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            $data['document'] = ['yaml' => $this->ReadEditorAttribute('YamlDraft'), 'editorDraft' => array_intersect_key($draft, array_flip(['configuration', 'baseRevision', 'baseChecksum', 'draftChecksum']))];
            foreach (['rule' => 'EditorSelection', 'sequence' => 'SequenceSelection'] as $kind => $name) {
                $selected = json_decode($this->ReadEditorAttribute($name) ?: '[]', true, 8, JSON_THROW_ON_ERROR);
                $data['selection'][$kind] = $selected['id'] ?? '';
                $work = json_decode($this->ReadEditorBuffer('EditorWork.' . $kind) ?: 'null', true, 32, JSON_THROW_ON_ERROR);
                $data['work'][$kind] = $work === null ? null : array_intersect_key($work, ['dirty' => true, 'input' => true]);
            }
        }
        return $data;
    }

    private function WorkspaceEditorSnapshot(array $workspace, array $source): array
    {
        $snapshot = ['scope' => $this->workspaceEditorContext['scope'], 'devices' => [], 'groups' => [], 'scenes' => [], 'loadedAt' => null];
        foreach ($workspace['systems'] as $id => $target) {
            $local = $id === $source['binding']['systemId'];
            if (!$local && !WorkspaceState::Allows($source, $target)) continue;
            $catalog = $target['catalog'];
            if ($catalog === null || !$this->WorkspaceConnectionCurrent($target['binding'])) continue;
            $confirmed = $this->WorkspaceCatalog($target['binding']);
            if ($confirmed === null || WorkspaceState::Fingerprint($confirmed) !== WorkspaceState::Fingerprint($catalog)) continue;
            $part = $this->AddEditorScenes($this->AddEditorGroupTargets($catalog));
            if ($local) {
                $snapshot['loadedAt'] = $catalog['loadedAt'];
                $this->workspaceEditorContext['catalogAvailable'] = true;
            }
            foreach ($part['devices'] as $key => $entry) {
                $entry['workspaceBinding'] = $target['binding'];
                $entry['workspaceKey'] = (string) $key;
                $entry['bindings'] = $target['nativeBindings'][$key] ?? [];
                if (!$local) {
                    $entry['remoteTarget'] = ['type' => str_starts_with((string) $key, 'group.') ? 'group' : 'device',
                        'id'                         => str_starts_with((string) $key, 'group.') ? (int) substr($key, 6) : $key, 'system' => $target['binding']['baseTopic']];
                    $entry['name'] = $target['binding']['baseTopic'] . ' · ' . $entry['name'];
                    $entry['triggers'] = $entry['numeric'] = [];
                    foreach ($entry['properties'] as &$meta) $meta['access'] &= 2;
                    unset($meta);
                }
                $snapshot['devices'][$local ? $key : $this->EditorTargetKey($entry['remoteTarget'])] = $entry;
            }
        }
        return $snapshot;
    }
}
