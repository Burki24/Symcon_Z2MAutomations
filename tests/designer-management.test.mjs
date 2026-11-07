import test from 'node:test';
import assert from 'node:assert/strict';
import {matchesRuleState, sequenceUsers} from '../Z2MAutomations/visualization/management.js';

test('state filter distinguishes active draft flags and ignores unrelated catalog and sequence entries', () => {
    const rows = [{id: 'on', enabled: true}, {id: 'off', enabled: false}, {id: 'sequence'}];
    assert.deepEqual(rows.filter(row => matchesRuleState(row, 'enabled')).map(row => row.id), ['on', 'sequence']);
    assert.deepEqual(rows.filter(row => matchesRuleState(row, 'disabled')).map(row => row.id), ['off', 'sequence']);
    assert.equal(rows.filter(row => matchesRuleState(row, 'all')).length, 3);
});

test('sequence deletion displays each dependent rule once and includes disabled rules', () => {
    const config = {automations: [
        {id: 'one', enabled: false, actions: [{type: 'sequence', sequence_id: 'shared'}, {type: 'sequence', sequence_id: 'shared'}]},
        {id: 'two', enabled: true, actions: [{type: 'sequence', sequence_id: 'other'}]}
    ]};
    assert.deepEqual(sequenceUsers(config, 'shared').map(rule => rule.id), ['one']);
    assert.deepEqual(sequenceUsers(config, 'unused'), []);
    assert.equal(config.automations[0].actions.length, 2);
});
