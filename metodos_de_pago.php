<?php
include 'head.php';
include 'assets/php/legal_page_layout.php';

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="page-title-area">
    <div class="container">
        <div class="page-title-content">
            <h2>M&eacute;todos de Pago</h2>
            <ul>
                <li><a href="index.php">Inicio</a></li>
                <li>M&eacute;todos de Pago</li>
            </ul>
        </div>
    </div>
</div>

<?php
lm_open_legal_section(
    'M&eacute;todos de Pago',
    'M&Eacute;TODOS DE PAGO - LIBRER&Iacute;A MARQUENSE',
    '',
    'En Librer&iacute;a Marquense ofrecemos m&eacute;todos de pago seguros, claros y confiables para facilitar la compra de &uacute;tiles escolares, papeler&iacute;a, libros, material did&aacute;ctico y art&iacute;culos de oficina. Todos los pagos se realizan bajo confirmaci&oacute;n del pedido.',
    'bx-wallet'
);

lm_render_legal_block('1. Proceso de Compra', [], [
    'No se realiza ning&uacute;n cobro autom&aacute;tico sin confirmaci&oacute;n.',
    'El pedido queda en proceso de validaci&oacute;n.',
    'Nuestro equipo puede comunicarse para confirmar disponibilidad, cantidades, presentaciones y detalles del pedido.'
]);

lm_render_legal_block('2. Pago Contra Entrega', [
    'Disponible para compras dentro del territorio nacional.'
], [
    'El pago se realiza al momento de recibir el producto.',
    'Aplica &uacute;nicamente para pedidos confirmados.',
    'El cliente debe contar con el monto exacto al momento de la entrega.'
]);

lm_render_legal_block('3. Transferencia Bancaria', [
    'Disponible previa coordinaci&oacute;n.'
], [
    'Los datos bancarios ser&aacute;n proporcionados &uacute;nicamente por nuestros canales oficiales.',
    'El pedido ser&aacute; procesado una vez confirmado el pago.'
]);

lm_render_legal_block('4. Pago con Tarjeta', [
    'Aceptamos pagos con tarjeta de cr&eacute;dito o d&eacute;bito seg&uacute;n disponibilidad.'
], [
    'Esta opci&oacute;n puede requerir confirmaci&oacute;n previa.',
    'Las condiciones ser&aacute;n informadas antes de finalizar la compra.'
]);

lm_render_legal_block('5. Condiciones Generales', [], [
    'Librer&iacute;a Marquense no solicita pagos sin confirmaci&oacute;n previa.',
    'Los precios publicados pueden estar sujetos a cambios sin previo aviso.',
    'Nos reservamos el derecho de cancelar pedidos con informaci&oacute;n incompleta o incorrecta.',
    'Los pagos realizados no son reembolsables, salvo error comprobado por parte de Librer&iacute;a Marquense o acuerdo comercial aplicable.'
]);

lm_render_legal_block('6. Aceptaci&oacute;n', [
    'Al realizar una compra en Librer&iacute;a Marquense, el cliente declara haber le&iacute;do, comprendido y aceptado estos M&eacute;todos de Pago.'
]);

lm_close_legal_section();
?>

<?php include 'footer.php'; ?>
