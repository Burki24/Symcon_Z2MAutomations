import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import vm from 'node:vm';
import * as localization from '../Z2MAutomations/visualization/localization.js';

const source = readFileSync(new URL('../Z2MAutomations/visualization/startup.js', import.meta.url), 'utf8');
async function start(failure) {
    const nodes = new Map();
    const listeners = new Map();
    let imports = 0; let reloads = 0;
    const document = {querySelector(selector) {
        if (!nodes.has(selector)) nodes.set(selector, {textContent: '', disabled: false, dataset: {}, addEventListener: (name, fn) => listeners.set(name, fn)});
        return nodes.get(selector);
    }};
    vm.runInNewContext(source.replace("import('./reader.js')", 'loadReader()').replace("import('./localization.js')", 'loadLocalization()'), {
        document, loadLocalization: () => Promise.resolve(localization), location: {reload: () => reloads++},
        loadReader: () => { imports++; return failure ? Promise.reject(failure) : Promise.resolve(); }
    });
    for (let index = 0; index < 8; index++) await Promise.resolve();
    return {nodes, listeners, imports, reloads: () => reloads};
}

test('the deferred loader starts one module import and disables obsolete runtime buttons', async () => {
    const result = await start();
    assert.equal(result.imports, 1);
    for (const name of ['save', 'activate', 'stop']) assert.equal(result.nodes.get('#' + name + '-button').disabled, true);
    assert.equal(result.nodes.get('#draft-status').textContent, 'Designer wird geladen …');
    assert.equal(result.listeners.size, 0);
    assert.equal(result.reloads(), 0);
});

test('a failed module import has a readable recovery action without disclosing errors or tokens', async () => {
    const result = await start(new Error('PRIVATE_ACCESS_TOKEN http://synthetic.invalid/private'));
    const status = result.nodes.get('#draft-status'); const feedback = result.nodes.get('#feedback'); const button = result.nodes.get('#validate-button');
    assert.equal(status.textContent, 'Designer konnte nicht starten');
    assert.match(feedback.textContent, /Entwürfe und Z2M bleiben unverändert/);
    assert.doesNotMatch(feedback.textContent, /PRIVATE_ACCESS_TOKEN|synthetic.invalid/);
    assert.equal(feedback.dataset.tone, 'error');
    assert.equal(button.disabled, false);
    assert.equal(button.textContent, 'Designer neu laden');
    assert.equal(result.reloads(), 0);
    result.listeners.get('click')();
    assert.equal(result.reloads(), 1);
});
