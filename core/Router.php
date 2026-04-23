<?php

declare(strict_types=1);

namespace Core;

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\SolicitudController;
use App\Controllers\NotificacionController;
use App\Controllers\AdminController;
use App\Exportar\Admin\ExportController;
use App\Exportar\Jefe\ExportControllerJe;
use App\Exportar\Rrhh\ExportControllerRrhh;

final class Router
{
    private const ROUTES = [
        'GET' => [
            '/'                              => [AuthController::class, 'loginForm'],
            '/login'                         => [AuthController::class, 'loginForm'],
            '/seleccionar-rol'               => [AuthController::class, 'seleccionarRolForm'],
            '/dashboard'                     => [DashboardController::class, 'index'],
            '/solicitud/crear'               => [SolicitudController::class, 'crearForm'],
            '/solicitudes'                   => [DashboardController::class, 'listar'],
            '/jefe/solicitudes'              => [DashboardController::class, 'solicitudesJefe'],
            '/rrhh/solicitudes'              => [DashboardController::class, 'solicitudesRrhh'],
            '/admin/solicitudes'             => [DashboardController::class, 'solicitudesAdmin'],
            '/empleado/solicitudes'          => [DashboardController::class, 'solicitudesEmpleado'],
            '/api/notificaciones/contador'   => [NotificacionController::class, 'contador'],
            '/api/notificaciones'            => [NotificacionController::class, 'listar'],
            '/exportar/todas/excel'          => [ExportController::class, 'todasExcel'],
            '/exportar/jefe/excel'           => [ExportControllerJe::class, 'todasExcelJefe'],
            '/exportar/rrhh/excel'           => [ExportControllerRrhh::class, 'todasExcelRrhh'],
            // Rutas de Administración
            '/admin/empleados'               => [AdminController::class, 'empleados'],
        ],
        'POST' => [
            '/login'                            => [AuthController::class, 'loginPost'],
            '/logout'                           => [AuthController::class, 'logout'],
            '/seleccionar-rol'                  => [AuthController::class, 'seleccionarRolPost'],
            '/solicitud/crear'                  => [SolicitudController::class, 'crearPost'],
            '/api/notificaciones/leer-todas'    => [NotificacionController::class, 'marcarTodasLeidas'],
        ],
    ];

    private const PARAM_ROUTES = [
        'GET' => [
            '#^/solicitud/(\d+)/ver$#'    => [SolicitudController::class, 'ver'],
            '#^/solicitud/(\d+)/editar$#' => [SolicitudController::class, 'editarForm'],
            '#^/archivo/(\d+)$#'          => [SolicitudController::class, 'servirArchivo'],
            // Rutas con parámetros de Administración
            '#^/admin/empleados/([^/]+)$#' => [AdminController::class, 'empleadoDetalle'],
        ],
        'POST' => [
            '#^/solicitud/(\d+)/editar$#'         => [SolicitudController::class, 'editarPost'],
            '#^/solicitud/(\d+)/eliminar$#'       => [SolicitudController::class, 'eliminar'],
            '#^/solicitud/(\d+)/jefe$#'           => [SolicitudController::class, 'gestionJefePost'],
            '#^/solicitud/(\d+)/rrhh$#'           => [SolicitudController::class, 'gestionRrhhPost'],
            '#^/api/notificaciones/(\d+)/leer$#' => [NotificacionController::class, 'marcarLeida'],
            // Gestión de roles admin
            '#^/admin/empleados/([^/]+)/hacer-admin$#' => [AdminController::class, 'hacerAdmin'],
            '#^/admin/empleados/([^/]+)/quitar-admin$#' => [AdminController::class, 'quitarAdmin'],
        ],
    ];

    public static function dispatch(string $path, string $method): void
    {
        $method = strtoupper($method);

        if (isset(self::ROUTES[$method][$path])) {
            [$controller, $action] = self::ROUTES[$method][$path];
            (new $controller())->$action();
            return;
        }

        foreach (self::PARAM_ROUTES[$method] ?? [] as $pattern => [$controller, $action]) {
            if (preg_match($pattern, $path, $matches)) {
                (new $controller())->$action($matches[1]);
                return;
            }
        }

        http_response_code(404);
        self::renderError(404, 'Página no encontrada');
    }

    private static function renderError(int $code, string $message): void
    {
        $baseUrl = Config::baseUrl();
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width,initial-scale=1.0">
            <title>{$code} - Error</title>
            <style>
                body{font-family:'Inter',Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f0f2f0;color:#1c2b1e}
                .error-box{text-align:center;padding:60px 24px}
                .error-box h2{font-size:48px;color:#0a5a1f;margin:0 0 8px}
                .error-box p{color:#5a6b5b;font-size:16px;margin:0 0 24px}
                a{color:#128b3b;text-decoration:none;font-weight:600}a:hover{text-decoration:underline}
            </style>
        </head>
        <body>
            <div class="error-box">
                <h2>{$code}</h2>
                <p>{$message}</p>
                <a href="{$baseUrl}/login">Volver al inicio</a>
            </div>
        </body>
        </html>
        HTML;
    }
}