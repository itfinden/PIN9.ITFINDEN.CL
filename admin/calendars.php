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

// Debug: Verificar que las funciones estén disponibles
if (!function_exists('limpiarString')) {
    error_log('ERROR: Función limpiarString no encontrada');
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../security/check_access.php';

// Permitir solo admin empresa o superadmin
verificarPermisoVista($_SESSION['id_user'], 33); // Ajusta el id_permission real

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();
$id_user = $_SESSION['id_user'];
$id_rol = $_SESSION['id_rol'] ?? null;
$id_company = $_SESSION['id_company'] ?? null;

// Determinar empresas visibles
if ($id_rol == 2) {
    // Admin empresa: solo su empresa
    $companies = $connection->prepare('SELECT id_company, company_name FROM companies WHERE id_company = ?');
    $companies->execute([$id_company]);
    $companies = $companies->fetchAll();
} else {
    // Superadmin: todas
    $companies = $connection->query('SELECT id_company, company_name FROM companies')->fetchAll();
}

// Alta calendario
if (isset($_POST['action']) && $_POST['action'] === 'add') {
    try {
        $id_company_new = (int)$_POST['id_company'];
        $calendar_name = trim($_POST['calendar_name']);
        $colour = trim($_POST['colour']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Validaciones básicas
        if (empty($calendar_name) || empty($colour)) {
            throw new Exception('Nombre y color son requeridos');
        }
        
        $stmt = $connection->prepare('INSERT INTO calendar_companies (id_company, calendar_name, colour, is_default, is_active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$id_company_new, $calendar_name, $colour, $is_default]);
        
        if ($is_default) {
            // Solo uno por empresa
            $connection->prepare('UPDATE calendar_companies SET is_default=0 WHERE id_company=? AND id_calendar_companies!=?')->execute([$id_company_new, $connection->lastInsertId()]);
        }
        
        header('Location: calendars.php?success=1'); 
        exit;
    } catch (Exception $e) {
        error_log('Error al crear calendario: ' . $e->getMessage());
        header('Location: calendars.php?error=' . urlencode($e->getMessage())); 
        exit;
    }
}

// Edición calendario
if (isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id_calendar_companies'])) {
    try {
        $id_cal = (int)$_POST['id_calendar_companies'];
        $calendar_name = trim($_POST['calendar_name']);
        $colour = trim($_POST['colour']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        // Validaciones básicas
        if (empty($calendar_name) || empty($colour)) {
            throw new Exception('Nombre y color son requeridos');
        }
        
        $stmt = $connection->prepare('UPDATE calendar_companies SET calendar_name=?, colour=?, is_default=? WHERE id_calendar_companies=?');
        $stmt->execute([$calendar_name, $colour, $is_default, $id_cal]);
        
        if ($is_default) {
            $id_company_edit = (int)$_POST['id_company'];
            $connection->prepare('UPDATE calendar_companies SET is_default=0 WHERE id_company=? AND id_calendar_companies!=?')->execute([$id_company_edit, $id_cal]);
        }
        
        header('Location: calendars.php?success=1'); 
        exit;
    } catch (Exception $e) {
        error_log('Error al editar calendario: ' . $e->getMessage());
        header('Location: calendars.php?error=' . urlencode($e->getMessage())); 
        exit;
    }
}

// Baja lógica
if (isset($_GET['delete'])) {
    $id_cal = (int)$_GET['delete'];
    $connection->prepare('UPDATE calendar_companies SET is_active=0 WHERE id_calendar_companies=?')->execute([$id_cal]);
    header('Location: calendars.php'); exit;
}

// Listado
$where = ($id_rol == 2) ? 'WHERE cc.id_company = ' . (int)$id_company : '';
$calendars = $connection->query('SELECT cc.*, c.company_name FROM calendar_companies cc JOIN companies c ON cc.id_company = c.id_company ' . $where . ' ORDER BY c.company_name, cc.calendar_name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Calendarios de Empresa</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/calendars.php usando CSS variables */
    .admin-calendars-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-calendars-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-calendars-title i {
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
    
    .calendar-color {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 10px;
        border: 2px solid var(--border-color);
        box-shadow: 0 2px 4px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .calendar-color:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 8px var(--shadow-medium);
    }
    
    .table td {
        vertical-align: middle;
    }
    
    /* Color Picker Styles */
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .color-preview {
        width: 38px;
        height: 38px;
        border-radius: 6px;
        border: 2px solid var(--border-color);
        box-shadow: 0 1px 3px var(--shadow-light);
        cursor: pointer;
        display: inline-block;
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .color-preview:hover {
        transform: scale(1.05);
        box-shadow: 0 2px 8px var(--shadow-medium);
    }
    
    input[type="color"].form-control {
        opacity: 0;
        width: 38px;
        height: 38px;
        position: absolute;
        left: 0;
        top: 0;
        cursor: pointer;
    }
    
    .color-picker-label {
        position: relative;
        display: inline-block;
    }
    
    .color-value {
        font-size: 13px;
        color: var(--text-muted);
        font-weight: 500;
    }
    
    .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 12px;
    }
    
    .badge-success {
        background-color: var(--success-color, #28a745);
        color: white;
    }
    
    .badge-secondary {
        background-color: var(--secondary-color, #6c757d);
        color: white;
    }
    
    .btn-sm {
        padding: 4px 8px;
        font-size: 0.8rem;
        border-radius: 15px;
    }
    
    .alert {
        border-radius: 10px;
        border: none;
        box-shadow: 0 2px 8px var(--shadow-light);
        margin-bottom: 20px;
    }
    
    .alert-success {
        background-color: var(--success-color, #d4edda);
        color: var(--success-text, #155724);
        border-left: 4px solid var(--success-color, #28a745);
    }
    
    .alert-danger {
        background-color: var(--danger-color, #f8d7da);
        color: var(--danger-text, #721c24);
        border-left: 4px solid var(--danger-color, #dc3545);
    }
    
    .alert .close {
        color: inherit;
        opacity: 0.7;
    }
    
    .alert .close:hover {
        opacity: 1;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-calendars-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-calendars-title {
            font-size: 1.5rem;
        }
        
        .form-inline {
            flex-direction: column;
            align-items: stretch;
        }
        
        .form-group {
            margin-bottom: 15px;
            margin-right: 0;
        }
        
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .btn-sm {
            padding: 3px 6px;
            font-size: 0.7rem;
        }
    }
    
    @media (max-width: 576px) {
        .admin-calendars-container {
            padding: 15px;
            margin: 5px;
        }
        
        .admin-calendars-title {
            font-size: 1.3rem;
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }
        
        .table-responsive {
            font-size: 0.8rem;
        }
        
        .color-preview {
            width: 30px;
            height: 30px;
        }
        
        input[type="color"].form-control {
            width: 30px;
            height: 30px;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-calendars-container">
        <h2 class="admin-calendars-title"><i class="fas fa-calendar-alt mr-2"></i>Calendarios de Empresa</h2>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                <strong>¡Éxito!</strong> Calendario guardado correctamente.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>Error:</strong> <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
    <button class="btn btn-success mb-3" data-toggle="collapse" data-target="#addForm"><i class="fas fa-plus mr-1"></i>Nuevo Calendario</button>
    <div id="addForm" class="collapse mb-4">
        <form method="POST" class="form-inline">
            <input type="hidden" name="action" value="add">
            <div class="form-group mr-2">
                <label for="id_company" class="mr-2">Empresa</label>
                <select name="id_company" class="form-control" required>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= $c['id_company'] ?>"><?= htmlspecialchars($c['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mr-2">
                <label for="calendar_name" class="mr-2">Nombre</label>
                <input type="text" name="calendar_name" class="form-control" required>
            </div>
            <!-- === INICIO COLOR PICKER PROFESIONAL === -->
            <div class="form-group">
                <label for="colour" class="mr-2">Color</label>
                <div class="color-picker-wrapper">
                    <label class="color-picker-label">
                        <span class="color-preview" id="colorPreview" style="background: #0275d8;"></span>
                        <input type="color" name="colour" class="form-control" id="colour" value="#0275d8">
                    </label>
                    <span id="colorValue" class="color-value">#0275d8</span>
                </div>
            </div>
            <!-- === FIN COLOR PICKER PROFESIONAL === -->
            <div class="form-group mr-2">
                <label class="mr-2">Por defecto</label>
                <input type="checkbox" name="is_default" value="1">
            </div>
            <button type="submit" class="btn btn-primary">Crear</button>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="thead-dark">
                <tr>
                    <th>ID</th>
                    <th>Empresa</th>
                    <th>Nombre</th>
                    <th>Color</th>
                    <th>Por defecto</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($calendars as $cal): ?>
                <tr>
                    <td><?= $cal['id_calendar_companies'] ?></td>
                    <td><?= htmlspecialchars($cal['company_name']) ?></td>
                    <td><?= htmlspecialchars($cal['calendar_name']) ?></td>
                    <td>
                        <div class="calendar-color" style="background-color: <?= htmlspecialchars($cal['colour']) ?>;"></div>
                        <span class="color-value"><?= htmlspecialchars($cal['colour']) ?></span>
                    </td>
                    <td><?= $cal['is_default'] ? '<span class="badge badge-success">Sí</span>' : '' ?></td>
                    <td><?= $cal['is_active'] ? '<span class="badge badge-success">Sí</span>' : '<span class="badge badge-secondary">No</span>' ?></td>
                    <td>
                        <button class="btn btn-sm btn-info" data-toggle="collapse" data-target="#editForm<?= $cal['id_calendar_companies'] ?>">Editar</button>
                        <a href="?delete=<?= $cal['id_calendar_companies'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este calendario?')">Eliminar</a>
                    </td>
                </tr>
                <tr class="collapse" id="editForm<?= $cal['id_calendar_companies'] ?>">
                    <td colspan="7">
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id_calendar_companies" value="<?= $cal['id_calendar_companies'] ?>">
                            <input type="hidden" name="id_company" value="<?= $cal['id_company'] ?>">
                            <div class="form-group mr-2">
                                <label for="calendar_name" class="mr-2">Nombre</label>
                                <input type="text" name="calendar_name" class="form-control" value="<?= htmlspecialchars($cal['calendar_name']) ?>" required>
                            </div>
                            <!-- === INICIO COLOR PICKER PROFESIONAL (EDICIÓN) === -->
                            <div class="form-group mr-2">
                                <label for="colour" class="mr-2">Color</label>
                                <div class="color-picker-wrapper">
                                    <label class="color-picker-label">
                                        <span class="color-preview" id="colorPreviewEdit<?= $cal['id_calendar_companies'] ?>" style="background: <?= htmlspecialchars($cal['colour']) ?>;"></span>
                                        <input type="color" name="colour" class="form-control" id="colourEdit<?= $cal['id_calendar_companies'] ?>" value="<?= htmlspecialchars($cal['colour']) ?>">
                                    </label>
                                    <span id="colorValueEdit<?= $cal['id_calendar_companies'] ?>" class="color-value"><?= htmlspecialchars($cal['colour']) ?></span>
                                </div>
                            </div>
                            <!-- === FIN COLOR PICKER PROFESIONAL (EDICIÓN) === -->
                            <div class="form-group mr-2">
                                <label class="mr-2">Por defecto</label>
                                <input type="checkbox" name="is_default" value="1" <?= $cal['is_default'] ? 'checked' : '' ?>>
                            </div>
                            <button type="submit" class="btn btn-primary">Guardar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<?php include __DIR__ . '/../views/partials/slidepanel_menu.php'; ?>

<script>
$(document).ready(function() {
    // Inicializar theme switcher
    if (typeof ThemeSwitcher !== 'undefined') {
        const themeSwitcher = new ThemeSwitcher();
        themeSwitcher.init();
    }
    
    // Inicializar color pickers
    function initColorPickers() {
        // Color picker principal (crear)
        const colorInput = document.getElementById('colour');
        const colorPreview = document.getElementById('colorPreview');
        const colorValue = document.getElementById('colorValue');
        
        if (colorInput && colorPreview && colorValue) {
            colorInput.addEventListener('input', function() {
                colorPreview.style.background = this.value;
                colorValue.textContent = this.value;
            });
        }
        
        // Color pickers de edición
        document.querySelectorAll('input[id^="colourEdit"]').forEach(function(input) {
            const id = input.id.replace('colourEdit', '');
            const preview = document.getElementById('colorPreviewEdit' + id);
            const value = document.getElementById('colorValueEdit' + id);
            
            if (preview && value) {
                input.addEventListener('input', function() {
                    preview.style.background = this.value;
                    value.textContent = this.value;
                });
            }
        });
    }
    
    // Inicializar cuando el DOM esté listo
    initColorPickers();
    
    // Re-inicializar después de cambios dinámicos (como collapse)
    $(document).on('shown.bs.collapse', function() {
        initColorPickers();
    });
});
</script>
</body>
</html> 