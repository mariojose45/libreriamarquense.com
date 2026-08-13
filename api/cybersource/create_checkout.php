<?php

require __DIR__ . '/_bootstrap.php';

function lm_checkout_device_fingerprint_id_from_input(array $input)
{
    $value = '';
    foreach (array('deviceFingerprintID', 'device_fingerprint_id') as $key) {
        if (isset($input[$key]) && trim((string) $input[$key]) !== '') {
            $value = trim((string) $input[$key]);
            break;
        }
    }

    if ($value === '') {
        return '';
    }

    if (!preg_match('/^[A-Za-z0-9_-]{1,88}$/', $value)) {
        throw new \LM\CyberSource\GatewayException('El identificador de Device Fingerprint no es valido.');
    }

    return $value;
}

try {
    lm_require_post();

    if (!$config->isEnabled()) {
        lm_json_response(array(
            'success' => false,
            'configured' => false,
            'message' => 'La pasarela de pago aun no esta habilitada. Configure config/cybersource.private.php.',
        ), 503);
    }

    if (!$config->hasSecureAcceptance() && !$config->hasHostedCheckout()) {
        lm_json_response(array(
            'success' => false,
            'configured' => false,
            'message' => 'Falta configurar Secure Acceptance o la URL oficial de Neonet.',
        ), 503);
    }

    $input = lm_read_json_body();
    $deviceFingerprintId = lm_checkout_device_fingerprint_id_from_input($input);

    if (!empty($input['website'])) {
        lm_json_response(array('success' => false, 'message' => 'Solicitud no valida.'), 400);
    }

    lm_require_csrf_token('checkout', $input);
    lm_require_rate_limit_json('checkout:' . lm_client_ip(), 8, 300);
    unset($input['csrf_token'], $input['website'], $input['deviceFingerprintID'], $input['device_fingerprint_id']);

    $validator = new \LM\CyberSource\OrderPayloadValidator($config);
    $orderPayload = $validator->validateForHostedCheckout($input);

    $sessionTtl = (int) $config->get('secure_acceptance.session_ttl_minutes', $config->get('hosted_checkout.session_ttl_minutes', 60));
    $currency = (string) $config->get('currency', 'GTQ');

    $sessionData = array(
        'status' => 'PENDING',
        'amount' => $orderPayload['total_venta'],
        'currency' => $currency,
        'order_payload' => $orderPayload,
        'expires_at' => gmdate('c', time() + ($sessionTtl * 60)),
        'provider' => $config->hasSecureAcceptance() ? 'cybersource_secure_acceptance' : 'neonet_cybersource_hosted',
    );

    if ($deviceFingerprintId !== '') {
        $sessionData['device_fingerprint_id'] = $deviceFingerprintId;
    }

    $session = $store->create($sessionData);

    if ($config->hasSecureAcceptance()) {
        $siteBaseUrl = rtrim((string) $config->get('site.base_url'), '/');
        $redirectUrl = $siteBaseUrl . '/api/cybersource/secure_acceptance_redirect.php?ref=' . rawurlencode($session['reference']);
    } else {
        $checkout = new \LM\CyberSource\HostedCheckoutService($config);
        $redirectUrl = $checkout->buildRedirectUrl($session);
    }

    $logger->info('Checkout hospedado creado', array(
        'reference' => $session['reference'],
        'amount' => $session['amount'],
        'currency' => $session['currency'],
        'device_fingerprint' => $deviceFingerprintId !== '',
    ));

    lm_json_response(array(
        'success' => true,
        'reference' => $session['reference'],
        'redirect_url' => $redirectUrl,
    ));
} catch (\LM\CyberSource\GatewayException $e) {
    $logger->warning('Checkout rechazado', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => $e->getMessage()), 400);
} catch (Throwable $e) {
    $logger->error('Error inesperado creando checkout', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => 'No se pudo iniciar el pago en linea.'), 500);
}
