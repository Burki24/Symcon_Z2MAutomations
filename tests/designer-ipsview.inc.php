<?php

declare(strict_types=1);

// Uses the same eight-system fixture as the native and browser contract tests.
$ipsBefore = $manager->RawWorkspace();
$ipsSent = count($parent->sent);
$ipsForm = $decode($manager->GetConfigurationForm());
$check(($ipsForm['elements'][0]['caption'] ?? '') === 'IPSView-Ansicht'
    && ($ipsForm['elements'][0]['items'][0]['name'] ?? '') === 'EnableIPSView', 'IPSView properties belong to persistent form elements, not transient actions');
$check($manager->GetIPSViewHTML() === '' && $manager->Hook('ipsview.html', '')[0] === 404, 'IPSView is opt-in and has no public embedded page while disabled');
IPS_SetProperty($managerID, 'EnableIPSView', true);
IPS_SetProperty($managerID, 'IPSViewServerURL', 'https://symcon.example:3777');
IPS_ApplyChanges($managerID);
$ipsVariable = IPS_GetObjectIDByIdent('IPSViewDesigner', $managerID);
$ipsHtml = GetValueString($ipsVariable);
$ipsConfig = static function (string $html): array
{
    if (!preg_match('/window\.Z2MAIPSView = (\{.*?\});<\/script>/s', $html, $match)) throw new RuntimeException('Missing IPSView bootstrap.');
    return json_decode($match[1], true, 16, JSON_THROW_ON_ERROR)['runtime'];
};
$ipsGrant = $ipsConfig($ipsHtml)['grant'];
$ipsReadAttribute = new ReflectionMethod($manager, 'ReadAttributeString');
$check($ipsReadAttribute->invoke($manager, 'IPSViewAccessHash') === hash('sha256', $ipsGrant)
    && !str_contains($ipsReadAttribute->invoke($manager, 'IPSViewAccessScope'), $ipsGrant), 'Entry attributes contain only a hash and scope, never the clear capability');
$check(preg_match('/\A[a-f0-9]{64}\z/D', $ipsGrant) === 1 && str_contains($ipsHtml, 'SymconVisualizationInteraction')
    && str_contains($ipsHtml, 'SymconHTTPOrigin') && str_contains($ipsHtml, 'name="burki24.wheel.v1"') && !str_contains($ipsHtml, 'window.open('), 'Helper renders a self-contained full-designer HTMLBox with central wheel and origin support');
$check(IPS_GetVariable($ipsVariable)['VariableType'] === VARIABLETYPE_STRING && strlen($ipsHtml) < 1048576, 'Optional output is a bounded native String variable');
$check($manager->Hook('ipsview.html', '')[0] === 200 && str_contains($manager->Hook('ipsview.html', '')[1], 'defer src="startup.js"'), 'IPSView serves the same complete designer and recoverable startup');
$manager->requestBody = '{}';
$check($manager->Hook('data', $ipsGrant)[0] === 403, 'The persisted entry grant is never a draft read or write capability');
$nativeToken = explode('#', $manager->OpenAutomationDesigner())[1];
[$ipsCode, $ipsBody] = $manager->Hook('ipsview-access', $ipsGrant, [], 'POST');
$ipsAccess = $decode($ipsBody);
$check($ipsCode === 200 && $ipsAccess['ok'] && $ipsAccess['token'] !== $ipsGrant, 'Authorized same-origin entry issues a separate short-lived session');
$ipsToken = $ipsAccess['token'];
[$ipsCode, $ipsBody] = $manager->Hook('data', $ipsToken);
$ipsData = $decode($ipsBody);
$check($ipsCode === 200 && count($ipsData['workspace']['systems']) === 8 && $ipsData['workspaceContext']['systemId'] === $overview()['selectedSystem'], 'All eight systems and the current selection are available inside IPSView');
$check($manager->Hook('access-renew', $ipsToken, [], 'POST')[0] === 200, 'IPSView uses the existing in-place access renewal');
$ipsReadBuffer = new ReflectionMethod($manager, 'GetBuffer');
$ipsWriteBuffer = new ReflectionMethod($manager, 'SetBuffer');
$ipsSessions = $decode($ipsReadBuffer->invoke($manager, 'DesignerSessions'));
foreach ($ipsSessions as &$ipsSession) if ($ipsSession['hash'] === hash('sha256', $ipsToken)) $ipsSession['expires'] = 0;
unset($ipsSession);
$ipsWriteBuffer->invoke($manager, 'DesignerSessions', json_encode($ipsSessions));
$check($manager->Hook('data', $ipsToken)[0] === 403 && $manager->Hook('access-renew', $ipsToken, [], 'POST')[0] === 403, 'An expired IPSView session cannot renew itself');
[$ipsCode, $ipsBody] = $manager->Hook('ipsview-access', $ipsGrant, [], 'POST');
$ipsToken = $decode($ipsBody)['token'];
$check($ipsCode === 200 && $manager->Hook('data', $ipsToken)[0] === 200, 'Later HTMLBox reopening can use the retained entry to obtain a fresh session');
$check($manager->Hook('ipsview-access', $ipsGrant, ['HTTP_ORIGIN' => 'https://foreign.example'], 'POST')[0] === 403
    && $manager->Hook('ipsview-access', $ipsGrant, ['HTTP_SEC_FETCH_SITE' => 'cross-site'], 'POST')[0] === 403
    && $manager->Hook('ipsview-access', $ipsGrant, ['CONTENT_TYPE' => 'text/plain'], 'POST')[0] === 415, 'Entry exchange retains origin, fetch-site and JSON checks');
$manager->requestBody = '{"operation":"activate"}';
$check($manager->Hook('ipsview-access', $ipsGrant, [], 'POST')[0] === 400, 'IPSView entry cannot carry arbitrary commands');
$manager->requestBody = '{}';
$check($manager->Hook('ipsview-access?token=' . $ipsGrant, '', [], 'POST')[0] === 404
    && $manager->Hook('ipsview-access', str_repeat('0', 64), [], 'POST')[0] === 403, 'Query tokens and unknown grants are rejected');
$manager->RequestAction('IPSViewHTMLRegenerateVariables', '');
$ipsNewGrant = $ipsConfig(GetValueString($ipsVariable))['grant'];
$check($ipsNewGrant !== $ipsGrant && $manager->Hook('ipsview-access', $ipsGrant, [], 'POST')[0] === 403
    && $manager->Hook('data', $ipsToken)[0] === 403 && $manager->Hook('access-renew', $ipsToken, [], 'POST')[0] === 403, 'Explicit regeneration revokes old entry grants and their existing sessions');
$check($manager->Hook('data', $nativeToken)[0] === 200, 'IPSView regeneration preserves an independent browser session');
$ipsScope = $decode($manager->RawWorkspace());
$ipsScope['systems'][$a]['binding']['generation']++;
try {
    $manager->ReplaceWorkspace(json_encode($ipsScope));
    $check($manager->Hook('ipsview-access', $ipsNewGrant, [], 'POST')[0] === 403, 'A changed system binding revokes the persisted entry grant');
} finally {
    $manager->ReplaceWorkspace($ipsBefore);
}
$check($manager->RawWorkspace() === $ipsBefore && count($parent->sent) === $ipsSent, 'Rendering, exchanging and renewing IPSView access never change drafts, system selection or MQTT');
IPS_SetProperty($managerID, 'EnableIPSView', false);
IPS_ApplyChanges($managerID);
$check(IPS_VariableExists($ipsVariable) && GetValueString($ipsVariable) !== '' && $manager->Hook('ipsview.html', '')[0] === 404
    && $manager->Hook('ipsview-access', $ipsNewGrant, [], 'POST')[0] === 403, 'Disabling retains the HTML variable while revoking access');
$ipsDebug = implode("\n", $manager->debugMessages);
foreach ([$ipsGrant, $ipsNewGrant, $ipsToken] as $ipsSecret) $check(!str_contains($ipsDebug, $ipsSecret), 'IPSView capabilities never enter native debug');
$check(str_contains($ipsDebug, 'IPSView-Einstieg bestätigt') && str_contains($ipsDebug, 'IPSView-Zugang abgelehnt') && str_contains($ipsDebug, 'IPSView-Ausgabe ausgeschaltet'), 'German debug explains successful access, rejection and opt-out');
$ipsLinkCases = ['/////', 'https://user:synthetic@host.example', 'https://host.example/console/', 'https://host.example?target=other', 'https://host.example#fragment', 'javascript:alert(1)'];
foreach ($ipsLinkCases as $ipsLinkCase) {
    IPS_SetProperty($managerID, 'EnableIPSView', true);
    IPS_SetProperty($managerID, 'IPSViewServerURL', $ipsLinkCase);
    IPS_ApplyChanges($managerID);
    $check($ipsConfig(GetValueString($ipsVariable))['grant'] === ''
        && $manager->Hook('ipsview-access', $ipsNewGrant, [], 'POST')[0] === 403, 'Invalid or credential-bearing server addresses cannot expose an entry');
}
IPS_SetProperty($managerID, 'IPSViewServerURL', 'https://symcon.example:3777');
IPS_ApplyChanges($managerID);
$manager->ipsAssetAvailable = false;
$check($manager->GetIPSViewHTML() === '' && $ipsReadAttribute->invoke($manager, 'IPSViewAccessHash') === '', 'Missing HTML assets cannot leave a usable newly issued entry');
$manager->ipsAssetAvailable = true;
IPS_SetProperty($managerID, 'EnableIPSView', false);
IPS_ApplyChanges($managerID);
echo "IPSView full-designer access, eight systems, revocation, input isolation and debug passed.\n";
