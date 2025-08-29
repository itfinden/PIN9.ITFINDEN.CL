# Módulo BOTWhatsapp para PIN9

Este módulo permite gestionar bots de WhatsApp usando la Evolution API, con acceso restringido a administradores y superadministradores.

## Características

- ✅ Crear instancias de WhatsApp automáticamente
- ✅ Gestionar múltiples bots por empresa
- ✅ Verificar estado de conexión
- ✅ Enviar mensajes de prueba
- ✅ Integración completa con Evolution API
- ✅ Soporte para temas claro/oscuro
- ✅ Sistema de permisos basado en roles

## Instalación

### 1. Instalar el módulo
```bash
# Acceder como superadmin y ejecutar
/Modules/BOTWhatsapp/install.php
```

### 2. Configurar variables globales
```bash
# Editar el archivo de configuración global
nano config/setting.php
```

**Configuración obligatoria:**
```php
define('EVOLUTION_API_BASE_URL', 'https://tu-dominio-evolution-api.com');
define('EVOLUTION_API_GLOBAL_KEY', 'tu_api_key_real_aqui');
```

**Configuración opcional:**
- Webhooks para recibir eventos
- RabbitMQ para mensajería
- SQS para colas de mensajes
- Chatwoot para atención al cliente

### 3. Acceder al módulo
- Navegar a `/Modules/BOTWhatsapp/manage.php`
- Solo accesible para admins y superadmins

## Uso

### Crear un nuevo bot
1. Llenar formulario con:
   - **Teléfono:** Número de WhatsApp (+56912345678)
   - **Nombre de Instancia:** Identificador único (mi-bot-empresa)
2. El sistema automáticamente:
   - Crea la instancia en Evolution API
   - Configura WhatsApp-BAILEYS
   - Habilita generación de QR
   - Guarda en base de datos

### Gestionar bots existentes
- **Ver estado:** Botón info para verificar conexión
- **Enviar prueba:** Botón enviar para mensaje de prueba
- **Eliminar:** Botón eliminar para borrar bot y instancia

## Estructura de archivos

```
Modules/BOTWhatsapp/
├── install.php             # Instalador del módulo
├── manage.php              # Página principal de gestión
└── README.md               # Este archivo

config/setting.php          # Configuración global (incluye Evolution API)
```

## Configuración avanzada

### Webhooks
```php
define('WEBHOOK_ENABLED', true);
define('WEBHOOK_URL', 'https://tu-webhook.com/evolution-events');
```

### RabbitMQ
```php
define('RABBITMQ_ENABLED', true);
```

### Chatwoot
```php
define('CHATWOOT_ENABLED', true);
define('CHATWOOT_CONFIG', [
    'accountId' => '1',
    'token' => 'tu_token',
    'url' => 'https://tu-chatwoot.com'
]);
```

## Base de datos

El módulo crea la tabla `whatsapp_bots` con:
- `id_bot`: Identificador único
- `id_company`: Empresa del bot (scope)
- `phone_number`: Número de WhatsApp
- `instance_name`: Nombre de instancia en Evolution API
- `evolutionapi_base_url`: URL base de la API
- `evolutionapi_token`: API key para la instancia
- `status`: Estado del bot (inactive/active)
- `created_at`: Fecha de creación

## Permisos

- **Superadmin:** Acceso completo, ve todos los bots
- **Admin:** Acceso a bots de su empresa
- **Usuario normal:** Sin acceso

## Troubleshooting

### Error "There is no active transaction"
- El archivo `install.php` ya está corregido
- Ejecutar nuevamente la instalación

### Error de conexión a Evolution API
- Verificar `EVOLUTION_API_BASE_URL` en `config/setting.php`
- Verificar `EVOLUTION_API_GLOBAL_KEY` en `config/setting.php`
- Comprobar conectividad de red

### Bot no aparece en la lista
- Verificar permisos de usuario
- Verificar scope de empresa
- Revisar logs de auditoría

## Logs

El módulo registra todas las acciones en `audit_log`:
- Creación de bots
- Eliminación de bots
- Verificación de estado
- Envío de mensajes
- Errores de API

## Soporte

Para problemas o mejoras:
1. Revisar logs de auditoría
2. Verificar configuración en `config/setting.php`
3. Comprobar permisos de usuario
4. Revisar conectividad con Evolution API
