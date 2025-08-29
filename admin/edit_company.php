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

$id_company = $_GET['id_company'] ?? null;
if (!$id_company) die('Empresa no especificada.');

$database = new Database();
$connection = $database->connection();
// Obtener planes disponibles
$stmt = $connection->query("SELECT id_plan, plan_name, price FROM subscription_plans WHERE is_active = 1 ORDER BY sort_order, id_plan");
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estados disponibles
$stmt = $connection->query("SELECT id_status, status_name FROM subscription_statuses ORDER BY sort_order, id_status");
$statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Obtener datos actuales
$stmt = $connection->prepare("
        SELECT c.*, 
               COALESCE(sp.plan_name, 'Sin plan') as plan_name,
               COALESCE(ss.status_name, 'inactive') as subscription_status_name,
               cs.id_plan as current_plan_id,
               cs.id_subscription_status as current_status_id
        FROM companies c
        LEFT JOIN company_subscriptions cs ON c.id_company = cs.id_company
        LEFT JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
        LEFT JOIN subscription_statuses ss ON cs.id_subscription_status = ss.id_status
        WHERE c.id_company = ?
    ");
$stmt->execute([$id_company]);
$company = $stmt->fetch();

if (!$company) die('Empresa no encontrada.');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'company_name', 'company_email', 'company_phone', 'company_address', 'company_website', 'company_tax_id',
        'max_users'
    ];
    $data = [];
    foreach ($fields as $field) {
        $data[$field] = $_POST[$field] ?? '';
    }
    
    // Procesar suscripción
    $new_plan_id = $_POST['subscription_plan'] ?? null;
    $new_status_id = $_POST['subscription_status'] ?? null;
    
    // Validación básica
    if (empty($data['company_name']) || empty($data['company_email'])) {
        $error = 'El nombre y el email son obligatorios.';
    } else {
        // Actualizar datos básicos de la empresa
        $sql = "UPDATE companies SET company_name=?, company_email=?, company_phone=?, company_address=?, company_website=?, company_tax_id=?, max_users=? WHERE id_company=?";
        $stmt = $connection->prepare($sql);
        $ok = $stmt->execute([
            $data['company_name'], $data['company_email'], $data['company_phone'], $data['company_address'],
            $data['company_website'], $data['company_tax_id'], $data['max_users'], $id_company
        ]);
        
        if ($ok && $new_plan_id && $new_status_id) {
            // Actualizar o crear suscripción
            $stmt = $connection->prepare("SELECT COUNT(*) FROM company_subscriptions WHERE id_company = ?");
            $stmt->execute([$id_company]);
            $subscription_exists = $stmt->fetchColumn();
            
            if ($subscription_exists) {
                // Actualizar suscripción existente
                $stmt = $connection->prepare("
                    UPDATE company_subscriptions 
                    SET id_plan = ?, id_subscription_status = ?, updated_at = NOW()
                    WHERE id_company = ?
                ");
                $stmt->execute([$new_plan_id, $new_status_id, $id_company]);
            } else {
                // Crear nueva suscripción
                $stmt = $connection->prepare("
                    INSERT INTO company_subscriptions (
                        id_company, id_plan, id_subscription_status, id_payment_status,
                        start_date, end_date, next_billing_date, auto_renew, current_users, 
                        id_payment_method_type, notes
                    ) VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 
                             DATE_ADD(CURDATE(), INTERVAL 1 MONTH), 1, 0, 1, 'Creada desde edición de empresa')
                ");
                $stmt->execute([$id_company, $new_plan_id, $new_status_id, 1]);
            }
        }
        if ($ok) {
            audit_log('Editar empresa', 'Empresa ID: ' . $id_company . ', Nombre: ' . $data['company_name']);
            $success = 'Datos actualizados correctamente.';
            // Refrescar datos
            $stmt = $connection->prepare("
        SELECT c.*, 
               COALESCE(sp.plan_name, 'Sin plan') as plan_name,
               COALESCE(ss.status_name, 'inactive') as subscription_status_name,
               cs.id_plan as current_plan_id,
               cs.id_subscription_status as current_status_id
        FROM companies c
        LEFT JOIN company_subscriptions cs ON c.id_company = cs.id_company
        LEFT JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
        LEFT JOIN subscription_statuses ss ON cs.id_subscription_status = ss.id_status
        WHERE c.id_company = ?
    ");
            $stmt->execute([$id_company]);
            $company = $stmt->fetch();
        } else {
            $error = 'Error al actualizar los datos.';
        }
    }
}

if (isset($_POST['delete_company'])) {
    $stmt = $connection->prepare("DELETE FROM companies WHERE id_company = ?");
    $ok = $stmt->execute([$id_company]);
    if ($ok) {
        audit_log('Eliminar empresa', 'Empresa ID: ' . $id_company . ', Nombre: ' . $company['company_name']);
        header('Location: companies.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Editar Empresa</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/edit_company.php usando CSS variables */
    .admin-edit-company-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1000px;
    }
    
    .admin-edit-company-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-edit-company-title i {
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
    
    .form-text {
        color: var(--text-muted);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-edit-company-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-edit-company-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-edit-company-container">
        <h2 class="admin-edit-company-title"><i class="fas fa-building mr-2"></i>Editar Empresa</h2>
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
        <div class="form-row">
            <div class="form-group col-md-4">
                <label>Plan de Suscripción</label>
                <select name="subscription_plan" class="form-control">
                    <option value="">Seleccionar plan</option>
                    <?php foreach ($plans as $plan): ?>
                        <option value="<?php echo $plan['id_plan']; ?>" <?php echo ($company['current_plan_id'] == $plan['id_plan']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($plan['plan_name']); ?> - $<?php echo number_format($plan['price'], 0, ',', '.'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($company['plan_name']) && $company['plan_name'] != 'Sin plan'): ?>
                    <small class="form-text text-muted">Plan actual: <?php echo htmlspecialchars($company['plan_name']); ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group col-md-4">
                <label>Estado de Suscripción</label>
                <select name="subscription_status" class="form-control">
                    <option value="">Seleccionar estado</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status['id_status']; ?>" <?php echo ($company['current_status_id'] == $status['id_status']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status['status_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($company['subscription_status_name'])): ?>
                    <small class="form-text text-muted">Estado actual: <?php echo htmlspecialchars($company['subscription_status_name']); ?></small>
                <?php endif; ?>
            </div>
            <div class="form-group col-md-4">
                <label>Máx. usuarios</label>
                <input type="number" name="max_users" class="form-control" value="<?php echo htmlspecialchars($company['max_users']); ?>" min="1">
            </div>
        </div>
        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Guardar cambios</button>
        <a href="companies.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i> Volver</a>
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