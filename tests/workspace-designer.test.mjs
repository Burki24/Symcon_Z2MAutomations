import test from 'node:test';
import assert from 'node:assert/strict';
import {systemHeaders, loadSystemDevices, loadSystemView, checkViewResponse} from '../Z2MAutomations/visualization/workspace.js';

const context = {systemId: '11111111-1111-4111-8111-111111111111', generation: 3, fingerprint: 'a'.repeat(64)};
test('draft writes carry the exact system, generation and document fingerprint', () => {
    assert.deepEqual(systemHeaders({workspaceContext: context}), {'X-Z2MA-System-ID': context.systemId, 'X-Z2MA-Generation': '3', 'X-Z2MA-Fingerprint': context.fingerprint});
    assert.deepEqual(systemHeaders({}), {});
});
test('paged catalogs include all entries and preserve identical device IDs across systems', async () => {
    const data = {workspaceContext: context, devices: [{key: 'local'}], devicePage: {offset: 0, total: 3, hasMore: true}};
    const requests = [];
    await loadSystemDevices(data, {token: 'read', base: 'http://127.0.0.1/view/index.html#private', request: async (url, init) => {
        requests.push(init.headers); assert.equal(url.pathname, '/view/devices'); assert.equal(url.hash, '');
        const offset = Number(init.headers['X-Z2MA-Offset']);
        return {ok: true, json: async () => ({workspaceContext: context, devices: [{key: 'peer' + offset}], devicePage: {offset, total: 3, hasMore: offset < 2}})};
    }});
    assert.deepEqual(data.devices.map(device => device.key), ['local', 'peer1', 'peer2']);
    assert.deepEqual(requests.map(headers => headers['X-Z2MA-Offset']), ['1', '2']);
    assert.ok(requests.every(headers => headers['X-Z2MA-Fingerprint'] === context.fingerprint));
});
test('changed or empty catalog pages fail without blending another system into the view', async () => {
    for (const changed of [{...context, systemId: 'other'}, {...context, fingerprint: 'b'.repeat(64)}, context]) {
        const data = {workspaceContext: context, devices: [{key: 'local'}], devicePage: {total: 2, hasMore: true}};
        await assert.rejects(loadSystemDevices(data, {token: 'read', base: 'http://127.0.0.1/view/', request: async () => ({ok: true, json: async () => ({workspaceContext: changed, devices: [], devicePage: {offset: 1}})})}));
        assert.equal(data.devices.length, 1);
    }
});
test('HTTP failures stop paging without discarding the previous displayed model', async () => {
    const data = {workspaceContext: context, devices: [{key: 'local'}], devicePage: {total: 2, hasMore: true}};
    await assert.rejects(loadSystemDevices(data, {token: 'read', base: 'http://127.0.0.1/view/', request: async () => ({ok: false})}));
    assert.equal(data.devices[0].key, 'local');
});

test('one temporary module read is retried without repeating a control request', async () => {
    let reads = 0; const pauses = [];
    const expected = {workspaceContext: context, devices: [], configuration: {revision: 5, automations: [{id: 'new_rule'}]}};
    const result = await loadSystemView(async () => {
        await checkViewResponse({ok: ++reads > 1, status: reads === 1 ? 503 : 200});
        return structuredClone(expected);
    }, {base: 'http://127.0.0.1/view/', wait: async ms => pauses.push(ms)});
    assert.deepEqual(result, expected);
    assert.equal(reads, 2); assert.deepEqual(pauses, [250]);
});

test('a changed paged snapshot restarts the whole GET read and discards partial old devices', async () => {
    let reads = 0; let pages = 0; const calls = [];
    const nextContext = {...context, fingerprint: 'b'.repeat(64)};
    const result = await loadSystemView(async () => {
        reads++;
        return {workspaceContext: reads === 1 ? context : nextContext, workspace: {fingerprint: (reads === 1 ? 'c' : 'd').repeat(64)},
            configuration: {revision: reads === 1 ? 4 : 5}, devices: [{key: reads === 1 ? 'old0' : 'new0'}], devicePage: {offset: 0, total: 3, hasMore: true}};
    }, {token: 'synthetic', base: 'http://127.0.0.1/view/index.html', wait: async () => {}, request: async (url, init) => {
        calls.push([url.pathname, init.method ?? 'GET', init.body]); pages++;
        const offset = Number(init.headers['X-Z2MA-Offset']);
        if (pages === 2) return {ok: false, status: 409, json: async () => ({error: 'stale_workspace'})};
        if (reads === 1) return {ok: true, json: async () => ({workspaceContext: context, devices: [{key: 'old1'}], devicePage: {offset, total: 3, hasMore: true}})};
        assert.equal(init.headers['X-Z2MA-Fingerprint'], nextContext.fingerprint);
        assert.equal(init.headers['X-Z2MA-Workspace-Fingerprint'], 'd'.repeat(64));
        assert.equal(offset, 1);
        return {ok: true, json: async () => ({workspaceContext: nextContext, devices: [{key: 'new1'}, {key: 'new2'}], devicePage: {offset, total: 3, hasMore: false}})};
    }});
    assert.equal(reads, 2); assert.equal(result.configuration.revision, 5);
    assert.deepEqual(result.devices.map(device => device.key), ['new0', 'new1', 'new2']);
    assert.deepEqual(calls, Array.from({length: 3}, () => ['/view/devices', 'GET', undefined]));
});

test('read recovery is bounded and authorization or unrelated conflicts are never retried', async () => {
    for (const [status, error, expectedReads] of [[503, '', 2], [403, '', 1], [409, 'ownership_required', 1], [422, '', 1]]) {
        let reads = 0;
        await assert.rejects(loadSystemView(async () => {
            reads++; await checkViewResponse({ok: false, status, json: async () => ({error})});
        }, {wait: async () => {}}));
        assert.equal(reads, expectedReads);
    }
});

test('foreign systems, generations and malformed pages never trigger automatic read recovery', async () => {
    for (const changed of [{...context, systemId: 'foreign'}, {...context, generation: 4}, context]) {
        let reads = 0;
        await assert.rejects(loadSystemView(async () => {
            reads++; return {workspaceContext: context, devices: [{key: 'original'}], devicePage: {offset: 0, total: 2, hasMore: true}};
        }, {token: 'synthetic', base: 'http://127.0.0.1/view/', wait: async () => {}, request: async () => ({ok: true,
            json: async () => ({workspaceContext: changed, devices: changed === context ? [] : [{key: 'foreign'}], devicePage: {offset: 1, total: 2, hasMore: false}})})}));
        assert.equal(reads, 1);
    }
});
