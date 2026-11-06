import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import {webcrypto} from 'node:crypto';

class Element {
    textContent = ''; value = ''; disabled = false; hidden = false; dataset = {}; children = []; listeners = {}; attributes = {};
    append(...nodes) { this.children.push(...nodes); }
    replaceChildren(...nodes) { this.children = nodes; }
    setAttribute(name, value) { this.attributes[name] = value; }
    removeAttribute(name) { delete this.attributes[name]; if (name === 'href') delete this.href; }
    addEventListener(name, listener) { this.listeners[name] = listener; }
}
const origin = readFileSync(new URL('../libs/helper/visualization/http-origin.js', import.meta.url), 'utf8');
const tile = readFileSync(new URL('../Z2MAutomations/visualization/tile.js', import.meta.url), 'utf8');
const renewal = readFileSync(new URL('../Z2MAutomations/visualization/access.js', import.meta.url), 'utf8').replace('export function', 'function');
function tileHost({blocked = false, opaque = false} = {}) {
    const nodes = new Map(); const requests = []; const timers = new Map(); const popups = []; const events = {};
    const document = {hidden: false, referrer: 'http://synthetic.invalid/visualization/', baseURI: opaque ? 'about:blank' : 'http://synthetic.invalid/visualization/',
        getElementById(id) { if (!nodes.has(id)) nodes.set(id, new Element()); return nodes.get(id); },
        createElement: () => new Element(), addEventListener: (name, fn) => { events[name] = fn; }};
    const window = {Z2MATile: {version: 1, path: '/hook/Z2MAutomationsDesigner12345/index.html'}, document,
        location: {origin: opaque ? 'null' : 'http://synthetic.invalid', href: opaque ? 'about:blank' : 'http://synthetic.invalid/visualization/'},
        addEventListener() {}, open() { if (blocked) return null; const popup = {close() { this.closed = true; }, location: {replace(url) { this.href = url; }}}; popups.push(popup); return popup; }};
    const context = {window, document, URL, crypto: webcrypto, Uint8Array, Date, requestAction: (ident, value) => requests.push({ident, ...JSON.parse(value)}),
        setTimeout: fn => { const id = timers.size + 1; timers.set(id, fn); return id; }, clearTimeout: id => timers.delete(id)};
    vm.runInNewContext(origin + '\n' + tile, context);
    const state = {selectedSystem: 'system-a', readAt: 1800000000, systems: [
        {systemId: 'system-a', name: 'Synthetic A <script>', baseTopic: 'synthetic/a', rules: 3, sequences: 1, status: 'Automatik aktiv · Stand 3', openInput: true},
        {systemId: 'system-b', name: 'Synthetic B', baseTopic: 'synthetic/b', rules: 7, sequences: 2, status: 'Kein aktiver Automatikbetrieb bestätigt', openInput: false}]};
    const reply = (request = requests.at(-1), changes = {}) => window.handleMessage(JSON.stringify({type: 'z2ma-tile', version: 1, client: request.client,
        request: request.request, action: request.action, ok: true, state, ...changes}));
    return {nodes, requests, timers, popups, events, window, state, reply};
}
test('native tile reads cached status, filters other clients and selects systems without editor writes', () => {
    const host = tileHost(); const first = host.requests[0];
    assert.equal(first.ident, 'DesignerTile'); assert.equal(first.action, 'status');
    host.reply(first, {client: 'other'}); assert.equal(host.nodes.get('open').disabled, true);
    host.reply(); assert.equal(host.nodes.get('rules').textContent, 3); assert.equal(host.nodes.get('sequences-label').textContent, 'Aktionsfolge');
    assert.equal(host.nodes.get('system').children[0].textContent, 'Synthetic A <script> · synthetic/a');
    host.nodes.get('system').value = 'system-b'; host.nodes.get('system').listeners.change();
    assert.equal(host.requests[1].systemId, 'system-b'); assert.equal(host.requests[1].action, 'status');
    host.reply(host.requests[1], {state: {...host.state, selectedSystem: 'system-b'}});
    assert.equal(host.nodes.get('rules').textContent, 7); assert.equal(host.requests.length, 2);
});
test('malformed JSON and unsupported SDK values leave the pending request available for its real reply', () => {
    const host = tileHost();
    for (const data of ['{', 'null', '[]', '42', null, 17, {type: 'z2ma-tile'}]) {
        assert.doesNotThrow(() => host.window.handleMessage(data));
        assert.equal(host.nodes.get('open').disabled, true);
    }
    host.reply();
    assert.equal(host.nodes.get('open').disabled, false);
    assert.equal(host.nodes.get('rules').textContent, 3);
});
test('native designer opens only its validated path and capability, also from an opaque SDK document', () => {
    const host = tileHost({opaque: true}); host.reply(); host.nodes.get('open').listeners.click();
    assert.equal(host.requests.at(-1).action, 'access');
    host.reply(undefined, {access: {path: host.window.Z2MATile.path, systemId: 'system-a', token: 'a'.repeat(64), expires: 2000000000}});
    assert.equal(host.popups[0].opener, null);
    assert.equal(host.popups[0].location.href, 'http://synthetic.invalid' + host.window.Z2MATile.path + '#' + 'a'.repeat(64));
    assert.equal(host.nodes.get('designer-link').hidden, false);
    host.nodes.get('renew').listeners.click();
    host.reply(undefined, {access: {path: 'https://other.invalid/', systemId: 'system-a', token: 'b'.repeat(64), expires: 2000000000}});
    assert.equal(host.nodes.get('designer-link').hidden, true);
});
test('popup blocking leaves an explicit link, renewal does not navigate an existing designer', () => {
    const host = tileHost({blocked: true}); host.reply(); host.nodes.get('open').listeners.click();
    host.reply(undefined, {access: {path: host.window.Z2MATile.path, systemId: 'system-a', token: 'a'.repeat(64), expires: 2000000000}});
    assert.equal(host.nodes.get('designer-link').hidden, false); assert.equal(host.popups.length, 0);
    host.nodes.get('renew').listeners.click();
    host.reply(undefined, {access: {path: host.window.Z2MATile.path, systemId: 'system-a', token: 'b'.repeat(64), expires: 2000000000}});
    assert.match(host.nodes.get('designer-link').href, /#b{64}$/);
    assert.equal(host.popups.length, 0);
});
test('delayed and failed SDK responses cannot open the wrong system or leak a previous link', () => {
    const host = tileHost(); host.reply(); host.nodes.get('open').listeners.click();
    const request = host.requests.at(-1); [...host.timers.values()][0]();
    host.reply(request, {access: {path: host.window.Z2MATile.path, systemId: 'system-a', token: 'a'.repeat(64), expires: 2000000000}});
    assert.equal(host.popups[0].closed, true); assert.equal(host.nodes.get('designer-link').hidden, true);
    assert.equal(host.nodes.get('designer-link').href, undefined);
    host.nodes.get('open').listeners.click(); host.reply(undefined, {ok: false});
    assert.equal(host.popups[1].closed, true); assert.equal(host.nodes.get('open').disabled, false);
});

function renewalHost() {
    const requests = []; const events = {}; const docEvents = {}; const body = new Element();
    const document = {body, hidden: false, createElement: () => new Element(), addEventListener: (name, fn) => { docEvents[name] = fn; }, removeEventListener() {}};
    let tick; let result = {ok: true, status: 200, json: async () => ({ok: true, expires: 1800000900})};
    const window = {document, location: {href: 'http://synthetic.invalid/hook/designer/index.html#' + 'c'.repeat(64), hash: '#' + 'c'.repeat(64)},
        fetch: async (...args) => { requests.push(args); return result; }, setInterval: (fn, ms) => { assert.equal(ms, 240000); tick = fn; return 1; },
        clearInterval() {}, addEventListener: (name, fn) => { events[name] = fn; }};
    const context = {window, URL, AbortSignal}; vm.runInNewContext(renewal + '\nwindow.api = installAccessRenewal();', context);
    return {window, document, requests, events, docEvents, tick: () => tick(), result: value => { result = value; }, panel: body.children[0]};
}
test('access renewal keeps editor DOM, fragment, system context and pending writes untouched', async () => {
    const host = renewalHost(); const editor = new Element(); editor.value = 'Unsaved synthetic name'; host.document.body.append(editor);
    const fragment = host.window.location.hash;
    await host.window.api.renew();
    assert.equal(host.requests.length, 1); assert.match(host.requests[0][0].href, /\/access-renew$/);
    assert.equal(host.requests[0][1].redirect, 'error'); assert.equal(host.requests[0][1].body, '{}'); assert.equal(host.requests[0][1].headers['X-Z2MA-Read-Token'], 'c'.repeat(64));
    assert.equal(host.window.location.hash, fragment); assert.equal(editor.value, 'Unsaved synthetic name');
    assert.equal(host.document.body.children[1], editor); assert.equal(host.panel.dataset.tone, 'ok');
    host.document.hidden = true; host.tick(); assert.equal(host.requests.length, 1);
});
test('renewal rejects expired access without replaying writes; transient failure remains retryable', async () => {
    const host = renewalHost();
    host.result({ok: false, status: 503}); await host.window.api.renew(); assert.equal(host.panel.children[1].disabled, false);
    host.result({ok: false, status: 403}); await host.window.api.renew();
    assert.equal(host.panel.children[1].disabled, true); assert.match(host.panel.children[0].textContent, /Offene Eingaben bleiben/);
    await host.window.api.renew(); assert.equal(host.requests.length, 2);
    assert.ok(host.requests.every(([, options]) => options.body === '{}'));
});
