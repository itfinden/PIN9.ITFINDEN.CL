<?php
// Menú dinámico inspirado en Apple TV
// Este archivo debe ser incluido después de iniciar sesión y cargar las funciones

if (!isset($_SESSION['id_user'])) {
    return; // No mostrar menú si no hay sesión
}

$id_user = $_SESSION['id_user'];
$permisos_usuario = obtenerPermisosUsuario($id_user);

// Crear array de permisos para verificación rápida
$permisos_array = array_column($permisos_usuario, 'name');

// Solo mostrar si tiene permiso para ver el menú admin
if (!in_array('ver_menu_admin', $permisos_array)) {
    return;
}
?>

<!-- Apple TV Style Menu -->
<style>
.apple-tv-menu {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.95);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    z-index: 9999;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    align-items: center;
    justify-content: center;
}

.apple-tv-menu.active {
    opacity: 1;
    visibility: visible;
}

.apple-tv-menu-container {
    max-width: 1200px;
    width: 100%;
    padding: 2rem;
}

.apple-tv-menu-header {
    text-align: center;
    margin-bottom: 3rem;
}

.apple-tv-menu-title {
    font-size: 3rem;
    font-weight: 300;
    color: #ffffff;
    margin: 0;
    letter-spacing: -0.02em;
}

.apple-tv-menu-subtitle {
    font-size: 1.2rem;
    color: #86868b;
    margin-top: 0.5rem;
    font-weight: 400;
}

.apple-tv-menu-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.apple-tv-menu-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 2rem;
    text-decoration: none;
    color: #ffffff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.apple-tv-menu-item:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}

.apple-tv-menu-item:active {
    transform: translateY(-2px);
}

.apple-tv-menu-item-icon {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
    opacity: 0.9;
}

.apple-tv-menu-item-title {
    font-size: 1.3rem;
    font-weight: 500;
    margin-bottom: 0.5rem;
    letter-spacing: -0.01em;
}

.apple-tv-menu-item-description {
    font-size: 0.9rem;
    color: #86868b;
    line-height: 1.4;
}

.apple-tv-menu-footer {
    text-align: center;
    margin-top: 2rem;
}

.apple-tv-menu-close {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #ffffff;
    padding: 0.75rem 2rem;
    border-radius: 25px;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.apple-tv-menu-close:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.apple-tv-menu-trigger {
    background: linear-gradient(135deg, #007AFF, #5856D6);
    border: none;
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(0, 122, 255, 0.3);
}

.apple-tv-menu-trigger:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 122, 255, 0.4);
}

.apple-tv-menu-trigger:active {
    transform: translateY(0);
}

/* Responsive */
@media (max-width: 768px) {
    .apple-tv-menu-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .apple-tv-menu-title {
        font-size: 2rem;
    }
    
    .apple-tv-menu-item {
        padding: 1.5rem;
    }
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.apple-tv-menu-item {
    animation: fadeInUp 0.6s ease forwards;
}

.apple-tv-menu-item:nth-child(1) { animation-delay: 0.1s; }
.apple-tv-menu-item:nth-child(2) { animation-delay: 0.2s; }
.apple-tv-menu-item:nth-child(3) { animation-delay: 0.3s; }
.apple-tv-menu-item:nth-child(4) { animation-delay: 0.4s; }
.apple-tv-menu-item:nth-child(5) { animation-delay: 0.5s; }
.apple-tv-menu-item:nth-child(6) { animation-delay: 0.6s; }
</style>

<!-- Botón para abrir el menú -->
<button class="apple-tv-menu-trigger" onclick="openAppleTVMenu()">
    <i class="fas fa-cogs"></i> Admin Panel
</button>

<!-- Menú Apple TV -->
<div class="apple-tv-menu" id="appleTVMenu">
    <div class="apple-tv-menu-container">
        <div class="apple-tv-menu-header">
            <h1 class="apple-tv-menu-title">Admin Panel</h1>
            <p class="apple-tv-menu-subtitle">Gestiona tu sistema de manera elegante</p>
        </div>
        
        <div class="apple-tv-menu-grid">
            <!-- Opciones de Superadmin -->
            <?php if (in_array('admin_panel', $permisos_array)): ?>
                <a href="admin/dashboard.php" class="apple-tv-menu-item">
                    <i class="fas fa-tachometer-alt apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Panel Principal</div>
                    <div class="apple-tv-menu-item-description">Vista general del sistema y estadísticas</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('manage_companies', $permisos_array)): ?>
                <a href="admin/companies.php" class="apple-tv-menu-item">
                    <i class="fas fa-building apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Gestionar Empresas</div>
                    <div class="apple-tv-menu-item-description">Administra todas las empresas del sistema</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('admin_services', $permisos_array)): ?>
                <a href="admin/services.php" class="apple-tv-menu-item">
                    <i class="fas fa-cogs apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Servicios (Admin)</div>
                    <div class="apple-tv-menu-item-description">Gestiona servicios de todas las empresas</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('audit_logs', $permisos_array)): ?>
                <a href="admin/audit_logs.php" class="apple-tv-menu-item">
                    <i class="fas fa-history apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Logs de Auditoría</div>
                    <div class="apple-tv-menu-item-description">Revisa la actividad del sistema</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('manage_permissions', $permisos_array)): ?>
                <a href="admin/role_permissions.php" class="apple-tv-menu-item">
                    <i class="fas fa-key apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Permisos por Rol</div>
                    <div class="apple-tv-menu-item-description">Configura permisos del sistema</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('manage_languages', $permisos_array)): ?>
                <a href="lang/manage.php" class="apple-tv-menu-item">
                    <i class="fas fa-language apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Gestionar Idiomas</div>
                    <div class="apple-tv-menu-item-description">Configura traducciones del sistema</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('manage_superadmins', $permisos_array)): ?>
                <a href="admin/superadmins.php" class="apple-tv-menu-item">
                    <i class="fas fa-user-shield apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">SuperAdministradores</div>
                    <div class="apple-tv-menu-item-description">Gestiona superadmins del sistema</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('manage_users', $permisos_array)): ?>
                <a href="admin/users.php" class="apple-tv-menu-item">
                    <i class="fas fa-users apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Gestionar Usuarios</div>
                    <div class="apple-tv-menu-item-description">Administra usuarios del sistema</div>
                </a>
            <?php endif; ?>
            
            <!-- Opciones de Admin de Empresa -->
            <?php if (in_array('manage_services', $permisos_array)): ?>
                <a href="services.php" class="apple-tv-menu-item">
                    <i class="fas fa-list apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Mis Servicios</div>
                    <div class="apple-tv-menu-item-description">Gestiona los servicios de tu empresa</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('new_service', $permisos_array)): ?>
                <a href="new_service.php" class="apple-tv-menu-item">
                    <i class="fas fa-plus apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Nuevo Servicio</div>
                    <div class="apple-tv-menu-item-description">Crea un nuevo servicio para tu empresa</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('invite_users', $permisos_array)): ?>
                <a href="invite_users.php" class="apple-tv-menu-item">
                    <i class="fas fa-user-plus apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Invitar Usuarios</div>
                    <div class="apple-tv-menu-item-description">Invita nuevos usuarios a tu empresa</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('company_settings', $permisos_array)): ?>
                <a href="company-settings.php" class="apple-tv-menu-item">
                    <i class="fas fa-cog apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Configuración de Empresa</div>
                    <div class="apple-tv-menu-item-description">Gestiona la configuración de tu empresa</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('manage_tickets', $permisos_array)): ?>
                <a href="tickets.php" class="apple-tv-menu-item">
                    <i class="fas fa-ticket-alt apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Gestión de Tickets</div>
                    <div class="apple-tv-menu-item-description">Administra tickets de soporte</div>
                </a>
            <?php endif; ?>
            
            <!-- Opciones de Usuario -->
            <?php if (in_array('edit_profile', $permisos_array)): ?>
                <a href="profile.php" class="apple-tv-menu-item">
                    <i class="fas fa-user-edit apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Editar Perfil</div>
                    <div class="apple-tv-menu-item-description">Actualiza tu información personal</div>
                </a>
            <?php endif; ?>
            
            <?php if (in_array('view_tickets', $permisos_array)): ?>
                <a href="my_tickets.php" class="apple-tv-menu-item">
                    <i class="fas fa-ticket-alt apple-tv-menu-item-icon"></i>
                    <div class="apple-tv-menu-item-title">Mis Tickets</div>
                    <div class="apple-tv-menu-item-description">Revisa tus tickets de soporte</div>
                </a>
            <?php endif; ?>
        </div>
        
        <div class="apple-tv-menu-footer">
            <button class="apple-tv-menu-close" onclick="closeAppleTVMenu()">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<script>
function openAppleTVMenu() {
    document.getElementById('appleTVMenu').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeAppleTVMenu() {
    document.getElementById('appleTVMenu').classList.remove('active');
    document.body.style.overflow = 'auto';
}

// Cerrar con ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeAppleTVMenu();
    }
});

// Cerrar al hacer clic fuera del menú
document.getElementById('appleTVMenu').addEventListener('click', function(event) {
    if (event.target === this) {
        closeAppleTVMenu();
    }
});
</script> 