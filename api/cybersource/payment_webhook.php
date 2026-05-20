<?php

require __DIR__ . '/_bootstrap.php';

try {
    lm_require_post();

    $input = lm_read_json_body();
    $checkout = new \LM\CyberSource\HostedCheckoutService($config);
    $secureAcceptance = new \LM\CyberSource\SecureAcceptanceService($config);
    if (isset($input['signed_field_names'], $input['signature'])) {
        $trusted = $secureAcceptance->validateResponse($input);
    } else {
        $trusted = $checkout->validateProviderSignature($input);
    }
    $token = (string) lm_request_header('X-Internal-Token');
    $adminToken = (string) $config->get('security.admin_token', '');

    if (!$trusted && ($adminToken === '' || !hash_equals($adminToken, $token))) {
        lm_json_response(array('success' => false, 'message' => 'Webhook no autorizado.'), 403);
    }

    if (isset($input['signed_field_names'], $input['signature'])) {
        $reference = $secureAcceptance->extractReference($input);
        $status = $secureAcceptance->normalizeDecision($input);
    } else {
        $reference = isset($input['ref']) ? $input['ref'] : (isset($input['reference']) ? $input['reference'] : '');
        $statusParam = isset($input['status']) ? $input['status'] : (isset($input['payment_status']) ? $input['payment_status'] : '');
        $status = $checkout->normalizeStatus($statusParam);
    }

    if (!lm_payment_reference_is_valid($reference)) {
        throw new \LM\CyberSource\GatewayException('Referencia no valida.');
    }

    $session = $store->find($reference);

    if (!$session) {
        throw new \LM\CyberSource\GatewayException('Referencia no encontrada.');
    }

    if (lm_session_is_expired($session)) {
        $session['status'] = 'EXPIRED';
        $store->save($reference, $session);
        throw new \LM\CyberSource\GatewayException('La sesion de pago vencio.');
    }

    $session['provider_webhook'] = array(
        'status' => $status,
        'payload' => $input,
        'received_at' => gmdate('c'),
    );

    if ($status === 'APPROVED') {
        if (!lm_provider_amount_matches_session($input, $session)) {
            throw new \LM\CyberSource\GatewayException('El monto aprobado no coincide con el total del pedido.');
        }

        $session['status'] = 'PAID';
        $session['provider_transaction_id'] = $secureAcceptance->extractTransactionId($input);
        $session['provider_authorization_number'] = $secureAcceptance->extractAuthorizationNumber($input);
        $store->save($reference, $session);
        $session = lm_finalize_paid_session($session, $config, $store, $logger);
    } elseif ($status === 'DECLINED' || $status === 'CANCELLED') {
        $session['status'] = $status;
        $store->save($reference, $session);
    } else {
        $session['status'] = 'AWAITING_CONFIRMATION';
        $store->save($reference, $session);
    }

    lm_json_response(array('success' => true, 'reference' => $reference, 'status' => $session['status']));
} catch (\LM\CyberSource\GatewayException $e) {
    $logger->warning('Webhook rechazado', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => $e->getMessage()), 400);
} catch (Throwable $e) {
    $logger->error('Error inesperado en webhook', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => 'No se pudo procesar el webhook.'), 500);
}
