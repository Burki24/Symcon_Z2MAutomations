<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true, 32, JSON_THROW_ON_ERROR);
IPS\Kernel::reset();
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient', 'tests/fixtures/Z2MDevice'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $ok, string $message): void
{ if (!$ok) throw new RuntimeException($message); };
$check(method_exists(Z2MAutomations::class, 'StartWorkspaceSetup'), 'Direct setup must read Z2M without a legacy source instance.');

class SetupManagerProbe extends Z2MAutomations
{
    public static string $storage;
    public array $debug = [];
    public array $reloadedForms = [];
    public bool $failReload = false;
    public string $failAt = '';
    public function Raw(string $name): string
    {
        return $this->ReadAttributeString($name);
    }
    public function LoseViews(): void
    {
        $this->SetBuffer('WorkspaceEditorViews', '');
    }
    protected function MigrationStorageDirectory(): string
    {
        return self::$storage . '/' . $this->InstanceID;
    }
    protected function WorkspaceSetupCheckpoint(string $Step): void
    {
        if ($this->failAt === $Step) {
            $this->failAt = '';
            throw new RuntimeException('Synthetic setup interruption');
        }
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true);
    }
    protected function ReloadForm(): bool
    {
        if ($this->failReload) {
            $this->failReload = false;
            throw new RuntimeException('PRIVATE_RELOAD_FAILURE');
        }
        // The kernel rebuilds the form in a separate entry, not on this PHP object's stack.
        $free = IPS_SemaphoreEnter('Z2MA.Owner.' . $this->InstanceID, 0);
        if ($free) IPS_SemaphoreLeave('Z2MA.Owner.' . $this->InstanceID);
        $fresh = new self($this->InstanceID);
        $status = IPS_GetInstance($this->InstanceID)['InstanceStatus'];
        IPS\InstanceManager::setStatus($this->InstanceID, 101);
        try {
            $fresh->Create();
        } finally {
            IPS\InstanceManager::setStatus($this->InstanceID, $status);
        }
        $fresh->ApplyPersistence($this->GetPersistence());
        $form = json_decode($fresh->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
        $this->reloadedForms[] = ['unlocked' => $free, 'usable' => $form['actions'] !== []];
        unset($fresh);
        gc_collect_cycles();
        return true;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
}
class SetupConnectionProbe extends Z2MConnection
{
    public int $clock = 1000;
    public array $debug = [];
    public function LoseState(): void
    {
        $this->SetBuffer('TransportState', '');
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MConnection/form.json'), true);
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = SetupManagerProbe::class;
$modules[SystemContract::CONNECTION_MODULE]['Class'] = SetupConnectionProbe::class;
$registry->setValue(null, $modules);
$decode = static fn (string $raw): array => json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
$restart = static function (int $id): object
{
    $old = IPS\InstanceManager::getInstanceInterface($id);
    $saved = $old->GetPersistence();
    $class = get_class($old);
    $fresh = new $class($id);
    IPS\InstanceManager::setStatus($id, 101);
    $fresh->Create();
    $fresh->ApplyPersistence($saved);
    if ($old instanceof SetupConnectionProbe) $fresh->clock = $old->clock;
    $property = new ReflectionProperty(IPS\InstanceManager::class, 'interfaces');
    $interfaces = $property->getValue();
    $interfaces[$id] = $fresh;
    $property->setValue(null, $interfaces);
    IPS\InstanceManager::setStatus($id, 102);
    $fresh->ApplyChanges();
    return $fresh;
};
foreach ([[1, false], [2, false], [2, true], [8, false]] as [$count, $split]) {
    SetupManagerProbe::$storage = sys_get_temp_dir() . '/z2ma-setup-' . $op();
    $managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
    $manager = IPS\InstanceManager::getInstanceInterface($managerID);
    $parents = $connections = $documents = $receivers = $catalogs = [];
    $parentID = 0;
    for ($i = 0; $i < $count; $i++) {
        if ($parentID === 0 || $split) {
            $parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
            IPS_ApplyChanges($parentID);
            $parents[$parentID] = IPS\InstanceManager::getInstanceInterface($parentID);
        }
        $topic = 'synthetic/setup_' . $count . '_' . ($split ? 'split' : 'shared') . '_' . $i;
        $connection = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
        IPS_ConnectInstance($connection, $parentID);
        IPS_SetProperty($connection, 'BaseTopic', $topic);
        IPS_SetProperty($connection, 'Active', true);
        IPS_ApplyChanges($connection);
        $connections[$topic] = $connection;
        $device = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
        IPS_ConnectInstance($device, $parentID);
        IPS_SetProperty($device, 'MQTTBaseTopic', $topic);
        IPS_SetProperty($device, 'IEEE', '0x00124b0000000001');
        IPS_ApplyChanges($device);
        $empty = $count === 1;
        $configuration = ['schema_version' => 1, 'revision' => $empty ? 0 : 7,
            'automations'                  => $empty ? [] : [['id' => 'same_rule', 'name' => 'PRIVATE_SETUP_RULE', 'enabled' => true,
                'triggers'                                         => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
                'actions'                                          => [['type' => 'set', 'target' => ['type' => 'device', 'id' => '0x00124b0000000001'], 'values' => ['state' => 'ON']]]]],
            'mesh_peers' => ['synthetic/source']];
        $yaml = $empty ? null : json_encode($configuration, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
        $documents[$topic] = ['yaml' => $yaml, 'configuration' => $configuration];
        $receivers[$topic] = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => ExtensionContract::BundledVersion(),
            'session'                            => sprintf('11111111-1111-4111-8111-%012d', $i + 1), 'state' => 'ready', 'stored_revision' => $empty ? 0 : 7,
            'checksum'                           => $empty ? null : hash('sha256', $yaml), 'automation_count' => $empty ? 0 : 1, 'storage' => $empty ? 'empty' : 'ok',
            'executing'                          => !$empty, 'runtime_version' => 1, 'active_revision' => $empty ? null : 7, 'active_checksum' => $empty ? null : hash('sha256', $yaml),
            'enabled_count'                      => $empty ? 0 : 1, 'runtime_error' => '', 'catalog_version' => 1];
        $catalogs[$topic] = ['devices' => [['id' => '0x00124b0000000001', 'name' => 'Synthetic lamp', 'triggers' => [], 'actions' => [],
            'properties'                         => [['property' => 'state', 'type' => 'binary', 'access' => 3, 'label' => 'State', 'endpoint' => '', 'values' => ['ON', 'OFF']]], 'numeric' => []]], 'groups' => [], 'scenes' => []];
    }
    $drop = '';
    $changeReply = null;
    foreach ($parents as $parent) $parent->onPublish = static function (array $packet) use ($parent, &$receivers, &$documents, &$catalogs, &$drop, &$changeReply): void
    {
        $topic = strstr($packet['Topic'], '/symcon/', true);
        $command = substr($packet['Topic'], strrpos($packet['Topic'], '/') + 1);
        if ($drop === $command) return;
        $data = $receivers[$topic];
        if ($command === 'editor') $data += ['editor_version' => 1] + $documents[$topic] + $catalogs[$topic];
        if ($command === 'validate') {
            $doc = json_decode($packet['Payload']['yaml'], true);
            $data = ['valid' => true, 'revision' => $doc['revision'], 'checksum' => hash('sha256', $packet['Payload']['yaml']), 'automation_count' => count($doc['automations']), 'executing' => false];
        }
        if ($command === 'store') $data += ['unchanged' => true];
        $reply = ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data];
        if ($changeReply !== null && !$changeReply($packet, $reply)) return;
        $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), $reply);
    };
    $overview = $decode($manager->GetWorkspaceOverview());
    $check($decode($manager->ConfigureWorkspace(json_encode(array_values($connections)), $overview['fingerprint'], $op()))['ok'], 'Map direct connection instances.');
    $overview = $decode($manager->GetWorkspaceOverview());
    $detail = static function (string $system) use (&$manager, $decode): array
    { return $decode($manager->GetWorkspaceSystem($system)); };
    $beforeReceivers = $receivers;
    $before = [];
    foreach ($overview['systems'] as $system) $before[$system['systemId']] = $detail($system['systemId']);
    $operations = [];
    $preserved = [];
    foreach ($overview['systems'] as $system) {
        $systemID = $system['systemId'];
        $record = $detail($systemID);
        $topic = $record['binding']['baseTopic'];
        $operation = $op();
        $operations[] = $operation;
        $read = $decode($manager->StartWorkspaceSetup($systemID, 1, $record['fingerprint'], $operation));
        $check($read['ok'] && $read['setup']['phase'] === 'preview', 'Direct read yields a secured preview: ' . json_encode($read));
        $check($detail($systemID)['data'] === $record['data'], 'Preview never replaces the current local document.');
        $check($decode(IPS\InstanceManager::getInstanceInterface($connections[$topic])->GetSystemOwnership()) === [], 'Read preview does not claim the connection.');
        $check(!$decode($manager->GetWorkspaceTransferStatus($systemID))['transfer']['canValidate'], 'Preview is not an implicit permission to transfer.');
        $confirmed = $decode($manager->ConfirmWorkspaceSetup($operation));
        $check($confirmed['ok'] && $confirmed['setup']['phase'] === 'complete', 'Direct setup completes without a legacy instance: ' . json_encode($confirmed));
        $reloaded = end($manager->reloadedForms);
        $check($reloaded !== false && $reloaded['unlocked'] && $reloaded['usable'], 'Automatic setup form rebuild must run after the owner lock is released and show the central controls.');
        $after = $detail($systemID);
        $document = $after['data']['document'];
        $check($after['binding']['sourceInstance'] === 0 && $document !== null, 'No artificial source instance or missing document.');
        $check($document['editorDraft']['baseRevision'] === ($count === 1 ? 0 : 7), 'Correct stored revision becomes the editing base.');
        $check($document['editorDraft']['configuration']['mesh_peers'] === ['synthetic/source'], 'Directed peer grants are retained.');
        if ($count === 1) $check($document['editorDraft']['configuration']['revision'] === 1 && $document['editorDraft']['configuration']['automations'] === [], 'An empty receiver yields only a new local revision-one draft.');
        else $check($document['yaml'] === $documents[$topic]['yaml'] && $document['editorDraft']['configuration'] === $documents[$topic]['configuration'], 'Exact YAML bytes, IDs, names, flags and definitions preserved.');
        $check($decode($manager->GetWorkspaceTransferStatus($systemID))['transfer']['canValidate'], 'Direct owner is accepted by the existing transfer API.');
        if (count($operations) === 1) {
            $designer = $decode($manager->GetDesignerData());
            $check($designer['draftAvailable'] && $designer['catalogAvailable'] && count($designer['devices']) === 1 && $designer['createAllowed'], 'Direct setup provides the actual browser editor with a usable draft, catalog and native switching rights.');
        }
        $check($decode($manager->ConfirmWorkspaceSetup($operation))['setup']['phase'] === 'complete', 'Repeated confirmation does not re-import.');
        $check(!$decode($manager->StartWorkspaceSetup($systemID, 1, $after['fingerprint'], $op()))['ok'], 'Existing documents cannot be overwritten by another import.');
        foreach ($preserved as $previous => $data) $check($detail($previous)['data'] === $data, 'Another direct setup preserves existing drafts, IDs and grants.');
        $preserved[$systemID] = $after['data'];
    }
    $check($receivers === $beforeReceivers, 'Stored and active Z2M state remain byte-for-byte unchanged.');
    foreach ($parents as $parent) foreach ($parent->sent as $packet) $check(preg_match('~/request/(status|editor)$~', $packet['Topic']) === 1, 'Setup sends only read-only commands.');
    $last = end($overview['systems']);
    $lastID = $last['systemId'];
    $rollback = $decode($manager->RollbackWorkspaceSetup(end($operations)));
    $check($rollback['ok'] && $rollback['setup']['phase'] === 'rolled_back', 'A local return path is available before external storage: ' . json_encode($rollback));
    $reloaded = end($manager->reloadedForms);
    $check($reloaded['unlocked'] && $reloaded['usable'], 'The local return also rebuilds the usable form outside the ownership lock.');
    $check($detail($lastID)['data'] === $before[$lastID]['data'], 'Return restores only the target local data.');
    foreach (array_slice($overview['systems'], 0, -1) as $system) $check($detail($system['systemId'])['data']['document'] !== null, 'Return preserves other owned systems.');

    if ($count !== 2 || $split) continue;
    $topic = $last['binding']['baseTopic'];
    $connection = IPS\InstanceManager::getInstanceInterface($connections[$topic]);
    $sent = static fn (): int => array_sum(array_map(static fn ($parent): int => count($parent->sent), $parents));
    $begin = static function (?string $operation = null) use (&$manager, $lastID, $detail, $op, $decode): array
    {
        $record = $detail($lastID);
        return $decode($manager->StartWorkspaceSetup($lastID, 1, $record['fingerprint'], $operation ?? $op()));
    };
    $status = static function () use (&$manager, $decode): array
    { return $decode($manager->GetWorkspaceSetupStatus()); };
    $cancel = static function () use (&$manager, $status, $decode): void
    { $decode($manager->CancelWorkspaceSetup($status()['setup']['operation'])); };
    $beforeSecond = $detail($lastID);
    $otherID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
    $countBeforeDenied = $sent();
    $check(!$decode($connection->RequestWorkspaceSnapshot($otherID, $op(), false))['ok'], 'A free owner parameter without a real setup journal cannot read snapshots.');
    $check(!$decode($connection->ClaimWorkspaceConnection($otherID, $op(), json_encode($last['binding']), false))['ok'] && $sent() === $countBeforeDenied, 'A direct claim without current authority cannot alter ownership or send MQTT.');

    $read = $begin();
    $reads = $sent();
    $check($read['setup']['canConfirm'], 'Read preview can be explicitly confirmed.');
    $replay = $decode($manager->StartWorkspaceSetup($lastID, 1, $beforeSecond['fingerprint'], $read['setup']['operation']));
    $check($replay['setup']['phase'] === 'preview' && $sent() === $reads, 'Repeated read operation does not resend.');
    $cancel();
    $check($detail($lastID)['data'] === $beforeSecond['data'] && $decode($connection->GetSystemOwnership()) === [], 'Cancel leaves data and ownership unchanged.');

    $drop = 'status';
    $check($begin()['setup']['phase'] === 'reading', 'A lost reply remains pending.');
    $connection->clock += 11;
    $check($status()['setup']['phase'] === 'error' && $sent() === $reads + 1, 'Timeout does not repeat network reads or claim owners.');
    $cancel();
    $drop = '';

    $drop = 'status';
    $check($begin()['setup']['phase'] === 'reading', 'Begin another pending read.');
    $cancel();
    $drop = '';
    $check($begin()['setup']['phase'] === 'preview', 'Cancel releases its pending local read immediately.');
    $cancel();

    $changeReply = static function (array $packet, array &$reply): bool
    {
        if (str_ends_with($packet['Topic'], '/editor')) $reply['data']['yaml'] .= 'corrupt';
        return true;
    };
    $check(!$begin()['ok'], 'Wrong YAML checksum fails closed without an empty replacement.');
    $check($status()['setup']['phase'] === 'error' && $detail($lastID)['data'] === $beforeSecond['data'], 'Rejected data cannot change drafts or owners.');
    $cancel();
    $changeReply = null;

    // An asynchronous reply completes through the timer, just like the real installation.
    $read = $begin();
    $drop = 'status';
    $reloadCount = count($manager->reloadedForms);
    $check($decode($manager->ConfirmWorkspaceSetup($read['setup']['operation']))['setup']['phase'] === 'verifying'
        && count($manager->reloadedForms) === $reloadCount, 'Pending verification does not publish a premature form.');
    $parent = $parents[$last['binding']['mqttParent']];
    $packet = end($parent->sent);
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
        'transaction'                                                                              => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $receivers[$topic]]);
    $drop = '';
    $sentBeforeRefresh = $sent();
    $manager->RefreshWorkspaceSetup();
    $reloaded = end($manager->reloadedForms);
    $check(
        $status()['setup']['phase'] === 'complete' && count($manager->reloadedForms) === $reloadCount + 1
        && $reloaded['unlocked'] && $reloaded['usable'] && $sent() === $sentBeforeRefresh,
        'Timer completion rebuilds one usable form after releasing the lock, without another MQTT request.'
    );
    $check($decode($manager->RollbackWorkspaceSetup($read['setup']['operation']))['ok'], 'Restore the asynchronous setup locally.');

    // Reopening a form can itself finish an interrupted local commit; do not reload it recursively.
    $read = $begin();
    $manager->failAt = 'published';
    $decode($manager->ConfirmWorkspaceSetup($read['setup']['operation']));
    $reloadCount = count($manager->reloadedForms);
    $form = $decode($manager->GetConfigurationForm());
    $check(
        $form['actions'] !== [] && $status()['setup']['phase'] === 'complete' && count($manager->reloadedForms) === $reloadCount,
        'A rebuilding form displays the recovered state directly, without another reload loop.'
    );
    $check($decode($manager->RollbackWorkspaceSetup($read['setup']['operation']))['ok'], 'Restore the recovered setup locally.');

    $read = $begin();
    $manager->failReload = true;
    $check($decode($manager->ConfirmWorkspaceSetup($read['setup']['operation']))['setup']['phase'] === 'complete'
        && $detail($lastID)['data']['document']['yaml'] === $documents[$topic]['yaml'], 'A native form reload failure cannot undo or misreport a completed setup.');
    $check(str_contains(implode(' ', $manager->debug), 'Das Formular konnte nicht aktualisiert werden.')
        && !str_contains(implode(' ', $manager->debug), 'PRIVATE_RELOAD_FAILURE'), 'Reload failure debug explains recovery without exposing exception contents.');
    $check($decode($manager->RollbackWorkspaceSetup($read['setup']['operation']))['ok'], 'Restore setup after a failed display update.');

    $read = $begin();
    $manager = $restart($managerID);
    $connection = $restart($connections[$topic]);
    $reads = $sent();
    $check($status()['setup']['phase'] === 'preview' && $sent() === $reads, 'A saved preview survives lost transient buffers without rereading.');
    $check($decode($manager->ConfirmWorkspaceSetup($read['setup']['operation']))['setup']['phase'] === 'complete', 'An explicit confirmation rechecks the receiver after a restart.');
    $check($decode($manager->RollbackWorkspaceSetup($read['setup']['operation']))['setup']['phase'] === 'rolled_back', 'Return after persisted preview.');

    $read = $begin();
    $receivers[$topic]['session'] = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    $confirmed = $decode($manager->ConfirmWorkspaceSetup($read['setup']['operation']));
    $check(!$confirmed['ok'] && $status()['setup']['phase'] === 'error' && $decode($connection->GetSystemOwnership()) === [], 'Fresh verification rejects a restarted receiver before ownership.');
    $cancel();

    // Interrupted local commits resume without any new MQTT request.
    foreach (['claimed', 'owner', 'published'] as $step) {
        $read = $begin();
        $manager->failAt = $step;
        $decode($manager->ConfirmWorkspaceSetup($read['setup']['operation']));
        $sentBeforeResume = $sent();
        $manager = $restart($managerID);
        $connection = $restart($connections[$topic]);
        $resumed = $status();
        $check($resumed['setup']['phase'] === 'complete' && $sent() === $sentBeforeResume, 'Local recovery after ' . $step . ' does not read or write Z2M again.');
        $check($detail($lastID)['data']['document']['yaml'] === $documents[$topic]['yaml'], 'Recovery preserves exact document bytes.');
        $check($decode($manager->RollbackWorkspaceSetup($read['setup']['operation']))['setup']['phase'] === 'rolled_back', 'Local return after resumed claim.');
    }

    foreach (['restored', 'released'] as $step) {
        $read = $begin();
        $decode($manager->ConfirmWorkspaceSetup($read['setup']['operation']));
        $manager->failAt = $step;
        $decode($manager->RollbackWorkspaceSetup($read['setup']['operation']));
        $sentBeforeResume = $sent();
        $manager = $restart($managerID);
        $check($status()['setup']['phase'] === 'rolled_back' && $sent() === $sentBeforeResume, 'Interrupted return resumes after ' . $step . ' without MQTT.');
        $check($detail($lastID)['data'] === $beforeSecond['data'] && $decode($connection->GetSystemOwnership()) === [], 'Return releases only the selected connection.');
    }

    // The native form uses real generated callbacks and keeps the overwrite guard.
    $overviewNow = $decode($manager->GetWorkspaceOverview());
    $check($decode($manager->SelectWorkspaceSystem($lastID, $overviewNow['fingerprint'], $op()))['ok'], 'Select direct setup system for native UI.');
    $fields = [];
    $visit = static function (array $items) use (&$visit, &$fields): void
    {
        foreach ($items as $item) {
            if (isset($item['name'])) $fields[$item['name']] = $item;
            if (isset($item['items'])) $visit($item['items']);
        }
    };
    $visit($decode($manager->GetConfigurationForm())['actions']);
    $check($fields['WorkspaceSetupRead']['enabled'] && !$fields['WorkspaceSetupConfirm']['enabled'], 'Native setup starts with read only.');
    $id = $managerID;
    $WorkspaceEditorFingerprint = $detail($lastID)['fingerprint'];
    eval($fields['WorkspaceSetupRead']['onClick']);
    $visit($decode($manager->GetConfigurationForm())['actions']);
    $WorkspaceSetupOperation = $fields['WorkspaceSetupOperation']['value'];
    $check($fields['WorkspaceSetupConfirm']['enabled'] && str_contains($fields['WorkspaceSetupConfirm']['confirm'], $topic), 'Confirmation names the actual topic.');
    eval($fields['WorkspaceSetupConfirm']['onClick']);
    $check($status()['setup']['phase'] === 'complete', 'Native generated callback completes the real setup workflow.');
    $snapshot = $manager->Raw('SystemWorkspace');
    $check(!$begin()['ok'] && $manager->Raw('SystemWorkspace') === $snapshot, 'Repeated setup cannot overwrite the imported draft.');
    $transfer = static function (string $action) use (&$manager, $lastID, $detail, $decode, $op): array
    {
        $view = $decode($manager->GetWorkspaceTransferStatus($lastID))['transfer'];
        return $decode($manager->RequestWorkspaceTransfer($lastID, 1, $detail($lastID)['fingerprint'], $action, $op(), $view['context']));
    };
    $check($transfer('validate')['transfer']['canStore'], 'Directly set up systems use the existing correlated validation workflow.');
    $stored = $transfer('store');
    $check(($stored['transfer']['status'] ?? '') === 'stored', 'Idempotent transfer works after direct setup, without another owner transition: ' . json_encode($stored));
    $check(!$decode($manager->RollbackWorkspaceSetup($WorkspaceSetupOperation))['ok'], 'Possible external writing blocks the purely local return.');
    foreach ($parents as $parent) foreach ($parent->sent as $packet) $check(preg_match('~/request/(activate|deactivate|run_once|stop_rule|restart)$~', $packet['Topic']) !== 1, 'Neither setup nor subsequent transfer activates or stops automations.');
    $debug = implode(' ', $manager->debug) . implode(' ', $connection->debug);
    $check(str_contains($debug, 'Direkte Einrichtung abgeschlossen') && !str_contains($debug, 'PRIVATE_SETUP_RULE'), 'German setup debug explains success without names or YAML.');
}
echo "Direct setup: 1/2/8 systems, shared/separate brokers, exact YAML, empty receiver, owners and local return passed.\n";
