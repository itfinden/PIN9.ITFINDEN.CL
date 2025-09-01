<?php
/**
 * Script para crear el sistema de gestión de contenido WYSIWYG
 * con soporte multiidioma para superadmins
 */

require_once __DIR__ . '/../db/connection.php';

try {
    // Crear tabla para el contenido de la página principal
    $sql_content = "CREATE TABLE IF NOT EXISTS `main_page_content` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `section_key` varchar(100) NOT NULL,
        `language` varchar(10) NOT NULL DEFAULT 'es',
        `title` text,
        `subtitle` text,
        `description` longtext,
        `icon` varchar(100),
        `sort_order` int(11) DEFAULT 0,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_section_lang` (`section_key`, `language`),
        KEY `idx_language` (`language`),
        KEY `idx_section` (`section_key`),
        KEY `idx_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_content);
    echo "✅ Tabla main_page_content creada exitosamente\n";
    
    // Crear tabla para el historial de cambios
    $sql_history = "CREATE TABLE IF NOT EXISTS `content_history` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `content_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `action` varchar(50) NOT NULL,
        `old_content` longtext,
        `new_content` longtext,
        `language` varchar(10) NOT NULL,
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_content_id` (`content_id`),
        KEY `idx_user_id` (`user_id`),
        KEY `idx_created_at` (`created_at`),
        FOREIGN KEY (`content_id`) REFERENCES `main_page_content`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql_history);
    echo "✅ Tabla content_history creada exitosamente\n";
    
    // Insertar contenido por defecto en español
    $default_content_es = [
        [
            'section_key' => 'hero',
            'language' => 'es',
            'title' => 'Haz tu vida memorable',
            'subtitle' => 'Mantén tu vida organizada. Somos la solución que necesitas.',
            'description' => '',
            'icon' => '',
            'sort_order' => 1
        ],
        [
            'section_key' => 'business_takeoff',
            'language' => 'es',
            'title' => 'DESPEGA TU NEGOCIO',
            'subtitle' => 'Mantén todos tus proyectos en un solo lugar. Te ofrecemos un tablero Kanban simple donde podrás agregar tantos proyectos y tareas como quieras.',
            'description' => '',
            'icon' => 'fas fa-rocket',
            'sort_order' => 2
        ],
        [
            'section_key' => 'calendar_feature',
            'language' => 'es',
            'title' => 'OLVIDA OLVIDAR',
            'subtitle' => '¿Siempre llegas tarde? Déjanos llevar tu agenda por ti. Te ofrecemos un calendario completamente escalable donde puedes programar todos tus eventos y verlos fácilmente.',
            'description' => '',
            'icon' => 'far fa-calendar-check',
            'sort_order' => 3
        ]
    ];
    
    // Insertar contenido por defecto en inglés
    $default_content_en = [
        [
            'section_key' => 'hero',
            'language' => 'en',
            'title' => 'Make your life memorable',
            'subtitle' => 'Keep your life organized. We are the solution that you need.',
            'description' => '',
            'icon' => '',
            'sort_order' => 1
        ],
        [
            'section_key' => 'business_takeoff',
            'language' => 'en',
            'title' => 'TAKE OFF YOUR BUSINESS',
            'subtitle' => 'Keep all your projects in one place. We offer you a simple Kanban board where you will be able to add as many projects and tasks as you want.',
            'description' => '',
            'icon' => 'fas fa-rocket',
            'sort_order' => 2
        ],
        [
            'section_key' => 'calendar_feature',
            'language' => 'en',
            'title' => 'FORGET ABOUT FORGETTING',
            'subtitle' => 'Always late? Let us take your agenda for you. We offer you a completely scalable calendar where you can schedule all your events and see them easily.',
            'description' => '',
            'icon' => 'far fa-calendar-check',
            'sort_order' => 3
        ]
    ];
    
    // Insertar contenido en español
    $stmt = $pdo->prepare("INSERT IGNORE INTO main_page_content (section_key, language, title, subtitle, description, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($default_content_es as $content) {
        $stmt->execute([
            $content['section_key'],
            $content['language'],
            $content['title'],
            $content['subtitle'],
            $content['description'],
            $content['icon'],
            $content['sort_order']
        ]);
    }
    echo "✅ Contenido por defecto en español insertado\n";
    
    // Insertar contenido en inglés
    foreach ($default_content_en as $content) {
        $stmt->execute([
            $content['section_key'],
            $content['language'],
            $content['title'],
            $content['subtitle'],
            $content['description'],
            $content['icon'],
            $content['sort_order']
        ]);
    }
    echo "✅ Contenido por defecto en inglés insertado\n";
    
    echo "\n🎉 Sistema de gestión de contenido creado exitosamente!\n";
    echo "📝 Ahora puedes acceder al panel de administración para editar el contenido.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
