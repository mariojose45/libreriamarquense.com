<?php

namespace LM\CyberSource;

class SecureAcceptanceService
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function buildSignedFormFields(array $session)
    {
        if (!$this->config->hasSecureAcceptance()) {
            throw new GatewayException('Falta configurar Secure Acceptance de CyberSource.');
        }

        $order = isset($session['order_payload']) && is_array($session['order_payload'])
            ? $session['order_payload']
            : array();

        $siteBaseUrl = rtrim((string) $this->config->get('site.base_url'), '/');
        $returnUrl = $siteBaseUrl . $this->config->get('site.return_path');
        $cancelUrl = $siteBaseUrl . $this->config->get('site.cancel_path');
        $transactionType = trim((string) $this->config->get('secure_acceptance.transaction_type', ''));
        if ($transactionType === '') {
            $transactionType = (string) $this->config->get('operation', 'authorization');
        }

        $customer = $this->customerData($order);

        $fields = array(
            'access_key' => (string) $this->config->get('secure_acceptance.access_key'),
            'profile_id' => (string) $this->config->get('secure_acceptance.profile_id'),
            'transaction_uuid' => $session['reference'] . '-' . bin2hex(random_bytes(8)),
            'signed_field_names' => '',
            'unsigned_field_names' => '',
            'signed_date_time' => gmdate('Y-m-d\TH:i:s\Z'),
            'locale' => (string) $this->config->get('secure_acceptance.locale', 'es'),
            'transaction_type' => strtolower($transactionType),
            'reference_number' => $session['reference'],
            'amount' => number_format((float) $session['amount'], 2, '.', ''),
            'currency' => $session['currency'],
            'bill_to_forename' => $customer['first_name'],
            'bill_to_surname' => $customer['last_name'],
            'bill_to_email' => $customer['email'],
            'bill_to_phone' => $customer['phone'],
            'bill_to_address_line1' => $customer['address'],
            'bill_to_address_city' => $customer['city'],
            'bill_to_address_state' => $customer['state'],
            'bill_to_address_country' => $customer['country'],
            'bill_to_address_postal_code' => $customer['postal_code'],
            'override_custom_receipt_page' => $returnUrl,
            'override_custom_cancel_page' => $cancelUrl,
        );

        $deviceFingerprintId = $this->deviceFingerprintId($session);
        if ($deviceFingerprintId !== '') {
            $fields['device_fingerprint_id'] = $deviceFingerprintId;
        }

        $fields = $this->removeEmptyOptionalFields($fields);
        $fields['signed_field_names'] = implode(',', array_keys($fields));
        $fields['signature'] = $this->sign($fields);

        return $fields;
    }

    public function validateResponse(array $params)
    {
        if (!$this->config->hasSecureAcceptance()) {
            return false;
        }

        if (!isset($params['signature'], $params['signed_field_names'])) {
            return false;
        }

        $expected = $this->sign($params);
        return hash_equals($expected, (string) $params['signature']);
    }

    public function normalizeDecision(array $params)
    {
        $decision = strtoupper(trim((string) $this->value($params, 'decision', '')));
        $reasonCode = trim((string) $this->value($params, 'reason_code', ''));

        if ($decision === 'ACCEPT' || $reasonCode === '100') {
            return 'APPROVED';
        }

        if (in_array($decision, array('DECLINE', 'REJECT', 'ERROR'), true)) {
            return 'DECLINED';
        }

        if (in_array($decision, array('CANCEL', 'CANCELLED', 'CANCELED'), true)) {
            return 'CANCELLED';
        }

        if ($reasonCode !== '' && $reasonCode !== '100') {
            return 'DECLINED';
        }

        return 'PENDING';
    }

    public function extractReference(array $params)
    {
        foreach (array('req_reference_number', 'reference_number', 'ref', 'reference') as $key) {
            if (isset($params[$key]) && trim((string) $params[$key]) !== '') {
                return trim((string) $params[$key]);
            }
        }

        return '';
    }

    public function extractTransactionId(array $params)
    {
        foreach (array('transaction_id', 'request_token', 'id') as $key) {
            if (isset($params[$key]) && trim((string) $params[$key]) !== '') {
                return trim((string) $params[$key]);
            }
        }

        return '';
    }

    public function extractAuthorizationNumber(array $params)
    {
        foreach (array('auth_trans_ref_no', 'auth_code', 'authorization_code', 'transaction_id', 'request_token', 'id') as $key) {
            if (isset($params[$key]) && trim((string) $params[$key]) !== '') {
                return trim((string) $params[$key]);
            }
        }

        return '';
    }

    private function sign(array $fields)
    {
        $secret = (string) $this->config->get('secure_acceptance.secret_key');
        $signedFieldNames = explode(',', (string) $fields['signed_field_names']);
        $signedData = array();

        foreach ($signedFieldNames as $fieldName) {
            $fieldName = trim($fieldName);
            if ($fieldName === '') {
                continue;
            }
            $signedData[] = $fieldName . '=' . (isset($fields[$fieldName]) ? $fields[$fieldName] : '');
        }

        return base64_encode(hash_hmac('sha256', implode(',', $signedData), $secret, true));
    }

    private function customerData(array $order)
    {
        $fullName = trim((string) $this->value($order, 'nombre_cliente', 'Cliente Libreria Marquense'));
        $parts = preg_split('/\s+/', $fullName);
        $firstName = $parts && count($parts) > 0 ? array_shift($parts) : 'Cliente';
        $lastName = $parts && count($parts) > 0 ? implode(' ', $parts) : 'Marquense';

        return array(
            'first_name' => $this->limit($firstName, 60),
            'last_name' => $this->limit($lastName, 60),
            'email' => $this->validEmail((string) $this->value($order, 'correo_cliente', '')) ?: 'clientes@libreriamarquense.com',
            'phone' => $this->digits((string) $this->value($order, 'telefono_cliente', '')),
            'address' => $this->limit((string) $this->value($order, 'direccion_cliente', 'Ciudad'), 60),
            'city' => 'Guatemala',
            'state' => 'GT',
            'country' => 'GT',
            'postal_code' => '01001',
        );
    }

    private function removeEmptyOptionalFields(array $fields)
    {
        foreach ($fields as $key => $value) {
            if (in_array($key, array('bill_to_phone'), true) && trim((string) $value) === '') {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    private function deviceFingerprintId(array $session)
    {
        if (!isset($session['device_fingerprint_id'])) {
            return '';
        }

        $value = trim((string) $session['device_fingerprint_id']);
        return preg_match('/^[A-Za-z0-9_-]{1,88}$/', $value) ? $value : '';
    }

    private function value(array $array, $key, $default)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    private function limit($value, $length)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        return function_exists('mb_substr') ? mb_substr($value, 0, $length, 'UTF-8') : substr($value, 0, $length);
    }

    private function digits($value)
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private function validEmail($value)
    {
        $value = trim($value);
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }
}
