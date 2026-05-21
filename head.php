<?php
function getApi($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, []);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    $response = curl_exec($ch);

    if ($response === false) {
        error_log('Error CURL consultando API: ' . curl_error($ch));
        $response = '';
    }

    curl_close($ch);
    return $response;
}

if (!headers_sent()) {
    $content_security_policy = "default-src 'self'; "
        . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
        . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
        . "img-src 'self' data: blob: https://ssl.sol.sistemasolgt.com; "
        . "font-src 'self' data: https://fonts.gstatic.com;"
        . "connect-src 'self' https://ssl.sol.sistemasolgt.com https://cdn.jsdelivr.net; "
        . "media-src 'self' data: blob:; "
        . "object-src 'none'; "
        . "base-uri 'self'; "
        . "frame-ancestors 'self'; "
        . "upgrade-insecure-requests; "
        . "form-action 'self';";

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: accelerometer=(), autoplay=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()');
    header('Content-Security-Policy: ' . $content_security_policy);
}

// Definir variables para evitar errores
if (!isset($current_page)) {
    $current_page = basename($_SERVER['PHP_SELF']);
}

if (!isset($paginas_servicios)) {
    $paginas_servicios = [
        'servicios.php'
    ];
}

include "assets/php/rutas.php";

$response = getApi($url_listar_categorias);
$data = json_decode($response, true);

$categorias = $data["data"] ?? [];

?>

<!doctype html>
<html lang="es-GT">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <!-- Meanmenu CSS -->
    <link rel="stylesheet" href="assets/css/meanmenu.css">
    <!-- Boxicons CSS -->
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <!-- Flaticon CSS -->
    <link rel="stylesheet" href="assets/css/flaticon.css">
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <!-- Owl Carousel Default CSS -->
    <link rel="stylesheet" href="assets/css/owl.theme.default.min.css">
    <!-- Magnific Popup CSS -->
    <link rel="stylesheet" href="assets/css/magnific-popup.min.css">
    <!-- Nice Select CSS -->
    <link rel="stylesheet" href="assets/css/nice-select.min.css">
    <!-- Slick CSS -->
    <link rel="stylesheet" href="assets/css/slick.min.css">

    <!-- Odometer CSS -->
    <link rel="stylesheet" href="assets/css/odometer.min.css">
    <!-- Style CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
    <!-- Dark CSS -->
    <link rel="stylesheet" href="assets/css/dark.css?v=<?php echo filemtime('assets/css/dark.css'); ?>">
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="assets/css/responsive.css?v=<?php echo filemtime('assets/css/responsive.css'); ?>">

    <?php
    // Configuracion SEO por defecto
    $site_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $current_url = $site_url . $_SERVER['REQUEST_URI'];

    // SEO por defecto (puedes personalizar por pagina)
    $seo_title = isset($seo_title) ? $seo_title : "Librería Marquense | Útiles escolares y papelería";
    $seo_description = isset($seo_description) ? $seo_description : "Librería Marquense ofrece útiles escolares, papelería, libros, material didáctico y productos de oficina con atención confiable en Guatemala.";
    $seo_keywords = isset($seo_keywords) ? $seo_keywords : "Librería Marquense, útiles escolares Guatemala, papelería, libros, material didáctico, productos escolares";
    $seo_image = isset($seo_image) ? $seo_image : $site_url . "/assets/img/LogoLibreriaMarquense.jpeg";
    $browser_title = "Librería Marquense | Útiles escolares y papelería";
    $site_name = isset($site_name) ? $site_name : "Librería Marquense";
    $site_phone_number = isset($site_phone_number) ? $site_phone_number : "+502 2232-8537 / +502 2253-6302 / +502 2372-3286 / +502 2372-3287";
    $site_whatsapp_number = isset($site_whatsapp_number) ? $site_whatsapp_number : "+502 5591-0533";
    $site_whatsapp_url = isset($site_whatsapp_url) ? $site_whatsapp_url : "https://wa.me/50255910533";
    $site_email = isset($site_email) ? $site_email : "servicioslcliente@libreriamarquense.com";
    $site_business_description = isset($site_business_description) ? $site_business_description : "Venta de útiles escolares, papelería, libros, material didáctico y productos de oficina en Guatemala.";
    $site_social_links = isset($site_social_links) ? $site_social_links : [
        'facebook' => 'https://www.facebook.com/LibreriaMarquenseSA?locale=es_LA',
        'instagram' => 'https://www.instagram.com/libreriamarquense/?hl=es',
        'tiktok' => 'https://www.tiktok.com/@lmmarquense/video/7632414546631658760',
    ];
    $site_social_same_as = array_values(array_filter($site_social_links));

    // Limpiar URL para canonical
    $canonical_url = strtok($current_url, '?');
    ?>

    <!-- SEO Meta Tags -->
    <title><?php echo htmlspecialchars($browser_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_keywords); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($site_name); ?>">
    <meta name="robots" content="index, follow">
    <meta name="language" content="Spanish">
    <meta name="revisit-after" content="7 days">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">

    <!-- Google Search Console Verification -->
    <meta name="google-site-verification" content="yX3GJ3Ju-PIJeHR1dsRWzP_g1CPGgeKGrgi7-zCywvc" />
    <!-- google-site-verification=yX3GJ3Ju-PIJeHR1dsRWzP_g1CPGgeKGrgi7-zCywvc -->
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars($current_url); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($seo_image); ?>">
    <meta property="og:locale" content="es_GT">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($current_url); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($seo_image); ?>">

        <!-- Schema.org structured data -->
    <script type="application/ld+json">
                {
                "@context": "https://schema.org",
                "@type": "Store",
                "name": <?php echo json_encode($site_name); ?>,
                "url": <?php echo json_encode($site_url); ?>,
                "logo": <?php echo json_encode($site_url . "/assets/img/LogoLibreriaMarquense.jpeg"); ?>,
                "description": <?php echo json_encode($site_business_description); ?>,
                "address": {
                    "@type": "PostalAddress",
                    "addressCountry": "GT"
                },
                "contactPoint": {
                    "@type": "ContactPoint",
                    "telephone": <?php echo json_encode($site_phone_number); ?>,
                    "contactType": "servicio al cliente"<?php if (!empty($site_email)): ?>,
                    "email": <?php echo json_encode($site_email); ?><?php endif; ?>
                },
                "sameAs": <?php echo json_encode($site_social_same_as, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
                }
                </script>


    
    <script type="application/ld+json">
                    {
                    "@context": "https://schema.org",
                    "@type": "WebSite",
                    "name": <?php echo json_encode($site_name); ?>,
                    "url": <?php echo json_encode($site_url); ?>,
                    "potentialAction": {
                        "@type": "SearchAction",
                        "target": <?php echo json_encode($site_url . "/tienda.php?buscar={search_term_string}"); ?>,
                        "query-input": "required name=search_term_string"
                    }
                    }
                </script>


    <link rel="icon" type="image/jpeg" href="assets/img/LogoLibreriaMarquense.jpeg?v=2">
    <link rel="shortcut icon" type="image/jpeg" href="assets/img/LogoLibreriaMarquense.jpeg?v=2">
</head>

<body>

    <!-- Start Preloader Area -->
    <div class="preloader">
        <div class="loader">
            <img src="assets/img/LogoLibreriaMarquense.jpeg" alt="Cargando..." style="max-width: 300px; border-radius: 10px;">
        </div>
    </div>
    <!-- End Preloader Area -->


    <!-- End Top Header Area -->

    <!-- Start Middle Header Area -->
    <div class="middle-header-area">
        <div class="container">
            <div class="row align-items-center">


                <div class="col-lg-10">
                    <div class="middle-header-search">
                        <form role="search" onsubmit="buscarProductos(event)">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <select id="select-categoria-header" class="form-control">
                                            <option value="">Todas las categorías</option>

                                            <?php foreach ($categorias as $cat): ?>
                                                <option value="<?= htmlspecialchars((string) $cat['idcategoria'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string) $cat['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <div class="search-box">
                                        <input type="search" id="search" name="buscar" class="form-control"
                                            placeholder="Busqueda de productos..." autocomplete="off"
                                            aria-label="Busqueda de productos">
                                        <button type="submit" aria-label="Buscar productos"><i
                                                class='bx bx-search'></i></button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-2">
                    <ul class="middle-header-optional">
                        <li>
                            <a href="cart.php" class="cart-icon">
                                <i class="flaticon-shopping-cart"></i>
                                <span class="cart-count"></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <!-- End Middle Header Area -->
    <style>
        .logo-grande {
            width: clamp(104px, 16vw, 132px) !important;
            height: auto !important;
            max-width: 100% !important;
            display: block;
            margin: 0 auto;
        }

        @media (max-width: 768px) {
            .logo-grande {
                width: 112px !important;
            }
        }

        @media (max-width: 480px) {
            .logo-grande {
                width: 98px !important;
            }
        }

        /* Estilos para el icono del carrito */
        .cart-icon {
            position: relative;
            display: inline-block;
            font-size: 24px;
            color: #333;
            text-decoration: none;
        }

        .cart-icon:hover {
            color: #1A2697;
        }

        .cart-icon .flaticon-shopping-cart {
            font-size: 24px;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #1A2697;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            min-width: 20px;
        }

        .cart-count:empty {
            display: none;
        }

        .middle-header-optional li {
            list-style: none;
        }

        /* Logo en el header superior */
        .logo-header {
            max-width: 180px;
            height: auto;
        }

        /* Asegurar que el icono del carrito sea visible */
        .middle-header-optional {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .middle-header-optional .cart-icon {
            font-size: 24px;
            color: #333;
            text-decoration: none;
            padding: 5px;
        }

        .middle-header-optional .cart-icon i {
            font-size: 24px;
        }

        /* Logo en el navbar principal */
        .main-navbar {
            background: #fff;
        }

        .main-navbar .navbar {
            min-height: 96px;
            padding: 10px 0;
            align-items: center;
        }

        .navbar-brand {
            padding: 0;
            margin-right: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-navbar {
            width: clamp(128px, 10vw, 154px);
            max-width: 154px;
            height: auto;
            display: block;
        }

        @media (min-width: 992px) and (max-width: 1199px) {
            .logo-navbar {
                width: 132px;
            }

            .main-navbar .navbar .navbar-nav .nav-item a {
                padding-left: 14px;
                padding-right: 14px;
            }
        }

        @media (max-width: 991px) {
            .navbar-brand {
                display: none;
            }
        }

        .main-responsive-menu>div,
        .main-responsive-menu a {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Icono del carrito en el navbar */
        .cart-icon-navbar {
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-icon-navbar i {
            font-size: 20px;
        }

        .cart-count-navbar {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #B73639;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            min-width: 18px;
        }

        .cart-count-navbar:empty {
            display: none;
        }

        .middle-header-area {
            position: relative;
            z-index: 10020;
        }

        .middle-header-search {
            position: relative;
            z-index: 10030;
        }

        .middle-header-search form .form-group .nice-select {
            z-index: 10040;
        }

        .middle-header-search form .form-group .nice-select.open {
            z-index: 10070;
        }

        .middle-header-search form .form-group .nice-select .list {
            z-index: 10080;
        }

        .middle-header-search form .form-group .nice-select.open .list {
            opacity: 1 !important;
            pointer-events: auto !important;
            transform: scale(1) translateY(0) !important;
            -webkit-transform: scale(1) translateY(0) !important;
        }

        .middle-header-search form .form-group .nice-select .list .option:hover,
        .middle-header-search form .form-group .nice-select .list .option.focus,
        .middle-header-search form .form-group .nice-select .list .option.selected {
            background-color: #1A2697 !important;
            color: #ffffff !important;
        }

        /* Mobile menu layer: always above banners, videos and page content. */
        @media (max-width: 991px) {

            .middle-header-area,
            .middle-header-search {
                position: relative;
                z-index: 2000000;
            }

            #select-categoria-header,
            .middle-header-search form .form-group .nice-select {
                position: relative;
                z-index: 2000001;
            }

            .middle-header-search form .form-group .nice-select.open,
            .middle-header-search form .form-group .nice-select .list {
                z-index: 2000002 !important;
            }

            .middle-header-search form .form-group .nice-select.open .list {
                opacity: 1 !important;
                pointer-events: auto !important;
                transition: none !important;
                -webkit-transition: none !important;
                transform: scale(1) translateY(0) !important;
                -webkit-transform: scale(1) translateY(0) !important;
            }

            .middle-header-search form .form-group .nice-select .list .option:hover,
            .middle-header-search form .form-group .nice-select .list .option.focus,
            .middle-header-search form .form-group .nice-select .list .option.selected {
                color: #ffffff !important;
            }

            .navbar-area,
            .navbar-area.is-sticky,
            .main-responsive-nav,
            .main-responsive-menu.mean-container {
                position: relative;
                z-index: 1000000 !important;
                overflow: visible !important;
                isolation: isolate;
            }

            .mean-container .mean-bar {
                position: absolute !important;
                left: 0;
                right: 0;
                z-index: 1000001 !important;
                overflow: visible !important;
                background: transparent !important;
            }

            .mean-container a.meanmenu-reveal {
                z-index: 1000003 !important;
            }

            .mean-container .mean-nav {
                position: relative;
                z-index: 1000002 !important;
                background: #ffffff !important;
                box-shadow: 0 18px 35px rgba(0, 0, 0, 0.14);
            }

            .mean-container .mean-nav ul,
            .mean-container .mean-nav ul li,
            .mean-container .mean-nav ul li a {
                background: #ffffff !important;
            }

            .mean-container .mean-nav ul li a {
                border-color: #dbeefd !important;
            }

        }
    </style>

    <!-- Start Navbar Area -->
    <div class="navbar-area">
        <div class="main-responsive-nav">
            <div class="container">
                <div class="main-responsive-menu">
                    <div>
                        <a href="index.php">
                            <img src="assets/img/LogoLibreriaMarquense.jpeg" alt="Librería Marquense" class="logo-grande"
                                onerror="this.src='assets/img/LogoLibreriaMarquense.jpeg'; this.onerror=null;">
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-navbar">
            <div class="container">
                <nav class="navbar navbar-expand-md navbar-light">
                    <a class="navbar-brand" href="index.php">
                        <img src="assets/img/LogoLibreriaMarquense.jpeg" alt="Librería Marquense" class="logo-navbar"
                            onerror="this.src='assets/img/LogoLibreriaMarquense.jpeg'; this.onerror=null;">
                    </a>

                    <div class="collapse navbar-collapse mean-menu">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a href="index.php" class="nav-link">
                                    Inicio
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="tienda.php" class="nav-link">
                                    Productos
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="servicios.php" class="nav-link">
                                    Servicios
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="ofertas.php" class="nav-link">
                                    Ofertas
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="menosde100.php" class="nav-link">
                                    Menos de 100
                                </a>
                            </li>


                            <li class="nav-item">
                                <a href="cart.php" class="nav-link cart-icon-navbar">
                                    <i class="flaticon-shopping-cart"></i>
                                    Carrito
                                    <span class="cart-count-navbar"></span>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a href="contact.php" class="nav-link">Contactanos</a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>


    </div>
    <!-- End Navbar Area -->
