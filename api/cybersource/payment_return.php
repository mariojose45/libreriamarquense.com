<?php

require __DIR__ . '/_bootstrap.php';

$params = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

$checkout = new \LM\CyberSource\HostedCheckoutService($config);
$secureAcceptance = new \LM\CyberSource\SecureAcceptanceService($config);

if (isset($params['signed_field_names'], $params['signature'])) {
    $reference = $secureAcceptance->extractReference($params);
    $trusted = $secureAcceptance->validateResponse($params);
    $status = $secureAcceptance->normalizeDecision($params);
} else {
    $reference = isset($params['ref']) ? $params['ref'] : (isset($params['reference']) ? $params['reference'] : '');
    $statusParam = isset($params['status']) ? $params['status'] : (isset($params['payment_status']) ? $params['payment_status'] : '');
    $trusted = $checkout->validateProviderSignature($params);
    $status = $checkout->normalizeStatus($statusParam);
}

$title = 'Pago en validacion';
$message = 'Recibimos el retorno del portal de pago. Tu pedido queda en revision mientras se confirma la transaccion.';
$clearCart = false;

try {
    if (!lm_payment_reference_is_valid($reference)) {
        throw new \LM\CyberSource\GatewayException('Referencia de pago no valida.');
    }

    $session = $store->find($reference);

    if (!$session) {
        throw new \LM\CyberSource\GatewayException('No encontramos la referencia de pago.');
    }

    if (lm_session_is_expired($session)) {
        $session['status'] = 'EXPIRED';
        $store->save($reference, $session);
        throw new \LM\CyberSource\GatewayException('La sesion de pago vencio.');
    }

    $session['provider_return'] = array(
        'trusted' => $trusted,
        'status' => $status,
        'params' => $params,
        'received_at' => gmdate('c'),
    );

    if ($trusted && $status === 'APPROVED') {
        if (!lm_provider_amount_matches_session($params, $session)) {
            throw new \LM\CyberSource\GatewayException('El monto aprobado no coincide con el total del pedido.');
        }

        $session['status'] = 'PAID';
        $session['provider_transaction_id'] = $secureAcceptance->extractTransactionId($params);
        $session['provider_authorization_number'] = $secureAcceptance->extractAuthorizationNumber($params);
        $store->save($reference, $session);

        $session = lm_finalize_paid_session($session, $config, $store, $logger);
        $title = 'Pago aprobado';
        $message = 'Tu pago fue aprobado y el pedido fue enviado correctamente.';
        $clearCart = true;
    } elseif ($trusted && $status === 'DECLINED') {
        $session['status'] = 'DECLINED';
        $store->save($reference, $session);
        $title = 'Pago rechazado';
        $message = 'El portal de pago rechazo la transaccion. Puedes volver al carrito e intentarlo nuevamente.';
    } elseif ($trusted && $status === 'CANCELLED') {
        $session['status'] = 'CANCELLED';
        $store->save($reference, $session);
        $title = 'Pago cancelado';
        $message = 'El pago fue cancelado. Puedes volver al carrito cuando desees continuar.';
    } else {
        $session['status'] = 'AWAITING_CONFIRMATION';
        $store->save($reference, $session);
    }
} catch (Throwable $e) {
    $logger->warning('Retorno de pago no finalizado', array('reference' => $reference, 'message' => $e->getMessage()));
    $title = 'Pago pendiente';
    $message = 'No fue posible confirmar automaticamente el pago. Conserva tu referencia y contactanos para validarlo.';
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, sans-serif; background: #f5f7fb; color: #1f2937; }
        .box { width: min(92vw, 520px); background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 18px 50px rgba(15, 23, 42, .16); text-align: center; }
        h1 { margin: 0 0 12px; color: #1A2697; font-size: 28px; }
        p { margin: 0 0 22px; line-height: 1.55; }
        .ref { display: inline-block; margin-bottom: 24px; padding: 8px 12px; border-radius: 8px; background: #EEF2FF; color: #1A2697; font-weight: 700; }
        a { display: inline-flex; align-items: center; justify-content: center; min-height: 46px; padding: 0 22px; border-radius: 8px; background: #1A2697; color: #fff; text-decoration: none; font-weight: 700; }
    </style>
</head>
<body>
    <main class="box">
        <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php if ($reference !== ''): ?>
            <div class="ref">Referencia: <?php echo htmlspecialchars($reference, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <br>
        <a href="/index.php" id="return-home-link">Volver al inicio</a>
    </main>
    <script>
        (function () {
            const shouldClearCart = <?php echo $clearCart ? 'true' : 'false'; ?>;
            const homeUrl = '/index.php';
            const cartStorageKeys = [
                'compusisgt_carrito',
                'compusisgt_carrito_timestamp',
                'compusisgt_carrito_hash'
            ];

            function clearCartStorage(targetWindow) {
                if (!shouldClearCart || !targetWindow) {
                    return;
                }

                try {
                    cartStorageKeys.forEach(function (key) {
                        targetWindow.sessionStorage.removeItem(key);
                    });
                } catch (error) {
                    console.warn('No se pudo limpiar el carrito de la ventana indicada:', error);
                }
            }

            function goHomeAndClosePaymentTab(event) {
                if (event) {
                    event.preventDefault();
                }

                clearCartStorage(window);

                try {
                    if (window.opener && !window.opener.closed) {
                        clearCartStorage(window.opener);
                        window.opener.location.replace(homeUrl);
                        window.close();

                        window.setTimeout(function () {
                            if (!window.closed) {
                                window.location.replace(homeUrl);
                            }
                        }, 250);
                        return;
                    }
                } catch (error) {
                    console.warn('No se pudo controlar la pestana original:', error);
                }

                window.location.replace(homeUrl);
            }

            clearCartStorage(window);
            if (window.opener && !window.opener.closed) {
                clearCartStorage(window.opener);
            }

            const returnHomeLink = document.getElementById('return-home-link');
            if (returnHomeLink) {
                returnHomeLink.addEventListener('click', goHomeAndClosePaymentTab);
            }
        }());
    </script>
</body>
</html>
