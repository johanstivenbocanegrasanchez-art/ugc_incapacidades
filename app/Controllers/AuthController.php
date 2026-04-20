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

        // Obtener jefe inmediato (no aplica para admin/RRHH)
        $jefe = (!in_array($rol, [ROL_ADMIN, ROL_RRHH], true))
            ? $empleadoModel->getJefeInmediato($cedula)
            : null;

        Session::setUser([
            'cedula'        => $cedula,
            'nombre'        => $ldap['nombre'],
            'email'         => $ldap['email'],
            'nivel'         => (int) ($empleado['NIVEL'] ?? 0),
            'centro_costo'  => $empleado['CENTRO_COSTO'] ?? '',
            'rol'           => $rol,
            'nit_jefe'      => $jefe['NIT_JEFE'] ?? null,
            'nombre_jefe'   => $jefe['NOMBRE_JEFE'] ?? null,
        ]);

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
