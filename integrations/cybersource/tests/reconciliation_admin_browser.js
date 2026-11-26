'use strict';

// Optional loopback-only visual/navigation checks. Input contains synthetic credentials only.
const fs = require('fs');
const path = require('path');
const http = require('http');
const https = require('https');
const { spawnSync } = require('child_process');
const { once } = require('events');
const { chromium } = require(process.env.LM_TEST_PLAYWRIGHT || 'playwright');

let browser;
let proxy;
let activePage;
let activeOutput;
let lastStatus = 0;
const sockets = new Set();
let assertions = 0;
const screenshots = [];
const check = (condition, label) => { assertions++; if (!condition) throw new Error('FAIL: ' + label); };

async function main() {
    let raw = '';
    for await (const part of process.stdin) raw += part;
    const input = JSON.parse(raw);
    raw = '';
    const fixture = fs.realpathSync(input.fixture);
    const backend = new URL(input.url);
    const project = path.resolve(__dirname, '../../..');
    check(/^lm-admin-http-[a-f0-9]{32}$/.test(path.basename(fixture)) && fs.existsSync(path.join(fixture, 'fixture-only')), 'isolated fixture marker');
    check(backend.hostname === '127.0.0.1' && backend.protocol === 'http:' && backend.pathname === '/admin/conciliacion/', 'loopback backend only');
    check(input.username === 'fixture.operator' && typeof input.password === 'string', 'synthetic operator only');
    const output = path.resolve(input.output);
    activeOutput = output;
    check(!output.toLowerCase().startsWith(project.toLowerCase() + path.sep) && output.toLowerCase() !== project.toLowerCase(), 'screenshots outside the application');
    fs.mkdirSync(output, { recursive: true });
    const config = path.join(fixture, 'browser-tls.cnf');
    const key = path.join(fixture, 'browser-tls.key');
    const cert = path.join(fixture, 'browser-tls.crt');
    fs.writeFileSync(config, '[req]\ndistinguished_name=dn\nx509_extensions=v3\nprompt=no\n[dn]\nCN=localhost\n[v3]\nsubjectAltName=DNS:localhost,IP:127.0.0.1\n');
    const tls = spawnSync(process.env.LM_TEST_OPENSSL || 'C:/laragon/bin/git/mingw64/bin/openssl.exe',
        ['req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1', '-config', config, '-keyout', key, '-out', cert],
        { windowsHide: true, stdio: 'pipe' });
    check(tls.status === 0, 'ephemeral local TLS fixture');
    let origin;
    proxy = https.createServer({ key: fs.readFileSync(key), cert: fs.readFileSync(cert) }, (request, response) => {
        if (!['GET', 'POST'].includes(request.method) || request.url !== '/admin/conciliacion/') {
            response.writeHead(404).end(); return;
        }
        const headers = { ...request.headers, host: backend.host };
        if (request.method === 'POST') {
            if (headers.origin !== origin && headers.origin !== 'null' && headers.origin !== undefined) {
                response.writeHead(403).end(); return;
            }
            // The fixture router emulates the canonical HTTPS host. Chromium may
            // send an opaque Origin under no-referrer; emulate that host here too.
            headers.origin = 'https://libreriamarquense.com';
        }
        const upstream = http.request({ hostname: '127.0.0.1', port: backend.port, path: backend.pathname, method: request.method, headers }, remote => {
            response.writeHead(remote.statusCode, remote.headers); remote.pipe(response);
        });
        upstream.on('error', () => { if (!response.headersSent) response.writeHead(502); response.end(); });
        request.pipe(upstream);
    });
    proxy.on('connection', socket => { sockets.add(socket); socket.on('close', () => sockets.delete(socket)); });
    proxy.listen(0, '127.0.0.1');
    await once(proxy, 'listening');
    origin = 'https://localhost:' + proxy.address().port;
    browser = await chromium.launch({ channel: process.env.LM_TEST_BROWSER_CHANNEL || 'chrome', headless: true });
    const sizes = [{ width: 1280, height: 900 }, { width: 768, height: 1024 }, { width: 390, height: 844 }, { width: 320, height: 740 }];
    const legacy = 'LMQ-20260820161331-8A8F4A7D';
    for (const viewport of sizes) {
        // TLS and canonical Origin are emulated only inside this loopback fixture.
        const context = await browser.newContext({ viewport, ignoreHTTPSErrors: true, locale: 'es-GT', reducedMotion: 'reduce' });
        await context.route('**/*', route => {
            const url = new URL(route.request().url());
            return url.origin === origin && url.pathname === backend.pathname ? route.continue() : route.abort();
        });
        const page = await context.newPage();
        activePage = page;
        page.on('response', response => { if (response.url().startsWith(origin)) lastStatus = response.status(); });
        const errors = [];
        page.on('pageerror', error => errors.push(error.name));
        const capture = async name => {
            const file = path.join(output, name + '-' + viewport.width + '.png');
            await page.screenshot({ path: file, fullPage: true });
            screenshots.push(file);
            check(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), 'no horizontal overflow: ' + name + '/' + viewport.width);
        };
        const screen = async name => {
            await page.locator('[data-screen="' + name + '"]').waitFor();
            check(await page.locator('[data-screen]').count() === 1, 'one active section: ' + name);
        };
        const home = async () => { await page.locator('button[name="action"][value="home"]').click(); await screen('home'); };
        const references = () => page.locator('input[type="hidden"][name="reference"]').evaluateAll(nodes => nodes.map(node => node.value));
        await page.goto(origin + backend.pathname);
        await page.locator('#username').fill(input.username);
        await page.locator('#password').fill(input.password);
        await page.getByRole('button', { name: 'Iniciar sesión', exact: true }).click();
        await screen('home');
        check(await page.locator('.menu-card').count() === 3 && await page.locator('#reference').count() === 0, 'home contains three choices without a search form');
        await capture('inicio');
        await page.locator('.menu-card[value="search"]').click();
        await screen('search');
        check(await page.locator('table').count() === 0 && await page.locator('.menu-card').count() === 0, 'query is independent');
        await capture('consulta');
        await page.locator('#reference').fill(legacy);
        await page.getByRole('button', { name: 'Consultar referencia', exact: true }).click();
        await page.locator('#detalle-operacion').waitFor();
        check(await page.locator('[data-screen]').count() === 0 && await page.locator('#reference').count() === 0, 'detail hides all main sections');
        check(await page.getByText('NO VERIFICADO', { exact: true }).first().isVisible(), 'historical verification warning remains visible');
        check(await page.locator('#technical-details').getAttribute('open') === null, 'technical section collapsed initially');
        check(!await page.getByText('Cliente Historico Sintetico', { exact: true }).isVisible(), 'buyer details hidden until explicitly expanded');
        check(await page.locator('pre').count() === 0 && await page.locator('input[name="review_token"]').count() === 0, 'no commercial JSON or mutation controls');
        await capture('detalle');
        await page.locator('#technical-details > summary').click();
        check(await page.getByText('Cliente Historico Sintetico', { exact: true }).isVisible(), 'native technical disclosure works');
        await capture('detalle-tecnico');
        await page.getByRole('button', { name: 'Atrás', exact: true }).click();
        await screen('search');
        check(await page.locator('#reference').inputValue() === '', 'query back opens a fresh search');
        await page.locator('#reference').fill(legacy);
        await page.getByRole('button', { name: 'Consultar referencia', exact: true }).click();
        await page.locator('#detalle-operacion').waitFor();
        await page.getByRole('button', { name: 'Volver a consultar', exact: true }).click();
        await screen('search');
        await home();
        await page.locator('.menu-card[value="list"]').click();
        await screen('pending');
        const pendingRows = await references();
        await capture('pendientes');
        await page.locator('.actions button').first().click();
        await page.locator('#detalle-operacion').waitFor();
        await page.getByRole('button', { name: 'Atrás', exact: true }).click();
        await screen('pending');
        check(JSON.stringify(await references()) === JSON.stringify(pendingRows), 'pending back preserves its list');
        await home();
        await page.locator('.menu-card[value="incidents"]').click();
        await screen('incidents');
        check(await page.locator('#reference').count() === 0, 'incident list has no repeated query form');
        await capture('incidencias');
        await page.getByRole('button', { name: 'Revisar referencia ' + legacy, exact: true }).click();
        await page.locator('#detalle-operacion').waitFor();
        await page.getByRole('button', { name: 'Atrás', exact: true }).click();
        await screen('incidents');
        await page.getByRole('button', { name: 'Siguiente segmento', exact: true }).click();
        await screen('incidents');
        const incidentRows = await references();
        check(incidentRows.length > 0 && !incidentRows.includes(legacy), 'incident page two is distinct');
        await page.locator('.actions button').first().click();
        await page.locator('#detalle-operacion').waitFor();
        await page.getByRole('button', { name: 'Atrás', exact: true }).click();
        await screen('incidents');
        check(JSON.stringify(await references()) === JSON.stringify(incidentRows), 'incident back preserves page two');
        await page.getByRole('button', { name: 'Cerrar sesión', exact: true }).click();
        await page.locator('#username').waitFor();
        check(await page.locator('.menu-card').count() === 0 && await page.locator('#detalle-operacion').count() === 0, 'logout hides operational information');
        check(errors.length === 0, 'no browser execution errors');
        await context.close();
    }
    input.password = '';
    console.log(JSON.stringify({ Result: 'PASS', Assertions: assertions, Logins: sizes.length, Viewports: sizes.map(size => size.width), Screenshots: screenshots, RealCommercialOperations: false }));
}

main().catch(async error => {
    console.error(error.message);
    if (activePage && !activePage.isClosed()) {
        const state = await activePage.evaluate(() => ({
            loginVisible: !!document.querySelector('#username'),
            homeVisible: !!document.querySelector('[data-screen="home"]'),
            csrfDenied: document.body.textContent.includes('Formulario vencido o no autorizado'),
            accessDenied: document.body.textContent.includes('Origen administrativo no autorizado'),
            initUnavailable: document.body.textContent.includes('Panel no disponible'),
            loginDenied: document.body.textContent.includes('Solicitud no confirmada')
        }));
        console.error(JSON.stringify({ lastStatus, state }));
        await activePage.screenshot({ path: path.join(activeOutput, 'fixture-error.png'), fullPage: true });
    }
    process.exitCode = 1;
}).finally(async () => {
    if (browser) await browser.close();
    for (const socket of sockets) socket.destroy();
    if (proxy) await new Promise(resolve => proxy.close(resolve));
});
