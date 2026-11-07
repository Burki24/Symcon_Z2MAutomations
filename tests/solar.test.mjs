import assert from 'node:assert/strict';
import test from 'node:test';
import {solarEventUTC, solarForLocalDay} from '../libs/z2m/src/solar.mjs';
import references from './fixtures/solar-reference.json' with {type: 'json'};

test('solar times agree with an independent astronomical reference within five minutes', () => {
    // Synthetic equatorial reference: PHP date_sun_info, apparent solar horizon.
    const rise = solarEventUTC('2026-03-20', 0, 0, 'sunrise');
    const set = solarEventUTC('2026-03-20', 0, 0, 'sunset');
    assert.ok(Math.abs(rise - Date.parse('2026-03-20T06:04:03Z')) < 300000);
    assert.ok(Math.abs(set - Date.parse('2026-03-20T18:10:51Z')) < 300000);
});

test('independent PHP date_sun_info fixtures cover seasons, leap year and both longitude signs', () => {
    for (const row of references) for (const event of ['sunrise', 'sunset']) {
        const actual = solarEventUTC(row.day, row.latitude, row.longitude, event);
        assert.ok(actual !== null && Math.abs(actual - row[event]) < 300000, JSON.stringify({row, event, actual}));
    }
    assert.equal(solarEventUTC('2026-02-31', 0, 0, 'sunrise'), null);
    assert.equal(solarEventUTC('2026-01-01', 91, 0, 'sunrise'), null);
});

test('polar days without the chosen solar event have no switching time', () => {
    assert.equal(solarEventUTC('2026-06-21', 90, 0, 'sunset'), null);
    assert.equal(solarEventUTC('2026-12-21', 90, 0, 'sunrise'), null);
});

test('local dates and positive/negative offsets preserve absolute time across midnight', () => {
    const t = {event: 'sunset', latitude: 0, longitude: 180, timezone: 'Pacific/Kiritimati', offset_minutes: 0};
    const base = solarForLocalDay('2026-03-20', t);
    assert.ok(base !== null);
    assert.equal(solarForLocalDay('2026-03-20', {...t, offset_minutes: 720}), base + 43200000);
    assert.equal(solarForLocalDay('2026-03-20', {...t, offset_minutes: -720}), base - 43200000);
    assert.equal(base % 60000, 0, 'Solar automation times use minute resolution');
});
