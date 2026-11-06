/* HTMLBox entry only: secrets stay in the fixed own frame's fragment. No RPC or MQTT. */
(() => {
    const config = window.Z2MAIPSView?.runtime ?? {};
    const frame = document.getElementById('ipsview-designer');
    const feedback = document.getElementById('ipsview-feedback');
    const renew = document.getElementById('ipsview-renew');
    const retry = document.getElementById('ipsview-retry');
    const channel = 'z2ma.designer.embed.v1';
    let origin = null; let connected = false; let timer;
    function failure(message) { feedback.textContent = message; renew.disabled = true; retry.hidden = connected; }
    function open() {
        if (connected) return;
        clearTimeout(timer); retry.hidden = true;
        const configured = config.origin ? window.SymconHTTPOrigin.httpOrigin(config.origin) : null;
        origin = configured || (!config.origin ? window.SymconHTTPOrigin.resolve(window) : null);
        if (!/^[a-f0-9]{64}$/.test(config.grant ?? '')) { failure(config.message || 'Bitte die IPSView-Ausgabe im Modul neu erzeugen.'); return; }
        if (!origin || !/^\/hook\/Z2MAutomationsDesigner\d{5}\/ipsview\.html$/.test(config.path ?? '')) {
            failure('Die Symcon-Serveradresse fehlt oder ist ungültig. Bitte im Modul die aus IPSView erreichbare Serveradresse eintragen und die Einstellungen übernehmen.'); return;
        }
        feedback.textContent = 'Designer wird geöffnet …';
        frame.src = origin + config.path + '#entry.' + config.grant; frame.hidden = false;
        timer = setTimeout(() => failure('Der Designer antwortet noch nicht. Bitte Serveradresse, Verbindung und IPSView-Ausgabe prüfen; anschließend erneut verbinden.'), 15000);
    }
    window.addEventListener('message', event => {
        if (event.source !== frame.contentWindow || event.origin !== origin || event.data?.channel !== channel) return;
        if (event.data.type === 'ready') {
            clearTimeout(timer); connected = true; renew.disabled = false; retry.hidden = true;
            feedback.textContent = 'Designer geöffnet. Systemauswahl und Status stehen direkt im Arbeitsbereich bereit.';
        } else if (event.data.type === 'renewed' && connected) {
            clearTimeout(timer); renew.disabled = false;
            feedback.textContent = event.data.ok === true ? 'Zugang verlängert. Systemauswahl und offene Eingaben bleiben erhalten.'
                : 'Zugang konnte nicht verlängert werden. Bitte die Meldung im Designer beachten; offene Eingaben bleiben erhalten.';
        } else if (event.data.type === 'interaction-error') {
            failure('Die Bedienunterstützung konnte nicht geladen werden. Bitte die Moduldateien und das Debug prüfen.');
        }
    });
    renew.addEventListener('click', () => {
        if (!connected || renew.disabled) return;
        renew.disabled = true; feedback.textContent = 'Zugang wird verlängert …';
        frame.contentWindow.postMessage({channel, type: 'renew'}, origin);
        timer = setTimeout(() => { renew.disabled = false; feedback.textContent = 'Noch keine Antwort zur Verlängerung. Bitte die Verbindung prüfen und erneut versuchen; offene Eingaben bleiben erhalten.'; }, 12000);
    });
    retry.addEventListener('click', open);
    window.addEventListener('pagehide', () => clearTimeout(timer));
    open();
})();
