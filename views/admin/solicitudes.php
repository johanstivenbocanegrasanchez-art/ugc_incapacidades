<?php
use Core\Config;

require_once __DIR__ . '/../shared/badge_estado.php';
$baseUrl = Config::baseUrl();
?>
<div class="page-header animate-fade-down" style="display:flex;justify-content:space-between;align-items:center;">
  <div>
    <h1 class="page-title"><?= htmlspecialchars($titulo) ?></h1>
    <p style="color:var(--muted);font-size:14px;margin-top:4px">
      <?= htmlspecialchars($subtitulo ?? 'Consulta detallada de solicitudes') ?>
    </p>
  </div>

  <div style="display:flex;gap:10px;">
    <a href="<?= $baseUrl ?>/dashboard" class="btn btn-green">Volver al dashboard</a>
    <a href="<?= $baseUrl ?>/exportar/todas/excel" class="btn btn-green">Descargar reporte Excel</a>
  </div>
</div>

<div class="stats-row animate-fade-up" style="grid-template-columns:repeat(3,1fr)">
  <div class="stat-card">
    <div class="stat-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
        <polyline points="14 2 14 8 20 8"/>
      </svg>
    </div>
    <div class="num"><?= count($solicitudes) ?></div>
    <div class="lbl">Solicitudes encontradas</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"/>
        <path d="M12 6v6l4 2"/>
      </svg>
    </div>
    <div class="num"><?= htmlspecialchars($nombreFiltro ?? 'General') ?></div>
    <div class="lbl">Filtro aplicado</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M12 1v22"/>
        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14.5a3.5 3.5 0 0 1 0 7H6"/>
      </svg>
    </div>
    <div class="num">Admin</div>
    <div class="lbl">Vista actual</div>
  </div>
</div>

<div class="section-header">
  <h2><?= htmlspecialchars($titulo) ?></h2>
</div>

<?php if (empty($solicitudes)): ?>
  <div class="empty-state animate-fade-up">
    <p>No hay solicitudes para mostrar en esta categoría.</p>
  </div>
<?php else: ?>
  <?php
    $filas = $solicitudes;
    require __DIR__ . '/../shared/tabla_solicitudes.php';
  ?>
<?php endif; ?>