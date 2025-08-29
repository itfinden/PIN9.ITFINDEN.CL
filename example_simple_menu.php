<?php
/**
 * Ejemplo de uso del Sistema Simple de Menús
 */

require_once 'db/connection.php';
require_once 'includes/simple_menu_renderer.php';

// Simular un usuario logueado (en producción esto vendría de la sesión)
$userId = 1; // ID del usuario actual

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Simple de Menús - PIN9</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #f8f9fa;
            border-right: 1px solid #dee2e6;
        }
        .module-card {
            transition: transform 0.2s;
        }
        .module-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar con menú vertical -->
            <div class="col-md-3 sidebar p-3">
                <h5 class="mb-3">📱 Menú Principal</h5>
                <?php echo render_simple_menu($userId, 'vertical', [
                    'menu_class' => 'nav flex-column',
                    'link_class' => 'nav-link text-dark'
                ]); ?>
            </div>
            
            <!-- Contenido principal -->
            <div class="col-md-9 p-4">
                <h1>🎯 Sistema Simple de Menús</h1>
                <p class="lead">Basado en módulos y roles - Sin complicaciones</p>
                
                <hr>
                
                <!-- Menú como navbar -->
                <h4>🌐 Menú Horizontal</h4>
                <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
                    <?php echo render_simple_menu($userId, 'navbar', [
                        'menu_class' => 'navbar-nav me-auto',
                        'link_class' => 'nav-link'
                    ]); ?>
                </nav>
                
                <hr>
                
                <!-- Menú como cards -->
                <h4>🃏 Módulos Disponibles</h4>
                <?php echo render_simple_menu($userId, 'cards', [
                    'container_class' => 'row g-3',
                    'card_class' => 'col-md-4 col-lg-3'
                ]); ?>
                
                <hr>
                
                <!-- Información del usuario -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>👤 Información del Usuario</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>ID Usuario:</strong> <?php echo $userId; ?></p>
                                <p><strong>Rol:</strong> 
                                    <?php 
                                    $renderer = new SimpleMenuRenderer($pdo);
                                    echo ucfirst($renderer->getUserRole($userId)); 
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>🔐 Permisos de Módulos</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $modules = ['dashboard', 'calendar', 'projects', 'tickets', 'admin'];
                                foreach ($modules as $module) {
                                    $canAccess = can_access_module($userId, $module);
                                    $canEdit = can_edit_module($userId, $module);
                                    $status = $canAccess ? '✅' : '❌';
                                    $editStatus = $canEdit ? '✏️' : '👁️';
                                    
                                    echo "<p><strong>$module:</strong> $status Acceso, $editStatus " . 
                                         ($canEdit ? 'Editar' : 'Solo Ver') . "</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
