<?php
/**
 * Acciones AJAX para el Menu Creator
 */

session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../db/functions.php';

// Verificar que sea superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit();
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener datos JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'add_to_menu':
            addToMenu($pdo, $input);
            break;
            
        case 'update_order':
            updateMenuOrder($pdo, $input);
            break;
            
        case 'toggle_visibility':
            toggleVisibility($pdo, $input);
            break;
            
        case 'remove_from_menu':
            removeFromMenu($pdo, $input);
            break;
            
        case 'add_new_functionality':
            addNewFunctionality($pdo, $input);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (PDOException $e) {
    error_log("Error en menu_creator_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}

/**
 * Agregar funcionalidad al menú de un rol
 */
function addToMenu($pdo, $input) {
    $functionality_key = $input['functionality_key'] ?? '';
    $role_type = $input['role_type'] ?? '';
    
    if (empty($functionality_key) || empty($role_type)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM role_menus WHERE role_type = ? AND functionality_key = ?");
    $stmt->execute([$role_type, $functionality_key]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'La funcionalidad ya está en el menú']);
        return;
    }
    
    // Obtener el siguiente orden
    $stmt = $pdo->prepare("SELECT MAX(menu_order) as max_order FROM role_menus WHERE role_type = ?");
    $stmt->execute([$role_type]);
    $result = $stmt->fetch();
    $next_order = ($result['max_order'] ?? 0) + 1;
    
    // Insertar en el menú
    $stmt = $pdo->prepare("INSERT INTO role_menus (role_type, functionality_key, is_visible, menu_order) VALUES (?, ?, 1, ?)");
    $stmt->execute([$role_type, $functionality_key, $next_order]);
    
    // Asignar permisos automáticamente si está habilitado
    assignAutoPermissions($pdo, $functionality_key, $role_type);
    
    echo json_encode(['success' => true, 'message' => 'Funcionalidad agregada al menú']);
}

/**
 * Actualizar orden del menú
 */
function updateMenuOrder($pdo, $input) {
    $role_type = $input['role_type'] ?? '';
    $items = $input['items'] ?? [];
    
    if (empty($role_type) || empty($items)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE role_menus SET menu_order = ? WHERE id = ?");
    
    foreach ($items as $item) {
        $stmt->execute([$item['order'], $item['id']]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
}

/**
 * Alternar visibilidad de un elemento del menú
 */
function toggleVisibility($pdo, $input) {
    $menu_id = $input['menu_id'] ?? 0;
    $is_visible = $input['is_visible'] ?? 0;
    
    if (!$menu_id) {
        echo json_encode(['success' => false, 'message' => 'ID de menú requerido']);
        return;
    }
    
    $stmt = $pdo->prepare("UPDATE role_menus SET is_visible = ? WHERE id = ?");
    $stmt->execute([$is_visible, $menu_id]);
    
    echo json_encode(['success' => true, 'message' => 'Visibilidad actualizada']);
}

/**
 * Remover elemento del menú
 */
function removeFromMenu($pdo, $input) {
    $menu_id = $input['menu_id'] ?? 0;
    
    if (!$menu_id) {
        echo json_encode(['success' => false, 'message' => 'ID de menú requerido']);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM role_menus WHERE id = ?");
    $stmt->execute([$menu_id]);
    
    echo json_encode(['success' => true, 'message' => 'Elemento eliminado del menú']);
}

/**
 * Agregar nueva funcionalidad al sistema
 */
function addNewFunctionality($pdo, $input) {
    $functionality_key = $input['functionality_key'] ?? '';
    $functionality_name = $input['functionality_name'] ?? '';
    $icon = $input['icon'] ?? 'fas fa-cog';
    $url = $input['url'] ?? '';
    $description = $input['description'] ?? '';
    
    if (empty($functionality_key) || empty($functionality_name) || empty($url)) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        return;
    }
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM system_functionalities WHERE functionality_key = ?");
    $stmt->execute([$functionality_key]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'La funcionalidad ya existe']);
        return;
    }
    
    // Insertar nueva funcionalidad
    $stmt = $pdo->prepare("INSERT INTO system_functionalities (functionality_key, functionality_name, icon, url, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$functionality_key, $functionality_name, $icon, $url, $description]);
    
    // Crear permisos automáticos
    createAutoPermissions($pdo, $functionality_key, $functionality_name);
    
    echo json_encode(['success' => true, 'message' => 'Nueva funcionalidad creada']);
}

/**
 * Asignar permisos automáticamente
 */
function assignAutoPermissions($pdo, $functionality_key, $role_type) {
    // Obtener permisos automáticos para esta funcionalidad
    $stmt = $pdo->prepare("SELECT permission_name FROM auto_permissions WHERE functionality_key = ?");
    $stmt->execute([$functionality_key]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($permissions)) return;
    
    // Verificar si existe la tabla de permisos de usuario
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_permissions'");
    if (!$stmt->fetch()) {
        // Si no existe, crear tabla básica
        $sql = "CREATE TABLE IF NOT EXISTS `user_permissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `permission_name` varchar(100) NOT NULL,
            `granted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `user_permission` (`user_id`, `permission_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $pdo->exec($sql);
    }
    
    // Obtener usuarios con este rol
    $stmt = $pdo->prepare("
        SELECT DISTINCT cu.id_user 
        FROM company_users cu 
        WHERE cu.role = ? AND cu.status = 'active'
    ");
    $stmt->execute([$role_type]);
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Asignar permisos a cada usuario
    $stmt = $pdo->prepare("INSERT IGNORE INTO user_permissions (user_id, permission_name) VALUES (?, ?)");
    
    foreach ($users as $user_id) {
        foreach ($permissions as $permission) {
            $stmt->execute([$user_id, $permission]);
        }
    }
}

/**
 * Crear permisos automáticos para una nueva funcionalidad
 */
function createAutoPermissions($pdo, $functionality_key, $functionality_name) {
    $permissions = [
        ['view_' . $functionality_key, 'Ver ' . $functionality_name],
        ['manage_' . $functionality_key, 'Gestionar ' . $functionality_name]
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO auto_permissions (functionality_key, permission_name, permission_description) VALUES (?, ?, ?)");
    
    foreach ($permissions as $perm) {
        $stmt->execute([$functionality_key, $perm[0], $perm[1]]);
    }
}
?>
