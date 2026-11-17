<?php

namespace LM\CyberSource;

class ChargeGroups
{
    public static function reserve(PaymentSessionStore $store, array $session, $transactionId)
    {
        $group = isset($session['charge_group']) ? $session['charge_group'] : hash('sha256', $session['reference']);
        return $store->withLock('charge-group-' . $group, function () use ($store, $session, $transactionId, $group) {
            $record = $store->readIndex('charge-group-' . $group) ?: array('state' => 'VERIFIED', 'references' => array());
            $record['references'][$session['reference']] = $transactionId;
            if (count($record['references']) > 1) {
                $record['state'] = 'CONFLICT';
            } elseif ($record['state'] === 'REVIEW') {
                $onlyProviderReview = true;
                foreach (isset($session['reconciliation']) ? $session['reconciliation'] : array() as $issue) {
                    if ($issue['state'] === 'OPEN' && $issue['reasons'] !== array('provider_review')) {
                        $onlyProviderReview = false;
                    }
                }
                if ($onlyProviderReview && empty($session['payment_conflicts'])) {
                    $record['state'] = 'VERIFIED';
                }
            }
            $store->writeIndex('charge-group-' . $group, $record);
            return $record['state'] === 'CONFLICT';
        });
    }

    public static function conflicted(PaymentSessionStore $store, array $session)
    {
        if (empty($session['charge_group'])) {
            return false;
        }
        $record = $store->readIndex('charge-group-' . $session['charge_group']);
        return $record && $record['state'] === 'CONFLICT';
    }

    public static function hold(PaymentSessionStore $store, array $session, $transactionId)
    {
        if (empty($session['charge_group'])) {
            return;
        }
        $group = $session['charge_group'];
        $store->withLock('charge-group-' . $group, function () use ($store, $session, $transactionId, $group) {
            $record = $store->readIndex('charge-group-' . $group) ?: array('state' => 'REVIEW', 'references' => array());
            if ($record['state'] !== 'CONFLICT') {
                $record['state'] = 'REVIEW';
            }
            if (!isset($record['references'][$session['reference']])) {
                $record['references'][$session['reference']] = $transactionId;
            }
            $store->writeIndex('charge-group-' . $group, $record);
        });
    }

    public static function blocked(PaymentSessionStore $store, array $session)
    {
        $record = empty($session['charge_group']) ? null : $store->readIndex('charge-group-' . $session['charge_group']);
        return $record && $record['state'] !== 'VERIFIED';
    }

    public static function chargedElsewhere(PaymentSessionStore $store, array $session)
    {
        $record = empty($session['charge_group']) ? null : $store->readIndex('charge-group-' . $session['charge_group']);
        if (!$record) {
            return false;
        }
        foreach ($record['references'] as $reference => $transactionId) {
            if ($reference !== $session['reference']) {
                return true;
            }
        }
        return false;
    }
}
