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
verificarPermisoVista($_SESSION['id_user'], 35); // admin_panel

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

$success = '';
$error = '';

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $plan_name = trim($_POST['plan_name']);
        $plan_description = trim($_POST['plan_description']);
        $price = (float)$_POST['price'];
        $billing_cycle = $_POST['billing_cycle'];
        $max_users = (int)$_POST['max_users'];
        $max_projects = (int)$_POST['max_projects'];
        $max_storage_gb = (int)$_POST['max_storage_gb'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_popular = isset($_POST['is_popular']) ? 1 : 0;
        $sort_order = (int)$_POST['sort_order'];
        
        // Construir features JSON
        $features = [
            'calendar' => isset($_POST['feature_calendar']),
            'projects' => isset($_POST['feature_projects']),
            'basic_support' => isset($_POST['feature_basic_support']),
            'advanced_support' => isset($_POST['feature_advanced_support']),
            'priority_support' => isset($_POST['feature_priority_support']),
            'api_access' => isset($_POST['feature_api_access']),
            'custom_integrations' => isset($_POST['feature_custom_integrations']),
            'white_label' => isset($_POST['feature_white_label']),
            'advanced_analytics' => isset($_POST['feature_advanced_analytics'])
        ];
        
        if ($_POST['action'] === 'add') {
            // Crear nuevo plan
            $stmt = $connection->prepare("
                INSERT INTO subscription_plans (plan_name, plan_description, price, id_billing_cycle, max_users, max_projects, max_storage_gb, features, is_active, is_popular, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$plan_name, $plan_description, $price, $billing_cycle, $max_users, $max_projects, $max_storage_gb, json_encode($features), $is_active, $is_popular, $sort_order])) {
                $success = 'Plan creado exitosamente.';
                audit_log('Crear plan de suscripción', 'Plan: ' . $plan_name);
            } else {
                $error = 'Error al crear el plan.';
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id_plan'])) {
            // Editar plan existente
            $id_plan = (int)$_POST['id_plan'];
            $stmt = $connection->prepare("
                UPDATE subscription_plans 
                SET plan_name = ?, plan_description = ?, price = ?, id_billing_cycle = ?, max_users = ?, max_projects = ?, max_storage_gb = ?, features = ?, is_active = ?, is_popular = ?, sort_order = ?
                WHERE id_plan = ?
            ");
            
            if ($stmt->execute([$plan_name, $plan_description, $price, $billing_cycle, $max_users, $max_projects, $max_storage_gb, json_encode($features), $is_active, $is_popular, $sort_order, $id_plan])) {
                $success = 'Plan actualizado exitosamente.';
                audit_log('Editar plan de suscripción', 'Plan ID: ' . $id_plan . ', Nombre: ' . $plan_name);
            } else {
                $error = 'Error al actualizar el plan.';
            }
        }
    }
}

// Eliminar plan
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_plan = (int)$_GET['delete'];
    
    // Verificar si hay suscripciones activas con este plan
    $stmt = $connection->prepare("SELECT COUNT(*) FROM company_subscriptions WHERE id_plan = ? AND subscription_status = 'active'");
    $stmt->execute([$id_plan]);
    $active_subscriptions = $stmt->fetchColumn();
    
    if ($active_subscriptions > 0) {
        $error = 'No se puede eliminar el plan porque tiene ' . $active_subscriptions . ' suscripción(es) activa(s).';
    } else {
        $stmt = $connection->prepare("DELETE FROM subscription_plans WHERE id_plan = ?");
        if ($stmt->execute([$id_plan])) {
            $success = 'Plan eliminado exitosamente.';
            audit_log('Eliminar plan de suscripción', 'Plan ID: ' . $id_plan);
        } else {
            $error = 'Error al eliminar el plan.';
        }
    }
}

// Obtener plan para editar
$edit_plan = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $connection->prepare("SELECT sp.*, bc.cycle_name FROM subscription_plans sp LEFT JOIN billing_cycles bc ON sp.id_billing_cycle = bc.id_cycle WHERE sp.id_plan = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_plan = $stmt->fetch();
}

// Obtener todos los planes
$plans = $connection->query("SELECT sp.*, bc.cycle_name FROM subscription_plans sp LEFT JOIN billing_cycles bc ON sp.id_billing_cycle = bc.id_cycle ORDER BY sp.sort_order, sp.plan_name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Gestión de Planes de Suscripción</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/subscription_plans.php usando CSS variables */
    .admin-subscription-plans-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-subscription-plans-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-subscription-plans-title i {
        color: var(--primary-color);
    }
    
    .plan-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        box-shadow: 0 4px 6px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .plan-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px var(--shadow-medium);
    }
    
    .plan-popular {
        border: 2px solid var(--primary-color);
        position: relative;
    }
    
    .plan-popular::before {
        content: "Más Popular";
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--primary-color);
        color: var(--text-light);
        padding: 2px 10px;
        border-radius: 10px;
        font-size: 0.8rem;
    }
    
    .feature-list {
        list-style: none;
        padding: 0;
    }
    
    .feature-list li {
        padding: 5px 0;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .feature-list li:last-child {
        border-bottom: none;
    }
    
    .feature-list i {
        color: var(--success-color);
        margin-right: 8px;
    }
    
    .card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        box-shadow: 0 2px 8px var(--shadow-light);
    }
    
    .card-header {
        background-color: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .card-body {
        background-color: var(--bg-card);
        color: var(--text-primary);
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
    
    .form-label {
        color: var(--text-primary);
        font-weight: 600;
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
        .admin-subscription-plans-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-subscription-plans-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-subscription-plans-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="admin-subscription-plans-title"><i class="fas fa-layer-group mr-2"></i>Gestión de Planes de Suscripción</h2>
                


                <a href="subscription_plans.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-right mr-2"></i>Plan Subscripcion
                </a>
                <a href="subscriptions.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Volver
                </a>

            </div>
        </div>
    </div>

    <!-- Mensajes -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Formulario de Plan -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $edit_plan ? 'edit' : 'plus' ?> mr-2"></i>
                        <?= $edit_plan ? 'Editar Plan' : 'Nuevo Plan' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="<?= $edit_plan ? 'edit' : 'add' ?>">
                        <?php if ($edit_plan): ?>
                            <input type="hidden" name="id_plan" value="<?= $edit_plan['id_plan'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Nombre del Plan *</label>
                            <input type="text" name="plan_name" class="form-control" value="<?= htmlspecialchars($edit_plan['plan_name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="plan_description" class="form-control" rows="3"><?= htmlspecialchars($edit_plan['plan_description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Precio (CLP) *</label>
                                <input type="number" name="price" class="form-control" value="<?= $edit_plan['price'] ?? '' ?>" min="0" step="100" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Ciclo de Facturación</label>
                                <select name="billing_cycle" class="form-control" required>
                                    <option value="">Seleccionar ciclo de facturación</option>
                                    <?php
                                    // Obtener billing_cycles disponibles desde la tabla
                                    $stmt = $connection->query("SELECT id_cycle, cycle_description FROM billing_cycles ORDER BY id_cycle");
                                    $billing_cycles = $stmt->fetchAll();
                                    foreach ($billing_cycles as $cycle):
                                    ?>
                                        <option value="<?= $cycle['id_cycle'] ?>" <?= (isset($edit_plan['id_billing_cycle']) ? $edit_plan['id_billing_cycle'] : '') == $cycle['id_cycle'] ? 'selected' : '' ?>><?= htmlspecialchars($cycle['cycle_description']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Máx. Usuarios</label>
                                <input type="number" name="max_users" class="form-control" value="<?= $edit_plan['max_users'] ?? 10 ?>" min="1">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Máx. Proyectos</label>
                                <input type="number" name="max_projects" class="form-control" value="<?= $edit_plan['max_projects'] ?? 50 ?>" min="1">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Almacenamiento (GB)</label>
                                <input type="number" name="max_storage_gb" class="form-control" value="<?= $edit_plan['max_storage_gb'] ?? 5 ?>" min="1">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Características</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_calendar" class="custom-control-input" id="feature_calendar" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['calendar']) && json_decode($edit_plan['features'], true)['calendar']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_calendar">Calendario</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_projects" class="custom-control-input" id="feature_projects" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['projects']) && json_decode($edit_plan['features'], true)['projects']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_projects">Proyectos</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_basic_support" class="custom-control-input" id="feature_basic_support" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['basic_support']) && json_decode($edit_plan['features'], true)['basic_support']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_basic_support">Soporte Básico</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_advanced_support" class="custom-control-input" id="feature_advanced_support" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['advanced_support']) && json_decode($edit_plan['features'], true)['advanced_support']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_advanced_support">Soporte Avanzado</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_priority_support" class="custom-control-input" id="feature_priority_support" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['priority_support']) && json_decode($edit_plan['features'], true)['priority_support']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_priority_support">Soporte Prioritario</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_api_access" class="custom-control-input" id="feature_api_access" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['api_access']) && json_decode($edit_plan['features'], true)['api_access']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_api_access">Acceso API</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_custom_integrations" class="custom-control-input" id="feature_custom_integrations" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['custom_integrations']) && json_decode($edit_plan['features'], true)['custom_integrations']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_custom_integrations">Integraciones Personalizadas</label>
                                    </div>
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="feature_white_label" class="custom-control-input" id="feature_white_label" <?= (isset($edit_plan['features']) && is_array(json_decode($edit_plan['features'], true)) && isset(json_decode($edit_plan['features'], true)['white_label']) && json_decode($edit_plan['features'], true)['white_label']) ? 'checked' : '' ?>>
                                        <label class="custom-control-label" for="feature_white_label">White Label</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Orden</label>
                                <input type="number" name="sort_order" class="form-control" value="<?= $edit_plan['sort_order'] ?? 0 ?>" min="0">
                            </div>
                            <div class="form-group col-md-4">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" <?= ($edit_plan['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="is_active">Activo</label>
                                </div>
                            </div>
                            <div class="form-group col-md-4">
                                <div class="custom-control custom-checkbox mt-4">
                                    <input type="checkbox" name="is_popular" class="custom-control-input" id="is_popular" <?= ($edit_plan['is_popular'] ?? 0) ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="is_popular">Más Popular</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i><?= $edit_plan ? 'Actualizar' : 'Crear' ?> Plan
                            </button>
                            <?php if ($edit_plan): ?>
                                <a href="subscription_plans.php" class="btn btn-secondary ml-2">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Planes -->
        <div class="col-md-8">
            <div class="row">
                <?php foreach ($plans as $plan): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card plan-card <?= $plan['is_popular'] ? 'plan-popular' : '' ?>">
                            <div class="card-header text-center">
                                <h5 class="mb-0"><?= htmlspecialchars($plan['plan_name']) ?></h5>
                                <h3 class="text-primary mb-0">$<?= number_format($plan['price'], 0, ',', '.') ?></h3>
                                <small class="text-muted">por <?= isset($plan['cycle_name']) ? ($plan['cycle_name'] === 'monthly' ? 'mes' : ($plan['cycle_name'] === 'quarterly' ? 'trimestre' : ($plan['cycle_name'] === 'yearly' ? 'año' : 'ciclo'))) : 'ciclo' ?></small>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?= htmlspecialchars($plan['plan_description']) ?></p>
                                
                                <ul class="feature-list">
                                    <li><i class="fas fa-users"></i> Hasta <?= $plan['max_users'] ?> usuarios</li>
                                    <li><i class="fas fa-project-diagram"></i> Hasta <?= $plan['max_projects'] ?> proyectos</li>
                                    <li><i class="fas fa-hdd"></i> <?= $plan['max_storage_gb'] ?> GB de almacenamiento</li>
                                    <?php 
                                    $features = json_decode($plan['features'], true);
                                    if ($features):
                                        foreach ($features as $feature => $enabled):
                                            if ($enabled):
                                                $feature_names = [
                                                    'calendar' => 'Calendario',
                                                    'projects' => 'Proyectos',
                                                    'basic_support' => 'Soporte Básico',
                                                    'advanced_support' => 'Soporte Avanzado',
                                                    'priority_support' => 'Soporte Prioritario',
                                                    'api_access' => 'Acceso API',
                                                    'custom_integrations' => 'Integraciones Personalizadas',
                                                    'white_label' => 'White Label',
                                                    'advanced_analytics' => 'Analytics Avanzados'
                                                ];
                                                if (isset($feature_names[$feature])):
                                    ?>
                                    <li><i class="fas fa-check"></i> <?= $feature_names[$feature] ?></li>
                                    <?php 
                                                endif;
                                            endif;
                                        endforeach;
                                    endif;
                                    ?>
                                </ul>
                            </div>
                            <div class="card-footer text-center">
                                <div class="btn-group" role="group">
                                    <a href="?edit=<?= $plan['id_plan'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?= $plan['id_plan'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este plan?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <span class="badge badge-<?= $plan['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $plan['is_active'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                    <?php if ($plan['is_popular']): ?>
                                        <span class="badge badge-primary ml-1">Popular</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

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