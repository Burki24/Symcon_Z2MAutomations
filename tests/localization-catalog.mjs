import assert from 'node:assert/strict';

export function validateCatalogs(catalogs) {
    const placeholders = text => [...text.matchAll(/\{\{([A-Za-z][A-Za-z0-9_]*)\}\}/g)].map(match => match[1]).sort();
    assert.deepEqual(Object.keys(catalogs.de).sort(), Object.keys(catalogs.en).sort(), 'German and English keys must match');
    for (const [key, text] of Object.entries(catalogs.en)) {
        assert.match(key, /^[a-z][a-zA-Z0-9_.]*$/, 'Use stable message keys');
        assert.equal(typeof text, 'string');
        assert.equal(typeof catalogs.de[key], 'string');
        assert.ok(text.trim() && catalogs.de[key].trim(), key + ': empty translation');
        assert.deepEqual(placeholders(text), placeholders(catalogs.de[key]), key + ': placeholders differ');
        assert.doesNotMatch(text + catalogs.de[key], /<\/?[a-z][^>]*>/i, key + ': markup belongs in the view, not the catalogs');
    }
}
