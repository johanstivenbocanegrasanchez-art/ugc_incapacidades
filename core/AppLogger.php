<?php

declare(strict_types=1);

namespace Core;

final class AppLogger
{
    private static ?string $logDir = null;

    public static function init(): void
    {
        self::$logDir = dirname(__DIR__) . '/logs';
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }

        // Proteger directorio de logs
        $htaccess = self::$logDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "<IfModule mod_authz_core_module>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core_module>\n  Deny from all\n</IfModule>\n");
        }
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (!Config::isDev()) {
            return;
        }
        self::log('DEBUG', $message, $context);
    }

    public static function exception(\Throwable $e): void
    {
        self::log('EXCEPTION', $e->getMessage(), [
            'file'    => $e->getFile() . ':' . $e->getLine(),
            'trace'   => $e->getTraceAsString(),
            'code'    => $e->getCode(),
            'class'   => get_class($e),
        ]);
    }

    private static function log(string $level, string $message, array $context = []): void
    {
        if (self::$logDir === null) {
            self::init();
        }

        $date    = date('Y-m-d H:i:s');
        $context = $context ? ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line    = "[{$date}] [{$level}] {$message}{$context}" . PHP_EOL;

        $file = self::$logDir . '/app-' . date('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
