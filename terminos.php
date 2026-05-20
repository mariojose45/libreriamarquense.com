<?php
include 'head.php';
include 'assets/php/legal_page_layout.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>T&eacute;rminos y Condiciones</h2>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>T&eacute;rminos y Condiciones</li>
            </ul>
        </div>
    </div>
</div>

<?php
lm_open_legal_section(
    'T&eacute;rminos y Condiciones',
    'T&Eacute;RMINOS Y CONDICIONES DE USO - LIBRER&Iacute;A MARQUENSE',
    '',
    'Bienvenido(a) a Librer&iacute;a Marquense. Al acceder, navegar o realizar una compra en nuestro sitio web, usted acepta los presentes T&eacute;rminos y Condiciones, los cuales regulan el uso del sitio y la compra de &uacute;tiles escolares, papeler&iacute;a, libros, material did&aacute;ctico y productos relacionados.',
    'bx-receipt'
);

lm_render_legal_block('1. Informaci&oacute;n General', [
    'Librer&iacute;a Marquense es una tienda dedicada a la comercializaci&oacute;n de &uacute;tiles escolares, papeler&iacute;a, libros, material did&aacute;ctico, art&iacute;culos de oficina y productos complementarios. Los contenidos publicados tienen fines informativos y comerciales. Nos reservamos el derecho de modificar productos, precios, promociones y condiciones sin previo aviso.'
]);

lm_render_legal_block('2. Uso del Sitio Web', [
    'El usuario se compromete a utilizar el sitio de forma responsable, proporcionando informaci&oacute;n veraz y actualizada. Queda prohibido el uso fraudulento, malintencionado o ilegal del contenido del sitio, as&iacute; como su reproducci&oacute;n total o parcial sin autorizaci&oacute;n.'
]);

lm_render_legal_block('3. Productos y Disponibilidad', [
    'Los productos ofrecidos por Librer&iacute;a Marquense incluyen &uacute;tiles escolares, cuadernos, papel, cartulinas, lapiceros, l&aacute;pices, materiales para manualidades, libros y art&iacute;culos de oficina. La disponibilidad de inventario puede variar y algunas im&aacute;genes o descripciones son de referencia seg&uacute;n marca, presentaci&oacute;n, color o tama&ntilde;o.'
]);

lm_render_legal_block('4. Listas Escolares y Pedidos Especiales', [
    'La preparaci&oacute;n de listas escolares, paquetes o pedidos especiales est&aacute; sujeta a disponibilidad de inventario y confirmaci&oacute;n previa. El cliente debe revisar grado, cantidades, marcas solicitadas y referencias antes de aprobar la compra.'
]);

lm_render_legal_block('5. Compras y Pagos', [
    'Las compras est&aacute;n sujetas a disponibilidad de inventario. Los m&eacute;todos de pago disponibles ser&aacute;n informados durante el proceso de compra o coordinaci&oacute;n del pedido. Librer&iacute;a Marquense se reserva el derecho de cancelar pedidos con informaci&oacute;n incompleta, incorrecta o no confirmada.'
]);

lm_render_legal_block('6. Entregas y Retiro de Pedidos', [
    'Las entregas se realizan a la direcci&oacute;n proporcionada por el cliente o por medio del retiro acordado, seg&uacute;n disponibilidad. Los tiempos de entrega son estimados y pueden variar por ubicaci&oacute;n, volumen del pedido y condiciones log&iacute;sticas. Librer&iacute;a Marquense no se hace responsable por retrasos atribuibles a terceros.'
]);

lm_render_legal_block('7. Cambios, Garant&iacute;as y Reclamos', [
    'Los cambios o reclamos aplican seg&uacute;n el tipo de producto y las condiciones publicadas en la p&aacute;gina de Pol&iacute;ticas de Garant&iacute;a. No aplican cambios por uso inadecuado, da&ntilde;o posterior a la entrega, humedad, manchas, desgaste normal o alteraci&oacute;n del producto.'
]);

lm_render_legal_block('8. Responsabilidad del Cliente', [
    'El cliente es responsable de verificar productos, cantidades, medidas, colores, marcas, grado escolar y compatibilidad con su lista o necesidad antes de confirmar la compra. Tambi&eacute;n debe revisar el pedido al momento de la entrega o retiro.'
]);

lm_render_legal_block('9. Privacidad y Datos Personales', [
    'La informaci&oacute;n personal proporcionada ser&aacute; utilizada &uacute;nicamente para procesar pedidos, brindar atenci&oacute;n al cliente y dar seguimiento relacionado con compras, entregas o consultas. Librer&iacute;a Marquense no comparte datos personales con terceros, salvo obligaci&oacute;n legal o requerimiento operativo indispensable para completar la entrega.'
]);

lm_render_legal_block('10. Limitaci&oacute;n de Responsabilidad', [
    'Librer&iacute;a Marquense no ser&aacute; responsable por da&ntilde;os derivados del uso inadecuado de productos, interpretaciones err&oacute;neas de la informaci&oacute;n publicada, retrasos externos o inconvenientes t&eacute;cnicos del sitio.'
]);

lm_render_legal_block('11. Modificaciones', [
    'Nos reservamos el derecho de modificar estos T&eacute;rminos y Condiciones en cualquier momento. Las modificaciones entrar&aacute;n en vigor a partir de su publicaci&oacute;n en el sitio web.'
]);

lm_render_legal_block('12. Aceptaci&oacute;n', [
    'Al utilizar este sitio web o realizar una compra, el usuario declara haber le&iacute;do, comprendido y aceptado estos T&eacute;rminos y Condiciones en su totalidad.'
]);

lm_close_legal_section();
?>

<?php include 'footer.php'; ?>
