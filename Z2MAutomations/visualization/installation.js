import {systemHeaders} from './workspace.js';

const digest = value => typeof value === 'string' && /^[a-f0-9]{64}$/.test(value);
export function checkedInstallation(v, ctx) {
    return v?.systemId === ctx?.systemId && v?.generation === ctx?.generation && digest(v.fingerprint) && digest(v.context)
        && ['idle', 'checking', 'prepared', 'pending', 'installed', 'error', 'interrupted'].includes(v.status)
        && ['pending', 'canInstall', 'mayHaveUploaded'].every(key => typeof v[key] === 'boolean')
        && ['message', 'blocked', 'runtimeMessage', 'notice'].every(key => typeof v[key] === 'string')
        && [v.bundledBuild, v.installedBuild].every(value => value === null || digest(value))
        && (v.executing === null || typeof v.executing === 'boolean');
}

export async function installationRequest(data, payload, {token, base = location.href, request = fetch} = {}) {
    const ctx = data?.workspaceContext;
    if (!ctx) throw new Error('Bitte zuerst ein Z2M-System auswählen.');
    const endpoint = new URL(payload ? 'installation' : 'installation-status', base); endpoint.hash = '';
    const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 10000);
    try {
        let response; let result;
        try {
            response = await request(endpoint, {method: payload ? 'POST' : 'GET', headers: {'X-Z2MA-Read-Token': token, ...systemHeaders(data), ...(payload ? {'Content-Type': 'application/json'} : {})},
                ...(payload ? {body: JSON.stringify(payload)} : {}), credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const text = await response.text(); if (text.length > 16384) throw Error('response_limit'); result = JSON.parse(text);
        } catch { throw new Error('Keine eindeutige Installationsrückmeldung erhalten. Die Datei wird nicht erneut gesendet. Bitte den Diagnosestand neu laden.'); }
        if (!response.ok || !result.ok) throw new Error(result.message || 'Installation nicht möglich. Bitte den Diagnosestand neu laden.');
        if (!checkedInstallation(result.installation, ctx)) throw new Error('Die Installationsantwort passt nicht zum gewählten System. Bitte den Diagnosestand neu laden.');
        return result;
    } finally { clearTimeout(timeout); }
}

/** A separate, explicit consent pins the displayed system and artifact context. */
export function installExtensionControl({container, getData, getResult, isBusy, onResult, onError, onBusy}) {
    const action = document.createElement('button'); action.type = 'button'; action.className = 'installation-start';
    action.textContent = 'Mitgelieferte Erweiterung auf diesem System installieren oder aktualisieren';
    const status = document.createElement('p'); status.className = 'installation-status'; status.setAttribute('role', 'status'); status.setAttribute('aria-live', 'polite');
    const runtime = document.createElement('p'); runtime.className = 'installation-runtime';
    container.append(status, runtime, action);
    const dialog = document.createElement('dialog'); dialog.className = 'creation-dialog installation-confirm'; dialog.id = 'installation-confirm';
    dialog.setAttribute('aria-labelledby', 'installation-title');
    dialog.innerHTML = '<h2 id="installation-title">Erweiterung installieren oder aktualisieren?</h2><p class="installation-target"></p><dl class="installation-builds"></dl><p class="installation-notice"></p>'
        + '<p>Die Bestätigung gilt ausschließlich für dieses System und die hier angezeigte mitgelieferte Datei.</p><div class="dialog-actions"><button type="button" class="installation-cancel">Abbrechen</button><button type="button" class="installation-accept primary">Installation bestätigen</button></div>';
    document.body.append(dialog);
    let captured = null; let submitting = false;
    const $ = selector => dialog.querySelector(selector);
    const current = snapshot => snapshot?.workspaceContext?.systemId === getData()?.workspaceContext?.systemId && snapshot?.workspaceContext?.generation === getData()?.workspaceContext?.generation;
    function paint() {
        const v = getResult()?.installation;
        action.hidden = !getData()?.installationAllowed;
        action.disabled = isBusy() || !v?.canInstall || !current(getData());
        status.textContent = v?.message ?? 'Installationsstand noch nicht verfügbar. Bitte den Diagnosestand neu laden.';
        status.dataset.phase = v?.status ?? 'idle'; runtime.textContent = v?.runtimeMessage ?? '';
    }
    action.addEventListener('click', () => {
        const result = getResult(); const v = result?.installation; const data = getData();
        if (isBusy() || !data?.installationAllowed || !v?.canInstall || !checkedInstallation(v, data.workspaceContext)) return;
        captured = {data: {...data, workspaceContext: {...data.workspaceContext, fingerprint: v.fingerprint}}, context: v.context, requestId: crypto.randomUUID()};
        const row = result.diagnostics;
        $('.installation-target').textContent = `${row.name} · Basistopic ${row.baseTopic} · Z2M-Verbindung #${row.connection} · MQTT-Gateway #${row.mqttParent}`;
        const builds = $('.installation-builds'); builds.replaceChildren();
        for (const [label, value] of [['Installierter Build', v.installedBuild], ['Mitgelieferter Build', v.bundledBuild]]) {
            const term = document.createElement('dt'); term.textContent = label; const description = document.createElement('dd'); description.textContent = value ?? 'Noch nicht bestätigt'; builds.append(term, description);
        }
        $('.installation-notice').textContent = v.notice;
        $('.installation-accept').disabled = false; dialog.showModal();
    });
    $('.installation-cancel').addEventListener('click', () => dialog.close());
    $('.installation-accept').addEventListener('click', async () => {
        const intent = captured;
        if (submitting || !intent || !current(intent.data)) return;
        submitting = true; $('.installation-accept').disabled = true; onBusy(true); dialog.close();
        try {
            const result = await installationRequest(intent.data, {requestId: intent.requestId, context: intent.context, confirmed: true}, {token: location.hash.slice(1)});
            if (current(intent.data)) onResult(result.installation);
        } catch (error) { if (current(intent.data)) onError(error.message); }
        finally {submitting = false; captured = null; onBusy(false); paint();}
    });
    dialog.addEventListener('close', () => {if (!submitting) captured = null;});
    return paint;
}
