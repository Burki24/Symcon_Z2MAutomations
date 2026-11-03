/* Own bounded rule editor. Existing data is cloned; only changed rule fields are submitted. */
import {creationDevices, creationProperties, creationValue, requestIdentity} from './creator.js';

const copy = value => structuredClone(value);
const same = (a, b) => JSON.stringify(a) === JSON.stringify(b);
const editableFields = ['name', 'enabled', 'retrigger', 'triggers', 'conditions', 'actions'];
export function ruleChanges(original, working) {
    return Object.fromEntries(editableFields.filter(key => !same(original[key], working[key])).map(key => [key, copy(working[key])]));
}
export function targetDevice(data, target) {
    return data.devices.find(device => device.target.type === target?.type && device.target.id === target?.id && (device.target.system ?? '') === (target?.system ?? ''));
}
export function valueIndex(property, side, value) {
    return (side === 'target' ? property?.actionValues : property?.values)?.findIndex(entry => same(entry, value)) ?? -1;
}
const compare = {eq: 'ist gleich', ne: 'ist ungleich', gt: 'ist größer als', gte: 'ist mindestens', lt: 'ist kleiner als', lte: 'ist höchstens'};
const kinds = {triggers: {device: 'Geräteereignis', time: 'Uhrzeit', sun: 'Sonnenzeit'}, conditions: {device_value: 'Gerätewert', weekday: 'Wochentage', time_window: 'Zeitfenster'},
    actions: {set: 'Werte setzen', toggle: 'Umschalten', scene: 'Szene aufrufen', sequence: 'Aktionsfolge aufrufen'}};
const dayNames = ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];
const valueLabel = value => typeof value === 'boolean' ? (value ? 'Ja / erkannt' : 'Nein / nicht erkannt') : ({ON: 'Ein', OFF: 'Aus', TOGGLE: 'Umschalten'}[value] ?? String(value ?? 'Ohne Wert'));

export function installEditing({getData, getRule, reload, onEdited, summaries}) {
    const editButton = document.createElement('button');
    editButton.id = 'edit-automation-button'; editButton.textContent = 'Automation bearbeiten';
    document.querySelector('.entry-tools').prepend(editButton);
    const dialog = document.createElement('dialog');
    dialog.id = 'edit-dialog'; dialog.className = 'creation-dialog rule-edit-dialog'; dialog.setAttribute('aria-labelledby', 'edit-title');
    dialog.innerHTML = '<form id="edit-form"><span class="eyebrow">VORHANDENE AUTOMATION</span><h2 id="edit-title">Automation bearbeiten</h2><p>Änderungen betreffen nur den lokalen Symcon-Entwurf. Laufende Automationen und Z2M bleiben unverändert.</p><div id="edit-fields"></div><p id="edit-message" role="status" aria-live="polite"></p><div class="dialog-actions"><button type="button" id="edit-reload">Neu laden und Eingaben verwerfen</button><button type="button" id="edit-cancel">Abbrechen</button><button type="submit" id="edit-submit" class="primary">Änderungen in den Entwurf übernehmen</button></div></form>';
    document.body.append(dialog);
    const $ = name => dialog.querySelector('#edit-' + name);
    let original; let working; let context; let requestId; let busy = false; let serial = 0;
    const hint = text => { $('message').textContent = text; };
    const node = (tag, text, className) => { const element = document.createElement(tag); if (text !== undefined) element.textContent = text; if (className) element.className = className; return element; };
    function field(parent, label, value, set, options = {}) {
        const wrap = node('label', undefined, 'field'); wrap.append(node('span', label));
        const input = document.createElement(options.choices ? 'select' : 'input'); input.id = 'edit-field-' + (++serial);
        if (options.choices) {
            for (const [key, caption] of options.choices) input.add(new Option(caption, key));
            if (![...input.options].some(option => option.value === String(value ?? ''))) input.add(new Option('Bisheriger Wert nicht verfügbar', String(value ?? '')));
        } else {
            input.type = options.type ?? 'text';
            for (const key of ['min', 'max', 'step', 'maxLength']) if (options[key] !== undefined) input[key] = options[key];
        }
        if (input.type === 'checkbox') { input.checked = value === true; wrap.classList.add('edit-check'); }
        else input.value = value ?? '';
        input.disabled = options.disabled ?? false;
        if (options.required !== false && input.type !== 'checkbox') input.required = true;
        input.addEventListener(options.choices || input.type === 'checkbox' ? 'change' : 'input', () => set(input.type === 'checkbox' ? input.checked : input.value));
        wrap.append(input); parent.append(wrap); return input;
    }
    const number = (parent, label, object, key, initial = 0, min = 0, max = 86400, step = 1) => field(parent, label, object[key] ?? initial, text => { object[key] = text === '' ? null : Number(text); }, {type: 'number', min, max, step});
    const time = (parent, label, object, key) => field(parent, label, object[key], text => { object[key] = text.length === 5 ? text + ':00' : text; }, {type: 'time', step: 1});
    const zone = (parent, object) => field(parent, 'Zeitzone', object.timezone, text => { object.timezone = text; });
    const check = (parent, label, object, key) => field(parent, label, object[key] ?? false, value => { object[key] = value; }, {type: 'checkbox'});
    function button(parent, text, action, disabled = false) {
        const result = node('button', text); result.type = 'button'; result.disabled = disabled; result.addEventListener('click', action); parent.append(result); return result;
    }
    function propertyValue(parent, property, side, value, set) {
        if (!property) { parent.append(node('p', 'Bitte zuerst Gerät und Eigenschaft / Kanal auswählen.', 'field-help')); return; }
        if (property.type === 'numeric') {
            field(parent, 'Wert' + (property.unit ? ' (' + property.unit + ')' : ''), value, text => set(text === '' ? null : Number(text)), {type: 'number', step: side === 'target' ? property.step ?? 'any' : 'any',
                ...(side === 'target' ? {min: property.minimum, max: property.maximum} : {})});
        } else if (property.type === 'color') {
            field(parent, 'Farbe', value?.hex ?? '#ffffff', text => set({hex: text}), {type: 'color'});
        } else {
            const values = side === 'target' ? property.actionValues : property.values;
            field(parent, 'Wert', valueIndex(property, side, value) < 0 ? '' : String(valueIndex(property, side, value)), text => {
                if (text === '') { set(undefined); return; }
                set(creationValue(property, side, text));
            }, {choices: [['', 'Bitte Wert auswählen'], ...values.map((v, index) => [String(index), property.sceneNames?.[v] ?? property.valueLabels?.find(entry => same(entry.value, v))?.label ?? valueLabel(v)])]});
        }
    }
    function devicePicker(parent, side, current, set) {
        const box = node('div', undefined, 'edit-device-picker'); parent.append(box);
        const search = field(box, 'Gerät suchen', '', () => refreshOptions(), {type: 'search', required: false});
        const select = field(box, 'Gerät', current?.key ?? '', key => { const device = getData().devices.find(entry => entry.key === key); set(device); renderFields(); }, {choices: [['', 'Bitte Gerät auswählen']]});
        function refreshOptions() {
            const found = creationDevices(getData(), side, search.value);
            if (current && !found.some(device => device.key === current.key)) found.unshift(current);
            select.replaceChildren(new Option('Bitte Gerät auswählen', ''));
            for (const device of found) select.add(new Option(`${device.name} · ${device.target.system ?? getData().module.baseTopic}`, device.key));
            if (current) select.value = current.key;
        }
        refreshOptions();
    }
    function sourceFields(parent, item) {
        const device = getData().devices.find(entry => entry.key === item.device && !entry.target.system && entry.target.type === 'device');
        devicePicker(parent, 'source', device, selected => { item.device = selected?.key ?? ''; item.property = ''; delete item.value; });
        const properties = creationProperties(device, 'source');
        field(parent, 'Eigenschaft / Kanal', item.property, property => { item.property = property; delete item.value; renderFields(); }, {choices: [['', 'Bitte Eigenschaft / Kanal auswählen'], ...properties.map(meta => [meta.id, meta.label])]});
        const property = properties.find(meta => meta.id === item.property);
        field(parent, 'Vergleich', item.operator, value => { item.operator = value; }, {choices: Object.entries(compare).filter(([key]) => property?.type === 'numeric' || ['eq', 'ne'].includes(key))});
        propertyValue(parent, property, 'source', item.value, value => { if (value === undefined) delete item.value; else item.value = value; });
        if (item.type === 'device') { number(parent, 'Mindestdauer (Sekunden)', item, 'for_seconds'); check(parent, 'Nur bei Wertänderung auslösen', item, 'on_change'); }
        else check(parent, 'Nur beim Start prüfen', item, 'start_only');
    }
    function weekdays(parent, item, optional = false) {
        if (optional) {
            field(parent, 'Zeitfenster auf Wochentage begrenzen', Object.hasOwn(item, 'weekdays'), value => { if (value) item.weekdays = [1, 2, 3, 4, 5, 6, 7]; else delete item.weekdays; renderFields(); }, {type: 'checkbox'});
            if (!Object.hasOwn(item, 'weekdays')) return;
        }
        const row = node('div', undefined, 'edit-weekdays'); parent.append(row);
        for (let day = 1; day <= 7; day++) field(row, dayNames[day - 1], item.weekdays?.includes(day), checked => {
            item.weekdays = checked ? [...(item.weekdays ?? []), day].sort((a, b) => a - b) : item.weekdays.filter(value => value !== day);
        }, {type: 'checkbox'});
    }
    function actionFields(parent, item) {
        number(parent, 'Wartezeit vor diesem Schritt (Sekunden)', item, 'delay_seconds');
        if (item.type === 'sequence') {
            field(parent, 'Aktionsfolge', item.sequence_id, value => { item.sequence_id = value; }, {choices: [['', 'Bitte Aktionsfolge auswählen'], ...(getData().configuration.sequences ?? []).map(entry => [entry.id, entry.name])]});
            parent.append(node('p', 'Die gespeicherte Aktionsfolge wird verwendet. Ihre Definition bleibt unverändert.', 'field-help')); return;
        }
        const device = targetDevice(getData(), item.target);
        devicePicker(parent, 'target', device, selected => {
            item.target = selected ? copy(selected.target) : {type: 'device', id: ''};
            if (item.type === 'set') item.values = {};
            else if (item.type === 'toggle') item.property = '';
            else { delete item.scene_id; delete item.endpoint; }
        });
        let properties = creationProperties(device, 'target');
        if (item.type === 'toggle') {
            properties = properties.filter(meta => /^state(?:_[A-Za-z0-9_]+)?$/.test(meta.id) && meta.actionValues.includes('TOGGLE'));
            field(parent, 'Eigenschaft / Kanal', item.property, value => { item.property = value; }, {choices: [['', 'Bitte Kanal auswählen'], ...properties.map(meta => [meta.id, meta.label])]}); return;
        }
        if (item.type === 'scene') {
            properties = properties.filter(meta => meta.sceneEndpoint !== undefined);
            let property = properties.find(meta => meta.sceneEndpoint === (item.endpoint ?? 0));
            field(parent, 'Szenenkanal', property?.id ?? '', value => {
                property = properties.find(meta => meta.id === value); delete item.scene_id;
                if (property?.sceneEndpoint > 0) item.endpoint = property.sceneEndpoint; else delete item.endpoint;
                renderFields();
            }, {choices: [['', 'Bitte Szenenkanal auswählen'], ...properties.map(meta => [meta.id, meta.label])]});
            propertyValue(parent, property, 'target', item.scene_id, value => { if (value === undefined) delete item.scene_id; else item.scene_id = value; }); return;
        }
        properties = properties.filter(meta => meta.sceneEndpoint === undefined);
        for (const [key, value] of Object.entries(item.values)) {
            const command = node('fieldset', undefined, 'edit-command'); command.append(node('legend', 'Zu setzender Wert')); parent.append(command);
            field(command, 'Eigenschaft / Kanal', key, property => {
                delete item.values[key]; if (property) item.values[property] = undefined; else item.values[''] = undefined; renderFields();
            }, {choices: [['', 'Bitte Eigenschaft / Kanal auswählen'], ...properties.filter(meta => meta.id === key || !Object.hasOwn(item.values, meta.id)).map(meta => [meta.id, meta.label])]});
            const meta = properties.find(property => property.id === key);
            propertyValue(command, meta ? {...meta, actionValues: meta.actionValues.filter(v => v !== 'TOGGLE')} : null, 'target', value, next => { item.values[key] = next; });
            button(command, 'Wert entfernen', () => { delete item.values[key]; renderFields(); });
        }
        button(parent, 'Weiteren Wert hinzufügen', () => { item.values[''] = undefined; renderFields(); }, Object.keys(item.values).length >= 8 || Object.hasOwn(item.values, ''));
        parent.append(node('p', 'Mehrere Werte werden als gemeinsamer Gerätebefehl gesetzt. Zum Umschalten die Aktionsart „Umschalten“ wählen.', 'field-help'));
    }
    function newItem(section, type) {
        if (type === 'device' || type === 'device_value') return {type, device: '', property: '', operator: 'eq'};
        if (type === 'time') return {type, at: '18:00:00', timezone: 'Europe/Berlin'};
        if (type === 'sun') return {type, event: 'sunset', latitude: 0, longitude: 0, offset_minutes: 0, timezone: 'Europe/Berlin'};
        if (type === 'weekday') return {type, weekdays: [1, 2, 3, 4, 5], timezone: 'Europe/Berlin'};
        if (type === 'time_window') return {type, after: '18:00:00', before: '06:00:00', timezone: 'Europe/Berlin'};
        if (type === 'sequence') return {type, sequence_id: '', delay_seconds: 0};
        return {type, target: {type: 'device', id: ''}, delay_seconds: 0, ...(type === 'set' ? {values: {}} : type === 'toggle' ? {property: ''} : {})};
    }
    function renderFields() {
        const scroll = dialog.scrollTop;
        const expanded = new Map([...dialog.querySelectorAll('details[data-section]')].map(element => [element.dataset.section, element.open]));
        serial = 0; const root = $('fields'); root.replaceChildren();
        field(root, 'Name der Automation', working.name, value => { working.name = value; }, {maxLength: 120});
        check(root, 'Regel im Entwurf eingeschaltet (ändert nicht den laufenden Betrieb)', working, 'enabled');
        field(root, 'Bei erneuter Auslösung', working.retrigger ?? 'ignore', value => { working.retrigger = value; }, {choices: Object.entries({ignore: 'Ignorieren', restart: 'Von vorn beginnen', extend: 'Laufende Wartezeit zurücksetzen'})});
        for (const [section, title] of [['triggers', '1. Wenn – Auslöser (ODER)'], ['conditions', '2. Nur wenn – Bedingungen (UND)'], ['actions', '3. Dann – Aktionen in dieser Reihenfolge']]) {
            const details = node('details', undefined, 'edit-section'); details.dataset.section = section; details.open = expanded.get(section) ?? true; details.append(node('summary', title)); root.append(details);
            const allowed = getData().editSections?.[original.id]?.[section] === true;
            if (!allowed) {
                details.append(node('p', 'Dieser Bereich bleibt unverändert erhalten. Gerätedaten oder Schaltrechte fehlen, oder die Angaben können hier nicht vollständig bearbeitet werden. Bitte bei Bedarf im nativen Formular prüfen.', 'edit-preserved'));
                for (const item of working[section]) details.append(node('p', summaries[section](item), 'field-help'));
                continue;
            }
            working[section].forEach((item, index) => {
                const row = node('fieldset', undefined, 'edit-row'); row.append(node('legend', `${index + 1}. ${kinds[section][item.type]}`)); details.append(row);
                const controls = node('div', undefined, 'edit-row-fields'); row.append(controls);
                field(controls, 'Art des Eintrags', item.type, type => {
                    const replacement = newItem(section, type);
                    if (section === 'actions') {
                        replacement.delay_seconds = item.delay_seconds ?? 0;
                        if (item.target && type !== 'sequence') replacement.target = copy(item.target);
                    }
                    if (item.timezone && replacement.timezone) replacement.timezone = item.timezone;
                    working[section][index] = replacement; renderFields();
                }, {choices: Object.entries(kinds[section])});
                if (item.type === 'device' || item.type === 'device_value') sourceFields(controls, item);
                else if (item.type === 'time') { time(controls, 'Uhrzeit', item, 'at'); zone(controls, item); }
                else if (item.type === 'sun') {
                    field(controls, 'Sonnenereignis', item.event, value => { item.event = value; }, {choices: [['sunrise', 'Sonnenaufgang'], ['sunset', 'Sonnenuntergang']]});
                    number(controls, 'Breitengrad', item, 'latitude', 0, -90, 90, 'any'); number(controls, 'Längengrad', item, 'longitude', 0, -180, 180, 'any');
                    number(controls, 'Zeitversatz (Minuten)', item, 'offset_minutes', 0, -720, 720); zone(controls, item);
                } else if (item.type === 'weekday') { weekdays(controls, item); zone(controls, item); }
                else if (item.type === 'time_window') { time(controls, 'Von', item, 'after'); time(controls, 'Bis', item, 'before'); weekdays(controls, item, true); zone(controls, item); }
                else actionFields(controls, item);
                const tools = node('div', undefined, 'edit-row-tools'); row.append(tools);
                if (section === 'actions') for (const [delta, text] of [[-1, 'Nach oben'], [1, 'Nach unten']]) button(tools, text, () => {
                    const destination = index + delta; [working[section][destination], working[section][index]] = [item, working[section][destination]]; renderFields();
                }, index + delta < 0 || index + delta >= working[section].length);
                button(tools, 'Eintrag entfernen', () => { working[section].splice(index, 1); renderFields(); }, section !== 'conditions' && working[section].length === 1);
            });
            const tools = node('div', undefined, 'edit-row-tools'); details.append(tools); let type = Object.keys(kinds[section])[0];
            field(tools, 'Art des neuen Eintrags', type, value => { type = value; }, {choices: Object.entries(kinds[section])});
            button(tools, 'Eintrag hinzufügen', () => { working[section].push(newItem(section, type)); renderFields(); }, working[section].length >= (section === 'triggers' ? 8 : 16));
        }
        dialog.scrollTop = scroll;
    }
    function open(rule) {
        original = copy(rule); delete original.kind; working = copy(original); context = getData().createContext; requestId = requestIdentity();
        renderFields(); hint(getData().editNotice || 'Alle Angaben sind vorbelegt. Erst „Änderungen in den Entwurf übernehmen“ ändert den lokalen Entwurf.');
        $('submit').disabled = !getData().editAllowed;
        if (!dialog.open) dialog.showModal(); $('fields').querySelector('input')?.focus();
    }
    editButton.addEventListener('click', () => { const rule = getRule(); if (rule?.triggers) open(rule); });
    $('cancel').addEventListener('click', () => { if (!busy) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    $('reload').addEventListener('click', async () => {
        if (busy || (Object.keys(ruleChanges(original, working)).length && !confirm('Eigene Eingaben verwerfen und den aktuellen lokalen Entwurf laden?'))) return;
        if (await reload()) { const rule = getData().configuration?.automations.find(entry => entry.id === original.id); if (rule) open(rule); else { hint('Diese Automation wurde inzwischen entfernt. Bitte den Dialog schließen.'); $('submit').disabled = true; } }
        else hint('Die Ansicht konnte nicht geladen werden. Ihre Eingaben bleiben erhalten.');
    });
    $('form').addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !getData().editAllowed) return;
        const payload = {requestId, context, ruleId: original.id, changes: ruleChanges(original, working)};
        const controls = [...dialog.querySelectorAll('button, input, select')].map(element => [element, element.disabled]);
        busy = true; for (const [element] of controls) element.disabled = true; hint('Änderungen werden geprüft und nur im lokalen Entwurf übernommen …');
        const controller = new AbortController(); const timer = setTimeout(() => controller.abort(), 15000);
        try {
            const endpoint = new URL('edit', location.href); endpoint.hash = '';
            const response = await fetch(endpoint, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-Z2MA-Read-Token': location.hash.slice(1)}, body: JSON.stringify(payload), credentials: 'same-origin', cache: 'no-store', redirect: 'error', signal: controller.signal});
            const text = await response.text(); if (text.length > 4096) throw new Error('transport'); const result = JSON.parse(text);
            if (!response.ok) { hint(result.message || 'Die Änderung konnte nicht übernommen werden. Ihre Eingaben bleiben erhalten.'); return; }
            dialog.close(); await onEdited(result.ruleId);
        } catch { hint('Keine Bestätigung vom Modul erhalten. Ihre Eingaben bleiben erhalten. Derselbe unveränderte Auftrag kann erneut gesendet werden.'); }
        finally { clearTimeout(timer); busy = false; for (const [element, disabled] of controls) element.disabled = disabled; }
    });
    return {refresh() { editButton.disabled = !getRule()?.triggers || !getData()?.editAllowed; editButton.title = getData()?.editNotice || 'Ausgewählte Automation im lokalen Entwurf bearbeiten'; }};
}
