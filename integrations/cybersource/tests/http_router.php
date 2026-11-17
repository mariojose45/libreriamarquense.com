<?php

if (PHP_SAPI !== 'cli-server' || getenv('LM_PAYMENT_ISOLATED_TEST') !== '1') {
    http_response_code(404);
    exit;
}
$proof = (string) getenv('LM_PAYMENT_TEST_CONTROL');
if ($proof === '' || !isset($_SERVER['HTTP_X_ISOLATION_PROXY']) || !hash_equals($proof, $_SERVER['HTTP_X_ISOLATION_PROXY'])) {
    http_response_code(403);
    exit;
}
require_once dirname(__DIR__) . '/Autoload.php';
$root = dirname(__DIR__, 3);
$testConfig = \LM\CyberSource\Config::forProcess($root);
if ($_SERVER['HTTP_HOST'] !== parse_url($testConfig->get('site.base_url'), PHP_URL_HOST) . ':' . parse_url($testConfig->get('site.base_url'), PHP_URL_PORT)) {
    http_response_code(421);
    exit;
}
$_SERVER['HTTPS'] = 'on';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function fixtureJson($data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
if (strpos($path, '/__fixture/') === 0) {
    if (!isset($_SERVER['HTTP_X_TEST_CONTROL']) || !hash_equals($proof, $_SERVER['HTTP_X_TEST_CONTROL'])) {
        http_response_code(403);
        exit;
    }
    require_once $root . '/assets/php/security.php';
    $testStore = new \LM\CyberSource\PaymentSessionStore($testConfig->get('paths.session_dir'));
    if ($path === '/__fixture/info') {
        fixtureJson(array('csrf' => lm_csrf_token('checkout'), 'isolated' => true));
    }
    $input = json_decode((string) file_get_contents('php://input'), true) ?: array();
    $reference = isset($_GET['ref']) ? $_GET['ref'] : (isset($input['reference']) ? $input['reference'] : '');
    $testSession = $testStore->find($reference);
    if (!$testSession) {
        http_response_code(404);
        fixtureJson(array('success' => false));
    }
    if ($path === '/__fixture/session') {
        fixtureJson(array_intersect_key($testSession, array_flip(array('reference', 'status', 'payment_verified',
            'provider_transaction_id', 'external_order_response', 'order_dispatch', 'order_email', 'reconciliation', 'payment_conflicts', 'applied_events'))));
    }
    if ($path === '/__fixture/hold') {
        $testStore->withLock('session-' . $reference, function () use ($testConfig) {
            file_put_contents($testConfig->get('isolation.root') . '/lock-ready', 'fixture');
            usleep(4500000);
        });
        fixtureJson(array('released' => true));
    }
    if ($path === '/__fixture/patch') {
        $testStore->withLock('session-' . $reference, function () use ($testStore, $reference, $input) {
            $session = $testStore->find($reference);
            if (!empty($input['expired'])) {
                $session['expires_at'] = gmdate('c', time() - 10);
            }
            if (!empty($input['legacy'])) {
                unset($session['secure_acceptance_request']['credential_fingerprint']);
            }
            $testStore->save($reference, $session);
        });
        fixtureJson(array('patched' => true));
    }
    if ($path === '/__fixture/receipt') {
        $attempt = $testSession['secure_acceptance_request'];
        $params = array('decision' => 'ACCEPT', 'reason_code' => '100', 'transaction_id' => 'txn-' . $reference,
            'req_reference_number' => $reference, 'req_profile_id' => $attempt['profile_id'],
            'req_transaction_uuid' => $attempt['transaction_uuid'], 'req_transaction_type' => $attempt['transaction_type'],
            'req_access_key' => $testConfig->get('secure_acceptance.access_key'), 'req_amount' => '30.00',
            'req_currency' => 'GTQ', 'auth_amount' => '30.00', 'auth_code' => '123456', 'signed_date_time' => gmdate('Y-m-d\TH:i:s\Z'));
        foreach (isset($input['mutation']) ? $input['mutation'] : array() as $key => $value) {
            if (array_key_exists($key, $params)) {
                $params[$key] = $value;
            }
        }
        if (in_array($params['decision'], array('DECLINE', 'CANCEL'), true)) {
            foreach (array('auth_amount', 'auth_code') as $field) {
                if (!isset($input['mutation'][$field])) {
                    unset($params[$field]);
                }
            }
        }
        foreach (isset($input['omit']) ? $input['omit'] : array() as $key) {
            unset($params[$key]);
        }
        $params['signed_field_names'] = implode(',', array_merge(array_keys($params), array('signed_field_names')));
        $data = array();
        foreach (explode(',', $params['signed_field_names']) as $key) {
            $data[] = $key . '=' . $params[$key];
        }
        $params['signature'] = base64_encode(hash_hmac('sha256', implode(',', $data), $testConfig->get('secure_acceptance.secret_key'), true));
        fixtureJson($params);
    }
    http_response_code(404);
    exit;
}
$routes = array('/api/cybersource/create_checkout.php', '/api/cybersource/secure_acceptance_redirect.php',
    '/api/cybersource/payment_webhook.php', '/api/cybersource/payment_return.php', '/api/orders/create.php',
    '/integrations/cybersource/process_pending.php', '/integrations/cybersource/reconcile.php',
    '/index.php', '/cart.php', '/tienda.php', '/producto.php', '/servicios.php', '/nosotros.php',
    '/ofertas.php', '/menosde100.php', '/contact.php', '/assets/php/productos_paginados.php', '/assets/php/form-process.php');
if (preg_match('~^/assets/(?:js|css|img|fonts)/[A-Za-z0-9_./ -]+$~D', $path) && strpos($path, '..') === false) {
    return false;
}
if (!in_array($path, $routes, true)) {
    http_response_code(404);
    exit;
}
require $root . $path;
