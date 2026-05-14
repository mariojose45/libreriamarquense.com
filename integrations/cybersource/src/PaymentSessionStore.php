<?php

namespace LM\CyberSource;

class PaymentSessionStore
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0755, true);
        }
    }

    public function create(array $data)
    {
        $reference = $this->generateReference();
        $data['reference'] = $reference;
        $data['created_at'] = gmdate('c');
        $data['updated_at'] = gmdate('c');
        $this->save($reference, $data);

        return $data;
    }

    public function find($reference)
    {
        $file = $this->fileForReference($reference);
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    public function save($reference, array $data)
    {
        $data['updated_at'] = gmdate('c');
        $file = $this->fileForReference($reference);
        file_put_contents($file, json_encode(SensitiveData::redact($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    private function generateReference()
    {
        return 'LMQ-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    private function fileForReference($reference)
    {
        $safe = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $reference);
        if ($safe === '') {
            throw new GatewayException('Referencia de pago no valida.');
        }

        return $this->dir . '/' . $safe . '.json';
    }
}
