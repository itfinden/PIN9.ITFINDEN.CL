<?php
// settings.php - Archivo de configuración global

/**
 * Configuración del entorno
 */
define('ENVIRONMENT', 'development'); // 'development' o 'production'

/**
 * Configuración de la base de datos
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'usuario_db');
define('DB_PASS', 'contraseña_segura');
define('DB_NAME', 'nombre_base_datos');
define('DB_CHARSET', 'utf8mb4');

/**
 * Configuración del sitio
 */
define('SITE_NAME', 'PIN9 SITE');
define('SITE_URL', 'https://pin9.itfinden.cl/');
define('SITE_LANG', 'es-ES');
define('SITE_TIMEZONE', 'America/Santiago');
define('ADMIN_EMAIL', 'friedrich@itfinden.com');

/**
 * Rutas importantes
 */
define('BASE_PATH', realpath(dirname(__FILE__) . '/..'));
define('APP_PATH', BASE_PATH . '/app');
define('VIEWS_PATH', APP_PATH . '/views');
define('UPLOADS_PATH', BASE_PATH . '/public/uploads');

/**
 * Configuración de seguridad
 */
define('SESSION_NAME', 'MISITIO_SESSID');
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_COST', 12); // Coste para password_hash()

/**
 * Configuración de correo
 */
define('MAIL_HOST', 'smtp.misitio.com');
define('MAIL_USER', 'no-reply@misitio.com');
define('MAIL_PASS', 'contraseña_correo');
define('MAIL_PORT', 587);
define('MAIL_FROM', 'no-reply@misitio.com');
define('MAIL_FROM_NAME', SITE_NAME);

/**
 * Configuración para desarrollo
 */
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
    define('CACHE_ENABLED', false);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    define('DEBUG_MODE', false);
    define('CACHE_ENABLED', true);
}

/**
 * Configuración de Evolution API (BOTWhatsapp)
 */
define('EVOLUTION_API_BASE_URL', 'https://bot-itfinden-evolution-api.eswpf9.easypanel.host');
define('EVOLUTION_API_GLOBAL_KEY', '429683C4C977415CAAFCCE10F7D57E11'); // Reemplazar con tu API key real

// Configuración por defecto para nuevas instancias
define('DEFAULT_INTEGRATION_TYPE', 'WHATSAPP-BAILEYS');
define('DEFAULT_QRCODE_ENABLED', true);

// Configuración de webhooks (opcional)
define('WEBHOOK_ENABLED', false);
define('WEBHOOK_URL', '');
define('WEBHOOK_EVENTS', [
    'APPLICATION_STARTUP',
    'QRCODE_UPDATED',
    'MESSAGES_SET',
    'MESSAGES_UPSERT',
    'MESSAGES_UPDATE',
    'MESSAGES_DELETE',
    'SEND_MESSAGE',
    'CONNECTION_UPDATE'
]);

// Configuración de RabbitMQ (opcional)
define('RABBITMQ_ENABLED', false);
define('RABBITMQ_EVENTS', [
    'APPLICATION_STARTUP',
    'QRCODE_UPDATED',
    'MESSAGES_SET',
    'MESSAGES_UPSERT',
    'MESSAGES_UPDATE',
    'MESSAGES_DELETE',
    'SEND_MESSAGE',
    'CONNECTION_UPDATE'
]);

// Configuración de SQS (opcional)
define('SQS_ENABLED', false);
define('SQS_EVENTS', [
    'APPLICATION_STARTUP',
    'QRCODE_UPDATED',
    'MESSAGES_SET',
    'MESSAGES_UPSERT',
    'MESSAGES_UPDATE',
    'MESSAGES_DELETE',
    'SEND_MESSAGE',
    'CONNECTION_UPDATE'
]);

// Configuración de Chatwoot (opcional)
define('CHATWOOT_ENABLED', false);
define('CHATWOOT_CONFIG', [
    'accountId' => '',
    'token' => '',
    'url' => '',
    'signMsg' => true,
    'reopenConversation' => true,
    'conversationPending' => false,
    'importContacts' => true,
    'nameInbox' => 'evolution',
    'mergeBrazilContacts' => true,
    'importMessages' => true,
    'daysLimitImportMessages' => 3,
    'organization' => 'Evolution Bot',
    'logo' => 'https://evolution-api.com/files/evolution-api-favicon.png'
]);

/**
 * Otras configuraciones
 */
define('ITEMS_PER_PAGE', 10); // Para paginación
define('MAX_UPLOAD_SIZE', 5242880); // 5MB en bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'png', 'gif', 'pdf']);

/**
 * Funciones helper para Evolution API (BOTWhatsapp)
 */

/**
 * Obtener configuración de Evolution API
 */
function getEvolutionApiConfig() {
    return [
        'baseUrl' => EVOLUTION_API_BASE_URL,
        'globalApikey' => EVOLUTION_API_GLOBAL_KEY,
        'defaultIntegration' => DEFAULT_INTEGRATION_TYPE,
        'defaultQrcode' => DEFAULT_QRCODE_ENABLED,
        'webhook' => [
            'enabled' => WEBHOOK_ENABLED,
            'url' => WEBHOOK_URL,
            'events' => WEBHOOK_EVENTS
        ],
        'rabbitmq' => [
            'enabled' => RABBITMQ_ENABLED,
            'events' => RABBITMQ_EVENTS
        ],
        'sqs' => [
            'enabled' => SQS_ENABLED,
            'events' => SQS_EVENTS
        ],
        'chatwoot' => [
            'enabled' => CHATWOOT_ENABLED,
            'config' => CHATWOOT_CONFIG
        ]
    ];
}

/**
 * Obtener configuración por defecto para nueva instancia
 */
function getDefaultInstanceConfig($instanceName) {
    $config = [
        'instanceName' => $instanceName,
        'qrcode' => DEFAULT_QRCODE_ENABLED,
        'integration' => DEFAULT_INTEGRATION_TYPE
    ];
    
    // Agregar webhook si está habilitado
    if (WEBHOOK_ENABLED && !empty(WEBHOOK_URL)) {
        $config['webhook'] = [
            'url' => WEBHOOK_URL,
            'byEvents' => false,
            'base64' => true,
            'headers' => [
                'authorization' => 'Bearer ' . EVOLUTION_API_GLOBAL_KEY,
                'Content-Type' => 'application/json'
            ],
            'events' => WEBHOOK_EVENTS
        ];
    }
    
    // Agregar RabbitMQ si está habilitado
    if (RABBITMQ_ENABLED) {
        $config['rabbitmq'] = [
            'enabled' => true,
            'events' => RABBITMQ_EVENTS
        ];
    }
    
    // Agregar SQS si está habilitado
    if (SQS_ENABLED) {
        $config['sqs'] = [
            'enabled' => true,
            'events' => SQS_EVENTS
        ];
    }
    
    // Agregar Chatwoot si está habilitado
    if (CHATWOOT_ENABLED && !empty(CHATWOOT_CONFIG['accountId'])) {
        $config['chatwootAccountId'] = CHATWOOT_CONFIG['accountId'];
        $config['chatwootToken'] = CHATWOOT_CONFIG['token'];
        $config['chatwootUrl'] = CHATWOOT_CONFIG['url'];
        $config['chatwootSignMsg'] = CHATWOOT_CONFIG['signMsg'];
        $config['chatwootReopenConversation'] = CHATWOOT_CONFIG['reopenConversation'];
        $config['chatwootConversationPending'] = CHATWOOT_CONFIG['conversationPending'];
        $config['chatwootImportContacts'] = CHATWOOT_CONFIG['importContacts'];
        $config['chatwootNameInbox'] = CHATWOOT_CONFIG['nameInbox'];
        $config['chatwootMergeBrazilContacts'] = CHATWOOT_CONFIG['mergeBrazilContacts'];
        $config['chatwootImportMessages'] = CHATWOOT_CONFIG['importMessages'];
        $config['chatwootDaysLimitImportMessages'] = CHATWOOT_CONFIG['daysLimitImportMessages'];
        $config['chatwootOrganization'] = CHATWOOT_CONFIG['organization'];
        $config['chatwootLogo'] = CHATWOOT_CONFIG['logo'];
    }
    
    return $config;
}

/**
 * Validar configuración del módulo BOTWhatsapp
 */
function validateBOTWhatsappConfig() {
    $errors = [];
    
    if (empty(EVOLUTION_API_BASE_URL)) {
        $errors[] = 'EVOLUTION_API_BASE_URL no está configurado';
    }
    
    if (empty(EVOLUTION_API_GLOBAL_KEY) || EVOLUTION_API_GLOBAL_KEY === 'tu_global_api_key_aqui') {
        $errors[] = 'EVOLUTION_API_GLOBAL_KEY no está configurado correctamente';
    }
    
    if (WEBHOOK_ENABLED && empty(WEBHOOK_URL)) {
        $errors[] = 'Webhook habilitado pero URL no configurada';
    }
    
    if (CHATWOOT_ENABLED && empty(CHATWOOT_CONFIG['accountId'])) {
        $errors[] = 'Chatwoot habilitado pero accountId no configurado';
    }
    
    return $errors;
}

// Incluir este archivo en tu aplicación principal para tener acceso a estas constantes
?>