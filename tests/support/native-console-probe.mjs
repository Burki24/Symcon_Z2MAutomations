// Test-only observer of the public console proxy, before its Worker transport.
// Self-contained so Playwright can inject it with page.evaluateHandle().
export function installNativeConsoleProbe({instanceID, registry = globalThis.customElements} = {}) {
    if (!Number.isSafeInteger(instanceID) || instanceID <= 0) throw new Error('Ungültiger Instanzbezug für die Prüfung.');
    const prototype = registry?.get('ips-proxy')?.prototype;
    const descriptor = prototype && Object.getOwnPropertyDescriptor(prototype, 'makeRequest');
    const marker = Symbol.for('Z2MA.tests.nativeConsoleProbe');
    if (!descriptor?.configurable || typeof descriptor.value !== 'function' || descriptor.value[marker]) {
        throw new Error('Konsolenaufrufe können nicht eindeutig aufgezeichnet werden.');
    }
    const original = descriptor.value, records = [], problems = new Set();
    let active = true;
    const masked = text => {
        if (typeof text !== 'string' || text.length > 2 * 1024 * 1024) return null;
        let output = '', quote = '', comment = '';
        for (let i = 0; i < text.length; i++) {
            const char = text[i], next = text[i + 1];
            if (comment) {
                if (comment === 'line' && char === '\n') comment = '';
                else if (comment === 'block' && char === '*' && next === '/') { comment = ''; i++; }
                output += char === '\n' ? '\n' : ' ';
            } else if (quote) {
                if (char === '\\') { i++; output += ' '; }
                else if (char === quote) quote = '';
                output += ' ';
            } else if (char === '"' || char === "'" || char === '`') { quote = char; output += ' '; }
            else if (char === '#' || (char === '/' && next === '/')) { comment = 'line'; output += ' '; }
            else if (char === '/' && next === '*') { comment = 'block'; i++; output += ' '; }
            else if (text.slice(i, i + 3) === '<<<') return null;
            else output += char;
        }
        return quote || comment === 'block' ? null : output;
    };
    const observe = (method, params) => {
        if (!active || !['IPS_RunScriptTextWait', 'IPS_RunScriptTextWaitEx'].includes(method)) return null;
        const code = Array.isArray(params) ? masked(params[0]) : null;
        if (code === null) { problems.add('unreadable_script'); return null; }
        const calls = [...code.matchAll(/(?<![\w$\\:>])Z2MA_(RequestWorkspaceInstallation|GetWorkspaceInstallationStatus)\s*\(/g)].map(match => match[1]);
        if (!calls.length) return null;
        const assignments = [...code.matchAll(/\$id\s*=(?!=)/g)];
        const binding = assignments.length === 1 && /^\$id\s*=\s*(\d+)\s*;/.exec(code.slice(assignments[0].index));
        if (!binding || Number(binding[1]) !== instanceID) problems.add('foreign_binding');
        if (records.length >= 64) { problems.add('overflow'); return null; }
        const install = calls.filter(call => call === 'RequestWorkspaceInstallation').length;
        const record = {install, status: calls.length - install, outcome: 'pending'};
        records.push(record);
        return record;
    };
    function wrapped(...args) {
        let record;
        try { record = observe(args[0], args[1]); } catch { problems.add('observer_error'); }
        let value;
        try { value = Reflect.apply(original, this, args); }
        catch (error) { if (record) record.outcome = 'rejected'; throw error; }
        if (record) {
            const returned = result => {
                record.outcome = result === '' ? 'returned' : 'feedback';
                if (typeof result !== 'string') problems.add('unexpected_result');
            };
            try {
                if (typeof value?.then === 'function') value.then(returned, () => { record.outcome = 'rejected'; });
                else returned(value);
            } catch { problems.add('observer_error'); }
        }
        return value;
    }
    Object.defineProperty(wrapped, marker, {value: true});
    Object.defineProperty(prototype, 'makeRequest', {...descriptor, value: wrapped});
    return {
        snapshot() {
            const counts = {installRequests: 0, installCalls: 0, statusRequests: 0, statusCalls: 0, pending: 0, returned: 0, feedback: 0, rejected: 0};
            for (const record of records) {
                counts.installRequests += record.install > 0 ? 1 : 0;
                counts.installCalls += record.install;
                counts.statusRequests += record.status > 0 ? 1 : 0;
                counts.statusCalls += record.status;
                counts[record.outcome]++;
            }
            return {active, valid: problems.size === 0, problems: [...problems], counts};
        },
        restore() {
            if (!active) return !problems.has('changed_wrapper');
            active = false;
            if (prototype.makeRequest !== wrapped) { problems.add('changed_wrapper'); return false; }
            Object.defineProperty(prototype, 'makeRequest', descriptor);
            return true;
        }
    };
}
