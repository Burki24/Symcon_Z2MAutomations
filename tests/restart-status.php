<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

$check = static function (bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
};
trait RestartStatusProbe
{
    public int $clock = 1000;
    public array $messages = [];
    public function RequestInterval(): int
    {
        return $this->GetTimerInterval('RequestTimeout');
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->messages[] = json_decode($Data, true, 32, JSON_THROW_ON_ERROR);
        return true;
    }
}
IPS\Kernel::reset();
$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (['Z2MConnection', 'Z2MAutomations'] as $name) IPS\ModuleLoader::loadSingleModule($root . '/' . $name, $library['id']);
IPS\ModuleLoader::loadSingleModule(__DIR__ . '/fixtures/MQTTClient', $library['id']);
class RestartConnectionProbe extends Z2MConnection
{
    use RestartStatusProbe;
}
class RestartAutomationProbe extends Z2MAutomations
{
    use RestartStatusProbe;
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$ids = [];
foreach (['Z2MConnection' => RestartConnectionProbe::class, 'Z2MAutomations' => RestartAutomationProbe::class] as $name => $class) {
    $id = json_decode(file_get_contents($root . '/' . $name . '/module.json'), true, 512, JSON_THROW_ON_ERROR)['id'];
    $modules[$id]['Class'] = $class;
    $ids[] = $id;
}
$registry->setValue(null, $modules);
$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => '0.1.0',
    'session'                => '11111111-1111-4111-8111-111111111111', 'state' => 'ready', 'stored_revision' => 0,
    'checksum'               => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false,
    'runtime_version'        => 1, 'active_revision' => null, 'active_checksum' => null, 'enabled_count' => 0, 'runtime_error' => ''];
foreach ($ids as $guid) {
    $id = IPS_CreateInstance($guid);
    $parentID = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
    IPS_ApplyChanges($parentID);
    IPS_ConnectInstance($id, $parentID);
    IPS_SetProperty($id, 'Active', true);
    IPS_SetProperty($id, 'BaseTopic', 'synthetic/restart-' . $id);
    IPS_ApplyChanges($id);
    $module = IPS\InstanceManager::getInstanceInterface($id);
    $parent = IPS\InstanceManager::getInstanceInterface($parentID);
    $base = IPS_GetProperty($id, 'BaseTopic');
    $state = static fn (): array => json_decode($module->GetTransportState(), true, 32, JSON_THROW_ON_ERROR);
    $reply = static function (array $packet, array $data, bool $retained = false) use ($parent): void
    {
        $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
            'transaction'                                                                              => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => (object) $data], $retained);
    };
    $restart = static function () use ($parent, $base): void
    {
        $parent->publish($base . '/bridge/state', ['state' => 'offline']);
        $parent->publish($base . '/bridge/state', ['state' => 'online']);
    };
    $restart();
    $check($module->RequestInterval() === 1000 && $parent->sent === [], 'A Z2M restart must queue a delayed read-only status confirmation without a form or manual click');
    $module->clock++;
    $module->ExpireRequest();
    $first = $parent->sent[array_key_last($parent->sent)];
    $check(str_ends_with($first['Topic'], '/request/status') && !$state()['extensionConfirmed'], 'An early unanswered status request cannot confirm readiness');
    $module->clock += 11;
    $module->ExpireRequest();
    $check($module->RequestInterval() === 2000 && $state()['lastError'] === '', 'Extension startup timeout must schedule a bounded retry without a persistent failure');
    $parent->publish($base . '/symcon/automations/status', $ready, true);
    $check(!$state()['extensionConfirmed'], 'A retained startup announcement never substitutes for a fresh correlated response');
    $module->clock += 2;
    $module->ExpireRequest();
    $second = $parent->sent[array_key_last($parent->sent)];
    $check(count($parent->sent) === 2 && $second['Payload']['transaction'] !== $first['Payload']['transaction'], 'Retry uses a new correlation identifier and sends exactly one read');
    $reply($first, $ready);
    $reply($second, $ready, true);
    $check(!$state()['extensionConfirmed'], 'Late and retained responses cannot confirm the current attempt');
    $reply($second, $ready);
    $check($state()['extensionConfirmed'] && $state()['runtimeConfirmed'] && $module->RequestInterval() === 0, 'A fresh reply confirms the extension and runtime and cancels all retries');

    // A reported startup phase also retries, and a new session must be confirmed.
    $restart();
    $module->clock++;
    $module->ExpireRequest();
    $packet = $parent->sent[array_key_last($parent->sent)];
    $starting = $ready;
    $starting['state'] = 'stopped';
    $reply($packet, $starting);
    $check(!$state()['extensionConfirmed'] && $module->RequestInterval() === 2000, 'A not-ready receiver is retried rather than treated as permanently unavailable');
    $module->clock += 2;
    $module->ExpireRequest();
    $packet = $parent->sent[array_key_last($parent->sent)];
    $newReady = $ready;
    $newReady['session'] = '22222222-2222-4222-8222-222222222222';
    $parent->publish($base . '/symcon/automations/status', $ready, true);
    $parent->publish($base . '/symcon/automations/status', $newReady);
    $check(!$state()['extensionConfirmed'], 'A new unsolicited receiver session does not cancel its read-only confirmation or authorize readiness');
    $reply($packet, $newReady);
    $check($state()['extensionConfirmed'] && $module->RequestInterval() === 0, 'A matching response confirms the newly observed session');

    // Missing extensions exhaust a fixed budget; duplicate announcements cannot renew it.
    $restart();
    $before = count($parent->sent);
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $module->clock += $attempt === 1 ? 1 : 2;
        $module->ExpireRequest();
        $check(count($parent->sent) === $before + $attempt, 'Each retry publishes exactly one status request');
        $parent->publish($base . '/bridge/state', ['state' => 'online']);
        $module->clock += 11;
        $module->ExpireRequest();
    }
    $module->clock += 100;
    $module->ExpireRequest();
    $check(
        count($parent->sent) === $before + 3 && $state()['lastError'] === 'timeout' && $module->RequestInterval() === 0,
        'Three unsuccessful attempts stop with a visible timeout and no polling loop'
    );
    $reply($parent->sent[array_key_last($parent->sent)], $newReady);
    $check(!$state()['extensionConfirmed'], 'A response after the retry budget expired cannot retroactively confirm the receiver');

    // Explicit not_ready errors use the same budget, without trusting malformed replies.
    $restart();
    $module->clock++;
    $module->ExpireRequest();
    $packet = $parent->sent[array_key_last($parent->sent)];
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
        'transaction'                                                                              => $packet['Payload']['transaction'], 'status' => 'error', 'error' => ['code' => 'not_ready']]);
    $check($module->RequestInterval() === 2000 && $state()['lastError'] === '', 'A correlated not_ready error defers only the status check');
    $module->clock += 2;
    $module->ExpireRequest();
    $reply($parent->sent[array_key_last($parent->sent)], $newReady);
    $check($state()['extensionConfirmed'] && $module->RequestInterval() === 0, 'A ready receiver completes the deferred error path');

    // An explicit operation wins while a retry is queued.
    $restart();
    $module->clock++;
    $module->ExpireRequest();
    $module->clock += 11;
    $module->ExpireRequest();
    $manual = $module->RequestExtensionStatus();
    $packet = $parent->sent[array_key_last($parent->sent)];
    $before = count($parent->sent);
    $module->clock += 2;
    $module->ExpireRequest();
    $check(count($parent->sent) === $before && $state()['operation']['transaction'] === $manual, 'A queued retry cannot displace or duplicate an explicit request');
    $reply($packet, $newReady);
    $check($state()['extensionConfirmed'] && $module->RequestInterval() === 0, 'The explicit request completes without a trailing automatic probe');

    // Disabling a connection and changing its topic both invalidate queued retries.
    foreach (['disable', 'topic'] as $change) {
        $restart();
        $module->clock++;
        $module->ExpireRequest();
        $module->clock += 11;
        $module->ExpireRequest();
        if ($change === 'disable') IPS_SetProperty($id, 'Active', false);
        else IPS_SetProperty($id, 'BaseTopic', $base . '-changed');
        IPS_ApplyChanges($id);
        $before = count($parent->sent);
        $module->clock += 2;
        $module->ExpireRequest();
        $check(count($parent->sent) === $before && $module->RequestInterval() === 0, 'Connection changes cancel startup retries without sending to either scope');
        IPS_SetProperty($id, 'Active', true);
        IPS_SetProperty($id, 'BaseTopic', $base);
        IPS_ApplyChanges($id);
    }
    $parent->onPublish = static fn (array $packet) => $reply($packet, $newReady);
    $restart();
    $module->clock++;
    $module->ExpireRequest();
    $before = count($parent->sent);
    $module->ExpireRequest();
    $parent->publish($base . '/bridge/state', ['state' => 'online']);
    $check(
        $state()['extensionConfirmed'] && count($parent->sent) === $before && $module->RequestInterval() === 0,
        'Synchronous confirmation and duplicate online announcements cannot duplicate requests'
    );
    foreach ($parent->sent as $sent) $check(str_ends_with($sent['Topic'], '/request/status') && !$sent['Retain'], 'Restart recovery never stores, activates or switches anything');
    $messages = array_column($module->messages, 'Meldung');
    $check(in_array('Erweiterungsstatus nach Z2M-Anlauf durch frische passende Antwort bestätigt. Weitere automatische Versuche entfallen.', $messages, true), 'Debug explains successful confirmation in German');
    $check(in_array('Automatische Erweiterungsprüfung beendet: keine Bestätigung erhalten oder Verbindung nicht mehr verfügbar. Bitte Verbindung und Erweiterung prüfen.', $messages, true), 'Debug explains exhausted attempts and the next user action');
}

echo "Restart status confirmation checks passed.\n";
