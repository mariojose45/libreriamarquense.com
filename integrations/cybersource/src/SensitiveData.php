<?php

namespace LM\CyberSource;

class SensitiveData
{
    public static function redact($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        $redacted = array();
        foreach ($value as $key => $item) {
            if (self::isSensitiveKey($key)) {
                $redacted[$key] = '[REDACTED]';
                continue;
            }

            $redacted[$key] = self::redact($item);
        }

        return $redacted;
    }

    private static function isSensitiveKey($key)
    {
        $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $key));
        if ($normalized === '') {
            return false;
        }

        $exactKeys = array(
            'authorization',
            'cvv',
            'cvc',
            'securitycode',
            'cardnumber',
            'number',
            'apikey',
            'accesskey',
            'privatekey',
            'keyid',
            'merchantid',
        );

        if (in_array($normalized, $exactKeys, true)) {
            return true;
        }

        foreach (array('password', 'passwd', 'pwd', 'secret', 'token', 'signature',
            'accesskey', 'sharedkey', 'privatekey', 'cardnumber', 'cvn', 'cvv', 'cvc', 'cardexpiry', 'cardexpiration', 'securitycode') as $term) {
            if (strpos($normalized, $term) !== false) {
                return true;
            }
        }

        return false;
    }

    public static function forLog($value)
    {
        if (is_array($value)) {
            $result = array();
            foreach ($value as $key => $item) {
                $normalized = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $key));
                $personal = preg_match('/email|correo|phone|telefono|address|direccion|forename|surname|nombrecliente|nit|payload|body|params/', $normalized);
                $result[$key] = self::isSensitiveKey($key) || $personal ? '[REDACTED]' : self::forLog($item);
            }
            return $result;
        }
        if (is_string($value)) {
            $value = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[EMAIL]', $value);
            $value = preg_replace('/(?<![A-Za-z0-9])[0-9]{13,19}(?![A-Za-z0-9])/', '[NUMBER]', $value);
            $value = preg_replace('/((?:secret|token|signature|access_key|password)[\s"\x27]*[=:][\s"\x27]*)[^\s,"\x27&]+/i', '$1[REDACTED]', $value);
        }
        return $value;
    }
}
