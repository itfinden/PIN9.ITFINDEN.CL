<?php
session_start();
require_once 'db/functions.php';
require_once 'security/check_access.php';

// Verificar permiso para eliminar servicios
verificarPermisoVista($_SESSION['id_user'], 21); // delete_service
$database = new Database();
$connection = $database->connection();
$id_company = $_SESSION['id_company'];
$id_service = $_GET['id_service'] ?? null;
if (!$id_service) die('Servicio no especificado.');
$stmt = $connection->prepare("DELETE FROM services WHERE id_service = ? AND id_company = ?");
$ok = $stmt->execute([$id_service, $id_company]);
if ($ok) {
    audit_log('Eliminar servicio', 'Servicio ID: ' . $id_service);
}
header('Location: services.php');
exit; 