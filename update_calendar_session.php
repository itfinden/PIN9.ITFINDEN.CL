<?php
session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/lang/language_handler.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit();
}

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['id_calendar']) || !isset($_POST['calendar_name'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit();
}

// Obtener los datos del POST
$id_calendar = (int)$_POST['id_calendar'];
$calendar_name = trim($_POST['calendar_name']);

// Validar que el ID del calendario sea válido
if ($id_calendar <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de calendario inválido']);
    exit();
}

// Verificar que el calendario existe y el usuario tiene acceso
require_once 'db/functions.php';
$database = new Database();
$connection = $database->connection();

$id_user = $_SESSION['id_user'];
$is_superadmin = isSuperAdmin($id_user);

if ($is_superadmin) {
    // Para superadmins: verificar que el calendario existe
    $sql = "SELECT id_calendar_companies, calendar_name FROM calendar_companies WHERE id_calendar_companies = ? AND is_active = 1";
    $stmt = $connection->prepare($sql);
    $stmt->execute([$id_calendar]);
    $calendar = $stmt->fetch();
} else {
    // Para usuarios normales: verificar que el calendario pertenece a su empresa
    $empresa = obtenerEmpresaUsuario($id_user);
    if ($empresa && isset($empresa['id_company'])) {
        $sql = "SELECT cc.id_calendar_companies, cc.calendar_name 
                FROM calendar_companies cc 
                JOIN companies c ON cc.id_company = c.id_company 
                WHERE cc.id_calendar_companies = ? AND cc.id_company = ? AND cc.is_active = 1";
        $stmt = $connection->prepare($sql);
        $stmt->execute([$id_calendar, $empresa['id_company']]);
        $calendar = $stmt->fetch();
    } else {
        $calendar = null;
    }
}

if (!$calendar) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado al calendario']);
    exit();
}

// Actualizar la sesión
$_SESSION['id_calendar'] = $id_calendar;
$_SESSION['calendar_name'] = $calendar_name;

// Guardar la sesión
session_write_close();

// Responder con éxito
echo json_encode([
    'success' => true,
    'id_calendar' => $id_calendar,
    'calendar_name' => $calendar_name,
    'message' => 'Calendario actualizado correctamente'
]);
?>
