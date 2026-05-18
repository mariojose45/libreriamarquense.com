<?php
// SEO para la pagina menos de Q100
$seo_title = "Menos de Q100 - Librería Marquense | Productos escolares económicos";
$seo_description = "Descubre productos escolares, útiles, papelería y artículos de oficina por menos de Q100 en Librería Marquense.";
$seo_keywords = "Librería Marquense, productos menos de Q100, útiles escolares económicos, papelería económica, Guatemala";

include 'head.php';
$current_page = basename($_SERVER['PHP_SELF']);



?>

<style>
    /* Variable CSS para la ruta base de imágenes */
    :root {
        --ruta-imagenes:
            <?php
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $path = dirname($_SERVER['PHP_SELF']);
            echo $protocol . "://" . $host . $path . "/files/articulos/";
            ?>;
    }

    .main-slider-content,
    .main-slider-content b,
    .main-slider-content h1,
    .main-slider-content p,
    .main-slider-content a {
        color: #fff !important;
    }

    /* Para el botón */
    .main-slider-content .default-btn {
        background-color: #1A2697;
        /* azul institucional */
        border-color: #1A2697;
        color: #fff !important;
    }

    .main-slider-content .default-btn:hover {
        background-color: #101A5C;
        /* azul más oscuro */
        border-color: #101A5C;
    }

    /* FORZAR VISIBILIDAD DE TODOS LOS ELEMENTOS DEL SLIDER */
    .home-slides-two .main-slider-content b,
    .home-slides-two .main-slider-content h1,
    .home-slides-two .main-slider-content p {
        opacity: 1 !important;
        visibility: visible !important;
    }

    .home-slides-two .main-slider-content b {
        display: inline-block !important;
    }

    .home-slides-two .main-slider-content h1,
    .home-slides-two .main-slider-content p {
        display: block !important;
    }

    /* =========================
   BANNER SUAVE / SECUNDARIO
========================= */
    .exclusive-offers-banner.banner-soft {
        background: #F7F8FC;
        margin-top: 40px;
        margin-bottom: 0;
        padding: 30px 0;
    }

    .exclusive-offers-banner.banner-soft::before {
        display: none;
        /* sin animación */
    }

    .banner-soft .banner-text {
        color: #1A2697;
        font-size: 24px;
        font-weight: 700;
        text-shadow: none;
    }

    .banner-soft .banner-btn {
        background: #1A2697;
        color: #fff;
    }

    .banner-soft .banner-btn:hover {
        background: #142276;
    }
</style>

<!-- Mensaje de error si viene de redirección -->
<?php if (isset($_GET['error'])): ?>
    <div class="container" style="margin-top: 20px;">
        <div class="alert alert-danger" role="alert" style="padding: 15px; margin-bottom: 20px; border-radius: 5px;">
            <?php
            $error = $_GET['error'];
            switch ($error) {
                case 'producto_no_encontrado':
                    echo '<strong>⚠️ Producto no encontrado:</strong> El producto que buscas no existe o ha sido eliminado.';
                    break;
                case 'id_invalido':
                    echo '<strong>⚠️ ID inválido:</strong> El ID del producto no es válido. Por favor, selecciona un producto desde la tienda.';
                    break;
                case 'id_faltante':
                    echo '<strong>⚠️ ID faltante:</strong> No se especificó un ID de producto. Por favor, selecciona un producto desde la tienda.';
                    break;
                default:
                    echo '<strong>⚠️ Error:</strong> Ha ocurrido un error al cargar el producto.';
            }
            ?>
        </div>
    </div>
<?php endif; ?>



<!-- End Main Slider Area -->


<!-- End Overview Area -->




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
        /* 🔥 Evita salto de línea */
        overflow: hidden;
        /* Oculta texto excesivo */
        text-overflow: ellipsis;
        /* Añade "..." */
        margin-bottom: 5px;
    }

    /* Banner Ofertas Exclusivas */
    .exclusive-offers-banner {
        position: relative;
        width: calc(100% - 24px) !important;
        max-width: 1360px !important;
        min-height: 142px !important;
        margin: 50px auto -50px !important;
        padding: 0 !important;
        overflow: hidden !important;
        z-index: 5;
        border-radius: 22px !important;
        box-sizing: border-box !important;
        background: linear-gradient(112deg, #101A5C 0%, #1A2697 49.35%, #166B38 49.55%, #355329 100%) !important;
        background-color: #1A2697 !important;
        box-shadow: 0 18px 42px rgba(16, 26, 92, 0.16) !important;
        isolation: isolate;
    }

    @property --lm-gold-border-angle {
        syntax: "<angle>";
        inherits: false;
        initial-value: 0deg;
    }

    .exclusive-offers-banner::before {
        content: "";
        position: absolute;
        inset: 0;
        padding: 4px;
        border-radius: inherit;
        background: conic-gradient(from var(--lm-gold-border-angle),
                rgba(255, 255, 255, .08) 0deg,
                rgba(226, 178, 70, .32) 34deg,
                #f8df8a 72deg,
                #fff4bd 96deg,
                #c58b2b 132deg,
                rgba(255, 255, 255, .10) 180deg,
                rgba(181, 124, 38, .26) 232deg,
                #f3d474 286deg,
                rgba(255, 255, 255, .08) 360deg);
        animation: goldenBorderOrbit 7s linear infinite;
        filter: drop-shadow(0 0 8px rgba(238, 198, 87, .24));
        mask:
            linear-gradient(#000 0 0) content-box,
            linear-gradient(#000 0 0);
        mask-composite: exclude;
        -webkit-mask:
            linear-gradient(#000 0 0) content-box,
            linear-gradient(#000 0 0);
        -webkit-mask-composite: xor;
        z-index: 2;
        pointer-events: none;
    }

    .exclusive-offers-banner::after {
        content: "";
        position: absolute;
        inset: 0;
        border-radius: inherit;
        box-shadow:
            inset 0 0 28px rgba(255, 236, 164, .08),
            inset 0 0 70px rgba(0, 0, 0, .12);
        z-index: 1;
        pointer-events: none;
    }

    @keyframes goldenBorderOrbit {
        to {
            --lm-gold-border-angle: 360deg;
        }
    }

    .banner-content {
        min-height: 142px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 12px !important;
        position: relative;
        z-index: 3;
        text-align: center !important;
    }

    .banner-text {
        color: #fff !important;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .banner-btn {
        background: #F7D917 !important;
        background-color: #F7D917 !important;
        color: #292929 !important;
        font-weight: 700;
        padding: 12px 35px;
        border-radius: 50px;
        font-size: 16px;
        text-transform: uppercase;
        box-shadow: 0 10px 24px rgba(208, 195, 43, 0.25) !important;
        transition: all 0.3s ease;
    }

    .banner-btn:hover {
        transform: translateY(-3px) scale(1.05);
        background: #D6B900 !important;
        background-color: #D6B900 !important;
        color: #292929 !important;
        box-shadow: 0 12px 28px rgba(184, 173, 37, 0.34) !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .exclusive-offers-banner {
            width: calc(100% - 18px) !important;
            min-height: 132px !important;
            border-radius: 18px !important;
        }

        .banner-content {
            min-height: 132px !important;
            gap: 12px !important;
        }

        .banner-text {
            font-size: 18px !important;
            line-height: 1.22 !important;
        }

        .banner-btn {
            padding: 10px 24px !important;
            font-size: 14px !important;
        }
    }

    @media (max-width: 359px) {
        .exclusive-offers-banner {
            width: calc(100% - 14px) !important;
            min-height: 132px !important;
            border-radius: 16px !important;
        }

        .banner-content {
            min-height: 132px !important;
            gap: 10px !important;
        }

        .banner-text {
            font-size: 18px !important;
        }

        .banner-btn {
            padding: 9px 18px !important;
            font-size: 13px !important;
        }
    }

    /* Estilo Título de Sección con Líneas */
    .section-title {
        border-bottom: none !important;
        position: relative;
        margin-bottom: 50px;
        text-align: center;
        overflow: hidden;
    }

    .section-title h2 {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        display: inline-block;
        position: relative;
        background: transparent !important;
        padding: 0 20px;
        margin-top: 0 !important;
        top: 0 !important;
    }

    .section-title h2::before,
    .section-title h2::after {
        content: "";
        position: absolute;
        top: 50%;
        width: 1000px;
        height: 2px;
        background-color: #ddd;
        z-index: -1;
    }

    .section-title h2::before {
        right: 100%;
        margin-right: 15px;
    }

    .section-title h2::after {
        left: 100%;
        margin-left: 15px;
    }

    /* Estilo Tarjeta Producto */
    .single-arrivals-products {
        border: 4px solid #000;
        /* Borde negro grueso tipo marco */
        border-radius: 15px;
        /* Bordes un poco más redondeados */
        padding: 15px;
        background: #fff;
        transition: all 0.3s ease;
        position: relative;
    }

    .single-arrivals-products:hover {
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        transform: translateY(-5px);
        border-color: #000;
        /* Mantener borde negro al hover */
    }

    /* Estilo tag con acento Marquense */
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
        /* Etiqueta roja uniforme */
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(183, 54, 57, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Mantener el mismo rojo en hover */
    .single-arrivals-products:hover .arrivals-products-image .tag {
        background: #B73639 !important;
    }

    /* Ajuste imagen para que no se salga del borde redondeado */
    .single-arrivals-products .arrivals-products-image {
        border-radius: 8px;
        overflow: hidden;
    }

    @media (max-width: 768px){

        .arrivals-products-area{
            padding-top: 100px !important;
            padding-bottom: 60px !important;
        }

        #contenedor-promociones-productos{
            display: flex;
            flex-wrap: wrap;
            margin-left: -6px;
            margin-right: -6px;
        }

        #contenedor-promociones-productos > div{
            width: 50% !important;
            flex: 0 0 50% !important;
            max-width: 50% !important;
            padding-left: 6px;
            padding-right: 6px;
            margin-bottom: 14px;
        }

        .single-arrivals-products{
            padding: 10px !important;
            border-width: 3px !important;
        }

        .arrivals-products-content h3{
            font-size: 14px !important;
            line-height: 1.25;
        }
    }

</style>

<!-- Banner Ofertas Exclusivas -->
<div class="exclusive-offers-banner banner-main">
    <div class="container">
        <div class="banner-content">
            <span class="banner-text">TODO POR MENOS DE Q100</span>
            <a href="tienda.php" class="banner-btn">VER PRODUCTOS</a>
        </div>
    </div>
</div>

<!-- Start Arrivals Products Area -->
<section class="arrivals-products-area pt-100 pb-70">
    <div class="container">
        <div class="row" id="contenedor-promociones-productos">

            <!-- Aquí se insertarán los productos dinámicos -->

        </div>
        <div id="products-pagination" class="catalog-pagination-host"></div>
    </div>
</section>

<style>
    .section-header-soft {
        background: #F7F8FC;
        padding: 50px 0;
        text-align: center;
    }

    .section-header-soft h2 {
        color: #1A2697;
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    .section-header-soft .btn-soft {
        background: #1A2697;
        color: #fff;
        padding: 10px 28px;
        border-radius: 30px;
        font-weight: 600;
        display: inline-block;
    }

    .section-header-soft .btn-soft:hover {
        background: #142276;
    }
</style>

<!-- End Arrivals Products Area -->
<style>
    /* Animación de Ondas */
    .section-header-soft {
        position: relative;
        width: 100%;
        min-height: 280px;
        /* Reducida altura */
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #F7F8FC;
        padding: 0;
    }

    .section-header-soft .wave {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, #1A2697 0%, #B73639 58%, #166B38 100%);
        /* Fondo gradiente Marquense */
        box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.1);
        z-index: 0;
    }

    .section-header-soft .wave span {
        content: "";
        position: absolute;
        width: 200vw;
        height: 200vw;
        top: -190vw;
        /* Ajuste para subir las ondas con nueva altura */
        left: 50%;
        transform: translate(-50%, 0);
        background: rgba(255, 255, 255, 0.1);
        opacity: 1;
        border-radius: 43%;
    }

    .section-header-soft .wave span:nth-child(1) {
        animation: wave-animate 6s linear infinite;
        opacity: 0.3;
        background: rgba(255, 255, 255, 0.3);
    }

    .section-header-soft .wave span:nth-child(2) {
        animation: wave-animate 10s linear infinite;
        opacity: 0.2;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 40%;
    }

    .section-header-soft .wave span:nth-child(3) {
        animation: wave-animate 15s linear infinite;
        opacity: 0.1;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 45%;
    }

    @keyframes wave-animate {
        0% {
            transform: translate(-50%, 0) rotate(0deg);
        }

        100% {
            transform: translate(-50%, 0) rotate(360deg);
        }
    }

    /* Contenido sobre la animación */
    .section-header-soft .content {
        position: relative;
        z-index: 5;
        text-align: center;
        width: 100%;
    }

    .section-header-soft h2 {
        color: #fff !important;
        font-size: 3em;
        /* Texto un poco más pequeño */
        letter-spacing: 2px;
        margin-bottom: 20px;
        text-transform: uppercase;
        font-weight: 800;
        text-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        position: relative;
    }

    .section-header-soft .btn-soft {
        background: #fff;
        color: #2C3FAE;
        padding: 12px 30px;
        /* Botón un poco más compacto */
        border-radius: 30px;
        font-weight: 700;
        display: inline-block;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        text-decoration: none;
        position: relative;
    }

    .section-header-soft .btn-soft:hover {
        background: #2C3FAE;
        color: #fff;
        box-shadow: 0 5px 20px rgba(73, 115, 255, 0.4);
        transform: translateY(-3px);
    }

    @media (max-width: 768px) {
        .section-header-soft h2 {
            font-size: 1.8em;
        }

        .section-header-soft {
            min-height: 200px;
            /* Altura móvil reducida */
        }

        .section-header-soft .wave span {
            width: 300vw;
            height: 300vw;
            top: -290vw;
        }
    }
</style>




<!-- MODAL DE VISTA RÁPIDA -->
<!-- ================================
     MODAL VISTA RÁPIDA
================================ -->
<style>
    /* ===========================
    FONDO OSCURO DEL MODAL
=========================== */
    .mp-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .7);
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
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.25);
        animation: fadeIn .25s ease-out;
        overflow-y: auto;
        overflow-x: hidden;
    }

    @keyframes fadeIn {
        from {
            transform: scale(.95);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
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
        border-color: #B42A27;
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
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.20);
        transition: .2s;
    }

    .mp-prev {
        left: -5px;
    }

    .mp-next {
        right: -5px;
    }

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
      MODAL VISTA RÁPIDA
=========================== -->
<!-- 🟦 MODAL PRODUCTO - SIMPLE Y PROPIO -->
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
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <button class="mp-btn-agregar" onclick="agregarAlCarritoDesdeModal()">🛒 Agregar al
                            carrito</button>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
                        <button id="btn-compartir-whatsapp" class="mp-btn-compartir mp-whatsapp"
                            onclick="return compartirWhatsApp(event);" title="Compartir por WhatsApp">
                            <i class='bx bxl-whatsapp'></i> Compartir por WhatsApp
                        </button>
                    </div>


                </div>
            </div>

        </div>
    </div>
</div>









<!-- Start Footer Area -->

<?php include 'footer.php'; ?>
<script type="text/javascript" src="assets/js/sweatlert.js"></script>
<script type="text/javascript" src="assets/js/menosde100.js?v=<?php echo filemtime('assets/js/menosde100.js'); ?>"></script>
<script>
    // Limpiar parámetros de error de la URL después de mostrar el mensaje
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            // Limpiar la URL después de 5 segundos
            setTimeout(function () {
                if (window.history && window.history.replaceState) {
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }
            }, 5000);
        }
    });
</script>
