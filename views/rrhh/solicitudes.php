<?php
use Core\Config;

require_once __DIR__ . '/../shared/badge_estado.php';
$baseUrl = Config::baseUrl();
?>
<div class="page-header animate-fade-down">
  <div>
    <h1 class="page-title"><?= htmlspecialchars($titulo) ?></h1>
    <p style="color:var(--muted);font-size:14px;margin-top:4px">
      <?= htmlspecialchars($subtitulo ?? 'Consulta detallada de solicitudes') ?>
    </p>
  </div>
  <a href="<?= $baseUrl ?>/dashboard" class="btn btn-green">Volver al dashboard</a>
</div>

<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat-card">
    <div class="stat-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
        <polyline points="22 4 12 14.01 9 11.01"/>
      </svg>
    </div>
    <div class="num"><?= count($solicitudes) ?></div>
    <div class="lbl">Solicitudes encontradas</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
    </div>
    <div class="num"><?= htmlspecialchars($nombreFiltro ?? 'General') ?></div>
    <div class="lbl">Filtro aplicado</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 6v6l4 2"/>
      </svg>
    </div>
    <div class="num">RRHH</div>
    <div class="lbl">Vista actual</div>
  </div>
</div>

<div class="section-header"><h2><?= htmlspecialchars($titulo) ?></h2></div>

<?php if (empty($solicitudes)): ?>
  <div class="empty-state animate-fade-up">
    <p>No hay solicitudes para mostrar en esta categoría.</p>
  </div>
<?php else: ?>
<div class="ugc-table-wrap animate-fade-up">
  <table class="ugc-table">
    <thead>
      <tr>
        <th>#</th>
        <th>Empleado</th>
        <th>Tipo</th>
        <th>Inicio</th>
        <th>Fin</th>
        <th>Estado</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($solicitudes as $s): ?>
      <tr>
        <td data-label="#"><?= $s['ID'] ?></td>
        <td data-label="Empleado"><?= htmlspecialchars($s['NIT_EMPLEADO'] ?? '') ?></td>
        <td data-label="Tipo">
          <?= htmlspecialchars($tipos[$s['TIPO_SOLICITUD']] ?? $s['TIPO_SOLICITUD'] ?? '') ?>
          <?= !empty($s['RUTA_COMPROBANTE']) ? '<span title="Tiene PDF adjunto">📎</span>' : '' ?>
        </td>
        <td data-label="Inicio"><?= !empty($s['FECHA_INICIO']) ? substr($s['FECHA_INICIO'], 0, 10) : '' ?></td>
        <td data-label="Fin"><?= !empty($s['FECHA_FIN']) ? substr($s['FECHA_FIN'], 0, 10) : '' ?></td>
        <td data-label="Estado"><?= badgeEstado($s['ESTADO'] ?? '') ?></td>
        <td class="actions-cell">
          <a href="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/ver" class="btn btn-outline btn-sm">Ver</a>

          <?php if (($tipo ?? '') === 'pendientes' && ($s['ESTADO'] ?? '') === 'APROBADO_JEFE'): ?>
            <a href="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/ver" class="btn btn-green btn-sm">Gestionar</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>