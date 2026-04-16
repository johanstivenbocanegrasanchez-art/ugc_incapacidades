<?php
use Core\Config;
use Core\Security;

$baseUrl = Config::baseUrl();
$cssUrl  = $baseUrl . '/public/css/ugc.css';
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title><?= htmlspecialchars(Config::appName()) ?> – Acceso</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $cssUrl ?>">
</head>
<body>
<header class="ugc-header">
  <svg width="42" height="42" viewBox="0 0 80 80" fill="none" aria-label="UGC">
    <rect width="80" height="80" rx="10" fill="rgba(255,255,255,.15)"/>
    <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="23" font-weight="800" font-family="Inter,Arial">UGC</text>
  </svg>
  <div class="brand">UNIVERSIDAD<small>La Gran Colombia</small></div>
</header>
<?= $content ?>
</body>
</html>
