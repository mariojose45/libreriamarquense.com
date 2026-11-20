<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// A CWP-only entry point: no shell, subprocesses, mutable argv or HTTP output.
ob_start(function ($output) { return ''; });
if (!function_exists('ini_set') || !function_exists('putenv')) {
    fwrite(STDERR, "Cron no disponible: consulte al administrador del hosting.\n");
    exit(1);
}
foreach (array('display_errors', 'display_startup_errors', 'log_errors') as $setting) {
    ini_set($setting, '0');
    if (!in_array(strtolower((string) ini_get($setting)), array('', '0', 'off'), true)) {
        fwrite(STDERR, "Cron bloqueado: no fue posible proteger la salida de errores.\n");
        exit(1);
    }
}

$logger = null;
$finished = false;
register_shutdown_function(function () use (&$logger, &$finished) {
    $error = error_get_last();
    if (!$finished && $logger && $error && in_array($error['type'],
        array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR), true)) {
        $logger->error('Cron interrumpido por error fatal; detalles omitidos por privacidad.');
    }
});

try {
    $root = realpath(dirname(__DIR__, 2));
    if ($root !== '/home/maxime/libreriamarquense.com' || PHP_VERSION_ID < 80300 || PHP_VERSION_ID >= 80400
        || (isset($argc) && $argc !== 1)) {
        throw new RuntimeException('Destino, version PHP o argumentos del lanzador no autorizados.');
    }
    $logPath = '/home/maxime/cron_cybersource.log';
    if (is_link($logPath) || (file_exists($logPath) && !is_file($logPath))) {
        throw new RuntimeException('Destino de log no permitido.');
    }
    $mask = umask(0077);
    try {
        $log = @fopen($logPath, 'ab');
    } finally {
        umask($mask);
    }
    if (!$log) {
        throw new RuntimeException('No se pudo abrir el log privado.');
    }
    try {
        $line = gmdate('c') . " CRON_START\n";
        if (!@chmod($logPath, 0640) || !flock($log, LOCK_EX | LOCK_NB)
            || fwrite($log, $line) !== strlen($line) || !fflush($log)) {
            throw new RuntimeException('Log privado no disponible.');
        }
    } finally {
        flock($log, LOCK_UN);
        fclose($log);
    }
    ini_set('error_log', $logPath);
    if (ini_get('error_log') !== $logPath) {
        throw new RuntimeException('No se pudo proteger el log de PHP.');
    }
    require_once __DIR__ . '/Autoload.php';
    $logger = new \LM\CyberSource\SecureLogger($logPath);
    $warningLogged = false;
    set_error_handler(function ($severity) use ($logger, &$warningLogged) {
        if ((error_reporting() & $severity) && !$warningLogged) {
            $warningLogged = true;
            $logger->warning('Cron: aviso PHP; contenido omitido por privacidad.');
        }
        return true;
    }, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED | E_STRICT);

    if (!putenv('LM_PAYMENT_ISOLATED_TEST=0') || !putenv('LM_PAYMENT_PRODUCTION_CONFIRMED=1')
        || getenv('LM_PAYMENT_ISOLATED_TEST') !== '0' || getenv('LM_PAYMENT_PRODUCTION_CONFIRMED') !== '1') {
        throw new RuntimeException('No se pudo declarar el entorno del procesador.');
    }
    $config = \LM\CyberSource\Config::forProcess($root);
    if (!in_array($config->get('environment'), array('production', 'prod'), true)
        || \LM\CyberSource\SideEffectPolicy::isolated($config)) {
        throw new RuntimeException('Este lanzador solo admite la configuracion productiva no aislada.');
    }
    // Application options, not process arguments: the shared core does not use getopt().
    $failures = \LM\CyberSource\PendingProcessor::run($config, array('--allow-production'), $logger);
    $logger->info('Cron finalizado', array('pending_count' => $failures));
    $finished = true;
    exit($failures ? 2 : 0);
} catch (Throwable $e) {
    $finished = true;
    if ($logger) {
        $logger->warning('Cron bloqueado o interrumpido; revise autorizacion y estado durable.',
            array('exception' => get_class($e)));
    } else {
        fwrite(STDERR, "Cron bloqueado: revise rutas, funciones y permisos privados con el administrador.\n");
    }
    exit(1);
}
