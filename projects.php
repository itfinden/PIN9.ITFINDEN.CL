<?php
session_start();
// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/lang/language_handler.php';
require_once __DIR__ . '/db/functions.php';
require_once __DIR__ . '/security/check_access.php';
require_once __DIR__ . '/config/setting.php';

// Verificar permiso para gestionar permisos por rol
if (!isset($_SESSION['id_user'])) {
    // Usuario no autenticado, redirigir a login
     header('Location: ' . SITE_URL . 'login.php');
    exit();
}

// Verificación alternativa para superadmin
if (isset($_SESSION['is_superadmin']) && $_SESSION['is_superadmin']) {
    // Superadmin puede acceder sin verificación de permiso específico
} else {
    // Para usuarios normales, verificar permiso
    verificarPermisoVista($_SESSION['id_user'], 30); // manage_projects
}

$database = new Database();
$connection = $database->connection();


// -------------------- SHOWING PROJECTS -------------------------
$projects = [];
if (isset($_SESSION['id_user']) && !empty($_SESSION['id_user'])) {
    $projects = $connection->prepare("SELECT SQL_CALC_FOUND_ROWS * FROM projects WHERE id_user = ? ORDER BY id_project DESC") ;			
    $projects->execute(array($_SESSION['id_user']));
    $projects = $projects->fetchAll();
}


// -------------------- SHOWING TASKS -------------------------
if($_SERVER['REQUEST_METHOD'] == 'GET') {
	if(isset($_GET['idProject'])) {	

		$id_project_for_task = limpiarString($_GET['idProject']);


		// Mostrar todas las tareas del proyecto, sin filtrar por usuario
		$show_tasks = $connection->prepare("SELECT * FROM tasks WHERE id_project = ? ORDER BY deadline DESC");
		$show_tasks->execute(array($id_project_for_task));
		$show_tasks = $show_tasks->fetchAll();
	}	
}
// -------------------- SHOWING NAME OF PROJECTS -------------------------
$nombre_proyecto_actual = '';
if (isset($id_project_for_task) && $id_project_for_task) {
    foreach ($projects as $p) {
        if ($p['id_project'] == $id_project_for_task) {
            $nombre_proyecto_actual = $p['project_name'];
            break;
        }
    }
}
// -------------------- GOOD BYE -------------------------

require 'views/projects.view.php';


if($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	// --------------------- ADDING NEW PROJECTS -------------------------
	if ( isset($_POST['project_name']) AND isset($_POST['start_date']) AND isset($_POST['end_date']) ) {


		$project_name = htmlspecialchars(trim($_POST['project_name'] ?? ''), ENT_QUOTES, 'UTF-8');
		$project_description = htmlspecialchars(trim($_POST['project_description'] ?? ''), ENT_QUOTES, 'UTF-8');
		$project_colour = htmlspecialchars(trim($_POST['project_colour'] ?? ''), ENT_QUOTES, 'UTF-8');
		$start_date = trim($_POST['start_date'] ?? '');
		$start_date = date("Y-m-d", strtotime($start_date));
		$end_date = trim($_POST['end_date'] ?? '');
		$end_date = date("Y-m-d", strtotime($end_date));
		$id_user = htmlspecialchars(trim($_POST['id_user'] ?? ''), ENT_QUOTES, 'UTF-8');


		$id_user = (int)$id_user; 


		$statement = $connection->prepare('INSERT INTO projects (id_user, project_name, project_description, project_colour, start_date, end_date) VALUES
		(?, ?, ?, ?, ?, ?)');
		$statement->execute(array($id_user, $project_name, $project_description, $project_colour, $start_date, $end_date));	
		$add_project = $statement->fetch();
		if (isset($add_project)) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',
				title: 'Congrats!',
				text: 'Your project has been successfully created!',
				showConfirmButton: false,
				timer: 1200,
			}).then(function(){ 
				location.href = 'projects.php'				
				});";
			echo '</script>';
			audit_log('Crear proyecto', 'Nombre: ' . $_POST['project_name']);
		}

	}

	// -------------------- DELETING PROJECT --------------------------
	if(isset($_POST['id_project'])) {	

		//$id_project = filter_var(htmlspecialchars($_POST['id_project']), FILTER_SANITIZE_STRING);		
		$id_project = htmlspecialchars(trim($_POST['id_project'] ?? ''), ENT_QUOTES, 'UTF-8');

		$del_project = $connection->prepare("DELETE FROM projects WHERE id_project =?") ;			
		$del_project->execute(array($id_project));
		if ($del_project!==FALSE) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',
				title: 'Good bye!',
				text: 'Your project has been deleted!',
				showConfirmButton: false,
				timer: 1200,
			}).then(function(){ 
				location.href = 'projects.php'
				});";
			echo '</script>';			
			audit_log('Eliminar proyecto', 'ID: ' . $_POST['id_project']);
		} 

	}

	// --------------------------- EDITING PROJECT -------------------------------
	if(isset($_POST['edit_id_project'])) {	
		/*
		$edit_id_project = filter_var(htmlspecialchars($_POST['edit_id_project']), FILTER_SANITIZE_STRING);
		$edit_project_name = filter_var(htmlspecialchars($_POST['edit_project_name']), FILTER_SANITIZE_STRING);
		$edit_project_description = filter_var(htmlspecialchars($_POST['edit_project_description']), FILTER_SANITIZE_STRING);
		$edit_project_colour = filter_var(htmlspecialchars($_POST['edit_project_colour']), FILTER_SANITIZE_STRING);
		$edit_start_date= filter_var(htmlspecialchars($_POST['edit_start_date']), FILTER_SANITIZE_STRING); 
		$edit_start_date= date("Y-m-d", strtotime($edit_start_date));
		$edit_end_date= filter_var(htmlspecialchars($_POST['edit_end_date']), FILTER_SANITIZE_STRING); 
		$edit_end_date= date("Y-m-d", strtotime($edit_end_date));
*/
		$edit_id_project = htmlspecialchars(trim($_POST['edit_id_project'] ?? ''), ENT_QUOTES, 'UTF-8');
		$edit_project_name = htmlspecialchars(trim($_POST['edit_project_name'] ?? ''), ENT_QUOTES, 'UTF-8');
		$edit_project_description = htmlspecialchars(trim($_POST['edit_project_description'] ?? ''), ENT_QUOTES, 'UTF-8');
		$edit_project_colour = htmlspecialchars(trim($_POST['edit_project_colour'] ?? ''), ENT_QUOTES, 'UTF-8');
		$edit_start_date = trim($_POST['edit_start_date'] ?? '');
		$edit_start_date = date("Y-m-d", strtotime($edit_start_date));
		$edit_end_date = trim($_POST['edit_end_date'] ?? '');
		$edit_end_date = date("Y-m-d", strtotime($edit_end_date));
		
		$statement = $connection->prepare('UPDATE projects SET project_name=?, project_description=?, project_colour=?, start_date=?, end_date=? WHERE id_project=?');
		$statement->execute(array($edit_project_name, $edit_project_description, $edit_project_colour, $edit_start_date, $edit_end_date, $edit_id_project));	
		$edit_project = $statement->fetch();
		if (isset($edit_project)) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',
				title: 'Well Done!',
				text: 'Your project has been updated!',
				showConfirmButton: false,
				timer: 1200,
			}).then(function(){ 
				location.href = 'projects.php'
				});";
			echo '</script>';
			audit_log('Editar proyecto', 'ID: ' . $_POST['edit_id_project'] . ', Nombre: ' . $_POST['edit_project_name']);
		} 

	}
	

	// --------------------------- ADDING NEW TASK -------------------------------
	if ( isset($_POST['task_name']) AND isset($_POST['id_task_project']) AND isset($_POST['id_user']) ) {
		$id_project = limpiarString($_POST['id_task_project'] ?? '');
		$id_project = (int)$id_project; 
		$id_user = limpiarString($_POST['id_user'] ?? '');
		$id_user = (int)$id_user; 
		$task_status = limpiarString($_POST['task_status'] ?? '');
		$task_status = (int)$task_status; 
		$task_name = limpiarString($_POST['task_name'] ?? '');
		$task_description = limpiarString($_POST['task_description'] ?? '');
		$task_colour = limpiarString($_POST['task_colour'] ?? '');
		$deadline = limpiarString($_POST['deadline'] ?? ''); 
		
		// Validar y formatear la fecha
		if (!empty($deadline)) {
			$deadline = date("Y-m-d", strtotime($deadline));
		} else {
			$deadline = '1970-01-01'; // Fecha por defecto si está vacía
		}

		$statement = $connection->prepare('INSERT INTO tasks (id_user, id_project, task_status, task_name, task_description, task_colour, deadline) VALUES
		(?, ?, ?, ?, ?, ?, ?)');
		$statement->execute(array($id_user, $id_project, $task_status, $task_name, $task_description, $task_colour, $deadline));	
		$add_task = $statement->fetch();
		if (isset($add_task)) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',
				title: 'Congrats!',
				text: 'Your task has been successfully created!',
				showConfirmButton: false,
				timer: 1200,
			}).then(function(){ 
				location.href = 'projects.php?idProject=" . $id_project . "';
			});";
			echo '</script>';			
		}
		
	}

	// -------------------- DELETING TASK --------------------------
	if(isset($_POST['id_task'])) {	
		
		$id_task = filter_var(htmlspecialchars($_POST['id_task']), FILTER_SANITIZE_STRING);	
		$id_project = filter_var(htmlspecialchars($_POST['id_project']), FILTER_SANITIZE_STRING);	

		$id_task = htmlspecialchars(trim($_POST['id_task'] ?? ''), ENT_QUOTES, 'UTF-8');
		$id_project = htmlspecialchars(trim($_POST['id_project'] ?? ''), ENT_QUOTES, 'UTF-8');

		$del_task = $connection->prepare("DELETE FROM tasks WHERE id_task =?") ;			
		$del_task->execute(array($id_task));
		if ($del_task!==FALSE) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',
				title: 'Good bye!',
				text: 'Your task has been deleted!',
				showConfirmButton: false,
				timer: 1200,
			}).then(function(){ 
				location.href = 'projects.php?idProject=" . $id_project . "';
			});";
			echo '</script>';			
		} 

	}

	// --------------------------- EDITING TASK -------------------------------
	if(isset($_POST['edit_id_task'])) {	
		$id_project = (int)($_POST['edit_id_task_project'] ?? 0);
		$edit_id_task = (int)($_POST['edit_id_task'] ?? 0);
		$edit_task_name = htmlspecialchars(trim($_POST['edit_task_name'] ?? ''), ENT_QUOTES, 'UTF-8');
		$edit_task_description = htmlspecialchars(trim($_POST['edit_task_description'] ?? ''), ENT_QUOTES, 'UTF-8');
		$edit_task_colour = htmlspecialchars(trim($_POST['edit_task_colour'] ?? ''), ENT_QUOTES, 'UTF-8');
		$edit_task_status = (int)($_POST['edit_task_status'] ?? 1);
		$deadline = trim($_POST['edit_deadline'] ?? '');

		// Validar y formatear la fecha
		if (!empty($deadline)) {
			$deadline = date("Y-m-d", strtotime($deadline));
		} else {
			$deadline = '1970-01-01'; // Fecha por defecto si está vacía
		}

		
		$statement = $connection->prepare('UPDATE tasks SET task_name=?, task_description=?, task_colour=?, deadline=?, task_status=? WHERE id_task=?');
		$statement->execute(array($edit_task_name, $edit_task_description, $edit_task_colour, $deadline, $edit_task_status, $edit_id_task));	
		$edit_task = $statement->fetch();
		if (isset($edit_task)) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',
				title: 'Well Done!',
				text: 'Your task has been updated!',
				showConfirmButton: false,
				timer: 1200,
			}).then(function(){ 
				location.href = 'projects.php?idProject=" . $id_project . "';
			});";
			echo '</script>';
		} 

	}

	// --------------------------- MOVING TASK TO THE RIGHT -------------------------------
	if(isset($_POST['id_task_right'])) {	
		/*
		$id_project_right = filter_var(htmlspecialchars($_POST['id_project_right']), FILTER_SANITIZE_STRING);
		$id_task_right = filter_var(htmlspecialchars($_POST['id_task_right']), FILTER_SANITIZE_STRING);
		$task_status = filter_var(htmlspecialchars($_POST['task_status']), FILTER_SANITIZE_STRING);
		*/

		$id_project_right = (int)($_POST['id_project_right'] ?? 0);
		$id_task_right = (int)($_POST['id_task_right'] ?? 0);
		//$task_status = (int)($_POST['task_status'] ?? 0);
		$task_status = htmlspecialchars(trim($_POST['task_status'] ?? ''), ENT_QUOTES, 'UTF-8');

		$new_status = ((int)$task_status + 1);
				
		$statement = $connection->prepare('UPDATE tasks SET task_status=? WHERE id_task=?');
		$statement->execute(array($new_status, $id_task_right));	
		$move_task_right = $statement->fetch();
		if (isset($move_task_right)) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',				
				showConfirmButton: false,
				title: 'Well Done!',
				timer: 500,
			}).then(function(){ 
				location.href = 'projects.php?idProject=" . $id_project_right . "';
			});";
			echo '</script>';
		} 

	}

	// --------------------------- MOVING TASK TO THE LEFT -------------------------------
	if(isset($_POST['id_task_left'])) {	
		/*
		$id_project_left = filter_var(htmlspecialchars($_POST['id_project_left']), FILTER_SANITIZE_STRING);
		$id_task_left = filter_var(htmlspecialchars($_POST['id_task_left']), FILTER_SANITIZE_STRING);
		$task_status = filter_var(htmlspecialchars($_POST['task_status']), FILTER_SANITIZE_STRING);
		*/
		$id_project_left = (int)($_POST['id_project_left'] ?? 0);
		$id_task_left = (int)($_POST['id_task_left'] ?? 0);
		//$task_status = (int)($_POST['task_status'] ?? 0);
		$task_status = htmlspecialchars(trim($_POST['task_status'] ?? ''), ENT_QUOTES, 'UTF-8');
		$new_status = ((int)$task_status - 1);
				
		$statement = $connection->prepare('UPDATE tasks SET task_status=? WHERE id_task=?');
		$statement->execute(array($new_status, $id_task_left));	
		$move_task_left = $statement->fetch();
		if (isset($move_task_left)) {
			echo '<script language="javascript">';
			echo "Swal.fire({
				position: 'top-end',
				icon: 'success',				
				showConfirmButton: false,
				title: 'Well Done!',
				timer: 500,
			}).then(function(){ 
				location.href = 'projects.php?idProject=" . $id_project_left . "';
			});";
			echo '</script>';
		} 

	}

}


?>