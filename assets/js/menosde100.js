// ------------------------------------------------------------
// Inicialización
// ------------------------------------------------------------
document.addEventListener("DOMContentLoaded", function () {
    if (typeof window.cargarProductosPromociones === "function") {
        window.cargarProductosPromociones();
    }
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

    const thumbs = document.querySelectorAll(".mp-thumb-item");

    thumbs.forEach(thumb => {
        thumb.classList.remove("active");
    });

    if (thumbs[mpIndex]) {
        thumbs[mpIndex].classList.add("active");
    }
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
   PAGINACION MENOS DE 100 - LIBRERIA MARQUENSE
============================================================ */
const MENOS100_PRODUCTOS_PAGINADOS_ENDPOINT = "assets/php/productos_paginados.php";
const MENOS100_PRODUCTOS_POR_PAGINA = 30;

let menos100EstadoPaginacion = {
    page: 1
};

function menos100PaginaDesdeUrl() {
    const page = parseInt(new URLSearchParams(window.location.search).get("page"), 10);
    return Number.isFinite(page) && page > 0 ? page : 1;
}

function menos100ActualizarUrl() {
    const params = new URLSearchParams(window.location.search);

    if (menos100EstadoPaginacion.page > 1) {
        params.set("page", menos100EstadoPaginacion.page);
    } else {
        params.delete("page");
    }

    const query = params.toString();
    const nextUrl = `${window.location.pathname}${query ? "?" + query : ""}${window.location.hash}`;
    window.history.replaceState({}, "", nextUrl);
}

function menos100CantidadPaginasSeleccionables() {
    return window.matchMedia("(max-width: 991px)").matches ? 2 : 3;
}

function menos100PaginasVisibles(page, totalPages) {
    const visibles = menos100CantidadPaginasSeleccionables();

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

function menos100RenderizarPaginacion(meta) {
    const host = document.getElementById("products-pagination");
    if (!host || !meta) return;

    const page = parseInt(meta.page || 1, 10);
    const totalPages = parseInt(meta.total_pages || 1, 10);
    const total = parseInt(meta.total || 0, 10);
    const from = parseInt(meta.from || 0, 10);
    const to = parseInt(meta.to || 0, 10);
    const pages = menos100PaginasVisibles(page, totalPages);

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
        <div class="catalog-pagination" role="navigation" aria-label="Paginacion de menos de 100">
            <div class="catalog-pagination__info">
                <p class="catalog-pagination__summary">Mostrando ${from} a ${to} de ${total} productos</p>
                <span class="catalog-pagination__context">Menos de 100</span>
            </div>
            <div class="catalog-pagination__pages">
                <button type="button" class="catalog-pagination__button catalog-pagination__button--prev" onclick="cambiarPaginaPromociones(${page - 1})" ${page <= 1 ? "disabled" : ""}>Anterior</button>
                ${pageButtons}
                <button type="button" class="catalog-pagination__button catalog-pagination__button--next" onclick="cambiarPaginaPromociones(${page + 1})" ${page >= totalPages ? "disabled" : ""}>Siguiente</button>
            </div>
        </div>
    `;
}

function menos100PrepararCompra(producto) {
    if (!window.LMProductPresentations) {
        const stock = Number(producto?.stock ?? 0);
        const precio = Number(producto?.precio_venta ?? 0);
        return {
            disponible: Number.isFinite(stock) && stock > 0 && Number.isFinite(precio) && precio > 0,
            registro: ""
        };
    }

    const presentacion = window.LMProductPresentations.defaultUnit(producto);
    const disponible = !!presentacion
        && !presentacion.disabled
        && (presentacion.stock === null || presentacion.stock > 0)
        && presentacion.precio > 0;

    return {
        disponible,
        registro: disponible
            ? window.LMProductPresentations.registerForCart(producto)
            : ""
    };
}

window.menos100NotificarSinStock = function () {
    if (window.Carrito && typeof window.Carrito.mostrarNotificacion === "function") {
        window.Carrito.mostrarNotificacion("Producto no disponible por falta de stock.");
    } else {
        alert("Producto no disponible por falta de stock.");
    }

    return false;
};

function menos100RenderizarCardProducto(producto) {
    const nombreVisible = (producto?.nombre ?? "Producto").toString();
    const nombre = escaparTextoProducto(nombreVisible);
    const descripcion = escaparTextoProducto(producto?.descripcion ?? "");
    const codigo = escaparTextoProducto(producto?.codigo ?? "");
    const stock = escaparTextoProducto(producto?.stock ?? "N/A");
    const precioVenta = producto?.precio_venta ?? 0;
    const idArticulo = escaparTextoProducto(producto?.idarticulo ?? "");
    const imagen = obtenerImagenProducto(producto);
    const compra = menos100PrepararCompra(producto);
    const accionCarrito = compra.disponible
        ? `agregarAlCarrito(
                '${idArticulo}',
                '${nombre}',
                '${precioVenta}',
                '${imagen}',
                '${descripcion}',
                1,
                '${compra.registro}'
            )`
        : "return menos100NotificarSinStock()";

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
                            onclick="${accionCarrito}"
                            aria-disabled="${compra.disponible ? "false" : "true"}">
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

function menos100RenderizarProductos(productos) {
    const contenedor = document.getElementById("contenedor-promociones-productos");
    if (!contenedor) return;

    if (!Array.isArray(productos) || productos.length === 0) {
        contenedor.innerHTML = `
            <div class="col-12">
                <div class="catalog-empty-state">No hay productos disponibles para mostrar.</div>
            </div>`;
        return;
    }

    contenedor.innerHTML = productos.map(menos100RenderizarCardProducto).join("");
}

function menos100CargarListadoPaginado(actualizarUrl = true) {
    const contenedor = document.getElementById("contenedor-promociones-productos");
    if (!contenedor) return Promise.resolve();

    contenedor.innerHTML = `
        <div class="col-12">
            <div class="catalog-empty-state">Cargando productos...</div>
        </div>`;

    return fetch(MENOS100_PRODUCTOS_PAGINADOS_ENDPOINT, {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            mode: "menosde100",
            page: menos100EstadoPaginacion.page,
            per_page: MENOS100_PRODUCTOS_POR_PAGINA
        })
    })
        .then(async response => {
            const respuestaTexto = await response.text();

            let data = null;

            try {
                data = JSON.parse(respuestaTexto);
            } catch (error) {
                data = null;
            }

            if (!response.ok) {
                const mensaje =
                    data?.message ||
                    respuestaTexto ||
                    `Error HTTP ${response.status}`;

                throw new Error(mensaje);
            }

            if (!data) {
                throw new Error(
                    "El servidor no devolvió una respuesta JSON válida."
                );
            }

            return data;
        })
        .then(data => {
            if (!data.success || !Array.isArray(data.data)) {
                throw new Error(data.message || "No fue posible cargar los productos.");
            }

            menos100EstadoPaginacion.page = parseInt(data.meta?.page || menos100EstadoPaginacion.page, 10);
            menos100RenderizarProductos(data.data);
            menos100RenderizarPaginacion(data.meta);

            if (actualizarUrl) {
                menos100ActualizarUrl();
            }
        })
        .catch(error => {
            console.error("Error al cargar menos de 100 paginado:", error);
            contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">Error al cargar los productos. Por favor intenta de nuevo.</div>
                </div>`;
        });
}

window.cambiarPaginaPromociones = function (page) {
    const pageNumber = parseInt(page, 10);
    if (!Number.isFinite(pageNumber) || pageNumber < 1) return;

    menos100EstadoPaginacion.page = pageNumber;
    menos100CargarListadoPaginado(true).then(() => {
        const section = document.querySelector(".arrivals-products-area");
        if (section) {
            section.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    });
};

window.cargarProductosPromociones = function () {
    menos100EstadoPaginacion.page = menos100PaginaDesdeUrl();
    return menos100CargarListadoPaginado(true);
};








