import test from 'node:test';
import assert from 'node:assert/strict';
import {installSystemSelection} from '../Z2MAutomations/visualization/workspace.js';
import {createUUID} from '../Z2MAutomations/visualization/identity.js';
import {requestIdentity} from '../Z2MAutomations/visualization/creator.js';
import {catalogRequest} from '../Z2MAutomations/visualization/catalog.js';
import {transferRequest} from '../Z2MAutomations/visualization/transfer.js';
import {runtimeRequest} from '../Z2MAutomations/visualization/runtime.js';

const uuid = /^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/;
const nativeCrypto = globalThis.crypto;
async function withCrypto(value, run) {
    const descriptor = Object.getOwnPropertyDescriptor(globalThis, 'crypto');
    Object.defineProperty(globalThis, 'crypto', {configurable: true, value});
    try { return await run(); } finally { Object.defineProperty(globalThis, 'crypto', descriptor); }
}
class Element {
    append() {}
    after() {}
    replaceChildren(...children) { this.children = children; }
    addEventListener() {}
}

test('UUIDs preserve random bits, set the v4 and variant fields and use fresh secure bytes', async () => {
    assert.equal(requestIdentity, createUUID);
    for (const [fill, expected] of [[0, '00000000-0000-4000-8000-000000000000'], [255, 'ffffffff-ffff-4fff-bfff-ffffffffffff']]) {
        let calls = 0;
        await withCrypto({getRandomValues: bytes => { calls++; assert.ok(bytes instanceof Uint8Array); assert.equal(bytes.length, 16); return bytes.fill(fill); }}, async () => {
            assert.equal(createUUID(), expected); assert.equal(calls, 1);
        });
    }
    await withCrypto({getRandomValues: bytes => nativeCrypto.getRandomValues(bytes)}, async () => {
        const values = Array.from({length: 2000}, () => createUUID());
        assert.ok(values.every(value => uuid.test(value))); assert.equal(new Set(values).size, values.length);
    });
});

test('missing or rejected secure randomness fails with a German message without leaking details', async () => {
    for (const crypto of [undefined, {}, {getRandomValues() { throw new Error('PRIVATE_RANDOMNESS_DETAIL'); }}]) {
        await withCrypto(crypto, async () => {
            assert.throws(() => createUUID(), error => /Aktion konnte nicht vorbereitet/.test(error.message) && !/PRIVATE_RANDOMNESS_DETAIL|TypeError/.test(error.message));
        });
    }
});

test('catalog, transfer and runtime defaults reach their endpoint once without randomUUID', async () => {
    await withCrypto({getRandomValues: bytes => nativeCrypto.getRandomValues(bytes)}, async () => {
        const data = {workspaceContext: {systemId: '11111111-1111-4111-8111-111111111111', generation: 1, fingerprint: 'a'.repeat(64)}, runtime: {}, transfer: {}};
        for (const [request, action, path] of [[catalogRequest, 'refresh', 'catalog-refresh'], [transferRequest, 'validate', 'transfer'], [runtimeRequest, 'status', 'runtime']]) {
            let calls = 0;
            await assert.rejects(request(data, action, {base: 'http://synthetic.invalid/designer/index.html', request: async (url, init) => {
                calls++; assert.equal(url.pathname, '/designer/' + path); assert.equal(init.method, 'POST');
                assert.match(JSON.parse(init.body).requestId, uuid);
                return {ok: false, json: async () => ({ok: false, message: 'Synthetic stop'}), text: async () => JSON.stringify({ok: false, message: 'Synthetic stop'})};
            }}), /Synthetic stop/);
            assert.equal(calls, 1);
        }
    });
});

test('system selection sends a valid UUID and switches both ways without secure-context randomUUID', async () => {
    await withCrypto({getRandomValues: bytes => nativeCrypto.getRandomValues(bytes)}, async () => {
        const previous = {document: globalThis.document, Option: globalThis.Option, location: globalThis.location, fetch: globalThis.fetch};
        const a = '11111111-1111-4111-8111-111111111111', b = '22222222-2222-4222-8222-222222222222';
        const data = {workspaceContext: {systemId: a}, workspace: {fingerprint: 'a'.repeat(64), systems: [a, b].map(id => ({name: 'Synthetic', binding: {systemId: id, baseTopic: 'synthetic'}}))}};
        const original = structuredClone(data); const requests = []; const messages = [];
        globalThis.document = {createElement: () => new Element(), querySelector: selector => selector === '.preview-notice' ? new Element() : null};
        globalThis.Option = class { constructor(text, value) { this.text = text; this.value = value; } };
        globalThis.location = {href: 'http://synthetic.invalid/hook/designer/index.html#PRIVATE_TOKEN', hash: '#PRIVATE_TOKEN'};
        globalThis.fetch = async (url, options) => { requests.push({url, options}); return {ok: true, json: async () => ({ok: true})}; };
        try {
            const control = installSystemSelection({getData: () => data, select: async id => {data.workspaceContext.systemId = id;}, message: s => messages.push(s)});
            control(data);
            assert.equal(await control.choose(b), true, messages.join('; '));
            assert.equal(await control.choose(a), true, messages.join('; '));
            assert.equal(requests.length, 2);
            const ids = requests.map(({url, options}, i) => {
                assert.equal(url.pathname, '/hook/designer/select-system'); assert.equal(url.hash, ''); assert.equal(options.method, 'POST');
                const body = JSON.parse(options.body); assert.equal(body.systemId, i ? a : b); assert.equal(body.fingerprint, data.workspace.fingerprint);
                assert.match(body.requestId, uuid); return body.requestId;
            });
            assert.notEqual(ids[0], ids[1]); assert.deepEqual(messages, []); assert.deepEqual(data, original);
        } finally { Object.assign(globalThis, previous); }
    });
});
