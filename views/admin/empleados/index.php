<?php

use Core\Config;

$baseUrl = Config::baseUrl();

// Filtro activo desde URL
$filtroRol = $_GET['rol'] ?? '';

// Helper para determinar rol visual
// Ahora verifica NIT contra Super Admin y admins adicionales, no solo nivel
function getRolInfo(int $nivel, string $nit = '', array $adminsAdicionales = []): array {
    // Verificar si es Super Admin o admin adicional
    if ($nit === SUPER_ADMIN_NIT || in_array($nit, $adminsAdicionales, true)) {
        return ['label' => 'Administrador', 'class' => 'rol-admin', 'icon' => 'crown', 'key' => 'admin'];
    }
    if (in_array($nivel, [5, 6])) {
        return ['label' => 'Talento Humano', 'class' => 'rol-rrhh', 'icon' => 'users', 'key' => 'rrhh'];
    }
    if ($nivel >= NIVEL_MIN_JEFE) {
        return ['label' => 'Jefe Inmediato', 'class' => 'rol-jefe', 'icon' => 'user-check', 'key' => 'jefe'];
    }
    return ['label' => 'Empleado', 'class' => 'rol-empleado', 'icon' => 'user', 'key' => 'empleado'];
}

// Helper para construir URL con filtro
function urlConFiltro(string $baseUrl, string $busqueda, string $rol): string {
    $params = array_filter(['q' => $busqueda, 'rol' => $rol]);
    return $baseUrl . '/admin/empleados' . (!empty($params) ? '?' . http_build_query($params) : '');
}

?>

<!-- Fix para subrayados en tarjetas de filtro -->
<style>
.stats-row a.stat-card,
.stats-row a.stat-card *,
.stats-row a.stat-card .num,
.stats-row a.stat-card .lbl,
.stats-row a.stat-card div,
.stats-row a.stat-card span {
    text-decoration: none !important;
    border-bottom: none !important;
}
.stats-row a.stat-card:hover,
.stats-row a.stat-card:hover * {
    text-decoration: none !important;
}
</style>

<!-- Header Mejorado -->
<div class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;margin-bottom:24px;">
    <div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
            <div style="width:44px;height:44px;background:linear-gradient(135deg,#0a5a1f 0%,#128b3b 100%);border-radius:12px;display:flex;align-items:center;justify-content:center;color:white;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
            <div>
                <h1 class="page-title" style="margin:0;">Gestión de Empleados</h1>
                <p style="color:var(--muted);font-size:14px;margin:0;">
                    Directorio de empleados activos en la organización
                </p>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:10px;">
        <a href="<?= $baseUrl ?>/dashboard" class="btn btn-outline" style="display:flex;align-items:center;gap:6px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                <polyline points="9 22 9 12 15 12 15 22"/>
            </svg>
            Dashboard
        </a>
    </div>
</div>

<!-- Estadísticas Rápidas con Filtros -->
<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:24px;">
    
    <!-- Total (limpia filtro) -->
    <a href="<?= urlConFiltro($baseUrl, $busqueda ?? '', '') ?>" 
       class="stat-card <?= $filtroRol === '' ? 'filtro-activo' : '' ?>" 
       style="background:<?= $filtroRol === '' ? 'linear-gradient(135deg,#0a5a1f 0%,#128b3b 100%)' : '#f3f4f6' ?>;color:<?= $filtroRol === '' ? 'white' : '#374151' ?>;border:<?= $filtroRol === '' ? 'none' : '2px solid #e5e7eb' ?>;text-decoration:none !important;transition:all 0.2s;"
       onmouseover="if(!this.classList.contains('filtro-activo')) { this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'; }"
       onmouseout="this.style.transform='';this.style.boxShadow='';">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div style="font-size:32px;font-weight:700;"><?= $total ?? count($empleados) ?></div>
                <div style="font-size:14px;opacity:<?= $filtroRol === '' ? '0.9' : '1' ?>;">Total Empleados</div>
                <?php if ($filtroRol !== ''): ?>
                    <div style="font-size:11px;margin-top:4px;opacity:0.8;">↻ Ver todos</div>
                <?php endif; ?>
            </div>
            <div style="width:40px;height:40px;background:<?= $filtroRol === '' ? 'rgba(255,255,255,0.2)' : '#0a5a1f' ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
        </div>
    </a>

    <?php
    // Calcular distribución por rol (antes de aplicar filtro actual)
    $todosLosEmpleados = $model->getTodos();
    // Solo Super Admin + admins adicionales del JSON son considerados administradores
    $totalAdmins = count(array_filter($todosLosEmpleados, function($e) use ($adminsAdicionales) {
        $nit = (string)($e['NIT'] ?? '');
        return $nit === SUPER_ADMIN_NIT || in_array($nit, $adminsAdicionales ?? [], true);
    }));
    $totalJefes = count(array_filter($todosLosEmpleados, function($e) use ($adminsAdicionales) {
        $nit = (string)($e['NIT'] ?? '');
        $nivel = (int)($e['NIVEL'] ?? 0);
        $esAdmin = ($nit === SUPER_ADMIN_NIT) || in_array($nit, $adminsAdicionales ?? [], true);
        return $nivel >= NIVEL_MIN_JEFE && !$esAdmin;
    }));
    $totalEmpleadosRegulares = count($todosLosEmpleados) - $totalAdmins - $totalJefes;
    ?>

    <!-- Administradores -->
    <a href="<?= urlConFiltro($baseUrl, $busqueda ?? '', 'admin') ?>" 
       class="stat-card <?= $filtroRol === 'admin' ? 'filtro-activo' : '' ?>"
       style="background:<?= $filtroRol === 'admin' ? '#f59e0b' : '#fef3c7' ?>;border:<?= $filtroRol === 'admin' ? '2px solid #d97706' : '1px solid #f59e0b' ?>;text-decoration:none !important;transition:all 0.2s;"
       onmouseover="if(!this.classList.contains('filtro-activo')) { this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(245,158,11,0.3)'; }"
       onmouseout="this.style.transform='';this.style.boxShadow='';">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div style="font-size:32px;font-weight:700;color:<?= $filtroRol === 'admin' ? 'white' : '#92400e' ?>;"><?= $totalAdmins ?></div>
                <div style="font-size:14px;color:<?= $filtroRol === 'admin' ? 'white' : '#b45309' ?>;font-weight:<?= $filtroRol === 'admin' ? '600' : '400' ?>;">Administradores</div>
                <?php if ($filtroRol === 'admin'): ?>
                    <div style="font-size:11px;color:white;margin-top:4px;opacity:0.9;">(Filtrando)</div>
                <?php endif; ?>
            </div>
            <div style="width:40px;height:40px;background:<?= $filtroRol === 'admin' ? 'rgba(255,255,255,0.3)' : '#f59e0b' ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;color:<?= $filtroRol === 'admin' ? '#92400e' : 'white' ?>;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
        </div>
    </a>

    <!-- Jefes -->
    <a href="<?= urlConFiltro($baseUrl, $busqueda ?? '', 'jefe') ?>" 
       class="stat-card <?= $filtroRol === 'jefe' ? 'filtro-activo' : '' ?>"
       style="background:<?= $filtroRol === 'jefe' ? '#3b82f6' : '#dbeafe' ?>;border:<?= $filtroRol === 'jefe' ? '2px solid #2563eb' : '1px solid #3b82f6' ?>;text-decoration:none !important;transition:all 0.2s;"
       onmouseover="if(!this.classList.contains('filtro-activo')) { this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(59,130,246,0.3)'; }"
       onmouseout="this.style.transform='';this.style.boxShadow='';">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div style="font-size:32px;font-weight:700;color:<?= $filtroRol === 'jefe' ? 'white' : '#1e40af' ?>;"><?= $totalJefes ?></div>
                <div style="font-size:14px;color:<?= $filtroRol === 'jefe' ? 'white' : '#1d4ed8' ?>;font-weight:<?= $filtroRol === 'jefe' ? '600' : '400' ?>;">Jefes</div>
                <?php if ($filtroRol === 'jefe'): ?>
                    <div style="font-size:11px;color:white;margin-top:4px;opacity:0.9;">(Filtrando)</div>
                <?php endif; ?>
            </div>
            <div style="width:40px;height:40px;background:<?= $filtroRol === 'jefe' ? 'rgba(255,255,255,0.3)' : '#3b82f6' ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;color:<?= $filtroRol === 'jefe' ? '#1e40af' : 'white' ?>;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
    </a>

    <!-- Empleados -->
    <a href="<?= urlConFiltro($baseUrl, $busqueda ?? '', 'empleado') ?>" 
       class="stat-card <?= $filtroRol === 'empleado' ? 'filtro-activo' : '' ?>"
       style="background:<?= $filtroRol === 'empleado' ? '#6b7280' : '#f3f4f6' ?>;border:<?= $filtroRol === 'empleado' ? '2px solid #4b5563' : '1px solid #9ca3af' ?>;text-decoration:none !important;transition:all 0.2s;"
       onmouseover="if(!this.classList.contains('filtro-activo')) { this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(107,114,128,0.3)'; }"
       onmouseout="this.style.transform='';this.style.boxShadow='';">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;">
            <div>
                <div style="font-size:32px;font-weight:700;color:<?= $filtroRol === 'empleado' ? 'white' : '#374151' ?>;"><?= $totalEmpleadosRegulares ?></div>
                <div style="font-size:14px;color:<?= $filtroRol === 'empleado' ? 'white' : '#4b5563' ?>;font-weight:<?= $filtroRol === 'empleado' ? '600' : '400' ?>;">Empleados</div>
                <?php if ($filtroRol === 'empleado'): ?>
                    <div style="font-size:11px;color:white;margin-top:4px;opacity:0.9;">(Filtrando)</div>
                <?php endif; ?>
            </div>
            <div style="width:40px;height:40px;background:<?= $filtroRol === 'empleado' ? 'rgba(255,255,255,0.3)' : '#6b7280' ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;color:<?= $filtroRol === 'empleado' ? '#374151' : 'white' ?>;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
        </div>
    </a>
</div>

<!-- Búsqueda Mejorada -->
<div class="search-section animate-fade-up" style="background:white;border-radius:16px;padding:24px;margin-bottom:24px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
    <form method="get" action="<?= $baseUrl ?>/admin/empleados" style="display:flex;gap:12px;align-items:center;">
        <!-- Input oculto para mantener filtro de rol -->
        <?php if (!empty($filtroRol)): ?>
            <input type="hidden" name="rol" value="<?= htmlspecialchars($filtroRol) ?>">
        <?php endif; ?>
        
        <div style="flex:1;position:relative;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="position:absolute;left:16px;top:50%;transform:translateY(-50%);color:var(--muted);">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="q" value="<?= htmlspecialchars($busqueda ?? '') ?>" 
                   placeholder="Buscar por nombre, apellido o número de documento..." 
                   style="width:100%;padding:14px 16px 14px 48px;border:2px solid #e5e7eb;border-radius:12px;font-size:15px;transition:all 0.2s;"
                   onfocus="this.style.borderColor='#0a5a1f';this.style.boxShadow='0 0 0 3px rgba(10,90,31,0.1)';"
                   onblur="this.style.borderColor='#e5e7eb';this.style.boxShadow='none';">
        </div>
        <button type="submit" class="btn btn-green" style="padding:14px 24px;font-size:15px;display:flex;align-items:center;gap:8px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
            </svg>
            Buscar
        </button>
        <?php if (!empty($busqueda) || !empty($filtroRol)): ?>
            <a href="<?= $baseUrl ?>/admin/empleados" class="btn btn-gray" style="padding:14px 20px;display:flex;align-items:center;gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Limpiar filtros
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Resultados -->
<div class="section-header animate-fade-up" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;flex-wrap:wrap;gap:12px;">
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
        <h2 style="margin:0;display:flex;align-items:center;gap:8px;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:#0a5a1f;">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Empleados Activos
        </h2>
        
        <?php if (!empty($filtroRol)): 
            $rolLabels = ['admin' => 'Administradores', 'jefe' => 'Jefes', 'empleado' => 'Empleados'];
            $rolColors = ['admin' => '#f59e0b', 'jefe' => '#3b82f6', 'empleado' => '#6b7280'];
        ?>
            <span style="background:<?= $rolColors[$filtroRol] ?>20;color:<?= $rolColors[$filtroRol] ?>;border:1px solid <?= $rolColors[$filtroRol] ?>40;padding:4px 12px;border-radius:20px;font-size:13px;display:flex;align-items:center;gap:6px;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Filtrando: <?= $rolLabels[$filtroRol] ?? $filtroRol ?>
            </span>
        <?php endif; ?>
    </div>
    
    <span style="color:var(--muted);font-size:14px;background:#f3f4f6;padding:6px 12px;border-radius:20px;">
        <?= $total ?? count($empleados) ?> resultados
    </span>
</div>

<?php if (empty($empleados)): ?>
    <!-- Estado Vacío Mejorado -->
    <div class="empty-state animate-fade-up" style="text-align:center;padding:60px 20px;background:#f8faf8;border-radius:16px;">
        <div style="width:80px;height:80px;background:#e5e7eb;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <h3 style="margin:0 0 8px;color:#374151;">No se encontraron empleados</h3>
        <p style="color:var(--muted);margin:0;">
            <?php if (!empty($busqueda) && !empty($filtroRol)): ?>
                No hay <?= strtolower($rolLabels[$filtroRol] ?? $filtroRol) ?> que coincidan con "<strong><?= htmlspecialchars($busqueda) ?></strong>"
            <?php elseif (!empty($busqueda)): ?>
                No hay resultados para "<strong><?= htmlspecialchars($busqueda) ?></strong>"
            <?php elseif (!empty($filtroRol)): ?>
                No hay <?= strtolower($rolLabels[$filtroRol] ?? $filtroRol) ?> registrados en el sistema
            <?php else: ?>
                No hay empleados registrados en el sistema
            <?php endif; ?>
        </p>
        <?php if (!empty($busqueda) || !empty($filtroRol)): ?>
            <a href="<?= $baseUrl ?>/admin/empleados" class="btn btn-outline" style="margin-top:16px;display:inline-flex;align-items:center;gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
                Limpiar filtros
            </a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <!-- Grid de Tarjetas de Empleados -->
    <div class="empleados-grid animate-fade-up" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:16px;margin-bottom:24px;">
        <?php foreach ($empleados as $emp): 
            $rolInfo = getRolInfo((int)($emp['NIVEL'] ?? 0), (string)($emp['NIT'] ?? ''), $adminsAdicionales ?? []);
            $iniciales = '';
            if (!empty($emp['NOMBRE_COMPLETO'])) {
                $partes = explode(' ', $emp['NOMBRE_COMPLETO']);
                $iniciales = strtoupper(substr($partes[0] ?? '', 0, 1) . substr($partes[1] ?? '', 0, 1));
            }
        ?>
            <div class="empleado-card" style="background:white;border-radius:16px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,0.1);transition:all 0.2s;cursor:pointer;"
                 onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)';"
                 onmouseout="this.style.transform='';this.style.boxShadow='0 1px 3px rgba(0,0,0,0.1)';">
                
                <div style="display:flex;gap:16px;align-items:flex-start;">
                    <!-- Avatar -->
                    <div style="width:56px;height:56px;background:linear-gradient(135deg,#0a5a1f 0%,#128b3b 100%);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:600;font-size:18px;flex-shrink:0;">
                        <?= $iniciales ?: '?' ?>
                    </div>
                    
                    <div style="flex:1;min-width:0;">
                        <!-- Nombre -->
                        <h4 style="margin:0 0 4px 0;font-size:16px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            <?= htmlspecialchars($emp['NOMBRE_COMPLETO'] ?? 'Sin nombre') ?>
                        </h4>
                        
                        <!-- NIT -->
                        <div style="font-size:13px;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2"/>
                                <line x1="8" y1="11" x2="16" y2="11"/>
                                <line x1="8" y1="15" x2="12" y2="15"/>
                            </svg>
                            <?= htmlspecialchars($emp['NIT'] ?? 'N/A') ?>
                        </div>
                        
                        <!-- Badges -->
                        <div style="display:flex;gap:8px;flex-wrap:wrap;">
                            <!-- Rol Badge -->
                            <span class="badge <?= $rolInfo['class'] ?>" style="font-size:11px;padding:4px 10px;border-radius:20px;">
                                <?= $rolInfo['label'] ?>
                            </span>
                            
                            <?php 
                            // Verificar si es admin adicional manualmente asignado
                            $esAdminAdicional = in_array((string)($emp['NIT'] ?? ''), $adminsAdicionales ?? [], true);
                            if ($esAdminAdicional): 
                            ?>
                                <!-- Admin Adicional Badge -->
                                <span style="font-size:11px;padding:4px 10px;border-radius:20px;background:#0a5a1f;color:white;display:flex;align-items:center;gap:4px;">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                    </svg>
                                    Admin Asignado
                                </span>
                            <?php endif; ?>
                            
                            <!-- Nivel Badge -->
                            <span style="font-size:11px;padding:4px 10px;border-radius:20px;background:#f3f4f6;color:#4b5563;display:flex;align-items:center;gap:4px;">
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                                Nivel <?= $emp['NIVEL'] ?? '?' ?>
                            </span>
                            
                            <!-- Centro de Costo Badge -->
                            <span style="font-size:11px;padding:4px 10px;border-radius:20px;background:#dbeafe;color:#1e40af;">
                                CC: <?= htmlspecialchars($emp['CENTRO_COSTO'] ?? 'N/A') ?>
                            </span>
                        </div>
                        
                    </div>
                    
                    <!-- Botón Ver -->
                    <a href="<?= $baseUrl ?>/admin/empleados/<?= urlencode($emp['NIT'] ?? '') ?>" 
                       class="btn btn-sm btn-outline" 
                       style="flex-shrink:0;white-space:nowrap;">
                        Ver perfil →
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginación Mejorada -->
    <?php if (($totalPaginas ?? 1) > 1): 
        $paginaActual = $pagina ?? 1;
        $rangoInicio = max(1, $paginaActual - 2);
        $rangoFin = min($totalPaginas, $paginaActual + 2);
    ?>
        <div class="pagination animate-fade-up" style="display:flex;justify-content:center;align-items:center;gap:8px;padding:20px;background:white;border-radius:12px;">
            
            <!-- Botón Anterior -->
            <?php if ($paginaActual > 1): ?>
                <a href="<?= $baseUrl ?>/admin/empleados?<?= http_build_query(array_filter(['pagina' => $paginaActual - 1, 'q' => $busqueda ?? '', 'rol' => $filtroRol ?? ''])) ?>" 
                   class="btn btn-outline btn-sm" style="display:flex;align-items:center;gap:4px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Anterior
                </a>
            <?php else: ?>
                <button class="btn btn-gray btn-sm" disabled style="display:flex;align-items:center;gap:4px;opacity:0.5;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                    Anterior
                </button>
            <?php endif; ?>
            
            <!-- Números de página -->
            <div style="display:flex;gap:4px;">
                <?php if ($rangoInicio > 1): ?>
                    <a href="<?= $baseUrl ?>/admin/empleados?<?= http_build_query(array_filter(['pagina' => 1, 'q' => $busqueda ?? '', 'rol' => $filtroRol ?? ''])) ?>" 
                       class="btn btn-sm btn-outline">1</a>
                    <?php if ($rangoInicio > 2): ?>
                        <span style="padding:6px 8px;color:var(--muted);">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $rangoInicio; $i <= $rangoFin; $i++): ?>
                    <?php if ($i === $paginaActual): ?>
                        <button class="btn btn-sm btn-green" style="min-width:36px;"><?= $i ?></button>
                    <?php else: ?>
                        <a href="<?= $baseUrl ?>/admin/empleados?<?= http_build_query(array_filter(['pagina' => $i, 'q' => $busqueda ?? '', 'rol' => $filtroRol ?? ''])) ?>" 
                           class="btn btn-sm btn-outline" style="min-width:36px;"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($rangoFin < $totalPaginas): ?>
                    <?php if ($rangoFin < $totalPaginas - 1): ?>
                        <span style="padding:6px 8px;color:var(--muted);">...</span>
                    <?php endif; ?>
                    <a href="<?= $baseUrl ?>/admin/empleados?<?= http_build_query(array_filter(['pagina' => $totalPaginas, 'q' => $busqueda ?? '', 'rol' => $filtroRol ?? ''])) ?>" 
                       class="btn btn-sm btn-outline"><?= $totalPaginas ?></a>
                <?php endif; ?>
            </div>
            
            <!-- Botón Siguiente -->
            <?php if ($paginaActual < $totalPaginas): ?>
                <a href="<?= $baseUrl ?>/admin/empleados?<?= http_build_query(array_filter(['pagina' => $paginaActual + 1, 'q' => $busqueda ?? '', 'rol' => $filtroRol ?? ''])) ?>" 
                   class="btn btn-outline btn-sm" style="display:flex;align-items:center;gap:4px;">
                    Siguiente
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            <?php else: ?>
                <button class="btn btn-gray btn-sm" disabled style="display:flex;align-items:center;gap:4px;opacity:0.5;">
                    Siguiente
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

<?php endif; ?>

<!-- Estilos adicionales -->
<style>
.rol-admin { background: #fef3c7; color: #92400e; border: 1px solid #f59e0b; }
.rol-rrhh { background: #fce7f3; color: #9d174d; border: 1px solid #ec4899; }
.rol-jefe { background: #dbeafe; color: #1e40af; border: 1px solid #3b82f6; }
.rol-empleado { background: #f3f4f6; color: #4b5563; border: 1px solid #9ca3af; }

.empleado-card:hover .btn-outline {
    background: #0a5a1f;
    color: white;
    border-color: #0a5a1f;
}
</style>
