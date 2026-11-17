<?php

namespace LM\CyberSource;

class Money
{
    public static function cents($value)
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new GatewayException('Importe no valido.');
        }
        if (!preg_match('/^(0|[1-9][0-9]{0,8})(?:\.([0-9]{1,2}))?$/D', (string) $value, $match)) {
            throw new GatewayException('Importe no valido.');
        }
        return ((int) $match[1] * 100) + (int) str_pad(isset($match[2]) ? $match[2] : '', 2, '0');
    }

    public static function decimal($cents)
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
