<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
ini_set('display_errors', '0');
ini_set('log_errors', '0');

try {
    $options = getopt('', array('output:'), $rest);
    if ($rest !== count($argv) || count($options) !== 1 || !isset($options['output']) || !is_string($options['output'])) {
        throw new RuntimeException('Indique --output con un archivo nuevo fuera del proyecto.');
    }
    require dirname(__DIR__, 2) . '/integrations/cybersource/Autoload.php';
    $raw = stream_get_contents(STDIN, 16385);
    if (!is_string($raw) || strlen($raw) > 16384) {
        throw new RuntimeException('Entrada no valida.');
    }
    $input = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    if (!is_array($input) || !isset($input['username'], $input['password'], $input['confirmation'])
        || !is_string($input['password']) || !is_string($input['confirmation'])
        || !hash_equals($input['password'], $input['confirmation'])) {
        throw new RuntimeException('Confirme la contrasena.');
    }
    $account = \LM\CyberSource\ReconciliationAdminStore::account($input['username'], $input['password']);
    $parent = realpath(dirname($options['output']));
    $publicParent = realpath(dirname(__DIR__, 3));
    $normalize = function ($path) { return strtolower(rtrim(str_replace('\\', '/', (string) $path), '/')); };
    if (!$parent || !$publicParent || $normalize($parent) === $normalize($publicParent)
        || strpos($normalize($parent), $normalize($publicParent) . '/') === 0
        || basename($options['output']) !== $account['username'] . '.json') {
        throw new RuntimeException('El archivo debe estar fuera de la raiz web y llamarse usuario.json.');
    }
    $file = $parent . '/' . $account['username'] . '.json';
    if (file_exists($file) || is_link($file)) {
        throw new RuntimeException('No se sobrescriben cuentas existentes.');
    }
    $body = json_encode($account, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    $handle = fopen($file, 'xb');
    if (!$handle) {
        throw new RuntimeException('No se pudo crear el archivo local.');
    }
    try {
        chmod($file, 0600);
        for ($offset = 0; $offset < strlen($body); $offset += $written) {
            $written = fwrite($handle, substr($body, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Escritura local incompleta.');
            }
        }
        if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
            throw new RuntimeException('No se pudo sincronizar el archivo local.');
        }
    } finally {
        fclose($handle);
    }
    unset($input, $raw, $account, $body);
    echo "Cuenta local generada. Suba unicamente el archivo JSON al directorio privado users, con permisos 0600.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "No se genero una cuenta utilizable. Revise usuario, contrasena (16 caracteres como minimo, 72 bytes como maximo), confirmacion, ruta y archivo no existente. No comparta la entrada ni el JSON.\n");
    exit(1);
}
