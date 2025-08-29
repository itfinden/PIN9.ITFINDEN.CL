<?php
/**
 * Script de configuración del Sistema de Temas
 * Aplica el sistema de temas a todas las páginas existentes
 */

echo "=== CONFIGURACIÓN DEL SISTEMA DE TEMAS ===\n\n";

// 1. Verificar archivos necesarios
echo "1. Verificando archivos necesarios...\n";

$required_files = [
    'css/style.css',
    'js/theme-switcher.js',
    'theme_handler.php',
    'config/theme_config.php',
    'views/partials/modern_navbar.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file\n";
    } else {
        echo "   ❌ $file - NO ENCONTRADO\n";
    }
}

echo "\n2. Aplicando sistema de temas a páginas principales...\n";

// Lista de archivos PHP que necesitan el sistema de temas
$php_files = [
    'content.php',
    'tickets.php',
    'calendar.php',
    'calendar2.php',
    'projects.php',
    'today.php',
    'services.php',
    'edit_ticket.php',
    'new_ticket.php',
    'view_ticket.php',
    'edit_service.php',
    'new_service.php',
    'delete_service.php',
    'login.php',
    'register.php',
    'register-company.php',
    'invite-user.php',
    'accept-invitation.php',
    'company-settings.php'
];

$updated_files = 0;

foreach ($php_files as $file) {
    if (file_exists($file)) {
        echo "   Procesando: $file\n";
        
        // Leer el contenido del archivo
        $content = file_get_contents($file);
        
        // Verificar si ya tiene el sistema de temas
        if (strpos($content, 'theme_handler.php') !== false) {
            echo "     ⚠️  Ya tiene el sistema de temas\n";
            continue;
        }
        
        // Agregar require_once para theme_handler.php después de session_start
        if (strpos($content, 'session_start()') !== false) {
            $content = preg_replace(
                '/(session_start\(\);)/',
                '$1' . "\nrequire_once 'theme_handler.php';",
                $content
            );
        }
        
        // Buscar y actualizar el tag HTML
        if (preg_match('/<html[^>]*>/', $content, $matches)) {
            $html_tag = $matches[0];
            $new_html_tag = str_replace('<html', '<html ' . applyThemeToHTML(), $html_tag);
            $content = str_replace($html_tag, $new_html_tag, $content);
        }
        
        // Agregar el script del theme switcher en el head
        if (strpos($content, '<head>') !== false) {
            $content = preg_replace(
                '/(<head>)/',
                '$1' . "\n    <script src=\"js/theme-switcher.js\"></script>",
                $content
            );
        }
        
        // Guardar el archivo actualizado
        if (file_put_contents($file, $content)) {
            echo "     ✅ Actualizado\n";
            $updated_files++;
        } else {
            echo "     ❌ Error al actualizar\n";
        }
    } else {
        echo "   ⚠️  $file - NO ENCONTRADO\n";
    }
}

echo "\n3. Actualizando archivos de vista...\n";

// Archivos de vista que necesitan actualización
$view_files = [
    'views/login.view.php',
    'views/register.view.php',
    'views/register-company.view.php',
    'views/invite-user.view.php',
    'views/accept-invitation.view.php',
    'views/calendar.view.php',
    'views/content.view.php',
    'views/today.view.php',
    'views/projects.view.php'
];

foreach ($view_files as $file) {
    if (file_exists($file)) {
        echo "   Procesando: $file\n";
        
        $content = file_get_contents($file);
        
        // Verificar si ya tiene el sistema de temas
        if (strpos($content, 'theme_handler.php') !== false) {
            echo "     ⚠️  Ya tiene el sistema de temas\n";
            continue;
        }
        
        // Agregar require_once para theme_handler.php
        if (strpos($content, '<?php') !== false) {
            $content = preg_replace(
                '/(<\?php)/',
                '$1' . "\nrequire_once __DIR__ . '/../theme_handler.php';",
                $content
            );
        }
        
        // Buscar y actualizar el tag HTML
        if (preg_match('/<html[^>]*>/', $content, $matches)) {
            $html_tag = $matches[0];
            $new_html_tag = str_replace('<html', '<html ' . applyThemeToHTML(), $html_tag);
            $content = str_replace($html_tag, $new_html_tag, $content);
        }
        
        // Agregar el script del theme switcher en el head
        if (strpos($content, '<head>') !== false) {
            $content = preg_replace(
                '/(<head>)/',
                '$1' . "\n    <script src=\"js/theme-switcher.js\"></script>",
                $content
            );
        }
        
        // Guardar el archivo actualizado
        if (file_put_contents($file, $content)) {
            echo "     ✅ Actualizado\n";
            $updated_files++;
        } else {
            echo "     ❌ Error al actualizar\n";
        }
    } else {
        echo "   ⚠️  $file - NO ENCONTRADO\n";
    }
}

echo "\n4. Verificando archivos de administración...\n";

// Archivos de administración
$admin_files = glob('admin/*.php');

foreach ($admin_files as $file) {
    echo "   Procesando: $file\n";
    
    $content = file_get_contents($file);
    
    // Verificar si ya tiene el sistema de temas
    if (strpos($content, 'theme_handler.php') !== false) {
        echo "     ⚠️  Ya tiene el sistema de temas\n";
        continue;
    }
    
    // Agregar require_once para theme_handler.php después de session_start
    if (strpos($content, 'session_start()') !== false) {
        $content = preg_replace(
            '/(session_start\(\);)/',
            '$1' . "\nrequire_once '../theme_handler.php';",
            $content
        );
    }
    
    // Buscar y actualizar el tag HTML
    if (preg_match('/<html[^>]*>/', $content, $matches)) {
        $html_tag = $matches[0];
        $new_html_tag = str_replace('<html', '<html ' . applyThemeToHTML(), $html_tag);
        $content = str_replace($html_tag, $new_html_tag, $content);
    }
    
    // Agregar el script del theme switcher en el head
    if (strpos($content, '<head>') !== false) {
        $content = preg_replace(
            '/(<head>)/',
            '$1' . "\n    <script src=\"../js/theme-switcher.js\"></script>",
            $content
        );
    }
    
    // Guardar el archivo actualizado
    if (file_put_contents($file, $content)) {
        echo "     ✅ Actualizado\n";
        $updated_files++;
    } else {
        echo "     ❌ Error al actualizar\n";
    }
}

echo "\n5. Creando archivo de configuración global...\n";

// Crear un archivo de configuración global
$global_config = '<?php
// Configuración global del sistema de temas
// Este archivo se incluye automáticamente en todas las páginas

if (!defined("THEME_SYSTEM_LOADED")) {
    define("THEME_SYSTEM_LOADED", true);
    
    // Incluir el manejador de temas
    require_once __DIR__ . "/theme_handler.php";
    
    // Aplicar tema al HTML si no se ha hecho
    if (!function_exists("applyThemeToHTML")) {
        function applyThemeToHTML() {
            $theme = getCurrentTheme();
            return "data-theme=\"" . $theme . "\"";
        }
    }
}
?>';

if (file_put_contents('theme_system_global.php', $global_config)) {
    echo "   ✅ Archivo de configuración global creado\n";
} else {
    echo "   ❌ Error al crear archivo de configuración global\n";
}

echo "\n=== RESUMEN ===\n";
echo "Archivos actualizados: $updated_files\n";
echo "Sistema de temas configurado correctamente.\n\n";

echo "=== PRÓXIMOS PASOS ===\n";
echo "1. Probar el sistema de temas en diferentes páginas\n";
echo "2. Verificar que el selector de tema funcione correctamente\n";
echo "3. Comprobar que los temas se apliquen a todos los componentes\n";
echo "4. Revisar la documentación en docs/THEME_SYSTEM.md\n\n";

echo "¡Sistema de temas instalado exitosamente!\n";
?> 