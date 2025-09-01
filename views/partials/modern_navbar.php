<?php
// Incluir el manejador de idioma
require_once __DIR__ . '/../../lang/language_handler.php';
require_once __DIR__ . '/../../db/functions.php';
require_once __DIR__ . '/../../theme_handler.php';

// Ocultar navbar si slidepanel está habilitado
if (!empty($_SESSION['enable_slidepanel']) || (isset($_GET['menu']) && $_GET['menu'] === 'slidepanel')) {
    return;
}

$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
$company_name = isset($_SESSION['company_name']) ? $_SESSION['company_name'] : null;
$user_id = isset($_SESSION['id_user']) ? $_SESSION['id_user'] : null;
// Hidratación de sesión si falta el nombre de usuario pero hay sesión activa
if (!$user && $user_id) {
  $info = GET_INFO($user_id);
  if ($info) {
    $_SESSION['user'] = $info['user'] ?? null;
    $_SESSION['user_role'] = $info['user_role'] ?? null;
    $empresa = obtenerEmpresaUsuario($user_id);
    $_SESSION['company_name'] = $empresa['company_name'] ?? null;
    $user = $_SESSION['user'];
    $user_role = $_SESSION['user_role'];
    $company_name = $_SESSION['company_name'];
  }
}
?>

<!-- Navbar Moderno y Profesional -->
<nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm modern-navbar" style="z-index:1040;" <?php echo applyThemeToHTML(); ?>>
  <div class="container">
    <!-- Logo y Brand -->
    <a class="navbar-brand d-flex align-items-center" href="/index.php">
      <div class="logo-container">
        <img src="/img/logo.png" alt="Pin9" class="logo-img">
        <span class="brand-text d-none d-md-inline">Pin9</span>
      </div>
    </a>

    <!-- Botón de menú móvil -->
    <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Menú principal -->
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto align-items-center">
        <?php if ($user): ?>
          <!-- Información de la empresa -->
          <?php if ($company_name): ?>
            <li class="nav-item d-none d-lg-block">
              <div class="company-info">
                <i class="fas fa-building text-primary"></i>
                <span class="company-name"><?php echo htmlspecialchars($company_name); ?></span>
              </div>
            </li>
          <?php endif; ?>

          <!-- Enlaces principales -->
          <?php if ($user_id && tienePermiso($user_id, 9)): ?>
            <li class="nav-item">
              <a class="nav-link modern-nav-link" href="/content.php">
                <i class="fas fa-tachometer-alt"></i>
                <span class="nav-text"><?php echo $lang->get('dashboard'); ?></span>
              </a>
            </li>
          <?php endif; ?>



          <!-- Menú Profesional -->
          <?php include __DIR__ . '/professional_menu.php'; ?>

          <!-- Selector de Idioma -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle modern-nav-link" href="#" id="languageDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-globe"></i>
              <span class="nav-text"><?php echo strtoupper($current_lang); ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right modern-dropdown" aria-labelledby="languageDropdown">
              <div class="dropdown-header">
                <i class="fas fa-globe mr-2"></i> <?php echo $lang->get('LANG'); ?>
              </div>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item <?php echo ($current_lang === 'es') ? 'active' : ''; ?>" href="?lang=es">
                <i class="fas fa-flag mr-2"></i> Español
                <?php if ($current_lang === 'es'): ?>
                  <i class="fas fa-check text-success ml-auto"></i>
                <?php endif; ?>
              </a>
              <a class="dropdown-item <?php echo ($current_lang === 'en') ? 'active' : ''; ?>" href="?lang=en">
                <i class="fas fa-flag mr-2"></i> English
                <?php if ($current_lang === 'en'): ?>
                  <i class="fas fa-check text-success ml-auto"></i>
                <?php endif; ?>
              </a>
            </div>
          </li>

          <!-- Selector de Tema -->
          <li class="nav-item">
            <div class="theme-switcher-container">
              <label class="theme-switcher">
                <input type="checkbox" id="theme-toggle">
                <span class="theme-slider">
                  <i class="fas fa-sun theme-icon sun"></i>
                  <i class="fas fa-moon theme-icon moon"></i>
                </span>
              </label>
            </div>
          </li>

          <!-- Notificaciones -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle modern-nav-link" href="#" id="notificationsDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-bell"></i>
              <span class="notification-badge">2</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right modern-dropdown" aria-labelledby="notificationsDropdown">
              <div class="dropdown-header">
                <i class="fas fa-bell mr-2"></i> Notificaciones
              </div>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="#">
                <div class="notification-item">
                  <i class="fas fa-calendar-check text-success"></i>
                  <div class="notification-content">
                    <div class="notification-title">Nuevo evento programado</div>
                    <div class="notification-time">Hace 5 minutos</div>
                  </div>
                </div>
              </a>
              <a class="dropdown-item" href="#">
                <div class="notification-item">
                  <i class="fas fa-user-plus text-info"></i>
                  <div class="notification-content">
                    <div class="notification-title">Nuevo usuario registrado</div>
                    <div class="notification-time">Hace 1 hora</div>
                  </div>
                </div>
              </a>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item text-center" href="#">
                <small>Ver todas las notificaciones</small>
              </a>
            </div>
          </li>

          <!-- Perfil de usuario -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle modern-nav-link user-profile" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
              </div>
              <div class="user-info d-none d-lg-block">
                <div class="user-name"><?php echo htmlspecialchars($user); ?></div>
                <?php if ($user_role): ?>
                  <div class="user-role"><?php echo ucfirst($user_role); ?></div>
                <?php endif; ?>
              </div>
            </a>
            <div class="dropdown-menu dropdown-menu-right modern-dropdown" aria-labelledby="userDropdown">
              <div class="dropdown-header">
                <i class="fas fa-user mr-2"></i> Mi Cuenta
              </div>
              <div class="dropdown-divider"></div>
              <?php if ($user_id && tienePermiso($user_id, 13)): ?>
                <a class="dropdown-item" href="/profile.php">
                  <i class="fas fa-user-edit mr-2"></i> Editar Perfil
                </a>
                <a class="dropdown-item" href="/company-settings.php">
                  <i class="fas fa-cog mr-2"></i> Configuración
                </a>
                <div class="dropdown-divider"></div>
              <?php endif; ?>
              <a class="dropdown-item" href="/logout.php">
                <i class="fas fa-sign-out-alt mr-2"></i> <?php echo $lang->get('logout'); ?>
              </a>
            </div>
          </li>

        <?php else: ?>
          <!-- Menú para usuarios no logueados -->
          <li class="nav-item">
            <a class="nav-link modern-nav-link" href="/index.php">
              <i class="fas fa-home"></i>
              <span class="nav-text"><?php echo $lang->get('home'); ?></span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link modern-nav-link" href="/login.php">
              <i class="fas fa-sign-in-alt"></i>
              <span class="nav-text"><?php echo $lang->get('login'); ?></span>
            </a>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle modern-nav-link" href="#" id="registerDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-user-plus"></i>
              <span class="nav-text">Registrarse</span>
            </a>
            <div class="dropdown-menu dropdown-menu-right modern-dropdown" aria-labelledby="registerDropdown">
              <div class="dropdown-header">
                <i class="fas fa-user-plus mr-2"></i> Crear Cuenta
              </div>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="/register.php">
                <i class="fas fa-user mr-2"></i> Cuenta Individual
              </a>
              <a class="dropdown-item" href="/register-company.php">
                <i class="fas fa-building mr-2"></i> Cuenta Empresarial
              </a>
            </div>
          </li>
          <?php if ($user_id): ?>
          <li class="nav-item">
            <a class="nav-link modern-nav-link" href="/logout.php">
              <i class="fas fa-sign-out-alt"></i>
              <span class="nav-text"><?php echo $lang->get('logout'); ?></span>
            </a>
          </li>
          <?php endif; ?>

          <!-- Selector de Idioma -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle modern-nav-link" href="#" id="languageDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-globe"></i>
              <span class="nav-text"><?php echo strtoupper($current_lang); ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right modern-dropdown" aria-labelledby="languageDropdown">
              <div class="dropdown-header">
                <i class="fas fa-globe mr-2"></i> SELECT_LANGUAGE
              </div>
              <div class="dropdown-divider"></div>
              <a class="dropdown-item" href="?lang=es">
                <i class="fas fa-flag mr-2"></i> Español
              </a>
              <a class="dropdown-item" href="?lang=en">
                <i class="fas fa-flag mr-2"></i> English
              </a>
            </div>
          </li>

          <!-- Selector de Tema -->
          <li class="nav-item">
            <div class="theme-switcher-container">
              <label class="theme-switcher">
                <input type="checkbox" id="theme-toggle">
                <span class="theme-slider">
                  <i class="fas fa-sun theme-icon sun"></i>
                  <i class="fas fa-moon theme-icon moon"></i>
                </span>
              </label>
            </div>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Espaciador para el contenido -->
<div style="height:80px;"></div>

<!-- Estilos CSS para el navbar moderno -->
<style>
/* CORRECCIÓN IMPORTANTE: Asegurar que el navbar tenga un fondo uniforme */
.modern-navbar,
.modern-navbar.navbar,
.modern-navbar.navbar-light {
  background: var(--bg-navbar) !important;
  background-color: var(--bg-navbar) !important;
  background-image: none !important;
}

/* Asegurar que todos los elementos hijos tengan fondo transparente */
.modern-navbar *:not(.dropdown-menu) {
  background: transparent !important;
  background-color: transparent !important;
}

/* Estilos específicos para temas */
[data-theme="dark"] .modern-navbar,
[data-theme="dark"] .modern-navbar.navbar,
[data-theme="dark"] .modern-navbar.navbar-light {
  background: var(--bg-navbar) !important;
  background-color: var(--bg-navbar) !important;
}

[data-theme="light"] .modern-navbar,
[data-theme="light"] .modern-navbar.navbar,
[data-theme="light"] .modern-navbar.navbar-light {
  background: var(--bg-navbar) !important;
  background-color: var(--bg-navbar) !important;
}

.modern-navbar {
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
  background: var(--bg-navbar) !important;
  border-bottom: 1px solid var(--border-color) !important;
}

.modern-navbar:hover {
  box-shadow: 0 4px 20px var(--shadow-light) !important;
}

.logo-container {
  display: flex;
  align-items: center;
  transition: transform 0.3s ease;
}

.logo-container:hover {
  transform: scale(1.05);
}

.logo-img {
  height: 50px;
  width: auto;
  margin-right: 12px;
  transition: all 0.3s ease;
}

.brand-text {
  font-weight: 700;
  font-size: 1.5rem;
  color: var(--text-primary);
  letter-spacing: -0.5px;
}

.company-info {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 25px;
  font-size: 0.9rem;
  font-weight: 500;
  margin-right: 20px;
  box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
}

.company-name {
  margin-left: 8px;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.modern-nav-link {
  display: flex;
  align-items: center;
  padding: 12px 16px !important;
  margin: 0 4px;
  border-radius: 8px;
  transition: all 0.3s ease;
  position: relative;
  font-weight: 500;
  color: var(--text-primary) !important;
}

.modern-nav-link:hover {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
  color: var(--text-light) !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px var(--shadow-medium);
}

.modern-nav-link i {
  margin-right: 8px;
  font-size: 1.1rem;
}

.nav-text {
  font-size: 0.95rem;
}

.notification-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: #e74c3c;
  color: white;
  border-radius: 50%;
  width: 18px;
  height: 18px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.user-profile {
  padding: 8px 16px !important;
  border-radius: 25px;
  background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
  color: white !important;
  margin-left: 10px;
}

.user-profile:hover {
  background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%) !important;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
}

.user-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  margin-right: 10px;
}

.user-avatar i {
  font-size: 1.2rem;
  margin: 0;
}

.user-info {
  text-align: left;
}

.user-name {
  font-weight: 600;
  font-size: 0.9rem;
  line-height: 1.2;
}

.user-role {
  font-size: 0.75rem;
  opacity: 0.8;
  line-height: 1.2;
}

.modern-dropdown {
  border: none;
  border-radius: 12px;
  box-shadow: 0 10px 30px var(--shadow-light);
  padding: 8px 0;
  min-width: 250px;
  background: var(--bg-card);
  border: 1px solid var(--border-color);
}

.modern-dropdown .dropdown-header {
  font-weight: 600;
  color: var(--text-primary);
  padding: 12px 20px 8px;
  font-size: 0.9rem;
}

.modern-dropdown .dropdown-item {
  padding: 12px 20px;
  transition: all 0.2s ease;
  border-radius: 0;
  font-size: 0.9rem;
  color: var(--text-primary);
  background: var(--bg-card);
}

.modern-dropdown .dropdown-item:hover {
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
  color: var(--text-light);
  transform: translateX(5px);
}

.modern-dropdown .dropdown-item.active {
  background: linear-gradient(135deg, var(--success-color) 0%, var(--primary-color) 100%);
  color: var(--text-light);
  font-weight: 600;
}

.modern-dropdown .dropdown-item.active i.fa-check {
  color: var(--text-light);
}

.modern-dropdown .dropdown-divider {
  margin: 8px 0;
  border-color: var(--border-color);
}

.notification-item {
  display: flex;
  align-items: center;
  padding: 8px 0;
}

.notification-item i {
  font-size: 1.2rem;
  margin-right: 12px;
  width: 20px;
  text-align: center;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 500;
  font-size: 0.85rem;
  margin-bottom: 2px;
}

.notification-time {
  font-size: 0.75rem;
  color: var(--text-muted);
}

/* Language Selector en Navbar */
.language-selector {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  margin: 0 4px;
}

.language-selector .nav-link {
  display: flex;
  align-items: center;
  padding: 8px 12px !important;
  border-radius: 20px;
  background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
  color: var(--text-light) !important;
  transition: all 0.3s ease;
}

.language-selector .nav-link:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px var(--shadow-medium);
}

/* Theme Switcher en Navbar */
.theme-switcher-container {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  margin: 0 4px;
}

.theme-switcher {
  position: relative;
  display: inline-block;
  width: 50px;
  height: 25px;
  margin: 0;
}

.theme-switcher input {
  opacity: 0;
  width: 0;
  height: 0;
}

.theme-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: var(--bg-secondary);
  transition: 0.3s ease;
  border-radius: 25px;
  border: 2px solid var(--border-color);
}

.theme-slider:before {
  position: absolute;
  content: "";
  height: 17px;
  width: 17px;
  left: 2px;
  bottom: 2px;
  background-color: var(--primary-color);
  transition: 0.3s ease;
  border-radius: 50%;
}

input:checked + .theme-slider {
  background-color: var(--primary-color);
}

input:checked + .theme-slider:before {
  transform: translateX(25px);
}

.theme-icon {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  font-size: 10px;
  color: #ffffff;
  transition: 0.3s ease;
}

.theme-icon.sun {
  left: 6px;
}

.theme-icon.moon {
  right: 6px;
}

/* Responsive */
@media (max-width: 991.98px) {
  .modern-nav-link {
    padding: 15px 20px !important;
    margin: 2px 0;
    border-radius: 8px;
  }
  
  .company-info {
    margin: 10px 0;
    justify-content: center;
  }
  
  .user-profile {
    margin: 10px 0;
    justify-content: center;
  }
  
  .modern-dropdown {
    min-width: 200px;
  }
  
  .theme-switcher-container {
    justify-content: center;
    margin: 10px 0;
  }
}

/* Animaciones adicionales */
.navbar-nav .nav-item {
  opacity: 0;
  animation: fadeInUp 0.6s ease forwards;
}

.navbar-nav .nav-item:nth-child(1) { animation-delay: 0.1s; }
.navbar-nav .nav-item:nth-child(2) { animation-delay: 0.2s; }
.navbar-nav .nav-item:nth-child(3) { animation-delay: 0.3s; }
.navbar-nav .nav-item:nth-child(4) { animation-delay: 0.4s; }
.navbar-nav .nav-item:nth-child(5) { animation-delay: 0.5s; }

@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Estilos adicionales para dropdowns */
.modern-navbar .dropdown-menu {
  background: var(--bg-card) !important;
  border: 1px solid var(--border-color) !important;
}

/* Estilos específicos para iconos del toggler */
[data-theme="light"] .modern-navbar .navbar-toggler-icon {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(0, 0, 0, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
}

[data-theme="dark"] .modern-navbar .navbar-toggler-icon {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e") !important;
}
</style>

<!-- Scripts adicionales -->
<script>
$(document).ready(function() {
  // Efecto de scroll en el navbar
  $(window).scroll(function() {
    if ($(this).scrollTop() > 50) {
      $('.modern-navbar').addClass('scrolled');
    } else {
      $('.modern-navbar').removeClass('scrolled');
    }
  });
  
  // Tooltips para elementos del navbar
  $('[data-toggle="tooltip"]').tooltip();
  
  // Animación suave para los dropdowns
  $('.dropdown-toggle').on('click', function() {
    $(this).next('.dropdown-menu').slideToggle(200);
  });
  
  // Inicializar theme switcher
  if (typeof ThemeSwitcher !== 'undefined') {
    const themeSwitcher = new ThemeSwitcher();
    themeSwitcher.init();
  }
});
</script> 