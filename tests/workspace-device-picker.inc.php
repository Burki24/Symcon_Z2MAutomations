<?php

declare(strict_types=1);

$pickerBefore = $manager->RawWorkspace();
$pickerPackets = [count($parent->sent), count($secondaryParent->sent)];
$pickerToken = substr(strrchr($manager->OpenAutomationDesigner(), '#'), 1);
$pickerData = static function () use ($manager, $pickerToken, $a, $decode, $check): array
{
    [$code, $body] = $manager->Hook('data', $pickerToken, ['HTTP_X_Z2MA_SYSTEM_ID' => $a]);
    $check($code === 200, 'The device picker reads its selected system');
    return $decode($body);
};
$pickerPeers = static function (string $system, array $peers) use ($manager, $detail, $decode, $op, $check): void
{
    $record = $detail($system);
    $data = $record['data'];
    $data['document']['editorDraft']['configuration']['mesh_peers'] = $peers;
    $data['document']['yaml'] = json_encode($data['document']['editorDraft']['configuration']);
    $data['document']['editorDraft']['draftChecksum'] = hash('sha256', $data['document']['yaml']);
    $check($decode($manager->UpdateWorkspaceSystem($system, 1, $record['fingerprint'], json_encode($data), $op(), true))['ok'], 'Synthetic peer grants update only the local document');
};
try {
    $view = $pickerData();
    $check(isset($view['deviceSystems']) && count($view['deviceSystems']) === 8, 'The Symcon form needs all eight Z2M network choices, including unavailable partners');
    $owners = array_column($view['deviceSystems'], null, 'key');
    $check($owners['']['baseTopic'] === 'synthetic/system_0' && $owners['synthetic/system_2']['reason'] === 'permissions', 'Local and denied remote networks have explicit identities and reasons');
    $check($owners['synthetic/system_1']['reason'] === '' && $owners['synthetic/system_1']['conditionReason'] === 'permissions', 'A directed action grant does not imply permission to exchange condition replies');
    $pickerPeers($a, ['synthetic/system_1']);
    $view = $pickerData();
    $remote = array_values(array_filter($view['devices'], static fn (array $device): bool => ($device['target']['system'] ?? '') === 'synthetic/system_1' && $device['target']['type'] === 'device'))[0];
    $check($remote['target']['id'] === $deviceKey && $remote['key'] !== $deviceKey, 'Identical remote device IDs retain qualified picker keys');
    $properties = array_column($remote['properties'], null, 'id');
    $check($properties['state_l2']['readable'] && $properties['state_l2']['writable'] && $properties['display_flag']['readable'] && !$properties['display_flag']['writable'], 'Partner conditions retain readable properties without inventing write rights');
    $check($readEntities($a, $b, 'condition')['ok'] && !$readEntities($a, $b, 'trigger')['ok'], 'Partner conditions are selectable while triggers remain local');
    $rule = ['name'  => 'Remote condition and action', 'retrigger' => 'ignore',
        'triggers'   => [['type' => 'device', 'device' => $deviceKey, 'property' => 'state_l2', 'operator' => 'eq', 'value' => 'ON']],
        'conditions' => [['type' => 'device_value', 'device' => $deviceKey, 'system' => 'synthetic/system_1', 'property' => 'display_flag', 'operator' => 'eq', 'value' => true]],
        'actions'    => [['type' => 'toggle', 'target' => ['type' => 'device', 'id' => $deviceKey, 'system' => 'synthetic/system_1'], 'property' => 'state_l2']]];
    $postPicker = static function (array $fields) use ($manager, $pickerToken, $pickerData, $a): array
    {
        static $serial = 0;
        $current = $pickerData();
        $manager->requestBody = json_encode(['requestId' => 'abcddcba-1234-4234-8234-' . str_pad((string) ++$serial, 12, '0', STR_PAD_LEFT), 'context' => $current['createContext'], 'rule' => $fields]);
        return $manager->Hook('create', $pickerToken, ['HTTP_X_Z2MA_SYSTEM_ID' => $a, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $current['workspaceContext']['fingerprint']], 'POST');
    };
    $beforePeer = $detail($b)['data'];
    [$code, $body] = $postPicker($rule);
    $check($code === 200, 'A new disabled rule can use a partner condition and toggle action: ' . $body);
    $createdID = $decode($body)['ruleId'];
    $created = array_values(array_filter($pickerData()['configuration']['automations'], static fn (array $entry): bool => $entry['id'] === $createdID))[0];
    $check(!$created['enabled'] && $created['conditions'] === $rule['conditions'] && $created['actions'] === $rule['actions'] && $detail($b)['data'] === $beforePeer, 'The selected draft retains remote ownership and leaves the partner document untouched');
    $reopened = $pickerData();
    $check($reopened['editSections'][$createdID]['conditions'] && $reopened['editSections'][$createdID]['actions'], 'Reopened partner sections remain editable');
    $native = $decode($manager->WorkspaceEditorAction($a, 1, $detail($a)['fingerprint'], 'SelectAutomation', json_encode([$createdID])));
    $check($native['result'], 'The same partner condition can reopen in the native editor');
    foreach (['EditorConditionList' => true, 'EditorTriggers' => false] as $list => $partnerAllowed) {
        $reply = $decode($manager->WorkspaceEditorAction($a, 1, $detail($a)['fingerprint'], 'GetEditorItemForm', json_encode([$list, '', 0, 'eq', false])));
        $fields = array_column($decode($reply['result']), null, 'name');
        $check(in_array($remote['key'], array_column($fields['RowDevice']['options'], 'value'), true) === $partnerAllowed, 'Native partner choices belong to conditions, never device triggers');
    }
    $nativeRule = array_replace($rule, ['name' => 'Remote native switch value', 'actions' => [
        ['type' => 'set', 'target' => $rule['actions'][0]['target'], 'values' => ['state_l2' => 'TOGGLE'], 'delay_seconds' => 7]]]);
    [$code, $body] = $postPicker($nativeRule);
    $check($code === 200, 'Werte setzen with TOGGLE is accepted for a permitted partner channel: ' . $body);
    $nativeID = $decode($body)['ruleId'];
    $nativeRule = array_values(array_filter($pickerData()['configuration']['automations'], static fn (array $entry): bool => $entry['id'] === $nativeID))[0];
    $check(!$nativeRule['enabled'] && $nativeRule['actions'][0] === ['type' => 'set', 'target' => $rule['actions'][0]['target'], 'values' => ['state_l2' => 'TOGGLE'], 'delay_seconds' => 7]
        && $detail($b)['data'] === $beforePeer, 'Partner ownership, native TOGGLE, delay and other system documents survive unchanged');
    $pickerPeers($b, []);
    $view = $pickerData();
    $owners = array_column($view['deviceSystems'], null, 'key');
    $check($owners['synthetic/system_1']['reason'] === 'permissions' && !array_filter($view['devices'], static fn (array $device): bool => ($device['target']['system'] ?? '') === 'synthetic/system_1'), 'Revocation removes partner devices and explains why the network is unavailable');
    $beforeDenied = $manager->RawWorkspace();
    $invalid = $rule;
    $invalid['name'] = 'Denied partner rule';
    [$code] = $postPicker($invalid);
    $check($code === 422 && $manager->RawWorkspace() === $beforeDenied, 'A forged remote selection cannot bypass revoked grants or resolve to a local device with the same ID');
    $pickerPeers($b, ['synthetic/system_0']);
    $invalid['triggers'][0]['system'] = 'synthetic/system_1';
    [$code] = $postPicker($invalid);
    $check($code === 422, 'A forged remote trigger stays rejected');
    $check([count($parent->sent), count($secondaryParent->sent)] === $pickerPackets, 'Creating and reopening partner rules sends no MQTT commands');
} finally {
    $manager->ReplaceWorkspace($pickerBefore);
}
