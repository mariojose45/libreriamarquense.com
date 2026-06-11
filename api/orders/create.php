<?php

require dirname(__DIR__) . '/cybersource/_bootstrap.php';

try {
    lm_require_post();

    $input = lm_read_json_body();

    if (!empty($input['website'])) {
        lm_json_response(array('success' => false, 'message' => 'Solicitud no valida.'), 400);
    }

    lm_require_csrf_token('checkout', $input);
    lm_require_rate_limit_json('order:' . lm_client_ip(), 8, 300);
    unset($input['csrf_token'], $input['website']);

    $validator = new \LM\CyberSource\OrderPayloadValidator($config);
    $orderPayload = $validator->validateForOrder($input, array('Pago Contra Entrega', 'Transferencia'));

    $forwarder = new \LM\CyberSource\OrderForwarder($config, $logger);
    $response = $forwarder->send($orderPayload);

    $logger->info('Pedido enviado a API externa', array(
        'payment_method' => $orderPayload['forma_pago'],
        'amount' => $orderPayload['total_venta'],
        'shipping' => $orderPayload['envio'],
    ));

    lm_json_response($response);
} catch (\LM\CyberSource\GatewayException $e) {
    $logger->warning('Pedido rechazado', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => $e->getMessage()), 400);
} catch (Throwable $e) {
    $logger->error('Error inesperado procesando pedido', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => 'No se pudo procesar el pedido.'), 500);
}
