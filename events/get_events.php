<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../db/functions.php';

$database = new Database();
$connection = $database->connection();

$id_user = $_SESSION['id_user'] ?? null;
$id_calendar = isset($_GET['id_calendar']) ? (int)$_GET['id_calendar'] : null;

if (!$id_user || !$id_calendar) {
    echo json_encode(['error' => 'Faltan parámetros']);
    exit;
}

// Verificar si el usuario es superadmin
$is_superadmin = isSuperAdmin($id_user);
$is_company_admin = user_has_permission($id_user, 'manage_companies');

// Construir query según el rol
if ($is_superadmin) {
    // Para superadmins: obtener TODOS los eventos del calendario seleccionado
    $sql = "SELECT c.id_event, c.title, c.description, c.start_date, c.end_date, c.colour, c.id_user
            FROM calendar c
            WHERE c.id_calendar = :id_calendar";
    $params = [':id_calendar' => $id_calendar];
    
} elseif ($is_company_admin) {
    // Para admins de empresa: obtener todos los eventos del calendario de su empresa
    $empresa = obtenerEmpresaUsuario($id_user);
    if ($empresa && isset($empresa['id_company'])) {
        $sql = "SELECT c.id_event, c.title, c.description, c.start_date, c.end_date, c.colour, c.id_user
                FROM calendar c
                JOIN calendar_companies cc ON c.id_calendar = cc.id_calendar_companies
                WHERE cc.id_calendar_companies = :id_calendar 
                AND cc.id_company = :id_company";
        $params = [':id_calendar' => $id_calendar, ':id_company' => $empresa['id_company']];
    } else {
        $sql = "SELECT id_event, title, description, start_date, end_date, colour, id_user 
                FROM calendar 
                WHERE id_user = :id_user AND id_calendar = :id_calendar";
        $params = [':id_user' => $id_user, ':id_calendar' => $id_calendar];
    }
    
} else {
    // Para usuarios normales: obtener solo SUS eventos del calendario
    $sql = "SELECT id_event, title, description, start_date, end_date, colour, id_user 
            FROM calendar 
            WHERE id_user = :id_user AND id_calendar = :id_calendar";
    $params = [':id_user' => $id_user, ':id_calendar' => $id_calendar];
}

$stmt = $connection->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$events = [];
foreach ($rows as $row) {
    $events[] = [
        'id' => $row['id_event'],
        'title' => $row['title'],
        'description' => $row['description'],
        'start' => $row['start_date'],
        'end' => $row['end_date'],
        'backgroundColor' => $row['colour'],
    ];
}


    echo json_encode($events);
