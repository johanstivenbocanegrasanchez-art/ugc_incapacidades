<?php

declare(strict_types=1);

namespace App\Controllers;

use Core\Controller;
use Core\Config;
use Core\Flash;
use Core\Session;
use Core\Security;
use Core\Validator;
use Config\Ldap;
use App\Models\EmpleadoModel;
use App\Models\AdminRolesModel;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        $error       = $_SESSION['login_error'] ?? null;
        $devUsuarios = Config::isDev() ? USUARIOS_PRUEBA : [];
        unset($_SESSION['login_error']);

        $this->render('auth/login', compact('error', 'devUsuarios'), 'auth');
    }

    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 300;

    public function loginPost(): void
    {
        $cedula   = Security::sanitizeString($_POST['cedula'] ?? '');
        $password = trim($_POST['password'] ?? '');

        // Validación de formato
        $v = Validator::make()
            ->required($cedula, 'cedula', 'Ingresa tu número de documento.')
            ->alphaNumeric($cedula, 'cedula', 'El documento solo debe contener números y letras.')
            ->maxLength($cedula, 20, 'cedula')
            ->required($password, 'password', 'Ingresa tu contraseña.');

        if ($v->hasErrors()) {
            $_SESSION['login_error'] = reset($v->getErrors());
            $this->redirect('/login');
        }

        // Rate limiting por IP (desactivado en modo desarrollo)
        if (!Config::isDev() && !$this->checkLoginRateLimit()) {
            $_SESSION['login_error'] = 'Demasiados intentos. Intenta de nuevo en 5 minutos.';
            $this->redirect('/login');
        }

        // Modo desarrollo: usuarios de prueba
        if (Config::isDev() && isset(USUARIOS_PRUEBA[(string) $cedula]) && $password === 'prueba123') {
            $this->registerFailedLogin(false); // Login exitoso, limpiar contador
            session_regenerate_id(true);
            
            // Si es el Super Admin en modo desarrollo, mostrar selector de rol
            if (((string) $cedula) === SUPER_ADMIN_NIT) {
                $usuarioPrueba = USUARIOS_PRUEBA[(string) $cedula];
                $_SESSION['usuario_tmp'] = [
                    'cedula'        => $usuarioPrueba['cedula'],
                    'nombre'        => $usuarioPrueba['nombre'],
                    'email'         => $usuarioPrueba['email'],
                    'nivel'         => $usuarioPrueba['nivel'],
                    'centro_costo'  => $usuarioPrueba['centro_costo'],
                    'nit_jefe'      => $usuarioPrueba['nit_jefe'],
                    'nombre_jefe'   => $usuarioPrueba['nombre_jefe'],
                ];
                $this->redirect('/seleccionar-rol');
                return;
            }
            
            Session::setUser(USUARIOS_PRUEBA[(string) $cedula]);
            $this->redirect('/dashboard');
            return;
        }

        // Autenticación LDAP
        $ldap = Ldap::authenticate($cedula, $password);
        if (!$ldap) {
            $this->registerFailedLogin(true);
            $_SESSION['login_error'] = 'Credenciales incorrectas.';
            $this->redirect('/login');
            return;
        }

        // Login exitoso: limpiar rate limit y regenerar session ID
        $this->registerFailedLogin(false);
        session_regenerate_id(true);

        // Obtener datos del empleado desde Oracle
        $empleadoModel = new EmpleadoModel();
        $empleado = $empleadoModel->getByNit($cedula);
        $rol = $empleadoModel->getRol($cedula);

        // Obtener nombre completo desde Oracle (prioridad) o usar el del LDAP como fallback
        $nombreCompleto = $empleado['NOMBRE_COMPLETO'] ?? null;
        $nombreFinal = $nombreCompleto && trim($nombreCompleto) !== '' ? $nombreCompleto : ($ldap['nombre'] ?? $cedula);

        // Verificar si es el Super Admin único (tiene múltiples roles disponibles)
        if (((string) $cedula) === SUPER_ADMIN_NIT) {
            // Guardar datos temporales y mostrar selector de rol
            $_SESSION['usuario_tmp'] = [
                'cedula'        => $cedula,
                'nombre'        => $nombreFinal,
                'email'         => $ldap['email'],
                'nivel'         => (int) ($empleado['NIVEL'] ?? 0),
                'centro_costo'  => $empleado['CENTRO_COSTO'] ?? '',
                'nit_jefe'      => null,
                'nombre_jefe'   => null,
            ];
            $this->redirect('/seleccionar-rol');
            return;
        }

        // Verificar si tiene rol admin adicional asignado manualmente (JSON)
        $adminRolesModel = new AdminRolesModel();
        if ($adminRolesModel->esAdminAdicional((string) $cedula)) {
            $rol = ROL_ADMIN;
        }

        // Obtener jefe inmediato (no aplica para admin/RRHH)
        $jefe = (!in_array($rol, [ROL_ADMIN, ROL_RRHH], true))
            ? $empleadoModel->getJefeInmediato($cedula)
            : null;

        Session::setUser([
            'cedula'        => $cedula,
            'nombre'        => $nombreFinal,
            'email'         => $ldap['email'],
            'nivel'         => (int) ($empleado['NIVEL'] ?? 0),
            'centro_costo'  => $empleado['CENTRO_COSTO'] ?? '',
            'rol'           => $rol,
            'nit_jefe'      => $jefe['NIT_JEFE'] ?? null,
            'nombre_jefe'   => $jefe['NOMBRE_JEFE'] ?? null,
        ]);

        $this->redirect('/dashboard');
    }

    /**
     * Mostrar formulario de selección de rol (solo para Super Admin)
     * GET /seleccionar-rol
     */
    public function seleccionarRolForm(): void
    {
        // Verificar que existe sesión temporal de Super Admin
        $usuarioTmp = $_SESSION['usuario_tmp'] ?? null;
        if (!$usuarioTmp || ((string)($usuarioTmp['cedula'] ?? '')) !== SUPER_ADMIN_NIT) {
            $this->redirect('/login');
            return;
        }

        $this->render('auth/seleccionar_rol', [], 'auth');
    }

    /**
     * Procesar selección de rol del Super Admin
     * POST /seleccionar-rol
     */
    public function seleccionarRolPost(): void
    {
        // Verificar que existe sesión temporal de Super Admin
        $usuarioTmp = $_SESSION['usuario_tmp'] ?? null;
        if (!$usuarioTmp || ((string)($usuarioTmp['cedula'] ?? '')) !== SUPER_ADMIN_NIT) {
            $this->redirect('/login');
            return;
        }

        $rolSeleccionado = $_POST['rol'] ?? '';
        
        // Validar selección
        if (!in_array($rolSeleccionado, ['superadmin', 'jefe'], true)) {
            Flash::error('Selección de rol inválida.');
            $this->redirect('/seleccionar-rol');
            return;
        }

        $empleadoModel = new EmpleadoModel();
        $cedula = $usuarioTmp['cedula'];
        
        if ($rolSeleccionado === 'superadmin') {
            // Ingresar como Super Admin (ROL_ADMIN)
            $rol = ROL_ADMIN;
            $jefe = null;
            Flash::success('Has ingresado como Super Administrador. Tienes acceso total al sistema.');
        } else {
            // Ingresar como Jefe Inmediato (su rol normal)
            $rol = ROL_JEFE;
            $jefe = $empleadoModel->getJefeInmediato($cedula);
            Flash::success('Has ingresado como Jefe Inmediato. Acceso a flujo de aprobaciones.');
        }

        // Establecer sesión final
        Session::setUser([
            'cedula'        => $cedula,
            'nombre'        => $usuarioTmp['nombre'],
            'email'         => $usuarioTmp['email'],
            'nivel'         => $usuarioTmp['nivel'],
            'centro_costo'  => $usuarioTmp['centro_costo'],
            'rol'           => $rol,
            'nit_jefe'      => $jefe['NIT_JEFE'] ?? null,
            'nombre_jefe'   => $jefe['NOMBRE_JEFE'] ?? null,
        ]);

        // Limpiar sesión temporal
        unset($_SESSION['usuario_tmp']);

        $this->redirect('/dashboard');
    }

    private function checkLoginRateLimit(): bool
    {
        $key = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $attempts = (int) ($_SESSION[$key] ?? 0);
        $lastAttempt = (int) ($_SESSION[$key . '_time'] ?? 0);

        // Si pasó el tiempo de lockout, resetear
        if ($lastAttempt > 0 && (time() - $lastAttempt) > self::LOGIN_LOCKOUT_SECONDS) {
            unset($_SESSION[$key], $_SESSION[$key . '_time']);
            return true;
        }

        return $attempts < self::LOGIN_MAX_ATTEMPTS;
    }

    private function registerFailedLogin(bool $failed): void
    {
        $key = 'login_attempts_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');

        if ($failed) {
            $_SESSION[$key] = (int) ($_SESSION[$key] ?? 0) + 1;
            $_SESSION[$key . '_time'] = time();
        } else {
            unset($_SESSION[$key], $_SESSION[$key . '_time']);
        }
    }

    public function logout(): void
    {
        Session::destroy();
        $this->redirect('/login');
    }
}
