<?php
/**
 * Renderizador Simple de Menús
 * Basado en módulos y roles
 */

class SimpleMenuRenderer {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener el rol del usuario
     */
    public function getUserRole($userId) {
        try {
            $sql = "SELECT r.name as role_key 
                    FROM roles r 
                    JOIN user_roles ur ON r.id_role = ur.id_role 
                    WHERE ur.id_user = ? 
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['role_key'] : 'user'; // Default a 'user'
        } catch (PDOException $e) {
            return 'user'; // Default en caso de error
        }
    }
    
    /**
     * Obtener módulos disponibles para un rol
     */
    public function getModulesForRole($roleKey) {
        try {
            $sql = "SELECT m.*, p.can_access, p.can_edit, p.can_delete, p.menu_order
                    FROM system_modules_simple m
                    JOIN module_role_permissions_simple p ON m.module_key = p.module_key
                    WHERE p.role_key = ? AND p.can_access = 1 AND m.is_active = 1
                    ORDER BY p.menu_order, m.menu_order";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$roleKey]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Renderizar menú como lista vertical
     */
    public function renderVerticalMenu($userId, $options = []) {
        $roleKey = $this->getUserRole($userId);
        $modules = $this->getModulesForRole($roleKey);
        
        $menuClass = $options['menu_class'] ?? 'nav flex-column';
        $itemClass = $options['item_class'] ?? 'nav-item';
        $linkClass = $options['link_class'] ?? 'nav-link';
        $currentUrl = $options['current_url'] ?? $_SERVER['REQUEST_URI'];
        
        $html = "<ul class=\"$menuClass\">\n";
        
        foreach ($modules as $module) {
            $isActive = ($currentUrl === $module['url']) ? ' active' : '';
            $icon = $module['icon'] ? "<i class=\"{$module['icon']}\"></i> " : '';
            
            // Construir URL completa usando la configuración
            $fullUrl = $this->buildFullUrl($module['url']);
            
            $html .= "  <li class=\"$itemClass\">\n";
            $html .= "    <a class=\"$linkClass$isActive\" href=\"$fullUrl\" title=\"{$module['description']}\">\n";
            $html .= "      $icon{$module['module_name']}\n";
            $html .= "    </a>\n";
            $html .= "  </li>\n";
        }
        
        $html .= "</ul>";
        
        return $html;
    }
    
    /**
     * Renderizar menú como navbar horizontal
     */
    public function renderNavbarMenu($userId, $options = []) {
        $roleKey = $this->getUserRole($userId);
        $modules = $this->getModulesForRole($roleKey);
        
        $menuClass = $options['menu_class'] ?? 'navbar-nav';
        $itemClass = $options['item_class'] ?? 'nav-item';
        $linkClass = $options['link_class'] ?? 'nav-link';
        $currentUrl = $options['current_url'] ?? $_SERVER['REQUEST_URI'];
        
        $html = "<ul class=\"$menuClass\">\n";
        
        foreach ($modules as $module) {
            $isActive = ($currentUrl === $module['url']) ? ' active' : '';
            $icon = $module['icon'] ? "<i class=\"{$module['icon']}\"></i> " : '';
            
            // Construir URL completa usando la configuración
            $fullUrl = $this->buildFullUrl($module['url']);
            
            $html .= "  <li class=\"$itemClass\">\n";
            $html .= "    <a class=\"$linkClass$isActive\" href=\"$fullUrl\" title=\"{$module['description']}\">\n";
            $html .= "      $icon{$module['module_name']}\n";
            $html .= "    </a>\n";
            $html .= "  </li>\n";
        }
        
        $html .= "</ul>";
        
        return $html;
    }
    
    /**
     * Renderizar menú como cards
     */
    public function renderCardMenu($userId, $options = []) {
        $roleKey = $this->getUserRole($userId);
        $modules = $this->getModulesForRole($roleKey);
        
        $containerClass = $options['container_class'] ?? 'row';
        $cardClass = $options['card_class'] ?? 'col-md-3 mb-3';
        $currentUrl = $options['current_url'] ?? $_SERVER['REQUEST_URI'];
        
        $html = "<div class=\"$containerClass\">\n";
        
        foreach ($modules as $module) {
            $isActive = ($currentUrl === $module['url']) ? ' border-primary' : '';
            $icon = $module['icon'] ? "<i class=\"{$module['icon']} fa-2x mb-2\"></i>" : '';
            
            // Construir URL completa usando la configuración
            $fullUrl = $this->buildFullUrl($module['url']);
            
            $html .= "  <div class=\"$cardClass\">\n";
            $html .= "    <div class=\"card h-100$isActive\">\n";
            $html .= "      <div class=\"card-body text-center\">\n";
            $html .= "        <div class=\"text-primary mb-2\">$icon</div>\n";
            $html .= "        <div class=\"card-title\">{$module['module_name']}</div>\n";
            $html .= "        <p class=\"card-text small\">{$module['description']}</p>\n";
            $html .= "        <a href=\"$fullUrl\" class=\"btn btn-outline-primary btn-sm\">Acceder</a>\n";
            $html .= "      </div>\n";
            $html .= "    </div>\n";
            $html .= "  </div>\n";
        }
        
        $html .= "</div>";
        
        return $html;
    }
    
    /**
     * Verificar si usuario puede acceder a un módulo
     */
    public function canAccessModule($userId, $moduleKey) {
        $roleKey = $this->getUserRole($userId);
        
        try {
            $sql = "SELECT can_access FROM module_role_permissions_simple 
                    WHERE module_key = ? AND role_key = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$moduleKey, $roleKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['can_access'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Construir URL completa usando la configuración
     */
    private function buildFullUrl($relativeUrl) {
        // Si ya es una URL completa, devolverla tal como está
        if (filter_var($relativeUrl, FILTER_VALIDATE_URL)) {
            return $relativeUrl;
        }
        
        // Si es relativa, construir la URL completa
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Obtener el path base del proyecto
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($scriptPath);
        
        // Si estamos en la raíz, usar solo el host
        if ($basePath === '/') {
            return $protocol . '://' . $host . $relativeUrl;
        }
        
        // Si no, incluir el path base
        return $protocol . '://' . $host . $basePath . $relativeUrl;
    }
    
    /**
     * Verificar si usuario puede editar en un módulo
     */
    public function canEditModule($userId, $moduleKey) {
        $roleKey = $this->getUserRole($userId);
        
        try {
            $sql = "SELECT can_edit FROM module_role_permissions_simple 
                    WHERE module_key = ? AND role_key = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$moduleKey, $roleKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['can_edit'] == 1;
        } catch (PDOException $e) {
            return false;
        }
    }
}

// Funciones helper para uso rápido
function render_simple_menu($userId, $type = 'vertical', $options = []) {
    global $pdo;
    
    // Si no hay conexión PDO, crear una
    if (!$pdo) {
        require_once __DIR__ . '/../db/connection.php';
        try {
            $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return "<div class='alert alert-danger'>Error de conexión a la base de datos</div>";
        }
    }
    
    $renderer = new SimpleMenuRenderer($pdo);
    
    switch ($type) {
        case 'navbar':
            return $renderer->renderNavbarMenu($userId, $options);
        case 'cards':
            return $renderer->renderCardMenu($userId, $options);
        case 'vertical':
        default:
            return $renderer->renderVerticalMenu($userId, $options);
    }
}

function can_access_module($userId, $moduleKey) {
    global $pdo;
    
    // Si no hay conexión PDO, crear una
    if (!$pdo) {
        require_once __DIR__ . '/../db/connection.php';
        try {
            $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    $renderer = new SimpleMenuRenderer($pdo);
    return $renderer->canAccessModule($userId, $moduleKey);
}

function can_edit_module($userId, $moduleKey) {
    global $pdo;
    
    // Si no hay conexión PDO, crear una
    if (!$pdo) {
        require_once __DIR__ . '/../db/connection.php';
        try {
            $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
            $renderer = new SimpleMenuRenderer($pdo);
            return $renderer->canEditModule($userId, $moduleKey);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    $renderer = new SimpleMenuRenderer($pdo);
    return $renderer->canEditModule($userId, $moduleKey);
}
?>
