<?php
// Menú dinámico basado en permisos del usuario
// Este archivo debe ser incluido después de iniciar sesión y cargar las funciones

if (!isset($_SESSION['id_user'])) {
    return; // No mostrar menú si no hay sesión
}

$id_user = $_SESSION['id_user'];
$permisos_usuario = obtenerPermisosUsuario($id_user);

// Crear array de permisos para verificación rápida
$permisos_array = array_column($permisos_usuario, 'name');
?>

<!-- Menú de Administración -->
<?php if (in_array('ver_menu_admin', $permisos_array)): ?>
    <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-cogs"></i> Admin
        </a>
        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
            
            <!-- Opciones de Superadmin -->
            <?php if (in_array('admin_panel', $permisos_array)): ?>
                <li><a class="dropdown-item" href="admin/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Panel Principal
                </a></li>
                <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            
            <?php if (in_array('manage_companies', $permisos_array)): ?>
                <li><a class="dropdown-item" href="admin/companies.php">
                    <i class="fas fa-building"></i> Gestionar Empresas
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('admin_services', $permisos_array)): ?>
                <li><a class="dropdown-item" href="admin/services.php">
                    <i class="fas fa-cogs"></i> Servicios (Admin)
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('audit_logs', $permisos_array)): ?>
                <li><a class="dropdown-item" href="admin/audit_logs.php">
                    <i class="fas fa-history"></i> Logs de Auditoría
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('manage_permissions', $permisos_array)): ?>
                <li><a class="dropdown-item" href="admin/role_permissions.php">
                    <i class="fas fa-key"></i> Permisos por Rol
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('manage_languages', $permisos_array)): ?>
                <li><a class="dropdown-item" href="lang/manage.php">
                    <i class="fas fa-language"></i> Gestionar Idiomas
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('manage_superadmins', $permisos_array)): ?>
                <li><a class="dropdown-item" href="admin/superadmins.php">
                    <i class="fas fa-user-shield"></i> SuperAdministradores
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('manage_users', $permisos_array)): ?>
                <li><a class="dropdown-item" href="admin/users.php">
                    <i class="fas fa-users"></i> Gestionar Usuarios
                </a></li>
            <?php endif; ?>
            
            <!-- Opciones de Admin de Empresa -->
            <?php if (in_array('manage_services', $permisos_array)): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="services.php">
                    <i class="fas fa-list"></i> Mis Servicios
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('new_service', $permisos_array)): ?>
                <li><a class="dropdown-item" href="new_service.php">
                    <i class="fas fa-plus"></i> Nuevo Servicio
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('invite_users', $permisos_array)): ?>
                <li><a class="dropdown-item" href="invite_users.php">
                    <i class="fas fa-user-plus"></i> Invitar Usuarios
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('company_settings', $permisos_array)): ?>
                <li><a class="dropdown-item" href="company-settings.php">
                    <i class="fas fa-cog"></i> Configuración de Empresa
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('manage_tickets', $permisos_array)): ?>
                <li><a class="dropdown-item" href="tickets.php">
                    <i class="fas fa-ticket-alt"></i> Gestión de Tickets
                </a></li>
            <?php endif; ?>
            
            <!-- Opciones de Usuario -->
            <?php if (in_array('edit_profile', $permisos_array)): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user-edit"></i> Editar Perfil
                </a></li>
            <?php endif; ?>
            
            <?php if (in_array('view_tickets', $permisos_array)): ?>
                <li><a class="dropdown-item" href="my_tickets.php">
                    <i class="fas fa-ticket-alt"></i> Mis Tickets
                </a></li>
            <?php endif; ?>
            
        </ul>
    </li>
<?php endif; ?> 