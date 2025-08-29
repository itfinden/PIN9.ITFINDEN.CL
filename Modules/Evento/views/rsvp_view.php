<?php
// Variables disponibles: $guest, $token
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>RSVP - <?php echo htmlspecialchars($guest['event_title']); ?></title>
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" crossorigin="anonymous">
</head>
<body>
	<div class="container mt-5" style="max-width: 640px;">
		<div class="card">
			<div class="card-body text-center">
				<h4 class="card-title mb-3"><?php echo htmlspecialchars($guest['event_title']); ?></h4>
				<p class="text-muted">Hola <?php echo htmlspecialchars($guest['full_name']); ?>, confirma tu asistencia.</p>
				<div class="d-flex justify-content-around mt-4">
					<a class="btn btn-success" href="/Modules/Evento/rsvp.php?token=<?php echo urlencode($token); ?>&action=accept">Aceptar</a>
					<a class="btn btn-outline-danger" href="/Modules/Evento/rsvp.php?token=<?php echo urlencode($token); ?>&action=reject">Rechazar</a>
				</div>
				<p class="mt-3 text-muted" style="font-size: 0.9em;">Tu respuesta quedará registrada automáticamente.</p>
			</div>
		</div>
	</div>
</body>
</html>



