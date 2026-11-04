<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';

use Z2MAutomations\SystemContract;
use Z2MAutomations\Z2MProtocol;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $condition, string $message): void
{ if (!$condition) throw new RuntimeException($message); };
$decode = static fn (string $text): array => json_decode($text, true, 64, JSON_THROW_ON_ERROR);
$root = dirname(__DIR__);
IPS\Kernel::reset();
$library = $decode(file_get_contents($root . '/library.json'));
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient', 'tests/fixtures/MQTTServer'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);

class DiscoveryManagerProbe extends Z2MAutomations
{
    public array $debug = [];
    public array $updates = [];
    public function RawWorkspace(): string
    {
        return $this->ReadAttributeString('SystemWorkspace');
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
        $this->debug[] = $Data;
        return true;
    }
}

class DiscoveryConnectionProbe extends Z2MConnection
{
    public function FixtureOwner(int $owner): void
    {
        $marker = $owner === 0 ? [] : ['version' => 1, 'role' => 'connection', 'owner' => $owner, 'operation' => str_repeat('a', 32),
            'systemId'                           => $this->ReadPropertyString('SystemID'), 'generation' => $this->ReadPropertyInteger('Generation'), 'baseTopic' => $this->ReadPropertyString('BaseTopic')];
        $this->WriteAttributeString('SystemOwnership', json_encode($marker));
    }
}

$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = DiscoveryManagerProbe::class;
$modules[SystemContract::CONNECTION_MODULE]['Class'] = DiscoveryConnectionProbe::class;
$registry->setValue(null, $modules);
$managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
$manager = IPS\InstanceManager::getInstanceInterface($managerID);
$parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
IPS_SetName($parentID, 'Synthetic MQTT Client');
IPS_ApplyChanges($parentID);
$otherParent = IPS_CreateInstance(Z2MProtocol::MQTT_SERVER);
IPS_SetName($otherParent, 'Synthetic MQTT Server');
IPS_ApplyChanges($otherParent);
$connections = [];
foreach ([$parentID, $otherParent] as $i => $parent) {
    $id = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_SetName($id, 'Synthetic connection ' . $i);
    IPS_ConnectInstance($id, $parent);
    IPS_SetProperty($id, 'BaseTopic', 'synthetic/system_' . $i);
    IPS_ApplyChanges($id);
    $connections[] = $id;
}
$fields = static function (array $form) use (&$fields): array
{
    $result = [];
    foreach ($form as $value) if (is_array($value)) {
        if (isset($value['name'])) $result[$value['name']] = $value;
        $result = array_replace($result, $fields($value));
    }
    return $result;
};
$form = $fields($decode($manager->GetConfigurationForm()));
$list = $form['WorkspaceConnections'];
$check(count($list['values']) === 2, 'Both existing device-type Z2M connections must appear automatically before any assignment.');
$check(!$list['add'] && !$list['delete'] && $list['columns'][0]['edit']['type'] === 'CheckBox', 'Connections are selected by checkboxes instead of manual object picking.');
$check($list['columns'][0]['save'] && $list['columns'][1]['save'], 'Checkbox state and hidden stable instance IDs participate in the native list serialization.');
$check($manager->RawWorkspace() === '' && IPS_GetInstance($managerID)['ConnectionID'] === 0, 'Opening discovery does not assign systems or MQTT parents.');
$byID = static fn (array $view): array => array_column($view['WorkspaceConnections']['values'], null, 'Connection');
$rows = $byID($form);
$check($rows[$connections[0]]['Name'] === 'Synthetic connection 0 (#' . $connections[0] . ')' && $rows[$connections[0]]['Topic'] === 'synthetic/system_0'
    && str_contains($rows[$connections[0]]['MQTT'], 'Synthetic MQTT Client') && str_contains($rows[$connections[1]]['MQTT'], 'Synthetic MQTT Server'), 'Names, topics, and exact MQTT assignments are displayed.');
$check(!$rows[$connections[0]]['Use'] && $rows[$connections[0]]['editable'] && IPS_GetInstance($connections[0])['ModuleInfo']['ModuleType'] === 3, 'Device-type connections are offered without an automatic checkmark.');
$fresh = static fn (): array => $fields($decode($manager->GetConfigurationForm()));
$apply = static fn (array $ids, array $view): bool => $manager->ApplyWorkspaceConnections(json_encode($ids), $view['WorkspaceFingerprint']['value'], $view['WorkspaceCandidatesFingerprint']['value']);
$check(!$apply([], $form) && str_contains($manager->updates['WorkspaceNotice']['caption'], 'mindestens eine'), 'Empty selection has a clear message and writes nothing.');
$variables = ['id'         => $managerID, 'WorkspaceFingerprint' => $form['WorkspaceFingerprint']['value'], 'WorkspaceCandidatesFingerprint' => $form['WorkspaceCandidatesFingerprint']['value'],
    'WorkspaceConnections' => new ArrayIterator(array_map(static fn (array $row): object => (object) array_replace($row, ['Use' => $row['Connection'] === $connections[0]]), $list['values']))];
$runNative = static function (string $script, array $variables): void
{
    extract($variables, EXTR_SKIP);
    eval($script);
};
$runNative($form['WorkspaceApplyConnections']['onClick'], $variables);
$overview = $decode($manager->GetWorkspaceOverview());
$check(count($overview['systems']) === 1 && $overview['systems'][0]['binding']['connectionInstance'] === $connections[0], 'The generated native callback applies only checked rows, including native object rows.');
$system = $overview['systems'][0]['systemId'];
$detail = $decode($manager->GetWorkspaceSystem($system));
$configuration = ['schema_version' => 1, 'revision' => 1, 'automations' => [['id' => 'synthetic_rule', 'name' => 'SYNTHETIC_DRAFT', 'enabled' => false,
    'triggers'                                                                    => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [], 'actions' => [['type' => 'toggle', 'target' => ['type' => 'group', 'id' => 7], 'property' => 'state']]]]];
$yaml = json_encode($configuration);
$data = $detail['data'];
$data['document'] = ['yaml' => $yaml, 'editorDraft' => ['configuration' => $configuration, 'baseRevision' => 0, 'baseChecksum' => null, 'draftChecksum' => hash('sha256', $yaml)]];
$check($decode($manager->UpdateWorkspaceSystem($system, 1, $detail['fingerprint'], json_encode($data), bin2hex(random_bytes(16))))['ok'], 'Prepare a synthetic existing draft.');
$form = $fresh();
$rows = $byID($form);
$check($rows[$connections[0]]['Use'] && !$rows[$connections[0]]['editable'] && !$rows[$connections[1]]['Use'], 'Existing assignments are prechecked and cannot be casually unchecked.');
$check($apply($connections, $form), 'Add a second checked connection.');
$check($decode($manager->GetWorkspaceSystem($system))['data'] === $data, 'Adding a system preserves the exact existing draft.');
$before = $manager->RawWorkspace();
$check(!$apply([$connections[1]], $fresh()) && $manager->RawWorkspace() === $before, 'Forged unchecking cannot remove an existing system.');

$third = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
IPS_ConnectInstance($third, $parentID);
IPS_SetProperty($third, 'BaseTopic', 'synthetic/third');
IPS_ApplyChanges($third);
$thirdModule = IPS\InstanceManager::getInstanceInterface($third);
$foreign = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
$view = $fresh();
$thirdModule->FixtureOwner($foreign);
$check(!$apply([...$connections, $third], $view) && $manager->RawWorkspace() === $before, 'An ownership change after opening invalidates the captured choices.');
$view = $fresh();
$rows = $byID($view);
$check(!$rows[$third]['Use'] && !$rows[$third]['editable'] && str_contains($rows[$third]['Status'], 'anderen Zentrale'), 'Foreign-owned connections remain visible with an explanation and disabled selection.');
$check(!$apply([...$connections, $third], $view) && str_contains($manager->updates['WorkspaceNotice']['caption'], 'anderen Automationszentrale'), 'Forged foreign selection is rejected server-side.');
$thirdModule->FixtureOwner(0);
$view = $fresh();
IPS_SetProperty($third, 'BaseTopic', 'synthetic/changed');
IPS_ApplyChanges($third);
$check(!$apply([...$connections, $third], $view) && $manager->RawWorkspace() === $before, 'A changed topic cannot be silently selected from an older form.');
IPS_DisconnectInstance($third);
$view = $fresh();
$rows = $byID($view);
$check(!$rows[$third]['editable'] && str_contains($rows[$third]['MQTT'], 'Keine MQTT'), 'Unconfigured connections are shown with actionable information.');
$check($apply($connections, $view) && $decode($manager->GetWorkspaceSystem($system))['data'] === $data, 'An unrelated invalid connection does not block valid selected systems.');

$nine = $connections;
for ($i = 0; $i < 7; $i++) {
    $id = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_ConnectInstance($id, $parentID);
    IPS_SetProperty($id, 'BaseTopic', 'synthetic/extra_' . $i);
    IPS_ApplyChanges($id);
    $nine[] = $id;
}
$before = $manager->RawWorkspace();
$check(!$apply($nine, $fresh()) && $manager->RawWorkspace() === $before && str_contains($manager->updates['WorkspaceNotice']['caption'], 'höchstens acht'), 'Selecting nine systems is rejected without changing existing data.');
foreach (IPS_GetChildrenIDs($connections[0]) as $variable) IPS_DeleteVariable($variable);
IPS_DeleteInstance($connections[0]);
$view = $fresh();
$rows = $byID($view);
$check($rows[$connections[0]]['Use'] && !$rows[$connections[0]]['editable'] && str_contains($rows[$connections[0]]['Status'], 'fehlt'), 'A deleted assigned connection remains visible and prechecked instead of vanishing from the selection.');
$check(!$apply($connections, $view) && $manager->RawWorkspace() === $before, 'Missing assigned connections cannot discard their saved drafts.');
$check(IPS_GetInstance($managerID)['ConnectionID'] === 0 && IPS_GetInstance($connections[1])['ConnectionID'] === $otherParent, 'Parent relationships remain unchanged.');
$check(IPS\InstanceManager::getInstanceInterface($parentID)->sent === [] && IPS\InstanceManager::getInstanceInterface($otherParent)->sent === [], 'Discovery and applying checkboxes emit no MQTT.');
$debug = implode(' ', $manager->debug);
$check(str_contains($debug, 'Häkchenauswahl') && str_contains($debug, 'Angehakte') && !str_contains($debug, 'SYNTHETIC_DRAFT') && !str_contains($debug, 'Synthetic MQTT'), 'Debug explains discovery and selection without logging private draft or display names.');

echo "Connection discovery and checkbox setup passed.\n";
