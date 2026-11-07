import assert from 'node:assert/strict';
import {createHash} from 'node:crypto';
import {gunzipSync} from 'node:zlib';
import {readFileSync} from 'node:fs';

export const digest = value => createHash('sha256').update(value).digest('hex');
const assetName = name => /^(?:[a-z][a-z-]*\.(?:js|css|html)|locales\/(?:de|en|messages)\.json)$/.test(name);
const reservedAssets = new Set(['tile.js', 'tile.css', 'tile.html', 'interaction.js']);
export function validatePackage(manifest, assets, license) {
    assert.equal(manifest.format, 1);
    assert.equal(manifest.name, '@z2ma/designer');
    assert.equal(manifest.source, 'https://github.com/Burki24/Z2M-Automations');
    assert.equal(manifest.license, 'PolyForm-Noncommercial-1.0.0');
    assert.match(manifest.version, /^\d+\.\d+\.\d+$/);
    assert.equal(manifest.origin?.repository, 'https://github.com/Burki24/Symcon_Z2MAutomations');
    assert.match(manifest.origin?.commit ?? '', /^[a-f0-9]{40}$/);
    assert.match(license.toString('utf8'), /PolyForm Noncommercial License 1\.0\.0/);
    assert.match(license.toString('utf8'), /Copyright 2026 Burkhard Kneiseler/);
    assert.ok(Object.keys(manifest.assets).length >= 20 && Object.keys(manifest.assets).length <= 60);
    assert.deepEqual([...assets.keys()].sort(), Object.keys(manifest.assets).sort());
    for (const [name, hash] of Object.entries(manifest.assets)) {
        assert.ok(!reservedAssets.has(name), 'Reserved native asset: ' + name);
        assert.ok(assetName(name), 'Unsupported designer asset: ' + name);
        assert.equal(digest(assets.get(name)), hash, name + ': hash mismatch');
    }
    assert.equal(digest(JSON.stringify(manifest.assets)), manifest.content);
}

// Restricted reader for our npm-generated flat USTAR package: no extraction,
// symlinks, PAX paths or unverified files are executed.
export function readPackage(archive) {
    assert.ok(archive.length <= 2097152, 'Designer archive too large');
    const tar = gunzipSync(archive, {maxOutputLength: 4194304});
    const entries = new Map();
    let offset = 0;
    while (offset + 512 <= tar.length) {
        const header = tar.subarray(offset, offset + 512);
        if (header.every(byte => byte === 0)) break;
        const text = (start, end) => header.subarray(start, end).toString('utf8').replace(/\0.*$/s, '');
        const name = text(0, 100);
        assert.equal(text(345, 500), '', 'Prefixed archive paths are unsupported');
        assert.ok(header[156] === 0 || header[156] === 48, 'Only regular files are accepted');
        assert.ok(/^package\/[a-zA-Z0-9/_.-]+$/.test(name) && !name.includes('..') && !entries.has(name));
        assert.match(text(124, 136).trim(), /^[0-7]+$/);
        const size = parseInt(text(124, 136), 8);
        const expected = parseInt(text(148, 156), 8);
        const sum = header.reduce((total, byte, index) => total + (index >= 148 && index < 156 ? 32 : byte), 0);
        assert.equal(sum, expected, 'Invalid archive header checksum');
        offset += 512;
        assert.ok(size <= 1048576 && offset + size <= tar.length);
        entries.set(name, tar.subarray(offset, offset + size));
        offset += Math.ceil(size / 512) * 512;
        assert.ok(entries.size <= 64);
    }
    const manifest = JSON.parse(entries.get('package/manifest.json')?.toString('utf8') ?? 'null');
    const assets = new Map([...entries].filter(([name]) => name.startsWith('package/assets/')).map(([name, bytes]) => [name.slice(15), bytes]));
    const license = entries.get('package/LICENSE');
    assert.ok(license);
    validatePackage(manifest, assets, license);
    assert.deepEqual([...entries.keys()].sort(), ['package/LICENSE', 'package/manifest.json', 'package/package.json', ...[...assets.keys()].map(name => 'package/assets/' + name)].sort());
    return {manifest, assets, license};
}

export function checkInstalled(root = new URL('../', import.meta.url)) {
    const manifest = JSON.parse(readFileSync(new URL('libs/designer-package.json', root), 'utf8'));
    const assets = new Map(Object.keys(manifest.assets).map(name => [name, readFileSync(new URL('Z2MAutomations/visualization/' + name, root))]));
    validatePackage(manifest, assets, readFileSync(new URL('libs/designer-LICENSE', root)));
    return manifest;
}
