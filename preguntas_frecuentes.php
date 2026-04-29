<?php
include 'head.php';
include 'assets/php/legal_page_layout.php';

$current_page = basename($_SERVER['PHP_SELF']);
$paginas_servicios = [];

$faqContacto = 'Puedes escribirnos por WhatsApp al ' . htmlspecialchars($site_whatsapp_number) . ', llamarnos al ' . htmlspecialchars($site_phone_number) . ' o utilizar el formulario de contacto del sitio web.';
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
ticell_open_legal_section(
    'Preguntas Frecuentes',
    'PREGUNTAS FRECUENTES - TI-CELL',
    '',
    'Resolvemos aqu&iacute; las consultas m&aacute;s comunes sobre compra, reparaci&oacute;n, garant&iacute;as y atenci&oacute;n para que puedas comprar o solicitar servicio con mayor confianza.',
    'bx-help-circle'
);

ticell_render_legal_block('&iquest;Qu&eacute; es TI-CELL?', [
    'TI-CELL es una tienda especializada en venta de celulares, accesorios, repuestos y servicios de reparaci&oacute;n, enfocada en brindar atenci&oacute;n confiable y soluciones pr&aacute;cticas para tu equipo.'
]);

ticell_render_legal_block('&iquest;Qu&eacute; tipo de productos venden?', [
    'Ofrecemos productos y soluciones como:'
], [
    'Celulares.',
    'Accesorios para celular.',
    'Repuestos.',
    'Equipos y art&iacute;culos de tecnolog&iacute;a.',
    'Servicios de reparaci&oacute;n y mantenimiento.'
]);

ticell_render_legal_block('&iquest;Tambi&eacute;n realizan reparaciones?', [
    'S&iacute;. Atendemos diagn&oacute;stico y reparaci&oacute;n de celulares, sujeto a revisi&oacute;n t&eacute;cnica y disponibilidad de repuestos.'
]);

ticell_render_legal_block('&iquest;Los productos son originales?', [
    'Trabajamos con productos seleccionados y proveedores confiables. La disponibilidad y caracter&iacute;sticas pueden variar seg&uacute;n la marca y el modelo.'
]);

ticell_render_legal_block('&iquest;C&oacute;mo puedo realizar una compra?', [
    'Puedes realizar tu compra desde el sitio web, por WhatsApp o coordinando directamente con nuestro equipo.'
]);

ticell_render_legal_block('&iquest;Cu&aacute;les son los m&eacute;todos de pago disponibles?', [
    'Aceptamos pago contra entrega, transferencias bancarias y pagos con tarjeta, seg&uacute;n disponibilidad y confirmaci&oacute;n del pedido o servicio.'
]);

ticell_render_legal_block('&iquest;Realizan env&iacute;os?', [
    'S&iacute;. Realizamos env&iacute;os a nivel nacional. Los tiempos de entrega pueden variar seg&uacute;n la ubicaci&oacute;n y las condiciones log&iacute;sticas.'
]);

ticell_render_legal_block('&iquest;Los productos o reparaciones tienen garant&iacute;a?', [
    'S&iacute;. La garant&iacute;a aplica seg&uacute;n el tipo de producto o servicio realizado. Puedes revisar el detalle en nuestra p&aacute;gina de Pol&iacute;ticas de Garant&iacute;a.'
]);

ticell_render_legal_block('&iquest;C&oacute;mo puedo recibir asesor&iacute;a antes de comprar?', [
    'Nuestro equipo puede brindarte asesor&iacute;a personalizada para elegir el celular, accesorio o servicio adecuado para tu necesidad.'
]);

ticell_render_legal_block('&iquest;C&oacute;mo puedo contactarlos?', [
    $faqContacto
]);

ticell_render_legal_block('Importante', [
    'Para reparaciones y compatibilidad de accesorios, recomendamos confirmar marca, modelo y detalle de la falla antes de cerrar la compra o el servicio.'
]);

ticell_close_legal_section();
?>

<?php include 'footer.php'; ?>
