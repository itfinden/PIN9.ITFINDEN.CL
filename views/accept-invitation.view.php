<!DOCTYPE html>
<html lang="en">
<?php
require_once __DIR__ . '/../lang/Languaje.php';
$lang = Language::autoDetect();
?>
<head>
	<?php $title= "Accept Invitation - Pin9"; ?>
	
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
<title><?php echo $title; ?></title>
	
</head>

<body>

<!-- -------------------------------------- MENU -------------------------------------------- -->
<?php require_once __DIR__ . '/partials/modern_navbar.php'; ?>

<!-- ----------------------- MAIN CONTENT --------------------------------------- -->
<div class="container">
	<div class="row m-0 p-0">
		<div class="col-6 p-5">
			<img class="img-fluid pl-5" src="img/2.jpg" alt="project_management">		
		</div>	
		<div class="col-6 p-5 justify-content-center">
			
			<?php if ($errors && !is_array($errors)): ?>
				<!-- Invalid Invitation -->
				<div class="text-center">
					<i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
					<h2 class="mt-3">Invalid Invitation</h2>
					<p class="text-muted"><?php echo $errors; ?></p>
					<a href="main.php" class="btn btn-primary">Go to Home</a>
				</div>
			<?php else: ?>
				<!-- Accept Invitation Form -->
				<div class="text-center mb-4">
					<i class="fas fa-user-plus text-primary" style="font-size: 3rem;"></i>
					<h2 class="mt-3">You're Invited!</h2>
					<p class="text-muted">Complete your registration to join <strong><?php echo htmlspecialchars($company['name']); ?></strong></p>
				</div>
				
				<form class="px-5" name="accept_invitation" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST">
					
					<!-- Personal Information -->
					<h5 class="mb-3 text-primary"><i class="fas fa-user"></i> Personal Information</h5>
					
					<div class="row">
						<div class="col-md-6 mb-3">					
							<div class="input-group">
								<div class="input-group-prepend">
								<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-user"></i></small></span>
								</div>					
								<input class="form-control" type="text" name="first_name" placeholder="First Name *" required>
							</div>
						</div>
						<div class="col-md-6 mb-3">					
							<div class="input-group">
								<div class="input-group-prepend">
								<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-user"></i></small></span>
								</div>					
								<input class="form-control" type="text" name="last_name" placeholder="Last Name *" required>
							</div>
						</div>
					</div>
					
					<div class="mb-3">					
						<div class="input-group">
							<div class="input-group-prepend">
							<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-envelope"></i></small></span>
							</div>					
							<input class="form-control" type="email" value="<?php echo htmlspecialchars($invitation['email']); ?>" readonly>
						</div>
						<small class="text-muted">This is the email address you were invited with</small>
					</div>
					
					<div class="mb-3">					
						<div class="input-group">
							<div class="input-group-prepend">
							<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-phone"></i></small></span>
							</div>					
							<input class="form-control" type="tel" name="phone" placeholder="Phone Number">
						</div>
					</div>
					
					<div class="row">
						<div class="col-md-6 mb-3">					
							<div class="input-group">
								<div class="input-group-prepend">
								<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-briefcase"></i></small></span>
								</div>					
								<input class="form-control" type="text" name="position" placeholder="Position">
							</div>
						</div>
						<div class="col-md-6 mb-3">					
							<div class="input-group">
								<div class="input-group-prepend">
								<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-building"></i></small></span>
								</div>					
								<input class="form-control" type="text" name="department" placeholder="Department">
							</div>
						</div>
					</div>
					
					<!-- Account Information -->
					<h5 class="mb-3 text-primary"><i class="fas fa-shield-alt"></i> Account Information</h5>
					
					<div class="mb-3">					
						<div class="input-group">
							<div class="input-group-prepend">
							<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-user"></i></small></span>
							</div>					
							<input class="form-control" type="text" name="username" placeholder="Username *" required>
						</div>
					</div>
					
					<div class="mb-3">					
						<div class="input-group">
							<div class="input-group-prepend">
							<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-lock"></i></small></span>
							</div>					
							<input class="form-control" type="password" name="password" placeholder="Password *" required>
						</div>
					</div>
					
					<div class="mb-4">					
						<div class="input-group">
							<div class="input-group-prepend">
							<span class="btn-sign-up text-light input-group-text"> <small><i class="fas fa-key"></i></small></span>
							</div>					
							<input class="form-control" type="password" name="password2" placeholder="Confirm Password *" required>
						</div>
					</div>

					<div class="d-flex justify-content-center mx-4 mb-3 mb-lg-4">
					<button type="button" class="btn btn-success btn-lg" onclick="accept_invitation.submit()">
						<i class="fas fa-check"></i> Accept Invitation & Join Company
					</button>
					</div>

					<?php if(!empty($errors) && is_array($errors)): ?>
						<div class="alert alert-danger">
							<ul class="mb-0">
								<?php echo $errors; ?>
							</ul>
						</div>
					<?php endif; ?>
				</form>
				
				<div class="text-center mt-3">
					<span>Already have an account? <a class="nav-link text-primary d-inline" href="login.php">Log in</a></span>
				</div>
			<?php endif; ?>
		</div>
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