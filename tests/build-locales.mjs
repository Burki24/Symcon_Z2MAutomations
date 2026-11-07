import {readFileSync, writeFileSync, readdirSync} from 'node:fs';
import {createHash} from 'node:crypto';
import assert from 'node:assert/strict';
import {validateCatalogs} from './localization-catalog.mjs';

const root = new URL('../Z2MAutomations/visualization/', import.meta.url);
const catalogs = Object.fromEntries(['de', 'en'].map(language => [language, JSON.parse(readFileSync(new URL('locales/' + language + '.json', root), 'utf8'))]));
validateCatalogs(catalogs);
const defined = key => Object.hasOwn(catalogs.en, key) || Object.hasOwn(catalogs.en, key + '.other');
for (const entry of readdirSync(root).filter(name => /\.(?:js|html)$/.test(name) && name !== 'locales.js')) {
    const source = readFileSync(new URL(entry, root), 'utf8');
    for (const match of source.matchAll(/\bt\(["']([^"']+)["']|data-i18n(?:-title|-placeholder|-aria-label)?="([^"]+)"/g)) {
        assert.ok(defined(match[1] ?? match[2]), entry + ': unknown translation key ' + (match[1] ?? match[2]));
    }
}
// Only native presentation sources are indexed. The browser receives keys and
// parameters; names and configuration never undergo a string-replacement pass.
const messages = {};
for (const module of ['Z2MAutomations', 'Z2MConnection']) {
    const native = JSON.parse(readFileSync(new URL('../../' + module + '/locale.json', root), 'utf8'));
    for (const [english, german] of Object.entries(native.translations.de)) {
        if (!english || !german || typeof german !== 'string') continue;
        const key = 'server.' + createHash('sha256').update(english).digest('hex').slice(0, 16);
        assert.ok(defined(key), 'Add native message to both JSON catalogs: ' + english);
        messages[key] = [german, english];
    }
}
for (const [key, text] of Object.entries(catalogs.de)) if (!key.startsWith('server.')) messages[key] = [text, catalogs.en[key]];
const artifacts = {
    'locales.js': '/* Generated from locales/de.json and locales/en.json. Run npm run build:locales. */\nexport const catalogs = ' + JSON.stringify(catalogs) + ';\n',
    'locales/messages.json': JSON.stringify(messages, null, 4) + '\n'
};
for (const [file, artifact] of Object.entries(artifacts)) {
    const target = new URL(file, root);
    if (process.argv.includes('--check')) assert.equal(readFileSync(target, 'utf8'), artifact, file + ' is stale; run npm run build:locales');
    else writeFileSync(target, artifact);
}
console.log('Local catalogs verified: ' + Object.keys(catalogs.en).length + ' matching DE/EN keys; view references and native presentation index checked.');
