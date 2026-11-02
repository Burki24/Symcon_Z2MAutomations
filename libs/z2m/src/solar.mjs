// Own implementation of the published NOAA general solar equations (solareqns.PDF).
// Apparent horizon 90.833 degrees; a calculated time, not a weather observation.
const SOLAR_DAY = 86400000;
const solarRad = Math.PI / 180;

export function solarEventUTC(day, latitude, longitude, event) {
    const midnight = Date.parse(day + 'T00:00:00Z');
    if (!Number.isFinite(midnight) || new Date(midnight).toISOString().slice(0, 10) !== day
        || !Number.isFinite(latitude) || Math.abs(latitude) > 90 || !Number.isFinite(longitude) || Math.abs(longitude) > 180
        || !['sunrise', 'sunset'].includes(event)) return null;
    let instant = midnight + (720 - 4 * longitude) * 60000;
    for (let iteration = 0; iteration < 4; iteration++) {
        const date = new Date(instant), year = date.getUTCFullYear();
        const yearStart = Date.UTC(year, 0, 1), days = (Date.UTC(year + 1, 0, 1) - yearStart) / SOLAR_DAY;
        const angle = 2 * Math.PI / days * ((instant - yearStart) / SOLAR_DAY - 0.5);
        const equation = 229.18 * (0.000075 + 0.001868 * Math.cos(angle) - 0.032077 * Math.sin(angle)
            - 0.014615 * Math.cos(2 * angle) - 0.040849 * Math.sin(2 * angle));
        const declination = 0.006918 - 0.399912 * Math.cos(angle) + 0.070257 * Math.sin(angle)
            - 0.006758 * Math.cos(2 * angle) + 0.000907 * Math.sin(2 * angle)
            - 0.002697 * Math.cos(3 * angle) + 0.00148 * Math.sin(3 * angle);
        const cosine = (Math.cos(90.833 * solarRad) - Math.sin(latitude * solarRad) * Math.sin(declination))
            / (Math.cos(latitude * solarRad) * Math.cos(declination));
        if (!Number.isFinite(cosine) || Math.abs(cosine) > 1) return null;
        const hourAngle = Math.acos(cosine) / solarRad * (event === 'sunrise' ? 1 : -1);
        instant = midnight + (720 - 4 * (longitude + hourAngle) - equation) * 60000;
    }
    return Math.round(instant);
}

export function solarForLocalDay(day, trigger) {
    const formatter = new Intl.DateTimeFormat('en-CA', {timeZone: trigger.timezone, year: 'numeric', month: '2-digit', day: '2-digit'});
    const anchor = Date.parse(day + 'T00:00:00Z');
    for (const shift of [-1, 0, 1]) {
        const utcDay = new Date(anchor + shift * SOLAR_DAY).toISOString().slice(0, 10);
        const instant = solarEventUTC(utcDay, trigger.latitude, trigger.longitude, trigger.event);
        if (instant === null) continue;
        const parts = Object.fromEntries(formatter.formatToParts(instant).map(p => [p.type, p.value]));
        if (`${parts.year}-${parts.month}-${parts.day}` === day) {
            return Math.round(instant / 60000) * 60000 + trigger.offset_minutes * 60000;
        }
    }
    return null;
}
