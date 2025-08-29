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

// Verificar permiso para gestionar empresas
verificarPermisoVista($_SESSION['id_user'], 25); // Admin Empresas

require_once __DIR__ . '/../theme_handler.php';


$id_company = $_SESSION['id_company'] ?? null;
if (!$id_company) die('Empresa no especificada.');

$database = new Database();
$connection = $database->connection();

$company = $connection->prepare("SELECT * FROM companies WHERE id_company = ?");
$company->execute([$id_company]);
$company = $company->fetch();

$users = $connection->prepare("
    SELECT u.id_user, u.user, up.first_name, up.last_name, up.email, cu.role, cu.status
    FROM company_users cu
    JOIN users u ON cu.id_user = u.id_user
    LEFT JOIN user_profiles up ON u.id_user = up.id_user
    WHERE cu.id_company = ?
");
$users->execute([$id_company]);
$users = $users->fetchAll();

if (isset($_POST['delete_user']) && isset($_POST['id_user'])) {
    $del_user = (int)$_POST['id_user'];
    $stmt = $connection->prepare("DELETE FROM company_users WHERE id_company = ? AND id_user = ?");
    $ok = $stmt->execute([$id_company, $del_user]);
    if ($ok) {
        audit_log('Eliminar usuario de empresa', 'Empresa ID: ' . $id_company . ', Usuario ID: ' . $del_user);
        header('Location: company_users.php?id_company=' . $id_company);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Admin - Usuarios de <?= htmlspecialchars($company['company_name']) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/company_users.php usando CSS variables */
    .admin-company-users-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-company-users-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-company-users-title i {
        color: var(--primary-color);
    }
    
    .btn {
        border-radius: 25px;
        padding: 8px 16px;
        font-weight: 600;
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--shadow-medium);
    }
    
    .table {
        background-color: var(--bg-card);
        color: var(--text-primary);
    }
    
    .table thead th {
        background-color: var(--bg-secondary);
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    
    .table tbody td {
        color: var(--text-primary);
        border-color: var(--border-color);
    }
    
    .table tbody tr:hover {
        background-color: var(--bg-secondary);
    }
    
    .badge {
        border-radius: 12px;
        padding: 4px 8px;
        font-size: 0.8rem;
        font-weight: 500;
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
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-company-users-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-company-users-title {
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
    <div class="admin-company-users-container">
        <h2 class="admin-company-users-title"><i class="fas fa-users mr-2"></i>Usuarios de <?= htmlspecialchars($company['company_name']) ?></h2>
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= htmlspecialchars($user['user'] ?? '') ?></td>
                <td><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></td>
                <td><?= htmlspecialchars($user['email'] ?? '') ?></td>
                <td><span class="badge badge-<?= $user['role'] === 'admin' ? 'danger' : 'info' ?>"><?= htmlspecialchars($user['role'] ?? '') ?></span></td>
                <td><span class="badge badge-<?= $user['status'] === 'active' ? 'success' : ($user['status'] === 'pending' ? 'warning' : 'secondary') ?>"><?= htmlspecialchars($user['status'] ?? '') ?></span></td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="edit_user.php?id_user=<?= $user['id_user'] ?>&id_company=<?= $id_company ?>&from=company_users" class="btn btn-sm btn-info" title="Editar usuario">
                            <i class="fas fa-edit mr-1"></i>Editar
                        </a>
                        <a href="edit_user_roles.php?id_user=<?= $user['id_user'] ?>&id_company=<?= $id_company ?>" class="btn btn-sm btn-warning" title="Gestionar roles">
                            <i class="fas fa-user-cog mr-1"></i>Roles
                        </a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
        </div>
        <a href="companies.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Volver a empresas</a>
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