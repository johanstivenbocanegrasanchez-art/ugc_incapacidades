<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Session;
use Core\Security;
use Core\Validator;
use App\Models\SolicitudModel;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();
        $user  = $this->user();
        $model = new SolicitudModel();
        $tipos = TIPOS_SOLICITUD;

        switch ($user['rol']) {
            case ROL_ADMIN:
                $stats = $model->contarPorEstado();
                $todas = $model->getAll();
                $filtros = ['estado' => '', 'tipo' => ''];
                $this->render('admin/dashboard', compact('user', 'stats', 'todas', 'tipos', 'filtros'));
                break;

            case ROL_RRHH:
                $pendientes = $model->getPendientesRRHH();
                $todas      = $model->getAll();
                $this->render('rrhh/dashboard', compact('user', 'pendientes', 'todas', 'tipos'));
                break;

            case ROL_JEFE:
                $pendientes     = $model->getPendientesJefe($user['cedula']);
                $misSolicitudes = $model->getByEmpleado($user['cedula']);
                $gestionadas    = $model->getGestionadasByJefe($user['cedula']);
                $this->render('jefe/dashboard', compact('user', 'pendientes', 'misSolicitudes', 'gestionadas', 'tipos'));
                break;

            default:
                $solicitudes = $model->getByEmpleado($user['cedula']);
                $this->render('empleado/dashboard', compact('user', 'solicitudes', 'tipos'));
        }
    }

    public function listar(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_RRHH, ROL_JEFE]);
        $user  = $this->user();
        $model = new SolicitudModel();

        $estado = Security::sanitizeString($_GET['estado'] ?? '');
        $tipo   = Security::sanitizeString($_GET['tipo'] ?? '');

        // Validar filtros contra whitelist
        $estadosValidos = [ESTADO_PENDIENTE_JEFE, ESTADO_APROBADO_JEFE, ESTADO_RECHAZADO_JEFE, ESTADO_APROBADO_RRHH, ESTADO_RECHAZADO_RRHH];
        if ($estado !== '' && !in_array($estado, $estadosValidos, true)) {
            $estado = '';
        }
        if ($tipo !== '' && !in_array($tipo, array_keys(TIPOS_SOLICITUD), true)) {
            $tipo = '';
        }

        $filtros = [
            'estado' => $estado,
            'tipo'   => $tipo,
        ];

        $todas = $model->getAll($filtros);
        $tipos = TIPOS_SOLICITUD;
        $stats = $model->contarPorEstado();

        $this->render('admin/dashboard', compact('user', 'todas', 'tipos', 'stats', 'filtros'));
    }
}
