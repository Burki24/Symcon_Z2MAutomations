import {createUUID} from './identity.js';
import {systemHeaders} from './workspace.js';

const digest = value => typeof value === 'string' && /^[a-f0-9]{64}$/.test(value);
const topic = value => typeof value === 'string' && value.length <= 128 && /^[A-Za-z0-9_-]+(?:\/[A-Za-z0-9_-]+)*$/.test(value);
export function checkedPermissions(view, context) {
    return !!view && view.systemId === context?.systemId && view.generation === context?.generation && digest(view.fingerprint) && digest(view.context)
        && ['name', 'message', 'notice', 'blocked'].every(key => typeof view[key] === 'string') && topic(view.baseTopic) && typeof view.canEdit === 'boolean'
        && Array.isArray(view.peers) && view.peers.length <= 8 && view.peers.every(topic) && new Set(view.peers).size === view.peers.length
        && Array.isArray(view.choices) && view.choices.length <= 15 && view.choices.every(row => row && typeof row === 'object')
        && new Set(view.choices.map(row => row.topic)).size === view.choices.length
        && view.choices.every(row => topic(row.topic) && typeof row.systemId === 'string' && typeof row.name === 'string' && typeof row.assigned === 'boolean'
            && typeof row.selected === 'boolean' && row.selected === view.peers.includes(row.topic) && Number.isSafeInteger(row.affectedCount) && row.affectedCount >= 0 && row.affectedCount <= 100
            && Array.isArray(row.affected) && row.affected.length === Math.min(20, row.affectedCount)
            && row.affected.every(rule => rule && typeof rule.id === 'string' && typeof rule.name === 'string' && typeof rule.enabled === 'boolean'))
        && view.peers.every(peer => view.choices.some(row => row.topic === peer));
}

export function permissionChanges(view, selected) {
    if (!Array.isArray(selected) || selected.length > 8 || new Set(selected).size !== selected.length || selected.some(peer => !view.choices.some(row => row.topic === peer))) {
        throw new Error('Bitte höchstens acht angezeigte Partnersysteme auswählen.');
    }
    return {added: view.choices.filter(row => selected.includes(row.topic) && !row.selected), removed: view.choices.filter(row => row.selected && !selected.includes(row.topic))};
}

export async function permissionsRequest(data, payload, {token, base = location.href, request = fetch} = {}) {
    const context = data?.workspaceContext;
    if (!context) throw new Error('Bitte zuerst das empfangende Z2M-System auswählen.');
    const endpoint = new URL(payload ? 'permissions' : 'permissions-status', base); endpoint.hash = '';
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        let response; let result;
        try {
            response = await request(endpoint, {method: payload ? 'POST' : 'GET', headers: {'X-Z2MA-Read-Token': token, ...systemHeaders(data), ...(payload ? {'Content-Type': 'application/json'} : {})},
                ...(payload ? {body: JSON.stringify(payload)} : {}), credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const text = await response.text(); if (text.length > (payload ? 16384 : 262144)) throw Error('response_limit'); result = JSON.parse(text);
        } catch { throw new Error('Keine eindeutige Rückmeldung erhalten. Es wird nicht automatisch erneut gesendet. Bitte den Stand neu laden oder denselben Auftrag ausdrücklich erneut bestätigen.'); }
        if (!response.ok || !result?.ok) throw new Error(typeof result?.message === 'string' ? result.message : 'Die Partnerfreigaben konnten nicht übernommen werden. Bitte den Stand neu laden.');
        if (payload ? result.permissionsSaved !== true || result.systemId !== context.systemId || result.generation !== context.generation || !digest(result.fingerprint)
            || typeof result.changed !== 'boolean' || !Number.isSafeInteger(result.peerCount) || result.peerCount < 0 || result.peerCount > 8 || typeof result.notice !== 'string'
            : !checkedPermissions(result.permissions, context)) throw new Error('Die Rückmeldung passt nicht zum gewählten System. Bitte den Stand neu laden.');
        return payload ? result : result.permissions;
    } finally { clearTimeout(timeout); }
}

/** Receiver grants senders access; changing a grant never sends MQTT or alters a partner document. */
export function installPermissions({getData, reload, message, isLoading = () => false}) {
    const button = document.createElement('button'); button.type = 'button'; button.className = 'permissions-open'; button.textContent = 'Partnerfreigaben';
    document.querySelector('.toolbar').append(button);
    const dialog = document.createElement('dialog'); dialog.id = 'permissions-dialog'; dialog.className = 'creation-dialog permissions-dialog';
    dialog.setAttribute('aria-labelledby', 'permissions-title');
    dialog.innerHTML = '<h2 id="permissions-title">Partnerfreigaben</h2><p class="permissions-target"></p><p class="permissions-notice"></p>'
        + '<div class="permissions-edit"><p>Von welchen Systemen darf dieses System Befehle empfangen?</p><div class="permissions-choices"></div></div>'
        + '<section class="permissions-review" hidden><h3>Diese Änderungen übernehmen?</h3><div class="permissions-changes"></div></section>'
        + '<p class="permissions-feedback" role="status" aria-live="polite"></p><div class="dialog-actions"><button type="button" class="permissions-cancel">Abbrechen</button>'
        + '<button type="button" class="permissions-back" hidden>Auswahl ändern</button><button type="button" class="permissions-check">Änderungen prüfen</button>'
        + '<button type="button" class="permissions-accept primary" hidden>Freigaben im Entwurf übernehmen</button></div>';
    document.body.append(dialog);
    const $ = selector => dialog.querySelector(selector);
    let busy = false; let captured = null; let view = null; let pending = null;
    const current = data => data?.workspaceContext?.systemId === getData()?.workspaceContext?.systemId && data?.workspaceContext?.generation === getData()?.workspaceContext?.generation;
    const feedback = text => { $('.permissions-feedback').textContent = text; };
    function paint() {
        const data = getData(); button.hidden = !data?.workspace; button.disabled = busy || isLoading() || !data?.permissionsAllowed || !data?.workspaceContext;
        dialog.setAttribute('aria-busy', String(busy));
        for (const selector of ['.permissions-cancel', '.permissions-back', '.permissions-check', '.permissions-accept']) $(selector).disabled = busy;
        if (view && !view.canEdit) $('.permissions-check').disabled = true;
        dialog.querySelectorAll('input').forEach(input => { input.disabled = busy || !view?.canEdit; });
    }
    const paragraph = (parent, text) => { const item = document.createElement('p'); item.textContent = text; parent.append(item); };
    function stage(review) {
        $('.permissions-edit').hidden = review; $('.permissions-review').hidden = !review;
        $('.permissions-check').hidden = review; $('.permissions-back').hidden = !review; $('.permissions-accept').hidden = !review;
    }
    button.addEventListener('click', async () => {
        if (busy || isLoading() || !getData()?.permissionsAllowed || document.querySelector('dialog[open]')) return;
        captured = structuredClone(getData()); view = null; pending = null; busy = true; stage(false); feedback('Partnerfreigaben werden gelesen …');
        $('.permissions-target').textContent = `Empfangendes System: ${captured.module?.name ?? ''} · ${captured.module?.baseTopic ?? ''}`;
        $('.permissions-notice').textContent = ''; $('.permissions-choices').replaceChildren(); dialog.showModal(); paint();
        try {
            const loaded = await permissionsRequest(captured, null, {token: location.hash.slice(1)});
            if (!current(captured)) { dialog.close(); return; }
            view = loaded; captured.workspaceContext.fingerprint = view.fingerprint;
            $('.permissions-target').textContent = `Empfangendes System: ${view.name} · ${view.baseTopic}`;
            $('.permissions-notice').textContent = view.notice;
            for (const row of view.choices) {
                const label = document.createElement('label'); label.className = 'permissions-choice';
                const input = document.createElement('input'); input.type = 'checkbox'; input.value = row.topic; input.checked = row.selected;
                const caption = document.createElement('span'); caption.textContent = `${row.name} · ${row.topic} → ${view.name} · ${view.baseTopic}`;
                label.append(input, caption); $('.permissions-choices').append(label);
                if (!row.assigned) paragraph($('.permissions-choices'), 'Dieses bisher freigegebene System ist hier nicht zugeordnet. Seine Regeln können nicht geprüft werden. Ohne Änderung bleibt die Freigabe erhalten.');
            }
            if (!view.choices.length) paragraph($('.permissions-choices'), 'Noch kein weiteres System zugeordnet.');
            feedback(view.message);
        } catch (error) { feedback(error.message); } finally { busy = false; paint(); if (!view) $('.permissions-check').disabled = true; }
    });
    $('.permissions-check').addEventListener('click', () => {
        if (busy || !view?.canEdit || !current(captured)) return;
        try {
            const peers = [...dialog.querySelectorAll('input:checked')].map(input => input.value);
            const changes = permissionChanges(view, peers); const content = $('.permissions-changes'); content.replaceChildren();
            for (const row of changes.added) paragraph(content, `Neu erlauben: ${row.name} · ${row.topic} → ${view.name} · ${view.baseTopic}`);
            for (const row of changes.removed) {
                paragraph(content, `Freigabe entziehen: ${row.name} · ${row.topic} → ${view.name} · ${view.baseTopic}`);
                paragraph(content, row.assigned ? (row.affectedCount === 1 ? '1 lokale Automation verwendet diese Verbindung.' : `${row.affectedCount} lokale Automationen verwenden diese Verbindung.`)
                    + ' Nach Übertragung des Entzugs werden ihre Befehle abgelehnt.' : 'Nicht zugeordnetes System: Betroffene Automationen sind unbekannt.');
                for (const rule of row.affected) paragraph(content, `• ${rule.name} (im Entwurf ${rule.enabled ? 'eingeschaltet' : 'ausgeschaltet'})`);
                if (row.affectedCount > row.affected.length) {
                    const remaining = row.affectedCount - row.affected.length;
                    paragraph(content, remaining === 1 ? 'Eine weitere Automation ist betroffen.' : `Weitere ${remaining} Automationen sind betroffen.`);
                }
            }
            if (!changes.added.length && !changes.removed.length) paragraph(content, 'Die Auswahl ist unverändert. Der Entwurf wird nicht neu geschrieben.');
            pending = {requestId: createUUID(), context: view.context, peers, confirmed: true}; stage(true); feedback('Nur der lokale Entwurf des empfangenden Systems wird geändert. Der laufende Betrieb bleibt unverändert.');
        } catch (error) { feedback(error.message); }
    });
    $('.permissions-back').addEventListener('click', () => { if (!busy) { pending = null; stage(false); feedback(view.message); } });
    $('.permissions-cancel').addEventListener('click', () => { if (!busy) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    $('.permissions-accept').addEventListener('click', async () => {
        if (busy || !pending || !current(captured)) return;
        busy = true; paint(); feedback('Partnerfreigaben werden im lokalen Entwurf übernommen …');
        try {
            const result = await permissionsRequest(captured, pending, {token: location.hash.slice(1)});
            if (!current(captured)) { dialog.close(); return; }
            pending = null; dialog.close(); message(result.notice);
            if (!await reload()) message(`${result.notice} Bitte „Ansicht aktualisieren“ wählen.`);
            else message(result.notice);
        } catch (error) { feedback(error.message); } finally { busy = false; paint(); }
    });
    return () => { if (dialog.open && captured && !current(captured)) dialog.close(); paint(); };
}
