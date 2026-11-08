import {readFile, writeFile} from 'node:fs/promises';
import {createHash} from 'node:crypto';

const source = new URL('../libs/z2m/src/extension.mjs', import.meta.url);
const schema = new URL('../libs/z2m/automations.schema.json', import.meta.url);
const target = new URL('../libs/z2m/z2m-automations.mjs', import.meta.url);
const marker = "import AUTOMATIONS_SCHEMA from '../automations.schema.json' with {type: 'json'};";
const code = await readFile(source, 'utf8');
const buildMarker = "export const EXTENSION_BUILD = 'development';";
const versionMarker = "export const EXTENSION_VERSION = 'development';";
if (code.split(buildMarker).length !== 2) throw new Error('Expected exactly one build marker.');
if (code.split(versionMarker).length !== 2) throw new Error('Expected exactly one version marker.');
if (code.split(marker).length !== 2) throw new Error('Expected exactly one schema import.');
const sequenceMarker = "import {expandActionPlan, SequenceError} from './sequences.mjs';";
if (code.split(sequenceMarker).length !== 2) throw new Error('Expected exactly one sequence import.');
const sequences = await readFile(new URL('../libs/z2m/src/sequences.mjs', import.meta.url), 'utf8');
const journalMarker = "import {RunJournal} from './run-journal.mjs';";
if (code.split(journalMarker).length !== 2) throw new Error('Expected exactly one run journal import.');
const journal = (await readFile(new URL('../libs/z2m/src/run-journal.mjs', import.meta.url), 'utf8'))
    .replace("import {createHash} from 'node:crypto';\n", '')
    .replace("import {join} from 'node:path';\n", '');
const runtimeMarker = "import {AutomationRuntime, RuntimeError} from './runtime.mjs';";
if (code.split(runtimeMarker).length !== 2) throw new Error('Expected exactly one runtime import.');
const runtime = (await readFile(new URL('../libs/z2m/src/runtime.mjs', import.meta.url), 'utf8'))
    .replace("import {solarForLocalDay} from './solar.mjs';\n", '')
    .replace("import {hasScene} from './scenes.mjs';\n", '')
    .replace("import {expandActionPlan} from './sequences.mjs';\n", '')
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
const template = '// Generated from own extension, runtime, mesh, scenes, sequences, diagnostics, event catalog and schema sources. Do not edit.\n'
    + code.replace(marker, 'const AUTOMATIONS_SCHEMA = ' + JSON.stringify(JSON.parse(await readFile(schema, 'utf8'))) + ';')
        .replace(journalMarker, journal).replace(sequenceMarker, sequences).replace(runtimeMarker, solar + '\n' + runtime).replace(meshMarker, mesh).replace(sceneMarker, scenes).replace(debugMarker, diagnostics);
const metadataFile = new URL('../libs/z2m/version.json', import.meta.url);
const metadata = JSON.parse(await readFile(metadataFile, 'utf8'));
const validVersion = /^(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})\.(0|[1-9][0-9]{0,8})(?![\s\S])/;
if (Object.keys(metadata).sort().join(',') !== 'source,version' || typeof metadata.version !== 'string' || !validVersion.test(metadata.version)
    || !(metadata.source === null && metadata.version === '0.2.0' || typeof metadata.source === 'string' && /^[a-f0-9]{64}$/.test(metadata.source))) {
    throw new Error('Invalid extension version metadata. Restore the tracked version.json; never reset the counter.');
}
const sourceHash = createHash('sha256').update(template, 'utf8').digest('hex');
let version = metadata.version;
if (metadata.source !== null && metadata.source !== sourceHash) {
    const parts = version.split('.').map(Number);
    if (parts[2] >= 999999999) throw new Error('Extension patch version exhausted. A reviewed minor or major release is required.');
    version = `${parts[0]}.${parts[1]}.${parts[2] + 1}`;
}
const nextMetadata = JSON.stringify({version, source: sourceHash}, null, 4) + '\n';
const versioned = template.replace(versionMarker, `export const EXTENSION_VERSION = '${version}';`);
const build = createHash('sha256').update(versioned, 'utf8').digest('hex');
const generated = versioned.replace(buildMarker, `export const EXTENSION_BUILD = '${build}';`);
if (process.argv.includes('--check')) {
    if (await readFile(metadataFile, 'utf8') !== nextMetadata) throw new Error('Extension version is stale. Run npm run build:extension.');
    if (await readFile(target, 'utf8') !== generated) throw new Error('Extension artifact is stale. Run npm run build:extension.');
    console.log(`Extension ${version} matches source, schema and version metadata (Build ${build.slice(0, 12)}).`);
} else {
    await writeFile(target, generated, 'utf8');
    await writeFile(metadataFile, nextMetadata, 'utf8');
    console.log(`Built Z2M extension ${version} (Build ${build.slice(0, 12)}). Unchanged sources retain their version.`);
}
