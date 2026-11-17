<?php

namespace LM\CyberSource;

class Reconciliation
{
    public static function held(array $session)
    {
        if (!empty($session['payment_conflicts']) || !empty($session['fulfillment_hold'])) {
            return true;
        }
        foreach (isset($session['reconciliation']) ? $session['reconciliation'] : array() as $issue) {
            if ($issue['state'] === 'OPEN') {
                return true;
            }
        }
        return false;
    }

    public static function hold(array $session, $id, array $reasons, $transactionId = '')
    {
        $session['reconciliation'][$id] = array('state' => 'OPEN', 'reasons' => $reasons,
            'transaction_id' => $transactionId, 'recorded_at' => gmdate('c'));
        return $session;
    }
}
