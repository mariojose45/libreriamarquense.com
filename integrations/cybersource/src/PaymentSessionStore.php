<?php

namespace LM\CyberSource;

class PaymentSessionStore
{
    private $dir;

    public function __construct($dir)
    {
        $this->dir = rtrim($dir, '/\\');
        if (!is_dir($this->dir) && !@mkdir($this->dir, 0750, true) && !is_dir($this->dir)) {
            throw new PaymentStorageException('No se pudo preparar el almacenamiento de pagos.');
        }
    }

    public function create(array $data)
    {
        $reference = 'LMQ-' . gmdate('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(4)));
        return $this->withLock('session-' . $reference, function () use ($reference, $data) {
            if ($this->find($reference)) {
                throw new GatewayException('Referencia ya existente.');
            }
            $data['reference'] = $reference;
            $data['created_at'] = gmdate('c');
            return $this->save($reference, $data);
        });
    }

    public function find($reference)
    {
        return $this->read($this->fileForReference($reference));
    }

    public function save($reference, array $data)
    {
        return $this->withLock('write-' . $reference, function () use ($reference, $data) {
            $file = $this->fileForReference($reference);
            $current = $this->read($file);
            $revision = isset($current['_revision']) ? (int) $current['_revision'] : 0;
            if ((isset($data['_revision']) ? (int) $data['_revision'] : 0) !== $revision) {
                throw new PaymentStorageException('La sesion fue actualizada por otro proceso.');
            }
            $data['reference'] = $reference;
            $data['_revision'] = $revision + 1;
            $data['updated_at'] = gmdate('c');
            $this->atomicWrite($file, SensitiveData::redact($data));
            return $data;
        });
    }

    public function withLock($key, callable $callback, $timeoutSeconds = 2)
    {
        $file = $this->dir . '/lock-' . hash('sha256', $key) . '.lck';
        $handle = @fopen($file, 'c');
        if (!$handle) {
            throw new PaymentStorageException('No se pudo bloquear el procesamiento del pago.');
        }
        @chmod($file, 0640);
        $deadline = microtime(true) + $timeoutSeconds;
        try {
            do {
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    return $callback();
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            throw new PaymentStorageException('Pago en procesamiento. Consulte mas tarde.');
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function readIndex($key)
    {
        return $this->read($this->indexFile($key));
    }

    public function writeIndex($key, array $data)
    {
        $this->atomicWrite($this->indexFile($key), $data);
    }

    public function claimQuotation($reference, $quotation, $environment)
    {
        $this->fileForReference($reference);
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $quotation) || !in_array($environment, array('test', 'production', 'prod'), true)) {
            throw new GatewayException('Identidad de cotizacion no valida.');
        }
        $key = 'quotation-' . ($environment === 'prod' ? 'production' : $environment) . '-' . $quotation;
        return $this->withLock($key, function () use ($key, $reference) {
            $owner = $this->readIndex($key);
            if ($owner && $owner['reference'] !== $reference) {
                return false;
            }
            $this->writeIndex($key, array('reference' => $reference));
            return true;
        });
    }

    public function references()
    {
        foreach (glob($this->dir . '/LMQ-*.json') ?: array() as $file) {
            $reference = basename($file, '.json');
            if (preg_match('/^LMQ-[0-9]{14}-[A-F0-9]{8}$/D', $reference)) {
                yield $reference;
            }
        }
    }

    public function recordEvent($reference, array $event)
    {
        $this->fileForReference($reference);
        $id = $reference . '-' . hash('sha256', json_encode($event, JSON_UNESCAPED_SLASHES));
        return $this->withLock('inbox-' . $reference, function () use ($id, $reference, $event) {
            $existing = $this->findEvent($id);
            if ($existing) {
                return $existing;
            }
            $record = array('id' => $id, 'reference' => $reference, 'state' => 'RECEIVED',
                'received_at' => gmdate('c'), 'event' => SensitiveData::redact($event));
            $this->saveEvent($record);
            return $record;
        });
    }

    public function hasPendingEvents($reference)
    {
        $this->fileForReference($reference);
        foreach (glob($this->dir . '/event-' . $reference . '-*.json') ?: array() as $file) {
            $record = $this->read($file);
            if (!$record || !isset($record['state']) || $record['state'] !== 'APPLIED') {
                return true;
            }
        }
        return false;
    }

    public function findEvent($id)
    {
        return $this->read($this->eventFile($id));
    }

    public function saveEvent(array $record)
    {
        $this->atomicWrite($this->eventFile($record['id']), $record);
    }

    public function eventIds()
    {
        foreach (glob($this->dir . '/event-LMQ-*.json') ?: array() as $file) {
            yield substr(basename($file, '.json'), 6);
        }
    }

    private function eventFile($id)
    {
        if (!is_string($id) || !preg_match('/^LMQ-[0-9]{14}-[A-F0-9]{8}-[a-f0-9]{64}$/D', $id)) {
            throw new PaymentStorageException('Identificador de evento no valido.');
        }
        return $this->dir . '/event-' . $id . '.json';
    }

    private function read($file)
    {
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        $data = $raw === false ? null : json_decode($raw, true);
        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new PaymentStorageException('Registro de pago ilegible; requiere revision.');
        }
        return $data;
    }

    protected function atomicWrite($file, array $data)
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new PaymentStorageException('No se pudo serializar el registro de pago.');
        }
        $temporary = $file . '.' . bin2hex(random_bytes(8)) . '.tmp';
        $handle = @fopen($temporary, 'xb');
        if (!$handle) {
            throw new PaymentStorageException('No se pudo guardar el registro de pago.');
        }
        @chmod($temporary, 0640);
        try {
            $offset = 0;
            while ($offset < strlen($json)) {
                $written = fwrite($handle, substr($json, $offset));
                if ($written === false || $written === 0) {
                    throw new PaymentStorageException('Escritura de pago incompleta.');
                }
                $offset += $written;
            }
            if (!fflush($handle) || (function_exists('fsync') && !fsync($handle))) {
                throw new PaymentStorageException('No se pudo sincronizar el registro de pago.');
            }
            fclose($handle);
            $handle = null;
            if (!@rename($temporary, $file)) {
                throw new PaymentStorageException('No se pudo publicar el registro de pago.');
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    private function fileForReference($reference)
    {
        if (!is_string($reference) || !preg_match('/^LMQ-[0-9]{14}-[A-F0-9]{8}$/D', $reference)) {
            throw new GatewayException('Referencia de pago no valida.');
        }

        return $this->dir . '/' . $reference . '.json';
    }

    private function indexFile($key)
    {
        return $this->dir . '/index-' . hash('sha256', $key) . '.json';
    }
}
