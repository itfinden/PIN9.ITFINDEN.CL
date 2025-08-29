<?php
require_once __DIR__ . '/../../db/functions.php';
$id_user = $_SESSION['id_user'] ?? null;
$id_company = null;
$calendars = [];

// Obtener el calendario activo de la sesión
$active_calendar_id = $_SESSION['id_calendar'] ?? null;

if ($id_user) {
    $empresa = obtenerEmpresaUsuario($id_user);
    if ($empresa && isset($empresa['id_company'])) {
        $id_company = $empresa['id_company'];
        $calendars = getActiveCalendarsByCompany($id_company);
    }
}
?>
<div class="modal fade" id="ModalEdit" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form class="form-horizontal" method="POST" action="events/actions/eventEdit.php" onsubmit="return validaForm(this);">
                <div class="modal-header d-flex justify-content-between">
                    <h4 class="modal-title" id="myModalLabel">Event edit</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="title" class="col-sm-4 control-label">Title</label>
                        <div class="col-sm-12">
                            <input type="text" name="title" class="form-control" id="title" placeholder="Title" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" class="col-sm-4 control-label">Description</label>
                        <div class="col-sm-12">
                            <textarea name="description" class="form-control" id="description" placeholder="Description"></textarea>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="colour" class="col-sm-4 control-label">Colour</label>
                        <div class="col-sm-12">
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
                    </div>

                    <div class="form-group">
                        <label for="start_date" class="col-sm-4 control-label">Start date</label>
                        <div class="col-sm-12 mb-2">
                            <input type="date" name="start_date" class="form-control" id="start_date" required>
                        </div>
                        <div class="col-sm-12">
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

                    <div class="form-group">
                        <label for="end_date" class="col-sm-4 control-label">End date</label>
                        <div class="col-sm-12 mb-2">
                            <input type="date" name="end_date" class="form-control" id="end_date" required>
                        </div>
                        <div class="col-sm-12">
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
                        
                    <!-- Delete the event -->
                    <div class="form-group"> 
                        <div class="col-sm-offset-2 col-sm-12">
                            <div class="checkbox">
                                <label class="text-danger"><input type="checkbox" class="mr-2" name="delete">Delete</label>
                            </div>
                        </div>
                    </div>
                    

                    
                    <input type="hidden" name="id_event" class="form-control" id="id_event">
                    <!-- Campo oculto para id_calendar -->
                    <input type="hidden" name="id_calendar" value="<?= htmlspecialchars($active_calendar_id ?? 1) ?>">
            
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
  // Actualizar información del calendario cuando se abre el modal
  $('#ModalEdit').on('show.bs.modal', function() {
    // Siempre usar el valor actual de la sesión
    var sessionCalendarId = '<?= htmlspecialchars($active_calendar_id ?? 1) ?>';
    $('input[name="id_calendar"]').val(sessionCalendarId);
  });
});
</script>