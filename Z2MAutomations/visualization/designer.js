/* Own, offline interaction prototype. No Symcon, MQTT or device access. */
(() => {
    'use strict';
    const $ = selector => document.querySelector(selector);
    const esc = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[character]));
    const icon = name => `<svg aria-hidden="true"><use href="#icon-${name}"/></svg>`;
    const clone = value => structuredClone(value);
    const uid = () => crypto.randomUUID();
    const property = (id, label, type, values, writable = false) => ({id, label, type, values, writable});
    const catalog = [
        {id: 'demo.motion', name: 'Demo-Bewegungsmelder', room: 'Eingangsbereich', properties: [property('occupancy', 'Bewegung', 'boolean', [true, false])]},
        {id: 'demo.brightness', name: 'Demo-Helligkeitssensor', room: 'Außenbereich', properties: [property('illuminance', 'Beleuchtungsstärke', 'number', [], false)]},
        {id: 'demo.temperature', name: 'Demo-Raumsensor', room: 'Wohnbereich', properties: [property('temperature', 'Temperatur', 'number', [], false)]},
        {id: 'demo.lamp', name: 'Demo-Leuchte', room: 'Eingangsbereich', properties: [property('state', 'Schaltzustand', 'enum', ['ON', 'OFF', 'TOGGLE'], true), property('brightness', 'Helligkeit', 'number', [], true)]},
        {id: 'demo.switch', name: 'Demo-Schaltaktor', room: 'Arbeitszimmer', properties: [property('state_l1', 'Kanal 1', 'enum', ['ON', 'OFF', 'TOGGLE'], true), property('state_l2', 'Kanal 2', 'enum', ['ON', 'OFF', 'TOGGLE'], true)]},
        {id: 'demo.group', name: 'Demo-Lichtgruppe', room: 'Wohnbereich', properties: [property('state', 'Schaltzustand', 'enum', ['ON', 'OFF', 'TOGGLE'], true)], scenes: [{id: '3', name: 'Gedimmtes Licht'}, {id: '7', name: 'Leselicht'}]}
    ];
    const initial = [
        {id: 'demo.motion-rule', kind: 'device', name: 'Licht bei Bewegung', enabled: true, retrigger: 'extend',
            triggers: [{id: 'demo.trigger', device: 'demo.motion', property: 'occupancy', compare: 'eq', value: true, hold: 0, changed: false}],
            conditions: [{id: 'demo.condition', device: 'demo.brightness', property: 'illuminance', compare: 'lt', value: 100, startOnly: true}],
            actions: [{id: 'demo.on', type: 'set', device: 'demo.lamp', property: 'state', value: 'ON', delay: 0}, {id: 'demo.off', type: 'set', device: 'demo.lamp', property: 'state', value: 'OFF', delay: 60}]},
        {id: 'demo.button-rule', kind: 'device', name: 'Zweiten Kanal schalten', enabled: false, retrigger: 'ignore',
            triggers: [{id: 'demo.other-trigger', device: 'demo.motion', property: 'occupancy', compare: 'eq', value: true, hold: 0, changed: true}], conditions: [],
            actions: [{id: 'demo.channel', type: 'set', device: 'demo.switch', property: 'state_l2', value: 'ON', delay: 0}]},
        {id: 'demo.clock-rule', kind: 'time', name: 'Abendstimmung', enabled: false, retrigger: 'ignore',
            schedule: {mode: 'clock', at: '18:00, 21:30', timezone: 'Europe/Berlin', offset: 0, weekdays: [1, 2, 3, 4, 5, 6, 7]}, conditions: [],
            actions: [{id: 'demo.scene', type: 'scene', device: 'demo.group', scene: '3', delay: 0}]},
        {id: 'demo.sequence', kind: 'sequence', name: 'Kurzes Lichtsignal', actions: [
            {id: 'demo.sequence-on', type: 'set', device: 'demo.lamp', property: 'state', value: 'ON', delay: 0},
            {id: 'demo.sequence-off', type: 'set', device: 'demo.lamp', property: 'state', value: 'OFF', delay: 5}]}
    ];
    let entries = clone(initial);
    let saved = clone(initial);
    let selected = {entry: initial[0].id, kind: 'condition', item: 'demo.condition'};
    let category = 'device';
    let verified = null;
    let running = false;
    let runningEntries = [];
    let dragged = null;
    let confirmation = null;
    let activity = [];
    const categoryNames = {device: 'Geräteausgelöst', time: 'Zeit & Astro', sequence: 'Aktionsfolgen'};
    const categoryIcons = {device: 'device', time: 'clock', sequence: 'flow'};
    const compareNames = {eq: 'ist gleich', ne: 'ist ungleich', gt: 'ist größer als', gte: 'ist mindestens', lt: 'ist kleiner als', lte: 'ist höchstens'};
    const current = () => entries.find(entry => entry.id === selected.entry) ?? null;
    const deviceById = id => catalog.find(device => device.id === id);
    const metaFor = item => deviceById(item.device)?.properties.find(meta => meta.id === item.property);
    const serial = value => JSON.stringify(value);
    const valueText = value => ({ON: 'Ein', OFF: 'Aus', TOGGLE: 'Umschalten', true: 'Erkannt', false: 'Nicht erkannt'}[String(value)] ?? String(value));
    const listFor = kind => ({trigger: 'triggers', condition: 'conditions', action: 'actions'}[kind]);
    const selectedItem = () => selected.kind === 'rule' ? current() : selected.kind === 'schedule' ? current()?.schedule : current()?.[listFor(selected.kind)]?.find(item => item.id === selected.item);

    function note(message, tone = 'info') {
        $('#feedback').textContent = message;
        $('#feedback').dataset.tone = tone;
        activity.unshift({time: new Date().toLocaleTimeString('de-DE', {hour: '2-digit', minute: '2-digit'}), message});
        activity = activity.slice(0, 30);
        $('#activity-count').textContent = String(activity.length);
        $('#activity-list').innerHTML = activity.map(item => `<li><time>${esc(item.time)}</time> · ${esc(item.message)}</li>`).join('');
    }

    function summary(entry) {
        if (entry.kind === 'sequence') return `${entry.actions.length} Aktionen · mehrfach verwendbar`;
        if (entry.kind === 'time') return entry.schedule.mode === 'clock' ? `Uhrzeit: ${entry.schedule.at || 'noch offen'}` : `${entry.schedule.mode === 'sunrise' ? 'Sonnenaufgang' : 'Sonnenuntergang'} · ${entry.schedule.offset >= 0 ? '+' : ''}${entry.schedule.offset} Minuten`;
        const first = entry.triggers[0];
        return first ? `${deviceById(first.device)?.name ?? 'Gerät wählen'}${entry.triggers.length > 1 ? ` + ${entry.triggers.length - 1} weitere` : ''}` : 'Noch kein Auslöser';
    }

    function renderNavigation() {
        $('#entry-count').textContent = String(entries.filter(entry => entry.kind !== 'sequence').length);
        $('#categories').innerHTML = Object.keys(categoryNames).map(kind => `<button class="category ${category === kind ? 'active' : ''}" data-action="category" data-kind="${kind}" aria-pressed="${category === kind}">${icon(categoryIcons[kind])}${categoryNames[kind]}<span class="category-count">${entries.filter(entry => entry.kind === kind).length}</span></button>`).join('');
        const query = $('#search').value.trim().toLocaleLowerCase('de-DE');
        const matches = entries.filter(entry => entry.kind === category && `${entry.name} ${summary(entry)} ${entry.actions.map(action => deviceById(action.device)?.name ?? '').join(' ')}`.toLocaleLowerCase('de-DE').includes(query));
        $('#list-label').textContent = categoryNames[category];
        $('#list-count').textContent = String(matches.length);
        $('#entry-list').innerHTML = matches.length ? matches.map(entry => `<button class="entry ${selected.entry === entry.id ? 'active' : ''}" data-action="entry" data-id="${esc(entry.id)}" aria-pressed="${selected.entry === entry.id}"><span class="entry-title-row"><strong>${esc(entry.name || 'Ohne Namen')}</strong>${entry.kind !== 'sequence' ? `<span class="enabled-dot ${entry.enabled ? 'on' : ''}" aria-label="Im Entwurf ${entry.enabled ? 'eingeschaltet' : 'ausgeschaltet'}"></span>` : ''}</span><small>${esc(summary(entry))}</small></button>`).join('') : '<p class="empty-list">Hier gibt es noch keinen passenden Eintrag. Lege einen neuen an oder ändere die Suche.</p>';
    }

    function section(title, type, count, content, addKind) {
        return `<section class="flow-section ${type}-section"><div class="section-title"><span class="section-symbol">${icon({when: 'device', condition: 'condition', action: 'bolt'}[type])}</span><h3>${title}</h3><small>${count}</small></div>${content}${addKind ? `<button class="add-block" data-action="add" data-kind="${addKind}">${icon('plus')}${{trigger: 'Weiteren Auslöser hinzufügen', condition: 'Bedingung hinzufügen', action: 'Aktion hinzufügen'}[addKind]}</button>` : ''}</section>`;
    }

    function card(kind, item, title, detail, index, tag = '') {
        const active = selected.kind === kind && selected.item === item.id;
        const isAction = kind === 'action';
        return `<div class="flow-card ${active ? 'selected' : ''}" data-action="block" data-kind="${kind}" data-id="${esc(item.id)}" tabindex="0" role="button" aria-label="${esc(`${title}: ${detail}`)}" aria-pressed="${active}">
            ${isAction ? `<span class="grip" aria-hidden="true">${icon('grip')}</span>` : ''}<span class="card-mark">${icon({trigger: 'device', condition: 'condition', action: item.type === 'sequence' ? 'flow' : 'bolt'}[kind])}</span>
            <div class="card-content"><strong>${esc(title)}</strong><p>${esc(detail)}</p>${tag ? `<span class="card-tag">${esc(tag)}</span>` : ''}</div>
            ${isAction ? `<div class="order-buttons"><button class="icon-button up" data-action="move" data-id="${esc(item.id)}" data-direction="-1" aria-label="Aktion ${index + 1} nach oben verschieben" ${index === 0 ? 'disabled' : ''}>${icon('arrow')}</button><button class="icon-button" data-action="move" data-id="${esc(item.id)}" data-direction="1" aria-label="Aktion ${index + 1} nach unten verschieben" ${index === current().actions.length - 1 ? 'disabled' : ''}>${icon('arrow')}</button></div>` : `<span class="card-number">${index + 1}</span>`}
        </div>`;
    }

    function renderFlow() {
        const entry = current();
        if (!entry) {
            $('#entry-title').textContent = 'Deine nächste Automation';
            $('#entry-subtitle').textContent = 'Lege links einen neuen Eintrag an.';
            $('#entry-type').textContent = categoryNames[category].toUpperCase();
            $('#flow').innerHTML = '<p class="sequence-note">Noch kein Eintrag ausgewählt. Mit „Neu anlegen“ beginnst Du einen neuen Ablauf.</p>';
            document.querySelectorAll('.entry-tools button').forEach(button => { button.disabled = true; });
            return;
        }
        document.querySelectorAll('.entry-tools button').forEach(button => { button.disabled = false; });
        $('#entry-type').textContent = categoryNames[entry.kind].toUpperCase();
        $('#entry-title').textContent = entry.name || 'Ohne Namen';
        $('#entry-subtitle').textContent = entry.kind === 'sequence' ? 'Einmal zusammenstellen und in mehreren Automationen verwenden.' : `${entry.enabled ? 'Im Entwurf eingeschaltet' : 'Im Entwurf ausgeschaltet'} · ${entry.actions.length} ${entry.actions.length === 1 ? 'Aktion' : 'Aktionen'} · ${entry.conditions.length} ${entry.conditions.length === 1 ? 'Bedingung' : 'Bedingungen'}`;
        let content = '';
        if (entry.kind === 'device') {
            const triggers = entry.triggers.map((item, index) => card('trigger', item, deviceById(item.device)?.name ?? 'Gerät wählen', `${metaFor(item)?.label ?? 'Eigenschaft wählen'} ${compareNames[item.compare]} ${valueText(item.value)}${item.hold ? ` · ${item.hold} Sekunden lang` : ''}`, index)).join('<div class="or-label">ODER</div>');
            content += section('1 · WENN', 'when', `${entry.triggers.length} / 8 Auslöser`, triggers, 'trigger');
        } else if (entry.kind === 'time') {
            const active = selected.kind === 'schedule';
            content += section('1 · WENN', 'when', 'Zeitpunkt', `<div class="flow-card ${active ? 'selected' : ''}" data-action="schedule" role="button" tabindex="0" aria-pressed="${active}"><span class="card-mark">${icon('clock')}</span><div class="card-content"><strong>${entry.schedule.mode === 'clock' ? 'Wenn die Uhrzeit erreicht ist' : 'Wenn die Sonne den Zeitpunkt erreicht'}</strong><p>${esc(summary(entry))} · ${esc(entry.schedule.timezone)}</p></div></div>`);
        } else content += '<p class="sequence-note">Diese Aktionsfolge hat keinen eigenen Auslöser. Wähle sie später in einer Automation als Aktion aus.</p>';
        if (entry.kind !== 'sequence') {
            content += '<div class="flow-connector" aria-hidden="true"></div>';
            content += section('2 · NUR WENN', 'condition', 'optional · alle gemeinsam', entry.conditions.map((item, index) => card('condition', item, deviceById(item.device)?.name ?? 'Gerät wählen', `${metaFor(item)?.label ?? 'Eigenschaft wählen'} ${compareNames[item.compare]} ${valueText(item.value)}${item.property === 'illuminance' ? ' Lux' : ''}`, index, item.startOnly ? 'Nur beim Start' : 'Gilt auch während des Ablaufs')).join(''), 'condition');
        }
        if (entry.kind !== 'sequence') content += '<div class="flow-connector" aria-hidden="true"></div>';
        content += section(`${entry.kind === 'sequence' ? '' : '3 · '}DANN`, 'action', `${entry.actions.length} / 16 Aktionen`, entry.actions.map((item, index) => {
            const title = item.type === 'sequence' ? entries.find(candidate => candidate.id === item.sequence)?.name ?? 'Aktionsfolge wählen' : deviceById(item.device)?.name ?? 'Gerät wählen';
            const action = item.type === 'scene' ? `Szene: ${deviceById(item.device)?.scenes?.find(scene => scene.id === item.scene)?.name ?? 'wählen'}` : item.type === 'sequence' ? 'Aktionsfolge ausführen' : `${metaFor(item)?.label ?? 'Eigenschaft wählen'}: ${valueText(item.value)}`;
            return card('action', item, title, `${action} · ${item.delay ? `vorher ${item.delay} Sekunden warten` : 'sofort ausführen'}`, index);
        }).join(''), 'action');
        $('#flow').innerHTML = content;
    }

    const options = (values, value) => values.map(option => `<option value="${esc(option.value)}" ${String(option.value) === String(value) ? 'selected' : ''}>${esc(option.label)}</option>`).join('');
    const selectField = (label, field, values, value) => `<label class="field">${label}<select data-field="${field}">${options(values, value)}</select></label>`;
    const inputField = (label, field, value, type = 'text', extra = '') => `<label class="field">${label}<input data-field="${field}" type="${type}" value="${esc(value)}" ${extra}></label>`;
    const checkField = (label, field, value) => `<label class="check-field"><input type="checkbox" data-field="${field}" ${value ? 'checked' : ''}><span>${label}</span></label>`;
    const deviceOptions = mode => catalog.filter(device => mode === 'scene' ? device.scenes?.length : mode !== 'action' || device.properties.some(meta => meta.writable)).map(device => ({value: device.id, label: `${device.name} · ${device.room}`}));

    function deviceFields(item, kind) {
        const device = deviceById(item.device);
        const properties = device?.properties.filter(meta => kind !== 'action' || meta.writable) ?? [];
        const meta = metaFor(item);
        let html = selectField('Gerät', 'device', deviceOptions(kind), item.device);
        html += selectField('Eigenschaft / Kanal', 'property', properties.map(p => ({value: p.id, label: p.label})), item.property);
        if (kind !== 'action') html += selectField('Vergleich', 'compare', Object.entries(compareNames).filter(([key]) => meta?.type === 'number' || ['eq', 'ne'].includes(key)).map(([value, label]) => ({value, label})), item.compare);
        if (meta?.type === 'number') html += inputField(`Wert${item.property === 'illuminance' ? ' in Lux' : item.property === 'temperature' ? ' in °C' : ''}`, 'value', item.value, 'number', 'step="any"');
        else html += selectField('Wert', 'value', (meta?.values ?? []).filter(value => kind === 'action' || value !== 'TOGGLE').map(value => ({value, label: valueText(value)})), item.value);
        return html;
    }

    function renderInspector() {
        const entry = current();
        const item = selectedItem();
        let html = '';
        let title = 'Automation';
        let subtitle = 'Wähle links einen Eintrag oder lege einen neuen an.';
        if (entry && selected.kind === 'rule') {
            title = entry.kind === 'sequence' ? 'Aktionsfolge' : 'Automation';
            subtitle = 'Name und Verhalten des gesamten Ablaufs.';
            html = inputField('Name', 'name', entry.name, 'text', 'maxlength="120" autocomplete="off"');
            if (entry.kind !== 'sequence') {
                html += checkField('Regel im Entwurf eingeschaltet', 'enabled', entry.enabled);
                html += '<hr class="inspector-separator"><div class="field-group-title">BEI ERNEUTER AUSLÖSUNG</div>';
                html += selectField('Wenn der Ablauf bereits läuft', 'retrigger', [{value: 'ignore', label: 'Neue Auslösung ignorieren'}, {value: 'restart', label: 'Von vorn beginnen'}, {value: 'extend', label: 'Laufende Wartezeit zurücksetzen'}], entry.retrigger);
                html += '<p class="field-help">Zurücksetzen startet nur die aktuelle Wartezeit neu. Bereits ausgeführte Schritte werden nicht wiederholt.</p>';
            }
            html += '<div class="inspector-note">Änderungen gehören zunächst zum Entwurf. Oben kannst Du prüfen, speichern und den Beispielbetrieb aktivieren.</div>';
        } else if (entry && selected.kind === 'schedule') {
            title = 'Zeitpunkt bearbeiten'; subtitle = 'Uhrzeiten oder Sonnenzeiten für diesen Ablauf.';
            html = selectField('Auslösen bei', 'mode', [{value: 'clock', label: 'Festen Uhrzeiten'}, {value: 'sunrise', label: 'Sonnenaufgang'}, {value: 'sunset', label: 'Sonnenuntergang'}], item.mode);
            html += item.mode === 'clock' ? inputField('Uhrzeiten', 'at', item.at, 'text', 'placeholder="07:00, 18:30"') + '<p class="field-help">Bis zu acht Uhrzeiten, durch Kommas getrennt.</p>' : inputField('Zeitversatz in Minuten', 'offset', item.offset, 'number', 'min="-720" max="720" step="1"') + '<p class="field-help">Minus bedeutet vorher, Plus bedeutet nachher. Die Standortauswahl folgt in der angebundenen Version.</p>';
            html += selectField('Zeitzone', 'timezone', [{value: 'Europe/Berlin', label: 'Europe/Berlin'}, {value: 'UTC', label: 'UTC'}], item.timezone);
            html += '<div class="field-group-title">ERLAUBTE WOCHENTAGE</div><div class="days">' + ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'].map((day, i) => `<label class="day"><input type="checkbox" data-day="${i + 1}" ${item.weekdays.includes(i + 1) ? 'checked' : ''} aria-label="${['Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag', 'Sonntag'][i]}"><span>${day}</span></label>`).join('') + '</div>';
        } else if (entry && item) {
            title = {trigger: 'Auslöser bearbeiten', condition: 'Bedingung bearbeiten', action: `Aktion ${entry.actions.indexOf(item) + 1} bearbeiten`}[selected.kind];
            subtitle = {trigger: 'Jeder Auslöser kann den Ablauf allein starten.', condition: 'Diese Voraussetzung begrenzt die Ausführung.', action: 'Die Reihenfolge bestimmt den Ablauf.'}[selected.kind];
            if (selected.kind === 'action') {
                const kinds = [{value: 'set', label: 'Gerät oder Kanal schalten'}, {value: 'scene', label: 'Vorhandene Szene aufrufen'}];
                if (entry.kind !== 'sequence') kinds.push({value: 'sequence', label: 'Aktionsfolge verwenden'});
                html += selectField('Art der Aktion', 'type', kinds, item.type);
                if (item.type === 'sequence') html += selectField('Aktionsfolge', 'sequence', entries.filter(candidate => candidate.kind === 'sequence' && candidate.id !== entry.id).map(candidate => ({value: candidate.id, label: candidate.name})), item.sequence);
                else if (item.type === 'scene') {
                    html += selectField('Gerät / Gruppe', 'device', deviceOptions('scene'), item.device);
                    html += selectField('Szene', 'scene', (deviceById(item.device)?.scenes ?? []).map(scene => ({value: scene.id, label: scene.name})), item.scene);
                } else html += deviceFields(item, 'action');
                html += '<hr class="inspector-separator">' + inputField('Wartezeit vor diesem Schritt (Sekunden)', 'delay', item.delay, 'number', 'min="0" max="86400" step="1"');
                html += '<p class="field-help">0 bedeutet sofort. Die Wartezeit gehört zu diesem Schritt und wird beim Verschieben mitgenommen.</p>';
            } else {
                html += deviceFields(item, selected.kind);
                if (selected.kind === 'trigger') {
                    html += inputField('Mindestdauer (Sekunden)', 'hold', item.hold, 'number', 'min="0" max="86400" step="1"');
                    html += checkField('Nur bei einer Wertänderung auslösen', 'changed', item.changed);
                } else {
                    html += '<hr class="inspector-separator">' + checkField('Nur beim Start prüfen', 'startOnly', item.startOnly);
                    html += '<p class="field-help">Geeignet für Helligkeit: Sie begrenzt das Einschalten. Spätere Aktionen und das Verlängern der Wartezeit werden dadurch nicht blockiert.</p>';
                }
            }
            html += `<button class="delete-block" data-action="remove-block">${icon('trash')}${{trigger: 'Auslöser', condition: 'Bedingung', action: 'Aktion'}[selected.kind]} entfernen</button>`;
        }
        $('#inspector-title').textContent = title;
        $('#inspector-subtitle').textContent = subtitle;
        $('#inspector-fields').innerHTML = html || '<p class="field-help">Hier erscheinen die Einstellungen des ausgewählten Bausteins.</p>';
    }

    function renderStatus() {
        const same = serial(entries) === serial(saved);
        const checked = verified === serial(entries);
        $('#draft-status').textContent = !same ? checked ? 'Änderungen geprüft · noch nicht gespeichert' : 'Entwurf geändert · noch nicht geprüft' : checked ? 'Vorschau geprüft und gespeichert' : 'Beispielstand geladen';
        $('#draft-status').className = `status-chip ${!same ? 'dirty' : checked ? 'valid' : ''}`;
        $('#runtime-status').className = `runtime-chip ${running ? 'running' : ''}`;
        const enabledCount = runningEntries.filter(entry => entry.kind !== 'sequence' && entry.enabled).length;
        $('#runtime-status').innerHTML = `<i></i>${running ? `Beispielbetrieb läuft · ${enabledCount} ${enabledCount === 1 ? 'Regel' : 'Regeln'}` : 'Beispielbetrieb gestoppt'}`;
        $('#save-button').disabled = !checked;
        $('#activate-button').disabled = !checked || !same || running;
        $('#stop-button').disabled = !running;
    }

    function render(inspector = true) {
        renderNavigation(); renderFlow(); renderStatus();
        if (inspector) renderInspector();
    }

    function changed(rebuildInspector = false) {
        verified = null;
        $('#issues').hidden = true;
        render(rebuildInspector);
    }

    function choose(entryId, kind = 'rule', itemId = '') {
        const entry = entries.find(candidate => candidate.id === entryId);
        if (!entry) return;
        category = entry.kind;
        selected = {entry: entryId, kind, item: itemId};
        render();
    }

    function updateField(field, value, control) {
        const item = selectedItem();
        if (!item) return;
        let rebuild = false;
        if (field === 'type') {
            const replacement = value === 'scene' ? {type: 'scene', device: 'demo.group', scene: '3'} : value === 'sequence' ? {type: 'sequence', sequence: entries.find(entry => entry.kind === 'sequence')?.id ?? ''} : {type: 'set', device: 'demo.lamp', property: 'state', value: 'ON'};
            const previous = {id: item.id, delay: item.delay};
            Object.keys(item).forEach(key => { delete item[key]; });
            Object.assign(item, previous, replacement); rebuild = true;
        } else if (field === 'device' || field === 'property') {
            item[field] = value;
            const device = deviceById(item.device);
            if (item.type === 'scene') item.scene = device?.scenes?.[0]?.id ?? '';
            else {
                const meta = field === 'device' ? device?.properties.find(p => selected.kind !== 'action' || p.writable) : metaFor(item);
                item.property = meta?.id ?? '';
                item.value = meta?.type === 'number' ? 0 : meta?.values[0];
                if (selected.kind !== 'action') item.compare = 'eq';
            }
            rebuild = true;
        } else if (field === 'value') {
            const meta = metaFor(item);
            item.value = meta?.type === 'number' ? (value === '' ? null : Number(value)) : meta?.type === 'boolean' ? value === 'true' : value;
        } else if (control.type === 'checkbox') item[field] = control.checked;
        else if (control.type === 'number') item[field] = value === '' ? null : Number(value);
        else { item[field] = value; rebuild = field === 'mode'; }
        changed(rebuild);
    }

    function add(kind) {
        const entry = current(); if (!entry) return;
        const list = entry[listFor(kind)];
        if (!list || list.length >= (kind === 'trigger' ? 8 : 16)) { note('Die Höchstzahl ist erreicht. Bearbeite oder entferne zuerst einen vorhandenen Baustein.', 'error'); return; }
        const item = kind === 'trigger' ? {id: uid(), device: 'demo.motion', property: 'occupancy', compare: 'eq', value: true, hold: 0, changed: false} : kind === 'condition' ? {id: uid(), device: 'demo.brightness', property: 'illuminance', compare: 'lt', value: 100, startOnly: false} : {id: uid(), type: 'set', device: 'demo.lamp', property: 'state', value: 'ON', delay: 0};
        list.push(item); selected = {entry: entry.id, kind, item: item.id}; changed(true);
        note('Baustein hinzugefügt. Die Einstellungen findest Du rechts.');
    }

    function move(id, destination) {
        const entry = current(); if (!entry) return;
        const from = entry.actions.findIndex(item => item.id === id);
        if (from < 0 || destination < 0 || destination >= entry.actions.length || from === destination) return;
        const [item] = entry.actions.splice(from, 1); entry.actions.splice(destination, 0, item);
        selected = {entry: entry.id, kind: 'action', item: id}; changed(true);
        note('Aktion verschoben. Ihre Wartezeit ist erhalten geblieben.');
    }

    function inspect() {
        const issues = [];
        const issue = (entry, message, kind = 'rule', item = '') => issues.push({entry: entry.id, message: `${entry.name || 'Ohne Namen'}: ${message}`, kind, item});
        const duration = value => Number.isInteger(value) && value >= 0 && value <= 86400;
        for (const entry of entries) {
            if (!entry.name.trim() || entry.name.length > 120) issue(entry, 'Bitte einen Namen mit höchstens 120 Zeichen eingeben.');
            if (entry.kind === 'device' && (entry.triggers.length < 1 || entry.triggers.length > 8)) issue(entry, 'Ein bis acht Auslöser sind nötig.');
            if (entry.actions.length < 1 || entry.actions.length > 16) issue(entry, 'Bitte mindestens eine und höchstens 16 Aktionen anlegen.');
            if (entry.kind !== 'sequence' && entry.conditions.length > 16) issue(entry, 'Höchstens 16 Bedingungen sind möglich.');
            if (entry.kind === 'time') {
                const schedule = entry.schedule;
                if (schedule.mode === 'clock') {
                    const times = schedule.at.split(',').map(time => time.trim());
                    if (times.length > 8 || times.some(time => !/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/.test(time)) || new Set(times.map(time => time.length === 5 ? time + ':00' : time)).size !== times.length) issue(entry, 'Bitte eine bis acht verschiedene gültige Uhrzeiten eingeben.', 'schedule');
                } else if (!Number.isInteger(schedule.offset) || Math.abs(schedule.offset) > 720) issue(entry, 'Der Sonnenzeitversatz muss zwischen −720 und 720 Minuten liegen.', 'schedule');
                if (!schedule.weekdays.length) issue(entry, 'Bitte mindestens einen Wochentag wählen.', 'schedule');
            }
            for (const kind of ['trigger', 'condition', 'action']) for (const item of entry[listFor(kind)] ?? []) {
                if (kind === 'action' && !duration(item.delay)) issue(entry, 'Die Wartezeit muss 0 bis 86400 ganze Sekunden betragen.', kind, item.id);
                if (kind === 'trigger' && !duration(item.hold)) issue(entry, 'Die Mindestdauer muss 0 bis 86400 ganze Sekunden betragen.', kind, item.id);
                if (item.type === 'sequence') {
                    if (entry.kind === 'sequence' || !entries.some(candidate => candidate.kind === 'sequence' && candidate.id === item.sequence)) issue(entry, 'Bitte eine vorhandene Aktionsfolge wählen.', kind, item.id);
                } else if (item.type === 'scene') {
                    if (!deviceById(item.device)?.scenes?.some(scene => scene.id === item.scene)) issue(entry, 'Bitte eine vorhandene Szene wählen.', kind, item.id);
                } else {
                    const meta = metaFor(item);
                    if (!meta || (kind === 'action' && !meta.writable)) issue(entry, 'Gerät und erlaubte Eigenschaft auswählen.', kind, item.id);
                    else if (meta.type === 'number' && (typeof item.value !== 'number' || !Number.isFinite(item.value))) issue(entry, 'Bitte einen gültigen Zahlenwert eingeben.', kind, item.id);
                    else if (meta.type !== 'number' && !meta.values.includes(item.value)) issue(entry, 'Bitte einen angebotenen Wert wählen.', kind, item.id);
                    if (kind === 'action' && item.property === 'brightness' && !(item.value >= 0 && item.value <= 254)) issue(entry, 'Helligkeit muss zwischen 0 und 254 liegen.', kind, item.id);
                }
            }
        }
        return issues;
    }

    function validate() {
        const issues = inspect();
        $('#issues').hidden = issues.length === 0;
        $('#issues').innerHTML = issues.length ? `<strong>${issues.length} Angaben bitte prüfen</strong><ul>${issues.map(item => `<li><button data-action="issue" data-entry="${esc(item.entry)}" data-kind="${item.kind}" data-id="${esc(item.item)}">${esc(item.message)}</button></li>`).join('')}</ul>` : '';
        verified = issues.length ? null : serial(entries); renderStatus();
        note(issues.length ? 'Die Vorschau ist noch nicht gültig. Klicke auf einen Hinweis, um die Stelle zu öffnen.' : 'Vorschau geprüft. Die echte Prüfung durch Z2M gehört erst zur späteren Anbindung.', issues.length ? 'error' : 'success');
    }

    function confirm(title, message, accept, label = 'Bestätigen') {
        confirmation = accept; $('#confirm-title').textContent = title; $('#confirm-message').textContent = message; $('#confirm-accept').textContent = label; $('#confirm-dialog').showModal();
    }

    const actions = {
        category: button => { category = button.dataset.kind; renderNavigation(); },
        entry: button => choose(button.dataset.id),
        block: button => { selected.kind = button.dataset.kind; selected.item = button.dataset.id; renderFlow(); renderInspector(); },
        schedule: () => { selected.kind = 'schedule'; selected.item = ''; renderFlow(); renderInspector(); },
        'select-rule': () => { selected.kind = 'rule'; selected.item = ''; renderFlow(); renderInspector(); },
        add: button => add(button.dataset.kind),
        move: button => { const from = current().actions.findIndex(item => item.id === button.dataset.id); move(button.dataset.id, from + Number(button.dataset.direction)); },
        'remove-block': () => {
            const entry = current(); const list = listFor(selected.kind); if (!entry || !list) return;
            entry[list] = entry[list].filter(item => item.id !== selected.item); selected.kind = 'rule'; selected.item = ''; changed(true); note('Baustein aus dem Entwurf entfernt.');
        },
        validate,
        save: () => {
            if (verified !== serial(entries)) return;
            if (serial(saved) !== serial(entries)) { running = false; runningEntries = []; }
            saved = clone(entries); renderStatus(); note('Vorschau für dieses Fenster gespeichert. Beim Neuladen beginnt wieder der Beispielstand.', 'success');
        },
        activate: () => {
            if (verified !== serial(entries) || serial(saved) !== serial(entries)) return;
            if (!saved.some(entry => entry.kind !== 'sequence' && entry.enabled)) { note('Keine Regel ist eingeschaltet. Wähle zuerst eine Regel und schalte sie im Entwurf ein.', 'error'); return; }
            running = true; runningEntries = clone(saved); renderStatus(); note('Beispielbetrieb aktiviert. Es werden keine Geräte geschaltet.', 'success');
        },
        stop: () => { running = false; runningEntries = []; renderStatus(); note('Beispielbetrieb gestoppt. Die gespeicherte Vorschau bleibt erhalten.'); },
        new: () => { $('#new-kind').value = category; $('#new-name').value = ''; $('#new-dialog').showModal(); $('#new-name').focus(); },
        'close-new': () => $('#new-dialog').close(),
        'copy-entry': () => {
            const entry = current(); if (!entry) return;
            const copy = clone(entry); copy.id = uid(); copy.name = (entry.name.slice(0, 112) + ' – Kopie').slice(0, 120);
            if (copy.kind !== 'sequence') copy.enabled = false;
            for (const list of ['triggers', 'conditions', 'actions']) for (const item of copy[list] ?? []) item.id = uid();
            entries.push(copy); selected = {entry: copy.id, kind: 'rule', item: ''}; category = copy.kind; changed(true); note('Kopie angelegt. Eine kopierte Automation beginnt ausgeschaltet.');
        },
        'delete-entry': () => {
            const entry = current(); if (!entry) return;
            if (entry.kind === 'sequence' && entries.some(other => other.actions.some(action => action.type === 'sequence' && action.sequence === entry.id))) { note('Diese Aktionsfolge wird noch verwendet. Entferne zuerst ihre Aufrufe.', 'error'); return; }
            confirm('Eintrag aus der Vorschau löschen?', `„${entry.name}“ wird nur aus diesem geöffneten Entwurf entfernt.`, () => { entries = entries.filter(candidate => candidate.id !== entry.id); selected = {entry: entries.find(candidate => candidate.kind === category)?.id ?? '', kind: 'rule', item: ''}; changed(true); note('Eintrag aus der Vorschau entfernt.'); }, 'Löschen');
        },
        reset: () => confirm('Beispielstand wiederherstellen?', 'Die Änderungen in diesem Fenster werden verworfen. Es werden ausschließlich die mitgelieferten Beispieldaten geladen.', () => { entries = clone(initial); saved = clone(initial); verified = null; running = false; runningEntries = []; selected = {entry: initial[0].id, kind: 'condition', item: 'demo.condition'}; category = 'device'; $('#search').value = ''; $('#issues').hidden = true; render(); note('Beispielstand wiederhergestellt.'); }, 'Zurücksetzen'),
        'cancel-confirm': () => { confirmation = null; $('#confirm-dialog').close(); },
        'accept-confirm': () => { const operation = confirmation; confirmation = null; $('#confirm-dialog').close(); operation?.(); },
        issue: button => choose(button.dataset.entry, button.dataset.kind, button.dataset.id)
    };

    document.addEventListener('click', event => {
        const target = event.target.closest('[data-action]');
        if (target && !target.disabled) actions[target.dataset.action]?.(target);
    });
    $('#flow').addEventListener('keydown', event => {
        if (event.target.matches('.flow-card') && ['Enter', ' '].includes(event.key)) { event.preventDefault(); event.target.click(); }
    });
    $('#inspector-fields').addEventListener('input', event => {
        if (event.target.matches('input[data-field]:not([type="checkbox"])')) updateField(event.target.dataset.field, event.target.value, event.target);
    });
    $('#inspector-fields').addEventListener('change', event => {
        const control = event.target;
        if (control.matches('select[data-field], input[type="checkbox"][data-field]')) updateField(control.dataset.field, control.value, control);
        if (control.dataset.day) {
            const day = Number(control.dataset.day); const schedule = current()?.schedule; if (!schedule) return;
            schedule.weekdays = control.checked ? [...schedule.weekdays, day].sort() : schedule.weekdays.filter(value => value !== day); changed(false);
        }
    });
    $('#search').addEventListener('input', renderNavigation);
    $('#new-form').addEventListener('submit', event => {
        event.preventDefault(); const name = $('#new-name').value.trim(); if (!name) { $('#new-name').focus(); return; }
        const type = $('#new-kind').value; const kind = type === 'sun' ? 'time' : type;
        const entry = {id: uid(), kind, name, actions: []};
        if (kind !== 'sequence') { entry.enabled = false; entry.retrigger = 'ignore'; entry.conditions = []; }
        if (kind === 'device') entry.triggers = [];
        if (kind === 'time') entry.schedule = {mode: type === 'sun' ? 'sunset' : 'clock', at: '18:00', offset: 0, timezone: 'Europe/Berlin', weekdays: [1, 2, 3, 4, 5, 6, 7]};
        entries.push(entry); selected = {entry: entry.id, kind: 'rule', item: ''}; category = kind; $('#search').value = ''; $('#new-dialog').close(); changed(true); note('Eintrag angelegt. Ergänze die Bausteine in der Mitte.');
    });
    function clearDrop() { document.querySelectorAll('.drop-before, .drop-after').forEach(card => card.classList.remove('drop-before', 'drop-after')); }
    const dropCard = event => document.elementFromPoint(event.clientX, event.clientY)?.closest('.flow-card[data-kind="action"]');
    function endDrag() {
        dragged = null; clearDrop(); document.querySelectorAll('.dragging').forEach(card => card.classList.remove('dragging'));
    }
    $('#flow').addEventListener('pointerdown', event => {
        const handle = event.target.closest('.grip');
        if (!handle || event.button !== 0) return;
        const card = handle.closest('.flow-card');
        event.preventDefault(); handle.setPointerCapture(event.pointerId);
        dragged = {id: card.dataset.id, pointer: event.pointerId}; card.classList.add('dragging');
    });
    $('#flow').addEventListener('pointermove', event => {
        if (!dragged || event.pointerId !== dragged.pointer) return;
        clearDrop(); const card = dropCard(event);
        if (!card || card.dataset.id === dragged.id) return;
        const rect = card.getBoundingClientRect();
        card.classList.add(event.clientY < rect.top + rect.height / 2 ? 'drop-before' : 'drop-after');
    });
    $('#flow').addEventListener('pointerup', event => {
        if (!dragged || event.pointerId !== dragged.pointer) return;
        const id = dragged.id; const card = dropCard(event); endDrag();
        if (!card || card.dataset.id === id) return;
        const entry = current(); const from = entry.actions.findIndex(item => item.id === id); const to = entry.actions.findIndex(item => item.id === card.dataset.id);
        const rect = card.getBoundingClientRect(); const after = event.clientY >= rect.top + rect.height / 2;
        move(id, to + (after ? 1 : 0) - (from < to ? 1 : 0));
    });
    $('#flow').addEventListener('pointercancel', endDrag);
    $('#flow').addEventListener('lostpointercapture', endDrag);
    render();
})();
