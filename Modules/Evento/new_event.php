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
verificarPermisoVista($_SESSION["id_user"], 45); // view_dashboard

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

$company = $empresa;

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$title = limpiarString($_POST['title'] ?? '');
	$event_type = limpiarString($_POST['event_type'] ?? 'Evento');
	$description = trim($_POST['description'] ?? '');
	
	// Procesar fecha y hora separadas
	$start_date_only = limpiarString($_POST['start_date_only'] ?? '');
	$start_time = limpiarString($_POST['start_time'] ?? '');
	$end_date_only = limpiarString($_POST['end_date_only'] ?? '');
	$end_time = limpiarString($_POST['end_time'] ?? '');
	
	// Combinar fecha y hora
	$start_date = $start_date_only && $start_time ? $start_date_only . ' ' . $start_time . ':00' : null;
	$end_date = $end_date_only && $end_time ? $end_date_only . ' ' . $end_time . ':00' : null;
	
	$subevent_count = (int)($_POST['subevent_count'] ?? 1);
	$id_company = isset($_POST['id_company']) ? (int)$_POST['id_company'] : null;

	if ($title === '') { $errors[] = 'El título es obligatorio.'; }
	if ($event_type === '') { $errors[] = 'El tipo de evento es obligatorio.'; }
	if ($start_date_only === '') { $errors[] = 'La fecha de inicio es obligatoria.'; }
	if ($start_time === '') { $errors[] = 'La hora de inicio es obligatoria.'; }
	if ($end_date_only === '') { $errors[] = 'La fecha de fin es obligatoria.'; }
	if ($end_time === '') { $errors[] = 'La hora de fin es obligatoria.'; }
	if ($subevent_count < 1 || $subevent_count > 20) { $errors[] = 'La cantidad de subeventos debe estar entre 1 y 20.'; }
	if ($is_superadmin && !$id_company) { $errors[] = 'Debe seleccionar una empresa.'; }

			if (empty($errors)) {
			try {
				$db->beginTransaction();

				// Usar empresa seleccionada o la del usuario
				$company_id = $is_superadmin ? $id_company : ($company['id_company'] ?? null);
				
				// Validar que tenemos una empresa válida
				if (!$company_id || $company_id <= 0) {
					throw new Exception('No se pudo determinar la empresa para el evento.');
				}
				
				// Verificar que la empresa existe
				$stmt = $db->prepare("SELECT id_company, company_name FROM companies WHERE id_company = :id_company");
				$stmt->execute([':id_company' => $company_id]);
				$company_info = $stmt->fetch(PDO::FETCH_ASSOC);
				if (!$company_info) {
					throw new Exception('La empresa seleccionada no existe.');
				}
				
				// Crear calendario específico para este evento
				$calendar_name = $title . ' - ' . date('d/m/Y', strtotime($start_date));
				
				// Crear calendario con is_default = 2 (valor único para eventos)
				

				
				// Crear el calendario del evento (siempre será por defecto para evitar conflictos)
				$stmt = $db->prepare("INSERT INTO calendar_companies (id_company, calendar_name, colour, is_default, is_active, begin_calendar, end_calendar) 
				VALUES (:id_company, :calendar_name, :colour, 2, 1, :begin_calendar, :end_calendar)");
				$stmt->execute([
					':id_company' => $company_id,
					':calendar_name' => $calendar_name,
					':colour' => '#007bff', // Color por defecto para el calendario
					':begin_calendar' => $start_date_only,
					':end_calendar' => $end_date_only
				]);
				$id_calendar = (int)$db->lastInsertId();

				// Crear el evento padre
				$stmt = $db->prepare("INSERT INTO evento_main (id_company, id_owner, title, event_type, description, start_date, end_date, colour, id_calendar)
				VALUES (:id_company, :id_owner, :title, :event_type, :description, :start_date, :end_date, :colour, :id_calendar)");
				$stmt->execute([
					':id_company' => $company_id,
					':id_owner' => $id_user,
					':title' => $title,
					':event_type' => $event_type,
					':description' => $description,
					':start_date' => $start_date ?: null,
					':end_date' => $end_date ?: null,
					':colour' => '#007bff', // Color por defecto para el evento padre
					':id_calendar' => $id_calendar
				]);
				$evento_id = (int)$db->lastInsertId();

			$db->commit();
			audit_log('Crear Evento (módulo Evento)', 'Título: ' . $title);
			header('Location: /Modules/Evento/manage_event.php?id=' . $evento_id);
			exit;
		} catch (Exception $e) {
			$db->rollBack();
			$errors[] = 'Error creando evento: ' . $e->getMessage();
		}
	}
}

// Obtener empresas disponibles para superadmin
$companies = [];
if ($is_superadmin) {
    $stmt = $db->prepare("SELECT id_company, company_name FROM companies ORDER BY company_name ASC");
    $stmt->execute();
    $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Empresa seleccionada (por defecto la del usuario si no es superadmin)
$selected_company_id = null;
if ($is_superadmin) {
    $selected_company_id = $_POST['id_company'] ?? ($company['id_company'] ?? null);
} else {
    $selected_company_id = $company['id_company'] ?? null;
}

?>

<!DOCTYPE html>
<html lang="en" <?php echo $theme_attributes ?? ''; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pin9 - Nuevo Evento</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/Modules/Content/css/style.css">
    <script src="/js/theme-switcher.js"></script>
    
    <style>
		/* Estilos para el slide panel con engranaje */
		.slide-panel {
			position: fixed;
			top: 0;
			right: -400px;
			width: 400px;
			height: 100vh;
			background: white;
			box-shadow: -5px 0 15px rgba(0,0,0,0.3);
			z-index: 10001;
			transition: right 0.3s ease-out;
			overflow-y: auto;
		}
		
		.slide-panel.active {
			right: 0;
		}
		
		.slide-panel-header {
			background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
			color: white;
			padding: 20px;
			display: flex;
			justify-content: space-between;
			align-items: center;
			border-bottom: 1px solid #dee2e6;
		}
		
		.slide-panel-header h4 {
			margin: 0;
			font-size: 1.2rem;
			font-weight: 600;
		}
		
		.close-panel {
			background: none;
			border: none;
			color: white;
			font-size: 1.5rem;
			cursor: pointer;
			padding: 5px;
			border-radius: 50%;
			width: 40px;
			height: 40px;
			display: flex;
			align-items: center;
			justify-content: center;
			transition: background-color 0.2s ease-out;
		}
		
		.close-panel:hover {
			background-color: rgba(255,255,255,0.2);
		}
		
		.slide-panel-content {
			padding: 20px;
		}
		
		.panel-overlay {
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			background: rgba(0,0,0,0.5);
			z-index: 10000;
			opacity: 0;
			visibility: hidden;
			transition: opacity 0.2s ease-out, visibility 0.2s ease-out;
		}
		
		.panel-overlay.active {
			opacity: 1;
			visibility: visible;
		}
		
		/* Pestaña con engranaje */
		.settings-tab {
			position: fixed;
			right: 0;
			top: 50%;
			transform: translateY(-50%);
			background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
			color: white;
			padding: 15px 10px;
			border-radius: 8px 0 0 8px;
			box-shadow: -2px 0 10px rgba(0,0,0,0.2);
			cursor: pointer;
			z-index: 9999;
			transition: all 0.3s ease;
			writing-mode: vertical-rl;
			text-orientation: mixed;
			font-size: 12px;
			font-weight: 600;
			letter-spacing: 1px;
			min-height: 120px;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.settings-tab:hover {
			background: linear-gradient(135deg, #495057 0%, #343a40 100%);
			transform: translateY(-50%) translateX(-5px);
			box-shadow: -4px 0 15px rgba(0,0,0,0.3);
		}
		
		.settings-tab i {
			margin-bottom: 8px;
			font-size: 16px;
		}
		
		/* Responsive */
		@media (max-width: 768px) {
			.slide-panel {
				width: 100%;
				right: -100%;
			}
		}
		
		/* Prevenir scroll del body cuando el panel está abierto */
		body.panel-open {
			overflow: hidden;
		}
		
		.company-box {
			width: 100%;
			padding: 15px;
			border-radius: 8px;
			cursor: pointer;
			transition: all 0.3s ease;
			border: 2px solid transparent;
			position: relative;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1);
			margin-bottom: 10px;
			background: #f8f9fa;
		}
		
		.company-box:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 12px rgba(0,0,0,0.2);
		}
		
		.company-box.selected {
			border-color: #007bff;
			background: #e3f2fd;
			box-shadow: 0 0 0 3px rgba(0,123,255,0.2), 0 4px 12px rgba(0,0,0,0.2);
			transform: translateY(-2px);
		}
		
		.company-box i {
			margin-right: 8px;
			font-size: 16px;
		}
	</style>
</head>

<body>
<?php require_once __DIR__ . '/../../views/partials/modern_navbar.php'; ?>

<div class="modern-dashboard">
    <div class="container">
		<h3>Crear Nuevo Evento</h3>
		
		<!-- Pestaña con engranaje para abrir el slide panel -->
		<?php if ($is_superadmin): ?>
		<div class="settings-tab" id="settings-tab" title="Seleccionar Empresa">
			<i class="fas fa-building"></i>
			EMPRESA
		</div>
		<?php endif; ?>
		<?php foreach ($errors as $e): ?>
			<div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
		<?php endforeach; ?>
		<form method="post" action="">
			<div class="form-row">
				<div class="form-group col-md-6">
					<label>Título</label>
					<input type="text" name="title" class="form-control" required>
				</div>
				<div class="form-group col-md-6">
					<label>Tipo de Evento</label>
					<select name="event_type" class="form-control" required>
						<option value="">Seleccionar tipo de evento</option>
						<option value="Fiesta">Fiesta</option>
						<option value="Cumpleaños">Cumpleaños</option>
						<option value="Matrimonio">Matrimonio</option>
						<option value="Boda">Boda</option>
						<option value="Aniversario">Aniversario</option>
						<option value="Graduación">Graduación</option>
						<option value="Conferencia">Conferencia</option>
						<option value="Seminario">Seminario</option>
						<option value="Reunión">Reunión</option>
						<option value="Celebración">Celebración</option>
						<option value="Otro">Otro</option>
					</select>
				</div>
			</div>
			<div class="form-row">
				<div class="form-group col-md-6">
					<label>Cantidad de Subeventos</label>
					<input type="number" name="subevent_count" class="form-control" min="1" max="20" value="1" required>
					<small class="form-text text-muted">Número de subeventos que tendrá este evento (ej: despedida de soltero, prueba de vestido, etc.)</small>
				</div>
				<div class="form-group col-md-6">
					<label>Empresa</label>
					<?php if ($is_superadmin): ?>
						<div class="alert alert-info">
							<i class="fas fa-info-circle"></i> 
							Empresa seleccionada: <strong id="selected-company-name"><?php echo htmlspecialchars($company['company_name'] ?? 'Selecciona una empresa'); ?></strong>
							<br><small>Haz clic en la pestaña "EMPRESA" para cambiar</small>
						</div>
						<input type="hidden" name="id_company" id="selected-company-id" value="<?php echo $selected_company_id; ?>">
					<?php else: ?>
						<input type="text" class="form-control" value="<?php echo htmlspecialchars($company['company_name'] ?? 'Sin empresa'); ?>" readonly>
						<input type="hidden" name="id_company" value="<?php echo $selected_company_id; ?>">
					<?php endif; ?>
				</div>
			</div>
			<div class="form-group">
				<label>Descripción</label>
				<textarea name="description" class="form-control" rows="3"></textarea>
			</div>
			<div class="form-row">
				<div class="form-group col-md-3">
					<label>Fecha Inicio del Evento</label>
					<input type="date" name="start_date_only" id="start_date_only" class="form-control" required>
				</div>
				<div class="form-group col-md-3">
					<label>Hora Inicio del Evento</label>
					<select name="start_time" id="start_time" class="form-control" required>
						<option value="">Seleccionar hora</option>
						<?php for ($hour = 0; $hour < 24; $hour++): ?>
							<?php for ($minute = 0; $minute < 60; $minute += 30): ?>
								<?php $time = sprintf('%02d:%02d', $hour, $minute); ?>
								<option value="<?php echo $time; ?>"><?php echo $time; ?></option>
							<?php endfor; ?>
						<?php endfor; ?>
					</select>
				</div>
				<div class="form-group col-md-3">
					<label>Fecha Fin del Evento</label>
					<input type="date" name="end_date_only" id="end_date_only" class="form-control" required>
				</div>
				<div class="form-group col-md-3">
					<label>Hora Fin del Evento</label>
					<select name="end_time" id="end_time" class="form-control" required>
						<option value="">Seleccionar hora</option>
						<?php for ($hour = 0; $hour < 24; $hour++): ?>
							<?php for ($minute = 0; $minute < 60; $minute += 30): ?>
								<?php $time = sprintf('%02d:%02d', $hour, $minute); ?>
								<option value="<?php echo $time; ?>"><?php echo $time; ?></option>
							<?php endfor; ?>
						<?php endfor; ?>
					</select>
				</div>
			</div>
			<div class="form-row">
				<div class="form-group col-md-12">
					<label>Calendario del Evento</label>
					<div class="alert alert-info">
						<i class="fas fa-info-circle"></i> Se creará automáticamente un calendario específico para este evento llamado: "<strong id="calendar-name-preview"><?php echo htmlspecialchars($title ?? 'Mi Evento'); ?></strong>"
					</div>
				</div>
			</div>
			<button class="btn btn-primary">Guardar</button>
			<a href="/evento_dashboard.php" class="btn btn-link">Cancelar</a>
		</form>
    </div>
</div>

<!-- Slide Panel para Selección de Empresa -->
<?php if ($is_superadmin): ?>
<div id="company-slide-panel" class="slide-panel">
    <div class="slide-panel-header">
        <h4><i class="fas fa-building mr-2"></i>Seleccionar Empresa</h4>
        <button type="button" class="close-panel" id="close-company-panel">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="slide-panel-content">
        <div class="text-center" style="padding: 20px;">
            <div class="company-container">
                <?php if (!empty($companies)): ?>
                    <?php foreach ($companies as $comp): ?>
                        <div class="company-box<?= ($selected_company_id == $comp['id_company']) ? ' selected' : '' ?>"
                             data-id="<?= $comp['id_company'] ?>"
                             data-name="<?= htmlspecialchars($comp['company_name']) ?>">
                            <i class="fas fa-building"></i> 
                            <?= htmlspecialchars($comp['company_name']) ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted">No hay empresas disponibles.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Overlay para el panel -->
<div id="panel-overlay" class="panel-overlay"></div>
<?php endif; ?>

<?php require __DIR__ . '/../../footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" crossorigin="anonymous"></script>

<script>
$(document).ready(function() {
    // Establecer fecha de fin igual a la de inicio por defecto
    $('#start_date_only').on('change', function() {
        if (!$('#end_date_only').val()) {
            $('#end_date_only').val($(this).val());
        }
        updateCalendarPreview();
    });
    
    // Validar que la fecha de fin no sea anterior a la de inicio
    $('#end_date_only, #end_time').on('change', function() {
        var startDate = $('#start_date_only').val();
        var startTime = $('#start_time').val();
        var endDate = $('#end_date_only').val();
        var endTime = $('#end_time').val();
        
        if (startDate && startTime && endDate && endTime) {
            var start = new Date(startDate + ' ' + startTime);
            var end = new Date(endDate + ' ' + endTime);
            
            if (end <= start) {
                alert('La fecha y hora de fin debe ser posterior a la de inicio.');
                $(this).val('');
            }
        }
        updateCalendarPreview();
    });
    
    // Establecer hora de fin 1 hora después de la de inicio por defecto
    $('#start_time').on('change', function() {
        if (!$('#end_time').val()) {
            var time = $(this).val();
            if (time) {
                var [hours, minutes] = time.split(':');
                var endHours = (parseInt(hours) + 1) % 24;
                var endTime = endHours.toString().padStart(2, '0') + ':' + minutes;
                $('#end_time').val(endTime);
            }
        }
        updateCalendarPreview();
    });
    
    // Actualizar preview del nombre del calendario
    $('input[name="title"], #start_date_only').on('input change', function() {
        updateCalendarPreview();
    });
    
    function updateCalendarPreview() {
        var title = $('input[name="title"]').val() || 'Mi Evento';
        var startDate = $('#start_date_only').val();
        
        if (startDate) {
            var date = new Date(startDate);
            var formattedDate = date.getDate().toString().padStart(2, '0') + '/' + 
                               (date.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                               date.getFullYear();
            $('#calendar-name-preview').text(title + ' - ' + formattedDate);
        } else {
            $('#calendar-name-preview').text(title);
        }
    }
    
    // Inicializar preview
    updateCalendarPreview();
});

<?php if ($is_superadmin): ?>
// Script para el slide panel de selección de empresa
$(document).ready(function() {
    // Abrir panel de configuración desde la pestaña con engranaje
    $('#settings-tab').on('click', function() {
        $('#company-slide-panel').addClass('active');
        $('#panel-overlay').addClass('active');
        $('body').addClass('panel-open');
    });

    // Cerrar panel
    $('#close-company-panel, #panel-overlay').on('click', function() {
        closeCompanyPanel();
    });

    // Función para cerrar el panel
    function closeCompanyPanel() {
        $('#company-slide-panel').removeClass('active');
        $('#panel-overlay').removeClass('active');
        $('body').removeClass('panel-open');
    }

    // ESC key para cerrar panel
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#company-slide-panel').hasClass('active')) {
            closeCompanyPanel();
        }
    });

    // Seleccionar empresa
    $('.company-box').on('click', function() {
        var companyId = $(this).data('id');
        var companyName = $(this).data('name');
        
        $('.company-box').removeClass('selected');
        $(this).addClass('selected');
        
        // Actualizar campos ocultos y mostrar
        $('#selected-company-id').val(companyId);
        $('#selected-company-name').text(companyName);
        
        // Cerrar panel
        closeCompanyPanel();
    });
});
<?php endif; ?>
</script>

</body>
</html>
</body>
</html>


