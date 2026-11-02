<?php

declare(strict_types=1);

// Changed storage through the real JavaScript receiver, with a deliberately delayed final status.
$debugGet = $exchange(['topic' => 'synthetic/z2m/symcon/automations/request/get',
    'payload'                  => ['protocol_version' => 1, 'transaction' => 'synthetic-debug-storage-get']]);
$debugGet = array_values(array_filter($debugGet, static fn (object $m): bool => str_ends_with($m->topic, '/response/get')))[0];
$debugDocument = json_decode($debugGet->payload->data->yaml, true, 32, JSON_THROW_ON_ERROR);
$debugDocument['revision']++;
$debugChangedYaml = json_encode($debugDocument, JSON_THROW_ON_ERROR);
$check($module->StartRuntimeDebug(60), 'Recording starts before changed storage');
$debugChangedLease = $module->DebugLease();
$check($module->ValidateYaml($debugChangedYaml) !== '', 'Changed revision validates without renewing the recording');
$debugWire = $parent->onPublish;
$debugHoldStatus = false;
$debugDeferredStatus = [];
$parent->onPublish = static function (array $packet) use ($debugWire, $exchange, &$debugHoldStatus, &$debugDeferredStatus): void
{
    if ($debugHoldStatus && str_ends_with($packet['Topic'], '/request/status')) {
        $debugDeferredStatus = $exchange(['topic' => $packet['Topic'], 'payload' => $packet['Payload']]);
        return;
    }
    if (str_ends_with($packet['Topic'], '/request/store')) {
        $debugHoldStatus = true;
    }
    $debugWire($packet);
};
try {
    $check($module->StoreYaml($debugChangedYaml) !== '' && $debugDeferredStatus !== [], 'Changed storage reaches the final status confirmation');
    $debugAfterStore = $module->DebugLease();
    $check(($debugAfterStore['active'] ?? false) && $debugAfterStore['client'] === $debugChangedLease['client']
        && $debugAfterStore['deadline'] === $debugChangedLease['deadline'], 'Changed storage retains the original recording client and expiry');
    $check(!$state()['extension']['executing'] && !$state()['runtimeConfirmed'], 'Preserved diagnostics never reactivate changed automations');
    $debugSaveLines = implode('\n', array_column($module->debugMessages, 1));
    $check(str_contains($debugSaveLines, 'laufende Ablaufaufzeichnung bleibt mit ihrer bisherigen Endzeit erhalten'), 'German debug explains retained recording during storage verification');
    $module->debugNow = $debugChangedLease['deadline'] + 1;
    $module->ExpireRuntimeDebug();
    $check($module->DebugLease() === [] && str_contains($module->formUpdates['RuntimeDebugInfo']['caption'], 'automatisch beendet'), 'Original expiry still ends recording when storage status never arrives');
    $module->ExpireRequest();
    $check($state()['operation']['status'] === 'stored_unconfirmed', 'Missing storage confirmation remains visible independently of recording expiry');
} finally {
    $module->debugNow = null;
    $parent->onPublish = $debugWire;
}
$exchange(['topic' => 'synthetic/z2m/symcon/automations/request/debug', 'payload' => ['protocol_version' => 1,
    'transaction'                                                                                        => 'synthetic-debug-storage-cleanup', 'session' => $debugChangedLease['session'], 'client' => $debugChangedLease['client'], 'seconds' => 0]]);
$deliver($debugDeferredStatus);
$check($state()['operation']['status'] === 'stored_unconfirmed' && $module->DebugLease() === [], 'Late storage confirmation does not restart an expired recording or complete a timed-out workflow');
$check($module->RequestExtensionStatus() !== '', 'Fresh status can recover independently after recording expiry');
$check($module->StartRuntimeDebug(60), 'Recording starts before a real parent disconnection');
IPS_DisconnectInstance($id);
$module->ExpireRuntimeDebug();
$check($module->DebugLease() === [], 'MQTT disconnection still invalidates the recording');
IPS_ConnectInstance($id, $parentID);
$parent->publish('synthetic/z2m/bridge/state', ['state' => 'online']);
$check($module->RequestExtensionStatus() !== '' && $state()['runtimeConfirmed'], 'Reconnection restores status without silently restarting recording');
$check($module->DebugLease() === [], 'An expired or disconnected recording is never restored automatically');

unset($debugGet, $debugDocument, $debugChangedYaml, $debugChangedLease, $debugWire, $debugHoldStatus,
    $debugDeferredStatus, $debugAfterStore, $debugSaveLines);
