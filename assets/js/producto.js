// ------------------------------------------------------------
// Variables globales para el filtro de precio
// ------------------------------------------------------------
let todosLosProductos = []; // Almacenar todos los productos cargados
let precioMinimo = 0;
let precioMaximo = 0;
const PRODUCTO_IMG_BASE = "https://ssl.sol.sistemasolgt.com/ticel/files/articulos/";
const PRODUCTO_IMG_PLACEHOLDER = "assets/img/404.png";

function construirUrlImagenProducto(nombreArchivo) {
    const nombre = (nombreArchivo ?? "").toString().trim();
    return nombre ? `${PRODUCTO_IMG_BASE}${encodeURIComponent(nombre)}` : "";
}

// ------------------------------------------------------------
// Inicialización
// ------------------------------------------------------------
document.addEventListener("DOMContentLoaded", function () {
    // Detectar parámetros en la URL
    const urlParams = new URLSearchParams(window.location.search);
    const categoriaId = urlParams.get('categoria');
    const buscar = urlParams.get('buscar');

    if (buscar) {
        // Si hay búsqueda, cargar productos por búsqueda
        cargarProductosPorBusqueda(buscar);
    } else if (categoriaId) {
        // Si hay categoría, cargar productos por categoría
        cargarProductosPorCategoria(categoriaId);
    } else {
        // Si no hay parámetros, cargar todos los productos nuevos
        cargarProductosNuevos();
    }

    // Inicializar el filtro de precio después de que jQuery esté listo
    if (typeof jQuery !== 'undefined') {
        // Esperar a que los productos se carguen
        setTimeout(inicializarFiltroPrecio, 1000);
    } else {
        // Si jQuery no está disponible, esperar y reintentar
        setTimeout(function () {
            if (typeof jQuery !== 'undefined') {
                inicializarFiltroPrecio();
            }
        }, 2000);
    }

    // Agregar evento al botón Filter
    document.addEventListener('click', function (e) {
        if (e.target.closest('.price-range-filter-button .btn')) {
            e.preventDefault();
            aplicarFiltroPrecio();
        }
    });
});



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

            // Verificar si estamos en index.php o tienda.php
            let contenedor = document.getElementById("contenedor-nuevos-productos");
            let contenedorTienda = document.getElementById("products-collections-filter");

            // Determinar qué contenedor usar
            let contenedorActivo = contenedor || contenedorTienda;

            if (!contenedorActivo) {
                console.error("❌ No se encontró el contenedor de productos");
                return;
            }

            contenedorActivo.innerHTML = "";

            if (!data.success || !Array.isArray(data.data)) {
                contenedorActivo.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">No hay productos nuevos para mostrar.</div>
                </div>`;
                return;
            }

            let productos = data.data;
            let esTienda = !!contenedorTienda; // Si existe el contenedor de tienda, usar formato de tienda

            // Guardar productos para el filtro
            if (esTienda) {
                todosLosProductos = productos;
                calcularRangoPrecios(productos);
                // Inicializar el slider después de calcular el rango
                setTimeout(inicializarFiltroPrecio, 300);
            }

            productos.forEach(producto => {

                const imagen = construirUrlImagenProducto(producto?.imagen) || PRODUCTO_IMG_PLACEHOLDER;

                let html = "";

                if (esTienda) {
                    // Formato para tienda.php
                    html = `
                    <div class="col-lg-4 col-sm-6">
                        <div class="single-shop-products">
                            <div class="shop-products-image">
                                <a href="javascript:void(0)"
                                onclick="vistaRapida(
                                    '${producto.nombre.replace(/'/g, "\\'")}',
                                    '${imagen}',
                                    '${producto.precio_venta}',
                                    '${producto.idarticulo}',
                                    '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                )">
                                    <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                                </a>
                                <div class="tag">New</div>
                                <ul class="shop-action">
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
                                            '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                        )">
                                            <i class="flaticon-view"></i>
                                        </a>
                                    </li>
                                </ul>
                            </div>
            
                            <div class="shop-products-content">
                                <h3>
                                    <a href="producto.php?id=${producto.idarticulo}">${producto.nombre}</a>
                                </h3>
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
                } else {
                    // Formato para index.php
                    html = `
                    <div class="col-lg-3 col-sm-6">
                        <div class="single-arrivals-products">
                            <div class="arrivals-products-image">
                                <a href="javascript:void(0)"
                                onclick="vistaRapida(
                                    '${producto.nombre.replace(/'/g, "\\'")}',
                                    '${imagen}',
                                    '${producto.precio_venta}',
                                    '${producto.idarticulo}',
                                    '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                )">
                                    <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                                </a>
                                <div class="tag">New</div>
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
                                            '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                        )">
                                            <i class="flaticon-view"></i>
                                        </a>
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
                }

                contenedorActivo.innerHTML += html;
            });

        })
        .catch(error => {
            console.error("❌ Error:", error);
            let contenedorActivo = document.getElementById("contenedor-nuevos-productos") || document.getElementById("products-collections-filter");
            if (contenedorActivo) {
                contenedorActivo.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">Error al cargar los productos nuevos.</div>
                </div>`;
            }
        });
}

/* ============================================================
   ⭐ FUNCIÓN VISTA RÁPIDA - REDIRIGE A PÁGINA DE PRODUCTO
============================================================ */
function vistaRapida(nombre, imagen, precio, idarticulo, descripcion) {
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
    document.getElementById("mp-stock").innerText = "En stock";
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


/* ============================================================
   🔥 CARGAR PRODUCTOS POR CATEGORÍA
============================================================ */
function cargarProductosPorCategoria(idcategoria) {

    fetch("https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_articulos_listarProductosxCategoria.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ idcategoria: idcategoria })
    })
        .then(response => response.json())
        .then(data => {

            let contenedor = document.getElementById("products-collections-filter");

            if (!contenedor) {
                console.error("❌ No se encontró el contenedor de productos");
                return;
            }

            contenedor.innerHTML = "";

            if (!data.success || !Array.isArray(data.data)) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">No hay productos disponibles en esta categoría.</div>
                </div>`;
                return;
            }

            let productos = data.data;

            if (productos.length === 0) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info">No hay productos en esta categoría.</div>
                </div>`;
                return;
            }

            // Guardar productos para el filtro
            todosLosProductos = productos;
            calcularRangoPrecios(productos);
            // Inicializar el slider después de calcular el rango
            setTimeout(inicializarFiltroPrecio, 300);

            productos.forEach(producto => {

                const imagen = construirUrlImagenProducto(producto?.imagen) || PRODUCTO_IMG_PLACEHOLDER;

                let html = `
                <div class="col-lg-4 col-sm-6">
                    <div class="single-shop-products">
                        <div class="shop-products-image">
                            <a href="javascript:void(0)"
                            onclick="vistaRapida(
                                '${producto.nombre.replace(/'/g, "\\'")}',
                                '${imagen}',
                                '${producto.precio_venta}',
                                '${producto.idarticulo}',
                                '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                            )">
                                <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                            </a>
                           
                            <ul class="shop-action">
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
                                        '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                    )">
                                        <i class="flaticon-view"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
        
                        <div class="shop-products-content">
                            <h3>
                                <a href="producto.php?id=${producto.idarticulo}">${producto.nombre}</a>
                            </h3>
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
            console.error("❌ Error al cargar productos por categoría:", error);
            let contenedor = document.getElementById("products-collections-filter");
            if (contenedor) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">Error al cargar los productos.</div>
                </div>`;
            }
        });
}

/* ============================================================
   🔥 CALCULAR RANGO DE PRECIOS DE LOS PRODUCTOS
============================================================ */
function calcularRangoPrecios(productos) {
    if (!productos || productos.length === 0) {
        precioMinimo = 0;
        precioMaximo = 100;
        return;
    }

    let precios = productos.map(p => parseFloat(p.precio_venta) || 0);
    precioMinimo = Math.floor(Math.min(...precios));
    precioMaximo = Math.ceil(Math.max(...precios));

    // Asegurar que haya al menos un rango mínimo
    if (precioMinimo === precioMaximo) {
        precioMaximo = precioMinimo + 100;
    }

    // Redondear a múltiplos de 10 para mejor UX
    precioMinimo = Math.floor(precioMinimo / 10) * 10;
    precioMaximo = Math.ceil(precioMaximo / 10) * 10;
}

/* ============================================================
   🔥 INICIALIZAR EL SLIDER DE PRECIO
============================================================ */
function inicializarFiltroPrecio() {
    if (typeof jQuery === 'undefined' || !jQuery("#range-slider").length) {
        return;
    }

    // Si aún no tenemos productos, usar valores por defecto
    let min = precioMinimo || 0;
    let max = precioMaximo || 100;

    // Destruir slider existente si existe
    if (jQuery("#range-slider").hasClass("ui-slider")) {
        jQuery("#range-slider").slider("destroy");
    }

    // Inicializar el slider con los valores reales
    jQuery("#range-slider").slider({
        range: true,
        min: min,
        max: max,
        values: [min, max],
        slide: function (event, ui) {
            jQuery("#price-amount").val("Q" + ui.values[0] + " - Q" + ui.values[1]);
        }
    });

    // Establecer el valor inicial
    jQuery("#price-amount").val("Q" + min + " - Q" + max);
}

/* ============================================================
   🔥 APLICAR FILTRO DE PRECIO
============================================================ */
function aplicarFiltroPrecio() {
    if (typeof jQuery === 'undefined' || !jQuery("#range-slider").length) {
        return;
    }

    let valores = jQuery("#range-slider").slider("values");
    let precioMin = valores[0];
    let precioMax = valores[1];

    let contenedor = document.getElementById("products-collections-filter");
    if (!contenedor) {
        console.error("❌ No se encontró el contenedor de productos");
        return;
    }

    // Filtrar productos por precio
    let productosFiltrados = todosLosProductos.filter(producto => {
        let precio = parseFloat(producto.precio_venta) || 0;
        return precio >= precioMin && precio <= precioMax;
    });

    // Limpiar contenedor
    contenedor.innerHTML = "";

    if (productosFiltrados.length === 0) {
        contenedor.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info">No hay productos en este rango de precios.</div>
            </div>`;
        return;
    }

    // Mostrar productos filtrados
    productosFiltrados.forEach(producto => {
        const imagen = construirUrlImagenProducto(producto?.imagen) || PRODUCTO_IMG_PLACEHOLDER;

        let html = `
            <div class="col-lg-4 col-sm-6">
                <div class="single-shop-products">
                    <div class="shop-products-image">
                        <a href="javascript:void(0)"
                        onclick="vistaRapida(
                            '${producto.nombre.replace(/'/g, "\\'")}',
                            '${imagen}',
                            '${producto.precio_venta}',
                            '${producto.idarticulo}',
                            '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                        )">
                            <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                        </a>
                        <div class="tag">New</div>
                        <ul class="shop-action">
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
                                <a href="javascript:void(0)">
                                    <i class="flaticon-heart"></i>
                                </a>
                            </li>
                            <li>
                                <a href="javascript:void(0)"
                                onclick="vistaRapida(
                                    '${producto.nombre.replace(/'/g, "\\'")}',
                                    '${imagen}',
                                    '${producto.precio_venta}',
                                    '${producto.idarticulo}',
                                    '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                )">
                                    <i class="flaticon-view"></i>
                                </a>
                            </li>
                        </ul>
                    </div>
        
                    <div class="shop-products-content">
                        <h3>
                            <a href="producto.php?id=${producto.idarticulo}">${producto.nombre}</a>
                        </h3>
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
}

/* ============================================================
   🔥 CARGAR PRODUCTOS POR BÚSQUEDA
============================================================ */
function cargarProductosPorBusqueda(termino) {

    fetch("https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_articulos_listarProductosxSearch.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ search: termino })
    })
        .then(response => response.json())
        .then(data => {

            let contenedor = document.getElementById("products-collections-filter");

            if (!contenedor) {
                console.error("❌ No se encontró el contenedor de productos");
                return;
            }

            contenedor.innerHTML = "";

            if (!data.success || !Array.isArray(data.data)) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning">No se encontraron productos para "${termino}".</div>
                </div>`;
                return;
            }

            let productos = data.data;

            if (productos.length === 0) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info">No se encontraron productos para "${termino}".</div>
                </div>`;
                return;
            }

            // Guardar productos para el filtro
            todosLosProductos = productos;
            calcularRangoPrecios(productos);
            setTimeout(inicializarFiltroPrecio, 300);

            // Mostrar mensaje de resultados
            const mensajeResultados = document.createElement('div');
            mensajeResultados.className = 'col-12 mb-3';
            mensajeResultados.innerHTML = `
            <div class="alert alert-info">
                <strong>Resultados de búsqueda:</strong> Se encontraron ${productos.length} producto(s) para "${termino}"
            </div>
        `;
            contenedor.appendChild(mensajeResultados);

            // Mostrar productos
            productos.forEach(producto => {
                const imagen = construirUrlImagenProducto(producto?.imagen) || PRODUCTO_IMG_PLACEHOLDER;

                let html = `
                <div class="col-lg-4 col-sm-6">
                    <div class="single-shop-products">
                        <div class="shop-products-image">
                            <a href="javascript:void(0)"
                            onclick="vistaRapida(
                                '${producto.nombre.replace(/'/g, "\\'")}',
                                '${imagen}',
                                '${producto.precio_venta}',
                                '${producto.idarticulo}',
                                '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                            )">
                            <img src="${imagen}" alt="${producto.nombre}" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='${PRODUCTO_IMG_PLACEHOLDER}';">
                            </a>
                            <div class="tag">New</div>
                            <ul class="shop-action">
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
                                    <a href="javascript:void(0)">
                                        <i class="flaticon-heart"></i>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void(0)"
                                    onclick="vistaRapida(
                                        '${producto.nombre.replace(/'/g, "\\'")}',
                                        '${imagen}',
                                        '${producto.precio_venta}',
                                        '${producto.idarticulo}',
                                        '${producto.descripcion ? producto.descripcion.replace(/'/g, "\\'") : ""}'
                                    )">
                                        <i class="flaticon-view"></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
        
                        <div class="shop-products-content">
                            <h3>
                                <a href="producto.php?id=${producto.idarticulo}">${producto.nombre}</a>
                            </h3>
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
            console.error("❌ Error al buscar productos:", error);
            let contenedor = document.getElementById("products-collections-filter");
            if (contenedor) {
                contenedor.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger">Error al buscar los productos. Por favor intenta de nuevo.</div>
                </div>`;
            }
        });
}








