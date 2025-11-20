(function(){
  if (window.__ECONCAL_BOOTED__) return; window.__ECONCAL_BOOTED__ = true;
  function isDark(){ var c=document.documentElement.classList; return c.contains('dark-blue')||c.contains('all-black')||c.contains('dark'); }
  function build(state){
    var base='https://sslecal2.investing.com?columns=exc_flags,exc_currency,exc_importance,exc_actual,exc_forecast,exc_previous&features=datepicker,timezone&timeZone=12&timeFilter=timeRemain';
    var u=base+"&calType="+encodeURIComponent(state.calType)+"&importance="+encodeURIComponent(state.importance)+"&lang="+encodeURIComponent(state.lang);
    try{ u += '&parentUrl=' + encodeURIComponent(window.location.href); }catch(_){ }
    if(state.countries){ u += '&countries=' + encodeURIComponent(state.countries); }
    return u;
  }
  function init(root){
    if(!root) return;
    var defCountries=root.getAttribute('data-countries')||'';
    var defCalType=root.getAttribute('data-caltype')||'week';
    var defImp=root.getAttribute('data-importance')||'2,3';
    var lang=root.getAttribute('data-lang')||'12';
    var iframe=root.querySelector('.econcal-iframe');
    var dd=root.querySelector('.econcal-dropdown');
    var btn=root.querySelector('.econcal-filter-btn');
    var filterWrap=root.querySelector('.econcal-filter');
    var header=root.querySelector('.econcal-header');
    var parentCard=root.closest('.card');
    var host=parentCard ? parentCard.querySelector('.econcal-host-header-right') : null;
    var state={countries:defCountries,calType:defCalType,importance:defImp,lang:lang};
    var loadingEl=root.querySelector('.econcal-loading');

    // Botão de alternância de cor do iframe (aparece só em tema escuro)
    var colorBtn = document.createElement('button');
    colorBtn.type = 'button';
    colorBtn.className = 'econcal-color-btn';
    colorBtn.title = 'Alternar cor do calendário';
    colorBtn.textContent = '🌓';
    colorBtn.style.display = 'none';

    if(host && filterWrap){
      try{
        host.innerHTML='';
        // inserir o botão de cor à esquerda do botão de filtros
        host.appendChild(colorBtn);
        host.appendChild(filterWrap);
        if(header){ header.style.display='none'; }
        root.classList.add('econcal-hosted');
      }catch(_){}
    } else {
      if(header){ header.style.display='flex'; }
    }

    // Garantir que o dropdown nunca apareça fora do modal
    if (dd) {
      try {
        dd.classList.remove('econcal-dd-portal');
        dd.style.display = 'none';
        dd.style.position = 'static';
        dd.style.left = dd.style.top = dd.style.visibility = dd.style.zIndex = '';
        dd.style.maxHeight = dd.style.overflow = '';
      } catch(_){}
    }

    // Modal Bootstrap para filtros (hosted)
    var modalEl = document.getElementById('econcalFilterModal');
    var modalBody = document.getElementById('econcal-modal-body');
    var bsModal = null;
    try { if (modalEl && window.bootstrap && window.bootstrap.Modal) { bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl); } } catch(_){ }

    // Pré-mover dropdown para dentro do modal para evitar piscadas/posição incorreta
    if (dd && modalBody) {
      try {
        dd.classList.remove('econcal-dd-portal');
        dd.classList.remove('show');
        dd.style.display = 'none';
        dd.style.position = 'static';
        dd.style.left = dd.style.top = dd.style.visibility = dd.style.zIndex = '';
        dd.style.maxHeight = dd.style.overflow = '';
        if (dd.parentNode !== modalBody) modalBody.appendChild(dd);
      } catch(_){}
    }

    function absorbLegacyPortals(){
      if (!modalBody) return;
      try {
        document.querySelectorAll('.econcal-dropdown.econcal-dd-portal').forEach(function(el){
          try {
            el.classList.remove('econcal-dd-portal');
            el.classList.remove('show');
            el.style.display = 'none';
            el.style.position = 'static';
            el.style.left = el.style.top = el.style.right = el.style.bottom = el.style.visibility = el.style.zIndex = '';
            el.style.maxHeight = el.style.overflow = '';
            if (el.parentNode !== modalBody) modalBody.appendChild(el);
          } catch(_){}
        });
      } catch(_){}
    }

    absorbLegacyPortals();

    function updateColorToggleVisibility(){
      var dark = isDark();
      if (dark) {
        colorBtn.style.display = 'inline-flex';
        if (!root.classList.contains('force-dark')) root.classList.add('force-dark');
      } else {
        colorBtn.style.display = 'none';
        root.classList.remove('force-dark');
      }
    }

    colorBtn.addEventListener('click', function(){
      root.classList.toggle('force-dark');
    });

    updateColorToggleVisibility();

    var lastUrl='';
    function showLoading(){ if(loadingEl){ loadingEl.classList.add('active'); } }
    function hideLoading(){ if(loadingEl){ loadingEl.classList.remove('active'); } }
    function apply(showLoad){ if(!iframe) return; var url=build(state); if(url===lastUrl) return; lastUrl=url; if(showLoad!==false) showLoading(); iframe.src=url; }

    if(iframe){ iframe.addEventListener('load', function(){ hideLoading(); }); }

    if(btn){ btn.addEventListener('click', function(e){
      e.preventDefault(); e.stopPropagation();
      if (!bsModal || !modalBody || !dd) return;
      // mover conteúdo para o modal
      try {
        absorbLegacyPortals();
        modalBody.innerHTML = '';
        dd.classList.add('show');
        dd.style.display = 'flex';
        dd.style.position = 'static';
        dd.style.left = dd.style.top = dd.style.visibility = dd.style.zIndex = '';
        dd.style.maxHeight = dd.style.overflow = '';
        modalBody.appendChild(dd);
      } catch(_){ }
      try { bsModal.show(); } catch(_){ }
    }); }

    if(dd){ dd.addEventListener('click', function(e){ e.stopPropagation(); var t=e.target; if(t && t.tagName==='BUTTON'){
      var k=t.getAttribute('data-key'); var v=t.getAttribute('data-val'); if(k){ state[k]=v; apply(true); }
      try { if (bsModal) bsModal.hide(); } catch(_){ }
    }}); }


    var mo=new MutationObserver(function(m){ for(var i=0;i<m.length;i++){ if(m[i].attributeName==='class'){ updateColorToggleVisibility(); break; } } });
    try{ mo.observe(document.documentElement,{attributes:true}); }catch(_){ }

    apply(true);
  }
  function boot(){ try{ document.querySelectorAll('.econcal-root').forEach(init); }catch(_){ } }
  if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
