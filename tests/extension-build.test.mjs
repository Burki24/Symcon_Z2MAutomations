import assert from 'node:assert/strict';
import test from 'node:test';
import {mkdtemp, mkdir, cp, readFile, writeFile, rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {execFileSync} from 'node:child_process';

test('extension builds are reproducible and identify changes to bundled code, schema and debug texts', async () => {
    const root = await mkdtemp(join(tmpdir(), 'z2ma-build-'));
    try {
        await mkdir(join(root, 'tests'));
        await cp(new URL('./build-extension.mjs', import.meta.url), join(root, 'tests/build-extension.mjs'));
        await cp(new URL('../libs/z2m', import.meta.url), join(root, 'libs/z2m'), {recursive: true});
        const artifact = join(root, 'libs/z2m/z2m-automations.mjs');
        const metadata = join(root, 'libs/z2m/version.json');
        const build = () => execFileSync(process.execPath, [join(root, 'tests/build-extension.mjs')], {cwd: root, stdio: 'pipe'});
        const identity = async () => (await readFile(artifact, 'utf8')).match(/export const EXTENSION_BUILD = '([a-f0-9]{64})';/)?.[1];
        const version = async () => JSON.parse(await readFile(metadata, 'utf8')).version;
        build();
        let previousVersion = await version();
        const first = await readFile(artifact, 'utf8');
        const firstID = await identity();
        assert.match(firstID ?? '', /^[a-f0-9]{64}$/, 'The artifact carries an automatically generated content identifier');
        build();
        assert.equal(await readFile(artifact, 'utf8'), first, 'Repeated builds contain no time or machine-specific inputs');
        assert.equal(await version(), previousVersion, 'Unchanged builds retain their readable version');
        await writeFile(join(root, 'library.json'), '{"version":"99.999"}\n');
        build();
        assert.equal(await readFile(artifact, 'utf8'), first, 'Library-only releases never change the independent extension version');
        let previous = firstID;
        for (const path of ['src/extension.mjs', 'src/runtime.mjs', 'src/mesh.mjs', 'src/scenes.mjs', 'src/sequences.mjs', 'src/solar.mjs', 'src/run-journal.mjs', 'src/debug.mjs', 'automations.schema.json', 'debug-events.json']) {
            const target = join(root, 'libs/z2m', path);
            const original = await readFile(target, 'utf8');
            const changed = path.endsWith('.mjs') ? original + '\n// Synthetic build change.\n'
                : path === 'automations.schema.json' ? original.replace('"title":', '"description": "Synthetic build change", "title":')
                    : original.replace('Wiederverwendbare Aktionsfolge', 'Synthetisch geänderte Aktionsfolge');
            assert.notEqual(changed, original);
            await writeFile(target, changed, 'utf8');
            const beforeCheck = await readFile(metadata, 'utf8');
            assert.throws(() => execFileSync(process.execPath, [join(root, 'tests/build-extension.mjs'), '--check'], {cwd: root, stdio: 'pipe'}), 'Check mode detects stale generated code');
            assert.equal(await readFile(metadata, 'utf8'), beforeCheck, 'Check mode never increments or rewrites version metadata');
            build();
            const parts = previousVersion.split('.').map(Number);
            assert.equal(await version(), `${parts[0]}.${parts[1]}.${parts[2] + 1}`, path + ' automatically increments the patch version');
            previousVersion = await version();
            assert.ok((await readFile(artifact, 'utf8')).includes(`export const EXTENSION_VERSION = '${previousVersion}';`), 'The loaded artifact carries the new version');
            const next = await identity();
            assert.notEqual(next, previous, path + ' changes the identity');
            previous = next;
        }
        const previousMetadata = await readFile(metadata, 'utf8');
        for (const invalid of ['01.2.3', '0.2.0\n', '0.2.0\r', '0.2.0-extra']) {
            await writeFile(metadata, JSON.stringify({...JSON.parse(previousMetadata), version: invalid}));
            assert.throws(build, 'Invalid metadata cannot silently reset the release sequence');
        }
        await writeFile(metadata, previousMetadata);
        const beforeRestore = await readFile(artifact, 'utf8');
        await rm(artifact);
        build();
        assert.equal(await readFile(artifact, 'utf8'), beforeRestore, 'Recreating an artifact retains the tracked version');
        const exhausted = {...JSON.parse(previousMetadata), version: '0.2.999999999', source: 'a'.repeat(64)};
        await writeFile(metadata, JSON.stringify(exhausted));
        assert.throws(build, 'Patch overflow requires a deliberate release decision instead of a reset');
        assert.equal(await readFile(artifact, 'utf8'), beforeRestore, 'Failed version planning never changes the upload artifact');
        await rm(metadata);
        assert.throws(build, 'Missing metadata cannot silently reset the release sequence');
    } finally { await rm(root, {recursive: true, force: true}); }
});
