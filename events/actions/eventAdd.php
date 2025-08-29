<?php session_start();



	require_once('../../db/functions.php');
	$database = new Database();
	$db = $database->connection();




	if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['start_date']) && isset($_POST['end_date']) && isset($_POST['colour'])){
		
		
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
		$id_user = $_SESSION['id_user'];
		$id_calendar = $_SESSION['id_calendar'];

		// Convertir fechas del formato Y-m-d H:i a Y-m-d H:i:s
		$start_dt = DateTime::createFromFormat('Y-m-d H:i', $start_datetime_str);
		$end_dt = DateTime::createFromFormat('Y-m-d H:i', $end_datetime_str);

		if (!$start_dt) {
			// Si la fecha es inválida, usar el primer día del mes y año actual
			$start_dt = new DateTime(date('Y-m-01 00:00:00'));
		}
		if (!$end_dt) {
			$end_dt = new DateTime(date('Y-m-01 00:00:00'));
		}

		$start_date_formatted = $start_dt->format('Y-m-d H:i:s');
		$end_date_formatted = $end_dt->format('Y-m-d H:i:s');

		error_log("Formatted dates:");
		error_log("- start_date_formatted: $start_date_formatted");
		error_log("- end_date_formatted: $end_date_formatted");

		if (!$start_date_formatted || !$end_date_formatted) {
			error_log("ERROR: Fecha inválida. No se puede grabar el evento.");
			http_response_code(400);
			die('Invalid date format');
		}

		// Use prepared statement to prevent SQL injection
		$sql = "INSERT INTO calendar(id_user, id_calendar, title, description, start_date, end_date, colour) 
		VALUES (:id_user, :id_calendar, :title, :description, :start_date, :end_date, :colour)";
		
		error_log("SQL: $sql");
		
		$query = $db->prepare($sql);
		if ($query == false) {
			error_log("Database prepare error: " . print_r($db->errorInfo(), true));
			//http_response_code(500);
			die('There was a problem while preparing the query');
		}
		
		error_log("Query prepared successfully");
		
		$params = [
			':id_user' => $id_user,
			':id_calendar' => $id_calendar,
			':title' => $title,
			':description' => $description,
			':start_date' => $start_date_formatted,
			':end_date' => $end_date_formatted,
			':colour' => $colour
		];
		
		error_log("Parameters: " . print_r($params, true));
		
		$sth = $query->execute($params);
		
		if ($sth == false) {
			error_log("Database execute error: " . print_r($query->errorInfo(), true));
			http_response_code(500);
			die('There was a problem while running the query');
		}
		
		error_log("Query executed successfully");
		error_log("Last insert ID: " . $db->lastInsertId());

		// Log the action
		$audit_result = audit_log('Crear evento', 'Título: ' . $title . ', Fecha inicio: ' . $start_date_formatted);
		error_log("Audit log result: " . ($audit_result ? 'success' : 'failed'));
		
		error_log("=== END DEBUG EVENT ADD ===");

	} else {
		error_log("=== DEBUG: Missing required parameters ===");
		error_log("POST data: " . print_r($_POST, true));
		error_log("Required fields check:");
		error_log("- title: " . (isset($_POST['title']) ? 'OK' : 'MISSING'));
		error_log("- description: " . (isset($_POST['description']) ? 'OK' : 'MISSING'));
		error_log("- start_date: " . (isset($_POST['start_date']) ? 'OK' : 'MISSING'));
		error_log("- end_date: " . (isset($_POST['end_date']) ? 'OK' : 'MISSING'));
		error_log("- colour: " . (isset($_POST['colour']) ? 'OK' : 'MISSING'));
		error_log("=== END DEBUG MISSING PARAMETERS ===");
	}
	if(isset($_SERVER['HTTP_REFERER'])){
		header("Location:".$_SERVER['HTTP_REFERER']."");
	}
?>