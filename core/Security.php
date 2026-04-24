<?php

declare(strict_types=1);

namespace Core;

final class Security
{
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateCsrfToken(?string $token): bool
    {
        if ($token === null || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    public static function sanitizeString(string $value): string
    {
        return htmlspecialchars(trim($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function sanitizeInt(string $value): int
    {
        return (int) filter_var(trim($value), FILTER_SANITIZE_NUMBER_INT);
    }

    public static function sanitizeFloat(string $value): float
    {
        return (float) filter_var(trim($value), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    public static function maxLength(string $value, int $max): string
    {
        return mb_strlen($value) > $max ? mb_substr($value, 0, $max) : $value;
    }

    public static function applySecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

        // Content-Security-Policy: permitir solo recursos del mismo origen
        // style-src y script-src incluyen 'unsafe-inline' para soporte de código inline
        // img-src permite data: y blob: para iconos inline y previsualización de PDFs
        // object-src permite blob: para visualización de PDFs en el navegador
        $csp = "default-src 'self' blob:; "
             . "script-src 'self' 'unsafe-inline'; "
             . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
             . "img-src 'self' data: blob:; "
             . "font-src 'self' https://fonts.gstatic.com; "
             . "frame-src 'self' blob:; "
             . "frame-ancestors 'self'; "
             . "form-action 'self'; "
             . "base-uri 'self'; "
             . "object-src 'self' blob:";
        header('Content-Security-Policy: ' . $csp);

        if (Config::get('APP_ENV') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
