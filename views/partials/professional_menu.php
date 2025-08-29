<?php
// Menú profesional con submenús laterales
// Este archivo debe ser incluido después de iniciar sesión y cargar las funciones

if (!isset($_SESSION['id_user'])) {
    return; // No mostrar menú si no hay sesión
}
require_once __DIR__ . '/../../db/functions.php';
$id_user = $_SESSION['id_user'];
$database = new Database();
$connection = $database->connection();

// Obtener permisos del usuario con info de menú
$sql = "SELECT p.Titulo, p.url, p.icon, p.section, p.menu_order
        FROM GET_ACCESS ga
        JOIN permissions p ON ga.id_permission = p.id_permission
        WHERE ga.id_user = :id_user AND p.show_in_menu = 1 AND p.url IS NOT NULL AND p.url != ''
        ORDER BY p.section, p.menu_order, p.Titulo";
$stmt = $connection->prepare($sql);
$stmt->execute([':id_user' => $id_user]);
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($menu_items)) return;

// Agrupar por sección
$menu_by_section = [];
foreach ($menu_items as $item) {
    $section = $item['section'] ?: 'General';
    $menu_by_section[$section][] = $item;
}
?>

<!-- Menú Profesional con Submenús -->
<style>
.professional-menu-dropdown {
    position: relative;
    display: inline-block;
}

.professional-menu-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.professional-menu-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.professional-menu-btn:active {
    transform: translateY(0);
}

.professional-menu-content {
    position: absolute;
    top: 100%;
    right: 0;
    background: white;
    width: 200px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1000;
    margin-top: 8px;
}

.professional-menu-content.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.professional-menu-header {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f3f4;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.professional-menu-title {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.professional-menu-subtitle {
    font-size: 11px;
    color: #6c757d;
    margin: 2px 0 0 0;
}

.professional-menu-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    color: #495057;
    text-decoration: none;
    transition: all 0.15s ease;
    border-bottom: 1px solid #f8f9fa;
    position: relative;
}

.professional-menu-item:hover {
    background: #f8f9fa;
    color: #007bff;
    text-decoration: none;
}

.professional-menu-item:last-child {
    border-bottom: none;
}

.professional-menu-item-icon {
    width: 16px;
    margin-right: 10px;
    font-size: 12px;
    color: #6c757d;
    text-align: center;
}

.professional-menu-item:hover .professional-menu-item-icon {
    color: #007bff;
}

.professional-menu-item-content {
    flex: 1;
}

.professional-menu-item-title {
    font-size: 13px;
    font-weight: 500;
    margin: 0;
    line-height: 1.2;
}

.professional-menu-item-arrow {
    font-size: 10px;
    color: #6c757d;
    margin-left: 8px;
}

.professional-menu-item:hover .professional-menu-item-arrow {
    color: #007bff;
}

/* Submenús */
.professional-submenu {
    position: absolute;
    left: 100%;
    top: 0;
    background: white;
    width: 220px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    opacity: 0;
    visibility: hidden;
    transform: translateX(-10px);
    transition: all 0.2s ease;
    z-index: 1001;
    margin-left: 8px;
}

.professional-menu-item:hover .professional-submenu {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}

.professional-submenu-header {
    padding: 10px 16px;
    border-bottom: 1px solid #f1f3f4;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.professional-submenu-title {
    font-size: 13px;
    font-weight: 600;
    color: #1a1a1a;
    margin: 0;
}

.professional-submenu-item {
    display: flex;
    align-items: center;
    padding: 8px 16px;
    color: #495057;
    text-decoration: none;
    transition: all 0.15s ease;
    border-bottom: 1px solid #f8f9fa;
    font-size: 12px;
}

.professional-submenu-item:hover {
    background: #f8f9fa;
    color: #007bff;
    text-decoration: none;
}

.professional-submenu-item:last-child {
    border-bottom: none;
}

.professional-submenu-item-icon {
    width: 14px;
    margin-right: 8px;
    font-size: 11px;
    color: #6c757d;
    text-align: center;
}

.professional-submenu-item:hover .professional-submenu-item-icon {
    color: #007bff;
}

.professional-menu-footer {
    padding: 8px 16px;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
    text-align: center;
}

.professional-menu-close {
    background: none;
    border: none;
    color: #6c757d;
    font-size: 11px;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.15s ease;
}

.professional-menu-close:hover {
    background: #e9ecef;
    color: #495057;
}

/* Responsive */
@media (max-width: 768px) {
    .professional-menu-content {
        right: -20px;
        width: 180px;
    }
    
    .professional-submenu {
        left: auto;
        right: 100%;
        margin-left: 0;
        margin-right: 8px;
    }
}
</style>

<!-- Botón del menú -->
<div class="professional-menu-dropdown">
    <button class="professional-menu-btn" onclick="toggleProfessionalMenu()">
        <i class="fas fa-cogs"></i>
        Admin
        <i class="fas fa-chevron-down" style="font-size: 10px; margin-left: 4px;"></i>
    </button>
    
    <!-- Contenido del menú -->
    <div class="professional-menu-content" id="professionalMenu">
        <div class="professional-menu-header">
            <h3 class="professional-menu-title">Panel de Administración</h3>
            <p class="professional-menu-subtitle">Gestiona tu sistema</p>
        </div>
        
        <?php foreach ($menu_by_section as $section => $items): ?>
            <div class="professional-menu-item">
                <i class="fas fa-folder-open professional-menu-item-icon"></i>
                <div class="professional-menu-item-content">
                    <div class="professional-menu-item-title"><?php echo htmlspecialchars($section); ?></div>
                </div>
                <i class="fas fa-chevron-right professional-menu-item-arrow"></i>
                
                <div class="professional-submenu">
                    <div class="professional-submenu-header">
                        <div class="professional-submenu-title"><?php echo htmlspecialchars($section); ?></div>
                    </div>
                    
                    <?php foreach ($items as $item): ?>
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" class="professional-submenu-item" 
                           title="<?php echo htmlspecialchars($item['Titulo']); ?>">
                            <i class="<?php echo htmlspecialchars($item['icon'] ?: 'fas fa-link'); ?> professional-submenu-item-icon"></i>
                            <?php echo htmlspecialchars($item['Titulo']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="professional-menu-footer">
            <button class="professional-menu-close" onclick="closeProfessionalMenu()">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
function toggleProfessionalMenu() {
    const menu = document.getElementById('professionalMenu');
    menu.classList.toggle('show');
}

function closeProfessionalMenu() {
    const menu = document.getElementById('professionalMenu');
    menu.classList.remove('show');
}

// Cerrar al hacer clic fuera del menú
document.addEventListener('click', function(event) {
    const menu = document.getElementById('professionalMenu');
    const dropdown = event.target.closest('.professional-menu-dropdown');
    
    if (!dropdown && menu.classList.contains('show')) {
        menu.classList.remove('show');
    }
});

// Cerrar con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeProfessionalMenu();
    }
});
</script> 