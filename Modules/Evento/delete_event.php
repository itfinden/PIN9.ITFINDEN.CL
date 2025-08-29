<?php
session_start();

require_once __DIR__ . '/../../lang/language_handler.php';
require_once __DIR__ . '/../../theme_handler.php';
require_once __DIR__ . '/../../db/functions.php';
require_once __DIR__ . '/../../security/check_access.php';

if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    header('Location: /login.php');
    exit;
}

$id_user = $_SESSION['id_user'];
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($event_id <= 0) {
    header('Location: /evento_dashboard.php');
    exit;
}

$database = new Database();
$db = $database->connection();

// Verificar que el usuario puede eliminar este evento
$stmt = $db->prepare('SELECT * FROM evento_main WHERE id_evento_main = :id');
$stmt->execute([':id' => $event_id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    header('Location: /evento_dashboard.php');
    exit;
}

// Solo el dueño o superadmin puede eliminar
$is_superadmin = isSuperAdmin($id_user);
if (!$is_superadmin && $evento['id_owner'] != $id_user) {
    header('Location: /evento_dashboard.php');
    exit;
}

try {
    $db->beginTransaction();
    
    // 1. Eliminar eventos del calendario (subeventos)
    $stmt = $db->prepare('SELECT id_calendar_event FROM evento_subevent WHERE id_evento_main = :event_id AND id_calendar_event IS NOT NULL');
    $stmt->execute([':event_id' => $event_id]);
    $calendar_events = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($calendar_events)) {
        $placeholders = str_repeat('?,', count($calendar_events) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM calendar WHERE id_event IN ($placeholders)");
        $stmt->execute($calendar_events);
    }
    
    // 2. Eliminar asignaciones de invitados a subeventos
    $stmt = $db->prepare('DELETE esg FROM evento_subevent_guest esg 
                         JOIN evento_subevent es ON esg.id_evento_subevent = es.id_evento_subevent 
                         WHERE es.id_evento_main = :event_id');
    $stmt->execute([':event_id' => $event_id]);
    
    // 3. Eliminar subeventos
    $stmt = $db->prepare('DELETE FROM evento_subevent WHERE id_evento_main = :event_id');
    $stmt->execute([':event_id' => $event_id]);
    
    // 4. Eliminar invitados
    $stmt = $db->prepare('DELETE FROM evento_guest WHERE id_evento_main = :event_id');
    $stmt->execute([':event_id' => $event_id]);
    
    // 5. Eliminar calendario específico
    if ($evento['id_calendar']) {
        $stmt = $db->prepare('DELETE FROM calendar_companies WHERE id_calendar_companies = :calendar_id');
        $stmt->execute([':calendar_id' => $evento['id_calendar']]);
    }
    
    // 6. Eliminar evento padre
    $stmt = $db->prepare('DELETE FROM evento_main WHERE id_evento_main = :event_id');
    $stmt->execute([':event_id' => $event_id]);
    
    $db->commit();
    
    audit_log('Eliminar Evento Completo', 'Evento: ' . $evento['title'] . ', ID: ' . $event_id);
    
    header('Location: /evento_dashboard.php?deleted=1');
    exit;
    
} catch (Exception $e) {
    $db->rollBack();
    error_log('Error eliminando evento: ' . $e->getMessage());
    header('Location: /evento_dashboard.php?error=1');
    exit;
}
?>

