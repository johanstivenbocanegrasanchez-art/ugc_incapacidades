<?php 
use Core\Config;
use Core\Security;

require_once __DIR__ . '/badge_estado.php';
$baseUrl = Config::baseUrl();
?>

<div class="page-header animate-fade-down">
  <h1 class="page-title">Solicitud #<?= $solicitud['ID'] ?></h1>
</div>

<div class="form-card animate-fade-up">

  <div class="form-group">
    <label>Estado</label>
    <div><?= badgeEstado($solicitud['ESTADO']) ?></div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Empleado (NIT)</label>
      <input type="text" value="<?= htmlspecialchars($solicitud['NIT_EMPLEADO']) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Jefe (NIT)</label>
      <input type="text" value="<?= htmlspecialchars($solicitud['NIT_JEFE']) ?>" readonly>
    </div>
  </div>

  <div class="form-group">
    <label>Tipo</label>
    <input type="text" value="<?= htmlspecialchars($tipos[$solicitud['TIPO_SOLICITUD']] ?? $solicitud['TIPO_SOLICITUD']) ?>" readonly>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Fecha inicio</label>
      <input type="text" value="<?= substr($solicitud['FECHA_INICIO'], 0, 10) ?>" readonly>
    </div>

    <div class="form-group">
      <label>Fecha fin</label>
      <input type="text" value="<?= substr($solicitud['FECHA_FIN'], 0, 10) ?>" readonly>
    </div>
  </div>

  <div class="form-row">
    <div class="form-group">
      <label>Duración en horas</label>
      <input type="text" value="<?= $solicitud['DURACION_HORAS'] ?? '—' ?>" readonly>
    </div>

    <div class="form-group">
      <label>Duración en días</label>
      <input type="text" value="<?= $solicitud['DURACION_DIAS'] ?? '—' ?>" readonly>
    </div>
  </div>

  <div class="form-group">
    <label>Observaciones</label>
    <textarea rows="4" readonly><?= htmlspecialchars($solicitud['OBSERVACIONES'] ?? '') ?></textarea>
  </div>

  <?php if (!empty($solicitud['RUTA_COMPROBANTE'])): ?>
    <div class="form-group">
      <label>Documento adjunto</label>
      <div class="archivo-adjunto" style="margin:0;">
        <span class="archivo-icono">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <polyline points="9 15 12 12 15 15"></polyline>
          </svg>
        </span>
        <div class="archivo-info">
          <div class="archivo-nombre"><?= htmlspecialchars(basename($solicitud['RUTA_COMPROBANTE'])) ?></div>
        </div>
        <a href="<?= $baseUrl ?>/archivo/<?= $solicitud['ID'] ?>" target="_blank" class="archivo-ver">Ver PDF</a>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['OBSERVACION_JEFE'])): ?>
    <div class="form-group">
      <label>Observación del jefe</label>
      <textarea rows="3" readonly><?= htmlspecialchars($solicitud['OBSERVACION_JEFE']) ?></textarea>
    </div>
  <?php endif; ?>

  <?php if (!empty($solicitud['OBSERVACION_RRHH'])): ?>
    <div class="form-group">
      <label>Observación de RRHH</label>
      <textarea rows="3" readonly><?= htmlspecialchars($solicitud['OBSERVACION_RRHH']) ?></textarea>
    </div>
  <?php endif; ?>

  <?php if ($solicitud['ESTADO'] === 'PENDIENTE_JEFE' && in_array($user['rol'], [ROL_JEFE, ROL_ADMIN], true) && $solicitud['NIT_JEFE'] === $user['cedula']): ?>
    <form method="post" action="<?= $baseUrl ?>/solicitud/<?= $solicitud['ID'] ?>/jefe">
      <?= Security::csrfField() ?>

      <div class="form-group">
        <label>Gestión de Jefe</label>
        <textarea name="observacion_jefe" rows="3" placeholder="Opcional..."></textarea>
      </div>

      <div class="form-actions">
        <button name="accion" value="aprobar" class="btn btn-green">Aprobar</button>
        <button name="accion" value="rechazar" class="btn btn-red">Rechazar</button>
      </div>
    </form>
  <?php endif; ?>

  <?php if ($solicitud['ESTADO'] === 'APROBADO_JEFE' && in_array($user['rol'], [ROL_RRHH, ROL_ADMIN], true)): ?>
    <form method="post" action="<?= $baseUrl ?>/solicitud/<?= $solicitud['ID'] ?>/rrhh">
      <?= Security::csrfField() ?>

      <div class="form-group">
        <label>Gestión de Talento Humano</label>
        <textarea name="observacion_rrhh" rows="3" placeholder="Opcional..."></textarea>
      </div>

      <div class="form-actions">
        <button name="accion" value="aprobar" class="btn btn-green">Aprobar</button>
        <button name="accion" value="rechazar" class="btn btn-red">Rechazar</button>
      </div>
    </form>
  <?php endif; ?>

  <div class="volver-wrap">
    <a href="<?= $baseUrl ?>/dashboard" class="btn btn-outline btn-volver">Volver</a>
  </div>

</div>