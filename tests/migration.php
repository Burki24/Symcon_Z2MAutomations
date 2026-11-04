<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
$root = dirname(__DIR__);
if (!is_file($root . '/libs/SystemMigration.php')) throw new RuntimeException('Durable local takeover and rollback are not implemented.');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
IPS\Kernel::reset();
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient', 'tests/fixtures/Z2MDevice'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);

use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

class TakeoverProbe extends Z2MAutomations
{
    public static string $storage;
    public array $debug = [];
    public ?string $failAt = null;
    public $checkpoint = null;
    public function Seed(int $peer = 0): void
    {
        $configuration = ['schema_version' => 1, 'revision' => 5, 'automations' => [
            ['id'          => 'same_rule', 'name' => 'PRIVATE_RULE', 'enabled' => false,
                'triggers' => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
                'actions'  => [['type' => 'sequence', 'sequence_id' => 'same_sequence']]]],
            'sequences' => [['id' => 'same_sequence', 'name' => 'PRIVATE_SEQUENCE', 'actions' => [
                ['type' => 'toggle', 'target' => ['type' => 'group', 'id' => 7], 'property' => 'state']]]]];
        if ($peer > 0) $configuration['mesh_peers'] = ['synthetic/system_0'];
        $yaml = "# Exact synthetic bytes äöü\n" . json_encode($configuration, JSON_PRETTY_PRINT) . "\n";
        $scope = (new ReflectionMethod($this, 'TransportScope'))->invoke($this);
        $this->WriteAttributeString('YamlDraft', $yaml);
        $this->WriteAttributeString('EditorDraft', json_encode(['scope' => $scope, 'session' => 'PRIVATE_RECEIVER', 'baseRevision' => 4,
            'baseChecksum'                                              => str_repeat('a', 64), 'draftChecksum' => hash('sha256', $yaml), 'configuration' => $configuration]));
        $this->WriteAttributeString('EditorSnapshot', json_encode(['scope' => $scope, 'loadedAt' => 1, 'devices' => []]));
        $this->WriteAttributeString('EditorSelection', json_encode(['id' => 'same_rule', 'checksum' => hash('sha256', $yaml), 'token' => 'PRIVATE_SELECTION']));
        $this->SetBuffer('EditorWork.rule', json_encode(['context' => ['token' => 'PRIVATE_WORK'], 'input' => ['name' => 'PRIVATE_INPUT'], 'dirty' => false]));
        $this->SetBuffer('DesignerSessions', 'PRIVATE_ACCESS');
        $this->SetBuffer('EventImportReport', '{"source":"PRIVATE_IMPORT"}');
    }
    public function Raw(string $key): string
    {
        return $this->ReadAttributeString($key);
    }
    public function Change(string $key, string $value, bool $buffer = false): void
    {
        if ($buffer) $this->SetBuffer($key, $value);
        else $this->WriteAttributeString($key, $value);
    }
    public function Buffer(string $key): string
    {
        return $this->GetBuffer($key);
    }
    protected function MigrationStorageDirectory(): string
    {
        return self::$storage . '/' . $this->InstanceID;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }
    protected function MigrationCheckpoint(string $phase): void
    {
        if ($this->checkpoint !== null)($this->checkpoint)($phase);
        if ($phase === $this->failAt) {
            $this->failAt = null;
            throw new RuntimeException('Injected synthetic interruption');
        }
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Data;
        return true;
    }
}
$modulesProperty = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $modulesProperty->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = TakeoverProbe::class;
$modulesProperty->setValue(null, $modules);
$check = static function (bool $condition, string $message): void
{ if (!$condition) throw new RuntimeException($message); };
$decode = static fn (string $raw): array => json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
$operation = static fn (): string => bin2hex(random_bytes(16));
$directories = [];
$scenario = static function (int $count) use (&$directories, $decode): array
{
    TakeoverProbe::$storage = sys_get_temp_dir() . '/z2ma-synthetic-migration-' . bin2hex(random_bytes(8));
    $directories[] = TakeoverProbe::$storage;
    $parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
    IPS_ApplyChanges($parentID);
    $managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
    $manager = IPS\InstanceManager::getInstanceInterface($managerID);
    $rows = $originals = [];
    for ($i = 0; $i < $count; $i++) {
        $sourceID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
        IPS_ConnectInstance($sourceID, $parentID);
        IPS_SetProperty($sourceID, 'BaseTopic', 'synthetic/system_' . $i);
        if ($i === 1) IPS_SetProperty($sourceID, 'PeerModuleID', $rows[0]['sourceInstance']);
        IPS_ApplyChanges($sourceID);
        $source = IPS\InstanceManager::getInstanceInterface($sourceID);
        $source->Seed($i === 1 ? $rows[0]['sourceInstance'] : 0);
        $connectionID = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
        IPS_ConnectInstance($connectionID, $parentID);
        IPS_SetProperty($connectionID, 'BaseTopic', 'synthetic/system_' . $i);
        IPS_ApplyChanges($connectionID);
        $deviceID = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
        IPS_ConnectInstance($deviceID, $parentID);
        IPS_SetProperty($deviceID, 'IEEE', '0x00124b0000000001');
        IPS_SetProperty($deviceID, 'MQTTBaseTopic', 'synthetic/system_' . $i);
        IPS_ApplyChanges($deviceID);
        $variable = IPS_GetObjectIDByIdent('state', $deviceID);
        $rows[] = ['sourceInstance' => $sourceID, 'connectionInstance' => $connectionID, 'fingerprint' => $decode($source->GetSystemMigrationSource())['fingerprint']];
        $originals[$sourceID] = ['yaml' => $source->Raw('YamlDraft'), 'draft' => $source->Raw('EditorDraft'), 'objects' => IPS_GetChildrenIDs($sourceID),
            'nativeInstance'            => $deviceID, 'nativeVariable' => $variable, 'nativeConfiguration' => IPS_GetConfiguration($deviceID), 'variableMetadata' => IPS_GetVariable($variable)];
    }
    return [$managerID, $manager, $rows, $originals, IPS\InstanceManager::getInstanceInterface($parentID)];
};
$prepare = static function (TakeoverProbe $manager, array $rows, string $op) use ($decode): array
{
    $overview = $decode($manager->GetWorkspaceOverview());
    return $decode($manager->PrepareSystemMigration(json_encode($rows), $overview['fingerprint'] ?? WorkspaceState::Fingerprint(WorkspaceState::Empty()), $op));
};
$restart = static function (int $id): TakeoverProbe
{
    $old = IPS\InstanceManager::getInstanceInterface($id);
    $saved = $old->GetPersistence();
    $fresh = new TakeoverProbe($id);
    IPS\InstanceManager::setStatus($id, 101);
    $fresh->Create();
    $fresh->ApplyPersistence($saved);
    $property = new ReflectionProperty(IPS\InstanceManager::class, 'interfaces');
    $all = $property->getValue();
    $all[$id] = $fresh;
    $property->setValue(null, $all);
    IPS\InstanceManager::setStatus($id, 102);
    $fresh->ApplyChanges();
    return $fresh;
};

foreach ([1, 2, 8] as $count) {
    [$managerID, $manager, $rows, $originals, $parent] = $scenario($count);
    $op = $operation();
    $before = $manager->Raw('SystemWorkspace');
    $beforeReferences = $manager->GetReferenceList();
    $prepared = $prepare($manager, $rows, $op);
    $check($prepared['ok'] && $prepared['phase'] === 'prepared', 'One, two and eight sources can be privately prepared');
    $check($manager->Raw('SystemWorkspace') === $before, 'Preparation does not publish its copies');
    $check($decode($manager->ApplySystemMigration($op))['phase'] === 'committed', 'The explicit takeover publishes after fencing all sources');
    $state = $decode($manager->Raw('SystemWorkspace'));
    foreach ($rows as $row) $check(in_array($row['connectionInstance'], $manager->GetReferenceList(), true), 'Committed connection references are registered');
    $check(count($state['systems']) === $count, 'Every original system is retained');
    $check($manager->GetCompatibleParents() === '{}', 'Owned manager still declares no native parent requirement');
    $check(str_contains($manager->GetConfigurationForm(), 'WorkspaceSystem'), 'Committed manager can open the system-bound native editor');
    $check(isset($decode($manager->GetDesignerData())['workspaceContext']), 'Committed owner can read its system-bound browser model');
    foreach ($state['systems'] as $systemID => $record) {
        $id = $record['binding']['sourceInstance'];
        $source = IPS\InstanceManager::getInstanceInterface($id);
        $check($source->GetCompatibleParents() === '{}', 'Parent-policy metadata remains valid for a fenced source without mutating its connection');
        $check($record['data']['document']['yaml'] === $originals[$id]['yaml'] && $source->Raw('EditorDraft') === $originals[$id]['draft'], 'Raw YAML and source draft bytes survive ownership transfer');
        $check($record['data']['document']['editorDraft']['configuration']['revision'] === 5 && $record['data']['selection']['rule'] === 'same_rule', 'Revision and selected IDs survive without merging equal IDs');
        $check(!isset($record['data']['document']['editorDraft']['session'], $record['data']['document']['editorDraft']['scope']) && $record['catalog'] === null, 'Old receiver capabilities and historical catalogs grant no new authority');
        $check($source->Buffer('DesignerSessions') === '' && !$decode($source->CheckConnection())['ok'], 'Old browser access and old public MQTT calls are blocked');
        $check(!$source->SelectAutomation('', '', false, 'device'), 'Old native editor cannot become a second writer');
        $check(str_contains($source->GetConfigurationForm(), 'zentrale'), 'Migrated source explains where editing moved');
        $connection = IPS\InstanceManager::getInstanceInterface($record['binding']['connectionInstance']);
        $check(!$decode($connection->InstallExtension(false))['ok'], 'Independent extension installation cannot bypass claimed ownership');
        $check(!$decode($source->FenceSystemMigration($managerID, $op, $rows[array_search($id, array_column($rows, 'sourceInstance'), true)]['fingerprint'], true))['ok'], 'Direct source release is rejected outside the rollback phase');
        $check($record['data']['work']['rule']['input']['name'] === 'PRIVATE_INPUT', 'Clean native form inputs are preserved without old authorization context');
        $check($source->Raw('YamlDraft') === $originals[$id]['yaml'] && IPS_GetChildrenIDs($id) === $originals[$id]['objects'], 'Native objects, histories and source document remain in place');
        $binding = $record['nativeBindings']['0x00124b0000000001']['state'];
        $check($binding['id'] === $originals[$id]['nativeVariable'] && $binding['instance'] === $originals[$id]['nativeInstance'], 'Native identity is preserved separately for equal devices in different systems');
        $check(IPS_GetConfiguration($binding['instance']) === $originals[$id]['nativeConfiguration'] && IPS_GetVariable($binding['id']) === $originals[$id]['variableMetadata'], 'Migration never changes native device configuration, action or archive identity');
    }
    $first = array_key_first($state['systems']);
    $data = $state['systems'][$first]['data'];
    $data['document']['yaml'] .= "# New central edit\n";
    $data['document']['editorDraft']['draftChecksum'] = hash('sha256', $data['document']['yaml']);
    $changed = $decode($manager->UpdateWorkspaceSystem($first, 1, WorkspaceState::Fingerprint($state['systems'][$first]), json_encode($data), $operation(), false));
    $check($changed['ok'], 'Only the new manager can edit the taken-over document');
    $edited = $manager->Raw('SystemWorkspace');
    $check($decode($manager->ApplySystemMigration($op))['ok'] && $manager->Raw('SystemWorkspace') === $edited, 'Repeated takeover never overwrites a subsequently edited target');
    $foreignID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
    $foreign = IPS\InstanceManager::getInstanceInterface($foreignID);
    $foreignOverview = $decode($foreign->GetWorkspaceOverview());
    $check(!$decode($foreign->ConfigureWorkspace(json_encode(array_column($rows, 'connectionInstance')), $foreignOverview['fingerprint'], $operation()))['ok'], 'A second manager cannot write a claimed connection');
    if ($count === 1) {
        $aliasSourceID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
        IPS_ConnectInstance($aliasSourceID, IPS_GetInstance($rows[0]['sourceInstance'])['ConnectionID']);
        IPS_SetProperty($aliasSourceID, 'BaseTopic', 'synthetic/system_0');
        IPS_ApplyChanges($aliasSourceID);
        $aliasSource = IPS\InstanceManager::getInstanceInterface($aliasSourceID);
        $aliasSource->Seed();
        $aliasConnection = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
        IPS_ConnectInstance($aliasConnection, IPS_GetInstance($rows[0]['connectionInstance'])['ConnectionID']);
        IPS_SetProperty($aliasConnection, 'BaseTopic', 'synthetic/system_0');
        IPS_ApplyChanges($aliasConnection);
        $aliasRows = [['sourceInstance' => $aliasSourceID, 'connectionInstance' => $aliasConnection, 'fingerprint' => $decode($aliasSource->GetSystemMigrationSource())['fingerprint']]];
        $aliasOp = $operation();
        $check($prepare($foreign, $aliasRows, $aliasOp)['ok'], 'An unclaimed alias can be reviewed locally');
        $check(!$decode($foreign->ApplySystemMigration($aliasOp))['ok'], 'A different connection cannot acquire an already owned mesh topic');
        $check($decode($foreign->RollbackSystemMigration($aliasOp))['ok'] && $decode($manager->GetSystemMigration($op))['phase'] === 'committed', 'Returning a refused alias leaves the original owner unchanged');
    }
    $check($decode($manager->RollbackSystemMigration($op))['phase'] === 'rolled_back', 'Explicit rollback restores local ownership');
    $check($manager->Raw('SystemWorkspace') === $before, 'Rollback restores the exact central preimage');
    $check($manager->GetReferenceList() === $beforeReferences, 'Rollback restores original native references without removing user references');
    $archive = new Z2MAutomations\MigrationArchive(TakeoverProbe::$storage . '/' . $managerID);
    $saved = $archive->Read($op . '-return');
    $check($saved['workspace']['systems'][$first]['data'] === $data, 'Central edits are additionally archived before rollback');
    foreach ($rows as $row) {
        $source = IPS\InstanceManager::getInstanceInterface($row['sourceInstance']);
        $check($decode($source->GetSystemOwnership()) === [], 'Each old source is released');
        $check($decode($source->Raw('EditorSelection'))['token'] === '', 'Former native edit tokens are not restored');
        $check($source->Raw('YamlDraft') === $originals[$row['sourceInstance']]['yaml'], 'Returning ownership preserves the original source YAML');
    }
    $check($decode($manager->RollbackSystemMigration($op))['ok'], 'Repeated rollback is idempotent');
    $check($prepare($manager, $rows, $op)['phase'] === 'rolled_back' && $decode($manager->GetSystemOwnership()) === [], 'An old completed operation cannot be reused to reacquire ownership');
    if ($count === 1) {
        $nextOperation = $operation();
        $check($prepare($manager, $rows, $nextOperation)['ok'] && $decode($manager->ApplySystemMigration($nextOperation))['ok'], 'A new explicit takeover can reuse the preserved sources');
        $check(!$decode($manager->UpdateWorkspaceSystem($first, 1, WorkspaceState::Fingerprint($state['systems'][$first]), json_encode($state['systems'][$first]['data']), $operation(), false))['ok'], 'An old editor fingerprint cannot write after rollback and a new owner epoch');
        $check($decode($manager->RollbackSystemMigration($nextOperation))['ok'], 'The second owner epoch has its own safe return');
    }
    $check($parent->sent === [], 'Backup, takeover and rollback emit zero MQTT packets');
    $check(!str_contains(implode('', $manager->debug), 'PRIVATE_'), 'Debug never contains archived input, document or capabilities');
}

// Fail after each durable side effect; resume with fresh objects and lost buffers.
foreach (['archive-0', 'archive-1', 'manifest', 'prepared', 'owner'] as $phase) {
    [$managerID, $manager, $rows, $originals, $parent] = $scenario(2);
    $op = $operation();
    $manager->failAt = $phase;
    $check(!$prepare($manager, $rows, $op)['ok'] && $manager->Raw('SystemWorkspace') === '', 'Preparation interruption never publishes incomplete data at ' . $phase);
    $manager = $restart($managerID);
    $check($prepare($manager, $rows, $op)['ok'], 'Preparation can be reviewed again after ' . $phase);
    $check($decode($manager->ApplySystemMigration($op))['ok'] && $decode($manager->RollbackSystemMigration($op))['ok'], 'A recovered preparation can take over and return after ' . $phase);
    $check($parent->sent === [], 'Preparation recovery sends no MQTT');
}
foreach (['applying', 'source-0', 'connection-0', 'source-1', 'connection-1', 'workspace', 'references', 'committed'] as $phase) {
    [$managerID, $manager, $rows, $originals, $parent] = $scenario(2);
    $op = $operation();
    $check($prepare($manager, $rows, $op)['ok'], 'Recovery setup prepared');
    $manager->failAt = $phase;
    $manager->checkpoint = static function (string $boundary) use ($manager, $decode, $check): void
    {
        if ($boundary !== 'committed') $check(!$decode($manager->GetWorkspaceOverview())['ok'], 'Prepared copies cannot be opened for editing before commit');
    };
    $check(!$decode($manager->ApplySystemMigration($op))['ok'], 'Synthetic failure is reported at ' . $phase);
    foreach ($rows as $row) if ($decode(IPS\InstanceManager::getInstanceInterface($row['sourceInstance'])->GetSystemOwnership()) !== []) $restart($row['sourceInstance']);
    $manager = $restart($managerID);
    $check($decode($manager->ApplySystemMigration($op))['ok'], 'Resume after lost volatile state succeeds at ' . $phase);
    $check(count($decode($manager->Raw('SystemWorkspace'))['systems']) === 2 && $parent->sent === [], 'Recovery preserves both documents without MQTT');
    $check($decode($manager->RollbackSystemMigration($op))['ok'], 'Resumed migration can return');
}
foreach (['return-backup', 'return-workspace', 'return-connection-0', 'return-source-0', 'return-connection-1', 'return-source-1', 'rolled_back'] as $phase) {
    [$managerID, $manager, $rows, $originals, $parent] = $scenario(2);
    $op = $operation();
    $check($prepare($manager, $rows, $op)['ok'] && $decode($manager->ApplySystemMigration($op))['ok'], 'Rollback recovery setup committed');
    $manager->failAt = $phase;
    $check(!$decode($manager->RollbackSystemMigration($op))['ok'], 'Return interruption is reported at ' . $phase);
    if ($phase === 'return-source-0') {
        $releasedSource = IPS\InstanceManager::getInstanceInterface($rows[0]['sourceInstance']);
        $releasedSource->Change('YamlDraft', $releasedSource->Raw('YamlDraft') . "# Edited after release\n");
    }
    $manager = $restart($managerID);
    $check($decode($manager->RollbackSystemMigration($op))['ok'] && $manager->Raw('SystemWorkspace') === '', 'Return resumes without overwriting later source changes at ' . $phase);
    $check($parent->sent === [], 'Interrupted return performs no MQTT action');
    if ($phase === 'return-source-0') $check(str_ends_with($releasedSource->Raw('YamlDraft'), "# Edited after release\n"), 'Resumed rollback preserves changes made after an individual source was released');
}

[$managerID, $manager, $rows, $originals, $parent] = $scenario(1);
$source = IPS\InstanceManager::getInstanceInterface($rows[0]['sourceInstance']);
$source->Change('EditorWork.rule', '{"dirty":true,"input":{"name":"PRIVATE_OPEN"}}', true);
$backupOp = $operation();
$check($decode($manager->BackupSystemMigration(json_encode([$rows[0]['sourceInstance']]), $backupOp))['ok'], 'A blocked source can still be fully backed up');
$archive = new Z2MAutomations\MigrationArchive(TakeoverProbe::$storage . '/' . $managerID);
$check(str_contains($archive->Read($backupOp . '-backup-' . $rows[0]['sourceInstance'])['work']['EditorWork.rule'], 'PRIVATE_OPEN'), 'Private backup retains pending input');
$check(!$prepare($manager, $rows, $operation())['ok'], 'Pending input prevents takeover');
$source->Seed();
$op = $operation();
$check($prepare($manager, $rows, $op)['ok'], 'Fresh unchanged source can be prepared');
$source->Change('YamlDraft', $source->Raw('YamlDraft') . "# unapplied\n");
$check($decode($manager->ApplySystemMigration($op))['error'] === 'source_changed', 'Changes after preview block source fencing');
$check($decode($source->GetSystemOwnership()) === [], 'Changed source remains the only writer');
$check($decode($manager->RollbackSystemMigration($op))['ok'], 'Prepared target can be released without overwriting an unfenced source');
$source->Seed();
$rows[0]['fingerprint'] = $decode($source->GetSystemMigrationSource())['fingerprint'];
$op = $operation();
$check($prepare($manager, $rows, $op)['ok'] && $decode($manager->ApplySystemMigration($op))['ok'], 'Source conflict fixture committed');
$source->Change('YamlDraft', $source->Raw('YamlDraft') . "# external change\n");
$check($decode($manager->RollbackSystemMigration($op))['error'] === 'rollback_conflict', 'External source changes are never overwritten by rollback');
$check($decode($source->GetSystemOwnership()) !== [] && !$decode($manager->GetWorkspaceOverview())['ok'], 'Conflict leaves both editors safely fenced with archives retained');
$source->Change('YamlDraft', $originals[$rows[0]['sourceInstance']]['yaml']);
$check($decode($manager->RollbackSystemMigration($op))['ok'], 'After explicit conflict resolution the same rollback can resume');

[$managerID, $manager, $rows, $originals, $parent] = $scenario(1);
$op = $operation();
$check($prepare($manager, $rows, $op)['ok'], 'Corrupt archive fixture prepared');
$journalPath = TakeoverProbe::$storage . '/' . $managerID . '/' . $op . '-journal.json';
$journalBytes = file_get_contents($journalPath);
file_put_contents($journalPath, str_replace('prepared', 'tampered', $journalBytes));
$check(!$decode($manager->ApplySystemMigration($op))['ok'] && $manager->Raw('SystemWorkspace') === '', 'Checksum failure in persistent journal cannot publish the target');
file_put_contents($journalPath, $journalBytes);
$check($decode($manager->ApplySystemMigration($op))['ok'] && $decode($manager->RollbackSystemMigration($op))['ok'], 'Verified original journal can resume after storage repair');

[$managerID, $manager, $rows, $originals, $parent] = $scenario(1);
$sourceID = $rows[0]['sourceInstance'];
IPS_SetProperty($sourceID, 'Active', true);
IPS_ApplyChanges($sourceID);
$source = IPS\InstanceManager::getInstanceInterface($sourceID);
$source->Seed();
$entryLock = 'Z2MA.Owner.' . $sourceID;
$check(IPS_SemaphoreEnter($entryLock, 0), 'Independent public entry fixture holds the owner fence');
try {
    $parent->publish('synthetic/system_0/bridge/state', ['state' => 'online'], true);
}
finally {
    IPS_SemaphoreLeave($entryLock);
}
$check($decode($source->GetTransportState())['bridgeOnline'], 'Normal MQTT replies must not be dropped while another public entry holds the owner fence');
$rows[0]['fingerprint'] = $decode($source->GetSystemMigrationSource())['fingerprint'];
$op = $operation();
$check($prepare($manager, $rows, $op)['ok'], 'In-flight dispatch fixture prepared');
$source->Change('TransportDispatches', '{"synthetic_dispatch":true}', true);
$check(!$decode($manager->ApplySystemMigration($op))['ok'] && $decode($source->GetSystemOwnership()) === [], 'An outbound handoff still on the stack prevents ownership transfer even without a pending response');
$source->Change('TransportDispatches', '', true);
$check($decode($manager->ApplySystemMigration($op))['ok'] && $decode($manager->RollbackSystemMigration($op))['ok'], 'Transfer resumes once the actual handoff has completed');

echo "Migration: private archives, 1/2/8 systems, owner fences, restart, all takeover/return boundaries, conflicts and zero MQTT passed.\n";

// Only delete the synthetic directories created by this test.
foreach ($directories as $directory) {
    if (!is_dir($directory)) continue;
    $resolved = realpath($directory);
    if ($resolved === false || !str_starts_with(basename($resolved), 'z2ma-synthetic-migration-')) throw new RuntimeException('Unsafe synthetic cleanup target');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $entry) {
        if ($entry->isDir() && !$entry->isLink()) rmdir($entry->getPathname());
        else unlink($entry->getPathname());
    }
    rmdir($resolved);
}
