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
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../security/check_access.php';
verificarPermisoVista($_SESSION['id_user'], 34); // admin_panel

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

// Métricas principales
$total_companies = $connection->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$active_subscriptions = $connection->query("SELECT COUNT(*) FROM company_subscriptions cs 
             JOIN subscription_statuses ss ON cs.id_subscription_status = ss.id_status 
             WHERE ss.status_name = 'active'")->fetchColumn();
$pending_payments = $connection->query("SELECT COUNT(*) FROM invoices i 
             JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status 
             WHERE inv_status.status_name IN ('sent', 'overdue')")->fetchColumn();
$total_revenue = $connection->query("SELECT COALESCE(SUM(total_amount), 0) FROM invoices i 
             JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status 
             WHERE inv_status.status_name = 'paid'")->fetchColumn();

// Suscripciones que vencen pronto (próximos 30 días)
$expiring_soon = $connection->query("
    SELECT cs.*, c.company_name, c.company_email, sp.plan_name, sp.price
    FROM company_subscriptions cs
    JOIN companies c ON cs.id_company = c.id_company
    JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
    JOIN subscription_statuses ss ON cs.id_subscription_status = ss.id_status
    WHERE ss.status_name = 'active' 
    AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY cs.end_date ASC
    LIMIT 10
")->fetchAll();

// Facturas vencidas
$overdue_invoices = $connection->query("
    SELECT i.*, c.company_name, c.company_email, sp.plan_name
    FROM invoices i
    JOIN companies c ON i.id_company = c.id_company
    JOIN company_subscriptions cs ON i.id_subscription = cs.id_subscription
    JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
    JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status
    WHERE inv_status.status_name = 'overdue'
    ORDER BY i.due_date ASC
    LIMIT 10
")->fetchAll();

// Últimas transacciones
$recent_transactions = $connection->query("
    SELECT pt.*, i.invoice_number, c.company_name, pm.method_name
    FROM payment_transactions pt
    JOIN invoices i ON pt.id_invoice = i.id_invoice
    JOIN companies c ON i.id_company = c.id_company
    JOIN payment_methods pm ON pt.id_payment_method = pm.id_method
    ORDER BY pt.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Administración de Suscripciones</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/subscriptions.php usando CSS variables */
    .admin-subscriptions-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-subscriptions-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-subscriptions-title i {
        color: var(--primary-color);
    }
    
    .metric-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        box-shadow: 0 4px 6px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
    }
    
    .metric-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px var(--shadow-medium);
    }
    
    .metric-card .card-body {
        background-color: var(--bg-card);
        color: var(--text-primary);
    }
    
    .metric-card .card-title {
        color: var(--text-primary);
    }
    
    .metric-card .card-text {
        color: var(--text-secondary);
    }
    
    .metric-icon {
        font-size: 2.5rem;
        opacity: 0.8;
    }
    
    .status-badge {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .alert-expiring {
        background-color: var(--warning-color-alpha);
        border-left: 4px solid var(--warning-color);
        color: var(--text-primary);
    }
    
    .alert-overdue {
        background-color: var(--danger-color-alpha);
        border-left: 4px solid var(--danger-color);
        color: var(--text-primary);
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
    
    .list-group-item {
        background-color: var(--bg-card);
        border-color: var(--border-color);
        color: var(--text-primary);
    }
    
    .list-group-item:hover {
        background-color: var(--bg-secondary);
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
    
    .text-muted {
        color: var(--text-muted) !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-subscriptions-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-subscriptions-title {
            font-size: 1.5rem;
        }
        
        .metric-card {
            margin-bottom: 15px;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-subscriptions-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="admin-subscriptions-title"><i class="fas fa-credit-card mr-2"></i>Administración de Suscripciones</h2>
                <div>
                    <a href="subscription_plans.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i>Gestionar Planes
                    </a>
                    <a href="invoices.php" class="btn btn-success ml-2">
                        <i class="fas fa-file-invoice mr-1"></i>Facturas
                    </a>
                    <a href="payment_methods.php" class="btn btn-info ml-2">
                        <i class="fas fa-credit-card mr-1"></i>Métodos de Pago
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Métricas principales -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-building metric-icon text-primary mb-2"></i>
                    <h3 class="card-title text-primary"><?= number_format($total_companies) ?></h3>
                    <p class="card-text">Empresas Registradas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle metric-icon text-success mb-2"></i>
                    <h3 class="card-title text-success"><?= number_format($active_subscriptions) ?></h3>
                    <p class="card-text">Suscripciones Activas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-clock metric-icon text-warning mb-2"></i>
                    <h3 class="card-title text-warning"><?= number_format($pending_payments) ?></h3>
                    <p class="card-text">Pagos Pendientes</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign metric-icon text-info mb-2"></i>
                    <h3 class="card-title text-info">$<?= number_format($total_revenue, 0, ',', '.') ?></h3>
                    <p class="card-text">Ingresos Totales</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Suscripciones que vencen pronto -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Suscripciones que Vencen Pronto</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($expiring_soon)): ?>
                        <p class="text-muted">No hay suscripciones que venzan en los próximos 30 días.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($expiring_soon as $subscription): ?>
                                <div class="list-group-item alert-expiring">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($subscription['company_name']) ?></h6>
                                            <small class="text-muted">
                                                Plan: <?= htmlspecialchars($subscription['plan_name']) ?> - 
                                                Vence: <?= date('d/m/Y', strtotime($subscription['end_date'])) ?>
                                            </small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-warning status-badge">
                                                <?= date('d', strtotime($subscription['end_date']) - time()) ?> días
                                            </span>
                                            <br>
                                            <small class="text-muted">$<?= number_format($subscription['price'], 0, ',', '.') ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Facturas vencidas -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-times-circle mr-2"></i>Facturas Vencidas</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($overdue_invoices)): ?>
                        <p class="text-muted">No hay facturas vencidas.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($overdue_invoices as $invoice): ?>
                                <div class="list-group-item alert-overdue">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($invoice['company_name']) ?></h6>
                                            <small class="text-muted">
                                                Factura: <?= htmlspecialchars($invoice['invoice_number']) ?> - 
                                                Vencida: <?= date('d/m/Y', strtotime($invoice['due_date'])) ?>
                                            </small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-danger status-badge">Vencida</span>
                                            <br>
                                            <small class="text-muted">$<?= number_format($invoice['total_amount'], 0, ',', '.') ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas transacciones -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history mr-2"></i>Últimas Transacciones</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_transactions)): ?>
                        <p class="text-muted">No hay transacciones recientes.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Factura</th>
                                        <th>Método</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_transactions as $transaction): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($transaction['company_name']) ?></td>
                                            <td><?= htmlspecialchars($transaction['invoice_number']) ?></td>
                                            <td><?= htmlspecialchars($transaction['method_name']) ?></td>
                                            <td>$<?= number_format($transaction['amount'], 0, ',', '.') ?></td>
                                            <td>
                                                <span class="badge badge-<?= $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                    <?= ucfirst($transaction['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($transaction['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
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
</body>
</html> 