<?php
// SEO para la pagina de producto
$seo_title = "Producto - Librería Marquense | Libros, Papelería, Artículos Escolares y de Oficina";
$seo_description = "Consulta detalles de productos en Librería Marquense: útiles escolares, papelería, libros, material didáctico y artículos de oficina.";
$seo_keywords = "Librería Marquense, producto escolar, útiles escolares, papelería, libros, material didáctico, productos de oficina Guatemala";
$current_page = basename($_SERVER['PHP_SELF']);

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

        // El detalle se carga en el navegador. No redirigir aqui si el API de detalle
        // responde vacio, porque los listados pueden tener articulos validos que ese
        // endpoint no devuelve de forma consistente.
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

include 'head.php';

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
        background: #B42A27;
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
        background: #9F211F;
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

    .product-presentations {
        margin: 18px 0 20px;
    }

    .product-presentations__label {
        display: block;
        color: #222;
        font-weight: 700;
        margin-bottom: 10px;
    }

    .product-presentations__options {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .product-presentation-option {
        border: 1px solid #166B38;
        background: #fff;
        color: #166B38;
        border-radius: 6px;
        padding: 9px 14px;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.2;
        cursor: pointer;
        transition: background-color .2s ease, color .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .product-presentation-option:hover:not(:disabled),
    .product-presentation-option.is-selected {
        background: #166B38;
        color: #fff;
        box-shadow: 0 6px 16px rgba(22, 107, 56, .18);
    }

    .product-presentation-option:disabled {
        border-color: #b8c5bd;
        color: #7b8b81;
        background: #f4f7f5;
        cursor: not-allowed;
        opacity: .72;
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
                            // Iconos por categoría - Librería Marquense
                            $iconosPorCategoria = [
                                "BOLIGRAFOS" => "bx bx-pen",
                                "PAPELERIA" => "bx bx-file",
                                "TEXTO ESCOLAR" => "bx bx-book",
                                "DIDACTICO INFANTIL" => "bx bx-book",
                                "MARCADOR" => "bx bx-pencil",
                                "TECNOLOGIA" => "bx bx-laptop",
                                "CONTABILIDAD Y AUDITORIA" => "bx bx-calculator",
                                "NOVELA Y LITERATURA GENERAL" => "bx bx-book-open",
                                "JURIDICOS" => "bx bx-shield",
                                "AUTOAYUDA" => "bx bx-heart",
                                "ARTICULOS DE CORTE" => "bx bx-cut",
                                "ADHESIVOS" => "bx bx-purchase-tag",
                                "OFICINA" => "bx bx-briefcase",
                                "BIBLIAS" => "bx bx-book-reader",
                                "TINTAS Y SUMINISTROS" => "bx bx-printer",
                                "LIBRO INFANTIL" => "bx bx-happy",
                                "MANUALIDADES" => "bx bx-palette",
                                "ESCOLAR" => "bx bx-library",
                                "OTROS" => "bx bx-category",
                                "CUADERNOS" => "bx bx-notepad",
                                "IDIOMAS" => "bx bx-globe",
                                "SOCIOLOGIA" => "bx bx-group",
                                "FACTURACION" => "bx bx-receipt",
                                "POLITICA" => "bx bx-building",
                                "FIESTA Y DECORACION" => "bx bx-party",
                                "DICCIONARIO" => "bx bx-book-content",
                                "SEXOLOGIA" => "bx bx-heart-circle",
                                "LIBROS DE COLOREAR" => "bx bx-brush",
                                "ENTRETENIMIENTO" => "bx bx-joystick",
                                "EDICIONES ANTIGUAS" => "bx bx-time-five",
                                "LIMPIEZA Y CAFETERIA" => "bx bx-coffee",
                                "TEMPORADA" => "bx bx-calendar-star",
                                "ANTROPOLOGIA/MAYA" => "bx bx-map-alt",
                                "LITERATURA" => "bx bx-book-open",
                                "ADMINISTRACION, LIDERAZGO Y MARKETING" => "bx bx-line-chart",
                                "EDICIONES ATRASADAS" => "bx bx-history",
                                "FISICA" => "bx bx-atom",
                                "TEATRO" => "bx bx-mask",
                                "POESIA" => "bx bx-edit",
                                "ECONOMIA" => "bx bx-bar-chart-alt-2",
                                "HISTORIA DE GUATEMALA" => "bx bx-landscape",
                                "METODOLOGIA" => "bx bx-list-check",
                                "RELIGIOSOS" => "bx bx-church",
                                "QUIMICA" => "bx bx-test-tube",
                                "ESTADISTICA" => "bx bx-pie-chart-alt-2",
                                "MATEMATICA" => "bx bx-math",
                                "TEXTOS EN INGLES" => "bx bx-world",
                                "GASTRONOMIA" => "bx bx-dish",
                                "PSICOLOGIA" => "bx bx-brain",
                                "COMUNICACION" => "bx bx-message-dots",
                                "FILOSOFIA" => "bx bx-bulb",
                                "BOTANICA" => "bx bx-leaf",
                                "GRAMATICA" => "bx bx-text",
                                "HISTORIA GENERAL" => "bx bx-history",
                                "EMPAQUE" => "bx bx-package",
                                "ARTE Y DIBUJO ESCOLAR" => "bx bx-palette",
                                "PROMOCIONALES" => "bx bx-gift",
                                "BIOGRAFIA" => "bx bx-user",
                                "ESPIRITUALIDAD" => "bx bx-donate-heart",
                                "ARQUITECTURA" => "bx bx-building-house",
                                "PEDAGOGIA" => "bx bx-chalkboard",
                                "MONOGRAFIA" => "bx bx-file-find",
                                "INGENIERIA" => "bx bx-cog",
                                "ETICA Y MORAL" => "bx bx-check-shield",
                                "SALUD Y BIENESTAR" => "bx bx-plus-medical",
                                "BIOLOGIA" => "bx bx-dna",
                                "LIBROS DE ARTE" => "bx bx-paint",
                                "TURISMO" => "bx bx-map",
                                "FOTOGRAFIA" => "bx bx-camera",
                                "VARIOS" => "bx bx-category-alt",
                                "MANGA COMICS" => "bx bx-book-heart",
                                "MUSICA" => "bx bx-music",
                                "TECNICO" => "bx bx-wrench",
                                "MEDICINA" => "bx bx-plus-medical",
                                "N/A" => "bx bx-category",
                                "HIGIENE CUIDADO PERSONAL" => "bx bx-health"
                            ];

                            $index = 0;
                            $limiteCategoriasSidebar = 60;
                            $categoriasMostradas = [];

                            foreach ($categorias as $cat):
                                if (isset($cat['condicion']) && $cat['condicion'] == 1):

                                    $nombreOriginal = trim((string) ($cat['nombre'] ?? ''));

                                    $nombreCategoria = strtoupper($nombreOriginal);
                                    $nombreCategoria = strtr($nombreCategoria, [
                                        'Á' => 'A',
                                        'É' => 'E',
                                        'Í' => 'I',
                                        'Ó' => 'O',
                                        'Ú' => 'U',
                                        'Ü' => 'U',
                                        'Ñ' => 'N',
                                        'á' => 'A',
                                        'é' => 'E',
                                        'í' => 'I',
                                        'ó' => 'O',
                                        'ú' => 'U',
                                        'ü' => 'U',
                                        'ñ' => 'N'
                                    ]);

                                    $nombreCategoria = preg_replace('/\s+/', ' ', $nombreCategoria);

                                    if ($nombreCategoria === '' || isset($categoriasMostradas[$nombreCategoria])) {
                                        continue;
                                    }

                                    if ($index >= $limiteCategoriasSidebar) {
                                        break;
                                    }

                                    $categoriasMostradas[$nombreCategoria] = true;
                                    $icono = $iconosPorCategoria[$nombreCategoria] ?? "bx bx-category";
                            ?>
                                    <li>
                                        <a href="tienda.php?categoria=<?= $cat['idcategoria'] ?>" class="nav-link">
                                            <i class="<?= $icono ?>"></i>
                                            <?= htmlspecialchars($nombreOriginal) ?>
                                        </a>
                                    </li>
                                <?php
                                    $index++;
                                endif;
                            endforeach;

                            if (empty($categorias) || $index == 0):
                                ?>
                                <li>
                                    <div class="alert alert-info" style="padding: 10px; margin: 0;">
                                        No hay categorías disponibles.
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
                                            <li hidden><span id="producto-stock"></span>
                                            </li>
                                            <li><span>SKU:</span> <span id="producto-sku"></span></li>
                                            <li><span>Código:</span> <span id="producto-codigo"></span></li>
                                            <li><span>Categoría:</span> <span id="producto-categoria"></span></li>
                                        </ul>

                                        <div id="producto-presentaciones-wrapper" class="product-presentations" hidden>
                                            <span class="product-presentations__label">Presentación:</span>
                                            <div id="producto-presentaciones" class="product-presentations__options"></div>
                                        </div>

                                        <div class="product-quantities">
                                            <span>Cantidad:</span>

                                            <div class="input-counter">
                                                <span class="minus-btn">
                                                    <i class='bx bx-minus'></i>
                                                </span>
                                                <input type="text" id="producto-cantidad" value="1" min="1" max="999" inputmode="numeric" pattern="[0-9]*" onchange="normalizarCantidadProducto()" oninput="normalizarCantidadProducto(false)">
                                                <span class="plus-btn">
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

                                        <div id="producto-reserva-note" class="product-reserve-note" hidden>
                                            <strong>Reservar disponibilidad</strong>
                                            <span>Producto agotado por ahora. Escribenos por WhatsApp para reservar o consultar reposicion.</span>
                                        </div>

                                        <div class="products-share">
                                            <ul class="social">
                                                <li><span>Compartir:</span></li>
                                                <li>
                                                    <button id="btn-compartir-whatsapp-producto"
                                                        class="mp-btn-compartir mp-whatsapp"
                                                        onclick="compartirWhatsAppProducto(event)"
                                                        style="background: #166B38; color: white; border: none; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer;"
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
<script type="text/javascript" src="assets/js/producto.js?v=<?php echo filemtime('assets/js/producto.js'); ?>"></script>
<script>
    // Variables globales para el producto
    let productoActual = null;
    let imagenPrincipal = null; // Imagen principal del producto
    let imagenesProducto = []; // Fotos adicionales de la API
    let presentacionesProducto = [];
    let presentacionSeleccionada = null;
    let avisoProductoNoDisponibleMostrado = false;
    let imagenIndex = 0; // Índice de la imagen actual mostrada

    // ============================================================
    //  VALIDAR ID DEL PRODUCTO
    // ============================================================
    const IMAGEN_PRODUCTO_BASE = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/files/articulos/";
    const IMAGEN_PRODUCTO_PLACEHOLDER = "assets/img/ProductoSinImagen.png";
    const PRODUCTO_DETALLE_API = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_tienda_articulos_listarid.php";
    const PRODUCTO_FOTOS_API = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_tienda_mostrarfotosproducto.php";
    const PRODUCTO_FALLBACK_ENDPOINT = "assets/php/productos_paginados.php";
    const PRODUCTO_FALLBACK_MODOS = ["nuevos", "ofertas", "menosde100"];
    const PRODUCTO_FALLBACK_PER_PAGE = 60;

    function escaparHTMLProducto(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

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
    document.addEventListener('DOMContentLoaded', function() {
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
    async function cargarProducto(idarticulo) {
        // Validar ID nuevamente antes de hacer la petición
        if (!validarIdProducto(idarticulo)) {
            mostrarError('ID de producto inválido.');
            return;
        }

        // Sanitizar ID para la petición
        const idSanitizado = parseInt(idarticulo, 10);

        try {
            productoActual = await obtenerProductoDesdeDetalle(idSanitizado);

            if (!productoActual) {
                productoActual = await buscarProductoEnListados(idSanitizado);
            }

            if (!productoActual) {
                mostrarError('No se pudo cargar el producto solicitado.');
                return;
            }

            cargarFotosProducto(idSanitizado);
            return;
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error al cargar el producto. Por favor, intenta de nuevo.');
            return;
        }

    }

    // ============================================================
    // OBTENER DATOS DEL PRODUCTO
    // ============================================================
    async function obtenerProductoDesdeDetalle(idarticulo) {
        const response = await fetch(PRODUCTO_DETALLE_API, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                idarticulo
            })
        });

        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }

        const data = await response.json();
        if (!data || !data.success || !Array.isArray(data.data) || data.data.length === 0) {
            return null;
        }

        const producto = data.data.find(item => String(item?.idarticulo) === String(idarticulo)) || data.data[0];
        if (!producto || String(producto.idarticulo) !== String(idarticulo)) {
            return null;
        }

        return normalizarProducto(producto, idarticulo);
    }

    async function buscarProductoEnListados(idarticulo) {
        for (const modo of PRODUCTO_FALLBACK_MODOS) {
            let pagina = 1;
            let totalPaginas = 1;

            do {
                const response = await fetch(PRODUCTO_FALLBACK_ENDPOINT, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        mode: modo,
                        page: pagina,
                        per_page: PRODUCTO_FALLBACK_PER_PAGE,
                        idarticulo
                    })
                });

                if (!response.ok) {
                    break;
                }

                const data = await response.json();
                if (!data || !data.success || !Array.isArray(data.data)) {
                    break;
                }

                const producto = data.data.find(item => String(item?.idarticulo) === String(idarticulo));
                if (producto) {
                    return normalizarProducto(producto, idarticulo);
                }

                totalPaginas = Number(data?.meta?.total_pages) || 1;
                pagina++;
            } while (pagina <= totalPaginas);
        }

        return null;
    }

    function normalizarProducto(producto, idarticulo) {
        const stock = producto.stock ?? producto.stocksucursal ?? producto.existencia ?? 0;
        const descripcion = (producto.descripcion ?? '').toString().trim();
        const descripcionValida = descripcion && descripcion !== '0' ? descripcion : '';

        const presentaciones = window.LMProductPresentations
            ? window.LMProductPresentations.extract(producto)
            : [];

        return {
            ...producto,
            idarticulo: producto.idarticulo || idarticulo,
            codigo: producto.codigo || producto.codarticulo || 'N/A',
            nombre: producto.nombre || producto.descripcion || producto.codigo || 'Producto',
            descripcion: descripcionValida || producto.nombre || '',
            precio_venta: producto.precio_venta ?? producto.precio ?? producto.preciofinal ?? 0,
            stock,
            categoria: producto.categoria || producto.nombrecategoria || producto.nom_categoria || 'N/A',
            imagen: producto.imagen || producto.foto || producto.ruta_imagen || '',
            presentaciones
        };
    }

    function obtenerPresentacionActiva() {
        if (presentacionSeleccionada) {
            return presentacionSeleccionada;
        }

        if (presentacionesProducto.length > 0) {
            return presentacionesProducto[0];
        }

        if (window.LMProductPresentations) {
            return window.LMProductPresentations.defaultUnit(productoActual || {});
        }

        return {
            nombre: 'UNIDAD',
            tipo: 'unidad',
            precio: normalizarNumeroProducto(productoActual?.precio_venta, 0),
            stock: normalizarStockProducto(productoActual?.stock)
        };
    }

    function mostrarToastProductoNoDisponible(mensaje = 'Producto no disponible.') {
        if (window.Carrito && typeof Carrito.mostrarNotificacion === 'function') {
            Carrito.mostrarNotificacion(mensaje);
            return;
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'warning',
                title: mensaje,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1800,
                timerProgressBar: true
            });
        }
    }

    function normalizarNumeroProducto(valor, fallback = 0) {
        if (valor === null || valor === undefined || valor === '') {
            return fallback;
        }

        const numero = Number(String(valor).replace(',', '.'));
        return Number.isFinite(numero) ? numero : fallback;
    }

    function normalizarStockProducto(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return null;
        }

        const stock = normalizarNumeroProducto(valor, NaN);
        return Number.isFinite(stock) ? stock : null;
    }

    function productoSinStock(stock) {
        return stock !== null && stock <= 0;
    }

    function productoNoComprable(precio, stock) {
        return precio <= 0 || productoSinStock(stock);
    }

    function actualizarEstadoAgotadoProducto(precio, stock) {
        const sinStock = productoSinStock(stock);
        const noComprable = productoNoComprable(precio, stock);

        if (productoActual) {
            productoActual.agotado = sinStock;
            productoActual.no_comprable = noComprable;
        }

        const imagenWrapper = document.querySelector('.main-products-image');
        if (imagenWrapper) {
            imagenWrapper.classList.toggle('is-sold-out', sinStock);
        }

        const reservaNote = document.getElementById('producto-reserva-note');
        if (reservaNote) {
            reservaNote.hidden = !sinStock;
            reservaNote.classList.toggle('is-visible', sinStock);
        }

        const cantidades = document.querySelector('.product-quantities');
        if (cantidades) {
            cantidades.classList.toggle('is-disabled', noComprable);
        }

        const inputCantidad = document.getElementById('producto-cantidad');
        if (inputCantidad) {
            inputCantidad.disabled = noComprable;
            inputCantidad.max = stock !== null && stock > 0 ? String(Math.min(99, Math.floor(stock))) : '99';
        }

        const botonAgregar = document.querySelector('.product-add-to-cart .default-btn');
        if (botonAgregar) {
            botonAgregar.disabled = noComprable;
            botonAgregar.style.opacity = '';
            botonAgregar.style.cursor = '';
            botonAgregar.innerHTML = noComprable
                ? `<i class="flaticon-shopping-cart"></i> ${sinStock ? 'Producto agotado' : 'No disponible'} <span></span>`
                : '<i class="flaticon-shopping-cart"></i> Agregar al carrito <span></span>';
        }

        return { sinStock, noComprable };
    }

    function normalizarTipoPresentacionProducto(presentacion) {
        const texto = String(presentacion?.tipo || presentacion?.nombre || '').trim();
        if (window.LMProductPresentations && typeof window.LMProductPresentations.normalizeName === 'function') {
            return window.LMProductPresentations.normalizeName(texto).toLowerCase();
        }

        return texto.toLowerCase();
    }

    function esPresentacionUnidadProducto(presentacion) {
        const tipo = normalizarTipoPresentacionProducto(presentacion);
        return tipo === 'unidad' || tipo === 'unit' || tipo === 'unidades';
    }

    function filtrarPresentacionesMostrables(presentaciones) {
        const disponibles = presentaciones.filter((presentacion) => {
            const precio = parseFloat(presentacion?.precio || 0);
            const stock = presentacion?.stock === null || presentacion?.stock === undefined
                ? null
                : parseFloat(presentacion.stock || 0);

            return precio > 0 && (stock === null || stock > 0);
        });

        const tienePresentacionNoUnidad = disponibles.some((presentacion) => !esPresentacionUnidadProducto(presentacion));
        return tienePresentacionNoUnidad ? disponibles : [];
    }

    function actualizarVistaPresentacion() {
        if (!productoActual) return;

        const presentacion = obtenerPresentacionActiva();
        const precio = normalizarNumeroProducto(presentacion.precio ?? productoActual.precio_venta, 0);
        const stock = presentacion.stock === null || presentacion.stock === undefined
            ? normalizarStockProducto(productoActual.stock)
            : normalizarStockProducto(presentacion.stock);

        productoActual.precio_venta = precio;
        productoActual.stock = stock;

        document.getElementById('producto-precio').textContent = 'Q' + precio.toFixed(2);
        const stockElement = document.getElementById('producto-stock');
        if (stockElement) {
            stockElement.textContent = '';
        }

        const estadoCompra = actualizarEstadoAgotadoProducto(precio, stock);

        normalizarCantidadProducto();

        if (!estadoCompra.noComprable) {
            avisoProductoNoDisponibleMostrado = false;
        } else if (!avisoProductoNoDisponibleMostrado) {
            avisoProductoNoDisponibleMostrado = true;
            mostrarToastProductoNoDisponible(estadoCompra.sinStock
                ? 'Producto agotado. Puedes consultar reserva por WhatsApp.'
                : 'Producto no disponible.');
        }
    }

    function seleccionarPresentacionProducto(index) {
        const presentacion = presentacionesProducto[index];
        if (!presentacion || presentacion.disabled) {
            return;
        }

        presentacionSeleccionada = presentacion;

        document.querySelectorAll('.product-presentation-option').forEach((button, buttonIndex) => {
            button.classList.toggle('is-selected', buttonIndex === index);
        });

        actualizarVistaPresentacion();
    }

    function renderizarPresentacionesProducto() {
        const wrapper = document.getElementById('producto-presentaciones-wrapper');
        const contenedor = document.getElementById('producto-presentaciones');

        if (!wrapper || !contenedor) {
            return;
        }

        const presentacionesApi = Array.isArray(productoActual?.presentaciones)
            ? productoActual.presentaciones
            : [];
        presentacionesProducto = filtrarPresentacionesMostrables(presentacionesApi);

        if (presentacionesProducto.length === 0) {
            wrapper.hidden = true;
            contenedor.innerHTML = '';
            presentacionSeleccionada = null;
            actualizarVistaPresentacion();
            return;
        }

        const primeraDisponible = presentacionesProducto.findIndex(item => !item.disabled);
        presentacionSeleccionada = primeraDisponible >= 0 ? presentacionesProducto[primeraDisponible] : null;

        contenedor.innerHTML = presentacionesProducto.map((presentacion, index) => {
            const selected = presentacionSeleccionada === presentacion ? ' is-selected' : '';
            const disabled = presentacion.disabled ? ' disabled' : '';
            const title = presentacion.disabled ? 'No disponible' : `Q${parseFloat(presentacion.precio || 0).toFixed(2)}`;

            return `
                <button type="button" class="product-presentation-option${selected}" onclick="seleccionarPresentacionProducto(${index})" title="${escaparHTMLProducto(title)}"${disabled}>
                    ${escaparHTMLProducto(presentacion.nombre)}
                </button>
            `;
        }).join('');

        wrapper.hidden = false;
        actualizarVistaPresentacion();
    }

    // ============================================================
    // CARGAR FOTOS DEL PRODUCTO (con validacion)
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

        // Cargar fotos desde la API
        fetch(PRODUCTO_FOTOS_API, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    idarticulo: idSanitizado
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.imagenes && Array.isArray(data.imagenes)) {
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
                                url = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/" + ruta;
                            }

                            // Codificar espacios y caracteres especiales en la URL
                            url = url.replace(/ /g, '%20');

                            if (url) {
                                imagenesProcesadas.push({
                                    url: url,
                                    orden: parseInt(img.orden) || index,
                                    id: img.id
                                });
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

                } else {
                    if (data.imagenes && !Array.isArray(data.imagenes)) {
                        console.warn('data.imagenes no es un array:', typeof data.imagenes);
                    }
                    if (!data.success) {
                        console.warn('La API de fotos devolvio success: false');
                    }
                }

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
        document.getElementById('producto-stock').textContent = '';
        document.getElementById('producto-sku').textContent = productoActual.idarticulo || 'N/A';
        document.getElementById('producto-codigo').textContent = productoActual.codigo || 'N/A';
        document.getElementById('producto-categoria').textContent = productoActual.categoria || 'N/A';
        renderizarPresentacionesProducto();

        // Llenar descripción completa
        const descripcionCompletaSegura = escaparHTMLProducto(productoActual.descripcion || '');
        document.getElementById('producto-descripcion-completa').innerHTML =
            descripcionCompletaSegura ? `<p>${descripcionCompletaSegura}</p>` : '<p>No hay descripción disponible para este producto.</p>';

        // Llenar información adicional
        let infoHtml = `
        <li><strong>Nombre:</strong> <span>${escaparHTMLProducto(productoActual.nombre || 'N/A')}</span></li>
        <li><strong>Código:</strong> <span>${escaparHTMLProducto(productoActual.codigo || 'N/A')}</span></li>
        <li><strong>Categoría:</strong> <span>${escaparHTMLProducto(productoActual.categoria || 'N/A')}</span></li>
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
            return;
        }

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
        imgPrincipal.onerror = function() {
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
        imgNavPrincipal.onclick = function() {
            cambiarImagen(-1); // -1 indica imagen principal
        };
        imgNavPrincipal.onerror = function() {
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
            imgNav.onclick = function() {
                cambiarImagen(index);
            };
            imgNav.onerror = function() {
                console.error('❌ Error al cargar miniatura:', img);
                this.onerror = null;
                this.src = IMAGEN_PRODUCTO_PLACEHOLDER;
            };
            divNav.appendChild(imgNav);
            sliderNav.appendChild(divNav);
        });
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
        } else if (index >= 0 && index < imagenesProducto.length) {
            // Mostrar foto de la API
            imgPrincipal.src = imagenesProducto[index];
            imagenIndex = index;
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
    function normalizarCantidadProducto(forzarMinimo = true) {
        const input = document.getElementById('producto-cantidad');
        if (!input) return 1;

        const valorLimpio = input.value.replace(/\D/g, '');
        let cantidad = parseInt(valorLimpio, 10);
        const maximo = parseInt(input.max || '99', 10);

        if (!Number.isInteger(cantidad) || cantidad < 1) {
            cantidad = 1;
        }

        if (Number.isInteger(maximo) && maximo > 0 && cantidad > maximo) {
            cantidad = maximo;
        }

        input.value = forzarMinimo || valorLimpio !== '' ? String(cantidad) : '';
        return cantidad;
    }

    function cambiarCantidad(n) {
        const input = document.getElementById('producto-cantidad');
        if (!input || input.disabled) return;

        const cantidadActual = normalizarCantidadProducto();
        const cambio = n > 0 ? 1 : -1;
        const nuevaCantidad = Math.max(1, cantidadActual + cambio);
        input.value = String(nuevaCantidad);
        normalizarCantidadProducto();
    }

    // ============================================================
    // AGREGAR AL CARRITO DESDE PRODUCTO
    // ============================================================
    function agregarAlCarritoDesdeProducto() {
        if (!productoActual) return;

        let cantidad = normalizarCantidadProducto();
        let imagen = imagenesProducto[0] || imagenPrincipal || IMAGEN_PRODUCTO_PLACEHOLDER;
        const presentacion = obtenerPresentacionActiva();
        const precio = normalizarNumeroProducto(presentacion.precio ?? productoActual.precio_venta, 0);
        const stock = presentacion.stock === null || presentacion.stock === undefined
            ? normalizarStockProducto(productoActual.stock)
            : normalizarStockProducto(presentacion.stock);

        if (productoNoComprable(precio, stock)) {
            mostrarToastProductoNoDisponible(productoSinStock(stock)
                ? 'Producto agotado. Puedes consultar reserva por WhatsApp.'
                : 'Producto no disponible.');
            return;
        }

        const agregado = agregarAlCarrito(
            productoActual.idarticulo,
            productoActual.nombre,
            precio,
            imagen,
            productoActual.descripcion || '',
            cantidad,
            {
                presentacion: presentacion.nombre,
                tipo_presentacion: presentacion.tipo,
                precio_presentacion: precio,
                stock_presentacion: stock,
                cantidadpresentacion: presentacion.cantidadpresentacion || 1
            }
        );

        if (!agregado) {
            return;
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
        if (productoActual.agotado) {
            const precioReserva = normalizarNumeroProducto(productoActual.precio_venta, 0).toFixed(2);
            const mensajeReserva = encodeURIComponent(
                `Hola, quiero reservar o consultar disponibilidad de este producto:\n\n` +
                `*${productoActual.nombre}*\n\n` +
                `Precio: *Q${precioReserva}*\n\n` +
                `Ver mas detalles:\n${url}\n\n` +
                `_Libreria Marquense - Utiles escolares y papeleria_`
            );
            const whatsappReservaUrl = `https://wa.me/50255910533?text=${mensajeReserva}`;
            const isMobileReserva = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

            if (isMobileReserva) {
                window.location.href = whatsappReservaUrl;
            } else {
                window.open(whatsappReservaUrl, '_blank');
            }

            return false;
        }

        const mensaje = encodeURIComponent(
            `🛍️ *${productoActual.nombre}*\n\n` +
            `💰 Precio: *Q${parseFloat(productoActual.precio_venta || 0).toFixed(2)}*\n\n` +
            `Ver más detalles:\n${url}\n\n` +
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
        document.getElementById('producto-error').innerHTML = `<p style="color: red;">${escaparHTMLProducto(mensaje)}</p>`;
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
    document.addEventListener('DOMContentLoaded', function() {
        const searchInputSidebar = document.getElementById('search-sidebar');
        if (searchInputSidebar) {
            searchInputSidebar.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    buscarProductosSidebar(e);
                }
            });
        }
    });
</script>
