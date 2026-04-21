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
  <form method="post" action="<?= $baseUrl ?>/solicitud/crear" id="formSolicitud" enctype="multipart/form-data">
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
    <div class="form-group">
      <label>Documento adjunto (PDF, máx. 5MB) *</label>
      <div class="file-upload-container">
        <input type="file" name="documento_pdf" id="documento_pdf" accept=".pdf,application/pdf" required />
        <div class="file-upload-hint">
          <span class="hint-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg></span>
          <span>Formato permitido: PDF. Tamaño máximo: 5MB</span>
        </div>
      </div>
      <div id="pdf-preview-container" class="pdf-preview-container" style="display:none;">
        <div class="pdf-preview-header">
          <span class="pdf-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><polyline points="9 15 12 12 15 15"></polyline></svg></span>
          <span id="pdf-filename" class="pdf-filename"></span>
          <span id="pdf-size" class="pdf-size"></span>
          <button type="button" id="remove-pdf" class="remove-pdf-btn" title="Eliminar archivo">&times;</button>
        </div>
        <div class="pdf-preview-content">
          <iframe id="pdf-iframe" src="" frameborder="0"></iframe>
        </div>
      </div>
      <div id="file-error" class="file-error" style="display:none;"></div>
    </div>
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

  // File upload handling
  const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
  const ALLOWED_TYPES = ['application/pdf', '.pdf'];

  var fileInput = document.getElementById('documento_pdf');
  var previewContainer = document.getElementById('pdf-preview-container');
  var fileError = document.getElementById('file-error');
  var pdfFilename = document.getElementById('pdf-filename');
  var pdfSize = document.getElementById('pdf-size');
  var pdfIframe = document.getElementById('pdf-iframe');
  var removeBtn = document.getElementById('remove-pdf');

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB', 'GB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function showError(msg) {
    fileError.textContent = msg;
    fileError.style.display = 'block';
    fileInput.value = '';
    previewContainer.style.display = 'none';
  }

  function hideError() {
    fileError.style.display = 'none';
    fileError.textContent = '';
  }

  function clearFile() {
    fileInput.value = '';
    previewContainer.style.display = 'none';
    pdfIframe.src = '';
    hideError();
  }

  fileInput.addEventListener('change', function() {
    hideError();
    var file = this.files[0];
    if (!file) {
      clearFile();
      return;
    }

    // Validate file type
    if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
      showError('Error: Solo se permiten archivos PDF.');
      return;
    }

    // Validate file size
    if (file.size > MAX_FILE_SIZE) {
      showError('Error: El archivo excede el tamaño máximo de 5MB.');
      return;
    }

    // Show preview
    var fileUrl = URL.createObjectURL(file);
    pdfFilename.textContent = file.name;
    pdfSize.textContent = formatFileSize(file.size);
    pdfIframe.src = fileUrl;
    previewContainer.style.display = 'block';
  });

  removeBtn.addEventListener('click', clearFile);
})();
</script>
