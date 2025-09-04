<?php
// Slidepanel Menu Original - Restaurado
// Este archivo se incluye desde footer.php cuando $_SESSION['enable_slidepanel'] está activo
?>

<!-- Botón flotante para abrir el slidepanel -->
<button id="sp-fab" class="sp-fab" aria-label="Abrir menú">
  <i class="fas fa-bars"></i>
</button>

<!-- Panel lateral del slidepanel -->
<aside id="sp-panel" class="sp-panel" aria-hidden="true">
  <div class="sp-header">
    <h6 class="sp-title mb-0"><i class="fas fa-compass mr-1"></i> Menú</h6>
    <button id="sp-close" class="sp-close" aria-label="Cerrar"><i class="fas fa-times"></i></button>
  </div>
  <div class="sp-content">
    <div class="sp-section-title">Navegación</div>
    <a class="sp-item" href="/content.php"><i class="fas fa-home"></i> Inicio</a>
    <a class="sp-item" href="/calendar.php"><i class="fas fa-calendar-alt"></i> Calendario</a>
    <a class="sp-item" href="/evento_dashboard.php"><i class="fas fa-glass-cheers"></i> Eventos</a>
    <a class="sp-item" href="/projects.php"><i class="fas fa-project-diagram"></i> Proyectos</a>

    <div class="sp-section-title">Cuenta</div>
    <a class="sp-item" href="/content.php"><i class="fas fa-user"></i> Perfil</a>
    <a class="sp-item" href="/logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a>

    <div class="sp-section-title">Acciones rápidas</div>
    <a class="sp-item" href="/evento_nuevo.php"><i class="fas fa-plus"></i> Nuevo Evento</a>
    <a class="sp-item" href="/today.php"><i class="fas fa-bolt"></i> Hoy</a>
  </div>
</aside>

<!-- Estilos CSS para el slidepanel -->
<style>
/* Botón flotante */
.sp-fab {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color, #007bff) 0%, var(--primary-hover, #0056b3) 100%);
  color: white;
  border: none;
  box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
  cursor: pointer;
  z-index: 1000;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.sp-fab:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 25px rgba(0, 123, 255, 0.4);
}

/* Panel lateral */
.sp-panel {
  position: fixed;
  top: 0;
  right: -350px;
  width: 350px;
  height: 100vh;
  background: var(--bg-primary, #ffffff);
  box-shadow: -5px 0 15px rgba(0,0,0,0.3);
  z-index: 1001;
  transition: right 0.3s ease-out;
  overflow-y: auto;
  color: var(--text-primary, #212529);
}

.sp-panel.sp-open {
  right: 0;
}

/* Header del panel */
.sp-header {
  background: linear-gradient(135deg, var(--primary-color, #007bff) 0%, var(--primary-hover, #0056b3) 100%);
  color: white;
  padding: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--border-color, #dee2e6);
}

.sp-title {
  font-size: 1.2rem;
  font-weight: 600;
  margin: 0;
}

.sp-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.2s ease-out;
}

.sp-close:hover {
  background-color: rgba(255,255,255,0.2);
}

/* Contenido del panel */
.sp-content {
  padding: 20px;
  background: var(--bg-primary, #ffffff);
  color: var(--text-primary, #212529);
}

.sp-section-title {
  font-weight: 600;
  color: var(--text-primary, #333);
  margin: 20px 0 10px 0;
  padding-bottom: 8px;
  border-bottom: 2px solid var(--primary-color, #007bff);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.sp-section-title:first-child {
  margin-top: 0;
}

.sp-item {
  display: flex;
  align-items: center;
  padding: 12px 15px;
  margin: 5px 0;
  text-decoration: none;
  color: var(--text-primary, #333);
  border-radius: 8px;
  transition: all 0.3s ease;
  border-left: 3px solid transparent;
}

.sp-item:hover {
  background: var(--bg-secondary, #f8f9fa);
  color: var(--text-primary, #333);
  text-decoration: none;
  border-left-color: var(--primary-color, #007bff);
  transform: translateX(5px);
}

.sp-item i {
  margin-right: 12px;
  width: 20px;
  text-align: center;
  color: var(--primary-color, #007bff);
}

/* Overlay para cerrar el panel */
.panel-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.2s ease-out, visibility 0.2s ease-out;
}

.panel-overlay.active {
  opacity: 1;
  visibility: visible;
}

/* Responsive */
@media (max-width: 768px) {
  .sp-panel {
    width: 100%;
    right: -100%;
  }
  
  .sp-fab {
    bottom: 20px;
    right: 20px;
    width: 50px;
    height: 50px;
    font-size: 1rem;
  }
  
  .sp-header {
    padding: 15px;
  }
  
  .sp-content {
    padding: 15px;
  }
  
  .sp-item {
    padding: 10px 12px;
  }
}

/* Dark mode */
[data-theme="dark"] .sp-panel {
  background: var(--bg-primary-dark, #1a1a1a);
  color: var(--text-primary-dark, #ffffff);
}

[data-theme="dark"] .sp-content {
  background: var(--bg-primary-dark, #1a1a1a);
  color: var(--text-primary-dark, #ffffff);
}

[data-theme="dark"] .sp-item:hover {
  background: var(--bg-secondary-dark, #2d2d2d);
}
</style>

<!-- JavaScript para el slidepanel -->
<script>
(function(){
  function qs(sel){return document.querySelector(sel)}
  
  // Esperar a que el DOM esté completamente cargado
  function initSlidepanel() {
    var btn = qs('#sp-fab');
    var panel = qs('#sp-panel');
    
    console.log('Slidepanel init - btn:', !!btn, 'panel:', !!panel);
    
    if(!btn || !panel) {
      console.log('Slidepanel: Elementos no encontrados');
      return;
    }
    
    function toggle(){ 
      console.log('Toggle slidepanel - antes:', panel.classList.contains('sp-open'));
      panel.classList.toggle('sp-open');
      console.log('Toggle slidepanel - después:', panel.classList.contains('sp-open'));
    }
    
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      console.log('Botón slidepanel clickeado');
      toggle();
    });
    
    var closeBtn = qs('#sp-close');
    if(closeBtn){ 
      closeBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        console.log('Botón cerrar clickeado');
        toggle();
      });
    }
    
    // Cerrar con Escape
    document.addEventListener('keydown', function(e){ 
      if(e.key === 'Escape' && panel.classList.contains('sp-open')) {
        console.log('Cerrando slidepanel con Escape');
        toggle(); 
      }
    });
    
    // Cerrar al hacer clic fuera del panel
    document.addEventListener('click', function(e) {
      if(panel.classList.contains('sp-open') && 
         !panel.contains(e.target) && 
         !btn.contains(e.target)) {
        console.log('Cerrando slidepanel - clic fuera');
        toggle();
      }
    });
    
    console.log('Slidepanel inicializado correctamente');
  }
  
  // Inicializar cuando el DOM esté listo
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSlidepanel);
  } else {
    initSlidepanel();
  }
})();
</script>