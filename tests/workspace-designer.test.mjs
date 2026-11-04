import test from 'node:test';
import assert from 'node:assert/strict';
import {systemHeaders, loadSystemDevices} from '../Z2MAutomations/visualization/workspace.js';

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
