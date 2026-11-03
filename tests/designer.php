<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
IPS\Kernel::reset();
$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ([$root . '/Z2MAutomations', __DIR__ . '/fixtures/MQTTClient', __DIR__ . '/fixtures/Z2MDevice'] as $path) {
    IPS\ModuleLoader::loadSingleModule($path, $library['id']);
}

class DesignerProbe extends Z2MAutomations
{
    public int $clock = 1800000000;
    public array $debug = [];
    public bool $hookAvailable = true;
    public function Seed(): void
    {
        $state = json_decode($this->GetBuffer('TransportState'), true);
        $properties = [];
        foreach (['state', 'display_flag', 'action'] as $property) {
            $properties[$property] = ['property' => $property, 'type' => 'binary', 'access' => 3, 'label' => $property, 'endpoint' => '', 'values' => ['ON', 'OFF']];
        }
        $properties['action'] = array_replace($properties['action'], ['type' => 'enum', 'access' => 1, 'values' => ['single']]);
        $choices = [hash('sha256', 'choice') => ['property' => 'state', 'value' => 'ON']];
        $device = '0x0000000000000001';
        $snapshot = ['scope' => $state['scope'], 'devices' => [$device => ['name' => 'Synthetic lamp <script>', 'triggers' => $choices, 'actions' => $choices,
            'numeric'                                                             => [], 'properties' => $properties]], 'groups' => []];
        $configuration = ['schema_version' => 1, 'revision' => 4, 'automations' => [
            ['id'          => 'synthetic_device', 'name' => 'Synthetic motion rule', 'enabled' => false,
                'triggers' => [['type' => 'device', 'device' => $device, 'property' => 'action', 'operator' => 'eq', 'value' => 'single']], 'conditions' => [],
                'actions'  => [['type' => 'set', 'target' => ['type' => 'device', 'id' => $device], 'values' => ['state' => 'ON'], 'delay_seconds' => 5]]],
            ['id'            => 'synthetic_clock', 'name' => 'Synthetic clock rule', 'enabled' => false,
                'triggers'   => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC'], ['type' => 'time', 'at' => '21:00:00', 'timezone' => 'UTC']],
                'conditions' => [['type' => 'weekday', 'weekdays' => [1, 2, 3, 4, 5], 'timezone' => 'UTC']], 'actions' => [['type' => 'sequence', 'sequence_id' => 'shared']]]],
            'sequences' => [['id' => 'shared', 'name' => 'Synthetic shared', 'actions' => [['type' => 'toggle', 'target' => ['type' => 'device', 'id' => $device], 'property' => 'state']]]]];
        $yaml = json_encode($configuration) . "\n";
        $this->WriteAttributeString('EditorSnapshot', json_encode($snapshot));
        $this->WriteAttributeString('YamlDraft', $yaml);
        $this->WriteAttributeString('EditorDraft', json_encode(['scope' => $state['scope'], 'configuration' => $configuration, 'draftChecksum' => hash('sha256', $yaml)]));
        $this->SetBuffer('EditorWork.rule', json_encode(['context' => ['scope' => $state['scope']], 'dirty' => true, 'input' => ['secret' => 'not-exported']]));
    }

    public function EarlyReceive(): string
    {
        $this->SetBuffer('ModulePropertiesReady', '');
        try {
            return $this->ReceiveData(json_encode(['DataID' => \Z2MAutomations\Z2MProtocol::MQTT_RX, 'PacketType' => 3,
                'Topic'                                     => 'synthetic/z2m/bridge/state', 'Payload' => bin2hex('{"state":"online"}'), 'Retain' => false]));
        } finally {
            $this->SetBuffer('ModulePropertiesReady', '1');
        }
    }
    public function Fingerprint(): string
    {
        return hash('sha256', $this->ReadAttributeString('EditorSnapshot') . $this->ReadAttributeString('EditorDraft') . $this->ReadAttributeString('YamlDraft')
            . $this->GetBuffer('TransportState') . $this->GetBuffer('EditorWork.rule') . $this->GetBuffer('EditorWork.sequence'));
    }
    public function Mismatch(): void
    {
        $this->WriteAttributeString('YamlDraft', 'Different local input');
    }
    public function Hook(string $path, string $method = 'GET', string $token = ''): array
    {
        $_SERVER['REQUEST_URI'] = '/hook/Z2MAutomationsDesigner' . $this->InstanceID . '/' . $path;
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['HTTP_X_Z2MA_READ_TOKEN'] = $token;
        ob_start();
        try {
            $this->ProcessHookData();
            return [http_response_code(), ob_get_contents()];
        } finally {
            ob_end_clean();
        }
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function RegisterHook(string $HookPath): bool
    {
        return $this->hookAvailable;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
}

$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$guid = '{23D57681-8424-48DC-9FF0-25C110C533A9}';
$modules[$guid]['Class'] = DesignerProbe::class;
$registry->setValue(null, $modules);
$id = IPS_CreateInstance($guid);
$parent = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
IPS_ApplyChanges($parent);
IPS_ConnectInstance($id, $parent);
IPS_SetProperty($id, 'BaseTopic', 'synthetic/z2m');
IPS_ApplyChanges($id);
$deviceID = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
IPS_ConnectInstance($deviceID, $parent);
IPS_SetProperty($deviceID, 'IEEE', '0x0000000000000001');
IPS_ApplyChanges($deviceID);
$module = IPS\InstanceManager::getInstanceInterface($id);
$check = static function (bool $value, string $message): void
{ if (!$value) throw new RuntimeException($message); };
$empty = json_decode($module->GetDesignerData(), true);
$check(!$empty['draftAvailable'] && !$empty['catalogAvailable'] && $empty['configuration'] === null && $empty['devices'] === [], 'Missing caches never become example rules');
$module->Seed();
$before = $module->Fingerprint();
$check($module->EarlyReceive() === '' && $module->Fingerprint() === $before, 'Queued MQTT before property initialization cannot touch configuration or transport');
$data = json_decode($module->GetDesignerData(), true);
$check($data['contractVersion'] === 1 && $data['mode'] === 'read' && $data['draftAvailable'] && $data['catalogAvailable'], 'Read-only contract uses scoped existing caches');
$check(count($data['configuration']['automations']) === 2 && count($data['configuration']['sequences']) === 1 && $data['configuration']['automations'][0]['actions'][0]['delay_seconds'] === 5, 'Existing definitions and delay survive export');
$check($data['openFormEdits'] && !str_contains(json_encode($data), 'not-exported'), 'Pending native input is flagged but not exported or overwritten');
$properties = array_column($data['devices'][0]['properties'], null, 'id');
$check($properties['state']['writable'] && !$properties['display_flag']['writable'] && $properties['action']['readable'], 'Symcon actions distinguish switchable properties from display variables');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 1);
$rights = array_column(json_decode($module->GetDesignerData(), true)['devices'][0]['properties'], null, 'id');
$check(!$rights['state']['writable'], 'Revoked variable action is reflected on the next read');
$link = $module->OpenAutomationDesigner();
$token = explode('#', $link)[1];
$check(preg_match('/\A[a-f0-9]{64}\z/D', $token) === 1 && str_starts_with($link, 'link:/hook/'), 'Native button receives relative link and fragment capability');
$check($module->Hook('data')[0] === 403 && $module->Hook('data', 'GET', str_repeat('0', 64))[0] === 403, 'Missing or incorrect capability rejected');
$check($module->Hook('data', 'POST', $token)[0] === 405 && $module->Hook('data?token=' . $token)[0] === 404 && $module->Hook('../module.php')[0] === 404, 'No writes, query tokens or arbitrary files');
$check($module->Hook('data', 'GET', $token)[0] === 200, 'Valid capability reads module data');
$page = $module->Hook('index.html');
$check($page[0] === 200 && str_contains($page[1], 'src="reader.js"') && !str_contains($page[1], 'src="designer.js"') && !str_contains($page[1], $token), 'Shell contains no example script, private bootstrap or token');
$check($module->Hook('designer.js')[0] === 404 && $module->Hook('reader.js')[0] === 200 && $module->Hook('designer.css')[0] === 200, 'Explicitly permitted read-only assets only');
$check($module->Fingerprint() === $before, 'All reads leave input, draft and runtime transport byte-identical');
$module->clock += 901;
$check($module->Hook('data', 'GET', $token)[0] === 403, 'Capability expires at original end time');
$links = [];
for ($i = 0; $i < 5; $i++) $links[] = explode('#', $module->OpenAutomationDesigner())[1];
$check($module->Hook('data', 'GET', $links[0])[0] === 403 && $module->Hook('data', 'GET', $links[4])[0] === 200, 'At most four viewer sessions valid');
$other = IPS\InstanceManager::getInstanceInterface(IPS_CreateInstance($guid));
$check($other->Hook('data', 'GET', $links[4])[0] === 403, 'Capability grants no access to another module');
$module->Mismatch();
$mismatch = json_decode($module->GetDesignerData(), true);
$check($mismatch['draftMismatch'] && !$mismatch['draftAvailable'] && $mismatch['configuration'] === null, 'Changed YAML is not presented as current editor rules');
IPS_SetProperty($id, 'BaseTopic', 'synthetic/other');
$changedScope = json_decode($module->GetDesignerData(), true);
$check($changedScope['module']['baseTopic'] === 'synthetic/z2m', 'Pending configuration is not mistaken for applied gateway scope');
IPS_ApplyChanges($id);
$applied = json_decode($module->GetDesignerData(), true);
$check(!$applied['draftAvailable'] && !$applied['catalogAvailable'] && $module->Hook('data', 'GET', $links[4])[0] === 403, 'ApplyChanges invalidates old-topic caches and sessions');
$module->hookAvailable = false;
$check($module->OpenAutomationDesigner() === '', 'Failed hook registration returns no broken URL');
$debug = implode("\n", $module->debug);
$check(str_contains($debug, 'keine Abfrage') && str_contains($debug, 'Lesezugang') && !str_contains($debug, $token)
    && !str_contains($debug, 'Synthetic motion rule') && !str_contains($debug, 'not-exported'), 'German debug explains actions without exposing names, input or capabilities');
echo "Read-only designer data, rights, isolation, HTTP whitelist and capability lifecycle passed.\n";
