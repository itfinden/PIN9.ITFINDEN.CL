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
verificarPermisoVista($_SESSION["id_user"], 47); // view_dashboard

// Obtener información del usuario y su empresa
$id_user = $_SESSION["id_user"];
$user_info = GET_INFO($id_user);
$empresa = obtenerEmpresaUsuario($id_user);
$is_superadmin = isSuperAdmin($id_user);
$is_company_admin = user_has_permission($id_user, 'manage_companies');

// Preparar el tema antes de incluir la vista
$theme_attributes = applyThemeToHTML();

$database = new Database();
$db = $database->connection();

// Configurar variables de sesión para el navbar
$_SESSION['user'] = $user_info['user'] ?? '';
$_SESSION['user_role'] = $user_info['user_role'] ?? '';
$_SESSION['company_name'] = $empresa['company_name'] ?? '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load event
$stmt = $db->prepare('SELECT * FROM evento_main WHERE id_evento_main = :id');
$stmt->execute([':id' => $id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$evento) {
	header('Location: /Modules/Evento/dashboard.php');
	exit;
}

// Handle subevent creation
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_subevent'])) {
	$title = limpiarString($_POST['sub_title'] ?? '');
	$description = trim($_POST['sub_description'] ?? '');
	$start_date = limpiarString($_POST['sub_start_date'] ?? '');
	$end_date = limpiarString($_POST['sub_end_date'] ?? '');
	$colour = limpiarString($_POST['sub_colour'] ?? '#28a745');
	$id_calendar = $evento['id_calendar'] ? (int)$evento['id_calendar'] : null;

	if ($title === '') { $errors[] = 'El título del subevento es obligatorio.'; }

	if (empty($errors)) {
		try {
			$db->beginTransaction();
			$stmt = $db->prepare('INSERT INTO evento_subevent (id_evento_main, title, description, start_date, end_date, colour) VALUES (:id_evento_main, :title, :description, :start_date, :end_date, :colour)');
			$stmt->execute([
				':id_evento_main' => $id,
				':title' => $title,
				':description' => $description,
				':start_date' => $start_date ?: null,
				':end_date' => $end_date ?: null,
				':colour' => $colour
			]);
			$sub_id = (int)$db->lastInsertId();

			// Crear evento en el calendario del evento padre
			if ($id_calendar && $start_date && $end_date) {
				$cal = $db->prepare("INSERT INTO calendar(id_user, id_calendar, title, description, start_date, end_date, colour) VALUES (:id_user, :id_calendar, :title, :description, :start_date, :end_date, :colour)");
				$cal->execute([
					':id_user' => $id_user,
					':id_calendar' => $id_calendar,
					':title' => $title,
					':description' => $description,
					':start_date' => $start_date,
					':end_date' => $end_date,
					':colour' => $colour
				]);
				$calendar_event_id = (int)$db->lastInsertId();
				$db->prepare('UPDATE evento_subevent SET id_calendar_event = :idce WHERE id_evento_subevent = :id')
					->execute([':idce' => $calendar_event_id, ':id' => $sub_id]);
			}

			$db->commit();
			audit_log('Crear Subevento (módulo Evento)', 'Título: ' . $title);
			header('Location: /Modules/Evento/manage_event.php?id=' . $id);
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error creando subevento: ' . $e->getMessage();
		}
	}
}

// Handle subevent update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_subevent'])) {
	$subevent_id = (int)($_POST['subevent_id'] ?? 0);
	$title = limpiarString($_POST['sub_title'] ?? '');
	$description = trim($_POST['sub_description'] ?? '');
	$start_date = limpiarString($_POST['sub_start_date'] ?? '');
	$end_date = limpiarString($_POST['sub_end_date'] ?? '');
	$colour = limpiarString($_POST['sub_colour'] ?? '#28a745');

	if ($title === '') { $errors[] = 'El título del subevento es obligatorio.'; }

	if (empty($errors)) {
		try {
			$db->beginTransaction();
			
			// Actualizar subevento
			$stmt = $db->prepare('UPDATE evento_subevent SET title = :title, description = :description, start_date = :start_date, end_date = :end_date, colour = :colour WHERE id_evento_subevent = :id AND id_evento_main = :evento_id');
			$stmt->execute([
				':id' => $subevent_id,
				':evento_id' => $id,
				':title' => $title,
				':description' => $description,
				':start_date' => $start_date ?: null,
				':end_date' => $end_date ?: null,
				':colour' => $colour
			]);

			// Actualizar evento en calendario si existe
			$stmt = $db->prepare('SELECT id_calendar_event FROM evento_subevent WHERE id_evento_subevent = :id');
			$stmt->execute([':id' => $subevent_id]);
			$calendar_event_id = $stmt->fetchColumn();
			
			if ($calendar_event_id && $start_date && $end_date) {
				$cal = $db->prepare("UPDATE calendar SET title = :title, description = :description, start_date = :start_date, end_date = :end_date, colour = :colour WHERE id_event = :id");
				$cal->execute([
					':id' => $calendar_event_id,
					':title' => $title,
					':description' => $description,
					':start_date' => $start_date,
					':end_date' => $end_date,
					':colour' => $colour
				]);
			}

			$db->commit();
			audit_log('Actualizar Subevento (módulo Evento)', 'ID: ' . $subevent_id . ', Título: ' . $title);
			header('Location: /Modules/Evento/manage_event.php?id=' . $id);
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error actualizando subevento: ' . $e->getMessage();
		}
	}
}

// Handle subevent deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_subevent'])) {
	$subevent_id = (int)($_POST['subevent_id'] ?? 0);
	
	if ($subevent_id > 0) {
		try {
			$db->beginTransaction();
			
			// Obtener ID del evento de calendario
			$stmt = $db->prepare('SELECT id_calendar_event FROM evento_subevent WHERE id_evento_subevent = :id AND id_evento_main = :evento_id');
			$stmt->execute([':id' => $subevent_id, ':evento_id' => $id]);
			$calendar_event_id = $stmt->fetchColumn();
			
			// Eliminar asignaciones de invitados
			$db->prepare('DELETE FROM evento_subevent_guest WHERE id_evento_subevent = :id')
				->execute([':id' => $subevent_id]);
			
			// Eliminar evento del calendario
			if ($calendar_event_id) {
				$db->prepare('DELETE FROM calendar WHERE id_event = :id')
					->execute([':id' => $calendar_event_id]);
			}
			
			// Eliminar subevento
			$db->prepare('DELETE FROM evento_subevent WHERE id_evento_subevent = :id AND id_evento_main = :evento_id')
				->execute([':id' => $subevent_id, ':evento_id' => $id]);
			
			$db->commit();
			audit_log('Eliminar Subevento (módulo Evento)', 'ID: ' . $subevent_id);
			header('Location: /Modules/Evento/manage_event.php?id=' . $id);
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error eliminando subevento: ' . $e->getMessage();
		}
	}
}

// Handle guest assignment to subevent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_guests'])) {
	$subevent_id = (int)($_POST['subevent_id'] ?? 0);
	$selected_guests = $_POST['selected_guests'] ?? [];
	
	if ($subevent_id > 0) {
		try {
			$db->beginTransaction();
			
			// Eliminar asignaciones previas
			$db->prepare('DELETE FROM evento_subevent_guest WHERE id_evento_subevent = :subevent_id')
				->execute([':subevent_id' => $subevent_id]);
			
			// Asignar nuevos invitados
			foreach ($selected_guests as $guest_id) {
				$db->prepare('INSERT INTO evento_subevent_guest (id_evento_subevent, id_evento_guest) VALUES (:subevent_id, :guest_id)')
					->execute([':subevent_id' => $subevent_id, ':guest_id' => $guest_id]);
			}
			
			$db->commit();
			audit_log('Asignar invitados a subevento', 'Subevento ID: ' . $subevent_id . ', Invitados: ' . count($selected_guests));
			header('Location: /Modules/Evento/manage_event.php?id=' . $id);
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error asignando invitados: ' . $e->getMessage();
		}
	}
}

// Handle guest add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_guest'])) {
	$full_name = limpiarString($_POST['guest_name'] ?? '');
	$email = limpiarString($_POST['guest_email'] ?? '');
	if ($full_name === '' || $email === '') { $errors[] = 'Nombre y email del invitado son obligatorios.'; }
	if (empty($errors)) {
		$token = bin2hex(random_bytes(16));
		$stmt = $db->prepare('INSERT INTO evento_guest (id_evento_main, full_name, email, token) VALUES (:id_evento_main, :full_name, :email, :token)');
		$stmt->execute([
			':id_evento_main' => $id,
			':full_name' => $full_name,
			':email' => $email,
			':token' => $token
		]);
		audit_log('Agregar Invitado (módulo Evento)', 'Invitado: ' . $email);
		header('Location: /Modules/Evento/manage_event.php?id=' . $id);
		exit;
	}
}

// Handle guest update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_guest'])) {
	$guest_id = (int)($_POST['guest_id'] ?? 0);
	$full_name = limpiarString($_POST['guest_name'] ?? '');
	$email = limpiarString($_POST['guest_email'] ?? '');
	
	if ($full_name === '' || $email === '') { $errors[] = 'Nombre y email del invitado son obligatorios.'; }
	
	if (empty($errors)) {
		try {
			$stmt = $db->prepare('UPDATE evento_guest SET full_name = :full_name, email = :email WHERE id_evento_guest = :id AND id_evento_main = :evento_id');
			$stmt->execute([
				':id' => $guest_id,
				':evento_id' => $id,
				':full_name' => $full_name,
				':email' => $email
			]);
			audit_log('Actualizar Invitado (módulo Evento)', 'ID: ' . $guest_id . ', Email: ' . $email);
			header('Location: /Modules/Evento/manage_event.php?id=' . $id);
			exit;
		} catch (Exception $e) {
			$errors[] = 'Error actualizando invitado: ' . $e->getMessage();
		}
	}
}

// Handle guest deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_guest'])) {
	$guest_id = (int)($_POST['guest_id'] ?? 0);
	
	if ($guest_id > 0) {
		try {
			$db->beginTransaction();
			
			// Eliminar asignaciones del invitado en todos los subeventos
			$db->prepare('DELETE FROM evento_subevent_guest WHERE id_evento_guest = :id')
				->execute([':id' => $guest_id]);
			
			// Eliminar invitado
			$db->prepare('DELETE FROM evento_guest WHERE id_evento_guest = :id AND id_evento_main = :evento_id')
				->execute([':id' => $guest_id, ':evento_id' => $id]);
			
			$db->commit();
			audit_log('Eliminar Invitado (módulo Evento)', 'ID: ' . $guest_id);
			header('Location: /Modules/Evento/manage_event.php?id=' . $id);
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error eliminando invitado: ' . $e->getMessage();
		}
	}
}

// Handle bulk guest operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_guest_action'])) {
	$selected_guests = $_POST['selected_guests'] ?? [];
	$action = $_POST['bulk_action'] ?? '';
	
	if (!empty($selected_guests) && $action) {
		try {
			$db->beginTransaction();
			
			if ($action === 'delete') {
				// Eliminar invitados seleccionados
				foreach ($selected_guests as $guest_id) {
					$guest_id = (int)$guest_id;
					
					// Eliminar asignaciones
					$db->prepare('DELETE FROM evento_subevent_guest WHERE id_evento_guest = :id')
						->execute([':id' => $guest_id]);
					
					// Eliminar invitado
					$db->prepare('DELETE FROM evento_guest WHERE id_evento_guest = :id AND id_evento_main = :evento_id')
						->execute([':id' => $guest_id, ':evento_id' => $id]);
				}
				audit_log('Eliminación masiva de invitados (módulo Evento)', 'Cantidad: ' . count($selected_guests));
			}
			
			$db->commit();
			header('Location: /Modules/Evento/manage_event.php?id=' . $id);
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error en operación masiva: ' . $e->getMessage();
		}
	}
}

// Load subevents and guests
$subevents = [];
$guests = [];
$stmt = $db->prepare('SELECT * FROM evento_subevent WHERE id_evento_main = :id ORDER BY start_date ASC');
$stmt->execute([':id' => $id]);
$subevents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare('SELECT * FROM evento_guest WHERE id_evento_main = :id ORDER BY created_at DESC');
$stmt->execute([':id' => $id]);
$guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load guest assignments for each subevent
$subevent_guests = [];
foreach ($subevents as $subevent) {
	$stmt = $db->prepare('SELECT eg.* FROM evento_guest eg 
		JOIN evento_subevent_guest esg ON eg.id_evento_guest = esg.id_evento_guest 
		WHERE esg.id_evento_subevent = :subevent_id');
	$stmt->execute([':subevent_id' => $subevent['id_evento_subevent']]);
	$subevent_guests[$subevent['id_evento_subevent']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="en" <?php echo $theme_attributes ?? ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Gestionar Evento</title>
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
		<a href="/evento_dashboard.php" class="btn btn-link">← Volver al Dashboard</a>
		<h3>Gestionar Evento: <?php echo htmlspecialchars($evento['title']); ?></h3>
		<?php foreach ($errors as $e): ?>
			<div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
		<?php endforeach; ?>

		<!-- Pestañas de navegación -->
		<ul class="nav nav-tabs" id="eventTabs" role="tablist">
			<li class="nav-item">
				<a class="nav-link active" id="subevents-tab" data-toggle="tab" href="#subevents" role="tab">
					<i class="fas fa-calendar-alt"></i> Subeventos (<?php echo count($subevents); ?>)
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="guests-tab" data-toggle="tab" href="#guests" role="tab">
					<i class="fas fa-users"></i> Invitados (<?php echo count($guests); ?>)
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="assignments-tab" data-toggle="tab" href="#assignments" role="tab">
					<i class="fas fa-user-plus"></i> Asignaciones
				</a>
			</li>
		</ul>

		<!-- Contenido de las pestañas -->
		<div class="tab-content" id="eventTabsContent">
			<!-- Pestaña Subeventos -->
			<div class="tab-pane fade show active" id="subevents" role="tabpanel">
				<div class="row mt-3">
					<div class="col-md-4">
						<div class="card">
							<div class="card-header">
								<h6><i class="fas fa-plus"></i> Crear Subevento</h6>
							</div>
							<div class="card-body">
								<form method="post">
									<input type="hidden" name="create_subevent" value="1">
									<div class="form-group">
										<label>Título *</label>
										<input type="text" name="sub_title" class="form-control" required>
									</div>
									<div class="form-group">
										<label>Descripción</label>
										<textarea name="sub_description" class="form-control" rows="3"></textarea>
									</div>
									<div class="form-group">
										<label>Fecha y Hora de Inicio</label>
										<input type="datetime-local" name="sub_start_date" class="form-control">
									</div>
									<div class="form-group">
										<label>Fecha y Hora de Fin</label>
										<input type="datetime-local" name="sub_end_date" class="form-control">
									</div>
									<div class="form-group">
										<label>Color</label>
										<select name="sub_colour" class="form-control">
											<option value="#28a745" selected>Verde</option>
											<option value="#007bff">Azul</option>
											<option value="#dc3545">Rojo</option>
											<option value="#ffc107">Amarillo</option>
											<option value="#6f42c1">Púrpura</option>
											<option value="#fd7e14">Naranja</option>
											<option value="#20c997">Turquesa</option>
											<option value="#e83e8c">Rosa</option>
										</select>
									</div>
									<button type="submit" class="btn btn-primary btn-block">
										<i class="fas fa-plus"></i> Crear Subevento
									</button>
								</form>
							</div>
						</div>
					</div>
					<div class="col-md-8">
						<div class="card">
							<div class="card-header">
								<h6><i class="fas fa-list"></i> Lista de Subeventos</h6>
							</div>
							<div class="card-body">
								<?php if (empty($subevents)): ?>
									<div class="text-center text-muted py-4">
										<i class="fas fa-calendar-times fa-3x mb-3"></i>
										<p>No hay subeventos creados aún.</p>
									</div>
								<?php else: ?>
									<div class="table-responsive">
										<table class="table table-hover">
											<thead>
												<tr>
													<th>Título</th>
													<th>Fechas</th>
													<th>Invitados</th>
													<th>Acciones</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($subevents as $se): ?>
													<tr>
														<td>
															<strong><?php echo htmlspecialchars($se['title']); ?></strong>
															<?php if ($se['description']): ?>
																<br><small class="text-muted"><?php echo htmlspecialchars($se['description']); ?></small>
															<?php endif; ?>
														</td>
														<td>
															<?php if ($se['start_date'] && $se['end_date']): ?>
																<small>
																	<i class="fas fa-clock"></i> 
																	<?php echo date('d/m/Y H:i', strtotime($se['start_date'])); ?> - 
																	<?php echo date('d/m/Y H:i', strtotime($se['end_date'])); ?>
																</small>
															<?php else: ?>
																<small class="text-muted">Sin fechas definidas</small>
															<?php endif; ?>
														</td>
														<td>
															<?php 
															$guest_count = isset($subevent_guests[$se['id_evento_subevent']]) ? count($subevent_guests[$se['id_evento_subevent']]) : 0;
															?>
															<span class="badge badge-info"><?php echo $guest_count; ?> invitados</span>
														</td>
														<td>
															<div class="btn-group btn-group-sm">
																<button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#editSubeventModal<?php echo $se['id_evento_subevent']; ?>">
																	<i class="fas fa-edit"></i>
																</button>
																<button type="button" class="btn btn-outline-success" data-toggle="modal" data-target="#assignGuestsModal<?php echo $se['id_evento_subevent']; ?>">
																	<i class="fas fa-user-plus"></i>
																</button>
																<button type="button" class="btn btn-outline-danger" onclick="deleteSubevent(<?php echo $se['id_evento_subevent']; ?>, '<?php echo htmlspecialchars($se['title']); ?>')">
																	<i class="fas fa-trash"></i>
																</button>
															</div>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Pestaña Invitados -->
			<div class="tab-pane fade" id="guests" role="tabpanel">
				<div class="row mt-3">
					<div class="col-md-4">
						<div class="card">
							<div class="card-header">
								<h6><i class="fas fa-user-plus"></i> Agregar Invitado</h6>
							</div>
							<div class="card-body">
								<form method="post">
									<input type="hidden" name="add_guest" value="1">
									<div class="form-group">
										<label>Nombre Completo *</label>
										<input type="text" name="guest_name" class="form-control" required>
									</div>
									<div class="form-group">
										<label>Email *</label>
										<input type="email" name="guest_email" class="form-control" required>
									</div>
									<button type="submit" class="btn btn-primary btn-block">
										<i class="fas fa-plus"></i> Agregar Invitado
									</button>
								</form>
							</div>
						</div>
					</div>
					<div class="col-md-8">
						<div class="card">
							<div class="card-header d-flex justify-content-between align-items-center">
								<h6><i class="fas fa-users"></i> Lista de Invitados</h6>
								<?php if (!empty($guests)): ?>
									<button type="button" class="btn btn-sm btn-outline-danger" onclick="bulkDeleteGuests()">
										<i class="fas fa-trash"></i> Eliminar Seleccionados
									</button>
								<?php endif; ?>
							</div>
							<div class="card-body">
								<?php if (empty($guests)): ?>
									<div class="text-center text-muted py-4">
										<i class="fas fa-users fa-3x mb-3"></i>
										<p>No hay invitados agregados aún.</p>
									</div>
								<?php else: ?>
									<form method="post" id="bulkGuestsForm">
										<input type="hidden" name="bulk_guest_action" value="1">
										<input type="hidden" name="bulk_action" id="bulkAction" value="">
										<div class="table-responsive">
											<table class="table table-hover">
												<thead>
													<tr>
														<th>
															<input type="checkbox" id="selectAllGuests" onchange="toggleAllGuests()">
														</th>
														<th>Invitado</th>
														<th>Email</th>
														<th>Estado</th>
														<th>Link RSVP</th>
														<th>Acciones</th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($guests as $g): ?>
														<tr>
															<td>
																<input type="checkbox" name="selected_guests[]" value="<?php echo $g['id_evento_guest']; ?>" class="guest-checkbox">
															</td>
															<td><?php echo htmlspecialchars($g['full_name']); ?></td>
															<td><?php echo htmlspecialchars($g['email']); ?></td>
															<td>
																<?php 
																$status_class = '';
																$status_text = $g['status'];
																switch($g['status']) {
																	case 'pending': $status_class = 'badge-warning'; $status_text = 'Pendiente'; break;
																	case 'accepted': $status_class = 'badge-success'; $status_text = 'Aceptado'; break;
																	case 'rejected': $status_class = 'badge-danger'; $status_text = 'Rechazado'; break;
																}
																?>
																<span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
															</td>
															<td>
																<div class="input-group input-group-sm">
																	<input class="form-control" readonly value="<?php echo htmlspecialchars((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/Modules/Evento/rsvp.php?token=' . $g['token']); ?>">
																	<div class="input-group-append">
																		<button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard(this)">
																			<i class="fas fa-copy"></i>
																		</button>
																	</div>
																</div>
															</td>
															<td>
																<div class="btn-group btn-group-sm">
																	<button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#editGuestModal<?php echo $g['id_evento_guest']; ?>">
																		<i class="fas fa-edit"></i>
																	</button>
																	<button type="button" class="btn btn-outline-danger" onclick="deleteGuest(<?php echo $g['id_evento_guest']; ?>, '<?php echo htmlspecialchars($g['full_name']); ?>')">
																		<i class="fas fa-trash"></i>
																	</button>
																</div>
															</td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									</form>
								<?php endif; ?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- Pestaña Asignaciones -->
			<div class="tab-pane fade" id="assignments" role="tabpanel">
				<div class="mt-3">
					<div class="card">
						<div class="card-header">
							<h6><i class="fas fa-user-plus"></i> Asignación de Invitados a Subeventos</h6>
						</div>
						<div class="card-body">
							<?php if (empty($subevents)): ?>
								<div class="text-center text-muted py-4">
									<i class="fas fa-calendar-times fa-3x mb-3"></i>
									<p>No hay subeventos para asignar invitados.</p>
								</div>
							<?php elseif (empty($guests)): ?>
								<div class="text-center text-muted py-4">
									<i class="fas fa-users fa-3x mb-3"></i>
									<p>No hay invitados para asignar.</p>
								</div>
							<?php else: ?>
								<div class="row">
									<?php foreach ($subevents as $se): ?>
										<div class="col-md-6 mb-3">
											<div class="card">
												<div class="card-header">
													<h6 class="mb-0">
														<i class="fas fa-calendar-alt"></i> 
														<?php echo htmlspecialchars($se['title']); ?>
													</h6>
												</div>
												<div class="card-body">
													<?php 
													$assigned_guests = isset($subevent_guests[$se['id_evento_subevent']]) ? $subevent_guests[$se['id_evento_subevent']] : [];
													?>
													<p class="text-muted">
														<small>
															<i class="fas fa-users"></i> 
															<?php echo count($assigned_guests); ?> de <?php echo count($guests); ?> invitados asignados
														</small>
													</p>
													<button type="button" class="btn btn-primary btn-sm btn-block" data-toggle="modal" data-target="#assignGuestsModal<?php echo $se['id_evento_subevent']; ?>">
														<i class="fas fa-user-plus"></i> Gestionar Asignaciones
													</button>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	</div>
	
	<?php require __DIR__ . '/../../footer.php'; ?>
	
	<!-- Modales para editar subeventos -->
	<?php foreach ($subevents as $se): ?>
	<div class="modal fade" id="editSubeventModal<?php echo $se['id_evento_subevent']; ?>" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Editar Subevento: <?php echo htmlspecialchars($se['title']); ?></h5>
					<button type="button" class="close" data-dismiss="modal">
						<span>&times;</span>
					</button>
				</div>
				<form method="post">
					<input type="hidden" name="update_subevent" value="1">
					<input type="hidden" name="subevent_id" value="<?php echo $se['id_evento_subevent']; ?>">
					<div class="modal-body">
						<div class="form-group">
							<label>Título *</label>
							<input type="text" name="sub_title" class="form-control" value="<?php echo htmlspecialchars($se['title']); ?>" required>
						</div>
						<div class="form-group">
							<label>Descripción</label>
							<textarea name="sub_description" class="form-control" rows="3"><?php echo htmlspecialchars($se['description']); ?></textarea>
						</div>
						<div class="form-group">
							<label>Fecha y Hora de Inicio</label>
							<input type="datetime-local" name="sub_start_date" class="form-control" value="<?php echo $se['start_date'] ? date('Y-m-d\TH:i', strtotime($se['start_date'])) : ''; ?>">
						</div>
						<div class="form-group">
							<label>Fecha y Hora de Fin</label>
							<input type="datetime-local" name="sub_end_date" class="form-control" value="<?php echo $se['end_date'] ? date('Y-m-d\TH:i', strtotime($se['end_date'])) : ''; ?>">
						</div>
						<div class="form-group">
							<label>Color</label>
							<select name="sub_colour" class="form-control">
								<option value="#28a745" <?php echo $se['colour'] == '#28a745' ? 'selected' : ''; ?>>Verde</option>
								<option value="#007bff" <?php echo $se['colour'] == '#007bff' ? 'selected' : ''; ?>>Azul</option>
								<option value="#dc3545" <?php echo $se['colour'] == '#dc3545' ? 'selected' : ''; ?>>Rojo</option>
								<option value="#ffc107" <?php echo $se['colour'] == '#ffc107' ? 'selected' : ''; ?>>Amarillo</option>
								<option value="#6f42c1" <?php echo $se['colour'] == '#6f42c1' ? 'selected' : ''; ?>>Púrpura</option>
								<option value="#fd7e14" <?php echo $se['colour'] == '#fd7e14' ? 'selected' : ''; ?>>Naranja</option>
								<option value="#20c997" <?php echo $se['colour'] == '#20c997' ? 'selected' : ''; ?>>Turquesa</option>
								<option value="#e83e8c" <?php echo $se['colour'] == '#e83e8c' ? 'selected' : ''; ?>>Rosa</option>
							</select>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
						<button type="submit" class="btn btn-primary">Guardar Cambios</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

	<!-- Modales para editar invitados -->
	<?php foreach ($guests as $g): ?>
	<div class="modal fade" id="editGuestModal<?php echo $g['id_evento_guest']; ?>" tabindex="-1">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Editar Invitado: <?php echo htmlspecialchars($g['full_name']); ?></h5>
					<button type="button" class="close" data-dismiss="modal">
						<span>&times;</span>
					</button>
				</div>
				<form method="post">
					<input type="hidden" name="update_guest" value="1">
					<input type="hidden" name="guest_id" value="<?php echo $g['id_evento_guest']; ?>">
					<div class="modal-body">
						<div class="form-group">
							<label>Nombre Completo *</label>
							<input type="text" name="guest_name" class="form-control" value="<?php echo htmlspecialchars($g['full_name']); ?>" required>
						</div>
						<div class="form-group">
							<label>Email *</label>
							<input type="email" name="guest_email" class="form-control" value="<?php echo htmlspecialchars($g['email']); ?>" required>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
						<button type="submit" class="btn btn-primary">Guardar Cambios</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

	<!-- Modales para asignar invitados -->
	<?php foreach ($subevents as $se): ?>
	<div class="modal fade" id="assignGuestsModal<?php echo $se['id_evento_subevent']; ?>" tabindex="-1">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title">Asignar Invitados a: <?php echo htmlspecialchars($se['title']); ?></h5>
					<button type="button" class="close" data-dismiss="modal">
						<span>&times;</span>
					</button>
				</div>
				<form method="post">
					<input type="hidden" name="assign_guests" value="1">
					<input type="hidden" name="subevent_id" value="<?php echo $se['id_evento_subevent']; ?>">
					<div class="modal-body">
						<div class="form-group">
							<label>Seleccionar invitados:</label>
							<div class="row">
								<?php foreach ($guests as $guest): ?>
									<?php 
									$is_assigned = false;
									if (isset($subevent_guests[$se['id_evento_subevent']])) {
										foreach ($subevent_guests[$se['id_evento_subevent']] as $assigned_guest) {
											if ($assigned_guest['id_evento_guest'] == $guest['id_evento_guest']) {
												$is_assigned = true;
												break;
											}
										}
									}
									?>
									<div class="col-md-6">
										<div class="form-check">
											<input class="form-check-input" type="checkbox" name="selected_guests[]" 
												value="<?php echo $guest['id_evento_guest']; ?>" 
												id="guest_<?php echo $se['id_evento_subevent']; ?>_<?php echo $guest['id_evento_guest']; ?>"
												<?php echo $is_assigned ? 'checked' : ''; ?>>
											<label class="form-check-label" for="guest_<?php echo $se['id_evento_subevent']; ?>_<?php echo $guest['id_evento_guest']; ?>">
												<strong><?php echo htmlspecialchars($guest['full_name']); ?></strong><br>
												<small class="text-muted"><?php echo htmlspecialchars($guest['email']); ?></small>
											</label>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
						<button type="submit" class="btn btn-primary">Guardar Asignación</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	<?php endforeach; ?>

	<!-- Formularios ocultos para eliminaciones -->
	<form method="post" id="deleteSubeventForm" style="display: none;">
		<input type="hidden" name="delete_subevent" value="1">
		<input type="hidden" name="subevent_id" id="deleteSubeventId">
	</form>

	<form method="post" id="deleteGuestForm" style="display: none;">
		<input type="hidden" name="delete_guest" value="1">
		<input type="hidden" name="guest_id" id="deleteGuestId">
	</form>

	<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>

	<script>
		// Funciones para eliminar subeventos
		function deleteSubevent(id, title) {
			if (confirm('¿Estás seguro de que quieres eliminar el subevento "' + title + '"?\n\nEsto eliminará:\n- El subevento\n- El evento del calendario\n- Todas las asignaciones de invitados\n\nEsta acción no se puede deshacer.')) {
				document.getElementById('deleteSubeventId').value = id;
				document.getElementById('deleteSubeventForm').submit();
			}
		}

		// Funciones para eliminar invitados
		function deleteGuest(id, name) {
			if (confirm('¿Estás seguro de que quieres eliminar al invitado "' + name + '"?\n\nEsto eliminará:\n- El invitado\n- Todas sus asignaciones a subeventos\n\nEsta acción no se puede deshacer.')) {
				document.getElementById('deleteGuestId').value = id;
				document.getElementById('deleteGuestForm').submit();
			}
		}

		// Funciones para operaciones masivas de invitados
		function toggleAllGuests() {
			var selectAll = document.getElementById('selectAllGuests');
			var checkboxes = document.getElementsByClassName('guest-checkbox');
			
			for (var i = 0; i < checkboxes.length; i++) {
				checkboxes[i].checked = selectAll.checked;
			}
		}

		function bulkDeleteGuests() {
			var checkboxes = document.getElementsByClassName('guest-checkbox');
			var selectedCount = 0;
			
			for (var i = 0; i < checkboxes.length; i++) {
				if (checkboxes[i].checked) {
					selectedCount++;
				}
			}
			
			if (selectedCount === 0) {
				alert('Por favor selecciona al menos un invitado para eliminar.');
				return;
			}
			
			if (confirm('¿Estás seguro de que quieres eliminar ' + selectedCount + ' invitado(s)?\n\nEsta acción no se puede deshacer.')) {
				document.getElementById('bulkAction').value = 'delete';
				document.getElementById('bulkGuestsForm').submit();
			}
		}

		// Función para copiar al portapapeles
		function copyToClipboard(button) {
			var input = button.parentElement.previousElementSibling;
			input.select();
			document.execCommand('copy');
			
			// Cambiar temporalmente el icono para mostrar que se copió
			var icon = button.querySelector('i');
			var originalClass = icon.className;
			icon.className = 'fas fa-check';
			button.classList.remove('btn-outline-secondary');
			button.classList.add('btn-success');
			
			setTimeout(function() {
				icon.className = originalClass;
				button.classList.remove('btn-success');
				button.classList.add('btn-outline-secondary');
			}, 2000);
		}

		// Auto-fill end date when start date changes
		document.addEventListener('DOMContentLoaded', function() {
			var startDateInputs = document.querySelectorAll('input[name="sub_start_date"]');
			var endDateInputs = document.querySelectorAll('input[name="sub_end_date"]');
			
			for (var i = 0; i < startDateInputs.length; i++) {
				startDateInputs[i].addEventListener('change', function() {
					var endInput = this.parentElement.parentElement.querySelector('input[name="sub_end_date"]');
					if (endInput && !endInput.value) {
						var startDate = new Date(this.value);
						startDate.setHours(startDate.getHours() + 1);
						endInput.value = startDate.toISOString().slice(0, 16);
					}
				});
			}
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


