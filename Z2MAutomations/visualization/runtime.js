import {createUUID} from './identity.js';
import {systemHeaders} from './workspace.js';

/* Polling is read-only. Every MQTT request, including a fresh status, needs a click. */
export async function runtimeRequest(data, action, {token, rule = '', run = '', resume = true,
    request = fetch, base = location.href, requestId = createUUID()} = {}) {
    const ctx = data?.workspaceContext;
    if (!ctx || !data.runtime || !['poll', 'status', 'activate', 'deactivate', 'run_once', 'stop_rule'].includes(action)) throw new Error('Bitte zuerst ein Z2M-System auswählen.');
    const endpoint = new URL(action === 'poll' ? 'runtime-status' : 'runtime', base); endpoint.hash = '';
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        let response; let result;
        try {
            response = await request(endpoint, {method: action === 'poll' ? 'GET' : 'POST',
                headers: {'X-Z2MA-Read-Token': token, ...systemHeaders(data), ...(action === 'poll' ? {} : {'Content-Type': 'application/json'})},
                ...(action === 'poll' ? {} : {body: JSON.stringify({action, context: data.runtime.context, requestId, rule, run, resume})}),
                credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            result = await response.json();
        } catch (error) {
            if (error.name === 'AbortError') throw error;
            throw new Error('Keine lesbare Rückmeldung. Es wird nichts erneut gesendet. Bitte den Betriebsstatus aktualisieren.');
        }
        if (!response.ok || !result.ok) throw new Error(result.message || 'Betriebsauftrag nicht bestätigt. Bitte den Betriebsstatus aktualisieren.');
        const view = result.runtime;
        if (view?.systemId !== ctx.systemId || view?.generation !== ctx.generation || typeof view.context !== 'string'
            || typeof view.fingerprint !== 'string' || typeof view.message !== 'string' || typeof view.pending !== 'boolean'
            || typeof view.canActivate !== 'boolean' || typeof view.canStop !== 'boolean' || typeof view.canRefresh !== 'boolean'
            || !view.rules || typeof view.rules !== 'object' || Object.values(view.rules).some(r => !r || typeof r.name !== 'string'
                || typeof r.run !== 'string' || typeof r.message !== 'string' || typeof r.canRun !== 'boolean' || typeof r.canStop !== 'boolean')) {
            throw new Error('Die Rückmeldung gehört nicht zum gewählten System oder ist unvollständig. Bitte die Ansicht aktualisieren.');
        }
        return view;
    } finally { clearTimeout(timeout); }
}

export function installRuntime({getData, reload, isLoading = () => false, message, onStatus = () => {}}) {
    const element = (type, text = '') => { const node = document.createElement(type); node.textContent = text; return node; };
    const panel = element('section'); panel.className = 'system-transfer system-runtime'; panel.hidden = true;
    const heading = element('strong');
    const status = element('p'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
    const notice = element('p'); notice.className = 'field-help';
    const resumeLabel = element('label'); const resume = element('input'); resume.type = 'checkbox';
    resumeLabel.append(resume, element('span', ' Automatikbetrieb nach einem Z2M-Neustart wieder aufnehmen'));
    const actions = element('div'); actions.className = 'transfer-actions';
    const buttons = {};
    for (const [action, text] of [['activate', 'Gespeicherte Automationen aktivieren'], ['deactivate', 'Alle Automationen dieses Systems stoppen'], ['status', 'Betriebsstatus in Z2M aktualisieren']]) {
        const button = element('button', text); button.type = 'button'; button.addEventListener('click', () => send(action)); buttons[action] = button; actions.append(button);
    }
    const selectionLabel = element('label', 'Eine gespeicherte Automation auswählen'); const selection = element('select'); selection.setAttribute('aria-label', 'Automation für Einzelstart oder gezielten Stopp');
    selectionLabel.append(selection);
    const runStatus = element('p'); runStatus.setAttribute('role', 'status');
    const runActions = element('div'); runActions.className = 'transfer-actions';
    for (const [action, text] of [['run_once', 'Einmal mit Bedingungen starten'], ['stop_rule', 'Nur diesen Lauf stoppen']]) {
        const button = element('button', text); button.type = 'button'; button.addEventListener('click', () => send(action)); buttons[action] = button; runActions.append(button);
    }
    const note = element('p', 'Einzelstart prüft die Bedingungen auch bei ausgeschalteten Regeln. Andere Automationen und der Automatikbetrieb bleiben dabei unverändert. Stoppen nimmt bereits gesendete Befehle nicht zurück.'); note.className = 'field-help';
    panel.append(heading, status, notice, resumeLabel, actions, selectionLabel, runStatus, runActions, note);
    document.querySelector('.system-selector').after(panel);
    const selections = new Map(); const preferences = new Map();
    let busy = false; let timer = null; let until = 0; let observed = '';
    const key = data => `${data?.workspaceContext?.systemId ?? ''}:${data?.workspaceContext?.generation ?? ''}`;
    const current = data => key(getData()) === key(data);
    selection.addEventListener('change', () => { selections.set(key(getData()), selection.value); paint(); });
    resume.addEventListener('change', () => preferences.set(key(getData()), resume.checked));
    function paint() {
        const data = getData(); const v = data?.runtime;
        panel.hidden = !data?.workspace || !v;
        if (panel.hidden) return;
        heading.textContent = `Betrieb steuern · ${data.module.baseTopic}`;
        const mode = !v.confirmed ? 'Betrieb noch nicht bestätigt.' : v.executing ? 'Automatikbetrieb aktiv.' : 'Automatikbetrieb gestoppt.';
        status.textContent = `${mode} ${v.message}`; notice.textContent = v.startNotice || '';
        resume.checked = preferences.get(key(data)) ?? v.resumeAfterRestart;
        resume.disabled = busy || isLoading();
        selection.replaceChildren();
        const placeholder = element('option', 'Bitte Automation auswählen'); placeholder.value = ''; selection.append(placeholder);
        for (const [id, rule] of Object.entries(v.rules)) { const option = element('option', rule.name); option.value = id; selection.append(option); }
        const selected = selections.get(key(data)) ?? '';
        selection.value = Object.hasOwn(v.rules, selected) ? selected : '';
        const rule = v.rules[selection.value];
        selection.disabled = busy || isLoading();
        runStatus.textContent = rule?.message ?? 'Bitte eine gespeicherte Automation für den Einzelstart oder Stopp auswählen.';
        buttons.activate.disabled = busy || isLoading() || !v.canActivate;
        buttons.deactivate.disabled = busy || isLoading() || !v.canStop;
        buttons.status.disabled = busy || isLoading() || !v.canRefresh;
        buttons.run_once.disabled = busy || isLoading() || !rule?.canRun;
        buttons.stop_rule.disabled = busy || isLoading() || !rule?.canStop;
    }
    function schedule() {
        clearTimeout(timer); const v = getData()?.runtime;
        if (v && (v.pending || Object.values(v.rules).some(r => r.state === 'running')) && Date.now() < until) timer = setTimeout(() => send('poll', true), 1500);
    }
    async function send(action, automatic = false) {
        if (busy || isLoading()) { if (automatic) schedule(); return; }
        const data = getData(); const v = data?.runtime; if (!v) return;
        const ruleId = selections.get(key(data)) ?? ''; const rule = v.rules[ruleId];
        if (action !== 'poll' && document.querySelector('dialog[open]')) { message('Bitte die offene Bearbeitung zuerst übernehmen oder schließen.'); return; }
        if (action === 'activate' && !v.canActivate || action === 'deactivate' && !v.canStop || action === 'status' && !v.canRefresh
            || action === 'run_once' && !rule?.canRun || action === 'stop_rule' && !rule?.canStop) return;
        const target = `„${data.module.baseTopic}“`;
        const prompt = action === 'activate' ? `Gespeicherte Automationen in ${target} aktivieren? Geräte können dadurch geschaltet werden.`
            : action === 'deactivate' ? `Alle Automationen in ${target} stoppen? Bereits gesendete Befehle bleiben wirksam.`
                : action === 'run_once' ? `„${rule.name}“ in ${target} einmal mit Bedingungsprüfung starten? Geräte können geschaltet werden.` : '';
        if (prompt && !confirm(prompt)) return;
        const choice = preferences.get(key(data)) ?? v.resumeAfterRestart;
        busy = true; clearTimeout(timer); paint(); if (!automatic) until = Date.now() + 120000;
        try {
            const view = await runtimeRequest(data, action, {token: location.hash.slice(1),
                rule: ['run_once', 'stop_rule'].includes(action) ? ruleId : '', run: action === 'stop_rule' ? rule.run : '', resume: choice});
            if (!current(data)) return;
            if (getData() !== data || view.fingerprint !== data.workspaceContext.fingerprint) { await reload(); return; }
            data.runtime = view;
            data.status = {...data.status, confirmed: view.confirmed, executing: view.executing, activeRevision: view.activeRevision, storedRevision: view.storedRevision};
            onStatus(data.status);
        } catch (error) {
            if (current(data)) message(error.name === 'AbortError' ? 'Die Bestätigung steht noch aus. Kein erneuter Versand. Bitte den Betriebsstatus aktualisieren.' : error.message);
        } finally { busy = false; paint(); schedule(); }
    }
    return () => {
        const data = getData(); const next = `${key(data)}:${data?.runtime?.operation ?? ''}`;
        if (next !== observed) { observed = next; until = Date.now() + 120000; }
        paint(); schedule();
    };
}
