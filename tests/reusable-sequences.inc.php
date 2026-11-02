<?php

declare(strict_types=1);

$module->LoadAutomationEditor();
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$sequenceOriginal = json_decode($sequenceForm['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$sequenceOn = '0x0000000000000002:' . hash('sha256', json_encode(['property' => 'state', 'value' => 'ON'], JSON_THROW_ON_ERROR));
$sequenceOff = '0x0000000000000002:' . hash('sha256', json_encode(['property' => 'state', 'value' => 'OFF'], JSON_THROW_ON_ERROR));
$sequenceSteps = [['Choice' => $sequenceOn, 'Delay' => 0], ['Choice' => $sequenceOff, 'Delay' => 5]];
$runEditorScript($sequenceForm['SequenceEditorID']['onChange'], $sequenceForm, ['SequenceEditorID' => '']);
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$runEditorScript($sequenceForm['SequenceEditorActions']['onEdit'], $sequenceForm, ['SequenceEditorActions' => new ArrayIterator($sequenceSteps)]);
$runEditorScript(
    $sequenceForm['SequenceSaveButton']['onClick'],
    $sequenceForm,
    ['SequenceEditorName' => 'Synthetic reusable plan', 'SequenceEditorActions' => new ArrayIterator($sequenceSteps)]
);
$check($state()['operation']['status'] === 'sequence_created', 'Native sequence form creates a reusable definition from permitted device steps');
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$sequenceID = $sequenceForm['SequenceEditorID']['value'];
$check($sequenceID !== '' && $sequenceForm['SequenceEditorName']['value'] === 'Synthetic reusable plan'
    && $sequenceForm['SequenceEditorActions']['values'] === $sequenceSteps, 'Sequence selection restores its name, order and delay');
$sequenceSteps = array_reverse($sequenceSteps);
$runEditorScript($sequenceForm['SequenceEditorActions']['onChangeOrder'], $sequenceForm, ['SequenceEditorActions' => new ArrayIterator($sequenceSteps)]);
$runEditorScript(
    $sequenceForm['SequenceSaveButton']['onClick'],
    $sequenceForm,
    ['SequenceEditorName' => 'Synthetic reusable edited plan', 'SequenceEditorActions' => new ArrayIterator($sequenceSteps)]
);
$check($state()['operation']['status'] === 'sequence_updated', 'Native form edits and reorders a reusable sequence without changing its ID');

$sequenceCallers = [];
foreach ([2, 1] as $calls) {
    $module->SelectAutomation('', '', false, 'time');
    $sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
    $references = array_fill(0, $calls, ['Choice' => 'sequence:' . $sequenceID, 'Delay' => 2]);
    $runEditorScript($sequenceForm['TimeCreateRuleButton']['onClick'], $sequenceForm, ['TimeEditorName' => 'Synthetic reusable caller ' . $calls,
        'TimeEditorEnabled'                                                                             => false, 'TimeEditorTimeMode' => 'clock', 'TimeEditorTimes' => new ArrayIterator([['At' => '18:00:00']]), 'TimeEditorTimezone' => 'UTC',
        'TimeEditorWindow'                                                                              => false, 'TimeEditorConditionType' => 'none', 'TimeEditorConditionList' => new ArrayIterator([]),
        'TimeEditorActions'                                                                             => new ArrayIterator($references)]);
    $check($state()['operation']['status'] === 'draft_created', 'Rule creation accepts sequence references rather than copying the definition');
    $sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
    $document = json_decode($sequenceForm['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
    $sequenceCallers[] = $document['automations'][array_key_last($document['automations'])]['id'];
}
$check($module->SelectAutomation($sequenceCallers[0], '', false, 'time'), 'A rule starting with a sequence remains editable');
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(
    count($sequenceForm['TimeEditorActions']['values']) === 2 && $sequenceForm['TimeEditorActions']['values'][0]['Choice'] === 'sequence:' . $sequenceID,
    'Existing rule restores reusable references and caller-specific delays'
);
$sequenceDialog = formFields(json_decode($module->GetEditorItemForm('TimeEditorActions', 'sequence:' . $sequenceID, 2, 'eq', false), true, 512, JSON_THROW_ON_ERROR));
$sequenceDebug = array_map(static fn (array $entry): array => json_decode($entry[1], true, 512, JSON_THROW_ON_ERROR), array_slice($module->debugMessages, -3));
$check(count(array_filter($sequenceDebug, static fn (array $entry): bool => ($entry['Bezug']['WiederverwendbareFolge'] ?? false) && ($entry['Bezug']['Vorbelegt'] ?? false))) === 1, 'Native debug correctly confirms a prefilled sequence without exposing its identity');
$check($sequenceDialog['RowActionKind']['value'] === 'sequence' && $sequenceDialog['RowSequence']['value'] === $sequenceID
    && !$sequenceDialog['RowDevice']['visible'] && $sequenceDialog['Delay']['value'] === 2, 'The native row dialog restores action type and sequence selection');
$runEditorScript($sequenceDialog['RowSequence']['onChange'], $sequenceDialog, []);
$check($module->formUpdates['Choice']['value'] === 'sequence:' . $sequenceID, 'Native sequence callback validates and binds the reference');
$module->SelectReusableSequence($sequenceID, '', false);
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(!$sequenceForm['SequenceDeleteButton']['enabled'] && $sequenceForm['SequenceEditorList']['values'][0]['Usage'] === 2, 'Usage counts protect a definition shared by two automations');
$unchangedSequenceDraft = $sequenceForm['YamlInput']['value'];
$check(!$module->DeleteReusableSequence($sequenceID, $sequenceForm['SequenceEditorToken']['value'], $unchangedSequenceDraft, true)
    && $state()['lastError'] === 'sequence_in_use', 'Public deletion cannot bypass the in-use protection');
$check(!$module->SaveReusableSequence(
    $sequenceID,
    $sequenceForm['SequenceEditorToken']['value'],
    'Synthetic oversized definition',
    json_encode(array_fill(0, 9, ['Choice' => $sequenceOn, 'Delay' => 0]), JSON_THROW_ON_ERROR),
    $unchangedSequenceDraft,
    true
)
    && $state()['lastError'] === 'sequence_limit', 'Editing a shared definition checks the expanded limit of every referencing rule');
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($sequenceForm['YamlInput']['value'] === $unchangedSequenceDraft, 'Rejected changes preserve the whole definition and all callers');
$module->ValidateAutomationDraft();
$check($state()['operation']['status'] === 'validated', 'Generated sequence document validates through the real JavaScript receiver');
$module->StoreAutomationDraft();
$check($state()['operation']['status'] === 'stored', 'Definitions and references are persisted together without activation');
$module->LoadAutomationEditor();
$module->SelectReusableSequence($sequenceID, '', false);
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($sequenceForm['SequenceEditorName']['value'] === 'Synthetic reusable edited plan'
    && $sequenceForm['SequenceEditorActions']['values'] === $sequenceSteps, 'Stored definition reloads with its edited order and delays');
$targetSequenceVariable = 0;
foreach (IPS_GetInstanceListByModuleID('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}') as $deviceID) {
    if (IPS_GetProperty($deviceID, 'IEEE') === '0x0000000000000002' && IPS_GetInstance($deviceID)['ConnectionID'] === $parentID) {
        $targetSequenceVariable = IPS_GetObjectIDByIdent('state', $deviceID);
        break;
    }
}
$check($targetSequenceVariable > 0, 'Synthetic target variable is resolved by its device identity and gateway');
IPS_SetVariableCustomAction($targetSequenceVariable, 1);
$deniedSequenceDialog = formFields(json_decode($module->GetEditorItemForm('TimeEditorActions', 'sequence:' . $sequenceID, 2, 'eq', false), true, 512, JSON_THROW_ON_ERROR));
$check($deniedSequenceDialog['Choice']['value'] === '', 'A sequence cannot bypass switching permissions revoked after storage');
IPS_SetVariableCustomAction($targetSequenceVariable, 0);
foreach ($sequenceCallers as $caller) {
    $module->SelectAutomation($caller, '', false, 'time');
    $sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
    $check($module->DeleteAutomation($caller, $sequenceForm['TimeEditorEditToken']['value'], $sequenceForm['YamlInput']['value'], true, 'time'), 'Callers can be removed without deleting the shared definition');
}
$module->SelectReusableSequence($sequenceID, '', false);
$sequenceForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$runEditorScript($sequenceForm['SequenceDeleteButton']['onClick'], $sequenceForm, []);
$check($state()['operation']['status'] === 'sequence_deleted', 'Unused sequence can be deleted through its native button');
$sequenceOriginal['revision'] = $state()['extension']['storedRevision'] + 1;
$sequenceRestore = json_encode($sequenceOriginal, JSON_THROW_ON_ERROR);
$module->ValidateYaml($sequenceRestore);
$module->StoreYaml($sequenceRestore);
$module->LoadAutomationEditor();
unset($sequenceForm, $sequenceOriginal, $sequenceOn, $sequenceOff, $sequenceSteps, $sequenceID, $sequenceCallers, $references, $document,
    $sequenceDialog, $unchangedSequenceDraft, $targetSequenceVariable, $deniedSequenceDialog, $sequenceRestore);
