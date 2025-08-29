<?php
require_once 'Language.php';

// Obtener instancia del lenguaje
$lang = Language::getInstance();

// Ejemplo 1: Traducción simple
echo $lang->get('WELCOME') . "\n";

// Ejemplo 2: Traducción con parámetros
echo $lang->get('GREETING', ['name' => 'Juan']) . "\n";

// Ejemplo 3: Traducción anidada
echo $lang->get('PRODUCTS.TITLE') . "\n";

// Ejemplo 4: Traducción anidada con parámetros
echo $lang->get('PRODUCTS.COUNT', ['count' => 5]) . "\n";

// Ejemplo 5: Cambiar idioma dinámicamente
$lang->setLanguage('es');
echo $lang->get('BUTTONS.SAVE') . "\n";

// Ejemplo 6: Manejo de errores
echo $lang->get('ERRORS.REQUIRED', ['field' => 'email']) . "\n";

// Ejemplo 7: Obtener todas las traducciones del idioma actual
// print_r($lang->getAll());

?>