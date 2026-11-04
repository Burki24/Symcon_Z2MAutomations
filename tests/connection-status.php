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
IPS\ModuleLoader::loadSingleModule($root . '/Z2MConnection', $library['id']);
IPS\ModuleLoader::loadSingleModule(__DIR__ . '/fixtures/MQTTClient', $library['id']);

class GermanStatusConnection extends Z2MConnection
{
    public int $clock = 1000;
    public array $messages = [];
    public function Translate(string $Text): string
    {
        $locale = json_decode(file_get_contents(dirname(__DIR__) . '/Z2MConnection/locale.json'), true, 512, JSON_THROW_ON_ERROR);
        return $locale['translations']['de'][$Text] ?? $Text;
    }
    public function SimulatePreviousInstallationDisplay(): void
    {
        $state = json_decode($this->GetBuffer('TransportState'), true, 32, JSON_THROW_ON_ERROR);
        $state['extensionInstalled'] = false;
        $this->SetBuffer('TransportState', json_encode($state, JSON_THROW_ON_ERROR));
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
$guid = '{BF8E1981-7C41-4F2B-BCBB-1744A5D7D5FC}';
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[$guid]['Class'] = GermanStatusConnection::class;
$registry->setValue(null, $modules);
$id = IPS_CreateInstance($guid);
$parentID = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
IPS_ApplyChanges($parentID);
IPS_ConnectInstance($id, $parentID);
IPS_SetProperty($id, 'BaseTopic', 'synthetic/status');
IPS_SetProperty($id, 'Active', true);
IPS_ApplyChanges($id);
$connection = IPS\InstanceManager::getInstanceInterface($id);
$parent = IPS\InstanceManager::getInstanceInterface($parentID);
$value = static fn (string $ident): mixed => GetValue(IPS_GetObjectIDByIdent($ident, $id));
$state = static fn (): array => json_decode($connection->GetTransportState(), true, 32, JSON_THROW_ON_ERROR);
$check($value('HealthStatus') === 'Noch nicht geprüft', 'Initial health status must be German');
$tx = $connection->CheckHealth();
$check($value('HealthStatus') === 'Antwort von Z2M steht noch aus', 'Pending health status must be German');
$parent->publish('synthetic/status/bridge/response/health_check', ['transaction' => $tx, 'status' => 'ok', 'data' => ['healthy' => true]]);
$check($value('HealthStatus') === 'Funktionsfähig' && $state()['health'] === 'healthy', 'Healthy is translated only for display; protocol state stays unchanged');
$tx = $connection->CheckHealth();
$parent->publish('synthetic/status/bridge/response/health_check', ['transaction' => $tx, 'status' => 'ok', 'data' => ['healthy' => false]]);
$check($value('HealthStatus') === 'Verbindungsprüfung fehlgeschlagen', 'Unhealthy status must be German');
$tx = $connection->CheckHealth();
$parent->publish('synthetic/status/bridge/response/health_check', ['transaction' => $tx, 'status' => 'error']);
$check($value('HealthStatus') === 'Fehler', 'Error status must be German');

$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => '0.1.0', 'session' => '11111111-1111-4111-8111-111111111111',
    'state'                  => 'ready', 'stored_revision' => 0, 'checksum' => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false];
$parent->publish('synthetic/status/symcon/automations/status', $ready, true);
$check(!$value('ExtensionInstalled') && !$value('ExtensionReady'), 'Passive status does not become a fresh installation confirmation');
$tx = $connection->RequestExtensionStatus();
$reply = ['protocol_version' => 1, 'transaction' => $tx, 'status' => 'ok', 'data' => $ready];
$topic = 'synthetic/status/symcon/automations/response/status';
$parent->publish($topic, $reply, true);
$parent->publish($topic, array_replace($reply, ['transaction' => 'wrong-transaction']));
$check(!$value('ExtensionInstalled') && !$value('ExtensionReady'), 'Retained and unrelated responses must not mark an extension installed');
$parent->publish($topic, array_replace($reply, ['protocol_version' => 2]));
$check(!$value('ExtensionInstalled'), 'Invalid protocol cannot confirm installation');
$reply['transaction'] = $connection->RequestExtensionStatus();
$parent->publish($topic, $reply);
$check($value('ExtensionReady') && $value('ExtensionInstalled') && $state()['extensionInstalled'], 'Confirmed ready receiver must be marked installed even without bridge/extensions');
$before = count($parent->sent);
$connection->SimulatePreviousInstallationDisplay();
$check($state()['extensionInstalled'] && $value('ExtensionInstalled') && count($parent->sent) === $before, 'Previously cached confirmation repairs display locally without another MQTT request');
$count = count($connection->messages);
$state();
$messages = array_column(array_slice($connection->messages, $count), 'Meldung');
$check(!in_array('Bestätigter Empfängerstatus belegt die installierte Z2M-Erweiterung; Installationsanzeige berichtigt.', $messages, true), 'Unchanged reads do not repeat the installation correction');
$parent->publish('synthetic/status/bridge/extensions', []);
$check(!$value('ExtensionInstalled') && !$value('ExtensionReady'), 'Explicit removal clears installation and readiness');
$reply['transaction'] = $connection->RequestExtensionStatus();
$parent->publish($topic, $reply);
IPS_SetProperty($id, 'BaseTopic', 'synthetic/other');
IPS_ApplyChanges($id);
$check(!$value('ExtensionInstalled') && !$value('ExtensionReady'), 'Topic change cannot carry confirmation to another system');
$connection->CheckHealth();
$connection->clock += 10;
$connection->ExpireRequest();
$check($value('HealthStatus') === 'Keine rechtzeitige Antwort', 'Timeout remains German');
$connection->CheckHealth();
IPS_SetProperty($id, 'Active', false);
IPS_ApplyChanges($id);
$check($value('HealthStatus') === 'Abgebrochen', 'Cancelled health remains German');
$debug = array_column($connection->messages, 'Meldung');
$check(in_array('Bestätigter Empfängerstatus belegt die installierte Z2M-Erweiterung; Installationsanzeige berichtigt.', $debug, true)
    && in_array('Verbindungsprüfung erfolgreich: Zigbee2MQTT ist erreichbar und meldet keine Probleme.', $debug, true), 'Native debug explains installation recognition and health success in German');
echo "Connection installation indication, German health display and safe confirmation boundaries passed.\n";
