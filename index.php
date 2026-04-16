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

// Cargar configuración (incluye .env)
require_once __DIR__ . '/config/config.php';

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
