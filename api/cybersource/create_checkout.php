<?php

require __DIR__ . '/_bootstrap.php';

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
    $validator = new \LM\CyberSource\OrderPayloadValidator($config);
    $orderPayload = $validator->validateForHostedCheckout($input);

    $sessionTtl = (int) $config->get('secure_acceptance.session_ttl_minutes', $config->get('hosted_checkout.session_ttl_minutes', 60));
    $currency = (string) $config->get('currency', 'GTQ');

    $session = $store->create(array(
        'status' => 'PENDING',
        'amount' => $orderPayload['total_venta'],
        'currency' => $currency,
        'order_payload' => $orderPayload,
        'expires_at' => gmdate('c', time() + ($sessionTtl * 60)),
        'provider' => $config->hasSecureAcceptance() ? 'cybersource_secure_acceptance' : 'neonet_cybersource_hosted',
    ));

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
