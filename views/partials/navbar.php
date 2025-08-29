<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../lang/Languaje.php';
require_once __DIR__ . '/../../db/functions.php';
$lang = Language::autoDetect();
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
<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm" style="z-index:1040;">
  <div class="container">
    <a class="navbar-brand font-weight-bold d-flex align-items-center" href="/index.php">
      <img src="/img/logo.png" alt="Logo" style="height:60px;width:auto;margin-right:8px;">
      <span class="d-none d-md-inline"> Pin9</span>
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto align-items-center">
        <?php if ($user): ?>
          <?php if ($company_name): ?>
            <li class="nav-item">
              <span class="navbar-text text-muted d-none d-md-inline">
                <i class="fas fa-building mr-1"></i> <?php echo htmlspecialchars($company_name); ?>
              </span>
            </li>
          <?php endif; ?>
          
          <?php if ($user_id && tienePermiso($user_id, 9)): ?>
            <li class="nav-item"><a class="nav-link" href="/content.php"><i class="fas fa-tachometer-alt mr-1"></i> <?php echo $lang->get('dashboard'); ?></a></li>
          <?php endif; ?>
          
          <?php if ($user_id && tienePermiso($user_id, 15)): ?>
            <li class="nav-item"><a class="nav-link" href="/tickets.php"><i class="fas fa-ticket-alt mr-1"></i> <?php echo $lang->get('tickets'); ?></a>!!!</li>
          <?php endif; ?>
          
          <!-- Menú Profesional basado en permisos -->
          <?php include __DIR__ . '/professional_menu.php'; ?>
          
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars($user); ?>
              <?php if ($user_role): ?>
                <span class="badge badge-<?php echo $user_role === 'admin' ? 'danger' : 'info'; ?> ml-1"><?php echo ucfirst($user_role); ?></span>
              <?php endif; ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
              <?php if ($user_id && tienePermiso($user_id, 13)): ?>
                <a class="dropdown-item" href="/profile.php"><i class="fas fa-user mr-1"></i> Profile</a>
                <div class="dropdown-divider"></div>
              <?php endif; ?>
              <a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt mr-1"></i> <?php echo $lang->get('logout'); ?></a>
            </div>
          </li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/index.php"><i class="fas fa-home mr-1"></i> <?php echo $lang->get('home'); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="/login.php"><i class="fas fa-sign-in-alt mr-1"></i> <?php echo $lang->get('login'); ?></a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="registerDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i class="fas fa-user-plus mr-1"></i> Register
            </a>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="registerDropdown">
              <a class="dropdown-item" href="/register.php"><i class="fas fa-user mr-1"></i> Individual Account</a>
              <a class="dropdown-item" href="/register-company.php"><i class="fas fa-building mr-1"></i> Company Account</a>
            </div>
          </li>
          <?php if ($user_id): ?>
            <li class="nav-item"><a class="nav-link" href="/logout.php"><i class="fas fa-sign-out-alt mr-1"></i> <?php echo $lang->get('logout'); ?></a></li>
          <?php endif; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div style="height:64px;"></div> 