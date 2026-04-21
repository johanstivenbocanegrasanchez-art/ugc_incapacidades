<?php
use Core\Config;
use Core\Security;

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
        <circle cx="12" cy="12" r="10"/>
        <polyline points="12 6 12 12 16 14"/>
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
        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
      </svg>
    </div>
    <div class="num">Jefe</div>
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
        <th>Empleado (NIT)</th>
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
        <td data-label="Empleado"><?= htmlspecialchars($s['NIT_EMPLEADO'] ?? $user['cedula'] ?? '') ?></td>
        <td data-label="Tipo">
          <?= htmlspecialchars($tipos[$s['TIPO_SOLICITUD']] ?? $s['TIPO_SOLICITUD'] ?? '') ?>
          <?= !empty($s['RUTA_COMPROBANTE']) ? '<span class="icon-attachment" title="Tiene PDF adjunto"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></span>' : '' ?>
        </td>
        <td data-label="Inicio"><?= !empty($s['FECHA_INICIO']) ? substr($s['FECHA_INICIO'], 0, 10) : '' ?></td>
        <td data-label="Fin"><?= !empty($s['FECHA_FIN']) ? substr($s['FECHA_FIN'], 0, 10) : '' ?></td>
        <td data-label="Estado"><?= badgeEstado($s['ESTADO'] ?? '') ?></td>
        <td class="actions-cell">
          <a href="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/ver" class="btn btn-outline btn-sm">Ver</a>

          <?php if (($s['ESTADO'] ?? '') === 'PENDIENTE_JEFE'): ?>
            <a href="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/ver" class="btn btn-green btn-sm">Gestionar</a>
          <?php endif; ?>

          <?php if (($tipo ?? '') === 'mis_solicitudes' && ($s['ESTADO'] ?? '') === 'PENDIENTE_JEFE'): ?>
            <a href="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/editar" class="btn btn-gray btn-sm">Editar</a>
            <form method="post" action="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/eliminar" class="inline-form" onsubmit="return confirm('¿Eliminar?')">
              <?= Security::csrfField() ?>
              <button type="submit" class="btn btn-red btn-sm">Eliminar</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>