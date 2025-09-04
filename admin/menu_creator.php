<?php
/**
 * MENU CREATOR - Sistema de Menús por Roles
 * SUPERADMIN, ADMIN, USUARIO
 */

session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../db/functions.php';

// Verificar que sea superadmin
if (!isset($_SESSION['is_superadmin']) || $_SESSION['is_superadmin'] != 1) {
    header('Location: ../login.php?error=access_denied');
    exit();
}

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener funcionalidades disponibles
    $stmt = $pdo->query("SELECT * FROM system_functionalities WHERE is_active = 1 ORDER BY functionality_name");
    $functionalities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener menús por rol
    $stmt = $pdo->query("
        SELECT rm.*, sf.functionality_name, sf.icon, sf.url, sf.description
        FROM role_menus rm
        JOIN system_functionalities sf ON rm.functionality_key = sf.functionality_key
        ORDER BY rm.role_type, rm.menu_order
    ");
    $role_menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar por rol
    $menus_by_role = [
        'superadmin' => [],
        'admin' => [],
        'usuario' => []
    ];
    
    foreach ($role_menus as $menu) {
        $menus_by_role[$menu['role_type']][] = $menu;
    }
    
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MENU CREATOR - PIN9</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Sortable.js para drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px;
            padding: 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        
        .header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .role-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .role-section:hover {
            border-color: #667eea;
            transform: translateY(-2px);
        }
        
        .role-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
        }
        
        .role-title {
            font-size: 1.5em;
            font-weight: 600;
            margin: 0;
        }
        
        .role-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9em;
        }
        
        .badge-superadmin {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
        }
        
        .badge-admin {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
            color: white;
        }
        
        .badge-usuario {
            background: linear-gradient(45deg, #a8e6cf, #7fcdcd);
            color: #333;
        }
        
        .menu-container {
            min-height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 15px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .menu-container.drag-over {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .menu-item {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: move;
            transition: all 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .menu-item:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .menu-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        
        .menu-item-info {
            display: flex;
            align-items: center;
            flex-grow: 1;
        }
        
        .menu-item-icon {
            font-size: 1.2em;
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        
        .menu-item-details h6 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }
        
        .menu-item-details small {
            color: #666;
        }
        
        .menu-item-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8em;
        }
        
        .available-functionalities {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        
        .functionality-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            cursor: move;
            transition: all 0.3s ease;
        }
        
        .functionality-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .functionality-item.dragging {
            opacity: 0.5;
        }
        
        .save-button {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            padding: 15px 40px;
            border-radius: 25px;
            color: white;
            font-weight: 600;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        
        .save-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .empty-state {
            text-align: center;
            color: #999;
            padding: 40px 20px;
            font-style: italic;
        }
        
        .stats {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="main-container">
            <!-- Header -->
            <div class="header">
                <h1><i class="fas fa-bars"></i> MENU CREATOR</h1>
                <p>Gestiona los menús por rol: SUPERADMIN, ADMIN, USUARIO</p>
            </div>
            
            <!-- Alertas -->
            <div id="alerts"></div>
            
            <!-- Estadísticas -->
            <div class="stats">
                <div class="row">
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= count($functionalities) ?></div>
                            <div class="stat-label">Funcionalidades</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= count($menus_by_role['superadmin']) ?></div>
                            <div class="stat-label">Superadmin</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= count($menus_by_role['admin']) ?></div>
                            <div class="stat-label">Admin</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-item">
                            <div class="stat-number"><?= count($menus_by_role['usuario']) ?></div>
                            <div class="stat-label">Usuario</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <!-- Funcionalidades Disponibles -->
                <div class="col-md-3">
                    <div class="available-functionalities">
                        <h5><i class="fas fa-puzzle-piece"></i> Funcionalidades Disponibles</h5>
                        <p class="text-muted small">Arrastra para agregar a los menús</p>
                        
                        <div id="availableFunctionalities">
                            <?php foreach ($functionalities as $func): ?>
                                <div class="functionality-item" data-functionality="<?= htmlspecialchars($func['functionality_key']) ?>">
                                    <div class="d-flex align-items-center">
                                        <i class="<?= htmlspecialchars($func['icon']) ?> menu-item-icon"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($func['functionality_name']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($func['description']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Menús por Rol -->
                <div class="col-md-9">
                    <!-- SUPERADMIN -->
                    <div class="role-section">
                        <div class="role-header">
                            <h3 class="role-title"><i class="fas fa-crown"></i> SUPERADMIN</h3>
                            <span class="role-badge badge-superadmin">Acceso Total</span>
                        </div>
                        <div class="menu-container" data-role="superadmin" id="superadmin-menu">
                            <?php if (empty($menus_by_role['superadmin'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-plus-circle fa-2x mb-3"></i>
                                    <p>Arrastra funcionalidades aquí</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($menus_by_role['superadmin'] as $menu): ?>
                                    <div class="menu-item" data-menu-id="<?= $menu['id'] ?>" data-functionality="<?= $menu['functionality_key'] ?>">
                                        <div class="menu-item-info">
                                            <i class="<?= htmlspecialchars($menu['icon']) ?> menu-item-icon"></i>
                                            <div class="menu-item-details">
                                                <h6><?= htmlspecialchars($menu['custom_title'] ?: $menu['functionality_name']) ?></h6>
                                                <small><?= htmlspecialchars($menu['description']) ?></small>
                                            </div>
                                        </div>
                                        <div class="menu-item-actions">
                                            <button class="btn btn-sm btn-outline-primary" onclick="toggleVisibility(<?= $menu['id'] ?>, <?= $menu['is_visible'] ?>)">
                                                <i class="fas fa-eye<?= $menu['is_visible'] ? '' : '-slash' ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromMenu(<?= $menu['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ADMIN -->
                    <div class="role-section">
                        <div class="role-header">
                            <h3 class="role-title"><i class="fas fa-user-shield"></i> ADMIN</h3>
                            <span class="role-badge badge-admin">Administración</span>
                        </div>
                        <div class="menu-container" data-role="admin" id="admin-menu">
                            <?php if (empty($menus_by_role['admin'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-plus-circle fa-2x mb-3"></i>
                                    <p>Arrastra funcionalidades aquí</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($menus_by_role['admin'] as $menu): ?>
                                    <div class="menu-item" data-menu-id="<?= $menu['id'] ?>" data-functionality="<?= $menu['functionality_key'] ?>">
                                        <div class="menu-item-info">
                                            <i class="<?= htmlspecialchars($menu['icon']) ?> menu-item-icon"></i>
                                            <div class="menu-item-details">
                                                <h6><?= htmlspecialchars($menu['custom_title'] ?: $menu['functionality_name']) ?></h6>
                                                <small><?= htmlspecialchars($menu['description']) ?></small>
                                            </div>
                                        </div>
                                        <div class="menu-item-actions">
                                            <button class="btn btn-sm btn-outline-primary" onclick="toggleVisibility(<?= $menu['id'] ?>, <?= $menu['is_visible'] ?>)">
                                                <i class="fas fa-eye<?= $menu['is_visible'] ? '' : '-slash' ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromMenu(<?= $menu['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- USUARIO -->
                    <div class="role-section">
                        <div class="role-header">
                            <h3 class="role-title"><i class="fas fa-user"></i> USUARIO</h3>
                            <span class="role-badge badge-usuario">Acceso Básico</span>
                        </div>
                        <div class="menu-container" data-role="usuario" id="usuario-menu">
                            <?php if (empty($menus_by_role['usuario'])): ?>
                                <div class="empty-state">
                                    <i class="fas fa-plus-circle fa-2x mb-3"></i>
                                    <p>Arrastra funcionalidades aquí</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($menus_by_role['usuario'] as $menu): ?>
                                    <div class="menu-item" data-menu-id="<?= $menu['id'] ?>" data-functionality="<?= $menu['functionality_key'] ?>">
                                        <div class="menu-item-info">
                                            <i class="<?= htmlspecialchars($menu['icon']) ?> menu-item-icon"></i>
                                            <div class="menu-item-details">
                                                <h6><?= htmlspecialchars($menu['custom_title'] ?: $menu['functionality_name']) ?></h6>
                                                <small><?= htmlspecialchars($menu['description']) ?></small>
                                            </div>
                                        </div>
                                        <div class="menu-item-actions">
                                            <button class="btn btn-sm btn-outline-primary" onclick="toggleVisibility(<?= $menu['id'] ?>, <?= $menu['is_visible'] ?>)">
                                                <i class="fas fa-eye<?= $menu['is_visible'] ? '' : '-slash' ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="removeFromMenu(<?= $menu['id'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Botón Guardar -->
            <div class="text-center mt-4">
                <button class="btn save-button" onclick="saveMenuConfiguration()">
                    <i class="fas fa-save"></i> Guardar Configuración
                </button>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Inicializar Sortable.js
        document.addEventListener('DOMContentLoaded', function() {
            initializeSortable();
        });
        
        function initializeSortable() {
            // Funcionalidades disponibles
            new Sortable(document.getElementById('availableFunctionalities'), {
                group: 'shared',
                animation: 150,
                ghostClass: 'dragging',
                onEnd: function(evt) {
                    if (evt.to.dataset.role) {
                        addToMenu(evt.item.dataset.functionality, evt.to.dataset.role);
                    }
                }
            });
            
            // Menús por rol
            ['superadmin', 'admin', 'usuario'].forEach(role => {
                new Sortable(document.getElementById(role + '-menu'), {
                    group: 'shared',
                    animation: 150,
                    ghostClass: 'dragging',
                    onEnd: function(evt) {
                        updateMenuOrder(role);
                    }
                });
            });
        }
        
        // Agregar funcionalidad al menú
        function addToMenu(functionalityKey, roleType) {
            fetch('menu_creator_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_to_menu',
                    functionality_key: functionalityKey,
                    role_type: roleType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert('Error al agregar funcionalidad', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al agregar funcionalidad', 'danger');
            });
        }
        
        // Actualizar orden del menú
        function updateMenuOrder(roleType) {
            const container = document.getElementById(roleType + '-menu');
            const items = Array.from(container.children).map((item, index) => ({
                id: item.dataset.menuId,
                order: index + 1
            }));
            
            fetch('menu_creator_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_order',
                    role_type: roleType,
                    items: items
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Orden actualizado', 'success');
                }
            })
            .catch(error => console.error('Error:', error));
        }
        
        // Alternar visibilidad
        function toggleVisibility(menuId, currentVisibility) {
            const newVisibility = currentVisibility ? 0 : 1;
            
            fetch('menu_creator_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_visibility',
                    menu_id: menuId,
                    is_visible: newVisibility
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert('Error al cambiar visibilidad', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al cambiar visibilidad', 'danger');
            });
        }
        
        // Remover del menú
        function removeFromMenu(menuId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este elemento del menú?')) return;
            
            fetch('menu_creator_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove_from_menu',
                    menu_id: menuId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showAlert('Error al eliminar elemento', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al eliminar elemento', 'danger');
            });
        }
        
        // Guardar configuración
        function saveMenuConfiguration() {
            showAlert('Configuración guardada exitosamente', 'success');
        }
        
        // Mostrar alerta
        function showAlert(message, type) {
            const alertsContainer = document.getElementById('alerts');
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            alertsContainer.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
