# Sistema de Menús Dinámico

## Descripción General

El sistema de menús utiliza una configuración centralizada en `config/menu_config.php` que define la estructura del menú de administración, pero **las rutas se obtienen dinámicamente desde la tabla `permissions` de la base de datos**.

## Arquitectura del Sistema

### Base de Datos: Tabla `permissions`
```sql
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) UNIQUE,
    description TEXT,
    display_name VARCHAR(100),
    route VARCHAR(255)  -- ← Las rutas se almacenan aquí
);
```

### Archivo de Configuración: `config/menu_config.php`
```php
$menu_config = [
    'sistema' => [
        'title' => 'Sistema',
        'icon' => 'fas fa-server',
        'subtitle' => 'Administración del Sistema',
        'permission_required' => ['admin_panel', 'manage_companies', ...],
        'items' => [
            'admin_panel' => [
                'title' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'permission' => 'admin_panel',  // ← Se busca en BD
                'description' => 'Panel principal'
            ]
        ]
    ]
];
```

## Flujo de Funcionamiento

### 1. Lectura de Rutas desde BD
```php
function getPermissionRoutes() {
    $sql = "SELECT name, route FROM permissions WHERE route IS NOT NULL AND route != ''";
    // Retorna: ['admin_panel' => '/admin/dashboard.php', ...]
}
```

### 2. Filtrado por Permisos
```php
function getVisibleMenuItems($menu_config, $user_permissions) {
    $permission_routes = getPermissionRoutes();  // ← Obtiene rutas de BD
    
    foreach ($menu_config as $section) {
        foreach ($section['items'] as $item) {
            if (in_array($item['permission'], $user_permissions)) {
                // Obtener ruta desde BD
                $item['url'] = $permission_routes[$item['permission']] ?? '#';
            }
        }
    }
}
```

### 3. Generación de URLs
```php
function buildUrl($relative_path) {
    // Convierte rutas relativas en URLs completas
    // Ejemplo: '/admin/dashboard.php' → 'https://tudominio.com/admin/dashboard.php'
}
```

## Ventajas del Sistema

### ✅ Centralizado en Base de Datos
- **Rutas en BD**: Todas las rutas se almacenan en `permissions.route`
- **Gestión dinámica**: Cambiar rutas sin tocar código
- **Consistencia**: Una sola fuente de verdad para rutas

### ✅ Separación de Responsabilidades
- **Configuración**: Estructura del menú en `menu_config.php`
- **Rutas**: URLs en tabla `permissions`
- **Permisos**: Control de acceso en `role_permissions`

### ✅ Flexibilidad Total
- **Agregar páginas**: Solo actualizar BD
- **Cambiar rutas**: Solo actualizar BD
- **Reorganizar menú**: Solo actualizar configuración

## Cómo Agregar Nuevas Páginas

### 1. Crear el Archivo PHP
```php
// admin/nueva_pagina.php
<?php
// Tu código aquí
?>
```

### 2. Agregar Permiso en BD
```sql
INSERT INTO permissions (name, description, display_name, route) 
VALUES ('nueva_funcionalidad', 'Descripción del permiso', 'Nueva Funcionalidad', '/admin/nueva_pagina.php');
```

### 3. Asignar Permiso a Roles
```sql
INSERT INTO role_permissions (role_id, permission_id) 
SELECT r.id, p.id 
FROM roles r, permissions p 
WHERE r.name = 'superadmin' AND p.name = 'nueva_funcionalidad';
```

### 4. Agregar al Menú (Opcional)
```php
// En config/menu_config.php
'nueva_funcionalidad' => [
    'title' => 'Nueva Funcionalidad',
    'icon' => 'fas fa-star',
    'permission' => 'nueva_funcionalidad',
    'description' => 'Descripción del elemento'
]
```

## Gestión de Rutas

### Verificar Rutas Existentes
```sql
SELECT name, route, description 
FROM permissions 
WHERE route IS NOT NULL AND route != ''
ORDER BY name;
```

### Actualizar Rutas
```sql
UPDATE permissions 
SET route = '/nueva/ruta.php' 
WHERE name = 'nombre_permiso';
```

### Agregar Rutas Faltantes
```sql
-- Script automático: db/update_missing_routes.sql
UPDATE permissions SET route = '/admin/dashboard.php' 
WHERE name = 'admin_panel' AND (route IS NULL OR route = '');
```

## Herramientas de Diagnóstico

### 1. Test Completo: `test_menu_system.php`
- Verifica configuración del menú
- Muestra rutas desde BD
- Analiza permisos del usuario
- Identifica rutas faltantes
- Simula menú completo

### 2. Script de Actualización: `db/update_missing_routes.sql`
- Actualiza rutas faltantes automáticamente
- Muestra estado de todas las rutas
- Verifica consistencia

### 3. Funciones Helper
```php
getPermissionRoutes()      // Obtiene todas las rutas
getPermissionInfo($name)   // Información de un permiso
getAllPermissionsWithRoutes() // Todos los permisos con rutas
```

## Ejemplos de Uso

### Menú Completo
```php
// El menú se genera automáticamente
$visible_menu = getVisibleMenuItems($menu_config, $user_permissions);

foreach ($visible_menu as $section) {
    foreach ($section['items'] as $item) {
        echo "<a href='" . buildUrl($item['url']) . "'>";
        echo htmlspecialchars($item['title']);
        echo "</a>";
    }
}
```

### Verificar Ruta de Permiso
```php
$permission_info = getPermissionInfo('admin_panel');
if ($permission_info) {
    echo "Ruta: " . $permission_info['route'];
    echo "Descripción: " . $permission_info['description'];
}
```

## Migración desde Sistema Anterior

### Antes (Hardcodeado)
```php
<?php if (in_array('admin_panel', $permisos_array)): ?>
    <a href="admin/dashboard.php" class="menu-item">
        <i class="fas fa-tachometer-alt"></i>
        Dashboard
    </a>
<?php endif; ?>
```

### Después (Dinámico desde BD)
```php
<?php foreach ($visible_menu as $section): ?>
    <?php foreach ($section['items'] as $item): ?>
        <a href="<?php echo buildUrl($item['url']); ?>" class="menu-item">
            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
            <?php echo htmlspecialchars($item['title']); ?>
        </a>
    <?php endforeach; ?>
<?php endforeach; ?>
```

## Mantenimiento

### Agregar Nueva Página
1. Crear archivo PHP
2. Agregar entrada en tabla `permissions` con ruta
3. Asignar permisos a roles
4. Opcional: agregar al menú en `menu_config.php`

### Cambiar Rutas
1. Actualizar campo `route` en tabla `permissions`
2. Los cambios se reflejan inmediatamente
3. No es necesario tocar código

### Cambiar Permisos
1. Modificar asignaciones en `role_permissions`
2. El menú se actualiza automáticamente
3. Verificar con `test_menu_system.php`

## Troubleshooting

### Menú Vacío
- Verificar permisos del usuario
- Ejecutar `test_menu_system.php`
- Revisar tabla `role_permissions`

### Rutas No Funcionan
- Verificar campo `route` en tabla `permissions`
- Ejecutar `db/update_missing_routes.sql`
- Revisar función `buildUrl()`

### Permisos No Aplican
- Verificar tabla `company_users`
- Revisar función `obtenerPermisosUsuario()`
- Comprobar roles asignados 