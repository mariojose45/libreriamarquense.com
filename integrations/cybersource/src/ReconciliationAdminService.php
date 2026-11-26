<?php

namespace LM\CyberSource;

final class ReconciliationAdminService
{
    private $config;
    private $settings;
    private $store;
    private $review;
    private $audit;
    private $auth;

    public function __construct(Config $config, array $settings, PaymentSessionStore $store,
        ManualReconciliation $review, ReconciliationAdminStore $audit, ReconciliationAdminAuth $auth)
    {
        if ($config->get('environment') !== 'production' || SideEffectPolicy::isolated($config)
            || getenv('LM_PAYMENT_ISOLATED_TEST') === '1') {
            throw new \RuntimeException('El panel no admite simuladores ni ambientes diferentes de produccion.');
        }
        $this->config = $config;
        $this->settings = $settings;
        $this->store = $store;
        $this->review = $review;
        $this->audit = $audit;
        $this->auth = $auth;
    }

    public function canLink()
    {
        $this->auth->requireUser();
        return isset($this->settings['allow_manual_links']) && $this->settings['allow_manual_links'] === true
            && $this->config->get('execution.production_authorized', false) === true
            && function_exists('putenv');
    }

    public function pending($cursor = '', $mode = 'pending')
    {
        $username = $this->auth->requireUser();
        if (!in_array($mode, array('pending', 'incidents'), true)) {
            throw new \RuntimeException('Consulta no valida.');
        }
        if ($cursor !== '' && !preg_match('/^LMQ-[0-9]{14}-[A-F0-9]{8}$/D', $cursor)) {
            throw new \RuntimeException('Cursor no valido.');
        }
        $this->audit->audit($mode === 'incidents' ? 'list_incidents' : 'list_pending', $username);
        $items = array();
        $unreadable = array();
        $scanned = 0;
        $next = '';
        foreach ($this->store->references() as $reference) {
            if ($cursor !== '' && strcmp($reference, $cursor) <= 0) {
                continue;
            }
            $scanned++;
            $this->audit->metrics()->count('records_scanned');
            try {
                $summary = $this->pendingSummary($reference, $mode);
                if ($summary !== null) {
                    $items[] = $summary;
                    $this->audit->metrics()->count('pending_found');
                }
            } catch (\Throwable $e) {
                $unreadable[] = $reference;
                $this->audit->metrics()->count('records_unreadable');
            }
            if (count($items) >= 25 || $scanned >= 500) {
                $next = $reference;
                break;
            }
        }
        return array('items' => $items, 'next' => $next, 'unreadable' => $unreadable);
    }

    private function pendingSummary($reference, $mode)
    {
        $session = $this->store->find($reference);
        if (!$session || !isset($session['status'])) { throw new GatewayException('Registro no disponible.'); }
        $amount = isset($session['amount']) ? Money::decimal(Money::cents($session['amount'])) : null;
        $response = isset($session['external_order_response']) ? $session['external_order_response'] : array();
        $confirmed = is_array($response) && OrderForwarder::isConfirmed($response);
        // Same selection as ManualReconciliation::describe; no event/index scans are
        // needed for these six display fields. Detail and all writes still use ManualReconciliation.
        if ($mode === 'pending' && (!isset($session['payment_verified']) || $session['payment_verified'] !== true
            || ($confirmed && $session['status'] === 'ORDER_SENT'))) { return null; }
        $summary = array('reference' => $reference, 'status' => $session['status'], 'amount' => $amount,
            'currency' => isset($session['currency']) ? $session['currency'] : null,
            'dispatch' => isset($session['order_dispatch']['state']) ? $session['order_dispatch']['state'] : 'NOT_STARTED',
            'created_at' => isset($session['created_at']) ? $session['created_at'] : null);
        if ($mode === 'incidents') {
            $reasons = array();
            if (Reconciliation::held($session) || $session['status'] === 'REVIEW') { $reasons[] = 'PAYMENT_REVIEW'; }
            if (in_array($summary['dispatch'], array('SENDING', 'UNKNOWN', 'CONFIRMED_HELD'), true)) {
                $reasons[] = 'ERP_' . $summary['dispatch'];
            }
            if (in_array($session['status'], array('PAID', 'ORDER_SENT'), true)) {
                if (!isset($session['payment_verified']) || $session['payment_verified'] !== true
                    || !isset($session['catalog_verified']) || $session['catalog_verified'] !== true) {
                    $reasons[] = 'INCOMPLETE_VERIFICATION';
                }
                if (!$confirmed || $session['status'] !== 'ORDER_SENT') { $reasons[] = 'ORDER_UNCONFIRMED'; }
            }
            $email = isset($session['order_email']['state']) ? $session['order_email']['state'] : 'NOT_STARTED';
            if (in_array($email, array('SENDING', 'UNKNOWN', 'FAILED'), true)) { $reasons[] = 'EMAIL_' . $email; }
            if (in_array($session['status'], array('PENDING', 'SUBMITTED'), true) && !empty($session['expires_at'])
                && strtotime($session['expires_at']) !== false && strtotime($session['expires_at']) <= time()) {
                $reasons[] = 'ATTEMPT_EXPIRED';
            }
            if (!$reasons) { return null; }
            $summary['reasons'] = $reasons;
            $summary['environment'] = isset($session['environment']) ? $session['environment'] : 'UNVERIFIED';
        }
        return $summary;
    }

    public function detail($reference)
    {
        $username = $this->auth->requireUser();
        $this->audit->audit('view_reference', $username, array('reference' => $reference));
        $detail = $this->review->describe($reference, true);
        // Never render raw payment sessions, card data, authorization codes or credentials.
        $safe = array_intersect_key($detail, array_flip(array('reference', 'revision', 'environment', 'status',
            'amount', 'currency', 'created_at', 'dispatch', 'quotation_id', 'payment_verified',
            'financial_review', 'charge_group_blocked', 'pending_notifications', 'email_state', 'customer', 'items')));
        $safe['review_reasons'] = array();
        foreach (isset($detail['issues']) ? $detail['issues'] : array() as $issue) {
            if (!isset($issue['state']) || $issue['state'] !== 'OPEN') { continue; }
            foreach (isset($issue['reasons']) ? $issue['reasons'] : array() as $reason) {
                if (is_string($reason) && preg_match('/^[a-z][a-z0-9_]{0,79}$/D', $reason)) {
                    $safe['review_reasons'][] = $reason;
                }
            }
        }
        $safe['review_reasons'] = array_values(array_unique($safe['review_reasons']));
        return $safe;
    }

    public function link($csrf, $token, $quotation, $evidence, array $confirmations)
    {
        $username = $this->auth->requireUser();
        $this->auth->requireCsrf($csrf);
        if (!$this->canLink()) {
            $this->audit->audit('link_blocked', $username);
            throw new \RuntimeException('Vinculaciones bloqueadas por la configuracion del servidor.');
        }
        if (!is_string($quotation) || !preg_match('/^[1-9][0-9]{0,19}$/D', $quotation)
            || !is_string($evidence) || !preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._:\/-]{7,160}$/D', $evidence)
            || !isset($confirmations['authorize_link']) || $confirmations['authorize_link'] !== true) {
            throw new \RuntimeException('Indique cotizacion verificada, evidencia y autorizacion explicita.');
        }
        $ticket = $this->auth->consumeReview($token);
        $reference = $ticket['reference'];
        $operatorReview = array_merge($confirmations, array('operator' => $username,
            'expected_revision' => $ticket['expected_revision']));
        ManualReconciliation::validateReview($operatorReview);
        $operation = bin2hex(random_bytes(16));
        $context = array('reference' => $reference, 'quotation' => $quotation,
            'revision' => $ticket['expected_revision'], 'operation_id' => $operation, 'evidence_sha256' => hash('sha256', $evidence));
        $this->audit->audit('link_intent', $username, $context);
        $previous = getenv('LM_PAYMENT_PRODUCTION_CONFIRMED');
        try {
            // A scoped, authenticated web confirmation replaces the CLI operator action.
            // ManualReconciliation still enforces configuration + environment + explicit args.
            if (!putenv('LM_PAYMENT_PRODUCTION_CONFIRMED=1')) {
                throw new \RuntimeException('El hosting no permite confirmar el contexto administrativo.');
            }
            $this->review->apply($reference, 'link-order', $quotation, $evidence,
                $operatorReview, array('--allow-production'));
            $this->audit->audit('link_result', $username, array_merge($context, array('outcome' => 'confirmed')));
        } catch (\Throwable $e) {
            $this->audit->audit('link_result', $username, array_merge($context, array('outcome' => 'not_confirmed')));
            throw new \RuntimeException('Resultado no confirmado: vuelva a consultar la referencia antes de repetir. No cree ni reenvie pedidos.');
        } finally {
            putenv($previous === false ? 'LM_PAYMENT_PRODUCTION_CONFIRMED' : 'LM_PAYMENT_PRODUCTION_CONFIRMED=' . $previous);
        }
        return $reference;
    }
}
