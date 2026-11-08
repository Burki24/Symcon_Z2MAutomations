<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

if (!method_exists(Z2MAutomations::class, 'RequestWorkspaceRuntime')) {
    // Load the library before checking its public central control seam.
    $root = dirname(__DIR__);
    $library = json_decode(file_get_contents($root . '/library.json'), true, 32, JSON_THROW_ON_ERROR);
    IPS\Kernel::reset();
    foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient', 'tests/fixtures/Z2MDevice'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);
}
if (!method_exists(Z2MAutomations::class, 'RequestWorkspaceRuntime')) throw new RuntimeException('Central system-bound activation, stopping and single-run control are missing.');

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\Z2MProtocol;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $ok, string $why): void
{ if (!$ok) throw new RuntimeException($why); };
$decode = static fn (string $raw): array => json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
class RuntimeManagerProbe extends Z2MAutomations
{
    public static string $storage;
    public array $debug = [];
    public string $requestBody = '';
    public function LoseState(): void
    {
        $this->SetBuffer('DesignerSessions', '');
    }
    public function Hook(string $path, string $token, array $headers = [], string $method = 'GET'): array
    {
        $_SERVER = $headers + ['REQUEST_URI' => '/hook/Z2MAutomationsDesigner' . $this->InstanceID . '/' . $path, 'REQUEST_METHOD' => $method,
            'HTTP_X_Z2MA_READ_TOKEN'         => $token, 'HTTP_HOST' => '127.0.0.1:3777', 'HTTP_ORIGIN' => 'http://127.0.0.1:3777', 'CONTENT_TYPE' => 'application/json'];
        ob_start();
        try {
            $this->ProcessHookData();
            return [http_response_code(), ob_get_contents()];
        }
        finally {
            ob_end_clean();
        }
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
    protected function DesignerRequestBody(): string
    {
        return $this->requestBody;
    }
}
class RuntimeConnectionProbe extends Z2MConnection
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
$modules[SystemContract::SOURCE_MODULE]['Class'] = RuntimeManagerProbe::class;
$modules[SystemContract::CONNECTION_MODULE]['Class'] = RuntimeConnectionProbe::class;
$registry->setValue(null, $modules);
foreach ([[1, false], [2, false], [2, true], [8, false]] as [$count, $split]) {
    RuntimeManagerProbe::$storage = sys_get_temp_dir() . '/z2ma-runtime-' . $op();
    $id = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
    $manager = IPS\InstanceManager::getInstanceInterface($id);
    $parents = $connections = $metadata = $documents = $snapshots = $devices = [];
    $parentID = 0;
    for ($i = 0; $i < $count; $i++) {
        if ($parentID === 0 || $split) {
            $parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
            IPS_ApplyChanges($parentID);
            $parents[$parentID] = IPS\InstanceManager::getInstanceInterface($parentID);
        }
        $topic = 'synthetic/runtime_' . $count . '_' . ($split ? 'split' : 'shared') . '_' . $i;
        $cid = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
        IPS_ConnectInstance($cid, $parentID);
        IPS_SetProperty($cid, 'BaseTopic', $topic);
        IPS_SetProperty($cid, 'Active', true);
        IPS_ApplyChanges($cid);
        $connections[$topic] = IPS\InstanceManager::getInstanceInterface($cid);
        $device = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
        IPS_ConnectInstance($device, $parentID);
        IPS_SetProperty($device, 'MQTTBaseTopic', $topic);
        IPS_SetProperty($device, 'IEEE', '0x00124b0000000001');
        IPS_ApplyChanges($device);
        $devices[$topic] = $device;
        $cfg = ['schema_version' => 1, 'revision' => 3, 'automations' => [['id' => 'same_rule', 'name' => 'PRIVATE_RUNTIME_RULE', 'enabled' => false,
            'triggers'                                                          => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
            'actions'                                                           => [['type' => 'set', 'target' => ['type' => 'device', 'id' => '0x00124b0000000001'], 'values' => ['state' => 'ON']]]]]];
        $yaml = json_encode($cfg) . "\n";
        $documents[$topic] = ['configuration' => $cfg, 'yaml' => $yaml];
        $session = sprintf('11111111-1111-4111-8111-%012d', $i + 1);
        $metadata[$topic] = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => ExtensionContract::BundledVersion(),
            'session'                           => $session, 'state' => 'ready', 'stored_revision' => 3, 'checksum' => hash('sha256', $yaml), 'automation_count' => 1, 'storage' => 'ok',
            'executing'                         => false, 'runtime_version' => 1, 'active_revision' => null, 'active_checksum' => null, 'enabled_count' => 0, 'runtime_error' => '',
            'catalog_version'                   => 1, 'execution_version' => 1, 'resume_version' => 1, 'resume_enabled' => false, 'resume_status' => 'stopped', 'resume_error' => ''];
        $snapshots[$topic] = ['protocol_version' => 1, 'execution_version' => 1, 'session' => $session, 'revision' => 3, 'checksum' => hash('sha256', $yaml), 'sequence' => 0, 'runs' => []];
        $parents[$parentID]->publish($topic . '/bridge/state', ['state' => 'online']);
    }
    $drop = '';
    $condition = true;
    $mutate = null;
    foreach ($parents as $parent) $parent->onPublish = static function (array $packet) use ($parent, &$metadata, &$documents, &$snapshots, &$drop, &$condition, &$mutate): void
    {
        $topic = strstr($packet['Topic'], '/symcon/', true);
        $kind = substr($packet['Topic'], strrpos($packet['Topic'], '/') + 1);
        if ($drop === $kind) return;
        $data = $metadata[$topic];
        if ($kind === 'status') $data += ['activation_token' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'execution' => $snapshots[$topic]];
        if ($kind === 'editor') $data += ['editor_version' => 1] + $documents[$topic] + ['devices' => [['id' => '0x00124b0000000001', 'name' => 'Synthetic light', 'triggers' => [], 'actions' => [], 'numeric' => [],
            'properties'                                                                                     => [['property' => 'state', 'type' => 'binary', 'access' => 3, 'label' => 'State', 'endpoint' => '', 'values' => ['ON', 'OFF']]]]], 'groups' => [], 'scenes' => []];
        if (in_array($kind, ['activate', 'deactivate'], true)) {
            $active = $kind === 'activate';
            $metadata[$topic] = array_replace($metadata[$topic], ['executing' => $active, 'active_revision' => $active ? 3 : null,
                'active_checksum'                                             => $active ? $metadata[$topic]['checksum'] : null, 'enabled_count' => 0,
                'resume_enabled'                                              => $active && $packet['Payload']['resume_after_restart'], 'resume_status' => $active ? 'armed' : 'stopped']);
            $data = $metadata[$topic];
        }
        if ($kind === 'run_once') {
            $snapshots[$topic]['sequence']++;
            $snapshots[$topic]['runs'] = [['rule_id' => 'same_rule', 'run' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'revision' => 3,
                'checksum'                           => $metadata[$topic]['checksum'], 'state' => $condition ? 'running' : 'skipped', 'step' => $condition ? 1 : 0, 'error' => $condition ? '' : 'conditions_not_met']];
            $data = $snapshots[$topic];
        }
        if ($kind === 'stop_rule') {
            $snapshots[$topic]['sequence']++;
            $snapshots[$topic]['runs'][0]['state'] = 'stopped';
            $data = $snapshots[$topic];
        }
        $reply = ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data];
        if ($mutate !== null && !$mutate($packet, $reply)) return;
        $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), $reply);
    };
    $overview = $decode($manager->GetWorkspaceOverview());
    $check($decode($manager->ConfigureWorkspace(json_encode(array_map(static fn ($c): int => (new ReflectionProperty($c, 'InstanceID'))->getValue($c), array_values($connections))), $overview['fingerprint'], $op()))['ok'], 'Configure synthetic systems.');
    $systems = $decode($manager->GetWorkspaceOverview())['systems'];
    foreach ($systems as $s) {
        $operation = $op();
        $check($decode($manager->StartWorkspaceSetup($s['systemId'], 1, $s['fingerprint'], $operation))['setup']['phase'] === 'preview', 'Real direct setup preview.');
        $check($decode($manager->ConfirmWorkspaceSetup($operation))['setup']['phase'] === 'complete', 'Exclusive direct setup owner.');
    }
    $details = static fn (array $s): array => $decode($manager->GetWorkspaceSystem($s['systemId']));
    $view = static fn (array $s): array => $decode($manager->GetWorkspaceRuntimeStatus($s['systemId']))['runtime'];
    $request = static function (array $s, string $kind, string $rule = '', string $run = '', bool $resume = true, ?string $operation = null) use ($manager, $details, $view, $op, $decode): array
    {
        $v = $view($s);
        return $decode($manager->RequestWorkspaceRuntime($s['systemId'], 1, $details($s)['fingerprint'], $kind, $rule, $run, $resume, $operation ?? $op(), $v['context']));
    };
    $first = $systems[0];
    $topic = $first['binding']['baseTopic'];
    $original = $details($first)['data'];
    $before = $metadata;
    require __DIR__ . '/ownership-contention.inc.php';
    $check($decode($manager->GetWorkspaceSetupStatus())['setup']['canRollback'], 'Local return is initially available.');
    $request($first, 'status');
    $check($decode($manager->GetWorkspaceSetupStatus())['setup']['canRollback'], 'Pure runtime status does not block local return.');
    $check($view($first)['canActivate'], 'Matching saved draft can activate.');
    $active = $request($first, 'activate');
    $check($active['ok'] && $active['runtime']['status'] === 'activated' && $active['runtime']['executing'] === true && $active['runtime']['resumeAfterRestart'], 'Activation is followed by a fresh status and restart intent confirmation: ' . json_encode($active));
    $check(!$decode($manager->GetWorkspaceSetupStatus())['setup']['canRollback'], 'A possible external control prevents a misleading local rollback.');
    foreach (array_slice($systems, 1) as $s) $check($metadata[$s['binding']['baseTopic']] === $before[$s['binding']['baseTopic']], 'Other brokers and identical rule identities remain untouched.');
    $check($request($first, 'deactivate')['runtime']['status'] === 'stopped', 'Global stop confirmed.');
    $one = $request($first, 'run_once', 'same_rule');
    $check($one['runtime']['status'] === 'run_started' && $one['runtime']['executing'] === false && $one['runtime']['rules']['same_rule']['state'] === 'running', 'Disabled saved rule can run once without arming automatic execution.');
    $run = $one['runtime']['rules']['same_rule']['run'];
    $check($request($first, 'stop_rule', 'same_rule', $run)['runtime']['status'] === 'run_stopped', 'Targeted stop acknowledges exactly the captured run.');
    $condition = false;
    $skipped = $request($first, 'run_once', 'same_rule');
    $check($skipped['runtime']['rules']['same_rule']['state'] === 'skipped' && str_contains($skipped['runtime']['rules']['same_rule']['message'], 'Bedingung'), 'Condition rejection is shown distinctly from a completed action.');
    $condition = true;
    $check($details($first)['data'] === $original, 'Runtime commands never modify draft, selection, enabled flags or peer permissions.');
    $timerSent = array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
    $manager->RefreshWorkspaceRuntime();
    $check(array_sum(array_map(static fn ($p): int => count($p->sent), $parents)) === $timerSent, 'Runtime timer reads all assigned systems without sending MQTT commands.');
    foreach ($parents as $parent) foreach ($parent->sent as $packet) $check(preg_match('~/request/(store|validate|install|restart)$~', $packet['Topic']) !== 1, 'Runtime control never stores, validates, installs or restarts.');
    if ($count === 1) require __DIR__ . '/workspace-runtime-peer.inc.php';
    if ($count !== 2 || $split) continue;
    $sent = static fn (): int => array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
    $v = $view($first);
    $operation = $op();
    $args = [$first['systemId'], 1, $details($first)['fingerprint'], 'activate', '', '', false, $operation, $v['context']];
    $check($decode($manager->RequestWorkspaceRuntime(...$args))['runtime']['status'] === 'activated', 'Explicitly disabled restart policy is confirmed too.');
    $n = $sent();
    $check($decode($manager->RequestWorkspaceRuntime(...$args))['ok'] && $sent() === $n, 'Repeated operation ID cannot send a second activation.');
    $drop = 'activate';
    $check($request($first, 'activate')['runtime']['pending'], 'Lost command reply remains pending.');
    $connections[$topic]->clock += 11;
    $timeout = $view($first);
    $n = $sent();
    $check($timeout['status'] === 'timeout' && !$timeout['confirmed'] && $view($first)['status'] === 'timeout' && $sent() === $n, 'Timeout never retries and cannot claim confirmed active execution.');
    $drop = '';
    $check($request($first, 'deactivate')['runtime']['status'] === 'stopped', 'Explicit stop recovers unknown runtime.');
    $mutate = static function (array $packet, array &$reply) use (&$drop): bool
    {
        if (str_ends_with($packet['Topic'], '/activate')) $drop = 'status';
        return true;
    };
    $unconfirmed = $request($first, 'activate');
    $check($unconfirmed['runtime']['pending'] && !$unconfirmed['runtime']['confirmed'], 'Command acknowledgement alone never confirms activation.');
    $mutate = null;
    $connections[$topic]->clock += 11;
    $check($view($first)['status'] === 'timeout' && !$view($first)['confirmed'], 'Missing final status remains unconfirmed without retry.');
    $drop = '';
    $request($first, 'deactivate');
    $drop = 'activate';
    $request($first, 'activate');
    $drop = '';
    $check($request($first, 'deactivate')['runtime']['status'] === 'stopped', 'Stop supersedes a pending activation.');
    $request($first, 'run_once', 'same_rule');
    $oldRun = $view($first)['rules']['same_rule']['run'];
    $snapshots[$topic]['runs'][0]['run'] = 'cccccccc-cccc-4ccc-8ccc-cccccccccccc';
    $snapshots[$topic]['sequence']++;
    $changed = $request($first, 'stop_rule', 'same_rule', $oldRun);
    $check($changed['runtime']['status'] === 'error' && $changed['runtime']['rules']['same_rule']['run'] !== $oldRun, 'Fresh status rejects stopping a replacement run.');
    $snapshot = $details($first);
    $dirty = $snapshot['data'];
    $dirty['work']['rule'] = ['dirty' => true, 'input' => ['EditorName' => 'PRIVATE_UNSAVED']];
    $check($decode($manager->UpdateWorkspaceSystem($first['systemId'], 1, $snapshot['fingerprint'], json_encode($dirty), $op()))['ok'], 'Seed pending local input.');
    $check(!$view($first)['canActivate'] && $view($first)['canStop'], 'Unsaved inputs disable starting but never global stopping.');
    $check($request($first, 'deactivate')['runtime']['status'] === 'stopped' && $details($first)['data'] === $dirty, 'Stopping retains unsaved work.');
    $snapshot = $details($first);
    $manager->UpdateWorkspaceSystem($first['systemId'], 1, $snapshot['fingerprint'], json_encode($original), $op());
    $drop = 'status';
    $request($first, 'status');
    $connections[$topic]->LoseState();
    $check($view($first)['status'] === 'interrupted', 'Lost transient workflow becomes an interrupted durable result.');
    $drop = '';
    $check($request($systems[1], 'status')['runtime']['status'] === 'status_received', 'A failed system does not block another system.');
    $request($first, 'status');
    $n = $sent();
    $stale = $view($first);
    $check(!$decode($manager->RequestWorkspaceRuntime($first['systemId'], 2, $stale['fingerprint'], 'activate', '', '', true, $op(), $stale['context']))['ok'] && $sent() === $n, 'Stale generations never send.');
    $check(!$decode($manager->RequestWorkspaceRuntime($first['systemId'], 1, str_repeat('0', 64), 'activate', '', '', true, $op(), $stale['context']))['ok'] && $sent() === $n, 'Stale documents never send.');
    $check(!$decode($connections[$topic]->RequestRuntimeControl($id, $op()))['ok'] && $sent() === $n, 'An unjournaled direct connection command has no authority.');
    // An old or retained response can neither start execution nor release controls.
    $mutate = static function (array $packet, array &$reply) use ($parents, $check): bool
    {
        if (!str_ends_with($packet['Topic'], '/status')) return true;
        $responseTopic = str_replace('/request/', '/response/', $packet['Topic']);
        $parent = array_values($parents)[0];
        $parent->publish($responseTopic, $reply, true);
        $wrong = $reply;
        $wrong['transaction'] = str_repeat('0', 32);
        $parent->publish($responseTopic, $wrong);
        $wrong = $reply;
        $wrong['protocol_version'] = 2;
        $parent->publish($responseTopic, $wrong);
        return false;
    };
    $pending = $request($first, 'activate');
    $check(in_array($pending['runtime']['status'], ['pending', 'error'], true), 'Retained, unrelated and wrong-protocol replies cannot authorize activation.');
    $check(count(array_filter(array_merge(...array_map(static fn ($p): array => array_slice($p->sent, $n), $parents)), static fn ($p): bool => str_ends_with($p['Topic'], '/activate'))) === 0, 'No mutation follows an invalid status.');
    $mutate = null;
    $request($first, 'deactivate');
    // A changed Z2M session invalidates the request before its command phase.
    $metadata[$topic]['session'] = 'dddddddd-dddd-4ddd-8ddd-dddddddddddd';
    $snapshots[$topic]['session'] = $metadata[$topic]['session'];
    $changed = $request($first, 'activate');
    $check($changed['runtime']['status'] === 'error' && str_contains($changed['runtime']['message'], 'geändert'), 'A newly observed Z2M session requires an explicit new selection.');
    $request($first, 'deactivate');
    // Native rendering exposes only the scoped entry, and the generated click runs.
    $overview = $decode($manager->GetWorkspaceOverview());
    $manager->SelectWorkspaceSystem($first['systemId'], $overview['fingerprint'], $op());
    $form = $decode($manager->GetConfigurationForm());
    $check(str_contains(GetValue(IPS_GetObjectIDByIdent('RuntimeSystem', $id)), $topic), 'Opening the native system updates its matching status variable too.');
    $flatten = static function (array $items) use (&$flatten): array
    {
        $result = [];
        foreach ($items as $item) {
            if (isset($item['name'])) $result[$item['name']] = $item;
            $result += $flatten($item['items'] ?? []);
        }
        return $result;
    };
    $fields = $flatten($form['actions']);
    $check(isset($fields['WorkspaceRuntimeActivate'], $fields['WorkspaceRuntimeStopRule']) && str_contains($fields['WorkspaceRuntimeActivate']['onClick'], $first['systemId']), 'Native buttons carry an explicit system identity.');
    $WorkspaceEditorFingerprint = $details($first)['fingerprint'];
    $WorkspaceRuntimeContext = $view($first)['context'];
    $WorkspaceRuntimeResume = true;
    $check($fields['WorkspaceRuntimeRefresh']['enabled'], 'Native fresh status is enabled.');
    eval($fields['WorkspaceRuntimeRefresh']['onClick']);
    $check($view($first)['status'] === 'status_received', 'Generated native status callback executes with declared arguments.');
    // Browser route: cached GET, explicit same-origin POST and exact run context.
    $url = $manager->OpenAutomationDesigner();
    $token = substr($url, strrpos($url, '#') + 1);
    $v = $view($first);
    $headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $first['systemId'], 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $v['fingerprint']];
    $n = $sent();
    [$http, $body] = $manager->Hook('runtime-status', $token, $headers);
    $check($http === 200 && $decode($body)['runtime']['systemId'] === $first['systemId'] && $sent() === $n, 'Browser polling reads cached results without MQTT.');
    $manager->requestBody = json_encode(['action' => 'status', 'context' => $v['context'], 'requestId' => 'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee', 'rule' => '', 'run' => '', 'resume' => true]);
    [$http, $body] = $manager->Hook('runtime', $token, $headers, 'POST');
    $check($http === 200 && $decode($body)['runtime']['status'] === 'status_received', 'Browser explicit status uses the real connection workflow.');
    $n = $sent();
    [$http] = $manager->Hook('runtime', $token, $headers, 'GET');
    $check($http === 405 && $sent() === $n, 'GET cannot issue runtime commands.');
    [$http] = $manager->Hook('runtime', str_repeat('0', 64), $headers, 'POST');
    $check($http === 403 && $sent() === $n, 'Unknown browser token sends no commands.');
    [$http] = $manager->Hook('runtime', $token, ['HTTP_ORIGIN' => 'http://foreign.example'] + $headers, 'POST');
    $check($http === 403 && $sent() === $n, 'Cross-origin control is rejected.');
    $check($details($first)['data'] === $original, 'Native and browser control retain all draft data.');
    $debug = implode(' ', $manager->debug) . implode(' ', $connections[$topic]->debug);
    $check(str_contains($debug, 'Betriebsauftrag') && !str_contains($debug, 'PRIVATE_RUNTIME_RULE') && !str_contains($debug, 'PRIVATE_UNSAVED'), 'German debug does not expose names or form contents.');
}
echo "Central runtime: 1/2/8 systems, confirmed activation/stop, exact single runs, conditions, priorities, isolation and no draft changes passed.\n";
