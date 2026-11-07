<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

$root = dirname(__DIR__);
IPS\Kernel::reset();
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);
$check = static function (bool $ok, string $message): void
{ if (!$ok) throw new RuntimeException($message); };
class CatalogManagerProbe extends Z2MAutomations
{
    public int $clock = 1800000000;
    public array $debug = [];
    public string $requestBody = '';
    public function Raw(): string
    {
        return $this->ReadAttributeString('SystemWorkspace');
    }
    public function Jobs(): string
    {
        return $this->GetBuffer('WorkspaceCatalogJobs');
    }
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
    protected function DesignerRequestBody(): string
    {
        return $this->requestBody;
    }
    protected function RegisterHook(string $HookPath): bool
    {
        return true;
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Data;
        return true;
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = CatalogManagerProbe::class;
$registry->setValue(null, $modules);
$id = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
IPS_ApplyChanges($id);
$manager = IPS\InstanceManager::getInstanceInterface($id);
$decode = static fn (string $raw): array => json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
$detail = static fn (string $sys): array => $decode($manager->GetWorkspaceSystem($sys));
$overview = static fn (): array => $decode($manager->GetWorkspaceOverview());
$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => ExtensionContract::VERSION, 'session' => '11111111-1111-4111-8111-111111111111',
    'state'                  => 'ready', 'stored_revision' => 3, 'checksum' => str_repeat('a', 64), 'automation_count' => 1, 'storage' => 'ok', 'executing' => true,
    'runtime_version'        => 1, 'active_revision' => 3, 'active_checksum' => str_repeat('a', 64), 'enabled_count' => 1, 'runtime_error' => '', 'catalog_version' => 1, 'debug_version' => 1];
$catalogData = $ready + ['devices' => [['id' => '0x0000000000000001', 'name' => 'PRIVATE_DEVICE',
    'triggers'                               => [['property' => 'action', 'value' => 'single']], 'actions' => [], 'properties' => [['property' => 'action', 'type' => 'enum', 'access' => 1, 'label' => 'Synthetic event', 'endpoint' => '', 'values' => ['single']]]]], 'groups' => [], 'scenes' => []];
$mode = 'auto';
$held = [];
$parents = [];
$parentIDs = [];
$reply = static function (SyntheticMQTTClient $parent, array $packet, ?array $override = null) use ($ready, &$catalogData): void
{
    $data = $override ?? (str_ends_with($packet['Topic'], '/health_check') ? ['healthy' => true] : (str_ends_with($packet['Topic'], '/catalog') ? $catalogData : $ready));
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data]);
};
for ($i = 0; $i < 2; $i++) {
    $parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
    IPS_ApplyChanges($parentID);
    $p = IPS\InstanceManager::getInstanceInterface($parentID);
    $p->onPublish = static function (array $packet) use ($p, $reply, &$mode, &$held, $check): void
    {
        $check(str_ends_with($packet['Topic'], '/health_check') || str_ends_with($packet['Topic'], '/status') || str_ends_with($packet['Topic'], '/catalog'), 'Refresh never sends a write, activation or device command');
        if ($mode === 'hold' || ($mode === 'hold_catalog' && str_ends_with($packet['Topic'], '/catalog'))) {
            $held[] = [$p, $packet];
            return;
        }
        $reply($p, $packet, $mode === 'invalid' && str_ends_with($packet['Topic'], '/catalog') ? ['invalid' => true] : null);
    };
    $parents[] = $p;
    $parentIDs[] = $parentID;
}
$connections = $systems = [];
for ($i = 0; $i < 8; $i++) {
    $ci = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_ConnectInstance($ci, $parentIDs[$i % 2]);
    IPS_SetProperty($ci, 'Active', true);
    IPS_SetProperty($ci, 'BaseTopic', 'synthetic/catalog_' . $i);
    IPS_ApplyChanges($ci);
    $connections[] = $ci;
    $systems[] = IPS_GetProperty($ci, 'SystemID');
}
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview()['fingerprint'], $op()))['ok'], 'Configure eight systems over two native MQTT parents');
foreach ($systems as $i => $sys) {
    $conf = ['schema_version' => 1, 'revision' => 1, 'automations' => [], 'mesh_peers' => []];
    $yaml = '# PRIVATE_EXACT_YAML äöü' . "\n" . json_encode($conf) . "\n";
    $data = WorkspaceState::Data();
    $data['document'] = ['yaml' => $yaml, 'editorDraft' => ['configuration' => $conf, 'baseRevision' => 0, 'baseChecksum' => null, 'draftChecksum' => hash('sha256', $yaml)]];
    $data['work']['rule'] = ['dirty' => true, 'input' => ['PRIVATE_PENDING_INPUT' => $i]];
    $check($decode($manager->UpdateWorkspaceSystem($sys, 1, $detail($sys)['fingerprint'], json_encode($data), $op()))['ok'], 'Retain exact draft and open native inputs');
}
$baseline = [];
foreach ($systems as $sys) $baseline[$sys] = $detail($sys)['data'];
$sent = static fn (): int => array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
$beforePackets = $sent();
$beforeRaw = $manager->Raw();
foreach ($systems as $sys) {
    $manager->GetWorkspaceDeviceCatalogStatus($sys);
    $manager->GetDesignerData();
}
$check($sent() === $beforePackets && $manager->Raw() === $beforeRaw, 'Opening and polling the designer do not send MQTT or replace any data');
$operations = [];
foreach ($systems as $sys) {
    $operations[$sys] = $op();
    $d = $detail($sys);
    $r = $decode($manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $operations[$sys]));
    $check($r['ok'] && $r['catalogRefresh']['pending'] && $r['catalogRefresh']['phase'] === 'health', 'Explicit per-system refresh starts with a visible connection check');
}
$check($sent() === $beforePackets + 8, 'Exactly one health request per selected system, including shared MQTT parents');
foreach ($systems as $sys) {
    $d = $detail($sys);
    $manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $operations[$sys]);
}
$check($sent() === $beforePackets + 8, 'Repeated POST identity never sends a second refresh');
$manager->RefreshWorkspaceDeviceCatalogs();
$check($sent() === $beforePackets + 24, 'Each system requests fresh extension status and then only its device catalog');
foreach ($systems as $sys) $check($decode($manager->GetWorkspaceDeviceCatalogStatus($sys))['catalogRefresh']['phase'] === 'catalog', 'Read-only polling displays catalog progress');
$manager->RefreshWorkspaceDeviceCatalogs();
foreach ($systems as $sys) {
    $d = $detail($sys);
    $v = $decode($manager->GetWorkspaceDeviceCatalogStatus($sys))['catalogRefresh'];
    $check($v['phase'] === 'loaded' && !$v['pending'] && $v['counts']['devices'] === 1 && $v['loadedAt'] !== null, 'Fresh response is imported and completion includes counts and timestamp: ' . json_encode($v));
    $check($d['data'] === $baseline[$sys], 'Exact YAML, peers, selections and dirty native inputs stay byte-identical');
}
foreach ($connections as $ci) {
    $state = $decode(Z2MC_GetConnectionInfo($ci))['state']['extension'];
    $check($state['executing'] && $state['storedRevision'] === 3 && $state['activeRevision'] === 3 && $state['enabledCount'] === 1, 'Device refresh leaves the independently active receiver revision and rule count unchanged');
}
$catalogSystem = $systems[6];
$catalogConnection = IPS\InstanceManager::getInstanceInterface($connections[6]);
$catalogBefore = $manager->Raw();
$catalogPackets = $sent();
$catalogConnection->MessageSink(time(), 0, IPS_KERNELSTARTED, []);
$catalogToken = explode('#', $manager->OpenAutomationDesigner())[1];
[$catalogHttp, $catalogBody] = $manager->Hook('data', $catalogToken, ['HTTP_X_Z2MA_SYSTEM_ID' => $catalogSystem]);
$catalogView = $decode($catalogBody);
$catalogStatus = $decode($manager->GetWorkspaceDeviceCatalogStatus($catalogSystem))['catalogRefresh'];
$check($catalogHttp === 200 && !$catalogView['catalogAvailable'] && $catalogView['catalogLoadedAt'] === null, 'Editor cannot use an unconfirmed connection catalog after restart');
$check($catalogStatus['loadedAt'] === null && $catalogStatus['storedAt'] !== null && $catalogStatus['counts']['devices'] === 0
    && str_contains($catalogStatus['message'], 'nicht bestätigt'), 'Stored history is distinguished from confirmed current device data in the status panel');
$check($catalogView['catalogRefresh'] === $catalogStatus && $manager->Raw() === $catalogBefore && $sent() === $catalogPackets, 'Reading both representations preserves every stored catalog and draft without MQTT');
$manager->RequestWorkspaceDeviceCatalog($catalogSystem, 1, $detail($catalogSystem)['fingerprint'], $op());
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceDeviceCatalogs();
[$catalogHttp, $catalogBody] = $manager->Hook('data', $catalogToken, ['HTTP_X_Z2MA_SYSTEM_ID' => $catalogSystem]);
$catalogView = $decode($catalogBody);
$check($catalogHttp === 200 && $catalogView['catalogAvailable'] && $catalogView['catalogLoadedAt'] === $catalogView['catalogRefresh']['loadedAt']
    && $catalogView['catalogRefresh']['counts']['devices'] === 1 && $detail($catalogSystem)['data'] === $baseline[$catalogSystem], 'Explicit refresh restores consistent usable data and keeps pending inputs and exact YAML');
$catalogData['devices'][0]['name'] = 'PRIVATE_DEVICE_UPDATED';
$catalogConnection->RequestDeviceCatalog();
$catalogBefore = $manager->Raw();
$catalogPackets = $sent();
[$catalogHttp, $catalogBody] = $manager->Hook('data', $catalogToken, ['HTTP_X_Z2MA_SYSTEM_ID' => $catalogSystem]);
$catalogView = $decode($catalogBody);
$check($catalogHttp === 200 && !$catalogView['catalogAvailable'] && $catalogView['catalogRefresh']['loadedAt'] === null
    && $catalogView['catalogRefresh']['storedAt'] !== null && $manager->Raw() === $catalogBefore && $sent() === $catalogPackets, 'A newly confirmed but different connection catalog cannot validate an old central catalog');
$manager->RefreshWorkspaceCatalog($catalogSystem, 1, $detail($catalogSystem)['fingerprint'], $op());
[$catalogHttp, $catalogBody] = $manager->Hook('data', $catalogToken, ['HTTP_X_Z2MA_SYSTEM_ID' => $catalogSystem]);
$catalogView = $decode($catalogBody);
$check($catalogHttp === 200 && $catalogView['catalogAvailable'] && $catalogView['catalogRefresh']['loadedAt'] === $catalogView['catalogLoadedAt'], 'Explicitly importing the current connection catalog restores matching editor and panel state');
$packets = $sent();
$manager->RefreshWorkspaceDeviceCatalogs();
$check($sent() === $packets, 'Completed timers never repeat a read request');
$sys = $systems[0];
$d = $detail($sys);
$check($decode($manager->RequestWorkspaceDeviceCatalog($sys, 1, str_repeat('a', 64), $op()))['error'] === 'stale_system', 'Stale browser fingerprint cannot initiate network traffic');
$mode = 'hold_catalog';
$operation = $op();
$manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $operation);
$manager->RefreshWorkspaceDeviceCatalogs();
$oldCatalog = json_decode($manager->Raw(), true)['systems'][$sys]['catalog'];
$changed = $detail($sys)['data'];
$changed['work']['sequence'] = ['dirty' => true, 'input' => ['kept' => true]];
$check($decode($manager->UpdateWorkspaceSystem($sys, 1, $detail($sys)['fingerprint'], json_encode($changed), $op()))['ok'], 'A local change while waiting remains independent of the catalog job');
$pollPackets = $sent();
$manager->GetWorkspaceDeviceCatalogStatus($sys);
$check($sent() === $pollPackets, 'GET status cannot advance a workflow or send a request');
[$p, $packet] = array_pop($held);
$reply($p, $packet);
$manager->RefreshWorkspaceDeviceCatalogs();
$check($detail($sys)['data'] === $changed && $decode($manager->GetWorkspaceDeviceCatalogStatus($sys))['catalogRefresh']['phase'] === 'loaded', 'Completion merges only device data into the latest draft without losing intervening inputs');
$mode = 'hold';
$d = $detail($sys);
$manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $op());
$busyPackets = $sent();
$check($decode($manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $op()))['error'] === 'busy' && $sent() === $busyPackets, 'Concurrent refresh cannot replace or duplicate a pending request');
[$p, $packet] = array_pop($held);
$foreign = ['transaction' => str_repeat('f', 32), 'status' => 'ok', 'data' => ['healthy' => true]];
$p->publish(str_replace('/request/', '/response/', $packet['Topic']), $foreign);
$foreign['transaction'] = $packet['Payload']['transaction'];
$p->publish(str_replace('/request/', '/response/', $packet['Topic']), $foreign, true);
$manager->RefreshWorkspaceDeviceCatalogs();
$check($decode($manager->GetWorkspaceDeviceCatalogStatus($sys))['catalogRefresh']['phase'] === 'health' && $sent() === $busyPackets, 'Foreign and retained health replies cannot advance the catalog workflow');
$mode = 'auto';
$reply($p, $packet);
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceDeviceCatalogs();
$check($decode($manager->GetWorkspaceDeviceCatalogStatus($sys))['catalogRefresh']['phase'] === 'loaded', 'Only the exact fresh health reply allows requesting metadata');
$mode = 'invalid';
$beforeInvalid = $manager->Raw();
$d = $detail($sys);
$manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $op());
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceDeviceCatalogs();
$invalidView = $decode($manager->GetWorkspaceDeviceCatalogStatus($sys))['catalogRefresh'];
$check($invalidView['phase'] === 'error' && $invalidView['error'] !== '' && $manager->Raw() === $beforeInvalid, 'Invalid catalog preserves all previous central data and exposes its error to the compact status');
$mode = 'hold';
$d = $detail($sys);
$beforeTimeout = $manager->Raw();
$manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $op());
$manager->clock += 1000;
$manager->RefreshWorkspaceDeviceCatalogs();
$check(!$decode($manager->GetWorkspaceDeviceCatalogStatus($sys))['catalogRefresh']['pending'] && $manager->Raw() === $beforeTimeout, 'A deadline stops polling without replacing data or retrying MQTT');
foreach ($held as [$p, $packet]) $reply($p, $packet);
$held = [];
$afterTimeout = $sent();
$manager->RefreshWorkspaceDeviceCatalogs();
$check($sent() === $afterTimeout && $manager->Raw() === $beforeTimeout, 'Late responses cannot complete an expired catalog job');
$mode = 'auto';
$d = $detail($sys);
$manager->RequestWorkspaceDeviceCatalog($sys, 1, $d['fingerprint'], $op());
$manager->ApplyChanges();
$restartPackets = $sent();
$manager->RefreshWorkspaceDeviceCatalogs();
$check($sent() === $restartPackets && $manager->Raw() === $beforeTimeout, 'Module update discards transient requests without changing documents or resending MQTT');
$token = explode('#', $manager->OpenAutomationDesigner())[1];
$d = $detail($systems[1]);
$headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $systems[1], 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $d['fingerprint']];
$manager->requestBody = json_encode(['requestId' => '12345678-1234-4234-8234-123456789012']);
$check($manager->Hook('catalog-refresh', $token, $headers, 'GET')[0] === 405, 'Refresh route requires POST');
$check($manager->Hook('catalog-refresh', '', $headers, 'POST')[0] === 403, 'Refresh route requires a scoped designer capability');
$check($manager->Hook('catalog-refresh', $token, $headers, 'POST')[0] === 200, 'Browser refresh route accepts exactly the selected system');
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceDeviceCatalogs();
$headerPackets = $sent();
$check($manager->Hook('catalog-status', $token, $headers)[0] === 200 && $sent() === $headerPackets, 'Browser status route is GET-only and sends no MQTT');
$headers['HTTP_X_Z2MA_GENERATION'] = '2';
$check($manager->Hook('catalog-status', $token, $headers)[0] === 409, 'A foreign generation cannot consume another catalog status');
$manager->requestBody = str_repeat(' ', 4097);
$headers['HTTP_X_Z2MA_GENERATION'] = '1';
$check($manager->Hook('catalog-refresh', $token, $headers, 'POST')[0] === 409, 'Oversized refresh body is bounded');
$move = $systems[7];
$d = $detail($move);
$beforeMove = $manager->Raw();
$mode = 'hold';
$manager->RequestWorkspaceDeviceCatalog($move, 1, $d['fingerprint'], $op());
IPS_SetProperty($connections[7], 'BaseTopic', 'synthetic/moved');
IPS_ApplyChanges($connections[7]);
$manager->RefreshWorkspaceDeviceCatalogs();
$check($manager->Raw() === $beforeMove && !$decode($manager->GetWorkspaceDeviceCatalogStatus($move))['catalogRefresh']['pending'], 'A changed gateway/topic binding stops the job without importing data into the old system');
$debug = implode("\n", $manager->debug);
$check(str_contains($debug, 'Gerätedaten') && !str_contains($debug, 'PRIVATE_EXACT_YAML') && !str_contains($debug, 'PRIVATE_DEVICE') && !str_contains($debug, 'PRIVATE_PENDING_INPUT'), 'German debug explains flows without exporting raw catalog, names or drafts');
echo "Per-system device refresh: correlation, eight systems, drafts, open inputs, timeout, invalid replies, lifecycle and browser routes passed.\n";
