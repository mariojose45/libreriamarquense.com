<?php
if (!isset($auth, $escape, $nonce)) {
    http_response_code(404);
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conciliacion privada - Libreria Marquense</title>
    <style nonce="<?php echo $escape($nonce); ?>">
        body { font: 16px system-ui, sans-serif; color: #182038; background: #f4f6fb; margin: 0; }
        main { max-width: 1100px; margin: 30px auto; padding: 24px; background: white; }
        input, button { font: inherit; padding: 9px; margin: 5px 0; max-width: 95%; }
        label { display: block; margin: 12px 0; } button { cursor: pointer; }
        .notice { background: #fff1c9; padding: 16px; } .danger { border: 2px solid #8b2222; padding: 18px; }
        table { border-collapse: collapse; width: 100%; } th, td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
        pre { white-space: pre-wrap; overflow-wrap: anywhere; background: #f4f6fb; padding: 12px; }
    </style>
</head>
<body><main>
    <h1>Conciliacion privada</h1>
    <?php if ($flash !== ''): ?><p class="notice"><?php echo $escape($flash); ?></p><?php endif; ?>
    <?php if (!$actor): ?>
        <p>Acceso exclusivo para operadores autorizados. No use cuentas compartidas.</p>
        <form method="post" action="<?php echo $escape($settings['url_path']); ?>">
            <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
            <input type="hidden" name="action" value="login">
            <label>Usuario <input name="username" autocomplete="username" maxlength="64" required></label>
            <label>Contrasena <input type="password" name="password" autocomplete="current-password" maxlength="72" required></label>
            <button>Iniciar sesion</button>
        </form>
    <?php else: ?>
        <p>Operador: <strong><?php echo $escape($actor); ?></strong>. Cierre la sesion al terminar.</p>
        <form method="post" action="<?php echo $escape($settings['url_path']); ?>">
            <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
            <button name="action" value="logout">Cerrar sesion</button>
            <button name="action" value="list">Volver al inicio de pendientes</button>
        </form>
        <?php if (!$canLink): ?><p class="notice">Solo consulta: las vinculaciones estan bloqueadas. Se requieren autorizacion productiva, permiso propio del panel y configuracion compatible del hosting.</p><?php endif; ?>
        <p>El panel no consulta automaticamente SistemasOL y nunca crea ni reenvia pedidos.</p>
        <form method="post" action="<?php echo $escape($settings['url_path']); ?>">
            <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
            <input type="hidden" name="action" value="show">
            <label>Referencia LMQ <input name="reference" maxlength="40" pattern="LMQ-[0-9]{14}-[A-F0-9]{8}" required></label>
            <button>Consultar referencia</button>
        </form>
        <h2>Pagos aprobados sin pedido confirmado</h2>
        <p>Resumen leido del almacenamiento actual, sin cache. Consulte cada referencia para revisar eventos e incidencias completos.</p>
        <table><thead><tr><th>Referencia</th><th>Total</th><th>Estado de envio</th><th>Consulta</th></tr></thead><tbody>
        <?php foreach ($page['items'] as $item): ?>
            <tr><td><?php echo $escape($item['reference']); ?></td><td><?php echo $escape($item['currency'] . ' ' . $item['amount']); ?></td>
                <td><?php echo $escape($item['dispatch']); ?></td><td>
                    <form method="post" action="<?php echo $escape($settings['url_path']); ?>">
                        <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
                        <input type="hidden" name="action" value="show">
                        <input type="hidden" name="reference" value="<?php echo $escape($item['reference']); ?>">
                        <button>Revisar</button>
                    </form>
                </td></tr>
        <?php endforeach; ?>
        </tbody></table>
        <?php if (!$page['items']): ?><p>No hay resultados en este segmento. Esto no demuestra que una cotizacion no exista en SistemasOL.</p><?php endif; ?>
        <?php if ($page['unreadable']): ?><p class="notice">Registros locales ilegibles que requieren investigacion; no se presume su estado de pago: <?php echo $escape(implode(', ', $page['unreadable'])); ?>.</p><?php endif; ?>
        <?php if ($page['next'] !== ''): ?>
            <form method="post" action="<?php echo $escape($settings['url_path']); ?>">
                <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
                <button name="action" value="next">Siguiente segmento</button>
            </form>
        <?php endif; ?>
        <?php if ($detail): ?>
            <h2><?php echo $escape($detail['reference']); ?></h2>
            <p><?php echo $escape($detail['currency'] . ' ' . $detail['amount'] . ' | ' . $detail['status'] . ' | ' . $detail['dispatch']); ?>
                | Revision <?php echo $escape($detail['revision']); ?></p>
            <p>Fecha de creacion (UTC): <?php echo $escape($detail['created_at']); ?>. Ambiente: <?php echo $escape($detail['environment']); ?>.</p>
            <?php if ($detail['quotation_id'] !== null): ?><p>Cotizacion vinculada: <?php echo $escape($detail['quotation_id']); ?></p><?php endif; ?>
            <p>Revision financiera: <?php echo $detail['financial_review'] ? 'SI' : 'NO'; ?>;
                grupo bloqueado: <?php echo $detail['charge_group_blocked'] ? 'SI' : 'NO'; ?>;
                notificaciones pendientes: <?php echo $detail['pending_notifications'] ? 'SI' : 'NO'; ?>.</p>
            <h3>Datos minimos para verificar en SistemasOL</h3>
            <pre><?php echo $escape(json_encode(array('cliente' => $detail['customer'], 'articulos' => $detail['items']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>
            <?php if ($canLink && $reviewToken !== ''): ?>
                <form method="post" class="danger" action="<?php echo $escape($settings['url_path']); ?>">
                    <h3>Vincular una cotizacion existente</h3>
                    <p>Busque y verifique personalmente la cotizacion en SistemasOL. Si no aparece o hay dudas, deje el caso pendiente. Esta revision vence en cinco minutos.</p>
                    <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
                    <input type="hidden" name="action" value="link">
                    <input type="hidden" name="review_token" value="<?php echo $escape($reviewToken); ?>">
                    <label>ID de cotizacion existente <input name="quotation" inputmode="numeric" pattern="[1-9][0-9]{0,19}" maxlength="20" required></label>
                    <label>ID de evidencia o ticket (sin contrasenas ni datos de tarjeta) <input name="evidence" minlength="8" maxlength="161" required></label>
                    <label><input type="checkbox" name="customer_matches" value="1" required> Verifique que corresponde al mismo cliente.</label>
                    <label><input type="checkbox" name="amount_matches" value="1" required> Verifique importe y moneda exactos.</label>
                    <label><input type="checkbox" name="items_match" value="1" required> Verifique articulos, cantidades y precios.</label>
                    <label><input type="checkbox" name="status_matches" value="1" required> Verifique que la cotizacion existe y su estado corresponde.</label>
                    <label><input type="checkbox" name="authorize_link" value="1" required> Autorizo expresamente esta vinculacion local, sin crear ni reenviar pedidos.</label>
                    <label>Confirme su contrasena <input type="password" name="password" autocomplete="current-password" maxlength="72" required></label>
                    <button>Vincular cotizacion verificada</button>
                </form>
            <?php else: ?><p>La vinculacion no esta disponible para esta referencia o configuracion. No se ofrece reenvio automatico.</p><?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</main></body></html>
