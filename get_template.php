<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once 'db/functions.php';

$database = new Database();
$connection = $database->connection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_id'])) {
    try {
        $template_id = (int)$_POST['template_id'];
        
        $stmt = $connection->prepare("SELECT * FROM ticket_templates WHERE id_template = ? AND is_active = 1");
        $stmt->execute([$template_id]);
        $template = $stmt->fetch();
        
        if ($template) {
            echo json_encode([
                'success' => true,
                'data' => $template
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Plantilla no encontrada'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener la plantilla: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Solicitud inválida'
    ]);
}
?> 