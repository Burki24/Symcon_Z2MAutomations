/* Own scoped module view. Creation is a separate, local-draft-only POST. */
import {installCreation} from './creator.js';
import {installEditing} from './editor.js';
(() => {
    'use strict';
    const $ = selector => document.querySelector(selector);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char]));
    const icon = name => `<svg aria-hidden="true"><use href="#icon-${name}"/></svg>`;
    const compare = {eq: 'ist gleich', ne: 'ist ungleich', gt: 'ist größer als', gte: 'ist mindestens', lt: 'ist kleiner als', lte: 'ist höchstens'};
    const areas = {device: ['Geräteausgelöst', 'device'], time: ['Zeit & Astro', 'clock'], sequence: ['Aktionsfolgen', 'flow'], catalog: ['Geräte & Kanäle', 'search']};
    const days = values => (values ?? []).map(day => ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'][day - 1] ?? '?').join(', ');
    const values = new Map([['ON', 'Ein'], ['OFF', 'Aus'], ['TOGGLE', 'Umschalten']]);
    const valueText = value => typeof value === 'boolean' ? (value ? 'Ja / erkannt' : 'Nein / nicht erkannt') : (values.get(value) ?? (typeof value === 'object' ? JSON.stringify(value) : String(value ?? '—')));
    let data = null;
    let category = 'device';
    let selected = null;
    let selectedPart = null;
    let busy = false;
    let editing = null;
    const token = location.hash.slice(1);
    const endpoint = new URL('data', location.href);
    endpoint.hash = '';

    document.body.classList.add('module-designer');
    $('.toolbar').setAttribute('aria-label', 'Modulansicht und neue Automation');
    $('.preview-label').textContent = 'ENTWURF';
    $('.preview-notice strong').textContent = 'Automationen im lokalen Entwurf';
    $('.preview-notice > span:last-child').textContent = 'Regeln hier erstellen und bearbeiten. Änderungen betreffen nur den Symcon-Entwurf. Übertragen und Aktivieren weiterhin im nativen Formular.';
    $('#validate-button').innerHTML = icon('check') + 'Ansicht aktualisieren';
    $('#validate-button').removeAttribute('data-action');
    ['#save-button', '#activate-button', '#stop-button', '.toolbar-divider', '.navigation-footer', '.activity', '#new-dialog', '#confirm-dialog'].forEach(selector => $(selector).remove());
    document.querySelectorAll('.entry-tools [data-action]:not([data-action="select-rule"])').forEach(button => button.remove());
    const detailsButton = $('[data-action="select-rule"]');
    detailsButton.setAttribute('aria-label', 'Details der ausgewählten Regel anzeigen');
    detailsButton.title = 'Regeldetails';
    $('#selection-hint').textContent = 'Baustein anklicken → Details ansehen';
    $('.canvas-footer > span:last-child').textContent = 'Regeln hier erstellen und bearbeiten · Z2M bleibt unverändert';
    $('#draft-status').textContent = 'Moduldaten werden geladen …';
    $('#runtime-status').textContent = 'Betrieb noch nicht gelesen';
    $('#flow').innerHTML = '';
    $('#entry-title').textContent = 'Automationsdesigner';
    $('#entry-subtitle').textContent = 'Verbindung zur ausgewählten Modulinstanz';
    $('#inspector-fields').textContent = 'Hier erscheinen die Details des ausgewählten Bausteins.';

    function entries() {
        const rules = (data?.configuration?.automations ?? []).map(rule => ({...rule, kind: rule.triggers?.some(trigger => trigger.type === 'device') ? 'device' : 'time'}));
        const sequences = (data?.configuration?.sequences ?? []).map(sequence => ({...sequence, kind: 'sequence'}));
        return [...rules, ...sequences];
    }

    const entryKey = entry => `${entry.kind === 'sequence' ? 'sequence' : 'rule'}:${entry.id}`;
    const current = () => category === 'catalog' ? data?.devices.find(device => device.key === selected) : entries().find(entry => entryKey(entry) === selected);
    const targetDevice = target => data?.devices.find(device => device.target.type === target?.type && device.target.id === target?.id
        && (device.target.system ?? '') === (target?.system ?? ''));
    const sourceDevice = id => data?.devices.find(device => device.target.type === 'device' && device.target.id === id && !device.target.system);
    const targetName = target => targetDevice(target)?.name ?? `${target?.system ? target.system + ' · ' : ''}${target?.type === 'group' ? 'Gruppe ' : 'Gerät '}${target?.id ?? 'unbekannt'} (Gerätedaten fehlen)`;
    const sourceName = id => sourceDevice(id)?.name ?? `${id} (Gerätedaten fehlen)`;
    const propertyName = (device, property) => device?.properties.find(meta => meta.id === property)?.label ?? property;
    const displayValue = (device, property, value) => {
        const meta = device?.properties.find(meta => meta.id === property);
        return (meta?.valueLabels?.find(entry => entry.value === value)?.label ?? valueText(value)) + (typeof value === 'number' && meta?.unit ? ' ' + meta.unit : '');
    };
    const field = (label, value) => `<div class="read-field"><span>${esc(label)}</span><strong>${esc(value)}</strong></div>`;
    const message = text => { $('#feedback').textContent = text; };

    function triggerSummary(trigger) {
        if (trigger.type === 'device') return `${sourceName(trigger.device)} · ${propertyName(sourceDevice(trigger.device), trigger.property)} ${compare[trigger.operator] ?? trigger.operator} ${displayValue(sourceDevice(trigger.device), trigger.property, trigger.value)}`;
        if (trigger.type === 'time') return `${trigger.at} · ${trigger.timezone}`;
        if (trigger.type === 'sun') return `${trigger.event === 'sunrise' ? 'Sonnenaufgang' : 'Sonnenuntergang'} · ${trigger.offset_minutes >= 0 ? '+' : ''}${trigger.offset_minutes} Minuten · ${trigger.timezone}`;
        return `Weiterer Auslöser (${trigger.type})`;
    }

    function conditionSummary(condition) {
        if (condition.type === 'device_value') return `${sourceName(condition.device)} · ${propertyName(sourceDevice(condition.device), condition.property)} ${compare[condition.operator] ?? condition.operator} ${displayValue(sourceDevice(condition.device), condition.property, condition.value)}`;
        if (condition.type === 'time_window') return `${condition.after} bis ${condition.before} · ${condition.timezone}${condition.weekdays ? ' · ' + days(condition.weekdays) : ''}`;
        if (condition.type === 'weekday') return `${days(condition.weekdays)} · ${condition.timezone}`;
        return `Weitere Bedingung (${condition.type})`;
    }

    function actionSummary(action) {
        if (action.type === 'sequence') return `Aktionsfolge: ${entries().find(entry => entry.kind === 'sequence' && entry.id === action.sequence_id)?.name ?? action.sequence_id}`;
        if (action.type === 'scene') {
            const device = targetDevice(action.target);
            const scene = device?.properties.find(meta => meta.sceneEndpoint === (action.endpoint ?? 0))?.sceneNames?.[action.scene_id];
            return `${targetName(action.target)} · Szene: ${scene ?? action.scene_id}${action.endpoint ? ' · Kanal ' + action.endpoint : ''}`;
        }
        if (action.type === 'toggle') return `${targetName(action.target)} · ${propertyName(targetDevice(action.target), action.property)} umschalten`;
        if (action.type === 'set') return `${targetName(action.target)} · ${Object.entries(action.values ?? {}).map(([key, value]) => `${propertyName(targetDevice(action.target), key)}: ${displayValue(targetDevice(action.target), key, value)}`).join(' · ')}`;
        return `Weitere Aktion (${action.type})`;
    }

    function renderNavigation() {
        const all = entries();
        $('#entry-count').textContent = all.filter(entry => entry.kind !== 'sequence').length;
        $('#categories').innerHTML = Object.entries(areas).map(([kind, [label, symbol]]) => `<button class="category ${category === kind ? 'active' : ''}" data-area="${kind}" aria-pressed="${category === kind}">${icon(symbol)}${label}<span class="category-count">${kind === 'catalog' ? data?.devices.length ?? 0 : all.filter(entry => entry.kind === kind).length}</span></button>`).join('');
        $('#list-label').textContent = areas[category][0];
        const search = $('#search').value.trim().toLocaleLowerCase('de');
        const options = category === 'catalog' ? (data?.devices ?? []).map(device => ({key: device.key, title: device.name, subtitle: `${device.target.system ?? data.module.baseTopic} · ${device.target.id}`}))
            : all.filter(entry => entry.kind === category).map(entry => ({key: entryKey(entry), title: entry.name,
                subtitle: entry.kind === 'sequence' ? `${entry.actions.length} Schritte` : (entry.triggers ?? []).map(triggerSummary).join(' ODER '), enabled: entry.enabled}));
        const visible = options.filter(entry => `${entry.title} ${entry.subtitle}`.toLocaleLowerCase('de').includes(search));
        $('#list-count').textContent = `${visible.length} / ${options.length}`;
        $('#entry-list').innerHTML = visible.map(entry => `<button class="entry ${selected === entry.key ? 'active' : ''}" data-entry="${esc(entry.key)}" aria-pressed="${selected === entry.key}"><span class="entry-title-row"><strong>${esc(entry.title)}</strong>${entry.enabled !== undefined ? `<span class="enabled-dot ${entry.enabled ? 'on' : ''}" title="Im Entwurf ${entry.enabled ? 'eingeschaltet' : 'ausgeschaltet'}"></span>` : ''}</span><small>${esc(entry.subtitle)}</small></button>`).join('')
            || '<p class="empty-list">Keine passenden Einträge. Die Ansicht verwendet ausschließlich vorhandene Moduldaten.</p>';
    }

    function card(kind, index, title, summary, tag = '') {
        const key = `${kind}:${index}`;
        return `<button class="flow-card read-card ${selectedPart === key ? 'selected' : ''}" data-part="${key}" aria-pressed="${selectedPart === key}"><span class="card-mark">${icon(kind === 'conditions' ? 'condition' : kind === 'actions' ? 'bolt' : 'device')}</span><span class="card-content"><strong>${esc(title)}</strong><span class="read-summary">${esc(summary)}</span>${tag ? `<span class="card-tag">${esc(tag)}</span>` : ''}</span><span class="card-number">${index + 1}</span></button>`;
    }

    function section(title, type, content) {
        return `<section class="flow-section ${type}-section"><div class="section-title"><span class="section-symbol">${icon(type === 'condition' ? 'condition' : type === 'action' ? 'bolt' : 'clock')}</span><h3>${esc(title)}</h3></div>${content}</section>`;
    }

    function renderFlow() {
        const entry = current();
        $('#entry-type').textContent = areas[category][0].toUpperCase();
        $('#entry-title').textContent = entry?.name ?? 'Keine Auswahl';
        detailsButton.disabled = !entry;
        $('#entry-subtitle').textContent = entry ? category === 'catalog' ? 'Geladene Eigenschaften, Kanäle und Szenen' : entry.kind === 'sequence' ? 'Wiederverwendbare Aktionsfolge im lokalen Entwurf'
            : `Im Entwurf ${entry.enabled ? 'eingeschaltet' : 'ausgeschaltet'} · ${entry.actions.length} ${entry.actions.length === 1 ? 'Aktionsschritt' : 'Aktionsschritte'}` : 'Links einen vorhandenen Eintrag auswählen.';
        if (!entry) { $('#flow').innerHTML = '<p class="sequence-note">Hier werden vorhandene Regeln und Gerätedaten angezeigt. Es werden keine Beispiele ergänzt.</p>'; return; }
        if (category === 'catalog') {
            $('#flow').innerHTML = section('EIGENSCHAFTEN UND KANÄLE', 'when', entry.properties.map((meta, index) => card('properties', index, meta.label, meta.id,
                meta.writable ? 'Schaltbar in Symcon' : meta.readable ? 'Nur lesbar' : 'Nicht als Aktion verfügbar')).join(''));
            return;
        }
        let html = '';
        if (entry.kind !== 'sequence') {
            html += section('1 · WENN', 'when', entry.triggers.map((trigger, index) => card('triggers', index, trigger.type === 'device' ? 'Geräteereignis' : trigger.type === 'sun' ? 'Sonnenzeit' : 'Uhrzeit', triggerSummary(trigger))).join('<div class="or-label">ODER</div>'));
            html += '<div class="flow-connector"></div>' + section('2 · NUR WENN', 'condition', entry.conditions.length ? entry.conditions.map((condition, index) => card('conditions', index, condition.type === 'device_value' ? 'Gerätebedingung' : condition.type === 'weekday' ? 'Wochentage' : 'Zeitfenster', conditionSummary(condition), condition.start_only ? 'Nur beim Start' : '')).join('') : '<p class="field-help">Keine zusätzlichen Bedingungen.</p>') + '<div class="flow-connector"></div>';
        }
        html += section(`${entry.kind === 'sequence' ? '' : '3 · '}DANN`, 'action', entry.actions.map((action, index) => card('actions', index, `Schritt ${index + 1}`, actionSummary(action), action.delay_seconds ? `Vorher ${action.delay_seconds} Sekunden warten` : 'Sofort ausführen')).join(''));
        $('#flow').innerHTML = html;
    }

    function renderInspector() {
        const entry = current();
        let html = '';
        let title = 'Details';
        if (entry && category === 'catalog') {
            const property = selectedPart?.startsWith('properties:') ? entry.properties[Number(selectedPart.split(':')[1])] : null;
            title = property?.label ?? 'Gerät / Gruppe';
            html = field('Name', entry.name) + field('Z2M-System', entry.target.system ?? data.module.baseTopic) + field('Kennung', entry.target.id);
            if (property) {
                html += field('Eigenschaft / Kanal', property.id) + field('Lesbar', property.readable ? 'Ja' : 'Nein') + field('In Symcon schaltbar', property.writable ? 'Ja' : 'Nein');
                if (property.unit) html += field('Einheit', property.unit);
                for (const [key, label] of [['minimum', 'Minimum'], ['maximum', 'Maximum'], ['step', 'Schrittweite']]) if (property[key] !== undefined) html += field(label, property[key]);
                if (property.values.length) html += field(property.sceneNames ? 'Vorhandene Szenen' : 'Verfügbare Werte', property.values.map(value => displayValue(entry, property.id, value)).join(', '));
            }
        } else if (entry) {
            const [kind, position] = selectedPart?.split(':') ?? [];
            const item = entry[kind]?.[Number(position)];
            title = item ? ({triggers: 'Auslöser', conditions: 'Bedingung', actions: 'Aktion'}[kind]) + ' ' + (Number(position) + 1) : entry.kind === 'sequence' ? 'Aktionsfolge' : 'Automation';
            html = field('Name', entry.name) + field('Regelkennung', entry.id);
            if (!item && entry.kind !== 'sequence') html += field('Im lokalen Entwurf', entry.enabled ? 'Eingeschaltet' : 'Ausgeschaltet') + field('Bei erneuter Auslösung', {ignore: 'Neue Auslösung ignorieren', restart: 'Von vorn beginnen', extend: 'Laufende Wartezeit zurücksetzen'}[entry.retrigger ?? 'ignore']);
            if (item) {
                html += field('Beschreibung', kind === 'triggers' ? triggerSummary(item) : kind === 'conditions' ? conditionSummary(item) : actionSummary(item));
                if (item.device) html += field('Gerät', sourceName(item.device));
                if (item.target) html += field('Ziel', targetName(item.target)) + field('Z2M-System', item.target.system ?? data.module.baseTopic);
                if (item.property) html += field('Eigenschaft / Kanal', propertyName(item.target ? targetDevice(item.target) : sourceDevice(item.device), item.property));
                if (Object.hasOwn(item, 'value')) html += field('Vergleichswert', displayValue(sourceDevice(item.device), item.property, item.value));
                if (item.values) for (const [key, value] of Object.entries(item.values)) html += field(propertyName(targetDevice(item.target), key), displayValue(targetDevice(item.target), key, value));
                if (item.for_seconds !== undefined) html += field('Mindestdauer', `${item.for_seconds} Sekunden`);
                if (kind === 'triggers' && item.type === 'device') html += field('Nur bei Wertänderung', item.on_change ? 'Ja' : 'Nein');
                if (kind === 'conditions' && item.type === 'device_value') html += field('Nur beim Start prüfen', item.start_only ? 'Ja' : 'Nein');
                if (kind === 'actions') html += field('Wartezeit vor diesem Schritt', `${item.delay_seconds ?? 0} Sekunden`);
                if (item.type === 'scene') html += field('Szenennummer', item.scene_id) + (item.endpoint ? field('Kanal', item.endpoint) : '');
                if (item.type === 'sun') html += field('Breitengrad', item.latitude) + field('Längengrad', item.longitude);
                if (item.type === 'sequence') html += `<button data-sequence="${esc(item.sequence_id)}">Aktionsfolge ansehen</button>`;
            }
        }
        $('#inspector-title').textContent = title;
        $('#inspector-subtitle').textContent = category === 'catalog' ? 'Gerätedaten zur Auswahl in Automationen.' : entry?.kind === 'sequence' ? 'Aktionsfolgen werden weiterhin im nativen Formular bearbeitet.' : 'Bausteindetails anzeigen. Für Änderungen „Automation bearbeiten“ wählen.';
        $('#inspector-fields').innerHTML = html || '<p class="field-help">Wähle links einen Eintrag und anschließend einen Baustein.</p>';
    }

    function render() { renderNavigation(); renderFlow(); renderInspector(); editing?.refresh(); }
    function selectEntry(key) { selected = key; selectedPart = null; render(); }
    function chooseArea(area) {
        category = area; selectedPart = null;
        selected = category === 'catalog' ? data?.devices[0]?.key : entryKeyOrNull(entries().find(entry => entry.kind === category));
        render();
    }
    const entryKeyOrNull = entry => entry ? entryKey(entry) : null;

    document.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button || button.disabled) return;
        if (button.dataset.area) chooseArea(button.dataset.area);
        if (button.dataset.entry) selectEntry(button.dataset.entry);
        if (button.dataset.part) { selectedPart = button.dataset.part; renderFlow(); renderInspector(); }
        if (button === detailsButton) { selectedPart = null; renderFlow(); renderInspector(); }
        if (button.dataset.sequence) { category = 'sequence'; $('#search').value = ''; selectEntry('sequence:' + button.dataset.sequence); }
    });
    $('#search').addEventListener('input', renderNavigation);
    $('#validate-button').addEventListener('click', load);

    async function load(preferredRule = '') {
        if (busy) return false;
        busy = true; $('#validate-button').disabled = true;
        message('Vorhandene Moduldaten werden gelesen. Es wird keine Z2M-Abfrage gestartet.');
        try {
            if (!/^[a-f0-9]{64}$/.test(token)) throw new Error('access');
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 10000);
            let response; let text;
            try {
                response = await fetch(endpoint, {method: 'GET', headers: {'X-Z2MA-Read-Token': token}, cache: 'no-store', signal: controller.signal, redirect: 'error'});
                if (response.ok) text = await response.text();
            } finally { clearTimeout(timeout); }
            if (!response.ok) throw new Error(response.status === 403 ? 'access' : 'transport');
            if (text.length > 2097152) throw new Error('invalid');
            const next = JSON.parse(text);
            if (next.contractVersion !== 1 || next.mode !== 'read' || !Array.isArray(next.devices) || !next.module || !next.status) throw new Error('invalid');
            data = next;
            document.title = `${data.module.name} · Automationsdesigner · Entwurf`;
            $('#draft-status').textContent = data.draftAvailable ? `Lokaler Entwurf · Revision ${data.configuration.revision}` : 'Kein passender Editorentwurf geladen';
            $('#runtime-status').textContent = data.status.confirmed ? `Zuletzt bestätigt: ${data.status.executing ? 'Automatik aktiv' : 'Automatik gestoppt'} · aktive Revision ${data.status.activeRevision}` : 'Betrieb noch nicht bestätigt';
            $('#draft-status').className = 'status-chip';
            const notices = [`${data.module.name} · Topic ${data.module.baseTopic} · Stand aus dem Modul, keine Live-Prüfung.`];
            const catalogTime = Number.isSafeInteger(data.catalogLoadedAt) && data.catalogLoadedAt > 0
                ? new Date(data.catalogLoadedAt * 1000).toLocaleString('de-DE') : null;
            notices[0] += catalogTime ? ` Gerätedaten zuletzt aktualisiert: ${catalogTime}.` : ' Aktualisierungszeit der Gerätedaten noch unbekannt.';
            if (!data.catalogAvailable) notices.push('Gerätedaten fehlen für diese Verbindung. Im nativen Modul „Nur Gerätedaten aktualisieren“ wählen, danach hier „Ansicht aktualisieren“. Entwürfe bleiben erhalten.');
            if (!data.draftAvailable && !data.draftMismatch) notices.push('Für diese Verbindung ist noch kein Automationsentwurf geladen. Die Gerätedaten können Sie unabhängig davon ansehen. Gespeicherte Regeln werden erst mit „Geräte und gespeicherte Automationen laden“ übernommen.');
            if (data.draftMismatch) notices.push('Der YAML-Entwurf weicht vom geladenen Editorstand ab. Bitte im nativen Formular prüfen; hier wird nichts ersetzt.');
            if (data.openFormEdits) notices.push('Im nativen Formular gibt es noch nicht in den Entwurf übernommene Eingaben. Diese sind hier noch nicht enthalten und bleiben erhalten.');
            $('#issues').hidden = data.catalogAvailable && data.draftAvailable && !data.openFormEdits;
            $('#issues').textContent = notices.join(' ');
            $('#feedback').dataset.tone = '';
            message(notices[0]);
            if (typeof preferredRule === 'string' && data.configuration?.automations.some(rule => rule.id === preferredRule)) {
                category = entries().find(entry => entry.id === preferredRule && entry.kind !== 'sequence').kind; selected = 'rule:' + preferredRule; selectedPart = null; $('#search').value = '';
            } else if (!current()) {
                const first = entries().find(entry => entry.kind === category) ?? entries()[0];
                if (category !== 'catalog' && first) category = first.kind;
                selected = category === 'catalog' ? data.devices[0]?.key : entryKeyOrNull(first);
                selectedPart = null;
            }
            render();
            return true;
        } catch (error) {
            const text = error.message === 'access' ? 'Der Lesezugang fehlt oder ist abgelaufen. Bitte den Designer erneut im Modul öffnen.' : 'Moduldaten konnten nicht gelesen werden. Verbindung und Debug prüfen; die bisherige Anzeige ist möglicherweise veraltet.';
            message(text); $('#feedback').dataset.tone = 'error';
            $('#issues').hidden = false; $('#issues').textContent = text;
            return false;
        } finally { busy = false; $('#validate-button').disabled = false; }
    }
    installCreation({getData: () => data, reload: () => load(), onCreated: async ruleId => {
        const refreshed = await load(ruleId);
        message(refreshed ? 'Neue Automation ausgeschaltet im Symcon-Entwurf ergänzt. Prüfen und Übertragen weiterhin im nativen Formular.'
            : 'Die Automation wurde im Entwurf ergänzt. Die Ansicht konnte noch nicht aktualisiert werden; bitte „Ansicht aktualisieren“ wählen.');
    }});
    editing = installEditing({getData: () => data, getRule: current, reload: () => load(), summaries: {triggers: triggerSummary, conditions: conditionSummary, actions: actionSummary}, onEdited: async ruleId => {
        const refreshed = await load(ruleId);
        message(refreshed ? 'Änderung im lokalen Symcon-Entwurf übernommen. In Z2M wurde nichts gespeichert oder aktiviert.'
            : 'Die Änderung wurde im Entwurf übernommen. Bitte die Ansicht erneut aktualisieren.');
    }});
    render();
    load();
})();
