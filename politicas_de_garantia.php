<?php
$current_page = basename($_SERVER['PHP_SELF']);

$seo_title = 'Políticas de garantía | Librería Marquense';

$seo_description = 'Consulta las políticas de garantía de Librería Marquense para productos, pedidos, cambios, daños, defectos de fábrica y condiciones aplicables.';

$canonical_url = 'https://libreriamarquense.com/politicas_de_garantia.php';

$seo_robots = 'index, follow, max-image-preview:large';

include 'head.php';
include 'assets/php/legal_page_layout.php';
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
lm_open_legal_section(
    'Pol&iacute;ticas de Garant&iacute;a',
    'POL&Iacute;TICAS DE GARANT&Iacute;A - LIBRER&Iacute;A MARQUENSE',
    '',
    'En Librer&iacute;a Marquense nuestro compromiso es ofrecer &uacute;tiles escolares, papeler&iacute;a, libros, material did&aacute;ctico y art&iacute;culos de oficina en buen estado, con una experiencia de compra clara, segura y confiable. Por ello, establecemos las siguientes Pol&iacute;ticas de Garant&iacute;a aplicables a los productos comercializados en nuestro sitio web y tienda.',
    'bx-shield-quarter'
);

lm_render_legal_block('1. Alcance de la Garant&iacute;a', [], [
    'La entrega del producto en buen estado.',
    'Que el producto corresponda a lo solicitado.',
    'Productos con defectos de f&aacute;brica.',
    'Paquetes, listas escolares o pedidos preparados conforme a la informaci&oacute;n confirmada por el cliente.'
]);

lm_render_legal_block('2. Productos Cubiertos', [
    'La garant&iacute;a aplica cuando el producto presenta defecto de f&aacute;brica, da&ntilde;o visible al momento de la entrega o error comprobado en el pedido. Para listas escolares o paquetes, la revisi&oacute;n se realiza contra la informaci&oacute;n confirmada por el cliente.'
]);

lm_render_legal_block('3. Casos Cubiertos por Garant&iacute;a', [], [
    'Producto da&ntilde;ado durante el env&iacute;o.',
    'Error en el producto entregado.',
    'Producto con defecto de f&aacute;brica.',
    'Faltante comprobado en un paquete o lista escolar previamente confirmada.'
]);

lm_render_legal_block('4. Casos NO Cubiertos por Garant&iacute;a', [], [
    'Productos usados, manchados, rotos, rayados o da&ntilde;ados por mal uso.',
    'Da&ntilde;os por humedad, calor, almacenamiento inadecuado o manipulaci&oacute;n posterior a la entrega.',
    'Empaques abiertos o alterados cuando el producto requiere empaque sellado para cambio.',
    'Desgaste normal, da&ntilde;o est&eacute;tico o preferencia personal.',
    'Productos personalizados, de pedido especial o cortados a medida, salvo defecto comprobado.'
]);

lm_render_legal_block('5. Revisi&oacute;n, Cambios y Reposici&oacute;n', [
    'Los cambios o revisiones se realizar&aacute;n cuando el producto presente da&ntilde;os visibles al momento de la entrega, exista un error en el pedido o se detecte una falla cubierta por la garant&iacute;a aplicable.',
    'Todo cambio est&aacute; sujeto a revisi&oacute;n, disponibilidad de inventario y presentaci&oacute;n del comprobante de compra. No se aceptan devoluciones por cambio de opini&oacute;n, salvo acuerdo expreso o error comprobado por parte de Librer&iacute;a Marquense.'
]);

lm_render_legal_block('6. Responsabilidad del Cliente', [], [
    'Revisar el producto al momento de recibirlo o retirarlo.',
    'Verificar cantidades, marcas, colores, medidas, grado escolar y especificaciones antes de confirmar la compra.',
    'Conservar el comprobante de compra y reportar cualquier inconveniente con claridad y evidencia cuando corresponda.'
]);

lm_render_legal_block('7. Limitaci&oacute;n de Responsabilidad', [], [
    'Uso indebido del producto.',
    'Da&ntilde;os ocasionados despu&eacute;s de la entrega.',
    'P&eacute;rdidas, deterioro o faltantes reportados despu&eacute;s de haber recibido el pedido conforme.',
    'Incumplimiento de recomendaciones de uso, almacenamiento o cuidado del producto.'
]);

lm_render_legal_block('8. Modificaciones', [
    'Librer&iacute;a Marquense se reserva el derecho de modificar estas Pol&iacute;ticas de Garant&iacute;a en cualquier momento. Las modificaciones entrar&aacute;n en vigor a partir de su publicaci&oacute;n en el sitio web.'
]);

lm_render_legal_block('9. Aceptaci&oacute;n', [
    'Al realizar una compra en Librer&iacute;a Marquense, el cliente declara haber le&iacute;do, comprendido y aceptado estas Pol&iacute;ticas de Garant&iacute;a en su totalidad.'
]);

lm_close_legal_section();
?>

<?php include 'footer.php'; ?>
