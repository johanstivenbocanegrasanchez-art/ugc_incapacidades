<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Flash;
use Core\Session;
use Core\Security;
use App\Models\EmpleadoModel;
use App\Models\SolicitudModel;

final class SolicitudController extends Controller
{
    public function crearForm(): void
    {
        $this->requireLogin();
        $user = $this->user();

        $empleadoModel = new EmpleadoModel();
        $esAprendiz = $empleadoModel->esAprendiz($user['centro_costo'] ?? '');

        if (!$esAprendiz && empty($user['nit_jefe'])) {
            $this->render('empleado/sin_jefe', compact('user'));
            return;
        }

        $jefes = $esAprendiz ? $empleadoModel->getTodosLosJefes() : [];
        $hoy   = date('Y-m-d');
        $tipos = TIPOS_SOLICITUD;

        $this->render('empleado/form_crear', compact('user', 'jefes', 'esAprendiz', 'hoy', 'tipos'));
    }

    public function crearPost(): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $user = $this->user();
        $empleadoModel = new EmpleadoModel();
        $esAprendiz = $empleadoModel->esAprendiz($user['centro_costo'] ?? '');

        $nitJefe = $esAprendiz
            ? Security::sanitizeString($_POST['nit_jefe_seleccionado'] ?? '')
            : ($user['nit_jefe'] ?? '');

        if (!$nitJefe) {
            Flash::error('Debes seleccionar un jefe.');
            $this->redirect('/solicitud/crear');
        }

        $data = [
            'nit_empleado'      => $user['cedula'],
            'nit_jefe'          => $nitJefe,
            'tipo_solicitud'    => Security::sanitizeString($_POST['tipo_solicitud'] ?? ''),
            'fecha_inicio'      => Security::sanitizeString($_POST['fecha_inicio'] ?? ''),
            'fecha_fin'         => Security::sanitizeString($_POST['fecha_fin'] ?? ''),
            'duracion_horas'    => Security::sanitizeFloat($_POST['duracion_horas'] ?? '0') ?: null,
            'duracion_dias'     => Security::sanitizeFloat($_POST['duracion_dias'] ?? '0') ?: null,
            'observaciones'     => Security::sanitizeString($_POST['observaciones'] ?? ''),
        ];

        $ok = (new SolicitudModel())->crear($data);

        if ($ok) {
            Flash::success('Solicitud creada correctamente.');
        } else {
            Flash::error('Error al crear la solicitud.');
        }

        $this->redirect('/dashboard');
    }

    public function editarForm(string $id): void
    {
        $this->requireLogin();
        $user = $this->user();

        $solicitud = (new SolicitudModel())->getById((int) $id);
        if (!$solicitud || $solicitud['NIT_EMPLEADO'] !== $user['cedula']) {
            http_response_code(403);
            Flash::error('No autorizado.');
            $this->redirect('/dashboard');
        }

        $empleadoModel = new EmpleadoModel();
        $esAprendiz = $empleadoModel->esAprendiz($user['centro_costo'] ?? '');
        $jefes = $esAprendiz ? $empleadoModel->getTodosLosJefes() : [];
        $hoy   = date('Y-m-d');
        $tipos = TIPOS_SOLICITUD;

        $this->render('empleado/form_editar', compact('user', 'jefes', 'esAprendiz', 'hoy', 'tipos', 'solicitud'));
    }

    public function editarPost(string $id): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $user = $this->user();
        $empleadoModel = new EmpleadoModel();
        $esAprendiz = $empleadoModel->esAprendiz($user['centro_costo'] ?? '');

        $nitJefe = $esAprendiz
            ? Security::sanitizeString($_POST['nit_jefe_seleccionado'] ?? '')
            : null;

        $data = [
            'tipo_solicitud'       => Security::sanitizeString($_POST['tipo_solicitud'] ?? ''),
            'fecha_inicio'         => Security::sanitizeString($_POST['fecha_inicio'] ?? ''),
            'fecha_fin'            => Security::sanitizeString($_POST['fecha_fin'] ?? ''),
            'duracion_horas'       => Security::sanitizeFloat($_POST['duracion_horas'] ?? '0') ?: null,
            'duracion_dias'        => Security::sanitizeFloat($_POST['duracion_dias'] ?? '0') ?: null,
            'observaciones'        => Security::sanitizeString($_POST['observaciones'] ?? ''),
            'nit_jefe_actualizado' => $nitJefe,
        ];

        $ok = (new SolicitudModel())->editar((int) $id, $user['cedula'], $data);

        if ($ok) {
            Flash::success('Solicitud actualizada.');
        } else {
            Flash::error('Solo puedes editar solicitudes pendientes.');
        }

        $this->redirect('/dashboard');
    }

    public function eliminar(string $id): void
    {
        $this->requireLogin();
        $this->validateCsrf();

        $user = $this->user();
        $ok = (new SolicitudModel())->eliminar((int) $id, $user['cedula']);

        if ($ok) {
            Flash::success('Solicitud eliminada.');
        } else {
            Flash::error('No se pudo eliminar.');
        }

        $this->redirect('/dashboard');
    }

    public function ver(string $id): void
    {
        $this->requireLogin();
        $user = $this->user();

        $solicitud = (new SolicitudModel())->getById((int) $id);
        if (!$solicitud) {
            http_response_code(404);
            Flash::error('Solicitud no encontrada.');
            $this->redirect('/dashboard');
        }

        $tipos = TIPOS_SOLICITUD;
        $this->render('shared/detalle', compact('user', 'solicitud', 'tipos'));
    }

    public function gestionJefePost(string $id): void
    {
        $this->requireRole([ROL_JEFE, ROL_ADMIN]);
        $this->validateCsrf();

        $user = $this->user();
        $model = new SolicitudModel();
        $obs = Security::sanitizeString($_POST['observacion_jefe'] ?? '');
        $accion = Security::sanitizeString($_POST['accion'] ?? '');

        $ok = ($accion === 'aprobar')
            ? $model->aprobarJefe((int) $id, $user['cedula'], $obs)
            : $model->rechazarJefe((int) $id, $user['cedula'], $obs);

        if ($ok) {
            Flash::success('Gestión guardada.');
        } else {
            Flash::error('No se pudo gestionar.');
        }

        $this->redirect('/dashboard');
    }

    public function gestionRrhhPost(string $id): void
    {
        $this->requireRole([ROL_RRHH, ROL_ADMIN]);
        $this->validateCsrf();

        $user = $this->user();
        $model = new SolicitudModel();
        $obs = Security::sanitizeString($_POST['observacion_rrhh'] ?? '');
        $accion = Security::sanitizeString($_POST['accion'] ?? '');

        $ok = ($accion === 'aprobar')
            ? $model->aprobarRRHH((int) $id, $user['cedula'], $obs)
            : $model->rechazarRRHH((int) $id, $user['cedula'], $obs);

        if ($ok) {
            Flash::success('Gestión RRHH guardada.');
        } else {
            Flash::error('No se pudo gestionar.');
        }

        $this->redirect('/dashboard');
    }
}
