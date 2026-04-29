// ------------------------------------------------------------
// Inicialización
// ------------------------------------------------------------
document.addEventListener("DOMContentLoaded", function () {
    // El slider se carga desde PHP en index.php, no necesita JavaScript
    cargarProductosPromociones();
    cargarProductosNuevos();
    cargarProductosMasVendidos();
});

const PRODUCTO_IMG_BASE = "https://ssl.sol.sistemasolgt.com/ticel/files/articulos/";
const PRODUCTO_IMG_PLACEHOLDER = "assets/img/404.png";

function construirUrlImagenProducto(nombreArchivo) {
    const nombre = (nombreArchivo ?? "").toString().trim();
    return nombre ? `${PRODUCTO_IMG_BASE}${encodeURIComponent(nombre)}` : "";
}

function obtenerImagenProducto(producto) {
    return construirUrlImagenProducto(producto?.imagen) || PRODUCTO_IMG_PLACEHOLDER;
}

/* ============================================================
   🔥 CARGAR PRODUCTOS NUEVOS DESDE TU API
============================================================ */
function cargarProductosPromociones() {

    fetch("https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_articulos_listarProductospromociones.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({})
    })
        .then(response => response.json())
        .then(data => {

            let contenedor = document.getElementById("contenedor-promociones-productos");
            contenedor.innerHTML = "";

            if (!data.success || !Array.isArray(data.data)) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">No hay productos nuevos para mostrar.</div>
                </div>`;
                return;
            }

            let productos = data.data;

            productos.forEach(producto => {

                const imagen = obtenerImagenProducto(producto);

                let html = `
                <div class="col-lg-3 col-sm-6">
                    <div class="single-arrivals-products">

                        <div class="arrivals-products-image">
                            <a href="javascript:void(0)"
                            onclick="vistaRapida(
                                '${producto.nombre.replace(/'/g, "\\'")}',
                                '${imagen}',
                                '${producto.precio_venta}',
                                '${producto.idarticulo}',
                                '${producto.codigo || ""}',
                                '${producto.descripcion.replace(/'/g, "\\'")}',
                                '${producto.stock || "N/A"}'
                            )">
                                <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                            </a>
                            <div class="tag">Promoción</div>

                            <!-- ⭐ Iconos -->
                            <ul class="arrivals-action">
                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="agregarAlCarrito(
                                        '${producto.idarticulo}',
                                        '${producto.nombre.replace(/'/g, "\\'")}',
                                        '${producto.precio_venta}',
                                        '${imagen}',
                                        '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                    )">
                                        <i class="flaticon-shopping-cart"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="vistaRapida(
                                        '${producto.nombre.replace(/'/g, "\\'")}',
                                        '${imagen}',
                                        '${producto.precio_venta}',
                                        '${producto.idarticulo}',
                                        '${producto.codigo || ""}',
                                        '${producto.descripcion.replace(/'/g, "\\'")}',
                                        '${producto.stock || "N/A"}'
                                    )">
                                        <i class="flaticon-view"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="arrivals-products-content">
                            <h3 class="product-title-limit">${producto.nombre}</h3>

                            <!-- ⭐ Rating fijo -->
                            <ul class="rating">
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                            </ul>

                            <span>Q${parseFloat(producto.precio_venta).toFixed(2)}</span>
                        </div>

                    </div>
                </div>
            `;

                contenedor.innerHTML += html;
            });

        })
        .catch(error => {
            console.error("❌ Error:", error);
            contenedor.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">Error al cargar los productos nuevos.</div>
            </div>`;
        });
}



/* ============================================================
   🔥 CARGAR PRODUCTOS NUEVOS DESDE TU API
============================================================ */
function cargarProductosNuevos() {

    fetch("https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_articulos_listarProductosnuevos.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({})
    })
        .then(response => response.json())
        .then(data => {

            let contenedor = document.getElementById("contenedor-nuevos-productos");
            contenedor.innerHTML = "";

            if (!data.success || !Array.isArray(data.data)) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">No hay productos nuevos para mostrar.</div>
                </div>`;
                return;
            }

            let productos = data.data;

            productos.forEach(producto => {

                const imagen = obtenerImagenProducto(producto);

                let html = `
                <div class="col-lg-3 col-sm-6">
                    <div class="single-arrivals-products">

                        <div class="arrivals-products-image">
                            <a href="javascript:void(0)"
                            onclick="vistaRapida(
                                '${producto.nombre.replace(/'/g, "\\'")}',
                                '${imagen}',
                                '${producto.precio_venta}',
                                '${producto.idarticulo}',
                                '${producto.codigo || ""}',
                                '${producto.descripcion.replace(/'/g, "\\'")}',
                                '${producto.stock || "N/A"}'
                            )">
                                    <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                            </a>
                            <div class="tag">New</div>

                            <!-- ⭐ Iconos -->
                            <ul class="arrivals-action">
                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="agregarAlCarrito(
                                        '${producto.idarticulo}',
                                        '${producto.nombre.replace(/'/g, "\\'")}',
                                        '${producto.precio_venta}',
                                        '${imagen}',
                                        '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                    )">
                                        <i class="flaticon-shopping-cart"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="vistaRapida(
                                        '${producto.nombre.replace(/'/g, "\\'")}',
                                        '${imagen}',
                                        '${producto.precio_venta}',
                                        '${producto.idarticulo}',
                                        '${producto.codigo || ""}',
                                        '${producto.descripcion.replace(/'/g, "\\'")}',
                                        '${producto.stock || "N/A"}'
                                    )">
                                        <i class="flaticon-view"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="arrivals-products-content">
                            <h3 class="product-title-limit">${producto.nombre}</h3>

                            <!-- ⭐ Rating fijo -->
                            <ul class="rating">
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                            </ul>

                            <span>Q${parseFloat(producto.precio_venta).toFixed(2)}</span>
                        </div>

                    </div>
                </div>
            `;

                contenedor.innerHTML += html;
            });

        })
        .catch(error => {
            console.error("❌ Error:", error);
            contenedor.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">Error al cargar los productos nuevos.</div>
            </div>`;
        });
}

/* ============================================================
   ⭐ FUNCIÓN VISTA RÁPIDA - REDIRIGE A PÁGINA DE PRODUCTO
============================================================ */
function vistaRapida(nombre, imagen, precio, idarticulo, codigo, descripcion, stocksucursal) {
    // Redirigir a la página de producto con el idarticulo
    window.location.href = 'producto.php?id=' + idarticulo;
}

let mpIndex = 0;
let mpImagenes = [];
let mpProductoActual = null; // Guardar datos del producto actual para compartir

/* ===============================
   ABRIR MODAL CON DATOS
=============================== */
function abrirModalProducto(producto) {

    mpImagenes = Array.isArray(producto.imagenes) && producto.imagenes.length
        ? producto.imagenes
        : [PRODUCTO_IMG_PLACEHOLDER];
    mpIndex = 0;
    mpProductoActual = producto; // Guardar producto para compartir

    // Colocar texto
    document.getElementById("mp-titulo").innerText = producto.nombre;
    document.getElementById("mp-precio").innerText = "Q" + producto.precio;
    document.getElementById("mp-descripcion").innerText = producto.descripcion;
    document.getElementById("mp-stock").innerText = producto.stocksucursal;
    document.getElementById("mp-sku").innerText = producto.sku;

    // Imagen principal
    const imagenPrincipal = document.getElementById("mp-imagen-principal");
    imagenPrincipal.src = mpImagenes[0];
    imagenPrincipal.onerror = function () {
        this.onerror = null;
        this.src = PRODUCTO_IMG_PLACEHOLDER;
    };

    // Thumbs
    let thumbs = document.getElementById("mp-thumbs");
    thumbs.innerHTML = "";

    mpImagenes.forEach((img, i) => {
        thumbs.innerHTML += `
            <div class="mp-thumb-item ${i === 0 ? 'active' : ''}" onclick="mpMostrar(${i})">
                <img src="${img}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
            </div>
        `;

    });

    // Mostrar modal
    document.getElementById("modalProducto").classList.add("show");
    // Prevenir scroll del body cuando el modal está abierto
    document.body.style.overflow = 'hidden';

    // Agregar event listener alternativo para WhatsApp en móvil (por si onclick no funciona)
    setTimeout(() => {
        const btnWhatsApp = document.getElementById("btn-compartir-whatsapp");
        if (btnWhatsApp) {
            // Agregar listener touchstart para móvil (más confiable que click)
            btnWhatsApp.addEventListener('touchstart', function (e) {
                e.preventDefault();
                e.stopPropagation();
                compartirWhatsApp(e);
            }, { passive: false });

            // También agregar click como respaldo
            btnWhatsApp.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                compartirWhatsApp(e);
            }, { passive: false });
        }
    }, 100);
}


/* ===============================
   CERRAR MODAL
=============================== */
function cerrarModal() {
    document.getElementById("modalProducto").classList.remove("show");
    // Prevenir scroll del body cuando se cierra
    document.body.style.overflow = '';
}

// Cerrar modal al hacer clic fuera (en el overlay)
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalProducto');
    if (modal) {
        modal.addEventListener('click', function (e) {
            // Si se hace clic en el overlay (no en el modal)
            if (e.target === modal) {
                cerrarModal();
            }
        });
    }

    // Cerrar con tecla ESC
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalProducto');
            if (modal && modal.classList.contains('show')) {
                cerrarModal();
            }
        }
    });
});

/* ===============================
   MOSTRAR IMAGEN POR ÍNDICE
=============================== */
function mpMostrar(i) {
    mpIndex = i;
    const imagenPrincipal = document.getElementById("mp-imagen-principal");
    imagenPrincipal.src = mpImagenes[mpIndex] || PRODUCTO_IMG_PLACEHOLDER;
    imagenPrincipal.onerror = function () {
        this.onerror = null;
        this.src = PRODUCTO_IMG_PLACEHOLDER;
    };

    let thumbs = document.querySelectorAll(".mp-thumb");
    thumbs.forEach(t => t.classList.remove("active"));
    thumbs[mpIndex].classList.add("active");
}

/* ===============================
   SIGUIENTE Y ANTERIOR
=============================== */
function imgNext() {
    mpIndex = (mpIndex + 1) % mpImagenes.length;
    mpMostrar(mpIndex);
}

function imgPrev() {
    mpIndex = (mpIndex - 1 + mpImagenes.length) % mpImagenes.length;
    mpMostrar(mpIndex);
}

/* ===============================
   CAMBIAR CANTIDAD
=============================== */
function mpCambiarCantidad(n) {
    let input = document.getElementById("mp-cantidad");
    let val = parseInt(input.value) + n;

    if (val < 1) val = 1;
    input.value = val;
}

/* ===============================
   FUNCIONES PARA COMPARTIR
=============================== */

// Obtener URL del producto
window.obtenerUrlProducto = function () {
    if (!mpProductoActual || !mpProductoActual.idarticulo) {
        return window.location.origin + window.location.pathname;
    }
    const baseUrl = window.location.origin;
    return `${baseUrl}/producto.php?id=${mpProductoActual.idarticulo}`;
}

// Obtener texto para compartir
window.obtenerTextoCompartir = function () {
    if (!mpProductoActual) return '';
    const nombre = mpProductoActual.nombre || 'Producto';
    const precio = mpProductoActual.precio || '0.00';
    const url = obtenerUrlProducto();
    return `¡Mira este producto en CompusisGT!\n\n${nombre}\nPrecio: Q${precio}\n\n${url}`;
}

// Compartir por WhatsApp - Función global
window.compartirWhatsApp = function (e) {
    // Prevenir comportamiento por defecto
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    if (!mpProductoActual) {
        alert('No hay información del producto para compartir');
        return false;
    }

    try {
        const url = obtenerUrlProducto();
        // Mensaje mejorado con emojis y mejor formato
        const mensaje = encodeURIComponent(
            `🛍️ *${mpProductoActual.nombre}*\n\n` +
            `💰 Precio: *Q${mpProductoActual.precio}*\n\n` +
            `📦 Disponibilidad: ${mpProductoActual.stocksucursal || 'Consultar'}\n\n` +
            `🔗 Ver más detalles:\n${url}\n\n` +
            `_CompusisGT - Tecnología en Guatemala_`
        );

        const whatsappUrl = `https://wa.me/?text=${mensaje}`;

        // Detectar si es móvil
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        if (isMobile) {
            // En móvil, usar location.href directamente
            window.location.href = whatsappUrl;
        } else {
            // En escritorio, abrir en nueva ventana
            window.open(whatsappUrl, '_blank');
        }

        return false;
    } catch (error) {
        console.error('Error al compartir por WhatsApp:', error);
        alert('Error al compartir. Por favor, intenta de nuevo.');
        return false;
    }
}



// ===========================================================
// ⭐ FUNCIÓN: Cargar productos nuevos
// ===========================================================
function cargarProductosMasVendidos() {

    fetch("https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_articulos_listarProductosnuevos_lomasvendido.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({})
    })
        .then(response => response.json())
        .then(data => {

            // Mostrar JSON real (solo para pruebas)
            // alert(JSON.stringify(data, null, 2));

            let contenedor = document.getElementById("productos-mas-vendidos");

            contenedor.innerHTML = "";

            if (!data.success || !Array.isArray(data.data)) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">No hay productos nuevos para mostrar.</div>
                </div>`;
                return;
            }

            let productos = data.data;

            productos.forEach(producto => {

                const imagen = obtenerImagenProducto(producto);

                let html = `
                <div class="col-lg-3 col-sm-6">
                    <div class="single-arrivals-products">

                        <div class="arrivals-products-image">
                            <a href="javascript:void(0)"
                            onclick="vistaRapida(
                                '${producto.nombre.replace(/'/g, "\\'")}',
                                '${imagen}',
                                '${producto.precio_venta}',
                                '${producto.idarticulo}',
                                '${producto.codigo}',
                                '${producto.descripcion.replace(/'/g, "\\'")}',
                                '${producto.stock}'
                            )">
                                <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                            </a>
                            <div class="tag">Popular</div>

                            <ul class="arrivals-action">
                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="agregarAlCarrito(
                                        '${producto.idarticulo}',
                                        '${producto.nombre.replace(/'/g, "\\'")}',
                                        '${producto.precio_venta}',
                                        '${imagen}',
                                        '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                    )">
                                        <i class="flaticon-shopping-cart"></i>
                                    </a>
                                </li>
                                <li><a href="javascript:void(0)"
                                    onclick="vistaRapida(
                                        '${producto.nombre.replace(/'/g, "\\'")}',
                                        '${imagen}',
                                        '${producto.precio_venta}',
                                        '${producto.idarticulo}',
                                        '${producto.codigo}',
                                        '${producto.descripcion.replace(/'/g, "\\'")}',
                                        '${producto.stock}'
                                    )">
                                    <i class="flaticon-view"></i></a>
                                </li>
                            </ul>
                        </div>

                        <div class="arrivals-products-content">
                            <h3 class="product-title-limit">${producto.nombre}</h3>

                            <ul class="rating">
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                            </ul>

                            <span>Q${parseFloat(producto.precio_venta).toFixed(2)}</span>
                        </div>

                    </div>
                </div>
            `;

                contenedor.innerHTML += html;
            });

        })
        .catch(error => {
            console.error("❌ Error:", error);
            document.getElementById("contenedor-mas-vendidos").innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">Error al cargar los productos.</div>
            </div>`;
        });
}







