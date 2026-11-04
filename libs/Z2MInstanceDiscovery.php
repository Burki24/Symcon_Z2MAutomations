<?php

declare(strict_types=1);

namespace Z2MAutomations;

require_once __DIR__ . '/Z2MInstanceIdentity.php';

trait Z2MInstanceDiscovery
{
    private const Z2M_MODULE_TYPES = Z2MInstanceIdentity::TYPES;

    private ?array $gatewayZ2MInstances = null;

    private function ReadZ2MInstanceScope(int $id, bool $allowPeer = false): ?array
    {
        if ($this->GetParentID() <= 0 || !IPS_InstanceExists($this->GetParentID())) return null;
        $local = Z2MInstanceIdentity::Read($id, $this->GetParentID());
        $peer = $allowPeer ? $this->PeerContext() : null;
        $remote = $peer === null ? null : Z2MInstanceIdentity::Read($id, $peer['gateway'], $peer['system']);
        if ($remote !== null) {
            if ($remote['type'] === 'bridge') return null;
            $remote['key'] = 'remote.' . hash('sha256', $remote['topic']) . '.' . $remote['key'];
            return $remote;
        }
        return $local;
    }

    private function GatewayZ2MInstances(): array
    {
        if ($this->gatewayZ2MInstances !== null) {
            return $this->gatewayZ2MInstances;
        }
        $instances = [];
        foreach (array_keys(self::Z2M_MODULE_TYPES) as $module) {
            foreach (array_slice(IPS_GetInstanceListByModuleID($module), 0, 512) as $id) {
                $scope = $this->ReadZ2MInstanceScope($id);
                if ($scope !== null) {
                    $instances[$id] = $scope;
                }
            }
        }
        return $this->gatewayZ2MInstances = $instances;
    }

    private function GatewayBaseTopics(): array
    {
        $topics = [];
        foreach ($this->GatewayZ2MInstances() as $scope) {
            $topics[$scope['topic']] = true;
        }
        $names = array_keys($topics);
        sort($names, SORT_STRING);
        return $names;
    }

    private function GatewayTopicFields(): array
    {
        $names = $this->GatewayBaseTopics();
        $current = $this->ReadPropertyString('BaseTopic');
        $options = [];
        // Every native MultiSelect option must bind the same nonempty property set.
        // Keep manual/previous settings representable without inventing a detected topic.
        if (!in_array($current, $names, true)) {
            $options[] = ['caption' => sprintf($this->Translate('Saved base topic: %s (not detected here)'), $current),
                'value'             => [['name' => 'BaseTopic', 'value' => $current]]];
        }
        foreach ($names as $topic) {
            $options[] = ['caption' => $topic, 'value' => [['name' => 'BaseTopic', 'value' => $topic]]];
        }
        $hint = !$this->HasParent() ? 'Connect an MQTT gateway, then select or enter the Z2M base topic.'
            : ($names === [] ? 'No base topic was detected on this gateway. Enter the Z2M base topic manually.'
                : (!in_array($current, $names, true) ? 'The saved base topic was not found on this gateway. Choose a detected topic or enter it manually, then apply the settings.'
                    : (count($names) > 1 ? 'Several base topics were found. Select the intended Z2M system; the gateway alone does not determine its topic.'
                        : 'The saved base topic matches the devices on this gateway.')));
        return ['options' => $options, 'enabled' => $names !== [], 'hint' => $this->Translate($hint)];
    }

    private function FillGatewayTopics(array &$form): void
    {
        $fields = $this->GatewayTopicFields();
        foreach ($this->EditorFormFields($form['elements']) as &$field) {
            if (($field['name'] ?? '') === 'DetectedBaseTopic') {
                unset($field['value']);
                foreach (['options', 'enabled'] as $parameter) {
                    $field[$parameter] = $fields[$parameter];
                }
            } elseif (($field['name'] ?? '') === 'GatewayTopicInfo') {
                $field['caption'] = $fields['hint'];
            }
        }
        unset($field);
        $this->SetBuffer('GatewayTopicFormScope', $this->GatewayTopicFormScope());
        $this->DebugEvent('AKTION', 'Basistopics aus Z2M-Instanzen am eigenen MQTT-Gateway ermittelt; Einstellungen unverändert.', ['Gateway' => $this->GetParentID(), 'GefundeneTopics' => count($this->GatewayBaseTopics())]);
    }

    private function RefreshGatewayTopicFields(): void
    {
        $scope = $this->GatewayTopicFormScope();
        $previous = $this->GetBuffer('GatewayTopicFormScope');
        if ($previous === '' || $previous === $scope) {
            return;
        }
        $this->SetBuffer('GatewayTopicFormScope', $scope);
        // Native MultiSelect validates array values by object identity. Replacing
        // options leaves its old selection detached even when its contents match.
        // Rebuild on a changed configuration scope, never on routine status updates.
        $this->ReloadForm();
        $this->DebugEvent('AKTION', 'Formular nach Gateway- oder Topic-Wechsel neu aufgebaut; Topic-Auswahl neu gebunden. Gespeicherte Einstellungen und YAML-Entwurf bleiben unverändert.', ['Gateway' => $this->GetParentID(), 'GefundeneTopics' => count($this->GatewayBaseTopics())]);
    }

    private function GatewayTopicFormScope(): string
    {
        return json_encode([$this->GetParentID(), $this->ReadPropertyString('BaseTopic'), $this->GatewayBaseTopics()], JSON_THROW_ON_ERROR);
    }
}
