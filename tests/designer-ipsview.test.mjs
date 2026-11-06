import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import {openIPSViewSession} from '../Z2MAutomations/visualization/ipsview-access.js';

test('IPSView entry is exchanged before use; the grant never enters a network URL', async () => {
    const requests = []; const history = [];
    const environment = {location: {href: 'https://synthetic.invalid/hook/designer/ipsview.html#entry.' + 'a'.repeat(64), hash: '#entry.' + 'a'.repeat(64)},
        history: {replaceState: (...args) => history.push(args)}, fetch: async (...args) => { requests.push(args); return {ok: true, json: async () => ({ok: true, token: 'b'.repeat(64), expires: 1800000900})}; }};
    await openIPSViewSession(environment);
    assert.equal(requests.length, 1); assert.equal(requests[0][0].hash, ''); assert.equal(requests[0][0].pathname, '/hook/designer/ipsview-access');
    assert.equal(requests[0][1].headers['X-Z2MA-Read-Token'], 'a'.repeat(64)); assert.equal(requests[0][1].redirect, 'error');
    assert.equal(history.length, 1); assert.equal(history[0][2], '#' + 'b'.repeat(64));
    assert.equal(requests[0][1].body, '{}');
});

test('expired, malformed and rejected entries never replace the current page or replay writes', async () => {
    let replacements = 0; let requests = 0;
    const environment = {location: {href: 'https://synthetic.invalid/hook/designer/ipsview.html', hash: '#entry.invalid'},
        history: {replaceState: () => replacements++}, fetch: async () => { requests++; return {ok: false}; }};
    await assert.rejects(openIPSViewSession(environment)); assert.equal(requests, 0);
    environment.location.hash = '#entry.' + 'c'.repeat(64);
    await assert.rejects(openIPSViewSession(environment)); assert.equal(requests, 1); assert.equal(replacements, 0);
    environment.location.hash = '#' + 'd'.repeat(64);
    await openIPSViewSession(environment); assert.equal(requests, 1);
});

const origin = readFileSync(new URL('../libs/helper/visualization/http-origin.js', import.meta.url), 'utf8');
const loader = readFileSync(new URL('../Z2MAutomations/visualization/ipsview-loader.js', import.meta.url), 'utf8');
function host(changes = {}) {
    const elements = {}; const events = {}; const timers = new Map(); const posts = [];
    for (const id of ['ipsview-designer', 'ipsview-feedback', 'ipsview-renew', 'ipsview-retry']) elements[id] = {hidden: id === 'ipsview-designer', disabled: false, listeners: {}, addEventListener(name, fn) { this.listeners[name] = fn; }};
    elements['ipsview-designer'].contentWindow = {postMessage: (...args) => posts.push(args)};
    const document = {getElementById: id => elements[id], baseURI: 'data:text/html,synthetic'};
    const window = {document, Z2MAIPSView: {runtime: {origin: 'https://synthetic.invalid', path: '/hook/Z2MAutomationsDesigner12345/ipsview.html', grant: 'e'.repeat(64), ...changes}},
        location: {origin: 'null', href: 'data:text/html,synthetic'}, addEventListener: (name, fn) => { events[name] = fn; }};
    vm.runInNewContext(origin + '\n' + loader, {window, document, URL, setTimeout: fn => { const id = timers.size + 1; timers.set(id, fn); return id; }, clearTimeout: id => timers.delete(id)});
    const message = (type, changes = {}) => events.message({source: elements['ipsview-designer'].contentWindow, origin: 'https://synthetic.invalid', data: {channel: 'z2ma.designer.embed.v1', type, ...changes}});
    return {elements, events, timers, posts, message};
}

test('opaque HTMLBox loads only the configured own full designer and preserves it during renewal', () => {
    const page = host(); const frame = page.elements['ipsview-designer'];
    assert.equal(frame.hidden, false); assert.match(frame.src, /^https:\/\/synthetic.invalid\/hook\/Z2MAutomationsDesigner12345\/ipsview.html#entry\./);
    page.message('ready'); const url = frame.src;
    page.elements['ipsview-renew'].listeners.click(); assert.equal(page.posts.length, 1);
    page.message('renewed', {ok: true}); assert.equal(frame.src, url); assert.equal(page.elements['ipsview-renew'].disabled, false);
    assert.match(page.elements['ipsview-feedback'].textContent, /offene Eingaben bleiben erhalten/);
});

test('HTMLBox rejects foreign frame replies, arbitrary paths and missing server addresses', () => {
    const page = host(); page.elements['ipsview-renew'].disabled = true;
    page.events.message({source: {}, origin: 'https://synthetic.invalid', data: {channel: 'z2ma.designer.embed.v1', type: 'ready'}});
    assert.equal(page.elements['ipsview-renew'].disabled, true);
    assert.equal(host({path: '/other'}).elements['ipsview-designer'].hidden, true);
    assert.equal(host({origin: ''}).elements['ipsview-designer'].hidden, true);
    assert.equal(host({grant: ''}).elements['ipsview-designer'].hidden, true);
});
