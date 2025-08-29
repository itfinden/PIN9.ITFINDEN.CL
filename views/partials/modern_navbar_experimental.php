<?php
// Experimental modern navbar with multi-entry menu, non-invasive
// This file is optional and used only on test pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load calendar types config
$calendarTypes = [];
$configPath = __DIR__ . '/../../config/calendar_types.php';
if (file_exists($configPath)) {
    $calendarTypes = require $configPath;
}

$is_superadmin = isset($_SESSION['is_superadmin']) && (int)$_SESSION['is_superadmin'] === 1;
$id_rol = (int)($_SESSION['id_rol'] ?? 0);
$username = $_SESSION['user'] ?? 'Usuario';
$companyName = $_SESSION['company_name'] ?? ($_SESSION['name_company'] ?? '');

?>
<nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
  <a class="navbar-brand" href="/content.php">
    <img src="/img/logo.png" alt="Logo" style="height:28px;" />
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarExperimental" aria-controls="navbarExperimental" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarExperimental">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="menuCalendarios" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-calendar-alt"></i> Calendarios
        </a>
        <div class="dropdown-menu" aria-labelledby="menuCalendarios">
          <h6 class="dropdown-header">Tipos</h6>
          <?php foreach ($calendarTypes as $type): ?>
            <a class="dropdown-item" href="/calendar.php?type=<?php echo urlencode($type['key']); ?>">
              <i class="fas <?php echo htmlspecialchars($type['icon']); ?> mr-2"></i>
              <?php echo htmlspecialchars($type['label']); ?>
            </a>
          <?php endforeach; ?>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="/calendar.php">
            <i class="fas fa-list mr-2"></i> Ver todos
          </a>
        </div>
      </li>

      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="menuEventos" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-glass-cheers"></i> Eventos
        </a>
        <div class="dropdown-menu" aria-labelledby="menuEventos">
          <a class="dropdown-item" href="/evento_dashboard.php"><i class="fas fa-th-large mr-2"></i> Dashboard</a>
          <a class="dropdown-item" href="/evento_nuevo.php"><i class="fas fa-plus mr-2"></i> Nuevo Evento</a>
        </div>
      </li>

      <?php if ($is_superadmin || $id_rol === 2): ?>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="menuAdmin" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-tools"></i> Administración
        </a>
        <div class="dropdown-menu" aria-labelledby="menuAdmin">
          <a class="dropdown-item" href="/admin/dashboard.php"><i class="fas fa-tachometer-alt mr-2"></i> Panel Admin</a>
          <a class="dropdown-item" href="/admin/companies.php"><i class="fas fa-building mr-2"></i> Empresas</a>
          <a class="dropdown-item" href="/admin/calendars.php"><i class="fas fa-calendar-alt mr-2"></i> Calendarios</a>
        </div>
      </li>
      <?php endif; ?>
    </ul>

    <ul class="navbar-nav ml-auto">
      <li class="nav-item mr-3 d-none d-md-block"><span class="navbar-text text-muted"><?php echo htmlspecialchars($companyName); ?></span></li>
      <li class="nav-item">
        <a class="nav-link" href="#" onclick="window.toggleTheme && window.toggleTheme();return false;" title="Tema">
          <i class="fas fa-adjust"></i>
        </a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="menuUser" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($username); ?>
        </a>
        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="menuUser">
          <a class="dropdown-item" href="/content.php"><i class="fas fa-home mr-2"></i> Inicio</a>
          <div class="dropdown-divider"></div>
          <a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt mr-2"></i> Salir</a>
        </div>
      </li>
    </ul>
  </div>
</nav>

