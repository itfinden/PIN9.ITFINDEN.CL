<?php
session_start();
require_once __DIR__ . '/../../theme_handler.php';
require_once __DIR__ . '/../../lang/language_handler.php';
$theme_attributes = applyThemeToHTML();

// Defaults for demo
$_SESSION['user'] = $_SESSION['user'] ?? 'Usuario Demo';
$_SESSION['company_name'] = $_SESSION['company_name'] ?? 'Empresa Demo';
$_SESSION['is_superadmin'] = $_SESSION['is_superadmin'] ?? 0;
$_SESSION['id_rol'] = $_SESSION['id_rol'] ?? 3;
?><!DOCTYPE html>
<html lang="es" <?php echo $theme_attributes ?? ''; ?>>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Slidepanel Menu (Demo)</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/css/style.css">
  <link rel="stylesheet" href="/Modules/Menu/assets/menu.css">
  <script src="/js/theme-switcher.js"></script>
</head>
<body>

  <div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between">
      <h4>Demo: Slidepanel como menú</h4>
      <button class="btn btn-outline-secondary" onclick="window.toggleTheme && window.toggleTheme()"><i class="fas fa-adjust"></i></button>
    </div>
    <p class="text-muted">Este demo no modifica el navbar actual. Usa el botón flotante para abrir el menú lateral.</p>
    <div class="card">
      <div class="card-body">
        <p>Usuario: <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></p>
        <p>Empresa: <strong><?php echo htmlspecialchars($_SESSION['company_name']); ?></strong></p>
      </div>
    </div>
  </div>

  <!-- Botón flotante -->
  <button id="sp-fab" class="sp-fab" aria-label="Abrir menú">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Panel lateral -->
  <aside id="sp-panel" class="sp-panel" aria-hidden="true">
    <div class="sp-header">
      <h6 class="sp-title mb-0"><i class="fas fa-compass mr-1"></i> Menú</h6>
      <button id="sp-close" class="sp-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
    </div>
    <div class="sp-content">
      <div class="sp-section-title">Navegación</div>
      <a class="sp-item" href="/content.php"><i class="fas fa-home"></i> Inicio</a>
      <a class="sp-item" href="/calendar.php"><i class="fas fa-calendar-alt"></i> Calendario</a>
      <a class="sp-item" href="/evento_dashboard.php"><i class="fas fa-glass-cheers"></i> Eventos</a>
      <a class="sp-item" href="/projects.php"><i class="fas fa-project-diagram"></i> Proyectos</a>

      <div class="sp-section-title">Cuenta</div>
      <a class="sp-item" href="/content.php"><i class="fas fa-user"></i> Perfil</a>
      <a class="sp-item" href="/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>

      <div class="sp-section-title">Acciones rápidas</div>
      <a class="sp-item" href="/evento_nuevo.php"><i class="fas fa-plus"></i> Nuevo Evento</a>
      <a class="sp-item" href="/today.php"><i class="fas fa-bolt"></i> Hoy</a>
    </div>
  </aside>

  <script src="/Modules/Menu/assets/menu.js"></script>
</body>
</html>


