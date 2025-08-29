<!DOCTYPE html>
<html lang="en" <?php 
require_once __DIR__ . '/../theme_handler.php';
echo applyThemeToHTML();
?>>
<?php
require_once __DIR__ . '/../lang/JsonLanguage.php';
require_once __DIR__ . '/../db/connection.php';

$lang = JsonLanguage::autoDetect();
$current_lang = $_SESSION['lang'] ?? $lang->getLanguage() ?? 'es';

// Verificar que la conexión esté disponible
if (!isset($pdo)) {
    // Si no hay conexión, usar datos por defecto
    $subscription_plans = [
        [
            'plan_name' => 'Básico',
            'plan_description' => 'Plan ideal para pequeñas empresas',
            'price' => 29900,
            'max_users' => 5,
            'max_projects' => 20,
            'max_storage_gb' => 2,
            'features' => '{"calendar": true, "projects": true, "basic_support": true}',
            'cycle_name' => 'mes'
        ],
        [
            'plan_name' => 'Profesional',
            'plan_description' => 'Plan recomendado para empresas en crecimiento',
            'price' => 59900,
            'max_users' => 15,
            'max_projects' => 50,
            'max_storage_gb' => 10,
            'features' => '{"calendar": true, "projects": true, "advanced_support": true, "api_access": true}',
            'cycle_name' => 'mes',
            'is_popular' => 1
        ],
        [
            'plan_name' => 'Empresarial',
            'plan_description' => 'Plan completo para grandes organizaciones',
            'price' => 99900,
            'max_users' => 50,
            'max_projects' => 200,
            'max_storage_gb' => 50,
            'features' => '{"calendar": true, "projects": true, "priority_support": true, "api_access": true, "custom_integrations": true}',
            'cycle_name' => 'mes'
        ]
    ];
    
    $global_services = [
        [
            'name' => 'Gestión de Proyectos',
            'type' => 'SaaS',
            'unit' => 'usuario',
            'duration' => 'mensual',
            'price' => 49990,
            'description' => 'Plataforma para gestión de proyectos y tareas'
        ],
        [
            'name' => 'Soporte Premium',
            'type' => 'Soporte',
            'unit' => 'empresa',
            'duration' => 'anual',
            'price' => 299990,
            'description' => 'Soporte técnico prioritario 24/7'
        ]
    ];
} else {

function format_price($price, $lang) {
    if ($lang === 'es') {
        return '$' . number_format($price, 0, ',', '.');
    } else {
        return '$' . number_format($price, 2, '.', ',');
    }
}

    // Obtener planes de suscripción activos
    $subscription_plans = [];
    try {
        $stmt = $pdo->prepare("
            SELECT sp.*, bc.cycle_name 
            FROM subscription_plans sp 
            JOIN billing_cycles bc ON sp.id_billing_cycle = bc.id_cycle 
            WHERE sp.is_active = 1 
            ORDER BY sp.sort_order ASC, sp.price ASC
        ");
        $stmt->execute();
        $subscription_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Si hay error, usar datos por defecto
        $subscription_plans = [
            [
                'plan_name' => 'Básico',
                'plan_description' => 'Plan ideal para pequeñas empresas',
                'price' => 29900,
                'max_users' => 5,
                'max_projects' => 20,
                'max_storage_gb' => 2,
                'features' => '{"calendar": true, "projects": true, "basic_support": true}',
                'cycle_name' => 'mes'
            ],
            [
                'plan_name' => 'Profesional',
                'plan_description' => 'Plan recomendado para empresas en crecimiento',
                'price' => 59900,
                'max_users' => 15,
                'max_projects' => 50,
                'max_storage_gb' => 10,
                'features' => '{"calendar": true, "projects": true, "advanced_support": true, "api_access": true}',
                'cycle_name' => 'mes',
                'is_popular' => 1
            ],
            [
                'plan_name' => 'Empresarial',
                'plan_description' => 'Plan completo para grandes organizaciones',
                'price' => 99900,
                'max_users' => 50,
                'max_projects' => 200,
                'max_storage_gb' => 50,
                'features' => '{"calendar": true, "projects": true, "priority_support": true, "api_access": true, "custom_integrations": true}',
                'cycle_name' => 'mes'
            ]
        ];
    }

    // Obtener servicios globales activos
    $global_services = [];
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM services 
            WHERE id_company = 1 AND status = 'active' 
            ORDER BY price ASC
        ");
        $stmt->execute();
        $global_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Si hay error, usar datos por defecto
        $global_services = [
            [
                'name' => 'Gestión de Proyectos',
                'type' => 'SaaS',
                'unit' => 'usuario',
                'duration' => 'mensual',
                'price' => 49990,
                'description' => 'Plataforma para gestión de proyectos y tareas'
            ],
            [
                'name' => 'Soporte Premium',
                'type' => 'Soporte',
                'unit' => 'empresa',
                'duration' => 'anual',
                'price' => 299990,
                'description' => 'Soporte técnico prioritario 24/7'
            ]
        ];
    }
}

function getFeatureIcon($feature) {
    $icons = [
        'calendar' => 'fas fa-calendar-alt',
        'projects' => 'fas fa-project-diagram',
        'basic_support' => 'fas fa-headset',
        'advanced_support' => 'fas fa-headset',
        'priority_support' => 'fas fa-headset',
        'api_access' => 'fas fa-code',
        'custom_integrations' => 'fas fa-plug',
        'white_label' => 'fas fa-tag',
        'advanced_analytics' => 'fas fa-chart-line'
    ];
    return $icons[$feature] ?? 'fas fa-check';
}
?>

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

	<!-- BOOTSTRAP-->
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" media='all' integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

	<!-- FONTS-->
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600&family=Open+Sans:wght@300;400;600&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css2?family=Comfortaa&display=swap" rel="stylesheet">

	<!-- CSS STYLE-->
	<link rel="stylesheet" href="css/style.css">
	<title><?php $title ?></title>
	
	<style>
		/* Estilos para los planes de suscripción */
		.ribbon {
			width: 150px;
			height: 150px;
			position: absolute;
			top: -10px;
			right: -10px;
			overflow: hidden;
		}
		
		.ribbon span {
			position: absolute;
			display: block;
			width: 225px;
			padding: 8px 0;
			background-color: #28a745;
			box-shadow: 0 5px 10px rgba(0,0,0,.1);
			color: #fff;
			text-shadow: 0 1px 1px rgba(0,0,0,.2);
			text-transform: uppercase;
			text-align: center;
			right: -25px;
			top: 30px;
			transform: rotate(45deg);
			font-size: 12px;
			font-weight: bold;
		}
		
		.specs {
			background: var(--bg-secondary);
			border-radius: 10px;
			padding: 15px;
			margin: 15px 0;
		}
		
		.spec-item {
			padding: 10px 5px;
		}
		
		.spec-value {
			font-size: 1.5rem;
			font-weight: bold;
			color: var(--text-primary);
			margin: 5px 0;
		}
		
		.spec-label {
			font-size: 0.8rem;
			color: var(--text-muted);
			text-transform: uppercase;
		}
		
		/* Estilos para los servicios */
		.service-icon {
			height: 80px;
			display: flex;
			align-items: center;
			justify-content: center;
		}
		
		.detail-item {
			padding: 10px 5px;
			border-radius: 8px;
			background: var(--bg-tertiary);
			margin: 5px 0;
		}
		
		.detail-label {
			font-size: 0.7rem;
			color: var(--text-muted);
			text-transform: uppercase;
			margin-bottom: 3px;
		}
		
		.detail-value {
			font-size: 0.9rem;
			font-weight: 600;
			color: var(--text-primary);
		}
		
		.service-description {
			border-top: 1px solid var(--border-color);
			padding-top: 15px;
		}
		
		/* Responsive */
		@media (max-width: 768px) {
			.specs .row {
				margin: 0;
			}
			
			.spec-item {
				padding: 5px;
			}
			
			.spec-value {
				font-size: 1.2rem;
			}
			
			.detail-item {
				padding: 8px 3px;
			}
		}
	</style>
	
	<!-- THEME SWITCHER JS -->
	<script src="js/theme-switcher.js"></script>
</head>

<body>
<!-- -------------------------------------- MENU -------------------------------------------- -->
<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>


<!-- ----------------------- MAIN CONTENT --------------------------------------- -->
<div class="row m-0 p-0">
	<div class="col-6 p-5">
		<div class="container mx-5 mt-3">
			<h2 class="display-4"> <small>Make your life <span class="text-primary">memorable</span></small> </h2>
			<p class="lead">Keep your life organized. We are the solution that you need.</p>
		</div>
		<div class="container mx-5 mr-5 mt-3 d-inline-block">
			<h4 class="text-primary pr-5"><i class="fas fa-rocket pr-3"></i>TAKE OFF YOUR BUSSINESS</h3>
			<p class="text-muted pr-5">Keep all your projects in one place. We offer you a simple Kanban board where you will be able to add as many projects and tasks as you want.</p>
			<h4 class="text-primary pr-5"><i class="far fa-calendar-check pr-3"></i>FORGET ABOUT FORGETTING</h3>
			<p class="text-muted pr-5">Always late? Let us take your agenda for you. We offer you a completely scalable calendar where you can schedule all your events and see them easily. </p>
				
		</div>
		<div class="container d-flex justify-content-center mt-4">
			<a href="register.php" class="btn btn-sign-up">GET STARTED <i class="fas fa-arrow-circle-right pl-2"></i></a>
		</div>
	</div>
	<div class="col-6">
		<img class="img-fluid" src="img/1.png" alt="project_management">		
	</div>	
</div>

<!-- ===================== PLANES Y PRECIOS ===================== -->
<div class="container my-5">
	<h2 class="text-center mb-4 font-weight-bold">Planes de Suscripción</h2>
	<div class="row justify-content-center">
		<?php foreach ($subscription_plans as $plan): ?>
			<?php 
			$features = json_decode($plan['features'], true);
			$is_popular = $plan['is_popular'] ?? false;
			$card_class = $is_popular ? 'border-success' : 'border-primary';
			$header_class = $is_popular ? 'bg-success' : 'bg-primary';
			$btn_class = $is_popular ? 'btn-success' : 'btn-outline-primary';
			?>
			<div class="col-md-4 mb-4">
				<div class="card shadow h-100 <?php echo $card_class; ?>">
					<?php if ($is_popular): ?>
						<div class="ribbon ribbon-top-right"><span>Popular</span></div>
					<?php endif; ?>
					<div class="card-header <?php echo $header_class; ?> text-white text-center">
						<h4 class="my-0"><?php echo htmlspecialchars($plan['plan_name']); ?></h4>
					</div>
					<div class="card-body text-center">
						<h2 class="card-title pricing-card-title">
							<?php echo format_price($plan['price'], $current_lang); ?> 
							<small class="text-muted">/ <?php echo $plan['cycle_name'] ?? 'mes'; ?></small>
						</h2>
						<p class="text-muted"><?php echo htmlspecialchars($plan['plan_description']); ?></p>
						
						<!-- Especificaciones del plan -->
						<div class="specs mb-3">
							<div class="row text-center">
								<div class="col-4">
									<div class="spec-item">
										<i class="fas fa-users text-primary"></i>
										<div class="spec-value"><?php echo $plan['max_users']; ?></div>
										<div class="spec-label">Usuarios</div>
									</div>
								</div>
								<div class="col-4">
									<div class="spec-item">
										<i class="fas fa-project-diagram text-success"></i>
										<div class="spec-value"><?php echo $plan['max_projects']; ?></div>
										<div class="spec-label">Proyectos</div>
									</div>
								</div>
								<div class="col-4">
									<div class="spec-item">
										<i class="fas fa-hdd text-info"></i>
										<div class="spec-value"><?php echo $plan['max_storage_gb']; ?>GB</div>
										<div class="spec-label">Almacenamiento</div>
									</div>
								</div>
							</div>
						</div>
						
						<!-- Características del plan -->
						<ul class="list-unstyled mt-3 mb-4">
							<?php if ($features): ?>
								<?php foreach ($features as $feature => $enabled): ?>
									<?php if ($enabled): ?>
										<li class="mb-2">
											<i class="<?php echo getFeatureIcon($feature); ?> text-success mr-2"></i>
											<?php 
											$feature_names = [
												'calendar' => 'Calendario completo',
												'projects' => 'Gestión de proyectos',
												'basic_support' => 'Soporte básico',
												'advanced_support' => 'Soporte avanzado',
												'priority_support' => 'Soporte prioritario',
												'api_access' => 'Acceso a API',
												'custom_integrations' => 'Integraciones personalizadas',
												'white_label' => 'White label',
												'advanced_analytics' => 'Analíticas avanzadas'
											];
											echo $feature_names[$feature] ?? ucfirst($feature);
											?>
										</li>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</ul>
						
						<a href="register-company.php" class="btn <?php echo $btn_class; ?> btn-block">
							<?php echo $is_popular ? 'Comenzar Ahora' : 'Seleccionar Plan'; ?>
						</a>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>



<!-- -------------------------- FOOTER --------------------------- -->
<?php require 'footer.php'; ?>

<!-- --------------------- JS SCRIPTS JQUERY + POPPER + BOOTSTRAP ------------------------- -->
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

</body>
</html>