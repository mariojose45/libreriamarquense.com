<?php

namespace LM\CyberSource;

class HostedCheckoutService
{
    private $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function buildRedirectUrl(array $session)
    {
        $template = trim((string) $this->config->get('hosted_checkout.redirect_url_template', ''));
        if ($template === '') {
            return null;
        }

        $siteBaseUrl = rtrim((string) $this->config->get('site.base_url'), '/');
        $returnUrl = $siteBaseUrl . $this->config->get('site.return_path') . '?ref=' . rawurlencode($session['reference']);
        $cancelUrl = $siteBaseUrl . $this->config->get('site.cancel_path');

        $replacements = array(
            '{reference}' => rawurlencode($session['reference']),
            '{amount}' => rawurlencode(number_format((float) $session['amount'], 2, '.', '')),
            '{currency}' => rawurlencode($session['currency']),
            '{return_url}' => rawurlencode($returnUrl),
            '{cancel_url}' => rawurlencode($cancelUrl),
            '{device_fingerprint_id}' => rawurlencode(isset($session['device_fingerprint_id']) ? (string) $session['device_fingerprint_id'] : ''),
        );

        return strtr($template, $replacements);
    }

    public function validateProviderSignature(array $params)
    {
        $secret = (string) $this->config->get('hosted_checkout.return_signature_secret', '');
        if ($secret === '') {
            return (bool) $this->config->get('hosted_checkout.accept_unsigned_return', false);
        }

        $signatureParam = (string) $this->config->get('hosted_checkout.signature_param', 'signature');
        if (!isset($params[$signatureParam])) {
            return false;
        }

        $received = (string) $params[$signatureParam];
        unset($params[$signatureParam]);
        ksort($params);

        $pairs = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                continue;
            }
            $pairs[] = $key . '=' . $value;
        }

        $expected = hash_hmac('sha256', implode('&', $pairs), $secret);
        return hash_equals($expected, $received);
    }

    public function normalizeStatus($status)
    {
        $status = strtoupper(trim((string) $status));
        if (in_array($status, array('APPROVED', 'AUTHORIZED', 'PAID', 'SUCCESS', 'OK'), true)) {
            return 'APPROVED';
        }

        if (in_array($status, array('DECLINED', 'REJECTED', 'FAILED', 'ERROR'), true)) {
            return 'DECLINED';
        }

        if (in_array($status, array('CANCELLED', 'CANCELED', 'VOIDED'), true)) {
            return 'CANCELLED';
        }

        return 'PENDING';
    }
}
