<?php
session_start();

// Habilitar slidepanel para admin dashboard
$_SESSION['enable_slidepanel'] = 1;

// Verificar autenticación y permisos
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

// Verificar que sea superadmin o admin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    // Verificar si es admin
    if (!isset($_SESSION['id_rol']) || $_SESSION['id_rol'] != 2) {
        header('Location: ../login.php?error=access_denied');
        exit();
    }
}

require_once __DIR__ . '/../db/functions.php';
require_once __DIR__ . '/../theme_handler.php';

$database = new Database();
$connection = $database->connection();

// Obtener estadísticas del sistema
$stats = [];

// Estadísticas básicas
$stats['empresas'] = $connection->query("SELECT COUNT(*) FROM companies")->fetchColumn();
$stats['usuarios'] = $connection->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['superadmins'] = $connection->query("SELECT COUNT(DISTINCT id_user) FROM user_roles WHERE id_role = 1")->fetchColumn();
$stats['admins'] = $connection->query("SELECT COUNT(DISTINCT id_user) FROM user_roles WHERE id_role = 2")->fetchColumn();
$stats['servicios'] = $connection->query("SELECT COUNT(*) FROM services")->fetchColumn();
$stats['tickets'] = $connection->query("SELECT COUNT(*) FROM tickets")->fetchColumn();

// Verificar si la tabla events existe antes de consultarla
try {
    $stats['eventos'] = $connection->query("SELECT COUNT(*) FROM events")->fetchColumn();
} catch (Exception $e) {
    $stats['eventos'] = 0; // Si no existe la tabla, establecer en 0
}

$stats['logs'] = $connection->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

// Estadísticas de actividad reciente
try {
    $stats['usuarios_activos_hoy'] = $connection->query("SELECT COUNT(DISTINCT id_user) FROM audit_logs")->fetchColumn();
} catch (Exception $e) {
    $stats['usuarios_activos_hoy'] = 0;
}

try {
    $stats['logs_hoy'] = $connection->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
} catch (Exception $e) {
    $stats['logs_hoy'] = 0;
}

// Últimos logs de auditoría
try {
    $stmt = $connection->prepare("
        SELECT l.*, u.user, u.email 
        FROM audit_logs l 
        LEFT JOIN users u ON l.id_user = u.id_user 
        ORDER BY l.id_log DESC 
        LIMIT 15
    ");
    $stmt->execute();
    $logs_recientes = $stmt->fetchAll();
} catch (Exception $e) {
    // Si hay error con el JOIN, obtener solo logs
    try {
        $stmt = $connection->prepare("
            SELECT * FROM audit_logs 
            ORDER BY id_log DESC 
            LIMIT 15
        ");
        $stmt->execute();
        $logs_recientes = $stmt->fetchAll();
    } catch (Exception $e2) {
        // Si también falla, crear array vacío
        $logs_recientes = [];
    }
    // Agregar campos vacíos a cada log
    foreach ($logs_recientes as &$log) {
        $log['user'] = 'Sistema';
        $log['email'] = '';
    }
}

// Últimas empresas creadas
try {
    $stmt = $connection->prepare("
        SELECT * FROM companies 
        ORDER BY id_company DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $empresas_recientes = $stmt->fetchAll();
} catch (Exception $e) {
    // Si hay error, crear array vacío
    $empresas_recientes = [];
}

// Últimos usuarios registrados
try {
    $stmt = $connection->prepare("
        SELECT u.*, c.name as company_name 
        FROM users u 
        LEFT JOIN companies c ON u.id_company = c.id_company 
        ORDER BY u.id_user DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $usuarios_recientes = $stmt->fetchAll();
} catch (Exception $e) {
    // Si hay error con el JOIN, obtener solo usuarios
    try {
        $stmt = $connection->prepare("
            SELECT * FROM users 
            ORDER BY id_user DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $usuarios_recientes = $stmt->fetchAll();
    } catch (Exception $e2) {
        // Si también falla, crear array vacío
        $usuarios_recientes = [];
    }
    // Agregar company_name vacío a cada usuario
    foreach ($usuarios_recientes as &$usuario) {
        $usuario['company_name'] = 'Sin empresa';
    }
}

// Servicios más populares
try {
    $stmt = $connection->prepare("
        SELECT s.*, COUNT(t.id_ticket) as ticket_count 
        FROM services s 
        LEFT JOIN tickets t ON s.id_service = t.id_service 
        GROUP BY s.id_service 
        ORDER BY ticket_count DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $servicios_populares = $stmt->fetchAll();
} catch (Exception $e) {
    // Si hay error, obtener solo servicios sin contar tickets
    $stmt = $connection->prepare("SELECT * FROM services LIMIT 5");
    $stmt->execute();
    $servicios_populares = $stmt->fetchAll();
    // Agregar ticket_count = 0 a cada servicio
    foreach ($servicios_populares as &$servicio) {
        $servicio['ticket_count'] = 0;
    }
}

// Obtener información del usuario actual
$user_id = $_SESSION['id_user'];
$username = $_SESSION['user'] ?? 'Admin';
$is_superadmin = $_SESSION['is_superadmin'] ?? false;
?>
<!DOCTYPE html>
<html lang="es" <?php echo applyThemeToHTML(); ?>>
<head>
    <title>Dashboard Admin - Pin9</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Dashboard de administración de Pin9">
    
    <!-- CSS -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Theme Switcher -->
    <script src="../js/theme-switcher.js"></script>
    
    <style>
    :root {
        --primary-color: #007bff;
        --success-color: #28a745;
        --warning-color: #ffc107;
        --danger-color: #dc3545;
        --info-color: #17a2b8;
        --dark-color: #343a40;
        --light-color: #f8f9fa;
        --transition-speed: 0.3s;
        --transition-ease: ease;
    }

    .admin-dashboard {
        background: var(--bg-primary);
        min-height: 100vh;
        padding: 15px 0;
    }

    .dashboard-header {
        background: var(--bg-card);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px var(--shadow-light);
        border: 1px solid var(--border-color);
    }

    .dashboard-title {
        color: var(--text-primary);
        font-weight: 700;
        margin-bottom: 5px;
        font-size: 1.5rem;
    }

    .dashboard-subtitle {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: var(--bg-card);
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 4px 15px var(--shadow-light);
        border: 1px solid var(--border-color);
        transition: all var(--transition-speed) var(--transition-ease);
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--success-color));
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px var(--shadow-medium);
    }

    .stat-icon {
        font-size: 1.8rem;
        margin-bottom: 8px;
        opacity: 0.8;
    }

    .stat-number {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 3px;
        color: var(--text-primary);
    }

    .stat-label {
        color: var(--text-muted);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .content-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .main-content {
        background: var(--bg-card);
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 4px 15px var(--shadow-light);
        border: 1px solid var(--border-color);
    }

    .sidebar-content {
        background: var(--bg-card);
        border-radius: 10px;
        padding: 15px;
        box-shadow: 0 4px 15px var(--shadow-light);
        border: 1px solid var(--border-color);
        height: fit-content;
    }

    .section-title {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 1.1rem;
    }

    .section-title i {
        color: var(--primary-color);
    }

    .table-custom {
        background: var(--bg-card);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px var(--shadow-light);
    }

    .table-custom th {
        background: var(--bg-secondary);
        color: var(--text-primary);
        border: none;
        padding: 10px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .table-custom td {
        border: none;
        padding: 8px 10px;
        color: var(--text-primary);
        border-bottom: 1px solid var(--border-color);
        font-size: 0.85rem;
    }

    .table-custom tr:hover {
        background: var(--bg-hover);
    }

    .badge-custom {
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .badge-primary { background: var(--primary-color); color: white; }
    .badge-success { background: var(--success-color); color: white; }
    .badge-warning { background: var(--warning-color); color: white; }
    .badge-danger { background: var(--danger-color); color: white; }
    .badge-info { background: var(--info-color); color: white; }

    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 10px;
        margin-bottom: 20px;
    }

    .action-btn {
        background: var(--bg-secondary);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        text-decoration: none;
        color: var(--text-primary);
        transition: all var(--transition-speed) var(--transition-ease);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
        font-size: 0.8rem;
    }

    .action-btn:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
        text-decoration: none;
    }

    .action-btn i {
        font-size: 1.2rem;
    }

    .user-info {
        background: var(--bg-secondary);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        text-align: center;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin: 0 auto 10px;
    }

    .user-name {
        color: var(--text-primary);
        font-weight: 600;
        margin-bottom: 5px;
    }

    .user-role {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .activity-item:last-child {
        border-bottom: none;
    }

    .activity-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        color: white;
    }

    .activity-content {
        flex: 1;
    }

    .activity-title {
        color: var(--text-primary);
        font-weight: 500;
        font-size: 0.8rem;
        margin-bottom: 2px;
    }

    .activity-time {
        color: var(--text-muted);
        font-size: 0.7rem;
    }

    .responsive-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
    }

    .responsive-grid .main-content {
        padding: 15px;
    }

    .responsive-grid .section-title {
        font-size: 1rem;
        margin-bottom: 10px;
    }

    .responsive-grid .stat-number {
        font-size: 1.4rem;
        margin-bottom: 2px;
    }

    .responsive-grid small {
        font-size: 0.7rem;
    }

    @media (max-width: 768px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        .dashboard-header {
            padding: 20px;
        }
        
        .main-content, .sidebar-content {
            padding: 20px;
        }
    }

    /* Animaciones */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-card, .main-content, .sidebar-content {
        animation: fadeInUp 0.6s ease-out;
    }
    </style>
</head>
<body class="admin-dashboard">
    <?php require_once __DIR__ . '/../views/partials/modern_navbar.php'; ?>

    <div class="container-fluid">
        <!-- Header del Dashboard -->
        <div class="dashboard-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="dashboard-title">
                        <i class="fas fa-chart-line mr-3"></i>
                        Dashboard de Administración
                    </h1>
                    <p class="dashboard-subtitle">
                        Bienvenido, <?php echo htmlspecialchars($username); ?> 
                        <?php if ($is_superadmin): ?>
                            <span class="badge badge-warning">Superadmin</span>
                        <?php else: ?>
                            <span class="badge badge-info">Admin</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <div class="d-flex justify-content-end align-items-center">
                        <div class="mr-3">
                            <small class="text-muted">Última actividad</small><br>
                            <span class="text-primary"><?php echo date('d/m/Y H:i'); ?></span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                <i class="fas fa-cog"></i> Acciones
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="companies.php">
                                    <i class="fas fa-building"></i> Gestionar Empresas
                                </a>
                                <a class="dropdown-item" href="users.php">
                                    <i class="fas fa-users"></i> Gestionar Usuarios
                                </a>
                                <a class="dropdown-item" href="services.php">
                                    <i class="fas fa-cogs"></i> Gestionar Servicios
                                </a>
                                <a class="dropdown-item" href="audit_logs.php">
                                    <i class="fas fa-clipboard-list"></i> Ver Logs
                                </a>
                                <?php if ($is_superadmin): ?>
                                <a class="dropdown-item" href="content_editor.php">
                                    <i class="fas fa-edit"></i> Editor de Contenido
                                </a>
                                <a class="dropdown-item" href="menu_creator.php">
                                    <i class="fas fa-bars"></i> Creador de Menús
                                </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../Modules/BOTWhatsapp/manage.php">
                                    <i class="fab fa-whatsapp"></i> BOT WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Principales -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-number text-primary"><?php echo number_format($stats['empresas']); ?></div>
                <div class="stat-label">Empresas</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-success">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number text-success"><?php echo number_format($stats['usuarios']); ?></div>
                <div class="stat-label">Usuarios</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-warning">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-number text-warning"><?php echo number_format($stats['superadmins']); ?></div>
                <div class="stat-label">Superadmins</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-info">
                    <i class="fas fa-cogs"></i>
                </div>
                <div class="stat-number text-info"><?php echo number_format($stats['servicios']); ?></div>
                <div class="stat-label">Servicios</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-danger">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-number text-danger"><?php echo number_format($stats['tickets']); ?></div>
                <div class="stat-label">Tickets</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon text-primary">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number text-primary"><?php echo number_format($stats['eventos']); ?></div>
                <div class="stat-label">Eventos</div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="content-grid">
            <!-- Contenido Principal -->
            <div class="main-content">
                <h3 class="section-title">
                    <i class="fas fa-history"></i>
                    Actividad Reciente
                </h3>
                
                <div class="table-responsive">
                    <table class="table table-custom">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Usuario</th>
                                <th>Acción</th>
                                <th>Detalles</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs_recientes as $log): ?>
                            <tr>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($log['user'] ?? 'Sistema'); ?></strong>
                                    <?php if ($log['email']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($log['email']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge-custom badge-primary">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo htmlspecialchars(substr($log['details'], 0, 50)); ?>...</small>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <h4 class="section-title">
                            <i class="fas fa-building"></i>
                            Empresas Recientes
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Empresa</th>
                                        <th>Estado</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($empresas_recientes as $empresa): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($empresa['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge-custom badge-success">Activa</span>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y', strtotime($empresa['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <h4 class="section-title">
                            <i class="fas fa-users"></i>
                            Usuarios Recientes
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Empresa</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usuarios_recientes as $usuario): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($usuario['user']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($usuario['email']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($usuario['company_name'] ?? 'Sin empresa'); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></small>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="sidebar-content">
                <!-- Información del Usuario -->
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                    <div class="user-role">
                        <?php if ($is_superadmin): ?>
                            <i class="fas fa-crown text-warning"></i> Superadministrador
                        <?php else: ?>
                            <i class="fas fa-user-shield text-info"></i> Administrador
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones Rápidas -->
                <h4 class="section-title">
                    <i class="fas fa-bolt"></i>
                    Acciones Rápidas
                </h4>
                
                <div class="quick-actions">
                    <a href="companies.php" class="action-btn">
                        <i class="fas fa-building"></i>
                        <span>Empresas</span>
                    </a>
                    <a href="company_users.php" class="action-btn">
                        <i class="fas fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                    <a href="services.php" class="action-btn">
                        <i class="fas fa-cogs"></i>
                        <span>Servicios</span>
                    </a>
                    <a href="audit_logs.php" class="action-btn">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Logs</span>
                    </a>
                    <a href="../Modules/BOTWhatsapp/manage.php" class="action-btn">
                        <i class="fab fa-whatsapp"></i>
                        <span>BOT WhatsApp</span>
                    </a>
                    <a href="role_permissions.php" class="action-btn">
                        <i class="fas fa-key"></i>
                        <span>Permisos</span>
                    </a>
                </div>

                <!-- Estadísticas de Actividad -->
                <h4 class="section-title">
                    <i class="fas fa-chart-bar"></i>
                    Actividad Hoy
                </h4>
                
                <div class="activity-item">
                    <div class="activity-icon bg-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Usuarios Activos</div>
                        <div class="activity-time"><?php echo $stats['usuarios_activos_hoy']; ?> usuarios</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon bg-success">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Logs Generados</div>
                        <div class="activity-time"><?php echo $stats['logs_hoy']; ?> registros</div>
                    </div>
                </div>
                
                <div class="activity-item">
                    <div class="activity-icon bg-warning">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Tickets Abiertos</div>
                        <div class="activity-time"><?php echo $stats['tickets']; ?> total</div>
                    </div>
                </div>

                <!-- Servicios Populares -->
                <h4 class="section-title">
                    <i class="fas fa-star"></i>
                    Servicios Populares
                </h4>
                
                <?php foreach ($servicios_populares as $servicio): ?>
                <div class="activity-item">
                    <div class="activity-icon bg-info">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title"><?php echo htmlspecialchars($servicio['name']); ?></div>
                        <div class="activity-time"><?php echo $servicio['ticket_count']; ?> tickets</div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Grilla Responsive para Móviles -->
        <div class="responsive-grid">
            <div class="main-content">
                <h3 class="section-title">
                    <i class="fas fa-chart-pie"></i>
                    Resumen del Sistema
                </h3>
                <div class="row">
                    <div class="col-6 col-md-3 mb-3">
                        <div class="text-center">
                            <div class="stat-number text-primary"><?php echo number_format($stats['empresas']); ?></div>
                            <small class="text-muted">Empresas</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="text-center">
                            <div class="stat-number text-success"><?php echo number_format($stats['usuarios']); ?></div>
                            <small class="text-muted">Usuarios</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="text-center">
                            <div class="stat-number text-warning"><?php echo number_format($stats['servicios']); ?></div>
                            <small class="text-muted">Servicios</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-3">
                        <div class="text-center">
                            <div class="stat-number text-danger"><?php echo number_format($stats['tickets']); ?></div>
                            <small class="text-muted">Tickets</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../footer.php'; ?>
    <?php include __DIR__ . '/../views/partials/slidepanel_menu.php'; ?>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Inicializar theme switcher
        if (typeof ThemeSwitcher !== 'undefined') {
            const themeSwitcher = new ThemeSwitcher();
            themeSwitcher.init();
        }
        
        // Auto-refresh de estadísticas cada 30 segundos
        setInterval(function() {
            // Aquí se podría hacer una llamada AJAX para actualizar las estadísticas
            console.log('Dashboard actualizado automáticamente');
        }, 30000);
        
        // Tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
        // Animaciones suaves para las tarjetas
        $('.stat-card').hover(
            function() {
                $(this).addClass('shadow-lg');
            },
            function() {
                $(this).removeClass('shadow-lg');
            }
        );
    });
    </script>
</body>
</html> 