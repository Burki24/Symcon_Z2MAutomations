import {solarForLocalDay} from './solar.mjs';
import {hasScene} from './scenes.mjs';
import {expandActionPlan} from './sequences.mjs';
import {createHash, randomUUID} from 'node:crypto';

// Library-local execution policy. No foreign automation implementation is bundled.
export const RUNTIME_LIMITS = Object.freeze({concurrentRules: 16, commandsPerSecond: 20, ruleCooldownMs: 1000, publishTimeoutMs: 5000});
const object = value => value !== null && typeof value === 'object' && !Array.isArray(value);
const scalar = value => value === null || ['string', 'boolean'].includes(typeof value) || (typeof value === 'number' && Number.isFinite(value));
const nativeClock = {now: () => performance.now(), wallNow: () => Date.now(), setTimeout, clearTimeout, setInterval, clearInterval};

export class RuntimeError extends Error {
    constructor(code) { super(code); this.code = code; }
}

function compare(actual, clause) {
    if (!scalar(actual)) return false;
    if (clause.operator === 'eq') return actual === clause.value;
    if (clause.operator === 'ne') return actual !== clause.value;
    if (typeof actual !== 'number' || typeof clause.value !== 'number') return false;
    return {gt: () => actual > clause.value, gte: () => actual >= clause.value,
        lt: () => actual < clause.value, lte: () => actual <= clause.value}[clause.operator]();
}

export class AutomationRuntime {
    constructor({zigbee, dispatch, logger, clock = nativeClock, available = true, readState, remoteDispatch, onDeactivate, onCancelRun}) {
        this.zigbee = zigbee;
        this.dispatch = dispatch;
        this.readState = readState;
        this.remoteDispatch = remoteDispatch;
        this.onDeactivate = onDeactivate;
        this.onCancelRun = onCancelRun;
        this.logger = logger;
        this.clock = clock;
        this.available = available;
        this.generation = 0;
        this.active = null;
        this.rules = [];
        this.values = new Map();
        this.changes = new Map();
        this.references = new Map();
        this.holds = new Map();
        this.waits = new Map();
        this.running = new Map();
        this.tasks = new Set();
        this.cooldowns = new Map();
        this.formats = new Map();
        this.days = new Map();
        this.solar = new Map();
        this.sentAt = [];
        this.timer = null;
        this.lastError = '';
        this.executions = new Map();
        this.results = new Map();
        this.executionSequence = 0;
    }

    executionSnapshot() { return {sequence: this.executionSequence, runs: [...this.results.values()]}; }

    executionState(rule, context, state, error = '', step = 0) {
        const execution = this.executions.get(context.run);
        if (step === 0 && this.results.get(rule.id)?.run === context.run) step = this.results.get(rule.id).step;
        this.results.delete(rule.id);
        this.results.set(rule.id, {rule_id: rule.id, run: context.run, revision: context.revision,
            checksum: execution?.candidate.checksum ?? this.active?.checksum,
            state, error, step});
        while (this.results.size > 100) this.results.delete(this.results.keys().next().value);
        this.executionSequence++;
        try { this.onExecutionChange?.(); } catch { /* Reporting cannot alter execution. */ }
    }

    canContinue(generation, context = {}) {
        const execution = this.executions.get(context.run);
        return generation === this.generation && !execution?.cancelled && Boolean(this.active || execution?.once);
    }

    permittedPeer(system, context = {}) {
        const execution = this.executions.get(context.run);
        return this.canContinue(execution?.generation ?? this.generation, context)
            && (execution?.candidate ?? this.active)?.document.mesh_peers?.includes(system);
    }

    startOnce(candidate, ruleID) {
        if (!this.available || typeof this.dispatch !== 'function') throw new RuntimeError('runtime_unavailable');
        const rule = candidate.document.automations.find(item => item.id === ruleID);
        if (!rule) throw new RuntimeError('rule_missing');
        for (const clause of rule.conditions) if (clause.device && !this.device(clause.device)) throw new RuntimeError('unknown_entity');
        for (const {action} of expandActionPlan(candidate.document, rule.actions)) {
            if (action.target.system !== undefined) {
                if (!candidate.document.mesh_peers?.includes(action.target.system) || typeof this.remoteDispatch !== 'function') throw new RuntimeError('mesh_not_allowed');
            } else {
                const entity = this.target(action.target);
                if (action.type === 'scene' && !hasScene(entity, action.target.type, action)) throw new RuntimeError('scene_unavailable');
                if (action.type === 'toggle' && typeof this.readState !== 'function') throw new RuntimeError('runtime_unavailable');
            }
        }
        const context = {run: randomUUID(), rule: candidate.document.automations.indexOf(rule) + 1,
            revision: candidate.document.revision, origin: this.system};
        this.log('AKTION', 'Einmaliger Start angefordert; gespeicherte Auslöser bleiben unverändert.', {}, context);
        return this.launch(rule, context, candidate);
    }

    stopRule(ruleID, run) {
        const execution = this.executions.get(run);
        if (!execution || execution.rule.id !== ruleID || execution.cancelled) throw new RuntimeError('run_conflict');
        execution.cancelled = true;
        for (const [timer, wait] of this.waits) if (wait.run === run) {
            this.clock.clearTimeout(timer); this.waits.delete(timer); wait.resolve(false);
        }
        this.onCancelRun?.(run);
        this.executionState(execution.rule, execution.context, 'stopped');
        this.log('AKTION', 'Ausgewählten Regellauf gestoppt; bereits übergebene Befehle bleiben wirksam.', {}, execution.context);
        return this.results.get(ruleID);
    }

    metadata() {
        return {runtime_version: 1, executing: this.active !== null, active_revision: this.active?.document.revision ?? null,
            active_checksum: this.active?.checksum ?? null, enabled_count: this.rules.length, runtime_error: this.lastError};
    }

    log(event, message, details = {}, context = {}) {
        try {
            const safeContext = {...context};
            if (typeof safeContext.origin === 'string') safeContext.origin = createHash('sha256').update(safeContext.origin).digest('hex');
            this.logger.debug?.('Z2MAutomations | ' + event + ' | ' + JSON.stringify({Meldung: message, Bezug: {...details, ...safeContext}}));
        } catch { /* A broken diagnostic sink cannot alter execution. */ }
        try { this.diagnostics?.emit(message, details, context); } catch { /* Independent from local logging. */ }
    }

    trace(rule, extra = {}) {
        return {run: randomUUID(), rule: (this.active?.document.automations.indexOf(rule) ?? -1) + 1,
            revision: this.active?.document.revision, origin: this.system, ...extra};
    }

    device(id) {
        const entity = this.zigbee?.resolveEntity?.(id);
        return entity?.isDevice?.() && entity.ieeeAddr === id && entity.zh?.type !== 'Coordinator' ? entity : null;
    }

    target(target) {
        const entity = target.type === 'device' ? this.device(target.id)
            : [...(this.zigbee?.groupsIterator?.() ?? [])].find(group => group.ID === target.id);
        if (!entity || typeof entity.name !== 'string' || entity.name === '' || entity.name.length > 1024
            || /[\u0000-\u001f\u007f+#]/.test(entity.name) || /^bridge(?:\/|$)/.test(entity.name)) throw new RuntimeError('unknown_entity');
        return entity;
    }

    activate(candidate) {
        if (!this.available || !this.zigbee?.resolveEntity || typeof this.dispatch !== 'function') throw new RuntimeError('runtime_unavailable');
        const rules = candidate.document.automations.filter(rule => rule.enabled);
        const references = new Map();
        const changes = new Map();
        for (const rule of rules) {
            for (const clause of [...rule.triggers, ...rule.conditions]) {
                if (!clause.device) continue;
                if (!this.device(clause.device)) throw new RuntimeError('unknown_entity');
                if (!references.has(clause.device)) references.set(clause.device, new Set());
                references.get(clause.device).add(clause.property);
                if (clause.on_change) {
                    if (typeof this.readState !== 'function') throw new RuntimeError('runtime_unavailable');
                    const cached = this.readState(this.device(clause.device));
                    if (!changes.has(clause.device)) changes.set(clause.device, Object.create(null));
                    if (object(cached) && Object.hasOwn(cached, clause.property) && scalar(cached[clause.property])) {
                        changes.get(clause.device)[clause.property] = cached[clause.property];
                    }
                }
            }
            for (const {action} of expandActionPlan(candidate.document, rule.actions)) {
                if (action.target.system !== undefined) {
                    if (!candidate.document.mesh_peers?.includes(action.target.system) || typeof this.remoteDispatch !== 'function') throw new RuntimeError('mesh_not_allowed');
                    continue;
                }
                const entity = this.target(action.target);
                if (action.type === 'scene' && !hasScene(entity, action.target.type, action)) throw new RuntimeError('scene_unavailable');
                if (action.type === 'toggle' && typeof this.readState !== 'function') throw new RuntimeError('runtime_unavailable');
            }
        }
        if (this.active?.checksum === candidate.checksum) return;
        this.deactivate();
        this.active = candidate;
        this.rules = rules;
        this.references = references;
        this.changes = changes;
        this.lastError = '';
        if (rules.some(rule => rule.triggers.some(trigger => ['time', 'sun'].includes(trigger.type)))) {
            this.timer = this.clock.setInterval(() => this.tick(), 1000);
            this.timer?.unref?.();
        }
        this.log('AKTION', 'Gespeicherte Revision für neue Auslöser aktiviert.', {Revision: candidate.document.revision, Regeln: rules.length});
    }

    deactivate() {
        for (const [run, execution] of this.executions) if (!execution.cancelled) this.stopRule(execution.rule.id, run);
        this.generation++;
        this.onDeactivate?.();
        this.active = null;
        this.rules = [];
        if (this.timer !== null) this.clock.clearInterval(this.timer);
        this.timer = null;
        for (const [timer, wait] of this.waits) { this.clock.clearTimeout(timer); wait.resolve(false); }
        this.waits.clear();
        for (const hold of this.holds.values()) {
            if (hold.timer !== null) this.clock.clearTimeout(hold.timer);
            this.log('AKTION', 'Mindestdauer abgebrochen.', {}, hold.context);
        }
        this.holds.clear();
        this.values.clear();
        this.changes.clear();
        this.references.clear(); this.cooldowns.clear(); this.days.clear(); this.formats.clear(); this.solar.clear();
        this.running.clear();
        this.log('AKTION', 'Ausführung gestoppt; ausstehende Verzögerungen verworfen.');
    }

    async stop() { this.deactivate(); await Promise.allSettled([...this.tasks]); }

    local(timezone) {
        if (!this.formats.has(timezone)) this.formats.set(timezone, new Intl.DateTimeFormat('en-GB', {
            timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hourCycle: 'h23'
        }));
        const p = Object.fromEntries(this.formats.get(timezone).formatToParts(this.clock.wallNow()).map(part => [part.type, part.value]));
        return {day: `${p.year}-${p.month}-${p.day}`, time: `${p.hour}:${p.minute}:${p.second}`};
    }

    conditions(rule, context = {}) {
        return rule.conditions.every((clause, index) => {
            let passed;
            if (clause.type === 'device_value') passed = Boolean(this.device(clause.device)
                && compare((this.executions.get(context.run)?.values ?? this.values).get(clause.device)?.[clause.property], clause));
            else if (clause.type === 'weekday') {
                const local = this.local(clause.timezone);
                passed = clause.weekdays.includes(new Date(local.day + 'T00:00:00Z').getUTCDay() || 7);
                this.log('AKTION', 'Kalendertag gegen erlaubte Wochentage geprüft.', {Erfüllt: passed}, {...context, condition: index + 1});
                return passed;
            }
            else {
                const local = this.local(clause.timezone);
                const overnight = clause.after > clause.before;
                const anchor = new Date(local.day + 'T00:00:00Z');
                if (overnight && local.time < clause.before) anchor.setUTCDate(anchor.getUTCDate() - 1);
                passed = (overnight ? local.time >= clause.after || local.time < clause.before : local.time >= clause.after && local.time < clause.before)
                    && (!clause.weekdays || clause.weekdays.includes(anchor.getUTCDay() || 7));
            }
            this.log('AKTION', 'Bedingung geprüft.', {Erfüllt: passed}, {...context, condition: index + 1});
            return passed;
        });
    }

    receive(event) {
        if ((!this.active && this.executions.size === 0) || !event?.entity?.isDevice?.() || !object(event.payload)
            || ['publishCached', 'lastSeenChanged'].includes(event.stateChangeReason)) return;
        const id = event.entity.ieeeAddr;
        for (const execution of this.executions.values()) if (execution.once && !execution.cancelled) {
            for (const clause of execution.rule.conditions) if (clause.device === id && Object.hasOwn(event.payload, clause.property)) {
                const values = execution.values.get(id) ?? Object.create(null);
                values[clause.property] = scalar(event.payload[clause.property]) ? event.payload[clause.property] : undefined;
                execution.values.set(id, values);
            }
        }
        if (!this.active) return;
        const properties = this.references.get(id);
        if (!properties || !this.device(id)) return;
        const values = this.values.get(id) ?? Object.create(null);
        const previous = {...(this.changes.get(id) ?? {})};
        for (const property of properties) {
            if (Object.hasOwn(event.payload, property)) values[property] = scalar(event.payload[property]) ? event.payload[property] : undefined;
        }
        this.values.set(id, values);
        this.changes.set(id, {...previous, ...values});
        this.log('EMPFANG', 'Neue Eigenschaften einer konfigurierten Quelle ausgewertet.', {Eigenschaften: [...properties].filter(property => Object.hasOwn(event.payload, property)).length});
        for (const rule of this.rules) {
            rule.triggers.forEach((trigger, index) => {
                if (trigger.type !== 'device' || trigger.device !== id || !Object.hasOwn(event.payload, trigger.property)) return;
                const key = rule.id + ':' + index;
                const matches = compare(values[trigger.property], trigger);
                const priorContext = this.holds.get(key)?.context;
                const context = priorContext ?? this.trace(rule, {trigger: index + 1});
                this.log('AKTION', 'Auslöser geprüft.', {Erfüllt: matches}, context);
                const changed = !Object.hasOwn(previous, trigger.property) || previous[trigger.property] !== values[trigger.property];
                if (matches && trigger.on_change && !changed) this.log('AKTION', 'Wert unverändert; Auslöser verlangt eine Wertänderung.', {Regel: rule.id, Auslöser: index + 1}, context);
                if (!trigger.for_seconds) { if (matches && (!trigger.on_change || changed)) this.launch(rule, context); return; }
                const prior = this.holds.get(key);
                if (!matches) {
                    if (prior?.timer !== null && prior?.timer !== undefined) this.clock.clearTimeout(prior.timer);
                    if (prior) this.log('AKTION', 'Mindestdauer abgebrochen.', {}, context);
                    this.holds.delete(key);
                } else if (!prior && (!trigger.on_change || changed)) {
                    const generation = this.generation;
                    this.log('AKTION', 'Mindestdauer begonnen.', {}, {...context, delay: trigger.for_seconds});
                    const hold = {context, timer: this.clock.setTimeout(() => {
                        hold.timer = null;
                        if (generation === this.generation && this.active && compare(this.values.get(id)?.[trigger.property], trigger)
                            && this.device(id)) {
                            this.log('AKTION', 'Mindestdauer erreicht.', {}, context);
                            this.launch(rule, context);
                        }
                    }, trigger.for_seconds * 1000)};
                    hold.timer?.unref?.();
                    this.holds.set(key, hold);
                }
            });
        }
    }

    tick() {
        if (!this.active) return;
        for (const rule of this.rules) rule.triggers.forEach((trigger, index) => {
            if (trigger.type === 'sun') { this.tickSolar(rule, trigger, index); return; }
            if (trigger.type !== 'time') return;
            const local = this.local(trigger.timezone);
            const key = rule.id + ':' + index;
            if (local.time === trigger.at && (!this.days.has(key) || local.day > this.days.get(key))) {
                this.days.set(key, local.day);
                const context = this.trace(rule, {trigger: index + 1});
                this.log('AKTION', 'Zeitpunkt erreicht.', {}, context);
                this.launch(rule, context);
            }
        });
    }

    tickSolar(rule, trigger, index) {
        const now = this.clock.wallNow(), day = this.local(trigger.timezone).day, key = rule.id + ':' + index;
        if (this.solar.get(key)?.day !== day) {
            const anchor = Date.parse(day + 'T00:00:00Z'), targets = [];
            for (const offset of [-1, 0, 1]) {
                const date = new Date(anchor + offset * 86400000).toISOString().slice(0, 10);
                targets.push({day: date, at: solarForLocalDay(date, trigger)});
            }
            this.solar.set(key, {day, targets});
            const context = {rule: this.active.document.automations.indexOf(rule) + 1, trigger: index + 1,
                revision: this.active.document.revision, origin: this.system};
            this.log('AKTION', 'Astro-Auslöser für den heutigen Tag geprüft; Zeitversatz berücksichtigt.', {}, context);
            if (targets[1].at === null) this.log('AKTION', 'Heute gibt es für dieses Sonnenereignis keinen Zeitpunkt; Auslöser wird übersprungen.', {}, context);
        }
        for (const target of this.solar.get(key).targets) {
            if (target.at === null || Math.floor(now / 1000) !== Math.floor(target.at / 1000)
                || (this.days.has(key) && target.day <= this.days.get(key))) continue;
            this.days.set(key, target.day);
            const context = this.trace(rule, {trigger: index + 1});
            this.log('AKTION', 'Astro-Zeitpunkt einschließlich Zeitversatz erreicht.', {}, context);
            this.launch(rule, context);
        }
    }

    wait(milliseconds, generation, context = {}) {
        if (!this.canContinue(generation, context)) return Promise.resolve(false);
        if (!milliseconds) return Promise.resolve(true);
        return new Promise(resolve => {
            const timer = this.clock.setTimeout(() => { this.waits.delete(timer); resolve(this.canContinue(generation, context)); }, milliseconds);
            timer?.unref?.();
            this.waits.set(timer, {resolve, run: context.run});
        });
    }

    launch(rule, context = this.trace(rule), once = null) {
        const now = this.clock.now();
        const reason = !this.active && !once ? 'inactive' : this.running.has(rule.id) ? 'already_running'
            : this.tasks.size >= RUNTIME_LIMITS.concurrentRules ? 'concurrency_limit'
                : now - (this.cooldowns.get(rule.id) ?? -Infinity) < RUNTIME_LIMITS.ruleCooldownMs ? 'cooldown' : '';
        if (reason) {
            this.log('AKTION', 'Regellauf nicht gestartet.', {Regel: rule.id, Grund: reason}, context);
            if (once) throw new RuntimeError(reason === 'already_running' ? 'rule_busy' : 'busy');
            return;
        }
        const generation = this.generation;
        const execution = {rule, context, generation, candidate: once ?? this.active, once: Boolean(once), cancelled: false};
        if (once) {
            execution.values = new Map();
            for (const clause of rule.conditions) if (clause.device) {
                let cached;
                try { cached = this.readState?.(this.device(clause.device)); } catch { /* Unknown conditions cannot authorize a command. */ }
                const values = execution.values.get(clause.device) ?? Object.create(null);
                if (object(cached) && Object.hasOwn(cached, clause.property) && scalar(cached[clause.property])) values[clause.property] = cached[clause.property];
                execution.values.set(clause.device, values);
            }
        }
        this.executions.set(context.run, execution);
        if (!this.conditions(rule, context)) {
            this.executionState(rule, context, 'skipped', 'conditions_not_met');
            this.executions.delete(context.run);
            this.log('AKTION', 'Regellauf nicht gestartet.', {Grund: 'conditions_not_met'}, context);
            return this.results.get(rule.id);
        }
        this.cooldowns.set(rule.id, now);
        this.executionState(rule, context, 'running');
        this.log('START', once ? 'Einmaliger Regellauf mit erfüllten Bedingungen begonnen.' : 'Auslöser und Bedingungen erfüllt; Aktionsfolge begonnen.', {Regel: rule.id}, context);
        // Schedule the task after registering it so synchronous feedback cannot re-enter this rule.
        const task = Promise.resolve().then(() => this.run(rule, generation, context)).catch(error => {
            if (execution.cancelled) return;
            const code = error instanceof RuntimeError ? error.code : 'action_failed';
            if (!once) this.lastError = code;
            this.executionState(rule, context, 'failed', code);
            this.log('FEHLER', 'Aktionsfolge abgebrochen; keine automatische Wiederholung.', {Regel: rule.id, Grund: code}, context);
        }).finally(() => {
            if (this.results.get(rule.id)?.run === context.run && this.results.get(rule.id)?.state === 'running') this.executionState(rule, context, 'completed');
            this.executions.delete(context.run);
            if (this.running.get(rule.id) === task) this.running.delete(rule.id);
            this.tasks.delete(task);
            this.log('ENDE', 'Aktionsfolge beendet; Übergaben sind keine Gerätebestätigung.', {Regel: rule.id}, context);
        });
        this.running.set(rule.id, task);
        this.tasks.add(task);
        return this.results.get(rule.id);
    }

    async run(rule, generation, context) {
        const document = this.executions.get(context.run)?.candidate.document ?? this.active?.document;
        for (const [index, entry] of expandActionPlan(document, rule.actions).entries()) {
            const {action} = entry;
            if (!this.canContinue(generation, context)) return;
            const step = {...context, step: index + 1, delay: action.delay_seconds ?? 0,
                ...(entry.sequence ? {sequence: entry.sequence, sequence_step: entry.sequence_step} : {})};
            if (entry.sequence_step === 1) this.log('AKTION', 'Wiederverwendbare Aktionsfolge im Regellauf begonnen.', {}, step);
            this.executionState(rule, context, 'running', '', index + 1);
            if (step.delay) this.log('AKTION', 'Wartezeit vor Aktionsschritt begonnen.', {}, step);
            if (!await this.wait(step.delay * 1000, generation, context)) {
                this.log('AKTION', 'Aktionsschritt wegen Stopp abgebrochen.', {}, step);
                return;
            }
            if (step.delay) this.log('AKTION', 'Wartezeit vor Aktionsschritt beendet.', {}, step);
            if (!this.canContinue(generation, context)) return;
            if (!this.conditions(rule, step)) {
                this.executionState(rule, context, 'skipped', 'conditions_not_met', index + 1);
                this.log('AKTION', 'Bedingung vor Aktion nicht mehr erfüllt.', {Regel: rule.id}, step); return;
            }
            try {
                if (action.target.system !== undefined) {
                    await this.remoteDispatch(action, step);
                } else {
                    await this.executeLocal(action, generation, step);
                }
            } catch (error) {
                this.log('FEHLER', 'Aktionsschritt fehlgeschlagen; die restliche Folge wird abgebrochen.', {Grund: error instanceof RuntimeError ? error.code : 'action_failed'}, step);
                throw error;
            }
        }
    }

    async executeLocal(action, generation = this.generation, context = {}) {
        if (!this.canContinue(generation, context)) throw new RuntimeError('mesh_stopped');
        const entity = this.target(action.target);
        let values = action.values;
        if (action.type === 'scene') {
            if (!hasScene(entity, action.target.type, action)) throw new RuntimeError('scene_unavailable');
            values = {scene_recall: action.scene_id};
            this.log('AKTION', 'Vorhandene Szene für den Aufruf erneut geprüft.', {Zielart: action.target.type, Gerätekanal: action.endpoint !== undefined}, context);
        }
        if (action.type === 'toggle') {
            const current = this.readState(entity)?.[action.property];
            if (current !== 'ON' && current !== 'OFF') throw new RuntimeError('state_unavailable');
            values = {[action.property]: current === 'ON' ? 'OFF' : 'ON'};
            this.log('AKTION', 'Umschaltziel aus dem aktuellen Z2M-Zustand ermittelt.', {Zielart: action.target.type}, context);
        }
        const now = this.clock.now();
        this.sentAt = this.sentAt.filter(time => now - time < 1000);
        if (this.sentAt.length >= RUNTIME_LIMITS.commandsPerSecond) throw new RuntimeError('rate_limited');
        this.sentAt.push(now);
        this.log('SENDEN', action.type === 'scene' ? 'Szenenaufruf an native Z2M-Befehlsverarbeitung übergeben; Gerätewirkung nicht bestätigt.'
            : 'Set-Aktion an native Z2M-Befehlsverarbeitung übergeben; Gerätewirkung nicht bestätigt.', {Zielart: action.target.type, Eigenschaften: Object.keys(values).length}, context);
        let timeout;
        try {
            await Promise.race([
                Promise.resolve().then(() => {
                    if (!this.canContinue(generation, context)) throw new RuntimeError('mesh_stopped');
                    return this.dispatch(entity.name + (action.type === 'scene' && action.endpoint !== undefined ? '/' + action.endpoint : '') + '/set', JSON.stringify(values));
                }),
                new Promise((_, reject) => { timeout = this.clock.setTimeout(() => reject(new RuntimeError('action_timeout')), RUNTIME_LIMITS.publishTimeoutMs); })
            ]);
            this.log('EMPFANG', 'Native Befehlsverarbeitung hat die Übergabe angenommen; Gerätewirkung nicht bestätigt.', {}, context);
        } finally { this.clock.clearTimeout(timeout); }
    }
}
