<?php

use Core\Config;

$baseUrl = Config::baseUrl();
require_once __DIR__ . '/../../shared/pagination.php';

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
<div class="section-header animate-fade-up" style="margin-top:32px;margin-bottom:16px;">
    <h2 style="margin:0;display:flex;align-items:center;gap:10px;font-size:18px;color:#1f2937;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0a5a1f" stroke-width="2">
            <path d="M3 3v18h18"/>
            <path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"/>
        </svg>
        Estadísticas de Solicitudes
    </h2>
</div>

<div class="stats-row animate-fade-up" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
    <div style="background:white;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid #e5e7eb;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#6b7280,#9ca3af);"></div>
        <div style="font-size:32px;font-weight:800;color:#374151;line-height:1;"><?= $stats['total'] ?? 0 ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:6px;font-weight:500;">Total Solicitudes</div>
    </div>
    <div style="background:white;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid #e5e7eb;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#d97706,#f59e0b);"></div>
        <div style="font-size:32px;font-weight:800;color:#d97706;line-height:1;"><?= $stats['pendientes'] ?? 0 ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:6px;font-weight:500;">Pendientes</div>
    </div>
    <div style="background:white;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid #e5e7eb;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#059669,#10b981);"></div>
        <div style="font-size:32px;font-weight:800;color:#059669;line-height:1;"><?= $stats['aprobadas'] ?? 0 ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:6px;font-weight:500;">Aprobadas</div>
    </div>
    <div style="background:white;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border:1px solid #e5e7eb;position:relative;overflow:hidden;">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,#dc2626,#ef4444);"></div>
        <div style="font-size:32px;font-weight:800;color:#dc2626;line-height:1;"><?= $stats['rechazadas'] ?? 0 ?></div>
        <div style="font-size:13px;color:#6b7280;margin-top:6px;font-weight:500;">Rechazadas</div>
    </div>
</div>

<!-- Historial de Solicitudes -->
<div class="section-header animate-fade-up" style="margin-top:32px;margin-bottom:16px;">
    <h2 style="margin:0;display:flex;align-items:center;gap:10px;font-size:18px;color:#1f2937;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#0a5a1f" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="16" y1="13" x2="8" y2="13"/>
            <line x1="16" y1="17" x2="8" y2="17"/>
            <polyline points="10 9 9 9 8 9"/>
        </svg>
        Historial de Solicitudes
    </h2>
</div>

<?php if (empty($solicitudes)): ?>
    <div style="background:#f8fafc;border-radius:16px;padding:48px 24px;text-align:center;border:2px dashed #e2e8f0;">
        <div style="width:64px;height:64px;background:#e2e8f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
        </div>
        <h3 style="margin:0 0 8px 0;font-size:16px;color:#374151;">Sin solicitudes registradas</h3>
        <p style="margin:0;color:#64748b;font-size:14px;">Este empleado no tiene solicitudes de incapacidad en el sistema.</p>
    </div>
<?php else: ?>
    <div style="background:white;border-radius:16px;box-shadow:0 1px 3px rgba(0,0,0,0.1);overflow:hidden;border:1px solid #e5e7eb;">
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:linear-gradient(180deg,#0a5a1f,#0d7028);">
                        <th style="padding:14px 16px;text-align:left;font-size:12px;font-weight:600;color:white;text-transform:uppercase;letter-spacing:0.5px;border:none;">ID</th>
                        <th style="padding:14px 16px;text-align:left;font-size:12px;font-weight:600;color:white;text-transform:uppercase;letter-spacing:0.5px;border:none;">Tipo</th>
                        <th style="padding:14px 16px;text-align:left;font-size:12px;font-weight:600;color:white;text-transform:uppercase;letter-spacing:0.5px;border:none;">Fecha Inicio</th>
                        <th style="padding:14px 16px;text-align:left;font-size:12px;font-weight:600;color:white;text-transform:uppercase;letter-spacing:0.5px;border:none;">Fecha Fin</th>
                        <th style="padding:14px 16px;text-align:left;font-size:12px;font-weight:600;color:white;text-transform:uppercase;letter-spacing:0.5px;border:none;">Estado</th>
                        <th style="padding:14px 16px;text-align:left;font-size:12px;font-weight:600;color:white;text-transform:uppercase;letter-spacing:0.5px;border:none;">Fecha Creación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudes as $sol): ?>
                        <tr style="border-bottom:1px solid #f1f5f9;transition:background 0.2s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">
                            <td style="padding:14px 16px;font-size:14px;color:#374151;font-weight:500;">#<?= $sol['ID'] ?></td>
                            <td style="padding:14px 16px;font-size:14px;color:#374151;"><?= htmlspecialchars(TIPOS_SOLICITUD[$sol['TIPO_SOLICITUD']] ?? $sol['TIPO_SOLICITUD']) ?></td>
                            <td style="padding:14px 16px;font-size:14px;color:#64748b;"><?= date('d/m/Y', strtotime($sol['FECHA_INICIO'])) ?></td>
                            <td style="padding:14px 16px;font-size:14px;color:#64748b;"><?= date('d/m/Y', strtotime($sol['FECHA_FIN'])) ?></td>
                            <td style="padding:14px 16px;">
                                <?php 
                                $estadoColors = [
                                    'PENDIENTE_JEFE' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Pendiente Jefe'],
                                    'APROBADO_JEFE' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Aprobado Jefe'],
                                    'RECHAZADO_JEFE' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Rechazado Jefe'],
                                    'APROBADO_RRHH' => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Aprobado RRHH'],
                                    'RECHAZADO_RRHH' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Rechazado RRHH'],
                                ];
                                $estado = $estadoColors[$sol['ESTADO']] ?? ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => $sol['ESTADO']];
                                ?>
                                <span style="display:inline-block;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600;background:<?= $estado['bg'] ?>;color:<?= $estado['color'] ?>;">
                                    <?= $estado['label'] ?>
                                </span>
                            </td>
                            <td style="padding:14px 16px;font-size:14px;color:#64748b;"><?= date('d/m/Y H:i', strtotime($sol['FECHA_CREACION'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php ugcRenderPagination($historialPagination, 'solicitudes'); ?>
<?php endif; ?>

<!-- Estilos adicionales -->
<style>
.rol-admin-detalle { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
.rol-rrhh-detalle { background: #fce7f3; color: #9d174d; border: 1px solid #ec4899; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
.rol-jefe-detalle { background: #dbeafe; color: #1e40af; border: 1px solid #3b82f6; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
.rol-empleado-detalle { background: #f3f4f6; color: #4b5563; border: 1px solid #9ca3af; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
</style>
