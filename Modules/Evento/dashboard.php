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
verificarPermisoVista($_SESSION["id_user"], 43); // view_dashboard

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

$user_company = $empresa;

// Ensure tables exist hint (link to installer)
$needs_install = false;
try {
	$db->query('SELECT 1 FROM evento_main LIMIT 1');
} catch (Exception $e) {
	$needs_install = true;
}

// Handle event deletion
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
	$event_id = (int)($_POST['event_id'] ?? 0);
	
	if ($event_id > 0) {
		try {
			$db->beginTransaction();
			
			// Verificar que el usuario puede eliminar este evento
			$stmt = $db->prepare('SELECT * FROM evento_main WHERE id_evento_main = :id');
			$stmt->execute([':id' => $event_id]);
			$evento = $stmt->fetch(PDO::FETCH_ASSOC);
			
			if (!$evento) {
				throw new Exception('Evento no encontrado.');
			}
			
			// Solo el propietario o superadmin puede eliminar
			if (!$is_superadmin && $evento['id_owner'] != $id_user) {
				throw new Exception('No tienes permisos para eliminar este evento.');
			}
			
			// Eliminar eventos del calendario (subeventos)
			$stmt = $db->prepare('SELECT id_calendar_event FROM evento_subevent WHERE id_evento_main = :id');
			$stmt->execute([':id' => $event_id]);
			$calendar_events = $stmt->fetchAll(PDO::FETCH_COLUMN);
			
			foreach ($calendar_events as $cal_event_id) {
				if ($cal_event_id) {
					$db->prepare('DELETE FROM calendar WHERE id_event = :id')->execute([':id' => $cal_event_id]);
				}
			}
			
			// Eliminar asignaciones de invitados a subeventos
			$db->prepare('DELETE esg FROM evento_subevent_guest esg 
				JOIN evento_subevent es ON esg.id_evento_subevent = es.id_evento_subevent 
				WHERE es.id_evento_main = :id')->execute([':id' => $event_id]);
			
			// Eliminar subeventos
			$db->prepare('DELETE FROM evento_subevent WHERE id_evento_main = :id')->execute([':id' => $event_id]);
			
			// Eliminar invitados
			$db->prepare('DELETE FROM evento_guest WHERE id_evento_main = :id')->execute([':id' => $event_id]);
			
			// Eliminar calendario específico
			if ($evento['id_calendar']) {
				$db->prepare('DELETE FROM calendar_companies WHERE id_calendar_companies = :id')->execute([':id' => $evento['id_calendar']]);
			}
			
			// Eliminar evento principal
			$db->prepare('DELETE FROM evento_main WHERE id_evento_main = :id')->execute([':id' => $event_id]);
			
			$db->commit();
			audit_log('Eliminar Evento Completo', 'Evento: ' . $evento['title'] . ', ID: ' . $event_id);
			header('Location: /evento_dashboard.php?deleted=1');
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error eliminando evento: ' . $e->getMessage();
		}
	}
}

// Fetch events for dashboard
$events = [];
try {
	if ($is_superadmin) {
		$sql = "SELECT em.*, 
			(SELECT COUNT(*) FROM evento_subevent es WHERE es.id_evento_main = em.id_evento_main) AS subevents_count,
			(SELECT COUNT(*) FROM evento_guest eg WHERE eg.id_evento_main = em.id_evento_main) AS guests_count,
			c.company_name
			FROM evento_main em
			LEFT JOIN companies c ON em.id_company = c.id_company
			ORDER BY em.created_at DESC";
		$stmt = $db->prepare($sql);
		$stmt->execute();
		$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
	} else if ($user_company) {
		$sql = "SELECT em.*, 
			(SELECT COUNT(*) FROM evento_subevent es WHERE es.id_evento_main = em.id_evento_main) AS subevents_count,
			(SELECT COUNT(*) FROM evento_guest eg WHERE eg.id_evento_main = em.id_evento_main) AS guests_count,
			c.company_name
			FROM evento_main em
			LEFT JOIN companies c ON em.id_company = c.id_company
			WHERE em.id_company = :id_company OR em.id_owner = :id_user
			ORDER BY em.created_at DESC";
		$stmt = $db->prepare($sql);
		$stmt->execute([
			':id_company' => $user_company['id_company'],
			':id_user' => $id_user
		]);
		$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
} catch (Exception $e) {
	$events = [];
}

?>

<!DOCTYPE html>
<html lang="en" <?php echo $theme_attributes ?? ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Módulo Eventos</title>
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
			<h3 class="mb-0">Módulo Evento 2025</h3>
			<div>
				<?php if ($needs_install): ?>
					<a class="btn btn-warning" href="/Modules/Evento/install.php">Instalar Tablas</a>
				<?php endif; ?>
				<a class="btn btn-primary" href="/Modules/Evento/new_event.php">Crear Evento</a>
			</div>
		</div>

		<?php if (isset($_GET['deleted'])): ?>
			<div class="alert alert-success">
				<i class="fas fa-check-circle"></i> Evento eliminado correctamente junto con todos sus subeventos, invitados y calendario.
			</div>
		<?php endif; ?>

		<?php if (!empty($errors)): ?>
			<?php foreach ($errors as $error): ?>
				<div class="alert alert-danger">
					<i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
				</div>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php if (isset($_GET['error'])): ?>
			<div class="alert alert-danger">
				<i class="fas fa-exclamation-triangle"></i> Error al eliminar el evento. Por favor, inténtalo de nuevo.
			</div>
		<?php endif; ?>

		<?php if (isset($_GET['updated'])): ?>
			<div class="alert alert-success">
				<i class="fas fa-check-circle"></i> Evento actualizado correctamente.
			</div>
		<?php endif; ?>

		<!-- Estadísticas rápidas -->
		<?php if (!empty($events)): ?>
			<div class="row mb-4">
				<div class="col-md-3">
					<div class="card bg-primary text-white">
						<div class="card-body text-center">
							<i class="fas fa-calendar-alt fa-2x mb-2"></i>
							<h4><?php echo count($events); ?></h4>
							<p class="mb-0">Total Eventos</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card bg-success text-white">
						<div class="card-body text-center">
							<i class="fas fa-list fa-2x mb-2"></i>
							<h4><?php echo array_sum(array_column($events, 'subevents_count')); ?></h4>
							<p class="mb-0">Total Subeventos</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card bg-info text-white">
						<div class="card-body text-center">
							<i class="fas fa-users fa-2x mb-2"></i>
							<h4><?php echo array_sum(array_column($events, 'guests_count')); ?></h4>
							<p class="mb-0">Total Invitados</p>
						</div>
					</div>
				</div>
				<div class="col-md-3">
					<div class="card bg-warning text-white">
						<div class="card-body text-center">
							<i class="fas fa-clock fa-2x mb-2"></i>
							<h4><?php 
								$upcoming = 0;
								foreach ($events as $ev) {
									if ($ev['start_date'] && strtotime($ev['start_date']) > time()) {
										$upcoming++;
									}
								}
								echo $upcoming;
							?></h4>
							<p class="mb-0">Próximos Eventos</p>
						</div>
					</div>
				</div>
			</div>
			
		<?php endif; ?>

		<?php if (empty($events)): ?>
			<div class="text-center py-5">
				<i class="fas fa-calendar-times fa-5x text-muted mb-3"></i>
				<h4 class="text-muted">No hay eventos creados aún</h4>
				<p class="text-muted">Comienza creando tu primer evento para gestionar fiestas, cumpleaños, bodas y más.</p>
				<a href="/Modules/Evento/new_event.php" class="btn btn-primary btn-lg">
					<i class="fas fa-plus"></i> Crear Primer Evento
				</a>
			</div>
		<?php else: ?>
			<div class="card">
				<div class="card-header">
					<h5 class="mb-0"><i class="fas fa-list"></i> Lista de Eventos</h5>
				</div>
				<div class="card-body">
					<div class="table-responsive">
						<table class="table table-hover">
							<thead class="thead-light">
								<tr>
									<th>Título</th>
									<th>Tipo</th>
									<th>Empresa</th>
									<th>Fechas</th>
									<th>Subeventos</th>
									<th>Invitados</th>
									<th>Estado</th>
									<th>Calendario</th>
									<th>Acciones</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($events as $ev): ?>
									<tr>
										<td>
											<strong><?php echo htmlspecialchars($ev['title']); ?></strong>
											<?php if ($ev['description']): ?>
												<br><small class="text-muted"><?php echo htmlspecialchars(substr($ev['description'], 0, 50)) . (strlen($ev['description']) > 50 ? '...' : ''); ?></small>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge badge-secondary"><?php echo htmlspecialchars($ev['event_type'] ?? 'Evento'); ?></span>
										</td>
										<td>
											<?php if ($is_superadmin && $ev['company_name']): ?>
												<small class="text-muted"><?php echo htmlspecialchars($ev['company_name']); ?></small>
											<?php else: ?>
												<small class="text-muted">-</small>
											<?php endif; ?>
										</td>
										<td>
											<?php if ($ev['start_date'] && $ev['end_date']): ?>
												<small>
													<i class="fas fa-calendar"></i> 
													<?php echo date('d/m/Y', strtotime($ev['start_date'])); ?> - 
													<?php echo date('d/m/Y', strtotime($ev['end_date'])); ?>
												</small>
											<?php else: ?>
												<small class="text-muted">Sin fechas definidas</small>
											<?php endif; ?>
										</td>
										<td>
											<span class="badge badge-info"><?php echo (int)($ev['subevents_count'] ?? 0); ?></span>
										</td>
										<td>
											<span class="badge badge-success"><?php echo (int)($ev['guests_count'] ?? 0); ?></span>
										</td>
										<td>
											<?php 
											$status_class = '';
											$status_text = '';
											$manual_status = $ev['status'] ?? 'proximo';
											
											if ($manual_status === 'proximo') {
												$status_class = 'badge-warning';
												$status_text = 'Próximo';
											} elseif ($manual_status === 'en_curso') {
												$status_class = 'badge-success';
												$status_text = 'En Curso';
											} elseif ($manual_status === 'finalizado') {
												$status_class = 'badge-secondary';
												$status_text = 'Finalizado';
											} else {
												$status_class = 'badge-light';
												$status_text = 'Sin Estado';
											}
											?>
											<span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
										</td>
										<td>
											<?php if ($ev['id_calendar']): ?>
												<a href="/calendar.php?id_calendar=<?php echo (int)$ev['id_calendar']; ?>" class="btn btn-outline-info btn-sm" title="Ver Calendario del Evento">
													<i class="fas fa-calendar"></i> Ver
												</a>
											<?php else: ?>
												<span class="text-muted"><small>Sin calendario</small></span>
											<?php endif; ?>
										</td>
										<td>
											<div class="btn-group btn-group-sm">
												<a class="btn btn-outline-primary" href="/Modules/Evento/manage_event.php?id=<?php echo (int)$ev['id_evento_main']; ?>" title="Gestionar">
													<i class="fas fa-cog"></i>
												</a>
												<a class="btn btn-outline-info" href="/Modules/Evento/edit_event.php?id=<?php echo (int)$ev['id_evento_main']; ?>" title="Editar">
													<i class="fas fa-edit"></i>
												</a>
												<button class="btn btn-outline-danger" onclick="deleteEvent(<?php echo (int)$ev['id_evento_main']; ?>, '<?php echo htmlspecialchars($ev['title']); ?>')" title="Eliminar">
													<i class="fas fa-trash"></i>
												</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		<?php endif; ?>
    </div>
</div>

	<?php require __DIR__ . '/../../footer.php'; ?>

	<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>

	<script>
	function deleteEvent(eventId, eventTitle) {
		if (confirm('¿Estás seguro de que quieres eliminar el evento "' + eventTitle + '"?\n\nEsto eliminará:\n- El evento padre\n- Todos los subeventos\n- El calendario específico\n- Todos los invitados\n\nEsta acción no se puede deshacer.')) {
			window.location.href = '/Modules/Evento/delete_event.php?id=' + eventId;
		}
	}
	</script>

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

</body>
</html>


