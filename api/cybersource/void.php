<?php

require __DIR__ . '/_bootstrap.php';

try {
    lm_require_post();
    lm_require_admin_token($config);
    $input = lm_read_json_body();

    if (empty($input['capture_id'])) {
        throw new \LM\CyberSource\GatewayException('Falta el campo requerido: capture_id');
    }

    $response = lm_payment_service($config, $logger)->voidCapture($input['capture_id']);
    lm_json_response(array('success' => true, 'response' => $response));
} catch (\LM\CyberSource\GatewayException $e) {
    lm_json_response(array('success' => false, 'message' => $e->getMessage()), 400);
} catch (Throwable $e) {
    $logger->error('Error en void', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => 'No se pudo anular la transaccion.'), 500);
}
