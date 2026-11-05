import {createUUID} from './identity.js';
import {systemHeaders} from './workspace.js';

/* An explicit POST starts the read workflow. Polling never starts or repeats MQTT. */
export async function catalogRequest(data, action, {token, request = fetch, base = location.href, requestId = createUUID()} = {}) {
    const ctx = data?.workspaceContext;
    if (!ctx || !['refresh', 'status'].includes(action)) throw new Error('Bitte zuerst ein Z2M-System auswählen.');
    const endpoint = new URL(action === 'refresh' ? 'catalog-refresh' : 'catalog-status', base); endpoint.hash = '';
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        let response; let result;
        try {
            response = await request(endpoint, {method: action === 'refresh' ? 'POST' : 'GET',
                headers: {'X-Z2MA-Read-Token': token, ...systemHeaders(data), ...(action === 'refresh' ? {'Content-Type': 'application/json'} : {})},
                ...(action === 'refresh' ? {body: JSON.stringify({requestId})} : {}),
                credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const text = await response.text(); if (text.length > 16384) throw new Error('response_limit'); result = JSON.parse(text);
        } catch {
            throw new Error('Keine eindeutige Rückmeldung zu den Gerätedaten erhalten. Der Ladeauftrag wird nicht erneut gesendet. Bitte „Ladestatus prüfen“ wählen.');
        }
        if (!response.ok || !result.ok) throw new Error(result.message || 'Gerätedaten konnten nicht aktualisiert werden. Der vorhandene Entwurf bleibt erhalten.');
        const view = result.catalogRefresh;
        if (view?.systemId !== ctx.systemId || view.generation !== ctx.generation || typeof view.message !== 'string'
            || typeof view.fingerprint !== 'string' || typeof view.operation !== 'string' || typeof view.phase !== 'string'
            || typeof view.pending !== 'boolean' || typeof view.canRefresh !== 'boolean'
            || !view.counts || !['devices', 'groups', 'scenes'].every(key => Number.isSafeInteger(view.counts[key]) && view.counts[key] >= 0)
            || !(view.loadedAt === null || Number.isSafeInteger(view.loadedAt))) throw new Error('Die Rückmeldung gehört nicht zum gewählten System oder ist unvollständig. Bitte die Ansicht aktualisieren.');
        return view;
    } finally { clearTimeout(timeout); }
}

export function installCatalogRefresh({getData, reload, isLoading = () => false, message}) {
    const panel = document.createElement('section'); panel.className = 'system-catalog'; panel.hidden = true;
    const heading = document.createElement('strong');
    const status = document.createElement('p'); status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
    const details = document.createElement('p'); details.className = 'field-help';
    const actions = document.createElement('div'); actions.className = 'catalog-actions';
    const refresh = document.createElement('button'); refresh.type = 'button'; refresh.textContent = 'Gerätedaten aktualisieren';
    const poll = document.createElement('button'); poll.type = 'button'; poll.textContent = 'Ladestatus prüfen';
    actions.append(refresh, poll);
    const note = document.createElement('p'); note.className = 'field-help';
    note.textContent = 'Lädt nur Geräte, Kanäle, Gruppen und Szenen dieses Systems. Automationsentwürfe und laufende Regeln bleiben erhalten.';
    panel.append(heading, status, details, actions, note); document.querySelector('.system-selector').after(panel);
    let busy = false; let timer = null; let observed = ''; let windowEnd = 0; let localError = null;
    const current = captured => getData()?.workspaceContext?.systemId === captured?.workspaceContext?.systemId
        && getData()?.workspaceContext?.generation === captured?.workspaceContext?.generation;
    function paint() {
        const data = getData(); const view = data?.catalogRefresh;
        panel.hidden = !data?.workspace || !view;
        if (panel.hidden) return;
        heading.textContent = `Gerätedaten · ${data.module.baseTopic}`;
        status.textContent = localError && current(localError.data) ? localError.message : view.message;
        details.textContent = view.loadedAt === null ? 'Noch kein bestätigter Gerätekatalog eingelesen.'
            : `Zuletzt eingelesen: ${new Date(view.loadedAt * 1000).toLocaleString('de-DE')} · ${view.counts.devices} Geräte · ${view.counts.groups} Gruppen · ${view.counts.scenes} Szenen`;
        panel.setAttribute('aria-busy', String(busy || view.pending));
        panel.classList.toggle('is-loading', busy || view.pending);
        refresh.disabled = busy || isLoading() || !view.canRefresh;
        poll.disabled = busy || isLoading();
        refresh.textContent = view.pending ? 'Gerätedaten werden geladen …' : 'Gerätedaten aktualisieren';
    }
    function schedule() {
        clearTimeout(timer);
        if (getData()?.catalogRefresh?.pending && Date.now() < windowEnd) timer = setTimeout(() => send('status', true), 1200);
    }
    async function send(action, automatic = false) {
        if (busy || isLoading()) { if (automatic) schedule(); return; }
        const data = getData(); if (!data?.catalogRefresh) return;
        if (action === 'refresh' && document.querySelector('dialog[open]')) { message('Bitte die offene Bearbeitung zuerst übernehmen oder schließen. Ihre Eingaben bleiben erhalten.'); return; }
        if (action === 'refresh' && !data.catalogRefresh.canRefresh) return;
        localError = null; busy = true; clearTimeout(timer); paint();
        if (!automatic) windowEnd = Date.now() + 610000;
        if (action === 'refresh') { status.textContent = 'Gerätedatenaktualisierung wird angefordert …'; message('Aktuelle Gerätedaten werden angefordert. Entwürfe und Automatikbetrieb bleiben erhalten.'); }
        try {
            const view = await catalogRequest(data, action, {token: location.hash.slice(1)});
            if (!current(data)) return;
            // Do not replace an editing model while the user has a modal with unsaved inputs.
            if (getData() !== data) { if (!document.querySelector('dialog[open]')) await reload(); return; }
            data.catalogRefresh = view;
            if (view.phase === 'loaded' && view.fingerprint !== data.workspaceContext.fingerprint) {
                if (document.querySelector('dialog[open]')) message('Gerätedaten sind geladen. Ihre Eingaben bleiben erhalten. Bitte die Bearbeitung schließen und anschließend „Ansicht aktualisieren“ wählen.');
                else {
                    const loaded = await reload();
                    message(loaded === false ? 'Gerätedaten sind geladen. Die Ansicht konnte noch nicht nachgeladen werden. Bitte „Ansicht aktualisieren“ wählen.' : view.message);
                }
            } else if (!automatic || !view.pending) message(view.message);
        } catch (error) {
            if (current(data)) { localError = {data, message: error.message}; message(error.message); }
        } finally { busy = false; paint(); schedule(); }
    }
    refresh.addEventListener('click', () => send('refresh'));
    poll.addEventListener('click', () => send('status'));
    return () => {
        const data = getData(); const key = `${data?.workspaceContext?.systemId ?? ''}:${data?.catalogRefresh?.operation ?? ''}`;
        if (observed !== key) { observed = key; windowEnd = Date.now() + 610000; }
        paint(); schedule();
    };
}
