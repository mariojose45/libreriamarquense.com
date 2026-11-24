<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
ini_set('display_errors', '0');
ini_set('log_errors', '0');

$failure = 'INTERNAL';
try {
    $failure = 'ARGUMENTS';
    $options = getopt('', array('output:'), $rest);
    if ($rest !== count($argv) || count($options) !== 1 || !isset($options['output']) || !is_string($options['output'])) {
        throw new RuntimeException('Indique --output con un archivo nuevo fuera del proyecto.');
    }
    $failure = 'DEPENDENCY';
    require dirname(__DIR__, 2) . '/integrations/cybersource/Autoload.php';
    $failure = 'INPUT_SIZE';
    $raw = stream_get_contents(STDIN, 16385);
    if (!is_string($raw) || $raw === '' || strlen($raw) > 16384) {
        throw new RuntimeException('Entrada no valida.');
    }
    $failure = 'INPUT_JSON';
    $input = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
    $failure = 'INPUT_FIELDS';
    if (!is_array($input) || !isset($input['username'], $input['password'], $input['confirmation'])
        || !is_string($input['password']) || !is_string($input['confirmation'])) {
        throw new RuntimeException('Campos no validos.');
    }
    $failure = 'USERNAME';
    if (!\LM\CyberSource\ReconciliationAdminStore::validUsername($input['username'])) {
        throw new RuntimeException('Usuario no valido.');
    }
    $failure = 'PASSWORD_LENGTH';
    if (!\LM\CyberSource\ReconciliationAdminStore::validPassword($input['password'])) {
        throw new RuntimeException('Longitud o formato de contrasena no validos.');
    }
    $failure = 'PASSWORD_MISMATCH';
    if (!hash_equals($input['password'], $input['confirmation'])) {
        throw new RuntimeException('Confirme la contrasena.');
    }
    $failure = 'INTERNAL';
    $account = \LM\CyberSource\ReconciliationAdminStore::account($input['username'], $input['password']);
    $failure = 'OUTPUT_PATH';
    $parent = realpath(dirname($options['output']));
    $publicParent = realpath(dirname(__DIR__, 3));
    $normalize = function ($path) { return strtolower(rtrim(str_replace('\\', '/', (string) $path), '/')); };
    if (!$parent || !$publicParent || $normalize($parent) === $normalize($publicParent)
        || strpos($normalize($parent), $normalize($publicParent) . '/') === 0
        || basename($options['output']) !== $account['username'] . '.json') {
        throw new RuntimeException('El archivo debe estar fuera de la raiz web y llamarse usuario.json.');
    }
    $file = $parent . '/' . $account['username'] . '.json';
    $failure = 'OUTPUT_EXISTS';
    if (file_exists($file) || is_link($file)) {
        throw new RuntimeException('No se sobrescriben cuentas existentes.');
    }
    $body = json_encode($account, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    $failure = 'OUTPUT_CREATE';
    $handle = fopen($file, 'xb');
    if (!$handle) {
        throw new RuntimeException('No se pudo crear el archivo local.');
    }
    try {
        chmod($file, 0600);
        $failure = 'OUTPUT_WRITE';
        for ($offset = 0; $offset < strlen($body); $offset += $written) {
            $written = fwrite($handle, substr($body, $offset));
            if ($written === false || $written === 0) {
                throw new RuntimeException('Escritura local incompleta.');
            }
        }
        $failure = 'OUTPUT_SYNC';
        if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
            throw new RuntimeException('No se pudo sincronizar el archivo local.');
        }
    } finally {
        fclose($handle);
    }
    unset($input, $raw, $account, $body);
    echo "Cuenta local generada. Suba unicamente el archivo JSON al directorio privado users, con permisos 0600.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'LM_ADMIN_ACCOUNT_ERROR=' . $failure . "\n");
    exit(1);
}
