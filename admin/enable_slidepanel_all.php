<?php
session_start();

// Verificar que sea superadmin
if (!isset($_SESSION['id_user']) || $_SESSION['is_superadmin'] != 1) {
    die('Acceso restringido a superadmins');
}

echo "<h2>Habilitando Slidepanel en todos los archivos Admin</h2>";
echo "<hr>";

// Lista de archivos admin que necesitan slidepanel habilitado
$admin_files = [
    'dashboard.php',
    'company_users.php',
    'billing_reports.php',
    'edit_user.php',
    'edit_company.php',
    'subscription_plans.php',
    'calendars.php',
    'audit_logs_advanced.php',
    'company-settings.php',
    'edit_user_roles.php',
    'subscriptions.php',
    'companies.php',
    'audit_logs.php',
    'edit_service.php',
    'services.php',
    'new_service.php',
    'role_permissions.php',
    'superadmins.php',
    'billing_config.php',
    'invoices.php',
    'payment_methods.php'
];

$processed = 0;
$errors = [];

foreach ($admin_files as $file) {
    $file_path = __DIR__ . '/' . $file;
    
    if (!file_exists($file_path)) {
        $errors[] = "❌ Archivo no encontrado: $file";
        continue;
    }
    
    $content = file_get_contents($file_path);
    
    // Verificar si ya tiene enable_slidepanel
    if (strpos($content, '$_SESSION[\'enable_slidepanel\'] = 1;') !== false) {
        echo "✅ $file - Ya tiene slidepanel habilitado<br>";
        continue;
    }
    
    // Buscar la línea session_start() y agregar enable_slidepanel después
    if (strpos($content, 'session_start();') !== false) {
        $new_content = str_replace(
            'session_start();',
            "session_start();\n\n// Habilitar slidepanel para admin\n\$_SESSION['enable_slidepanel'] = 1;",
            $content
        );
        
        if (file_put_contents($file_path, $new_content)) {
            echo "✅ $file - Slidepanel habilitado<br>";
            $processed++;
        } else {
            $errors[] = "❌ Error al escribir: $file";
        }
    } else {
        $errors[] = "❌ No se encontró session_start() en: $file";
    }
}

echo "<hr>";
echo "<h3>Resumen:</h3>";
echo "✅ Archivos procesados: $processed<br>";

if (!empty($errors)) {
    echo "<h4>Errores:</h4>";
    foreach ($errors as $error) {
        echo "$error<br>";
    }
}

echo "<hr>";
echo "<h3>✅ Proceso completado</h3>";
echo "<p>Ahora todos los archivos admin tienen el slidepanel habilitado.</p>";
echo "<p><a href='dashboard.php'>Probar Dashboard Admin</a></p>";

echo "<hr>";
echo "<p><strong>Nota:</strong> Este archivo es solo para configuración. Elimínalo en producción.</p>";
?>
