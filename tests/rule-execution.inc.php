<?php

declare(strict_types=1);

$module->LoadAutomationEditor();
$singleForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$singleOriginal = json_decode($singleForm['YamlInput']['value'], true, 32, JSON_THROW_ON_ERROR);
$singleRule = ['id' => 'synthetic_once', 'name' => 'Synthetic private once name', 'enabled' => false,
    'triggers'      => [['type' => 'device', 'device' => '0x0000000000000001', 'property' => 'action', 'operator' => 'eq', 'value' => 'single']],
    'conditions'    => [], 'actions' => [['type' => 'set', 'target' => ['type' => 'device', 'id' => '0x0000000000000002'], 'values' => ['state' => 'ON'], 'delay_seconds' => 30]]];
$singleDocument = ['schema_version' => 1, 'revision' => $state()['extension']['storedRevision'] + 1, 'automations' => [$singleRule]];
$singleYaml = json_encode($singleDocument, JSON_THROW_ON_ERROR);
$module->ValidateYaml($singleYaml);
$check($state()['operation']['status'] === 'validated', 'Synthetic one-shot document validates');
$module->StoreYaml($singleYaml);
$module->LoadAutomationEditor();
$singleForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$runEditorScript($singleForm['ExecutionRule']['onChange'], $singleForm, ['ExecutionRule' => $singleRule['id']]);
$singleForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($singleForm['RunOnceButton']['enabled'] && !$singleForm['StopRuleButton']['enabled'], 'Saved disabled rule is selectable for one-shot execution');
$singleResume = array_intersect_key($state()['extension'], array_flip(['resumeEnabled', 'resumeStatus', 'executing']));
$runEditorScript($singleForm['RunOnceButton']['onClick'], $singleForm, []);
$check($state()['operation']['status'] === 'run_started', 'Native start button completes fresh status and one-shot command through the real receiver: ' . json_encode([$state()['operation'], $state()['lastError']], JSON_THROW_ON_ERROR));
$check(($state()['execution']['runs']['synthetic_once']['state'] ?? '') === 'running', 'Run receipt updates the selected automation status');
$singleForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(!$singleForm['RunOnceButton']['enabled'] && $singleForm['StopRuleButton']['enabled'], 'Only stop is offered for a running rule');
$singleSnapshot = $state()['execution'];
$singleWire = ['protocol_version' => 1, 'execution_version' => 1, 'session' => $singleSnapshot['session'], 'revision' => $singleSnapshot['revision'],
    'checksum'                    => $singleSnapshot['checksum'], 'sequence' => $singleSnapshot['sequence'] - 1, 'runs' => array_values($singleSnapshot['runs'])];
$singleWire['runs'][0]['state'] = 'completed';
$parent->publish('synthetic/z2m/symcon/automations/runs', $singleWire);
$check($state()['execution'] === $singleSnapshot, 'Late out-of-order status cannot overwrite the newer running state');
$singleWire['sequence'] += 10;
$parent->publish('synthetic/z2m/symcon/automations/runs', $singleWire, true);
$check($state()['execution'] === $singleSnapshot, 'Retained status cannot authorize or overwrite a live run');
$singleWire['runs'][0]['error'] = 'private-invalid-body';
$parent->publish('synthetic/z2m/symcon/automations/runs', $singleWire);
$check($state()['execution'] === $singleSnapshot, 'Arbitrary remote text is rejected before debug and status');
$runEditorScript($singleForm['StopRuleButton']['onClick'], $singleForm, []);
$check($state()['operation']['status'] === 'run_stopped' && $state()['execution']['runs']['synthetic_once']['state'] === 'stopped', 'Native stop button cancels exactly the confirmed saved run');
$check(array_intersect_key($state()['extension'], $singleResume) === $singleResume, 'One-shot controls preserve global runtime and durable restart status');
$check($module->StopAutomationRun('synthetic_once') !== '' && $state()['lastError'] === 'run_conflict', 'An already stopped run is not silently restarted or stopped again');
$check($module->RunAutomationOnce('synthetic_once', $singleForm['YamlInput']['value'] . '# unsaved', true) === ''
    && $state()['lastError'] === 'execution_draft', 'Unsaved YAML is rejected without a one-shot request');
$singleDebug = json_encode($module->debugMessages, JSON_THROW_ON_ERROR);
$check(str_contains($singleDebug, 'Einmaligen Start') && str_contains($singleDebug, 'Regellaufs')
    && !str_contains($singleDebug, $singleRule['name']) && !str_contains($singleDebug, 'private-invalid-body'), 'Safe German debug covers one-shot control without names or rejected arbitrary text');
$singleOriginal['revision'] = $state()['extension']['storedRevision'] + 1;
$singleRestore = json_encode($singleOriginal, JSON_THROW_ON_ERROR);
$module->ValidateYaml($singleRestore);
$module->StoreYaml($singleRestore);
$module->LoadAutomationEditor();
unset($singleForm, $singleOriginal, $singleRule, $singleDocument, $singleYaml, $singleResume, $singleSnapshot, $singleWire, $singleDebug, $singleRestore);
