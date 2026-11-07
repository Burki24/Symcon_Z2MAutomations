import assert from 'node:assert/strict';
import test from 'node:test';
import vm from 'node:vm';
import {installNativeConsoleProbe} from './support/native-console-probe.mjs';

const script = (body = 'Z2MA_RequestWorkspaceInstallation($id, "SYNTHETIC", 1, "FINGERPRINT", "OPERATION", "CONTEXT", true);', id = 12345) => '<?php\n$id = ' + id + ';\n' + body;
const harness = (run = () => Promise.resolve('')) => {
    class Proxy {
        makeRequest(...args) { return run.apply(this, args); }
    }
    const registry = {get: name => name === 'ips-proxy' ? Proxy : undefined};
    const original = Proxy.prototype.makeRequest;
    const probe = installNativeConsoleProbe({instanceID: 12345, registry});
    return {Proxy, registry, original, probe, proxy: new Proxy()};
};

test('native observer counts console handoffs before transport and preserves this, arguments and promise identity', async () => {
    const forwarded = [], promise = Promise.resolve('');
    const h = harness(function (...args) { forwarded.push({receiver: this, args}); return promise; });
    try {
        assert.equal(h.probe.snapshot().counts.installRequests, 0, 'Cancelled dialog does not invoke the proxy');
        const params = [script()];
        assert.equal(h.proxy.makeRequest('IPS_RunScriptTextWait', params), promise);
        assert.equal(h.probe.snapshot().counts.pending, 1, 'An unresolved RPC is not recorded as a returned callback');
        await promise;
        assert.equal(forwarded[0].receiver, h.proxy);
        assert.equal(forwarded[0].args[1], params);
        assert.deepEqual(h.probe.snapshot().counts, {installRequests: 1, installCalls: 1, statusRequests: 0, statusCalls: 0, pending: 0, returned: 1, feedback: 0, rejected: 0});
        await h.proxy.makeRequest('IPS_RunScriptTextWaitEx', [script('Z2MA_GetWorkspaceInstallationStatus($id, "SYNTHETIC");'), {}]);
        await h.proxy.makeRequest('IPS_GetKernelVersion', []);
        assert.equal(h.probe.snapshot().counts.installRequests, 1);
        assert.equal(h.probe.snapshot().counts.statusRequests, 1);
        assert.equal(h.probe.snapshot().valid, true);
        assert.equal(h.probe.restore(), true);
        assert.equal(h.Proxy.prototype.makeRequest, h.original);
        await h.proxy.makeRequest('IPS_RunScriptTextWait', [script()]);
        assert.equal(h.probe.snapshot().counts.installRequests, 1);
    } finally { h.probe.restore(); }
});

test('quoted data, comments, foreign methods and similarly named calls do not inflate the native counter or leak bodies', async () => {
    const h = harness();
    try {
        const data = script(`$input = 'PRIVATE_SECRET Z2MA_RequestWorkspaceInstallation($id)';
            $more = "escaped \\" Z2MA_RequestWorkspaceInstallation($id)";
            // Z2MA_RequestWorkspaceInstallation($id);
            /* Z2MA_GetWorkspaceInstallationStatus($id); */
            # Z2MA_RequestWorkspaceInstallation($id);
            OtherZ2MA_RequestWorkspaceInstallation($id);`);
        await h.proxy.makeRequest('IPS_RunScriptTextWait', [data]);
        await h.proxy.makeRequest('IPS_SetProperty', [12345, 'PRIVATE_SECRET', script()]);
        assert.equal(h.probe.snapshot().counts.installRequests, 0);
        assert.equal(h.probe.snapshot().valid, true);
        assert.ok(!JSON.stringify(h.probe.snapshot()).includes('PRIVATE_SECRET'));
    } finally { h.probe.restore(); }
});

test('repeated RPCs and multiple actual calls remain visible instead of passing through a visible-phase fallback', async () => {
    const h = harness();
    try {
        await h.proxy.makeRequest('IPS_RunScriptTextWait', [script()]);
        await h.proxy.makeRequest('IPS_RunScriptTextWait', [script()]);
        await h.proxy.makeRequest('IPS_RunScriptTextWait', [script('Z2MA_RequestWorkspaceInstallation($id); Z2MA_RequestWorkspaceInstallation($id);')]);
        assert.equal(h.probe.snapshot().counts.installRequests, 3);
        assert.equal(h.probe.snapshot().counts.installCalls, 4);
        assert.notEqual(h.probe.snapshot().counts.installCalls, 1);
    } finally { h.probe.restore(); }
});

test('rejection, synchronous failure and feedback are counted separately without replacing results or storing errors', async () => {
    const secret = new Error('PRIVATE_REJECTION');
    let mode = 'reject';
    const h = harness(() => {
        if (mode === 'throw') throw secret;
        return mode === 'reject' ? Promise.reject(secret) : Promise.resolve('PRIVATE_FEEDBACK');
    });
    try {
        await assert.rejects(h.proxy.makeRequest('IPS_RunScriptTextWait', [script()]), error => error === secret);
        mode = 'throw';
        assert.throws(() => h.proxy.makeRequest('IPS_RunScriptTextWait', [script()]), error => error === secret);
        mode = 'feedback';
        assert.equal(await h.proxy.makeRequest('IPS_RunScriptTextWait', [script()]), 'PRIVATE_FEEDBACK');
        assert.equal(h.probe.snapshot().counts.rejected, 2);
        assert.equal(h.probe.snapshot().counts.feedback, 1);
        assert.ok(!JSON.stringify(h.probe.snapshot()).includes('PRIVATE'));
    } finally { h.probe.restore(); }
});

test('missing proxy, double instrumentation, foreign binding and unreadable scripts cannot silently pass', async () => {
    assert.throws(() => installNativeConsoleProbe({instanceID: 12345, registry: {get: () => undefined}}));
    const h = harness();
    try {
        assert.throws(() => installNativeConsoleProbe({instanceID: 12345, registry: h.registry}));
        await h.proxy.makeRequest('IPS_RunScriptTextWait', [script(undefined, 23456)]);
        await h.proxy.makeRequest('IPS_RunScriptTextWait', [script('$x = <<<END\nPRIVATE\nEND;')]);
        assert.equal(h.probe.snapshot().valid, false);
        assert.deepEqual(h.probe.snapshot().problems, ['foreign_binding', 'unreadable_script']);
    } finally { h.probe.restore(); }
});

test('recording is bounded and restoration does not overwrite another observer', async () => {
    let forwarded = 0;
    const h = harness(() => { forwarded++; return Promise.resolve(''); });
    try {
        for (let i = 0; i < 65; i++) await h.proxy.makeRequest('IPS_RunScriptTextWait', [script()]);
        assert.equal(h.probe.snapshot().counts.installRequests, 64);
        assert.equal(forwarded, 65, 'An observer overflow must not suppress the real console request');
        assert.equal(h.probe.snapshot().valid, false);
        assert.deepEqual(h.probe.snapshot().problems, ['overflow']);
        const replacement = () => '';
        h.Proxy.prototype.makeRequest = replacement;
        assert.equal(h.probe.restore(), false);
        assert.equal(h.Proxy.prototype.makeRequest, replacement);
    } finally { h.probe.restore(); }
});

test('the browser injection is self-contained and retains no Node module closure', async () => {
    const context = vm.createContext({});
    vm.runInContext('class Proxy { makeRequest() { return Promise.resolve(\'\'); } }; globalThis.customElements = {get: () => Proxy}; globalThis.proxy = new Proxy();', context);
    vm.runInContext('globalThis.probe = (' + installNativeConsoleProbe.toString() + ')({instanceID:12345});', context);
    context.code = script();
    await vm.runInContext('proxy.makeRequest("IPS_RunScriptTextWait", [code])', context);
    const snapshot = JSON.parse(vm.runInContext('JSON.stringify(probe.snapshot())', context));
    assert.equal(snapshot.counts.installRequests, 1);
    assert.equal(snapshot.counts.returned, 1);
    assert.equal(snapshot.valid, true);
    vm.runInContext('probe.restore()', context);
});
