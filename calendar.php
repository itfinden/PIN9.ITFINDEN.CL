<?php
session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once 'lang/language_handler.php';

if (isset($_SESSION['user'])) {
} else {
    header('Location: main.php');
    die();
}

require_once('db/functions.php');

$database = new Database();
$connection = $database->connection();

$id_user = $_SESSION['id_user']; // Asegura que id_user está definido

// Verificar si el usuario es superadmin
$is_superadmin = isSuperAdmin($id_user);

// Verificar si el usuario es admin de empresa
$is_company_admin = user_has_permission($id_user, 'manage_companies');

// Obtener calendarios disponibles para establecer el calendario activo por defecto
$calendars = [];

if ($is_superadmin) {
    // Para superadmins: obtener todos los calendarios de todas las empresas
    $sql = "
        SELECT 
            cc.id_calendar_companies,
            cc.calendar_name,
            cc.colour,
            cc.is_default,
            cc.is_active,
            c.id_company,
            c.company_name
        FROM calendar_companies cc
        JOIN companies c ON cc.id_company = c.id_company
        WHERE cc.is_active = 1
        ORDER BY c.company_name ASC, cc.calendar_name ASC
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $calendars = $stmt->fetchAll();
    
} else {
    // Para usuarios normales: obtener solo calendarios de su empresa
    $empresa = obtenerEmpresaUsuario($id_user);
    
    if ($empresa && isset($empresa['id_company'])) {
        $sql = "
            SELECT 
                cc.id_calendar_companies,
                cc.calendar_name,
                cc.colour,
                cc.is_default,
                cc.is_active,
                c.id_company,
                c.company_name
            FROM calendar_companies cc
            JOIN companies c ON cc.id_company = c.id_company
            WHERE cc.id_company = ? AND cc.is_active = 1
            ORDER BY cc.calendar_name ASC
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$empresa['id_company']]);
        $calendars = $stmt->fetchAll();
    }
}

// Establecer el calendario activo por defecto en la sesión
if (!empty($calendars)) {
    // Buscar primero un calendario marcado como default
    $default_calendar = null;
    foreach ($calendars as $cal) {
        if ($cal['is_default'] == 1) {
            $default_calendar = $cal;
            break;
        }
    }
    
    // Si no hay default, usar el primero
    if (!$default_calendar) {
        $default_calendar = $calendars[0];
    }
    
    // Establecer en la sesión
    $_SESSION['id_calendar'] = $default_calendar['id_calendar_companies'];
    $_SESSION['calendar_name'] = $default_calendar['calendar_name'];
} else {
    // Si no hay calendarios, establecer un valor por defecto
    $_SESSION['id_calendar'] = null;
    $_SESSION['calendar_name'] = null;
}

if ($is_superadmin) {
    // Para superadmins: obtener TODOS los eventos de TODAS las empresas
    $sql = "
        SELECT c.id_event, c.title, c.description, c.start_date, c.end_date, c.colour,
               cc.calendar_name, comp.company_name, c.id_user
        FROM calendar c
        JOIN calendar_companies cc ON c.id_calendar = cc.id_calendar_companies
        JOIN companies comp ON cc.id_company = comp.id_company
        ORDER BY c.start_date DESC
    ";
    
    $statement = $connection->prepare($sql);
    $statement->execute();
    $events = $statement->fetchAll();
    
} elseif ($is_company_admin) {
    // Para admins de empresa: obtener todos los eventos de SU empresa
    $empresa = obtenerEmpresaUsuario($id_user);
    if ($empresa && isset($empresa['id_company'])) {
        $sql = "
            SELECT c.id_event, c.title, c.description, c.start_date, c.end_date, c.colour,
                   cc.calendar_name, comp.company_name, c.id_user
            FROM calendar c
            JOIN calendar_companies cc ON c.id_calendar = cc.id_calendar_companies
            JOIN companies comp ON cc.id_company = comp.id_company
            WHERE comp.id_company = ?
            ORDER BY c.start_date DESC
        ";
        
        $statement = $connection->prepare($sql);
        $statement->execute([$empresa['id_company']]);
        $events = $statement->fetchAll();
    } else {
        $events = [];
    }
    
} else {
    // Para usuarios normales: obtener solo SUS eventos
    $sql = "
        SELECT id_event, title, description, start_date, end_date, colour, id_user
        FROM calendar 
        WHERE id_user = ?
        ORDER BY start_date DESC
    ";
    
    $statement = $connection->prepare($sql);
    $statement->execute([$id_user]);
    $events = $statement->fetchAll();
}

// Pasar variables a la vista
$view_data = [
    'calendars' => $calendars,
    'is_superadmin' => $is_superadmin,
    'id_user' => $id_user,
    'events' => $events ?? []
];

require_once 'views/calendar.view.php';
?>
