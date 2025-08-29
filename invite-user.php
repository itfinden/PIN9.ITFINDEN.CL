<?php
session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/lang/language_handler.php';

// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit();
}

require_once 'db/functions.php';
require_once 'lang/Languaje.php';

// Verificar permisos de administrador
if (!user_has_permission($_SESSION['id_user'], 'admin_panel')) {
    header('Location: login.php');
    exit();
}

require_once 'theme_handler.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	// Validation
	$email = filter_var(htmlspecialchars($_POST['email']), FILTER_SANITIZE_EMAIL);
	$role = filter_var(htmlspecialchars($_POST['role']), FILTER_SANITIZE_STRING);
	$message = filter_var(htmlspecialchars($_POST['message']), FILTER_SANITIZE_STRING);
	
	$errors = '';
	$success = '';

	// Checking for empty spaces
	if (empty($email)) {
		$errors = '<li>Please enter an email address.</li>';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$errors = '<li>Please enter a valid email address.</li>';
	} else {

		include 'db/functions.php';
		$database = new Database();
		$connection = $database->connection();

		// Check if user already exists in this company
		$statement = $connection->prepare('
			SELECT cu.* FROM company_users cu 
			JOIN user_profiles up ON cu.id_user = up.id_user 
			WHERE cu.id_company = :company_id AND up.email = :email
		');
		$statement->execute(array(
			':company_id' => $_SESSION['id_company'],
			':email' => $email
		));	
		$result = $statement->fetch();
	
		if ($result != false) {
			$errors .= '<li>This user is already a member of your company.</li>';
		}

		// Check if invitation already exists
		$statement = $connection->prepare('
			SELECT * FROM invitations 
			WHERE id_company = :company_id AND email = :email AND status = "pending"
		');
		$statement->execute(array(
			':company_id' => $_SESSION['id_company'],
			':email' => $email
		));	
		$result = $statement->fetch();
	
		if ($result != false) {
			$errors .= '<li>An invitation has already been sent to this email address.</li>';
		}
	}

	// If no errors, create invitation
	if ($errors == '') {
		// Generate unique token
		$token = bin2hex(random_bytes(32));
		$expires_at = date('Y-m-d H:i:s', strtotime('+7 days'));
		
		$statement = $connection->prepare('
			INSERT INTO invitations (id_company, email, invited_by, role, token, expires_at) 
			VALUES (:company_id, :email, :invited_by, :role, :token, :expires_at)
		');
		$statement->execute(array(
			':company_id' => $_SESSION['id_company'],
			':email' => $email,
			':invited_by' => $_SESSION['id_user'],
			':role' => $role,
			':token' => $token,
			':expires_at' => $expires_at
		));
		
		// Send invitation email
		$invitation_link = "https://pin9.itfinden.cl/accept-invitation.php?token=" . $token;
		$company_name = $_SESSION['company_name'];
		
		$to = $email;
		$subject = "You've been invited to join $company_name on Pin9";
		$headers = "From: noreply@pin9.itfinden.cl\r\n";
		$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
		
		$email_body = "
		<html>
		<head>
			<title>Invitation to join $company_name</title>
		</head>
		<body>
			<h2>You've been invited!</h2>
			<p>You have been invited to join <strong>$company_name</strong> on Pin9, our project management platform.</p>
			<p><strong>Role:</strong> " . ucfirst($role) . "</p>
			" . (!empty($message) ? "<p><strong>Message:</strong> $message</p>" : "") . "
			<p>Click the link below to accept the invitation and create your account:</p>
			<p><a href='$invitation_link' style='background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Accept Invitation</a></p>
			<p>Or copy this link: $invitation_link</p>
			<p>This invitation expires in 7 days.</p>
			<br>
			<p>Best regards,<br>The Pin9 Team</p>
		</body>
		</html>
		";
		
		if (mail($to, $subject, $email_body, $headers)) {
			$success = "Invitation sent successfully to $email";
			audit_log('Invitar usuario', 'Empresa ID: ' . $_SESSION['id_company'] . ', Email: ' . $email . ', Rol: ' . $role);
		} else {
			$errors = '<li>Failed to send invitation email. Please try again.</li>';
		}
	}
}

// Get company information
$database = new Database();
$connection = $database->connection();

$statement = $connection->prepare('SELECT * FROM companies WHERE id_company = :company_id');
$statement->execute(array(':company_id' => $_SESSION['id_company']));
$company = $statement->fetch();

// Get pending invitations
$statement = $connection->prepare('
	SELECT i.*, up.first_name, up.last_name 
	FROM invitations i 
	LEFT JOIN users u ON i.invited_by = u.id_user 
	LEFT JOIN user_profiles up ON u.id_user = up.id_user 
	WHERE i.id_company = :company_id 
	ORDER BY i.created_at DESC
');
$statement->execute(array(':company_id' => $_SESSION['id_company']));
$invitations = $statement->fetchAll();

require 'views/invite-user.view.php';
?> 