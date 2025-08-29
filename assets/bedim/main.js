// Minimal JS to toggle Bedim-style navbar
(function(){
  var toggle = document.querySelector('.bdm-nav__toggle');
  var menu = document.querySelector('#bdm-menu');
  if(!toggle || !menu) return;
  toggle.addEventListener('click', function(){
    menu.classList.toggle('bdm-show-menu');
  });
})();


