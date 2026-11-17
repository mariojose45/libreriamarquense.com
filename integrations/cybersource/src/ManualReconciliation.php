<?php

namespace LM\CyberSource;

class ManualReconciliation
{
    private $store;

    public function __construct(PaymentSessionStore $store)
    {
        $this->store = $store;
    }

    public function apply($reference, $action, $value, $evidence)
    {
        if (!is_string($evidence) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._:\/-]{7,160}$/D', $evidence)) {
            throw new GatewayException('Se requiere una referencia verificable de evidencia, sin datos secretos.');
        }
        return $this->store->withLock('fulfill-' . $reference, function () use ($reference, $action, $value, $evidence) {
            return $this->store->withLock('session-' . $reference, function () use ($reference, $action, $value, $evidence) {
                $session = $this->store->find($reference);
                if (!$session || empty($session['payment_verified']) || empty($session['verified_receipt'])
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
                    if (!preg_match('/^[1-9][0-9]*$/D', (string) $value) || Reconciliation::held($session)
                        || isset($session['external_order_response'])
                        || !isset($session['order_dispatch']['state'])
                        || !in_array($session['order_dispatch']['state'], array('SENDING', 'UNKNOWN'), true)) {
                        throw new GatewayException('No puede vincularse esta cotizacion al estado actual.');
                    }
                    $session['external_order_response'] = array('success' => true, 'idcotizacion' => (string) $value,
                        'confirmation_source' => 'operator_verified', 'evidence' => $evidence);
                    if (!$this->store->claimQuotation($reference, $value, $session['environment'])) {
                        throw new GatewayException('La cotizacion ya pertenece a otra referencia.');
                    }
                    $session['order_dispatch']['state'] = 'CONFIRMED_MANUAL';
                    $session['status'] = 'ORDER_SENT';
                } else {
                    throw new GatewayException('Operacion manual no permitida.');
                }
                $session['operator_actions'][] = array('action' => $action, 'target' => $value,
                    'evidence' => $evidence, 'at' => gmdate('c'));
                return $this->store->save($reference, $session);
            });
        }, 0);
    }
}
