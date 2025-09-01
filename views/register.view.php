<!DOCTYPE html>
<html lang="en">
<?php
require_once __DIR__ . '/../lang/Languaje.php';
require_once __DIR__ . '/../theme_handler.php';
$lang = Language::autoDetect();
?>
<head>
	<?php $title= "PIN9"; ?>
	
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

<!-- Custom theme-aware styles for register page -->
<style>
/* Theme-aware styles for register page */
body[data-theme="dark"] {
    background-color: var(--bg-primary);
    color: var(--text-primary);
}

body[data-theme="dark"] .container {
    background-color: var(--bg-primary);
}

body[data-theme="dark"] .form-control {
    background-color: var(--bg-secondary);
    border-color: var(--border-color);
    color: var(--text-primary);
}

body[data-theme="dark"] .form-control:focus {
    background-color: var(--bg-secondary);
    border-color: var(--primary-color);
    color: var(--text-primary);
    box-shadow: 0 0 0 0.2rem rgba(77, 171, 247, 0.25);
}

body[data-theme="dark"] .input-group-text {
    background-color: var(--bg-secondary);
    border-color: var(--border-color);
    color: var(--text-primary);
}

body[data-theme="dark"] .btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

body[data-theme="dark"] .btn-primary:hover {
    background-color: var(--info-color);
    border-color: var(--info-color);
}

body[data-theme="dark"] .text-primary {
    color: var(--primary-color) !important;
}

body[data-theme="dark"] .h1 {
    color: var(--text-primary);
}

body[data-theme="dark"] .err {
    color: var(--danger-color);
}

body[data-theme="dark"] .err ul {
    background-color: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 15px;
}

/* Additional theme support for navbar and other elements */
body[data-theme="dark"] .modern-navbar {
    background: var(--bg-navbar) !important;
    border-bottom: 1px solid var(--border-color) !important;
}

body[data-theme="dark"] .navbar-light .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='30' height='30' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255, 255, 255, 0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

body[data-theme="dark"] .brand-text {
    color: var(--text-primary);
}

/* CORRECCIÓN IMPORTANTE: Alineación del input-group para que el prepend tenga la misma altura */
.input-group {
    align-items: stretch;
}

.input-group-prepend {
    display: flex;
    align-items: stretch;
}

.input-group-prepend .input-group-text {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    border-radius: 0.375rem 0 0 0.375rem;
    border-right: none;
    background-color: var(--primary-color);
    color: white;
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    min-height: 38px; /* Altura estándar de Bootstrap para inputs */
}

.input-group .form-control {
    border-radius: 0 0.375rem 0.375rem 0;
    border-left: none;
    min-height: 38px; /* Altura estándar de Bootstrap para inputs */
}

/* Asegurar que el input-group tenga altura uniforme */
.input-group > .form-control,
.input-group > .input-group-prepend > .input-group-text {
    height: 38px;
    line-height: 1.5;
}

/* Estilos específicos para el tema oscuro */
body[data-theme="dark"] .input-group-prepend .input-group-text {
    background-color: var(--primary-color);
    border-color: var(--border-color);
    color: var(--text-light);
}

body[data-theme="dark"] .input-group .form-control {
    border-color: var(--border-color);
    background-color: var(--bg-secondary);
    color: var(--text-primary);
}

/* Ensure theme switcher is visible */
.theme-switcher-container {
    display: flex !important;
    align-items: center;
    padding: 8px 16px;
    margin: 0 4px;
}

.theme-switcher {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 25px;
    margin: 0;
}

.theme-switcher input {
    opacity: 0;
    width: 0;
    height: 0;
}

.theme-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--bg-secondary);
    transition: 0.3s ease;
    border-radius: 25px;
    border: 2px solid var(--border-color);
}

.theme-slider:before {
    position: absolute;
    content: "";
    height: 17px;
    width: 17px;
    left: 2px;
    bottom: 2px;
    background-color: var(--primary-color);
    transition: 0.3s ease;
    border-radius: 50%;
}

input:checked + .theme-slider {
    background-color: var(--primary-color);
}

input:checked + .theme-slider:before {
    transform: translateX(25px);
}

.theme-icon {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    font-size: 10px;
    color: #ffffff;
    transition: 0.3s ease;
}

.theme-icon.sun {
    left: 6px;
}

.theme-icon.moon {
    right: 6px;
}

/* Debug styles to ensure theme is working */
body[data-theme="dark"] * {
    transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
}
</style>

<title><?php $title ?></title>
	
</head>


<body <?php echo applyThemeToHTML(); ?>>

<!-- -------------------------------------- MENU -------------------------------------------- -->
<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>


<!-- ----------------------- MAIN CONTENT --------------------------------------- -->
<div class="container">
	<div class="row m-0 p-0">
		<div class="col-6 p-5">
			<img class="img-fluid pl-5" src="img/2.jpg" alt="project_management">		
		</div>	
		<div class="col-6 p-5 justify-content-center">
			<p class="text-center h1 fw-bold m-5">SIGN UP</p>
			<form class="px-5" name="signup" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
				<div class="mb-4">					
					<div class="input-group">
						<div class="input-group-prepend">
						<span class="input-group-text"> <i class="fas fa-user"></i></span>
						</div>					
						<input class="form-control" type="text" name="user" placeholder="Username" required>
					</div>
				</div>
				<div class="mb-4">					
					<div class="input-group">
						<div class="input-group-prepend">
						<span class="input-group-text"> <i class="fas fa-lock"></i></span>
						</div>					
						<input class="form-control" type="password" name="password" placeholder="Password" required>
					</div>
				</div>
				<div class="mb-4">					
					<div class="input-group">
						<div class="input-group-prepend">
						<span class="input-group-text"> <i class="fas fa-key"></i></span>
						</div>					
						<input class="form-control" type="password" name="password2" placeholder="Confirm password" required>
					</div>
				</div>

				<div class="d-flex justify-content-center mx-4 mb-3 mb-lg-4">
				<button type="button" class="btn btn-primary" onclick="signup.submit()">Register</button>
				</div>

				<?php if(!empty($errors)): ?>
					<div class="err">
						<ul>
							<?php echo $errors; ?>
						</ul>
					</div>
				<?php endif; ?>
			</form>
			<span class="d-flex justify-content-center">Already have an account?<a class="nav-link text-primary m-0 p-0 pl-2" href="login.php">Log in</a></span>			
		</div>

	</div>
</div>

<!-- -------------------------- FOOTER --------------------------- -->
<?php require 'footer.php'; ?>
  
<!-- --------------------- JS SCRIPTS JQUERY + POPPER + BOOTSTRAP ------------------------- -->
<script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>

<!-- Theme Switcher Script -->
<script src="js/theme-switcher.js"></script>

</body>
</html>
