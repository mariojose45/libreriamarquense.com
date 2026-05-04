<?php
// SEO para la pagina de inicio
$seo_title = "TI-CELL | Celulares, accesorios y reparaciones en Guatemala";
$seo_description = "TI-CELL es tu tienda de celulares, accesorios, repuestos y reparacion tecnica en Guatemala.";
$seo_keywords = "TI-CELL, celulares Guatemala, accesorios para celulares, repuestos para telefonos, reparacion de celulares, tienda de tecnologia Guatemala";

include 'head.php';
$current_page = basename($_SERVER['PHP_SELF']);

$apiUrl = "https://ssl.sol.sistemasolgt.com/ticel/api/api_tienda_inicio.php";
$slider = null;

$ch = curl_init($apiUrl);
if ($ch !== false) {
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, []);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response !== false && empty($curlError) && $httpCode == 200) {
        $data = json_decode($response, true);

        if ($data !== null && isset($data['success']) && $data['success'] === true && !empty($data['data']) && is_array($data['data'])) {
            $slider = $data['data'][0];
        }
    }
}

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
            ?>
        ;
    }

    }

    /* Evita que aparezcan miniaturas circulares generadas por scripts antiguos del slider de inicio. */
    .main-slider-area .inicio-circular-carousel,
    .main-slider-area .lm-final-circular-thumbs,
    .main-slider-area .lm-professional-circle-nav,
    .main-slider-area .lm-root-circular-nav,
    .main-slider-area .lm-clean-real-slider__circle {
        display: none !important;
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

    .banner-best {
        position: relative;
        background: linear-gradient(90deg, #EEF2FF 0%, #E6E9FA 50%, #E6E9FA 100%);
        margin-top: 50px;
        margin-bottom: -50px;
        padding: 28px 0;
        overflow: hidden;
        z-index: 5;
        border-top: 1px solid rgba(26, 38, 151, 0.12);
        border-bottom: 1px solid rgba(26, 38, 151, 0.12);
    }

    .banner-best::before {
        content: "";
        position: absolute;
        top: 0;
        left: -20%;
        width: 140%;
        height: 100%;
        background: linear-gradient(120deg,
                transparent 0%,
                rgba(255, 255, 255, 0.45) 35%,
                transparent 70%);
        transform: skewX(-18deg);
        animation: bestShine 4s linear infinite;
    }

    @keyframes bestShine {
        0% {
            transform: translateX(-30%) skewX(-18deg);
        }

        100% {
            transform: translateX(30%) skewX(-18deg);
        }
    }

    .banner-best .banner-text {
        color: #1A2697;
        font-size: 24px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .banner-best .banner-text::after {
        content: "";
        display: block;
        width: 70px;
        height: 3px;
        margin: 8px auto 0;
        border-radius: 10px;
        background: linear-gradient(90deg, #1A2697, #6A79D6);
    }

    .banner-best .banner-btn {
        background: linear-gradient(90deg, #1A2697 0%, #2C3FAE 100%);
        color: #fff;
        font-weight: 700;
        padding: 12px 35px;
        border-radius: 50px;
        box-shadow: 0 8px 22px rgba(26, 38, 151, 0.22);
        transition: all 0.3s ease;
    }

    .banner-best .banner-btn:hover {
        background: linear-gradient(90deg, #142276 0%, #1A2697 100%);
        transform: translateY(-3px);
    }

    /* =========================
   RESPONSIVE SLIDER MOBILE
========================= */

    @media only screen and (max-width: 767px) {

        .main-slider-area:not(.slider-circular-home) {
            height: 540px;
        }

        .main-slider-area:not(.slider-circular-home) .main-slider-item-box {

            width: 140px;
            height: 205px;

            bottom: 78px;
            top: auto;

            transform: translateX(-50%);

        }

        .main-slider-area:not(.slider-circular-home) .main-slider-item-box:nth-child(3) {

            left: 50%;
            right: auto;

        }

        .main-slider-area:not(.slider-circular-home) .main-slider-item-box:nth-child(n + 4) {

            display: none !important;

        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content {

            top: 36px;
            bottom: auto;

            left: 18px;
            right: 18px;

        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content h1 {

            font-size: 30px;

        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content p {

            max-width: 100%;

        }

    }

    @media only screen and (max-width: 359px) {

        .main-slider-area:not(.slider-circular-home) {
            height: 520px;
        }

        .main-slider-area:not(.slider-circular-home) .main-slider-item-box {
            width: 118px;
            height: 170px;
            bottom: 74px;
        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content {
            top: 30px;
            left: 14px;
            right: 14px;
        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content b {
            font-size: 12px;
        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content h1 {
            font-size: 24px;
        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content p {
            font-size: 12px;
        }

        .main-slider-area:not(.slider-circular-home) .main-slider-content .default-btn {
            padding: 9px 16px;
            font-size: 13px;
        }

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


<!-- Start Main Slider Area -->
<?php
$sliderItems = [];

if ($slider) {
    for ($i = 1; $i <= 3; $i++) {
        if (!empty($slider["bloque_$i"])) {
            $sliderItems[] = [
                'index' => $i,
                'data' => $slider["bloque_$i"]
            ];
        }
    }
}
?>

<div class="main-slider-area slider-circular-home">
    <div class="home-slides-two slider-stack">
        <?php if (!empty($sliderItems)): ?>
            <?php for ($r = 0; $r < 2; $r++): ?>
                <?php foreach ($sliderItems as $item): ?>
                    <?php
                    $i = $item['index'];
                    $b = $item['data'];

                    $rutaImagenes = "https://ssl.sol.sistemasolgt.com/ticel/files/articulos/";
                    $imagenUrl = !empty($b['imagen']) ? $rutaImagenes . $b['imagen'] : '';
                    ?>
                    <div class="main-slider-item-box" <?= !empty($imagenUrl) ? 'style="background-image: url(\'' . htmlspecialchars($imagenUrl, ENT_QUOTES) . '\');"' : '' ?>>
                        <div class="main-slider-overlay"></div>

                        <div class="main-slider-content">
                            <b><?= htmlspecialchars($b['titulo']) ?></b>
                            <h1><?= htmlspecialchars($b['sub_titulo']) ?></h1>
                            <p><?= htmlspecialchars($b['descripcion']) ?></p>

                            <div class="slider-btn">
                                <?php if ($i == 3): ?>
                                    <a href="contact.php" class="default-btn">
                                        <i class="flaticon-shopping-cart"></i>
Dirigite a Nosotros
                                        <span></span>
                                    </a>
                                <?php else: ?>
                                    <a href="tienda.php" class="default-btn">
                                        <i class="flaticon-shopping-cart"></i>
                                        <?= $i == 1 ? 'Comprar Ahora' : 'Cotizar Ahora' ?>
                                        <span></span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endfor; ?>
        <?php endif; ?>
    </div>

    <div class="slider-stack-buttons">
        <button type="button" class="slider-stack-prev" aria-label="Slide anterior">
            <i class="flaticon-left-arrow"></i>
        </button>
        <button type="button" class="slider-stack-next" aria-label="Slide siguiente">
            <i class="flaticon-right-arrow"></i>
        </button>
    </div>
</div>
<!-- End Main Slider Area -->


<!-- End Overview Area -->
<style>
    .support-cards {
        padding: 30px 0 20px;
    }

    .support-cards .col-lg-3,
    .support-cards .col-md-6 {
        display: flex;
    }

    .support-card {
        background: #fff;
        width: 100%;
        padding: 30px 20px;
        border-radius: 16px;
        text-align: center;
        box-shadow: none;
        border: 2px solid #B73639;
        transition: all .3s ease;

        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;

        min-height: 190px;
    }

    .support-card:hover {
        transform: translateY(-6px);
        border-color: #B73639;
        box-shadow: 0 10px 25px rgba(183, 54, 57, 0.12);
    }

    .support-card i {
        font-size: 46px;
        color: #B73639;
        margin-bottom: 14px;
        line-height: 1;
    }

    .support-card h4 {
        font-size: 16px;
        font-weight: 700;
        margin-bottom: 6px;
        line-height: 1.25;

        min-height: 40px;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    .support-card p {
        font-size: 14px;
        color: #666;
        margin: 0;
        line-height: 1.35;

        min-height: 38px;

        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* RESPONSIVE SUPPORT CARDS */
    @media (max-width: 767px) {

        .support-cards {
            padding: 20px 0 10px;
        }

        .support-cards .row {
            display: flex;
            flex-wrap: wrap;
            margin-left: -8px;
            margin-right: -8px;
            row-gap: 16px;
        }

        .support-cards .col-lg-3,
        .support-cards .col-md-6 {
            flex: 0 0 50%;
            max-width: 50%;
            padding-left: 8px;
            padding-right: 8px;
            margin-bottom: 0;
            display: flex;
        }

        .support-card {
            padding: 16px 10px;
            border-radius: 14px;
            min-height: 135px;
        }

        .support-card i {
            font-size: 30px;
            margin-bottom: 8px;
        }

        .support-card h4 {
            font-size: 14px;
            line-height: 1.2;
            margin-bottom: 4px;
            min-height: 34px;
        }

        .support-card p {
            font-size: 12px;
            line-height: 1.25;
            min-height: 30px;
        }

        .exclusive-offers-banner {
            margin-bottom: 35px;
        }

        .section-header-soft {
            margin-bottom: 35px;
        }

        .products-after-banner {
            padding-top: 0 !important;
        }
    }
</style>
<!-- Start Support Area -->
<section class="support-area support-cards">
    <div class="container">
        <div class="row">

            <div class="col-lg-3 col-md-6">
                <div class="support-card">
                    <i class="bx bx-store-alt"></i>
                    <h4>Celulares y Accesorios</h4>
                    <p>Disponibles</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="support-card">
                    <i class="bx bx-calendar-check"></i>
                    <h4>Reparaciones</h4>
                    <p>y Diagnostico</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="support-card">
                    <i class="bx bx-package"></i>
                    <h4>Env&iacute;os</h4>
                    <p>a Domicilio</p>
                </div>
            </div>

            <div class="col-lg-3 col-md-6">
                <div class="support-card">
                    <i class="bx bx-user-voice"></i>
                    <h4>Asesoria Tecnica</h4>
                    <p>Personalizada</p>
                </div>
            </div>

        </div>
    </div>
</section>



<!-- End Support Area -->
<style>
    .arrivals-products-image {
        width: 100%;
        aspect-ratio: 1 / 1;
        max-width: 100%;
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
        background: linear-gradient(90deg, #1A2697 0%, #B73639 58%, #466934 100%);
        padding: 25px 0;
        margin-top: 50px;
        margin-bottom: 60px;
        overflow: hidden;
        z-index: 5;
        isolation: isolate;
    }

    @property --lm-gold-border-angle {
        syntax: "<angle>";
        inherits: false;
        initial-value: 0deg;
    }

    .exclusive-offers-banner::before,
    .section-header-soft::before {
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

    .exclusive-offers-banner::after,
    .section-header-soft::after {
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
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        position: relative;
        z-index: 3;
    }

    .banner-text {
        color: #fff;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: 1px;
        text-transform: uppercase;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }

    .banner-btn {
        background: #466934;
        color: #fff;
        font-weight: 700;
        padding: 12px 35px;
        border-radius: 50px;
        font-size: 16px;
        text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }

    .banner-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        background: #355329;
        color: #fff;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .exclusive-offers-banner {
            padding: 18px 0;
            margin-top: 30px;
            margin-bottom: 35px;
        }

        .banner-content {
            flex-direction: column;
            gap: 12px;
            text-align: center;
        }

        .banner-text {
            font-size: 18px;
            line-height: 1.3;
        }

        .banner-btn {
            padding: 10px 24px;
            font-size: 14px;
        }
    }

    /* Home banners: same visual width as the main slider, with the brand split color. */
    .exclusive-offers-banner,
    .section-header-soft {
        width: calc(100% - 24px) !important;
        max-width: 1360px !important;
        min-height: 142px !important;
        margin-left: auto !important;
        margin-right: auto !important;
        padding: 0 !important;
        border-radius: 22px !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
        background: linear-gradient(112deg, #101A5C 0%, #1A2697 49.35%, #466934 49.55%, #355329 100%) !important;
        background-color: #1A2697 !important;
        box-shadow: 0 18px 42px rgba(16, 26, 92, 0.16) !important;
    }

    .section-header-soft .wave {
        background: linear-gradient(112deg, #101A5C 0%, #1A2697 49.35%, #466934 49.55%, #355329 100%) !important;
    }

    .exclusive-offers-banner .container,
    .section-header-soft .content.container {
        width: 100% !important;
        max-width: 1320px !important;
        padding-left: 24px !important;
        padding-right: 24px !important;
    }

    .exclusive-offers-banner .banner-content,
    .section-header-soft .content {
        min-height: 142px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 12px !important;
        text-align: center !important;
    }

    .section-header-soft h2 {
        margin-bottom: 0 !important;
        font-size: 30px !important;
        line-height: 1.15 !important;
    }

    @media (max-width: 768px) {
        .exclusive-offers-banner,
        .section-header-soft {
            width: calc(100% - 18px) !important;
            min-height: 132px !important;
            border-radius: 18px !important;
        }

        .exclusive-offers-banner .container,
        .section-header-soft .content.container {
            padding-left: 18px !important;
            padding-right: 18px !important;
        }

        .exclusive-offers-banner .banner-content,
        .section-header-soft .content {
            min-height: 132px !important;
            flex-direction: column !important;
            gap: 12px !important;
        }

        .section-header-soft h2 {
            font-size: 22px !important;
        }
    }

    @media (max-width: 359px) {
        .exclusive-offers-banner,
        .section-header-soft {
            width: calc(100% - 14px) !important;
            min-height: 132px !important;
            border-radius: 16px !important;
        }

        .exclusive-offers-banner .banner-content,
        .section-header-soft .content {
            min-height: 132px !important;
            gap: 10px !important;
        }

        .banner-text,
        .section-header-soft h2 {
            font-size: 18px !important;
            line-height: 1.22 !important;
        }

        .banner-btn,
        .section-header-soft .btn-soft {
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
        width: 100%;
        min-width: 0;
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

    #contenedor-promociones-productos,
    #contenedor-nuevos-productos,
    #productos-mas-vendidos {
        --bs-gutter-x: 0;
        --bs-gutter-y: 0;
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 30px 24px !important;
        align-items: stretch;
        justify-content: center;
        width: 100%;
        max-width: 930px;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    #contenedor-promociones-productos>div,
    #contenedor-nuevos-productos>div,
    #productos-mas-vendidos>div {
        width: 100% !important;
        max-width: none !important;
        min-width: 0 !important;
        flex: none !important;
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin: 0 !important;
    }

    #contenedor-promociones-productos .single-arrivals-products,
    #contenedor-nuevos-productos .single-arrivals-products,
    #productos-mas-vendidos .single-arrivals-products {
        height: 100%;
        margin-bottom: 0;
    }

    @media (max-width: 991px) {

        #contenedor-promociones-productos,
        #contenedor-nuevos-productos,
        #productos-mas-vendidos {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            max-width: 660px;
            gap: 22px 16px !important;
        }
    }

    @media (max-width: 575px) {

        #contenedor-promociones-productos,
        #contenedor-nuevos-productos,
        #productos-mas-vendidos {
            max-width: 100%;
            gap: 16px 12px !important;
        }
    }

    @media (max-width: 359px) {

        #contenedor-promociones-productos,
        #contenedor-nuevos-productos,
        #productos-mas-vendidos {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
            max-width: 100%;
            gap: 16px 12px !important;
        }
    }

    @media (max-width: 767px) {

        #contenedor-promociones-productos>div,
        #contenedor-nuevos-productos>div,
        #productos-mas-vendidos>div {
            flex: 0 0 50%;
            max-width: 50%;
            min-width: 0;
            padding-left: 6px;
            padding-right: 6px;
            margin-bottom: 12px;
        }

        .single-arrivals-products {
            border-width: 3px;
            border-radius: 12px;
            padding: 10px;
        }

        .arrivals-products-content h3 {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .single-arrivals-products .arrivals-products-image .tag {
            font-size: 11px !important;
            padding: 4px 10px !important;
            top: 8px !important;
            right: 8px !important;
        }

        .arrivals-products-image {
            aspect-ratio: 1 / 1;
        }

        .arrivals-products-area,
        .special-products-area {
            padding-top: 0 !important;
        }

        @media (max-width: 767px) {

            .arrivals-products-area,
            .special-products-area {
                padding-top: 0 !important;
                padding-bottom: 30px !important;
            }
        }

    }
</style>

<!-- Banner Ofertas Exclusivas -->
<div class="exclusive-offers-banner banner-main">
    <div class="container">
        <div class="banner-content">
            <span class="banner-text">OFERTAS EXCLUSIVAS SOLO POR HOY</span>
            <a href="tienda.php" class="banner-btn">VER PRODUCTOS</a>
        </div>
    </div>
</div>

<!-- Start Arrivals Products Area -->
<section class="arrivals-products-area products-after-banner pb-70">
    <div class="container">
        <div class="row" id="contenedor-promociones-productos">

            <!-- Aquí se insertarán los productos dinámicos -->

        </div>
    </div>
</section>

<style>
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
        min-height: 180px;
        overflow: hidden;
        isolation: isolate;
        display: flex;
        justify-content: center;
        align-items: center;
        background: #F7F8FC;
        padding: 0;
        margin-bottom: 60px;
    }

    .products-after-banner {
        padding-top: 0 !important;
    }

    .section-header-soft .wave {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, #1A2697 0%, #B73639 58%, #466934 100%);
        /* Fondo gradiente Marquense */
        box-shadow: inset 0 0 50px rgba(0, 0, 0, 0.1);
        z-index: 0;
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
        background: #466934;
        color: #fff;
        padding: 12px 35px;
        /* Botón un poco más compacto */
        border-radius: 50px;
        font-weight: 700;
        display: inline-block;
        font-size: 16px;
        text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        text-decoration: none;
        position: relative;
    }

    .section-header-soft .btn-soft:hover {
        background: #355329;
        color: #fff;
        box-shadow: 0 6px 20px rgba(70, 105, 52, 0.35);
        transform: translateY(-3px) scale(1.05);
    }

    @media (max-width: 768px) {
        .section-header-soft {
            min-height: 180px;
            margin-bottom: 35px;
        }

        .section-header-soft h2 {
            font-size: 1.8em;
        }

        .section-header-soft .btn-soft {
            padding: 10px 22px;
            font-size: 14px;
        }

    }
</style>

<div class="section-header-soft">
    <div class="wave"></div>
    <div class="content container">
        <h2>NUEVOS PRODUCTOS</h2>
        <a href="tienda.php" class="btn-soft">VER PRODUCTOS</a>
    </div>
</div>

<!-- Start Arrivals Products Area -->
<section class="arrivals-products-area products-after-banner pb-70">
    <div class="container">


        <div class="row" id="contenedor-nuevos-productos">

            <!-- Aquí se insertarán los productos dinámicos -->

        </div>
    </div>
</section>

<!-- End Arrivals Products Area -->
<div class="exclusive-offers-banner">
    <div class="container">
        <div class="banner-content">
            <span class="banner-text">LOS M&Aacute;S VENDIDOS</span>
            <a href="tienda.php" class="banner-btn">VER PRODUCTOS</a>
        </div>
    </div>
</div>


<!-- Start Special Products Area -->
<section class="special-products-area products-after-banner pb-70">
    <div class="container">
        <div class="row">


            <div class="col-lg-12 col-md-12">


                <div class="row" id="productos-mas-vendidos">
                    <!-- Aquí se llenarán los productos dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End Special Products Area -->

<?php
$iconosCategoriasInicio = [
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
    "VIDRIOS DE CAMARAS" => "bx bx-camera",
    "PEGAMENTO IMPERMEABLE" => "bx bx-water",
    "SOPORTE" => "bx bx-package",
    "BOCINA" => "bx bx-volume-full",
];

$categoriasInicio = isset($categorias) && is_array($categorias)
    ? array_values(array_filter($categorias, static function ($cat) {
        return isset($cat['condicion']) && (string) $cat['condicion'] === '1';
    }))
    : [];
?>

<style>
    .home-categories-area {
        background: #F7F8FC;
        padding: 22px 0 30px;
    }

    .home-categories-shell {
        position: relative;
        width: calc(100% - 24px);
        max-width: 1360px;
        margin: 0 auto;
        padding: 0 12px;
        background: transparent;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        overflow: visible;
    }

    .home-categories-shell::before {
        display: none;
    }

    .home-categories-title {
        margin: 0 0 12px;
        color: #1f2430;
        font-size: 23px;
        font-weight: 800;
        line-height: 1.2;
    }

    .home-categories-viewport {
        margin: 0 -4px;
        overflow-x: auto;
        overflow-y: hidden;
        scroll-behavior: smooth;
        scroll-snap-type: x proximity;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
        padding-top: 6px;
        padding-bottom: 4px;
    }

    .home-categories-viewport::-webkit-scrollbar {
        display: none;
    }

    .home-categories-track {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: max-content;
        padding: 2px 4px 4px;
    }

    .home-category-card {
        width: 112px;
        flex: 0 0 112px;
        text-align: center;
        color: #292929;
        text-decoration: none;
        scroll-snap-align: center;
    }

    .home-category-card:hover,
    .home-category-card:focus {
        color: #111111;
        text-decoration: none;
    }

    .home-category-circle {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 78px;
        height: 78px;
        margin: 0 auto 7px;
        border-radius: 50%;
        background: #ffffff;
        border: 2px solid #050505;
        box-shadow: none;
        transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
    }

    .home-category-circle::after {
        display: none;
    }

    .home-category-circle i {
        position: relative;
        z-index: 2;
        color: #050505;
        font-size: 32px;
        line-height: 1;
        transition: color .22s ease, transform .22s ease;
    }

    .home-category-card:hover .home-category-circle,
    .home-category-card:focus .home-category-circle {
        border-color: #050505;
        box-shadow: none;
        transform: translateY(-2px);
    }

    .home-category-card:hover .home-category-circle::after,
    .home-category-card:focus .home-category-circle::after {
        opacity: .72;
    }

    .home-category-card:hover .home-category-circle i,
    .home-category-card:focus .home-category-circle i {
        color: #050505;
        transform: scale(1.06);
    }

    .home-category-label {
        display: block;
        min-height: 30px;
        color: #333333;
        font-size: 14px;
        font-weight: 700;
        line-height: 1.22;
        overflow: hidden;
        overflow-wrap: normal;
        word-break: normal;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .home-categories-controls {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 0;
    }

    .home-categories-arrow {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border: 3px solid #ffffff;
        outline: 1px solid #050505;
        border-radius: 50%;
        background: #050505;
        color: #ffffff;
        box-shadow: none;
        cursor: pointer;
        transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
    }

    .home-categories-arrow:first-child {
        background: #050505;
        box-shadow: none;
    }

    .home-categories-arrow:hover,
    .home-categories-arrow:focus {
        transform: translateY(-2px);
        background: #171717;
        color: #ffffff;
        box-shadow: none;
    }

    .home-categories-arrow:first-child:hover,
    .home-categories-arrow:first-child:focus {
        background: #171717;
    }

    .home-categories-arrow i {
        color: #ffffff;
        font-size: 24px;
        line-height: 1;
    }

    .home-categories-empty {
        margin: 0;
        color: #555555;
        font-size: 16px;
    }

    @media (max-width: 991px) {
        .home-categories-area {
            padding: 20px 0 28px;
        }

        .home-categories-shell {
            width: calc(100% - 18px);
            padding: 0 10px;
        }

        .home-categories-title {
            margin-bottom: 11px;
            font-size: 20px;
        }

        .home-categories-track {
            gap: 8px;
            padding-top: 2px;
            padding-bottom: 4px;
        }

        .home-category-card {
            width: 96px;
            flex-basis: 96px;
        }

        .home-category-circle {
            width: 70px;
            height: 70px;
            margin-bottom: 6px;
        }

        .home-category-circle i {
            font-size: 28px;
        }

        .home-category-label {
            min-height: 27px;
            font-size: 10.5px;
            line-height: 1.18;
        }
    }

    @media (max-width: 480px) {
        .home-categories-shell {
            padding-left: 14px;
            padding-right: 14px;
        }

        .home-categories-title {
            font-size: 19px;
        }

        .home-category-card {
            width: 86px;
            flex-basis: 86px;
        }

        .home-category-circle {
            width: 62px;
            height: 62px;
            box-shadow: none;
        }

        .home-category-circle::after {
            inset: 12px;
        }

        .home-category-circle i {
            font-size: 25px;
        }

        .home-category-label {
            min-height: 24px;
            font-size: 9.5px;
            line-height: 1.16;
        }

        .home-categories-arrow {
            width: 34px;
            height: 34px;
        }
    }
</style>

<section class="home-categories-area" aria-labelledby="home-categories-title">
    <div class="home-categories-shell">
        <h2 id="home-categories-title" class="home-categories-title">Buscar por categor&iacute;a</h2>

        <?php if (!empty($categoriasInicio)): ?>
            <div class="home-categories-viewport" id="home-categories-viewport">
                <div class="home-categories-track">
                    <?php foreach ($categoriasInicio as $cat):
                        $nombreCategoriaInicio = strtoupper(trim((string) ($cat['nombre'] ?? '')));
                        $iconoCategoriaInicio = $iconosCategoriasInicio[$nombreCategoriaInicio] ?? "bx bx-category";

                        if (strpos($nombreCategoriaInicio, 'ANTENA') !== false || (strpos($nombreCategoriaInicio, 'FLEX') !== false && strpos($nombreCategoriaInicio, 'SE') !== false)) {
                            $iconoCategoriaInicio = "bx bx-broadcast";
                        }

                        $idCategoriaInicio = (string) ($cat['idcategoria'] ?? '');
                        $textoCategoriaInicio = (string) ($cat['nombre'] ?? 'Categoria');
                    ?>
                        <a class="home-category-card" href="tienda.php?categoria=<?= urlencode($idCategoriaInicio) ?>" aria-label="Ver productos de <?= htmlspecialchars($textoCategoriaInicio, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="home-category-circle" aria-hidden="true">
                                <i class="<?= htmlspecialchars($iconoCategoriaInicio, ENT_QUOTES, 'UTF-8') ?>"></i>
                            </span>
                            <span class="home-category-label"><?= htmlspecialchars($textoCategoriaInicio, ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="home-categories-controls" aria-label="Controles de categorias">
                <button class="home-categories-arrow" type="button" data-home-categories-dir="-1" aria-label="Categorias anteriores">
                    <i class="bx bx-chevron-left" aria-hidden="true"></i>
                </button>
                <button class="home-categories-arrow" type="button" data-home-categories-dir="1" aria-label="Categorias siguientes">
                    <i class="bx bx-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        <?php else: ?>
            <p class="home-categories-empty">No hay categor&iacute;as disponibles.</p>
        <?php endif; ?>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var viewport = document.getElementById('home-categories-viewport');

        if (!viewport) {
            return;
        }

        var buttons = document.querySelectorAll('[data-home-categories-dir]');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var direction = parseInt(button.getAttribute('data-home-categories-dir'), 10) || 1;
                var amount = Math.max(220, Math.floor(viewport.clientWidth * 0.82));

                viewport.scrollBy({
                    left: amount * direction,
                    behavior: 'smooth'
                });
            });
        });
    });
</script>




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
        background: #B73639;
        color: #fff;
        border-color: #B73639;
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
        background: #466934;
    }

    .mp-whatsapp:hover {
        background: #466934;
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
                        <button class="mp-btn-agregar" onclick="agregarAlCarritoDesdeModal()">🛒
                            Agregar al
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    function normalizeText(text) {
        return text.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    document.querySelectorAll('a').forEach(function (link) {
        if (link.textContent.trim() === 'Dirigite a Nosotros') {
            link.setAttribute('href', 'contact.php');
        }
    });

    document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, span, strong').forEach(function (element) {
        if (normalizeText(element.textContent) === 'reparaciones') {
            var item = element.closest('[class*="col-"]') || element.closest('.single-feature, .feature-box, .single-service, .service-box') || element.parentElement;
            if (item) {
                item.remove();
            }
        }
    });

    var featureTitles = ['celulares y accesorios', 'envios', 'asesoria tecnica'];
    var featureItems = [];
    document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, span, strong').forEach(function (element) {
        if (featureTitles.indexOf(normalizeText(element.textContent)) !== -1) {
            var item = element.closest('[class*="col-"]') || element.closest('.single-feature, .feature-box, .single-service, .service-box') || element.parentElement;
            if (item && featureItems.indexOf(item) === -1) {
                featureItems.push(item);
            }
        }
    });

    if (featureItems.length === 3 && featureItems[0].parentElement) {
        var featuresRow = featureItems[0].parentElement;
        var slider = document.querySelector('.slider-area, .home-slider, .hero-slider, .main-slider, .owl-carousel, .swiper, #slider, .slider');
        var sliderWidth = slider ? slider.getBoundingClientRect().width : 0;

        if (!sliderWidth) {
            document.querySelectorAll('section, div').forEach(function (element) {
                var box = element.getBoundingClientRect();
                var firstBox = featureItems[0].getBoundingClientRect();
                if (box.bottom <= firstBox.top && box.width > sliderWidth && box.width < window.innerWidth * 0.95) {
                    sliderWidth = box.width;
                }
            });
        }

        featuresRow.style.display = 'flex';
        featuresRow.style.justifyContent = 'center';
        featuresRow.style.flexWrap = 'wrap';
        featuresRow.style.marginLeft = 'auto';
        featuresRow.style.marginRight = 'auto';
        if (sliderWidth) {
            featuresRow.style.maxWidth = sliderWidth + 'px';
        }

        featureItems.forEach(function (item) {
            item.style.float = 'none';
        });
    }

    return;

    function addCircularSliderStyle() {
        if (document.getElementById('inicio-circular-slider-style')) {
            return;
        }

        var style = document.createElement('style');
        style.id = 'inicio-circular-slider-style';
        style.textContent = `
            .inicio-circular-slider {
                position: relative;
                overflow: hidden;
            }

            .inicio-circular-slider .inicio-circular-carousel {
                position: absolute;
                top: 50%;
                right: clamp(34px, 7vw, 105px);
                width: clamp(210px, 24vw, 300px);
                height: clamp(210px, 24vw, 300px);
                transform: translateY(-50%);
                z-index: 5;
                pointer-events: none;
            }

            .inicio-circular-slider .inicio-circular-carousel__item {
                position: absolute;
                display: block;
                width: clamp(66px, 7vw, 92px);
                height: clamp(66px, 7vw, 92px);
                padding: 0;
                border: 3px solid rgba(255, 255, 255, 0.92);
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.16);
                box-shadow: 0 12px 26px rgba(0, 0, 0, 0.24);
                overflow: hidden;
                pointer-events: auto;
            }

            .inicio-circular-slider .inicio-circular-carousel__item img {
                display: block;
                width: 100%;
                height: 100%;
                border-radius: 50%;
                object-fit: cover;
            }

            .inicio-circular-slider .inicio-circular-carousel__item.is-main {
                top: 50%;
                left: 40%;
                width: clamp(108px, 11vw, 148px);
                height: clamp(108px, 11vw, 148px);
                transform: translate(-50%, -50%);
                z-index: 3;
            }

            .inicio-circular-slider .inicio-circular-carousel__item.is-top {
                top: 14%;
                right: 3%;
                z-index: 2;
            }

            .inicio-circular-slider .inicio-circular-carousel__item.is-bottom {
                right: 18%;
                bottom: 8%;
                z-index: 2;
            }

            .inicio-circular-slider-side-preview {
                opacity: 0 !important;
                pointer-events: none !important;
            }

            .inicio-circular-slider .owl-nav,
            .inicio-circular-slider-nav-scope .owl-nav {
                position: absolute !important;
                right: clamp(84px, 12vw, 175px) !important;
                bottom: clamp(28px, 6vw, 56px) !important;
                left: auto !important;
                top: auto !important;
                display: flex !important;
                gap: 10px !important;
                align-items: center !important;
                justify-content: center !important;
                z-index: 8 !important;
            }

            .inicio-circular-slider .owl-nav button,
            .inicio-circular-slider .owl-nav div,
            .inicio-circular-slider-nav-scope .owl-nav button,
            .inicio-circular-slider-nav-scope .owl-nav div {
                position: static !important;
                transform: none !important;
                margin: 0 !important;
            }

            .inicio-circular-slider .slick-prev,
            .inicio-circular-slider .slick-next,
            .inicio-circular-slider .swiper-button-prev,
            .inicio-circular-slider .swiper-button-next,
            .inicio-circular-slider-nav-scope .slick-prev,
            .inicio-circular-slider-nav-scope .slick-next,
            .inicio-circular-slider-nav-scope .swiper-button-prev,
            .inicio-circular-slider-nav-scope .swiper-button-next {
                top: auto !important;
                bottom: clamp(28px, 6vw, 56px) !important;
                z-index: 8 !important;
                transform: none !important;
            }

            .inicio-circular-slider .slick-prev,
            .inicio-circular-slider .swiper-button-prev,
            .inicio-circular-slider-nav-scope .slick-prev,
            .inicio-circular-slider-nav-scope .swiper-button-prev {
                left: auto !important;
                right: clamp(128px, 15vw, 220px) !important;
            }

            .inicio-circular-slider .slick-next,
            .inicio-circular-slider .swiper-button-next,
            .inicio-circular-slider-nav-scope .slick-next,
            .inicio-circular-slider-nav-scope .swiper-button-next {
                right: clamp(86px, 12vw, 172px) !important;
            }

            @media (max-width: 767px) {
                .inicio-circular-slider .inicio-circular-carousel {
                    display: none;
                }

                .inicio-circular-slider .owl-nav,
                .inicio-circular-slider-nav-scope .owl-nav {
                    right: 24px !important;
                    bottom: 22px !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function getImageUrl(value) {
        if (!value || value === 'none') {
            return '';
        }

        var match = value.match(/url\\([\"']?([^\"')]+)[\"']?\\)/);
        return match ? match[1] : '';
    }

    function getSliderImages(slider) {
        var images = [];

        slider.querySelectorAll('img').forEach(function (image) {
            if (image.currentSrc || image.src) {
                images.push(image.currentSrc || image.src);
            }
        });

        slider.querySelectorAll('*').forEach(function (element) {
            var image = getImageUrl(window.getComputedStyle(element).backgroundImage);
            if (image) {
                images.push(image);
            }
        });

        var sliderBackground = getImageUrl(window.getComputedStyle(slider).backgroundImage);
        if (sliderBackground) {
            images.push(sliderBackground);
        }

        return images.filter(function (image, index, list) {
            return image && list.indexOf(image) === index && image.indexOf('logo') === -1;
        });
    }

    function findInicioSlider() {
        var actionButton = Array.from(document.querySelectorAll('a')).find(function (link) {
            var text = normalizeText(link.textContent);
            return text === 'cotizar ahora' || text === 'comprar ahora' || text === 'dirigite a nosotros';
        });

        if (!actionButton) {
            return null;
        }

        var current = actionButton.parentElement;
        while (current && current !== document.body) {
            var box = current.getBoundingClientRect();
            if (box.width > 520 && box.height > 180) {
                return current;
            }
            current = current.parentElement;
        }

        return null;
    }

    function buildCircularSlider() {
        var slider = findInicioSlider();
        if (!slider || slider.classList.contains('inicio-circular-slider')) {
            return;
        }

        var images = getSliderImages(slider);
        if (!images.length) {
            return;
        }

        while (images.length < 3) {
            images = images.concat(images);
        }

        addCircularSliderStyle();
        slider.classList.add('inicio-circular-slider');

        var navScope = slider;
        var parent = slider;
        while (parent && parent !== document.body) {
            if (parent.querySelector('.owl-nav, .slick-prev, .slick-next, .swiper-button-prev, .swiper-button-next')) {
                navScope = parent;
                break;
            }
            parent = parent.parentElement;
        }
        navScope.classList.add('inicio-circular-slider-nav-scope');

        var carousel = document.createElement('div');
        carousel.className = 'inicio-circular-carousel';
        slider.appendChild(carousel);

        var positions = ['is-main', 'is-top', 'is-bottom'];
        var active = 0;

        function renderCircularItems() {
            carousel.innerHTML = '';
            positions.forEach(function (position, index) {
                var button = document.createElement('button');
                var image = document.createElement('img');

                button.type = 'button';
                button.className = 'inicio-circular-carousel__item ' + position;
                image.src = images[(active + index) % images.length];
                image.alt = '';

                button.appendChild(image);
                button.addEventListener('click', function () {
                    active = (active + index) % images.length;
                    renderCircularItems();
                });

                carousel.appendChild(button);
            });
        }

        renderCircularItems();

        slider.querySelectorAll('.owl-next, .slick-next, .swiper-button-next').forEach(function (button) {
            button.addEventListener('click', function () {
                active = (active + 1) % images.length;
                renderCircularItems();
                setTimeout(hideRectangularSidePreviews, 80);
            });
        });

        slider.querySelectorAll('.owl-prev, .slick-prev, .swiper-button-prev').forEach(function (button) {
            button.addEventListener('click', function () {
                active = (active - 1 + images.length) % images.length;
                renderCircularItems();
                setTimeout(hideRectangularSidePreviews, 80);
            });
        });

        function hideRectangularSidePreviews() {
            var sliderBox = slider.getBoundingClientRect();
            navScope.querySelectorAll('.owl-item, .slick-slide').forEach(function (slide) {
                var slideBox = slide.getBoundingClientRect();
                var isSidePreview = slideBox.left > sliderBox.left + (sliderBox.width * 0.55) || slideBox.right < sliderBox.left + (sliderBox.width * 0.45);

                if (isSidePreview && !slide.contains(slider)) {
                    slide.classList.add('inicio-circular-slider-side-preview');
                } else {
                    slide.classList.remove('inicio-circular-slider-side-preview');
                }
            });
        }

        hideRectangularSidePreviews();
        window.addEventListener('resize', hideRectangularSidePreviews);
        if (window.MutationObserver) {
            new MutationObserver(hideRectangularSidePreviews).observe(navScope, {
                attributes: true,
                childList: true,
                subtree: true
            });
        }
    }

    buildCircularSlider();
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        return;

        function cleanSliderText(text) {
            return text.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function imageFromBackground(value) {
            var match = value && value.match(/url\(["']?([^"')]+)["']?\)/);
            return match ? match[1] : '';
        }

        function collectSliderImages(scope) {
            var images = [];

            scope.querySelectorAll('img').forEach(function (image) {
                if (image.currentSrc || image.src) {
                    images.push(image.currentSrc || image.src);
                }
            });

            scope.querySelectorAll('*').forEach(function (element) {
                var background = imageFromBackground(window.getComputedStyle(element).backgroundImage);
                if (background) {
                    images.push(background);
                }
            });

            return images.filter(function (image, index, list) {
                return image && list.indexOf(image) === index && image.toLowerCase().indexOf('logo') === -1;
            });
        }

        function addFinalSliderStyle() {
            if (document.getElementById('lm-final-circular-slider-style')) {
                return;
            }

            var style = document.createElement('style');
            style.id = 'lm-final-circular-slider-style';
            style.textContent = `
                .lm-final-circular-slider {
                    position: relative !important;
                    overflow: hidden !important;
                }

                .lm-final-circular-slider .owl-stage {
                    width: 100% !important;
                    transform: none !important;
                }

                .lm-final-circular-slider .owl-item {
                    float: none !important;
                }

                .lm-final-circular-slider .owl-item.lm-final-main-slide {
                    display: block !important;
                    width: 100% !important;
                    opacity: 1 !important;
                    position: relative !important;
                    z-index: 2 !important;
                }

                .lm-final-circular-slider .owl-item:not(.lm-final-main-slide) {
                    display: none !important;
                }

                .lm-final-circular-thumbs {
                    position: absolute;
                    top: 50%;
                    right: clamp(40px, 8vw, 118px);
                    width: clamp(205px, 24vw, 310px);
                    height: clamp(205px, 24vw, 310px);
                    transform: translateY(-50%);
                    z-index: 7;
                    pointer-events: none;
                }

                .lm-final-circular-thumb {
                    position: absolute;
                    display: block;
                    padding: 0;
                    border: 3px solid rgba(255, 255, 255, 0.95);
                    border-radius: 50% !important;
                    background: rgba(255, 255, 255, 0.16);
                    box-shadow: 0 12px 26px rgba(0, 0, 0, 0.24);
                    overflow: hidden;
                    pointer-events: auto;
                }

                .lm-final-circular-thumb img {
                    display: block;
                    width: 100%;
                    height: 100%;
                    border-radius: 50% !important;
                    object-fit: cover;
                }

                .lm-final-circular-thumb.is-main {
                    top: 50%;
                    left: 35%;
                    width: clamp(112px, 11vw, 152px);
                    height: clamp(112px, 11vw, 152px);
                    transform: translate(-50%, -50%);
                    z-index: 3;
                }

                .lm-final-circular-thumb.is-top {
                    top: 13%;
                    right: 4%;
                    width: clamp(70px, 7vw, 96px);
                    height: clamp(70px, 7vw, 96px);
                    z-index: 2;
                }

                .lm-final-circular-thumb.is-bottom {
                    right: 18%;
                    bottom: 7%;
                    width: clamp(70px, 7vw, 96px);
                    height: clamp(70px, 7vw, 96px);
                    z-index: 2;
                }

                .lm-final-circular-slider .owl-nav {
                    position: absolute !important;
                    left: auto !important;
                    top: auto !important;
                    right: clamp(95px, 13vw, 188px) !important;
                    bottom: clamp(30px, 6vw, 58px) !important;
                    display: flex !important;
                    gap: 10px !important;
                    align-items: center !important;
                    justify-content: center !important;
                    z-index: 9 !important;
                }

                .lm-final-circular-slider .owl-nav button,
                .lm-final-circular-slider .owl-nav div {
                    position: static !important;
                    transform: none !important;
                    margin: 0 !important;
                }

                @media (max-width: 767px) {
                    .lm-final-circular-thumbs {
                        display: none;
                    }

                    .lm-final-circular-slider .owl-nav {
                        right: 24px !important;
                        bottom: 22px !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        document.querySelectorAll('.inicio-circular-carousel').forEach(function (element) {
            element.remove();
        });

        document.querySelectorAll('.inicio-circular-slider, .inicio-circular-slider-nav-scope, .inicio-circular-slider-side-preview').forEach(function (element) {
            element.classList.remove('inicio-circular-slider', 'inicio-circular-slider-nav-scope', 'inicio-circular-slider-side-preview');
        });

        var actionButton = Array.from(document.querySelectorAll('a')).find(function (link) {
            var text = cleanSliderText(link.textContent);
            return text === 'cotizar ahora' || text === 'comprar ahora' || text === 'dirigite a nosotros';
        });

        if (!actionButton) {
            return;
        }

        var mainSlide = actionButton.closest('.owl-item');
        var slider = mainSlide ? mainSlide.closest('.owl-carousel') : null;

        if (!slider) {
            var current = actionButton.parentElement;
            while (current && current !== document.body) {
                var box = current.getBoundingClientRect();
                if (box.width > 520 && box.height > 190) {
                    slider = current;
                }
                current = current.parentElement;
            }
        }

        if (!slider || slider.classList.contains('lm-final-circular-slider')) {
            return;
        }

        addFinalSliderStyle();
        slider.classList.add('lm-final-circular-slider');

        if (mainSlide) {
            slider.querySelectorAll('.owl-item').forEach(function (item) {
                item.classList.remove('lm-final-main-slide');
            });
            mainSlide.classList.add('lm-final-main-slide');
        }

        var images = collectSliderImages(slider);
        if (!images.length && mainSlide) {
            images = collectSliderImages(mainSlide);
        }

        while (images.length < 3 && images.length > 0) {
            images = images.concat(images);
        }

        if (!images.length) {
            return;
        }

        var thumbs = document.createElement('div');
        thumbs.className = 'lm-final-circular-thumbs';
        slider.appendChild(thumbs);

        ['is-main', 'is-top', 'is-bottom'].forEach(function (position, index) {
            var thumb = document.createElement('button');
            var image = document.createElement('img');

            thumb.type = 'button';
            thumb.className = 'lm-final-circular-thumb ' + position;
            image.src = images[index];
            image.alt = '';

            thumb.appendChild(image);
            thumbs.appendChild(thumb);
        });
    }, 120);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        return;

        function plain(text) {
            return (text || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function bgImage(value) {
            var match = value && value.match(/url\(["']?([^"')]+)["']?\)/);
            return match ? match[1] : '';
        }

        function hasSliderButton(element) {
            return Array.from(element.querySelectorAll('a, button')).some(function (button) {
                var text = plain(button.textContent);
                return text === 'cotizar ahora' || text === 'comprar ahora' || text === 'dirigite a nosotros';
            });
        }

        function getImages(element) {
            var images = [];

            element.querySelectorAll('img').forEach(function (image) {
                var src = image.currentSrc || image.src;
                if (src) {
                    images.push(src);
                }
            });

            element.querySelectorAll('*').forEach(function (node) {
                var image = bgImage(window.getComputedStyle(node).backgroundImage);
                if (image) {
                    images.push(image);
                }
            });

            var ownBackground = bgImage(window.getComputedStyle(element).backgroundImage);
            if (ownBackground) {
                images.push(ownBackground);
            }

            return images.filter(function (image, index, list) {
                var clean = image.toLowerCase();
                return image && list.indexOf(image) === index && clean.indexOf('logo') === -1 && clean.indexOf('whatsapp') === -1;
            });
        }

        function addStyle() {
            if (document.getElementById('lm-professional-circular-slider-style')) {
                return;
            }

            var style = document.createElement('style');
            style.id = 'lm-professional-circular-slider-style';
            style.textContent = `
                #inicio-circular-slider-style,
                #lm-final-circular-slider-style {
                    display: none !important;
                }

                .inicio-circular-carousel,
                .lm-final-circular-thumbs {
                    display: none !important;
                }

                .lm-professional-circular-slider {
                    position: relative !important;
                    overflow: hidden !important;
                }

                .lm-professional-circular-slider .owl-stage-outer,
                .lm-professional-circular-slider .carousel-inner {
                    overflow: hidden !important;
                }

                .lm-professional-circular-slider .owl-stage {
                    width: 100% !important;
                    transform: none !important;
                    transition: none !important;
                }

                .lm-professional-circular-slider .owl-item {
                    position: absolute !important;
                    inset: 0 !important;
                    width: 100% !important;
                    opacity: 0 !important;
                    visibility: hidden !important;
                    pointer-events: none !important;
                }

                .lm-professional-circular-slider .owl-item.lm-professional-main-slide {
                    position: relative !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    pointer-events: auto !important;
                    z-index: 2 !important;
                }

                .lm-professional-circular-slider .carousel-item:not(.lm-professional-main-slide) {
                    opacity: 0 !important;
                    visibility: hidden !important;
                    pointer-events: none !important;
                }

                .lm-professional-circular-slider .carousel-item.lm-professional-main-slide {
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }

                .lm-professional-circle-nav {
                    position: absolute;
                    top: 50%;
                    right: clamp(42px, 8vw, 115px);
                    width: clamp(210px, 23vw, 300px);
                    height: clamp(210px, 23vw, 300px);
                    transform: translateY(-50%);
                    z-index: 8;
                    pointer-events: none;
                }

                .lm-professional-circle-nav__item {
                    position: absolute;
                    display: block;
                    padding: 0;
                    border: 3px solid rgba(255, 255, 255, 0.96);
                    border-radius: 50% !important;
                    background: rgba(255, 255, 255, 0.22);
                    box-shadow: 0 13px 28px rgba(0, 0, 0, 0.28);
                    overflow: hidden;
                    pointer-events: auto;
                }

                .lm-professional-circle-nav__item img {
                    display: block;
                    width: 100%;
                    height: 100%;
                    border-radius: 50% !important;
                    object-fit: cover;
                }

                .lm-professional-circle-nav__item.is-active {
                    top: 50%;
                    left: 36%;
                    width: clamp(116px, 11vw, 150px);
                    height: clamp(116px, 11vw, 150px);
                    transform: translate(-50%, -50%);
                    z-index: 3;
                }

                .lm-professional-circle-nav__item.is-next {
                    top: 13%;
                    right: 4%;
                    width: clamp(72px, 7vw, 96px);
                    height: clamp(72px, 7vw, 96px);
                    z-index: 2;
                }

                .lm-professional-circle-nav__item.is-prev {
                    right: 18%;
                    bottom: 7%;
                    width: clamp(72px, 7vw, 96px);
                    height: clamp(72px, 7vw, 96px);
                    z-index: 2;
                }

                .lm-professional-circular-slider .owl-nav,
                .lm-professional-circular-slider .carousel-control-prev,
                .lm-professional-circular-slider .carousel-control-next {
                    z-index: 10 !important;
                }

                .lm-professional-circular-slider .owl-nav {
                    position: absolute !important;
                    top: auto !important;
                    left: auto !important;
                    right: clamp(96px, 13vw, 182px) !important;
                    bottom: clamp(30px, 6vw, 58px) !important;
                    display: flex !important;
                    gap: 10px !important;
                    align-items: center !important;
                    justify-content: center !important;
                }

                .lm-professional-circular-slider .owl-nav button,
                .lm-professional-circular-slider .owl-nav div {
                    position: static !important;
                    margin: 0 !important;
                    transform: none !important;
                }

                .lm-professional-circular-slider .carousel-control-prev,
                .lm-professional-circular-slider .carousel-control-next {
                    top: auto !important;
                    bottom: clamp(30px, 6vw, 58px) !important;
                    width: auto !important;
                    height: auto !important;
                    opacity: 1 !important;
                    transform: none !important;
                }

                .lm-professional-circular-slider .carousel-control-prev {
                    left: auto !important;
                    right: clamp(140px, 16vw, 225px) !important;
                }

                .lm-professional-circular-slider .carousel-control-next {
                    right: clamp(96px, 13vw, 182px) !important;
                }

                @media (max-width: 767px) {
                    .lm-professional-circle-nav {
                        display: none !important;
                    }

                    .lm-professional-circular-slider .owl-nav {
                        right: 24px !important;
                        bottom: 22px !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        function chooseSlider() {
            var candidates = Array.from(document.querySelectorAll('.owl-carousel, .carousel, [class*="slider"]')).filter(function (element) {
                var box = element.getBoundingClientRect();
                return box.width > 520 && box.height > 180 && hasSliderButton(element);
            });

            if (candidates.length) {
                return candidates[0];
            }

            var button = Array.from(document.querySelectorAll('a, button')).find(function (element) {
                var text = plain(element.textContent);
                return text === 'cotizar ahora' || text === 'comprar ahora' || text === 'dirigite a nosotros';
            });

            var current = button ? button.parentElement : null;
            while (current && current !== document.body) {
                var box = current.getBoundingClientRect();
                if (box.width > 520 && box.height > 180) {
                    return current;
                }
                current = current.parentElement;
            }

            return null;
        }

        function markMainSlide(slider) {
            var main = Array.from(slider.querySelectorAll('.owl-item, .carousel-item')).find(function (slide) {
                return hasSliderButton(slide) && slide.getBoundingClientRect().width > 0;
            }) || Array.from(slider.querySelectorAll('.owl-item.active, .carousel-item.active'))[0] || Array.from(slider.querySelectorAll('.owl-item, .carousel-item'))[0];

            slider.querySelectorAll('.owl-item, .carousel-item').forEach(function (slide) {
                slide.classList.remove('lm-professional-main-slide');
            });

            if (main) {
                main.classList.add('lm-professional-main-slide');
            }
        }

        function buildCircleNavigation(slider) {
            slider.querySelectorAll('.inicio-circular-carousel, .lm-final-circular-thumbs, .lm-professional-circle-nav').forEach(function (element) {
                element.remove();
            });

            var images = getImages(slider);
            while (images.length > 0 && images.length < 3) {
                images = images.concat(images);
            }

            if (!images.length) {
                return;
            }

            var active = 0;
            var nav = document.createElement('div');
            nav.className = 'lm-professional-circle-nav';
            slider.appendChild(nav);

            function render() {
                nav.innerHTML = '';

                [
                    { className: 'is-active', image: images[active % images.length] },
                    { className: 'is-next', image: images[(active + 1) % images.length] },
                    { className: 'is-prev', image: images[(active + 2) % images.length] }
                ].forEach(function (item, index) {
                    var button = document.createElement('button');
                    var image = document.createElement('img');

                    button.type = 'button';
                    button.className = 'lm-professional-circle-nav__item ' + item.className;
                    image.src = item.image;
                    image.alt = '';
                    button.appendChild(image);

                    button.addEventListener('click', function () {
                        active = (active + index) % images.length;
                        render();
                    });

                    nav.appendChild(button);
                });
            }

            render();

            slider.querySelectorAll('.owl-next, .carousel-control-next').forEach(function (button) {
                button.addEventListener('click', function () {
                    active = (active + 1) % images.length;
                    render();
                    setTimeout(function () {
                        markMainSlide(slider);
                    }, 80);
                });
            });

            slider.querySelectorAll('.owl-prev, .carousel-control-prev').forEach(function (button) {
                button.addEventListener('click', function () {
                    active = (active - 1 + images.length) % images.length;
                    render();
                    setTimeout(function () {
                        markMainSlide(slider);
                    }, 80);
                });
            });
        }

        document.querySelectorAll('#inicio-circular-slider-style, #lm-final-circular-slider-style').forEach(function (element) {
            element.remove();
        });

        document.querySelectorAll('.inicio-circular-carousel, .lm-final-circular-thumbs').forEach(function (element) {
            element.remove();
        });

        document.querySelectorAll('.inicio-circular-slider, .inicio-circular-slider-nav-scope, .inicio-circular-slider-side-preview, .lm-final-circular-slider').forEach(function (element) {
            element.classList.remove('inicio-circular-slider', 'inicio-circular-slider-nav-scope', 'inicio-circular-slider-side-preview', 'lm-final-circular-slider');
        });

        var slider = chooseSlider();
        if (!slider) {
            return;
        }

        addStyle();
        slider.classList.add('lm-professional-circular-slider');
        markMainSlide(slider);
        buildCircleNavigation(slider);

        if (window.MutationObserver) {
            new MutationObserver(function () {
                markMainSlide(slider);
            }).observe(slider, {
                attributes: true,
                subtree: true,
                attributeFilter: ['class', 'style']
            });
        }
    }, 650);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        return;

        function lmText(value) {
            return (value || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function lmButtonText(element) {
            return Array.from(element.querySelectorAll('a, button')).some(function (button) {
                var text = lmText(button.textContent);
                return text === 'cotizar ahora' || text === 'comprar ahora' || text === 'dirigite a nosotros';
            });
        }

        function lmBackground(value) {
            var match = value && value.match(/url\(["']?([^"')]+)["']?\)/);
            return match ? match[1] : '';
        }

        function lmSlideImage(slide) {
            var image = slide.querySelector('img');
            if (image && (image.currentSrc || image.src)) {
                return image.currentSrc || image.src;
            }

            var dataImage = slide.querySelector('[data-setbg], [data-bg], [data-background]');
            if (dataImage) {
                return dataImage.getAttribute('data-setbg') || dataImage.getAttribute('data-bg') || dataImage.getAttribute('data-background') || '';
            }

            var nodes = [slide].concat(Array.from(slide.querySelectorAll('*')));
            for (var i = 0; i < nodes.length; i++) {
                var found = lmBackground(window.getComputedStyle(nodes[i]).backgroundImage);
                if (found) {
                    return found;
                }
            }

            return '';
        }

        function lmAddRootStyle() {
            if (document.getElementById('lm-root-circular-slider-style')) {
                return;
            }

            var style = document.createElement('style');
            style.id = 'lm-root-circular-slider-style';
            style.textContent = `
                #inicio-circular-slider-style,
                #lm-final-circular-slider-style,
                #lm-professional-circular-slider-style,
                .inicio-circular-carousel,
                .lm-final-circular-thumbs,
                .lm-professional-circle-nav {
                    display: none !important;
                }

                [data-lm-root-circular="true"] {
                    position: relative !important;
                    overflow: hidden !important;
                }

                [data-lm-root-circular="true"] .owl-stage-outer {
                    overflow: hidden !important;
                }

                [data-lm-root-circular="true"] .owl-stage {
                    width: 100% !important;
                    transform: none !important;
                    transition: none !important;
                    display: block !important;
                }

                [data-lm-root-circular="true"] .owl-item {
                    float: none !important;
                    width: 100% !important;
                    transform: none !important;
                    transition: opacity .35s ease !important;
                }

                [data-lm-root-circular="true"] .owl-item:not(.lm-root-slide-active) {
                    display: none !important;
                    opacity: 0 !important;
                    visibility: hidden !important;
                    pointer-events: none !important;
                }

                [data-lm-root-circular="true"] .owl-item.lm-root-slide-active {
                    display: block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    pointer-events: auto !important;
                }

                .lm-root-circular-nav {
                    position: absolute;
                    top: 50%;
                    right: clamp(42px, 8vw, 118px);
                    width: clamp(215px, 24vw, 315px);
                    height: clamp(215px, 24vw, 315px);
                    transform: translateY(-50%);
                    z-index: 12;
                    pointer-events: none;
                }

                .lm-root-circular-nav__button {
                    position: absolute;
                    display: block;
                    padding: 0;
                    border: 3px solid rgba(255, 255, 255, .96);
                    border-radius: 50% !important;
                    background: rgba(255, 255, 255, .18);
                    box-shadow: 0 13px 28px rgba(0, 0, 0, .28);
                    overflow: hidden;
                    pointer-events: auto;
                }

                .lm-root-circular-nav__button img {
                    display: block;
                    width: 100%;
                    height: 100%;
                    border-radius: 50% !important;
                    object-fit: cover;
                }

                .lm-root-circular-nav__button.is-current {
                    top: 50%;
                    left: 35%;
                    width: clamp(118px, 11vw, 152px);
                    height: clamp(118px, 11vw, 152px);
                    transform: translate(-50%, -50%);
                    z-index: 3;
                }

                .lm-root-circular-nav__button.is-next {
                    top: 13%;
                    right: 4%;
                    width: clamp(72px, 7vw, 98px);
                    height: clamp(72px, 7vw, 98px);
                    z-index: 2;
                }

                .lm-root-circular-nav__button.is-prev {
                    right: 18%;
                    bottom: 7%;
                    width: clamp(72px, 7vw, 98px);
                    height: clamp(72px, 7vw, 98px);
                    z-index: 2;
                }

                [data-lm-root-circular="true"] .owl-nav {
                    position: absolute !important;
                    left: auto !important;
                    top: auto !important;
                    right: clamp(96px, 13vw, 184px) !important;
                    bottom: clamp(30px, 6vw, 58px) !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    gap: 10px !important;
                    z-index: 13 !important;
                }

                [data-lm-root-circular="true"] .owl-nav button,
                [data-lm-root-circular="true"] .owl-nav div {
                    position: static !important;
                    margin: 0 !important;
                    transform: none !important;
                }

                @media (max-width: 767px) {
                    .lm-root-circular-nav {
                        display: none !important;
                    }

                    [data-lm-root-circular="true"] .owl-nav {
                        right: 24px !important;
                        bottom: 22px !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        document.querySelectorAll('.inicio-circular-carousel, .lm-final-circular-thumbs, .lm-professional-circle-nav, .lm-root-circular-nav').forEach(function (element) {
            element.remove();
        });

        document.querySelectorAll('#inicio-circular-slider-style, #lm-final-circular-slider-style, #lm-professional-circular-slider-style').forEach(function (element) {
            element.remove();
        });

        var actionButton = Array.from(document.querySelectorAll('a, button')).find(function (button) {
            var text = lmText(button.textContent);
            return text === 'cotizar ahora' || text === 'comprar ahora' || text === 'dirigite a nosotros';
        });

        if (!actionButton) {
            return;
        }

        var slider = actionButton.closest('.owl-carousel') || actionButton.closest('[class*="slider"]');
        if (!slider) {
            return;
        }

        var stage = slider.querySelector('.owl-stage');
        var slides = Array.from(slider.querySelectorAll('.owl-item:not(.cloned)')).filter(function (slide) {
            return lmSlideImage(slide) || lmButtonText(slide);
        });

        if (!slides.length) {
            slides = Array.from(slider.children).filter(function (slide) {
                return lmSlideImage(slide) || lmButtonText(slide);
            });
        }

        if (!slides.length) {
            return;
        }

        var nav = slider.querySelector('.owl-nav');
        if (!nav && slider.parentElement) {
            nav = slider.parentElement.querySelector('.owl-nav');
        }

        if (nav && nav.parentElement !== slider) {
            slider.appendChild(nav);
        }

        lmAddRootStyle();
        slider.setAttribute('data-lm-root-circular', 'true');

        if (stage) {
            stage.style.width = '100%';
            stage.style.transform = 'none';
        }

        var activeIndex = Math.max(0, slides.findIndex(function (slide) {
            return slide.classList.contains('active') || lmButtonText(slide);
        }));

        var images = slides.map(lmSlideImage).filter(function (image) {
            return image;
        });

        while (images.length > 0 && images.length < 3) {
            images = images.concat(images);
        }

        var circleNav = document.createElement('div');
        circleNav.className = 'lm-root-circular-nav';
        slider.appendChild(circleNav);

        function showSlide(index) {
            activeIndex = (index + slides.length) % slides.length;

            slides.forEach(function (slide, slideIndex) {
                slide.classList.toggle('lm-root-slide-active', slideIndex === activeIndex);
                slide.classList.toggle('active', slideIndex === activeIndex);
                slide.style.display = slideIndex === activeIndex ? 'block' : 'none';
                slide.style.opacity = slideIndex === activeIndex ? '1' : '0';
                slide.style.visibility = slideIndex === activeIndex ? 'visible' : 'hidden';
                slide.style.width = '100%';
            });

            if (stage) {
                stage.style.width = '100%';
                stage.style.transform = 'none';
            }

            renderCircleNav();
        }

        function circleImage(offset) {
            if (!images.length) {
                return '';
            }

            return images[(activeIndex + offset + images.length) % images.length];
        }

        function renderCircleNav() {
            circleNav.innerHTML = '';

            [
                { className: 'is-current', offset: 0, target: activeIndex },
                { className: 'is-next', offset: 1, target: activeIndex + 1 },
                { className: 'is-prev', offset: -1, target: activeIndex - 1 }
            ].forEach(function (item) {
                var src = circleImage(item.offset);
                if (!src) {
                    return;
                }

                var button = document.createElement('button');
                var image = document.createElement('img');

                button.type = 'button';
                button.className = 'lm-root-circular-nav__button ' + item.className;
                image.src = src;
                image.alt = '';

                button.appendChild(image);
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    showSlide(item.target);
                });

                circleNav.appendChild(button);
            });
        }

        if (nav) {
            nav.querySelectorAll('.owl-prev, .prev, [class*="prev"]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    showSlide(activeIndex - 1);
                }, true);
            });

            nav.querySelectorAll('.owl-next, .next, [class*="next"]').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    showSlide(activeIndex + 1);
                }, true);
            });
        }

        showSlide(activeIndex);
    }, 1200);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        return;

        function normalText(value) {
            return (value || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function isSliderAction(element) {
            var text = normalText(element.textContent);
            return text === 'cotizar ahora' || text === 'comprar ahora' || text === 'dirigite a nosotros';
        }

        function hasSliderAction(scope) {
            return Array.from(scope.querySelectorAll('a, button')).some(isSliderAction);
        }

        function imageUrl(value) {
            var match = value && value.match(/url\(["']?([^"')]+)["']?\)/);
            return match ? match[1] : '';
        }

        function findImage(scope) {
            var image = scope.querySelector('img');
            if (image && (image.currentSrc || image.src)) {
                return image.currentSrc || image.src;
            }

            var dataImage = scope.querySelector('[data-setbg], [data-bg], [data-background]');
            if (dataImage) {
                return dataImage.getAttribute('data-setbg') || dataImage.getAttribute('data-bg') || dataImage.getAttribute('data-background') || '';
            }

            var nodes = [scope].concat(Array.from(scope.querySelectorAll('*')));
            for (var i = 0; i < nodes.length; i++) {
                var bg = imageUrl(window.getComputedStyle(nodes[i]).backgroundImage);
                if (bg) {
                    return bg;
                }
            }

            return '';
        }

        function resetGeneratedWork() {
            document.querySelectorAll(
                '#inicio-circular-slider-style, #lm-final-circular-slider-style, #lm-professional-circular-slider-style, #lm-root-circular-slider-style, #lm-clean-real-slider-style, .inicio-circular-carousel, .lm-final-circular-thumbs, .lm-professional-circle-nav, .lm-root-circular-nav, .lm-clean-real-slider'
            ).forEach(function (element) {
                element.remove();
            });

            document.querySelectorAll(
                '.inicio-circular-slider, .inicio-circular-slider-nav-scope, .inicio-circular-slider-side-preview, .lm-final-circular-slider, .lm-professional-circular-slider, .lm-root-slide-active'
            ).forEach(function (element) {
                element.classList.remove(
                    'inicio-circular-slider',
                    'inicio-circular-slider-nav-scope',
                    'inicio-circular-slider-side-preview',
                    'lm-final-circular-slider',
                    'lm-professional-circular-slider',
                    'lm-root-slide-active'
                );
                element.removeAttribute('data-lm-root-circular');
            });
        }

        function addCleanStyle() {
            if (document.getElementById('lm-clean-real-slider-style')) {
                return;
            }

            var style = document.createElement('style');
            style.id = 'lm-clean-real-slider-style';
            style.textContent = `
                .lm-clean-real-slider {
                    position: relative;
                    width: 100%;
                    overflow: hidden;
                }

                .lm-clean-real-slider__viewport {
                    position: relative;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                }

                .lm-clean-real-slider__slide {
                    position: absolute;
                    inset: 0;
                    opacity: 0;
                    visibility: hidden;
                    pointer-events: none;
                    transition: opacity .35s ease;
                }

                .lm-clean-real-slider__slide.is-active {
                    opacity: 1;
                    visibility: visible;
                    pointer-events: auto;
                    z-index: 2;
                }

                .lm-clean-real-slider__slide > * {
                    width: 100% !important;
                    height: 100% !important;
                    min-height: 100% !important;
                    margin: 0 !important;
                }

                .lm-clean-real-slider__circle {
                    position: absolute;
                    top: 50%;
                    right: clamp(38px, 8vw, 115px);
                    width: clamp(215px, 24vw, 315px);
                    height: clamp(215px, 24vw, 315px);
                    transform: translateY(-50%);
                    z-index: 5;
                    pointer-events: none;
                }

                .lm-clean-real-slider__thumb {
                    position: absolute;
                    display: block;
                    padding: 0;
                    border: 3px solid rgba(255, 255, 255, .96);
                    border-radius: 50%;
                    background: rgba(255, 255, 255, .18);
                    box-shadow: 0 13px 28px rgba(0, 0, 0, .28);
                    overflow: hidden;
                    pointer-events: auto;
                }

                .lm-clean-real-slider__thumb img {
                    display: block;
                    width: 100%;
                    height: 100%;
                    border-radius: 50%;
                    object-fit: cover;
                }

                .lm-clean-real-slider__thumb.is-current {
                    top: 50%;
                    left: 35%;
                    width: clamp(118px, 11vw, 152px);
                    height: clamp(118px, 11vw, 152px);
                    transform: translate(-50%, -50%);
                    z-index: 3;
                }

                .lm-clean-real-slider__thumb.is-next {
                    top: 13%;
                    right: 4%;
                    width: clamp(72px, 7vw, 98px);
                    height: clamp(72px, 7vw, 98px);
                    z-index: 2;
                }

                .lm-clean-real-slider__thumb.is-prev {
                    right: 18%;
                    bottom: 7%;
                    width: clamp(72px, 7vw, 98px);
                    height: clamp(72px, 7vw, 98px);
                    z-index: 2;
                }

                .lm-clean-real-slider__arrows {
                    position: absolute;
                    right: clamp(98px, 13vw, 186px);
                    bottom: clamp(30px, 6vw, 58px);
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    justify-content: center;
                    z-index: 6;
                }

                .lm-clean-real-slider__arrow {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 32px;
                    height: 32px;
                    border: 0;
                    border-radius: 4px;
                    background: #3f6b2f;
                    color: #fff;
                    font-size: 18px;
                    line-height: 1;
                    cursor: pointer;
                }

                @media (max-width: 767px) {
                    .lm-clean-real-slider__circle {
                        display: none;
                    }

                    .lm-clean-real-slider__arrows {
                        right: 24px;
                        bottom: 22px;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        function findOriginalSlider() {
            var slider = Array.from(document.querySelectorAll('.owl-carousel')).find(function (element) {
                return hasSliderAction(element);
            });

            if (slider) {
                return slider;
            }

            var button = Array.from(document.querySelectorAll('a, button')).find(isSliderAction);
            var current = button ? button.parentElement : null;

            while (current && current !== document.body) {
                var box = current.getBoundingClientRect();
                if (box.width > 520 && box.height > 180) {
                    return current;
                }
                current = current.parentElement;
            }

            return null;
        }

        function cleanClone(node) {
            node.querySelectorAll('.owl-nav, .owl-dots, .lm-clean-real-slider__circle, .lm-clean-real-slider__arrows').forEach(function (element) {
                element.remove();
            });

            node.querySelectorAll('*').forEach(function (element) {
                element.style.display = '';
                element.style.opacity = '';
                element.style.visibility = '';
                element.classList.remove('cloned', 'active', 'center', 'lm-root-slide-active');
            });
        }

        resetGeneratedWork();

        var original = findOriginalSlider();
        if (!original || original.dataset.lmCleanReplaced === 'true') {
            return;
        }

        var originalBox = original.getBoundingClientRect();
        var rawSlides = Array.from(original.querySelectorAll('.owl-item:not(.cloned)')).map(function (item) {
            return item.firstElementChild || item;
        }).filter(function (slide, index, list) {
            return slide && list.indexOf(slide) === index && (findImage(slide) || hasSliderAction(slide));
        });

        if (!rawSlides.length) {
            rawSlides = Array.from(original.children).filter(function (slide) {
                return findImage(slide) || hasSliderAction(slide);
            });
        }

        if (!rawSlides.length) {
            return;
        }

        addCleanStyle();

        var slider = document.createElement('div');
        var viewport = document.createElement('div');
        var circle = document.createElement('div');
        var arrows = document.createElement('div');
        var activeIndex = Math.max(0, rawSlides.findIndex(hasSliderAction));

        slider.className = 'lm-clean-real-slider';
        viewport.className = 'lm-clean-real-slider__viewport';
        circle.className = 'lm-clean-real-slider__circle';
        arrows.className = 'lm-clean-real-slider__arrows';

        slider.style.height = Math.max(260, Math.round(originalBox.height)) + 'px';
        slider.style.minHeight = Math.max(260, Math.round(originalBox.height)) + 'px';

        var slides = rawSlides.map(function (slide, index) {
            var wrapper = document.createElement('div');
            var clone = slide.cloneNode(true);

            cleanClone(clone);
            wrapper.className = 'lm-clean-real-slider__slide';
            if (index === activeIndex) {
                wrapper.classList.add('is-active');
            }
            wrapper.appendChild(clone);
            viewport.appendChild(wrapper);

            return {
                element: wrapper,
                image: findImage(slide)
            };
        });

        function slideImage(index) {
            var slide = slides[(index + slides.length) % slides.length];
            return slide && slide.image ? slide.image : slides[0].image;
        }

        function renderCircle() {
            circle.innerHTML = '';

            [
                { className: 'is-current', index: activeIndex },
                { className: 'is-next', index: activeIndex + 1 },
                { className: 'is-prev', index: activeIndex - 1 }
            ].forEach(function (item) {
                var src = slideImage(item.index);
                if (!src) {
                    return;
                }

                var button = document.createElement('button');
                var image = document.createElement('img');

                button.type = 'button';
                button.className = 'lm-clean-real-slider__thumb ' + item.className;
                image.src = src;
                image.alt = '';

                button.appendChild(image);
                button.addEventListener('click', function () {
                    setActive(item.index);
                });

                circle.appendChild(button);
            });
        }

        function setActive(index) {
            activeIndex = (index + slides.length) % slides.length;
            slides.forEach(function (slide, slideIndex) {
                slide.element.classList.toggle('is-active', slideIndex === activeIndex);
            });
            renderCircle();
        }

        ['‹', '›'].forEach(function (label, index) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'lm-clean-real-slider__arrow';
            button.textContent = label;
            button.addEventListener('click', function () {
                setActive(activeIndex + (index === 0 ? -1 : 1));
            });
            arrows.appendChild(button);
        });

        slider.appendChild(viewport);
        slider.appendChild(circle);
        slider.appendChild(arrows);
        renderCircle();

        original.dataset.lmCleanReplaced = 'true';
        original.style.display = 'none';
        original.parentNode.insertBefore(slider, original);
    }, 2200);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    return;

    function restoreOriginalSlider() {
        document.querySelectorAll(
            '#inicio-circular-slider-style, #lm-final-circular-slider-style, #lm-professional-circular-slider-style, #lm-root-circular-slider-style, #lm-clean-real-slider-style, .inicio-circular-carousel, .lm-final-circular-thumbs, .lm-professional-circle-nav, .lm-root-circular-nav, .lm-clean-real-slider'
        ).forEach(function (element) {
            element.remove();
        });

        document.querySelectorAll('[data-lm-clean-replaced="true"]').forEach(function (element) {
            element.style.display = '';
            element.removeAttribute('data-lm-clean-replaced');
        });

        document.querySelectorAll('[data-lm-root-circular="true"]').forEach(function (element) {
            element.removeAttribute('data-lm-root-circular');
        });

        document.querySelectorAll(
            '.inicio-circular-slider, .inicio-circular-slider-nav-scope, .inicio-circular-slider-side-preview, .lm-final-circular-slider, .lm-professional-circular-slider, .lm-root-slide-active, .lm-professional-main-slide'
        ).forEach(function (element) {
            element.classList.remove(
                'inicio-circular-slider',
                'inicio-circular-slider-nav-scope',
                'inicio-circular-slider-side-preview',
                'lm-final-circular-slider',
                'lm-professional-circular-slider',
                'lm-root-slide-active',
                'lm-professional-main-slide'
            );
        });

        document.querySelectorAll('.owl-stage, .owl-item').forEach(function (element) {
            element.style.display = '';
            element.style.opacity = '';
            element.style.visibility = '';
            element.style.width = '';
            element.style.transform = '';
            element.style.transition = '';
            element.style.position = '';
            element.style.inset = '';
            element.style.pointerEvents = '';
            element.style.zIndex = '';
        });
    }

    restoreOriginalSlider();
    setTimeout(restoreOriginalSlider, 300);
    setTimeout(restoreOriginalSlider, 900);
    setTimeout(restoreOriginalSlider, 1500);
    setTimeout(restoreOriginalSlider, 2600);
    var restoreTimer = setInterval(restoreOriginalSlider, 100);
    setTimeout(function () {
        clearInterval(restoreTimer);
        restoreOriginalSlider();
    }, 4200);
});
</script>

<?php include 'footer.php'; ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    return;

    function cleanCircularSliderArtifacts() {
        document.querySelectorAll(
            '#inicio-circular-slider-style, #lm-final-circular-slider-style, #lm-professional-circular-slider-style, #lm-root-circular-slider-style, #lm-clean-real-slider-style, .inicio-circular-carousel, .lm-final-circular-thumbs, .lm-professional-circle-nav, .lm-root-circular-nav, .lm-clean-real-slider'
        ).forEach(function (element) {
            element.remove();
        });

        document.querySelectorAll('[data-lm-clean-replaced="true"]').forEach(function (element) {
            element.style.display = '';
            element.removeAttribute('data-lm-clean-replaced');
        });

        document.querySelectorAll('[data-lm-root-circular="true"]').forEach(function (element) {
            element.removeAttribute('data-lm-root-circular');
        });

        document.querySelectorAll(
            '.inicio-circular-slider, .inicio-circular-slider-nav-scope, .inicio-circular-slider-side-preview, .lm-final-circular-slider, .lm-professional-circular-slider, .lm-root-slide-active, .lm-professional-main-slide'
        ).forEach(function (element) {
            element.classList.remove(
                'inicio-circular-slider',
                'inicio-circular-slider-nav-scope',
                'inicio-circular-slider-side-preview',
                'lm-final-circular-slider',
                'lm-professional-circular-slider',
                'lm-root-slide-active',
                'lm-professional-main-slide'
            );
        });

        document.querySelectorAll('.owl-stage, .owl-item').forEach(function (element) {
            element.style.display = '';
            element.style.opacity = '';
            element.style.visibility = '';
            element.style.width = '';
            element.style.transform = '';
            element.style.transition = '';
            element.style.position = '';
            element.style.inset = '';
            element.style.pointerEvents = '';
            element.style.zIndex = '';
        });
    }

    cleanCircularSliderArtifacts();
    var cleanSliderTimer = setInterval(cleanCircularSliderArtifacts, 80);
    setTimeout(function () {
        clearInterval(cleanSliderTimer);
        cleanCircularSliderArtifacts();
    }, 5000);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        function normalizeSectionText(value) {
            return (value || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function injectProductNineStyle() {
            if (document.getElementById('lm-products-nine-style')) {
                return;
            }

            var style = document.createElement('style');
            style.id = 'lm-products-nine-style';
            style.textContent = `
                .lm-products-nine-layout {
                    display: grid !important;
                    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
                    gap: 28px 24px !important;
                    align-items: stretch !important;
                    max-width: 900px !important;
                    width: 100% !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                }

                .lm-products-nine-layout > [class*="col-"],
                .lm-products-nine-layout > .lm-product-grid-item {
                    width: auto !important;
                    max-width: none !important;
                    min-width: 0 !important;
                    flex: none !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                    margin: 0 !important;
                }

                .lm-products-nine-layout .single-arrivals-products,
                .lm-products-nine-layout .arrivals-products-image,
                .lm-products-nine-layout .arrivals-products-content,
                .lm-products-nine-layout .rating {
                    width: 100% !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                }

                .lm-products-nine-layout .single-arrivals-products {
                    height: 100% !important;
                    display: flex !important;
                    flex-direction: column !important;
                }

                .lm-products-nine-layout .arrivals-products-content {
                    margin-top: 16px !important;
                    display: flex !important;
                    flex-direction: column !important;
                }

                .lm-products-nine-layout .arrivals-products-content h3 {
                    font-size: 17px !important;
                    line-height: 1.2 !important;
                    margin-bottom: 8px !important;
                }

                .lm-products-nine-layout .arrivals-products-content .rating {
                    margin-bottom: 8px !important;
                }

                .lm-products-nine-layout .arrivals-products-content span {
                    font-size: 18px !important;
                    line-height: 1.2 !important;
                }

                .lm-products-nine-layout .arrivals-products-image img {
                    width: 100% !important;
                    max-width: 100% !important;
                }

                .lm-products-nine-layout > .lm-product-side-panel {
                    display: none !important;
                }

                .lm-products-nine-layout > .lm-product-side-panel::after {
                    content: "";
                    display: block;
                    width: 68%;
                    height: 46%;
                    margin-bottom: 24px;
                    border-radius: 16px;
                    background: rgba(255, 255, 255, .08);
                }

                .lm-products-nine-layout .lm-products-extra-hidden {
                    display: none !important;
                }

                .lm-products-nine-layout .single-product,
                .lm-products-nine-layout .product-item,
                .lm-products-nine-layout .product-card {
                    height: 100%;
                }

                @media (max-width: 991px) {
                    .lm-products-nine-layout {
                        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                    }

                    .lm-products-nine-layout > .lm-product-side-panel {
                        grid-row: auto;
                        min-height: 150px;
                    }
                }

                @media (max-width: 575px) {
                    .lm-products-nine-layout {
                        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                        max-width: calc(100% - 24px) !important;
                        gap: 16px 12px !important;
                    }

                    .lm-products-nine-layout .arrivals-products-content {
                        margin-top: 12px !important;
                    }

                    .lm-products-nine-layout .arrivals-products-content h3 {
                        font-size: 15px !important;
                    }

                    .lm-products-nine-layout .arrivals-products-content .rating li i {
                        font-size: 15px !important;
                    }

                    .lm-products-nine-layout .arrivals-products-content span {
                        font-size: 17px !important;
                    }

                    .lm-products-nine-layout .arrivals-products-image .tag {
                        font-size: 12px !important;
                        padding: 5px 12px !important;
                    }
                }

                @media (max-width: 359px) {
                    .lm-products-nine-layout {
                        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                        max-width: calc(100% - 24px) !important;
                        gap: 16px 12px !important;
                    }

                    .lm-products-nine-layout .arrivals-products-content h3 {
                        font-size: 16px !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        function isProductItem(element) {
            if (!element || element.classList.contains('lm-product-side-panel')) {
                return false;
            }

            var text = normalizeSectionText(element.textContent);
            var hasPrice = /q\\s*\\d|q\\.\\d|q\\d/.test(text);
            var hasProductImage = !!element.querySelector('img');
            var hasBadge = /promocion|promoción|nuevo|popular/.test(text);

            return hasProductImage && (hasPrice || hasBadge);
        }

        function directProductItems(container) {
            return Array.from(container.children).filter(isProductItem);
        }

        function findProductContainerAfterHeading(heading) {
            var headingTop = heading.getBoundingClientRect().bottom + window.scrollY;
            var containers = Array.from(document.querySelectorAll('.row, [class*="product"], section, div')).filter(function (element) {
                var box = element.getBoundingClientRect();
                var top = box.top + window.scrollY;
                var items = directProductItems(element);
                return top > headingTop && top < headingTop + 760 && items.length >= 3;
            });

            containers.sort(function (a, b) {
                return (a.getBoundingClientRect().top + window.scrollY) - (b.getBoundingClientRect().top + window.scrollY);
            });

            return containers[0] || null;
        }

        function productKey(element) {
            var text = normalizeSectionText(element.textContent).replace(/\s+/g, ' ');
            return text.slice(0, 80);
        }

        function sourceForSection(sectionName) {
            if (sectionName.indexOf('ofertas') !== -1) {
                return 'ofertas.php';
            }

            return 'tienda.php';
        }

        function fillFromPage(sectionName, container) {
            var current = directProductItems(container);
            if (current.length >= 9) {
                return Promise.resolve();
            }

            return fetch(sourceForSection(sectionName), { credentials: 'same-origin' })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('No se pudo cargar la fuente de productos.');
                    }
                    return response.text();
                })
                .then(function (html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var existingKeys = directProductItems(container).map(productKey);
                    var candidates = Array.from(doc.querySelectorAll('[class*="col-"], .single-product, .product-item, .product-card')).filter(isProductItem);

                    candidates.some(function (candidate) {
                        if (directProductItems(container).length >= 9) {
                            return true;
                        }

                        var key = productKey(candidate);
                        if (existingKeys.indexOf(key) !== -1) {
                            return false;
                        }

                        var clone = candidate.cloneNode(true);
                        clone.classList.add('lm-product-grid-item');
                        container.appendChild(clone);
                        existingKeys.push(key);
                        return false;
                    });
                })
                .catch(function () {
                    return null;
                });
        }

        function applyNineProductLayout(sectionName, container) {
            injectProductNineStyle();
            container.classList.add('lm-products-nine-layout');

            container.querySelectorAll(':scope > .lm-product-side-panel').forEach(function (panel) {
                panel.remove();
            });

            directProductItems(container).forEach(function (item, index) {
                item.classList.toggle('lm-products-extra-hidden', index >= 9);
            });
        }

        var sections = [
            'ofertas exclusivas solo por hoy',
            'nuevos productos',
            'los mas vendidos'
        ];

        sections.forEach(function (sectionName) {
            var heading = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6, strong, span, div')).find(function (element) {
                return normalizeSectionText(element.textContent).indexOf(sectionName) !== -1;
            });

            if (!heading) {
                return;
            }

            var container = findProductContainerAfterHeading(heading);
            if (!container) {
                return;
            }

            fillFromPage(sectionName, container).then(function () {
                applyNineProductLayout(sectionName, container);
            });
        });

        setTimeout(function () {
            var nuevosHeading = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6, strong, span, div')).find(function (element) {
                return normalizeSectionText(element.textContent).indexOf('nuevos productos') !== -1;
            });

            if (!nuevosHeading) {
                return;
            }

            var nuevosContainer = findProductContainerAfterHeading(nuevosHeading);
            if (!nuevosContainer) {
                return;
            }

                fillFromPage('nuevos productos', nuevosContainer).then(function () {
                applyNineProductLayout('nuevos productos', nuevosContainer);
                nuevosContainer.style.display = 'grid';
                nuevosContainer.style.maxWidth = '900px';
                nuevosContainer.style.width = '100%';
                nuevosContainer.style.marginLeft = 'auto';
                nuevosContainer.style.marginRight = 'auto';
                nuevosContainer.style.gap = '28px 24px';

                directProductItems(nuevosContainer).forEach(function (item, index) {
                    item.style.width = 'auto';
                    item.style.maxWidth = 'none';
                    item.style.flex = 'none';
                    item.style.paddingLeft = '0';
                    item.style.paddingRight = '0';
                    item.classList.toggle('lm-products-extra-hidden', index >= 9);
                });
            });
        }, 1800);

        setTimeout(function () {
            var nuevosHeading = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6, strong, span, div')).find(function (element) {
                return normalizeSectionText(element.textContent).indexOf('nuevos productos') !== -1;
            });
            var nuevosContainer = nuevosHeading ? findProductContainerAfterHeading(nuevosHeading) : null;

            if (nuevosContainer) {
                applyNineProductLayout('nuevos productos', nuevosContainer);
            }
        }, 3200);
    }, 900);
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        function featureText(value) {
            return (value || '').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        function ensureFeatureStyle() {
            if (document.getElementById('lm-feature-banners-style')) {
                return;
            }

            var style = document.createElement('style');
            style.id = 'lm-feature-banners-style';
            style.textContent = `
                .lm-feature-banners-row {
                    display: grid !important;
                    grid-template-columns: repeat(3, minmax(230px, 1fr)) !important;
                    column-gap: 64px !important;
                    row-gap: 28px !important;
                    align-items: center !important;
                    justify-content: center !important;
                    max-width: 1120px !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                }

                .lm-feature-banner-col {
                    width: auto !important;
                    max-width: none !important;
                    flex: none !important;
                    padding-left: 0 !important;
                    padding-right: 0 !important;
                }

                .lm-feature-banner {
                    position: relative;
                    display: flex;
                    align-items: center;
                    min-height: 132px;
                    width: 100%;
                    text-decoration: none !important;
                    color: inherit;
                }

                .lm-feature-banner__image {
                    position: absolute;
                    left: 0;
                    top: 50%;
                    width: 124px;
                    height: 124px;
                    transform: translateY(-50%);
                    object-fit: contain;
                    border: 4px solid #111;
                    border-radius: 50%;
                    background: #fff;
                    padding: 10px;
                    box-shadow: 0 12px 24px rgba(0, 0, 0, .18);
                    z-index: 2;
                    pointer-events: none;
                }

                .lm-feature-banner__content {
                    width: 100%;
                    min-height: 92px;
                    margin-left: 62px;
                    padding: 19px 24px 19px 82px;
                    border: 1px solid rgba(202, 51, 61, .75);
                    border-radius: 0 50px 50px 0;
                    background: linear-gradient(90deg, #171717 0%, #111 100%);
                    box-shadow: 0 12px 26px rgba(0, 0, 0, .14);
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                    align-items: flex-start;
                }

                .lm-feature-banner__title {
                    margin: 0;
                    color: #fff;
                    font-size: 18px;
                    font-weight: 700;
                    line-height: 1.15;
                }

                .lm-feature-banner__subtitle {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    margin-top: 8px;
                    padding: 3px 13px;
                    border: 1px solid rgba(255, 255, 255, .86);
                    border-radius: 18px;
                    color: #fff;
                    font-size: 12px;
                    font-weight: 600;
                    line-height: 1.2;
                }

                @media (max-width: 991px) {
                    .lm-feature-banners-row {
                        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                        column-gap: 30px !important;
                        row-gap: 18px !important;
                        max-width: 680px !important;
                    }

                    .lm-feature-banner {
                        min-height: 116px !important;
                    }

                    .lm-feature-banner__content {
                        width: calc(100% - 62px) !important;
                        min-height: 78px !important;
                        padding: 15px 18px 15px 76px !important;
                    }

                    .lm-feature-banner__title {
                        font-size: 17px !important;
                    }

                    .lm-feature-banner__subtitle {
                        margin-top: 6px !important;
                        padding: 3px 12px !important;
                        font-size: 11px !important;
                    }
                }

                @media (max-width: 575px) {
                    .lm-feature-banners-row {
                        grid-template-columns: 1fr !important;
                        width: calc(100% - 32px) !important;
                        max-width: 326px !important;
                        row-gap: 16px !important;
                    }

                    .lm-feature-banner {
                        min-height: 108px !important;
                    }

                    .lm-feature-banner__image {
                        width: 108px !important;
                        height: 108px !important;
                        padding: 8px !important;
                    }

                    .lm-feature-banner__content {
                        width: calc(100% - 54px) !important;
                        min-height: 72px !important;
                        margin-left: 54px !important;
                        padding: 12px 16px 12px 66px !important;
                        border-radius: 0 40px 40px 0 !important;
                    }

                    .lm-feature-banner__title {
                        font-size: 18px !important;
                    }

                    .lm-feature-banner__subtitle {
                        margin-top: 5px !important;
                        padding: 3px 10px !important;
                        font-size: 10.5px !important;
                    }
                }

                @media (max-width: 359px) {
                    .lm-feature-banners-row {
                        width: calc(100% - 30px) !important;
                        max-width: 286px !important;
                        row-gap: 14px !important;
                    }

                    .lm-feature-banner {
                        min-height: 92px !important;
                    }

                    .lm-feature-banner__image {
                        width: 86px !important;
                        height: 86px !important;
                        border-width: 3px !important;
                    }

                    .lm-feature-banner__content {
                        width: calc(100% - 43px) !important;
                        min-height: 62px !important;
                        margin-left: 43px !important;
                        padding: 10px 12px 10px 52px !important;
                        border-radius: 0 32px 32px 0 !important;
                    }

                    .lm-feature-banner__title {
                        font-size: 14.5px !important;
                    }

                    .lm-feature-banner__subtitle {
                        margin-top: 4px !important;
                        padding: 2px 8px !important;
                        font-size: 9px !important;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        function setImageSource(image, fileName, alternatives) {
            var projectPath = window.location.pathname.replace(/[^\/]*$/, '');
            var fileNames = [fileName].concat(alternatives || []);
            var folders = [
                projectPath + 'assets/img/',
                '/libreriamarquense.com/assets/img/',
                'assets/img/',
                'assets/img/banner/',
                'assets/img/banners/',
                'assets/images/',
                'assets/images/banner/',
                'assets/images/banners/',
                'img/',
                ''
            ];
            var candidates = [];
            var index = 0;

            fileNames.forEach(function (name) {
                folders.forEach(function (folder) {
                    candidates.push(folder + name);
                });
            });

            image.onerror = function () {
                index += 1;
                if (index < candidates.length) {
                    image.src = candidates[index];
                }
            };

            image.src = candidates[index];
        }

        var items = [
            {
                matches: ['celulares y accesorios', 'utiles escolares', 'útiles escolares'],
                title: 'Útiles Escolares',
                subtitle: 'Disponibles',
                image: 'UtilesEscolares.png'
            },
            {
                matches: ['envios', 'envíos'],
                title: 'Envíos',
                subtitle: 'a Domicilio',
                image: 'EnviosaDomicilio.png',
                alternatives: ['EnviosDomicilio.png']
            },
            {
                matches: ['asesoria tecnica', 'asesoría técnica'],
                title: 'Asesoría Técnica',
                subtitle: 'Personalizada',
                image: 'AsesoriaTecnica.png'
            }
        ];

        var transformed = [];

        items.forEach(function (config) {
            var titleNode = Array.from(document.querySelectorAll('h1, h2, h3, h4, h5, h6, p, span, strong')).find(function (element) {
                return config.matches.indexOf(featureText(element.textContent)) !== -1;
            });

            if (!titleNode) {
                return;
            }

            var card = titleNode.closest('[class*="col-"]') || titleNode.closest('.single-feature, .feature-box, .single-service, .service-box') || titleNode.parentElement;
            if (!card || transformed.indexOf(card) !== -1) {
                return;
            }

            var link = card.querySelector('a[href]');
            var href = link ? link.getAttribute('href') : '';
            var wrapper = href ? document.createElement('a') : document.createElement('div');
            var image = document.createElement('img');
            var content = document.createElement('div');
            var title = document.createElement('h3');
            var subtitle = document.createElement('span');

            wrapper.className = 'lm-feature-banner';
            if (href) {
                wrapper.href = href;
            }

            image.className = 'lm-feature-banner__image';
            image.alt = config.title;
            setImageSource(image, config.image, config.alternatives);

            content.className = 'lm-feature-banner__content';
            title.className = 'lm-feature-banner__title';
            subtitle.className = 'lm-feature-banner__subtitle';
            title.textContent = config.title;
            subtitle.textContent = config.subtitle;

            content.appendChild(title);
            content.appendChild(subtitle);
            wrapper.appendChild(image);
            wrapper.appendChild(content);

            card.classList.add('lm-feature-banner-col');
            card.innerHTML = '';
            card.appendChild(wrapper);
            transformed.push(card);
        });

        if (transformed.length) {
            ensureFeatureStyle();
            var row = transformed[0].parentElement;
            if (row) {
                row.classList.add('lm-feature-banners-row');
                var slider = Array.from(document.querySelectorAll('.owl-carousel, [class*="slider"]')).find(function (element) {
                    var box = element.getBoundingClientRect();
                    return box.width > 520 && box.height > 180;
                });

                if (slider) {
                    row.style.maxWidth = Math.round(slider.getBoundingClientRect().width) + 'px';
                    row.style.width = '100%';
                }
            }
        }
    }, 500);
});
</script>
<script type="text/javascript" src="assets/js/sweatlert.js"></script>
<script type="text/javascript" src="assets/js/index.js"></script>
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
