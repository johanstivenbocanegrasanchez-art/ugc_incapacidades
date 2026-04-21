<?php
use Core\Config;

require_once __DIR__ . '/badge_estado.php';
$baseUrl = Config::baseUrl();
?>
<?php if (empty($filas)): ?>
  <div class="empty-state">
    <p>No hay solicitudes para mostrar.</p>
  </div>
<?php else: ?>
<div class="ugc-table-wrap">
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
    <?php foreach ($filas as $s): ?>
      <tr>
        <td data-label="#"><?= $s['ID'] ?></td>
        <td data-label="Empleado"><?= htmlspecialchars($s['NIT_EMPLEADO']) ?></td>
        <td data-label="Tipo"><?= htmlspecialchars($tipos[$s['TIPO_SOLICITUD']] ?? $s['TIPO_SOLICITUD']) ?> <?= !empty($s['RUTA_COMPROBANTE']) ? '<span class="icon-attachment" title="Tiene PDF adjunto"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg></span>' : '' ?></td>
        <td data-label="Inicio"><?= substr($s['FECHA_INICIO'], 0, 10) ?></td>
        <td data-label="Fin"><?= substr($s['FECHA_FIN'], 0, 10) ?></td>
        <td data-label="Estado"><?= badgeEstado($s['ESTADO']) ?></td>
        <td>
          <a href="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/ver" class="btn btn-outline btn-sm">Ver</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
