import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import test from 'node:test';
import {meshStar, rule, settle, sourceID} from './fixtures/mesh-star.mjs';

const systems = Array.from({length: 8}, (_, index) => 'synthetic/s' + (index + 1));
const hash = value => createHash('sha256').update(value).digest('hex');
const result = (node, id) => node.runs().find(run => run.rule_id === id);
const packets = (h, phase) => h.publications.filter(packet => packet.phase === phase);
const to = (target, index) => {
    const item = rule('target_' + index, target.system);
    if (index % 2 === 0) item.actions[0].target = {type: 'device', id: sourceID, system: target.system};
    item.actions[0].values.state = index % 2 === 0 ? 'OFF' : 'ON';
    return item;
};
const rulesFor = (h, source) => h.nodes.flatMap((target, index) => target === source ? [] : [to(target, index)]);
const enableAll = async h => {
    for (const node of h.nodes) await node.load(rulesFor(h, node), h.nodes.filter(peer => peer !== node).map(peer => peer.system));
    for (const node of h.nodes) await node.debug();
};
const trace = (h, source, target, run, ruleNumber) => {
    const received = target.records().filter(record => record.run === run.run);
    assert.ok(received.length >= 3, 'Target must record this run, not merely accept a command');
    assert.ok(received.every(record => record.rule === ruleNumber && record.revision === 1 && record.step === 1 && record.origin === hash(source.system)));
    assert.ok(received.some(record => record.event === 'event_15'));
    assert.ok(source.records().some(record => record.run === run.run && record.event === 'event_14'));
    for (const other of h.nodes.filter(node => node !== source && node !== target)) {
        assert.ok(!other.records().some(record => record.run === run.run), 'Unrelated extension must not report a foreign run');
    }
};

test('eight extensions route all 56 directed pairs to exact device/group targets with isolated debug', async () => {
    const h = await meshStar(systems);
    try {
        assert.equal(h.nodes.length, 8);
        assert.equal(new Set(h.nodes.map(node => node.extension)).size, 8);
        assert.equal(new Set(h.nodes.map(node => node.directory)).size, 8);
        await enableAll(h);
        h.network.duplicateExecute = true;
        const runs = new Set();
        for (const [sourceIndex, source] of h.nodes.entries()) {
            const before = h.nodes.map(node => node.commands.length);
            await source.trigger();
            for (const [targetIndex, target] of h.nodes.entries()) {
                assert.equal(target.commands.length, before[targetIndex] + (target === source ? 0 : 1));
                if (target === source) continue;
                const run = result(source, 'target_' + targetIndex);
                assert.equal(run.state, 'completed');
                assert.equal(run.step, 1);
                runs.add(run.run);
                trace(h, source, target, run, targetIndex < sourceIndex ? targetIndex + 1 : targetIndex);
                const entity = targetIndex % 2 === 0 ? 'synthetic/button' : 'synthetic/group';
                assert.equal(target.commands.at(-1).topic, target.system + '/' + entity + '/set');
                assert.deepEqual(JSON.parse(target.commands.at(-1).message), {state: targetIndex % 2 === 0 ? 'OFF' : 'ON'});
            }
            await h.clock.advance(1000);
        }
        assert.equal(runs.size, 56, 'Equal rule IDs on different sources must not merge runs');
        assert.deepEqual(h.nodes.map(node => node.commands.length), Array(8).fill(7));
        assert.equal(new Set(packets(h, 'prepare').map(packet => packet.data.transaction)).size, 56);
        assert.equal(new Set(packets(h, 'challenge').map(packet => packet.data.token)).size, 56);
        for (const phase of ['prepare', 'challenge', 'execute', 'result']) assert.equal(packets(h, phase).length, 56);
        assert.ok(h.publications.every(packet => packet.options.clientOptions.qos === 0 && packet.options.clientOptions.retain === false));
        assert.ok(h.publications.every(packet => h.hops.filter(hop => hop.id === packet.id).length === 7), 'Each publication traverses the seven edges once');
        assert.ok(h.hops.every(hop => hop.from === systems[0] || hop.to === systems[0]), 'There are no direct leaf-to-leaf edges');
        const debug = JSON.stringify(h.nodes.flatMap(node => node.records()));
        for (const privateValue of ['synthetic/', sourceID, 'Synthetic target_', '"state":"ON"', '"state":"OFF"']) assert.ok(!debug.includes(privateValue));
        assert.ok(h.nodes.every(node => node.logs.some(line => line.includes('Einmalig freigegebene Aktion'))
            && node.logs.some(line => line.includes('native Befehlsübergabe bestätigt'))));
    } finally { await h.close(); }
});

test('eight extensions keep receiver grants independent through selective approval and revocation', async () => {
    const h = await meshStar(systems);
    try {
        const source = h.nodes[0], allowed = new Set([1, 3, 5, 7]);
        for (let index = 1; index < 8; index++) await h.nodes[index].load([], [h.nodes[index % 2 === 1 ? 0 : 1].system]);
        await source.load(rulesFor(h, source), systems.slice(1));
        for (const node of h.nodes) await node.debug();
        const counts = Array(8).fill(0);
        const wave = async () => {
            const recordCounts = h.nodes.map(node => node.records().length);
            await source.trigger(); await h.clock.advance(4000);
            for (let index = 1; index < 8; index++) {
                const target = h.nodes[index], run = result(source, 'target_' + index);
                if (allowed.has(index)) {
                    counts[index]++;
                    assert.equal(run.state, 'completed');
                    assert.ok(target.records().some(record => record.run === run.run && record.event === 'event_15'));
                } else {
                    assert.equal(run.state, 'failed');
                    assert.equal(run.error, 'mesh_timeout');
                    assert.ok(source.records().some(record => record.run === run.run && record.event === 'event_07' && record.reason === 'mesh_timeout'));
                    assert.ok(!target.records().some(record => record.run === run.run));
                    assert.ok(target.records().slice(recordCounts[index]).some(record => record.event === 'event_17' && record.reason === 'mesh_not_allowed'));
                }
            }
            assert.deepEqual(h.nodes.map(node => node.commands.length), counts);
        };
        await wave();
        let checksums = h.nodes.map(node => node.extension.status().checksum);
        await h.nodes[2].load([], [source.system, h.nodes[1].system]); allowed.add(2);
        assert.deepEqual(h.nodes.map(node => node.commands.length), counts, 'A new grant must not replay a rejected command');
        assert.ok(h.nodes.every((node, index) => index === 2 || node.extension.status().checksum === checksums[index]));
        await wave();
        checksums = h.nodes.map(node => node.extension.status().checksum);
        await h.nodes[5].load([], []); allowed.delete(5);
        assert.ok(h.nodes.every((node, index) => index === 5 || node.extension.status().checksum === checksums[index]));
        await wave();
        assert.deepEqual(counts, [0, 3, 2, 3, 0, 2, 0, 3]);
        assert.ok(h.nodes.every(node => node.extension.status().executing));
    } finally { await h.close(); }
});

test('loss and recovery of the eighth branch isolates its timeout while six other targets remain usable', async () => {
    const h = await meshStar(systems);
    try {
        await enableAll(h);
        const source = h.nodes[2], failed = h.nodes[7];
        h.setBranch(failed, false);
        await source.trigger();
        const failedRun = result(source, 'target_7').run;
        assert.deepEqual(h.nodes.map(node => node.commands.length), [1, 1, 0, 1, 1, 1, 1, 0]);
        assert.equal(source.runs().filter(run => run.state === 'completed').length, 6);
        await h.clock.advance(3999);
        assert.equal(result(source, 'target_7').state, 'running');
        await h.clock.advance(1);
        assert.equal(result(source, 'target_7').state, 'failed');
        assert.equal(result(source, 'target_7').error, 'mesh_timeout');
        assert.ok(source.records().some(record => record.run === failedRun && record.event === 'event_07' && record.reason === 'mesh_timeout'));
        for (const other of h.nodes.filter(node => node !== source)) assert.ok(!other.records().some(record => record.run === failedRun));
        await source.trigger(); await h.clock.advance(4000);
        assert.deepEqual(h.nodes.map(node => node.commands.length), [2, 2, 0, 2, 2, 2, 2, 0]);
        assert.ok(h.nodes.every(node => node.extension.status().executing), 'A failed bridge must not stop another runtime');
        h.setBranch(failed, true); await settle(); await h.clock.advance(1000);
        assert.equal(failed.commands.length, 0, 'Reconnection must not replay either missed command');
        await source.trigger();
        assert.deepEqual(h.nodes.map(node => node.commands.length), [3, 3, 0, 3, 3, 3, 3, 1]);
        assert.ok(source.runs().every(run => run.state === 'completed'));
        trace(h, source, failed, result(source, 'target_7'), 7);
        assert.ok(source.logs.some(line => line.includes('Aktionsfolge abgebrochen; keine automatische Wiederholung.')));
    } finally { await h.close(); }
});

test('seven pending mesh results reject cross-branch replies and preserve a single late timeout', async () => {
    const h = await meshStar(systems);
    try {
        await enableAll(h);
        const source = h.nodes[0];
        h.network.hold = packet => packet.phase === 'result';
        await source.trigger();
        const replies = packets(h, 'result');
        assert.equal(replies.length, 7);
        assert.ok(source.runs().every(run => run.state === 'running'));
        for (const [index, reply] of replies.entries()) {
            await source.extension.receive({...reply.event,
                message: JSON.stringify({...reply.data, transaction: replies[(index + 1) % 7].data.transaction})});
        }
        await settle();
        assert.ok(source.runs().every(run => run.state === 'running'), 'Another receiver cannot settle any of the seven waiters');
        for (const reply of replies.slice(0, 6)) await source.extension.receive(reply.event);
        await settle();
        assert.equal(source.runs().filter(run => run.state === 'completed').length, 6);
        const late = replies[6];
        const prepare = packets(h, 'prepare').find(packet => packet.data.transaction === late.data.transaction);
        const run = source.runs().find(item => item.run === prepare.data.trace.run);
        await h.clock.advance(3999);
        assert.equal(source.runs().find(item => item.run === run.run).state, 'running');
        await h.clock.advance(1);
        assert.equal(source.runs().find(item => item.run === run.run).error, 'mesh_timeout');
        await source.extension.receive(late.event); await settle();
        assert.equal(source.runs().find(item => item.run === run.run).state, 'failed');
        assert.equal(source.runs().filter(item => item.state === 'completed').length, 6);
        for (const execute of packets(h, 'execute')) {
            for (const target of h.nodes.slice(1)) {
                await target.extension.receive({...execute.event, topic: 'z2ma/mesh/' + hash(target.system) + '/execute'});
            }
        }
        await settle();
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 1, 1, 1, 1, 1, 1, 1], 'Replayed or retargeted execute cannot add a command');
        assert.ok(source.records().some(record => record.run === run.run && record.event === 'event_07' && record.reason === 'mesh_timeout'));
    } finally { await h.close(); }
});

test('all seven outer extensions exchange a ring of actions through a broker with its extension stopped', async () => {
    const h = await meshStar(systems);
    try {
        const root = h.nodes[0], leaves = h.nodes.slice(1);
        await root.load([], []);
        for (const [index, node] of leaves.entries()) {
            const next = leaves[(index + 1) % 7], previous = leaves[(index + 6) % 7];
            await node.load([rule('shared_ring_rule', next.system)], [previous.system, next.system]);
            await node.debug();
        }
        await root.extension.stop();
        await Promise.all(leaves.map(node => node.trigger()));
        assert.deepEqual(h.nodes.map(node => node.commands.length), [0, 1, 1, 1, 1, 1, 1, 1]);
        assert.equal(root.extension.status().executing, false);
        assert.equal(root.records().length, 0);
        assert.equal(new Set(leaves.map(node => result(node, 'shared_ring_rule').run)).size, 7);
        for (const [index, node] of leaves.entries()) {
            const target = leaves[(index + 1) % 7], run = result(node, 'shared_ring_rule');
            assert.equal(run.state, 'completed');
            trace(h, node, target, run, 1);
        }
        for (const phase of ['prepare', 'challenge', 'execute', 'result']) assert.equal(packets(h, phase).length, 7);
        for (const packet of h.publications) {
            const hops = h.hops.filter(hop => hop.id === packet.id);
            assert.equal(hops.length, 7);
            assert.ok(hops.every(hop => hop.from === root.system || hop.to === root.system));
            assert.ok(hops.some(hop => hop.from === packet.source && hop.to === root.system));
        }
    } finally { await h.close(); }
});
