<?php

namespace LM\CyberSource;

class CatalogPriceValidator
{
    private $config;
    private $lookup;

    public function __construct(Config $config, ?callable $lookup = null)
    {
        $this->config = $config;
        $this->lookup = $lookup;
    }

    public function validate(array $order)
    {
        $source = $order['datosArticulos']['articulos'];
        $articles = array('idarticulo' => $source['idarticulo'], 'cantidad' => $source['cantidad']);
        $products = array();
        $total = 0;
        $presentations = array();
        foreach ($articles['idarticulo'] as $i => $id) {
            if (!isset($products[$id])) {
                $response = $this->lookup ? call_user_func($this->lookup, $id) : $this->fetch($id);
                $products[$id] = $this->productFromResponse($response, $id);
            }
            $product = $products[$id];
            $name = isset($source['presen'][$i]) ? $source['presen'][$i] : 'UNIDAD';
            $type = isset($source['tipo_presentacion'][$i]) ? $source['tipo_presentacion'][$i] : 'unidad';
            $selected = null;
            foreach ($this->presentations($product) as $candidate) {
                if ($this->name($candidate['name']) === $this->name($name) && $candidate['type'] === $type) {
                    if ($selected && $selected['price'] !== $candidate['price']) {
                        throw new GatewayException('Precio de catalogo ambiguo. Contacte a la tienda.');
                    }
                    $selected = $candidate;
                }
            }
            if (!$selected || $selected['price'] <= 0
                || Money::cents($source['precio_venta'][$i]) !== $selected['price']) {
                throw new GatewayException('El precio o presentacion cambio. Actualice el carrito antes de pagar.');
            }
            $quantity = $articles['cantidad'][$i];
            $line = $selected['price'] * $quantity;
            $total += $line;
            $price = (float) Money::decimal($selected['price']);
            $subtotal = (float) Money::decimal($line);
            $reserve = $selected['stock'] !== null && $quantity > $selected['stock'];
            $values = array('precio_venta' => $price, 'precio_presentacion' => $price,
                'subtotal1' => $subtotal, 'subtotaldes1' => $subtotal, 'subtotal_presentacion' => $subtotal,
                'presen' => $selected['name'], 'presentacion' => $selected['name'],
                'tipo_presentacion' => $selected['type'], 'cantidadpresentacion' => 1,
                'stock_validado' => $selected['stock'], 'requiere_reserva' => $reserve);
            foreach ($values as $key => $value) {
                $articles[$key][$i] = $value;
            }
            $presentations[] = array('idarticulo' => $id, 'cantidad' => $quantity,
                'presentacion' => $selected['name'], 'tipo_presentacion' => $selected['type'],
                'precio_presentacion' => $price, 'subtotal_presentacion' => $subtotal,
                'stock_validado' => $selected['stock'], 'requiere_reserva' => $reserve);
        }
        $shipping = Money::cents($order['envio']);
        if (Money::cents($order['total_ventades']) !== $total || Money::cents($order['total_venta']) !== $total + $shipping) {
            throw new GatewayException('El total no coincide con el catalogo y el envio del servidor.');
        }
        $order['total_ventades'] = (float) Money::decimal($total);
        $order['total_venta'] = (float) Money::decimal($total + $shipping);
        $order['no_auto_tarjeta'] = '';
        $order['datosArticulos'] = array('articulos' => $articles);
        $order['presen'] = $articles['presen'];
        $order['cantidadpresentacion'] = $articles['cantidadpresentacion'];
        $order['requiere_reserva'] = $articles['requiere_reserva'];
        $order['presentaciones'] = $presentations;
        $allowed = array('nit', 'nombre_cliente', 'telefono_cliente', 'direccion_cliente', 'correo_cliente',
            'tipo_documento_cliente', 'forma_pago', 'total_venta', 'total_ventades', 'envio',
            'comentario_cotizacion', 'no_auto_tarjeta', 'datosArticulos', 'presen',
            'cantidadpresentacion', 'requiere_reserva', 'presentaciones');
        $order = array_intersect_key($order, array_flip($allowed));
        $order['idvendedor'] = 0;
        $order['tipo_cliente'] = 'Cliente';
        $order['forma_productos'] = 'Detallado';
        $order['destino'] = 'VENTA';
        return $order;
    }

    private function fetch($id)
    {
        $url = (string) $this->config->get('catalog_api.url', '');
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            throw new GatewayException('Falta configurar el catalogo seguro del servidor.');
        }
        list($status, $raw) = HttpsTransport::postJson($this->config, $url, array('idarticulo' => (int) $id),
            (int) $this->config->get('catalog_api.timeout_seconds', 12), 'catalog');
        if ($raw === false || $status < 200 || $status >= 300) {
            throw new GatewayException('No se pudo verificar el catalogo. No se inicio ningun pago.');
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new GatewayException('Respuesta de catalogo no valida.');
        }
        return $decoded;
    }

    private function productFromResponse(array $response, $id)
    {
        if (!isset($response['success'], $response['data']) || $response['success'] !== true || !is_array($response['data'])) {
            throw new GatewayException('El catalogo no confirmo el producto.');
        }
        $matches = array();
        foreach ($response['data'] as $product) {
            if (is_array($product) && isset($product['idarticulo']) && (string) $product['idarticulo'] === (string) $id) {
                $matches[] = $product;
            }
        }
        if (count($matches) !== 1) {
            throw new GatewayException('Producto inexistente o ambiguo en el catalogo.');
        }
        return $matches[0];
    }

    private function presentations(array $product)
    {
        $stockKeys = array('stock', 'stocksucursal', 'existencia', 'stock_unidad');
        $stock = $this->first($product, $stockKeys, null);
        $result = array();
        foreach (isset($product['presentaciones']) && is_array($product['presentaciones']) ? $product['presentaciones'] : array() as $item) {
            if (!is_array($item)) {
                continue;
            }
            $result[] = $this->presentation($this->first($item, array('nombre', 'presentacion'), ''),
                $this->first($item, array('tipo'), ''), $this->first($item, array('precio', 'precio_venta'), 0),
                $stock !== null ? $stock : $this->first($item, array('stock', 'existencia', 'stocksucursal'), null));
        }
        $types = array('unidad', 'blister', 'caja', 'fardo', 'sacos', 'paquete');
        for ($i = 1; $i <= 20; $i++) {
            $index = sprintf('%02d', $i);
            $type = $i <= 6 ? $types[$i - 1] : 'presentacion_' . $index;
            $priceKeys = array('precio_' . $type, 'precio_' . $index);
            if ($i === 1) {
                $priceKeys = array_merge($priceKeys, array('precio_venta', 'precio'));
            }
            $price = $this->first($product, $priceKeys, null);
            $name = $this->first($product, array('nombre_' . $index), $i === 1 && is_numeric($price) && (float) $price > 0 ? 'UNIDAD' : '');
            if ($name !== '' && $price !== null) {
                $result[] = $this->presentation($name, $type, $price,
                    $this->first($product, $i === 1 ? $stockKeys : array_merge(array('stock_' . $type), $stockKeys), null));
            }
        }
        return $result;
    }

    private function presentation($name, $type, $price, $stock)
    {
        $text = (string) $price;
        if (strpos($text, '.') !== false) {
            $text = rtrim(rtrim($text, '0'), '.');
        }
        $type = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '_', $type !== '' ? $type : $name), '_'));
        return array('name' => trim($name), 'type' => $type, 'price' => Money::cents($text === '' ? '0' : $text),
            'stock' => $stock !== null && is_numeric($stock) ? max(0, (float) $stock) : null);
    }

    private function first(array $data, array $keys, $default)
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return $default;
    }

    private function name($name)
    {
        if (!is_string($name)) {
            throw new GatewayException('Presentacion no valida.');
        }
        return strtoupper(preg_replace('/[.\s]+/', '', $name));
    }
}
