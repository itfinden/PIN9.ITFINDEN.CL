
<?php
if (isset($_SESSION['user'])) {
} else {
	header('Location: login.php');
	die();
}
?>
<?php
// Verificar permiso para ver el dashboard - comentado temporalmente para debug
// verificarPermisoVista($_SESSION['id_user'], 30); // view_dashboard
?>

<style>
/* Estilos para asegurar que el modal se muestre correctamente */
#new-task-modal {
    z-index: 9999 !important;
}

#new-task-modal .modal-dialog {
    max-width: 600px;
    margin: 1.75rem auto;
}

#new-task-modal .modal-content {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* Asegurar que el modal esté por encima de todo */
.modal-backdrop {
    z-index: 9998 !important;
}

/* Forzar que el modal se muestre fuera del contenedor */
.modal {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    overflow: auto !important;
}
</style>

<!-- --------------------------------------- NEW TASK MODAL ------------------------------------------------------ -->
<div id="new-task-modal" class="modal fade" role="dialog" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="lead text-primary" >Create a New Task</h3>
                <a class="close text-dark btn" data-dismiss="modal">×</a>
            </div>
            <form name="task_todo" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
                <div class="modal-body">
                    <label class="text-dark">Status<span class="text-danger pl-1">*</span></label>
                    <div class="form-group d-flex justify-content-around">                    
                        <div class="form-check">                        
                            <input class="form-check-input btn" type="radio" name="task_status" id="task_status_1" value="1" checked>
                            <label class="form-check-label" for="task_status_1">
                                To do
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input btn" type="radio" name="task_status" id="task_status_2" value="2">
                            <label class="form-check-label" for="task_status_2">
                                In progress
                            </label>
                        </div>
                        <div class="form-check" >
                            <input class="form-check-input btn" type="radio" name="task_status" id="task_status_3" value="3">
                            <label class="form-check-label" for="task_status_3">
                                Complete
                            </label>
                        </div>
                    </div>				
                    <div class="form-group">
                        <label class="text-dark" for="task_name">Task Name<span class="text-danger pl-1">*</span></label>
                        <input class="form-control" type="text" name="task_name" required>
                    </div>
                    <div class="form-group">
                        <label class="text-dark" for="task_description">Description</label>
                        <textarea class="form-control" type="text" name="task_description"></textarea>
                    </div>
					<div class="form-group">
						<label for="task_colour" class="text-dark">Priority</label>
						<select name="task_colour" class="form-control">
							<option value=""></option>
							<option style="color:#5cb85c" value="#5cb85c">&#9724; Low</option>						  
							<option style="color:#f0ad4e" value="#f0ad4e">&#9724; Medium</option>
							<option style="color:#d9534f" value="#d9534f">&#9724; High</option>													  
						</select>
				    </div>         
                    <div class="form-group d-flex justify-content-between mt-2">
                        <div class="col-12 m-0 p-1">  
                            <label class="text-dark">Deadline</label>
                            <input type="date" class="form-control" runat="server" name="deadline" min="<?php echo date('Y-m-d'); ?>" data-date-format="yyyy-mm-dd"/>
                        </div>                   
                    </div>                    
                    <div class="form-group">
                        <input hidden id="id_task_project" name="id_task_project" value=<?php echo $id_project_for_task; ?> >
                    </div>
                    <div class="form-group">
                        <input hidden id="id_user" name="id_user" value=<?php echo $_SESSION['id_user']; ?> >
                    </div>						
                </div>
                <div class="modal-footer">					
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
