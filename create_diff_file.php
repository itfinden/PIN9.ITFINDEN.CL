<?php
/**
 * Script para generar un archivo de diferencias como referencia
 */

$diff_content = "=== DIFERENCIAS PARA APLICAR EN ARCHIVOS LOCALES ===\n\n";

$files_to_modify = [
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

foreach ($files_to_modify as $file) {
    $diff_content .= "--- $file ---\n";
    $diff_content .= "BUSCAR:\n";
    $diff_content .= "<?php\n";
    $diff_content .= "session_start();\n\n";
    $diff_content .= "REEMPLAZAR CON:\n";
    $diff_content .= "<?php\n";
    $diff_content .= "session_start();\n";
    $diff_content .= "// Manejar cambio de idioma ANTES de cualquier output\n";
    $diff_content .= "require_once __DIR__ . '/lang/language_handler.php';\n\n";
    $diff_content .= "---\n\n";
}

// Archivos especiales que necesitan session_start()
$special_files = [
    'main.php' => [
        'buscar' => "<?php \nsession_start();\n\n// Manejar cambio de idioma\nif (isset(\$_GET['lang'])) {\n    \$lang = \$_GET['lang'];\n    if (in_array(\$lang, ['es', 'en'])) {\n        \$_SESSION['lang'] = \$lang;\n    }\n}",
        'reemplazar' => "<?php \nsession_start();\n// Manejar cambio de idioma ANTES de cualquier output\nrequire_once 'lang/language_handler.php';"
    ],
    'calendar2.php' => [
        'buscar' => "<?php \nif (isset(\$_SESSION['user'])) {\n} else {\n    header('Location: main.php');\n    die();\n}?>",
        'reemplazar' => "<?php \nsession_start();\n// Manejar cambio de idioma ANTES de cualquier output\nrequire_once 'lang/language_handler.php';\n\nif (isset(\$_SESSION['user'])) {\n} else {\n    header('Location: main.php');\n    die();\n}\n?>"
    ],
    'views/invite-user.view.php' => [
        'buscar' => "<!DOCTYPE html>\n<html lang=\"en\" <?php echo applyThemeToHTML(); ?>>\n<?php\nrequire_once __DIR__ . '/../lang/Languaje.php';\n\$lang = Language::autoDetect();\n?>",
        'reemplazar' => "<?php\nsession_start();\n// Manejar cambio de idioma ANTES de cualquier output\nrequire_once __DIR__ . '/../lang/language_handler.php';\n?>\n<!DOCTYPE html>\n<html lang=\"<?php echo \$current_lang; ?>\" <?php echo applyThemeToHTML(); ?>>\n<?php\nrequire_once __DIR__ . '/../lang/Languaje.php';\n\$lang = Language::autoDetect();\n?>"
    ]
];

foreach ($special_files as $file => $changes) {
    $diff_content .= "--- $file (ESPECIAL) ---\n";
    $diff_content .= "BUSCAR:\n";
    $diff_content .= $changes['buscar'] . "\n\n";
    $diff_content .= "REEMPLAZAR CON:\n";
    $diff_content .= $changes['reemplazar'] . "\n\n";
    $diff_content .= "---\n\n";
}

$diff_content .= "=== INSTRUCCIONES ===\n\n";
$diff_content .= "1. Haz backup de todos tus archivos locales\n";
$diff_content .= "2. Aplica los cambios uno por uno\n";
$diff_content .= "3. Para archivos en admin/, usa: require_once __DIR__ . '/../lang/language_handler.php';\n";
$diff_content .= "4. Para archivos en views/, usa: require_once __DIR__ . '/../lang/language_handler.php';\n";
$diff_content .= "5. Para archivos en la raíz, usa: require_once __DIR__ . '/lang/language_handler.php';\n\n";
$diff_content .= "=== COMANDOS GIT ===\n\n";
$diff_content .= "git status                    # Ver archivos modificados\n";
$diff_content .= "git diff archivo.php          # Ver diferencias\n";
$diff_content .= "git add archivo.php           # Agregar archivo\n";
$diff_content .= "git add .                     # Agregar todos\n";
$diff_content .= "git commit -m \"Agregado language_handler.php\"\n";
$diff_content .= "git push                      # Subir al servidor\n\n";

// Guardar el archivo de diferencias
file_put_contents('language_handler_changes.txt', $diff_content);

echo "✅ ARCHIVO DE DIFERENCIAS CREADO: language_handler_changes.txt\n";
echo "📋 Este archivo contiene todas las diferencias que necesitas aplicar\n";
echo "🔍 Revisa el archivo para ver exactamente qué cambios hacer\n\n";

echo "📊 RESUMEN:\n";
echo "===========\n";
echo "Archivos normales a modificar: " . count($files_to_modify) . "\n";
echo "Archivos especiales a modificar: " . count($special_files) . "\n";
echo "Total de archivos: " . (count($files_to_modify) + count($special_files)) . "\n\n";

echo "💡 PRÓXIMOS PASOS:\n";
echo "==================\n";
echo "1. Revisa el archivo language_handler_changes.txt\n";
echo "2. Aplica los cambios a tus archivos locales\n";
echo "3. Prueba cada archivo modificado\n";
echo "4. Haz commit y push con Git\n";
?>
