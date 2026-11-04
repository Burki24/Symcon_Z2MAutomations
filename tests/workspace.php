<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
$root = dirname(__DIR__);
if (!is_file($root . '/libs/WorkspaceState.php')) throw new RuntimeException('A versioned per-system workspace is required.');
require_once $root . '/libs/WorkspaceState.php';

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
};
IPS\Kernel::reset();
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient', 'tests/fixtures/Z2MDevice', 'tests/fixtures/Z2MGroup'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);

class WorkspaceProbe extends Z2MAutomations
{
    public array $debugMessages = [];
    public $onCatalogRead = null;
    public function RawWorkspace(): string
    {
        return $this->ReadAttributeString('SystemWorkspace');
    }
    public function ReplaceWorkspace(string $raw): void
    {
        $this->WriteAttributeString('SystemWorkspace', $raw);
    }
    public function LegacyDigest(): string
    {
        return hash('sha256', json_encode([IPS_GetConfiguration($this->InstanceID), $this->ReadAttributeString('YamlDraft'),
            $this->ReadAttributeString('EditorDraft'), $this->ReadAttributeString('EditorSnapshot'), $this->GetBuffer('DesignerSessions')], JSON_THROW_ON_ERROR));
    }
    protected function ReadWorkspaceConnectionCatalog(int $id): array
    {
        $value = parent::ReadWorkspaceConnectionCatalog($id);
        if ($this->onCatalogRead !== null)($this->onCatalogRead)($id);
        return $value;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debugMessages[] = $Data;
        return true;
    }
}

use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = WorkspaceProbe::class;
$registry->setValue(null, $modules);
$managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
IPS_ApplyChanges($managerID);
$manager = IPS\InstanceManager::getInstanceInterface($managerID);
$legacy = $manager->LegacyDigest();
$parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
IPS_ApplyChanges($parentID);
$parent = IPS\InstanceManager::getInstanceInterface($parentID);
$decode = static fn (string $text): array => json_decode($text, true, 32, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
$overview = static fn (): array => $decode($manager->GetWorkspaceOverview());
$detail = static fn (string $id): array => $decode($manager->GetWorkspaceSystem($id));
$readEntities = static fn (string $source, string $target, string $use = 'action', int $offset = 0, int $limit = 50): array => $decode($manager->GetWorkspaceEntities($source, $target, $use, $offset, $limit));
$initial = $overview();
$check($initial['ok'] && $initial['systems'] === [] && $manager->RawWorkspace() === '', 'Empty overview does not persist or create objects');
$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => '0.1.0', 'session' => '11111111-1111-4111-8111-111111111111',
    'state'                  => 'ready', 'stored_revision' => 0, 'checksum' => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false, 'catalog_version' => 1];
$deviceKey = '0x0000000000000001';
$catalogData = $ready + ['devices' => [['id' => $deviceKey, 'name' => 'Same synthetic device', 'triggers' => [['property' => 'state_l2', 'value' => 'ON']],
    'actions'                                => [['property' => 'state_l2', 'value' => 'OFF']], 'properties' => [
        ['property' => 'state_l2', 'type' => 'binary', 'access' => 3, 'label' => 'Channel 2', 'endpoint' => 'l2', 'values' => ['ON', 'OFF']],
        ['property' => 'display_flag', 'type' => 'binary', 'access' => 1, 'label' => 'Sensor', 'endpoint' => '', 'values' => [true, false]]]]],
    'groups' => [['id' => 7, 'name' => 'Same synthetic group']],
    'scenes' => [['target' => ['type' => 'group', 'id' => 7], 'id' => 2, 'name' => 'Synthetic scene', 'control_property' => 'state']]];
$parent->onPublish = static function (array $packet) use ($parent, &$catalogData, $ready): void
{
    $data = str_ends_with($packet['Topic'], '/request/catalog') ? $catalogData : (str_ends_with($packet['Topic'], '/request/health_check') ? ['healthy' => true] : $ready);
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data]);
};
$connections = $systems = $groups = [];
for ($i = 0; $i < 8; $i++) {
    $id = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_ConnectInstance($id, $parentID);
    IPS_SetProperty($id, 'Active', true);
    IPS_SetProperty($id, 'BaseTopic', 'synthetic/system_' . $i);
    IPS_ApplyChanges($id);
    $module = IPS\InstanceManager::getInstanceInterface($id);
    $module->CheckHealth();
    $module->RequestDeviceCatalog();
    $connections[] = $id;
    $systems[] = IPS_GetProperty($id, 'SystemID');
    $device = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
    IPS_ConnectInstance($device, $parentID);
    IPS_SetProperty($device, 'IEEE', $deviceKey);
    IPS_SetProperty($device, 'MQTTBaseTopic', 'synthetic/system_' . $i);
    IPS_ApplyChanges($device);
    $group = IPS_CreateInstance('{11BF3773-E940-469B-9DD7-FB9ACD7199A2}');
    IPS_ConnectInstance($group, $parentID);
    IPS_SetProperty($group, 'MQTTBaseTopic', 'synthetic/system_' . $i);
    IPS_ApplyChanges($group);
    $groups[] = $group;
}
$sent = count($parent->sent);
$configured = $decode($manager->ConfigureWorkspace(json_encode($connections), $initial['fingerprint'], $op()));
$check($configured['ok'] && count($overview()['systems']) === 8, 'Eight independent connections are registered locally');
$before = $manager->RawWorkspace();
$check($decode($manager->ConfigureWorkspace(json_encode([...$connections, $connections[0]]), $overview()['fingerprint'], $op()))['error'] === 'system_limit' && $manager->RawWorkspace() === $before, 'Ninth connection is rejected before writing');
$documents = [];
foreach ($systems as $i => $system) {
    $document = ['schema_version' => 1, 'revision' => 1, 'automations' => [['id' => 'same_rule', 'name' => 'Synthetic rule ' . $i, 'enabled' => false,
        'triggers'                                                               => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
        'actions'                                                                => [['type' => 'sequence', 'sequence_id' => 'same_sequence']]]],
        'sequences' => [['id' => 'same_sequence', 'name' => 'Synthetic sequence', 'actions' => [['type' => 'toggle', 'target' => ['type' => 'group', 'id' => 7], 'property' => 'state']]]]];
    if ($i === 1) $document['mesh_peers'] = ['synthetic/system_0'];
    if ($i === 2) $document['mesh_peers'] = [];
    $yaml = '# Synthetic exact YAML bytes äöü' . "\n" . json_encode($document, JSON_PRETTY_PRINT) . "\n";
    $data = WorkspaceState::Data();
    $data['document'] = ['yaml' => $yaml, 'editorDraft' => ['configuration' => $document, 'baseRevision' => 0, 'baseChecksum' => null, 'draftChecksum' => hash('sha256', $yaml)]];
    $data['selection'] = ['rule' => 'same_rule', 'sequence' => 'same_sequence'];
    $documents[$system] = $data;
    $result = $decode($manager->UpdateWorkspaceSystem($system, 1, $detail($system)['fingerprint'], json_encode($data), $op()));
    $check($result['ok'], 'A system receives its own exact YAML, editor definition and selections');
    $result = $decode($manager->RefreshWorkspaceCatalog($system, 1, $detail($system)['fingerprint'], $op()));
    $check($result['ok'] && $detail($system)['data'] === $data, 'Catalog and native bindings do not change documents, peer grants or selection');
}
$check($manager->LegacyDigest() === $legacy && count($parent->sent) === $sent, 'Workspace operations preserve old editor/config and emit no MQTT');
$a = $systems[0];
$b = $systems[1];
$c = $systems[2];
$targets = $readEntities($a, $b);
$check($targets['ok'] && count($targets['items']) === 2, 'Directed explicit permission allows remote device and group actions');
$local = $readEntities($a, $a);
$check($local['items'][0]['key'] !== $targets['items'][0]['key'], 'Equal native identities never merge across systems');
$check(isset($local['items'][0]['properties']['state_l2']) && !isset($local['items'][0]['properties']['display_flag']), 'Actions require native write access; display-only variables are excluded');
$sources = $readEntities($a, $a, 'trigger');
$check(isset($sources['items'][0]['properties']['display_flag']), 'Readable sensor properties remain trigger choices');
$check($readEntities($a, $b, 'condition')['error'] === 'remote_source' && $readEntities($b, $a)['error'] === 'permission_missing'
    && $readEntities($a, $c)['error'] === 'permission_missing', 'Remote sources and ungranted reverse directions stay blocked');
$check(isset($targets['items'][1]['properties']['z2ma_scene']), 'Existing group scene selection uses shared metadata conversion');
$groupVariable = IPS_GetObjectIDByIdent('state', $groups[1]);
IPS_SetVariableCustomAction($groupVariable, 1);
$check(count($readEntities($a, $b)['items']) === 1 && count($readEntities($c, $c)['items']) === 2, 'Losing a target permission protects only that target and leaves other systems usable');
$check($detail($b)['data'] === $documents[$b], 'Permission loss never deletes rules or peer grants');
IPS_SetVariableCustomAction($groupVariable, 0);
IPS_SetIdent($groupVariable, 'renamed_state');
$check(count($readEntities($a, $b)['items']) === 1, 'Stored variable identity is rechecked instead of finding another variable by name');
IPS_SetIdent($groupVariable, 'state');
$withoutGrant = $documents[$b];
unset($withoutGrant['document']['editorDraft']['configuration']['mesh_peers']);
$withoutGrant['document']['yaml'] = json_encode($withoutGrant['document']['editorDraft']['configuration']);
$withoutGrant['document']['editorDraft']['draftChecksum'] = hash('sha256', $withoutGrant['document']['yaml']);
$before = $manager->RawWorkspace();
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $detail($b)['fingerprint'], json_encode($withoutGrant), $op()))['error'] === 'permission_changed'
    && $manager->RawWorkspace() === $before, 'Ordinary edits cannot silently drop incoming peer grants');
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $detail($b)['fingerprint'], json_encode($withoutGrant), $op(), true))['ok']
    && $readEntities($a, $b)['error'] === 'permission_missing', 'Only an explicitly confirmed local permission change removes a directed target');
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $detail($b)['fingerprint'], json_encode($documents[$b]), $op(), true))['ok'], 'Explicit grant restoration remains local');

// Independent record fingerprints and explicit retry receipts.
$beforeA = $detail($a);
$beforeB = $detail($b);
$dataA = $beforeA['data'];
$dataA['work']['rule'] = ['dirty' => true, 'input' => ['name' => 'Synthetic open input']];
$operation = $op();
$request = json_encode($dataA);
$savedA = $manager->UpdateWorkspaceSystem($a, 1, $beforeA['fingerprint'], $request, $operation);
$check($decode($savedA)['ok'] && $manager->UpdateWorkspaceSystem($a, 1, $beforeA['fingerprint'], $request, $operation) === $savedA, 'Same local operation returns its original receipt without another write');
$check($decode($manager->UpdateWorkspaceSystem($a, 1, $beforeA['fingerprint'], json_encode($beforeA['data']), $operation))['error'] === 'operation_conflict', 'Reused request identity cannot change its payload');
$check($decode($manager->UpdateWorkspaceSystem($a, 1, $beforeA['fingerprint'], $request, $op()))['error'] === 'stale_system', 'A stale tab cannot overwrite newer input');
$check($decode($manager->UpdateWorkspaceSystem($b, 1, $beforeB['fingerprint'], json_encode($beforeB['data']), $op()))['ok'], 'Editing A does not invalidate a separate system B context');
$check($decode($manager->SelectWorkspaceSystem($b, $overview()['fingerprint'], $op()))['error'] === 'open_input', 'Switching systems cannot discard dirty work');
$check($detail($a)['data']['work']['rule'] === $dataA['work']['rule'], 'Open form input remains available');
$check($decode($manager->UpdateWorkspaceSystem($a, 1, $detail($a)['fingerprint'], json_encode($documents[$a]), $op()))['ok'], 'An explicit update can resolve its own open input');
$check($decode($manager->SelectWorkspaceSystem($b, $overview()['fingerprint'], $op()))['ok'], 'Switch works after resolving open work');

// Atomic refresh is aborted if a read callback changes local state.
$prior = $detail($c);
$manager->onCatalogRead = function () use ($manager, $c, $detail, $op, $documents): void
{
    $manager->onCatalogRead = null;
    $data = $documents[$c];
    $data['work']['sequence'] = ['dirty' => true, 'input' => ['name' => 'Synthetic concurrent input']];
    $manager->UpdateWorkspaceSystem($c, 1, $detail($c)['fingerprint'], json_encode($data), $op());
};
$check($decode($manager->RefreshWorkspaceCatalog($c, 1, $prior['fingerprint'], $op()))['error'] === 'stale_system', 'Module callback occurs outside workspace lock and a concurrent edit survives');
$check($detail($c)['data']['work']['sequence']['dirty'], 'Concurrent input was not overwritten by the catalog read');

IPS_SetProperty($connections[3], 'BaseTopic', 'synthetic/changed');
IPS_ApplyChanges($connections[3]);
$check(!$detail($systems[3])['connectionCurrent'] && $detail($systems[3])['data'] === $documents[$systems[3]], 'Changed topic protects the existing draft without losing bytes');
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview()['fingerprint'], $op()))['error'] === 'binding_changed', 'Changed routing requires an explicit new generation');
IPS_SetProperty($connections[3], 'Generation', 2);
IPS_ApplyChanges($connections[3]);
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview()['fingerprint'], $op()))['ok'], 'Explicit higher generation rebinds while retaining documents');
$check($readEntities($systems[3], $systems[3])['error'] === 'catalog_changed', 'Rebinding cannot reuse a catalog from the old destination');
$check($decode($manager->UpdateWorkspaceSystem($systems[3], 1, $detail($systems[3])['fingerprint'], json_encode($documents[$systems[3]]), $op()))['error'] === 'stale_system', 'Old generation cannot write the newly bound system');
$check($decode($manager->ConfigureWorkspace(json_encode(array_slice($connections, 0, 7)), $overview()['fingerprint'], $op()))['error'] === 'remove_blocked', 'Systems cannot be removed before the backup/reference workflow exists');
$persisted = $manager->RawWorkspace();
IPS_ApplyChanges($managerID);
$check($manager->RawWorkspace() === $persisted && $detail($b)['data'] === $documents[$b], 'Repeated module configuration preserves the persistent workspace');
$manager->ReplaceWorkspace('{');
$check($overview()['error'] === 'workspace_corrupt' && $manager->RawWorkspace() === '{', 'Corrupt workspace is never silently reset');
$manager->ReplaceWorkspace(json_encode(['version' => 2, 'selectedSystem' => '', 'systems' => [], 'receipts' => []]));
$check($overview()['error'] === 'workspace_version', 'Unknown version is protected');
$manager->ReplaceWorkspace($persisted);
$check($manager->LegacyDigest() === $legacy && count($parent->sent) === $sent, 'Every workspace path remains local and leaves existing automation instances unchanged');
$debug = implode('', $manager->debugMessages);
$check(str_contains($debug, 'Systementwurf und Bearbeitungsdaten lokal übernommen') && str_contains($debug, 'keine Daten ein zweites Mal')
    && !str_contains($debug, 'Synthetic open input') && !str_contains($debug, '# Synthetic exact YAML'), 'Canonical German debug describes outcomes without content');

// Smaller workspaces have the same isolated contract; references do not transfer ownership yet.
foreach ([1, 2] as $size) {
    $probeID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
    $probe = IPS\InstanceManager::getInstanceInterface($probeID);
    $head = $decode($probe->GetWorkspaceOverview());
    $check($decode($probe->ConfigureWorkspace(json_encode(array_slice($connections, 0, $size)), $head['fingerprint'], $op()))['ok']
        && count($decode($probe->GetWorkspaceOverview())['systems']) === $size, 'One and two system preparation workspaces remain local and usable');
}
$before = $manager->RawWorkspace();
IPS_SetProperty($connections[7], 'SystemID', $systems[6]);
IPS_ApplyChanges($connections[7]);
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview()['fingerprint'], $op()))['error'] === 'duplicate_systemId' && $manager->RawWorkspace() === $before, 'Duplicate system identity cannot alter the registry');
IPS_SetProperty($connections[7], 'SystemID', $systems[7]);
IPS_SetProperty($connections[7], 'BaseTopic', 'synthetic/system_6');
IPS_ApplyChanges($connections[7]);
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview()['fingerprint'], $op()))['error'] === 'duplicate_baseTopic' && $manager->RawWorkspace() === $before, 'Duplicate mesh topics are rejected even though instance IDs differ');
IPS_SetProperty($connections[7], 'BaseTopic', 'synthetic/system_7');
IPS_ApplyChanges($connections[7]);

// Eight large catalogs are persisted separately and never returned in one overview.
for ($i = 2; $i <= 255; $i++) {
    $catalogData['devices'][] = ['id' => '0x' . str_pad(dechex($i), 16, '0', STR_PAD_LEFT), 'name' => 'Synthetic sensor ' . $i,
        'triggers'                    => [], 'actions' => [], 'properties' => [['property' => 'mode', 'type' => 'enum', 'access' => 1, 'label' => 'Mode', 'endpoint' => '',
            'values'                                                                       => array_map(static fn (int $value): string => 'Synthetic value ' . $value . str_repeat('x', 80), range(1, 32))]]];
}
$check(strlen(json_encode($catalogData)) < Z2MProtocol::MAX_PAYLOAD_BYTES, 'Large fixture respects the existing MQTT payload bound');
foreach ($connections as $i => $connection) {
    $module = IPS\InstanceManager::getInstanceInterface($connection);
    $module->CheckHealth();
    $module->RequestDeviceCatalog();
    $record = $detail($systems[$i]);
    $check($decode($manager->RefreshWorkspaceCatalog($systems[$i], $record['generation'], $record['fingerprint'], $op()))['ok'], 'A large canonical per-system catalog is accepted');
}
$networkBefore = count($parent->sent);
$overviewBytes = strlen($manager->GetWorkspaceOverview());
$page = $readEntities($a, $a, 'condition', 0, 100);
$check($overviewBytes < 32768 && $page['ok'] && $page['total'] === 255 && count($page['items']) === 100 && $page['hasMore'], 'Eight large catalogs keep overview small and detail paging explicit');
$secondPage = $readEntities($a, $a, 'condition', 200, 100);
$check(count($secondPage['items']) === 55 && !$secondPage['hasMore'], 'Last page reports every remaining sensor without silent truncation');
$check(strlen(json_encode($page)) < WorkspaceState::MAX_RESPONSE && $detail($b)['data'] === $documents[$b] && count($parent->sent) === $networkBefore, 'Bounded reads retain exact documents and never publish');
// Scope filtering precedes any catalog limit: other systems must not hide later native instances.
for ($i = 0; $i < 513; $i++) {
    $unrelated = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
    IPS_ConnectInstance($unrelated, $parentID);
}
$late = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
IPS_ConnectInstance($late, $parentID);
IPS_SetProperty($late, 'IEEE', '0x00000000000000ff');
IPS_SetProperty($late, 'MQTTBaseTopic', 'synthetic/system_0');
IPS_ApplyChanges($late);
$catalogData['devices'][254]['properties'] = $catalogData['devices'][0]['properties'];
IPS\InstanceManager::getInstanceInterface($connections[0])->RequestDeviceCatalog();
$nativeTraffic = count($parent->sent);
$beforeLate = $detail($a);
$check($decode($manager->RefreshWorkspaceCatalog($a, 1, $beforeLate['fingerprint'], $op()))['ok'], 'Native catalog refresh handles more than 512 unrelated devices');
$lateTargets = $readEntities($a, $a);
$check(in_array('0x00000000000000ff', array_column($lateTargets['items'], 'id'), true), 'A matching native instance after unrelated instances is never silently omitted');
$networkBefore = $nativeTraffic;
IPS_SetProperty($connections[1], 'Active', false);
IPS_ApplyChanges($connections[1]);
$check($readEntities($a, $b)['error'] === 'catalog_changed' && $readEntities($a, $a)['ok'] && $detail($b)['data'] === $documents[$b], 'An offline system protects only its target choices, preserving other workspaces and its draft');
$check($decode($manager->UpdateWorkspaceSystem($a, 1, $detail($a)['fingerprint'], str_repeat('x', WorkspaceState::MAX_DATA + 1), $op()))['error'] === 'invalid_request', 'Oversized client payload is rejected before decoding or writing');
$check(count($parent->sent) === $networkBefore && $manager->LegacyDigest() === $legacy, 'All final workspace checks preserve legacy state and emit no MQTT');
echo 'Workspace sizes: overview ' . $overviewBytes . ' bytes, detail page ' . strlen(json_encode($page)) . " bytes.\n";
echo "Workspace: eight isolated documents, directed targets, rights, concurrency, retries and preservation passed.\n";
