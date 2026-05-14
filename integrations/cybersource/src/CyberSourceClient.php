<?php

namespace LM\CyberSource;

class CyberSourceClient
{
    private $config;
    private $logger;

    public function __construct(Config $config, SecureLogger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function request($method, $path, array $payload = array())
    {
        if (!$this->config->hasRestCredentials()) {
            throw new GatewayException('Faltan credenciales REST de CyberSource.');
        }

        $method = strtoupper($method);
        $baseUrl = $this->config->getCyberSourceBaseUrl();
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $body = $method === 'POST' ? json_encode($payload, JSON_UNESCAPED_SLASHES) : '';
        $headers = CyberSourceSignature::headers(
            $method,
            $path,
            $host,
            $body,
            (string) $this->config->get('cybersource.merchant_id'),
            (string) $this->config->get('cybersource.key_id'),
            (string) $this->config->get('cybersource.shared_secret')
        );

        if (!function_exists('curl_init')) {
            throw new GatewayException('La extension cURL de PHP es requerida para CyberSource REST.');
        }

        $ch = curl_init($baseUrl . $path);
        curl_setopt_array($ch, array(
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => 30,
        ));

        $responseBody = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $decoded = json_decode((string) $responseBody, true);
        $this->logger->info('CyberSource REST response', array(
            'method' => $method,
            'path' => $path,
            'status' => $statusCode,
            'response' => is_array($decoded) ? $decoded : $responseBody,
        ));

        if ($responseBody === false || $statusCode < 200 || $statusCode >= 300) {
            throw new GatewayException($error ?: 'CyberSource rechazo la solicitud REST.');
        }

        return is_array($decoded) ? $decoded : array('raw' => $responseBody);
    }
}
