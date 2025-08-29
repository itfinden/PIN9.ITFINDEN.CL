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

function is_superadmin() {
    return isset($_SESSION['id_user']) && user_has_permission($_SESSION['id_user'], 'admin_panel');
}
if (!is_superadmin()) {
    die('Acceso restringido solo a superadministradores.');
}

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

// Filtros
$company_id = $_GET['company_id'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? '';
$date = $_GET['date'] ?? '';
$where = [];
$params = [];
if ($company_id) {
    $where[] = 'cu.id_company = ?';
    $params[] = $company_id;
}
if ($user_id) {
    $where[] = 'l.id_user = ?';
    $params[] = $user_id;
}
if ($action) {
    $where[] = 'l.action LIKE ?';
    $params[] = "%$action%";
}
if ($date) {
    $where[] = 'DATE(l.created_at) = ?';
    $params[] = $date;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT l.*, u.user, up.first_name, up.last_name, cu.id_company, c.company_name
FROM audit_logs l
LEFT JOIN users u ON l.id_user = u.id_user
LEFT JOIN user_profiles up ON u.id_user = up.id_user
LEFT JOIN company_users cu ON l.id_user = cu.id_user
LEFT JOIN companies c ON cu.id_company = c.id_company
$where_sql
ORDER BY l.created_at DESC LIMIT 300";
$stmt = $connection->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Empresas y usuarios para filtros
$companies = $connection->query("SELECT id_company, company_name FROM companies ORDER BY company_name")->fetchAll();
$users = $connection->query("SELECT u.id_user, u.user, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.id_user = up.id_user ORDER BY u.user")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Logs de Auditoría Avanzados</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/audit_logs_advanced.php usando CSS variables */
    .admin-audit-logs-advanced-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-audit-logs-advanced-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-audit-logs-advanced-title i {
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
    
    .btn {
        border-radius: 25px;
        padding: 10px 20px;
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
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-audit-logs-advanced-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-audit-logs-advanced-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-audit-logs-advanced-container">
        <h2 class="admin-audit-logs-advanced-title"><i class="fas fa-clipboard-list mr-2"></i>Logs de Auditoría Avanzados</h2>
    <form class="form-row mb-3" method="get">
        <div class="form-group col-md-3">
            <select name="company_id" class="form-control">
                <option value="">Todas las empresas</option>
                <?php foreach ($companies as $c): ?>
                    <option value="<?= $c['id_company'] ?>" <?= $company_id == $c['id_company'] ? 'selected' : '' ?>><?= htmlspecialchars($c['company_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-3">
            <select name="user_id" class="form-control">
                <option value="">Todos los usuarios</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= $u['id_user'] ?>" <?= $user_id == $u['id_user'] ? 'selected' : '' ?>><?= htmlspecialchars($u['user'] . ' (' . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '') . ')') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group col-md-2">
            <input type="text" name="action" class="form-control" placeholder="Acción" value="<?= htmlspecialchars($action) ?>">
        </div>
        <div class="form-group col-md-2">
            <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
        </div>
        <div class="form-group col-md-2">
            <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-search mr-1"></i> Filtrar</button>
        </div>
    </form>
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Fecha</th>
                <th>Empresa</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Detalles</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
                <td><?= htmlspecialchars($log['company_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($log['user'] . ' (' . ($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? '') . ')') ?></td>
                <td><?= htmlspecialchars($log['action'] ?? '') ?></td>
<td><?= htmlspecialchars($log['details'] ?? '') ?></td>
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