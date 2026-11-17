<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/Autoload.php';

try {
    $config = \LM\CyberSource\Config::forProcess(dirname(__DIR__, 2));
    $options = getopt('', array('list', 'reference:', 'resolve-event:', 'link-order:', 'evidence:', 'confirm', 'allow-production'));
    if (!is_dir($config->get('paths.session_dir'))) {
        throw new RuntimeException('Almacenamiento inexistente.');
    }
    $store = new \LM\CyberSource\PaymentSessionStore($config->get('paths.session_dir'));
    if (isset($options['list'])) {
        foreach ($store->eventIds() as $id) {
            $record = $store->findEvent($id);
            if ($record['state'] !== 'APPLIED') {
                echo json_encode(array('reference' => $record['reference'], 'event_id' => $id,
                    'inbox_state' => $record['state'])), PHP_EOL;
            }
        }
        foreach ($store->references() as $reference) {
            $session = $store->find($reference);
            if (in_array($session['status'], array('PAID', 'REVIEW', 'SUBMITTED'), true)
                || \LM\CyberSource\Reconciliation::held($session) || isset($session['order_dispatch']['state'])
                && in_array($session['order_dispatch']['state'], array('UNKNOWN', 'SENDING', 'CONFIRMED_HELD'), true)
                || isset($session['order_email']['state']) && in_array($session['order_email']['state'], array('FAILED', 'UNKNOWN', 'SENDING'), true)) {
                echo json_encode(array('reference' => $reference, 'status' => $session['status'],
                    'issues' => isset($session['reconciliation']) ? $session['reconciliation'] : array(),
                    'dispatch' => isset($session['order_dispatch']['state']) ? $session['order_dispatch']['state'] : '',
                    'email' => isset($session['order_email']['state']) ? $session['order_email']['state'] : '')), PHP_EOL;
            }
        }
        exit(0);
    }
    \LM\CyberSource\SideEffectPolicy::assertWorker($config, $argv);
    if (!isset($options['confirm'], $options['reference'], $options['evidence'])
        || isset($options['resolve-event']) === isset($options['link-order'])) {
        throw new RuntimeException('Seleccione una operacion y confirme la evidencia verificada.');
    }
    $action = isset($options['resolve-event']) ? 'resolve' : 'link-order';
    $value = $action === 'resolve' ? $options['resolve-event'] : $options['link-order'];
    $review = new \LM\CyberSource\ManualReconciliation($store);
    $review->apply($options['reference'], $action, $value, $options['evidence']);
    echo "Revision guardada. No se ejecutaron cobros, pedidos ni correos.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "Revision no aplicada. Verifique estado, aislamiento y evidencia.\n");
    exit(1);
}
