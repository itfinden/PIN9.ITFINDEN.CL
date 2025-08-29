<?php
session_start();

// Habilitar slidepanel para admin
$_SESSION['enable_slidepanel'] = 1;

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/../lang/language_handler.php';

// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

require_once __DIR__ . '/../db/functions.php';
require_once __DIR__ . '/../lang/Languaje.php';

// Verificar que la clase Database esté disponible
if (!class_exists('Database')) {
    die('Error: Clase Database no encontrada. Verificar que db/functions.php esté incluido correctamente.');
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

// Verificar permisos de administrador
if (!user_has_permission($_SESSION['id_user'], 'admin_panel')) {
    die('Acceso restringido solo a superadministradores.');
}

require_once __DIR__ . '/../theme_handler.php';
$id_user = $_GET['id_user'] ?? null;
$id_company = $_GET['id_company'] ?? null;
if (!$id_user) {
    echo '<div class="alert alert-danger m-4">Usuario no especificado.</div>';
    require_once __DIR__ . '/../footer.php';
    exit;
}
$database = new Database();
$connection = $database->connection();


// Obtener perfil
$stmt = $connection->prepare("SELECT u.id_user, u.user, up.first_name, up.last_name, up.email, up.phone, up.position, up.department, up.avatar FROM users u LEFT JOIN user_profiles up ON u.id_user = up.id_user WHERE u.id_user = ?");
$stmt->execute([$id_user]);
$user = $stmt->fetch();



if (!$user) {
    echo '<div class="alert alert-danger m-4">Usuario no encontrado.</div>';
    require_once __DIR__ . '/../footer.php';
    exit;
}
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $department = trim($_POST['department'] ?? '');
    // Validaciones básicas
    if (!$first_name || !$last_name || !$email) {
        $error = 'Nombre, apellido y email son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email no válido.';
    } else {
        // Actualizar o crear perfil
        $stmt = $connection->prepare("SELECT id_profile FROM user_profiles WHERE id_user = ?");
        $stmt->execute([$id_user]);
        if ($stmt->fetch()) {
            $stmt = $connection->prepare("UPDATE user_profiles SET first_name=?, last_name=?, email=?, phone=?, position=?, department=? WHERE id_user=?");
            $ok = $stmt->execute([$first_name, $last_name, $email, $phone, $position, $department, $id_user]);
        } else {
            $stmt = $connection->prepare("INSERT INTO user_profiles (id_user, first_name, last_name, email, phone, position, department) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$id_user, $first_name, $last_name, $email, $phone, $position, $department]);
        }
        if ($ok) {
            audit_log('Editar usuario', 'Usuario ID: ' . $id_user);
            $success = 'Datos actualizados correctamente.';
            // Refrescar datos
            $stmt = $connection->prepare("SELECT u.id_user, u.user, up.first_name, up.last_name, up.email, up.phone, up.position, up.department, up.avatar FROM users u LEFT JOIN user_profiles up ON u.id_user = up.id_user WHERE u.id_user = ?");
            $stmt->execute([$id_user]);
            $user = $stmt->fetch();
            
            // Determinar a dónde redirigir basado en el parámetro 'from' o referer
            $redirect_url = '';
            $from_page = $_GET['from'] ?? '';
            
            // Si viene de company_users.php
            if ($id_company) {
                $redirect_url = 'company_users.php?id_company=' . urlencode($id_company);
            }
            // Si viene de superadmins.php (detectado por parámetro)
            elseif ($from_page === 'superadmins') {
                $redirect_url = 'superadmins.php';
            }
            // Si viene de company_users.php (detectado por parámetro)
            elseif ($from_page === 'company_users') {
                $redirect_url = 'company_users.php?id_company=' . urlencode($id_company);
            }
            // Si viene de superadmins.php (detectado por referer)
            elseif (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'superadmins.php') !== false) {
                $redirect_url = 'superadmins.php';
            }
            // Si viene de company_users.php (detectado por referer)
            elseif (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'company_users.php') !== false) {
                // Extraer id_company del referer si está presente
                $referer_parts = parse_url($_SERVER['HTTP_REFERER']);
                if (isset($referer_parts['query'])) {
                    parse_str($referer_parts['query'], $query_params);
                    if (isset($query_params['id_company'])) {
                        $redirect_url = 'company_users.php?id_company=' . urlencode($query_params['id_company']);
                    } else {
                        $redirect_url = 'company_users.php';
                    }
                } else {
                    $redirect_url = 'company_users.php';
                }
            }
            // Por defecto, volver a superadmins.php
            else {
                $redirect_url = 'superadmins.php';
            }
            
            if ($redirect_url) {
                header('Location: ' . $redirect_url);
                exit;
            }
        } else {
            $error = 'Error al actualizar los datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Admin - Editar usuario</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/edit_user.php usando CSS variables */
    .admin-edit-user-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1000px;
    }
    
    .admin-edit-user-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-edit-user-title i {
        color: var(--primary-color);
    }
    
    .form-control {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .form-control:focus {
        background-color: var(--bg-secondary);
        border-color: var(--primary-color);
        color: var(--text-primary);
        box-shadow: 0 0 0 0.2rem var(--primary-color-alpha);
    }
    
    .form-control::placeholder {
        color: var(--text-muted);
    }
    
    .form-label {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 8px;
    }
    
    .btn {
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--shadow-medium);
    }
    
    .alert {
        border-radius: 10px;
        border: none;
        padding: 15px 20px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background-color: var(--success-color-alpha);
        color: var(--success-color);
    }
    
    .alert-danger {
        background-color: var(--danger-color-alpha);
        color: var(--danger-color);
    }
    
    .text-danger {
        color: var(--danger-color) !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-edit-user-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-edit-user-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-edit-user-container">
        <h2 class="admin-edit-user-title"><i class="fas fa-user-edit mr-2"></i>Editar usuario: <?php echo htmlspecialchars($user['user'] ?? ''); ?></h2>
        
        <!-- Debug temporal - mostrar valores que se pasan al formulario -->
        <div style="padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; margin-bottom: 20px; font-size: 12px;">
            <strong>Debug - Valores del formulario:</strong><br>
            Nombre: "<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"<br>
            Apellido: "<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>"<br>
            Email: "<?php echo htmlspecialchars($user['email'] ?? ''); ?>"<br>
            Teléfono: "<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"<br>
            Posición: "<?php echo htmlspecialchars($user['position'] ?? ''); ?>"<br>
            Departamento: "<?php echo htmlspecialchars($user['department'] ?? ''); ?>"
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form method="post" novalidate>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Nombre <span class="text-danger">*</span></label>
              <input type="text" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Apellido <span class="text-danger">*</span></label>
              <input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Email <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Teléfono</label>
              <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Posición</label>
              <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Departamento</label>
              <input type="text" name="department" class="form-control" value="<?php echo htmlspecialchars($user['department'] ?? ''); ?>">
            </div>
          </div>
          <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar cambios</button>
          <?php
          $from_page = $_GET['from'] ?? '';
          $back_url = '';
          
          if ($id_company) {
              $back_url = 'company_users.php?id_company=' . urlencode($id_company);
          } elseif ($from_page === 'superadmins') {
              $back_url = 'superadmins.php';
          } elseif ($from_page === 'company_users') {
              $back_url = 'company_users.php?id_company=' . urlencode($id_company);
          } else {
              $back_url = 'superadmins.php';
          }
          ?>
          <a href="<?php echo $back_url; ?>" class="btn btn-secondary ml-2"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
        </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>

<?php include __DIR__ . '/../views/partials/slidepanel_menu.php'; ?>

<script>
$(document).ready(function() {
    // Inicializar theme switcher
    if (typeof ThemeSwitcher !== 'undefined') {
        const themeSwitcher = new ThemeSwitcher();
        themeSwitcher.init();
    }
});
</script>
</body>
</html> 