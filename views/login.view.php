
<!DOCTYPE html>
<html lang="en" <?php 
require_once __DIR__ . '/../theme_handler.php';
echo applyThemeToHTML();
?>>
<?php
require_once __DIR__ . '/../lang/JsonLanguage.php';

// Establecer el idioma correcto
if (isset($_SESSION['lang'])) {
    $lang = Language::getInstance();
    $lang->setLanguage($_SESSION['lang']);
} else {
    $lang = JsonLanguage::autoDetect();
}

$current_lang = $_SESSION['lang'] ?? $lang->language ?? 'es';
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

<!-- Theme Switcher JS se cargará después de jQuery -->

<style>
/* Estilos específicos para login con tema */
.login-container {
    min-height: 100vh;
   // background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px 0;
}

.login-form-container {
    background: var(--bg-card);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 15px 35px var(--shadow-light);
    border: 1px solid var(--border-color);
    transition: all var(--transition-speed) var(--transition-ease);
    width: 100%;
    max-width: 500px;
}

.login-title {
    font-size: 2.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 30px;
    text-align: center;
    color: var(--text-primary);
}

.form-control {
    border-radius: 10px;
    border: 2px solid var(--border-color);
    padding: 12px 15px;
    transition: all var(--transition-speed) var(--transition-ease);
    background-color: var(--bg-card);
    color: var(--text-primary);
    font-size: 1rem;
}

.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    background-color: var(--bg-card);
    color: var(--text-primary);
    transform: translateY(-1px);
}

.form-control::placeholder {
    color: var(--text-muted);
}

.input-group-text {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: var(--text-light);
    border-radius: 10px 0 0 10px;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
    border: none;
    border-radius: 25px;
    padding: 15px 40px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all var(--transition-speed) var(--transition-ease);
    color: var(--text-light);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px var(--shadow-medium);
    background: linear-gradient(135deg, var(--info-color) 0%, var(--primary-color) 100%);
}

.err {
    background: var(--danger-color);
    color: var(--text-light);
    padding: 15px;
    border-radius: 10px;
    margin: 20px 0;
    border: 1px solid var(--border-color);
}

.err ul {
    margin: 0;
    padding-left: 20px;
}

/* Responsive */
@media (max-width: 768px) {
    .login-form-container {
        padding: 30px 20px;
        margin: 20px;
    }
    
    .login-title {
        font-size: 2rem;
    }
}
</style>
	
</head>


<body>
<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>
<div style="height:60px;"></div>

<!-- ----------------------- MAIN CONTENT --------------------------------------- -->
<div class="login-container">
	<div class="login-form-container">
		<h1 class="login-title"><?php echo $lang->get('LOGIN_TITLE'); ?></h1>
		<form name="login" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
				<div class="mb-4">
					<label for="lang" class="font-weight-bold mb-1" style="color: var(--text-primary);"><?php echo $lang->get('LANG'); ?></label>
					<div class="input-group">
						<div class="input-group-prepend">
							<span class="input-group-text" style="background: var(--bg-card); border-color: var(--border-color);">
								<img src="https://flagcdn.com/24x18/es.png" alt="Español" style="width:24px;height:18px;">
							</span>
						</div> 
						<select name="lang" id="lang" class="form-control" style="max-width:140px;display:inline-block; background: var(--bg-card); color: var(--text-primary); border-color: var(--border-color);height: auto;" onchange="changeLanguage(this.value)">
							<option value="es" <?php if($current_lang==='es') echo 'selected'; ?>>Español</option>
							<option value="en" <?php if($current_lang==='en') echo 'selected'; ?>>English</option>
						</select>
						<div class="input-group-append">
							<span class="input-group-text" style="background: var(--bg-card); border-color: var(--border-color);">
								<img src="https://flagcdn.com/24x18/gb.png" alt="English" style="width:24px;height:18px;">
							</span>
						</div>
					</div>
				</div>
				<div class="mb-4">					
					<div class="input-group">
						<div class="input-group-prepend">
						<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-user"></i></small></span>
						</div>											
						<input class="form-control" style="height: auto;" type="text" name="user" placeholder="<?php echo $lang->get('USERNAME'); ?>" required>
					</div>
				</div>
				<div class="mb-4">					
					<div class="input-group">
						<div class="input-group-prepend">
						<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-lock"></i></small></span>
						</div>					
						<input class="form-control" style="height: auto;" type="password" name="password" placeholder="<?php echo $lang->get('PASSWORD'); ?>" required>						
					</div>
				</div>			

				<div class="d-flex justify-content-center mx-4 mb-3 mb-lg-4">
				<button type="submit" class="btn btn-primary"><?php echo $lang->get('LOGIN_BUTTON'); ?></button>
				</div>

				<?php if(!empty($errors)): ?>
					<div class="err">
						<ul>
							<?php echo $errors; ?>
						</ul>
					</div>
				<?php endif; ?>
			</form>
			<div class="text-center mt-4">
				<span><?php echo $lang->get('NO_ACCOUNT'); ?> 
					<a class="text-primary" href="register.php"><?php echo $lang->get('SIGN_UP'); ?></a>
				</span>
			</div>
		</div>
	</div>
</div>

<!-- -------------------------- FOOTER --------------------------- -->
<?php require 'footer.php'; ?>
  
<!-- --------------------- JS SCRIPTS JQUERY + POPPER + BOOTSTRAP ------------------------- -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.min.js"></script>

<!-- Theme Switcher JS -->
<script src="js/theme-switcher.js"></script>

<script>
$(document).ready(function() {
    // Función para cambiar idioma
    window.changeLanguage = function(lang) {
        // Evitar cambios repetidos
        const currentLang = '<?php echo $current_lang; ?>';
        if (lang === currentLang) {
            return;
        }
        
        // Deshabilitar el select temporalmente para evitar múltiples clicks
        $('#lang').prop('disabled', true);
        
        // Cambiar idioma
        window.location.href = '?lang=' + lang;
    };
});
</script>

</body>
</html>