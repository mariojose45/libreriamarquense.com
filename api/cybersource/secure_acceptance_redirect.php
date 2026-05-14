<?php

require __DIR__ . '/_bootstrap.php';

$reference = isset($_GET['ref']) ? $_GET['ref'] : '';

try {
    if (!$config->isEnabled() || !$config->hasSecureAcceptance()) {
        throw new \LM\CyberSource\GatewayException('La pasarela no esta configurada.');
    }

    $session = $store->find($reference);
    if (!$session) {
        throw new \LM\CyberSource\GatewayException('No encontramos la referencia de pago.');
    }

    if (isset($session['expires_at']) && strtotime($session['expires_at']) < time()) {
        $session['status'] = 'EXPIRED';
        $store->save($reference, $session);
        throw new \LM\CyberSource\GatewayException('La sesion de pago vencio. Vuelve al carrito para intentarlo de nuevo.');
    }

    $secureAcceptance = new \LM\CyberSource\SecureAcceptanceService($config);
    $fields = $secureAcceptance->buildSignedFormFields($session);
    $endpoint = $config->getSecureAcceptanceEndpoint();

    $session['secure_acceptance_request'] = array(
        'endpoint' => $endpoint,
        'transaction_uuid' => $fields['transaction_uuid'],
        'signed_at' => gmdate('c'),
    );
    $store->save($reference, $session);
} catch (Throwable $e) {
    $logger->warning('No se pudo redirigir a Secure Acceptance', array('reference' => $reference, 'message' => $e->getMessage()));
    http_response_code(400);
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!doctype html>
    <html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>No se pudo iniciar el pago</title>
        <style>
            body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, sans-serif; background: #f5f7fb; color: #1f2937; }
            .box { width: min(92vw, 500px); background: #fff; border-radius: 12px; padding: 26px; box-shadow: 0 18px 50px rgba(15, 23, 42, .16); text-align: center; }
            h1 { margin: 0 0 12px; color: #1A2697; font-size: 25px; }
            p { margin: 0 0 20px; line-height: 1.5; }
            a { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 18px; border-radius: 8px; background: #1A2697; color: #fff; text-decoration: none; font-weight: 700; }
        </style>
    </head>
    <body>
        <main class="box">
            <h1>No se pudo iniciar el pago</h1>
            <p><?php echo htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'); ?></p>
            <a href="/cart.php">Volver al carrito</a>
        </main>
    </body>
    </html>
    <?php
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirigiendo al pago seguro</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; font-family: Arial, sans-serif; background: #f5f7fb; color: #1f2937; }
        .box { width: min(92vw, 500px); background: #fff; border-radius: 12px; padding: 26px; box-shadow: 0 18px 50px rgba(15, 23, 42, .16); text-align: center; }
        h1 { margin: 0 0 10px; color: #1A2697; font-size: 25px; }
        p { margin: 0; line-height: 1.5; }
        button { margin-top: 20px; min-height: 42px; padding: 0 18px; border: 0; border-radius: 8px; background: #1A2697; color: #fff; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <main class="box">
        <h1>Pago seguro</h1>
        <p>Te estamos redirigiendo a la pagina segura de pago.</p>
        <form id="secure-acceptance-form" method="post" action="<?php echo htmlspecialchars($endpoint, ENT_QUOTES, 'UTF-8'); ?>">
            <?php foreach ($fields as $name => $value): ?>
                <input type="hidden" name="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endforeach; ?>
            <button type="submit">Continuar al pago</button>
        </form>
    </main>
    <script>
        document.getElementById('secure-acceptance-form').submit();
    </script>
</body>
</html>
