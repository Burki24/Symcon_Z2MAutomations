import {createUUID} from './identity.js';
/* Explicit, local-only event migration. Never execute a Symcon event or send a Z2M command. */
import {systemHeaders} from './workspace.js';

export function importRows(view, search = '', supportedOnly = false) {
    const term = search.trim().toLocaleLowerCase('de');
    return (view?.rows ?? []).filter(row => (!supportedOnly || row.importable)
        && `${row.name} ${row.device} ${row.id} ${row.instanceId}`.toLocaleLowerCase('de').includes(term));
}

export function importMessage(view, preview = false) {
    return view?.blocked || (preview ? view?.selected?.assessment : '') || view?.message || 'Bitte die Importansicht aktualisieren.';
}

function checkedView(view, context) {
    return view?.systemId === context.systemId && view.generation === context.generation && typeof view.context === 'string'
        && /^[a-f0-9]{64}$/.test(view.context) && typeof view.message === 'string' && typeof view.canEvaluate === 'boolean'
        && typeof view.scanned === 'boolean' && typeof view.limited === 'boolean' && Array.isArray(view.rows) && view.rows.length <= 256
        && view.rows.every(row => Number.isSafeInteger(row.id) && row.id > 0 && (row.instanceId === '' || Number.isSafeInteger(row.instanceId))
            && ['name', 'device', 'assessment'].every(key => typeof row[key] === 'string') && typeof row.importable === 'boolean' && typeof row.imported === 'boolean')
        && Number.isSafeInteger(view.selected?.id) && typeof view.selected.canImport === 'boolean'
        && ['name', 'summary', 'assessment', 'token'].every(key => typeof view.selected[key] === 'string')
        && (view.selected.canImport ? /^[a-f0-9]{32}$/.test(view.selected.token) : view.selected.token === '');
}

export async function importRequest(data, payload, {token, base = location.href, request = fetch} = {}) {
    if (!data?.workspaceContext) throw new Error('Bitte zuerst ein Z2M-System auswählen.');
    const endpoint = new URL('event-import', base); endpoint.hash = '';
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 15000);
    try {
        let response; let result;
        try {
            response = await request(endpoint, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-Z2MA-Read-Token': token, ...systemHeaders(data)},
                body: JSON.stringify(payload), credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const text = await response.text(); if (text.length > 524288) throw Error('response_limit'); result = JSON.parse(text);
        } catch { throw new Error('Keine eindeutige Rückmeldung erhalten. Der Importauftrag wird nicht erneut gesendet. Bitte die Ansicht aktualisieren und den Entwurf prüfen.'); }
        if (result.view && !checkedView(result.view, data.workspaceContext)) throw new Error('Die Importantwort gehört nicht zu diesem System oder ist unvollständig. Bitte die Ansicht aktualisieren.');
        if (!response.ok || result.ok !== true) { const error = new Error(typeof result.message === 'string' ? result.message : 'Der Import wurde nicht übernommen. Bitte die Vorschau erneut prüfen.'); error.view = result.view; throw error; }
        if (!checkedView(result.view, data.workspaceContext)) throw new Error('Die Importantwort ist unvollständig. Bitte die Ansicht aktualisieren.');
        return result;
    } finally { clearTimeout(timeout); }
}

export function installEventImport({getData, isLoading, reload, onImported, message}) {
    const button = document.createElement('button'); button.id = 'event-import-button'; button.textContent = 'Symcon-Ereignisse importieren';
    document.querySelector('.toolbar').append(button);
    const dialog = document.createElement('dialog'); dialog.className = 'creation-dialog event-import-dialog'; dialog.id = 'event-import-dialog'; dialog.setAttribute('aria-labelledby', 'event-import-title');
    dialog.innerHTML = '<h2 id="event-import-title">Symcon-Ereignisse importieren</h2><p class="event-import-system"></p>'
        + '<p>Unterstützte Symcon-Ereignisse werden als ausgeschaltete Automationen in den Entwurf übernommen. Die bisherigen Ereignisse bleiben unverändert. Vor dem späteren Start in Z2M das jeweilige Originalereignis ausschalten, damit die Aktion nicht doppelt ausgeführt wird.</p>'
        + '<div class="event-import-controls"><button type="button" class="event-import-scan">Vorhandene Ereignisse prüfen</button><button type="button" class="event-import-refresh">Ansicht aktualisieren</button>'
        + '<label>Nach Ereignis, Gerät oder ID suchen<input class="event-import-search" type="search"></label><label class="event-import-filter"><input type="checkbox"> Nur importierbare Ereignisse</label></div>'
        + '<p class="event-import-count"></p><div class="event-import-list"><table><thead><tr><th>Ereignis</th><th>Auslösendes Gerät</th><th>Importprüfung</th></tr></thead><tbody></tbody></table></div>'
        + '<section class="event-import-preview" aria-label="Importvorschau"><h3>Vorschau</h3><strong></strong><p class="event-import-assessment"></p><p class="event-import-summary"></p></section>'
        + '<p class="event-import-message" role="status" aria-live="polite"></p><div class="dialog-actions"><button type="button" class="event-import-cancel">Schließen</button><button type="button" class="event-import-accept primary">Ausgeschaltet zum Entwurf hinzufügen</button></div>';
    document.body.append(dialog);
    const $ = selector => dialog.querySelector(selector);
    const search = $('.event-import-search'); const filter = $('.event-import-filter input');
    let captured; let view; let busy = false; let pending = null;
    const node = (tag, text) => { const element = document.createElement(tag); element.textContent = text; return element; };
    const current = () => Boolean(captured?.workspaceContext?.systemId) && captured.workspaceContext.systemId === getData()?.workspaceContext?.systemId && captured.workspaceContext.generation === getData()?.workspaceContext?.generation;
    const hint = text => { $('.event-import-message').textContent = text; };
    function paint() {
        $('.event-import-system').textContent = `Z2M-System: ${captured?.module?.baseTopic ?? ''}`;
        const rows = importRows(view, search.value, filter.checked); const body = $('tbody'); body.replaceChildren();
        $('.event-import-count').textContent = `${rows.length} von ${view?.rows?.length ?? 0} Ereignissen` + (view?.limited ? ' · Suchgrenze erreicht: höchstens 256 Treffer aus 4096 Ereignissen.' : '');
        for (const row of rows) {
            const tr = document.createElement('tr'); if (row.id === view.selected.id) tr.classList.add('is-selected');
            const name = document.createElement('td'); const pick = node('button', row.name); pick.type = 'button'; pick.dataset.eventId = String(row.id); pick.disabled = busy;
            pick.addEventListener('click', () => send('preview', row.id)); name.append(pick, node('small', `Ereignis ${row.id}`));
            const device = node('td', row.device); device.append(node('small', `Instanz ${row.instanceId || 'nicht mehr vorhanden'}`));
            const assessment = document.createElement('td'); assessment.append(node('strong', row.imported ? 'Bereits übernommen' : row.importable ? 'Importierbar' : view.canEvaluate ? 'Nicht importierbar' : 'Noch nicht prüfbar'), node('p', row.assessment));
            tr.append(name, device, assessment); body.append(tr);
        }
        $('.event-import-preview strong').textContent = view?.selected?.name || (view?.selected?.id ? `Ereignis ${view.selected.id}` : 'Bitte einen Eintrag für die Vorschau auswählen.');
        $('.event-import-assessment').textContent = view?.selected?.assessment ?? '';
        $('.event-import-summary').textContent = view?.selected?.summary ?? '';
        $('.event-import-accept').disabled = busy || !current() || !view?.selected?.canImport;
        for (const selector of ['.event-import-scan', '.event-import-refresh', '.event-import-cancel', '.event-import-search', '.event-import-filter input']) $(selector).disabled = busy;
        $('.event-import-scan').disabled = busy || !current();
        dialog.setAttribute('aria-busy', String(busy));
    }
    async function send(action, eventId = 0) {
        if (busy || isLoading() || !current()) return;
        if (action === 'import' && !view.selected.canImport) return;
        const context = view.context; const previewToken = view.selected.token;
        if (!pending || pending.action !== action || pending.eventId !== eventId || pending.previewToken !== previewToken || pending.context !== context) {
            pending = {requestId: createUUID(), context, action, ...(action === 'scan' ? {} : {eventId}), ...(action === 'import' ? {previewToken} : {})};
        }
        busy = true; paint(); hint(action === 'scan' ? 'Ereignisse dieses Systems werden geprüft …' : action === 'preview' ? 'Auslöser und Aktion werden geprüft. Das Original bleibt unverändert …' : 'Ereignis wird erneut geprüft und ausgeschaltet zum lokalen Entwurf hinzugefügt …');
        try {
            const result = await importRequest(captured, pending, {token: location.hash.slice(1)});
            if (!current() || !dialog.open) return;
            view = result.view; captured.eventImport = view; pending = null;
            hint(importMessage(view, action === 'preview'));
            if (action === 'import') { dialog.close(); await onImported(result.ruleId); message(result.message); }
        } catch (error) { if (current() && dialog.open) { if (error.view) { view = error.view; captured.eventImport = view; } hint(error.message); } }
        finally { busy = false; paint(); }
    }
    button.addEventListener('click', () => {
        if (busy || isLoading() || !getData()?.eventImportAllowed) return;
        captured = getData(); view = structuredClone(captured.eventImport); pending = null; search.value = ''; filter.checked = false; paint(); hint(importMessage(view)); dialog.showModal(); $('.event-import-scan').focus();
    });
    search.addEventListener('input', paint); filter.addEventListener('change', paint);
    $('.event-import-scan').addEventListener('click', () => send('scan'));
    $('.event-import-accept').addEventListener('click', () => send('import', view.selected.id));
    $('.event-import-cancel').addEventListener('click', () => { if (!busy) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    $('.event-import-refresh').addEventListener('click', async () => {
        if (busy || isLoading()) return;
        busy = true; paint();
        try { if (await reload()) { captured = getData(); view = structuredClone(captured.eventImport); pending = null; hint(importMessage(view)); } else hint('Ansicht nicht geladen. Die bisherige Vorschau bleibt sichtbar.'); }
        finally { busy = false; paint(); }
    });
    return {refresh() { button.hidden = !getData()?.workspace; button.disabled = isLoading() || !getData()?.eventImportAllowed; button.title = 'Ereignisse des ausgewählten Z2M-Systems prüfen und ausgeschaltet übernehmen'; }};
}
