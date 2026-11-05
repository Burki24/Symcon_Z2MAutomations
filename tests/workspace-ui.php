<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
$root = dirname(__DIR__);
if (!is_file($root . '/libs/WorkspaceState.php')) throw new RuntimeException('A versioned per-system workspace is required.');
require_once $root . '/libs/WorkspaceState.php';

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
};
IPS\Kernel::reset();
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient', 'tests/fixtures/Z2MDevice', 'tests/fixtures/Z2MGroup'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);

class WorkspaceUIProbe extends Z2MAutomations
{
    public array $debugMessages = [];
    public $onCatalogRead = null;
    public string $requestBody = '';
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
    public function ClearViews(): void
    {
        $this->SetBuffer('WorkspaceEditorViews', '');
    }
    public function LocalAttributes(): string
    {
        return $this->ReadAttributeString('YamlDraft') . $this->ReadAttributeString('EditorDraft') . $this->ReadAttributeString('EditorSnapshot');
    }
    public function RawWorkspace(): string
    {
        return $this->ReadAttributeString('SystemWorkspace');
    }
    public function ReplaceWorkspace(string $raw): void
    {
        $this->WriteAttributeString('SystemWorkspace', $raw);
    }
    public function LegacyDigest(): string
    {
        return hash('sha256', json_encode([IPS_GetConfiguration($this->InstanceID), $this->ReadAttributeString('YamlDraft'),
            $this->ReadAttributeString('EditorDraft'), $this->ReadAttributeString('EditorSnapshot'), $this->GetBuffer('DesignerSessions')], JSON_THROW_ON_ERROR));
    }
    protected function DesignerRequestBody(): string
    {
        return $this->requestBody;
    }
    protected function RegisterHook(string $HookPath): bool
    {
        return true;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }
    protected function ReadWorkspaceConnectionCatalog(int $id): array
    {
        $value = parent::ReadWorkspaceConnectionCatalog($id);
        if ($this->onCatalogRead !== null)($this->onCatalogRead)($id);
        return $value;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debugMessages[] = $Data;
        return true;
    }
}

use Z2MAutomations\SystemContract;
use Z2MAutomations\WorkspaceState;
use Z2MAutomations\Z2MProtocol;

$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = WorkspaceUIProbe::class;
$registry->setValue(null, $modules);
$managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
IPS_ApplyChanges($managerID);
$manager = IPS\InstanceManager::getInstanceInterface($managerID);
$legacy = $manager->LegacyDigest();
$parentID = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
IPS_ApplyChanges($parentID);
$parent = IPS\InstanceManager::getInstanceInterface($parentID);
$decode = static fn (string $text): array => json_decode($text, true, 32, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
$overview = static fn (): array => $decode($manager->GetWorkspaceOverview());
$detail = static fn (string $id): array => $decode($manager->GetWorkspaceSystem($id));
$readEntities = static fn (string $source, string $target, string $use = 'action', int $offset = 0, int $limit = 50): array => $decode($manager->GetWorkspaceEntities($source, $target, $use, $offset, $limit));
$initial = $overview();
$check($initial['ok'] && $initial['systems'] === [] && $manager->RawWorkspace() === '', 'Empty overview does not persist or create objects');
$ready = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => '0.1.0', 'session' => '11111111-1111-4111-8111-111111111111',
    'state'                  => 'ready', 'stored_revision' => 0, 'checksum' => null, 'automation_count' => 0, 'storage' => 'empty', 'executing' => false, 'catalog_version' => 1];
$deviceKey = '0x0000000000000001';
$catalogData = $ready + ['devices' => [['id' => $deviceKey, 'name' => 'Same synthetic device', 'triggers' => [['property' => 'state_l2', 'value' => 'ON']],
    'actions'                                => [['property' => 'state_l2', 'value' => 'OFF']], 'properties' => [
        ['property' => 'state_l2', 'type' => 'binary', 'access' => 3, 'label' => 'Channel 2', 'endpoint' => 'l2', 'values' => ['ON', 'OFF']],
        ['property' => 'display_flag', 'type' => 'binary', 'access' => 1, 'label' => 'Sensor', 'endpoint' => '', 'values' => [true, false]]]]],
    'groups' => [['id' => 7, 'name' => 'Same synthetic group']],
    'scenes' => [['target' => ['type' => 'group', 'id' => 7], 'id' => 2, 'name' => 'Synthetic scene', 'control_property' => 'state']]];
$parent->onPublish = static function (array $packet) use ($parent, &$catalogData, $ready): void
{
    $data = str_ends_with($packet['Topic'], '/request/catalog') ? $catalogData : (str_ends_with($packet['Topic'], '/request/health_check') ? ['healthy' => true] : $ready);
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data]);
};
$connections = $systems = $groups = [];
for ($i = 0; $i < 8; $i++) {
    $id = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_ConnectInstance($id, $parentID);
    IPS_SetProperty($id, 'Active', true);
    IPS_SetProperty($id, 'BaseTopic', 'synthetic/system_' . $i);
    IPS_ApplyChanges($id);
    $module = IPS\InstanceManager::getInstanceInterface($id);
    $module->CheckHealth();
    $module->RequestDeviceCatalog();
    $connections[] = $id;
    $systems[] = IPS_GetProperty($id, 'SystemID');
    $device = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
    IPS_ConnectInstance($device, $parentID);
    IPS_SetProperty($device, 'IEEE', $deviceKey);
    IPS_SetProperty($device, 'MQTTBaseTopic', 'synthetic/system_' . $i);
    IPS_ApplyChanges($device);
    $group = IPS_CreateInstance('{11BF3773-E940-469B-9DD7-FB9ACD7199A2}');
    IPS_ConnectInstance($group, $parentID);
    IPS_SetProperty($group, 'MQTTBaseTopic', 'synthetic/system_' . $i);
    IPS_ApplyChanges($group);
    $groups[] = $group;
}
$sent = count($parent->sent);
$configured = $decode($manager->ConfigureWorkspace(json_encode($connections), $initial['fingerprint'], $op()));
$check($configured['ok'] && count($overview()['systems']) === 8, 'Eight independent connections are registered locally');
$before = $manager->RawWorkspace();
$check($decode($manager->ConfigureWorkspace(json_encode([...$connections, $connections[0]]), $overview()['fingerprint'], $op()))['error'] === 'system_limit' && $manager->RawWorkspace() === $before, 'Ninth connection is rejected before writing');
$documents = [];
foreach ($systems as $i => $system) {
    $document = ['schema_version' => 1, 'revision' => 1, 'automations' => [['id' => 'same_rule', 'name' => 'Synthetic rule ' . $i, 'enabled' => false,
        'triggers'                                                               => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
        'actions'                                                                => [['type' => 'sequence', 'sequence_id' => 'same_sequence']]]],
        'sequences' => [['id' => 'same_sequence', 'name' => 'Synthetic sequence', 'actions' => [['type' => 'toggle', 'target' => ['type' => 'group', 'id' => 7], 'property' => 'state']]]]];
    if ($i === 1) $document['mesh_peers'] = ['synthetic/system_0'];
    if ($i === 2) $document['mesh_peers'] = [];
    $yaml = '# Synthetic exact YAML bytes äöü' . "\n" . json_encode($document, JSON_PRETTY_PRINT) . "\n";
    $data = WorkspaceState::Data();
    $data['document'] = ['yaml' => $yaml, 'editorDraft' => ['configuration' => $document, 'baseRevision' => 0, 'baseChecksum' => null, 'draftChecksum' => hash('sha256', $yaml)]];
    $data['selection'] = ['rule' => 'same_rule', 'sequence' => 'same_sequence'];
    $documents[$system] = $data;
    $result = $decode($manager->UpdateWorkspaceSystem($system, 1, $detail($system)['fingerprint'], json_encode($data), $op()));
    $check($result['ok'], 'A system receives its own exact YAML, editor definition and selections');
    $result = $decode($manager->RefreshWorkspaceCatalog($system, 1, $detail($system)['fingerprint'], $op()));
    $check($result['ok'] && $detail($system)['data'] === $data, 'Catalog and native bindings do not change documents, peer grants or selection');
}
$check($manager->LegacyDigest() === $legacy && count($parent->sent) === $sent, 'Workspace operations preserve old editor/config and emit no MQTT');

$a = $systems[0];
$b = $systems[1];
$legacyAttributes = $manager->LocalAttributes();
$form = $manager->GetConfigurationForm();
$check(str_contains($form, 'WorkspaceSystem') && str_contains($form, 'WorkspaceEditorAction'), 'Native form exposes system selection and scoped editor callbacks');
$check(!str_contains($form, 'Z2MA_StartAutomations'), 'No legacy runtime button is routed through a manager');
$dataA = $decode($manager->GetDesignerData());
$check($dataA['workspaceContext']['systemId'] === $a && $dataA['configuration']['automations'][0]['name'] === 'Synthetic rule 0', 'Browser reads only selected document');
$check($dataA['createAllowed'] && $dataA['editAllowed'], 'Local workspace document is editable without a legacy MQTT parent');
$check(count($dataA['devices']) === 4, 'Directed remote actions are available alongside local entities');
$beforeB = $detail($b)['data'];
$check($decode($manager->WorkspaceEditorAction($a, 1, $detail($a)['fingerprint'], 'SelectAutomation', json_encode(['same_rule'])))['result'], 'Native selection opens a system-bound rule');
$selected = $decode($manager->GetDesignerData());
$token = substr(strrchr($manager->OpenAutomationDesigner(), '#'), 1);
[$code, $raw] = $manager->Hook('data', $token);
$check($code === 200 && $decode($raw)['workspaceContext']['systemId'] === $a, 'Authenticated browser data uses selected system');
$headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $a, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $detail($a)['fingerprint']];
$manager->requestBody = json_encode(['requestId' => '11111111-1111-4111-8111-111111111111', 'context' => $selected['createContext'], 'ruleId' => 'same_rule', 'changes' => ['name' => 'Changed only A']]);
[$code, $raw] = $manager->Hook('edit', $token, $headers, 'POST');
$check($code === 200, 'Browser metadata edit succeeds: ' . $raw);
$check($detail($a)['data']['document']['editorDraft']['configuration']['automations'][0]['name'] === 'Changed only A' && $detail($b)['data'] === $beforeB, 'Browser edit preserves another system with identical rule IDs');
$check($manager->LegacyDigest() !== '', 'Legacy state remains accessible');
$check(count($parent->sent) === $sent, 'System editing emits no MQTT');

$fieldsOf = static function (array $form) use (&$fieldsOf): array
{
    $result = [];
    foreach ($form as $value) if (is_array($value)) {
        if (isset($value['name'])) $result[$value['name']] = $value;
        $result = array_replace($result, $fieldsOf($value));
    }
    return $result;
};
$fields = $fieldsOf($decode($manager->GetConfigurationForm()));
$variables = ['id' => $managerID];
foreach ($fields as $name => $field) $variables[$name] = $field['value'] ?? $field['values'] ?? '';
$variables['TimeEditorName'] = 'Native name only A';
$runNative = static function (string $script, array $variables): array
{
    // Execute only generated repository callbacks against the synthetic kernel.
    extract($variables, EXTR_SKIP);
    eval($script);
    return get_defined_vars();
};
$check(isset($fields['TimeUpdateRuleButton']['onClick']), 'Time editor has a save callback');
$runNative($fields['TimeUpdateRuleButton']['onClick'], $variables);
$check($detail($a)['data']['document']['editorDraft']['configuration']['automations'][0]['name'] === 'Native name only A', 'Native multi-call callback captures and commits with the next fingerprint in the same click');
$check(!($detail($a)['data']['work']['rule']['dirty'] ?? false) && $detail($b)['data'] === $beforeB, 'Native commit completes only its system work');
$fields = $fieldsOf($decode($manager->GetConfigurationForm()));
$variables = ['id' => $managerID];
foreach ($fields as $name => $field) $variables[$name] = $field['value'] ?? $field['values'] ?? '';
$variables['TimeEditorName'] = 'Pending name A';
$runNative($fields['TimeEditorName']['onChange'], $variables);
$check($detail($a)['data']['work']['rule']['dirty'], 'Native input is retained as system-specific pending work');
$check(!$manager->ChooseWorkspaceSystem($b, $overview()['fingerprint'], $a, 1, $detail($a)['fingerprint'], ''), 'Switch blocks while native input has not been committed');
$manager->ClearViews();
$fields = $fieldsOf($decode($manager->GetConfigurationForm()));
$check($fields['TimeEditorName']['value'] === 'Pending name A', 'Pending work survives loss of ephemeral browser and native view buffers');
$variables = ['id' => $managerID];
foreach ($fields as $name => $field) $variables[$name] = $field['value'] ?? $field['values'] ?? '';
$runNative($fields['TimeUpdateRuleButton']['onClick'], $variables);
$oldA = $detail($a);
$check($manager->ChooseWorkspaceSystem($b, $overview()['fingerprint'], $a, 1, $detail($a)['fingerprint'], ''), 'Native switches after pending work is committed');
$check(!$decode($manager->WorkspaceEditorAction($a, 1, $oldA['fingerprint'], 'SelectAutomation', '["same_rule"]'))['result'], 'An old native form cannot operate after another system was selected');
$browserB = $decode($manager->GetDesignerData());
$check($browserB['configuration']['automations'][0]['name'] === 'Synthetic rule 1' && $browserB['configuration']['mesh_peers'] === ['synthetic/system_0'], 'Second system restores its independent document and grant');
$headerB = ['HTTP_X_Z2MA_SYSTEM_ID' => $b, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $detail($b)['fingerprint']];
$manager->requestBody = json_encode(['requestId' => '11111111-1111-4111-8111-111111111111', 'context' => $browserB['createContext'], 'ruleId' => 'same_rule', 'changes' => ['name' => 'Native name only B']]);
[$code, $raw] = $manager->Hook('edit', $token, $headerB, 'POST');
$check($code === 200 && $detail($b)['data']['document']['editorDraft']['configuration']['automations'][0]['name'] === 'Native name only B', 'Same request ID on a different system is independent');
$check($detail($b)['data']['document']['editorDraft']['configuration']['mesh_peers'] === ['synthetic/system_0'], 'Browser edit preserves incoming partner permission');
$beforeStale = $manager->RawWorkspace();
[$code] = $manager->Hook('edit', $token, $headerB, 'POST');
$check($code === 409 && $manager->RawWorkspace() === $beforeStale, 'Stale browser fingerprint cannot overwrite a newer document');
$check($manager->LocalAttributes() === $legacyAttributes && count($parent->sent) === $sent, 'All native and browser changes preserve old attributes and send no MQTT');
$check(str_contains(implode(' ', $manager->debugMessages), 'Systementwurf'), 'Debug explains system-bound local changes');

// Creating a disabled local rule must preserve the existing document and directed grants.
$browserB = $decode($manager->GetDesignerData());
$headerB['HTTP_X_Z2MA_FINGERPRINT'] = $detail($b)['fingerprint'];
$manager->requestBody = json_encode(['requestId' => '22222222-2222-4222-8222-222222222222', 'context' => $browserB['createContext'], 'name' => 'Disabled local rule',
    'source'                                     => ['type' => 'time', 'times' => ['07:00:00', '20:30:00'], 'timezone' => 'UTC'],
    'target'                                     => ['device' => $deviceKey, 'property' => 'state_l2', 'value' => 'OFF']]);
[$code, $raw] = $manager->Hook('create', $token, $headerB, 'POST');
$check($code === 200, 'Browser creates a disabled system-local rule: ' . $raw);
$createdB = $detail($b)['data']['document']['editorDraft']['configuration'];
$check(count($createdB['automations']) === 2 && !$createdB['automations'][1]['enabled'] && $createdB['mesh_peers'] === ['synthetic/system_0'], 'Creation preserves grants and other rules');
// Management is bound to one system, even when another system has the same rule ID.
$manageA = $detail($a)['data'];
$browserB = $decode($manager->GetDesignerData());
$headerB['HTTP_X_Z2MA_FINGERPRINT'] = $detail($b)['fingerprint'];
$manager->requestBody = json_encode(['requestId' => '33333333-3333-4333-8333-333333333333', 'context' => $browserB['createContext'], 'operation' => 'copy-rule', 'id' => 'same_rule']);
[$code, $raw] = $manager->Hook('manage', $token, $headerB, 'POST');
$check($code === 200, 'Central browser can copy a rule within its bound system: ' . $raw);
$manageCopy = $decode($raw)['ruleId'];
$check($detail($a)['data'] === $manageA && count($detail($b)['data']['document']['editorDraft']['configuration']['automations']) === 3, 'Copy leaves the other system byte-identical');
$beforeReplay = $manager->RawWorkspace();
$check($manager->Hook('manage', $token, $headerB, 'POST')[0] === 409 && $manager->RawWorkspace() === $beforeReplay, 'Stale central binding requires an explicit refresh');
$headerB['HTTP_X_Z2MA_FINGERPRINT'] = $detail($b)['fingerprint'];
$check($manager->Hook('manage', $token, $headerB, 'POST')[0] === 200 && $manager->RawWorkspace() === $beforeReplay, 'Current central binding confirms a previous receipt without another copy');
$browserB = $decode($manager->GetDesignerData());
$manager->requestBody = json_encode(['requestId' => '44444444-4444-4444-8444-444444444444', 'context' => $browserB['createContext'], 'operation' => 'delete-rule', 'id' => $manageCopy]);
[$code, $raw] = $manager->Hook('manage', $token, $headerB, 'POST');
$check($code === 200 && $detail($a)['data'] === $manageA && count($parent->sent) === $sent, 'Central deletion is local, system-isolated and sends no MQTT');
// Missing bindings affect only that system; the draft is still readable.
$beforeOffline = $detail($systems[7])['data'];
IPS_DisconnectInstance($connections[7]);
$check($manager->ChooseWorkspaceSystem($systems[7], $overview()['fingerprint'], $b, 1, $detail($b)['fingerprint'], ''), 'An unavailable system can still be selected for reading');
$offline = $decode($manager->GetDesignerData());
$check(!$offline['catalogAvailable'] && $offline['configuration']['automations'][0]['name'] === 'Synthetic rule 7', 'Unavailable connection preserves its displayed document');
$check(str_contains($manager->GetConfigurationForm(), 'MQTT-Zuordnung, Basistopic und Besitzer prüfen'), 'Unavailable native system has a clear connection notice');
$check($detail($systems[7])['data'] === $beforeOffline, 'Reading unavailable system never replaces its document');
IPS_ConnectInstance($connections[7], $parentID);
$check($manager->ChooseWorkspaceSystem($a, $overview()['fingerprint'], $systems[7], 1, $detail($systems[7])['fingerprint'], ''), 'Another system remains usable');
// All callbacks, including dynamic item forms, use the same system context.
$item = $decode($manager->WorkspaceEditorAction($a, 1, $detail($a)['fingerprint'], 'GetEditorItemForm', '["TimeEditorActions","",0,"eq",false]'));
$check(is_string($item['result']) && str_contains($item['result'], 'WorkspaceEditorAction') && !str_contains($item['result'], 'Z2MA_ConfigureEditorItem('), 'Dynamic native item callbacks are scoped too');
$check(!$decode($manager->WorkspaceEditorAction($a, 1, $detail($a)['fingerprint'], 'ActivateAutomations', '[]'))['result'], 'Runtime actions are not accepted by the local editor endpoint');
$check(count($parent->sent) === $sent, 'No UI operation sends MQTT');

// Eight rich catalogs are read in bounded pages, not as one giant browser response.
for ($i = 2; $i <= 255; $i++) $catalogData['devices'][] = ['id' => '0x' . str_pad(dechex($i), 16, '0', STR_PAD_LEFT), 'name' => 'Synthetic sensor ' . $i,
    'triggers'                                                  => [], 'actions' => [], 'properties' => [['property' => 'mode', 'type' => 'enum', 'access' => 1, 'label' => 'Mode', 'endpoint' => '',
        'values'                                                                                                     => array_map(static fn (int $n): string => 'Synthetic ' . $n . str_repeat('x', 80), range(1, 32))]]];
foreach ($connections as $i => $connection) {
    $module = IPS\InstanceManager::getInstanceInterface($connection);
    $module->CheckHealth();
    $module->RequestDeviceCatalog();
    $check($decode($manager->RefreshWorkspaceCatalog($systems[$i], 1, $detail($systems[$i])['fingerprint'], $op()))['ok'], 'Refresh a rich per-system catalog');
    if ($i > 0) {
        $data = $detail($systems[$i])['data'];
        $data['document']['editorDraft']['configuration']['mesh_peers'] = ['synthetic/system_0'];
        $data['document']['yaml'] = json_encode($data['document']['editorDraft']['configuration']);
        $data['document']['editorDraft']['draftChecksum'] = hash('sha256', $data['document']['yaml']);
        $check($decode($manager->UpdateWorkspaceSystem($systems[$i], 1, $detail($systems[$i])['fingerprint'], json_encode($data), $op(), true))['ok'], 'Explicit grant for a synthetic scaling target');
    }
}
$largeSent = count($parent->sent);
$large = $decode($manager->GetDesignerData());
$check(count($large['devices']) === 20 && $large['devicePage']['total'] === 2048 && $large['devicePage']['hasMore'], 'Browser starts with twenty of all eight systems device/group entries');
$check(strlen(json_encode($large)) < 1048576, 'First browser data page stays below native buffer size');
$allKeys = array_column($large['devices'], 'key');
while (count($allKeys) < $large['devicePage']['total']) {
    [$code, $raw] = $manager->Hook('devices', $token, ['HTTP_X_Z2MA_SYSTEM_ID' => $a, 'HTTP_X_Z2MA_FINGERPRINT' => $large['workspaceContext']['fingerprint'], 'HTTP_X_Z2MA_WORKSPACE_FINGERPRINT' => $large['workspace']['fingerprint'], 'HTTP_X_Z2MA_OFFSET' => (string) count($allKeys)]);
    $check($code === 200 && strlen($raw) < 1048576, 'Every rich catalog page is bounded and authenticated');
    $page = $decode($raw);
    $check($page['devicePage']['offset'] === count($allKeys), 'Catalog pages continue without gaps');
    array_push($allKeys, ...array_column($page['devices'], 'key'));
}
$check(count(array_unique($allKeys)) === 2048 && count($parent->sent) === $largeSent, 'Paging includes every entity with unique system keys and no MQTT');
$largeForm = $manager->GetConfigurationForm();
$check(strlen($largeForm) < 1048576, 'Native form remains within the Symcon buffer for eight rich catalogs');
echo 'Workspace UI sizes: native ' . strlen($largeForm) . ', first browser page ' . strlen(json_encode($large)) . " bytes.\n";
echo "Workspace UI: scoped native and browser editing passed.\n";
