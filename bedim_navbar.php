<?php
session_start();
require_once __DIR__ . '/theme_handler.php';
$theme_attributes = applyThemeToHTML();
?><!DOCTYPE html>
<html lang="es" <?php echo $theme_attributes ?? ''; ?>>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Navbar Bedimcode (Demo)</title>
  <link rel="stylesheet" href="/assets/bedim/style.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body>
  <header class="bdm-header">
    <nav class="bdm-nav">
      <a href="/content.php" class="bdm-nav__logo">
        <i class="fas fa-bolt"></i> <span>PIN9</span>
      </a>
      <button class="bdm-nav__toggle" aria-label="Abrir menú"><i class="fas fa-bars"></i></button>
      <div class="bdm-nav__menu" id="bdm-menu">
        <ul class="bdm-nav__list">
          <li><a class="bdm-nav__link" href="/content.php"><i class="fas fa-home mr-1"></i>Inicio</a></li>
          <li><a class="bdm-nav__link" href="/calendar.php"><i class="fas fa-calendar-alt mr-1"></i>Calendario</a></li>
          <li><a class="bdm-nav__link" href="/evento_dashboard.php"><i class="fas fa-glass-cheers mr-1"></i>Eventos</a></li>
          <li><a class="bdm-nav__link" href="/logout.php"><i class="fas fa-sign-out-alt mr-1"></i>Salir</a></li>
        </ul>
      </div>
    </nav>
  </header>

  <main class="bdm-container">
    <span class="bdm-tag">Demo</span>
    <h2 class="bdm-title">Navbar responsive estilo Bedimcode</h2>
    <p>Esta demo es independiente del navbar actual del sistema.</p>
    <p>Ruta: <code>/bedim_navbar.php</code></p>
  </main>

  <script src="/assets/bedim/main.js"></script>
</body>
</html>


