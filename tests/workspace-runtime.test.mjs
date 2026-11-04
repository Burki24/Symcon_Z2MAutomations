import test from 'node:test';
import assert from 'node:assert/strict';
import {runtimeRequest, installRuntime} from '../Z2MAutomations/visualization/runtime.js';

const endpoint = 'http://127.0.0.1/view/index.html#PRIVATE_TOKEN';
const context = {systemId: '11111111-1111-4111-8111-111111111111', generation: 2, fingerprint: 'a'.repeat(64)};
const model = () => ({workspaceContext: {...context}, workspace: {}, module: {baseTopic: 'synthetic/one'},
    runtime: {...context, context: 'b'.repeat(64), pending: false, operation: '', message: 'Status bestätigt.', startNotice: '',
        confirmed: true, executing: false, resumeAfterRestart: true, canActivate: true, canStop: true, canRefresh: true,
        rules: {same_rule: {name: '<b>Synthetic rule</b>', run: '22222222-2222-4222-8222-222222222222', state: 'completed', message: 'Abgeschlossen.', canRun: true, canStop: false}}}});

test('runtime commands carry the exact system, stored context, run UUID and restart choice', async () => {
    const data = model();
    for (const action of ['status', 'activate', 'deactivate', 'run_once', 'stop_rule']) {
        let calls = 0;
        const result = await runtimeRequest(data, action, {base: endpoint, token: 'PRIVATE_TOKEN', rule: 'same_rule', run: data.runtime.rules.same_rule.run, resume: false,
            requestId: '33333333-3333-4333-8333-333333333333', request: async (url, init) => {
                calls++; assert.equal(url.pathname, '/view/runtime'); assert.equal(url.hash, ''); assert.equal(init.method, 'POST');
                assert.equal(init.headers['X-Z2MA-System-ID'], context.systemId); assert.equal(init.headers['X-Z2MA-Generation'], '2');
                assert.equal(init.headers['X-Z2MA-Fingerprint'], context.fingerprint);
                assert.deepEqual(JSON.parse(init.body), {action, context: data.runtime.context, requestId: '33333333-3333-4333-8333-333333333333', rule: 'same_rule', run: data.runtime.rules.same_rule.run, resume: false});
                return {ok: true, json: async () => ({ok: true, runtime: data.runtime})};
            }});
        assert.equal(calls, 1); assert.equal(result, data.runtime);
    }
});
test('polling is GET-only; failed commands are never retried and foreign replies are rejected', async () => {
    const data = model(); let calls = 0;
    await runtimeRequest(data, 'poll', {base: endpoint, request: async (url, init) => {
        assert.equal(url.pathname, '/view/runtime-status'); assert.equal(init.method, 'GET'); assert.equal(init.body, undefined);
        return {ok: true, json: async () => ({ok: true, runtime: data.runtime})};
    }});
    await assert.rejects(runtimeRequest(data, 'activate', {base: endpoint, request: async () => { calls++; throw new Error('PRIVATE_NETWORK'); }}), /Keine lesbare Rückmeldung/);
    assert.equal(calls, 1);
    for (const runtime of [{...data.runtime, systemId: 'other'}, {...data.runtime, generation: 3}, {...data.runtime, rules: {same_rule: {name: 'incomplete'}}}]) {
        await assert.rejects(runtimeRequest(data, 'poll', {base: endpoint, request: async () => ({ok: true, json: async () => ({ok: true, runtime})})}));
    }
});

class Element {
    constructor() { this.children = []; this.events = {}; this.disabled = false; this.hidden = false; this.textContent = ''; this.value = ''; }
    append(...nodes) { this.children.push(...nodes); }
    replaceChildren() { this.children = []; }
    after(node) { this.next = node; }
    setAttribute() {}
    addEventListener(name, callback) { this.events[name] = callback; }
}
test('visible controls require confirmation, retain per-system selection and discard a late foreign response', async () => {
    const previous = {document: globalThis.document, location: globalThis.location, fetch: globalThis.fetch, confirm: globalThis.confirm};
    const anchor = new Element(); let data = model(); let calls = 0; let finish; let prompt = '';
    globalThis.document = {createElement: () => new Element(), querySelector: selector => selector === '.system-selector' ? anchor : null};
    globalThis.location = {href: endpoint, hash: '#token'}; globalThis.confirm = text => { prompt = text; return false; };
    globalThis.fetch = async () => { calls++; return new Promise(resolve => { finish = resolve; }); };
    try {
        const update = installRuntime({getData: () => data, reload: async () => {}, message: text => assert.fail(text)});
        update(); const panel = anchor.next;
        const [activate, stop] = panel.children[4].children; const selection = panel.children[5].children[0]; const [once, stopOne] = panel.children[7].children;
        assert.match(panel.children[0].textContent, /synthetic\/one/);
        assert.equal(activate.disabled, false); assert.equal(stop.disabled, false); assert.equal(once.disabled, true); assert.equal(stopOne.disabled, true);
        assert.equal(selection.children[1].textContent, '<b>Synthetic rule</b>', 'Names remain text, never markup');
        await activate.events.click(); assert.equal(calls, 0); assert.match(prompt, /synthetic\/one/);
        selection.value = 'same_rule'; selection.events.change(); assert.equal(once.disabled, false);
        globalThis.confirm = () => true;
        const old = data; const waiting = once.events.click(); assert.equal(calls, 1);
        data = model(); data.workspaceContext.systemId = '44444444-4444-4444-8444-444444444444'; data.runtime.systemId = data.workspaceContext.systemId; data.module.baseTopic = 'synthetic/two'; update();
        assert.equal(selection.value, '');
        finish({ok: true, json: async () => ({ok: true, runtime: {...old.runtime, message: 'OLD SYSTEM'}})}); await waiting;
        assert.doesNotMatch(panel.children[1].textContent, /OLD SYSTEM/); assert.match(panel.children[0].textContent, /synthetic\/two/);
        data = old; update(); assert.equal(selection.value, 'same_rule');
        data = null; update(); assert.equal(panel.hidden, true);
    } finally { Object.assign(globalThis, previous); }
});
