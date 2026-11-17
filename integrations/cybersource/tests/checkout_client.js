'use strict';
const fs = require('fs');
const vm = require('vm');
const path = require('path');
const file = fs.readFileSync(path.resolve(__dirname, '../../../assets/js/cart.js'), 'utf8');
const source = file.slice(file.indexOf('function restaurarBotonPagoTarjeta('));
let assertions = 0;
function check(value, label) { assertions++; if (!value) throw new Error(label); }
function environment(responses, confirm = true) {
    let fetches = 0; let redirects = 0; let closed = 0;
    const bodies = [];
    const sandbox = { console: { error() {} },
        window: { confirm: () => confirm },
        document: { getElementById: id => ({ value: id === 'checkoutCsrfToken' ? 'synthetic-csrf' : '' }) },
        actualizarVistaPagoTarjeta() {}, cerrarModalPedido() {},
        cerrarVentanaPagoExterna() { closed++; },
        abrirVentanaPagoExterna: () => ({ location: { replace() { redirects++; } }, focus() {} }),
        prepararDeviceFingerprintParaPago: () => Promise.resolve(''),
        alert() {},
        fetch: async (_, options) => {
            bodies.push(JSON.parse(options.body));
            const response = responses[fetches++];
            if (!response) throw new Error('unexpected fetch');
            return { status: response.status, ok: response.status === 200, json: async () => response.body };
        }
    };
    vm.runInNewContext(source, sandbox, { filename: 'cart-payment-functions.js' });
    return { sandbox, bodies, stats: () => ({ fetches, redirects, closed }) };
}
const tick = () => new Promise(resolve => setTimeout(resolve, 10));
(async () => {
    const isolatedSource = fs.readFileSync(path.resolve(__dirname, '../../../assets/js/store-test-transport.js'), 'utf8');
    const destinations = [];
    function FakeXhr() {}
    FakeXhr.prototype.open = function (_, url) { destinations.push(url); };
    const testWindow = { LM_ISOLATED_STORE: { providerTest: false },
        location: { href: 'https://localhost:12345/cart.php', origin: 'https://localhost:12345' },
        fetch: async (url, options) => { destinations.push(url); return options; } };
    vm.runInNewContext(isolatedSource, { window: testWindow, XMLHttpRequest: FakeXhr, URL, Error, Promise });
    const requestOptions = { method: 'POST', body: '{"idarticulo":1}' };
    check(await testWindow.fetch('https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_tienda_articulos_listarid.php', requestOptions) === requestOptions,
        'isolated browser transport preserves request method and payload');
    check(destinations.pop() === 'https://localhost:12345/__test_store/api_tienda_articulos_listarid.php', 'browser catalog uses same-origin simulator');
    for (const denied of ['https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_cotizacion_insertar.php', 'https://example.com/api',
        'https://h.online-metrix.net/collector', 'https://localhost.evil.invalid/api']) {
        let blocked = false;
        try { await testWindow.fetch(denied); } catch (_) { blocked = true; }
        check(blocked && destinations.length === 0, 'browser TEST blocks unapproved external destination');
    }
    await testWindow.fetch('/api/cybersource/create_checkout.php', requestOptions);
    check(destinations.pop() === 'https://localhost:12345/api/cybersource/create_checkout.php', 'same-origin card controller preserved');
    new FakeXhr().open('POST', 'https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_tienda_ofertas.php');
    check(destinations.pop() === 'https://localhost:12345/__test_store/api_tienda_ofertas.php', 'legacy XHR uses isolated read-only simulator');
    let blockedXhr = false;
    try { new FakeXhr().open('POST', 'https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_cotizacion_insertar.php'); } catch (_) { blockedXhr = true; }
    check(blockedXhr && destinations.length === 0, 'legacy XHR cannot create real orders');
    const successful = environment([{ status: 200, body: { success: true, redirect_url: 'https://localhost/mock/pay' } }]);
    successful.sandbox.iniciarPagoTarjeta({}, {});
    successful.sandbox.iniciarPagoTarjeta({}, {});
    await tick();
    check(successful.stats().fetches === 1 && successful.stats().redirects === 1, 'double click must create one request/window flow');
    const retry = environment([{ status: 409, body: { requires_confirmation: true, purchase_confirmation: 'synthetic-confirmation', message: 'another purchase?' } },
        { status: 200, body: { success: true, redirect_url: 'https://localhost/mock/pay' } }]);
    retry.sandbox.iniciarPagoTarjeta({}, {}); await tick();
    check(retry.stats().fetches === 2 && retry.bodies[1].purchase_confirmation === 'synthetic-confirmation', 'new intent requires explicit customer confirmation');
    check(retry.stats().redirects === 1, 'confirmed new intention redirects once');
    const cancelled = environment([{ status: 409, body: { requires_confirmation: true, purchase_confirmation: 'synthetic-confirmation', message: 'another purchase?' } }], false);
    cancelled.sandbox.iniciarPagoTarjeta({}, {}); await tick();
    check(cancelled.stats().fetches === 1 && cancelled.stats().closed === 1 && cancelled.stats().redirects === 0, 'cancelled intention cannot create another attempt');
    const failed = environment([{ status: 400, body: { success: false, message: 'pending reconciliation' } }]);
    failed.sandbox.iniciarPagoTarjeta({}, {}); await tick();
    check(failed.stats().closed === 1 && !failed.sandbox.iniciarPagoTarjeta.inFlight, 'failed checkout restores UI without a new payment');
    console.log(`OK: ${assertions} checkout client assertions; no browser navigation or real fetches.`);
})().catch(error => { console.error(error.message); process.exitCode = 1; });
