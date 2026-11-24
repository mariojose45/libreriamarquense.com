<?php

// Emulates TLS termination only for a loopback fixture. This is not an HTTPS certification.
if (PHP_SAPI !== 'cli-server' || !preg_match('/^lm-admin-http-[a-f0-9]{32}$/D', basename(__DIR__))
    || !is_file(__DIR__ . '/fixture-only') || !isset($_SERVER['REMOTE_ADDR'])
    || $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') { http_response_code(404); exit; }
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (!in_array($path, array('/admin/conciliacion/', '/admin/conciliacion/index.php'), true)) {
    http_response_code(404);
    exit;
}
$_SERVER['HTTPS'] = 'on';
$_SERVER['HTTP_HOST'] = 'libreriamarquense.com';
require __DIR__ . '/site/admin/conciliacion/index.php';
