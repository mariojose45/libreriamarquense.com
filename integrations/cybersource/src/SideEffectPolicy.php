<?php

namespace LM\CyberSource;

class SideEffectPolicy
{
    public static function isolated(Config $config)
    {
        return $config->get('environment', 'test') === 'test' && $config->get('isolation.enabled', false) === true;
    }

    public static function assertOrderTransport(Config $config, $url)
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new GatewayException('El registro de pedidos requiere un destino HTTPS seguro.');
        }
        if (strtolower((string) $config->get('environment', 'test')) === 'test') {
            if (!self::isolated($config)) {
                throw new GatewayException('TEST bloquea el registro real de pedidos. Use el entorno aislado.');
            }
            self::assertIsolation($config);
            if (!$parts || $parts['scheme'] !== 'https' || !in_array($parts['host'], array('localhost', '127.0.0.1', '[::1]'), true)
                || isset($parts['user']) || isset($parts['pass'])) {
                throw new GatewayException('TEST solo permite el simulador HTTPS local.');
            }
        } elseif (!in_array($config->get('environment'), array('production', 'prod'), true)
            || $config->get('execution.production_authorized', false) !== true) {
            throw new GatewayException('La ejecucion productiva necesita autorizacion explicita de despliegue.');
        }
    }

    public static function assertCheckout(Config $config)
    {
        $endpoint = $config->getSecureAcceptanceEndpoint();
        $host = parse_url($endpoint, PHP_URL_HOST);
        if (parse_url($endpoint, PHP_URL_SCHEME) !== 'https' || parse_url($endpoint, PHP_URL_USER) !== null) {
            throw new GatewayException('El formulario requiere un endpoint HTTPS seguro.');
        }
        if (self::isolated($config)) {
            self::assertIsolation($config);
            $expected = $config->get('isolation.provider_test', false) ? 'testsecureacceptance.cybersource.com' : 'localhost';
            if ($host !== $expected) {
                throw new GatewayException('El checkout simulado debe permanecer en localhost.');
            }
        } elseif ($config->get('environment', 'test') === 'test') {
            if ($host !== 'testsecureacceptance.cybersource.com') {
                throw new GatewayException('TEST no puede usar un endpoint productivo de pago.');
            }
        } elseif (!in_array($config->get('environment'), array('production', 'prod'), true)
            || $config->get('execution.production_authorized', false) !== true || $host !== 'secureacceptance.cybersource.com') {
            throw new GatewayException('Checkout productivo no autorizado o endpoint incorrecto.');
        }
    }

    public static function assertIsolation(Config $config)
    {
        $root = realpath((string) $config->get('isolation.root', ''));
        $application = realpath(dirname(__DIR__, 3));
        if (!self::isolated($config) || !$root || !$application
            || !preg_match('/^lm-cybersource-(?:security|http)-[a-f0-9]{16}$/D', basename($root))
            || !is_file($root . '/fixture-only')) {
            throw new GatewayException('Falta un almacenamiento TEST aislado verificable.');
        }
        foreach (array('paths.session_dir', 'paths.log_file') as $field) {
            $path = str_replace('\\', '/', (string) $config->get($field));
            $base = str_replace('\\', '/', $root);
            $resolved = realpath($path) ?: realpath(dirname($path));
            $resolved = $resolved ? str_replace('\\', '/', $resolved) : '';
            if (DIRECTORY_SEPARATOR === '\\') {
                $path = strtolower($path);
                $base = strtolower($base);
                $resolved = strtolower($resolved);
            }
            if (preg_match('~(?:^|/)\.\.(?:/|$)~', $path) || ($path !== $base && strpos($path, $base . '/') !== 0)
                || ($resolved !== $base && strpos($resolved, $base . '/') !== 0)) {
                throw new GatewayException('TEST no puede compartir almacenamiento o logs con la tienda.');
            }
        }
        $app = strtolower(str_replace('\\', '/', $application));
        $base = strtolower(str_replace('\\', '/', $root));
        if ($base === $app || strpos($base, $app . '/') === 0 || $config->get('order_email.mode') !== 'capture') {
            throw new GatewayException('El entorno TEST debe estar fuera de la tienda y capturar el correo.');
        }
    }

    public static function assertWorker(Config $config, array $args)
    {
        if ($config->get('environment', 'test') === 'test') {
            self::assertIsolation($config);
            return;
        }
        if (!in_array($config->get('environment'), array('production', 'prod'), true)
            || !in_array('--allow-production', $args, true) || getenv('LM_PAYMENT_PRODUCTION_CONFIRMED') !== '1'
            || $config->get('execution.production_authorized', false) !== true) {
            throw new GatewayException('Procesador productivo bloqueado: requiere autorizacion de configuracion, entorno y CLI.');
        }
    }

    public static function assertCatalogTransport(Config $config, $url)
    {
        $parts = parse_url($url);
        if (!in_array($config->get('environment', 'test'), array('test', 'production', 'prod'), true)
            || !$parts || !isset($parts['scheme'], $parts['host'], $parts['path']) || $parts['scheme'] !== 'https'
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            throw new GatewayException('Destino de catalogo no permitido.');
        }
        if (self::isolated($config)) {
            self::assertIsolation($config);
            if (!in_array($parts['host'], array('localhost', '127.0.0.1'), true)
                || !($parts['path'] === '/mock/catalog'
                    || preg_match('~^/mock/store/api_tienda_[A-Za-z0-9_]+\.php$~D', $parts['path']))) {
                throw new GatewayException('El catalogo TEST solo permite consultas al simulador local.');
            }
        } elseif ($parts['host'] !== 'ssl.sol.sistemasolgt.com' || isset($parts['port']) && $parts['port'] !== 443
            || !preg_match('~^/libremarquenseDos/api/api_tienda_[A-Za-z0-9_]+\.php$~D', $parts['path'])) {
            throw new GatewayException('Solo se permiten las consultas de catalogo de Marquense.');
        }
    }

    public static function assertRestTransport(Config $config, $url)
    {
        $environment = $config->get('environment', 'test');
        $parts = parse_url($url);
        $expected = $environment === 'test' ? 'apitest.cybersource.com' : 'api.cybersource.com';
        if (self::isolated($config) || !in_array($environment, array('test', 'production', 'prod'), true)
            || !$parts || !isset($parts['scheme'], $parts['host'], $parts['path']) || $parts['scheme'] !== 'https'
            || $parts['host'] !== $expected || isset($parts['port']) && $parts['port'] !== 443
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
            || !preg_match('~^/pts/v2/[A-Za-z0-9_/-]+$~D', $parts['path'])
            || $environment !== 'test' && $config->get('execution.production_authorized', false) !== true) {
            throw new GatewayException('Transporte REST bloqueado por ambiente o destino.');
        }
    }
}
