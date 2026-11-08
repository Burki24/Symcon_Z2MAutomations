<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
IPS\Kernel::reset();
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient', 'tests/fixtures/Z2MDevice'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

class TransferManagerProbe extends Z2MAutomations
{
    public static string $storage;
    public array $debug = [];
    public array $updates = [];
    public string $requestBody = '';
    public function Hook(string $path, string $token, array $headers = [], string $method = 'GET'): array
    {
        $_SERVER = ['REQUEST_URI'    => '/hook/Z2MAutomationsDesigner' . $this->InstanceID . '/' . $path, 'REQUEST_METHOD' => $method,
            'HTTP_X_Z2MA_READ_TOKEN' => $token, 'HTTP_HOST' => '127.0.0.1:3777', 'HTTP_ORIGIN' => 'http://127.0.0.1:3777', 'CONTENT_TYPE' => 'application/json'] + $headers;
        ob_start();
        try {
            $this->ProcessHookData();
            return [http_response_code(), ob_get_contents()];
        }
        finally {
            ob_end_clean();
        }
    }
    public function Seed(): void
    {
        $scope = json_decode($this->GetBuffer('TransportState'), true)['scope'];
        $configuration = ['schema_version' => 1, 'revision' => 1, 'automations' => [['id' => 'same_rule', 'name' => 'PRIVATE_RULE', 'enabled' => false,
            'triggers'                                                                    => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
            'actions'                                                                     => [['type' => 'set', 'target' => ['type' => 'device', 'id' => '0x00124b0000000001'], 'values' => ['state' => 'ON']]]]]];
        $yaml = json_encode($configuration) . "\n";
        $this->WriteAttributeString('YamlDraft', $yaml);
        $this->WriteAttributeString('EditorDraft', json_encode(['scope' => $scope, 'session' => '11111111-1111-4111-8111-111111111111',
            'baseRevision'                                              => 0, 'baseChecksum' => null, 'configuration' => $configuration, 'draftChecksum' => hash('sha256', $yaml)]));
        $this->WriteAttributeString('EditorSnapshot', json_encode(['scope' => $scope, 'loadedAt' => 1, 'devices' => [], 'groups' => [], 'scenes' => []]));
    }
    public function Raw(string $attribute): string
    {
        return $this->ReadAttributeString($attribute);
    }
    public function ResetTransient(): void
    {
        $this->SetBuffer('WorkspaceEditorViews', '');
    }
    public function UpdateFormField(string $Field, string $Parameter, mixed $Value): bool
    {
        $this->updates[$Field][$Parameter] = $Value;
        return true;
    }
    protected function DesignerRequestBody(): string
    {
        return $this->requestBody;
    }
    protected function RegisterHook(string $HookPath): bool
    {
        return true;
    }
    protected function MigrationStorageDirectory(): string
    {
        return self::$storage . '/' . $this->InstanceID;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true);
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
}
class TransferConnectionProbe extends Z2MConnection
{
    public int $clock = 1000;
    public array $debug = [];
    public function ResetTransient(): void
    {
        $this->SetBuffer('TransportState', '');
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MConnection/form.json'), true);
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = TransferManagerProbe::class;
$modules[SystemContract::CONNECTION_MODULE]['Class'] = TransferConnectionProbe::class;
$registry->setValue(null, $modules);
$check = static function (bool $condition, string $message): void
{ if (!$condition) throw new RuntimeException($message); };
$decode = static fn (string $raw): array => json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));

foreach ([[1, false], [2, false], [2, true], [8, false]] as [$count, $separate]) {
    TransferManagerProbe::$storage = sys_get_temp_dir() . '/z2ma-transfer-synthetic-' . bin2hex(random_bytes(8));
    $managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
    $manager = IPS\InstanceManager::getInstanceInterface($managerID);
    $parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
    IPS_ApplyChanges($parentID);
    $parent = IPS\InstanceManager::getInstanceInterface($parentID);
    $parents = [$parentID => $parent];
    $mapping = $connections = $receivers = $native = [];
    for ($i = 0; $i < $count; $i++) {
        if ($separate && $i > 0) {
            $parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
            IPS_ApplyChanges($parentID);
            $parent = IPS\InstanceManager::getInstanceInterface($parentID);
            $parents[$parentID] = $parent;
        }
        $topic = 'synthetic/transfer_' . $count . '_' . ($separate ? 'split' : 'shared') . '_' . $i;
        $source = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
        IPS_ConnectInstance($source, $parentID);
        IPS_SetProperty($source, 'BaseTopic', $topic);
        IPS_ApplyChanges($source);
        $sourceModule = IPS\InstanceManager::getInstanceInterface($source);
        $sourceModule->Seed();
        $connection = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
        IPS_ConnectInstance($connection, $parentID);
        IPS_SetProperty($connection, 'BaseTopic', $topic);
        IPS_SetProperty($connection, 'Active', true);
        IPS_ApplyChanges($connection);
        $device = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
        IPS_ConnectInstance($device, $parentID);
        IPS_SetProperty($device, 'MQTTBaseTopic', $topic);
        IPS_SetProperty($device, 'IEEE', '0x00124b0000000001');
        IPS_ApplyChanges($device);
        $native[$topic] = $device;
        $mapping[] = ['sourceInstance' => $source, 'connectionInstance' => $connection, 'fingerprint' => $decode($sourceModule->GetSystemMigrationSource())['fingerprint']];
        $connections[$topic] = IPS\InstanceManager::getInstanceInterface($connection);
        $receivers[$topic] = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => ExtensionContract::BundledVersion(),
            'extension_build'                    => ExtensionContract::BundledBuild(), 'session' => sprintf('11111111-1111-4111-8111-%012d', $i + 1), 'state' => 'ready',
            'stored_revision'                    => 0, 'checksum' => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false,
            'runtime_version'                    => 1, 'active_revision' => null, 'active_checksum' => null, 'enabled_count' => 0, 'runtime_error' => '', 'catalog_version' => 1];
    }
    if ($count === 1) {
        $unclaimed = IPS\InstanceManager::getInstanceInterface(IPS_CreateInstance(SystemContract::SOURCE_MODULE));
        $check($decode($unclaimed->ConfigureWorkspace(json_encode(array_column($mapping, 'connectionInstance')), $decode($unclaimed->GetWorkspaceOverview())['fingerprint'], $op()))['ok'], 'Preparatory mapping does not claim a connection.');
        $candidate = $decode($unclaimed->GetWorkspaceOverview())['systems'][0]['systemId'];
        $check($decode($unclaimed->GetWorkspaceTransferStatus($candidate))['transfer']['blocked'] === 'ownership_required', 'Mapping alone cannot authorize a Z2M write.');
    }
    $migration = $op();
    $prepared = $decode($manager->PrepareSystemMigration(json_encode($mapping), $decode($manager->GetWorkspaceOverview())['fingerprint'], $migration));
    $check($prepared['ok'], 'Prepare real local takeover for transfer: ' . json_encode($prepared));
    $check($decode($manager->ApplySystemMigration($migration))['ok'], 'Apply local owner transfer.');
    $rejectValidation = false;
    $drop = '';
    $statusAfterStore = false;
    $onReply = null;
    foreach ($parents as $parent) $parent->onPublish = static function (array $packet) use ($parent, &$receivers, &$rejectValidation, &$drop, &$statusAfterStore, &$onReply): void
    {
        $topic = strstr($packet['Topic'], '/bridge/', true) ?: strstr($packet['Topic'], '/symcon/', true);
        $command = substr($packet['Topic'], strrpos($packet['Topic'], '/') + 1);
        if ($drop === $command) return;
        $request = $packet['Payload'];
        $status = 'ok';
        $data = $receivers[$topic];
        $error = null;
        if ($command === 'health_check') $data = ['healthy' => true];
        if ($command === 'catalog') $data += ['catalog_version' => 1, 'devices' => [['id' => '0x00124b0000000001', 'name' => 'Synthetic lamp',
            'triggers'                                                                    => [['property' => 'state', 'value' => 'ON']], 'actions' => [['property' => 'state', 'value' => 'ON']],
            'properties'                                                                  => [['property' => 'state', 'type' => 'binary', 'access' => 3, 'label' => 'State', 'endpoint' => '', 'values' => ['ON', 'OFF']]], 'numeric' => []]], 'groups' => [], 'scenes' => []];
        if ($command === 'validate') {
            $document = json_decode($request['yaml'], true);
            $data = ['valid' => true, 'revision' => $document['revision'], 'checksum' => hash('sha256', $request['yaml']), 'automation_count' => count($document['automations']), 'executing' => false];
            if ($rejectValidation) {
                $status = 'error';
                $data = null;
                $error = ['code' => 'invalid_schema', 'details' => ['issues' => [['path' => '/automations/0/actions', 'reason' => 'minItems', 'limit' => 1]], 'truncated' => false]];
            }
        }
        if ($command === 'store') {
            $document = json_decode($request['yaml'], true);
            $receivers[$topic] = array_replace($receivers[$topic], ['stored_revision' => $document['revision'], 'checksum' => hash('sha256', $request['yaml']),
                'automation_count'                                                    => count($document['automations']), 'storage' => 'ok', 'executing' => false]);
            $data = $receivers[$topic] + ['unchanged' => false];
            if ($statusAfterStore) $drop = 'status';
        }
        $reply = ['protocol_version' => 1, 'transaction' => $request['transaction'], 'status' => $status, 'data' => $data];
        if ($error !== null) $reply['error'] = $error;
        if ($onReply !== null && !$onReply($packet, $reply)) return;
        $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), $reply);
    };
    foreach ($connections as $connection) {
        $connection->CheckHealth(false);
        $connection->RequestDeviceCatalog();
    }
    $overview = $decode($manager->GetWorkspaceOverview());
    foreach ($overview['systems'] as $system) $check($decode($manager->RefreshWorkspaceCatalog($system['systemId'], 1, $system['fingerprint'], $op()))['ok'], 'Refresh migrated catalog.');
    $systems = array_column($overview['systems'], 'systemId');
    $status = static fn (string $id): array => $decode($manager->GetWorkspaceTransferStatus($id));
    $detail = static fn (string $id): array => $decode($manager->GetWorkspaceSystem($id));
    $request = static function (string $id, string $action, ?string $operation = null) use ($manager, $detail, $status, $op, $decode): array
    {
        $view = $status($id)['transfer'];
        $record = $detail($id);
        return $decode($manager->RequestWorkspaceTransfer($id, $record['generation'], $record['fingerprint'], $action, $operation ?? $op(), $view['context']));
    };
    $sentCount = static fn (): int => array_sum(array_map(static fn ($parent): int => count($parent->sent), $parents));
    $first = $systems[0];
    $before = $manager->Raw('SystemWorkspace');
    $sent = $sentCount();
    foreach ($systems as $system) $check($status($system)['transfer']['canValidate'], 'Current owner, document and connection permit validation.');
    $check($sentCount() === $sent && $manager->Raw('SystemWorkspace') === $before, 'Status reads send nothing and do not mutate drafts.');
    $check(!$request($first, 'store')['ok'] && $sentCount() === $sent, 'Storing before validation sends nothing.');
    foreach ($systems as $system) {
        $result = $request($system, 'validate');
        $check($result['ok'] && $result['transfer']['status'] === 'validated' && $result['transfer']['canStore'], 'Real correlated MQTT validation: ' . json_encode($result));
    }
    $otherData = [];
    foreach ($systems as $system) $otherData[$system] = $detail($system)['data'];
    $stored = $request($first, 'store');
    $check($stored['ok'] && $stored['transfer']['status'] === 'stored', 'Store requires matching receipt and final status: ' . json_encode($stored));
    $after = $detail($first)['data'];
    $check($after['document']['yaml'] === $otherData[$first]['document']['yaml'] && $after['document']['editorDraft']['baseRevision'] === 1, 'Advance only the editing base; exact YAML is retained.');
    foreach (array_slice($systems, 1) as $system) $check($detail($system)['data'] === $otherData[$system], 'Store never mutates another system with equal rule/device IDs.');
    $check($decode($manager->RollbackSystemMigration($migration))['error'] === 'z2m_write_started', 'A local rollback cannot falsely restore Z2M after a store.');
    foreach ($parents as $parent) $check(array_filter($parent->sent, static fn (array $p): bool => preg_match('~/request/(activate|deactivate|run_once|stop_rule|restart)$~', $p['Topic']) === 1) === [], 'Validation and transfer never activate, stop, run or restart.');
    $debug = implode(' ', $manager->debug);
    $check(str_contains($debug, 'genau dieses Systems') && !str_contains($debug, 'PRIVATE_RULE'), 'German manager debug identifies system boundaries without names or YAML.');

    if ($count !== 2 || $separate) continue;
    $second = $systems[1];
    $secondTopic = array_keys($connections)[1];
    $connection = $connections[$secondTopic];
    $update = static function (string $id, callable $change) use ($detail, $manager, $decode, $op, $check): void
    {
        $record = $detail($id);
        $data = $record['data'];
        $change($data);
        $data['document']['yaml'] = json_encode($data['document']['editorDraft']['configuration']) . "\n";
        $data['document']['editorDraft']['draftChecksum'] = hash('sha256', $data['document']['yaml']);
        $check($decode($manager->UpdateWorkspaceSystem($id, $record['generation'], $record['fingerprint'], json_encode($data), $op()))['ok'], 'Synthetic local edit accepted.');
    };
    $rejectValidation = true;
    $failed = $request($second, 'validate');
    $check($failed['transfer']['status'] === 'error' && str_contains($failed['transfer']['details'], 'PRIVATE_RULE') && str_contains($failed['transfer']['details'], '1'), 'Detailed validation feedback names rule and action.');
    $feedback = $failed['transfer']['details'];
    $connection->RequestExtensionStatus();
    $manager->RefreshWorkspaceTransfers();
    $check($status($second)['transfer']['details'] === $feedback, 'Unrelated status reads preserve detailed validation feedback.');
    $rejectValidation = false;
    $view = $status($second)['transfer'];
    $record = $detail($second);
    $operation = $op();
    $args = [$second, 1, $record['fingerprint'], 'validate', $operation, $view['context']];
    $check($decode($manager->RequestWorkspaceTransfer(...$args))['transfer']['canStore'], 'Validation after schema rejection succeeds.');
    $sent = $sentCount();
    $check($decode($manager->RequestWorkspaceTransfer(...$args))['ok'] && $sentCount() === $sent, 'Replayed accepted operation never sends again.');
    $args[3] = 'store';
    $check(!$decode($manager->RequestWorkspaceTransfer(...$args))['ok'] && $sentCount() === $sent, 'Operation ID reused for a different action is rejected.');
    $update($second, static function (array &$data): void
    { $data['document']['editorDraft']['configuration']['automations'][0]['name'] = 'PRIVATE_CHANGED'; });
    $check(!$request($second, 'store')['ok'] && $sentCount() === $sent, 'An edited checksum invalidates a successful validation.');
    $update($second, static function (array &$data): void
    { $data['work']['rule'] = ['dirty' => true, 'input' => ['name' => 'PRIVATE_UNFINISHED']]; });
    $check($request($second, 'validate')['error'] === 'open_input' && $sentCount() === $sent, 'Open input blocks validation without overwriting it.');
    $update($second, static function (array &$data): void
    { $data['work']['rule'] = null; });

    // One slow system does not block another system on the same MQTT parent.
    $drop = 'validate';
    $pending = $request($second, 'validate');
    $check($pending['transfer']['pending'], 'A missing reply remains pending.');
    $drop = '';
    $check($request($first, 'validate')['transfer']['canStore'], 'Another system remains independently usable.');
    $connection->clock += 11;
    $check($status($second)['transfer']['status'] === 'timeout', 'Pending validation expires without retry.');

    // Wrong protocol, retained and wrong transaction responses cannot release store.
    $onReply = static function (array $packet, array $reply) use ($parent, $connection): bool
    {
        if (!str_ends_with($packet['Topic'], '/validate')) return true;
        $topic = str_replace('/request/', '/response/', $packet['Topic']);
        $parent->publish($topic, $reply, true);
        $parent->publish($topic, array_replace($reply, ['transaction' => str_repeat('f', 32)]));
        $parent->publish(str_replace('/validate', '/store', $topic), $reply);
        return false;
    };
    $check($request($second, 'validate')['transfer']['pending'], 'Uncorrelated replies do not finish validation.');
    $connection->clock += 11;
    $check(!$status($second)['transfer']['canStore'], 'Invalid responses cannot enable store.');
    $onReply = static function (array $packet, array $reply) use ($parent): bool
    {
        if (!str_ends_with($packet['Topic'], '/validate')) return true;
        $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), array_replace($reply, ['protocol_version' => 9]));
        return false;
    };
    $check($request($second, 'validate')['transfer']['status'] === 'error', 'A correlated incompatible protocol is rejected explicitly.');
    $onReply = null;
    $check($request($second, 'validate')['transfer']['canStore'], 'Fresh proof after timeout.');
    IPS_DisconnectInstance($native[$secondTopic]);
    $sent = $sentCount();
    $check($request($second, 'store')['error'] === 'actions_unavailable' && $sentCount() === $sent, 'A lost native switch binding prevents storage before MQTT.');
    IPS_ConnectInstance($native[$secondTopic], $parentID);
    $receivers[$secondTopic]['session'] = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
    $storePackets = static fn (): int => count(array_filter($parent->sent, static fn (array $p): bool => str_ends_with($p['Topic'], '/store')));
    $stores = $storePackets();
    $check(!$request($second, 'store')['transfer']['canStore'] && $storePackets() === $stores, 'A changed receiver session invalidates the receipt before wire store.');

    // Store acknowledgement without final status is explicitly unconfirmed, not successful.
    $check($request($second, 'validate')['transfer']['canStore'], 'Validation follows new receiver session.');
    $connection->RequestDeviceCatalog();
    $check($decode($manager->RefreshWorkspaceCatalog($second, 1, $detail($second)['fingerprint'], $op()))['ok'], 'Refresh device proof for the changed receiver session.');
    $statusAfterStore = true;
    $check($request($second, 'store')['transfer']['pending'], 'File accepted but final status pending.');
    $connection->clock += 11;
    $check($status($second)['transfer']['status'] === 'stored_unconfirmed' && $detail($second)['data']['document']['editorDraft']['baseRevision'] === 0, 'Unconfirmed storage never advances the local editing base.');
    $statusAfterStore = false;
    $drop = '';
    $stores = $storePackets();
    $connection->ResetTransient();
    $manager->ResetTransient();
    $check(!$status($second)['transfer']['canStore'] && $storePackets() === $stores, 'Restart preserves result but never resends a store or validation proof.');

    // Native and authenticated browser entry points use exactly the same owner-bound workflow.
    $connection->CheckHealth(false);
    $connection->RequestDeviceCatalog();
    $check($decode($manager->RefreshWorkspaceCatalog($second, 1, $detail($second)['fingerprint'], $op()))['ok'], 'Refresh after transient transport reset.');
    $token = substr(strrchr($manager->OpenAutomationDesigner(), '#'), 1);
    $record = $detail($second);
    $view = $status($second)['transfer'];
    $headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $second, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $record['fingerprint']];
    $manager->requestBody = json_encode(['action' => 'validate', 'context' => $view['context'], 'requestId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa']);
    [$code, $raw] = $manager->Hook('transfer', 'wrong', $headers, 'POST');
    $check($code === 403, 'Transfer requires the designer token.');
    [$code] = $manager->Hook('transfer', $token, $headers, 'GET');
    $check($code === 405, 'Transfer never runs through GET.');
    [$code, $raw] = $manager->Hook('transfer', $token, $headers, 'POST');
    $check($code === 200 && $decode($raw)['transfer']['canStore'], 'Browser validate uses real correlated transport: ' . $raw);
    $check($decode($manager->SelectWorkspaceSystem($second, $decode($manager->GetWorkspaceOverview())['fingerprint'], $op()))['ok'], 'Select stored system for native form.');
    $form = $decode($manager->GetConfigurationForm());
    $fields = [];
    $visit = static function (array $items) use (&$visit, &$fields): void
    {
        foreach ($items as $item) {
            if (isset($item['name'])) $fields[$item['name']] = $item;
            if (isset($item['items'])) $visit($item['items']);
        }
    };
    $visit($form['actions']);
    $check($fields['WorkspaceStore']['enabled'] && str_contains($fields['WorkspaceStore']['confirm'], 'stoppt'), 'Native form exposes validated store and explains stopping.');
    $id = $managerID;
    $WorkspaceEditorFingerprint = $detail($second)['fingerprint'];
    $WorkspaceTransferContext = $fields['WorkspaceTransferContext']['value'];
    eval($fields['WorkspaceStore']['onClick']);
    $check($status($second)['transfer']['status'] === 'stored', 'Actual generated native callback stores the selected system only.');
    $sent = $sentCount();
    [$code, $raw] = $manager->Hook('transfer-status', $token, $headers);
    $check($code === 200 && $decode($raw)['transfer']['status'] === 'stored' && $sentCount() === $sent, 'Browser status GET sends no MQTT.');

    $update($second, static function (array &$data): void
    {
        $data['document']['editorDraft']['configuration']['revision'] = 2;
        $data['document']['editorDraft']['configuration']['automations'][0]['name'] = 'PRIVATE_BEFORE_STORE';
    });
    $check($request($second, 'validate')['transfer']['canStore'], 'Prepare next revision.');
    $onReply = static function (array $packet, array $reply) use ($update, $second): bool
    {
        if (str_ends_with($packet['Topic'], '/store')) $update($second, static function (array &$data): void
        { $data['document']['editorDraft']['configuration']['automations'][0]['name'] = 'PRIVATE_EDIT_WHILE_STORING'; });
        return true;
    };
    $check($request($second, 'store')['transfer']['status'] === 'stored', 'In-flight edit does not prevent read-only storage confirmation.');
    $after = $detail($second)['data']['document']['editorDraft'];
    $check($after['configuration']['automations'][0]['name'] === 'PRIVATE_EDIT_WHILE_STORING' && $after['baseRevision'] === 1, 'Store result never overwrites an edited document or falsely updates its base.');
    $onReply = null;

    $onReply = static function (array $packet, array $reply) use ($update, $first): bool
    {
        if (str_ends_with($packet['Topic'], '/status')) $update($first, static function (array &$data): void
        { $data['document']['editorDraft']['configuration']['automations'][0]['name'] = 'PRIVATE_BEFORE_VALIDATE'; });
        return true;
    };
    $sent = $sentCount();
    $check($request($first, 'validate')['transfer']['status'] === 'cancelled' && $sentCount() === $sent + 1, 'A changed document between phases cancels before wire validation.');
    $onReply = null;
    $drop = 'status';
    $check($request($first, 'validate')['transfer']['pending'], 'Pending job before restart.');
    $stores = $sentCount();
    $connections[array_keys($connections)[0]]->ResetTransient();
    $manager->ResetTransient();
    $check($status($first)['transfer']['status'] === 'interrupted' && $sentCount() === $stores, 'Lost transient workflow is marked interrupted without resending.');
    $drop = '';
    $firstConnection = $connections[array_keys($connections)[0]];
    $firstConnection->CheckHealth(false);
    $drop = 'status';
    $check($request($first, 'validate')['transfer']['pending'], 'Prepare pending request before gateway loss.');
    IPS_DisconnectInstance($detail($first)['binding']['connectionInstance']);
    $sent = $sentCount();
    $check($status($first)['transfer']['status'] === 'interrupted' && $sentCount() === $sent, 'Changed gateway ends pending display without calling a wrong target.');
    $check(!$status($first)['transfer']['canValidate'], 'Changed gateway cannot validate with an obsolete binding.');
    $drop = '';
    $allDebug = implode(' ', $manager->debug);
    foreach ($connections as $connection) $allDebug .= implode(' ', $connection->debug);
    $check(!str_contains($allDebug, 'PRIVATE_'), 'All error and storage paths redact private rule names and form inputs.');
}

echo "Workspace transfer: migrated owners, 1/2/8 systems, validation, confirmed storage, editing bases and safe rollback passed.\n";
