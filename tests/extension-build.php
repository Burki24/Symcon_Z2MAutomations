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
class BuildDisplayModule extends Z2MAutomations
{
    public array $debugMessages = [];
    public array $updates = [];
    public function SeedDraft(string $draft): void
    {
        $this->WriteAttributeString('YamlDraft', $draft);
    }
    public function UpdateFormField(string $Field, string $Parameter, mixed $Value): bool
    {
        $this->updates[$Field][$Parameter] = $Value;
        return true;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debugMessages[] = json_decode($Data, true, 512, JSON_THROW_ON_ERROR);
        return true;
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[$metadata['id']]['Class'] = BuildDisplayModule::class;
$registry->setValue(null, $modules);
$id = IPS_CreateInstance($metadata['id']);
$parentID = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
IPS_ApplyChanges($parentID);
IPS_ConnectInstance($id, $parentID);
IPS_SetProperty($id, 'BaseTopic', 'synthetic/build');
IPS_SetProperty($id, 'Active', true);
IPS_ApplyChanges($id);
$module = IPS\InstanceManager::getInstanceInterface($id);
$parent = IPS\InstanceManager::getInstanceInterface($parentID);
$module->SeedDraft('synthetic-private-build-draft');
$fields = static function (array $items) use (&$fields): array
{
    $result = [];
    foreach ($items as $item) {
        if (isset($item['name'])) {
            $result[$item['name']] = $item;
        }
        $result += $fields($item['items'] ?? []);
    }
    return $result;
};
$form = static fn (): array => $fields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$initial = $form();
$check(isset($initial['InstalledExtensionInfo'], $initial['BundledExtensionInfo'], $initial['ExtensionUpdateInfo']), 'Setup displays installed build, bundled build and comparison');
$check(str_contains($initial['ExtensionUpdateInfo']['caption'], 'Not yet checked'), 'Missing confirmation never claims the extension is current');
$build = Z2MAutomations\ExtensionContract::BundledBuild();
$check(is_string($build) && preg_match('/\A[a-f0-9]{64}\z/', $build) === 1, 'PHP reads the generated artifact identity');
$version = Z2MAutomations\ExtensionContract::BundledVersion();
$versionMetadata = json_decode(file_get_contents($root . '/libs/z2m/version.json'), true, 16, JSON_THROW_ON_ERROR);
$check($version === $versionMetadata['version'] && str_contains($initial['BundledExtensionInfo']['caption'], $version), 'Bundled display reads the generated extension version, independently of the library');
$check(Z2MAutomations\ExtensionContract::VersionFromHeader("export const EXTENSION_VERSION = '01.2.3';") === null, 'Invalid header versions are rejected');
$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => $version, 'extension_build' => $build,
    'session'                => '11111111-1111-4111-8111-111111111111', 'state' => 'ready', 'stored_revision' => 0,
    'checksum'               => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false];
$parent->onPublish = static function (array $packet) use ($parent, &$ready): void
{
    $health = str_ends_with($packet['Topic'], '/request/health_check');
    if (!$health && !str_ends_with($packet['Topic'], '/request/status')) {
        throw new RuntimeException('Build comparison must never send a write command');
    }
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1,
        'transaction'                                                                              => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => (object) ($health ? ['healthy' => true] : $ready)]);
};
$module->RefreshStatus();
$current = $form();
$check(str_contains($current['InstalledExtensionInfo']['caption'], $version), 'Fresh receiver confirmation displays its actual version');
foreach ([null, 123, '', '01.2.3', '1.2', '1.2.3-extra', '<untrusted>'] as $invalid) {
    $invalidStatus = json_decode(json_encode(array_replace($ready, ['extension_version' => $invalid]), JSON_THROW_ON_ERROR));
    $check(Z2MAutomations\ExtensionContract::Status($invalidStatus) === null, 'Malformed extension versions cannot confirm the receiver');
}
$oldStatus = json_decode(json_encode(array_replace($ready, ['extension_version' => '0.1.0']), JSON_THROW_ON_ERROR));
$check(Z2MAutomations\ExtensionContract::Status($oldStatus)['version'] === '0.1.0', 'An older compatible extension remains readable for an explicit update');
$oldStatus->protocol_version = 2;
$check(Z2MAutomations\ExtensionContract::Status($oldStatus) === null, 'Release version changes never relax the MQTT protocol contract');
$check(str_contains($current['ExtensionUpdateInfo']['caption'], 'Up to date')
    && str_contains($current['InstalledExtensionInfo']['caption'], substr($build, 0, 12))
    && str_contains($current['BundledExtensionInfo']['caption'], substr($build, 0, 12)), 'Automatic confirmation displays matching builds without installation');
$ready['extension_build'] = str_repeat($build[0] === 'a' ? 'b' : 'a', 64);
$module->RequestExtensionStatus();
$different = $form();
$check(str_contains($different['ExtensionUpdateInfo']['caption'], 'Update available')
    && $module->updates['ExtensionUpdateInfo']['caption'] === $different['ExtensionUpdateInfo']['caption'], 'Fresh status updates the open form when the installed build differs');
$before = count($parent->sent);
$check($form()['ExtensionUpdateInfo']['caption'] === $different['ExtensionUpdateInfo']['caption'] && count($parent->sent) === $before, 'Reopening renders the confirmed comparison without synchronous MQTT');
$parent->publish('synthetic/build/symcon/automations/response/status', ['protocol_version' => 1, 'transaction' => 'synthetic-unrelated', 'status' => 'ok',
    'data'                                                                                 => (object) array_replace($ready, ['extension_build' => $build])], true);
$check($form()['ExtensionUpdateInfo']['caption'] === $different['ExtensionUpdateInfo']['caption'], 'An unrelated retained response cannot replace the confirmed build comparison');
unset($ready['extension_build']);
$module->RequestExtensionStatus();
$legacy = $form();
$check(str_contains($legacy['InstalledExtensionInfo']['caption'], 'without build identifier')
    && str_contains($legacy['ExtensionUpdateInfo']['caption'], 'cannot be compared'), 'Older extensions remain readable and explain why an update is recommended');
foreach ([null, 123, '<untrusted>', str_repeat('a', 65)] as $invalid) {
    $ready['extension_build'] = $invalid;
    $check(Z2MAutomations\ExtensionContract::Status(json_decode(json_encode($ready, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR)) === null, 'Invalid build identifiers are rejected at the protocol boundary');
}
$module->RequestExtensionStatus();
$check(str_contains($form()['ExtensionUpdateInfo']['caption'], 'Not yet checked'), 'Rejected status cannot leave a stale current/update claim');
$ready['extension_build'] = $build;
$module->RequestExtensionStatus();
$beforeDebug = count(array_filter($module->debugMessages, static fn (array $entry): bool => isset($entry['Bezug']['Vergleich'])));
$form();
$form();
$check(count(array_filter($module->debugMessages, static fn (array $entry): bool => isset($entry['Bezug']['Vergleich']))) === $beforeDebug, 'Unchanged build comparisons are not repeatedly logged');
IPS_SetProperty($id, 'BaseTopic', 'synthetic/other');
IPS_ApplyChanges($id);
$check(str_contains($form()['InstalledExtensionInfo']['caption'], 'Not yet confirmed'), 'Changing topic clears the installed build comparison');
$check($form()['YamlInput']['value'] === 'synthetic-private-build-draft'
    && !str_contains(json_encode($module->debugMessages, JSON_THROW_ON_ERROR), 'synthetic-private-build-draft'), 'Read-only build comparisons preserve private drafts without logging them');
$check(
    count(array_filter($module->debugMessages, static fn (array $entry): bool => ($entry['Bezug']['Vergleich'] ?? '') === 'different')) > 0
    && count(array_filter($module->debugMessages, static fn (array $entry): bool => ($entry['Bezug']['Vergleich'] ?? '') === 'current')) > 0,
    'German debug records explain current and different build decisions'
);
echo "Extension build display and read-only comparison passed.\n";
