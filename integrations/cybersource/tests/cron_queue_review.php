<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/Autoload.php';

use LM\CyberSource\Config;
use LM\CyberSource\ManualReconciliation;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\PaymentStorageException;
use LM\CyberSource\ReconciliationAdminAuth;
use LM\CyberSource\ReconciliationAdminService;
use LM\CyberSource\ReconciliationAdminStore;

// No workers, receipts, transports or fulfillment. Payment fixtures exist only in memory.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
final class CronReviewMemoryStore extends PaymentSessionStore
{
    public $records = array();
    public $events = array();
    public $indexes = array();
    public $reads = 0;
    public function __construct() {}
    public function references() { foreach (array_keys($this->records) as $ref) { yield $ref; } }
    public function find($ref) {
        $this->reads++;
        $record = isset($this->records[$ref]) ? $this->records[$ref] : null;
        if ($record === 'corrupt') { throw new PaymentStorageException('PRIVATE-ERROR-SENTINEL'); }
        return $record;
    }
    public function eventIds($reference = null) { foreach ($this->events as $id => $record) { if ($reference === null || strpos($id, $reference . '-') === 0) { yield $id; } } }
    public function findEvent($id) {
        $record = $this->events[$id];
        if ($record === 'corrupt') { throw new PaymentStorageException('PRIVATE-ERROR-SENTINEL'); }
        return $record;
    }
    public function readIndex($key) { return isset($this->indexes[$key]) ? $this->indexes[$key] : null; }
    public function hasPendingEvents($reference) {
        foreach ($this->eventIds($reference) as $id) { if ($this->findEvent($id)['state'] !== 'APPLIED') { return true; } }
        return false;
    }
    public function create(array $data) { throw new RuntimeException('Forbidden business write'); }
    public function save($ref, array $data) { throw new RuntimeException('Forbidden business write'); }
    public function writeIndex($key, array $data) { throw new RuntimeException('Forbidden business write'); }
    public function saveEvent(array $record) { throw new RuntimeException('Forbidden business write'); }
    public function withLock($key, callable $callback, $timeoutSeconds = 2) { throw new RuntimeException('Forbidden business lock'); }
}

$checks = 0;
$check = function ($ok, $label) use (&$checks) { $checks++; if (!$ok) { throw new RuntimeException('FAIL: ' . $label); } };
$root = sys_get_temp_dir() . '/lm-cron-review-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
mkdir($root . '/users', 0700);
$canonical = realpath($root);
$beforeConfirmed = getenv('LM_PAYMENT_PRODUCTION_CONFIRMED');
try {
    $password = 'Synthetic-cron-review-' . bin2hex(random_bytes(16));
    $account = ReconciliationAdminStore::account('fixture.operator', $password);
    file_put_contents($root . '/users/fixture.operator.json', json_encode($account));
    chmod($root . '/users/fixture.operator.json', 0600);
    $private = new ReconciliationAdminStore($root, array(dirname(__DIR__, 3)));
    $auth = new ReconciliationAdminAuth($private);
    $auth->start();
    $config = new Config(array('environment' => 'production', 'execution' => array('production_authorized' => false)));
    $store = new CronReviewMemoryStore();
    $service = new ReconciliationAdminService($config, array('allow_manual_links' => false), $store,
        new ManualReconciliation($store, $config), $private, $auth);
    $denied = false;
    try { $service->cronPreview(); } catch (Throwable $e) { $denied = true; }
    $check($denied && $store->reads === 0, 'authentication required before any payment read');
    $auth->login('fixture.operator', $password, '192.0.2.20');
    $base = array('status' => 'PAID', 'payment_verified' => true, 'catalog_verified' => true,
        'environment' => 'production', 'isolated_test' => false, 'amount' => 29.75, 'currency' => 'GTQ',
        'created_at' => '2026-08-20T16:13:31Z', 'provider_authorization_number' => 'PRIVATE-AUTH-SENTINEL');
    $sequence = 0;
    $add = function (array $overrides = array(), array $remove = array()) use ($base, $store, &$sequence) {
        $ref = 'LMQ-20260820161331-' . sprintf('%08X', ++$sequence);
        $record = array_replace($base, $overrides);
        foreach ($remove as $field) { unset($record[$field]); }
        $record['reference'] = $ref;
        $store->records[$ref] = $record;
        return $ref;
    };
    $candidates = array($add(), $add(array('status' => 'ORDER_SENT')),
        $add(array('payment_verified' => 1, 'catalog_verified' => 1)),
        $add(array('payment_verified' => 'false')), $add(array('order_dispatch' => array())),
        $add(array('external_order_response' => null)), $add(array('provider_authorization_number' => 123456)),
        $add(array('expires_at' => '2020-01-01T00:00:00Z')));
    $excluded = array();
    foreach (array('PENDING', 'SUBMITTED', 'DECLINED', 'CANCELLED', 'EXPIRED', 'UNVERIFIED', 'ATTEMPT_EXPIRED') as $state) {
        $excluded[] = $add(array('status' => $state));
    }
    $excluded[] = $add(array('status' => 'ORDER_SENT'), array('environment', 'isolated_test'));
    $excluded[] = $add(array(), array('payment_verified'));
    $excluded[] = $add(array('isolated_test' => 'false'));
    $excluded[] = $add(array('environment' => 'test'));
    $excluded[] = $add(array('catalog_verified' => false));
    $excluded[] = $add(array('fulfillment_hold' => true));
    $excluded[] = $add(array('reconciliation' => array('fixture' => array('state' => 'OPEN'))));
    $excluded[] = $add(array('payment_conflicts' => array('fixture' => true)));
    $excluded[] = $add(array('provider_authorization_number' => '   '));
    $excluded[] = $add(array('provider_authorization_number' => array('invalid')));
    $excluded[] = $add(array('charge_group' => 'fixture-group'));
    $store->indexes['charge-group-fixture-group'] = array('state' => 'CONFLICT');
    foreach (array('SENDING', 'UNKNOWN', 'CONFIRMED') as $state) { $excluded[] = $add(array('order_dispatch' => array('state' => $state))); }
    foreach (array(array(), false, array('success' => false)) as $response) { $excluded[] = $add(array('external_order_response' => $response)); }
    $confirmed = array('success' => true, 'idcotizacion' => 987);
    $mailRef = $add(array('status' => 'ORDER_SENT', 'external_order_response' => $confirmed));
    $mailReleaseRef = $add(array('external_order_response' => $confirmed, 'order_dispatch' => array('state' => 'CONFIRMED_HELD')));
    $excluded[] = $add(array('status' => 'ORDER_SENT', 'external_order_response' => $confirmed, 'order_email' => array('state' => 'FAILED')));
    $pendingRef = $add(array('status' => 'PENDING'));
    $pendingPaid = $add();
    foreach (array($pendingRef, $pendingPaid) as $ref) {
        $store->events[$ref . '-' . hash('sha256', $ref)] = array('state' => 'RECEIVED', 'event' => array('status' => 'APPROVED'));
    }
    $excluded[] = $pendingPaid;
    $fingerprint = hash('sha256', serialize(array($store->records, $store->events, $store->indexes)));
    $report = $service->cronPreview();
    $check($report['complete'] && $report['errors'] === 0, 'all fixtures read completely');
    $check($report['order_candidates'] === 8, 'actual nonempty gates include truthy flags, empty journals and expired PAID records');
    $check($report['mail_candidates'] === 2, 'confirmed orders can attempt mail without resending quotations');
    $check($report['pending_events'] === 2 && !$report['no_automatic_sends_observed'], 'pending inbox prevents a clear result without applying any event');
    $orderRows = array_column(array_filter($report['rows'], function ($row) { return $row['reason'] === 'order_send_candidate'; }), 'reference');
    foreach ($candidates as $ref) { $check(in_array($ref, $orderRows, true), 'potential quotation candidate visible ' . $ref); }
    foreach ($excluded as $ref) { $check(!in_array($ref, $orderRows, true), 'blocked or previously attempted reference not a quotation candidate ' . $ref); }
    $check(!in_array($mailRef, $orderRows, true) && !in_array($mailReleaseRef, $orderRows, true), 'existing confirmed quotation is never a new order candidate');
    $check(count(array_filter($report['rows'], function ($row) { return $row['reason'] === 'non_boolean_verification_flags'; })) === 2, 'noncanonical persisted flags cannot hide candidates');
    $check(strpos(json_encode($report), 'PRIVATE-AUTH-SENTINEL') === false, 'authorization values never returned');
    $check(!$report['production_authorized'] && $report['authorization_is_boolean']
        && getenv('LM_PAYMENT_PRODUCTION_CONFIRMED') === $beforeConfirmed, 'preview never authorizes the worker');
    $check(hash('sha256', serialize(array($store->records, $store->events, $store->indexes))) === $fingerprint, 'all payment data, events and indexes unchanged');
    $limited = $service->cronPreview(1);
    $check(!$limited['complete'] && !$limited['no_automatic_sends_observed'], 'bounded scan cannot report a clean queue');
    $store->records['LMQ-20260820161331-FFFFFFFF'] = 'corrupt';
    $store->events['LMQ-20260820161331-EEEEEEEE-' . str_repeat('a', 64)] = 'corrupt';
    $broken = $service->cronPreview();
    $check(!$broken['complete'] && $broken['errors'] === 2 && !$broken['no_automatic_sends_observed'], 'unreadable storage prevents a definitive result');
    $check(strpos(json_encode($broken), 'PRIVATE-ERROR-SENTINEL') === false, 'storage error text remains private');
    $cronPreview = $report;
    $section = 'cron_check'; $detail = null; $detailError = ''; $selectedReference = ''; $flash = '';
    $actor = 'fixture.operator'; $settings = array('url_path' => '/admin/conciliacion/'); $nonce = 'fixture-nonce';
    $canLink = false; $reviewToken = ''; $page = array('items' => array(), 'next' => '', 'unreadable' => array());
    $escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    ob_start(); require dirname(__DIR__, 3) . '/admin/conciliacion/view.php'; $html = ob_get_clean();
    $check(strpos($html, 'data-screen="cron_check"') !== false && strpos($html, 'id="cron-order-count">8') !== false, 'read-only report renders actual candidate count');
    $check(strpos($html, 'PRIVATE-AUTH-SENTINEL') === false && strpos($html, 'name="review_token"') === false, 'report HTML exposes neither secrets nor mutation forms');
    $store->records = array(); $store->events = array(); $store->indexes = array();
    $empty = $service->cronPreview();
    $check($empty['complete'] && $empty['no_automatic_sends_observed'], 'empty storage is distinguished from an incomplete read');
    foreach (array('cronPreview', 'cronEligibility') as $method) {
        $reflection = new ReflectionMethod(ReconciliationAdminService::class, $method);
        $source = implode('', array_slice(file($reflection->getFileName()), $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
        foreach (array('PendingProcessor::run', 'new OrderFulfillment', '->process(', '->save(', '->writeIndex(', '->saveEvent(', '->withLock(', '->apply(', 'putenv(', 'mail(', 'curl_exec(') as $forbidden) {
            $check(strpos($source, $forbidden) === false, 'preview cannot call a worker, transport or business write: ' . $forbidden);
        }
    }
    $auth->close();
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION = array(); session_destroy(); }
    $resolved = realpath($root);
    $temp = str_replace('\\', '/', realpath(sys_get_temp_dir()));
    if ($resolved !== $canonical || !preg_match('/^lm-cron-review-[a-f0-9]{16}$/D', basename($root))
        || strpos(str_replace('\\', '/', $resolved), rtrim($temp, '/') . '/') !== 0) { throw new RuntimeException('Unsafe fixture cleanup refused.'); }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($root);
}
echo 'OK: ' . $checks . ' read-only Cron queue assertions; no processor, callbacks, orders or mail invoked.' . PHP_EOL;
