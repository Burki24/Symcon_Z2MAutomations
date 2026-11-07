import test from 'node:test';
import assert from 'node:assert/strict';
import {checkedPermissions, permissionChanges, permissionsRequest, installPermissions} from '../Z2MAutomations/visualization/permissions.js';

const base = 'http://z2ma-designer.test:1234/view/index.html#PRIVATE_TOKEN';
const model = {workspaceContext: {systemId: '11111111-1111-4111-8111-111111111111', generation: 1, fingerprint: 'a'.repeat(64)}};
const view = () => ({...model.workspaceContext, context: 'b'.repeat(64), name: 'Synthetic receiver', baseTopic: 'synthetic/receiver', canEdit: true, blocked: '',
    message: 'Bereit', notice: 'Nur lokaler Entwurf', peers: ['synthetic/old'], choices: [
        {systemId: '', name: 'Bisheriges System', topic: 'synthetic/old', assigned: false, selected: true, affectedCount: 0, affected: []},
        {systemId: '22222222-2222-4222-8222-222222222222', name: 'Synthetic sender', topic: 'synthetic/sender', assigned: true, selected: false,
            affectedCount: 1, affected: [{id: 'disabled_rule', name: 'Synthetic disabled sequence user', enabled: false}]}]});
const response = result => ({ok: true, text: async () => JSON.stringify({ok: true, ...result})});

test('directed changes retain unknown grants, include sequence users and reject invented selections', () => {
    const v = view(); assert.equal(checkedPermissions(v, model.workspaceContext), true);
    assert.deepEqual(permissionChanges(v, ['synthetic/old']), {added: [], removed: []});
    const change = permissionChanges(v, ['synthetic/sender']);
    assert.equal(change.added[0].name, 'Synthetic sender'); assert.equal(change.removed[0].topic, 'synthetic/old');
    assert.equal(v.choices[1].affected[0].enabled, false);
    for (const peers of [['unknown'], ['synthetic/old', 'synthetic/old'], Array(9).fill('synthetic/old'), null]) assert.throws(() => permissionChanges(v, peers));
    assert.deepEqual(v.peers, ['synthetic/old'], 'Review never changes the loaded model');
});

test('GET reads only the scoped receiver and POST carries the narrow explicitly confirmed change', async () => {
    const payload = {requestId: 'cccccccc-cccc-4ccc-8ccc-cccccccccccc', context: 'b'.repeat(64), peers: ['synthetic/sender'], confirmed: true}; let calls = 0;
    const request = async (url, init) => {
        calls++; assert.equal(url.hash, ''); assert.equal(url.origin, 'http://z2ma-designer.test:1234');
        assert.equal(init.headers['X-Z2MA-System-ID'], model.workspaceContext.systemId); assert.equal(init.headers['X-Z2MA-Generation'], '1');
        assert.equal(init.headers['X-Z2MA-Fingerprint'], 'a'.repeat(64)); assert.equal(init.headers['X-Z2MA-Read-Token'], 'PRIVATE_TOKEN');
        assert.equal(init.credentials, 'same-origin'); assert.equal(init.redirect, 'error'); assert.equal(init.cache, 'no-store');
        if (calls === 1) { assert.equal(url.pathname, '/view/permissions-status'); assert.equal(init.method, 'GET'); assert.equal(init.body, undefined); return response({permissions: view()}); }
        assert.equal(url.pathname, '/view/permissions'); assert.equal(init.method, 'POST'); assert.deepEqual(JSON.parse(init.body), payload);
        return response({permissionsSaved: true, ...model.workspaceContext, changed: true, peerCount: 1, notice: 'Nur Entwurf'});
    };
    assert.deepEqual(await permissionsRequest(model, null, {base, token: 'PRIVATE_TOKEN', request}), view());
    assert.equal((await permissionsRequest(model, payload, {base, token: 'PRIVATE_TOKEN', request})).permissionsSaved, true); assert.equal(calls, 2);
});

test('malformed and foreign views cannot be used for a confirmation', async () => {
    for (const malformed of [{...view(), systemId: 'other'}, {...view(), generation: 2}, {...view(), peers: []}, {...view(), context: ''},
        {...view(), choices: [...view().choices, view().choices[0]]}, {...view(), choices: [null]}, {...view(), choices: [{...view().choices[0], affectedCount: 1}]},
        {...view(), choices: [{...view().choices[1], affected: [null]}]}, {...view(), baseTopic: 'bad/+'}]) {
        assert.equal(checkedPermissions(malformed, model.workspaceContext), false);
        await assert.rejects(permissionsRequest(model, null, {base, request: async () => response({permissions: malformed})}));
    }
    await assert.rejects(permissionsRequest(model, null, {base, request: async () => ({ok: true, text: async () => 'x'.repeat(262145)})}));
    await assert.rejects(permissionsRequest(model, {}, {base, request: async () => response({permissionsSaved: true, ...model.workspaceContext, fingerprint: ''})}));
    await assert.rejects(permissionsRequest(model, null, {base, request: async () => ({ok: true, text: async () => 'null'})}), /Bitte den Stand neu laden/);
});

test('transport loss never retries and server rejections stay understandable', async () => {
    let calls = 0;
    await assert.rejects(permissionsRequest(model, {}, {base, request: async () => { calls++; throw Error('PRIVATE_URL'); }}), error => /nicht automatisch erneut/.test(error.message) && !error.message.includes('PRIVATE_URL'));
    assert.equal(calls, 1);
    await assert.rejects(permissionsRequest(model, {}, {base, request: async () => ({ok: false, text: async () => JSON.stringify({message: 'Die Freigaben haben sich geändert. Bitte neu laden.'})})}), /Bitte neu laden/);
    await assert.rejects(permissionsRequest({}, null, {base}), /empfangende/);
});

class Element {
    constructor(tag = '') { this.tag = tag; this.children = []; this.events = {}; this.fields = {}; this.open = false; }
    append(...items) { this.children.push(...items); }
    replaceChildren(...items) { this.children = items; }
    setAttribute() {}
    addEventListener(key, callback) { this.events[key] = callback; }
    set innerHTML(value) {
        for (const match of value.matchAll(/class="([a-z-]+)(?: [a-z-]+)*"/g)) this.fields['.' + match[1]] = new Element();
        this.fields['.permissions-accept'] = new Element();
    }
    querySelector(selector) { return this.fields[selector]; }
    querySelectorAll(selector) {
        const all = [...this.children, ...Object.values(this.fields)].flatMap(child => [child, ...child.querySelectorAll('input')]);
        return [...new Set(all)].filter(child => child.tag === 'input' && (selector !== 'input:checked' || child.checked));
    }
    showModal() { this.open = true; }
    close() { this.open = false; }
}

async function dialogTest(run) {
    const previous = {document: globalThis.document, location: globalThis.location, fetch: globalThis.fetch};
    const toolbar = new Element(); const body = new Element(); let data = {...model, workspace: {}, permissionsAllowed: true, module: {name: 'Synthetic receiver'}};
    let reloads = 0; let feedback = '';
    globalThis.document = {body, createElement: tag => new Element(tag), querySelector: selector => selector === '.toolbar' ? toolbar : body.children.find(child => child.open) ?? null};
    globalThis.location = {href: base, hash: '#PRIVATE_TOKEN'};
    const update = installPermissions({getData: () => data, reload: async () => { reloads++; return true; }, message: text => { feedback = text; }});
    const dialog = body.children[0]; const button = toolbar.children[0];
    const click = selector => dialog.querySelector(selector).events.click();
    try { await run({dialog, button, click, update, setData: next => { data = next; update(); }, data, reloads: () => reloads, feedback: () => typeof feedback === 'function' ? feedback() : feedback}); }
    finally { Object.assign(globalThis, previous); }
}

test('dialog reads, cancels and reviews receiver-only changes without an implicit write', async () => dialogTest(async ui => {
    let calls = 0; globalThis.fetch = async () => { calls++; return response({permissions: view()}); };
    ui.update(); assert.equal(calls, 0); assert.equal(ui.button.disabled, false);
    await ui.button.events.click(); assert.equal(calls, 1); assert.equal(ui.dialog.open, true);
    assert.match(ui.dialog.querySelector('.permissions-target').textContent, /Empfangendes System: Synthetic receiver/);
    const inputs = ui.dialog.querySelectorAll('input'); assert.equal(inputs[0].checked, true);
    inputs[0].checked = false; inputs[1].checked = true;
    ui.click('.permissions-check'); assert.equal(calls, 1);
    assert.equal(ui.dialog.querySelector('.permissions-edit').hidden, true);
    const changes = ui.dialog.querySelector('.permissions-changes').children.map(item => item.textContent).join(' ');
    assert.match(changes, /synthetic\/sender → Synthetic receiver/); assert.match(changes, /Betroffene Automationen sind unbekannt/);
    ui.click('.permissions-back'); assert.equal(inputs[0].checked, false, 'Back retains the local selection');
    ui.click('.permissions-cancel'); assert.equal(ui.dialog.open, false); assert.equal(ui.reloads(), 0); assert.equal(calls, 1);
}));

test('manual replay after lost reply keeps exact payload and binding; no automatic retry or reciprocal grant', async () => dialogTest(async ui => {
    let attempts = 0; const sent = [];
    globalThis.fetch = async (url, init) => {
        if (init.method === 'GET') return response({permissions: view()});
        attempts++; sent.push({body: JSON.parse(init.body), headers: init.headers});
        if (attempts === 1) throw Error('lost reply');
        return response({permissionsSaved: true, ...model.workspaceContext, changed: true, peerCount: 2, notice: 'Im lokalen Entwurf übernommen'});
    };
    await ui.button.events.click(); ui.dialog.querySelectorAll('input')[1].checked = true; ui.click('.permissions-check');
    await ui.click('.permissions-accept'); assert.equal(attempts, 1); assert.equal(ui.dialog.open, true); assert.equal(ui.reloads(), 0);
    assert.match(ui.dialog.querySelector('.permissions-feedback').textContent, /nicht automatisch erneut/);
    await ui.click('.permissions-accept'); assert.equal(attempts, 2); assert.deepEqual(sent[0], sent[1]);
    assert.deepEqual(sent[0].body.peers, ['synthetic/old', 'synthetic/sender']); assert.equal(sent[0].body.confirmed, true);
    assert.equal(sent[0].headers['X-Z2MA-System-ID'], model.workspaceContext.systemId); assert.equal(ui.reloads(), 1); assert.equal(ui.dialog.open, false);
    assert.match(ui.feedback(), /lokalen Entwurf/);
}));

test('late foreign read and missing capability cannot expose or confirm an old system view', async () => dialogTest(async ui => {
    let finish; let calls = 0; globalThis.fetch = async () => { calls++; return new Promise(resolve => { finish = resolve; }); };
    const loading = ui.button.events.click(); assert.equal(ui.button.disabled, true); assert.equal(calls, 1);
    ui.setData({...ui.data, workspaceContext: {...model.workspaceContext, systemId: 'other'}});
    finish(response({permissions: view()})); await loading; assert.equal(ui.dialog.open, false); assert.equal(ui.reloads(), 0);
    ui.setData({...ui.data, permissionsAllowed: false}); assert.equal(ui.button.disabled, true);
    await ui.button.events.click(); assert.equal(calls, 1);
}));
