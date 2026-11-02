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
        const build = () => execFileSync(process.execPath, [join(root, 'tests/build-extension.mjs')], {cwd: root});
        const identity = async () => (await readFile(artifact, 'utf8')).match(/export const EXTENSION_BUILD = '([a-f0-9]{64})';/)?.[1];
        build();
        const first = await readFile(artifact, 'utf8');
        const firstID = await identity();
        assert.match(firstID ?? '', /^[a-f0-9]{64}$/, 'The artifact carries an automatically generated content identifier');
        build();
        assert.equal(await readFile(artifact, 'utf8'), first, 'Repeated builds contain no time or machine-specific inputs');
        let previous = firstID;
        for (const path of ['src/runtime.mjs', 'automations.schema.json', 'debug-events.json']) {
            const target = join(root, 'libs/z2m', path);
            const original = await readFile(target, 'utf8');
            const changed = path.endsWith('.mjs') ? original + '\n// Synthetic build change.\n'
                : path === 'automations.schema.json' ? original.replace('"title":', '"description": "Synthetic build change", "title":')
                    : original.replace('Wiederverwendbare Aktionsfolge', 'Synthetisch geänderte Aktionsfolge');
            assert.notEqual(changed, original);
            await writeFile(target, changed, 'utf8');
            assert.throws(() => execFileSync(process.execPath, [join(root, 'tests/build-extension.mjs'), '--check'], {cwd: root, stdio: 'pipe'}), 'Check mode detects stale generated code');
            build();
            const next = await identity();
            assert.notEqual(next, previous, path + ' changes the identity');
            previous = next;
        }
    } finally { await rm(root, {recursive: true, force: true}); }
});
