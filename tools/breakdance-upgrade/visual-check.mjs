// Render a loopback staging site in a temporary, terminal-only Chromium profile.
import { spawn } from 'node:child_process';
import { mkdir, mkdtemp, readFile, rm, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { setTimeout as sleep } from 'node:timers/promises';

const [origin, configPath, directory, baseline] = process.argv.slice(2);
const local = new URL(origin);
if (local.origin !== origin || local.protocol !== 'http:'
    || !['127.0.0.1', '[::1]'].includes(local.hostname) || !local.port) {
    throw Error('Visual checks require the staging loopback origin.');
}
const config = JSON.parse(await readFile(configPath, 'utf8'));
const auditDir = join(directory, 'audit-inputs');
await mkdir(auditDir);
const profile = await mkdtemp(join(directory, '.headless-'));
const child = spawn(config.chrome || 'chromium', [
    '--headless=new', '--remote-debugging-pipe', '--user-data-dir=' + profile,
    '--no-first-run', '--no-default-browser-check', '--disable-background-networking',
    '--disable-component-update', '--disable-sync', '--disable-crash-reporter',
    '--proxy-server=http://127.0.0.1:9', '--proxy-bypass-list=<-loopback>;' + local.host,
    '--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1, EXCLUDE ::1',
    'about:blank',
], { stdio: ['ignore', 'ignore', 'ignore', 'pipe', 'pipe'] });

const pending = new Map();
const loaded = new Set();
const resources = new Map();
const requests = new Map();
const failed = new Map();
const blockedAssets = new Set();
const result = { pages: {}, issues: [], external_requests_blocked: 0 };
let next = 0;
let buffer = '';
let unavailable;
let closing = false;
child.on('error', closeConnection);
child.on('exit', () => closeConnection(Error('The headless browser stopped.')));

function closeConnection(error) {
    unavailable = error;
    for (const request of pending.values()) {
        clearTimeout(request.timer);
        request.reject(error);
    }
    pending.clear();
}

function send(method, params = {}, sessionId) {
    if (unavailable) return Promise.reject(unavailable);
    return new Promise((resolve, reject) => {
        const id = ++next;
        const timer = setTimeout(() => {
            pending.delete(id);
            reject(Error(method + ' timed out.'));
        }, 20000);
        pending.set(id, { resolve, reject, timer });
        child.stdio[3].write(JSON.stringify({ id, method, params, sessionId }) + '\0');
    });
}

function own(url) {
    return new URL(url).origin === origin;
}

child.stdio[4].setEncoding('utf8');
child.stdio[4].on('data', chunk => {
    buffer += chunk;
    const messages = buffer.split('\0');
    buffer = messages.pop();
    for (const payload of messages) {
        if (!payload) continue;
        const message = JSON.parse(payload);
        const response = pending.get(message.id);
        if (response) {
            pending.delete(message.id);
            clearTimeout(response.timer);
            if (message.error) response.reject(Error(message.error.message));
            else response.resolve(message.result);
            continue;
        }
        const value = message.params;
        switch (message.method) {
        case 'Fetch.requestPaused': {
            const url = value.request.url;
            const allowed = own(url) || /^(data|blob|about):/.test(url);
            const method = allowed ? 'Fetch.continueRequest' : 'Fetch.failRequest';
            if (!allowed) {
                result.external_requests_blocked++;
                const external = new URL(url);
                if (['Document', 'Stylesheet', 'Font'].includes(value.resourceType) ||
                    ['127.0.0.1', '[::1]', 'localhost'].includes(external.hostname)) {
                    blockedAssets.add(value.resourceType + ' ' + external.origin + external.pathname);
                }
            }
            send(method, {
                requestId: value.requestId, ...(allowed ? {} : { errorReason: 'BlockedByClient' }),
            }, message.sessionId).catch(error => {
                if (!closing) closeConnection(error);
            });
            break;
        }
        case 'Page.lifecycleEvent':
            if (value.name === 'load') loaded.add(value.loaderId);
            break;
        case 'Network.requestWillBeSent':
            if (own(value.request.url)) requests.set(value.requestId, value.request.url);
            break;
        case 'Network.loadingFailed':
            if (!value.canceled && requests.has(value.requestId)) {
                failed.set(new URL(requests.get(value.requestId)).pathname, value.errorText);
            }
            break;
        case 'Network.responseReceived':
            if (own(value.response.url)) {
                const { url, status, mimeType } = value.response;
                resources.set(value.requestId, { url, status, mimeType, type: value.type,
                    loaderId: value.loaderId, frameId: value.frameId });
            }
            break;
        }
    }
});

async function until(check, subject) {
    for (let attempt = 0; attempt < 100; attempt++) {
        if (await check()) return;
        await sleep(150);
    }
    throw Error('Timed out waiting for ' + subject);
}

async function measure(checks) {
    await Promise.race([document.fonts.ready, new Promise(resolve => setTimeout(resolve, 3500))]);
    const elements = {};
    for (const [selector, properties] of Object.entries(checks)) {
        const element = document.querySelector(selector);
        if (!element) {
            elements[selector] = null;
            continue;
        }
        const rect = element.getBoundingClientRect();
        const styles = getComputedStyle(element);
        const geometry = { x: rect.x, y: rect.y, width: rect.width, height: rect.height };
        elements[selector] = Object.fromEntries(properties.map(name =>
            [name, name in geometry ? geometry[name] : styles.getPropertyValue(name)]));
    }
    return {
        elements,
        overflow: Math.max(0, document.documentElement.scrollWidth - innerWidth),
        fonts: [...document.fonts].filter(font => font.status === 'loaded').map(font => font.family.replace(/["']/g, '')),
    };
}

function openMobileMenu(button) {
    const toggle = document.querySelector(button);
    if (!(toggle instanceof HTMLButtonElement) || toggle.form || !toggle.getClientRects().length) return false;
    toggle.click();
    return true;
}

async function changedPixels(before, after) {
    const [original, current] = await Promise.all([before, after].map(data =>
        createImageBitmap(new Blob([Uint8Array.from(atob(data), char => char.charCodeAt(0))],
            { type: 'image/png' }))));
    if (original.width !== current.width || original.height !== current.height) return 100;
    const { width, height } = original;
    const canvas = new OffscreenCanvas(width, height);
    const context = canvas.getContext('2d', { willReadFrequently: true });
    const [old, next] = [original, current].map(image => {
        context.drawImage(image, 0, 0);
        return context.getImageData(0, 0, width, height).data;
    });
    let count = 0;
    for (let i = 0; i < old.length; i += 4) {
        if (Math.abs(old[i] - next[i]) > 12 || Math.abs(old[i + 1] - next[i + 1]) > 12
            || Math.abs(old[i + 2] - next[i + 2]) > 12) count++;
    }
    return (100 * count) / (width * height);
}

const viewports = config.viewports || { desktop: [1280, 800], mobile: [390, 844] };
try {
    const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
    const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
    const cdp = (method, params) => send(method, params, sessionId);
    const evaluate = async (expression) => {
        const { result: value, exceptionDetails } = await cdp('Runtime.evaluate', {
            expression, awaitPromise: true, returnByValue: true,
        });
        if (exceptionDetails) throw Error(exceptionDetails.text);
        return value.value;
    };
    await cdp('Page.enable');
    await cdp('Page.setLifecycleEventsEnabled', { enabled: true });
    await cdp('Network.enable', { maxTotalBufferSize: 50 * 1024 * 1024 });
    await cdp('Network.setCacheDisabled', { cacheDisabled: true });
    await cdp('Network.setBypassServiceWorker', { bypass: true });
    await cdp('Fetch.enable', { patterns: [{ urlPattern: '*' }] });

    for (const route of config.routes) {
        result.pages[route.name] = {};
        for (const [screen, [width, height]] of Object.entries(viewports)) {
            const label = route.name + '-' + screen;
            const url = new URL(route.path, origin);
            if (!own(url.href)) throw Error('Route left the staging origin.');
            requests.clear();
            resources.clear();
            failed.clear();
            blockedAssets.clear();
            await cdp('Emulation.setDeviceMetricsOverride', { width, height, deviceScaleFactor: 1, mobile: false });
            const navigation = await cdp('Page.navigate', { url: url.href });
            if (navigation.errorText) throw Error(navigation.errorText);
            await until(() => loaded.has(navigation.loaderId), label);
            if (!await evaluate('location.origin === ' + JSON.stringify(origin))) {
                throw Error('Navigation left the staging origin.');
            }
            const checks = route.checks || { body: ['font-family'] };
            const metrics = await evaluate('(' + measure.toString() + ')(' + JSON.stringify(checks) + ')');
            const document = [...resources.values()].find(item => item.type === 'Document'
                && item.loaderId === navigation.loaderId && item.frameId === navigation.frameId);
            if (document?.status !== (route.status ?? 200)) {
                result.issues.push(label + ' returned HTTP ' + (document?.status ?? 'unknown'));
            }
            if (metrics.overflow > (route.max_overflow_px ?? 1)) {
                result.issues.push(label + ' has horizontal overflow: ' + metrics.overflow + 'px');
            }
            for (const [selector, value] of Object.entries(metrics.elements)) {
                if (!value) result.issues.push(label + ' is missing ' + selector);
            }
            for (const font of route.fonts || []) {
                if (!metrics.fonts.includes(font)) result.issues.push(label + ' did not load font ' + font);
            }
            for (const fragment of route.required_assets || []) {
                if (![...resources.values()].some(item =>
                    item.status >= 200 && item.status < 300 && new URL(item.url).pathname.includes(fragment))) {
                    result.issues.push(label + ' did not load ' + fragment);
                }
            }
            for (const [id, item] of resources) {
                const path = new URL(item.url).pathname;
                if (['Script', 'Stylesheet', 'Font', 'Image'].includes(item.type) &&
                    (item.status >= 400 || item.mimeType === 'text/html')) {
                    result.issues.push(label + ' failed asset ' + path + ' (HTTP ' + item.status + ', ' + item.mimeType + ')');
                }
                if (item.type === 'Stylesheet' && item.status === 200 && item.mimeType === 'text/css') {
                    try {
                        const body = await cdp('Network.getResponseBody', { requestId: id });
                        const data = body.base64Encoded ? Buffer.from(body.body, 'base64') : Buffer.from(body.body);
                        if (data.length <= 10 * 1024 * 1024) {
                            await writeFile(join(auditDir, label + '-' + id.replace(/[^\w.-]/g, '_') + '.css'), data);
                        } else {
                            result.issues.push(label + ' stylesheet exceeds capture limit ' + path);
                        }
                    } catch {
                        result.issues.push(label + ' could not capture stylesheet ' + path);
                    }
                }
            }
            for (const [path, reason] of failed) result.issues.push(label + ' failed request ' + path + ' (' + reason + ')');

            const { data } = await cdp('Page.captureScreenshot', { format: 'png', fromSurface: true });
            await writeFile(join(directory, label + '.png'), Buffer.from(data, 'base64'));
            if (baseline && route.max_changed_pixels_percent !== undefined) {
                const before = await readFile(join(baseline, label + '.png'), 'base64');
                const percent = await evaluate('(' + changedPixels.toString() + ')('
                    + JSON.stringify(before) + ',' + JSON.stringify(data) + ')');
                metrics.changed_pixels_percent = Number(percent.toFixed(3));
                if (percent > route.max_changed_pixels_percent) {
                    result.issues.push(label + ' changed ' + metrics.changed_pixels_percent + '% of screenshot pixels.');
                }
            }
            const html = await evaluate('document.documentElement.outerHTML');
            if (Buffer.byteLength(html) <= 10 * 1024 * 1024) {
                await writeFile(join(auditDir, label + '.html'), html);
            }
            if (screen === 'mobile' && route.mobile_menu) {
                const menu = route.mobile_menu;
                try {
                    const opened = await evaluate('(' + openMobileMenu.toString() + ')(' + JSON.stringify(menu.button) + ')');
                    if (!opened) throw Error('The menu button was unavailable.');
                    await until(() => evaluate('(() => { const item = document.querySelector('
                        + JSON.stringify(menu.open) + '); return !!item && item.getClientRects().length > 0'
                        + ' && getComputedStyle(item).visibility === "visible"; })()'), 'mobile menu');
                    metrics.mobile_menu_opened = true;
                    const menuScreenshot = await cdp('Page.captureScreenshot', { format: 'png', fromSurface: true });
                    await writeFile(join(directory, label + '-menu-open.png'), Buffer.from(menuScreenshot.data, 'base64'));
                } catch (error) {
                    result.issues.push(label + ' mobile menu: ' + error.message);
                }
            }
            for (const asset of blockedAssets) result.issues.push(label + ' blocked asset ' + asset);
            result.pages[route.name][screen] = { http_status: document?.status ?? null, ...metrics };
        }
    }
    await writeFile(join(directory, 'visual.json'), JSON.stringify(result, null, 2) + '\n');
} finally {
    closing = true;
    try { await send('Browser.close'); } catch {}
    if (child.exitCode === null) child.kill();
    await rm(profile, { recursive: true, force: true, maxRetries: 5 });
}
