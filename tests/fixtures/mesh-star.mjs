import assert from 'node:assert/strict';
import {mkdtemp, rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import Extension from '../../libs/z2m/z2m-automations.mjs';

export const sourceID = '0x0000000000000001';
export const settle = async () => { for (let n = 0; n < 3; n++) await new Promise(resolve => setImmediate(resolve)); };
export const rule = (id, system, value = 'single') => ({id, name: 'Synthetic ' + id, enabled: true,
    triggers: [{type: 'device', device: sourceID, property: 'action', operator: 'eq', value}], conditions: [],
    actions: [{type: 'set', target: {type: 'group', id: 7, system}, values: {state: 'ON'}}]});

class Clock {
    time = 0;
    next = 0;
    timers = new Map();
    now = () => this.time;
    wallNow = () => Date.parse('2026-01-01T12:00:00Z') + this.time;
    setTimeout = (callback, delay) => { const id = ++this.next; this.timers.set(id, {callback, at: this.time + delay}); return id; };
    clearTimeout = id => this.timers.delete(id);
    setInterval = (callback, delay) => { const id = ++this.next; this.timers.set(id, {callback, at: this.time + delay, interval: delay}); return id; };
    clearInterval = this.clearTimeout;
    async advance(ms) {
        const end = this.time + ms;
        while (true) {
            const entry = [...this.timers].filter(([, timer]) => timer.at <= end).sort((a, b) => a[1].at - b[1].at)[0];
            if (!entry) break;
            const [id, timer] = entry;
            this.time = timer.at;
            if (timer.interval) timer.at += timer.interval; else this.timers.delete(id);
            timer.callback(); await settle();
        }
        this.time = end; await settle();
    }
}

// Synthetic broker transport, not a Mosquitto implementation or acceptance test.
// Only mesh topics traverse the two star edges; the middle extension does not relay them.
export async function meshStar() {
    const nodes = [], publications = [], hops = [], errors = [], clock = new Clock();
    const links = new Set(['0:1', '0:2']);
    const network = {duplicateExecute: false, hold: () => false};
    let publication = 0, request = 0;
    const deliver = (node, event) => {
        queueMicrotask(() => {
            void node.extension.receive(event).catch(error => errors.push(error));
            if (network.duplicateExecute && event.topic.endsWith('/execute')) {
                void node.extension.receive(event).catch(error => errors.push(error));
            }
        });
    };
    const route = (at, from, packet) => {
        const node = nodes[at];
        if (node.connected && [...node.subscriptions].some(filter => packet.event.topic.startsWith(filter.slice(0, -1)))) deliver(node, packet.event);
        for (const next of at === 0 ? [1, 2] : [0]) {
            if (next === from || !links.has([at, next].sort().join(':'))) continue;
            hops.push({id: packet.id, from: node.system, to: nodes[next].system, phase: packet.phase});
            route(next, at, packet);
        }
    };
    const close = async () => {
        for (const node of nodes) {
            try { await node.extension?.stop(); } finally { await rm(node.directory, {recursive: true, force: true}); }
        }
        await settle();
        assert.deepEqual(errors, [], 'Synthetic transport must not hide receive failures');
        assert.equal(clock.timers.size, 0, 'Stopping all extensions must clear their timers');
    };
    try {
        for (const system of ['synthetic/s1', 'synthetic/s3', 'synthetic/s4']) {
            const index = nodes.length;
            const directory = await mkdtemp(join(tmpdir(), 'z2ma-mesh-star-'));
            const node = {system, directory, connected: true, subscriptions: new Set(), messages: [], commands: [], logs: []};
            nodes.push(node);
            const device = {ieeeAddr: sourceID, ID: sourceID, name: 'synthetic/button', isDevice: () => true};
            // Equal group IDs and names on all systems expose accidental local dispatch.
            const group = {ID: 7, name: 'synthetic/group'};
            const bus = {onMQTTMessage: () => {}, onPublishEntityState: (_owner, callback) => { node.event = callback; },
                removeListeners: () => {}, emitMQTTMessage: event => node.commands.push(event)};
            const mqtt = {isConnected: () => node.connected,
                subscribe: async topic => { node.subscriptions.add(topic); },
                unsubscribe: async topic => { node.subscriptions.delete(topic); },
                publish: async (topic, message, options) => {
                    topic = (options.baseTopic ?? system) + '/' + topic;
                    const data = JSON.parse(message);
                    node.messages.push({topic, data, options});
                    if (!topic.startsWith('z2ma/mesh/')) return;
                    const packet = {id: ++publication, source: system, phase: topic.split('/').at(-1),
                        event: {topic, message, retain: false}, data, options};
                    publications.push(packet);
                    if (!network.hold(packet)) route(index, -1, packet);
                }};
            node.extension = new Extension({resolveEntity: id => id === sourceID ? device : null, groupsIterator: () => [group]},
                mqtt, {get: () => ({state: 'OFF'})}, null, bus, null, null, null,
                {get: () => ({mqtt: {base_topic: system}})}, {debug: message => node.logs.push(message)}, {directory, clock});
            node.request = async (operation, fields = {}) => {
                const transaction = 'synthetic-star-' + ++request;
                await node.extension.receive({topic: system + '/symcon/automations/request/' + operation,
                    message: JSON.stringify({protocol_version: 1, transaction, ...fields})});
                const reply = node.messages.findLast(item => item.data.transaction === transaction)?.data;
                assert.ok(reply, 'Extension must answer its control request');
                return reply;
            };
            node.load = async (rules, peers) => {
                const current = node.extension.status();
                const saved = await node.request('store', {session: current.session, expected_revision: current.stored_revision,
                    yaml: JSON.stringify({schema_version: 1, revision: current.stored_revision + 1, automations: rules, mesh_peers: peers})});
                assert.equal(saved.status, 'ok', JSON.stringify(saved));
                const status = (await node.request('status')).data;
                assert.equal((await node.request('activate', {session: status.session, expected_revision: status.stored_revision,
                    checksum: status.checksum, activation_token: status.activation_token})).status, 'ok');
            };
            node.trigger = async (value = 'single') => { node.event({entity: device, payload: {action: value}}); await settle(); };
            node.debug = async () => {
                assert.equal((await node.request('debug', {session: node.extension.status().session,
                    client: '11111111-1111-4111-8111-111111111111', seconds: 60})).status, 'ok');
            };
            node.records = () => node.messages.filter(item => item.topic === system + '/symcon/automations/debug').map(item => item.data.record);
            node.runs = () => node.extension.status().execution.runs;
            await node.extension.start();
        }
        return {a: nodes[0], b: nodes[1], c: nodes[2], nodes, clock, network, publications, hops, close,
            setBranch: (leaf, connected) => {
                const key = '0:' + nodes.indexOf(leaf);
                assert.ok(key === '0:1' || key === '0:2');
                if (connected) links.add(key); else links.delete(key);
            }};
    } catch (error) { await close(); throw error; }
}
