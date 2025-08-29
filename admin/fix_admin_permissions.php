<?php
session_start();

// Verificar que sea superadmin
if (!isset($_SESSION['id_user']) || $_SESSION['is_superadmin'] != 1) {
    die('Acceso restringido a superadmins');
}

require_once __DIR__ . '/../db/functions.php';

$database = new Database();
$connection = $database->connection();

echo "<h2>Arreglando Permisos del Admin Dashboard</h2>";
echo "<hr>";

// 1. Registrar la URL del dashboard admin en permissions
echo "<h3>1. Registrando URL del dashboard admin...</h3>";

$stmt = $connection->prepare("SELECT COUNT(*) FROM permissions WHERE url = '/admin/dashboard.php'");
$stmt->execute();
$exists = $stmt->fetchColumn();

if ($exists == 0) {
    $stmt = $connection->prepare("
        INSERT INTO permissions (name, description, Titulo, url, icon, section, menu_order, show_in_menu) 
        VALUES ('admin_dashboard', 'Dashboard de administración', 'Dashboard Admin', '/admin/dashboard.php', 'fas fa-chart-bar', 'Admin', 1, 1)
    ");
    $stmt->execute();
    $dashboard_id = $connection->lastInsertId();
    echo "✅ URL /admin/dashboard.php registrada con ID: $dashboard_id<br>";
} else {
    $stmt = $connection->prepare("SELECT id_permission FROM permissions WHERE url = '/admin/dashboard.php'");
    $stmt->execute();
    $dashboard_id = $stmt->fetchColumn();
    echo "✅ URL /admin/dashboard.php ya existe con ID: $dashboard_id<br>";
}

// 2. Asignar permiso al superadmin
echo "<h3>2. Asignando permiso al superadmin...</h3>";

$stmt = $connection->prepare("SELECT COUNT(*) FROM GET_ACCESS WHERE id_user = ? AND id_permission = ?");
$stmt->execute([$_SESSION['id_user'], $dashboard_id]);
$has_permission = $stmt->fetchColumn();

if ($has_permission == 0) {
    $stmt = $connection->prepare("INSERT INTO GET_ACCESS (id_user, id_permission) VALUES (?, ?)");
    $stmt->execute([$_SESSION['id_user'], $dashboard_id]);
    echo "✅ Permiso asignado al superadmin<br>";
} else {
    echo "✅ Superadmin ya tiene el permiso<br>";
}

// 3. Registrar otras URLs admin importantes
echo "<h3>3. Registrando otras URLs admin...</h3>";

$admin_urls = [
    ['admin_calendars', 'Gestión de calendarios', 'Calendarios', '/admin/calendars.php', 'fas fa-calendar-alt'],
    ['admin_companies', 'Gestión de empresas', 'Empresas', '/admin/companies.php', 'fas fa-building'],
    ['admin_services', 'Gestión de servicios', 'Servicios', '/admin/services.php', 'fas fa-cogs'],
    ['admin_users', 'Gestión de usuarios', 'Usuarios', '/admin/company_users.php', 'fas fa-users'],
    ['admin_audit_logs', 'Logs de auditoría', 'Auditoría', '/admin/audit_logs.php', 'fas fa-clipboard-list'],
    ['admin_role_permissions', 'Permisos por rol', 'Permisos', '/admin/role_permissions.php', 'fas fa-key'],
    ['admin_superadmins', 'Gestión de superadmins', 'Superadmins', '/admin/superadmins.php', 'fas fa-user-shield'],
    ['admin_billing_config', 'Configuración de facturación', 'Facturación', '/admin/billing_config.php', 'fas fa-credit-card'],
    ['admin_subscriptions', 'Gestión de suscripciones', 'Suscripciones', '/admin/subscriptions.php', 'fas fa-receipt'],
    ['admin_invoices', 'Gestión de facturas', 'Facturas', '/admin/invoices.php', 'fas fa-file-invoice']
];

foreach ($admin_urls as $url_data) {
    $stmt = $connection->prepare("SELECT COUNT(*) FROM permissions WHERE url = ?");
    $stmt->execute([$url_data[3]]);
    $exists = $stmt->fetchColumn();
    
    if ($exists == 0) {
        $stmt = $connection->prepare("
            INSERT INTO permissions (name, description, Titulo, url, icon, section, menu_order, show_in_menu) 
            VALUES (?, ?, ?, ?, ?, 'Admin', 10, 1)
        ");
        $stmt->execute($url_data);
        echo "✅ URL {$url_data[3]} registrada<br>";
    } else {
        echo "✅ URL {$url_data[3]} ya existe<br>";
    }
}

// 4. Asignar todos los permisos admin al superadmin
echo "<h3>4. Asignando todos los permisos admin al superadmin...</h3>";

$stmt = $connection->prepare("
    SELECT p.id_permission 
    FROM permissions p 
    WHERE p.section = 'Admin' 
    AND p.id_permission NOT IN (
        SELECT ga.id_permission 
        FROM GET_ACCESS ga 
        WHERE ga.id_user = ?
    )
");
$stmt->execute([$_SESSION['id_user']]);
$missing_permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (!empty($missing_permissions)) {
    foreach ($missing_permissions as $perm_id) {
        $stmt = $connection->prepare("INSERT INTO GET_ACCESS (id_user, id_permission) VALUES (?, ?)");
        $stmt->execute([$_SESSION['id_user'], $perm_id]);
    }
    echo "✅ " . count($missing_permissions) . " permisos admin asignados al superadmin<br>";
} else {
    echo "✅ Superadmin ya tiene todos los permisos admin<br>";
}

echo "<hr>";
echo "<h3>✅ Proceso completado</h3>";
echo "<p>Ahora puedes acceder a:</p>";
echo "<ul>";
echo "<li><a href='dashboard.php'>Dashboard Admin</a></li>";
echo "<li><a href='calendars.php'>Calendarios</a></li>";
echo "<li><a href='companies.php'>Empresas</a></li>";
echo "<li><a href='services.php'>Servicios</a></li>";
echo "<li><a href='company_users.php'>Usuarios</a></li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Nota:</strong> Este archivo es solo para configuración. Elimínalo en producción.</p>";
?>
