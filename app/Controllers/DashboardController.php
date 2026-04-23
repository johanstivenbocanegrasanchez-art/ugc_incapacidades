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
        $q = trim(Security::sanitizeString($_GET['q'] ?? ''));

        switch ($user['rol']) {
            case ROL_ADMIN:
                $todas = $model->getAll();

                if ($q !== '') {
                    $todas = array_values(array_filter($todas, fn($s) => $this->coincideBusqueda($s, $q)));
                }

                $stats = [
                    'PENDIENTE_JEFE' => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'PENDIENTE_JEFE')),
                    'APROBADO_JEFE'  => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'APROBADO_JEFE')),
                    'RECHAZADO_JEFE' => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'RECHAZADO_JEFE')),
                    'APROBADO_RRHH'  => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'APROBADO_RRHH')),
                    'RECHAZADO_RRHH' => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'RECHAZADO_RRHH')),
                    'TOTAL'          => count($todas),
                ];

                $filtros = ['estado' => '', 'tipo' => ''];
                $this->render('admin/dashboard', compact('user', 'stats', 'todas', 'tipos', 'filtros', 'q'));
                break;

            case ROL_RRHH:
                $pendientes = $model->getPendientesRRHH();
                $todas      = $model->getAll();

                if ($q !== '') {
                    $pendientes = array_values(array_filter($pendientes, fn($s) => $this->coincideBusqueda($s, $q)));
                    $todas      = array_values(array_filter($todas, fn($s) => $this->coincideBusqueda($s, $q)));
                }

                $stats = [
                    'pendientes'   => count($pendientes),
                    'aprobadas'    => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'APROBADO_RRHH')),
                    'rechazadas'   => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'RECHAZADO_RRHH')),
                    'historico'    => count($todas),
                    'revisionJefe' => count(array_filter($todas, fn($s) => ($s['ESTADO'] ?? '') === 'PENDIENTE_JEFE')),
                ];

                $this->render('rrhh/dashboard', compact('user', 'pendientes', 'todas', 'tipos', 'q', 'stats'));
                break;

            case ROL_JEFE:
                $pendientes     = $model->getPendientesJefe($user['cedula']);
                $misSolicitudes = $model->getByEmpleado($user['cedula']);
                $gestionadas    = $model->getGestionadasByJefe($user['cedula']);

                if ($q !== '') {
                    $pendientes     = array_values(array_filter($pendientes, fn($s) => $this->coincideBusqueda($s, $q)));
                    $misSolicitudes = array_values(array_filter($misSolicitudes, fn($s) => $this->coincideBusqueda($s, $q)));
                    $gestionadas    = array_values(array_filter($gestionadas, fn($s) => $this->coincideBusqueda($s, $q)));
                }

                $stats = [
                    'pendientes'     => count($pendientes),
                    'aprobadas'      => count(array_filter(
                        $gestionadas,
                        fn($s) => in_array(($s['ESTADO'] ?? ''), ['APROBADO_JEFE', 'APROBADO_RRHH', 'RECHAZADO_RRHH'], true)
                    )),
                    'rechazadas'     => count(array_filter($gestionadas, fn($s) => ($s['ESTADO'] ?? '') === 'RECHAZADO_JEFE')),
                    'misSolicitudes' => count($misSolicitudes),
                    'gestionadas'    => count($gestionadas),
                ];

                $this->render('jefe/dashboard', compact('user', 'pendientes', 'misSolicitudes', 'gestionadas', 'tipos', 'q', 'stats'));
                break;

            default:
                $solicitudes = $model->getByEmpleado($user['cedula']);

                if ($q !== '') {
                    $solicitudes = array_values(array_filter($solicitudes, fn($s) => $this->coincideBusqueda($s, $q)));
                }

                $this->render('empleado/dashboard', compact('user', 'solicitudes', 'tipos', 'q'));
        }
    }

    public function listar(): void
    {
        $this->requireRole([ROL_ADMIN, ROL_RRHH, ROL_JEFE]);
        $user  = $this->user();
        $model = new SolicitudModel();

        $estado = Security::sanitizeString($_GET['estado'] ?? '');
        $tipo   = Security::sanitizeString($_GET['tipo'] ?? '');

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
        $aprobadas      = array_values(array_filter(
            $gestionadas,
            fn($s) => in_array(($s['ESTADO'] ?? ''), ['APROBADO_JEFE', 'APROBADO_RRHH', 'RECHAZADO_RRHH'], true)
        ));
        $rechazadas     = array_values(array_filter(
            $gestionadas,
            fn($s) => ($s['ESTADO'] ?? '') === 'RECHAZADO_JEFE'
        ));

        switch ($tipo) {
            case 'pendientes':
                $titulo = 'Solicitudes pendientes de aprobación';
                $subtitulo = 'Consulta detallada de las solicitudes que están esperando tu gestión';
                $nombreFiltro = 'Pendientes de aprobación';
                $solicitudes = $pendientes;
                break;

            case 'aprobadas':
                $titulo = 'Solicitudes aprobadas por jefe';
                $subtitulo = 'Consulta detallada de las solicitudes que aprobaste como jefe inmediato';
                $nombreFiltro = 'Aprobadas por jefe';
                $solicitudes = $aprobadas;
                break;

            case 'rechazadas':
                $titulo = 'Solicitudes rechazadas por jefe';
                $subtitulo = 'Consulta detallada de las solicitudes que rechazaste como jefe inmediato';
                $nombreFiltro = 'Rechazadas por jefe';
                $solicitudes = $rechazadas;
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
        $aprobadas = array_values(array_filter(
            $todas,
            fn($s) => ($s['ESTADO'] ?? '') === 'APROBADO_RRHH'
        ));
        $rechazadas = array_values(array_filter(
            $todas,
            fn($s) => ($s['ESTADO'] ?? '') === 'RECHAZADO_RRHH'
        ));

        switch ($tipo) {
            case 'pendientes':
                $titulo = 'Pendientes de RRHH';
                $subtitulo = 'Solicitudes aprobadas por jefe que están pendientes de aprobación final';
                $nombreFiltro = 'Pendientes RRHH';
                $solicitudes = $pendientes;
                break;

            case 'aprobadas':
                $titulo = 'Solicitudes aprobadas por Talento Humano';
                $subtitulo = 'Consulta detallada de las solicitudes aprobadas en la revision final';
                $nombreFiltro = 'Aprobadas RRHH';
                $solicitudes = $aprobadas;
                break;

            case 'rechazadas':
                $titulo = 'Solicitudes rechazadas por Talento Humano';
                $subtitulo = 'Consulta detallada de las solicitudes rechazadas en la revision final';
                $nombreFiltro = 'Rechazadas RRHH';
                $solicitudes = $rechazadas;
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

    private function coincideBusqueda(array $s, string $q): bool
    {
        $normalizar = function (string $texto): string {
            $texto = mb_strtolower(trim($texto), 'UTF-8');

            $reemplazos = [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
                'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
                'ñ' => 'n',
            ];

            $texto = strtr($texto, $reemplazos);
            $texto = str_replace(['_', '-', '.', ',', ';', ':', '/', '\\'], ' ', $texto);
            $texto = preg_replace('/\s+/', ' ', $texto);

            return $texto;
        };

        $qNormalizado = $normalizar($q);

        $texto = implode(' ', [
            $s['TIPO_SOLICITUD'] ?? '',
            $s['ESTADO'] ?? '',
            $s['NIT_EMPLEADO'] ?? '',
            $s['OBSERVACIONES'] ?? '',
            $s['NOMBRE_EMPLEADO'] ?? '',
            $s['JEFE_INMEDIATO'] ?? '',
        ]);

        $textoNormalizado = $normalizar($texto);

        return $qNormalizado === '' || mb_strpos($textoNormalizado, $qNormalizado) !== false;
    }
}
