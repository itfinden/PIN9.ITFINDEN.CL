<?php
// Manejar cambio de idioma ANTES de cualquier output
require_once 'lang/language_handler.php';
require_once 'theme_handler.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["id_user"]) || empty($_SESSION["id_user"])) {
    header("Location: login.php");
    exit;
}

require_once "db/functions.php";
require_once "security/check_access.php";

// Verificar permiso para ver el dashboard
verificarPermisoVista($_SESSION["id_user"], 9); // view_dashboard

// Obtener información del usuario y su empresa
$id_user = $_SESSION["id_user"];
$user_info = GET_INFO($id_user);
$empresa = obtenerEmpresaUsuario($id_user);
$is_superadmin = isSuperAdmin($id_user);
$is_company_admin = user_has_permission($id_user, 'manage_companies');

// Obtener estadísticas según el rol del usuario
$database = new Database();
$connection = $database->connection();

// Estadísticas de tareas
$tareas_pendientes = 0;
$proyectos_activos = 0;
$eventos_hoy = 0;
$equipos_activos = 0;

if ($is_superadmin) {
    // Para superadmins: mostrar estadísticas globales
    $sql_tareas = "SELECT COUNT(*) as total FROM tickets WHERE id_status != 4"; // No cerrados
    $sql_proyectos = "SELECT COUNT(*) as total FROM projects WHERE end_date >= CURDATE()";
    $sql_eventos = "SELECT COUNT(*) as total FROM calendar WHERE DATE(start_date) = CURDATE()";
    $sql_equipos = "SELECT COUNT(DISTINCT id_company) as total FROM companies WHERE subscription_status != 'cancelled'";
    
    $stmt = $connection->prepare($sql_tareas);
    $stmt->execute();
    $tareas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $connection->prepare($sql_proyectos);
    $stmt->execute();
    $proyectos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $connection->prepare($sql_eventos);
    $stmt->execute();
    $eventos_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $connection->prepare($sql_equipos);
    $stmt->execute();
    $equipos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
} elseif ($is_company_admin) {
    // Para admins de empresa: mostrar estadísticas de su empresa
    if ($empresa && isset($empresa['id_company'])) {
        $sql_tareas = "SELECT COUNT(*) as total FROM tickets WHERE id_company = ? AND id_status != 4";
        $sql_proyectos = "SELECT COUNT(*) as total FROM projects WHERE end_date >= CURDATE()";
        $sql_eventos = "SELECT COUNT(*) as total FROM calendar c 
                       JOIN calendar_companies cc ON c.id_calendar = cc.id_calendar_companies 
                       WHERE cc.id_company = ? AND DATE(c.start_date) = CURDATE()";
        $sql_equipos = "SELECT COUNT(*) as total FROM company_users WHERE id_company = ? AND status = 1";
        
        $stmt = $connection->prepare($sql_tareas);
        $stmt->execute([$empresa['id_company']]);
        $tareas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $connection->prepare($sql_proyectos);
        $stmt->execute();
        $proyectos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $connection->prepare($sql_eventos);
        $stmt->execute([$empresa['id_company']]);
        $eventos_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $connection->prepare($sql_equipos);
        $stmt->execute([$empresa['id_company']]);
        $equipos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
} else {
    // Para usuarios normales: mostrar solo sus estadísticas
    // Usar consultas más robustas con manejo de errores
    
    // Tickets - intentar tabla directa primero, luego vista
    try {
        $sql_tareas = "SELECT COUNT(*) as total FROM tickets WHERE id_user_creator = ? AND id_status != 4";
        $stmt = $connection->prepare($sql_tareas);
        $stmt->execute([$id_user]);
        $tareas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        try {
            $sql_tareas = "SELECT COUNT(*) as total FROM v_tickets_complete WHERE id_user_creator = ? AND id_status != 4";
            $stmt = $connection->prepare($sql_tareas);
            $stmt->execute([$id_user]);
            $tareas_pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e2) {
            error_log("Error en consulta de tickets para usuario $id_user: " . $e2->getMessage());
            $tareas_pendientes = 0;
        }
    }
    
    // Proyectos
    try {
        $sql_proyectos = "SELECT COUNT(*) as total FROM projects WHERE id_user = ? AND end_date >= CURDATE()";
        $stmt = $connection->prepare($sql_proyectos);
        $stmt->execute([$id_user]);
        $proyectos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error en consulta de proyectos para usuario $id_user: " . $e->getMessage());
        $proyectos_activos = 0;
    }
    
    // Eventos
    try {
        $sql_eventos = "SELECT COUNT(*) as total FROM calendar WHERE id_user = ? AND DATE(start_date) = CURDATE()";
        $stmt = $connection->prepare($sql_eventos);
        $stmt->execute([$id_user]);
        $eventos_hoy = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error en consulta de eventos para usuario $id_user: " . $e->getMessage());
        $eventos_hoy = 0;
    }
    
    // Equipos
    try {
        $sql_equipos = "SELECT COUNT(*) as total FROM company_users WHERE id_user = ? AND status = 1";
        $stmt = $connection->prepare($sql_equipos);
        $stmt->execute([$id_user]);
        $equipos_activos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Error en consulta de equipos para usuario $id_user: " . $e->getMessage());
        $equipos_activos = 0;
    }
}

// Obtener permisos del usuario para mostrar acciones relevantes
$permisos_usuario = obtenerPermisosUsuario($id_user);
$permisos_nombres = array_column($permisos_usuario, 'name');

// Verificar que el usuario esté logueado (verificación adicional)
if (!isset($_SESSION["user"])) {
    header("Location: main.php");
    die();
}

// Preparar el tema antes de incluir la vista
$theme_attributes = applyThemeToHTML();

// Ahora incluir la vista mejorada
require "views/content_enhanced.view.php";
?>
