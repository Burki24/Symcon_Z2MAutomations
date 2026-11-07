<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
$root = dirname(__DIR__);
IPS\Kernel::reset();
$library = json_decode(file_get_contents($root . '/library.json'), true, 32, JSON_THROW_ON_ERROR);
foreach (['Z2MAutomations', 'Z2MConnection', 'tests/fixtures/MQTTClient'] as $folder) IPS\ModuleLoader::loadSingleModule($root . '/' . $folder, $library['id']);
if (!method_exists(Z2MAutomations::class, 'RequestWorkspaceInstallation') || !method_exists(Z2MConnection::class, 'RequestOwnedInstallation')) throw new RuntimeException('A system-bound, owner-authorized central installation entry is required.');

use Z2MAutomations\ExtensionContract;
use Z2MAutomations\SystemContract;
use Z2MAutomations\Z2MProtocol;

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool
{
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $ok, string $message): void
{ if (!$ok) throw new RuntimeException($message); };
$decode = static fn (string $text): array => json_decode($text, true, 64, JSON_THROW_ON_ERROR);
$op = static fn (): string => bin2hex(random_bytes(16));
class InstallationManagerProbe extends Z2MAutomations
{
    public int $clock = 1800000000;
    public array $debug = [];
    public ?string $artifact = null;
    public string $requestBody = '';
    public string $storage;
    public array $formUpdates = [];
    public int $reloads = 0;
    public function ConcurrentAuthority(string $operation): string
    {
        $property = new ReflectionProperty(Z2MAutomations::class, 'ownershipEntry');
        $old = $property->getValue($this);
        $property->setValue($this, false);
        try {
            return $this->GetWorkspaceInstallationAuthority($operation);
        }
        finally {
            $property->setValue($this, $old);
        }
    }
    public function Raw(): string
    {
        return $this->ReadAttributeString('SystemWorkspace');
    }
    public function NativeFormSelection(string $system): void
    {
        // Fixture setup: public navigation intentionally blocks the dirty inputs below.
        // Only select the form to exercise; retain all original input and document data.
        $state = json_decode($this->Raw(), true, 64, JSON_THROW_ON_ERROR);
        if (!isset($state['systems'][$system])) throw new RuntimeException('Missing native fixture system');
        $state['selectedSystem'] = $system;
        $this->WriteAttributeString('SystemWorkspace', json_encode($state, JSON_THROW_ON_ERROR));
    }
    public function Hook(string $path, string $token, array $headers, string $method = 'POST'): array
    {
        $_SERVER = $headers + ['REQUEST_URI' => '/hook/Z2MAutomationsDesigner' . $this->InstanceID . '/' . $path, 'REQUEST_METHOD' => $method,
            'HTTP_X_Z2MA_READ_TOKEN'         => $token, 'HTTP_HOST' => '127.0.0.1:3777', 'HTTP_ORIGIN' => 'http://127.0.0.1:3777', 'CONTENT_TYPE' => 'application/json'];
        ob_start();
        try {
            $this->ProcessHookData();
            return [http_response_code(), ob_get_contents()];
        } finally {
            ob_end_clean();
        }
    }
    public function LoseAccess(): void
    {
        $sessions = json_decode($this->GetBuffer('DesignerSessions'), true);
        foreach ($sessions as &$s) unset($s['installation']);
        $this->SetBuffer('DesignerSessions', json_encode($sessions));
    }
    protected function UpdateFormField(string $Field, string $Parameter, mixed $Value): bool
    {
        $this->formUpdates[$Field][$Parameter] = $Value;
        return parent::UpdateFormField($Field, $Parameter, $Value);
    }
    protected function ReloadForm(): bool
    {
        $this->reloads++;
        return true;
    }
    protected function BundledExtensionCode(): string
    {
        return $this->artifact ?? parent::BundledExtensionCode();
    }
    protected function DesignerRequestBody(): string
    {
        return $this->requestBody;
    }
    protected function RegisterHook(string $path): bool
    {
        return true;
    }
    protected function MigrationStorageDirectory(): string
    {
        return $this->storage;
    }
    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true);
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
}
class InstallationConnectionProbe extends Z2MConnection
{
    public bool $badHealthReply = false;
    public int $clock = 1800000000;
    public ?string $artifact = null;
    public array $debug = [];
    public function CheckHealth(bool $RefreshExtension = false): string
    {
        return $this->badHealthReply ? '{"ok":false,"error":"instance_busy"}' : parent::CheckHealth($RefreshExtension);
    }
    public function LoseWorkflow(): void
    {
        $this->SetBuffer('TransportState', '');
    }
    protected function BundledExtensionCode(): string
    {
        return $this->artifact ?? parent::BundledExtensionCode();
    }
    protected function Now(): int
    {
        return $this->clock;
    }
    protected function SendDebug(string $Message, string $Data, int $Format): bool
    {
        $this->debug[] = $Message . $Data;
        return true;
    }
}
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[SystemContract::SOURCE_MODULE]['Class'] = InstallationManagerProbe::class;
$modules[SystemContract::CONNECTION_MODULE]['Class'] = InstallationConnectionProbe::class;
$registry->setValue(null, $modules);
$managerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
$manager = IPS\InstanceManager::getInstanceInterface($managerID);
$manager->storage = sys_get_temp_dir() . '/z2ma-installation-' . $op();
IPS_ApplyChanges($managerID);
$parents = $parentIDs = [];
for ($i = 0; $i < 2; $i++) {
    $p = IPS_CreateInstance(Z2MProtocol::MQTT_CLIENT);
    IPS_ApplyChanges($p);
    $parentIDs[] = $p;
    $parents[] = IPS\InstanceManager::getInstanceInterface($p);
}
$connections = $metadata = $documents = [];
$mode = 'auto';
$held = [];
$uploads = [];
$catalogReads = [];
$bundle = file_get_contents($root . '/libs/z2m/' . ExtensionContract::NAME);
$catalogDevices = [['id' => '0x0000000000000001', 'name' => 'PRIVATE_DEVICE', 'triggers' => [['property' => 'action', 'value' => 'single']], 'actions' => [],
    'properties'         => [['property' => 'action', 'type' => 'enum', 'access' => 1, 'label' => 'Synthetic event', 'endpoint' => '', 'values' => ['single']]]]];
for ($i = 0; $i < 8; $i++) {
    $topic = 'synthetic/install_' . $i;
    $ci = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_ConnectInstance($ci, $parentIDs[$i % 2]);
    IPS_SetProperty($ci, 'Active', true);
    IPS_SetProperty($ci, 'BaseTopic', $topic);
    IPS_ApplyChanges($ci);
    $connections[] = $ci;
    $cfg = ['schema_version' => 1, 'revision' => 3, 'automations' => [], 'mesh_peers' => []];
    $yaml = '# PRIVATE_EXACT_YAML' . "\n" . json_encode($cfg) . "\n";
    $documents[$topic] = ['yaml' => $yaml, 'configuration' => $cfg];
    $metadata[$topic] = ['protocol_version' => 1, 'schema_version' => 1, 'extension_version' => '0.1.0', 'extension_build' => ExtensionContract::BundledBuild(),
        'session'                           => sprintf('11111111-1111-4111-8111-%012d', $i + 1), 'state' => 'ready', 'stored_revision' => 3, 'checksum' => hash('sha256', $yaml),
        'automation_count'                  => 0, 'storage' => 'ok', 'executing' => true, 'runtime_version' => 1, 'active_revision' => 3, 'active_checksum' => hash('sha256', $yaml),
        'enabled_count'                     => 0, 'runtime_error' => '', 'resume_version' => 1, 'resume_enabled' => true, 'resume_status' => 'armed', 'resume_error' => '', 'catalog_version' => 1];
    $parents[$i % 2]->publish($topic . '/bridge/state', ['state' => 'online']);
}
foreach ($parents as $parent) $parent->onPublish = static function (array $packet) use ($parent, $manager, $decode, &$metadata, &$documents, &$mode, &$held, &$uploads, &$catalogReads, $catalogDevices, $bundle, $check): void
{
    $base = str_contains($packet['Topic'], '/bridge/request/') ? strstr($packet['Topic'], '/bridge/request/', true) : strstr($packet['Topic'], '/symcon/automations/request/', true);
    $kind = substr($packet['Topic'], strrpos($packet['Topic'], '/') + 1);
    $check(in_array($kind, ['health_check', 'status', 'editor', 'catalog', 'save'], true), 'Installation never writes YAML, activates, stops or switches devices');
    if (($mode === 'hold_health' && $kind === 'health_check') || ($mode === 'hold_upload' && $kind === 'save')
        || ($mode === 'hold_install_status' && $kind === 'status')) {
        $held[] = [$parent, $packet];
        return;
    }
    $data = $kind === 'health_check' ? ['healthy' => true] : $metadata[$base];
    if ($kind === 'catalog') {
        $catalogReads[] = $base;
        if ($mode === 'hold_catalog') {
            $held[] = [$parent, $packet];
            return;
        }
        if ($mode === 'reject_catalog') {
            $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['transaction' => $packet['Payload']['transaction'], 'status' => 'error']);
            return;
        }
        $data = $mode === 'invalid_catalog' ? ['invalid' => true] : $data + ['devices' => $catalogDevices, 'groups' => [], 'scenes' => []];
    }
    if ($kind === 'editor') $data += ['editor_version' => 1, 'devices' => [], 'groups' => [], 'scenes' => []] + $documents[$base];
    if ($kind === 'save') {
        $check($decode($manager->ConcurrentAuthority($packet['Payload']['transaction']))['ok'], 'A reentrant kernel authority read never waits on the active owner lock');
        $check($packet['Payload']['name'] === ExtensionContract::NAME && $packet['Payload']['code'] === $bundle, 'Only the fixed bundled artifact reaches the official Z2M extension/save API');
        $uploads[] = $base;
        if ($mode === 'reject') {
            $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['transaction' => $packet['Payload']['transaction'], 'status' => 'error']);
            return;
        }
        if ($mode !== 'same_session') $metadata[$base]['session'] = sprintf('22222222-2222-4222-8222-%012d', count($uploads));
        if ($mode === 'wrong_build') $metadata[$base]['extension_build'] = str_repeat('f', 64);
        if ($mode === 'changed_store') {
            $metadata[$base]['stored_revision']++;
            $metadata[$base]['active_revision']++;
        }
        $data = (object) [];
    }
    $parent->publish(str_replace('/request/', '/response/', $packet['Topic']), ['protocol_version' => 1, 'transaction' => $packet['Payload']['transaction'], 'status' => 'ok', 'data' => $data]);
};
$overview = static fn (): array => $decode($manager->GetWorkspaceOverview());
$detail = static fn (string $id): array => $decode($manager->GetWorkspaceSystem($id));
$check($decode($manager->ConfigureWorkspace(json_encode($connections), $overview()['fingerprint'], $op()))['ok'], 'Configure eight systems over two MQTT parents');
$systems = $overview()['systems'];
$read = static fn (string $id): array => $decode($manager->GetWorkspaceInstallationStatus($id))['installation'];
$beforePackets = array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
foreach ($systems as $s) $check($read($s['systemId'])['canInstall'], 'Ownerless configured systems offer explicit initial installation');
$check(array_sum(array_map(static fn ($p): int => count($p->sent), $parents)) === $beforePackets, 'Opening all installation views never sends MQTT');
$fresh = $systems[7];
$v = $read($fresh['systemId']);
$check($decode($manager->RequestWorkspaceInstallation($fresh['systemId'], 1, $fresh['fingerprint'], $op(), $v['context'], true))['ok'], 'Initial installation works before ownership handover');
$manager->RefreshWorkspaceInstallations();
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceInstallations();
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceInstallations();
$check(count($catalogReads) === 1 && $catalogReads[0] === $topic, 'A successful installation automatically refreshes only its own device data');
$check($read($fresh['systemId'])['status'] === 'installed' && $decode(Z2MC_GetSystemOwnership($connections[7])) === [], 'Bootstrap installs without taking ownership or loading rules');
foreach ($systems as $s) {
    $operation = $op();
    $check($decode($manager->StartWorkspaceSetup($s['systemId'], 1, $detail($s['systemId'])['fingerprint'], $operation))['setup']['phase'] === 'preview', 'Use the real read-only setup preview');
    $check($decode($manager->ConfirmWorkspaceSetup($operation))['setup']['phase'] === 'complete', 'Use the real exclusive owner handover');
    $d = $detail($s['systemId']);
    $d['data']['work']['rule'] = ['dirty' => true, 'input' => ['PRIVATE_PENDING' => true]];
    $check($decode($manager->UpdateWorkspaceSystem($s['systemId'], 1, $d['fingerprint'], json_encode($d['data']), $op()))['ok'], 'Keep unsaved native inputs');
}
$preserved = static function (array $workspace): array
{
    // Only catalogs, their native mappings and operation receipts may change.
    unset($workspace['receipts']);
    foreach ($workspace['systems'] as &$record) unset($record['catalog'], $record['nativeBindings']);
    unset($record);
    return $workspace;
};
$baseline = $preserved($decode($manager->Raw()));
$unchanged = static fn (): bool => $preserved($decode($manager->Raw())) === $baseline;
$originalDocuments = $documents;
$before = Z2MAutomations\InstallationProtocol::Before($decode(Z2MC_GetConnectionInfo($connections[0]))['state']);
$check($before['resume'] === true, 'Approval pins the receiver-confirmed automatic restart setting');
$check($decode($manager->GetWorkspaceSetupStatus())['setup']['canRollback'], 'Before an owned installation the latest local handover can be rolled back');
$requestFingerprints = [];
$requestContexts = [];
$request = static function (string $id, ?string $operation = null, ?string $context = null, bool $confirmed = true) use ($manager, $detail, $read, $decode, $op, &$requestFingerprints, &$requestContexts): array
{
    $d = $detail($id);
    $operation ??= $op();
    $requestFingerprints[$operation] ??= $d['fingerprint'];
    $requestContexts[$operation] ??= $context ?? $read($id)['context'];
    return $decode($manager->RequestWorkspaceInstallation($id, 1, $requestFingerprints[$operation], $operation, $context ?? $requestContexts[$operation], $confirmed));
};
$first = $systems[0]['systemId'];
$topic = $systems[0]['binding']['baseTopic'];
$priorUploads = count($uploads);
$faulty = IPS\InstanceManager::getInstanceInterface($connections[0]);
$faulty->badHealthReply = true;
$check($request($first)['installation']['status'] === 'error' && !$read($first)['pending'] && count($uploads) === $priorUploads, 'A busy JSON reply is not persisted as a transaction or allowed to poison the journal');
$faulty->badHealthReply = false;
$check($request($first, null, null, false)['error'] === 'installation_confirmation' && count($uploads) === $priorUploads, 'No installation without explicit confirmation');
$operation = $op();
$context = $read($first)['context'];
$check($request($first, $operation, $context)['installation']['status'] === 'checking' && count($uploads) === $priorUploads, 'The initial request only checks health; no upload happens from the GET response');
$manager->RefreshWorkspaceInstallations();
$check($read($first)['status'] === 'refreshing' && !$read($first)['canInstall'], 'Confirmed upload remains visibly busy until fresh device data is available');
$catalogPackets = array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
for ($i = 0; $i < 3; $i++) $read($first);
$check(array_sum(array_map(static fn ($p): int => count($p->sent), $parents)) === $catalogPackets, 'GET installation status never starts or repeats device reads');
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceInstallations();
$manager->RefreshWorkspaceDeviceCatalogs();
$manager->RefreshWorkspaceInstallations();
$check($read($first)['status'] === 'installed' && array_slice($uploads, $priorUploads) === [$topic], 'New session and expected build confirm one scoped upload');
$check($read($first)['devices']['phase'] === 'loaded' && str_contains($read($first)['message'], 'Gerätedaten'), 'Final installation result includes confirmed device data');
$check($read($first)['devices']['counts']['devices'] === 1, 'The final result exposes actual loaded entity counts');
$check($request($first, $operation, $context)['ok'] && array_slice($uploads, $priorUploads) === [$topic], 'The same confirmation never uploads twice');
$check($unchanged() && $documents === $originalDocuments, 'Installation preserves every draft, pending input and automation file');
$check(!$decode($manager->GetWorkspaceSetupStatus())['setup']['canRollback'], 'A possible extension upload fences off a misleading local rollback');
$packets = static fn (): int => array_sum(array_map(static fn ($p): int => count($p->sent), $parents));
$finish = static function (string $id, string $expected = 'installed') use ($manager, $read, $check): void
{
    for ($attempt = 0; $attempt < 8; $attempt++) {
        $manager->RefreshWorkspaceInstallations();
        $manager->RefreshWorkspaceDeviceCatalogs();
        $manager->RefreshWorkspaceInstallations();
        if ($read($id)['status'] === $expected) break;
    }
    $check($read($id)['status'] === $expected, 'Expected final installation state: ' . $expected);
};
// All eight systems, including multiple topics on the same broker, stay independent.
foreach (array_slice($systems, 1) as $s) {
    $check($request($s['systemId'])['ok'], 'Each owned system accepts an explicit confirmation');
    $finish($s['systemId']);
}
$check(count($uploads) === $priorUploads + 8 && count(array_unique($uploads)) === 8, 'Exactly one owner-authorized upload per selected system');
$check($unchanged() && $documents === $originalDocuments, 'All local inputs, grants, YAML and other systems preserved');
$check($request($first, $operation, $context)['ok'], 'Replay reads the old result');
$check($request($first, $operation, str_repeat('b', 64))['error'] === 'operation_conflict', 'An operation cannot change its approved intent');
$newOperation = $op();
$check($request($first, $newOperation)['ok'], 'A new explicit update is allowed');
$finish($first);
$check($request($first, $operation, $context)['error'] === 'installation_superseded', 'Old confirmation cannot replay a newer job');
$check(!$decode(Z2MC_InstallExtension($connections[0], false))['ok'], 'Owned connection still blocks direct legacy installation');
$check(!$decode(Z2MC_RequestOwnedInstallation($connections[1], $managerID, $newOperation))['ok'], 'An approval for another connection is rejected');
$check(!$decode(Z2MC_RequestOwnedInstallation($connections[0], 36902, $op()))['ok'], 'Missing owner cannot produce kernel warnings');
foreach (['same_session' => 'installation_session_mismatch', 'wrong_build' => 'installation_build_mismatch', 'changed_store' => 'installation_storage_changed', 'reject' => 'installation_failed'] as $scenario => $expected) {
    $readsBeforeFailure = count($catalogReads);
    $mode = $scenario;
    $request($first);
    $finish($first, 'error');
    $check($read($first)['blocked'] !== 'installation_pending', 'Failed install does not leave a pending UI');
    $state = $read($first);
    $check(count($catalogReads) === $readsBeforeFailure, 'Rejected installations never refresh device data');
    $check($state['error'] === $expected && strlen($state['message']) > 15, 'A clear reason is displayed for ' . $scenario . ': ' . $state['error']);
    $metadata[$topic]['extension_build'] = ExtensionContract::BundledBuild();
    $metadata[$topic]['stored_revision'] = 3;
    $metadata[$topic]['active_revision'] = 3;
    $mode = 'auto';
    Z2MC_RequestExtensionStatus($connections[0]);
}
// The approved bytes cannot silently change, even while their build tag stays the same.
$staleContext = $read($first)['context'];
$manager->artifact = $bundle . "\n// synthetic changed bytes\n";
$count = count($uploads);
$check(!$request($first, null, $staleContext)['ok'] && count($uploads) === $count, 'Artifact changes invalidate consent');
$manager->artifact = null;
$connection = IPS\InstanceManager::getInstanceInterface($connections[0]);
$request($first);
$connection->artifact = $bundle . "\n// synthetic changed bytes\n";
$finish($first, 'error');
$check(count($uploads) === $count, 'Different connection artifact never uploads');
$connection->artifact = null;
$mode = 'auto';
$request($first);
$manager->clock += 20;
$finish($first, 'error');
$check(count($uploads) === $count, 'A healthy response cannot trigger an upload after the confirmation deadline');
$mode = 'hold_health';
$request($first);
$manager->clock += 20;
$connection->clock += 20;
$finish($first, 'error');
$check(count($uploads) === $count, 'Timed-out health check never uploads');
$mode = 'auto';
Z2MC_CheckHealth($connections[0], false);
$mode = 'hold_upload';
$operation = $op();
$context = $read($first)['context'];
$request($first, $operation, $context);
$finish($first, 'pending');
$countPackets = $packets();
for ($i = 0; $i < 3; $i++) {
    $read($first);
    $request($first, $operation, $context);
}
$check($packets() === $countPackets, 'Polling and replay never resend an unacknowledged upload');
$second = $systems[1]['systemId'];
$mode = 'auto';
$request($second);
$finish($second);
$connection->clock += 20;
$finish($first, 'error');
$check($read($first)['mayHaveUploaded'], 'Timeout preserves uncertainty about the attempted upload');
// An interrupted central module may observe an existing result, never resume sending.
$mode = 'hold_health';
$start = $request($first);
$check($start['ok'], 'Prepare module update interruption: ' . json_encode($start));
$countPackets = $packets();
$manager->ApplyChanges();
$check($read($first)['status'] === 'interrupted' && $packets() === $countPackets, 'Module update stops an unsent job without upload: ' . json_encode($read($first)));
$mode = 'auto';
$connection->clock += 20;
Z2MC_ExpireRequest($connections[0]);
Z2MC_CheckHealth($connections[0], false);
$mode = 'hold_upload';
$request($first);
$finish($first, 'pending');
$countPackets = $packets();
$connection->LoseWorkflow();
$check($read($first)['status'] === 'interrupted' && $packets() === $countPackets, 'Lost transport workflow is not replayed');
$mode = 'auto';
Z2MC_RequestExtensionStatus($connections[0]);
// Native form and browser route both bind approval to the actual system.
$countPackets = $packets();
$form = json_decode($manager->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
$find = static function (array $node, string $name) use (&$find): ?array
{
    if (($node['name'] ?? '') === $name) return $node;
    foreach ($node as $v) if (is_array($v) && ($found = $find($v, $name)) !== null) return $found;
    return null;
};
$button = $find($form, 'WorkspaceInstallationStart');
$check($button !== null && str_contains($button['confirm'], 'Build') && str_contains($button['confirm'], 'Aufzeichnungen'), 'Native consent explains artifact and interruption');
$reloadButton = $find($form, 'WorkspaceInstallationReload');
$check(str_contains($reloadButton['onClick'], 'Z2MA_ChooseWorkspaceSystem') && str_contains($reloadButton['onClick'], 'get_defined_vars()'), 'Native reload uses the existing guarded path to capture open inputs before rebuilding');
$check($packets() === $countPackets, 'Opening/reloading the native installation form never sends MQTT');
$selected = $overview()['selectedSystem'];
$nativeEntries = static fn (string $event): int => count(array_filter($manager->debug, static fn (string $line): bool => str_contains($line, ' | ' . $event . ' | RequestWorkspaceInstallation:')));
$check($first !== $second, 'Native callback proof requires two distinct systems');
foreach ([$first, $second] as $nativeSystem) {
    $manager->NativeFormSelection($nativeSystem);
    $form = json_decode($manager->GetConfigurationForm(), true, 64, JSON_THROW_ON_ERROR);
    $button = $find($form, 'WorkspaceInstallationStart');
    $starts = $nativeEntries('START');
    $ends = $nativeEntries('ENDE');
    $debugOffset = count($manager->debug);
    $count = count($uploads);
    // Opening the native form is not an accepted callback or an upload.
    $manager->GetConfigurationForm();
    $check($nativeEntries('START') === $starts && count($uploads) === $count, 'Native preparation creates zero installation entries');
    $id = $managerID;
    ob_start();
    eval($button['onClick']);
    $feedback = ob_get_clean();
    $check($feedback === '' && $read($nativeSystem)['status'] === 'checking', 'The actual native button callback queues only its frozen selected system');
    $check($nativeEntries('START') === $starts + 1 && $nativeEntries('ENDE') === $ends + 1, 'One native callback has exactly one canonical debug start and end');
    $nativePair = array_values(array_filter(array_slice($manager->debug, $debugOffset), static fn (string $line): bool => str_contains($line, ' | START | RequestWorkspaceInstallation:') || str_contains($line, ' | ENDE | RequestWorkspaceInstallation:')));
    $nativePair = array_map(static fn (string $line): array => json_decode(substr($line, strpos($line, '{')), true, 32, JSON_THROW_ON_ERROR), $nativePair);
    $check(count($nativePair) === 2 && $nativePair[0]['Instanz'] === $managerID && $nativePair[1]['Instanz'] === $managerID
        && $nativePair[0]['Vorgang'] === $nativePair[1]['Vorgang'] && $nativePair[0]['Aufruf'] === $nativePair[1]['Aufruf'], 'Native debug pair retains the exact instance, flow and call identity');
    $finish($nativeSystem);
    $check(count($uploads) === $count + 1, 'One accepted native callback produces exactly one synthetic upload');
    $statusButtons = array_values(array_filter($find($form, 'WorkspaceInstallationPanel')['items'], static fn (array $item): bool => ($item['type'] ?? '') === 'Button' && ($item['caption'] ?? '') === 'Installationsstatus aktualisieren'));
    $check(count($statusButtons) === 1, 'Native status control must be unambiguous');
    eval($statusButtons[0]['onClick']);
    $check($nativeEntries('START') === $starts + 1 && $nativeEntries('ENDE') === $ends + 1 && count($uploads) === $count + 1, 'Native status callback does not reinstall or add installation debug entries');
}
$manager->NativeFormSelection($selected);
$url = $manager->OpenAutomationDesigner();
$token = parse_url($url, PHP_URL_FRAGMENT);
$id = $systems[2]['systemId'];
$v = $read($id);
$headers = ['HTTP_X_Z2MA_SYSTEM_ID' => $id, 'HTTP_X_Z2MA_GENERATION' => '1', 'HTTP_X_Z2MA_FINGERPRINT' => $detail($id)['fingerprint']];
[$status, $body] = $manager->Hook('installation-status', $token, $headers, 'GET');
$check($status === 200 && $decode($body)['installation']['systemId'] === $id, 'Dedicated browser status reads the selected system');
[$status] = $manager->Hook('installation', $token, $headers, 'GET');
$check($status === 405, 'GET never initiates installation');
[$status] = $manager->Hook('installation-status', $token, array_replace($headers, ['HTTP_X_Z2MA_GENERATION' => '2']), 'GET');
$check($status === 409, 'A stale generation cannot read another binding');
$manager->requestBody = json_encode(['requestId' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'context' => $v['context'], 'confirmed' => false]);
[$status] = $manager->Hook('installation', $token, $headers);
$check($status !== 200, 'Browser requires explicit affirmative consent');
$manager->requestBody = json_encode(['requestId' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'context' => $v['context'], 'confirmed' => true, 'code' => 'forbidden']);
[$status] = $manager->Hook('installation', $token, $headers);
$check($status !== 200, 'Browser cannot supply code or arbitrary filenames');
$manager->requestBody = json_encode(['requestId' => 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb', 'context' => $v['context'], 'confirmed' => true]);
[$status] = $manager->Hook('installation', $token, $headers + ['HTTP_ORIGIN' => 'http://foreign.invalid']);
$check($status === 403, 'Foreign-origin installation is rejected before MQTT');
[$status, $body] = $manager->Hook('installation', $token, $headers);
$check($status === 200 && $decode($body)['installation']['pending'], 'Browser accepted the fixed system-bound installation');
$finish($id);
$manager->LoseAccess();
[$status] = $manager->Hook('installation-status', $token, $headers, 'GET');
$check($status === 403, 'Installation has its own session capability');
require __DIR__ . '/installation-devices.inc.php';
$allDebug = implode("\n", $manager->debug) . implode("\n", $connection->debug);
foreach (['PRIVATE_PENDING', 'PRIVATE_EXACT_YAML', 'PRIVATE_DEVICE', 'PRIVATE_DURING_FETCH', 'export const EXTENSION_BUILD', $token, $metadata[$topic]['session']] as $private) $check(!str_contains($allDebug, $private), 'Debug contains no source, inputs, token or raw receiver session');
$check(str_contains($allDebug, 'frisch bestätigt') && str_contains($allDebug, 'Installationsrückmeldung'), 'German debug explains send and result');
$check($unchanged() && $documents === $originalDocuments, 'No test writes or activates any automations');
$fieldsOf = static function (array $form) use (&$fieldsOf): array
{
    $result = [];
    foreach ($form as $value) if (is_array($value)) {
        if (isset($value['name'])) $result[$value['name']] = $value;
        $result = array_replace($result, $fieldsOf($value));
    } return $result;
};
$fields = $fieldsOf($decode($manager->GetConfigurationForm()));
$variables = ['id' => $managerID];
foreach ($fields as $name => $field) $variables[$name] = $field['value'] ?? $field['values'] ?? '';
$variables['DeviceEditorName'] = 'PRIVATE_NATIVE_UNSAVED';
$beforeReload = $packets();
$runNative = static function (string $script, array $variables): void
{
    extract($variables, EXTR_SKIP);
    eval($script);
};
$runNative($fields['WorkspaceInstallationReload']['onClick'], $variables);
$check(str_contains(json_encode($detail($selected)['data']['work']), 'PRIVATE_NATIVE_UNSAVED') && $documents === $originalDocuments && $packets() === $beforeReload, 'Native installation reload preserves unsaved editor input without MQTT or YAML writes');
$check(!str_contains(implode("\n", $manager->debug), 'PRIVATE_NATIVE_UNSAVED'), 'Reload capture does not log the private pending input');
echo "Central per-system installation, consent, errors, interruption, replay and browser tests passed.\n";
