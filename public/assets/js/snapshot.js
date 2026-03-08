(function(){
  const STORAGE_KEY = 'TO_DASH_SNAPSHOT_V1';
  const ENDPOINT = '/actions/boot.php';
  
  // Verificar se o usuário tem snapshot avançado habilitado
  function isAdvancedMode(){
    const meta = document.querySelector('meta[name="user-advanced-snapshot"]');
    return meta ? meta.content === '1' : true; // padrão: true
  }

  function nowInfo(){
    const d = new Date();
    const pad = n => String(n).padStart(2,'0');
    const time = `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    const date = `${pad(d.getDate())}/${pad(d.getMonth()+1)}/${d.getFullYear()}`;
    return { time, date, iso: d.toISOString() };
  }

  function has(){ try { return !!localStorage.getItem(STORAGE_KEY); } catch(_) { return false; } }
  function load(){ try { const raw = localStorage.getItem(STORAGE_KEY); return raw ? JSON.parse(raw) : null; } catch(_) { return null; } }
  function save(obj){ try { localStorage.setItem(STORAGE_KEY, JSON.stringify(obj)); return true; } catch(_) { return false; } }
  function clear(){ try { localStorage.removeItem(STORAGE_KEY); } catch(_) {} }

  function labelForToggle(){
    const el = document.getElementById('toggleSnapshotMenu');
    if (!el) return;
    el.innerHTML = has()
      ? '<i class="fas fa-trash-alt me-2"></i>Limpar snapshot'
      : '<i class="fas fa-camera me-2"></i>Fazer snapshot';
  }

  async function fetchAllData(){
    const resp = await fetch(ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'acao=listar'
    });
    if (!resp.ok) throw new Error('Falha ao obter dados');
    const json = await resp.json();
    return json && Array.isArray(json.data) ? json.data : (json.data ? Object.values(json.data) : []);
  }

  function itemKeyOf(it){ return ((it && (it.id_api || it.code)) || '').toString(); }

  function snapshotTooltipHTML(it, meta){
    const when = meta?.createdAtLocal || '';
    const name = (it.apelido || it.nome || '').toString();
    
    // Formatar 'Último' com 2 casas decimais
    let lastFormatted = '--';
    if (it.last != null) {
      const lastNum = parseFloat(String(it.last).replace(',', '.'));
      lastFormatted = Number.isFinite(lastNum) ? lastNum.toFixed(2) : String(it.last);
    }
    
    // Formatar 'Var.%' com 2 casas decimais
    let percFormatted = '--';
    let percNum = 0;
    if (it.pcp != null) {
      const raw = String(it.pcp).replace('%', '').replace(',', '.');
      percNum = parseFloat(raw);
      percFormatted = Number.isFinite(percNum) ? `${percNum.toFixed(2)}%` : String(it.pcp);
    }
    
    // Formatar 'Var' (variação absoluta) com 2 casas decimais e sinal
    let absFormatted = '--';
    if (it.pc != null) {
      const absNum = parseFloat(String(it.pc).replace(',', '.'));
      if (Number.isFinite(absNum)) {
        const sign = absNum > 0 ? '+' : '';
        absFormatted = `${sign}${absNum.toFixed(2)}`;
      } else {
        absFormatted = String(it.pc);
      }
    }
    
    // Formatar hora
    const ts = it.timestamp ? new Date(parseInt(it.timestamp,10)*1000) : null;
    const userTimezone = (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'America/Sao_Paulo';
    const time = ts ? ts.toLocaleString('pt-BR', { timeZone: userTimezone, hour12:false }) : (it.status_hr || '');
    
    // Classe de cor baseada no percentual
    const cls = percNum > 0 ? 'pos' : (percNum < 0 ? 'neg' : 'neu');
    
    return `
      <div class="snap-tip">
        <div class="snap-head">Snapshot • ${escapeHtml(when)}</div>
        <div class="snap-name">${escapeHtml(name)}</div>
        <div class="snap-grid">
          <div><span class="lbl">Último</span><span class="val">${escapeHtml(lastFormatted)}</span></div>
          <div><span class="lbl">Var.%</span><span class="val ${cls}">${escapeHtml(percFormatted)}</span></div>
          <div><span class="lbl">Var</span><span class="val ${cls}">${escapeHtml(absFormatted)}</span></div>
          <div><span class="lbl">Hora</span><span class="val">${escapeHtml(time)}</span></div>
        </div>
      </div>`;
  }

  function escapeHtml(s){
    try {
      return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[m]));
    } catch(_) { return s; }
  }

  function createOrUpdateIcon(td, html, item, meta){
    if (!td) return;
    const advanced = isAdvancedMode();
    
    if (advanced) {
      // Limpar overlay simples, se existir
      try { const s = td.querySelector('.snap-simple'); if (s) { const inst = (window.bootstrap && bootstrap.Tooltip) ? bootstrap.Tooltip.getInstance(s) : null; if (inst) inst.dispose(); s.remove(); } } catch(_){ }

      // Modo avançado: ícone de câmera com tooltip hover (não clicável)
      let icon = td.querySelector('.snapcam');
      if (!icon) {
        icon = document.createElement('i');
        icon.className = 'fas fa-camera snapcam me-1';
        icon.setAttribute('data-bs-toggle','tooltip');
        icon.setAttribute('data-bs-html','true');
        td.insertBefore(icon, td.firstChild);
      }
      icon.setAttribute('title', html);
      try {
        const inst = (window.bootstrap && bootstrap.Tooltip && bootstrap.Tooltip.getInstance) ? bootstrap.Tooltip.getInstance(icon) : null;

        if (inst) {
          // Verificar se a tooltip está visível no momento
          let tipEl = null, isOpen = false;
          try {
            tipEl = (inst.getTipElement && inst.getTipElement());
            isOpen = !!(tipEl && tipEl.classList.contains('show'));
          } catch(_) {}

          if (isOpen) {
            // Tooltip está visível: apenas atualizar o conteúdo sem destruir
            try {
              const inner = tipEl.querySelector('.tooltip-inner');
              if (inner) inner.innerHTML = html;
            } catch(_) {}
          } else {
            // Tooltip não está visível: pode destruir e recriar
            try {
              inst.dispose();
            } catch(_) {}
            if (window.bootstrap && bootstrap.Tooltip) {
              new bootstrap.Tooltip(icon, {
                container: 'body',
                boundary: 'viewport',
                html: true,
                customClass: 'snapshot-tip'
              });
            }
          }
        } else {
          // Não existe tooltip: criar uma nova
          if (window.bootstrap && bootstrap.Tooltip) {
            new bootstrap.Tooltip(icon, {
              container: 'body',
              boundary: 'viewport',
              html: true,
              customClass: 'snapshot-tip'
            });
          }
        }
      } catch(_){}
    } else {
      // Limpar ícone avançado, se existir
      try { const ic = td.querySelector('.snapcam'); if (ic) { const inst = (window.bootstrap && bootstrap.Tooltip) ? bootstrap.Tooltip.getInstance(ic) : null; if (inst) inst.dispose(); ic.remove(); } } catch(_){ }

      // Modo simples: mostrar apenas a variação percentual (congelada) da snapshot
      let span = td.querySelector('.snap-simple');
      if (!span) {
        span = document.createElement('span');
        span.className = 'snap-simple me-2';
        span.style.fontSize = '0.85em';
        span.style.fontWeight = '600';
        span.setAttribute('data-bs-toggle','tooltip');
        span.setAttribute('data-bs-html','false');
        td.insertBefore(span, td.firstChild);
      }

      // Normalizar percentual e fixar em 2 casas decimais
      const raw = (item.pcp != null) ? String(item.pcp) : '';
      const asNum = parseFloat(raw.replace('%','').replace(',','.'));
      const pDisp = Number.isFinite(asNum) ? `${asNum.toFixed(2)}%` : (raw || '--');
      const pcpNum = Number.isFinite(asNum) ? asNum : 0;
      const cls = pcpNum > 0 ? 'text-success' : (pcpNum < 0 ? 'text-danger' : 'text-muted');
      span.className = `snap-simple me-2 ${cls}`;
      span.textContent = pDisp;

      const when = meta?.createdAtLocal || '';
      span.setAttribute('title', `Snapshot • ${when}`);
      try {
        const inst = (window.bootstrap && bootstrap.Tooltip && bootstrap.Tooltip.getInstance) ? bootstrap.Tooltip.getInstance(span) : null;

        if (inst) {
          // Verificar se a tooltip está visível no momento
          let tipEl = null, isOpen = false;
          try {
            tipEl = (inst.getTipElement && inst.getTipElement());
            isOpen = !!(tipEl && tipEl.classList.contains('show'));
          } catch(_) {}

          if (isOpen) {
            // Tooltip está visível: apenas atualizar o título sem destruir
            // (Para tooltips simples de texto, não há necessidade de atualizar o HTML interno)
          } else {
            // Tooltip não está visível: pode destruir e recriar
            try {
              inst.dispose();
            } catch(_) {}
            if (window.bootstrap && bootstrap.Tooltip) {
              new bootstrap.Tooltip(span, {
                container: 'body',
                boundary: 'viewport'
              });
            }
          }
        } else {
          // Não existe tooltip: criar uma nova
          if (window.bootstrap && bootstrap.Tooltip) {
            new bootstrap.Tooltip(span, {
              container: 'body',
              boundary: 'viewport'
            });
          }
        }
      } catch(_){}
    }
  }

  function removeIcons(){ 
    document.querySelectorAll('.snapcam, .snap-simple').forEach(el => { 
      try { const inst = bootstrap.Tooltip.getInstance(el); if (inst) inst.dispose(); } catch(_){} 
      el.remove(); 
    }); 
  }

  function renderIcons(){
    const snap = load(); if (!snap || !Array.isArray(snap.items)) return;
    const meta = { createdAtLocal: snap.createdAtLocal };
    // map by key for O(1)
    const map = new Map();
    snap.items.forEach(it => { map.set(itemKeyOf(it), it); });
    map.forEach((it, key) => {
      const tds = document.querySelectorAll(`.vlr_${cssEscape(key)}`);
      tds.forEach(td => {
        const html = snapshotTooltipHTML(it, meta);
        createOrUpdateIcon(td, html, it, meta);
      });
    });
  }

  function cssEscape(s){
    try { return (window.CSS && CSS.escape) ? CSS.escape(String(s)) : String(s).replace(/["#$%&'()*+,./:;<=>?@\[\]^`{|}~]/g, '\\$&'); } catch(_) { return s; }
  }

  async function onToggleClick(ev){
    ev.preventDefault();
    if (has()) {
      clear();
      removeIcons();
      // limpeza resiliente de marcas antigas
      document.querySelectorAll('[data-tooltip-original]').forEach(el => el.removeAttribute('data-tooltip-original'));
      if (window.TO && TO.utils) TO.utils.showNotification('Snapshot removida.', 'info');
    } else {
      try {
        const all = await fetchAllData();
        const info = nowInfo();
        const data = { createdAt: info.iso, createdAtLocal: `${info.time} ${info.date}`, items: all };
        save(data);
        // Esperar preenchimento inicial do dashboard, se necessário
        let tries = 0; const maxTries = 20;
        function tryRender(){
          if (document.querySelector('.vlr_field')) { renderIcons(); return; }
          if (++tries <= maxTries) { setTimeout(tryRender, 150); } else { renderIcons(); }
        }
        tryRender();
        if (window.TO && TO.utils) TO.utils.showNotification(`Snapshot feita às ${data.createdAtLocal}.`, 'success');
      } catch (e) {
        if (window.TO && TO.utils) TO.utils.showNotification('Falha ao tirar snapshot.', 'danger');
      }
    }
    labelForToggle();
  }

  function initMenu(){
    const btn = document.getElementById('toggleSnapshotMenu');
    if (!btn) return;
    btn.addEventListener('click', onToggleClick);
    labelForToggle();
  }

  // Init quando DOM pronto
  document.addEventListener('DOMContentLoaded', function(){
    initMenu();
    if (has()) {
      // Renderizar overlay conforme o modo atual (avançado ou simples)
      renderIcons();
      setTimeout(renderIcons, 250);
    } else {
      // Sem snapshot salva: garantir que não haja overlays
      removeIcons();
    }
  });

  // Reforçar ícone quando a linha for atualizada dinamicamente
  try {
    window.addEventListener('dashboardRowUpdated', function(ev){
      if (!has()) return;
      const id = ev && ev.detail && ev.detail.id ? String(ev.detail.id) : '';
      if (!id) return;
      const snap = load(); if (!snap || !Array.isArray(snap.items)) return;
      const it = snap.items.find(x => itemKeyOf(x) === id);
      if (!it) return;
      const meta = { createdAtLocal: snap.createdAtLocal };
      const html = snapshotTooltipHTML(it, meta);
      document.querySelectorAll(`.vlr_${cssEscape(id)}`).forEach(td => createOrUpdateIcon(td, html, it, meta));
    });
  } catch(_){}
})();
