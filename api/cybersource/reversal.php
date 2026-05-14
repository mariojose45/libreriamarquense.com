<?php

require __DIR__ . '/_bootstrap.php';

try {
    lm_require_post();
    lm_require_admin_token($config);
    $input = lm_read_json_body();

    foreach (array('payment_id', 'amount') as $field) {
        if (empty($input[$field])) {
            throw new \LM\CyberSource\GatewayException('Falta el campo requerido: ' . $field);
        }
    }

    $response = lm_payment_service($config, $logger)->reverseAuthorization($input['payment_id'], $input['amount']);
    lm_json_response(array('success' => true, 'response' => $response));
} catch (\LM\CyberSource\GatewayException $e) {
    lm_json_response(array('success' => false, 'message' => $e->getMessage()), 400);
} catch (Throwable $e) {
    $logger->error('Error en reversal', array('message' => $e->getMessage()));
    lm_json_response(array('success' => false, 'message' => 'No se pudo reversar la autorizacion.'), 500);
}
