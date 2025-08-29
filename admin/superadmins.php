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

require_once __DIR__ . '/../security/check_access.php';

// Verificar permiso para gestionar superadministradores
if (!user_has_permission($_SESSION['id_user'], 'admin_panel')) {
    die('Acceso restringido solo a superadministradores.');
}

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

// Buscar usuarios
$search = $_GET['search'] ?? '';
$where = '';
$params = [];
if ($search) {
    $where = "WHERE u.user LIKE ? OR up.email LIKE ? OR up.first_name LIKE ? OR up.last_name LIKE ?";
    $params = array_fill(0, 4, "%$search%");
}

// Todos los usuarios y si son superadmin
$sql = "SELECT u.id_user, u.user, up.first_name, up.last_name, up.email,
  (SELECT COUNT(*) FROM user_roles ur WHERE ur.id_user = u.id_user AND ur.id_role = 1) AS is_superadmin
FROM users u
LEFT JOIN user_profiles up ON u.id_user = up.id_user
$where
ORDER BY is_superadmin DESC, u.user ASC";
$stmt = $connection->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Agregar/quitar superadmin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_user'])) {
    $id_user = (int)$_POST['id_user'];
    if (isset($_POST['add_superadmin'])) {
        $connection->prepare("INSERT IGNORE INTO user_roles (id_user, id_role) VALUES (?, 1)")->execute([$id_user]);
        audit_log('Asignar superadmin', 'id_user=' . $id_user);
    } elseif (isset($_POST['remove_superadmin'])) {
        $connection->prepare("DELETE FROM user_roles WHERE id_user = ? AND id_role = 1")->execute([$id_user]);
        audit_log('Quitar superadmin', 'id_user=' . $id_user);
    }
    header('Location: superadmins.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Gestión de Superadmins</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/superadmins.php usando CSS variables */
    .admin-superadmins-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1200px;
    }
    
    .admin-superadmins-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-superadmins-title i {
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
    
    .btn-sm {
        padding: 8px 16px;
        font-size: 0.875rem;
    }
    
    .badge {
        border-radius: 15px;
        padding: 6px 12px;
        font-weight: 600;
    }
    
    .btn-group {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }
    
    .btn-group .btn {
        margin: 0;
        flex: 1;
        min-width: auto;
    }
    
    .alert-info {
        background-color: var(--info-color-alpha);
        color: var(--info-color);
        border: 1px solid var(--info-color);
    }
    
    .alert ul {
        padding-left: 20px;
    }
    
    .alert li {
        margin-bottom: 5px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-superadmins-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-superadmins-title {
            font-size: 1.5rem;
        }
        
        .btn-group {
            flex-direction: column;
        }
        
        .btn-group .btn {
            margin-bottom: 5px;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-superadmins-container">
        <h2 class="admin-superadmins-title"><i class="fas fa-user-shield mr-2"></i>Gestión de Superadmins</h2>
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Funcionalidades disponibles:</strong>
            <ul class="mb-0 mt-2">
                <li><strong>Editar:</strong> Modificar información del usuario</li>
                <li><strong>Roles:</strong> Gestionar roles y permisos del usuario</li>
                <li><strong>Asignar/Quitar Superadmin:</strong> Cambiar el estado de superadministrador</li>
            </ul>
        </div>
    <form class="form-inline mb-3" method="get">
        <input type="text" name="search" class="form-control mr-2" placeholder="Buscar usuario, email o nombre" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Buscar</button>
    </form>
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Superadmin</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo htmlspecialchars($user['user'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                <td>
                    <?php if ($user['is_superadmin']): ?>
                        <span class="badge badge-success">Sí</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">No</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="edit_user.php?id_user=<?php echo $user['id_user']; ?>&from=superadmins" class="btn btn-info btn-sm" title="Editar usuario">
                            <i class="fas fa-edit mr-1"></i>Editar
                        </a>
                        <a href="edit_user_roles.php?id_user=<?php echo $user['id_user']; ?>" class="btn btn-warning btn-sm" title="Gestionar roles">
                            <i class="fas fa-user-cog mr-1"></i>Roles
                        </a>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="id_user" value="<?php echo $user['id_user']; ?>">
                            <?php if ($user['is_superadmin']): ?>
                                <button type="submit" name="remove_superadmin" class="btn btn-danger btn-sm" onclick="return confirm('¿Quitar superadmin?')" title="Quitar superadmin">
                                    <i class="fas fa-user-slash mr-1"></i>Quitar
                                </button>
                            <?php else: ?>
                                <button type="submit" name="add_superadmin" class="btn btn-success btn-sm" title="Asignar superadmin">
                                    <i class="fas fa-user-shield mr-1"></i>Asignar
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
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