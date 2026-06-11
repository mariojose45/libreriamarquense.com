<?php

require dirname(__DIR__) . '/integrations/cybersource/Autoload.php';

use LM\CyberSource\Config;
use LM\CyberSource\GatewayException;
use LM\CyberSource\OrderPayloadValidator;

function lm_test_assert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function lm_test_payload($deliveryType, $deliveryPlace, $shipping, $paymentMethod = 'Transferencia')
{
    $subtotal = 100.00;

    return array(
        'nit' => 'C/F',
        'nombre_cliente' => 'Cliente Prueba',
        'telefono_cliente' => '55555555',
        'direccion_cliente' => 'Direccion de prueba 123',
        'correo_cliente' => 'cliente@example.com',
        'tipo_documento_cliente' => 'NIT',
        'forma_pago' => $paymentMethod,
        'total_venta' => $subtotal + $shipping,
        'total_ventades' => $subtotal,
        'envio' => $shipping,
        'modalidad_entrega' => $deliveryType,
        'lugar_entrega' => $deliveryPlace,
        'comentario_cotizacion' => 'Dato del cliente que no debe controlar la modalidad',
        'datosArticulos' => array(
            'articulos' => array(
                'idarticulo' => array(10),
                'cantidad' => array(2),
                'precio_venta' => array(50),
                'subtotal1' => array(100),
                'subtotaldes1' => array(100),
                'presen' => array('UNIDAD'),
            ),
        ),
    );
}

$config = Config::fromFile(dirname(__DIR__) . '/config/cybersource.php');
$validator = new OrderPayloadValidator($config);

$pickup = $validator->validateForOrder(
    lm_test_payload('store_pickup', 'Recoge en tienda', 0.00),
    array('Transferencia')
);

lm_test_assert($pickup['envio'] === 0.0, 'El retiro debe conservar envio Q0.00.');
lm_test_assert(
    $pickup['direccion_cliente'] === '8A Avenida 19-55, Ciudad de Guatemala 01001',
    'El retiro debe usar la direccion configurada de la tienda.'
);
lm_test_assert(
    strpos($pickup['comentario_cotizacion'], 'Recoge en tienda') !== false,
    'El comentario debe identificar el retiro en tienda.'
);
lm_test_assert(!isset($pickup['modalidad_entrega'], $pickup['lugar_entrega']), 'Los campos internos no deben llegar a la API externa.');
lm_test_assert(isset($pickup['datosArticulos']['articulos']['presen']), 'El validador debe conservar campos existentes del contrato de articulos.');

$delivery = $validator->validateForOrder(
    lm_test_payload('shipping', 'Ciudad de Guatemala, Zona 1', 25.00),
    array('Transferencia')
);

lm_test_assert($delivery['envio'] === 25.0, 'El envio debe usar la tarifa configurada.');
lm_test_assert(
    strpos($delivery['comentario_cotizacion'], 'Ciudad de Guatemala, Zona 1') !== false,
    'El comentario debe identificar el lugar de entrega.'
);

$rejected = false;
try {
    $validator->validateForOrder(
        lm_test_payload('shipping', 'Ciudad de Guatemala, Zona 1', 0.00),
        array('Transferencia')
    );
} catch (GatewayException $e) {
    $rejected = strpos($e->getMessage(), 'costo de envio') !== false;
}

lm_test_assert($rejected, 'Un envio a domicilio no debe aceptar costo Q0.00.');

echo "OrderPayloadValidatorTest: OK\n";
