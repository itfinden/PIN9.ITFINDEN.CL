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

$lang = Language::getInstance();
$current_lang = $lang->language ?? 'es';

function format_price($price, $lang) {
    if ($lang === 'es') {
        return '$' . number_format($price, 0, ',', '.');
    } else {
        return '$' . number_format($price, 2, '.', ',');
    }
}

if (!user_has_permission($_SESSION['id_user'], 'admin_panel')) {
    die('Acceso restringido solo a superadministradores.');
}

require_once __DIR__ . '/../theme_handler.php';
$database = new Database();
$connection = $database->connection();
$id_service = $_GET['id_service'] ?? null;
if (!$id_service) die('Servicio no especificado.');
$stmt = $connection->prepare("SELECT * FROM services WHERE id_service = ?");
$stmt->execute([$id_service]);
$service = $stmt->fetch();
if (!$service) die('Servicio no encontrado.');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    if (!$name || !$type) {
        $error = 'Nombre y tipo son obligatorios.';
    } else {
        $stmt = $connection->prepare("UPDATE services SET name=?, type=?, unit=?, duration=?, price=?, description=?, status=? WHERE id_service=?");
        $ok = $stmt->execute([$name, $type, $unit, $duration, $price, $description, $status, $id_service]);
        if ($ok) {
            audit_log('Editar servicio (superadmin)', 'Servicio: ' . $name);
            header('Location: services.php');
            exit;
        } else {
            $error = 'Error al actualizar el servicio.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Editar Servicio</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/edit_service.php usando CSS variables */
    .admin-edit-service-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1000px;
    }
    
    .admin-edit-service-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-edit-service-title i {
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
    
    .alert-danger {
        background-color: var(--danger-color-alpha);
        color: var(--danger-color);
    }
    
    .form-text {
        color: var(--text-muted);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-edit-service-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-edit-service-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-edit-service-container">
        <h2 class="admin-edit-service-title"><i class="fas fa-edit mr-2"></i>Editar Servicio</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Nombre</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($service['name']); ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label>Tipo</label>
                <input type="text" name="type" class="form-control" value="<?php echo htmlspecialchars($service['type']); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Unidad</label>
                <input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($service['unit']); ?>">
            </div>
            <div class="form-group col-md-4">
                <label>Duración</label>
                <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($service['duration']); ?>">
            </div>
            <div class="form-group col-md-4">
                <label>Precio</label>
                <input type="number" step="0.01" name="price" class="form-control" value="<?php echo htmlspecialchars($service['price']); ?>">
                <small class="form-text text-muted">Actual: <?php echo format_price($service['price'], $current_lang); ?></small>
            </div>
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="description" class="form-control"><?php echo htmlspecialchars($service['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label>Estado</label>
            <select name="status" class="form-control">
                <option value="active" <?php if($service['status']==='active') echo 'selected'; ?>>Activo</option>
                <option value="inactive" <?php if($service['status']==='inactive') echo 'selected'; ?>>Inactivo</option>
                <option value="suspended" <?php if($service['status']==='suspended') echo 'selected'; ?>>Suspendido</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar</button>
        <a href="services.php" class="btn btn-secondary ml-2">Cancelar</a>
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