<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/Autoload.php';

use LM\CyberSource\Config;
use LM\CyberSource\ManualReconciliation;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\ReconciliationAdminAuth;
use LM\CyberSource\ReconciliationAdminService;
use LM\CyberSource\ReconciliationAdminStore;
use LM\CyberSource\ReconciliationAdminMetrics;

// Synthetic files only. No payment, reconciliation apply, ERP or mail execution.
final class AdminPerformanceStore extends PaymentSessionStore
{
    public $reads = 0;
    public $indexes = 0;
    public $eventScans = 0;
    public function find($reference) { $this->reads++; return parent::find($reference); }
    public function readIndex($key) { $this->indexes++; return parent::readIndex($key); }
    public function hasPendingEvents($reference) { $this->eventScans++; return parent::hasPendingEvents($reference); }
}

$root = sys_get_temp_dir() . '/lm-admin-performance-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
mkdir($root . '/users', 0700);
mkdir($root . '/payments', 0700);
$canonical = realpath($root);
$result = array();
$checks = 0;
$check = function ($condition, $label) use (&$checks) {
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++;
};
try {
    $password = 'Synthetic-benchmark-' . bin2hex(random_bytes(16));
    $account = ReconciliationAdminStore::account('fixture.operator', $password);
    file_put_contents($root . '/users/fixture.operator.json', json_encode($account));
    chmod($root . '/users/fixture.operator.json', 0600);
    $start = hrtime(true);
    $private = new ReconciliationAdminStore($root, array(dirname(__DIR__, 3)));
    $result['private_init_ms'] = (hrtime(true) - $start) / 1000000;
    $auth = new ReconciliationAdminAuth($private);
    $start = hrtime(true);
    $auth->start();
    $result['session_open_ms'] = (hrtime(true) - $start) / 1000000;
    $start = hrtime(true);
    $auth->login('fixture.operator', $password, '192.0.2.10');
    $result['login_ms'] = (hrtime(true) - $start) / 1000000;
    $config = new Config(array('environment' => 'production', 'execution' => array('production_authorized' => false)));
    $store = new AdminPerformanceStore($root . '/payments');
    $service = new ReconciliationAdminService($config, array('allow_manual_links' => false), $store,
        new ManualReconciliation($store, $config), $private, $auth);
    $measure = function () use ($service, $auth, $store) {
        $times = array();
        for ($n = 0; $n < 3; $n++) {
            $store->reads = $store->indexes = $store->eventScans = 0;
            $start = hrtime(true);
            $auth->current();
            $service->canLink();
            $page = $service->pending();
            $times[] = (hrtime(true) - $start) / 1000000;
        }
        sort($times);
        return array('median_dashboard_ms' => $times[1], 'min_ms' => $times[0], 'max_ms' => $times[2],
            'session_reads' => $store->reads, 'index_reads' => $store->indexes, 'event_directory_scans' => $store->eventScans,
            'pending' => count($page['items']), 'unreadable' => count($page['unreadable']));
    };
    $result['empty'] = $measure();
    for ($i = 0; $i < 500; $i++) {
        $reference = 'LMQ-20260101000000-' . sprintf('%08X', $i);
        $group = hash('sha256', 'fixture-' . $i);
        $pending = $i >= 490;
        $record = array('reference' => $reference, '_revision' => 1, 'status' => $pending ? 'PAID' : 'ORDER_SENT',
            'payment_verified' => true, 'amount' => 30, 'currency' => 'GTQ', 'environment' => 'production',
            'created_at' => '2026-01-01T00:00:00Z', 'charge_group' => $group,
            'order_dispatch' => array('state' => $pending ? 'UNKNOWN' : 'CONFIRMED'));
        if (!$pending) { $record['external_order_response'] = array('success' => true, 'idcotizacion' => $i + 1); }
        file_put_contents($root . '/payments/' . $reference . '.json', json_encode($record));
        file_put_contents($root . '/payments/index-' . hash('sha256', 'charge-group-' . $group) . '.json', json_encode(array('state' => 'VERIFIED')));
        for ($j = 0; $j < 4; $j++) {
            file_put_contents($root . '/payments/event-' . $reference . '-' . hash('sha256', 'fixture-' . $j) . '.json', '{"state":"APPLIED"}');
        }
    }
    $result['synthetic_records'] = 500;
    $result['synthetic_event_files'] = 2000;
    $result['populated'] = $measure();
    $check($result['populated']['pending'] === 10 && $store->indexes === 0 && $store->eventScans === 0, 'summary avoids unrelated index and event reads');
    $parityStore = new AdminPerformanceStore($root . '/parity');
    $manual = new ManualReconciliation($parityStore, $config);
    $parity = new ReconciliationAdminService($config, array('allow_manual_links' => false), $parityStore, $manual, $private, $auth);
    $expected = array();
    $records = array(
        array('status' => 'PAID', 'payment_verified' => true),
        array('status' => 'ORDER_SENT', 'payment_verified' => true, 'external_order_response' => array('success' => true, 'idcotizacion' => 123)),
        array('status' => 'ORDER_SENT', 'payment_verified' => true, 'external_order_response' => array('success' => false)),
        array('status' => 'PAID', 'payment_verified' => true, 'external_order_response' => array('success' => true, 'idcotizacion' => '456')),
        array('status' => 'REVIEW', 'payment_verified' => false),
        array('status' => 'CANCELLED'),
        array('status' => 'PAID', 'payment_verified' => 1),
        array('status' => 'PAID', 'payment_verified' => true, 'amount' => 'invalid'),
        array('status' => 'PAID', 'payment_verified' => true, 'order_dispatch' => array('state' => 'SENDING')),
    );
    $pendingRef = '';
    foreach ($records as $i => $record) {
        $ref = 'LMQ-20261124000000-' . sprintf('%08X', $i);
        $record += array('amount' => 30, 'currency' => 'GTQ', '_revision' => 1);
        file_put_contents($root . '/parity/' . $ref . '.json', json_encode($record));
        try {
            $summary = $manual->describe($ref);
            if ($summary['approved_without_confirmed_order']) {
                $expected[] = array_intersect_key($summary, array_flip(array('reference', 'status', 'amount', 'currency', 'dispatch', 'created_at')));
            }
        } catch (Throwable $e) { /* An invalid amount is compared through the unreadable list below. */ }
        if ($i === 0) { $pendingRef = $ref; }
    }
    $page = $parity->pending();
    $check($page['items'] == $expected, 'list matches original eligibility and display fields');
    $check(count($page['unreadable']) === 1, 'invalid payment data remains visible as unreadable');
    $changed = $parityStore->find($pendingRef);
    $changed['external_order_response'] = array('success' => true, 'idcotizacion' => 789);
    $changed['status'] = 'ORDER_SENT';
    file_put_contents($root . '/parity/' . $pendingRef . '.json', json_encode($changed));
    $check(count($parity->pending()['items']) === count($expected) - 1, 'freshly confirmed fixture disappears without cache');
    $changed['status'] = 'PAID';
    unset($changed['external_order_response']);
    file_put_contents($root . '/parity/' . $pendingRef . '.json', json_encode($changed));
    $check(count($parity->pending()['items']) === count($expected), 'newly pending fixture is immediately visible');
    $parityStore->eventScans = 0;
    $parity->detail($pendingRef);
    $check($parityStore->eventScans === 1, 'detail still runs the original complete checks');
    file_put_contents($root . '/parity/event-' . $pendingRef . '-' . str_repeat('a', 64) . '.json', 'invalid');
    $denied = false;
    try { $parity->detail($pendingRef); } catch (Throwable $e) { $denied = true; }
    $check($denied, 'corrupt event cannot bypass the full detail checks');

    $disabled = new ReconciliationAdminMetrics();
    $check($disabled->snapshot('dashboard') === null, 'instrumentation disabled by default');
    $check(glob($root . '/audit/performance-*.jsonl') === array(), 'disabled instrumentation creates no file');
    $metrics = new ReconciliationAdminMetrics(true);
    $timedPrivate = new ReconciliationAdminStore($root, array(dirname(__DIR__, 3)), $metrics);
    $timedAuth = new ReconciliationAdminAuth($timedPrivate);
    $timedAuth->requireUser();
    $snapshot = $metrics->snapshot('dashboard');
    $check(isset($snapshot['stages_ms']['account_lookup_ms']) && !isset($snapshot['stages_ms']['password_verify_ms']), 'refresh does not verify a bcrypt password');
    $check($timedPrivate->writePerformance($snapshot), 'safe private performance record');
    $unsafe = $snapshot;
    $unsafe['password'] = 'must-not-log';
    $check(!$timedPrivate->writePerformance($unsafe), 'unknown sensitive top-level key rejected');
    $unsafe = $snapshot;
    $unsafe['stages_ms']['cookie'] = 1;
    $check(!$timedPrivate->writePerformance($unsafe), 'unknown stage rejected');
    $unsafe = $snapshot;
    $unsafe['request_ms'] = 'must-not-log';
    $unsafe['stages_ms']['request_ms'] = 1;
    $check(!$timedPrivate->writePerformance($unsafe), 'request duration cannot be overridden by a stage');
    $log = file_get_contents(glob($root . '/audit/performance-*.jsonl')[0]);
    $check(strpos($log, $password) === false && strpos($log, $account['password_hash']) === false
        && strpos($log, 'fixture.operator') === false && strpos($log, 'LMQ-') === false, 'performance file contains no identity or payment details');
    $link = glob($root . '/audit/performance-*.jsonl')[0];
    $saved = $link . '.saved';
    rename($link, $saved);
    mkdir($link, 0700);
    $check(!$timedPrivate->writePerformance($snapshot), 'unavailable performance log fails softly');
    rmdir($link);
    rename($saved, $link);
    $handle = fopen($link, 'r+');
    ftruncate($handle, 5 * 1024 * 1024);
    fclose($handle);
    $check(!$timedPrivate->writePerformance($snapshot), 'daily performance file size limit');
    clearstatcache(true, $link);
    $check(filesize($link) === 5 * 1024 * 1024, 'size-limited log is not extended');
    $auth->close();
    $check(session_status() === PHP_SESSION_NONE && isset($_SESSION['admin']), 'session closes before rendering without discarding authentication');
    $auth->start();
    $check($auth->current() === 'fixture.operator', 'authentication persists after early close');
    $result['assertions'] = $checks;
    $result['php'] = PHP_VERSION;
    $result['real_commercial_operations'] = false;
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION = array(); session_destroy(); }
    $resolved = realpath($root);
    $temp = str_replace('\\', '/', realpath(sys_get_temp_dir()));
    if ($resolved !== $canonical || !preg_match('/^lm-admin-performance-[a-f0-9]{16}$/D', basename($root))
        || strpos(str_replace('\\', '/', $resolved), rtrim($temp, '/') . '/') !== 0) { throw new RuntimeException('Unsafe fixture cleanup refused.'); }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($root);
}
echo json_encode($result, JSON_PRETTY_PRINT), PHP_EOL;
