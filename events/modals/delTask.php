
<?php
if (isset($_SESSION['user'])) {
} else {
	header('Location: login.php');
	die();
}
?>

<style>
/* Estilos específicos para el modal de eliminar tarea */
#task-delete-<?php echo $i; ?> {
    z-index: 9999 !important;
}

#task-delete-<?php echo $i; ?> .modal-dialog {
    max-width: 500px;
    margin: 1.75rem auto;
}

#task-delete-<?php echo $i; ?> .modal-content {
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

/* Asegurar que el modal esté por encima de todo */
#task-delete-<?php echo $i; ?> .modal-backdrop {
    z-index: 9998 !important;
}

/* Forzar que el modal se muestre fuera del contenedor */
#task-delete-<?php echo $i; ?> {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    overflow: auto !important;
}

/* Asegurar que los modales no se corten */
#task-delete-<?php echo $i; ?> .modal-dialog-centered {
    display: flex !important;
    align-items: center !important;
    min-height: calc(100% - 3.5rem) !important;
}

/* Prevenir que los modales se cierren automáticamente */
#task-delete-<?php echo $i; ?> {
    overflow: hidden !important;
}

#task-delete-<?php echo $i; ?> .modal-backdrop {
    pointer-events: none !important;
}

#task-delete-<?php echo $i; ?> .modal-backdrop.show {
    pointer-events: auto !important;
}

/* Asegurar que los modales se mantengan abiertos */
#task-delete-<?php echo $i; ?>.show {
    display: block !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}

/* Prevenir conflictos con otros elementos */
#task-delete-<?php echo $i; ?> .modal-dialog {
    pointer-events: auto !important;
    z-index: 10000 !important;
}
</style>
<!-- --------------------------------------- DELETE TASK MODAL ------------------------------------------------------ -->
<div id="task-delete-<?php echo $i; ?>" class="col-sm modal fade" role="dialog" style="z-index: 9999;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="lead text-primary" >Are you sure?</h3>
                <a class="close text-dark btn" data-dismiss="modal">×</a>
            </div>
            <form name="task" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" role="form">
                <div class="modal-body">
                    <p class="text-dark">Do you want to delete <i class="text-primary"><?php echo $s['task_name']; ?> </i> ?</p>
                    <p class="text-dark">You won't be able to revert this!</p>
               
                    <div class="form-group">
                        <input hidden type="int" name="id_task" value="<?php echo $s['id_task']; ?>">
                    </div>	
                    <div class="form-group">
                        <input hidden type="int" name="id_project" value="<?php echo $s['id_project']; ?>">
                    </div>				
                </div>
                <div class="modal-footer">					
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">Delete</button>
                </div>
            </form>
        </div>
    </div>
</div>
