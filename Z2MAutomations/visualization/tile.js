/* Native HTML SDK adapter. No credentials, MQTT calls or writes to drafts. */
(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const bootstrap = window.Z2MATile;
    const nonce = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
    let client = ''; let pending = null; let timer = null; let selected = ''; let state = null; let access = null;
    const feedback = text => { $('feedback').textContent = text; };
    function buttons() {
        $('system').disabled = !!pending || !state?.systems.length;
        $('refresh').disabled = !!pending;
        $('open').disabled = !!pending || !selected;
        $('renew').disabled = !!pending || !selected;
    }
    function forgetAccess() {
        access = null; $('designer-link').hidden = true; $('designer-link').removeAttribute('href');
    }
    function send(action, popup = null) {
        if (pending) { popup?.close(); return; }
        try {
            if (!client) client = nonce();
            const request = nonce();
            if (typeof requestAction !== 'function') throw new Error();
            pending = {request, action, popup}; buttons();
            feedback(action === 'access' ? 'Designerzugang wird vorbereitet …' : 'Modulstatus wird gelesen …');
            timer = setTimeout(() => {
                pending?.popup?.close(); pending = null; buttons();
                feedback('Das Modul hat noch nicht geantwortet. Bitte den Status erneut aktualisieren oder die Systemzuordnung im Modul prüfen.');
            }, 10000);
            requestAction('DesignerTile', JSON.stringify({client, request, action, systemId: selected}));
        } catch {
            clearTimeout(timer); pending = null; popup?.close(); buttons();
            feedback('Die Symcon-Verbindung der Kachel ist noch nicht verfügbar. Bitte „Status aktualisieren“ wählen oder die Kachel erneut öffnen.');
        }
    }
    function render(value) {
        state = value; selected = value.selectedSystem;
        $('system').replaceChildren();
        for (const item of value.systems) {
            const option = document.createElement('option'); option.value = item.systemId;
            option.textContent = `${item.name} · ${item.baseTopic}`; $('system').append(option);
        }
        $('system').value = selected;
        const item = value.systems.find(system => system.systemId === selected);
        $('status').textContent = item?.status ?? 'Bitte zuerst Z2M-Verbindungen in der Zentrale zuordnen.';
        $('rules').textContent = item?.rules ?? '—'; $('sequences').textContent = item?.sequences ?? '—';
        $('rules-label').textContent = item?.rules === 1 ? 'Automation' : 'Automationen';
        $('sequences-label').textContent = item?.sequences === 1 ? 'Aktionsfolge' : 'Aktionsfolgen';
        $('input').textContent = item?.openInput ? 'Im nativen Formular gibt es noch offene Änderungen.' : '';
        $('read-at').textContent = 'Vorhandene Moduldaten, gelesen um ' + new Date(value.readAt * 1000).toLocaleTimeString('de-DE') + ' · keine Live-Abfrage';
    }
    window.handleMessage = function (data) {
        if (data?.type !== 'z2ma-tile' || data.version !== 1 || data.client !== client || !pending
            || data.request !== pending.request || data.action !== pending.action) return;
        const current = pending; pending = null; clearTimeout(timer);
        if (!data.ok) {
            current.popup?.close(); buttons(); feedback('Das Modul kann die Ansicht derzeit nicht bereitstellen. Bitte Systemzuordnung und Modul-Debug prüfen.'); return;
        }
        try {
            if (!Array.isArray(data.state?.systems) || typeof data.state.selectedSystem !== 'string') throw new Error();
            render(data.state);
            if (current.action === 'access') {
                const origin = window.SymconHTTPOrigin.resolve(window);
                const entry = data.access;
                if (!origin || entry?.path !== bootstrap.path || entry.systemId !== selected || !/^[a-f0-9]{64}$/.test(entry.token)
                    || !Number.isInteger(entry.expires) || entry.expires * 1000 <= Date.now()) throw new Error();
                access = new URL(entry.path, origin); access.hash = entry.token;
                $('designer-link').href = access.href; $('designer-link').hidden = false;
                current.popup?.location.replace(access.href);
                feedback(current.popup ? 'Designer im neuen Fenster geöffnet. Die Kachel bleibt bei diesem System.' : 'Zugang erneuert. Den Designer über den Link öffnen; bereits offene Designerfenster bleiben unverändert.');
            } else feedback('Statusanzeige aktualisiert. Entwürfe und Automatikbetrieb bleiben unverändert.');
        } catch {
            current.popup?.close(); forgetAccess(); feedback('Der Designerzugang konnte nicht geöffnet werden. Bitte die Kachel im Symcon-Browserzugang erneut öffnen.');
        }
        buttons();
    };
    $('system').addEventListener('change', () => { selected = $('system').value; forgetAccess(); send('status'); });
    $('refresh').addEventListener('click', () => send('status'));
    $('renew').addEventListener('click', () => { forgetAccess(); send('access'); });
    $('open').addEventListener('click', () => {
        let popup = null;
        try { popup = window.open('about:blank', '_blank'); if (popup) popup.opener = null; } catch { /* The explicit link remains available. */ }
        forgetAccess(); send('access', popup);
    });
    document.addEventListener('visibilitychange', () => { if (!document.hidden && !pending) send('status'); });
    window.addEventListener('pagehide', () => { clearTimeout(timer); pending?.popup?.close(); pending = null; });
    send('status');
})();
