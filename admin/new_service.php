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

// Verificar permiso para crear servicios como superadmin
verificarPermisoVista($_SESSION['id_user'], 22); // admin_new_service

require_once __DIR__ . '/../theme_handler.php';
$lang = Language::getInstance();
$current_lang = $lang->language ?? 'es';
function format_price($price, $lang) {
    if ($lang === 'es') {
        return '$' . number_format($price, 0, ',', '.');
    } else {
        return '$' . number_format($price, 2, '.', ',');
    }
}
$database = new Database();
$connection = $database->connection();

// Determinar qué empresas mostrar según el rol del usuario
$id_rol = $_SESSION['id_rol'] ?? null;
$id_company = $_SESSION['id_company'] ?? null;

if ($id_rol == 2) {
    // Admin de empresa: solo mostrar su empresa
    $companies = $connection->prepare("SELECT id_company, company_name FROM companies WHERE id_company = ? ORDER BY company_name");
    $companies->execute([$id_company]);
    $companies = $companies->fetchAll();
    $id_company = $_SESSION['id_company']; // Forzar la empresa del usuario
} else {
    // Superadmin: mostrar todas las empresas
    $companies = $connection->query("SELECT id_company, company_name FROM companies ORDER BY company_name")->fetchAll();
    $id_company = $_GET['id_company'] ?? '';
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_company = $_POST['id_company'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    // Validaciones backend
    if (!$id_company || !$name || !$type || $price === '') {
        $error = 'Empresa, nombre, tipo y precio son obligatorios.';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'El precio debe ser un número mayor a 0.';
    } elseif (strlen($name) < 3 || strlen($name) > 100) {
        $error = 'El nombre debe tener entre 3 y 100 caracteres.';
    } elseif (strlen($description) > 500) {
        $error = 'La descripción no puede superar los 500 caracteres.';
    } else {
        // Validar nombre único por empresa
        $stmt = $connection->prepare("SELECT COUNT(*) FROM services WHERE id_company = ? AND name = ?");
        $stmt->execute([$id_company, $name]);
        if ($stmt->fetchColumn() > 0) {
            $error = 'Ya existe un servicio con ese nombre para la empresa seleccionada.';
        } else {
            $stmt = $connection->prepare("INSERT INTO services (id_company, name, type, unit, duration, price, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$id_company, $name, $type, $unit, $duration, $price, $description, $status]);
            if ($ok) {
                audit_log('Crear servicio (superadmin)', 'Servicio: ' . $name . ', Empresa: ' . $id_company);
                $success = 'Servicio creado correctamente.';
                // Limpiar campos
                $id_company = $name = $type = $unit = $duration = $price = $description = $status = '';
            } else {
                $error = 'Error al crear el servicio.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Nuevo Servicio</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/new_service.php usando CSS variables */
    .admin-new-service-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1000px;
    }
    
    .admin-new-service-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-new-service-title i {
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
        .admin-new-service-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-new-service-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-new-service-container">
        <h2 class="admin-new-service-title"><i class="fas fa-plus mr-2"></i>Nuevo Servicio</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <form method="post" id="serviceForm" novalidate>
        <div class="form-group">
            <label>Empresa <span class="text-danger">*</span></label>
            <?php if ($id_rol == 2): ?>
                <!-- Admin de empresa: mostrar solo su empresa, no editable -->
                <input type="hidden" name="id_company" value="<?php echo htmlspecialchars($id_company); ?>">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($companies[0]['company_name'] ?? ''); ?>" readonly>
                <small class="form-text text-muted">Empresa asignada a tu cuenta.</small>
            <?php else: ?>
                <!-- Superadmin: permitir selección de empresa -->
                <select name="id_company" class="form-control" required>
                    <option value="">Seleccione empresa</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?php echo $c['id_company']; ?>" <?php if($id_company==$c['id_company']) echo 'selected'; ?>><?php echo htmlspecialchars($c['company_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Seleccione una empresa.</div>
            <?php endif; ?>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label>Nombre <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" minlength="3" maxlength="100" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
                <small class="form-text text-muted">Entre 3 y 100 caracteres. Único por empresa.</small>
                <div class="invalid-feedback">Ingrese un nombre válido.</div>
            </div>
            <div class="form-group col-md-6">
                <label>Tipo <span class="text-danger">*</span></label>
                <input type="text" name="type" class="form-control" required value="<?php echo htmlspecialchars($type ?? ''); ?>">
                <div class="invalid-feedback">Ingrese el tipo de servicio.</div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Unidad</label>
                <input type="text" name="unit" class="form-control" value="<?php echo htmlspecialchars($unit ?? ''); ?>">
            </div>
            <div class="form-group col-md-4">
                <label>Duración</label>
                <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($duration ?? ''); ?>">
            </div>
            <div class="form-group col-md-4">
                <label>Precio <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0.01" name="price" class="form-control" required value="<?php echo htmlspecialchars($price ?? ''); ?>" id="priceInput">
                <small class="form-text text-muted">Ejemplo: 10000. Previsualización: <span id="pricePreview"><?php echo isset($price) && $price !== '' ? format_price($price, $current_lang) : ''; ?></span></small>
                <div class="invalid-feedback">Ingrese un precio válido mayor a 0.</div>
            </div>
        </div>
        <div class="form-group">
            <label>Descripción</label>
            <textarea name="description" class="form-control" maxlength="500"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
            <small class="form-text text-muted">Máximo 500 caracteres.</small>
        </div>
        <div class="form-group">
            <label>Estado</label>
            <select name="status" class="form-control">
                <option value="active" <?php if(($status ?? '')==='active') echo 'selected'; ?>>Activo</option>
                <option value="inactive" <?php if(($status ?? '')==='inactive') echo 'selected'; ?>>Inactivo</option>
                <option value="suspended" <?php if(($status ?? '')==='suspended') echo 'selected'; ?>>Suspendido</option>
            </select>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i>Guardar</button>
        <button type="button" class="btn btn-secondary ml-2" id="clearBtn">Limpiar</button>
        <a href="services.php<?php echo isset($id_company) && $id_company ? '?id_company=' . urlencode($id_company) : ''; ?>" class="btn btn-outline-dark ml-2">Cancelar</a>
        </div>
    </form>
    </div>
</div>
<?php include __DIR__ . '/../views/partials/slidepanel_menu.php'; ?>

<script>
$(document).ready(function() {
    // Inicializar theme switcher
    if (typeof ThemeSwitcher !== 'undefined') {
        const themeSwitcher = new ThemeSwitcher();
        themeSwitcher.init();
    }
    
    // Previsualización de precio en tiempo real
    function formatPriceJS(price, lang) {
        price = parseFloat(price);
        if (isNaN(price)) return '';
        if (lang === 'es') {
            return '$' + price.toLocaleString('es-CL', {minimumFractionDigits: 0, maximumFractionDigits: 0});
        } else {
            return '$' + price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
    $('#priceInput').on('input', function() {
        var val = $(this).val();
        var lang = '<?php echo $current_lang; ?>';
        $('#pricePreview').text(formatPriceJS(val, lang));
    });
    // Validación visual Bootstrap
    $('#serviceForm').on('submit', function(e) {
        var form = this;
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(form).addClass('was-validated');
    });
    // Botón limpiar
    $('#clearBtn').on('click', function() {
        $('#serviceForm')[0].reset();
        $('#pricePreview').text('');
        $('#serviceForm').removeClass('was-validated');
    });
});
</script>
</body>
</html> 