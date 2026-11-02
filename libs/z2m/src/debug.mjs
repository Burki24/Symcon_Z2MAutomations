import {createHash, randomUUID} from 'node:crypto';
import DEBUG_EVENTS from '../debug-events.json' with {type: 'json'};

export {DEBUG_EVENTS};
const debugCodes = new Map(Object.entries(DEBUG_EVENTS).map(([code, entry]) => [entry.message, code]));
const debugUUID = value => typeof value === 'string' && /^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/.test(value);
export const debugTrace = value => value && debugUUID(value.run) && Number.isInteger(value.rule) && value.rule >= 1 && value.rule <= 100
    && Number.isInteger(value.step) && value.step >= 1 && value.step <= 16 && Number.isSafeInteger(value.revision) && value.revision >= 1
    ? {run: value.run, rule: value.rule, step: value.step, revision: value.revision} : undefined;

// Live diagnostics only: no disk, retries, MQTT retain or awaited device-side work.
export class RuntimeDiagnostics {
    constructor(mqtt, clock = {}) {
        this.mqtt = mqtt;
        this.now = clock.now ?? (() => performance.now());
        this.wall = clock.wallNow ?? (() => Date.now());
        this.clients = new Map();
        this.queue = [];
        this.sending = false;
        this.setTimeout = clock.setTimeout ?? setTimeout;
        this.clearTimeout = clock.clearTimeout ?? clearTimeout;
        this.sentAt = [];
        this.dropped = 0;
        this.closed = false;
    }

    configure(client, seconds) {
        this.prune();
        if (this.closed || !debugUUID(client) || !Number.isInteger(seconds) || seconds < 0 || seconds > 900) return null;
        if (seconds > 0 && !this.clients.has(client) && this.clients.size >= 8) return null;
        if (seconds === 0) this.clients.delete(client);
        else this.clients.set(client, this.now() + seconds * 1000);
        return {debug_version: 1, client, seconds, active: seconds > 0};
    }

    prune() {
        for (const [client, until] of this.clients) if (until <= this.now()) this.clients.delete(client);
    }

    emit(message, details = {}, context = {}) {
        try {
            this.prune();
            const code = debugCodes.get(message);
            if (this.closed || !code || this.clients.size === 0 || this.mqtt.isConnected?.() === false) return;
            this.sentAt = this.sentAt.filter(time => this.now() - time < 1000);
            if (this.queue.length >= 128 || this.sentAt.length >= 80) { this.dropped = Math.min(1000000, this.dropped + 1); return; }
            const record = {id: randomUUID(), at: Math.trunc(this.wall()), event: code};
            if (debugUUID(context.run)) record.run = context.run;
            for (const [key, max] of Object.entries({rule: 100, trigger: 8, condition: 16, step: 16, revision: Number.MAX_SAFE_INTEGER, delay: 86400})) {
                if (Number.isSafeInteger(context[key]) && context[key] >= (key === 'delay' ? 0 : 1) && context[key] <= max) record[key] = context[key];
            }
            if (typeof context.origin === 'string' && context.origin.length <= 1024) record.origin = createHash('sha256').update(context.origin).digest('hex');
            if (typeof details.Erfüllt === 'boolean') record.passed = details.Erfüllt;
            const reason = details.Grund;
            if (['inactive', 'already_running', 'concurrency_limit', 'cooldown', 'conditions_not_met', 'action_failed', 'action_timeout', 'unknown_entity', 'state_unavailable', 'rate_limited', 'scene_unavailable', 'mesh_timeout', 'mesh_unavailable', 'mesh_not_allowed', 'mesh_stopped', 'mesh_rejected', 'mesh_invalid', 'mesh_busy'].includes(reason)) record.reason = reason;
            this.sentAt.push(this.now());
            this.queue.push({clients: [...this.clients.keys()], record});
            if (!this.sending) void this.drain().catch(() => { this.dropped = Math.min(1000000, this.dropped + 1); });
        } catch { /* Diagnostics must not affect automation behavior. */ }
    }

    async drain() {
        this.sending = true;
        try {
            while (this.queue.length && !this.closed) {
                this.prune();
                const item = this.queue.shift();
                const clients = item.clients.filter(client => this.clients.has(client));
                if (!clients.length || this.mqtt.isConnected?.() === false) continue;
                const dropped = this.dropped;
                this.dropped = 0;
                let timer;
                try {
                    await Promise.race([
                        Promise.resolve().then(() => {
                            this.prune();
                            const recipients = clients.filter(client => this.clients.has(client));
                            if (this.closed || !recipients.length) return;
                            return this.mqtt.publish('symcon/automations/debug', JSON.stringify({protocol_version: 1,
                                debug_version: 1, session: this.session, clients: recipients, dropped, record: item.record}),
                            {skipLog: true, skipReceive: true, clientOptions: {qos: 0, retain: false}});
                        }),
                        new Promise((_, reject) => { timer = this.setTimeout(() => reject(new Error()), 2000); timer?.unref?.(); })
                    ]);
                } catch {
                    this.dropped = Math.min(1000000, this.dropped + dropped + 1 + this.queue.length);
                    this.queue.length = 0;
                }
                finally { this.clearTimeout(timer); }
            }
        } finally { this.sending = false; }
    }

    stop() { this.closed = true; this.clients.clear(); this.queue.length = 0; }
}
