<?php

declare(strict_types=1);

foreach (['device', 'time'] as $area) {
    $module->Seed();
    $prefix = $area === 'device' ? 'Device' : 'Time';
    if ($area === 'device') {
        $document = json_decode($form()['YamlInput']['value'], true);
        $document['automations'][0]['triggers'] = [['type' => 'device', 'device' => '0x0000000000000001', 'property' => 'state', 'operator' => 'eq', 'value' => 'ON']];
        $module->Seed($document['automations']);
    }
    $check($module->SelectAutomation('first', '', false, $area), 'Select a synthetic rule');
    $fields = $form();
    $check($fields[$prefix . 'EditorRetrigger']['value'] === 'ignore', 'Existing rules without a policy keep their ignore behavior');
    foreach (['restart', 'extend', 'ignore'] as $policy) {
        $before = $fields['YamlInput']['value'];
        $run($fields[$prefix . 'EditorRetrigger']['onChange'], $fields, [$prefix . 'EditorRetrigger' => $policy]);
        $pending = $form();
        $check($pending[$prefix . 'EditorRetrigger']['value'] === $policy && $pending['YamlInput']['value'] === $before, 'Policy input survives reopening without writing the draft');
        $run($pending[$prefix . 'UpdateRuleButton']['onClick'], $pending);
        $fields = $form();
        $document = json_decode($fields['YamlInput']['value'], true);
        $check($document['automations'][0]['retrigger'] === $policy, 'Native apply writes the chosen policy');
        $check($module->SelectAutomation('', '', false, $area) && $module->SelectAutomation('first', '', false, $area), 'Reopen a saved local rule');
        $fields = $form();
        $check($fields[$prefix . 'EditorRetrigger']['value'] === $policy, 'Selection prefills the saved policy in either area');
    }
    foreach (['resume', 'resume_expired', 'abort'] as $restartPolicy) {
        $before = $fields['YamlInput']['value'];
        $run($fields[$prefix . 'EditorRestartPolicy']['onChange'], $fields, [$prefix . 'EditorRestartPolicy' => $restartPolicy]);
        $pending = $form();
        $check($pending[$prefix . 'EditorRestartPolicy']['value'] === $restartPolicy && $pending['YamlInput']['value'] === $before, 'Restart choice survives form refresh without writing YAML');
        $run($pending[$prefix . 'UpdateRuleButton']['onClick'], $pending);
        $check($module->SelectAutomation('', '', false, $area) && $module->SelectAutomation('first', '', false, $area), 'Reopen the selected recovery rule');
        $fields = $form();
        $document = json_decode($fields['YamlInput']['value'], true);
        $check($document['automations'][0]['restart_policy'] === $restartPolicy && $fields[$prefix . 'EditorRestartPolicy']['value'] === $restartPolicy, 'Restart choice is stored and prefilled in either native editor');
    }
    $before = $fields['YamlInput']['value'];
    $run($fields[$prefix . 'UpdateRuleButton']['onClick'], $fields, [$prefix . 'EditorRestartPolicy' => 'retry_all']);
    $check($form()['YamlInput']['value'] === $before && str_contains($module->GetTransportState(), 'editor_restart_policy'), 'Invalid recovery policy is rejected atomically');
    $run($fields[$prefix . 'UpdateRuleButton']['onClick'], $fields, [$prefix . 'EditorRetrigger' => 'parallel']);
    $check($form()['YamlInput']['value'] === $before && str_contains($module->GetTransportState(), 'editor_retrigger'), 'Unknown policies are rejected atomically');
}
$module->Seed();
$module->SelectAutomation('first', '', false, 'time');
$fields = $form();
$run($fields['TimeUpdateRuleButton']['onClick'], $fields, ['TimeEditorRetrigger' => 'extend', 'TimeEditorRestartPolicy' => 'resume']);
$fields = $form();
$options = ['EditorTriggerType' => 'time', 'EditorAt' => '09:00:00', 'EditorTimezone' => 'UTC'];
$check($module->SaveScheduledAutomation(
    'first',
    $fields['TimeEditorEditToken']['value'],
    'Synthetic renamed',
    false,
    '',
    '',
    '',
    '',
    json_encode($options),
    '',
    false,
    json_encode($fields['TimeEditorActions']['values']),
    'time'
), 'An older API caller can edit other fields without supplying a new policy');
$fields = $form();
$check($fields['TimeEditorRetrigger']['value'] === 'extend', 'Omitted API option preserves an existing nondefault policy');
$check($fields['TimeEditorRestartPolicy']['value'] === 'resume', 'An omitted API recovery option preserves the existing continuation policy');
$run($fields['TimeCopyRuleButton']['onClick'], $fields);
$fields = $form();
$check($fields['TimeEditorRetrigger']['value'] === 'extend' && !$fields['TimeEditorEnabled']['value'], 'Disabled copies retain their retrigger policy');
$check($fields['TimeEditorRestartPolicy']['value'] === 'resume', 'Disabled copies retain their continuation policy');
echo "Retrigger native callback and prefill checks passed.\n";
