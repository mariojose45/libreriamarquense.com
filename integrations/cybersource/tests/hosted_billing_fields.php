<?php

// Pure field/validation checks: no checkout, callback, storage, HTTP or mail execution.
require __DIR__ . '/fixtures.php';

use LM\CyberSource\Config;
use LM\CyberSource\OrderPayloadValidator;
use LM\CyberSource\SecureAcceptanceService;

$checks = 0;
$check = function ($condition, $label) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks++;
};

$data = array(
    'environment' => 'test', 'operation' => 'sale', 'currency' => 'GTQ',
    'execution' => array('production_authorized' => false),
    'site' => array('base_url' => 'https://example.invalid', 'return_path' => '/return'),
    'secure_acceptance' => array('enabled' => true, 'profile_id' => 'fixture-profile',
        'access_key' => 'fixture-access-not-a-credential', 'secret_key' => 'fixture-secret-not-a-credential'),
    'delivery' => array(
        'pickup' => array('type' => 'store_pickup', 'value' => 'store', 'address' => 'Test store', 'shipping' => 0),
        'shipping_groups' => array('Test city' => array('Test destination' => 5)),
    ),
);
$config = new Config($data);
$validator = new OrderPayloadValidator($config);
$service = new SecureAcceptanceService($config);
$addressFields = array('bill_to_address_line1', 'bill_to_address_city', 'bill_to_address_state',
    'bill_to_address_country', 'bill_to_address_postal_code');

foreach (array('Tarjeta', 'Transferencia', 'Pago Contra Entrega') as $method) {
    foreach (array('store_pickup', 'shipping') as $delivery) {
        $input = fixtureOrder();
        $input['forma_pago'] = $method;
        $expectedAddress = 'Test store';
        $expectedComment = 'Tienda en linea | Entrega: Recoge en tienda | Sucursal: Test store';
        if ($delivery === 'shipping') {
            $input['modalidad_entrega'] = 'shipping';
            $input['lugar_entrega'] = 'Test destination';
            $input['envio'] = 5;
            $input['total_venta'] = 35;
            $expectedAddress = $input['direccion_cliente'];
            $expectedComment = 'Tienda en linea | Entrega a domicilio | Lugar: Test destination';
        }
        $order = $validator->validateForOrder($input);
        $check($order['direccion_cliente'] === $expectedAddress, $method . ': ERP delivery address');
        $check($order['comentario_cotizacion'] === $expectedComment, $method . ': ERP delivery comment');
        $check($order['forma_pago'] === $method && $order['no_auto_tarjeta'] === '', $method . ': payment fields');
        $check($order['total_venta'] === (float) $input['total_venta'], $method . ': ERP total');
        $check($order['datosArticulos']['articulos']['idarticulo'] === array(1), $method . ': ERP items');
        $check(!array_key_exists('modalidad_entrega', $order) && !array_key_exists('lugar_entrega', $order), $method . ': ERP contract');

        if ($method !== 'Tarjeta') {
            continue;
        }
        $session = array('reference' => 'LMQ-20261124000000-00000000', 'amount' => $order['total_venta'],
            'currency' => 'GTQ', 'order_payload' => $order, 'device_fingerprint_id' => 'fixture-device',
            'secure_acceptance_request' => array('transaction_uuid' => str_repeat('a', 32),
                'signed_date_time' => '2026-11-24T00:00:00Z'));
        $original = json_encode($session);
        $fields = $service->buildSignedFormFields($session);
        $check(json_encode($session) === $original, 'field generation leaves ERP payload unchanged');
        $check($fields['locale'] === 'es-es', 'documented default locale');
        $check($fields['transaction_type'] === 'sale' && $fields['currency'] === 'GTQ', 'operation and currency');
        $check($fields['bill_to_forename'] === 'Cliente' && $fields['bill_to_surname'] === 'Prueba', 'known customer name');
        $names = explode(',', $fields['signed_field_names']);
        $check($fields['unsigned_field_names'] === '', 'no unsigned merchant fields');
        foreach ($addressFields as $name) {
            $check(!array_key_exists($name, $fields) && !in_array($name, $names, true), 'hosted collection: ' . $name);
        }
        $signed = array();
        foreach ($names as $name) {
            $check(array_key_exists($name, $fields), 'signed field exists: ' . $name);
            $signed[] = $name . '=' . $fields[$name];
        }
        $expected = base64_encode(hash_hmac('sha256', implode(',', $signed),
            $config->get('secure_acceptance.secret_key'), true));
        $check(hash_equals($expected, $fields['signature']), 'HMAC-SHA256 unchanged');
        $check(count($names) === count($fields) - 1 && count(array_unique($names)) === count($names), 'all merchant fields signed once');
    }
}

$data['secure_acceptance']['locale'] = 'es-es';
$explicit = (new SecureAcceptanceService(new Config($data)))->buildSignedFormFields($session);
$check($explicit['locale'] === 'es-es', 'explicit locale');
$check($config->get('execution.production_authorized') === false, 'production remains unauthorized');
echo 'PASS: ' . $checks . " pure field/validation assertions; no commercial operations.\n";
