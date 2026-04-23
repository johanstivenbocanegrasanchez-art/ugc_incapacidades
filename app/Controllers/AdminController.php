<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Security;
use Core\Flash;
use App\Models\EmpleadoModel;
use App\Models\SolicitudModel;
use App\Models\AdminRolesModel;

/**
 * Controlador para funciones administrativas del sistema
 */
final class AdminController extends Controller
{
    /**
     * Listado de empleados (solo lectura desde Oracle)
     * GET /admin/empleados
     */
    public function empleados(): void
    {
        $this->requireRole([ROL_ADMIN]);

        $busqueda = Security::sanitizeString($_GET['q'] ?? '');
        $centroCosto = Security::sanitizeString($_GET['cc'] ?? '');
        $filtroRol = Security::sanitizeString($_GET['rol'] ?? '');
        $pagina = max(1, (int) ($_GET['pagina'] ?? 1));
        $porPagina = 30;

        $model = new EmpleadoModel();
        $adminRolesModel = new AdminRolesModel();
        
        // Obtener todos los empleados activos desde Oracle
        $todosEmpleados = $model->getTodos();
        
        // Lista de NITs con rol admin adicional
        $adminsAdicionales = $adminRolesModel->getAdminsAdicionales();

        // Aplicar filtro por rol
        if (!empty($filtroRol)) {
            $todosEmpleados = array_filter($todosEmpleados, function ($emp) use ($filtroRol, $adminsAdicionales) {
                $nivel = (int) ($emp['NIVEL'] ?? 0);
                $nit = (string) ($emp['NIT'] ?? '');
                // Verificar si es admin (Super Admin o admins adicionales del JSON)
                $esAdmin = ($nit === SUPER_ADMIN_NIT) || in_array($nit, $adminsAdicionales, true);

                switch ($filtroRol) {
                    case 'admin':
                        return $esAdmin;
                    case 'jefe':
                        return $nivel >= NIVEL_MIN_JEFE && !$esAdmin;
                    case 'empleado':
                        return $nivel < NIVEL_MIN_JEFE && !$esAdmin;
                    default:
                        return true;
                }
            });
        }

        // Aplicar filtros de búsqueda
        if (!empty($busqueda)) {
            $todosEmpleados = array_filter($todosEmpleados, function ($emp) use ($busqueda) {
                $texto = strtolower(($emp['NOMBRE_COMPLETO'] ?? '') . ' ' . ($emp['NIT'] ?? ''));
                return str_contains($texto, strtolower($busqueda));
            });
        }

        // Paginación manual
        $total = count($todosEmpleados);
        $totalPaginas = (int) ceil($total / $porPagina);
        $empleados = array_slice($todosEmpleados, ($pagina - 1) * $porPagina, $porPagina);

        // Verificar si el usuario actual es el Super Admin único
        $user = $this->user();
        $esSuperAdmin = ((string)($user['cedula'] ?? '')) === SUPER_ADMIN_NIT;

        $this->render('admin/empleados/index', compact(
            'empleados',
            'total',
            'pagina',
            'totalPaginas',
            'busqueda',
            'centroCosto',
            'filtroRol',
            'model',
            'adminsAdicionales',
            'esSuperAdmin',
            'user'
        ));
    }

    /**
     * Ver detalle de un empleado y su historial
     * GET /admin/empleados/{nit}
     */
    public function empleadoDetalle(string $nit): void
    {
        $this->requireRole([ROL_ADMIN]);

        $empleadoModel = new EmpleadoModel();
        $solicitudModel = new SolicitudModel();
        $adminRolesModel = new AdminRolesModel();

        $empleado = $empleadoModel->getByNit($nit);
        
        if (!$empleado) {
            Flash::error('Empleado no encontrado.');
            $this->redirect('/admin/empleados');
        }

        // Obtener historial de solicitudes
        $solicitudes = $solicitudModel->getByEmpleado($nit);
        
        // Estadísticas
        $stats = [
            'total' => count($solicitudes),
            'pendientes' => count(array_filter($solicitudes, fn($s) => $s['ESTADO'] === 'PENDIENTE_JEFE')),
            'aprobadas' => count(array_filter($solicitudes, fn($s) => in_array($s['ESTADO'], ['APROBADO_JEFE', 'APROBADO_RRHH']))),
            'rechazadas' => count(array_filter($solicitudes, fn($s) => in_array($s['ESTADO'], ['RECHAZADO_JEFE', 'RECHAZADO_RRHH']))),
        ];

        // Datos para gestión de roles admin
        $adminsAdicionales = $adminRolesModel->getAdminsAdicionales();
        $esAdminAdicional = in_array($nit, $adminsAdicionales, true);
        
        // Verificar si el usuario actual es el Super Admin único
        $user = $this->user();
        $esSuperAdmin = ((string)($user['cedula'] ?? '')) === SUPER_ADMIN_NIT;
        $puedeGestionarAdmin = $esSuperAdmin && $nit !== ($user['cedula'] ?? '');

        $this->render('admin/empleados/detalle', compact(
            'empleado', 
            'solicitudes', 
            'stats', 
            'adminsAdicionales', 
            'esAdminAdicional',
            'esSuperAdmin',
            'puedeGestionarAdmin',
            'user'
        ));
    }

    /**
     * Asignar rol admin adicional a un empleado
     * POST /admin/empleados/{nit}/hacer-admin
     */
    public function hacerAdmin(string $nit): void
    {
        $this->requireRole([ROL_ADMIN]);

        // Solo el Super Admin único puede asignar roles
        $user = $this->user();
        if (((string)($user['cedula'] ?? '')) !== SUPER_ADMIN_NIT) {
            Flash::error('No tienes permiso para asignar roles de administrador.');
            $this->redirect('/admin/empleados');
            return;
        }

        $adminRolesModel = new AdminRolesModel();
        
        if ($adminRolesModel->agregarAdmin($nit)) {
            Flash::success("Empleado {$nit} ahora tiene acceso como administrador.");
        } else {
            Flash::error('Error al asignar rol de administrador.');
        }

        $this->redirect('/admin/empleados');
    }

    /**
     * Quitar rol admin adicional a un empleado
     * POST /admin/empleados/{nit}/quitar-admin
     */
    public function quitarAdmin(string $nit): void
    {
        $this->requireRole([ROL_ADMIN]);

        // Solo el Super Admin único puede quitar roles
        $user = $this->user();
        if (((string)($user['cedula'] ?? '')) !== SUPER_ADMIN_NIT) {
            Flash::error('No tienes permiso para quitar roles de administrador.');
            $this->redirect('/admin/empleados');
            return;
        }

        $adminRolesModel = new AdminRolesModel();
        
        if ($adminRolesModel->quitarAdmin($nit)) {
            Flash::success("Se ha removido el acceso de administrador al empleado {$nit}.");
        } else {
            Flash::error('Error al remover rol de administrador.');
        }

        $this->redirect('/admin/empleados');
    }
}
