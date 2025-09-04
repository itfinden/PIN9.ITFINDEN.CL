<!DOCTYPE html>
<html lang="en" <?php echo $theme_attributes ?? ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Pin9 - Dashboard Principal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/Modules/Content/css/style.css?v=<?php echo time(); ?>">
    <script src="js/theme-switcher.js"></script>
</head>

<body>
<?php require_once __DIR__ . "/partials/modern_navbar.php"; ?>

<div class="modern-dashboard">
    <div class="container">
        <!-- Sección de bienvenida -->
        <div class="welcome-section">
            <h1 class="welcome-title">
                <i class="fas fa-home mr-3"></i>
                Bienvenido a Pin9
            </h1>
            <p class="welcome-subtitle">
                <?php if ($is_superadmin): ?>
                    Panel de Administración Global - Gestión de todas las empresas
                <?php elseif ($is_company_admin): ?>
                    Panel de Administración - <?= htmlspecialchars($empresa['company_name'] ?? 'Tu Empresa') ?>
                <?php else: ?>
                    Gestiona tus proyectos, tareas y calendario de forma profesional
                <?php endif; ?>
            </p>
            
            <!-- Estadísticas rápidas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="fas fa-tasks stat-icon"></i>
                    <div class="stat-number"><?= $tareas_pendientes ?></div>
                    <div class="stat-label">
                        <?php if ($is_superadmin): ?>
                            Tickets Pendientes
                        <?php else: ?>
                            Tareas Pendientes
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-project-diagram stat-icon"></i>
                    <div class="stat-number"><?= $proyectos_activos ?></div>
                    <div class="stat-label">
                        <?php if ($is_superadmin): ?>
                            Proyectos Globales
                        <?php else: ?>
                            Proyectos Activos
                        <?php endif; ?>
                    </div>
                </div>
                <div class="stat-card">
                    <i class="fas fa-calendar-check stat-icon"></i>
                    <div class="stat-number"><?= $eventos_hoy ?></div>
                    <div class="stat-label">
                        <?php if ($is_superadmin): ?>
                            Eventos Hoy
                        <?php else: ?>
                            Eventos Hoy
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Acciones principales -->
        <div class="main-actions">
            <a href="today.php" class="action-card">
                <i class="fas fa-calendar-day action-icon"></i>
                <h3 class="action-title">Hoy</h3>
                <p class="action-description">
                    Revisa tus tareas y eventos del día. Mantén el control de lo que necesitas hacer hoy.
                </p>
            </a>
            
            <a href="projects.php" class="action-card">
                <i class="fas fa-project-diagram action-icon"></i>
                <h3 class="action-title">Proyectos</h3>
                <p class="action-description">
                    Gestiona todos tus proyectos. Crea, organiza y da seguimiento a tus iniciativas.
                </p>
            </a>
            
            <a href="calendar.php" class="action-card">
                <i class="fas fa-calendar-alt action-icon"></i>
                <h3 class="action-title">Calendario</h3>
                <p class="action-description">
                    Visualiza y programa eventos. Mantén una vista completa de tu agenda.
                </p>
            </a>

            <a href="/evento_dashboard.php" class="action-card">
                <i class="fas fa-glass-cheers action-icon"></i>
                <h3 class="action-title">Eventos</h3>
                <p class="action-description">
                    Administra fiestas, matrimonios y más. Crea eventos, subeventos e invitados.
                </p>
            </a>
            
            <a href="tickets.php" class="action-card">
                <i class="fas fa-ticket-alt action-icon"></i>
                <h3 class="action-title">Tickets</h3>
                <p class="action-description">
                    Gestiona tickets y solicitudes. Crea, asigna y da seguimiento a tareas y problemas.
                </p>
            </a>
        </div>
        
        <!-- Acciones rápidas -->
        <div class="quick-actions">
            <h3><i class="fas fa-bolt mr-2"></i>Acciones Rápidas</h3>
            <div class="quick-buttons">
                <?php if (in_array('new_service', $permisos_nombres) || $is_superadmin): ?>
                <a href="new_service.php" class="quick-btn">
                    <i class="fas fa-plus"></i>
                    Nuevo Servicio
                </a>
                <?php endif; ?>
                
                <?php if (in_array('invite_users', $permisos_nombres) || $is_company_admin || $is_superadmin): ?>
                <a href="invite-user.php" class="quick-btn">
                    <i class="fas fa-user-plus"></i>
                    Invitar Usuario
                </a>
                <?php endif; ?>
                
                <?php if (in_array('company_settings', $permisos_nombres) || $is_company_admin || $is_superadmin): ?>
                <a href="company-settings.php" class="quick-btn">
                    <i class="fas fa-cog"></i>
                    Configuración
                </a>
                <?php endif; ?>
                
                <?php if (in_array('view_tickets', $permisos_nombres) || $is_superadmin): ?>
                <a href="tickets.php" class="quick-btn">
                    <i class="fas fa-ticket-alt"></i>
                    Ver Tickets
                </a>
                <?php endif; ?>
                
                <?php if ($is_superadmin): ?>
                <a href="admin/dashboard.php" class="quick-btn">
                    <i class="fas fa-shield-alt"></i>
                    Panel Admin
                </a>
                <?php endif; ?>
                
                <?php if (in_array('manage_tickets', $permisos_nombres) || $is_superadmin): ?>
                <a href="new_ticket.php" class="quick-btn">
                    <i class="fas fa-plus-circle"></i>
                    Nuevo Ticket
                </a>
                <?php endif; ?>
                
                <?php if (in_array('manage_calendars', $permisos_nombres) || $is_company_admin || $is_superadmin): ?>
                <a href="calendar.php" class="quick-btn">
                    <i class="fas fa-calendar-alt"></i>
                    Calendario
                </a>
                <?php endif; ?>

                <a href="/evento_dashboard.php" class="quick-btn">
                    <i class="fas fa-glass-cheers"></i>
                    Eventos
                </a>
            </div>
        </div>
    </div>
</div>

<?php require "footer.php"; ?>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script>
// Animaciones de entrada
document.addEventListener("DOMContentLoaded", function() {
    const cards = document.querySelectorAll(".stat-card, .action-card");
    
    cards.forEach((card, index) => {
        card.style.opacity = "0";
        card.style.transform = "translateY(30px)";
        
        setTimeout(() => {
            card.style.transition = "all 0.6s ease";
            card.style.opacity = "1";
            card.style.transform = "translateY(0)";
        }, index * 100);
    });
});

// Efecto de contador animado para las estadísticas
function animateCounter(element, target) {
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 30);
}

// Iniciar animación de contadores cuando las tarjetas sean visibles
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const numberElement = entry.target.querySelector(".stat-number");
            const target = parseInt(numberElement.textContent);
            animateCounter(numberElement, target);
            observer.unobserve(entry.target);
        }
    });
});

document.querySelectorAll(".stat-card").forEach(card => {
    observer.observe(card);
});
</script>
<div style="position:fixed;bottom:0;right:0;z-index:9999;font-size:9px;opacity:0.5;background:#fff;color:#333;padding:2px 6px;border:1px solid #ccc;border-radius:3px;max-width:350px;max-height:220px;overflow:auto;">
<b>SESSION:</b><br>
<?php
foreach ($_SESSION as $k => $v) {
    echo htmlspecialchars($k) . ': ' . htmlspecialchars(print_r($v, true)) . '<br>';
}
?>
<b>FIN SESSION:</b><br>
</div>
</body>
</html>