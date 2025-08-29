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
verificarPermisoVista($_SESSION['id_user'], 38); // admin_panel

require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

// Filtros de fecha
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // Primer día del mes actual
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Último día del mes actual
$report_type = $_GET['report_type'] ?? 'overview';

// Obtener métricas generales
$metrics = $connection->query("SELECT * FROM v_billing_metrics")->fetch();

// Ingresos por mes (últimos 12 meses)
$monthly_revenue = $connection->query("
    SELECT 
        DATE_FORMAT(i.invoice_date, '%Y-%m') as month,
        SUM(i.total_amount) as revenue,
        COUNT(i.id_invoice) as invoice_count
    FROM invoices i
    JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status
    WHERE inv_status.status_name = 'paid'
    AND i.invoice_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(i.invoice_date, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();

// Facturas por estado
$invoices_by_status = $connection->query("
    SELECT 
        inv_status.status_name, inv_status.color,
        COUNT(i.id_invoice) as count,
        SUM(i.total_amount) as total_amount
    FROM invoices i
    JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status
    WHERE i.invoice_date BETWEEN ? AND ?
    GROUP BY inv_status.id_status, inv_status.status_name, inv_status.color
    ORDER BY count DESC
")->execute([$date_from, $date_to])->fetchAll();

// Empresas por plan
$companies_by_plan = $connection->query("
    SELECT 
        sp.plan_name,
        COUNT(cs.id_subscription) as company_count,
        SUM(sp.price) as total_value
    FROM company_subscriptions cs
    JOIN subscription_plans sp ON cs.id_plan = sp.id_plan
    JOIN subscription_statuses ss ON cs.id_subscription_status = ss.id_status
    WHERE ss.status_name = 'active'
    GROUP BY sp.id_plan, sp.plan_name
    ORDER BY company_count DESC
")->fetchAll();

// Métodos de pago más usados
$payment_methods_usage = $connection->query("
    SELECT 
        pmt.type_name,
        COUNT(pt.id_transaction) as usage_count,
        SUM(pt.amount) as total_amount
    FROM payment_transactions pt
    JOIN payment_methods pm ON pt.id_payment_method = pm.id_method
    JOIN payment_method_types pmt ON pm.id_payment_method_type = pmt.id_type
    JOIN transaction_statuses ts ON pt.id_transaction_status = ts.id_status
    WHERE ts.status_name = 'completed'
    AND pt.created_at BETWEEN ? AND ?
    GROUP BY pmt.id_type, pmt.type_name
    ORDER BY usage_count DESC
")->execute([$date_from, $date_to])->fetchAll();

// Facturas vencidas
$overdue_invoices = $connection->query("
    SELECT 
        i.invoice_number,
        c.company_name,
        i.due_date,
        i.total_amount,
        DATEDIFF(CURDATE(), i.due_date) as days_overdue
    FROM invoices i
    JOIN companies c ON i.id_company = c.id_company
    JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status
    WHERE inv_status.status_name = 'overdue'
    ORDER BY days_overdue DESC
    LIMIT 10
")->fetchAll();

// Top empresas por facturación
$top_companies = $connection->query("
    SELECT 
        c.company_name,
        COUNT(i.id_invoice) as invoice_count,
        SUM(i.total_amount) as total_billed,
        AVG(i.total_amount) as avg_invoice
    FROM invoices i
    JOIN companies c ON i.id_company = c.id_company
    JOIN invoice_statuses inv_status ON i.id_invoice_status = inv_status.id_status
    WHERE inv_status.status_name = 'paid'
    AND i.invoice_date BETWEEN ? AND ?
    GROUP BY c.id_company, c.company_name
    ORDER BY total_billed DESC
    LIMIT 10
")->execute([$date_from, $date_to])->fetchAll();
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Reportes de Facturación</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Theme Switcher JS -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    /* Estilos específicos para admin/billing_reports.php usando CSS variables */
    .admin-billing-reports-container {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 30px;
        margin: 0 auto;
        box-shadow: 0 4px 16px var(--shadow-light);
        transition: all var(--transition-speed) var(--transition-ease);
        max-width: 1400px;
    }
    
    .admin-billing-reports-title {
        color: var(--text-primary);
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .admin-billing-reports-title i {
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
    
    .chart-container {
        position: relative;
        height: 300px;
        margin: 20px 0;
        background-color: var(--bg-card);
        border-radius: 10px;
        padding: 20px;
    }
    
    .report-section {
        background-color: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 2px 8px var(--shadow-light);
    }
    
    .report-section h5 {
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
    
    .table-responsive {
        border-radius: 10px;
        overflow: hidden;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-billing-reports-container {
            padding: 20px;
            margin: 10px;
        }
        
        .admin-billing-reports-title {
            font-size: 1.5rem;
        }
        
        .report-section {
            padding: 20px;
        }
    }
    </style>
</head>
<body class="bg">
<?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>
<div style="height:80px;"></div>

<div class="container mt-4">
    <div class="admin-billing-reports-container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2 class="admin-billing-reports-title"><i class="fas fa-chart-bar mr-2"></i>Reportes de Facturación</h2>
                <div>
                    <button class="btn btn-success" onclick="exportReport()">
                        <i class="fas fa-download mr-1"></i>Exportar
                    </button>
                    <a href="subscriptions.php" class="btn btn-secondary ml-2">
                        <i class="fas fa-arrow-left mr-1"></i>Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="report-section">
        <h5><i class="fas fa-filter mr-2"></i>Filtros de Reporte</h5>
        <form method="get" class="row">
            <div class="col-md-3">
                <label>Desde</label>
                <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            </div>
            <div class="col-md-3">
                <label>Hasta</label>
                <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            </div>
            <div class="col-md-3">
                <label>Tipo de Reporte</label>
                <select name="report_type" class="form-control">
                    <option value="overview" <?= $report_type === 'overview' ? 'selected' : '' ?>>Vista General</option>
                    <option value="revenue" <?= $report_type === 'revenue' ? 'selected' : '' ?>>Ingresos</option>
                    <option value="companies" <?= $report_type === 'companies' ? 'selected' : '' ?>>Empresas</option>
                    <option value="invoices" <?= $report_type === 'invoices' ? 'selected' : '' ?>>Facturas</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i>Generar Reporte
                    </button>
                    <a href="billing_reports.php" class="btn btn-outline-secondary ml-1">
                        <i class="fas fa-times mr-1"></i>Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Métricas Principales -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-primary">
                <div class="card-body text-center">
                    <i class="fas fa-building fa-2x text-primary mb-2"></i>
                    <h3 class="card-title text-primary"><?= number_format($metrics['total_companies']) ?></h3>
                    <p class="card-text">Empresas Registradas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-success">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                    <h3 class="card-title text-success"><?= number_format($metrics['active_subscriptions']) ?></h3>
                    <p class="card-text">Suscripciones Activas</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-info">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x text-info mb-2"></i>
                    <h3 class="card-title text-info">$<?= number_format($metrics['total_revenue'], 0, ',', '.') ?></h3>
                    <p class="card-text">Ingresos Totales</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card metric-card border-warning">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                    <h3 class="card-title text-warning"><?= number_format($metrics['overdue_subscriptions']) ?></h3>
                    <p class="card-text">Suscripciones Vencidas</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <!-- Ingresos Mensuales -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line mr-2"></i>Ingresos Mensuales</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facturas por Estado -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie mr-2"></i>Facturas por Estado</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tablas de Datos -->
    <div class="row">
        <!-- Top Empresas -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-trophy mr-2"></i>Top Empresas por Facturación</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Empresa</th>
                                    <th>Facturas</th>
                                    <th>Total Facturado</th>
                                    <th>Promedio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_companies as $company): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($company['company_name']) ?></td>
                                        <td><?= $company['invoice_count'] ?></td>
                                        <td>$<?= number_format($company['total_billed'], 0, ',', '.') ?></td>
                                        <td>$<?= number_format($company['avg_invoice'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facturas Vencidas -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle mr-2"></i>Facturas Vencidas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Factura</th>
                                    <th>Empresa</th>
                                    <th>Días Vencida</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdue_invoices as $invoice): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($invoice['invoice_number']) ?></td>
                                        <td><?= htmlspecialchars($invoice['company_name']) ?></td>
                                        <td>
                                            <span class="badge badge-danger"><?= $invoice['days_overdue'] ?> días</span>
                                        </td>
                                        <td>$<?= number_format($invoice['total_amount'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Métodos de Pago -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card mr-2"></i>Uso de Métodos de Pago</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="paymentMethodsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empresas por Plan -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-layer-group mr-2"></i>Empresas por Plan</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="plansChart"></canvas>
                    </div>
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
    
    // Datos para los gráficos
    const monthlyData = <?= json_encode($monthly_revenue) ?>;
    const statusData = <?= json_encode($invoices_by_status) ?>;
    const paymentMethodsData = <?= json_encode($payment_methods_usage) ?>;
    const plansData = <?= json_encode($companies_by_plan) ?>;

    // Gráfico de Ingresos Mensuales
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(item => {
                const [year, month] = item.month.split('-');
                return new Date(year, month - 1).toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
            }).reverse(),
            datasets: [{
                label: 'Ingresos ($)',
                data: monthlyData.map(item => item.revenue).reverse(),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Gráfico de Facturas por Estado
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(item => item.status_name.charAt(0).toUpperCase() + item.status_name.slice(1)),
            datasets: [{
                data: statusData.map(item => item.count),
                backgroundColor: statusData.map(item => item.color),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Gráfico de Métodos de Pago
    const paymentCtx = document.getElementById('paymentMethodsChart').getContext('2d');
    new Chart(paymentCtx, {
        type: 'bar',
        data: {
            labels: paymentMethodsData.map(item => item.type_name.replace('_', ' ').toUpperCase()),
            datasets: [{
                label: 'Transacciones',
                data: paymentMethodsData.map(item => item.usage_count),
                backgroundColor: 'rgba(54, 162, 235, 0.8)',
                borderColor: 'rgb(54, 162, 235)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Empresas por Plan
    const plansCtx = document.getElementById('plansChart').getContext('2d');
    new Chart(plansCtx, {
        type: 'bar',
        data: {
            labels: plansData.map(item => item.plan_name),
            datasets: [{
                label: 'Empresas',
                data: plansData.map(item => item.company_count),
                backgroundColor: 'rgba(255, 99, 132, 0.8)',
                borderColor: 'rgb(255, 99, 132)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

// Función para exportar reporte
function exportReport() {
    // Implementar exportación a PDF o Excel
    alert('Función de exportación en desarrollo');
}
</script>
</body>
</html> 