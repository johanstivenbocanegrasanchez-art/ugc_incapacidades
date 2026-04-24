<?php

declare(strict_types=1);

namespace Core;

final class Config
{
    private static array $values = [];

    public static function load(string $envPath): void
    {
        if (!file_exists($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            self::$values[$key] = $value;
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$values[$key])) {
            return self::$values[$key];
        }
        $env = getenv($key);
        if ($env !== false) {
            self::$values[$key] = $env;
            return $env;
        }
        return $default;
    }

    public static function getInt(string $key, int $default = 0): int
    {
        return (int) self::get($key, $default);
    }

    public static function getBool(string $key, bool $default = false): bool
    {
        $val = self::get($key, $default);
        if (is_bool($val)) {
            return $val;
        }
        return filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    public static function isDev(): bool
    {
        return self::get('APP_ENV', 'production') === 'development';
    }

    public static function baseUrl(): string
    {
        return rtrim(self::get('BASE_URL', '/'), '/');
    }

    public static function assetUrl(string $path): string
    {
        $relativePath = '/' . ltrim(str_replace('\\', '/', $path), '/');
        $absolutePath = dirname(__DIR__) . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $assetUrl = self::baseUrl() . $relativePath;

        if (!is_file($absolutePath)) {
            return $assetUrl;
        }

        return $assetUrl . '?v=' . filemtime($absolutePath);
    }

    public static function appName(): string
    {
        return self::get('APP_NAME', 'UGC');
    }
}
