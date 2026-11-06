/* Refresh the same scoped capability; never reload editors or retry their writes. */
export function installAccessRenewal(environment = window) {
    const {document, location} = environment;
    const token = location.hash.slice(1);
    if (!/^[a-f0-9]{64}$/.test(token)) return;
    const endpoint = new URL('access-renew', location.href); endpoint.hash = '';
    const panel = document.createElement('aside'); panel.className = 'designer-access';
    const label = document.createElement('span'); label.setAttribute('role', 'status');
    label.textContent = 'Designerzugang wird bei geöffneter Ansicht automatisch verlängert.';
    const button = document.createElement('button'); button.type = 'button'; button.textContent = 'Zugang verlängern';
    panel.append(label, button); document.body.append(panel);
    let busy = false; let stopped = false;
    async function renew() {
        if (busy || stopped) return;
        busy = true; button.disabled = true;
        try {
            const response = await environment.fetch(endpoint, {method: 'POST', cache: 'no-store', credentials: 'same-origin', redirect: 'error',
                headers: {'Content-Type': 'application/json', 'X-Z2MA-Read-Token': token}, body: '{}', signal: AbortSignal.timeout(10000)});
            if (!response.ok) {
                if (response.status === 403) stopped = true;
                throw new Error();
            }
            const result = await response.json();
            if (result.ok !== true || !Number.isInteger(result.expires)) throw new Error();
            label.textContent = 'Designerzugang verlängert bis ' + new Date(result.expires * 1000).toLocaleTimeString('de-DE') + '. Offene Eingaben bleiben erhalten.';
            panel.dataset.tone = 'ok';
        } catch {
            label.textContent = stopped ? 'Der Designerzugang ist abgelaufen. Offene Eingaben bleiben sichtbar. Bitte den Designer über das Modul oder die Kachel erneut öffnen.'
                : 'Der Zugang konnte noch nicht verlängert werden. Bitte die Verbindung prüfen und erneut versuchen; offene Eingaben bleiben erhalten.';
            panel.dataset.tone = 'error';
        } finally { busy = false; button.disabled = stopped; }
    }
    button.addEventListener('click', renew);
    const timer = environment.setInterval(() => { if (!document.hidden) void renew(); }, 240000);
    const visible = () => { if (!document.hidden) void renew(); };
    document.addEventListener('visibilitychange', visible);
    environment.addEventListener('pagehide', event => { if (event.persisted) return; stopped = true; environment.clearInterval(timer); document.removeEventListener('visibilitychange', visible); });
    return {renew};
}
