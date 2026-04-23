<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Flash;
use Core\Session;
use Core\Security;
use Core\Validator;
use App\Models\EmpleadoModel;
use App\Models\SolicitudModel;
use App\Services\NotificacionService;

final class SolicitudController extends Controller
{
    public function crearForm(): void
    {
        $this->requireLogin();
        $user = $this->user();

        $empleadoModel = new EmpleadoModel();
        $esAprendiz = $empleadoModel->esAprendiz($user['centro_costo'] ?? '');

        // Si el usuario no tiene jefe asignado en sesión, intentar actualizarlo en tiempo real
        if (!$esAprendiz && empty($user['nit_jefe'])) {
            $jefe = $empleadoModel->getJefeInmediato($user['cedula']);
            
            // Depuración: registrar qué devuelve getJefeInmediato
            error_log("DEBUG: getJefeInmediato para {$user['cedula']} devuelve: " . print_r($jefe, true));
            
            if ($jefe && !empty($jefe['NIT_JEFE'])) {
                // Actualizar la sesión con la información del jefe
                $user['nit_jefe'] = $jefe['NIT_JEFE'];
                $user['nombre_jefe'] = $jefe['NOMBRE_JEFE'];
                \Core\Session::setUser($user);
                error_log("DEBUG: Sesión actualizada con jefe: {$jefe['NIT_JEFE']} - {$jefe['NOMBRE_JEFE']}");
            } else {
                error_log("DEBUG: No se encontró jefe para {$user['cedula']}, mostrando vista sin_jefe");
                $this->render('empleado/sin_jefe', compact('user'));
                return;
            }
        }

        $jefes = $esAprendiz ? $empleadoModel->getTodosLosJefes() : [];

        // En desarrollo: si el usuario es de prueba, mostrar solo jefes de prueba
        if ($esAprendiz && \Core\Config::isDev()) {
            $jefes = [];
            foreach (USUARIOS_PRUEBA as $cedula => $datos) {
                if ($datos['rol'] === ROL_JEFE) {
                    $jefes[] = [
                        'NIT' => $datos['cedula'],
                        'NOMBRE_COMPLETO' => $datos['nombre'],
                        'CENTRO_COSTO' => $datos['centro_costo'],
                        'NIVEL' => $datos['nivel'],
                    ];
                }
            }
        }

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

        $tipoSolicitud = Security::sanitizeString($_POST['tipo_solicitud'] ?? '');
        $fechaInicio   = Security::sanitizeString($_POST['fecha_inicio'] ?? '');
        $fechaFin      = Security::sanitizeString($_POST['fecha_fin'] ?? '');
        $duracionHoras = Security::sanitizeFloat($_POST['duracion_horas'] ?? '0') ?: null;
        $duracionDias  = Security::sanitizeFloat($_POST['duracion_dias'] ?? '0') ?: null;
        $observaciones = Security::sanitizeString($_POST['observaciones'] ?? '');

        // Validación centralizada
        $v = Validator::make()
            ->required($tipoSolicitud, 'tipo_solicitud', 'Selecciona un tipo de solicitud.')
            ->inWhitelist($tipoSolicitud, array_keys(TIPOS_SOLICITUD), 'tipo_solicitud', 'Tipo de solicitud no válido.')
            ->required($fechaInicio, 'fecha_inicio', 'La fecha de inicio es obligatoria.')
            ->date($fechaInicio, 'fecha_inicio')
            ->required($fechaFin, 'fecha_fin', 'La fecha fin es obligatoria.')
            ->date($fechaFin, 'fecha_fin')
            ->dateAfter($fechaInicio, $fechaFin, 'fecha_fin')
            ->range((string) ($duracionHoras ?? 0), 0, 999, 'duracion_horas')
            ->range((string) ($duracionDias ?? 0), 0, 365, 'duracion_dias')
            ->maxLength($observaciones, 2000, 'observaciones');

        if ($v->hasErrors()) {
            Flash::error(reset($v->getErrors()));
            $this->redirect('/solicitud/crear');
        }

        $data = [
            'nit_empleado'      => $user['cedula'],
            'nit_jefe'          => $nitJefe,
            'tipo_solicitud'    => $tipoSolicitud,
            'fecha_inicio'      => $fechaInicio,
            'fecha_fin'         => $fechaFin,
            'duracion_horas'    => $duracionHoras,
            'duracion_dias'     => $duracionDias,
            'observaciones'     => $observaciones,
            'ruta_archivo'      => null,
        ];

        // Procesar archivo PDF (obligatorio)
        $rutaArchivo = $this->procesarArchivoPDF($user['cedula']);
        if ($rutaArchivo === false) {
            $this->redirect('/solicitud/crear');
        }
        $data['ruta_archivo'] = $rutaArchivo;

        $model = new SolicitudModel();
        $ok = $model->crear($data);

        if ($ok) {
            // Obtener el ID de la solicitud recién creada
            $idSolicitud = $this->getUltimaSolicitudId($user['cedula']);

            // Notificar al jefe
            $notificacionService = new NotificacionService();
            $notificacionService->notificarNuevaSolicitud(
                $idSolicitud,
                $user['cedula'],
                $nitJefe,
                $data['tipo_solicitud']
            );

            Flash::success('Solicitud creada correctamente.');
        } else {
            Flash::error('Error al crear la solicitud.');
        }

        $this->redirect('/dashboard');
    }

    private function procesarArchivoPDF(string $nitEmpleado): string|false
    {
        if (!isset($_FILES['documento_pdf']) || $_FILES['documento_pdf']['error'] === UPLOAD_ERR_NO_FILE) {
            Flash::error('El documento PDF es obligatorio.');
            return false;
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

        // Crear directorio de almacenamiento fuera del acceso web directo
        $storageDir = __DIR__ . '/../../storage';
        $uploadDir  = $storageDir . '/solicitudes/';

        // Debug: verificar rutas
        error_log("[PDF Debug] storageDir: {$storageDir}, uploadDir: {$uploadDir}");
        error_log("[PDF Debug] __DIR__: " . __DIR__);

        if (!is_dir($storageDir)) {
            if (!mkdir($storageDir, 0755, true)) {
                error_log("[PDF Debug] Error al crear storageDir: {$storageDir}");
                Flash::error('Error al crear directorio de almacenamiento.');
                return false;
            }
        }

        // Verificar permisos de escritura
        if (!is_writable($storageDir)) {
            error_log("[PDF Debug] storageDir no tiene permisos de escritura: {$storageDir}");
            Flash::error('Error de permisos en directorio de almacenamiento.');
            return false;
        }

        // Proteger directorio con .htaccess (denegar acceso directo por URL)
        $htaccessPath = $storageDir . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "<IfModule mod_authz_core_module>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core_module>\n  Deny from all\n</IfModule>\n");
        }

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("[PDF Debug] Error al crear uploadDir: {$uploadDir}");
                Flash::error('Error al crear subdirectorio de solicitudes.');
                return false;
            }
        }

        // Verificar permisos de escritura en uploadDir
        if (!is_writable($uploadDir)) {
            error_log("[PDF Debug] uploadDir no tiene permisos de escritura: {$uploadDir}");
            Flash::error('Error de permisos en subdirectorio de solicitudes.');
            return false;
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
        error_log("[PDF Debug] Intentando mover archivo: tmp={$archivo['tmp_name']} -> dest={$rutaDestino}");
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            $error = error_get_last();
            error_log("[PDF Debug] Error al mover archivo: " . ($error['message'] ?? 'Unknown error'));
            error_log("[PDF Debug] tmp_name existe: " . (file_exists($archivo['tmp_name']) ? 'SI' : 'NO'));
            error_log("[PDF Debug] destino es escribible: " . (is_writable(dirname($rutaDestino)) ? 'SI' : 'NO'));
            Flash::error('Error al guardar el archivo físico.');
            return false;
        }
        error_log("[PDF Debug] Archivo movido exitosamente a: {$rutaDestino}");

        // Retornar ruta relativa para almacenar en BD
        return 'storage/solicitudes/' . $nombreUnico;
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

        // En desarrollo: si el usuario es de prueba, mostrar solo jefes de prueba
        if ($esAprendiz && \Core\Config::isDev()) {
            $jefes = [];
            foreach (USUARIOS_PRUEBA as $cedula => $datos) {
                if ($datos['rol'] === ROL_JEFE) {
                    $jefes[] = [
                        'NIT' => $datos['cedula'],
                        'NOMBRE_COMPLETO' => $datos['nombre'],
                        'CENTRO_COSTO' => $datos['centro_costo'],
                        'NIVEL' => $datos['nivel'],
                    ];
                }
            }
        }

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

        $tipoSolicitud = Security::sanitizeString($_POST['tipo_solicitud'] ?? '');
        $fechaInicio   = Security::sanitizeString($_POST['fecha_inicio'] ?? '');
        $fechaFin      = Security::sanitizeString($_POST['fecha_fin'] ?? '');
        $duracionHoras = Security::sanitizeFloat($_POST['duracion_horas'] ?? '0') ?: null;
        $duracionDias  = Security::sanitizeFloat($_POST['duracion_dias'] ?? '0') ?: null;
        $observaciones = Security::sanitizeString($_POST['observaciones'] ?? '');

        // Validación centralizada
        $v = Validator::make()
            ->required($tipoSolicitud, 'tipo_solicitud', 'Selecciona un tipo de solicitud.')
            ->inWhitelist($tipoSolicitud, array_keys(TIPOS_SOLICITUD), 'tipo_solicitud', 'Tipo de solicitud no válido.')
            ->required($fechaInicio, 'fecha_inicio', 'La fecha de inicio es obligatoria.')
            ->date($fechaInicio, 'fecha_inicio')
            ->required($fechaFin, 'fecha_fin', 'La fecha fin es obligatoria.')
            ->date($fechaFin, 'fecha_fin')
            ->dateAfter($fechaInicio, $fechaFin, 'fecha_fin')
            ->range((string) ($duracionHoras ?? 0), 0, 999, 'duracion_horas')
            ->range((string) ($duracionDias ?? 0), 0, 365, 'duracion_dias')
            ->maxLength($observaciones, 2000, 'observaciones');

        if ($v->hasErrors()) {
            Flash::error(reset($v->getErrors()));
            $this->redirect('/dashboard');
        }

        $data = [
            'tipo_solicitud'       => $tipoSolicitud,
            'fecha_inicio'         => $fechaInicio,
            'fecha_fin'            => $fechaFin,
            'duracion_horas'       => $duracionHoras,
            'duracion_dias'        => $duracionDias,
            'observaciones'        => $observaciones,
            'nit_jefe_actualizado' => $nitJefe,
            'ruta_archivo'         => null,
        ];

        // Procesar reemplazo de PDF si se seleccionó
        $reemplazarPdf = !empty($_POST['reemplazar_pdf']);
        if ($reemplazarPdf) {
            $rutaArchivo = $this->procesarArchivoPDF($user['cedula']);
            if ($rutaArchivo !== false) {
                $data['ruta_archivo'] = $rutaArchivo;
            }
        }

        $model = new SolicitudModel();
        $solicitudActual = $model->getById((int) $id);

        $ok = $model->editar((int) $id, $user['cedula'], $data);

        if ($ok) {
            // Notificar al jefe que la solicitud fue editada
            if ($solicitudActual) {
                $notificacionService = new NotificacionService();
                $nitJefe = $data['nit_jefe_actualizado'] ?? $solicitudActual['NIT_JEFE'] ?? null;
                if ($nitJefe) {
                    $notificacionService->notificarSolicitudEditada(
                        (int) $id,
                        $user['cedula'],
                        $nitJefe,
                        $data['tipo_solicitud'] ?? $solicitudActual['TIPO_SOLICITUD']
                    );
                }
            }
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

    public function servirArchivo(string $id): void
    {
        $this->requireLogin();
        $user = $this->user();

        $solicitud = (new SolicitudModel())->getById((int) $id);
        if (!$solicitud || empty($solicitud['RUTA_COMPROBANTE'])) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }

        // Verificar permiso: empleado dueño, jefe asignado, RRHH o admin
        $tieneAcceso = (
            $solicitud['NIT_EMPLEADO'] === $user['cedula']
            || $solicitud['NIT_JEFE'] === $user['cedula']
            || in_array($user['rol'], [ROL_ADMIN, ROL_RRHH], true)
        );

        if (!$tieneAcceso) {
            http_response_code(403);
            exit('Sin permiso para ver este archivo.');
        }

        // Obtener solo el nombre del archivo (prevenir path traversal)
        $nombreArchivo = basename($solicitud['RUTA_COMPROBANTE']);
        $baseDir = dirname(__DIR__, 2);

        error_log("[PDF Debug servirArchivo] ID={$id}, RutaBD={$solicitud['RUTA_COMPROBANTE']}, baseDir={$baseDir}");

        // Buscar en storage/ (ubicación actual)
        $rutaAbsoluta = $baseDir . '/storage/solicitudes/' . $nombreArchivo;
        error_log("[PDF Debug servirArchivo] Intentando ruta1: {$rutaAbsoluta}, existe=" . (file_exists($rutaAbsoluta) ? 'SI' : 'NO'));

        // Si no existe, buscar en uploads/ (ubicación legacy)
        if (!file_exists($rutaAbsoluta)) {
            $rutaAbsoluta = $baseDir . '/uploads/solicitudes/' . $nombreArchivo;
            error_log("[PDF Debug servirArchivo] Intentando ruta2: {$rutaAbsoluta}, existe=" . (file_exists($rutaAbsoluta) ? 'SI' : 'NO'));
        }

        if (!file_exists($rutaAbsoluta)) {
            error_log("[PDF Debug servirArchivo] Archivo NO ENCONTRADO para ID={$id}");
            http_response_code(404);
            exit('Archivo no encontrado físicamente.');
        }

        error_log("[PDF Debug servirArchivo] Archivo encontrado: {$rutaAbsoluta}, tamaño=" . filesize($rutaAbsoluta));

        // Limpiar cualquier output buffering previo para evitar corrupción del PDF
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Servir el archivo PDF con headers apropiados
        // Nota: Se reemplazan los headers de seguridad CSP para permitir visualización inline
        header_remove('Content-Security-Policy');
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . filesize($rutaAbsoluta));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: bytes');

        readfile($rutaAbsoluta);
        exit;
    }

    public function gestionJefePost(string $id): void
    {
        $this->requireRole([ROL_JEFE, ROL_ADMIN]);
        $this->validateCsrf();

        $user = $this->user();
        $model = new SolicitudModel();
        $obs = Security::sanitizeString($_POST['observacion_jefe'] ?? '');
        $accion = Security::sanitizeString($_POST['accion'] ?? '');

        // Validar acción contra whitelist
        if (!in_array($accion, ['aprobar', 'rechazar'], true)) {
            Flash::error('Acción no válida.');
            $this->redirect('/dashboard');
        }

        $obs = Security::maxLength($obs, 2000);

        // Obtener datos de la solicitud antes de gestionar para las notificaciones
        $solicitud = $model->getById((int) $id);

        $ok = ($accion === 'aprobar')
            ? $model->aprobarJefe((int) $id, $user['cedula'], $obs)
            : $model->rechazarJefe((int) $id, $user['cedula'], $obs);

        if ($ok && $solicitud) {
            $notificacionService = new NotificacionService();

            if ($accion === 'aprobar') {
                // Notificar al empleado que fue aprobada
                $notificacionService->notificarAprobacionJefe(
                    (int) $id,
                    $solicitud['NIT_EMPLEADO'],
                    $user['cedula'],
                    $solicitud['TIPO_SOLICITUD']
                );

                // Notificar a RRHH para revisión
                $notificacionService->notificarRevisionRRHH(
                    (int) $id,
                    $solicitud['NIT_EMPLEADO'],
                    $solicitud['TIPO_SOLICITUD']
                );
            } else {
                // Notificar al empleado que fue rechazada
                $notificacionService->notificarRechazoJefe(
                    (int) $id,
                    $solicitud['NIT_EMPLEADO'],
                    $user['cedula'],
                    $solicitud['TIPO_SOLICITUD'],
                    $obs
                );
            }

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

        // Validar acción contra whitelist
        if (!in_array($accion, ['aprobar', 'rechazar'], true)) {
            Flash::error('Acción no válida.');
            $this->redirect('/dashboard');
        }

        $obs = Security::maxLength($obs, 2000);

        // Obtener datos de la solicitud antes de gestionar para las notificaciones
        $solicitud = $model->getById((int) $id);

        $ok = ($accion === 'aprobar')
            ? $model->aprobarRRHH((int) $id, $user['cedula'], $obs)
            : $model->rechazarRRHH((int) $id, $user['cedula'], $obs);

        if ($ok && $solicitud) {
            $notificacionService = new NotificacionService();

            if ($accion === 'aprobar') {
                // Notificar a empleado y jefe que RRHH aprobó
                $notificacionService->notificarAprobacionRRHH(
                    (int) $id,
                    $solicitud['NIT_EMPLEADO'],
                    $solicitud['NIT_JEFE'],
                    $solicitud['TIPO_SOLICITUD']
                );
            } else {
                // Notificar a empleado y jefe que RRHH rechazó
                $notificacionService->notificarRechazoRRHH(
                    (int) $id,
                    $solicitud['NIT_EMPLEADO'],
                    $solicitud['NIT_JEFE'],
                    $solicitud['TIPO_SOLICITUD'],
                    $obs
                );
            }

            Flash::success('Gestión RRHH guardada.');
        } else {
            Flash::error('No se pudo gestionar.');
        }

        $this->redirect('/dashboard');
    }

    /**
     * Obtener el ID de la última solicitud creada por un empleado
     */
    private function getUltimaSolicitudId(string $nitEmpleado): int
    {
        $rows = (new SolicitudModel())->getByEmpleado($nitEmpleado);
        return !empty($rows) ? (int) $rows[0]['ID'] : 0;
    }
}
