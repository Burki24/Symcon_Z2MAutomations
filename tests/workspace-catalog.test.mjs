import test from 'node:test';
import assert from 'node:assert/strict';
import {catalogRequest, installCatalogRefresh} from '../Z2MAutomations/visualization/catalog.js';

const base = 'http://127.0.0.1/view/index.html#PRIVATE_TOKEN';
const model = () => ({workspace: {}, module: {baseTopic: 'synthetic/one'},
    workspaceContext: {systemId: '11111111-1111-4111-8111-111111111111', generation: 2, fingerprint: 'a'.repeat(64)},
    catalogRefresh: {systemId: '11111111-1111-4111-8111-111111111111', generation: 2, fingerprint: 'a'.repeat(64),
        operation: '', phase: 'idle', pending: false, canRefresh: true, message: 'Bereit', loadedAt: null, counts: {devices: 0, groups: 0, scenes: 0}}});
const response = (catalogRefresh, ok = true) => ({ok, text: async () => JSON.stringify({ok, catalogRefresh})});

test('explicit catalog POST carries one exact system binding and no document or runtime command', async () => {
    const data = model(); let calls = 0;
    const view = await catalogRequest(data, 'refresh', {base, token: 'PRIVATE_TOKEN', requestId: '22222222-2222-4222-8222-222222222222', request: async (url, init) => {
        calls++; assert.equal(url.pathname, '/view/catalog-refresh'); assert.equal(url.hash, ''); assert.equal(init.method, 'POST');
        assert.equal(init.headers['X-Z2MA-System-ID'], data.workspaceContext.systemId); assert.equal(init.headers['X-Z2MA-Generation'], '2');
        assert.equal(init.headers['X-Z2MA-Fingerprint'], data.workspaceContext.fingerprint); assert.equal(init.credentials, 'same-origin');
        assert.deepEqual(JSON.parse(init.body), {requestId: '22222222-2222-4222-8222-222222222222'});
        return response(data.catalogRefresh);
    }});
    assert.equal(calls, 1); assert.deepEqual(view, data.catalogRefresh);
});

test('polling uses GET only, failed POST is never retried and transport details stay hidden', async () => {
    const data = model(); let calls = 0;
    await catalogRequest(data, 'status', {base, request: async (url, init) => {
        assert.equal(url.pathname, '/view/catalog-status'); assert.equal(init.method, 'GET'); assert.equal(init.body, undefined);
        return response(data.catalogRefresh);
    }});
    await assert.rejects(catalogRequest(data, 'refresh', {base, request: async () => { calls++; throw Error('PRIVATE_NETWORK_URL'); }}), error => !error.message.includes('PRIVATE_NETWORK_URL') && /nicht erneut/.test(error.message));
    assert.equal(calls, 1);
});

test('foreign, malformed and oversized catalog status cannot overwrite another model', async () => {
    const data = model(); const before = structuredClone(data);
    for (const view of [{...data.catalogRefresh, systemId: 'other'}, {...data.catalogRefresh, generation: 3}, {...data.catalogRefresh, counts: {devices: -1}}, {...data.catalogRefresh, pending: undefined}]) {
        await assert.rejects(catalogRequest(data, 'status', {base, request: async () => response(view)}));
    }
    await assert.rejects(catalogRequest(data, 'status', {base, request: async () => ({ok: true, text: async () => 'x'.repeat(16385)})}));
    await assert.rejects(catalogRequest(data, 'refresh', {base, request: async () => ({ok: false, text: async () => JSON.stringify({ok: false, message: 'Das Modul ist beschäftigt.'})})}), /beschäftigt/);
    assert.deepEqual(data, before);
});

class Element {
    constructor() { this.children = []; this.events = {}; this.attrs = {}; this.classList = {toggle: (key, on) => { this.attrs[key] = on; }}; }
    append(...items) { this.children.push(...items); }
    after(item) { this.next = item; }
    setAttribute(key, value) { this.attrs[key] = value; }
    addEventListener(key, callback) { this.events[key] = callback; }
}

test('visible pending state, completion, dialogs and late foreign replies preserve user inputs', async () => {
    const previous = {document: globalThis.document, location: globalThis.location, fetch: globalThis.fetch};
    const anchor = new Element(); let data = model(); let modal = false; let calls = 0; let reloads = 0; let finish;
    globalThis.document = {createElement: () => new Element(), querySelector: selector => selector === '.system-selector' ? anchor : selector === 'dialog[open]' && modal ? {} : null};
    globalThis.location = {href: base, hash: '#PRIVATE_TOKEN'};
    globalThis.fetch = async () => { calls++; return new Promise(resolve => { finish = resolve; }); };
    const update = installCatalogRefresh({getData: () => data, reload: async () => { reloads++; }, message: () => {}});
    try {
        update(); const panel = anchor.next; const [refresh, poll] = panel.children[3].children;
        assert.equal(calls, 0, 'Opening is read-only'); assert.match(panel.children[0].textContent, /synthetic\/one/);
        modal = true; await refresh.events.click(); assert.equal(calls, 0, 'An open editor blocks a new refresh without losing input'); modal = false;
        const waiting = refresh.events.click(); assert.equal(calls, 1); assert.equal(refresh.disabled, true); assert.equal(panel.attrs['aria-busy'], 'true');
        finish(response({...data.catalogRefresh, phase: 'health', operation: 'first', pending: true, canRefresh: false, message: 'Verbindung wird geprüft'})); await waiting;
        assert.equal(refresh.disabled, true); assert.match(refresh.textContent, /geladen/); assert.match(panel.children[1].textContent, /Verbindung/);
        modal = true; const pollWait = poll.events.click();
        finish(response({...data.catalogRefresh, phase: 'loaded', pending: false, canRefresh: true, fingerprint: 'b'.repeat(64), message: 'Gerätedaten geladen', loadedAt: 1800000000, counts: {devices: 5, groups: 2, scenes: 1}})); await pollWait;
        assert.equal(reloads, 0, 'Completing a refresh never reloads a modal with unsaved input'); assert.match(panel.children[2].textContent, /5 Geräte/);
        modal = false;
        const old = data; const late = poll.events.click();
        data = model(); data.workspaceContext.systemId = '33333333-3333-4333-8333-333333333333'; data.module.baseTopic = 'synthetic/two'; update();
        finish(response({...old.catalogRefresh, message: 'OLD SYSTEM'})); await late;
        assert.match(panel.children[0].textContent, /synthetic\/two/); assert.doesNotMatch(panel.children[1].textContent, /OLD SYSTEM/);
        const failed = refresh.events.click(); finish({ok: false, text: async () => JSON.stringify({ok: false, message: 'Bitte Verbindung prüfen.'})}); await failed;
        assert.match(panel.children[1].textContent, /Bitte Verbindung prüfen/, 'A failure remains visible after controls are enabled again');
        assert.equal(refresh.disabled, false);
    } finally { data = null; update(); Object.assign(globalThis, previous); }
});
