import test from 'node:test';
import assert from 'node:assert/strict';
import {checkedInstallation, installationRequest} from '../Z2MAutomations/visualization/installation.js';
import {diagnosticsRequest} from '../Z2MAutomations/visualization/diagnostics.js';

const data = {workspaceContext: {systemId: '11111111-1111-4111-8111-111111111111', generation: 1, fingerprint: 'a'.repeat(64)}};
const base = 'http://localhost/hook/designer/#PRIVATE_TOKEN';
const view = () => ({...data.workspaceContext, context: 'b'.repeat(64), status: 'checking', pending: true, canInstall: false,
    blocked: 'installation_pending', message: 'Verbindung wird geprüft.', runtimeMessage: 'Automatikbetrieb aktiv.', notice: 'Abläufe können enden.',
    mayHaveUploaded: false, installedBuild: 'c'.repeat(64), bundledBuild: 'd'.repeat(64), executing: true,
    devices: {phase: 'none', pending: false, message: 'Nach Installation werden Gerätedaten geladen.', counts: {devices: 0, groups: 0, scenes: 0}}});
const response = v => ({ok: true, text: async () => JSON.stringify({ok: true, installation: v})});

test('installation uses only fixed scoped routes and explicit consent without code or MQTT topics', async () => {
    const payload = {requestId: 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa', context: 'b'.repeat(64), confirmed: true};
    for (const body of [payload, null]) {
        let calls = 0;
        const result = await installationRequest(data, body, {base, token: 'TOKEN', request: async (url, options) => {
            calls++;
            assert.equal(url.pathname, '/hook/designer/' + (body ? 'installation' : 'installation-status')); assert.equal(url.hash, '');
            assert.equal(options.method, body ? 'POST' : 'GET'); assert.equal(options.headers['X-Z2MA-System-ID'], data.workspaceContext.systemId);
            assert.equal(options.headers['X-Z2MA-Fingerprint'], data.workspaceContext.fingerprint);
            assert.equal(options.credentials, 'same-origin'); assert.equal(options.redirect, 'error'); assert.equal(options.cache, 'no-store');
            assert.equal(options.body, body ? JSON.stringify(payload) : undefined); return response(view());
        }});
        assert.equal(calls, 1); assert.equal(result.installation.status, 'checking');
    }
});

test('foreign, malformed, oversized or failed installation replies never cause automatic replay', async () => {
    for (const v of [{...view(), generation: 2}, {...view(), systemId: 'other'}, {...view(), bundledBuild: 'invalid'}, {...view(), pending: 'yes'}, {...view(), executing: 1}]) {
        assert.equal(checkedInstallation(v, data.workspaceContext), false);
        await assert.rejects(installationRequest(data, null, {base, request: async () => response(v)}), /passt nicht/);
    }
    let calls = 0;
    await assert.rejects(installationRequest(data, {confirmed: true}, {base, request: async () => {calls++; throw Error('network');}}), /nicht erneut gesendet/);
    assert.equal(calls, 1);
    await assert.rejects(installationRequest(data, null, {base, request: async () => ({ok: true, text: async () => 'x'.repeat(16385)})}), /nicht erneut gesendet/);
    await assert.rejects(installationRequest(data, null, {base, request: async () => ({ok: false, text: async () => JSON.stringify({ok: false, message: 'Freigabe fehlt.'})})}), /Freigabe fehlt/);
});

test('pending, installed and interrupted states remain distinct and install status cannot replace another system diagnostics', async () => {
    for (const status of ['idle', 'checking', 'prepared', 'pending', 'installed', 'error', 'interrupted']) assert.equal(checkedInstallation({...view(), status}, data.workspaceContext), true);
    await assert.rejects(diagnosticsRequest(data, null, {base, request: async () => response({...view(), generation: 2})}));
});

test('installation remains busy through its device refresh and preserves a separate partial error', async () => {
    const loading = {...view(), status: 'refreshing', devices: {...view().devices, phase: 'loading', pending: true, message: 'Gerätedaten werden geladen.'}};
    assert.equal(checkedInstallation(loading, data.workspaceContext), true);
    const result = await installationRequest(data, null, {base, request: async () => response(loading)});
    assert.equal(result.installation.pending, true); assert.equal(result.installation.canInstall, false);
    for (const phase of ['loaded', 'error', 'interrupted']) {
        const v = {...view(), status: 'installed', pending: false, devices: {...view().devices, phase, message: phase === 'loaded' ? 'Gerätedaten aktuell.' : 'Erweiterung installiert, Gerätedaten nicht geladen.'}};
        assert.equal(checkedInstallation(v, data.workspaceContext), true);
    }
    for (const devices of [undefined, {...loading.devices, pending: false}, {...loading.devices, counts: {devices: -1, groups: 0, scenes: 0}}, {...loading.devices, phase: 'wrong'}]) {
        assert.equal(checkedInstallation({...loading, devices}, data.workspaceContext), false);
    }
    assert.equal(checkedInstallation({...loading, status: 'installed'}, data.workspaceContext), false);
});
