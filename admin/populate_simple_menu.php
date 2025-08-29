<?php
/**
 * Poblar Sistema Simple de Menús
 * Con módulos y roles existentes
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🚀 Poblando Sistema Simple de Menús...\n\n";
    
    // 1. Insertar módulos del sistema
    $modules = [
        ['dashboard', 'Dashboard', 'fas fa-tachometer-alt', '/content.php', 'Panel principal del sistema', 1],
        ['calendar', 'Calendario', 'fas fa-calendar-alt', '/calendar.php', 'Gestión de eventos y calendarios', 2],
        ['projects', 'Proyectos', 'fas fa-project-diagram', '/projects.php', 'Gestión de proyectos y tareas', 3],
        ['tickets', 'Tickets', 'fas fa-ticket-alt', '/tickets.php', 'Sistema de tickets de soporte', 4],
        ['today', 'Hoy', 'fas fa-clock', '/today.php', 'Tareas y eventos del día', 5],
        ['services', 'Servicios', 'fas fa-cogs', '/services.php', 'Gestión de servicios', 6],
        ['companies', 'Empresas', 'fas fa-building', '/admin/companies.php', 'Administración de empresas', 7],
        ['users', 'Usuarios', 'fas fa-users', '/admin/company_users.php', 'Gestión de usuarios', 8],
        ['billing', 'Facturación', 'fas fa-credit-card', '/admin/billing_config.php', 'Configuración de facturación', 9],
        ['audit', 'Auditoría', 'fas fa-shield-alt', '/admin/audit_logs.php', 'Logs de auditoría', 10],
        ['settings', 'Configuración', 'fas fa-cog', '/admin/company-settings.php', 'Configuración del sistema', 11]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_modules_simple (module_key, module_name, icon, url, description, menu_order) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($modules as $module) {
        $stmt->execute($module);
    }
    echo "✅ Módulos del sistema insertados\n";
    
    // 2. Insertar roles del sistema
    $roles = [
        ['superadmin', 'Super Administrador', 'Acceso completo a todo el sistema', 1],
        ['admin', 'Administrador', 'Administración de empresa y usuarios', 2],
        ['manager', 'Gerente', 'Gestión de proyectos y tickets', 3],
        ['user', 'Usuario', 'Acceso básico a módulos asignados', 4],
        ['guest', 'Invitado', 'Acceso limitado de solo lectura', 5]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_roles_simple (role_key, role_name, description, is_active) VALUES (?, ?, ?, ?)");
    
    foreach ($roles as $role) {
        $stmt->execute($role);
    }
    echo "✅ Roles del sistema insertados\n";
    
    // 3. Configurar permisos por módulo y rol
    $permissions = [
        // SUPERADMIN - Acceso a todo
        ['dashboard', 'superadmin', 1, 1, 1, 1],
        ['calendar', 'superadmin', 1, 1, 1, 2],
        ['projects', 'superadmin', 1, 1, 1, 3],
        ['tickets', 'superadmin', 1, 1, 1, 4],
        ['today', 'superadmin', 1, 1, 1, 5],
        ['services', 'superadmin', 1, 1, 1, 6],
        ['companies', 'superadmin', 1, 1, 1, 7],
        ['users', 'superadmin', 1, 1, 1, 8],
        ['billing', 'superadmin', 1, 1, 1, 9],
        ['audit', 'superadmin', 1, 1, 1, 10],
        ['settings', 'superadmin', 1, 1, 1, 11],
        
        // ADMIN - Acceso a administración de empresa
        ['dashboard', 'admin', 1, 1, 0, 1],
        ['calendar', 'admin', 1, 1, 1, 2],
        ['projects', 'admin', 1, 1, 1, 3],
        ['tickets', 'admin', 1, 1, 1, 4],
        ['today', 'admin', 1, 1, 0, 5],
        ['services', 'admin', 1, 1, 1, 6],
        ['users', 'admin', 1, 1, 1, 7],
        ['settings', 'admin', 1, 1, 0, 8],
        
        // MANAGER - Gestión de proyectos y tickets
        ['dashboard', 'manager', 1, 0, 0, 1],
        ['calendar', 'manager', 1, 1, 1, 2],
        ['projects', 'manager', 1, 1, 1, 3],
        ['tickets', 'manager', 1, 1, 1, 4],
        ['today', 'manager', 1, 0, 0, 5],
        ['services', 'manager', 1, 0, 0, 6],
        
        // USER - Acceso básico
        ['dashboard', 'user', 1, 0, 0, 1],
        ['calendar', 'user', 1, 1, 0, 2],
        ['projects', 'user', 1, 0, 0, 3],
        ['tickets', 'user', 1, 1, 0, 4],
        ['today', 'user', 1, 0, 0, 5],
        
        // GUEST - Solo lectura
        ['dashboard', 'guest', 1, 0, 0, 1],
        ['calendar', 'guest', 1, 0, 0, 2],
        ['projects', 'guest', 1, 0, 0, 3]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO module_role_permissions_simple (module_key, role_key, can_access, can_edit, can_delete, menu_order) VALUES (?, ?, ?, ?, ?, ?)");
    
    foreach ($permissions as $perm) {
        $stmt->execute($perm);
    }
    echo "✅ Permisos configurados\n";
    
    echo "\n🎉 Sistema Simple poblado exitosamente!\n";
    echo "📊 Resumen:\n";
    echo "   - " . count($modules) . " módulos configurados\n";
    echo "   - " . count($roles) . " roles definidos\n";
    echo "   - " . count($permissions) . " permisos configurados\n";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
