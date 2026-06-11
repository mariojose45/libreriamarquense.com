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
        return $this->validateForOrder($payload, array('Tarjeta'));
    }

    public function validateForOrder(array $payload, array $allowedPaymentMethods = array('Pago Contra Entrega', 'Tarjeta', 'Transferencia'))
    {
        $payload = $this->sanitizeArray($payload);
        $this->rejectSuspiciousMarkup($payload);

        $required = array(
            'nit',
            'nombre_cliente',
            'telefono_cliente',
            'direccion_cliente',
            'tipo_documento_cliente',
            'forma_pago',
            'total_venta',
            'total_ventades',
            'modalidad_entrega',
            'lugar_entrega',
        );

        foreach ($required as $field) {
            if (!array_key_exists($field, $payload) || $payload[$field] === '') {
                throw new GatewayException('Falta el campo requerido: ' . $field);
            }
        }

        if (!in_array($payload['forma_pago'], $allowedPaymentMethods, true)) {
            throw new GatewayException('La forma de pago no esta disponible para este proceso.');
        }

        $payload['nombre_cliente'] = $this->validateName($payload['nombre_cliente']);
        $payload['telefono_cliente'] = $this->validatePhone($payload['telefono_cliente']);
        $payload['direccion_cliente'] = $this->validateAddress($payload['direccion_cliente']);
        $payload['correo_cliente'] = $this->validateEmail(isset($payload['correo_cliente']) ? $payload['correo_cliente'] : '');
        $payload['tipo_documento_cliente'] = $this->validateDocumentType($payload['tipo_documento_cliente']);
        $payload['nit'] = $this->validateDocumentNumber($payload['nit'], $payload['tipo_documento_cliente']);

        $amount = $this->parseAmount($payload['total_venta'], 'El total del pedido');
        $subtotal = $this->parseAmount($payload['total_ventades'], 'El subtotal del pedido');
        $shipping = array_key_exists('envio', $payload) && trim((string) $payload['envio']) !== ''
            ? $this->parseAmount($payload['envio'], 'El costo de envio')
            : 0.00;
        $delivery = $this->validateDelivery($payload, $shipping);
        $shipping = $delivery['shipping'];
        $payload['direccion_cliente'] = $this->validateAddress($delivery['address']);
        $payload['comentario_cotizacion'] = $delivery['comment'];

        $maxAmount = (float) $this->config->get('security.max_amount', 50000);

        if ($amount <= 0) {
            throw new GatewayException('El total del pedido no es valido.');
        }

        if ($amount > $maxAmount) {
            throw new GatewayException('El total supera el limite permitido para pago en linea.');
        }

        if (abs($amount - ($subtotal + $shipping)) > 0.05) {
            throw new GatewayException('El total del pedido no coincide con el subtotal y envio calculados.');
        }

        if (!isset($payload['datosArticulos']['articulos']) || !is_array($payload['datosArticulos']['articulos'])) {
            throw new GatewayException('El detalle de articulos no es valido.');
        }

        $articleResult = $this->validateArticles($payload['datosArticulos']['articulos']);
        if (abs($articleResult['subtotal'] - $subtotal) > 0.05) {
            throw new GatewayException('El subtotal del pedido no coincide con el detalle de articulos.');
        }

        $payload['total_venta'] = $amount;
        $payload['total_ventades'] = $subtotal;
        $payload['envio'] = $shipping;
        $payload['datosArticulos']['articulos'] = $articleResult['articles'];
        $payload['no_auto_tarjeta'] = array_key_exists('no_auto_tarjeta', $payload)
            ? trim((string) $payload['no_auto_tarjeta'])
            : '';
        unset($payload['modalidad_entrega'], $payload['lugar_entrega']);

        return $payload;
    }

    public function normalizeAmount($value)
    {
        return $this->parseAmount($value, 'El monto');
    }

    private function validateDelivery(array $payload, $requestedShipping)
    {
        $deliveryConfig = $this->config->get('delivery', array());
        $pickup = isset($deliveryConfig['pickup']) && is_array($deliveryConfig['pickup'])
            ? $deliveryConfig['pickup']
            : array();
        $shippingGroups = isset($deliveryConfig['shipping_groups']) && is_array($deliveryConfig['shipping_groups'])
            ? $deliveryConfig['shipping_groups']
            : array();

        $deliveryType = trim((string) $payload['modalidad_entrega']);
        $deliveryPlace = trim((string) $payload['lugar_entrega']);

        if ($deliveryType === '' || $deliveryPlace === '') {
            throw new GatewayException('Seleccione una modalidad y lugar de entrega validos.');
        }

        $pickupType = isset($pickup['type']) ? (string) $pickup['type'] : 'store_pickup';
        if ($deliveryType === $pickupType) {
            $pickupValue = isset($pickup['value']) ? trim((string) $pickup['value']) : '';
            $pickupAddress = isset($pickup['address']) ? trim((string) $pickup['address']) : '';
            $pickupShipping = isset($pickup['shipping'])
                ? $this->parseAmount($pickup['shipping'], 'El costo de retiro')
                : 0.00;

            if ($pickupValue === '' || $pickupAddress === '' || !hash_equals($pickupValue, $deliveryPlace)) {
                throw new GatewayException('La sucursal seleccionada para recoger no es valida.');
            }

            if (abs($requestedShipping - $pickupShipping) > 0.01) {
                throw new GatewayException('Recoge en tienda no debe generar costo de envio.');
            }

            return array(
                'shipping' => $pickupShipping,
                'address' => $pickupAddress,
                'comment' => 'Tienda en linea | Entrega: Recoge en tienda | Sucursal: ' . $pickupAddress,
            );
        }

        if ($deliveryType !== 'shipping') {
            throw new GatewayException('La modalidad de entrega no es valida.');
        }

        $expectedShipping = null;
        foreach ($shippingGroups as $locations) {
            if (is_array($locations) && array_key_exists($deliveryPlace, $locations)) {
                $expectedShipping = $this->parseAmount($locations[$deliveryPlace], 'El costo de envio configurado');
                break;
            }
        }

        if ($expectedShipping === null) {
            throw new GatewayException('El lugar de entrega seleccionado no es valido.');
        }

        if (abs($requestedShipping - $expectedShipping) > 0.01) {
            throw new GatewayException('El costo de envio no coincide con el lugar seleccionado.');
        }

        return array(
            'shipping' => $expectedShipping,
            'address' => $payload['direccion_cliente'],
            'comment' => 'Tienda en linea | Entrega a domicilio | Lugar: ' . $deliveryPlace,
        );
    }

    private function validateName($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^(?=.{3,80}$)[\p{L}\p{M}]+(?:\s+[\p{L}\p{M}]+)*$/u', $value)) {
            throw new GatewayException('El nombre del cliente no es valido.');
        }

        return $value;
    }

    private function validatePhone($value)
    {
        $value = trim((string) $value);
        if (!preg_match('/^[0-9\s-]+$/', $value)) {
            throw new GatewayException('El telefono del cliente no es valido.');
        }

        $digits = preg_replace('/\D+/', '', $value);
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            throw new GatewayException('El telefono del cliente debe tener entre 8 y 15 digitos.');
        }

        return $digits;
    }

    private function validateEmail($value)
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return '';
        }

        if (strlen($value) > 120 || preg_match('/\s/', $value) || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new GatewayException('El correo del cliente no es valido.');
        }

        return $value;
    }

    private function validateAddress($value)
    {
        $value = trim((string) $value);
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

        if ($length < 8 || $length > 250) {
            throw new GatewayException('La direccion debe tener entre 8 y 250 caracteres.');
        }

        return $value;
    }

    private function validateDocumentType($value)
    {
        $value = strtoupper(trim((string) $value));
        if (!in_array($value, array('NIT', 'DPI'), true)) {
            throw new GatewayException('El tipo de documento no es valido.');
        }

        return $value;
    }

    private function validateDocumentNumber($value, $type)
    {
        $value = strtoupper(trim((string) $value));

        if ($type === 'NIT' && $value === 'C/F') {
            return $value;
        }

        if ($type === 'DPI' && preg_match('/^\d{13}$/', $value)) {
            return $value;
        }

        if ($type === 'NIT' && preg_match('/^\d{1,12}$/', $value)) {
            return $value;
        }

        throw new GatewayException('El numero de documento no es valido.');
    }

    private function validateArticles(array $articles)
    {
        foreach (array('idarticulo', 'cantidad', 'precio_venta', 'subtotal1') as $key) {
            if (!isset($articles[$key]) || !is_array($articles[$key]) || count($articles[$key]) === 0) {
                throw new GatewayException('El detalle de articulos esta incompleto.');
            }
        }

        $count = count($articles['idarticulo']);
        $maxLineItems = (int) $this->config->get('security.max_line_items', 100);

        if ($count > $maxLineItems) {
            throw new GatewayException('El pedido supera la cantidad de lineas permitidas.');
        }

        foreach (array('cantidad', 'precio_venta', 'subtotal1') as $key) {
            if (count($articles[$key]) !== $count) {
                throw new GatewayException('El detalle de articulos no coincide.');
            }
        }

        if (!isset($articles['subtotaldes1']) || !is_array($articles['subtotaldes1']) || count($articles['subtotaldes1']) !== $count) {
            $articles['subtotaldes1'] = $articles['subtotal1'];
        }

        $maxQuantity = (int) $this->config->get('security.max_quantity', 99);
        $maxUnitPrice = (float) $this->config->get('security.max_unit_price', 50000);
        $subtotal = 0.00;
        $normalized = array(
            'idarticulo' => array(),
            'cantidad' => array(),
            'precio_venta' => array(),
            'subtotal1' => array(),
            'subtotaldes1' => array(),
        );

        for ($index = 0; $index < $count; $index++) {
            $id = $this->parsePositiveInteger($articles['idarticulo'][$index], 'El producto');
            $quantity = $this->parsePositiveInteger($articles['cantidad'][$index], 'El valor de cantidad');
            if ($quantity < 1 || $quantity > $maxQuantity) {
                throw new GatewayException('La cantidad de un producto no es valida.');
            }

            $price = $this->parseAmount($articles['precio_venta'][$index], 'El precio del producto');
            if ($price <= 0 || $price > $maxUnitPrice) {
                throw new GatewayException('El precio de un producto no es valido.');
            }

            $lineSubtotal = $this->parseAmount($articles['subtotal1'][$index], 'El subtotal de un producto');
            $lineDiscountSubtotal = $this->parseAmount($articles['subtotaldes1'][$index], 'El subtotal con descuento de un producto');
            $expectedLineSubtotal = $this->normalizeMoney($price * $quantity);

            if (abs($lineSubtotal - $expectedLineSubtotal) > 0.05 || abs($lineDiscountSubtotal - $expectedLineSubtotal) > 0.05) {
                throw new GatewayException('El subtotal de un producto no coincide con precio y cantidad.');
            }

            $subtotal += $lineSubtotal;
            $normalized['idarticulo'][] = $id;
            $normalized['cantidad'][] = $quantity;
            $normalized['precio_venta'][] = $price;
            $normalized['subtotal1'][] = $lineSubtotal;
            $normalized['subtotaldes1'][] = $lineDiscountSubtotal;
        }

        foreach ($normalized as $key => $values) {
            $articles[$key] = $values;
        }

        return array(
            'articles' => $articles,
            'subtotal' => $this->normalizeMoney($subtotal),
        );
    }

    private function parsePositiveInteger($value, $label)
    {
        $value = trim((string) $value);
        if (!preg_match('/^\d+$/', $value)) {
            throw new GatewayException($label . ' no es valido.');
        }

        $number = (int) $value;
        if ($number <= 0) {
            throw new GatewayException($label . ' no es valido.');
        }

        return $number;
    }

    private function parseAmount($value, $label)
    {
        if (is_int($value) || is_float($value)) {
            $amount = $this->normalizeMoney((float) $value);
            if ($amount < 0) {
                throw new GatewayException($label . ' no es valido.');
            }

            return $amount;
        }

        $value = trim((string) $value);
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
            throw new GatewayException($label . ' no es valido.');
        }

        return $this->normalizeMoney((float) $value);
    }

    private function normalizeMoney($value)
    {
        return (float) number_format(round((float) $value, 2), 2, '.', '');
    }

    private function rejectSuspiciousMarkup(array $value)
    {
        foreach ($value as $item) {
            if (is_array($item)) {
                $this->rejectSuspiciousMarkup($item);
                continue;
            }

            if (is_string($item) && preg_match('/<\s*(script|iframe|object|embed|style|link|meta)|javascript\s*:|on[a-z]+\s*=|data\s*:/i', $item)) {
                throw new GatewayException('La solicitud contiene datos no permitidos.');
            }
        }
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
