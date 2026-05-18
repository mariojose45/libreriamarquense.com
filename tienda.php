<?php
// SEO para la pagina de tienda
$seo_title = "Tienda - Librería Marquense | Útiles escolares y papelería";
$seo_description = "Explora la tienda en línea de Librería Marquense con útiles escolares, papelería, libros, material didáctico y productos de oficina.";
$seo_keywords = "tienda Librería Marquense, útiles escolares, papelería, libros, material didáctico, productos escolares Guatemala";

include 'head.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Cargar categorías desde la API (igual que en head.php)
include "assets/php/rutas.php";
$response = getApi($url_listar_categorias);
$data = json_decode($response, true);
$categorias = $data["data"] ?? [];
$categoria_actual = isset($_GET['categoria']) ? (string) $_GET['categoria'] : '';

?>

        <style>
            .main-slider-content,
            .main-slider-content b,
            .main-slider-content h1,
            .main-slider-content p,
            .main-slider-content a {
                color: #fff !important;
            }

            /* Para el botón */
            .main-slider-content .default-btn {
                background-color: #1A2697; /* azul institucional */
                border-color: #1A2697;
                color: #fff !important;
            }

            .main-slider-content .default-btn:hover {
                background-color: #101A5C; /* azul más oscuro */
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
        white-space: nowrap;        /* 🔥 Evita salto de línea */
        overflow: hidden;           /* Oculta texto excesivo */
        text-overflow: ellipsis;    /* Añade "..." */
        margin-bottom: 5px;
    }

    /* Estilos para tienda.php - mismo tamaño fijo que index.php */
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
        white-space: nowrap;        /* 🔥 Evita salto de línea */
        overflow: hidden;           /* Oculta texto excesivo */
        text-overflow: ellipsis;    /* Añade "..." */
        margin-bottom: 5px;
    }

    /* Estilos para el botón de limpiar búsqueda */
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

    /* Ocultar sidebar (categorías y filtros) en móvil */
    @media (max-width: 768px) {
        .shop-area .col-lg-4.col-md-12 {
            display: none !important;
        }

        /* Hacer que el contenido de productos ocupe todo el ancho en móvil */
        .shop-area .col-lg-8.col-md-12 {
            width: 100% !important;
            flex: 0 0 100% !important;
            max-width: 100% !important;
        }

        .shop-area {
            padding-top: 12px !important;
        }
    }

    .single-arrivals-products {
        border: 4px solid #000;
        border-radius: 15px;
        padding: 15px;
        background: #fff;
        transition: all 0.3s ease;
        position: relative;
        margin-bottom: 24px;
    }

    .single-arrivals-products:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
        border-color: #000;
    }

    .single-arrivals-products .arrivals-products-image {
        border-radius: 8px;
        overflow: hidden;
    }

    .single-arrivals-products .arrivals-products-image .tag {
        border-radius: 50px !important;
        padding: 5px 15px !important;
        width: auto !important;
        height: auto !important;
        min-width: unset !important;
        line-height: normal !important;
        font-size: 13px !important;
        font-weight: 700 !important;
        top: 10px !important;
        right: 10px !important;
        background: #B73639 !important;
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(183, 54, 57, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .single-arrivals-products:hover .arrivals-products-image .tag {
        background: #B73639 !important;
    }

    .product-title-limit {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    @media (max-width: 768px) {
        #products-collections-filter > div {
            flex: 0 0 50%;
            max-width: 50%;
            padding-left: 6px;
            padding-right: 6px;
            margin-bottom: 12px;
        }

        .single-arrivals-products {
            border-width: 3px;
            border-radius: 12px;
            padding: 10px;
        }

        .single-arrivals-products .arrivals-products-image .tag {
            font-size: 11px !important;
            padding: 4px 10px !important;
            top: 8px !important;
            right: 8px !important;
        }

        .arrivals-products-content h3 {
            font-size: 14px;
            margin-bottom: 4px;
        }
    }

    .widget-area .widget_categories .categories li {
        margin-bottom: 14px;
    }

    .widget-area .widget_categories .categories li a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 18px;
        border: 1px solid #dcdcdc;
        border-radius: 0;
        background-color: #ffffff;
        color: #111111;
        font-weight: 600;
        transition: all 0.25s ease;
    }

    .widget-area .widget_categories .categories li a::before {
        display: none;
    }

    .widget-area .widget_categories .categories li a i {
        color: #111111;
        font-size: 20px;
        margin-right: 0;
        transition: color 0.25s ease;
    }

    .widget-area .widget_categories .categories li a:hover,
    .widget-area .widget_categories .categories li a:focus,
    .widget-area .widget_categories .categories li a.active {
        background-color: #166B38;
        border-color: #166B38;
        color: #ffffff;
    }

    .widget-area .widget_categories .categories li a:hover i,
    .widget-area .widget_categories .categories li a:focus i,
    .widget-area .widget_categories .categories li a.active i {
        color: #ffffff;
    }

    /* ===========================
       BANNER TIENDA
    =========================== */
    .tienda-banner-section {
        padding: 26px 0 6px;
        width: 100%;
    }

    .tienda-banner-section > .container {
        width: calc(100% - 24px);
        max-width: 1360px;
        padding-left: 0;
        padding-right: 0;
    }

    .tienda-banner {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-radius: 22px;
        background: #101A5C;
        height: 350px;
        min-height: 350px;
        isolation: isolate;
    }

    .tienda-banner::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    .tienda-banner::after {
        background: linear-gradient(180deg, rgba(4, 22, 58, 0.28) 0%, rgba(4, 22, 58, 0.58) 100%);
        z-index: 0;
    }

    .tienda-banner__video {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        transform: scale(1.18);
        transform-origin: center center;
        z-index: -1;
    }

    .tienda-banner__content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 350px;
        min-height: 350px;
        padding: 24px;
        text-align: center;
        width: 100%;
    }

    .tienda-banner__title {
        color: #ffffff;
        font-size: clamp(34px, 4.6vw, 56px);
        font-weight: 800;
        line-height: 1;
        margin: 0;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        text-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
    }

    @media (max-width: 991px) {
        .shop-area {
            padding-top: 24px !important;
        }

        .tienda-banner-section > .container {
            width: calc(100% - 18px);
        }

        .tienda-banner {
            height: 158px;
            min-height: 158px;
        }

        .tienda-banner__content {
            height: 158px;
            min-height: 158px;
        }

        .tienda-banner__video {
            object-fit: cover;
            transform: scale(1.12);
        }
    }

   /* ===== Movil ===== */

    @media (max-width: 767px) {

        .tienda-banner-section {
            padding-top: 18px;
        }

        .tienda-banner-section > .container {
            width: calc(100% - 18px);
        }

        .tienda-banner {
            height: 100px !important;
            min-height: 100px !important;
            overflow: hidden !important;
            position: relative !important;
            border-radius: 18px !important;
        }

        .tienda-banner__content {
            height: 100px !important;
            min-height: 100px !important;
            padding: 18px 12px !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .tienda-banner__video {
            position: absolute !important;
            inset: 0 !important;
            width: 100% !important;
            height: 100% !important;
            min-width: 0 !important;
            min-height: 0 !important;
            object-fit: cover !important;
            object-position: center center !important;
            transform: scale(1.12) !important;
            transform-origin: center center !important;
        }

        .tienda-banner__title {
            font-size: 28px;
            letter-spacing: 0.10em;
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

        <!-- Start Banner Tienda -->
        <section class="tienda-banner-section">
            <div class="container">
                <div class="tienda-banner tienda-banner--principal">
                    <video class="tienda-banner__video" autoplay muted loop playsinline preload="metadata">
                        <source src="assets/img/BannerTienda/VideoLibreria.mp4" type="video/mp4">
                    </video>
                    <div class="tienda-banner__content">
                        <h1 class="tienda-banner__title">TIENDA</h1>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Banner Tienda -->

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
                                    $iconosPorCategoria = [
                                        "CABLES DE AUDIO" => "bx bx-headphone",
                                        "COMPONENTES ELECTRONICOS" => "bx bx-microchip",
                                        "USO TECNICO" => "bx bx-wrench",
                                        "CAR AUDIO" => "bx bx-car",
                                        "MODULOS" => "bx bx-chip",
                                        "PANTALLAS SAMSUNG" => "bx bx-mobile",
                                        "PANTALLAS IPHONE" => "bx bxl-apple",
                                        "BATERIAS" => "bx bx-battery",
                                        "PLACAS" => "bx bx-microchip",
                                        "RATON" => "bx bx-mouse",
                                        "CONTROLES" => "bx bx-joystick",
                                        "CONECTORES" => "bx bx-plug",
                                        "LUCES LED" => "bx bx-bulb",
                                        "FOCOS" => "bx bx-bulb",
                                        "PANTALLAS HONOR" => "bx bx-mobile",
                                        "ACCESORIOS" => "bx bx-category",
                                        "MEMORIAS" => "bx bx-memory-card",
                                        "PROTECTORES" => "bx bx-shield",
                                        "TOUCH" => "bx bx-mobile",
                                        "PANTALLAS MOTOROLA" => "bx bx-mobile",
                                        "PANTALLAS HUAWEI" => "bx bx-mobile",
                                        "PANTALLAS TECNO SPARK" => "bx bx-mobile",
                                        "PANTALLAS XIAOMI REDMI" => "bx bx-mobile",
                                        "PANTALLAS ALCATEL" => "bx bx-mobile",
                                        "PANTALLAS MAXWEST" => "bx bx-mobile",
                                        "PANTALLAS HAIER" => "bx bx-mobile",
                                        "PANTALLAS LG" => "bx bx-mobile",
                                        "PANTALLAS SKY" => "bx bx-mobile",
                                        "PANTALLAS POCO" => "bx bx-mobile",
                                        "PANTALLAS NOKIA" => "bx bx-mobile",
                                        "PANTALLAS ONE PLUS" => "bx bx-mobile",
                                        "ADAPTADOR OTG" => "bx bx-usb",
                                        "PANTALLAS REALME" => "bx bx-mobile",
                                        "PANTALLAS XIAOMI" => "bx bx-mobile",
                                        "PANTALLAS ZTE" => "bx bx-mobile",
                                        "PANTALLAS OPPO" => "bx bx-mobile",
                                        "GLASS + OCA" => "bx bx-layer",
                                        "PANTALLAS INFINIX" => "bx bx-mobile",
                                        "PUERTOS DE CARGA" => "bx bx-plug",
                                        "RACK DE CARGA" => "bx bx-plug",
                                        "FLEX DE CARGA" => "bx bx-extension",
                                        "LAMINAS DE HIDROGEL" => "bx bx-shield",
                                        "FLEX MAIN" => "bx bx-extension",
                                        "BANDEJA SIM" => "bx bx-chip",
                                        "MICROFONOS" => "bx bx-microphone",
                                        "HERRAMIENTAS" => "bx bx-wrench",
                                        "TAPADERAS" => "bx bx-layer",
                                        "AURICULARES" => "bx bx-headphone",
                                        "REPUESTOS" => "bx bx-cog",
                                        "LENTES" => "bx bx-show",
                                        "BATERIA PARA RELOJ" => "bx bx-time-five",
                                        "VIDRIOS TEMPLADOS" => "bx bx-shield",
                                        "CAMARAS" => "bx bx-camera",
                                        "CUBOS" => "bx bx-plug",
                                        "ACCESORIOS PARA CARRO" => "bx bx-car",
                                        "ACCESORIOS VARIOS" => "bx bx-category",
                                        "CARGADORES" => "bx bx-plug",
                                        "CABLES" => "bx bx-usb",
                                        "HUELLAS" => "bx bx-fingerprint",
                                        "TELEFONOS F/TIGO" => "bx bx-phone",
                                        "TELEFONOS SMARTHONE TIGO" => "bx bx-phone",
                                        "TELEFONO SMARTHONE LIBERADOS" => "bx bx-mobile",
                                        "ANTENA DE SEÑAL" => "bx bx-broadcast",
                                        "VIDRIOS DE CAMARAS" => "bx bx-camera",
                                        "FLEX DE SEÑAL" => "bx bx-broadcast",
                                        "PEGAMENTO IMPERMEABLE" => "bx bx-water",
                                        "SOPORTE" => "bx bx-package",
                                        "BOCINA" => "bx bx-volume-full"
                                    ];

                                    $index = 0;
                                    foreach ($categorias as $cat):
                                        // Solo mostrar categorías activas (condicion == 1)
                                        if (isset($cat['condicion']) && $cat['condicion'] == 1):
                                            $nombreCategoria = strtoupper(trim((string) $cat['nombre']));
                                            $icono = $iconosPorCategoria[$nombreCategoria] ?? "bx bx-category";

                                            if (strpos($nombreCategoria, 'ANTENA') !== false || (strpos($nombreCategoria, 'FLEX') !== false && strpos($nombreCategoria, 'SE') !== false)) {
                                                $icono = "bx bx-broadcast";
                                            }
                                    ?>
                                    <li>
                                        <a href="tienda.php?categoria=<?= $cat['idcategoria'] ?>" class="nav-link <?= $categoria_actual === (string) $cat['idcategoria'] ? 'active' : '' ?>">
                                            <i class="<?= $icono ?>"></i>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </a>
                                    </li>
                                    <?php
                                            $index++;
                                        endif;
                                    endforeach;

                                    // Si no hay categorías, mostrar mensaje
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
                        <div class="products-filter-options">
                            <div class="row align-items-center">
                                <div class="col-lg- col-sm-2 col-md-12 col-xs-12">
                                    <div class="d-lg-flex d-md-flex align-items-center">

                                        <span class="sub-title d-none d-lg-block d-md-block">View:</span>

                                        <div class="view-list-row d-none d-lg-block d-md-block">
                                            <div class="view-column">
                                                <a href="#" class="icon-view-two">
                                                    <span></span>
                                                    <span></span>
                                                </a>

                                                <a href="#" class="icon-view-three active">
                                                    <span></span>
                                                    <span></span>
                                                    <span></span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div id="products-collections-filter" class="row">
                            <!-- Los productos se cargarán dinámicamente desde la API -->
                        </div>
                        <div id="products-pagination" class="catalog-pagination-host"></div>


                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Shop Area -->





        <!-- MODAL DE VISTA RAPIDA -->
<!-- ================================
     MODAL VISTA RAPIDA
================================ -->
<style>
    /* ===========================
        FONDO OSCURO DEL MODAL
    =========================== */
    .mp-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 99999;
    }

    .mp-overlay.show {
        display: flex !important;
    }

    /* ===========================
        CONTENEDOR DEL MODAL
    =========================== */
    .mp-modal {
        width: 90%;
        max-width: 900px;
        max-height: 95vh;
        background: #fff;
        border-radius: 12px;
        padding: 25px;
        position: relative;
        box-shadow: 0 10px 35px rgba(0,0,0,0.25);
        animation: fadeIn .25s ease-out;
        overflow-y: auto;
        overflow-x: hidden;
    }

    @keyframes fadeIn {
        from { transform: scale(.95); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }

    /* Botón cerrar */
    .mp-close {
        position: absolute;
        top: 12px;
        right: 15px;
        background: rgba(255, 255, 255, 0.9);
        border: 2px solid #333;
        border-radius: 50%;
        font-size: 24px;
        cursor: pointer;
        color: #333;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        transition: all 0.3s;
        line-height: 1;
        padding: 0;
    }

    .mp-close:hover {
        background: #B42A27;
        color: #fff;
        border-color: #B42A27s;
        transform: scale(1.1);
    }

    /* ===========================
            CONTENIDO
    =========================== */
    .mp-content {
        display: flex;
        gap: 25px;
    }

    /* ===========================
        COLUMNA IZQUIERDA
    =========================== */
    .mp-left {
        width: 50%;
        position: relative;
        text-align: center;
    }

    /* Imagen principal ajustada */
    .mp-img-main {
        width: 100%;
        max-width: 480px;
        max-height: 430px;
        height: auto;
        object-fit: contain !important;
        margin: 0 auto;
        display: block;
    }


    /* ===========================
        MINIATURAS
    =========================== */
    .mp-thumbs {
        display: flex;
        gap: 10px;
        margin-top: 12px;
        justify-content: center;
    }

    /* Miniaturas pequeñas */
    .mp-thumb-item {
        width: 55px !important;
        height: 55px !important;
        border-radius: 8px;
        overflow: hidden;
        cursor: pointer;
        border: 2px solid transparent;
        transition: .2s;
    }

    .mp-thumb-item img {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
    }

    .mp-thumb-item:hover,
    .mp-thumb-item.active {
        border-color: #1A2697;
    }

    /* ===========================
            FLECHAS
    =========================== */
    .mp-prev,
    .mp-next {
        position: absolute;
        top: 45%;
        background: #ffffffd5;
        border: none;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        font-size: 22px;
        cursor: pointer;
        box-shadow: 0 3px 10px rgba(0,0,0,0.20);
        transition: .2s;
    }

    .mp-prev { left: -5px; }
    .mp-next { right: -5px; }

    .mp-prev:hover,
    .mp-next:hover {
        background: #fff;
        transform: scale(1.08);
    }

    /* ===========================
        COLUMNA DERECHA
    =========================== */
    .mp-right {
        width: 50%;
    }

    .mp-precio {
        color: #1A2697;
        font-size: 28px;
        font-weight: bold;
        margin: 5px 0 10px;
    }

    /* ===========================
        CONTROL DE CANTIDAD
    =========================== */
    .mp-cantidad-box {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 15px 0;
    }

    .mp-cantidad-box button {
        width: 35px;
        height: 35px;
        background: #F2F5FA;
        border: none;
        border-radius: 8px;
        font-size: 22px;
        cursor: pointer;
    }

    #mp-cantidad {
        width: 60px;
        height: 35px;
        border: 1px solid #bbb;
        border-radius: 6px;
        text-align: center;
    }

    /* ===========================
            BOTÓN AGREGAR
    =========================== */
    .mp-btn-agregar {
        width: 100%;
        background: #1A2697;
        color: #fff;
        padding: 12px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 18px;
    }

    .mp-btn-agregar:hover {
        background: #101A5C;
    }

    /* ===========================
        BOTONES EN FILA
    =========================== */
    .mp-botones-container {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .mp-btn-agregar {
        flex: 1;
        min-width: 200px;
    }

    .mp-btn-compartir {
        flex: 1;
        min-width: 200px;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        font-weight: 600;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s;
    }

    .mp-btn-compartir i {
        font-size: 24px;
    }

    .mp-whatsapp {
        background: #166B38;
    }

    .mp-whatsapp:hover {
        background: #166B38;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    }

    /* ===========================
            RESPONSIVE
    =========================== */
    @media (max-width: 768px) {
        .mp-overlay {
            padding: 10px;
            align-items: flex-start;
            padding-top: 20px;
        }

        .mp-modal {
            width: 95%;
            max-width: 100%;
            max-height: 90vh;
            padding: 15px;
            margin: 0 auto;
        }

        .mp-close {
            top: 8px;
            right: 8px;
            width: 36px;
            height: 36px;
            font-size: 20px;
            background: rgba(220, 53, 69, 0.9);
            color: #fff;
            border: 2px solid #fff;
        }

        .mp-content {
            flex-direction: column;
            gap: 15px;
        }

        .mp-left,
        .mp-right {
            width: 100%;
        }

        .mp-left {
            order: 1;
        }

        .mp-right {
            order: 2;
        }

        .mp-img-main {
            width: 100% !important;
            max-width: 100% !important;
            max-height: 300px !important;
        }

        .mp-prev,
        .mp-next {
            top: 50%;
            transform: translateY(-50%);
            width: 35px;
            height: 35px;
            font-size: 18px;
        }

        .mp-prev {
            left: 5px;
        }

        .mp-next {
            right: 5px;
        }

        /* Miniaturas más pequeñas en móvil */
        .mp-thumb-item {
            width: 45px !important;
            height: 45px !important;
        }

        .mp-thumbs {
            gap: 8px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .mp-precio {
            font-size: 24px;
        }

        .mp-botones-container {
            flex-direction: column;
        }

        .mp-btn-agregar,
        .mp-btn-compartir {
            width: 100%;
            min-width: auto;
            font-size: 16px;
            padding: 14px;
        }

        /* Asegurar que el contenido sea scrolleable */
        .mp-right {
            max-height: none;
        }
    }

    @media (max-width: 480px) {
        .mp-modal {
            width: 98%;
            padding: 12px;
            max-height: 95vh;
        }

        .mp-close {
            top: 5px;
            right: 5px;
            width: 32px;
            height: 32px;
            font-size: 18px;
        }

        .mp-img-main {
            max-height: 250px !important;
        }

        .mp-prev,
        .mp-next {
            width: 30px;
            height: 30px;
            font-size: 16px;
        }

        .mp-thumb-item {
            width: 40px !important;
            height: 40px !important;
        }

        .mp-precio {
            font-size: 22px;
        }
    }
</style>


<!-- ===========================
      MODAL VISTA RAPIDA
=========================== -->
<!--MODAL PRODUCTO - SIMPLE Y PROPIO -->
<div id="modalProducto" class="mp-overlay">
    <div class="mp-modal">

        <button class="mp-close" onclick="cerrarModal()">✕</button>

        <div class="mp-content">

            <!-- Imagen + Thumbs -->
            <div class="mp-left">
                <img id="mp-imagen-principal" class="mp-img-main" src="" alt="">

                <div id="mp-thumbs" class="mp-thumbs"></div>

                <button class="mp-prev" onclick="imgPrev()">←</button>
                <button class="mp-next" onclick="imgNext()">→</button>
            </div>

            <!-- Info -->
            <div class="mp-right">
                <h2 id="mp-titulo"></h2>
                <h3 id="mp-precio" class="mp-precio"></h3>

                <p id="mp-descripcion"></p>

                <p><b>Disponibilidad:</b> <span id="mp-stock"></span></p>
                <p><b>SKU:</b> <span id="mp-sku"></span></p>

                <div class="mp-cantidad-box">
                    <button onclick="mpCambiarCantidad(-1)">–</button>
                    <input id="mp-cantidad" type="text" value="1">
                    <button onclick="mpCambiarCantidad(1)">+</button>
                </div>

                <!-- Botones en fila -->
                <div class="mp-botones-container">
                    <button class="mp-btn-agregar" onclick="agregarAlCarritoDesdeModal()">🛒 Agregar al carrito</button>
                    <button id="btn-compartir-whatsapp" class="mp-btn-compartir mp-whatsapp" onclick="return compartirWhatsApp(event);" title="Compartir por WhatsApp">
                        <i class='bx bxl-whatsapp'></i> Compartir por WhatsApp
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>









        <!-- Start Footer Area -->

<?php include 'footer.php'; ?>
<script type="text/javascript" src="assets/js/sweatlert.js"></script>
<script type="text/javascript" src="assets/js/tienda.js?v=<?php echo filemtime('assets/js/tienda.js'); ?>"></script>
<script>
// Asegura que Tienda abra desde el inicio al entrar o recargar.
(function () {
    function scrollTiendaAlInicio() {
        window.scrollTo({
            top: 0,
            left: 0,
            behavior: 'auto'
        });
    }

    if ('scrollRestoration' in history) {
        history.scrollRestoration = 'manual';
    }

    document.addEventListener('DOMContentLoaded', scrollTiendaAlInicio);
    window.addEventListener('pageshow', scrollTiendaAlInicio);
    window.addEventListener('load', function () {
        setTimeout(scrollTiendaAlInicio, 0);
    });
})();

// ============================================================
// FUNCIÓN PARA BUSCAR DESDE EL SIDEBAR
// ============================================================
function buscarProductosSidebar(event) {
    event.preventDefault();
    const searchInput = document.getElementById('search-sidebar');
    const termino = searchInput.value.trim();

    if (!termino) {
        alert('Por favor ingresa un término de búsqueda');
        return;
    }

    // Redirigir a tienda.php con el parámetro de búsqueda
    window.location.href = 'tienda.php?buscar=' + encodeURIComponent(termino);
}

// ============================================================
// FUNCIÓN PARA LIMPIAR LA BÚSQUEDA
// ============================================================
function limpiarBusqueda() {
    // Limpiar el input
    const searchInput = document.getElementById('search-sidebar');
    if (searchInput) {
        searchInput.value = '';
    }

    // Redirigir a tienda.php sin parámetros de búsqueda
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
