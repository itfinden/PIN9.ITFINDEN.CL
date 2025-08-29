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
verificarPermisoVista($_SESSION['id_user'], 4); // Auditoria

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

// Filtros
$user_id = $_GET['user_id'] ?? '';
$action = $_GET['action'] ?? '';
$date = $_GET['date'] ?? '';
$where = [];
$params = [];
if ($user_id) { $where[] = 'l.id_user = ?'; $params[] = $user_id; }
if ($action) { $where[] = 'l.action LIKE ?'; $params[] = "%$action%"; }
if ($date) { $where[] = 'DATE(l.created_at) = ?'; $params[] = $date; }
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT l.*, u.user, up.first_name, up.last_name FROM audit_logs l
LEFT JOIN users u ON l.id_user = u.id_user
LEFT JOIN user_profiles up ON u.id_user = up.id_user
$where_sql
ORDER BY l.created_at DESC LIMIT 200";
$stmt = $connection->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Usuarios para filtro
$users = $connection->query("SELECT u.id_user, u.user, up.first_name, up.last_name FROM users u LEFT JOIN user_profiles up ON u.id_user = up.id_user ORDER BY u.user")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Logs de Auditoría</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/audit_logs.php usando CSS variables */
    .admin-audit-logs-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-audit-logs-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-audit-logs-title i {
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
        .admin-audit-logs-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-audit-logs-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-audit-logs-container">
        <h2 class="admin-audit-logs-title"><i class="fas fa-clipboard-list mr-2"></i>Logs de Auditoría</h2>
    <form class="form-inline mb-3" method="get">
        <select name="user_id" class="form-control mr-2">
            <option value="">Todos los usuarios</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id_user'] ?>" <?= $user_id == $u['id_user'] ? 'selected' : '' ?>><?= htmlspecialchars($u['user'] . ' (' . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '') . ')') ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="action" class="form-control mr-2" placeholder="Acción" value="<?= htmlspecialchars($action) ?>">
        <input type="date" name="date" class="form-control mr-2" value="<?= htmlspecialchars($date) ?>">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Filtrar</button>
    </form>
    <div class="table-responsive">
    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>Fecha</th>
                <th>Usuario</th>
                <th>Acción</th>
                <th>Detalles</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['created_at'] ?? '') ?></td>
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