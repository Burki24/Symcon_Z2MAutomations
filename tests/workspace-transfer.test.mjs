import test from 'node:test';
import assert from 'node:assert/strict';
import {transferRequest, installTransfers} from '../Z2MAutomations/visualization/transfer.js';

const context = {systemId: '11111111-1111-4111-8111-111111111111', generation: 2, fingerprint: 'a'.repeat(64)};
const model = () => ({workspaceContext: {...context}, workspace: {}, module: {baseTopic: 'synthetic/one'},
    transfer: {systemId: context.systemId, generation: 2, fingerprint: context.fingerprint, context: 'b'.repeat(64), operation: '',
        status: 'idle', message: 'Noch nicht geprüft', details: '', pending: false, canValidate: true, canStore: false}});
const endpoint = 'http://127.0.0.1/view/index.html#PRIVATE_TOKEN';

test('validate and store POST carry one exact system context, request ID and validation context', async () => {
    for (const action of ['validate', 'store']) {
        const data = model(); let sent = 0;
        const view = await transferRequest(data, action, {token: 'PRIVATE_TOKEN', base: endpoint, requestId: '22222222-2222-4222-8222-222222222222', request: async (url, init) => {
            sent++; assert.equal(url.pathname, '/view/transfer'); assert.equal(url.hash, ''); assert.equal(init.method, 'POST');
            assert.equal(init.headers['X-Z2MA-System-ID'], context.systemId); assert.equal(init.headers['X-Z2MA-Generation'], '2');
            assert.equal(init.headers['X-Z2MA-Fingerprint'], context.fingerprint);
            assert.deepEqual(JSON.parse(init.body), {action, context: data.transfer.context, requestId: '22222222-2222-4222-8222-222222222222'});
            return {ok: true, json: async () => ({ok: true, transfer: data.transfer})};
        }});
        assert.equal(sent, 1); assert.equal(view, data.transfer);
    }
});
test('status polling is a cached GET without write body and never retries failed POST', async () => {
    const data = model(); let sent = 0;
    await transferRequest(data, 'status', {token: 'read', base: endpoint, request: async (url, init) => {
        assert.equal(url.pathname, '/view/transfer-status'); assert.equal(init.method, 'GET'); assert.equal(init.body, undefined);
        return {ok: true, json: async () => ({ok: true, transfer: data.transfer})};
    }});
    await assert.rejects(transferRequest(data, 'store', {base: endpoint, request: async () => { sent++; throw new Error('PRIVATE_NETWORK_URL'); }}), /Keine lesbare Rückmeldung/);
    assert.equal(sent, 1);
});
test('foreign systems, changed generations, incomplete replies and rejected jobs cannot replace status', async () => {
    const data = model(); const original = structuredClone(data);
    for (const transfer of [{...data.transfer, systemId: 'other'}, {...data.transfer, generation: 3}, {...data.transfer, canStore: undefined}]) {
        await assert.rejects(transferRequest(data, 'status', {base: endpoint, request: async () => ({ok: true, json: async () => ({ok: true, transfer})})}));
    }
    await assert.rejects(transferRequest(data, 'validate', {base: endpoint, request: async () => ({ok: false, json: async () => ({ok: false, message: 'Bitte neu prüfen.'})})}), /Bitte neu prüfen/);
    assert.deepEqual(data, original);
});

class Element {
    constructor() { this.children = []; this.events = {}; this.disabled = false; this.hidden = false; this.textContent = ''; }
    append(...nodes) { this.children.push(...nodes); }
    after(node) { this.next = node; }
    setAttribute() {}
    addEventListener(name, callback) { this.events[name] = callback; }
}
test('visible controls explain scope, require validation and confirmation, and discard a late foreign-system result', async () => {
    const previous = {document: globalThis.document, location: globalThis.location, fetch: globalThis.fetch, confirm: globalThis.confirm};
    const anchor = new Element(); let data = model(); let requests = 0; let resolve;
    globalThis.document = {createElement: () => new Element(), querySelector: selector => selector === '.system-selector' ? anchor : null};
    globalThis.location = {href: endpoint, hash: '#token'};
    globalThis.confirm = () => false;
    globalThis.fetch = async () => { requests++; return new Promise(done => { resolve = done; }); };
    try {
        const update = installTransfers({getData: () => data, reload: async () => {}, message: () => {}});
        update(); const panel = anchor.next; const [validate, store] = panel.children[3].children;
        assert.match(panel.children[0].textContent, /synthetic\/one/); assert.equal(validate.disabled, false); assert.equal(store.disabled, true);
        data.transfer.canStore = true; update(); await store.events.click(); assert.equal(requests, 0, 'Cancelled confirmation sends nothing');
        const waiting = validate.events.click(); assert.equal(requests, 1); assert.equal(store.disabled, true);
        const old = data;
        data = model(); data.workspaceContext.systemId = '33333333-3333-4333-8333-333333333333'; data.module.baseTopic = 'synthetic/two'; update();
        resolve({ok: true, json: async () => ({ok: true, transfer: {...old.transfer, message: 'Old system reply', canStore: true}})});
        await waiting; assert.equal(data.transfer.canStore, false); assert.doesNotMatch(panel.children[1].textContent, /Old system reply/);
        assert.match(panel.children[0].textContent, /synthetic\/two/);
        data = null; update(); assert.equal(panel.hidden, true);
    } finally { Object.assign(globalThis, previous); }
});
