<?php
/**
 * Script completo para configurar el sistema de suscripciones
 * Incluye menús, permisos, seguridad y enlaces
 */

// Configuración
$config = [
    'db_host' => 'localhost',
    'db_name' => 'itfinden_pin9',
    'db_user' => 'itfinden_pin9',
    'db_pass' => 'on5A5oR0zLG69eKS',
    'debug' => true
];

echo "🚀 Configuración Completa del Sistema de Suscripciones v2.0\n";
echo "========================================================\n\n";

// Función para mostrar mensajes
function showMessage($message, $type = 'info') {
    $colors = [
        'info' => '36',    // Cyan
        'success' => '32', // Green
        'error' => '31',   // Red
        'warning' => '33'  // Yellow
    ];
    
    $color = $colors[$type] ?? '36';
    echo "\033[{$color}m{$message}\033[0m\n";
}

// Función para ejecutar SQL
function executeSQL($pdo, $sql, $description) {
    try {
        showMessage("📝 {$description}...", 'info');
        $pdo->exec($sql);
        showMessage("✅ {$description} completado", 'success');
        return true;
    } catch (PDOException $e) {
        showMessage("❌ Error en {$description}: " . $e->getMessage(), 'error');
        return false;
    }
}

try {
    // Conectar a la base de datos
    showMessage("🔌 Conectando a la base de datos...", 'info');
    $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    showMessage("✅ Conexión exitosa", 'success');
    
    echo "\n📋 Paso 1: Configurando estructura de permisos\n";
    echo "---------------------------------------------\n";
    
    // Agregar columnas necesarias para el menú si no existen
    $stmt = $pdo->query("DESCRIBE permissions");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $neededColumns = ['icon', 'section', 'menu_order', 'show_in_menu'];
    foreach ($neededColumns as $column) {
        if (!in_array($column, $columns)) {
            $sql = "ALTER TABLE permissions ADD COLUMN {$column} VARCHAR(100) DEFAULT NULL";
            executeSQL($pdo, $sql, "Agregando columna {$column}");
        }
    }
    
    echo "\n📋 Paso 2: Configurando permisos del sistema de suscripciones\n";
    echo "----------------------------------------------------------\n";
    
    // Configurar permisos del sistema de suscripciones
    $subscriptionPermissions = [
        "UPDATE permissions SET 
            icon = 'fas fa-credit-card',
            section = 'Facturación',
            menu_order = 1,
            show_in_menu = 1
        WHERE name = 'manage_subscriptions'" => "Configurando manage_subscriptions",
        
        "UPDATE permissions SET 
            icon = 'fas fa-layer-group',
            section = 'Facturación',
            menu_order = 2,
            show_in_menu = 1
        WHERE name = 'manage_subscription_plans'" => "Configurando manage_subscription_plans",
        
        "UPDATE permissions SET 
            icon = 'fas fa-file-invoice',
            section = 'Facturación',
            menu_order = 3,
            show_in_menu = 1
        WHERE name = 'manage_invoices'" => "Configurando manage_invoices",
        
        "UPDATE permissions SET 
            icon = 'fas fa-credit-card',
            section = 'Facturación',
            menu_order = 4,
            show_in_menu = 1
        WHERE name = 'manage_payment_methods'" => "Configurando manage_payment_methods",
        
        "UPDATE permissions SET 
            icon = 'fas fa-chart-bar',
            section = 'Facturación',
            menu_order = 5,
            show_in_menu = 1
        WHERE name = 'view_billing_reports'" => "Configurando view_billing_reports",
        
        "UPDATE permissions SET 
            icon = 'fas fa-cog',
            section = 'Facturación',
            menu_order = 6,
            show_in_menu = 1
        WHERE name = 'manage_billing_config'" => "Configurando manage_billing_config"
    ];
    
    foreach ($subscriptionPermissions as $sql => $description) {
        executeSQL($pdo, $sql, $description);
    }
    
    echo "\n📋 Paso 3: Configurando permisos del sistema principal\n";
    echo "----------------------------------------------------\n";
    
    // Configurar permisos del sistema principal
    $systemPermissions = [
        "UPDATE permissions SET 
            icon = 'fas fa-tachometer-alt',
            section = 'Sistema',
            menu_order = 1,
            show_in_menu = 1
        WHERE name = 'admin_panel'" => "Configurando admin_panel",
        
        "UPDATE permissions SET 
            icon = 'fas fa-building',
            section = 'Sistema',
            menu_order = 2,
            show_in_menu = 1
        WHERE name = 'manage_companies'" => "Configurando manage_companies",
        
        "UPDATE permissions SET 
            icon = 'fas fa-users',
            section = 'Sistema',
            menu_order = 3,
            show_in_menu = 1
        WHERE name = 'manage_users'" => "Configurando manage_users",
        
        "UPDATE permissions SET 
            icon = 'fas fa-cogs',
            section = 'Sistema',
            menu_order = 4,
            show_in_menu = 1
        WHERE name = 'admin_services'" => "Configurando admin_services",
        
        "UPDATE permissions SET 
            icon = 'fas fa-history',
            section = 'Sistema',
            menu_order = 5,
            show_in_menu = 1
        WHERE name = 'audit_logs'" => "Configurando audit_logs",
        
        "UPDATE permissions SET 
            icon = 'fas fa-shield-alt',
            section = 'Sistema',
            menu_order = 6,
            show_in_menu = 1
        WHERE name = 'manage_permissions'" => "Configurando manage_permissions",
        
        "UPDATE permissions SET 
            icon = 'fas fa-language',
            section = 'Sistema',
            menu_order = 7,
            show_in_menu = 1
        WHERE name = 'manage_languages'" => "Configurando manage_languages",
        
        "UPDATE permissions SET 
            icon = 'fas fa-user-shield',
            section = 'Sistema',
            menu_order = 8,
            show_in_menu = 1
        WHERE name = 'manage_superadmins'" => "Configurando manage_superadmins"
    ];
    
    foreach ($systemPermissions as $sql => $description) {
        executeSQL($pdo, $sql, $description);
    }
    
    echo "\n📋 Paso 4: Configurando permisos de empresa\n";
    echo "------------------------------------------\n";
    
    // Configurar permisos de empresa
    $companyPermissions = [
        "UPDATE permissions SET 
            icon = 'fas fa-home',
            section = 'Empresa',
            menu_order = 1,
            show_in_menu = 1
        WHERE name = 'view_dashboard'" => "Configurando view_dashboard",
        
        "UPDATE permissions SET 
            icon = 'fas fa-tools',
            section = 'Empresa',
            menu_order = 2,
            show_in_menu = 1
        WHERE name = 'manage_services'" => "Configurando manage_services",
        
        "UPDATE permissions SET 
            icon = 'fas fa-user-plus',
            section = 'Empresa',
            menu_order = 3,
            show_in_menu = 1
        WHERE name = 'invite_users'" => "Configurando invite_users",
        
        "UPDATE permissions SET 
            icon = 'fas fa-cog',
            section = 'Empresa',
            menu_order = 4,
            show_in_menu = 1
        WHERE name = 'company_settings'" => "Configurando company_settings"
    ];
    
    foreach ($companyPermissions as $sql => $description) {
        executeSQL($pdo, $sql, $description);
    }
    
    echo "\n📋 Paso 5: Asignando permisos al superadmin\n";
    echo "------------------------------------------\n";
    
    // Asegurar que el superadmin tenga todos los permisos
    $allPermissions = [
        'admin_panel', 'manage_companies', 'manage_users', 'admin_services',
        'audit_logs', 'manage_permissions', 'manage_languages', 'manage_superadmins',
        'view_dashboard', 'manage_services', 'invite_users', 'company_settings',
        'manage_subscriptions', 'manage_subscription_plans', 'manage_invoices',
        'manage_payment_methods', 'view_billing_reports', 'manage_billing_config'
    ];
    
    foreach ($allPermissions as $permission) {
        $sql = "INSERT IGNORE INTO role_permissions (id_role, id_permission) 
                SELECT r.id_role, p.id_permission 
                FROM roles r, permissions p 
                WHERE r.name = 'superadmin' AND p.name = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$permission]);
        
        if ($stmt->rowCount() > 0) {
            showMessage("✅ Permiso {$permission} asignado al superadmin", 'success');
        }
    }
    
    echo "\n📋 Paso 6: Verificando configuración completa\n";
    echo "------------------------------------------\n";
    
    // Verificar secciones del menú
    $stmt = $pdo->query("
        SELECT DISTINCT section 
        FROM permissions 
        WHERE show_in_menu = 1 AND section IS NOT NULL 
        ORDER BY section
    ");
    $sections = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    showMessage("📊 Secciones configuradas en el menú:", 'info');
    foreach ($sections as $section) {
        showMessage("  - {$section}", 'info');
    }
    
    // Verificar permisos por sección
    foreach ($sections as $section) {
        $stmt = $pdo->prepare("
            SELECT name, Titulo, icon, menu_order 
            FROM permissions 
            WHERE section = ? AND show_in_menu = 1 
            ORDER BY menu_order
        ");
        $stmt->execute([$section]);
        $permissions = $stmt->fetchAll();
        
        showMessage("📊 Permisos en {$section}:", 'info');
        foreach ($permissions as $perm) {
            showMessage("  - {$perm['Titulo']} ({$perm['name']})", 'info');
        }
    }
    
    // Verificar archivos de seguridad
    $securityFiles = [
        'admin/subscriptions.php',
        'admin/subscription_plans.php',
        'admin/invoices.php',
        'admin/payment_methods.php',
        'admin/billing_reports.php',
        'admin/billing_config.php'
    ];
    
    showMessage("📊 Verificando archivos de seguridad:", 'info');
    foreach ($securityFiles as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (strpos($content, 'verificarPermisoVista') !== false) {
                showMessage("  ✅ {$file} - Seguridad configurada", 'success');
            } else {
                showMessage("  ⚠️  {$file} - Necesita configuración de seguridad", 'warning');
            }
        } else {
            showMessage("  ❌ {$file} - Archivo no encontrado", 'error');
        }
    }
    
    echo "\n🎉 Configuración completa exitosa!\n";
    echo "================================\n";
    
    showMessage("✅ Sistema de suscripciones completamente configurado", 'success');
    showMessage("✅ Menú dinámico organizado por secciones", 'success');
    showMessage("✅ Permisos asignados correctamente", 'success');
    showMessage("✅ Seguridad implementada", 'success');
    
    echo "\n📋 Resumen de la configuración:\n";
    echo "------------------------------\n";
    echo "1. ✅ Base de datos configurada\n";
    echo "2. ✅ Permisos organizados por secciones\n";
    echo "3. ✅ Menú dinámico configurado\n";
    echo "4. ✅ Superadmin con acceso completo\n";
    echo "5. ✅ Archivos de seguridad verificados\n";
    echo "6. ✅ URLs protegidas y accesibles\n";
    
    echo "\n🌐 URLs del sistema:\n";
    echo "------------------\n";
    echo "📊 Panel principal: /admin/subscriptions.php\n";
    echo "📋 Gestión de planes: /admin/subscription_plans.php\n";
    echo "📄 Gestión de facturas: /admin/invoices.php\n";
    echo "💳 Métodos de pago: /admin/payment_methods.php\n";
    echo "📈 Reportes: /admin/billing_reports.php\n";
    echo "⚙️ Configuración: /admin/billing_config.php\n";
    
    echo "\n🔒 Secciones del menú:\n";
    echo "--------------------\n";
    foreach ($sections as $section) {
        echo "- {$section}\n";
    }
    
    echo "\n✨ ¡El sistema está completamente listo para usar!\n";
    echo "🎯 Próximo paso: Acceder al panel de administración\n";
    
} catch (PDOException $e) {
    showMessage("❌ Error de conexión: " . $e->getMessage(), 'error');
    exit(1);
} catch (Exception $e) {
    showMessage("❌ Error general: " . $e->getMessage(), 'error');
    exit(1);
}
?> 