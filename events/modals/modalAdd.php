<?php
require_once __DIR__ . '/../../db/functions.php';
$id_user = $_SESSION['id_user'] ?? null;
$id_company = null;
$calendars = [];

// Obtener el calendario activo de la sesión
$active_calendar_id = $_SESSION['id_calendar'] ?? null;
$active_calendar_name = $_SESSION['calendar_name'] ?? 'Selecciona un calendario';

if ($id_user) {
    $empresa = obtenerEmpresaUsuario($id_user);
    if ($empresa && isset($empresa['id_company'])) {
        $id_company = $empresa['id_company'];
        $calendars = getActiveCalendarsByCompany($id_company);
    }
}
?>
<div class="modal fade" id="ModalAdd" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
        <form class="form-horizontal" method="POST" action="events/actions/eventAdd.php" onsubmit="return validaForm(this);">
        
            <div class="modal-header d-flex justify-content-between">
                <h4 class="modal-title" id="myModalLabel">New event</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Columna Izquierda -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="title" class="control-label">Title</label>
                            <input type="text" name="title" class="form-control" id="title" placeholder="title" required>
                        </div>

                        <div class="form-group">
                            <label for="start_date" class="control-label">Start date</label>
                            <input type="date" name="start_date" class="form-control" id="start_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_time" class="control-label">Start time</label>
                            <select name="start_time" class="form-control" id="start_time" required>
                                <?php
                                for ($h = 0; $h < 24; $h++) {
                                    foreach ([0, 30] as $m) {
                                        $time = sprintf('%02d:%02d', $h, $m);
                                        echo "<option value='$time'>$time</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Columna Derecha -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="colour" class="control-label">Colour</label>
                            <select name="colour" class="form-control" id="colour">
                                <option value="">Pick a colour</option>
                                <option style="color:#0275d8" value="#0275d8">&#9724; Blue</option>
                                <option style="color:#5bc0de" value="#5bc0de">&#9724; Tile</option>
                                <option style="color:#5cb85c" value="#5cb85c">&#9724; Green</option>                          
                                <option style="color:#f0ad4e" value="#f0ad4e">&#9724; Orange</option>
                                <option style="color:#d9534f" value="#d9534f">&#9724; Red</option>
                                <option style="color:#292b2c" value="#292b2c">&#9724; Black</option>                          
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="end_date" class="control-label">End date</label>
                            <input type="date" name="end_date" class="form-control" id="end_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_time" class="control-label">End time</label>
                            <select name="end_time" class="form-control" id="end_time" required>
                                <?php
                                for ($h = 0; $h < 24; $h++) {
                                    foreach ([0, 30] as $m) {
                                        $time = sprintf('%02d:%02d', $h, $m);
                                        echo "<option value='$time'>$time</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Description usando todo el ancho disponible -->
                <div class="form-group">
                    <label for="description" class="control-label">Description</label>
                    <textarea name="description" class="form-control" id="description" placeholder="Description" rows="4"></textarea>
                </div>
                <!-- Campo oculto para id_calendar -->
                <input type="hidden" name="id_calendar" id="modalAdd_id_calendar" value="<?= htmlspecialchars($_SESSION['id_calendar'] ?? 1) ?>">
            
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Add</button>
            </div>
        </form>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
  // Actualizar información del calendario cuando se abre el modal
  $('#ModalAdd').on('show.bs.modal', function() {
    // Siempre usar el valor actual de la sesión
    var sessionCalendarId = '<?= htmlspecialchars($_SESSION['id_calendar'] ?? 1) ?>';
    var sessionCalendarName = '<?= htmlspecialchars($_SESSION['calendar_name'] ?? 'Selecciona un calendario') ?>';
    
    $('#modalAdd_id_calendar').val(sessionCalendarId);
    
    // Mostrar información del calendario activo
    if ($('#selected-calendar-name').length) {
      $('#selected-calendar-name').text(sessionCalendarName);
    }
  });
});
</script>