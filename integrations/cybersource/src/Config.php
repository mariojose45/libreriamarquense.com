<?php

namespace LM\CyberSource;

class Config
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromFile($path)
    {
        if (!is_file($path)) {
            throw new GatewayException('No existe el archivo de configuracion de CyberSource.');
        }

        $data = require $path;

        if (!is_array($data)) {
            throw new GatewayException('La configuracion de CyberSource no es valida.');
        }

        return new self($data);
    }

    public static function forProcess($root)
    {
        if (getenv('LM_PAYMENT_ISOLATED_TEST') === '1') {
            $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
                ? strtolower((string) parse_url('http://' . $_SERVER['HTTP_HOST'], PHP_URL_HOST)) : '';
            $args = isset($GLOBALS['argv']) && is_array($GLOBALS['argv']) ? $GLOBALS['argv'] : array();
            if (in_array($host, array('libreriamarquense.com', 'www.libreriamarquense.com'), true)
                || getenv('LM_PAYMENT_PRODUCTION_CONFIRMED') === '1'
                || in_array('--allow-production', $args, true)) {
                throw new GatewayException('El aislamiento TEST no puede usarse en el dominio o procesador productivo.');
            }
            $config = self::fromFile($root . '/integrations/cybersource/tests/isolated_config.php');
            SideEffectPolicy::assertIsolation($config);
            return $config;
        }
        return self::fromFile($root . '/config/cybersource.php');
    }

    public function get($path, $default = null)
    {
        $value = $this->data;
        foreach (explode('.', $path) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    public function isEnabled()
    {
        return (bool) $this->get('enabled', false);
    }

    public function getCyberSourceBaseUrl()
    {
        $environment = strtolower((string) $this->get('environment', 'test'));
        if ($environment === 'production' || $environment === 'prod') {
            return rtrim((string) $this->get('cybersource.production_base_url'), '/');
        }

        return rtrim((string) $this->get('cybersource.test_base_url'), '/');
    }

    public function hasRestCredentials()
    {
        return trim((string) $this->get('cybersource.merchant_id', '')) !== ''
            && trim((string) $this->get('cybersource.key_id', '')) !== ''
            && trim((string) $this->get('cybersource.shared_secret', '')) !== '';
    }

    public function hasSecureAcceptance()
    {
        return (bool) $this->get('secure_acceptance.enabled', false)
            && trim((string) $this->get('secure_acceptance.profile_id', '')) !== ''
            && trim((string) $this->get('secure_acceptance.access_key', '')) !== ''
            && trim((string) $this->get('secure_acceptance.secret_key', '')) !== '';
    }

    public function getSecureAcceptanceEndpoint()
    {
        $environment = strtolower((string) $this->get('environment', 'test'));
        if ($environment === 'production' || $environment === 'prod') {
            return rtrim((string) $this->get('secure_acceptance.production_endpoint'), '/');
        }

        return rtrim((string) $this->get('secure_acceptance.test_endpoint'), '/');
    }

    public function hasHostedCheckout()
    {
        return trim((string) $this->get('hosted_checkout.redirect_url_template', '')) !== '';
    }
}
