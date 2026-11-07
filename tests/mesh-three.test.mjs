import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import test from 'node:test';
import {meshStar, rule, settle, sourceID} from './fixtures/mesh-star.mjs';

const hash = value => createHash('sha256').update(value).digest('hex');
const result = (node, id) => node.runs().find(run => run.rule_id === id);
const phase = (h, name, source) => h.publications.filter(packet => packet.phase === name && (!source || packet.source === source.system));

test('three extensions isolate simultaneous A-to-B/C runs, duplicate commands and correlated German debug', async () => {
    const h = await meshStar();
    try {
        assert.equal(new Set(h.nodes.map(node => node.extension)).size, 3);
        await h.b.load([], [h.a.system]); await h.c.load([], [h.a.system]);
        await h.a.load([rule('to_b', h.b.system), rule('to_c', h.c.system)], [h.b.system, h.c.system]);
        for (const node of h.nodes) await node.debug();
        h.network.duplicateExecute = true;
        await h.a.trigger();
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 1, 1]);
        assert.deepEqual(h.a.runs().map(run => run.state), ['completed', 'completed']);
        assert.equal(new Set(h.a.runs().map(run => run.run)).size, 2);
        const prepares = phase(h, 'prepare', h.a), challenges = phase(h, 'challenge');
        assert.equal(prepares.length, 2);
        assert.equal(new Set(prepares.map(packet => packet.data.transaction)).size, 2);
        assert.equal(new Set(challenges.map(packet => packet.data.token)).size, 2);
        for (const [index, target] of [h.b, h.c].entries()) {
            const run = result(h.a, index === 0 ? 'to_b' : 'to_c');
            const records = target.records().filter(record => record.run === run.run);
            assert.ok(records.length >= 3);
            assert.ok(records.every(record => record.rule === index + 1 && record.step === 1 && record.revision === 1 && record.origin === hash(h.a.system)));
            assert.ok(h.a.records().some(record => record.run === run.run && record.event === 'event_14'));
            assert.ok(records.some(record => record.event === 'event_15'));
            assert.ok(!target.records().some(record => record.run === result(h.a, index === 0 ? 'to_c' : 'to_b').run));
            assert.equal(target.commands[0].topic, target.system + '/synthetic/group/set');
            assert.deepEqual(JSON.parse(target.commands[0].message), {state: 'ON'});
            assert.ok(target.logs.some(line => line.includes('Einmalig freigegebene Aktion')));
        }
        const debug = JSON.stringify(h.nodes.flatMap(node => node.records()));
        for (const privateValue of ['synthetic/', sourceID, 'Synthetic to_', '"state":"ON"']) assert.ok(!debug.includes(privateValue));
        assert.ok(h.a.logs.some(line => line.includes('native Befehlsübergabe bestätigt')));
        assert.ok(h.publications.every(packet => packet.options.clientOptions.retain === false && packet.options.clientOptions.qos === 0));
    } finally { await h.close(); }
});

test('a failed star branch times out only its own run while the other branch remains usable', async () => {
    const h = await meshStar();
    try {
        await h.b.load([], [h.a.system]); await h.c.load([], [h.a.system]);
        await h.a.load([rule('to_b', h.b.system), rule('to_c', h.c.system)], [h.b.system, h.c.system]);
        for (const node of h.nodes) await node.debug();
        h.setBranch(h.b, false);
        await h.a.trigger();
        const failedRun = result(h.a, 'to_b').run;
        assert.equal(result(h.a, 'to_b').state, 'running');
        assert.equal(result(h.a, 'to_c').state, 'completed');
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 0, 1]);
        await h.clock.advance(3999);
        assert.equal(result(h.a, 'to_b').state, 'running');
        await h.clock.advance(1);
        assert.equal(result(h.a, 'to_b').error, 'mesh_timeout');
        assert.equal(result(h.a, 'to_b').state, 'failed');
        assert.ok(h.a.records().some(record => record.run === failedRun && record.event === 'event_07' && record.reason === 'mesh_timeout'));
        assert.ok(h.a.logs.some(line => line.includes('Aktionsfolge abgebrochen; keine automatische Wiederholung.')));
        assert.ok(!h.c.records().some(record => record.run === failedRun));
        await h.a.trigger();
        assert.equal(h.c.commands.length, 2, 'Healthy branch accepts a fresh independent run during the outage');
        assert.ok(h.nodes.every(node => node.extension.status().executing), 'Branch failure must not stop any automation runtime');
        await h.clock.advance(4000);
        h.setBranch(h.b, true); await settle(); await h.clock.advance(1000);
        assert.equal(h.b.commands.length, 0, 'Reconnection must not replay missed commands');
        await h.a.trigger();
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 1, 3]);
        assert.ok(h.a.runs().every(run => run.state === 'completed'));
    } finally { await h.close(); }
});

test('three extensions enforce separate peer grants without deriving reciprocal or transitive permissions', async () => {
    const h = await meshStar();
    try {
        await h.a.load([rule('a_to_b', h.b.system), rule('a_to_c', h.c.system)], [h.b.system, h.c.system]);
        await h.b.load([rule('b_to_c', h.c.system), rule('b_to_a', h.a.system, 'reverse')], [h.a.system, h.c.system]);
        await h.c.load([rule('c_to_b', h.b.system)], [h.b.system]);
        for (const node of h.nodes) await node.debug();
        await h.a.trigger(); await h.b.trigger();
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 1, 1]);
        assert.equal(result(h.a, 'a_to_b').state, 'completed');
        assert.equal(result(h.b, 'b_to_c').state, 'completed');
        await h.clock.advance(4000);
        assert.equal(result(h.a, 'a_to_c').error, 'mesh_timeout', 'A/B and B/C permission must not authorize A/C');
        assert.ok(h.c.logs.some(line => line.includes('mesh_not_allowed')));
        assert.ok(!h.c.records().some(record => record.run === result(h.a, 'a_to_c').run && record.event === 'event_15'));
        await h.c.trigger();
        assert.equal(h.b.commands.length, 2, 'Explicitly permitted reverse direction can execute');
        // Remove B/C and its outgoing rule together; C's B grant and A/B stay unchanged.
        await h.b.load([rule('b_to_a', h.a.system, 'reverse')], [h.a.system]);
        await h.clock.advance(1000); await h.c.trigger(); await h.b.trigger('reverse');
        await h.clock.advance(4000);
        assert.equal(h.b.commands.length, 2, 'C-to-B is blocked after its receiver grant is removed');
        assert.equal(result(h.c, 'c_to_b').error, 'mesh_timeout');
        assert.equal(h.a.commands.length, 1, 'Unrelated B-to-A permission remains usable');
        await h.c.load([], [h.a.system, h.b.system]);
        assert.equal(h.c.commands.length, 1, 'Granting A later must not replay its rejected run');
        await h.a.trigger();
        assert.equal(h.c.commands.length, 2, 'Fresh A-to-C run succeeds only after explicit receiver permission');
    } finally { await h.close(); }
});

test('a failed step still aborts its own remaining actions without aborting an independent rule', async () => {
    const h = await meshStar();
    try {
        await h.b.load([], [h.a.system]); await h.c.load([], [h.a.system]);
        const sequence = rule('failed_sequence', h.b.system);
        sequence.actions.push(rule('unused', h.c.system).actions[0]);
        await h.a.load([sequence, rule('independent_c', h.c.system)], [h.b.system, h.c.system]);
        h.setBranch(h.b, false);
        await h.a.trigger(); await h.clock.advance(4000);
        assert.equal(result(h.a, 'failed_sequence').state, 'failed');
        assert.equal(result(h.a, 'failed_sequence').step, 1);
        assert.equal(result(h.a, 'independent_c').state, 'completed');
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 0, 1]);
        assert.equal(phase(h, 'prepare', h.a).length, 2, 'Failed sequence must never request its second action');
    } finally { await h.close(); }
});

test('a reply from another branch cannot complete a held transaction or cause a replay', async () => {
    const h = await meshStar();
    try {
        await h.b.load([], [h.a.system]); await h.c.load([], [h.a.system]);
        await h.a.load([rule('to_b', h.b.system), rule('to_c', h.c.system)], [h.b.system, h.c.system]);
        h.network.hold = packet => packet.source === h.b.system && packet.phase === 'result';
        await h.a.trigger();
        const bReply = phase(h, 'result', h.b)[0], cReply = phase(h, 'result', h.c)[0];
        assert.equal(result(h.a, 'to_b').state, 'running');
        assert.equal(result(h.a, 'to_c').state, 'completed');
        // Use the real C reply, but substitute the pending B transaction. Source must still match.
        await h.a.extension.receive({...cReply.event, message: JSON.stringify({...cReply.data, transaction: bReply.data.transaction})});
        await settle();
        assert.equal(result(h.a, 'to_b').state, 'running');
        await h.clock.advance(4000);
        assert.equal(result(h.a, 'to_b').error, 'mesh_timeout');
        await h.a.extension.receive(bReply.event); await settle();
        assert.equal(result(h.a, 'to_b').state, 'failed', 'A late valid result cannot rewrite the failed run');
        const execute = phase(h, 'execute', h.a).find(packet => packet.data.transaction === bReply.data.transaction);
        await h.c.extension.receive({...execute.event, topic: 'z2ma/mesh/' + hash(h.c.system) + '/execute'});
        await h.b.extension.receive(execute.event); await settle();
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 1, 1], 'Tokens are destination-bound and single-use');
    } finally { await h.close(); }
});

test('outer S3 and S4 exchange actions through broker S1 while its extension is stopped', async () => {
    const h = await meshStar();
    try {
        await h.b.load([rule('s3_to_s4', h.c.system)], [h.c.system]);
        await h.c.load([], [h.b.system]);
        await h.a.extension.stop();
        await h.b.debug(); await h.c.debug();
        await h.b.trigger();
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 0, 1]);
        assert.equal(result(h.b, 's3_to_s4').state, 'completed');
        assert.equal(h.a.extension.status().executing, false);
        for (const packet of h.publications) {
            const senderIsB = packet.source === h.b.system;
            const first = senderIsB ? h.b : h.c, last = senderIsB ? h.c : h.b;
            assert.deepEqual(h.hops.filter(hop => hop.id === packet.id).map(hop => [hop.from, hop.to]),
                [[first.system, h.a.system], [h.a.system, last.system]]);
        }
        assert.deepEqual(h.publications.map(packet => packet.phase), ['prepare', 'challenge', 'execute', 'result']);
        const run = result(h.b, 's3_to_s4').run;
        assert.ok(h.c.records().some(record => record.run === run && record.origin === hash(h.b.system) && record.step === 1));
        assert.equal(h.a.records().length, 0, 'Relay broker needs no participating extension or peer grant');
    } finally { await h.close(); }
});
