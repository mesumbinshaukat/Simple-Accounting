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
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var isOpen = sidebar.classList.toggle('open');
        document.body.classList.toggle('sidebar-open', isOpen);
      });
      // Close sidebar on outside click (mobile)
      document.addEventListener('click', function(ev){
        if (document.body.classList.contains('sidebar-open')){
          var withinSidebar = sidebar.contains(ev.target);
          var onButton = btn.contains(ev.target);
          if (!withinSidebar && !onButton){
            sidebar.classList.remove('open');
            document.body.classList.remove('sidebar-open');
          }
        }
      });
    }

    // Deposit toggle + async account search
    var toggle = document.getElementById('depositToggle');
    var wrap = document.getElementById('depositWrap');
    var search = document.getElementById('accountSearch');
    var list = document.getElementById('accountSearchList');
    var hiddenInput = document.getElementById('transferTo');

    if (toggle && wrap && search && list && hiddenInput) {
      function doSearch(){
        var q = search.value.trim();
        var exclude = search.getAttribute('data-exclude-id') || '';
        fetch('/simple_accounting/accounts_search.php?q=' + encodeURIComponent(q) + '&exclude=' + encodeURIComponent(exclude), {
          credentials: 'same-origin'
        }).then(function(r){ return r.json(); })
          .then(function(data){ renderMenu(data); })
          .catch(function(){ renderMenu([]); });
      }

      toggle.addEventListener('change', function(){
        wrap.style.display = toggle.checked ? 'block' : 'none';
        if (!toggle.checked) {
          hiddenInput.value = '';
          list.innerHTML = '';
          list.style.display = 'none';
        } else {
          // show initial list on open with empty query
          doSearch();
        }
      });

      var debounceTimer = null;
      function renderMenu(items){
        if (!items || items.length === 0) {
          list.innerHTML = '';
          list.style.display = 'none';
          return;
        }
        var menu = document.createElement('div');
        menu.className = 'dropdown-menu';
        items.forEach(function(it){
          var di = document.createElement('div');
          di.className = 'dropdown-item';
          di.textContent = it.name;
          di.addEventListener('click', function(){
            hiddenInput.value = it.id;
            search.value = it.name;
            list.innerHTML = '';
            list.style.display = 'none';
          });
          menu.appendChild(di);
        });
        list.innerHTML = '';
        list.appendChild(menu);
        list.style.display = 'block';
      }

      search.addEventListener('input', function(){
        hiddenInput.value = '';
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSearch, 200);
      });

      search.addEventListener('focus', function(){
        // Always fetch on focus to show initial accounts
        doSearch();
      });

      document.addEventListener('click', function(ev){
        if (!wrap.contains(ev.target)) {
          list.innerHTML = '';
          list.style.display = 'none';
        }
      });
    }
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
