<?php
/**
 * Script para verificar el estado de Git y mostrar diferencias
 */

echo "🔍 VERIFICANDO ESTADO DE GIT\n";
echo "============================\n\n";

// Verificar si estamos en un repositorio Git
if (!is_dir('.git')) {
    echo "❌ ERROR: No se encontró un repositorio Git en este directorio\n";
    echo "Asegúrate de estar en el directorio correcto del proyecto\n";
    exit;
}

echo "📊 ESTADO ACTUAL DEL REPOSITORIO:\n";
echo "==================================\n\n";

// Ejecutar git status
$git_status = shell_exec('git status --porcelain 2>&1');
$git_status_lines = explode("\n", trim($git_status));

$modified_files = [];
$untracked_files = [];
$deleted_files = [];

foreach ($git_status_lines as $line) {
    if (empty($line)) continue;
    
    $status = substr($line, 0, 2);
    $file = substr($line, 3);
    
    switch ($status) {
        case 'M ':
        case 'MM':
            $modified_files[] = $file;
            break;
        case '??':
            $untracked_files[] = $file;
            break;
        case 'D ':
            $deleted_files[] = $file;
            break;
    }
}

echo "📝 ARCHIVOS MODIFICADOS (" . count($modified_files) . "):\n";
echo "========================================\n";
foreach ($modified_files as $file) {
    echo "✅ $file\n";
}

echo "\n📄 ARCHIVOS SIN SEGUIMIENTO (" . count($untracked_files) . "):\n";
echo "==========================================\n";
foreach ($untracked_files as $file) {
    echo "❓ $file\n";
}

echo "\n🗑️  ARCHIVOS ELIMINADOS (" . count($deleted_files) . "):\n";
echo "=====================================\n";
foreach ($deleted_files as $file) {
    echo "❌ $file\n";
}

echo "\n💡 COMANDOS ÚTILES:\n";
echo "===================\n";
echo "git status                    # Ver estado completo\n";
echo "git diff archivo.php          # Ver diferencias en un archivo\n";
echo "git add archivo.php           # Agregar archivo específico\n";
echo "git add .                     # Agregar todos los archivos\n";
echo "git commit -m \"mensaje\"      # Hacer commit\n";
echo "git push                      # Subir al servidor\n\n";

// Mostrar diferencias de ejemplo para el primer archivo modificado
if (!empty($modified_files)) {
    $example_file = $modified_files[0];
    echo "🔍 EJEMPLO DE DIFERENCIAS EN: $example_file\n";
    echo "============================================\n";
    
    $git_diff = shell_exec("git diff $example_file 2>&1");
    if ($git_diff) {
        echo $git_diff . "\n";
    } else {
        echo "No se pudieron mostrar las diferencias\n";
    }
}

echo "\n🎯 RECOMENDACIONES:\n";
echo "==================\n";
echo "1. Revisa los archivos modificados antes de hacer commit\n";
echo "2. Verifica que los cambios de language_handler.php estén correctos\n";
echo "3. Si hay archivos .backup, puedes eliminarlos después de verificar\n";
echo "4. Haz commit solo de los archivos que realmente necesitas\n\n";

echo "📋 COMANDOS PARA APLICAR CAMBIOS:\n";
echo "=================================\n";
echo "git add .                     # Agregar todos los cambios\n";
echo "git commit -m \"Agregado language_handler.php a archivos principales\"\n";
echo "git push                      # Subir al servidor\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "==============\n";
echo "• Verifica que los archivos funcionen antes de hacer push\n";
echo "• Los archivos .backup se pueden eliminar después de verificar\n";
echo "• Si hay errores, puedes restaurar desde los backups\n";
?>
