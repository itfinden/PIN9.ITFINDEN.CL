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
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../security/check_access.php';

// Verificar permiso para gestionar empresas
verificarPermisoVista($_SESSION['id_user'], 2); // Admin Empresas

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();
$companies = $connection->query("
    SELECT c.*, 
           COALESCE(sp.plan_name, 'Sin plan') as plan_name,
           COALESCE(ss.status_name, 'inactive') as subscription_status
    FROM companies c
    LEFT JOIN company_subscriptions cs ON c.id_company = cs.id_company
    LEFT JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
    LEFT JOIN subscription_statuses ss ON cs.id_subscription_status = ss.id_status
    ORDER BY c.id_company DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Admin - Empresas</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/companies.php usando CSS variables */
    .admin-companies-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-companies-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-companies-title i {
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
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-companies-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-companies-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-companies-container">
        <h2 class="admin-companies-title"><i class="fas fa-building mr-2"></i>Empresas registradas</h2>
    <!--<a href="role_permissions.php" class="btn btn-primary mb-3"><i class="fas fa-key mr-1"></i> Gestionar permisos por rol</a>-->
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Nombre</th>
                <th>Email</th>
                <th>Plan</th>
                <th>Estado</th>
                <th>Usuarios</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($companies as $company): ?>
            <tr>
                <td><?= htmlspecialchars($company['company_name']) ?></td>
                <td><?= htmlspecialchars($company['company_email']) ?></td>
                <td><?= htmlspecialchars($company['plan_name']) ?></td>
                <td>
                    <span class="badge badge-<?= $company['subscription_status'] === 'active' ? 'success' : 'secondary' ?>">
                        <?= htmlspecialchars($company['subscription_status']) ?>
                    </span>
                </td>
                <td>
                    <?php
                    $stmt = $connection->prepare("SELECT COUNT(*) FROM company_users WHERE id_company = ?");
                    $stmt->execute([$company['id_company']]);
                    echo $stmt->fetchColumn();
                    ?>
                </td>
                <td>
                    <a href="company_users.php?id_company=<?= $company['id_company'] ?>" class="btn btn-sm btn-info"><i class="fas fa-users mr-1"></i>Ver usuarios</a>
                    <a href="edit_company.php?id_company=<?= $company['id_company'] ?>" class="btn btn-sm btn-warning ml-1"><i class="fas fa-edit mr-1"></i>Editar</a>
                    <a href="services.php?id_company=<?= $company['id_company'] ?>" class="btn btn-sm btn-primary ml-1"><i class="fas fa-cogs mr-1"></i>Servicios</a>
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