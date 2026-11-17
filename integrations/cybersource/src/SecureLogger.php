<?php

namespace LM\CyberSource;

class SecureLogger
{
    private $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function info($message, array $context = array())
    {
        $this->write('INFO', $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->write('WARNING', $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->write('ERROR', $message, $context);
    }

    private function write($level, $message, array $context)
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
        }

        if (!is_dir($dir) || !is_writable($dir)) {
            return;
        }

        $line = json_encode(array(
            'time' => gmdate('c'),
            'level' => $level,
            'message' => SensitiveData::forLog($message),
            'context' => SensitiveData::forLog($context),
        ), JSON_UNESCAPED_SLASHES);

        if ($line !== false && @file_put_contents($this->file, $line . PHP_EOL, FILE_APPEND | LOCK_EX) !== false) {
            @chmod($this->file, 0640);
        } else {
            error_log('CyberSource: no se pudo escribir el log seguro.');
        }
    }
}
