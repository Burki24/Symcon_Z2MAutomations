import test from 'node:test';
import assert from 'node:assert/strict';
import {diagnosticsRequest, recordingEnd, buildHint} from '../Z2MAutomations/visualization/diagnostics.js';

const data = {workspaceContext: {systemId: '11111111-1111-4111-8111-111111111111', generation: 1, fingerprint: 'a'.repeat(64)}};
const view = () => ({systemId: data.workspaceContext.systemId, generation: 1, fingerprint: 'a'.repeat(64), context: 'b'.repeat(64), name: 'Synthetic system', baseTopic: 'synthetic/one',
    connection: 12345, mqttParent: 12346, mqttConnected: true, online: true, z2mVersion: 'synthetic', healthText: 'Gesund', phase: 'confirmed', pending: false, requestedAt: 1800000000,
    canCheck: true, blocked: '', message: 'Bestätigt', lastError: '', build: null, debug: {phase: 'active', active: true, pending: false, until: 1800000600, remaining: 600, canStart: false, canStop: true, message: 'Aufzeichnung läuft'}});
const response = v => ({ok: true, text: async () => JSON.stringify({ok: true, diagnostics: v, systems: [v]})});
const base = 'http://127.0.0.1/hook/synthetic/index.html#TOKEN';

test('diagnostic GET is cached and POST uses a fixed scoped contract without automatic retry', async () => {
    const calls = []; const request = async (url, init) => {calls.push({url: String(url), init}); return response(view());};
    const original = structuredClone(data);
    await diagnosticsRequest(data, null, {token: 'TOKEN', base, request});
    assert.equal(calls[0].init.method, 'GET'); assert.equal(calls[0].init.body, undefined); assert.match(calls[0].url, /diagnostics-status$/);
    const payload = {requestId: '22222222-2222-4222-8222-222222222222', action: 'debug_start', seconds: 60, context: 'b'.repeat(64)};
    await diagnosticsRequest(data, payload, {token: 'TOKEN', base, request});
    assert.equal(calls[1].init.method, 'POST'); assert.match(calls[1].url, /\/diagnostics$/);
    assert.deepEqual(JSON.parse(calls[1].init.body), payload); assert.equal(calls[1].init.headers['X-Z2MA-System-ID'], data.workspaceContext.systemId);
    assert.equal(calls[1].init.headers['X-Z2MA-Fingerprint'], 'a'.repeat(64)); assert.equal(calls[1].init.redirect, 'error'); assert.deepEqual(data, original);
    let count = 0;
    await assert.rejects(diagnosticsRequest(data, payload, {base, request: async () => {count++; throw Error('network');}}), /nicht erneut/); assert.equal(count, 1);
});

test('foreign bindings, malformed views, excessive rows and rejected responses cannot replace diagnostics', async () => {
    for (const changed of [{...view(), systemId: 'other'}, {...view(), generation: 2}, {...view(), debug: {...view().debug, until: 'wrong'}}]) {
        await assert.rejects(diagnosticsRequest(data, null, {base, request: async () => response(changed)}));
    }
    await assert.rejects(diagnosticsRequest(data, null, {base, request: async () => ({ok: true, text: async () => JSON.stringify({ok: true, diagnostics: view(), systems: Array(9).fill(view())})})}));
    await assert.rejects(diagnosticsRequest(data, null, {base, request: async () => ({ok: true, text: async () => 'x'.repeat(32769)})}));
    await assert.rejects(diagnosticsRequest(data, null, {base, request: async () => ({ok: false, text: async () => JSON.stringify({message: 'Zugang ist abgelaufen.'})})}), /abgelaufen/);
});

test('the displayed end is absolute and is never extended by a client poll', () => {
    const d = view().debug; const first = recordingEnd(d);
    assert.match(first, /automatisch/); assert.notEqual(first, recordingEnd({...d, until: 1800000700}));
    assert.equal(first, recordingEnd({...d, remaining: 3}));
    assert.match(recordingEnd({...d, until: null, phase: 'idle'}), /Keine/);
});

test('central build hints do not direct users to an unavailable native installation button', () => {
    assert.match(buildHint({comparison: 'different', fields: {ExtensionUpdateInfo: 'Press unavailable install button'}}), /Update verfügbar/);
    assert.doesNotMatch(buildHint({comparison: 'different'}), /button|Button|installieren/);
    assert.match(buildHint({comparison: 'legacy'}), /keine Buildkennung/);
    assert.equal(buildHint({comparison: 'current', fields: {ExtensionUpdateInfo: 'Aktuell'}}), 'Aktuell');
});
