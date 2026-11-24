<?php

namespace LM\CyberSource;

final class ReconciliationAdminStore
{
    private $root;
    private $metrics;

    public function __construct($directory, array $publicRoots, ?ReconciliationAdminMetrics $metrics = null)
    {
        $this->metrics = $metrics ?: new ReconciliationAdminMetrics();
        $root = realpath($directory);
        if (!$root || is_link($directory) || !is_dir($root)) {
            throw new \RuntimeException('Falta el directorio administrativo privado.');
        }
        foreach ($publicRoots as $publicRoot) {
            $public = realpath($publicRoot);
            if (!$public || self::inside($root, $public)) {
                throw new \RuntimeException('El almacenamiento administrativo debe estar fuera de las raices publicas.');
            }
        }
        $this->root = $root;
        $this->checkPermissions($root);
        $this->directory('users', false);
        foreach (array('sessions', 'rate', 'audit') as $name) {
            $this->directory($name, true);
        }
    }

    private static function inside($path, $parent)
    {
        $path = str_replace('\\', '/', $path);
        $parent = rtrim(str_replace('\\', '/', $parent), '/');
        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtolower($path);
            $parent = strtolower($parent);
        }
        return $path === $parent || strpos($path, $parent . '/') === 0;
    }

    private function checkPermissions($path)
    {
        clearstatcache(true, $path);
        $mode = fileperms($path);
        if ($mode === false || DIRECTORY_SEPARATOR !== '\\' && ($mode & 0077) !== 0) {
            throw new \RuntimeException('Use permisos privados: directorios 0700, archivos 0600.');
        }
    }

    private function directory($name, $create)
    {
        $path = $this->root . '/' . $name;
        if (is_link($path) || !is_dir($path) && (!$create || !mkdir($path, 0700))) {
            throw new \RuntimeException('Directorio administrativo no disponible.');
        }
        $resolved = realpath($path);
        if (!$resolved || !self::inside($resolved, $this->root)) {
            throw new \RuntimeException('Directorio administrativo no valido.');
        }
        $this->checkPermissions($path);
        return $path;
    }

    public function sessionDirectory()
    {
        return $this->directory('sessions', false);
    }

    public function metrics()
    {
        return $this->metrics;
    }

    public static function validUsername($username)
    {
        return is_string($username) && preg_match('/^[a-z0-9][a-z0-9._-]{2,63}$/D', $username);
    }

    public static function validPassword($password)
    {
        return is_string($password) && strlen($password) <= 72 && strpos($password, "\0") === false
            && preg_match('/^.{16,72}$/usD', $password);
    }

    public static function account($username, $password)
    {
        if (!self::validUsername($username) || !self::validPassword($password)) {
            throw new \RuntimeException('Usuario o longitud de contrasena no validos.');
        }
        return array('version' => 1, 'username' => $username, 'role' => 'reconciliation_admin',
            'enabled' => true, 'password_hash' => password_hash($password, PASSWORD_BCRYPT, array('cost' => 12)),
            'session_version' => bin2hex(random_bytes(16)));
    }

    public function user($username)
    {
        $this->metrics->count('account_lookups');
        return $this->metrics->measure('account_lookup_ms', function () use ($username) { return $this->loadUser($username); });
    }

    private function loadUser($username)
    {
        if (!self::validUsername($username)) {
            return null;
        }
        $record = $this->read($this->root . '/users/' . $username . '.json');
        if (!$record) {
            return null;
        }
        $info = isset($record['password_hash']) && is_string($record['password_hash'])
            ? password_get_info($record['password_hash']) : array();
        if (!isset($record['version'], $record['username'], $record['role'], $record['enabled'], $record['session_version'])
            || $record['version'] !== 1 || $record['username'] !== $username
            || $record['role'] !== 'reconciliation_admin' || $record['enabled'] !== true
            || !is_string($record['session_version']) || !preg_match('/^[a-f0-9]{32}$/D', $record['session_version'])
            || !isset($info['algoName'], $info['options']['cost']) || $info['algoName'] !== 'bcrypt' || $info['options']['cost'] !== 12) {
            return null;
        }
        return $record;
    }

    private function read($file)
    {
        if (is_link($file)) {
            throw new \RuntimeException('Archivo administrativo no valido.');
        }
        if (!is_file($file)) {
            return null;
        }
        $this->checkPermissions($file);
        $raw = file_get_contents($file, false, null, 0, 32769);
        if (!is_string($raw) || strlen($raw) > 32768) {
            throw new \RuntimeException('Archivo administrativo ilegible.');
        }
        $record = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($record)) {
            throw new \RuntimeException('Archivo administrativo no valido.');
        }
        return $record;
    }

    private function locked(callable $callback)
    {
        $file = $this->root . '/rate/auth.lock';
        if (is_link($file)) {
            throw new \RuntimeException('Bloqueo administrativo no valido.');
        }
        $handle = fopen($file, 'c');
        if (!$handle) {
            throw new \RuntimeException('Almacenamiento administrativo no disponible.');
        }
        try {
            chmod($file, 0600);
            $this->checkPermissions($file);
            $deadline = microtime(true) + 2;
            do {
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    return $callback();
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            throw new \RuntimeException('Acceso temporalmente no disponible.');
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    private function write($file, array $record)
    {
        $temp = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $handle = fopen($temp, 'xb');
        if (!$handle) {
            throw new \RuntimeException('Almacenamiento administrativo no disponible.');
        }
        try {
            chmod($temp, 0600);
            $this->checkPermissions($temp);
            self::writeAll($handle, json_encode($record, JSON_THROW_ON_ERROR));
            fclose($handle);
            $handle = null;
            if (!rename($temp, $file)) {
                throw new \RuntimeException('No se pudo guardar el control administrativo.');
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($temp)) {
                unlink($temp);
            }
        }
    }

    private static function writeAll($handle, $data)
    {
        for ($offset = 0; $offset < strlen($data); $offset += $written) {
            $written = fwrite($handle, substr($data, $offset));
            if ($written === false || $written === 0) {
                throw new \RuntimeException('Escritura administrativa incompleta.');
            }
        }
        if (!fflush($handle) || function_exists('fsync') && !fsync($handle)) {
            throw new \RuntimeException('No se pudo sincronizar el registro administrativo.');
        }
    }

    public function reserveAttempt($username, $ip)
    {
        return $this->locked(function () use ($username, $ip) {
            $records = array();
            foreach (array('global' => 200, 'user:' . $username => 5, 'ip:' . $ip => 40) as $key => $limit) {
                $file = $this->root . '/rate/' . hash('sha256', $key) . '.json';
                $record = $this->read($file);
                if ($record !== null && (!isset($record['until'], $record['count'])
                    || !is_int($record['until']) || !is_int($record['count']) || $record['count'] < 0)) {
                    throw new \RuntimeException('Control de intentos no disponible.');
                }
                if (!$record || $record['until'] <= time()) {
                    $record = array('until' => time() + 900, 'count' => 0);
                }
                if (!isset($record['count']) || !is_int($record['count']) || $record['count'] >= $limit) {
                    return false;
                }
                $records[$file] = $record;
            }
            foreach ($records as $file => $record) {
                $record['count']++;
                $this->write($file, $record);
            }
            return true;
        });
    }

    public function clearUserAttempts($username)
    {
        $this->locked(function () use ($username) {
            $this->write($this->root . '/rate/' . hash('sha256', 'user:' . $username) . '.json',
                array('until' => time() + 900, 'count' => 0));
        });
    }

    public function audit($action, $username, array $context = array())
    {
        return $this->metrics->measure('audit_ms', function () use ($action, $username, $context) {
            return $this->appendAudit($action, $username, $context);
        });
    }

    private function appendAudit($action, $username, array $context)
    {
        if (!preg_match('/^[a-z_]{3,40}$/D', $action) || !self::validUsername($username)) {
            throw new \RuntimeException('Registro administrativo no valido.');
        }
        $allowed = array('reference', 'quotation', 'revision', 'outcome', 'operation_id', 'evidence_sha256');
        $safe = array_intersect_key($context, array_flip($allowed));
        foreach ($safe as $value) {
            if ((!is_int($value) && !is_string($value)) || strlen((string) $value) > 160
                || !preg_match('/^[A-Za-z0-9._-]*$/D', (string) $value)) {
                throw new \RuntimeException('Contexto administrativo no valido.');
            }
        }
        $file = $this->root . '/audit/' . gmdate('Y-m') . '.jsonl';
        if (is_link($file)) {
            throw new \RuntimeException('Registro administrativo no valido.');
        }
        $handle = fopen($file, 'ab');
        if (!$handle) {
            throw new \RuntimeException('Auditoria administrativa no disponible.');
        }
        try {
            chmod($file, 0600);
            $this->checkPermissions($file);
            if (!flock($handle, LOCK_EX | LOCK_NB)) {
                throw new \RuntimeException('Auditoria ocupada; repita la consulta.');
            }
            self::writeAll($handle, json_encode(array('at' => gmdate('c'), 'action' => $action,
                'operator' => $username, 'context' => $safe), JSON_THROW_ON_ERROR) . "\n");
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function writePerformance(array $record)
    {
        if (!ReconciliationAdminMetrics::safeRecord($record)) { return false; }
        $file = $this->root . '/audit/performance-' . gmdate('Y-m-d') . '.jsonl';
        $handle = null;
        try {
            if (is_link($file)) { return false; }
            $handle = @fopen($file, 'ab');
            if (!$handle) { return false; }
            @chmod($file, 0600);
            $this->checkPermissions($file);
            if (!flock($handle, LOCK_EX | LOCK_NB)) { return false; }
            $line = json_encode($record, JSON_THROW_ON_ERROR) . "\n";
            $stat = fstat($handle);
            if (!$stat || $stat['size'] + strlen($line) > 5 * 1024 * 1024) { return false; }
            for ($offset = 0; $offset < strlen($line); $offset += $written) {
                $written = fwrite($handle, substr($line, $offset));
                if ($written === false || $written === 0) { return false; }
            }
            // Optional timings are best-effort, unlike the unchanged durable security audit.
            return fflush($handle);
        } catch (\Throwable $e) { return false; }
        finally {
            if (is_resource($handle)) { flock($handle, LOCK_UN); fclose($handle); }
        }
    }
}
