import test from 'node:test';
import assert from 'node:assert/strict';
import {creationRule, ruleChanges, sequenceChanges, targetDevice, valueIndex} from '../Z2MAutomations/visualization/editor.js';

test('complete creation submits the same typed sections as editing without server-owned fields', () => {
    const working = {id: 'must-not-submit', enabled: true, mesh_peers: ['must-not-submit'], name: 'Complete', retrigger: 'extend',
        triggers: [{type: 'time', at: '07:00:00', timezone: 'UTC'}, {type: 'sun', event: 'sunset', offset_minutes: -15, latitude: 0, longitude: 0, timezone: 'UTC'}],
        conditions: [{type: 'device_value', value: false, start_only: true}],
        actions: [{type: 'set', values: {level: 0, color: {hex: '#123456'}}, delay_seconds: 5}]};
    const fields = creationRule(working);
    assert.deepEqual(Object.keys(fields), ['name', 'retrigger', 'triggers', 'conditions', 'actions']);
    assert.equal(fields.conditions[0].value, false);
    assert.deepEqual(fields.actions, working.actions);
    fields.actions[0].values.level = 70;
    assert.equal(working.actions[0].values.level, 0);
    assert.deepEqual(ruleChanges({name: '', retrigger: 'ignore', triggers: [], conditions: [], actions: []}, {...fields, enabled: undefined}), fields);
});

test('sequence editing submits only changed name and actions without mutating callers or original typed values', () => {
    const original = {id: 'shared', name: 'Shared', actions: [{type: 'set', values: {state: 'OFF', level: 0}, delay_seconds: 5}]};
    const working = structuredClone(original);
    assert.deepEqual(sequenceChanges(original, working), {});
    working.name = 'Renamed'; working.id = 'ignored'; working.enabled = true;
    assert.deepEqual(sequenceChanges(original, working), {name: 'Renamed'});
    working.actions[0].delay_seconds = 0;
    const changes = sequenceChanges(original, working);
    assert.deepEqual(changes.actions[0].values, {state: 'OFF', level: 0});
    changes.actions[0].values.level = 50;
    assert.equal(working.actions[0].values.level, 0);
    assert.equal(original.actions[0].delay_seconds, 5);
});

test('editing starts with a lossless clone and submits only explicit changed fields', () => {
    const original = {id: 'synthetic', name: 'Original', enabled: false, triggers: [{type: 'time', at: '00:00:00', timezone: 'UTC'}],
        conditions: [{type: 'device_value', value: 0, start_only: false}], actions: [{type: 'set', values: {color: {hex: '#123456'}, level: 0}, delay_seconds: 0}], extra: {retained: true}};
    const working = structuredClone(original);
    assert.deepEqual(ruleChanges(original, working), {});
    working.name = 'Renamed'; working.enabled = true;
    assert.deepEqual(ruleChanges(original, working), {name: 'Renamed', enabled: true});
    working.actions[0].delay_seconds = 5;
    const changes = ruleChanges(original, working);
    assert.equal(original.actions[0].delay_seconds, 0);
    assert.deepEqual(changes.actions[0].values, {color: {hex: '#123456'}, level: 0});
    changes.actions[0].values.level = 40;
    assert.equal(working.actions[0].values.level, 0);
    assert.ok(!Object.hasOwn(changes, 'id') && !Object.hasOwn(changes, 'extra') && !Object.hasOwn(changes, 'retrigger'));
});

test('prefill resolves device and group identities within their exact system', () => {
    const local = {target: {type: 'group', id: 7}};
    const peer = {target: {type: 'group', id: 7, system: 'synthetic/peer'}};
    const device = {target: {type: 'device', id: '0x0000000000000001'}};
    const data = {devices: [local, peer, device]};
    assert.equal(targetDevice(data, {type: 'group', id: 7}), local);
    assert.equal(targetDevice(data, {type: 'group', id: 7, system: 'synthetic/peer'}), peer);
    assert.equal(targetDevice(data, {type: 'device', id: '0x0000000000000001'}), device);
    assert.equal(targetDevice(data, {type: 'group', id: 7, system: 'synthetic/unknown'}), undefined);
});

test('prefill distinguishes boolean false, zero, string zero, scenes and unavailable values', () => {
    const property = {values: [false, 0, '0', 7], actionValues: ['OFF', 'TOGGLE', 7]};
    assert.equal(valueIndex(property, 'source', false), 0);
    assert.equal(valueIndex(property, 'source', 0), 1);
    assert.equal(valueIndex(property, 'source', '0'), 2);
    assert.equal(valueIndex(property, 'target', 7), 2);
    assert.equal(valueIndex(property, 'source', true), -1);
});
