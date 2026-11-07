import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {checkInstalled, validatePackage, readPackage, digest} from './designer-package.mjs';

test('pinned designer package is complete and rejects changes or unexpected assets', () => {
    const root = new URL('../', import.meta.url);
    const manifest = checkInstalled(root);
    const assets = new Map(Object.keys(manifest.assets).map(name => [name, readFileSync(new URL('Z2MAutomations/visualization/' + name, root))]));
    const license = readFileSync(new URL('libs/designer-LICENSE', root));
    assets.set('reader.js', Buffer.from('changed'));
    assert.throws(() => validatePackage(manifest, assets, license), /hash mismatch/);
    assets.set('../private.json', Buffer.from('{}'));
    assert.throws(() => validatePackage(manifest, assets, license));
    assert.throws(() => readPackage(Buffer.from('not a package')));
});

test('shared packages cannot replace the native tile or the central interaction asset', () => {
    const root = new URL('../', import.meta.url);
    const installed = checkInstalled(root);
    const original = new Map(Object.keys(installed.assets).map(name => [name, readFileSync(new URL('Z2MAutomations/visualization/' + name, root))]));
    const license = readFileSync(new URL('libs/designer-LICENSE', root));
    for (const reserved of ['tile.js', 'tile.css', 'tile.html', 'interaction.js']) {
        const assets = new Map(original);
        assets.set(reserved, Buffer.from('unexpected native replacement'));
        const manifest = structuredClone(installed);
        manifest.assets[reserved] = digest(assets.get(reserved));
        manifest.content = digest(JSON.stringify(manifest.assets));
        assert.throws(() => validatePackage(manifest, assets, license), /Reserved native asset/);
    }
});
