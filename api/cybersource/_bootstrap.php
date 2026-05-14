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

function lm_finalize_paid_session(array $session, Config $config, PaymentSessionStore $store, SecureLogger $logger)
{
    if (isset($session['external_order_response']) && is_array($session['external_order_response'])) {
        return $session;
    }

    $forwarder = new OrderForwarder($config, $logger);
    $response = $forwarder->send($session['order_payload']);

    $session['status'] = 'ORDER_SENT';
    $session['external_order_response'] = $response;
    $store->save($session['reference'], $session);

    return $session;
}
