<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

$root = dirname(__DIR__);
IPS\Kernel::reset();
$library = json_decode(file_get_contents($root . '/library.json'), true, 32, JSON_THROW_ON_ERROR);
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);
if (!method_exists(Z2MAutomations::class, 'GetWorkspaceDiagnostics') || !method_exists(Z2MAutomations::class, 'RequestWorkspaceDiagnostics')) throw new RuntimeException('Central read-only diagnostics and explicit observation requests are missing.');
if (!method_exists(Z2MConnection::class, 'GetConnectionDiagnostics')) throw new RuntimeException('A sanitized connection diagnostics view is required.');

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $ok, string $why): void
{ if (!$ok) throw new RuntimeException($why); };
$decode = static fn (string $s): array => json_decode($s, true, 32, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
class DiagnosticsManagerProbe extends Z2MAutomations
{
    public int $clock = 1800000000;
    public array $debug = [];
    public string $requestBody = '';
    public function Raw(): string
    {
        return $this->ReadAttributeString('SystemWorkspace');
    }
    public function RemoveAccess(): void
    {
        $sessions = json_decode($this->GetBuffer('DesignerSessions'), true);
        foreach ($sessions as &$session) unset($session['diagnostics']);
        $this->SetBuffer('DesignerSessions', json_encode($sessions));
    }
    public function Hook(string $path, string $token, array $headers, string $method): array
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
    protected function DesignerRequestBody(): string
    {
        return $this->requestBody;
    }
    protected function RegisterHook(string $path): bool
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
class DiagnosticsConnectionProbe extends Z2MConnection
{
    public int $clock = 1800000000;
    public function Lease(): array
    {
        return json_decode($this->GetBuffer('RuntimeDebug') ?: '[]', true);
    }
    protected function Now(): int
    {
        return $this->clock;
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = DiagnosticsManagerProbe::class;
$modules[SystemContract::CONNECTION_MODULE]['Class'] = DiagnosticsConnectionProbe::class;
$registry->setValue(null, $modules);
$managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
IPS_ApplyChanges($managerID);
$manager = IPS\InstanceManager::getInstanceInterface($managerID);
$overview = static fn (): array => $decode($manager->GetWorkspaceOverview());
$detail = static fn (string $id): array => $decode($manager->GetWorkspaceSystem($id));
$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => '0.1.0', 'extension_build' => ExtensionContract::BundledBuild(),
    'session'                => '11111111-1111-4111-8111-111111111111', 'state' => 'ready', 'stored_revision' => 0, 'checksum' => null,
    'automation_count'       => 0, 'storage' => 'empty', 'executing' => false, 'debug_version' => 1];
$mode = 'auto';
$held = [];
$parents = [];
$parentIDs = [];
$reply = static function (SyntheticMQTTClient $parent, array $packet) use (&$ready): void
{
    $data = str_ends_with($packet['Topic'], '/health_check') ? ['healthy' => true] : $ready;
    if (str_ends_with($packet['Topic'], '/debug')) $data = ['debug_version' => 1, 'client' => $packet['Payload']['client'], 'seconds' => $packet['Payload']['seconds'], 'active' => $packet['Payload']['seconds'] > 0];
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data]);
};
for ($i = 0; $i < 2; $i++) {
    $id = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
    IPS_ApplyChanges($id);
    $p = IPS\InstanceManager::getInstanceInterface($id);
    $p->onPublish = static function (array $packet) use ($p, &$mode, &$held, $reply, $check): void
    {
        $check(preg_match('~/(?:health_check|status|debug)$~', $packet['Topic']) === 1, 'Diagnostics never install, store, activate or send device commands');
        if ($mode === 'hold') {
            $held[] = [$p, $packet];
            return;
        }
        $reply($p, $packet);
    };
    $parents[] = $p;
    $parentIDs[] = $id;
}
$connections = $systems = [];
for ($i = 0; $i < 8; $i++) {
    $ci = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_ConnectInstance($ci, $parentIDs[$i % 2]);
    IPS_SetProperty($ci, 'Active', true);
    IPS_SetProperty($ci, 'BaseTopic', 'synthetic/diagnostics_' . $i);
    IPS_ApplyChanges($ci);
    $connections[] = $ci;
    $systems[] = IPS_GetProperty($ci, 'SystemID');
}
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview()['fingerprint'], $op()))['ok'], 'Configure eight systems on two brokers');
foreach ($systems as $i => $system) {
    $data = WorkspaceState::Data();
    $data['work']['rule'] = ['dirty' => true, 'input' => ['PRIVATE_UNSAVED' => $i]];
    $check($decode($manager->UpdateWorkspaceSystem($system, 1, $detail($system)['fingerprint'], json_encode($data), $op()))['ok'], 'Keep pending native inputs');
}
$baseline = $manager->Raw();
$sent = static fn (): int => array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
$view = static fn (string $id): array => $decode($manager->GetWorkspaceDiagnostics($id))['diagnostics'];
$before = $sent();
$initial = $decode($manager->GetWorkspaceDiagnostics($systems[0]));
$check(count($initial['systems']) === 8 && $initial['diagnostics']['build']['comparison'] === 'unconfirmed' && $sent() === $before, 'Opening reads all systems without MQTT and never claims an unknown build is current');
$request = static function (string $system, string $action, int $seconds = 0, ?string $operation = null, ?string $context = null) use ($manager, $view, $detail, $decode, $op): array
{
    $d = $detail($system);
    return $decode($manager->RequestWorkspaceDiagnostics($system, 1, $d['fingerprint'], $action, $seconds, $operation ?? $op(), $context ?? $view($system)['context']));
};
foreach ($systems as $i => $system) {
    $start = $sent();
    $check($request($system, 'health')['diagnostics']['phase'] === 'confirmed' && $sent() === $start + 1, 'Health checks use the correct broker and report the actual reply');
    $check($request($system, 'extension')['diagnostics']['build']['comparison'] === 'current', 'A confirmed matching extension has the existing build comparison');
    $check($view($system)['healthText'] === 'Gesund', 'Health is shown in German');
}
$first = $systems[0];
$second = $systems[1];
$d = $detail($first);
$ctx = $view($first)['context'];
$operation = $op();
$start = $sent();
$check($request($first, 'debug_start', 60, $operation, $ctx)['diagnostics']['debug']['active'], 'Start requires and receives a debug confirmation');
$lease = IPS\InstanceManager::getInstanceInterface($connections[0])->Lease();
$check($request($first, 'debug_start', 60, $operation, $ctx)['ok'] && $sent() === $start + 1, 'Replaying a start never resends or extends its end time');
$check($request($first, 'debug_start', 61, $operation, $ctx)['error'] === 'operation_conflict', 'Changed intent cannot reuse a receipt');
$check($request($second, 'debug_start', 120)['diagnostics']['debug']['active'], 'Record on the second broker independently');
$check($view($first)['debug']['until'] === 1800000060 && $view($second)['debug']['until'] === 1800000120, 'Each system keeps its own deadline');
$start = $sent();
$json = $manager->GetWorkspaceDiagnostics($first);
$check(!str_contains($json, $lease['client']) && !str_contains($json, $lease['session']) && !str_contains($json, 'PRIVATE_UNSAVED') && $sent() === $start, 'Read views expose neither subscription identities nor input data and never send MQTT');
$request($first, 'extension');
$check($view($first)['debug']['until'] === 1800000060, 'Status refresh preserves the original recording end');
$mode = 'hold';
$request($first, 'extension');
$check($view($first)['pending'] && $view($first)['debug']['canStop'], 'Own recording can be stopped during a pending extension probe');
$mode = 'auto';
$check($request($first, 'debug_stop')['ok'] && $view($first)['debug']['phase'] === 'idle' && $view($second)['debug']['active'], 'Stop affects only the selected connection, without cancelling its status request');
foreach ($held as [$p, $packet]) $reply($p, $packet);
$held = [];
$check($view($first)['phase'] === 'confirmed', 'The independent status probe completes after debug stop');
$connection = IPS\InstanceManager::getInstanceInterface($connections[1]);
$connection->clock += 121;
$start = $sent();
$check($view($second)['debug']['phase'] === 'idle' && $sent() === $start, 'Expired recording ends locally on read and does not send a new command');
$connection->clock = 1800000000;
$ready['extension_build'] = str_repeat('f', 64);
$request($first, 'extension');
$check($view($first)['build']['comparison'] === 'different', 'A different installed build produces the shared update hint');
unset($ready['extension_build']);
$request($first, 'extension');
$check($view($first)['build']['comparison'] === 'legacy', 'A legacy build remains explicitly incomparable');
$ready['extension_build'] = ExtensionContract::BundledBuild();
$request($first, 'extension');
$stale = $view($first)['context'];
$request($first, 'debug_start', 45);
$check($request($first, 'debug_stop', 0, null, $stale)['error'] === 'diagnostics_changed', 'A stale stop cannot stop a replacement recording');
$request($first, 'debug_stop');
$mode = 'hold';
$request($first, 'health');
$connection = IPS\InstanceManager::getInstanceInterface($connections[0]);
$connection->clock += 11;
$check($view($first)['phase'] === 'error' && str_contains($view($first)['healthText'], 'Keine Antwort'), 'Missing responses are reported as failures, never as success');
$connection->clock = 1800000000;
$held = [];
$mode = 'auto';
$start = $sent();
foreach ([0, 901] as $seconds) $check(!$request($first, 'debug_start', $seconds)['ok'], 'Recording duration must be from 1 to 900 seconds');
$check($sent() === $start && !$request($first, 'install')['ok'], 'Invalid durations and installation commands never reach MQTT');
$request($first, 'extension');
$mode = 'hold';
$request($first, 'debug_start', 60);
$check($view($first)['debug']['phase'] === 'starting', 'A request is not falsely displayed as an acknowledged recording');
$mode = 'auto';
$request($first, 'debug_stop');
foreach ($held as [$p, $packet]) $reply($p, $packet);
$held = [];
$check($view($first)['debug']['phase'] === 'idle', 'A late start reply cannot reactivate a stopped recording');
$mode = 'hold';
$request($first, 'debug_start', 60);
$connection->clock += 11;
$check($view($first)['debug']['phase'] === 'idle', 'Unacknowledged recording is released after its original timeout');
$connection->clock = 1800000000;
$mode = 'auto';
$held = [];
$request($first, 'debug_start', 60);
$until = $view($first)['debug']['until'];
$manager->ApplyChanges();
$check($view($first)['debug']['until'] === $until, 'Updating the central module does not extend or stop a connection recording');
$request($first, 'debug_stop');
$last = $systems[7];
$lastConnection = $connections[7];
$start = $sent();
IPS_SetProperty($lastConnection, 'Active', false);
IPS_ApplyChanges($lastConnection);
$check(!$view($last)['canCheck'] && $view($last)['build']['comparison'] === 'unconfirmed' && !$request($last, 'health')['ok'] && $sent() === $start, 'Disconnected connections never claim fresh metadata or accept observations');
IPS_SetProperty($lastConnection, 'Active', true);
IPS_SetProperty($lastConnection, 'Generation', 2);
IPS_ApplyChanges($lastConnection);
$check($view($last)['build'] === null && !$request($last, 'health')['ok'] && $sent() === $start, 'Changed generations do not reuse a prior connection or debug lease');
IPS_SetProperty($lastConnection, 'Generation', 1);
IPS_ApplyChanges($lastConnection);
$mode = 'hold';
$request($first, 'health');
[$healthParent, $healthPacket] = $held[array_key_last($held)];
$healthParent->publish(str_replace('/request/', '/response/', $healthPacket['Topic']), ['transaction' => $healthPacket['Payload']['transaction'], 'status' => 'error']);
$check($view($first)['healthText'] === 'Prüfung fehlgeschlagen' && $view($first)['phase'] === 'error', 'An explicit receiver rejection is never displayed as an unchecked connection');
$held = [];
$mode = 'auto';
$token = substr($manager->OpenAutomationDesigner(), -64);
$headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $first, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $detail($first)['fingerprint']];
$start = $sent();
[$code, $body] = $manager->Hook('diagnostics-status', $token, $headers, 'GET');
$check($code === 200 && count($decode($body)['systems']) === 8 && strlen($body) < 32768 && $sent() === $start, 'Fixed GET route is cached, bounded and read-only');
$manager->requestBody = json_encode(['requestId' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', 'action' => 'health', 'seconds' => 0, 'context' => $view($first)['context']]);
[$code] = $manager->Hook('diagnostics', $token, $headers, 'POST');
$check($code === 200 && $sent() === $start + 1, 'The scoped POST reaches the existing health transport once');
[$code] = $manager->Hook('diagnostics', $token, $headers, 'POST');
$check($code === 200 && $sent() === $start + 1, 'Duplicate browser POST does not resend MQTT');
$beforeRejected = $sent();
$manager->requestBody = json_encode(['requestId' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'action' => 'install', 'seconds' => 0, 'context' => $view($first)['context']]);
[$code] = $manager->Hook('diagnostics', $token, $headers, 'POST');
$check($code === 409 && $sent() === $beforeRejected, 'The browser route cannot install an extension');
$manager->requestBody = json_encode(['requestId' => 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', 'action' => 'health', 'seconds' => 0, 'context' => $view($first)['context'], 'topic' => 'synthetic/forbidden']);
[$code] = $manager->Hook('diagnostics', $token, $headers, 'POST');
$check($code === 409 && $sent() === $beforeRejected, 'Extra MQTT topics and arbitrary command parameters are rejected');
[$code] = $manager->Hook('diagnostics', $token, $headers, 'GET');
$check($code === 405, 'GET cannot invoke a diagnostic command');
[$code] = $manager->Hook('diagnostics-status', $token, array_replace($headers, ['HTTP_X_Z2MA_GENERATION' => '2']), 'GET');
$check($code === 409, 'Changed binding generation is rejected');
$manager->requestBody = str_repeat('x', 4097);
[$code] = $manager->Hook('diagnostics', $token, $headers, 'POST');
$check($code === 409, 'Oversized bodies never reach the transport');
$manager->RemoveAccess();
[$code] = $manager->Hook('diagnostics-status', $token, $headers, 'GET');
$check($code === 403, 'A separate browser capability is required for diagnostics');
$check($manager->Raw() === $baseline, 'Every draft, selection, grant and open native input stays byte-identical across all observations');
$debug = implode("\n", $manager->debug);
$check(str_contains($debug, 'keine Verlängerung') && str_contains($debug, 'Automatikbetrieb bleiben erhalten') && !str_contains($debug, 'PRIVATE_UNSAVED') && !str_contains($debug, $token) && !str_contains($debug, $lease['client']), 'German flow debug explains intent and replay without private inputs or credentials');
echo "Workspace diagnostics: eight systems, two brokers, probes, build comparison, bounded debug leases and HTTP isolation passed.\n";
