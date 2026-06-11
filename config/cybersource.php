<?php

/**
 * Configuracion publica/segura de la pasarela.
 *
 * NO escriba credenciales reales en este archivo.
 * Para credenciales use variables de entorno o cree:
 * config/cybersource.private.php
 *
 * En config/cybersource.private.example.php esta la plantilla con los campos:
 * - Merchant ID
 * - Key ID
 * - Shared Secret
 * - URL TEST: https://apitest.cybersource.com
 * - URL PRODUCCION: https://api.cybersource.com
 */

$privateConfig = __DIR__ . '/cybersource.private.php';
$private = is_file($privateConfig) ? require $privateConfig : array();

$config = array(
    'enabled' => false,
    'environment' => getenv('CYBERSOURCE_ENVIRONMENT') ?: 'test',
    'currency' => getenv('CYBERSOURCE_CURRENCY') ?: 'GTQ',
    'operation' => getenv('CYBERSOURCE_OPERATION') ?: 'authorization',

    'site' => array(
        'base_url' => getenv('SITE_BASE_URL') ?: 'https://libreriamarquense.com',
        'return_path' => '/api/cybersource/payment_return.php',
        'cancel_path' => '/cart.php?payment=cancelled',
    ),

    'cybersource' => array(
        'test_base_url' => 'https://apitest.cybersource.com',
        'production_base_url' => 'https://api.cybersource.com',
        'merchant_id' => getenv('CYBERSOURCE_MERCHANT_ID') ?: '',
        'key_id' => getenv('CYBERSOURCE_KEY_ID') ?: '',
        'shared_secret' => getenv('CYBERSOURCE_SHARED_SECRET') ?: '',
    ),

    /*
     * Secure Acceptance / formulario de pago alojado.
     *
     * Este es el flujo donde el cliente ingresa tarjeta directamente en la
     * pagina segura de CyberSource/Neonet. Aqui NO van datos de tarjeta.
     * Las claves reales deben colocarse solo en cybersource.private.php o env.
     */
    'secure_acceptance' => array(
        'enabled' => false,
        'profile_id' => getenv('CYBERSOURCE_SA_PROFILE_ID') ?: '',
        'access_key' => getenv('CYBERSOURCE_SA_ACCESS_KEY') ?: '',
        'secret_key' => getenv('CYBERSOURCE_SA_SECRET_KEY') ?: '',
        'test_endpoint' => getenv('CYBERSOURCE_SA_TEST_ENDPOINT') ?: 'https://testsecureacceptance.cybersource.com/pay',
        'production_endpoint' => getenv('CYBERSOURCE_SA_PRODUCTION_ENDPOINT') ?: 'https://secureacceptance.cybersource.com/pay',
        'transaction_type' => getenv('CYBERSOURCE_SA_TRANSACTION_TYPE') ?: '',
        'locale' => getenv('CYBERSOURCE_SA_LOCALE') ?: 'es',
        'session_ttl_minutes' => 60,
    ),

    /*
     * Neonet / pagina segura hospedada:
     *
     * Como el ingreso de tarjeta se hace en la pagina de Neonet, no se
     * capturan numeros de tarjeta ni CVV en este sitio. Cuando Neonet entregue
     * la URL oficial, configure redirect_url_template en el archivo privado.
     *
     * Placeholders disponibles:
     * {reference}, {amount}, {currency}, {return_url}, {cancel_url}
     */
    'hosted_checkout' => array(
        'redirect_url_template' => getenv('NEONET_CHECKOUT_URL_TEMPLATE') ?: '',
        'session_ttl_minutes' => 60,
        'return_signature_secret' => getenv('NEONET_RETURN_SIGNATURE_SECRET') ?: '',
        'signature_param' => getenv('NEONET_SIGNATURE_PARAM') ?: 'signature',
        'accept_unsigned_return' => false,
    ),

    'security' => array(
        'admin_token' => getenv('CYBERSOURCE_ADMIN_TOKEN') ?: '',
        'max_amount' => 50000,
        'max_quantity' => 99,
        'max_line_items' => 100,
        'max_unit_price' => 50000,
    ),

    'external_order_api' => array(
        'url' => 'https://ssl.sol.sistemasolgt.com/libremarquenseDos/api/api_cotizacion_insertar.php',
        'timeout_seconds' => 25,
    ),

    'delivery' => require __DIR__ . '/delivery.php',

    'paths' => array(
        'session_dir' => dirname(__DIR__) . '/storage/cybersource',
        'log_file' => dirname(__DIR__) . '/logs/cybersource.log',
    ),
);

if (is_array($private)) {
    $config = array_replace_recursive($config, $private);
}

return $config;
