<?php
// Reusable Slidepanel Menu partial
// Usage: include after main content, before </body>
// Activation: set $_SESSION['enable_slidepanel']=1 or add query ?menu=slidepanel

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$enabled = !empty($_SESSION['enable_slidepanel']) || (isset($_GET['menu']) && $_GET['menu'] === 'slidepanel');

if (!$enabled) {
    return;
}

// Get user info for role-based menu
$user_id = $_SESSION['id_user'] ?? null;
$is_superadmin = !empty($_SESSION['is_superadmin']);
$id_rol = (int)($_SESSION['id_rol'] ?? 0);
$is_admin = $id_rol === 2 || $is_superadmin;
$username = $_SESSION['user'] ?? 'Usuario';
$company_name = $_SESSION['company_name'] ?? ($_SESSION['name_company'] ?? '');

// Get professional menu items from database
$professional_menu_items = [];
if ($user_id) {
    require_once __DIR__ . '/../../db/functions.php';
    $database = new Database();
    $connection = $database->connection();
    
    $sql = "SELECT p.Titulo, p.url, p.icon, p.section, p.menu_order
            FROM GET_ACCESS ga
            JOIN permissions p ON ga.id_permission = p.id_permission
            WHERE ga.id_user = :id_user AND p.show_in_menu = 1 AND p.url IS NOT NULL AND p.url != ''
            ORDER BY p.section, p.menu_order, p.Titulo";
    $stmt = $connection->prepare($sql);
    $stmt->execute([':id_user' => $user_id]);
    $professional_menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group by section
    $menu_by_section = [];
    foreach ($professional_menu_items as $item) {
        $section = $item['section'] ?: 'General';
        $menu_by_section[$section][] = $item;
    }
}
?>
<link rel="stylesheet" href="/Modules/Menu/assets/menu.css">

<button id="home-fab" class="action-fab home-fab" aria-label="Ir al inicio" onclick="window.location.href='/content.php'">
  <i class="fas fa-home"></i>
</button>

<button id="calendar-fab" class="action-fab calendar-fab" aria-label="Calendario" onclick="window.location.href='/calendar.php'">
  <i class="fas fa-calendar-alt"></i>
</button>

<button id="tickets-fab" class="action-fab tickets-fab" aria-label="Tickets" onclick="window.location.href='/tickets.php'">
  <i class="fas fa-ticket-alt"></i>
</button>

<button id="projects-fab" class="action-fab projects-fab" aria-label="Proyectos" onclick="window.location.href='/projects.php'">
  <i class="fas fa-project-diagram"></i>
</button>

<button id="events-fab" class="action-fab events-fab" aria-label="Eventos" onclick="window.location.href='/evento_dashboard.php'">
  <i class="fas fa-glass-cheers"></i>
</button>

<button id="sp-fab" class="sp-fab" aria-label="Abrir menú">
  <i class="fas fa-bars"></i>
</button>

<aside id="sp-panel" class="sp-panel" aria-hidden="true">
  <div class="sp-header">
    <h6 class="sp-title mb-0"><i class="fas fa-compass mr-1"></i> Menú</h6>
    <div class="sp-header-controls">
      <!-- Theme Toggle -->
      <button id="theme-toggle" class="theme-toggle" aria-label="Cambiar tema" title="Cambiar tema">
        <i class="fas fa-sun" id="theme-icon"></i>
      </button>
      <!-- Close Button -->
      <button id="sp-close" class="sp-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
    </div>
  </div>
  <div class="sp-content">
    <!-- User Info -->
    <div class="sp-section-title">Usuario</div>
    <div class="sp-item" style="pointer-events: none; padding: 8px 16px;">
      <i class="fas fa-user"></i> <?php echo htmlspecialchars($username); ?>
      <?php if ($company_name): ?>
        <br><small style="color: var(--text-muted); margin-left: 20px;"><i class="fas fa-building"></i> <?php echo htmlspecialchars($company_name); ?></small>
      <?php endif; ?>
    </div>
    
    <!-- Tree Menu Structure -->
    <div class="sp-section-title">Navegación</div>
    <div class="tree-menu">
      <div class="tree-item">
        <div class="tree-header" onclick="toggleTree('nav')">
          <i class="fas fa-chevron-right tree-arrow" id="nav-arrow"></i>
          <i class="fas fa-home"></i> Principal
        </div>
        <div class="tree-content" id="nav-content" style="display: none;">
          <a class="sp-item" href="/content.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
          <a class="sp-item" href="/calendar.php"><i class="fas fa-calendar-alt"></i> Calendario</a>
          <a class="sp-item" href="/today.php"><i class="fas fa-bolt"></i> Hoy</a>
        </div>
      </div>
      
      <div class="tree-item">
        <div class="tree-header" onclick="toggleTree('events')">
          <i class="fas fa-chevron-right tree-arrow" id="events-arrow"></i>
          <i class="fas fa-glass-cheers"></i> Eventos
        </div>
        <div class="tree-content" id="events-content" style="display: none;">
          <a class="sp-item" href="/evento_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
          <a class="sp-item" href="/evento_nuevo.php"><i class="fas fa-plus"></i> Nuevo Evento</a>
        </div>
      </div>
      
      <div class="tree-item">
        <div class="tree-header" onclick="toggleTree('projects')">
          <i class="fas fa-chevron-right tree-arrow" id="projects-arrow"></i>
          <i class="fas fa-project-diagram"></i> Proyectos
        </div>
        <div class="tree-content" id="projects-content" style="display: none;">
          <a class="sp-item" href="/projects.php"><i class="fas fa-list"></i> Lista</a>
          <a class="sp-item" href="/new_ticket.php"><i class="fas fa-ticket-alt"></i> Nuevo Ticket</a>
        </div>
      </div>
    </div>
    
    <!-- Panel de Administración - Tree -->
    <?php if (!empty($professional_menu_items)): ?>
    <div class="sp-section-title">Administración</div>
    <div class="tree-menu">
      <?php foreach ($menu_by_section as $section => $items): ?>
      <div class="tree-item">
        <div class="tree-header" onclick="toggleTree('<?php echo str_replace(' ', '-', $section); ?>')">
          <i class="fas fa-chevron-right tree-arrow" id="<?php echo str_replace(' ', '-', $section); ?>-arrow"></i>
          <i class="fas fa-folder"></i> <?php echo htmlspecialchars($section); ?>
        </div>
        <div class="tree-content" id="<?php echo str_replace(' ', '-', $section); ?>-content" style="display: none;">
          <?php foreach ($items as $item): ?>
            <a class="sp-item" href="<?php echo htmlspecialchars($item['url']); ?>" 
               title="<?php echo htmlspecialchars($item['Titulo']); ?>">
              <i class="<?php echo htmlspecialchars($item['icon'] ?: 'fas fa-link'); ?>"></i>
              <?php echo htmlspecialchars($item['Titulo']); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <!-- Admin Menu - Tree -->
    <?php if ($is_admin): ?>
    <div class="sp-section-title">Admin</div>
    <div class="tree-menu">
      <?php if ($is_superadmin): ?>
      <div class="tree-item">
        <div class="tree-header" onclick="toggleTree('superadmin')">
          <i class="fas fa-chevron-right tree-arrow" id="superadmin-arrow"></i>
          <i class="fas fa-crown"></i> Superadmin
        </div>
        <div class="tree-content" id="superadmin-content" style="display: none;">
          <a class="sp-item" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
          <a class="sp-item" href="/admin/companies.php"><i class="fas fa-building"></i> Empresas</a>
          <a class="sp-item" href="/admin/calendars.php"><i class="fas fa-calendar-alt"></i> Calendarios</a>
          <a class="sp-item" href="/admin/users.php"><i class="fas fa-users"></i> Usuarios</a>
        </div>
      </div>
      <?php else: ?>
      <div class="tree-item">
        <div class="tree-header" onclick="toggleTree('admin')">
          <i class="fas fa-chevron-right tree-arrow" id="admin-arrow"></i>
          <i class="fas fa-cog"></i> Admin
        </div>
        <div class="tree-content" id="admin-content" style="display: none;">
          <a class="sp-item" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
          <a class="sp-item" href="/admin/company-users.php"><i class="fas fa-users"></i> Usuarios</a>
          <a class="sp-item" href="/admin/company-settings.php"><i class="fas fa-cog"></i> Config</a>
          <a class="sp-item" href="/Modules/BOTWhatsapp/manage.php"><i class="fab fa-whatsapp"></i> BOT WhatsApp</a>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Account -->
    <div class="sp-section-title">Cuenta</div>
    <a class="sp-item" href="/profile.php"><i class="fas fa-user-edit"></i> Editar Perfil</a>
    <a class="sp-item" href="/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>
  </div>
</aside>

<style>
/* Tree Menu - Theme Aware */
.tree-menu {
  margin: 0 16px;
}

.tree-item {
  margin-bottom: 6px;
}

.tree-header {
  display: flex;
  align-items: center;
  padding: 10px 12px;
  background: var(--bg-secondary, #f8f9fa);
  border: 1px solid var(--border-color, #e9ecef);
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  font-size: 14px;
  color: var(--text-primary, #212529);
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.tree-header::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: var(--primary-color, #007bff);
  transform: scaleY(0);
  transition: transform 0.3s ease;
}

.tree-header:hover {
  background: var(--bg-hover, #e9ecef);
  transform: translateX(3px);
  box-shadow: 0 2px 8px var(--shadow-light, rgba(0,0,0,0.1));
}

.tree-header:hover::before {
  transform: scaleY(1);
}

.tree-arrow {
  margin-right: 10px;
  font-size: 12px;
  color: var(--text-muted, #6c757d);
  transition: all 0.3s ease;
  width: 16px;
  text-align: center;
}

.tree-arrow.expanded {
  transform: rotate(90deg);
  color: var(--primary-color, #007bff);
}

.tree-content {
  margin: 8px 0 8px 20px;
  padding-left: 16px;
  border-left: 2px solid var(--border-color, #e9ecef);
  position: relative;
}

.tree-content::before {
  content: '';
  position: absolute;
  left: -2px;
  top: 0;
  bottom: 0;
  width: 2px;
  background: linear-gradient(to bottom, var(--primary-color, #007bff), transparent);
}

.tree-content .sp-item {
  padding: 8px 12px;
  font-size: 13px;
  margin: 4px 0;
  border-radius: 6px;
  transition: all 0.2s ease;
  position: relative;
}

.tree-content .sp-item:hover {
  background: var(--bg-hover, #f8f9fa);
  transform: translateX(5px);
}

.tree-content .sp-item::before {
  content: '';
  position: absolute;
  left: -16px;
  top: 50%;
  width: 8px;
  height: 2px;
  background: var(--border-color, #e9ecef);
  transform: translateY(-50%);
}

/* Dark mode overrides */
[data-theme="dark"] .tree-header {
  background: var(--bg-secondary, #2a2f36);
  border-color: var(--border-color, #404040);
  color: var(--text-primary, #e5e7eb);
}

[data-theme="dark"] .tree-header:hover {
  background: var(--bg-hover, #3a3f46);
}

[data-theme="dark"] .tree-content {
  border-left-color: var(--border-color, #404040);
}

[data-theme="dark"] .tree-content .sp-item:hover {
  background: var(--bg-hover, #3a3f46);
}

[data-theme="dark"] .tree-content .sp-item::before {
  background: var(--border-color, #404040);
}

/* Animation for tree expansion */
.tree-content {
  animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive adjustments */
@media (max-width: 480px) {
  .tree-header {
    padding: 8px 10px;
    font-size: 13px;
  }
  
  .tree-content {
    margin-left: 15px;
    padding-left: 12px;
  }
  
  .tree-content .sp-item {
    padding: 6px 10px;
    font-size: 12px;
  }
}

/* Header Controls */
.sp-header-controls {
  display: flex;
  align-items: center;
  gap: 8px;
}

.theme-toggle {
  background: none;
  border: none;
  color: var(--text-primary, #212529);
  font-size: 16px;
  padding: 6px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
}

.theme-toggle:hover {
  background: var(--bg-hover, #e9ecef);
  transform: scale(1.1);
}

.theme-toggle:active {
  transform: scale(0.95);
}

[data-theme="dark"] .theme-toggle {
  color: var(--text-primary, #e5e7eb);
}

[data-theme="dark"] .theme-toggle:hover {
  background: var(--bg-hover, #3a3f46);
}

/* Home Button Styles */
.home-fab {
  position: fixed;
  left: calc(50% - 210px);
  bottom: 20px;
  width: 56px;
  height: 56px;
  background: var(--primary-color, #007bff);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 4px 12px var(--shadow-light, rgba(0,0,0,0.15));
  transition: all 0.3s ease;
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

/* Action Button Styles */
.action-fab {
  position: fixed;
  bottom: 20px;
  width: 56px;
  height: 56px;
  background: var(--primary-color, #007bff);
  color: white;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  box-shadow: 0 4px 12px var(--shadow-light, rgba(0,0,0,0.15));
  transition: all 0.3s ease;
  z-index: 999;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

.action-fab:hover {
  background: var(--primary-hover, #0056b3);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px var(--shadow-medium, rgba(0,0,0,0.25));
}

.action-fab:active {
  transform: translateY(0);
  box-shadow: 0 2px 8px var(--shadow-light, rgba(0,0,0,0.15));
}

/* Position action buttons in center */
.calendar-fab {
  left: calc(50% - 140px);
}

.tickets-fab {
  left: calc(50% - 70px);
}

.projects-fab {
  left: calc(50%);
}

.events-fab {
  left: calc(50% + 70px);
}

/* Dark mode overrides for action buttons */
[data-theme="dark"] .action-fab {
  background: var(--primary-color, #007bff);
  box-shadow: 0 4px 12px var(--shadow-dark, rgba(0,0,0,0.3));
}

[data-theme="dark"] .action-fab:hover {
  background: var(--primary-hover, #0056b3);
  box-shadow: 0 6px 20px var(--shadow-dark, rgba(0,0,0,0.4));
}

/* Menu button on the right */
.sp-fab {
  right: 20px !important;
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
  background: var(--primary-color, #007bff) !important;
  color: white !important;
}

/* Ensure all buttons have the same color scheme */
.home-fab,
.action-fab,
.sp-fab {
  background: var(--primary-color, #007bff) !important;
  color: white !important;
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
  pointer-events: auto !important;
}

/* Hover effects for all buttons */
.home-fab:hover,
.action-fab:hover,
.sp-fab:hover {
  background: var(--primary-hover, #0056b3) !important;
  transform: translateY(-2px);
  box-shadow: 0 6px 20px var(--shadow-medium, rgba(0,0,0,0.25));
}

/* Active effects for all buttons */
.home-fab:active,
.action-fab:active,
.sp-fab:active {
  transform: translateY(0);
  box-shadow: 0 2px 8px var(--shadow-light, rgba(0,0,0,0.15));
}

/* Dark mode overrides for all buttons */
[data-theme="dark"] .home-fab,
[data-theme="dark"] .action-fab,
[data-theme="dark"] .sp-fab {
  background: var(--primary-color, #007bff) !important;
  color: white !important;
}

[data-theme="dark"] .home-fab:hover,
[data-theme="dark"] .action-fab:hover,
[data-theme="dark"] .sp-fab:hover {
  background: var(--primary-hover, #0056b3) !important;
  box-shadow: 0 6px 20px var(--shadow-dark, rgba(0,0,0,0.4));
}
</style>

<script src="/Modules/Menu/assets/menu.js"></script>
<script>
function toggleTree(id) {
  const content = document.getElementById(id + '-content');
  const arrow = document.getElementById(id + '-arrow');
  
  if (content.style.display === 'none') {
    content.style.display = 'block';
    arrow.classList.add('expanded');
  } else {
    content.style.display = 'none';
    arrow.classList.remove('expanded');
  }
}

// Theme Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
  const themeToggle = document.getElementById('theme-toggle');
  const themeIcon = document.getElementById('theme-icon');
  
  // Set initial icon based on current theme
  function updateThemeIcon() {
    const currentTheme = localStorage.getItem('theme') || 'light';
    if (currentTheme === 'dark') {
      themeIcon.className = 'fas fa-moon';
      themeIcon.style.color = '#fbbf24'; // Yellow for moon
    } else {
      themeIcon.className = 'fas fa-sun';
      themeIcon.style.color = '#f59e0b'; // Orange for sun
    }
  }
  
  // Toggle theme using existing system
  function toggleTheme() {
    // Get current theme from localStorage
    const currentTheme = localStorage.getItem('theme') || 'light';
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    // Update localStorage
    localStorage.setItem('theme', newTheme);
    
    // Update body and html attributes
    document.body.setAttribute('data-theme', newTheme);
    document.documentElement.setAttribute('data-theme', newTheme);
    
    // Update meta theme-color for mobile
    const metaThemeColor = document.querySelector('meta[name="theme-color"]');
    if (metaThemeColor) {
      metaThemeColor.setAttribute('content', newTheme === 'dark' ? '#1a1a1a' : '#ffffff');
    }
    
    // Trigger theme change event
    const event = new CustomEvent('themeChanged', { detail: { theme: newTheme } });
    document.dispatchEvent(event);
    
    // Update icon
    updateThemeIcon();
    
    // Force CSS refresh for immediate visual change
    document.body.style.transition = 'none';
    setTimeout(() => {
      document.body.style.transition = '';
    }, 10);
  }
  
  // Initialize theme icon
  updateThemeIcon();
  
  // Add click event
  themeToggle.addEventListener('click', toggleTheme);
  
  // Listen for theme changes from other components
  document.addEventListener('themeChanged', function(e) {
    updateThemeIcon();
  });
  
  // Also listen for storage changes (in case theme is changed from another tab)
  window.addEventListener('storage', function(e) {
    if (e.key === 'theme') {
      updateThemeIcon();
    }
  });
});
</script>