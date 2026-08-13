// ============================================================
// 🛒 CARGAR Y MOSTRAR PRODUCTOS DEL CARRITO
// ============================================================

document.addEventListener("DOMContentLoaded", function () {
    cargarCarrito();
    inicializarEventos();
    inicializarRestriccionesFormularioPedido();
    actualizarVistaPagoTarjeta();
});

const CANTIDAD_MAXIMA_CARRITO = 99;
const TIPO_ENTREGA_RETIRO = 'store_pickup';
const TIPO_ENTREGA_ENVIO = 'shipping';
const PRODUCTO_IMG_PLACEHOLDER = 'assets/img/ProductoSinImagen.png';
const DEVICE_FINGERPRINT_MIN_WAIT_MS = 80;
let direccionEntregaAnterior = '';
let deviceFingerprintState = {
    id: '',
    sessionId: '',
    published: false,
    publishedAt: 0
};

function obtenerConfiguracionDeviceFingerprint() {
    const config = window.LM_DEVICE_FINGERPRINT_CONFIG || {};
    const scriptBaseUrl = String(config.script_base_url || 'https://h.online-metrix.net').replace(/\/+$/, '');

    return {
        enabled: config.enabled === true,
        merchantId: String(config.merchant_id || '').replace(/[^A-Za-z0-9_-]+/g, ''),
        orgId: String(config.org_id || '').replace(/[^A-Za-z0-9_-]+/g, ''),
        scriptBaseUrl: scriptBaseUrl === 'https://h.online-metrix.net' ? scriptBaseUrl : 'https://h.online-metrix.net'
    };
}

function generarTokenDeviceFingerprint() {
    const bytes = new Uint8Array(12);
    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
        window.crypto.getRandomValues(bytes);
    } else {
        for (let index = 0; index < bytes.length; index++) {
            bytes[index] = Math.floor(Math.random() * 256);
        }
    }

    const random = Array.from(bytes, function (byte) {
        return byte.toString(16).padStart(2, '0');
    }).join('');

    return ('LMQ-' + Date.now().toString(36) + '-' + random).slice(0, 48);
}

function reiniciarDeviceFingerprint() {
    deviceFingerprintState = {
        id: '',
        sessionId: '',
        published: false,
        publishedAt: 0
    };

    const container = document.getElementById('cybersourceDeviceFingerprintContainer');
    if (container) {
        container.innerHTML = '';
    }
}

function obtenerDeviceFingerprintId() {
    const config = obtenerConfiguracionDeviceFingerprint();
    if (!config.enabled || !config.merchantId || !config.orgId) {
        return '';
    }

    if (!deviceFingerprintState.id) {
        deviceFingerprintState.id = generarTokenDeviceFingerprint();
        deviceFingerprintState.sessionId = config.merchantId + deviceFingerprintState.id;
    }

    return deviceFingerprintState.id;
}

function publicarDeviceFingerprint() {
    const config = obtenerConfiguracionDeviceFingerprint();
    const deviceFingerprintId = obtenerDeviceFingerprintId();

    if (!deviceFingerprintId || deviceFingerprintState.published) {
        return deviceFingerprintId;
    }

    const container = document.getElementById('cybersourceDeviceFingerprintContainer');
    if (!container) {
        return deviceFingerprintId;
    }

    const script = document.createElement('script');
    script.type = 'text/javascript';
    script.async = true;
    script.src = config.scriptBaseUrl
        + '/fp/tags.js?org_id=' + encodeURIComponent(config.orgId)
        + '&session_id=' + encodeURIComponent(deviceFingerprintState.sessionId);

    container.innerHTML = '';
    container.appendChild(script);

    deviceFingerprintState.published = true;
    deviceFingerprintState.publishedAt = Date.now();

    return deviceFingerprintId;
}

function prepararDeviceFingerprintParaPago() {
    const deviceFingerprintId = publicarDeviceFingerprint();
    if (!deviceFingerprintId) {
        return Promise.resolve('');
    }

    const elapsed = Date.now() - deviceFingerprintState.publishedAt;
    const waitMs = Math.max(0, DEVICE_FINGERPRINT_MIN_WAIT_MS - elapsed);

    return new Promise(function (resolve) {
        window.setTimeout(function () {
            resolve(deviceFingerprintId);
        }, waitMs);
    });
}

function escaparHTML(valor) {
    return String(valor ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function obtenerIdProductoSeguro(valor) {
    const texto = String(valor ?? '').trim();
    return /^\d+$/.test(texto) && parseInt(texto, 10) > 0 ? texto : '';
}

function normalizarCantidadPedido(valor) {
    const texto = String(valor ?? '').trim();
    if (!/^\d+$/.test(texto)) {
        return null;
    }

    const cantidad = parseInt(texto, 10);
    return cantidad >= 1 && cantidad <= CANTIDAD_MAXIMA_CARRITO ? cantidad : null;
}

function escaparAtributoJs(valor) {
    return String(valor ?? '')
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/\r?\n/g, ' ');
}

function obtenerClaveCarrito(producto, idFallback = '') {
    if (producto?.cart_key) {
        return String(producto.cart_key);
    }

    if (window.Carrito && typeof Carrito.obtenerClaveProducto === 'function') {
        return Carrito.obtenerClaveProducto(producto);
    }

    return String(idFallback);
}

function obtenerIdHtmlSeguro(valor) {
    return String(valor ?? '').replace(/[^A-Za-z0-9_-]+/g, '-');
}

function obtenerStockPresentacionCarrito(producto) {
    if (window.Carrito && typeof Carrito.obtenerStockPresentacion === 'function') {
        return Carrito.obtenerStockPresentacion(producto);
    }

    if (producto?.stock_presentacion === null || producto?.stock_presentacion === undefined || producto?.stock_presentacion === '') {
        return null;
    }

    const stock = Number(String(producto.stock_presentacion).replace(',', '.'));
    return Number.isFinite(stock) ? Math.max(0, stock) : null;
}

function obtenerImagenProductoSegura(valor) {
    const texto = String(valor ?? '').trim();

    if (!texto) {
        return PRODUCTO_IMG_PLACEHOLDER;
    }

    try {
        const url = new URL(texto, window.location.href);
        if (url.protocol === 'http:' || url.protocol === 'https:') {
            return url.href;
        }
    } catch (error) {
        if (/^(assets\/|\.?\/?assets\/)/.test(texto)) {
            return texto;
        }
    }

    return PRODUCTO_IMG_PLACEHOLDER;
}

// ============================================================
// CARGAR PRODUCTOS DEL CARRITO DESDE LOCALSTORAGE
// ============================================================
function cargarCarrito() {
    const carrito = Carrito.obtenerCarrito();
    const tbody = document.querySelector('.cart-table tbody');

    if (!tbody) {
        console.error('No se encontró el tbody del carrito');
        return;
    }

    if (carrito.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="alert alert-info">
                        <h4>Tu carrito está vacío</h4>
                        <p>Agrega productos desde la tienda para comenzar a comprar.</p>
                        <a href="tienda.php" class="default-btn">Ir a la Tienda</a>
                    </div>
                </td>
            </tr>
        `;
        actualizarTotales(0);
        return;
    }

    tbody.innerHTML = '';

    carrito.forEach((producto) => {
        const idarticulo = obtenerIdProductoSeguro(producto.idarticulo);
        if (!idarticulo) {
            return;
        }

        const imagen = escaparHTML(obtenerImagenProductoSegura(producto.imagen));
        const nombre = escaparHTML(producto.nombre || 'Producto');
        const precio = parseFloat(producto.precio) || 0;
        const cantidad = normalizarCantidadPedido(producto.cantidad) || 1;
        const total = precio * cantidad;
        const claveCarrito = obtenerClaveCarrito(producto, idarticulo);
        const claveJs = escaparAtributoJs(claveCarrito);
        const inputId = `cantidad-${obtenerIdHtmlSeguro(claveCarrito)}`;
        const presentacion = escaparHTML(producto.presentacion || 'UNIDAD');

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="product-thumbnail">
                <a href="javascript:void(0)" class="remove" onclick="eliminarDelCarrito('${claveJs}')">
                    <i class='bx bx-x'></i>
                </a>
                <a href="#">
                    <img src="${imagen}" alt="${nombre}" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                </a>
            </td>
            <td class="product-name">
                <a href="producto.php?id=${idarticulo}">${nombre}</a>
                <span class="cart-product-presentation">${presentacion}</span>
            </td>
            <td class="product-price">
                <span class="unit-amount">Q${precio.toFixed(2)}</span>
            </td>
            <td class="product-quantity">
                <div class="input-counter">
                    <span class="minus-btn" onclick="cambiarCantidad('${claveJs}', -1)">
                        <i class='bx bx-minus'></i>
                    </span>
                    <input type="text" value="${cantidad}" id="${inputId}" inputmode="numeric" pattern="[0-9]*" maxlength="2"
                           onchange="actualizarCantidadInput('${claveJs}', this.value)">
                    <span class="plus-btn" onclick="cambiarCantidad('${claveJs}', 1)">
                        <i class='bx bx-plus'></i>
                    </span>
                </div>
            </td>
            <td class="product-subtotal">
                <span class="subtotal-amount">Q${total.toFixed(2)}</span>
            </td>
        `;

        tbody.appendChild(tr);
    });

    actualizarTotales();
}

// ============================================================
// ACTUALIZAR TOTALES DEL CARRITO
// ============================================================
function obtenerOpcionLugarEnvio() {
    const lugarEnvioInput = document.getElementById('lugarEnvio');
    if (!lugarEnvioInput || lugarEnvioInput.selectedIndex < 0) {
        return null;
    }

    return lugarEnvioInput.options[lugarEnvioInput.selectedIndex];
}

function obtenerTipoEntregaSeleccionado() {
    const opcionSeleccionada = obtenerOpcionLugarEnvio();
    return opcionSeleccionada
        ? String(opcionSeleccionada.getAttribute('data-delivery-type') || '')
        : '';
}

function esRecogeEnTienda() {
    return obtenerTipoEntregaSeleccionado() === TIPO_ENTREGA_RETIRO;
}

function obtenerDireccionRecogida() {
    const opcionSeleccionada = obtenerOpcionLugarEnvio();
    return opcionSeleccionada
        ? String(opcionSeleccionada.getAttribute('data-address') || '').trim()
        : '';
}

function obtenerCostoEnvioSeleccionado(subtotal = null) {
    if (subtotal !== null && subtotal <= 0) {
        return 0;
    }

    const opcionSeleccionada = obtenerOpcionLugarEnvio();
    const costoEnvio = opcionSeleccionada
        ? parseFloat(opcionSeleccionada.getAttribute('data-shipping') || '0')
        : 0;

    return Number.isFinite(costoEnvio) && costoEnvio >= 0 ? costoEnvio : 0;
}

function obtenerErrorLugarEnvio(lugarEnvioInput) {
    if (!lugarEnvioInput || !lugarEnvioInput.value) {
        return 'Seleccione el lugar de entrega para calcular el envio.';
    }

    const tipoEntrega = obtenerTipoEntregaSeleccionado();
    const costoEnvio = obtenerCostoEnvioSeleccionado();

    if (tipoEntrega === TIPO_ENTREGA_RETIRO) {
        return costoEnvio === 0 ? '' : 'La modalidad Recoge en tienda debe tener costo de envio Q0.00.';
    }

    if (tipoEntrega !== TIPO_ENTREGA_ENVIO || costoEnvio <= 0) {
        return 'Seleccione un lugar de entrega valido.';
    }

    return '';
}

function actualizarEstadoDireccionEntrega() {
    const direccionInput = document.getElementById('direccion');
    const direccionLabel = document.getElementById('direccionEntregaLabel');
    const pickupStoreInfo = document.getElementById('pickupStoreInfo');

    if (!direccionInput) {
        return;
    }

    if (esRecogeEnTienda()) {
        if (!direccionInput.readOnly && direccionInput.value.trim()) {
            direccionEntregaAnterior = direccionInput.value;
        }

        direccionInput.value = obtenerDireccionRecogida();
        direccionInput.readOnly = true;
        direccionInput.setAttribute('aria-readonly', 'true');
        aplicarEstadoValidacion(direccionInput, '');

        if (direccionLabel) {
            direccionLabel.innerHTML = 'Sucursal para recoger <span class="required">*</span>';
        }
        if (pickupStoreInfo) {
            pickupStoreInfo.hidden = false;
        }
        return;
    }

    if (direccionInput.readOnly) {
        direccionInput.value = direccionEntregaAnterior;
    }

    direccionInput.readOnly = false;
    direccionInput.removeAttribute('aria-readonly');

    if (direccionLabel) {
        direccionLabel.innerHTML = 'Direccion de entrega <span class="required">*</span>';
    }
    if (pickupStoreInfo) {
        pickupStoreInfo.hidden = true;
    }
}

function entregaEsDepartamentos() {
    const opcionSeleccionada = obtenerOpcionLugarEnvio();
    return !!opcionSeleccionada && opcionSeleccionada.value === 'Departamentos';
}

function construirComentarioEntrega(tipoEntrega, lugarEntrega) {
    if (tipoEntrega === TIPO_ENTREGA_RETIRO) {
        return `Tienda en linea | Entrega: Recoge en tienda | Sucursal: ${lugarEntrega}`;
    }

    return `Tienda en linea | Entrega a domicilio | Lugar: ${lugarEntrega}`;
}

function obtenerErrorFormaPago(formaPagoInput) {
    if (!formaPagoInput || !formaPagoInput.value) {
        return 'Seleccione el metodo de pago.';
    }

    if (!VALIDACION_PEDIDO.FORMAS_PAGO_PERMITIDAS.includes(formaPagoInput.value)) {
        return 'El metodo de pago seleccionado no es valido.';
    }

    if (formaPagoInput.value === 'Pago Contra Entrega' && entregaEsDepartamentos()) {
        return 'Pago Contra Entrega no esta disponible para envios a Departamentos.';
    }

    return '';
}

function actualizarVisibilidadPagoContraEntrega(formaPagoInput, ocultar) {
    if (!formaPagoInput) {
        return;
    }

    if (window.jQuery) {
        const $selectVisual = window.jQuery(formaPagoInput).next('.nice-select');
        $selectVisual.find('.option').filter(function () {
            return window.jQuery(this).data('value') === 'Pago Contra Entrega';
        }).toggle(!ocultar);
    }
}

function sincronizarFormaPagoPorLugarEnvio() {
    const formaPagoInput = document.getElementById('formaPago');
    if (!formaPagoInput) {
        return;
    }

    const ocultarPagoContraEntrega = entregaEsDepartamentos();
    const opcionPagoContraEntrega = Array.from(formaPagoInput.options).find(function (option) {
        return option.value === 'Pago Contra Entrega';
    });

    if (opcionPagoContraEntrega) {
        opcionPagoContraEntrega.disabled = ocultarPagoContraEntrega;
        opcionPagoContraEntrega.hidden = ocultarPagoContraEntrega;
    }

    if (ocultarPagoContraEntrega && formaPagoInput.value === 'Pago Contra Entrega') {
        formaPagoInput.value = '';
    }

    refrescarSelectorVisual(formaPagoInput);
    actualizarVisibilidadPagoContraEntrega(formaPagoInput, ocultarPagoContraEntrega);
    aplicarEstadoValidacion(formaPagoInput, formaPagoInput.value ? obtenerErrorFormaPago(formaPagoInput) : '');
    actualizarVistaPagoTarjeta();
}

function actualizarTotales(subtotal = null) {
    if (subtotal === null) {
        subtotal = Carrito.obtenerSubtotal();
    }

    const shipping = obtenerCostoEnvioSeleccionado(subtotal);
    const total = subtotal + shipping;

    const subtotalElement = document.querySelector('.cart-totals ul li:nth-child(1) span');
    const shippingElement = document.querySelector('.cart-totals ul li:nth-child(2) span');
    const totalElement = document.querySelector('.cart-totals ul li:nth-child(3) span');
    const payableElement = document.querySelector('.cart-totals ul li:nth-child(4) span');
    const orderTotalElement = document.getElementById('totalPagarPedido');

    if (subtotalElement) subtotalElement.textContent = `Q${subtotal.toFixed(2)}`;
    if (shippingElement) shippingElement.textContent = `Q${shipping.toFixed(2)}`;
    if (totalElement) totalElement.textContent = `Q${total.toFixed(2)}`;
    if (payableElement) payableElement.textContent = `Q${total.toFixed(2)}`;
    if (orderTotalElement) orderTotalElement.textContent = `Q${total.toFixed(2)}`;
}

// ============================================================
// ELIMINAR PRODUCTO DEL CARRITO
// ============================================================
function eliminarDelCarrito(idarticulo) {
    if (confirm('¿Estás seguro de que deseas eliminar este producto del carrito?')) {
        Carrito.eliminarProducto(idarticulo);
        cargarCarrito();
    }
}

// ============================================================
// CAMBIAR CANTIDAD DE PRODUCTO
// ============================================================
function cambiarCantidad(idarticulo, cambio) {
    const carrito = Carrito.obtenerCarrito();
    const producto = carrito.find(item => {
        if (window.Carrito && typeof Carrito.itemCoincideConClave === 'function') {
            return Carrito.itemCoincideConClave(item, idarticulo);
        }

        return item.idarticulo == idarticulo;
    });

    if (producto) {
        const cantidadActual = normalizarCantidadPedido(producto.cantidad) || 1;
        const nuevaCantidad = cantidadActual + cambio;

        if (nuevaCantidad < 1) {
            eliminarDelCarrito(idarticulo);
        } else if (nuevaCantidad > CANTIDAD_MAXIMA_CARRITO) {
            mostrarErrorFormulario(`La cantidad maxima por producto es ${CANTIDAD_MAXIMA_CARRITO}.`);
        } else {
            Carrito.actualizarCantidad(idarticulo, nuevaCantidad);
            cargarCarrito();
        }
    }
}

// ============================================================
// ACTUALIZAR CANTIDAD DESDE INPUT
// ============================================================
function actualizarCantidadInput(idarticulo, valor) {
    const cantidad = normalizarCantidadPedido(valor);
    if (cantidad === null) {
        mostrarErrorFormulario(`Ingrese una cantidad entera entre 1 y ${CANTIDAD_MAXIMA_CARRITO}.`);
        cargarCarrito();
        return;
    }

    const carrito = Carrito.obtenerCarrito();
    const producto = carrito.find(item => {
        if (window.Carrito && typeof Carrito.itemCoincideConClave === 'function') {
            return Carrito.itemCoincideConClave(item, idarticulo);
        }

        return item.idarticulo == idarticulo;
    });
    Carrito.actualizarCantidad(idarticulo, cantidad);
    cargarCarrito();
}

// ============================================================
// INICIALIZAR EVENTOS
// ============================================================
function inicializarEventos() {
    // Evento para el botón "Update Cart"
    const updateBtn = document.querySelector('.cart-buttons .default-btn');
    if (updateBtn) {
        updateBtn.addEventListener('click', function (e) {
            e.preventDefault();
            cargarCarrito();
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Carrito actualizado',
                    showConfirmButton: false,
                    timer: 1500
                });
            }
        });
    }
}

const VALIDACION_PEDIDO = {
    NOMBRE_REGEX: /^(?=.{3,80}$)[A-Za-zÀ-ÖØ-öø-ÿÑñ][A-Za-zÀ-ÖØ-öø-ÿÑñ\s]*$/,
    DIRECCION_REGEX: /^(?=.{8,250}$)[A-Za-z0-9À-ÖØ-öø-ÿÑñ][A-Za-z0-9À-ÖØ-öø-ÿÑñ\s#.,\/-]*$/,
    TELEFONO_REGEX: /^\d{8,15}$/,
    EMAIL_REGEX: /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,24}$/,
    NIT_REGEX: /^\d{1,12}$/,
    DPI_REGEX: /^\d{13}$/,
    FORMAS_PAGO_PERMITIDAS: ['Pago Contra Entrega', 'Tarjeta', 'Transferencia'],
    TIPOS_DOCUMENTO_PERMITIDOS: ['nit', 'dpi'],
    CANTIDAD_MAXIMA: CANTIDAD_MAXIMA_CARRITO
};

function normalizarTextoSeguro(str) {
    if (typeof str !== 'string') return '';

    let valor = str;
    if (typeof valor.normalize === 'function') {
        valor = valor.normalize('NFKC');
    }

    return valor
        .replace(/[\u0000-\u001F\u007F-\u009F]/g, '')
        .replace(/[\u200B-\u200D\uFEFF]/g, '');
}

function compactarEspacios(str) {
    return normalizarTextoSeguro(str)
        .replace(/\s+/g, ' ')
        .trim();
}

function limpiarNombreInput(str) {
    return normalizarTextoSeguro(str)
        .replace(/[^A-Za-zÀ-ÖØ-öø-ÿÑñ\s.'-]/g, '')
        .replace(/\s{2,}/g, ' ')
        .replace(/^\s+/, '')
        .slice(0, 80);
}

function limpiarDireccionInput(str) {
    return normalizarTextoSeguro(str)
        .replace(/[^A-Za-z0-9À-ÖØ-öø-ÿÑñ\s#.,\/-]/g, '')
        .replace(/\s{2,}/g, ' ')
        .replace(/^\s+/, '')
        .slice(0, 250);
}

function limpiarTelefonoInput(str) {
    return normalizarTextoSeguro(str)
        .replace(/\D/g, '')
        .slice(0, 15);
}

function limpiarCorreoInput(str) {
    return normalizarTextoSeguro(str)
        .replace(/[\s<>"`;]+/g, '')
        .slice(0, 120)
        .toLowerCase();
}

function limpiarDocumentoInput(str, tipoDocumento = '') {
    const maxLength = tipoDocumento === 'nit' ? 12 : 13;
    return normalizarTextoSeguro(str)
        .replace(/\D/g, '')
        .slice(0, maxLength);
}

function sanitizarInput(str) {
    return compactarEspacios(normalizarTextoSeguro(str).replace(/[<>"`;{}[\]|\\]/g, ''));
}

function obtenerErrorNombre(nombre) {
    if (!nombre) return 'El nombre completo es obligatorio';
    if (!VALIDACION_PEDIDO.NOMBRE_REGEX.test(nombre)) {
        return 'Ingrese un nombre valido usando solo letras y espacios.';
    }
    return '';
}

function obtenerErrorDireccion(direccion) {
    if (!direccion) return 'La direccion es obligatoria';
    if (!VALIDACION_PEDIDO.DIRECCION_REGEX.test(direccion)) {
        return 'La direccion debe tener entre 8 y 250 caracteres y solo usar letras, numeros y signos permitidos.';
    }
    return '';
}

function obtenerErrorTelefono(telefono) {
    if (!telefono) return 'El telefono es obligatorio para contactarte';
    if (!VALIDACION_PEDIDO.TELEFONO_REGEX.test(telefono)) {
        return 'El telefono debe contener solo numeros y tener entre 8 y 15 digitos.';
    }
    return '';
}

function obtenerErrorCorreo(correo) {
    if (!correo) return 'El correo electronico es obligatorio para enviar tu referencia.';
    if (!VALIDACION_PEDIDO.EMAIL_REGEX.test(correo)) {
        return 'Por favor ingrese un correo electronico valido';
    }
    return '';
}

function obtenerErrorDocumento(tipoDocumento, numeroDocumento) {
    if (!VALIDACION_PEDIDO.TIPOS_DOCUMENTO_PERMITIDOS.includes(tipoDocumento)) {
        return 'Seleccione un tipo de documento valido';
    }

    if (!numeroDocumento) {
        return tipoDocumento === 'dpi'
            ? 'Por favor ingrese el DPI para la factura'
            : 'Por favor ingrese el NIT para la factura';
    }

    if (tipoDocumento === 'dpi' && !VALIDACION_PEDIDO.DPI_REGEX.test(numeroDocumento)) {
        return 'El DPI debe tener exactamente 13 caracteres';
    }

    if (tipoDocumento === 'nit' && !VALIDACION_PEDIDO.NIT_REGEX.test(numeroDocumento)) {
        return 'El NIT debe tener entre 1 y 12 digitos';
    }

    return '';
}

function aplicarEstadoValidacion(input, mensaje) {
    if (!input) return;
    input.setCustomValidity(mensaje || '');
}

function mostrarErrorFormulario(mensaje, inputId = '') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: mensaje,
            confirmButtonText: 'Aceptar'
        });
    } else {
        alert(mensaje);
    }

    if (inputId) {
        const input = document.getElementById(inputId);
        if (input) {
            input.focus();
            if (typeof input.reportValidity === 'function') {
                input.reportValidity();
            }
        }
    }
}

function actualizarVistaPagoTarjeta() {
    const formaPagoInput = document.getElementById('formaPago');
    const pasarelaInfo = document.getElementById('pasarelaTarjetaInfo');
    const btnEnviar = document.querySelector('.mp-btn-enviar');
    const esTarjeta = formaPagoInput && formaPagoInput.value === 'Tarjeta';

    if (pasarelaInfo) {
        pasarelaInfo.hidden = !esTarjeta;
    }

    if (btnEnviar) {
        btnEnviar.textContent = esTarjeta ? 'Continuar a pago seguro' : 'Enviar Pedido';
    }

    if (esTarjeta) {
        publicarDeviceFingerprint();
    }
}

function refrescarSelectorVisual(selectElement) {
    if (selectElement && window.jQuery && typeof window.jQuery.fn.niceSelect === 'function') {
        window.jQuery(selectElement).niceSelect('update');
    }
}

function inicializarRestriccionesFormularioPedido() {
    const nombreInput = document.getElementById('nombreCompleto');
    const direccionInput = document.getElementById('direccion');
    const telefonoInput = document.getElementById('telefono');
    const correoInput = document.getElementById('correo');
    const lugarEnvioInput = document.getElementById('lugarEnvio');
    const formaPagoInput = document.getElementById('formaPago');
    const tipoDocumentoInput = document.getElementById('tipoDocumento');
    const numeroDocumentoInput = document.getElementById('numeroDocumento');

    if (nombreInput) {
        nombreInput.addEventListener('input', function () {
            this.value = limpiarNombreInput(this.value);
            aplicarEstadoValidacion(this, this.value ? obtenerErrorNombre(sanitizarInput(this.value)) : '');
        });
    }

    if (direccionInput) {
        direccionInput.addEventListener('input', function () {
            this.value = limpiarDireccionInput(this.value);
            aplicarEstadoValidacion(this, this.value ? obtenerErrorDireccion(sanitizarInput(this.value)) : '');
        });
    }

    if (telefonoInput) {
        telefonoInput.addEventListener('input', function () {
            this.value = limpiarTelefonoInput(this.value);
            aplicarEstadoValidacion(this, this.value ? obtenerErrorTelefono(this.value) : '');
        });
    }

    if (correoInput) {
        correoInput.addEventListener('input', function () {
            this.value = limpiarCorreoInput(this.value);
            aplicarEstadoValidacion(this, obtenerErrorCorreo(this.value));
        });
    }

    if (lugarEnvioInput) {
        const sincronizarTotalesEntrega = function () {
            actualizarEstadoDireccionEntrega();
            aplicarEstadoValidacion(lugarEnvioInput, obtenerErrorLugarEnvio(lugarEnvioInput));
            sincronizarFormaPagoPorLugarEnvio();
            actualizarTotales();
        };

        lugarEnvioInput.addEventListener('change', sincronizarTotalesEntrega);

        if (window.jQuery) {
            window.jQuery(lugarEnvioInput).off('change.lmShippingTotals').on('change.lmShippingTotals', sincronizarTotalesEntrega);
        }
    }

    if (formaPagoInput) {
        formaPagoInput.addEventListener('change', function () {
            const errorFormaPago = obtenerErrorFormaPago(this);
            if (errorFormaPago && this.value) {
                this.value = '';
                refrescarSelectorVisual(this);
            }
            aplicarEstadoValidacion(this, this.value ? obtenerErrorFormaPago(this) : '');
            actualizarVistaPagoTarjeta();
        });
    }

    if (tipoDocumentoInput) {
        tipoDocumentoInput.addEventListener('change', function () {
            aplicarEstadoValidacion(this, this.value && !VALIDACION_PEDIDO.TIPOS_DOCUMENTO_PERMITIDOS.includes(this.value)
                ? 'Seleccione un tipo de documento valido'
                : '');
        });
    }

    if (numeroDocumentoInput) {
        numeroDocumentoInput.addEventListener('input', function () {
            const tipoDocumentoElement = document.getElementById('tipoDocumento');
            const tipoDocumento = tipoDocumentoElement ? tipoDocumentoElement.value : '';
            this.value = limpiarDocumentoInput(this.value, tipoDocumento);
            aplicarEstadoValidacion(this, tipoDocumento ? obtenerErrorDocumento(tipoDocumento, this.value) : '');
        });
    }
}

// ============================================================
// MODAL DE PEDIDO
// ============================================================

// Abrir modal de pedido
function abrirModalPedido() {
    // Verificar que el carrito no esté vacío
    const carrito = Carrito.obtenerCarrito();
    if (carrito.length === 0) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Carrito vacío',
                text: 'Agrega productos al carrito antes de continuar',
                confirmButtonText: 'Aceptar'
            });
        } else {
            alert('El carrito está vacío. Agrega productos antes de continuar.');
        }
        return;
    }

    // Limpiar formulario
    document.getElementById('formPedido').reset();
    direccionEntregaAnterior = '';
    document.getElementById('nit-group').style.display = 'none';
    document.getElementById('numeroDocumento-group').style.display = 'none';
    document.getElementById('documentoHelp').textContent = '';
    reiniciarDeviceFingerprint();
    aplicarEstadoValidacion(document.getElementById('nombreCompleto'), '');
    aplicarEstadoValidacion(document.getElementById('direccion'), '');
    aplicarEstadoValidacion(document.getElementById('lugarEnvio'), '');
    aplicarEstadoValidacion(document.getElementById('telefono'), '');
    aplicarEstadoValidacion(document.getElementById('correo'), '');
    aplicarEstadoValidacion(document.getElementById('tipoDocumento'), '');
    aplicarEstadoValidacion(document.getElementById('numeroDocumento'), '');

    // Dejar el metodo de pago pendiente para que el cliente lo seleccione.
    document.getElementById('formaPago').value = '';
    refrescarSelectorVisual(document.getElementById('lugarEnvio'));
    actualizarEstadoDireccionEntrega();
    sincronizarFormaPagoPorLugarEnvio();
    actualizarTotales();
    actualizarVistaPagoTarjeta();

    // Mostrar modal
    document.getElementById('modalPedido').classList.add('show');
}

// Cerrar modal de pedido
function cerrarModalPedido() {
    document.getElementById('modalPedido').classList.remove('show');
}

// Mostrar/ocultar campo de documento según checkbox
function toggleNit() {
    const checkbox = document.getElementById('necesitaFactura');
    if (!checkbox) return;

    const nitGroup = document.getElementById('nit-group');
    const numeroDocumentoGroup = document.getElementById('numeroDocumento-group');
    const tipoDocumento = document.getElementById('tipoDocumento');
    const numeroDocumento = document.getElementById('numeroDocumento');
    const documentoHelp = document.getElementById('documentoHelp');

    if (!nitGroup || !numeroDocumentoGroup || !tipoDocumento || !numeroDocumento) return;

    if (checkbox.checked) {
        nitGroup.style.display = 'block';
        tipoDocumento.setAttribute('required', 'required');
    } else {
        nitGroup.style.display = 'none';
        numeroDocumentoGroup.style.display = 'none';
        tipoDocumento.removeAttribute('required');
        numeroDocumento.removeAttribute('required');
        aplicarEstadoValidacion(tipoDocumento, '');
        aplicarEstadoValidacion(numeroDocumento, '');
        tipoDocumento.value = '';
        numeroDocumento.value = '';
        if (documentoHelp) {
            documentoHelp.textContent = '';
        }
    }
}

// Cambiar tipo de documento (NIT o DPI)
function cambiarTipoDocumento() {
    const tipoDocumento = document.getElementById('tipoDocumento').value;
    const numeroDocumentoGroup = document.getElementById('numeroDocumento-group');
    const labelNumeroDocumento = document.getElementById('labelNumeroDocumento');
    const numeroDocumento = document.getElementById('numeroDocumento');
    const documentoHelp = document.getElementById('documentoHelp');

    if (tipoDocumento === '') {
        numeroDocumentoGroup.style.display = 'none';
        numeroDocumento.removeAttribute('required');
        numeroDocumento.value = '';
        numeroDocumento.setAttribute('pattern', '[0-9]{1,13}');
        numeroDocumento.removeAttribute('minlength');
        aplicarEstadoValidacion(numeroDocumento, '');
        return;
    }

    numeroDocumentoGroup.style.display = 'block';
    numeroDocumento.setAttribute('required', 'required');
    numeroDocumento.value = '';

    if (tipoDocumento === 'nit') {
        labelNumeroDocumento.innerHTML = 'NIT <span class="required">*</span>';
        numeroDocumento.setAttribute('maxlength', '12');
        numeroDocumento.setAttribute('pattern', '[0-9]{1,12}');
        numeroDocumento.setAttribute('minlength', '1');
        documentoHelp.textContent = 'El NIT debe tener menos de 13 caracteres';
        documentoHelp.style.color = '#666';
    } else if (tipoDocumento === 'dpi') {
        labelNumeroDocumento.innerHTML = 'DPI <span class="required">*</span>';
        numeroDocumento.setAttribute('maxlength', '13');
        numeroDocumento.setAttribute('pattern', '[0-9]{13}');
        numeroDocumento.setAttribute('minlength', '13');
        documentoHelp.textContent = 'El DPI debe tener 13 caracteres';
        documentoHelp.style.color = '#666';
    } else {
        documentoHelp.textContent = 'Seleccione un tipo de documento valido';
        documentoHelp.style.color = '#B73639';
        aplicarEstadoValidacion(numeroDocumento, 'Seleccione un tipo de documento valido');
        return;
    }

    validarDocumento();
}

// Validar el documento según el tipo
function validarDocumento() {
    const tipoDocumento = document.getElementById('tipoDocumento').value;
    const numeroDocumentoInput = document.getElementById('numeroDocumento');
    const documentoHelp = document.getElementById('documentoHelp');

    numeroDocumentoInput.value = limpiarDocumentoInput(numeroDocumentoInput.value, tipoDocumento);
    const numeroDocumentoLimpiado = numeroDocumentoInput.value.trim();

    if (!tipoDocumento || !numeroDocumentoLimpiado) {
        if (numeroDocumentoLimpiado.length === 0) {
            documentoHelp.textContent = '';
        }
        aplicarEstadoValidacion(numeroDocumentoInput, '');
        return;
    }

    if (tipoDocumento === 'dpi') {
        if (numeroDocumentoLimpiado.length < 13) {
            documentoHelp.textContent = `El DPI debe tener 13 caracteres (${numeroDocumentoLimpiado.length}/13)`;
            documentoHelp.style.color = '#B73639';
            aplicarEstadoValidacion(numeroDocumentoInput, 'El DPI debe tener exactamente 13 caracteres');
        } else if (numeroDocumentoLimpiado.length === 13) {
            documentoHelp.textContent = 'DPI valido';
            documentoHelp.style.color = '#166B38';
            aplicarEstadoValidacion(numeroDocumentoInput, '');
        } else {
            documentoHelp.textContent = 'El DPI debe tener exactamente 13 caracteres';
            documentoHelp.style.color = '#B73639';
            aplicarEstadoValidacion(numeroDocumentoInput, 'El DPI debe tener exactamente 13 caracteres');
        }
    } else if (tipoDocumento === 'nit') {
        if (numeroDocumentoLimpiado.length >= 13) {
            documentoHelp.textContent = 'El NIT debe tener menos de 13 caracteres';
            documentoHelp.style.color = '#B73639';
            aplicarEstadoValidacion(numeroDocumentoInput, 'El NIT debe tener entre 1 y 12 digitos');
        } else if (numeroDocumentoLimpiado.length === 0) {
            documentoHelp.textContent = 'El NIT debe tener menos de 13 caracteres';
            documentoHelp.style.color = '#666';
            aplicarEstadoValidacion(numeroDocumentoInput, 'Por favor ingrese el NIT para la factura');
        } else {
            documentoHelp.textContent = 'NIT valido';
            documentoHelp.style.color = '#166B38';
            aplicarEstadoValidacion(numeroDocumentoInput, '');
        }
    }
}

// Validar formato de correo electronico
function validarCorreo(correo) {
    if (!correo) return true;
    return VALIDACION_PEDIDO.EMAIL_REGEX.test(correo);
}

// Procesar pedido
function procesarPedido(event) {
    event.preventDefault();

    const nombreInput = document.getElementById('nombreCompleto');
    const direccionInput = document.getElementById('direccion');
    const telefonoInput = document.getElementById('telefono');
    const correoInput = document.getElementById('correo');
    const lugarEnvioInput = document.getElementById('lugarEnvio');
    const tipoDocumentoInput = document.getElementById('tipoDocumento');
    const numeroDocumentoInput = document.getElementById('numeroDocumento');
    const formaPagoInput = document.getElementById('formaPago');
    actualizarEstadoDireccionEntrega();

    // Obtener y sanitizar datos del formulario
    const nombreCompleto = sanitizarInput(limpiarNombreInput(nombreInput.value));
    const direccion = sanitizarInput(limpiarDireccionInput(direccionInput.value));
    const telefono = limpiarTelefonoInput(telefonoInput.value);
    const correo = limpiarCorreoInput(correoInput.value);
    const necesitaFactura = document.getElementById('necesitaFactura').checked;
    const tipoDocumento = tipoDocumentoInput.value;
    const numeroDocumento = limpiarDocumentoInput(numeroDocumentoInput.value, tipoDocumento);
    const formaPago = formaPagoInput.value;
    const modalidadEntrega = obtenerTipoEntregaSeleccionado();
    const lugarEntrega = lugarEnvioInput.value;

    nombreInput.value = nombreCompleto;
    direccionInput.value = direccion;
    telefonoInput.value = telefono;
    correoInput.value = correo;
    numeroDocumentoInput.value = numeroDocumento;

    const errorNombre = obtenerErrorNombre(nombreCompleto);
    if (errorNombre) {
        aplicarEstadoValidacion(nombreInput, errorNombre);
        mostrarErrorFormulario(errorNombre, 'nombreCompleto');
        return;
    }
    aplicarEstadoValidacion(nombreInput, '');

    const errorDireccion = obtenerErrorDireccion(direccion);
    if (errorDireccion) {
        aplicarEstadoValidacion(direccionInput, errorDireccion);
        mostrarErrorFormulario(errorDireccion, 'direccion');
        return;
    }
    aplicarEstadoValidacion(direccionInput, '');

    const errorTelefono = obtenerErrorTelefono(telefono);
    if (errorTelefono) {
        aplicarEstadoValidacion(telefonoInput, errorTelefono);
        mostrarErrorFormulario(errorTelefono, 'telefono');
        return;
    }
    aplicarEstadoValidacion(telefonoInput, '');

    const errorCorreo = obtenerErrorCorreo(correo);
    if (errorCorreo || (correo && !validarCorreo(correo))) {
        aplicarEstadoValidacion(correoInput, errorCorreo || 'Por favor ingrese un correo electronico valido');
        mostrarErrorFormulario(errorCorreo || 'Por favor ingrese un correo electronico valido', 'correo');
        return;
    }
    aplicarEstadoValidacion(correoInput, '');

    const errorLugarEnvio = obtenerErrorLugarEnvio(lugarEnvioInput);
    if (errorLugarEnvio) {
        aplicarEstadoValidacion(lugarEnvioInput, errorLugarEnvio);
        mostrarErrorFormulario(errorLugarEnvio, 'lugarEnvio');
        return;
    }
    aplicarEstadoValidacion(lugarEnvioInput, '');

    const errorFormaPago = obtenerErrorFormaPago(formaPagoInput);
    if (errorFormaPago) {
        aplicarEstadoValidacion(formaPagoInput, errorFormaPago);
        mostrarErrorFormulario(errorFormaPago, 'formaPago');
        return;
    }
    aplicarEstadoValidacion(formaPagoInput, '');

    // Si no necesita factura, usar "C/F" como NIT
    let nitFinal = '';
    let tipoDocumentoFinal = '';

    if (necesitaFactura) {
        tipoDocumentoFinal = tipoDocumento === 'nit' ? 'NIT' : 'DPI';
        nitFinal = numeroDocumento;
    } else {
        // Si no necesita factura, usar "C/F"
        tipoDocumentoFinal = 'NIT';
        nitFinal = 'C/F';
    }

    const subtotalPedido = Carrito.obtenerSubtotal();
    const shippingPedido = obtenerCostoEnvioSeleccionado(subtotalPedido);

    const formData = {
        nombreCompleto: nombreCompleto,
        direccion: direccion,
        telefono: telefono || '', // Opcional
        correo: correo,
        necesitaFactura: necesitaFactura,
        tipoDocumento: tipoDocumentoFinal,
        numeroDocumento: nitFinal,
        formaPago: formaPago,
        modalidadEntrega: modalidadEntrega,
        lugarEntrega: lugarEntrega,
        carrito: Carrito.obtenerCarrito(),
        subtotal: subtotalPedido,
        shipping: shippingPedido,
        total: subtotalPedido + shippingPedido
    };

    // Validar documento si necesita factura
    if (necesitaFactura) {
        if (!tipoDocumento) {
            aplicarEstadoValidacion(tipoDocumentoInput, 'Seleccione el tipo de documento (NIT o DPI)');
            mostrarErrorFormulario('Por favor seleccione el tipo de documento (NIT o DPI)', 'tipoDocumento');
            return;
        }

        const errorDocumento = obtenerErrorDocumento(tipoDocumento, numeroDocumento);
        if (errorDocumento) {
            aplicarEstadoValidacion(numeroDocumentoInput, errorDocumento);
            mostrarErrorFormulario(errorDocumento, 'numeroDocumento');
            return;
        }

        aplicarEstadoValidacion(tipoDocumentoInput, '');
        aplicarEstadoValidacion(numeroDocumentoInput, '');
    }

    // Validar carrito antes de procesar
    const validacion = Carrito.validarCarritoParaEnvio();
    if (!validacion.valido) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error de validación',
                text: validacion.mensaje,
                confirmButtonText: 'Aceptar'
            });
        } else {
            alert(validacion.mensaje);
        }
        return;
    }

    // Obtener carrito (ya validado)
    const carrito = Carrito.obtenerCarrito();
    const subtotal = formData.subtotal;
    const total = formData.total;

    // Construir array de artículos
    const idarticulo = [];
    const cantidad = [];
    const precio_venta = [];
    const subtotal1 = [];
    const subtotaldes1 = [];
    const presen = [];
    const tipo_presentacion = [];
    const cantidadpresentacion = [];
    const stock_validado = [];
    const subtotal_presentacion = [];
    const requiere_reserva = [];

    carrito.forEach(producto => {
        idarticulo.push(parseInt(producto.idarticulo));
        const cantidadProducto = normalizarCantidadPedido(producto.cantidad) || 1;
        cantidad.push(cantidadProducto);
        const precio = parseFloat(producto.precio || 0);
        precio_venta.push(precio);
        const subtotalItem = precio * cantidadProducto;
        subtotal1.push(subtotalItem);
        subtotaldes1.push(subtotalItem);
        presen.push(producto.presentacion || 'UNIDAD');
        tipo_presentacion.push(producto.tipo_presentacion || 'unidad');
        cantidadpresentacion.push(parseInt(producto.cantidadpresentacion || 1, 10) || 1);
        const stockProducto = obtenerStockPresentacionCarrito(producto);
        stock_validado.push(stockProducto);
        requiere_reserva.push(!!producto.requiere_reserva || (stockProducto !== null && cantidadProducto > stockProducto));
        subtotal_presentacion.push(subtotalItem);
    });

    // Construir objeto para la API (con datos sanitizados)
    const apiData = {
        nit: formData.numeroDocumento, // Ya incluye "C/F" si no necesita factura
        nombre_cliente: formData.nombreCompleto,
        telefono_cliente: formData.telefono, // Obligatorio
        direccion_cliente: formData.direccion,
        correo_cliente: formData.correo,
        tipo_documento_cliente: formData.tipoDocumento, // Ya incluye "NIT" si no necesita factura
        forma_pago: formData.formaPago,
        total_venta: total,
        total_ventades: subtotal,
        idvendedor: 0,
        tipo_cliente: "Cliente",
        forma_productos: "Detallado",
        comentario_cotizacion: construirComentarioEntrega(formData.modalidadEntrega, formData.lugarEntrega),
        destino: "VENTA",
        no_auto_tarjeta: "",
        envio: formData.shipping,
        modalidad_entrega: formData.modalidadEntrega,
        lugar_entrega: formData.lugarEntrega,
        presen: presen,
        cantidadpresentacion: cantidadpresentacion,
        requiere_reserva: requiere_reserva,
        presentaciones: carrito.map((producto, index) => ({
            idarticulo: idarticulo[index],
            presentacion: presen[index],
            tipo_presentacion: tipo_presentacion[index],
            precio_presentacion: precio_venta[index],
            cantidad: cantidad[index],
            stock_validado: stock_validado[index],
            requiere_reserva: requiere_reserva[index],
            subtotal_presentacion: subtotal_presentacion[index]
        })),
        datosArticulos: {
            articulos: {
                idarticulo: idarticulo,
                cantidad: cantidad,
                precio_venta: precio_venta,
                subtotal1: subtotal1,
                subtotaldes1: subtotaldes1,
                presen: presen,
                presentacion: presen,
                tipo_presentacion: tipo_presentacion,
                cantidadpresentacion: cantidadpresentacion,
                precio_presentacion: precio_venta,
                stock_validado: stock_validado,
                requiere_reserva: requiere_reserva,
                subtotal_presentacion: subtotal_presentacion
            }
        }
    };

    // Deshabilitar botón de enviar
    const btnEnviar = document.querySelector('.mp-btn-enviar');

    if (btnEnviar) {
        btnEnviar.disabled = true;
        btnEnviar.textContent = formData.formaPago === 'Tarjeta'
            ? 'Redirigiendo...'
            : 'Enviando...';
    }

    // ⚠️ IMPORTANTE: El servidor (API) DEBE validar los precios contra la base de datos
    // antes de procesar el pedido. Esta validación del cliente es solo una capa de protección.
    // El servidor debe:
    // 1. Verificar que cada idarticulo existe
    // 2. Validar que los precios coinciden con los precios en la base de datos
    // 3. Verificar que las cantidades son válidas
    // 4. Recalcular el total basándose en los precios del servidor, no del cliente

    if (formData.formaPago === 'Tarjeta') {
        iniciarPagoTarjeta(apiData, btnEnviar);
        return;
    }

    const csrfToken = document.getElementById('checkoutCsrfToken')?.value || '';
    const orderApiData = Object.assign({}, apiData, {
        csrf_token: csrfToken,
        website: document.getElementById('checkoutWebsite')?.value || ''
    });

    // Validar importes y modalidad en el backend local antes de reenviar.
    fetch('/api/orders/create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify(orderApiData)
    })
        .then(response => {
            // Verificar si la respuesta es OK
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status}`);
            }

            // Intentar parsear como JSON
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', text);
                    throw new Error('La respuesta del servidor no es válida');
                }
            });
        })
        .then(data => {
            if (data.success) {
                // Construir mensaje con el número de cotización
                const numeroCotizacion = data.idcotizacion || 'N/A';
                const mensajeExito = formData.modalidadEntrega === TIPO_ENTREGA_RETIRO
                    ? `Cotizacion #${numeroCotizacion}. Te contactaremos cuando el pedido este listo para recoger.`
                    : `Cotizacion #${numeroCotizacion}`;

                // Limpiar carrito inmediatamente
                Carrito.limpiarCarrito();

                // Cerrar modal
                cerrarModalPedido();

                // Mostrar mensaje de éxito
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Pedido enviado con éxito!',
                        text: mensajeExito,
                        confirmButtonText: 'Aceptar',
                        allowOutsideClick: false,
                        allowEscapeKey: false
                    }).then(() => {
                        // Redirigir a index.php
                        window.location.href = 'index.php';
                    });
                } else {
                    alert(`¡Pedido enviado con éxito! ${mensajeExito}`);
                    // Redirigir a index.php
                    window.location.href = 'index.php';
                }
            } else {
                // Error en la respuesta de la API
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al procesar el pedido. Por favor intenta nuevamente.',
                        confirmButtonText: 'Aceptar'
                    }).then(() => {
                        // Habilitar botón de enviar nuevamente
                        if (btnEnviar) {
                            btnEnviar.disabled = false;
                            actualizarVistaPagoTarjeta();
                        }
                    });
                } else {
                    alert(data.message || 'Error al procesar el pedido. Por favor intenta nuevamente.');
                    // Habilitar botón de enviar nuevamente
                    if (btnEnviar) {
                        btnEnviar.disabled = false;
                        actualizarVistaPagoTarjeta();
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error completo:', error);

            // Mostrar error
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: error.message || 'No se pudo conectar con el servidor. Por favor intenta nuevamente.',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    // Habilitar botón de enviar nuevamente
                    if (btnEnviar) {
                        btnEnviar.disabled = false;
                        actualizarVistaPagoTarjeta();
                    }
                });
            } else {
                alert(error.message || 'No se pudo conectar con el servidor. Por favor intenta nuevamente.');
                // Habilitar botón de enviar nuevamente
                if (btnEnviar) {
                    btnEnviar.disabled = false;
                    actualizarVistaPagoTarjeta();
                }
            }
        });
}

// Cerrar modal al hacer clic fuera de él
document.addEventListener('DOMContentLoaded', function () {
    const modalPedido = document.getElementById('modalPedido');
    if (modalPedido) {
        modalPedido.addEventListener('click', function (e) {
            if (e.target === modalPedido) {
                cerrarModalPedido();
            }
        });
    }
});

function obtenerConfiguracionVentanaPago() {
    const width = Math.min(760, Math.max(420, window.screen.availWidth - 80));
    const height = Math.min(820, Math.max(620, window.screen.availHeight - 80));
    const left = Math.max(0, Math.round((window.screen.availWidth - width) / 2));
    const top = Math.max(0, Math.round((window.screen.availHeight - height) / 2));

    return [
        `width=${width}`,
        `height=${height}`,
        `left=${left}`,
        `top=${top}`,
        'resizable=yes',
        'scrollbars=yes',
        'status=yes',
        'menubar=no',
        'toolbar=no',
        'location=yes'
    ].join(',');
}

function escribirCargaVentanaPago(ventanaPago) {
    if (!ventanaPago || ventanaPago.closed) {
        return;
    }

    const logoUrl = new URL('assets/img/LogoLibreriaMarquense.jpeg', window.location.href).href;

    try {
        ventanaPago.document.open();
        ventanaPago.document.write(`
            <!doctype html>
            <html lang="es">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>Conectando con la pasarela</title>
                <style>
                    body {
                        margin: 0;
                        min-height: 100vh;
                        display: grid;
                        place-items: center;
                        background: #ffffff;
                        font-family: Arial, sans-serif;
                    }

                    .payment-loader {
                        display: grid;
                        place-items: center;
                        gap: 18px;
                    }

                    .payment-loader img {
                        width: min(280px, 70vw);
                        border-radius: 10px;
                    }

                    .payment-loader span {
                        width: 44px;
                        height: 44px;
                        border: 4px solid #e5e7eb;
                        border-top-color: #1A2697;
                        border-radius: 50%;
                        animation: spin .8s linear infinite;
                    }

                    @keyframes spin {
                        to { transform: rotate(360deg); }
                    }
                </style>
            </head>
            <body>
                <main class="payment-loader" aria-label="Cargando pasarela de pago">
                    <img src="${logoUrl}" alt="Libreria Marquense">
                    <span aria-hidden="true"></span>
                </main>
            </body>
            </html>
        `);
        ventanaPago.document.close();
        ventanaPago.focus();
    } catch (error) {
        console.warn('No se pudo escribir el cargador de pago:', error);
    }
}

function abrirVentanaPagoExterna() {
    const ventanaPago = window.open('', '_blank', obtenerConfiguracionVentanaPago());

    if (!ventanaPago || ventanaPago.closed || typeof ventanaPago.closed === 'undefined') {
        return null;
    }

    escribirCargaVentanaPago(ventanaPago);
    return ventanaPago;
}

function cerrarVentanaPagoExterna(ventanaPago) {
    try {
        if (ventanaPago && !ventanaPago.closed) {
            ventanaPago.close();
        }
    } catch (error) {
        console.warn('No se pudo cerrar la ventana de pago:', error);
    }
}

function restaurarBotonPagoTarjeta(btnEnviar) {
    if (btnEnviar) {
        btnEnviar.disabled = false;
        actualizarVistaPagoTarjeta();
    }
}

function iniciarPagoTarjeta(apiData, btnEnviar) {
    const ventanaPago = abrirVentanaPagoExterna();
    const csrfToken = document.getElementById('checkoutCsrfToken')?.value || '';

    if (!ventanaPago) {
        restaurarBotonPagoTarjeta(btnEnviar);

        const mensaje = 'Chrome bloqueo la ventana externa de pago. Permite ventanas emergentes para libreriamarquense.com e intenta nuevamente.';
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: 'Ventana de pago bloqueada',
                text: mensaje,
                confirmButtonText: 'Aceptar'
            });
        } else {
            alert(mensaje);
        }

        return;
    }

    prepararDeviceFingerprintParaPago()
        .then(function (deviceFingerprintId) {
            const secureApiData = Object.assign({}, apiData, {
                csrf_token: csrfToken,
                website: document.getElementById('checkoutWebsite')?.value || ''
            });

            if (deviceFingerprintId) {
                secureApiData.deviceFingerprintID = deviceFingerprintId;
            }

            return fetch('/api/cybersource/create_checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(secureApiData)
            });
        })
        .then(response => {
            return response.text().then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('La respuesta de la pasarela no es valida');
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || `Error HTTP: ${response.status}`);
                }

                return data;
            });
        })
        .then(data => {
            if (!data.redirect_url) {
                throw new Error('La pasarela no devolvio una URL de pago segura.');
            }

            ventanaPago.location.replace(data.redirect_url);
            ventanaPago.focus();
            cerrarModalPedido();
            restaurarBotonPagoTarjeta(btnEnviar);
        })
        .catch(error => {
            console.error('Error iniciando pago:', error);
            cerrarVentanaPagoExterna(ventanaPago);

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo iniciar el pago',
                    text: error.message || 'Intenta nuevamente o selecciona otro metodo de pago.',
                    confirmButtonText: 'Aceptar'
                }).then(() => {
                    restaurarBotonPagoTarjeta(btnEnviar);
                });
            } else {
                alert(error.message || 'No se pudo iniciar el pago.');
                restaurarBotonPagoTarjeta(btnEnviar);
            }
        });
}

