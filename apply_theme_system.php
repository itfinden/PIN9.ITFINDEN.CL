<?php
/**
 * Script para aplicar el sistema de temas a las páginas principales
 * Ejecutar desde la línea de comandos: php apply_theme_system.php
 */

echo "=== APLICANDO SISTEMA DE TEMAS A PÁGINAS PRINCIPALES ===\n\n";

// Lista de archivos principales que necesitan el sistema de temas
$main_files = [
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

$updated_count = 0;

foreach ($main_files as $file) {
    if (file_exists($file)) {
        echo "Procesando: $file\n";
        
        $content = file_get_contents($file);
        $original_content = $content;
        
        // 1. Agregar require_once para theme_handler.php después de session_start
        if (strpos($content, 'session_start()') !== false && strpos($content, 'theme_handler.php') === false) {
            $content = preg_replace(
                '/(session_start\(\);)/',
                '$1' . "\nrequire_once 'theme_handler.php';",
                $content
            );
        }
        
        // 2. Buscar y actualizar el tag HTML para agregar data-theme
        if (preg_match('/<html[^>]*>/', $content, $matches)) {
            $html_tag = $matches[0];
            if (strpos($html_tag, 'data-theme') === false) {
                // Agregar require_once temporal para obtener la función
                $temp_content = "<?php require_once 'theme_handler.php'; ?>\n" . $content;
                $temp_file = 'temp_' . uniqid() . '.php';
                file_put_contents($temp_file, $temp_content);
                
                // Incluir temporalmente para obtener la función
                ob_start();
                include $temp_file;
                ob_end_clean();
                unlink($temp_file);
                
                // Ahora podemos usar la función
                $new_html_tag = str_replace('<html', '<html ' . applyThemeToHTML(), $html_tag);
                $content = str_replace($html_tag, $new_html_tag, $content);
            }
        }
        
        // 3. Agregar el script del theme switcher en el head
        if (strpos($content, '<head>') !== false && strpos($content, 'theme-switcher.js') === false) {
            $content = preg_replace(
                '/(<head>)/',
                '$1' . "\n    <script src=\"js/theme-switcher.js\"></script>",
                $content
            );
        }
        
        // Guardar solo si hubo cambios
        if ($content !== $original_content) {
            if (file_put_contents($file, $content)) {
                echo "  ✅ Actualizado\n";
                $updated_count++;
            } else {
                echo "  ❌ Error al guardar\n";
            }
        } else {
            echo "  ⚠️  Ya tiene el sistema de temas\n";
        }
        
    } else {
        echo "  ⚠️  $file - NO ENCONTRADO\n";
    }
}

echo "\n=== ACTUALIZANDO ARCHIVOS DE VISTA ===\n";

// Archivos de vista
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
        echo "Procesando: $file\n";
        
        $content = file_get_contents($file);
        $original_content = $content;
        
        // 1. Agregar require_once para theme_handler.php
        if (strpos($content, '<?php') !== false && strpos($content, 'theme_handler.php') === false) {
            $content = preg_replace(
                '/(<\?php)/',
                '$1' . "\nrequire_once __DIR__ . '/../theme_handler.php';",
                $content
            );
        }
        
        // 2. Buscar y actualizar el tag HTML
        if (preg_match('/<html[^>]*>/', $content, $matches)) {
            $html_tag = $matches[0];
            if (strpos($html_tag, 'data-theme') === false) {
                // Crear contenido temporal para obtener la función
                $temp_content = "<?php require_once __DIR__ . '/../theme_handler.php'; ?>\n" . $content;
                $temp_file = 'temp_' . uniqid() . '.php';
                file_put_contents($temp_file, $temp_content);
                
                // Incluir temporalmente
                ob_start();
                include $temp_file;
                ob_end_clean();
                unlink($temp_file);
                
                $new_html_tag = str_replace('<html', '<html ' . applyThemeToHTML(), $html_tag);
                $content = str_replace($html_tag, $new_html_tag, $content);
            }
        }
        
        // 3. Agregar el script del theme switcher
        if (strpos($content, '<head>') !== false && strpos($content, 'theme-switcher.js') === false) {
            $content = preg_replace(
                '/(<head>)/',
                '$1' . "\n    <script src=\"js/theme-switcher.js\"></script>",
                $content
            );
        }
        
        // Guardar solo si hubo cambios
        if ($content !== $original_content) {
            if (file_put_contents($file, $content)) {
                echo "  ✅ Actualizado\n";
                $updated_count++;
            } else {
                echo "  ❌ Error al guardar\n";
            }
        } else {
            echo "  ⚠️  Ya tiene el sistema de temas\n";
        }
        
    } else {
        echo "  ⚠️  $file - NO ENCONTRADO\n";
    }
}

echo "\n=== ACTUALIZANDO ARCHIVOS DE ADMINISTRACIÓN ===\n";

// Archivos de administración
$admin_files = glob('admin/*.php');

foreach ($admin_files as $file) {
    echo "Procesando: $file\n";
    
    $content = file_get_contents($file);
    $original_content = $content;
    
    // 1. Agregar require_once para theme_handler.php
    if (strpos($content, 'session_start()') !== false && strpos($content, 'theme_handler.php') === false) {
        $content = preg_replace(
            '/(session_start\(\);)/',
            '$1' . "\nrequire_once '../theme_handler.php';",
            $content
        );
    }
    
    // 2. Buscar y actualizar el tag HTML
    if (preg_match('/<html[^>]*>/', $content, $matches)) {
        $html_tag = $matches[0];
        if (strpos($html_tag, 'data-theme') === false) {
            // Crear contenido temporal
            $temp_content = "<?php require_once '../theme_handler.php'; ?>\n" . $content;
            $temp_file = 'temp_' . uniqid() . '.php';
            file_put_contents($temp_file, $temp_content);
            
            // Incluir temporalmente
            ob_start();
            include $temp_file;
            ob_end_clean();
            unlink($temp_file);
            
            $new_html_tag = str_replace('<html', '<html ' . applyThemeToHTML(), $html_tag);
            $content = str_replace($html_tag, $new_html_tag, $content);
        }
    }
    
    // 3. Agregar el script del theme switcher
    if (strpos($content, '<head>') !== false && strpos($content, 'theme-switcher.js') === false) {
        $content = preg_replace(
            '/(<head>)/',
            '$1' . "\n    <script src=\"../js/theme-switcher.js\"></script>",
            $content
        );
    }
    
    // Guardar solo si hubo cambios
    if ($content !== $original_content) {
        if (file_put_contents($file, $content)) {
            echo "  ✅ Actualizado\n";
            $updated_count++;
        } else {
            echo "  ❌ Error al guardar\n";
        }
    } else {
        echo "  ⚠️  Ya tiene el sistema de temas\n";
    }
}

echo "\n=== RESUMEN ===\n";
echo "Archivos actualizados: $updated_count\n";
echo "Sistema de temas aplicado correctamente.\n\n";

echo "=== VERIFICACIÓN ===\n";
echo "Para verificar que todo funciona:\n";
echo "1. Visita: https://pin9.itfinden.cl/test_theme_system.php\n";
echo "2. Visita: https://pin9.itfinden.cl/content.php\n";
echo "3. Visita: https://pin9.itfinden.cl/services.php\n";
echo "4. Usa el selector de tema en la barra de navegación\n\n";

echo "¡Sistema de temas aplicado exitosamente!\n";
?> 