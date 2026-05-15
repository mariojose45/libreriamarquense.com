<?php
// SEO para la pagina de contacto
$seo_title = "Contacto - Librería Marquense | Útiles escolares y papelería";
$seo_description = "Contacta a Librería Marquense para consultas sobre útiles escolares, papelería, listas escolares, sucursales y pedidos.";
$seo_keywords = "contacto Librería Marquense, librería Guatemala, útiles escolares, papelería, listas escolares";

$branch_timezone = new DateTimeZone('America/Guatemala');

function create_daily_schedule(array $days, $open_hour, $open_minute, $close_hour, $close_minute) {
    $schedule = [];

    foreach ($days as $day) {
        $schedule[$day] = [$open_hour, $open_minute, $close_hour, $close_minute];
    }

    return $schedule;
}

function get_branch_status(array $weekly_schedule, DateTimeZone $timezone) {
    $now = new DateTime('now', $timezone);
    $day_of_week = (int) $now->format('N');

    if (!isset($weekly_schedule[$day_of_week])) {
        return [
            'class' => 'branch-card__availability--closed',
            'label' => 'Cerrado',
        ];
    }

    [$open_hour, $open_minute, $close_hour, $close_minute] = $weekly_schedule[$day_of_week];
    $current_minutes = ((int) $now->format('G') * 60) + (int) $now->format('i');
    $open_minutes = ($open_hour * 60) + $open_minute;
    $close_minutes = ($close_hour * 60) + $close_minute;
    $is_open_now = $current_minutes >= $open_minutes && $current_minutes < $close_minutes;

    return [
        'class' => $is_open_now ? 'branch-card__availability--open' : 'branch-card__availability--closed',
        'label' => $is_open_now ? 'Abierto' : 'Cerrado',
    ];
}

function branch_image_src($filename) {
    return 'assets/img/Sucursales/' . rawurlencode($filename);
}

$branch_statuses = [
    'san_miguel_ixtahuacan' => get_branch_status(
        array_replace(
            create_daily_schedule([1, 2, 3, 4, 5], 8, 0, 17, 30),
            create_daily_schedule([6], 8, 0, 13, 0)
        ),
        $branch_timezone
    ),
    'zamara' => get_branch_status(create_daily_schedule([1, 2, 3, 4, 5, 7], 8, 30, 17, 0), $branch_timezone),
    'san_miguel_ixtahuacan_2' => get_branch_status(create_daily_schedule([1, 2, 3, 4, 5, 7], 8, 0, 17, 0), $branch_timezone),
    'sipacapa' => get_branch_status(create_daily_schedule([1, 2, 3, 4, 5, 7], 8, 30, 18, 0), $branch_timezone),
    'huitan' => get_branch_status(
        array_replace(
            create_daily_schedule([1, 2, 3, 4, 5], 8, 0, 18, 0),
            create_daily_schedule([6, 7], 8, 0, 17, 0)
        ),
        $branch_timezone
    ),
];

include 'head.php';
$current_page = basename($_SERVER['PHP_SELF']);
?>

        <!-- Start Page Banner -->
        <div class="page-title-area">
            <div class="container">
                <div class="page-title-content">
                    <h2>Contactanos</h2>

                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li>Contactanos</li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- End Page Banner -->

        <!-- Start Contact Area -->
        <section class="contact-area ptb-50">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="contact-form">
                            <div class="tile">
                                <h3>Dejanos tu mensaje</h3>
                                <p>Tu email no sera publicado. Los campos requeridos estan marcados con *</p>
                            </div>

                            <form id="contactForm">
                                <div class="row">
                                    <div class="col-lg-12 col-md-12">
                                        <div class="form-group">
                                            <label>Mensaje*</label>

                                            <textarea name="message" id="message" cols="30" rows="5" required data-error="Por favor ingrese su mensaje" class="form-control"></textarea>
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Nombre*</label>

                                            <input type="text" name="name" id="name" class="form-control" required data-error="Por favor ingrese su nombre">
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-6">
                                        <div class="form-group">
                                            <label>Email*</label>

                                            <input type="email" name="email" id="email" class="form-control" required data-error="Por favor ingrese su email">
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-12">
                                        <div class="form-group">
                                            <label>Telefono*</label>

                                            <input type="text" name="phone_number" id="phone_number" class="form-control" required data-error="Por favor ingrese su telefono">
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-12">
                                        <div class="form-group">
                                            <label>Asunto*</label>

                                            <input type="text" name="msg_subject" id="msg_subject" class="form-control" required data-error="Por favor ingrese el asunto">
                                            <div class="help-block with-errors"></div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 col-md-12">
                                        <button type="submit" class="default-btn">
                                            Enviar mensaje
                                            <span></span>
                                        </button>
                                        <div id="msgSubmit" class="h3 text-center hidden"></div>
                                        <div class="clearfix"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- End Contact Area -->

        <!-- Start Branches Area -->
        <section class="branches-area pb-50">
            <div class="container">
                <div class="tile branch-title">
                    <h3>Nuestra Sucursal</h3>
                </div>

                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-12">
                        <div class="branch-card">
                            <div class="branch-card__media">
                                <img src="<?= htmlspecialchars(branch_image_src('LibreriaMarquense01.jpeg')) ?>" alt="Sucursal Librer&iacute;a Marquense Ciudad de Guatemala">
                            </div>
                            <div class="branch-card__body">
                                <div class="branch-card__summary">
                                    <div class="branch-card__copy">
                                        <h3>Librer&iacute;a Marquense</h3>
                                        <p class="branch-card__address">8A Avenida 19-55, Cdad. de Guatemala 01001.</p>
                                        <p class="branch-card__pickup">Atencion en tienda disponible</p>
                                    </div>

                                    <ul class="branch-card__meta">
                                        <li class="branch-card__availability <?php echo $branch_statuses['san_miguel_ixtahuacan']['class']; ?>">
                                            <span class="branch-card__status-dot" aria-hidden="true"></span>
                                            <?php echo $branch_statuses['san_miguel_ixtahuacan']['label']; ?>
                                        </li>
                            <li><i class='bx bx-phone-call'></i> <?php echo htmlspecialchars($site_phone_number); ?></li>
                                        <li><i class='bx bx-envelope'></i> <a href="mailto:servicioslcliente@libreriamarquense.com">servicioslcliente@libreriamarquense.com</a></li>
                                        <li><i class='bx bx-time-five'></i> Lunes a Viernes 8:00 am a 5:30 pm</li>
                                        <li><i class='bx bx-calendar'></i> Sabado 8:00 am a 1:00 pm</li>
                                        <li><i class='bx bx-calendar-x'></i> Domingo cerrado</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="branch-card__actions">
                                <a href="https://maps.app.goo.gl/MyeMAYkL67PXadYY6" target="_blank" rel="noopener"><span>Ir con Maps</span><img src="assets/img/Sucursales/GoogleMaps.png" alt="Google Maps"></a>
                                <a href="https://waze.com/ul?q=8A%20Avenida%2019-55%2C%20Ciudad%20de%20Guatemala%2001001" target="_blank" rel="noopener"><span>Ir con Waze</span><img src="assets/img/Sucursales/Waze.png" alt="Waze"></a>
                                <a href="<?php echo htmlspecialchars($site_whatsapp_url); ?>" target="_blank" rel="noopener"><span>WhatsApp</span><i class='bx bxl-whatsapp'></i></a>
                            </div>
                        </div>
                    </div>

                    <?php if (false): ?>
                    <div class="col-lg-6 col-md-12">
                        <div class="branch-card">
                            <div class="branch-card__media">
                                <img src="<?= htmlspecialchars(branch_image_src('TI-CELL Zamara.jpeg')) ?>" alt="Sucursal Librer&iacute;a Marquense Zamara">
                            </div>
                            <div class="branch-card__body">
                                <div class="branch-card__summary">
                                    <div class="branch-card__copy">
                                        <h3>Librer&iacute;a Marquense Zamara</h3>
                                        <p class="branch-card__address">Informacion de direccion pendiente de confirmar.</p>
                                        <p class="branch-card__pickup">Atencion en tienda disponible</p>
                                    </div>

                                    <ul class="branch-card__meta">
                                        <li class="branch-card__availability <?php echo $branch_statuses['zamara']['class']; ?>">
                                            <span class="branch-card__status-dot" aria-hidden="true"></span>
                                            <?php echo $branch_statuses['zamara']['label']; ?>
                                        </li>
                            <li><i class='bx bx-phone-call'></i> <?php echo htmlspecialchars($site_phone_number); ?></li>
                                        
                                        <li><i class='bx bx-time-five'></i> Domingo a Viernes 8:30 am a 5:00 pm</li><li><i class='bx bx-envelope'></i> <a href="mailto:servicioslcliente@libreriamarquense.com">servicioslcliente@libreriamarquense.com</a></li>
                                        <li><i class='bx bx-calendar-x'></i> Sabado cerrado</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="branch-card__actions">
                                <a href="https://maps.app.goo.gl/1PLmn8J6Uncqh8dY8?g_st=ac" target="_blank" rel="noopener"><span>Ir con Maps</span><img src="assets/img/Sucursales/GoogleMaps.png" alt="Google Maps"></a>
                                <a href="https://waze.com/ul?q=Libreria%20Marquense%20Zamara" target="_blank" rel="noopener"><span>Ir con Waze</span><img src="assets/img/Sucursales/Waze.png" alt="Waze"></a>
                                <a href="<?php echo htmlspecialchars($site_whatsapp_url); ?>" target="_blank" rel="noopener"><span>WhatsApp</span><i class='bx bxl-whatsapp'></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="branch-card">
                            <div class="branch-card__media">
                                <img src="<?= htmlspecialchars(branch_image_src('TI-CELL San Miguel Ixtahuacán2.jpeg')) ?>" alt="Sucursal Librer&iacute;a Marquense San Miguel Ixtahuac&aacute;n 2">
                            </div>
                            <div class="branch-card__body">
                                <div class="branch-card__summary">
                                    <div class="branch-card__copy">
                                        <h3>Librer&iacute;a Marquense San Miguel Ixtahuac&aacute;n 2</h3>
                                        <p class="branch-card__address">Informacion de direccion pendiente de confirmar.</p>
                                        <p class="branch-card__pickup">Atencion en tienda disponible</p>
                                    </div>

                                    <ul class="branch-card__meta">
                                        <li class="branch-card__availability <?php echo $branch_statuses['san_miguel_ixtahuacan_2']['class']; ?>">
                                            <span class="branch-card__status-dot" aria-hidden="true"></span>
                                            <?php echo $branch_statuses['san_miguel_ixtahuacan_2']['label']; ?>
                                        </li>
                            <li><i class='bx bx-phone-call'></i> <?php echo htmlspecialchars($site_phone_number); ?></li>
                                        <li><i class='bx bx-envelope'></i> <a href="mailto:servicioslcliente@libreriamarquense.com">servicioslcliente@libreriamarquense.com</a></li>
                                        <li><i class='bx bx-time-five'></i> Domingo a Viernes 8:00 am a 5:00 pm</li>
                                        <li><i class='bx bx-calendar-x'></i> Sabado cerrado</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="branch-card__actions">
                                <a href="https://maps.app.goo.gl/tSR2G5jenkkegkJJ6?g_st=aw" target="_blank" rel="noopener"><span>Ir con Maps</span><img src="assets/img/Sucursales/GoogleMaps.png" alt="Google Maps"></a>
                                <a href="https://waze.com/ul?q=Libreria%20Marquense%20San%20Miguel%20Ixtahuacan%202" target="_blank" rel="noopener"><span>Ir con Waze</span><img src="assets/img/Sucursales/Waze.png" alt="Waze"></a>
                                <a href="<?php echo htmlspecialchars($site_whatsapp_url); ?>" target="_blank" rel="noopener"><span>WhatsApp</span><i class='bx bxl-whatsapp'></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="branch-card">
                            <div class="branch-card__media">
                                <img src="<?= htmlspecialchars(branch_image_src('TI-CELL Sipacapa.jpeg')) ?>" alt="Sucursal Librer&iacute;a Marquense Sipacapa">
                            </div>
                            <div class="branch-card__body">
                                <div class="branch-card__summary">
                                    <div class="branch-card__copy">
                                        <h3>Librer&iacute;a Marquense Sipacapa</h3>
                                        <p class="branch-card__address">Informacion de direccion pendiente de confirmar.</p>
                                        <p class="branch-card__pickup">Atencion en tienda disponible</p>
                                    </div>

                                    <ul class="branch-card__meta">
                                        <li class="branch-card__availability <?php echo $branch_statuses['sipacapa']['class']; ?>">
                                            <span class="branch-card__status-dot" aria-hidden="true"></span>
                                            <?php echo $branch_statuses['sipacapa']['label']; ?>
                                        </li>
                            <li><i class='bx bx-phone-call'></i> <?php echo htmlspecialchars($site_phone_number); ?></li>
                                        <li><i class='bx bx-envelope'></i> <a href="mailto:servicioslcliente@libreriamarquense.com">servicioslcliente@libreriamarquense.com</a></li>
                                        <li><i class='bx bx-time-five'></i> Domingo a Viernes 8:30 am a 6:00 pm</li>
                                        <li><i class='bx bx-calendar-x'></i> Sabado cerrado</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="branch-card__actions">
                                <a href="https://maps.app.goo.gl/F5BjPJA7QTPsLif26?g_st=aw" target="_blank" rel="noopener"><span>Ir con Maps</span><img src="assets/img/Sucursales/GoogleMaps.png" alt="Google Maps"></a>
                                <a href="https://waze.com/ul?q=Libreria%20Marquense%20Sipacapa" target="_blank" rel="noopener"><span>Ir con Waze</span><img src="assets/img/Sucursales/Waze.png" alt="Waze"></a>
                                <a href="<?php echo htmlspecialchars($site_whatsapp_url); ?>" target="_blank" rel="noopener"><span>WhatsApp</span><i class='bx bxl-whatsapp'></i></a>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-12">
                        <div class="branch-card">
                            <div class="branch-card__media">
                                <img src="<?= htmlspecialchars(branch_image_src('TI-CELL Huitán.jpeg')) ?>" alt="Sucursal Librer&iacute;a Marquense Huit&aacute;n">
                            </div>
                            <div class="branch-card__body">
                                <div class="branch-card__summary">
                                    <div class="branch-card__copy">
                                        <h3>Librer&iacute;a Marquense Huit&aacute;n</h3>
                                        <p class="branch-card__address">Informacion de direccion pendiente de confirmar.</p>
                                        <p class="branch-card__pickup">Atencion en tienda disponible</p>
                                    </div>

                                    <ul class="branch-card__meta">
                                        <li class="branch-card__availability <?php echo $branch_statuses['huitan']['class']; ?>">
                                            <span class="branch-card__status-dot" aria-hidden="true"></span>
                                            <?php echo $branch_statuses['huitan']['label']; ?>
                                        </li>
                            <li><i class='bx bx-phone-call'></i> <?php echo htmlspecialchars($site_phone_number); ?></li>
                                        <li><i class='bx bx-envelope'></i> <a href="mailto:servicioslcliente@libreriamarquense.com">servicioslcliente@libreriamarquense.com</a></li>
                                        <li><i class='bx bx-time-five'></i> Lunes a Viernes 8:00 am a 6:00 pm</li>
                                        <li><i class='bx bx-calendar'></i> Sabado y Domingo 8:00 am a 5:00 pm</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="branch-card__actions">
                                <a href="https://maps.app.goo.gl/uucnyxR72uFgJuHb8" target="_blank" rel="noopener"><span>Ir con Maps</span><img src="assets/img/Sucursales/GoogleMaps.png" alt="Google Maps"></a>
                                <a href="https://waze.com/ul?q=Libreria%20Marquense%20Huitan" target="_blank" rel="noopener"><span>Ir con Waze</span><img src="assets/img/Sucursales/Waze.png" alt="Waze"></a>
                                <a href="<?php echo htmlspecialchars($site_whatsapp_url); ?>" target="_blank" rel="noopener"><span>WhatsApp</span><i class='bx bxl-whatsapp'></i></a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <!-- End Branches Area -->

        <style>

        .contact-form {
            margin: 0 auto;
            max-width: 900px;
        }

        .branch-title {
            margin: 0 auto 35px;
            max-width: 900px;
        }

        .branch-title h3 {
            border-bottom: 1px solid #cccccc;
            font-size: 25px;
            margin-bottom: 0;
            padding-bottom: 15px;
            position: relative;
        }

        .branch-title h3::before {
            background-color: #1A2697;
            bottom: 0;
            content: "";
            height: 1px;
            left: 0;
            position: absolute;
            width: 70px;
        }

        .branches-area .container {
            max-width: 1320px;
        }

        .branches-area .row {
            margin-left: auto;
            margin-right: auto;
            max-width: 1280px;
            row-gap: 40px;
        }

        .branch-card {
            background: #ffffff;
            border: 2px solid #1A2697;
            border-radius: 36px;
            box-shadow: 0 16px 36px rgba(26, 38, 151, .10);
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            height: 100%;
            margin: 0 auto;
            max-width: 620px;
            overflow: hidden;
            padding: 30px 32px;
        }

        .branch-card__media {
            flex: 0 0 46%;
        }

        .branch-card__media img {
            border-radius: 34px;
            display: block;
            height: 250px;
            max-height: 250px;
            object-fit: cover;
            object-position: center;
            width: 100%;
        }

        .branch-card__body {
            display: flex;
            flex: 1;
            flex-direction: column;
            min-width: 0;
            justify-content: center;
        }

        .branch-card__summary {
            display: block;
        }

        .branch-card__body h3 {
            color: #1A2697;
            font-size: 20px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 8px;
        }

        .branch-card__address,
        .branch-card__pickup {
            font-size: 13px;
            line-height: 1.2;
            margin-bottom: 6px;
        }

        .branch-card__pickup {
            color: #166B38;
            font-weight: 700;
        }

        .branch-card__meta {
            border-top: 1px solid #ececec;
            margin: 8px 0 0;
            padding: 12px 0 0;
        }

        .branch-card__meta li {
            align-items: flex-start;
            color: #5b5b5b;
            display: flex;
            font-size: 12px;
            gap: 8px;
            line-height: 1.2;
            margin-bottom: 9px;
        }

        .branch-card__meta i {
            color: #777777;
            font-size: 17px;
            margin-top: 2px;
        }

        .branch-card__availability {
            align-items: center !important;
            font-weight: 700;
        }

        .branch-card__availability--open {
            color: #166B38 !important;
        }

        .branch-card__availability--closed {
            color: #B73639 !important;
        }

        .branch-card__status-dot {
            background: currentColor;
            border-radius: 50%;
            display: inline-block;
            flex: 0 0 10px;
            height: 10px;
            width: 10px;
        }

        .branch-card__actions {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            flex: 0 0 100%;
            margin: 6px auto 0;
            max-width: 640px;
            width: 100%;
        }

        .branch-card__actions a {
            align-items: center;
            background: #ffffff;
            border: 1px solid #d8d8d8;
            border-radius: 20px;
            color: #5F6675;
            display: flex;
            font-size: 13px;
            font-weight: 500;
            gap: 8px;
            justify-content: center;
            min-height: 50px;
            padding: 8px 10px;
            text-align: center;
            transition: all .3s ease;
            width: 100%;
        }

        .branch-card__actions a img {
            height: 20px;
            object-fit: contain;
            width: 20px;
        }

        .branch-card__actions a i {
            color: #166B38;
            font-size: 20px;
            line-height: 1;
        }

        .branch-card__actions a:hover {
            border-color: #1A2697;
            color: #1A2697;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {

        .contact-area .container,
        .branches-area .container {
            max-width: 100%;
            padding-left: 14px;
            padding-right: 14px;
        }

        .contact-form {
            margin: 0 auto;
            max-width: none;
            padding: 18px 14px;
        }

        .branch-title {
            margin-bottom: 24px;
            max-width: none;
        }

        .contact-form .tile h3,
        .branch-title h3 {
            font-size: 22px;
            line-height: 1.3;
        }

        .contact-form .default-btn {
            margin-top: 10px;
            text-align: center;
            width: 100%;
        }

        .branch-card {
            border-radius: 26px;
            flex-direction: column;
            flex-wrap: nowrap;
            gap: 20px;
            max-width: none;
            padding: 18px;
        }

        .branches-area .row {
            row-gap: 24px;
        }

        .branches-area .col-lg-6,
        .branches-area .col-md-12 {
            padding-left: 8px;
            padding-right: 8px;
        }

        .branch-card__summary {
            display: block;
        }

        .branch-card__media,
        .branch-card__body {
            flex: 0 0 auto;
            width: 100%;
        }

        .branch-card__actions {
            flex: 0 0 auto;
            width: 100%;
        }

        .branch-card__media img {
            border-radius: 22px;
            height: 220px;
            max-height: 240px;
        }

        .branch-card__body h3 {
            font-size: 22px;
        }

        .branch-card__address,
        .branch-card__pickup,
        .branch-card__meta li {
            font-size: 14px;
        }

        .branch-card__actions {
            margin-top: 14px;
            grid-template-columns: 1fr;
            max-width: none;
        }

        .branch-card__actions a {
            gap: 10px;
            min-height: 58px;
        }

        .branch-card__actions a img {
            height: 24px;
            width: 24px;
        }

        .branch-card__actions a i {
            font-size: 22px;
        }
    }

        </style>

<?php include 'footer.php'; ?>
<script type="text/javascript" src="assets/js/sweatlert.js"></script>
