<?php

declare(strict_types=1);

// Real module -> MQTT data flow -> generated JavaScript artifact -> native debug.
$check($module->ActivateAutomations() !== '', 'Debug recording does not require rewriting stored automations');
$runtimeBeforeDebug = $state()['extension'];
$check(!$module->StartRuntimeDebug(901), 'Recording rejects unbounded durations');
$check($module->StartRuntimeDebug(300), 'A valid timed recording is accepted');
$lease = $module->DebugLease();
$check($lease['active'] && $lease['pending'] === '', 'Only a matching fresh extension reply confirms recording');
$check($module->RequestExtensionStatus() !== '' && $module->DebugLease()['active'], 'Ordinary manual status refresh preserves the active recording');
$check($state()['extension'] === $runtimeBeforeDebug, 'Recording leaves automation runtime state unchanged');
$recordingForm = formFields(json_decode($module->GetConfigurationForm(), true, 512, JSON_THROW_ON_ERROR)['actions']);
$check($recordingForm['RuntimeDebugStop']['enabled'] && str_contains($recordingForm['RuntimeDebugInfo']['caption'], 'Aufzeichnung läuft'), 'Reopened form shows confirmed recording');
$recordingMessages = $exchange(['control' => 'event', 'device' => '0x0000000000000001', 'payload' => ['action' => 'single']]);
$beforeDebug = count($module->debugMessages);
$deliver($recordingMessages);
$recorded = array_slice($module->debugMessages, $beforeDebug);
$capturedDebug = implode('\n', array_column($recorded, 1));
$check(str_contains($capturedDebug, 'Z2M: Auslöser geprüft.') && str_contains($capturedDebug, 'Regellauf')
    && str_contains($capturedDebug, 'Aktionsschritt') && str_contains($capturedDebug, 'native Z2M-Befehlsverarbeitung'), 'Executed rule arrives in native German debug with trace and step');
$stream = array_values(array_filter($recordingMessages, static fn (object $m): bool => str_ends_with($m->topic, '/automations/debug')));
$check(count($stream) >= 5, 'A full simple flow is forwarded by the generated artifact');
$countRuntimeLines = static fn (): int => count(array_filter($module->debugMessages, static fn (array $line): bool => str_contains($line[1], 'Z2M: ')));
$count = $countRuntimeLines();
$deliver([$stream[0], $stream[0]]);
$check($countRuntimeLines() === $count, 'Repeated MQTT records are deduplicated');
$invalid = clone $stream[0];
$invalid->payload = clone $invalid->payload;
$invalid->payload->record = clone $invalid->payload->record;
$invalid->payload->record->id = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$invalid->payload->record->message = 'synthetic-secret-message';
$deliver([$invalid]);
$check(!str_contains(implode('\n', array_column($module->debugMessages, 1)), 'synthetic-secret-message'), 'Untrusted messages cannot inject arbitrary debug text');
$retained = clone $stream[0];
$retained->retain = true;
$deliver([$retained]);
$check($countRuntimeLines() === $count, 'Retained debug is never replayed');
$check($module->StopRuntimeDebug() && $module->DebugLease() === [], 'Own recording is stopped through a correlated acknowledgement');
$deliver($stream);
$check($countRuntimeLines() === $count, 'No forwarding occurs after stop');
$check($module->StartRuntimeDebug(1), 'Short programmatic recording starts');
$module->debugNow = time() + 2;
$module->ExpireRuntimeDebug();
$module->debugNow = null;
$check($module->DebugLease() === [] && str_contains($module->formUpdates['RuntimeDebugInfo']['caption'], 'automatisch beendet'), 'Expiry timer ends local forwarding and updates the form without a new message');

$send = $parent->onPublish;
$parent->onPublish = static function (array $packet) use ($send): void
{
    if (!str_ends_with($packet['Topic'], '/request/debug')) {
        $send($packet);
    }
};
$check($module->StartRuntimeDebug(60), 'Missing acknowledgement leaves recording pending');
$pending = $module->DebugLease();
$response = ['protocol_version' => 1, 'transaction' => $pending['pending'], 'status' => 'ok',
    'data'                      => ['debug_version' => 1, 'client' => $pending['client'], 'seconds' => 60, 'active' => true]];
$parent->publish('synthetic/z2m/symcon/automations/response/debug', $response, true);
$check(!$module->DebugLease()['active'], 'Retained control acknowledgement cannot confirm recording');
$response['transaction'] = 'synthetic-wrong-transaction';
$parent->publish('synthetic/z2m/symcon/automations/response/debug', $response);
$check(!$module->DebugLease()['active'], 'Unrelated acknowledgement cannot confirm recording');
$module->debugNow = time() + 121;
$module->ExpireRuntimeDebug();
$module->debugNow = null;
$check($module->DebugLease() === [] && str_contains($module->formUpdates['RuntimeDebugInfo']['caption'], 'Keine Bestätigung'), 'Timed-out control requests do not stay active');
$parent->onPublish = $send;
$check($module->StartRuntimeDebug(60), 'Recording can recover after a timeout');
IPS_ApplyChanges($id);
$check($module->DebugLease() === [], 'Apply changes cancels local recording instead of silently renewing it');
$check($module->StartRuntimeDebug(60), 'Recording restarts explicitly after apply');
$deliver($exchange(['control' => 'restart']));
$check($module->DebugLease() === [], 'A changed extension session cancels recording without enabling it again');
$check($module->StopAutomations() !== '', 'Debug test restores stopped automation runtime');

unset($recordingForm, $recordingMessages, $recorded, $capturedDebug, $stream, $invalid, $retained, $response, $pending, $send, $lease, $runtimeBeforeDebug);
