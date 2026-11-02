<?php

declare(strict_types=1);

require __DIR__ . '/stubs/autoload.php';
IPS\Kernel::reset();
$root = dirname(__DIR__);
$library = json_decode(file_get_contents($root . '/library.json'), true, 512, JSON_THROW_ON_ERROR);
foreach ([$root . '/Z2MAutomations', __DIR__ . '/fixtures/MQTTClient', __DIR__ . '/fixtures/Z2MDevice'] as $path) {
    IPS\ModuleLoader::loadSingleModule($path, $library['id']);
}
class EditorWorkProbe extends Z2MAutomations
{
    public array $updates = [];
    public array $debug = [];
    public function Seed(?array $Rules = null): void
    {
        $state = json_decode($this->GetBuffer('TransportState'), true, 32, JSON_THROW_ON_ERROR);
        $choice = ['property' => 'state', 'value' => 'ON'];
        $key = hash('sha256', json_encode($choice, JSON_THROW_ON_ERROR));
        $device = '0x0000000000000001';
        $entry = ['name' => 'Synthetic light', 'triggers' => [$key => $choice], 'actions' => [$key => $choice], 'numeric' => [],
            'properties' => ['state' => ['property' => 'state', 'type' => 'binary', 'access' => 3, 'label' => 'State', 'endpoint' => '', 'values' => ['ON', 'OFF']]]];
        $action = ['type' => 'set', 'target' => ['type' => 'device', 'id' => $device], 'values' => ['state' => 'ON'], 'delay_seconds' => 0];
        $rules = [];
        foreach (['first', 'second'] as $n => $id) {
            $rules[] = ['id' => $id, 'name' => 'Synthetic ' . $id, 'enabled' => false,
                'triggers'   => [['type' => 'time', 'at' => '0' . ($n + 7) . ':00:00', 'timezone' => 'UTC']], 'conditions' => [], 'actions' => [$action]];
        }
        $document = ['schema_version' => 1, 'revision' => 1, 'automations' => $Rules ?? $rules,
            'sequences'               => [['id' => 'shared', 'name' => 'Synthetic shared', 'actions' => [$action]]]];
        $yaml = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        $hash = hash('sha256', $yaml);
        $session = '11111111-1111-4111-8111-111111111111';
        $state['bridgeState'] = 'online';
        $state['bridgeOnline'] = true;
        $state['extension'] = ['session' => $session, 'storedRevision' => 1, 'checksum' => $hash, 'automationCount' => 2, 'storage' => 'ok', 'executing' => true,
            'activeRevision'             => 1, 'activeChecksum' => $hash, 'enabledCount' => 0, 'runtimeVersion' => 1, 'runtimeError' => '', 'ready' => true,
            'version'                    => '0.1.0', 'meshReady' => false, 'meshPeerCount' => 0, 'resumeEnabled' => true, 'resumeStatus' => 'armed', 'resumeError' => '',
            'sequenceVersion'            => 1, 'executionVersion' => 1, 'debugVersion' => 1, 'build' => null];
        $state['extensionConfirmed'] = $state['runtimeConfirmed'] = true;
        $this->SetBuffer('TransportState', json_encode($state, JSON_THROW_ON_ERROR));
        $this->WriteAttributeString('EditorSnapshot', json_encode(['scope' => $state['scope'], 'devices' => [$device => $entry], 'groups' => []], JSON_THROW_ON_ERROR));
        $this->WriteAttributeString('YamlDraft', $yaml);
        $this->WriteAttributeString('EditorDraft', json_encode(['scope' => $state['scope'], 'session' => $session, 'baseRevision' => 1,
            'baseChecksum'                                              => $hash, 'draftChecksum' => $hash, 'configuration' => $document], JSON_THROW_ON_ERROR));
    }
    public function HasPendingWorkflow(): bool
    {
        return (json_decode($this->GetBuffer('TransportState'), true)['workflow'] ?? null) !== null;
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
$metadata = json_decode(file_get_contents($root . '/Z2MAutomations/module.json'), true);
$registry = new ReflectionProperty(IPS\ModuleLoader::class, 'modules');
$modules = $registry->getValue();
$modules[$metadata['id']]['Class'] = EditorWorkProbe::class;
$registry->setValue(null, $modules);
$id = IPS_CreateInstance($metadata['id']);
$parentID = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
IPS_ApplyChanges($parentID);
IPS_ConnectInstance($id, $parentID);
IPS_SetProperty($id, 'Active', true);
IPS_SetProperty($id, 'BaseTopic', 'synthetic/z2m');
IPS_ApplyChanges($id);
$deviceID = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
IPS_ConnectInstance($deviceID, $parentID);
IPS_SetProperty($deviceID, 'IEEE', '0x0000000000000001');
IPS_ApplyChanges($deviceID);
$module = IPS\InstanceManager::getInstanceInterface($id);
$module->Seed();
$check = static function (bool $ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
};
$fieldsOf = static function (array $items) use (&$fieldsOf): array
{
    $r = [];
    foreach ($items as $i) {
        if (isset($i['name'])) {
            $r[$i['name']] = $i;
        }
        $r += $fieldsOf($i['items'] ?? []);
        $r += $fieldsOf($i['popup']['items'] ?? []);
    }
    return $r;
};
$form = static fn (): array => $fieldsOf(json_decode($module->GetConfigurationForm(), true)['actions']);
$fields = $form();
$check(isset($fields['EditorWorkInfo'], $fields['DraftWorkInfo'], $fields['RuntimeWorkInfo']), 'Form separates pending input, saved draft and actual Z2M operation');
$check($module->SelectAutomation('first', '', false, 'time'), 'Select the first synthetic rule');
$fields = $form();
$run = static function (string $script, array $fields, array $overrides = []) use ($id): void
{
    $values = ['id' => $id];
    foreach ($fields as $name => $field) {
        $values[$name] = $field['value'] ?? (($field['type'] ?? '') === 'List' ? new ArrayIterator($field['values'] ?? []) : '');
    }
    extract(array_replace($values, $overrides), EXTR_SKIP);
    eval($script);
};
$standalone = 'function Z2MA_NavigateEditor($id, $kind, $area, $target, $draft, $input) { echo $input; } '
    . '$id = 1; $EditorTimeRule = "second"; $YamlInput = ""; $TimeEditorName = "Synthetic input"; '
    . '$TimeEditorRule = ""; $TimeEditorEditToken = ""; $UnrelatedSecret = "must-not-leave-script"; '
    . $fields['EditorTimeRule']['onChange'];
$process = proc_open([PHP_BINARY, '-r', $standalone], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
$check(is_resource($process), 'Create isolated native callback process');
fclose($pipes[0]);
$standaloneOutput = stream_get_contents($pipes[1]);
$standaloneError = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$check(proc_close($process) === 0, 'Native callback must work without module helper classes loaded: ' . $standaloneError);
$check(str_contains($standaloneOutput, 'Synthetic input') && !str_contains($standaloneOutput, 'must-not-leave-script'), 'Isolated script captures editable fields only');

$yaml = $fields['YamlInput']['value'];
$run($fields['EditorTimeRule']['onChange'], $fields, ['EditorTimeRule' => 'second', 'TimeEditorName' => 'private-unsaved-name']);
$check(($module->updates['EditorNavigationPopup']['visible'] ?? false) === true, 'Native selection callback protects even edits whose onChange has not arrived');
$check(($module->updates['EditorTimeRule']['value'] ?? null) === 'first', 'Blocked navigation restores selection without clearing inputs');
$retained = $form();
$check($retained['TimeEditorName']['value'] === 'private-unsaved-name' && $retained['YamlInput']['value'] === $yaml, 'Reopening preserves pending input without changing the draft');
$token = $module->updates['EditorNavigationToken']['value'];
$run($retained['DiscardEditorNavigation']['onClick'], $retained, ['EditorNavigationToken' => $token]);
$after = $form();
$check($after['TimeEditorName']['value'] === 'Synthetic second' && $after['YamlInput']['value'] === $yaml, 'Discard affects only form input, not stored draft');
$check(!$module->ResolveEditorNavigation($token, '{}'), 'Confirmation cannot be replayed');
$run($after['TimeEditorName']['onChange'], $after, ['TimeEditorName' => 'private-unsaved-name']);
$dirty = $form();
$input = Z2MAutomations\EditorWorkInput::Capture(array_map(static fn (array $f): mixed => $f['value'] ?? ($f['values'] ?? ''), $dirty));
$check(!$module->EditorWorkReady($input), 'Validation and transmission cannot ignore uncommitted edits');
$run($dirty['TimeEditorDetails']['onClick'], $dirty);
$stillDirty = $form();
$check($stillDirty['TimeEditorName']['value'] === 'private-unsaved-name' && $stillDirty['TimeEditorAt']['visible'], 'Collapsing and reopening the active time editor preserves its fields and visibility');
$run($stillDirty['TimeEditorName']['onChange'], $stillDirty, ['TimeEditorName' => 'Synthetic second']);
$reverted = $form();
$check(str_contains($reverted['EditorWorkInfo']['caption'], 'No pending edits'), 'Reverting all inputs clears the change indicator');
$check(str_contains($reverted['DraftWorkInfo']['caption'], 'Stored in Z2M') && str_contains($reverted['RuntimeWorkInfo']['caption'], 'running'), 'Stored document and confirmed execution are separate visible states');
$run($reverted['TimeEditorEnabled']['onChange'], $reverted, ['TimeEditorEnabled' => true]);
$changed = $form();
$check(str_contains($changed['EditorWorkInfo']['caption'], 'Form changed') && $changed['TimeEditorEnabled']['value'] === true, 'Enabled changes are pending form edits, not immediate runtime activation');
$run($changed['TimeUpdateRuleButton']['onClick'], $changed);
$applied = $form();
$check(str_contains($applied['EditorWorkInfo']['caption'], 'No pending edits') && str_contains($applied['DraftWorkInfo']['caption'], 'local in Symcon'), 'Successful apply moves input to the local draft and clears the form indicator');
$check(str_contains($applied['RuntimeWorkInfo']['caption'], 'Draft changes do not apply yet'), 'Applying a draft does not claim to change the running Z2M snapshot');
$run($applied['EditorDeviceRule']['onChange'], $applied, ['EditorDeviceRule' => '']);
$device = $form();
$run($device['DeviceEditorName']['onChange'], $device, ['DeviceEditorName' => 'Synthetic device draft']);
$deviceDirty = $form();
$run($deviceDirty['EditorTimeRule']['onChange'], $deviceDirty, ['EditorTimeRule' => 'first']);
$check(($module->updates['EditorNavigationPopup']['visible'] ?? false) && $form()['DeviceEditorName']['value'] === 'Synthetic device draft', 'Switching areas protects a new unsaved device automation');
$switchToken = $module->updates['EditorNavigationToken']['value'];
$deviceDirty = $form();
$run($deviceDirty['DiscardEditorNavigation']['onClick'], $deviceDirty, ['EditorNavigationToken' => $switchToken]);
$check($form()['TimeEditorName']['value'] === 'Synthetic first', 'Actual popup callback switches area after explicit discard');
$check($module->SelectReusableSequence('shared', '', false), 'Select the shared sequence');
$sequence = $form();
$run($sequence['SequenceEditorName']['onChange'], $sequence, ['SequenceEditorName' => 'Pending shared sequence']);
$sequenceDirty = $form();
$run($sequenceDirty['TimeEditorEnabled']['onChange'], $sequenceDirty, ['TimeEditorEnabled' => true]);
$bothDirty = $form();
$run($bothDirty['TimeUpdateRuleButton']['onClick'], $bothDirty);
$afterRule = $form();
$check($afterRule['SequenceEditorName']['value'] === 'Pending shared sequence' && str_contains($afterRule['EditorWorkInfo']['caption'], 'Form changed'), 'Applying a rule preserves a separate pending sequence edit and its selection binding');
$run($afterRule['SequenceSaveButton']['onClick'], $afterRule);
$afterSequence = $form();
$check(str_contains($afterSequence['EditorWorkInfo']['caption'], 'No pending edits') && $afterSequence['SequenceEditorName']['value'] === 'Pending shared sequence', 'Sequence apply clears only its own pending edit');
$run($afterSequence['TimeEditorName']['onChange'], $afterSequence, ['TimeEditorName' => 'Pending rule name']);
$twoEditors = $form();
$run($twoEditors['SequenceSaveButton']['onClick'], $twoEditors, ['SequenceEditorName' => 'Updated sequence']);
$afterSequence = $form();
$check($afterSequence['TimeEditorName']['value'] === 'Pending rule name', 'Applying a sequence preserves a separate pending rule edit');
$run($afterSequence['SequenceEditorID']['onChange'], $afterSequence, ['SequenceEditorID' => '', 'SequenceEditorName' => 'Another pending name']);
$check(($module->updates['SequenceEditorID']['value'] ?? '') === 'shared', 'Switching a sequence protects its pending name');
$beforeStatus = $form()['SequenceEditorName']['value'];
$module->GetTransportState();
$check($form()['SequenceEditorName']['value'] === $beforeStatus, 'Read-only status changes do not replace pending fields');
$check(!$module->TrackEditorInput('{broken', false) && !$module->TrackEditorInput(str_repeat('x', 131073), false), 'Invalid and oversized snapshots are rejected without changing input');
$check(!str_contains(implode('', $module->debug), 'private-unsaved-name'), 'Native debug never logs user input');
$check($module->SelectAutomation('first', '', false, 'time') && $module->SelectReusableSequence('shared', '', false), 'Prepare independent list deletion test');
$beforeDelete = $form();
$run($beforeDelete['TimeEditorActions']['onDelete'], $beforeDelete);
$run($beforeDelete['EditorTimeRule']['onChange'], $beforeDelete, ['EditorTimeRule' => 'second', 'TimeEditorActions' => new ArrayIterator([])]);
$deleted = $form();
$check($deleted['TimeEditorActions']['values'] === [] && str_contains($deleted['EditorWorkInfo']['caption'], 'Form changed'), 'After a pre-delete native callback, navigation preserves the actual remaining list');
$validationFound = false;
foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator(json_decode($module->GetConfigurationForm(), true))) as $key => $value) {
    if ($key === 'onClick' && is_string($value) && str_contains($value, 'Z2MA_ValidateYaml(')) {
        $validationFound = true;
        $run($value, $deleted);
    }
}
$check($validationFound, 'Exercise the actual validation callback');
$check(($module->updates['EditorWorkPopup']['visible'] ?? false) && !$module->HasPendingWorkflow(), 'Actual validation button cannot transmit while pending inputs exist');
$check(!$module->TrackEditorInput('{"time":"invalid"}', false), 'Malformed editor maps are rejected safely');

require __DIR__ . '/editor-overview.inc.php';

IPS_SetProperty($id, 'BaseTopic', 'synthetic/other');
IPS_ApplyChanges($id);
$check($module->EditorWorkReady('{}'), 'Pending inputs from an old MQTT scope cannot block loading the new scope');
$check(str_contains($form()['DraftWorkInfo']['caption'], 'Load devices'), 'Old scope draft is not presented as current');
$check($form()['EditorDeviceSearch']['value'] === '' && $form()['EditorAdvancedStateFilter']['value'] === 'all', 'View filters do not cross MQTT contexts');

echo "Editor work status and guarded native selection passed.\n";
