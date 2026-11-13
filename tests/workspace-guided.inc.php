<?php

declare(strict_types=1);

use Z2MAutomations\SystemContract;

$beforeGuided = $manager->RawWorkspace();
$check($manager->Hook('workspace-status', '')[0] === 403, 'Guided setup requires an authenticated session');
[$code, $raw] = $manager->Hook('workspace-status', $token);
$guided = $decode($raw);
$check($code === 200 && $guided['ok'] && count($guided['choices']) > 0, 'Guided setup lists existing connections without configuring them');
$check($manager->RawWorkspace() === $beforeGuided, 'Opening setup leaves workspace documents untouched');
$selectedConnections = array_column(array_filter($guided['choices'], static fn (array $row): bool => $row['Use']), 'Connection');
$guidedBody = ['requestId' => '99999999-9999-4999-8999-999999999991', 'fingerprint' => $guided['fingerprint'], 'candidates' => $guided['candidates'], 'connections' => array_values($selectedConnections)];
$manager->requestBody = json_encode($guidedBody + ['shell' => 'unsupported']);
$check($manager->Hook('workspace-configure', $token, [], 'POST')[0] === 409 && $manager->RawWorkspace() === $beforeGuided, 'Extra setup fields cannot reach configuration');
$manager->requestBody = json_encode(array_replace($guidedBody, ['candidates' => str_repeat('0', 64)]));
$check($manager->Hook('workspace-configure', $token, [], 'POST')[0] === 409 && $manager->RawWorkspace() === $beforeGuided, 'Stale setup choices cannot modify the workspace');
$manager->SetupAccess($token, false);
$manager->requestBody = json_encode($guidedBody);
$check($manager->Hook('workspace-configure', $token, [], 'POST')[0] === 403 && $manager->Hook('workspace-status', $token)[0] === 403, 'A read/edit session without setup permission cannot assign systems');
$check($manager->RawWorkspace() === $beforeGuided, 'Denied setup preserves every existing document');
$manager->SetupAccess($token, true);
$beforeGuidedA = $detail($a)['data'];
$beforeGuidedB = $detail($b)['data'];
[$code, $raw] = $manager->Hook('workspace-configure', $token, [], 'POST');
$check($code === 200 && $decode($raw)['ok'], 'Confirmed setup delegates to the existing checked assignment path: ' . $raw);
$check($detail($a)['data'] === $beforeGuidedA && $detail($b)['data'] === $beforeGuidedB, 'Setup preserves documents, rule identities and grants');
$check($manager->Hook('data', $token)[0] === 200, 'The explicitly configuring session remains usable');
$check($manager->Hook('workspace-configure', $token)[0] === 405, 'Configuration cannot be invoked with a read request');
$manager->requestBody = '';

// An actual new assignment updates only the explicitly configuring session.
$guidedManagerID = IPS_CreateInstance(SystemContract::SOURCE_MODULE);
IPS_ApplyChanges($guidedManagerID);
$guidedManager = IPS\InstanceManager::getInstanceInterface($guidedManagerID);
$guidedConnections = [];
for ($i = 0; $i < 2; $i++) {
    $connection = IPS_CreateInstance(SystemContract::CONNECTION_MODULE);
    IPS_SetProperty($connection, 'BaseTopic', 'synthetic-guided-' . $i);
    IPS_ConnectInstance($connection, $parentID);
    IPS_ApplyChanges($connection);
    $guidedConnections[] = $connection;
}
$guidedOverview = $decode($guidedManager->GetWorkspaceOverview());
$check($decode($guidedManager->ConfigureWorkspace(json_encode([$guidedConnections[0]]), $guidedOverview['fingerprint'], $op()))['ok'], 'Isolated guided assignment fixture starts with one system');
$guidedToken = substr(strrchr($guidedManager->OpenAutomationDesigner(), '#'), 1);
$oldGuidedToken = substr(strrchr($guidedManager->OpenAutomationDesigner(), '#'), 1);
[$code, $raw] = $guidedManager->Hook('workspace-status', $guidedToken);
$choices = $decode($raw);
$guidedManager->requestBody = json_encode(['requestId' => '99999999-9999-4999-8999-999999999992', 'fingerprint' => $choices['fingerprint'], 'candidates' => $choices['candidates'], 'connections' => $guidedConnections]);
[$code, $raw] = $guidedManager->Hook('workspace-configure', $guidedToken, [], 'POST');
$check($code === 200 && $decode($raw)['ok'], 'A current explicit setup adds the selected existing connection: ' . $raw);
$check(count($decode($guidedManager->GetWorkspaceOverview())['systems']) === 2, 'The second system is persisted');
$check($guidedManager->Hook('data', $guidedToken)[0] === 200 && $guidedManager->Hook('data', $oldGuidedToken)[0] === 403, 'Only the approved setup session receives the changed scope');
