'use strict';

(function () {
    if (!window.LM_ISOLATED_STORE || window.LM_ISOLATED_STORE.environment !== 'test') return;
    const hostname = new URL(window.location.href).hostname.toLowerCase();
    if (hostname === 'libreriamarquense.com' || hostname === 'www.libreriamarquense.com') return;
    const remote = 'https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/';
    function isolatedUrl(input) {
        const url = new URL(input, window.location.href);
        if (url.href.startsWith(remote) && /^api_tienda_[A-Za-z0-9_]+\.php$/.test(url.href.slice(remote.length))) {
            return new URL('/__test_store/' + url.href.slice(remote.length), window.location.origin).href;
        }
        if (url.origin === window.location.origin
            || window.LM_ISOLATED_STORE.providerTest && url.origin === 'https://h.online-metrix.net') return url.href;
        throw new Error('El entorno TEST bloquea conexiones externas no simuladas.');
    }
    const originalFetch = window.fetch.bind(window);
    window.fetch = function (input, options) {
        try {
            if (typeof input !== 'string' && !(input instanceof URL)) {
                throw new Error('TEST requiere una URL explicita para verificar el destino.');
            }
            return originalFetch(isolatedUrl(input), options);
        } catch (error) {
            return Promise.reject(error);
        }
    };
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function (method, url, ...args) {
        return originalOpen.call(this, method, isolatedUrl(url), ...args);
    };
}());
