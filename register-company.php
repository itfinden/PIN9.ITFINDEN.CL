<?php session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/lang/language_handler.php';

if (isset($_SESSION['user'])) {
	header('Location: content.php');
	die();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
	
	// Validation
	$company_name = filter_var(htmlspecialchars($_POST['company_name']), FILTER_SANITIZE_STRING);
	$company_email = filter_var(htmlspecialchars($_POST['company_email']), FILTER_SANITIZE_EMAIL);
	$company_phone = filter_var(htmlspecialchars($_POST['company_phone']), FILTER_SANITIZE_STRING);
	$company_address = filter_var(htmlspecialchars($_POST['company_address']), FILTER_SANITIZE_STRING);
	$company_website = filter_var(htmlspecialchars($_POST['company_website']), FILTER_SANITIZE_URL);
	$company_tax_id = filter_var(htmlspecialchars($_POST['company_tax_id']), FILTER_SANITIZE_STRING);
	
	// Admin user data
	$admin_first_name = filter_var(htmlspecialchars($_POST['admin_first_name']), FILTER_SANITIZE_STRING);
	$admin_last_name = filter_var(htmlspecialchars($_POST['admin_last_name']), FILTER_SANITIZE_STRING);
	$admin_email = filter_var(htmlspecialchars($_POST['admin_email']), FILTER_SANITIZE_EMAIL);
	$admin_username = filter_var(htmlspecialchars($_POST['admin_username']), FILTER_SANITIZE_STRING);
	$admin_password = filter_var(htmlspecialchars($_POST['admin_password']), FILTER_SANITIZE_STRING);
	$admin_password2 = filter_var(htmlspecialchars($_POST['admin_password2']), FILTER_SANITIZE_STRING);
	
	$errors = '';

	// Checking for empty spaces
	if (empty($company_name) || empty($company_email) || empty($admin_first_name) || 
		empty($admin_last_name) || empty($admin_email) || empty($admin_username) || 
		empty($admin_password) || empty($admin_password2)) {
		$errors = '<li>Please fill in all the required fields.</li>';
	} else {

		include 'db/functions.php';
		$database = new Database();
		$connection = $database->connection();

		// Check if company email already exists
		$statement = $connection->prepare('SELECT * FROM companies WHERE company_email = :email LIMIT 1');
		$statement->execute(array(':email' => $company_email));	
		$result = $statement->fetch();
	
		if ($result != false) {
			$errors .= '<li>Sorry, a company with this email already exists.</li>';
		}

		// Check if admin username already exists
		$statement = $connection->prepare('SELECT * FROM users WHERE user = :user LIMIT 1');
		$statement->execute(array(':user' => $admin_username));	
		$result = $statement->fetch();
	
		if ($result != false) {
			$errors .= '<li>Sorry, the admin username already exists.</li>';
		}

		// Check if admin email already exists
		$statement = $connection->prepare('SELECT * FROM user_profiles WHERE email = :email LIMIT 1');
		$statement->execute(array(':email' => $admin_email));	
		$result = $statement->fetch();
	
		if ($result != false) {
			$errors .= '<li>Sorry, the admin email already exists.</li>';
		}

		// Hashing the password
		$admin_password = hash('sha512', $admin_password);
		$admin_password2 = hash('sha512', $admin_password2);

		// Checking if both passwords match
		if ($admin_password != $admin_password2) {
			$errors .= '<li>The password confirmation does not match.</li>';
		}
	}

	// If no errors, create company and admin user
	if ($errors == '') {
		try {
			$connection->beginTransaction();
			
			// Create company
			$statement = $connection->prepare('INSERT INTO companies (company_name, company_email, company_phone, company_address, company_website, company_tax_id) VALUES (:name, :email, :phone, :address, :website, :tax_id)');
			$statement->execute(array(
				':name' => $company_name,
				':email' => $company_email,
				':phone' => $company_phone,
				':address' => $company_address,
				':website' => $company_website,
				':tax_id' => $company_tax_id
			));
			
			$company_id = $connection->lastInsertId();
			
			// Create admin user
			$statement = $connection->prepare('INSERT INTO users (id_user, user, password) VALUES (null, :user, :password)');
			$statement->execute(array(
				':user' => $admin_username,
				':password' => $admin_password
			));
			
			$user_id = $connection->lastInsertId();
			
			// Create user profile
			$statement = $connection->prepare('INSERT INTO user_profiles (id_user, first_name, last_name, email) VALUES (:user_id, :first_name, :last_name, :email)');
			$statement->execute(array(
				':user_id' => $user_id,
				':first_name' => $admin_first_name,
				':last_name' => $admin_last_name,
				':email' => $admin_email
			));
			
			// Link user to company as admin
			$statement = $connection->prepare('INSERT INTO company_users (id_company, id_user, role, status, accepted_at) VALUES (:company_id, :user_id, "admin", "active", NOW())');
			$statement->execute(array(
				':company_id' => $company_id,
				':user_id' => $user_id
			));
			
			$connection->commit();
			audit_log('Crear empresa', 'Empresa: ' . $company_name . ', Admin: ' . $admin_username);
			
			// Set session variables
			$_SESSION['user'] = $admin_username;
			$_SESSION['id_user'] = $user_id;
			$_SESSION['id_company'] = $company_id;
			$_SESSION['user_role'] = 'admin';
			$_SESSION['company_name'] = $company_name;
			
			header('Location: content.php');
			exit();
			
		} catch (Exception $e) {
			$connection->rollback();
			$errors = '<li>An error occurred while creating the account. Please try again.</li>';
		}
	}
}

require 'views/register-company.view.php';
?> 