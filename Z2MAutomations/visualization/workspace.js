import {createUUID} from './identity.js';
/* Explicit system identity accompanies every local draft write. */
export function systemHeaders(data) {
    const context = data?.workspaceContext;
    return context ? {'X-Z2MA-System-ID': context.systemId, 'X-Z2MA-Generation': String(context.generation), 'X-Z2MA-Fingerprint': context.fingerprint} : {};
}

export async function loadSystemDevices(data, {token, signal, base = location.href, request = fetch, onProgress = () => {}}) {
    while (data.devicePage?.hasMore) {
        const offset = data.devices.length;
        const endpoint = new URL('devices', base); endpoint.hash = '';
        onProgress(offset, data.devicePage.total);
        const controller = new AbortController(); const abort = () => controller.abort();
        signal?.addEventListener('abort', abort, {once: true});
        if (signal?.aborted) controller.abort();
        const timeout = setTimeout(abort, 10000);
        let page;
        try {
            const response = await request(endpoint, {headers: {'X-Z2MA-Read-Token': token, ...systemHeaders(data), 'X-Z2MA-Workspace-Fingerprint': data.workspace?.fingerprint ?? '', 'X-Z2MA-Offset': String(offset)},
                signal: controller.signal, credentials: 'same-origin', cache: 'no-store', redirect: 'error'});
            if (!response.ok) throw new Error('catalog');
            page = await response.json();
        } finally { clearTimeout(timeout); signal?.removeEventListener('abort', abort); }
        if (page.workspaceContext?.systemId !== data.workspaceContext.systemId || page.workspaceContext.fingerprint !== data.workspaceContext.fingerprint
            || page.devicePage?.offset !== offset || page.devicePage.total !== data.devicePage.total || !Array.isArray(page.devices) || !page.devices.length) throw new Error('catalog');
        data.devices.push(...page.devices); data.devicePage = page.devicePage;
    }
    return data;
}

export function installSystemSelection({getData, select, message, isLoading = () => false}) {
    const row = document.createElement('div'); row.className = 'system-selector'; row.hidden = true;
    const label = document.createElement('label'); label.textContent = 'Z2M-System';
    const picker = document.createElement('select'); picker.id = 'designer-system'; label.htmlFor = picker.id;
    const note = document.createElement('span'); note.textContent = 'Eigener Entwurf je System · Bearbeiten verändert Z2M nicht';
    row.append(label, picker, note); document.querySelector('.preview-notice').after(row);
    let busy = false;
    async function choose(systemId) {
        const data = getData(); const previous = data?.workspaceContext?.systemId ?? '';
        if (busy || isLoading() || document.querySelector('dialog[open]')) {
            picker.value = previous; message('Bitte die offene Bearbeitung zuerst übernehmen oder schließen. Ihre Eingaben bleiben erhalten.'); return false;
        }
        busy = true; picker.disabled = true;
        const controller = new AbortController(); const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const endpoint = new URL('select-system', location.href); endpoint.hash = '';
            const response = await fetch(endpoint, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-Z2MA-Read-Token': location.hash.slice(1)},
                body: JSON.stringify({systemId, fingerprint: data.workspace.fingerprint, requestId: createUUID()}),
                credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || 'System konnte nicht gewechselt werden. Bitte die Ansicht aktualisieren.');
            await select(systemId);
            return true;
        } catch (error) { picker.value = previous; message(error.name === 'AbortError' ? 'Systemwechsel nicht bestätigt. Bitte die Ansicht aktualisieren.' : error.message); return false; }
        finally { clearTimeout(timeout); busy = false; picker.disabled = false; }
    }
    picker.addEventListener('change', () => choose(picker.value));
    const update = data => {
        row.hidden = !data?.workspace;
        picker.replaceChildren(...(data?.workspace?.systems ?? []).map(system => new Option(`${system.name} · ${system.binding.baseTopic}`, system.binding.systemId)));
        picker.value = data?.workspaceContext?.systemId ?? '';
    };
    update.choose = choose;
    return update;
}
