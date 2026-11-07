<?php

declare(strict_types=1);

// The native SDK must not select the editor globally, change documents or emit MQTT.
$tileBefore = $manager->RawWorkspace();
$tileMqtt = count($parent->sent);
$tileShell = $manager->GetVisualizationTile();
$check(str_contains($tileShell, 'window.handleMessage') && str_contains($tileShell, 'SymconHTTPOrigin') && str_contains($tileShell, 'SymconVisualizationInteraction')
    && str_contains($tileShell, 'id="designer-frame"') && !str_contains($tileShell, 'window.open(')
    && !str_contains($tileShell, '/* TILE_') && !str_contains($tileShell, $token), 'Native tile is self-contained and starts without a browser capability');
$tileCall = static function (string $action, string $system) use ($manager): array
{
    $request = ['client' => str_repeat('1', 32), 'request' => bin2hex(random_bytes(16)), 'action' => $action, 'systemId' => $system];
    $manager->RequestAction('DesignerTile', json_encode($request));
    $reply = end($manager->tileMessages);
    if ($reply['client'] !== $request['client'] || $reply['request'] !== $request['request'] || $reply['action'] !== $action) throw new RuntimeException('Native replies must target their requesting view and operation.');
    return $reply;
};
$tileStatus = $tileCall('status', $b);
$check($tileStatus['ok'] && $tileStatus['state']['selectedSystem'] === $b && count($tileStatus['state']['systems']) === 8
    && $tileStatus['state']['systems'][1]['baseTopic'] === 'synthetic/system_1', 'Eight systems retain their independent names, topics and cached status in the tile');
$check(!isset($tileStatus['access']) && !isset($tileStatus['state']['systems'][0]['binding']), 'Status exposes neither capabilities nor connection credentials');
$tileAccess = $tileCall('access', $b);
$check($tileAccess['ok'] && $tileAccess['access']['systemId'] === $b && preg_match('/\A[a-f0-9]{64}\z/D', $tileAccess['access']['token']) === 1, 'Explicit tile action provides access for exactly its selected system');
[$tileHttp, $tileRaw] = $manager->Hook('data', $tileAccess['access']['token']);
$check($tileHttp === 200 && $decode($tileRaw)['workspaceContext']['systemId'] === $b, 'A newly opened designer initially reads the tile-selected system, without changing the native selection');
$check($manager->RawWorkspace() === $tileBefore && count($parent->sent) === $tileMqtt, 'Opening and selecting a tile preserves every draft, open native input, selected editor and MQTT state');
$check(!$tileCall('access', str_repeat('f', 32))['ok'], 'An unassigned system cannot receive designer access');
$tileReplyCount = count($manager->tileMessages);
$manager->RequestAction('StartAutomations', '{}');
$manager->RequestAction('DesignerTile', '{"action":"access"}');
$check(count($manager->tileMessages) === $tileReplyCount && count($parent->sent) === $tileMqtt, 'Tile dispatch rejects arbitrary actions and malformed requests without any MQTT side effect');
$check(!str_contains(implode("\n", $manager->debugMessages), $tileAccess['access']['token']), 'Native debug never reveals designer capabilities');
$manager->tileDelivery = false;
$tileCall('status', $b);
$manager->tileDelivery = true;
$check(str_contains(implode("\n", array_slice($manager->debugMessages, -4)), 'Antwort nicht an die Kachel übergeben'), 'Rejected SDK delivery is explained without reporting a successful send');

$tileScope = $decode($tileBefore);
$tileScope['systems'][$b]['binding']['generation']++;
try {
    $manager->ReplaceWorkspace(json_encode($tileScope));
    $manager->requestBody = '{}';
    $check($manager->Hook('access-renew', $tileAccess['access']['token'], [], 'POST')[0] === 403, 'A changed connection binding revokes existing access and its renewal');
} finally {
    $manager->ReplaceWorkspace($tileBefore);
}
