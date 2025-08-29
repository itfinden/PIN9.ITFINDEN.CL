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

// Verificar permiso para crear tickets
verificarPermisoVista($_SESSION['id_user'], 40); // manage_tickets

$database = new Database();
$connection = $database->connection();

$message = '';
$error = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $category_id = (int)$_POST['category'];
        $priority_id = (int)$_POST['priority'];
        $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
        $due_date = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $estimated_hours = !empty($_POST['estimated_hours']) ? (float)$_POST['estimated_hours'] : 0;
        
        if (empty($title) || empty($description) || $category_id <= 0 || $priority_id <= 0) {
            throw new Exception('Todos los campos obligatorios deben estar completos.');
        }
        
        // Generar número de ticket
        $prefix = 'TKT';
        $year = date('Y');
        $stmt = $connection->prepare("SELECT COUNT(*) FROM tickets WHERE YEAR(created_at) = ?");
        $stmt->execute([$year]);
        $count = $stmt->fetchColumn() + 1;
        $ticket_number = $prefix . $year . str_pad($count, 4, '0', STR_PAD_LEFT);
        
        // Obtener usuario asignado si se especificó
        $assigned_user_id = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        
        // Verificar que id_company esté disponible
        if (!isset($_SESSION['id_company']) || empty($_SESSION['id_company'])) {
            // Verificar si es superadmin
            $is_superadmin = isSuperAdmin($_SESSION['id_user']);
            
            if ($is_superadmin) {
                // Para superadmins, permitir seleccionar empresa
                if (!isset($_POST['id_company']) || empty($_POST['id_company'])) {
                    throw new Exception('Como superadmin, debes seleccionar una empresa para el ticket.');
                }
                $id_company = (int)$_POST['id_company'];
            } else {
                // Para usuarios normales, intentar obtener id_company del usuario
                $stmt = $connection->prepare("
                    SELECT cu.id_company
                    FROM company_users cu
                    WHERE cu.id_user = ? AND cu.status = 'active'
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['id_user']]);
                $company_info = $stmt->fetch();
                
                if (!$company_info) {
                    throw new Exception('El usuario no está asociado a ninguna empresa activa.');
                }
                
                $id_company = $company_info['id_company'];
            }
        } else {
            $id_company = $_SESSION['id_company'];
        }
        
        // Verificar que la empresa existe
        $stmt = $connection->prepare("SELECT id_company FROM companies WHERE id_company = ?");
        $stmt->execute([$id_company]);
        if (!$stmt->fetch()) {
            throw new Exception('La empresa especificada no existe.');
        }
        
        // Verificar que el usuario asignado existe (si se especificó)
        if ($assigned_user_id) {
            $stmt = $connection->prepare("SELECT id_user FROM users WHERE id_user = ?");
            $stmt->execute([$assigned_user_id]);
            if (!$stmt->fetch()) {
                throw new Exception('El usuario asignado no existe.');
            }
        }
        
        // Insertar ticket con validaciones
        $sql = "INSERT INTO tickets (ticket_number, title, description, id_company, id_user_creator, id_user_assigned, id_category, id_priority, id_status, is_urgent, due_date, estimated_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
        
        $stmt = $connection->prepare($sql);
        $result = $stmt->execute([
            $ticket_number,
            $title,
            $description,
            $id_company,
            $_SESSION['id_user'],
            $assigned_user_id,
            $category_id,
            $priority_id,
            $is_urgent,
            $due_date,
            $estimated_hours
        ]);
        
        if ($result) {
            $ticket_id = $connection->lastInsertId();
            
            // Registrar en historial
            $history_sql = "INSERT INTO ticket_history (id_ticket, id_user, field_name, new_value, change_description) VALUES (?, ?, 'created', ?, 'Ticket creado')";
            $stmt = $connection->prepare($history_sql);
            $stmt->execute([$ticket_id, $_SESSION['id_user'], 'Nuevo ticket']);
            
            $message = "Ticket creado exitosamente con número: $ticket_number";
            
            // Redirigir después de 2 segundos
            header("refresh:2;url=view_ticket.php?id=$ticket_id");
        } else {
            throw new Exception('Error al crear el ticket.');
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



// Obtener usuarios para asignación
$is_superadmin = isSuperAdmin($_SESSION['id_user']);
$id_company = $_SESSION['id_company'] ?? null;

if ($is_superadmin) {
    // Para superadmins, mostrar usuarios de todas las empresas
    $stmt = $connection->prepare("
        SELECT u.id_user, u.user, up.email, up.first_name, up.last_name, c.company_name
        FROM company_users cu
        JOIN users u ON cu.id_user = u.id_user
        LEFT JOIN user_profiles up ON u.id_user = up.id_user
        JOIN companies c ON cu.id_company = c.id_company
        WHERE cu.status = 'active' AND c.subscription_status != 'cancelled'
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
            WHERE cu.id_user = ? AND cu.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['id_user']]);
        $company_info = $stmt->fetch();
        $id_company = $company_info['id_company'] ?? null;
    }

    if ($id_company) {
        $stmt = $connection->prepare("
            SELECT u.id_user, u.user, up.email, up.first_name, up.last_name
            FROM company_users cu
            JOIN users u ON cu.id_user = u.id_user
            LEFT JOIN user_profiles up ON u.id_user = up.id_user
            WHERE cu.id_company = ? AND cu.status = 'active'
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
    <title>Pin9 - Crear Nuevo Ticket</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <script src="js/theme-switcher.js"></script>
    <style>
        .new-ticket-page {
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .ticket-form-container {
            background: var(--bg-card);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px var(--shadow-light);
            border: 1px solid var(--border-color);
            transition: all var(--transition-speed) var(--transition-ease);
        }
        
        .form-title {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 30px;
            text-align: center;
            color: var(--text-primary);
        }
        
        .form-section {
            background: var(--bg-secondary);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            border: 1px solid var(--border-color);
            transition: all var(--transition-speed) var(--transition-ease);
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid var(--border-color);
            padding: 12px 15px;
            transition: all var(--transition-speed) var(--transition-ease);
            background-color: var(--bg-card);
            color: var(--text-primary);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            background-color: var(--bg-card);
            color: var(--text-primary);
        }
        
        .form-control::placeholder {
            color: var(--text-muted);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 25px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all var(--transition-speed) var(--transition-ease);
            color: var(--text-light);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px var(--shadow-medium);
            color: var(--text-light);
        }
        
        .urgent-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: var(--bg-secondary);
            border: 2px solid var(--danger-color);
            border-radius: 10px;
            margin-bottom: 20px;
            transition: all var(--transition-speed) var(--transition-ease);
        }
        
        .urgent-toggle input[type="checkbox"] {
            transform: scale(1.5);
        }
        
        .urgent-label {
            font-weight: 600;
            color: var(--danger-color);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, var(--success-color) 0%, var(--success-color) 100%);
            color: var(--text-light);
        }
        
        .alert-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, var(--danger-color) 100%);
            color: var(--text-light);
        }
        
        /* Estilos para Summernote */
        .note-editor.note-frame {
            background-color: var(--bg-card);
            border-color: var(--border-color);
        }
        
        .note-editor.note-frame .note-editing-area {
            background-color: var(--bg-card);
        }
        
        .note-editor.note-frame .note-editing-area .note-editable {
            background-color: var(--bg-card);
            color: var(--text-primary);
        }
        
        .note-toolbar {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
        }
        
        .note-btn {
            background-color: var(--bg-card);
            border-color: var(--border-color);
            color: var(--text-primary);
        }
        
        .note-btn:hover {
            background-color: var(--bg-tertiary);
            color: var(--text-primary);
        }
        
        @media (max-width: 768px) {
            .form-title {
                font-size: 2rem;
            }
            
            .ticket-form-container {
                padding: 20px;
            }
            
            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>

<body>
<?php require_once 'views/partials/modern_navbar.php'; ?>

<div class="new-ticket-page">
    <div class="container">
        <div class="ticket-form-container">
            <h1 class="form-title">
                <i class="fas fa-plus-circle mr-3"></i>
                Crear Nuevo Ticket
            </h1>
            
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
                                       placeholder="Describe brevemente el problema o solicitud">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category" class="form-label">Categoría *</label>
                                <select class="form-control" id="category" name="category" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id_category']; ?>">
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="priority" class="form-label">Prioridad *</label>
                                <select class="form-control" id="priority" name="priority" required>
                                    <option value="">Seleccionar prioridad</option>
                                    <?php foreach ($priorities as $priority): ?>
                                        <option value="<?php echo $priority['id_priority']; ?>">
                                            <?php echo htmlspecialchars($priority['priority_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="due_date" class="form-label">Fecha de Vencimiento</label>
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="urgent-toggle">
                        <input type="checkbox" id="is_urgent" name="is_urgent" value="1">
                        <label for="is_urgent" class="urgent-label">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Marcar como urgente
                        </label>
                    </div>
                    
                    <?php if (isSuperAdmin($_SESSION['id_user'])): ?>
                    <div class="form-group">
                        <label for="id_company" class="form-label">Empresa *</label>
                        <select class="form-control" id="id_company" name="id_company" required>
                            <option value="">Seleccionar empresa</option>
                            <?php 
                            $stmt = $connection->prepare("SELECT id_company, company_name FROM companies WHERE subscription_status != 'cancelled' ORDER BY company_name");
                            $stmt->execute();
                            $companies = $stmt->fetchAll();
                            foreach ($companies as $company): 
                            ?>
                                <option value="<?php echo $company['id_company']; ?>">
                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
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
                                  placeholder="Describe detalladamente el problema, incluyendo pasos para reproducirlo, comportamiento esperado vs actual, y cualquier información adicional relevante..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="estimated_hours" class="form-label">Horas Estimadas</label>
                                <input type="number" class="form-control" id="estimated_hours" name="estimated_hours" 
                                       min="0" step="0.5" placeholder="0.0">
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
                                <option value="<?php echo $user['id_user']; ?>">
                                    <?php echo htmlspecialchars($display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Botones -->
                <div class="text-center">
                    <button type="submit" class="btn btn-primary btn-submit">
                        <i class="fas fa-save mr-2"></i>
                        Crear Ticket
                    </button>
                    <a href="tickets.php" class="btn btn-secondary ml-3" style="border-radius: 25px; padding: 15px 30px; border: 1px solid var(--border-color);">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>

<script>
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
    
    $('#category, #priority').on('change', function() {
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
        
        if (!title || !description || !category || !priority) {
            e.preventDefault();
            alert('Por favor completa todos los campos obligatorios.');
            return false;
        }
        
        if (description.length < 20) {
            e.preventDefault();
            alert('La descripción debe tener al menos 20 caracteres.');
            return false;
        }
    });
});
</script>

</body>
</html> 