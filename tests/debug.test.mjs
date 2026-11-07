import assert from 'node:assert/strict';
import test from 'node:test';
import {RuntimeDiagnostics, DEBUG_EVENTS, debugTrace} from '../libs/z2m/src/debug.mjs';

const client = '11111111-1111-4111-8111-111111111111';
const session = '22222222-2222-4222-8222-222222222222';
const text = Object.values(DEBUG_EVENTS).find(e => e.message === 'Auslöser geprüft.').message;
const flush = async () => { for (let n = 0; n < 1000; n++) await Promise.resolve(); };
function fixture(publish) {
    let now = 0, next = 0;
    const timers = new Map(), records = [];
    const clock = {now: () => now, wallNow: () => 1800000000000 + now,
        setTimeout: (callback, delay) => { timers.set(++next, {callback, at: now + delay}); return next; }, clearTimeout: id => timers.delete(id)};
    const d = new RuntimeDiagnostics({publish: publish ?? (async (_topic, body, options) => records.push({body: JSON.parse(body), options}))}, clock);
    d.session = session;
    return {d, records, advance: async ms => {
        now += ms;
        for (const [id, timer] of timers) if (timer.at <= now) { timers.delete(id); timer.callback(); }
        await flush();
    }};
}

test('debug transport is disabled by default, bounded, sanitised and reports lost records', async () => {
    const h = fixture();
    h.d.emit(text); await flush(); assert.equal(h.records.length, 0);
    assert.equal(h.d.configure(client, 901), null);
    assert.equal(h.d.configure('untrusted', 60), null);
    assert.equal(h.d.configure(client, 30).active, true);
    for (let n = 0; n < 1000; n++) h.d.emit(text, {Erfüllt: true, Grund: 'private'}, {run: client, rule: 1, origin: 'synthetic/a', private: 'never-forward'});
    await flush();
    assert.equal(h.records.length, 80);
    await h.advance(1001); h.d.emit(text); await flush();
    assert.equal(h.records.reduce((sum, r) => sum + r.body.dropped, 0), 920);
    assert.ok(h.records.every(r => !r.options.clientOptions.retain && r.options.skipLog && r.options.skipReceive));
    assert.ok(!JSON.stringify(h.records).includes('never-forward') && !JSON.stringify(h.records).includes('synthetic/a'));
    assert.equal(h.records[0].body.record.origin.length, 64);
    await h.advance(30000); const count = h.records.length; h.d.emit(text); await flush(); assert.equal(h.records.length, count);
    h.d.stop(); assert.equal(h.d.configure(client, 1), null);
});

test('a stalled or rejected publisher does not stall callers, retry old records or create unbounded work', async () => {
    let count = 0;
    const h = fixture(() => { count++; return new Promise(() => {}); });
    h.d.configure(client, 60);
    for (let n = 0; n < 80; n++) h.d.emit(text);
    await flush(); assert.equal(count, 1);
    await h.advance(2001); assert.equal(count, 1, 'Timed-out backlog is discarded without retries');
    const records = [];
    h.d.mqtt.publish = async (_topic, body) => records.push(JSON.parse(body));
    h.d.emit(text); await flush();
    assert.equal(records[0].dropped, 80);
    h.d.mqtt.publish = async () => { throw new Error('private-network-error'); };
    h.d.emit(text); await flush();
    assert.equal(h.d.sending, false);
    h.d.stop();
});

test('client leases are capped and stop invalidates queued output without cancelling other clients', async () => {
    const h = fixture();
    for (let n = 1; n <= 8; n++) assert.ok(h.d.configure(`${n}1111111-1111-4111-8111-111111111111`, 5));
    assert.equal(h.d.configure('91111111-1111-4111-8111-111111111111', 5), null);
    h.d.emit(text); h.d.configure(client, 0); await flush();
    assert.equal(h.records[0].body.clients.length, 7);
    h.d.emit(text); h.d.stop(); await flush(); assert.equal(h.records.length, 1);
    const fresh = fixture(); fresh.d.emit(text); await flush(); assert.equal(fresh.records.length, 0);
    assert.equal(debugTrace({run: client, rule: 1, step: 1, revision: Infinity}), undefined);
    assert.equal(debugTrace({run: 'private', rule: 1, step: 1, revision: 1}), undefined);
});
