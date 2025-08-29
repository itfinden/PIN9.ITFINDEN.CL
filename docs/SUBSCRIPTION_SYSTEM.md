# Sistema de Suscripciones y Facturación - PIN9 v2.0

## 📋 **Descripción General**

El Sistema de Suscripciones y Facturación v2.0 es un módulo completo y mejorado que permite al superadmin gestionar planes de suscripción, facturación automática, métodos de pago y reportes financieros para todas las empresas registradas en la plataforma.

### **🚀 Nuevas Características v2.0:**

- ✅ **ENUMs reemplazados por tablas externas** para mejor administración
- ✅ **Sistema de estados configurable** (suscripciones, facturas, pagos, transacciones)
- ✅ **Tipos de método de pago extensibles**
- ✅ **Ciclos de facturación personalizables**
- ✅ **Sistema de notificaciones avanzado**
- ✅ **Configuración completa del sistema**
- ✅ **Script de instalación automática**

## 🏗️ **Arquitectura del Sistema v2.0**

### **Base de Datos Mejorada**

#### **Tablas de Configuración (Nuevas):**

1. **`subscription_statuses`** - Estados de suscripción configurables
2. **`payment_statuses`** - Estados de pago configurables
3. **`invoice_statuses`** - Estados de factura configurables
4. **`transaction_statuses`** - Estados de transacción configurables
5. **`payment_method_types`** - Tipos de método de pago
6. **`billing_cycles`** - Ciclos de facturación
7. **`notification_types`** - Tipos de notificación

#### **Tablas Principales (Actualizadas):**

1. **`subscription_plans`** - Planes de suscripción disponibles
2. **`company_subscriptions`** - Suscripciones activas de empresas
3. **`invoices`** - Facturas generadas
4. **`invoice_items`** - Detalles de facturas
5. **`payment_methods`** - Métodos de pago configurados
6. **`payment_transactions`** - Transacciones de pago
7. **`payment_notifications`** - Notificaciones de pago
8. **`billing_config`** - Configuración del sistema

#### **Vistas Útiles:**

- `v_active_subscriptions` - Suscripciones activas
- `v_pending_invoices` - Facturas pendientes
- `v_billing_metrics` - Métricas de facturación

## 🚀 **Funcionalidades Principales**

### **1. Gestión de Planes de Suscripción**

#### **Características:**
- ✅ Crear, editar y eliminar planes
- ✅ Definir precios y ciclos de facturación
- ✅ Configurar límites (usuarios, proyectos, almacenamiento)
- ✅ Activar/desactivar características por plan
- ✅ Marcar planes como "populares"
- ✅ Ordenamiento personalizado

#### **Tipos de Planes:**
- **Básico**: $29.900/mes - 5 usuarios, 20 proyectos, 2GB
- **Profesional**: $59.900/mes - 15 usuarios, 50 proyectos, 10GB
- **Empresarial**: $99.900/mes - 50 usuarios, 200 proyectos, 50GB

### **2. Panel de Administración de Suscripciones**

#### **Dashboard Principal:**
- 📊 Métricas en tiempo real
- ⚠️ Alertas de suscripciones que vencen
- 💰 Facturas vencidas
- 📈 Últimas transacciones
- 🔄 Estado de pagos

#### **Métricas Mostradas:**
- Total de empresas registradas
- Suscripciones activas
- Pagos pendientes
- Ingresos totales

### **3. Sistema de Facturación Avanzado**

#### **Generación Automática:**
- Facturas mensuales/trimestrales/anuales
- Cálculo automático de impuestos (IVA configurable)
- Numeración secuencial personalizable
- Generación de PDF (en desarrollo)

#### **Estados de Factura Configurables:**
- `draft` - Borrador
- `sent` - Enviada
- `paid` - Pagada
- `overdue` - Vencida
- `cancelled` - Cancelada
- `refunded` - Reembolsada

### **4. Métodos de Pago Extensibles**

#### **Soportados:**
- 🏦 **Transferencia Bancaria** (configurable)
- 💳 **WebPay Plus** (Tarjetas)
- 💰 **PayPal**
- 💳 **Tarjetas de Crédito/Débito**
- 📝 **Pago Manual**

#### **Configuración Avanzada:**
- Datos bancarios configurables
- Claves API para pasarelas
- Configuración por método
- Ambientes de prueba/producción

### **5. Sistema de Notificaciones**

#### **Tipos de Notificaciones Configurables:**
- 📅 Recordatorio de pago próximo
- ⚠️ Factura vencida
- 🔄 Suscripción por vencer
- ✅ Pago recibido
- 🔄 Suscripción renovada

#### **Configuración de Email:**
- Servidor SMTP configurable
- Plantillas personalizables
- Variables dinámicas
- Programación automática

### **6. Reportes y Analytics**

#### **Gráficos Interactivos:**
- 📈 Ingresos mensuales
- 🥧 Distribución de facturas por estado
- 📊 Uso de métodos de pago
- 📋 Empresas por plan

#### **Métricas Avanzadas:**
- Top empresas por facturación
- Facturas vencidas
- Tendencias de crecimiento
- Análisis de conversión

## 📁 **Estructura de Archivos v2.0**

```
admin/
├── subscriptions.php              # Panel principal
├── subscription_plans.php         # CRUD de planes
├── invoices.php                   # Gestión de facturas
├── payment_methods.php            # Métodos de pago
├── billing_reports.php            # Reportes
└── billing_config.php             # Configuración

db/
├── subscription_system_v2.sql     # Esquema completo v2.0
├── migrate_to_v2.sql              # Script de migración
├── add_subscription_permissions.sql # Permisos
└── generate_test_data.sql         # Datos de prueba

docs/
└── SUBSCRIPTION_SYSTEM.md         # Esta documentación

install_subscription_system.php    # Script de instalación
```

## 🔧 **Instalación y Configuración**

### **Instalación Automática:**

```bash
# 1. Configurar la base de datos en install_subscription_system.php
# 2. Ejecutar el script de instalación
php install_subscription_system.php
```

### **Instalación Manual:**

```sql
-- 1. Ejecutar el esquema completo
SOURCE db/subscription_system_v2.sql;

-- 2. Agregar permisos
SOURCE db/add_subscription_permissions.sql;

-- 3. Generar datos de prueba (opcional)
SOURCE db/generate_test_data.sql;
```

### **Configuración Inicial:**

1. **Acceder al panel de administración:**
   - URL: `/admin/subscriptions.php`

2. **Configurar métodos de pago:**
   - URL: `/admin/payment_methods.php`

3. **Personalizar configuración:**
   - URL: `/admin/billing_config.php`

4. **Revisar reportes:**
   - URL: `/admin/billing_reports.php`

5. **Gestionar facturas:**
   - URL: `/admin/invoices.php`

## 📊 **Reportes Disponibles**

### **1. Métricas de Facturación:**
- Ingresos totales
- Suscripciones activas
- Tasa de conversión
- Promedio de plan

### **2. Reportes de Empresas:**
- Empresas por plan
- Empresas vencidas
- Historial de pagos

### **3. Análisis Financiero:**
- Ingresos por período
- Métodos de pago más usados
- Facturas pendientes

### **4. Gráficos Interactivos:**
- Líneas de tiempo
- Gráficos de dona
- Gráficos de barras
- Exportación de datos

## 🔐 **Seguridad y Permisos**

### **Permisos Requeridos:**
- `manage_subscriptions` - Panel principal
- `manage_subscription_plans` - Gestión de planes
- `manage_invoices` - Gestión de facturas
- `manage_payment_methods` - Métodos de pago
- `view_billing_reports` - Reportes
- `manage_billing_config` - Configuración

### **Auditoría:**
- Todas las acciones se registran en `audit_logs`
- Trazabilidad completa de cambios
- Historial de transacciones

## 🚀 **Próximas Funcionalidades**

### **Fase 2:**
- 🔄 Renovación automática de suscripciones
- 📧 Notificaciones por email
- 📱 Notificaciones push
- 🔗 Integración con APIs de pago
- 📊 Dashboard avanzado con gráficos

### **Fase 3:**
- 💰 Sistema de descuentos
- 🎁 Promociones y cupones
- 📈 Analytics avanzados
- 🔄 Migración de planes
- 📋 Contratos digitales

### **Fase 4:**
- 🤖 Automatización completa
- 📱 App móvil
- 🔗 APIs públicas
- 🌐 Multi-idioma
- 💳 Más pasarelas de pago

## 🛠️ **Mantenimiento**

### **Tareas Automáticas Recomendadas:**

1. **Generación de Facturas:**
   ```bash
   # Cron job diario
   0 1 * * * php /path/to/generate_invoices.php
   ```

2. **Recordatorios de Pago:**
   ```bash
   # Cron job diario
   0 9 * * * php /path/to/send_payment_reminders.php
   ```

3. **Limpieza de Datos:**
   ```bash
   # Cron job semanal
   0 2 * * 0 php /path/to/cleanup_old_data.php
   ```

4. **Backup Automático:**
   ```bash
   # Cron job diario
   0 3 * * * /path/to/backup_database.sh
   ```

### **Monitoreo:**
- Verificar logs de errores
- Monitorear métricas de rendimiento
- Revisar alertas de facturas vencidas
- Controlar uso de recursos

## 🔧 **Solución de Problemas**

### **Problemas Comunes:**

1. **Error de conexión a base de datos:**
   - Verificar credenciales en `db/functions.php`
   - Comprobar que MySQL esté ejecutándose

2. **Permisos denegados:**
   - Verificar que el usuario tenga permisos de superadmin
   - Revisar la tabla `role_permissions`

3. **Facturas no se generan:**
   - Verificar configuración de facturación
   - Comprobar que las suscripciones estén activas

4. **Emails no se envían:**
   - Configurar credenciales SMTP
   - Verificar configuración de email

### **Logs y Debugging:**
- Habilitar modo debug en `billing_config`
- Revisar logs de PHP
- Verificar logs de MySQL

## 📞 **Soporte**

Para soporte técnico o consultas sobre el sistema de suscripciones:

- 📧 Email: soporte@itfinden.cl
- 📱 WhatsApp: +56 9 1234 5678
- 🌐 Web: https://itfinden.cl/soporte
- 📚 Documentación: docs/SUBSCRIPTION_SYSTEM.md

## 📋 **Changelog**

### **v2.0.0 (2025-07-28)**
- ✅ Reemplazo de ENUMs por tablas externas
- ✅ Sistema de estados configurable
- ✅ Métodos de pago extensibles
- ✅ Configuración completa del sistema
- ✅ Script de instalación automática
- ✅ Datos de prueba incluidos
- ✅ Documentación actualizada

### **v1.0.0 (2025-07-27)**
- ✅ Sistema básico de suscripciones
- ✅ Gestión de planes
- ✅ Facturación básica
- ✅ Métodos de pago básicos

---

**Versión:** 2.0.0  
**Última actualización:** 28 de Julio 2025  
**Desarrollado por:** ITFINDEN SPA  
**Compatibilidad:** PHP 7.4+, MySQL 5.7+ 