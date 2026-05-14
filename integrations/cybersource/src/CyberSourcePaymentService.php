<?php

namespace LM\CyberSource;

class CyberSourcePaymentService
{
    private $client;
    private $config;

    public function __construct(CyberSourceClient $client, Config $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    public function createPaymentWithToken(array $orderPayload, array $paymentInformation, $capture)
    {
        $amount = number_format((float) $orderPayload['total_venta'], 2, '.', '');
        $nameParts = preg_split('/\s+/', trim((string) $orderPayload['nombre_cliente']));
        $firstName = array_shift($nameParts) ?: 'Cliente';
        $lastName = trim(implode(' ', $nameParts)) ?: 'Marquense';

        $payload = array(
            'clientReferenceInformation' => array(
                'code' => isset($orderPayload['payment_reference']) ? $orderPayload['payment_reference'] : uniqid('LMQ-', true),
            ),
            'processingInformation' => array(
                'capture' => (bool) $capture,
                'commerceIndicator' => 'internet',
            ),
            'orderInformation' => array(
                'amountDetails' => array(
                    'totalAmount' => $amount,
                    'currency' => (string) $this->config->get('currency', 'GTQ'),
                ),
                'billTo' => array(
                    'firstName' => $firstName,
                    'lastName' => $lastName,
                    'address1' => (string) $orderPayload['direccion_cliente'],
                    'locality' => 'Guatemala',
                    'administrativeArea' => 'GT',
                    'postalCode' => '01001',
                    'country' => 'GT',
                    'email' => $orderPayload['correo_cliente'] !== '' ? (string) $orderPayload['correo_cliente'] : 'clientes@libreriamarquense.com',
                    'phoneNumber' => (string) $orderPayload['telefono_cliente'],
                ),
            ),
            'paymentInformation' => $paymentInformation,
        );

        return $this->client->request('POST', '/pts/v2/payments', $payload);
    }

    public function capture($paymentId, $amount, $currency)
    {
        return $this->client->request('POST', '/pts/v2/payments/' . rawurlencode($paymentId) . '/captures', array(
            'orderInformation' => array(
                'amountDetails' => array(
                    'totalAmount' => number_format((float) $amount, 2, '.', ''),
                    'currency' => $currency,
                ),
            ),
        ));
    }

    public function refund($paymentId, $amount, $currency)
    {
        return $this->client->request('POST', '/pts/v2/payments/' . rawurlencode($paymentId) . '/refunds', array(
            'orderInformation' => array(
                'amountDetails' => array(
                    'totalAmount' => number_format((float) $amount, 2, '.', ''),
                    'currency' => $currency,
                ),
            ),
        ));
    }

    public function voidCapture($captureId)
    {
        return $this->client->request('POST', '/pts/v2/captures/' . rawurlencode($captureId) . '/voids', array());
    }

    public function reverseAuthorization($paymentId, $amount)
    {
        return $this->client->request('POST', '/pts/v2/payments/' . rawurlencode($paymentId) . '/reversals', array(
            'reversalInformation' => array(
                'amountDetails' => array(
                    'totalAmount' => number_format((float) $amount, 2, '.', ''),
                ),
            ),
        ));
    }
}
