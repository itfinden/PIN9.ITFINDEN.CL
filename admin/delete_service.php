<?php
session_start();

// Manejar cambio de idioma ANTES de cualquier output
require_once __DIR__ . '/../lang/language_handler.php';

// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
    }
}

require_once __DIR__ . '/../db/functions.php';
require_once __DIR__ . '/../lang/Languaje.php';

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user'])) {
    header('Location: ../login.php');
    exit();
}

if (!user_has_permission($_SESSION['id_user'], 'admin_panel')) {
    die('Acceso restringido solo a superadministradores.');
}

require_once __DIR__ . '/../theme_handler.php';
$database = new Database();
$connection = $database->connection();
$id_service = $_GET['id_service'] ?? null;
if (!$id_service) die('Servicio no especificado.');
$stmt = $connection->prepare("DELETE FROM services WHERE id_service = ?");
$ok = $stmt->execute([$id_service]);
if ($ok) {
    audit_log('Eliminar servicio (superadmin)', 'Servicio ID: ' . $id_service);
}
header('Location: services.php');
exit; 