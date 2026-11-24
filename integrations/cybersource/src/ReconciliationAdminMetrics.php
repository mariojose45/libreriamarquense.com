<?php

namespace LM\CyberSource;

final class ReconciliationAdminMetrics
{
    private const STAGES = array('private_init_ms', 'session_open_ms', 'session_close_ms', 'auth_current_ms',
        'account_lookup_ms', 'login_ms', 'password_verify_ms', 'rate_reservation_ms', 'rate_clear_ms',
        'audit_ms', 'service_init_ms', 'pending_ms', 'detail_ms', 'render_ms', 'link_ms');
    private const COUNTERS = array('account_lookups', 'records_scanned', 'pending_found', 'records_unreadable');
    private $enabled;
    private $started;
    private $stages = array();
    private $counts = array();

    public function __construct($enabled = false)
    {
        $this->enabled = $enabled === true && function_exists('hrtime');
        $this->started = $this->enabled ? hrtime(true) : 0;
    }

    public function measure($stage, callable $operation)
    {
        if (!$this->enabled || !in_array($stage, self::STAGES, true)) {
            return $operation();
        }
        $start = hrtime(true);
        try { return $operation(); }
        finally {
            $this->stages[$stage] = (isset($this->stages[$stage]) ? $this->stages[$stage] : 0)
                + (hrtime(true) - $start) / 1000000;
        }
    }

    public function count($name, $amount = 1)
    {
        if ($this->enabled && in_array($name, self::COUNTERS, true) && is_int($amount) && $amount >= 0) {
            $this->counts[$name] = min(1000000, (isset($this->counts[$name]) ? $this->counts[$name] : 0) + $amount);
        }
    }

    public function snapshot($kind)
    {
        if (!$this->enabled) { return null; }
        $stages = array();
        foreach ($this->stages as $name => $duration) { $stages[$name] = round($duration, 3); }
        return array('version' => 1, 'at' => gmdate('Y-m-d\TH:i:s\Z'),
            'kind' => in_array($kind, array('login', 'dashboard', 'detail', 'post', 'logout', 'error'), true) ? $kind : 'other',
            'request_ms' => round((hrtime(true) - $this->started) / 1000000, 3),
            'stages_ms' => $stages, 'counts' => $this->counts);
    }

    public static function safeRecord(array $record)
    {
        if (count($record) !== 6 || !isset($record['version'], $record['at'], $record['kind'], $record['request_ms'], $record['stages_ms'], $record['counts'])
            || $record['version'] !== 1 || !is_string($record['at']) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $record['at'])
            || !in_array($record['kind'], array('login', 'dashboard', 'detail', 'post', 'logout', 'error', 'other'), true)
            || !is_array($record['stages_ms']) || !is_array($record['counts'])) { return false; }
        if (!self::duration($record['request_ms'])) { return false; }
        foreach ($record['stages_ms'] as $name => $value) {
            if (!in_array($name, self::STAGES, true) || !self::duration($value)) { return false; }
        }
        foreach ($record['counts'] as $name => $value) {
            if (!in_array($name, self::COUNTERS, true) || !is_int($value) || $value < 0 || $value > 1000000) { return false; }
        }
        return true;
    }

    private static function duration($value)
    {
        return (is_int($value) || is_float($value)) && is_finite((float) $value) && $value >= 0 && $value <= 86400000;
    }
}
