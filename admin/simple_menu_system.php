<?php
/**
 * Sistema Simple de Menús por Módulos y Roles
 * Reemplaza el sistema complejo anterior
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Creando Sistema Simple de Menús...\n\n";
    
    // 1. Tabla de módulos del sistema
    $sql1 = "CREATE TABLE IF NOT EXISTS `system_modules_simple` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `module_key` varchar(50) NOT NULL,
        `module_name` varchar(100) NOT NULL,
        `icon` varchar(100) NOT NULL,
        `url` varchar(255) NOT NULL,
        `description` text,
        `is_active` tinyint(1) DEFAULT 1,
        `menu_order` int(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `module_key` (`module_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql1);
    echo "✅ Tabla 'system_modules_simple' creada\n";
    
    // 2. Tabla de roles del sistema
    $sql2 = "CREATE TABLE IF NOT EXISTS `system_roles_simple` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `role_key` varchar(50) NOT NULL,
        `role_name` varchar(100) NOT NULL,
        `description` text,
        `is_active` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`id`),
        UNIQUE KEY `role_key` (`role_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql2);
    echo "✅ Tabla 'system_roles_simple' creada\n";
    
    // 3. Tabla de permisos por módulo y rol
    $sql3 = "CREATE TABLE IF NOT EXISTS `module_role_permissions_simple` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `module_key` varchar(50) NOT NULL,
        `role_key` varchar(50) NOT NULL,
        `can_access` tinyint(1) DEFAULT 0,
        `can_edit` tinyint(1) DEFAULT 0,
        `can_delete` tinyint(1) DEFAULT 0,
        `menu_order` int(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `module_role` (`module_key`, `role_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql3);
    echo "✅ Tabla 'module_role_permissions_simple' creada\n";
    
    echo "\n🎉 Sistema Simple de Menús creado exitosamente!\n";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
