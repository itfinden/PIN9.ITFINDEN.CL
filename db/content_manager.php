<?php
/**
 * Clase para gestionar el contenido de la página principal
 * con soporte multiidioma
 */

class ContentManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Obtener contenido de una sección específica en un idioma
     */
    public function getSectionContent($section_key, $language = 'es') {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM main_page_content 
                WHERE section_key = ? AND language = ? AND is_active = 1
                LIMIT 1
            ");
            $stmt->execute([$section_key, $language]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo contenido: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtener todo el contenido de la página principal en un idioma
     */
    public function getAllContent($language = 'es') {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM main_page_content 
                WHERE language = ? AND is_active = 1
                ORDER BY sort_order ASC
            ");
            $stmt->execute([$language]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error obteniendo todo el contenido: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener contenido organizado por secciones
     */
    public function getContentBySections($language = 'es') {
        $content = $this->getAllContent($language);
        $organized = [];
        
        foreach ($content as $item) {
            $organized[$item['section_key']] = $item;
        }
        
        return $organized;
    }
    
    /**
     * Obtener idiomas disponibles
     */
    public function getAvailableLanguages() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT DISTINCT language FROM main_page_content 
                WHERE is_active = 1 
                ORDER BY language
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Error obteniendo idiomas: " . $e->getMessage());
            return ['es'];
        }
    }
    
    /**
     * Verificar si existe contenido para un idioma
     */
    public function hasLanguageContent($language) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM main_page_content 
                WHERE language = ? AND is_active = 1
            ");
            $stmt->execute([$language]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Error verificando idioma: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener contenido con fallback a idioma por defecto
     */
    public function getContentWithFallback($section_key, $preferred_language = 'es', $fallback_language = 'es') {
        $content = $this->getSectionContent($section_key, $preferred_language);
        
        if (!$content && $preferred_language !== $fallback_language) {
            $content = $this->getSectionContent($section_key, $fallback_language);
        }
        
        return $content;
    }
    
    /**
     * Renderizar contenido HTML de una sección
     */
    public function renderSection($section_key, $language = 'es', $template = 'default') {
        $content = $this->getSectionContent($section_key, $language);
        
        if (!$content) {
            return '';
        }
        
        switch ($template) {
            case 'hero':
                return $this->renderHeroSection($content);
            case 'feature':
                return $this->renderFeatureSection($content);
            default:
                return $this->renderDefaultSection($content);
        }
    }
    
    /**
     * Renderizar sección hero
     */
    private function renderHeroSection($content) {
        $html = '<div class="container mx-5 mt-3">';
        $html .= '<h2 class="display-4"><small>' . htmlspecialchars($content['title']) . '</small></h2>';
        $html .= '<p class="lead">' . htmlspecialchars($content['subtitle']) . '</p>';
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Renderizar sección de características
     */
    private function renderFeatureSection($content) {
        $html = '<div class="container mx-5 mr-5 mt-3 d-inline-block">';
        $html .= '<h4 class="text-primary pr-5">';
        if ($content['icon']) {
            $html .= '<i class="' . htmlspecialchars($content['icon']) . ' pr-3"></i>';
        }
        $html .= htmlspecialchars($content['title']) . '</h4>';
        $html .= '<p class="text-muted pr-5">' . htmlspecialchars($content['subtitle']) . '</p>';
        
        if ($content['description']) {
            $html .= '<div class="mt-2">' . $content['description'] . '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Renderizar sección por defecto
     */
    private function renderDefaultSection($content) {
        $html = '<div class="content-section">';
        $html .= '<h4 class="text-primary">';
        if ($content['icon']) {
            $html .= '<i class="' . htmlspecialchars($content['icon']) . '"></i> ';
        }
        $html .= htmlspecialchars($content['title']) . '</h4>';
        $html .= '<p>' . htmlspecialchars($content['subtitle']) . '</p>';
        
        if ($content['description']) {
            $html .= '<div class="mt-2">' . $content['description'] . '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }
}
?>
