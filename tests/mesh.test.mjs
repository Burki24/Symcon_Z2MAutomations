import assert from 'node:assert/strict';
import test from 'node:test';
import {mkdtemp, rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import Extension, {validateYaml} from '../libs/z2m/z2m-automations.mjs';

const source = '0x0000000000000001';
const flush = async () => { for (let n = 0; n < 100; n++) await Promise.resolve(); };
const remoteRule = (type = 'toggle') => ({id: 'synthetic_cross_system', name: 'Synthetic cross-system rule', enabled: true,
    triggers: [{type: 'device', device: source, property: 'action', operator: 'eq', value: 'single'}], conditions: [],
    actions: [{type, target: {type: 'group', id: 7, system: 'synthetic/b'}, ...(type === 'toggle' ? {property: 'state'} : {values: {state: 'ON'}})}]});

async function pair() {
    const nodes = [], wire = [];
    let now = 0;
    const network = {holdExecute: false, holdResult: false, retained: false, duplicateExecute: false};
    for (const system of ['synthetic/a', 'synthetic/b']) {
        const directory = await mkdtemp(join(tmpdir(), 'z2ma-mesh-'));
        const node = {system, directory, connected: true, subscriptions: new Set(), messages: [], commands: [], logs: []};
        const device = {ieeeAddr: source, ID: source, name: 'synthetic/button', isDevice: () => true};
        node.device = device;
        const group = {ID: 7, name: system + '/group', zh: {members: [{ID: 1, meta: {scenes: {'5_7': {name: 'Synthetic mesh scene'}}}}]}};
        node.group = group;
        const bus = {onMQTTMessage: () => {}, onPublishEntityState: (_owner, callback) => { node.event = callback; }, removeListeners: () => {},
            emitMQTTMessage: event => { node.commands.push(event); }};
        const mqtt = {isConnected: () => node.connected,
            subscribe: async topic => { node.subscriptions.add(topic); },
            unsubscribe: async topic => { node.subscriptions.delete(topic); },
            publish: async (topic, message, options) => {
                // Z2M always joins baseTopic + '/' + topic, even for an empty baseTopic.
                topic = (options.baseTopic ?? system) + '/' + topic;
                node.messages.push({topic, data: JSON.parse(message), options});
                if (!topic.startsWith('z2ma/mesh/')) return;
                const event = {topic, message, retain: network.retained};
                wire.push({system, event, options});
                if (network.holdExecute && topic.endsWith('/execute')) return;
                if (network.holdResult && topic.endsWith('/result')) return;
                for (const peer of nodes) {
                    if (!peer.connected || ![...peer.subscriptions].some(filter => topic.startsWith(filter.slice(0, -1)))) continue;
                    queueMicrotask(() => {
                        void peer.extension.receive(event);
                        if (network.duplicateExecute && topic.endsWith('/execute')) void peer.extension.receive(event);
                    });
                }
            }};
        const clock = {now: () => now, wallNow: () => network.wall ?? Date.now(), setTimeout, clearTimeout, setInterval, clearInterval};
        node.extension = new Extension({resolveEntity: id => id === source ? device : null, groupsIterator: () => [group]}, mqtt,
            {get: () => ({state: 'OFF'})}, null, bus, null, null, null, {get: () => ({mqtt: {base_topic: system}})},
            {debug: message => node.logs.push(message)}, {directory, clock});
        nodes.push(node);
        await node.extension.start();
        node.load = async (rules, peers = [system === 'synthetic/a' ? 'synthetic/b' : 'synthetic/a']) => {
            const request = async (operation, fields = {}) => {
                const transaction = 'synthetic-control-' + node.messages.length;
                await node.extension.receive({topic: system + '/symcon/automations/request/' + operation,
                    message: JSON.stringify({protocol_version: 1, transaction, ...fields})});
                return node.messages.findLast(message => message.data.transaction === transaction).data;
            };
            const current = node.extension.status();
            const saved = await request('store', {session: current.session, expected_revision: current.stored_revision,
                yaml: JSON.stringify({schema_version: 1, revision: current.stored_revision + 1, automations: rules, mesh_peers: peers})});
            assert.equal(saved.status, 'ok');
            const status = (await request('status')).data;
            const activated = await request('activate', {session: status.session, expected_revision: status.stored_revision,
                checksum: status.checksum, activation_token: status.activation_token});
            assert.equal(activated.status, 'ok');
        };
        node.trigger = async () => { node.event({entity: device, payload: {action: 'single'}}); await flush(); };
    }
    return {a: nodes[0], b: nodes[1], wire, network, advance: ms => { now += ms; },
        close: async () => { for (const node of nodes) { await node.extension.stop(); await rm(node.directory, {recursive: true, force: true}); } }};
}

test('two independent extensions execute a remote group toggle once without Symcon and ignore duplicate commands', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule()]); await h.b.load([]);
        h.network.duplicateExecute = true;
        await h.a.trigger();
        assert.equal(h.a.commands.length, 0, 'Identical local group ID must not receive the remote action');
        assert.equal(h.b.commands.length, 1);
        assert.equal(h.b.commands[0].topic, 'synthetic/b/synthetic/b/group/set');
        assert.deepEqual(JSON.parse(h.b.commands[0].message), {state: 'ON'});
        assert.equal(h.a.extension.status().runtime_error, '');
        assert.ok(h.wire.every(message => message.options.baseTopic === 'z2ma' && message.options.clientOptions.retain === false && message.options.clientOptions.qos === 0));
        assert.ok(h.a.logs.some(message => message.includes('native Befehlsübergabe bestätigt')));
        assert.ok(h.b.logs.some(message => message.includes('Einmalig freigegebene Aktion')));
    } finally { await h.close(); }
});

test('recordings on both systems retain one run and step across the mesh without disclosing action bodies', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule()]); await h.b.load([]);
        for (const node of [h.a, h.b]) {
            await node.extension.receive({topic: node.system + '/symcon/automations/request/debug', message: JSON.stringify({protocol_version: 1,
                transaction: 'synthetic-debug', session: node.extension.status().session, client: '11111111-1111-4111-8111-111111111111', seconds: 60})});
        }
        h.network.duplicateExecute = true;
        await h.a.trigger(); await flush();
        const source = h.a.messages.filter(m => m.topic.endsWith('/automations/debug')).map(m => m.data.record);
        const destination = h.b.messages.filter(m => m.topic.endsWith('/automations/debug')).map(m => m.data.record);
        const run = source.find(r => r.step === 1)?.run;
        assert.ok(run && destination.length >= 3);
        const traced = destination.filter(r => r.run === run);
        assert.ok(traced.length >= 3 && traced.every(r => r.rule === 1 && r.step === 1 && r.revision === 1));
        assert.ok(traced.every(r => r.origin === source.find(r => r.step === 1).origin));
        assert.equal(h.b.commands.length, 1);
        assert.ok(!JSON.stringify([...source, ...destination]).includes('synthetic/') && !JSON.stringify(destination).includes('0x0000000000000001'));
        const prepare = h.wire.find(m => m.event.topic.endsWith('/prepare'));
        assert.equal(JSON.parse(prepare.event.message).trace.run, run);
    } finally { await h.close(); }
});

test('a solar event invokes the permitted remote action through the existing mesh path', async () => {
    const h = await pair();
    try {
        const {solarForLocalDay} = await import('../libs/z2m/src/solar.mjs');
        const trigger = {type: 'sun', event: 'sunset', latitude: 0, longitude: 0, offset_minutes: 15, timezone: 'UTC'};
        const r = remoteRule(); r.triggers = [trigger];
        h.network.wall = solarForLocalDay('2026-03-20', trigger);
        await h.a.load([r]); await h.b.load([]);
        h.a.extension.runtime.tick(); await flush();
        assert.equal(h.b.commands.length, 1);
        assert.equal(h.a.commands.length, 0);
        h.a.extension.runtime.tick(); await flush(); assert.equal(h.b.commands.length, 1);
        assert.equal(h.a.extension.status().runtime_error, '');
    } finally { await h.close(); }
});

test('remote scenes are recalled only on the selected system and rechecked by the receiver', async () => {
    const h = await pair();
    try {
        const r = remoteRule();
        r.actions = [{type: 'scene', target: r.actions[0].target, scene_id: 5}];
        await h.a.load([r]); await h.b.load([]);
        h.network.duplicateExecute = true;
        await h.a.trigger();
        assert.equal(h.a.commands.length, 0);
        assert.equal(h.b.commands.length, 1);
        assert.deepEqual(JSON.parse(h.b.commands[0].message), {scene_recall: 5});
        assert.equal(h.a.extension.status().runtime_error, '');
        assert.ok(h.b.logs.some(line => line.includes('Szenenaufruf')));
        delete h.b.group.zh;
        h.advance(1000);
        await h.a.trigger();
        assert.equal(h.b.commands.length, 1);
        assert.equal(h.b.extension.status().runtime_error, 'scene_unavailable');
        assert.equal(h.a.extension.status().runtime_error, 'scene_unavailable');
    } finally { await h.close(); }
});

test('remote device scenes keep endpoint identity even when both systems know the same device', async () => {
    const h = await pair();
    try {
        h.b.device.zh = {endpoints: [{ID: 2, meta: {scenes: {'0_0': {name: 'Synthetic endpoint scene'}}}}]};
        const r = remoteRule();
        r.actions = [{type: 'scene', target: {type: 'device', id: source, system: 'synthetic/b'}, scene_id: 0, endpoint: 2}];
        await h.a.load([r]); await h.b.load([]);
        await h.a.trigger();
        assert.equal(h.a.commands.length, 0);
        assert.equal(h.b.commands.length, 1);
        assert.equal(h.b.commands[0].topic, 'synthetic/b/synthetic/button/2/set');
        assert.deepEqual(JSON.parse(h.b.commands[0].message), {scene_recall: 0});
    } finally { await h.close(); }
});

test('incoming remote actions require explicit activation and an allowed source; disconnects never queue commands', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule()]); await h.b.load([], []);
        assert.equal(h.b.extension.mesh.subscribed, false);
        h.a.connected = false;
        await h.a.trigger();
        assert.equal(h.a.extension.status().runtime_error, 'mesh_unavailable');
        assert.equal(h.b.commands.length, 0);
        assert.equal(h.wire.length, 0);
        assert.ok(h.a.logs.some(message => message.includes('keine automatische Wiederholung')));
    } finally { await h.close(); }
});

test('expired or modified commands cannot switch; stopping the source cancels pending exchanges', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule('set')]); await h.b.load([]);
        h.network.holdExecute = true;
        const pending = h.a.extension.mesh.dispatch(remoteRule('set').actions[0]);
        const rejected = assert.rejects(pending, {code: 'mesh_stopped'});
        await flush();
        const command = h.wire.findLast(message => message.event.topic.endsWith('/execute')).event;
        h.advance(4001);
        await h.b.extension.receive(command);
        assert.equal(h.b.commands.length, 0);
        h.a.extension.runtime.deactivate();
        await rejected;
        await h.b.extension.receive(command);
        assert.equal(h.b.commands.length, 0);
    } finally { await h.close(); }
});

test('stopping or restarting the destination invalidates grants and replayed execute messages', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule()]); await h.b.load([]);
        await h.a.trigger();
        const command = h.wire.findLast(message => message.event.topic.endsWith('/execute')).event;
        h.b.extension.runtime.deactivate();
        await h.b.extension.receive(command);
        await h.b.load([]);
        await h.b.extension.receive(command);
        await h.b.extension.receive({...command, retain: true});
        assert.equal(h.b.commands.length, 1);
    } finally { await h.close(); }
});

test('remote schema rejects arbitrary systems and MQTT topics without changing the local schema contract', () => {
    const document = {schema_version: 1, revision: 1, automations: [remoteRule()], mesh_peers: ['synthetic/b']};
    assert.ok(validateYaml(JSON.stringify(document)));
    document.automations[0].actions[0].target.system = 'synthetic/+/set';
    assert.throws(() => validateYaml(JSON.stringify(document)), {code: 'invalid_schema'});
});

test('modified grants, unapproved sources and malformed packets cannot execute actions', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule('set')]); await h.b.load([]);
        h.network.holdExecute = true;
        const pending = h.a.extension.mesh.dispatch(remoteRule('set').actions[0]);
        const rejected = assert.rejects(pending, {code: 'mesh_stopped'});
        await flush();
        const original = h.wire.findLast(message => message.event.topic.endsWith('/execute')).event;
        const changed = JSON.parse(original.message);
        changed.action.values.state = 'OFF';
        await h.b.extension.receive({...original, message: JSON.stringify(changed)});
        await h.b.extension.receive(original);
        await h.b.extension.receive({...original, message: '{invalid'});
        await h.b.extension.receive({...original, message: JSON.stringify({...changed, source: 'synthetic/unapproved'})});
        assert.equal(h.b.commands.length, 0);
        h.a.extension.runtime.deactivate();
        await rejected;
    } finally { await h.close(); }
});

test('missing remote targets return rejection; physical device success is never claimed', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule()]); await h.b.load([]);
        const action = remoteRule().actions[0];
        action.target.id = 8;
        await assert.rejects(h.a.extension.mesh.dispatch(action), {code: 'mesh_rejected'});
        assert.equal(h.b.extension.status().runtime_error, 'unknown_entity');
        assert.equal(h.b.commands.length, 0);
        assert.ok(!h.a.logs.some(message => message.includes('native Befehlsübergabe bestätigt')));
    } finally { await h.close(); }
});

test('the same implementation supports the reverse direction without mixing equal group IDs', async () => {
    const h = await pair();
    try {
        const rule = remoteRule();
        rule.actions[0].target.system = 'synthetic/a';
        await h.a.load([]); await h.b.load([rule]);
        await h.b.trigger();
        assert.equal(h.a.commands.length, 1);
        assert.equal(h.b.commands.length, 0);
        assert.equal(h.a.commands[0].topic, 'synthetic/a/synthetic/a/group/set');
    } finally { await h.close(); }
});

test('a lost acknowledgement reports uncertainty without automatically executing the action again', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule()]); await h.b.load([]);
        h.network.holdResult = true;
        await h.a.trigger();
        assert.equal(h.b.commands.length, 1, 'The target can already have dispatched before the acknowledgement is lost');
        await new Promise(resolve => setTimeout(resolve, 4100));
        assert.equal(h.a.extension.status().runtime_error, 'mesh_timeout');
        assert.equal(h.wire.filter(message => message.event.topic.endsWith('/execute')).length, 1);
        assert.equal(h.b.commands.length, 1, 'No automatic retry after uncertain outcome');
        assert.ok(!h.a.logs.some(message => message.includes('native Befehlsübergabe bestätigt')));
    } finally { await h.close(); }
});


test('both systems resume their stored permission after restart and exchange fresh commands', async () => {
    const h = await pair();
    try {
        await h.a.load([remoteRule('set')]); await h.b.load([]);
        await h.a.extension.stop(); await h.b.extension.stop();
        await h.a.extension.start(); await h.b.extension.start();
        for (const node of [h.a, h.b]) {
            assert.equal(node.extension.status().resume_status, 'resumed');
            assert.equal(node.extension.status().executing, true);
            assert.equal(node.extension.status().mesh_ready, true);
            assert.equal(node.commands.length, 0);
        }
        await h.a.trigger();
        assert.equal(h.b.commands.length, 1);
        assert.equal(h.a.extension.status().runtime_error, '');
    } finally { await h.close(); }
});
