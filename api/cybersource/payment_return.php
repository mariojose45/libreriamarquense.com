<?php

require __DIR__ . '/_bootstrap.php';

$reference = '';
$title = 'Pago en validacion';
$message = 'Recibimos el retorno del portal de pago. Tu pedido queda en revision mientras se confirma la transaccion.';
$clearCart = false;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new \LM\CyberSource\GatewayException('Se requiere el retorno firmado de CyberSource.');
    }
    $params = \LM\CyberSource\SecureAcceptanceNotification::parseForm(
        file_get_contents('php://input', false, null, 0, 131073),
        isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : ''
    );
    $service = new \LM\CyberSource\SecureAcceptanceService($config);
    $notifications = new \LM\CyberSource\SecureAcceptanceNotification($service, $store);
    $session = $notifications->receive($params, 'customer_response');
    $reference = $session['reference'];
    if (!empty($session['payment_verified']) && empty($session['notification_queued'])) {
        try {
            $session = lm_finalize_paid_session($session, $config, $store, $logger);
        } catch (Throwable $e) {
            $logger->warning('Pedido pendiente de procesamiento durable', array('reference' => $reference));
        }
        $held = \LM\CyberSource\Reconciliation::held($session) || \LM\CyberSource\ChargeGroups::blocked($store, $session)
            || $store->hasPendingEvents($reference);
        $title = $held ? 'Pago en revision' : 'Pago aprobado';
        $message = $held
            ? 'Se registro un pago, pero existe una incidencia que necesita conciliacion. No vuelvas a pagar; conserva tu referencia.'
            : ($session['status'] === 'ORDER_SENT'
            ? 'Tu pago fue aprobado y el pedido fue enviado correctamente.'
            : 'Tu pago fue aprobado. El registro del pedido esta pendiente de confirmacion; no vuelvas a pagar. Conserva tu referencia.');
        $clearCart = true;
    } elseif ($session['status'] === 'DECLINED') {
        $title = 'Pago rechazado';
        $message = 'El portal de pago rechazo la transaccion. Puedes volver al carrito e intentarlo nuevamente.';
    } elseif ($session['status'] === 'CANCELLED') {
        $title = 'Pago cancelado';
        $message = 'El pago fue cancelado. Puedes volver al carrito cuando desees continuar.';
    }
} catch (Throwable $e) {
    $logger->warning('Retorno de pago no finalizado', array('reference' => $reference, 'exception' => get_class($e)));
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
