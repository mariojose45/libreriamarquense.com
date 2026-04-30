<?php
// SEO para la pagina de producto
$seo_title = "Producto - TI-CELL | Celulares y accesorios";
$seo_description = "Consulta detalles de productos en TI-CELL: celulares, accesorios, repuestos y tecnologia para tu dia a dia.";
$seo_keywords = "TI-CELL, producto celular, accesorios para telefonos, repuestos, tienda de tecnologia Guatemala";

include 'head.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Cargar categorias desde la API (igual que en head.php)
include "assets/php/rutas.php";
$response = getApi($url_listar_categorias);
$data = json_decode($response, true);
$categorias = $data["data"] ?? [];


// Obtener y validar idarticulo de la URL
$idarticulo = 0;

if (isset($_GET['id'])) {
    // Sanitizar y validar el ID
    $id_raw = $_GET['id'];

    // Solo permitir parametros enteros positivos
    if (preg_match('/^[0-9]+$/', $id_raw)) {
        $idarticulo = intval($id_raw);

        // Validar rango razonable (1 a 999999)
        if ($idarticulo < 1 || $idarticulo > 999999) {
            // ID fuera de rango válido
            header("HTTP/1.0 404 Not Found");
            header("Location: index.php?error=producto_no_encontrado");
            exit();
        }

        // Verificar que el producto existe en la API antes de mostrar la pagina
        $api_url = "https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_articulos_listarid.php";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['idarticulo' => $idarticulo]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $api_response = curl_exec($ch);
        curl_close($ch);

        $producto_data = json_decode($api_response, true);

        // Si el producto no existe o hay error, redirigir
        if (
            !$producto_data || !isset($producto_data['success']) || !$producto_data['success'] ||
            !isset($producto_data['data']) || empty($producto_data['data'])
        ) {
            header("HTTP/1.0 404 Not Found");
            header("Location: index.php?error=producto_no_encontrado");
            exit();
        }
    } else {
        // ID invalido (contiene caracteres no numericos)
        header("HTTP/1.0 400 Bad Request");
        header("Location: index.php?error=id_invalido");
        exit();
    }
} else {
    // No se proporciono ID
    header("HTTP/1.0 400 Bad Request");
    header("Location: index.php?error=id_faltante");
    exit();
}

?>

<style>
    .main-slider-content,
    .main-slider-content b,
    .main-slider-content h1,
    .main-slider-content p,
    .main-slider-content a {
        color: #fff !important;
    }


    .main-slider-content .default-btn {
        background-color: #1A2697;
        /* azul institucional */
        border-color: #1A2697;
        color: #fff !important;
    }

    .main-slider-content .default-btn:hover {
        background-color: #101A5C;
        border-color: #101A5C;
    }
</style>



<!-- End Support Area -->
<style>
    .arrivals-products-image {
        width: 100%;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        position: relative;
    }

    .arrivals-products-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    .arrivals-products-content h3 {
        font-size: 18px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        /* Oculta texto excesivo */
        text-overflow: ellipsis;
        margin-bottom: 5px;
    }

    .shop-products-image {
        width: 100%;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        position: relative;
    }

    .shop-products-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
    }

    .shop-products-content h3 {
        font-size: 18px;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        /* Oculta texto excesivo */
        text-overflow: ellipsis;
        margin-bottom: 5px;
    }

    /* Estilos para el boton de limpiar busquedad */
    .search-form {
        position: relative;
    }

    .btn-limpiar-busqueda {
        position: absolute;
        right: 45px;
        top: 50%;
        transform: translateY(-50%);
        background: #B73639;
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        padding: 0;
        font-size: 16px;
        transition: all 0.3s;
        z-index: 10;
    }

    .btn-limpiar-busqueda:hover {
        background: #8F1F24;
        transform: translateY(-50%) scale(1.1);
    }

    .btn-limpiar-busqueda i {
        line-height: 1;
    }

    /* Estilos para el slider de imagenes del producto */
    .main-products-image {
        position: relative;
    }

    /* Imagen principal arriba */
    .slider-for {
        margin-bottom: 15px;
    }

    .slider-for img {
        width: 100%;
        height: auto;
        max-height: 500px;
        object-fit: contain;
        display: block;
        border-radius: 8px;
        background: #fff;
    }

    /* Miniaturas horizontales abajo */
    .slider-nav {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 10px;
        justify-content: center;
        align-items: center;
        margin-top: 15px;
        width: 100%;
    }

    .slider-nav>div {
        width: 80px !important;
        height: 80px !important;
        min-width: 80px !important;
        min-height: 80px !important;
        border: 2px solid transparent;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s;
        opacity: 0.7;
        background: #f5f5f5;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .slider-nav>div:hover {
        opacity: 1;
        border-color: #1A2697;
        transform: scale(1.05);
    }

    .slider-nav>div.active {
        border-color: #1A2697;
        opacity: 1;
        box-shadow: 0 2px 8px rgba(26, 38, 151, 0.3);
    }

    .slider-nav>div img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Responsive para Moviles */
    @media (max-width: 768px) {
        .slider-nav {
            overflow-x: auto;
            padding-bottom: 10px;
            justify-content: flex-start;
        }

        .slider-nav>div {
            width: 70px;
            height: 70px;
        }

        .slider-for img {
            max-height: 350px;
        }
    }

    .slider-for.slick-initialized>div {
        display: block !important;
    }


    /* =========================================
   MINIATURAS PRODUCTO - ESTILO DESEADO
========================================= */

    #slider-nav {
        display: flex !important;
        flex-direction: row !important;
        gap: 10px;
        justify-content: center;
        align-items: center;
        margin-top: 15px;
        width: 100%;
    }

    /* Cada miniatura */
    #slider-nav .thumb-item {
        width: 70px;
        height: 70px;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        opacity: 0.6;
        transition: all 0.25s ease;
        background: #f5f5f5;
    }

    /* Imagen dentro */
    #slider-nav .thumb-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Hover */
    #slider-nav .thumb-item:hover {
        opacity: 1;
        transform: scale(1.05);
    }

    /* Activa */
    #slider-nav .thumb-item.active {
        border-color: #1A2697;
        opacity: 1;
        box-shadow: 0 2px 8px rgba(26, 38, 151, .35);
    }

    /* Mobile */
    @media (max-width: 768px) {
        #slider-nav {
            overflow-x: auto;
            justify-content: flex-start;
            padding-bottom: 8px;
        }

        #slider-nav .thumb-item {
            width: 60px;
            height: 60px;
            flex-shrink: 0;
        }

        .shop-area .col-lg-4.col-md-12 {
            display: none !important;
        }

        .shop-area .col-lg-8.col-md-12 {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }
    }
</style>

<!-- Start Page Banner -->
<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>Tienda</h2>

            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>Tienda</li>
            </ul>
        </div>
    </div>
</div>
<!-- End Page Banner -->
<!-- Start Shop Area -->
<section class="shop-area bg-ffffff pt-50 pb-50">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-12">
                <aside class="widget-area">


                    <div class="widget widget_categories">
                        <h3 class="widget-title">Categorías</h3>

                        <ul class="categories">
                            <?php
                            // Iconos disponibles para las cateroias (Boxicons - belleza y cuidado personal)
                            $iconos = [
                                "bx bx-spa",               // Spa / tratamientos de belleza
                                "bx bx-heart",             // Cuidado personal / amor propio
                                "bx bx-star",              // Calidad / productos destacados
                                "bx bx-palette",           // Maquillaje / colores
                                "bx bx-droplet",           // Hidrataci / cuidado de la piel
                                "bx bx-shield",            // protección / cuidado
                                "bx bx-check-circle",      // Verificado / calidad garantizada
                                "bx bx-sparkles"           // Brillo / belleza / glamour
                            ];

                            $index = 0;
                            foreach ($categorias as $cat):
                                // Solo mostrar categorias activas (condicion == 1)
                                if (isset($cat['condicion']) && $cat['condicion'] == 1):
                                    $icono = $iconos[$index % count($iconos)];
                                    ?>
                                    <li>
                                        <a href="tienda.php?categoria=<?= $cat['idcategoria'] ?>" class="nav-link">
                                            <i class="<?= $icono ?>"></i>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </a>
                                    </li>
                                    <?php
                                    $index++;
                                endif;
                            endforeach;

                            // Si no hay categorias, mostrar mensaje
                            if (empty($categorias) || $index == 0):
                                ?>
                                <li>
                                    <div class="alert alert-info" style="padding: 10px; margin: 0;">
                                        No hay categorias disponibles.
                                    </div>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <div class="widget widget_price">
                        <h3 class="widget-title">Precio</h3>

                        <form class="price-range-content">
                            <div class="price-range-bar" id="range-slider"></div>
                            <div class="price-range-filter">
                                <div class="price-range-filter-item d-flex align-items-center order-1 order-xl-2">
                                    <h4>Rango:</h4>
                                    <input type="text" id="price-amount" readonly>
                                </div>

                                <div class="price-range-filter-item price-range-filter-button order-2 order-xl-1">
                                    <button type="button" class="btn btn-red btn-icon">Filter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </aside>
            </div>

            <div class="col-lg-8 col-md-12">
                <!-- Start Products Details Area -->
                <section class="products-details-area ptb-50">
                    <div class="container">
                        <div id="producto-loading" style="text-align: center; padding: 50px;">
                            <p>Cargando producto...</p>
                        </div>
                        <div id="producto-error" style="display: none; text-align: center; padding: 50px;">
                            <p style="color: red;">Error al cargar el producto. Por favor, intenta de nuevo.</p>
                        </div>
                        <div id="producto-details" class="products-details-desc" style="display: none;">
                            <div class="row align-items-center">
                                <div class="col-lg-6 col-md-6">
                                    <div class="main-products-image">
                                        <div class="slider slider-for" id="slider-for"></div>
                                        <div class="slider slider-nav" id="slider-nav"></div>
                                    </div>
                                </div>

                                <div class="col-lg-6 col-md-6">
                                    <div class="product-content content-two">
                                        <h3 id="producto-nombre"></h3>

                                        <div class="product-review">
                                            <div class="rating">
                                                <i class='bx bxs-star'></i>
                                                <i class='bx bxs-star'></i>
                                                <i class='bx bxs-star'></i>
                                                <i class='bx bxs-star'></i>
                                                <i class='bx bxs-star'></i>
                                            </div>
                                        </div>

                                        <div class="price">
                                            <span class="new-price" id="producto-precio"></span>
                                        </div>
                                        <p id="producto-descripcion"></p>

                                        <ul class="products-info">
                                            <li><span>Disponibilidad:</span> <span id="producto-stock">En stock</span>
                                            </li>
                                            <li><span>SKU:</span> <span id="producto-sku"></span></li>
                                            <li><span>Código:</span> <span id="producto-codigo"></span></li>
                                            <li><span>Categoría:</span> <span id="producto-categoria"></span></li>
                                        </ul>

                                        <div class="product-quantities">
                                            <span>Cantidad:</span>

                                            <div class="input-counter">
                                                <span class="minus-btn" onclick="cambiarCantidad(-1)">
                                                    <i class='bx bx-minus'></i>
                                                </span>
                                                <input type="text" id="producto-cantidad" value="1">
                                                <span class="plus-btn" onclick="cambiarCantidad(1)">
                                                    <i class='bx bx-plus'></i>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="product-add-to-cart">
                                            <button type="button" class="default-btn"
                                                onclick="agregarAlCarritoDesdeProducto()">
                                                <i class="flaticon-shopping-cart"></i>
                                                Agregar al carrito
                                                <span></span>
                                            </button>
                                        </div>

                                        <div class="products-share">
                                            <ul class="social">
                                                <li><span>Compartir:</span></li>
                                                <li>
                                                    <button id="btn-compartir-whatsapp-producto"
                                                        class="mp-btn-compartir mp-whatsapp"
                                                        onclick="compartirWhatsAppProducto(event)"
                                                        style="background: #466934; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
                                                        title="Compartir por WhatsApp">
                                                        <i class='bx bxl-whatsapp'></i>
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="products-details-tabs" id="producto-tabs" style="display: none;">
                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                <li class="nav-item"><a class="nav-link active" id="description-tab"
                                        data-bs-toggle="tab" href="#description" role="tab"
                                        aria-controls="description">Descripción</a></li>
                                <li class="nav-item"><a class="nav-link" id="information-tab" data-bs-toggle="tab"
                                        href="#information" role="tab" aria-controls="information">Información</a></li>
                            </ul>

                            <div class="tab-content" id="myTabContent">
                                <div class="tab-pane fade show active" id="description" role="tabpanel">
                                    <h2>Descripción del Producto</h2>
                                    <div id="producto-descripcion-completa"></div>
                                </div>

                                <div class="tab-pane fade" id="information" role="tabpanel">
                                    <ul class="information-list" id="producto-informacion"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- End Products Details Area -->

            </div>
        </div>
    </div>
    </div>
</section>
<!-- End Shop Area -->














<!-- Start Footer Area -->

<?php include 'footer.php'; ?>
<script type="text/javascript" src="assets/js/sweatlert.js"></script>
<script type="text/javascript" src="assets/js/producto.js"></script>
<script>
    // Variables globales para el producto
    let productoActual = null;
    let imagenPrincipal = null; // Imagen principal del producto
    let imagenesProducto = []; // Fotos adicionales de la API
    let imagenIndex = 0; // Índice de la imagen actual mostrada

    // ============================================================
    // 🔒 VALIDAR ID DEL PRODUCTO
    // ============================================================
    const IMAGEN_PRODUCTO_BASE = "https://ssl.sol.sistemasolgt.com/ticel/files/articulos/";
    const IMAGEN_PRODUCTO_PLACEHOLDER = "assets/img/404.png";

    function construirUrlImagenProducto(nombreArchivo) {
        const nombre = (nombreArchivo ?? "").toString().trim();
        return nombre ? `${IMAGEN_PRODUCTO_BASE}${encodeURIComponent(nombre)}` : "";
    }

    function validarIdProducto(id) {
        // Solo permitir números enteros positivos
        if (!id || typeof id !== 'string' && typeof id !== 'number') {
            return false;
        }

        // Convertir a string y validar formato
        const idStr = String(id).trim();

        // Verificar que solo contiene números
        if (!/^[0-9]+$/.test(idStr)) {
            return false;
        }

        // Convertir a número y validar rango
        const idNum = parseInt(idStr, 10);
        if (isNaN(idNum) || idNum < 1 || idNum > 999999) {
            return false;
        }

        return true;
    }

    // ============================================================
    // CARGAR PRODUCTO AL INICIAR LA PÁGINA
    // ============================================================
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const idarticulo = urlParams.get('id');

        // Validar ID antes de procesar
        if (!idarticulo || !validarIdProducto(idarticulo)) {
            document.getElementById('producto-loading').style.display = 'none';
            document.getElementById('producto-error').style.display = 'block';
            document.getElementById('producto-error').innerHTML =
                '<p style="color: red;">ID de producto inválido. Por favor, selecciona un producto válido.</p>' +
                '<p><a href="index.php" class="default-btn">Volver al inicio</a></p>';

            // Limpiar la URL para evitar que se vea el ID inválido
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, document.title, 'producto.php');
            }
            return;
        }

        // Sanitizar ID (solo números)
        const idSanitizado = parseInt(idarticulo, 10);
        cargarProducto(idSanitizado);
    });

    // ============================================================
    // CARGAR DATOS DEL PRODUCTO DESDE LA API (con validación)
    // ============================================================
    function cargarProducto(idarticulo) {
        // Validar ID nuevamente antes de hacer la petición
        if (!validarIdProducto(idarticulo)) {
            mostrarError('ID de producto inválido.');
            return;
        }

        // Sanitizar ID para la petición
        const idSanitizado = parseInt(idarticulo, 10);

        fetch("https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_articulos_listarid.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ idarticulo: idSanitizado })
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                // Validar respuesta de la API
                if (!data || !data.success || !data.data || data.data.length === 0) {
                    mostrarError('No se encontró el producto. El ID puede ser inválido o el producto no existe.');
                    // Limpiar URL si el producto no existe
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState({}, document.title, 'producto.php');
                    }
                    return;
                }

                productoActual = data.data[0];

                // Validar que el ID del producto coincide con el solicitado
                if (productoActual.idarticulo != idSanitizado) {
                    console.warn('⚠️ El ID del producto no coincide');
                    mostrarError('Error de validación del producto.');
                    return;
                }

                cargarFotosProducto(idSanitizado);
            })
            .catch(error => {
                console.error('Error:', error);
                mostrarError('Error al cargar el producto. Por favor, intenta de nuevo.');
            });
    }

    // ============================================================
    // CARGAR FOTOS DEL PRODUCTO (con validación)
    // ============================================================
    function cargarFotosProducto(idarticulo) {
        // Validar ID antes de cargar fotos
        if (!validarIdProducto(idarticulo)) {
            console.error('ID inválido para cargar fotos');
            return;
        }

        const idSanitizado = parseInt(idarticulo, 10);
        // Guardar imagen principal por separado
        imagenPrincipal = construirUrlImagenProducto(productoActual.imagen) || IMAGEN_PRODUCTO_PLACEHOLDER;

        // Limpiar array de fotos adicionales
        imagenesProducto = [];
        imagenIndex = 0; // Empezar con la imagen principal

        console.log('🖼️ Cargando fotos para producto:', idarticulo);
        console.log('🖼️ Imagen principal:', imagenPrincipal);

        // Cargar fotos desde la API
        fetch("https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_mostrarfotosproducto.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ idarticulo: idSanitizado })
        })
            .then(r => r.json())
            .then(data => {
                console.log('📸 Respuesta de API de fotos:', data);

                if (data.success && data.imagenes && Array.isArray(data.imagenes)) {
                    console.log('✅ Se encontraron', data.imagenes.length, 'fotos adicionales');

                    // Procesar y ordenar las imágenes
                    let imagenesProcesadas = [];

                    data.imagenes.forEach((img, index) => {
                        // La API devuelve: { "id": "2", "ruta": "files/imagenesarticulos/...", "orden": "0" }
                        let ruta = img.ruta;

                        if (ruta && typeof ruta === 'string') {
                            let url = '';

                            // Si la ruta ya incluye la URL completa
                            if (ruta.startsWith('http://') || ruta.startsWith('https://')) {
                                url = ruta;
                            }
                            // Si empieza con /, agregar dominio completo
                            else if (ruta.startsWith('/')) {
                                url = "https://ssl.sol.sistemasolgt.com" + ruta;
                            }
                            // Si es relativa (como "files/imagenesarticulos/..."), construir la URL completa
                            else {
                                // Asegurar que no tenga barra inicial
                                ruta = ruta.replace(/^\//, '');
                                url = "https://ssl.sol.sistemasolgt.com/ticel/" + ruta;
                            }

                            // Codificar espacios y caracteres especiales en la URL
                            url = url.replace(/ /g, '%20');

                            if (url) {
                                imagenesProcesadas.push({
                                    url: url,
                                    orden: parseInt(img.orden) || index,
                                    id: img.id
                                });
                                console.log(`  📷 Foto ${index + 1} (orden ${img.orden || index}): ${url}`);
                            }
                        } else {
                            console.warn(`⚠️ Imagen ${index + 1} no tiene ruta válida:`, img);
                        }
                    });

                    // Ordenar por el campo "orden" (ascendente: menor a mayor)
                    imagenesProcesadas.sort((a, b) => {
                        // Primero ordenar por el campo "orden"
                        if (a.orden !== b.orden) {
                            return a.orden - b.orden;
                        }
                        // Si tienen el mismo orden, ordenar por ID
                        return (parseInt(a.id) || 0) - (parseInt(b.id) || 0);
                    });

                    // Limpiar el array antes de agregar las imágenes ordenadas
                    imagenesProducto = [];

                    // Agregar las imágenes ordenadas al array (solo las de la API, no la principal)
                    imagenesProcesadas.forEach(img => {
                        imagenesProducto.push(img.url);
                    });

                    console.log('📋 Imágenes ordenadas por campo "orden":');
                    imagenesProducto.forEach((url, idx) => {
                        const img = imagenesProcesadas.find(i => i.url === url);
                        console.log(`  ${idx + 1}. Orden: ${img?.orden || 'N/A'}, ID: ${img?.id || 'N/A'} - ${url.split('/').pop()}`);
                    });
                } else {
                    console.log('⚠️ No se encontraron fotos adicionales en la API');

                    if (data.imagenes && !Array.isArray(data.imagenes)) {
                        console.log('⚠️ data.imagenes no es un array:', typeof data.imagenes);
                    }
                    if (!data.success) {
                        console.log('⚠️ API devolvió success: false');
                    }
                }

                console.log('🖼️ Imagen principal:', imagenPrincipal);
                console.log('🖼️ Fotos adicionales:', imagenesProducto.length);
                console.log('🖼️ Total de imágenes disponibles:', imagenesProducto.length + 1);
                mostrarProducto();
            })
            .catch(error => {
                console.error('❌ Error al cargar fotos:', error);
                mostrarProducto(); // Mostrar producto aunque falle la carga de fotos
            });
    }

    // ============================================================
    // MOSTRAR PRODUCTO EN LA PÁGINA
    // ============================================================
    function mostrarProducto() {
        if (!productoActual) return;

        // Ocultar loading y error
        document.getElementById('producto-loading').style.display = 'none';
        document.getElementById('producto-error').style.display = 'none';

        // Mostrar detalles
        document.getElementById('producto-details').style.display = 'block';
        document.getElementById('producto-tabs').style.display = 'block';

        // Llenar información básica
        document.getElementById('producto-nombre').textContent = productoActual.nombre || 'Sin nombre';
        document.getElementById('producto-precio').textContent = 'Q' + parseFloat(productoActual.precio_venta || 0).toFixed(2);
        document.getElementById('producto-descripcion').textContent = productoActual.descripcion || 'Sin descripción disponible.';
        document.getElementById('producto-stock').textContent = parseFloat(productoActual.stock || 0) > 0 ? 'En stock' : 'Agotado';
        document.getElementById('producto-sku').textContent = productoActual.idarticulo || 'N/A';
        document.getElementById('producto-codigo').textContent = productoActual.codigo || 'N/A';
        document.getElementById('producto-categoria').textContent = productoActual.categoria || 'N/A';

        // Llenar descripción completa
        document.getElementById('producto-descripcion-completa').innerHTML =
            productoActual.descripcion ? `<p>${productoActual.descripcion}</p>` : '<p>No hay descripción disponible para este producto.</p>';

        // Llenar información adicional
        let infoHtml = `
        <li><strong>Nombre:</strong> <span>${productoActual.nombre || 'N/A'}</span></li>
        <li><strong>Código:</strong> <span>${productoActual.codigo || 'N/A'}</span></li>
        <li><strong>Categoría:</strong> <span>${productoActual.categoria || 'N/A'}</span></li>
        <li><strong>Stock:</strong> <span>${productoActual.stock || '0'}</span></li>
        <li><strong>Precio:</strong> <span>Q${parseFloat(productoActual.precio_venta || 0).toFixed(2)}</span></li>
    `;
        document.getElementById('producto-informacion').innerHTML = infoHtml;

        // Mostrar imágenes
        mostrarImagenes();
    }

    // ============================================================
    // MOSTRAR IMÁGENES DEL PRODUCTO
    // ============================================================
    function mostrarImagenes() {
        if (!imagenPrincipal) {
            console.log('⚠️ No hay imagen principal para mostrar');
            return;
        }

        console.log('🖼️ Mostrando imagen principal y', imagenesProducto.length, 'fotos adicionales');

        let sliderNav = document.getElementById('slider-nav');
        let sliderFor = document.getElementById('slider-for');

        if (!sliderNav || !sliderFor) {
            console.error('❌ No se encontraron los elementos del slider');
            return;
        }

        // Limpiar contenedores
        sliderNav.innerHTML = '';
        sliderFor.innerHTML = '';

        // ============================================
        // MOSTRAR IMAGEN PRINCIPAL (GRANDE)
        // ============================================
        let imgPrincipal = document.createElement('img');
        imgPrincipal.id = 'imagen-principal-actual';
        imgPrincipal.src = imagenPrincipal;
        imgPrincipal.alt = productoActual.nombre || 'Producto';
        imgPrincipal.style.width = '100%';
        imgPrincipal.style.height = 'auto';
        imgPrincipal.style.maxHeight = '500px';
        imgPrincipal.style.objectFit = 'contain';
        imgPrincipal.style.display = 'block';
        imgPrincipal.onerror = function () {
            console.error('❌ Error al cargar imagen principal:', imagenPrincipal);
            this.onerror = null;
            this.src = IMAGEN_PRODUCTO_PLACEHOLDER;
        };
        sliderFor.appendChild(imgPrincipal);

        // ============================================
        // MOSTRAR MINIATURAS (FOTOS DE LA API)
        // ============================================
        // Agregar miniatura de la imagen principal primero
        let divNavPrincipal = document.createElement('div');
        divNavPrincipal.className = 'thumb-item active';
        let imgNavPrincipal = document.createElement('img');
        imgNavPrincipal.src = imagenPrincipal;
        imgNavPrincipal.alt = 'Imagen principal';
        imgNavPrincipal.onclick = function () {
            cambiarImagen(-1); // -1 indica imagen principal
        };
        imgNavPrincipal.onerror = function () {
            this.onerror = null;
            this.src = IMAGEN_PRODUCTO_PLACEHOLDER;
        };
        divNavPrincipal.appendChild(imgNavPrincipal);
        sliderNav.appendChild(divNavPrincipal);

        // Agregar miniaturas de las fotos de la API
        imagenesProducto.forEach((img, index) => {
            let divNav = document.createElement('div');
            divNav.className = 'thumb-item';
            let imgNav = document.createElement('img');
            imgNav.src = img;
            imgNav.alt = `Foto ${index + 1}`;
            imgNav.onclick = function () {
                cambiarImagen(index);
            };
            imgNav.onerror = function () {
                console.error('❌ Error al cargar miniatura:', img);
                this.onerror = null;
                this.src = IMAGEN_PRODUCTO_PLACEHOLDER;
            };
            divNav.appendChild(imgNav);
            sliderNav.appendChild(divNav);
        });

        console.log('✅ Imágenes mostradas correctamente');
    }

    // ============================================================
    // CAMBIAR IMAGEN AL HACER CLICK EN MINIATURA
    // ============================================================
    function cambiarImagen(index) {
        let imgPrincipal = document.getElementById('imagen-principal-actual');
        if (!imgPrincipal) return;

        // index = -1 significa imagen principal, index >= 0 significa foto de la API
        if (index === -1) {
            // Mostrar imagen principal
            imgPrincipal.src = imagenPrincipal;
            imagenIndex = -1;
            console.log('🖼️ Cambiando a imagen principal');
        } else if (index >= 0 && index < imagenesProducto.length) {
            // Mostrar foto de la API
            imgPrincipal.src = imagenesProducto[index];
            imagenIndex = index;
            console.log('🖼️ Cambiando a foto', index + 1, 'de la API');
        }

        // Actualizar clase activa en miniaturas
        let sliderNav = document.getElementById('slider-nav');
        if (sliderNav) {
            for (let i = 0; i < sliderNav.children.length; i++) {
                let thumbItem = sliderNav.children[i];
                // i = 0 es la imagen principal, i > 0 son las fotos de la API
                if ((index === -1 && i === 0) || (index >= 0 && i === index + 1)) {
                    thumbItem.classList.add('active');
                    thumbItem.style.border = '2px solid #1A2697';
                    thumbItem.style.opacity = '1';
                } else {
                    thumbItem.classList.remove('active');
                    thumbItem.style.border = '2px solid transparent';
                    thumbItem.style.opacity = '0.7';
                }
            }
        }
    }

    // ============================================================
    // CAMBIAR CANTIDAD
    // ============================================================
    function cambiarCantidad(n) {
        let input = document.getElementById('producto-cantidad');
        let val = parseInt(input.value) + n;
        if (val < 1) val = 1;
        input.value = val;
    }

    // ============================================================
    // AGREGAR AL CARRITO DESDE PRODUCTO
    // ============================================================
    function agregarAlCarritoDesdeProducto() {
        if (!productoActual) return;

        let cantidad = parseInt(document.getElementById('producto-cantidad').value) || 1;
        let imagen = imagenesProducto[0] || imagenPrincipal || IMAGEN_PRODUCTO_PLACEHOLDER;

        agregarAlCarrito(
            productoActual.idarticulo,
            productoActual.nombre,
            productoActual.precio_venta,
            imagen,
            productoActual.descripcion || ''
        );

        // Mostrar mensaje 
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: '¡Agregado!',
                text: 'El producto se agregó al carrito',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }

    // ============================================================
    // COMPARTIR POR WHATSAPP
    // ============================================================
    function compartirWhatsAppProducto(e) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        if (!productoActual) return false;

        const url = window.location.href;
        const mensaje = encodeURIComponent(
            `🛍️ *${productoActual.nombre}*\n\n` +
            `💰 Precio: *Q${parseFloat(productoActual.precio_venta || 0).toFixed(2)}*\n\n` +
            `📦 Disponibilidad: ${parseFloat(productoActual.stock || 0) > 0 ? 'En stock' : 'Agotado'}\n\n` +
            `🔗 Ver más detalles:\n${url}\n\n` +
            `_Librería Marquense - Útiles escolares y papelería_`
        );

        const whatsappUrl = `https://wa.me/50255910533?text=${mensaje}`;

        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

        if (isMobile) {
            window.location.href = whatsappUrl;
        } else {
            window.open(whatsappUrl, '_blank');
        }

        return false;
    }

    // ============================================================
    // MOSTRAR ERROR
    // ============================================================
    function mostrarError(mensaje) {
        document.getElementById('producto-loading').style.display = 'none';
        document.getElementById('producto-error').style.display = 'block';
        document.getElementById('producto-error').innerHTML = `<p style="color: red;">${mensaje}</p>`;
    }

    // ============================================================
    // FUNCION PARA BUSCAR DESDE EL SIDEBAR
    // ============================================================
    function buscarProductosSidebar(event) {
        event.preventDefault();
        const searchInput = document.getElementById('search-sidebar');
        const termino = searchInput.value.trim();

        if (!termino) {
            alert('Por favor ingresa un término de búsqueda');
            return;
        }

        window.location.href = 'tienda.php?buscar=' + encodeURIComponent(termino);
    }

    // ============================================================
    // FUNCION PARA LIMPIAR LA BARRA DE BUSQUEDAD
    // ============================================================
    function limpiarBusqueda() {
        const searchInput = document.getElementById('search-sidebar');
        if (searchInput) {
            searchInput.value = '';
        }
        window.location.href = 'tienda.php';
    }

    // Permitir búsqueda con Enter en el sidebar
    document.addEventListener('DOMContentLoaded', function () {
        const searchInputSidebar = document.getElementById('search-sidebar');
        if (searchInputSidebar) {
            searchInputSidebar.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarProductosSidebar(e);
                }
            });
        }
    });
</script>
