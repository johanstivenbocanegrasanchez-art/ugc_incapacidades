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

  <div style="display:flex;align-items:center;gap:14px;flex:1;">
    <a href="<?= $baseUrl ?>/dashboard" class="logo-link" title="Ir al Inicio">
      <img src="<?= $baseUrl ?>/public/images/Logo%20ULGC.png" alt="Universidad La Gran Colombia" class="header-logo" height="50">
    </a>

    <div class="header-search">
      <form action="<?= $baseUrl ?>/dashboard" method="get">
        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="m20 20-3.5-3.5"></path>
        </svg>
        <input
          type="text"
          name="q"
          placeholder="Buscar solicitud, empleado o tipo..."
          value="<?= htmlspecialchars($q ?? $_GET['q'] ?? '') ?>"
        >
      </form>
    </div>
  </div>

  <nav>
  </nav>

  <!-- Notificaciones -->
  <div class="notificacion-wrap">
    <button class="notificacion-bell" id="notifBell" aria-label="Notificaciones">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
        <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
      </svg>
      <span class="notificacion-badge" id="notifBadge" data-count="0">0</span>
    </button>
    <div class="notificacion-dropdown" id="notifDropdown">
      <div class="notificacion-header">
        <h4>Notificaciones</h4>
        <button class="mark-all" id="markAllRead">Marcar todo leído</button>
      </div>
      <div class="notificacion-list" id="notifList">
        <div class="notificacion-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
          </svg>
          <p>No tienes notificaciones nuevas</p>
        </div>
      </div>
    </div>
  </div>

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
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
  <strong>Modo sin BD:</strong> Oracle no disponible — la extensión OCI8 no está instalada o no hay conexión. Las operaciones usarán datos vacíos.
</div>
<?php endif; ?>

<main class="ugc-wrap">
  <?php if ($flash): ?>
    <div
      class="flash flash-<?= $flash['type'] === 'success' ? 'ok' : 'err' ?> animate-fade-down"
      <?= $flash['type'] === 'success' ? 'data-auto-dismiss="true"' : '' ?>
    >
      <?= $flash['type'] === 'success' ? 'Exito:' : 'Error:' ?> <?= htmlspecialchars($flash['message']) ?>
    </div>
  <?php endif; ?>
  <?= $content ?>
</main>

<?php
// Determinar rol y rutas correctas según el usuario
$userRol = $user['rol'] ?? '';
$solicitudesUrl = match($userRol) {
    ROL_ADMIN => $baseUrl . '/admin/solicitudes',
    ROL_RRHH => $baseUrl . '/rrhh/solicitudes',
    ROL_JEFE => $baseUrl . '/jefe/solicitudes',
    ROL_EMPLEADO => $baseUrl . '/empleado/solicitudes',
    default => $baseUrl . '/dashboard'
};
$esAdminORrhh = in_array($userRol, [ROL_ADMIN, ROL_RRHH], true);
?>
<footer class="ugc-footer">
  <div class="footer-content">
    <div class="footer-brand">
      <span class="footer-logo">UGC</span>
      <span class="footer-name">Universidad La Gran Colombia</span>
    </div>
    <div class="footer-links">
      <a href="<?= $baseUrl ?>/dashboard">Inicio</a>
      <a href="<?= $solicitudesUrl ?>"><?= $esAdminORrhh ? 'Todas las Solicitudes' : 'Mis Solicitudes' ?></a>
      <form action="<?= $baseUrl ?>/logout" method="post" class="footer-logout-form">
        <?= Security::csrfField() ?>
        <button type="submit" class="footer-logout-btn">Cerrar Sesión</button>
      </form>
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

  document.querySelectorAll('.ugc-header nav a').forEach(a=>{
    a.addEventListener('click',()=>document.querySelector('.ugc-header nav').classList.remove('nav-open'));
  });

  const headerSearchForm = document.querySelector('.header-search form');
  if (headerSearchForm) {
    headerSearchForm.addEventListener('submit', function(e){
      const input = this.querySelector('input[name="q"]');
      if (input && !input.value.trim()) {
        e.preventDefault();
      }
    });
  }

  const flashAutoDismiss = document.querySelector('.flash[data-auto-dismiss="true"]');
  if (flashAutoDismiss) {
    setTimeout(() => {
      flashAutoDismiss.classList.add('flash-exit');
      setTimeout(() => flashAutoDismiss.remove(), 400);
    }, 3500);
  }

  // ============================================
  // SISTEMA DE NOTIFICACIONES CON CACHE LOCAL
  // ============================================
  const notifBell = document.getElementById('notifBell');
  const notifDropdown = document.getElementById('notifDropdown');
  const notifBadge = document.getElementById('notifBadge');
  const notifList = document.getElementById('notifList');
  const markAllBtn = document.getElementById('markAllRead');
  const baseUrl = '<?= $baseUrl ?>';

  let notificaciones = [];
  let dropdownOpen = false;

  // CACHE LOCAL - Claves de almacenamiento
  const CACHE_KEYS = {
    CONTADOR: 'ugc_notif_contador',
    NOTIFICACIONES: 'ugc_notif_lista',
    TIMESTAMP: 'ugc_notif_timestamp',
    USER_NIT: 'ugc_notif_user_nit'
  };
  const CACHE_DURACION_MS = 5 * 60 * 1000; // 5 minutos

  // Obtener NIT del usuario actual para aislar caché por usuario
  const userNit = '<?= $user['cedula'] ?? '' ?>';

  // Verificar si el caché pertenece al usuario actual
  function esCacheValido() {
    const cacheUserNit = localStorage.getItem(CACHE_KEYS.USER_NIT);
    return cacheUserNit === userNit;
  }

  // Guardar en caché
  function guardarCache(contador, notifs) {
    try {
      localStorage.setItem(CACHE_KEYS.USER_NIT, userNit);
      localStorage.setItem(CACHE_KEYS.CONTADOR, String(contador));
      localStorage.setItem(CACHE_KEYS.NOTIFICACIONES, JSON.stringify(notifs || []));
      localStorage.setItem(CACHE_KEYS.TIMESTAMP, String(Date.now()));
    } catch (e) {
      // Silenciar errores de localStorage (modo privado, etc.)
    }
  }

  // Cargar desde caché
  function cargarCache() {
    if (!esCacheValido()) return null;
    try {
      const timestamp = parseInt(localStorage.getItem(CACHE_KEYS.TIMESTAMP) || '0');
      const ahora = Date.now();
      if (ahora - timestamp > CACHE_DURACION_MS) return null; // Cache expirado

      return {
        contador: parseInt(localStorage.getItem(CACHE_KEYS.CONTADOR) || '0'),
        notificaciones: JSON.parse(localStorage.getItem(CACHE_KEYS.NOTIFICACIONES) || '[]'),
        timestamp: timestamp
      };
    } catch (e) {
      return null;
    }
  }

  // Mostrar contador en el badge
  function mostrarContador(count, animar = false) {
    if (!notifBadge) return;
    notifBadge.textContent = count > 99 ? '99+' : count;
    notifBadge.setAttribute('data-count', count);

    if (count > 0 && animar) {
      notifBell.classList.add('has-new');
      setTimeout(() => notifBell.classList.remove('has-new'), 1000);
    }
  }

  // Toggle dropdown
  if (notifBell) {
    notifBell.addEventListener('click', (e) => {
      e.stopPropagation();
      dropdownOpen = !dropdownOpen;
      notifDropdown.classList.toggle('active', dropdownOpen);
      if (dropdownOpen) {
        cargarNotificaciones();
      }
    });
  }

  document.addEventListener('click', (e) => {
    if (dropdownOpen && !notifDropdown.contains(e.target) && !notifBell.contains(e.target)) {
      dropdownOpen = false;
      notifDropdown.classList.remove('active');
    }
  });

  // Cargar contador: primero desde caché (instantáneo), luego actualizar
  const cacheInicial = cargarCache();
  if (cacheInicial) {
    mostrarContador(cacheInicial.contador, false);
  }
  // Actualizar desde servidor en segundo plano
  actualizarContador(true);
  // Refrescar cada 60 segundos
  setInterval(() => actualizarContador(false), 60000);

  if (markAllBtn) {
    markAllBtn.addEventListener('click', async () => {
      const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value;
      if (!csrfToken) {
        mostrarToast('Error: No se encontró el token de seguridad. Recarga la página.', 'error');
        return;
      }

      markAllBtn.disabled = true;
      markAllBtn.textContent = 'Procesando...';

      // Actualizar caché local inmediatamente (UX más rápida)
      guardarCache(0, []);
      mostrarContador(0, false);
      notificaciones = [];
      renderizarNotificaciones();

      try {
        const response = await fetch(`${baseUrl}/api/notificaciones/leer-todas`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        const data = await response.json();
        if (data.success) {
          // Confirmar estado desde servidor
          actualizarContador(false);
          cargarNotificaciones();
          mostrarToast('Todas las notificaciones marcadas como leídas', 'success');
        } else {
          mostrarToast(data.error || 'Error al marcar notificaciones', 'error');
        }
      } catch (err) {
        console.error('Error al marcar notificaciones:', err);
        mostrarToast('Error de conexión. Inténtalo de nuevo.', 'error');
      } finally {
        markAllBtn.disabled = false;
        markAllBtn.textContent = 'Marcar todo leído';
      }
    });
  }

  // Función para actualizar el contador
  async function actualizarContador(animar = false) {
    try {
      const response = await fetch(`${baseUrl}/api/notificaciones/contador`);
      const data = await response.json();
      const count = data.contador || 0;

      mostrarContador(count, animar);

      // Guardar en caché
      const cacheActual = cargarCache();
      guardarCache(count, cacheActual?.notificaciones || []);
    } catch (err) {
      console.error('Error al cargar contador:', err);
    }
  }

  async function cargarNotificaciones() {
    // Primero, mostrar desde caché si existe (carga instantánea)
    const cache = cargarCache();
    if (cache && cache.notificaciones && cache.notificaciones.length > 0) {
      notificaciones = cache.notificaciones;
      renderizarNotificaciones();
    }

    // Luego, actualizar desde servidor en segundo plano
    try {
      const response = await fetch(`${baseUrl}/api/notificaciones`);
      const data = await response.json();
      const nuevasNotifs = data.notificaciones || [];

      // Solo re-renderizar si hay cambios
      const hayCambios = JSON.stringify(notificaciones) !== JSON.stringify(nuevasNotifs);
      if (hayCambios || !cache) {
        notificaciones = nuevasNotifs;
        renderizarNotificaciones();
      }

      // Actualizar caché
      const contadorActual = parseInt(notifBadge?.getAttribute('data-count') || '0');
      guardarCache(contadorActual, nuevasNotifs);
    } catch (err) {
      console.error('Error al cargar notificaciones:', err);
      // Si no hay caché y falló la petición, mostrar estado vacío
      if (!cache) {
        notificaciones = [];
        renderizarNotificaciones();
      }
    }
  }

  function renderizarNotificaciones() {
    if (!notifList) return;

    if (notificaciones.length === 0) {
      notifList.innerHTML = `
        <div class="notificacion-empty">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"></path>
            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"></path>
          </svg>
          <p>No tienes notificaciones nuevas</p>
        </div>
      `;
      return;
    }

    notifList.innerHTML = notificaciones.map(n => {
      const icono = getIconoNotificacion(n.TIPO);
      const claseIcono = getClaseIcono(n.TIPO);
      const fecha = formatearFecha(n.FECHA_CREACION);

      return `
        <a href="${baseUrl}/solicitud/${n.ID_SOLICITUD}/ver"
           class="notificacion-item unread"
           data-id="${n.ID}"
           onclick="marcarLeida(${n.ID}, event)">
          <div class="notificacion-icon ${claseIcono}">${icono}</div>
          <div class="notificacion-content">
            <p>${escapeHtml(n.MENSAJE)}</p>
            <span class="notificacion-time">${fecha}</span>
          </div>
        </a>
      `;
    }).join('');
  }

  window.marcarLeida = async function(id, event) {
    const csrfToken = document.querySelector('input[name="_csrf_token"]')?.value;
    if (!csrfToken) {
      mostrarToast('Error: Token de seguridad no encontrado', 'error');
      return;
    }

    // Actualizar caché local inmediatamente (UX más rápida)
    const cache = cargarCache();
    if (cache && cache.notificaciones) {
      const notifsActualizadas = cache.notificaciones.filter(n => n.ID != id);
      const nuevoContador = Math.max(0, cache.contador - 1);
      guardarCache(nuevoContador, notifsActualizadas);
      mostrarContador(nuevoContador, false);
    }

    try {
      const response = await fetch(`${baseUrl}/api/notificaciones/${id}/leer`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const data = await response.json();
      if (data.success) {
        // Actualizar desde servidor para sincronizar
        actualizarContador(false);
        // Recargar lista si el dropdown está abierto
        if (dropdownOpen) {
          cargarNotificaciones();
        }
      }
    } catch (err) {
      console.error('Error al marcar como leída:', err);
    }
  };

  function getIconoNotificacion(tipo) {
    const svgs = {
      'campana': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
      'editar': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>',
      'check': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
      'cruz': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
      'ojo': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>',
      'prohibido': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>',
      'documento': '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>'
    };
    const iconos = {
      'NUEVA_SOLICITUD': svgs.campana,
      'SOLICITUD_EDITADA': svgs.editar,
      'SOLICITUD_APROBADA_JEFE': svgs.check,
      'SOLICITUD_RECHAZADA_JEFE': svgs.cruz,
      'REVISION_RRHH': svgs.ojo,
      'SOLICITUD_APROBADA_RRHH': svgs.check,
      'SOLICITUD_RECHAZADA_RRHH': svgs.prohibido
    };
    return iconos[tipo] || svgs.documento;
  }

  function getClaseIcono(tipo) {
    if (tipo.includes('RECHAZADA')) return 'rechazada';
    if (tipo.includes('APROBADA')) return 'aprobada';
    if (tipo.includes('REVISION')) return 'revision';
    return 'nueva';
  }

  function formatearFecha(fecha) {
    if (!fecha) return '';

    let fechaStr = fecha;
    if (typeof fecha === 'string') {
      if (/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(fecha)) {
        fechaStr = fecha.replace(' ', 'T') + '-05:00';
      }
      else if (/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/.test(fecha)) {
        const parts = fecha.match(/(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}):(\d{2})/);
        if (parts) {
          fechaStr = `${parts[3]}-${parts[2]}-${parts[1]}T${parts[4]}:${parts[5]}:${parts[6]}-05:00`;
        }
      }
    }

    const date = new Date(fechaStr);
    const now = new Date();

    const colombiaOffset = 5 * 60 * 60 * 1000;
    const nowColombia = new Date(now.getTime() + (now.getTimezoneOffset() * 60000) - colombiaOffset);

    const diffMs = nowColombia - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Ahora mismo';
    if (diffMins < 60) return `Hace ${diffMins} min`;
    if (diffHours < 24) return `Hace ${diffHours} h`;
    if (diffDays === 1) return 'Ayer';
    if (diffDays < 7) return `Hace ${diffDays} días`;

    return date.toLocaleDateString('es-CO', { day: 'numeric', month: 'short', timeZone: 'America/Bogota' });
  }

  function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function mostrarToast(mensaje, tipo = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${tipo}`;
    toast.innerHTML = `
      <span class="toast-message">${escapeHtml(mensaje)}</span>
      <button class="toast-close" onclick="this.parentElement.remove()">×</button>
    `;

    document.body.appendChild(toast);

    requestAnimationFrame(() => {
      toast.style.opacity = '1';
      toast.style.transform = 'translateX(0)';
    });

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }
});
</script>
</body>
</html>
