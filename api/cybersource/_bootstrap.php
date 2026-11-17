<?php

require_once dirname(__DIR__, 2) . '/integrations/cybersource/Autoload.php';
require_once dirname(__DIR__, 2) . '/assets/php/security.php';

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

$config = Config::forProcess(dirname(__DIR__, 2));
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

function lm_require_csrf_token($action, array $payload = array())
{
    $token = (string) lm_request_header('X-CSRF-Token');

    if ($token === '' && isset($payload['csrf_token'])) {
        $token = (string) $payload['csrf_token'];
    }

    if (!lm_validate_csrf_token($token, $action)) {
        lm_json_response(array('success' => false, 'message' => 'La sesion del formulario vencio. Recargue la pagina e intente nuevamente.'), 403);
    }
}

function lm_require_rate_limit_json($key, $maxAttempts, $windowSeconds)
{
    if (!lm_rate_limit($key, $maxAttempts, $windowSeconds)) {
        lm_json_response(array('success' => false, 'message' => 'Ha realizado varios intentos seguidos. Espere un momento e intente nuevamente.'), 429);
    }
}

function lm_payment_reference_is_valid($reference)
{
    return is_string($reference) && preg_match('/^LMQ-\d{14}-[A-F0-9]{8}$/', $reference);
}

function lm_session_is_expired(array $session)
{
    return isset($session['expires_at']) && strtotime((string) $session['expires_at']) < time();
}

function lm_payment_service(Config $config, SecureLogger $logger)
{
    return new CyberSourcePaymentService(new CyberSourceClient($config, $logger), $config);
}

function lm_finalize_paid_session(array $session, Config $config, PaymentSessionStore $store, SecureLogger $logger)
{
    $processor = new \LM\CyberSource\OrderFulfillment($config, $store, $logger);
    return $processor->process($session['reference']);
}
