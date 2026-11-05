/* Shared typed metadata and scheduling helpers for the rule editor. */
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
