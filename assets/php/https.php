<?php

require_once dirname(__DIR__, 2) . '/integrations/cybersource/Autoload.php';

function lm_store_transport_config()
{
    static $config;
    if (!$config) {
        $config = \LM\CyberSource\Config::forProcess(dirname(__DIR__, 2));
    }
    return $config;
}

function lm_store_https_options($url)
{
    $config = lm_store_transport_config();
    if (\LM\CyberSource\SideEffectPolicy::isolated($config)
        && preg_match('~^https://ssl\.sol\.sistemasolgt\.com/libremarquenseDos/api/(api_tienda_[A-Za-z0-9_]+\.php)$~D', $url, $match)) {
        $url = rtrim($config->get('isolation.store_api_origin', ''), '/') . '/' . $match[1];
    }
    return \LM\CyberSource\HttpsTransport::curlOptions($config, $url, 'catalog');
}

function lm_store_cache_directory($normalDirectory)
{
    $config = lm_store_transport_config();
    if (\LM\CyberSource\SideEffectPolicy::isolated($config)) {
        \LM\CyberSource\SideEffectPolicy::assertIsolation($config);
        return $config->get('isolation.root') . '/catalog-cache';
    }
    return $normalDirectory;
}
