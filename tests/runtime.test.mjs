import assert from 'node:assert/strict';
import test from 'node:test';
import {mkdtemp, rm, writeFile, readFile} from 'node:fs/promises';
import {createHash} from 'node:crypto';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import Extension, {validateYaml, entityScenes, buildEditorScenes, DEBUG_EVENTS, RunJournal} from '../libs/z2m/z2m-automations.mjs';

const sourceID = '0x0000000000000001';
const targetID = '0x0000000000000002';
const rule = () => ({id: 'synthetic_rule', name: 'Synthetic rule', enabled: true,
    triggers: [{type: 'device', device: sourceID, property: 'occupancy', operator: 'eq', value: true}],
    conditions: [], actions: [{type: 'set', target: {type: 'device', id: targetID}, values: {state: 'ON'}}]});
const yaml = (rules = [rule()], revision = 1) => JSON.stringify({schema_version: 1, revision, automations: rules});
const flush = async () => { for (let i = 0; i < 20; i++) await Promise.resolve(); };
const settleJournal = async h => {
    for (let i = 0; i < 5; i++) {
        await new Promise(resolve => setImmediate(resolve));
        await h.extension.runJournal.flush();
        await flush();
    }
};

test('restart continues only the waiting tail, counts downtime and retains the run identity', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        const r = {...rule(), restart_policy: 'resume'};
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true}); await settleJournal(h);
        const run = h.extension.status().execution.runs[0].run;
        await h.clock.advance(2000); const wall = h.clock.wallNow(); await h.close(false);
        h = await harness(directory, true, h => { h.clock.wall = wall + 1000; }); await settleJournal(h);
        assert.equal(h.commands.length, 0, 'The completed ON action is never replayed');
        assert.equal(h.extension.status().execution.runs[0].run, run);
        await h.clock.advance(1999); await settleJournal(h); assert.equal(h.commands.length, 0);
        await h.clock.advance(1); await settleJournal(h);
        assert.deepEqual(h.commands.map(command => command.data), [{state: 'OFF'}]);
        assert.ok(h.logs.some(line => line.includes('verbleibender Wartezeit fortsetzen')));
        await h.close(false); h = await harness(directory); await settleJournal(h);
        await h.clock.advance(10000); assert.equal(h.commands.length, 0, 'A completed continuation cannot run again');
    } finally { await h.close(); }
});

test('expired waits either end the run or catch up exactly the pending action as configured', async () => {
    for (const policy of ['abort', 'resume', 'resume_expired']) {
        let h = await harness(); const directory = h.directory;
        try {
            const r = {...rule(), restart_policy: policy};
            r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
            await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true}); await settleJournal(h);
            const wall = h.clock.wallNow(); await h.close(false);
            h = await harness(directory, true, h => { h.clock.wall = wall + 6000; }); await settleJournal(h);
            assert.deepEqual(h.commands.map(command => command.data), policy === 'resume_expired' ? [{state: 'OFF'}] : []);
            if (policy !== 'abort') assert.ok(h.logs.some(line => line.includes(policy === 'resume' ? 'Wartezeit abgelaufen' : 'Schritt mit erneuter Bedingungsprüfung nachholen')));
        } finally { await h.close(); }
    }
});

test('recovered waits require fresh normal conditions, preserve start-only semantics and reject clock rollback', async () => {
    for (const mode of ['fresh', 'missing', 'start_only', 'rollback']) {
        let h = await harness(); const directory = h.directory;
        try {
            const r = {...rule(), restart_policy: 'resume', conditions: [{type: 'device_value', device: sourceID,
                property: 'illuminance', operator: 'lt', value: 50, ...(mode === 'start_only' ? {start_only: true} : {})}]};
            r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
            await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true, illuminance: 10}); await settleJournal(h);
            const wall = h.clock.wallNow(); await h.close(false);
            h = await harness(directory, true, h => { h.clock.wall = wall + (mode === 'rollback' ? -1000 : 1000); }); await settleJournal(h);
            if (mode === 'fresh') await h.event({illuminance: 10});
            await h.clock.advance(5000); await settleJournal(h);
            assert.equal(h.commands.length, ['fresh', 'start_only'].includes(mode) ? 1 : 0, mode);
        } finally { await h.close(); }
    }
});

test('explicit global stop and changed configuration cannot resurrect waiting automatic runs', async () => {
    for (const mode of ['stop', 'changed', 'disabled']) {
        let h = await harness(); const directory = h.directory;
        try {
            const r = {...rule(), restart_policy: 'resume_expired'}; r.actions[0].delay_seconds = 5;
            await h.store(yaml([r])); await h.activate({resume_after_restart: mode !== 'disabled'});
            await h.event({occupancy: true}); await settleJournal(h);
            if (mode === 'stop') await h.deactivate();
            if (mode === 'changed') await h.store(yaml([{...r, name: 'Changed synthetic rule'}], 2));
            await h.close(false); h = await harness(directory); await settleJournal(h);
            await h.clock.advance(10000); assert.equal(h.commands.length, 0, mode);
        } finally { await h.close(); }
    }
});

test('restart continuation is opt-in and accepts only the three declared policies', () => {
    for (const restart_policy of ['abort', 'resume', 'resume_expired']) {
        assert.doesNotThrow(() => validateYaml(yaml([{...rule(), restart_policy}])));
    }
    for (const restart_policy of [null, true, 'retry', {}, 1]) {
        assert.throws(() => validateYaml(yaml([{...rule(), restart_policy}])));
    }
    assert.throws(() => validateYaml(yaml([{...rule(), restart_policy: 'retry'}])), error =>
        error.details?.issues.some(issue => issue.path === '/automations/0/restart_policy'));
});

test('an uncertain device handoff has no recoverable checkpoint and is not repeated after restart', async () => {
    const first = await harness(); let second; let release;
    try {
        const r = {...rule(), restart_policy: 'resume_expired'};
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await first.store(yaml([r])); await first.activate(); await first.event({occupancy: true}); await settleJournal(first);
        let handoffs = 0;
        first.extension.runtime.dispatch = () => { handoffs++; return new Promise(resolve => { release = resolve; }); };
        await first.clock.advance(5000); await settleJournal(first);
        assert.equal(handoffs, 1);
        const envelope = JSON.parse(await readFile(join(first.directory, 'running-automations.json'), 'utf8'));
        assert.deepEqual(JSON.parse(envelope.payload).runs, [], 'Durable consume happens before the unconfirmed handoff');
        second = await harness(first.directory); await settleJournal(second); await second.clock.advance(10000);
        assert.equal(second.commands.length, 0, 'Cold extension startup cannot repeat the uncertain action');
    } finally {
        release?.(); await first.close(false); if (second) await second.close(); else await rm(first.directory, {recursive: true, force: true});
    }
});

test('journal backpressure is bounded and failures cannot create an unlimited persistence queue', async () => {
    let release; const gate = new Promise(resolve => { release = resolve; });
    const journal = new RunJournal({atomicWrite: () => gate}, () => ({system: 'synthetic/system', revision: 1, checksum: 'a'.repeat(64)}), () => true);
    const pending = [];
    for (let i = 0; i < 65; i++) pending.push(journal.put({rule_id: 'synthetic_rule', run: '11111111-1111-4111-8111-111111111111',
        step: 1, saved_at: 1000, deadline: 2000 + i, delay: 5000}).then(() => true, () => false));
    assert.equal(journal.pending, 64);
    release(); const results = await Promise.all(pending);
    assert.equal(results.filter(ok => !ok).length, 1);
    assert.equal(journal.pending, 0); assert.equal(journal.failed, true);
});

test('renewed waits retain their full duration across multiple restarts and preserve reusable sequence position', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        const r = {...rule(), restart_policy: 'resume', retrigger: 'extend', actions: [{type: 'sequence', sequence_id: 'synthetic_sequence'}]};
        const document = {schema_version: 1, revision: 1, automations: [r], sequences: [{id: 'synthetic_sequence', name: 'Synthetic sequence',
            actions: [rule().actions[0], {...rule().actions[0], values: {state: 'OFF'}, delay_seconds: 5}]}]};
        await h.store(JSON.stringify(document)); await h.activate(); await h.event({occupancy: true}); await settleJournal(h);
        await h.clock.advance(3000); await h.event({occupancy: true}); await settleJournal(h);
        let wall = h.clock.wallNow(); await h.close(false);
        h = await harness(directory, true, h => { h.clock.wall = wall + 1000; }); await settleJournal(h);
        await h.clock.advance(1000); await settleJournal(h); assert.equal(h.commands.length, 0);
        wall = h.clock.wallNow(); await h.close(false);
        h = await harness(directory, true, h => { h.clock.wall = wall + 1000; }); await settleJournal(h);
        await h.clock.advance(1999); await settleJournal(h); assert.equal(h.commands.length, 0);
        await h.clock.advance(1); await settleJournal(h);
        assert.deepEqual(h.commands.map(command => command.data), [{state: 'OFF'}]);
        assert.ok(h.logs.some(line => line.includes('"sequence_step":2')));
    } finally { await h.close(); }
});

test('invalid journals, wrong system bindings and changed checksums never authorize continuation', async () => {
    for (const mode of ['corrupt', 'foreign', 'checksum', 'duplicate']) {
        let h = await harness(); const directory = h.directory;
        try {
            const r = {...rule(), restart_policy: 'resume_expired'}; r.actions[0].delay_seconds = 5;
            await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true}); await settleJournal(h); await h.close(false);
            const path = join(directory, 'running-automations.json');
            const envelope = JSON.parse(await readFile(path, 'utf8')), saved = JSON.parse(envelope.payload);
            if (mode === 'foreign') saved.system = 'synthetic/foreign';
            if (mode === 'checksum') saved.checksum = '0'.repeat(64);
            if (mode === 'duplicate') saved.runs.push(saved.runs[0]);
            envelope.payload = JSON.stringify(saved); envelope.checksum = createHash('sha256').update(envelope.payload).digest('hex');
            await writeFile(path, mode === 'corrupt' ? '{invalid-json' : JSON.stringify(envelope));
            h = await harness(directory); await h.clock.advance(10000);
            assert.equal(h.commands.length, 0, mode);
            assert.ok(h.logs.some(line => line.includes('Fortsetzungsspeicher unlesbar oder unpassend')));
            assert.equal(h.extension.status().executing, true, 'Fresh automatic triggers remain available');
            assert.equal(h.extension.status().runtime_error, 'storage_error', 'The recovery failure is also visible in status');
                if (mode === 'corrupt') {
                    assert.equal((await h.activate()).status, 'ok', 'Explicit activation repairs the failed journal before allowing recovery again');
                    const envelope = JSON.parse(await readFile(join(directory, 'running-automations.json'), 'utf8'));
                    assert.deepEqual(JSON.parse(envelope.payload).runs, [], 'Repair cannot re-arm an old rejected run');
                    assert.equal(h.extension.runJournal.failed, false);
                }
        } finally { await h.close(); }
    }
});

test('journal write failures stop the tail before handoff and single starts are never persisted', async () => {
    for (const mode of ['failure', 'single', 'targeted_stop']) {
        let h = await harness(); const directory = h.directory;
        try {
            const r = {...rule(), restart_policy: 'resume_expired'};
            r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
            await h.store(yaml([r])); await h.activate();
            if (mode === 'failure') {
                const write = h.extension.store.atomicWrite.bind(h.extension.store);
                h.extension.store.atomicWrite = (name, text) => name === 'running-automations.json' ? Promise.reject(new Error('synthetic private error')) : write(name, text);
            }
            if (mode === 'single') await h.request('run_once', {session: h.session, rule_id: r.id, expected_revision: 1, checksum: h.extension.status().checksum});
            else await h.event({occupancy: true});
            if (mode === 'failure') {
                for (let i = 0; i < 8; i++) await new Promise(resolve => setImmediate(resolve));
                await h.clock.advance(6000);
                assert.deepEqual(h.commands.map(command => command.data), [{state: 'ON'}]);
                assert.equal(h.extension.status().execution.runs[0].error, 'storage_error');
            } else {
                await settleJournal(h);
                if (mode === 'targeted_stop') {
                    const run = h.extension.status().execution.runs[0].run;
                    const result = await h.request('stop_rule', {session: h.session, rule_id: r.id, run, expected_revision: 1, checksum: h.extension.status().checksum});
                    assert.equal(result.status, 'ok');
                }
                await h.close(false); h = await harness(directory); await settleJournal(h);
                await h.clock.advance(10000); assert.equal(h.commands.length, 0, mode);
            }
        } finally { await h.close(); }
    }
});

test('a delayed composite set with RGB color is handed off once with every typed property intact', async () => {
    const h = await harness();
    try {
        const r = rule(); r.actions[0].values = {state: 'ON', brightness: 80, color: {hex: '#547CFF'}};
        r.actions[0].delay_seconds = 2;
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true});
        assert.equal(h.commands.length, 0); await h.clock.advance(2000);
        assert.equal(h.commands.length, 1); assert.deepEqual(h.commands[0].data, r.actions[0].values);
        assert.equal(h.extension.status().execution.runs[0].step, 1);
    } finally { await h.close(); }
});

test('retrigger policies validate strictly while missing policy keeps ignore semantics', () => {
    for (const retrigger of ['ignore', 'restart', 'extend']) assert.doesNotThrow(() => validateYaml(yaml([{...rule(), retrigger}])));
    for (const retrigger of ['', 'parallel', null, true, 1, {}]) assert.throws(() => validateYaml(yaml([{...rule(), retrigger}])));
});

test('start-only conditions reject unknown or bright starts but allow extension and delayed off after it gets bright', async () => {
    const h = await harness();
    try {
        const r = {...rule(), retrigger: 'extend', conditions: [{type: 'device_value', device: sourceID,
            property: 'illuminance', operator: 'lt', value: 20, start_only: true}]};
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        assert.doesNotThrow(() => validateYaml(yaml([r])));
        await h.store(yaml([r])); await h.activate();
        await h.event({occupancy: true}); assert.equal(h.commands.length, 0, 'Unknown brightness cannot authorize a start');
        await h.clock.advance(1000); await h.event({occupancy: true, illuminance: 30}); assert.equal(h.commands.length, 0);
        await h.clock.advance(1000); await h.event({occupancy: true, illuminance: 10});
        assert.deepEqual(h.commands.map(c => c.data.state), ['ON']);
        const run = h.extension.status().execution.runs[0].run;
        await h.clock.advance(3000); await h.event({occupancy: true, illuminance: 30});
        await h.clock.advance(2000); assert.equal(h.commands.length, 1, 'New movement renewed the original deadline despite brightness');
        await h.clock.advance(3000); assert.deepEqual(h.commands.map(c => c.data.state), ['ON', 'OFF']);
        assert.equal(h.extension.status().execution.runs[0].run, run);
        await h.clock.advance(1000); await h.event({occupancy: true}); assert.equal(h.commands.length, 2, 'A subsequent new run must check brightness again');
        assert.ok(h.logs.some(line => line.includes('Nur beim Start gültige Bedingung')));
    } finally { await h.close(); }
});

test('start-only flags validate strictly and restarting still requires every start condition', async () => {
    const condition = {type: 'device_value', device: sourceID, property: 'illuminance', operator: 'lt', value: 20};
    for (const start_only of ['true', 1, null, {}]) assert.throws(() => validateYaml(yaml([{...rule(), conditions: [{...condition, start_only}]}])));
    const h = await harness();
    try {
        const r = {...rule(), retrigger: 'restart', conditions: [{...condition, start_only: true}]};
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true, illuminance: 10});
        const run = h.extension.status().execution.runs[0].run;
        await h.clock.advance(2000); await h.event({occupancy: true, illuminance: 30});
        assert.equal(h.extension.status().execution.runs[0].run, run, 'Rejected restart preserves the existing run');
        await h.clock.advance(3000); assert.deepEqual(h.commands.map(c => c.data.state), ['ON', 'OFF']);
    } finally { await h.close(); }
});

test('start-only conditions also authorize a one-shot run once while continuous conditions still guard its tail', async () => {
    const h = await harness();
    try {
        const r = {...rule(), enabled: false, conditions: [{type: 'device_value', device: sourceID,
            property: 'illuminance', operator: 'lt', value: 20, start_only: true}]};
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r]));
        await runOnce(h); await flush();
        assert.equal(h.extension.status().execution.runs[0].error, 'conditions_not_met');
        h.targetStates.set(sourceID, {illuminance: 10});
        assert.equal((await runOnce(h)).status, 'ok'); await flush();
        await h.clock.advance(2000); await h.event({illuminance: 30});
        await h.clock.advance(3000); assert.deepEqual(h.commands.map(c => c.data.state), ['ON', 'OFF']);
        assert.equal(h.extension.status().executing, false, 'One-shot execution does not enable automatic execution');
        r.conditions.push({type: 'device_value', device: sourceID, property: 'occupancy', operator: 'eq', value: true});
        await h.store(yaml([r], 2)); h.targetStates.set(sourceID, {illuminance: 10, occupancy: true});
        await runOnce(h); await flush(); await h.clock.advance(2000); await h.event({occupancy: false});
        await h.clock.advance(3000); assert.deepEqual(h.commands.map(c => c.data.state), ['ON', 'OFF', 'ON']);
        assert.equal(h.extension.status().execution.runs[0].state, 'skipped', 'Only the explicitly marked condition is exempt from rechecking');
    } finally { await h.close(); }
});

test('extend renews only the current delay without repeating prior actions and respects cooldown', async () => {
    const h = await harness();
    try {
        const r = {...rule(), retrigger: 'extend'};
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true});
        await h.request('debug', {session: h.session, client: '11111111-1111-4111-8111-111111111111', seconds: 60});
        const run = h.extension.status().execution.runs[0].run;
        await h.clock.advance(3000); await h.event({occupancy: true});
        await h.clock.advance(500); await h.event({occupancy: true});
        await h.clock.advance(4499); assert.deepEqual(h.commands.map(c => c.data.state), ['ON']);
        await h.clock.advance(1); assert.deepEqual(h.commands.map(c => c.data.state), ['ON', 'OFF']);
        assert.equal(h.extension.status().execution.runs[0].run, run);
        assert.ok(h.logs.some(line => line.includes('Wartezeit wegen erneuter Auslösung auf volle Dauer zurückgesetzt.')));
        const code = Object.keys(DEBUG_EVENTS).find(key => DEBUG_EVENTS[key].message.startsWith('Wartezeit wegen erneuter'));
        const record = h.messages.find(m => m.data.record?.event === code)?.data.record;
        assert.equal(record?.run, run); assert.equal(record.step, 2); assert.equal(record.delay, 5);
    } finally { await h.close(); }
});

test('restart cancels the old wait, creates one new run and never overlaps the old tail', async () => {
    const h = await harness();
    try {
        const r = {...rule(), retrigger: 'restart'};
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true});
        const old = h.extension.status().execution.runs[0].run;
        await h.clock.advance(3000); await h.event({occupancy: true});
        assert.notEqual(h.extension.status().execution.runs[0].run, old);
        assert.deepEqual(h.commands.map(c => c.data.state), ['ON', 'ON']);
        await h.clock.advance(2000); assert.equal(h.commands.length, 2);
        await h.clock.advance(3000); assert.deepEqual(h.commands.map(c => c.data.state), ['ON', 'ON', 'OFF']);
    } finally { await h.close(); }
});

test('failed retrigger conditions preserve the original wait and explicit single starts stay busy', async () => {
    for (const retrigger of ['restart', 'extend']) {
        const h = await harness();
        try {
            const r = {...rule(), retrigger, conditions: [{type: 'device_value', device: sourceID, property: 'illuminance', operator: 'lt', value: 20}]};
            r.actions[0].delay_seconds = 5;
            await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true, illuminance: 10});
            const run = h.extension.status().execution.runs[0].run;
            await h.clock.advance(2000); await h.event({occupancy: true, illuminance: 30});
            assert.equal(h.extension.status().execution.runs[0].run, run);
            assert.equal((await runOnce(h)).error.code, 'rule_busy');
            await h.event({illuminance: 10}); await h.clock.advance(3000);
            assert.equal(h.commands.length, 1, 'Rejected trigger did not change the original deadline');
        } finally { await h.close(); }
    }
});

test('restart waits for an in-flight handoff, coalesces triggers and abandons replacement on failure', async () => {
    for (const fail of [false, true]) {
        const h = await harness(); let release;
        try {
            const r = {...rule(), retrigger: 'restart'};
            r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
            await h.store(yaml([r])); await h.activate();
            let dispatches = 0;
            h.extension.runtime.dispatch = () => { dispatches++; return dispatches === 1 ? new Promise((resolve, reject) => { release = () => fail ? reject(new Error()) : resolve(); }) : undefined; };
            await h.event({occupancy: true}); await h.clock.advance(2000); await h.event({occupancy: true});
            await h.clock.advance(1000); await h.event({occupancy: true});
            assert.equal(dispatches, 1, 'No second handoff before the first settles');
            release(); await flush();
            assert.equal(dispatches, fail ? 1 : 2);
            if (fail) assert.equal(h.extension.status().execution.runs[0].state, 'failed');
            await h.deactivate(); await h.clock.advance(10000);
            assert.equal(dispatches, fail ? 1 : 2);
        } finally { release?.(); await h.close(); }
    }
});

test('targeted stop and changed storage discard pending restarts and renewed waits', async () => {
    for (const retrigger of ['restart', 'extend']) {
        const h = await harness();
        try {
            const r = {...rule(), retrigger}; r.actions[0].delay_seconds = 5;
            await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true});
            await h.clock.advance(2000); await h.event({occupancy: true});
            const run = h.extension.status().execution.runs[0];
            assert.equal((await h.request('stop_rule', {session: h.session, rule_id: r.id, run: run.run, expected_revision: 1, checksum: h.extension.status().checksum})).status, 'ok');
            await h.clock.advance(10000); assert.equal(h.commands.length, 0);
            await h.event({occupancy: true}); await h.clock.advance(2000); await h.event({occupancy: true});
            await h.store(yaml([r], 2)); await h.clock.advance(10000); assert.equal(h.commands.length, 0);
        } finally { await h.close(); }
    }
});

test('ignore preserves the existing deadline and extend cannot replay an action during a handoff', async () => {
    for (const policy of [undefined, 'ignore', 'extend']) {
        const h = await harness(); let release;
        try {
            const r = rule(); if (policy) r.retrigger = policy;
            await h.store(yaml([r])); await h.activate();
            let count = 0;
            h.extension.runtime.dispatch = () => { count++; return new Promise(resolve => { release = resolve; }); };
            await h.event({occupancy: true}); await h.clock.advance(2000); await h.event({occupancy: true});
            assert.equal(count, 1); release(); await flush();
            assert.equal(h.extension.status().execution.runs[0].state, 'completed');
        } finally { release?.(); await h.close(); }
    }
});

test('explicit stop cancels a restart queued behind a handoff and timeout never retries it', async () => {
    for (const stop of [false, true]) {
        const h = await harness();
        try {
            const r = {...rule(), retrigger: 'restart'};
            await h.store(yaml([r])); await h.activate();
            let count = 0;
            h.extension.runtime.dispatch = () => { count++; return new Promise(() => {}); };
            await h.event({occupancy: true}); await h.clock.advance(2000); await h.event({occupancy: true});
            if (stop) {
                const run = h.extension.status().execution.runs[0];
                assert.equal((await h.request('stop_rule', {session: h.session, rule_id: r.id, run: run.run, expected_revision: 1, checksum: h.extension.status().checksum})).status, 'ok');
            }
            await h.clock.advance(5000);
            assert.equal(count, 1);
            assert.equal(h.extension.status().execution.runs[0].state, stop ? 'stopped' : 'failed');
        } finally { await h.close(); }
    }
});

test('clock retriggers restart a waiting rule and renewed waits do not survive an extension restart', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        const r = {...rule(), retrigger: 'restart'};
        r.triggers = ['12:00:01', '12:00:03'].map(at => ({type: 'time', at, timezone: 'UTC'}));
        r.actions[0].delay_seconds = 5;
        await h.store(yaml([r])); await h.activate();
        await h.clock.advance(1000); const old = h.extension.status().execution.runs[0].run;
        await h.clock.advance(2000); assert.notEqual(h.extension.status().execution.runs[0].run, old);
        await h.clock.advance(3000); assert.equal(h.commands.length, 0);
        await h.clock.advance(2000); assert.equal(h.commands.length, 1);
        r.retrigger = 'extend'; r.triggers = rule().triggers;
        await h.store(yaml([r], 2)); await h.activate(); await h.event({occupancy: true});
        await h.clock.advance(2000); await h.event({occupancy: true}); await h.close(false);
        h = await harness(directory); assert.equal(h.extension.status().executing, true);
        await h.clock.advance(10000); assert.equal(h.commands.length, 0);
        await h.event({occupancy: true}); await h.clock.advance(5000); assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('weekday clauses validate independently and reject malformed days, zones and oversized time lists', () => {
    const r = rule(); r.conditions = [{type: 'weekday', weekdays: [1, 3, 7], timezone: 'Pacific/Auckland'}];
    assert.deepEqual(JSON.parse(JSON.stringify(validateYaml(yaml([r])).document.automations[0].conditions)), r.conditions);
    for (const weekdays of [[], [0], [8], [1, 1], ['1'], [true], [1.5], [1, 2, 3, 4, 5, 6, 7, 8]]) {
        assert.throws(() => validateYaml(yaml([{...r, conditions: [{...r.conditions[0], weekdays}]}])));
    }
    assert.throws(() => validateYaml(yaml([{...r, conditions: [{...r.conditions[0], timezone: 'invalid-zone'}]}])));
    assert.throws(() => validateYaml(yaml([{...r, conditions: [{...r.conditions[0], after: '00:00:00'}]}])));
    const triggers = Array.from({length: 8}, (_, i) => ({type: 'time', at: `0${i}:00:00`, timezone: 'UTC'}));
    assert.equal(validateYaml(yaml([{...r, triggers}])).document.automations[0].triggers.length, 8);
    assert.throws(() => validateYaml(yaml([{...r, triggers: [...triggers, {type: 'time', at: '08:00:00', timezone: 'UTC'}]}])));
});

test('multiple clock slots independently run on allowed days and DST cannot replay either slot', async () => {
    const h = await harness();
    try {
        const r = rule(); r.triggers = ['02:30:00', '02:45:00'].map(at => ({type: 'time', at, timezone: 'Europe/Berlin'}));
        r.conditions = [{type: 'weekday', weekdays: [7], timezone: 'Europe/Berlin'}];
        await h.store(yaml([r])); await h.activate();
        for (const [at, count] of [['2026-10-25T00:29:59Z', 1], ['2026-10-25T00:44:59Z', 2],
            ['2026-10-25T01:29:59Z', 2], ['2026-10-25T01:44:59Z', 2], ['2026-10-26T01:29:59Z', 2], ['2026-11-01T01:29:59Z', 3]]) {
            h.clock.wall = Date.parse(at); await h.clock.advance(1000);
            assert.equal(h.commands.length, count, at);
        }
        assert.ok(h.logs.some(line => line.includes('Kalendertag gegen erlaubte Wochentage geprüft.') && line.includes('false')));
    } finally { await h.close(); }
});

test('calendar weekdays use their own zone and remain distinct from overnight window start days', async () => {
    const h = await harness();
    try {
        const r = rule(); r.conditions = [{type: 'weekday', weekdays: [2], timezone: 'Pacific/Auckland'}];
        await h.store(yaml([r])); await h.activate();
        h.clock.wall = Date.parse('2026-11-02T10:59:00Z'); await h.event({occupancy: true});
        assert.equal(h.commands.length, 0, 'It is still Monday in Auckland');
        await h.clock.advance(1000); h.clock.wall = Date.parse('2026-11-02T11:00:00Z'); await h.event({occupancy: true});
        assert.equal(h.commands.length, 1, 'Tuesday locally, Monday UTC; no time window is required');
        r.conditions = [{type: 'weekday', weekdays: [2], timezone: 'UTC'},
            {type: 'time_window', after: '22:00:00', before: '06:00:00', timezone: 'UTC', weekdays: [1]}];
        await h.store(yaml([r], 2)); await h.activate();
        h.clock.wall = Date.parse('2026-11-03T01:00:00Z'); await h.event({occupancy: true});
        assert.equal(h.commands.length, 2, 'Tuesday calendar day and Monday window start both match');
        await h.clock.advance(1000); h.clock.wall = Date.parse('2026-11-03T22:00:00Z'); await h.event({occupancy: true});
        assert.equal(h.commands.length, 2, 'Tuesday window start is not permitted');
    } finally { await h.close(); }
});

test('weekday conditions are checked for one-shot execution and rechecked after a midnight delay', async () => {
    const h = await harness();
    try {
        const r = rule(); r.enabled = false; r.conditions = [{type: 'weekday', weekdays: [1], timezone: 'UTC'}];
        r.actions[0].delay_seconds = 5;
        await h.store(yaml([r]));
        h.clock.wall = Date.parse('2026-11-01T23:59:00Z'); await runOnce(h); await flush();
        assert.equal(h.extension.status().execution.runs[0].state, 'skipped');
        await h.clock.advance(1000); h.clock.wall = Date.parse('2026-11-02T23:59:58Z'); await runOnce(h); await flush();
        assert.equal(h.extension.status().execution.runs[0].state, 'running');
        await h.clock.advance(5000);
        assert.equal(h.commands.length, 0, 'Tuesday is rechecked before the delayed action');
        assert.equal(h.extension.status().execution.runs[0].state, 'skipped');
    } finally { await h.close(); }
});

test('restart restores multiple times and weekday conditions without replaying a missed clock slot', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        const r = rule(); r.triggers = ['07:00:00', '18:00:00'].map(at => ({type: 'time', at, timezone: 'UTC'}));
        r.conditions = [{type: 'weekday', weekdays: [1], timezone: 'UTC'}];
        await h.store(yaml([r])); await h.activate(); await h.close(false);
        h = await harness(directory, true, context => { context.clock.wall = Date.parse('2026-11-02T07:00:10Z'); });
        assert.equal(h.extension.status().executing, true);
        await h.clock.advance(1000); assert.equal(h.commands.length, 0);
        h.clock.wall = Date.parse('2026-11-02T17:59:59Z'); await h.clock.advance(1000);
        assert.equal(h.commands.length, 1);
        h.clock.wall = Date.parse('2026-11-03T06:59:59Z'); await h.clock.advance(1000);
        assert.equal(h.commands.length, 1, 'Restored weekday restriction still blocks Tuesday');
    } finally { await h.close(); }
});

test('reusable sequences execute in rule order with call delay, step delays and conditions without changing definitions', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.actions = [{type: 'sequence', sequence_id: 'synthetic_sequence', delay_seconds: 2}, {...rule().actions[0], values: {state: 'OFF'}}];
        const document = {schema_version: 1, revision: 1, automations: [r], sequences: [{id: 'synthetic_sequence', name: 'Synthetic reusable name',
            actions: [{...rule().actions[0], delay_seconds: 1}, {...rule().actions[0], values: {state: 'OFF'}, delay_seconds: 3}]}]};
        const text = JSON.stringify(document);
        assert.equal((await h.store(text)).status, 'ok'); await h.activate(); await h.event({occupancy: true});
        await h.clock.advance(2999); assert.equal(h.commands.length, 0);
        await h.clock.advance(1); assert.deepEqual(h.commands.map(item => item.data.state), ['ON']);
        await h.clock.advance(3000); assert.deepEqual(h.commands.map(item => item.data.state), ['ON', 'OFF', 'OFF']);
        assert.equal(h.extension.status().execution.runs[0].step, 3);
        assert.equal(h.extension.store.current.yaml, text, 'Expansion never edits the persisted definitions');
        await h.clock.advance(1000); await runOnce(h); await flush();
        const run = h.extension.status().execution.runs[0];
        await h.request('stop_rule', {session: h.session, rule_id: r.id, expected_revision: 1, checksum: h.extension.status().checksum, run: run.run});
        await h.clock.advance(10000); assert.equal(h.commands.length, 3, 'Targeted stop cancels the whole reusable sequence');
    } finally { await h.close(); }
});

test('sequence references reject missing definitions, duplicate IDs, nesting, excess expansion and combined delay overflow', () => {
    const leaf = rule().actions[0];
    const doc = {schema_version: 1, revision: 1, automations: [{...rule(), actions: [{type: 'sequence', sequence_id: 'synthetic_sequence'}]}],
        sequences: [{id: 'synthetic_sequence', name: 'Synthetic', actions: [leaf]}]};
    assert.doesNotThrow(() => validateYaml(JSON.stringify(doc)));
    for (const change of [d => { d.sequences = []; }, d => { d.sequences.push(d.sequences[0]); },
        d => { d.sequences[0].actions = [{type: 'sequence', sequence_id: 'synthetic_sequence'}]; },
        d => { d.sequences[0].actions = Array(16).fill(leaf); d.automations[0].actions.push(leaf); },
        d => { d.automations[0].actions[0].delay_seconds = 86400; d.sequences[0].actions[0].delay_seconds = 1; },
        d => { d.sequences = Array.from({length: 33}, (_, i) => ({id: 'sequence_' + i, name: 'Synthetic', actions: [leaf]})); }]) {
        const invalid = structuredClone(doc); change(invalid);
        assert.throws(() => validateYaml(JSON.stringify(invalid)), error => ['invalid_schema', 'invalid_semantics'].includes(error.code));
    }
});

test('two rules sharing one sequence remain independent when exactly one run is stopped', async () => {
    const h = await harness();
    try {
        const a = {...rule(), id: 'first_rule', actions: [{type: 'sequence', sequence_id: 'shared_sequence'}]};
        const b = {...a, id: 'second_rule'};
        await h.store(JSON.stringify({schema_version: 1, revision: 1, automations: [a, b], sequences: [{id: 'shared_sequence', name: 'Synthetic shared plan',
            actions: [{...rule().actions[0], delay_seconds: 2}, {...rule().actions[0], values: {state: 'OFF'}}]}]}));
        await h.activate(); await h.event({occupancy: true});
        const status = h.extension.status();
        const selected = status.execution.runs.find(item => item.rule_id === a.id);
        await h.request('stop_rule', {session: h.session, rule_id: a.id, run: selected.run, expected_revision: 1, checksum: status.checksum});
        await h.clock.advance(2000);
        assert.deepEqual(h.commands.map(item => item.data.state), ['ON', 'OFF']);
        assert.equal(h.extension.status().execution.runs.find(item => item.rule_id === a.id).state, 'stopped');
        assert.equal(h.extension.status().execution.runs.find(item => item.rule_id === b.id).state, 'completed');
    } finally { await h.close(); }
});

test('conditions are rechecked inside a reusable sequence and prevent its remaining actions', async () => {
    const h = await harness();
    try {
        const r = rule(); r.conditions = [{type: 'device_value', device: sourceID, property: 'occupancy', operator: 'eq', value: true}];
        r.actions = [{type: 'sequence', sequence_id: 'synthetic_sequence'}];
        const doc = {schema_version: 1, revision: 1, automations: [r], sequences: [{id: 'synthetic_sequence', name: 'Synthetic condition plan',
            actions: [rule().actions[0], {...rule().actions[0], values: {state: 'OFF'}, delay_seconds: 2}]}]};
        await h.store(JSON.stringify(doc)); await h.activate(); await h.event({occupancy: true});
        assert.equal(h.commands.length, 1);
        await h.event({occupancy: false}); await h.clock.advance(2000);
        assert.equal(h.commands.length, 1);
        assert.equal(h.extension.status().execution.runs[0].state, 'skipped');
        assert.equal(h.extension.status().execution.runs[0].step, 2);
    } finally { await h.close(); }
});

test('reusable scene actions validate their definition location and are rechecked before each invocation', async () => {
    const h = await harness();
    try {
        const r = rule(); r.actions = [{type: 'sequence', sequence_id: 'synthetic_scene_sequence'}];
        const doc = {schema_version: 1, revision: 1, automations: [r], sequences: [{id: 'synthetic_scene_sequence', name: 'Synthetic scene plan',
            actions: [{type: 'scene', target: {type: 'device', id: targetID}, scene_id: 0, endpoint: 2}]}]};
        const invalid = await h.request('validate', {yaml: JSON.stringify(doc)});
        assert.deepEqual(invalid.error.details.issues[0], {path: '/sequences/0/actions/0/scene_id', reason: 'scene_unavailable'});
        h.devices.get(targetID).zh = {endpoints: [{ID: 2, meta: {scenes: {'0_0': {name: 'Synthetic scene'}}}}]};
        await h.store(JSON.stringify(doc)); await h.activate(); await h.event({occupancy: true});
        assert.deepEqual(h.commands[0].data, {scene_recall: 0});
        delete h.devices.get(targetID).zh;
        await h.clock.advance(1000);
        assert.equal((await runOnce(h)).error.code, 'scene_unavailable');
        assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('stored definitions resume after restart without replaying interrupted sequence steps', async () => {
    const h = await harness();
    try {
        const r = rule(); r.actions = [{type: 'sequence', sequence_id: 'synthetic_sequence'}];
        await h.store(JSON.stringify({schema_version: 1, revision: 1, automations: [r], sequences: [{id: 'synthetic_sequence', name: 'Synthetic persisted plan',
            actions: [{...rule().actions[0], delay_seconds: 5}, {...rule().actions[0], values: {state: 'OFF'}}]}]}));
        await h.activate(); await h.event({occupancy: true}); await h.close(false);
        const next = await harness(h.directory);
        try {
            assert.equal(next.extension.status().executing, true);
            await next.clock.advance(10000); assert.equal(next.commands.length, 0);
            await next.event({occupancy: true}); await next.clock.advance(5000);
            assert.deepEqual(next.commands.map(command => command.data.state), ['ON', 'OFF']);
        } finally { await next.close(); }
    } finally { await h.close(); }
});

async function runOnce(h, id = 'synthetic_rule', overrides = {}, event = {}) {
    const status = (await h.request('status')).data;
    return h.request('run_once', {session: status.session, activation_token: status.activation_token,
        expected_revision: status.stored_revision, checksum: status.checksum, rule_id: id, ...overrides}, event);
}

test('one-shot toggle and scene actions share native dispatch and current time conditions', async () => {
    const h = await harness();
    try {
        const r = rule(); r.enabled = false;
        r.actions = [{type: 'toggle', target: {type: 'device', id: targetID}, property: 'state'}];
        r.conditions = [{type: 'time_window', after: '12:00:00', before: '13:00:00', timezone: 'UTC'}];
        h.clock.wall = Date.parse('2026-01-01T11:59:59Z');
        await h.store(yaml([r])); await runOnce(h);
        assert.equal(h.commands.length, 0);
        assert.equal(h.extension.status().execution.runs[0].state, 'skipped');
        await h.clock.advance(1000); await runOnce(h); await flush();
        assert.deepEqual(h.commands[0].data, {state: 'ON'});
        h.devices.get(targetID).zh = {endpoints: [{ID: 2, meta: {scenes: {'0_0': {name: 'Synthetic scene'}}}}]};
        r.actions = [{type: 'scene', target: {type: 'device', id: targetID}, scene_id: 0, endpoint: 2}];
        await h.store(yaml([r], 2)); await runOnce(h); await flush();
        assert.equal(h.commands[1].topic, 'synthetic/target/2/set');
        assert.deepEqual(h.commands[1].data, {scene_recall: 0});
        assert.equal(h.extension.status().executing, false);
    } finally { await h.close(); }
});

test('global stop and changed storage cancel one-shot waits while identical storage preserves them', async () => {
    const h = await harness();
    try {
        const r = rule(); r.actions[0].delay_seconds = 5;
        const text = yaml([r]);
        await h.store(text); await runOnce(h); await flush();
        await h.store(text);
        assert.equal(h.extension.status().execution.runs[0].state, 'running');
        await h.clock.advance(5000); assert.equal(h.commands.length, 1);
        await h.clock.advance(1000); await runOnce(h); await flush();
        await h.deactivate(); await h.clock.advance(5000);
        assert.equal(h.commands.length, 1);
        assert.equal(h.extension.status().execution.runs[0].state, 'stopped');
        await runOnce(h); await flush(); await h.store(yaml([r], 2));
        await h.clock.advance(5000); assert.equal(h.commands.length, 1);
        assert.deepEqual(h.extension.status().execution.runs, [], 'Old snapshot results are not assigned to the newly saved revision');
    } finally { await h.close(); }
});

test('one-shot requests cannot reuse a consumed approval or wait for recording/status delivery', async () => {
    const h = await harness();
    try {
        await h.store(yaml());
        const status = (await h.request('status')).data;
        const command = {session: status.session, rule_id: 'synthetic_rule', expected_revision: 1,
            checksum: status.checksum, activation_token: status.activation_token};
        const publish = h.extension.mqtt.publish;
        let pendingReports = 0;
        h.extension.mqtt.publish = (topic, ...args) => {
            if (topic === 'symcon/automations/runs') { pendingReports++; return new Promise(() => {}); }
            return publish(topic, ...args);
        };
        await h.request('run_once', command); await flush();
        assert.equal(h.commands.length, 1, 'A stalled run-status Publish cannot stall the device action');
        assert.equal(pendingReports, 1, 'Only one report is in flight; further changes are coalesced');
        assert.equal(h.extension.status().execution.runs[0].state, 'completed');
        assert.equal((await h.request('run_once', command)).error.code, 'activation_expired');
    } finally { await h.close(); }
});

test('a disabled saved rule runs once while automatic execution remains stopped and restart intent is unchanged', async () => {
    const h = await harness();
    try {
        const r = rule(); r.enabled = false;
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r]));
        const before = h.extension.executionIntent;
        const response = await runOnce(h);
        assert.equal(response.status, 'ok'); await flush();
        assert.equal(h.commands.length, 1);
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.extension.status().execution.runs[0].state, 'running');
        await h.event({occupancy: true});
        assert.equal(h.commands.length, 1, 'Manual execution never arms a disabled trigger');
        await h.clock.advance(5000);
        assert.deepEqual(h.commands.map(item => item.data.state), ['ON', 'OFF']);
        assert.equal(h.extension.status().execution.runs[0].state, 'completed');
        assert.equal(h.extension.status().execution.runs[0].step, 2);
        assert.deepEqual(h.extension.executionIntent, before);
        assert.ok(h.logs.some(line => line.includes('Einmaliger Regellauf')));
        await h.clock.advance(1000); await runOnce(h); await flush();
        await h.close(false);
        const restarted = await harness(h.directory);
        try { await restarted.clock.advance(6000); assert.equal(restarted.commands.length, 0); }
        finally { await restarted.close(); }
    } finally { await h.close(); }
});

test('one-shot conditions use known Z2M state and are rechecked before delayed actions', async () => {
    const h = await harness();
    try {
        const r = rule(); r.conditions = [{type: 'device_value', device: sourceID, property: 'occupancy', operator: 'eq', value: true}];
        r.actions.push({...r.actions[0], values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r]));
        assert.equal((await runOnce(h)).status, 'ok');
        assert.equal(h.extension.status().execution.runs[0].state, 'skipped');
        assert.equal(h.commands.length, 0, 'Unknown condition values never authorize commands');
        h.targetStates.set(sourceID, {occupancy: true});
        await runOnce(h); await flush(); assert.equal(h.commands.length, 1);
        await h.event({occupancy: false}); await h.clock.advance(5000);
        assert.equal(h.commands.length, 1);
        assert.equal(h.extension.status().execution.runs[0].error, 'conditions_not_met');
    } finally { await h.close(); }
});

test('targeted stop cancels only one pending sequence and rejects stale run IDs without disabling triggers', async () => {
    const h = await harness();
    try {
        const a = rule(); a.actions[0].delay_seconds = 5;
        const b = {...rule(), id: 'other_rule', actions: [{...rule().actions[0], delay_seconds: 5}]};
        await h.store(yaml([a, b])); await h.activate();
        await h.event({occupancy: true});
        const snapshot = h.extension.status().execution;
        const selected = snapshot.runs.find(item => item.rule_id === a.id);
        assert.equal((await runOnce(h)).error.code, 'rule_busy');
        const envelope = {session: h.session, expected_revision: snapshot.revision, checksum: snapshot.checksum, rule_id: a.id};
        assert.equal((await h.request('stop_rule', {...envelope, run: snapshot.runs.find(item => item.rule_id === b.id).run})).error.code, 'run_conflict');
        assert.equal((await h.request('stop_rule', {...envelope, run: selected.run})).status, 'ok');
        await h.clock.advance(5000);
        assert.equal(h.commands.length, 1, 'The other rule still executes');
        assert.equal(h.extension.status().executing, true);
        assert.equal(h.extension.status().execution.runs.find(item => item.rule_id === a.id).state, 'stopped');
        await h.event({occupancy: true}); await h.clock.advance(5000);
        assert.equal(h.commands.length, 3, 'Stopped rule remains armed for later ordinary triggers');
        assert.equal((await h.request('stop_rule', {...envelope, run: selected.run})).error.code, 'run_conflict');
    } finally { await h.close(); }
});

test('one-shot control verifies session, revision, checksum, one-use token and disk state without retrying failures', async () => {
    const h = await harness();
    try {
        await h.store(yaml());
        for (const [override, expected] of [[{session: '00000000-0000-0000-0000-000000000000'}, 'session_mismatch'],
            [{expected_revision: 2}, 'revision_conflict'], [{checksum: '0'.repeat(64)}, 'checksum_mismatch'],
            [{rule_id: 'missing'}, 'rule_missing']]) {
            assert.equal((await runOnce(h, 'synthetic_rule', override)).error.code, expected);
        }
        assert.equal((await runOnce(h, 'synthetic_rule', {}, {retain: true})).status, 'error');
        const token = (await h.request('status')).data.activation_token;
        await h.clock.advance(31000);
        assert.equal((await h.request('run_once', {session: h.session, expected_revision: 1,
            checksum: h.extension.status().checksum, rule_id: 'synthetic_rule', activation_token: token})).error.code, 'activation_expired');
        h.sendFault = true; await runOnce(h); await flush();
        assert.equal(h.extension.status().execution.runs[0].state, 'failed');
        assert.equal(h.extension.status().execution.runs[0].error, 'action_failed');
        assert.equal(h.commands.length, 1);
        await h.clock.advance(6000); assert.equal(h.commands.length, 1);
        assert.ok(!JSON.stringify(h.extension.status().execution).includes('synthetic-private-body'));
        await writeFile(join(h.directory, 'automations.yaml'), yaml([], 2));
        assert.equal((await runOnce(h)).status, 'error');
        assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('solar triggers run once at the offset time and skip missed times, polar events and failed conditions', async () => {
    const {solarForLocalDay} = await import('../libs/z2m/src/solar.mjs');
    const h = await harness();
    try {
        const t = {type: 'sun', event: 'sunrise', latitude: 0, longitude: 0, offset_minutes: -30, timezone: 'UTC'};
        const r = rule(); r.triggers = [t];
        const at = solarForLocalDay('2026-03-20', t);
        h.clock.wall = at - 1000;
        assert.equal((await h.store(yaml([r]))).status, 'ok'); await h.activate();
        await h.clock.advance(999); assert.equal(h.commands.length, 0);
        await h.clock.advance(1); assert.equal(h.commands.length, 1);
        h.clock.wall -= 1000; await h.clock.advance(1000); assert.equal(h.commands.length, 1, 'Clock rollback cannot replay an already observed solar day');
        await h.deactivate(); h.clock.wall = at + 60000; await h.activate(); await h.clock.advance(1000);
        assert.equal(h.commands.length, 1, 'Restart/activation cannot catch up a missed event');
        r.conditions = [{type: 'device_value', device: sourceID, property: 'temperature', operator: 'lt', value: 20}];
        await h.store(yaml([r], 2)); h.clock.wall = at - 1000; await h.activate(); await h.event({temperature: 25}); await h.clock.advance(1000);
        assert.equal(h.commands.length, 1, 'Solar triggers use the same AND conditions');
        r.triggers[0] = {...t, latitude: 90}; r.conditions = [];
        await h.store(yaml([r], 3)); h.clock.wall = Date.parse('2026-06-21T00:00:00Z'); await h.activate(); await h.clock.advance(86400000);
        assert.equal(h.commands.length, 1);
        assert.ok(h.logs.some(s => s.includes('keinen Zeitpunkt')));
        for (const patch of [{latitude: 91}, {longitude: -181}, {offset_minutes: 721}, {event: 'noon'}, {timezone: 'Unknown/Zone'}]) {
            const invalid = rule(); invalid.triggers = [{...t, ...patch}];
            assert.throws(() => validateYaml(yaml([invalid])), undefined, JSON.stringify(patch));
        }
    } finally { await h.close(); }
});

test('solar offsets cross local midnight and persisted permission resumes without Symcon', async () => {
    const {solarForLocalDay} = await import('../libs/z2m/src/solar.mjs');
    for (const offset of [-720, 720]) {
        let h = await harness();
        try {
            const t = {type: 'sun', event: 'sunrise', latitude: 0, longitude: 180, offset_minutes: offset, timezone: 'Pacific/Kiritimati'};
            const at = solarForLocalDay('2026-03-20', t), r = rule(); r.triggers = [t];
            await h.store(yaml([r])); await h.activate({resume_after_restart: true});
            const directory = h.directory; await h.close(false);
            h = await harness(directory, true, h => { h.clock.wall = at - 1000; });
            assert.equal(h.extension.status().resume_status, 'resumed');
            await h.clock.advance(1000);
            assert.equal(h.commands.length, 1, 'The base-event day is found even when the offset changes the local date');
            await h.clock.advance(1000); assert.equal(h.commands.length, 1);
        } finally { await h.close(); }
    }
});

test('live MQTT recording is leased independently and follows an ordered automation without raw values', async () => {
    const h = await harness();
    try {
        const client = '11111111-1111-4111-8111-111111111111';
        const second = '22222222-2222-4222-8222-222222222222';
        const recording = await h.request('debug', {session: h.session, client, seconds: 10});
        assert.equal(recording?.status, 'ok');
        await h.request('debug', {session: h.session, client: second, seconds: 20});
        const r = rule();
        r.conditions = [{type: 'device_value', device: sourceID, property: 'temperature', operator: 'lt', value: 20}];
        r.actions[0].delay_seconds = 2;
        await h.store(yaml([r])); await h.activate();
        await h.event({occupancy: true, temperature: 15, privateText: 'synthetic-private-text'});
        await h.clock.advance(2000);
        const records = h.messages.filter(m => m.topic === 'symcon/automations/debug');
        assert.ok(records.length > 0);
        assert.ok(records.every(m => !m.options.clientOptions.retain && m.options.skipLog));
        const {DEBUG_EVENTS} = await import('../libs/z2m/src/debug.mjs');
        const recordText = records.map(m => DEBUG_EVENTS[m.data.record.event]?.message).join('\n');
        for (const text of ['Auslöser geprüft.', 'Bedingung geprüft.', 'Wartezeit vor Aktionsschritt begonnen.', 'Wartezeit vor Aktionsschritt beendet.', 'Aktionsfolge beendet; Übergaben sind keine Gerätebestätigung.']) assert.ok(recordText.includes(text), text);
        const steps = records.map(m => m.data.record).filter(r => r.step === 1);
        assert.ok(steps.length >= 3 && steps.every(r => r.run === steps[0].run && r.rule === 1));
        assert.equal(h.commands.length, 1);
        assert.ok(!JSON.stringify(records).includes('synthetic-private-text') && !JSON.stringify(records).includes(sourceID));
        await h.request('debug', {session: h.session, client, seconds: 0});
        await h.clock.advance(1000); await h.event({occupancy: true, temperature: 15}); await h.clock.advance(2000);
        assert.deepEqual(h.messages.filter(m => m.topic === 'symcon/automations/debug').at(-1).data.clients, [second]);
        await h.clock.advance(20000);
        const count = h.messages.filter(m => m.topic === 'symcon/automations/debug').length;
        await h.event({occupancy: true, temperature: 15}); await h.clock.advance(2000);
        assert.equal(h.messages.filter(m => m.topic === 'symcon/automations/debug').length, count);
        assert.equal(await h.request('debug', {session: h.session, client, seconds: 1}, {retain: true}), undefined);
    } finally { await h.close(); }
});

test('recorded decisions identify failed conditions, minimum duration cancellation and the failing step', async () => {
    const h = await harness();
    try {
        const {DEBUG_EVENTS} = await import('../libs/z2m/src/debug.mjs');
        const records = () => h.messages.filter(m => m.topic === 'symcon/automations/debug').map(m => m.data.record);
        const message = r => DEBUG_EVENTS[r.event].message;
        await h.request('debug', {session: h.session, client: '11111111-1111-4111-8111-111111111111', seconds: 60});
        const r = rule();
        r.conditions = [{type: 'device_value', device: sourceID, property: 'temperature', operator: 'lt', value: 20}];
        await h.store(yaml([r])); await h.activate();
        await h.event({occupancy: true, temperature: 25});
        const condition = records().find(r => r.condition === 1 && r.passed === false);
        assert.ok(condition && records().some(r => r.run === condition.run && r.reason === 'conditions_not_met'));
        assert.equal(h.commands.length, 0);
        r.triggers[0].for_seconds = 2; r.conditions = [];
        await h.store(yaml([r], 2)); await h.activate();
        await h.event({occupancy: true}); await h.clock.advance(1000); await h.event({occupancy: false});
        const start = records().find(r => message(r) === 'Mindestdauer begonnen.');
        assert.ok(start && records().some(r => r.run === start.run && message(r) === 'Mindestdauer abgebrochen.'));
        await h.clock.advance(2000); assert.equal(h.commands.length, 0);
        h.sendFault = true; h.logFault = true;
        await h.event({occupancy: true}); await h.clock.advance(2000); await flush();
        assert.ok(records().some(r => r.step === 1 && r.rule === 1 && r.reason === 'action_failed'));
        assert.equal(h.commands.length, 1, 'Only one device handoff is attempted even with failing debug sinks');
        assert.ok(!JSON.stringify(records()).includes('synthetic-private-body'));
    } finally { await h.close(); }
});

test('recording validates control envelopes and never survives an extension restart', async () => {
    let h = await harness();
    try {
        const client = '11111111-1111-4111-8111-111111111111';
        for (const fields of [{seconds: 901}, {seconds: -1}, {seconds: '60'}, {session: client}, {client: 'unsafe'}, {unexpected: true}]) {
            assert.equal((await h.request('debug', {session: h.session, client, seconds: 60, ...fields})).status, 'error');
        }
        await h.request('debug', {session: h.session, client, seconds: 60});
        await h.store(yaml()); await h.activate({resume_after_restart: true});
        const directory = h.directory;
        await h.close(false);
        h = await harness(directory);
        await h.event({occupancy: true}); await flush();
        assert.equal(h.messages.filter(m => m.topic === 'symcon/automations/debug').length, 0);
        assert.notEqual(h.session, client);
    } finally { await h.close(); }
});

test('timed triggers and stop cancellation remain traceable while an unavailable debug publisher cannot block execution', async () => {
    const h = await harness();
    try {
        const {DEBUG_EVENTS} = await import('../libs/z2m/src/debug.mjs');
        await h.request('debug', {session: h.session, client: '11111111-1111-4111-8111-111111111111', seconds: 60});
        const r = rule();
        r.triggers = [{type: 'time', at: '12:00:01', timezone: 'UTC'}];
        r.actions[0].delay_seconds = 5;
        await h.store(yaml([r])); await h.activate(); await h.clock.advance(1000);
        await h.deactivate();
        for (let n = 0; n < 300; n++) await Promise.resolve();
        const records = h.messages.filter(m => m.topic === 'symcon/automations/debug').map(m => m.data.record);
        const timed = records.find(r => DEBUG_EVENTS[r.event].message === 'Zeitpunkt erreicht.');
        assert.ok(timed && records.some(r => r.run === timed.run && r.step === 1 && DEBUG_EVENTS[r.event].message === 'Aktionsschritt wegen Stopp abgebrochen.'));
        assert.equal(h.commands.length, 0);
        h.extension.diagnostics.mqtt = {publish: () => new Promise(() => {})};
        await h.store(yaml([rule()], 2)); await h.activate(); await h.event({occupancy: true});
        assert.equal(h.commands.length, 1, 'Device execution finishes while a debug Publish is still unresolved');
        await h.clock.advance(2001);
        await h.extension.stop(); await h.extension.start(); h.session = h.extension.status().session;
        assert.equal((await h.request('debug', {session: h.session, client: '11111111-1111-4111-8111-111111111111', seconds: 60})).status, 'ok', 'A reused extension object accepts a new explicit recording after restart');
    } finally { await h.close(); }
});

class Clock {
    time = 0;
    wall = Date.parse('2026-10-26T12:00:00Z');
    next = 0;
    timers = new Map();
    now = () => this.time;
    wallNow = () => this.wall;
    setTimeout = (callback, delay) => { const id = ++this.next; this.timers.set(id, {callback, at: this.time + delay}); return id; };
    clearTimeout = id => this.timers.delete(id);
    setInterval = (callback, delay) => { const id = ++this.next; this.timers.set(id, {callback, at: this.time + delay, interval: delay}); return id; };
    clearInterval = this.clearTimeout;
    async advance(ms) {
        const end = this.time + ms;
        while (true) {
            const entry = [...this.timers].filter(([, timer]) => timer.at <= end).sort((a, b) => a[1].at - b[1].at)[0];
            if (!entry) break;
            const [id, timer] = entry;
            this.wall += timer.at - this.time; this.time = timer.at;
            if (timer.interval) timer.at += timer.interval; else this.timers.delete(id);
            timer.callback(); await flush();
        }
        this.wall += end - this.time; this.time = end; await flush();
    }
}

async function harness(directory = null, nativeDispatch = true, beforeStart = () => {}) {
    directory ??= await mkdtemp(join(tmpdir(), 'z2ma-runtime-'));
    const clock = new Clock();
    const messages = [], commands = [], logs = [];
    const device = (ID, name) => ({ID, ieeeAddr: ID, name, isDevice: () => true});
    const devices = new Map([[sourceID, device(sourceID, 'synthetic/source')], [targetID, device(targetID, 'synthetic/target')]]);
    const group = {ID: 7, name: 'synthetic/group', isDevice: () => false};
    let publishState, receive;
    const bus = {onMQTTMessage: (_owner, callback) => { receive = callback; },
        emitMQTTMessage: nativeDispatch ? event => {
            commands.push({topic: event.topic.slice('synthetic/z2m/'.length), fullTopic: event.topic, data: JSON.parse(event.message)});
            if (h.sendFault) throw new Error('synthetic-private-body');
        } : undefined,
        onPublishEntityState: (_owner, callback) => { publishState = callback; },
        removeListeners: () => { receive = undefined; publishState = undefined; }};
    const zigbee = {resolveEntity: id => devices.get(id), groupsIterator: () => [group].values()};
    const mqtt = {publish: async (topic, payload, options) => {
        const message = {topic, data: JSON.parse(payload), options};
        messages.push(message);
    }};
    const logger = Object.fromEntries(['debug', 'info', 'warning', 'error'].map(level => [level, text => { logs.push(text); if (h.logFault) throw new Error('sink unavailable'); }]));
    const targetStates = new Map([[targetID, {state: 'OFF'}], [7, {state: 'ON'}]]);
    const extension = new Extension(zigbee, mqtt, {get: entity => targetStates.get(entity.ID)}, null, bus, null, null, null,
        {get: () => ({mqtt: {base_topic: 'synthetic/z2m'}})}, logger, {directory, clock});
    let sequence = 0;
    const h = {extension, directory, clock, devices, group, targetStates, commands, messages, logs, sendFault: false, logFault: false,
        request: async (operation, data = {}, extra = {}) => {
            const transaction = 'runtime-' + ++sequence;
            await receive({topic: 'synthetic/z2m/symcon/automations/request/' + operation,
                message: JSON.stringify({protocol_version: 1, transaction, ...data}), ...extra});
            return messages.findLast(message => message.data.transaction === transaction)?.data;
        },
        event: async (payload, extra = {}) => { publishState?.({entity: devices.get(sourceID), payload, message: payload, ...extra}); await flush(); },
        close: async (remove = true) => { await extension.stop(); if (remove) await rm(directory, {recursive: true, force: true}); }};
    await beforeStart(h);
    await extension.start();
    h.session = extension.status().session;
    h.store = text => h.request('store', {yaml: text, expected_revision: extension.status().stored_revision, session: h.session});
    h.activate = async (extra = {}, event = {}) => {
        const status = await h.request('status');
        return h.request('activate', {session: h.session, activation_token: status.data.activation_token,
            expected_revision: extension.status().stored_revision, checksum: extension.status().checksum, ...extra}, event);
    };
    h.deactivate = () => h.request('deactivate', {session: h.session});
    return h;
}

test('scene catalog reads persisted endpoint/group identities without commands or foreign group scenes', () => {
    const endpoints = [{ID: 1, meta: {scenes: {'0_0': {name: 'Synthetic zero'}, '5_7': {name: 'Synthetic group'}, '255_0': {name: 'Synthetic last'},
        '256_0': {}, '3_9': {}, '4_0': {name: 'private\ntext'}}}}, {ID: 2, meta: {scenes: {'0_0': {name: 'Synthetic second channel'}}}}];
    const device = {zh: {endpoints}, endpoint: () => endpoints[0]};
    assert.deepEqual(entityScenes(device, 'device').map(s => [s.id, s.endpoint]), [[0, 1], [4, 1], [255, 1], [0, 2]]);
    assert.equal(entityScenes(device, 'device')[1].name, '');
    const group = {ID: 7, zh: {members: [endpoints[0], endpoints[0]]}};
    assert.deepEqual(entityScenes(group, 'group'), [{id: 5, name: 'Synthetic group'}]);
    const catalog = buildEditorScenes({resolveEntity: () => device, groupsIterator: () => [group]},
        [{id: targetID, properties: [{property: 'state', access: 2, endpoint: ''}]}], [{id: 7}]);
    assert.equal(catalog.length, 4, 'Only scenes on the controlled endpoint plus the group are offered');
    assert.ok(catalog.every(entry => entry.control_property === 'state'));
});

test('scene actions validate references, recall the correct endpoint/group and preserve delays', async () => {
    const h = await harness();
    try {
        const target = h.devices.get(targetID);
        target.zh = {endpoints: [{ID: 2, meta: {scenes: {'0_0': {name: 'Synthetic device scene'}}}}]};
        h.group.zh = {members: [{ID: 1, meta: {scenes: {'5_7': {name: 'Synthetic group scene'}}}}]};
        const r = rule();
        r.actions = [{type: 'scene', target: {type: 'device', id: targetID}, scene_id: 0, endpoint: 2},
            {type: 'scene', target: {type: 'group', id: 7}, scene_id: 5, delay_seconds: 2}];
        assert.equal((await h.store(yaml([r]))).status, 'ok');
        assert.equal(h.commands.length, 0);
        assert.equal((await h.activate()).status, 'ok');
        await h.event({occupancy: true});
        assert.equal(h.commands.length, 1);
        assert.equal(h.commands[0].topic, 'synthetic/target/2/set');
        assert.deepEqual(h.commands[0].data, {scene_recall: 0});
        await h.clock.advance(2000);
        assert.deepEqual(h.commands[1].data, {scene_recall: 5});
        assert.equal(h.commands[1].topic, 'synthetic/group/set');
        assert.ok(h.logs.some(line => line.includes('Szenenaufruf an native')));
        assert.ok(!h.logs.join().includes('Synthetic device scene'));
    } finally { await h.close(); }
});

test('deleted scenes are rejected during validation, storage, activation and delayed execution', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.actions = [{type: 'scene', target: {type: 'group', id: 7}, scene_id: 5, delay_seconds: 2}];
        const rejected = await h.request('validate', {yaml: yaml([r])});
        assert.equal(rejected.error.code, 'invalid_semantics');
        assert.deepEqual(rejected.error.details.issues[0], {path: '/automations/0/actions/0/scene_id', reason: 'scene_unavailable'});
        assert.equal((await h.store(yaml([r]))).status, 'error');
        h.group.zh = {members: [{ID: 1, meta: {scenes: {'5_7': {name: 'Synthetic group scene'}}}}]};
        assert.equal((await h.store(yaml([r]))).status, 'ok');
        const metadata = h.group.zh;
        delete h.group.zh;
        assert.equal((await h.activate()).error.code, 'scene_unavailable');
        h.group.zh = metadata;
        assert.equal((await h.activate()).status, 'ok');
        await h.event({occupancy: true});
        delete h.group.zh;
        await h.clock.advance(2000);
        assert.equal(h.commands.length, 0);
        assert.equal(h.extension.status().runtime_error, 'scene_unavailable');
        for (const action of [{...r.actions[0], endpoint: 1}, {...r.actions[0], scene_id: -1}, {...r.actions[0], scene_id: 256},
            {...r.actions[0], scene_id: '5'}, {...r.actions[0], target: {type: 'device', id: targetID}}]) {
            assert.throws(() => validateYaml(yaml([{...r, actions: [action]}])), error => error.code === 'invalid_schema');
        }
    } finally { await h.close(); }
});

test('scene restart requires the persisted scene; stop cancels a pending scene recall', async () => {
    let h = await harness();
    try {
        h.group.zh = {members: [{ID: 1, meta: {scenes: {'5_7': {name: 'Synthetic restart scene'}}}}]};
        const r = rule();
        r.actions = [{type: 'scene', target: {type: 'group', id: 7}, scene_id: 5, delay_seconds: 2}];
        await h.store(yaml([r])); await h.activate();
        await h.event({occupancy: true});
        await h.deactivate();
        await h.clock.advance(3000);
        assert.equal(h.commands.length, 0);
        await h.activate();
        const directory = h.directory;
        await h.close(false);
        h = await harness(directory);
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.extension.status().resume_error, 'scene_unavailable');
        assert.equal(h.commands.length, 0);
    } finally { await h.close(); }
});

test('storage never executes; explicit activation executes a fresh device trigger through native set topics', async () => {
    const h = await harness();
    try {
        await h.store(yaml()); await h.event({occupancy: true});
        assert.equal(h.commands.length, 0);
        assert.equal((await h.activate()).status, 'ok');
        await h.event({occupancy: true});
        assert.equal(h.commands.length, 1);
        assert.equal(h.commands[0].topic, 'synthetic/target/set');
        assert.deepEqual(h.commands[0].data, {state: 'ON'});
        assert.equal(h.commands[0].fullTopic, 'synthetic/z2m/synthetic/target/set');
        assert.equal(h.messages.filter(message => message.topic.endsWith('/set')).length, 0, 'Device commands must not depend on broker loopback');
        assert.equal(h.extension.status().executing, true);
    } finally { await h.close(); }
});

test('activation binds session, revision and checksum; disabled rules and missing devices never execute', async () => {
    const h = await harness();
    try {
        await h.store(yaml());
        for (const [extra, event, error] of [
            [{session: 'previous-session'}, {}, 'session_mismatch'],
            [{expected_revision: 0}, {}, 'revision_conflict'],
            [{checksum: 'f'.repeat(64)}, {}, 'checksum_mismatch'],
            [{}, {retain: true}, 'session_mismatch']
        ]) assert.equal((await h.activate(extra, event)).error.code, error);
        assert.equal(h.extension.status().executing, false);
        h.devices.delete(targetID);
        assert.equal((await h.activate()).error.code, 'unknown_entity');
        const disabled = rule(); disabled.enabled = false;
        await h.store(yaml([disabled], 2));
        assert.equal((await h.activate()).status, 'ok');
        await h.event({occupancy: true});
        assert.equal(h.commands.length, 0);
        assert.equal(h.extension.status().enabled_count, 0);
    } finally { await h.close(); }
});

test('activation rejects hosts without native command dispatch instead of falling back to broker loopback', async () => {
    const h = await harness(null, false);
    try {
        await h.store(yaml());
        assert.equal((await h.activate()).error.code, 'runtime_unavailable');
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.commands.length, 0);
    } finally { await h.close(); }
});

test('only fresh matching typed properties trigger; cached and unrelated fields are ignored', async () => {
    const h = await harness();
    try {
        await h.store(yaml()); await h.activate();
        await h.event({temperature: 20}, {message: {occupancy: true}});
        await h.event({occupancy: true}, {stateChangeReason: 'publishCached'});
        await h.event({occupancy: 'true'});
        await h.event({occupancy: true}, {entity: h.group});
        assert.equal(h.commands.length, 0);
        await h.event({occupancy: true});
        await h.event({occupancy: true});
        assert.equal(h.commands.length, 1, 'feedback/cooldown prevents an immediate second run');
        await h.clock.advance(1000); await h.event({occupancy: true});
        assert.equal(h.commands.length, 2, 'a later fresh report is a new event');
    } finally { await h.close(); }
});

test('numeric comparisons and all conditions use observed values without coercion', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.triggers[0] = {...r.triggers[0], property: 'temperature', operator: 'gte', value: 20};
        r.conditions = [{type: 'device_value', device: sourceID, property: 'enabled', operator: 'ne', value: false}];
        await h.store(yaml([r])); await h.activate();
        await h.event({temperature: 21});
        await h.event({temperature: '21', enabled: true});
        assert.equal(h.commands.length, 0, 'missing ne-value and a numeric string both fail closed');
        await h.event({temperature: 20});
        assert.equal(h.commands.length, 1);
        await h.clock.advance(1000); await h.event({temperature: 19});
        assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('for_seconds measures continuous match, survives repeated matching reports and cancels on false', async () => {
    const h = await harness();
    try {
        const r = rule(); r.triggers[0].for_seconds = 5;
        await h.store(yaml([r])); await h.activate();
        await h.event({occupancy: true}); await h.clock.advance(3000);
        await h.event({occupancy: true}); await h.clock.advance(2000);
        assert.equal(h.commands.length, 1, 'repeated true must not restart the hold timer');
        await h.event({occupancy: true}); await h.clock.advance(5000);
        assert.equal(h.commands.length, 1, 'a continuous match runs only once');
        await h.event({occupancy: false}); await h.event({occupancy: true});
        await h.clock.advance(4000); await h.event({occupancy: false}); await h.clock.advance(2000);
        assert.equal(h.commands.length, 1, 'broken duration does not fire');
        await h.event({occupancy: true}); await h.clock.advance(5000);
        assert.equal(h.commands.length, 2);
    } finally { await h.close(); }
});

test('ordered delayed actions resolve current names and support groups; concurrent runs of one rule are dropped', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.actions = [{...r.actions[0], delay_seconds: 2}, {type: 'set', target: {type: 'group', id: 7}, values: {state: 'OFF'}, delay_seconds: 3}];
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true});
        await h.clock.advance(1000); await h.event({occupancy: true});
        h.devices.get(targetID).name = 'synthetic/renamed';
        await h.clock.advance(1000);
        assert.equal(h.commands.length, 1); assert.equal(h.commands[0].topic, 'synthetic/renamed/set');
        await h.clock.advance(2999); assert.equal(h.commands.length, 1);
        await h.clock.advance(1); assert.equal(h.commands.length, 2);
        assert.equal(h.commands[1].topic, 'synthetic/group/set');
    } finally { await h.close(); }
});

test('deactivate, changed storage and stop cancel every pending hold and delayed action', async () => {
    const h = await harness();
    try {
        const r = rule(); r.actions[0].delay_seconds = 5;
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true});
        await h.deactivate(); await h.clock.advance(6000); assert.equal(h.commands.length, 0);
        await h.activate(); await h.event({occupancy: true});
        await h.store(yaml([r], 2)); await h.clock.advance(6000); assert.equal(h.commands.length, 0);
        assert.equal(h.extension.status().executing, false);
        await h.activate(); await h.event({occupancy: true});
        await h.extension.stop(); await h.clock.advance(6000); assert.equal(h.commands.length, 0);
        assert.equal(h.clock.timers.size, 0);
    } finally { await h.close(); }
});

test('unchanged stores and duplicate activation keep a pending hold; changed conditions cancel a delayed sequence', async () => {
    const h = await harness();
    try {
        const r = rule(); r.triggers[0].for_seconds = 2; r.actions[0].delay_seconds = 2;
        r.conditions = [{type: 'device_value', device: sourceID, property: 'allowed', operator: 'eq', value: true}];
        const text = yaml([r]);
        await h.store(text); await h.activate(); await h.event({occupancy: true, allowed: true});
        await h.clock.advance(1000); await h.activate();
        const response = await h.store(text);
        assert.equal(response.data.unchanged, true); assert.equal(response.data.executing, true);
        await h.clock.advance(1000); await h.event({allowed: false}); await h.clock.advance(2000);
        assert.equal(h.commands.length, 0);
        await h.event({occupancy: false}); await h.event({occupancy: true, allowed: true});
        await h.clock.advance(4000); assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('time triggers use explicit zones, fire once per local day and do not replay DST repeated hours', async () => {
    const h = await harness();
    try {
        const r = rule(); r.triggers = [{type: 'time', at: '02:30:00', timezone: 'Europe/Berlin'}];
        await h.store(yaml([r])); await h.activate();
        h.clock.wall = Date.parse('2026-10-25T00:29:59Z'); await h.clock.advance(1000);
        assert.equal(h.commands.length, 1);
        h.clock.wall = Date.parse('2026-10-25T01:29:59Z'); await h.clock.advance(1000);
        assert.equal(h.commands.length, 1, 'repeated local hour must not run twice');
        h.clock.wall = Date.parse('2026-10-26T01:29:59Z'); await h.clock.advance(1000);
        assert.equal(h.commands.length, 2);
        await h.deactivate(); assert.equal(h.clock.timers.size, 0);
    } finally { await h.close(); }
});

test('overnight windows use weekday of the window start and exclude the end boundary', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.conditions = [{type: 'time_window', after: '22:00:00', before: '06:00:00', timezone: 'UTC', weekdays: [1]}];
        await h.store(yaml([r])); await h.activate();
        h.clock.wall = Date.parse('2026-10-27T01:00:00Z'); await h.event({occupancy: true});
        assert.equal(h.commands.length, 1, 'Tuesday morning belongs to Monday night');
        await h.clock.advance(1000); h.clock.wall = Date.parse('2026-10-27T06:00:00Z'); await h.event({occupancy: true});
        assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('action transport failures abort sequences without retry and diagnostics never expose payloads or exception text', async () => {
    const h = await harness();
    try {
        const r = rule(); r.actions[0].values = {state: 'synthetic-private-body'};
        r.actions.push({type: 'set', target: {type: 'group', id: 7}, values: {state: 'OFF'}});
        await h.store(yaml([r])); await h.activate(); h.sendFault = true;
        await h.event({occupancy: true});
        assert.equal(h.commands.length, 1);
        assert.equal(h.extension.status().runtime_error, 'action_failed');
        assert.equal(h.logs.some(line => line.includes('synthetic-private-body')), false);
        h.sendFault = false; h.logFault = true;
        await h.clock.advance(1000); await h.event({occupancy: true});
        assert.equal(h.commands.length, 3, 'broken debug does not suppress actions');
    } finally { await h.close(); }
});

test('activation refuses corrupted disk; disabled automatic restart keeps stored rules stopped', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        await h.store(yaml()); await h.activate({resume_after_restart: false});
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().stored_revision, 1);
        assert.equal(h.extension.status().executing, false);
        await h.event({occupancy: true}); assert.equal(h.commands.length, 0);
        await writeFile(join(directory, 'automations.yaml'), 'broken: [');
        assert.equal((await h.activate()).error.code, 'storage_unavailable');
        assert.equal(h.extension.status().executing, false);
    } finally { await h.close(); }
});

test('an activation token is fresh, one-use and invalidated by deactivation; retained status never grants it', async () => {
    const h = await harness();
    try {
        await h.store(yaml());
        const activation = async () => ({session: h.session, expected_revision: 1, checksum: h.extension.status().checksum,
            activation_token: (await h.request('status')).data.activation_token});
        const request = await activation();
        assert.equal((await h.request('activate', request)).status, 'ok');
        await h.deactivate();
        assert.equal((await h.request('activate', request)).error.code, 'activation_expired');
        const expired = await activation(); await h.clock.advance(30000);
        assert.equal((await h.request('activate', expired)).error.code, 'activation_expired');
        assert.equal(h.extension.status().executing, false);
        assert.ok(h.messages.filter(message => message.topic === 'symcon/automations/status').every(message => !Object.hasOwn(message.data, 'activation_token')));
    } finally { await h.close(); }
});

test('unknown targets disappearing during delays abort safely and recovered backup cannot be activated', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        const r = rule(); r.actions[0].delay_seconds = 1;
        await h.store(yaml([r])); await h.activate(); await h.event({occupancy: true});
        h.devices.delete(targetID); await h.clock.advance(1000);
        assert.equal(h.commands.length, 0); assert.equal(h.extension.status().runtime_error, 'unknown_entity');
        await h.store(yaml([], 2)); await h.close(false);
        await writeFile(join(directory, 'automations.yaml'), 'broken: [');
        h = await harness(directory);
        assert.equal(h.extension.status().storage, 'recovered_read_only');
        assert.equal((await h.activate()).error.code, 'storage_unavailable');
    } finally { await h.close(); }
});

test('global publication rate and concurrent rules are bounded; hung publication times out without a retry', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.actions = Array.from({length: 16}, () => ({type: 'set', target: {type: 'device', id: targetID}, values: {state: 'ON'}}));
        const second = structuredClone(r); second.id = 'second_rule';
        await h.store(yaml([r, second])); await h.activate();
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 20, 'commands beyond the global rate are dropped');
        assert.equal(h.extension.status().runtime_error, 'rate_limited');
        await h.store(yaml([rule()], 2)); await h.activate();
        await h.clock.advance(1000);
        h.extension.runtime.dispatch = () => new Promise(() => {});
        await h.event({occupancy: true}); await h.clock.advance(5000);
        assert.equal(h.extension.status().runtime_error, 'action_timeout');
        assert.equal(h.extension.runtime.tasks.size, 0);
        assert.equal(h.clock.timers.size, 0);
    } finally { await h.close(); }
});

test('all six comparisons are strict and OR triggers run with AND conditions', async () => {
    for (const [operator, actual, expected] of [['eq', 2, 2], ['ne', 3, 2], ['gt', 3, 2], ['gte', 2, 2], ['lt', 1, 2], ['lte', 2, 2]]) {
        const h = await harness();
        try {
            const r = rule();
            r.triggers.push({type: 'device', device: sourceID, property: 'temperature', operator, value: expected});
            r.conditions = [{type: 'device_value', device: sourceID, property: 'allowed', operator: 'eq', value: true},
                {type: 'device_value', device: sourceID, property: 'blocked', operator: 'eq', value: false}];
            await h.store(yaml([r])); await h.activate();
            await h.event({temperature: actual, allowed: true, blocked: true}); assert.equal(h.commands.length, 0);
            await h.event({temperature: actual, allowed: true, blocked: false}); assert.equal(h.commands.length, 1, operator);
        } finally { await h.close(); }
    }
});

test('time windows include start, exclude end and missed wall-clock triggers are not caught up', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.conditions = [{type: 'time_window', after: '12:00:00', before: '13:00:00', timezone: 'UTC'}];
        await h.store(yaml([r])); await h.activate();
        await h.event({occupancy: true}); assert.equal(h.commands.length, 1);
        await h.clock.advance(1000); h.clock.wall = Date.parse('2026-10-26T13:00:00Z');
        await h.event({occupancy: true}); assert.equal(h.commands.length, 1);
        r.conditions = []; r.triggers = [{type: 'time', at: '14:00:00', timezone: 'UTC'}];
        await h.store(yaml([r], 2)); await h.activate();
        h.clock.wall = Date.parse('2026-10-26T14:00:01Z'); await h.clock.advance(1000);
        assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('parallel rule count is bounded and deactivation cancels held triggers as well as action waits', async () => {
    const h = await harness();
    try {
        const rules = Array.from({length: 25}, (_, index) => {
            const r = rule(); r.id = 'rule_' + index; r.actions[0].delay_seconds = 10; return r;
        });
        await h.store(yaml(rules)); await h.activate(); await h.event({occupancy: true});
        assert.equal(h.extension.runtime.tasks.size, 16);
        await h.deactivate(); await flush();
        assert.equal(h.extension.runtime.tasks.size, 0);
        const held = rule(); held.triggers[0].for_seconds = 10;
        await h.store(yaml([held], 2)); await h.activate(); await h.event({occupancy: true});
        await h.deactivate(); await h.clock.advance(10000);
        assert.equal(h.commands.length, 0); assert.equal(h.clock.timers.size, 0);
    } finally { await h.close(); }
});

test('stop during activation disk verification cannot arm a stopped extension', async () => {
    const h = await harness();
    try {
        await h.store(yaml());
        const status = await h.request('status');
        let unblock;
        h.extension.store.verifyDisk = () => new Promise(resolve => { unblock = resolve; });
        const activation = h.request('activate', {session: h.session, activation_token: status.data.activation_token,
            expected_revision: 1, checksum: h.extension.status().checksum});
        await flush();
        const stopped = h.extension.stop(); unblock();
        assert.equal((await activation).error.code, 'not_ready');
        await stopped;
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.clock.timers.size, 0);
    } finally { await h.close(); }
});

test('diagnostic start/end pairs remain balanced for rejected activation and failed action sequences', async () => {
    const h = await harness();
    try {
        await h.store(yaml());
        await h.activate({checksum: 'a'.repeat(64)});
        await h.activate(); h.sendFault = true; await h.event({occupancy: true});
        await h.extension.stop();
        const starts = h.logs.filter(line => line.includes(' | START | '));
        const ends = h.logs.filter(line => line.includes(' | ENDE | '));
        assert.ok(starts.length > 5);
        assert.equal(starts.length, ends.length);
        assert.ok(h.logs.some(line => line.includes('checksum_mismatch')));
        assert.ok(h.logs.some(line => line.includes('action_failed')));
        assert.equal(h.logs.some(line => line.includes(h.session)), false);
        assert.equal(h.logs.some(line => line.includes('synthetic-private-body')), false);
    } finally { await h.close(); }
});


test('toggle uses current Z2M aggregate state and sends a uniform command for groups', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.actions = [{type: 'toggle', target: {type: 'group', id: 7}, property: 'state'}];
        assert.equal((await h.store(yaml([r]))).status, 'ok');
        assert.equal((await h.activate()).status, 'ok');
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.deepEqual(h.commands.map(command => command.data), [{state: 'OFF'}]);
        assert.equal(h.commands[0].topic, 'synthetic/group/set');
        h.targetStates.set(7, {state: 'OFF'});
        await h.clock.advance(1001);
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.deepEqual(h.commands.at(-1).data, {state: 'ON'});
        h.targetStates.delete(7);
        await h.clock.advance(1001);
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 2);
        assert.equal(h.extension.status().runtime_error, 'state_unavailable');
        assert.ok(h.logs.some(text => text.includes('Umschaltziel')));
        assert.equal(JSON.stringify(h.commands).includes('TOGGLE'), false);
    } finally { await h.close(); }
});

test('toggle reads the exact device channel and fails activation without the state interface', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.actions = [{type: 'toggle', target: {type: 'device', id: targetID}, property: 'state_l2'}];
        h.targetStates.set(targetID, {state: 'ON', state_l2: 'OFF'});
        assert.equal((await h.store(yaml([r]))).status, 'ok');
        assert.equal((await h.activate()).status, 'ok');
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.deepEqual(h.commands[0].data, {state_l2: 'ON'});
        await h.deactivate();
        h.extension.runtime.readState = undefined;
        assert.equal((await h.activate()).error.code, 'runtime_unavailable');
    } finally { await h.close(); }
});


test('changed-value triggers suppress repeated reports and retain cached baselines across unrelated updates', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.triggers[0].on_change = true;
        h.targetStates.set(sourceID, {occupancy: true});
        assert.equal((await h.store(yaml([r]))).status, 'ok');
        assert.equal((await h.activate()).status, 'ok');
        await h.event({temperature: 21});
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 0);
        await h.event({occupancy: false});
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 1);
        await h.clock.advance(1001);
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 1);
        await h.event({occupancy: false}, {stateChangeReason: 'publishCached'});
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 1);
        await h.event({occupancy: false});
        await h.event({occupancy: true}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 2);
        assert.ok(h.logs.some(text => text.includes('Wert unverändert')));
    } finally { await h.close(); }
});

test('uncached event properties start from the first fresh report and restart their observation on activation', async () => {
    const h = await harness();
    try {
        const r = rule();
        r.triggers = [{type: 'device', device: sourceID, property: 'action', operator: 'eq', value: 'single', on_change: true}];
        assert.equal((await h.store(yaml([r]))).status, 'ok');
        assert.equal((await h.activate()).status, 'ok');
        await h.event({action: 'single'}); await Promise.all([...h.extension.runtime.tasks]);
        await h.clock.advance(1001);
        await h.event({action: 'single'}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 1);
        await h.event({action: 'double'});
        await h.event({action: 'single'}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 2);
        await h.deactivate();
        assert.equal((await h.activate()).status, 'ok');
        await h.event({action: 'single'}); await Promise.all([...h.extension.runtime.tasks]);
        assert.equal(h.commands.length, 3);
    } finally { await h.close(); }
});


test('restart restores the explicitly activated snapshot without Symcon; stop persists across restart', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        await h.store(yaml());
        assert.equal(h.extension.status().executing, false);
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().executing, false, 'Saving never authorizes restart');
        assert.equal((await h.activate()).status, 'ok');
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().executing, true);
        assert.equal(h.extension.status().resume_status, 'resumed');
        assert.equal(h.commands.length, 0, 'Startup never emits a device command');
        await h.event({occupancy: true});
        assert.equal(h.commands.length, 1);
        assert.equal((await h.deactivate()).status, 'ok');
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.extension.status().resume_status, 'stopped');
        await h.event({occupancy: true}); assert.equal(h.commands.length, 0);
        assert.ok(h.logs.some(line => line.includes('bewusst gestoppt')));
    } finally { await h.close(); }
});

test('restart cancels old action sequences and waits for fresh condition observations', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        const r = rule();
        r.conditions = [{type: 'device_value', device: sourceID, property: 'illuminance', operator: 'lt', value: 50}];
        r.actions.push({type: 'set', target: {type: 'device', id: targetID}, values: {state: 'OFF'}, delay_seconds: 5});
        await h.store(yaml([r])); await h.activate();
        await h.event({occupancy: true, illuminance: 10});
        assert.equal(h.commands.length, 1);
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().executing, true);
        await h.clock.advance(6000); assert.equal(h.commands.length, 0, 'Old delayed actions are discarded');
        await h.event({occupancy: true}); assert.equal(h.commands.length, 0, 'Unknown condition is not fulfilled');
        await h.event({occupancy: true, illuminance: 10}); assert.equal(h.commands.length, 1);
    } finally { await h.close(); }
});

test('restart rejects changed YAML, corrupt intent, another system and recovered backup', async () => {
    for (const mutation of [
        async dir => writeFile(join(dir, 'automations.yaml'), yaml([], 2)),
        async dir => writeFile(join(dir, 'execution-state.json'), '{broken'),
        async dir => { const {readFile} = await import('node:fs/promises'); const intent = JSON.parse(await readFile(join(dir, 'execution-state.json'), 'utf8')); intent.system = 'synthetic/other'; await writeFile(join(dir, 'execution-state.json'), JSON.stringify(intent)); },
        async dir => { await writeFile(join(dir, 'automations.previous.yaml'), yaml()); await writeFile(join(dir, 'automations.yaml'), 'broken: ['); }
    ]) {
        let h = await harness(); const directory = h.directory;
        try {
            await h.store(yaml()); await h.activate(); await h.close(false);
            await mutation(directory); h = await harness(directory);
            assert.equal(h.extension.status().executing, false);
            assert.equal(h.extension.status().resume_status, 'blocked');
            assert.ok(h.extension.status().resume_error);
            await h.event({occupancy: true}); assert.equal(h.commands.length, 0);
            assert.ok(h.logs.some(line => line.includes('Wiederanlauf abgebrochen')));
        } finally { await h.close(); }
    }
});

test('missing activation state never authorizes startup; unavailable devices block restart', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        await h.store(yaml()); await h.activate(); await h.close(false);
        h = await harness(directory, true, context => context.devices.delete(targetID));
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.extension.status().resume_error, 'unknown_entity');
        await h.close(false);
        await rm(join(directory, 'execution-state.json'));
        h = await harness(directory);
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.extension.status().resume_status, 'unconfigured');
    } finally { await h.close(); }
});

test('changed storage disarms restart while idempotent storage retains explicit activation', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        await h.store(yaml()); await h.activate();
        assert.equal((await h.store(yaml())).data.unchanged, true);
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().executing, true);
        await h.store(yaml([rule()], 2));
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().executing, false);
    } finally { await h.close(); }
});

test('activation and stop report persistence failures instead of confirming a durable change', async () => {
    const h = await harness();
    try {
        await h.store(yaml());
        const write = h.extension.store.atomicWrite.bind(h.extension.store);
        let calls = 0;
        h.extension.store.atomicWrite = async (...args) => { if (++calls === 2) throw new Error('private storage detail'); return write(...args); };
        assert.equal((await h.activate()).error.code, 'storage_error');
        assert.equal(h.extension.status().executing, false);
        h.extension.store.atomicWrite = write;
        await h.activate();
        h.extension.store.atomicWrite = async () => { throw new Error('private storage detail'); };
        assert.equal((await h.deactivate()).error.code, 'storage_error');
        assert.equal(h.extension.status().executing, false);
        assert.equal(h.extension.status().resume_status, 'blocked');
        assert.ok(!h.logs.join('').includes('private storage detail'));
    } finally { await h.close(); }
});


test('an explicit stop received during startup wins over automatic recovery', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        await h.store(yaml()); await h.activate(); await h.close(false);
        let release, entered, startingContext;
        const gate = new Promise(resolve => { release = resolve; });
        const reached = new Promise(resolve => { entered = resolve; });
        const io = await import('node:fs/promises');
        const starting = harness(directory, true, context => {
            startingContext = context;
            context.extension.store.io = {...io, readFile: async path => { entered(); await gate; return io.readFile(path); }};
        });
        await reached;
        const stopping = startingContext.extension.receive({topic: 'synthetic/z2m/symcon/automations/request/deactivate',
            message: JSON.stringify({protocol_version: 1, transaction: 'synthetic-stop-startup', session: startingContext.extension.session})});
        release(); h = await starting; await stopping;
        assert.equal(h.extension.status().executing, false);
        await h.close(false); h = await harness(directory);
        assert.equal(h.extension.status().resume_status, 'stopped');
        assert.equal(h.extension.status().executing, false);
    } finally { await h.close(); }
});

test('automatic recovery does not replay a switching time missed during downtime', async () => {
    let h = await harness(); const directory = h.directory;
    try {
        const r = rule(); r.triggers = [{type: 'time', at: '12:05:00', timezone: 'UTC'}];
        await h.store(yaml([r])); await h.activate(); await h.close(false);
        h = await harness(directory, true, context => { context.clock.wall = Date.parse('2026-10-26T12:10:00Z'); });
        assert.equal(h.extension.status().executing, true);
        await h.clock.advance(10000);
        assert.equal(h.commands.length, 0);
    } finally { await h.close(); }
});
