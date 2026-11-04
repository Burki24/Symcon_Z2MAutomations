/* Own small creation dialog; device metadata and all draft writes stay in the module. */
export function creationProperties(device, side) {
    return (device?.properties ?? []).filter(property => side === 'source' ? property.readable && property.type !== 'color' : property.writable);
}

export function creationDevices(data, side, search = '') {
    const term = search.trim().toLocaleLowerCase('de');
    return (data?.devices ?? []).filter(device => (side !== 'source' || (device.target.type === 'device' && !device.target.system))
        && creationProperties(device, side).length > 0
        && `${device.name} ${device.key} ${device.target.system ?? data.module.baseTopic} ${device.target.id}`.toLocaleLowerCase('de').includes(term));
}

export function creationValue(property, side, input) {
    if (!property || input === '') throw new Error('Bitte einen Wert auswählen oder eingeben.');
    if (property.type === 'numeric') {
        const number = Number(input);
        if (!Number.isFinite(number)) throw new Error('Bitte eine gültige Zahl eingeben.');
        return number;
    }
    if (property.type === 'color') return {hex: input};
    const values = side === 'target' ? property.actionValues : property.values;
    const index = Number(input);
    if (!Number.isInteger(index) || index < 0 || index >= values.length) throw new Error('Bitte einen angebotenen Wert auswählen.');
    return values[index];
}

export function creationSchedule(type, values) {
    const timezone = values.timezone?.trim();
    if (!timezone) throw new Error('Bitte eine Zeitzone auswählen.');
    if (type === 'time') {
        if (!Array.isArray(values.times) || values.times.length < 1 || values.times.length > 8) throw new Error('Bitte eine bis acht Uhrzeiten eintragen.');
        const times = values.times.map(value => {
            if (typeof value !== 'string' || !/^([01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/.test(value)) throw new Error('Bitte jede Uhrzeit vollständig eintragen.');
            return value.length === 5 ? value + ':00' : value;
        });
        if (new Set(times).size !== times.length) throw new Error('Diese Uhrzeit ist mehrfach eingetragen. Bitte jede Uhrzeit nur einmal verwenden.');
        return {type, times, timezone};
    }
    if (type !== 'sun') throw new Error('Bitte eine angebotene Auslöserart auswählen.');
    if (!['sunrise', 'sunset'].includes(values.event)) throw new Error('Bitte Sonnenaufgang oder Sonnenuntergang auswählen.');
    const number = (text, limit, message) => {
        if (typeof text !== 'string' || text.trim() === '' || !Number.isFinite(Number(text)) || Math.abs(Number(text)) > limit) throw new Error(message);
        return Number(text);
    };
    const latitude = number(values.latitude, 90, 'Bitte den Breitengrad zwischen -90 und 90 angeben.');
    const longitude = number(values.longitude, 180, 'Bitte den Längengrad zwischen -180 und 180 angeben.');
    const offset_minutes = number(values.offset, 720, 'Bitte einen Zeitversatz zwischen -720 und 720 Minuten angeben.');
    if (!Number.isInteger(offset_minutes)) throw new Error('Bitte den Zeitversatz in ganzen Minuten angeben.');
    return {type, event: values.event, latitude, longitude, offset_minutes, timezone};
}

export function requestIdentity() {
    const bytes = crypto.getRandomValues(new Uint8Array(16));
    bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
    const hex = [...bytes].map(byte => byte.toString(16).padStart(2, '0')).join('');
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

export function installCreation({getData, reload, onCreated}) {
    const button = document.createElement('button');
    button.id = 'new-automation-button'; button.className = 'primary'; button.textContent = '+ Neue Automation';
    document.querySelector('.toolbar').prepend(button);
    const dialog = document.createElement('dialog');
    dialog.id = 'create-dialog'; dialog.className = 'creation-dialog'; dialog.setAttribute('aria-labelledby', 'create-title');
    dialog.innerHTML = `<form id="create-form"><span class="eyebrow">NEUE AUTOMATION</span><h2 id="create-title">Neue Automation</h2>
        <p>Wählen Sie, wann die Automation starten und welches Gerät sie schalten soll. Die neue Regel beginnt ausgeschaltet.</p>
        <label class="field">Name der Automation<input id="create-name" required maxlength="120" autocomplete="off" placeholder="Zum Beispiel: Flurlicht bei Bewegung"></label>
        <div class="creation-columns">${['source', 'target'].map(side => `<fieldset class="creation-side"><legend>${side === 'source' ? '1. Wann soll die Automation starten?' : '2. Dann dieses Gerät schalten'}</legend>
        ${side === 'source' ? '<label class="field">Auslöserart<select id="create-type"><option value="device">Geräteereignis</option><option value="time">Uhrzeit</option><option value="sun">Sonnenaufgang / Sonnenuntergang</option></select></label><div id="create-device-source">' : ''}
        <label class="field">${side === 'source' ? 'Auslösendes Gerät suchen' : 'Zielgerät suchen'}<input id="create-${side}-search" type="search" autocomplete="off" placeholder="Gerätename, Kennung oder Z2M-System"></label>
        <label class="field">${side === 'source' ? 'Auslösendes Gerät' : 'Zielgerät'}<select id="create-${side}-device" size="5" required></select></label>
        <p class="field-help" id="create-${side}-count"></p>
        <label class="field">${side === 'source' ? 'Eigenschaft / Kanal des Auslösers' : 'Eigenschaft / Kanal des Ziels'}<select id="create-${side}-property" required></select></label>
        ${side === 'source' ? '<label class="field">Vergleich<select id="create-operator"><option value="eq">ist gleich</option><option value="ne">ist ungleich</option></select></label>' : ''}
        <label class="field">${side === 'source' ? 'Auslösender Wert' : 'Gewünschter Wert'}<span id="create-${side}-value-box"></span></label>
        ${side === 'source' ? `</div><div id="create-clock-source" hidden><p class="field-help">Jede eingetragene Uhrzeit startet dieselbe Automation. Höchstens acht Zeiten.</p><div id="create-times"></div><button type="button" id="create-add-time">Uhrzeit hinzufügen</button></div>
        <div id="create-sun-source" hidden>
        <label class="field">Sonnenereignis<select id="create-sun-event"><option value="sunrise">Sonnenaufgang</option><option value="sunset">Sonnenuntergang</option></select></label>
        <label class="field">Breitengrad des Standorts<input id="create-latitude" type="number" min="-90" max="90" step="any" required></label>
        <label class="field">Längengrad des Standorts<input id="create-longitude" type="number" min="-180" max="180" step="any" required></label>
        <p class="field-help">Standort in Dezimalgrad eingeben. Daraus berechnet Z2M die Sonnenzeit; eine Ortung wird nicht gestartet.</p>
        <label class="field">Zeitversatz (Minuten)<input id="create-offset" type="number" min="-720" max="720" step="1" value="0" required></label>
        <p class="field-help">0 = zur Sonnenzeit. -30 = 30 Minuten früher, 30 = 30 Minuten später. Höchstens 12 Stunden davor oder danach.</p></div>
        <label class="field" id="create-zone-field" hidden>Zeitzone<select id="create-timezone" required></select></label>
        <p class="field-help" id="create-schedule-help" hidden>Zeiten gelten täglich in der ausgewählten Zeitzone. Verpasste Zeiten werden nicht nachgeholt. Bedingungen und weitere Schritte können Sie nach dem Anlegen mit „Automation bearbeiten“ ergänzen.</p>` : ''}
        </fieldset>`).join('')}</div>
        <p class="creation-note">Auslöser dürfen lesbare Sensoren sein. Als Ziel werden nur schaltbare Eigenschaften angeboten. Bestehende Regeln bleiben erhalten; Z2M wird hier nicht verändert.</p>
        <p id="create-message" role="status" aria-live="polite"></p>
        <div class="dialog-actions"><button type="button" id="create-reload">Ansicht aktualisieren</button><button type="button" id="create-cancel">Abbrechen</button><button type="submit" id="create-submit" class="primary">Ausgeschaltet zum Entwurf hinzufügen</button></div></form>`;
    document.body.append(dialog);
    const field = id => dialog.querySelector('#create-' + id);
    const label = value => typeof value === 'boolean' ? (value ? 'Ja / erkannt' : 'Nein / nicht erkannt')
        : ({ON: 'Ein', OFF: 'Aus', TOGGLE: 'Umschalten'}[value] ?? String(value ?? 'Ohne Wert'));
    const hint = text => { field('message').textContent = text; };
    let context = ''; let requestId = ''; let busy = false;
    const selectedDevice = side => getData()?.devices.find(device => device.key === field(side + '-device').value);
    const selectedProperty = side => creationProperties(selectedDevice(side), side).find(property => property.id === field(side + '-property').value);

    function updateSourceType() {
        const type = field('type').value;
        for (const [id, active] of [['device-source', type === 'device'], ['clock-source', type === 'time'], ['sun-source', type === 'sun'], ['zone-field', type !== 'device']]) {
            const box = field(id); box.hidden = !active;
            for (const control of box.querySelectorAll('input,select,button')) control.disabled = !active;
        }
        field('schedule-help').hidden = type === 'device';
        field('add-time').disabled = type !== 'time' || field('times').children.length >= 8;
        for (const control of field('times').querySelectorAll('button')) control.disabled = type !== 'time' || field('times').children.length === 1;
    }
    function addTime(value = '') {
        if (field('times').children.length >= 8) return;
        const row = document.createElement('div'); row.className = 'creation-time-row';
        const wrap = document.createElement('label'); wrap.className = 'field'; wrap.append(document.createTextNode('Uhrzeit'));
        const input = document.createElement('input'); input.type = 'time'; input.step = '1'; input.required = true; input.value = value;
        wrap.append(input); row.append(wrap);
        const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Entfernen';
        remove.addEventListener('click', () => { row.remove(); updateSourceType(); }); row.append(remove);
        field('times').append(row); updateSourceType();
    }
    function fillTimezones(reset = false) {
        const previous = reset ? 'Europe/Berlin' : field('timezone').value || 'Europe/Berlin';
        field('timezone').replaceChildren(...(getData()?.timezones ?? ['Europe/Berlin', 'UTC']).map(zone => new Option(zone, zone)));
        field('timezone').value = previous;
        if (!field('timezone').value) field('timezone').prepend(new Option('Bitte Zeitzone auswählen', '', true, true));
    }

    function fillValue(side, preserved) {
        const property = selectedProperty(side); const box = field(side + '-value-box'); box.replaceChildren();
        const value = document.createElement(property && ['numeric', 'color'].includes(property.type) ? 'input' : 'select');
        value.id = 'create-' + side + '-value'; value.required = true; value.disabled = !property;
        if (property?.type === 'numeric') {
            value.type = 'number'; value.step = side === 'target' ? String(property.step ?? 'any') : 'any';
            if (side === 'target') for (const key of ['minimum', 'maximum']) if (property[key] !== undefined) value[key === 'minimum' ? 'min' : 'max'] = property[key];
        } else if (property?.type === 'color') { value.type = 'color'; value.value = '#ffffff'; }
        else {
            value.add(new Option('Bitte Wert auswählen', ''));
            (side === 'target' ? property?.actionValues : property?.values)?.forEach((entry, index) => value.add(new Option(property.valueLabels?.find(item => item.value === entry)?.label ?? label(entry), String(index))));
        }
        if (preserved !== undefined) value.value = preserved;
        box.append(value);
        if (side === 'source') {
            const previous = field('operator').value;
            field('operator').replaceChildren(...Object.entries({eq: 'ist gleich', ne: 'ist ungleich', ...(property?.type === 'numeric' ? {gt: 'ist größer als', gte: 'ist mindestens', lt: 'ist kleiner als', lte: 'ist höchstens'} : {})}).map(([key, text]) => new Option(text, key)));
            if ([...field('operator').options].some(option => option.value === previous)) field('operator').value = previous;
        }
    }

    function fillProperties(side, preserve = false) {
        const select = field(side + '-property'); const previous = select.value;
        const value = preserve ? field(side + '-value')?.value : undefined;
        select.replaceChildren(new Option('Bitte Eigenschaft / Kanal auswählen', ''));
        creationProperties(selectedDevice(side), side).forEach(property => select.add(new Option(property.label, property.id)));
        select.disabled = !selectedDevice(side);
        if (preserve && [...select.options].some(option => option.value === previous)) select.value = previous;
        fillValue(side, value);
    }

    function fillDevices(side, preserve = false) {
        const select = field(side + '-device'); const previous = select.value;
        const devices = creationDevices(getData(), side, field(side + '-search').value);
        select.replaceChildren(new Option('Bitte Gerät auswählen', ''));
        devices.forEach(device => select.add(new Option(`${device.name} · ${device.target.system ?? getData().module.baseTopic} · ${device.target.id}`, device.key)));
        if (devices.some(device => device.key === previous)) select.value = previous;
        field(side + '-count').textContent = devices.length ? `${devices.length} passende Geräte oder Gruppen` : 'Keine passenden Geräte. Suchtext und geladene Gerätedaten prüfen.';
        fillProperties(side, preserve && select.value === previous);
    }

    function updateContext(newRequest = false) {
        context = getData()?.createContext ?? '';
        if (newRequest || !requestId) requestId = requestIdentity();
        field('submit').disabled = !getData()?.createAllowed;
        hint(getData()?.createNotice || 'Die Auswahl wird vor dem Übernehmen nochmals im Modul geprüft.');
    }
    button.addEventListener('click', () => {
        if (!getData()) return;
        field('form').reset(); updateContext(true);
        for (const side of ['source', 'target']) fillDevices(side);
        fillTimezones(true); field('times').replaceChildren(); addTime(); updateSourceType();
        dialog.showModal(); field('name').focus();
    });
    field('type').addEventListener('change', updateSourceType);
    field('add-time').addEventListener('click', () => addTime());
    for (const side of ['source', 'target']) {
        field(side + '-search').addEventListener('input', () => fillDevices(side, true));
        field(side + '-device').addEventListener('change', () => fillProperties(side));
        field(side + '-property').addEventListener('change', () => fillValue(side));
    }
    field('cancel').addEventListener('click', () => { if (!busy) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
    field('reload').addEventListener('click', async () => {
        if (busy) return;
        if (await reload()) { updateContext(); for (const side of ['source', 'target']) fillDevices(side, true); fillTimezones(); updateSourceType(); }
        else hint('Die Ansicht konnte nicht aktualisiert werden. Ihre Eingaben bleiben erhalten. Bitte Verbindung prüfen.');
    });
    field('form').addEventListener('submit', async event => {
        event.preventDefault(); if (busy || !getData()?.createAllowed) return;
        let payload;
        try {
            const choice = side => ({device: field(side + '-device').value, property: field(side + '-property').value,
                value: creationValue(selectedProperty(side), side, field(side + '-value').value), ...(side === 'source' ? {operator: field('operator').value} : {})});
            const type = field('type').value;
            const source = type === 'device' ? choice('source') : creationSchedule(type, {times: [...field('times').querySelectorAll('input')].map(input => input.value),
                timezone: field('timezone').value, event: field('sun-event').value, latitude: field('latitude').value, longitude: field('longitude').value, offset: field('offset').value});
            payload = {requestId, context, name: field('name').value, source, target: choice('target')};
        } catch (error) { hint(error.message); return; }
        const controls = [...dialog.querySelectorAll('button, input, select')].map(element => [element, element.disabled]);
        busy = true;
        for (const [control] of controls) control.disabled = true;
        hint('Auswahl wird geprüft und ausgeschaltet zum Symcon-Entwurf hinzugefügt …');
        const controller = new AbortController(); const timer = setTimeout(() => controller.abort(), 15000);
        try {
            const endpoint = new URL('create', location.href); endpoint.hash = '';
            const response = await fetch(endpoint, {method: 'POST', headers: {'Content-Type': 'application/json', 'X-Z2MA-Read-Token': location.hash.slice(1)}, body: JSON.stringify(payload), cache: 'no-store', credentials: 'same-origin', redirect: 'error', signal: controller.signal});
            const text = await response.text();
            if (text.length > 4096) throw new Error('transport');
            const result = JSON.parse(text);
            if (!response.ok) { hint(result.message || 'Die Automation konnte nicht übernommen werden. Bitte die Angaben prüfen.'); return; }
            dialog.close(); await onCreated(result.ruleId);
        } catch { hint('Keine Bestätigung vom Modul erhalten. Ihre Eingaben bleiben erhalten. Sie können denselben Auftrag erneut senden; er wird nicht doppelt angelegt.'); }
        finally {
            clearTimeout(timer); busy = false;
            for (const [control, disabled] of controls) control.disabled = disabled;
            field('submit').disabled = !getData()?.createAllowed;
        }
    });
}
