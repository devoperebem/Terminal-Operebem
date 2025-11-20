(function(){
  var byCode = {};
  var activeTooltips = []; // Rastrear tooltips abertas
  // Removido estado global por bolsa para evitar abertura em grupo
  function guardHide(inst, state){
    try{
      if(!inst || inst._hideGuarded) return; inst._hideGuarded = true;
      var origHide = inst.hide && inst.hide.bind(inst);
      if(!origHide) return;
      inst.hide = function(){ try{ origHide(); }catch(_){ } };
    }catch(_){ }
  }
  // Desabilitar watchdog de reabertura automática para permitir fechar tooltips corretamente
  try { if (window.__statusTipGuard) { clearInterval(window.__statusTipGuard); window.__statusTipGuard = null; } } catch(_){ }
  // Fechamento global: clique fora/ESC fecha todas as tooltips de status e remove órfãs
  try {
    if (!window.__statusTipGlobalBound) {
      window.__statusTipGlobalBound = true;
      document.addEventListener('click', function(e){
        try{
          var tgt = e.target;
          var insideTip = false;
          var insideBubble = false;
          try { document.querySelectorAll('.tooltip.market-status-tip').forEach(function(t){ if(t.contains(tgt)) insideTip = true; }); } catch(_){ }
          try { var anyBubble = (tgt.closest && tgt.closest('.status-bubble')); if (anyBubble) insideBubble = true; } catch(_){ }
          if (!insideTip && !insideBubble) {
            try {
              document.querySelectorAll('.status-bubble').forEach(function(b){
                try{
                  var inst = b._statusTip || ((window.bootstrap && bootstrap.Tooltip && bootstrap.Tooltip.getInstance) ? bootstrap.Tooltip.getInstance(b) : null);
                  if (inst && inst.hide) { inst.hide(); }
                }catch(_){ }
              });
            } catch(_){ }
            // Remover tooltips órfãs (sem elemento de origem)
            try {
              document.querySelectorAll('.tooltip.market-status-tip.show').forEach(function(t){
                try { t.classList.remove('show'); if (t.parentNode) t.parentNode.removeChild(t); } catch(_){ }
              });
            } catch(_){ }
          }
        }catch(_){ }
      }, true);
      // Também fechar em pointerdown (captura antes de event handlers que possam barrar click)
      document.addEventListener('pointerdown', function(e){
        try{
          var tgt = e.target;
          var insideTip = false;
          var insideBubble = false;
          try { document.querySelectorAll('.tooltip.market-status-tip').forEach(function(t){ if(t.contains(tgt)) insideTip = true; }); } catch(_){ }
          try { var anyBubble = (tgt.closest && tgt.closest('.status-bubble')); if (anyBubble) insideBubble = true; } catch(_){ }
          if (!insideTip && !insideBubble) {
            try { document.querySelectorAll('.status-bubble').forEach(function(b){ var inst = b._statusTip || ((window.bootstrap && bootstrap.Tooltip && bootstrap.Tooltip.getInstance) ? bootstrap.Tooltip.getInstance(b) : null); if (inst && inst.hide) inst.hide(); }); } catch(_){ }
            try { document.querySelectorAll('.tooltip.market-status-tip.show').forEach(function(t){ try { t.classList.remove('show'); if (t.parentNode) t.parentNode.removeChild(t); } catch(_){ } }); } catch(_){ }
          }
        }catch(_){ }
      }, true);
      // (removido) Fechar por focusin para evitar flicker
      // (removido) Fechamento por 'shown.bs.tooltip' global para evitar flicker
      document.addEventListener('keydown', function(e){
        try{
          if (e.key === 'Escape'){
            try {
              document.querySelectorAll('.status-bubble').forEach(function(b){
                try{
                  var inst = b._statusTip || ((window.bootstrap && bootstrap.Tooltip && bootstrap.Tooltip.getInstance) ? bootstrap.Tooltip.getInstance(b) : null);
                  if (inst && inst.hide) { inst.hide(); }
                }catch(_){ }
              });
            } catch(_){ }
            try {
              document.querySelectorAll('.tooltip.market-status-tip.show').forEach(function(t){
                try { t.classList.remove('show'); if (t.parentNode) t.parentNode.removeChild(t); } catch(_){ }
              });
            } catch(_){ }
          }
        }catch(_){ }
      });
    }
  } catch(_){ }
  function pad(n){return n<10?'0'+n:''+n}
  function toSec(hms){var a=(hms||'').split(':');if(a.length<2)return null;var h=parseInt(a[0]||'0',10),m=parseInt(a[1]||'0',10),s=parseInt(a[2]||'0',10);if(isNaN(h)||isNaN(m)||isNaN(s))return null;return h*3600+m*60+s}
  function fmtDur(mins){if(!isFinite(mins))return '';mins=Math.max(0,Math.round(mins));var h=Math.floor(mins/60),m=mins%60;return h>0?(h+'h '+pad(m)+'m'):(m+'m')}
  function mapClsDesc(s){var x=(s||'').toLowerCase();if(x==='open')return{c:'active',d:'Mercado Aberto'};if(x==='pre')return{c:'pre-market',d:'Pré-mercado'};if(x==='post')return{c:'after-hours',d:'Pós-mercado'};if(x==='closed')return{c:'close',d:'Mercado Fechado'};return{c:'close',d:'Sem status'}}
  function nextInfo(ex){try{var status=(ex.calculated_status||'').toLowerCase();var nowStr=(ex.local_time||'').trim();var nowSec=toSec(nowStr);var tPre=toSec(ex.pre_open_time||'');var tOpen=toSec(ex.open_time||'');var tClose=toSec(ex.close_time||'');var tPostS=toSec(ex.after_hours_start||'');var tPostE=toSec(ex.after_hours_end||'');var nxtName='',nxtSec=null;function delta(a,b){if(a==null||b==null)return null;var d=b-a;return d>=0?d:(86400+d)}
    if(status==='pre'&&tOpen!=null){nxtName='Abertura';nxtSec=tOpen}
    else if(status==='open'){if(tPostS!=null){nxtName='Pós-mercado';nxtSec=tPostS}else if(tClose!=null){nxtName='Fechamento';nxtSec=tClose}}
    else if(status==='post'&&tPostE!=null){nxtName='Fechamento';nxtSec=tPostE}
    else { if(tPre!=null){nxtName='Pré-mercado';nxtSec=tPre}else if(tOpen!=null){nxtName='Abertura';nxtSec=tOpen} }
    var mins=null; if(nowSec!=null&&nxtSec!=null){var ds=delta(nowSec,nxtSec);mins=Math.round(ds/60)}
    return {name:nxtName,mins:mins}
  }catch(e){return {name:'',mins:null}}}
  function buildPhases(status){var s=(status||'').toLowerCase();var labels={'pre':'Pré','open':'Aberto','post':'Pós','closed':'Fechado'};var seg=function(k){var bg='#999';if(k==='pre')bg='#fbbf24';if(k==='open')bg='#00bf63';if(k==='post')bg='#0d47a1';if(k==='closed')bg='#999';var op=(s===k)?'1':'0.35';var lbl=labels[k]||'';var fw=(s===k)?'600':'400';return '<div style="flex:1;text-align:center"><div style="height:8px;background:'+bg+';opacity:'+op+';border-radius:4px;margin-bottom:4px"></div><div style="font-size:0.7rem;color:'+(s===k?bg:'#999')+';font-weight:'+fw+'">'+lbl+'</div></div>'};return '<div style="display:flex;gap:8px;margin:12px 0 8px 0">'+seg('pre')+seg('open')+seg('post')+seg('closed')+'</div>'}
  function tzText(ex){try{var tz=(ex.timezone_name||'').split('/');var city=(tz[1]||tz[0]||'').replace(/_/g,' ');var utc=ex.timezone_utc;var sign=Number(utc)>=0?'+':'';return city+' (UTC'+sign+utc+')'}catch(e){return ''}}
  function tipHtml(code, ex){
    if(code==='CRYPTO'){
      var cd=mapClsDesc('open');
      return '<div class="snap-tip"><div class="snap-head">Status do mercado</div><div class="snap-name">Criptomoedas • '+cd.d+'</div>'+buildPhases('open')+'<div class="snap-grid"><div><span class="lbl">Próximo</span><span class="val">Sempre aberto</span></div><div><span class="lbl">Fuso</span><span class="val">Global</span></div></div></div>'
    }
    if(!ex){
      return '<div class="snap-tip"><div class="snap-head">Status do mercado</div><div class="snap-name">'+code+' • Sem dados</div>'+buildPhases('closed')+'</div>'
    }
    var cd=mapClsDesc(ex.calculated_status);var nx=nextInfo(ex);var frase='';
    if(cd.c==='close'){frase='Hora de uma pausa — este mercado está fechado. Ele abrirá em '+(fmtDur(nx.mins)||'—')+'.'}
    else if(cd.c==='active'){frase='Este mercado está aberto agora.'+(nx.name?(' Fecha em '+(fmtDur(nx.mins)||'—')+'.'):'')}
    else if(cd.c==='pre-market'){frase='Pré-mercado em andamento.'+(nx.name?(' Abertura em '+(fmtDur(nx.mins)||'—')+'.'):'')}
    else if(cd.c==='after-hours'){frase='Pós-mercado em andamento.'+(nx.name?(' Fechamento em '+(fmtDur(nx.mins)||'—')+'.'):'')}
    return '<div class="snap-tip"><div class="snap-head">Status do mercado</div><div class="snap-name">'+(ex.exchange_name||code)+' • '+cd.d+'</div>'+buildPhases(ex.calculated_status)+'<div class="snap-grid"><div><span class="lbl">Próximo</span><span class="val">'+(nx.name? (nx.name+' em '+(fmtDur(nx.mins)||'—')) : '—')+'</span></div><div><span class="lbl">Fuso</span><span class="val">'+tzText(ex)+'</span></div></div><div style="margin-top:6px;font-size:0.85rem;color:#6c757d">'+frase+'</div></div>'
  }
  function applyEl(el){
    try{
      var code=(el.getAttribute('data-exchange')||'').toUpperCase();
      if(!code)return;
      var ex=byCode[code];
      var st=(code==='CRYPTO')?'open':(ex&&ex.calculated_status)||'closed';
      var m=mapClsDesc(st);
      el.classList.remove('active','pre-market','after-hours','close');
      el.classList.add(m.c);
      el.style.cursor='pointer';

      var html=tipHtml(code,ex);
      var inst = el._statusTip;

      // Declarar variáveis de estado ANTES de criar instância (evita problemas de escopo)
      if (!el._statusState) {
        el._statusState = {
          isHoveringEl: false,
          isHoveringTip: false,
          isVisible: false
        };
      }
      var state = el._statusState;

      // CRÍTICO: Se tooltip estiver visível, NÃO FAZER NADA - deixar completamente estático
      if (state.isVisible) {
        return; // Pula toda atualização enquanto tooltip estiver visível
      }

      // Criar nova instância apenas se não existir
      if (!inst && window.bootstrap && bootstrap.Tooltip){
        inst = new bootstrap.Tooltip(el,{
          title:html,
          html:true,
          container:'body',
          customClass:'snapshot-tip market-status-tip',
          placement:'auto',
          trigger:'manual',
          boundary:'viewport',
          fallbackPlacements:['top','bottom','right','left'],
          offset:[0,10],
          delay:{show:0,hide:0}
        });
        el._statusTip = inst;
        inst._exchangeCode = code;
      }

      // Atualizar conteúdo apenas quando tooltip NÃO estiver visível
      try{
        if (inst && inst._config) {
          inst._config.title = html;
          el.setAttribute('title', html);
          el.setAttribute('data-bs-original-title', html);
        }
      }catch(_){ }

      if(!el._statusEv && inst){
        el._statusEv=true;

        function getInst(){ try{ return el._statusTip; }catch(_){ return null; } }

        var closeOthers=function(){
          try{
            document.querySelectorAll('.status-bubble').forEach(function(otherEl){
              if(otherEl===el) return;
              try{
                if(otherEl._statusState) otherEl._statusState.isVisible = false;
                var oi = otherEl._statusTip;
                if(oi && oi.hide){ oi.hide(); }
              }catch(_){ }
            });
            activeTooltips.length = 0;
          }catch(_){}
        };

        // Lógica SIMPLES: se não tiver hover em nenhum lugar, esconde
        var checkHide=function(){
          try{
            if(!state.isHoveringEl && !state.isHoveringTip){
              var cur=getInst();
              if(cur){
                state.isVisible = false;
                cur.hide();
              }
            }
          }catch(_){}
        };

        var onEnter=function(){
          try{
            state.isHoveringEl=true;
            // Fechar outras tooltips
            closeOthers();
            // Mostrar esta tooltip imediatamente
            state.isVisible = true;
            inst.show();
            if(activeTooltips.indexOf(inst)===-1){activeTooltips.push(inst);}
          }catch(_){ }
        };

        var onLeave=function(){
          try{
            state.isHoveringEl=false;
            // Aguardar um pouco para ver se o mouse entrou na tooltip
            setTimeout(checkHide, 100);
          }catch(_){}
        };

        var onClick=function(e){
          try{
            e.preventDefault();
            var cur=getInst();
            if(!cur) return;
            var tip=(cur.getTipElement&&cur.getTipElement());
            var open=!!(tip && tip.classList.contains('show'));
            if(open){
              state.isVisible = false;
              cur.hide();
            } else {
              closeOthers();
              state.isVisible = true;
              cur.show();
              if(activeTooltips.indexOf(cur)===-1){activeTooltips.push(cur);}
            }
          }catch(_){ }
        };
        var onKey=function(e){ if(e && (e.key==='Enter' || e.key===' ')){ onClick(e); } };
        
        el.setAttribute('role','button');
        if(!el.hasAttribute('tabindex')) el.setAttribute('tabindex','0');
        el.addEventListener('mouseenter', onEnter);
        el.addEventListener('mouseleave', onLeave);
        el.addEventListener('click', onClick);
        el.addEventListener('touchstart', onClick, {passive:true});
        el.addEventListener('keydown', onKey);
        el.addEventListener('blur', function(){ try{ state.isHoveringEl=false; setTimeout(checkHide, 100); }catch(_){ } });

        // Clique fora fecha
        var onDocClick=function(e){
          try{
            var cur=getInst();
            if(!cur) return;
            var tip=(cur.getTipElement&&cur.getTipElement());
            var insideEl = el.contains(e.target);
            var insideTip = tip && tip.contains(e.target);
            if(!insideEl && !insideTip){
              state.isHoveringEl=false;
              state.isHoveringTip=false;
              state.isVisible = false;
              cur.hide();
            }
          }catch(_){ }
        };
        document.addEventListener('click', onDocClick, true);

        // Esc fecha
        var onEsc=function(e){
          try{
            var cur=getInst();
            if(!cur) return;
            if(e.key==='Escape'){
              state.isHoveringEl=false;
              state.isHoveringTip=false;
              state.isVisible = false;
              cur.hide();
            }
          }catch(_){ }
        };
        document.addEventListener('keydown', onEsc);

        el.addEventListener('shown.bs.tooltip', function(){
          try{
            var cur=getInst();
            var tip = (cur && cur.getTipElement && cur.getTipElement());
            if (tip){
              tip.style.pointerEvents='auto';
              if(!tip._statusHoverBound){
                tip._statusHoverBound = true;
                tip.addEventListener('mouseenter', function(){
                  try{
                    state.isHoveringTip=true;
                  }catch(_){ }
                });
                tip.addEventListener('mouseleave', function(){
                  try{
                    state.isHoveringTip=false;
                    setTimeout(checkHide, 100);
                  }catch(_){ }
                });
              }
              // Se o mouse já está sobre a tooltip, marcar como hover ativo
              try {
                if (tip.matches && tip.matches(':hover')) {
                  state.isHoveringTip = true;
                }
              } catch(_){ }
            }
          }catch(_){ }
        });

        el.addEventListener('hidden.bs.tooltip', function(){
          try{
            var cur=getInst();
            var idx=activeTooltips.indexOf(cur);
            if(idx>-1){activeTooltips.splice(idx,1);}
            var idx2=activeTooltips.indexOf(inst);
            if(idx2>-1){activeTooltips.splice(idx2,1);}
            state.isHoveringEl=false;
            state.isHoveringTip=false;
            state.isVisible = false;
          }catch(_){ }
        });
      }
    }catch(e){}
  }
  function refreshAll(){document.querySelectorAll('.status-bubble[data-exchange]').forEach(applyEl)}
  async function pull(){
    try{
      var r=await fetch('/api/market-clock/all');
      if(r && r.ok){
        var j=await r.json();
        var arr=(j&&j.data)||[];var m={};
        for(var i=0;i<arr.length;i++){var ex=arr[i];if(ex&&ex.exchange_code){m[(ex.exchange_code||'').toUpperCase()]=ex}}
        byCode=m;
      }
    }catch(e){}
    try{ refreshAll(); }catch(_){ }
  }
  function align(){try{var n=new Date();var ms=((5-(n.getMinutes()%5))%5)*60000 - n.getSeconds()*1000 - n.getMilliseconds();var d=ms<=0?0:ms;setTimeout(function(){pull();setInterval(pull,300000)},d)}catch(e){}
  }
  if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',function(){pull();align()})}else{pull();align()}
  try{window.addEventListener('dashboardRowUpdated',function(ev){var id=(ev&&ev.detail&&ev.detail.id)||'';if(!id)return;var els=document.querySelectorAll('.status_'+CSS.escape(id));els.forEach(applyEl)})}catch(e){}
  try{
    var mo=null, t=null; 
    function schedule(){ if(t){ clearTimeout(t); } t=setTimeout(refreshAll, 50); }
    mo=new MutationObserver(function(muts){
      try{
        // Ignorar mutações que envolvem APENAS tooltips de status
        var shouldSchedule=false;
        for(var i=0;i<muts.length;i++){
          var m=muts[i];
          var nodes=[];
          try{ if(m.addedNodes && m.addedNodes.length){ nodes = nodes.concat(Array.from(m.addedNodes)); } }catch(_){}
          try{ if(m.removedNodes && m.removedNodes.length){ nodes = nodes.concat(Array.from(m.removedNodes)); } }catch(_){}
          for(var j=0;j<nodes.length;j++){
            var n=nodes[j];
            if(n && n.nodeType===1){
              var isStatusTip = (n.classList && n.classList.contains('tooltip') && n.classList.contains('market-status-tip')) || (n.closest && n.closest('.tooltip.market-status-tip'));
              if(!isStatusTip){ shouldSchedule=true; break; }
            }
          }
          if(shouldSchedule) break;
        }
        if(shouldSchedule){ schedule(); }
      }catch(_){ schedule(); }
    }); 
    mo.observe(document.body || document.documentElement,{childList:true,subtree:true});
  }catch(_){ }
})();
