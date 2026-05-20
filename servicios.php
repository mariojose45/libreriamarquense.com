<?php include 'head.php';

$current_page = basename($_SERVER['PHP_SELF']);

$paginas_servicios = [
    'servicios.php'
];

// Obtener datos de la API de servicios
$apiUrl = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_tienda_servicios.php";
$servicios = [];
$testimonios = [];

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
            foreach ($data['data'] as $item) {
                if (isset($item['tipo']) && is_string($item['tipo'])) {
                    // Normalizar el tipo (trim y comparación case-insensitive)
                    $tipo = trim($item['tipo']);
                    if (strcasecmp($tipo, 'Servicio') === 0) {
                        $servicios[] = $item;
                    } elseif (strcasecmp($tipo, 'Testimonio') === 0) {
                        $testimonios[] = $item;
                    }
                }
            }
        }
    }
}

$rutaImagenes = "https://ssl.sol.sistemasolgt.com/libremarquenseDos/files/articulos/";
?>

<!-- Start Page Banner -->
<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>Servicios</h2>

            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>Servicios</li>
            </ul>
        </div>
    </div>
</div>
<!-- End Page Banner -->

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<style>
    /* Tamaños de Imagenes para servicios y testimonios */
    .team-area .team-image {
        width: 100%;
        max-width: 550px;
        height: 550px;
        overflow: hidden;
    }

    .team-area .team-image img {
        width: 550px;
        height: 550px;
        object-fit: cover;
        max-width: 100%;
    }

    .blog-area .blog-image {
        width: 100%;
        max-width: 750px;
        height: 500px;
        overflow: hidden;
    }

    .blog-area .blog-image img {
        width: 750px;
        height: 500px;
        object-fit: cover;
        max-width: 100%;
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
        margin-top: 50px;
        margin-bottom: -50px;
        /* Adjust for next section padding */
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
        .banner-content {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }

        .banner-text {
            font-size: 18px;
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

    @media (max-width: 767px) {
        .section-title {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 35px;
            text-align: center;
            overflow: visible;
        }

        .section-title::before,
        .section-title::after {
            content: "";
            flex: 1;
            height: 2px;
            background-color: #ddd;
            min-width: 35px;
            max-width: 90px;
        }

        .section-title h2 {
            font-size: 24px;
            line-height: 1.2;
            padding: 0;
            margin: 0 !important;
            display: block;
            max-width: calc(100% - 110px);
            word-break: break-word;
        }

        .section-title h2::before,
        .section-title h2::after {
            display: none;
        }
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


    /* ===== SWIPER 3D FLIP CARD ===== */
    .team-area {
        overflow-x: hidden;
    }

    .servicios-swiper {
        width: 100%;
        max-width: 1120px;
        margin: 0 auto;
        padding-top: 20px;
        padding-bottom: 60px;
        overflow: visible;
    }

    .servicios-swiper .swiper-wrapper {
        align-items: center;
    }

    .servicios-swiper .swiper-slide {
        width: 320px;
        height: 500px;
        display: flex;
        justify-content: center;
        align-items: center;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .28s ease, visibility .28s ease;
    }

    .servicios-swiper .swiper-slide-active,
    .servicios-swiper .swiper-slide-prev,
    .servicios-swiper .swiper-slide-next {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .flip-card-custom {
        --service-card-image: none;
        width: 100%;
        max-width: 320px;
        height: 500px;
        perspective: 1500px;
    }

    .flip-card-inner-custom {
        position: relative;
        width: 100%;
        height: 100%;
        transition: transform 0.8s ease;
        transform-style: preserve-3d;
    }

    .flip-card-custom:hover .flip-card-inner-custom {
        transform: rotateY(180deg);
    }

    .flip-card-custom:active .flip-card-inner-custom {
        transform: rotateY(180deg);
    }

    .flip-card-front-custom,
    .flip-card-back-custom {
        position: absolute;
        inset: 0;
        border-radius: 28px;
        overflow: hidden;
        backface-visibility: hidden;
        -webkit-backface-visibility: hidden;
        box-shadow: 0 20px 50px rgba(0, 0, 0, .18);
        background: #17214F;
    }

    .flip-card-front-custom img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .flip-card-overlay-custom {
        position: absolute;
        inset: 0;
        background: linear-gradient(to top, rgba(0, 0, 0, .75), rgba(0, 0, 0, .15));
        display: flex;
        flex-direction: column;
        justify-content: flex-end;
        padding: 24px 22px;
        color: #fff;
    }

    .flip-card-overlay-custom h3 {
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 10px;
        color: #fff;
    }

    .flip-card-overlay-custom p {
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 0;
        color: rgba(255, 255, 255, .92);
    }

    .flip-card-back-custom {
        background: #17214F;
        color: #fff;
        transform: rotateY(180deg);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px 24px;
        text-align: left;
        isolation: isolate;
    }

    .flip-card-back-custom::before {
        content: "";
        position: absolute;
        inset: -18px;
        background-image: var(--service-card-image);
        background-size: cover;
        background-position: center;
        filter: blur(13px);
        opacity: .58;
        transform: scale(1.08);
        z-index: -2;
    }

    .flip-card-back-custom::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(180deg, rgba(26, 38, 151, .78) 0%, rgba(15, 23, 64, .9) 100%);
        z-index: -1;
    }

    .flip-card-back-content-custom {
        position: relative;
        z-index: 1;
        width: 100%;
    }

    .flip-card-back-content-custom h3 {
        color: #fff;
        font-size: 24px;
        font-weight: 800;
        margin-bottom: 14px;
    }

    .flip-card-back-content-custom span {
        display: inline-block;
        margin-bottom: 12px;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        color: #aeb8ff;
    }

    .flip-card-back-content-custom p {
        color: rgba(255, 255, 255, .9);
        font-size: 15px;
        line-height: 1.7;
        margin-bottom: 12px;
    }

    .flip-card-back-content-custom .social {
        margin-top: 18px;
        padding-left: 0;
        list-style: none;
        display: flex;
        gap: 12px;
    }

    .flip-card-back-content-custom .social li a {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, .12);
        color: #fff;
        font-size: 18px;
    }

    .servicios-pagination .swiper-pagination-bullet {
        width: 12px;
        height: 12px;
        opacity: .45;
    }

    .servicios-pagination .swiper-pagination-bullet-active {
        opacity: 1;
    }

    .testimonios-carousel-shell {
        --testimonios-gap: 32px;
        margin: 0 auto;
        max-width: 1440px;
        overflow: hidden;
        padding: 28px 0 26px;
    }

    .testimonios-marquee {
        display: flex;
        align-items: stretch;
        gap: var(--testimonios-gap);
        width: max-content;
        min-width: 100%;
    }

    .testimonios-track {
        flex: 0 0 auto;
        display: flex;
        align-items: stretch;
        gap: var(--testimonios-gap);
        width: max-content;
        animation: testimoniosMarquee 34s linear infinite;
        will-change: transform;
    }

    @keyframes testimoniosMarquee {
        from {
            transform: translate3d(0, 0, 0);
        }

        to {
            transform: translate3d(calc(-100% - var(--testimonios-gap)), 0, 0);
        }
    }

    .testimonial-carousel-card {
        width: 360px;
        min-width: 360px;
        min-height: 440px;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, #F7F8FC 0%, #ffffff 100%);
        border: 2px solid #1A2697;
        border-radius: 34px;
        box-shadow: 0 18px 46px rgba(11, 34, 86, 0.15);
        overflow: hidden;
    }

    .testimonial-carousel-card__media {
        position: relative;
        aspect-ratio: 1 / 0.82;
        background: #EEF2FF;
        overflow: hidden;
    }

    .testimonial-carousel-card__media img {
        width: 100%;
        height: 100%;
        display: block;
        object-fit: cover;
        object-position: center;
    }

    .testimonial-carousel-card__media::after {
        content: "";
        position: absolute;
        inset: auto 0 0;
        height: 42%;
        background: linear-gradient(180deg, rgba(12, 29, 78, 0) 0%, rgba(12, 29, 78, 0.24) 100%);
        pointer-events: none;
    }

    .testimonial-carousel-card__content {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding: 22px 22px 16px;
    }

    .testimonial-carousel-card__tag {
        color: #2C3FAE;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .14em;
        text-transform: uppercase;
    }

    .testimonial-carousel-card__content h3 {
        color: #1A2697;
        font-size: 20px;
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 0;
    }

    .testimonial-carousel-card__content p {
        color: #5F6675;
        font-size: 16px;
        line-height: 1.65;
        margin-bottom: 0;
    }

    .testimonial-carousel-card__date {
        margin-top: auto;
        color: #166B38s;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.4;
    }

    @media (max-width: 767px) {
        .servicios-swiper {
            max-width: 100%;
            padding-top: 10px;
            padding-bottom: 45px;
            overflow: hidden;
        }

        .servicios-swiper .swiper-slide {
            width: 220px;
            height: 360px;
        }

        .flip-card-custom {
            max-width: 220px;
            height: 360px;
        }

        .flip-card-overlay-custom,
        .flip-card-back-custom {
            padding: 16px;
        }

        .flip-card-overlay-custom h3,
        .flip-card-back-content-custom h3 {
            font-size: 17px;
            margin-bottom: 8px;
        }

        .flip-card-overlay-custom p,
        .flip-card-back-content-custom p {
            font-size: 12px;
            line-height: 1.4;
        }

        .flip-card-back-content-custom span {
            font-size: 11px;
            margin-bottom: 8px;
        }

        .flip-card-back-content-custom .social {
            gap: 8px;
            margin-top: 12px;
        }

        .flip-card-back-content-custom .social li a {
            width: 36px;
            height: 36px;
            font-size: 16px;
        }

        .testimonios-carousel-shell {
            --testimonios-gap: 18px;
            padding-top: 18px;
            padding-bottom: 16px;
        }

        .testimonios-track {
            animation-duration: 24s;
        }

        .testimonial-carousel-card {
            width: 250px;
            min-width: 250px;
            min-height: 340px;
            border-radius: 24px;
        }

        .testimonial-carousel-card__content {
            gap: 10px;
            padding: 18px 18px 14px;
        }

        .testimonial-carousel-card__content h3 {
            font-size: 18px;
        }

        .testimonial-carousel-card__content p {
            font-size: 14px;
            line-height: 1.5;
        }

        .testimonial-carousel-card__date {
            font-size: 13px;
        }

        .team-area .container,
        .blog-area .container {
            padding-left: 12px;
            padding-right: 12px;
        }
    }

    @media (min-width: 768px) and (max-width: 1199px) {
        .testimonios-carousel-shell {
            --testimonios-gap: 24px;
        }

        .testimonios-track {
            animation-duration: 28s;
        }

        .testimonial-carousel-card {
            width: 310px;
            min-width: 310px;
            min-height: 410px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .testimonios-track {
            animation: none;
        }
    }
</style>

<!-- Start Story Area -->
<section class="story-area ptb-50">
    <div class="exclusive-offers-banner banner-main">
        <div class="container">
            <div class="banner-content">
                <span class="banner-text">COLECCI&Oacute;N ESCOLAR</span>
                <a href="tienda.php" class="banner-btn">VER PRODUCTOS</a>
            </div>
        </div>
    </div>
</section>


<?php
$serviciosCarrusel = $servicios;

if (!empty($serviciosCarrusel)) {
    $serviciosBase = array_values($serviciosCarrusel);

    while (count($serviciosCarrusel) < 3) {
        foreach ($serviciosBase as $servicioBase) {
            $serviciosCarrusel[] = $servicioBase;

            if (count($serviciosCarrusel) >= 3) {
                break;
            }
        }
    }
}

$serviciosCarruselTotal = count($serviciosCarrusel);
$serviciosInitialSlide = $serviciosCarruselTotal > 0 ? (int) floor($serviciosCarruselTotal / 2) : 0;
?>

<section class="team-area pt-50 pb-20">
    <div class="container">
        <div class="section-title">
            <h2>Nuestros Servicios</h2>
        </div>

        <div class="swiper servicios-swiper">
            <div class="swiper-wrapper">
                <?php if (!empty($serviciosCarrusel)): ?>
                        <?php foreach ($serviciosCarrusel as $servicio): ?>
                                <?php
                                $imagenUrl = !empty($servicio['imagen_servicio']) ? $rutaImagenes . $servicio['imagen_servicio'] : 'assets/img/team/team-1.jpg';
                                $nombre = isset($servicio['nombre']) ? $servicio['nombre'] : 'Sin nombre';
                                $descripcion = isset($servicio['descripcion_servicio']) ? $servicio['descripcion_servicio'] : '';
                                ?>
                                <div class="swiper-slide">
                                    <div class="flip-card-custom" style="--service-card-image: url(&quot;<?= htmlspecialchars($imagenUrl, ENT_QUOTES, 'UTF-8') ?>&quot;);">
                                        <div class="flip-card-inner-custom">
                                            <div class="flip-card-front-custom">
                                                <img src="<?= htmlspecialchars($imagenUrl) ?>" alt="<?= htmlspecialchars($nombre) ?>">
                                                <div class="flip-card-overlay-custom">
                                                    <h3><?= htmlspecialchars($nombre) ?></h3>
                                                </div>
                                            </div>
                                            <div class="flip-card-back-custom">
                                                <div class="flip-card-back-content-custom">
                                                    <h3><?= htmlspecialchars($nombre) ?></h3>
                                                    <p><?= htmlspecialchars($descripcion) ?></p>
                                                                                                        <?php if (!empty(array_filter($site_social_links ?? []))): ?>
                                                        <ul class="social">
                                                            <?php if (!empty($site_social_links['facebook'])): ?>
                                                                <li>
                                                                    <a href="<?php echo htmlspecialchars($site_social_links['facebook']); ?>" target="_blank" rel="noopener">
                                                                        <i class='bx bxl-facebook'></i>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if (!empty($site_social_links['instagram'])): ?>
                                                                <li>
                                                                    <a href="<?php echo htmlspecialchars($site_social_links['instagram']); ?>" target="_blank" rel="noopener">
                                                                        <i class='bx bxl-instagram'></i>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                            <?php if (!empty($site_social_links['tiktok'])): ?>
                                                                <li>
                                                                    <a href="<?php echo htmlspecialchars($site_social_links['tiktok']); ?>" target="_blank" rel="noopener">
                                                                        <i class='bx bxl-tiktok'></i>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        <?php endforeach; ?>
                <?php else: ?>
                        <div class="swiper-slide">
                            <p style="text-align:center; padding:20px;">No hay servicios disponibles en este momento.</p>
                        </div>
                <?php endif; ?>
            </div>
            <div class="swiper-pagination servicios-pagination"></div>
        </div>
    </div>
</section>


<?php
$testimoniosCarrusel = array_values($testimonios);

if (!empty($testimoniosCarrusel)) {
    $testimoniosBase = $testimoniosCarrusel;

    while (count($testimoniosCarrusel) < 8) {
        foreach ($testimoniosBase as $testimonioBase) {
            $testimoniosCarrusel[] = $testimonioBase;

            if (count($testimoniosCarrusel) >= 8) {
                break;
            }
        }
    }
}
?>

<section class="blog-area bg-color pt-50 pb-50">
    <div class="container">
        <div class="section-title">
            <h2>Testimonios de nuestros Clientes</h2>
        </div>

        <div class="testimonios-carousel-shell">
            <?php if (!empty($testimoniosCarrusel)): ?>
                    <div class="testimonios-marquee">
                        <?php for ($trackIndex = 0; $trackIndex < 2; $trackIndex++): ?>
                                <div class="testimonios-track" <?php echo $trackIndex === 1 ? ' aria-hidden="true"' : ''; ?>>
                                    <?php foreach ($testimoniosCarrusel as $testimonio): ?>
                                            <?php
                                            $imagenUrl = !empty($testimonio['imagen_servicio']) ? $rutaImagenes . $testimonio['imagen_servicio'] : 'assets/img/blog/blog-1.jpg';
                                            $nombre = isset($testimonio['nombre']) ? $testimonio['nombre'] : 'Sin nombre';
                                            $descripcion = isset($testimonio['descripcion_servicio']) ? $testimonio['descripcion_servicio'] : '';
                                            $fechaCreacion = isset($testimonio['fecha_creacion']) ? trim((string) $testimonio['fecha_creacion']) : '';
                                            ?>
                                            <article class="testimonial-carousel-card">
                                                <div class="testimonial-carousel-card__media">
                                                    <img src="<?= htmlspecialchars($imagenUrl) ?>" alt="<?= htmlspecialchars($nombre) ?>">
                                                </div>

                                                <div class="testimonial-carousel-card__content">
                                                    <h3><?= htmlspecialchars($nombre) ?></h3>
                                                    <p><?= htmlspecialchars($descripcion) ?></p>

                                                </div>
                                            </article>
                                    <?php endforeach; ?>
                                </div>
                        <?php endfor; ?>
                    </div>
            <?php else: ?>
                    <p style="text-align:center; padding:20px;">No hay testimonios disponibles en este momento.</p>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- End Story Area -->

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const serviciosTotal = <?php echo (int) $serviciosCarruselTotal; ?>;
        const serviciosCenterSlide = <?php echo (int) $serviciosInitialSlide; ?>;
        const serviciosLoop = serviciosTotal > 3;

        const serviciosSwiper = new Swiper('.servicios-swiper', {
            effect: 'coverflow',
            grabCursor: true,
            centeredSlides: true,
            slidesPerView: 'auto',
            loop: serviciosLoop,
            rewind: !serviciosLoop && serviciosTotal > 1,
            initialSlide: serviciosCenterSlide,
            loopAdditionalSlides: serviciosLoop ? serviciosTotal : 0,
            slideToClickedSlide: true,
            watchSlidesProgress: true,
            watchOverflow: false,
            spaceBetween: 10,
            coverflowEffect: {
                rotate: 0,
                stretch: 0,
                depth: 180,
                modifier: 1.3,
                slideShadows: true,
                scale: 0.9
            },
            pagination: {
                el: '.servicios-pagination',
                clickable: true
            },
            breakpoints: {
                0: {
                    spaceBetween: 0
                },
                768: {
                    spaceBetween: 18
                }
            }
        });

        const centerServiciosSwiper = () => {
            serviciosSwiper.update();

            if (serviciosLoop && typeof serviciosSwiper.slideToLoop === 'function') {
                serviciosSwiper.slideToLoop(serviciosCenterSlide, 0, false);
                return;
            }

            serviciosSwiper.slideTo(serviciosCenterSlide, 0, false);
        };

        requestAnimationFrame(centerServiciosSwiper);
        window.addEventListener('load', centerServiciosSwiper, { once: true });
        setTimeout(centerServiciosSwiper, 120);

    });
</script>




<!-- End Story Area -->

<!-- Start Footer Area -->
<?php include 'footer.php'; ?>
