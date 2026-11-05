/* Local-only rule management. Confirmation captures its system and exact draft. */
export function matchesRuleState(entry, state) {
    return state === 'all' || entry.enabled === undefined || entry.enabled === (state === 'enabled');
}

export function sequenceUsers(configuration, id) {
    return (configuration?.automations ?? []).filter(rule => rule.actions.some(action => action.type === 'sequence' && action.sequence_id === id));
}

export function installManagement({getData, getEntry, isLoading, reload, copied, message, filterChanged}) {
    const tools = document.querySelector('.entry-tools');
    const makeButton = (label, action) => {
        const button = document.createElement('button'); button.type = 'button'; button.textContent = label;
        button.addEventListener('click', action); tools.append(button); return button;
    };
    const copy = makeButton('Automation kopieren', () => open('copy-rule'));
    const remove = makeButton('Löschen', () => open(getEntry()?.kind === 'sequence' ? 'delete-sequence' : 'delete-rule'));
    remove.classList.add('danger');
    const filterLabel = document.createElement('label'); filterLabel.className = 'rule-state-filter'; filterLabel.textContent = 'Regelschalter im Entwurf';
    const filter = document.createElement('select'); filter.setAttribute('aria-label', 'Automationen nach Regelschalter filtern');
    for (const [value, label] of [['all', 'Alle'], ['enabled', 'Aktiviert'], ['disabled', 'Deaktiviert']]) filter.add(new Option(label, value));
    filterLabel.append(filter); document.querySelector('#categories').before(filterLabel);
    const dialog = document.createElement('dialog'); dialog.className = 'management-dialog';
    dialog.setAttribute('aria-labelledby', 'management-title');
    dialog.innerHTML = '<h2 id="management-title"></h2><p class="management-description"></p><ul class="management-users"></ul><p class="creation-feedback" role="status"></p><div class="dialog-actions"><button type="button" class="management-cancel">Abbrechen</button><button type="button" class="primary management-accept">Bestätigen</button></div>';
    document.body.append(dialog);
    const $ = selector => dialog.querySelector(selector);
    let busy = false; let pending = null; let lastCategory = 'device'; let lastState = 'all';
    const binding = () => {
        const ctx = getData()?.workspaceContext;
        return ctx ? {'X-Z2MA-System-ID': ctx.systemId, 'X-Z2MA-Generation': String(ctx.generation), 'X-Z2MA-Fingerprint': ctx.fingerprint} : {};
    };
    async function send(payload, headers) {
        const endpoint = new URL('manage', location.href); endpoint.hash = '';
        const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 15000);
        try {
            const response = await fetch(endpoint, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-Z2MA-Read-Token': location.hash.slice(1), ...headers},
                body: JSON.stringify(payload), credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const text = await response.text(); if (text.length > 65536) throw new Error('transport');
            return {ok: response.ok, result: JSON.parse(text)};
        } finally { clearTimeout(timeout); }
    }
    const showUsers = users => {
        $('.management-users').replaceChildren(...users.map(user => { const item = document.createElement('li'); item.textContent = user; return item; }));
    };
    function open(operation) {
        if (busy || isLoading() || !getData()?.manageAllowed || !getEntry()) return;
        const entry = getEntry();
        pending = {payload: {requestId: crypto.randomUUID(), context: getData().createContext, operation, id: entry.id}, headers: binding()};
        const users = operation === 'delete-sequence' ? sequenceUsers(getData().configuration, entry.id) : [];
        $('#management-title').textContent = operation === 'copy-rule' ? 'Automation kopieren' : operation === 'delete-sequence' ? 'Aktionsfolge löschen' : 'Automation löschen';
        $('.management-description').textContent = users.length ? `„${entry.name}“ wird noch von folgenden Automationen verwendet. Entfernen Sie zuerst dort den Folgenaufruf. Die Aktionsfolge bleibt erhalten.`
            : operation === 'copy-rule' ? `„${entry.name}“ als neue, ausgeschaltete Automation zum lokalen Entwurf hinzufügen?`
            : `„${entry.name}“ aus dem lokalen Entwurf löschen? Der bisherige Z2M-Stand läuft weiter, bis Sie einen geänderten Entwurf ausdrücklich übertragen.`;
        showUsers(users.map(rule => rule.name)); $('.creation-feedback').textContent = '';
        $('.management-accept').hidden = users.length > 0;
        $('.management-accept').textContent = operation === 'copy-rule' ? 'Ausgeschaltete Kopie anlegen' : 'Aus Entwurf löschen';
        dialog.showModal();
    }
    $('.management-cancel').addEventListener('click', () => { if (!busy) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    $('.management-accept').addEventListener('click', async () => {
        if (busy || !pending) return;
        busy = true; $('.management-accept').disabled = true; $('.management-cancel').disabled = true;
        $('.creation-feedback').textContent = 'Die Änderung wird nur im lokalen Entwurf geprüft und übernommen …';
        try {
            const {ok, result} = await send(pending.payload, pending.headers);
            if (!ok) { $('.creation-feedback').textContent = result.message || 'Die Änderung wurde nicht übernommen.'; showUsers(result.users ?? []); return; }
            dialog.close();
            let refreshed;
            if (result.operation === 'copy-rule') { filter.value = 'all'; filterChanged('all'); refreshed = await copied(result.ruleId); }
            else refreshed = await reload();
            message(result.message + (refreshed === false ? ' Die Ansicht konnte noch nicht nachgeladen werden. Bitte „Ansicht aktualisieren“ wählen.' : ''));
        } catch { $('.creation-feedback').textContent = 'Keine Bestätigung vom Modul erhalten. Sie können denselben unveränderten Auftrag erneut bestätigen oder abbrechen und die Ansicht aktualisieren.'; }
        finally { busy = false; $('.management-accept').disabled = false; $('.management-cancel').disabled = false; refresh(lastCategory, lastState); }
    });
    filter.addEventListener('change', async () => {
        filterChanged(filter.value);
        if (!getData()?.manageAllowed || isLoading()) return;
        const payload = {requestId: crypto.randomUUID(), context: getData().createContext, operation: 'filter', id: filter.value};
        try { const {ok, result} = await send(payload, binding()); message(ok ? 'Übersicht gefiltert. „Aktiviert“ bezieht sich auf den Regelschalter im Entwurf.' : result.message); }
        catch { message('Die Übersicht wurde lokal gefiltert. Die Bestätigung für das Modul-Debug fehlt; Entwurf und Betrieb bleiben erhalten.'); }
    });
    function refresh(category, state) {
        lastCategory = category; lastState = state;
        const entry = getEntry(); const rule = Boolean(entry?.triggers);
        copy.hidden = !rule; copy.disabled = busy || isLoading() || !getData()?.manageAllowed;
        remove.hidden = !rule && entry?.kind !== 'sequence'; remove.disabled = busy || isLoading() || !getData()?.manageAllowed;
        remove.textContent = entry?.kind === 'sequence' ? 'Aktionsfolge löschen' : 'Automation löschen';
        filterLabel.hidden = !['device', 'time'].includes(category); filter.value = state;
    }
    return {refresh};
}
