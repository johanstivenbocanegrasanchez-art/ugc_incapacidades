<?php
use Core\Config;
use Core\Security;

$esAprendiz = $esAprendiz ?? false;
$jefes      = $jefes      ?? [];
$tipos      = $tipos      ?? [];
$hoy        = $hoy        ?? date('Y-m-d');
$s          = $solicitud;
$baseUrl    = Config::baseUrl();
?>
<div class="page-header animate-fade-down">
  <h1 class="page-title">Editar Solicitud #<?= $s['ID'] ?></h1>
  <a href="<?= $baseUrl ?>/dashboard" class="btn btn-outline btn-sm">Volver</a>
</div>
<div class="form-card animate-fade-up">
  <form method="post" action="<?= $baseUrl ?>/solicitud/<?= $s['ID'] ?>/editar" id="formEditar">
    <?= Security::csrfField() ?>
    <?php if ($esAprendiz): ?>
    <div class="form-group">
      <label>Jefe responsable *</label>
      <select name="nit_jefe_seleccionado" required>
        <option value="">-- Selecciona un jefe --</option>
        <?php foreach ($jefes as $j): ?>
          <option value="<?= htmlspecialchars($j['NIT']) ?>" <?= $j['NIT'] === $s['NIT_JEFE'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($j['NOMBRE_COMPLETO']) ?> — CC <?= htmlspecialchars($j['CENTRO_COSTO']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="form-group">
      <label>Tipo de solicitud *</label>
      <select name="tipo_solicitud" required>
        <?php foreach ($tipos as $val => $lbl): ?><option value="<?= $val ?>" <?= $s['TIPO_SOLICITUD'] === $val ? 'selected' : '' ?>><?= $lbl ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Fecha inicio *</label><input type="date" name="fecha_inicio" id="fecha_inicio" value="<?= substr($s['FECHA_INICIO'], 0, 10) ?>" min="<?= $hoy ?>" required/></div>
      <div class="form-group"><label>Fecha fin *</label><input type="date" name="fecha_fin" id="fecha_fin" value="<?= substr($s['FECHA_FIN'], 0, 10) ?>" min="<?= $hoy ?>" required/></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Duración en horas</label><input type="number" name="duracion_horas" value="<?= $s['DURACION_HORAS'] ?? '' ?>" min="0" max="999" step="0.5"/></div>
      <div class="form-group"><label>Duración en días</label><input type="number" name="duracion_dias" value="<?= $s['DURACION_DIAS'] ?? '' ?>" min="0" max="365" step="0.5"/></div>
    </div>
    <div class="form-group"><label>Observaciones</label><textarea name="observaciones" rows="4"><?= htmlspecialchars($s['OBSERVACIONES'] ?? '') ?></textarea></div>
    <div class="form-actions">
      <button type="submit" class="btn btn-green">Guardar cambios</button>
      <a href="<?= $baseUrl ?>/dashboard" class="btn btn-gray">Cancelar</a>
    </div>
  </form>
</div>
<script>
(function(){
  var hoy=<?= json_encode($hoy) ?>,ini=document.getElementById('fecha_inicio'),fin=document.getElementById('fecha_fin');
  ini.addEventListener('change',function(){fin.min=ini.value||hoy;if(fin.value&&fin.value<ini.value)fin.value=ini.value;});
  document.getElementById('formEditar').addEventListener('submit',function(e){
    if(fin.value&&fin.value<ini.value){e.preventDefault();alert('La fecha fin no puede ser anterior al inicio.');}
  });
})();
</script>
