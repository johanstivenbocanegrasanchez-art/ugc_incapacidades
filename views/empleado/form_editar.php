<?php
use Core\Config;
use Core\Security;

if (!function_exists('normalizarFechaInputUGC')) {
    function normalizarFechaInputUGC($valor)
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_object($valor)) {
            if (method_exists($valor, '__toString')) {
                $valor = (string)$valor;
            } elseif (property_exists($valor, 'date')) {
                $valor = $valor->date;
            } else {
                return '';
            }
        }

        if (is_array($valor)) {
            if (isset($valor['date'])) {
                $valor = $valor['date'];
            } else {
                return '';
            }
        }

        $valor = trim((string)$valor);
        if ($valor === '') {
            return '';
        }

        // yyyy-mm-dd o yyyy-mm-dd hh:mm:ss
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $valor, $m)) {
            return substr($m[0], 0, 10);
        }

        // dd/mm/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $valor, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        // dd-mm-yyyy
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $valor, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }

        // dd/mm/yy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{2})$/', $valor, $m)) {
            $anio = (int)$m[3];
            $anio = $anio >= 70 ? '19' . $m[3] : '20' . $m[3];
            return $anio . '-' . $m[2] . '-' . $m[1];
        }

        // dd-mm-yy
        if (preg_match('/^(\d{2})-(\d{2})-(\d{2})$/', $valor, $m)) {
            $anio = (int)$m[3];
            $anio = $anio >= 70 ? '19' . $m[3] : '20' . $m[3];
            return $anio . '-' . $m[2] . '-' . $m[1];
        }

        // dd-MON-yy o dd/MON/yy
        if (preg_match('/^(\d{1,2})[-\/ ]([A-Za-z]{3})[-\/ ](\d{2,4})$/', $valor, $m)) {
            $meses = [
                'JAN' => '01', 'FEB' => '02', 'MAR' => '03', 'APR' => '04',
                'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AUG' => '08',
                'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DEC' => '12'
            ];

            $dia = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $mes = strtoupper($m[2]);
            $anio = $m[3];

            if (strlen($anio) === 2) {
                $anio = ((int)$anio >= 70 ? '19' : '20') . $anio;
            }

            if (isset($meses[$mes])) {
                return $anio . '-' . $meses[$mes] . '-' . $dia;
            }
        }

        $ts = strtotime($valor);
        return $ts ? date('Y-m-d', $ts) : '';
    }
}

if (!function_exists('diasEntreFechasUGC')) {
    function diasEntreFechasUGC($inicio, $fin)
    {
        if (empty($inicio) || empty($fin)) {
            return '';
        }

        try {
            $d1 = new DateTime($inicio);
            $d2 = new DateTime($fin);

            if ($d2 < $d1) {
                return '';
            }

            return $d1->diff($d2)->days + 1;
        } catch (Throwable $e) {
            return '';
        }
    }
}

$esAprendiz = $esAprendiz ?? false;
$jefes      = $jefes ?? [];
$tipos      = $tipos ?? [];
$hoy        = $hoy ?? date('Y-m-d');
$s          = $solicitud ?? [];
$baseUrl    = Config::baseUrl();

$fechaInicioValue = normalizarFechaInputUGC($s['FECHA_INICIO'] ?? '');
$fechaFinValue    = normalizarFechaInputUGC($s['FECHA_FIN'] ?? '');

$duracionDiasValue = $s['DURACION_DIAS'] ?? '';
if ($duracionDiasValue === '' || $duracionDiasValue === null) {
    $duracionDiasValue = diasEntreFechasUGC($fechaInicioValue, $fechaFinValue);
}

$duracionHorasValue = $s['DURACION_HORAS'] ?? '';
if (($duracionHorasValue === '' || $duracionHorasValue === null) && $duracionDiasValue !== '') {
    $duracionHorasValue = (float)$duracionDiasValue * 8;
}

$horasEsperadas = ($duracionDiasValue !== '' && $duracionDiasValue !== null)
    ? ((float)$duracionDiasValue * 8)
    : '';

$horasAutoInicial = false;

if ($duracionHorasValue === '' || $duracionHorasValue === null) {
    $horasAutoInicial = true;
} elseif ($horasEsperadas !== '' && (float)$duracionHorasValue == (float)$horasEsperadas) {
    $horasAutoInicial = true;
}

$minFechaInicio = $hoy;
if (!empty($fechaInicioValue) && $fechaInicioValue < $hoy) {
    $minFechaInicio = $fechaInicioValue;
}

$minFechaFin = !empty($fechaInicioValue) ? $fechaInicioValue : $hoy;
if (!empty($fechaFinValue) && $fechaFinValue < $minFechaFin) {
    $minFechaFin = $fechaFinValue;
}
?>

<div class="page-header animate-fade-down">
  <h1 class="page-title">Editar Solicitud #<?= htmlspecialchars($s['ID'] ?? '') ?></h1>
</div>

<div class="form-card animate-fade-up">
  <?php if (!$esAprendiz && !empty($user['nombre_jefe'])): ?>
    <div class="info-jefe">
      <div><strong>Jefe asignado:</strong> <?= htmlspecialchars($user['nombre_jefe']) ?></div>
    </div>
  <?php endif; ?>

  <form method="post" action="<?= $baseUrl ?>/solicitud/<?= htmlspecialchars($s['ID'] ?? '') ?>/editar" id="formEditar" enctype="multipart/form-data">
    <?= Security::csrfField() ?>

    <?php if ($esAprendiz): ?>
      <div class="form-group">
        <label>Jefe responsable *</label>
        <select name="nit_jefe_seleccionado" required>
          <option value="">-- Selecciona un jefe --</option>
          <?php foreach ($jefes as $j): ?>
            <option
              value="<?= htmlspecialchars($j['NIT']) ?>"
              <?= (($j['NIT'] ?? '') == ($s['NIT_JEFE'] ?? '')) ? 'selected' : '' ?>
            >
              <?= htmlspecialchars($j['NOMBRE_COMPLETO']) ?> — CC <?= htmlspecialchars($j['CENTRO_COSTO']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

    <div class="form-group">
      <label>Tipo de solicitud *</label>
      <select name="tipo_solicitud" required>
        <?php foreach ($tipos as $val => $lbl): ?>
          <option value="<?= htmlspecialchars($val) ?>" <?= (($s['TIPO_SOLICITUD'] ?? '') === $val) ? 'selected' : '' ?>>
            <?= htmlspecialchars($lbl) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Fecha inicio *</label>
        <input
          type="date"
          name="fecha_inicio"
          id="fecha_inicio"
          value="<?= htmlspecialchars($fechaInicioValue) ?>"
          min="<?= htmlspecialchars($minFechaInicio) ?>"
          required
        />
        <span class="field-hint">No puede ser anterior a hoy</span>
      </div>

      <div class="form-group">
        <label>Fecha fin *</label>
        <input
          type="date"
          name="fecha_fin"
          id="fecha_fin"
          value="<?= htmlspecialchars($fechaFinValue) ?>"
          min="<?= htmlspecialchars($minFechaFin) ?>"
          required
        />
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
          value="<?= htmlspecialchars((string)$duracionHorasValue) ?>"
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
          value="<?= htmlspecialchars((string)$duracionDiasValue) ?>"
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
      <textarea name="observaciones" rows="4"><?= htmlspecialchars($s['OBSERVACIONES'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label>Documento adjunto (PDF, máx. 5MB)</label>

      <?php if (!empty($s['RUTA_COMPROBANTE'])): ?>
        <div class="archivo-actual">
          <div class="archivo-adjunto">
            <span class="archivo-icono">📑</span>
            <div class="archivo-info">
              <div class="archivo-nombre"><?= htmlspecialchars(basename($s['RUTA_COMPROBANTE'])) ?></div>
              <div class="archivo-actual-label">Archivo actual</div>
            </div>
            <a href="<?= $baseUrl ?>/archivo/<?= htmlspecialchars($s['ID']) ?>" target="_blank" class="archivo-ver">Ver PDF</a>
          </div>

          <label class="reemplazar-label">
            <input type="checkbox" name="reemplazar_pdf" value="1" id="chkReemplazar">
            <span>Reemplazar archivo</span>
          </label>
        </div>
      <?php endif; ?>

      <div class="file-upload-container" id="uploadContainer" <?= !empty($s['RUTA_COMPROBANTE']) ? 'style="display:none;"' : '' ?>>
        <input
          type="file"
          name="documento_pdf"
          id="documento_pdf"
          accept=".pdf,application/pdf"
          <?= empty($s['RUTA_COMPROBANTE']) ? 'required' : '' ?>
        />
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
      <button type="submit" class="btn btn-green">Guardar cambios</button>
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
  var horasAuto = <?= $horasAutoInicial ? 'true' : 'false' ?>;

  var ini = document.getElementById('fecha_inicio');
  var fin = document.getElementById('fecha_fin');
  var horas = document.getElementById('duracion_horas');
  var dias = document.getElementById('duracion_dias');
  var form = document.getElementById('formEditar');

  function limpiarCamposDuracion() {
    dias.value = '';
    if (horasAuto) {
      horas.value = '';
    }
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

    if (horasAuto) {
      horas.value = diffDays * HORAS_POR_DIA;
    }
  }

  if (horas) {
    horas.addEventListener('input', function () {
      horasAuto = false;
    });
  }

  if (ini && fin) {
    ini.addEventListener('change', calcularDuracion);
    fin.addEventListener('change', calcularDuracion);
    ini.addEventListener('input', calcularDuracion);
    fin.addEventListener('input', calcularDuracion);
    calcularDuracion();
  }

  if (form) {
    form.addEventListener('submit', function (e) {
      if (!ini.value) {
        e.preventDefault();
        alert('Debes seleccionar la fecha de inicio.');
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

  var chkReemplazar = document.getElementById('chkReemplazar');
  var uploadContainer = document.getElementById('uploadContainer');
  var fileInput = document.getElementById('documento_pdf');
  var previewContainer = document.getElementById('pdf-preview-container');
  var fileError = document.getElementById('file-error');
  var pdfFilename = document.getElementById('pdf-filename');
  var pdfSize = document.getElementById('pdf-size');
  var pdfIframe = document.getElementById('pdf-iframe');
  var removeBtn = document.getElementById('remove-pdf');

  function hideError() {
    fileError.style.display = 'none';
    fileError.textContent = '';
  }

  function clearFile() {
    if (!fileInput) return;
    fileInput.value = '';
    previewContainer.style.display = 'none';
    pdfIframe.src = '';
    hideError();
  }

  if (chkReemplazar && uploadContainer && fileInput) {
    chkReemplazar.addEventListener('change', function () {
      if (this.checked) {
        uploadContainer.style.display = 'block';
        fileInput.required = true;
      } else {
        uploadContainer.style.display = 'none';
        fileInput.required = false;
        clearFile();
      }
    });
  }

  const MAX_FILE_SIZE = 5 * 1024 * 1024;

  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    var k = 1024;
    var sizes = ['Bytes', 'KB', 'MB'];
    var i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  function showError(msg) {
    fileError.textContent = msg;
    fileError.style.display = 'block';
    fileInput.value = '';
    previewContainer.style.display = 'none';
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