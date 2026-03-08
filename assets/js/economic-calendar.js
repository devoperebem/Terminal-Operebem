(function(){
  function isDark(){ var c=document.documentElement.classList; return c.contains('dark-blue')||c.contains('all-black')||c.contains('dark'); }
  function build(state){
    var base='https://sslecal2.investing.com?columns=exc_flags,exc_currency,exc_importance,exc_actual,exc_forecast,exc_previous&features=datepicker,timezone&timeZone=12&timeFilter=timeRemain';
    var u=base+"&calType="+encodeURIComponent(state.calType)+"&importance="+encodeURIComponent(state.importance)+"&lang="+encodeURIComponent(state.lang);
    try{ u += '&parentUrl=' + encodeURIComponent(window.location.href); }catch(_){ }
    if(state.countries){ u += '&countries=' + encodeURIComponent(state.countries); }
    if(isDark()){ u += '&theme=dark&colorScheme=dark'; }
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

    if(host && filterWrap){
      try{ host.innerHTML=''; host.appendChild(filterWrap); if(header){ header.style.display='none'; } root.classList.add('econcal-hosted'); }catch(_){}
    } else {
      if(header){ header.style.display='flex'; }
    }

    var portalDD=null, portalW=null;
    function ensurePortal(){
      if(!dd) return null; if(portalDD) return portalDD;
      portalDD=dd; try{ document.body.appendChild(portalDD);}catch(_){ }
      portalDD.classList.add('econcal-dd-portal');
      portalDD.style.position='fixed'; portalDD.style.zIndex='2147483647'; portalDD.style.display='none'; portalDD.style.visibility='hidden';
      return portalDD;
    }
    function placePortal(){
      if(!btn||!portalDD) return;
      var r=btn.getBoundingClientRect();
      portalDD.style.visibility='hidden'; portalDD.style.display='flex';
      var width=(portalW&&portalW>0)?portalW:(portalDD.offsetWidth||280);
      var left=Math.max(8, Math.min((r.right - width), window.innerWidth - width - 8));
      var top=Math.min(r.bottom + 8, window.innerHeight - portalDD.offsetHeight - 8);
      portalDD.style.left=left+'px'; portalDD.style.top=top+'px'; portalDD.style.visibility='visible';
    }

    var lastUrl='';
    function showLoading(){ if(loadingEl){ loadingEl.classList.add('active'); } }
    function hideLoading(){ if(loadingEl){ loadingEl.classList.remove('active'); } }
    function apply(showLoad){ if(!iframe) return; var url=build(state); if(url===lastUrl) return; lastUrl=url; if(showLoad!==false) showLoading(); iframe.src=url; }

    if(iframe){ iframe.addEventListener('load', function(){ hideLoading(); }); }

    if(btn){ btn.addEventListener('click', function(e){ e.stopPropagation(); ensurePortal(); if(!portalDD) return; var willOpen=(portalDD.style.display==='none');
      if(willOpen){ root.classList.add('econcal-dd-open'); if(iframe){ iframe.style.pointerEvents='none'; }
        var w=Math.min(Math.max((portalDD.scrollWidth||portalDD.offsetWidth||280),280),420); portalW=w; portalDD.style.width=w+'px';
        placePortal(); if(!root.classList.contains('econcal-hosted')){ window.addEventListener('resize', placePortal, {passive:true}); window.addEventListener('scroll', placePortal, {passive:true}); }
      } else { root.classList.remove('econcal-dd-open'); if(iframe){ iframe.style.pointerEvents=''; } if(!root.classList.contains('econcal-hosted')){ window.removeEventListener('resize', placePortal); window.removeEventListener('scroll', placePortal); } }
      portalDD.style.display=willOpen?'flex':'none';
    }); }

    if(dd){ dd.addEventListener('click', function(e){ e.stopPropagation(); var t=e.target; if(t && t.tagName==='BUTTON'){
      var k=t.getAttribute('data-key'); var v=t.getAttribute('data-val'); if(k){ state[k]=v; apply(true); }
      if(portalDD){ portalDD.style.display='none'; } root.classList.remove('econcal-dd-open'); if(iframe){ iframe.style.pointerEvents=''; }
      if(!root.classList.contains('econcal-hosted')){ window.removeEventListener('resize', placePortal); window.removeEventListener('scroll', placePortal); }
    }}); }

    document.addEventListener('click', function(e){ if(portalDD && btn && !portalDD.contains(e.target) && !btn.contains(e.target)){
      portalDD.style.display='none'; root.classList.remove('econcal-dd-open'); if(iframe){ iframe.style.pointerEvents=''; }
      if(!root.classList.contains('econcal-hosted')){ window.removeEventListener('resize', placePortal); window.removeEventListener('scroll', placePortal); }
    }});

    var mo=new MutationObserver(function(m){ for(var i=0;i<m.length;i++){ if(m[i].attributeName==='class'){ apply(true); break; } } });
    try{ mo.observe(document.documentElement,{attributes:true}); }catch(_){ }

    apply(true);
  }
  function boot(){ try{ document.querySelectorAll('.econcal-root').forEach(init); }catch(_){ } }
  if(document.readyState==='loading'){ document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
})();
