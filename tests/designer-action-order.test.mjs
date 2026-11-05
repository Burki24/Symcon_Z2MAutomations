import test from 'node:test';
import assert from 'node:assert/strict';
import {moveAction, actionDropPosition, installActionSorting} from '../Z2MAutomations/visualization/action-order.js';

test('moving a whole action keeps its delay, typed values and duplicate steps intact', () => {
    const steps = [{type: 'set', delay_seconds: 0, values: {state: 'ON', level: 0}},
        {type: 'set', delay_seconds: 5, values: {state: 'OFF', enabled: false}}, {type: 'set', delay_seconds: 9, values: {state: 'ON'}}];
    const original = structuredClone(steps);
    const moved = moveAction(steps, 2, 0);
    assert.deepEqual(moved, [original[2], original[0], original[1]]);
    assert.deepEqual(steps, original);
    assert.equal(moved[0], steps[2]);
    assert.deepEqual(moveAction(moved, 0, 2), steps);
    assert.deepEqual(moveAction([steps[0], steps[0], steps[1]], 2, 0), [steps[1], steps[0], steps[0]]);
});

test('drop positions distinguish before and after while rejecting foreign or invalid moves', () => {
    assert.equal(actionDropPosition(2, 0, false), 0);
    assert.equal(actionDropPosition(0, 2, true), 2);
    assert.equal(actionDropPosition(0, 1, false), 0);
    const steps = [{id: 'a'}, {id: 'b'}];
    for (const [from, to] of [[-1, 0], [0, 2], [0.5, 1], ['0', 1], [null, 1]]) assert.equal(moveAction(steps, from, to), steps);
    assert.equal(moveAction(steps, 1, 1), steps);
});

test('drag controller rejects foreign, busy, stale and cancelled drags without writing', () => {
    const emitter = extra => Object.assign({listeners: new Map(), addEventListener(type, fn) { this.listeners.set(type, fn); }, removeEventListener(type) { this.listeners.delete(type); }}, extra);
    const rows = [0, 1, 2].map(index => emitter({dataset: {actionIndex: String(index)}, classList: {add() {}, remove() {}}, getBoundingClientRect: () => ({top: index * 100, height: 80})}));
    const handles = rows.map(row => emitter({closest: () => row}));
    rows.forEach(row => { row.closest = () => row; });
    const container = emitter({querySelectorAll: selector => selector === '[data-order-handle]' ? handles : rows, contains: row => rows.includes(row), closest: () => null});
    let items = ['one', 'two', 'three']; let permitted = true; const calls = [];
    const sorter = installActionSorting(container, {getItems: () => items, canMove: () => permitted, onMove: (...args) => calls.push(args)});
    const transfer = {types: [], values: {}, setData(type, value) { this.values[type] = value; this.types.push(type); }, getData(type) { return this.values[type] ?? ''; }};
    const event = (target, dataTransfer = transfer) => ({target, dataTransfer, clientY: 10, preventDefault() {}, stopPropagation() {}});
    container.listeners.get('drop')(event(rows[0]));
    assert.deepEqual(calls, []);
    handles[2].listeners.get('dragstart')(event(handles[2]));
    container.listeners.get('drop')(event(rows[0], {...transfer, getData: () => 'foreign'}));
    permitted = false; container.listeners.get('drop')(event(rows[0])); permitted = true;
    assert.deepEqual(calls, []);
    items = items.slice(); container.listeners.get('drop')(event(rows[0]));
    assert.deepEqual(calls, []);
    handles[2].listeners.get('dragstart')(event(handles[2])); handles[2].listeners.get('dragend')();
    container.listeners.get('drop')(event(rows[0])); assert.deepEqual(calls, []);
    handles[2].listeners.get('dragstart')(event(handles[2])); container.listeners.get('drop')(event(rows[0]));
    assert.deepEqual(calls, [[2, 0]]);
    sorter.dispose(); assert.equal(container.listeners.size, 0); assert(handles.every(handle => handle.listeners.size === 0));
    assert.deepEqual(items, ['one', 'two', 'three']);
});
