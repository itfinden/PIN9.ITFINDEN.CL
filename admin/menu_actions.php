<?php
/**
 * Archivo de acciones AJAX para el administrador de menús dinámicos
 * Maneja todas las operaciones CRUD de menús, elementos y configuraciones
 */

session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../db/functions.php';

// Verificar si el usuario es superadmin
if (!isSuperAdmin($_SESSION['id_user'] ?? 0)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener la acción solicitada
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_menu_items':
            getMenuItems($pdo);
            break;
            
        case 'get_available_items':
            getAvailableItems($pdo);
            break;
            
        case 'add_menu_item':
            addMenuItem($pdo);
            break;
            
        case 'update_menu_item':
            updateMenuItem($pdo);
            break;
            
        case 'remove_menu_item':
            removeMenuItem($pdo);
            break;
            
        case 'update_menu_order':
            updateMenuOrder($pdo);
            break;
            
        case 'export_menu':
            exportMenu($pdo);
            break;
            
        case 'generate_preview':
            generatePreview($pdo);
            break;
            
        case 'get_company_menu_config':
            getCompanyMenuConfig($pdo);
            break;
            
        case 'toggle_company_menu':
            toggleCompanyMenu($pdo);
            break;
            
        case 'update_company_menu_title':
            updateCompanyMenuTitle($pdo);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (PDOException $e) {
    error_log("Error en menu_actions.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos']);
}

/**
 * Obtener elementos de un menú específico
 */
function getMenuItems($pdo) {
    $menuId = $_GET['menu_id'] ?? 0;
    
    $sql = "SELECT * FROM dynamic_menu_items WHERE id_menu = ? ORDER BY menu_order ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menuId]);
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'items' => $items]);
}

/**
 * Obtener elementos disponibles para agregar a menús
 */
function getAvailableItems($pdo) {
    $moduleFilter = $_GET['module'] ?? '';
    
    $sql = "SELECT 
                p.name as item_key,
                p.Titulo as title,
                p.description,
                p.Url as url,
                p.icon,
                p.icon_color,
                p.name as permission_required,
                NULL as module_name
            FROM permissions p 
            WHERE p.Url IS NOT NULL AND p.Url != ''";
    
    $params = [];
    
    if ($moduleFilter) {
        $sql .= " AND p.name LIKE ?";
        $params[] = "%$moduleFilter%";
    }
    
    $sql .= " ORDER BY p.Titulo ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Agregar elementos de módulos
    $sql_modules = "SELECT 
                        module_key as item_key,
                        display_name as title,
                        description,
                        user_url as url,
                        icon,
                        '#007bff' as icon_color,
                        'admin_panel' as permission_required,
                        module_key as module_name
                    FROM system_modules 
                    WHERE is_active = 1";
    
    if ($moduleFilter) {
        $sql_modules .= " AND module_key = ?";
        $stmt_modules = $pdo->prepare($sql_modules);
        $stmt_modules->execute([$moduleFilter]);
    } else {
        $stmt_modules = $pdo->prepare($sql_modules);
        $stmt_modules->execute();
    }
    
    $moduleItems = $stmt_modules->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar elementos
    $allItems = array_merge($items, $moduleItems);
    
    echo json_encode(['success' => true, 'items' => $allItems]);
}

/**
 * Agregar elemento a un menú
 */
function addMenuItem($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $menuId = $input['menu_id'] ?? 0;
    $itemData = $input['item_data'] ?? [];
    
    // Verificar si el elemento ya existe en el menú
    $sql_check = "SELECT COUNT(*) FROM dynamic_menu_items WHERE id_menu = ? AND item_key = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$menuId, $itemData['item_key']]);
    
    if ($stmt_check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'El elemento ya existe en este menú']);
        return;
    }
    
    // Obtener el siguiente orden
    $sql_order = "SELECT COALESCE(MAX(menu_order), 0) + 1 FROM dynamic_menu_items WHERE id_menu = ?";
    $stmt_order = $pdo->prepare($sql_order);
    $stmt_order->execute([$menuId]);
    $nextOrder = $stmt_order->fetchColumn();
    
    // Insertar el elemento
    $sql = "INSERT INTO dynamic_menu_items (
                id_menu, item_key, title, description, url, icon, icon_color, 
                permission_required, module_name, menu_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        $menuId,
        $itemData['item_key'],
        $itemData['title'],
        $itemData['description'] ?? '',
        $itemData['url'] ?? '',
        $itemData['icon'] ?? 'fas fa-link',
        $itemData['icon_color'] ?? '#6c757d',
        $itemData['permission_required'] ?? null,
        $itemData['module_name'] ?? null,
        $nextOrder
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Elemento agregado al menú']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar elemento']);
    }
}

/**
 * Actualizar elemento de menú
 */
function updateMenuItem($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $itemId = $input['item_id'] ?? 0;
    $title = $input['title'] ?? '';
    $description = $input['description'] ?? '';
    $url = $input['url'] ?? '';
    $icon = $input['icon'] ?? 'fas fa-link';
    $iconColor = $input['icon_color'] ?? '#6c757d';
    $permission = $input['permission_required'] ?? null;
    $module = $input['module_name'] ?? null;
    
    $sql = "UPDATE dynamic_menu_items SET 
                title = ?, description = ?, url = ?, icon = ?, icon_color = ?,
                permission_required = ?, module_name = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id_menu_item = ?";
    
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([
        $title, $description, $url, $icon, $iconColor, $permission, $module, $itemId
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Elemento actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar elemento']);
    }
}

/**
 * Remover elemento de menú
 */
function removeMenuItem($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $itemId = $input['item_id'] ?? 0;
    
    $sql = "DELETE FROM dynamic_menu_items WHERE id_menu_item = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([$itemId]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Elemento eliminado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar elemento']);
    }
}

/**
 * Actualizar orden de elementos del menú
 */
function updateMenuOrder($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $items = $input['items'] ?? [];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($items as $item) {
            $sql = "UPDATE dynamic_menu_items SET menu_order = ? WHERE id_menu_item = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$item['order'], $item['id_menu_item']]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Orden actualizado']);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error al actualizar orden']);
    }
}

/**
 * Exportar configuración de menú
 */
function exportMenu($pdo) {
    $menuId = $_GET['menu_id'] ?? 0;
    
    // Obtener información del menú
    $sql_menu = "SELECT * FROM dynamic_menus WHERE id_menu = ?";
    $stmt_menu = $pdo->prepare($sql_menu);
    $stmt_menu->execute([$menuId]);
    $menu = $stmt_menu->fetch(PDO::FETCH_ASSOC);
    
    // Obtener elementos del menú
    $sql_items = "SELECT * FROM dynamic_menu_items WHERE id_menu = ? ORDER BY menu_order ASC";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$menuId]);
    $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [
        'menu' => $menu,
        'items' => $items,
        'exported_at' => date('Y-m-d H:i:s'),
        'version' => '1.0'
    ];
    
    // Configurar headers para descarga
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="menu_config_' . $menuId . '.json"');
    header('Content-Length: ' . strlen(json_encode($config)));
    
    echo json_encode($config, JSON_PRETTY_PRINT);
}

/**
 * Generar vista previa del menú
 */
function generatePreview($pdo) {
    $menuId = $_GET['menu_id'] ?? 0;
    $roleId = $_GET['role_id'] ?? 0;
    
    // Obtener elementos del menú
    $sql = "SELECT * FROM dynamic_menu_items WHERE id_menu = ? ORDER BY menu_order ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menuId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtrar por permisos del rol (simplificado)
    $filteredItems = [];
    foreach ($items as $item) {
        if (empty($item['permission_required'])) {
            $filteredItems[] = $item;
        } else {
            // Aquí podrías implementar lógica más compleja de verificación de permisos
            $filteredItems[] = $item;
        }
    }
    
    echo json_encode(['success' => true, 'menu' => $filteredItems]);
}

/**
 * Obtener configuración de menús por empresa
 */
function getCompanyMenuConfig($pdo) {
    $companyId = $_GET['company_id'] ?? 0;
    
    // Obtener menús disponibles
    $sql = "SELECT 
                m.id_menu,
                m.menu_name,
                COALESCE(cm.is_active, 1) as is_active,
                cm.custom_title,
                cm.custom_description
            FROM dynamic_menus m
            LEFT JOIN company_menus cm ON m.id_menu = cm.id_menu AND cm.id_company = ?
            WHERE m.is_active = 1
            ORDER BY m.menu_name";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$companyId]);
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $config = [
        'company_id' => $companyId,
        'menus' => $menus
    ];
    
    echo json_encode(['success' => true, 'config' => $config]);
}

/**
 * Alternar menú de empresa
 */
function toggleCompanyMenu($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $companyId = $input['company_id'] ?? 0;
    $menuId = $input['menu_id'] ?? 0;
    $isActive = $input['is_active'] ?? false;
    
    // Verificar si ya existe la configuración
    $sql_check = "SELECT id_company_menu FROM company_menus WHERE id_company = ? AND id_menu = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$companyId, $menuId]);
    
    if ($stmt_check->fetchColumn()) {
        // Actualizar existente
        $sql = "UPDATE company_menus SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id_company = ? AND id_menu = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$isActive ? 1 : 0, $companyId, $menuId]);
    } else {
        // Crear nuevo
        $sql = "INSERT INTO company_menus (id_company, id_menu, is_active) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$companyId, $menuId, $isActive ? 1 : 0]);
    }
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Configuración actualizada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar configuración']);
    }
}

/**
 * Actualizar título personalizado del menú de empresa
 */
function updateCompanyMenuTitle($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $companyId = $input['company_id'] ?? 0;
    $menuId = $input['menu_id'] ?? 0;
    $customTitle = $input['custom_title'] ?? '';
    
    // Verificar si ya existe la configuración
    $sql_check = "SELECT id_company_menu FROM company_menus WHERE id_company = ? AND id_menu = ?";
    $stmt_check = $pdo->prepare($sql_check);
    $stmt_check->execute([$companyId, $menuId]);
    
    if ($stmt_check->fetchColumn()) {
        // Actualizar existente
        $sql = "UPDATE company_menus SET custom_title = ?, updated_at = CURRENT_TIMESTAMP WHERE id_company = ? AND id_menu = ?";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$customTitle, $companyId, $menuId]);
    } else {
        // Crear nuevo
        $sql = "INSERT INTO company_menus (id_company, id_menu, custom_title) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([$companyId, $menuId, $customTitle]);
    }
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Título actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar título']);
    }
}
?>
