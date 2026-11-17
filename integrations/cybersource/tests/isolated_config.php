<?php

$providerTest = getenv('LM_PAYMENT_PROVIDER_TEST') === '1';
if (getenv('LM_PAYMENT_ISOLATED_TEST') !== '1'
    || (!$providerTest && !in_array(PHP_SAPI, array('cli', 'cli-server'), true))) {
    throw new RuntimeException('Configuracion reservada al banco de pruebas local.');
}

$root = realpath((string) getenv('LM_PAYMENT_TEST_ROOT'));
$origin = (string) getenv('LM_PAYMENT_TEST_ORIGIN');
$url = parse_url($origin);
$mockOrigin = $providerTest ? (string) getenv('LM_PAYMENT_MOCK_ORIGIN') : $origin;
$mock = parse_url($mockOrigin);
if (!$root || !$url || !isset($url['scheme'], $url['host']) || $url['scheme'] !== 'https'
    || (!$providerTest && $url['host'] !== 'localhost')
    || ($providerTest && (in_array(strtolower($url['host']), array('libreriamarquense.com', 'www.libreriamarquense.com'), true)
        || isset($url['port']) && $url['port'] !== 443))
    || !$mock || !isset($mock['scheme'], $mock['host']) || $mock['scheme'] !== 'https' || $mock['host'] !== 'localhost') {
    throw new RuntimeException('Origen TEST HTTPS localhost no configurado.');
}

return array('enabled' => true, 'environment' => 'test', 'currency' => 'GTQ', 'operation' => 'sale',
    'isolation' => array('enabled' => true, 'root' => $root, 'provider_test' => $providerTest,
        'store_api_origin' => $mockOrigin . '/mock/store'),
    'execution' => array('production_authorized' => false),
    'site' => array('base_url' => $origin, 'return_path' => '/api/cybersource/payment_return.php'),
    'secure_acceptance' => array('enabled' => true,
        'profile_id' => $providerTest ? (getenv('LM_PAYMENT_TEST_SA_PROFILE_ID') ?: '') : 'fixture-profile',
        'access_key' => $providerTest ? (getenv('LM_PAYMENT_TEST_SA_ACCESS_KEY') ?: '') : 'fixture-access-not-a-credential',
        'secret_key' => $providerTest ? (getenv('LM_PAYMENT_TEST_SA_SECRET_KEY') ?: '') : 'fixture-secret-not-a-credential',
        'test_endpoint' => $providerTest ? 'https://testsecureacceptance.cybersource.com/pay' : $origin . '/mock/pay',
        'transaction_type' => 'sale', 'session_ttl_minutes' => 60),
    'catalog_api' => array('url' => $mockOrigin . '/mock/catalog', 'timeout_seconds' => 5),
    'external_order_api' => array('url' => $mockOrigin . '/mock/orders', 'timeout_seconds' => 2),
    'transport' => array('ca_file' => $root . '/ca.crt'),
    'cybersource' => array('merchant_id' => $providerTest ? (getenv('LM_PAYMENT_TEST_MERCHANT_ID') ?: '') : ''),
    'device_fingerprint' => array('enabled' => $providerTest,
        'merchant_id' => $providerTest ? (getenv('LM_PAYMENT_TEST_MERCHANT_ID') ?: '') : '',
        'test_org_id' => '1snn5n9w', 'production_org_id' => 'k8vif92e', 'script_base_url' => 'https://h.online-metrix.net'),
    'order_email' => array('enabled' => true, 'mode' => 'capture'),
    'delivery' => array('pickup' => array('type' => 'store_pickup', 'value' => 'store', 'label' => 'Test pickup',
        'address' => 'Test store', 'schedule' => 'Synthetic test only', 'shipping' => 0), 'shipping_groups' => array()),
    'security' => array('max_amount' => 50000, 'max_quantity' => 99, 'max_line_items' => 100, 'max_unit_price' => 50000),
    'paths' => array('session_dir' => $root . '/sessions', 'log_file' => $root . '/test.log'));
