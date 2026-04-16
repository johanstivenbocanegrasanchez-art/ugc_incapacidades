<?php

declare(strict_types=1);

namespace Config;

use Core\Config;

final class Oracle
{
    private static ?Oracle $instance = null;
    private $conn = null;
    private bool $disponible = false;

    private function __construct()
    {
        if (!function_exists('oci_connect')) {
            return;
        }

        $host    = Config::get('ORACLE_HOST', '');
        $port    = Config::get('ORACLE_PORT', '1521');
        $service = Config::get('ORACLE_SERVICE', '');
        $user    = Config::get('ORACLE_USER', '');
        $pass    = Config::get('ORACLE_PASS', '');
        $charset = Config::get('ORACLE_CHARSET', 'AL32UTF8');

        $dsn = "{$host}:{$port}/{$service}";
        $conn = @oci_connect($user, $pass, $dsn, $charset);

        if ($conn) {
            $this->conn = $conn;
            $this->disponible = true;
        }
    }

    public static function getInstance(): Oracle
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function estaDisponible(): bool
    {
        return $this->disponible;
    }

    public function query(string $sql, array $binds = []): array
    {
        if (!$this->disponible) {
            return [];
        }

        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            return [];
        }

        foreach ($binds as $key => &$value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        if (!@oci_execute($stmt, OCI_DEFAULT)) {
            $error = oci_error($stmt);
            error_log('Oracle query error: ' . ($error['message'] ?? 'Unknown'));
            oci_free_statement($stmt);
            return [];
        }

        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $rows[] = $row;
        }
        oci_free_statement($stmt);

        return $rows;
    }

    public function execute(string $sql, array $binds = []): bool
    {
        if (!$this->disponible) {
            return false;
        }

        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            return false;
        }

        foreach ($binds as $key => &$value) {
            oci_bind_by_name($stmt, $key, $value);
        }

        $result = @oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        if (!$result) {
            $error = oci_error($stmt);
            error_log('Oracle execute error: ' . ($error['message'] ?? 'Unknown'));
        }

        oci_free_statement($stmt);
        return (bool) $result;
    }
}
