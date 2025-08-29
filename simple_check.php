<?php
echo "🔍 Verificación Simple de Funciones\n";
echo "==================================\n\n";

// Verificar si el archivo existe
$file = 'db/functions.php';
if (file_exists($file)) {
    echo "✅ Archivo existe: $file\n\n";
    
    // Leer el contenido del archivo
    $content = file_get_contents($file);
    echo "📋 Tamaño del archivo: " . strlen($content) . " caracteres\n\n";
    
    // Buscar las funciones específicas
    $functions_to_check = [
        'isSuperAdmin',
        'getAllCompaniesAndCalendars'
    ];
    
    echo "📋 Buscando funciones en el archivo:\n";
    foreach ($functions_to_check as $function) {
        if (strpos($content, "function $function") !== false) {
            echo "✅ Función '$function' ENCONTRADA en el archivo\n";
        } else {
            echo "❌ Función '$function' NO encontrada en el archivo\n";
        }
    }
    
    echo "\n📋 Últimas 200 caracteres del archivo:\n";
    echo substr($content, -200);
    
} else {
    echo "❌ Archivo no existe: $file\n";
}

echo "\n✅ Verificación completada\n";
?> 