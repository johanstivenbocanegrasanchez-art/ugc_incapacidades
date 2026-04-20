<?php

declare(strict_types=1);

// PSR-4 Autoloader vía Composer (o fallback manual) - CARGAR PRIMERO
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    spl_autoload_register(function (string $class): void {
        $prefixes = [
            'App\\'   => __DIR__ . '/app/',
            'Core\\'  => __DIR__ . '/core/',
            'Config\\' => __DIR__ . '/config/',
        ];
        foreach ($prefixes as $prefix => $baseDir) {
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                continue;
            }
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    });
}

use Core\Config;
use Core\Session;
use Core\Security;
use Core\Router;
use Core\AppLogger;

// Cargar configuración (incluye .env)
require_once __DIR__ . '/config/config.php';

// Iniciar logger y registrar handlers globales de error
AppLogger::init();

set_exception_handler(function (\Throwable $e): void {
    AppLogger::exception($e);
    http_response_code(500);
    if (Config::isDev()) {
        echo '<pre>' . htmlspecialchars((string) $e) . '</pre>';
    } else {
        echo '<h2>Error interno</h2><p>Ocurrió un problema. Intenta de nuevo más tarde.</p>';
    }
    exit;
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
    AppLogger::error($errstr, ['file' => $errfile . ':' . $errline, 'errno' => $errno]);
    // En producción no mostrar errores PHP
    if (Config::isDev()) {
        return false; // Dejar que el handler default de PHP lo muestre
    }
    return true; // Suprimir en producción
});

// Iniciar sesión segura
Session::start();

// Headers de seguridad
Security::applySecurityHeaders();

// Parsear la URL
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base   = Config::baseUrl();
$path   = $base ? preg_replace('#^' . preg_quote($base, '#') . '#', '', $uri) : $uri;
$path   = '/' . trim($path ?: '/', '/');
$method = strtoupper($_SERVER['REQUEST_METHOD']);

// Despachar ruta
Router::dispatch($path, $method);
