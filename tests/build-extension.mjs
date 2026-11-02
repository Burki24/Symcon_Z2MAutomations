import {readFile, writeFile} from 'node:fs/promises';

const source = new URL('../libs/z2m/src/extension.mjs', import.meta.url);
const schema = new URL('../libs/z2m/automations.schema.json', import.meta.url);
const target = new URL('../libs/z2m/z2m-automations.mjs', import.meta.url);
const marker = "import AUTOMATIONS_SCHEMA from '../automations.schema.json' with {type: 'json'};";
const code = await readFile(source, 'utf8');
if (code.split(marker).length !== 2) throw new Error('Expected exactly one schema import.');
const runtimeMarker = "import {AutomationRuntime, RuntimeError} from './runtime.mjs';";
if (code.split(runtimeMarker).length !== 2) throw new Error('Expected exactly one runtime import.');
const runtime = (await readFile(new URL('../libs/z2m/src/runtime.mjs', import.meta.url), 'utf8'))
    .replace("import {solarForLocalDay} from './solar.mjs';\n", '')
    .replace("import {hasScene} from './scenes.mjs';\n", '')
    .replace("import {createHash, randomUUID} from 'node:crypto';\n", '');
const debugMarker = "import {RuntimeDiagnostics} from './debug.mjs';";
if (code.split(debugMarker).length !== 2) throw new Error('Expected exactly one diagnostics import.');
const diagnostics = (await readFile(new URL('../libs/z2m/src/debug.mjs', import.meta.url), 'utf8'))
    .replace("import {createHash, randomUUID} from 'node:crypto';\n", '')
    .replace("import DEBUG_EVENTS from '../debug-events.json' with {type: 'json'};",
        'const DEBUG_EVENTS = ' + JSON.stringify(JSON.parse(await readFile(new URL('../libs/z2m/debug-events.json', import.meta.url), 'utf8'))) + ';');
const sceneMarker = "import {buildEditorScenes, hasScene} from './scenes.mjs';";
if (code.split(sceneMarker).length !== 2) throw new Error('Expected exactly one scenes import.');
const scenes = await readFile(new URL('../libs/z2m/src/scenes.mjs', import.meta.url), 'utf8');
const meshMarker = "import {MeshTransport, meshSystemValid} from './mesh.mjs';";
if (code.split(meshMarker).length !== 2) throw new Error('Expected exactly one mesh import.');
const mesh = (await readFile(new URL('../libs/z2m/src/mesh.mjs', import.meta.url), 'utf8'))
    .replace("import {createHash, randomUUID} from 'node:crypto';\n", '')
    .replace("import {debugTrace} from './debug.mjs';\n", '')
    .replace("import {RuntimeError} from './runtime.mjs';\n", '');
const solar = await readFile(new URL('../libs/z2m/src/solar.mjs', import.meta.url), 'utf8');
const generated = '// Generated from own extension, runtime, mesh, scenes, diagnostics, event catalog and schema sources. Do not edit.\n'
    + code.replace(marker, 'const AUTOMATIONS_SCHEMA = ' + JSON.stringify(JSON.parse(await readFile(schema, 'utf8'))) + ';')
        .replace(runtimeMarker, solar + '\n' + runtime).replace(meshMarker, mesh).replace(sceneMarker, scenes).replace(debugMarker, diagnostics);
if (process.argv.includes('--check')) {
    if (await readFile(target, 'utf8') !== generated) throw new Error('Extension artifact is stale. Run npm run build:extension.');
    console.log('Extension artifact matches source and schema.');
} else {
    await writeFile(target, generated, 'utf8');
    console.log('Built single-file Z2M extension with embedded schema.');
}
