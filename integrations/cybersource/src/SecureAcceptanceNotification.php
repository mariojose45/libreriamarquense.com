<?php

namespace LM\CyberSource;

class SecureAcceptanceNotification
{
    private $service;
    private $store;

    public function __construct(SecureAcceptanceService $service, PaymentSessionStore $store)
    {
        $this->service = $service;
        $this->store = $store;
    }

    public static function parseForm($raw, $contentType)
    {
        if (strtolower(trim(explode(';', $contentType)[0])) !== 'application/x-www-form-urlencoded'
            || !is_string($raw) || strlen($raw) > 131072 || $raw === '') {
            throw new GatewayException('Se requiere un POST de formulario de Secure Acceptance.');
        }
        $params = array();
        foreach (explode('&', $raw) as $pair) {
            $parts = explode('=', $pair, 2);
            $name = urldecode($parts[0]);
            if (!preg_match('/^[a-z][a-z0-9_]*$/D', $name) || array_key_exists($name, $params)) {
                throw new GatewayException('Formulario ambiguo o no valido.');
            }
            $params[$name] = urldecode(isset($parts[1]) ? $parts[1] : '');
        }
        return $params;
    }

    public function receive(array $params, $source)
    {
        $auth = $this->service->authenticateResponse($params);
        if ($auth === null) {
            throw new GatewayException('Firma de pago no valida.');
        }
        $fields = $auth['fields'];
        $reference = $this->service->extractReference($fields);
        $session = $this->store->find($reference);
        $validator = new SecureAcceptanceResponse($this->serviceConfig());
        if (!$session || !$validator->correlates($auth, $session)) {
            throw new GatewayException('No existe correlacion confiable con el comercio y referencia.');
        }
        $issues = $validator->issues($auth, $session);
        $safe = array();
        foreach (array('decision', 'reason_code', 'transaction_id', 'auth_amount', 'auth_code',
            'req_reference_number', 'req_amount', 'req_currency', 'req_profile_id',
            'req_transaction_uuid', 'req_transaction_type', 'signed_date_time') as $name) {
            if (isset($fields[$name])) {
                $safe[$name] = $fields[$name];
            }
        }
        // Persist the authenticated inbox BEFORE trying to acquire the session.
        // Merchant POST can acknowledge a durable event even while mail is sending.
        $record = $this->store->recordEvent($reference, array('authenticated_fields' => $safe,
            'credential_fingerprint' => $auth['credential_fingerprint'], 'issues' => $issues,
            'status' => $issues ? 'HELD' : $this->service->normalizeDecision($fields)));
        try {
            return $this->applyEvent($record['id']);
        } catch (PaymentStorageException $e) {
            $current = $this->store->find($reference);
            if (!$current) {
                throw $e;
            }
            $current['notification_queued'] = true;
            return $current;
        }
    }

    public function applyEvent($id)
    {
        $record = $this->store->findEvent($id);
        if (!$record) {
            throw new PaymentStorageException('Evento no encontrado.');
        }
        $reference = $record['reference'];
        return $this->store->withLock('session-' . $reference, function () use ($reference, $id) {
            $record = $this->store->findEvent($id);
            $session = $this->store->find($reference);
            if (!$session) {
                throw new PaymentStorageException('Sesion de evento no encontrada.');
            }
            if ($record['state'] === 'APPLIED' || isset($session['applied_events'][$id])) {
                if ($record['state'] !== 'APPLIED') {
                    $record['state'] = 'APPLIED';
                    $this->store->saveEvent($record);
                }
                return $session;
            }
            $event = $record['event'];
            $fields = $event['authenticated_fields'];
            $status = $event['status'];
            $transactionId = isset($fields['transaction_id']) ? $fields['transaction_id'] : '';
            $paid = !empty($session['payment_verified']) || in_array($session['status'], array('PAID', 'ORDER_SENT'), true);
            if ($status === 'HELD') {
                ChargeGroups::hold($this->store, $session, $transactionId);
                $session = Reconciliation::hold($session, $id, $event['issues'], $transactionId);
                if (!$paid) {
                    $session['status'] = 'REVIEW';
                }
            } elseif ($status === 'APPROVED') {
                $conflict = !empty($session['provider_transaction_id']) && $session['provider_transaction_id'] !== $transactionId;
                if ($conflict) {
                    $session['payment_conflicts'][$transactionId] = array('event_id' => $id, 'received_at' => gmdate('c'));
                    $session = Reconciliation::hold($session, $id, array('additional_charge_for_reference'), $transactionId);
                } else {
                    $ownerConflict = $this->store->withLock('transaction-' . $transactionId, function () use ($transactionId, $reference) {
                        $owner = $this->store->readIndex('transaction-' . $transactionId);
                        if ($owner && $owner['reference'] !== $reference) {
                            return true;
                        }
                        $this->store->writeIndex('transaction-' . $transactionId, array('reference' => $reference));
                        return false;
                    });
                    if ($ownerConflict) {
                        $session = Reconciliation::hold($session, $id, array('transaction_already_owned'), $transactionId);
                        if (!$paid) {
                            $session['status'] = 'REVIEW';
                        }
                    } else {
                        $groupConflict = ChargeGroups::reserve($this->store, $session, $transactionId);
                        $session['payment_verified'] = true;
                        $session['provider_transaction_id'] = $transactionId;
                        $session['provider_authorization_number'] = trim($fields['auth_code']);
                        if ($session['status'] !== 'ORDER_SENT') {
                            $session['status'] = 'PAID';
                        }
                        $session['verified_receipt'] = $fields;
                        if ($groupConflict) {
                            $session = Reconciliation::hold($session, $id, array('additional_charge_in_retry_group'), $transactionId);
                        }
                        // A later complete ACCEPT resolves a preceding provider REVIEW,
                        // but never silently clears financial mismatches or conflicts.
                        foreach (isset($session['reconciliation']) ? $session['reconciliation'] : array() as $key => $issue) {
                            if ($issue['reasons'] === array('provider_review')) {
                                $session['reconciliation'][$key]['state'] = 'RESOLVED_BY_ACCEPT';
                            }
                        }
                    }
                }
            } elseif (!$paid && !in_array($session['status'], array('DECLINED', 'CANCELLED'), true)) {
                $session['status'] = $status;
                $session['verified_receipt'] = $fields;
                if ($status === 'REVIEW') {
                    ChargeGroups::hold($this->store, $session, $transactionId);
                    $session = Reconciliation::hold($session, $id, array('provider_review'), $transactionId);
                }
            }
            $session['applied_events'][$id] = gmdate('c');
            $session['confirmed_at'] = gmdate('c');
            $session = $this->store->save($reference, $session);
            $record['state'] = 'APPLIED';
            $record['applied_at'] = gmdate('c');
            $this->store->saveEvent($record);
            return $session;
        });
    }

    private function serviceConfig()
    {
        return $this->service->config();
    }
}
