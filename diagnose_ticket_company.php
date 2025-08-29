<?php
session_start();

// Verificar que el usuario esté logueado
if (!isset($_SESSION['id_user']) || empty($_SESSION['id_user'])) {
    die('❌ No hay usuario logueado');
}

require_once 'db/functions.php';

$database = new Database();
$connection = $database->connection();

echo "🔍 Diagnóstico de Problema de Empresa en Tickets\n";
echo "==============================================\n\n";

// 1. Verificar sesión del usuario
echo "📋 Información de Sesión:\n";
echo "- ID Usuario: " . ($_SESSION['id_user'] ?? 'NO DEFINIDO') . "\n";
echo "- Usuario: " . ($_SESSION['user'] ?? 'NO DEFINIDO') . "\n";
echo "- ID Empresa: " . ($_SESSION['id_company'] ?? 'NO DEFINIDO') . "\n";
echo "- Nombre Empresa: " . ($_SESSION['name_company'] ?? 'NO DEFINIDO') . "\n";
echo "- Rol: " . ($_SESSION['rol_name'] ?? 'NO DEFINIDO') . "\n\n";

// 2. Verificar si existe la empresa en la sesión
$id_company = $_SESSION['id_company'] ?? null;
if (!$id_company) {
    echo "❌ PROBLEMA: No hay ID de empresa en la sesión\n\n";
    
    // Intentar obtener la empresa del usuario
    echo "🔧 Intentando obtener empresa del usuario...\n";
    $stmt = $connection->prepare("
        SELECT cu.id_company, c.company_name
        FROM company_users cu
        JOIN companies c ON cu.id_company = c.id_company
        WHERE cu.id_user = ? AND cu.status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['id_user']]);
    $company_info = $stmt->fetch();
    
    if ($company_info) {
        echo "✅ Empresa encontrada: ID " . $company_info['id_company'] . " - " . $company_info['company_name'] . "\n";
        $id_company = $company_info['id_company'];
    } else {
        echo "❌ No se encontró empresa asociada al usuario\n\n";
        
        // Verificar si hay empresas en la base de datos
        echo "🔍 Verificando empresas disponibles...\n";
        $stmt = $connection->prepare("SELECT id_company, company_name FROM companies LIMIT 5");
        $stmt->execute();
        $companies = $stmt->fetchAll();
        
        if ($companies) {
            echo "📋 Empresas disponibles:\n";
            foreach ($companies as $company) {
                echo "- ID: " . $company['id_company'] . " | Nombre: " . $company['company_name'] . "\n";
            }
        } else {
            echo "❌ No hay empresas en la base de datos\n";
        }
    }
} else {
    echo "✅ ID de empresa en sesión: $id_company\n\n";
}

// 3. Verificar que la empresa existe en la tabla companies
if ($id_company) {
    echo "🔍 Verificando existencia de empresa en base de datos...\n";
    $stmt = $connection->prepare("SELECT id_company, company_name FROM companies WHERE id_company = ?");
    $stmt->execute([$id_company]);
    $company = $stmt->fetch();
    
    if ($company) {
        echo "✅ Empresa existe: " . $company['company_name'] . "\n";
    } else {
        echo "❌ PROBLEMA: La empresa con ID $id_company NO existe en la tabla companies\n";
    }
}

// 4. Verificar relación usuario-empresa
echo "\n🔍 Verificando relación usuario-empresa...\n";
$stmt = $connection->prepare("
    SELECT cu.id_company, cu.status, c.company_name
    FROM company_users cu
    JOIN companies c ON cu.id_company = c.id_company
    WHERE cu.id_user = ?
");
$stmt->execute([$_SESSION['id_user']]);
$user_companies = $stmt->fetchAll();

if ($user_companies) {
    echo "📋 Usuario asociado a empresas:\n";
    foreach ($user_companies as $uc) {
        echo "- ID: " . $uc['id_company'] . " | Nombre: " . $uc['company_name'] . " | Estado: " . $uc['status'] . "\n";
    }
} else {
    echo "❌ Usuario no está asociado a ninguna empresa\n";
}

echo "\n🎯 SOLUCIÓN RECOMENDADA:\n";
echo "1. Si no hay empresa en sesión, asignar una empresa al usuario\n";
echo "2. Si la empresa no existe, crear una empresa por defecto\n";
echo "3. Actualizar la sesión con el ID de empresa correcto\n";
?> 