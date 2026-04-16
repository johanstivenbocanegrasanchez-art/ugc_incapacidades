<?php

declare(strict_types=1);

namespace Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('UGC_SOL');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Strict');
            if (Config::get('APP_ENV') === 'production') {
                ini_set('session.cookie_secure', '1');
            }
            session_start();
        }
    }

    public static function setUser(array $user): void
    {
        $_SESSION['usuario'] = $user;
    }

    public static function getUser(): array
    {
        return $_SESSION['usuario'] ?? [];
    }

    public static function isLoggedIn(): bool
    {
        return !empty($_SESSION['usuario']);
    }

    public static function getRole(): string
    {
        return $_SESSION['usuario']['rol'] ?? '';
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
