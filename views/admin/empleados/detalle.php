<?php

use Core\Config;

$baseUrl = Config::baseUrl();

// Helper para determinar rol visual
// Verifica si es Super Admin o admin adicional además del nivel
function getRolInfoDetalle(int $nivel, bool $esAdmin = false): array {
    if ($esAdmin) {
        return ['label' => 'Administrador', 'class' => 'rol-admin-detalle', 'icon' => 'crown'];
    }
    if (in_array($nivel, [5, 6])) {
        return ['label' => 'Talento Humano', 'class' => 'rol-rrhh-detalle', 'icon' => 'users'];
    }
    if ($nivel >= NIVEL_MIN_JEFE) {
        return ['label' => 'Jefe Inmediato', 'class' => 'rol-jefe-detalle', 'icon' => 'user-check'];
    }
    return ['label' => 'Empleado', 'class' => 'rol-empleado-detalle', 'icon' => 'user'];
}

$rolInfo = getRolInfoDetalle((int)($empleado['NIVEL'] ?? 0), ($esAdminAdicional ?? false) || ((string)($empleado['NIT'] ?? '')) === SUPER_ADMIN_NIT);
$iniciales = '';
if (!empty($empleado['NOMBRE_COMPLETO'])) {
    $partes = explode(' ', $empleado['NOMBRE_COMPLETO']);
    $iniciales = strtoupper(substr($partes[0] ?? '', 0, 1) . substr($partes[1] ?? '', 0, 1));
}

?>
<!-- Header con Perfil Visual -->
<div class="page-header animate-fade-down" style="background:linear-gradient(135deg,#0a5a1f 0%,#128b3b 100%);border-radius:16px;padding:32px;margin-bottom:24px;color:white;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px;">
        <div style="display:flex;gap:20px;align-items:center;">
            <!-- Avatar Grande -->
            <div style="width:80px;height:80px;background:rgba(255,255,255,0.2);backdrop-filter:blur(10px);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:28px;border:3px solid rgba(255,255,255,0.3);">
                <?= $iniciales ?: '?' ?>
            </div>
            
            <div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:8px;">
                    <h1 class="page-title" style="margin:0;color:white;font-size:24px;">
                        <?= htmlspecialchars($empleado['NOMBRE_COMPLETO'] ?? 'Sin nombre') ?>
                    </h1>
                    <span style="background:rgba(255,255,255,0.2);color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500;">
                        <?= $rolInfo['label'] ?>
                    </span>
                    <?php if ($esAdminAdicional ?? false): ?>
                        <span style="background:#0a5a1f;color:white;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                            </svg>
                            Admin Asignado
                        </span>
                    <?php endif; ?>
                </div>
                <p style="margin:0;font-size:15px;opacity:0.9;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                    <span style="display:flex;align-items:center;gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <line x1="8" y1="11" x2="16" y2="11"/>
                        </svg>
                        NIT: <?= htmlspecialchars($empleado['NIT'] ?? 'N/A') ?>
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                        Nivel <?= $empleado['NIVEL'] ?? 'N/A' ?>
                    </span>
                    <span style="display:flex;align-items:center;gap:6px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                        </svg>
                        CC: <?= htmlspecialchars($empleado['CENTRO_COSTO'] ?? 'N/A') ?>
                    </span>
                </p>
            </div>
        </div>

        <div style="display:flex;gap:10px;">
            <a href="<?= $baseUrl ?>/admin/empleados" class="btn" style="background:rgba(255,255,255,0.2);color:white;border:1px solid rgba(255,255,255,0.3);display:flex;align-items:center;gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Volver al listado
            </a>
        </div>
    </div>
</div>

<!-- Información Detallada -->
<div class="info-sections animate-fade-up" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:20px;margin-bottom:24px;">
    
    <!-- Tarjeta Información Personal -->
    <div style="background:white;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin:0 0 16px 0;font-size:16px;color:#1f2937;display:flex;align-items:center;gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0a5a1f" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
            Información Personal
        </h3>
        
        <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#94a3b8;">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Nombre completo
                </span>
                <span style="font-weight:500;color:#1f2937;font-size:14px;text-align:right;"><?= htmlspecialchars($empleado['NOMBRE_COMPLETO'] ?? 'N/A') ?></span>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#94a3b8;">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <line x1="8" y1="11" x2="16" y2="11"/>
                        <line x1="8" y1="15" x2="12" y2="15"/>
                    </svg>
                    Número de identificación
                </span>
                <span style="font-weight:500;color:#1f2937;font-size:14px;font-family:monospace;"><?= htmlspecialchars($empleado['NIT'] ?? 'N/A') ?></span>
            </div>
            <div style="background:#f0fdf4;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;border:1px solid #bbf7d0;">
                <span style="color:#166534;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    Estado
                </span>
                <span style="font-size:13px;font-weight:600;color:#15803d;">Activo</span>
            </div>
        </div>
    </div>

    <!-- Tarjeta Información Laboral -->
    <div style="background:white;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        <h3 style="margin:0 0 16px 0;font-size:16px;color:#1f2937;display:flex;align-items:center;gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0a5a1f" stroke-width="2">
                <rect x="2" y="3" width="20" height="14" rx="2"/>
                <line x1="8" y1="21" x2="16" y2="21"/>
                <line x1="12" y1="17" x2="12" y2="21"/>
            </svg>
            Información Laboral
        </h3>
        
        <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#94a3b8;">
                        <rect x="2" y="3" width="20" height="14" rx="2"/>
                        <line x1="8" y1="21" x2="16" y2="21"/>
                    </svg>
                    Centro de Costo
                </span>
                <span style="font-weight:600;color:#1f2937;font-size:14px;background:#e0e7ff;padding:4px 10px;border-radius:6px;font-family:monospace;"><?= htmlspecialchars($empleado['CENTRO_COSTO'] ?? 'N/A') ?></span>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#94a3b8;">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    Nivel jerárquico
                </span>
                <span style="font-weight:600;color:#1f2937;font-size:14px;background:#fef3c7;padding:4px 12px;border-radius:20px;min-width:28px;text-align:center;"><?= $empleado['NIVEL'] ?? 'N/A' ?></span>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;display:flex;justify-content:space-between;align-items:center;">
                <span style="color:#64748b;font-size:13px;display:flex;align-items:center;gap:6px;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#94a3b8;">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Rol en el sistema
                </span>
                <span style="font-size:12px;" class="badge <?= $rolInfo['class'] ?>">
                    <?= $rolInfo['label'] ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Tarjeta Gestión de Accesos (Solo Super Admin) -->
    <?php if ($puedeGestionarAdmin ?? false): ?>
    <div style="background:white;border-radius:16px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:2px solid <?= ($esAdminAdicional ?? false) ? '#0a5a1f' : '#e5e7eb' ?>;">
        <h3 style="margin:0 0 16px 0;font-size:16px;color:#1f2937;display:flex;align-items:center;gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0a5a1f" stroke-width="2">
                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            Gestión de Accesos
        </h3>
        
        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php if ($esAdminAdicional ?? false): ?>
                <!-- Estado: Admin Asignado -->
                <div style="background:#f0fdf4;border-radius:10px;padding:14px 16px;border:1px solid #bbf7d0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        <span style="font-weight:600;color:#166534;">Administrador Asignado</span>
                    </div>
                    <p style="margin:0 0 12px 0;font-size:12px;color:#4b5563;">
                        Este empleado tiene acceso de administrador asignado manualmente.
                    </p>
                    <form method="post" action="<?= $baseUrl ?>/admin/empleados/<?= urlencode($empleado['NIT'] ?? '') ?>/quitar-admin" style="display:inline;">
                        <button type="submit" class="btn" style="background:#dc2626;color:white;border:none;width:100%;display:flex;align-items:center;justify-content:center;gap:6px;"
                                onclick="return confirm('¿Quitar acceso de administrador a <?= htmlspecialchars($empleado['NOMBRE_COMPLETO'] ?? '') ?>?')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <line x1="17" y1="8" x2="22" y2="13"/>
                                <line x1="22" y1="8" x2="17" y2="13"/>
                            </svg>
                            Quitar Admin
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <!-- Estado: No es Admin -->
                <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;border:1px solid #e5e7eb;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6b7280" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span style="font-weight:600;color:#374151;">Rol Base: <?= $rolInfo['label'] ?></span>
                    </div>
                    <p style="margin:0 0 12px 0;font-size:12px;color:#6b7280;">
                        Puedes asignar acceso de administrador adicional a este empleado.
                    </p>
                    <form method="post" action="<?= $baseUrl ?>/admin/empleados/<?= urlencode($empleado['NIT'] ?? '') ?>/hacer-admin" style="display:inline;">
                        <button type="submit" class="btn" style="background:#0a5a1f;color:white;border:none;width:100%;display:flex;align-items:center;justify-content:center;gap:6px;"
                                onclick="return confirm('¿Dar acceso de administrador a <?= htmlspecialchars($empleado['NOMBRE_COMPLETO'] ?? '') ?>?')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M20 8v6"/>
                                <path d="M23 11h-6"/>
                            </svg>
                            Hacer Admin
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Estadísticas de Solicitudes -->
<div class="section-header animate-fade-up" style="margin-top:24px;">
    <h2>Estadísticas de Solicitudes</h2>
</div>

<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(150px,1fr));">
    <div class="stat-card">
        <div class="num" style="font-size:32px;color:#374151;"><?= $stats['total'] ?? 0 ?></div>
        <div class="lbl">Total Solicitudes</div>
    </div>
    <div class="stat-card">
        <div class="num" style="font-size:32px;color:#d97706;"><?= $stats['pendientes'] ?? 0 ?></div>
        <div class="lbl">Pendientes</div>
    </div>
    <div class="stat-card">
        <div class="num" style="font-size:32px;color:#059669;"><?= $stats['aprobadas'] ?? 0 ?></div>
        <div class="lbl">Aprobadas</div>
    </div>
    <div class="stat-card">
        <div class="num" style="font-size:32px;color:#dc2626;"><?= $stats['rechazadas'] ?? 0 ?></div>
        <div class="lbl">Rechazadas</div>
    </div>
</div>

<!-- Historial de Solicitudes -->
<div class="section-header animate-fade-up" style="margin-top:24px;">
    <h2>Historial de Solicitudes</h2>
</div>

<?php if (empty($solicitudes)): ?>
    <div class="empty-state animate-fade-up">
        <p>Este empleado no tiene solicitudes registradas.</p>
    </div>
<?php else: ?>
    <div class="table-container animate-fade-up">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Estado</th>
                    <th>Fecha Creación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($solicitudes as $sol): ?>
                    <tr>
                        <td><?= $sol['ID'] ?></td>
                        <td><?= htmlspecialchars(TIPOS_SOLICITUD[$sol['TIPO_SOLICITUD']] ?? $sol['TIPO_SOLICITUD']) ?></td>
                        <td><?= date('d/m/Y', strtotime($sol['FECHA_INICIO'])) ?></td>
                        <td><?= date('d/m/Y', strtotime($sol['FECHA_FIN'])) ?></td>
                        <td>
                            <?php badgeEstado($sol['ESTADO']); ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($sol['FECHA_CREACION'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Estilos adicionales -->
<style>
.rol-admin-detalle { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
.rol-rrhh-detalle { background: #fce7f3; color: #9d174d; border: 1px solid #ec4899; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
.rol-jefe-detalle { background: #dbeafe; color: #1e40af; border: 1px solid #3b82f6; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
.rol-empleado-detalle { background: #f3f4f6; color: #4b5563; border: 1px solid #9ca3af; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
</style>
