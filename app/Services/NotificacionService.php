<?php

declare(strict_types=1);

namespace App\Services;

use Core\Config;
use App\Models\NotificacionModel;
use App\Models\EmpleadoModel;
use Core\AppLogger;

final class NotificacionService
{
    private NotificacionModel $model;
    private EmpleadoModel $empleadoModel;

    public function __construct()
    {
        $this->model = new NotificacionModel();
        $this->empleadoModel = new EmpleadoModel();
    }

    /**
     * Notificar al jefe cuando un empleado crea una solicitud
     */
    public function notificarNuevaSolicitud(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud): void
    {
        $empleado = $this->empleadoModel->getByNit($nitEmpleado);
        $nombreEmpleado = $empleado['NOMBRE_COMPLETO'] ?? $nitEmpleado;
        $tipoLabel = $this->getTipoLabel($tipoSolicitud);

        $mensaje = "{$nombreEmpleado} solicitó {$tipoLabel} y requiere tu aprobación";

        $this->model->crear($nitJefe, 'NUEVA_SOLICITUD', $mensaje, $idSolicitud);
    }

    /**
     * Notificar al empleado cuando su jefe aprueba la solicitud
     */
    public function notificarAprobacionJefe(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud): void
    {
        $jefe = $this->empleadoModel->getByNit($nitJefe);
        $nombreJefe = $jefe['NOMBRE_COMPLETO'] ?? 'Tu jefe';
        $tipoLabel = $this->getTipoLabel($tipoSolicitud);

        $mensaje = "{$nombreJefe} aprobó tu solicitud de {$tipoLabel}";

        $this->model->crear($nitEmpleado, 'SOLICITUD_APROBADA_JEFE', $mensaje, $idSolicitud);
    }

    /**
     * Notificar al empleado cuando su jefe rechaza la solicitud
     */
    public function notificarRechazoJefe(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud, ?string $observacion = null): void
    {
        $jefe = $this->empleadoModel->getByNit($nitJefe);
        $nombreJefe = $jefe['NOMBRE_COMPLETO'] ?? 'Tu jefe';
        $tipoLabel = $this->getTipoLabel($tipoSolicitud);

        $mensaje = "{$nombreJefe} rechazó tu solicitud de {$tipoLabel}";
        if (!empty($observacion)) {
            $mensaje .= ". Observación: " . substr($observacion, 0, 100);
        }

        $this->model->crear($nitEmpleado, 'SOLICITUD_RECHAZADA_JEFE', $mensaje, $idSolicitud);
    }

    /**
     * Notificar a RRHH cuando un jefe aprueba una solicitud
     */
    public function notificarRevisionRRHH(int $idSolicitud, string $nitEmpleado, string $tipoSolicitud): void
    {
        $empleado = $this->empleadoModel->getByNit($nitEmpleado);
        $nombreEmpleado = $empleado['NOMBRE_COMPLETO'] ?? $nitEmpleado;
        $tipoLabel = $this->getTipoLabel($tipoSolicitud);

        $mensaje = "Nueva solicitud de {$tipoLabel} de {$nombreEmpleado} pendiente de revisión";

        // Notificar a todos los usuarios de RRHH
        $usuariosRRHH = $this->getUsuariosRRHH();

        foreach ($usuariosRRHH as $nitRRHH) {
            $this->model->crear($nitRRHH, 'REVISION_RRHH', $mensaje, $idSolicitud);
        }
    }

    /**
     * Notificar al empleado y jefe cuando RRHH aprueba la solicitud
     */
    public function notificarAprobacionRRHH(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud): void
    {
        $tipoLabel = $this->getTipoLabel($tipoSolicitud);

        // Notificar al empleado
        $mensajeEmpleado = "Talento Humano aprobó tu solicitud de {$tipoLabel}. ¡Proceso completado!";
        $this->model->crear($nitEmpleado, 'SOLICITUD_APROBADA_RRHH', $mensajeEmpleado, $idSolicitud);

        // Notificar al jefe
        $mensajeJefe = "La solicitud de {$tipoLabel} de tu colaborador fue aprobada por Talento Humano";
        $this->model->crear($nitJefe, 'SOLICITUD_APROBADA_RRHH', $mensajeJefe, $idSolicitud);
    }

    /**
     * Notificar al empleado y jefe cuando RRHH rechaza la solicitud
     */
    public function notificarRechazoRRHH(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud, ?string $observacion = null): void
    {
        $tipoLabel = $this->getTipoLabel($tipoSolicitud);

        // Notificar al empleado
        $mensajeEmpleado = "Talento Humano rechazó tu solicitud de {$tipoLabel}";
        if (!empty($observacion)) {
            $mensajeEmpleado .= ". Observación: " . substr($observacion, 0, 100);
        }
        $this->model->crear($nitEmpleado, 'SOLICITUD_RECHAZADA_RRHH', $mensajeEmpleado, $idSolicitud);

        // Notificar al jefe
        $mensajeJefe = "La solicitud de {$tipoLabel} de tu colaborador fue rechazada por Talento Humano";
        $this->model->crear($nitJefe, 'SOLICITUD_RECHAZADA_RRHH', $mensajeJefe, $idSolicitud);
    }

    /**
     * Notificar cuando una solicitud es editada
     */
    public function notificarSolicitudEditada(int $idSolicitud, string $nitEmpleado, string $nitJefe, string $tipoSolicitud): void
    {
        $empleado = $this->empleadoModel->getByNit($nitEmpleado);
        $nombreEmpleado = $empleado['NOMBRE_COMPLETO'] ?? $nitEmpleado;
        $tipoLabel = $this->getTipoLabel($tipoSolicitud);

        $mensaje = "{$nombreEmpleado} modificó su solicitud de {$tipoLabel}";

        $this->model->crear($nitJefe, 'SOLICITUD_EDITADA', $mensaje, $idSolicitud);
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function contarNoLeidas(string $nit): int
    {
        return $this->model->contarNoLeidas($nit);
    }

    /**
     * Obtener notificaciones no leídas
     */
    public function getNoLeidas(string $nit): array
    {
        return $this->model->getNoLeidas($nit);
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarLeida(int $idNotificacion, string $nit): bool
    {
        return $this->model->marcarLeida($idNotificacion, $nit);
    }

    /**
     * Marcar todas como leídas
     */
    public function marcarTodasLeidas(string $nit): bool
    {
        return $this->model->marcarTodasLeidas($nit);
    }

    /**
     * Obtener label legible del tipo de solicitud
     */
    private function getTipoLabel(string $tipo): string
    {
        return TIPOS_SOLICITUD[$tipo] ?? $tipo;
    }

    /**
     * Obtener NITs de usuarios RRHH
     */
    private function getUsuariosRRHH(): array
    {
        $rrhh = [];

        // Buscar todos los empleados en centros de costo de RRHH en Oracle
        $empleadosRRHH = $this->empleadoModel->getPorCentrosCosto(CC_RRHH);
        AppLogger::debug('Empleados RRHH desde Oracle', ['count' => count($empleadosRRHH)]);

        // Asegurar que sea array (por si hay error y devuelve false/null)
        if (!is_array($empleadosRRHH)) {
            AppLogger::warning('getPorCentrosCosto no devolvió array', ['result_type' => gettype($empleadosRRHH)]);
            $empleadosRRHH = [];
        }

        foreach ($empleadosRRHH as $emp) {
            $rrhh[] = $emp['NIT'];
        }

        // En modo desarrollo, SIEMPRE agregar usuarios de prueba que sean RRHH/Admin
        // para que puedan recibir notificaciones durante las pruebas
        AppLogger::debug('isDev check', ['isDev' => Config::isDev(), 'rrhh_count' => count($rrhh)]);

        if (Config::isDev()) {
            AppLogger::debug('Agregando usuarios de prueba RRHH/Admin');
            foreach (USUARIOS_PRUEBA as $cedula => $datos) {
                // Forzar string para evitar TypeError (las cédulas numéricas se convierten a int)
                $cedulaStr = (string) $cedula;
                if (($datos['rol'] ?? '') === ROL_RRHH) {
                    $rrhh[] = $cedulaStr;
                    AppLogger::debug('Agregado RRHH de prueba', ['cedula' => $cedulaStr]);
                }
                // También incluir admins como RRHH (tienen todos los permisos)
                if (($datos['rol'] ?? '') === ROL_ADMIN) {
                    $rrhh[] = $cedulaStr;
                    AppLogger::debug('Agregado ADMIN de prueba', ['cedula' => $cedulaStr]);
                }
            }
        }

        AppLogger::debug('Total RRHH a notificar', ['count' => count($rrhh)]);
        return array_unique($rrhh);
    }
}
