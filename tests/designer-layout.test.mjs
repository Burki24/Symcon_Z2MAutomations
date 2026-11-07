import test from 'node:test';
import assert from 'node:assert/strict';
import {layoutStatus} from '../Z2MAutomations/visualization/layout.js';

test('missing observations never appear as confirmed system or device state', () => {
    assert.deepEqual(layoutStatus(null), {transfer: 'Nicht verfügbar', runtime: 'Nicht bestätigt', catalog: 'Nicht verfügbar'});
    assert.equal(layoutStatus({runtime: {executing: true, confirmed: false}}).runtime, 'Nicht bestätigt');
});

test('in-progress work stays distinguishable from a prior successful result', () => {
    const view = layoutStatus({transfer: {pending: true, status: 'stored'}, runtime: {pending: true, confirmed: true, executing: true}, catalogRefresh: {pending: true, loadedAt: 1800000000}});
    assert.deepEqual(view, {transfer: 'Auftrag läuft', runtime: 'Auftrag läuft', catalog: 'Wird geladen'});
});

test('errors and detailed rejection remain visible while the control body is closed', () => {
    const view = layoutStatus({transfer: {details: 'A rule was rejected', canStore: false}, catalogRefresh: {error: 'timeout', loadedAt: 1800000000}, runtime: {confirmed: true, executing: false}});
    assert.equal(view.transfer, 'Hinweis beachten');
    assert.equal(view.catalog, 'Hinweis beachten');
    assert.equal(view.runtime, 'Automatik gestoppt');
});

test('presentation derives confirmed summaries without modifying the supplied state', () => {
    const data = Object.freeze({catalogAvailable: true, transfer: Object.freeze({canStore: true}), runtime: Object.freeze({confirmed: true, executing: true}), catalogRefresh: Object.freeze({loadedAt: 1800000000})});
    assert.deepEqual(layoutStatus(data), {transfer: 'Geprüft', runtime: 'Automatik aktiv', catalog: 'Gerätedaten geladen'});
    assert.deepEqual(data.runtime, {confirmed: true, executing: true});
});

test('a stored catalog timestamp never overrides the missing editor confirmation', () => {
    const stored = {catalogAvailable: false, catalogRefresh: {loadedAt: 1800000000}};
    assert.equal(layoutStatus(stored).catalog, 'Ansicht aktualisieren');
    assert.equal(layoutStatus({catalogAvailable: false, catalogRefresh: {loadedAt: null, storedAt: 1800000000}}).catalog, 'Nicht bestätigt');
    assert.equal(layoutStatus({catalogAvailable: true, catalogRefresh: {loadedAt: null, storedAt: 1800000000}}).catalog, 'Nicht bestätigt');
    assert.equal(layoutStatus({catalogAvailable: false, catalogRefresh: {loadedAt: null, storedAt: null}}).catalog, 'Noch nicht geladen');
});
