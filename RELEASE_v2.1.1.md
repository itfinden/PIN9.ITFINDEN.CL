# PIN9 v2.1.1 - Subrelease

**Fecha:** 11 de Agosto, 2025  
**Versión:** 2.1.1  
**Tipo:** Subrelease (Mejoras y correcciones)

## 🚀 Nuevas Funcionalidades

### Slide Panel de Calendarios
- **Pestaña con engranaje**: Nueva pestaña lateral derecha con icono de engranaje
- **Posición**: Esquina derecha centrada verticalmente
- **Acceso**: Solo para superadmins y usuarios con permisos `manage_calendars`
- **Diseño**: Gradiente gris oscuro con texto vertical "CALENDARIOS"

### Interfaz de Calendarios Mejorada
- **Lista de calendarios**: Cajas organizadas en columna vertical
- **Selección visual**: Calendario activo destacado con borde blanco
- **Información completa**: Nombre, empresa (para superadmins), badge "Default"
- **Scroll vertical**: Con barra personalizada para mejor UX

### Notificaciones SweetAlert2
- **Reemplazo de alerts**: Notificaciones elegantes en lugar de alerts del navegador
- **Posición**: Esquina superior derecha
- **Duración**: 2 segundos automáticamente
- **Tipos**: Success (verde) y Error (rojo)
- **Barra de progreso**: Muestra tiempo restante

## 🔧 Mejoras Técnicas

### Sistema de Idiomas
- **Manejo mejorado**: Parámetro `?lang=en` funciona correctamente
- **Integración**: `language_handler.php` incluido en `calendar.php`
- **Compatibilidad**: Funciona con cambio de idioma sin errores

### Estructura de Código
- **Variables pasadas**: Datos de calendarios pasados correctamente a la vista
- **Debug mejorado**: Comentarios de debug para troubleshooting
- **Código limpio**: Eliminación de código duplicado y redundante

## 🐛 Correcciones

### Calendario
- **Slide panel funcional**: Los calendarios aparecen correctamente en el panel
- **Selección de calendarios**: Funciona la selección y cambio de calendario activo
- **Interfaz limpia**: Calendario principal sin distracciones visuales

### JavaScript
- **Alertas reemplazadas**: Todos los `alert()` cambiados por notificaciones SweetAlert2
- **Funcionalidad completa**: Calendario funciona correctamente con notificaciones

## 📁 Archivos Modificados

- `calendar.php` - Integración de language_handler.php
- `calendar2.php` - Implementación de SweetAlert2 y notificaciones
- `views/calendar.view.php` - Slide panel y interfaz de calendarios

## 🎯 Características del Slide Panel

### Diseño
- **Ancho**: 400px (responsive en móviles)
- **Animación**: Transición suave desde la derecha
- **Overlay**: Fondo semi-transparente
- **Cierre**: Botón X, overlay o tecla ESC

### Contenido
- **Header**: Título "Calendarios" con icono de engranaje
- **Lista de calendarios**: Botones con colores y información
- **Información del sistema**: Usuario, tipo, calendarios disponibles
- **Estado**: Calendario activo marcado con checkmark

## 🔒 Permisos

- **Superadmins**: Acceso completo a todos los calendarios
- **Admins de empresa**: Acceso a calendarios de su empresa
- **Usuarios normales**: Sin acceso al slide panel

## 📱 Responsive

- **Desktop**: Panel de 400px de ancho
- **Móvil**: Panel de ancho completo (100%)
- **Pestaña**: Siempre visible en dispositivos móviles

## 🚀 Instalación

1. **Git**: `git checkout v2.1.1`
2. **Backup**: Archivo `PIN9_v2.1.1_20250811_084352.tar.gz` disponible
3. **Dependencias**: SweetAlert2 incluido via CDN

## 📊 Estadísticas

- **Archivos modificados**: 3
- **Líneas agregadas**: 400+
- **Líneas eliminadas**: 261
- **Tamaño del backup**: ~276 MB

## 🔮 Próximas Mejoras

- Configuración avanzada de calendarios
- Personalización de colores y temas
- Integración con más tipos de eventos
- Exportación de calendarios

---

**Desarrollado por:** ITFINDEN SPA  
**Versión anterior:** v2.1.0  
**Próxima versión:** v2.2.0

