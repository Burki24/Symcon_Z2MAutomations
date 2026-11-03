import test from 'node:test';
import assert from 'node:assert/strict';
import {creationDevices, creationProperties, creationValue} from '../Z2MAutomations/visualization/creator.js';

const data = {module: {baseTopic: 'synthetic/local'}, devices: [
    {key: 'button', name: 'Shared name', target: {type: 'device', id: 'button'}, properties: [
        {id: 'action', type: 'enum', readable: true, writable: false, values: ['single', 'double'], actionValues: []}]},
    {key: 'lamp', name: 'Shared name', target: {type: 'device', id: 'lamp'}, properties: [
        {id: 'state_l2', type: 'binary', readable: true, writable: true, values: ['ON', 'OFF'], actionValues: ['ON', 'OFF', 'TOGGLE']},
        {id: 'display', type: 'numeric', readable: true, writable: false}]},
    {key: 'peer:lamp', name: 'Remote lamp', target: {type: 'device', id: 'lamp', system: 'synthetic/peer'}, properties: [
        {id: 'state_l1', type: 'binary', readable: false, writable: true, values: ['ON', 'OFF'], actionValues: ['ON', 'OFF', 'TOGGLE']}]}
]};

test('creation searches names, exact identities and systems without merging equally named devices', () => {
    assert.deepEqual(creationDevices(data, 'source', 'shared NAME').map(device => device.key), ['button', 'lamp']);
    assert.deepEqual(creationDevices(data, 'target', 'synthetic/peer').map(device => device.key), ['peer:lamp']);
    assert.deepEqual(creationDevices(data, 'source', 'synthetic/peer'), []);
    assert.deepEqual(creationDevices(data, 'source', 'unavailable'), []);
});

test('sensor events remain sources while actions require writable channels', () => {
    assert.deepEqual(creationProperties(data.devices[0], 'source').map(property => property.id), ['action']);
    assert.deepEqual(creationProperties(data.devices[0], 'target'), []);
    assert.deepEqual(creationProperties(data.devices[1], 'target').map(property => property.id), ['state_l2']);
});

test('values retain their actual type, including false and zero; only action values offer toggle', () => {
    const binary = {type: 'binary', values: [true, false, 0, '0'], actionValues: ['ON', 'OFF', 'TOGGLE']};
    assert.equal(creationValue(binary, 'source', '1'), false);
    assert.equal(creationValue(binary, 'source', '2'), 0);
    assert.equal(creationValue(binary, 'source', '3'), '0');
    assert.equal(creationValue(binary, 'target', '2'), 'TOGGLE');
    assert.equal(creationValue({type: 'numeric'}, 'source', '-5.5'), -5.5);
    assert.deepEqual(creationValue({type: 'color'}, 'target', '#12ab34'), {hex: '#12ab34'});
    assert.throws(() => creationValue(binary, 'source', '7'));
    assert.throws(() => creationValue(binary, 'source', ''));
    assert.throws(() => creationValue({type: 'numeric'}, 'target', 'Infinity'));
});
