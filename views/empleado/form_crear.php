<?php
use Core\Config;
use Core\Security;

$esAprendiz = $esAprendiz ?? false;
$jefes      = $jefes ?? [];
$tipos      = $tipos ?? [];
$hoy        = $hoy ?? date('Y-m-d');
$baseUrl    = Config::baseUrl();
?>
<div class="page-header animate-fade-down">
  <h1 class="page-title">Nueva Solicitud de Permiso</h1>
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
            <option value="<?= htmlspecialchars($j['NIT']) ?>">
              <?= htmlspecialchars($j['NOMBRE_COMPLETO']) ?> — CC <?= htmlspecialchars($j['CENTRO_COSTO']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label>Tipo de solicitud *</label>
      <select name="tipo_solicitud" required>
        <option value="">-- Selecciona --</option>
        <?php foreach ($tipos as $val => $lbl): ?>
          <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Fecha inicio *</label>
        <input type="date" name="fecha_inicio" id="fecha_inicio" min="<?= htmlspecialchars($hoy) ?>" required />
        <span class="field-hint">No puede ser anterior a hoy</span>
      </div>

      <div class="form-group">
        <label>Fecha fin *</label>
        <input type="date" name="fecha_fin" id="fecha_fin" min="<?= htmlspecialchars($hoy) ?>" required />
        <span class="field-hint">No puede ser anterior a la fecha inicio</span>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Duración en horas</label>
        <input
          type="number"
          name="duracion_horas"
          id="duracion_horas"
          min="0"
          max="9999"
          step="0.5"
          placeholder="Ej. 8"
        />
      </div>

      <div class="form-group">
        <label>Duración en días</label>
        <input
          type="number"
          name="duracion_dias"
          id="duracion_dias"
          min="0"
          max="365"
          step="1"
          placeholder="Ej. 1"
          readonly
        />
      </div>
    </div>

    <div class="form-group">
      <label>Observaciones</label>
      <textarea name="observaciones" rows="4" placeholder="Describe el motivo..."></textarea>
    </div>

    <div class="form-group">
      <label>Documento adjunto (PDF, máx. 5MB) *</label>

      <div class="file-upload-container">
        <input type="file" name="documento_pdf" id="documento_pdf" accept=".pdf,application/pdf" required />
        <div class="file-upload-hint">
          <span class="hint-icon">📄</span>
          <span>Formato permitido: PDF. Tamaño máximo: 5MB</span>
        </div>
      </div>

      <div id="pdf-preview-container" class="pdf-preview-container" style="display:none;">
        <div class="pdf-preview-header">
          <span class="pdf-icon">📑</span>
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

    <div style="margin-top:14px;">
      <a href="<?= $baseUrl ?>/dashboard" class="btn btn-outline btn-sm">Volver</a>
    </div>
  </form>
</div>

<script>
(function () {
  var hoy = <?= json_encode($hoy) ?>;
  var HORAS_POR_DIA = 8;

  var ini = document.getElementById('fecha_inicio');
  var fin = document.getElementById('fecha_fin');
  var horas = document.getElementById('duracion_horas');
  var dias = document.getElementById('duracion_dias');
  var form = document.getElementById('formSolicitud');

  function limpiarCamposDuracion() {
    dias.value = '';
    horas.value = '';
  }

  function calcularDuracion() {
    var fechaInicio = ini.value;
    var fechaFin = fin.value;

    if (!fechaInicio) {
      limpiarCamposDuracion();
      return;
    }

    fin.min = fechaInicio || hoy;

    if (!fechaFin) {
      fin.value = fechaInicio;
      fechaFin = fechaInicio;
    }

    if (fechaFin < fechaInicio) {
      fin.value = fechaInicio;
      fechaFin = fechaInicio;
    }

    var d1 = new Date(fechaInicio + 'T00:00:00');
    var d2 = new Date(fechaFin + 'T00:00:00');

    if (d2 < d1) {
      limpiarCamposDuracion();
      return;
    }

    var diffMs = d2 - d1;
    var diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24)) + 1;

    dias.value = diffDays;
    horas.value = diffDays * HORAS_POR_DIA;
  }

  if (ini && fin) {
    ini.addEventListener('change', calcularDuracion);
    fin.addEventListener('change', calcularDuracion);
    ini.addEventListener('input', calcularDuracion);
    fin.addEventListener('input', calcularDuracion);
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      if (!ini.value) {
        e.preventDefault();
        alert('Debes seleccionar la fecha de inicio.');
        return;
      }

      if (ini.value < hoy) {
        e.preventDefault();
        alert('La fecha de inicio no puede ser anterior a hoy.');
        return;
      }

      if (!fin.value) {
        fin.value = ini.value;
      }

      if (fin.value < ini.value) {
        e.preventDefault();
        alert('La fecha fin no puede ser anterior al inicio.');
        return;
      }

      calcularDuracion();
    });
  }

  const MAX_FILE_SIZE = 5 * 1024 * 1024;

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

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      hideError();

      var file = this.files[0];
      if (!file) {
        clearFile();
        return;
      }

      if (file.type !== 'application/pdf' && !file.name.toLowerCase().endsWith('.pdf')) {
        showError('Error: Solo se permiten archivos PDF.');
        return;
      }

      if (file.size > MAX_FILE_SIZE) {
        showError('Error: El archivo excede el tamaño máximo de 5MB.');
        return;
      }

      var fileUrl = URL.createObjectURL(file);
      pdfFilename.textContent = file.name;
      pdfSize.textContent = formatFileSize(file.size);
      pdfIframe.src = fileUrl;
      previewContainer.style.display = 'block';
    });
  }

  if (removeBtn) {
    removeBtn.addEventListener('click', clearFile);
  }
})();
</script>