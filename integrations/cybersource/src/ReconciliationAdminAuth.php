<?php

namespace LM\CyberSource;

final class ReconciliationAdminAuth
{
    private $store;
    private $passwordConfirmed = false;
    // Timing-only hash for unknown users, never an account or a default password.
    private const DUMMY_HASH = '$2y$12$N7hfhDzwJM6Kc8r6djHrB.AW2Xy/Ik8Ip4Dw8RoC5S.Eli61lINZ6';

    public function __construct(ReconciliationAdminStore $store)
    {
        $this->store = $store;
    }

    public static function requireHttps(array $server, $host)
    {
        if (!isset($server['HTTPS'], $server['HTTP_HOST'])
            || !in_array(strtolower((string) $server['HTTPS']), array('on', '1'), true)
            || strtolower((string) $server['HTTP_HOST']) !== $host) {
            throw new \RuntimeException('Use exclusivamente el dominio HTTPS autorizado.');
        }
        if (isset($server['REQUEST_METHOD']) && $server['REQUEST_METHOD'] === 'POST'
            && isset($server['HTTP_ORIGIN']) && $server['HTTP_ORIGIN'] !== 'https://' . $host) {
            throw new \RuntimeException('Origen administrativo no autorizado.');
        }
    }

    public function start()
    {
        if (session_status() !== PHP_SESSION_NONE || headers_sent()) {
            throw new \RuntimeException('La sesion administrativa debe ser independiente.');
        }
        foreach (array('session.save_handler' => 'files', 'session.use_strict_mode' => '1', 'session.use_only_cookies' => '1',
            'session.use_trans_sid' => '0', 'session.gc_maxlifetime' => '1800',
            'session.gc_probability' => '1', 'session.gc_divisor' => '100') as $name => $value) {
            if (ini_set($name, $value) === false || (string) ini_get($name) !== $value) {
                throw new \RuntimeException('No se pudo proteger la sesion administrativa.');
            }
        }
        session_save_path($this->store->sessionDirectory());
        session_name('__Host-LMReconciliation');
        session_set_cookie_params(array('lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => true,
            'httponly' => true, 'samesite' => 'Strict'));
        if (!session_start()) {
            throw new \RuntimeException('Sesion administrativa no disponible.');
        }
        if (!isset($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
    }

    public function csrf()
    {
        return $_SESSION['csrf'];
    }

    public function requireCsrf($token)
    {
        if (!is_string($token) || !isset($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            throw new \RuntimeException('La sesion del formulario vencio.');
        }
    }

    private static function fingerprint(array $account)
    {
        return hash('sha256', json_encode($account, JSON_THROW_ON_ERROR));
    }

    public function current()
    {
        $auth = isset($_SESSION['admin']) ? $_SESSION['admin'] : null;
        if (!is_array($auth) || !isset($auth['username'], $auth['fingerprint'], $auth['created'], $auth['last_seen'])
            || !is_int($auth['created']) || !is_int($auth['last_seen'])
            || $auth['created'] > time() || $auth['last_seen'] > time()
            || time() - $auth['created'] > 28800 || time() - $auth['last_seen'] > 900) {
            unset($_SESSION['admin'], $_SESSION['review'], $_SESSION['reference'], $_SESSION['cursor']);
            return null;
        }
        $account = $this->store->user($auth['username']);
        if (!$account || !is_string($auth['fingerprint']) || !hash_equals(self::fingerprint($account), $auth['fingerprint'])) {
            unset($_SESSION['admin'], $_SESSION['review'], $_SESSION['reference'], $_SESSION['cursor']);
            return null;
        }
        $_SESSION['admin']['last_seen'] = time();
        return $account['username'];
    }

    public function requireUser()
    {
        $username = $this->current();
        if (!$username) {
            throw new \RuntimeException('Inicie sesion con su cuenta administrativa.');
        }
        return $username;
    }

    private function verifyPassword($username, $password, $ip, $action)
    {
        $validUsername = ReconciliationAdminStore::validUsername($username);
        $safeUser = $validUsername ? $username : 'unknown';
        if (!$this->store->reserveAttempt($safeUser, $ip)) {
            $this->store->audit('authentication_blocked', $safeUser);
            return false;
        }
        $account = $validUsername ? $this->store->user($safeUser) : null;
        $hash = $account ? $account['password_hash'] : self::DUMMY_HASH;
        $valid = is_string($password) && strlen($password) <= 72 && strpos($password, "\0") === false;
        $matches = password_verify($valid ? $password : '', $hash);
        $ok = $account !== null && $valid && $matches;
        $this->store->audit($action, $safeUser, array('outcome' => $ok ? 'success' : 'denied'));
        if ($ok) {
            $this->store->clearUserAttempts($safeUser);
        }
        return $ok ? $account : false;
    }

    public function login($username, $password, $ip)
    {
        $account = $this->verifyPassword($username, $password, $ip, 'login');
        if (!$account) {
            throw new \RuntimeException('Credenciales incorrectas o acceso temporalmente bloqueado.');
        }
        if (!session_regenerate_id(true)) {
            throw new \RuntimeException('No se pudo renovar la sesion administrativa.');
        }
        $_SESSION = array('csrf' => bin2hex(random_bytes(32)), 'admin' => array('username' => $account['username'],
            'fingerprint' => self::fingerprint($account), 'created' => time(), 'last_seen' => time()));
    }

    public function logout()
    {
        $username = $this->requireUser();
        try {
            $this->store->audit('logout', $username);
        } finally {
            $_SESSION = array();
            session_destroy();
            setcookie(session_name(), '', array('expires' => time() - 3600, 'path' => '/', 'domain' => '',
                'secure' => true, 'httponly' => true, 'samesite' => 'Strict'));
            $this->passwordConfirmed = false;
        }
    }

    public function confirmPassword($password, $ip)
    {
        $username = $this->requireUser();
        $this->passwordConfirmed = (bool) $this->verifyPassword($username, $password, $ip, 'reauthenticate');
        if (!$this->passwordConfirmed) {
            throw new \RuntimeException('No se pudo confirmar la identidad del operador.');
        }
    }

    public function issueReview($reference, $revision)
    {
        $username = $this->requireUser();
        if (!is_string($reference) || !preg_match('/^LMQ-[0-9]{14}-[A-F0-9]{8}$/D', $reference)
            || !is_int($revision) || $revision < 1) {
            throw new \RuntimeException('Revision administrativa no disponible.');
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['review'] = array('token' => hash('sha256', $token), 'reference' => $reference,
            'revision' => $revision, 'operator' => $username, 'at' => time());
        return $token;
    }

    public function consumeReview($token)
    {
        $username = $this->requireUser();
        $review = isset($_SESSION['review']) ? $_SESSION['review'] : null;
        unset($_SESSION['review']);
        if (!$this->passwordConfirmed || !is_array($review) || !is_string($token)
            || !isset($review['token'], $review['reference'], $review['revision'], $review['operator'], $review['at'])
            || $review['operator'] !== $username || $review['at'] > time() || time() - $review['at'] > 300
            || !hash_equals($review['token'], hash('sha256', $token))) {
            throw new \RuntimeException('Repita la consulta, revise los datos y confirme su contrasena.');
        }
        $this->passwordConfirmed = false;
        return array('reference' => $review['reference'], 'expected_revision' => $review['revision'], 'operator' => $username);
    }
}
