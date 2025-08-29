<?php
session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/lang/language_handler.php';

// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

require_once 'db/functions.php';
require_once 'lang/Languaje.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

require_once 'security/check_access.php';

// Verificar permiso para crear servicios
verificarPermisoVista($_SESSION['id_user'], 19); // new_service

require_once 'theme_handler.php';
$lang = Language::getInstance();
$current_lang = $_SESSION['lang'] ?? $lang->language ?? 'es';
function format_price($price, $lang) {
    if ($lang === 'es') {
        return '$' . number_format($price, 0, ',', '.');
    } else {
        return '$' . number_format($price, 2, '.', ',');
    }
}
$database = new Database();
$connection = $database->connection();
$id_company = $_SESSION['id_company'];
$error = '';
$success = '';
$name = $type = $unit = $duration = $price = $description = $status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = $_POST['status'] ?? 'active';
    // Validaciones backend
    if (!$name || !$type || $price === '') {
        $error = 'Nombre, tipo y precio son obligatorios.';
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
            $error = 'Ya existe un servicio con ese nombre para tu empresa.';
        } else {
            $stmt = $connection->prepare("INSERT INTO services (id_company, name, type, unit, duration, price, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ok = $stmt->execute([$id_company, $name, $type, $unit, $duration, $price, $description, $status]);
            if ($ok) {
                audit_log('Crear servicio', 'Servicio: ' . $name);
                $success = 'Servicio creado correctamente.';
                $name = $type = $unit = $duration = $price = $description = $status = '';
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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="js/theme-switcher.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <style>
    /* Estilos específicos para new_service.php usando CSS variables */
    .new-service-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 800px;
    }
    
    .new-service-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .new-service-title i {
        color: var(--primary-color);
    }
    
    .form-group label {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 8px;
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
    
    .form-text {
        color: var(--text-muted);
    }
    
    .text-danger {
        color: var(--danger-color) !important;
    }
    
    .btn-success {
        background: linear-gradient(135deg, var(--success-color) 0%, var(--primary-color) 100%);
        border: none;
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px var(--shadow-medium);
    }
    
    .btn-secondary {
        background-color: var(--secondary-color);
        border: none;
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .btn-outline-dark {
        border: 2px solid var(--border-color);
        color: var(--text-primary);
        border-radius: 25px;
        padding: 12px 25px;
        font-weight: 600;
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .btn-outline-dark:hover {
        background-color: var(--bg-secondary);
        border-color: var(--text-primary);
        color: var(--text-primary);
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
    
    .alert-success {
        background-color: var(--success-color-alpha);
        color: var(--success-color);
    }
    
    .invalid-feedback {
        color: var(--danger-color);
    }
    
    .was-validated .form-control:invalid {
        border-color: var(--danger-color);
    }
    
    .was-validated .form-control:valid {
        border-color: var(--success-color);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .new-service-container {
            padding: 20px;
            margin: 10px;
        }
        
        .new-service-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once 'views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="new-service-container">
        <h2 class="new-service-title"><i class="fas fa-plus mr-2"></i>Nuevo Servicio</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
    <form method="post" id="serviceForm" novalidate>
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
        <a href="services.php" class="btn btn-outline-dark ml-2">Cancelar</a>
    </form>
    </div>
</div>
<script>
// Previsualización de precio en tiempo real
$(document).ready(function() {
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
    
    // Inicializar theme switcher
    if (typeof ThemeSwitcher !== 'undefined') {
        const themeSwitcher = new ThemeSwitcher();
        themeSwitcher.init();
    }
});
</script>
</body>
</html> 