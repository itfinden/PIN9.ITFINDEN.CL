<?php
/**
 * Administrador de Menús Dinámicos
 * Permite gestionar menús, elementos y permisos de forma visual
 */

session_start();
require_once __DIR__ . '/../db/connection.php';
require_once __DIR__ . '/../db/functions.php';

// Verificar si el usuario es superadmin
if (!isSuperAdmin($_SESSION['id_user'] ?? 0)) {
    header('Location: ../login.php');
    exit;
}

// Obtener datos para el formulario
try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener menús
    $stmt = $pdo->query("SELECT * FROM dynamic_menus ORDER BY menu_name");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener módulos
    $stmt = $pdo->query("SELECT * FROM system_modules ORDER BY display_name");
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener permisos
    $stmt = $pdo->query("SELECT * FROM permissions ORDER BY name");
    $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener empresas
    $stmt = $pdo->query("SELECT * FROM companies ORDER BY company_name");
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener roles
    $stmt = $pdo->query("SELECT * FROM roles ORDER BY name");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrador de Menús - PIN9</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Sortable.js para drag & drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <style>
        .menu-builder {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .menu-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            cursor: move;
            transition: all 0.3s ease;
        }
        
        .menu-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .menu-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
        }
        
        .menu-section {
            background: white;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 20px;
            min-height: 200px;
            transition: all 0.3s ease;
        }
        
        .menu-section.drag-over {
            border-color: #007bff;
            background: #f8f9ff;
        }
        
        .icon-picker {
            position: relative;
        }
        
        .icon-picker .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .color-picker {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .permission-badge {
            background: #e9ecef;
            color: #495057;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin: 2px;
            display: inline-block;
        }
        
        .module-badge {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            margin: 2px;
            display: inline-block;
        }
        
        .tab-content {
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 5px 5px;
            padding: 20px;
        }
        
        .menu-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        
        .menu-preview .nav-link {
            color: #495057;
            padding: 8px 15px;
            border-radius: 5px;
            margin: 2px 0;
            transition: all 0.3s ease;
        }
        
        .menu-preview .nav-link:hover {
            background: #e9ecef;
            color: #212529;
        }
        
        .menu-preview .nav-link i {
            margin-right: 8px;
            width: 16px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h5 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Administración</span>
                    </h5>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="menu_manager.php">
                                <i class="fas fa-bars"></i> Gestor de Menús
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="companies.php">
                                <i class="fas fa-building"></i> Empresas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="role_permissions.php">
                                <i class="fas fa-key"></i> Permisos
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-bars text-primary"></i> 
                        Administrador de Menús Dinámicos
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="saveMenuOrder()">
                                <i class="fas fa-save"></i> Guardar Orden
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="exportMenuConfig()">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alertas -->
                <div id="alerts"></div>

                <!-- Tabs de navegación -->
                <ul class="nav nav-tabs" id="menuTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="builder-tab" data-bs-toggle="tab" data-bs-target="#builder" type="button" role="tab">
                            <i class="fas fa-puzzle-piece"></i> Constructor de Menús
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" type="button" role="tab">
                            <i class="fas fa-key"></i> Gestión de Permisos
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="companies-tab" data-bs-toggle="tab" data-bs-target="#companies" type="button" role="tab">
                            <i class="fas fa-building"></i> Menús por Empresa
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="preview-tab" data-bs-toggle="tab" data-bs-target="#preview" type="button" role="tab">
                            <i class="fas fa-eye"></i> Vista Previa
                        </button>
                    </li>
                </ul>

                <!-- Contenido de las tabs -->
                <div class="tab-content" id="menuTabsContent">
                    <!-- Tab Constructor de Menús -->
                    <div class="tab-pane fade show active" id="builder" role="tabpanel">
                        <div class="row">
                            <!-- Panel izquierdo - Elementos disponibles -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-plus-circle"></i> Elementos Disponibles</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Filtrar por módulo:</label>
                                            <select class="form-select" id="moduleFilter">
                                                <option value="">Todos los módulos</option>
                                                <?php foreach ($modules as $module): ?>
                                                    <option value="<?= htmlspecialchars($module['module_key']) ?>">
                                                        <?= htmlspecialchars($module['display_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div id="availableItems" class="menu-section">
                                            <!-- Los elementos se cargarán dinámicamente -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel central - Constructor de menús -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5><i class="fas fa-edit"></i> Constructor de Menús</h5>
                                            <div>
                                                <select class="form-select form-select-sm" id="menuSelector" style="width: auto;">
                                                    <?php foreach ($menus as $menu): ?>
                                                        <option value="<?= $menu['id_menu'] ?>">
                                                            <?= htmlspecialchars($menu['menu_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div id="menuBuilder" class="menu-section">
                                            <!-- Los elementos del menú se cargarán aquí -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Gestión de Permisos -->
                    <div class="tab-pane fade" id="permissions" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-key"></i> Permisos del Sistema</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Permiso</th>
                                                        <th>Descripción</th>
                                                        <th>URL</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($permissions as $permission): ?>
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-primary"><?= htmlspecialchars($permission['name']) ?></span>
                                                            </td>
                                                            <td><?= htmlspecialchars($permission['description'] ?? '') ?></td>
                                                            <td><small><?= htmlspecialchars($permission['Url'] ?? '') ?></small></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-puzzle-piece"></i> Módulos del Sistema</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Módulo</th>
                                                        <th>Descripción</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($modules as $module): ?>
                                                        <tr>
                                                            <td>
                                                                <i class="<?= htmlspecialchars($module['icon']) ?>"></i>
                                                                <?= htmlspecialchars($module['display_name']) ?>
                                                            </td>
                                                            <td><?= htmlspecialchars($module['description']) ?></td>
                                                            <td>
                                                                <?php if ($module['is_core']): ?>
                                                                    <span class="badge bg-success">Core</span>
                                                                <?php else: ?>
                                                                    <span class="badge bg-info">Módulo</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Menús por Empresa -->
                    <div class="tab-pane fade" id="companies" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-building"></i> Empresas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="list-group" id="companyList">
                                            <?php foreach ($companies as $company): ?>
                                                <a href="#" class="list-group-item list-group-item-action" 
                                                   data-company-id="<?= $company['id_company'] ?>">
                                                    <i class="fas fa-building"></i>
                                                    <?= htmlspecialchars($company['company_name']) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-bars"></i> Configuración de Menús</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="companyMenuConfig">
                                            <p class="text-muted">Selecciona una empresa para configurar sus menús</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Vista Previa -->
                    <div class="tab-pane fade" id="preview" role="tabpanel">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-eye"></i> Configuración de Vista Previa</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Seleccionar Menú:</label>
                                            <select class="form-select" id="previewMenuSelector">
                                                <?php foreach ($menus as $menu): ?>
                                                    <option value="<?= $menu['id_menu'] ?>">
                                                        <?= htmlspecialchars($menu['menu_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label class="form-label">Seleccionar Rol:</label>
                                            <select class="form-select" id="previewRoleSelector">
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?= $role['id_role'] ?>">
                                                        <?= htmlspecialchars($role['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <button class="btn btn-primary" onclick="generatePreview()">
                                            <i class="fas fa-eye"></i> Generar Vista Previa
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-bars"></i> Vista Previa del Menú</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="menuPreview">
                                            <p class="text-muted">Selecciona un menú y rol para generar la vista previa</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal para editar elemento de menú -->
    <div class="modal fade" id="editMenuItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Elemento de Menú</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editMenuItemForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Título</label>
                                    <input type="text" class="form-control" id="editTitle" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control" id="editDescription" rows="3"></textarea>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">URL</label>
                                    <input type="text" class="form-control" id="editUrl">
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Icono</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="editIcon" placeholder="fas fa-link">
                                        <button class="btn btn-outline-secondary" type="button" onclick="openIconPicker()">
                                            <i class="fas fa-icons"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Color del Icono</label>
                                    <input type="color" class="form-control color-picker" id="editIconColor">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Permiso Requerido</label>
                                    <select class="form-select" id="editPermission">
                                        <option value="">Sin permiso</option>
                                        <?php foreach ($permissions as $permission): ?>
                                            <option value="<?= htmlspecialchars($permission['name']) ?>">
                                                <?= htmlspecialchars($permission['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Módulo</label>
                                    <select class="form-select" id="editModule">
                                        <option value="">Sin módulo</option>
                                        <?php foreach ($modules as $module): ?>
                                            <option value="<?= htmlspecialchars($module['module_key']) ?>">
                                                <?= htmlspecialchars($module['display_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveMenuItem()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Variables globales
        let currentMenuId = null;
        let currentEditingItem = null;
        let menuItems = [];

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            currentMenuId = document.getElementById('menuSelector').value;
            loadMenuItems();
            loadAvailableItems();
            initializeSortable();
        });

        // Cargar elementos del menú seleccionado
        function loadMenuItems() {
            const menuId = document.getElementById('menuSelector').value;
            if (menuId === currentMenuId) return;
            
            currentMenuId = menuId;
            
            fetch(`menu_actions.php?action=get_menu_items&menu_id=${menuId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        menuItems = data.items;
                        renderMenuItems();
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Cargar elementos disponibles
        function loadAvailableItems() {
            const moduleFilter = document.getElementById('moduleFilter').value;
            
            fetch(`menu_actions.php?action=get_available_items&module=${moduleFilter}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderAvailableItems(data.items);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Renderizar elementos del menú
        function renderMenuItems() {
            const container = document.getElementById('menuBuilder');
            container.innerHTML = '';
            
            menuItems.forEach(item => {
                const itemElement = createMenuItemElement(item);
                container.appendChild(itemElement);
            });
        }

        // Renderizar elementos disponibles
        function renderAvailableItems(items) {
            const container = document.getElementById('availableItems');
            container.innerHTML = '';
            
            items.forEach(item => {
                const itemElement = createAvailableItemElement(item);
                container.appendChild(itemElement);
            });
        }

        // Crear elemento de menú
        function createMenuItemElement(item) {
            const div = document.createElement('div');
            div.className = 'menu-item';
            div.dataset.itemId = item.id_menu_item;
            
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="${item.icon}" style="color: ${item.icon_color}; margin-right: 10px;"></i>
                            <h6 class="mb-0">${item.title}</h6>
                        </div>
                        <p class="text-muted small mb-2">${item.description || ''}</p>
                        <div class="d-flex flex-wrap">
                            ${item.permission_required ? `<span class="permission-badge">${item.permission_required}</span>` : ''}
                            ${item.module_name ? `<span class="module-badge">${item.module_name}</span>` : ''}
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="editMenuItem(${item.id_menu_item})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-outline-danger" onclick="removeMenuItem(${item.id_menu_item})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            return div;
        }

        // Crear elemento disponible
        function createAvailableItemElement(item) {
            const div = document.createElement('div');
            div.className = 'menu-item';
            div.dataset.itemData = JSON.stringify(item);
            
            div.innerHTML = `
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center mb-2">
                            <i class="${item.icon}" style="color: ${item.icon_color}; margin-right: 10px;"></i>
                            <h6 class="mb-0">${item.title}</h6>
                        </div>
                        <p class="text-muted small mb-2">${item.description || ''}</p>
                        <div class="d-flex flex-wrap">
                            ${item.permission_required ? `<span class="permission-badge">${item.permission_required}</span>` : ''}
                            ${item.module_name ? `<span class="module-badge">${item.module_name}</span>` : ''}
                        </div>
                    </div>
                    <button class="btn btn-sm btn-success" onclick="addToMenu(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            `;
            
            return div;
        }

        // Inicializar Sortable.js
        function initializeSortable() {
            const menuBuilder = document.getElementById('menuBuilder');
            
            new Sortable(menuBuilder, {
                group: 'menu-items',
                animation: 150,
                ghostClass: 'dragging',
                onEnd: function(evt) {
                    updateMenuOrder();
                }
            });
            
            const availableItems = document.getElementById('availableItems');
            
            new Sortable(availableItems, {
                group: 'menu-items',
                animation: 150,
                ghostClass: 'dragging'
            });
        }

        // Agregar elemento al menú
        function addToMenu(itemData) {
            const menuId = document.getElementById('menuSelector').value;
            
            fetch('menu_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'add_menu_item',
                    menu_id: menuId,
                    item_data: itemData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadMenuItems();
                    showAlert('Elemento agregado al menú', 'success');
                } else {
                    showAlert('Error al agregar elemento', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al agregar elemento', 'danger');
            });
        }

        // Editar elemento de menú
        function editMenuItem(itemId) {
            const item = menuItems.find(i => i.id_menu_item == itemId);
            if (!item) return;
            
            currentEditingItem = item;
            
            // Llenar formulario
            document.getElementById('editTitle').value = item.title;
            document.getElementById('editDescription').value = item.description || '';
            document.getElementById('editUrl').value = item.url || '';
            document.getElementById('editIcon').value = item.icon;
            document.getElementById('editIconColor').value = item.icon_color;
            document.getElementById('editPermission').value = item.permission_required || '';
            document.getElementById('editModule').value = item.module_name || '';
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('editMenuItemModal'));
            modal.show();
        }

        // Guardar elemento de menú
        function saveMenuItem() {
            if (!currentEditingItem) return;
            
            const formData = {
                action: 'update_menu_item',
                item_id: currentEditingItem.id_menu_item,
                title: document.getElementById('editTitle').value,
                description: document.getElementById('editDescription').value,
                url: document.getElementById('editUrl').value,
                icon: document.getElementById('editIcon').value,
                icon_color: document.getElementById('editIconColor').value,
                permission_required: document.getElementById('editPermission').value,
                module_name: document.getElementById('editModule').value
            };
            
            fetch('menu_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadMenuItems();
                    bootstrap.Modal.getInstance(document.getElementById('editMenuItemModal')).hide();
                    showAlert('Elemento actualizado', 'success');
                } else {
                    showAlert('Error al actualizar elemento', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al actualizar elemento', 'danger');
            });
        }

        // Remover elemento del menú
        function removeMenuItem(itemId) {
            if (!confirm('¿Estás seguro de que quieres eliminar este elemento?')) return;
            
            fetch('menu_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'remove_menu_item',
                    item_id: itemId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadMenuItems();
                    showAlert('Elemento eliminado', 'success');
                } else {
                    showAlert('Error al eliminar elemento', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error al eliminar elemento', 'danger');
            });
        }

        // Actualizar orden del menú
        function updateMenuOrder() {
            const menuBuilder = document.getElementById('menuBuilder');
            const items = Array.from(menuBuilder.children).map((item, index) => ({
                id_menu_item: item.dataset.itemId,
                order: index + 1
            }));
            
            fetch('menu_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_menu_order',
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

        // Guardar orden del menú
        function saveMenuOrder() {
            updateMenuOrder();
        }

        // Exportar configuración del menú
        function exportMenuConfig() {
            const menuId = document.getElementById('menuSelector').value;
            
            fetch(`menu_actions.php?action=export_menu&menu_id=${menuId}`)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `menu_config_${menuId}.json`;
                    a.click();
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => console.error('Error:', error));
        }

        // Generar vista previa
        function generatePreview() {
            const menuId = document.getElementById('previewMenuSelector').value;
            const roleId = document.getElementById('previewRoleSelector').value;
            
            fetch(`menu_actions.php?action=generate_preview&menu_id=${menuId}&role_id=${roleId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderMenuPreview(data.menu);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Renderizar vista previa del menú
        function renderMenuPreview(menu) {
            const container = document.getElementById('menuPreview');
            
            let html = '<div class="menu-preview">';
            html += '<h6>Vista Previa del Menú</h6>';
            html += '<nav class="nav flex-column">';
            
            menu.forEach(item => {
                html += `
                    <a class="nav-link" href="${item.url || '#'}">
                        <i class="${item.icon}" style="color: ${item.icon_color}"></i>
                        ${item.title}
                    </a>
                `;
            });
            
            html += '</nav></div>';
            container.innerHTML = html;
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

        // Event listeners
        document.getElementById('menuSelector').addEventListener('change', loadMenuItems);
        document.getElementById('moduleFilter').addEventListener('change', loadAvailableItems);
        
        // Configuración de empresas
        document.querySelectorAll('#companyList a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const companyId = this.dataset.companyId;
                loadCompanyMenuConfig(companyId);
                
                // Actualizar estado activo
                document.querySelectorAll('#companyList a').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Cargar configuración de menús por empresa
        function loadCompanyMenuConfig(companyId) {
            fetch(`menu_actions.php?action=get_company_menu_config&company_id=${companyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCompanyMenuConfig(data.config);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Renderizar configuración de menús por empresa
        function renderCompanyMenuConfig(config) {
            const container = document.getElementById('companyMenuConfig');
            
            let html = '<div class="row">';
            config.menus.forEach(menu => {
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <h6>${menu.menu_name}</h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           ${menu.is_active ? 'checked' : ''} 
                                           onchange="toggleCompanyMenu(${config.company_id}, ${menu.id_menu}, this.checked)">
                                    <label class="form-check-label">Activo</label>
                                </div>
                                <input type="text" class="form-control mt-2" 
                                       placeholder="Título personalizado" 
                                       value="${menu.custom_title || ''}"
                                       onchange="updateCompanyMenuTitle(${config.company_id}, ${menu.id_menu}, this.value)">
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        }

        // Alternar menú de empresa
        function toggleCompanyMenu(companyId, menuId, isActive) {
            fetch('menu_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_company_menu',
                    company_id: companyId,
                    menu_id: menuId,
                    is_active: isActive
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Configuración actualizada', 'success');
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Actualizar título personalizado del menú de empresa
        function updateCompanyMenuTitle(companyId, menuId, title) {
            fetch('menu_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'update_company_menu_title',
                    company_id: companyId,
                    menu_id: menuId,
                    custom_title: title
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Título actualizado', 'success');
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>
