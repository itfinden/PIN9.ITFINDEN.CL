<?php
// Manejo de idioma para todas las páginas del sistema
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Manejar cambio de idioma
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    if (in_array($lang, ['es', 'en'])) {
        $_SESSION['lang'] = $lang;
        
        // Forzar la escritura de la sesión
        session_write_close();
        
        // Redirigir a la página actual sin parámetros para evitar bucle
        $current_url = $_SERVER['REQUEST_URI'];
        $clean_url = strtok($current_url, '?'); // Remover parámetros de URL
        header('Location: ' . $clean_url);
        exit();
    }
}

// Incluir el sistema de idioma
require_once __DIR__ . '/Languaje.php';

// Establecer el idioma
$lang = Language::autoDetect();
$current_lang = $_SESSION['lang'] ?? $lang->language ?? 'es';


?>