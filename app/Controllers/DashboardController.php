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

    public function solicitudesJefe(): void
{
    $this->requireRole([ROL_JEFE]);

    $user  = $this->user();
    $model = new SolicitudModel();
    $tipos = TIPOS_SOLICITUD;

    $tipo = Security::sanitizeString($_GET['tipo'] ?? 'pendientes');

    $pendientes     = $model->getPendientesJefe($user['cedula']);
    $misSolicitudes = $model->getByEmpleado($user['cedula']);
    $gestionadas    = $model->getGestionadasByJefe($user['cedula']);

    switch ($tipo) {
        case 'pendientes':
            $titulo = 'Solicitudes pendientes de aprobación';
            $subtitulo = 'Consulta detallada de las solicitudes que están esperando tu gestión';
            $nombreFiltro = 'Pendientes de aprobación';
            $solicitudes = $pendientes;
            break;

        case 'mis_solicitudes':
            $titulo = 'Mis solicitudes personales';
            $subtitulo = 'Consulta detallada de tus solicitudes registradas';
            $nombreFiltro = 'Mis solicitudes';
            $solicitudes = $misSolicitudes;
            break;

        case 'gestionadas':
            $titulo = 'Historial de solicitudes gestionadas';
            $subtitulo = 'Consulta detallada del historial que ya fue gestionado';
            $nombreFiltro = 'Historial gestionado';
            $solicitudes = $gestionadas;
            break;

        default:
            $titulo = 'Solicitudes';
            $subtitulo = 'Consulta detallada de solicitudes';
            $nombreFiltro = 'General';
            $solicitudes = [];
            break;
    }

    $this->render('jefe/solicitudes', compact(
        'user',
        'tipos',
        'titulo',
        'subtitulo',
        'nombreFiltro',
        'solicitudes',
        'tipo'
    ));
}
public function solicitudesRrhh(): void
{
    $this->requireRole([ROL_RRHH]);

    $user  = $this->user();
    $model = new SolicitudModel();
    $tipos = TIPOS_SOLICITUD;

    $tipo = Security::sanitizeString($_GET['tipo'] ?? 'pendientes');

    $pendientes = $model->getPendientesRRHH();
    $todas      = $model->getAll();
    $enRevisionJefe = array_values(array_filter(
        $todas,
        fn($s) => ($s['ESTADO'] ?? '') === 'PENDIENTE_JEFE'
    ));

    switch ($tipo) {
        case 'pendientes':
            $titulo = 'Pendientes de RRHH';
            $subtitulo = 'Solicitudes aprobadas por jefe que están pendientes de aprobación final';
            $nombreFiltro = 'Pendientes RRHH';
            $solicitudes = $pendientes;
            break;

        case 'historico':
            $titulo = 'Historial completo';
            $subtitulo = 'Consulta completa de solicitudes registradas en el sistema';
            $nombreFiltro = 'Histórico completo';
            $solicitudes = $todas;
            break;

        case 'revision_jefe':
            $titulo = 'Solicitudes en revisión de jefe';
            $subtitulo = 'Solicitudes que aún están siendo revisadas por el jefe inmediato';
            $nombreFiltro = 'En revisión jefe';
            $solicitudes = $enRevisionJefe;
            break;

        default:
            $titulo = 'Solicitudes RRHH';
            $subtitulo = 'Consulta de solicitudes';
            $nombreFiltro = 'General';
            $solicitudes = [];
            break;
    }

    $this->render('rrhh/solicitudes', compact(
        'user',
        'tipos',
        'titulo',
        'subtitulo',
        'nombreFiltro',
        'solicitudes',
        'tipo'
    ));
}
public function solicitudesAdmin(): void
{
    $this->requireRole([ROL_ADMIN]);

    $user  = $this->user();
    $model = new SolicitudModel();
    $tipos = TIPOS_SOLICITUD;

    $estado = Security::sanitizeString($_GET['estado'] ?? 'total');

    $labels = [
        'PENDIENTE_JEFE' => 'Pendiente Jefe',
        'APROBADO_JEFE'  => 'Aprobado Jefe',
        'RECHAZADO_JEFE' => 'Rechazado Jefe',
        'APROBADO_RRHH'  => 'Aprobado RRHH',
        'RECHAZADO_RRHH' => 'Rechazado RRHH',
    ];

    $filtros = [
        'estado' => '',
        'tipo'   => '',
    ];

    if ($estado !== 'total' && isset($labels[$estado])) {
        $filtros['estado'] = $estado;
        $solicitudes = $model->getAll($filtros);
        $titulo = 'Solicitudes: ' . $labels[$estado];
        $subtitulo = 'Consulta detallada de solicitudes en estado ' . $labels[$estado];
        $nombreFiltro = $labels[$estado];
    } else {
        $solicitudes = $model->getAll();
        $titulo = 'Todas las solicitudes';
        $subtitulo = 'Consulta general de todas las solicitudes registradas en el sistema';
        $nombreFiltro = 'Total solicitudes';
    }

    $this->render('admin/solicitudes', compact(
        'user',
        'tipos',
        'titulo',
        'subtitulo',
        'nombreFiltro',
        'solicitudes',
        'estado',
        'labels'
    ));
}
public function solicitudesEmpleado(): void
{
    $this->requireLogin();

    $user  = $this->user();
    $model = new SolicitudModel();
    $tipos = TIPOS_SOLICITUD;

    $tipo = Security::sanitizeString($_GET['tipo'] ?? 'total');

    $todas = $model->getByEmpleado($user['cedula']);

    $pendientes = array_values(array_filter(
        $todas,
        fn($s) => ($s['ESTADO'] ?? '') === 'PENDIENTE_JEFE'
    ));

    $aprobadas = array_values(array_filter(
        $todas,
        fn($s) => in_array(($s['ESTADO'] ?? ''), ['APROBADO_JEFE', 'APROBADO_RRHH'], true)
    ));

    $rechazadas = array_values(array_filter(
        $todas,
        fn($s) => in_array(($s['ESTADO'] ?? ''), ['RECHAZADO_JEFE', 'RECHAZADO_RRHH'], true)
    ));

    switch ($tipo) {
        case 'pendientes':
            $titulo = 'Solicitudes pendientes';
            $subtitulo = 'Consulta detallada de tus solicitudes pendientes';
            $nombreFiltro = 'Pendientes';
            $solicitudes = $pendientes;
            break;

        case 'aprobadas':
            $titulo = 'Solicitudes aprobadas';
            $subtitulo = 'Consulta detallada de tus solicitudes aprobadas';
            $nombreFiltro = 'Aprobadas';
            $solicitudes = $aprobadas;
            break;

        case 'rechazadas':
            $titulo = 'Solicitudes rechazadas';
            $subtitulo = 'Consulta detallada de tus solicitudes rechazadas';
            $nombreFiltro = 'Rechazadas';
            $solicitudes = $rechazadas;
            break;

        case 'total':
        default:
            $titulo = 'Todas mis solicitudes';
            $subtitulo = 'Consulta general de todas tus solicitudes registradas';
            $nombreFiltro = 'Total';
            $solicitudes = $todas;
            break;
    }

    $this->render('empleado/solicitudes', compact(
        'user',
        'tipos',
        'titulo',
        'subtitulo',
        'nombreFiltro',
        'solicitudes',
        'tipo'
    ));
}
}