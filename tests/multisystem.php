<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
require_once dirname(__DIR__) . '/libs/SystemContract.php';

/** Native module RPCs enter a separate PHP context, unlike the SDK stub dispatcher. */
function Z2MA_GetSystemMigrationSource(int $InstanceID): string
{
    $context = clone IPS\InstanceManager::getInstanceInterface($InstanceID);
    (new ReflectionProperty(Z2MAutomations::class, 'ownershipEntry'))->setValue($context, false);
    return $context->GetSystemMigrationSource();
}

IPS\Kernel::reset();
$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true);
foreach ([$root . '/Z2MAutomations', __DIR__ . '/fixtures/MQTTClient'] as $path) IPS\ModuleLoader::loadSingleModule($path, $library['id']);

class MigrationProbe extends Z2MAutomations
{
    public array $debug = [];
    public function Seed(): void
    {
        $configuration = ['schema_version' => 1, 'revision' => 5, 'automations' => [
            ['id'         => 'same_rule', 'name' => 'Synthetic shared name', 'enabled' => false, 'triggers' => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
                'actions' => [['type' => 'sequence', 'sequence_id' => 'same_sequence']]]],
            'sequences' => [['id' => 'same_sequence', 'name' => 'Synthetic sequence', 'actions' => [
                ['type' => 'toggle', 'target' => ['type' => 'group', 'id' => 7], 'property' => 'state']]]], 'mesh_peers' => ['synthetic/existing']];
        $yaml = json_encode($configuration, JSON_PRETTY_PRINT) . "\n";
        $scope = (new ReflectionMethod($this, 'TransportScope'))->invoke($this);
        $this->WriteAttributeString('YamlDraft', $yaml);
        $this->WriteAttributeString('EditorDraft', json_encode(['scope' => $scope, 'session' => 'private-session', 'baseRevision' => 4,
            'baseChecksum'                                              => str_repeat('a', 64), 'draftChecksum' => hash('sha256', $yaml), 'configuration' => $configuration]));
        $this->WriteAttributeString('EditorSnapshot', json_encode(['scope' => $scope, 'devices' => ['synthetic' => ['bindings' => ['state' => ['id' => 23456]]]], 'loadedAt' => 1]));
        $this->SetBuffer('DesignerSessions', 'private-browser-access');
    }
    public function Change(string $name, string $value, bool $buffer = false): void
    {
        if ($buffer) $this->SetBuffer($name, $value);
        else $this->WriteAttributeString($name, $value);
    }
    public function DraftFields(array $fields): void
    {
        $draft = json_decode($this->ReadAttributeString('EditorDraft'), true);
        $this->WriteAttributeString('EditorDraft', json_encode(array_replace($draft, $fields)));
    }
    public function Digest(): string
    {
        $parts = [IPS_GetConfiguration($this->InstanceID), IPS_GetInstance($this->InstanceID)['ConnectionID']];
        foreach (['YamlDraft', 'EditorDraft', 'EditorSnapshot', 'EditorSelection', 'SequenceSelection'] as $name) $parts[] = $this->ReadAttributeString($name);
        foreach (['TransportState', 'DesignerSessions', 'EditorWork.rule', 'EditorWork.sequence', 'EditorNavigation'] as $name) $parts[] = $this->GetBuffer($name);
        return hash('sha256', json_encode($parts));
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
}
$check = static function (bool $condition, string $message): void
{ if (!$condition) throw new RuntimeException($message); };
$guid = '{23D57681-8424-48DC-9FF0-25C110C533A9}';
$modulesProperty = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $modulesProperty->getValue();
$modules[$guid]['Class'] = MigrationProbe::class;
$modulesProperty->setValue(null, $modules);
$parent = IPS_CreateInstance(\Z2MAutomations\Z2MProtocol::MQTT_CLIENT);
IPS_ApplyChanges($parent);
$ids = [];
for ($n = 0; $n < 8; $n++) {
    $id = IPS_CreateInstance($guid);
    IPS_ConnectInstance($id, $parent);
    IPS_SetProperty($id, 'BaseTopic', 'synthetic/system_' . $n);
    IPS_ApplyChanges($id);
    IPS\InstanceManager::getInstanceInterface($id)->Seed();
    $ids[] = $id;
}
$probe = IPS\InstanceManager::getInstanceInterface($ids[0]);
$before = array_map(static fn (int $id): string => IPS\InstanceManager::getInstanceInterface($id)->Digest(), $ids);
$packets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
foreach ([1, 2, 8] as $count) {
    $result = json_decode($probe->PreviewSystemMigration(json_encode(array_slice($ids, 0, $count))), true);
    $check($result['contractVersion'] === 1 && $result['status'] === 'ready_for_review' && count($result['systems']) === $count, 'Explicit one, two and eight system sources can be inspected');
    $check($result['systems'][0]['rules'] === 1 && $result['systems'][0]['sequences'] === 1 && $result['systems'][0]['localChanges'], 'Preview distinguishes local drafts from stored base and keeps repeated IDs system-local');
    $check($probe->PreviewSystemMigration(json_encode(array_slice($ids, 0, $count))) === json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'Read-only preview is deterministic and does not allocate system identities');
}
$reordered = json_decode($probe->PreviewSystemMigration(json_encode([$ids[1], $ids[0]])), true);
$check($reordered['status'] === 'ready_for_review' && array_column($reordered['systems'], 'sourceInstance') === [$ids[1], $ids[0]], 'Self-inspection works in any source position without changing selection order');
$foreignLock = 'Z2MA.Owner.' . $ids[1];
$check(IPS_SemaphoreEnter($foreignLock, 0), 'An independent caller holds the other source entry lock');
try {
    $blocked = json_decode($probe->PreviewSystemMigration(json_encode([$ids[0], $ids[1]])), true);
    $check($blocked['systems'][0]['status'] === 'ready_for_review'
        && in_array('source_unavailable', array_column($blocked['systems'][1]['issues'], 'code'), true), 'Direct self-inspection does not bypass the owner lock of another source');
} finally {
    IPS_SemaphoreLeave($foreignLock);
}
$check(json_decode($probe->PreviewSystemMigration(json_encode([$ids[0], $ids[1]])), true)['status'] === 'ready_for_review', 'Both owner locks are released after a rejected foreign inspection');
$probe->debug = [];
$probe->PreviewSystemMigration(json_encode([$ids[0]]));
$starts = array_values(array_filter($probe->debug, static fn (string $line): bool => str_contains($line, ' | START | ')));
$ends = array_values(array_filter($probe->debug, static fn (string $line): bool => str_contains($line, ' | ENDE | ')));
$check(count($starts) === 2 && count($ends) === 2, 'Self-inspection retains balanced public preview and source debug entries');
$startRecords = array_map(static fn (string $line): array => json_decode(substr($line, strpos($line, '{"Instanz"')), true, 16, JSON_THROW_ON_ERROR), $starts);
$check($startRecords[0]['Vorgang'] === $startRecords[1]['Vorgang'] && $startRecords[0]['Aufruf'] !== $startRecords[1]['Aufruf'], 'Direct self-inspection shares the canonical debug flow without new counters');
$check(str_contains(implode('', $probe->debug), 'Die Vorschau liest den Bestand dieser Instanz direkt.')
    && !str_contains(implode('', $probe->debug), 'OwnershipRejected'), 'German debug explains the direct read without a false ownership rejection');
$ownLock = 'Z2MA.Owner.' . $ids[0];
$check(IPS_SemaphoreEnter($ownLock, 0), 'An independent caller holds this source entry lock');
try {
    $check(json_decode($probe->PreviewSystemMigration(json_encode([$ids[0]])), true)['error'] === 'instance_busy', 'An independent preview reports contention and cannot bypass the held owner lock');
} finally {
    IPS_SemaphoreLeave($ownLock);
}
$afterInitial = array_map(static fn (int $id): string => IPS\InstanceManager::getInstanceInterface($id)->Digest(), $ids);
$check($before === $afterInitial, 'Every selected source retains its full YAML, peers, local IDs and pending state during successful previews');
foreach (['[]', '[1,2,3,4,5,6,7,8,9]', '["12345"]', '{}', 'null', '{', str_repeat(' ', 1025), json_encode([$ids[0], $ids[0]])] as $invalid) {
    $check(json_decode($probe->PreviewSystemMigration($invalid), true)['status'] === 'blocked', 'Malformed, oversized, duplicate or excessive source selections are rejected');
}
$check(json_decode($probe->PreviewSystemMigration(json_encode([$parent])), true)['status'] === 'blocked', 'A native gateway is not mistaken for an automation source');
$second = IPS\InstanceManager::getInstanceInterface($ids[1]);
$sourceFingerprint = json_decode($second->GetSystemMigrationSource(), true)['fingerprint'];
$second->Change('YamlDraft', 'synthetic unapplied YAML');
$report = json_decode($probe->PreviewSystemMigration(json_encode([$ids[1]])), true);
$check($report['status'] === 'blocked' && in_array('draft_mismatch', array_column($report['systems'][0]['issues'], 'code'), true), 'Divergent raw YAML and editor state are protected');
$check($report['systems'][0]['fingerprint'] !== $sourceFingerprint, 'Source fingerprint changes when raw draft bytes change');
$second->Seed();
$second->DraftFields(['baseRevision' => 0, 'baseChecksum' => null]);
$check(json_decode($probe->PreviewSystemMigration(json_encode([$ids[1]])), true)['status'] === 'ready_for_review', 'An unsaved draft with no stored base is retained and can be inspected');
$second->Seed();
$second->Change('EditorWork.rule', json_encode(['dirty' => true, 'input' => ['name' => 'PRIVATE_INPUT'], 'context' => ['token' => 'PRIVATE_SELECTION']]), true);
$report = json_decode($probe->PreviewSystemMigration(json_encode([$ids[1]])), true);
$check($report['status'] === 'blocked' && in_array('open_input', array_column($report['systems'][0]['issues'], 'code'), true), 'Uncommitted native input blocks readiness without exporting or changing the input');
$check(!str_contains(json_encode($report), 'PRIVATE_'), 'Preview does not leak raw pending input or selection capabilities');
$second->Change('EditorWork.rule', '', true);
$second->Change('EditorDraft', '{');
$check(json_decode($probe->PreviewSystemMigration(json_encode([$ids[1]])), true)['status'] === 'blocked', 'Corrupt metadata never becomes an empty successful draft');
$second->Seed();
$second->DraftFields(['configuration' => ['schema_version' => 2, 'revision' => 5, 'automations' => []]]);
$check(json_decode($probe->PreviewSystemMigration(json_encode([$ids[1]])), true)['status'] === 'blocked', 'Unknown document schema is not treated as an empty supported draft');
$second->Seed();
IPS_SetProperty($ids[1], 'BaseTopic', 'synthetic/system_0');
IPS_ApplyChanges($ids[1]);
$second->Seed();
$report = json_decode($probe->PreviewSystemMigration(json_encode(array_slice($ids, 0, 2))), true);
$check($report['status'] === 'blocked' && in_array('duplicate_topic', array_column($report['systems'][1]['issues'], 'code'), true), 'Equal topics are reported as routing conflicts, even when device and rule IDs match');
IPS_SetProperty($ids[1], 'BaseTopic', 'synthetic/system_1');
IPS_ApplyChanges($ids[1]);
$second->Seed();
// Rebaseline after deliberate fixture changes; inspection itself is the object of this assertion.
$before = array_map(static fn (int $id): string => IPS\InstanceManager::getInstanceInterface($id)->Digest(), $ids);
$probe->debug = [];
$probe->PreviewSystemMigration(json_encode($ids));
$after = array_map(static fn (int $id): string => IPS\InstanceManager::getInstanceInterface($id)->Digest(), $ids);
$check($before === $after && count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $packets, 'Inspection never mutates attributes, parents, buffers, native work or sends MQTT');
$snapshot = $probe->GetSystemMigrationSource();
$check(!str_contains($snapshot, 'private-session') && !str_contains($snapshot, 'private-browser-access') && !str_contains($snapshot, 'Synthetic shared name'), 'Source inspection returns metadata, never YAML, names or session secrets');
$debug = implode("\n", $probe->debug);
$check(str_contains($debug, 'Übernahmevorschau') && !str_contains($debug, 'PRIVATE_') && !str_contains($debug, 'synthetic/system_') && !str_contains($debug, 'Synthetic shared name'), 'Native debug describes decisions with safe references');

$systems = [];
foreach ($ids as $index => $id) $systems[] = ['systemId' => sprintf('11111111-1111-4111-8111-%012d', $index), 'sourceInstance' => $id,
    'connectionInstance'                                 => 0, 'mqttParent' => $parent, 'baseTopic' => 'synthetic/system_' . $index, 'ownerInstance' => $ids[0], 'generation' => 1, 'version' => 1];
$check(\Z2MAutomations\SystemContract::RegistryError($systems) === '', 'Registry represents eight independently bound systems sharing a native MQTT client');
$check(\Z2MAutomations\SystemContract::RegistryError([...$systems, $systems[0]]) === 'system_limit', 'Ninth record is rejected');
foreach (['systemId' => 'duplicate_systemId', 'baseTopic' => 'duplicate_baseTopic', 'sourceInstance' => 'duplicate_sourceInstance'] as $field => $error) {
    $duplicate = array_slice($systems, 0, 2);
    $duplicate[1][$field] = $duplicate[0][$field];
    $check(\Z2MAutomations\SystemContract::RegistryError($duplicate) === $error, 'Conflicting identity, routing or source ownership is rejected');
}
$foreign = $systems;
$foreign[1]['ownerInstance'] = $ids[1];
$check(\Z2MAutomations\SystemContract::RegistryError($foreign) === 'owner_conflict', 'A registry cannot silently mix ownership between managers');
$unknown = $systems;
$unknown[0]['version'] = 2;
$check(\Z2MAutomations\SystemContract::RegistryError($unknown) === 'unsupported_version', 'Unknown registry versions remain blocked');
$check(\Z2MAutomations\SystemContract::CONNECTION_MODULE !== $guid && \Z2MAutomations\SystemContract::CONNECTION_PREFIX === 'Z2MC', 'New module identity and four-letter prefix are reserved separately');
foreach (array_merge(glob($root . '/*/module.json'), glob(__DIR__ . '/fixtures/*/module.json'), [$root . '/library.json']) as $metadataFile) {
    $knownIdentity = json_decode(file_get_contents($metadataFile), true);
    if ($metadataFile === $root . '/Z2MConnection/module.json') {
        $check($knownIdentity['id'] === \Z2MAutomations\SystemContract::CONNECTION_MODULE
            && $knownIdentity['prefix'] === \Z2MAutomations\SystemContract::CONNECTION_PREFIX, 'Implemented connection uses exactly its reserved identity');
        continue;
    }
    $check($knownIdentity['id'] !== \Z2MAutomations\SystemContract::CONNECTION_MODULE && ($knownIdentity['prefix'] ?? '') !== \Z2MAutomations\SystemContract::CONNECTION_PREFIX, 'Reserved connection identity does not collide with existing library, module or fixture identities');
}
$check(\Z2MAutomations\SystemContract::EntityKey($systems[0]['systemId'], 'group', '7') !== \Z2MAutomations\SystemContract::EntityKey($systems[1]['systemId'], 'group', '7'), 'Equal native group IDs never alias between systems');
foreach (['device' => '0x0000000000000001', 'rule' => 'same_rule', 'sequence' => 'same_sequence'] as $kind => $identity) {
    $check(\Z2MAutomations\SystemContract::EntityKey($systems[0]['systemId'], $kind, $identity) !== \Z2MAutomations\SystemContract::EntityKey($systems[1]['systemId'], $kind, $identity), 'Device, rule and sequence identifiers retain their owning system');
}
$check(\Z2MAutomations\SystemContract::BindingMatches($systems[0], $ids[0], $systems[0]['systemId'], 1)
    && !\Z2MAutomations\SystemContract::BindingMatches($systems[0], $ids[1], $systems[0]['systemId'], 1)
    && !\Z2MAutomations\SystemContract::BindingMatches($systems[0], $ids[0], $systems[0]['systemId'], 2), 'Owner and generation are explicit contract checks, not the selected UI row');
$check(\Z2MAutomations\SystemContract::Role(null, true) === 'legacy' && \Z2MAutomations\SystemContract::Role(null, false) === 'unassigned'
    && \Z2MAutomations\SystemContract::Role(['version' => 1, 'role' => 'manager'], true) === 'manager'
    && \Z2MAutomations\SystemContract::Role(['version' => 2, 'role' => 'manager'], true) === 'unsupported', 'Role contract never silently converts existing data or accepts unknown versions');
$secondParent = IPS_CreateInstance(\Z2MAutomations\Z2MProtocol::MQTT_CLIENT);
IPS_ApplyChanges($secondParent);
IPS_DisconnectInstance($ids[1]);
IPS_ConnectInstance($ids[1], $secondParent);
IPS_ApplyChanges($ids[1]);
$second->Seed();
$independent = json_decode($probe->PreviewSystemMigration(json_encode(array_slice($ids, 0, 2))), true);
$check($independent['status'] === 'ready_for_review' && $independent['systems'][0]['mqttParent'] !== $independent['systems'][1]['mqttParent'], 'Independent native MQTT parents are retained without reparenting');
IPS_SetProperty($ids[1], 'BaseTopic', 'synthetic/system_0');
IPS_ApplyChanges($ids[1]);
$second->Seed();
$check(json_decode($probe->PreviewSystemMigration(json_encode(array_slice($ids, 0, 2))), true)['status'] === 'blocked', 'Identical mesh topics on separate brokers still conflict');
IPS_SetProperty($ids[1], 'BaseTopic', 'synthetic/system_1');
IPS_ApplyChanges($ids[1]);
$second->Seed();
IPS_SetProperty($ids[1], 'PeerModuleID', $ids[7]);
IPS_ApplyChanges($ids[1]);
$second->Seed();
$unresolved = json_decode($probe->PreviewSystemMigration(json_encode(array_slice($ids, 0, 2))), true);
$check($unresolved['status'] === 'blocked' && in_array('peer_missing', array_column($unresolved['systems'][1]['issues'], 'code'), true), 'Unselected partner modules are retained as unresolved references, never removed');
$check(count(IPS\InstanceManager::getInstanceInterface($secondParent)->sent) === 0, 'Preview sends nothing through another broker connection');
echo "Read-only multi-system preview, identity and ownership contracts passed.\n";
