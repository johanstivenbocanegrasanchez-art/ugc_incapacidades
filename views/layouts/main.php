<?php
use Core\Config;
use Core\Flash;
use Core\Session;
use Core\Security;
use Config\Oracle;

$user  = $user ?? Session::getUser();
$flash = Flash::get();
$rolesLabel = [ROL_ADMIN => 'Administrador', ROL_RRHH => 'Talento Humano', ROL_JEFE => 'Jefe Inmediato', ROL_EMPLEADO => 'Empleado'];
$rolLabel   = $rolesLabel[$user['rol'] ?? ''] ?? 'Usuario';
$baseUrl    = Config::baseUrl();
$cssUrl     = $baseUrl . '/public/css/ugc.css';
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= htmlspecialchars(Config::appName()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $cssUrl ?>">
</head>
<body>
<header class="ugc-header">
  <button class="menu-toggle" aria-label="Menú" onclick="document.querySelector('.ugc-header nav').classList.toggle('nav-open')">
    <span></span><span></span><span></span>
  </button>
  <svg width="42" height="42" viewBox="0 0 80 80" fill="none" aria-label="UGC">
    <rect width="80" height="80" rx="10" fill="rgba(255,255,255,.15)"/>
    <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="23" font-weight="800" font-family="Inter,Arial">UGC</text>
  </svg>
  <div class="brand">UNIVERSIDAD<small>La Gran Colombia</small></div>
  <nav>
    <a href="<?= $baseUrl ?>/dashboard">Inicio</a>
    <?php if (in_array($user['rol'] ?? '', [ROL_ADMIN, ROL_RRHH, ROL_JEFE], true)): ?>
      <a href="<?= $baseUrl ?>/solicitudes">Todas las solicitudes</a>
    <?php endif; ?>
    <?php if (in_array($user['rol'] ?? '', [ROL_EMPLEADO, ROL_JEFE], true)): ?>
      <a href="<?= $baseUrl ?>/solicitud/crear">+ Nueva solicitud</a>
    <?php endif; ?>
  </nav>
  <div class="user-chip">
    <?= htmlspecialchars($user['nombre'] ?? '') ?>
    <span class="user-role"><?= $rolLabel ?></span>
  </div>
  <form action="<?= $baseUrl ?>/logout" method="post">
    <?= Security::csrfField() ?>
    <button class="logout" type="submit">Salir</button>
  </form>
</header>

<?php if (Config::isDev() && !Oracle::getInstance()->estaDisponible()): ?>
<div class="dev-banner">
  ⚠️ <strong>Modo sin BD:</strong> Oracle no disponible — la extensión OCI8 no está instalada o no hay conexión. Las operaciones usarán datos vacíos.
</div>
<?php endif; ?>

<main class="ugc-wrap">
  <?php if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] === 'success' ? 'ok' : 'err' ?> animate-fade-down">
      <?= $flash['type'] === 'success' ? 'Exito:' : 'Error:' ?> <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>
  <?= $content ?>
</main>

<footer class="ugc-footer">
  <div class="footer-content">
    <div class="footer-brand">
      <span class="footer-logo">UGC</span>
      <span class="footer-name">Universidad La Gran Colombia</span>
    </div>
    <div class="footer-links">
      <a href="<?= $baseUrl ?>/dashboard">Inicio</a>
      <a href="<?= $baseUrl ?>/dashboard">Mis Solicitudes</a>
      <a href="<?= $baseUrl ?>/logout">Cerrar Sesion</a>
    </div>
    <div class="footer-copy">
      &copy; <?= date('Y') ?> Sistema de Solicitudes. Todos los derechos reservados.
    </div>
  </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.ugc-table tbody tr').forEach((r,i)=>{
    r.style.cssText='opacity:0;transform:translateY(10px);transition:opacity .3s ease,transform .3s ease';
    setTimeout(()=>{r.style.opacity='1';r.style.transform='none';},50+i*40);
  });
  document.querySelectorAll('.stat-card').forEach((c,i)=>{
    c.style.cssText='opacity:0;transform:translateY(14px);transition:opacity .35s ease,transform .35s ease';
    setTimeout(()=>{c.style.opacity='1';c.style.transform='none';},80+i*70);
  });
  // Cerrar menú móvil al hacer clic en un enlace
  document.querySelectorAll('.ugc-header nav a').forEach(a=>{
    a.addEventListener('click',()=>document.querySelector('.ugc-header nav').classList.remove('nav-open'));
  });
});
</script>
</body>
</html>
