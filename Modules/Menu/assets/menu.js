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


