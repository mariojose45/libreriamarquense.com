<?php

require_once dirname(__DIR__, 2) . '/integrations/cybersource/Autoload.php';

use LM\CyberSource\Config;
use LM\CyberSource\GatewayException;
use LM\CyberSource\HostedCheckoutService;
use LM\CyberSource\OrderForwarder;
use LM\CyberSource\OrderPayloadValidator;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\SecureAcceptanceService;
use LM\CyberSource\SecureLogger;
use LM\CyberSource\CyberSourceClient;
use LM\CyberSource\CyberSourcePaymentService;

$config = Config::fromFile(dirname(__DIR__, 2) . '/config/cybersource.php');
$logger = new SecureLogger($config->get('paths.log_file'));
$store = new PaymentSessionStore($config->get('paths.session_dir'));

function lm_json_response(array $payload, $statusCode = 200)
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function lm_read_json_body()
{
    $raw = file_get_contents('php://input');
    $data = json_decode((string) $raw, true);

    if (!is_array($data)) {
        throw new GatewayException('La solicitud debe enviarse en formato JSON.');
    }

    return $data;
}

function lm_request_header($name)
{
    $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    if (isset($_SERVER[$serverKey])) {
        return $_SERVER[$serverKey];
    }

    if (function_exists('getallheaders')) {
        foreach (getallheaders() as $header => $value) {
            if (strtolower($header) === strtolower($name)) {
                return $value;
            }
        }
    }

    return '';
}

function lm_require_post()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        lm_json_response(array('success' => false, 'message' => 'Metodo no permitido.'), 405);
    }
}

function lm_require_admin_token(Config $config)
{
    $expected = (string) $config->get('security.admin_token', '');
    $received = (string) lm_request_header('X-Internal-Token');

    if ($expected === '' || !hash_equals($expected, $received)) {
        lm_json_response(array('success' => false, 'message' => 'No autorizado.'), 403);
    }
}

function lm_payment_service(Config $config, SecureLogger $logger)
{
    return new CyberSourcePaymentService(new CyberSourceClient($config, $logger), $config);
}

function lm_payment_authorization_number_from_session(array $session)
{
    foreach (array('provider_authorization_number', 'provider_transaction_id') as $key) {
        if (isset($session[$key]) && trim((string) $session[$key]) !== '') {
            return trim((string) $session[$key]);
        }
    }

    foreach (array('provider_return', 'provider_webhook') as $containerKey) {
        if (!isset($session[$containerKey]) || !is_array($session[$containerKey])) {
            continue;
        }

        $container = $session[$containerKey];
        $payload = array();

        if (isset($container['params']) && is_array($container['params'])) {
            $payload = $container['params'];
        } elseif (isset($container['payload']) && is_array($container['payload'])) {
            $payload = $container['payload'];
        }

        foreach (array('auth_trans_ref_no', 'auth_code', 'authorization_code', 'transaction_id', 'request_token', 'id') as $field) {
            if (isset($payload[$field]) && trim((string) $payload[$field]) !== '') {
                return trim((string) $payload[$field]);
            }
        }
    }

    return '';
}

function lm_normalize_money($value)
{
    return (float) number_format(round((float) $value, 2), 2, '.', '');
}

function lm_shipping_amount_from_order_payload(array $orderPayload)
{
    if (array_key_exists('envio', $orderPayload) && trim((string) $orderPayload['envio']) !== '') {
        return lm_normalize_money($orderPayload['envio']);
    }

    $total = isset($orderPayload['total_venta']) ? (float) $orderPayload['total_venta'] : 0;
    $subtotal = isset($orderPayload['total_ventades']) ? (float) $orderPayload['total_ventades'] : 0;

    return lm_normalize_money(max(0, $total - $subtotal));
}

function lm_external_order_payload(array $session)
{
    $orderPayload = isset($session['order_payload']) && is_array($session['order_payload'])
        ? $session['order_payload']
        : array();

    $orderPayload['envio'] = lm_shipping_amount_from_order_payload($orderPayload);

    if (!array_key_exists('no_auto_tarjeta', $orderPayload) || trim((string) $orderPayload['no_auto_tarjeta']) === '') {
        $orderPayload['no_auto_tarjeta'] = lm_payment_authorization_number_from_session($session);
    }

    return $orderPayload;
}

function lm_finalize_paid_session(array $session, Config $config, PaymentSessionStore $store, SecureLogger $logger)
{
    if (isset($session['external_order_response']) && is_array($session['external_order_response'])) {
        return $session;
    }

    $orderPayload = lm_external_order_payload($session);
    $forwarder = new OrderForwarder($config, $logger);
    $response = $forwarder->send($orderPayload);

    $session['status'] = 'ORDER_SENT';
    $session['order_payload'] = $orderPayload;
    $session['external_order_response'] = $response;
    $store->save($session['reference'], $session);

    return $session;
}
