<?php

require __DIR__ . '/_bootstrap.php';

try {
    lm_require_post();

    $input = \LM\CyberSource\SecureAcceptanceNotification::parseForm(
        file_get_contents('php://input', false, null, 0, 131073),
        isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : ''
    );
    $secureAcceptance = new \LM\CyberSource\SecureAcceptanceService($config);
    $notifications = new \LM\CyberSource\SecureAcceptanceNotification($secureAcceptance, $store);
    $session = $notifications->receive($input, 'merchant_post');
    // The saved PAID session is the durable job. Never wait for external HTTP/mail here.
    lm_json_response(array('success' => true));
} catch (\LM\CyberSource\PaymentStorageException $e) {
    $logger->error('Notificacion pendiente por almacenamiento', array('exception' => get_class($e)));
    header('Retry-After: 30');
    lm_json_response(array('success' => false, 'message' => 'Procesamiento temporalmente no disponible.'), 503);
} catch (\LM\CyberSource\GatewayException $e) {
    $logger->warning('Webhook rechazado', array('exception' => get_class($e)));
    lm_json_response(array('success' => false, 'message' => 'Notificacion no aceptada.'), 400);
} catch (Throwable $e) {
    $logger->error('Error inesperado en webhook', array('exception' => get_class($e)));
    lm_json_response(array('success' => false, 'message' => 'No se pudo procesar el webhook.'), 500);
}
