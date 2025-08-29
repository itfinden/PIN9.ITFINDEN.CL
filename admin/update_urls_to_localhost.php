<?php
/**
 * Script para actualizar URLs a localhost
 */

require_once __DIR__ . '/../db/connection.php';

try {
    $pdo = new PDO("mysql:host=$hostname;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Actualizando URLs a localhost...\n\n";
    
    // 1. Actualizar system_modules_simple
    echo "📱 Actualizando módulos del sistema...\n";
    
    $updateModules = [
        'dashboard' => 'http://localhost/content.php',
        'calendar' => 'http://localhost/calendar.php',
        'projects' => 'http://localhost/projects.php',
        'tickets' => 'http://localhost/tickets.php',
        'today' => 'http://localhost/today.php',
        'services' => 'http://localhost/services.php',
        'companies' => 'http://localhost/admin/companies.php',
        'users' => 'http://localhost/admin/company_users.php',
        'billing' => 'http://localhost/admin/billing_config.php',
        'audit' => 'http://localhost/admin/audit_logs.php',
        'settings' => 'http://localhost/admin/company-settings.php'
    ];
    
    $stmt = $pdo->prepare("UPDATE system_modules_simple SET url = ? WHERE module_key = ?");
    
    foreach ($updateModules as $moduleKey => $newUrl) {
        $stmt->execute([$newUrl, $moduleKey]);
        echo "   ✅ $moduleKey -> $newUrl\n";
    }
    
    echo "\n";
    
    // 2. Verificar que se actualizaron correctamente
    echo "🔍 Verificando URLs actualizadas...\n";
    
    $sql = "SELECT module_key, module_name, url FROM system_modules_simple ORDER BY menu_order";
    $stmt = $pdo->query($sql);
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($modules as $module) {
        echo "   - {$module['module_name']}: {$module['url']}\n";
    }
    
    echo "\n🎉 URLs actualizadas exitosamente a localhost!\n";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
