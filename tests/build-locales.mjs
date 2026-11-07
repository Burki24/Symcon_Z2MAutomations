import {checkInstalled} from './designer-package.mjs';
import {readFileSync} from 'node:fs';
import {createHash} from 'node:crypto';
import assert from 'node:assert/strict';
import {validateCatalogs} from './localization-catalog.mjs';

// The existing entry point now verifies the pinned shared package, read-only.
const manifest = checkInstalled();
const base = new URL('../Z2MAutomations/visualization/', import.meta.url);
const catalogs = Object.fromEntries(['de', 'en'].map(language => [language, JSON.parse(readFileSync(new URL('locales/' + language + '.json', base), 'utf8'))]));
validateCatalogs(catalogs);
const messages = JSON.parse(readFileSync(new URL('locales/messages.json', base), 'utf8'));
for (const module of ['Z2MAutomations', 'Z2MConnection']) {
    const native = JSON.parse(readFileSync(new URL('../' + module + '/locale.json', import.meta.url), 'utf8'));
    for (const [english, german] of Object.entries(native.translations.de)) {
        if (!english || !german || typeof german !== 'string') continue;
        const key = 'server.' + createHash('sha256').update(english).digest('hex').slice(0, 16);
        assert.ok(Object.hasOwn(catalogs.en, key), 'Update the canonical shared catalog: ' + key);
        assert.deepEqual(messages[key], [german, english], 'Update the shared native presentation template: ' + key);
    }
}
console.log('Gemeinsames Designer-Paket unverändert: ' + manifest.version + ' / ' + manifest.content.slice(0, 12));
