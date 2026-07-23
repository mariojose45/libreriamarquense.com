<?php

namespace LM\CyberSource;

class OrderReferenceMailer
{
    private $config;
    private $logger;

    public function __construct(Config $config, SecureLogger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function send(array $orderPayload, array $orderResponse = array(), $paymentReference = '')
    {
        if (!$this->isEnabled()) {
            return array('attempted' => false, 'sent' => false, 'reason' => 'disabled');
        }

        $recipient = isset($orderPayload['correo_cliente']) ? strtolower(trim((string) $orderPayload['correo_cliente'])) : '';
        if ($recipient === '') {
            return array('attempted' => false, 'sent' => false, 'reason' => 'missing_email');
        }

        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->logger->warning('Correo de confirmacion omitido por email invalido', array(
                'email_domain' => $this->emailDomain($recipient),
            ));

            return array('attempted' => false, 'sent' => false, 'reason' => 'invalid_email');
        }

        if (!$this->orderResponseAllowsEmail($orderResponse)) {
            return array('attempted' => false, 'sent' => false, 'reason' => 'order_response_failed');
        }

        $quotationNumber = $this->extractQuotationNumber($orderResponse);
        $paymentReference = trim((string) $paymentReference);
        $mainReference = $quotationNumber !== '' ? 'Cotizacion #' . $quotationNumber : $paymentReference;

        if ($mainReference === '') {
            $this->logger->warning('Correo de confirmacion omitido por falta de referencia', array(
                'email_domain' => $this->emailDomain($recipient),
            ));

            return array('attempted' => false, 'sent' => false, 'reason' => 'missing_reference');
        }

        $subject = $this->buildSubject($quotationNumber, $paymentReference);
        $body = $this->buildBody($orderPayload, $quotationNumber, $paymentReference);
        $headers = $this->buildHeaders($recipient);
        $mailParameters = $this->buildMailParameters();

        $sent = $mailParameters !== ''
            ? @mail($recipient, $subject, $body, $headers, $mailParameters)
            : @mail($recipient, $subject, $body, $headers);
        if ($sent) {
            $this->logger->info('Correo de confirmacion enviado', array(
                'reference' => $mainReference,
                'email_domain' => $this->emailDomain($recipient),
            ));

            return array('attempted' => true, 'sent' => true, 'reason' => '');
        }

        $this->logger->warning('No se pudo enviar el correo de confirmacion', array(
            'reference' => $mainReference,
            'email_domain' => $this->emailDomain($recipient),
        ));

        return array('attempted' => true, 'sent' => false, 'reason' => 'mail_failed');
    }

    private function isEnabled()
    {
        return (bool) $this->config->get('order_email.enabled', true);
    }

    private function extractQuotationNumber(array $response)
    {
        foreach (array('idcotizacion', 'id_cotizacion', 'numero_cotizacion', 'no_cotizacion', 'cotizacion') as $key) {
            if (isset($response[$key]) && trim((string) $response[$key]) !== '') {
                return trim((string) $response[$key]);
            }
        }

        return '';
    }

    private function orderResponseAllowsEmail(array $response)
    {
        if (!array_key_exists('success', $response)) {
            return true;
        }

        if (is_bool($response['success'])) {
            return $response['success'];
        }

        $value = strtolower(trim((string) $response['success']));
        return !in_array($value, array('', '0', 'false', 'no'), true);
    }

    private function buildSubject($quotationNumber, $paymentReference)
    {
        if ($quotationNumber !== '') {
            return 'Cotizacion #' . $quotationNumber . ' - Libreria Marquense';
        }

        return 'Referencia ' . $paymentReference . ' - Libreria Marquense';
    }

    private function buildBody(array $orderPayload, $quotationNumber, $paymentReference)
    {
        $customerName = isset($orderPayload['nombre_cliente']) ? trim((string) $orderPayload['nombre_cliente']) : '';
        $total = isset($orderPayload['total_venta']) ? number_format((float) $orderPayload['total_venta'], 2, '.', '') : '0.00';
        $paymentMethod = isset($orderPayload['forma_pago']) ? trim((string) $orderPayload['forma_pago']) : '';
        $deliveryComment = isset($orderPayload['comentario_cotizacion']) ? trim((string) $orderPayload['comentario_cotizacion']) : '';

        $lines = array();
        $lines[] = $customerName !== '' ? 'Hola ' . $customerName . ',' : 'Hola,';
        $lines[] = '';
        $lines[] = 'Gracias por comprar en Libreria Marquense.';
        $lines[] = '';

        if ($quotationNumber !== '') {
            $lines[] = 'Tu numero de cotizacion es: #' . $quotationNumber;
        }

        if ($paymentReference !== '') {
            $lines[] = 'Tu referencia de pago es: ' . $paymentReference;
        }

        $lines[] = 'Total del pedido: Q' . $total;

        if ($paymentMethod !== '') {
            $lines[] = 'Metodo de pago: ' . $paymentMethod;
        }

        if ($deliveryComment !== '') {
            $lines[] = 'Entrega: ' . $deliveryComment;
        }

        $lines[] = '';
        $lines[] = 'Conserva este correo para futuras consultas sobre tu pedido.';
        $lines[] = '';
        $lines[] = 'Libreria Marquense';

        return implode("\n", $lines);
    }

    private function buildHeaders($recipient)
    {
        $fromEmail = $this->validEmail((string) $this->config->get('order_email.from_email', 'no-reply@libreriamarquense.com'));
        if ($fromEmail === '') {
            $fromEmail = 'no-reply@libreriamarquense.com';
        }

        $fromName = $this->cleanHeaderValue((string) $this->config->get('order_email.from_name', 'Libreria Marquense'));
        if ($fromName === '') {
            $fromName = 'Libreria Marquense';
        }

        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
        );

        $replyTo = $this->validEmail((string) $this->config->get('order_email.reply_to', 'servicioalcliente@libreriamarquense.com'));
        if ($replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }

        $bcc = $this->validEmail((string) $this->config->get('order_email.bcc', ''));
        if ($bcc !== '' && !hash_equals(strtolower($recipient), strtolower($bcc))) {
            $headers[] = 'Bcc: ' . $bcc;
        }

        return implode("\r\n", $headers);
    }

    private function buildMailParameters()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return '';
        }

        $fromEmail = $this->validEmail((string) $this->config->get('order_email.from_email', 'no-reply@libreriamarquense.com'));
        return $this->validEnvelopeEmail($fromEmail) ? '-f' . $fromEmail : '';
    }

    private function validEmail($value)
    {
        $value = strtolower(trim($this->cleanHeaderValue($value)));
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '';
    }

    private function validEnvelopeEmail($value)
    {
        return (bool) preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', (string) $value);
    }

    private function cleanHeaderValue($value)
    {
        return trim(preg_replace('/[\r\n]+/', ' ', (string) $value));
    }

    private function emailDomain($email)
    {
        $parts = explode('@', (string) $email, 2);
        return isset($parts[1]) ? strtolower($parts[1]) : '';
    }
}
