<?php

namespace LM\CyberSource;

class ManualReconciliation
{
    private $store;
    private $config;

    public function __construct(PaymentSessionStore $store, Config $config)
    {
        $this->store = $store;
        $this->config = $config;
    }

    public static function validateReview(array $review, $requireMatches = true)
    {
        $operator = isset($review['operator']) ? $review['operator'] : null;
        $revision = isset($review['expected_revision']) ? $review['expected_revision'] : null;
        if (!is_string($operator) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{2,63}$/D', $operator)
            || (!is_int($revision) && !is_string($revision))
            || !preg_match('/^[1-9][0-9]*$/D', (string) $revision)
            || (string) (int) $revision !== (string) $revision) {
            throw new GatewayException('Indique operador y revision obtenida con --show.');
        }
        $validated = array('operator' => $operator, 'expected_revision' => (int) $revision);
        foreach (array('customer_matches', 'amount_matches', 'items_match', 'status_matches') as $field) {
            if ($requireMatches && (!isset($review[$field]) || $review[$field] !== true)) {
                throw new GatewayException('Confirme cliente, importe/moneda, articulos y estado contra SistemasOL.');
            }
            $validated[$field] = isset($review[$field]) && $review[$field] === true;
        }
        return $validated;
    }

    public static function assertLinkIntent($intent, $quotation)
    {
        if ($intent !== null && (!is_array($intent) || !isset($intent['idcotizacion'], $intent['state'])
            || $intent['idcotizacion'] !== $quotation || $intent['state'] !== 'RESERVED')) {
            throw new GatewayException('Existe una vinculacion previa. No puede sustituirse por otra cotizacion.');
        }
    }

    public function describe($reference, $details = false)
    {
        $session = $this->store->find($reference);
        if (!$session) {
            throw new GatewayException('Referencia no encontrada.');
        }
        $receipt = isset($session['verified_receipt']) ? $session['verified_receipt'] : array();
        $dispatch = isset($session['order_dispatch']) ? $session['order_dispatch'] : array();
        $response = isset($session['external_order_response']) ? $session['external_order_response'] : array();
        $confirmed = is_array($response) && OrderForwarder::isConfirmed($response);
        $result = array('reference' => $reference, 'revision' => isset($session['_revision']) ? $session['_revision'] : null,
            'environment' => isset($session['environment']) ? $session['environment'] : null,
            'payment_verified' => isset($session['payment_verified']) && $session['payment_verified'] === true,
            'status' => $session['status'], 'transaction_id' => isset($session['provider_transaction_id']) ? $session['provider_transaction_id'] : null,
            'authorization_code' => isset($session['provider_authorization_number']) ? $session['provider_authorization_number'] : null,
            'amount' => isset($session['amount']) ? Money::decimal(Money::cents($session['amount'])) : null,
            'currency' => isset($session['currency']) ? $session['currency'] : null,
            'created_at' => isset($session['created_at']) ? $session['created_at'] : null,
            'provider_signed_at' => isset($receipt['signed_date_time']) ? $receipt['signed_date_time'] : null,
            'last_confirmation_at' => isset($session['confirmed_at']) ? $session['confirmed_at'] : null,
            'provider_decision' => isset($receipt['decision']) ? $receipt['decision'] : null,
            'provider_reason_code' => isset($receipt['reason_code']) ? $receipt['reason_code'] : null,
            'dispatch' => isset($dispatch['state']) ? $dispatch['state'] : 'NOT_STARTED',
            'dispatch_started_at' => isset($dispatch['started_at']) ? $dispatch['started_at'] : null,
            'dispatch_unknown_at' => isset($dispatch['unknown_at']) ? $dispatch['unknown_at'] : null,
            'dispatch_confirmed_at' => isset($dispatch['confirmed_at']) ? $dispatch['confirmed_at'] : null,
            'manual_link_quotation' => isset($dispatch['manual_link']['idcotizacion']) ? $dispatch['manual_link']['idcotizacion'] : null,
            'manual_link_state' => isset($dispatch['manual_link']['state']) ? $dispatch['manual_link']['state'] : null,
            'quotation_id' => $confirmed ? $response['idcotizacion'] : null,
            'approved_without_confirmed_order' => isset($session['payment_verified']) && $session['payment_verified'] === true
                && (!$confirmed || $session['status'] !== 'ORDER_SENT'),
            'financial_review' => Reconciliation::held($session),
            'charge_group_blocked' => ChargeGroups::blocked($this->store, $session),
            'pending_notifications' => $this->store->hasPendingEvents($reference),
            'email_state' => isset($session['order_email']['state']) ? $session['order_email']['state'] : 'NOT_STARTED',
            'automatic_erp_lookup' => false, 'automatic_resend' => false);
        if ($details) {
            // This allowlist is for the private operator terminal, never application logs.
            $order = isset($session['order_payload']) ? $session['order_payload'] : array();
            $result['customer'] = array_intersect_key($order, array_flip(array('nit', 'tipo_documento_cliente', 'nombre_cliente')));
            $result['shipping_amount'] = isset($order['envio']) ? Money::decimal(Money::cents($order['envio'])) : null;
            $source = isset($order['datosArticulos']['articulos']) ? $order['datosArticulos']['articulos'] : array();
            $result['items'] = array_intersect_key($source, array_flip(array('idarticulo', 'cantidad', 'presen',
                'presentacion', 'tipo_presentacion', 'precio_venta', 'subtotal1')));
            $result['issues'] = array();
            foreach (isset($session['reconciliation']) ? $session['reconciliation'] : array() as $id => $issue) {
                $result['issues'][$id] = array_intersect_key($issue, array_flip(array('state', 'reasons', 'transaction_id')));
            }
            $result['operator_review'] = isset($dispatch['operator_review']) ? $dispatch['operator_review'] : null;
            $result['manual_link_review'] = isset($dispatch['manual_link']['review']) ? $dispatch['manual_link']['review'] : null;
            $result['manual_link_evidence'] = isset($dispatch['manual_link']['evidence']) ? $dispatch['manual_link']['evidence'] : null;
        }
        return $result;
    }

    public function apply($reference, $action, $value, $evidence, array $review = array(), array $args = array())
    {
        if (!in_array($action, array('resolve', 'link-order'), true) || !is_string($value)) {
            throw new GatewayException('Operacion manual no permitida.');
        }
        if (!is_string($evidence) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._:\/-]{7,160}$/D', $evidence)) {
            throw new GatewayException('Se requiere una referencia verificable de evidencia, sin datos secretos.');
        }
        $review = self::validateReview($review, $action === 'link-order');
        SideEffectPolicy::assertWorker($this->config, $args);
        return $this->store->withLock('fulfill-' . $reference, function () use ($reference, $action, $value, $evidence, $review) {
            return $this->store->withLock('session-' . $reference, function () use ($reference, $action, $value, $evidence, $review) {
                // Serialize the final pending-inbox check with durable receipt arrival.
                return $this->store->withLock('inbox-' . $reference, function () use ($reference, $action, $value, $evidence, $review) {
                    return $this->applyLocked($reference, $action, $value, $evidence, $review);
                });
            });
        }, 0);
    }

    private function applyLocked($reference, $action, $value, $evidence, array $review)
    {
        $session = $this->store->find($reference);
        if (!$session || !isset($session['_revision']) || $session['_revision'] !== $review['expected_revision']) {
            throw new GatewayException('La referencia cambio. Repita --show y verifique de nuevo antes de conciliar.');
        }
        if (!isset($session['environment'], $session['isolated_test'])
            || $session['environment'] !== $this->config->get('environment', 'test')
            || $session['isolated_test'] !== SideEffectPolicy::isolated($this->config)
            || $this->store->hasPendingEvents($reference)) {
            throw new GatewayException('Ambiente diferente o notificaciones pendientes: no se aplicara la revision.');
        }
        if (!isset($session['payment_verified']) || $session['payment_verified'] !== true || empty($session['verified_receipt'])
            || !isset($session['verified_receipt']['decision'], $session['verified_receipt']['reason_code'])
            || $session['verified_receipt']['decision'] !== 'ACCEPT' || $session['verified_receipt']['reason_code'] !== '100'
            || empty($session['provider_authorization_number']) || !empty($session['payment_conflicts'])
            || ChargeGroups::conflicted($this->store, $session)) {
            throw new GatewayException('No existe una confirmacion financiera completa sin conflictos.');
        }
        if ($action === 'resolve') {
            if (!isset($session['reconciliation'][$value]) || $session['reconciliation'][$value]['state'] !== 'OPEN') {
                throw new GatewayException('Incidencia no encontrada o ya resuelta.');
            }
            $session['reconciliation'][$value]['state'] = 'RESOLVED_BY_OPERATOR';
            $session['reconciliation'][$value]['evidence'] = $evidence;
            $session['reconciliation'][$value]['resolved_at'] = gmdate('c');
            if (!Reconciliation::held($session) && !empty($session['charge_group'])) {
                $group = $session['charge_group'];
                $this->store->withLock('charge-group-' . $group, function () use ($group, $session) {
                    $record = $this->store->readIndex('charge-group-' . $group);
                    if ($record && $record['state'] === 'REVIEW' && count($record['references']) === 1
                        && isset($record['references'][$session['reference']])) {
                        $record['state'] = 'VERIFIED';
                        $this->store->writeIndex('charge-group-' . $group, $record);
                    }
                });
            }
        } elseif ($action === 'link-order') {
            if (!preg_match('/^[1-9][0-9]*$/D', $value)
                || $session['status'] !== 'PAID' || !isset($session['catalog_verified']) || $session['catalog_verified'] !== true
                || Reconciliation::held($session) || ChargeGroups::blocked($this->store, $session)
                || isset($session['external_order_response'])
                || !isset($session['order_dispatch']['state'])
                || !in_array($session['order_dispatch']['state'], array('SENDING', 'UNKNOWN'), true)) {
                throw new GatewayException('No puede vincularse esta cotizacion al estado actual.');
            }
            $receipt = $session['verified_receipt'];
            if (!isset($receipt['req_reference_number'], $receipt['transaction_id'], $receipt['auth_code'],
                $receipt['req_amount'], $receipt['auth_amount'], $receipt['req_currency'], $session['provider_transaction_id'],
                $session['amount'], $session['currency'])
                || $receipt['req_reference_number'] !== $reference
                || $receipt['transaction_id'] !== $session['provider_transaction_id']
                || trim($receipt['auth_code']) !== $session['provider_authorization_number']
                || $session['currency'] !== 'GTQ' || $receipt['req_currency'] !== 'GTQ'
                || Money::cents($session['amount']) <= 0
                || Money::cents($session['amount']) !== Money::cents($receipt['req_amount'])
                || Money::cents($session['amount']) !== Money::cents($receipt['auth_amount'])) {
                throw new GatewayException('La evidencia financiera local esta incompleta o es contradictoria.');
            }
            $intent = isset($session['order_dispatch']['manual_link']) ? $session['order_dispatch']['manual_link'] : null;
            self::assertLinkIntent($intent, $value);
            if ($intent === null) {
                // Journal the chosen quotation before its independent uniqueness index.
                $session['order_dispatch']['manual_link'] = array('state' => 'RESERVED', 'idcotizacion' => $value,
                    'evidence' => $evidence, 'review' => $review, 'started_at' => gmdate('c'));
                $session = $this->store->save($reference, $session);
            }
            if (!$this->store->claimQuotation($reference, $value, $session['environment'])) {
                throw new GatewayException('La cotizacion ya pertenece a otra referencia.');
            }
            $session['external_order_response'] = array('success' => true, 'idcotizacion' => $value,
                'confirmation_source' => 'operator_verified', 'evidence' => $evidence);
            $session['order_dispatch']['previous_state'] = $session['order_dispatch']['state'];
            $session['order_dispatch']['state'] = 'CONFIRMED_MANUAL';
            $session['order_dispatch']['confirmed_at'] = gmdate('c');
            $session['order_dispatch']['needs_reconciliation'] = false;
            $session['order_dispatch']['operator_review'] = $review;
            $session['order_dispatch']['manual_link']['state'] = 'CONFIRMED';
            $session['order_dispatch']['manual_link']['confirmed_at'] = gmdate('c');
            $session['status'] = 'ORDER_SENT';
        }
        $session['operator_actions'][] = array('action' => $action, 'target' => $value,
            'evidence' => $evidence, 'review' => $review, 'at' => gmdate('c'));
        return $this->store->save($reference, $session);
    }
}
