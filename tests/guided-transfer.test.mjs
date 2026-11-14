import test from 'node:test';
import assert from 'node:assert/strict';
import {runGuidedTransfer} from '../Z2MAutomations/visualization/transfer.js';

function fixture({executing = true, stopConfirmed = true, validated = true, loseStore = false, changeOnReload = false} = {}) {
    const data = {workspaceContext: {systemId: 'synthetic', generation: 1}, configuration: {revision: 3, automations: [{id: 'a', enabled: true}]}};
    const calls = [];
    const options = {getData: () => data, token: 'SYNTHETIC', reload: async () => { if (changeOnReload) data.configuration.revision++; },
        runtime: async (_data, action) => {
            calls.push(action);
            return {confirmed: action === 'deactivate' ? stopConfirmed : true, executing: action === 'status' && executing, pending: false};
        },
        transfer: async (_data, action) => {
            calls.push(action);
            if (action === 'store' && loseStore) throw new Error('lost acknowledgement');
            return {pending: false, canStore: validated, status: action === 'validate' ? (validated ? 'validated' : 'error') : 'stored', receiverConfirmed: true};
        }};
    return {data, calls, options};
}

test('guided transfer reads status, stops once, revalidates and stores without activating', async () => {
    const {options, calls} = fixture();
    assert.equal((await runGuidedTransfer(options)).status, 'stored');
    assert.deepEqual(calls, ['status', 'deactivate', 'validate', 'store']);
});

test('an already stopped receiver is checked and stored without another stop', async () => {
    const {options, calls} = fixture({executing: false});
    await runGuidedTransfer(options);
    assert.deepEqual(calls, ['status', 'validate', 'store']);
});

test('a confirmed stop retains the restart preference and still stores the complete draft', async () => {
    const {data, options, calls} = fixture();
    data.configuration.automations.push({id: 'new_rule', name: 'Synthetic new automation', enabled: false});
    const expected = structuredClone(data.configuration);
    const runtime = options.runtime;
    options.runtime = async (current, action) => ({...await runtime(current, action),
        resumeAfterRestart: true, resumeStatus: action === 'deactivate' ? 'stopped' : 'armed'});
    const transfer = options.transfer;
    const stored = [];
    options.transfer = async (current, action) => {
        if (action === 'store') stored.push(structuredClone(current.configuration));
        return transfer(current, action);
    };
    assert.equal((await runGuidedTransfer(options)).status, 'stored');
    assert.deepEqual(calls, ['status', 'deactivate', 'validate', 'store']);
    assert.deepEqual(stored, [expected], 'the new disabled rule and existing enabled rule are stored together');
});

test('a stopped receiver with a retained restart preference needs no repeated stop', async () => {
    const {options, calls} = fixture({executing: false});
    const runtime = options.runtime;
    options.runtime = async (current, action) => ({...await runtime(current, action), resumeAfterRestart: true, resumeStatus: 'stopped'});
    await runGuidedTransfer(options);
    assert.deepEqual(calls, ['status', 'validate', 'store']);
});

test('an armed or unconfirmed restart state after stopping never permits storage', async () => {
    for (const resumeStatus of ['armed', 'resumed', 'blocked', undefined]) {
        const {options, calls} = fixture();
        const runtime = options.runtime;
        options.runtime = async (current, action) => ({...await runtime(current, action), resumeAfterRestart: true, resumeStatus});
        await assert.rejects(runGuidedTransfer(options));
        assert.deepEqual(calls, ['status', 'deactivate']);
    }
});

test('unconfirmed stop and changed local input cannot lead to validation or storage', async () => {
    for (const change of [{stopConfirmed: false}, {changeOnReload: true}]) {
        const {options, calls} = fixture(change);
        await assert.rejects(runGuidedTransfer(options));
        assert.deepEqual(calls, ['status', 'deactivate']);
    }
});

test('a failed validation never sends store and a lost store acknowledgement is never replayed', async () => {
    const invalid = fixture({validated: false});
    await assert.rejects(runGuidedTransfer(invalid.options));
    assert.deepEqual(invalid.calls, ['status', 'deactivate', 'validate']);
    const lost = fixture({loseStore: true});
    await assert.rejects(runGuidedTransfer(lost.options), /lost acknowledgement/);
    assert.equal(lost.calls.filter(action => action === 'store').length, 1);
});

test('pending operation is observed with cached reads and expires without replay', async () => {
    const {options, calls} = fixture(); let clock = 0;
    options.timeout = 100; options.now = () => clock; options.wait = async () => { clock += 60; };
    options.runtime = async (_data, action) => { calls.push(action); return {pending: true}; };
    await assert.rejects(runGuidedTransfer(options));
    assert.deepEqual(calls, ['status', 'poll', 'poll']);
});

test('restart intent or a remaining single run requires a confirmed complete stop before storage', async () => {
    for (const remaining of [{resumeAfterRestart: true}, {rules: {a: {state: 'running'}}}]) {
        const {options, calls} = fixture({executing: false});
        const runtime = options.runtime;
        options.runtime = async (data, action) => ({...await runtime(data, action), ...(action === 'status' ? remaining : {})});
        await runGuidedTransfer(options);
        assert.deepEqual(calls, ['status', 'deactivate', 'validate', 'store']);
    }
});

test('validation findings are observed even when storage is refused', async () => {
    const {options, calls} = fixture({validated: false}); const results = [];
    options.observe = (kind, result) => results.push([kind, result]);
    await assert.rejects(runGuidedTransfer(options));
    assert.equal(results.at(-1)[0], 'transfer');
    assert.equal(results.at(-1)[1].status, 'error');
    assert.ok(!calls.includes('store'));
});

test('a failed view refresh after stopping prevents validation and storage', async () => {
    const {options, calls} = fixture(); options.reload = async () => false;
    await assert.rejects(runGuidedTransfer(options));
    assert.deepEqual(calls, ['status', 'deactivate']);
});

function resumeFixture({wasRunning = true, recovery = true, failAt = '', changeAfterStore = false} = {}) {
    const {data, options} = fixture(); const calls = []; let executing = wasRunning; let saved = false;
    data.configuration.automations.push({id: 'new_rule', name: 'New disabled automation', enabled: false});
    options.resumePrevious = true;
    options.reload = async () => { if (saved && changeAfterStore) data.configuration.automations[1].enabled = true; };
    options.runtime = async (_data, action, fields) => {
        calls.push([action, fields]);
        if (action === failAt && (saved || action === 'deactivate')) throw new Error('synthetic failure');
        if (action === 'deactivate') executing = false;
        if (action === 'activate') { assert.equal(saved, true); assert.equal(fields.resume, recovery); executing = true; }
        return {confirmed: true, executing, pending: false, canActivate: true, resumeAfterRestart: recovery,
            resumeStatus: executing ? 'armed' : 'stopped', storedRevision: saved ? 3 : 2, activeRevision: executing ? saved ? 3 : 2 : null};
    };
    options.transfer = async (_data, action) => {
        calls.push([action]);
        if (action === failAt) throw new Error('synthetic failure');
        if (action === 'store') saved = true;
        return {pending: false, canStore: true, status: action === 'validate' ? 'validated' : 'stored', receiverConfirmed: true, storedRevision: 3};
    };
    return {data, options, calls};
}

for (const recovery of [false, true]) test('explicit resume follows verified storage and preserves restart preference ' + recovery, async () => {
    const {data, options, calls} = resumeFixture({recovery});
    const before = structuredClone(data.configuration);
    const result = await runGuidedTransfer(options);
    assert.equal(result.resumed, true);
    assert.deepEqual(calls.map(([action]) => action), ['status', 'deactivate', 'validate', 'store', 'status', 'activate']);
    assert.deepEqual(data.configuration, before, 'resuming never enables the new disabled rule or changes any draft flag');
});

test('resume consent never starts an already stopped system', async () => {
    const {options, calls} = resumeFixture({wasRunning: false});
    await runGuidedTransfer(options);
    assert.deepEqual(calls.map(([action]) => action), ['status', 'validate', 'store']);
});

test('failed stop, validation, storage or post-store refresh cannot resume operation', async () => {
    for (const failure of [{failAt: 'deactivate'}, {failAt: 'validate'}, {failAt: 'store'}, {failAt: 'status'}, {changeAfterStore: true}]) {
        const {options, calls} = resumeFixture(failure);
        await assert.rejects(runGuidedTransfer(options));
        assert.equal(calls.some(([action]) => action === 'activate'), false);
    }
});

test('a failed activation is sent once and does not undo or repeat confirmed storage', async () => {
    const {options, calls} = resumeFixture({failAt: 'activate'});
    await assert.rejects(runGuidedTransfer(options), /Wiederaufnahme/);
    assert.equal(calls.filter(([action]) => action === 'activate').length, 1);
    assert.equal(calls.filter(([action]) => action === 'store').length, 1);
});

test('missing recovery preference or mismatched stored evidence never permits resumption', async () => {
    const unknown = resumeFixture(); const initialRuntime = unknown.options.runtime;
    unknown.options.runtime = async (...args) => { const result = await initialRuntime(...args); delete result.resumeAfterRestart; return result; };
    await assert.rejects(runGuidedTransfer(unknown.options));
    assert.deepEqual(unknown.calls.map(([action]) => action), ['status']);
    for (const changed of [{confirmed: false}, {canActivate: false}, {storedRevision: 99}]) {
        const {options, calls} = resumeFixture(); const runtime = options.runtime;
        options.runtime = async (...args) => ({...await runtime(...args), ...(calls.some(([action]) => action === 'store') ? changed : {})});
        await assert.rejects(runGuidedTransfer(options));
        assert.equal(calls.some(([action]) => action === 'activate'), false);
    }
    const missing = resumeFixture(); const transfer = missing.options.transfer;
    missing.options.transfer = async (...args) => { const result = await transfer(...args); delete result.storedRevision; return result; };
    await assert.rejects(runGuidedTransfer(missing.options));
    assert.equal(missing.calls.some(([action]) => action === 'activate'), false);
});

test('pending activation is observed without repeating the activation request', async () => {
    const {options, calls} = resumeFixture(); const runtime = options.runtime;
    options.wait = async () => {};
    options.runtime = async (...args) => ({...await runtime(...args), pending: args[1] === 'activate'});
    assert.equal((await runGuidedTransfer(options)).resumed, true);
    assert.equal(calls.filter(([action]) => action === 'activate').length, 1);
    assert.equal(calls.at(-1)[0], 'poll');
});
