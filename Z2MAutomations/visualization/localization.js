/* Local JSON catalogs and presentation bindings. No network or translation dependency. */
import {catalogs} from './locales.js';

const supported = new Set(['de', 'en']);
const storageKey = 'z2ma.designer.language';
const normalize = value => typeof value === 'string' ? value.toLowerCase().split(/[-_]/)[0] : '';
function initialLanguage() {
    try { const saved = globalThis.localStorage?.getItem(storageKey); if (supported.has(saved)) return saved; } catch {}
    for (const candidate of globalThis.navigator?.languages ?? [globalThis.navigator?.language]) {
        if (supported.has(normalize(candidate))) return normalize(candidate);
    }
    return 'en';
}
let currentLanguage = initialLanguage();
const bindings = new Map();
const listeners = new Set();
let audit = null;
const valueOf = value => typeof value === 'function' ? valueOf(value()) : value;
export const language = () => currentLanguage;
export const locale = () => currentLanguage === 'de' ? 'de-DE' : 'en-GB';
export const formatNumber = value => new Intl.NumberFormat(locale(), {maximumFractionDigits: 20}).format(value);
export const formatDate = (value, options) => new Intl.DateTimeFormat(locale(), options ?? {dateStyle: 'medium', timeStyle: 'medium'}).format(value);
export function t(key, values = {}) {
    const count = Number.isFinite(values.count) ? new Intl.PluralRules(locale()).select(values.count) : null;
    const text = catalogs[currentLanguage]?.[count ? `${key}.${count}` : key] ?? catalogs[currentLanguage]?.[key]
        ?? catalogs.en?.[count ? `${key}.${count}` : key] ?? catalogs.en?.[key];
    if (typeof text !== 'string') return 'Translation unavailable';
    return text.replace(/\{\{([A-Za-z][A-Za-z0-9_]*)\}\}/g, (_, name) => Object.hasOwn(values, name) ? String(values[name] ?? '') : `{{${name}}}`);
}
function bind(element, property, provider, apply) {
    let entries = bindings.get(element);
    if (!entries) { entries = new Map(); bindings.set(element, entries); }
    const update = () => apply(valueOf(provider));
    entries.set(property, update); update(); return element;
}
export const bindText = (element, provider) => bind(element, 'text', provider, value => { element.textContent = value ?? ''; });
export const bindAttribute = (element, name, provider) => bind(element, `attribute:${name}`, provider, value => { element.setAttribute(name, String(value ?? '')); });

function updateMarkup(existing, replacement) {
    const oldChildren = [...existing.childNodes]; const nextChildren = [...replacement.childNodes];
    if (oldChildren.length !== nextChildren.length) return;
    for (let index = 0; index < oldChildren.length; index++) {
        const old = oldChildren[index]; const next = nextChildren[index];
        if (old.nodeType === 3 && next.nodeType === 3) { if (old.data !== next.data) old.data = next.data; continue; }
        if (old.nodeType !== 1 || next.nodeType !== 1 || old.tagName !== next.tagName || old.id !== next.id) continue;
        for (const name of ['title', 'placeholder', 'aria-label', 'alt']) if (next.hasAttribute(name)) old.setAttribute(name, next.getAttribute(name));
        updateMarkup(old, next);
    }
}
export function bindHTML(element, provider) {
    const initial = valueOf(provider);
    element.innerHTML = initial;
    return bind(element, 'html', provider, text => {
        if (text === initial && !element.ownerDocument?.createElement) return;
        const template = element.ownerDocument?.createElement('template');
        if (!template) return;
        template.innerHTML = text; updateMarkup(element, template.content);
    });
}
export function localizeRecord(factory) {
    const initial = factory();
    return new Proxy(initial, {get(_target, key) { const record = factory(); const value = Reflect.get(record, key); return typeof value === 'function' ? value.bind(record) : value; }});
}
export function localizedOption(provider, value, defaultSelected, selected) {
    const option = new Option(String(valueOf(provider)), value, defaultSelected, selected);
    bindText(option, provider); return option;
}
export class MessageError extends Error {
    constructor(key, values = {}) {
        super(t(key, values));
        Object.defineProperty(this, 'message', {get: () => t(key, values), configurable: true});
    }
}
export function localizeResponse(data) {
    if (!data || typeof data !== 'object' || !Array.isArray(data.presentation?.messages)) return data;
    if (data.presentation.version !== 1 || data.presentation.messages.length > 256) return data;
    for (const descriptor of data.presentation.messages) {
        if (!Array.isArray(descriptor.path) || descriptor.path.length > 12 || typeof descriptor.key !== 'string'
            || !Object.hasOwn(catalogs.en, descriptor.key) || !descriptor.values || typeof descriptor.values !== 'object') continue;
        const path = descriptor.path;
        if (!['message', 'details', 'blocked', 'createNotice', 'editNotice', 'sequenceNotice', 'healthText', 'label', 'InstalledExtensionInfo', 'BundledExtensionInfo', 'ExtensionUpdateInfo'].includes(path.at(-1))
            || path.some(key => ['__proto__', 'constructor', 'prototype', 'configuration', 'name', 'sceneNames'].includes(key))) continue;
        let owner = data;
        for (const key of path.slice(0, -1)) { owner = owner && Object.hasOwn(owner, key) ? owner[key] : null; }
        if (!owner || typeof owner[path.at(-1)] !== 'string') continue;
        Object.defineProperty(owner, path.at(-1), {get: () => t(descriptor.key, descriptor.values), enumerable: true, configurable: true});
    }
    return data;
}
export function onLanguageChange(callback) { listeners.add(callback); return () => listeners.delete(callback); }
export function setLanguage(next, {persist = true, report = true} = {}) {
    if (!supported.has(next)) throw new Error('Unsupported language.');
    const previous = currentLanguage; currentLanguage = next;
    if (persist) { try { globalThis.localStorage?.setItem(storageKey, next); } catch {} }
    if (globalThis.document?.documentElement) globalThis.document.documentElement.lang = next;
    // Keep every original control and listener, including an open modal and its unsaved input.
    for (const [element, entries] of bindings) {
        if (element.isConnected === false) { bindings.delete(element); continue; }
        for (const update of entries.values()) update();
    }
    for (const callback of listeners) callback(next, previous);
    if (report && audit && previous !== next) void audit({language: next, previousLanguage: previous, openEditor: !!globalThis.document?.querySelector('dialog[open]')});
    return next;
}
export function installLanguagePicker({report} = {}) {
    if (typeof document === 'undefined') return;
    if (report) audit = report;
    document.documentElement.lang = currentLanguage;
    if (document.getElementById('designer-language')) return;
    const label = document.createElement('label'); label.className = 'designer-language';
    const caption = document.createElement('span'); bindText(caption, () => t('language.caption'));
    const select = document.createElement('select'); select.id = 'designer-language';
    select.add(new Option('Deutsch', 'de')); select.add(new Option('English', 'en')); select.value = currentLanguage;
    bindAttribute(select, 'aria-label', () => t('language.caption'));
    select.addEventListener('change', () => setLanguage(select.value));
    label.append(caption, select); document.querySelector('.toolbar')?.append(label);
    onLanguageChange(() => { select.value = currentLanguage; });
    for (const element of document.querySelectorAll('[data-i18n]')) bindText(element, () => t(element.dataset.i18n));
    for (const name of ['title', 'placeholder', 'aria-label']) for (const element of document.querySelectorAll(`[data-i18n-${name}]`)) {
        bindAttribute(element, name, () => t(element.getAttribute(`data-i18n-${name}`)));
    }
}
