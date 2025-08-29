<?php
// ==================== CONFIGURACIÓN DEL SISTEMA DE TEMAS ====================

// Configuración de temas disponibles
$THEME_CONFIG = [
    'light' => [
        'name' => 'Tema Claro',
        'icon' => 'fas fa-sun',
        'description' => 'Tema claro para uso diurno',
        'default' => true
    ],
    'dark' => [
        'name' => 'Tema Oscuro',
        'icon' => 'fas fa-moon',
        'description' => 'Tema oscuro para uso nocturno',
        'default' => false
    ]
];

// Configuración de colores por tema
$THEME_COLORS = [
    'light' => [
        'primary' => '#007bff',
        'secondary' => '#6c757d',
        'success' => '#28a745',
        'danger' => '#dc3545',
        'warning' => '#ffc107',
        'info' => '#17a2b8',
        'light' => '#f8f9fa',
        'dark' => '#343a40',
        'bg_primary' => '#ffffff',
        'bg_secondary' => '#f8f9fa',
        'bg_card' => '#ffffff',
        'text_primary' => '#212529',
        'text_secondary' => '#6c757d',
        'border' => '#dee2e6'
    ],
    'dark' => [
        'primary' => '#4dabf7',
        'secondary' => '#868e96',
        'success' => '#51cf66',
        'danger' => '#ff6b6b',
        'warning' => '#ffd43b',
        'info' => '#74c0fc',
        'light' => '#495057',
        'dark' => '#f8f9fa',
        'bg_primary' => '#1a1a1a',
        'bg_secondary' => '#2d2d2d',
        'bg_card' => '#2d2d2d',
        'text_primary' => '#ffffff',
        'text_secondary' => '#ced4da',
        'border' => '#495057'
    ]
];

// Función para obtener la configuración de un tema
function getThemeConfig($theme = null) {
    global $THEME_CONFIG;
    
    if ($theme === null) {
        return $THEME_CONFIG;
    }
    
    return isset($THEME_CONFIG[$theme]) ? $THEME_CONFIG[$theme] : null;
}

// Función para obtener los colores de un tema
function getThemeColors($theme) {
    global $THEME_COLORS;
    
    return isset($THEME_COLORS[$theme]) ? $THEME_COLORS[$theme] : $THEME_COLORS['light'];
}

// Función para generar CSS variables dinámicamente
function generateThemeCSS($theme) {
    $colors = getThemeColors($theme);
    
    $css = ":root {\n";
    foreach ($colors as $key => $value) {
        $css .= "    --{$key}: {$value};\n";
    }
    $css .= "}\n";
    
    return $css;
}

// Función para obtener el tema por defecto
function getDefaultTheme() {
    global $THEME_CONFIG;
    
    foreach ($THEME_CONFIG as $theme => $config) {
        if ($config['default']) {
            return $theme;
        }
    }
    
    return 'light';
}

// Función para validar si un tema es válido
function isValidTheme($theme) {
    global $THEME_CONFIG;
    
    return isset($THEME_CONFIG[$theme]);
}

// Función para obtener todos los temas disponibles
function getAvailableThemes() {
    global $THEME_CONFIG;
    
    return array_keys($THEME_CONFIG);
}

// Función para obtener información del tema actual
function getCurrentThemeInfo() {
    $currentTheme = getCurrentTheme();
    $config = getThemeConfig($currentTheme);
    $colors = getThemeColors($currentTheme);
    
    return [
        'theme' => $currentTheme,
        'config' => $config,
        'colors' => $colors
    ];
}
?> 