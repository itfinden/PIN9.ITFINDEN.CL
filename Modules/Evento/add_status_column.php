<?php
session_start();

require_once __DIR__ . '/../../db/functions.php';

if (!isset($_SESSION['user'])) {
    header('Location: /login.php');
    die();
}

$database = new Database();
$db = $database->connection();

$errors = [];
$messages = [];

try {
    // Verificar si la columna status ya existe
    $stmt = $db->prepare("SHOW COLUMNS FROM evento_main LIKE 'status'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if (!$column_exists) {
        // Agregar columna status
        $db->exec("ALTER TABLE evento_main ADD COLUMN status ENUM('proximo','en_curso','finalizado') DEFAULT 'proximo' AFTER end_date");
        $messages[] = 'Columna status agregada correctamente a evento_main.';
    } else {
        $messages[] = 'La columna status ya existe en evento_main.';
    }
    
    // Verificar la estructura actual de la tabla
    $stmt = $db->prepare("DESCRIBE evento_main");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $messages[] = 'Estructura actual de la tabla evento_main:';
    foreach ($columns as $col) {
        $messages[] = "- {$col['Field']}: {$col['Type']} {$col['Null']} {$col['Key']} {$col['Default']}";
    }
    
} catch (Exception $e) {
    $errors[] = 'Error en la migración: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migración Columna Status</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" crossorigin="anonymous">
</head>
<body>
    <div class="container mt-4">
        <h3>Migración Columna Status - Módulo Evento</h3>
        
        <?php foreach ($messages as $m): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($m); ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; ?>
        
        <div class="mt-3">
            <a href="/Modules/Evento/edit_event.php?id=3" class="btn btn-primary">Probar Edición de Evento</a>
            <a href="/evento_dashboard.php" class="btn btn-secondary">Volver al Dashboard</a>
        </div>
    </div>
</body>
</html>
