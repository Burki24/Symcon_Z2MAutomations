import {createHash} from 'node:crypto';
import {join} from 'node:path';

// Only waiting automatic runs are recoverable. Commands are consumed before handoff.
export class RunJournal {
    constructor(store, binding, enabled) {
        this.store = store;
        this.binding = binding;
        this.enabled = enabled;
        this.runs = new Map();
        this.queue = Promise.resolve();
        this.frozen = false;
        this.failed = false;
        this.pending = 0;
    }

    save() {
        if (this.pending >= 64) { this.failed = true; return Promise.reject(new Error('storage_error')); }
        const payload = JSON.stringify({version: 1, ...this.binding(), runs: [...this.runs.values()]});
        const text = JSON.stringify({payload, checksum: createHash('sha256').update(payload).digest('hex')});
        if (Buffer.byteLength(text) > 32768) return Promise.reject(new Error('storage_error'));
        this.pending++;
        const task = this.queue.then(() => this.store.atomicWrite('running-automations.json', text)).finally(() => { this.pending--; });
        this.queue = task.catch(() => { this.failed = true; });
        return task;
    }

    put(record) {
        if (this.frozen) return Promise.resolve();
        if (this.failed || !this.enabled() || (!this.runs.has(record.run) && this.runs.size >= 16)) return Promise.reject(new Error('storage_error'));
        this.runs.set(record.run, record);
        return this.save();
    }

    remove(run) {
        if (this.frozen || !this.runs.delete(run)) return Promise.resolve();
        return this.save();
    }

    async reset(force = false) {
        const hadRuns = this.runs.size > 0;
        this.runs.clear();
        if (this.frozen) return;
        this.failed = false;
        if (hadRuns || force) await this.save();
    }

    async flush() {
        await this.queue;
        if (this.failed) throw new Error('storage_error');
    }

    async load() {
        this.frozen = false;
        this.failed = false;
        this.runs.clear();
        const path = join(this.store.directory, 'running-automations.json');
        let text;
        try {
            const stat = await this.store.io.lstat(path);
            if (!stat.isFile() || stat.isSymbolicLink() || stat.size > 32768) throw new Error('storage_error');
            const bytes = await this.store.io.readFile(path);
            if (bytes.length > 32768) throw new Error('storage_error');
            text = new TextDecoder('utf-8', {fatal: true}).decode(bytes);
        } catch (error) {
            if (error.code === 'ENOENT') return [];
            throw error;
        }
        const envelope = JSON.parse(text);
        if (!envelope || Object.keys(envelope).length !== 2 || typeof envelope.payload !== 'string'
            || envelope.checksum !== createHash('sha256').update(envelope.payload).digest('hex')) throw new Error('storage_error');
        const saved = JSON.parse(envelope.payload), binding = this.binding();
        if (!saved || Object.keys(saved).length !== 5 || saved.version !== 1 || saved.system !== binding.system
            || saved.revision !== binding.revision || saved.checksum !== binding.checksum
            || !Array.isArray(saved.runs) || saved.runs.length > 16) throw new Error('storage_error');
        const rules = new Set(), runs = new Set();
        for (const item of saved.runs) {
            if (!item || Object.keys(item).length !== 6 || typeof item.rule_id !== 'string'
                || typeof item.run !== 'string' || !/^[a-f0-9]{8}-(?:[a-f0-9]{4}-){3}[a-f0-9]{12}$/.test(item.run)
                || !Number.isInteger(item.step) || item.step < 1 || item.step > 16
                || !Number.isSafeInteger(item.saved_at) || item.saved_at < 0
                || !Number.isSafeInteger(item.deadline) || item.deadline <= item.saved_at
                || !Number.isInteger(item.delay) || item.delay < 1 || item.delay > 86400000
                || item.deadline - item.saved_at > item.delay || rules.has(item.rule_id) || runs.has(item.run)) throw new Error('storage_error');
            rules.add(item.rule_id); runs.add(item.run);
            this.runs.set(item.run, item);
        }
        return [...this.runs.values()];
    }
}
