<?php

declare(strict_types=1);

// Public hook lifecycle: real deadlines, no draft writes, no capability escalation.
$renewToken = explode('#', $module->OpenAutomationDesigner())[1];
$renewStart = $module->clock;
$renewBefore = $module->Fingerprint();
$module->MakeReadOnly($renewToken);
$rightsBefore = $module->AccessSessions();
$module->requestBody = '{}';
$module->clock += 800;
[$http, $raw] = $module->Hook('access-renew', 'POST', $renewToken);
$renewResult = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
$check($http === 200 && $renewResult['expires'] === $module->clock + 900, 'Active access is extended without replacing its capability');
$rightsAfter = $module->AccessSessions();
$renewHash = hash('sha256', $renewToken);
$beforeSession = array_values(array_filter($rightsBefore, static fn (array $item): bool => $item['hash'] === $renewHash))[0];
$afterSession = array_values(array_filter($rightsAfter, static fn (array $item): bool => $item['hash'] === $renewHash))[0];
unset($beforeSession['expires'], $afterSession['expires']);
$check($beforeSession === $afterSession, 'Renewal cannot add write capabilities or replace scope, receipts, identity and hard deadline');
$check($module->Fingerprint() === $renewBefore && !str_contains($raw, $renewToken), 'Renewal preserves drafts, open input and transport, and never echoes tokens');
$module->clock += 101;
$check($module->Hook('data', 'GET', $renewToken)[0] === 200, 'Same bearer survives its former deadline after renewal');
$module->requestBody = '{"systemId":"other"}';
$check($module->Hook('access-renew', 'POST', $renewToken)[0] === 400, 'Renewal accepts no caller-provided scope or system');
$module->requestBody = '{}';
$_SERVER['HTTP_ORIGIN'] = 'https://other.example.invalid';
$check($module->Hook('access-renew', 'POST', $renewToken)[0] === 403, 'Cross-origin renewal is rejected');
$_SERVER['HTTP_ORIGIN'] = 'http://127.0.0.1:3777';
$check($module->Hook('access-renew', 'GET', $renewToken)[0] === 405, 'Renewal is a guarded JSON POST');
$module->clock += 900;
$check($module->Hook('access-renew', 'POST', $renewToken)[0] === 403, 'Expired access cannot be revived');
$renewToken = explode('#', $module->OpenAutomationDesigner())[1];
$hardStart = $module->clock;
for ($elapsed = 800; $elapsed < 28800; $elapsed += 800) {
    $module->clock = $hardStart + $elapsed;
    [$http, $raw] = $module->Hook('access-renew', 'POST', $renewToken);
    $check($http === 200 && json_decode($raw, true)['expires'] <= $hardStart + 28800, 'Renewal respects the absolute eight-hour limit');
}
$module->clock = $hardStart + 28800;
$check($module->Hook('access-renew', 'POST', $renewToken)[0] === 403, 'Continuous use still expires at the hard deadline');
$module->clock = $renewStart;
$renewToken = explode('#', $module->OpenAutomationDesigner())[1];
IPS_ApplyChanges($id);
$check($module->Hook('access-renew', 'POST', $renewToken)[0] === 403, 'Module updates invalidate old access, including renewal');
$check($module->Hook('access.js')[0] === 200 && $module->Hook('tile.js')[0] === 404, 'Only the shared browser asset is publicly served; native tile resources are embedded');

$check($module->Hook('embedding.js')[0] === 200, 'Native embedding uses an explicitly allowed module asset');
[$interactionHttp, $interactionBody] = $module->Hook('interaction.js');
$check($interactionHttp === 200 && $interactionBody === file_get_contents(dirname(__DIR__) . '/libs/helper/visualization/interaction.min.js'), 'The fixed interaction route serves the unchanged canonical helper resource');

// Removing an integration must not promote its still-buffered sessions to browser access.
$browserToken = explode('#', $module->OpenAutomationDesigner())[1];
$retiredToken = explode('#', $module->OpenAutomationDesigner())[1];
$retiredSessions = $module->AccessSessions();
foreach ($retiredSessions as &$retiredSession) if ($retiredSession['hash'] === hash('sha256', $retiredToken)) $retiredSession['ipsViewGrant'] = str_repeat('d', 64);
unset($retiredSession);
(new ReflectionMethod($module, 'SetBuffer'))->invoke($module, 'DesignerSessions', json_encode($retiredSessions));
$retiredBefore = $module->Fingerprint();
foreach (['ipsview.html', 'ipsview-access.js', 'ipsview-loader.html', 'ipsview-loader.css', 'ipsview-loader.js'] as $retiredAsset) {
    $check($module->Hook($retiredAsset)[0] === 404, 'Removed IPSView assets cannot be requested: ' . $retiredAsset);
}
$module->requestBody = '{}';
$check($module->Hook('ipsview-access', 'POST', $retiredToken)[0] === 404 && !method_exists($module, 'GetIPSViewHTML'), 'IPSView entry exchange and public HTML generation are removed');
$check($module->Hook('data', 'GET', $retiredToken)[0] === 403 && $module->Hook('access-renew', 'POST', $retiredToken)[0] === 403, 'Previously issued IPSView sessions cannot be reused or renewed after removal');
$check($module->Hook('data', 'GET', $browserToken)[0] === 200 && $module->Hook('access-renew', 'POST', $browserToken)[0] === 200, 'Regular browser and native-tile access remains available');
$check($module->Fingerprint() === $retiredBefore, 'Rejecting the retired integration preserves drafts and transport state');
