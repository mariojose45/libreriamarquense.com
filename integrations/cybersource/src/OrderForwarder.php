<?php

namespace LM\CyberSource;

class OrderForwarder
{
    private $config;
    private $logger;
    private $transport;

    public function __construct(Config $config, SecureLogger $logger, ?callable $transport = null)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->transport = $transport;
    }

    public function send(array $orderPayload, $paymentWasRegistered = false)
    {
        $url = (string) $this->config->get('external_order_api.url');
        $timeout = (int) $this->config->get('external_order_api.timeout_seconds', 25);

        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new GatewayException('No esta configurada la API externa de pedidos.');
        }

        $body = json_encode($orderPayload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new GatewayException('No se pudo serializar el pedido.');
        }

        SideEffectPolicy::assertOrderTransport($this->config, $url);
        if ($this->transport) {
            list($statusCode, $responseBody) = call_user_func($this->transport, $body);
        } else {
            list($statusCode, $responseBody) = HttpsTransport::postJson($this->config, $url, $orderPayload, $timeout);
        }

        if ($responseBody === false || $statusCode < 200 || $statusCode >= 300) {
            $this->logger->error('Error enviando pedido a API externa', array('status' => $statusCode));
            throw new GatewayException($paymentWasRegistered
                ? 'El pago fue registrado, pero no se pudo enviar el pedido a la API externa.'
                : 'No se pudo enviar el pedido a la API externa.');
        }

        $decoded = json_decode((string) $responseBody, true);
        if (!is_array($decoded)) {
            throw new GatewayException('La API externa devolvio una respuesta no valida.');
        }

        if ($paymentWasRegistered && !self::isConfirmed($decoded)) {
            throw new GatewayException('La API externa no confirmo el registro del pedido. Requiere conciliacion.');
        }

        return $decoded;
    }

    public static function isConfirmed(array $response)
    {
        // Observed Marquense contract: success:true plus a positive quotation ID.
        return isset($response['success'], $response['idcotizacion']) && $response['success'] === true
            && (is_int($response['idcotizacion']) || is_string($response['idcotizacion']))
            && preg_match('/^[1-9][0-9]*$/D', (string) $response['idcotizacion']);
    }
}
