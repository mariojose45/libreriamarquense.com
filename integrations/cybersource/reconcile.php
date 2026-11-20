<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/Autoload.php';

try {
    $optionNames = array('list', 'approved-unconfirmed', 'show', 'reference:', 'revision:', 'operator:',
        'match-customer', 'match-amount', 'match-items', 'match-status', 'resolve-event:', 'link-order:',
        'evidence:', 'confirm', 'allow-production');
    $options = getopt('', $optionNames, $restIndex);
    if ($restIndex !== count($argv)) {
        throw new RuntimeException('Argumentos inesperados.');
    }
    foreach (array_slice($argv, 1) as $argument) {
        if (strpos($argument, '--') === 0 && !in_array(explode('=', substr($argument, 2), 2)[0],
            array_map(function ($name) { return rtrim($name, ':'); }, $optionNames), true)) {
            throw new RuntimeException('Opcion desconocida.');
        }
    }
    foreach ($options as $value) {
        if (is_array($value)) {
            throw new RuntimeException('No se permiten opciones duplicadas.');
        }
    }
    $modes = (int) isset($options['list']) + (int) isset($options['show'])
        + (int) isset($options['resolve-event']) + (int) isset($options['link-order']);
    if ($modes !== 1 || isset($options['approved-unconfirmed']) && !isset($options['list'])) {
        throw new RuntimeException('Seleccione una sola operacion.');
    }
    if (isset($options['list']) || isset($options['show'])) {
        foreach (array('revision', 'operator', 'match-customer', 'match-amount', 'match-items', 'match-status', 'evidence', 'confirm') as $field) {
            if (isset($options[$field])) {
                throw new RuntimeException('Las consultas no admiten opciones de modificacion.');
            }
        }
    }
    $config = \LM\CyberSource\Config::forProcess(dirname(__DIR__, 2));
    if (!is_dir($config->get('paths.session_dir'))) {
        throw new RuntimeException('Almacenamiento inexistente.');
    }
    $store = new \LM\CyberSource\PaymentSessionStore($config->get('paths.session_dir'));
    $review = new \LM\CyberSource\ManualReconciliation($store, $config);
    if (isset($options['list'])) {
        if (!isset($options['approved-unconfirmed'])) {
            foreach ($store->eventIds() as $id) {
                $record = $store->findEvent($id);
                if ($record['state'] !== 'APPLIED') {
                    echo json_encode(array('reference' => $record['reference'], 'event_id' => $id,
                        'inbox_state' => $record['state'])), PHP_EOL;
                }
            }
        }
        $unreadable = 0;
        foreach ($store->references() as $reference) {
            try {
                $summary = $review->describe($reference);
                $pending = in_array($summary['status'], array('PAID', 'REVIEW', 'SUBMITTED'), true)
                    || $summary['financial_review'] || $summary['charge_group_blocked'] || $summary['pending_notifications']
                    || in_array($summary['dispatch'], array('UNKNOWN', 'SENDING', 'CONFIRMED_HELD'), true)
                    || in_array($summary['email_state'], array('FAILED', 'UNKNOWN', 'SENDING'), true);
                if (isset($options['approved-unconfirmed']) ? $summary['approved_without_confirmed_order'] : $pending) {
                    echo json_encode($summary), PHP_EOL;
                }
            } catch (Throwable $e) {
                $unreadable++;
                echo json_encode(array('reference' => $reference, 'requires_investigation' => true, 'reason' => 'unreadable_local_record')), PHP_EOL;
            }
        }
        exit($unreadable ? 2 : 0);
    }
    if (!isset($options['reference']) || !is_string($options['reference'])) {
        throw new RuntimeException('Indique la referencia.');
    }
    if (isset($options['show'])) {
        echo json_encode($review->describe($options['reference'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), PHP_EOL;
        exit(0);
    }
    \LM\CyberSource\SideEffectPolicy::assertWorker($config, $argv);
    if (!in_array('--confirm', $argv, true) || !isset($options['evidence']) || !is_string($options['evidence'])) {
        throw new RuntimeException('Seleccione una operacion y confirme la evidencia verificada.');
    }
    $action = isset($options['resolve-event']) ? 'resolve' : 'link-order';
    $value = $action === 'resolve' ? $options['resolve-event'] : $options['link-order'];
    $confirmations = array('operator' => isset($options['operator']) ? $options['operator'] : null,
        'expected_revision' => isset($options['revision']) ? $options['revision'] : null,
        'customer_matches' => in_array('--match-customer', $argv, true),
        'amount_matches' => in_array('--match-amount', $argv, true),
        'items_match' => in_array('--match-items', $argv, true),
        'status_matches' => in_array('--match-status', $argv, true));
    $review->apply($options['reference'], $action, $value, $options['evidence'], $confirmations, $argv);
    echo "Revision guardada. No se ejecutaron cobros, pedidos ni correos.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Revision no confirmada. Consulte --show antes de repetir: puede existir una intencion durable. Verifique revision, operador, confirmaciones, notificaciones, evidencia y autorizacion.\n");
    exit(1);
}
