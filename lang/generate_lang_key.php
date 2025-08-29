<?php
/**
 * Generador de claves de idioma a partir de un texto.
 * Uso: Llamar a generateLangKey('Texto de ejemplo') para obtener la clave y el valor listo para pegar en el archivo de idioma.
 */

function generateLangKey($text) {
    // Quitar acentos y caracteres especiales
    $key = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    // Reemplazar espacios y caracteres no alfanuméricos por guiones bajos
    $key = preg_replace('/[^A-Za-z0-9]+/', '_', $key);
    // Quitar guiones bajos al inicio y final
    $key = trim($key, '_');
    // Convertir a mayúsculas
    $key = strtoupper($key);
    return [
        'key' => $key,
        'php' => "    '$key' => '$text',"
    ];
}

// Ejemplo de uso interactivo:
if (php_sapi_name() === 'cli' && isset($argv[1])) {
    $result = generateLangKey($argv[1]);
    echo $result['php'] . "\n";
} 