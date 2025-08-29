<?php
/**
 * Script de Backup de Base de Datos
 * Versión: 2.1.0
 * Fecha: <?php echo date('Y-m-d H:i:s'); ?>
 */

require_once 'db/functions.php';

try {
    $database = new Database();
    $connection = $database->connection();
    
    // Obtener información de la base de datos
    $db_name = 'itfinden_pin9';
    $backup_file = 'backup_pin9_v2.1.0_' . date('Y-m-d_H-i-s') . '.sql';
    
    echo "🔄 Iniciando backup de la base de datos...\n";
    echo "📁 Archivo: $backup_file\n";
    
    // Obtener todas las tablas
    $stmt = $connection->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $backup_content = "-- Backup de Base de Datos Pin9\n";
    $backup_content .= "-- Versión: 2.1.0\n";
    $backup_content .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- Base de datos: $db_name\n\n";
    
    $backup_content .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $backup_content .= "SET AUTOCOMMIT = 0;\n";
    $backup_content .= "START TRANSACTION;\n";
    $backup_content .= "SET time_zone = \"+00:00\";\n\n";
    
    foreach ($tables as $table) {
        echo "📋 Procesando tabla: $table\n";
        
        // Obtener estructura de la tabla
        $stmt = $connection->query("SHOW CREATE TABLE `$table`");
        $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
        $backup_content .= "-- Estructura de tabla `$table`\n";
        $backup_content .= "DROP TABLE IF EXISTS `$table`;\n";
        $backup_content .= $create_table['Create Table'] . ";\n\n";
        
        // Obtener datos de la tabla
        $stmt = $connection->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $backup_content .= "-- Datos de tabla `$table`\n";
            $backup_content .= "INSERT INTO `$table` VALUES\n";
            
            $insert_values = [];
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $insert_values[] = "(" . implode(', ', $values) . ")";
            }
            
            $backup_content .= implode(",\n", $insert_values) . ";\n\n";
        }
    }
    
    $backup_content .= "COMMIT;\n";
    
    // Guardar el archivo de backup
    file_put_contents($backup_file, $backup_content);
    
    echo "✅ Backup completado exitosamente!\n";
    echo "📁 Archivo guardado: $backup_file\n";
    echo "📊 Tamaño: " . number_format(filesize($backup_file) / 1024, 2) . " KB\n";
    echo "📋 Tablas procesadas: " . count($tables) . "\n";
    
    // Crear también un backup comprimido
    $compressed_file = $backup_file . '.gz';
    $gz = gzopen($compressed_file, 'w9');
    gzwrite($gz, $backup_content);
    gzclose($gz);
    
    echo "🗜️  Backup comprimido: $compressed_file\n";
    echo "📊 Tamaño comprimido: " . number_format(filesize($compressed_file) / 1024, 2) . " KB\n";
    
} catch (Exception $e) {
    echo "❌ Error durante el backup: " . $e->getMessage() . "\n";
}
?> 