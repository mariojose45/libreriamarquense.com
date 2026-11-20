<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/Autoload.php';
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

// Metadata validation only: no payment records, callbacks, dispatch, mail or private config.
$checks = 0;
function manualCheck($condition, $label)
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}
function manualRejects(callable $callback, $label)
{
    try {
        $callback();
    } catch (\LM\CyberSource\GatewayException $e) {
        manualCheck(true, $label);
        return;
    }
    manualCheck(false, $label);
}

$valid = array('operator' => 'OPERATOR-01', 'expected_revision' => '7', 'customer_matches' => true,
    'amount_matches' => true, 'items_match' => true, 'status_matches' => true);
$review = \LM\CyberSource\ManualReconciliation::validateReview($valid);
manualCheck($review['expected_revision'] === 7, 'revision is normalized to an integer');
manualCheck($review['operator'] === 'OPERATOR-01', 'operator identity is retained');
foreach (array('customer_matches', 'amount_matches', 'items_match', 'status_matches') as $field) {
    foreach (array(null, false, 'true', 1, array()) as $invalid) {
        $data = array_replace($valid, array($field => $invalid));
        manualRejects(function () use ($data) { \LM\CyberSource\ManualReconciliation::validateReview($data); }, 'all matches must be explicit booleans');
    }
    $data = $valid;
    unset($data[$field]);
    manualRejects(function () use ($data) { \LM\CyberSource\ManualReconciliation::validateReview($data); }, 'missing confirmation rejects');
}
foreach (array(null, '', 0, -1, '01', '+1', '1.0', '1e2', '99999999999999999999999', true, array()) as $invalid) {
    $data = array_replace($valid, array('expected_revision' => $invalid));
    manualRejects(function () use ($data) { \LM\CyberSource\ManualReconciliation::validateReview($data); }, 'revision must be positive and exact');
}
foreach (array(null, '', 'ab', 'operator with spaces', "operator\n01", str_repeat('a', 65), array()) as $invalid) {
    $data = array_replace($valid, array('operator' => $invalid));
    manualRejects(function () use ($data) { \LM\CyberSource\ManualReconciliation::validateReview($data); }, 'operator must be a bounded identifier');
}
$withExtra = $valid + array('untrusted_extra' => 'must-not-persist');
manualCheck(!isset(\LM\CyberSource\ManualReconciliation::validateReview($withExtra)['untrusted_extra']), 'review uses an explicit allowlist');
$resolve = \LM\CyberSource\ManualReconciliation::validateReview(array('operator' => 'OPERATOR-01', 'expected_revision' => 1), false);
manualCheck(!$resolve['customer_matches'] && $resolve['expected_revision'] === 1, 'issue resolution still requires identity and revision without implying ERP matches');
\LM\CyberSource\ManualReconciliation::assertLinkIntent(null, '7');
manualCheck(true, 'an unreserved quotation may start a manual intent');
\LM\CyberSource\ManualReconciliation::assertLinkIntent(array('idcotizacion' => '7', 'state' => 'RESERVED'), '7');
manualCheck(true, 'an interrupted intent may resume only the same chosen identity');
foreach (array(array('idcotizacion' => '8', 'state' => 'RESERVED'), array('idcotizacion' => '7', 'state' => 'CONFIRMED'),
    array('idcotizacion' => '7'), array('state' => 'RESERVED'), array(), 'invalid', false) as $intent) {
    manualRejects(function () use ($intent) { \LM\CyberSource\ManualReconciliation::assertLinkIntent($intent, '7'); }, 'contradictory or incomplete manual intent rejects');
}

class ManualUntouchedStore extends \LM\CyberSource\PaymentSessionStore
{
    public $calls = 0;
    public function __construct() {}
    public function withLock($key, callable $callback, $timeoutSeconds = 2)
    {
        $this->calls++;
        throw new LogicException('Invalid review reached persistence.');
    }
}
$untouched = new ManualUntouchedStore();
$manual = new \LM\CyberSource\ManualReconciliation($untouched, new \LM\CyberSource\Config(array('environment' => 'production')));
manualRejects(function () use ($manual) { $manual->apply('not-a-payment', 'link-order', '1', 'TICKET-GUARD-ONLY'); }, 'link rejects without acknowledgements before storage');
manualRejects(function () use ($manual) { $manual->apply('not-a-payment', 'resolve', 'issue', 'TICKET-GUARD-ONLY'); }, 'resolve rejects without operator and revision before storage');
manualRejects(function () use ($manual, $valid) { $manual->apply('not-a-payment', 'resend', '1', 'TICKET-GUARD-ONLY', $valid); }, 'resend action does not exist');
manualRejects(function () use ($manual, $valid) {
    $manual->apply('not-a-payment', 'link-order', '1', 'TICKET-GUARD-ONLY', $valid, array('--allow-production'));
}, 'service itself rejects production mutation without configuration authorization');
manualCheck($untouched->calls === 0, 'no storage operation was attempted');

$source = file_get_contents(dirname(__DIR__) . '/src/ManualReconciliation.php');
$apply = substr($source, strpos($source, 'public function apply('));
manualCheck(strpos($apply, "withLock('fulfill-'") < strpos($apply, "withLock('session-'"), 'manual changes share fulfillment lock ordering');
manualCheck(strpos($apply, 'SideEffectPolicy::assertWorker') < strpos($apply, "withLock('fulfill-'"), 'service authorization is checked before persistence');
manualCheck(strpos($apply, "withLock('inbox-'") < strpos($apply, 'hasPendingEvents'), 'inbox check occurs under the inbox lock');
manualCheck(strpos($apply, "\$session['_revision'] !== \$review['expected_revision']") !== false, 'stale operator review is rejected');
manualCheck(strpos($apply, "'state' => 'RESERVED'") < strpos($apply, 'claimQuotation('), 'chosen quotation is journaled before the independent uniqueness index');
manualCheck(strpos($apply, 'self::assertLinkIntent($intent, $value)') !== false, 'interrupted manual link cannot change quotation identity');
manualCheck(strpos($apply, "['manual_link']['state'] = 'CONFIRMED'") !== false, 'completed manual link retains its durable intent');
foreach (array('new OrderForwarder', 'new OrderFulfillment', 'HttpsTransport::', 'curl_exec(', 'mail(') as $forbidden) {
    manualCheck(strpos($source, $forbidden) === false, 'operator service cannot call remote or mail transports');
}
$cli = file_get_contents(dirname(__DIR__) . '/reconcile.php');
manualCheck(strpos($cli, "'show'") !== false && strpos($cli, "'approved-unconfirmed'") !== false, 'operator views exist');
manualCheck(strpos($cli, 'SideEffectPolicy::assertWorker') < strpos($cli, '$review->apply('), 'CLI mutations retain the production authorization guard');
manualCheck(strpos($cli, '$modes !== 1') < strpos($cli, 'Config::forProcess'), 'ambiguous CLI operations fail before configuration');
manualCheck(strpos($cli, "PHP_SAPI !== 'cli'") < strpos($cli, '/Autoload.php'), 'operator tool remains inaccessible over HTTP');
echo 'OK: ' . $checks . ' manual review metadata/static assertions; no payments, orders, callbacks, storage or mail.' . PHP_EOL;
