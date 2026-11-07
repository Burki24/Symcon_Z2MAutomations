<?php

declare(strict_types=1);

// Real generated JavaScript receiver, synthetic native parent and kernel only.
(static function () use ($check, $decode, $op): void
{
    $root = dirname(__DIR__);
    $peer = proc_open(['node', __DIR__ . '/extension-peer.mjs'], [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $root);
    if (!is_resource($peer)) throw new RuntimeException('Cannot start runtime extension peer.');
    stream_set_timeout($pipes[1], 10);
    $exchange = static function (array $command) use ($pipes): array
    {
        fwrite($pipes[0], json_encode($command, JSON_THROW_ON_ERROR) . "\n");
        fflush($pipes[0]);
        $line = fgets($pipes[1]);
        if ($line === false) throw new RuntimeException('Runtime extension peer did not respond.');
        return json_decode($line, false, 64, JSON_THROW_ON_ERROR);
    };
    $wire = static function (string $kind, array $body = []) use ($exchange, $op): object
    {
        $transaction = $op();
        $messages = $exchange(['topic' => 'synthetic/z2m/symcon/automations/request/' . $kind, 'payload' => ['protocol_version' => 1, 'transaction' => $transaction] + $body]);
        foreach ($messages as $m) if (($m->payload->transaction ?? '') === $transaction) {
            if ($m->payload->status !== 'ok') throw new RuntimeException('Peer preparation failed: ' . json_encode($m->payload));
            return $m->payload->data;
        }
        throw new RuntimeException('Peer preparation response missing.');
    };
    try {
        $exchange(['topic' => 'synthetic/z2m/bridge/request/extension/save', 'payload' => ['name' => 'z2m-automations.mjs', 'code' => file_get_contents($root . '/libs/z2m/z2m-automations.mjs'), 'transaction' => $op()]]);
        $rule = ['id'  => 'manual_wait', 'name' => 'Synthetic delayed automation', 'enabled' => false,
            'triggers' => [['type' => 'time', 'at' => '18:00:00', 'timezone' => 'UTC']], 'conditions' => [],
            'actions'  => [['type' => 'set', 'target' => ['type' => 'device', 'id' => '0x0000000000000002'], 'values' => ['state' => 'ON'], 'delay_seconds' => 600]]];
        $conditional = $rule;
        $conditional['id'] = 'manual_condition';
        $conditional['conditions'] = [['type' => 'device_value', 'device' => '0x0000000000000002', 'property' => 'state', 'operator' => 'eq', 'value' => 'OFF']];
        $yaml = json_encode(['schema_version' => 1, 'revision' => 1, 'automations' => [$rule, $conditional]]) . "\n";
        $m = $wire('status');
        $wire('validate', ['yaml' => $yaml]);
        $wire('store', ['yaml' => $yaml, 'session' => $m->session, 'expected_revision' => 0]);
        $pid = IPS_CreateInstance('{F7A0DD2E-7684-95C0-64C2-D2A9DC47577B}');
        $parent = IPS\InstanceManager::getInstanceInterface($pid);
        $cid = IPS_CreateInstance(Z2MAutomations\SystemContract::CONNECTION_MODULE);
        IPS_ConnectInstance($cid, $pid);
        IPS_SetProperty($cid, 'BaseTopic', 'synthetic/z2m');
        IPS_SetProperty($cid, 'Active', true);
        IPS_ApplyChanges($cid);
        $device = IPS_CreateInstance('{E5BB36C6-A70B-EB23-3716-9151A09AC8A2}');
        IPS_ConnectInstance($device, $pid);
        IPS_SetProperty($device, 'MQTTBaseTopic', 'synthetic/z2m');
        IPS_SetProperty($device, 'IEEE', '0x0000000000000002');
        IPS_ApplyChanges($device);
        $deliver = static function (array $messages) use ($parent, $check): void
        {
            foreach ($messages as $message) {
                $check(!str_ends_with($message->topic, '/set'), 'No delayed device command may execute.');
                $parent->publish($message->topic, $message->payload, $message->retain);
            }
        };
        $parent->onPublish = static fn (array $p) => $deliver($exchange(['topic' => $p['Topic'], 'payload' => $p['Payload']]));
        $parent->publish('synthetic/z2m/bridge/state', ['state' => 'online']);
        $id = IPS_CreateInstance(Z2MAutomations\SystemContract::SOURCE_MODULE);
        $manager = IPS\InstanceManager::getInstanceInterface($id);
        $overview = $decode($manager->GetWorkspaceOverview());
        $check($decode($manager->ConfigureWorkspace(json_encode([$cid]), $overview['fingerprint'], $op()))['ok'], 'Configure real peer system.');
        $s = $decode($manager->GetWorkspaceOverview())['systems'][0];
        $setup = $op();
        $check($decode($manager->StartWorkspaceSetup($s['systemId'], 1, $s['fingerprint'], $setup))['setup']['phase'] === 'preview', 'Read real stored YAML.');
        $check($decode($manager->ConfirmWorkspaceSetup($setup))['setup']['phase'] === 'complete', 'Claim real peer connection.');
        $original = $decode($manager->GetWorkspaceSystem($s['systemId']))['data'];
        $request = static function (string $kind, string $rule = '', string $run = '') use ($manager, $s, $decode, $op): array
        {
            $v = $decode($manager->GetWorkspaceRuntimeStatus($s['systemId']))['runtime'];
            return $decode($manager->RequestWorkspaceRuntime($s['systemId'], 1, $v['fingerprint'], $kind, $rule, $run, true, $op(), $v['context']));
        };
        $result = $request('activate');
        $check($result['runtime']['status'] === 'activated' && $result['runtime']['confirmed'] && $result['runtime']['resumeAfterRestart'], 'Real receiver confirms activation and persisted restart intent: ' . json_encode($result));
        $check($request('deactivate')['runtime']['status'] === 'stopped', 'Real receiver confirms global stop.');
        $run = $request('run_once', 'manual_wait');
        $check($run['runtime']['rules']['manual_wait']['state'] === 'running' && !$run['runtime']['executing'], 'Real disabled rule starts once while automatic mode stays stopped: ' . json_encode($run));
        $stop = $request('stop_rule', 'manual_wait', $run['runtime']['rules']['manual_wait']['run']);
        $check($stop['runtime']['rules']['manual_wait']['state'] === 'stopped', 'Real targeted stop cancels the delayed action.');
        $conditional = $request('run_once', 'manual_condition');
        $check($conditional['runtime']['rules']['manual_condition']['state'] === 'skipped', 'Real condition rejection is visible.');
        $check($decode($manager->GetWorkspaceSystem($s['systemId']))['data'] === $original, 'Real runtime control retains exact documents.');
        $check(array_filter($parent->sent, static fn (array $p): bool => preg_match('~/request/(store|validate|extension/save|restart)$~', $p['Topic']) === 1) === [], 'Central runtime never stores, validates or installs.');
    } finally {
        fclose($pipes[0]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $check(proc_close($peer) === 0, 'Runtime peer must exit cleanly: ' . $stderr);
    }
})();
