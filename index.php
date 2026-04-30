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

        .main-slider-area {
            height: 460px;
        }

        .main-slider-area .main-slider-item-box {

            width: 95px;
            height: 150px;

            bottom: 70px;
            top: auto;

            transform: none;

        }

        .main-slider-area .main-slider-item-box:nth-child(3) {

            right: 110px;
            left: auto;

        }

        .main-slider-area .main-slider-item-box:nth-child(4) {

            right: 10px;
            left: auto;

        }

        .main-slider-area .main-slider-item-box:nth-child(5) {

            display: none;

        }

        .main-slider-area .main-slider-content {

            bottom: 100px;
            top: auto;

            left: 18px;
            right: 18px;

        }

        .main-slider-area .main-slider-content h1 {

            font-size: 26px;

        }

        .main-slider-area .main-slider-content p {

            max-width: 85%;

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

<div class="main-slider-area">
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
                                        Solicitar Servicio
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
        <button type="button" class="slider-stack-prev">
            <i class="flaticon-left-arrow"></i>
        </button>
        <button type="button" class="slider-stack-next">
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
    }

    .exclusive-offers-banner::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 60%;
        height: 100%;
        background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.3), transparent);
        transform: skewX(-25deg);
        animation: shimmer 2.5s infinite;
    }

    @keyframes shimmer {
        100% {
            left: 200%;
        }
    }

    .banner-content {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 30px;
        position: relative;
        z-index: 2;
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

    @media (max-width: 767px) {

        #contenedor-promociones-productos>div,
        #contenedor-nuevos-productos>div,
        #productos-mas-vendidos>div {
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

        .section-header-soft .wave span {
            width: 300vw;
            height: 300vw;
            top: -290vw;
        }
    }
</style>

<div class="section-header-soft">
    <div class="wave">
        <span></span>
        <span></span>
        <span></span>
    </div>
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

<?php include 'footer.php'; ?>
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
