<?php
/**
 * Script para poblar el sistema de menús dinámicos con datos iniciales
 * Basado en los módulos y permisos existentes del sistema
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🚀 Poblando sistema de menús dinámicos...\n\n";
    
    // 1. Insertar menús principales del sistema
    $menus = [
        ['menu_name' => 'Menú Principal', 'menu_key' => 'main_menu', 'description' => 'Menú principal del sistema', 'is_system' => 1],
        ['menu_name' => 'Menú de Administración', 'menu_key' => 'admin_menu', 'description' => 'Menú para superadministradores', 'is_system' => 1],
        ['menu_name' => 'Menú de Empresa', 'menu_key' => 'company_menu', 'description' => 'Menú para usuarios de empresa', 'is_system' => 1],
        ['menu_name' => 'Menú de Usuario', 'menu_key' => 'user_menu', 'description' => 'Menú personal del usuario', 'is_system' => 1],
        ['menu_name' => 'Menú de Módulos', 'menu_key' => 'modules_menu', 'description' => 'Menú de módulos del sistema', 'is_system' => 1]
    ];
    
    foreach ($menus as $menu) {
        $sql = "INSERT IGNORE INTO dynamic_menus (menu_name, menu_key, description, is_system) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$menu['menu_name'], $menu['menu_key'], $menu['description'], $menu['is_system']]);
    }
    echo "✅ Menús principales creados\n";
    
    // 2. Insertar módulos del sistema
    $modules = [
        [
            'module_key' => 'calendar',
            'display_name' => 'Calendario',
            'description' => 'Sistema de gestión de calendarios y eventos',
            'icon' => 'fas fa-calendar-alt',
            'admin_url' => '/admin/calendars.php',
            'user_url' => '/calendar.php',
            'is_core' => 1
        ],
        [
            'module_key' => 'tickets',
            'display_name' => 'Sistema de Tickets',
            'description' => 'Gestión de tickets de soporte',
            'icon' => 'fas fa-ticket-alt',
            'admin_url' => '/admin/tickets.php',
            'user_url' => '/tickets.php',
            'is_core' => 1
        ],
        [
            'module_key' => 'projects',
            'display_name' => 'Proyectos',
            'description' => 'Gestión de proyectos y tareas',
            'icon' => 'fas fa-project-diagram',
            'admin_url' => null,
            'user_url' => '/projects.php',
            'is_core' => 1
        ],
        [
            'module_key' => 'services',
            'display_name' => 'Servicios',
            'description' => 'Gestión de servicios de empresa',
            'icon' => 'fas fa-cogs',
            'admin_url' => '/admin/services.php',
            'user_url' => '/services.php',
            'is_core' => 1
        ],
        [
            'module_key' => 'billing',
            'display_name' => 'Facturación',
            'description' => 'Sistema de facturación y suscripciones',
            'icon' => 'fas fa-credit-card',
            'admin_url' => '/admin/subscriptions.php',
            'user_url' => null,
            'is_core' => 1
        ],
        [
            'module_key' => 'botwhatsapp',
            'display_name' => 'Bot WhatsApp',
            'description' => 'Integración con WhatsApp Business API',
            'icon' => 'fab fa-whatsapp',
            'admin_url' => '/Modules/BOTWhatsapp/manage.php',
            'user_url' => null,
            'is_core' => 0
        ],
        [
            'module_key' => 'evento',
            'display_name' => 'Gestión de Eventos',
            'description' => 'Sistema avanzado de gestión de eventos',
            'icon' => 'fas fa-calendar-check',
            'admin_url' => null,
            'user_url' => '/Modules/Evento/dashboard.php',
            'is_core' => 0
        ]
    ];
    
    foreach ($modules as $module) {
        $sql = "INSERT IGNORE INTO system_modules (module_key, display_name, description, icon, admin_url, user_url, is_core) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$module['module_key'], $module['display_name'], $module['description'], $module['icon'], $module['admin_url'], $module['user_url'], $module['is_core']]);
    }
    echo "✅ Módulos del sistema creados\n";
    
    // 3. Insertar elementos del menú principal
    $main_menu_items = [
        // Dashboard
        ['menu_key' => 'main_menu', 'item_key' => 'dashboard', 'title' => 'Dashboard', 'description' => 'Panel principal', 'url' => '/content.php', 'icon' => 'fas fa-tachometer-alt', 'icon_color' => '#007bff', 'permission_required' => 'view_dashboard', 'menu_order' => 1],
        
        // Calendario
        ['menu_key' => 'main_menu', 'item_key' => 'calendar', 'title' => 'Calendario', 'description' => 'Gestión de eventos', 'url' => '/calendar.php', 'icon' => 'fas fa-calendar-alt', 'icon_color' => '#28a745', 'permission_required' => 'add_calendar_event', 'menu_order' => 2],
        
        // Proyectos
        ['menu_key' => 'main_menu', 'item_key' => 'projects', 'title' => 'Proyectos', 'description' => 'Gestión de proyectos', 'url' => '/projects.php', 'icon' => 'fas fa-project-diagram', 'icon_color' => '#ffc107', 'permission_required' => 'projects', 'menu_order' => 3],
        
        // Tickets
        ['menu_key' => 'main_menu', 'item_key' => 'tickets', 'title' => 'Tickets', 'description' => 'Sistema de soporte', 'url' => '/tickets.php', 'icon' => 'fas fa-ticket-alt', 'icon_color' => '#dc3545', 'permission_required' => 'view_tickets', 'menu_order' => 4],
        
        // Servicios
        ['menu_key' => 'main_menu', 'item_key' => 'services', 'title' => 'Servicios', 'description' => 'Gestión de servicios', 'url' => '/services.php', 'icon' => 'fas fa-cogs', 'icon_color' => '#6f42c1', 'permission_required' => 'manage_services', 'menu_order' => 5]
    ];
    
    // Obtener ID del menú principal
    $stmt = $pdo->prepare("SELECT id_menu FROM dynamic_menus WHERE menu_key = 'main_menu'");
    $stmt->execute();
    $main_menu_id = $stmt->fetchColumn();
    
    foreach ($main_menu_items as $item) {
        $sql = "INSERT IGNORE INTO dynamic_menu_items (id_menu, item_key, title, description, url, icon, icon_color, permission_required, menu_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$main_menu_id, $item['item_key'], $item['title'], $item['description'], $item['url'], $item['icon'], $item['icon_color'], $item['permission_required'], $item['menu_order']]);
    }
    echo "✅ Elementos del menú principal creados\n";
    
    // 4. Insertar elementos del menú de administración
    $admin_menu_items = [
        ['menu_key' => 'admin_menu', 'item_key' => 'admin_dashboard', 'title' => 'Dashboard Admin', 'description' => 'Panel de administración', 'url' => '/admin/dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'icon_color' => '#007bff', 'permission_required' => 'admin_panel', 'menu_order' => 1],
        ['menu_key' => 'admin_menu', 'item_key' => 'manage_companies', 'title' => 'Empresas', 'description' => 'Gestión de empresas', 'url' => '/admin/companies.php', 'icon' => 'fas fa-building', 'icon_color' => '#28a745', 'permission_required' => 'manage_companies', 'menu_order' => 2],
        ['menu_key' => 'admin_menu', 'item_key' => 'manage_users', 'title' => 'Usuarios', 'description' => 'Gestión de usuarios', 'url' => '/admin/company_users.php', 'icon' => 'fas fa-users', 'icon_color' => '#ffc107', 'permission_required' => 'manage_users', 'menu_order' => 3],
        ['menu_key' => 'admin_menu', 'item_key' => 'admin_services', 'title' => 'Servicios', 'description' => 'Administrar servicios', 'url' => '/admin/services.php', 'icon' => 'fas fa-cogs', 'icon_color' => '#6f42c1', 'permission_required' => 'admin_services', 'menu_order' => 4],
        ['menu_key' => 'admin_menu', 'item_key' => 'audit_logs', 'title' => 'Logs', 'description' => 'Auditoría del sistema', 'url' => '/admin/audit_logs.php', 'icon' => 'fas fa-history', 'icon_color' => '#6c757d', 'permission_required' => 'audit_logs', 'menu_order' => 5],
        ['menu_key' => 'admin_menu', 'item_key' => 'manage_permissions', 'title' => 'Permisos', 'description' => 'Gestión de permisos', 'url' => '/admin/role_permissions.php', 'icon' => 'fas fa-key', 'icon_color' => '#e83e8c', 'permission_required' => 'manage_permissions', 'menu_order' => 6],
        ['menu_key' => 'admin_menu', 'item_key' => 'manage_superadmins', 'title' => 'SuperAdmins', 'description' => 'Gestión de superadministradores', 'url' => '/admin/superadmins.php', 'icon' => 'fas fa-crown', 'icon_color' => '#fd7e14', 'permission_required' => 'manage_superadmins', 'menu_order' => 7]
    ];
    
    // Obtener ID del menú de administración
    $stmt = $pdo->prepare("SELECT id_menu FROM dynamic_menus WHERE menu_key = 'admin_menu'");
    $stmt->execute();
    $admin_menu_id = $stmt->fetchColumn();
    
    foreach ($admin_menu_items as $item) {
        $sql = "INSERT IGNORE INTO dynamic_menu_items (id_menu, item_key, title, description, url, icon, icon_color, permission_required, menu_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$admin_menu_id, $item['item_key'], $item['title'], $item['description'], $item['url'], $item['icon'], $item['icon_color'], $item['permission_required'], $item['menu_order']]);
    }
    echo "✅ Elementos del menú de administración creados\n";
    
    // 5. Insertar elementos del menú de empresa
    $company_menu_items = [
        ['menu_key' => 'company_menu', 'item_key' => 'company_dashboard', 'title' => 'Dashboard Empresa', 'description' => 'Panel de empresa', 'url' => '/content.php', 'icon' => 'fas fa-building', 'icon_color' => '#28a745', 'permission_required' => 'view_dashboard', 'menu_order' => 1],
        ['menu_key' => 'company_menu', 'item_key' => 'company_services', 'title' => 'Mis Servicios', 'description' => 'Gestión de servicios', 'url' => '/services.php', 'icon' => 'fas fa-tools', 'icon_color' => '#6f42c1', 'permission_required' => 'manage_services', 'menu_order' => 2],
        ['menu_key' => 'company_menu', 'item_key' => 'new_service', 'title' => 'Nuevo Servicio', 'description' => 'Crear servicio', 'url' => '/new_service.php', 'icon' => 'fas fa-plus', 'icon_color' => '#28a745', 'permission_required' => 'new_service', 'menu_order' => 3],
        ['menu_key' => 'company_menu', 'item_key' => 'invite_users', 'title' => 'Invitar Usuarios', 'description' => 'Agregar usuarios', 'url' => '/invite-user.php', 'icon' => 'fas fa-user-plus', 'icon_color' => '#17a2b8', 'permission_required' => 'invite_users', 'menu_order' => 4],
        ['menu_key' => 'company_menu', 'item_key' => 'company_settings', 'title' => 'Configuración', 'description' => 'Ajustes de empresa', 'url' => '/company-settings.php', 'icon' => 'fas fa-cog', 'icon_color' => '#6c757d', 'permission_required' => 'company_settings', 'menu_order' => 5]
    ];
    
    // Obtener ID del menú de empresa
    $stmt = $pdo->prepare("SELECT id_menu FROM dynamic_menus WHERE menu_key = 'company_menu'");
    $stmt->execute();
    $company_menu_id = $stmt->fetchColumn();
    
    foreach ($company_menu_items as $item) {
        $sql = "INSERT IGNORE INTO dynamic_menu_items (id_menu, item_key, title, description, url, icon, icon_color, permission_required, menu_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$company_menu_id, $item['item_key'], $item['title'], $item['description'], $item['url'], $item['icon'], $item['icon_color'], $item['permission_required'], $item['menu_order']]);
    }
    echo "✅ Elementos del menú de empresa creados\n";
    
    // 6. Insertar elementos del menú de módulos
    $modules_menu_items = [
        ['menu_key' => 'modules_menu', 'item_key' => 'calendar_module', 'title' => 'Calendario', 'description' => 'Sistema de calendarios', 'url' => '/calendar.php', 'icon' => 'fas fa-calendar-alt', 'icon_color' => '#28a745', 'permission_required' => 'add_calendar_event', 'menu_order' => 1, 'module_name' => 'calendar'],
        ['menu_key' => 'modules_menu', 'item_key' => 'tickets_module', 'title' => 'Tickets', 'description' => 'Sistema de soporte', 'url' => '/tickets.php', 'icon' => 'fas fa-ticket-alt', 'icon_color' => '#dc3545', 'permission_required' => 'view_tickets', 'menu_order' => 2, 'module_name' => 'tickets'],
        ['menu_key' => 'modules_menu', 'item_key' => 'projects_module', 'title' => 'Proyectos', 'description' => 'Gestión de proyectos', 'url' => '/projects.php', 'icon' => 'fas fa-project-diagram', 'icon_color' => '#ffc107', 'permission_required' => 'projects', 'menu_order' => 3, 'module_name' => 'projects'],
        ['menu_key' => 'modules_menu', 'item_key' => 'services_module', 'title' => 'Servicios', 'description' => 'Gestión de servicios', 'url' => '/services.php', 'icon' => 'fas fa-cogs', 'icon_color' => '#6f42c1', 'permission_required' => 'manage_services', 'menu_order' => 4, 'module_name' => 'services'],
        ['menu_key' => 'modules_menu', 'item_key' => 'botwhatsapp_module', 'title' => 'Bot WhatsApp', 'description' => 'Integración WhatsApp', 'url' => '/Modules/BOTWhatsapp/manage.php', 'icon' => 'fab fa-whatsapp', 'icon_color' => '#25d366', 'permission_required' => 'admin_panel', 'menu_order' => 5, 'module_name' => 'botwhatsapp'],
        ['menu_key' => 'modules_menu', 'item_key' => 'evento_module', 'title' => 'Eventos', 'description' => 'Gestión de eventos', 'url' => '/Modules/Evento/dashboard.php', 'icon' => 'fas fa-calendar-check', 'icon_color' => '#fd7e14', 'permission_required' => 'add_calendar_event', 'menu_order' => 6, 'module_name' => 'evento']
    ];
    
    // Obtener ID del menú de módulos
    $stmt = $pdo->prepare("SELECT id_menu FROM dynamic_menus WHERE menu_key = 'modules_menu'");
    $stmt->execute();
    $modules_menu_id = $stmt->fetchColumn();
    
    foreach ($modules_menu_items as $item) {
        $sql = "INSERT IGNORE INTO dynamic_menu_items (id_menu, item_key, title, description, url, icon, icon_color, permission_required, menu_order, module_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$modules_menu_id, $item['item_key'], $item['title'], $item['description'], $item['url'], $item['icon'], $item['icon_color'], $item['permission_required'], $item['menu_order'], $item['module_name']]);
    }
    echo "✅ Elementos del menú de módulos creados\n";
    
    echo "\n🎉 Sistema de menús poblado exitosamente!\n";
    echo "📊 Resumen:\n";
    echo "   - 5 menús principales creados\n";
    echo "   - 7 módulos del sistema registrados\n";
    echo "   - 22 elementos de menú configurados\n";
    echo "\n📋 Próximos pasos:\n";
    echo "   1. Crear el administrador de menús (admin/menu_manager.php)\n";
    echo "   2. Crear el renderizador de menús (includes/menu_renderer.php)\n";
    echo "   3. Integrar en las vistas existentes\n";
    
} catch (PDOException $e) {
    die("❌ Error poblando sistema de menús: " . $e->getMessage());
}
?>
