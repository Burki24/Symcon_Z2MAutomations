import test from 'node:test';
import assert from 'node:assert/strict';
import {workspaceSetupRequest, setLocalRuleEnabled} from '../Z2MAutomations/visualization/symcon-workspace.js';

const base = 'http://localhost/hook/synthetic/index.html';
const choices = {ok: true, fingerprint: 'a'.repeat(64), candidates: 'b'.repeat(64), choices: [
    {Connection: 12345, Use: true, editable: false, Name: 'Synthetic system', MQTT: 'Synthetic parent', Topic: 'synthetic', Status: 'Ready'}]};
const response = value => ({ok: true, text: async () => JSON.stringify(value)});

test('guided system choices are authenticated read-only requests with a bounded signal', async () => {
    const calls = [];
    assert.deepEqual(await workspaceSetupRequest('status', {token: 'synthetic', base, request: async (url, options) => {
        calls.push({url, options}); return response(choices);
    }}), choices);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url.pathname, '/hook/synthetic/workspace-status');
    assert.equal(calls[0].options.method, 'GET');
    assert.equal(calls[0].options.body, undefined);
    assert.equal(calls[0].options.headers['X-Z2MA-Read-Token'], 'synthetic');
    assert.ok(calls[0].options.signal instanceof AbortSignal);
});

test('malformed setup choices cannot be offered and a failed assignment is never replayed', async () => {
    for (const value of [{...choices, fingerprint: 'invalid'}, {...choices, choices: {}}, {...choices, choices: [{...choices.choices[0], editable: 'true'}]}]) {
        await assert.rejects(workspaceSetupRequest('status', {base, request: async () => response(value)}));
    }
    let calls = 0;
    await assert.rejects(workspaceSetupRequest('configure', {base, token: 'synthetic', body: {connections: [12345]}, request: async () => {
        calls++; throw new Error('PRIVATE_TRANSPORT_DETAIL');
    }}), error => !error.message.includes('PRIVATE_TRANSPORT_DETAIL'));
    assert.equal(calls, 1);
});

const draft = () => ({editAllowed: true, createContext: 'context', workspaceContext: {systemId: 'a', generation: 2, fingerprint: 'c'.repeat(64)},
    configuration: {automations: [{id: 'rule_a', name: 'Synthetic', enabled: false, actions: []}]}});

test('an operation checkbox reuses the existing scoped local edit endpoint and changes only enabled', async () => {
    const data = draft(); const before = structuredClone(data); let calls = 0;
    await setLocalRuleEnabled(data, 'rule_a', true, {base, token: 'synthetic', request: async (url, options) => {
        calls++;
        assert.equal(url.pathname, '/hook/synthetic/edit');
        assert.equal(options.method, 'POST');
        assert.equal(options.headers['X-Z2MA-System-ID'], 'a');
        assert.equal(options.headers['X-Z2MA-Generation'], '2');
        assert.equal(options.headers['X-Z2MA-Fingerprint'], 'c'.repeat(64));
        const body = JSON.parse(options.body);
        assert.deepEqual(Object.keys(body).sort(), ['changes', 'context', 'requestId', 'ruleId']);
        assert.deepEqual(body.changes, {enabled: true});
        assert.equal(body.context, data.createContext); assert.equal(body.ruleId, 'rule_a');
        return response({ruleId: 'rule_a'});
    }});
    assert.equal(calls, 1); assert.deepEqual(data, before);
});

test('missing authority, unknown rules and lost local edit replies cannot change or replay a rule', async () => {
    let calls = 0; const request = async () => { calls++; throw new Error('PRIVATE_TRANSPORT_DETAIL'); };
    for (const [data, id, enabled] of [[{...draft(), editAllowed: false}, 'rule_a', true], [draft(), 'foreign', true], [draft(), 'rule_a', 'true']]) {
        await assert.rejects(setLocalRuleEnabled(data, id, enabled, {base, request}));
    }
    assert.equal(calls, 0);
    await assert.rejects(setLocalRuleEnabled(draft(), 'rule_a', true, {base, request}), error => !error.message.includes('PRIVATE_TRANSPORT_DETAIL'));
    assert.equal(calls, 1);
});
