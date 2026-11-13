import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {load} from 'js-yaml';
import {assertNoBrowserCapture} from './browser-capture-policy.mjs';

const read = path => readFileSync(new URL(path, import.meta.url), 'utf8');

test('DOM checks and real input remain available without browser capture', () => {
    assert.doesNotThrow(() => assertNoBrowserCapture('await page.locator("#issues").innerText(); await page.mouse.wheel(0, 300);'));
    assert.doesNotThrow(() => assertNoBrowserCapture(read('./designer-localization.browser.mjs')));
});

test('browser capture policy rejects image recording and traces before launch', () => {
    for (const source of [
        'await page.screenshot({path: "failure.png"});',
        'await page.locator("#issues")["screenshot"]();',
        'await expect(page).toHaveScreenshot();',
        'browser.newContext({recordVideo: {dir: "videos"}});',
        'context.tracing.start({snapshots: true});',
        'browser.startTracing(page);',
        'session.send("Page.captureScreenshot");',
        'session.send("Page.startScreencast");',
        'navigator.mediaDevices.getDisplayMedia();',
        'desktopCapturer.getSources({types: ["screen"]});'
    ]) assert.throws(() => assertNoBrowserCapture(source), /Browser capture is forbidden/);
});

test('CI Chromium image and pinned Playwright dependency use the same version', () => {
    const manifest = JSON.parse(read('../package.json'));
    const workflow = load(read('../.github/workflows/tests.yml'));
    const job = workflow.jobs.browser;
    assert.match(manifest.devDependencies.playwright, /^\d+\.\d+\.\d+$/);
    assert.match(job.container.image, new RegExp('^mcr\\.microsoft\\.com/playwright:v' + manifest.devDependencies.playwright.replaceAll('.', '\\.') + '-noble@sha256:[a-f0-9]{64}$'));
    assert.equal(job['continue-on-error'] ?? false, false);
    assert.ok(job.steps.some(step => step.run === 'npm run test:browser' && !step['continue-on-error']));
});
