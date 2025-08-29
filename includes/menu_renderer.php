<?php
/**
 * Renderizador de Menús Dinámicos
 * Permite mostrar menús personalizados basados en permisos, roles y empresa
 */

require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../db/functions.php';

class DynamicMenuRenderer {
    private $pdo;
    private $userId;
    private $companyId;
    private $userRole;
    private $userPermissions;
    
    public function __construct($userId = null) {
        global $hostname, $username, $password, $database;
        
        $this->pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $this->userId = $userId;
        $this->loadUserData();
    }
    
    /**
     * Cargar datos del usuario (empresa, rol, permisos)
     */
    private function loadUserData() {
        if (!$this->userId) return;
        
        try {
            // Obtener información de empresa y rol
            $sql = "SELECT 
                        cu.id_company,
                        cu.role,
                        c.company_name
                    FROM company_users cu
                    JOIN companies c ON cu.id_company = c.id_company
                    WHERE cu.id_user = ? AND cu.status = 'active'
                    LIMIT 1";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->userId]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userData) {
                $this->companyId = $userData['id_company'];
                $this->userRole = $userData['role'];
            }
            
            // Obtener permisos del usuario
            $this->userPermissions = $this->getUserPermissions();
            
        } catch (PDOException $e) {
            error_log("Error cargando datos de usuario: " . $e->getMessage());
        }
    }
    
    /**
     * Obtener permisos del usuario
     */
    private function getUserPermissions() {
        if (!$this->userId) return [];
        
        try {
            $sql = "SELECT DISTINCT p.name
                    FROM permissions p
                    JOIN role_permissions rp ON p.id_permission = rp.id_permission
                    JOIN user_roles ur ON rp.id_role = ur.id_role
                    WHERE ur.id_user = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->userId]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo permisos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Renderizar un menú específico
     */
    public function renderMenu($menuKey, $options = []) {
        try {
            // Obtener el menú
            $sql = "SELECT * FROM dynamic_menus WHERE menu_key = ? AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$menuKey]);
            $menu = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$menu) {
                return $this->renderFallbackMenu($menuKey, $options);
            }
            
            // Obtener elementos del menú
            $items = $this->getMenuItems($menu['id_menu'], $options);
            
            // Renderizar el menú
            return $this->renderMenuHTML($menu, $items, $options);
            
        } catch (PDOException $e) {
            error_log("Error renderizando menú: " . $e->getMessage());
            return $this->renderFallbackMenu($menuKey, $options);
        }
    }
    
    /**
     * Obtener elementos de un menú con filtros de permisos
     */
    private function getMenuItems($menuId, $options = []) {
        try {
            $sql = "SELECT * FROM dynamic_menu_items 
                    WHERE id_menu = ? AND is_active = 1 
                    ORDER BY menu_order ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$menuId]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Filtrar por permisos
            $filteredItems = [];
            foreach ($items as $item) {
                if ($this->canUserSeeMenuItem($item)) {
                    $filteredItems[] = $item;
                }
            }
            
            return $filteredItems;
            
        } catch (PDOException $e) {
            error_log("Error obteniendo elementos del menú: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si el usuario puede ver un elemento del menú
     */
    private function canUserSeeMenuItem($item) {
        // Si no requiere permiso, siempre visible
        if (empty($item['permission_required'])) {
            return true;
        }
        
        // Verificar si el usuario tiene el permiso
        return in_array($item['permission_required'], $this->userPermissions);
    }
    
    /**
     * Renderizar HTML del menú
     */
    private function renderMenuHTML($menu, $items, $options = []) {
        $menuClass = $options['menu_class'] ?? 'nav flex-column';
        $itemClass = $options['item_class'] ?? 'nav-link';
        $activeClass = $options['active_class'] ?? 'active';
        $currentUrl = $options['current_url'] ?? $_SERVER['REQUEST_URI'] ?? '';
        
        $html = "<nav class=\"{$menuClass}\">";
        
        foreach ($items as $item) {
            $isActive = $this->isCurrentPage($item['url'], $currentUrl);
            $activeClassAttr = $isActive ? " {$activeClass}" : '';
            
            $html .= "<a href=\"{$item['url']}\" class=\"{$itemClass}{$activeClassAttr}\"";
            
            // Agregar atributos adicionales
            if (!empty($item['target'])) {
                $html .= " target=\"{$item['target']}\"";
            }
            
            if (!empty($item['css_classes'])) {
                $html .= " class=\"{$itemClass}{$activeClassAttr} {$item['css_classes']}\"";
            }
            
            $html .= ">";
            
            // Icono
            if (!empty($item['icon'])) {
                $iconColor = $item['icon_color'] ?? '#6c757d';
                $html .= "<i class=\"{$item['icon']}\" style=\"color: {$iconColor}; margin-right: 8px;\"></i>";
            }
            
            // Título
            $title = $this->getCustomTitle($item);
            $html .= htmlspecialchars($title);
            
            $html .= "</a>";
        }
        
        $html .= "</nav>";
        
        return $html;
    }
    
    /**
     * Obtener título personalizado para la empresa
     */
    private function getCustomTitle($item) {
        if (!$this->companyId) {
            return $item['title'];
        }
        
        try {
            $sql = "SELECT custom_title FROM company_menus 
                    WHERE id_company = ? AND id_menu = (
                        SELECT id_menu FROM dynamic_menu_items WHERE id_menu_item = ?
                    )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->companyId, $item['id_menu_item']]);
            $customTitle = $stmt->fetchColumn();
            
            return $customTitle ?: $item['title'];
            
        } catch (PDOException $e) {
            return $item['title'];
        }
    }
    
    /**
     * Verificar si es la página actual
     */
    private function isCurrentPage($itemUrl, $currentUrl) {
        if (empty($itemUrl) || $itemUrl === '#') {
            return false;
        }
        
        // Comparar URLs
        $itemUrl = rtrim($itemUrl, '/');
        $currentUrl = rtrim($currentUrl, '/');
        
        return $itemUrl === $currentUrl || strpos($currentUrl, $itemUrl) === 0;
    }
    
    /**
     * Renderizar menú de fallback si no existe el dinámico
     */
    private function renderFallbackMenu($menuKey, $options = []) {
        // Menús de fallback basados en el sistema existente
        $fallbackMenus = [
            'main_menu' => $this->renderMainMenuFallback($options),
            'admin_menu' => $this->renderAdminMenuFallback($options),
            'company_menu' => $this->renderCompanyMenuFallback($options),
            'user_menu' => $this->renderUserMenuFallback($options)
        ];
        
        return $fallbackMenus[$menuKey] ?? '<p class="text-muted">Menú no disponible</p>';
    }
    
    /**
     * Menú principal de fallback
     */
    private function renderMainMenuFallback($options) {
        $items = [
            ['url' => '/content.php', 'icon' => 'fas fa-home', 'title' => 'Dashboard'],
            ['url' => '/calendar.php', 'icon' => 'fas fa-calendar', 'title' => 'Calendario'],
            ['url' => '/projects.php', 'icon' => 'fas fa-project-diagram', 'title' => 'Proyectos'],
            ['url' => '/tickets.php', 'icon' => 'fas fa-ticket-alt', 'title' => 'Tickets'],
            ['url' => '/services.php', 'icon' => 'fas fa-cogs', 'title' => 'Servicios']
        ];
        
        return $this->renderFallbackHTML($items, $options);
    }
    
    /**
     * Menú de administración de fallback
     */
    private function renderAdminMenuFallback($options) {
        if (!in_array('admin_panel', $this->userPermissions)) {
            return '<p class="text-muted">Acceso denegado</p>';
        }
        
        $items = [
            ['url' => '/admin/dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'title' => 'Dashboard Admin'],
            ['url' => '/admin/companies.php', 'icon' => 'fas fa-building', 'title' => 'Empresas'],
            ['url' => '/admin/company_users.php', 'icon' => 'fas fa-users', 'title' => 'Usuarios'],
            ['url' => '/admin/services.php', 'icon' => 'fas fa-cogs', 'title' => 'Servicios'],
            ['url' => '/admin/audit_logs.php', 'icon' => 'fas fa-history', 'title' => 'Logs']
        ];
        
        return $this->renderFallbackHTML($items, $options);
    }
    
    /**
     * Menú de empresa de fallback
     */
    private function renderCompanyMenuFallback($options) {
        $items = [
            ['url' => '/content.php', 'icon' => 'fas fa-building', 'title' => 'Dashboard Empresa'],
            ['url' => '/services.php', 'icon' => 'fas fa-tools', 'title' => 'Mis Servicios'],
            ['url' => '/new_service.php', 'icon' => 'fas fa-plus', 'title' => 'Nuevo Servicio'],
            ['url' => '/invite-user.php', 'icon' => 'fas fa-user-plus', 'title' => 'Invitar Usuarios'],
            ['url' => '/company-settings.php', 'icon' => 'fas fa-cog', 'title' => 'Configuración']
        ];
        
        return $this->renderFallbackHTML($items, $options);
    }
    
    /**
     * Menú de usuario de fallback
     */
    private function renderUserMenuFallback($options) {
        $items = [
            ['url' => '/profile.php', 'icon' => 'fas fa-user', 'title' => 'Mi Perfil'],
            ['url' => '/tickets.php', 'icon' => 'fas fa-ticket-alt', 'title' => 'Mis Tickets'],
            ['url' => '/calendar.php', 'icon' => 'fas fa-calendar', 'title' => 'Mi Calendario']
        ];
        
        return $this->renderFallbackHTML($items, $options);
    }
    
    /**
     * Renderizar HTML de fallback
     */
    private function renderFallbackHTML($items, $options) {
        $menuClass = $options['menu_class'] ?? 'nav flex-column';
        $itemClass = $options['item_class'] ?? 'nav-link';
        $currentUrl = $options['current_url'] ?? $_SERVER['REQUEST_URI'] ?? '';
        
        $html = "<nav class=\"{$menuClass}\">";
        
        foreach ($items as $item) {
            $isActive = $this->isCurrentPage($item['url'], $currentUrl);
            $activeClass = $isActive ? ' active' : '';
            
            $html .= "<a href=\"{$item['url']}\" class=\"{$itemClass}{$activeClass}\">";
            $html .= "<i class=\"{$item['icon']}\" style=\"margin-right: 8px;\"></i>";
            $html .= htmlspecialchars($item['title']);
            $html .= "</a>";
        }
        
        $html .= "</nav>";
        
        return $html;
    }
    
    /**
     * Renderizar menú como lista desplegable (Bootstrap dropdown)
     */
    public function renderDropdownMenu($menuKey, $options = []) {
        $items = $this->getMenuItems($this->getMenuId($menuKey), $options);
        
        if (empty($items)) {
            return '';
        }
        
        $dropdownId = $options['dropdown_id'] ?? 'menu-dropdown-' . uniqid();
        $buttonText = $options['button_text'] ?? 'Menú';
        $buttonClass = $options['button_class'] ?? 'btn btn-secondary dropdown-toggle';
        
        $html = "<div class=\"dropdown\">";
        $html .= "<button class=\"{$buttonClass}\" type=\"button\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">";
        $html .= htmlspecialchars($buttonText);
        $html .= "</button>";
        
        $html .= "<ul class=\"dropdown-menu\" id=\"{$dropdownId}\">";
        
        foreach ($items as $item) {
            $html .= "<li><a class=\"dropdown-item\" href=\"{$item['url']}\">";
            
            if (!empty($item['icon'])) {
                $iconColor = $item['icon_color'] ?? '#6c757d';
                $html .= "<i class=\"{$item['icon']}\" style=\"color: {$iconColor}; margin-right: 8px;\"></i>";
            }
            
            $title = $this->getCustomTitle($item);
            $html .= htmlspecialchars($title);
            $html .= "</a></li>";
        }
        
        $html .= "</ul></div>";
        
        return $html;
    }
    
    /**
     * Renderizar menú como navbar horizontal
     */
    public function renderNavbarMenu($menuKey, $options = []) {
        $items = $this->getMenuItems($this->getMenuId($menuKey), $options);
        
        if (empty($items)) {
            return '';
        }
        
        $navbarClass = $options['navbar_class'] ?? 'navbar-nav me-auto mb-2 mb-lg-0';
        $itemClass = $options['item_class'] ?? 'nav-link';
        
        $html = "<ul class=\"{$navbarClass}\">";
        
        foreach ($items as $item) {
            $isActive = $this->isCurrentPage($item['url'], $_SERVER['REQUEST_URI'] ?? '');
            $activeClass = $isActive ? ' active' : '';
            
            $html .= "<li class=\"nav-item\">";
            $html .= "<a class=\"{$itemClass}{$activeClass}\" href=\"{$item['url']}\">";
            
            if (!empty($item['icon'])) {
                $iconColor = $item['icon_color'] ?? '#6c757d';
                $html .= "<i class=\"{$item['icon']}\" style=\"color: {$iconColor}; margin-right: 5px;\"></i>";
            }
            
            $title = $this->getCustomTitle($item);
            $html .= htmlspecialchars($title);
            $html .= "</a></li>";
        }
        
        $html .= "</ul>";
        
        return $html;
    }
    
    /**
     * Obtener ID del menú por clave
     */
    private function getMenuId($menuKey) {
        try {
            $sql = "SELECT id_menu FROM dynamic_menus WHERE menu_key = ? AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$menuKey]);
            
            return $stmt->fetchColumn() ?: 0;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Obtener menús disponibles para una empresa
     */
    public function getAvailableMenus() {
        if (!$this->companyId) {
            return [];
        }
        
        try {
            $sql = "SELECT 
                        m.id_menu,
                        m.menu_name,
                        m.menu_key,
                        COALESCE(cm.is_active, 1) as is_active,
                        cm.custom_title
                    FROM dynamic_menus m
                    LEFT JOIN company_menus cm ON m.id_menu = cm.id_menu AND cm.id_company = ?
                    WHERE m.is_active = 1
                    ORDER BY m.menu_name";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->companyId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Error obteniendo menús disponibles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verificar si un menú está activo para la empresa
     */
    public function isMenuActive($menuKey) {
        if (!$this->companyId) {
            return true; // Por defecto activo si no hay empresa
        }
        
        try {
            $sql = "SELECT COALESCE(cm.is_active, 1) as is_active
                    FROM dynamic_menus m
                    LEFT JOIN company_menus cm ON m.id_menu = cm.id_menu AND cm.id_company = ?
                    WHERE m.menu_key = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$this->companyId, $menuKey]);
            
            return (bool) $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            return true;
        }
    }
}

/**
 * Función helper para renderizar menús rápidamente
 */
function render_dynamic_menu($menuKey, $options = [], $userId = null) {
    $renderer = new DynamicMenuRenderer($userId);
    return $renderer->renderMenu($menuKey, $options);
}

/**
 * Función helper para renderizar menús como dropdown
 */
function render_dropdown_menu($menuKey, $options = [], $userId = null) {
    $renderer = new DynamicMenuRenderer($userId);
    return $renderer->renderDropdownMenu($menuKey, $options);
}

/**
 * Función helper para renderizar menús como navbar
 */
function render_navbar_menu($menuKey, $options = [], $userId = null) {
    $renderer = new DynamicMenuRenderer($userId);
    return $renderer->renderNavbarMenu($menuKey, $options);
}
?>
