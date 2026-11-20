<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/Autoload.php';

$dir = isset($argv[1]) ? realpath($argv[1]) : false;
if (!$dir || realpath(dirname($dir)) !== realpath(sys_get_temp_dir())
    || !preg_match('/^lm-cybersource-activation-[a-f0-9]{16}$/D', basename($dir))
    || !is_file($dir . '/fixture-only')) {
    exit(1);
}
$store = new \LM\CyberSource\PaymentSessionStore($dir);
$store->withLock('pending-worker-global', function () use ($dir) {
    file_put_contents($dir . '/ready', 'lock-only');
    $deadline = microtime(true) + 5;
    while (!is_file($dir . '/stop') && microtime(true) < $deadline) {
        usleep(10000);
    }
});
echo 'LOCK_RELEASED';
