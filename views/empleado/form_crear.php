<?php
use Core\Config;
use Core\Security;

$esAprendiz = $esAprendiz ?? false;
$jefes      = $jefes      ?? [];
$tipos      = $tipos      ?? [];
$hoy        = $hoy        ?? date('Y-m-d');
$baseUrl    = Config::baseUrl();
?>
<div class="page-header animate-fade-down">
  <h1 class="page-title">Nueva Solicitud de Permiso</h1>
  <a href="<?= $baseUrl ?>/dashboard" class="btn btn-outline btn-sm">Volver</a>
</div>
<div class="form-card animate-fade-up">
  <?php if (!$esAprendiz && !empty($user['nombre_jefe'])): ?>
  <div class="info-jefe">
    <div><strong>Jefe asignado:</strong> <?= htmlspecialchars($user['nombre_jefe']) ?></div>
  </div>
  <?php endif; ?>
  <form method="post" action="<?= $baseUrl ?>/solicitud/crear" id="formSolicitud">
    <?= Security::csrfField() ?>
    <?php if ($esAprendiz): ?>
    <div class="form-group">
      <label>Jefe responsable * <span class="badge-info">Aprendiz – elige tu jefe</span></label>
      <select name="nit_jefe_seleccionado" required>
        <option value="">-- Selecciona un jefe --</option>
        <?php foreach ($jefes as $j): ?>
          <option value="<?= htmlspecialchars($j['NIT']) ?>"><?= htmlspecialchars($j['NOMBRE_COMPLETO']) ?> — CC <?= htmlspecialchars($j['CENTRO_COSTO']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group">
      <label>Tipo de solicitud *</label>
      <select name="tipo_solicitud" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($tipos as $val => $lbl): ?><option value="<?= $val ?>"><?= $lbl ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Fecha inicio *</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" min="<?= $hoy ?>" required/>
        <span class="field-hint">No puede ser anterior a hoy</span>
      </div>
      <div class="form-group">
        <label>Fecha fin *</label>
        <input type="date" name="fecha_fin" id="fecha_fin" min="<?= $hoy ?>" required/>
        <span class="field-hint">No puede ser anterior a la fecha inicio</span>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Duración en horas</label><input type="number" name="duracion_horas" min="0" max="999" step="0.5" placeholder="Ej. 4"/></div>
      <div class="form-group"><label>Duración en días</label><input type="number" name="duracion_dias" min="0" max="365" step="0.5" placeholder="Ej. 2"/></div>
    </div>
    <div class="form-group"><label>Observaciones</label><textarea name="observaciones" rows="4" placeholder="Describe el motivo..."></textarea></div>
    <div class="form-actions">
      <button type="submit" class="btn btn-green">Enviar solicitud</button>
      <a href="<?= $baseUrl ?>/dashboard" class="btn btn-gray">Cancelar</a>
    </div>
  </form>
</div>
<script>
(function(){
  var hoy=<?= json_encode($hoy) ?>,ini=document.getElementById('fecha_inicio'),fin=document.getElementById('fecha_fin');
  ini.min=hoy;fin.min=hoy;
  ini.addEventListener('change',function(){fin.min=ini.value||hoy;if(fin.value&&fin.value<ini.value)fin.value=ini.value;});
  document.getElementById('formSolicitud').addEventListener('submit',function(e){
    if(ini.value<hoy){e.preventDefault();alert('La fecha de inicio no puede ser anterior a hoy.');return;}
    if(fin.value&&fin.value<ini.value){e.preventDefault();alert('La fecha fin no puede ser anterior al inicio.');}
  });
})();
</script>
