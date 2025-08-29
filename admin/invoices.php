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
verificarPermisoVista($_SESSION['id_user'], 36); // admin_panel

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

$success = '';
$error = '';

// Filtros
$status_filter = $_GET['status'] ?? '';
$company_filter = $_GET['company'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Construir WHERE clause
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "inv_status.status_name = ?";
    $params[] = $status_filter;
}

if ($company_filter) {
    $where_conditions[] = "c.company_name LIKE ?";
    $params[] = "%$company_filter%";
}

if ($date_from) {
    $where_conditions[] = "i.invoice_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "i.invoice_date <= ?";
    $params[] = $date_to;
}

$where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Obtener facturas
$sql = "
    SELECT 
        i.*,
        c.company_name,
        c.company_email,
        sp.plan_name,
        inv_status.status_name as invoice_status, inv_status.color as status_color,
        pmt.type_name as payment_method_type
    FROM invoices i
    JOIN companies c ON i.id_company = c.id_company
    JOIN company_subscriptions cs ON i.id_subscription = cs.id_subscription
    JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
    JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status
    JOIN payment_method_types pmt ON i.id_payment_method_type = pmt.id_type
    $where_sql
    ORDER BY i.invoice_date DESC, i.id_invoice DESC
";

$stmt = $connection->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Obtener datos para filtros
$statuses = $connection->query("SELECT status_name FROM invoice_statuses ORDER BY sort_order")->fetchAll();
$companies = $connection->query("SELECT id_company, company_name FROM companies ORDER BY company_name")->fetchAll();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $invoice_id = (int)$_POST['invoice_id'];
        
        if ($_POST['action'] === 'change_status') {
            $new_status = $_POST['new_status'];
            
            // Obtener ID del status
            $stmt = $connection->prepare("SELECT id_status FROM invoice_statuses WHERE status_name = ?");
            $stmt->execute([$new_status]);
            $status_id = $stmt->fetchColumn();
            
            if ($status_id) {
                $stmt = $connection->prepare("UPDATE invoices SET id_invoice_status = ? WHERE id_invoice = ?");
                if ($stmt->execute([$status_id, $invoice_id])) {
                    $success = 'Estado de factura actualizado correctamente.';
                    audit_log('Cambiar estado de factura', 'Factura ID: ' . $invoice_id . ', Nuevo estado: ' . $new_status);
                } else {
                    $error = 'Error al actualizar el estado.';
                }
            }
        } elseif ($_POST['action'] === 'delete') {
            // Verificar que la factura no esté pagada
            $stmt = $connection->prepare("SELECT inv_status.status_name FROM invoices i JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status WHERE i.id_invoice = ?");
            $stmt->execute([$invoice_id]);
            $current_status = $stmt->fetchColumn();
            
            if ($current_status === 'paid') {
                $error = 'No se puede eliminar una factura pagada.';
            } else {
                $stmt = $connection->prepare("DELETE FROM invoices WHERE id_invoice = ?");
                if ($stmt->execute([$invoice_id])) {
                    $success = 'Factura eliminada correctamente.';
                    audit_log('Eliminar factura', 'Factura ID: ' . $invoice_id);
                } else {
                    $error = 'Error al eliminar la factura.';
                }
            }
        }
    }
}

// Generar nueva factura
if (isset($_GET['generate']) && is_numeric($_GET['generate'])) {
    $subscription_id = (int)$_GET['generate'];
    
    // Obtener datos de la suscripción
    $stmt = $connection->prepare("
        SELECT cs.*, c.company_name, c.company_email, sp.plan_name, sp.price, bc.cycle_name
        FROM company_subscriptions cs
        JOIN companies c ON cs.id_company = c.id_company
        JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
        JOIN billing_cycles bc ON sp.id_billing_cycle = bc.id_cycle
        WHERE cs.id_subscription = ?
    ");
    $stmt->execute([$subscription_id]);
    $subscription = $stmt->fetch();
    
    if ($subscription) {
        // Generar número de factura
        $prefix = $connection->query("SELECT config_value FROM billing_config WHERE config_key = 'invoice_prefix'")->fetchColumn() ?: 'FAC';
        $year = date('Y');
        $month = date('m');
        
        $stmt = $connection->prepare("
            SELECT COUNT(*) + 1 as next_number 
            FROM invoices 
            WHERE invoice_number LIKE ?
        ");
        $stmt->execute([$prefix . $year . $month . '%']);
        $next_number = $stmt->fetchColumn();
        
        $invoice_number = $prefix . $year . $month . str_pad($next_number, 4, '0', STR_PAD_LEFT);
        
        // Calcular fechas
        $invoice_date = date('Y-m-d');
        $payment_terms = $connection->query("SELECT config_value FROM billing_config WHERE config_key = 'payment_terms_days'")->fetchColumn() ?: 30;
        $due_date = date('Y-m-d', strtotime("+$payment_terms days"));
        
        // Calcular montos
        $amount = $subscription['price'];
        $tax_rate = $connection->query("SELECT config_value FROM billing_config WHERE config_key = 'tax_rate'")->fetchColumn() ?: 19;
        $tax_amount = $amount * ($tax_rate / 100);
        $total_amount = $amount + $tax_amount;
        
        // Obtener IDs necesarios
        $stmt = $connection->prepare("SELECT id_status FROM invoice_statuses WHERE status_name = 'draft'");
        $stmt->execute();
        $draft_status_id = $stmt->fetchColumn();
        
        $stmt = $connection->prepare("SELECT id_type FROM payment_method_types WHERE type_name = 'manual'");
        $stmt->execute();
        $manual_payment_id = $stmt->fetchColumn();
        
        // Insertar factura
        $stmt = $connection->prepare("
            INSERT INTO invoices (invoice_number, id_company, id_subscription, id_invoice_status, invoice_date, due_date, amount, tax_amount, total_amount, id_payment_method_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$invoice_number, $subscription['id_company'], $subscription_id, $draft_status_id, $invoice_date, $due_date, $amount, $tax_amount, $total_amount, $manual_payment_id])) {
            $invoice_id = $connection->lastInsertId();
            
            // Insertar item de factura
            $stmt = $connection->prepare("
                INSERT INTO invoice_items (id_invoice, item_description, quantity, unit_price, total_price, tax_rate)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$invoice_id, $subscription['plan_name'] . ' - ' . $subscription['cycle_name'], 1, $amount, $amount, $tax_rate]);
            
            $success = 'Factura generada correctamente: ' . $invoice_number;
            audit_log('Generar factura', 'Factura: ' . $invoice_number . ', Empresa: ' . $subscription['company_name']);
        } else {
            $error = 'Error al generar la factura.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Gestión de Facturas</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/invoices.php usando CSS variables */
    .admin-invoices-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-invoices-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-invoices-title i {
        color: var(--primary-color);
    }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
    }
    
    .invoice-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        box-shadow: 0 2px 4px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .invoice-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px var(--shadow-medium);
    }
    
    .filter-section {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px var(--shadow-light);
    }
    
    .filter-section h5 {
        color: var(--text-primary);
        margin-bottom: 15px;
    }
    
    .filter-section label {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 5px;
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
    
    .modal-content {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
    }
    
    .modal-header {
        background-color: var(--bg-secondary);
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }
    
    .modal-body {
        background-color: var(--bg-card);
        color: var(--text-primary);
    }
    
    .modal-footer {
        background-color: var(--bg-secondary);
        border-top: 1px solid var(--border-color);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-invoices-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-invoices-title {
            font-size: 1.5rem;
        }
        
        .filter-section {
            padding: 15px;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-invoices-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="admin-invoices-title"><i class="fas fa-file-invoice mr-2"></i>Gestión de Facturas</h2>
                <div>
                    <a href="subscriptions.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Volver
                    </a>
                    <a href="billing_reports.php" class="btn btn-info ml-2">
                        <i class="fas fa-chart-bar mr-1"></i>Reportes
                    </a>
                </div>
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

    <!-- Filtros -->
    <div class="filter-section">
        <h5><i class="fas fa-filter mr-2"></i>Filtros</h5>
        <form method="get" class="row">
            <div class="col-md-3">
                <label>Estado</label>
                <select name="status" class="form-control">
                    <option value="">Todos los estados</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= $status['status_name'] ?>" <?= $status_filter === $status['status_name'] ? 'selected' : '' ?>>
                            <?= ucfirst($status['status_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Empresa</label>
                <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($company_filter) ?>" placeholder="Buscar empresa...">
            </div>
            <div class="col-md-2">
                <label>Desde</label>
                <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            </div>
            <div class="col-md-2">
                <label>Hasta</label>
                <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i>Filtrar
                    </button>
                    <a href="invoices.php" class="btn btn-outline-secondary ml-1">
                        <i class="fas fa-times mr-1"></i>Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-primary"><?= count($invoices) ?></h5>
                    <p class="card-text">Total Facturas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        $<?= number_format(array_sum(array_column($invoices, 'total_amount')), 0, ',', '.') ?>
                    </h5>
                    <p class="card-text">Total Facturado</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-warning">
                        <?= count(array_filter($invoices, function($i) { return $i['invoice_status'] === 'sent'; })) ?>
                    </h5>
                    <p class="card-text">Pendientes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title text-danger">
                        <?= count(array_filter($invoices, function($i) { return $i['invoice_status'] === 'overdue'; })) ?>
                    </h5>
                    <p class="card-text">Vencidas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Facturas -->
    <div class="row">
        <?php foreach ($invoices as $invoice): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card invoice-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?= htmlspecialchars($invoice['invoice_number']) ?></h6>
                        <span class="badge status-badge" style="background-color: <?= $invoice['status_color'] ?>; color: white;">
                            <?= ucfirst($invoice['invoice_status']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title"><?= htmlspecialchars($invoice['company_name']) ?></h6>
                        <p class="card-text">
                            <small class="text-muted">
                                <i class="fas fa-calendar mr-1"></i>
                                <?= date('d/m/Y', strtotime($invoice['invoice_date'])) ?>
                            </small>
                        </p>
                        <p class="card-text">
                            <strong>Plan:</strong> <?= htmlspecialchars($invoice['plan_name']) ?><br>
                            <strong>Vence:</strong> <?= date('d/m/Y', strtotime($invoice['due_date'])) ?><br>
                            <strong>Método:</strong> <?= ucfirst(str_replace('_', ' ', $invoice['payment_method_type'])) ?>
                        </p>
                        <div class="text-right">
                            <h5 class="text-primary">$<?= number_format($invoice['total_amount'], 0, ',', '.') ?></h5>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#viewInvoiceModal" data-invoice='<?= json_encode($invoice) ?>'>
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#editStatusModal" data-invoice-id="<?= $invoice['id_invoice'] ?>" data-current-status="<?= $invoice['invoice_status'] ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($invoice['invoice_status'] !== 'paid'): ?>
                                <button type="button" class="btn btn-outline-danger" onclick="deleteInvoice(<?= $invoice['id_invoice'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($invoices)): ?>
        <div class="text-center py-5">
            <i class="fas fa-file-invoice fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No se encontraron facturas</h5>
            <p class="text-muted">Intenta ajustar los filtros de búsqueda</p>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Ver Factura -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Factura</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="invoiceDetails">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="downloadPDF()">
                    <i class="fas fa-download mr-1"></i>Descargar PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Estado -->
<div class="modal fade" id="editStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cambiar Estado de Factura</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" name="invoice_id" id="editInvoiceId">
                    <div class="form-group">
                        <label>Nuevo Estado</label>
                        <select name="new_status" class="form-control" required>
                            <?php foreach ($statuses as $status): ?>
                                <option value="<?= $status['status_name'] ?>"><?= ucfirst($status['status_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Actualizar Estado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Formulario para eliminar -->
<form id="deleteForm" method="post" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="invoice_id" id="deleteInvoiceId">
</form>



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
    
    // Ver detalles de factura
    $('#viewInvoiceModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var invoice = button.data('invoice');
    var modal = $(this);
    
    var html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Información de Factura</h6>
                <p><strong>Número:</strong> ${invoice.invoice_number}</p>
                <p><strong>Fecha:</strong> ${new Date(invoice.invoice_date).toLocaleDateString()}</p>
                <p><strong>Vence:</strong> ${new Date(invoice.due_date).toLocaleDateString()}</p>
                <p><strong>Estado:</strong> <span class="badge" style="background-color: ${invoice.status_color}">${invoice.invoice_status}</span></p>
            </div>
            <div class="col-md-6">
                <h6>Información de Cliente</h6>
                <p><strong>Empresa:</strong> ${invoice.company_name}</p>
                <p><strong>Email:</strong> ${invoice.company_email}</p>
                <p><strong>Plan:</strong> ${invoice.plan_name}</p>
                <p><strong>Método de Pago:</strong> ${invoice.payment_method_type}</p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <h6>Detalles de Pago</h6>
                <table class="table table-sm">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-right">$${parseFloat(invoice.amount).toLocaleString()}</td>
                    </tr>
                    <tr>
                        <td>IVA:</td>
                        <td class="text-right">$${parseFloat(invoice.tax_amount).toLocaleString()}</td>
                    </tr>
                    <tr class="table-primary">
                        <td><strong>Total:</strong></td>
                        <td class="text-right"><strong>$${parseFloat(invoice.total_amount).toLocaleString()}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    `;
    
    modal.find('#invoiceDetails').html(html);
});

// Editar estado
$('#editStatusModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var invoiceId = button.data('invoice-id');
    var currentStatus = button.data('current-status');
    var modal = $(this);
    
    modal.find('#editInvoiceId').val(invoiceId);
        modal.find('select[name="new_status"]').val(currentStatus);
    });
});

// Eliminar factura
function deleteInvoice(invoiceId) {
    if (confirm('¿Estás seguro de eliminar esta factura? Esta acción no se puede deshacer.')) {
        document.getElementById('deleteInvoiceId').value = invoiceId;
        document.getElementById('deleteForm').submit();
    }
}

// Descargar PDF
function downloadPDF() {
    // Implementar generación de PDF
    alert('Función de descarga de PDF en desarrollo');
}
</script>
</body>
</html> 