  </div>
  <div aria-live="polite" aria-atomic="true" class="position-relative">
    <div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>
  </div>
  <div id="loading-backdrop" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(255,255,255,.6); z-index: 1055;">
    <div class="position-absolute top-50 start-50 translate-middle text-center">
      <div class="spinner-border text-primary" role="status"></div>
      <div class="mt-2">Carregando...</div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
  <script>
    // Ping de presenÃ§a
    setInterval(function(){
      fetch('<?= App\Lib\url('monitor.ping') ?>').catch(()=>{});
    }, 30000);

    // Mostrar overlay de carregando em cliques
    $(document).on('click', 'a.app-link, button.app-action', function(){
      $('#loading-backdrop').removeClass('d-none');
      setTimeout(()=>$('#loading-backdrop').addClass('d-none'), 5000);
    });

    // ConfirmaÃ§Ãµes padrÃ£o
    $(document).on('click', '[data-confirm]', function(e){
      const msg = $(this).data('confirm') || 'Confirmar aÃ§Ã£o?';
      if(!confirm(msg)) { e.preventDefault(); }
    });

    // Datatables padrÃ£o
    $(function(){
      $('.datatable').DataTable({
        language: {
          url: 'https://cdn.datatables.net/plug-ins/1.13.8/i18n/pt-BR.json'
        }
      });
    });

    // Dropdown com submenu (Bootstrap 5 nativamente nÃ£o abre em cascata)
    // Clique no item de submenu alterna o menu Ã  direita
    $(document).on('click', '.dropdown-submenu > a[data-bs-toggle="submenu"]', function(e){
      e.preventDefault();
      e.stopPropagation();
      const $submenu = $(this).next('.dropdown-menu');
      // Fecha outros submenus abertos no mesmo nÃ­vel
      $(this).closest('.dropdown-menu').find('.dropdown-menu.show').not($submenu).removeClass('show');
      $submenu.toggleClass('show');
    });

    // Ao fechar o dropdown pai, fecha todos os submenus
    $('.dropdown').on('hide.bs.dropdown', function(){
      $(this).find('.dropdown-menu.show').removeClass('show');
    });
    // Menubar (desktop): abre dropdown ao passar o mouse
    if (window.matchMedia('(hover: hover)').matches) {
      const $menus = $('.navbar .nav-item.dropdown');
      $menus.each(function(){
        const $item = $(this);
        const $toggle = $item.find('> a[data-bs-toggle="dropdown"]');
        if ($toggle.length) {
          const dd = new bootstrap.Dropdown($toggle[0], {autoClose: true});
          let hideTimer;
          $item.on('mouseenter', function(){ clearTimeout(hideTimer); dd.show(); });
          $item.on('mouseleave', function(){ hideTimer = setTimeout(()=>dd.hide(), 150); });
        }
      });
    }
  </script>
  <script>
    // AtualizaÃ§Ã£o transparente do menu a cada 30s
    (function(){
      <?php
        // Permite configurar o intervalo via config_sistema (processo 'menu', chave 'refresh_menu')
        require_once __DIR__ . '/../../modelos/Config.php';
        $refreshMenu = (int)(\App\Modelos\Config\obter('menu', 'refresh_menu') ?? 30);
        if ($refreshMenu < 5) { $refreshMenu = 5; }
        if ($refreshMenu > 3600) { $refreshMenu = 3600; }
      ?>
      const target = document.getElementById('nav-menus');
      if (!target) return;
      window.APP_BASE_PATH = window.APP_BASE_PATH || '<?= App\Config\base_path() ?>';
      let lastSig = '';
      const MENU_REFRESH_MS = <?= (int)$refreshMenu * 1000 ?>;
      function buildUrl(acao){ return (window.APP_BASE_PATH||'') + '/index.php?acao=' + encodeURIComponent(acao); }
      function icon(i){ return (i && String(i).trim()) ? i : 'fa-bars'; }
      function escapeHtml(s){ return String(s||'').replace(/[&<>\"]/g, c=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"})[c]); }
      function rebindDropdownHelpers(){
        // Fecha submenus ao fechar o dropdown pai
        $('.navbar .dropdown').off('hide.bs.dropdown.__submenufix').on('hide.bs.dropdown.__submenufix', function(){
          $(this).find('.dropdown-menu.show').removeClass('show');
        });
        // Hover para abrir (desktop)
        if (window.matchMedia('(hover: hover)').matches) {
          const $menus = $('.navbar .nav-item.dropdown');
          $menus.each(function(){
            const $item = $(this);
            const $toggle = $item.find('> a[data-bs-toggle="dropdown"]');
            if ($toggle.length) {
              const dd = new bootstrap.Dropdown($toggle[0], {autoClose: true});
              let hideTimer;
              $item.off('.hoverdd');
              $item.on('mouseenter.hoverdd', function(){ clearTimeout(hideTimer); dd.show(); });
              $item.on('mouseleave.hoverdd', function(){ hideTimer = setTimeout(()=>dd.hide(), 150); });
            }
          });
        }
      }
      function render(menus){
        const ul = document.createElement('ul');
        ul.className = 'navbar-nav me-auto mb-2 mb-lg-0';
        menus.forEach(m => {
          const li = document.createElement('li'); li.className = 'nav-item dropdown';
          const a = document.createElement('a'); a.className = 'nav-link dropdown-toggle'; a.href = '#'; a.setAttribute('role','button'); a.setAttribute('data-bs-toggle','dropdown'); a.setAttribute('aria-expanded','false');
          a.innerHTML = '<i class="fa '+escapeHtml(icon(m.icone))+' me-1"></i>'+escapeHtml(m.nome||'');
          const menu = document.createElement('ul'); menu.className = 'dropdown-menu';
          const itMap = {}; (m.itens||[]).forEach(it=>{ itMap[it.id] = it; });
          if (m.nivel && m.nivel.length){
            m.nivel.forEach(n => {
              if (n.tipo === 'item'){
                const it = itMap[n.id]; if (!it) return; const lii = document.createElement('li'); const ai = document.createElement('a');
                ai.className='dropdown-item app-link'; ai.href = buildUrl(it.rota_acao||''); ai.innerHTML = '<i class="fa '+escapeHtml(icon(it.icone))+' me-1"></i>'+escapeHtml(it.nome||'');
                lii.appendChild(ai); menu.appendChild(lii);
              } else if (n.tipo === 'submenu') {
                const sm = (m.submenus||{})[n.id] || null; if (!sm) return; const lis = document.createElement('li'); lis.className='dropdown-submenu';
                const as = document.createElement('a'); as.href='#'; as.className='dropdown-item dropdown-toggle'; as.setAttribute('data-bs-toggle','submenu'); as.innerHTML = '<i class="fa '+escapeHtml(icon(sm.icone))+' me-1"></i>'+escapeHtml(sm.nome||'');
                const uls = document.createElement('ul'); uls.className='dropdown-menu';
                (sm.itens||[]).forEach(it=>{ const lii=document.createElement('li'); const ai=document.createElement('a'); ai.className='dropdown-item app-link'; ai.href=buildUrl(it.rota_acao||''); ai.innerHTML='<i class="fa '+escapeHtml(icon(it.icone))+' me-1"></i>'+escapeHtml(it.nome||''); lii.appendChild(ai); uls.appendChild(lii); });
                lis.appendChild(as); lis.appendChild(uls); menu.appendChild(lis);
              }
            });
          } else {
            (m.itens||[]).forEach(it=>{ const lii=document.createElement('li'); const ai=document.createElement('a'); ai.className='dropdown-item app-link'; ai.href=buildUrl(it.rota_acao||''); ai.innerHTML='<i class="fa '+escapeHtml(icon(it.icone))+' me-1"></i>'+escapeHtml(it.nome||''); lii.appendChild(ai); menu.appendChild(lii); });
            if (m.submenus && Object.keys(m.submenus).length){
              Object.values(m.submenus).forEach(sm=>{ const lis=document.createElement('li'); lis.className='dropdown-submenu'; const as=document.createElement('a'); as.href='#'; as.className='dropdown-item dropdown-toggle'; as.setAttribute('data-bs-toggle','submenu'); as.innerHTML = '<i class="fa '+escapeHtml(icon(sm.icone))+' me-1"></i>'+escapeHtml(sm.nome||''); const uls=document.createElement('ul'); uls.className='dropdown-menu'; (sm.itens||[]).forEach(it=>{ const lii=document.createElement('li'); const ai=document.createElement('a'); ai.className='dropdown-item app-link'; ai.href=buildUrl(it.rota_acao||''); ai.innerHTML='<i class="fa '+escapeHtml(icon(it.icone))+' me-1"></i>'+escapeHtml(it.nome||''); lii.appendChild(ai); uls.appendChild(lii); }); lis.appendChild(as); lis.appendChild(uls); menu.appendChild(lis); });
            }
          }
          li.appendChild(a); li.appendChild(menu); ul.appendChild(li);
        });
        target.replaceChildren(...ul.childNodes);
        rebindDropdownHelpers();
      }
      async function tick(){
        try{
          const r = await fetch(buildUrl('menu.nav') + '&_ts=' + Date.now(), {credentials:'same-origin', cache:'no-store'});
          const j = await r.json();
          if (!j || !j.ok) return;
          const sig = JSON.stringify(j.menus||[]);
          if (sig !== lastSig){ lastSig = sig; render(j.menus||[]); }
        }catch(e){ /* silencioso */ }
      }
      tick();
      setInterval(tick, MENU_REFRESH_MS);
    })();
</script>
  <script>
    // Segunda verificaÃ§Ã£o: se o ping nÃ£o retornar JSON, redireciona para login (garante logout visual)
    (function(){
      const pingUrl = '<?= App\Lib\url('monitor.ping') ?>';
      const loginBase = '<?= App\Lib\url('autenticacao.login') ?>';
      async function pingCheck(){
        try{
          const r = await fetch(pingUrl, {credentials:'same-origin', cache:'no-store'});
          const ct = (r.headers.get('content-type')||'').toLowerCase();
          if (!r.ok || ct.indexOf('application/json') === -1) {
            const redirected = (r.url && r.url !== pingUrl) ? r.url : loginBase;
            window.location.href = redirected;
          }
        }catch(e){}
      }
      setInterval(pingCheck, 10000);
    })();
  </script>
  <script>
    // Contador regressivo da sessÃ£o (ao lado do nome do usuÃ¡rio)
    (function(){
      const el = document.getElementById('session-remaining');
      if (!el) return;
      const ttl = parseInt(el.getAttribute('data-ttl')||'0', 10);
      const loginTs = parseInt(el.getAttribute('data-login-ts')||'0', 10);
      if (!ttl || !loginTs) { el.style.display='none'; return; }
      function fmt(secs){
        if (secs < 0) secs = 0;
        const h = Math.floor(secs/3600); const m = Math.floor((secs%3600)/60); const s = secs%60;
        const mm = (m<10?'0':'')+m; const ss=(s<10?'0':'')+s;
        return h>0 ? (h+':'+mm+':'+ss) : (mm+':'+ss);
      }
      function tick(){
        const now = Math.floor(Date.now()/1000);
        const left = (loginTs + ttl) - now;
        el.textContent = fmt(left);
        if (left <= 0) {
          try { window.location.href = '<?= App\Lib\url('autenticacao.login') ?>&timeout=1'; } catch(e) {}
        }
      }
      tick();
      setInterval(tick, 1000);
    })();
  </script>
</body>
</html>











