<?php

namespace LM\CyberSource;

class OrderFulfillment
{
    private $store;
    private $sendOrder;
    private $sendEmail;
    private $config;

    public function __construct(Config $config, PaymentSessionStore $store, SecureLogger $logger,
        ?callable $sendOrder = null, ?callable $sendEmail = null)
    {
        $this->store = $store;
        $this->config = $config;
        $forwarder = new OrderForwarder($config, $logger);
        $mailer = new OrderReferenceMailer($config, $logger);
        $this->sendOrder = $sendOrder ?: function ($order) use ($forwarder) { return $forwarder->send($order, true); };
        $this->sendEmail = $sendEmail ?: function ($order, $response, $reference) use ($mailer) {
            return $mailer->send($order, $response, $reference);
        };
    }

    public function process($reference)
    {
        return $this->store->withLock('fulfill-' . $reference, function () use ($reference) {
            $session = $this->store->withLock('inbox-' . $reference, function () use ($reference, &$claimed) {
                return $this->change($reference, function ($session) {
                if (!empty($session['payment_verified']) && (!isset($session['environment'])
                    || $session['environment'] !== $this->config->get('environment', 'test')
                    || !isset($session['isolated_test']) || $session['isolated_test'] !== SideEffectPolicy::isolated($this->config))) {
                    return Reconciliation::hold($session, 'environment_mismatch', array('execution_environment_mismatch'));
                }
                if (ChargeGroups::blocked($this->store, $session) && !Reconciliation::held($session)) {
                    return Reconciliation::hold($session, 'retry_group_conflict', array('retry_group_conflict'));
                }
                if (empty($session['payment_verified']) || empty($session['catalog_verified'])
                    || Reconciliation::held($session)
                    || $this->store->hasPendingEvents($session['reference'])
                    || !in_array($session['status'], array('PAID', 'ORDER_SENT'), true)) {
                    return $session;
                }
                if (isset($session['external_order_response'])) {
                    if ($session['status'] === 'PAID' && OrderForwarder::isConfirmed($session['external_order_response'])
                        && isset($session['order_dispatch']['state']) && $session['order_dispatch']['state'] === 'CONFIRMED_HELD') {
                        if (!$this->store->claimQuotation($session['reference'], $session['external_order_response']['idcotizacion'], $session['environment'])) {
                            return Reconciliation::hold($session, 'quotation_identity', array('quotation_already_owned_by_another_reference'));
                        }
                        $session['status'] = 'ORDER_SENT';
                        $session['order_dispatch']['state'] = 'CONFIRMED_AFTER_REVIEW';
                        $session['order_dispatch']['released_at'] = gmdate('c');
                    }
                    return $session;
                }
                if (!empty($session['order_dispatch'])) {
                    return $session;
                }
                if (empty($session['provider_authorization_number']) || trim($session['provider_authorization_number']) === '') {
                    return Reconciliation::hold($session, 'missing_authorization', array('missing_authorization'));
                }
                SideEffectPolicy::assertOrderTransport($this->config, (string) $this->config->get('external_order_api.url', ''));
                $session['order_payload']['no_auto_tarjeta'] = $session['provider_authorization_number'];
                $session['order_dispatch'] = array('state' => 'SENDING', 'started_at' => gmdate('c'),
                    'payload_hash' => hash('sha256', json_encode($session['order_payload'])));
                return $session;
                }, $claimed);
            });
            $claimed = $claimed && !isset($session['external_order_response'])
                && isset($session['order_dispatch']['state']) && $session['order_dispatch']['state'] === 'SENDING';

            if (empty($session['payment_verified']) || empty($session['catalog_verified']) || Reconciliation::held($session)) {
                return $session;
            }

            // The durable SENDING marker is a one-shot claim. No automatic resend
            // after timeouts/crashes until the remote API offers verified idempotency.
            if ($claimed) {
                try {
                    $response = call_user_func($this->sendOrder, $session['order_payload']);
                    if (!is_array($response) || !OrderForwarder::isConfirmed($response)) {
                        throw new GatewayException('Pedido no confirmado.');
                    }
                    $session = $this->change($reference, function ($current) use ($response) {
                        $current['external_order_response'] = $response;
                        if (!$this->store->claimQuotation($current['reference'], $response['idcotizacion'], $current['environment'])) {
                            $current = Reconciliation::hold($current, 'quotation_identity', array('quotation_already_owned_by_another_reference'));
                        }
                        if (ChargeGroups::blocked($this->store, $current) && !Reconciliation::held($current)) {
                            $current = Reconciliation::hold($current, 'retry_group_conflict', array('retry_group_conflict'));
                        }
                        $held = Reconciliation::held($current) || $this->store->hasPendingEvents($current['reference']);
                        $current['order_dispatch']['state'] = $held ? 'CONFIRMED_HELD' : 'CONFIRMED';
                        $current['order_dispatch']['confirmed_at'] = gmdate('c');
                        $current['status'] = $held ? 'PAID' : 'ORDER_SENT';
                        return $current;
                    });
                } catch (\Throwable $e) {
                    $session = $this->change($reference, function ($current) {
                        $current['order_dispatch']['state'] = 'UNKNOWN';
                        $current['order_dispatch']['needs_reconciliation'] = true;
                        return $current;
                    });
                    return $session;
                }
            }
            if ($session['status'] !== 'ORDER_SENT' || empty($session['external_order_response'])
                || !OrderForwarder::isConfirmed($session['external_order_response'])) {
                return $session;
            }
            // Session lock linearizes the final hold check and local mail acceptance.
            // An incoming receipt is already durable in the inbox if this lock is busy.
            return $this->store->withLock('session-' . $reference, function () use ($reference) {
                $current = $this->store->find($reference);
                if (Reconciliation::held($current) || ChargeGroups::blocked($this->store, $current) || isset($current['order_email'])
                    || $current['status'] !== 'ORDER_SENT') {
                    return $current;
                }
                SideEffectPolicy::assertOrderTransport($this->config, (string) $this->config->get('external_order_api.url', ''));
                $current = $this->store->withLock('inbox-' . $reference, function () use ($current, $reference) {
                    if ($this->store->hasPendingEvents($reference)) {
                        return $current;
                    }
                    $current['order_email'] = array('state' => 'SENDING', 'started_at' => gmdate('c'));
                    return $this->store->save($reference, $current);
                });
                if (!isset($current['order_email'])) {
                    return $current;
                }
                try {
                    $result = call_user_func($this->sendEmail, $current['order_payload'], $current['external_order_response'], $reference);
                    if (!is_array($result)) {
                        throw new GatewayException('Resultado de correo no valido.');
                    }
                    $result['state'] = !empty($result['sent']) ? 'SENT' : (!empty($result['attempted']) ? 'FAILED' : 'SKIPPED');
                } catch (\Throwable $e) {
                    $result = array('state' => 'UNKNOWN', 'sent' => false, 'reason' => 'needs_reconciliation');
                }
                $current['order_email'] = $result;
                return $this->store->save($reference, $current);
            });
        }, 0);
    }

    private function change($reference, callable $callback, &$changed = null)
    {
        return $this->store->withLock('session-' . $reference, function () use ($reference, $callback, &$changed) {
            $session = $this->store->find($reference);
            if (!$session) {
                throw new GatewayException('Referencia no encontrada.');
            }
            $updated = $callback($session);
            $changed = $updated !== $session;
            return $changed ? $this->store->save($reference, $updated) : $session;
        });
    }
}
