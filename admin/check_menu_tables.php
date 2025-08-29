<?php
/**
 * Script para verificar que las tablas del sistema de menús se crearon correctamente
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔍 Verificando tablas del sistema de menús...\n\n";
    
    // Lista de tablas a verificar
    $tables = [
        'dynamic_menus',
        'dynamic_menu_items', 
        'company_menus',
        'role_menu_items',
        'system_modules'
    ];
    
    foreach ($tables as $table) {
        try {
            $sql = "SHOW TABLES LIKE ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$table]);
            
            if ($stmt->fetch()) {
                // Contar registros
                $count_sql = "SELECT COUNT(*) FROM $table";
                $count_stmt = $pdo->query($count_sql);
                $count = $count_stmt->fetchColumn();
                
                echo "✅ Tabla '$table' existe con $count registros\n";
                
                // Mostrar estructura
                $structure_sql = "DESCRIBE $table";
                $structure_stmt = $pdo->query($structure_sql);
                $columns = $structure_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "   Estructura:\n";
                foreach ($columns as $column) {
                    echo "     - {$column['Field']}: {$column['Type']} " . 
                         ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') .
                         ($column['Key'] === 'PRI' ? ' (PRIMARY)' : '') . "\n";
                }
                
                // Mostrar algunos datos de ejemplo
                if ($count > 0) {
                    $sample_sql = "SELECT * FROM $table LIMIT 3";
                    $sample_stmt = $pdo->query($sample_sql);
                    $samples = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "   Datos de ejemplo:\n";
                    foreach ($samples as $sample) {
                        $sample_str = json_encode($sample, JSON_PRETTY_PRINT);
                        echo "     " . str_replace("\n", "\n     ", $sample_str) . "\n";
                    }
                }
                
            } else {
                echo "❌ Tabla '$table' NO existe\n";
            }
            
        } catch (PDOException $e) {
            echo "❌ Error verificando tabla '$table': " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    echo "🎯 Verificación completada!\n";
    
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>
