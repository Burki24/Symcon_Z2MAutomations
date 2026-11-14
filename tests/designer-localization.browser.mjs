/* Offline browser acceptance against synthetic loopback fixtures; never connects to Symcon or MQTT. */
import assert from 'node:assert/strict';
import {createServer} from 'node:http';
import {readFileSync} from 'node:fs';
import {pathToFileURL} from 'node:url';
import {assertNoBrowserCapture} from './browser-capture-policy.mjs';
assertNoBrowserCapture(readFileSync(new URL(import.meta.url), 'utf8'));
const {chromium} = await import(process.env.Z2MA_PLAYWRIGHT_MODULE ? pathToFileURL(process.env.Z2MA_PLAYWRIGHT_MODULE).href : 'playwright');
const catalogs = JSON.parse(readFileSync(new URL('../Z2MAutomations/visualization/locales/en.json', import.meta.url), 'utf8'));
const german = JSON.parse(readFileSync(new URL('../Z2MAutomations/visualization/locales/de.json', import.meta.url), 'utf8'));
const assets = new URL('../Z2MAutomations/visualization/', import.meta.url);
const audits = []; const writes = []; const exports = []; const errors = [];
const token = 'a'.repeat(64);
const rule = {id: 'rule_a', name: 'Eigener Name – ON', enabled: false, retrigger: 'extend',
    triggers: [{type: 'device', device: 'lamp', property: 'state', operator: 'eq', value: 'ON'}],
    conditions: [{type: 'weekday', weekdays: [1, 4], timezone: 'UTC'}],
    actions: [{type: 'set', target: {type: 'device', id: 'lamp'}, values: {state: 'ON'}, delay_seconds: 5},
        {type: 'set', target: {type: 'device', id: 'lamp'}, values: {state: 'OFF'}, delay_seconds: 0}]};
const rules = new Map([['a', structuredClone(rule)], ['b', structuredClone(rule)]]);
const createdRules = new Map([['a', []], ['b', []]]);
const sequence = {id: 'test_steps', name: 'Test action sequence', actions: [rule.actions[0]]};
const systems = ['a', 'b'].map((name, index) => ({name: 'Eigenes System ' + name, binding: {systemId: name, generation: 1, mqttParent: index + 10001, baseTopic: name === 'a' ? 'zigbee2mqtt' : 'Z2M-2'}}));
const interaction = readFileSync(new URL('../libs/helper/visualization/interaction.min.js', import.meta.url), 'utf8');
let createContext = 'c'.repeat(64);
let overflowRules = false; let rejectReview = false;
let catalogAvailable = true; let storedCatalog = true; let catalogFingerprint = 'e'.repeat(64);
let nativeFormEdits = false; let rejectViewRead = false;
const operations = [];
const transferViews = new Map(); const runtimeViews = new Map();
const draftSyncViews = new Map(); const draftSyncBackups = new Map(); let draftSyncMode = 'updated';
function draftSyncView(id) {
    return draftSyncViews.get(id) ?? {systemId: id, generation: 1, fingerprint: catalogFingerprint, operation: '', phase: 'idle',
        pending: false, canStart: true, canReplace: false, canRestore: false, baseRevision: 4, remoteRevision: null,
        counts: {added: 0, changed: 0, removed: 0}, permissionsChanged: false, error: ''};
}
function finishDraftSync(id) {
    Object.assign(rules.get(id), {...draftSyncBackups.get(id), name: 'Fresh Z2M automation ' + id});
    draftSyncViews.set(id, {...draftSyncView(id), phase: 'updated', pending: false, canStart: true, canReplace: false, canRestore: true});
}
function transferView(id) {
    return transferViews.get(id) ?? {message: 'Synthetic transfer', details: '', pending: false, receiverConfirmed: true, storedRevision: 4,
        canValidate: true, canStore: false, context: '1'.repeat(64), status: 'idle', systemId: id, generation: 1, fingerprint: 'e'.repeat(64)};
}
function runtimeView(id) {
    return runtimeViews.get(id) ?? {message: 'Synthetic runtime', pending: false, confirmed: true, executing: true, resumeAfterRestart: true,
        canRefresh: true, canActivate: false, canStop: true, context: '2'.repeat(64), status: 'idle', systemId: id, generation: 1, fingerprint: 'e'.repeat(64), rules: {}};
}
const setupView = () => ({ok: true, fingerprint: 'd'.repeat(64), candidates: '3'.repeat(64), choices: systems.map((system, index) =>
    ({Connection: index + 10010, Use: true, editable: false, Name: system.name, MQTT: String(system.binding.mqttParent), Topic: system.binding.baseTopic, Status: 'Synthetic ready'}))});
function model(id = 'a') {
    return {contractVersion: 1, mode: 'read', module: {id: 1, name: 'Synthetic centre', baseTopic: id === 'a' ? 'zigbee2mqtt' : 'Z2M-2', gateway: 2},
        readAt: 1800000000, catalogAvailable, catalogLoadedAt: catalogAvailable ? 1800000000 : null, draftAvailable: true, draftMismatch: false, openFormEdits: nativeFormEdits,
        configuration: {schema_version: 1, revision: 4 + createdRules.get(id).length, mesh_peers: ['kept-peer'],
            automations: [rules.get(id), ...createdRules.get(id), ...(overflowRules ? Array.from({length: 30}, (_, i) => ({...rule, id: 'overflow_' + i, name: 'Synthetic automation ' + i})) : [])], sequences: [sequence]},
        devices: [{key: 'lamp', name: 'Eigener Gerätename – Ein', target: {type: 'device', id: 'lamp'},
            properties: [{id: 'state', label: 'Eigener Kanal', type: 'binary', readable: true, writable: true, values: ['ON', 'OFF'], actionValues: ['ON', 'OFF', 'TOGGLE'], valueLabels: [], unit: ''}]}],
        status: {confirmed: true, mqttConnected: true, executing: true, activeRevision: 4, storedRevision: 4},
        timezones: ['UTC', 'Europe/Berlin'], createContext, createAllowed: true, editAllowed: true, manageAllowed: true, sequenceAllowed: true,
        sequenceCreateAllowed: true, createNotice: '', editNotice: '', sequenceNotice: '', sequenceSections: {},
        editSections: {rule_a: {triggers: true, conditions: true, actions: true}},
        workspace: {fingerprint: 'd'.repeat(64), systems}, workspaceContext: {systemId: id, generation: 1, fingerprint: catalogFingerprint},
        catalogRefresh: {systemId: id, generation: 1, fingerprint: catalogFingerprint, operation: '', phase: catalogAvailable ? 'loaded' : 'idle',
            pending: false, canRefresh: true, message: 'Synthetic catalogue', loadedAt: catalogAvailable ? 1800000000 : null,
            storedAt: storedCatalog ? 1800000000 : null, counts: {devices: catalogAvailable ? 1 : 0, groups: 0, scenes: 0}},
        draftSyncAllowed: true, draftSync: draftSyncView(id), transfer: transferView(id), runtime: runtimeView(id)};
}
let active = 'a';
const server = createServer(async (request, response) => {
    const route = new URL(request.url, 'http://localhost').pathname.slice(1);
    response.setHeader('Cache-Control', 'no-store');
    if (request.method === 'POST') {
        const parts = []; for await (const part of request) parts.push(part);
        const body = JSON.parse(Buffer.concat(parts).toString('utf8'));
        response.setHeader('Content-Type', 'application/json');
        if (route === 'language') audits.push(body);
        else if (route === 'select-system') active = body.systemId;
        else if (route === 'workspace-configure') {
            operations.push({route, body}); response.end(JSON.stringify(setupView())); return;
        }
        else if (route === 'draft-sync') {
            const id = request.headers['x-z2ma-system-id']; operations.push({route, systemId: id, body});
            if (body.action === 'restore') {
                Object.assign(rules.get(id), draftSyncBackups.get(id));
                draftSyncViews.set(id, {...draftSyncView(id), phase: 'restored', canRestore: false});
            } else if (body.action === 'replace') finishDraftSync(id);
            else {
                draftSyncBackups.set(id, structuredClone(rules.get(id)));
                draftSyncViews.set(id, {...draftSyncView(id), operation: body.requestId.replaceAll('-', ''), remoteRevision: 74,
                    phase: draftSyncMode === 'pending' ? 'reading' : draftSyncMode, pending: draftSyncMode === 'pending',
                    canStart: draftSyncMode !== 'pending', canReplace: draftSyncMode === 'conflict', canRestore: false,
                    counts: {added: 0, changed: 1, removed: 0}});
                if (draftSyncMode === 'updated') finishDraftSync(id);
            }
            response.end(JSON.stringify({ok: true, draftSync: draftSyncView(id)})); return;
        }
        else if (route === 'catalog-refresh') {
            const id = request.headers['x-z2ma-system-id']; operations.push({route, systemId: id, body});
            catalogAvailable = true; storedCatalog = true; catalogFingerprint = '4'.repeat(64);
            response.end(JSON.stringify({ok: true, catalogRefresh: model(id).catalogRefresh})); return;
        }
        else if (route === 'runtime') {
            const id = request.headers['x-z2ma-system-id']; operations.push({route, systemId: id, body});
            if (body.action === 'deactivate') runtimeViews.set(id, {...runtimeView(id), executing: false, resumeAfterRestart: false, status: 'stopped'});
            response.end(JSON.stringify({ok: true, runtime: runtimeView(id)})); return;
        }
        else if (route === 'transfer') {
            const id = request.headers['x-z2ma-system-id']; operations.push({route, systemId: id, body});
            transferViews.set(id, {...transferView(id), canStore: body.action === 'validate', status: body.action === 'validate' ? 'validated' : 'stored',
                message: body.action === 'validate' ? 'Synthetic validated' : 'Synthetic stored', storedRevision: body.action === 'store' ? model(id).configuration.revision : 4});
            if (body.action === 'validate' && rejectReview) transferViews.set(id, {...transferView(id), canStore: false, status: 'error', message: 'Synthetic rejected',
                findings: [{path: '/automations/0/actions/0/values/state', reason: 'capability_enum', capability: {system: id, device: 'Synthetic lamp', label: 'State', property: 'state', actual: 'INVALID', actualType: 'string', allowed: ['ON', 'OFF']}}]});
            response.end(JSON.stringify({ok: true, transfer: transferView(id)})); return;
        }
        else if (route === 'create-yaml') {
            const systemId = request.headers['x-z2ma-system-id'];
            exports.push({route, systemId});
            const candidate = structuredClone(model(systemId).configuration);
            candidate.revision++;
            candidate.automations.push({...body.rule, id: 'exported_' + systemId, enabled: false});
            response.end(JSON.stringify({yaml: JSON.stringify(candidate, null, 2) + '\n'})); return;
        }
        else if (route === 'create') {
            writes.push({route, body, systemId: request.headers['x-z2ma-system-id']});
            if (body.context !== createContext) {response.writeHead(409); response.end(JSON.stringify({ok: false})); return;}
            const id = 'created_' + active;
            createdRules.get(active).push({...body.rule, id, enabled: false});
            response.end(JSON.stringify({ok: true, ruleId: id})); return;
        }
        else { writes.push({route, body, systemId: request.headers['x-z2ma-system-id']}); if (route === 'edit') Object.assign(rules.get(active), structuredClone(body.changes)); }
        response.end(JSON.stringify({ok: true, ruleId: 'rule_a'})); return;
    }
    if (route === 'data') {response.setHeader('Content-Type', 'application/json'); if (rejectViewRead) {response.writeHead(503); response.end('{}'); return;} response.end(JSON.stringify(model(active))); return;}
    if (route === 'workspace-status') {response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify(setupView())); return;}
    if (route === 'draft-sync-status') {
        const id = request.headers['x-z2ma-system-id'];
        if (draftSyncView(id).pending) finishDraftSync(id);
        response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({ok: true, draftSync: draftSyncView(id)})); return;
    }
    if (!/^[a-z-]+\.(?:js|html|css)$/.test(route)) {response.writeHead(404); response.end(); return;}
    try {
        let body = route === 'interaction.js' ? interaction : readFileSync(new URL(route, assets), 'utf8');
        if (route === 'index.html') body = body.replace('type="module" src="designer.js"', 'defer src="startup.js"').replace("connect-src 'none'", "connect-src 'self'");
        if (route === 'tile.html') {
            body = body.replace('/* TILE_STYLE */', readFileSync(new URL('tile.css', assets), 'utf8'))
                .replace('/* TILE_BOOTSTRAP */', JSON.stringify({path: '/index.html', objectId: 10000, canMaximize: false}))
                .replace('/* TILE_INTERACTION */', () => interaction)
                .replace('/* TILE_SCRIPT */', () => readFileSync(new URL('tile.js', assets), 'utf8'))
                .replace('/* TILE_ORIGIN */', `window.SymconHTTPOrigin = {resolve: () => location.origin};
                    window.requestAction = (_, json) => {
                        const state = {selectedSystem: ${JSON.stringify(active)}, systems: [{systemId: ${JSON.stringify(active)}, name: 'Test system', status: 'Ready'}]};
                        const access = {systemId: state.selectedSystem, path: '/index.html', token: '${token}', expires: Math.floor(Date.now() / 1000) + 60};
                        queueMicrotask(() => window.handleMessage(JSON.stringify({...JSON.parse(json), type: 'z2ma-tile', version: 1, ok: true, state, access})));
                    };`);
        }
        response.setHeader('Content-Type', route.endsWith('.js') ? 'application/javascript' : route.endsWith('.css') ? 'text/css' : 'text/html'); response.end(body);
    } catch {response.writeHead(404); response.end();}
});
const browser = await chromium.launch({headless: true, ...(process.env.Z2MA_BROWSER_CHANNEL ? {channel: process.env.Z2MA_BROWSER_CHANNEL} : {})});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
async function showStep(view, key) {
    const button = view.locator('#desktop-nav-' + key);
    if (await button.isVisible()) await button.click(); else await view.locator('#desktop-step').selectOption(key);
    await view.locator('#desktop-view-' + key).waitFor({state: 'visible'});
}
async function verifyMissingCatalogNavigation(page, view, language, hasStored) {
    catalogAvailable = false; storedCatalog = hasStored; catalogFingerprint = 'e'.repeat(64);
    await page.reload();
    const issues = view.locator('#issues'); await issues.waitFor({state: 'visible'});
    await view.locator('#designer-language').selectOption(language);
    assert.match(await issues.innerText(), language === 'de' ? /Schritt 4.*Verbinden und Bestand lesen.*Gerätedaten aktualisieren/ : /step 4.*Connect and read inventory.*Refresh device data/);
    assert.doesNotMatch(await issues.innerText(), /Oben|at the top/);
    const before = structuredClone(model(active).configuration); const beforeWrites = writes.length; const beforeOperations = operations.length;
    await view.locator('#validate-button').click();
    await view.locator('#validate-button:not(:disabled)').waitFor();
    assert.equal(catalogAvailable, false, 'refreshing the module view never starts a device read');
    await showStep(view, 'automations');
    const shortcut = view.locator('#symcon-open-device-refresh');
    assert.equal(await shortcut.evaluate(button => button.closest('#issues') !== null), true, 'the next action belongs inside the device data warning');
    assert.equal(await shortcut.getAttribute('aria-describedby'), 'issues-message', 'keyboard navigation retains the warning context');
    assert.equal(await issues.locator('#issues-message').getAttribute('tabindex'), '0', 'long warning text remains keyboard accessible');
    const placement = await issues.evaluate(panel => {
        const message = panel.querySelector('#issues-message').getBoundingClientRect();
        const button = panel.querySelector('#symcon-open-device-refresh').getBoundingClientRect();
        const heading = document.querySelector('#desktop-view-automations-title').getBoundingClientRect();
        return {messageLeft: message.left, messageBottom: message.bottom, buttonLeft: button.left, buttonTop: button.top,
            buttonRight: button.right, panelBottom: panel.getBoundingClientRect().bottom, headingTop: heading.top, width: innerWidth};
    });
    assert.ok(Math.abs(placement.buttonLeft - placement.messageLeft) < 2, 'the action follows the warning at its leading edge');
    assert.ok(placement.buttonTop >= placement.messageBottom && placement.buttonTop - placement.messageBottom <= 20, 'the action is immediately below the warning text');
    assert.ok(placement.buttonRight <= placement.width, 'the action fits the browser or tile width');
    assert.ok(placement.panelBottom <= placement.headingTop + 1, 'the warning and its action precede the work area');
    const originalScheme = await page.evaluate(() => matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    for (const colorScheme of ['light', 'dark']) {
        await page.emulateMedia({colorScheme});
        const colors = await shortcut.evaluate(button => {
            const style = getComputedStyle(button);
            return {background: style.backgroundColor, text: style.color, height: button.getBoundingClientRect().height};
        });
        assert.notEqual(colors.background, 'rgba(0, 0, 0, 0)', 'the next action has a filled background in ' + colorScheme);
        assert.notEqual(colors.text, colors.background, 'the next action has distinct text in ' + colorScheme);
        assert.ok(colors.height >= 40, 'the next action remains a full-size button');
    }
    await page.emulateMedia({colorScheme: originalScheme});
    await issues.locator('#issues-message').focus();
    await page.keyboard.press('Tab');
    assert.equal(await shortcut.evaluate(button => button.ownerDocument.activeElement === button), true, 'Tab reaches the next action from the warning text');
    if (language === 'de') await page.keyboard.press('Enter'); else await shortcut.click();
    await view.locator('#desktop-view-connection').waitFor({state: 'visible'});
    const refresh = view.locator('.system-catalog button').first();
    assert.equal(await refresh.innerText(), language === 'de' ? 'Gerätedaten aktualisieren' : 'Refresh device data');
    assert.equal(await issues.isVisible(), false, 'step 4 uses its own device data card instead of a navigation warning');
    assert.equal(await shortcut.isVisible(), false, 'step 4 never links to itself');
    assert.equal(await refresh.evaluate(button => button.classList.contains('primary')), true, 'the explicit device read is highlighted in step 4');
    assert.equal(catalogAvailable, false, 'hiding the navigation warning does not confirm the catalog');
    for (const step of ['project', 'broker', 'system', 'draft', 'automations', 'review', 'transfer', 'operation']) {
        await showStep(view, step);
        assert.equal(await issues.isVisible(), true, 'the device data warning remains available in ' + step);
        assert.equal(await shortcut.isVisible(), true, 'the direct device data link remains available in ' + step);
    }
    await showStep(view, 'connection');
    assert.equal(await issues.isVisible(), false, 'manual navigation back to step 4 also hides the redundant warning');
    const otherLanguage = language === 'de' ? 'en' : 'de';
    await view.locator('#designer-language').selectOption(otherLanguage);
    assert.equal(await issues.isVisible(), false, 'a language change cannot restore the redundant navigation warning');
    await view.locator('#designer-language').selectOption(language);
    nativeFormEdits = true;
    await view.locator('#validate-button').click();
    await issues.waitFor({state: 'visible'});
    assert.doesNotMatch(await issues.innerText(), /Schritt 4|step 4/i, 'other form warnings remain visible without device navigation instructions');
    assert.equal(await shortcut.isVisible(), false, 'other warnings do not restore a link to the current step');
    nativeFormEdits = false;
    await view.locator('#validate-button:not(:disabled)').click();
    await issues.waitFor({state: 'hidden'});
    rejectViewRead = true;
    await view.locator('#validate-button:not(:disabled)').click();
    await issues.waitFor({state: 'visible'});
    const readError = (language === 'de' ? german : catalogs)['ui.module.data.could.not.be.80c47bf5'];
    assert.equal(await view.locator('#issues-message').innerText(), readError, 'failed view reads remain visible in step 4');
    await showStep(view, 'automations');
    await showStep(view, 'connection');
    assert.equal(await view.locator('#issues-message').innerText(), readError, 'navigation preserves the read failure');
    rejectViewRead = false;
    await view.locator('#validate-button:not(:disabled)').click();
    await issues.waitFor({state: 'hidden'});
    assert.equal(catalogAvailable, false, 'view recovery is not a device read');
    assert.equal(operations.length, beforeOperations, 'the shortcut only navigates and sends no MQTT request');
    await refresh.click();
    await issues.waitFor({state: 'hidden'});
    assert.equal(await view.locator('#symcon-open-device-refresh').isVisible(), false);
    assert.deepEqual(operations.slice(beforeOperations).map(row => row.route), ['catalog-refresh']);
    assert.equal(writes.length, beforeWrites, 'reading device data performs no draft write');
    assert.deepEqual(model(active).configuration, before, 'the local automation draft is preserved');
}
async function verifyDraftSynchronization(page, view, language) {
    active = 'a'; draftSyncMode = 'updated'; draftSyncViews.clear();
    await page.reload();
    await view.locator('#symcon-refresh-draft:not([disabled])').waitFor();
    await view.locator('#designer-language').selectOption(language);
    await showStep(view, 'automations');
    const refresh = view.locator('#symcon-refresh-draft');
    const feedback = view.locator('#symcon-draft-sync-status');
    const confirmation = view.locator('.guided-confirmation');
    const beforeOperations = operations.length; const before = structuredClone(rules.get('a')); const other = structuredClone(rules.get('b'));
    assert.equal(await refresh.innerText(), language === 'de' ? 'Aus Z2M aktualisieren' : 'Update from Z2M');
    await refresh.click();
    await view.getByRole('heading', {name: 'Fresh Z2M automation a', exact: true}).waitFor();
    assert.match(await feedback.innerText(), language === 'de' ? /aus Z2M aktualisiert/ : /updated from Z2M/);
    assert.equal(await feedback.getAttribute('data-tone'), 'success');
    assert.ok(await feedback.evaluate(node => getComputedStyle(node).color !== getComputedStyle(node.parentElement).color), 'confirmed feedback uses the semantic success color');
    assert.ok(await view.locator('#symcon-draft-sync').evaluate(node => node.scrollWidth <= node.clientWidth + 1), 'draft feedback fits the browser and narrow tile');
    assert.deepEqual(rules.get('b'), other);
    assert.deepEqual(operations.slice(beforeOperations).map(row => [row.route, row.body.action, row.systemId]), [['draft-sync', 'refresh', 'a']]);
    const restore = view.locator('#symcon-restore-draft');
    await restore.click();
    assert.ok(await confirmation.locator('button').first().evaluate(node => node === node.ownerDocument.activeElement), 'restoring has cancellation as initial focus');
    await confirmation.press('Escape'); await confirmation.waitFor({state: 'detached'});
    assert.equal(operations.length, beforeOperations + 1, 'cancelling restore sends nothing');
    await restore.click(); await confirmation.locator('button.primary').click();
    await view.getByRole('heading', {name: before.name, exact: true}).waitFor();
    assert.deepEqual(rules.get('a'), before);
    draftSyncMode = 'conflict'; await refresh.click();
    const replace = view.locator('#symcon-replace-draft'); await replace.waitFor({state: 'visible'});
    assert.deepEqual(rules.get('a'), before, 'a conflict keeps the current local draft');
    assert.match(await feedback.innerText(), language === 'de' ? /abweichende Änderungen/ : /diverged/);
    await replace.click(); await confirmation.press('Escape'); await confirmation.waitFor({state: 'detached'});
    assert.deepEqual(rules.get('a'), before);
    await replace.click(); await confirmation.locator('button.primary').click();
    await view.getByRole('heading', {name: 'Fresh Z2M automation a', exact: true}).waitFor();
    await view.locator('#edit-automation-button').click();
    const dialog = view.locator('#edit-dialog'); await dialog.waitFor({state: 'visible'});
    const field = dialog.locator('input[type=text]').first(); await field.fill('Unsaved draft sync input');
    const requestsBeforeOpenInput = operations.length;
    await refresh.evaluate(node => node.click());
    assert.equal(operations.length, requestsBeforeOpenInput, 'an open edit dialog cannot start synchronization');
    assert.equal(await field.inputValue(), 'Unsaved draft sync input');
    await view.locator('#edit-cancel').click();
    draftSyncMode = 'pending'; await refresh.click();
    await feedback.getByText(language === 'de' ? /aus Z2M aktualisiert/ : /updated from Z2M/).waitFor();
    assert.deepEqual(rules.get('b'), other, 'polling never mixes system state');
    assert.ok(operations.slice(beforeOperations).every(row => row.route === 'draft-sync'), 'the button cannot transfer, activate, stop, install or issue device commands');
}
async function verifyWorkspaceWheel(page) {
    overflowRules = true;
    await page.reload();
    await page.locator('#entry-list .entry').nth(25).waitFor({state: 'attached'});
    const list = page.locator('#entry-list');
    assert.ok(await list.evaluate(node => node.scrollHeight > node.clientHeight && node.clientHeight > 80));
    assert.ok(await page.evaluate(() => document.documentElement.scrollHeight <= innerHeight + 1), 'the desktop workspace fits the window');
    const outer = await page.locator('#desktop-view-automations').evaluate(node => node.scrollTop);
    const box = await list.boundingBox();
    await page.mouse.move(box.x + box.width / 2, box.y + Math.min(box.height / 2, 100));
    for (const delta of [240, -240]) {
        const before = await list.evaluate(node => node.scrollTop);
        await page.mouse.wheel(0, delta);
        await page.waitForFunction(({before, delta}) => (document.querySelector('#entry-list').scrollTop - before) * delta > 0, {before, delta});
    }
    assert.equal(await page.locator('#desktop-view-automations').evaluate(node => node.scrollTop), outer, 'the wheel stays inside the automation list');
    overflowRules = false; await page.reload();
    await page.locator('#edit-automation-button:not(:disabled)').waitFor();
}
async function verifyDraftForm(page, view, system, refresh = false) {
    const start = view.locator('#draft-wizard-start:not(:disabled)');
    await start.waitFor({state: 'attached'});
    const navigation = view.locator('.navigation .designer-fold');
    if (await navigation.getAttribute('open') === null) await navigation.locator('summary').click();
    assert.equal(await view.locator('#designer-system option:checked').innerText(), 'Eigenes System ' + system + ' · ' + systems.find(row => row.binding.systemId === system).binding.baseTopic);
    assert.match(await view.locator('.desktop-source').innerText(), /Revision 4/);
    assert.match(await view.locator('#symcon-stored-status').innerText(), /Revision 4/);
    await showStep(view, 'system');
    await view.locator('#symcon-system-choices input').first().waitFor();
    assert.equal(await view.locator('#symcon-system-choices input:checked').count(), 2);
    await showStep(view, 'automations');
    await start.click();
    const dialog = view.locator('#edit-dialog');
    const name = 'Form test ' + system;
    const nameInput = dialog.locator('#edit-fields input[type=text]').first();
    const submit = dialog.locator('#edit-submit');
    assert.match(await dialog.innerText(), new RegExp('Eigenes System ' + system));
    assert.equal(await dialog.locator('[data-section=conditions]').getAttribute('open'), null);
    assert.equal(await dialog.locator('#edit-next').count(), 0);
    assert.ok(await dialog.evaluate(node => node.scrollWidth <= node.clientWidth + 1), 'the form fits its available width');
    assert.equal(await dialog.locator('#edit-export').isVisible(), false, 'YAML export is kept in step 5');
    assert.equal(await dialog.locator('[data-language-picker]').isVisible(), false, 'language is selected in the central header');
    assert.ok(await submit.evaluate(node => {
        const button = node.getBoundingClientRect();
        const dialog = node.closest('dialog').getBoundingClientRect();
        return button.top >= dialog.top && button.bottom <= Math.min(dialog.bottom, innerHeight);
    }), 'the save action stays visible at the start of the form');
    if (view !== page) {
        const box = await dialog.boundingBox();
        await page.mouse.move(box.x + 20, box.y + 80);
        for (const delta of [240, -240]) {
            const top = await dialog.locator('#edit-content').evaluate(node => {
                delete node.dataset.testWheel;
                node.addEventListener('scrollend', () => { node.dataset.testWheel = 'scrolled'; }, {once: true});
                return node.scrollTop;
            });
            await page.mouse.wheel(0, delta);
            await view.locator('#edit-content[data-test-wheel=scrolled]').waitFor();
            assert.ok(await dialog.locator('#edit-content').evaluate((node, before) => node.scrollTop - before, top) * delta > 0, 'the tile editor scrolls in the wheel direction');
        }
    }
    await submit.click();
    assert.equal(await nameInput.getAttribute('aria-invalid'), 'true');
    assert.ok(await nameInput.evaluate(node => node === node.ownerDocument.activeElement));
    await nameInput.fill(name);
    const conditions = dialog.locator('[data-section=conditions]');
    await conditions.locator('summary').click();
    await conditions.getByRole('button', {name: 'Bedingung hinzufügen', exact: true}).click();
    const conditionKind = conditions.locator('fieldset select').first();
    assert.equal(await conditionKind.inputValue(), 'status');
    assert.ok(await conditionKind.evaluate(node => node === node.ownerDocument.activeElement));
    await conditions.locator('fieldset .edit-row-tools button').click();
    await conditions.locator('summary').click();
    await dialog.locator('[data-section=triggers] fieldset select').first().selectOption('time');
    const action = dialog.locator('[data-section=actions] fieldset select');
    await action.first().selectOption('sequence');
    await action.nth(1).selectOption(sequence.id);
    if (refresh) {
        createContext = 'f'.repeat(64);
        await submit.click();
        await dialog.locator('#edit-reload').click();
        await dialog.locator('#edit-submit:not(:disabled)').waitFor();
        assert.equal(await nameInput.inputValue(), name);
    }
    const before = model(system).configuration;
    assert.ok(await dialog.isVisible());
    assert.equal(await nameInput.inputValue(), name);
    await submit.click();
    await dialog.waitFor({state: 'hidden'});
    await view.getByRole('heading', {name, exact: true}).waitFor();
    assert.equal(createdRules.get(system).length, 1);
    assert.equal(createdRules.get(system)[0].enabled, false);
    assert.match(await view.locator('.desktop-source').innerText(), /Revision 5/);
    assert.match(await view.locator('#symcon-stored-status').innerText(), /Revision 4/);
    await showStep(view, 'draft');
    await view.locator('#symcon-export-draft').waitFor({state: 'visible'});
    const [download] = await Promise.all([page.waitForEvent('download'), view.locator('#symcon-export-draft').click()]);
    assert.equal(download.suggestedFilename(), 'automations.yaml');
    const candidate = JSON.parse(readFileSync(await download.path(), 'utf8'));
    assert.deepEqual(candidate.automations[0], before.automations[0]);
    assert.deepEqual(candidate.mesh_peers, before.mesh_peers);
    assert.deepEqual(candidate.sequences, before.sequences);
    assert.equal(candidate.automations[1].name, name);
    assert.equal(candidate.automations[1].enabled, false);
    assert.equal(candidate.automations[1].actions[0].sequence_id, sequence.id);
    await showStep(view, 'review');
    await view.locator('button[data-transfer-action=validate]').click();
    await view.locator('.desktop-review').getByText('Synthetic validated', {exact: true}).waitFor();
    await showStep(view, 'transfer');
    const beforeOperations = operations.length;
    await view.locator('button[data-transfer-action=store]').click();
    const confirmation = view.locator('.guided-confirmation');
    await confirmation.waitFor({state: 'visible'});
    assert.ok(await confirmation.locator('button').first().evaluate(node => node === node.ownerDocument.activeElement), 'cancel has the safe initial focus');
    await confirmation.press('Escape');
    await confirmation.waitFor({state: 'detached'});
    assert.equal(operations.length, beforeOperations, 'cancelled transmission sends nothing');
    await view.locator('button[data-transfer-action=store]').click();
    await confirmation.locator('button.primary').click();
    await view.locator('.system-transfer:not(.system-runtime)').getByText('Synthetic stored', {exact: true}).waitFor();
    assert.deepEqual(operations.slice(beforeOperations).map(row => [row.route, row.body.action]), [['runtime', 'status'], ['runtime', 'deactivate'], ['transfer', 'validate'], ['transfer', 'store']]);
    assert.ok(operations.slice(beforeOperations).every(row => row.systemId === system), 'all operations address the explicitly selected system');
}
try {
    const context = await browser.newContext({locale: 'en-GB', viewport: {width: 1440, height: 1000}});
    const page = await context.newPage(); page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://127.0.0.1:' + server.address().port + '/index.html#' + token);
    await page.waitForSelector('#edit-automation-button:not([disabled])');
    assert.equal(await page.locator('html').getAttribute('lang'), 'en');
    await page.locator('#edit-automation-button').click();
    const dialog = page.locator('#edit-dialog'); await dialog.waitFor({state: 'visible'});
    await dialog.locator('[data-section=settings] summary').click();
    await dialog.getByLabel('After a Zigbee2MQTT restart', {exact: true}).selectOption('resume_expired');
    const name = dialog.locator('input[type=text]').first(); await name.fill('Unsaved user input – Ein ON');
    await dialog.locator('[data-action-index="0"] input[type=number]').first().fill('9');
    await dialog.locator('[data-action-index="1"]').getByRole('button', {name: 'Move up', exact: true}).click();
    const search = dialog.locator('input[type=search]').first(); await search.fill('Eigener');
    await search.press('Enter');
    assert.ok(await dialog.locator('.edit-device-picker select').first().evaluate(node => node === node.ownerDocument.activeElement));
    assert.deepEqual(writes, [], 'Enter in a device search selects the results control without submitting');
    const selects = await dialog.locator('select:not([data-language-select])').evaluateAll(nodes => nodes.map(n => n.value));
    const checks = await dialog.locator('input[type=checkbox]').evaluateAll(nodes => nodes.map(n => n.checked));
    await name.evaluate(node => {window.__nameField = node;});
    const box = await dialog.boundingBox();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    const beforeWheel = await dialog.locator('#edit-content').evaluate(node => {
        window.__dialogScroll = null;
        node.addEventListener('wheel', () => node.addEventListener('scrollend', () => {
            window.__dialogScroll = node.scrollTop;
        }, {once: true}), {once: true});
        return node.scrollTop;
    });
    await page.mouse.wheel(0, 240);
    await page.waitForFunction(() => window.__dialogScroll !== null);
    assert.ok(await page.evaluate(before => window.__dialogScroll > before, beforeWheel), 'the open editor accepts real mouse-wheel input');
    await dialog.evaluate((root, {english, german}) => {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT); const rows = []; let node;
        while ((node = walker.nextNode())) {
            const key = Object.keys(english).find(key => english[key] === node.data.trim() && !key.startsWith('server.') && english[key] !== german[key]);
            if (key) rows.push({node, expected: german[key], original: node.data.trim()});
        }
        window.__languageRows = rows;
    }, {english: catalogs, german});
    await page.evaluate(async () => (await import('./localization.js')).setLanguage('de'));
    assert.deepEqual(await page.evaluate(() => window.__languageRows.filter(row => row.node.isConnected && row.node.data.trim() !== row.expected).map(row => ({before: row.original, after: row.node.data.trim(), expected: row.expected}))), [], 'every bound editor caption changes language');
    assert.equal(await name.inputValue(), 'Unsaved user input – Ein ON');
    assert.equal(await search.inputValue(), 'Eigener');
    assert.ok(await name.evaluate(node => window.__nameField === node), 'language changes retain the original input node and listeners');
    assert.deepEqual(await dialog.locator('input[type=checkbox]').evaluateAll(nodes => nodes.map(n => n.checked)), checks);
    assert.equal(await dialog.locator('input[type=text]').first().inputValue(), 'Unsaved user input – Ein ON');
    assert.match(await dialog.innerText(), /Name der Automation/);
    assert.equal(await dialog.getByLabel('Nach einem Zigbee2MQTT-Neustart', {exact: true}).inputValue(), 'resume_expired');
    await page.evaluate(async () => (await import('./localization.js')).setLanguage('en'));
    assert.match(await dialog.innerText(), /Automation name/);
    assert.equal(await name.inputValue(), 'Unsaved user input – Ein ON');
    assert.equal(await search.inputValue(), 'Eigener');
    const currentSelects = await dialog.locator('select:not([data-language-select])').evaluateAll(nodes => nodes.map(n => n.value));
    assert.deepEqual(currentSelects, selects, 'typed channel/value selections stay unchanged');
    assert.equal(await dialog.locator('#edit-content').evaluate(node => node.scrollTop), await page.evaluate(() => window.__dialogScroll));
    await dialog.locator('#edit-submit').click();
    await dialog.waitFor({state: 'hidden'});
    assert.equal(writes.length, 1);
    assert.equal(writes[0].route, 'edit', 'only the synthetic local draft endpoint is used');
    assert.equal(writes[0].body.changes.name, 'Unsaved user input – Ein ON');
    assert.equal(writes[0].body.changes.restart_policy, 'resume_expired');
    assert.deepEqual(writes[0].body.changes.actions.map(action => [action.values.state, action.delay_seconds]), [['OFF', 0], ['ON', 9]], 'language changes retain order, typed values and edited delay');
    assert.equal(rules.get('a').enabled, false);
    assert.equal(rules.get('b').name, rule.name, 'the other system draft is untouched');
    await page.locator('#designer-system').selectOption('b');
    await page.waitForFunction(() => document.querySelector('#designer-system').value === 'b' && document.querySelector('#entry-title').textContent.includes('Eigener Name'));
    await page.locator('#designer-language').selectOption('de');
    await page.reload();
    await page.waitForSelector('#edit-automation-button:not([disabled])');
    assert.equal(await page.locator('html').getAttribute('lang'), 'de', 'explicit preference survives reload');
    assert.match(await page.locator('#entry-title').innerText(), /Eigener Name – ON/);
    for (const colorScheme of ['light', 'dark']) {
        await page.emulateMedia({colorScheme});
        for (const width of [360, 1920, 3840]) {
            await page.setViewportSize({width, height: 1000});
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'no horizontal page overflow at ' + width + '/' + colorScheme);
        }
    }
    await page.waitForTimeout(100);
    assert.deepEqual(writes.map(write => write.route), ['edit'], 'no Z2M store, install, activation or device request');
    assert.ok(audits.length >= 3);
    assert.ok(audits.every(row => Object.keys(row).sort().join() === 'language,openEditor,previousLanguage'));
    await page.setViewportSize({width: 1440, height: 900});
    await verifyWorkspaceWheel(page);
    rejectReview = true;
    await showStep(page, 'review');
    await page.locator('button[data-transfer-action=validate]').click();
    const finding = page.locator('.desktop-findings li');
    await finding.waitFor();
    assert.match(await finding.innerText(), /Eigener Name.*Aktion 1.*state/s);
    assert.match(await finding.innerText(), /INVALID.*ON.*OFF/s, 'the finding explains the actual value and allowed values');
    await finding.locator('button').click();
    await page.locator('#edit-dialog').waitFor({state: 'visible'});
    assert.equal(await page.locator('#edit-dialog [aria-invalid=true]').count(), 1);
    assert.ok(await page.locator('#edit-dialog [aria-invalid=true]').evaluate(node => node === node.ownerDocument.activeElement), 'correction opens and focuses the actual value field');
    await page.locator('#edit-cancel').click();
    rejectReview = false; await showStep(page, 'automations');
    await verifyDraftForm(page, page, 'b', true);
    active = 'a';
    const tile = await context.newPage(); tile.on('pageerror', error => errors.push(error.message));
    await tile.setViewportSize({width: 400, height: 740});
    await tile.goto('http://127.0.0.1:' + server.address().port + '/tile.html');
    await tile.getByText('Designer in der Kachel verbunden.', {exact: false}).waitFor();
    await verifyDraftForm(tile, tile.frameLocator('#designer-frame'), 'a');
    assert.deepEqual(writes.map(write => [write.route, write.systemId]), [['edit', 'a'], ['create', 'b'], ['create', 'b'], ['create', 'a']]);
    assert.equal(writes[1].body.requestId, writes[2].body.requestId, 'refresh retains the request identity');
    assert.deepEqual(exports, [], 'optional step 5 downloads use the displayed local draft without a write request');
    const tileView = tile.frameLocator('#designer-frame');
    await showStep(tileView, 'operation');
    const beforeRelease = structuredClone(model('a').configuration);
    const beforeReleaseOperations = operations.length;
    await tileView.locator('#symcon-rule-switches input[data-rule-id="rule_a"]').check();
    await tileView.getByText('Freigabe im Entwurf gespeichert. Jetzt prüfen und in Schritt 8 übermitteln.', {exact: true}).waitFor();
    assert.equal(rules.get('a').enabled, true);
    assert.equal(rules.get('b').enabled, false);
    assert.deepEqual(writes.at(-1).body.changes, {enabled: true});
    assert.deepEqual(model('a').configuration.automations[0], {...beforeRelease.automations[0], enabled: true});
    assert.equal(operations.length, beforeReleaseOperations, 'editing a permission sends no transfer or runtime command');
    await verifyMissingCatalogNavigation(page, page, 'en', false);
    await verifyMissingCatalogNavigation(page, page, 'de', true);
    await verifyMissingCatalogNavigation(tile, tile.frameLocator('#designer-frame'), 'en', false);
    await verifyMissingCatalogNavigation(tile, tile.frameLocator('#designer-frame'), 'de', true);
    await verifyDraftSynchronization(page, page, 'en');
    await verifyDraftSynchronization(tile, tile.frameLocator('#designer-frame'), 'de');
    assert.deepEqual(errors, []);
    console.log('Fresh Z2M-to-draft update passed: browser/tile, direct adoption, conflict confirmation/cancellation, backup restoration, open-input protection and bounded polling.');
    console.log('Missing device data guidance passed: DE/EN, browser/tile, module refresh versus explicit step 4 read, no implicit MQTT and preserved drafts.');
    console.log('Offline guided workspace passed: browser and tile, eight steps, existing system choices, local form, optional export, cancellation and scoped stop/check/store without activation.');
    console.log('Offline browser localization passed: DE/EN, open input identity, selections, checkboxes, search, scroll, system switch, saved preference and 360–3840px in light/dark.');
    await context.close();
    for (const [browserLocale, expected] of [['de-DE', 'de'], ['fr-FR', 'en']]) {
        const fresh = await browser.newContext({locale: browserLocale}); const view = await fresh.newPage();
        await view.goto('http://127.0.0.1:' + server.address().port + '/index.html#' + token);
        await view.waitForSelector('#edit-automation-button:not([disabled])');
        assert.equal(await view.locator('html').getAttribute('lang'), expected, 'browser default and English fallback');
        await fresh.close();
    }
} catch (error) {
    for (const context of browser.contexts()) for (const page of context.pages()) for (const frame of page.frames()) {
        if (!frame.url().includes('/index.html')) continue;
        console.error('GUIDED_LAYOUT_FAILURE', await frame.evaluate(() => ({height: innerHeight, width: innerWidth,
            elements: ['.app-header', '.desktop-shell', '.desktop-steps', '.desktop-context', '.desktop-content', '.designer-feedback', '#desktop-view-automations', '#desktop-view-automations .desktop-step-body', '.workspace', '.navigation', '.designer-fold', '.designer-access'].map(selector => {
                const node = document.querySelector(selector); if (!node) return {selector};
                const rect = node.getBoundingClientRect(); const style = getComputedStyle(node);
                return {selector, top: rect.top, bottom: rect.bottom, height: rect.height, scrollHeight: node.scrollHeight, clientHeight: node.clientHeight, overflow: style.overflow, flex: style.flex};
            })})));
    }
    throw error;
} finally {await browser.close(); await new Promise(resolve => server.close(resolve));}
