<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/Autoload.php';

use LM\CyberSource\Config;
use LM\CyberSource\ManualReconciliation;
use LM\CyberSource\PaymentSessionStore;
use LM\CyberSource\ReconciliationAdminAuth;
use LM\CyberSource\ReconciliationAdminService;
use LM\CyberSource\ReconciliationAdminStore;

// Only administrative fixtures and a no-op delegate. Never run fulfillment or real reconciliation.
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$checks = 0;
$check = function ($condition, $label) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
    $checks++;
};
$reject = function (callable $operation, $label) use ($check) {
    $denied = false;
    try { $operation(); } catch (Throwable $e) { $denied = true; }
    $check($denied, $label);
};

class AdminReadOnlyFixtureStore extends PaymentSessionStore
{
    public function __construct() {}
    public function references() { yield 'LMQ-20261124000000-AAAAAAAA'; }
    public function find($reference)
    {
        return array('reference' => $reference, 'status' => 'PAID', 'payment_verified' => true,
            'amount' => 30, 'currency' => 'GTQ', 'created_at' => '2026-11-24T00:00:00Z',
            'order_dispatch' => array('state' => 'UNKNOWN'));
    }
}

class AdminNoOpReview extends ManualReconciliation
{
    public $calls = array();
    public $fail = false;
    public function __construct() {}
    public function describe($reference, $details = false)
    {
        return array('reference' => $reference, 'revision' => 7, 'environment' => 'production',
            'status' => 'PAID', 'payment_verified' => true, 'amount' => '30.00', 'currency' => 'GTQ',
            'created_at' => '2026-11-24T00:00:00Z', 'dispatch' => 'UNKNOWN', 'quotation_id' => null,
            'approved_without_confirmed_order' => true, 'financial_review' => false,
            'charge_group_blocked' => false, 'pending_notifications' => false,
            'customer' => array('nombre_cliente' => 'Cliente Prueba'), 'items' => array(),
            'authorization_code' => 'MUST_NOT_RENDER', 'secret_key' => 'MUST_NOT_RENDER');
    }
    public function apply($reference, $action, $value, $evidence, array $review = array(), array $args = array())
    {
        $this->calls[] = array('reference' => $reference, 'action' => $action, 'value' => $value,
            'review' => $review, 'args' => $args, 'confirmed' => getenv('LM_PAYMENT_PRODUCTION_CONFIRMED'));
        if ($this->fail) { throw new RuntimeException('No-op delegate failure'); }
        return array();
    }
}

$root = sys_get_temp_dir() . '/lm-reconciliation-admin-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
mkdir($root . '/users', 0700);
$canonicalFixtureRoot = realpath($root);
$beforeEnvironment = getenv('LM_PAYMENT_PRODUCTION_CONFIRMED');
$beforeIsolation = getenv('LM_PAYMENT_ISOLATED_TEST');
$password = 'Synthetic-only-password-' . bin2hex(random_bytes(8));
$saveAccount = function (array $account) use ($root) {
    file_put_contents($root . '/users/operator.one.json', json_encode($account));
    chmod($root . '/users/operator.one.json', 0600);
};
try {
    $app = dirname(__DIR__, 3);
    $reject(function () use ($root) { new ReconciliationAdminStore($root, array($root)); }, 'storage cannot be public');
    $check(!ReconciliationAdminStore::validUsername('../operator'), 'username traversal');
    $check(!ReconciliationAdminStore::validPassword('short'), 'short password');
    $check(!ReconciliationAdminStore::validPassword(str_repeat('x', 73)), 'bcrypt byte limit');
    $account = ReconciliationAdminStore::account('operator.one', $password);
    $check(password_verify($password, $account['password_hash']), 'password hash');
    $check(password_get_info($account['password_hash'])['options']['cost'] === 12, 'bcrypt work factor');
    $saveAccount($account);
    $private = new ReconciliationAdminStore($root, array($app));
    $check($private->user('../operator') === null && $private->user('missing') === null, 'unknown accounts denied');
    $auth = new ReconciliationAdminAuth($private);
    $auth->start();
    $check(session_name() === '__Host-LMReconciliation', 'separate cookie');
    $cookies = session_get_cookie_params();
    $check($cookies['secure'] && $cookies['httponly'] && $cookies['samesite'] === 'Strict' && $cookies['path'] === '/' && $cookies['domain'] === '', 'cookie protection');
    $check(ini_get('session.save_handler') === 'files' && session_save_path() === $private->sessionDirectory(), 'private session handler and path');
    $check(ini_get('session.use_strict_mode') === '1' && ini_get('session.use_only_cookies') === '1', 'session strict mode');
    $reject(function () { ReconciliationAdminAuth::requireHttps(array('HTTPS' => 'off', 'HTTP_HOST' => 'libreriamarquense.com', 'HTTP_X_FORWARDED_PROTO' => 'https'), 'libreriamarquense.com'); }, 'forwarded HTTPS cannot bypass transport');
    $reject(function () { ReconciliationAdminAuth::requireHttps(array('HTTPS' => 'on', 'HTTP_HOST' => 'attacker.invalid'), 'libreriamarquense.com'); }, 'canonical host');
    $reject(function () { ReconciliationAdminAuth::requireHttps(array('HTTPS' => 'on', 'HTTP_HOST' => 'libreriamarquense.com', 'REQUEST_METHOD' => 'POST', 'HTTP_ORIGIN' => 'https://attacker.invalid'), 'libreriamarquense.com'); }, 'cross origin');
    ReconciliationAdminAuth::requireHttps(array('HTTPS' => 'on', 'HTTP_HOST' => 'libreriamarquense.com'), 'libreriamarquense.com');
    $check($auth->current() === null, 'no default login');
    $sentinelAccount = array_replace($account, array('username' => 'unknown'));
    file_put_contents($root . '/users/unknown.json', json_encode($sentinelAccount));
    chmod($root . '/users/unknown.json', 0600);
    $reject(function () use ($auth, $password) { $auth->login('../invalid', $password, '192.0.2.5'); }, 'invalid username never aliases a real sentinel account');
    $reject(function () use ($auth) { $auth->requireUser(); }, 'unauthenticated authorization');
    $reject(function () use ($auth) { $auth->requireCsrf('wrong'); }, 'CSRF rejection');
    $csrf = $auth->csrf();
    $auth->requireCsrf($csrf);
    $oldId = session_id();
    $auth->login('operator.one', $password, '192.0.2.1');
    $check($auth->current() === 'operator.one', 'individual account login');
    $check(session_id() !== $oldId && $auth->csrf() !== $csrf, 'session and CSRF rotation');
    $store = new AdminReadOnlyFixtureStore();
    $manual = new AdminNoOpReview();
    $blockedConfig = new Config(array('environment' => 'production', 'execution' => array('production_authorized' => false)));
    $allowedConfig = new Config(array('environment' => 'production', 'execution' => array('production_authorized' => true)));
    $off = new ReconciliationAdminService($blockedConfig, array('allow_manual_links' => true), $store, $manual, $private, $auth);
    $check(!$off->canLink(), 'production false is always read-only');
    $matches = array('customer_matches' => true, 'amount_matches' => true, 'items_match' => true, 'status_matches' => true, 'authorize_link' => true);
    $reject(function () use ($off, $auth, $matches) { $off->link($auth->csrf(), 'forged', '123', 'ticket-test-123', $matches); }, 'forged POST cannot override production false');
    $check(!$manual->calls, 'no delegate or financial writes while blocked');
    $check(getenv('LM_PAYMENT_PRODUCTION_CONFIRMED') === $beforeEnvironment, 'blocked action cannot set environment authorization');
    $moduleOff = new ReconciliationAdminService($allowedConfig, array('allow_manual_links' => false), $store, $manual, $private, $auth);
    $check(!$moduleOff->canLink(), 'independent panel switch');
    $stringConfig = new Config(array('environment' => 'production', 'execution' => array('production_authorized' => 'true')));
    $stringFlag = new ReconciliationAdminService($stringConfig, array('allow_manual_links' => true), $store, $manual, $private, $auth);
    $check(!$stringFlag->canLink(), 'production authorization must be a boolean');
    $stringModuleFlag = new ReconciliationAdminService($allowedConfig, array('allow_manual_links' => 'true'), $store, $manual, $private, $auth);
    $check(!$stringModuleFlag->canLink(), 'panel authorization must be a boolean');
    $allowed = new ReconciliationAdminService($allowedConfig, array('allow_manual_links' => true), $store, $manual, $private, $auth);
    $ref = 'LMQ-20261124000000-AAAAAAAA';
    $page = $off->pending();
    $check(count($page['items']) === 1 && $page['items'][0]['dispatch'] === 'UNKNOWN', 'read-only pending list');
    $detail = $off->detail($ref);
    $check(!array_key_exists('authorization_code', $detail) && !array_key_exists('secret_key', $detail), 'detail allowlist');
    $actor = 'operator.one';
    $settings = array('url_path' => '/admin/conciliacion/');
    $nonce = 'fixture-nonce';
    $flash = '<script>not-executable</script>';
    $detail['customer']['nombre_cliente'] = '<script>not-executable</script>';
    $canLink = false;
    $reviewToken = 'must-not-render-when-blocked';
    $escape = function ($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); };
    ob_start();
    require $app . '/admin/conciliacion/view.php';
    $html = ob_get_clean();
    $check(strpos($html, '<script>') === false && strpos($html, '&lt;script&gt;') !== false, 'HTML output escaping');
    $check(strpos($html, 'name="review_token"') === false && strpos($html, 'MUST_NOT_RENDER') === false, 'blocked UI and sensitive data protection');
    $token = $auth->issueReview($ref, 7);
    $reject(function () use ($allowed, $auth, $token, $matches) { $allowed->link('wrong', $token, '123', 'ticket-test-123', $matches); }, 'link needs CSRF');
    $reject(function () use ($allowed, $auth, $token, $matches) { $allowed->link($auth->csrf(), $token, '123', 'ticket-test-123', $matches); }, 'link requires password confirmation in same request');
    $auth->confirmPassword($password, '192.0.2.1');
    $token = $auth->issueReview($ref, 7);
    $mismatch = array_replace($matches, array('amount_matches' => false));
    $reject(function () use ($allowed, $auth, $token, $mismatch) { $allowed->link($auth->csrf(), $token, '123', 'ticket-test-123', $mismatch); }, 'all correspondence confirmations required');
    $auth->confirmPassword($password, '192.0.2.1');
    $token = $auth->issueReview($ref, 7);
    $_SESSION['review']['at'] = time() - 301;
    $reject(function () use ($allowed, $auth, $token, $matches) { $allowed->link($auth->csrf(), $token, '123', 'ticket-test-123', $matches); }, 'stale review');
    $auth->confirmPassword($password, '192.0.2.1');
    $token = $auth->issueReview($ref, 7);
    $_SESSION['review']['operator'] = 'another.operator';
    $reject(function () use ($allowed, $auth, $token, $matches) { $allowed->link($auth->csrf(), $token, '123', 'ticket-test-123', $matches); }, 'review belongs to the authenticated operator');
    $auth->confirmPassword($password, '192.0.2.1');
    $token = $auth->issueReview($ref, 7);
    $spoof = array_merge($matches, array('operator' => 'forged.operator', 'expected_revision' => 999));
    $allowed->link($auth->csrf(), $token, '123', 'ticket-test-123', $spoof);
    $call = $manual->calls[0];
    $check($call['review']['operator'] === 'operator.one' && $call['review']['expected_revision'] === 7, 'identity and revision come from server session');
    $check($call['action'] === 'link-order' && $call['args'] === array('--allow-production') && $call['confirmed'] === '1', 'existing authorization contract passed to no-op delegate');
    $check(getenv('LM_PAYMENT_PRODUCTION_CONFIRMED') === $beforeEnvironment, 'environment restored');
    $auth->confirmPassword($password, '192.0.2.1');
    $reject(function () use ($allowed, $auth, $token, $matches) { $allowed->link($auth->csrf(), $token, '456', 'ticket-test-456', $matches); }, 'consumed review prevents contradictory replay');
    $check(count($manual->calls) === 1, 'no repeated delegation');
    $auth->confirmPassword($password, '192.0.2.1');
    $token = $auth->issueReview($ref, 7);
    $manual->fail = true;
    $reject(function () use ($allowed, $auth, $token, $matches) { $allowed->link($auth->csrf(), $token, '123', 'ticket-test-123', $matches); }, 'delegate failure remains uncertain, no retry');
    $check(getenv('LM_PAYMENT_PRODUCTION_CONFIRMED') === $beforeEnvironment && count($manual->calls) === 2, 'failure restores environment with a single delegation');
    $_SESSION['admin']['last_seen'] = time() - 901;
    $check($auth->current() === null, 'idle timeout');
    $auth->login('operator.one', $password, '192.0.2.1');
    $_SESSION['admin']['created'] = time() - 28801;
    $check($auth->current() === null, 'absolute timeout');
    $auth->login('operator.one', $password, '192.0.2.1');
    $account['enabled'] = false;
    $saveAccount($account);
    $check($auth->current() === null, 'disabled account revokes session');
    $account['enabled'] = true;
    $saveAccount($account);
    $auth->login('operator.one', $password, '192.0.2.1');
    $account['role'] = 'customer';
    $saveAccount($account);
    $check($auth->current() === null, 'non-administrator role revoked');
    $account['role'] = 'reconciliation_admin';
    $saveAccount($account);
    $auth->login('operator.one', $password, '192.0.2.1');
    $account['session_version'] = bin2hex(random_bytes(16));
    $saveAccount($account);
    $check($auth->current() === null, 'session version revocation');
    $auth->login('operator.one', $password, '192.0.2.1');
    $auth->logout();
    $check($auth->current() === null && session_status() === PHP_SESSION_NONE, 'logout destroys only the administrative session');
    for ($i = 0; $i < 5; $i++) { $check($private->reserveAttempt('limited.user', '198.51.100.2'), 'rate reservation'); }
    $check(!$private->reserveAttempt('limited.user', '198.51.100.2'), 'persistent account attempt limit');
    $audit = implode('', array_map('file_get_contents', glob($root . '/audit/*.jsonl')));
    $check(strpos($audit, $password) === false && strpos($audit, $account['password_hash']) === false, 'audit excludes passwords and hashes');
    $check(strpos($audit, 'link_intent') !== false && strpos($audit, 'link_result') !== false, 'intent and outcome audit');
    putenv('LM_PAYMENT_ISOLATED_TEST=1');
    $reject(function () use ($allowedConfig, $store, $manual, $private, $auth) {
        new ReconciliationAdminService($allowedConfig, array(), $store, $manual, $private, $auth);
    }, 'isolated simulator forbidden');
} finally {
    putenv($beforeEnvironment === false ? 'LM_PAYMENT_PRODUCTION_CONFIRMED' : 'LM_PAYMENT_PRODUCTION_CONFIRMED=' . $beforeEnvironment);
    putenv($beforeIsolation === false ? 'LM_PAYMENT_ISOLATED_TEST' : 'LM_PAYMENT_ISOLATED_TEST=' . $beforeIsolation);
    if (session_status() === PHP_SESSION_ACTIVE) { $_SESSION = array(); session_destroy(); }
    $resolved = realpath($root);
    $temp = str_replace('\\', '/', realpath(sys_get_temp_dir()));
    if ($resolved !== $canonicalFixtureRoot || !preg_match('/^lm-reconciliation-admin-[a-f0-9]{16}$/D', basename($root))
        || strpos(str_replace('\\', '/', $resolved), rtrim($temp, '/') . '/') !== 0) {
        throw new RuntimeException('Unsafe fixture cleanup refused.');
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) { $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
    rmdir($root);
}
echo 'PASS: ' . $checks . " administrative security assertions; no commercial operations.\n";
