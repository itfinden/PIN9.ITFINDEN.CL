// ==================== SISTEMA DE CAMBIO DE TEMAS ====================

class ThemeSwitcher {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        // Aplicar tema al cargar
        this.applyTheme();
        
        // Configurar event listeners
        this.setupEventListeners();
        
        // Aplicar tema al body
        document.body.setAttribute('data-theme', this.theme);
    }

    setupEventListeners() {
        // Buscar todos los theme switchers
        const themeSwitchers = document.querySelectorAll('.theme-switcher input');
        
        themeSwitchers.forEach(switcher => {
            switcher.checked = this.theme === 'dark';
            switcher.addEventListener('change', (e) => {
                this.theme = e.target.checked ? 'dark' : 'light';
                this.applyTheme();
                this.saveTheme();
            });
        });

        // Botones de tema directo (si existen)
        const lightButtons = document.querySelectorAll('.theme-light');
        const darkButtons = document.querySelectorAll('.theme-dark');
        
        lightButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.theme = 'light';
                this.applyTheme();
                this.saveTheme();
                this.updateSwitchers();
            });
        });
        
        darkButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                this.theme = 'dark';
                this.applyTheme();
                this.saveTheme();
                this.updateSwitchers();
            });
        });
    }

    applyTheme() {
        // Aplicar tema al body
        document.body.setAttribute('data-theme', this.theme);
        
        // Actualizar meta theme-color para móviles
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.setAttribute('content', this.theme === 'dark' ? '#1a1a1a' : '#ffffff');
        }
        
        // Aplicar clase al html para compatibilidad
        document.documentElement.setAttribute('data-theme', this.theme);
        
        // Notificar a otros componentes
        this.notifyThemeChange();
    }

    updateSwitchers() {
        const themeSwitchers = document.querySelectorAll('.theme-switcher input');
        themeSwitchers.forEach(switcher => {
            switcher.checked = this.theme === 'dark';
        });
    }

    saveTheme() {
        localStorage.setItem('theme', this.theme);
    }

    notifyThemeChange() {
        // Disparar evento personalizado para otros componentes
        const event = new CustomEvent('themeChanged', {
            detail: { theme: this.theme }
        });
        document.dispatchEvent(event);
    }

    getCurrentTheme() {
        return this.theme;
    }

    toggleTheme() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        this.applyTheme();
        this.saveTheme();
        this.updateSwitchers();
    }
}

// ==================== FUNCIONES DE UTILIDAD ====================

// Función para obtener el tema actual
function getCurrentTheme() {
    return localStorage.getItem('theme') || 'light';
}

// Función para cambiar tema
function changeTheme(theme) {
    const switcher = new ThemeSwitcher();
    switcher.theme = theme;
    switcher.applyTheme();
    switcher.saveTheme();
    switcher.updateSwitchers();
}

// Función para alternar tema
function toggleTheme() {
    const currentTheme = getCurrentTheme();
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    changeTheme(newTheme);
}

// ==================== INICIALIZACIÓN ====================

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    const themeSwitcher = new ThemeSwitcher();
    
    // Hacer disponible globalmente
    window.themeSwitcher = themeSwitcher;
    window.getCurrentTheme = getCurrentTheme;
    window.changeTheme = changeTheme;
    window.toggleTheme = toggleTheme;
});

// ==================== COMPATIBILIDAD CON FULLCALENDAR ====================

// Función para actualizar FullCalendar cuando cambie el tema
function updateFullCalendarTheme() {
    const event = new CustomEvent('themeChanged', {
        detail: { theme: getCurrentTheme() }
    });
    document.dispatchEvent(event);
}

// Escuchar cambios de tema para actualizar FullCalendar
document.addEventListener('themeChanged', (e) => {
    // Si FullCalendar está cargado, actualizar su tema
    if (typeof FullCalendar !== 'undefined') {
        const calendar = document.querySelector('#calendar');
        if (calendar) {
            const fcInstance = calendar.fullCalendar;
            if (fcInstance) {
                // Forzar re-render del calendario
                fcInstance.render();
            }
        }
    }
});

// ==================== DETECCIÓN DE PREFERENCIA DEL SISTEMA ====================

// Detectar preferencia del sistema operativo
function detectSystemTheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        return 'dark';
    }
    return 'light';
}

// Aplicar tema del sistema si no hay preferencia guardada
if (!localStorage.getItem('theme')) {
    const systemTheme = detectSystemTheme();
    if (systemTheme !== 'light') {
        localStorage.setItem('theme', systemTheme);
    }
}

// Escuchar cambios en la preferencia del sistema
if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        // Solo aplicar si no hay tema guardado
        if (!localStorage.getItem('theme')) {
            const newTheme = e.matches ? 'dark' : 'light';
            changeTheme(newTheme);
        }
    });
} 