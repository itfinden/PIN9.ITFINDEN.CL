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

// Verificar permiso para editar tickets
verificarPermisoVista($_SESSION['id_user'], 42); // manage_tickets

$database = new Database();
$connection = $database->connection();

$message = '';
$error = '';
$ticket = null;

// Obtener ID del ticket
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ticket_id <= 0) {
    header('Location: tickets.php');
    exit;
}

// Obtener datos del ticket
try {
    $stmt = $connection->prepare("SELECT * FROM v_tickets_complete WHERE id_ticket = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket no encontrado.');
    }
    
    // Verificar permisos: solo el creador, asignado, admin de empresa o superadmin pueden editar
    $is_superadmin = isSuperAdmin($_SESSION['id_user']);
    $is_company_admin = user_has_permission($_SESSION['id_user'], 'manage_companies');
    $is_creator = ($ticket['id_user_creator'] == $_SESSION['id_user']);
    $is_assigned = ($ticket['id_user_assigned'] == $_SESSION['id_user']);
    
    // Verificar permisos: solo el creador, asignado, admin de empresa o superadmin pueden editar
    if (!$is_superadmin && !$is_company_admin && !$is_creator && !$is_assigned) {
        throw new Exception('No tienes permisos para editar este ticket.');
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    try {
        // Validar datos
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category_id = (int)$_POST['category'];
        $priority_id = (int)$_POST['priority'];
        $status_id = (int)$_POST['status'];
        $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $estimated_hours = !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : 0;
        $assigned_user_id = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        
        if (empty($title) || empty($description) || $category_id <= 0 || $priority_id <= 0 || $status_id <= 0) {
            throw new Exception('Todos los campos obligatorios deben estar completos.');
        }
        
        // Actualizar ticket
        $sql = "UPDATE tickets SET 
                title = ?, 
                description = ?, 
                id_category = ?, 
                id_priority = ?, 
                id_status = ?, 
                id_user_assigned = ?, 
                is_urgent = ?, 
                due_date = ?, 
                estimated_hours = ?,
                updated_at = NOW()
                WHERE id_ticket = ?";
        
        $stmt = $connection->prepare($sql);
        $result = $stmt->execute([
            $title,
            $description,
            $category_id,
            $priority_id,
            $status_id,
            $assigned_user_id,
            $is_urgent,
            $due_date,
            $estimated_hours,
            $ticket_id
        ]);
        
        if ($result) {
            // Registrar en historial
            $history_sql = "INSERT INTO ticket_history (id_ticket, id_user, field_name, new_value, change_description) VALUES (?, ?, 'updated', ?, 'Ticket actualizado')";
            $stmt = $connection->prepare($history_sql);
            $stmt->execute([$ticket_id, $_SESSION['id_user'], 'Ticket actualizado']);
            
            $message = "Ticket actualizado exitosamente.";
            
            // Redirigir después de 2 segundos
            header("refresh:2;url=view_ticket.php?id=$ticket_id");
        } else {
            throw new Exception('Error al actualizar el ticket.');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Obtener datos para el formulario
$stmt = $connection->prepare("SELECT * FROM ticket_categories WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$categories = $stmt->fetchAll();

$stmt = $connection->prepare("SELECT * FROM ticket_priorities ORDER BY sort_order");
$stmt->execute();
$priorities = $stmt->fetchAll();

$stmt = $connection->prepare("SELECT * FROM ticket_statuses WHERE is_active = 1 ORDER BY sort_order");
$stmt->execute();
$statuses = $stmt->fetchAll();

// Obtener usuarios para asignación
$is_superadmin = isSuperAdmin($_SESSION['id_user']);
$id_company = $_SESSION['id_company'] ?? null;

if ($is_superadmin) {
    // Para superadmins, mostrar usuarios de todas las empresas
    $stmt = $connection->prepare("
        SELECT u.id_user, u.user, up.first_name, up.last_name, up.email, c.company_name
        FROM company_users cu
        JOIN users u ON cu.id_user = u.id_user
        LEFT JOIN user_profiles up ON u.id_user = up.id_user
        JOIN companies c ON cu.id_company = c.id_company
        WHERE cu.status = 1
        ORDER BY c.company_name, up.first_name, up.last_name
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
} else {
    // Para usuarios normales, obtener id_company si no está en sesión
    if (!$id_company) {
        $stmt = $connection->prepare("
            SELECT cu.id_company
            FROM company_users cu
            WHERE cu.id_user = ? AND cu.status = 1
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['id_user']]);
        $company_info = $stmt->fetch();
        $id_company = $company_info['id_company'] ?? null;
    }

    if ($id_company) {
        $stmt = $connection->prepare("
            SELECT u.id_user, u.user, up.first_name, up.last_name, up.email
            FROM company_users cu
            JOIN users u ON cu.id_user = u.id_user
            LEFT JOIN user_profiles up ON u.id_user = up.id_user
            WHERE cu.id_company = ? AND cu.status = 1
            ORDER BY up.first_name, up.last_name
        ");
        $stmt->execute([$id_company]);
        $users = $stmt->fetchAll();
    } else {
        $users = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Ticket #<?php echo $ticket['ticket_number'] ?? ''; ?></title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <!-- Summernote CSS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Theme Switcher JS -->
    <script src="js/theme-switcher.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="/Modules/Tickets/css/global_tickets.css">
    <link rel="stylesheet" href="/Modules/Tickets/css/edit_ticket_css.css">
</head>
<body>

<?php require_once 'views/partials/modern_navbar.php'; ?>

<div class="edit-ticket-page" style="padding-top: 80px;">
    <div class="container">
        <div class="ticket-form-container">
            <h1 class="form-title">
                <i class="fas fa-edit mr-3"></i>
                Editar Ticket #<?php echo $ticket['ticket_number'] ?? ''; ?>
            </h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if ($ticket): ?>
            <form method="POST" enctype="multipart/form-data">
                <!-- Información básica -->
                <div class="form-section">
                                            <h3 class="section-title">
                            <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                            Información del Ticket
                        </h3>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="title" class="form-label">Título del Ticket *</label>
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?php echo htmlspecialchars($ticket['title']); ?>"
                                       placeholder="Describe brevemente el problema o solicitud">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category" class="form-label">Categoría *</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id_category']; ?>" 
                                                <?php echo ($category['id_category'] == $ticket['id_category']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="priority" class="form-label">Prioridad *</label>
                                <select class="form-control" id="priority" name="priority" required>
                                    <option value="">Seleccionar prioridad</option>
                                    <?php foreach ($priorities as $priority): ?>
                                        <option value="<?php echo $priority['id_priority']; ?>"
                                                <?php echo ($priority['id_priority'] == $ticket['id_priority']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($priority['priority_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status" class="form-label">Estado *</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="">Seleccionar estado</option>
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?php echo $status['id_status']; ?>"
                                                <?php echo ($status['id_status'] == $ticket['id_status']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($status['status_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="due_date" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?php echo $ticket['due_date']; ?>"
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="urgent-toggle">
                        <input type="checkbox" id="is_urgent" name="is_urgent" value="1" 
                               <?php echo ($ticket['is_urgent'] == 1) ? 'checked' : ''; ?>>
                        <label for="is_urgent" class="urgent-label">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Marcar como urgente
                        </label>
                    </div>
                </div>
                
                <!-- Descripción -->
                <div class="form-section">
                                            <h3 class="section-title">
                            <i class="fas fa-align-left" style="color: var(--primary-color);"></i>
                            Descripción Detallada
                        </h3>
                    
                    <div class="form-group">
                        <label for="description" class="form-label">Descripción *</label>
                        <textarea class="form-control" id="description" name="description" rows="8" required
                                  placeholder="Describe detalladamente el problema, incluyendo pasos para reproducirlo, comportamiento esperado vs actual, y cualquier información adicional relevante..."><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estimated_hours" class="form-label">Horas Estimadas</label>
                                <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" 
                                       min="0" step="0.5" placeholder="0.0"
                                       value="<?php echo $ticket['estimated_hours']; ?>">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Asignación -->
                <?php if (!empty($users)): ?>
                <div class="form-section">
                                            <h3 class="section-title">
                            <i class="fas fa-user-plus" style="color: var(--primary-color);"></i>
                            Asignación
                        </h3>
                    
                    <div class="form-group">
                        <label for="assigned_to" class="form-label">Asignar a</label>
                        <select class="form-control" id="assigned_to" name="assigned_to">
                            <option value="">Sin asignar</option>
                            <?php foreach ($users as $user): ?>
                                <?php 
                                $name = trim($user['first_name'] . ' ' . $user['last_name']);
                                if (empty($name)) $name = $user['user'];
                                $display_name = $name;
                                if (isset($user['company_name'])) {
                                    $display_name .= ' (' . $user['company_name'] . ')';
                                }
                                ?>
                                <option value="<?php echo $user['id_user']; ?>"
                                        <?php echo ($user['id_user'] == $ticket['id_user_assigned']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Información del ticket -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-info" style="color: var(--primary-color);"></i>
                        Información del Ticket
                    </h3>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Número de Ticket</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($ticket['ticket_number']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Creado por</label>
                                <input type="text" class="form-control" value="<?php 
                                    $creator_name = '';
                                    if (!empty($ticket['creator_first_name']) || !empty($ticket['creator_last_name'])) {
                                        $creator_name = trim($ticket['creator_first_name'] . ' ' . $ticket['creator_last_name']);
                                    } else {
                                        $creator_name = $ticket['creator_username'] ?? 'Usuario';
                                    }
                                    echo htmlspecialchars($creator_name);
                                ?>" readonly>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Fecha de Creación</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Última Actualización</label>
                                <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?>" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Botones -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save mr-2"></i>
                        Actualizar Ticket
                    </button>
                    <a href="view_ticket.php?id=<?php echo $ticket_id; ?>" class="btn btn-secondary ml-3" style="border-radius: 25px; padding: 15px 30px; border: 1px solid var(--border-color);">
                        <i class="fas fa-eye mr-2"></i>
                        Ver Ticket
                    </a>
                    <a href="tickets.php" class="btn btn-outline-secondary ml-3" style="border-radius: 25px; padding: 15px 30px; border: 1px solid var(--border-color);">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

<script>
// Debug: Verificar que el tema se está aplicando
console.log('Tema actual:', document.documentElement.getAttribute('data-theme'));
console.log('Variables CSS:', getComputedStyle(document.documentElement).getPropertyValue('--bg-card'));

// Verificar que el contenedor existe
const container = document.querySelector('.ticket-form-container');
if (container) {
    console.log('Contenedor encontrado:', container);
    console.log('Estilo de fondo:', getComputedStyle(container).backgroundColor);
} else {
    console.log('Contenedor no encontrado');
}
$(document).ready(function() {
    // Inicializar editor de texto rico
    $('#description').summernote({
        height: 200,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        placeholder: 'Describe detalladamente el problema...'
    });
    
    // Validación en tiempo real
    $('#title').on('input', function() {
        if ($(this).val().length > 0) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    $('#category, #priority, #status').on('change', function() {
        if ($(this).val() !== '') {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-valid').addClass('is-invalid');
        }
    });
    
    // Confirmar antes de enviar
    $('form').on('submit', function(e) {
        const title = $('#title').val().trim();
        const description = $('#description').summernote('code').trim();
        const category = $('#category').val();
        const priority = $('#priority').val();
        const status = $('#status').val();
        
        if (!title || !description || !category || !priority || !status) {
            e.preventDefault();
            alert('Por favor completa todos los campos obligatorios.');
            return false;
        }
        
        if (description.length < 20) {
            e.preventDefault();
            alert('La descripción debe tener al menos 20 caracteres.');
            return false;
        }
        
        return confirm('¿Estás seguro de que quieres actualizar este ticket?');
    });
});
</script>

</body>
</html>