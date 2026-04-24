<?php

use Core\Config;
use Core\Flash;

$baseUrl = Config::baseUrl();
$cssUrl = Config::assetUrl('public/css/ugc.css');
$loginBg = Config::assetUrl('public/images/login-fondo.png');
$flash = Flash::get();

// Obtener datos temporales del usuario validado
$usuarioTmp = $_SESSION['usuario_tmp'] ?? null;
if (!$usuarioTmp || ((string)($usuarioTmp['cedula'] ?? '')) !== SUPER_ADMIN_NIT) {
    header('Location: ' . $baseUrl . '/login');
    exit;
}

$nombre = $usuarioTmp['nombre'] ?? 'Usuario';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Rol - Sistema Incapacidades</title>
    <link rel="stylesheet" href="<?= $cssUrl ?>">
    <style>
        :root {
            --green: #0a5a1f;
            --green2: #128b3b;
            --green3: #e8f5ec;
            --green4: #c8e6d0;
            --text: #1c2b1e;
            --muted: #5a6b5b;
            --surface: rgba(255, 255, 255, 0.95);
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #1f6f33 url("<?= $loginBg ?>") center center / cover no-repeat;
        }

        .rol-container {
            width: 100%;
            max-width: 480px;
            background: var(--surface);
            border-radius: 26px;
            padding: 40px 36px 32px;
            box-shadow:
                0 20px 45px rgba(0, 0, 0, 0.18),
                0 0 0 1px rgba(255, 255, 255, 0.2) inset;
            text-align: center;
            position: relative;
        }

        .rol-header {
            margin-bottom: 32px;
        }

        .rol-avatar {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, var(--green) 0%, var(--green2) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: 700;
            margin: 0 auto 20px;
            box-shadow:
                0 10px 30px rgba(10, 90, 31, 0.3),
                0 0 0 4px rgba(255, 255, 255, 0.2);
        }

        .rol-header h1 {
            margin: 0 0 8px 0;
            font-size: 26px;
            font-weight: 800;
            color: var(--green);
            letter-spacing: -0.4px;
        }

        .rol-header p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }

        .rol-options {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 28px;
        }

        .rol-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 22px 20px;
            border-radius: 16px;
            border: 2px solid #e8ece9;
            background: #fbfcfb;
            cursor: pointer;
            transition: all 0.25s ease;
            text-align: left;
            text-decoration: none;
            color: inherit;
            width: 100%;
        }

        .rol-card:hover {
            border-color: var(--green);
            background: linear-gradient(135deg, #f0fdf4 0%, #e8f5ec 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(10, 90, 31, 0.18);
        }

        .rol-card.superadmin:hover {
            border-color: #d97706;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            box-shadow: 0 12px 28px rgba(217, 119, 6, 0.2);
        }

        .rol-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        .rol-card.superadmin .rol-icon {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2);
        }

        .rol-card.jefe .rol-icon {
            background: linear-gradient(135deg, var(--green3) 0%, var(--green4) 100%);
            color: var(--green);
            box-shadow: 0 4px 12px rgba(10, 90, 31, 0.15);
        }

        .rol-info h3 {
            margin: 0 0 4px 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--text);
        }

        .rol-info p {
            margin: 0;
            font-size: 13px;
            color: var(--muted);
            font-weight: 500;
        }

        .rol-arrow {
            margin-left: auto;
            color: #9ca3af;
            transition: all 0.25s ease;
        }

        .rol-card:hover .rol-arrow {
            color: var(--green);
            transform: translateX(4px);
        }

        .rol-card.superadmin:hover .rol-arrow {
            color: #b45309;
        }

        .rol-badge {
            display: inline-block;
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 6px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .rol-card.superadmin .rol-badge {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .rol-card.jefe .rol-badge {
            background: var(--green3);
            color: var(--green);
            border: 1px solid var(--green4);
        }

        .rol-footer {
            margin-top: 24px;
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }

        .rol-footer::before {
            content: '';
            display: block;
            width: 40px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--green4), transparent);
            margin: 0 auto 16px;
        }

        /* Alert */
        .alert {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: left;
        }

        .alert-error {
            background: #fff2f0;
            color: #c0392b;
            border: 1px solid #f3c1bb;
        }

        .alert-success {
            background: var(--green3);
            color: var(--green);
            border: 1px solid var(--green4);
        }

        @media (max-width: 640px) {
            .rol-container {
                max-width: 100%;
                padding: 32px 24px;
                margin: 16px;
                border-radius: 22px;
            }

            .rol-avatar {
                width: 80px;
                height: 80px;
                font-size: 28px;
            }

            .rol-header h1 {
                font-size: 22px;
            }

            .rol-card {
                padding: 18px 16px;
            }

            .rol-icon {
                width: 48px;
                height: 48px;
            }
        }
    </style>
</head>
<body>
    <div class="rol-container">
        <div class="rol-header">
            <div class="rol-avatar">
                <?php 
                $iniciales = '';
                $partes = explode(' ', $nombre);
                $iniciales = strtoupper(substr($partes[0] ?? '', 0, 1) . substr($partes[1] ?? '', 0, 1));
                echo $iniciales ?: 'SA';
                ?>
            </div>
            <h1>¡Hola, <?= htmlspecialchars($nombre) ?>!</h1>
            <p>Tienes múltiples roles en el sistema. Selecciona cómo deseas ingresar:</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <?php if ($flash['type'] === 'error'): ?>
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    <?php else: ?>
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    <?php endif; ?>
                </svg>
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="rol-options">
            <!-- Opción: Super Admin -->
            <form method="post" action="<?= $baseUrl ?>/seleccionar-rol" style="margin: 0;">
                <input type="hidden" name="rol" value="superadmin">
                <button type="submit" class="rol-card superadmin" style="width: 100%; border: none; font-family: inherit; font-size: inherit;">
                    <div class="rol-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <div class="rol-info">
                        <h3>Super Administrador</h3>
                        <p>Gestión total del sistema, administración de usuarios y configuración</p>
                        <span class="rol-badge">Acceso Total</span>
                    </div>
                    <svg class="rol-arrow" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </form>

            <!-- Opción: Jefe Inmediato -->
            <form method="post" action="<?= $baseUrl ?>/seleccionar-rol" style="margin: 0;">
                <input type="hidden" name="rol" value="jefe">
                <button type="submit" class="rol-card jefe" style="width: 100%; border: none; font-family: inherit; font-size: inherit;">
                    <div class="rol-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="rol-info">
                        <h3>Jefe Inmediato</h3>
                        <p>Aprobación de solicitudes de tu equipo, vista de incapacidades del área</p>
                        <span class="rol-badge">Flujo Normal</span>
                    </div>
                    <svg class="rol-arrow" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            </form>
        </div>

        <p class="rol-footer">
            Puedes cambiar de rol cerrando sesión y volviendo a ingresar
        </p>
    </div>
</body>
</html>
