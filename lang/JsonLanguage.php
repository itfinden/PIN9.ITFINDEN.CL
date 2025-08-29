<?php
/**
 * JsonLanguage - Sistema de idiomas basado en archivos JSON
 * Reemplaza la clase Language anterior con un sistema más moderno y estructurado
 */
class JsonLanguage {
    private $lang;
    private $translations = [];
    private $fallback = 'es';
    private $cache = [];
    
    /**
     * Constructor
     * @param string $lang Código de idioma (es, en, fr, etc.)
     */
    public function __construct($lang = 'es') {
        $this->lang = $lang;
        $this->loadTranslations();
    }
    
    /**
     * Cargar traducciones desde archivo JSON
     */
    private function loadTranslations() {
        $file = __DIR__ . "/{$this->lang}.json";
        
        // Intentar cargar el idioma solicitado
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $this->translations = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error parsing JSON for language {$this->lang}: " . json_last_error_msg());
                $this->translations = [];
            }
        }
        
        // Si no se pudo cargar o está vacío, usar fallback
        if (empty($this->translations)) {
            $this->loadFallback();
        }
    }
    
    /**
     * Cargar idioma de respaldo
     */
    private function loadFallback() {
        if ($this->lang !== $this->fallback) {
            $fallbackFile = __DIR__ . "/{$this->fallback}.json";
            if (file_exists($fallbackFile)) {
                $content = file_get_contents($fallbackFile);
                $this->translations = json_decode($content, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("Error parsing fallback JSON: " . json_last_error_msg());
                    $this->translations = [];
                }
            }
        }
    }
    
    /**
     * Obtener traducción usando notación de puntos (ej: "calendar.data_update")
     * @param string $key Clave de traducción (ej: "calendar.data_update")
     * @param array $params Parámetros para reemplazar en el texto
     * @param string $default Valor por defecto si no se encuentra la clave
     * @return string Traducción o clave original si no se encuentra
     */
    public function get($key, $params = [], $default = null) {
        // Verificar cache
        $cacheKey = $this->lang . '_' . $key;
        if (isset($this->cache[$cacheKey])) {
            return $this->replaceParams($this->cache[$cacheKey], $params);
        }
        
        // Buscar en traducciones usando notación de puntos
        $keys = explode('.', $key);
        $value = $this->translations;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                // Si no se encuentra, usar fallback o clave original
                $value = $default ?? $key;
                break;
            }
        }
        
        // Cachear resultado
        $this->cache[$cacheKey] = $value;
        
        // Reemplazar parámetros y retornar
        return $this->replaceParams($value, $params);
    }
    
    /**
     * Reemplazar parámetros en el texto (ej: {name} -> "Juan")
     * @param string $text Texto con parámetros
     * @param array $params Parámetros a reemplazar
     * @return string Texto con parámetros reemplazados
     */
    private function replaceParams($text, $params) {
        if (empty($params) || !is_string($text)) {
            return $text;
        }
        
        foreach ($params as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        
        return $text;
    }
    
    /**
     * Obtener idioma actual
     * @return string Código de idioma
     */
    public function getLanguage() {
        return $this->lang;
    }
    
    /**
     * Cambiar idioma dinámicamente
     * @param string $lang Nuevo código de idioma
     */
    public function setLanguage($lang) {
        if ($this->lang !== $lang) {
            $this->lang = $lang;
            $this->cache = []; // Limpiar cache
            $this->loadTranslations();
        }
    }
    
    /**
     * Verificar si existe una clave
     * @param string $key Clave a verificar
     * @return bool True si existe, false si no
     */
    public function has($key) {
        $keys = explode('.', $key);
        $value = $this->translations;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Obtener todas las traducciones del idioma actual
     * @return array Array con todas las traducciones
     */
    public function getAll() {
        return $this->translations;
    }
    
    /**
     * Obtener sección específica
     * @param string $section Nombre de la sección
     * @return array Array con las traducciones de la sección
     */
    public function getSection($section) {
        return $this->translations[$section] ?? [];
    }
    
    /**
     * Método estático para compatibilidad con código existente
     * @param string $lang Código de idioma
     * @return JsonLanguage Instancia de la clase
     */
    public static function autoDetect($lang = null) {
        if ($lang === null) {
            $lang = $_SESSION['lang'] ?? 'es';
        }
        return new self($lang);
    }
    
    /**
     * Método para compatibilidad con código existente
     * @return string Código de idioma
     */
    public function language() {
        return $this->lang;
    }
}
?>
