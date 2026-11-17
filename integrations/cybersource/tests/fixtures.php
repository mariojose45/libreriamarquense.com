<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/Autoload.php';

error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

use LM\CyberSource\Config;
use LM\CyberSource\CheckoutCoordinator;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\SecureAcceptanceService;

function fixtureConfig($dir)
{
    if (!preg_match('/^lm-cybersource-security-[a-f0-9]{16}$/D', basename($dir))
        || !is_file($dir . '/fixture-only')) {
        throw new RuntimeException('Only isolated synthetic test directories are allowed.');
    }
    return new Config(array(
        'enabled' => true, 'environment' => 'test', 'operation' => 'sale', 'currency' => 'GTQ',
        'site' => array('base_url' => 'https://localhost:12345', 'return_path' => '/return'),
        'secure_acceptance' => array('enabled' => true, 'profile_id' => 'fixture-profile',
            'access_key' => 'fixture-access-not-a-credential', 'secret_key' => 'fixture-secret-not-a-credential',
            'test_endpoint' => 'https://localhost:12345/mock/pay'),
        'external_order_api' => array('url' => 'https://localhost:12345/mock/orders'),
        'isolation' => array('enabled' => true, 'root' => $dir),
        'order_email' => array('enabled' => true, 'mode' => 'capture'),
        'delivery' => array('pickup' => array('type' => 'store_pickup', 'value' => 'store', 'address' => 'Test store', 'shipping' => 0)),
        'paths' => array('session_dir' => $dir, 'log_file' => $dir . '/test.log'),
    ));
}

function fixtureOrder()
{
    return array('nit' => 'C/F', 'nombre_cliente' => 'Cliente Prueba', 'telefono_cliente' => '55555555',
        'direccion_cliente' => 'Test address', 'correo_cliente' => 'test@example.invalid',
        'tipo_documento_cliente' => 'NIT', 'forma_pago' => 'Tarjeta', 'total_venta' => 30,
        'total_ventades' => 30, 'envio' => 0, 'modalidad_entrega' => 'store_pickup', 'lugar_entrega' => 'store',
        'no_auto_tarjeta' => 'BROWSER-FORGED', 'datosArticulos' => array('articulos' => array(
            'idarticulo' => array(1), 'cantidad' => array(2), 'precio_venta' => array(15),
            'subtotal1' => array(30), 'subtotaldes1' => array(30), 'presen' => array('UNIDAD'),
            'tipo_presentacion' => array('unidad'))));
}

function fixtureSession(Config $config, PaymentSessionStore $store)
{
    $session = $store->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ',
        'catalog_verified' => true, 'order_payload' => fixtureOrder(), 'expires_at' => gmdate('c', time() + 3600)));
    $coordinator = new CheckoutCoordinator($config, $store);
    $coordinator->prepare($session['reference']);
    return $store->find($session['reference']);
}

function fixtureSign(array $params, Config $config)
{
    $parts = array();
    foreach (explode(',', $params['signed_field_names']) as $name) {
        $parts[] = $name . '=' . (isset($params[$name]) ? $params[$name] : '');
    }
    $params['signature'] = base64_encode(hash_hmac('sha256', implode(',', $parts), $config->get('secure_acceptance.secret_key'), true));
    return $params;
}

function fixtureResponse(array $session, Config $config, array $overrides = array())
{
    $params = array('decision' => 'ACCEPT', 'reason_code' => '100',
        'req_reference_number' => $session['reference'], 'req_profile_id' => 'fixture-profile',
        'req_transaction_uuid' => $session['secure_acceptance_request']['transaction_uuid'],
        'req_transaction_type' => 'sale', 'req_access_key' => $config->get('secure_acceptance.access_key'),
        'req_amount' => '30.00', 'req_currency' => 'GTQ', 'transaction_id' => 'txn-' . $session['reference'],
        'auth_amount' => '30.00', 'auth_code' => '123456', 'signed_date_time' => gmdate('Y-m-d\TH:i:s\Z'));
    $params = array_replace($params, $overrides);
    if (in_array($params['decision'], array('DECLINE', 'CANCEL'), true)) {
        foreach (array('auth_amount', 'auth_code') as $field) {
            if (!array_key_exists($field, $overrides)) {
                unset($params[$field]);
            }
        }
    }
    $params['signed_field_names'] = implode(',', array_merge(array_keys($params), array('signed_field_names')));
    return fixtureSign($params, $config);
}
