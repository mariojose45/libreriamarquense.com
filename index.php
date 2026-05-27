<?php
// SEO para la pagina de inicio
$seo_title = "Librería Marquense | Libros, Papelería, Artículos Escolares y de Oficina";
$seo_description = "Librería Marquense ofrece libros, papelería, artículos escolares, material didáctico y productos de oficina para estudiantes, familias y empresas en Guatemala.";
$seo_keywords = "Librería Marquense, libros Guatemala, papelería Guatemala, artículos escolares, útiles escolares, productos de oficina";

include 'head.php';
$current_page = basename($_SERVER['PHP_SELF']);

$apiUrl = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_tienda_inicio.php";
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

                    $rutaImagenes = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/files/articulos/";
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

<!-- Start Support Area -->
<style>
   
    .support-area.support-cards .container {
        max-width: 1040px !important;
    }

    .lm-feature-banners-row {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(230px, 1fr)) !important;
        column-gap: 42px !important;
        row-gap: 28px !important;
        align-items: center !important;
        justify-content: center !important;
        max-width: 1040px !important;
        width: 100% !important;
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
        border: 1px solid rgba(183, 54, 57, .75);
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
        .support-area.support-cards {
            padding: 34px 0 0 !important;
        }

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
        .support-area.support-cards {
            padding: 24px 0 0 !important;
        }

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
</style>

<section class="support-area support-cards">
    <div class="container">
        <div class="lm-feature-banners-row">

            <div class="lm-feature-banner-col">
                <a class="lm-feature-banner" href="tienda.php">
                    <img class="lm-feature-banner__image" src="assets/img/UtilesEscolares.png" alt="Útiles Escolares">
                    <div class="lm-feature-banner__content">
                        <h3 class="lm-feature-banner__title">Útiles Escolares</h3>
                        <span class="lm-feature-banner__subtitle">Disponibles</span>
                    </div>
                </a>
            </div>

            <div class="lm-feature-banner-col">
                <a class="lm-feature-banner" href="tienda.php">
                    <img class="lm-feature-banner__image" src="assets/img/EnviosaDomicilio.png" alt="Envíos">
                    <div class="lm-feature-banner__content">
                        <h3 class="lm-feature-banner__title">Envíos</h3>
                        <span class="lm-feature-banner__subtitle">a Domicilio</span>
                    </div>
                </a>
            </div>

            <div class="lm-feature-banner-col">
                <a class="lm-feature-banner" href="contact.php">
                    <img class="lm-feature-banner__image" src="assets/img/AsesoriaEscolar.png" alt="Asesoría escolar">
                    <div class="lm-feature-banner__content">
                        <h3 class="lm-feature-banner__title">Asesoría Escolar</h3>
                        <span class="lm-feature-banner__subtitle">Personalizada</span>
                    </div>
                </a>
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
        background: linear-gradient(90deg, #1A2697 0%, #B73639 58%, #166B38 100%);
        padding: 25px 0;
        margin-top: 46px;
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
        background: #166B38;
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
        background: linear-gradient(112deg, #101A5C 0%, #1A2697 49.35%, #166B38 49.55%, #355329 100%) !important;
        background-color: #1A2697 !important;
        box-shadow: 0 18px 42px rgba(16, 26, 92, 0.16) !important;
    }

    .section-header-soft .wave {
        background: linear-gradient(112deg, #101A5C 0%, #1A2697 49.35%, #166B38 49.55%, #355329 100%) !important;
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
        gap: 42px 80px !important;
        align-items: stretch;
        justify-content: center;
        width: 100%;
        max-width: 1120px;
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
        background: linear-gradient(90deg, #1A2697 0%, #B73639 58%, #166B38 100%);
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
        background: #166B38;
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
    "HIGIENE CUIDADO PERSONAL" => "bx bx-health",
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
                        $nombreCategoriaInicio = strtr($nombreCategoriaInicio, [
                            'Á' => 'A',
                            'É' => 'E',
                            'Í' => 'I',
                            'Ó' => 'O',
                            'Ú' => 'U',
                            'Ü' => 'U',
                            'Ñ' => 'N'
                        ]);
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

<?php include 'footer.php'; ?>

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
                    gap: 42px 80px !important;
                    align-items: stretch !important;
                    max-width: 1040px !important;
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
                nuevosContainer.style.maxWidth = '1120px';
                nuevosContainer.style.width = '100%';
                nuevosContainer.style.marginLeft = 'auto';
                nuevosContainer.style.marginRight = 'auto';
                nuevosContainer.style.gap = '42px 80px';

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
