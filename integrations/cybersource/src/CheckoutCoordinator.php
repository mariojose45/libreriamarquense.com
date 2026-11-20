<?php

namespace LM\CyberSource;

class CheckoutCoordinator
{
    private $config;
    private $store;

    public function __construct(Config $config, PaymentSessionStore $store)
    {
        $this->config = $config;
        $this->store = $store;
    }

    public static function purchaseKey($browserScope, array $order)
    {
        if (!is_string($browserScope) || $browserScope === '') {
            throw new GatewayException('Se requiere una sesion de compra valida.');
        }
        $items = array();
        foreach ($order['presentaciones'] as $item) {
            $key = $item['idarticulo'] . ':' . $item['tipo_presentacion'];
            $items[$key] = (isset($items[$key]) ? $items[$key] : 0) + $item['cantidad'];
        }
        ksort($items, SORT_STRING);
        $identity = array('customer' => array($order['nit'], $order['correo_cliente'],
            $order['telefono_cliente'], $order['direccion_cliente']),
            'delivery' => $order['comentario_cotizacion'], 'amount_cents' => Money::cents($order['total_venta']),
            'items' => $items);
        return hash('sha256', $browserScope . "\0" . json_encode($identity, JSON_UNESCAPED_SLASHES));
    }

    public function create(array $data, $purchaseKey, $confirmation = '')
    {
        SideEffectPolicy::assertCheckout($this->config);
        return $this->store->withLock('checkout-' . $purchaseKey, function () use ($data, $purchaseKey, $confirmation) {
            $existing = $this->store->readIndex('checkout-' . $purchaseKey);
            if ($existing) {
                $session = $this->store->find($existing['reference']);
                if (!$session) {
                    throw new GatewayException('Compra pendiente de conciliacion. Contacte a la tienda.');
                }
                if ($confirmation !== '' && isset($existing['consumed_confirmation'])
                    && hash_equals($existing['consumed_confirmation'], hash('sha256', $confirmation))) {
                    return $session;
                }
                if (!Reconciliation::held($session) && !ChargeGroups::blocked($this->store, $session)
                    && in_array($session['status'], array('PENDING', 'SUBMITTED'), true)
                    && strtotime($session['expires_at']) > time()) {
                    return $session;
                }
                $safeFinal = in_array($session['status'], array('DECLINED', 'CANCELLED', 'ORDER_SENT'), true)
                    || (in_array($session['status'], array('PENDING', 'EXPIRED'), true) && empty($session['secure_acceptance_request']));
                if (!$safeFinal || Reconciliation::held($session) || ChargeGroups::blocked($this->store, $session)) {
                    throw new GatewayException('Existe un pago pendiente de conciliacion. No vuelva a pagar. Referencia ' . $session['reference']);
                }
                $valid = $confirmation !== '' && isset($existing['confirmation_hash'], $existing['confirmation_expires'])
                    && $existing['confirmation_expires'] >= time()
                    && hash_equals($existing['confirmation_hash'], hash('sha256', $confirmation));
                if (!$valid) {
                    $nonce = bin2hex(random_bytes(32));
                    $existing['confirmation_hash'] = hash('sha256', $nonce);
                    $existing['confirmation_expires'] = time() + 300;
                    $this->store->writeIndex('checkout-' . $purchaseKey, $existing);
                    $message = $session['status'] === 'ORDER_SENT'
                        ? 'La compra anterior ya fue pagada. Confirma solamente si deseas realizar OTRA compra independiente.'
                        : 'Crear un nuevo intento con otra referencia. Si tienes dudas sobre un cobro anterior, cancela y solicita conciliacion.';
                    throw new NewPurchaseRequired($message, $nonce, $session['reference']);
                }
                $data['previous_reference'] = $session['reference'];
                if ($session['status'] !== 'ORDER_SENT') {
                    $data['charge_group'] = isset($session['charge_group']) ? $session['charge_group'] : hash('sha256', $session['reference']);
                }
            }
            $data['purchase_key'] = $purchaseKey;
            $data['intent_id'] = bin2hex(random_bytes(16));
            $data['charge_group'] = isset($data['charge_group']) ? $data['charge_group'] : bin2hex(random_bytes(16));
            $session = $this->store->create($data);
            $this->store->writeIndex('checkout-' . $purchaseKey, array('reference' => $session['reference'],
                'consumed_confirmation' => $confirmation !== '' ? hash('sha256', $confirmation) : ''));
            return $session;
        });
    }

    public function prepare($reference, $owner = '')
    {
        SideEffectPolicy::assertCheckout($this->config);
        return $this->store->withLock('session-' . $reference, function () use ($reference, $owner) {
            $session = $this->store->find($reference);
            if (!$session || !in_array($session['status'], array('PENDING', 'SUBMITTED'), true)
                || Reconciliation::held($session) || ChargeGroups::blocked($this->store, $session)
                || !empty($session['provider_transaction_id']) || !empty($session['external_order_response'])) {
                throw new GatewayException('Este intento de pago ya fue iniciado o finalizado. No se enviara nuevamente.');
            }
            if (isset($session['checkout_owner']) && !hash_equals($session['checkout_owner'], $owner)) {
                throw new GatewayException('El intento pertenece a otra sesion de compra.');
            }
            if (ChargeGroups::chargedElsewhere($this->store, $session)) {
                $session['status'] = 'REVIEW';
                $session = Reconciliation::hold($session, 'prior_charge', array('a_previous_intent_already_has_a_charge'));
                $this->store->save($reference, $session);
                throw new GatewayException('Un intento anterior ya tiene un cobro. No se iniciara otro pago; solicite conciliacion.');
            }
            if (empty($session['catalog_verified']) || $session['currency'] !== 'GTQ') {
                throw new GatewayException('El pedido requiere verificar nuevamente el catalogo.');
            }
            if (empty($session['expires_at']) || strtotime($session['expires_at']) <= time()) {
                $session['status'] = empty($session['secure_acceptance_request']) ? 'EXPIRED' : 'REVIEW';
                if ($session['status'] === 'REVIEW') {
                    $session = Reconciliation::hold($session, 'form_expired', array('submitted_form_expired'));
                }
                $this->store->save($reference, $session);
                throw new GatewayException('La sesion de pago vencio.');
            }
            $service = new SecureAcceptanceService($this->config);
            $resume = !empty($session['secure_acceptance_request']);
            if ($resume) {
                $attempt = $session['secure_acceptance_request'];
                if (empty($attempt['credential_fingerprint']) || empty($attempt['request_digest'])
                    || empty($attempt['signed_date_time']) || time() - strtotime($attempt['signed_date_time']) > 300
                    || !hash_equals($service->credentialFingerprint(), $attempt['credential_fingerprint'])) {
                    $session['status'] = 'REVIEW';
                    $session = Reconciliation::hold($session, 'resume_unavailable', array('cannot_safely_resume_submitted_form'));
                    $this->store->save($reference, $session);
                    throw new GatewayException('No es seguro reenviar este formulario. Solicite conciliacion; no vuelva a pagar.');
                }
            }
            $fields = $service->buildSignedFormFields($session);
            $digest = hash('sha256', json_encode($fields));
            if ($resume) {
                if (!hash_equals($session['secure_acceptance_request']['request_digest'], $digest)) {
                    $session['status'] = 'REVIEW';
                    $session = Reconciliation::hold($session, 'form_configuration_changed', array('request_digest_mismatch'));
                    $this->store->save($reference, $session);
                    throw new GatewayException('La configuracion del formulario cambio. No se reenviara el intento.');
                }
                return $fields;
            }
            $session['secure_acceptance_request'] = array(
                'transaction_uuid' => $fields['transaction_uuid'],
                'profile_id' => $fields['profile_id'],
                'transaction_type' => $fields['transaction_type'],
                'signed_date_time' => $fields['signed_date_time'],
                'credential_fingerprint' => $service->credentialFingerprint(),
                'request_digest' => $digest,
                'environment' => $this->config->get('environment', 'test'),
            );
            $session['environment'] = $this->config->get('environment', 'test');
            $session['isolated_test'] = SideEffectPolicy::isolated($this->config);
            $session['charge_group'] = isset($session['charge_group']) ? $session['charge_group'] : hash('sha256', $reference);
            $session['status'] = 'SUBMITTED';
            $this->store->save($reference, $session);
            return $fields;
        });
    }
}
