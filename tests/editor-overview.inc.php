<?php

declare(strict_types=1);

$overview = $form();
foreach (['Device', 'Time', 'Advanced'] as $stem) {
    $check($overview['Editor' . $stem . 'Rules']['rowCount'] >= 10, 'Rule overviews show at least ten rows');
    $widths = array_column($overview['Editor' . $stem . 'Rules']['columns'], 'width');
    $check(count(array_filter($widths, static fn (string $width): bool => $width === 'auto')) === 1, 'Native overview columns share one flexible width so headers and rows remain aligned');
    $check(isset($overview['Editor' . $stem . 'Search'], $overview['Editor' . $stem . 'StateFilter'], $overview['Editor' . $stem . 'Results']), 'Each overview offers search, enabled filter and a result count');
}
$check(isset($overview['DeviceCopyRuleButton'], $overview['TimeCopyRuleButton'], $overview['CopyRuleButton']), 'Every editing area offers a copy action');

$baseAction = ['type' => 'set', 'target' => ['type' => 'device', 'id' => '0x0000000000000001'], 'values' => ['state' => 'ON'], 'delay_seconds' => 5];
$timeTrigger = ['type' => 'time', 'at' => '07:00:00', 'timezone' => 'UTC'];
$deviceTrigger = ['type' => 'device', 'device' => '0x0000000000000001', 'property' => 'state', 'operator' => 'eq', 'value' => 'ON'];
$manyRules = [];
for ($number = 0; $number < 50; $number++) {
    $manyRules[] = ['id' => 'overview_' . $number, 'name' => $number === 0 ? 'Synthetic KÜCHE' : ($number < 2 ? 'Duplicate name' : 'Synthetic room ' . $number),
        'enabled'        => $number % 2 === 0, 'triggers' => [$number % 3 === 0 ? $timeTrigger : $deviceTrigger], 'conditions' => [], 'actions' => [$baseAction]];
}
$manyRules[0]['conditions'] = [
    ['type' => 'device_value', 'device' => '0x0000000000000001', 'property' => 'state', 'operator' => 'eq', 'value' => 'ON'],
    ['type' => 'time_window', 'after' => '22:00:00', 'before' => '08:00:00', 'timezone' => 'UTC', 'weekdays' => [1, 2, 3]]];
$manyRules[1]['triggers'][0]['for_seconds'] = 3;
$manyRules[1]['triggers'][0]['on_change'] = true;
$manyRules[2]['name'] = 'Duplicate name';
$manyRules[4]['triggers'] = [$timeTrigger, $deviceTrigger];
$manyRules[4]['actions'] = [['type' => 'scene', 'target' => ['type' => 'group', 'id' => 2, 'system' => 'synthetic/other'], 'scene_id' => 0, 'delay_seconds' => 2]];
$manyRules[6]['actions'] = [['type' => 'sequence', 'sequence_id' => 'shared', 'delay_seconds' => 3]];
$manyRules[9]['name'] = str_repeat('ä', 120);
$manyRules[12]['triggers'] = [['type' => 'sun', 'event' => 'sunset', 'latitude' => 50.0, 'longitude' => 10.0, 'offset_minutes' => -20, 'timezone' => 'UTC']];
$module->Seed($manyRules);
$check($module->SelectAutomation('overview_0', '', false, 'time'), 'Open a selected rule among fifty');
$many = $form();
$manyYaml = $many['YamlInput']['value'];
$manyRules = json_decode($manyYaml, true, 512, JSON_THROW_ON_ERROR)['automations'];
$tokenBeforeFilter = $many['TimeEditorEditToken']['value'];
$runtimeBeforeCopies = json_decode($module->GetTransportState(), true)['extension'];
$check($many['TimeCopyRuleButton']['enabled'], 'Copy is offered for a selected rule after reopening');
$check(count($many['EditorTimeRules']['values']) === 17 && count($many['EditorDeviceRules']['values']) === 32 && count($many['EditorAdvancedRules']['values']) === 1, 'Fifty rules are separated into their actual categories');
$run($many['EditorTimeSearch']['onChange'], $many, ['EditorTimeSearch' => 'küche', 'TimeEditorName' => 'Private pending name']);
$searched = $form();
$check(count($searched['EditorTimeRules']['values']) === 1 && $searched['EditorTimeRules']['values'][0]['Name'] === 'Synthetic KÜCHE', 'Search is Unicode case insensitive');
$check($searched['TimeEditorName']['value'] === 'Private pending name' && $searched['TimeEditorEditToken']['value'] === $tokenBeforeFilter && $searched['YamlInput']['value'] === $manyYaml, 'Searching captures fresh pending input without changing the selection token or draft');
$run($searched['EditorTimeStateFilter']['onChange'], $searched, ['EditorTimeStateFilter' => 'disabled']);
$hidden = $form();
$check($hidden['EditorTimeRules']['values'] === [] && count($hidden['EditorTimeRule']['options']) === 2 && $hidden['EditorTimeRule']['value'] === 'overview_0', 'A selected rule outside the combined filter is retained in the selector');
$check(str_contains($hidden['EditorTimeResults']['caption'], 'does not match') && $hidden['TimeEditorName']['value'] === 'Private pending name', 'No matches explain how editing remains available');
$check(!$module->FilterAutomations('other', '', 'all') && !$module->FilterAutomations('time', str_repeat('x', 161), 'all')
    && !$module->FilterAutomations('time', "\xFF", 'all') && !$module->FilterAutomations('time', '', 'wrong'), 'Invalid filters are rejected');
$check($module->FilterAutomations('device', 'Synthetic light', 'all') && count($form()['EditorDeviceRules']['values']) === 32, 'Search includes device names without disturbing another category');
$check($form()['EditorTimeSearch']['value'] === 'küche' && $form()['EditorTimeStateFilter']['value'] === 'disabled', 'Filters are independent by area');
$run($hidden['TimeCopyRuleButton']['onClick'], $hidden);
$check($form()['YamlInput']['value'] === $manyYaml, 'Native copy button cannot discard a pending name');
$check(!$module->CopyAutomation('overview_0', $tokenBeforeFilter, $manyYaml, true, 'time'), 'The direct copy entry also rejects pending edits');
$hidden = $form();
$run($hidden['TimeEditorName']['onChange'], $hidden, ['TimeEditorName' => 'Synthetic KÜCHE']);
$clean = $form();
$run($clean['TimeCopyRuleButton']['onClick'], $clean);
$copied = $form();
$copiedDoc = json_decode($copied['YamlInput']['value'], true, 512, JSON_THROW_ON_ERROR);
$copy = $copiedDoc['automations'][50];
$check(count($copiedDoc['automations']) === 51 && !$copy['enabled'] && $copy['id'] !== 'overview_0' && $copied['TimeEditorRule']['value'] === $copy['id'], 'Copy has a fresh identity, is disabled and opens in the correct editor');
$check($copiedDoc['sequences'] === json_decode($manyYaml, true)['sequences'], 'Shared sequence definitions remain unchanged instead of being duplicated');
$check($copy['triggers'] === $manyRules[0]['triggers'] && $copy['conditions'] === $manyRules[0]['conditions'] && $copy['actions'] === $manyRules[0]['actions'], 'Copy preserves all execution semantics');
$check($copied['EditorTimeSearch']['value'] === '' && $copied['EditorTimeStateFilter']['value'] === 'all' && $copied['EditorDeviceSearch']['value'] === 'Synthetic light', 'Copy clears its own view filter and preserves other areas');
$check(!$module->CopyAutomation('overview_0', $tokenBeforeFilter, $manyYaml, true, 'time') && count(json_decode($form()['YamlInput']['value'], true)['automations']) === 51, 'A repeated stale button cannot create another copy');
$check(array_slice($copiedDoc['automations'], 0, 50) === $manyRules && json_decode($module->GetTransportState(), true)['extension'] === $runtimeBeforeCopies, 'Original rules remain byte-for-byte equivalent in the document');
$check($module->SelectAutomation('overview_0', '', false, 'time'), 'Select source for a second independent copy');
$twice = $form();
$run($twice['TimeCopyRuleButton']['onClick'], $twice);
$secondDoc = json_decode($form()['YamlInput']['value'], true);
$check($secondDoc['automations'][51]['name'] !== $copy['name'] && $secondDoc['automations'][51]['id'] !== $copy['id'], 'Repeated copies receive distinguishable names and identities');
$check($module->SelectAutomation('overview_9', '', false, 'time'), 'Select maximum-length Unicode name');
$long = $form();
$run($long['TimeCopyRuleButton']['onClick'], $long);
$longCopy = array_slice(json_decode($form()['YamlInput']['value'], true)['automations'], -1)[0];
$check(preg_match_all('/./us', $longCopy['name']) === 120 && preg_match('//u', $longCopy['name']) === 1, 'Copy suffix respects the 120-character UTF-8 name limit');
foreach (['overview_4' => 'advanced', 'overview_6' => 'time', 'overview_12' => 'time', 'overview_1' => 'device'] as $sourceID => $area) {
    $check($module->SelectAutomation($sourceID, '', false, $area === 'advanced' ? 'device' : $area), 'Select another copy category');
    $sourceFields = $form();
    $prefix = $area === 'advanced' ? '' : ucfirst($area);
    $run($sourceFields[$prefix . 'CopyRuleButton']['onClick'], $sourceFields);
    $document = json_decode($form()['YamlInput']['value'], true);
    $last = array_slice($document['automations'], -1)[0];
    $original = $manyRules[(int) substr($sourceID, strlen('overview_'))];
    $check($last['triggers'] === $original['triggers'] && $last['actions'] === $original['actions'] && !$last['enabled'], 'Advanced, sequence, solar and device copies preserve triggers and actions');
}
$check($module->FilterAutomations('advanced', 'no-match', 'all') && $form()['EditorAdvancedSection']['visible'], 'An empty filtered advanced list remains visible so its filter can be cleared');
$check($module->FilterAutomations('device', 'Duplicate name', 'all'), 'Search duplicate names');
$duplicateOptions = $form()['EditorDeviceRule']['options'];
$duplicateIDs = array_column($duplicateOptions, 'value');
$check(in_array('overview_1', $duplicateIDs, true) && in_array('overview_2', $duplicateIDs, true), 'Identical names retain distinct stable selection IDs');
$selected = $form();
$check(!$module->CopyAutomation($selected['DeviceEditorRule']['value'], $selected['DeviceEditorEditToken']['value'], $selected['YamlInput']['value'], true, 'time'), 'A stale button from another editor area cannot copy');
$check(!$module->HasPendingWorkflow() && !str_contains(implode('', $module->debug), 'Private pending name'), 'Overview and copy never start MQTT operations or expose pending values');
$hundred = [];
for ($number = 0; $number < 100; $number++) {
    $hundred[] = array_replace($manyRules[0], ['id' => 'limit_' . $number]);
}
$module->Seed($hundred);
$check($module->SelectAutomation('limit_0', '', false, 'time'), 'Select source at the rule limit');
$limit = $form();
$check(!$module->CopyAutomation('limit_0', $limit['TimeEditorEditToken']['value'], $limit['YamlInput']['value'], true, 'time')
    && count(json_decode($form()['YamlInput']['value'], true)['automations']) === 100, 'Copy enforces the existing 100-rule schema limit');
echo "Automation overview search, filtering and disabled copies passed.\n";
