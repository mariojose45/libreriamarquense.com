<?php

require __DIR__ . '/fixtures.php';

$role = $argv[1];
$dir = $argv[2];
$reference = $argv[3];
$config = fixtureConfig($dir);
$store = new \LM\CyberSource\PaymentSessionStore($dir);
$logger = new \LM\CyberSource\SecureLogger($dir . '/test.log');
$deadline = microtime(true) + 10;
while (!is_file($dir . '/start') && microtime(true) < $deadline) {
    usleep(1000);
}
if (!is_file($dir . '/start')) {
    exit(2);
}
try {
    if ($role === 'checkout') {
        $coordinator = new \LM\CyberSource\CheckoutCoordinator($config, $store);
        $session = $coordinator->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ',
            'catalog_verified' => true, 'order_payload' => fixtureOrder(),
            'expires_at' => gmdate('c', time() + 3600)), 'concurrent-purchase');
        file_put_contents($dir . '/checkouts.count', $session['reference'] . "\n", FILE_APPEND | LOCK_EX);
    } elseif ($role === 'notify') {
        $service = new \LM\CyberSource\SecureAcceptanceService($config);
        $receiver = new \LM\CyberSource\SecureAcceptanceNotification($service, $store);
        $session = $store->find($reference);
        $receiver->receive(fixtureResponse($session, $config), 'merchant_post');
    } elseif ($role === 'fulfill') {
        $processor = new \LM\CyberSource\OrderFulfillment($config, $store, $logger,
            function ($order) use ($dir) {
                file_put_contents($dir . '/orders.count', "order\n", FILE_APPEND | LOCK_EX);
                usleep(200000);
                if ($order['no_auto_tarjeta'] !== '123456') {
                    throw new RuntimeException('Untrusted authorization.');
                }
                return array('success' => true, 'idcotizacion' => 42);
            }, function () use ($dir) {
                file_put_contents($dir . '/emails.count', "email\n", FILE_APPEND | LOCK_EX);
                return array('attempted' => true, 'sent' => true);
            });
        $processor->process($reference);
    } elseif ($role === 'prepare') {
        $coordinator = new \LM\CyberSource\CheckoutCoordinator($config, $store);
        $form = $coordinator->prepare($reference);
        file_put_contents($dir . '/attempts.count', $form['transaction_uuid'] . "\n", FILE_APPEND | LOCK_EX);
    } else {
        exit(3);
    }
    echo 'OK';
} catch (\LM\CyberSource\GatewayException $e) {
    echo 'BUSY';
}
