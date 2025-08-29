<?php
/**
 * Script simple para verificar y corregir empresas
 */

require_once 'db/functions.php';

echo "🔧 Verificación Simple de Empresas\n";
echo "=================================\n\n";

try {
    $connection = getConnection();
    echo "✅ Conexión exitosa\n\n";
    
    // 1. Verificar cuántos planes hay
    $stmt = $connection->query("SELECT COUNT(*) FROM subscription_plans");
    $plan_count = $stmt->fetchColumn();
    echo "📊 Planes disponibles: $plan_count\n";
    
    // 2. Verificar cuántas empresas hay
    $stmt = $connection->query("SELECT COUNT(*) FROM companies");
    $company_count = $stmt->fetchColumn();
    echo "📊 Empresas registradas: $company_count\n";
    
    // 3. Verificar cuántas suscripciones hay
    $stmt = $connection->query("SELECT COUNT(*) FROM company_subscriptions");
    $subscription_count = $stmt->fetchColumn();
    echo "📊 Suscripciones existentes: $subscription_count\n";
    
    // 4. Mostrar empresas sin suscripciones
    echo "\n📋 Empresas sin suscripciones:\n";
    $stmt = $connection->query("
        SELECT c.id_company, c.company_name, c.company_email
        FROM companies c
        LEFT JOIN company_subscriptions cs ON c.id_company = cs.id_company
        WHERE cs.id_subscription IS NULL
    ");
    $companies_without = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($companies_without) {
        foreach ($companies_without as $company) {
            echo "  🏢 {$company['company_name']} ({$company['company_email']})\n";
        }
        
        // 5. Si hay empresas sin suscripciones, asignar el primer plan
        if ($plan_count > 0) {
            echo "\n📋 Asignando plan por defecto...\n";
            
            // Obtener el primer plan
            $stmt = $connection->query("SELECT id_plan FROM subscription_plans WHERE is_active = 1 ORDER BY id_plan LIMIT 1");
            $default_plan_id = $stmt->fetchColumn();
            
            if ($default_plan_id) {
                echo "  📊 Plan por defecto ID: $default_plan_id\n";
                
                // Obtener estado activo
                $stmt = $connection->query("SELECT id_status FROM subscription_statuses WHERE status_name = 'active'");
                $active_status_id = $stmt->fetchColumn();
                
                if ($active_status_id) {
                    echo "  📊 Estado activo ID: $active_status_id\n";
                    
                    // Asignar suscripciones
                    $assigned = 0;
                    foreach ($companies_without as $company) {
                        $stmt = $connection->prepare("
                            INSERT INTO company_subscriptions (id_company, id_plan, id_subscription_status, start_date, end_date, auto_renew, current_users)
                            VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 1, 0)
                        ");
                        
                        if ($stmt->execute([$company['id_company'], $default_plan_id, $active_status_id])) {
                            echo "    ✅ {$company['company_name']} - Suscripción asignada\n";
                            $assigned++;
                        } else {
                            echo "    ❌ {$company['company_name']} - Error\n";
                        }
                    }
                    
                    echo "\n📊 Total asignadas: $assigned\n";
                } else {
                    echo "  ❌ No se encontró estado 'active'\n";
                }
            } else {
                echo "  ❌ No hay planes activos\n";
            }
        } else {
            echo "  ❌ No hay planes disponibles\n";
        }
    } else {
        echo "  ✅ Todas las empresas tienen suscripciones\n";
    }
    
    echo "\n🎉 Verificación completada!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?> 