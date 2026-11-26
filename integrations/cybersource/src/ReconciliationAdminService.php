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
        $safe['historical'] = !isset($safe['revision'], $safe['environment'])
            || !is_int($safe['revision']) || $safe['revision'] < 1
            || !in_array($safe['environment'], array('production', 'prod', 'test'), true);
        $safe['events'] = array();
        foreach ($this->store->eventIds($reference) as $id) {
            $record = $this->store->findEvent($id);
            if (!is_array($record) || !isset($record['reference'], $record['event'])
                || $record['reference'] !== $reference || !is_array($record['event'])) {
                throw new PaymentStorageException('Evento no disponible para consulta.');
            }
            $event = $record['event'];
            $fields = isset($event['authenticated_fields']) && is_array($event['authenticated_fields'])
                ? $event['authenticated_fields'] : array();
            $reasons = array();
            foreach (isset($event['issues']) && is_array($event['issues']) ? $event['issues'] : array() as $reason) {
                if (is_string($reason) && preg_match('/^[a-z][a-z0-9_]{0,79}$/D', $reason)) { $reasons[] = $reason; }
            }
            // Display only status metadata, never raw signed fields or provider credentials.
            $safe['events'][] = array(
                'received_at' => self::eventDate(isset($record['received_at']) ? $record['received_at'] : null),
                'applied_at' => self::eventDate(isset($record['applied_at']) ? $record['applied_at'] : null),
                'state' => isset($record['state']) && in_array($record['state'], array('RECEIVED', 'APPLIED'), true) ? $record['state'] : 'UNVERIFIED',
                'result' => isset($event['status']) && in_array($event['status'], array('APPROVED', 'DECLINED', 'CANCELLED', 'REVIEW', 'PENDING', 'HELD'), true)
                    ? $event['status'] : 'UNVERIFIED',
                'decision' => isset($fields['decision']) && in_array($fields['decision'], array('ACCEPT', 'DECLINE', 'CANCEL', 'REVIEW', 'ERROR'), true)
                    ? $fields['decision'] : 'UNVERIFIED',
                'reason_code' => isset($fields['reason_code']) && is_string($fields['reason_code']) && preg_match('/^[0-9]{3}$/D', $fields['reason_code'])
                    ? $fields['reason_code'] : 'UNVERIFIED',
                'reasons' => array_values(array_unique($reasons)),
            );
        }
        usort($safe['events'], function ($a, $b) { return strcmp((string) $b['received_at'], (string) $a['received_at']); });
        $safe['events_truncated'] = count($safe['events']) > 50;
        $safe['events'] = array_slice($safe['events'], 0, 50);
        return $safe;
    }

    private static function eventDate($value)
    {
        if (!is_string($value) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}(?:Z|[+-][0-9]{2}:[0-9]{2})$/D', $value)) {
            return null;
        }
        $time = strtotime($value);
        return $time === false ? null : gmdate('c', $time);
    }

    public function cronPreview($maxRecords = 5000)
    {
        $username = $this->auth->requireUser();
        if (!is_int($maxRecords) || $maxRecords < 1 || $maxRecords > 5000) {
            throw new \RuntimeException('Limite de consulta no valido.');
        }
        $this->audit->audit('review_cron_queue', $username);
        $report = array('checked_at' => gmdate('c'), 'complete' => true, 'records' => 0, 'events' => 0,
            'order_candidates' => 0, 'mail_candidates' => 0, 'pending_events' => 0, 'errors' => 0,
            'production_authorized' => $this->config->get('execution.production_authorized', false) === true,
            'authorization_is_boolean' => is_bool($this->config->get('execution.production_authorized', false)),
            'buckets' => array(), 'statuses' => array(), 'rows' => array(), 'rows_truncated' => false);
        $deadline = hrtime(true) + 8000000000;
        $budget = function () use (&$report, $deadline, $maxRecords) {
            if ($report['records'] + $report['events'] >= $maxRecords || hrtime(true) >= $deadline) {
                $report['complete'] = false;
                return false;
            }
            return true;
        };
        $addRow = function ($reference, $status, $reason, array $session = array()) use (&$report) {
            if (count($report['rows']) >= 200) { $report['rows_truncated'] = true; return; }
            $amount = null;
            try { if (isset($session['amount'])) { $amount = Money::decimal(Money::cents($session['amount'])); } }
            catch (\Throwable $e) { /* Invalid display data never suppresses a dispatch candidate. */ }
            $date = isset($session['created_at']) ? self::eventDate($session['created_at']) : null;
            $report['rows'][] = array('reference' => $reference, 'status' => $status, 'reason' => $reason,
                'created_at' => $date, 'amount' => $amount,
                'currency' => isset($session['currency']) && is_string($session['currency'])
                    && preg_match('/^[A-Z]{3}$/D', $session['currency']) ? $session['currency'] : null);
        };
        foreach ($this->store->eventIds() as $id) {
            if (!$budget()) { break; }
            $report['events']++;
            $reference = preg_match('/^(LMQ-[0-9]{14}-[A-F0-9]{8})-[a-f0-9]{64}$/D', $id, $match) ? $match[1] : null;
            try {
                $record = $this->store->findEvent($id);
                if (!is_array($record) || !isset($record['state'])) { throw new PaymentStorageException('Evento no disponible.'); }
                if ($record['state'] !== 'APPLIED') {
                    $report['pending_events']++;
                    $addRow($reference, 'PENDING_EVENT', 'notification_can_change_eligibility');
                }
            } catch (\Throwable $e) {
                $report['complete'] = false;
                $report['errors']++;
                $addRow($reference, 'UNREADABLE', 'event_unreadable');
            }
        }
        foreach ($this->store->references() as $reference) {
            if (!$budget()) { break; }
            $report['records']++;
            try {
                $session = $this->store->find($reference);
                if (!is_array($session) || !isset($session['status'])) { throw new PaymentStorageException('Registro no disponible.'); }
                $status = in_array($session['status'], array('PAID', 'ORDER_SENT', 'PENDING', 'SUBMITTED', 'REVIEW', 'DECLINED', 'CANCELLED', 'EXPIRED'), true)
                    ? $session['status'] : 'OTHER';
                $report['statuses'][$status] = isset($report['statuses'][$status]) ? $report['statuses'][$status] + 1 : 1;
                $result = $this->cronEligibility($session);
                $bucket = $result['bucket'];
                $report['buckets'][$bucket] = isset($report['buckets'][$bucket]) ? $report['buckets'][$bucket] + 1 : 1;
                if ($result['order']) {
                    $report['order_candidates']++;
                    $addRow($reference, $status, 'order_send_candidate', $session);
                }
                if ($result['mail']) {
                    $report['mail_candidates']++;
                    $addRow($reference, $status, 'mail_send_candidate', $session);
                }
                // The worker uses empty(), not strict boolean comparisons. Do not hide these records.
                if (($result['order'] || $result['mail']) && ($session['payment_verified'] !== true || $session['catalog_verified'] !== true)) {
                    $addRow($reference, $status, 'non_boolean_verification_flags', $session);
                }
            } catch (\Throwable $e) {
                $report['complete'] = false;
                $report['errors']++;
                $addRow($reference, 'UNREADABLE', 'record_unreadable');
            }
        }
        if (hrtime(true) >= $deadline) { $report['complete'] = false; }
        $report['no_automatic_sends_observed'] = $report['complete'] && $report['errors'] === 0
            && $report['order_candidates'] === 0 && $report['mail_candidates'] === 0 && $report['pending_events'] === 0;
        return $report;
    }

    private function cronEligibility(array $session)
    {
        $blocked = function ($bucket) { return array('bucket' => $bucket, 'order' => false, 'mail' => false); };
        if (empty($session['payment_verified']) || !in_array($session['status'], array('PAID', 'ORDER_SENT'), true)) {
            return $blocked('not_selected');
        }
        if (!isset($session['environment'], $session['isolated_test']) || $session['environment'] !== $this->config->get('environment', 'test')
            || $session['isolated_test'] !== SideEffectPolicy::isolated($this->config)) { return $blocked('environment_blocked'); }
        if (empty($session['catalog_verified'])) { return $blocked('catalog_blocked'); }
        if (Reconciliation::held($session) || ChargeGroups::blocked($this->store, $session)) { return $blocked('financial_hold'); }
        if ($this->store->hasPendingEvents($session['reference'])) { return $blocked('pending_notification'); }
        if (isset($session['external_order_response'])) {
            $response = $session['external_order_response'];
            $confirmed = is_array($response) && OrderForwarder::isConfirmed($response);
            $mayFinalize = $session['status'] === 'PAID' && isset($session['order_dispatch']['state'])
                && $session['order_dispatch']['state'] === 'CONFIRMED_HELD';
            // A retained confirmation may release locally and attempt mail, but never send the order again.
            return array('bucket' => 'response_present', 'order' => false,
                'mail' => $confirmed && ($session['status'] === 'ORDER_SENT' || $mayFinalize) && !isset($session['order_email']));
        }
        if (!empty($session['order_dispatch'])) { return $blocked('dispatch_present'); }
        if (empty($session['provider_authorization_number']) || !is_scalar($session['provider_authorization_number'])
            || trim((string) $session['provider_authorization_number']) === '') { return $blocked('authorization_missing'); }
        // Potential claim after authorization. No policy override, processor, transport or write is invoked.
        return array('bucket' => 'order_candidate', 'order' => true, 'mail' => false);
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
