<?php
session_start();

require_once __DIR__ . '/../../db/functions.php';

if (!isset($_SESSION['user'])) {
	header('Location: /login.php');
	die();
}

$database = new Database();
$db = $database->connection();

$errors = [];
$messages = [];

try {
	$db->exec("CREATE TABLE IF NOT EXISTS evento_main (
		id_evento_main INT AUTO_INCREMENT PRIMARY KEY,
		id_company INT NULL,
		id_owner INT NULL,
		title VARCHAR(255) NOT NULL,
		event_type VARCHAR(100) DEFAULT 'Evento',
		description TEXT NULL,
		start_date DATETIME NULL,
		end_date DATETIME NULL,
		status ENUM('proximo','en_curso','finalizado') DEFAULT 'proximo',
		colour VARCHAR(20) DEFAULT '#007bff',
		id_calendar INT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");

	$db->exec("CREATE TABLE IF NOT EXISTS evento_subevent (
		id_evento_subevent INT AUTO_INCREMENT PRIMARY KEY,
		id_evento_main INT NOT NULL,
		title VARCHAR(255) NOT NULL,
		description TEXT NULL,
		start_date DATETIME NULL,
		end_date DATETIME NULL,
		colour VARCHAR(20) DEFAULT '#28a745',
		id_calendar_event INT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		INDEX (id_evento_main)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");

	$db->exec("CREATE TABLE IF NOT EXISTS evento_guest (
		id_evento_guest INT AUTO_INCREMENT PRIMARY KEY,
		id_evento_main INT NOT NULL,
		full_name VARCHAR(200) NOT NULL,
		email VARCHAR(200) NOT NULL,
		token VARCHAR(64) NOT NULL,
		status ENUM('pending','accepted','rejected') DEFAULT 'pending',
		responded_at DATETIME NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uniq_token (token),
		INDEX (id_evento_main)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");

	$db->exec("CREATE TABLE IF NOT EXISTS evento_subevent_guest (
		id_evento_subevent_guest INT AUTO_INCREMENT PRIMARY KEY,
		id_evento_subevent INT NOT NULL,
		id_evento_guest INT NOT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
		UNIQUE KEY uniq_subevent_guest (id_evento_subevent, id_evento_guest),
		INDEX (id_evento_subevent),
		INDEX (id_evento_guest)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8");
	
	// Agregar columnas begin_calendar y end_calendar a calendar_companies si no existen
	try {
		$db->exec("ALTER TABLE calendar_companies 
		ADD COLUMN IF NOT EXISTS begin_calendar date NULL AFTER updated_at,
		ADD COLUMN IF NOT EXISTS end_calendar date NULL AFTER begin_calendar");
		$messages[] = 'Columnas begin_calendar y end_calendar agregadas a calendar_companies.';
	} catch (Exception $e) {
		$messages[] = 'Columnas begin_calendar y end_calendar ya existen o no se pudieron agregar: ' . $e->getMessage();
	}
	
	$messages[] = 'Tablas del módulo Evento creadas/verificadas correctamente.';
} catch (Exception $e) {
	$errors[] = 'Error instalando tablas: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Instalación Módulo Evento</title>
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" crossorigin="anonymous">
</head>
<body>
	<div class="container mt-4">
		<h3>Instalación Módulo Evento</h3>
		<?php foreach ($messages as $m): ?>
			<div class="alert alert-success"><?php echo htmlspecialchars($m); ?></div>
		<?php endforeach; ?>
		<?php foreach ($errors as $e): ?>
			<div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
		<?php endforeach; ?>
		<a href="/Modules/Evento/dashboard.php" class="btn btn-primary">Ir al Dashboard</a>
	</div>
</body>
</html>


