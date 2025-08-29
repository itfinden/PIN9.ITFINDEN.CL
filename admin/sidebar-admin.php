<?php /* Sidebar lateral con tema claro/oscuro para superadmin */ ?>
<style>
.sidebar-admin-light {
    min-height: 100vh;
    background: var(--bg-secondary);
    color: var(--text-primary);
    padding-top: 30px;
    position: fixed;
    left: 0;
    top: 0;
    width: 220px;
    z-index: 1000;
    border-right: 1px solid var(--border-color);
    transition: all var(--transition-speed) var(--transition-ease);
}
.sidebar-admin-light .nav-link {
    color: var(--text-primary);
    font-weight: 500;
    margin-bottom: 10px;
    transition: all var(--transition-speed) var(--transition-ease);
}
.sidebar-admin-light .nav-link.active, .sidebar-admin-light .nav-link:hover {
    background: var(--bg-card);
    color: var(--primary-color);
    border-radius: 5px;
}
@media (max-width: 991px) {
    .sidebar-admin-light {
        position: static;
        width: 100%;
        min-height: auto;
        padding-top: 10px;
    }
}
</style>
<nav class="col-lg-2 d-none d-lg-block sidebar-admin-light">
  <ul class="nav flex-column">
    <li class="nav-item"><a class="nav-link<?php if(strpos($_SERVER['SCRIPT_NAME'],'dashboard.php')!==false) echo ' active'; ?>" href="/admin/dashboard.php"><i class="fas fa-chart-bar mr-2"></i>Dashboard</a></li>
    <li class="nav-item"><a class="nav-link<?php if(strpos($_SERVER['SCRIPT_NAME'],'companies.php')!==false) echo ' active'; ?>" href="/admin/companies.php"><i class="fas fa-building mr-2"></i>Empresas</a></li>
    <li class="nav-item"><a class="nav-link<?php if(strpos($_SERVER['SCRIPT_NAME'],'services.php')!==false) echo ' active'; ?>" href="/admin/services.php"><i class="fas fa-cogs mr-2"></i>Servicios</a></li>
    <li class="nav-item"><a class="nav-link<?php if(strpos($_SERVER['SCRIPT_NAME'],'audit_logs')!==false) echo ' active'; ?>" href="/admin/audit_logs.php"><i class="fas fa-clipboard-list mr-2"></i>Auditoría</a></li>
    <li class="nav-item"><a class="nav-link<?php if(strpos($_SERVER['SCRIPT_NAME'],'role_permissions.php')!==false) echo ' active'; ?>" href="/admin/role_permissions.php"><i class="fas fa-key mr-2"></i>Permisos</a></li>
    <li class="nav-item"><a class="nav-link<?php if(strpos($_SERVER['SCRIPT_NAME'],'superadmins.php')!==false) echo ' active'; ?>" href="/admin/superadmins.php"><i class="fas fa-user-shield mr-2"></i>Superadmins</a></li>
    <li class="nav-item"><a class="nav-link<?php if(strpos($_SERVER['SCRIPT_NAME'],'manage.php')!==false) echo ' active'; ?>" href="/lang/manage.php"><i class="fas fa-language mr-2"></i>Idiomas</a></li>
  </ul>
</nav> 