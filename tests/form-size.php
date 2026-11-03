<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
IPS\Kernel::reset();
$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ([$root . '/Z2MAutomations', __DIR__ . '/fixtures/MQTTClient', __DIR__ . '/fixtures/Z2MDevice'] as $folder) {
    IPS\ModuleLoader::loadSingleModule($folder, $library['id']);
}
class FormSizeProbe extends Z2MAutomations
{
    public function SetSyntheticCatalog(array $devices): void
    {
        $state = json_decode($this->GetBuffer('TransportState'), true, 32, JSON_THROW_ON_ERROR);
        $this->WriteAttributeString('EditorSnapshot', json_encode(['scope' => $state['scope'], 'devices' => $devices, 'groups' => []], JSON_THROW_ON_ERROR));
    }

    protected function LoadConfigurationForm(): array
    {
        return json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/form.json'), true, 512, JSON_THROW_ON_ERROR);
    }
}
$metadata = json_decode(file_get_contents($root . '/Z2MAutomations/module.json'), true, 512, JSON_THROW_ON_ERROR);
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[$metadata['id']]['Class'] = FormSizeProbe::class;
$registry->setValue(null, $modules);
$id = IPS_CreateInstance($metadata['id']);
$parent = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
IPS_ApplyChanges($parent);
IPS_ConnectInstance($id, $parent);
IPS_SetProperty($id, 'BaseTopic', 'synthetic/z2m');
IPS_ApplyChanges($id);
$module = IPS\InstanceManager::getInstanceInterface($id);
$devices = [];
for ($n = 1; $n <= 8; $n++) {
    $deviceID = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
    IPS_ConnectInstance($deviceID, $parent);
    $ieee = '0x' . str_pad((string) $n, 16, '0', STR_PAD_LEFT);
    IPS_SetProperty($deviceID, 'IEEE', $ieee);
    IPS_ApplyChanges($deviceID);
    $entry = ['name' => 'Synthetic ' . $n . ' ' . str_repeat('ä', 100), 'actions' => [], 'triggers' => [], 'numeric' => [], 'properties' => []];
    foreach (['state_l1', 'state_l2', 'state_l3', 'cover_state'] as $property) {
        $values = [];
        for ($value = 0; $value < 16; $value++) {
            $values[] = 'Synthetic value ' . $value;
            $choice = ['property' => $property, 'value' => $values[$value]];
            $key = hash('sha256', json_encode($choice, JSON_THROW_ON_ERROR));
            $entry['actions'][$key] = $entry['triggers'][$key] = $choice;
        }
        $entry['properties'][$property] = ['property' => $property, 'type' => 'enum', 'access' => 3,
            'label'                                   => 'Synthetic property ' . str_repeat('ü', 70), 'endpoint' => '', 'values' => $values];
    }
    $devices[$ieee] = $entry;
}
$module->SetSyntheticCatalog($devices);
$form = $module->GetConfigurationForm();
$wireBytes = strlen(json_encode($form, JSON_THROW_ON_ERROR));
if ($wireBytes >= 750 * 1024) {
    throw new RuntimeException('Native form exceeds its size budget: ' . $wireBytes . ' bytes.');
}
$rowForm = json_decode($module->GetEditorItemForm('DeviceEditorActions', '', 0), true, 512, JSON_THROW_ON_ERROR);
$fields = array_column($rowForm, null, 'name');
if (count($fields['RowDevice']['options']) !== 9) {
    throw new RuntimeException('The complete device catalog must remain selectable in the row dialog.');
}
$firstDevice = array_key_first($devices);
$firstChoices = array_keys($devices[$firstDevice]['actions']);
$actionRows = array_map(static fn (string $key): array => ['Choice' => $firstDevice . ':' . $key, 'Delay' => 0], array_slice($firstChoices, 0, 16));
$triggerRows = array_map(static fn (array $row): array => ['Choice' => $row['Choice'], 'Compare' => 'eq', 'Hold' => 0], array_slice($actionRows, 0, 8));
$conditionRows = array_map(static fn (array $row): array => ['Choice' => $row['Choice'], 'Compare' => 'eq'], $actionRows);
if (!$module->InspectEditorActions(json_encode($actionRows), 'edit') || !$module->InspectEditorTriggers(json_encode($triggerRows), 'edit')
    || !$module->InspectEditorConditions(json_encode($conditionRows), 'edit')) {
    throw new RuntimeException('Synthetic maximum-length lists must remain editable.');
}
$variable = IPS_GetObjectIDByIdent('state_l1', IPS_GetInstanceListByModuleID('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}')[0]);
for ($n = 0; $n < 40; $n++) {
    $module->ConfigureEditorItem('value', 'DeviceEditorActions', $firstDevice, (string) $variable, json_encode($firstChoices[$n % 16]));
}
$largeSelectedForm = $module->GetConfigurationForm();
if (strlen(json_encode($largeSelectedForm, JSON_THROW_ON_ERROR)) >= 750 * 1024) {
    throw new RuntimeException('Repeated dialog edits or selected rows must not grow the form past its budget.');
}
if (!str_contains($largeSelectedForm, $actionRows[15]['Choice'])) {
    throw new RuntimeException('Changing another row must preserve existing selected display values.');
}
$compoundRows = [];
for ($n = 0; $n < 16; $n++) {
    $values = array_fill_keys(array_keys($devices[$firstDevice]['properties']), 'Synthetic value ' . $n);
    ksort($values);
    $compoundRows[] = ['Choice' => $firstDevice . ':command.' . rtrim(strtr(base64_encode(json_encode($values)), '+/', '-_'), '='), 'Delay' => $n];
}
if (!$module->InspectEditorActions(json_encode($compoundRows), 'edit')) {
    throw new RuntimeException('Sixteen complete multi-property rows must remain editable.');
}
$compoundForm = $module->GetConfigurationForm();
$compoundBytes = strlen(json_encode($compoundForm, JSON_THROW_ON_ERROR));
if ($compoundBytes >= 750 * 1024 || !str_contains($compoundForm, $compoundRows[15]['Choice'])) {
    throw new RuntimeException('Multi-property selections must retain their options within the native form budget: ' . $compoundBytes . ' bytes; final option present: ' . (str_contains($compoundForm, $compoundRows[15]['Choice']) ? 'yes' : 'no'));
}
echo 'Large-catalog native form budget passed: ' . $wireBytes . " bytes.\n";
echo 'Sixteen multi-property rows within native form budget: ' . $compoundBytes . " bytes.\n";
$largeValues = array_map(static fn (int $n): string => str_repeat('x', 277) . $n, range(0, 15));
foreach ($devices[$firstDevice]['properties'] as &$property) {
    $property['values'] = array_merge($property['values'], $largeValues);
}
unset($property);
$module->SetSyntheticCatalog($devices);
$compoundRows = [];
foreach ($largeValues as $n => $value) {
    $values = array_fill_keys(array_keys($devices[$firstDevice]['properties']), $value);
    ksort($values);
    $compoundRows[] = ['Choice' => $firstDevice . ':command.' . rtrim(strtr(base64_encode(json_encode($values)), '+/', '-_'), '='), 'Delay' => $n];
}
if (!$module->InspectEditorActions(json_encode($compoundRows), 'edit')) {
    throw new RuntimeException('Large but permitted command values must remain editable.');
}
$maximumCommandBytes = strlen(json_encode($module->GetConfigurationForm(), JSON_THROW_ON_ERROR));
// The baseline retains its 750 KiB budget. Near-limit command bodies use reserved
// headroom but must still leave at least 124 KiB below Symcon's 1 MiB output cap.
if ($maximumCommandBytes >= 900 * 1024) {
    throw new RuntimeException('Large complete commands exceed the native output safety budget: ' . $maximumCommandBytes . ' bytes.');
}
echo 'Near-limit multi-property values below 900 KiB safety budget: ' . $maximumCommandBytes . " bytes.\n";
