import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import * as localization from '../Z2MAutomations/visualization/localization.js';
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
const renewal = readFileSync(new URL('../Z2MAutomations/visualization/access.js', import.meta.url), 'utf8').replace(/^import .*localization.js';\r?\n/m, '').replace('export function', 'function');
function tileHost() {
    const nodes = new Map(); const requests = []; const timers = new Map(); const events = {}; const posts = [];
    const document = {documentElement: {}, body: {}, head: {},
        getElementById(id) {
            if (!nodes.has(id)) { const element = new Element(); if (id === 'designer-frame') { element.hidden = true; element.contentWindow = {postMessage: (value, origin) => posts.push({value, origin})}; } nodes.set(id, element); }
            return nodes.get(id);
        }};
    const openedObjects = [];
    const window = {openObject: id => openedObjects.push(id), Z2MATile: {version: 1, objectId: 12345, canMaximize: true, path: '/hook/Z2MAutomationsDesigner12345/index.html'}, document,
        location: {origin: 'http://synthetic.invalid', href: 'http://synthetic.invalid/visu/12345'},
        addEventListener: (name, fn) => { events[name] = fn; }, open() { throw new Error('The tile must never open another window.'); }};
    const context = {window, document, URL, crypto: webcrypto, Uint8Array, Date,
        getComputedStyle: () => ({fontSize: '18px', getPropertyValue: key => ({'--content-color': '#000000', '--card-color': '#ffffff', '--accent-color': '#00cdab'}[key] ?? '')}),
        requestAction: (ident, value) => requests.push({ident, ...JSON.parse(value)}),
        setTimeout: fn => { const id = Math.random(); timers.set(id, fn); return id; }, clearTimeout: id => timers.delete(id)};
    vm.runInNewContext(origin + '\n' + tile, context);
    const state = {selectedSystem: 'system-a', readAt: 1800000000, systems: [
        {systemId: 'system-a', name: 'Synthetic A', baseTopic: 'synthetic/a', rules: 3, status: 'Automatik aktiv · Stand 3'},
        {systemId: 'system-b', name: 'Synthetic B', baseTopic: 'synthetic/b', rules: 7, status: 'Automatik aktiv · Stand 7'}]};
    const reply = (request = requests.at(-1), changes = {}) => window.handleMessage(JSON.stringify({type: 'z2ma-tile', version: 1, client: request.client,
        request: request.request, action: request.action, ok: true, state, ...changes}));
    const message = (value, options = {}) => events.message({source: nodes.get('designer-frame').contentWindow, origin: 'http://synthetic.invalid', data: {channel: 'z2ma.designer.embed.v1', ...value}, ...options});
    const access = {path: window.Z2MATile.path, systemId: 'system-a', token: 'a'.repeat(64), expires: 2000000000};
    const connect = () => { reply(); reply(undefined, {access}); message({type: 'ready'}); };
    return {nodes, requests, timers, posts, events, window, state, reply, message, access, connect, openedObjects};
}
test('native tile automatically embeds the full designer at its fixed same-origin address', () => {
    const host = tileHost(); assert.equal(host.requests[0].action, 'status');
    host.connect();
    assert.equal(host.requests.length, 2); assert.equal(host.requests[1].action, 'access');
    assert.equal(host.nodes.get('designer-frame').hidden, false);
    assert.equal(host.nodes.get('designer-frame').src, 'http://synthetic.invalid' + host.access.path + '#' + 'a'.repeat(64));
    assert.equal(host.nodes.get('renew').disabled, false);
    assert.equal(host.posts[0].value.type, 'theme'); assert.equal(host.posts[0].value.fontSize, 18);
});
test('large view uses the native openObject action for the same automation instance', () => {
    const host = tileHost();
    host.connect(); host.nodes.get('maximize').listeners.click();
    assert.deepEqual(host.openedObjects, []);
    host.message({type: 'maximize-ready'});
    assert.deepEqual(host.openedObjects, [12345]);
    assert.equal(host.requests.length, 2);
});
test('maximization cannot replace the embedded document while its editor is open', () => {
    const host = tileHost(); host.connect();
    const address = host.nodes.get('designer-frame').src;
    host.nodes.get('maximize').listeners.click(); host.message({type: 'maximize-blocked'});
    assert.deepEqual(host.openedObjects, []);
    assert.equal(host.nodes.get('designer-frame').src, address);
    assert.match(host.nodes.get('feedback').textContent, /offene Bearbeitung/);
});
test('SDK replies remain JSON text, correlated to the actual client and current request', () => {
    const host = tileHost(); const first = host.requests[0];
    for (const data of ['{', 'null', '[]', '42', null, 17, {type: 'z2ma-tile'}]) assert.doesNotThrow(() => host.window.handleMessage(data));
    host.reply(first, {client: 'other'}); assert.equal(host.requests.length, 1);
    host.reply(first); assert.equal(host.requests.length, 2);
    host.reply(first); assert.equal(host.nodes.get('designer-frame').hidden, true);
    host.reply(undefined, {access: host.access});
    assert.equal(host.nodes.get('designer-frame').hidden, false);
});
test('status and access renewal keep the embedded document and pending editor input in place', () => {
    const host = tileHost(); host.connect(); const address = host.nodes.get('designer-frame').src;
    host.message({type: 'system', systemId: 'system-b'});
    host.nodes.get('refresh').listeners.click(); assert.equal(host.requests.at(-1).systemId, 'system-b');
    host.reply(undefined, {state: {...host.state, selectedSystem: 'system-b'}});
    host.nodes.get('renew').listeners.click(); assert.equal(host.posts.at(-1).value.type, 'renew');
    assert.equal(host.requests.length, 3); assert.equal(host.nodes.get('designer-frame').src, address);
    host.message({type: 'renewed', ok: true});
    assert.match(host.nodes.get('feedback').textContent, /Offene Eingaben/);
    assert.equal(host.nodes.get('renew').disabled, false);
});
test('foreign frame messages cannot change the selected system or acknowledge renewal', () => {
    const host = tileHost(); host.connect();
    host.message({type: 'system', systemId: 'system-b'}, {origin: 'http://other.invalid'});
    host.message({type: 'system', systemId: 'system-b'}, {source: {}});
    host.nodes.get('refresh').listeners.click(); assert.equal(host.requests.at(-1).systemId, 'system-a');
    host.reply(); host.nodes.get('renew').listeners.click();
    host.message({type: 'renewed', ok: true}, {source: {}}); assert.equal(host.nodes.get('renew').disabled, true);
    host.message({type: 'renewed', ok: false}); assert.equal(host.nodes.get('renew').disabled, false);
});
test('empty workspaces and failed or stale access never navigate a frame', () => {
    const empty = tileHost(); empty.reply(undefined, {state: {systems: [], selectedSystem: ''}});
    assert.equal(empty.requests.length, 1); assert.equal(empty.nodes.get('designer-frame').hidden, true);
    const invalid = tileHost(); invalid.reply(); invalid.reply(undefined, {access: {...invalid.access, path: 'https://other.invalid/'}});
    assert.equal(invalid.nodes.get('designer-frame').hidden, true);
    const late = tileHost(); const request = late.requests[0]; [...late.timers.values()][0](); late.reply(request);
    assert.equal(late.requests.length, 1); assert.equal(late.nodes.get('designer-frame').hidden, true);
});

const embedding = readFileSync(new URL('../Z2MAutomations/visualization/embedding.js', import.meta.url), 'utf8')
    .replace('export function', 'function').replace("import('./interaction.js')", 'loadInteraction()');
function embedHost(interaction = Promise.resolve()) {
    const events = {}; const posts = []; const properties = {}; let renewals = 0;
    const root = {dataset: {}, style: {setProperty: (key, value) => { properties[key] = value; }}};
    const parent = {postMessage: (value, origin) => posts.push({value, origin})};
    const window = {parent, name: 'burki24.wheel.v1', location: {origin: 'http://synthetic.invalid'}, document: {documentElement: root},
        addEventListener: (name, fn) => { events[name] = fn; }};
    vm.runInNewContext(embedding + '\nwindow.api = installNativeEmbedding({renew: async () => {renew(); return true;}});', {window, renew: () => renewals++, loadInteraction: () => interaction});
    const message = (value, options = {}) => events.message({source: parent, origin: window.location.origin, data: {channel: 'z2ma.designer.embed.v1', ...value}, ...options});
    return {root, properties, events, posts, window, message, renewals: () => renewals};
}
test('embedded designer follows native light/dark colors and font size independently of browser preference', async () => {
    const host = embedHost(); await Promise.resolve();
    host.message({type: 'theme', card: '#172338', text: '#ffffff', accent: '#00cdab', fontSize: 18});
    assert.equal(host.root.dataset.nativeEmbed, 'dark'); assert.equal(host.root.style.colorScheme, 'dark');
    assert.equal(host.properties['--native-text'], '#ffffff'); assert.equal(host.properties['--native-font-size'], '18px');
    host.message({type: 'theme', card: '#ffffff', text: '#000000', accent: '#00cdab', fontSize: 14});
    assert.equal(host.root.dataset.nativeEmbed, 'light'); assert.equal(host.properties['--native-text'], '#000000');
    assert.equal(host.posts[0].value.type, 'ready');
});
test('initial data reads can wait for the shared interaction dependency to release the module entry', async () => {
    let release;
    const interaction = new Promise(resolve => { release = resolve; });
    const host = embedHost(interaction);
    let reads = 0;
    host.window.api.ready.then(() => reads++);
    await Promise.resolve();
    assert.equal(reads, 0); assert.equal(host.posts.length, 0);
    release(); await host.window.api.ready; await Promise.resolve();
    assert.equal(reads, 1); assert.equal(host.posts[0].value.type, 'ready');
});
test('only the same-origin direct loader can request renewal or apply a native theme', async () => {
    const host = embedHost();
    await host.message({type: 'renew'}, {source: {}}); await host.message({type: 'renew'}, {origin: 'http://other.invalid'});
    assert.equal(host.renewals(), 0);
    host.message({type: 'theme', card: 'url(https://other.invalid)', text: '#000000', accent: '#00cdab', fontSize: 14});
    assert.equal(host.root.dataset.nativeEmbed, undefined);
    await host.message({type: 'renew'}); assert.equal(host.renewals(), 1); assert.equal(host.posts.at(-1).value.ok, true);
    host.window.api.update({workspaceContext: {systemId: 'synthetic-a'}}); host.window.api.update({workspaceContext: {systemId: 'synthetic-a'}});
    assert.equal(host.posts.filter(post => post.value.type === 'system').length, 1);
});

function renewalHost() {
    const requests = []; const events = {}; const docEvents = {}; const body = new Element();
    const document = {body, hidden: false, createElement: () => new Element(), addEventListener: (name, fn) => { docEvents[name] = fn; }, removeEventListener() {}};
    let tick; let result = {ok: true, status: 200, json: async () => ({ok: true, expires: 1800000900})};
    const window = {document, location: {href: 'http://synthetic.invalid/hook/designer/index.html#' + 'c'.repeat(64), hash: '#' + 'c'.repeat(64)},
        fetch: async (...args) => { requests.push(args); return result; }, setInterval: (fn, ms) => { assert.equal(ms, 240000); tick = fn; return 1; },
        clearInterval() {}, addEventListener: (name, fn) => { events[name] = fn; }};
    const context = {window, URL, AbortSignal, ...localization}; vm.runInNewContext(renewal + '\nwindow.api = installAccessRenewal();', context);
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
