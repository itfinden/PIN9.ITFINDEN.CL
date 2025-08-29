<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>RSVP</title>
	<link rel="stylesheet" href="/css/style.css">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" crossorigin="anonymous">
</head>
<body>
	<div class="container mt-5" style="max-width: 640px;">
		<div class="card">
			<div class="card-body text-center">
				<?php if ($status === 'accepted'): ?>
					<h4 class="text-success">¡Gracias por confirmar!</h4>
					<p class="text-muted">Se ha registrado tu asistencia.</p>
				<?php else: ?>
					<h4 class="text-danger">Has rechazado la invitación</h4>
					<p class="text-muted">Lamentamos que no puedas asistir.</p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</body>
</html>



