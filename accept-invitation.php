<?php session_start();

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
	header('Location: main.php');
	die();
}

$token = $_GET['token'];
$errors = '';
$invitation = null;
$company = null;

// Get invitation details
include 'db/functions.php';
$database = new Database();
$connection = $database->connection();

$statement = $connection->prepare('
	SELECT i.*, c.company_name, c.company_email 
	FROM invitations i 
	JOIN companies c ON i.id_company = c.id_company 
	WHERE i.token = :token AND i.status = "pending" AND i.expires_at > NOW()
');
$statement->execute(array(':token' => $token));
$invitation = $statement->fetch();

if (!$invitation) {
	$errors = 'Invalid or expired invitation link.';
} else {
	$company = array(
		'name' => $invitation['company_name'],
		'email' => $invitation['company_email']
	);
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && $invitation) {
	
	// Validation
	$first_name = filter_var(htmlspecialchars($_POST['first_name']), FILTER_SANITIZE_STRING);
	$last_name = filter_var(htmlspecialchars($_POST['last_name']), FILTER_SANITIZE_STRING);
	$username = filter_var(htmlspecialchars($_POST['username']), FILTER_SANITIZE_STRING);
	$password = filter_var(htmlspecialchars($_POST['password']), FILTER_SANITIZE_STRING);
	$password2 = filter_var(htmlspecialchars($_POST['password2']), FILTER_SANITIZE_STRING);
	$phone = filter_var(htmlspecialchars($_POST['phone']), FILTER_SANITIZE_STRING);
	$position = filter_var(htmlspecialchars($_POST['position']), FILTER_SANITIZE_STRING);
	$department = filter_var(htmlspecialchars($_POST['department']), FILTER_SANITIZE_STRING);
	
	$errors = '';

	// Checking for empty spaces
	if (empty($first_name) || empty($last_name) || empty($username) || 
		empty($password) || empty($password2)) {
		$errors = '<li>Please fill in all the required fields.</li>';
	} else {

		// Check if username already exists
		$statement = $connection->prepare('SELECT * FROM users WHERE user = :user LIMIT 1');
		$statement->execute(array(':user' => $username));	
		$result = $statement->fetch();
	
		if ($result != false) {
			$errors .= '<li>Sorry, the username already exists.</li>';
		}

		// Check if email already exists
		$statement = $connection->prepare('SELECT * FROM user_profiles WHERE email = :email LIMIT 1');
		$statement->execute(array(':email' => $invitation['email']));	
		$result = $statement->fetch();
	
		if ($result != false) {
			$errors .= '<li>Sorry, an account with this email already exists.</li>';
		}

		// Hashing the password
		$password = hash('sha512', $password);
		$password2 = hash('sha512', $password2);

		// Checking if both passwords match
		if ($password != $password2) {
			$errors .= '<li>The password confirmation does not match.</li>';
		}
	}

	// If no errors, create user and accept invitation
	if ($errors == '') {
		try {
			$connection->beginTransaction();
			
			// Create user
			$statement = $connection->prepare('INSERT INTO users (id_user, user, password) VALUES (null, :user, :password)');
			$statement->execute(array(
				':user' => $username,
				':password' => $password
			));
			
			$user_id = $connection->lastInsertId();
			
			// Create user profile
			$statement = $connection->prepare('INSERT INTO user_profiles (id_user, first_name, last_name, email, phone, position, department) VALUES (:user_id, :first_name, :last_name, :email, :phone, :position, :department)');
			$statement->execute(array(
				':user_id' => $user_id,
				':first_name' => $first_name,
				':last_name' => $last_name,
				':email' => $invitation['email'],
				':phone' => $phone,
				':position' => $position,
				':department' => $department
			));
			
			// Link user to company
			$statement = $connection->prepare('INSERT INTO company_users (id_company, id_user, role, status, invited_by, accepted_at) VALUES (:company_id, :user_id, :role, "active", :invited_by, NOW())');
			$statement->execute(array(
				':company_id' => $invitation['id_company'],
				':user_id' => $user_id,
				':role' => $invitation['role'],
				':invited_by' => $invitation['invited_by']
			));
			
			// Update invitation status
			$statement = $connection->prepare('UPDATE invitations SET status = "accepted", accepted_at = NOW() WHERE id_invitation = :invitation_id');
			$statement->execute(array(':invitation_id' => $invitation['id_invitation']));
			
			$connection->commit();
			
			// Set session variables
			$_SESSION['user'] = $username;
			$_SESSION['id_user'] = $user_id;
			$_SESSION['id_company'] = $invitation['id_company'];
			$_SESSION['user_role'] = $invitation['role'];
			$_SESSION['company_name'] = $invitation['company_name'];
			
			header('Location: content.php');
			exit();
			
		} catch (Exception $e) {
			$connection->rollback();
			$errors = '<li>An error occurred while creating your account. Please try again.</li>';
		}
	}
}

require 'views/accept-invitation.view.php';
?> 