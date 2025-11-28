(function(){
  const ENDPOINT = '/actions/quotes-public';
  const flashTimers = {};
  let displayedIds = new Set();
  // Removed Bootstrap tooltip initialization - using custom tooltip system from dashboard

  function toNumber(text){
    const sraw = (text ?? '').toString().trim().replace(/\u2212/g, '-'); // normaliza sinal unicode − para '-'
    if (!sraw) return null;
    const s = sraw.replace(/\s+/g, '');
    const m = s.match(/[+-]?[0-9.,]+/);
    if (!m) return null;
    let numStr = m[0];
    const lastDot = numStr.lastIndexOf('.');
    const lastComma = numStr.lastIndexOf(',');
    const lastSep = Math.max(lastDot, lastComma);
    if (lastSep > -1) {
      const intPart = numStr.slice(0, lastSep).replace(/[.,]/g, '');
      const decPart = numStr.slice(lastSep + 1).replace(/[^0-9]/g, '');
      numStr = `${intPart}.${decPart}`;
    } else {
      numStr = numStr.replace(/[^0-9+-]/g, '');
    }
    const n = parseFloat(numStr);
    return Number.isFinite(n) ? n : null;
  }

  function classFromPercent(num){
    if (num > 0) return { cls: 'text-success', color: '#37ed00' };
    if (num < 0) return { cls: 'text-danger', color: '#FF0000' };
    return { cls: 'text-neutral', color: '#000000' };
  }

  function flagFromItem(item){
    try{
      const id = ((item.id_api || item.code || '') + '').toUpperCase();
      const grp = (item.grupo || '').toLowerCase();
      if (grp.includes('adrs')) return 'us';
      if (id.startsWith('BMFBOVESPA:') || id.startsWith('BVMF:')) return 'br';
      if (id.startsWith('NYSE:') || id.startsWith('NASDAQ:')) return 'us';
      if (id.startsWith('LSE:')) return 'gb';
      if (id.startsWith('XETR:') || id.startsWith('FWB:')) return 'de';
      if (id.startsWith('TSX:') || id.startsWith('TSXV:') || id.startsWith('TO:')) return 'ca';
      if (id.startsWith('TSE:')) return 'jp';
      if (id.startsWith('HKEX:') || id.startsWith('SEHK:')) return 'hk';
      return 'us';
    }catch{ return 'us'; }
  }

  // Flash apenas na COR do texto, por 1.5s
  function flashNumber(el, color, duration=1500){
    if(!el) return;
    try{
      const original = getComputedStyle(el).color;
      // reset transition to restart
      el.style.transition = 'none';
      el.style.willChange = 'color';
      el.style.color = color;
      // force reflow
      void el.offsetWidth;
      // apply transition back to original color
      el.style.transition = `color ${duration}ms ease`;
      el.style.color = original;
      setTimeout(()=>{
        el.style.transition = '';
        el.style.willChange = '';
      }, duration + 50);
    }catch{}
  }

  function escapeSelector(selector){
    return selector.replace(/[!"#$%&'()*+,./:;<=>?@[\]^`{|}~]/g, "\\$&");
  }

  function formatTime(ts){
    if(!ts) return '';
    try{
      const d = new Date(parseInt(ts,10)*1000);
      const tz = (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'America/Sao_Paulo';
      return d.toLocaleTimeString('pt-BR', { hour12:false, timeZone: tz });
    }catch{ return ''; }
  }

  function formatTimeInfo(ts){
    if(!ts) return { time: '', full: '' };
    try{
      const d = new Date(parseInt(ts,10)*1000);
      const tz = (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'America/Sao_Paulo';
      const time = d.toLocaleTimeString('pt-BR', { hour12:false, timeZone: tz });
      const full = d.toLocaleString('pt-BR', { hour12:false, timeZone: tz });
      return { time, full };
    }catch{ return { time:'', full:'' }; }
  }

  function statusBubble(status, hora_status){
    let desc = '-';
    let cls = '';
    const raw = (status || '').toString();
    const s = raw.toLowerCase().replace(/\s+/g, ' ').trim();
    const cleanHour = (hora_status || '')
      .replace('GMT-3', '')
      .replace('As of today at', '')
      .replace('At close at', '')
      .replace('Last update at', '')
      .trim();
    const isOpen = (s.includes('aberto') || s.includes('market open') || s === 'open' || s === 'opened');
    const isClosed = (s.includes('fechado') || s.includes('market closed') || s === 'closed' || s === 'close');
    const isPre = (s.includes('pré') || s.includes('pre ') || s.includes('pre-') || s === 'pre' || s.includes('pre mkt'));
    const isAfter = (s.includes('after') || s.includes('post-market') || s.includes('post market') || s.includes('after-hours'));
    const isDelayed = (s.includes('atraso') || s.includes('delay') || s.includes('delayed'));
    const isDadosAtuais = s.includes('dados atuais');
    if (isOpen) { desc='Mercado Aberto'; cls='active'; }
    else if (isPre) { desc='Pré-mercado'; cls='pre-market'; }
    else if (isAfter) { desc='Pós-mercado'; cls='after-hours'; }
    else if (isClosed) { desc='Mercado Fechado' + (cleanHour ? ', ' + cleanHour : ''); cls='close'; }
    else if (isDelayed) { desc='Dados em atraso' + (cleanHour ? ', ' + cleanHour : ''); cls='after-hours'; }
    else if (isDadosAtuais) { desc='Dados atuais'; cls='close'; }
    else if (!s) { desc='Sem status'; cls='close'; }
    else { desc = raw; cls='close'; }
    return { desc, cls };
  }

  function escapeAttr(val){ try { return String(val).replace(/[&<>"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s])); } catch(_) { return ''; } }

  async function fetchListar(){
    const resp = await fetch(ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'acao=listar'
    });
    if(!resp.ok) throw new Error('Falha ao carregar cotações');
    return resp.json();
  }

  function buildRow(item){
    const pcp = item.pcp ? item.pcp.toString() : '';
    const pcpClean = pcp ? (pcp.includes('%') ? pcp : pcp + '%') : '';
    const num = toNumber(pcpClean);
    const displayPerc = (num !== null) ? `${num.toFixed(2)}%` : (pcpClean || '0.00%');
    const { cls, color } = classFromPercent(num ?? 0);
    const apelido = item.apelido || item.nome || item.code || '';
    const id = item.id_api || item.code || '';
    const tinfo = formatTimeInfo(item.timestamp);
    const hora = tinfo.time;
    const flag = flagFromItem(item);
    const last = (item.last ?? '').toString().trim();
    const abs = (item.pc ?? '').toString().trim();
    const stMk = (item.status_mercado ?? '').toString().trim();
    const nome = (item.nome ?? '').toString().trim();
    const { desc: statusDesc, cls: statusCls } = statusBubble(stMk, item.status_hr);
    const flagHtml = item.icone_bandeira ? `<span class="fi ${escapeAttr(item.icone_bandeira)} tooltip-target text-lg me-2" style="font-size: 13px" data-tooltip="${escapeAttr(item.bandeira || '')}"></span>` : `<span class="fi fi-${escapeAttr(flag)} me-2" style="font-size: 13px" data-tooltip="${escapeAttr(flag.toUpperCase())}"></span>`;
    const statusHtml = `<div class="status-bubble ms-1 me-3 status_${escapeAttr(id)} ${statusCls}" data-tooltip="${escapeAttr(statusDesc)}"></div>`;
    const nameHtml = `<span class="tooltip-target" data-tooltip="${escapeAttr(nome)}">${escapeAttr(apelido)}</span>`;

    return `
      <tr order="${item.order_tabela ?? ''}" data-id="${id}" style="font-weight:600 !important">
        <td width="50%">
          <div class="d-flex align-items-center">
            ${statusHtml}
            ${flagHtml}
            ${nameHtml}
          </div>
        </td>
        <td class="text-right vlr_field vlr_${escapeAttr(id)}" last="${escapeAttr(last)}"><span class="vlr-text">${escapeAttr(last)}</span></td>
        <td class="text-right ${cls} perc_${escapeAttr(id)} tooltip-target perc" data-tooltip="${escapeAttr(abs || '')}" data-tooltip-color="${color}" style="font-weight:900 !important; color:${color} !important;">${displayPerc}</td>
        <td class="text-right text-muted hr_${escapeAttr(id)} tooltip-target-left" data-tooltip="${escapeAttr(tinfo.full)}">${item.last ? escapeAttr(hora) : ''}</td>
      </tr>`;
  }

  function renderHomeTables(dados){
    const byOrder = (a,b)=>{
      const oa = parseInt(a.order_tabela||'9999',10);
      const ob = parseInt(b.order_tabela||'9999',10);
      return oa - ob;
    };
    const isCommodity = (g)=> (g||'').includes('metais') || (g||'').includes('energia') || (g||'').includes('agricola');
    const commodities = dados.filter(d=>isCommodity(d.grupo)).sort(byOrder).slice(0,5);
    const adrs = dados.filter(d=>(d.grupo||'').includes('adrs')).sort(byOrder).slice(0,5);

    const tbComo = document.getElementById('home_tbody_como');
    const tbAdrs = document.getElementById('home_tbody_adrs');
    if (tbComo){ tbComo.innerHTML = commodities.map(buildRow).join(''); }
    if (tbAdrs){ tbAdrs.innerHTML = adrs.map(buildRow).join(''); }

    // Atualiza médias com tooltip com base no DOM renderizado (agendado p/ próximo frame)
    requestAnimationFrame(updateAveragesFromDom);

    displayedIds = new Set([ ...commodities, ...adrs ].map(i=> i.id_api || i.code).filter(Boolean));
  }

  function updateHomeRow(item){
    const id = item.id_api || item.code; if(!id || !displayedIds.has(id)) return;
    const lastTd = document.querySelector(`.vlr_${CSS.escape ? CSS.escape(id) : escapeSelector(id)}`);
    const percTd = document.querySelector(`.perc_${CSS.escape ? CSS.escape(id) : escapeSelector(id)}`);
    const timeTd = document.querySelector(`.hr_${CSS.escape ? CSS.escape(id) : escapeSelector(id)}`);

    if (lastTd){
      const newText = (item.last ?? '').toString().trim();
      const span = lastTd.querySelector('.vlr-text');
      if (span) span.textContent = newText; else lastTd.textContent = newText;
      lastTd.setAttribute('last', newText || '0');
    }

    if (percTd){
      const pcp = item.pcp ? item.pcp.toString() : '';
      const tmpPerc = pcp ? (pcp.includes('%') ? pcp : pcp + '%') : '';
      const nval = toNumber(tmpPerc);
      const displayPerc = (nval !== null) ? `${nval.toFixed(2)}%` : (tmpPerc || '0.00%');
      percTd.textContent = displayPerc;
      percTd.setAttribute('data-tooltip', item.pc || '');
      percTd.classList.remove('text-danger','text-success','text-neutral','text-success-alt');
      if (nval !== null){
        const { cls, color } = classFromPercent(nval);
        if (cls) percTd.classList.add(cls);
        if (color) {
          percTd.style.setProperty('color', color, 'important');
          percTd.setAttribute('data-tooltip-color', color);
        }
      }
    }

    if (timeTd){
      const info = formatTimeInfo(item.timestamp);
      timeTd.textContent = item.last ? info.time : '';
      timeTd.setAttribute('data-tooltip', info.full || '');
    }

    // Atualiza status bubble
    try{
      const bubble = document.querySelector(`.status_${CSS.escape ? CSS.escape(id) : escapeSelector(id)}`);
      if (bubble){
        const { desc, cls } = statusBubble(item.status_mercado, item.status_hr);
        bubble.classList.remove('active','close','after-hours','pre-market');
        if (cls) bubble.classList.add(cls);
        bubble.setAttribute('data-tooltip', desc);
      }
    }catch{}

    // suporte legado: preview-asset (se existir)
    updatePreviewAssetLegacy(item);
  }
  function updatePreviewAssetLegacy(item){
    const id = item.id_api || item.code;
    if(!id) return;
    const safeId = escapeSelector(id);
    const el = document.querySelector(`.preview-asset[data-id="${CSS.escape ? CSS.escape(id) : safeId}"]`);
    if(!el) return;
    const lastEl = el.querySelector('.home-preview-last');
    const percEl = el.querySelector('.home-preview-perc');

    if (lastEl){
      const oldText = (lastEl.textContent || '').trim();
      const newText = (item.last ?? '').toString().trim();
      if (oldText !== newText){
        const prev = toNumber(oldText);
        const next = toNumber(newText);
        lastEl.textContent = newText;
        if (prev !== null && next !== null && prev !== next){
          flashNumber(lastEl, next > prev ? '#37ed00' : '#FF0000', 1500);
        }
      }
    }

    if (percEl){
      const pcp = item.pcp ? item.pcp.toString() : '';
      const tmpPerc = pcp ? (pcp.includes('%') ? pcp : pcp + '%') : '';
      const nval = toNumber(tmpPerc);
      const displayPerc = (nval !== null) ? `${nval.toFixed(2)}%` : (tmpPerc || '0.00%');
      percEl.textContent = displayPerc;
      percEl.classList.remove('text-danger','text-success','text-neutral','text-success-alt');
      if (nval !== null){
        const { cls, color } = classFromPercent(nval);
        if (cls) percEl.classList.add(cls);
        if (color) percEl.style.setProperty('color', color, 'important');
      }
    }
  }

  // Cálculo de média baseado no que está EXIBIDO no DOM (igual aparência dos cards)
  function computeAvgFromDom(tbodyId){
    const tbody = document.getElementById(tbodyId);
    if(!tbody) return null;
    const cells = tbody.querySelectorAll('td[class*="home-perc-"], td[class*="perc_"], tr > td:nth-child(3)');
    const vals = Array.from(cells)
      .map(td => toNumber(td.textContent))
      .filter(v => v !== null && Number.isFinite(v));
    if(!vals.length) return null;
    return vals.reduce((a,b)=>a+b,0) / vals.length;
  }

  function setAvg(elId, avg){
    const el = document.getElementById(elId);
    if (!el) return;
    if (avg === null){
      el.classList.remove('positive','negative','trend-up','trend-down');
      el.classList.add('neutral');
      el.innerHTML = '<span>—</span>';
      el.setAttribute('data-tooltip', 'Média: —');
      return;
    }
    const avgFixed = Number.isFinite(avg) ? parseFloat(avg.toFixed(2)) : 0;
    const txt = `${avgFixed.toFixed(2)}%`;
    el.setAttribute('data-tooltip', `Média: ${txt}`);
    el.classList.remove('positive','negative','trend-up','trend-down','neutral');
    const isUp = avg >= 0;
    if (isUp) {
      el.classList.add('positive','trend-up');
    } else {
      el.classList.add('negative','trend-down');
    }
    // Ícone FA com animação
    const arrow = isUp ? '<i class="fas fa-arrow-up arrow-icon"></i>' : '<i class="fas fa-arrow-down arrow-icon"></i>';
    el.innerHTML = `${arrow}<span>${txt}</span>`;
  }

  function updateAveragesFromDom(){
    const avgComo = computeAvgFromDom('home_tbody_como');
    const avgAdrs = computeAvgFromDom('home_tbody_adrs');
    setAvg('media-como-home', avgComo);
    setAvg('media-adrs-home', avgAdrs);
    if (avgComo === null || avgAdrs === null){
      setTimeout(() => {
        const a1 = computeAvgFromDom('home_tbody_como');
        const a2 = computeAvgFromDom('home_tbody_adrs');
        setAvg('media-como-home', a1);
        setAvg('media-adrs-home', a2);
      }, 120);
    }
  }

  async function firstLoad(){
    const res = await fetchListar();
    const dados = res.data || [];
    renderHomeTables(dados);
  }

  async function updateLoop(){
    try{
      if (window.__HOME_WS_OK) return;
      const res = await fetchListar();
      const dados = res.data || [];
      dados.forEach(updateHomeRow);
      // Recalcular médias com base no filtro atual (mesma lógica do firstLoad)
      const byOrder = (a,b)=> (parseInt(a.order_tabela||'9999',10) - parseInt(b.order_tabela||'9999',10));
      const isCommodity = (g)=> (g||'').includes('metais') || (g||'').includes('energia') || (g||'').includes('agricola');
      const commodities = dados.filter(d=>isCommodity(d.grupo)).sort(byOrder).slice(0,5);
      const adrs = dados.filter(d=>(d.grupo||'').includes('adrs')).sort(byOrder).slice(0,5);
      // Recalcular médias no próximo frame para garantir DOM atualizado
      requestAnimationFrame(updateAveragesFromDom);
    }catch(e){ /* silenciar */ }
  }

  document.addEventListener('DOMContentLoaded', function(){
    firstLoad();
    setInterval(updateLoop, 5000);
  });
})();
