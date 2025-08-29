<?php
/**
 * Configuración del menú de administración
 * Define las rutas, permisos y estructura del menú de forma dinámica
 * Las rutas se leen desde la tabla permissions de la base de datos
 */

// Configuración del menú principal (sin rutas hardcodeadas)
$menu_config = [
    'sistema' => [
        'title' => 'Sistema',
        'icon' => 'fas fa-server',
        'subtitle' => 'Administración del Sistema',
        'permission_required' => ['admin_panel', 'manage_companies', 'admin_services', 'audit_logs', 'audit_logs_advanced', 'manage_permissions', 'manage_languages', 'manage_superadmins'],
        'items' => [
            'admin_panel' => [
                'title' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'admin_panel',
                'description' => 'Panel principal'
            ],
            'manage_companies' => [
                'title' => 'Empresas',
                'icon' => 'fas fa-building',
                'permission' => 'manage_companies',
                'description' => 'Gestionar empresas'
            ],
            'admin_services' => [
                'title' => 'Servicios',
                'icon' => 'fas fa-cogs',
                'permission' => 'admin_services',
                'description' => 'Administrar servicios'
            ],
            'audit_logs' => [
                'title' => 'Logs',
                'icon' => 'fas fa-history',
                'permission' => 'audit_logs',
                'description' => 'Auditoría del sistema'
            ],
            'audit_logs_advanced' => [
                'title' => 'Logs Avanzados',
                'icon' => 'fas fa-chart-line',
                'permission' => 'audit_logs_advanced',
                'description' => 'Logs avanzados del sistema'
            ],
            'manage_permissions' => [
                'title' => 'Permisos',
                'icon' => 'fas fa-key',
                'permission' => 'manage_permissions',
                'description' => 'Gestión de permisos por rol'
            ],
            'manage_languages' => [
                'title' => 'Idiomas',
                'icon' => 'fas fa-globe',
                'permission' => 'manage_languages',
                'description' => 'Gestión de idiomas del sistema'
            ],
            'manage_superadmins' => [
                'title' => 'SuperAdmins',
                'icon' => 'fas fa-crown',
                'permission' => 'manage_superadmins',
                'description' => 'Gestión de superadministradores'
            ]
        ]
    ],
    'empresa' => [
        'title' => 'Empresa',
        'icon' => 'fas fa-building',
        'subtitle' => 'Gestión de Empresa',
        'permission_required' => ['manage_services', 'new_service', 'invite_users', 'company_settings', 'edit_service', 'delete_service', 'manage_users'],
        'items' => [
            'manage_services' => [
                'title' => 'Mis Servicios',
                'icon' => 'fas fa-list',
                'permission' => 'manage_services',
                'description' => 'Gestionar servicios'
            ],
            'new_service' => [
                'title' => 'Nuevo Servicio',
                'icon' => 'fas fa-plus',
                'permission' => 'new_service',
                'description' => 'Crear servicio'
            ],
            'edit_service' => [
                'title' => 'Editar Servicio',
                'icon' => 'fas fa-edit',
                'permission' => 'edit_service',
                'description' => 'Editar servicios existentes'
            ],
            'delete_service' => [
                'title' => 'Eliminar Servicio',
                'icon' => 'fas fa-trash',
                'permission' => 'delete_service',
                'description' => 'Eliminar servicios'
            ],
            'invite_users' => [
                'title' => 'Invitar Usuarios',
                'icon' => 'fas fa-user-plus',
                'permission' => 'invite_users',
                'description' => 'Agregar usuarios'
            ],
            'company_settings' => [
                'title' => 'Configuración',
                'icon' => 'fas fa-cog',
                'permission' => 'company_settings',
                'description' => 'Ajustes de empresa'
            ],
            'manage_users' => [
                'title' => 'Usuarios',
                'icon' => 'fas fa-users',
                'permission' => 'manage_users',
                'description' => 'Gestión de usuarios de empresa'
            ]
        ]
    ],
    'usuario' => [
        'title' => 'Usuario',
        'icon' => 'fas fa-user',
        'subtitle' => 'Gestión Personal',
        'permission_required' => ['edit_profile', 'view_tickets', 'manage_tickets', 'edit_user', 'edit_user_roles'],
        'items' => [
            'edit_profile' => [
                'title' => 'Mi Perfil',
                'icon' => 'fas fa-user-edit',
                'permission' => 'edit_profile',
                'description' => 'Editar información'
            ],
            'view_tickets' => [
                'title' => 'Mis Tickets',
                'icon' => 'fas fa-ticket-alt',
                'permission' => 'view_tickets',
                'description' => 'Ver tickets'
            ],
            'manage_tickets' => [
                'title' => 'Gestión Tickets',
                'icon' => 'fas fa-tasks',
                'permission' => 'manage_tickets',
                'description' => 'Administrar tickets'
            ],
            'edit_user' => [
                'title' => 'Editar Usuario',
                'icon' => 'fas fa-user-cog',
                'permission' => 'edit_user',
                'description' => 'Editar información de usuario'
            ],
            'edit_user_roles' => [
                'title' => 'Editar Roles',
                'icon' => 'fas fa-user-tag',
                'permission' => 'edit_user_roles',
                'description' => 'Editar roles de usuario'
            ]
        ]
    ]
];

/**
 * Función para obtener las rutas desde la base de datos
 */
function getPermissionRoutes() {
    try {
        $db = new Database();
        $pdo = $db->connection();
        
        $sql = "SELECT name, url FROM permissions WHERE url IS NOT NULL AND url != ''";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $routes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $routes[$row['name']] = $row['url'];
        }
        
        return $routes;
    } catch (Exception $e) {
        error_log("Error obteniendo rutas de permisos: " . $e->getMessage());
        return [];
    }
}

/**
 * Función para verificar si un usuario tiene al menos uno de los permisos requeridos
 */
function hasAnyRequiredPermission($required_permissions, $user_permissions) {
    foreach ($required_permissions as $permission) {
        if (in_array($permission, $user_permissions)) {
            return true;
        }
    }
    return false;
}

/**
 * Función para obtener los elementos del menú que el usuario puede ver
 */
function getVisibleMenuItems($menu_config, $user_permissions) {
    $visible_items = [];
    
    // Obtener rutas desde la base de datos
    $permission_routes = getPermissionRoutes();
    
    foreach ($menu_config as $section_key => $section) {
        // Verificar si el usuario tiene al menos un permiso requerido para esta sección
        if (hasAnyRequiredPermission($section['permission_required'], $user_permissions)) {
            $visible_section = $section;
            $visible_section['key'] = $section_key;
            $visible_section['items'] = [];
            
            // Filtrar elementos de la sección
            foreach ($section['items'] as $item_key => $item) {
                if (in_array($item['permission'], $user_permissions)) {
                    $item['key'] = $item_key;
                    
                    // Obtener la ruta desde la base de datos
                    if (isset($permission_routes[$item['permission']])) {
                        $item['url'] = $permission_routes[$item['permission']];
                    } else {
                        // Fallback: usar una ruta por defecto
                        $item['url'] = '#';
                        error_log("Ruta no encontrada para permiso: " . $item['permission']);
                    }
                    
                    $visible_section['items'][$item_key] = $item;
                }
            }
            
            // Solo agregar la sección si tiene elementos visibles
            if (!empty($visible_section['items'])) {
                $visible_items[$section_key] = $visible_section;
            }
        }
    }
    
    return $visible_items;
}

/**
 * Función para generar la URL base del sistema
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Si estamos en la raíz, usar '/'
    if ($path === '/') {
        return $protocol . '://' . $host . '/';
    }
    
    return $protocol . '://' . $host . $path . '/';
}

/**
 * Función para construir URLs completas
 */
function buildUrl($relative_path) {
    // Si la ruta ya es absoluta, devolverla tal como está
    if (strpos($relative_path, 'http') === 0) {
        return $relative_path;
    }
    
    // Si la ruta empieza con '/', es relativa al dominio
    if (strpos($relative_path, '/') === 0) {
        $base_url = getBaseUrl();
        return $base_url . ltrim($relative_path, '/');
    }
    
    // Ruta relativa al directorio actual
    $base_url = getBaseUrl();
    return $base_url . $relative_path;
}

/**
 * Función para obtener información completa de un permiso
 */
function getPermissionInfo($permission_name) {
    try {
        $db = new Database();
        $pdo = $db->connection();
        
        $sql = "SELECT * FROM permissions WHERE name = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$permission_name]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error obteniendo información de permiso: " . $e->getMessage());
        return null;
    }
}

/**
 * Función para obtener todos los permisos con sus rutas
 */
function getAllPermissionsWithRoutes() {
    try {
        $db = new Database();
        $pdo = $db->connection();
        
        $sql = "SELECT * FROM permissions ORDER BY name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $permissions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[] = $row;
        }
        
        return $permissions;
    } catch (Exception $e) {
        error_log("Error obteniendo permisos: " . $e->getMessage());
        return [];
    }
}
?> 