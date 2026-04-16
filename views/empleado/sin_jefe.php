<?php
use Core\Config;

$baseUrl = Config::baseUrl();
?>
<div class="ugc-wrap">
  <div class="flash flash-err animate-fade-down">No tienes un jefe inmediato asignado en el sistema. Contacta a Talento Humano para que actualicen tu informacion.</div>
  <a href="<?= $baseUrl ?>/dashboard" class="btn btn-outline">Volver al inicio</a>
</div>
