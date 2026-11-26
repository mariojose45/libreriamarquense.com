<?php

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/Autoload.php';

use LM\CyberSource\Config;
use LM\CyberSource\ManualReconciliation;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\ReconciliationAdminAuth;
use LM\CyberSource\ReconciliationAdminService;
use LM\CyberSource\ReconciliationAdminStore;

// Read-only administrative queries against synthetic files, never payment processing.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
final class IncidentFixtureStore extends PaymentSessionStore
{
    public $eventScans = 0;
    public $indexReads = 0;
    public function hasPendingEvents($reference) { $this->eventScans++; return parent::hasPendingEvents($reference); }
    public function readIndex($key) { $this->indexReads++; return parent::readIndex($key); }
}
$checks = 0;
$check = function ($condition, $label) use (&$checks) {
    $checks++;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
};
$root = sys_get_temp_dir() . '/lm-admin-incidents-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
mkdir($root . '/users', 0700);
$canonical = realpath($root);
try {
    $password = 'Synthetic-incidents-' . bin2hex(random_bytes(16));
    $account = ReconciliationAdminStore::account('fixture.operator', $password);
    file_put_contents($root . '/users/fixture.operator.json', json_encode($account));
    chmod($root . '/users/fixture.operator.json', 0600);
    $private = new ReconciliationAdminStore($root, array(dirname(__DIR__, 3)));
    $auth = new ReconciliationAdminAuth($private);
    $auth->start();
    $config = new Config(array('environment' => 'production', 'execution' => array('production_authorized' => false)));
    $store = new IncidentFixtureStore($root . '/payments');
    $service = new ReconciliationAdminService($config, array('allow_manual_links' => false), $store,
        new ManualReconciliation($store, $config), $private, $auth);
    $denied = false;
    try { $service->pending('', 'incidents'); } catch (Throwable $e) { $denied = true; }
    $check($denied, 'incident listing requires a current administrator');
    $auth->login('fixture.operator', $password, '192.0.2.12');
    $records = array(
        array('status' => 'REVIEW', 'payment_verified' => false,
            'reconciliation' => array('fixture' => array('state' => 'OPEN', 'reasons' => array('missing_or_unsigned_auth_code'),
                'transaction_id' => 'PRIVATE-SENTINEL'))),
        array('status' => 'ORDER_SENT', 'payment_verified' => true,
            'external_order_response' => array('success' => true, 'idcotizacion' => 11), 'order_email' => array('state' => 'FAILED')),
        array('status' => 'ORDER_SENT', 'payment_verified' => true,
            'external_order_response' => array('success' => true, 'idcotizacion' => 12), 'order_email' => array('state' => 'SENT')),
        array('status' => 'PAID', 'payment_verified' => true, 'order_dispatch' => array('state' => 'UNKNOWN')),
        array('status' => 'PAID', 'payment_verified' => true, 'order_dispatch' => array('state' => 'SENDING')),
        array('status' => 'SUBMITTED', 'expires_at' => gmdate('c', time() - 3600)),
        array('status' => 'PENDING', 'expires_at' => gmdate('c', time() - 3600)),
        array('status' => 'DECLINED'), array('status' => 'CANCELLED'), array('status' => 'EXPIRED'),
        array('status' => 'PENDING', 'expires_at' => gmdate('c', time() + 3600)),
        array('status' => 'ORDER_SENT', 'payment_verified' => true,
            'external_order_response' => array('success' => true, 'idcotizacion' => 13), 'order_email' => array('state' => 'UNKNOWN')),
        array('status' => 'PAID', 'payment_verified' => true, 'order_dispatch' => array('state' => 'CONFIRMED_HELD')),
    );
    $references = array();
    $hashes = array();
    foreach ($records as $i => $record) {
        $ref = 'LMQ-20261126000000-' . sprintf('%08X', $i);
        $record += array('reference' => $ref, '_revision' => 1, 'amount' => 30, 'currency' => 'GTQ',
            'environment' => 'production', 'catalog_verified' => true, 'created_at' => '2026-11-26T00:00:00Z');
        $file = $root . '/payments/' . $ref . '.json';
        file_put_contents($file, json_encode($record));
        $references[] = $ref;
        $hashes[$file] = hash_file('sha256', $file);
    }
    $normal = array_column($service->pending()['items'], 'reference');
    $check(!in_array($references[0], $normal, true) && !in_array($references[1], $normal, true),
        'reproduce the visibility gap in the original approved-only listing');
    $page = $service->pending('', 'incidents');
    $actual = array_column($page['items'], 'reference');
    foreach (array(0, 1, 3, 4, 5, 6, 11, 12) as $i) {
        $check(in_array($references[$i], $actual, true), 'visible incident fixture ' . $i);
    }
    foreach (array(2, 7, 8, 9, 10) as $i) {
        $check(!in_array($references[$i], $actual, true), 'normal finalized or active attempt not an incident ' . $i);
    }
    $check($store->eventScans === 0 && $store->indexReads === 0, 'summary does not reintroduce per-record event/index scans');
    $check(!$service->canLink(), 'production and panel mutation guards remain closed');
    $detail = $service->detail($references[0]);
    $check(in_array('missing_or_unsigned_auth_code', $detail['review_reasons'], true), 'safe financial review reason visible');
    $check($service->detail($references[1])['email_state'] === 'FAILED', 'mail failure visible without resending');
    $check(strpos(json_encode(array($page, $detail)), 'PRIVATE-SENTINEL') === false, 'no raw financial receipt or transaction details rendered');
    foreach ($hashes as $file => $hash) { $check(hash_file('sha256', $file) === $hash, 'queries never mutate payment data'); }
    $changed = json_decode(file_get_contents($root . '/payments/' . $references[1] . '.json'), true);
    $changed['order_email']['state'] = 'SENT';
    file_put_contents($root . '/payments/' . $references[1] . '.json', json_encode($changed));
    $check(!in_array($references[1], array_column($service->pending('', 'incidents')['items'], 'reference'), true),
        'fresh state appears immediately without cache');
    $denied = false;
    try { $service->pending('', 'invalid'); } catch (Throwable $e) { $denied = true; }
    $check($denied, 'only explicit list modes accepted');
    for ($i = 100; $i < 130; $i++) {
        $ref = 'LMQ-20261126000000-' . sprintf('%08X', $i);
        file_put_contents($root . '/payments/' . $ref . '.json', json_encode(array('status' => 'REVIEW', 'amount' => 30)));
    }
    $first = $service->pending('', 'incidents');
    $second = $service->pending($first['next'], 'incidents');
    $check(count($first['items']) === 25 && $first['next'] !== '' && count($second['items']) > 0, 'incident pagination remains bounded');
    $check(array_intersect(array_column($first['items'], 'reference'), array_column($second['items'], 'reference')) === array(),
        'pagination does not repeat records');
    $auth->close();
} finally {
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION = array(); session_destroy(); }
    $resolved = realpath($root);
    $temp = str_replace('\\', '/', realpath(sys_get_temp_dir()));
    if ($resolved !== $canonical || !preg_match('/^lm-admin-incidents-[a-f0-9]{16}$/D', basename($root))
        || strpos(str_replace('\\', '/', $resolved), rtrim($temp, '/') . '/') !== 0) { throw new RuntimeException('Unsafe cleanup refused.'); }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($root);
}
echo 'OK: ' . $checks . ' read-only incident assertions; no external calls or commercial operations.' . PHP_EOL;
