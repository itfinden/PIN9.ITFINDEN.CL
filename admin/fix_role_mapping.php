<?php
/**
 * Script para mapear roles existentes con el nuevo sistema simple
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Mapeando roles existentes...\n\n";
    
    // 1. Ver roles existentes en la base de datos
    echo "📋 Roles existentes en la base de datos:\n";
    $sql = "SELECT * FROM roles ORDER BY id_role";
    $stmt = $pdo->query($sql);
    $existingRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($existingRoles as $role) {
        echo "   - ID: {$role['id_role']}, Nombre: {$role['role_name']}\n";
    }
    
    echo "\n";
    
    // 2. Ver usuarios y sus roles
    echo "👥 Usuarios y sus roles:\n";
    $sql = "SELECT u.id_user, u.user, r.role_name, r.id_role 
            FROM users u 
            JOIN user_roles ur ON u.id_user = ur.id_user 
            JOIN roles r ON ur.id_role = r.id_role 
            ORDER BY u.id_user";
    $stmt = $pdo->query($sql);
    $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userRoles as $userRole) {
        echo "   - Usuario: {$userRole['user']} (ID: {$userRole['id_user']}) - Rol: {$userRole['role_name']} (ID: {$userRole['id_role']})\n";
    }
    
    echo "\n";
    
    // 3. Actualizar la tabla system_roles_simple con los roles reales
    echo "🔄 Actualizando roles en el sistema simple...\n";
    
    // Mapear roles existentes a claves del sistema simple
    $roleMapping = [
        1 => 'superadmin',  // Asumiendo que ID 1 es superadmin
        2 => 'admin',        // Asumiendo que ID 2 es admin
        3 => 'manager',      // Asumiendo que ID 3 es manager
        4 => 'user',         // Asumiendo que ID 4 es user
        5 => 'guest'         // Asumiendo que ID 5 es guest
    ];
    
    // Actualizar la tabla system_roles_simple
    foreach ($roleMapping as $oldRoleId => $newRoleKey) {
        $roleName = '';
        foreach ($existingRoles as $role) {
            if ($role['id_role'] == $oldRoleId) {
                $roleName = $role['role_name'];
                break;
            }
        }
        
        if ($roleName) {
            $sql = "UPDATE system_roles_simple SET role_key = ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$newRoleKey, $oldRoleId]);
            echo "   ✅ Rol ID $oldRoleId mapeado a '$newRoleKey' ($roleName)\n";
        }
    }
    
    // 4. Crear tabla de mapeo de roles
    echo "\n🔗 Creando tabla de mapeo de roles...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS `role_mapping` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `old_role_id` int(11) NOT NULL,
        `new_role_key` varchar(50) NOT NULL,
        `old_role_name` varchar(100) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `old_role_id` (`old_role_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "✅ Tabla 'role_mapping' creada\n";
    
    // Insertar mapeo
    $stmt = $pdo->prepare("INSERT IGNORE INTO role_mapping (old_role_id, new_role_key, old_role_name) VALUES (?, ?, ?)");
    
    foreach ($roleMapping as $oldRoleId => $newRoleKey) {
        $roleName = '';
        foreach ($existingRoles as $role) {
            if ($role['id_role'] == $oldRoleId) {
                $roleName = $role['role_name'];
                break;
            }
        }
        
        if ($roleName) {
            $stmt->execute([$oldRoleId, $newRoleKey, $roleName]);
            echo "   ✅ Mapeo: ID $oldRoleId -> $newRoleKey ($roleName)\n";
        }
    }
    
    echo "\n🎉 Mapeo de roles completado!\n";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
