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

require_once __DIR__ . '/../lang/Languaje.php';
require_once __DIR__ . '/../db/functions.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../security/check_access.php';

// Verificar permiso para gestionar empresas
verificarPermisoVista($_SESSION['id_user'], 12); // Admin Empresas

require_once __DIR__ . '/../theme_handler.php';

$id_company = $_SESSION['id_company'];
$database = new Database();
$connection = $database->connection();

// Obtener datos actuales
$stmt = $connection->prepare("SELECT * FROM companies WHERE id_company = ?");
$stmt->execute([$id_company]);
$company = $stmt->fetch();

if (!$company) die('Empresa no encontrada.');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'company_name', 'company_email', 'company_phone', 'company_address', 'company_website', 'company_tax_id'
    ];
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = $_POST[$field] ?? '';
    }
    // Validación básica
    if (empty($data['company_name']) || empty($data['company_email'])) {
        $error = 'El nombre y el email son obligatorios.';
    } else {
        $sql = "UPDATE companies SET company_name=?, company_email=?, company_phone=?, company_address=?, company_website=?, company_tax_id=? WHERE id_company=?";
        $stmt = $connection->prepare($sql);
        $ok = $stmt->execute([
            $data['company_name'], $data['company_email'], $data['company_phone'], $data['company_address'],
            $data['company_website'], $data['company_tax_id'], $id_company
        ]);
        if ($ok) {
            audit_log('Editar empresa propia', 'Empresa ID: ' . $id_company . ', Nombre: ' . $data['company_name']);
            $success = 'Datos actualizados correctamente.';
            // Refrescar datos
            $stmt = $connection->prepare("SELECT * FROM companies WHERE id_company = ?");
            $stmt->execute([$id_company]);
            $company = $stmt->fetch();
        } else {
            $error = 'Error al actualizar los datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Configuración de Empresa</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/company-settings.php usando CSS variables */
    .admin-company-settings-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1000px;
    }
    
    .admin-company-settings-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-company-settings-title i {
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
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-company-settings-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-company-settings-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php';?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-company-settings-container">
        <h2 class="admin-company-settings-title"><i class="fas fa-cog mr-2"></i>Configuración de Empresa</h2>
    <a href="services.php" class="btn btn-primary mb-3"><i class="fas fa-cogs mr-1"></i>Gestionar Servicios</a>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <form method="post">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Nombre</label>
                <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
            </div>
            <div class="form-group col-md-6">
                <label>Email</label>
                <input type="email" name="company_email" class="form-control" value="<?php echo htmlspecialchars($company['company_email']); ?>" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Teléfono</label>
                <input type="text" name="company_phone" class="form-control" value="<?php echo htmlspecialchars($company['company_phone']); ?>">
            </div>
            <div class="form-group col-md-6">
                <label>Dirección</label>
                <input type="text" name="company_address" class="form-control" value="<?php echo htmlspecialchars($company['company_address']); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Website</label>
                <input type="url" name="company_website" class="form-control" value="<?php echo htmlspecialchars($company['company_website']); ?>">
            </div>
            <div class="form-group col-md-6">
                <label>RUT / Tax ID</label>
                <input type="text" name="company_tax_id" class="form-control" value="<?php echo htmlspecialchars($company['company_tax_id']); ?>">
            </div>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
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