<?php
// Solo iniciar sesión si no se ha iniciado ya
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para obtener el tema actual
function getCurrentTheme() {
    // Primero verificar si hay un tema guardado en sesión
    if (isset($_SESSION['theme'])) {
        return $_SESSION['theme'];
    }
    
    // Si no hay tema en sesión, usar el tema del sistema o 'light' por defecto
    return 'light';
}

// Función para establecer el tema
function setTheme($theme) {
    if (in_array($theme, ['light', 'dark'])) {
        $_SESSION['theme'] = $theme;
        return true;
    }
    return false;
}

// Manejar solicitudes AJAX para cambiar tema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['set_theme', 'get_theme'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'set_theme':
            if (isset($_POST['theme'])) {
                $success = setTheme($_POST['theme']);
                echo json_encode(['success' => $success, 'theme' => $_POST['theme']]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No theme provided']);
            }
            break;
            
        case 'get_theme':
            echo json_encode(['theme' => getCurrentTheme()]);
            break;
    }
    exit;
}

// Función para aplicar el tema al HTML
function applyThemeToHTML() {
    $theme = getCurrentTheme();
    return 'data-theme="' . $theme . '"';
}

// Función para obtener clases CSS basadas en el tema
function getThemeClasses() {
    $theme = getCurrentTheme();
    $classes = ['theme-' . $theme];
    
    // Agregar clases adicionales según el tema
    if ($theme === 'dark') {
        $classes[] = 'dark-mode';
    } else {
        $classes[] = 'light-mode';
    }
    
    return implode(' ', $classes);
}
?>