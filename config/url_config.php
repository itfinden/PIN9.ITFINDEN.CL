<?php
/**
 * Configuración de URLs del sistema
 * Evita hardcodear URLs absolutas
 */

// Detectar automáticamente la URL base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Si estamos en la raíz, usar solo el host
    if ($path === '/') {
        return $protocol . '://' . $host;
    }
    
    // Si no, incluir el path hasta la raíz del proyecto
    return $protocol . '://' . $host . $path;
}

// URL base del sistema
define('BASE_URL', getBaseUrl());

// URLs de módulos principales (relativas)
define('MODULE_URLS', [
    'dashboard' => '/content.php',
    'calendar' => '/calendar.php',
    'projects' => '/projects.php',
    'tickets' => '/tickets.php',
    'today' => '/today.php',
    'services' => '/services.php',
    'companies' => '/admin/companies.php',
    'users' => '/admin/company_users.php',
    'billing' => '/admin/billing_config.php',
    'audit' => '/admin/audit_logs.php',
    'settings' => '/admin/company-settings.php'
]);

// Función helper para construir URLs completas
function buildUrl($path) {
    return BASE_URL . $path;
}

// Función helper para obtener URL de módulo
function getModuleUrl($moduleKey) {
    return isset(MODULE_URLS[$moduleKey]) ? buildUrl(MODULE_URLS[$moduleKey]) : '#';
}
?>
