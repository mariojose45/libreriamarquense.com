<?php

namespace LM\CyberSource;

class CyberSourceSignature
{
    public static function headers($method, $path, $host, $body, $merchantId, $keyId, $sharedSecret)
    {
        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));
        $requestTarget = strtolower($method) . ' ' . $path;

        $headersList = 'host date (request-target) digest v-c-merchant-id';
        $signatureString = "host: {$host}\n"
            . "date: {$date}\n"
            . "(request-target): {$requestTarget}\n"
            . "digest: {$digest}\n"
            . "v-c-merchant-id: {$merchantId}";

        $secret = base64_decode($sharedSecret, true);
        if ($secret === false) {
            $secret = $sharedSecret;
        }

        $signature = base64_encode(hash_hmac('sha256', $signatureString, $secret, true));

        return array(
            'Host: ' . $host,
            'Date: ' . $date,
            'Digest: ' . $digest,
            'v-c-merchant-id: ' . $merchantId,
            'Signature: keyid="' . $keyId . '", algorithm="HmacSHA256", headers="' . $headersList . '", signature="' . $signature . '"',
            'Content-Type: application/json',
            'Accept: application/json',
        );
    }
}
