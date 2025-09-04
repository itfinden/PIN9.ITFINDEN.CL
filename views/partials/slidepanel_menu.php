<?php
/**
 * Slidepanel Menu - Sistema de Menús por Roles
 * Lee desde la base de datos según el rol del usuario
 */

require_once __DIR__ . '/../../db/connection.php';
require_once __DIR__ . '/../../db/functions.php';

// Obtener información del usuario
$user_id = $_SESSION['id_user'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

// Determinar el rol para el menú
$menu_role = 'usuario'; // Por defecto

if ($user_id) {
    // Verificar si es superadmin
    if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin'] == 1) {
        $menu_role = 'superadmin';
    } elseif ($user_role === 'admin') {
        $menu_role = 'admin';
    } else {
        $menu_role = 'usuario';
    }
}

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener menú según el rol
    $stmt = $pdo->prepare("
        SELECT rm.*, sf.functionality_name, sf.icon, sf.url, sf.description
        FROM role_menus rm
        JOIN system_functionalities sf ON rm.functionality_key = sf.functionality_key
        WHERE rm.role_type = ? AND rm.is_visible = 1 AND sf.is_active = 1
        ORDER BY rm.menu_order ASC
    ");
    $stmt->execute([$menu_role]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Error cargando menú del slidepanel: " . $e->getMessage());
    $menu_items = [];
}
?>

<!-- Slidepanel Menu -->
<div class="slidepanel-menu">
    <div class="menu-header">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="user-details">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['user'] ?? 'Usuario'); ?></div>
                <div class="user-role-badge role-<?php echo $menu_role; ?>">
                    <?php echo strtoupper($menu_role); ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="menu-content">
        <nav class="menu-nav">
            <?php if (empty($menu_items)): ?>
                <div class="empty-menu">
                    <i class="fas fa-info-circle"></i>
                    <p>No hay elementos de menú disponibles</p>
                </div>
            <?php else: ?>
                <?php foreach ($menu_items as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['url']); ?>" class="menu-item" data-functionality="<?php echo htmlspecialchars($item['functionality_key']); ?>">
                        <div class="menu-item-icon">
                            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
                        </div>
                        <div class="menu-item-content">
                            <div class="menu-item-title">
                                <?php echo htmlspecialchars($item['custom_title'] ?: $item['functionality_name']); ?>
                            </div>
                            <div class="menu-item-description">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </div>
                        </div>
                        <div class="menu-item-arrow">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>
    </div>
    
    <div class="menu-footer">
        <div class="menu-stats">
            <div class="stat-item">
                <i class="fas fa-bars"></i>
                <span><?php echo count($menu_items); ?> elementos</span>
            </div>
            <div class="stat-item">
                <i class="fas fa-user-shield"></i>
                <span><?php echo ucfirst($menu_role); ?></span>
            </div>
        </div>
    </div>
</div>

<style>
.slidepanel-menu {
    background: var(--bg-primary);
    color: var(--text-primary);
    height: 100vh;
    display: flex;
    flex-direction: column;
    border-right: 1px solid var(--border-color);
    box-shadow: 2px 0 10px var(--shadow-light);
}

.menu-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--info-color));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.user-details {
    flex: 1;
}

.user-name {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 5px;
    color: var(--text-primary);
}

.user-role-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-superadmin {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
}

.role-admin {
    background: linear-gradient(45deg, #4ecdc4, #44a08d);
    color: white;
}

.role-usuario {
    background: linear-gradient(45deg, #a8e6cf, #7fcdcd);
    color: #333;
}

.menu-content {
    flex: 1;
    overflow-y: auto;
    padding: 10px 0;
}

.menu-nav {
    display: flex;
    flex-direction: column;
}

.menu-item {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
    position: relative;
}

.menu-item:hover {
    background: var(--bg-secondary);
    border-left-color: var(--primary-color);
    color: var(--text-primary);
    text-decoration: none;
    transform: translateX(5px);
}

.menu-item:active {
    background: var(--primary-color);
    color: white;
}

.menu-item-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--bg-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    transition: all 0.3s ease;
}

.menu-item:hover .menu-item-icon {
    background: var(--primary-color);
    color: white;
    transform: scale(1.1);
}

.menu-item-icon i {
    font-size: 1.2rem;
}

.menu-item-content {
    flex: 1;
}

.menu-item-title {
    font-weight: 600;
    font-size: 1rem;
    margin-bottom: 3px;
}

.menu-item-description {
    font-size: 0.85rem;
    opacity: 0.7;
    line-height: 1.3;
}

.menu-item-arrow {
    opacity: 0;
    transition: all 0.3s ease;
    color: var(--text-muted);
}

.menu-item:hover .menu-item-arrow {
    opacity: 1;
    transform: translateX(5px);
}

.empty-menu {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}

.empty-menu i {
    font-size: 3rem;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-menu p {
    font-size: 1.1rem;
    margin: 0;
}

.menu-footer {
    padding: 20px;
    border-top: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.menu-stats {
    display: flex;
    justify-content: space-between;
    gap: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: var(--text-muted);
}

.stat-item i {
    font-size: 1rem;
    color: var(--primary-color);
}

/* Responsive */
@media (max-width: 768px) {
    .slidepanel-menu {
        width: 100%;
    }
    
    .menu-header {
        padding: 15px;
    }
    
    .user-info {
        gap: 10px;
    }
    
    .user-avatar {
        width: 40px;
        height: 40px;
        font-size: 1.2rem;
    }
    
    .user-name {
        font-size: 1rem;
    }
    
    .menu-item {
        padding: 12px 15px;
    }
    
    .menu-item-icon {
        width: 35px;
        height: 35px;
        margin-right: 12px;
    }
    
    .menu-item-icon i {
        font-size: 1rem;
    }
    
    .menu-item-title {
        font-size: 0.95rem;
    }
    
    .menu-item-description {
        font-size: 0.8rem;
    }
}

/* Dark mode adjustments */
[data-theme="dark"] .slidepanel-menu {
    background: var(--bg-primary-dark);
    color: var(--text-primary-dark);
}

[data-theme="dark"] .menu-header {
    background: var(--bg-secondary-dark);
    border-bottom-color: var(--border-color-dark);
}

[data-theme="dark"] .menu-item:hover {
    background: var(--bg-secondary-dark);
}

[data-theme="dark"] .menu-item-icon {
    background: var(--bg-secondary-dark);
}

[data-theme="dark"] .menu-footer {
    background: var(--bg-secondary-dark);
    border-top-color: var(--border-color-dark);
}
</style>