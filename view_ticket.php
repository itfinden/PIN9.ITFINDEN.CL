<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}

require_once 'db/functions.php';
require_once 'theme_handler.php';
require_once 'security/check_access.php';

// Verificar permiso para ver tickets
verificarPermisoVista($_SESSION['id_user'], 41); // manage_tickets

$database = new Database();
$connection = $database->connection();

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id <= 0) {
    header('Location: tickets.php');
    exit;
}


// Obtener ticket con información completa
$sql = "SELECT * FROM v_tickets_complete WHERE id_ticket = ?";
$params = [$ticket_id];

// Solo mostrar tickets de la empresa del usuario (excepto para superadmin)
if (!isset($_SESSION['is_superadmin']) || !$_SESSION['is_superadmin']) {
    $sql .= " AND id_company = ?";
    $params[] = $_SESSION['id_company'];
}

$stmt = $connection->prepare($sql);
$stmt->execute($params);
$ticket = $stmt->fetch();

if (!$ticket) {
    header('Location: tickets.php');
    exit;
}

// Procesar nuevo comentario
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_comment') {
        try {
            $comment = trim($_POST['comment']);
            $is_internal = isset($_POST['is_internal']) ? 1 : 0;
            
            if (empty($comment)) {
                throw new Exception('El comentario no puede estar vacío.');
            }
            
            $stmt = $connection->prepare("INSERT INTO ticket_comments (id_ticket, id_user, comment, is_internal) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$ticket_id, $_SESSION['id_user'], $comment, $is_internal]);
            
            if ($result) {
                // Registrar en historial
                $history_sql = "INSERT INTO ticket_history (id_ticket, id_user, field_name, new_value, change_description) VALUES (?, ?, 'comment', ?, 'Comentario agregado')";
                $stmt = $connection->prepare($history_sql);
                $stmt->execute([$ticket_id, $_SESSION['id_user'], 'Nuevo comentario']);
                
                $message = 'Comentario agregado exitosamente.';
                
                // Redirigir para evitar reenvío
                header("Location: view_ticket.php?id=$ticket_id&success=1");
                exit;
            } else {
                throw new Exception('Error al agregar el comentario.');
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    } elseif ($_POST['action'] === 'update_status') {
        try {
            $new_status = (int)$_POST['new_status'];
            $resolution = trim($_POST['resolution'] ?? '');
            
            $stmt = $connection->prepare("UPDATE tickets SET id_status = ?, resolution = ?, updated_at = NOW() WHERE id_ticket = ?");
            $result = $stmt->execute([$new_status, $resolution, $ticket_id]);
            
            if ($result) {
                // Registrar en historial
                $stmt = $connection->prepare("SELECT status_name FROM ticket_statuses WHERE id_status = ?");
                $stmt->execute([$new_status]);
                $status_name = $stmt->fetchColumn();
                
                $history_sql = "INSERT INTO ticket_history (id_ticket, id_user, field_name, old_value, new_value, change_description) VALUES (?, ?, 'status', ?, ?, 'Estado actualizado')";
                $stmt = $connection->prepare($history_sql);
                $stmt->execute([$ticket_id, $_SESSION['id_user'], $ticket['status_name'], $status_name]);
                
                $message = 'Estado actualizado exitosamente.';
                
                // Redirigir para evitar reenvío
                header("Location: view_ticket.php?id=$ticket_id&success=1");
                exit;
            } else {
                throw new Exception('Error al actualizar el estado.');
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Mostrar mensaje de éxito
if (isset($_GET['success'])) {
    $message = 'Operación completada exitosamente.';
}

// Obtener comentarios
$stmt = $connection->prepare("
    SELECT tc.*, u.user, u.email 
    FROM ticket_comments tc 
    LEFT JOIN users u ON tc.id_user = u.id_user 
    WHERE tc.id_ticket = ? 
    ORDER BY tc.created_at ASC
");
$stmt->execute([$ticket_id]);
$comments = $stmt->fetchAll();

// Obtener historial
$stmt = $connection->prepare("
    SELECT th.*, u.user
    FROM ticket_history th 
    LEFT JOIN users u ON th.id_user = u.id_user 
    WHERE th.id_ticket = ? 
    ORDER BY th.created_at DESC
");
$stmt->execute([$ticket_id]);
$history = $stmt->fetchAll();

// Obtener datos para formularios
$stmt = $connection->prepare("SELECT * FROM ticket_statuses WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$statuses = $stmt->fetchAll();

// Obtener usuarios de la empresa para asignación
$stmt = $connection->prepare("
    SELECT u.id_user, u.user, up.first_name, up.last_name, up.email
    FROM company_users cu
    JOIN users u ON cu.id_user = u.id_user
    LEFT JOIN user_profiles up ON u.id_user = up.id_user
    WHERE cu.id_company = ? AND cu.status = 1
    ORDER BY up.first_name, up.last_name
");
$stmt->execute([$_SESSION['id_company']]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Ver Ticket #<?php echo $ticket['ticket_number']; ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
    <link rel="stylesheet" href="/Modules/Tickets/css/global_tickets.css">
</head>

<body>
<?php require_once 'views/partials/modern_navbar.php'; ?>

<div class="ticket-view-page">
    <div class="container">
        <div class="ticket-container">
            <!-- Header del ticket -->
            <div class="ticket-header">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h1 class="ticket-title">
                            <?php if ($ticket['is_urgent']): ?>
                                <span class="urgent-badge mr-2">URGENTE</span>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($ticket['title']); ?>
                        </h1>
                        <div class="ticket-number"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                    </div>
                    <div>
                        <a href="tickets.php" class="btn btn-secondary" style="border-radius: 20px; border: 1px solid var(--border-color);">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                        </a>
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
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Información del ticket -->
            <div class="ticket-info-grid">
                <div class="info-card">
                    <div class="info-title">Creado por</div>
                    <div class="info-value">
                        <?php 
                        $creator_name = '';
                        if (!empty($ticket['creator_first_name']) || !empty($ticket['creator_last_name'])) {
                            $creator_name = trim($ticket['creator_first_name'] . ' ' . $ticket['creator_last_name']);
                        } else {
                            $creator_name = $ticket['creator_username'] ?? 'Usuario';
                        }
                        echo htmlspecialchars($creator_name);
                        ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-title">Asignado a</div>
                    <div class="info-value">
                        <?php 
                        if ($ticket['id_user_assigned']) {
                            $assignee_name = '';
                            if (!empty($ticket['assignee_first_name']) || !empty($ticket['assignee_last_name'])) {
                                $assignee_name = trim($ticket['assignee_first_name'] . ' ' . $ticket['assignee_last_name']);
                            } else {
                                $assignee_name = $ticket['assignee_username'] ?? 'Usuario';
                            }
                            echo htmlspecialchars($assignee_name);
                        } else {
                            echo 'Sin asignar';
                        }
                        ?>
                    </div>
                </div>
                <div class="info-card">
                    <div class="info-title">Fecha de creación</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></div>
                </div>
                <div class="info-card">
                    <div class="info-title">Última actualización</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></div>
                </div>
                <?php if ($ticket['due_date']): ?>
                    <div class="info-card">
                        <div class="info-title">Fecha de vencimiento</div>
                        <div class="info-value"><?php echo date('d/m/Y', strtotime($ticket['due_date'])); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($ticket['estimated_hours'] > 0): ?>
                    <div class="info-card">
                        <div class="info-title">Horas estimadas</div>
                        <div class="info-value"><?php echo $ticket['estimated_hours']; ?> horas</div>
                    </div>
                <?php endif; ?>
                <?php if ($ticket['actual_hours'] > 0): ?>
                    <div class="info-card">
                        <div class="info-title">Horas reales</div>
                        <div class="info-value"><?php echo $ticket['actual_hours']; ?> horas</div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Descripción -->
            <div class="ticket-description">
                                        <h3 class="description-title">
                            <i class="fas fa-align-left mr-2" style="color: var(--primary-color);"></i>
                            Descripción
                        </h3>
                <div class="description-content">
                    <?php echo $ticket['description']; ?>
                </div>
            </div>
            
            <!-- Resolución (si existe) -->
            <?php if ($ticket['resolution']): ?>
                <div class="ticket-description">
                                            <h3 class="description-title">
                            <i class="fas fa-check-circle mr-2" style="color: var(--success-color);"></i>
                            Resolución
                        </h3>
                    <div class="description-content">
                        <?php echo $ticket['resolution']; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Formulario de cambio de estado -->
            <div class="status-form">
                <h3 class="section-title">
                    <i class="fas fa-cogs" style="color: var(--primary-color);"></i>
                    Cambiar Estado
                </h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="new_status">Nuevo Estado</label>
                                <select class="form-control" id="new_status" name="new_status" required>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['id_status']; ?>" <?php echo $status['id_status'] == $ticket['id_status'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['status_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="resolution">Resolución (opcional)</label>
                                <textarea class="form-control" id="resolution" name="resolution" rows="3" placeholder="Describe la resolución del problema..."><?php echo htmlspecialchars($ticket['resolution'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary btn-action">
                                <i class="fas fa-save mr-1"></i>
                                Actualizar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Comentarios -->
            <div class="comments-section">
                <h3 class="section-title">
                    <i class="fas fa-comments" style="color: var(--primary-color);"></i>
                    Comentarios (<?php echo count($comments); ?>)
                </h3>
                
                <!-- Formulario de nuevo comentario -->
                <div class="comment-form">
                    <form method="POST">
                        <input type="hidden" name="action" value="add_comment">
                        <div class="form-group">
                            <label for="comment">Nuevo Comentario</label>
                            <textarea class="form-control" id="comment" name="comment" rows="4" required placeholder="Escribe tu comentario..."></textarea>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal">
                            <label class="form-check-label" for="is_internal">
                                Comentario interno (solo visible para el equipo)
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-action">
                            <i class="fas fa-paper-plane mr-2"></i>
                            Agregar Comentario
                        </button>
                    </form>
                </div>
                
                <!-- Lista de comentarios -->
                <?php if (empty($comments)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-comments" style="font-size: 3rem; opacity: 0.3;"></i>
                        <p class="mt-3">No hay comentarios aún. ¡Sé el primero en comentar!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-card <?php echo $comment['is_internal'] ? 'comment-internal' : ''; ?>">
                            <div class="comment-header">
                                <div>
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                    <?php if ($comment['is_internal']): ?>
                                        <span class="badge badge-warning ml-2">Interno</span>
                                    <?php endif; ?>
                                </div>
                                <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></span>
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<script>
// Auto-resize textarea
document.getElementById('comment').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Confirmar antes de cambiar estado
document.querySelector('form[action="update_status"]').addEventListener('submit', function(e) {
    const newStatus = document.getElementById('new_status').value;
    const currentStatus = '<?php echo $ticket['id_status']; ?>';
    
    if (newStatus !== currentStatus) {
        if (!confirm('¿Estás seguro de que quieres cambiar el estado del ticket?')) {
            e.preventDefault();
        }
    }
});
</script>

</body>
</html> 