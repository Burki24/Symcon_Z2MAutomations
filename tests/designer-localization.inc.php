<?php

declare(strict_types=1);

// Public language audit route: fixed payload, same-origin capability, no editor/MQTT writes.
$languageToken = explode('#', $module->OpenAutomationDesigner())[1];
$languageBefore = $module->Fingerprint();
$languagePackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$module->requestBody = json_encode(['language' => 'en', 'previousLanguage' => 'de', 'openEditor' => true]);
$check($module->Hook('language', 'POST', $languageToken)[0] === 200, 'A language change is confirmed through the existing guarded browser hook');
$check($module->Hook('language', 'GET', $languageToken)[0] === 405 && $module->Hook('language', 'POST')[0] === 403, 'Language audit requires POST and a valid capability');
$module->requestBody = json_encode(['language' => 'en', 'previousLanguage' => 'de', 'openEditor' => true, 'name' => 'PRIVATE_INPUT']);
$check($module->Hook('language', 'POST', $languageToken)[0] === 400, 'Arbitrary editor content cannot be sent to language debug');
$module->requestBody = '{"language":"fr","previousLanguage":"de","openEditor":false}';
$check($module->Hook('language', 'POST', $languageToken)[0] === 400, 'Only supported language identifiers are accepted');
$module->requestBody = '{"language":"en","previousLanguage":"de","openEditor":false}';
$_SERVER['HTTP_ORIGIN'] = 'http://other.invalid';
$check($module->Hook('language', 'POST', $languageToken)[0] === 403, 'Foreign origins cannot invoke language audit');
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:3777';
$check($module->Fingerprint() === $languageBefore && count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $languagePackets, 'Language changes preserve every editor byte and send no MQTT');
$check(str_contains(implode("\n", $module->debug), 'Designersprache lokal gewechselt') && !str_contains(implode("\n", $module->debug), 'PRIVATE_INPUT'), 'German debug explains language switching without retaining private input');
$check($module->Hook('localization.js')[0] === 200 && $module->Hook('locales.js')[0] === 200
    && $module->Hook('locales/en.json')[0] === 404, 'Only fixed compiled language assets are exposed, never arbitrary catalog paths');

$present = new ReflectionMethod($module, 'DesignerPresentationBody');
$original = ['message' => 'Bitte zuerst ein Z2M-System auswählen.', 'configuration' => ['name' => 'Ein', 'enabled' => false, 'value' => 'ON'],
    'name'             => 'Bitte zuerst ein Z2M-System auswählen.', 'devices' => [['name' => 'Ein', 'sceneNames' => ['Ein']]]];
$rendered = json_decode($present->invoke($module, json_encode($original)), true);
$check($rendered['configuration'] === $original['configuration'] && $rendered['devices'] === $original['devices'] && $rendered['name'] === $original['name'], 'Presentation annotations leave protocol values, scene names and user names unchanged');
$check(count($rendered['presentation']['messages']) === 1 && $rendered['presentation']['messages'][0]['path'] === ['message'], 'Known browser-facing messages carry a stable key separately from their native text');
$catalog = json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/visualization/locales/messages.json'), true);
$formatted = null;
foreach ($catalog as $key => $sources) {
    if (substr_count($sources[0], '%s') === 1 && !str_contains(str_replace('%s', '', $sources[0]), '%')) {
        $formatted = ['message' => sprintf($sources[0], 'Synthetic value')];
        break;
    }
}
$check($formatted !== null, 'A native parameterized message is available for the adapter check');
$rendered = json_decode($present->invoke($module, json_encode($formatted)), true);
$check(($rendered['presentation']['messages'][0]['values']['value1'] ?? '') === 'Synthetic value', 'Native printf parameters become named presentation parameters without translating their values');

class DesignerMessageFixture
{
    use Z2MAutomations\DesignerPresentation;
    public array $journal = [];
    public function Render(array $view): array
    {
        return json_decode($this->DesignerPresentationBody(json_encode($view)), true);
    }
    private function ReadWorkspaceTransfers(): array
    {
        return $this->journal;
    }
}
$messageFixture = new DesignerMessageFixture();
$job = ['request' => ['operation' => 'fixture'], 'names' => ['Ein ON'],
    'details'     => ['issues' => [['path' => '/automations/0/triggers/0/value', 'reason' => 'required']], 'truncated' => false]];
$messageFixture->journal = ['systems' => ['fixture' => $job]];
$view = ['systemId' => 'fixture', 'operation' => 'fixture', 'context' => Z2MAutomations\WorkspaceState::Fingerprint($job), 'details' => 'Rejected'];
$detail = $messageFixture->Render($view)['presentation']['messages'][0];
$check($detail['key'] === 'validation.details' && str_contains($detail['localizedValues']['detail']['en'], 'This entry is missing.')
    && !str_contains($detail['localizedValues']['detail']['de'], 'This entry is missing.')
    && str_contains($detail['localizedValues']['detail']['en'], 'Ein ON') && str_contains($detail['localizedValues']['detail']['de'], 'Ein ON'), 'The canonical validation formatter supplies both languages while preserving the original rule name');
$view['context'] = str_repeat('0', 64);
$check(!in_array('validation.details', array_column($messageFixture->Render($view)['presentation']['messages'], 'key'), true), 'A stale status cannot reuse validation details from a newer job');
$many = $messageFixture->Render(['rows' => array_fill(0, 400, ['message' => 'Bitte zuerst ein Z2M-System auswählen.'])]);
$check(count($many['presentation']['messages']) === 256, 'Presentation annotations stay within the client limit');
$module->requestBody = '{}';
