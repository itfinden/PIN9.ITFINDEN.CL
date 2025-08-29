<?php

// Solo establecer el idioma por defecto si no está definido y la sesión está activa
if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['lang'])) {
    $_SESSION['lang'] = "es";
}

class Language {
    private static $instance = null;
    private $language = 'en';
    private $translations = [];
    private $loadedLanguages = [];

    private function __construct() {
        // Cargar idioma predeterminado
        $this->loadLanguage($this->language);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setLanguage($language) {
        $language = strtoupper($language);
        if (!in_array($language, ['EN', 'ES'])) {
            throw new Exception("Unsupported language: $language");
        }
        
        $this->language = strtolower($language);
        $this->loadLanguage($this->language);
    }

    private function loadLanguage($language) {
        $language = strtoupper($language);
        if (isset($this->loadedLanguages[$language])) {
            return;
        }

        $filePath = __DIR__ . "/$language.php";
        if (!file_exists($filePath)) {
            throw new Exception("Language file not found: $filePath");
        }

        $this->translations[$language] = require $filePath;
        $this->loadedLanguages[$language] = true;
    }

    public static function autoDetect() {
        $instance = self::getInstance();
        if (isset($_SESSION['lang'])) {
            $instance->setLanguage($_SESSION['lang']);
        } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            $instance->setLanguage(strtoupper($browserLang));
        }
        return $instance;
    }

    public function get($key, $replacements = []) {
        $keys = explode('.', $key);
        $languageKey = strtoupper($this->language);
        
        if (!isset($this->translations[$languageKey])) {
            return $key; // Devolver la clave original si no hay traducción
        }
        
        $translation = $this->translations[$languageKey];

        foreach ($keys as $k) {
            if (!isset($translation[$k])) {
                // Crear la clave automáticamente en ES.php y EN.php
                $keyUpper = strtoupper($key);
                $this->addMissingKey($key, $keyUpper);
                return $keyUpper;
            }
            $translation = $translation[$k];
        }

        // Reemplazar placeholders
        if (is_string($translation)) {
            foreach ($replacements as $placeholder => $value) {
                $translation = str_replace("{$placeholder}", $value, $translation);
            }
        }

        return $translation;
    }

    public function getAll() {
        return $this->translations[strtoupper($this->language)];
    }

    private function addMissingKey($key, $value) {
        $langFiles = [
            'ES' => __DIR__ . '/ES.php',
            'EN' => __DIR__ . '/EN.php',
        ];
        foreach ($langFiles as $file) {
            if (file_exists($file)) {
                $lang = include $file;
                // Soporta claves anidadas
                $ref = &$lang;
                $parts = explode('.', $key);
                foreach ($parts as $i => $part) {
                    if ($i === count($parts) - 1) {
                        $ref[$part] = $value;
                    } else {
                        if (!isset($ref[$part]) || !is_array($ref[$part])) {
                            $ref[$part] = [];
                        }
                        $ref = &$ref[$part];
                    }
                }
                $export = "<?php\nreturn " . var_export($lang, true) . ";\n";
                @file_put_contents($file, $export);
            }
        }
    }
}