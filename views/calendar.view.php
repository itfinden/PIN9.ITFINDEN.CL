<!DOCTYPE html>
<html lang="en" <?php require_once __DIR__ . '/../theme_handler.php'; echo applyThemeToHTML(); ?>>
<?php
require_once __DIR__ . '/../lang/Languaje.php';
$lang = Language::autoDetect();
$id_company = $_SESSION['id_company'];

?>
<head>
    <?php $title= "Calendar"; ?>
    <?php require 'head.php'; ?>

    <!-- FullCalendar v6.x -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
    
    <!-- Summernote CSS (Rich Text Editor) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">

    <!-- Tippy.js y Popper.js para tooltips avanzados -->
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>
    
    <!-- MULTILANG FULLCALENDAR: Pasar idioma PHP a JS -->
    <script>
      var calendarLang = '<?php echo isset($_SESSION['lang']) ? strtolower($_SESSION['lang']) : 'es'; ?>';
    </script>
 
    <style>
        .note-editor.note-frame {
            margin-bottom: 0;
        }
        .modal-body {
            padding: 20px;
        }
        
        /* Estilos para modal de 2 columnas */
        .modal-lg {
            max-width: 800px;
        }
        
        .modal-body .row {
            margin: 0;
        }
        
        .modal-body .col-md-6 {
            padding: 0 10px;
        }
        
        .modal-body .form-group {
            margin-bottom: 15px;
        }
        
        .modal-body .form-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-primary, #333);
        }
        
        .modal-body .form-control {
            border-radius: 6px;
            border: 1px solid var(--border-color, #ddd);
            transition: border-color 0.3s ease;
        }
        
        .modal-body .form-control:focus {
            border-color: var(--primary-color, #007bff);
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .modal-body textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }
        
        /* Estilos para las cajas de calendarios en columna */
        .calendar-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px;
            margin-bottom: 20px;
            background: var(--bg-secondary, #f8f9fa);
            border-radius: 8px;
            box-shadow: 0 2px 4px var(--shadow-light, rgba(0,0,0,0.1));
            width: 100%;
            height: 100%;
            overflow-y: auto;
        }
        
        .calendar-box {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            box-shadow: 0 2px 8px var(--shadow-light, rgba(0,0,0,0.1));
        }
        
        .calendar-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px var(--shadow-medium, rgba(0,0,0,0.2));
        }
        
        .calendar-box.selected {
            border-color: var(--text-primary, #fff);
            box-shadow: 0 0 0 3px var(--shadow-light, rgba(255,255,255,0.8)), 0 4px 12px var(--shadow-medium, rgba(0,0,0,0.2));
            transform: translateY(-2px);
        }
        
        .calendar-box i {
            margin-right: 8px;
            font-size: 16px;
        }
        
        .calendar-box small {
            display: block;
            font-size: 10px;
            opacity: 0.8;
            margin-top: 5px;
        }
        
        .calendar-box .badge {
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 8px;
            padding: 2px 6px;
            background: var(--bg-primary, rgba(255,255,255,0.9));
            color: var(--text-primary, #333);
        }
        
        /* Scroll vertical suave */
        .calendar-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .calendar-container::-webkit-scrollbar-track {
            background: var(--bg-secondary, #f1f1f1);
            border-radius: 3px;
        }
        
        .calendar-container::-webkit-scrollbar-thumb {
            background: var(--border-color, #c1c1c1);
            border-radius: 3px;
        }
        
        .calendar-container::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted, #a8a8a8);
        }
        
        /* Estilos para el slide panel con engranaje */
        .slide-panel {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100vh;
            background: var(--bg-primary, #ffffff);
            color: var(--text-primary, #212529);
            box-shadow: -5px 0 15px var(--shadow-dark, rgba(0,0,0,0.3));
            z-index: 10001;
            transition: right 0.3s ease-out;
            overflow-y: auto;
        }
        
        .slide-panel.active {
            right: 0;
        }
        
        .slide-panel-header {
            background: linear-gradient(135deg, var(--primary-color, #007bff) 0%, var(--primary-hover, #0056b3) 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color, #dee2e6);
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
            background: var(--bg-primary, #ffffff);
            color: var(--text-primary, #212529);
        }
        
        .calendar-option-btn {
            transition: all 0.2s ease-out;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            background: var(--bg-secondary, #f8f9fa);
            color: var(--text-primary, #212529);
            border: 1px solid var(--border-color, #e9ecef);
        }
        
        .calendar-option-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px var(--shadow-light, rgba(0,0,0,0.1));
            background: var(--bg-hover, #e9ecef);
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
        
        /* Dark mode overrides for slide panel */
        [data-theme="dark"] .slide-panel {
            background: var(--bg-primary, #1f2125) !important;
            color: var(--text-primary, #e5e7eb) !important;
            box-shadow: -5px 0 15px var(--shadow-dark, rgba(0,0,0,0.6)) !important;
        }
        
        [data-theme="dark"] .slide-panel-content {
            background: var(--bg-primary, #1f2125) !important;
            color: var(--text-primary, #e5e7eb) !important;
        }
        
        [data-theme="dark"] .calendar-option-btn {
            background: var(--bg-secondary, #2a2f36) !important;
            color: var(--text-primary, #e5e7eb) !important;
            border-color: var(--border-color, #404040) !important;
        }
        
        [data-theme="dark"] .calendar-option-btn:hover {
            background: var(--bg-hover, #3a3f46) !important;
            box-shadow: 0 4px 8px var(--shadow-dark, rgba(0,0,0,0.4)) !important;
        }
        
        [data-theme="dark"] .slide-panel-header {
            background: linear-gradient(135deg, var(--primary-color, #2563eb) 0%, var(--primary-hover, #1d4ed8) 100%) !important;
        }
        
        /* Estilos para el contenedor principal del calendario */
        .calendar-container-main {
            background: var(--bg-primary, #ffffff);
            color: var(--text-primary, #212529);
            border: 1px solid var(--border-color, #dee2e6);
            box-shadow: 0 2px 8px var(--shadow-light, rgba(0,0,0,0.1));
        }
        
        /* Estilos para elementos de texto */
        .lead {
            color: var(--text-primary, #212529);
        }
        
        .text-muted {
            color: var(--text-muted, #6c757d) !important;
        }
        
        /* Estilos para FullCalendar */
        .fc {
            background: var(--bg-primary, #ffffff);
            color: var(--text-primary, #212529);
        }
        
        .fc-theme-standard .fc-scrollgrid {
            border-color: var(--border-color, #dee2e6);
        }
        
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: var(--border-color, #dee2e6);
        }
        
        .fc-theme-standard .fc-list-day-cushion {
            background: var(--bg-secondary, #f8f9fa);
            color: var(--text-primary, #212529);
        }
        
        .fc-theme-standard .fc-list-event:hover td {
            background: var(--bg-hover, #e9ecef);
        }
        
        /* Dark mode overrides para calendario */
        [data-theme="dark"] .calendar-container-main {
            background: var(--bg-primary-dark, #1a1a1a);
            color: var(--text-primary-dark, #ffffff);
            border-color: var(--border-color-dark, #404040);
        }
        
        [data-theme="dark"] .calendar-container {
            background: var(--bg-secondary-dark, #2a2f36);
        }
        
        [data-theme="dark"] .fc {
            background: var(--bg-primary-dark, #1a1a1a);
            color: var(--text-primary-dark, #ffffff);
        }
        
        [data-theme="dark"] .fc-theme-standard .fc-scrollgrid {
            border-color: var(--border-color-dark, #404040);
        }
        
        [data-theme="dark"] .fc-theme-standard td, .fc-theme-standard th {
            border-color: var(--border-color-dark, #404040);
        }
        
        [data-theme="dark"] .fc-theme-standard .fc-list-day-cushion {
            background: var(--bg-secondary-dark, #2a2f36);
            color: var(--text-primary-dark, #ffffff);
        }
        
        [data-theme="dark"] .fc-theme-standard .fc-list-event:hover td {
            background: var(--bg-hover-dark, #3a3f46);
        }
        
        /* Debug panel styles */
        .debug-panel {
            background: var(--bg-primary, #ffffff) !important;
            color: var(--text-primary, #333) !important;
            border-color: var(--border-color, #ccc) !important;
        }
        
        [data-theme="dark"] .debug-panel {
            background: var(--bg-primary-dark, #1a1a1a) !important;
            color: var(--text-primary-dark, #ffffff) !important;
            border-color: var(--border-color-dark, #404040) !important;
        }
    </style>

    <link rel="stylesheet" href="/Modules/Calendar/css/style.css">  

</head>

<body class="bg">
<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>

<!-- aqui estaba antes el codigo -->
<!-- Page Content -->
<div class="container calendar-container-main rounded mt-4">
    <div class="row m-0 p-0">
        <div class="col-lg-12 text-center">
            <p class="lead"></p>
            <div id="calendar" class="col-centered mb-4">
            </div>
        </div>
    </div>

    <!-- MODALS -->
    <script type="text/javascript" class="d-print-none">
        function validaForm(erro) {
            
            // Obtener también las horas
            var startDate = erro.start_date.value;
            var endDate = erro.end_date.value;
            var startTime = erro.start_time ? erro.start_time.value : '';
            var endTime = erro.end_time ? erro.end_time.value : '';
            if(startDate > endDate){
                alert('The start date has to be before the end date.');
                return false;
            } else if(startDate == endDate) {
                if(startTime >= endTime) {
                    alert('The start time has to be before the end time.');
                    return false;
                }
            }
            console.log("validaForm pasó la validación");
            return true;
        }
        
        // Función para inicializar los editores enriquecidos
        function initSummernote() {
            $('#description').summernote({
                height: 150,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                ]
            });
            
            $('#ModalEdit #description').summernote({
                height: 150,
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['insert', ['link']],
                ]
            });
        }
    </script>


    <?php include ('events/modals/modalAdd.php'); ?>
    <?php include ('events/modals/modalEdit.php'); ?>


</div>

<!-- Pestaña con engranaje para abrir el slide panel -->
<?php if ($is_superadmin || user_has_permission($id_user, 'manage_calendars') || $_SESSION['id_rol'] == '3'): ?>
<div class="settings-tab" id="settings-tab" title="Configuración de calendarios">
    <i class="fas fa-cog"></i>
    CALENDARIOS
</div>
<?php endif; ?>

<!-- Slide Panel para Configuración de Calendarios -->
<div id="calendar-slide-panel" class="slide-panel">
    <div class="slide-panel-header">
        <h4><i class="fas fa-cog mr-2"></i>Calendarios</h4>
        <button type="button" class="close-panel" id="close-calendar-panel">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="slide-panel-content">
        <div class="text-center" style="padding: 50px 20px;">
            
            
            
            <!-- Ini Calendario -->
            <?php
// Obtener calendarios directamente para el slide panel
require_once __DIR__ . '/../db/functions.php';
$database = new Database();
$connection = $database->connection();

$id_user = $_SESSION['id_user'] ?? 0;
$is_superadmin = isSuperAdmin($id_user);

$calendars = [];

if ($is_superadmin) {
    // Para superadmins: obtener todos los calendarios de todas las empresas
    $sql = "
        SELECT 
            cc.id_calendar_companies,
            cc.calendar_name,
            cc.colour,
            cc.is_default,
            cc.is_active,
            c.id_company,
            c.company_name
        FROM calendar_companies cc
        JOIN companies c ON cc.id_company = c.id_company
        WHERE cc.is_active = 1
        ORDER BY c.company_name ASC, cc.calendar_name ASC
    ";
    
    $stmt = $connection->prepare($sql);
    $stmt->execute();
    $calendars = $stmt->fetchAll();
    
} else {
    // Para usuarios normales: obtener solo calendarios de su empresa
    $empresa = obtenerEmpresaUsuario($id_user);
    
    if ($empresa && isset($empresa['id_company'])) {
        $sql = "
            SELECT 
                cc.id_calendar_companies,
                cc.calendar_name,
                cc.colour,
                cc.is_default,
                cc.is_active,
                c.id_company,
                c.company_name
            FROM calendar_companies cc
            JOIN companies c ON cc.id_company = c.id_company
            WHERE cc.id_company = ? AND cc.is_active = 1
            ORDER BY cc.calendar_name ASC
        ";
        
        $stmt = $connection->prepare($sql);
        $stmt->execute([$empresa['id_company']]);
        $calendars = $stmt->fetchAll();
    }
}

// Debug temporal
echo "<!-- DEBUG: Calendarios obtenidos directamente -->";
echo "<!-- DEBUG: Count $calendars: " . count($calendars) . " -->";
echo "<!-- DEBUG: $is_superadmin: " . ($is_superadmin ? 'TRUE' : 'FALSE') . " -->";
echo "<!-- DEBUG: $id_user: " . $id_user . " -->";
?>
               <!-- <div class="container mt-3 mb-2">-->
                  <div class="calendar-container">
                    <?php if ($is_superadmin): ?>
                      <!-- Para superadmins: mostrar todos los calendarios -->
                      <?php if (!empty($calendars)): ?>
                        <?php foreach ($calendars as $cal): ?>
                           <div class="calendar-box<?= ($_SESSION['id_calendar'] == $cal['id_calendar_companies']) ? ' selected' : '' ?>"
                                style="background:<?= htmlspecialchars($cal['colour'] ?? '#007bff') ?>; color:#fff;"
                                data-id="<?= $cal['id_calendar_companies'] ?>">
                             <i class="fas fa-calendar-alt"></i> 
                             <?= htmlspecialchars($cal['calendar_name']) ?>
                             <small><?= htmlspecialchars($cal['company_name']) ?></small>
                             <?= ($cal['is_default'] ?? 0) ? '<span class="badge">Default</span>' : '' ?>
                           </div>
                         <?php endforeach; ?>
                       <?php endif; ?>
                     <?php else: ?>
                       <!-- Para usuarios normales -->
                       <?php if (!empty($calendars)): ?>
                         <?php foreach ($calendars as $cal): ?>
                           <div class="calendar-box<?= ($_SESSION['id_calendar'] == $cal['id_calendar_companies']) ? ' selected' : '' ?>"
                                style="background:<?= htmlspecialchars($cal['colour'] ?? '#007bff') ?>; color:#fff;"
                                data-id="<?= $cal['id_calendar_companies'] ?>">
                             <i class="fas fa-calendar-alt"></i> 
                             <?= htmlspecialchars($cal['calendar_name']) ?>
                             <?= ($cal['is_default'] ?? 0) ? '<span class="badge">Default</span>' : '' ?>
                           </div>
                         <?php endforeach; ?>
                       <?php endif; ?>
                     <?php endif; ?>
                     <?php if (empty($calendars)): ?>
                       <div class="text-muted">No hay calendarios activos para tu empresa. 2025</div>
                     <?php endif; ?>
                   </div>
                   <div id="debug-query" style="display:none;"></div>
               <!--  </div>-->

            <!-- fin Calendario -->

        </div>
    </div>
</div>

<!-- Overlay para el panel -->
<div id="panel-overlay" class="panel-overlay"></div>

<?php require 'footer.php'; ?>

    <!-- jQuery  -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Bootstrap Core JavaScript -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

    <!-- FullCalendar v6.x -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <!-- FullCalendar Locales -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/es.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/en.global.min.js'></script>
    
    <!-- Summernote JS (Rich Text Editor) -->
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>

    <?php include ('calendar2.php'); ?>
    



    <script>
        // Inicializar los editores cuando el DOM esté listo
        $(document).ready(function() {
            initSummernote();
            
            // Reiniciar los editores cuando se cierren los modales
            $('#ModalAdd').on('hidden.bs.modal', function() {
                $('#description').summernote('reset');
            });
            
            $('#ModalEdit').on('hidden.bs.modal', function() {
                $('#ModalEdit #description').summernote('reset');
            });

            // Selección visual y AJAX
            $('.calendar-box').click(function() {
                var id_calendar = $(this).data('id');
                
                // Extraer solo el nombre del calendario (sin empresa ni badge)
                var calendarName = $(this).clone().find('small, .badge').remove().end().text().trim();
                
                $('.calendar-box').removeClass('selected');
                $(this).addClass('selected');
                window.id_calendar_active = id_calendar;
                
                // Actualizar la sesión via AJAX
                $.ajax({
                    url: 'update_calendar_session.php',
                    method: 'POST',
                    data: {
                        id_calendar: id_calendar,
                        calendar_name: calendarName
                    },
                    success: function(response) {
                        console.log('Sesión actualizada:', response);
                        
                        // Actualizar el campo oculto del modal con el nuevo valor de la sesión
                        $('#modalAdd_id_calendar').val(id_calendar);
                        
                        // Mostrar modal con información del calendario activo
                        $('#active-calendar-name').text(calendarName);
                        $('#active-calendar-id').text(id_calendar);
                        $('#session-calendar-id').text(id_calendar);
                        $('#calendarActiveModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error actualizando sesión:', error);
                    }
                });
                
                // Recargar eventos en FullCalendar
                if (window.calendar && typeof window.calendar.refetchEvents === 'function') {
                    window.calendar.refetchEvents();
                }
            });
            // Dispara click en el calendario activo al cargar
            var activeBox = $('.calendar-box.selected');
            if (activeBox.length) {
                // Mostrar modal con información del calendario activo al cargar
                var id_calendar = activeBox.data('id');
                var calendarName = activeBox.clone().find('small, .badge').remove().end().text().trim();
                
                $('#active-calendar-name').text(calendarName);
                $('#active-calendar-id').text(id_calendar);
                $('#session-calendar-id').text(id_calendar);
                $('#calendarActiveModal').modal('show');
                
                activeBox.click();
            }
        });
    </script>

    <!-- Script para el slide panel de configuración -->
    <script>
        $(document).ready(function() {
            // Abrir panel de configuración desde la pestaña con engranaje
            $('#settings-tab').on('click', function() {
                $('#calendar-slide-panel').addClass('active');
                $('#panel-overlay').addClass('active');
                $('body').addClass('panel-open');
            });

            // Cerrar panel
            $('#close-calendar-panel, #panel-overlay').on('click', function() {
                closeSettingsPanel();
            });

            // Función para cerrar el panel
            function closeSettingsPanel() {
                $('#calendar-slide-panel').removeClass('active');
                $('#panel-overlay').removeClass('active');
                $('body').removeClass('panel-open');
            }

            // ESC key para cerrar panel
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#calendar-slide-panel').hasClass('active')) {
                    closeSettingsPanel();
                }
            });


        });
    </script>

<div class="debug-panel" style="position:fixed;bottom:0;right:0;z-index:9999;font-size:9px;opacity:0.5;padding:2px 6px;border:1px solid var(--border-color, #ccc);border-radius:3px;max-width:350px;max-height:220px;overflow:auto;">
<b>SESSION:</b><br>
<?php
foreach ($_SESSION as $k => $v) {
    echo htmlspecialchars($k) . ': ' . htmlspecialchars(print_r($v, true)) . '<br>';
}
?>
<b>FIN SESSION:</b><br>
</div>



</body>
</html>