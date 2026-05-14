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

        foreach (array('password', 'passwd', 'pwd', 'secret', 'token', 'signature') as $term) {
            if (strpos($normalized, $term) !== false) {
                return true;
            }
        }

        return false;
    }
}
