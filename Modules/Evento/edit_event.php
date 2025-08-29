<?php
session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/../../lang/language_handler.php';
require_once __DIR__ . '/../../theme_handler.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION["id_user"]) || empty($_SESSION["id_user"])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../../db/functions.php';
require_once __DIR__ . '/../../security/check_access.php';

// Verificar permiso para ver el dashboard
verificarPermisoVista($_SESSION["id_user"], 48); // view_new_event

// Obtener información del usuario y su empresa
$id_user = $_SESSION["id_user"];
$user_info = GET_INFO($id_user);
$empresa = obtenerEmpresaUsuario($id_user);
$empresa_company_name = (is_array($empresa) && array_key_exists('company_name', $empresa)) ? (string)$empresa['company_name'] : '';
$empresa_id_company = (is_array($empresa) && array_key_exists('id_company', $empresa)) ? (int)$empresa['id_company'] : 0;
$is_superadmin = isSuperAdmin($id_user);
$is_company_admin = user_has_permission($id_user, 'manage_companies');

// Configurar variables de sesión para el navbar ANTES de incluir la vista
// Solo configurar si están vacías, preservar valores existentes
$_SESSION['user'] = $_SESSION['user'] ?: ($user_info['user'] ?? '');
$_SESSION['user_role'] = $_SESSION['user_role'] ?: ($user_info['user_role'] ?? '');
$_SESSION['company_name'] = $_SESSION['company_name'] ?: $empresa_company_name;
$_SESSION['id_company'] = $_SESSION['id_company'] ?: $empresa_id_company;
$_SESSION['rol_name'] = $_SESSION['rol_name'] ?: ($user_info['rol_name'] ?? '');
$_SESSION['security'] = $_SESSION['security'] ?: ($user_info['security'] ?? '');
$_SESSION['name_company'] = $_SESSION['name_company'] ?: $empresa_company_name;
$_SESSION['mode'] = $_SESSION['mode'] ?: ($user_info['mode'] ?? '');
$_SESSION['is_superadmin'] = $_SESSION['is_superadmin'] ?? ($is_superadmin ? 1 : 0);
$_SESSION['lang'] = $_SESSION['lang'] ?? 'es';
$_SESSION['id_rol'] = $_SESSION['id_rol'] ?? ($user_info['id_rol'] ?? 1);

// Preparar el tema antes de incluir la vista
$theme_attributes = applyThemeToHTML();

$database = new Database();
$db = $database->connection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load event
$stmt = $db->prepare('SELECT * FROM evento_main WHERE id_evento_main = :id');
$stmt->execute([':id' => $id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$evento) {
	header('Location: /evento_dashboard.php');
	exit;
}

// Verificar permisos
$is_superadmin = isSuperAdmin($id_user);
$empresa = obtenerEmpresaUsuario($id_user);
$can_edit = $is_superadmin || $evento['id_owner'] == $id_user || $evento['id_company'] == $empresa['id_company'];

if (!$can_edit) {
	header('Location: /evento_dashboard.php');
	exit;
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = limpiarString($_POST['title'] ?? '');
	$event_type = limpiarString($_POST['event_type'] ?? '');
	$description = trim($_POST['description'] ?? '');
	$start_date = limpiarString($_POST['start_date'] ?? '');
	$end_date = limpiarString($_POST['end_date'] ?? '');
	$start_time = limpiarString($_POST['start_time'] ?? '');
	$end_time = limpiarString($_POST['end_time'] ?? '');
	$subevent_count = (int)($_POST['subevent_count'] ?? 1);
	$status = limpiarString($_POST['status'] ?? 'proximo');
	
	// Validaciones
	if ($title === '') { $errors[] = 'El título del evento es obligatorio.'; }
	if ($event_type === '') { $errors[] = 'El tipo de evento es obligatorio.'; }
	if ($subevent_count < 1 || $subevent_count > 20) { $errors[] = 'La cantidad de subeventos debe estar entre 1 y 20.'; }
	
	// Combinar fecha y hora
	$start_datetime = null;
	$end_datetime = null;
	if ($start_date && $start_time) {
		$start_datetime = $start_date . ' ' . $start_time;
	} elseif ($start_date) {
		$start_datetime = $start_date . ' 00:00';
	}
	
	if ($end_date && $end_time) {
		$end_datetime = $end_date . ' ' . $end_time;
	} elseif ($end_date) {
		$end_datetime = $end_date . ' 23:59';
	}
	
	// Validar fechas
	if ($start_datetime && $end_datetime && strtotime($start_datetime) >= strtotime($end_datetime)) {
		$errors[] = 'La fecha de fin debe ser posterior a la fecha de inicio.';
	}
	
	if (empty($errors)) {
		try {
			$db->beginTransaction();
			
			// Actualizar evento
			$stmt = $db->prepare('UPDATE evento_main SET title = :title, event_type = :event_type, description = :description, start_date = :start_date, end_date = :end_date, status = :status WHERE id_evento_main = :id');
			$stmt->execute([
				':id' => $id,
				':title' => $title,
				':event_type' => $event_type,
				':description' => $description,
				':start_date' => $start_datetime,
				':end_date' => $end_datetime,
				':status' => $status
			]);
			
			// Actualizar calendario si existe
			if ($evento['id_calendar']) {
				$start_date_only = $start_date ? date('Y-m-d', strtotime($start_date)) : null;
				$end_date_only = $end_date ? date('Y-m-d', strtotime($end_date)) : null;
				
				$stmt = $db->prepare('UPDATE calendar_companies SET calendar_name = :calendar_name, begin_calendar = :begin_calendar, end_calendar = :end_calendar WHERE id_calendar_companies = :id');
				$stmt->execute([
					':id' => $evento['id_calendar'],
					':calendar_name' => $title . ' - ' . date('d/m/Y', strtotime($start_date)),
					':begin_calendar' => $start_date_only,
					':end_calendar' => $end_date_only
				]);
			}
			
			$db->commit();
			audit_log('Actualizar Evento Padre', 'Evento: ' . $title . ', ID: ' . $id);
			header('Location: /evento_dashboard.php?updated=1');
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error actualizando evento: ' . $e->getMessage();
		}
	}
}

// Preparar datos para el formulario
$start_date_form = $evento['start_date'] ? date('Y-m-d', strtotime($evento['start_date'])) : '';
$start_time_form = $evento['start_date'] ? date('H:i', strtotime($evento['start_date'])) : '';
$end_date_form = $evento['end_date'] ? date('Y-m-d', strtotime($evento['end_date'])) : '';
$end_time_form = $evento['end_date'] ? date('H:i', strtotime($evento['end_date'])) : '';

?>
<!DOCTYPE html>
<html lang="en" <?php echo $theme_attributes ?? ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Editar Evento</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/Modules/Content/css/style.css">
    <script src="/js/theme-switcher.js"></script>
</head>
<body>
	<?php require_once __DIR__ . '/../../views/partials/modern_navbar.php'; ?>
	
	<div class="modern-dashboard">
		<div class="container">
		<div class="d-flex justify-content-between align-items-center mb-3">
			<h3>Editar Evento: <?php echo htmlspecialchars($evento['title']); ?></h3>
			<a href="/evento_dashboard.php" class="btn btn-outline-secondary">
				<i class="fas fa-arrow-left"></i> Volver al Dashboard
			</a>
		</div>
		
		<?php foreach ($errors as $error): ?>
			<div class="alert alert-danger">
				<i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
			</div>
		<?php endforeach; ?>
		
		<div class="row">
			<div class="col-md-8">
				<div class="card">
					<div class="card-header">
						<h5><i class="fas fa-edit"></i> Información del Evento</h5>
					</div>
					<div class="card-body">
						<form method="post">
							<div class="form-group">
								<label>Título del Evento *</label>
								<input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($evento['title']); ?>" required>
							</div>
							
							<div class="form-group">
								<label>Tipo de Evento *</label>
								<select name="event_type" class="form-control" required>
									<option value="">Seleccionar tipo de evento</option>
									<option value="Fiesta" <?php echo $evento['event_type'] == 'Fiesta' ? 'selected' : ''; ?>>Fiesta</option>
									<option value="Cumpleaños" <?php echo $evento['event_type'] == 'Cumpleaños' ? 'selected' : ''; ?>>Cumpleaños</option>
									<option value="Matrimonio" <?php echo $evento['event_type'] == 'Matrimonio' ? 'selected' : ''; ?>>Matrimonio</option>
									<option value="Boda" <?php echo $evento['event_type'] == 'Boda' ? 'selected' : ''; ?>>Boda</option>
									<option value="Aniversario" <?php echo $evento['event_type'] == 'Aniversario' ? 'selected' : ''; ?>>Aniversario</option>
									<option value="Graduación" <?php echo $evento['event_type'] == 'Graduación' ? 'selected' : ''; ?>>Graduación</option>
									<option value="Conferencia" <?php echo $evento['event_type'] == 'Conferencia' ? 'selected' : ''; ?>>Conferencia</option>
									<option value="Seminario" <?php echo $evento['event_type'] == 'Seminario' ? 'selected' : ''; ?>>Seminario</option>
									<option value="Reunión" <?php echo $evento['event_type'] == 'Reunión' ? 'selected' : ''; ?>>Reunión</option>
									<option value="Celebración" <?php echo $evento['event_type'] == 'Celebración' ? 'selected' : ''; ?>>Celebración</option>
									<option value="Otro" <?php echo $evento['event_type'] == 'Otro' ? 'selected' : ''; ?>>Otro</option>
								</select>
							</div>
							
							<div class="form-group">
								<label>Descripción</label>
								<textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($evento['description']); ?></textarea>
							</div>
							
							<div class="form-group">
								<label>Estado del Evento *</label>
								<select name="status" class="form-control" required>
									<option value="proximo" <?php echo ($evento['status'] ?? 'proximo') === 'proximo' ? 'selected' : ''; ?>>Próximo</option>
									<option value="en_curso" <?php echo ($evento['status'] ?? 'proximo') === 'en_curso' ? 'selected' : ''; ?>>En Curso</option>
									<option value="finalizado" <?php echo ($evento['status'] ?? 'proximo') === 'finalizado' ? 'selected' : ''; ?>>Finalizado</option>
								</select>
								<small class="form-text text-muted">Estado actual del evento principal</small>
							</div>
							
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Fecha de Inicio</label>
										<input type="date" name="start_date" class="form-control" value="<?php echo $start_date_form; ?>">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Hora de Inicio</label>
										<select name="start_time" class="form-control">
											<option value="">Sin hora específica</option>
											<?php for ($h = 0; $h < 24; $h++): ?>
												<?php for ($m = 0; $m < 60; $m += 30): ?>
													<?php $time = sprintf('%02d:%02d', $h, $m); ?>
													<option value="<?php echo $time; ?>" <?php echo $start_time_form == $time ? 'selected' : ''; ?>>
														<?php echo $time; ?>
													</option>
												<?php endfor; ?>
											<?php endfor; ?>
										</select>
									</div>
								</div>
							</div>
							
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Fecha de Fin</label>
										<input type="date" name="end_date" class="form-control" value="<?php echo $end_date_form; ?>">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Hora de Fin</label>
										<select name="end_time" class="form-control">
											<option value="">Sin hora específica</option>
											<?php for ($h = 0; $h < 24; $h++): ?>
												<?php for ($m = 0; $m < 60; $m += 30): ?>
													<?php $time = sprintf('%02d:%02d', $h, $m); ?>
													<option value="<?php echo $time; ?>" <?php echo $end_time_form == $time ? 'selected' : ''; ?>>
														<?php echo $time; ?>
													</option>
												<?php endfor; ?>
											<?php endfor; ?>
										</select>
									</div>
								</div>
							</div>
							
							<div class="form-group">
								<label>Cantidad de Subeventos</label>
								<input type="number" name="subevent_count" class="form-control" min="1" max="20" value="1">
								<small class="form-text text-muted">Número de subeventos que tendrá este evento (ej: despedida de soltero, prueba de vestido, etc.)</small>
							</div>
							
							<div class="form-group">
								<button type="submit" class="btn btn-primary">
									<i class="fas fa-save"></i> Guardar Cambios
								</button>
								<a href="/evento_dashboard.php" class="btn btn-secondary">
									<i class="fas fa-times"></i> Cancelar
								</a>
							</div>
						</form>
					</div>
				</div>
			</div>
			
			<div class="col-md-4">
				<div class="card">
					<div class="card-header">
						<h5><i class="fas fa-info-circle"></i> Información del Evento</h5>
					</div>
					<div class="card-body">
						<p><strong>ID:</strong> <?php echo $evento['id_evento_main']; ?></p>
						<p><strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($evento['created_at'])); ?></p>
						<p><strong>Propietario:</strong> <?php echo $evento['id_owner']; ?></p>
						<?php if ($evento['id_company']): ?>
							<p><strong>Empresa:</strong> <?php echo $evento['id_company']; ?></p>
						<?php endif; ?>
						<?php if ($evento['id_calendar']): ?>
							<p><strong>Calendario:</strong> <?php echo $evento['id_calendar']; ?></p>
						<?php endif; ?>
					</div>
				</div>
				
				<div class="card mt-3">
					<div class="card-header">
						<h5><i class="fas fa-cog"></i> Acciones</h5>
					</div>
					<div class="card-body">
						<a href="/Modules/Evento/manage_event.php?id=<?php echo $evento['id_evento_main']; ?>" class="btn btn-primary btn-block mb-2">
							<i class="fas fa-cog"></i> Gestionar Subeventos e Invitados
						</a>
						<button class="btn btn-danger btn-block" onclick="deleteEvent(<?php echo $evento['id_evento_main']; ?>, '<?php echo htmlspecialchars($evento['title']); ?>')">
							<i class="fas fa-trash"></i> Eliminar Evento
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>
	
	<?php require __DIR__ . '/../../footer.php'; ?>
	
	<!-- Formulario oculto para eliminación -->
	<form method="post" id="deleteEventForm" style="display: none;">
		<input type="hidden" name="delete_event" value="1">
		<input type="hidden" name="event_id" value="<?php echo $evento['id_evento_main']; ?>">
	</form>
	
	<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>
	
	<script>
		function deleteEvent(id, title) {
			if (confirm('¿Estás seguro de que quieres eliminar el evento "' + title + '"?\n\nEsto eliminará:\n- El evento padre\n- Todos los subeventos\n- El calendario específico\n- Todos los invitados\n\nEsta acción no se puede deshacer.')) {
				document.getElementById('deleteEventForm').submit();
			}
		}
		
		// Auto-fill end date when start date changes
		document.addEventListener('DOMContentLoaded', function() {
			var startDateInput = document.querySelector('input[name="start_date"]');
			var endDateInput = document.querySelector('input[name="end_date"]');
			
			startDateInput.addEventListener('change', function() {
				if (this.value && !endDateInput.value) {
					endDateInput.value = this.value;
				}
			});
		});
	</script>

<script>
// Animaciones de entrada
document.addEventListener("DOMContentLoaded", function() {
    const cards = document.querySelectorAll(".card");
    
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
</script>
    
</body>
</html>
