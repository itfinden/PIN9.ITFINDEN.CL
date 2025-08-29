<?php
/**
 * Script para verificar qué archivos fueron modificados por add_language_handler.php
 * y mostrar las diferencias
 */

$files_modified_by_script = [
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
    'admin/subscription_plans.php'
];

$files_modified_manually = [
    'main.php',
    'calendar2.php',
    'views/invite-user.view.php'
];

$all_modified_files = array_merge($files_modified_by_script, $files_modified_manually);

echo "🔍 VERIFICANDO ARCHIVOS MODIFICADOS\n";
echo "=====================================\n\n";

$modified_files = [];
$not_found_files = [];

foreach ($all_modified_files as $file) {
    if (!file_exists($file)) {
        $not_found_files[] = $file;
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Verificar si tiene language_handler.php
    if (strpos($content, 'language_handler.php') !== false) {
        $modified_files[] = $file;
        echo "✅ MODIFICADO: $file\n";
    } else {
        echo "❌ NO MODIFICADO: $file\n";
    }
}

echo "\n📊 RESUMEN:\n";
echo "============\n";
echo "Archivos modificados: " . count($modified_files) . "\n";
echo "Archivos no encontrados: " . count($not_found_files) . "\n";

if (!empty($modified_files)) {
    echo "\n📋 LISTA DE ARCHIVOS MODIFICADOS:\n";
    echo "==================================\n";
    foreach ($modified_files as $file) {
        echo "- $file\n";
    }
}

if (!empty($not_found_files)) {
    echo "\n⚠️  ARCHIVOS NO ENCONTRADOS:\n";
    echo "============================\n";
    foreach ($not_found_files as $file) {
        echo "- $file\n";
    }
}

echo "\n💡 INSTRUCCIONES PARA GIT:\n";
echo "==========================\n";
echo "1. Para ver diferencias en un archivo específico:\n";
echo "   git diff archivo.php\n\n";
echo "2. Para ver todos los archivos modificados:\n";
echo "   git status\n\n";
echo "3. Para agregar archivos modificados:\n";
echo "   git add archivo.php\n\n";
echo "4. Para agregar todos los archivos modificados:\n";
echo "   git add .\n\n";
echo "5. Para hacer commit:\n";
echo "   git commit -m \"Agregado language_handler.php a archivos principales\"\n\n";
echo "6. Para subir al servidor:\n";
echo "   git push\n\n";

// Mostrar ejemplo de diferencias para el primer archivo modificado
if (!empty($modified_files)) {
    $example_file = $modified_files[0];
    echo "\n🔍 EJEMPLO DE DIFERENCIAS EN: $example_file\n";
    echo "============================================\n";
    
    $content = file_get_contents($example_file);
    $lines = explode("\n", $content);
    
    $found_session_start = false;
    $found_language_handler = false;
    
    foreach ($lines as $line_num => $line) {
        if (strpos($line, 'session_start()') !== false) {
            echo "Línea " . ($line_num + 1) . ": " . trim($line) . "\n";
            $found_session_start = true;
        }
        if (strpos($line, 'language_handler.php') !== false) {
            echo "Línea " . ($line_num + 1) . ": " . trim($line) . "\n";
            $found_language_handler = true;
        }
        if ($found_session_start && $found_language_handler) {
            break;
        }
    }
}
?>
