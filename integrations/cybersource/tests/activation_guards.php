<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
// No signatures, payment references, callbacks, fulfillment, HTTP or mail.
require dirname(__DIR__) . '/Autoload.php';
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) {
        throw new ErrorException($message, 0, $severity, $file, $line);
    }
    return false;
});

$assertions = 0;
function activationCheck($ok, $label)
{
    global $assertions;
    $assertions++;
    if (!$ok) {
        throw new RuntimeException('FAIL: ' . $label);
    }
}
function activationRejects(callable $callback, $label, $expected = null)
{
    try {
        $callback();
    } catch (\LM\CyberSource\GatewayException $e) {
        activationCheck($expected === null || $e->getMessage() === $expected, $label);
        return;
    }
    activationCheck(false, $label);
}
class ActivationUntouchedStore extends \LM\CyberSource\PaymentSessionStore
{
    public $calls = 0;
    public function __construct() {}
    public function withLock($key, callable $callback, $timeoutSeconds = 2)
    {
        $this->calls++;
        throw new LogicException('Unauthorized creation reached storage.');
    }
}

$savedIsolation = getenv('LM_PAYMENT_ISOLATED_TEST');
$savedConfirmed = getenv('LM_PAYMENT_PRODUCTION_CONFIRMED');
$savedArgv = $GLOBALS['argv'];
$hadHost = isset($_SERVER['HTTP_HOST']);
$savedHost = $hadHost ? $_SERVER['HTTP_HOST'] : null;
$dir = sys_get_temp_dir() . '/lm-cybersource-activation-' . bin2hex(random_bytes(8));
mkdir($dir, 0700);
file_put_contents($dir . '/fixture-only', 'lock-test-only');
$child = null;
$pipes = array();
try {
    putenv('LM_PAYMENT_ISOLATED_TEST=0');
    putenv('LM_PAYMENT_PRODUCTION_CONFIRMED');
    foreach (array('production', 'prod') as $environment) {
        foreach (array(false, 'false', 0, null) as $authorization) {
            $config = new \LM\CyberSource\Config(array('environment' => $environment,
                'execution' => array('production_authorized' => $authorization),
                'secure_acceptance' => array('production_endpoint' => 'https://secureacceptance.cybersource.com/pay')));
            activationRejects(function () use ($config) { \LM\CyberSource\SideEffectPolicy::assertCheckout($config); }, 'checkout remains blocked');
            activationRejects(function () use ($config) {
                \LM\CyberSource\SideEffectPolicy::assertOrderTransport($config, 'https://example.invalid/orders');
            }, 'all order methods remain blocked');
            $store = new ActivationUntouchedStore();
            $coordinator = new \LM\CyberSource\CheckoutCoordinator($config, $store);
            activationRejects(function () use ($coordinator) { $coordinator->create(array(), 'guard-only'); }, 'creation rejects before storage');
            activationCheck($store->calls === 0, 'no payment attempt or lock was created');
        }
    }
    putenv('LM_PAYMENT_ISOLATED_TEST=1');
    $GLOBALS['argv'] = array('guard-test');
    $message = 'El aislamiento TEST no puede usarse en el dominio o procesador productivo.';
    foreach (array('libreriamarquense.com', 'www.libreriamarquense.com', 'LIBRERIAMARQUENSE.COM:443') as $host) {
        $_SERVER['HTTP_HOST'] = $host;
        activationRejects(function () use ($dir) { \LM\CyberSource\Config::forProcess($dir); }, 'production host rejects TEST before loading fixtures', $message);
    }
    unset($_SERVER['HTTP_HOST']);
    $GLOBALS['argv'] = array('guard-test', '--allow-production');
    activationRejects(function () use ($dir) { \LM\CyberSource\Config::forProcess($dir); }, 'production argument rejects TEST', $message);
    $GLOBALS['argv'] = array('guard-test');
    putenv('LM_PAYMENT_PRODUCTION_CONFIRMED=1');
    activationRejects(function () use ($dir) { \LM\CyberSource\Config::forProcess($dir); }, 'production environment rejects TEST', $message);
    putenv('LM_PAYMENT_ISOLATED_TEST=0');
    putenv('LM_PAYMENT_PRODUCTION_CONFIRMED');

    $command = '"' . PHP_BINARY . '" -n -d allow_url_fopen=0 -d disable_functions=mail,curl_exec "'
        . __DIR__ . '/activation_lock_worker.php" "' . $dir . '"';
    $child = proc_open($command, array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
        $pipes, null, null, array('bypass_shell' => true));
    activationCheck(is_resource($child), 'lock-only child started');
    fclose($pipes[0]);
    $deadline = microtime(true) + 3;
    while (!is_file($dir . '/ready') && microtime(true) < $deadline) {
        usleep(10000);
    }
    activationCheck(is_file($dir . '/ready'), 'independent process holds global lock');
    $lockStore = new \LM\CyberSource\PaymentSessionStore($dir);
    $called = false;
    $start = microtime(true);
    activationRejects(function () use ($lockStore, &$called) {
        $lockStore->withLock('pending-worker-global', function () use (&$called) { $called = true; }, 0);
    }, 'another sweep cannot enter the same global lock');
    activationCheck(!$called && microtime(true) - $start < 1, 'contention exits without running the callback');
    file_put_contents($dir . '/stop', 'release');
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($child);
    $child = null;
    activationCheck($exit === 0 && $output === 'LOCK_RELEASED' && $errors === '', 'lock-only child released cleanly');
    activationCheck($lockStore->withLock('pending-worker-global', function () { return 'released'; }, 0) === 'released',
        'global lock is reusable after process completion');
    activationCheck(!(glob($dir . '/*.json') ?: array()), 'no payment/session/order records created');

    $checkoutSource = file_get_contents(dirname(__DIR__, 3) . '/api/cybersource/create_checkout.php');
    $policyPosition = strpos($checkoutSource, 'SideEffectPolicy::assertCheckout($config)');
    $inputPosition = strpos($checkoutSource, '$input = lm_read_json_body()');
    activationCheck($policyPosition !== false && $inputPosition !== false && $policyPosition < $inputPosition,
        'API checks authorization before reading the order or querying catalog');
    $workerSource = file_get_contents(dirname(__DIR__) . '/process_pending.php');
    $globalPosition = strpos($workerSource, "withLock('pending-worker-global'");
    $receiverPosition = strpos($workerSource, 'new SecureAcceptanceNotification');
    activationCheck($globalPosition !== false && $receiverPosition !== false && $globalPosition < $receiverPosition,
        'global worker lock wraps the entire sweep');
    $workerGuardPosition = strpos($workerSource, 'SideEffectPolicy::assertWorker');
    $storagePosition = strpos($workerSource, 'new PaymentSessionStore');
    activationCheck($workerGuardPosition !== false && $storagePosition !== false && $workerGuardPosition < $storagePosition,
        'worker authorization remains before storage initialization');
    $bootstrap = file_get_contents(dirname(__DIR__, 3) . '/api/cybersource/_bootstrap.php');
    activationCheck(strpos($bootstrap, 'catch (Throwable $e)') !== false
        && strpos($bootstrap, "El servicio de pedidos no esta disponible en este momento.") !== false,
        'bootstrap failures return a generic response');
    echo 'OK: ' . $assertions . ' activation guard/lock assertions; no payments, attempts, callbacks, orders or mail.' . PHP_EOL;
} finally {
    if (is_resource($child)) {
        file_put_contents($dir . '/stop', 'release');
        foreach ($pipes as $pipe) {
            if (is_resource($pipe)) { fclose($pipe); }
        }
        proc_close($child);
    }
    putenv($savedIsolation === false ? 'LM_PAYMENT_ISOLATED_TEST' : 'LM_PAYMENT_ISOLATED_TEST=' . $savedIsolation);
    putenv($savedConfirmed === false ? 'LM_PAYMENT_PRODUCTION_CONFIRMED' : 'LM_PAYMENT_PRODUCTION_CONFIRMED=' . $savedConfirmed);
    $GLOBALS['argv'] = $savedArgv;
    if ($hadHost) { $_SERVER['HTTP_HOST'] = $savedHost; } else { unset($_SERVER['HTTP_HOST']); }
    if (realpath(dirname($dir)) === realpath(sys_get_temp_dir())
        && preg_match('/^lm-cybersource-activation-[a-f0-9]{16}$/D', basename($dir)) && is_file($dir . '/fixture-only')) {
        foreach (glob($dir . '/*') ?: array() as $file) {
            if (is_file($file)) { unlink($file); }
        }
        rmdir($dir);
    }
}
