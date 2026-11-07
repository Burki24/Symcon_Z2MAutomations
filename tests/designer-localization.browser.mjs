/* Offline browser acceptance. Supply an existing Playwright installation; never connects to Symcon or MQTT. */
import assert from 'node:assert/strict';
import {createServer} from 'node:http';
import {readFileSync} from 'node:fs';
import {pathToFileURL} from 'node:url';
const {chromium} = await import(pathToFileURL(process.env.Z2MA_PLAYWRIGHT_MODULE).href);
const catalogs = JSON.parse(readFileSync(new URL('../Z2MAutomations/visualization/locales/en.json', import.meta.url), 'utf8'));
const german = JSON.parse(readFileSync(new URL('../Z2MAutomations/visualization/locales/de.json', import.meta.url), 'utf8'));
const assets = new URL('../Z2MAutomations/visualization/', import.meta.url);
const audits = []; const writes = []; const errors = [];
const token = 'a'.repeat(64);
const rule = {id: 'rule_a', name: 'Eigener Name – ON', enabled: false, retrigger: 'extend',
    triggers: [{type: 'device', device: 'lamp', property: 'state', operator: 'eq', value: 'ON'}],
    conditions: [{type: 'weekday', weekdays: [1, 4], timezone: 'UTC'}],
    actions: [{type: 'set', target: {type: 'device', id: 'lamp'}, values: {state: 'ON'}, delay_seconds: 5},
        {type: 'set', target: {type: 'device', id: 'lamp'}, values: {state: 'OFF'}, delay_seconds: 0}]};
const rules = new Map([['a', structuredClone(rule)], ['b', structuredClone(rule)]]);
const systems = ['a', 'b'].map((name, index) => ({name: 'Eigenes System ' + name, binding: {systemId: name, generation: 1, baseTopic: name === 'a' ? 'zigbee2mqtt' : 'Z2M-2'}}));
function model(id = 'a') {
    return {contractVersion: 1, mode: 'read', module: {id: 1, name: 'Synthetic centre', baseTopic: id === 'a' ? 'zigbee2mqtt' : 'Z2M-2', gateway: 2},
        readAt: 1800000000, catalogAvailable: true, catalogLoadedAt: 1800000000, draftAvailable: true, draftMismatch: false, openFormEdits: false,
        configuration: {schema_version: 1, revision: 4, mesh_peers: ['kept-peer'], automations: [structuredClone(rules.get(id))], sequences: []},
        devices: [{key: 'lamp', name: 'Eigener Gerätename – Ein', target: {type: 'device', id: 'lamp'},
            properties: [{id: 'state', label: 'Eigener Kanal', type: 'binary', readable: true, writable: true, values: ['ON', 'OFF'], actionValues: ['ON', 'OFF', 'TOGGLE'], valueLabels: [], unit: ''}]}],
        status: {confirmed: true, mqttConnected: true, executing: true, activeRevision: 4, storedRevision: 4},
        timezones: ['UTC', 'Europe/Berlin'], createContext: 'c'.repeat(64), createAllowed: true, editAllowed: true, manageAllowed: true, sequenceAllowed: true,
        sequenceCreateAllowed: true, createNotice: '', editNotice: '', sequenceNotice: '', sequenceSections: {},
        editSections: {rule_a: {triggers: true, conditions: true, actions: true}},
        workspace: {fingerprint: 'd'.repeat(64), systems}, workspaceContext: {systemId: id, generation: 1, fingerprint: 'e'.repeat(64)}};
}
let active = 'a';
const server = createServer(async (request, response) => {
    const route = new URL(request.url, 'http://localhost').pathname.slice(1);
    response.setHeader('Cache-Control', 'no-store');
    if (request.method === 'POST') {
        const parts = []; for await (const part of request) parts.push(part);
        const body = JSON.parse(Buffer.concat(parts).toString('utf8'));
        if (route === 'language') audits.push(body);
        else if (route === 'select-system') active = body.systemId;
        else { writes.push({route, body}); if (route === 'edit') Object.assign(rules.get(active), structuredClone(body.changes)); }
        response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify({ok: true, ruleId: 'rule_a'})); return;
    }
    if (route === 'data') {response.setHeader('Content-Type', 'application/json'); response.end(JSON.stringify(model(active))); return;}
    if (!/^[a-z-]+\.(?:js|html|css)$/.test(route)) {response.writeHead(404); response.end(); return;}
    try {
        let body = readFileSync(new URL(route, assets), 'utf8');
        if (route === 'index.html') body = body.replace('type="module" src="designer.js"', 'defer src="startup.js"').replace("connect-src 'none'", "connect-src 'self'");
        response.setHeader('Content-Type', route.endsWith('.js') ? 'application/javascript' : route.endsWith('.css') ? 'text/css' : 'text/html'); response.end(body);
    } catch {response.writeHead(404); response.end();}
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
const browser = await chromium.launch({channel: 'msedge', headless: true});
try {
    const context = await browser.newContext({locale: 'en-GB', viewport: {width: 1440, height: 1000}});
    const page = await context.newPage(); page.on('pageerror', error => errors.push(error.message));
    await page.goto('http://127.0.0.1:' + server.address().port + '/index.html#' + token);
    await page.waitForSelector('#edit-automation-button:not([disabled])');
    assert.equal(await page.locator('html').getAttribute('lang'), 'en');
    await page.locator('#edit-automation-button').click();
    const dialog = page.locator('#edit-dialog'); await dialog.waitFor({state: 'visible'});
    const name = dialog.locator('input[type=text]').first(); await name.fill('Unsaved user input – Ein ON');
    await dialog.locator('[data-action-index="0"] input[type=number]').first().fill('9');
    await dialog.locator('[data-action-index="1"]').getByRole('button', {name: 'Move up', exact: true}).click();
    const search = dialog.locator('input[type=search]').first(); await search.fill('Eigener');
    const selects = await dialog.locator('select:not([data-language-select])').evaluateAll(nodes => nodes.map(n => n.value));
    const checks = await dialog.locator('input[type=checkbox]').evaluateAll(nodes => nodes.map(n => n.checked));
    await name.evaluate(node => {window.__nameField = node;});
    const box = await dialog.boundingBox();
    await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
    await page.mouse.wheel(0, 240); await page.waitForTimeout(100);
    assert.ok(await dialog.evaluate(node => node.scrollTop > 0), 'the open editor accepts real mouse-wheel input');
    await dialog.evaluate(node => {window.__dialogScroll = node.scrollTop;});
    await dialog.evaluate((root, {english, german}) => {
        const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT); const rows = []; let node;
        while ((node = walker.nextNode())) {
            const key = Object.keys(english).find(key => english[key] === node.data.trim() && !key.startsWith('server.') && english[key] !== german[key]);
            if (key) rows.push({node, expected: german[key], original: node.data.trim()});
        }
        window.__languageRows = rows;
    }, {english: catalogs, german});
    await dialog.locator('[data-language-picker] select').selectOption('de');
    assert.deepEqual(await page.evaluate(() => window.__languageRows.filter(row => row.node.isConnected && row.node.data.trim() !== row.expected).map(row => ({before: row.original, after: row.node.data.trim(), expected: row.expected}))), [], 'every bound editor caption changes language');
    assert.equal(await name.inputValue(), 'Unsaved user input – Ein ON');
    assert.equal(await search.inputValue(), 'Eigener');
    assert.ok(await name.evaluate(node => window.__nameField === node), 'language changes retain the original input node and listeners');
    assert.deepEqual(await dialog.locator('input[type=checkbox]').evaluateAll(nodes => nodes.map(n => n.checked)), checks);
    assert.equal(await dialog.locator('input[type=text]').first().inputValue(), 'Unsaved user input – Ein ON');
    assert.match(await dialog.innerText(), /Name der Automation/);
    await dialog.locator('[data-language-picker] select').selectOption('en');
    assert.match(await dialog.innerText(), /Automation name/);
    assert.equal(await name.inputValue(), 'Unsaved user input – Ein ON');
    assert.equal(await search.inputValue(), 'Eigener');
    const currentSelects = await dialog.locator('select:not([data-language-select])').evaluateAll(nodes => nodes.map(n => n.value));
    assert.deepEqual(currentSelects, selects, 'typed channel/value selections stay unchanged');
    assert.equal(await dialog.evaluate(node => node.scrollTop), await page.evaluate(() => window.__dialogScroll));
    await dialog.locator('#edit-submit').click();
    await dialog.waitFor({state: 'hidden'});
    assert.equal(writes.length, 1);
    assert.equal(writes[0].route, 'edit', 'only the synthetic local draft endpoint is used');
    assert.equal(writes[0].body.changes.name, 'Unsaved user input – Ein ON');
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
    for (const width of [360, 1920, 3840]) {
        await page.setViewportSize({width, height: 1000});
        assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'no horizontal page overflow at ' + width);
    }
    await page.waitForTimeout(100);
    assert.deepEqual(writes.map(write => write.route), ['edit'], 'no Z2M store, install, activation or device request');
    assert.ok(audits.length >= 3);
    assert.ok(audits.every(row => Object.keys(row).sort().join() === 'language,openEditor,previousLanguage'));
    assert.deepEqual(errors, []);
    console.log('Offline browser localization passed: DE/EN, open input identity, selections, checkboxes, search, scroll, system switch, saved preference and 360–3840px.');
    await context.close();
    for (const [browserLocale, expected] of [['de-DE', 'de'], ['fr-FR', 'en']]) {
        const fresh = await browser.newContext({locale: browserLocale}); const view = await fresh.newPage();
        await view.goto('http://127.0.0.1:' + server.address().port + '/index.html#' + token);
        await view.waitForSelector('#edit-automation-button:not([disabled])');
        assert.equal(await view.locator('html').getAttribute('lang'), expected, 'browser default and English fallback');
        await fresh.close();
    }
} finally {await browser.close(); await new Promise(resolve => server.close(resolve));}
