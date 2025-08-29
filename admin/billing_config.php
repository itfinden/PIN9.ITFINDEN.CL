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
verificarPermisoVista($_SESSION['id_user'], 39); // admin_panel

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

$success = '';
$error = '';

// Procesar formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_config') {
        try {
            $connection->beginTransaction();
            
            // Actualizar cada configuración
            $configs = [
                'company_name' => $_POST['company_name'] ?? '',
                'company_rut' => $_POST['company_rut'] ?? '',
                'company_address' => $_POST['company_address'] ?? '',
                'company_phone' => $_POST['company_phone'] ?? '',
                'company_email' => $_POST['company_email'] ?? '',
                'tax_rate' => (float)($_POST['tax_rate'] ?? 19),
                'invoice_prefix' => $_POST['invoice_prefix'] ?? 'FAC',
                'payment_terms_days' => (int)($_POST['payment_terms_days'] ?? 30),
                'auto_generate_invoices' => isset($_POST['auto_generate_invoices']) ? 1 : 0,
                'send_payment_reminders' => isset($_POST['send_payment_reminders']) ? 1 : 0,
                'reminder_days_before' => (int)($_POST['reminder_days_before'] ?? 7),
                'overdue_reminder_interval' => (int)($_POST['overdue_reminder_interval'] ?? 3),
                'max_overdue_reminders' => (int)($_POST['max_overdue_reminders'] ?? 5),
                'currency' => $_POST['currency'] ?? 'CLP',
                'timezone' => $_POST['timezone'] ?? 'America/Santiago',
                'invoice_template' => $_POST['invoice_template'] ?? 'default',
                'email_template_header' => $_POST['email_template_header'] ?? '',
                'email_template_footer' => $_POST['email_template_footer'] ?? '',
                'smtp_host' => $_POST['smtp_host'] ?? '',
                'smtp_port' => (int)($_POST['smtp_port'] ?? 587),
                'smtp_username' => $_POST['smtp_username'] ?? '',
                'smtp_password' => $_POST['smtp_password'] ?? '',
                'smtp_encryption' => $_POST['smtp_encryption'] ?? 'tls'
            ];
            
            foreach ($configs as $key => $value) {
                $stmt = $connection->prepare("
                    INSERT INTO billing_config (config_key, config_value, config_type) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
                ");
                
                $config_type = is_numeric($value) ? 'number' : (is_bool($value) ? 'boolean' : 'string');
                $stmt->execute([$key, $value, $config_type]);
            }
            
            $connection->commit();
            $success = 'Configuración actualizada exitosamente.';
            audit_log('Actualizar configuración de facturación', 'Configuración actualizada por superadmin');
            
        } catch (Exception $e) {
            $connection->rollBack();
            $error = 'Error al actualizar la configuración: ' . $e->getMessage();
        }
    }
}

// Obtener configuración actual
$configs = [];
$stmt = $connection->query("SELECT config_key, config_value, config_type FROM billing_config");
while ($row = $stmt->fetch()) {
    $configs[$row['config_key']] = [
        'value' => $row['config_value'],
        'type' => $row['config_type']
    ];
}

// Función helper para obtener valor de configuración
function getConfig($key, $default = '') {
    global $configs;
    return $configs[$key]['value'] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Configuración de Facturación</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/billing_config.php usando CSS variables */
    .admin-billing-config-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-billing-config-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-billing-config-title i {
        color: var(--primary-color);
    }
    
    .config-section {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px var(--shadow-light);
    }
    
    .config-section h4 {
        color: var(--text-primary);
        margin-bottom: 20px;
        font-weight: 600;
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
    
    .custom-control-label {
        color: var(--text-primary);
    }
    
    .custom-control-input:checked ~ .custom-control-label::before {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-billing-config-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-billing-config-title {
            font-size: 1.5rem;
        }
        
        .config-section {
            padding: 20px;
        }
    }
    </style>
        }
        .config-section h5 {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .form-group label {
            font-weight: 600;
            color: #495057;
        }
        .help-text {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .config-card {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-billing-config-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="admin-billing-config-title"><i class="fas fa-cog mr-2"></i>Configuración de Facturación</h2>
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

    <form method="post">
        <input type="hidden" name="action" value="update_config">

        <!-- Información de la Empresa -->
        <div class="config-section">
            <h5><i class="fas fa-building mr-2"></i>Información de la Empresa</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Nombre de la Empresa *</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars(getConfig('company_name', 'ITFINDEN SPA')) ?>" required>
                        <div class="help-text">Nombre que aparecerá en las facturas</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>RUT de la Empresa *</label>
                        <input type="text" name="company_rut" class="form-control" value="<?= htmlspecialchars(getConfig('company_rut', '76.123.456-7')) ?>" required>
                        <div class="help-text">RUT en formato XX.XXX.XXX-X</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Dirección de la Empresa</label>
                        <input type="text" name="company_address" class="form-control" value="<?= htmlspecialchars(getConfig('company_address', 'Av. Providencia 1234, Santiago')) ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input type="text" name="company_phone" class="form-control" value="<?= htmlspecialchars(getConfig('company_phone', '+56 2 2345 6789')) ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Email de Facturación</label>
                        <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars(getConfig('company_email', 'facturacion@itfinden.cl')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración de Facturación -->
        <div class="config-section">
            <h5><i class="fas fa-file-invoice mr-2"></i>Configuración de Facturación</h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Tasa de IVA (%)</label>
                        <input type="number" name="tax_rate" class="form-control" value="<?= getConfig('tax_rate', 19) ?>" min="0" max="100" step="0.1">
                        <div class="help-text">Porcentaje de IVA aplicado</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Prefijo de Facturas</label>
                        <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars(getConfig('invoice_prefix', 'FAC')) ?>" maxlength="10">
                        <div class="help-text">Prefijo para numeración</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Días para Pago</label>
                        <input type="number" name="payment_terms_days" class="form-control" value="<?= getConfig('payment_terms_days', 30) ?>" min="1" max="365">
                        <div class="help-text">Plazo de pago en días</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Moneda</label>
                        <select name="currency" class="form-control">
                            <option value="CLP" <?= getConfig('currency') === 'CLP' ? 'selected' : '' ?>>Peso Chileno (CLP)</option>
                            <option value="USD" <?= getConfig('currency') === 'USD' ? 'selected' : '' ?>>Dólar Americano (USD)</option>
                            <option value="EUR" <?= getConfig('currency') === 'EUR' ? 'selected' : '' ?>>Euro (EUR)</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Zona Horaria</label>
                        <select name="timezone" class="form-control">
                            <option value="America/Santiago" <?= getConfig('timezone') === 'America/Santiago' ? 'selected' : '' ?>>Chile (America/Santiago)</option>
                            <option value="America/New_York" <?= getConfig('timezone') === 'America/New_York' ? 'selected' : '' ?>>Nueva York (America/New_York)</option>
                            <option value="Europe/Madrid" <?= getConfig('timezone') === 'Europe/Madrid' ? 'selected' : '' ?>>Madrid (Europe/Madrid)</option>
                            <option value="UTC" <?= getConfig('timezone') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Plantilla de Factura</label>
                        <select name="invoice_template" class="form-control">
                            <option value="default" <?= getConfig('invoice_template') === 'default' ? 'selected' : '' ?>>Plantilla Predeterminada</option>
                            <option value="modern" <?= getConfig('invoice_template') === 'modern' ? 'selected' : '' ?>>Plantilla Moderna</option>
                            <option value="minimal" <?= getConfig('invoice_template') === 'minimal' ? 'selected' : '' ?>>Plantilla Minimalista</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración de Notificaciones -->
        <div class="config-section">
            <h5><i class="fas fa-bell mr-2"></i>Configuración de Notificaciones</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="auto_generate_invoices" class="custom-control-input" id="auto_generate_invoices" <?= getConfig('auto_generate_invoices', 1) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="auto_generate_invoices">Generar facturas automáticamente</label>
                        </div>
                        <div class="help-text">Genera facturas automáticamente al vencer suscripciones</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="send_payment_reminders" class="custom-control-input" id="send_payment_reminders" <?= getConfig('send_payment_reminders', 1) ? 'checked' : '' ?>>
                            <label class="custom-control-label" for="send_payment_reminders">Enviar recordatorios de pago</label>
                        </div>
                        <div class="help-text">Envía emails automáticos de recordatorio</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Días antes del vencimiento</label>
                        <input type="number" name="reminder_days_before" class="form-control" value="<?= getConfig('reminder_days_before', 7) ?>" min="1" max="30">
                        <div class="help-text">Días antes de enviar recordatorio</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Intervalo de recordatorios vencidos (días)</label>
                        <input type="number" name="overdue_reminder_interval" class="form-control" value="<?= getConfig('overdue_reminder_interval', 3) ?>" min="1" max="30">
                        <div class="help-text">Cada cuántos días enviar recordatorio</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Máximo de recordatorios vencidos</label>
                        <input type="number" name="max_overdue_reminders" class="form-control" value="<?= getConfig('max_overdue_reminders', 5) ?>" min="1" max="20">
                        <div class="help-text">Número máximo de recordatorios</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Configuración de Email -->
        <div class="config-section">
            <h5><i class="fas fa-envelope mr-2"></i>Configuración de Email</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Servidor SMTP</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= htmlspecialchars(getConfig('smtp_host', 'smtp.gmail.com')) ?>" placeholder="smtp.gmail.com">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Puerto SMTP</label>
                        <input type="number" name="smtp_port" class="form-control" value="<?= getConfig('smtp_port', 587) ?>" min="1" max="65535">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Encriptación</label>
                        <select name="smtp_encryption" class="form-control">
                            <option value="tls" <?= getConfig('smtp_encryption') === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= getConfig('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= getConfig('smtp_encryption') === 'none' ? 'selected' : '' ?>>Ninguna</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Usuario SMTP</label>
                        <input type="text" name="smtp_username" class="form-control" value="<?= htmlspecialchars(getConfig('smtp_username')) ?>" placeholder="tu-email@gmail.com">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Contraseña SMTP</label>
                        <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars(getConfig('smtp_password')) ?>" placeholder="Tu contraseña de aplicación">
                        <div class="help-text">Usa contraseña de aplicación para Gmail</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plantillas de Email -->
        <div class="config-section">
            <h5><i class="fas fa-edit mr-2"></i>Plantillas de Email</h5>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Encabezado de Email</label>
                        <textarea name="email_template_header" class="form-control" rows="4" placeholder="Contenido del encabezado de los emails..."><?= htmlspecialchars(getConfig('email_template_header', 'Estimado cliente,<br><br>')) ?></textarea>
                        <div class="help-text">HTML permitido. Variables: {company_name}, {customer_name}</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Pie de Email</label>
                        <textarea name="email_template_footer" class="form-control" rows="4" placeholder="Contenido del pie de los emails..."><?= htmlspecialchars(getConfig('email_template_footer', '<br><br>Saludos cordiales,<br>{company_name}')) ?></textarea>
                        <div class="help-text">HTML permitido. Variables: {company_name}, {company_phone}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card config-card">
                    <div class="card-body text-center">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save mr-2"></i>Guardar Configuración
                        </button>
                        <button type="button" class="btn btn-secondary btn-lg ml-3" onclick="testEmail()">
                            <i class="fas fa-paper-plane mr-2"></i>Probar Email
                        </button>
                        <button type="button" class="btn btn-info btn-lg ml-3" onclick="resetToDefaults()">
                            <i class="fas fa-undo mr-2"></i>Restaurar Predeterminados
                        </button>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </form>
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
    
    // Probar configuración de email
    window.testEmail = function() {
        if (confirm('¿Deseas enviar un email de prueba con la configuración actual?')) {
            // Aquí se implementaría la función de prueba de email
            alert('Función de prueba de email en desarrollo');
        }
    }

    // Restaurar configuración predeterminada
    window.resetToDefaults = function() {
        if (confirm('¿Estás seguro de restaurar la configuración predeterminada? Esto sobrescribirá todos los cambios.')) {
            // Aquí se implementaría la restauración de valores predeterminados
            alert('Función de restauración en desarrollo');
        }
    }

    // Validación de formulario
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        const companyName = document.querySelector('input[name="company_name"]').value.trim();
        const companyRut = document.querySelector('input[name="company_rut"]').value.trim();
        
        if (!companyName) {
            e.preventDefault();
            alert('El nombre de la empresa es obligatorio');
            return;
        }
        
        if (!companyRut) {
            e.preventDefault();
            alert('El RUT de la empresa es obligatorio');
            return;
        }
        
        // Validar formato de RUT chileno
        const rutRegex = /^\d{1,2}\.\d{3}\.\d{3}-[\dkK]$/;
        if (!rutRegex.test(companyRut)) {
            e.preventDefault();
            alert('El RUT debe tener el formato XX.XXX.XXX-X');
            return;
        }
    });
});
</script>
</body>
</html> 