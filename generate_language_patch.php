<?php
/**
 * Script para generar un patch con los cambios de language_handler.php
 * que necesitas aplicar a tus archivos locales
 */

$files_to_patch = [
    'register.php',
    'register-company.php',
    'invite-user.php',
    'services.php',
    'new_service.php',
    'edit_service.php',
    'delete_service.php',
    'tickets.php',
    'new_ticket.php',
    'edit_ticket.php',
    'view_ticket.php',
    'today.php',
    'calendar.php',
    'admin/companies.php',
    'admin/company_users.php',
    'admin/company-settings.php',
    'admin/edit_company.php',
    'admin/edit_user.php',
    'admin/edit_user_roles.php',
    'admin/superadmins.php',
    'admin/services.php',
    'admin/new_service.php',
    'admin/edit_service.php',
    'admin/delete_service.php',
    'admin/audit_logs.php',
    'admin/audit_logs_advanced.php',
    'admin/billing_config.php',
    'admin/billing_reports.php',
    'admin/calendars.php',
    'admin/invoices.php',
    'admin/payment_methods.php',
    'admin/role_permissions.php',
    'admin/subscriptions.php',
    'admin/subscription_plans.php',
    'main.php',
    'calendar2.php',
    'views/invite-user.view.php'
];

echo "🔧 GENERANDO PATCH PARA LANGUAGE_HANDLER.PHP\n";
echo "============================================\n\n";

echo "📋 INSTRUCCIONES PARA APLICAR LOS CAMBIOS:\n";
echo "===========================================\n\n";

echo "1. Para cada archivo, busca la línea que contiene 'session_start();'\n";
echo "2. Después de esa línea, agrega estas 3 líneas:\n";
echo "   \n";
echo "   // Manejar cambio de idioma ANTES de cualquier output\n";
echo "   require_once __DIR__ . '/lang/language_handler.php';\n";
echo "   \n";
echo "3. Para archivos en subdirectorios (como admin/), usa:\n";
echo "   require_once __DIR__ . '/../lang/language_handler.php';\n";
echo "   \n";
echo "4. Para archivos en views/, usa:\n";
echo "   require_once __DIR__ . '/../lang/language_handler.php';\n\n";

echo "📝 CAMBIOS ESPECÍFICOS POR ARCHIVO:\n";
echo "====================================\n\n";

foreach ($files_to_patch as $file) {
    if (!file_exists($file)) {
        echo "❌ ARCHIVO NO ENCONTRADO: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Verificar si ya tiene language_handler.php
    if (strpos($content, 'language_handler.php') !== false) {
        echo "✅ YA TIENE LANGUAGE_HANDLER: $file\n";
        continue;
    }
    
    // Determinar la ruta correcta según el directorio
    $lang_path = '';
    if (strpos($file, 'admin/') === 0) {
        $lang_path = '../lang/language_handler.php';
    } elseif (strpos($file, 'views/') === 0) {
        $lang_path = '../lang/language_handler.php';
    } else {
        $lang_path = 'lang/language_handler.php';
    }
    
    echo "🔧 NECESITA MODIFICACIÓN: $file\n";
    echo "   - Buscar: session_start();\n";
    echo "   - Agregar después: require_once __DIR__ . '/$lang_path';\n\n";
}

echo "\n🎯 ARCHIVOS ESPECIALES QUE NECESITAN session_start():\n";
echo "==================================================\n";
echo "- calendar2.php (agregar session_start() al principio)\n";
echo "- views/invite-user.view.php (agregar session_start() al principio)\n\n";

echo "💡 COMANDOS GIT ÚTILES:\n";
echo "=======================\n";
echo "git status                    # Ver archivos modificados\n";
echo "git diff archivo.php          # Ver diferencias en un archivo\n";
echo "git add archivo.php           # Agregar archivo al staging\n";
echo "git add .                     # Agregar todos los archivos\n";
echo "git commit -m \"mensaje\"      # Hacer commit\n";
echo "git push                      # Subir al servidor\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "==============\n";
echo "1. Haz backup de tus archivos locales antes de modificar\n";
echo "2. Aplica los cambios uno por uno y prueba cada archivo\n";
echo "3. Verifica que no haya errores de sintaxis\n";
echo "4. Sube los cambios al servidor con git push\n\n";

// Generar un archivo de ejemplo con los cambios
echo "📄 EJEMPLO DE CAMBIO EN register.php:\n";
echo "=====================================\n";
echo "ANTES:\n";
echo "<?php\n";
echo "session_start();\n";
echo "// resto del código...\n\n";
echo "DESPUÉS:\n";
echo "<?php\n";
echo "session_start();\n";
echo "// Manejar cambio de idioma ANTES de cualquier output\n";
echo "require_once __DIR__ . '/lang/language_handler.php';\n";
echo "// resto del código...\n\n";
?>
