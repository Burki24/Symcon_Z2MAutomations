// Optional local browser check. Playwright is supplied externally, never bundled.
import assert from 'node:assert/strict';
import {createServer} from 'node:http';
import {installNativeConsoleProbe} from './support/native-console-probe.mjs';

if (!process.env.Z2MA_PLAYWRIGHT_MODULE) throw new Error('Externe Playwright-Modul-URL über Z2MA_PLAYWRIGHT_MODULE angeben.');
const {chromium} = await import(process.env.Z2MA_PLAYWRIGHT_MODULE);
const serverCalls = [], serverErrors = [], legacy = {requests: 0};
const handle = async (request, response) => {
    if (request.url === '/worker.js') {
        response.setHeader('Content-Type', 'text/javascript');
        response.end(`onconnect = event => {
            const port = event.ports[0];
            port.onmessage = async event => {
                const response = await fetch('/rpc', {method: 'POST', body: JSON.stringify(event.data)});
                port.postMessage(await response.text());
            };
        };`);
    } else if (request.url === '/rpc' && request.method === 'POST') {
        let body = '';
        for await (const chunk of request) {
            body += chunk;
            if (body.length > 4096) { response.writeHead(413).end(); return; }
        }
        const {method, params} = JSON.parse(body);
        serverCalls.push(params[0].includes('Z2MA_RequestWorkspaceInstallation') ? 'install' : 'status');
        assert.equal(method, 'IPS_RunScriptTextWait');
        // Synthetic endpoint only acknowledges transport. It executes no PHP or MQTT.
        response.end('');
    } else if (request.url === '/') {
        response.setHeader('Content-Type', 'text/html; charset=utf-8');
        response.end(`<!doctype html><html lang="de"><title>Synthetische Konsolenprüfung</title>
            <ips-proxy></ips-proxy><button id="install">Installieren</button>
            <dialog id="confirm"><p>Synthetischen Auftrag senden?</p><button id="no">Nein</button><button id="yes">Ja</button></dialog>
            <button id="status">Status</button><script>
            class Proxy extends HTMLElement {
                constructor() { super(); this.worker = new SharedWorker('/worker.js'); }
                makeRequest(method, params) {
                    return new Promise(resolve => { this.worker.port.onmessage = event => resolve(event.data); this.worker.port.postMessage({method, params}); });
                }
            }
            customElements.define('ips-proxy', Proxy);
            const proxy = document.querySelector('ips-proxy'), confirm = document.querySelector('#confirm');
            let completed = 0;
            window.completed = () => completed;
            const call = name => proxy.makeRequest('IPS_RunScriptTextWait', ['<?php\\n$id = 12345;\\n' + name + '($id);']).then(() => completed++);
            document.querySelector('#install').onclick = () => confirm.showModal();
            document.querySelector('#no').onclick = () => confirm.close();
            document.querySelector('#yes').onclick = () => { confirm.close(); call('Z2MA_RequestWorkspaceInstallation'); };
            document.querySelector('#status').onclick = () => call('Z2MA_GetWorkspaceInstallationStatus');
            </script></html>`);
    } else { response.writeHead(404).end(); }
};
const server = createServer((request, response) => {
    void handle(request, response).catch(() => {
        serverErrors.push('synthetic_rpc_failed');
        if (!response.headersSent) response.writeHead(500);
        response.end();
    });
});
await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
let browser, observer;
try {
    browser = await chromium.launch({channel: 'msedge', headless: true});
    const page = await browser.newPage();
    const errors = [];
    page.on('pageerror', () => errors.push('page_error'));
    page.on('request', request => {
        if (request.method() === 'POST' && String(request.postData()).includes('Z2MA_RequestWorkspaceInstallation')) legacy.requests++;
    });
    page.on('websocket', socket => socket.on('framesent', frame => {
        if (String(frame.payload).includes('Z2MA_RequestWorkspaceInstallation')) legacy.requests++;
    }));
    await page.goto('http://127.0.0.1:' + server.address().port + '/');
    observer = await page.evaluateHandle(installNativeConsoleProbe, {instanceID: 12345});
    const snapshot = () => observer.evaluate(probe => probe.snapshot());
    await page.locator('#install').click(); await page.locator('#no').click();
    assert.equal((await snapshot()).counts.installRequests, 0);
    assert.equal(serverCalls.length, 0);
    await page.locator('#install').click(); await page.locator('#yes').click();
    await page.waitForFunction(() => window.completed() === 1);
    assert.deepEqual(serverCalls, ['install']);
    assert.equal(legacy.requests, 0, 'Page-only listeners miss the real SharedWorker request');
    let value = await snapshot();
    assert.equal(value.valid, true); assert.equal(value.counts.installRequests, 1); assert.equal(value.counts.installCalls, 1);
    assert.equal(value.counts.returned, 1);
    await page.locator('#status').click(); await page.waitForFunction(() => window.completed() === 2);
    assert.deepEqual(serverCalls, ['install', 'status']);
    value = await snapshot();
    assert.equal(value.valid, true); assert.equal(value.counts.installRequests, 1); assert.equal(value.counts.statusRequests, 1);
    assert.equal(value.counts.returned, 2);
    assert.equal(await observer.evaluate(probe => probe.restore()), true);
    await page.locator('#status').click(); await page.waitForFunction(() => window.completed() === 3);
    assert.deepEqual(serverCalls, ['install', 'status', 'status']);
    assert.equal((await snapshot()).counts.statusRequests, 1, 'Restored observer no longer records');
    assert.deepEqual(errors, []);
    assert.deepEqual(serverErrors, []);
    console.log(JSON.stringify({passed: true, legacyPageCounter: legacy.requests, observed: value.counts, syntheticWorkerCalls: serverCalls.length}));
} finally {
    try { await observer?.evaluate(probe => probe.restore()); } finally {
        await browser?.close(); server.closeAllConnections(); await new Promise(resolve => server.close(resolve));
    }
}
