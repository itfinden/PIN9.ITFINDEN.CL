<?php
/**
 * Script automatizado para migrar del sistema de idiomas PHP a JSON
 * Este script actualiza todos los archivos necesarios automáticamente
 */

echo "🚀 Iniciando migración automática del sistema de idiomas...\n\n";

// Lista de archivos a migrar
$filesToMigrate = [
    'views/calendar.view.php',
    'views/login.view.php',
    'views/main.view.php',
    'views/projects.view.php',
    'views/today.view.php',
    'views/invite-user.view.php',
    'views/register.view.php',
    'views/register-company.view.php',
    'admin/dashboard.php',
    'admin/services.php',
    'admin/companies.php',
    'admin/subscriptions.php',
    'admin/subscription_plans.php',
    'admin/audit_logs.php',
    'admin/billing_config.php',
    'admin/billing_reports.php',
    'admin/calendars.php',
    'admin/company_users.php',
    'admin/company-settings.php',
    'admin/edit_company.php',
    'admin/edit_service.php',
    'admin/edit_user.php',
    'admin/edit_user_roles.php',
    'admin/invoices.php',
    'admin/new_service.php',
    'admin/payment_methods.php',
    'admin/role_permissions.php',
    'admin/superadmins.php',
    'admin/tickets.php',
    'content.php',
    'main.php',
    'login.php',
    'projects.php',
    'tickets.php',
    'today.php',
    'invite-user.php',
    'register.php',
    'register-company.php'
];

$migratedFiles = [];
$skippedFiles = [];

foreach ($filesToMigrate as $file) {
    if (!file_exists($file)) {
        $skippedFiles[] = $file;
        continue;
    }
    
    echo "📝 Procesando: {$file}\n";
    
    // Leer contenido del archivo
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Cambios a realizar
    $changes = [
        // Cambiar includes
        "require_once 'lang/Languaje.php'" => "require_once 'lang/JsonLanguage.php'",
        "require_once '../lang/Languaje.php'" => "require_once '../lang/JsonLanguage.php'",
        "require_once __DIR__ . '/../lang/Languaje.php'" => "require_once __DIR__ . '/../lang/JsonLanguage.php'",
        "require_once __DIR__ . '/../../lang/Languaje.php'" => "require_once __DIR__ . '/../../lang/JsonLanguage.php'",
        
        // Cambiar llamadas a clases
        "Language::autoDetect()" => "JsonLanguage::autoDetect()",
        "Language::getInstance()" => "JsonLanguage::autoDetect()",
        "new Language()" => "new JsonLanguage()",
        
        // Cambiar métodos de idioma
        "\$lang->language" => "\$lang->getLanguage()",
        "\$lang->setLanguage(" => "\$lang->setLanguage(",
        
        // Cambiar claves de idioma (ejemplos comunes)
        "\$lang->get('WELCOME')" => "\$lang->get('general.welcome')",
        "\$lang->get('LOGIN')" => "\$lang->get('auth.login')",
        "\$lang->get('LOGOUT')" => "\$lang->get('auth.logout')",
        "\$lang->get('DASHBOARD')" => "\$lang->get('navigation.dashboard')",
        "\$lang->get('CALENDAR')" => "\$lang->get('navigation.calendar')",
        "\$lang->get('PROJECTS')" => "\$lang->get('navigation.projects')",
        "\$lang->get('TICKETS')" => "\$lang->get('navigation.tickets')",
        "\$lang->get('TODAY')" => "\$lang->get('navigation.today')",
        "\$lang->get('SAVE')" => "\$lang->get('buttons.save')",
        "\$lang->get('CANCEL')" => "\$lang->get('buttons.cancel')",
        "\$lang->get('DELETE')" => "\$lang->get('buttons.delete')",
        "\$lang->get('DESCRIPTION')" => "\$lang->get('services.description')",
        "\$lang->get('PRICE')" => "\$lang->get('services.price')",
        "\$lang->get('STATUS')" => "\$lang->get('services.status')",
        "\$lang->get('NEW_TASK')" => "\$lang->get('projects.new_task')",
        "\$lang->get('TASK')" => "\$lang->get('projects.task')",
        "\$lang->get('TO_DO')" => "\$lang->get('projects.to_do')",
        "\$lang->get('IN_PROGRESS')" => "\$lang->get('projects.in_progress')",
        "\$lang->get('COMPLETE')" => "\$lang->get('projects.complete')",
        "\$lang->get('DATA_UPDATE')" => "\$lang->get('calendar.data_update')",
        "\$lang->get('SAVE_ERROR')" => "\$lang->get('calendar.save_error')",
        "\$lang->get('HAPPENING_TODAY')" => "\$lang->get('calendar.happening_today')",
        "\$lang->get('STARTING_EVENTS')" => "\$lang->get('calendar.starting_events')",
        "\$lang->get('ENDING_EVENTS')" => "\$lang->get('calendar.ending_events')",
        "\$lang->get('NEW_SERVICE')" => "\$lang->get('services.new_service')",
        "\$lang->get('EDIT_SERVICE')" => "\$lang->get('services.edit_service')",
        "\$lang->get('DELETE_SERVICE')" => "\$lang->get('services.delete_service')",
        "\$lang->get('SAVE_SERVICE')" => "\$lang->get('services.save_service')",
        "\$lang->get('MANAGE_SERVICES')" => "\$lang->get('services.manage_services')",
        "\$lang->get('SERVICE_NAME')" => "\$lang->get('services.name')",
        "\$lang->get('SERVICE_TYPE')" => "\$lang->get('services.type')",
        "\$lang->get('SERVICES_TITLE')" => "\$lang->get('services.title')",
        "\$lang->get('DURATION')" => "\$lang->get('services.duration')",
        "\$lang->get('UNIT')" => "\$lang->get('services.unit')",
        "\$lang->get('LOGIN_TITLE')" => "\$lang->get('auth.login_title')",
        "\$lang->get('USERNAME')" => "\$lang->get('auth.username')",
        "\$lang->get('PASSWORD')" => "\$lang->get('auth.password')",
        "\$lang->get('NO_ACCOUNT')" => "\$lang->get('auth.no_account')",
        "\$lang->get('SIGN_UP')" => "\$lang->get('auth.sign_up')",
        "\$lang->get('LOGIN_BUTTON')" => "\$lang->get('auth.login_button')",
        "\$lang->get('PRICING_TITLE')" => "\$lang->get('pricing.title')",
        "\$lang->get('BASIC')" => "\$lang->get('pricing.basic')",
        "\$lang->get('PREMIUM')" => "\$lang->get('pricing.premium')",
        "\$lang->get('ENTERPRISE')" => "\$lang->get('pricing.enterprise')",
        "\$lang->get('BASIC_PRICE')" => "\$lang->get('pricing.basic_price')",
        "\$lang->get('PREMIUM_PRICE')" => "\$lang->get('pricing.premium_price')",
        "\$lang->get('ENTERPRISE_PRICE')" => "\$lang->get('pricing.enterprise_price')",
        "\$lang->get('PER_MONTH')" => "\$lang->get('pricing.per_month')",
        "\$lang->get('START_FREE')" => "\$lang->get('pricing.start_free')",
        "\$lang->get('REQUEST_PREMIUM')" => "\$lang->get('pricing.request_premium')",
        "\$lang->get('CONTACT_SALES')" => "\$lang->get('pricing.contact_sales')",
        "\$lang->get('BASIC_FEATURES')" => "\$lang->get('pricing.basic_features')",
        "\$lang->get('PREMIUM_FEATURES')" => "\$lang->get('pricing.premium_features')",
        "\$lang->get('ENTERPRISE_FEATURES')" => "\$lang->get('pricing.enterprise_features')",
        "\$lang->get('ERRORS_REQUIRED')" => "\$lang->get('errors.required')",
        "\$lang->get('ERRORS_EMAIL')" => "\$lang->get('errors.email')",
        "\$lang->get('PRODUCTS_TITLE')" => "\$lang->get('products.title')",
        "\$lang->get('PRODUCTS_COUNT')" => "\$lang->get('products.count')",
        "\$lang->get('PRODUCTS_EMPTY')" => "\$lang->get('products.empty')",
        "\$lang->get('GREETING')" => "\$lang->get('general.greeting')",
        "\$lang->get('HOME')" => "\$lang->get('general.home')",
        "\$lang->get('LANG')" => "\$lang->get('general.lang')",
        "\$lang->get('SELECT_LANGUAGE')" => "\$lang->get('general.select_language')"
    ];
    
    // Aplicar cambios
    foreach ($changes as $search => $replace) {
        $content = str_replace($search, $replace, $content);
    }
    
    // Si el contenido cambió, guardar el archivo
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        $migratedFiles[] = $file;
        echo "   ✅ Migrado exitosamente\n";
    } else {
        echo "   ⏭️  No requiere cambios\n";
    }
}

echo "\n📊 Resumen de la migración:\n";
echo "   ✅ Archivos migrados: " . count($migratedFiles) . "\n";
echo "   ⏭️  Archivos sin cambios: " . count($skippedFiles) . "\n";
echo "   📝 Total procesados: " . (count($migratedFiles) + count($skippedFiles)) . "\n";

if (!empty($migratedFiles)) {
    echo "\n📋 Archivos migrados:\n";
    foreach ($migratedFiles as $file) {
        echo "   • {$file}\n";
    }
}

if (!empty($skippedFiles)) {
    echo "\n⏭️  Archivos sin cambios:\n";
    foreach ($skippedFiles as $file) {
        echo "   • {$file}\n";
    }
}

echo "\n🎯 Migración automática completada!\n";
echo "   Ahora puedes probar el sistema con los nuevos archivos JSON.\n";
echo "   Recuerda que las claves de idioma ahora usan notación de puntos (ej: 'general.welcome').\n";
?>





