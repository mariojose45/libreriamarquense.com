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
ticell_open_legal_section(
    'T&eacute;rminos y Condiciones',
    'T&Eacute;RMINOS Y CONDICIONES DE USO - TI-CELL',
    '',
    'Bienvenido(a) a TI-CELL. Al acceder, navegar o realizar una compra en nuestro sitio web, usted acepta los presentes T&eacute;rminos y Condiciones, los cuales regulan el uso del sitio, los servicios ofrecidos y la compra de celulares, accesorios, repuestos y reparaciones.',
    'bx-receipt'
);

ticell_render_legal_block('1. Informaci&oacute;n General', [
    'TI-CELL es una tienda dedicada a la comercializaci&oacute;n de celulares, accesorios, repuestos y servicios de reparaci&oacute;n. Los contenidos publicados tienen fines informativos y comerciales. Nos reservamos el derecho de modificar productos, precios, promociones y condiciones sin previo aviso.'
]);

ticell_render_legal_block('2. Uso del Sitio Web', [
    'El usuario se compromete a utilizar el sitio de forma responsable, proporcionando informaci&oacute;n veraz y actualizada. Queda prohibido el uso fraudulento, malintencionado o ilegal del contenido del sitio, as&iacute; como su reproducci&oacute;n total o parcial sin autorizaci&oacute;n.'
]);

ticell_render_legal_block('3. Productos y Disponibilidad', [
    'Los productos ofrecidos en TI-CELL incluyen celulares, accesorios, repuestos y art&iacute;culos de tecnolog&iacute;a. La disponibilidad de inventario puede variar y algunas im&aacute;genes o descripciones son de referencia seg&uacute;n marca y modelo. TI-CELL no se responsabiliza por compras incompatibles cuando el cliente no haya confirmado previamente el modelo correcto del equipo.'
]);

ticell_render_legal_block('4. Reparaciones y Servicio T&eacute;cnico', [
    'Toda reparaci&oacute;n est&aacute; sujeta a diagn&oacute;stico previo y autorizaci&oacute;n del cliente antes de proceder, cuando aplique. El cliente debe informar la falla reportada con la mayor precisi&oacute;n posible y se recomienda realizar respaldo de informaci&oacute;n antes de entregar el equipo.'
]);

ticell_render_legal_block('5. Compras y Pagos', [
    'Las compras est&aacute;n sujetas a disponibilidad de inventario. Los m&eacute;todos de pago disponibles ser&aacute;n informados durante el proceso de compra o coordinaci&oacute;n del servicio. TI-CELL se reserva el derecho de cancelar pedidos con informaci&oacute;n incompleta o incorrecta.'
]);

ticell_render_legal_block('6. Env&iacute;os y Entregas', [
    'Los env&iacute;os se realizan a la direcci&oacute;n proporcionada por el cliente. Los tiempos de entrega son estimados y pueden variar seg&uacute;n ubicaci&oacute;n y condiciones log&iacute;sticas. TI-CELL no se hace responsable por retrasos atribuibles a terceros.'
]);

ticell_render_legal_block('7. Garant&iacute;as', [
    'Las garant&iacute;as aplican seg&uacute;n el tipo de producto o servicio y est&aacute;n sujetas a las condiciones publicadas en la p&aacute;gina de Pol&iacute;ticas de Garant&iacute;a. No toda falla posterior implica garant&iacute;a si existe evidencia de golpe, humedad, manipulaci&oacute;n de terceros o uso inadecuado.'
]);

ticell_render_legal_block('8. Responsabilidad del Cliente', [
    'El cliente es responsable de verificar marca, modelo y compatibilidad antes de comprar accesorios o repuestos. En reparaciones, el cliente es responsable de retirar cuentas, respaldar informaci&oacute;n importante y revisar el equipo al momento de la entrega.'
]);

ticell_render_legal_block('9. Privacidad y Datos Personales', [
    'La informaci&oacute;n personal proporcionada ser&aacute; utilizada &uacute;nicamente para procesar pedidos, brindar atenci&oacute;n al cliente y dar seguimiento relacionado con compras o servicios. TI-CELL no comparte datos personales con terceros, salvo obligaci&oacute;n legal o requerimiento operativo indispensable para completar la entrega o el servicio.'
]);

ticell_render_legal_block('10. Limitaci&oacute;n de Responsabilidad', [
    'TI-CELL no ser&aacute; responsable por da&ntilde;os derivados del uso inadecuado de productos, interpretaciones err&oacute;neas de la informaci&oacute;n publicada o inconvenientes t&eacute;cnicos del sitio.'
]);

ticell_render_legal_block('11. Modificaciones', [
    'Nos reservamos el derecho de modificar estos T&eacute;rminos y Condiciones en cualquier momento. Las modificaciones entrar&aacute;n en vigor a partir de su publicaci&oacute;n en el sitio web.'
]);

ticell_render_legal_block('12. Aceptaci&oacute;n', [
    'Al utilizar este sitio web o realizar una compra, el usuario declara haber le&iacute;do, comprendido y aceptado estos T&eacute;rminos y Condiciones en su totalidad.'
]);

ticell_close_legal_section();
?>

<?php include 'footer.php'; ?>
