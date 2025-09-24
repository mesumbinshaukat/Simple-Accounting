function addToast(message, type){
  var container = document.getElementById('toast-container');
  if(!container){
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  var t = document.createElement('div');
  t.className = 'toast ' + (type||'info');
  t.textContent = message;
  container.appendChild(t);
  setTimeout(function(){
    t.style.opacity = '0';
    t.style.transform = 'translateY(6px)';
    setTimeout(function(){ t.remove(); }, 300);
  }, 3000);
}

// Sidebar toggle (wait for DOM ready)
(function(){
  function init(){
    var btn = document.getElementById('menuToggle');
    var sidebar = document.getElementById('sidebar');
    if(btn && sidebar){
      btn.addEventListener('click', function(){
        sidebar.classList.toggle('open');
      });
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
