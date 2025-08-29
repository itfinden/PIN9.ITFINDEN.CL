<?php
// Habilitar reporte de errores para diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Verificación de Estructura de Tablas</h2>";
echo "<hr>";

try {
    require_once __DIR__ . '/../db/functions.php';
    $database = new Database();
    $connection = $database->connection();
    
    // Tablas a verificar
    $tables = ['users', 'companies', 'services', 'tickets', 'audit_logs', 'user_roles'];
    
    foreach ($tables as $table) {
        echo "<h3>Tabla: $table</h3>";
        
        try {
            // Obtener estructura de la tabla
            $stmt = $connection->prepare("DESCRIBE $table");
            $stmt->execute();
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Columna</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
                echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Mostrar algunos registros de ejemplo
            $stmt = $connection->prepare("SELECT * FROM $table LIMIT 3");
            $stmt->execute();
            $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($sample_data)) {
                echo "<h4>Datos de ejemplo:</h4>";
                echo "<pre>" . print_r($sample_data, true) . "</pre>";
            }
            
        } catch (Exception $e) {
            echo "❌ Error al verificar tabla $table: " . $e->getMessage() . "<br>";
        }
        
        echo "<hr>";
    }
    
} catch (Exception $e) {
    echo "❌ Error de conexión: " . $e->getMessage() . "<br>";
}

echo "<p><strong>Nota:</strong> Este archivo es solo para diagnóstico. Elimínalo en producción.</p>";
?>
