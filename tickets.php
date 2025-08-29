<?php
session_start();
// Manejar cambio de idioma ANTES de cualquier output
require_once 'lang/language_handler.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

require_once 'db/functions.php';
require_once 'theme_handler.php';
require_once 'security/check_access.php';

// Verificar permiso para ver tickets
verificarPermisoVista($_SESSION['id_user'], 14); // manage_tickets

$database = new Database();
$connection = $database->connection();

// Obtener filtros
$status_filter = isset($_GET['status']) ? (int)$_GET['status'] : 0;
$priority_filter = isset($_GET['priority']) ? (int)$_GET['priority'] : 0;
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$company_filter = isset($_GET['company']) ? (int)$_GET['company'] : 0;

// Verificar si es superadmin
$is_superadmin = false;
if (isset($_SESSION['is_superadmin'])) {
    $is_superadmin = $_SESSION['is_superadmin'];
} elseif (isset($_SESSION['id_user'])) {
    // Fallback: verificar usando la función isSuperAdmin
    $is_superadmin = isSuperAdmin($_SESSION['id_user']);
    // Actualizar la sesión para futuras consultas
    $_SESSION['is_superadmin'] = $is_superadmin;
    

}



// Construir consulta base
$sql = "SELECT * FROM v_tickets_complete WHERE 1=1";
$params = [];

// Aplicar filtros
if ($status_filter > 0) {
    $sql .= " AND id_status = ?";
    $params[] = $status_filter;
}

if ($priority_filter > 0) {
    $sql .= " AND id_priority = ?";
    $params[] = $priority_filter;
}

if ($category_filter > 0) {
    $sql .= " AND id_category = ?";
    $params[] = $category_filter;
}

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR ticket_number LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Filtro por empresa
if ($company_filter > 0) {
    $sql .= " AND id_company = ?";
    $params[] = $company_filter;
} elseif (!$is_superadmin) {
    // Solo mostrar tickets de la empresa del usuario (excepto para superadmin)
    $sql .= " AND id_company = ?";
    $params[] = $_SESSION['id_company'];
}

$sql .= " ORDER BY is_urgent DESC, created_at DESC";

$stmt = $connection->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Obtener datos para filtros
$stmt = $connection->prepare("SELECT * FROM ticket_statuses WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$statuses = $stmt->fetchAll();

$stmt = $connection->prepare("SELECT * FROM ticket_priorities ORDER BY sort_order");
$stmt->execute();
$priorities = $stmt->fetchAll();

$stmt = $connection->prepare("SELECT * FROM ticket_categories WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$categories = $stmt->fetchAll();


// Obtener estadísticas
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN id_status = 1 THEN 1 ELSE 0 END) as new_count,
    SUM(CASE WHEN id_status IN (2,3) THEN 1 ELSE 0 END) as in_progress_count,
    SUM(CASE WHEN id_status = 4 THEN 1 ELSE 0 END) as waiting_count,
    SUM(CASE WHEN id_status = 5 THEN 1 ELSE 0 END) as resolved_count,
    SUM(CASE WHEN is_urgent = 1 THEN 1 ELSE 0 END) as urgent_count
FROM v_tickets_complete WHERE 1=1";

if ($company_filter > 0) {
    $stats_sql .= " AND id_company = " . $company_filter;
} elseif (!$is_superadmin) {
    $stats_sql .= " AND id_company = " . $_SESSION['id_company'];
}

$stmt = $connection->prepare($stats_sql);
$stmt->execute();
$stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Sistema de Tickets</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="/Modules/Tickets/css/style.css">
    <script src="js/theme-switcher.js"></script>
</head>

<body>
<?php require_once 'views/partials/modern_navbar.php'; ?>

<div class="tickets-dashboard">
    <div class="container">
        <!-- Header con estadísticas -->
        <div class="tickets-header">
            <h1 class="tickets-title">
                <i class="fas fa-ticket-alt mr-3"></i>
                Sistema de Tickets 2025
            </h1>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" style="color: #3498db;"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total de Tickets</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #3498db;"><?php echo $stats['new_count']; ?></div>
                    <div class="stat-label">Nuevos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #e67e22;"><?php echo $stats['in_progress_count']; ?></div>
                    <div class="stat-label">En Progreso</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #9b59b6;"><?php echo $stats['waiting_count']; ?></div>
                    <div class="stat-label">Esperando</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #27ae60;"><?php echo $stats['resolved_count']; ?></div>
                    <div class="stat-label">Resueltos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" style="color: #e74c3c;"><?php echo $stats['urgent_count']; ?></div>
                    <div class="stat-label">Urgentes</div>
                </div>
            </div>
            
            <div class="text-center">
                <a href="new_ticket.php" class="btn btn-primary btn-lg" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%); border: none; border-radius: 25px; padding: 12px 30px;">
                    <i class="fas fa-plus mr-2"></i>
                    Crear Nuevo Ticket
                </a>
            </div>
        </div>
        
        <!-- Filtros -->
        <div class="filters-section">
            <form method="GET" class="row">
                <div class="col-md-3 mb-3">
                    <label for="search" class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Buscar tickets...">
                </div>
                <div class="col-md-2 mb-3">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-control" id="status" name="status">
                        <option value="0">Todos los estados</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?php echo $status['id_status']; ?>" <?php echo $status_filter == $status['id_status'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($status['status_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="priority" class="form-label">Prioridad</label>
                    <select class="form-control" id="priority" name="priority">
                        <option value="0">Todas las prioridades</option>
                        <?php foreach ($priorities as $priority): ?>
                            <option value="<?php echo $priority['id_priority']; ?>" <?php echo $priority_filter == $priority['id_priority'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($priority['priority_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label for="category" class="form-label">Categoría</label>
                    <select class="form-control" id="category" name="category">
                        <option value="0">Todas las categorías</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id_category']; ?>" <?php echo $category_filter == $category['id_category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['category_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($is_superadmin): ?>
                <div class="col-md-2 mb-3">
                    <label for="company" class="form-label">Empresa</label>
                    <select class="form-control" id="company" name="company">
                        <option value="0">Todas las empresas</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id_company']; ?>" <?php echo $company_filter == $company['id_company'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($company['company_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-<?php echo $is_superadmin ? '3' : '3'; ?> mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary mr-2" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%); border: none;">
                        <i class="fas fa-search mr-1"></i> Filtrar
                    </button>
                    <a href="tickets.php" class="btn btn-secondary" style="border: 1px solid var(--border-color);">
                        <i class="fas fa-times mr-1"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Lista de tickets -->
        <div class="tickets-container">
            <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <i class="fas fa-ticket-alt"></i>
                    <h3>No se encontraron tickets</h3>
                    <p>No hay tickets que coincidan con los filtros aplicados.</p>
                    <a href="new_ticket.php" class="btn btn-primary" style="background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%); border: none; border-radius: 25px;">
                        <i class="fas fa-plus mr-2"></i>
                        Crear Primer Ticket
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $ticket): ?>
                    <div class="ticket-card <?php echo $ticket['is_urgent'] ? 'urgent' : ''; ?>">
                        <div class="ticket-header">
                            <div class="flex-grow-1">
                                <h3 class="ticket-title">
                                    <?php if ($ticket['is_urgent']): ?>
                                        <span class="urgent-badge mr-2">URGENTE</span>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                </h3>
                                <div class="ticket-number"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                            </div>
                        </div>
                        
                        <div class="ticket-meta">
                            <span class="ticket-badge" style="background-color: <?php echo $ticket['status_color']; ?>; color: white;">
                                <i class="<?php echo $ticket['status_icon']; ?>"></i>
                                <?php echo htmlspecialchars($ticket['status_name']); ?>
                            </span>
                            <span class="ticket-badge" style="background-color: <?php echo $ticket['priority_color']; ?>; color: white;">
                                <i class="<?php echo $ticket['priority_icon']; ?>"></i>
                                <?php echo htmlspecialchars($ticket['priority_name']); ?>
                            </span>
                            <span class="ticket-badge" style="background-color: <?php echo $ticket['category_color']; ?>; color: white;">
                                <i class="<?php echo $ticket['category_icon']; ?>"></i>
                                <?php echo htmlspecialchars($ticket['category_name']); ?>
                            </span>
                            <span class="ticket-badge" style="background-color: #f8f9fa; color: #6c757d;">
                                <i class="fas fa-comments"></i>
                                <?php echo $ticket['comment_count']; ?> comentarios
                            </span>
                            <?php if ($is_superadmin): ?>
                            <span class="ticket-badge" style="background-color: #e3f2fd; color: #1976d2;">
                                <i class="fas fa-building"></i>
                                <?php echo htmlspecialchars($ticket['company_name'] ?? 'Sin empresa'); ?>
                            </span>
                            <?php endif; ?>
                            <?php if ($ticket['attachment_count'] > 0): ?>
                                <span class="ticket-badge" style="background-color: #f8f9fa; color: #6c757d;">
                                    <i class="fas fa-paperclip"></i>
                                    <?php echo $ticket['attachment_count']; ?> adjuntos
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ticket-description">
                            <?php 
                            $description = strip_tags($ticket['description']);
                            echo htmlspecialchars(substr($description, 0, 200)) . (strlen($description) > 200 ? '...' : ''); 
                            ?>
                        </div>
                        
                        <div class="ticket-footer">
                            <div class="ticket-info">
                                <small class="text-muted">
                                    <i class="fas fa-user mr-1"></i>
                                    Creado por <?php 
                                    $creator_name = '';
                                    if (!empty($ticket['creator_first_name']) || !empty($ticket['creator_last_name'])) {
                                        $creator_name = trim($ticket['creator_first_name'] . ' ' . $ticket['creator_last_name']);
                                    } else {
                                        $creator_name = $ticket['creator_username'] ?? 'Usuario';
                                    }
                                    echo htmlspecialchars($creator_name); 
                                    ?>
                                    <i class="fas fa-calendar ml-3 mr-1"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>
                                </small>
                            </div>
                            
                            <div class="ticket-actions">
                                <a href="view_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn-ticket btn-primary-ticket">
                                    <i class="fas fa-eye"></i>
                                    Ver
                                </a>
                                <a href="edit_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn-ticket btn-secondary-ticket">
                                    <i class="fas fa-edit"></i>
                                    Editar
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script>
// Animaciones de entrada
document.addEventListener("DOMContentLoaded", function() {
    const cards = document.querySelectorAll(".stat-card, .ticket-card");
    
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

// Auto-submit del formulario de filtros
document.querySelectorAll('#status, #priority, #category').forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

</body>
</html> 