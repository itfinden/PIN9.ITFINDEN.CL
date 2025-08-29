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

// Verificar permiso para gestionar métodos de pago
verificarPermisoVista($_SESSION['id_user'], 37); // admin_panel

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

$success = '';
$error = '';

// Procesar formulario de creación/edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $method_name = trim($_POST['method_name']);
        $id_payment_method_type = (int)$_POST['id_payment_method_type'];
        $description = trim($_POST['description']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Construir config_data JSON según el tipo
        $config_data = [];
        
        if ($_POST['action'] === 'add') {
            // Crear nuevo método
            $stmt = $connection->prepare("
                INSERT INTO payment_methods (method_name, id_payment_method_type, description, is_active, config_data)
                VALUES (?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$method_name, $id_payment_method_type, $description, $is_active, json_encode($config_data)])) {
                $success = 'Método de pago creado exitosamente.';
                audit_log('Crear método de pago', 'Método: ' . $method_name);
            } else {
                $error = 'Error al crear el método de pago.';
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['id_method'])) {
            // Editar método existente
            $id_method = (int)$_POST['id_method'];
            
            // Obtener configuración específica según el tipo
            $stmt = $connection->prepare("SELECT type_name FROM payment_method_types WHERE id_type = ?");
            $stmt->execute([$id_payment_method_type]);
            $type_name = $stmt->fetchColumn();
            
            switch ($type_name) {
                case 'bank_transfer':
                    $config_data = [
                        'bank_name' => $_POST['bank_name'] ?? '',
                        'account_type' => $_POST['account_type'] ?? '',
                        'account_number' => $_POST['account_number'] ?? '',
                        'rut' => $_POST['rut'] ?? '',
                        'email' => $_POST['email'] ?? ''
                    ];
                    break;
                    
                case 'webpay':
                    $config_data = [
                        'commerce_code' => $_POST['commerce_code'] ?? '',
                        'api_key' => $_POST['api_key'] ?? '',
                        'environment' => $_POST['environment'] ?? 'test',
                        'return_url' => $_POST['return_url'] ?? ''
                    ];
                    break;
                    
                case 'paypal':
                    $config_data = [
                        'client_id' => $_POST['client_id'] ?? '',
                        'secret' => $_POST['secret'] ?? '',
                        'environment' => $_POST['environment'] ?? 'sandbox',
                        'currency' => $_POST['currency'] ?? 'CLP'
                    ];
                    break;
                    
                case 'credit_card':
                    $config_data = [
                        'merchant_id' => $_POST['merchant_id'] ?? '',
                        'api_key' => $_POST['api_key'] ?? '',
                        'environment' => $_POST['environment'] ?? 'test'
                    ];
                    break;
                    
                default:
                    $config_data = [];
            }
            
            $stmt = $connection->prepare("
                UPDATE payment_methods 
                SET method_name = ?, id_payment_method_type = ?, description = ?, is_active = ?, config_data = ?
                WHERE id_method = ?
            ");
            
            if ($stmt->execute([$method_name, $id_payment_method_type, $description, $is_active, json_encode($config_data), $id_method])) {
                $success = 'Método de pago actualizado exitosamente.';
                audit_log('Editar método de pago', 'Método ID: ' . $id_method . ', Nombre: ' . $method_name);
            } else {
                $error = 'Error al actualizar el método de pago.';
            }
        }
    }
}

// Eliminar método
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id_method = (int)$_GET['delete'];
    
    // Verificar si hay transacciones usando este método
    $stmt = $connection->prepare("SELECT COUNT(*) FROM payment_transactions WHERE id_payment_method = ?");
    $stmt->execute([$id_method]);
    $transactions_count = $stmt->fetchColumn();
    
    if ($transactions_count > 0) {
        $error = 'No se puede eliminar el método porque tiene ' . $transactions_count . ' transacción(es) asociada(s).';
    } else {
        $stmt = $connection->prepare("DELETE FROM payment_methods WHERE id_method = ?");
        if ($stmt->execute([$id_method])) {
            $success = 'Método de pago eliminado exitosamente.';
            audit_log('Eliminar método de pago', 'Método ID: ' . $id_method);
        } else {
            $error = 'Error al eliminar el método de pago.';
        }
    }
}

// Obtener método para editar
$edit_method = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $connection->prepare("SELECT * FROM payment_methods WHERE id_method = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_method = $stmt->fetch();
}

// Obtener todos los métodos de pago
$methods = $connection->query("
    SELECT pm.*, pmt.type_name, pmt.icon, pmt.type_description
    FROM payment_methods pm
    JOIN payment_method_types pmt ON pm.id_payment_method_type = pmt.id_type
    ORDER BY pmt.sort_order, pm.method_name
")->fetchAll();

// Obtener tipos de método de pago
$payment_types = $connection->query("SELECT * FROM payment_method_types WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Gestión de Métodos de Pago</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/payment_methods.php usando CSS variables */
    .admin-payment-methods-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1200px;
    }
    
    .admin-payment-methods-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-payment-methods-title i {
        color: var(--primary-color);
    }
    
    .method-card {
        border-radius: 15px;
        box-shadow: 0 4px 6px var(--shadow-light);
        transition: transform 0.2s;
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
    }
    
    .method-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px var(--shadow-medium);
    }
    
    .method-icon {
        font-size: 2rem;
        margin-bottom: 10px;
        color: var(--primary-color);
    }
    
    .config-section {
        background: var(--bg-secondary);
        border-radius: 10px;
        padding: 15px;
        margin-top: 15px;
        border: 1px solid var(--border-color);
    }
    
    .config-section h6 {
        color: var(--text-primary);
        margin-bottom: 15px;
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
    
    .card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
    }
    
    .card-header {
        background-color: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .card-body {
        color: var(--text-primary);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-payment-methods-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-payment-methods-title {
            font-size: 1.5rem;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-payment-methods-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="admin-payment-methods-title"><i class="fas fa-credit-card mr-2"></i>Gestión de Métodos de Pago</h2>
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
        <!-- Formulario de Método de Pago -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $edit_method ? 'edit' : 'plus' ?> mr-2"></i>
                        <?= $edit_method ? 'Editar Método' : 'Nuevo Método de Pago' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" id="paymentMethodForm">
                        <input type="hidden" name="action" value="<?= $edit_method ? 'edit' : 'add' ?>">
                        <?php if ($edit_method): ?>
                            <input type="hidden" name="id_method" value="<?= $edit_method['id_method'] ?>">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Nombre del Método *</label>
                            <input type="text" name="method_name" class="form-control" value="<?= htmlspecialchars($edit_method['method_name'] ?? '') ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Tipo de Método *</label>
                            <select name="id_payment_method_type" class="form-control" required onchange="showConfigFields(this.value)">
                                <option value="">Seleccione un tipo</option>
                                <?php foreach ($payment_types as $type): ?>
                                    <option value="<?= $type['id_type'] ?>" <?= ($edit_method['id_payment_method_type'] ?? '') == $type['id_type'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['type_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($edit_method['description'] ?? '') ?></textarea>
                        </div>

                        <!-- Configuración específica por tipo -->
                        <div id="configFields">
                            <!-- Los campos se cargan dinámicamente -->
                        </div>

                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" name="is_active" class="custom-control-input" id="is_active" <?= ($edit_method['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="is_active">Activo</label>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i><?= $edit_method ? 'Actualizar' : 'Crear' ?> Método
                            </button>
                            <?php if ($edit_method): ?>
                                <a href="payment_methods.php" class="btn btn-secondary ml-2">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Lista de Métodos de Pago -->
        <div class="col-md-8">
            <div class="row">
                <?php foreach ($methods as $method): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card method-card">
                            <div class="card-body text-center">
                                <i class="<?= $method['icon'] ?> method-icon text-primary"></i>
                                <h5 class="card-title"><?= htmlspecialchars($method['method_name']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars($method['type_description']) ?></p>
                                
                                <?php if ($method['description']): ?>
                                    <p class="card-text"><?= htmlspecialchars($method['description']) ?></p>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <span class="badge badge-<?= $method['is_active'] ? 'success' : 'secondary' ?>">
                                        <?= $method['is_active'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                    <span class="badge badge-info ml-1">
                                        <?= ucfirst(str_replace('_', ' ', $method['type_name'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="?edit=<?= $method['id_method'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-info" onclick="viewConfig(<?= $method['id_method'] ?>)">
                                        <i class="fas fa-cog"></i>
                                    </button>
                                    <a href="?delete=<?= $method['id_method'] ?>" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar este método de pago?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
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

<!-- Modal Ver Configuración -->
<div class="modal fade" id="configModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configuración del Método de Pago</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="configDetails">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<?php include __DIR__ . '/../views/partials/slidepanel_menu.php'; ?>

<script>
// Mostrar campos de configuración según el tipo
function showConfigFields(typeId) {
    const configFields = document.getElementById('configFields');
    const typeSelect = document.querySelector('select[name="id_payment_method_type"]');
    const selectedOption = typeSelect.options[typeSelect.selectedIndex];
    const typeName = selectedOption.text.toLowerCase();
    
    let html = '';
    
    switch (typeName) {
        case 'transferencia bancaria':
            html = `
                <div class="config-section">
                    <h6><i class="fas fa-university mr-2"></i>Configuración Bancaria</h6>
                    <div class="form-group">
                        <label>Nombre del Banco</label>
                        <input type="text" name="bank_name" class="form-control" placeholder="Ej: Banco de Chile">
                    </div>
                    <div class="form-group">
                        <label>Tipo de Cuenta</label>
                        <select name="account_type" class="form-control">
                            <option value="Cuenta Corriente">Cuenta Corriente</option>
                            <option value="Cuenta Vista">Cuenta Vista</option>
                            <option value="Cuenta de Ahorro">Cuenta de Ahorro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Número de Cuenta</label>
                        <input type="text" name="account_number" class="form-control" placeholder="12345678">
                    </div>
                    <div class="form-group">
                        <label>RUT Titular</label>
                        <input type="text" name="rut" class="form-control" placeholder="12.345.678-9">
                    </div>
                    <div class="form-group">
                        <label>Email para Notificaciones</label>
                        <input type="email" name="email" class="form-control" placeholder="pagos@empresa.cl">
                    </div>
                </div>
            `;
            break;
            
        case 'webpay plus':
            html = `
                <div class="config-section">
                    <h6><i class="fas fa-credit-card mr-2"></i>Configuración WebPay</h6>
                    <div class="form-group">
                        <label>Código de Comercio</label>
                        <input type="text" name="commerce_code" class="form-control" placeholder="597055555532">
                    </div>
                    <div class="form-group">
                        <label>API Key</label>
                        <input type="text" name="api_key" class="form-control" placeholder="Tu API Key de WebPay">
                    </div>
                    <div class="form-group">
                        <label>Ambiente</label>
                        <select name="environment" class="form-control">
                            <option value="test">Test/Desarrollo</option>
                            <option value="production">Producción</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>URL de Retorno</label>
                        <input type="url" name="return_url" class="form-control" placeholder="https://tuempresa.cl/payment/return">
                    </div>
                </div>
            `;
            break;
            
        case 'paypal':
            html = `
                <div class="config-section">
                    <h6><i class="fab fa-paypal mr-2"></i>Configuración PayPal</h6>
                    <div class="form-group">
                        <label>Client ID</label>
                        <input type="text" name="client_id" class="form-control" placeholder="Tu PayPal Client ID">
                    </div>
                    <div class="form-group">
                        <label>Secret</label>
                        <input type="password" name="secret" class="form-control" placeholder="Tu PayPal Secret">
                    </div>
                    <div class="form-group">
                        <label>Ambiente</label>
                        <select name="environment" class="form-control">
                            <option value="sandbox">Sandbox (Desarrollo)</option>
                            <option value="live">Live (Producción)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Moneda</label>
                        <select name="currency" class="form-control">
                            <option value="CLP">Peso Chileno (CLP)</option>
                            <option value="USD">Dólar Americano (USD)</option>
                            <option value="EUR">Euro (EUR)</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        case 'tarjeta de crédito/débito':
            html = `
                <div class="config-section">
                    <h6><i class="fas fa-credit-card mr-2"></i>Configuración de Tarjeta</h6>
                    <div class="form-group">
                        <label>Merchant ID</label>
                        <input type="text" name="merchant_id" class="form-control" placeholder="Tu Merchant ID">
                    </div>
                    <div class="form-group">
                        <label>API Key</label>
                        <input type="text" name="api_key" class="form-control" placeholder="Tu API Key">
                    </div>
                    <div class="form-group">
                        <label>Ambiente</label>
                        <select name="environment" class="form-control">
                            <option value="test">Test/Desarrollo</option>
                            <option value="production">Producción</option>
                        </select>
                    </div>
                </div>
            `;
            break;
            
        default:
            html = '<div class="config-section"><p class="text-muted">No se requiere configuración adicional para este método.</p></div>';
    }
    
    configFields.innerHTML = html;
}

// Ver configuración
function viewConfig(methodId) {
    // Aquí se cargaría la configuración específica del método
    // Por ahora mostramos un mensaje
    $('#configModal').modal('show');
    $('#configDetails').html(`
        <div class="text-center">
            <i class="fas fa-cog fa-3x text-muted mb-3"></i>
            <h5>Configuración del Método</h5>
            <p class="text-muted">La configuración específica se mostrará aquí.</p>
        </div>
    `);
}

// Cargar configuración al editar
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar theme switcher
    if (typeof ThemeSwitcher !== 'undefined') {
        const themeSwitcher = new ThemeSwitcher();
        themeSwitcher.init();
    }
    
    const typeSelect = document.querySelector('select[name="id_payment_method_type"]');
    if (typeSelect && typeSelect.value) {
        showConfigFields(typeSelect.value);
    }
});
</script>
</body>
</html> 