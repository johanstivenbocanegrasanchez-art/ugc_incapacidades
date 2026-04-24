<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Security;
use App\Services\NotificacionService;

final class NotificacionController extends Controller
{
    private NotificacionService $service;

    public function __construct()
    {
        $this->service = new NotificacionService();
    }

    /**
     * Obtener contador de notificaciones no leídas (para el badge)
     * GET /api/notificaciones/contador
     */
    public function contador(): void
    {
        $this->requireLogin();
        $cedula = $this->user()['cedula'];
        Session::close();
        $this->jsonResponse(['contador' => $this->service->contarNoLeidas($cedula)]);
    }

    /**
     * Obtener lista de notificaciones no leídas
     * GET /api/notificaciones
     */
    public function listar(): void
    {
        $this->requireLogin();
        $cedula = $this->user()['cedula'];
        Session::close();
        $notificaciones = $this->service->getNoLeidas($cedula);
        $this->jsonResponse(['notificaciones' => $notificaciones]);
    }

    /**
     * Marcar una notificación como leída
     * POST /api/notificaciones/:id/leer
     */
    public function marcarLeida(string $id): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $cedula = $this->user()['cedula'];
        Session::close();
        $ok = $this->service->marcarLeida((int) $id, $cedula);
        $this->jsonResponse(['success' => $ok]);
    }

    /**
     * Marcar todas las notificaciones como leídas
     * POST /api/notificaciones/leer-todas
     */
    public function marcarTodasLeidas(): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $cedula = $this->user()['cedula'];
        Session::close();
        $ok = $this->service->marcarTodasLeidas($cedula);
        $this->jsonResponse(['success' => $ok]);
    }

    /**
     * Enviar respuesta JSON
     */
   private function jsonResponse(array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo json_encode($data);
    exit;
}
}
