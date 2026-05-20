// ------------------------------------------------------------
// Inicialización
// ------------------------------------------------------------
document.addEventListener("DOMContentLoaded", function () {
    // El slider se carga desde PHP en index.php, no necesita JavaScript
    cargarProductosPromociones();
});

const PRODUCTO_IMG_BASE = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/files/articulos/";
const PRODUCTO_IMG_PLACEHOLDER = "assets/img/404.png";

function escaparTextoProducto(valor) {
    return (valor ?? "").toString().replace(/'/g, "\\'");
}

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
    const contenedor = document.getElementById("contenedor-promociones-productos");

    if (!contenedor) {
        console.error("❌ No se encontró el contenedor de promociones.");
        return;
    }

    fetch("https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_tienda_ofertas.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({})
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            contenedor.innerHTML = "";

            if (!data.success || !Array.isArray(data.data)) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">No hay productos nuevos para mostrar.</div>
                </div>`;
                return;
            }

            let productos = data.data;
            let htmlProductos = "";

            productos.forEach(producto => {
                const nombreVisible = (producto?.nombre ?? "Producto").toString();
                const nombre = escaparTextoProducto(nombreVisible);
                const descripcion = escaparTextoProducto(producto?.descripcion ?? "");
                const codigo = escaparTextoProducto(producto?.codigo ?? "");
                const stock = escaparTextoProducto(producto?.stock ?? "N/A");
                const precioVenta = producto?.precio_venta ?? 0;
                const idArticulo = escaparTextoProducto(producto?.idarticulo ?? "");

                const imagen = obtenerImagenProducto(producto);

                let html = `
                <div class="col-lg-3 col-sm-6">
                    <div class="single-arrivals-products">

                        <div class="arrivals-products-image">
                            <a href="javascript:void(0)"
                            onclick="vistaRapida(
                                '${nombre}',
                                '${imagen}',
                                '${precioVenta}',
                                '${idArticulo}',
                                '${codigo}',
                                '${descripcion}',
                                '${stock}'
                            )">
                                <img src="${imagen}" alt="${nombreVisible}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                            </a>
                            <div class="tag">Ofertas</div>

                            <!-- ⭐ Iconos -->
                            <ul class="arrivals-action">
                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="agregarAlCarrito(
                                        '${idArticulo}',
                                        '${nombre}',
                                        '${precioVenta}',
                                        '${imagen}',
                                        '${descripcion}'
                                    )">
                                        <i class="flaticon-shopping-cart"></i>
                                    </a>
                                </li>

                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="vistaRapida(
                                        '${nombre}',
                                        '${imagen}',
                                        '${precioVenta}',
                                        '${idArticulo}',
                                        '${codigo}',
                                        '${descripcion}',
                                        '${stock}'
                                    )">
                                        <i class="flaticon-view"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="arrivals-products-content">
                            <h3 class="product-title-limit">${nombreVisible}</h3>

                            <!-- ⭐ Rating fijo -->
                            <ul class="rating">
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                                <li><i class='bx bxs-star'></i></li>
                            </ul>

                            <span>Q${parseFloat(precioVenta).toFixed(2)}</span>
                        </div>

                    </div>
                </div>
            `;

                htmlProductos += html;
            });

            contenedor.innerHTML = htmlProductos || `
                <div class="col-12">
                    <div class="alert alert-warning">No hay productos disponibles para mostrar.</div>
                </div>`;

        })
        .catch(error => {
            console.error("❌ Error al cargar productos en ofertas:", error);
            contenedor.innerHTML = `
            <div class="col-12">
                <div class="alert alert-danger">Error al cargar los productos. Por favor intenta de nuevo.</div>
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
    return `¡Mira este producto en Librería Marquense!\n\n${nombre}\nPrecio: Q${precio}\n\n${url}`;
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
            `_Librería Marquense - Útiles escolares y papelería_`
        );

        const whatsappUrl = `https://wa.me/50255910533?text=${mensaje}`;

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

/* ============================================================
   PAGINACION DE OFERTAS - LIBRERIA MARQUENSE
============================================================ */
const OFERTAS_PRODUCTOS_PAGINADOS_ENDPOINT = "assets/php/productos_paginados.php";
const OFERTAS_PRODUCTOS_POR_PAGINA = 30;

let ofertasEstadoPaginacion = {
    page: 1
};

function ofertasPaginaDesdeUrl() {
    const page = parseInt(new URLSearchParams(window.location.search).get("page"), 10);
    return Number.isFinite(page) && page > 0 ? page : 1;
}

function ofertasActualizarUrl() {
    const params = new URLSearchParams(window.location.search);

    if (ofertasEstadoPaginacion.page > 1) {
        params.set("page", ofertasEstadoPaginacion.page);
    } else {
        params.delete("page");
    }

    const query = params.toString();
    const nextUrl = `${window.location.pathname}${query ? "?" + query : ""}${window.location.hash}`;
    window.history.replaceState({}, "", nextUrl);
}

function ofertasCantidadPaginasSeleccionables() {
    return window.matchMedia("(max-width: 991px)").matches ? 2 : 3;
}

function ofertasPaginasVisibles(page, totalPages) {
    const visibles = ofertasCantidadPaginasSeleccionables();

    if (totalPages <= visibles) {
        return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    const margen = Math.floor(visibles / 2);
    let start = Math.max(1, page - margen);
    let end = start + visibles - 1;

    if (end > totalPages) {
        end = totalPages;
        start = Math.max(1, end - visibles + 1);
    }

    const pages = [];

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    if (end < totalPages) {
        if (end + 1 < totalPages) {
            pages.push("ellipsis");
        }
        pages.push(totalPages);
    }

    return pages;
}

function ofertasRenderizarPaginacion(meta) {
    const host = document.getElementById("products-pagination");
    if (!host || !meta) return;

    const page = parseInt(meta.page || 1, 10);
    const totalPages = parseInt(meta.total_pages || 1, 10);
    const total = parseInt(meta.total || 0, 10);
    const from = parseInt(meta.from || 0, 10);
    const to = parseInt(meta.to || 0, 10);
    const pages = ofertasPaginasVisibles(page, totalPages);

    const pageButtons = pages.map(item => {
        if (item === "ellipsis") {
            return `<span class="catalog-pagination__ellipsis">...</span>`;
        }

        return `
            <button type="button" class="catalog-pagination__button catalog-pagination__button--page ${item === page ? "is-active" : ""}" onclick="cambiarPaginaPromociones(${item})" aria-label="Ir a pagina ${item}">
                ${item}
            </button>
        `;
    }).join("");

    host.innerHTML = `
        <div class="catalog-pagination" role="navigation" aria-label="Paginacion de ofertas">
            <div class="catalog-pagination__info">
                <p class="catalog-pagination__summary">Mostrando ${from} a ${to} de ${total} productos</p>
                <span class="catalog-pagination__context">Ofertas especiales</span>
            </div>
            <div class="catalog-pagination__pages">
                <button type="button" class="catalog-pagination__button catalog-pagination__button--prev" onclick="cambiarPaginaPromociones(${page - 1})" ${page <= 1 ? "disabled" : ""}>Anterior</button>
                ${pageButtons}
                <button type="button" class="catalog-pagination__button catalog-pagination__button--next" onclick="cambiarPaginaPromociones(${page + 1})" ${page >= totalPages ? "disabled" : ""}>Siguiente</button>
            </div>
        </div>
    `;
}

function ofertasRenderizarCardProducto(producto) {
    const nombreVisible = (producto?.nombre ?? "Producto").toString();
    const nombre = escaparTextoProducto(nombreVisible);
    const descripcion = escaparTextoProducto(producto?.descripcion ?? "");
    const codigo = escaparTextoProducto(producto?.codigo ?? "");
    const stock = escaparTextoProducto(producto?.stock ?? "N/A");
    const precioVenta = producto?.precio_venta ?? 0;
    const idArticulo = escaparTextoProducto(producto?.idarticulo ?? "");
    const imagen = obtenerImagenProducto(producto);

    return `
        <div class="col-lg-3 col-sm-6">
            <div class="single-arrivals-products">
                <div class="arrivals-products-image">
                    <a href="javascript:void(0)"
                    onclick="vistaRapida(
                        '${nombre}',
                        '${imagen}',
                        '${precioVenta}',
                        '${idArticulo}',
                        '${codigo}',
                        '${descripcion}',
                        '${stock}'
                    )">
                        <img src="${imagen}" alt="${nombreVisible}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                    </a>
                    <div class="tag">Ofertas</div>
                    <ul class="arrivals-action">
                        <li>
                            <a href="javascript:void(0)"
                            onclick="agregarAlCarrito(
                                '${idArticulo}',
                                '${nombre}',
                                '${precioVenta}',
                                '${imagen}',
                                '${descripcion}'
                            )">
                                <i class="flaticon-shopping-cart"></i>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)"
                            onclick="vistaRapida(
                                '${nombre}',
                                '${imagen}',
                                '${precioVenta}',
                                '${idArticulo}',
                                '${codigo}',
                                '${descripcion}',
                                '${stock}'
                            )">
                                <i class="flaticon-view"></i>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="arrivals-products-content">
                    <h3 class="product-title-limit">${nombreVisible}</h3>
                    <ul class="rating">
                        <li><i class='bx bxs-star'></i></li>
                        <li><i class='bx bxs-star'></i></li>
                        <li><i class='bx bxs-star'></i></li>
                        <li><i class='bx bxs-star'></i></li>
                        <li><i class='bx bxs-star'></i></li>
                    </ul>
                    <span>Q${parseFloat(precioVenta).toFixed(2)}</span>
                </div>
            </div>
        </div>
    `;
}

function ofertasRenderizarProductos(productos) {
    const contenedor = document.getElementById("contenedor-promociones-productos");
    if (!contenedor) return;

    if (!Array.isArray(productos) || productos.length === 0) {
        contenedor.innerHTML = `
            <div class="col-12">
                <div class="catalog-empty-state">No hay productos disponibles para mostrar.</div>
            </div>`;
        return;
    }

    contenedor.innerHTML = productos.map(ofertasRenderizarCardProducto).join("");
}

function ofertasCargarListadoPaginado(actualizarUrl = true) {
    const contenedor = document.getElementById("contenedor-promociones-productos");
    if (!contenedor) return Promise.resolve();

    contenedor.innerHTML = `
        <div class="col-12">
            <div class="catalog-empty-state">Cargando productos...</div>
        </div>`;

    return fetch(OFERTAS_PRODUCTOS_PAGINADOS_ENDPOINT, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            mode: "ofertas",
            page: ofertasEstadoPaginacion.page,
            per_page: OFERTAS_PRODUCTOS_POR_PAGINA
        })
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success || !Array.isArray(data.data)) {
                throw new Error(data.message || "No fue posible cargar las ofertas.");
            }

            ofertasEstadoPaginacion.page = parseInt(data.meta?.page || ofertasEstadoPaginacion.page, 10);
            ofertasRenderizarProductos(data.data);
            ofertasRenderizarPaginacion(data.meta);

            if (actualizarUrl) {
                ofertasActualizarUrl();
            }
        })
        .catch(error => {
            console.error("Error al cargar ofertas paginadas:", error);
            contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">Error al cargar los productos. Por favor intenta de nuevo.</div>
                </div>`;
        });
}

window.cambiarPaginaPromociones = function (page) {
    const pageNumber = parseInt(page, 10);
    if (!Number.isFinite(pageNumber) || pageNumber < 1) return;

    ofertasEstadoPaginacion.page = pageNumber;
    ofertasCargarListadoPaginado(true).then(() => {
        const section = document.querySelector(".arrivals-products-area");
        if (section) {
            section.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    });
};

window.cargarProductosPromociones = function () {
    ofertasEstadoPaginacion.page = ofertasPaginaDesdeUrl();
    return ofertasCargarListadoPaginado(true);
};








