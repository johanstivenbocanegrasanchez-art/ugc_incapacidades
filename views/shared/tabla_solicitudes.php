<?php
use Core\Config;

require_once __DIR__ . '/badge_estado.php';
$baseUrl = Config::baseUrl();
?>
<?php if (empty($filas)): ?>
  <div class="empty-state">
    <p>📋 No hay solicitudes para mostrar.</p>
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
        <td data-label="Tipo"><?= htmlspecialchars($tipos[$s['TIPO_SOLICITUD']] ?? $s['TIPO_SOLICITUD']) ?> <?= !empty($s['RUTA_COMPROBANTE']) ? '<span title="Tiene PDF adjunto">📎</span>' : '' ?></td>
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
