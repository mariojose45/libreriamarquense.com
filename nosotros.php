<?php
// SEO para la pagina nosotros
$seo_title = "Nosotros - TI-CELL | Venta y reparacion de celulares";
$seo_description = "Conoce mas sobre TI-CELL, nuestra historia, mision y vision en venta de celulares, accesorios y reparacion tecnica.";
$seo_keywords = "nosotros TI-CELL, historia TI-CELL, tienda de celulares Guatemala, reparacion de celulares";

include 'head.php';
include 'assets/php/rutas.php';

$current_page = basename($_SERVER['PHP_SELF']);

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
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}

/*
|--------------------------------------------------------------------------
| VALORES POR DEFECTO
|--------------------------------------------------------------------------
| Estos se muestran si la API no responde o viene vacia.
|--------------------------------------------------------------------------
*/
$imagenNosotros = 'assets/img/Historia TI-CELL.png';

$historiaParrafos = [
    'TI-CELL nace con el objetivo de acercar celulares, accesorios, repuestos y soluciones tecnicas confiables a cada cliente que busca comprar o reparar su equipo con seguridad.',
    'Desde nuestras sucursales trabajamos para ofrecer una atencion cercana, opciones utiles para diferentes necesidades y un servicio tecnico enfocado en diagnostico, reparacion y compatibilidad real de cada dispositivo.',
    'Nuestro crecimiento se basa en la confianza de nuestros clientes, la rapidez de respuesta y el compromiso de brindar una experiencia clara, honesta y funcional en cada compra o servicio.'
];

$misionTexto = 'Brindar celulares, accesorios, repuestos y servicios de reparacion con atencion cercana, soluciones practicas y acompanamiento confiable para cada cliente.';
$visionTexto = 'Ser una tienda de referencia en tecnologia movil y reparacion de celulares, reconocida por su servicio, confianza, experiencia y respuesta oportuna.';

$diferenciadores = [
    'Venta de celulares, accesorios y repuestos con orientacion practica.',
    'Atencion personalizada para ayudarte a elegir la mejor opcion.',
    'Servicio tecnico para diagnostico, mantenimiento y reparacion.',
    'Acompanamiento en compatibilidad de equipos, modelos y accesorios.',
    'Atencion en sucursales para estar mas cerca de cada cliente.'
];

/*
|--------------------------------------------------------------------------
| ESPACIO DE LA API --AGREGAR LA CORRECTA!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
|--------------------------------------------------------------------------
*/
$url_nosotros = $url_nosotros ?? '';

$apiNosotros = getApiData($url_nosotros);
$nosotrosData = $apiNosotros['data'][0] ?? null;

/*
|--------------------------------------------------------------------------
| MAPEO DE DATOS DE API A LAS VARIABLES DEL DISEÑO
|--------------------------------------------------------------------------
*/
if (!empty($nosotrosData) && is_array($nosotrosData)) {

    $historiaTexto = trim($nosotrosData['historia'] ?? '');
    if ($historiaTexto !== '') {
        $historiaParrafosApi = preg_split("/\r\n|\n|\r/", $historiaTexto);
        $historiaParrafosApi = array_filter(array_map('trim', $historiaParrafosApi));

        if (!empty($historiaParrafosApi)) {
            $historiaParrafos = $historiaParrafosApi;
        }
    }

    $misionApi = trim($nosotrosData['mision'] ?? '');
    if ($misionApi !== '') {
        $misionTexto = $misionApi;
    }

    $visionApi = trim($nosotrosData['vision'] ?? '');
    if ($visionApi !== '') {
        $visionTexto = $visionApi;
    }

    $diferenciaTexto = trim($nosotrosData['diferencia'] ?? '');
    if ($diferenciaTexto !== '') {
        // Soporta separacion por guion o salto de linea
        if (strpos($diferenciaTexto, "\n") !== false || strpos($diferenciaTexto, "\r") !== false) {
            $diferenciadoresApi = preg_split("/\r\n|\n|\r/", $diferenciaTexto);
        } else {
            $diferenciadoresApi = explode('-', $diferenciaTexto);
        }

        $diferenciadoresApi = array_filter(array_map('trim', $diferenciadoresApi));

        if (!empty($diferenciadoresApi)) {
            $diferenciadores = $diferenciadoresApi;
        }
    }

    $imagenApi = trim($nosotrosData['imagen'] ?? '');
    if ($imagenApi !== '') {
        $imagenNosotros = $imagenApi;
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
    .ticell-about-area {
        background: #e9e9e9;
        padding: 50px 0;
    }

    .ticell-about-grid {
        --bs-gutter-x: 18px;
        --bs-gutter-y: 18px;
    }

    .ticell-about-panel {
        background: #ffffff;
        border: 1px solid rgba(36, 71, 155, 0.12);
        border-radius: 28px;
        box-shadow: 0 22px 55px rgba(19, 33, 76, 0.10);
        overflow: hidden;
        position: relative;
    }

    .ticell-about-panel::before {
        background: #24479b;
        content: "";
        display: block;
        height: 8px;
        width: 100%;
    }

    .ticell-about-story,
    .ticell-about-media,
    .ticell-about-card,
    .ticell-about-diff {
        height: 100%;
    }

    .ticell-about-story-group .row {
        --bs-gutter-x: 0;
    }

    .ticell-about-story-body,
    .ticell-about-card-body,
    .ticell-about-diff-body {
        padding: 30px 32px;
    }

    .ticell-about-story h3,
    .ticell-about-card h3,
    .ticell-about-diff h3 {
        color: #16224a;
        font-size: 34px;
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 18px;
    }

    .ticell-about-story p,
    .ticell-about-card p {
        color: #2f3b52;
        font-size: 18px;
        line-height: 1.9;
        margin-bottom: 14px;
    }

    .ticell-about-story p:last-child,
    .ticell-about-card p:last-child {
        margin-bottom: 0;
    }

    .ticell-about-media {
        align-items: center;
        display: flex;
        justify-content: center;
        padding: 26px 26px 26px 0;
    }

    .ticell-about-media img {
        border-radius: 20px;
        display: block;
        height: auto;
        max-height: 470px;
        min-height: 0;
        object-fit: cover;
        width: 100%;
    }

    .ticell-about-card h3 {
        font-size: 30px;
        margin-bottom: 12px;
    }

    .ticell-about-diff-body {
        padding-bottom: 30px;
    }

    .ticell-about-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .ticell-about-list li {
        color: #2f3b52;
        font-size: 18px;
        line-height: 1.85;
        padding-left: 30px;
        position: relative;
    }

    .ticell-about-list li + li {
        margin-top: 10px;
    }

    .ticell-about-list li i {
        color: #4aa364;
        font-size: 18px;
        left: 0;
        position: absolute;
        top: 8px;
    }

    @media only screen and (max-width: 991px) {
        .ticell-about-story h3,
        .ticell-about-card h3,
        .ticell-about-diff h3 {
            font-size: 28px;
        }

        .ticell-about-media {
            padding: 0 22px 22px 22px;
        }

        .ticell-about-media img {
            max-height: 320px;
        }
    }

    @media only screen and (max-width: 767px) {
        .ticell-about-area {
            padding: 40px 0;
        }

        .ticell-about-story-body,
        .ticell-about-card-body,
        .ticell-about-diff-body {
            padding: 26px 22px;
        }

        .ticell-about-story p,
        .ticell-about-card p,
        .ticell-about-list li {
            font-size: 16px;
        }

        .ticell-about-media img {
            max-height: 240px;
        }
    }
</style>

<section class="ticell-about-area">
    <div class="container">
        <div class="row ticell-about-grid">
            <div class="col-lg-12">
                <div class="ticell-about-panel ticell-about-story-group">
                    <div class="row align-items-stretch">
                        <div class="col-lg-7 col-md-12">
                            <div class="ticell-about-story">
                                <div class="ticell-about-story-body">
                                    <h3>Historia de la Empresa</h3>

                                    <?php foreach ($historiaParrafos as $parrafo): ?>
                                        <p><?php echo htmlspecialchars($parrafo, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5 col-md-12">
                            <div class="ticell-about-media">
                                <img
                                    src="<?php echo htmlspecialchars($imagenNosotros, ENT_QUOTES, 'UTF-8'); ?>"
                                    alt="TI-CELL - Nosotros"
                                    onerror="this.onerror=null;this.src='assets/img/servicios/nosotros.png';"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="ticell-about-panel ticell-about-card">
                    <div class="ticell-about-card-body">
                        <h3>Mision</h3>
                        <p><?php echo htmlspecialchars($misionTexto, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 col-md-12">
                <div class="ticell-about-panel ticell-about-card">
                    <div class="ticell-about-card-body">
                        <h3>Vision</h3>
                        <p><?php echo htmlspecialchars($visionTexto, ENT_QUOTES, 'UTF-8'); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="ticell-about-panel ticell-about-diff">
                    <div class="ticell-about-diff-body">
                        <h3>&iquest;Que nos diferencia de la competencia?</h3>

                        <ul class="ticell-about-list">
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