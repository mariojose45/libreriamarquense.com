<?php

if (!function_exists('lm_security_start_session')) {
    function lm_security_start_session()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params(array(
                'lifetime' => 0,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax',
            ));
        } else {
            session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
        }

        session_start();
    }

    function lm_security_headers()
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    }

    function lm_html_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    function lm_csrf_token($action = 'default')
    {
        lm_security_start_session();

        if (!isset($_SESSION['lm_csrf_tokens']) || !is_array($_SESSION['lm_csrf_tokens'])) {
            $_SESSION['lm_csrf_tokens'] = array();
        }

        if (empty($_SESSION['lm_csrf_tokens'][$action])) {
            $_SESSION['lm_csrf_tokens'][$action] = bin2hex(random_bytes(32));
        }

        return $_SESSION['lm_csrf_tokens'][$action];
    }

    function lm_validate_csrf_token($token, $action = 'default')
    {
        lm_security_start_session();

        if (!is_string($token) || $token === '') {
            return false;
        }

        return isset($_SESSION['lm_csrf_tokens'][$action])
            && hash_equals($_SESSION['lm_csrf_tokens'][$action], $token);
    }

    function lm_client_ip()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? preg_replace('/[^A-Fa-f0-9:.,]/', '', (string) $_SERVER['REMOTE_ADDR']) : 'unknown';
    }

    function lm_rate_limit($key, $maxAttempts, $windowSeconds)
    {
        lm_security_start_session();

        $now = time();
        $key = preg_replace('/[^A-Za-z0-9:_-]/', '_', (string) $key);

        if (!isset($_SESSION['lm_rate_limits']) || !is_array($_SESSION['lm_rate_limits'])) {
            $_SESSION['lm_rate_limits'] = array();
        }

        if (!isset($_SESSION['lm_rate_limits'][$key]) || !is_array($_SESSION['lm_rate_limits'][$key])) {
            $_SESSION['lm_rate_limits'][$key] = array();
        }

        $_SESSION['lm_rate_limits'][$key] = array_values(array_filter(
            $_SESSION['lm_rate_limits'][$key],
            function ($timestamp) use ($now, $windowSeconds) {
                return is_numeric($timestamp) && ($now - (int) $timestamp) < $windowSeconds;
            }
        ));

        if (count($_SESSION['lm_rate_limits'][$key]) >= $maxAttempts) {
            return false;
        }

        $_SESSION['lm_rate_limits'][$key][] = $now;
        return true;
    }

    function lm_clean_text($value, $maxLength = 1000)
    {
        $value = (string) $value;
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        $value = trim(strip_tags($value));
        $value = preg_replace('/\s+/u', ' ', $value);

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength, 'UTF-8');
        }

        return substr($value, 0, $maxLength);
    }

    function lm_contains_suspicious_markup($value)
    {
        $value = (string) $value;

        return (bool) preg_match(
            '/<\s*(script|iframe|object|embed|style|link|meta)|javascript\s*:|on[a-z]+\s*=|data\s*:/i',
            $value
        );
    }

    function lm_validate_name($value, &$clean = null)
    {
        if (lm_contains_suspicious_markup($value)) {
            return 'El nombre contiene caracteres no permitidos.';
        }

        $clean = lm_clean_text($value, 80);

        if ($clean === '') {
            return 'El nombre es obligatorio.';
        }

        if (!preg_match('/^(?=.{3,80}$)[\p{L}\p{M}]+(?:\s+[\p{L}\p{M}]+)*$/u', $clean)) {
            return 'El nombre debe tener entre 3 y 80 caracteres y usar solo letras y espacios.';
        }

        return '';
    }

    function lm_validate_email($value, &$clean = null, $required = true)
    {
        $clean = strtolower(trim((string) $value));

        if ($clean === '') {
            return $required ? 'El correo es obligatorio.' : '';
        }

        if (strlen($clean) > 120 || preg_match('/\s/', $clean) || lm_contains_suspicious_markup($clean)) {
            return 'El correo contiene caracteres no permitidos.';
        }

        if (!filter_var($clean, FILTER_VALIDATE_EMAIL)) {
            return 'Ingrese un correo electronico valido.';
        }

        return '';
    }

    function lm_validate_phone($value, &$clean = null, $minDigits = 8, $maxDigits = 8)
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return 'El telefono es obligatorio.';
        }

        if (lm_contains_suspicious_markup($raw) || !preg_match('/^[0-9\s-]+$/', $raw)) {
            return 'El telefono solo puede incluir numeros, espacios o guion.';
        }

        $clean = preg_replace('/\D+/', '', $raw);
        $length = strlen($clean);

        if ($length < $minDigits || $length > $maxDigits) {
            return $minDigits === $maxDigits
                ? 'El telefono debe tener exactamente ' . $minDigits . ' digitos.'
                : 'El telefono debe tener entre ' . $minDigits . ' y ' . $maxDigits . ' digitos.';
        }

        return '';
    }

    function lm_validate_text_field($value, $label, $minLength, $maxLength, &$clean = null)
    {
        if (lm_contains_suspicious_markup($value)) {
            return $label . ' contiene caracteres no permitidos.';
        }

        $clean = lm_clean_text($value, $maxLength);
        $length = function_exists('mb_strlen') ? mb_strlen($clean, 'UTF-8') : strlen($clean);

        if ($length < $minLength) {
            return $label . ' es obligatorio o demasiado corto.';
        }

        if ($length > $maxLength) {
            return $label . ' supera la longitud permitida.';
        }

        return '';
    }

    function lm_validate_integer_range($value, $min, $max, &$clean = null)
    {
        $raw = trim((string) $value);

        if (!preg_match('/^\d+$/', $raw)) {
            return 'La cantidad debe ser un numero entero.';
        }

        $number = (int) $raw;
        if ($number < $min || $number > $max) {
            return 'La cantidad debe estar entre ' . $min . ' y ' . $max . '.';
        }

        $clean = $number;
        return '';
    }
}
