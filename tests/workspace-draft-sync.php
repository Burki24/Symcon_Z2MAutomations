<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

$root = dirname(__DIR__);
IPS\Kernel::reset();
$library = json_decode(file_get_contents($root . '/library.json'), true, 32, JSON_THROW_ON_ERROR);
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);
$check = static function (bool $ok, string $message): void
{ if (!$ok) throw new RuntimeException($message); };
$decode = static fn (string $raw): array => json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
$check(method_exists(Z2MAutomations::class, 'StartWorkspaceDraftSync'), 'The automation workspace needs an explicit fresh Z2M-to-draft update.');

class DraftSyncManagerProbe extends Z2MAutomations
{
    public static string $storage;
    public int $clock = 1800000000;
    public array $debug = [];
    public string $body = '';
    public function Raw(): string
    {
        return $this->ReadAttributeString('SystemWorkspace');
    }
    public function Hook(string $route, string $token, array $headers, string $method = 'POST'): array
    {
        $_SERVER = ['REQUEST_URI' => '/hook/Z2MAutomationsDesigner' . $this->InstanceID . '/' . $route,
            'REQUEST_METHOD'      => $method, 'HTTP_X_Z2MA_READ_TOKEN' => $token, 'HTTP_HOST' => '127.0.0.1:3777',
            'HTTP_ORIGIN'         => 'http://127.0.0.1:3777', 'CONTENT_TYPE' => 'application/json'] + $headers;
        ob_start();
        try {
            $this->ProcessHookData();
            return [http_response_code(), ob_get_contents()];
        }
        finally {
            ob_end_clean();
        }
    }
    protected function DesignerRequestBody(): string
    {
        return $this->body;
    }
    protected function RegisterHook(string $HookPath): bool
    {
        return true;
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function MigrationStorageDirectory(): string
    {
        return self::$storage;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Data;
        return true;
    }
}
class DraftSyncConnectionProbe extends Z2MConnection
{
    public array $debug = [];
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Data;
        return true;
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = DraftSyncManagerProbe::class;
$modules[SystemContract::CONNECTION_MODULE]['Class'] = DraftSyncConnectionProbe::class;
$registry->setValue(null, $modules);
DraftSyncManagerProbe::$storage = sys_get_temp_dir() . '/z2ma-draft-sync-' . $op();
$id = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
$manager = IPS\InstanceManager::getInstanceInterface($id);
$parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
IPS_ApplyChanges($parentID);
$parent = IPS\InstanceManager::getInstanceInterface($parentID);
$documents = $metadata = $connections = [];
$rule = ['id'  => 'lamp_rule', 'name' => 'PRIVATE_RULE', 'enabled' => true,
    'triggers' => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
    'actions'  => [['type' => 'set', 'target' => ['type' => 'device', 'id' => 'lamp'], 'values' => ['state' => 'ON']]]];
$remote = static function (string $topic, int $revision, array $rules) use (&$documents, &$metadata): void
{
    $configuration = ['schema_version' => 1, 'revision' => $revision, 'automations' => $rules, 'mesh_peers' => ['synthetic/peer']];
    $yaml = "# PRIVATE_YAML\n" . json_encode($configuration, WorkspaceState::JSON_FLAGS | JSON_PRETTY_PRINT) . "\n";
    $documents[$topic] = ['configuration' => $configuration, 'yaml' => $yaml];
    $metadata[$topic] = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => ExtensionContract::BundledVersion(),
        'session'                           => '11111111-1111-4111-8111-111111111111', 'state' => 'ready', 'stored_revision' => $revision,
        'checksum'                          => hash('sha256', $yaml), 'automation_count' => count($rules), 'storage' => 'ok', 'executing' => true,
        'runtime_version'                   => 1, 'active_revision' => $revision, 'active_checksum' => hash('sha256', $yaml),
        'enabled_count'                     => count($rules), 'runtime_error' => '', 'catalog_version' => 1];
};
$packets = [];
$held = [];
$hold = false;
$change = null;
$parent->onPublish = static function (array $packet) use ($parent, &$packets, &$held, &$hold, &$change, &$documents, &$metadata, $check): void
{
    $packets[] = $packet;
    $command = substr($packet['Topic'], strrpos($packet['Topic'], '/') + 1);
    $check(in_array($command, ['status', 'editor'], true), 'Draft synchronization may only read; never validate, store, stop, activate or send device commands.');
    if ($hold) {
        $held[] = $packet;
        return;
    }
    $topic = strstr($packet['Topic'], '/symcon/', true);
    if ($change !== null) $change($topic, $command);
    $data = $metadata[$topic];
    if ($command === 'editor') $data += ['editor_version' => 1, 'devices' => [], 'groups' => [], 'scenes' => []] + $documents[$topic];
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
        'transaction'                                                                              => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data]);
};
for ($i = 0; $i < 2; $i++) {
    $connection = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_ConnectInstance($connection, $parentID);
    IPS_SetProperty($connection, 'BaseTopic', 'synthetic/draft_' . $i);
    IPS_SetProperty($connection, 'Active', true);
    IPS_ApplyChanges($connection);
    $connections[] = $connection;
    $remote('synthetic/draft_' . $i, 7, [$rule]);
}
$overview = $decode($manager->GetWorkspaceOverview());
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview['fingerprint'], $op()))['ok'], 'Configure the isolated systems.');
$systems = array_column($decode($manager->GetWorkspaceOverview())['systems'], 'systemId');
$detail = static fn (string $system): array => $decode($manager->GetWorkspaceSystem($system));
foreach ($systems as $system) {
    $record = $detail($system);
    $operation = $op();
    $initial = $decode($manager->StartWorkspaceSetup($system, 1, $record['fingerprint'], $operation));
    $check(($initial['setup']['phase'] ?? '') === 'preview', 'Read the initial base: ' . json_encode($initial));
    $check($decode($manager->ConfirmWorkspaceSetup($operation))['setup']['phase'] === 'complete', 'Claim the connection through the existing explicit setup.');
}
$system = $systems[0];
$other = $detail($systems[1]);
$topic = 'synthetic/draft_0';
$sync = static function (?string $operation = null) use ($manager, $detail, $system, $op, $decode): array
{
    $record = $detail($system);
    $reply = $decode($manager->StartWorkspaceDraftSync($system, 1, $record['fingerprint'], $operation ?? $op()));
    for ($i = 0; $i < 4 && ($reply['draftSync']['pending'] ?? false); $i++) $reply = $decode($manager->GetWorkspaceDraftSyncStatus($system));
    return $reply;
};
$remote($topic, 74, [$rule, array_replace($rule, ['id' => 'off_rule', 'name' => 'PRIVATE_NEW_RULE'])]);
$operation = $op();
$before = $detail($system);
$read = $sync($operation);
$check($read['ok'] && $read['draftSync']['phase'] === 'updated', 'A newer remote base must update the clean draft directly: ' . json_encode($read));
$after = $detail($system);
$check($after['data']['document']['yaml'] === $documents[$topic]['yaml'] && $after['data']['document']['editorDraft']['baseRevision'] === 74, 'Retain exact YAML, rule IDs, enabled flags, grants and the new base.');
$check($detail($systems[1]) === $other, 'The other system is byte-for-byte unchanged.');
$count = count($packets);
$manager->StartWorkspaceDraftSync($system, 1, $before['fingerprint'], $operation);
$check(count($packets) === $count, 'Replaying the same request cannot restart MQTT or replace the draft again.');
$check($sync()['draftSync']['phase'] === 'current', 'A current draft is not rewritten.');
$update = static function (array $data) use ($manager, $detail, $system, $decode, $op, $check): void
{
    $check($decode($manager->UpdateWorkspaceSystem($system, 1, $detail($system)['fingerprint'], json_encode($data, WorkspaceState::JSON_FLAGS), $op(), false))['ok'], 'Change the local draft through its public API.');
};
$local = $detail($system)['data'];
$local['document']['editorDraft']['configuration']['automations'][0]['name'] = 'PRIVATE_LOCAL_EDIT';
$local['document']['editorDraft']['configuration']['revision'] = 900;
$local['document']['yaml'] = json_encode($local['document']['editorDraft']['configuration'], WorkspaceState::JSON_FLAGS) . "\n";
$local['document']['editorDraft']['draftChecksum'] = hash('sha256', $local['document']['yaml']);
$update($local);
$check($sync()['draftSync']['phase'] === 'current' && $detail($system)['data'] === $local, 'A different local revision counter is not evidence of an older draft; local edits survive an unchanged remote base.');
$remote($topic, 75, [$rule]);
$read = $sync()['draftSync'];
$check($read['phase'] === 'conflict' && $read['canReplace'] && $detail($system)['data'] === $local
    && $read['counts'] === ['added' => 0, 'changed' => 1, 'removed' => 1], 'Divergent persisted changes produce a review without silently replacing any rules.');
$record = $detail($system);
$confirmed = $decode($manager->ResolveWorkspaceDraftSync($system, 1, $record['fingerprint'], $read['operation'], 'replace'));
$check(($confirmed['draftSync']['phase'] ?? '') === 'updated' && $detail($system)['data']['document']['yaml'] === $documents[$topic]['yaml'], 'Explicit replacement re-verifies the remote version and imports only the selected system.');
$count = count($packets);
$record = $detail($system);
$restored = $decode($manager->ResolveWorkspaceDraftSync($system, 1, $record['fingerprint'], $read['operation'], 'restore'));
$check($restored['draftSync']['phase'] === 'restored' && $detail($system)['data'] === $local && count($packets) === $count, 'The private backup restores exact local YAML and work without any MQTT request.');
$read = $sync()['draftSync'];
$remote($topic, 76, [$rule]);
$record = $detail($system);
$changed = $decode($manager->ResolveWorkspaceDraftSync($system, 1, $record['fingerprint'], $read['operation'], 'replace'));
$check(!$changed['ok'] && $detail($system)['data'] === $local, 'A Z2M change after conflict review invalidates confirmation instead of importing the outdated preview.');
$local['work']['rule'] = ['dirty' => true, 'input' => ['name' => 'PRIVATE_OPEN_INPUT']];
$update($local);
$count = count($packets);
$check(!$sync()['ok'] && count($packets) === $count && $detail($system)['data'] === $local, 'Open native form input blocks the update before any read is sent.');
$local['work']['rule'] = null;
$update($local);
$remote($topic, 73, [$rule]);
$check($sync()['draftSync']['phase'] === 'older' && $detail($system)['data'] === $local, 'An older remote base cannot roll the local draft back automatically.');
$remote($topic, 76, [$rule]);
$hold = true;
$record = $detail($system);
$operation = $op();
$pending = $decode($manager->StartWorkspaceDraftSync($system, 1, $record['fingerprint'], $operation));
$check($pending['draftSync']['pending'], 'Delayed replies leave an explicit bounded read job.');
$local['document']['editorDraft']['configuration']['automations'][0]['name'] = 'PRIVATE_CONCURRENT_EDIT';
$local['document']['yaml'] = json_encode($local['document']['editorDraft']['configuration'], WorkspaceState::JSON_FLAGS);
$local['document']['editorDraft']['draftChecksum'] = hash('sha256', $local['document']['yaml']);
$update($local);
$count = count($packets);
$pending = $decode($manager->GetWorkspaceDraftSyncStatus($system));
$check($pending['draftSync']['phase'] === 'error' && $detail($system)['data'] === $local && count($packets) === $count, 'A concurrent edit invalidates the job without retrying or importing stale data.');
$hold = false;
// Let the cancelled read's existing wire reply settle; no replacement request is issued.
foreach ($held as $packet) {
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
        'transaction'                                                                              => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $metadata[$topic]]);
}
$held = [];
$hold = true;
$pending = $sync();
$check($pending['draftSync']['pending'], 'The timeout case starts a real delayed read.');
$manager->clock += 601;
$count = count($packets);
$pending = $decode($manager->GetWorkspaceDraftSyncStatus($system));
$check($pending['draftSync']['phase'] === 'error' && $pending['draftSync']['error'] === 'draft_sync_timeout'
    && count($packets) === $count && $detail($system)['data'] === $local, 'Timeouts preserve the draft and never repeat a read.');
$hold = false;
foreach ($held as $packet) $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
    'transaction'                                                                                                         => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $metadata[$topic]]);
$token = explode('#', $manager->OpenAutomationDesigner())[1];
$record = $detail($system);
$headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $system, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $record['fingerprint']];
$manager->body = json_encode(['requestId' => '11111111-1111-4111-8111-111111111111', 'action' => 'refresh', 'operation' => '']);
[$http] = $manager->Hook('draft-sync', str_repeat('f', 64), $headers);
$check($http === 403 && count($packets) === $count, 'A foreign token cannot start a draft update.');
[$http] = $manager->Hook('draft-sync', $token, $headers, 'GET');
$check($http === 405, 'Read-only GET cannot initiate the explicit refresh action.');
[$http, $body] = $manager->Hook('draft-sync', $token, $headers);
$check($http === 200 && ($decode($body)['draftSync']['phase'] ?? '') === 'conflict', 'The real authenticated POST reads fresh Z2M and returns a protected conflict preview.');
[$http, $body] = $manager->Hook('data', $token, $headers, 'GET');
$model = $decode($body);
$check($http === 200 && $model['draftSyncAllowed'] && $model['draftSync']['systemId'] === $system, 'The real browser adapter exposes only the selected system view and its edit capability.');
$check($detail($systems[1]) === $other, 'All error and restore paths leave the other system untouched.');
$check(!str_contains(implode(' ', $manager->debug), 'PRIVATE_'), 'Debug output must not contain private rule names, YAML or open input.');
foreach ($connections as $connection) $check(!str_contains(implode(' ', IPS\InstanceManager::getInstanceInterface($connection)->debug), 'PRIVATE_'), 'The connection must never log the returned configuration.');
$directory = realpath(DraftSyncManagerProbe::$storage);
$check($directory !== false && str_starts_with($directory, realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'z2ma-draft-sync-'), 'Resolve the isolated test archive directory before cleanup.');
foreach (glob($directory . '/*.json') as $file) unlink($file);
rmdir($directory);
echo "Fresh draft sync: exact YAML, base comparison, local conflicts, backup/restore, concurrent edits, timeout, scoped HTTP and read-only MQTT passed.\n";
