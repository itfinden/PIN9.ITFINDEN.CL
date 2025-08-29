<?php
/**
 * Script para crear el sistema de menús dinámicos
 * Ejecutar una sola vez para crear las tablas necesarias
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Creando sistema de menús dinámicos...\n\n";
    
    // 1. Crear tabla de menús principales
    $sql_menus = "CREATE TABLE IF NOT EXISTS `dynamic_menus` (
        `id_menu` int(11) NOT NULL AUTO_INCREMENT,
        `menu_name` varchar(100) NOT NULL,
        `menu_key` varchar(50) NOT NULL,
        `description` text DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `is_system` tinyint(1) DEFAULT 0,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_menu`),
        UNIQUE KEY `menu_key` (`menu_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_menus);
    echo "✅ Tabla 'dynamic_menus' creada/verificada\n";
    
    // 2. Crear tabla de elementos de menú
    $sql_menu_items = "CREATE TABLE IF NOT EXISTS `dynamic_menu_items` (
        `id_menu_item` int(11) NOT NULL AUTO_INCREMENT,
        `id_menu` int(11) NOT NULL,
        `parent_id` int(11) DEFAULT NULL,
        `item_key` varchar(100) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `url` varchar(500) DEFAULT NULL,
        `icon` varchar(100) DEFAULT 'fas fa-link',
        `icon_color` varchar(7) DEFAULT '#6c757d',
        `permission_required` varchar(100) DEFAULT NULL,
        `module_name` varchar(100) DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `is_visible` tinyint(1) DEFAULT 1,
        `menu_order` int(11) DEFAULT 0,
        `target` enum('_self','_blank','_parent','_top') DEFAULT '_self',
        `css_classes` varchar(255) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_menu_item`),
        KEY `id_menu` (`id_menu`),
        KEY `parent_id` (`parent_id`),
        KEY `permission_required` (`permission_required`),
        KEY `module_name` (`module_name`),
        KEY `menu_order` (`menu_order`),
        CONSTRAINT `fk_menu_items_menu` FOREIGN KEY (`id_menu`) REFERENCES `dynamic_menus` (`id_menu`) ON DELETE CASCADE,
        CONSTRAINT `fk_menu_items_parent` FOREIGN KEY (`parent_id`) REFERENCES `dynamic_menu_items` (`id_menu_item`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_menu_items);
    echo "✅ Tabla 'dynamic_menu_items' creada/verificada\n";
    
    // 3. Crear tabla de asignación de menús a empresas
    $sql_company_menus = "CREATE TABLE IF NOT EXISTS `company_menus` (
        `id_company_menu` int(11) NOT NULL AUTO_INCREMENT,
        `id_company` int(11) NOT NULL,
        `id_menu` int(11) NOT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `custom_title` varchar(255) DEFAULT NULL,
        `custom_description` text DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_company_menu`),
        UNIQUE KEY `unique_company_menu` (`id_company`,`id_menu`),
        KEY `id_menu` (`id_menu`),
        CONSTRAINT `fk_company_menus_company` FOREIGN KEY (`id_company`) REFERENCES `companies` (`id_company`) ON DELETE CASCADE,
        CONSTRAINT `fk_company_menus_menu` FOREIGN KEY (`id_menu`) REFERENCES `dynamic_menus` (`id_menu`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_company_menus);
    echo "✅ Tabla 'company_menus' creada/verificada\n";
    
    // 4. Crear tabla de asignación de menús a roles
    $sql_role_menus = "CREATE TABLE IF NOT EXISTS `role_menu_items` (
        `id_role_menu_item` int(11) NOT NULL AUTO_INCREMENT,
        `id_role` int(11) NOT NULL,
        `id_menu_item` int(11) NOT NULL,
        `is_visible` tinyint(1) DEFAULT 1,
        `custom_title` varchar(255) DEFAULT NULL,
        `custom_icon` varchar(100) DEFAULT NULL,
        `custom_order` int(11) DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_role_menu_item`),
        UNIQUE KEY `unique_role_menu_item` (`id_role`,`id_menu_item`),
        KEY `id_menu_item` (`id_menu_item`),
        CONSTRAINT `fk_role_menu_items_role` FOREIGN KEY (`id_role`) REFERENCES `roles` (`id_role`) ON DELETE CASCADE,
        CONSTRAINT `fk_role_menu_items_menu_item` FOREIGN KEY (`id_menu_item`) REFERENCES `dynamic_menu_items` (`id_menu_item`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_role_menus);
    echo "✅ Tabla 'role_menu_items' creada/verificada\n";
    
    // 5. Crear tabla de módulos del sistema
    $sql_modules = "CREATE TABLE IF NOT EXISTS `system_modules` (
        `id_module` int(11) NOT NULL AUTO_INCREMENT,
        `module_name` varchar(100) NOT NULL,
        `module_key` varchar(50) NOT NULL,
        `display_name` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `version` varchar(20) DEFAULT '1.0.0',
        `is_active` tinyint(1) DEFAULT 1,
        `is_core` tinyint(1) DEFAULT 0,
        `icon` varchar(100) DEFAULT 'fas fa-puzzle-piece',
        `admin_url` varchar(500) DEFAULT NULL,
        `user_url` varchar(500) DEFAULT NULL,
        `permissions` text DEFAULT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_module`),
        UNIQUE KEY `module_key` (`module_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_modules);
    echo "✅ Tabla 'system_modules' creada/verificada\n";
    
    echo "\n🎉 Sistema de menús dinámicos creado exitosamente!\n";
    echo "📋 Próximos pasos:\n";
    echo "   1. Ejecutar populate_menu_system.php para llenar datos iniciales\n";
    echo "   2. Crear el administrador de menús en admin/menu_manager.php\n";
    echo "   3. Integrar con el sistema de permisos existente\n";
    
} catch (PDOException $e) {
    die("❌ Error creando sistema de menús: " . $e->getMessage());
}
?>
