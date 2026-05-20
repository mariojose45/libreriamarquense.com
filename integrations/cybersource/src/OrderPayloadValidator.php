<?php

namespace LM\CyberSource;

class OrderPayloadValidator
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function validateForHostedCheckout(array $payload)
    {
        $payload = $this->sanitizeArray($payload);

        $required = array(
            'nit',
            'nombre_cliente',
            'telefono_cliente',
            'direccion_cliente',
            'tipo_documento_cliente',
            'forma_pago',
            'total_venta',
            'total_ventades',
        );

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === '') {
                throw new GatewayException('Falta el campo requerido: ' . $field);
            }
        }

        if ($payload['forma_pago'] !== 'Tarjeta') {
            throw new GatewayException('La pasarela solo procesa pedidos con forma de pago Tarjeta.');
        }

        $amount = $this->normalizeAmount($payload['total_venta']);
        $maxAmount = (float) $this->config->get('security.max_amount', 50000);

        if ($amount <= 0) {
            throw new GatewayException('El total del pedido no es valido.');
        }

        if ($amount > $maxAmount) {
            throw new GatewayException('El total supera el limite permitido para pago en linea.');
        }

        if (!isset($payload['datosArticulos']['articulos']) || !is_array($payload['datosArticulos']['articulos'])) {
            throw new GatewayException('El detalle de articulos no es valido.');
        }

        $articles = $payload['datosArticulos']['articulos'];
        foreach (array('idarticulo', 'cantidad', 'precio_venta', 'subtotal1') as $key) {
            if (!isset($articles[$key]) || !is_array($articles[$key]) || count($articles[$key]) === 0) {
                throw new GatewayException('El detalle de articulos esta incompleto.');
            }
        }

        $payload['total_venta'] = $amount;
        $payload['total_ventades'] = $this->normalizeAmount($payload['total_ventades']);
        $payload['envio'] = array_key_exists('envio', $payload)
            ? $this->normalizeAmount($payload['envio'])
            : 0.00;
        $payload['no_auto_tarjeta'] = array_key_exists('no_auto_tarjeta', $payload)
            ? trim((string) $payload['no_auto_tarjeta'])
            : '';

        return $payload;
    }

    public function normalizeAmount($value)
    {
        $amount = round((float) $value, 2);
        return (float) number_format($amount, 2, '.', '');
    }

    private function sanitizeArray(array $value)
    {
        $clean = array();
        foreach ($value as $key => $item) {
            $clean[$key] = is_array($item) ? $this->sanitizeArray($item) : $this->sanitizeScalar($item);
        }

        return $clean;
    }

    private function sanitizeScalar($value)
    {
        if (is_string($value)) {
            $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
            return trim($value);
        }

        return $value;
    }
}
