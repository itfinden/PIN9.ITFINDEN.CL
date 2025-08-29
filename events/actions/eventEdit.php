<?php session_start();

if (isset($_SESSION['user'])) {
} else {
	header('Location: ../../login.php');
	die();
}
	
require_once('../../db/functions.php');
$database = new Database();
$db = $database->connection();



if (isset($_POST['delete']) && isset($_POST['id_event'])){
		
		$id_event = filter_var($_POST['id_event'], FILTER_SANITIZE_NUMBER_INT);

		$sql = "DELETE FROM calendar WHERE id_event = :id_event";

		$query = $db->prepare($sql);
		if ($query == false) {
			error_log("Database prepare error: " . print_r($db->errorInfo(), true));
			http_response_code(500);
			die('There was a problem while preparing the query');
		}

		$res = $query->execute([':id_event' => $id_event]);
		if ($res == false) {
			error_log("Database execute error: " . print_r($query->errorInfo(), true));
			http_response_code(500);
			die('There was a problem while running the query');
		}
		
		audit_log('Eliminar evento', 'ID evento: ' . $id_event);
		
}else if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['start_date']) && isset($_POST['end_date']) && isset($_POST['colour']) && isset($_POST['id_event']) ){
		
		$id_event = filter_var($_POST['id_event'], FILTER_SANITIZE_NUMBER_INT);
		$title = htmlspecialchars($_POST['title'], ENT_QUOTES, 'UTF-8');
		$description = isset($_POST['description']) ? $_POST['description'] : '';
		$description = preg_replace('/^<p>(.*)<\/p>$/si', '$1', $description);
		$start_date = $_POST['start_date'];
		$end_date = $_POST['end_date'];
		$start_time = isset($_POST['start_time']) ? $_POST['start_time'] : '00:00';
		$end_time = isset($_POST['end_time']) ? $_POST['end_time'] : '00:00';
		$start_datetime_str = $start_date . ' ' . $start_time;
		$end_datetime_str = $end_date . ' ' . $end_time;

		$colour = htmlspecialchars($_POST['colour'], ENT_QUOTES, 'UTF-8');
		$id_calendar = $_SESSION['id_calendar'];

		// Convertir fechas del formato Y-m-d H:i a Y-m-d H:i:s
		$start_dt = DateTime::createFromFormat('Y-m-d H:i', $start_datetime_str);
		$end_dt = DateTime::createFromFormat('Y-m-d H:i', $end_datetime_str);

		if (!$start_dt) {
			$start_dt = new DateTime(date('Y-m-01 00:00:00'));
		}
		if (!$end_dt) {
			$end_dt = new DateTime(date('Y-m-01 00:00:00'));
		}

		$start_date_formatted = $start_dt->format('Y-m-d H:i:s');
		$end_date_formatted = $end_dt->format('Y-m-d H:i:s');
		
		
		$sql = "UPDATE calendar SET title = :title, description = :description, start_date = :start_date, end_date = :end_date, colour = :colour, id_calendar = :id_calendar 
		WHERE id_event = :id_event";
		
		$query = $db->prepare($sql);
		if ($query == false) {
			error_log("Database prepare error: " . print_r($db->errorInfo(), true));
			http_response_code(500);
			die('There was a problem while preparing the query');
		}

		$sth = $query->execute([
			':id_event' => $id_event,
			':title' => $title,
			':description' => $description,
			':start_date' => $start_date_formatted,
			':end_date' => $end_date_formatted,
			':colour' => $colour,
			':id_calendar' => $id_calendar
		]);
		
		if ($sth == false) {
			error_log("Database execute error: " . print_r($query->errorInfo(), true));
			http_response_code(500);
			die('There was a problem while running the query');
		}

		audit_log('Editar evento', 'ID evento: ' . $id_event . ', Título: ' . $title);

}
	header('Location: ../../calendar.php');
?>