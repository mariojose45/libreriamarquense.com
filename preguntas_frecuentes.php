<?php
$current_page = basename($_SERVER['PHP_SELF']);

$paginas_servicios = [];

$seo_title = 'Preguntas frecuentes | Librería Marquense';

$seo_description = 'Resuelve tus dudas sobre compras, productos, listas escolares, métodos de pago, entregas, garantías y atención en Librería Marquense.';

$canonical_url = 'https://libreriamarquense.com/preguntas_frecuentes.php';

$seo_robots = 'index, follow, max-image-preview:large';

include 'head.php';
include 'assets/php/legal_page_layout.php';

$faqContacto =
    'Puedes escribirnos por WhatsApp al ' .
    htmlspecialchars($site_whatsapp_number, ENT_QUOTES, 'UTF-8') .
    ', llamarnos al ' .
    htmlspecialchars($site_phone_number, ENT_QUOTES, 'UTF-8') .
    ' o utilizar el formulario de contacto del sitio web.';
?>

<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>Preguntas Frecuentes</h2>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>Preguntas Frecuentes</li>
            </ul>
        </div>
    </div>
</div>

<?php
lm_open_legal_section(
    'Preguntas Frecuentes',
    'PREGUNTAS FRECUENTES - LIBRER&Iacute;A MARQUENSE',
    '',
    'Resolvemos aqu&iacute; las consultas m&aacute;s comunes sobre compras, productos, listas escolares, entregas, garant&iacute;as y atenci&oacute;n para que puedas realizar tu pedido con mayor confianza.',
    'bx-help-circle'
);

lm_render_legal_block('&iquest;Qu&eacute; es Librer&iacute;a Marquense?', [
    'Librer&iacute;a Marquense es una tienda especializada en &uacute;tiles escolares, papeler&iacute;a, libros, material did&aacute;ctico y art&iacute;culos de oficina, enfocada en brindar atenci&oacute;n cercana y productos pr&aacute;cticos para estudiantes, docentes, familias y empresas.'
]);

lm_render_legal_block('&iquest;Qu&eacute; tipo de productos venden?', [
    'Ofrecemos productos y soluciones como:'
], [
    '&Uacute;tiles escolares.',
    'Papeler&iacute;a general.',
    'Cuadernos, hojas, cartulinas y materiales para tareas.',
    'Lapiceros, l&aacute;pices, marcadores, crayones y productos de escritura.',
    'Libros, material did&aacute;ctico y art&iacute;culos de oficina, seg&uacute;n disponibilidad.'
]);

lm_render_legal_block('&iquest;Pueden preparar listas escolares completas?', [
    'S&iacute;. Podemos apoyar con la preparaci&oacute;n de listas escolares, paquetes o pedidos por cantidad, siempre sujeto a disponibilidad de inventario y confirmaci&oacute;n previa de marcas, presentaciones y cantidades.'
]);

lm_render_legal_block('&iquest;Los productos est&aacute;n disponibles de inmediato?', [
    'La disponibilidad puede variar seg&uacute;n temporada, demanda y presentaci&oacute;n del producto. Recomendamos confirmar existencias antes de cerrar compras grandes, listas escolares o pedidos especiales.'
]);

lm_render_legal_block('&iquest;C&oacute;mo puedo realizar una compra?', [
    'Puedes realizar tu compra desde el sitio web, por WhatsApp o coordinando directamente con nuestro equipo. Nuestro personal puede confirmar disponibilidad, precio y forma de entrega antes de finalizar el pedido.'
]);

lm_render_legal_block('&iquest;Cu&aacute;les son los m&eacute;todos de pago disponibles?', [
    'Aceptamos pago contra entrega, transferencias bancarias y pagos con tarjeta, seg&uacute;n disponibilidad y confirmaci&oacute;n del pedido.'
]);

lm_render_legal_block('&iquest;Realizan entregas o env&iacute;os?', [
    'S&iacute;. Podemos coordinar entregas o env&iacute;os seg&uacute;n ubicaci&oacute;n y disponibilidad. Los tiempos de entrega pueden variar por distancia, volumen del pedido y condiciones log&iacute;sticas.'
]);

lm_render_legal_block('&iquest;Los productos tienen garant&iacute;a o cambio?', [
    'S&iacute;. La garant&iacute;a o cambio aplica seg&uacute;n el tipo de producto y las condiciones establecidas. Puedes revisar el detalle en nuestra p&aacute;gina de Pol&iacute;ticas de Garant&iacute;a.'
]);

lm_render_legal_block('&iquest;C&oacute;mo puedo recibir asesor&iacute;a antes de comprar?', [
    'Nuestro equipo puede brindarte asesor&iacute;a para elegir &uacute;tiles, materiales, cantidades o presentaciones adecuadas seg&uacute;n tu lista escolar, tarea, oficina o necesidad espec&iacute;fica.'
]);

lm_render_legal_block('&iquest;C&oacute;mo puedo contactarlos?', [
    $faqContacto
]);

lm_render_legal_block('Importante', [
    'Para listas escolares, pedidos por cantidad o productos espec&iacute;ficos, recomendamos confirmar nombres, marcas, medidas, colores y cantidades antes de cerrar la compra.'
]);

lm_close_legal_section();
?>

<?php include 'footer.php'; ?>
