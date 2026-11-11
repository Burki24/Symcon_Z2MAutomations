import test from 'node:test';
import assert from 'node:assert/strict';
import {meshStar, rule, sourceID, settle} from './fixtures/mesh-star.mjs';

const conditional = (system, options = {}) => ({...rule('conditional', system),
    conditions: [{type: 'device_value', system, device: sourceID, property: 'state', operator: 'eq', value: 'ON', ...options}],
    actions: [{type: 'set', target: {type: 'group', id: 7}, values: {state: 'ON'}}]});

test('remote conditions use the owning system and freshly recheck before each action', async () => {
    const h = await meshStar();
    try {
        const r = conditional(h.b.system);
        h.a.state.state = 'ON';
        await h.a.load([r], [h.b.system]); await h.b.load([], [h.a.system]);
        await h.a.trigger();
        assert.equal(h.a.commands.length, 0, 'The local device with the same IEEE cannot satisfy a remote condition');
        assert.equal(h.a.runs()[0].state, 'skipped');
        assert.equal(h.a.logs.filter(line => line.includes(' | START | ') && line.includes('"run":')).length,
            h.a.logs.filter(line => line.includes(' | ENDE | ') && line.includes('"run":')).length);
        h.b.state.state = 'ON';
        await h.clock.advance(1100); await h.a.trigger();
        assert.equal(h.a.commands.length, 1);
        assert.equal(h.b.commands.length, 0, 'A condition never dispatches a device command');
        const queries = h.publications.filter(packet => packet.phase === 'condition');
        assert.ok(queries.length >= 3, 'Start and action checks must obtain independently correlated answers');
        assert.equal(new Set(queries.map(packet => packet.data.transaction)).size, queries.length);
        r.actions.push({...r.actions[0], delay_seconds: 2});
        await h.a.load([r], [h.b.system]); await h.a.trigger();
        const before = h.a.commands.length;
        h.b.state.state = 'OFF'; await h.clock.advance(2100);
        assert.equal(h.a.commands.length, before, 'A condition that changes during a delay blocks the next action');
        assert.equal(h.a.runs()[0].state, 'skipped');
    } finally { await h.close(); }
});

test('remote condition replies require fresh correlation, grants and an active receiver', async () => {
    const h = await meshStar();
    try {
        const r = conditional(h.b.system);
        h.b.state.state = 'ON';
        await h.a.load([r], [h.b.system]); await h.b.load([], []);
        await h.a.trigger(); await h.clock.advance(4100);
        assert.equal(h.a.commands.length, 0);
        assert.equal(h.a.runs()[0].state, 'failed');
        await h.b.load([], [h.a.system]);
        h.network.hold = packet => packet.phase === 'condition_result';
        await h.a.trigger();
        const reply = h.publications.findLast(packet => packet.phase === 'condition_result');
        assert.ok(reply);
        for (const data of [{...reply.data, source: h.c.system}, {...reply.data, transaction: '11111111-1111-4111-8111-111111111111'}]) {
            await h.a.extension.receive({...reply.event, message: JSON.stringify(data)}); await settle();
            assert.equal(h.a.commands.length, 0, 'Another system or transaction cannot satisfy the pending condition');
        }
        await h.a.extension.receive({...reply.event, retain: true}); await settle();
        assert.equal(h.a.commands.length, 0);
        await h.a.request('deactivate', {session: h.a.extension.session});
        await h.a.extension.receive(reply.event); await settle();
        assert.equal(h.a.commands.length, 0, 'A late positive reply cannot revive a stopped run');
        assert.equal(h.a.runs()[0].state, 'stopped');
    } finally { await h.close(); }
});

test('unknown remote values fail closed even for not-equal comparisons and stopped receivers', async () => {
    const h = await meshStar();
    try {
        const r = conditional(h.b.system, {operator: 'ne'});
        await h.a.load([r], [h.b.system]); await h.b.load([], [h.a.system]);
        delete h.b.state.state;
        await h.a.trigger();
        assert.equal(h.a.commands.length, 0);
        assert.equal(h.a.runs()[0].state, 'skipped');
        h.b.state.state = 'OFF';
        await h.b.request('deactivate', {session: h.b.extension.session});
        await h.clock.advance(1100); await h.a.trigger(); await h.clock.advance(4100);
        assert.equal(h.a.commands.length, 0);
        assert.equal(h.a.runs()[0].error, 'mesh_timeout');
    } finally { await h.close(); }
});

test('remote conditions require declared outgoing peers and do not extend device triggers', async () => {
    const h = await meshStar();
    try {
        const r = conditional(h.b.system);
        const store = async document => h.a.request('validate', {yaml: JSON.stringify(document)});
        const document = {schema_version: 1, revision: 1, mesh_peers: [], automations: [r]};
        const denied = await store(document);
        assert.equal(denied.status, 'error');
        assert.ok(denied.error.details.issues.some(issue => issue.path === '/automations/0/conditions/0/system' && issue.reason === 'remote_permission'));
        document.mesh_peers = [h.b.system];
        document.automations[0].triggers[0].system = h.b.system;
        assert.equal((await store(document)).error.code, 'invalid_schema');
    } finally { await h.close(); }
});

test('a one-shot run can check remote start-only conditions while its source is stopped', async () => {
    const h = await meshStar();
    try {
        const r = conditional(h.b.system, {start_only: true}); r.enabled = false;
        r.actions[0].delay_seconds = 2;
        await h.a.load([r], [h.b.system]); await h.b.load([], [h.a.system]);
        h.b.state.state = 'ON';
        await h.a.request('deactivate', {session: h.a.extension.session});
        const status = (await h.a.request('status')).data;
        const started = await h.a.request('run_once', {session: status.session, rule_id: r.id,
            expected_revision: status.stored_revision, checksum: status.checksum, activation_token: status.activation_token});
        assert.equal(started.status, 'ok'); await settle();
        h.b.state.state = 'OFF'; await h.clock.advance(2100);
        assert.equal(h.a.commands.length, 1);
        assert.equal(h.a.runs()[0].state, 'completed');
        assert.equal(h.a.extension.mesh.allowed(h.b.system), false, 'An outgoing read grants no incoming access');
        assert.equal(h.publications.filter(packet => packet.phase === 'condition').length, 1);
    } finally { await h.close(); }
});
