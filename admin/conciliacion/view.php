<?php
if (!isset($auth, $escape, $nonce, $section)) {
    http_response_code(404);
    exit;
}
$showDetail = $detail !== null || $detailError !== '';
$labels = array('home' => 'Inicio', 'search' => 'Consultar referencia', 'pending' => 'Pagos pendientes', 'incidents' => 'Incidencias', 'cron_check' => 'Comprobación previa al Cron');
$statusLabels = array(
    'PENDING' => 'Pendiente', 'SUBMITTED' => 'En espera de respuesta', 'PAID' => 'Pago aprobado',
    'ORDER_SENT' => 'Pedido registrado', 'REVIEW' => 'En revisión', 'DECLINED' => 'Rechazado',
    'CANCELLED' => 'Cancelado', 'EXPIRED' => 'Vencido', 'NOT_STARTED' => 'Sin registro de envío',
    'SENDING' => 'En proceso', 'UNKNOWN' => 'Sin confirmar', 'CONFIRMED' => 'Confirmado',
    'CONFIRMED_MANUAL' => 'Confirmado manualmente', 'CONFIRMED_HELD' => 'Confirmado con incidencia',
    'SENT' => 'Enviado', 'FAILED' => 'Fallido', 'SKIPPED' => 'Omitido', 'APPLIED' => 'Aplicado',
    'RECEIVED' => 'Recibido', 'APPROVED' => 'Aprobado', 'HELD' => 'Retenido', 'UNVERIFIED' => 'No verificado'
);
$reasonLabels = array(
    'PAYMENT_REVIEW' => 'Revisión del pago', 'INCOMPLETE_VERIFICATION' => 'Verificación incompleta',
    'ORDER_UNCONFIRMED' => 'Pedido sin confirmar', 'ERP_SENDING' => 'Envío en proceso',
    'ERP_UNKNOWN' => 'Envío sin confirmar', 'ERP_CONFIRMED_HELD' => 'Pedido confirmado con incidencia',
    'EMAIL_SENDING' => 'Correo en proceso', 'EMAIL_UNKNOWN' => 'Correo sin confirmar',
    'EMAIL_FAILED' => 'Fallo de correo', 'ATTEMPT_EXPIRED' => 'Intento vencido'
);
$display = function ($value, $fallback = 'No disponible') {
    return is_scalar($value) && (string) $value !== '' ? (string) $value : $fallback;
};
$statusLabel = function ($value) use ($statusLabels, $display) {
    return isset($statusLabels[$value]) ? $statusLabels[$value] : $display($value);
};
$dateLabel = function ($value) use ($display) {
    if (!is_string($value) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:Z|[+-][0-9]{2}:[0-9]{2})$/D', $value)) {
        return $display($value);
    }
    try { return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'))->format('d/m/Y H:i:s') . ' UTC'; }
    catch (Exception $e) { return $display($value); }
};
$tone = function ($value) {
    if (in_array($value, array('ORDER_SENT', 'CONFIRMED', 'CONFIRMED_MANUAL', 'SENT', 'APPLIED'), true)) { return 'success'; }
    if (in_array($value, array('FAILED', 'DECLINED'), true)) { return 'danger'; }
    if (in_array($value, array('REVIEW', 'HELD', 'UNKNOWN', 'CONFIRMED_HELD', 'SUBMITTED', 'SENDING'), true)) { return 'warning'; }
    return 'neutral';
};
$originLabel = isset($labels[$section]) ? $labels[$section] : 'Inicio';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conciliación privada | Librería Marquense</title>
    <style nonce="<?php echo $escape($nonce); ?>">
        :root { color-scheme: light; --ink: #17233c; --muted: #647087; --brand: #283594; --line: #e3e8f1; --bg: #f6f8fc; }
        * { box-sizing: border-box; }
        body { margin: 0; color: var(--ink); background: var(--bg); font: 15px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; }
        button, input { font: inherit; } button { cursor: pointer; } button, summary { -webkit-tap-highlight-color: transparent; }
        button:focus-visible, input:focus-visible, summary:focus-visible { outline: 3px solid #aab8ff; outline-offset: 3px; }
        form { margin: 0; } h1, h2, p { margin-top: 0; } h1 { font-size: clamp(1.65rem, 3vw, 2.1rem); line-height: 1.25; letter-spacing: -.035em; margin-bottom: 10px; }
        h2 { font-size: 1.05rem; margin-bottom: 8px; } p { margin-bottom: 16px; }
        .topbar { background: #fff; border-bottom: 1px solid var(--line); }
        .topbar-inner, .workspace, .footer { width: min(1184px, 100%); margin: auto; padding-left: 32px; padding-right: 32px; }
        .topbar-inner { min-height: 88px; display: flex; align-items: center; justify-content: space-between; gap: 24px; padding-top: 16px; padding-bottom: 16px; }
        .brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
        .brand-mark { width: 42px; height: 42px; background: var(--brand); color: white; border-radius: 12px; display: grid; place-items: center; font-size: 14px; font-weight: 750; flex: 0 0 auto; }
        .brand-name { display: block; font-size: 15px; letter-spacing: -.02em; } .brand-caption { display: block; color: var(--muted); font-size: 12px; margin-top: 1px; }
        .account { display: flex; align-items: center; justify-content: flex-end; flex-wrap: wrap; gap: 8px 14px; }
        .operator { color: var(--muted); font-size: 13px; overflow-wrap: anywhere; }
        .button { display: inline-flex; justify-content: center; align-items: center; min-height: 42px; padding: 9px 16px; border: 1px solid var(--line); border-radius: 9px; background: #fff; color: var(--ink); font-size: 14px; font-weight: 600; text-decoration: none; }
        .button:hover { background: #f1f4fa; border-color: #bac4d7; }
        .button-primary { color: #fff; background: var(--brand); border-color: var(--brand); }
        .button-primary:hover { color: #fff; background: #1c287a; border-color: #1c287a; }
        .button-quiet { background: transparent; border-color: transparent; color: var(--muted); }
        .button-quiet:hover { border-color: transparent; }
        .workspace { padding-top: 32px; padding-bottom: 24px; min-height: calc(100vh - 148px); }
        .breadcrumb { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; color: var(--muted); font-size: 13px; margin-bottom: 24px; }
        .breadcrumb span + span::before { content: "/"; color: #a3adbf; margin-right: 8px; }
        .page-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; margin-bottom: 28px; }
        .eyebrow { color: var(--brand); font-size: 11px; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; margin-bottom: 10px; }
        .muted { color: var(--muted); } .intro { color: var(--muted); margin-bottom: 0; max-width: 660px; }
        .badge { display: inline-flex; align-items: center; max-width: 100%; padding: 4px 9px; border-radius: 6px; font-size: 12px; font-weight: 650; line-height: 1.5; }
        .badge-neutral { color: #526078; background: #edf1f7; } .badge-success { color: #276047; background: #e8f4ed; }
        .badge-warning { color: #855d19; background: #fff3d9; } .badge-danger { color: #9a3640; background: #fcecef; }
        .readonly { white-space: nowrap; color: var(--brand); background: #eaf0ff; }
        .menu-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px; }
        .menu-card { text-align: left; color: inherit; background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 26px; min-height: 238px; display: flex; flex-direction: column; align-items: flex-start; transition: border-color .15s, box-shadow .15s; }
        .menu-card:hover { border-color: #98a6d6; box-shadow: 0 8px 25px #2634730a; }
        .card-number { display: grid; place-items: center; width: 38px; height: 34px; border-radius: 8px; background: #eef1fb; color: var(--brand); font-size: 12px; font-weight: 750; margin-bottom: 22px; }
        .card-title { font-size: 18px; font-weight: 700; letter-spacing: -.025em; margin-bottom: 8px; }
        .card-description { color: var(--muted); font-size: 14px; line-height: 1.6; margin-bottom: 22px; }
        .card-cta { color: var(--brand); font-size: 13px; font-weight: 650; margin-top: auto; }
        .panel { background: #fff; border: 1px solid var(--line); border-radius: 13px; overflow: hidden; margin-bottom: 20px; }
        .panel-body { padding: 24px; } .search-panel { max-width: 760px; }
        .field-label { display: block; font-size: 13px; font-weight: 650; margin-bottom: 8px; }
        input:not([type=hidden]):not([type=checkbox]) { width: 100%; min-width: 0; min-height: 46px; padding: 11px 13px; border: 1px solid #cdd5e2; border-radius: 8px; background: #fff; color: var(--ink); }
        .search-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 12px; } .search-row .button { min-height: 46px; }
        .hint { color: var(--muted); font-size: 12px; margin: 10px 0 0; }
        .notice { border: 1px solid #ead6a8; background: #fffaed; border-radius: 10px; padding: 14px 18px; color: #76571e; margin-bottom: 20px; font-size: 13px; overflow-wrap: anywhere; }
        .notice-error { color: #91323d; border-color: #efced3; background: #fff3f4; }
        .mono { font-family: ui-monospace, SFMono-Regular, Consolas, monospace; letter-spacing: -.025em; }
        .reference { overflow-wrap: anywhere; font-size: 13px; font-weight: 650; }
        .table-wrap { overflow-x: auto; } .data-table { width: 100%; border-collapse: collapse; text-align: left; }
        .data-table th { padding: 14px 20px; color: var(--muted); background: #fafbfe; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; border-bottom: 1px solid var(--line); }
        .data-table td { padding: 17px 20px; border-bottom: 1px solid var(--line); font-size: 13px; vertical-align: middle; }
        .data-table tr:last-child td { border-bottom: 0; } .data-table tbody tr:hover { background: #fcfdff; }
        .data-table .actions { text-align: right; } .cell-note { display: block; margin-top: 4px; color: var(--muted); font-size: 12px; }
        .reason { color: #755c2c; font-size: 12px; line-height: 1.7; }
        .empty { padding: 48px 24px; text-align: center; } .empty p { color: var(--muted); margin-bottom: 0; font-size: 14px; }
        .empty-title { display: block; font-size: 16px; font-weight: 650; margin-bottom: 7px; }
        .pagination { display: flex; align-items: center; justify-content: space-between; gap: 16px; color: var(--muted); font-size: 12px; margin-top: 16px; }
        .detail-reference { font-size: clamp(1.1rem, 2.2vw, 1.5rem); overflow-wrap: anywhere; letter-spacing: -.03em; margin-bottom: 4px; }
        .summary-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1px; background: var(--line); margin: 0; }
        .summary-item { background: #fff; padding: 22px; min-width: 0; }
        dt { color: var(--muted); font-size: 12px; margin-bottom: 9px; } dd { margin: 0; font-size: 14px; font-weight: 600; overflow-wrap: anywhere; }
        .amount { font-size: 23px; letter-spacing: -.04em; }
        details > summary { list-style: none; cursor: pointer; padding: 20px 24px; font-size: 14px; font-weight: 650; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        details > summary::-webkit-details-marker { display: none; }
        details > summary::after { content: "+"; color: var(--muted); font-size: 20px; line-height: 1; }
        details[open] > summary::after { content: "−"; }
        details[open] > summary { border-bottom: 1px solid var(--line); }
        .history-count { font-size: 12px; font-weight: 500; color: var(--muted); margin-left: 8px; }
        .events { list-style: none; padding: 0; margin: 0; }
        .event { display: flex; justify-content: space-between; gap: 20px; padding: 16px 24px; border-bottom: 1px solid var(--line); }
        .event:last-child { border-bottom: 0; } .event p { margin: 5px 0 0; color: var(--muted); font-size: 12px; overflow-wrap: anywhere; }
        .event time { color: var(--muted); font-size: 12px; white-space: nowrap; }
        .technical-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 20px 24px; margin: 0; }
        .technical-note { color: var(--muted); font-size: 12px; margin-top: 20px; margin-bottom: 0; }
        .technical-items { margin-top: 20px; } .technical-items th, .technical-items td { padding-left: 12px; padding-right: 12px; }
        .footer { color: #758098; font-size: 11px; padding-top: 12px; padding-bottom: 24px; }
        .login { max-width: 430px; margin: 42px auto 0; } .login .panel-body { padding: 32px; }
        .login h1 { font-size: 25px; } .login label { margin-top: 20px; } .login .button { width: 100%; margin-top: 24px; }
        .login-intro { font-size: 14px; color: var(--muted); }
        .manual-link { padding: 24px; border: 1px solid #ead6a8; border-radius: 12px; background: #fffaf0; }
        .manual-link label { display: block; margin: 12px 0; font-size: 13px; } .manual-link input[type=checkbox] { margin-right: 8px; }
        @media (max-width: 900px) {
            .menu-grid { gap: 12px; } .menu-card { padding: 22px; } .summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .technical-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .topbar-inner, .workspace, .footer { padding-left: 18px; padding-right: 18px; }
            .topbar-inner { align-items: flex-start; flex-direction: column; gap: 14px; padding-top: 18px; padding-bottom: 14px; }
            .account { justify-content: space-between; width: 100%; } .account .operator { flex: 1; } .account .button { min-height: 40px; padding: 8px 10px; }
            .workspace { padding-top: 24px; } .page-heading { gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
            .menu-grid { grid-template-columns: minmax(0, 1fr); gap: 12px; } .menu-card { min-height: 0; padding: 22px; }
            .card-number { margin-bottom: 15px; } .card-description { margin-bottom: 14px; }
            .search-row { grid-template-columns: minmax(0, 1fr); } .panel-body { padding: 20px; } .breadcrumb { margin-bottom: 18px; }
            .table-wrap { overflow: visible; } .data-table thead { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); }
            .data-table, .data-table tbody, .data-table tr, .data-table td { display: block; width: 100%; }
            .data-table tr { padding: 18px; border-bottom: 1px solid var(--line); } .data-table tr:last-child { border-bottom: 0; }
            .data-table td { padding: 5px 0; border-bottom: 0; display: grid; grid-template-columns: 76px minmax(0, 1fr); gap: 12px; }
            .data-table td::before { content: attr(data-label); font-size: 12px; color: var(--muted); }
            .data-table td.reference-cell, .data-table td.actions { display: block; }
            .data-table td.reference-cell::before, .data-table td.actions::before { display: none; }
            .data-table td.reference-cell { margin-bottom: 8px; } .data-table td.actions { padding-top: 12px; } .actions .button { width: 100%; }
            .summary-item { padding: 18px; } .technical-grid { grid-template-columns: minmax(0, 1fr); }
            details > summary { padding: 18px; } .event { padding: 16px 18px; flex-direction: column; gap: 7px; }
            .pagination { align-items: flex-start; flex-wrap: wrap; } .login { margin-top: 8px; } .login .panel-body { padding: 24px; }
            .technical-items .data-table td { grid-template-columns: 92px minmax(0, 1fr); }
        }
        @media (max-width: 360px) { .summary-grid { grid-template-columns: minmax(0, 1fr); } }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; } }
    </style>
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <div class="brand"><span class="brand-mark" aria-hidden="true">LM</span><div><strong class="brand-name">Librería Marquense</strong><span class="brand-caption">Conciliación privada</span></div></div>
        <?php if ($actor): ?>
        <form class="account" method="post" action="<?php echo $escape($settings['url_path']); ?>">
            <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
            <span class="operator"><?php echo $escape($actor); ?></span>
            <?php if ($section !== 'home' || $showDetail): ?><button class="button button-quiet" name="action" value="home">Volver al inicio</button><?php endif; ?>
            <button class="button" name="action" value="logout">Cerrar sesión</button>
        </form>
        <?php else: ?><span class="badge readonly">Acceso restringido</span><?php endif; ?>
    </div>
</header>
<main class="workspace" id="panel-content">
    <?php if ($flash !== ''): ?><p class="notice" role="status"><?php echo $escape($flash); ?></p><?php endif; ?>
    <?php if (!$actor): ?>
        <section class="panel login" aria-labelledby="login-title"><div class="panel-body">
            <p class="eyebrow">Acceso administrativo</p><h1 id="login-title">Iniciar sesión</h1>
            <p class="login-intro">Ingresá con tu cuenta individual para consultar operaciones de la tienda.</p>
            <form method="post" action="<?php echo $escape($settings['url_path']); ?>">
                <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
                <input type="hidden" name="action" value="login">
                <label class="field-label" for="username">Usuario</label><input id="username" name="username" autocomplete="username" maxlength="64" required>
                <label class="field-label" for="password">Contraseña</label><input id="password" type="password" name="password" autocomplete="current-password" maxlength="72" required>
                <button class="button button-primary">Iniciar sesión</button>
            </form>
            <p class="hint">Uso exclusivo de operadores autorizados. Cerrá la sesión al terminar.</p>
        </div></section>
    <?php else: ?>
        <?php if ($section !== 'home' || $showDetail): ?><nav class="breadcrumb" aria-label="Ubicación"><span>Inicio</span><span><?php echo $escape($originLabel); ?></span><?php if ($showDetail): ?><span>Detalle de operación</span><?php endif; ?></nav><?php endif; ?>
        <?php if ($showDetail): ?>
            <section id="detalle-operacion" aria-labelledby="detail-title">
                <div class="page-heading"><div><p class="eyebrow">Consulta de operación</p><h1 id="detail-title" class="detail-reference mono"><?php echo $escape($detail ? $detail['reference'] : $selectedReference); ?></h1><p class="intro">Resumen de la información disponible en el registro local.</p></div>
                    <form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button" name="action" value="back">Atrás</button></form>
                </div>
                <?php if ($detailError !== ''): ?>
                    <div class="notice notice-error" role="alert"><?php echo $escape($detailError); ?></div>
                <?php else: ?>
                    <?php if ($detail['historical']): ?><p class="notice">Registro histórico: faltan metadatos de verificación actuales. La consulta no confirma el pago ni cambia sus estados.</p><?php endif; ?>
                    <?php if ($detail['financial_review'] || $detail['charge_group_blocked'] || $detail['pending_notifications']): ?><p class="notice">Esta operación necesita revisión o tiene notificaciones pendientes. No se autorizan reenvíos desde esta consulta.</p><?php endif; ?>
                    <div class="panel"><dl class="summary-grid">
                        <div class="summary-item"><dt>Importe</dt><dd class="amount"><?php echo $escape($display($detail['currency']) . ' ' . $display($detail['amount'])); ?></dd></div>
                        <div class="summary-item"><dt>Estado del pago / pedido</dt><dd><span class="badge badge-<?php echo $tone($detail['status']); ?>"><?php echo $escape($statusLabel($detail['status'])); ?></span></dd></div>
                        <div class="summary-item"><dt>Verificación del pago</dt><dd><span class="badge badge-<?php echo $detail['payment_verified'] ? 'success' : 'warning'; ?>"><?php echo $detail['payment_verified'] ? 'Verificado' : 'NO VERIFICADO'; ?></span></dd></div>
                        <div class="summary-item"><dt>Cotización vinculada</dt><dd><?php echo $escape($detail['quotation_id'] !== null ? '#' . $detail['quotation_id'] : 'Sin confirmar'); ?></dd></div>
                        <div class="summary-item"><dt>Fecha de creación (UTC)</dt><dd><?php echo $escape($dateLabel($detail['created_at'])); ?></dd></div>
                        <div class="summary-item"><dt>Envío a SistemasOL</dt><dd><span class="badge badge-<?php echo $tone($detail['dispatch']); ?>"><?php echo $escape($statusLabel($detail['dispatch'])); ?></span></dd></div>
                        <div class="summary-item"><dt>Correo de confirmación</dt><dd><?php echo $escape(!isset($detail['email_state']) || $detail['email_state'] === 'NOT_STARTED' ? 'Sin registro' : $statusLabel($detail['email_state'])); ?></dd></div>
                        <div class="summary-item"><dt>Modo de consulta</dt><dd><span class="badge readonly">Solo lectura</span></dd></div>
                    </dl></div>
                    <details class="panel history"<?php echo count($detail['events']) <= 3 ? ' open' : ''; ?>>
                        <summary><span>Eventos históricos <span class="history-count"><?php echo count($detail['events']) . (count($detail['events']) === 1 ? ' evento' : ' eventos'); ?><?php echo $detail['events_truncated'] ? ' (recientes)' : ''; ?></span></span></summary>
                        <?php if ($detail['events']): ?><ol class="events">
                            <?php foreach ($detail['events'] as $event): ?><li class="event"><div><span class="badge badge-<?php echo $tone($event['result']); ?>"><?php echo $escape($statusLabel($event['result'])); ?></span><p><?php echo $escape($event['decision'] . ' / ' . $event['reason_code'] . ' · ' . $statusLabel($event['state'])); ?></p><?php if ($event['reasons']): ?><p><?php echo $escape(implode(', ', $event['reasons'])); ?></p><?php endif; ?></div><time datetime="<?php echo $escape($event['received_at'] ?: ''); ?>"><?php echo $escape($dateLabel($event['received_at'])); ?></time></li><?php endforeach; ?>
                        </ol><?php else: ?><div class="panel-body"><p class="muted">No hay eventos durables disponibles para esta referencia. Su ausencia no confirma ni descarta un cobro.</p></div><?php endif; ?>
                        <?php if ($detail['events_truncated']): ?><div class="panel-body"><p class="hint">Se muestran los 50 eventos más recientes. El historial completo permanece conservado.</p></div><?php endif; ?>
                    </details>
                    <details class="panel" id="technical-details"><summary>Detalles técnicos</summary><div class="panel-body">
                        <dl class="technical-grid">
                            <div><dt>Ambiente del registro</dt><dd><?php echo $escape($display($detail['environment'], 'NO VERIFICADO')); ?></dd></div>
                            <div><dt>Revisión del registro</dt><dd><?php echo $escape($display($detail['revision'])); ?></dd></div>
                            <div><dt>Estado local</dt><dd class="mono"><?php echo $escape($detail['status']); ?></dd></div>
                            <div><dt>Revisión financiera</dt><dd><?php echo $detail['financial_review'] ? 'Sí' : 'No'; ?></dd></div>
                            <div><dt>Grupo bloqueado</dt><dd><?php echo $detail['charge_group_blocked'] ? 'Sí' : 'No'; ?></dd></div>
                            <div><dt>Notificaciones pendientes</dt><dd><?php echo $detail['pending_notifications'] ? 'Sí' : 'No'; ?></dd></div>
                            <?php if (!empty($detail['customer'])): ?><div><dt>Cliente</dt><dd><?php echo $escape($display(isset($detail['customer']['nombre_cliente']) ? $detail['customer']['nombre_cliente'] : null)); ?></dd></div><?php endif; ?>
                        </dl>
                        <?php if ($detail['review_reasons']): ?><p class="technical-note">Motivos de revisión: <?php echo $escape(implode(', ', $detail['review_reasons'])); ?>.</p><?php endif; ?>
                        <?php $items = isset($detail['items']['idarticulo']) && is_array($detail['items']['idarticulo']) ? $detail['items']['idarticulo'] : array(); ?>
                        <?php if ($items): ?><div class="technical-items table-wrap"><table class="data-table"><thead><tr><th>Artículo</th><th>Cantidad</th><th>Precio unitario</th></tr></thead><tbody>
                            <?php foreach ($items as $i => $id): ?><tr><td data-label="Artículo"><?php echo $escape($display($id)); ?></td><td data-label="Cantidad"><?php echo $escape($display(isset($detail['items']['cantidad'][$i]) ? $detail['items']['cantidad'][$i] : null)); ?></td><td data-label="Precio"><?php echo $escape($display(isset($detail['items']['precio_venta'][$i]) ? $detail['items']['precio_venta'][$i] : null)); ?></td></tr><?php endforeach; ?>
                        </tbody></table></div><?php endif; ?>
                        <p class="technical-note">Información mínima del almacenamiento local. El panel no consulta automáticamente SistemasOL ni muestra credenciales o datos de tarjeta.</p>
                    </div></details>
                    <?php if ($canLink && $reviewToken !== ''): ?>
                        <form method="post" class="manual-link" action="<?php echo $escape($settings['url_path']); ?>">
                            <h2>Vincular una cotización existente</h2><p>Verificá personalmente la cotización en SistemasOL. Si hay dudas, dejá el caso pendiente. La revisión vence en cinco minutos.</p>
                            <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><input type="hidden" name="action" value="link"><input type="hidden" name="review_token" value="<?php echo $escape($reviewToken); ?>">
                            <label>ID de cotización existente <input name="quotation" inputmode="numeric" pattern="[1-9][0-9]{0,19}" maxlength="20" required></label>
                            <label>ID de evidencia o ticket, sin datos secretos <input name="evidence" minlength="8" maxlength="161" required></label>
                            <label><input type="checkbox" name="customer_matches" value="1" required>Verifiqué el cliente.</label>
                            <label><input type="checkbox" name="amount_matches" value="1" required>Verifiqué importe y moneda.</label>
                            <label><input type="checkbox" name="items_match" value="1" required>Verifiqué artículos, cantidades y precios.</label>
                            <label><input type="checkbox" name="status_matches" value="1" required>Verifiqué la existencia y estado de la cotización.</label>
                            <label><input type="checkbox" name="authorize_link" value="1" required>Autorizo la vinculación local, sin crear ni reenviar pedidos.</label>
                            <label>Confirmá tu contraseña <input type="password" name="password" autocomplete="current-password" maxlength="72" required></label>
                            <button class="button button-primary">Vincular cotización verificada</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($section === 'search'): ?><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button button-primary" name="action" value="search">Volver a consultar</button></form><?php endif; ?>
            </section>
        <?php elseif ($section === 'cron_check'): ?>
            <section aria-labelledby="cron-title" data-screen="cron_check">
                <div class="page-heading"><div><p class="eyebrow">Comprobación de solo lectura</p><h1 id="cron-title">Cola antes de activar el Cron</h1><p class="intro">Clasificación de lo que podría intentar procesarse tras autorizar producción. No ejecuta el procesador ni cambia la configuración.</p></div><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button" name="action" value="back">Atrás</button></form></div>
                <?php if (!$cronPreview['complete']): ?><p class="notice notice-error" role="alert">Lectura incompleta: hubo errores o se alcanzó el límite de 5.000 entradas / 8 segundos. No interpretes ceros como ausencia de candidatos. No actives el Cron basándote en esta consulta.</p>
                <?php elseif ($cronPreview['no_automatic_sends_observed']): ?><p class="notice">Sin candidatos de envío, correo ni eventos pendientes detectados en esta lectura completa. Esto no autoriza la activación ni garantiza lo que exista en SistemasOL.</p>
                <?php else: ?><p class="notice notice-error" role="alert">Hay operaciones o eventos que requieren revisión antes de activar el Cron. No crees ni reenvíes cotizaciones para investigar estos registros.</p><?php endif; ?>
                <div class="panel"><dl class="summary-grid">
                    <div class="summary-item"><dt>Candidatos a enviar cotización</dt><dd class="amount" id="cron-order-count"><?php echo (int) $cronPreview['order_candidates']; ?></dd></div>
                    <div class="summary-item"><dt>Eventos sin aplicar</dt><dd class="amount" id="cron-event-count"><?php echo (int) $cronPreview['pending_events']; ?></dd></div>
                    <div class="summary-item"><dt>Posibles intentos de correo</dt><dd class="amount" id="cron-mail-count"><?php echo (int) $cronPreview['mail_candidates']; ?></dd></div>
                    <div class="summary-item"><dt>Errores de lectura</dt><dd class="amount"><?php echo (int) $cronPreview['errors']; ?></dd></div>
                </dl><div class="panel-body"><p class="hint">Leídos: <?php echo (int) $cronPreview['records']; ?> registros y <?php echo (int) $cronPreview['events']; ?> eventos. Fecha: <?php echo $escape($dateLabel($cronPreview['checked_at'])); ?>. Autorización productiva: <?php echo !$cronPreview['authorization_is_boolean'] ? 'tipo no booleano, bloqueada' : ($cronPreview['production_authorized'] ? 'habilitada (true)' : 'bloqueada (false)'); ?>.</p></div></div>
                <?php $cronReasons = array('order_send_candidate' => 'Podría intentar enviar una cotización', 'mail_send_candidate' => 'Podría intentar correo, incluso tras liberar una confirmación retenida', 'notification_can_change_eligibility' => 'El evento pendiente puede cambiar la elegibilidad', 'non_boolean_verification_flags' => 'Banderas no booleanas: el procesador las evalúa por valor no vacío', 'event_unreadable' => 'Evento ilegible', 'record_unreadable' => 'Registro o dependencias ilegibles'); ?>
                <?php if ($cronPreview['rows']): ?><div class="panel table-wrap"><table class="data-table"><thead><tr><th>Referencia</th><th>Fecha (UTC)</th><th>Importe</th><th>Estado</th><th>Motivo</th><th class="actions">Consulta</th></tr></thead><tbody>
                    <?php foreach ($cronPreview['rows'] as $row): ?><tr><td class="reference-cell" data-label="Referencia"><span class="reference mono"><?php echo $escape($display($row['reference'])); ?></span></td><td data-label="Fecha"><?php echo $escape($dateLabel($row['created_at'])); ?></td><td data-label="Importe"><?php echo $escape($row['amount'] === null ? 'No disponible' : $display($row['currency']) . ' ' . $row['amount']); ?></td><td data-label="Estado"><?php echo $escape($row['status']); ?></td><td data-label="Motivo"><?php echo $escape($cronReasons[$row['reason']]); ?></td><td class="actions" data-label="Consulta"><?php if ($row['reference'] !== null): ?><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><input type="hidden" name="action" value="show"><input type="hidden" name="reference" value="<?php echo $escape($row['reference']); ?>"><button class="button">Revisar</button></form><?php endif; ?></td></tr><?php endforeach; ?>
                </tbody></table></div><?php endif; ?>
                <?php if ($cronPreview['rows_truncated']): ?><p class="notice">Se muestran hasta 200 incidencias; los contadores incluyen todas las entradas leídas. No se autoriza activar con candidatos sin revisar.</p><?php endif; ?>
                <details class="panel"><summary>Desglose de los registros revisados</summary><div class="panel-body">
                    <?php $bucketLabels = array('not_selected' => 'Estado/pago no seleccionado por el procesador', 'environment_blocked' => 'Ambiente o aislamiento incompatible / ausente', 'catalog_blocked' => 'Catálogo sin confirmar', 'financial_hold' => 'Conciliación o grupo bloqueado', 'pending_notification' => 'Notificaciones pendientes en la referencia', 'response_present' => 'Con respuesta externa: no se reenvía la cotización', 'dispatch_present' => 'Con diario de envío: no se reenvía la cotización', 'authorization_missing' => 'Sin autorización del proveedor utilizable', 'order_candidate' => 'Candidato a un primer intento local de envío'); ?>
                    <dl class="technical-grid"><?php foreach ($cronPreview['buckets'] as $bucket => $count): ?><div><dt><?php echo $escape($bucketLabels[$bucket]); ?></dt><dd><?php echo (int) $count; ?></dd></div><?php endforeach; ?></dl>
                    <p class="technical-note">Estados almacenados: <?php foreach ($cronPreview['statuses'] as $status => $count) { echo $escape($status . ': ' . $count . ' '); } ?></p>
                </div></details>
                <p class="hint">Lectura secuencial, sin caché y no atómica. Repetila justo antes de decidir: una confirmación posterior puede cambiar la cola. No hay corte automático por antigüedad. Los candidatos no garantizan que la API o el correo acepten el intento.</p>
                <form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button" name="action" value="cron_check">Repetir comprobación de solo lectura</button></form>
            </section>
        <?php elseif ($section === 'home'): ?>
            <section aria-labelledby="home-title" data-screen="home">
                <div class="page-heading"><div><p class="eyebrow">Administración de operaciones</p><h1 id="home-title">Panel de conciliación</h1><p class="intro">Elegí una opción para consultar la información que necesitás.</p></div><span class="badge readonly">Solo consulta</span></div>
                <form class="menu-grid" method="post" action="<?php echo $escape($settings['url_path']); ?>">
                    <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>">
                    <button class="menu-card" name="action" value="search"><span class="card-number">01</span><span class="card-title">Consultar referencia</span><span class="card-description">Buscá una operación con su referencia LMQ y revisá su información.</span><span class="card-cta">Abrir consulta</span></button>
                    <button class="menu-card" name="action" value="list"><span class="card-number">02</span><span class="card-title">Pagos pendientes</span><span class="card-description">Revisá los pagos aprobados cuyo pedido está pendiente de confirmación.</span><span class="card-cta">Ver pendientes</span></button>
                    <button class="menu-card" name="action" value="incidents"><span class="card-number">03</span><span class="card-title">Incidencias</span><span class="card-description">Consultá los casos que requieren revisión de pago, pedido o correo.</span><span class="card-cta">Ver incidencias</span></button>
                </form>
            </section>
        <?php elseif ($section === 'search'): ?>
            <section aria-labelledby="search-title" data-screen="search">
                <div class="page-heading"><div><p class="eyebrow">Búsqueda individual</p><h1 id="search-title">Consultar referencia</h1><p class="intro">Ingresá una referencia LMQ completa para ver únicamente esa operación.</p></div><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button" name="action" value="back">Atrás</button></form></div>
                <div class="panel search-panel"><div class="panel-body"><form method="post" action="<?php echo $escape($settings['url_path']); ?>">
                    <input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><input type="hidden" name="action" value="show">
                    <label class="field-label" for="reference">Referencia LMQ</label><div class="search-row"><input class="mono" id="reference" name="reference" maxlength="40" pattern="LMQ-[0-9]{14}-[A-F0-9]{8}" placeholder="LMQ-AAAAMMDDHHMMSS-XXXXXXXX" autocomplete="off" spellcheck="false" required><button class="button button-primary">Consultar referencia</button></div><p class="hint">Usá la referencia completa del pago, no el número de cotización.</p>
                </form></div></div>
            </section>
        <?php else: ?>
            <section aria-labelledby="list-title" data-screen="<?php echo $escape($section); ?>">
                <div class="page-heading"><div><p class="eyebrow">Revisión de operaciones</p><h1 id="list-title"><?php echo $escape($originLabel); ?></h1><p class="intro"><?php echo $section === 'incidents' ? 'Casos de pago, pedido o correo que necesitan revisión.' : 'Pagos aprobados cuyo pedido aún necesita confirmación.'; ?></p></div><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button" name="action" value="back">Atrás</button></form></div>
                <div class="panel">
                <?php if ($page['items']): ?>
                    <div class="table-wrap"><table class="data-table"><thead><tr><th>Referencia</th><th>Importe</th><th>Estado</th><?php if ($section === 'incidents'): ?><th>Motivo</th><?php endif; ?><th class="actions">Consulta</th></tr></thead><tbody>
                    <?php foreach ($page['items'] as $item): ?><tr>
                        <td class="reference-cell" data-label="Referencia"><span class="reference mono"><?php echo $escape($item['reference']); ?></span></td>
                        <td data-label="Importe"><?php echo $escape($display($item['currency']) . ' ' . $display($item['amount'])); ?></td>
                        <td data-label="Estado"><div><span class="badge badge-<?php echo $tone($item['status']); ?>"><?php echo $escape($statusLabel($item['status'])); ?></span><span class="cell-note"><?php echo $escape($statusLabel($item['dispatch'])); ?></span></div></td>
                        <?php if ($section === 'incidents'): ?><td data-label="Motivo"><span class="reason"><?php $reasons = array_map(function ($reason) use ($reasonLabels) { return isset($reasonLabels[$reason]) ? $reasonLabels[$reason] : $reason; }, $item['reasons']); echo $escape(implode(' · ', $reasons)); ?></span></td><?php endif; ?>
                        <td class="actions" data-label="Consulta"><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><input type="hidden" name="action" value="show"><input type="hidden" name="reference" value="<?php echo $escape($item['reference']); ?>"><button class="button" aria-label="Revisar referencia <?php echo $escape($item['reference']); ?>">Revisar</button></form></td>
                    </tr><?php endforeach; ?>
                    </tbody></table></div>
                <?php else: ?><div class="empty"><strong class="empty-title"><?php echo $section === 'incidents' ? 'Sin incidencias en este segmento' : 'Sin pagos pendientes en este segmento'; ?></strong><p><?php echo $page['next'] !== '' ? 'Podés continuar con el siguiente segmento.' : 'No hay resultados adicionales para mostrar.'; ?></p></div><?php endif; ?>
                </div>
                <?php if ($page['unreadable']): ?><p class="notice">Hay registros ilegibles que requieren investigación privada: <?php echo $escape(implode(', ', $page['unreadable'])); ?>. No se presume su estado financiero.</p><?php endif; ?>
                <div class="pagination"><span>Información actual del almacenamiento local, sin caché.</span><?php if ($page['next'] !== ''): ?><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button" name="action" value="next">Siguiente segmento</button></form><?php endif; ?></div>
                <?php if ($section === 'pending'): ?><form method="post" action="<?php echo $escape($settings['url_path']); ?>"><input type="hidden" name="csrf" value="<?php echo $escape($auth->csrf()); ?>"><button class="button" name="action" value="cron_check">Comprobar cola antes de activar el Cron</button></form><?php endif; ?>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<footer class="footer">Acceso restringido. Las consultas no crean ni reenvían pedidos.</footer>
</body>
</html>
