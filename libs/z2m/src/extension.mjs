export const EXTENSION_BUILD = 'development';
import {createHash, randomUUID} from 'node:crypto';
import * as fs from 'node:fs/promises';
import {join} from 'node:path';
import {fileURLToPath} from 'node:url';
import {TextDecoder} from 'node:util';
import Ajv from 'ajv';
import {parseEvents, constructFromEvents, JSON_SCHEMA, mapTag, EVENT_ID} from 'js-yaml';
import AUTOMATIONS_SCHEMA from '../automations.schema.json' with {type: 'json'};
import {AutomationRuntime, RuntimeError} from './runtime.mjs';
import {MeshTransport, meshSystemValid} from './mesh.mjs';
import {buildEditorScenes, hasScene} from './scenes.mjs';
import {RuntimeDiagnostics} from './debug.mjs';
import {expandActionPlan, SequenceError} from './sequences.mjs';

export const LIMITS = Object.freeze({yamlBytes: 131072, requestBytes: 1048576, queuedRequests: 16});
const PREFIX = 'symcon/automations';
const VALID_TRANSACTION = /^[A-Za-z0-9_-]{1,64}$/;
const ERRORS = Object.freeze({
    invalid_request: 'Invalid request envelope.',
    invalid_yaml: 'YAML must contain one bounded JSON-compatible document without anchors, aliases or tags.',
    invalid_schema: 'Automation configuration does not conform to schema version 1.',
    invalid_semantics: 'Duplicate automation ID, invalid timezone, empty time window or unapproved remote action.',
    session_mismatch: 'Request does not belong to the current extension session.',
    revision_conflict: 'Expected revision does not match the stored configuration.',
    revision_reused: 'New revision must be exactly the stored revision plus one.',
    storage_unavailable: 'Storage requires attention before configurations can be saved.',
    storage_error: 'Configuration could not be persisted.',
    busy: 'Too many requests are pending.',
    not_ready: 'Extension is not accepting requests.',
    runtime_unavailable: 'Required Z2M runtime interfaces are unavailable.',
    unknown_entity: 'A configured device or group is unavailable.',
    scene_unavailable: 'The selected scene is no longer available on the target.',
    checksum_mismatch: 'Activation checksum does not match the stored configuration.',
    activation_expired: 'Request a fresh activation token before activating the stored revision.',
    mesh_unavailable: 'The bridge transport is unavailable.',
    mesh_not_allowed: 'The second Z2M system is not permitted.',
    rule_missing: 'The selected automation is not stored.',
    rule_busy: 'This automation is already running.',
    run_conflict: 'The selected run has ended or changed.'
});

class ConfigurationError extends Error {
    constructor(code, details = null) {
        super(ERRORS[code]);
        this.code = code;
        this.details = details;
    }
}

const fail = (code, details = null) => { throw new ConfigurationError(code, details); };
const isObject = value => value !== null && typeof value === 'object' && !Array.isArray(value);
const isRevision = value => Number.isSafeInteger(value) && value >= 0;
const hash = text => createHash('sha256').update(text, 'utf8').digest('hex');
const validateSchema = new Ajv({strict: true, allowUnionTypes: true, allErrors: false}).compile(AUTOMATIONS_SCHEMA);
// Diagnostics select the declared branch, avoiding errors about other trigger/action types.
// Acceptance is still decided exclusively by the unchanged public schema above.
const diagnosticSchema = structuredClone(AUTOMATIONS_SCHEMA);
function selectDiagnosticBranches(node) {
    if (!node || typeof node !== 'object') return;
    if (Array.isArray(node.oneOf) && node.oneOf.every(branch =>
        (branch.$ref ? AUTOMATIONS_SCHEMA.definitions[branch.$ref.split('/').at(-1)] : branch)?.properties?.type?.const)) {
        node.type = 'object';
        node.discriminator = {propertyName: 'type'};
    }
    for (const child of Object.values(node)) selectDiagnosticBranches(child);
}
selectDiagnosticBranches(diagnosticSchema);
const diagnoseSchema = new Ajv({strict: true, allowUnionTypes: true, allErrors: true, discriminator: true}).compile(diagnosticSchema);
const DETAIL_FIELDS = new Set(['schema_version', 'revision', 'automations', 'sequences', 'sequence_id', 'mesh_peers', 'id', 'name', 'enabled', 'triggers', 'conditions', 'actions',
    'type', 'device', 'property', 'operator', 'value', 'for_seconds', 'on_change', 'at', 'timezone', 'event', 'latitude', 'longitude', 'offset_minutes', 'after', 'before', 'weekdays', 'target', 'system', 'values', 'delay_seconds', 'scene_id', 'endpoint']);
const DETAIL_REASONS = new Set(['required', 'type', 'enum', 'const', 'pattern', 'minimum', 'maximum', 'minItems', 'maxItems', 'minLength', 'maxLength',
    'minProperties', 'maxProperties', 'additionalProperties', 'uniqueItems', 'not', 'discriminator']);
function schemaDetails(document) {
    diagnoseSchema(document);
    const issues = [];
    const seen = new Set();
    for (const error of diagnoseSchema.errors ?? []) {
        if (!DETAIL_REASONS.has(error.keyword)) continue;
        const parts = error.instancePath.split('/').slice(1);
        if (error.keyword === 'required') parts.push(error.params.missingProperty);
        const safe = [];
        for (const part of parts) {
            if (!DETAIL_FIELDS.has(part) && !/^(?:0|[1-9][0-9]{0,2})$/.test(part)) break;
            safe.push(part);
            if (part === 'values' || part === 'value') break; // Never include user-authored object keys or values.
        }
        const issue = {path: safe.length ? '/' + safe.join('/') : '', reason: error.keyword};
        if (error.keyword === 'type' && ['number', 'integer', 'boolean', 'string', 'array', 'object'].includes(error.params.type)) issue.reason = error.params.type;
        if (typeof error.params.limit === 'number') issue.limit = error.params.limit;
        const key = JSON.stringify(issue);
        if (!seen.has(key)) { seen.add(key); issues.push(issue); }
    }
    return {issues: issues.slice(0, 20), truncated: issues.length > 20};
}
const validateMeshAction = new Ajv({strict: true, allowUnionTypes: true}).addSchema(AUTOMATIONS_SCHEMA).getSchema(AUTOMATIONS_SCHEMA.$id + '#/definitions/leafAction');
const yamlSchema = JSON_SCHEMA.withTags({
    ...mapTag,
    create: () => Object.create(null),
    addPair: (container, key, value) => {
        if (typeof key !== 'string' || ['__proto__', 'constructor', 'prototype', '<<'].includes(key)) return 'Unsupported mapping key';
        container[key] = value;
        return '';
    },
    has: (container, key) => Object.hasOwn(container, key),
    get: (container, key) => container[key]
});

export function validateYaml(yaml) {
    if (typeof yaml !== 'string' || yaml.length === 0 || Buffer.byteLength(yaml, 'utf8') > LIMITS.yamlBytes
        || Buffer.from(yaml, 'utf8').toString('utf8') !== yaml) fail('invalid_yaml');
    let document;
    try {
        const events = parseEvents(yaml, {maxDepth: 16});
        if (events.length > 20000 || events.filter(event => event.type === EVENT_ID.DOCUMENT).length !== 1) fail('invalid_yaml');
        for (const event of events) {
            if (event.type === EVENT_ID.ALIAS || event.anchorStart >= 0 || event.tagStart >= 0
                || (event.directives && event.directives.length !== 0)) fail('invalid_yaml');
        }
        [document] = constructFromEvents(events, {source: yaml, schema: yamlSchema, json: false, maxAliases: 0, maxTotalMergeKeys: 0});
    } catch (error) {
        const issue = {path: '', reason: 'yaml_syntax'};
        if (Number.isInteger(error.mark?.line) && error.mark.line >= 0 && error.mark.line < 1000000) {
            issue.line = error.mark.line + 1;
            if (Number.isInteger(error.mark.column) && error.mark.column >= 0 && error.mark.column < 1000000) issue.column = error.mark.column + 1;
        }
        fail('invalid_yaml', {issues: [issue], truncated: false});
    }
    if (!validateSchema(document)) fail('invalid_schema', schemaDetails(document));
    const ids = new Set();
    const issues = [];
    const sequenceIDs = new Set();
    for (const [index, sequence] of (document.sequences ?? []).entries()) {
        if (sequenceIDs.has(sequence.id)) issues.push({path: '/sequences/' + index + '/id', reason: 'sequence_duplicate'});
        sequenceIDs.add(sequence.id);
    }
    for (const [index, automation] of document.automations.entries()) {
        const path = '/automations/' + index;
        if (ids.has(automation.id)) issues.push({path: path + '/id', reason: 'duplicate_id'});
        ids.add(automation.id);
        let plan = [];
        try { plan = expandActionPlan(document, automation.actions); }
        catch (error) {
            if (!(error instanceof SequenceError)) throw error;
            issues.push({path: path + '/actions/' + error.step + (error.reason === 'sequence_delay' ? '/delay_seconds' : '/sequence_id'), reason: error.reason});
        }
        for (const entry of plan) {
            const {action, source_index: step} = entry;
            if (automation.enabled && action.target.system !== undefined && !document.mesh_peers?.includes(action.target.system)) {
                const location = entry.sequence ? `/sequences/${entry.sequence - 1}/actions/${entry.sequence_step - 1}` : path + '/actions/' + step;
                if (!issues.some(issue => issue.path === location + '/target/system' && issue.reason === 'remote_permission')) {
                    issues.push({path: location + '/target/system', reason: 'remote_permission'});
                }
            }
        }
        for (const section of ['triggers', 'conditions']) for (const [step, clause] of automation[section].entries()) {
            const location = path + '/' + section + '/' + step;
            if (clause.timezone !== undefined) {
                try {
                    new Intl.DateTimeFormat('en', {timeZone: clause.timezone}).format(0);
                } catch {
                    issues.push({path: location + '/timezone', reason: 'timezone'});
                }
            }
            if (clause.type === 'time_window' && clause.after === clause.before) issues.push({path: location + '/before', reason: 'time_window'});
        }
    }
    if (issues.length) fail('invalid_semantics', {issues: issues.slice(0, 20), truncated: issues.length > 20});
    return {yaml, document, checksum: hash(yaml)};
}

// Only discrete exposed values enter the click editor; raw definitions stay in Z2M.
export function buildEditorCatalog(zigbee) {
    const candidates = [];
    let optionCount = 0, devicesSeen = 0;
    const scalar = value => value === null || typeof value === 'boolean' || (typeof value === 'number' && Number.isFinite(value))
        || (typeof value === 'string' && [...value].length <= 256);
    for (const device of zigbee?.devicesIterator?.() ?? []) {
        if (++devicesSeen > 256) break;
        if (!device?.isDevice?.() || device.zh?.type === 'Coordinator' || !/^0x[0-9a-f]{16}$/.test(device.ieeeAddr)) continue;
        const triggers = [], actions = [], numeric = [], properties = [];
        let visited = 0;
        const walk = (features, depth = 0, phase = 0, endpoint = '') => {
            if (!Array.isArray(features) || depth > 6) return;
            for (const feature of features) {
                if (++visited > 128 || optionCount >= 1024) break;
                if (!isObject(feature)) continue;
                const channel = typeof feature.endpoint === 'string' ? feature.endpoint : endpoint;
                walk(feature.features, depth + 1, phase, channel);
                if (typeof feature.property !== 'string' || !/^[A-Za-z][A-Za-z0-9_]{0,63}$/.test(feature.property)
                    || ['constructor', 'prototype', '__proto__'].includes(feature.property)) continue;
                const state = feature.type === 'binary' && (feature.property === 'state' || feature.name === 'state'
                    || (typeof feature.endpoint === 'string' && feature.property === 'state_' + feature.endpoint));
                const priority = state ? 0 : feature.type === 'enum' && /^(?:state|action)(?:_[A-Za-z0-9_]+)?$/.test(feature.property) ? 1 : 2;
                if (priority !== phase) continue;
                const admitted = [];
                const before = optionCount;
                if (feature.type === 'numeric' && (feature.access & 3) !== 0 && numeric.length < 64 && optionCount < 1024) {
                    if ((feature.access & 1) !== 0) {
                        numeric.push({property: feature.property, unit: typeof feature.unit === 'string' ? [...feature.unit].slice(0, 24).join('') : ''});
                    }
                    optionCount++;
                }
                const values = feature.type === 'binary' ? [feature.value_on, feature.value_off]
                    : feature.type === 'enum' && Array.isArray(feature.values) ? feature.values.slice(0, 32) : [];
                for (const value of values) {
                    if (!scalar(value) || optionCount >= 1024) continue;
                    const entry = {property: feature.property, value};
                    const prior = optionCount;
                    if ((feature.access & 1) !== 0 && triggers.length < 64 && optionCount < 1024) { triggers.push(entry); optionCount++; }
                    if ((feature.access & 2) !== 0 && actions.length < 64 && optionCount < 1024) { actions.push(entry); optionCount++; }
                    if (optionCount > prior && !admitted.includes(value)) admitted.push(value);
                }
                if (optionCount > before && properties.length < 64 && ['binary', 'enum', 'numeric'].includes(feature.type)
                    && !properties.some(item => item.property === feature.property)) {
                    const property = {property: feature.property, type: feature.type, access: feature.access & 3,
                        label: typeof feature.label === 'string' && !/[\x00-\x1f\x7f]/.test(feature.label) ? [...feature.label].slice(0, 80).join('') : feature.property,
                        endpoint: typeof channel === 'string' && /^[A-Za-z0-9_-]{1,32}$/.test(channel) ? channel : '', values: admitted};
                    if (feature.type === 'numeric') {
                        for (const [from, to] of [['value_min', 'minimum'], ['value_max', 'maximum'], ['value_step', 'step']]) {
                            if (typeof feature[from] === 'number' && Number.isFinite(feature[from]) && (to !== 'step' || feature[from] > 0)) property[to] = feature[from];
                        }
                    }
                    properties.push(property);
                }
            }
        };
        let features;
        try { features = device.exposes(); } catch { continue; }
        candidates.push({entry: {id: device.ieeeAddr,
            name: typeof device.name === 'string' ? [...device.name].slice(0, 120).join('') : device.ieeeAddr, triggers, actions, numeric, properties},
        scan: phase => { visited = 0; walk(features, 0, phase); }, failed: false});
    }
    // Reserve switch channels first, then button events and cover commands, before special options.
    for (const phase of [0, 1, 2]) {
        for (const candidate of candidates) {
            if (candidate.failed) continue;
            try { candidate.scan(phase); } catch { candidate.failed = true; }
        }
    }
    return candidates.filter(({entry, failed}) => !failed && (entry.triggers.length || entry.actions.length || entry.numeric.length || entry.properties.length)).map(({entry}) => entry);
}

// Only stable group identity and display name cross the editor boundary.
export function buildEditorGroups(zigbee) {
    const groups = [], seen = new Set();
    let visited = 0;
    for (const group of zigbee?.groupsIterator?.() ?? []) {
        if (++visited > 256) break;
        if (!Number.isInteger(group.ID) || group.ID < 1 || group.ID > 65535 || seen.has(group.ID)
            || typeof group.name !== 'string' || !group.name || /[\x00-\x1f\x7f+#]/.test(group.name)) continue;
        seen.add(group.ID);
        groups.push({id: group.ID, name: [...group.name].slice(0, 120).join('')});
    }
    return groups;
}

class ConfigurationStore {
    constructor(directory, io = fs) {
        this.directory = directory;
        this.io = io;
        this.current = null;
        this.state = 'empty';
    }

    metadata() {
        return {stored_revision: this.current?.document.revision ?? 0, checksum: this.current?.checksum ?? null,
            automation_count: this.current?.document.automations.length ?? 0, storage: this.state, executing: false};
    }

    async read(name) {
        const path = join(this.directory, name);
        let handle;
        try {
            const stat = await this.io.lstat(path);
            if (!stat.isFile() || stat.isSymbolicLink() || stat.size > LIMITS.yamlBytes) fail('storage_unavailable');
            handle = await this.io.open(path, 'r');
            const bytes = await handle.readFile();
            if (bytes.length > LIMITS.yamlBytes) fail('storage_unavailable');
            return validateYaml(new TextDecoder('utf-8', {fatal: true}).decode(bytes));
        } finally {
            await handle?.close();
        }
    }

    async load() {
        this.current = null;
        this.state = 'empty';
        try {
            await this.io.mkdir(this.directory, {recursive: true, mode: 0o700});
            const directory = await this.io.lstat(this.directory);
            if (!directory.isDirectory() || directory.isSymbolicLink()) fail('storage_unavailable');
        } catch {
            this.state = 'error';
            return;
        }
        try {
            this.current = await this.read('automations.yaml');
            this.state = 'ok';
        } catch (error) {
            const missing = error.code === 'ENOENT';
            try {
                this.current = await this.read('automations.previous.yaml');
                // Never silently roll revision history back after a damaged/missing primary file.
                this.state = 'recovered_read_only';
            } catch (backupError) {
                this.state = missing && backupError.code === 'ENOENT' ? 'empty' : 'error';
            }
        }
    }

    async atomicWrite(name, yaml) {
        const temporary = join(this.directory, '.write-' + randomUUID() + '.tmp');
        let handle;
        let committed = false;
        try {
            handle = await this.io.open(temporary, 'wx', 0o600);
            await handle.writeFile(yaml, 'utf8');
            await handle.sync();
            await handle.close();
            handle = null;
            await this.io.rename(temporary, join(this.directory, name));
            committed = true;
        } finally {
            await handle?.close().catch(() => {});
            if (!committed) await this.io.unlink(temporary).catch(() => {});
        }
    }

    async verifyDisk() {
        try {
            // Verify that the primary has not been edited outside this extension since loading.
            if (this.current) {
                const disk = await this.read('automations.yaml');
                if (disk.checksum !== this.current.checksum) fail('storage_unavailable');
            } else {
                try {
                    await this.io.lstat(join(this.directory, 'automations.yaml'));
                    fail('storage_unavailable');
                } catch (error) {
                    if (error.code !== 'ENOENT') throw error;
                }
            }
        } catch {
            this.state = 'error';
            fail('storage_unavailable');
        }
    }

    async save(candidate, expectedRevision) {
        if (!['ok', 'empty'].includes(this.state)) fail('storage_unavailable');
        await this.verifyDisk();
        const revision = this.current?.document.revision ?? 0;
        if (candidate.document.revision === revision && candidate.checksum === this.current?.checksum
            && [revision, revision - 1].includes(expectedRevision)) {
            return {...this.metadata(), unchanged: true};
        }
        if (expectedRevision !== revision) fail('revision_conflict');
        if (candidate.document.revision !== revision + 1) fail('revision_reused');
        try {
            if (this.current) await this.atomicWrite('automations.previous.yaml', this.current.yaml);
            await this.atomicWrite('automations.yaml', candidate.yaml);
        } catch {
            fail('storage_error');
        }
        this.current = candidate;
        this.state = 'ok';
        return {...this.metadata(), unchanged: false};
    }
}

export default class Z2MAutomationsExtension {
    constructor(_zigbee, mqtt, _state, _publishEntityState, eventBus, _enableDisableExtension, _restartCallback, _addExtension, settings, logger, options = {}) {
        this.mqtt = mqtt;
        this.eventBus = eventBus;
        this.settings = settings;
        this.logger = logger;
        this.zigbee = _zigbee;
        this.clock = options.clock;
        this.diagnostics = new RuntimeDiagnostics(mqtt, options.clock);
        this.runtime = new AutomationRuntime({zigbee: _zigbee, logger, clock: options.clock,
            remoteDispatch: (action, context) => this.mesh.dispatch(action, context),
            onDeactivate: () => this.mesh?.cancel(),
            onCancelRun: run => this.mesh?.cancelRun(run),
            readState: typeof _state?.get === 'function' ? entity => _state.get(entity) : undefined,
            dispatch: (topic, message) => this.eventBus.emitMQTTMessage({topic: this.baseTopic + '/' + topic, message}),
            available: typeof eventBus.onPublishEntityState === 'function' && typeof eventBus.emitMQTTMessage === 'function'});
        this.runtime.diagnostics = this.diagnostics;
        this.runStatusDirty = false;
        this.runStatusTask = null;
        this.runtime.onExecutionChange = () => this.queueExecutionStatus();
        // A real deployed file is in data/external_extensions, including Z2M temporary import copies.
        const directory = options.directory ?? fileURLToPath(new URL('../z2m-automations/', import.meta.url));
        this.store = new ConfigurationStore(directory, options.io ?? fs);
        this.queue = Promise.resolve();
        this.pending = 0;
        this.accepting = false;
        this.session = '';
        this.activationToken = null;
        this.executionIntent = null;
        this.resumeStatus = 'unconfigured';
        this.resumeError = '';
        this.monotonicNow = options.clock?.now ?? (() => performance.now());
    }

    status() {
        return {protocol_version: 1, schema_version: 1, extension_version: '0.1.0', extension_build: EXTENSION_BUILD, debug_version: 1,
            resume_version: 1, resume_enabled: this.executionIntent?.enabled ?? false,
            resume_status: this.resumeStatus, resume_error: this.resumeError,
            mesh_version: 1, mesh_ready: Boolean(this.mesh?.subscribed && this.runtime.active?.document.mesh_peers?.length),
            mesh_peer_count: this.runtime.active?.document.mesh_peers?.length ?? 0,
            execution_version: 1, sequence_version: 1, execution: this.executionStatus(),
            session: this.session, state: this.accepting ? 'ready' : 'stopped', ...this.store.metadata(), ...this.runtime.metadata()};
    }

    queueExecutionStatus() {
        this.runStatusDirty = true;
        if (this.runStatusTask) return;
        // Keep at most one publish in flight and coalesce intervening changes.
        // Reporting never blocks device actions and never queues old snapshots.
        this.runStatusTask = Promise.resolve().then(async () => {
            while (this.runStatusDirty && this.accepting) {
                this.runStatusDirty = false;
                await this.publish('runs', this.executionStatus());
            }
        }).catch(() => {}).finally(() => {
            this.runStatusTask = null;
            if (this.runStatusDirty && this.accepting) this.queueExecutionStatus();
        });
    }

    executionStatus() {
        const snapshot = this.runtime.executionSnapshot(), current = this.store.current;
        return {protocol_version: 1, execution_version: 1, session: this.session,
            revision: current?.document.revision ?? 0, checksum: current?.checksum ?? null,
            sequence: snapshot.sequence, runs: snapshot.runs.filter(run => run.checksum === current?.checksum && run.revision === current?.document.revision)};
    }

    async publish(topic, data, retain = false) {
        try {
            await this.mqtt.publish(PREFIX + '/' + topic, JSON.stringify(data), {clientOptions: {retain, qos: 0}, skipLog: true});
        } catch {
            this.runtime.log('FEHLER', 'MQTT-Antwort konnte nicht veröffentlicht werden.');
        }
    }

    async start() {
        this.runtime.log('START', 'Erweiterung starten.');
        try {
            if (this.accepting) return;
            if (this.diagnostics.closed) {
                this.diagnostics = new RuntimeDiagnostics(this.mqtt, this.clock);
                this.runtime.diagnostics = this.diagnostics;
            }
            this.baseTopic = this.settings.get().mqtt.base_topic;
            this.runtime.system = this.baseTopic;
            this.mesh = new MeshTransport({mqtt: this.mqtt, system: this.baseTopic, runtime: this.runtime, validateAction: validateMeshAction, clock: this.clock});
            this.session = randomUUID();
            this.runtime.results.clear();
            this.runtime.executionSequence = 0;
            this.diagnostics.session = this.session;
            this.activationToken = null;
            await this.store.load();
            this.accepting = true;
            // Serialize user commands behind startup recovery, especially explicit stop.
            this.queue = this.restoreExecution();
            this.eventBus.onMQTTMessage(this, message => this.receive(message));
            this.eventBus.onPublishEntityState?.(this, event => {
                try { this.runtime.receive(event); }
                catch { this.runtime.log('FEHLER', 'Geräteereignis verworfen; interne Verarbeitung fehlgeschlagen.'); }
            });
            await this.queue;
            await this.publish('status', this.status(), true);
        } finally { this.runtime.log('ENDE', 'Startaufruf beendet.', {Bereit: this.accepting}); }
    }

    async stop() {
        this.runtime.log('START', 'Erweiterung stoppen.');
        try {
            this.accepting = false;
            this.activationToken = null;
            this.eventBus.removeListeners(this);
            this.runtime.deactivate();
            await this.mesh?.stop();
            await this.queue;
            await this.runtime.stop();
            this.diagnostics.stop();
            await this.publish('status', this.status(), true);
        } finally { this.runtime.log('ENDE', 'Stoppaufruf beendet.', {Bereit: this.accepting}); }
    }

    validateScenes(candidate) {
        const issues = [];
        for (const [rule, automation] of [...candidate.document.automations, ...(candidate.document.sequences ?? [])].entries()) {
            for (const [step, action] of automation.actions.entries()) {
                if (action.type !== 'scene' || action.target.system !== undefined) continue;
                let available = false;
                try { available = hasScene(this.runtime.target(action.target), action.target.type, action); }
                catch { /* A missing entity is reported at the same actionable scene position. */ }
                if (!available) {
                    const location = rule < candidate.document.automations.length ? `/automations/${rule}` : `/sequences/${rule - candidate.document.automations.length}`;
                    issues.push({path: `${location}/actions/${step}/scene_id`, reason: 'scene_unavailable'});
                }
            }
        }
        if (issues.length) fail('invalid_semantics', {issues: issues.slice(0, 20), truncated: issues.length > 20});
    }

    async persistExecution(active, enabled = this.executionIntent?.enabled ?? false) {
        const intent = {version: 1, system: this.baseTopic, enabled, active,
            revision: this.store.current?.document.revision ?? 0, checksum: this.store.current?.checksum ?? null};
        try {
            await this.store.atomicWrite('execution-state.json', JSON.stringify(intent));
        } catch {
            this.resumeStatus = 'blocked';
            this.resumeError = 'storage_error';
            fail('storage_error');
        }
        this.executionIntent = intent;
        this.resumeStatus = active ? (enabled ? 'armed' : 'disabled') : 'stopped';
        this.resumeError = '';
    }

    async prepareMesh() {
        if (!this.accepting) fail('not_ready');
        const peers = this.store.current.document.mesh_peers ?? [];
        if (peers.length) {
            if (peers.includes(this.baseTopic) || !meshSystemValid(this.baseTopic)) fail('mesh_not_allowed');
            await this.mesh.start();
        }
        if (!this.accepting) {
            await this.mesh.stop();
            fail('not_ready');
        }
    }

    async restoreExecution() {
        this.executionIntent = null;
        this.resumeStatus = 'unconfigured';
        this.resumeError = '';
        try {
            const path = join(this.store.directory, 'execution-state.json');
            let intent;
            try {
                const stat = await this.store.io.lstat(path);
                if (!stat.isFile() || stat.isSymbolicLink() || stat.size > 4096) fail('storage_unavailable');
                const bytes = await this.store.io.readFile(path);
                if (bytes.length > 4096) fail('storage_unavailable');
                intent = JSON.parse(new TextDecoder('utf-8', {fatal: true}).decode(bytes));
            } catch (error) {
                if (error.code === 'ENOENT') {
                    this.runtime.log('AKTION', 'Kein gespeicherter Betriebszustand; Automationen bleiben gestoppt.');
                    return;
                }
                throw error;
            }
            if (!isObject(intent) || Object.keys(intent).length !== 6 || intent.version !== 1 || intent.system !== this.baseTopic
                || typeof intent.enabled !== 'boolean' || typeof intent.active !== 'boolean' || !isRevision(intent.revision)
                || (intent.revision === 0 ? intent.checksum !== null : !/^[a-f0-9]{64}$/.test(intent.checksum))) fail('storage_unavailable');
            this.executionIntent = intent;
            this.resumeStatus = !intent.active ? 'stopped' : !intent.enabled ? 'disabled' : 'armed';
            if (!intent.active || !intent.enabled) {
                this.runtime.log('AKTION', 'Kein automatischer Wiederanlauf: bewusst gestoppt oder Wiederanlauf ausgeschaltet.');
                return;
            }
            if (this.store.state !== 'ok' || !this.store.current || intent.revision !== this.store.current.document.revision
                || intent.checksum !== this.store.current.checksum) fail('storage_unavailable');
            await this.store.verifyDisk();
            await this.prepareMesh();
            this.runtime.activate(this.store.current);
            this.resumeStatus = 'resumed';
            this.runtime.log('AKTION', 'Automationen nach Neustart automatisch aktiviert; nur neue Auslöser werden verarbeitet.',
                {Revision: intent.revision, Regeln: this.runtime.rules.length});
        } catch (error) {
            this.runtime.deactivate();
            this.resumeStatus = 'blocked';
            this.resumeError = error instanceof ConfigurationError || error instanceof RuntimeError ? error.code : 'storage_unavailable';
            this.runtime.log('FEHLER', 'Automatischer Wiederanlauf abgebrochen; Automationen bleiben gestoppt.', {Grund: this.resumeError});
        }
    }

    async receive(event) {
        if (this.accepting && this.mesh && await this.mesh.receive(event)) return;
        const prefix = this.baseTopic + '/' + PREFIX + '/request/';
        if (typeof event?.topic !== 'string' || !event.topic.startsWith(prefix)) return;
        const operation = event.topic.slice(prefix.length);
        if (operation === 'debug') {
            // Debug leases never enter the automation/storage queue or alter its status.
            let request;
            try {
                if (event.retain === true || typeof event.message !== 'string' || Buffer.byteLength(event.message) > 1024) throw new Error();
                request = JSON.parse(event.message);
                if (!isObject(request) || Object.keys(request).sort().join(',') !== 'client,protocol_version,seconds,session,transaction'
                    || request.protocol_version !== 1 || (typeof request.transaction !== 'string' || !VALID_TRANSACTION.test(request.transaction))
                    || request.session !== this.session || !this.accepting) throw new Error();
                const data = this.diagnostics.configure(request.client, request.seconds);
                if (!data) throw new Error();
                await this.publish('response/debug', {protocol_version: 1, transaction: request.transaction, status: 'ok', data});
            } catch {
                if (typeof request?.transaction === 'string' && VALID_TRANSACTION.test(request.transaction)) {
                    await this.publish('response/debug', {protocol_version: 1, transaction: request.transaction, status: 'error', error: {code: 'invalid_request'}});
                }
            }
            return;
        }
        if (!['validate', 'store', 'get', 'status', 'activate', 'deactivate', 'editor', 'run_once', 'stop_rule'].includes(operation)) return;
        this.runtime.log('START', 'Erweiterungsauftrag empfangen.', {Auftrag: operation});
        try { await this.handleRequest(operation, event); }
        finally { this.runtime.log('ENDE', 'Erweiterungsauftrag beendet; Ergebnis siehe Antwort.', {Auftrag: operation}); }
    }

    async handleRequest(operation, event) {
        let request;
        try {
            if (typeof event.message !== 'string' || Buffer.byteLength(event.message, 'utf8') > LIMITS.requestBytes) fail('invalid_request');
            request = JSON.parse(event.message);
            if (!isObject(request) || request.protocol_version !== 1 || typeof request.transaction !== 'string'
                || !VALID_TRANSACTION.test(request.transaction)) fail('invalid_request');
            const keys = ['protocol_version', 'transaction', ...(['validate', 'store'].includes(operation) ? ['yaml'] : []),
                ...(operation === 'store' ? ['session', 'expected_revision'] : []),
                ...(operation === 'activate' ? ['session', 'expected_revision', 'checksum', 'activation_token'] : []),
                ...(operation === 'deactivate' ? ['session'] : [])];
            if (['run_once', 'stop_rule'].includes(operation)) keys.push('session', 'rule_id', 'expected_revision', 'checksum');
            if (operation === 'run_once') keys.push('activation_token');
            if (operation === 'stop_rule') keys.push('run');
            if (operation === 'activate' && Object.hasOwn(request, 'resume_after_restart')) {
                if (typeof request.resume_after_restart !== 'boolean') fail('invalid_request');
                keys.push('resume_after_restart');
            }
            if (Object.keys(request).length !== keys.length || keys.some(key => !Object.hasOwn(request, key))) fail('invalid_request');
        } catch {
            this.runtime.log('FEHLER', 'Ungültigen Erweiterungsauftrag abgewiesen.', {Auftrag: operation, Grund: 'invalid_request'});
            const transaction = typeof request?.transaction === 'string' && VALID_TRANSACTION.test(request.transaction) ? request.transaction : undefined;
            await this.publish('response/' + operation, {protocol_version: 1, transaction, status: 'error', error: {code: 'invalid_request', message: ERRORS.invalid_request}});
            return;
        }
        if (!this.accepting || this.pending >= LIMITS.queuedRequests) {
            const code = this.accepting ? 'busy' : 'not_ready';
            await this.respond(operation, request.transaction, null, code);
            return;
        }
        this.pending++;
        const task = this.queue.then(async () => {
            try {
                if (!this.accepting) fail('not_ready');
                let data;
                if (operation === 'status') {
                    this.activationToken = {value: randomUUID(), expires: this.monotonicNow() + 30000};
                    // Only a correlated status response carries this one-use challenge, never retained status.
                    data = {...this.status(), activation_token: this.activationToken.value};
                }
                if (operation === 'get') data = {...this.store.metadata(), executing: this.runtime.metadata().executing, yaml: this.store.current?.yaml ?? null};
                if (['run_once', 'stop_rule'].includes(operation)) {
                    if (request.session !== this.session || event.retain === true) fail('session_mismatch');
                    if (!isRevision(request.expected_revision) || typeof request.checksum !== 'string'
                        || !/^[a-f0-9]{64}$/.test(request.checksum) || typeof request.rule_id !== 'string'
                        || !/^[a-z][a-z0-9_-]{0,63}$/.test(request.rule_id)) fail('invalid_request');
                    if (this.store.state !== 'ok' || !this.store.current) fail('storage_unavailable');
                    if (request.expected_revision !== this.store.current.document.revision) fail('revision_conflict');
                    if (request.checksum !== this.store.current.checksum) fail('checksum_mismatch');
                    if (operation === 'run_once') {
                        const token = this.activationToken; this.activationToken = null;
                        if (!token || request.activation_token !== token.value || token.expires <= this.monotonicNow()) fail('activation_expired');
                        const generation = this.runtime.generation;
                        await this.store.verifyDisk();
                        if (!this.accepting || generation !== this.runtime.generation) fail('not_ready');
                        const selected = this.store.current.document.automations.find(rule => rule.id === request.rule_id);
                        if (selected && expandActionPlan(this.store.current.document, selected.actions).some(step => step.action.target.system !== undefined)) await this.prepareMesh();
                        if (!this.accepting || generation !== this.runtime.generation) fail('not_ready');
                        this.runtime.startOnce(this.store.current, request.rule_id);
                    } else {
                        if (typeof request.run !== 'string' || !/^[a-f0-9-]{36}$/.test(request.run)) fail('invalid_request');
                        this.runtime.stopRule(request.rule_id, request.run);
                    }
                    data = this.executionStatus();
                }
                if (operation === 'editor') {
                    const devices = buildEditorCatalog(this.zigbee), groups = buildEditorGroups(this.zigbee);
                    const scenes = buildEditorScenes(this.zigbee, devices, groups);
                    data = {...this.status(), editor_version: 1, devices, groups, scenes, yaml: this.store.current?.yaml ?? null,
                        configuration: this.store.current?.document ?? {schema_version: 1, revision: 0, automations: []}};
                    this.runtime.log('AKTION', 'Vorhandene Szenen für den Klick-Editor gelesen; keine Geräte geschaltet.', {Szenen: scenes.length});
                }
                if (operation === 'validate') {
                    const candidate = validateYaml(request.yaml);
                    this.validateScenes(candidate);
                    data = {valid: true, revision: candidate.document.revision, checksum: candidate.checksum,
                        automation_count: candidate.document.automations.length, executing: false};
                }
                if (operation === 'store') {
                    if (request.session !== this.session || event.retain === true) fail('session_mismatch');
                    if (!isRevision(request.expected_revision)) fail('invalid_request');
                    const candidate = validateYaml(request.yaml);
                    this.validateScenes(candidate);
                    if (candidate.checksum !== this.store.current?.checksum && ['ok', 'empty'].includes(this.store.state)
                        && request.expected_revision === (this.store.current?.document.revision ?? 0)
                        && candidate.document.revision === request.expected_revision + 1) {
                        this.runtime.deactivate();
                        await this.persistExecution(false);
                    }
                    data = await this.store.save(candidate, request.expected_revision);
                    this.activationToken = null;
                    if (!data.unchanged) this.runtime.deactivate();
                    data.executing = this.runtime.metadata().executing;
                }
                if (['activate', 'deactivate'].includes(operation)) {
                    if (request.session !== this.session || event.retain === true) fail('session_mismatch');
                    if (operation === 'activate') {
                        if (!isRevision(request.expected_revision) || typeof request.checksum !== 'string' || !/^[a-f0-9]{64}$/.test(request.checksum)) fail('invalid_request');
                        const token = this.activationToken;
                        this.activationToken = null;
                        if (!token || request.activation_token !== token.value || token.expires <= this.monotonicNow()) fail('activation_expired');
                        if (this.store.state !== 'ok' || !this.store.current) fail('storage_unavailable');
                        await this.store.verifyDisk();
                        if (!this.accepting) fail('not_ready');
                        if (request.expected_revision !== this.store.current.document.revision) fail('revision_conflict');
                        if (request.checksum !== this.store.current.checksum) fail('checksum_mismatch');
                        await this.prepareMesh();
                        // Disarm the previous durable intent before attempting a new activation.
                        await this.persistExecution(false, request.resume_after_restart ?? true);
                        if (!this.accepting) fail('not_ready');
                        this.runtime.activate(this.store.current);
                        try { await this.persistExecution(true, request.resume_after_restart ?? true); }
                        catch (error) { this.runtime.deactivate(); throw error; }
                        if (!this.accepting) this.runtime.deactivate();
                    } else {
                        this.activationToken = null;
                        this.runtime.deactivate();
                        await this.persistExecution(false);
                    }
                    data = this.status();
                }
                await this.respond(operation, request.transaction, data);
                if (['store', 'activate', 'deactivate'].includes(operation)) await this.publish('status', this.status(), true);
            } catch (error) {
                if (this.store.state === 'error') this.runtime.deactivate();
                const code = error instanceof ConfigurationError || error instanceof RuntimeError ? error.code : 'storage_error';
                this.runtime.log('FEHLER', 'Erweiterungsauftrag abgewiesen.', {Auftrag: operation, Grund: code});
                const details = error instanceof ConfigurationError ? error.details : null;
                for (const issue of details?.issues ?? []) this.runtime.log('FEHLER', 'Entwurf an dieser Stelle korrigieren.', {Position: issue.path, Grund: issue.reason});
                await this.respond(operation, request.transaction, null, code, details);
            } finally {
                this.pending--;
            }
        });
        // Keep the next request usable even when a transport or test boundary rejects.
        this.queue = task.catch(() => {});
        await task;
    }

    async respond(operation, transaction, data, code = null, details = null) {
        await this.publish('response/' + operation, code === null
            ? {protocol_version: 1, transaction, status: 'ok', data}
            : {protocol_version: 1, transaction, status: 'error', error: {code, message: ERRORS[code], ...(details ? {details} : {})}});
    }
}
