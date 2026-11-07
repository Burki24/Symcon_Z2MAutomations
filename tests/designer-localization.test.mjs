import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {validateCatalogs} from './localization-catalog.mjs';
import {t, translate, setLanguage, language, localizeResponse, formatNumber, formatDate, MessageError, bindText, bindHTML} from '../Z2MAutomations/visualization/localization.js';
const de = JSON.parse(readFileSync(new URL('../Z2MAutomations/visualization/locales/de.json', import.meta.url), 'utf8'));
const en = JSON.parse(readFileSync(new URL('../Z2MAutomations/visualization/locales/en.json', import.meta.url), 'utf8'));

test('catalog validation rejects missing keys, incompatible placeholders and markup', () => {
    validateCatalogs({de, en});
    assert.throws(() => validateCatalogs({de: {}, en: {message: 'Ready'}}), /keys must match/);
    assert.throws(() => validateCatalogs({de: {message: 'Hallo {{name}}'}, en: {message: 'Hello {{other}}'}}), /placeholders differ/);
    assert.throws(() => validateCatalogs({de: {message: '<b>Hallo</b>'}, en: {message: 'Hello'}}), /markup belongs/);
});

test('English is the fallback and placeholders are substituted once without reinterpreting their values', () => {
    assert.equal(translate({en: {greeting: 'Hello {{name}}'}, de: {}}, 'de', 'greeting', {name: 'Ein {{name}} <script>'}), 'Hello Ein {{name}} <script>');
    assert.equal(translate({en: {}}, 'de', 'missing'), 'Translation unavailable');
    assert.throws(() => setLanguage('fr'), /Unsupported language/);
});

test('language selection formats numbers, dates and plural forms using Intl', () => {
    setLanguage('en', {persist: false, report: false});
    assert.equal(t('duration.seconds', {count: 1}), '1 second');
    assert.equal(t('duration.seconds', {count: 2}), '2 seconds');
    assert.equal(formatNumber(1234.5), '1,234.5');
    const date = new Date('2026-11-07T12:00:00Z');
    const options = {timeZone: 'UTC', year: 'numeric', month: 'long', day: 'numeric'};
    assert.match(formatDate(date, options), /November/);
    setLanguage('de', {persist: false, report: false});
    assert.equal(t('duration.seconds', {count: 1}), '1 Sekunde');
    assert.equal(t('duration.seconds', {count: 2}), '2 Sekunden');
    assert.equal(formatNumber(1234.5), '1.234,5');
    assert.match(formatDate(date, options), /7\. November 2026/);
});

test('only explicitly annotated presentation fields switch; names, typed values and configuration remain untouched', () => {
    const key = Object.keys(de).find(key => de[key] === 'Bitte zuerst ein Z2M-System auswählen.');
    const configuration = {name: 'Ein', state: 'ON', enabled: false, count: 0};
    const reply = localizeResponse({message: de[key], configuration, name: de[key], sceneNames: [de[key]], presentation: {version: 1, messages: [
        {path: ['message'], key, values: {}}, {path: ['name'], key, values: {}},
        {path: ['configuration', 'name'], key, values: {}}, {path: ['sceneNames', 0], key, values: {}},
        {path: ['__proto__', 'message'], key, values: {}}]}});
    setLanguage('en', {persist: false, report: false});
    assert.equal(reply.message, en[key]);
    assert.equal(reply.name, de[key]); assert.deepEqual(reply.configuration, configuration); assert.deepEqual(reply.sceneNames, [de[key]]);
    setLanguage('de', {persist: false, report: false}); assert.equal(reply.message, de[key]);
});

test('existing validation errors and detailed feedback switch without rerunning a request or translating rule names', () => {
    const error = new MessageError('language.caption');
    const reply = localizeResponse({details: 'previous native text', presentation: {version: 1, messages: [
        {path: ['details'], key: 'validation.details', values: {}, localizedValues: {detail: {de: 'Automation „Ein ON“: Bitte prüfen.', en: 'Automation “Ein ON”: Please check.'}}}]}});
    setLanguage('en', {persist: false, report: false}); assert.equal(error.message, 'Language'); assert.match(reply.details, /“Ein ON”: Please check/);
    setLanguage('de', {persist: false, report: false}); assert.equal(error.message, 'Sprache'); assert.match(reply.details, /„Ein ON“: Bitte prüfen/);
});

test('a new content binding supersedes a previous placeholder, preserving current content on language changes', () => {
    const element = {textContent: '', innerHTML: '', isConnected: true};
    bindText(element, () => t('language.caption'));
    bindHTML(element, () => '<span>current view</span>');
    setLanguage(language() === 'de' ? 'en' : 'de', {persist: false, report: false});
    assert.equal(element.innerHTML, '<span>current view</span>');
    setLanguage('de', {persist: false, report: false});
});
