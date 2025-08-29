# Sistema de Temas - Pin9

## Descripción

El sistema de temas de Pin9 permite a los usuarios cambiar entre un tema claro (día) y un tema oscuro (noche) para mejorar la experiencia de usuario y reducir la fatiga visual.

## Características

### ✅ Implementado
- [x] Tema claro (light) y oscuro (dark)
- [x] Persistencia del tema elegido en localStorage
- [x] Detección automática de preferencia del sistema operativo
- [x] Transiciones suaves entre temas
- [x] Selector de tema en el navbar
- [x] Compatibilidad con FullCalendar
- [x] CSS variables para fácil personalización
- [x] Responsive design

### 🎨 Temas Disponibles

#### Tema Claro (Light)
- **Colores principales**: Azul (#007bff)
- **Fondo**: Blanco (#ffffff)
- **Texto**: Gris oscuro (#212529)
- **Bordes**: Gris claro (#dee2e6)

#### Tema Oscuro (Dark)
- **Colores principales**: Azul claro (#4dabf7)
- **Fondo**: Gris muy oscuro (#1a1a1a)
- **Texto**: Blanco (#ffffff)
- **Bordes**: Gris medio (#495057)

## Uso

### Para Usuarios

1. **Cambiar tema**: Usar el selector en la barra de navegación
2. **Persistencia**: El tema se guarda automáticamente
3. **Detección automática**: Se detecta la preferencia del sistema operativo

### Para Desarrolladores

#### 1. Incluir el sistema en una página

```php
<?php
require_once 'theme_handler.php';
?>
<!DOCTYPE html>
<html lang="en" <?php echo applyThemeToHTML(); ?>>
<head>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-switcher.js"></script>
</head>
```

#### 2. Agregar el selector de tema

```html
<div class="theme-switcher-container">
    <label class="theme-switcher">
        <input type="checkbox" id="theme-toggle">
        <span class="theme-slider">
            <i class="fas fa-sun theme-icon sun"></i>
            <i class="fas fa-moon theme-icon moon"></i>
        </span>
    </label>
</div>
```

#### 3. Usar CSS variables

```css
.my-component {
    background-color: var(--bg-card);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}
```

#### 4. JavaScript para cambiar tema

```javascript
// Cambiar a tema oscuro
changeTheme('dark');

// Cambiar a tema claro
changeTheme('light');

// Alternar tema actual
toggleTheme();

// Obtener tema actual
const currentTheme = getCurrentTheme();
```

## Estructura de Archivos

```
├── css/
│   └── style.css              # Estilos principales con CSS variables
├── js/
│   └── theme-switcher.js      # JavaScript para manejo de temas
├── config/
│   └── theme_config.php       # Configuración de temas
├── theme_handler.php          # Manejo de temas en PHP
└── views/partials/
    └── modern_navbar.php      # Navbar con selector de tema
```

## CSS Variables Disponibles

### Colores de Fondo
- `--bg-primary`: Fondo principal
- `--bg-secondary`: Fondo secundario
- `--bg-card`: Fondo de tarjetas
- `--bg-navbar`: Fondo del navbar
- `--bg-footer`: Fondo del footer

### Colores de Texto
- `--text-primary`: Texto principal
- `--text-secondary`: Texto secundario
- `--text-muted`: Texto atenuado
- `--text-light`: Texto claro
- `--text-dark`: Texto oscuro

### Colores de Componentes
- `--primary-color`: Color primario
- `--secondary-color`: Color secundario
- `--success-color`: Color de éxito
- `--danger-color`: Color de peligro
- `--warning-color`: Color de advertencia
- `--info-color`: Color de información

### Bordes y Sombras
- `--border-color`: Color de bordes
- `--shadow-light`: Sombra ligera
- `--shadow-medium`: Sombra media
- `--shadow-dark`: Sombra oscura

### Transiciones
- `--transition-speed`: Velocidad de transición
- `--transition-ease`: Función de easing

## Personalización

### Agregar un nuevo tema

1. **Editar `config/theme_config.php`**:
```php
$THEME_CONFIG['custom'] = [
    'name' => 'Tema Personalizado',
    'icon' => 'fas fa-star',
    'description' => 'Descripción del tema',
    'default' => false
];

$THEME_COLORS['custom'] = [
    'primary' => '#your-color',
    // ... más colores
];
```

2. **Agregar estilos en `css/style.css`**:
```css
[data-theme="custom"] {
    --primary-color: #your-color;
    /* ... más variables */
}
```

### Modificar colores existentes

Editar las variables en `css/style.css` dentro de `:root` y `[data-theme="dark"]`.

## Compatibilidad

### Navegadores Soportados
- ✅ Chrome 49+
- ✅ Firefox 31+
- ✅ Safari 9.1+
- ✅ Edge 12+

### Componentes Compatibles
- ✅ Bootstrap 4
- ✅ FullCalendar
- ✅ Font Awesome
- ✅ jQuery

## Troubleshooting

### El tema no se aplica
1. Verificar que `theme-switcher.js` esté cargado
2. Verificar que `data-theme` esté en el elemento `<html>`
3. Verificar que las CSS variables estén definidas

### El tema no persiste
1. Verificar que localStorage esté habilitado
2. Verificar que no haya errores en la consola
3. Verificar que el JavaScript se ejecute correctamente

### Problemas con FullCalendar
1. Verificar que el evento `themeChanged` se dispare
2. Forzar re-render del calendario después del cambio
3. Verificar que los estilos del calendario usen CSS variables

## Contribuir

Para contribuir al sistema de temas:

1. Crear una rama para tu feature
2. Implementar los cambios
3. Probar en ambos temas
4. Actualizar la documentación
5. Crear un pull request

## Licencia

Este sistema de temas es parte de Pin9 y está bajo la misma licencia del proyecto. 