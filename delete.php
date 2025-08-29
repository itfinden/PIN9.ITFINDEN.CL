
<?php session_start();

if (isset($_SESSION['user'])) {
	include 'db/functions.php';
	$database = new Database();
	$connection = $database->connection();

} else {
	header('Location: login.php');
	die();
}

if (isset($_POST['delete'])) {
    // ... código de eliminación ...
    audit_log('Eliminar elemento', 'ID: ' . $_POST['id']);
}

?>