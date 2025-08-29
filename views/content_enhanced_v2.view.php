<!DOCTYPE html>
<html lang="en" <?php echo $theme_attributes ?? ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Dashboard Principal Mejorado</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/Modules/Content/css/style.css">
    <script src="js/theme-switcher.js"></script>
    <style>
        .modern-dashboard {
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .welcome-title {
            font-size: 2.5rem;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: #667eea;
        }
        
        .stat-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 20px;
            display: block;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            font-family: 'Arial', sans-serif;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .main-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        
        .action-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 3px solid transparent;
            text-align: center;
        }
        
        .action-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            border-color: #667eea;
            text-decoration: none;
            color: #333;
        }
        
        .action-icon {
            font-size: 3.5rem;
            color: #667eea;
            margin-bottom: 20px;
            display: block;
        }
        
        .action-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }
        
        .action-description {
            color: #666;
            line-height: 1.6;
            font-size: 1rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .quick-btn {
            background: white;
            color: #667eea;
            padding: 20px;
            border-radius: 15px;
            text-decoration: none;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            font-weight: 600;
        }
        
        .quick-btn:hover {
            transform: translateY(-5px);
            border-color: #667eea;
            text-decoration: none;
            color: #667eea;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .quick-btn i {
            font-size: 2rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .recent-activity {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin: 30px 0;
        }
        
        .activity-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.2rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .activity-time {
            color: #666;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main-actions {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .welcome-title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
<?php require_once __DIR__ . "/partials/modern_navbar.php"; ?>

<div class="modern-dashboard">
    <div class="container-fluid">
        <!-- Sección de bienvenida -->
        <div class="welcome-section">
            <h1 class="welcome-title">
                <i class="fas fa-home mr-3"></i>
                Bienvenido a Pin9
            </h1>
            <p class="welcome-subtitle">
                <?php if ($is_superadmin): ?>
                    🚀 Panel de Administración Global - Gestión completa de todas las empresas
                <?php elseif ($is_company_admin): ?>
                    🏢 Panel de Administración - <?= htmlspecialchars($empresa['company_name'] ?? 'Tu Empresa') ?>
                <?php else: ?>
                    💼 Gestiona tus proyectos, tareas y calendario de forma profesional
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
                        🎫 Tickets Pendientes
                    <?php else: ?>
                        📋 Tareas Pendientes
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-project-diagram stat-icon"></i>
                <div class="stat-number"><?= $proyectos_activos ?></div>
                <div class="stat-label">
                    <?php if ($is_superadmin): ?>
                        🌍 Proyectos Globales
                    <?php else: ?>
                        📊 Proyectos Activos
                    <?php endif; ?>
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-check stat-icon"></i>
                <div class="stat-number"><?= $eventos_hoy ?></div>
                <div class="stat-label">
                    📅 Eventos Hoy
                </div>
            </div>
            <div class="stat-card">
                <i class="fas fa-users stat-icon"></i>
                <div class="stat-number"><?= $equipos_activos ?></div>
                <div class="stat-label">
                    <?php if ($is_superadmin): ?>
                        🏢 Empresas Activas
                    <?php elseif ($is_company_admin): ?>
                        👥 Miembros Equipo
                    <?php else: ?>
                        🤝 Equipos Activos
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Acciones principales -->
        <div class="main-actions">
            <a href="today.php" class="action-card">
                <i class="fas fa-calendar-day action-icon"></i>
                <h3 class="action-title">📅 Hoy</h3>
                <p class="action-description">
                    Revisa tus tareas y eventos del día. Mantén el control de lo que necesitas hacer hoy.
                </p>
            </a>
            
            <a href="projects.php" class="action-card">
                <i class="fas fa-project-diagram action-icon"></i>
                <h3 class="action-title">📊 Proyectos</h3>
                <p class="action-description">
                    Gestiona todos tus proyectos. Crea, organiza y da seguimiento a tus iniciativas.
                </p>
            </a>
            
            <a href="calendar.php" class="action-card">
                <i class="fas fa-calendar-alt action-icon"></i>
                <h3 class="action-title">🗓️ Calendario</h3>
                <p class="action-description">
                    Organiza tu tiempo y eventos. Visualiza tu agenda de forma clara y eficiente.
                </p>
            </a>
            
            <a href="tickets.php" class="action-card">
                <i class="fas fa-ticket-alt action-icon"></i>
                <h3 class="action-title">🎫 Tickets</h3>
                <p class="action-description">
                    Gestiona tickets de soporte. Resuelve problemas y da seguimiento a solicitudes.
                </p>
            </a>
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
        
        <!-- Actividad reciente -->
        <div class="recent-activity">
            <h3 class="activity-title">
                <i class="fas fa-clock mr-2"></i>
                Actividad Reciente
            </h3>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-user"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">Sesión iniciada exitosamente</div>
                    <div class="activity-time">Hace 5 minutos</div>
                </div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">Dashboard actualizado</div>
                    <div class="activity-time">Hace 10 minutos</div>
                </div>
            </div>
            
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-text">Notificaciones revisadas</div>
                    <div class="activity-time">Hace 15 minutos</div>
                </div>
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
    const cards = document.querySelectorAll(".stat-card, .action-card, .quick-btn");
    
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

// Efectos hover mejorados
document.querySelectorAll('.action-card, .quick-btn').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});
</script>

</body>
</html>
