<?php
/**
 * Sistema de verificación de permisos para Pin9
 * 
 * Este archivo contiene funciones para verificar permisos de acceso
 * y mostrar pantallas de error de manera profesional.
 * 
 * @author Pin9 Development Team
 * @version 1.0
 */

// Incluir dependencias necesarias
require_once __DIR__ . '/../db/functions.php';
require_once __DIR__ . '/../config/setting.php';

/**
 * Verifica si un usuario tiene permiso para acceder a una página específica
 * 
 * @param int $id_user ID del usuario a verificar
 * @param int $id_permission ID del permiso/página a verificar
 * @return void Si no tiene permiso, muestra mensaje y termina ejecución
 */
function verificarPermisoVista($id_user, $id_permission) {
    if (empty($id_user)) {
        header('Location: ' . SITE_URL . 'login.php');
        exit;
    }
    // Obtener la URL actual relativa
    $current_url = $_SERVER['SCRIPT_NAME'];
    $current_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $current_url);

    // Conexión a la base de datos
    $database = new Database();
    $connection = $database->connection();

    // Buscar la URL en la tabla permissions
    $stmt = $connection->prepare("SELECT id_permission, name, url FROM permissions WHERE url = :url");
    $stmt->execute([':url' => $current_url]);
    $perm = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($perm) {
        // Si la URL existe, verifica que el ID coincida
        if ($perm['id_permission'] != $id_permission) {
            echo '<div style="background:yellow; color:black; padding:10px; border:2px solid orange; margin:20px 0;">
                <b>ADVERTENCIA DE PERMISOS:</b><br>
                <b>Página:</b> <code>' . htmlspecialchars($current_url) . '</code><br>
                El ID de permiso usado en el código (<b>' . $id_permission . '</b>) no coincide con el ID registrado para esta URL (<b>' . $perm['id_permission'] . '</b>).<br>
                Debes usar: <code>verificarPermisoVista($_SESSION[\'id_user\'], ' . $perm['id_permission'] . ');</code>
            </div>';
        }
    } else {
        // Si la URL no existe en la tabla, agregarla automáticamente
        $suggested_name = basename($current_url, ".php");
        $suggested_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $suggested_name);
        $suggested_name = strtolower($suggested_name);
        $description = 'Permiso auto-creado para ' . $current_url;
        $titulo = ucfirst(str_replace('_', ' ', $suggested_name));
        $insert = $connection->prepare("INSERT INTO permissions (name, description, Titulo, url) VALUES (:name, :description, :titulo, :url)");
        $insert->execute([
            ':name' => $suggested_name,
            ':description' => $description,
            ':titulo' => $titulo,
            ':url' => $current_url
        ]);
        $new_id = $connection->lastInsertId();
        echo '<div style="background:orange; color:white; padding:10px; border:2px solid red; margin:20px 0;">
            <b>PERMISO AGREGADO AUTOMÁTICAMENTE:</b><br>
            Se ha agregado la URL <code>' . $current_url . '</code> a la tabla <b>permissions</b>.<br>
            Usa este ID en tu código: <code>verificarPermisoVista($_SESSION[\'id_user\'], ' . $new_id . ');</code><br>
            <b>Nombre sugerido:</b> <code>' . $suggested_name . '</code>
        </div>';
    }

    // Lógica normal de permisos
    if (!tienePermiso($id_user, $id_permission)) {
        require_once __DIR__ . '/../header.php';
        ?>
        <div class="container mt-5">
            <div class="modal show" tabindex="-1" style="display:block; background:rgba(0,0,0,0.3);">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Acceso denegado</h5>
                        </div>
                        <div class="modal-body">
                            <p>No tienes permiso para acceder a esta sección. (<b><?php echo $id_permission ?></b>)</p>
                        </div>
                        <div class="modal-footer">
                            <a href="main.php" class="btn btn-primary">Volver al inicio</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div style="position:fixed;bottom:0;right:0;z-index:9999;font-size:9px;opacity:0.5;background:#fff;color:#333;padding:2px 6px;border:1px solid #ccc;border-radius:3px;max-width:350px;max-height:120px;overflow:auto;">
<b>SESSION:</b><br>
<?php
foreach ($_SESSION as $k => $v) {
    echo htmlspecialchars($k) . ': ' . htmlspecialchars(print_r($v, true)) . '<br>';
}
?>
</div>
        <?php
            require_once __DIR__ . '/../footer.php';
            exit;
    }
}

/**
 * Verifica si un usuario tiene rol específico
 * 
 * @param int $id_user ID del usuario a verificar
 * @param string $required_role Rol requerido (ej: 'admin', 'superadmin')
 * @return void Si no tiene el rol, muestra mensaje y termina ejecución
 */
function verificarRolUsuario($id_user, $required_role) {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $stmt = $connection->prepare("
            SELECT u.user as username, r.name as role
            FROM users u
            JOIN user_roles ur ON u.id_user = ur.id_user
            JOIN roles r ON ur.id_role = r.id_role
            WHERE u.id_user = ? AND r.name = ?
            LIMIT 1
        ");
        
        $stmt->execute([$id_user, $required_role]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resultado) {
            // Obtener información del usuario para mostrar en el error
            $stmt = $connection->prepare("
                SELECT u.user as username, r.name as role
                FROM users u
                LEFT JOIN user_roles ur ON u.id_user = ur.id_user
                LEFT JOIN roles r ON ur.id_role = r.id_role
                WHERE u.id_user = ?
                LIMIT 1
            ");
            
            $stmt->execute([$id_user]);
            $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $username = $user_info ? $user_info['username'] : 'Usuario no encontrado';
            $current_role = $user_info ? ucfirst($user_info['role']) : 'Sin rol asignado';
            
            mostrarErrorAcceso(
                $username,
                $current_role,
                "Acceso restringido. Rol requerido: <strong>" . ucfirst($required_role) . "</strong>"
            );
            exit;
        }
        
    } catch (PDOException $e) {
        mostrarErrorAcceso(
            "Error del sistema",
            "",
            "Error al verificar rol: " . htmlspecialchars($e->getMessage())
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
        <title>Acceso denegado - Pin9</title>
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
            .logo-container {
                text-align: center;
                margin-bottom: 2rem;
            }
            .logo-container img {
                height: 60px;
                width: auto;
            }
        </style>
    </head>
    <body>
        <div class="container access-container">
            <div class="logo-container">
                <img src="/img/logo.png" alt="Pin9 Logo">
            </div>
            
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h4><i class="bi bi-shield-lock"></i> Acceso restringido</h4>
                </div>
                <div class="card-body">
                    <?php if ($nombre_usuario): ?>
                        <p class="mb-2">Usuario: <strong><?= htmlspecialchars($nombre_usuario) ?></strong></p>
                    <?php endif; ?>
                    
                    <?php if ($rol_usuario): ?>
                        <p class="mb-2">Rol actual: <strong><?= htmlspecialchars($rol_usuario) ?></strong></p>
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

/**
 * Obtiene todos los permisos disponibles
 * 
 * @return array Lista de permisos con id, nombre y título
 */
function obtenerPermisosDisponibles() {
    $database = new Database();
    $connection = $database->connection();
    
    try {
        $stmt = $connection->prepare("
            SELECT id_permission, name, Titulo, description, Url
            FROM permissions
            ORDER BY id_permission
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error obteniendo permisos: " . $e->getMessage());
        return [];
    }
}

// Nota: Las funciones tienePermiso(), tieneRol(), obtenerPermisosUsuario(), etc.
// están definidas en db/functions.php para evitar duplicación

// Ejemplos de uso:
// verificarPermisoVista($_SESSION['id_user'], 1); // Verificar acceso a página ID 1
// verificarRolUsuario($_SESSION['id_user'], 'admin'); // Verificar rol admin
// if (tienePermiso($_SESSION['id_user'], 5)) { /* mostrar contenido */ }
?>