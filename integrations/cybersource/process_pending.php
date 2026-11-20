<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/Autoload.php';

use LM\CyberSource\Config;
use LM\CyberSource\PendingProcessor;

try {
    $config = Config::forProcess(dirname(__DIR__, 2));
    $failures = PendingProcessor::run($config, $argv);
    echo 'Procesador finalizado; pendientes: ' . $failures . PHP_EOL;
    exit($failures ? 2 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, "Procesador bloqueado o interrumpido. Revise autorizacion y estado durable antes de reintentar.\n");
    exit(1);
}
