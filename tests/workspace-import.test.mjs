import test from 'node:test';
import assert from 'node:assert/strict';
import {importRequest, importRows, importMessage} from '../Z2MAutomations/visualization/imports.js';
const data = {workspaceContext: {systemId: '11111111-1111-4111-8111-111111111111', generation: 2, fingerprint: 'a'.repeat(64)}};
const view = {systemId: data.workspaceContext.systemId, generation: 2, context: 'b'.repeat(64), canEvaluate: true,
    scanned: true, limited: false, rows: [{id: 12345, name: '<script>Event</script>', device: 'Same name', instanceId: 23456, importable: true, imported: false, assessment: 'Importierbar'},
        {id: 12346, name: 'Other', device: 'Same name', instanceId: 23457, importable: false, imported: false, assessment: 'Nicht importierbar'}],
    selected: {id: 12345, name: 'Event', summary: 'Synthetic only', assessment: 'Importierbar', canImport: true, token: 'c'.repeat(32)}, message: 'Ready'};
test('explicit event operations bind one system, preview and request identity without carrying event code or rule data', async () => {
    const calls = [];
    const request = async (url, options) => { calls.push({url, options}); return {ok: true, text: async () => JSON.stringify({ok: true, action: 'preview', view})}; };
    const payload = {requestId: '22222222-2222-4222-8222-222222222222', context: view.context, action: 'preview', eventId: 12345};
    const result = await importRequest(data, payload, {token: 'synthetic', base: 'https://example.invalid/designer/index.html#hidden', request});
    assert.equal(result.view.selected.id, 12345);
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url.pathname, '/designer/event-import');
    assert.equal(calls[0].url.hash, '');
    assert.equal(calls[0].options.headers['X-Z2MA-System-ID'], data.workspaceContext.systemId);
    assert.equal(calls[0].options.headers['X-Z2MA-Generation'], '2');
    assert.deepEqual(JSON.parse(calls[0].options.body), payload);
    assert.equal(calls[0].options.method, 'POST');
    assert.equal(calls[0].options.redirect, 'error');
});
test('event search distinguishes equal device names by IDs, preserves order and can show only importable events', () => {
    assert.deepEqual(importRows(view, '23457', false).map(r => r.id), [12346]);
    assert.deepEqual(importRows(view, 'SAME NAME', true).map(r => r.id), [12345]);
    assert.deepEqual(importRows(view, '12345', false).map(r => r.id), [12345]);
    assert.equal(view.rows.length, 2);
});

test('a disabled import explains pending edits or a full draft even when the event itself is supported', () => {
    assert.equal(importMessage({...view, blocked: 'Bitte offene Formulareingaben übernehmen.'}, true), 'Bitte offene Formulareingaben übernehmen.');
    assert.equal(importMessage({...view, blocked: 'Bereits 100 Automationen.'}, true), 'Bereits 100 Automationen.');
    assert.equal(importMessage(view, true), view.selected.assessment);
});
test('foreign, malformed and oversized responses fail without accepting another system or retrying a POST', async () => {
    for (const response of [{...view, systemId: 'foreign'}, {...view, generation: 3}, {...view, rows: Array(257).fill(view.rows[0])}, {...view, selected: {...view.selected, token: 'wrong'}}]) {
        await assert.rejects(importRequest(data, {action: 'scan'}, {base: 'https://example.invalid/', request: async () => ({ok: true, text: async () => JSON.stringify({ok: true, view: response})})}));
    }
    let calls = 0;
    await assert.rejects(importRequest(data, {action: 'scan'}, {base: 'https://example.invalid/', request: async () => { calls++; throw Error('private transport'); }}), /nicht erneut gesendet/);
    assert.equal(calls, 1);
    await assert.rejects(importRequest(data, {action: 'scan'}, {base: 'https://example.invalid/', request: async () => ({ok: true, text: async () => 'x'.repeat(524289)})}));
});
test('a rejected import preserves the explicit reason and refreshed preview without treating it as a committed rule', async () => {
    const reply = {...view, selected: {...view.selected, canImport: false, token: ''}};
    await assert.rejects(importRequest(data, {action: 'import'}, {base: 'https://example.invalid/', request: async () => ({ok: false, text: async () => JSON.stringify({ok: false, message: 'Vorschau geändert', view: reply})})}), error => error.message === 'Vorschau geändert' && !error.view.selected.canImport);
});
