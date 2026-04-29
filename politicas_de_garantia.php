<?php
include 'head.php';
include 'assets/php/legal_page_layout.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>Pol&iacute;ticas de Garant&iacute;a</h2>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>Pol&iacute;ticas de Garant&iacute;a</li>
            </ul>
        </div>
    </div>
</div>

<?php
ticell_open_legal_section(
    'Pol&iacute;ticas de Garant&iacute;a',
    'POL&Iacute;TICAS DE GARANT&Iacute;A - TI-CELL',
    '',
    'En TI-CELL nuestro compromiso es ofrecer celulares, accesorios, repuestos y servicios de reparaci&oacute;n con una experiencia de compra clara, segura y confiable. Por ello, establecemos las siguientes Pol&iacute;ticas de Garant&iacute;a aplicables a los productos y servicios comercializados en nuestro sitio web y sucursales.',
    'bx-shield-quarter'
);

ticell_render_legal_block('1. Alcance de la Garant&iacute;a', [], [
    'La entrega del producto en buen estado.',
    'Que el producto corresponda a lo solicitado.',
    'Productos con defectos de f&aacute;brica.',
    'Reparaciones realizadas por TI-CELL, limitadas al servicio efectuado y a los repuestos instalados cuando corresponda.'
]);

ticell_render_legal_block('2. Alcance para Reparaciones', [
    'Las reparaciones est&aacute;n sujetas a diagn&oacute;stico t&eacute;cnico previo. La garant&iacute;a cubre la falla atendida y no incluye da&ntilde;os diferentes o fallas no relacionadas con la intervenci&oacute;n realizada. Se recomienda realizar respaldo de informaci&oacute;n antes de entregar el equipo.'
]);

ticell_render_legal_block('3. Casos Cubiertos por Garant&iacute;a', [], [
    'Producto da&ntilde;ado durante el env&iacute;o.',
    'Error en el producto entregado.',
    'Producto con defecto de f&aacute;brica.',
    'Falla relacionada directamente con la reparaci&oacute;n realizada por TI-CELL.'
]);

ticell_render_legal_block('4. Casos NO Cubiertos por Garant&iacute;a', [], [
    'Golpes, humedad, sulfataci&oacute;n o da&ntilde;os por mal uso.',
    'Da&ntilde;os ocasionados por cargadores, energ&iacute;a inestable o accesorios incompatibles.',
    'Equipos abiertos, alterados o manipulados por terceros.',
    'Desgaste normal, da&ntilde;o est&eacute;tico o preferencia personal.',
    'Fallas de software, bloqueos por cuentas o informaci&oacute;n perdida no relacionadas con el servicio realizado.'
]);

ticell_render_legal_block('5. Revisi&oacute;n, Cambios y Reposici&oacute;n', [
    'Los cambios o revisiones &uacute;nicamente se realizar&aacute;n cuando el producto presente da&ntilde;os visibles al momento de la entrega, exista un error en el pedido o se detecte una falla relacionada con la garant&iacute;a aplicable.',
    'No se aceptan devoluciones por cambio de opini&oacute;n. No se realizan devoluciones de dinero una vez entregado el producto o finalizado el servicio, salvo acuerdo expreso o error comprobado por parte de TI-CELL.'
]);

ticell_render_legal_block('6. Responsabilidad del Cliente', [], [
    'Revisar el producto o equipo al momento de recibirlo.',
    'Verificar marca, modelo y compatibilidad antes de comprar accesorios o repuestos.',
    'Informar con claridad la falla reportada y conservar su comprobante de compra o servicio.'
]);

ticell_render_legal_block('7. Limitaci&oacute;n de Responsabilidad', [], [
    'Uso indebido del producto o del equipo reparado.',
    'Da&ntilde;os ocasionados despu&eacute;s de la entrega.',
    'Fallas provocadas por terceros o por incumplimiento de recomendaciones t&eacute;cnicas.'
]);

ticell_render_legal_block('8. Modificaciones', [
    'TI-CELL se reserva el derecho de modificar estas Pol&iacute;ticas de Garant&iacute;a en cualquier momento. Las modificaciones entrar&aacute;n en vigor a partir de su publicaci&oacute;n en el sitio web.'
]);

ticell_render_legal_block('9. Aceptaci&oacute;n', [
    'Al realizar una compra o contratar un servicio en TI-CELL, el cliente declara haber le&iacute;do, comprendido y aceptado estas Pol&iacute;ticas de Garant&iacute;a en su totalidad.'
]);

ticell_close_legal_section();
?>

<?php include 'footer.php'; ?>
