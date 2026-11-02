<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
$check = static function (bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
IPS\Kernel::reset();
$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
IPS\ModuleLoader::loadSingleModule($root . '/Z2MAutomations', $library['id']);
IPS\ModuleLoader::loadSingleModule(__DIR__ . '/fixtures/MQTTClient', $library['id']);
$metadata = json_decode(file_get_contents($root . '/Z2MAutomations/module.json'), true, 512, JSON_THROW_ON_ERROR);
class HealthFeedbackModule extends Z2MAutomations
{
    public int $clock = 1000;
    public int $popups = 0;
    public array $updates = [];
    public array $debugMessages = [];
    public bool $displayFails = false;
    public bool $expireBeforeSend = false;
    public function UpdateFormField(string $Field, string $Parameter, mixed $Value): bool
    {
        if ($this->displayFails && str_starts_with($Field, 'HealthResult')) {
            throw new RuntimeException('Synthetic private display failure');
        }
        $this->updates[$Field][$Parameter] = $Value;
        if ($Field === 'HealthResultPopup' && $Parameter === 'visible' && $Value === true) {
            $this->popups++;
        }
        return true;
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $entry = json_decode($Data, true, 512, JSON_THROW_ON_ERROR);
        $this->debugMessages[] = [$Message, $entry];
        if ($this->expireBeforeSend && ($entry['Bezug']['Gesundheitsprüfung'] ?? '') === 'pending') {
            $this->expireBeforeSend = false;
            $this->clock += 11;
        }
        return true;
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[$metadata['id']]['Class'] = HealthFeedbackModule::class;
$registry->setValue(null, $modules);
$id = IPS_CreateInstance($metadata['id']);
$parentID = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
IPS_ApplyChanges($parentID);
IPS_ConnectInstance($id, $parentID);
IPS_SetProperty($id, 'BaseTopic', 'synthetic/health');
IPS_SetProperty($id, 'Active', true);
IPS_ApplyChanges($id);
$module = IPS\InstanceManager::getInstanceInterface($id);
$parent = IPS\InstanceManager::getInstanceInterface($parentID);
$state = static fn (): array => json_decode($module->GetTransportState(), true, 512, JSON_THROW_ON_ERROR);
$form = json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR);
$fields = $form['actions'];
$script = null;
$popup = null;
while ($fields !== []) {
    $field = array_shift($fields);
    array_push($fields, ...($field['items'] ?? []));
    if (($field['caption'] ?? '') === 'Check Zigbee2MQTT connection') $script = $field['onClick'];
    if (($field['name'] ?? '') === 'HealthResultPopup') $popup = $field;
}
$check(is_string($script) && $popup !== null && !$popup['visible'], 'The connection check has a dedicated initially hidden result popup');
$click = static function () use ($id, $script): void
{ eval($script); };
$response = static function (mixed $healthy = true, bool $retain = false, ?string $transaction = null) use ($parent, $state): void
{
    $parent->publish('synthetic/health/bridge/response/health_check', ['transaction' => $transaction ?? $state()['transaction'], 'status' => 'ok', 'data' => ['healthy' => $healthy]], $retain);
};
$click();
$transaction = $state()['transaction'];
$check($module->popups === 0 && $state()['health'] === 'pending', 'Sending alone never displays success');
$click();
$check(count($parent->sent) === 1 && $state()['transaction'] === $transaction, 'Repeated manual clicks join one pending request');
$response(true, true);
$response(true, false, 'unrelated');
$response('synthetic-private-value');
$check($module->popups === 0, 'Retained, unrelated and malformed replies cannot display success');
$response();
$check($module->popups === 1 && $state()['health'] === 'healthy' && str_contains($module->updates['HealthResultMessage']['caption'], 'successfully'), 'A matching fresh healthy response shows one success popup');
$response();
$check($module->popups === 1, 'A duplicate response does not repeat the popup');
$click();
$response(false);
$check($module->popups === 2 && $state()['health'] === 'unhealthy' && str_contains($module->updates['HealthResultMessage']['caption'], 'reports a problem'), 'A reachable but unhealthy Z2M gets a clear failure message');
$click();
$parent->publish('synthetic/health/bridge/response/health_check', ['transaction' => $state()['transaction'], 'status' => 'error', 'error' => 'synthetic-private-failure']);
$check($module->popups === 3 && $state()['health'] === 'error', 'An explicit response failure shows one error popup');
$click();
$transaction = $state()['transaction'];
$module->clock += 11;
$module->ExpireRequest();
$check($module->popups === 4 && $state()['health'] === 'timeout' && str_contains($module->updates['HealthResultMessage']['caption'], 'did not respond'), 'The native request timer reports a missing response');
$response(true, false, $transaction);
$check($module->popups === 4, 'A late response cannot replace the timeout popup');
$parent->fail = true;
$click();
$check($module->popups === 5 && $state()['health'] === 'error', 'A transport failure is reported immediately');
$parent->fail = false;
$click();
$parent->publish('synthetic/health/bridge/state', ['state' => 'offline']);
$check($module->popups === 6 && $state()['health'] === 'cancelled', 'Bridge offline terminates and reports the pending manual check');
$module->CheckHealth(true);
$response(false);
$module->CheckHealth();
$response();
$check($module->popups === 6, 'Existing automatic and noninteractive health APIs remain silent');
$parent->onPublish = static function (array $packet) use ($parent): void
{
    $health = str_ends_with($packet['Topic'], '/request/health_check');
    $data = $health ? ['healthy' => true] : ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => '0.1.0',
        'session'                                               => '11111111-1111-4111-8111-111111111111', 'state' => 'ready', 'stored_revision' => 0,
        'checksum'                                              => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false];
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
        'transaction'                                                                              => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => (object) $data]);
};
$module->CheckHealth(true);
$check($module->popups === 6 && $state()['extensionConfirmed'], 'A successful automatic health/status chain opens no popup');
$parent->onPublish = null;
$module->CheckHealth(true);
$before = count($parent->sent);
$click();
$response(false);
$check($module->popups === 7 && count($parent->sent) === $before, 'A manual click can request feedback for a check already started automatically');
$parent->replyImmediately = true;
$click();
$check($module->popups === 8 && $state()['health'] === 'healthy', 'A synchronous parent reply cannot race the notification flag');
$module->displayFails = true;
$click();
$check($module->popups === 8 && $state()['health'] === 'healthy', 'A popup display failure cannot invalidate a successful health response');
$module->displayFails = false;
$parent->replyImmediately = false;
$module->expireBeforeSend = true;
$before = count($parent->sent);
$click();
$check($module->popups === 9 && $state()['health'] === 'timeout' && $module->popups === 9 && count($parent->sent) === $before, 'Expiry before publish is committed and reported once without sending an obsolete request');
$click();
IPS_SetProperty($id, 'Active', false);
IPS_ApplyChanges($id);
$check($module->popups === 10 && $state()['health'] === 'cancelled', 'Context cancellation reports the pending manual request once');
$click();
$check($module->popups === 11 && str_contains($module->updates['HealthResultMessage']['caption'], 'could not be started'), 'A disconnected instance gives immediate guidance');
$debug = json_encode($module->debugMessages, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
$check(!str_contains($debug, 'synthetic-private') && str_contains($debug, 'Popup-Rückmeldung'), 'German debug explains popup delivery without foreign errors or values');
echo "Manual connection popup correlation, failures and silent automatic checks passed.\n";
