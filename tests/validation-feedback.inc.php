<?php

declare(strict_types=1);

// The real JS receiver reports all affected rules; the module resolves names only locally.
$invalid = ['schema_version' => 1, 'revision' => 2, 'automations' => []];
foreach (range(0, 49) as $n) {
    $invalid['automations'][] = ['id' => 'synthetic_' . $n, 'name' => 'Synthetic private validation ' . $n, 'enabled' => false,
        'triggers'                    => [['type' => 'time', 'at' => '12:00:00', 'timezone' => 'Europe/Berlin']], 'conditions' => [],
        'actions'                     => [['type' => 'toggle', 'target' => ['type' => 'group', 'id' => 7], 'property' => 'state', 'delay_seconds' => 0]]];
}
$invalid['automations'][41]['actions'][0]['delay_seconds'] = -1;
$invalid['automations'][48]['triggers'][0]['at'] = 'synthetic-private-invalid-time';
$module->ValidateYaml(json_encode($invalid, JSON_THROW_ON_ERROR));
$validationFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$failureText = $validationFields['ErrorInfo']['caption'];
$check($state()['lastError'] === 'invalid_schema' && str_contains($failureText, 'Automation 42 „Synthetic private validation 41“')
    && str_contains($failureText, 'Action 1') && str_contains($failureText, 'at least 0')
    && str_contains($failureText, 'Automation 49 „Synthetic private validation 48“'), 'Validation locates multiple failing rules, names and action/trigger positions among fifty entries');
$check(
    $module->formUpdates['ErrorInfo']['caption'] === $failureText && GetValue(IPS_GetObjectIDByIdent('LastError', $id)) === $failureText,
    'Live updates, reopened form and status variable show the same diagnostics'
);
$check(
    !str_contains($module->GetTransportState(), 'Synthetic private validation') && !str_contains($failureText, 'synthetic-private-invalid-time'),
    'State export omits rule names and diagnostics never repeat invalid values'
);
$feedbackDebug = implode("\n", array_column($module->debugMessages, 1));
$check(str_contains($feedbackDebug, '/automations/41/actions/0/delay_seconds') && !str_contains($feedbackDebug, 'Synthetic private validation')
    && !str_contains($feedbackDebug, 'synthetic-private-invalid-time'), 'Native debug includes exact positions and causes without names or values');
$check($state()['extension']['storedRevision'] === 1 && !$state()['extension']['executing'], 'Rejected draft does not modify stored rules or execution');
$translations = json_decode(file_get_contents(dirname(__DIR__) . '/Z2MAutomations/locale.json'), true, 512, JSON_THROW_ON_ERROR)['translations']['de'];
$details = $state()['validationDetails'];
$german = \Z2MAutomations\ValidationFeedback::Text($details, static fn (string $text): string => $translations[$text] ?? $text);
$check(str_contains($german, 'Aktion 1') && str_contains($german, 'mindestens 0') && str_contains($german, 'Wartezeit'), 'German feedback uses understandable locations and constraints');
foreach ($details['issues'] as $issue) {
    $check(\Z2MAutomations\ValidationFeedback::Reason($issue, static fn (string $text): string => $translations[$text] ?? $text) !== '', 'Each rejected entry has a readable cause');
}
$module->ValidateYaml($yaml);
$validationFields = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check(
    $state()['operation']['status'] === 'validated' && $validationFields['ErrorInfo']['caption'] === '' && ($state()['validationDetails'] ?? null) === null,
    'A successful recheck removes all old diagnostic entries'
);
foreach ([
    ['issues' => [['path' => '/automations/0/private-secret', 'reason' => 'required']], 'truncated' => false],
    ['issues' => [['path' => '/automations/0/name', 'reason' => 'private-secret']], 'truncated' => false],
    ['issues' => [['path' => '/automations/0/name', 'reason' => 'maxLength', 'limit' => 'private-secret']], 'truncated' => false],
    ['issues' => [['path' => '', 'reason' => 'yaml_syntax', 'line' => -1]], 'truncated' => false],
    ['issues' => array_fill(0, 21, ['path' => '', 'reason' => 'type']), 'truncated' => false]
] as $untrusted) {
    $check(\Z2MAutomations\ValidationFeedback::Normalize(json_decode(json_encode($untrusted))) === null, 'Malformed diagnostic payload cannot inject arbitrary text or unbounded output');
}
$untrusted = (object) ['issues' => [(object) ['path' => '/revision', 'reason' => 'minimum', 'limit' => 1, 'message' => 'private-secret']], 'truncated' => true];
$clean = \Z2MAutomations\ValidationFeedback::Normalize($untrusted);
$check(
    !str_contains(json_encode($clean), 'private-secret') && str_contains(\Z2MAutomations\ValidationFeedback::Text($clean, static fn (string $text): string => $text), 'first 20'),
    'Unknown diagnostic fields are discarded and truncation is explicitly explained'
);
