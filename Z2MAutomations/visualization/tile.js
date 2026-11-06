/* HTML-SDK loader: the complete existing designer stays inside its own tile. */
(() => {
    'use strict';
    const $ = id => document.getElementById(id);
    const frame = $('designer-frame');
    const channel = 'z2ma.designer.embed.v1';
    const nonce = () => Array.from(crypto.getRandomValues(new Uint8Array(16)), byte => byte.toString(16).padStart(2, '0')).join('');
    let client = ''; let selected = ''; let pending = null; let timer = null; let frameTimer = null; let origin = ''; let connected = false; let opened = false; let renewing = false; let maximizing = false;
    const feedback = text => { $('feedback').textContent = text; };
    function buttons() {
        $('refresh').disabled = !!pending || renewing || maximizing;
        $('renew').disabled = !!pending || !connected || renewing || maximizing;
        $('maximize').disabled = !!pending || !connected || renewing || maximizing;
        $('retry').hidden = opened || !!pending;
    }
    function theme() {
        if (!connected) return;
        const style = getComputedStyle(document.documentElement);
        frame.contentWindow.postMessage({channel, type: 'theme',
            text: style.getPropertyValue('--content-color').trim(), card: style.getPropertyValue('--card-color').trim(), accent: style.getPropertyValue('--accent-color').trim(),
            fontSize: parseFloat(getComputedStyle(document.body).fontSize)}, origin);
    }
    function send(action) {
        if (pending || renewing || maximizing || (action === 'access' && opened)) return;
        try {
            if (!client) client = nonce();
            if (typeof requestAction !== 'function') throw new Error();
            const request = nonce(); pending = {request, action}; buttons();
            feedback(action === 'access' ? 'Vollständiger Designer wird in der Kachel geöffnet …' : 'Modulstatus wird gelesen …');
            timer = setTimeout(() => { pending = null; buttons(); feedback('Das Modul hat noch nicht geantwortet. Bitte die Verbindung erneut versuchen; Entwürfe und Betrieb bleiben unverändert.'); }, 10000);
            requestAction('DesignerTile', JSON.stringify({client, request, action, systemId: selected}));
        } catch {
            clearTimeout(timer); pending = null; buttons(); feedback('Die Symcon-Verbindung ist noch nicht verfügbar. Bitte die Verbindung erneut versuchen.');
        }
    }
    window.handleMessage = function (data) {
        if (typeof data !== 'string') return;
        try { data = JSON.parse(data); } catch { return; }
        if (data?.type !== 'z2ma-tile' || data.version !== 1 || data.client !== client || !pending || data.request !== pending.request || data.action !== pending.action) return;
        const current = pending; pending = null; clearTimeout(timer);
        if (!data.ok) { buttons(); feedback('Das Modul kann den Designer derzeit nicht bereitstellen. Bitte Systemzuordnung und Modul-Debug prüfen.'); return; }
        try {
            if (!Array.isArray(data.state?.systems) || typeof data.state.selectedSystem !== 'string') throw new Error();
            selected = data.state.selectedSystem;
            if (current.action === 'status') {
                const system = data.state.systems.find(item => item.systemId === selected);
                feedback(system ? `${system.name}: ${system.status}.` : 'Bitte zuerst Z2M-Verbindungen in der Automationszentrale zuordnen.');
                buttons(); if (!opened && system) send('access'); return;
            }
            const access = data.access;
            origin = window.SymconHTTPOrigin.resolve(window);
            if (!origin || access?.path !== window.Z2MATile.path || access.systemId !== selected || !/^[a-f0-9]{64}$/.test(access.token)
                || !Number.isInteger(access.expires) || access.expires * 1000 <= Date.now()) throw new Error();
            const address = new URL(access.path, origin); address.hash = access.token;
            opened = true; frame.hidden = false; frame.src = address.href;
            frameTimer = setTimeout(() => { if (!connected) feedback('Der Designer hat sich noch nicht gemeldet. Bitte die Kachel erneut öffnen und das Modul-Debug prüfen.'); }, 15000);
        } catch {
            feedback('Der Designer konnte in dieser Kachel nicht geöffnet werden. Bitte die Kachel erneut öffnen und das Modul-Debug prüfen.');
        }
        buttons();
    };
    window.addEventListener('message', event => {
        const value = event.data;
        if (!opened || event.source !== frame.contentWindow || event.origin !== origin || value?.channel !== channel) return;
        if (value.type === 'ready') {
            connected = true; clearTimeout(frameTimer); theme(); buttons();
            feedback('Designer in der Kachel verbunden. Änderungen werden zunächst als Entwurf bearbeitet.');
        }
        if (maximizing && ['maximize-ready', 'maximize-blocked'].includes(value.type)) {
            maximizing = false; clearTimeout(frameTimer); buttons();
            const id = window.Z2MATile.objectId;
            if (value.type === 'maximize-ready' && typeof window.openObject === 'function' && Number.isInteger(id) && id >= 10000 && id <= 59999) window.openObject(id);
            else feedback('Bitte die offene Bearbeitung zuerst übernehmen oder schließen. Ihre Eingaben bleiben erhalten.');
        }
        if (value.type === 'system' && typeof value.systemId === 'string') selected = value.systemId;
        if (value.type === 'renewed' && renewing) {
            renewing = false; clearTimeout(timer); buttons();
            feedback(value.ok === true ? 'Zugang verlängert. Offene Eingaben und Systemauswahl bleiben erhalten.' : 'Zugang konnte nicht verlängert werden. Offene Eingaben bleiben erhalten; bitte den Hinweis im Designer beachten.');
        }
    });
    $('maximize').hidden = !window.Z2MATile.canMaximize || typeof window.openObject !== 'function';
    $('maximize').addEventListener('click', () => {
        if (!connected || pending || renewing || maximizing || !window.Z2MATile.canMaximize) return;
        maximizing = true; buttons();
        frameTimer = setTimeout(() => { maximizing = false; buttons(); feedback('Die Großansicht konnte noch nicht vorbereitet werden. Offene Eingaben bleiben erhalten.'); }, 8000);
        frame.contentWindow.postMessage({channel, type: 'maximize'}, origin);
    });
    $('refresh').addEventListener('click', () => send('status'));
    $('retry').addEventListener('click', () => send('status'));
    $('renew').addEventListener('click', () => {
        if (!connected || pending || maximizing || renewing) return;
        renewing = true; buttons(); feedback('Designerzugang wird ohne Neuladen verlängert …');
        timer = setTimeout(() => { renewing = false; buttons(); feedback('Zugangserneuerung noch nicht bestätigt. Offene Eingaben bleiben erhalten.'); }, 12000);
        frame.contentWindow.postMessage({channel, type: 'renew'}, origin);
    });
    window.addEventListener('resize', theme);
    if (typeof MutationObserver === 'function') new MutationObserver(theme).observe(document.head, {subtree: true, childList: true, characterData: true, attributes: true});
    window.addEventListener('pagehide', () => { clearTimeout(timer); clearTimeout(frameTimer); });
    send('status');
})();
