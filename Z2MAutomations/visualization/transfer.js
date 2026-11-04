import {systemHeaders} from './workspace.js';

/* A POST is sent only after an explicit click. Polling reads cached module results. */
export async function transferRequest(data, action, {token, request = fetch, base = location.href, requestId = crypto.randomUUID()} = {}) {
    const context = data?.workspaceContext;
    if (!context || (action !== 'status' && !['validate', 'store'].includes(action))) throw new Error('Ungültiger Systemauftrag. Bitte die Ansicht aktualisieren.');
    const endpoint = new URL(action === 'status' ? 'transfer-status' : 'transfer', base); endpoint.hash = '';
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        let response; let result;
        try {
            response = await request(endpoint, {method: action === 'status' ? 'GET' : 'POST',
                headers: {'X-Z2MA-Read-Token': token, ...systemHeaders(data), ...(action === 'status' ? {} : {'Content-Type': 'application/json'})},
                ...(action === 'status' ? {} : {body: JSON.stringify({action, context: data.transfer.context, requestId})}),
                credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            result = await response.json();
        } catch (error) {
            if (error.name === 'AbortError') throw error;
            throw new Error('Keine lesbare Rückmeldung vom Modul. Es wird nichts erneut gesendet. Bitte den Übertragungsstatus aktualisieren.');
        }
        if (!response.ok || !result.ok) throw new Error(result.message || 'Auftrag nicht bestätigt. Bitte den Übertragungsstatus aktualisieren.');
        if (result.transfer?.systemId !== context.systemId || result.transfer?.generation !== context.generation
            || typeof result.transfer.message !== 'string' || typeof result.transfer.details !== 'string'
            || typeof result.transfer.context !== 'string' || typeof result.transfer.pending !== 'boolean'
            || typeof result.transfer.canValidate !== 'boolean' || typeof result.transfer.canStore !== 'boolean') throw new Error('Die Antwort gehört nicht zum ausgewählten System oder ist unvollständig. Bitte die Ansicht aktualisieren.');
        return result.transfer;
    } finally { clearTimeout(timeout); }
}

export function installTransfers({getData, reload, isLoading = () => false, message}) {
    const panel = document.createElement('section'); panel.className = 'system-transfer'; panel.hidden = true;
    const heading = document.createElement('strong');
    const status = document.createElement('p'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
    const details = document.createElement('p'); details.className = 'transfer-details';
    const actions = document.createElement('div'); actions.className = 'transfer-actions';
    const buttons = {};
    for (const [action, text] of [['validate', 'Entwurf in Z2M prüfen'], ['store', 'Geprüften Entwurf in Z2M speichern'], ['status', 'Übertragungsstatus aktualisieren']]) {
        const button = document.createElement('button'); button.type = 'button'; button.textContent = text;
        button.addEventListener('click', () => send(action)); buttons[action] = button; actions.append(button);
    }
    const note = document.createElement('p'); note.className = 'field-help';
    note.textContent = 'Speichern ersetzt nur die Automationsdatei dieses Systems. Bei Änderungen stoppt dort die bisherige Ausführung. Es wird keine Automation aktiviert.';
    panel.append(heading, status, details, actions, note); document.querySelector('.system-selector').after(panel);
    let timer = null; let busy = false; let windowEnd = 0; let observed = '';
    const current = captured => getData()?.workspaceContext?.systemId === captured?.workspaceContext?.systemId
        && getData()?.workspaceContext?.generation === captured?.workspaceContext?.generation;
    const paint = () => {
        const data = getData(); const view = data?.transfer;
        panel.hidden = !data?.workspace || !view;
        if (panel.hidden) return;
        heading.textContent = `Prüfen und übertragen · ${data.module.baseTopic}`;
        status.textContent = view.message; details.textContent = view.details;
        buttons.validate.disabled = busy || isLoading() || !view.canValidate;
        buttons.store.disabled = busy || isLoading() || !view.canStore;
        buttons.status.disabled = busy || isLoading();
    };
    const schedule = () => {
        clearTimeout(timer);
        if (getData()?.transfer?.pending && Date.now() < windowEnd) timer = setTimeout(() => send('status', true), 1200);
    };
    async function send(action, automatic = false) {
        if (busy || isLoading()) { if (automatic) schedule(); return; }
        const data = getData(); if (!data?.transfer) return;
        if (action !== 'status' && document.querySelector('dialog[open]')) { message('Bitte die offene Bearbeitung zuerst übernehmen oder schließen.'); return; }
        if (action === 'validate' && !data.transfer.canValidate || action === 'store' && !data.transfer.canStore) return;
        if (action === 'store' && !confirm(`Geprüften Entwurf für „${data.module.baseTopic}“ in Z2M speichern? Bei Änderungen stoppt dort der bisherige Automatikbetrieb. Es wird keine Automation aktiviert.`)) return;
        busy = true; clearTimeout(timer); paint();
        if (!automatic) windowEnd = Date.now() + 120000;
        try {
            const view = await transferRequest(data, action, {token: location.hash.slice(1)});
            if (!current(data)) return;
            // A stale read must never replace the model of a newer local edit.
            if (getData() !== data) { await reload(); return; }
            data.transfer = view;
            if (view.fingerprint !== data.workspaceContext.fingerprint) await reload();
            else paint();
        } catch (error) {
            if (current(data)) message(error.name === 'AbortError'
                ? 'Rückmeldung steht noch aus. Es wird nichts erneut gesendet. Bitte den Übertragungsstatus aktualisieren.' : error.message);
        } finally { busy = false; paint(); schedule(); }
    }
    return () => {
        const data = getData(); const key = `${data?.workspaceContext?.systemId ?? ''}:${data?.transfer?.operation ?? ''}`;
        if (observed !== key) { observed = key; windowEnd = Date.now() + 120000; }
        paint(); schedule();
    };
}
