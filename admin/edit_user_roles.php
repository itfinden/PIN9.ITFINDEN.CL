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

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../theme_handler.php';

$id_user = $_GET['id_user'] ?? null;
$id_company = $_GET['id_company'] ?? null;
if (!$id_user) die('Usuario no especificado.');

$database = new Database();
$connection = $database->connection();

// Obtener usuario
$user = $connection->prepare("SELECT u.id_user, u.user, up.first_name, up.last_name, up.email FROM users u LEFT JOIN user_profiles up ON u.id_user = up.id_user WHERE u.id_user = ?");
$user->execute([$id_user]);

$user = $user->fetch();

// Obtener roles del sistema
$roles = $connection->query("SELECT * FROM roles")->fetchAll();

// Obtener roles actuales del usuario
$user_roles = $connection->prepare("SELECT id_role FROM user_roles WHERE id_user = ?");
$user_roles->execute([$id_user]);
$user_roles = array_column($user_roles->fetchAll(), 'id_role');
var_dump($user_roles);

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_roles = $_POST['roles'] ?? [];
    // Eliminar roles actuales
    $connection->prepare("DELETE FROM user_roles WHERE id_user = ?")->execute([$id_user]);
    // Insertar nuevos roles
    foreach ($selected_roles as $id_role) {
        $connection->prepare("INSERT INTO user_roles (id_user, id_role) VALUES (?, ?)")->execute([$id_user, $id_role]);
    }
    audit_log('Editar roles usuario', 'Usuario ID: ' . $id_user . ', Nuevos roles: ' . implode(',', $selected_roles));
    header("Location: company_users.php?id_company=" . urlencode($id_company));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Admin - Editar roles de usuario</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/edit_user_roles.php usando CSS variables */
    .admin-edit-user-roles-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 800px;
    }
    
    .admin-edit-user-roles-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-edit-user-roles-title i {
        color: var(--primary-color);
    }
    
    .form-check-input {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .form-check-input:checked {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    .form-check-label {
        color: var(--text-primary);
        font-weight: 500;
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
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-edit-user-roles-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-edit-user-roles-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-edit-user-roles-container">
        <h2 class="admin-edit-user-roles-title"><i class="fas fa-user-cog mr-2"></i>Editar roles de <?php echo htmlspecialchars($user['user'] ?? ''); ?> (<?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>)</h2>
    <form method="post">
        <div class="form-group">
            <label>Roles asignados:</label><br>
            <?php foreach ($roles as $role): ?>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="roles[]" value="<?= $role['id_role'] ?>" id="role<?= $role['id_role'] ?>" <?= in_array($role['id_role'], $user_roles) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="role<?= $role['id_role'] ?>">
                        <?= htmlspecialchars($role['name']) ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
        <a href="company_users.php?id_company=<?= urlencode($id_company) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Volver</a>
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