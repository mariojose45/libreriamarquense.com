<?php

namespace LM\CyberSource;

class SecureAcceptanceResponse
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public static function fingerprint($profile, $access, $secret)
    {
        return hash_hmac('sha256', 'credential-id' . "\0" . $profile . "\0" . $access, $secret);
    }

    public function authenticate(array $params)
    {
        if (!$this->config->hasSecureAcceptance() || !isset($params['signature'], $params['signed_field_names'])
            || !is_string($params['signature']) || !is_string($params['signed_field_names'])
            || strlen($params['signed_field_names']) > 16000) {
            return null;
        }
        $names = explode(',', $params['signed_field_names']);
        if (!in_array('signed_field_names', $names, true) || count($names) !== count(array_unique($names))) {
            return null;
        }
        $data = array();
        $fields = array();
        foreach ($names as $name) {
            if (!preg_match('/^[a-z][a-z0-9_]*$/D', $name) || $name === 'signature'
                || (array_key_exists($name, $params) && !is_scalar($params[$name]))) {
                return null;
            }
            $value = isset($params[$name]) ? (string) $params[$name] : '';
            $data[] = $name . '=' . $value;
            if (array_key_exists($name, $params)) {
                $fields[$name] = $value;
            }
        }
        $keys = array(array('profile_id' => $this->config->get('secure_acceptance.profile_id'),
            'access_key' => $this->config->get('secure_acceptance.access_key'),
            'secret_key' => $this->config->get('secure_acceptance.secret_key')));
        foreach ((array) $this->config->get('secure_acceptance.verification_keys', array()) as $key) {
            if (is_array($key)) {
                $keys[] = $key;
            }
        }
        foreach ($keys as $key) {
            if (empty($key['profile_id']) || empty($key['access_key']) || empty($key['secret_key'])) {
                continue;
            }
            $expected = base64_encode(hash_hmac('sha256', implode(',', $data), (string) $key['secret_key'], true));
            if (hash_equals($expected, $params['signature'])) {
                return array('fields' => $fields, 'credential_fingerprint' => self::fingerprint($key['profile_id'], $key['access_key'], $key['secret_key']),
                    'credential_profile' => (string) $key['profile_id'],
                    'access_key_matches' => isset($fields['req_access_key']) && hash_equals((string) $key['access_key'], $fields['req_access_key']));
            }
        }
        return null;
    }

    public function correlates(array $auth, array $session)
    {
        $fields = $auth['fields'];
        $profile = isset($session['secure_acceptance_request']['profile_id'])
            ? $session['secure_acceptance_request']['profile_id'] : $this->config->get('secure_acceptance.profile_id');
        return isset($fields['req_reference_number'], $session['reference'])
            && hash_equals($session['reference'], $fields['req_reference_number'])
            && hash_equals((string) $profile, $auth['credential_profile']);
    }

    public function issues(array $auth, array $session)
    {
        $fields = $auth['fields'];
        $issues = array();
        $required = array('signed_date_time', 'decision', 'reason_code', 'req_reference_number',
            'req_amount', 'req_currency', 'req_profile_id', 'req_access_key', 'req_transaction_uuid', 'req_transaction_type');
        if (isset($fields['decision']) && $fields['decision'] === 'ACCEPT') {
            $required = array_merge($required, array('transaction_id', 'auth_amount', 'auth_code'));
        }
        foreach ($required as $name) {
            if (!isset($fields[$name]) || trim($fields[$name]) === '') {
                $issues[] = 'missing_or_unsigned_' . $name;
            }
        }
        $attempt = isset($session['secure_acceptance_request']) ? $session['secure_acceptance_request'] : array();
        foreach (array('transaction_uuid', 'profile_id', 'transaction_type', 'signed_date_time', 'credential_fingerprint', 'environment') as $name) {
            if (empty($attempt[$name])) {
                $issues[] = 'legacy_missing_' . $name;
            }
        }
        if (isset($attempt['environment']) && $attempt['environment'] !== $this->config->get('environment', 'test')) {
            $issues[] = 'attempt_environment_mismatch';
        }
        foreach (array('req_reference_number' => isset($session['reference']) ? $session['reference'] : '',
            'req_profile_id' => isset($attempt['profile_id']) ? $attempt['profile_id'] : '',
            'req_transaction_uuid' => isset($attempt['transaction_uuid']) ? $attempt['transaction_uuid'] : '',
            'req_transaction_type' => isset($attempt['transaction_type']) ? $attempt['transaction_type'] : '',
            'req_currency' => 'GTQ') as $key => $expected) {
            if (isset($fields[$key]) && !hash_equals((string) $expected, $fields[$key])) {
                $issues[] = 'mismatch_' . $key;
            }
        }
        if (!$auth['access_key_matches'] || (isset($attempt['credential_fingerprint'])
            && !hash_equals($attempt['credential_fingerprint'], $auth['credential_fingerprint']))) {
            $issues[] = 'attempt_credentials_mismatch';
        }
        $decision = isset($fields['decision']) ? $fields['decision'] : '';
        $reason = isset($fields['reason_code']) ? $fields['reason_code'] : '';
        if (!in_array($decision, array('ACCEPT', 'DECLINE', 'CANCEL', 'REVIEW', 'ERROR'), true)) {
            $issues[] = 'unsupported_decision';
        }
        if (!preg_match('/^[0-9]{3}$/D', $reason) || ($decision !== 'ACCEPT' && $reason === '100')
            || ($decision === 'ACCEPT' && $reason !== '100')) {
            $issues[] = $reason === '110' ? 'partial_authorization' : 'decision_reason_inconsistent';
        }
        $date = isset($fields['signed_date_time']) ? $fields['signed_date_time'] : '';
        $time = strtotime($date);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $date) || $time === false
            || gmdate('Y-m-d\TH:i:s\Z', $time) !== $date || $time > time() + 300
            || (!empty($attempt['signed_date_time']) && $time < strtotime($attempt['signed_date_time']) - 300)) {
            $issues[] = 'invalid_response_time';
        }
        try {
            $expected = isset($session['amount']) ? Money::cents($session['amount']) : 0;
            if ($expected <= 0 || !isset($fields['req_amount']) || Money::cents($fields['req_amount']) !== $expected
                || !isset($session['currency']) || $session['currency'] !== 'GTQ') {
                $issues[] = 'requested_amount_or_currency_mismatch';
            }
            if ($decision === 'ACCEPT' && (!isset($fields['auth_amount']) || Money::cents($fields['auth_amount']) !== $expected)) {
                $issues[] = 'authorized_amount_mismatch';
            }
            if (in_array($decision, array('DECLINE', 'CANCEL'), true)
                && ((isset($fields['auth_amount']) && trim($fields['auth_amount']) !== '' && Money::cents($fields['auth_amount']) > 0)
                    || isset($fields['auth_code']) && trim($fields['auth_code']) !== '')) {
                $issues[] = 'negative_decision_with_authorization_evidence';
            }
        } catch (GatewayException $e) {
            $issues[] = 'invalid_money_format';
        }
        if ($decision === 'ACCEPT') {
            if (!isset($fields['req_transaction_type']) || $fields['req_transaction_type'] !== 'sale') {
                $issues[] = 'authorization_requires_capture_before_fulfillment';
            }
            if (empty($fields['transaction_id']) || !preg_match('/^[A-Za-z0-9_-]{1,80}$/D', $fields['transaction_id'])) {
                $issues[] = 'invalid_transaction_id';
            }
            if (!isset($fields['auth_code']) || trim($fields['auth_code']) === ''
                || !preg_match('/^[A-Za-z0-9 -]{1,32}$/D', $fields['auth_code'])) {
                $issues[] = 'invalid_authorization_code';
            }
        }
        foreach (array('reference_number' => 'req_reference_number', 'currency' => 'req_currency',
            'profile_id' => 'req_profile_id', 'transaction_uuid' => 'req_transaction_uuid',
            'transaction_type' => 'req_transaction_type', 'amount' => 'req_amount') as $alias => $canonical) {
            if (isset($fields[$alias]) && (!isset($fields[$canonical]) || $fields[$alias] !== $fields[$canonical])) {
                $issues[] = 'contradictory_' . $alias;
            }
        }
        return array_values(array_unique($issues));
    }
}
