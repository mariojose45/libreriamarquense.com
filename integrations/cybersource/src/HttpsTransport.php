<?php

namespace LM\CyberSource;

class HttpsTransport
{
    public static function curlOptions(Config $config, $url, $purpose = 'order')
    {
        $parts = parse_url($url);
        if (!$parts || !isset($parts['scheme'], $parts['host']) || $parts['scheme'] !== 'https'
            || isset($parts['user']) || isset($parts['pass'])) {
            throw new GatewayException('Se requiere un destino HTTPS sin credenciales en la URL.');
        }
        if ($purpose === 'order') {
            SideEffectPolicy::assertOrderTransport($config, $url);
        } elseif ($purpose === 'catalog') {
            SideEffectPolicy::assertCatalogTransport($config, $url);
        } elseif ($purpose === 'rest') {
            SideEffectPolicy::assertRestTransport($config, $url);
        } else {
            throw new GatewayException('Proposito de transporte no permitido.');
        }
        $options = array(CURLOPT_URL => $url, CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2, CURLOPT_FOLLOWLOCATION => false, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS);
        $ca = (string) $config->get('transport.ca_file', '');
        if ($ca !== '') {
            $options[CURLOPT_CAINFO] = $ca;
        }
        if (SideEffectPolicy::isolated($config)) {
            $options[CURLOPT_RESOLVE] = array($parts['host'] . ':' . (isset($parts['port']) ? $parts['port'] : 443) . ':127.0.0.1');
            $options[CURLOPT_PROXY] = '';
        }
        return $options;
    }

    public static function postJson(Config $config, $url, array $payload, $timeout, $purpose = 'order')
    {
        // The transport itself enforces policy, not only its current callers.
        if ($purpose === 'order') {
            SideEffectPolicy::assertOrderTransport($config, $url);
        } elseif ($purpose === 'catalog') {
            SideEffectPolicy::assertCatalogTransport($config, $url);
        } else {
            throw new GatewayException('Proposito de transporte no permitido.');
        }
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new GatewayException('No se pudo serializar la solicitud.');
        }
        $ca = (string) $config->get('transport.ca_file', '');
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $options = array(CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Accept: application/json'),
                CURLOPT_POSTFIELDS => $body, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => min(5, $timeout));
            $options = array_replace($options, self::curlOptions($config, $url, $purpose));
            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            unset($ch);
        } else {
            if (SideEffectPolicy::isolated($config)) {
                throw new GatewayException('El transporte TEST aislado requiere cURL para fijar loopback.');
            }
            $ssl = array('verify_peer' => true, 'verify_peer_name' => true);
            if ($ca !== '') {
                $ssl['cafile'] = $ca;
            }
            $context = stream_context_create(array('http' => array('method' => 'POST', 'content' => $body,
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\n", 'timeout' => $timeout,
                'ignore_errors' => true, 'follow_location' => 0), 'ssl' => $ssl));
            $response = @file_get_contents($url, false, $context);
            $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers()
                : (isset($http_response_header) ? $http_response_header : array());
            $status = isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $m) ? (int) $m[1] : 0;
        }
        return array($status, $response);
    }
}
