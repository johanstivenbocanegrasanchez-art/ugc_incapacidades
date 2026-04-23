<?php

use Core\Config;
use Core\Flash;

$baseUrl = Config::baseUrl();
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
    <link rel="stylesheet" href="<?= $baseUrl ?>/public/css/ugc.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            margin: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .rol-container {
            background: white;
            border-radius: 24px;
            padding: 48px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 520px;
            width: 90%;
            text-align: center;
        }
        .rol-header {
            margin-bottom: 32px;
        }
        .rol-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #0a5a1f 0%, #128b3b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 0 auto 20px;
        }
        .rol-header h1 {
            margin: 0 0 8px 0;
            font-size: 24px;
            color: #1f2937;
        }
        .rol-header p {
            margin: 0;
            color: #6b7280;
            font-size: 15px;
        }
        .rol-options {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 32px;
        }
        .rol-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 24px;
            border-radius: 16px;
            border: 2px solid #e5e7eb;
            background: #f9fafb;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            text-decoration: none;
            color: inherit;
        }
        .rol-card:hover {
            border-color: #0a5a1f;
            background: #f0fdf4;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(10, 90, 31, 0.15);
        }
        .rol-card.superadmin:hover {
            border-color: #f59e0b;
            background: #fffbeb;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.15);
        }
        .rol-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .rol-card.superadmin .rol-icon {
            background: #fef3c7;
            color: #b45309;
        }
        .rol-card.jefe .rol-icon {
            background: #dbeafe;
            color: #1e40af;
        }
        .rol-info h3 {
            margin: 0 0 4px 0;
            font-size: 17px;
            color: #1f2937;
        }
        .rol-info p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        .rol-arrow {
            margin-left: auto;
            color: #9ca3af;
        }
        .rol-card:hover .rol-arrow {
            color: #0a5a1f;
        }
        .rol-card.superadmin:hover .rol-arrow {
            color: #b45309;
        }
        .rol-badge {
            display: inline-block;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 20px;
            margin-top: 6px;
            font-weight: 500;
        }
        .rol-card.superadmin .rol-badge {
            background: #fef3c7;
            color: #b45309;
        }
        .rol-card.jefe .rol-badge {
            background: #dbeafe;
            color: #1e40af;
        }
        @media (max-width: 480px) {
            .rol-container {
                padding: 32px 24px;
            }
            .rol-card {
                padding: 20px;
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
            <div class="alert alert-<?= $flash['type'] ?>" style="margin-bottom: 20px; text-align: left;">
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

        <p style="margin-top: 24px; font-size: 13px; color: #9ca3af;">
            Puedes cambiar de rol cerrando sesión y volviendo a ingresar
        </p>
    </div>
</body>
</html>
