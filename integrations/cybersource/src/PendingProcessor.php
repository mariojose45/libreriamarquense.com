<?php

namespace LM\CyberSource;

class PendingProcessor
{
    public static function run(Config $config, array $args, ?SecureLogger $logger = null)
    {
        SideEffectPolicy::assertWorker($config, $args);
        $store = new PaymentSessionStore($config->get('paths.session_dir'));
        $logger = $logger ?: new SecureLogger($config->get('paths.log_file'));
        // Serialize sweeps; per-reference locks still coordinate browser callbacks.
        return $store->withLock('pending-worker-global', function () use ($config, $store, $logger) {
            $notifications = new SecureAcceptanceNotification(new SecureAcceptanceService($config), $store);
            $processor = new OrderFulfillment($config, $store, $logger);
            $failures = 0;
            foreach ($store->eventIds() as $id) {
                try {
                    $record = $store->findEvent($id);
                    if ($record['state'] !== 'APPLIED') {
                        $notifications->applyEvent($id);
                    }
                } catch (\Throwable $e) {
                    $failures++;
                    $logger->warning('Evento durable pendiente', array('event_id' => $id, 'exception' => get_class($e)));
                }
            }
            foreach ($store->references() as $reference) {
                try {
                    $session = $store->find($reference);
                    if (!empty($session['payment_verified']) && in_array($session['status'], array('PAID', 'ORDER_SENT'), true)) {
                        $session = $processor->process($reference);
                    }
                    $dispatch = isset($session['order_dispatch']['state']) ? $session['order_dispatch']['state'] : '';
                    $email = isset($session['order_email']['state']) ? $session['order_email']['state'] : '';
                    if (Reconciliation::held($session) || in_array($dispatch, array('SENDING', 'UNKNOWN', 'CONFIRMED_HELD'), true)
                        || $session['status'] === 'PAID' && (empty($session['payment_verified']) || empty($session['catalog_verified']))
                        || $session['status'] === 'SUBMITTED' && !empty($session['expires_at']) && strtotime($session['expires_at']) <= time()
                        || in_array($email, array('SENDING', 'UNKNOWN', 'FAILED'), true)) {
                        $failures++;
                        $logger->warning('Referencia requiere revision', array('reference' => $reference, 'state' => $session['status']));
                    }
                } catch (\Throwable $e) {
                    $failures++;
                    $logger->warning('Procesamiento pendiente', array('reference' => $reference, 'exception' => get_class($e)));
                }
            }
            return $failures;
        }, 0);
    }
}
