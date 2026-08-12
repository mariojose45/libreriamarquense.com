<?php
$current_page = basename(
    $_SERVER['PHP_SELF'] ?? 'nosotros.php'
);

$seo_title =
    'Nosotros | Librería Marquense';

$seo_description =
    'Conoce más sobre Librería Marquense, nuestra historia, misión y visión como tienda de útiles escolares, papelería, libros, material didáctico y productos de oficina.';

$seo_keywords =
    'Librería Marquense, nosotros Librería Marquense, útiles escolares Guatemala, papelería Guatemala, libros, material didáctico, productos de oficina';

$canonical_url =
    'https://libreriamarquense.com/nosotros.php';

$seo_og_type =
    'website';

$seo_robots =
    'index, follow, max-image-preview:large';

include 'head.php';
include 'assets/php/rutas.php';

/*
|--------------------------------------------------------------------------
| FUNCION PARA CONSUMIR API
|--------------------------------------------------------------------------
*/
if (!function_exists('getApiData')) {
    function getApiData($url)
    {
        if (empty($url)) {
            return null;
        }

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || !$response || $httpCode < 200 || $httpCode >= 300) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}

if (!function_exists('getFirstApiTextValue')) {
    function getFirstApiTextValue(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('normalizeNosotrosImageUrl')) {
    function normalizeNosotrosImageUrl(string $image, string $baseUrl): string
    {
        $image = trim($image);

        if ($image === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $image) || strpos($image, 'assets/') === 0) {
            return $image;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($image, '/');
    }
}

if (!function_exists('cleanDiferenciadorText')) {
    function cleanDiferenciadorText(string $text): string
    {
        $text = trim($text);
        $text = preg_replace('/^(?:(?:\x{2713}|\x{2714}|\x{2022})|[-*])+\s*/u', '', $text);

        return trim((string) $text);
    }
}

/*
|--------------------------------------------------------------------------
| VALORES POR DEFECTO
|--------------------------------------------------------------------------
| Estos se muestran si la API no responde o viene vacia.
|--------------------------------------------------------------------------
*/
$imagenNosotros = 'assets/img/Sucursales/LibreriaMarquense01.jpeg';

$historiaParrafos = [
    'Librería Marquense nace con el propósito de ofrecer útiles escolares, papelería, libros, material didáctico y productos de oficina para estudiantes, docentes, familias y empresas.',
    'Trabajamos para brindar atención cercana, variedad de productos y soluciones prácticas para cada temporada escolar, tarea, oficina o necesidad educativa.',
    'Nuestro compromiso se basa en la confianza de nuestros clientes, la calidad del servicio y el deseo de apoyar la educación con productos útiles, accesibles y confiables.'
];

$misionTexto = 'Brindar útiles escolares, papelería, libros, material didáctico y productos de oficina con atención cercana, variedad, precios accesibles y servicio confiable para cada cliente.';
$visionTexto = 'Ser una librería de referencia en Guatemala, reconocida por su variedad, calidad, atención y compromiso con la educación de estudiantes, docentes, familias y empresas.';

$diferenciadores = [
    'Variedad de útiles escolares, papelería, libros y productos de oficina.',
    'Atención personalizada para estudiantes, docentes, familias y empresas.',
    'Apoyo en la preparación de listas escolares y pedidos por cantidad.',
    'Productos prácticos para tareas, oficina, estudio y actividades educativas.',
    'Servicio cercano, confiable y orientado a las necesidades del cliente.'
];

/*
|--------------------------------------------------------------------------
| ESPACIO DE LA API --AGREGAR LA CORRECTA!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
|--------------------------------------------------------------------------
*/
$url_nosotros = $url_nosotros ?? '';
$url_imagenes_articulos = $url_imagenes_articulos ?? 'https://ssl.sol.sistemasolgt.com/libremarquenseDos/files/articulos/';

$apiNosotros = getApiData($url_nosotros);
$nosotrosData = null;

if (!empty($apiNosotros['data'][0]) && is_array($apiNosotros['data'][0])) {
    $nosotrosData = $apiNosotros['data'][0];
}

/*
|--------------------------------------------------------------------------
| MAPEO DE DATOS DE API A LAS VARIABLES DEL DISEÑO
|--------------------------------------------------------------------------
*/
if (!empty($nosotrosData) && is_array($nosotrosData)) {

    $historiaTexto = getFirstApiTextValue($nosotrosData, ['historia_empresa', 'historia']);
    if ($historiaTexto !== '') {
        $historiaParrafosApi = preg_split("/\r\n|\n|\r/", $historiaTexto);
        $historiaParrafosApi = array_filter(array_map('trim', $historiaParrafosApi));

        if (!empty($historiaParrafosApi)) {
            $historiaParrafos = $historiaParrafosApi;
        }
    }

    $misionApi = getFirstApiTextValue($nosotrosData, ['mision_nosotros', 'mision']);
    if ($misionApi !== '') {
        $misionTexto = $misionApi;
    }

    $visionApi = getFirstApiTextValue($nosotrosData, ['vision_nosotros', 'vision']);
    if ($visionApi !== '') {
        $visionTexto = $visionApi;
    }

    $diferenciaTexto = getFirstApiTextValue($nosotrosData, ['diferencia_nosotros', 'diferencia']);
    if ($diferenciaTexto !== '') {
        // Soporta separacion por guion o salto de linea
        if (strpos($diferenciaTexto, "\n") !== false || strpos($diferenciaTexto, "\r") !== false) {
            $diferenciadoresApi = preg_split("/\r\n|\n|\r/", $diferenciaTexto);
        } else {
            $diferenciadoresApi = explode('-', $diferenciaTexto);
        }

        $diferenciadoresApi = array_filter(array_map('cleanDiferenciadorText', $diferenciadoresApi));

        if (!empty($diferenciadoresApi)) {
            $diferenciadores = $diferenciadoresApi;
        }
    }

    $imagenApi = getFirstApiTextValue($nosotrosData, ['imagen_nosotros', 'imagen']);
    if ($imagenApi !== '') {
        $imagenNosotros = normalizeNosotrosImageUrl($imagenApi, $url_imagenes_articulos);
    }
}
?>

<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>Sobre Nosotros</h2>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>Nosotros</li>
            </ul>
        </div>
    </div>
</div>

<style>
    .lm-about-area {
        background: #F7F8FC;
        padding: 50px 0;
    }

    .lm-about-grid {
        --bs-gutter-x: 18px;
        --bs-gutter-y: 18px;
    }

    .lm-about-panel {
        background: #ffffff;
        border: 1px solid rgba(26, 38, 151, 0.12);
        border-radius: 28px;
        box-shadow: 0 22px 55px rgba(19, 33, 76, 0.10);
        overflow: hidden;
        position: relative;
    }

    .lm-about-panel::before {
        background: #1A2697;
        content: "";
        display: block;
        height: 8px;
        width: 100%;
    }

    .lm-about-story,
    .lm-about-media,
    .lm-about-card,
    .lm-about-diff {
        height: 100%;
    }

    .lm-about-story-group .row {
        --bs-gutter-x: 0;
    }

    .lm-about-story-body,
    .lm-about-card-body,
    .lm-about-diff-body {
        padding: 30px 32px;
    }

    .lm-about-story h3,
    .lm-about-card h3,
    .lm-about-diff h3 {
        color: #17214F;
        font-size: 34px;
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 18px;
    }

    .lm-about-story p,
    .lm-about-card p {
        color: #2D3448;
        font-size: 18px;
        line-height: 1.9;
        margin-bottom: 14px;
    }

    .lm-about-story p:last-child,
    .lm-about-card p:last-child {
        margin-bottom: 0;
    }

    .lm-about-media {
        align-items: center;
        display: flex;
        justify-content: center;
        padding: 26px 26px 26px 0;
    }

    .lm-about-media img {
        border-radius: 20px;
        display: block;
        height: auto;
        max-height: 470px;
        min-height: 0;
        object-fit: cover;
        width: 100%;
    }

    .lm-about-card h3 {
        font-size: 30px;
        margin-bottom: 12px;
    }

    .lm-about-diff-body {
        padding-bottom: 30px;
    }

    .lm-about-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .lm-about-list li {
        color: #2D3448;
        font-size: 18px;
        line-height: 1.85;
        padding-left: 30px;
        position: relative;
    }

    .lm-about-list li + li {
        margin-top: 10px;
    }

    .lm-about-list li i {
        color: #166B38;
        font-size: 18px;
        left: 0;
        position: absolute;
        top: 8px;
    }

    @media only screen and (max-width: 991px) {
        .lm-about-area {
            padding: 36px 0;
        }

        .lm-about-grid {
            --bs-gutter-x: 14px;
            --bs-gutter-y: 14px;
        }

        .lm-about-panel {
            border-radius: 22px;
        }

        .lm-about-panel::before {
            height: 6px;
        }

        .lm-about-story-body,
        .lm-about-card-body,
        .lm-about-diff-body {
            padding: 24px;
        }

        .lm-about-story h3,
        .lm-about-card h3,
        .lm-about-diff h3 {
            font-size: 26px;
            margin-bottom: 12px;
        }

        .lm-about-card h3 {
            font-size: 24px;
        }

        .lm-about-story p,
        .lm-about-card p {
            font-size: 16px;
            line-height: 1.65;
            margin-bottom: 10px;
        }

        .lm-about-list li {
            font-size: 16px;
            line-height: 1.6;
            padding-left: 26px;
        }

        .lm-about-list li + li {
            margin-top: 8px;
        }

        .lm-about-list li i {
            top: 5px;
        }

        .lm-about-media {
            padding: 0 20px 20px 20px;
        }

        .lm-about-media img {
            max-height: 260px;
        }
    }

    @media only screen and (max-width: 767px) {
        .lm-about-area {
            padding: 28px 0;
        }

        .lm-about-grid {
            --bs-gutter-x: 12px;
            --bs-gutter-y: 12px;
        }

        .lm-about-panel {
            border-radius: 18px;
        }

        .lm-about-story-body,
        .lm-about-card-body,
        .lm-about-diff-body {
            padding: 20px 18px;
        }

        .lm-about-story h3,
        .lm-about-card h3,
        .lm-about-diff h3 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .lm-about-story p,
        .lm-about-card p,
        .lm-about-list li {
            font-size: 15px;
            line-height: 1.55;
        }

        .lm-about-list li + li {
            margin-top: 7px;
        }

        .lm-about-media {
            padding: 0 16px 18px 16px;
        }

        .lm-about-media img {
            border-radius: 16px;
            max-height: 210px;
        }
    }

    @media only screen and (max-width: 575px) {
        .lm-about-area {
            padding: 24px 0;
        }

        .lm-about-story-body,
        .lm-about-card-body,
        .lm-about-diff-body {
            padding: 18px 16px;
        }

        .lm-about-story h3,
        .lm-about-card h3,
        .lm-about-diff h3 {
            font-size: 21px;
        }

        .lm-about-story p,
        .lm-about-card p,
        .lm-about-list li {
            font-size: 14.5px;
            line-height: 1.5;
        }

        .lm-about-media img {
            max-height: 190px;
        }
    }
</style>

<section class="lm-about-area">
    <div class="container">
        <div class="row lm-about-grid">
            <div class="col-lg-12">
                <div class="lm-about-panel lm-about-story-group">
                    <div class="row align-items-stretch">
                        <div class="col-lg-7 col-md-12">
                            <div class="lm-about-story">
                                <div class="lm-about-story-body">
                                    <h3>Historia de la Empresa</h3>

                                    <?php foreach ($historiaParrafos as $parrafo): ?>
                                        <p><?php echo htmlspecialchars($parrafo, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 col-md-12">
                            <div class="lm-about-media">
                                <img
                                    src="<?php echo htmlspecialchars($imagenNosotros, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="Librería Marquense - Nosotros"
                                    onerror="this.onerror=null;this.src='assets/img/LogoLibreriaMarquense.jpeg';"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="lm-about-panel lm-about-card">
                    <div class="lm-about-card-body">
                        <h3>Misi&oacute;n</h3>
                        <p><?php echo htmlspecialchars($misionTexto, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="lm-about-panel lm-about-card">
                    <div class="lm-about-card-body">
                        <h3>Visi&oacute;n</h3>
                        <p><?php echo htmlspecialchars($visionTexto, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="lm-about-panel lm-about-diff">
                    <div class="lm-about-diff-body">
                        <h3>&iquest;Qu&eacute; nos diferencia de la competencia?</h3>

                        <ul class="lm-about-list">
                            <?php foreach ($diferenciadores as $diferenciador): ?>
                                <li>
                                    <i class='bx bx-check'></i>
                                    <?php echo htmlspecialchars($diferenciador, ENT_QUOTES, 'UTF-8'); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
