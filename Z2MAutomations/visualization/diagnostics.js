import {createUUID} from './identity.js';
import {systemHeaders} from './workspace.js';
import {checkedInstallation, installExtensionControl} from './installation.js';

const digest = value => typeof value === 'string' && /^[a-f0-9]{64}$/.test(value);
const integer = value => Number.isSafeInteger(value) && value >= 0;
function checkedRow(v) {
    const b = v?.build; const d = v?.debug;
    return typeof v?.systemId === 'string' && integer(v.generation) && v.generation > 0 && integer(v.connection) && integer(v.mqttParent)
        && ['name', 'baseTopic', 'healthText'].every(key => typeof v[key] === 'string') && typeof v.mqttConnected === 'boolean' && typeof v.online === 'boolean'
        && (b === null || (['current', 'different', 'legacy', 'missing', 'unconfirmed'].includes(b?.comparison)
            && [b.installed, b.bundled].every(value => value === null || digest(value))
            && ['InstalledExtensionInfo', 'BundledExtensionInfo', 'ExtensionUpdateInfo'].every(key => typeof b.fields?.[key] === 'string')))
        && ['idle', 'starting', 'active', 'stopping'].includes(d?.phase) && typeof d.active === 'boolean' && typeof d.pending === 'boolean'
        && typeof d.canStart === 'boolean' && typeof d.canStop === 'boolean' && typeof d.message === 'string' && integer(d.remaining)
        && (d.until === null || integer(d.until));
}

export async function diagnosticsRequest(data, payload, {token, base = location.href, request = fetch} = {}) {
    const ctx = data?.workspaceContext;
    if (!ctx) throw new Error('Bitte zuerst ein Z2M-System auswählen.');
    const endpoint = new URL(payload ? 'diagnostics' : 'diagnostics-status', base); endpoint.hash = '';
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        let response; let result;
        try {
            response = await request(endpoint, {method: payload ? 'POST' : 'GET', headers: {'X-Z2MA-Read-Token': token, ...systemHeaders(data), ...(payload ? {'Content-Type': 'application/json'} : {})},
                ...(payload ? {body: JSON.stringify(payload)} : {}), credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const text = await response.text(); if (text.length > 32768) throw Error('response_limit'); result = JSON.parse(text);
        } catch { throw new Error('Keine eindeutige Diagnoserückmeldung erhalten. Der Auftrag wird nicht erneut gesendet. Bitte „Diagnosestand neu laden“ wählen.'); }
        if (!response.ok || !result.ok) throw new Error(result.message || 'Diagnose nicht verfügbar. Bitte den Designer erneut im Modul öffnen.');
        const v = result.diagnostics;
        if (v?.systemId !== ctx.systemId || v.generation !== ctx.generation || !checkedRow(v) || !digest(v.fingerprint) || !digest(v.context)
            || !['idle', 'pending', 'confirmed', 'error'].includes(v.phase) || typeof v.pending !== 'boolean' || typeof v.canCheck !== 'boolean'
            || typeof v.message !== 'string' || typeof v.z2mVersion !== 'string' || typeof v.lastError !== 'string' || !(v.requestedAt === null || integer(v.requestedAt))
            || !Array.isArray(result.systems) || result.systems.length > 8 || !result.systems.every(checkedRow)
            || new Set(result.systems.map(row => row.systemId)).size !== result.systems.length
            || (result.installation !== undefined && !checkedInstallation(result.installation, ctx))) {
            throw new Error('Die Diagnoseantwort gehört nicht zum gewählten System oder ist unvollständig. Bitte den Diagnosestand neu laden.');
        }
        return result;
    } finally { clearTimeout(timeout); }
}

export function recordingEnd(debug) {
    return debug.until === null ? 'Keine laufende Endzeit.' : `Endet automatisch am ${new Date(debug.until * 1000).toLocaleString('de-DE')}.`;
}

export function buildHint(build) {
    if (build?.comparison === 'different') return 'Update verfügbar: Installierter und mitgelieferter Erweiterungsstand unterscheiden sich.';
    if (build?.comparison === 'legacy') return 'Kein Buildvergleich möglich: Die installierte Erweiterung meldet noch keine Buildkennung.';
    return build?.fields?.ExtensionUpdateInfo ?? 'Bitte den Erweiterungsstatus abfragen oder das Verbindungsmodul aktualisieren.';
}

export function installDiagnostics({getData, isLoading = () => false, chooseSystem, message, reload = async () => {}}) {
    const button = document.createElement('button'); button.id = 'diagnostics-button'; button.textContent = 'Systeme und Diagnose';
    document.querySelector('.toolbar').append(button);
    const dialog = document.createElement('dialog'); dialog.id = 'diagnostics-dialog'; dialog.className = 'creation-dialog diagnostics-dialog'; dialog.setAttribute('aria-labelledby', 'diagnostics-title');
    dialog.innerHTML = '<h2 id="diagnostics-title">Systeme und Diagnose</h2>'
        + '<p>Hier sehen Sie die zuletzt in Symcon bestätigten Daten. Für eine aktuelle Prüfung einen der Prüfbuttons wählen. Eine Installation der Erweiterung erfordert eine zusätzliche Bestätigung und kann laufende Abläufe unterbrechen.</p>'
        + '<div class="diagnostics-table"><table><thead><tr><th>Z2M-System</th><th>MQTT-Verbindung</th><th>Zigbee2MQTT</th><th>Erweiterungsstand</th><th>Aufzeichnung</th></tr></thead><tbody></tbody></table></div>'
        + '<h3 class="diagnostics-system"></h3><dl class="diagnostics-facts"></dl>'
        + '<section class="diagnostics-build" aria-label="Erweiterungsstände"><h3>Erweiterungsstände</h3><p class="installed"></p><p class="bundled"></p><p class="comparison"></p><p class="field-help">Die mitgelieferte Erweiterung lässt sich nach gesonderter Bestätigung für das ausgewählte System installieren. Laufende Abläufe und Aufzeichnungen können dabei unterbrochen werden.</p></section>'
        + '<div class="diagnostics-checks"><button type="button" data-diagnose="health">Zigbee2MQTT-Verbindung prüfen</button><button type="button" data-diagnose="extension">Erweiterungsstatus abfragen</button></div>'
        + '<p class="diagnostics-probe" role="status" aria-live="polite"></p>'
        + '<section class="diagnostics-recording" aria-label="Ablaufaufzeichnung"><h3>Z2M-Abläufe aufzeichnen</h3><p class="debug-location"></p>'
        + '<p class="debug-message" role="status" aria-live="polite"></p><p class="debug-end"></p>'
        + '<div class="diagnostics-checks"><label>Dauer<select class="diagnostics-duration"><option value="60">1 Minute</option><option value="300" selected>5 Minuten</option><option value="600">10 Minuten</option><option value="900">15 Minuten</option></select></label>'
        + '<button type="button" data-diagnose="debug_start">Aufzeichnung starten</button><button type="button" data-diagnose="debug_stop">Eigene Aufzeichnung stoppen</button></div>'
        + '<p class="field-help">Für Aktionen zwischen zwei Z2M-Systemen auf beiden Seiten starten. Regellauf und Aktionsschritt verbinden die Meldungen. Stoppen beendet nur die Aufzeichnung dieser Verbindung; die Automationen laufen weiter.</p></section>'
        + '<p class="diagnostics-message" role="status" aria-live="polite"></p><div class="dialog-actions"><button type="button" class="diagnostics-refresh">Diagnosestand neu laden</button><button type="button" class="diagnostics-close">Schließen</button></div>';
    document.body.append(dialog);
    const $ = selector => dialog.querySelector(selector);
    const text = (tag, value) => {const node = document.createElement(tag); node.textContent = value; return node;};
    let captured = null; let result = null; let busy = false; let timer = null; let error = ''; let windowEnd = 0; let installed = false;
    const current = data => data?.workspaceContext?.systemId === getData()?.workspaceContext?.systemId && data?.workspaceContext?.generation === getData()?.workspaceContext?.generation;
    const paintInstallation = installExtensionControl({container: $('.diagnostics-build'), getData: () => current(captured) ? captured : null, getResult: () => result,
        isBusy: () => busy || isLoading() || !!error || !current(captured),
        onResult: v => {if (result) result.installation = v; message(v.message);}, onError: value => {error = value;},
        onBusy: value => {busy = value; if (value) clearTimeout(timer); paint(); schedule();}});
    function paint() {
        const v = result?.diagnostics;
        const rows = $('tbody'); rows.replaceChildren();
        for (const row of result?.systems ?? []) {
            const tr = document.createElement('tr'); tr.classList.toggle('is-selected', row.systemId === v?.systemId);
            const name = document.createElement('td'); const pick = text('button', row.name); pick.type = 'button'; pick.dataset.systemId = row.systemId; pick.disabled = busy || isLoading();
            pick.addEventListener('click', () => switchSystem(row.systemId)); name.append(pick, text('small', row.baseTopic));
            const connection = text('td', row.mqttConnected ? 'MQTT verbunden' : 'MQTT nicht verbunden'); connection.append(text('small', `Verbindung #${row.connection} · MQTT-Gateway #${row.mqttParent}`));
            const online = text('td', row.online ? 'Online' : 'Nicht als online bestätigt'); online.append(text('small', row.healthText));
            const comparison = text('td', ({current: 'Aktuell', different: 'Updatehinweis vorhanden', legacy: 'Buildvergleich fehlt', missing: 'Moduldateien prüfen'})[row.build?.comparison] ?? 'Stand noch nicht bestätigt');
            const debug = text('td', row.debug.active ? 'Läuft' : row.debug.pending ? 'Bestätigung steht aus' : 'Aus');
            if (row.debug.until !== null) debug.append(text('small', recordingEnd(row.debug)));
            tr.append(name, connection, online, comparison, debug); rows.append(tr);
        }
        $('.diagnostics-system').textContent = v ? `Diagnose · ${v.baseTopic}` : 'Diagnosestand wird geladen …';
        const facts = $('.diagnostics-facts'); facts.replaceChildren();
        if (v) for (const [label, value] of [['Z2M-Verbindung', `${v.name} (#${v.connection})`], ['MQTT-Gateway', `#${v.mqttParent}`], ['Zigbee2MQTT-Version', v.z2mVersion || 'Noch nicht bestätigt'], ['Gesundheitsprüfung', v.healthText], ['Letzter Verbindungsfehler', v.lastError || 'Kein Fehler gemeldet.']]) facts.append(text('dt', label), text('dd', value));
        const fields = v?.build?.fields;
        $('.installed').textContent = fields?.InstalledExtensionInfo ?? 'Installierter Erweiterungsstand noch nicht bestätigt.';
        $('.bundled').textContent = fields?.BundledExtensionInfo ?? 'Mitgelieferten Stand im Verbindungsmodul prüfen.';
        $('.comparison').textContent = buildHint(v?.build);
        $('.diagnostics-build').dataset.comparison = v?.build?.comparison ?? 'unconfirmed';
        $('.diagnostics-probe').textContent = v?.message ?? '';
        $('.diagnostics-probe').dataset.phase = v?.phase ?? 'idle';
        $('.debug-location').textContent = v ? `Ablaufmeldungen erscheinen im nativen Debugfenster der Z2M-Verbindung „${v.name}“ (#${v.connection}). Dort „Debug“ öffnen und die Anzeige starten.` : '';
        $('.debug-message').textContent = v?.debug?.message ?? '';
        $('.debug-end').textContent = v ? recordingEnd(v.debug) : '';
        $('.diagnostics-message').textContent = error || (busy ? 'Diagnoseauftrag wird bearbeitet …' : '');
        dialog.setAttribute('aria-busy', String(busy));
        for (const action of ['health', 'extension', 'debug_start', 'debug_stop']) {
            const allowed = action === 'debug_start' ? v?.debug.canStart : action === 'debug_stop' ? v?.debug.canStop : v?.canCheck;
            $(`[data-diagnose="${action}"]`).disabled = busy || isLoading() || !current(captured) || !allowed || !!error;
        }
        $('.diagnostics-duration').disabled = busy || !!v?.debug.active || !!v?.debug.pending;
        $('.diagnostics-refresh').disabled = busy || isLoading();
        $('.diagnostics-close').disabled = busy;
        installed ||= result?.installation?.status === 'installed';
        paintInstallation();
    }
    function schedule() {
        clearTimeout(timer);
        const v = result?.diagnostics;
        if (dialog.open && !error && Date.now() < windowEnd && (v?.pending || v?.debug.active || v?.debug.pending || result?.installation?.pending)) timer = setTimeout(() => send(null), 1500);
    }
    async function send(action) {
        if (busy || isLoading() || !current(captured) || !dialog.open) return;
        const data = captured; const v = result?.diagnostics;
        if (action && !v) return;
        error = ''; busy = true; clearTimeout(timer); paint();
        try {
            const model = v ? {...data, workspaceContext: {...data.workspaceContext, fingerprint: v.fingerprint}} : data;
            const reply = await diagnosticsRequest(model, action ? {requestId: createUUID(), action, seconds: action === 'debug_start' ? Number($('.diagnostics-duration').value) : 0, context: v.context} : null, {token: location.hash.slice(1)});
            if (!dialog.open || !current(data)) return;
            result = reply;
            if (action) message(action.startsWith('debug_') ? reply.diagnostics.debug.message : reply.diagnostics.message);
        } catch (failure) { if (dialog.open && current(data)) error = failure.message; }
        finally { busy = false; paint(); schedule(); }
    }
    async function open() {
        if (busy || isLoading() || !getData()?.diagnosticsAllowed || document.querySelector('dialog[open]')) return;
        captured = getData(); result = null; error = ''; installed = false; windowEnd = Date.now() + 920000; paint(); dialog.showModal(); await send(null);
    }
    async function switchSystem(id) {
        if (busy || isLoading() || id === captured?.workspaceContext?.systemId) return;
        dialog.close(); clearTimeout(timer);
        await chooseSystem(id);
        if (!document.querySelector('dialog[open]')) await open();
    }
    button.addEventListener('click', open);
    for (const action of ['health', 'extension', 'debug_start', 'debug_stop']) $(`[data-diagnose="${action}"]`).addEventListener('click', () => send(action));
    $('.diagnostics-refresh').addEventListener('click', () => send(null));
    $('.diagnostics-close').addEventListener('click', () => dialog.close());
    dialog.addEventListener('cancel', event => {if (busy) event.preventDefault();});
    dialog.addEventListener('close', () => {clearTimeout(timer); if (installed && !busy) {installed = false; void reload();}});
    return () => {
        button.hidden = !getData()?.workspace; button.disabled = busy || isLoading() || !getData()?.diagnosticsAllowed;
        if (dialog.open && !current(captured)) {clearTimeout(timer); result = null; error = 'Das ausgewählte System hat sich geändert. Bitte die Diagnose schließen und erneut öffnen.'; paint();}
    };
}
