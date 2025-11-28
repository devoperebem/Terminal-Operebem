(function () {
  const ENDPOINT = '/actions/quotes-public';
  let displayedIds = new Set();

  function toNumber(text) {
    const sraw = (text ?? '').toString().trim().replace(/\u2212/g, '-');
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

  function classFromPercent(num) {
    if (num > 0) return { cls: 'text-success', color: '#37ed00' };
    if (num < 0) return { cls: 'text-danger', color: '#FF0000' };
    return { cls: 'text-neutral', color: '#000000' };
  }

  function escapeAttr(val) {
    try {
      return String(val).replace(/[&<>"]/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[s]));
    } catch (_) {
      return '';
    }
  }

  function exchangeCodeForItem(item) {
    const b = item.bolsa || '';
    if (b.includes('NYSE') || b.includes('NASDAQ') || b.includes('AMEX')) return 'XNYS';
    if (b.includes('CME')) return 'XCME';
    if (b.includes('COMEX')) return 'XCEC';
    if (b.includes('NYMEX')) return 'XNYM';
    if (b.includes('CBOT')) return 'XCBT';
    if (b.includes('EUREX')) return 'XEUR';
    if (b.includes('LSE') || b.includes('London')) return 'XLON';
    if (b.includes('B3') || b.includes('BOVESPA')) return 'BVMF';
    if (b.includes('Euronext')) return 'XPAR';
    if (b.includes('Frankfurt') || b.includes('Xetra')) return 'XFRA';
    if (b.includes('Tokyo') || b.includes('TSE')) return 'XTKS';
    if (b.includes('Hong Kong') || b.includes('HKEX')) return 'XHKG';
    if (b.includes('Shanghai') || b.includes('SSE')) return 'XSHG';
    return '';
  }

  function formatTimeFromTimestamp(ts) {
    if (!ts) return { time: '', full: '' };
    try {
      const d = new Date(parseInt(ts, 10) * 1000);
      const tz = (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'America/Sao_Paulo';
      const time = d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: tz });
      const full = d.toLocaleString('pt-BR', { hour12: false, timeZone: tz });
      return { time, full };
    } catch {
      return { time: '', full: '' };
    }
  }

  function buildRow(item, classPerc, colorPerc, timeInfo) {
    const itemKey = (item.id_api || item.code || '').toString();
    const ex = exchangeCodeForItem(item);
    const statusHtml = `<div class="status-bubble ms-1 me-3 status_${itemKey}" data-exchange="${escapeAttr(ex)}" data-code="${escapeAttr(item.code || item.id_api || '')}"></div>`;
    const flagHtml = item.icone_bandeira ? `<span class="fi ${escapeAttr(item.icone_bandeira)} tooltip-target text-lg me-2" style="font-size: 13px" data-tooltip="${escapeAttr(item.bandeira || '')}"></span>` : '';
    const cleanApelido = (item.apelido || '').replace(/\s*\(CFD\)\s*/gi, '').trim();
    const cleanNome = (item.nome || '').replace(/\s*\(CFD\)\s*/gi, '').trim();
    const nameHtml = `<span class="tooltip-target" data-tooltip="${escapeAttr(cleanNome)}">${escapeAttr(cleanApelido)}</span>`;

    let rawP = (item.pcp ?? '').toString().trim();
    let pNum;
    if (rawP === '' || /^(-|—|UNCH)$/i.test(rawP)) {
      pNum = 0;
    } else {
      const pcpStr = rawP.includes('%') ? rawP : (rawP + '%');
      pNum = toNumber(pcpStr);
    }
    const pDisp = (pNum !== null && Number.isFinite(pNum)) ? `${pNum.toFixed(2)}%` : (rawP || '0.00%');

    return `
      <tr order="${item.order_tabela ?? ''}" style="font-weight: 600 !important">
        <td width="50%">
          <div class="d-flex align-items-center">
            ${statusHtml}
            ${flagHtml}
            ${nameHtml}
          </div>
        </td>
        <td class="text-right vlr_field vlr_${itemKey}" last="${escapeAttr(item.last ?? '0')}"><span class="vlr-text">${escapeAttr(item.last ?? '')}</span></td>
        <td class="text-right ${classPerc} perc_${itemKey} tooltip-target perc" data-tooltip="${escapeAttr(item.pc || '')}" data-tooltip-color="${colorPerc}" style="font-weight: 900 !important; color: ${colorPerc} !important;">${pDisp}</td>
        <td class="text-right text-muted hr_${itemKey} tooltip-target-left" data-tooltip="${escapeAttr(timeInfo.full)}">${item.last ? escapeAttr(timeInfo.time) : ''}</td>
      </tr>
    `;
  }

  async function fetchListar() {
    const resp = await fetch(ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'acao=listar'
    });
    if (!resp.ok) throw new Error('Falha ao carregar cotações');
    return resp.json();
  }

  function renderHomeTables(dados) {
    const byOrder = (a, b) => {
      const oa = parseInt(a.order_tabela || '9999', 10);
      const ob = parseInt(b.order_tabela || '9999', 10);
      return oa - ob;
    };
    const isCommodity = (g) => (g || '').includes('metais') || (g || '').includes('energia') || (g || '').includes('agricola');
    const commodities = dados.filter(d => isCommodity(d.grupo)).sort(byOrder).slice(0, 5);
    const adrs = dados.filter(d => (d.grupo || '').includes('adrs')).sort(byOrder).slice(0, 5);

    const tbComo = document.getElementById('home_tbody_como');
    const tbAdrs = document.getElementById('home_tbody_adrs');

    if (tbComo) {
      tbComo.innerHTML = commodities.map(item => {
        const { time, full } = formatTimeFromTimestamp(item.timestamp);
        const pcp = item.pcp ? item.pcp.toString().replace(/\s+/g, '').replace('%', '').replace(',', '.') : '0';
        const numPerc = parseFloat(pcp) || 0;
        const { cls, color } = classFromPercent(numPerc);
        const timeInfo = { time, full };
        return buildRow(item, cls, color, timeInfo);
      }).join('');
    }

    if (tbAdrs) {
      tbAdrs.innerHTML = adrs.map(item => {
        const { time, full } = formatTimeFromTimestamp(item.timestamp);
        const pcp = item.pcp ? item.pcp.toString().replace(/\s+/g, '').replace('%', '').replace(',', '.') : '0';
        const numPerc = parseFloat(pcp) || 0;
        const { cls, color } = classFromPercent(numPerc);
        const timeInfo = { time, full };
        return buildRow(item, cls, color, timeInfo);
      }).join('');
    }

    requestAnimationFrame(updateAveragesFromDom);
    displayedIds = new Set([...commodities, ...adrs].map(i => i.id_api || i.code).filter(Boolean));
  }

  function escapeSelector(selector) {
    return selector.replace(/[!"#$%&'()*+,./:;<=>?@[\]^`{|}~]/g, "\\$&");
  }

  function updateHomeRow(item) {
    const id = item.id_api || item.code;
    if (!id || !displayedIds.has(id)) return;

    const safeId = CSS.escape ? CSS.escape(id) : escapeSelector(id);
    const lastTd = document.querySelector(`.vlr_${safeId}`);
    const percTd = document.querySelector(`.perc_${safeId}`);
    const timeTd = document.querySelector(`.hr_${safeId}`);

    if (lastTd) {
      const newText = (item.last ?? '').toString().trim();
      const span = lastTd.querySelector('.vlr-text');
      if (span) span.textContent = newText;
      else lastTd.textContent = newText;
      lastTd.setAttribute('last', newText || '0');
    }

    if (percTd) {
      const pcp = item.pcp ? item.pcp.toString() : '';
      const tmpPerc = pcp ? (pcp.includes('%') ? pcp : pcp + '%') : '';
      const nval = toNumber(tmpPerc);
      const displayPerc = (nval !== null) ? `${nval.toFixed(2)}%` : (tmpPerc || '0.00%');
      percTd.textContent = displayPerc;
      percTd.setAttribute('data-tooltip', item.pc || '');
      percTd.classList.remove('text-danger', 'text-success', 'text-neutral', 'text-success-alt');
      if (nval !== null) {
        const { cls, color } = classFromPercent(nval);
        if (cls) percTd.classList.add(cls);
        if (color) {
          percTd.style.setProperty('color', color, 'important');
          percTd.setAttribute('data-tooltip-color', color);
        }
      }
    }

    if (timeTd) {
      const info = formatTimeFromTimestamp(item.timestamp);
      timeTd.textContent = item.last ? info.time : '';
      timeTd.setAttribute('data-tooltip', info.full || '');
    }
  }

  function computeAvgFromDom(tbodyId) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return null;
    const cells = tbody.querySelectorAll('td.perc');
    const vals = Array.from(cells)
      .map(td => toNumber(td.textContent))
      .filter(v => v !== null && Number.isFinite(v));
    if (!vals.length) return null;
    return vals.reduce((a, b) => a + b, 0) / vals.length;
  }

  function setAvg(elId, avg) {
    const el = document.getElementById(elId);
    if (!el) return;
    if (avg === null) {
      el.classList.remove('positive', 'negative', 'trend-up', 'trend-down');
      el.classList.add('neutral');
      el.innerHTML = '<span>—</span>';
      el.setAttribute('data-tooltip', 'Média: —');
      return;
    }
    const avgFixed = Number.isFinite(avg) ? parseFloat(avg.toFixed(2)) : 0;
    const txt = `${avgFixed.toFixed(2)}%`;
    el.setAttribute('data-tooltip', `Média: ${txt}`);
    el.classList.remove('positive', 'negative', 'trend-up', 'trend-down', 'neutral');
    const isUp = avg >= 0;
    if (isUp) {
      el.classList.add('positive', 'trend-up');
    } else {
      el.classList.add('negative', 'trend-down');
    }
    const arrow = isUp ? '<i class="fas fa-arrow-up arrow-icon"></i>' : '<i class="fas fa-arrow-down arrow-icon"></i>';
    el.innerHTML = `${arrow}<span>${txt}</span>`;
  }

  function updateAveragesFromDom() {
    const avgComo = computeAvgFromDom('home_tbody_como');
    const avgAdrs = computeAvgFromDom('home_tbody_adrs');
    setAvg('media-como-home', avgComo);
    setAvg('media-adrs-home', avgAdrs);
    if (avgComo === null || avgAdrs === null) {
      setTimeout(() => {
        const a1 = computeAvgFromDom('home_tbody_como');
        const a2 = computeAvgFromDom('home_tbody_adrs');
        setAvg('media-como-home', a1);
        setAvg('media-adrs-home', a2);
      }, 120);
    }
  }

  async function firstLoad() {
    try {
      const res = await fetchListar();
      const dados = res.data || [];
      renderHomeTables(dados);
    } catch (e) {
      console.error('Error loading home quotes:', e);
    }
  }

  async function updateLoop() {
    try {
      if (window.__HOME_WS_OK) return;
      const res = await fetchListar();
      const dados = res.data || [];
      dados.forEach(updateHomeRow);
      requestAnimationFrame(updateAveragesFromDom);
    } catch (e) { /* silenciar */ }
  }

  // Expose for home-websocket.js fallback
  window.fetchSnapshotAndUpdate = firstLoad;

  document.addEventListener('DOMContentLoaded', function () {
    firstLoad();
    setInterval(updateLoop, 5000);
  });
})();
