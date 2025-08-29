<?php
/**
 * Verifica si un usuario tiene permiso para acceder a una página específica
 * 
 * @param int $id_user ID del usuario a verificar
 * @param int $id_pagina ID de la página a verificar
 * @return void Si no tiene permiso, muestra mensaje y termina ejecución
 */
function verificarPermisoVista($id_user, $id_pagina) {
    // 1. Conexión a la base de datos
    $database = new Database();
    $connection = $database->connection();
    
    try {
        // 2. Consulta optimizada para verificar permisos
        $stmt = $connection->prepare("
            SELECT 
                g.username,
                g.id_role,
                g.rol_name as rol
            FROM 
                GET_ACCESS g
            WHERE 
                g.id_user = ? 
                AND g.id_permission = ?
            LIMIT 1
        ");
        
        $stmt->execute([$id_user, $id_pagina]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 3. Verificación de resultados
        if (!$resultado) {
            // No tiene permiso o no existe el usuario/permiso
            mostrarErrorAcceso(
                "Usuario no identificado", 
                "Rol no asignado",
                "No tienes permisos para acceder a esta página"
            );
            exit;
        }
        
        // 4. Si llegó aquí, tiene permiso y continúa la ejecución
        
    } catch (PDOException $e) {
        // Manejo de errores de base de datos
        mostrarErrorAcceso(
            "Error del sistema",
            "",
            "Error al verificar permisos: " . htmlspecialchars($e->getMessage())
        );
        exit;
    }
}

/**
 * Muestra pantalla de error de acceso (versión mejorada)
 */
function mostrarErrorAcceso($nombre_usuario = "", $rol_usuario = "", $mensaje = "") {
    // Configuración previa para asegurar que no hay output antes
    if (headers_sent()) {
        ob_end_clean();
    }
    
    // Contenido HTML
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <title>Acceso denegado</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            .access-container {
                max-width: 600px;
                margin-top: 5rem;
            }
            body {
                background-color: #f8f9fa;
            }
        </style>
    </head>
    <body>
        <div class="container access-container">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4><i class="bi bi-shield-lock"></i> Acceso restringido</h4>
                </div>
                <div class="card-body">
                    <?php if ($nombre_usuario): ?>
                        <p class="mb-2">Usuario: <strong><?= htmlspecialchars($nombre_usuario) ?></strong></p>
                    <?php endif; ?>
                    
                    <?php if ($rol_usuario): ?>
                        <p class="mb-2">Rol: <strong><?= htmlspecialchars($rol_usuario) ?></strong></p>
                    <?php endif; ?>
                    
                    <div class="alert alert-danger mt-3">
                        <?= $mensaje ?>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="javascript:history.back()" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver
                        </a>
                        <a href="index.php" class="btn btn-primary">
                            <i class="bi bi-house-door"></i> Ir al inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
    </html>
    <?php
    exit; // Asegura que el script termine aquí
}

// Ejemplo de uso:
// verificarPermisoVista($_SESSION['user_id'], 3);
?>