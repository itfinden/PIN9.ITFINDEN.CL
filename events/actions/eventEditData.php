<?php session_start();



require_once('../../db/functions.php');
	$database = new Database();
	$db = $database->connection();

	if (isset($_POST['Event'][0]) && isset($_POST['Event'][1]) && isset($_POST['Event'][2])){
		
		$id_event = filter_var($_POST['Event'][0], FILTER_SANITIZE_NUMBER_INT);
		$start_date = $_POST['Event'][1];
		$end_date = $_POST['Event'][2];

		// Convertir fechas del formato d/m/Y H:i a Y-m-d H:i:s
		$start_dt = DateTime::createFromFormat('d/m/Y H:i', $start_date);
		$end_dt = DateTime::createFromFormat('d/m/Y H:i', $end_date);

		if (!$start_dt) {
			$start_dt = new DateTime(date('Y-m-01 00:00:00'));
		}
		if (!$end_dt) {
			$end_dt = new DateTime(date('Y-m-01 00:00:00'));
		}

		$start_date_formatted = $start_dt->format('Y-m-d H:i:s');
		$end_date_formatted = $end_dt->format('Y-m-d H:i:s');

		// DEBUG: Mostrar fechas recibidas
		error_log("Fecha inicio recibida: " . $start_date);
		error_log("Fecha fin recibida: " . $end_date);

		// DEBUG: Mostrar fechas procesadas
		error_log("Fecha inicio procesada: " . $start_date_formatted);
		error_log("Fecha fin procesada: " . $end_date_formatted);

		
		$sql = "UPDATE calendar SET start_date = :start_date, end_date = :end_date WHERE id_event = :id_event";
		
		$query = $db->prepare($sql);
		if ($query == false) {
			error_log("Database prepare error: " . print_r($db->errorInfo(), true));
			http_response_code(500);
			die('There was a problem while preparing the query');
		}

		$sth = $query->execute([
			':id_event' => $id_event,
			':start_date' => $start_date_formatted,
			':end_date' => $end_date_formatted
		]);
		
		if ($sth == false) {
			error_log("Database execute error: " . print_r($query->errorInfo(), true));
			http_response_code(500);
			die('There was a problem while running the query');
		} else {
			audit_log('Actualizar fechas evento', 'ID evento: ' . $id_event . ', Nueva fecha inicio: ' . $start_date_formatted);
			die('OK');
		}

	}
	//header('Location: '.$_SERVER['HTTP_REFERER']);
?>
