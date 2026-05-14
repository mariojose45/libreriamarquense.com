<?php

namespace LM\CyberSource;

class OrderForwarder
{
    private $config;
    private $logger;

    public function __construct(Config $config, SecureLogger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function send(array $orderPayload)
    {
        $url = (string) $this->config->get('external_order_api.url');
        $timeout = (int) $this->config->get('external_order_api.timeout_seconds', 25);

        if ($url === '') {
            throw new GatewayException('No esta configurada la API externa de pedidos.');
        }

        $body = json_encode($orderPayload, JSON_UNESCAPED_SLASHES);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: application/json'),
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_TIMEOUT => $timeout,
            ));
            $responseBody = curl_exec($ch);
            $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
        } else {
            $context = stream_context_create(array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
                    'content' => $body,
                    'timeout' => $timeout,
                ),
            ));
            $responseBody = file_get_contents($url, false, $context);
            $statusCode = isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m) ? (int) $m[1] : 0;
            $error = $responseBody === false ? 'No se pudo conectar con la API externa.' : '';
        }

        if ($responseBody === false || $statusCode < 200 || $statusCode >= 300) {
            $this->logger->error('Error enviando pedido a API externa', array('status' => $statusCode, 'error' => $error));
            throw new GatewayException('El pago fue registrado, pero no se pudo enviar el pedido a la API externa.');
        }

        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            throw new GatewayException('La API externa devolvio una respuesta no valida.');
        }

        return $decoded;
    }
}
