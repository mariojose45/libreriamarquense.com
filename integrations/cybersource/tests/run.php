<?php

// No private configuration, real HTTP transport, mail(), or existing sessions.
require __DIR__ . '/fixtures.php';

use LM\CyberSource\CatalogPriceValidator;
use LM\CyberSource\CheckoutCoordinator;
use LM\CyberSource\GatewayException;
use LM\CyberSource\Money;
use LM\CyberSource\OrderForwarder;
use LM\CyberSource\OrderFulfillment;
use LM\CyberSource\OrderPayloadValidator;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\SecureAcceptanceNotification;
use LM\CyberSource\SecureAcceptanceService;
use LM\CyberSource\SecureLogger;
use LM\CyberSource\SensitiveData;

$assertions = 0;
function check($condition, $label)
{
    global $assertions;
    $assertions++;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}
function rejects(callable $callback, $label)
{
    try {
        $callback();
    } catch (GatewayException $e) {
        check(true, $label);
        return;
    }
    check(false, $label);
}
function simultaneous($role, $dir, $reference)
{
    $processes = array();
    for ($i = 0; $i < 6; $i++) {
        $command = '"' . PHP_BINARY . '" -n -d allow_url_fopen=0 -d disable_functions=mail,curl_exec "'
            . __DIR__ . '/concurrent_worker.php" ' . $role . ' "' . $dir . '" ' . $reference;
        $pipes = array();
        $process = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
            $pipes, null, null, array('bypass_shell' => true));
        check(is_resource($process), 'child started');
        fclose($pipes[0]);
        $processes[] = array($process, $pipes);
    }
    file_put_contents($dir . '/start', 'go');
    $outcomes = array();
    foreach ($processes as $entry) {
        $output = stream_get_contents($entry[1][1]);
        $error = stream_get_contents($entry[1][2]);
        fclose($entry[1][1]);
        fclose($entry[1][2]);
        $exit = proc_close($entry[0]);
        $outcomes[] = array($exit, $output, $error);
    }
    unlink($dir . '/start');
    foreach ($outcomes as $outcome) {
        check($outcome[0] === 0 && $outcome[2] === '' && in_array($outcome[1], array('OK', 'BUSY'), true),
            'concurrent child completed without warnings: ' . json_encode($outcome));
    }
}

$dir = sys_get_temp_dir() . '/lm-cybersource-security-' . bin2hex(random_bytes(8));
mkdir($dir, 0700);
file_put_contents($dir . '/fixture-only', 'synthetic');
$config = fixtureConfig($dir);
$store = new PaymentSessionStore($dir);
$logger = new SecureLogger($dir . '/test.log');
$service = new SecureAcceptanceService($config);
$receiver = new SecureAcceptanceNotification($service, $store);

try {
    $session = fixtureSession($config, $store);
    $reference = $session['reference'];
    $valid = fixtureResponse($session, $config);
    check($service->validateResponse($valid, $session), 'valid signed response');
    check($service->normalizeDecision($valid) === 'APPROVED', 'accept plus 100');
    foreach (explode(',', $valid['signed_field_names']) as $field) {
        $bad = $valid;
        $bad['signed_field_names'] = implode(',', array_diff(explode(',', $bad['signed_field_names']), array($field)));
        $bad = fixtureSign($bad, $config);
        check(!$service->validateResponse($bad, $session), 'reject unsigned critical field ' . $field);
    }
    $bad = $valid;
    $bad['signature'] = base64_encode(random_bytes(32));
    rejects(function () use ($receiver, $bad) { $receiver->receive($bad, 'test'); }, 'invalid HMAC');
    $bad = $valid;
    unset($bad['signature']);
    check(!$service->validateResponse($bad, $session), 'missing signature');
    $bad = $valid;
    $bad['auth_amount'] = '0.01';
    check(!$service->validateResponse($bad, $session), 'tampered signed amount');
    $mutations = array(
        array('req_amount' => '29.99'), array('auth_amount' => '29.99'), array('auth_amount' => '30.01'),
        array('req_amount' => 'GTQ 30.00'), array('req_amount' => '3e1'), array('auth_amount' => 'NaN'),
        array('req_currency' => 'USD'), array('req_currency' => 'gtq'), array('req_profile_id' => 'other'),
        array('req_transaction_uuid' => 'another-attempt'), array('req_transaction_type' => 'authorization'),
        array('req_access_key' => 'another-access'), array('req_reference_number' => 'LMQ-20260101000000-AAAAAAAA'),
        array('decision' => 'DECLINE'), array('decision' => 'REVIEW'), array('reason_code' => '203'),
        array('reason_code' => '110'), array('amount' => '0.01'), array('currency' => 'USD'),
        array('signed_date_time' => '2099-01-01T00:00:00Z'), array('signed_date_time' => 'bad-date'),
    );
    foreach ($mutations as $mutation) {
        check(!$service->validateResponse(fixtureResponse($session, $config, $mutation), $session), 'reject signed mismatch ' . implode(',', array_keys($mutation)));
    }
    foreach (array('auth_amount', 'auth_code', 'req_amount', 'req_currency', 'req_transaction_uuid') as $field) {
        $bad = $valid;
        unset($bad[$field]);
        $bad = fixtureSign($bad, $config);
        check(!$service->validateResponse($bad, $session), 'missing critical value ' . $field);
    }
    $request = $service->buildSignedFormFields($session);
    $request['decision'] = 'ACCEPT';
    $request['reason_code'] = '100';
    check(!$service->validateResponse($request, $session), 'outgoing signature is not a response');
    $bad = $valid;
    $bad['decision'] = array('ACCEPT');
    check(!$service->validateResponse($bad, $session), 'array injection');
    $bad = $valid;
    $bad['signed_field_names'] .= ',decision';
    check(!$service->validateResponse(fixtureSign($bad, $config), $session), 'duplicate signed field');
    echo "PASS: authenticated fields, correlation, amount, GTQ and decisions\n";

    $body = http_build_query($valid, '', '&');
    check(SecureAcceptanceNotification::parseForm($body, 'application/x-www-form-urlencoded; charset=UTF-8') === $valid, 'parse real form encoding');
    foreach (array($body . '&decision=DECLINE', $body . '&req.amount=1', $body . '&decision%5B%5D=ACCEPT') as $badBody) {
        rejects(function () use ($badBody) { SecureAcceptanceNotification::parseForm($badBody, 'application/x-www-form-urlencoded'); }, 'reject ambiguous form');
    }
    rejects(function () { SecureAcceptanceNotification::parseForm('{}', 'application/json'); }, 'no JSON/admin-token bypass');
    rejects(function () { SecureAcceptanceNotification::parseForm(str_repeat('x', 131073), 'application/x-www-form-urlencoded'); }, 'body size limit');
    $before = $store->find($reference);
    $bad = $valid;
    $bad['signature'] = 'invalid';
    rejects(function () use ($receiver, $bad) { $receiver->receive($bad, 'test'); }, 'no mutation from invalid callback');
    check($store->find($reference) === $before, 'invalid callback leaves session unchanged');
    $paid = $receiver->receive($valid, 'merchant_post');
    $duplicate = $receiver->receive($valid, 'customer_response');
    check($paid === $duplicate && $paid['status'] === 'PAID', 'duplicate notifications no-op');
    check(!isset($paid['provider_webhook'], $paid['provider_return']), 'no raw response persisted');
    $decline = fixtureResponse($session, $config, array('decision' => 'DECLINE', 'reason_code' => '203', 'auth_code' => '', 'auth_amount' => ''));
    $afterDecline = $receiver->receive($decline, 'merchant_post');
    check($afterDecline['status'] === 'PAID' && $afterDecline['provider_transaction_id'] === $paid['provider_transaction_id']
        && $afterDecline['provider_authorization_number'] === $paid['provider_authorization_number'], 'cannot downgrade paid financial facts');
    $conflict = fixtureSession($config, $store);
    $receiver->receive(fixtureResponse($conflict, $config), 'test');
    $conflicted = $receiver->receive(fixtureResponse($conflict, $config, array('transaction_id' => 'different-id')), 'test');
    check($conflicted['provider_transaction_id'] !== 'different-id', 'second charge cannot replace the first transaction');
    check(!empty($store->find($conflict['reference'])['payment_conflicts']), 'different charge held for reconciliation');
    $other = fixtureSession($config, $store);
    $owned = $receiver->receive(fixtureResponse($other, $config, array('transaction_id' => $valid['transaction_id'])), 'test');
    check(empty($owned['payment_verified']) && \LM\CyberSource\Reconciliation::held($owned), 'transaction owned by another reference held, not approved');
    $late = fixtureSession($config, $store);
    $late['status'] = 'EXPIRED';
    $late['expires_at'] = gmdate('c', time() - 3600);
    $late = $store->save($late['reference'], $late);
    check($receiver->receive(fixtureResponse($late, $config), 'test')['status'] === 'PAID', 'legitimate late confirmation');
    $cancelled = fixtureSession($config, $store);
    // Synthetic signed cancellation; it never authorizes fulfillment.
    $cancel = fixtureResponse($cancelled, $config, array('decision' => 'CANCEL', 'reason_code' => '200'));
    check($receiver->receive($cancel, 'customer_response')['status'] === 'CANCELLED', 'authenticated cancel');
    check($receiver->receive(fixtureResponse($cancelled, $config), 'merchant_post')['status'] === 'PAID', 'late approval after cancel');
    $review = fixtureSession($config, $store);
    $reviewResponse = fixtureResponse($review, $config, array('decision' => 'REVIEW', 'reason_code' => '480'));
    check($receiver->receive($reviewResponse, 'merchant_post')['status'] === 'REVIEW', 'review never approves');
    $error = fixtureSession($config, $store);
    $errorResponse = fixtureResponse($error, $config, array('decision' => 'ERROR', 'reason_code' => '150'));
    check($receiver->receive($errorResponse, 'merchant_post')['status'] === 'REVIEW', 'system failure requires reconciliation');
    $declined = fixtureSession($config, $store);
    $declinedResponse = fixtureResponse($declined, $config, array('decision' => 'DECLINE', 'reason_code' => '203'));
    unset($declinedResponse['auth_code'], $declinedResponse['auth_amount']);
    $declinedResponse = fixtureSign($declinedResponse, $config);
    check($receiver->receive($declinedResponse, 'merchant_post')['status'] === 'DECLINED', 'real decline can omit authorization fields');
    echo "PASS: form POST, durable receipt, duplicates and late confirmations\n";

    $count = 0;
    $emails = 0;
    $processor = new OrderFulfillment($config, $store, $logger,
        function ($order) use (&$count) {
            $count++;
            check($order['no_auto_tarjeta'] === '123456', 'provider authorization replaces browser value');
            return array('success' => true, 'idcotizacion' => 120);
        }, function ($order, $response, $ref) use (&$emails, &$count) {
            check($count === 1 && $response['idcotizacion'] === 120 && $ref !== '', 'mail after functional confirmation');
            $emails++;
            return array('attempted' => true, 'sent' => true);
        });
    $sent = $processor->process($reference);
    $processor->process($reference);
    check($sent['status'] === 'ORDER_SENT' && $count === 1 && $emails === 1, 'one order and one email');
    check($receiver->receive($valid, 'merchant_post')['status'] === 'ORDER_SENT', 'paid replay preserves completed state');
    $held = new OrderFulfillment($config, $store, $logger, function () { throw new RuntimeException('Conflicting charge must be reconciled'); });
    check(!isset($held->process($conflict['reference'])['order_dispatch']), 'conflicting charges cannot create an order automatically');
    $failures = array(array('success' => false, 'idcotizacion' => 4), array('success' => true),
        array('success' => true, 'idcotizacion' => 0), array('idcotizacion' => 4), array('success' => 'false', 'idcotizacion' => 4));
    foreach ($failures as $response) {
        $test = fixtureSession($config, $store);
        $receiver->receive(fixtureResponse($test, $config), 'test');
        $calls = 0;
        $failing = new OrderFulfillment($config, $store, $logger, function () use ($response, &$calls) { $calls++; return $response; },
            function () { throw new RuntimeException('Mail must not be called'); });
        $failed = $failing->process($test['reference']);
        $failing->process($test['reference']);
        check($failed['status'] === 'PAID' && $failed['order_dispatch']['state'] === 'UNKNOWN' && $calls === 1, 'functional failure not ORDER_SENT or resent');
        check(!isset($failed['order_email']), 'no email on unconfirmed order');
    }
    $test = fixtureSession($config, $store);
    $receiver->receive(fixtureResponse($test, $config), 'test');
    $remoteCommits = 0;
    $lostResponse = new OrderFulfillment($config, $store, $logger, function () use (&$remoteCommits) {
        $remoteCommits++;
        throw new GatewayException('Simulated timeout after remote commit');
    });
    $lostResponse->process($test['reference']);
    $lost = $lostResponse->process($test['reference']);
    check($remoteCommits === 1 && $lost['order_dispatch']['state'] === 'UNKNOWN', 'lost response never automatically resubmitted');
    $receiver->receive(fixtureResponse($test, $config), 'merchant_post');
    $lostResponse->process($test['reference']);
    check($remoteCommits === 1, 'duplicate provider confirmation does not reset uncertain dispatch');
    $emailFailure = fixtureSession($config, $store);
    $receiver->receive(fixtureResponse($emailFailure, $config), 'test');
    $emailOrderCount = 0;
    $emailCount = 0;
    $emailProcessor = new OrderFulfillment($config, $store, $logger, function () use (&$emailOrderCount) {
        $emailOrderCount++;
        return array('success' => true, 'idcotizacion' => 121);
    }, function () use (&$emailCount) {
        $emailCount++;
        return array('attempted' => true, 'sent' => false, 'reason' => 'mail_failed');
    });
    $emailProcessor->process($emailFailure['reference']);
    $emailFailed = $emailProcessor->process($emailFailure['reference']);
    check($emailFailed['status'] === 'ORDER_SENT' && $emailFailed['order_email']['state'] === 'FAILED'
        && $emailOrderCount === 1 && $emailCount === 1, 'mail failure does not resend order or mail automatically');
    $legacy = $store->create(array('status' => 'ORDER_SENT', 'amount' => 30, 'currency' => 'GTQ',
        'order_payload' => fixtureOrder(), 'external_order_response' => array('success' => true, 'idcotizacion' => 2)));
    check($processor->process($legacy['reference']) === $legacy, 'legacy completed session preserved without resending');
    $test = fixtureSession($config, $store);
    $test = $receiver->receive(fixtureResponse($test, $config), 'test');
    $test['order_dispatch'] = array('state' => 'SENDING');
    $store->save($test['reference'], $test);
    $stopped = new OrderFulfillment($config, $store, $logger, function () { throw new RuntimeException('Must not resend after crash'); });
    check($stopped->process($test['reference'])['order_dispatch']['state'] === 'SENDING', 'durable claim survives worker crash');
    foreach (array(array(200, '{"success":false}'), array(500, '{"success":true,"idcotizacion":5}'), array(200, 'not-json')) as $transportResult) {
        $forwarder = new OrderForwarder($config, $logger, function () use ($transportResult) { return $transportResult; });
        rejects(function () use ($forwarder) { $forwarder->send(array(), true); }, 'HTTP or functional API failure');
    }
    $forwarder = new OrderForwarder($config, $logger, function () { return array(200, '{"success":true,"idcotizacion":5}'); });
    check($forwarder->send(array(), true)['idcotizacion'] === 5, 'observed success/idcotizacion contract');
    echo "PASS: order confirmation, one-shot dispatch, lost responses and email ordering\n";

    $lookup = function ($id) { return array('success' => true, 'data' => array(array('idarticulo' => $id,
        'nombre_01' => 'UNIDAD', 'precio_venta' => '15.00000', 'stock' => 5))); };
    $catalog = new CatalogPriceValidator($config, $lookup);
    $validator = new OrderPayloadValidator($config, $catalog);
    $order = $validator->validateForHostedCheckout(fixtureOrder());
    check($order['no_auto_tarjeta'] === '' && $order['total_venta'] === 30.0, 'trusted catalog price, discard browser authorization');
    $cheap = fixtureOrder();
    $cheap['total_venta'] = $cheap['total_ventades'] = 2;
    $cheap['datosArticulos']['articulos']['precio_venta'] = array(1);
    $cheap['datosArticulos']['articulos']['subtotal1'] = $cheap['datosArticulos']['articulos']['subtotaldes1'] = array(2);
    rejects(function () use ($validator, $cheap) { $validator->validateForHostedCheckout($cheap); }, 'internally consistent browser price forgery');
    $changed = fixtureOrder();
    $changed['datosArticulos']['articulos']['presen'] = array('CAJA');
    rejects(function () use ($validator, $changed) { $validator->validateForHostedCheckout($changed); }, 'unknown presentation');
    $malicious = fixtureOrder();
    $malicious['extra_untrusted'] = 'ignored';
    $malicious['presentaciones'] = array(array('precio_presentacion' => 0.01));
    $malicious['datosArticulos']['articulos']['extra_price'] = array(0.01);
    $clean = $validator->validateForHostedCheckout($malicious);
    check(!isset($clean['extra_untrusted'], $clean['datosArticulos']['articulos']['extra_price'])
        && $clean['presentaciones'][0]['precio_presentacion'] === 15.0, 'all alternate price representations canonicalized');
    $key = CheckoutCoordinator::purchaseKey('fixture-browser', $clean);
    $reordered = array_reverse($clean, true);
    $reordered['presentaciones'] = array(
        array('idarticulo' => 1, 'tipo_presentacion' => 'unidad', 'cantidad' => 1),
        array('idarticulo' => 1, 'tipo_presentacion' => 'unidad', 'cantidad' => 1));
    check(CheckoutCoordinator::purchaseKey('fixture-browser', $reordered) === $key,
        'purchase identity ignores JSON field order and split identical lines');
    rejects(function () use ($clean) { CheckoutCoordinator::purchaseKey('', $clean); }, 'no empty browser scope');
    $unavailable = new OrderPayloadValidator($config, new CatalogPriceValidator($config, function () { return array('success' => false); }));
    rejects(function () use ($unavailable) { $unavailable->validateForHostedCheckout(fixtureOrder()); }, 'catalog fail closed');
    $nonCard = fixtureOrder();
    $nonCard['forma_pago'] = 'Pago Contra Entrega';
    check($validator->validateForOrder($nonCard)['forma_pago'] === 'Pago Contra Entrega', 'non-card validator remains compatible');
    echo "PASS: server catalog, canonical pricing and existing non-card validation\n";

    $coordinator = new CheckoutCoordinator($config, $store);
    simultaneous('checkout', $dir, 'unused');
    check(count(file($dir . '/checkouts.count')) === 6 && count(array_unique(file($dir . '/checkouts.count'))) === 1,
        'simultaneous checkout requests reuse one session');
    $pending = $coordinator->create(array('status' => 'PENDING', 'catalog_verified' => true, 'amount' => 30,
        'currency' => 'GTQ', 'order_payload' => fixtureOrder(), 'expires_at' => gmdate('c', time() + 3600)), 'same-purchase');
    check($coordinator->create(array('status' => 'PENDING'), 'same-purchase')['reference'] === $pending['reference'], 'duplicate checkout reuses session');
    simultaneous('prepare', $dir, $pending['reference']);
    check(count(array_unique(file($dir . '/attempts.count'))) === 1, 'simultaneous redirects only reuse identical form UUID');
    rejects(function () use ($coordinator, $reference) { $coordinator->prepare($reference); }, 'no charge from paid/finalized session');
    foreach (array('PAID', 'ORDER_SENT', 'DECLINED', 'CANCELLED', 'REVIEW') as $terminalState) {
        $terminal = $store->create(array('status' => $terminalState, 'catalog_verified' => true,
            'currency' => 'GTQ', 'amount' => 30, 'order_payload' => fixtureOrder(), 'expires_at' => gmdate('c', time() + 3600)));
        rejects(function () use ($coordinator, $terminal) { $coordinator->prepare($terminal['reference']); }, 'cannot reopen ' . $terminalState);
    }
    check($coordinator->create(array(), 'same-purchase')['reference'] === $pending['reference'], 'submitted purchase only resumes its original reference');
    $race = fixtureSession($config, $store);
    simultaneous('notify', $dir, $race['reference']);
    check($store->find($race['reference'])['status'] === 'PAID', 'simultaneous callbacks durable');
    simultaneous('fulfill', $dir, $race['reference']);
    check(count(file($dir . '/orders.count')) === 1 && count(file($dir . '/emails.count')) === 1, 'simultaneous workers dispatch and email once');
    check($store->find($race['reference'])['status'] === 'ORDER_SENT', 'race ends completed');
    echo "PASS: six-process races for checkouts, redirects, callbacks and order workers\n";

    $stale = $store->find($reference);
    $store->save($reference, $stale);
    rejects(function () use ($store, $reference, $stale) { $store->save($reference, $stale); }, 'stale revision rejected');
    rejects(function () use ($store) { $store->find('../LMQ-20260101000000-AAAAAAAA'); }, 'no path aliases/traversal');
    file_put_contents($dir . '/LMQ-20260101000000-AAAAAAAA.json', '{broken');
    rejects(function () use ($store) { $store->find('LMQ-20260101000000-AAAAAAAA'); }, 'corrupt session fails closed');
    $redacted = SensitiveData::redact(array('req_access_key' => 'fixture-value', 'req_card_number' => '4111111111111111',
        'req_card_cvn' => '123', 'req_card_expiry_date' => '12-2030', 'order' => array('nombre_cliente' => 'Cliente Prueba')));
    check($redacted['req_access_key'] === '[REDACTED]' && $redacted['req_card_number'] === '[REDACTED]'
        && $redacted['req_card_cvn'] === '[REDACTED]' && $redacted['req_card_expiry_date'] === '[REDACTED]'
        && $redacted['order']['nombre_cliente'] === 'Cliente Prueba', 'session secrets removed, order data preserved');
    $logger->info('test', array('req_access_key' => 'do-not-log', 'correo_cliente' => 'person@example.invalid',
        'card_number' => '4111111111111111', 'raw' => 'password=hidden-test'));
    $log = file_get_contents($dir . '/test.log');
    check(strpos($log, 'do-not-log') === false && strpos($log, 'person@example.invalid') === false
        && strpos($log, '4111111111111111') === false && strpos($log, 'hidden-test') === false, 'safe logs');
    rejects(function () use ($store) {
        $store->create(array('invalid' => "\xB1\x31"));
    }, 'serialization failure is not acknowledged');
    check($config->get('environment') === 'test', 'tests never use production');
    check(Money::cents('22.50') === 2250 && Money::decimal(2250) === '22.50', 'exact cent arithmetic');
    echo "PASS: atomic persistence, revision checks and redaction\n";
    require __DIR__ . '/acceptance.php';
    echo 'OK: ' . $assertions . " assertions. No real HTTP, mail or payment calls.\n";
} finally {
    // Remove only this run's synthetic fixtures, never the application's storage.
    $real = realpath($dir);
    if ($real && basename($real) === basename($dir) && is_file($real . '/fixture-only')) {
        foreach (glob($real . '/*') ?: array() as $file) {
            if (is_file($file) && dirname(realpath($file)) === $real) {
                unlink($file);
            }
        }
        rmdir($real);
    }
}
