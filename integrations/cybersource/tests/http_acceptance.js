'use strict';

const fs = require('fs');
const os = require('os');
const path = require('path');
const net = require('net');
const http = require('http');
const https = require('https');
const crypto = require('crypto');
const { spawn, spawnSync } = require('child_process');
const { once } = require('events');

const php = process.env.LM_TEST_PHP || 'C:/laragon/bin/php/php-8.4.12-nts-Win32-vs17-x64/php.exe';
const openssl = process.env.LM_TEST_OPENSSL || 'C:/laragon/bin/git/mingw64/bin/openssl.exe';
const project = path.resolve(__dirname, '../../..');
const root = path.join(fs.realpathSync(os.tmpdir()), 'lm-cybersource-http-' + crypto.randomBytes(8).toString('hex'));
const control = crypto.randomBytes(32).toString('hex');
const children = [];
let serverDiagnostics = '';
const sockets = new Set();
let front;
let mode = 'success';
let serial = 0;
let assertionCount = 0;
let orderCalls = 0;
let commits = 0;
let beforeOrderReply = null;
let storeQueries = 0;
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));
function check(ok, message) { assertionCount++; if (!ok) throw new Error('FAIL: ' + message); }
async function freePort() {
    const server = net.createServer();
    server.listen(0, '127.0.0.1');
    await once(server, 'listening');
    const port = server.address().port;
    await new Promise(resolve => server.close(resolve));
    return port;
}
function fixtureOrder() {
    return { nit: 'C/F', nombre_cliente: 'Cliente Prueba', telefono_cliente: '55555555', direccion_cliente: 'Test address',
        correo_cliente: 'test@example.invalid', tipo_documento_cliente: 'NIT', forma_pago: 'Tarjeta', total_venta: 30,
        total_ventades: 30, envio: 0, modalidad_entrega: 'store_pickup', lugar_entrega: 'store', no_auto_tarjeta: 'BROWSER-FORGED',
        datosArticulos: { articulos: { idarticulo: [1], cantidad: [2], precio_venta: [15], subtotal1: [30], subtotaldes1: [30],
            presen: ['UNIDAD'], tipo_presentacion: ['unidad'] } } };
}
async function main() {
    fs.mkdirSync(root, { mode: 0o700 });
    fs.writeFileSync(path.join(root, 'fixture-only'), 'synthetic');
    fs.mkdirSync(path.join(root, 'sessions'));
    fs.mkdirSync(path.join(root, 'php-sessions'));
    fs.writeFileSync(path.join(root, 'openssl.cnf'), '[req]\ndistinguished_name=dn\nx509_extensions=v3\nprompt=no\n[dn]\nCN=localhost\n[v3]\nsubjectAltName=DNS:localhost,IP:127.0.0.1\nbasicConstraints=critical,CA:TRUE\n');
    const cert = spawnSync(openssl, ['req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-days', '1',
        '-config', path.join(root, 'openssl.cnf'),
        '-keyout', path.join(root, 'ca.key'), '-out', path.join(root, 'ca.crt')], { windowsHide: true, stdio: 'pipe' });
    check(cert.status === 0, 'temporary local TLS certificate created');
    const ca = fs.readFileSync(path.join(root, 'ca.crt'));
    const backends = [await freePort(), await freePort(), await freePort()];
    front = https.createServer({ key: fs.readFileSync(path.join(root, 'ca.key')), cert: ca }, async (request, response) => {
        if (!request.headers.host?.startsWith('localhost:')) { response.writeHead(421).end(); return; }
        const chunks = [];
        for await (const chunk of request) chunks.push(chunk);
        const body = Buffer.concat(chunks);
        if (/^\/(mock\/store|__test_store)\/api_tienda_[A-Za-z0-9_]+\.php$/.test(request.url)) {
            storeQueries++;
            const endpoint = request.url.split('/').pop();
            const product = { idarticulo: 1, nombre: 'Cuaderno TEST', descripcion: 'Synthetic only', precio_venta: 15,
                nombre_01: 'UNIDAD', stock: 10, idcategoria: 1, idmarca: 1,
                presentaciones: [{ nombre: 'UNIDAD', tipo: 'unidad', precio: 15 }] };
            const data = endpoint === 'api_tienda_categorias_listar.php' ? [{ idcategoria: 1, nombre: 'TEST' }]
                : endpoint.startsWith('api_tienda_articulos_') || endpoint === 'api_tienda_menosde100.php' || endpoint === 'api_tienda_ofertas.php' ? [product] : [];
            response.setHeader('Content-Type', 'application/json');
            response.end(JSON.stringify({ success: true, data }));
            return;
        }
        if (request.url === '/mock/catalog') {
            response.setHeader('Content-Type', 'application/json');
            response.end(JSON.stringify({ success: true, data: [{ idarticulo: 1, stock: 10,
                presentaciones: [{ nombre: 'UNIDAD', tipo: 'unidad', precio: '15.00' }] }] }));
            return;
        }
        if (request.url === '/mock/orders') {
            orderCalls++;
            const order = JSON.parse(body);
            if (mode === 'reject') { response.end(JSON.stringify({ success: false })); return; }
            if (mode === 'invalid-json') { response.end('invalid-json'); return; }
            if (mode === 'http-error') { response.writeHead(503).end(); return; }
            commits++;
            if (mode === 'lost-response') { request.socket.destroy(); return; }
            try {
                if (beforeOrderReply) await beforeOrderReply();
                check(order.no_auto_tarjeta === '123456' || order.forma_pago !== 'Tarjeta', 'provider authorization sent to simulator');
                response.end(JSON.stringify({ success: true, idcotizacion: commits }));
            } catch (_) { response.writeHead(500).end(); }
            return;
        }
        if (request.url.startsWith('/mock/')) { response.writeHead(404).end(); return; }
        const proxy = http.request({ host: '127.0.0.1', port: backends[serial++ % backends.length],
            path: request.url, method: request.method,
            headers: { ...request.headers, 'x-isolation-proxy': control } }, remote => {
            response.writeHead(remote.statusCode, remote.headers);
            remote.pipe(response);
        });
        proxy.on('error', () => { if (!response.headersSent) response.writeHead(502); response.end(); });
        proxy.end(body);
    });
    front.on('connection', socket => { sockets.add(socket); socket.on('close', () => sockets.delete(socket)); });
    front.listen(0, '127.0.0.1');
    await once(front, 'listening');
    const port = front.address().port;
    const origin = `https://localhost:${port}`;
    // This file belongs only to the generated TEST document root, never the store.
    fs.writeFileSync(path.join(root, '.htaccess'), 'RewriteEngine On\nRewriteCond %{HTTP_HOST} !^localhost:[0-9]+$ [NC]\nRewriteRule ^ - [F,L]\n');
    const env = { ...process.env, LM_PAYMENT_ISOLATED_TEST: '1', LM_PAYMENT_TEST_ROOT: root,
        LM_PAYMENT_PROVIDER_TEST: '0', LM_PAYMENT_MOCK_ORIGIN: origin,
        LM_PAYMENT_TEST_ORIGIN: origin, LM_PAYMENT_TEST_CONTROL: control };
    const phpArgs = ['-n', '-d', `extension_dir=${path.join(path.dirname(php), 'ext')}`,
        '-d', 'extension=openssl', '-d', 'extension=curl', '-d', 'disable_functions=mail',
        '-d', 'display_errors=0', '-d', 'error_reporting=32767', '-d', `error_log="${path.join(root, 'php-errors.log')}"`,
        '-d', `session.save_path="${path.join(root, 'php-sessions')}"`, '-d', 'session.name=LM_ISOLATED_TEST'];
    for (const backend of backends) {
        const child = spawn(php, [...phpArgs, '-S', `127.0.0.1:${backend}`, path.join(__dirname, 'http_router.php')],
            { cwd: project, env, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'] });
        children.push(child);
        child.stdout.resume(); child.stderr.on('data', data => { serverDiagnostics = (serverDiagnostics + data).slice(-4000); });
        child.on('error', () => {});
    }
    function request(url, { method = 'GET', data, form = false, cookie = '', headers = {} } = {}) {
        return new Promise((resolve, reject) => {
            const body = data ? (form ? new URLSearchParams(data).toString() : JSON.stringify(data)) : '';
            const req = https.request({ hostname: '127.0.0.1', servername: 'localhost', port, path: url, method, ca,
                headers: { host: `localhost:${port}`, 'x-test-control': control, ...(cookie ? { cookie } : {}),
                    ...(body ? { 'content-type': form ? 'application/x-www-form-urlencoded' : 'application/json', 'content-length': Buffer.byteLength(body) } : {}), ...headers } }, res => {
                const chunks = []; res.on('data', chunk => chunks.push(chunk));
                res.on('end', () => { const text = Buffer.concat(chunks).toString(); let json; try { json = JSON.parse(text); } catch (_) {}
                    resolve({ status: res.statusCode, text, json, headers: res.headers }); });
            });
            req.on('error', reject); req.setTimeout(10000, () => req.destroy(new Error('fixture timeout'))); req.end(body);
        });
    }
    for (let tries = 0; tries < 50; tries++) {
        const ready = await request('/__fixture/info').catch(error => ({ error: error.message }));
        if (ready?.json?.isolated) break;
        if (tries === 49) throw new Error('isolated PHP servers did not start: ' + (ready.error || ready.status));
        await sleep(100);
    }
    async function client() {
        const info = await request('/__fixture/info');
        check(info.status === 200 && info.json.isolated, 'isolated host reachable over verified TLS without public redirect');
        return { cookie: info.headers['set-cookie'][0].split(';')[0], csrf: info.json.csrf };
    }
    async function checkout(who, confirmation) {
        return request('/api/cybersource/create_checkout.php', { method: 'POST', cookie: who.cookie,
            data: { ...fixtureOrder(), csrf_token: who.csrf, ...(confirmation ? { purchase_confirmation: confirmation } : {}) } });
    }
    async function begin() {
        const who = await client(); const created = await checkout(who);
        check(created.status === 200 && created.json.success, 'checkout uses server catalog through local HTTPS');
        const reference = created.json.reference;
        const form = await request(`/api/cybersource/secure_acceptance_redirect.php?ref=${reference}`, { cookie: who.cookie });
        check(form.status === 200 && form.text.includes(origin + '/mock/pay'), 'signed form targets simulator only');
        return { who, reference, form };
    }
    async function receipt(reference, mutation = {}, omit = []) {
        return (await request('/__fixture/receipt', { method: 'POST', data: { reference, mutation, omit } })).json;
    }
    async function notify(fields) { return request('/api/cybersource/payment_webhook.php', { method: 'POST', data: fields, form: true }); }
    async function state(reference) { return (await request(`/__fixture/session?ref=${reference}`)).json; }
    async function cli(script, ...args) {
        const child = spawn(php, [...phpArgs, path.join(project, 'integrations/cybersource', script), ...args],
            { cwd: project, env, windowsHide: true, stdio: ['ignore', 'pipe', 'pipe'] });
        let stdout = ''; let stderr = '';
        child.stdout.on('data', x => stdout += x); child.stderr.on('data', x => stderr += x);
        const [code] = await once(child, 'exit'); return { code, stdout, stderr };
    }
    const worker = (...args) => cli('process_pending.php', ...args);
    for (const page of ['index.php', 'cart.php', 'tienda.php', 'producto.php?id=1', 'servicios.php', 'nosotros.php', 'ofertas.php', 'menosde100.php', 'contact.php']) {
        const rendered = await request('/' + page);
        check(rendered.status === 200 && rendered.text.includes('<html'), 'whole-store PHP 8.4 render through isolated catalog: ' + page);
        check(rendered.headers['content-security-policy'].includes("connect-src 'self';"), 'TEST browser CSP blocks unmocked production services');
    }
    check(storeQueries >= 9 && orderCalls === 0, 'store rendering uses only local read-only API and never creates an order');
    for (const input of [{ mode: 'nuevos' }, { mode: 'categoria', idcategoria: '1' }, { mode: 'busqueda', search: 'cuaderno' },
        { mode: 'marca', idmarca: '1' }, { mode: 'ofertas' }, { mode: 'menosde100' }]) {
        const listed = await request('/assets/php/productos_paginados.php', { method: 'POST', data: input });
        check(listed.status === 200 && listed.json?.success && listed.json.data.length > 0,
            'catalog paging/stream/multi-cURL remains functional with verified HTTPS: ' + input.mode);
    }
    check(fs.existsSync(path.join(root, 'catalog-cache')), 'catalog cache separated from public store');
    const contactPage = await request('/contact.php');
    const contactToken = contactPage.text.match(/name="csrf_token" value="([^"]+)"/)[1];
    const contactCookie = contactPage.headers['set-cookie'][0].split(';')[0];
    const contact = await request('/assets/php/form-process.php', { method: 'POST', form: true, cookie: contactCookie,
        data: { csrf_token: contactToken, name: 'Cliente Prueba', email: 'test@example.invalid', phone_number: '55555555',
            msg_subject: 'Prueba aislada', message: 'Mensaje sintetico de prueba', website: '' } });
    check(contact.status === 200 && contact.text === 'success' && fs.readdirSync(root).some(x => x.startsWith('contact-mail-')),
        'contact form captures mail without native delivery or public backup logs');
    check((await request('/assets/php/form-process.php')).status === 405, 'contact form rejects non-POST requests');
    const contactPayload = { name: 'Cliente Prueba', email: 'test@example.invalid', phone_number: '55555555',
        msg_subject: 'Prueba aislada', message: 'Mensaje sintetico de prueba', website: '' };
    const contactCaptures = () => fs.readdirSync(root).filter(x => x.startsWith('contact-mail-')).length;
    const capturesBeforeInvalidContact = contactCaptures();
    for (const token of ['', 'invalid']) {
        const rejected = await request('/assets/php/form-process.php', { method: 'POST', form: true, cookie: contactCookie,
            data: { ...contactPayload, csrf_token: token } });
        check(rejected.status === 403, 'contact form rejects missing or invalid CSRF');
    }
    for (const field of ['name', 'email', 'phone_number', 'msg_subject', 'message', 'website']) {
        const page = await request('/contact.php');
        const cookie = page.headers['set-cookie'][0].split(';')[0];
        const token = page.text.match(/name="csrf_token" value="([^"]+)"/)[1];
        const payload = { ...contactPayload, csrf_token: token, [field + '[]']: 'invalid-array-value' };
        delete payload[field];
        const rejected = await request('/assets/php/form-process.php', { method: 'POST', form: true, cookie, data: payload });
        check(rejected.status === 422, 'contact form rejects array-shaped input: ' + field);
    }
    check(contactCaptures() === capturesBeforeInvalidContact, 'invalid contact requests cannot capture or send mail');
    const first = await begin();
    const duplicateCreates = await Promise.all([checkout(first.who), checkout(first.who)]);
    check(duplicateCreates.every(x => x.json?.reference === first.reference), 'double click reuses reference');
    const resumed = await request(`/api/cybersource/secure_acceptance_redirect.php?ref=${first.reference}`, { cookie: first.who.cookie });
    check(resumed.text === first.form.text, 'form interruption resumes byte-identical form');
    const accepted = await receipt(first.reference);
    const bad = { ...accepted, signature: 'invalid' };
    check((await notify(bad)).status === 400, 'invalid HMAC rejected over HTTP');
    const duplicates = await Promise.all(Array.from({ length: 6 }, () => notify(accepted)));
    check(duplicates.every(x => x.status === 200), 'six concurrent authentic callbacks acknowledged');
    check(orderCalls === 0, 'webhook never calls order API before acknowledging');
    check((await state(first.reference)).status === 'PAID', 'webhook persisted paid receipt');
    const returned = await request('/api/cybersource/payment_return.php', { method: 'POST', data: accepted, form: true });
    check(returned.text.includes('pedido fue enviado correctamente'), 'return records order and confirms truthful result');
    await request('/api/cybersource/payment_return.php', { method: 'POST', data: accepted, form: true });
    check(orderCalls === 1 && commits === 1, 'duplicate return cannot duplicate order');
    check(fs.readdirSync(root).filter(x => x.startsWith('mail-')).length === 1, 'one captured confirmation, native mail disabled');
    const challenge = await checkout(first.who);
    check(challenge.status === 409 && challenge.json.requires_confirmation, 'paid purchase needs explicit new intent');
    const newPurchase = await checkout(first.who, challenge.json.purchase_confirmation);
    check(newPurchase.json.reference !== first.reference, 'new legitimate purchase receives another reference');
    check((await checkout(first.who, challenge.json.purchase_confirmation)).json.reference === newPurchase.json.reference, 'lost new-intent response can be replayed idempotently');
    for (const decision of ['DECLINE', 'CANCEL']) {
        const current = await begin();
        await notify(await receipt(current.reference, { decision, reason_code: '203' }));
        check((await state(current.reference)).status === (decision === 'DECLINE' ? 'DECLINED' : 'CANCELLED'), 'provider final negative result retained');
        const next = await checkout(current.who);
        check(next.status === 409 && next.json.requires_confirmation, 'negative outcome offers validated new intent');
        const confirmed = await checkout(current.who, next.json.purchase_confirmation);
        check(confirmed.json.reference !== current.reference, 'negative outcome does not permanently block new purchase');
    }
    for (const scenario of ['blank-code', 'missing-code', 'partial', 'wrong-amount', 'wrong-currency', 'legacy']) {
        const current = await begin();
        if (scenario === 'legacy') await request('/__fixture/patch', { method: 'POST', data: { reference: current.reference, legacy: true } });
        const mutation = scenario === 'blank-code' ? { auth_code: '       ' } : scenario === 'partial' ? { reason_code: '110', auth_amount: '15.00' }
            : scenario === 'wrong-amount' ? { auth_amount: '29.00' } : scenario === 'wrong-currency' ? { req_currency: 'USD' } : {};
        const fields = await receipt(current.reference, mutation, scenario === 'missing-code' ? ['auth_code'] : []);
        const oldCalls = orderCalls;
        check((await notify(fields)).status === 200, 'authentic unapprovable receipt durably acknowledged: ' + scenario);
        const held = await state(current.reference);
        check(!held.payment_verified && Object.keys(held.reconciliation || {}).length > 0, 'financial anomaly retained without approval: ' + scenario);
        await request('/api/cybersource/payment_return.php', { method: 'POST', data: fields, form: true });
        check(orderCalls === oldCalls, 'held return never calls SystemsOL: ' + scenario);
    }
    let lostOrder;
    for (const failure of ['reject', 'invalid-json', 'http-error', 'lost-response']) {
        const current = await begin(); const fields = await receipt(current.reference); await notify(fields);
        mode = failure; const oldCalls = orderCalls;
        const result = await request('/api/cybersource/payment_return.php', { method: 'POST', data: fields, form: true });
        const saved = await state(current.reference);
        check(saved.status === 'PAID' && saved.order_dispatch.state === 'UNKNOWN', 'API failure preserves payment and one-shot journal: ' + failure);
        check(!result.text.includes('pedido fue enviado correctamente'), 'API failure never claims success: ' + failure);
        await request('/api/cybersource/payment_return.php', { method: 'POST', data: fields, form: true });
        check(orderCalls === oldCalls + 1, 'API failure never automatically resends: ' + failure);
        if (failure === 'lost-response') lostOrder = { reference: current.reference, quotation: commits };
        mode = 'success';
    }
    const conflict = await begin(); const conflictReceipt = await receipt(conflict.reference); await notify(conflictReceipt);
    const extra = await receipt(conflict.reference, { transaction_id: 'additional-charge-fixture' });
    beforeOrderReply = async () => { check((await notify(extra)).status === 200, 'conflict acknowledged while order request in flight'); };
    const oldMail = fs.readdirSync(root).filter(x => x.startsWith('mail-')).length;
    const conflictReturn = await request('/api/cybersource/payment_return.php', { method: 'POST', data: conflictReceipt, form: true });
    beforeOrderReply = null;
    const conflictState = await state(conflict.reference);
    check(conflictState.order_dispatch.state === 'CONFIRMED_HELD' && conflictState.external_order_response.idcotizacion > 0, 'in-flight external commit retained for reconciliation');
    check(!conflictReturn.text.includes('pedido fue enviado correctamente') && fs.readdirSync(root).filter(x => x.startsWith('mail-')).length === oldMail,
        'conflict cannot emit misleading UI or email confirmation');
    const queued = await begin(); const queuedReceipt = await receipt(queued.reference);
    const hold = request(`/__fixture/hold?ref=${queued.reference}`);
    for (let i = 0; i < 100 && !fs.existsSync(path.join(root, 'lock-ready')); i++) await sleep(20);
    check(fs.existsSync(path.join(root, 'lock-ready')), 'session lock held by separate HTTP worker');
    const start = Date.now(); const acknowledged = await notify(queuedReceipt);
    check(acknowledged.status === 200 && Date.now() - start < 4000, 'durable inbox acknowledges without waiting for blocked session');
    await hold;
    const countBeforeWorker = orderCalls;
    const processed = await worker();
    check([0, 2].includes(processed.code) && processed.stderr === '', 'CLI worker handles independent pending jobs and reports holds');
    check((await state(queued.reference)).status === 'ORDER_SENT' && orderCalls === countBeforeWorker + 1, 'CLI recovers queued callback without customer return');
    check((await request('/integrations/cybersource/process_pending.php')).status === 404, 'worker inaccessible via HTTP');
    check((await request('/integrations/cybersource/reconcile.php')).status === 404, 'operator tool inaccessible via HTTP');
    const pendingList = await cli('reconcile.php', '--list');
    check(pendingList.code === 0 && pendingList.stdout.includes(lostOrder.reference)
        && !pendingList.stdout.includes('fixture-access-not-a-credential'), 'read-only operator listing identifies unknown dispatch without keys');
    const beforeLink = orderCalls;
    const operatorView = await cli('reconcile.php', '--show', '--reference=' + lostOrder.reference);
    const reviewRevision = JSON.parse(operatorView.stdout).revision;
    const link = await cli('reconcile.php', '--reference=' + lostOrder.reference, '--link-order=' + lostOrder.quotation,
        '--evidence=SISTEMASOL-SIMULATOR-TICKET-01', '--operator=HTTP-OPERATOR', '--revision=' + reviewRevision,
        '--match-customer', '--match-amount', '--match-items', '--match-status', '--confirm');
    check(link.code === 0 && (await state(lostOrder.reference)).order_dispatch.state === 'CONFIRMED_MANUAL'
        && orderCalls === beforeLink, 'operator CLI links independently verified quotation without posting again');
    const cash = await client();
    const cashOrder = { ...fixtureOrder(), forma_pago: 'Pago Contra Entrega', csrf_token: cash.csrf };
    const nonCard = await request('/api/orders/create.php', { method: 'POST', data: cashOrder, cookie: cash.cookie });
    check(nonCard.status === 200 && nonCard.json.success, 'existing non-card purchase works against isolated simulator');
    const tlsClient = await client();
    const previousCalls = orderCalls;
    const previousSessions = fs.readdirSync(path.join(root, 'sessions')).filter(x => x.startsWith('LMQ-')).length;
    fs.writeFileSync(path.join(root, 'ca.crt'), 'not-a-trusted-certificate');
    try {
        const deniedTls = await checkout(tlsClient);
        check(deniedTls.status === 400 && !deniedTls.json.success, 'invalid CA fails closed; TLS verification is not disabled');
        check(orderCalls === previousCalls && fs.readdirSync(path.join(root, 'sessions')).filter(x => x.startsWith('LMQ-')).length === previousSessions,
            'TLS failure cannot create a payment attempt or order');
    } finally {
        fs.writeFileSync(path.join(root, 'ca.crt'), ca);
    }
    const phpErrors = fs.existsSync(path.join(root, 'php-errors.log')) ? fs.readFileSync(path.join(root, 'php-errors.log'), 'utf8') : '';
    check(phpErrors === '', 'HTTP flows contain no warnings, notices or PHP 8.4 deprecations');
    console.log(`OK: ${assertionCount} HTTPS/controller assertions; 3 PHP workers; local SystemsOL simulator and captured mail only.`);
}

main().catch(error => {
    console.error(error.message);
    const log = path.join(root, 'php-errors.log');
    if (fs.existsSync(log)) console.error(fs.readFileSync(log, 'utf8').slice(-5000));
    console.error(serverDiagnostics);
    process.exitCode = 1;
}).finally(async () => {
    const exits = children.map(child => child.exitCode !== null || child.signalCode !== null ? Promise.resolve() : once(child, 'exit'));
    for (const child of children) child.kill();
    await Promise.all(exits);
    for (const socket of sockets) socket.destroy();
    if (front?.listening) await new Promise(resolve => front.close(resolve));
    if (fs.existsSync(root)) {
        const resolved = fs.realpathSync(root);
        const temp = fs.realpathSync(os.tmpdir());
        if (path.dirname(resolved) === temp && /^lm-cybersource-http-[a-f0-9]{16}$/.test(path.basename(resolved))
            && fs.existsSync(path.join(resolved, 'fixture-only'))) fs.rmSync(resolved, { recursive: true, force: true });
    }
});
