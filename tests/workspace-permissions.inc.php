<?php

declare(strict_types=1);

// Real module and HTTP entries, eight synthetic systems; no live data or MQTT writes.
$permissionsBefore = $manager->RawWorkspace();
$permissionPackets = [count($parent->sent), count($secondaryParent->sent)];
$permissionsToken = substr(strrchr($manager->OpenAutomationDesigner(), '#'), 1);
$permissionHeaders = static function (string $id) use ($detail, $check): array
{
    $value = $detail($id);
    $check($value['ok'], 'Valid permission test fixture: ' . json_encode($value));
    return ['HTTP_X_Z2MA_SYSTEM_ID' => $id, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $value['fingerprint']];
};
$permissionView = static function (string $id) use ($manager, $permissionsToken, $permissionHeaders, $decode, $check): array
{
    [$code, $raw] = $manager->Hook('permissions-status', $permissionsToken, $permissionHeaders($id));
    $check($code === 200, 'A scoped partner permission view must be available: ' . $raw);
    return $decode($raw)['permissions'];
};
$view = $permissionView($a);
[$code, $raw] = $manager->Hook('data', $permissionsToken, $permissionHeaders($a));
$check($code === 200 && $decode($raw)['permissionsAllowed'], 'A newly opened central designer advertises permission management');
$check(count($view['choices']) === 7 && !in_array('synthetic/system_0', array_column($view['choices'], 'topic'), true), 'Only other assigned systems are offered as new senders');
$check($view['canEdit'] && $manager->RawWorkspace() === $permissionsBefore, 'Reading grants neither writes nor queries Z2M');
$permissionPost = static function (string $id, array $view, array $peers, array $extra = [], ?array $headers = null) use ($manager, $permissionsToken, $permissionHeaders, $decode): array
{
    $manager->requestBody = json_encode($extra + ['requestId' => 'cccccccc-cccc-4ccc-8ccc-' . bin2hex(random_bytes(6)), 'context' => $view['context'], 'peers' => $peers, 'confirmed' => true]);
    [$code, $raw] = $manager->Hook('permissions', $permissionsToken, $headers ?? $permissionHeaders($id), 'POST');
    return [$code, $decode($raw)];
};
[$code, $result] = $permissionPost($a, $view, []);
$check($code === 200 && !$result['changed'] && $detail($a)['data'] === $documents[$a], 'No-op preserves exact YAML including comments and absent mesh_peers');
$view = $permissionView($a);
$headers = $permissionHeaders($a);
[$code, $result] = $permissionPost($a, $view, ['synthetic/system_2'], [], $headers);
$post = $manager->requestBody;
$granted = $manager->RawWorkspace();
$check($code === 200 && $result['changed'] && $result['peerCount'] === 1, 'Explicit scoped grant succeeds');
$expected = $documents[$a];
$expected['document']['editorDraft']['configuration']['mesh_peers'] = ['synthetic/system_2'];
$actual = $detail($a)['data'];
$check($actual['document']['editorDraft']['configuration'] === $expected['document']['editorDraft']['configuration'] && $actual['selection'] === $expected['selection'] && $actual['work'] === $expected['work'], 'Only receiver grants change, rule, sequence, selection and work remain intact');
$check($actual['document']['editorDraft']['baseRevision'] === 0 && $actual['document']['editorDraft']['baseChecksum'] === null && $detail($systems[2])['data'] === $documents[$systems[2]] && $detail($b)['data'] === $documents[$b], 'Saved base and partner documents are untouched');
$check($readEntities($systems[2], $a)['ok'] && !$readEntities($a, $systems[2])['ok'], 'Receiver grant allows source-to-target actions without granting reciprocal access');
[$code, $raw] = $manager->Hook('permissions', $permissionsToken, $headers, 'POST');
$check($code === 200 && $decode($raw) === $result && $manager->RawWorkspace() === $granted, 'Ambiguous reply replay returns same receipt without a second commit');
$manager->requestBody = json_encode(array_replace($decode($post), ['peers' => []]));
[$code, $raw] = $manager->Hook('permissions', $permissionsToken, $headers, 'POST');
$check($code === 409 && $decode($raw)['error'] === 'operation_conflict' && $manager->RawWorkspace() === $granted, 'Reusing a request ID for a different change is rejected');
$view = $permissionView($a);
foreach ([['synthetic/arbitrary'], ['synthetic/system_0'], ['synthetic/system_2', 'synthetic/system_2'], array_fill(0, 9, 'synthetic/system_2')] as $invalid) {
    [$code] = $permissionPost($a, $view, $invalid);
    $check($code === 409 && $manager->RawWorkspace() === $granted, 'Arbitrary, own, duplicate and excessive grants cannot be introduced');
}
[$code] = $permissionPost($a, $view, [], ['confirmed' => false]);
$check($code === 409 && $manager->RawWorkspace() === $granted, 'Grant changes require explicit confirmation');
[$code] = $permissionPost($a, $view, [], ['yaml' => 'never accepted']);
$check($code === 409 && $manager->RawWorkspace() === $granted, 'Narrow grant API cannot replace a document');
[$code] = $permissionPost($a, $view, [], [], ['HTTP_ORIGIN' => 'http://other.invalid'] + $permissionHeaders($a));
$check($code === 403 && $manager->RawWorkspace() === $granted, 'Foreign origins cannot submit grants');
[$code] = $manager->Hook('permissions', $permissionsToken, $headers);
$check($code === 405, 'Grant writes are never GET');

// Imported unknown grants are visible and retained. Sequence users include disabled rules.
$state = $decode($permissionsBefore);
$receiver = &$state['systems'][$a]['data']['document'];
$receiver['editorDraft']['configuration']['mesh_peers'] = ['synthetic/old_sender'];
$receiver['yaml'] = json_encode($receiver['editorDraft']['configuration']);
$receiver['editorDraft']['draftChecksum'] = hash('sha256', $receiver['yaml']);
unset($receiver);
$source = &$state['systems'][$systems[2]]['data']['document'];
$source['editorDraft']['configuration']['sequences'][0]['actions'][0]['target']['system'] = 'synthetic/system_0';
$source['yaml'] = json_encode($source['editorDraft']['configuration']);
$source['editorDraft']['draftChecksum'] = hash('sha256', $source['yaml']);
unset($source);
$manager->ReplaceWorkspace(json_encode($state));
$view = $permissionView($a);
$choices = array_column($view['choices'], null, 'topic');
$check(count($view['choices']) === 8 && $choices['synthetic/old_sender']['selected'] && !$choices['synthetic/old_sender']['assigned'], 'Existing unassigned grant remains visible and selected');
$check($choices['synthetic/system_2']['affectedCount'] === 1 && $choices['synthetic/system_2']['affected'][0] === ['id' => 'same_rule', 'name' => 'Synthetic rule 2', 'enabled' => false], 'Impact expands reusable sequences and includes disabled rules');
[$code] = $permissionPost($a, $view, ['synthetic/old_sender', 'synthetic/system_2']);
$check($code === 200 && (Z2MAutomations\WorkspaceState::Permissions($detail($a)['data'])['mesh_peers'] ?? []) === ['synthetic/old_sender', 'synthetic/system_2'], 'Adding a known sender retains imported unknown grants');
$view = $permissionView($a);
[$code] = $permissionPost($a, $view, ['synthetic/system_2']);
$check($code === 200 && (Z2MAutomations\WorkspaceState::Permissions($detail($a)['data'])['mesh_peers'] ?? []) === ['synthetic/system_2'], 'Unknown old grant can only be removed by explicit selection and confirmation');

// Confirmation pins partner documents too, so the displayed impact cannot go stale silently.
$view = $permissionView($a);
$headers = $permissionHeaders($a);
$state = $decode($manager->RawWorkspace());
$state['systems'][$b]['name'] = 'Renamed synthetic sender';
$manager->ReplaceWorkspace(json_encode($state));
$beforeStale = $manager->RawWorkspace();
[$code, $result] = $permissionPost($a, $view, [], [], $headers);
$check($code === 409 && $result['error'] === 'permissions_stale' && $manager->RawWorkspace() === $beforeStale, 'Changed partner context rejects a stale impact review without overwriting input');
$state['systems'][$a]['data']['work']['rule'] = ['dirty' => true, 'input' => ['pending' => 'Synthetic unsaved input']];
$manager->ReplaceWorkspace(json_encode($state));
$dirty = $manager->RawWorkspace();
$view = $permissionView($a);
$check(!$view['canEdit'] && $view['blocked'] === 'open_input', 'Pending native input visibly blocks permission editing');
[$code] = $permissionPost($a, $view, []);
$check($code === 409 && $manager->RawWorkspace() === $dirty, 'Pending work survives rejected grant submission');
$manager->ReplaceWorkspace($permissionsBefore);
$view = $permissionView($a);
[$code] = $permissionPost($a, $view, [], [], ['HTTP_X_Z2MA_GENERATION' => '2'] + $permissionHeaders($a));
$check($code === 409 && $manager->RawWorkspace() === $permissionsBefore, 'A foreign system generation cannot change grants');
IPS_DisconnectInstance($connections[2]);
IPS_ConnectInstance($connections[2], $secondaryParentID);
$view = $permissionView($a);
$check(!$view['canEdit'] && $view['blocked'] === 'binding_changed', 'A changed sender binding blocks a misleading grant review');
[$code] = $permissionPost($a, $view, ['synthetic/system_2']);
$check($code === 409 && $manager->RawWorkspace() === $permissionsBefore, 'Changed sender cannot receive a grant under its previous topic');
IPS_DisconnectInstance($connections[2]);
IPS_ConnectInstance($connections[2], $parentID);
$state = $decode($permissionsBefore);
$state['systems'][$a]['data'] = Z2MAutomations\WorkspaceState::Data();
$manager->ReplaceWorkspace(json_encode($state));
$view = $permissionView($a);
$check(!$view['canEdit'] && $view['blocked'] === 'no_document', 'A missing document is explained instead of silently creating one');

// Eight systems with one hundred dependent rules each still produce a bounded view.
$state = $decode($permissionsBefore);
foreach ($state['systems'] as $id => &$record) {
    if ($id === $a) continue;
    $cfg = &$record['data']['document']['editorDraft']['configuration'];
    $cfg['sequences'][0]['actions'][0]['target']['system'] = 'synthetic/system_0';
    $rule = $cfg['automations'][0];
    $cfg['automations'] = [];
    for ($i = 0; $i < 100; $i++) $cfg['automations'][] = array_replace($rule, ['id' => $i === 0 ? 'same_rule' : 'synthetic_rule_' . $i, 'name' => str_repeat('x', 120)]);
    $record['data']['document']['yaml'] = json_encode($cfg);
    $record['data']['document']['editorDraft']['draftChecksum'] = hash('sha256', $record['data']['document']['yaml']);
    unset($cfg);
}
unset($record);
$manager->ReplaceWorkspace(json_encode($state));
$view = $permissionView($a);
$check($view['choices'][0]['affectedCount'] === 100 && count($view['choices'][0]['affected']) === 20 && strlen(json_encode($view)) < 262144, 'Impact reports totals while bounding displayed names');
$manager->RemovePermissionAccess($permissionsToken);
$beforeDenied = $manager->RawWorkspace();
[$code] = $manager->Hook('permissions-status', $permissionsToken, $permissionHeaders($a));
$check($code === 403 && $manager->RawWorkspace() === $beforeDenied, 'Old sessions without the dedicated capability cannot access grants');
[$code] = $permissionPost($a, $view, []);
$check($code === 403 && $manager->RawWorkspace() === $beforeDenied, 'Missing capability rejects writes as well as reads');
$check([count($parent->sent), count($secondaryParent->sent)] === $permissionPackets, 'All partner management scenarios issue zero MQTT packets');
$check(str_contains(implode('\n', $manager->debugMessages), 'Empfangsfreigaben ausdrücklich bestätigt') && str_contains(implode('\n', $manager->debugMessages), 'Freigabeauftrag wiedererkannt'), 'German debug explains decision, direction and replay');
$manager->ReplaceWorkspace($permissionsBefore);
