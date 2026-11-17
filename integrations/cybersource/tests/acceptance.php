<?php

use LM\CyberSource\OrderFulfillment;
use LM\CyberSource\OrderPayloadValidator;
use LM\CyberSource\CatalogPriceValidator;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\GatewayException;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// Uses the isolated fixtures and assertions of run.php, never private configuration.
foreach (array(array('auth_code' => ''), array('auth_code' => '       '),
    array('reason_code' => '110', 'auth_amount' => '15.00'), array('req_amount' => '20.00'),
    array('req_currency' => 'USD'), array('decision' => 'DECLINE', 'reason_code' => '100'),
    array('decision' => 'CANCEL', 'reason_code' => '200', 'auth_amount' => '30.00', 'auth_code' => '123456')) as $mutation) {
    $test = fixtureSession($config, $store);
    $response = fixtureResponse($test, $config, $mutation);
    $held = $receiver->receive($response, 'merchant_post');
    check(empty($held['payment_verified']) && \LM\CyberSource\Reconciliation::held($held), 'authenticated anomaly held without approving');
    $ids = array_keys($held['reconciliation']);
    $event = $store->findEvent($ids[0]);
    check($event['state'] === 'APPLIED' && count($event['event']['issues']) > 0, 'hold has durable reasons');
    check(!isset($event['event']['authenticated_fields']['req_access_key']), 'inbox never stores access key');
    $before = $held;
    check($receiver->receive($response, 'customer_response') === $before, 'held notification idempotent');
    $noDispatch = new OrderFulfillment($config, $store, $logger, function () { throw new RuntimeException('held payment sent'); });
    check(!isset($noDispatch->process($test['reference'])['order_dispatch']), 'held payment cannot dispatch');
}
$test = fixtureSession($config, $store);
$incomplete = fixtureResponse($test, $config);
unset($incomplete['auth_code']);
$held = $receiver->receive(fixtureSign($incomplete, $config), 'merchant_post');
check($held['status'] === 'REVIEW' && !empty($held['reconciliation']), 'missing signed authorization retained');
$complete = $receiver->receive(fixtureResponse($test, $config), 'merchant_post');
check($complete['payment_verified'] && \LM\CyberSource\Reconciliation::held($complete), 'later complete receipt does not silently clear prior financial hold');
$manual = new \LM\CyberSource\ManualReconciliation($store);
$issue = array_keys($complete['reconciliation'])[0];
rejects(function () use ($manual, $complete, $issue) { $manual->apply($complete['reference'], 'resolve', $issue, 'bad'); }, 'operator needs evidence');
$resolved = $manual->apply($complete['reference'], 'resolve', $issue, 'NEONET-TICKET-UNIT-01');
check(!\LM\CyberSource\Reconciliation::held($resolved) && isset($resolved['operator_actions'][0]), 'operator resolution records evidence and retains payment');
$legacy = fixtureSession($config, $store);
unset($legacy['secure_acceptance_request']['profile_id'], $legacy['secure_acceptance_request']['credential_fingerprint']);
$legacy = $store->save($legacy['reference'], $legacy);
$legacyResponse = fixtureResponse($legacy, $config);
$legacyHeld = $receiver->receive($legacyResponse, 'merchant_post');
check(empty($legacyHeld['payment_verified']) && \LM\CyberSource\Reconciliation::held($legacyHeld), 'legacy receipt conserved but not automatically upgraded');

class AcceptanceFailStore extends PaymentSessionStore
{
    public $failSave = false;
    public $failEvent = false;
    public function save($reference, array $data)
    {
        if ($this->failSave) {
            throw new \LM\CyberSource\PaymentStorageException('Synthetic session persistence outage');
        }
        return parent::save($reference, $data);
    }
    protected function atomicWrite($file, array $data)
    {
        if ($this->failEvent && strpos(basename($file), 'event-') === 0) {
            throw new \LM\CyberSource\PaymentStorageException('Synthetic durable inbox outage');
        }
        return parent::atomicWrite($file, $data);
    }
}
$faultStore = new AcceptanceFailStore($dir);
$test = fixtureSession($config, $faultStore);
$faultReceiver = new \LM\CyberSource\SecureAcceptanceNotification($service, $faultStore);
$response = fixtureResponse($test, $config);
$faultStore->failSave = true;
$queued = $faultReceiver->receive($response, 'merchant_post');
check(!empty($queued['notification_queued']) && empty($queued['payment_verified']), 'inbox accepts receipt while session unavailable');
$recovered = new \LM\CyberSource\SecureAcceptanceNotification($service, new PaymentSessionStore($dir));
foreach ($store->eventIds() as $eventId) {
    $record = $store->findEvent($eventId);
    if ($record['reference'] === $test['reference'] && $record['state'] === 'RECEIVED') {
        $recovered->applyEvent($eventId);
    }
}
check($store->find($test['reference'])['status'] === 'PAID', 'fresh process can replay durable inbox without browser return');
$faultStore->failSave = false;
$queuedConflict = fixtureSession($config, $faultStore);
$receiver->receive(fixtureResponse($queuedConflict, $config), 'merchant_post');
$faultStore->failSave = true;
$faultReceiver->receive(fixtureResponse($queuedConflict, $config, array('auth_code' => ' ')), 'merchant_post');
$blockedDispatches = 0;
$waitingInbox = new OrderFulfillment($config, $store, $logger, function () use (&$blockedDispatches) {
    $blockedDispatches++;
    return array('success' => true, 'idcotizacion' => 99123);
});
$waiting = $waitingInbox->process($queuedConflict['reference']);
check($store->hasPendingEvents($queuedConflict['reference']) && !isset($waiting['order_dispatch']) && $blockedDispatches === 0,
    'unapplied durable financial event prevents dispatch using stale session state');
$faultStore->failSave = false;
$faultStore->failEvent = true;
$test = fixtureSession($config, $faultStore);
rejects(function () use ($faultReceiver, $test, $config) { $faultReceiver->receive(fixtureResponse($test, $config), 'merchant_post'); }, 'no acknowledgement when inbox write fails');
$faultStore->failEvent = false;

foreach (array('DECLINED', 'CANCELLED', 'EXPIRED', 'ORDER_SENT') as $state) {
    $key = 'new-intent-' . $state;
    $old = $coordinator->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ',
        'catalog_verified' => true, 'order_payload' => $order, 'expires_at' => gmdate('c', time() + 3600)), $key);
    $old['status'] = $state;
    $old = $store->save($old['reference'], $old);
    $old = $store->find($old['reference']);
    $challenge = null;
    try { $coordinator->create(array(), $key); } catch (\LM\CyberSource\NewPurchaseRequired $e) { $challenge = $e->response(); }
    check($challenge && $challenge['requires_confirmation'], 'closed purchase requires explicit new intent ' . $state);
    $new = $coordinator->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ',
        'catalog_verified' => true, 'order_payload' => $order, 'expires_at' => gmdate('c', time() + 3600)), $key, $challenge['purchase_confirmation']);
    check($new['reference'] !== $old['reference'] && $new['intent_id'] !== $old['intent_id'], 'new purchase has new reference and intent');
    check($store->find($old['reference']) === $old, 'old finalized reference unchanged');
    check($coordinator->create(array(), $key, $challenge['purchase_confirmation'])['reference'] === $new['reference'], 'confirmation replay returns same new purchase');
}
$test = $store->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ', 'catalog_verified' => true,
    'order_payload' => $order, 'checkout_owner' => 'browser-one', 'expires_at' => gmdate('c', time() + 3600)));
rejects(function () use ($coordinator, $test) { $coordinator->prepare($test['reference'], 'browser-two'); }, 'another browser cannot obtain form');
$first = $coordinator->prepare($test['reference'], 'browser-one');
$again = $coordinator->prepare($test['reference'], 'browser-one');
check($first === $again && strlen($first['transaction_uuid']) === 32, 'interrupted form resumes exact UUID, date and signature');
$current = $store->find($test['reference']);
$current['secure_acceptance_request']['signed_date_time'] = gmdate('Y-m-d\TH:i:s\Z', time() - 301);
$store->save($current['reference'], $current);
rejects(function () use ($coordinator, $test) { $coordinator->prepare($test['reference'], 'browser-one'); }, 'old form not replayed beyond safe duplicate window');
check(\LM\CyberSource\Reconciliation::held($store->find($test['reference'])), 'unrecoverable submission visible for reconciliation');

$arrayCatalog = new CatalogPriceValidator($config, function ($id) {
    return array('success' => true, 'data' => array(array('idarticulo' => $id, 'stock' => 10,
        'presentaciones' => array(array('nombre' => 'UNIDAD', 'tipo' => 'unidad', 'precio' => '15.00')))));
});
$arrayOrder = (new OrderPayloadValidator($config, $arrayCatalog))->validateForHostedCheckout(fixtureOrder());
check($arrayOrder['total_venta'] === 30.0, 'array-only catalog does not manufacture zero-price unit');
$different = fixtureOrder();
$different['datosArticulos']['articulos']['presen'] = array('CAJA');
$different['datosArticulos']['articulos']['tipo_presentacion'] = array('caja');
$boxCatalog = new CatalogPriceValidator($config, function ($id) {
    return array('success' => true, 'data' => array(array('idarticulo' => $id,
        'presentaciones' => array(array('nombre' => 'CAJA', 'tipo' => 'caja', 'precio' => 15), array('nombre' => 'UNIDAD', 'tipo' => 'unidad', 'precio' => 2)))));
});
check((new OrderPayloadValidator($config, $boxCatalog))->validateForHostedCheckout($different)['presen'][0] === 'CAJA', 'real selected presentation official price');

$test = fixtureSession($config, $store);
$receiver->receive(fixtureResponse($test, $config), 'merchant_post');
$dispatches = 0;
$mailCalls = 0;
$racy = new OrderFulfillment($config, $store, $logger, function () use ($receiver, $test, $config, &$dispatches) {
    $dispatches++;
    $receiver->receive(fixtureResponse($test, $config, array('transaction_id' => 'conflict-during-dispatch')), 'merchant_post');
    return array('success' => true, 'idcotizacion' => 9001);
}, function () use (&$mailCalls) { $mailCalls++; return array('attempted' => true, 'sent' => true); });
$raced = $racy->process($test['reference']);
$racy->process($test['reference']);
check($raced['status'] === 'PAID' && $raced['order_dispatch']['state'] === 'CONFIRMED_HELD', 'in-flight conflict never auto-finalizes');
check($raced['external_order_response']['idcotizacion'] === 9001 && isset($raced['order_dispatch']['payload_hash']), 'external irreversible result and payload evidence retained');
check($dispatches === 1 && $mailCalls === 0, 'conflict cannot duplicate order or send misleading success');

$test = fixtureSession($config, $store);
$receiver->receive(fixtureResponse($test, $config), 'merchant_post');
$lost = new OrderFulfillment($config, $store, $logger, function () { throw new GatewayException('lost after commit'); });
$lost->process($test['reference']);
$linked = $manual->apply($test['reference'], 'link-order', '9002', 'SISTEMASOL-TICKET-UNIT-02');
check($linked['status'] === 'ORDER_SENT' && $linked['order_dispatch']['state'] === 'CONFIRMED_MANUAL', 'manual verified linkage creates no remote request');
rejects(function () use ($manual, $test) { $manual->apply($test['reference'], 'link-order', '9003', 'SISTEMASOL-TICKET-UNIT-03'); }, 'manual linkage cannot replace an existing quotation');
$guard = new \LM\CyberSource\Config(array('environment' => 'test'));
$unpaid = fixtureOrder();
$unpaid['forma_pago'] = 'Transferencia';
check((new OrderPayloadValidator($config))->validateForOrder($unpaid)['no_auto_tarjeta'] === '', 'non-card orders cannot import browser authorization either');
rejects(function () use ($guard) { \LM\CyberSource\SideEffectPolicy::assertOrderTransport($guard, 'https://ssl.sol.sistemasolgt.com/real'); }, 'ordinary TEST cannot write production orders');
rejects(function () use ($config) { \LM\CyberSource\SideEffectPolicy::assertOrderTransport($config, 'https://example.com/orders'); }, 'isolated TEST blocks non-loopback destination');
$blockedMail = (new \LM\CyberSource\OrderReferenceMailer($guard, $logger))->send($order, array('success' => true, 'idcotizacion' => 1), 'unused');
check(!$blockedMail['attempted'] && $blockedMail['reason'] === 'test_delivery_blocked', 'ordinary TEST cannot send real customer mail');
rejects(function () use ($guard) { \LM\CyberSource\SideEffectPolicy::assertWorker($guard, array()); }, 'ordinary TEST worker cannot activate accidentally');
\LM\CyberSource\SideEffectPolicy::assertOrderTransport($config, 'https://localhost:12345/mock/orders');
check(true, 'isolation permits only local HTTPS simulator');
$captured = (new \LM\CyberSource\OrderReferenceMailer($config, $logger))->send($order, array('success' => true, 'idcotizacion' => 9999), 'fixture-reference');
check($captured['reason'] === 'captured_not_delivered' && count(glob($dir . '/mail-*.json')) === 1, 'test mail captured locally, never delivered');

$test = fixtureSession($config, $store);
$reviewed = $receiver->receive(fixtureResponse($test, $config, array('decision' => 'REVIEW', 'reason_code' => '480')), 'merchant_post');
check(\LM\CyberSource\ChargeGroups::blocked($store, $reviewed), 'provider review blocks every related retry');
$reviewAccepted = $receiver->receive(fixtureResponse($test, $config), 'merchant_post');
check(!\LM\CyberSource\Reconciliation::held($reviewAccepted) && !\LM\CyberSource\ChargeGroups::blocked($store, $reviewAccepted), 'complete later accept safely resolves preceding provider review');

$authConfig = new \LM\CyberSource\Config(array('environment' => 'test', 'operation' => 'authorization',
    'site' => $config->get('site'), 'secure_acceptance' => $config->get('secure_acceptance'),
    'isolation' => $config->get('isolation'), 'paths' => $config->get('paths'), 'order_email' => $config->get('order_email')));
$authSession = fixtureSession($authConfig, $store);
$authReceiver = new \LM\CyberSource\SecureAcceptanceNotification(new \LM\CyberSource\SecureAcceptanceService($authConfig), $store);
$authorizedOnly = $authReceiver->receive(fixtureResponse($authSession, $authConfig, array('req_transaction_type' => 'authorization')), 'merchant_post');
check(empty($authorizedOnly['payment_verified']) && \LM\CyberSource\Reconciliation::held($authorizedOnly), 'authorization without capture is not a paid sale');

$test = fixtureSession($config, $store);
$oldResponse = fixtureResponse($test, $config);
$rotatedKeys = $config->get('secure_acceptance');
$previousKey = array_intersect_key($rotatedKeys, array_flip(array('profile_id', 'access_key', 'secret_key')));
$rotatedKeys['access_key'] = 'new-fixture-access';
$rotatedKeys['secret_key'] = 'new-fixture-secret';
$rotatedKeys['verification_keys'] = array($previousKey);
$rotated = new \LM\CyberSource\Config(array('environment' => 'test', 'secure_acceptance' => $rotatedKeys));
check((new \LM\CyberSource\SecureAcceptanceService($rotated))->validateResponse($oldResponse, $test), 'verification-only old key validates its original attempt after rotation');
$crossEnvironment = new \LM\CyberSource\Config(array('environment' => 'production', 'secure_acceptance' => $config->get('secure_acceptance')));
check(!(new \LM\CyberSource\SecureAcceptanceService($crossEnvironment))->validateResponse($oldResponse, $test), 'TEST receipt never auto-approves in production even with same key');
$environmentSession = $receiver->receive($oldResponse, 'merchant_post');
$noCrossSend = new OrderFulfillment($crossEnvironment, $store, $logger, function () { throw new RuntimeException('TEST data reached production dispatch'); });
check(\LM\CyberSource\Reconciliation::held($noCrossSend->process($environmentSession['reference'])), 'copied TEST session cannot register a productive order');
rejects(function () use ($crossEnvironment) { \LM\CyberSource\SideEffectPolicy::assertWorker($crossEnvironment, array('--allow-production')); }, 'production worker blocked without independent environment/config authorization');
$unknownEnvironment = new \LM\CyberSource\Config(array('environment' => 'staging',
    'execution' => array('production_authorized' => true),
    'secure_acceptance' => array('test_endpoint' => 'https://secureacceptance.cybersource.com/pay')));
rejects(function () use ($unknownEnvironment) { \LM\CyberSource\SideEffectPolicy::assertCheckout($unknownEnvironment); }, 'unknown environment cannot open a production checkout');

$key = 'cancel-late-charge';
$prior = $coordinator->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ', 'catalog_verified' => true,
    'order_payload' => $order, 'expires_at' => gmdate('c', time() + 3600)), $key);
$coordinator->prepare($prior['reference']);
$prior = $store->find($prior['reference']);
$receiver->receive(fixtureResponse($prior, $config, array('decision' => 'CANCEL', 'reason_code' => '200')), 'merchant_post');
try { $coordinator->create(array(), $key); } catch (\LM\CyberSource\NewPurchaseRequired $e) { $challenge = $e->response(); }
$retry = $coordinator->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ', 'catalog_verified' => true,
    'order_payload' => $order, 'expires_at' => gmdate('c', time() + 3600)), $key, $challenge['purchase_confirmation']);
$receiver->receive(fixtureResponse($prior, $config), 'merchant_post');
rejects(function () use ($coordinator, $retry) { $coordinator->prepare($retry['reference']); }, 'late prior charge prevents a fresh related form before submission');
check($store->find($retry['reference'])['status'] === 'REVIEW', 'superseded retry conserved, never reopened after prior charge');

$terminal = fixtureSession($config, $store);
$terminal = $receiver->receive(fixtureResponse($terminal, $config), 'merchant_post');
$terminal['status'] = 'ORDER_SENT';
$terminal = $store->save($terminal['reference'], $terminal);
$group = $store->readIndex('charge-group-' . $terminal['charge_group']);
$group['references']['another-completed-attempt'] = 'another-charge';
$store->writeIndex('charge-group-' . $terminal['charge_group'], $group);
rejects(function () use ($coordinator, $terminal) { $coordinator->prepare($terminal['reference']); }, 'completed form cannot reopen even when its group has another charge');
check($store->find($terminal['reference']) === $terminal, 'stale redirect never downgrades a paid terminal session');
$owned = $store->create(array('status' => 'PENDING', 'amount' => 30, 'currency' => 'GTQ', 'catalog_verified' => true,
    'order_payload' => $order, 'checkout_owner' => 'owner-browser', 'charge_group' => $terminal['charge_group'],
    'expires_at' => gmdate('c', time() + 3600)));
$owned = $store->find($owned['reference']);
rejects(function () use ($coordinator, $owned) { $coordinator->prepare($owned['reference'], 'other-browser'); }, 'foreign browser rejected before any retry-group mutation');
check($store->find($owned['reference']) === $owned, 'foreign redirect cannot alter session state');

$zeroFallback = new CatalogPriceValidator($config, function ($id) {
    return array('success' => true, 'data' => array(array('idarticulo' => $id, 'precio_venta' => 0,
        'presentaciones' => array(array('nombre' => 'UNIDAD', 'tipo' => 'unidad', 'precio' => 15)))));
});
check((new OrderPayloadValidator($config, $zeroFallback))->validateForHostedCheckout(fixtureOrder())['total_venta'] === 30.0, 'generic zero fallback does not conflict with official presentation');

$test = fixtureSession($config, $store);
$receiver->receive(fixtureResponse($test, $config), 'merchant_post');
$apiOnce = 0;
$mailOnce = 0;
$recoverable = new OrderFulfillment($config, $store, $logger, function () use ($receiver, $test, $config, &$apiOnce) {
    $apiOnce++;
    $receiver->receive(fixtureResponse($test, $config, array('auth_code' => ' ')), 'merchant_post');
    return array('success' => true, 'idcotizacion' => 9004);
}, function () use (&$mailOnce) { $mailOnce++; return array('attempted' => true, 'sent' => true); });
$heldAfterCommit = $recoverable->process($test['reference']);
check($heldAfterCommit['order_dispatch']['state'] === 'CONFIRMED_HELD' && $mailOnce === 0, 'financial anomaly arriving during external commit holds confirmation');
$pendingIssue = array_keys($heldAfterCommit['reconciliation'])[0];
$manual->apply($test['reference'], 'resolve', $pendingIssue, 'NEONET-VERIFIED-FULL-RECEIPT-04');
$released = $recoverable->process($test['reference']);
check($released['status'] === 'ORDER_SENT' && $apiOnce === 1 && $mailOnce === 1, 'reviewed confirmed order recovers without a second API request');
$quoteMailCalls = 0;
for ($i = 0; $i < 2; $i++) {
    $quoteSession = fixtureSession($config, $store);
    $receiver->receive(fixtureResponse($quoteSession, $config), 'merchant_post');
    $quoteProcessor = new OrderFulfillment($config, $store, $logger, function () { return array('success' => true, 'idcotizacion' => 98765); },
        function () use (&$quoteMailCalls) { $quoteMailCalls++; return array('attempted' => true, 'sent' => true); });
    $quoteResult = $quoteProcessor->process($quoteSession['reference']);
    check($i === 0 ? $quoteResult['status'] === 'ORDER_SENT' : $quoteResult['order_dispatch']['state'] === 'CONFIRMED_HELD', 'same quotation cannot confirm two different references');
}
check($quoteMailCalls === 1, 'duplicate SystemsOL identity never confirms another order by mail');
echo "PASS: acceptance regressions, durable reconciliation, new intentions, safe resumption and isolation\n";

$fakeTransportCalls = 0;
$unguarded = new \LM\CyberSource\Config(array('environment' => 'test',
    'external_order_api' => array('url' => 'https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_cotizacion_insertar.php')));
$injected = new \LM\CyberSource\OrderForwarder($unguarded, $logger, function () use (&$fakeTransportCalls) {
    $fakeTransportCalls++;
    return array(200, '{"success":true,"idcotizacion":1}');
});
rejects(function () use ($injected) { $injected->send(array(), true); }, 'injected transport cannot bypass TEST order policy');
check($fakeTransportCalls === 0, 'blocked injection never executes');
$ordinarySession = $store->create(array('status' => 'PAID', 'payment_verified' => true, 'catalog_verified' => true,
    'environment' => 'test', 'isolated_test' => false, 'provider_authorization_number' => '123456', 'order_payload' => $order));
$ordinaryFulfillment = new OrderFulfillment($unguarded, $store, $logger, function () use (&$fakeTransportCalls) {
    $fakeTransportCalls++;
    return array('success' => true, 'idcotizacion' => 99124);
});
rejects(function () use ($ordinaryFulfillment, $ordinarySession) { $ordinaryFulfillment->process($ordinarySession['reference']); }, 'fulfillment callback cannot bypass ordinary TEST transport guard');
check($fakeTransportCalls === 0 && !isset($store->find($ordinarySession['reference'])['order_dispatch']), 'blocked TEST dispatch never claims a real API attempt');
rejects(function () use ($unguarded) { \LM\CyberSource\HttpsTransport::postJson($unguarded,
    'https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_cotizacion_insertar.php', array(), 1); }, 'direct HTTPS client cannot bypass TEST order policy');
rejects(function () use ($unguarded) { \LM\CyberSource\HttpsTransport::postJson($unguarded,
    'https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_cotizacion_insertar.php', array(), 1, 'catalog'); }, 'catalog purpose cannot be used to insert a quotation');
rejects(function () use ($config) { \LM\CyberSource\SideEffectPolicy::assertCatalogTransport($config, 'https://localhost:12345/mock/orders'); }, 'read-only simulator transport cannot reach order writer');
foreach (array('false', 'true', 1, array(1)) as $truthy) {
    $notAuthorized = new \LM\CyberSource\Config(array('environment' => 'production', 'execution' => array('production_authorized' => $truthy),
        'secure_acceptance' => array('production_endpoint' => 'https://secureacceptance.cybersource.com/pay')));
    rejects(function () use ($notAuthorized) { \LM\CyberSource\SideEffectPolicy::assertCheckout($notAuthorized); }, 'production checkout requires boolean true authorization');
    rejects(function () use ($notAuthorized) { \LM\CyberSource\SideEffectPolicy::assertOrderTransport($notAuthorized, 'https://example.invalid/orders'); }, 'production order transport requires boolean true authorization');
    check(!(new \LM\CyberSource\OrderReferenceMailer($notAuthorized, $logger))->send($order, array('success' => true, 'idcotizacion' => 1))['attempted'],
        'truthy configuration cannot enable real mail');
    $previousProductionFlag = getenv('LM_PAYMENT_PRODUCTION_CONFIRMED');
    putenv('LM_PAYMENT_PRODUCTION_CONFIRMED=1');
    try {
        rejects(function () use ($notAuthorized) { \LM\CyberSource\SideEffectPolicy::assertWorker($notAuthorized, array('--allow-production')); }, 'even CLI and process flags cannot bypass nonboolean production authorization');
    } finally {
        putenv($previousProductionFlag === false ? 'LM_PAYMENT_PRODUCTION_CONFIRMED' : 'LM_PAYMENT_PRODUCTION_CONFIRMED=' . $previousProductionFlag);
    }
}
check(!(new \LM\CyberSource\Config(array()))->hasRestCredentials(), 'absent REST fields are not mistaken for credentials');
$restWrongHost = new \LM\CyberSource\Config(array('environment' => 'test'));
rejects(function () use ($restWrongHost) { \LM\CyberSource\SideEffectPolicy::assertRestTransport($restWrongHost, 'https://api.cybersource.com/pts/v2/payments'); }, 'TEST REST cannot target production host');
rejects(function () use ($config, $logger) { (new \LM\CyberSource\CyberSourceClient($config, $logger))->request('POST', '/pts/v2/payments', array()); }, 'isolated checkout cannot activate a real REST payment transport');
$allowedProduction = new \LM\CyberSource\Config(array('environment' => 'production', 'execution' => array('production_authorized' => true)));
rejects(function () use ($allowedProduction) { \LM\CyberSource\HttpsTransport::postJson($allowedProduction, 'file:///never-read', array(), 1); }, 'HTTPS fallback cannot use local file URLs');
echo "PASS: transport-level TEST isolation, REST routing and strict production authorization\n";
