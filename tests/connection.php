<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $value, string $message): void
{
    if (!$value) throw new RuntimeException($message);
};
$root = dirname(__DIR__);
IPS\Kernel::reset();
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ([$root . '/Z2MConnection', __DIR__ . '/fixtures/MQTTClient', __DIR__ . '/fixtures/MQTTServer'] as $folder) IPS\ModuleLoader::loadSingleModule($folder, $library['id']);

class ClockedConnection extends Z2MConnection
{
    public int $clock = 1000;
    public array $debugMessages = [];
    public array $updates = [];
    public function catalogBytes(): string
    {
        return $this->ReadAttributeString('ConnectionCatalog');
    }
    public function recording(): array
    {
        return json_decode($this->GetBuffer('RuntimeDebug') ?: '[]', true, 32, JSON_THROW_ON_ERROR);
    }
    public function UpdateFormField(string $Field, string $Parameter, mixed $Value): bool
    {
        $this->updates[$Field][$Parameter] = $Value;
        return true;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MConnection/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debugMessages[] = [$Message, $Data];
        return true;
    }
}

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\Z2MProtocol;

$metadata = json_decode(file_get_contents($root . '/Z2MConnection/module.json'), true, 512, JSON_THROW_ON_ERROR);
$check($metadata['id'] === SystemContract::CONNECTION_MODULE && $metadata['prefix'] === SystemContract::CONNECTION_PREFIX, 'Reserved identity and prefix');
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[$metadata['id']]['Class'] = ClockedConnection::class;
$registry->setValue(null, $modules);
$parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
IPS_ApplyChanges($parentID);
$parent = IPS\InstanceManager::getInstanceInterface($parentID);
$instances = $connections = $systemIDs = [];
for ($i = 1; $i <= 8; $i++) {
    $id = IPS_CreateInstance($metadata['id']);
    $connection = IPS\InstanceManager::getInstanceInterface($id);
    $check(!IPS_GetProperty($id, 'Active') && SystemContract::SystemID(IPS_GetProperty($id, 'SystemID')), 'Disabled default with stable identity');
    $systemIDs[] = IPS_GetProperty($id, 'SystemID');
    $connection->ApplyChanges();
    $check($connection->CheckHealth() === '' && $parent->sent === [], 'Disabled modules create no network traffic');
    IPS_ConnectInstance($id, $parentID);
    IPS_SetProperty($id, 'BaseTopic', 'synthetic/system' . $i);
    IPS_SetProperty($id, 'Active', true);
    IPS_ApplyChanges($id);
    $check($connection->GetConfigurationForParent() === '{}', 'Shared parent configuration stays untouched');
    $instances[$i] = $id;
    $connections[$i] = $connection;
}
$check(count(array_unique($systemIDs)) === 8 && $parent->sent === [], 'Eight unique identities; ApplyChanges sends nothing');
$state = static fn (int $i): array => json_decode($connections[$i]->GetTransportState(), true, 32, JSON_THROW_ON_ERROR);
$catalog = static fn (int $i): array => json_decode($connections[$i]->GetDeviceCatalog(), true, 32, JSON_THROW_ON_ERROR);
$one = $connections[1];
$transactions = [];
foreach ($connections as $i => $connection) $transactions[$i] = $connection->CheckConnection();
$check(count(array_unique($transactions)) === 8 && count($parent->sent) === 8, 'Eight independent concurrent health requests');
$health = static fn (string $tx): array => ['transaction' => $tx, 'status' => 'ok', 'data' => ['healthy' => true]];
$parent->publish('synthetic/system1/bridge/response/health_check', $health($transactions[2]));
$parent->publish('synthetic/system1/bridge/response/health_check', $health($transactions[1]), true);
$check($state(1)['health'] === 'pending', 'Foreign and retained replies cannot confirm health');
foreach ($transactions as $i => $tx) $parent->publish('synthetic/system' . $i . '/bridge/response/health_check', $health($tx));
foreach ($connections as $i => $connection) $check($state($i)['health'] === 'healthy' && $state($i)['bridgeOnline'], 'Exact response confirms its own system');
$check($one->updates['HealthResultPopup']['visible'] === true, 'Health result popup confirmed');
$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => ExtensionContract::VERSION,
    'extension_build'        => ExtensionContract::BundledBuild(), 'session' => '11111111-1111-4111-8111-111111111111', 'state' => 'ready',
    'stored_revision'        => 0, 'checksum' => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false,
    'runtime_version'        => 1, 'active_revision' => null, 'active_checksum' => null, 'enabled_count' => 0, 'runtime_error' => '', 'catalog_version' => 1, 'debug_version' => 1];
$one->RequestExtensionStatus();
$tx = end($parent->sent)['Payload']['transaction'];
$check($one->RequestDeviceCatalog() === '', 'Pending request cannot be replaced');
$reply = static function (string $topic, array $data, string $tx, bool $retain = false) use ($parent): void
{
    $parent->publish($topic, ['protocol_version' => 1, 'transaction' => $tx, 'status' => 'ok', 'data' => (object) $data], $retain);
};
$reply('synthetic/system1/symcon/automations/response/status', $ready, $tx, true);
$check(!$state(1)['extensionConfirmed'], 'Retained status cannot confirm extension');
$reply('synthetic/system1/symcon/automations/response/status', $ready, $tx);
$check($state(1)['extensionConfirmed'] && !$state(2)['extensionConfirmed'], 'Correlated status isolated per system');
$check(str_contains($one->updates['ExtensionUpdateInfo']['caption'], 'Up to date'), 'Build comparison visible');
$parent->publish('synthetic/system1/bridge/info', ['version' => 'synthetic', 'config' => ['password' => 'synthetic-secret', 'advanced' => ['enable_external_js' => true]]]);
$check(!str_contains($one->GetConnectionInfo(), 'synthetic-secret') && !str_contains($one->GetConnectionInfo(), $ready['session']), 'Public status excludes raw config and sessions');
$device = ['id'  => '0x1111111111111111', 'name' => 'Synthetic switch', 'triggers' => [['property' => 'state_l2', 'value' => 'ON']], 'actions' => [['property' => 'state_l2', 'value' => 'OFF']],
    'properties' => [['property' => 'state_l2', 'type' => 'binary', 'access' => 3, 'label' => 'Channel 2', 'endpoint' => 'l2', 'values' => ['ON', 'OFF']],
        ['property' => 'temperature', 'type' => 'numeric', 'access' => 1, 'label' => 'Temperature', 'endpoint' => '', 'values' => []]]];
$data = $ready + ['devices' => [$device], 'groups' => [['id' => 1, 'name' => 'Synthetic group']],
    'scenes'                => [['target' => ['type' => 'group', 'id' => 1], 'id' => 2, 'name' => 'Synthetic scene', 'control_property' => 'state']], 'yaml' => 'must-not-be-persisted'];
$bad = false;
$parent->onPublish = static function (array $p) use ($parent, &$ready, &$data, &$bad): void
{
    $response = str_ends_with($p['Topic'], '/request/catalog') ? ($bad ? $data + ['invalid' => true] : $data) : $ready;
    if ($bad && str_ends_with($p['Topic'], '/request/catalog')) $response['devices'] = [['id' => 'bad']];
    $parent->publish(str_replace('/request/', '/response/', $p['Topic']), ['protocol_version' => 1, 'transaction' => $p['Payload']['transaction'], 'status' => 'ok', 'data' => $response]);
};
foreach ($connections as $connection) $check($connection->RequestDeviceCatalog() !== '', 'Synchronous native replies do not deadlock');
foreach ($connections as $i => $connection) {
    $loaded = $catalog($i);
    $check($loaded['available'] && $loaded['systemId'] === $systemIDs[$i - 1] && count($loaded['devices']) === 1 && $loaded['scenes'][0]['id'] === 2, 'Same device/group identities stay namespaced by system');
    $check($loaded['devices'][$device['id']]['properties']['state_l2']['access'] === 3 && $loaded['devices'][$device['id']]['properties']['temperature']['access'] === 1, 'Channel and read/write access retained');
    $check(!str_contains($connection->catalogBytes(), 'must-not-be-persisted'), 'Catalog refresh never persists YAML');
}
$originalCatalog = $one->catalogBytes();
$bad = true;
$one->RequestDeviceCatalog();
$check($state(1)['lastError'] === 'invalid_response' && $one->catalogBytes() === $originalCatalog && !$catalog(1)['available'], 'Invalid catalog preserves bytes but not current confirmation');
$bad = false;
$one->RequestDeviceCatalog();
$check($catalog(1)['available'], 'Fresh catalog restores availability');
$sent = count($parent->sent);
$form = json_decode($one->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$find = static function (array $node, string $name) use (&$find): ?array
{
    if (($node['name'] ?? '') === $name) return $node;
    foreach ($node as $value) if (is_array($value) && ($field = $find($value, $name)) !== null) return $field;
    return null;
};
$installButton = $find($form, 'ConnectionInstallButton');
$ownerNotice = $find($form, 'ConnectionInstallationOwner');
$check($installButton !== null && $installButton['visible'] && isset($installButton['confirm']), 'Standalone connections keep explicit installation with confirmation');
$check($ownerNotice !== null && !$ownerNotice['visible'], 'Standalone connections do not show a misleading owner notice');
$one->GetDeviceCatalog();
$one->GetConnectionInfo();
$check(count($parent->sent) === $sent, 'Read methods and form opening send no MQTT');
$parent->onPublish = static function (array $p) use ($parent, &$ready): void
{
    if (str_ends_with($p['Topic'], '/extension/save')) $ready['session'] = '22222222-2222-4222-8222-222222222222';
    $parent->publish(str_replace('/request/', '/response/', $p['Topic']), ['protocol_version' => 1, 'transaction' => $p['Payload']['transaction'], 'status' => 'ok', 'data' => $ready]);
};
$check($one->InstallExtension(true) !== '' && $state(1)['operation']['status'] === 'installed' && $one->updates['InstallResultPopup']['visible'], 'Official installation requires fresh receiver confirmation');
$frame = $parent->sent[count($parent->sent) - 2];
$check(str_ends_with($frame['Topic'], '/bridge/request/extension/save') && $frame['Payload']['name'] === ExtensionContract::NAME && str_contains($frame['Payload']['code'], 'EXTENSION_BUILD'), 'Only bundled artifact uploaded');
$check(!$catalog(1)['available'] && $one->catalogBytes() === $originalCatalog, 'New session invalidates catalog freshness, preserving bytes');
$check($one->InstallExtension(true) !== '' && $state(1)['operation']['status'] === 'error', 'Unchanged receiver session cannot falsely confirm an update');
$one->RequestExtensionStatus();
$parent->publish('synthetic/system1/bridge/info', ['version' => 'synthetic', 'config' => ['advanced' => ['enable_external_js' => false]]]);
$sent = count($parent->sent);
$check($one->InstallExtension(true) === '' && count($parent->sent) === $sent && $state(1)['lastError'] === 'external_js_disabled', 'Known disabled external JavaScript blocks upload');
$parent->publish('synthetic/system1/bridge/info', ['version' => 'synthetic', 'config' => ['advanced' => ['enable_external_js' => true]]]);
$parent->onPublish = static function (array $p) use ($parent): void
{
    $v = $p['Payload'];
    $parent->publish(str_replace('/request/', '/response/', $p['Topic']), ['protocol_version' => 1, 'transaction' => $v['transaction'], 'status' => 'ok',
        'data'                                                                                => ['debug_version' => 1, 'client' => $v['client'], 'seconds' => $v['seconds'], 'active' => $v['seconds'] > 0]]);
};
$check($one->StartRuntimeDebug(30) && $one->recording()['active'], 'Own runtime recording confirmed');
$until = $one->recording()['until'];
$one->ApplyChanges();
$one->GetConfigurationForm();
$check($one->recording()['until'] === $until, 'Unchanged settings preserve recording end time');
$check($one->StopRuntimeDebug() && $one->recording() === [], 'Own recording stopped');
$one->StartRuntimeDebug(10);
$one->clock += 10;
$one->ExpireRuntimeDebug();
$check($one->recording() === [], 'Automatic debug expiry');
$parent->onPublish = null;
$one->CheckHealth();
$one->RequestExtensionStatus();
$late = end($parent->sent)['Payload']['transaction'];
$connections[2]->RequestExtensionStatus();
$one->clock += 10;
$one->ExpireRequest();
$check($state(1)['health'] === 'timeout' && $state(1)['operation']['status'] === 'timeout' && $state(2)['operation']['status'] === 'pending', 'Independent request deadlines');
$reply('synthetic/system1/symcon/automations/response/status', $ready, $late);
$check(!$state(1)['extensionConfirmed'], 'Late replies cannot revive an expired request');
$otherID = IPS_CreateInstance(Z2MProtocol::MQTT_SERVER);
IPS_ApplyChanges($otherID);
$other = IPS\InstanceManager::getInstanceInterface($otherID);
IPS_DisconnectInstance($instances[2]);
IPS_ConnectInstance($instances[2], $otherID);
IPS_SetProperty($instances[2], 'BaseTopic', 'synthetic/system1');
IPS_ApplyChanges($instances[2]);
$check(!$state(2)['bridgeOnline'] && !$catalog(2)['available'], 'Gateway change invalidates confirmation');
$tx = $connections[2]->CheckHealth();
$parent->publish('synthetic/system1/bridge/response/health_check', $health($tx));
$check($state(2)['health'] === 'pending', 'Identical topic on another broker cannot confirm health');
$other->publish('synthetic/system1/bridge/response/health_check', $health($tx));
$check($state(2)['health'] === 'healthy', 'Correct native parent accepted');
$connections[2]->RequestExtensionStatus();
$frame = end($other->sent);
IPS_SetProperty($instances[2], 'Generation', 2);
IPS_ApplyChanges($instances[2]);
$other->publish('synthetic/system1/symcon/automations/response/status', ['protocol_version' => 1, 'transaction' => $frame['Payload']['transaction'], 'status' => 'ok', 'data' => $ready]);
$check($state(2)['operation']['status'] === 'cancelled' && !$state(2)['extensionConfirmed'], 'Generation change cancels pending responses');
$parent->changeStatus(201);
$check(!$state(1)['mqttConnected'] && !$catalog(1)['available'], 'Disconnect invalidates catalog');
$parent->changeStatus(102);
$check(!$state(1)['bridgeOnline'] && !$state(1)['extensionConfirmed'], 'Reconnect needs fresh confirmation');
// Eight separate native parents also remain isolated. This does not claim a real broker or mesh load test.
for ($i = 3; $i <= 8; $i++) {
    $gateway = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
    IPS_ApplyChanges($gateway);
    IPS_DisconnectInstance($instances[$i]);
    IPS_ConnectInstance($instances[$i], $gateway);
    IPS_SetProperty($instances[$i], 'BaseTopic', 'synthetic/system1');
    IPS_ApplyChanges($instances[$i]);
    $tx = $connections[$i]->CheckHealth();
    $parent->publish('synthetic/system1/bridge/response/health_check', $health($tx));
    $check($state($i)['health'] === 'pending', 'Different broker cannot deliver another systems response');
    IPS\InstanceManager::getInstanceInterface($gateway)->publish('synthetic/system1/bridge/response/health_check', $health($tx));
    $check($state($i)['health'] === 'healthy', 'Separate native parent is independently usable');
}
IPS_SetProperty($instances[8], 'SystemID', 'invalid');
IPS_ApplyChanges($instances[8]);
$check(IPS_GetInstance($instances[8])['InstanceStatus'] === 200 && $connections[8]->CheckHealth() === '', 'Invalid system identity blocks transport');
foreach (['StoreYaml', 'ValidateYaml', 'ActivateAutomations', 'StopAutomations', 'CreateSimpleAutomation'] as $method) $check(!method_exists($one, $method), 'Connection foundation has no editing/execution APIs');
$debug = json_encode($one->debugMessages, JSON_THROW_ON_ERROR);
$check(str_contains($debug, 'Verbindungsauftrag abgeschlossen') && !str_contains($debug, 'synthetic-secret') && !str_contains($debug, 'export const') && !str_contains($debug, $ready['session']), 'German debug omits secrets, source and sessions');
echo "Connection: eight systems, native isolation, catalog, installation, lifecycle and debug passed.\n";
