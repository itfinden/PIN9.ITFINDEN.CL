<?php
/**
 * Sistema de Menús por Roles - SUPERADMIN, ADMIN, USUARIO
 * Sistema simple y claro para gestionar menús por roles específicos
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Creando Sistema de Menús por Roles...\n\n";
    
    // 1. Tabla de funcionalidades/módulos disponibles
    $sql1 = "CREATE TABLE IF NOT EXISTS `system_functionalities` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `functionality_key` varchar(50) NOT NULL,
        `functionality_name` varchar(100) NOT NULL,
        `icon` varchar(100) NOT NULL DEFAULT 'fas fa-cog',
        `url` varchar(255) NOT NULL,
        `description` text,
        `is_active` tinyint(1) DEFAULT 1,
        `auto_permission` tinyint(1) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `functionality_key` (`functionality_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql1);
    echo "✅ Tabla 'system_functionalities' creada\n";
    
    // 2. Tabla de menús por rol
    $sql2 = "CREATE TABLE IF NOT EXISTS `role_menus` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `role_type` enum('superadmin','admin','usuario') NOT NULL,
        `functionality_key` varchar(50) NOT NULL,
        `is_visible` tinyint(1) DEFAULT 1,
        `menu_order` int(11) DEFAULT 0,
        `custom_title` varchar(100) DEFAULT NULL,
        `custom_icon` varchar(100) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `role_functionality` (`role_type`, `functionality_key`),
        KEY `role_type` (`role_type`),
        KEY `functionality_key` (`functionality_key`),
        KEY `menu_order` (`menu_order`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql2);
    echo "✅ Tabla 'role_menus' creada\n";
    
    // 3. Tabla de permisos automáticos
    $sql3 = "CREATE TABLE IF NOT EXISTS `auto_permissions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `functionality_key` varchar(50) NOT NULL,
        `permission_name` varchar(100) NOT NULL,
        `permission_description` text,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `functionality_permission` (`functionality_key`, `permission_name`),
        KEY `functionality_key` (`functionality_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql3);
    echo "✅ Tabla 'auto_permissions' creada\n";
    
    // Insertar funcionalidades básicas del sistema
    echo "\n📋 Insertando funcionalidades básicas...\n";
    
    $functionalities = [
        ['dashboard', 'Dashboard', 'fas fa-tachometer-alt', 'dashboard.php', 'Panel principal del sistema'],
        ['calendar', 'Calendario', 'fas fa-calendar-alt', 'calendar.php', 'Gestión de eventos y citas'],
        ['projects', 'Proyectos', 'fas fa-project-diagram', 'projects.php', 'Gestión de proyectos y tareas'],
        ['tickets', 'Tickets', 'fas fa-ticket-alt', 'tickets.php', 'Sistema de tickets y soporte'],
        ['today', 'Hoy', 'fas fa-calendar-day', 'today.php', 'Resumen del día actual'],
        ['services', 'Servicios', 'fas fa-cogs', 'services.php', 'Gestión de servicios'],
        ['companies', 'Empresas', 'fas fa-building', 'admin/companies.php', 'Administración de empresas'],
        ['users', 'Usuarios', 'fas fa-users', 'admin/company_users.php', 'Gestión de usuarios'],
        ['billing', 'Facturación', 'fas fa-file-invoice-dollar', 'admin/billing_reports.php', 'Reportes de facturación'],
        ['audit', 'Auditoría', 'fas fa-clipboard-list', 'admin/audit_logs.php', 'Logs de auditoría'],
        ['settings', 'Configuración', 'fas fa-cog', 'admin/company-settings.php', 'Configuración del sistema'],
        ['content_editor', 'Editor de Contenido', 'fas fa-edit', 'admin/content_editor.php', 'Editor de contenido WYSIWYG'],
        ['menu_creator', 'Creador de Menús', 'fas fa-bars', 'admin/menu_creator.php', 'Gestión de menús por roles']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_functionalities (functionality_key, functionality_name, icon, url, description) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($functionalities as $func) {
        $stmt->execute($func);
        echo "   ✅ {$func[1]}\n";
    }
    
    // Insertar permisos automáticos
    echo "\n🔐 Configurando permisos automáticos...\n";
    
    $auto_permissions = [
        ['dashboard', 'view_dashboard', 'Ver dashboard'],
        ['calendar', 'view_calendar', 'Ver calendario'],
        ['calendar', 'manage_events', 'Gestionar eventos'],
        ['projects', 'view_projects', 'Ver proyectos'],
        ['projects', 'manage_projects', 'Gestionar proyectos'],
        ['tickets', 'view_tickets', 'Ver tickets'],
        ['tickets', 'manage_tickets', 'Gestionar tickets'],
        ['today', 'view_today', 'Ver resumen del día'],
        ['services', 'view_services', 'Ver servicios'],
        ['services', 'manage_services', 'Gestionar servicios'],
        ['companies', 'view_companies', 'Ver empresas'],
        ['companies', 'manage_companies', 'Gestionar empresas'],
        ['users', 'view_users', 'Ver usuarios'],
        ['users', 'manage_users', 'Gestionar usuarios'],
        ['billing', 'view_billing', 'Ver facturación'],
        ['audit', 'view_audit', 'Ver auditoría'],
        ['settings', 'view_settings', 'Ver configuración'],
        ['settings', 'manage_settings', 'Gestionar configuración'],
        ['content_editor', 'edit_content', 'Editar contenido'],
        ['menu_creator', 'manage_menus', 'Gestionar menús']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO auto_permissions (functionality_key, permission_name, permission_description) VALUES (?, ?, ?)");
    
    foreach ($auto_permissions as $perm) {
        $stmt->execute($perm);
        echo "   ✅ {$perm[1]}\n";
    }
    
    // Configurar menús por rol
    echo "\n🎯 Configurando menús por rol...\n";
    
    // SUPERADMIN - Acceso a todo
    $superadmin_menus = [
        ['superadmin', 'dashboard', 1, 1],
        ['superadmin', 'calendar', 1, 2],
        ['superadmin', 'projects', 1, 3],
        ['superadmin', 'tickets', 1, 4],
        ['superadmin', 'today', 1, 5],
        ['superadmin', 'services', 1, 6],
        ['superadmin', 'companies', 1, 7],
        ['superadmin', 'users', 1, 8],
        ['superadmin', 'billing', 1, 9],
        ['superadmin', 'audit', 1, 10],
        ['superadmin', 'settings', 1, 11],
        ['superadmin', 'content_editor', 1, 12],
        ['superadmin', 'menu_creator', 1, 13]
    ];
    
    // ADMIN - Acceso a administración de empresa
    $admin_menus = [
        ['admin', 'dashboard', 1, 1],
        ['admin', 'calendar', 1, 2],
        ['admin', 'projects', 1, 3],
        ['admin', 'tickets', 1, 4],
        ['admin', 'today', 1, 5],
        ['admin', 'services', 1, 6],
        ['admin', 'users', 1, 7],
        ['admin', 'settings', 1, 8]
    ];
    
    // USUARIO - Acceso básico
    $usuario_menus = [
        ['usuario', 'dashboard', 1, 1],
        ['usuario', 'calendar', 1, 2],
        ['usuario', 'projects', 1, 3],
        ['usuario', 'tickets', 1, 4],
        ['usuario', 'today', 1, 5]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO role_menus (role_type, functionality_key, is_visible, menu_order) VALUES (?, ?, ?, ?)");
    
    echo "   🔹 SUPERADMIN:\n";
    foreach ($superadmin_menus as $menu) {
        $stmt->execute($menu);
        echo "      ✅ {$menu[1]}\n";
    }
    
    echo "   🔹 ADMIN:\n";
    foreach ($admin_menus as $menu) {
        $stmt->execute($menu);
        echo "      ✅ {$menu[1]}\n";
    }
    
    echo "   🔹 USUARIO:\n";
    foreach ($usuario_menus as $menu) {
        $stmt->execute($menu);
        echo "      ✅ {$menu[1]}\n";
    }
    
    echo "\n🎉 Sistema de Menús por Roles creado exitosamente!\n";
    echo "\n📋 Próximos pasos:\n";
    echo "   1. Acceder a: http://localhost/admin/menu_creator.php\n";
    echo "   2. Configurar menús por rol con drag & drop\n";
    echo "   3. Las nuevas funcionalidades se agregarán automáticamente\n";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
