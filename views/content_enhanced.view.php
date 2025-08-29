<!DOCTYPE html>
<html lang="en" <?php echo $theme_attributes ?? ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Dashboard Principal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/Modules/Content/css/style.css">
    <script src="js/theme-switcher.js"></script>
    <style>
        .sidebar-menu {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu .nav-link:hover,
        .sidebar-menu .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        
        .sidebar-menu .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-title {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .welcome-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }
        
        .quick-btn {
            background: white;
            color: #667eea;
            padding: 15px;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .quick-btn:hover {
            transform: translateY(-3px);
            border-color: #667eea;
            text-decoration: none;
            color: #667eea;
        }
        
        .quick-btn i {
            font-size: 1.5rem;
            margin-bottom: 8px;
            display: block;
        }
    </style>
</head>

<body>
<?php require_once __DIR__ . "/partials/modern_navbar.php"; ?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar con menú dinámico -->
        <div class="col-md-3 col-lg-2 sidebar-menu">
            <div class="text-center mb-4">
                <h5><i class="fas fa-bars"></i> Menú Principal</h5>
                <hr class="bg-light">
            </div>
            
            <?php 
            // Incluir el renderizador de menús simple
            require_once 'includes/simple_menu_renderer.php';
            
            // Renderizar menú vertical
            echo render_simple_menu($id_user, 'vertical', [
                'menu_class' => 'nav flex-column',
                'link_class' => 'nav-link',
                'current_url' => $_SERVER['REQUEST_URI']
            ]);
            ?>
        </div>
        
        <!-- Contenido principal -->
        <div class="col-md-9 col-lg-10 main-content">
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
            </div>
            
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
                <div class="stat-card">
                    <i class="fas fa-users stat-icon"></i>
                    <div class="stat-number"><?= $equipos_activos ?></div>
                    <div class="stat-label">
                        <?php if ($is_superadmin): ?>
                            Empresas Activas
                        <?php elseif ($is_company_admin): ?>
                            Miembros Equipo
                        <?php else: ?>
                            Equipos Activos
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <a href="today.php" class="quick-btn">
                    <i class="fas fa-calendar-day"></i>
                    Hoy
                </a>
                
                <a href="projects.php" class="quick-btn">
                    <i class="fas fa-project-diagram"></i>
                    Proyectos
                </a>
                
                <a href="tickets.php" class="quick-btn">
                    <i class="fas fa-ticket-alt"></i>
                    Tickets
                </a>
                
                <a href="calendar.php" class="quick-btn">
                    <i class="fas fa-calendar-alt"></i>
                    Calendario
                </a>
                
                <a href="services.php" class="quick-btn">
                    <i class="fas fa-cogs"></i>
                    Servicios
                </a>
                
                <?php if ($is_superadmin): ?>
                <a href="admin/companies.php" class="quick-btn">
                    <i class="fas fa-building"></i>
                    Empresas
                </a>
                <?php endif; ?>
                
                <?php if ($is_superadmin): ?>
                <a href="admin/audit_logs.php" class="quick-btn">
                    <i class="fas fa-shield-alt"></i>
                    Auditoría
                </a>
                <?php endif; ?>
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
    const cards = document.querySelectorAll(".stat-card, .quick-btn");
    
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

</body>
</html>
