<?php

/**
 * Copie este archivo como:
 * config/cybersource.private.php
 *
 * Ese archivo privado esta en .gitignore y NO debe subirse al repositorio.
 */

return array(
    'enabled' => true,

    // Use "test" mientras CyberSource/Neonet este en pruebas.
    'environment' => 'test',

    // Cambie a USD solo si su comercio/procesador lo solicita.
    'currency' => 'GTQ',

    // "authorization" = autorizar primero y capturar despues.
    // "sale" = autorizacion + captura inmediata.
    'operation' => 'authorization',

    'cybersource' => array(
        // Merchant ID entregado por CyberSource/Neonet.
        'merchant_id' => 'COLOQUE_AQUI_SU_MERCHANT_ID',

        // Key ID entregado por CyberSource/Neonet.
        'key_id' => 'COLOQUE_AQUI_SU_KEY_ID',

        // Shared Secret entregado por CyberSource/Neonet.
        'shared_secret' => 'COLOQUE_AQUI_SU_SHARED_SECRET',

        // Ambientes oficiales:
        'test_base_url' => 'https://apitest.cybersource.com',
        'production_base_url' => 'https://api.cybersource.com',
    ),

    'secure_acceptance' => array(
        /*
         * Use esta seccion cuando el cliente ingrese tarjeta en la pagina
         * segura de CyberSource/Neonet.
         */
        'enabled' => true,

        // Profile ID de Secure Acceptance.
        'profile_id' => 'COLOQUE_AQUI_SU_PROFILE_ID',

        // Access Key de Secure Acceptance.
        'access_key' => 'COLOQUE_AQUI_SU_ACCESS_KEY',

        // Secret Key de Secure Acceptance. No compartir ni subir al repositorio.
        'secret_key' => 'COLOQUE_AQUI_SU_SECRET_KEY',

        // Endpoints oficiales del formulario alojado.
        'test_endpoint' => 'https://testsecureacceptance.cybersource.com/pay',
        'production_endpoint' => 'https://secureacceptance.cybersource.com/pay',

        // Si se deja vacio, usa el valor de "operation".
        'transaction_type' => '',
        'locale' => 'es',
        'session_ttl_minutes' => 60,
    ),

    'hosted_checkout' => array(
        /*
         * Pegue aqui la plantilla oficial de redireccion que entregue Neonet.
         * Ejemplo ilustrativo, NO usar en produccion sin confirmacion de Neonet:
         * https://checkout.neonet.com/pay?reference={reference}&amount={amount}&currency={currency}&return_url={return_url}&cancel_url={cancel_url}
         */
        'redirect_url_template' => '',

        /*
         * Si Neonet firma el retorno, coloque aqui el secreto de firma.
         * Si no hay firma, el sistema deja el pago en validacion para no
         * confiar ciegamente en parametros del navegador.
         */
        'return_signature_secret' => '',
        'signature_param' => 'signature',
        'accept_unsigned_return' => false,
    ),

    'security' => array(
        // Token interno para endpoints administrativos: capture/refund/void/reversal.
        'admin_token' => 'GENERE_UN_TOKEN_LARGO_Y_PRIVADO',
        'max_amount' => 50000,
    ),

    'order_email' => array(
        // Requiere que PHP mail() este configurado en el servidor.
        'enabled' => true,
        'from_email' => 'no-reply@libreriamarquense.com',
        'from_name' => 'Libreria Marquense',
        'reply_to' => 'servicioslcliente@libreriamarquense.com',
        'bcc' => '',
    ),
);
