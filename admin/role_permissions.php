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

require_once __DIR__ . '/../config/setting.php';
require_once __DIR__ . '/../db/functions.php';
require_once __DIR__ . '/../lang/Languaje.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../security/check_access.php';

// Verificar permiso para gestionar permisos por rol
verificarPermisoVista($_SESSION['id_user'], 6); // Permisos por Rol

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

// Obtener roles y permisos
$roles = $connection->query("SELECT * FROM roles")->fetchAll();
$permissions = $connection->query("SELECT * FROM permissions")->fetchAll();

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($roles as $role) {
        $role_id = $role['id_role'];
        $selected_permissions = $_POST['permissions'][$role_id] ?? [];
        // Eliminar permisos actuales
        $connection->prepare("DELETE FROM role_permissions WHERE id_role = ?")->execute([$role_id]);
        // Insertar nuevos permisos
        foreach ($selected_permissions as $id_permission) {
            $connection->prepare("INSERT INTO role_permissions (id_role, id_permission) VALUES (?, ?)")->execute([$role_id, $id_permission]);
        }
    }
    header("Location: role_permissions.php?success=1");
    exit;
}

// Obtener permisos actuales por rol
$role_permissions = [];
$stmt = $connection->query("SELECT id_role, id_permission FROM role_permissions");
foreach ($stmt->fetchAll() as $rp) {
    $role_permissions[$rp['id_role']][] = $rp['id_permission'];
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Admin - Permisos por Rol</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/role_permissions.php usando CSS variables */
    .admin-role-permissions-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1200px;
    }
    
    .admin-role-permissions-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-role-permissions-title i {
        color: var(--primary-color);
    }
    
    .table {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        overflow: hidden;
    }
    
    .table thead th {
        background-color: var(--bg-secondary);
        border-color: var(--border-color);
        color: var(--text-primary);
        font-weight: 600;
    }
    
    .table tbody td {
        border-color: var(--border-color);
        color: var(--text-primary);
        background-color: var(--bg-card);
    }
    
    .table tbody tr:hover {
        background-color: var(--bg-secondary);
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
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-role-permissions-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-role-permissions-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-role-permissions-container">
        <h2 class="admin-role-permissions-title"><i class="fas fa-key mr-2"></i>Permisos por Rol</h2>
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">Permisos actualizados correctamente.</div>
    <?php endif; ?>
    <form method="post">
        <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>Permiso \ Rol</th>
                    <?php foreach ($roles as $role): ?>
                        <th><?= htmlspecialchars($role['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($permissions as $perm): ?>
                    <tr>
                        <td><?= htmlspecialchars($perm['name']) ?><br><small><?= htmlspecialchars($perm['description']) ?></small></td>
                        <?php foreach ($roles as $role): ?>
                            <td class="text-center">
                                <input type="checkbox" name="permissions[<?= $role['id_role'] ?>][]" value="<?= $perm['id_permission'] ?>" <?= (isset($role_permissions[$role['id_role']]) && in_array($perm['id_permission'], $role_permissions[$role['id_role']])) ? 'checked' : '' ?>>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
        <a href="companies.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Volver</a>
        <a href="../evento_dashboard.php" class="btn btn-info"><i class="fas fa-glass-cheers mr-1"></i> Probar Módulo Eventos</a>
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