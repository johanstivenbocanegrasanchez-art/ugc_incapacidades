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
            'ruta_archivo'      => null,
        ];

        // Procesar archivo PDF si se ha subido
        $rutaArchivo = $this->procesarArchivoPDF($user['cedula']);
        if ($rutaArchivo !== false) {
            $data['ruta_archivo'] = $rutaArchivo;
        }

        $ok = (new SolicitudModel())->crear($data);

        if ($ok) {
            Flash::success('Solicitud creada correctamente.');
        } else {
            Flash::error('Error al crear la solicitud.');
        }

        $this->redirect('/dashboard');
    }

    private function procesarArchivoPDF(string $nitEmpleado): string|false
    {
        if (!isset($_FILES['documento_pdf']) || $_FILES['documento_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // No hay archivo, es opcional
        }

        $archivo = $_FILES['documento_pdf'];

        // Validar errores de subida
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            Flash::error('Error al subir el archivo: código ' . $archivo['error']);
            return false;
        }

        // Validar tipo MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);

        $tiposPermitidos = ['application/pdf', 'application/x-pdf'];
        if (!in_array($mimeType, $tiposPermitidos, true)) {
            Flash::error('El archivo debe ser un PDF válido.');
            return false;
        }

        // Validar extensión
        $extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            Flash::error('La extensión del archivo debe ser .pdf');
            return false;
        }

        // Validar tamaño (5MB = 5 * 1024 * 1024 bytes)
        $maxSize = 5 * 1024 * 1024;
        if ($archivo['size'] > $maxSize) {
            Flash::error('El archivo excede el tamaño máximo de 5MB.');
            return false;
        }

        // Crear directorio de uploads si no existe
        $uploadDir = __DIR__ . '/../../public/uploads/solicitudes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generar nombre único seguro
        $nombreBase = preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($archivo['name'], PATHINFO_FILENAME));
        $nombreUnico = sprintf(
            '%s_%s_%s_%s.pdf',
            $nitEmpleado,
            date('Ymd'),
            uniqid('', true),
            substr($nombreBase, 0, 30)
        );

        $rutaDestino = $uploadDir . $nombreUnico;

        // Mover archivo
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            Flash::error('Error al guardar el archivo.');
            return false;
        }

        // Retornar ruta relativa para almacenar en BD
        return 'uploads/solicitudes/' . $nombreUnico;
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
