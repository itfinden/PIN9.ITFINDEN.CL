<?php
/**
 * Verificar estructura de la tabla roles
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Verificando tabla 'roles'...\n\n";
    
    // Ver estructura de la tabla
    $sql = "DESCRIBE roles";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 Estructura de la tabla 'roles':\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') .
             ($column['Key'] === 'PRI' ? ' (PRIMARY)' : '') . "\n";
    }
    
    echo "\n";
    
    // Ver datos de la tabla
    $sql = "SELECT * FROM roles LIMIT 10";
    $stmt = $pdo->query($sql);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Datos de la tabla 'roles':\n";
    foreach ($roles as $role) {
        echo "   - " . json_encode($role, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n";
    
    // Ver usuarios y roles
    echo "👥 Usuarios y roles:\n";
    $sql = "SELECT u.id_user, u.user, ur.id_role 
            FROM users u 
            JOIN user_roles ur ON u.id_user = ur.id_user 
            ORDER BY u.id_user 
            LIMIT 10";
    $stmt = $pdo->query($sql);
    $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($userRoles as $userRole) {
        echo "   - Usuario: {$userRole['user']} (ID: {$userRole['id_user']}) - Rol ID: {$userRole['id_role']}\n";
    }
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
