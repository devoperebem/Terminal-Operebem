// Mapeia o item de cripto para o slug do PNG em /assets/images/crypto-dashboard-flags
function cryptoSlugFrom(item) {
  const t = ((item.code || item.apelido || item.nome || '') + '').toLowerCase();
  if (!t) return null;
  if (t.includes('btc') || t.includes('bitcoin')) return 'bitcoin';
  if (t.includes('eth') || t.includes('ethereum')) return 'ethereum';
  if (t.includes('xrp') || t.includes('ripple')) return 'xrp';
  if (t.includes('ada') || t.includes('cardano')) return 'cardano';
  if (t.includes('bnb')) return 'bnb';
  if (t.includes('sol') || t.includes('solana')) return 'solana';
  if (t.includes('trx') || t.includes('tron')) return 'tron';
  if (t.includes('doge') || t.includes('dogecoin')) return 'dogecoin';
  if (t.includes('trump')) return 'trump';
  return null;
}
/*
 * Dashboard boot script - Portado do projeto antigo para o novo MVC
 * Responsável por carregar as cotações via /actions/boot.php e preencher as tabelas
 */

(function () {
  const ENDPOINT = '/actions/boot.php';
  const flashTimers = {};
  const RATES_WHITELIST = new Set(['US1M', 'US02Y', 'US05Y', 'US10Y', 'BR02Y', 'BR05Y', 'BR10Y', 'EU10Y', 'GB10Y', 'JP10Y']);
  let WS = null; let WS_OK = false; let COMP_TIMER = null; let FALLBACK_TIMER = null;

  function flashTextColor(el, flashColor, duration = 1000, key = '') {
    if (!el) return;
    try {
      const original = getComputedStyle(el).color;
      // Sem transições: aplica cor de flash imediatamente
      el.style.setProperty('transition', 'none');
      el.style.setProperty('will-change', 'color');
      el.style.setProperty('color', flashColor, 'important');

      if (key && flashTimers[key]) clearTimeout(flashTimers[key]);
      if (key) flashTimers[key] = setTimeout(() => {
        // Reverte imediatamente à cor original capturada, sem fade
        el.style.setProperty('transition', 'none');
        el.style.setProperty('color', original, 'important');
        el.style.removeProperty('will-change');
      }, duration);
    } catch (e) { }
  }

  // Helpers
  function formatTimeFromTimestamp(ts) {
    if (!ts) return '';
    try {
      // Obter timezone do usuário (padrão: America/Sao_Paulo)
      const userTimezone = window.USER_TIMEZONE || 'America/Sao_Paulo';

      const utcDate = new Date(parseInt(ts, 10) * 1000);
      const time = utcDate.toLocaleString('pt-BR', {
        timeZone: userTimezone,
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      });
      const full = utcDate.toLocaleString('pt-BR', { timeZone: userTimezone, hour12: false });
      return { time, full };
    } catch (e) {
      return { time: '', full: '' };
    }
  }

  function classFromPercent(numPerc) {
    if (numPerc > 0) return { cls: 'text-success', color: '#37ed00' };
    if (numPerc < 0) return { cls: 'text-danger', color: '#FF0000' };
    // Zero: cor depende do tema
    var html = document.documentElement;
    var isDark = !!(html && html.classList && (html.classList.contains('dark-blue') || html.classList.contains('all-black')));
    return { cls: 'text-neutral', color: isDark ? '#ffffff' : '#000000' };
  }

  // Normaliza string numérica robustamente, lidando com separadores mistos
  // Exemplos aceitos: "1.234,56" -> 1234.56 | "1,234,567.89" -> 1234567.89 | "144,137" -> 144137
  function toNumber(text) {
    const sraw = (text ?? '').toString().trim();
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

  function exchangeFromBolsa(b) {
    const s = (b || '').toString().toLowerCase();
    if (!s) return null;
    if (s.includes('b3') || s.includes('sao paulo') || s.includes('são paulo')) return 'XBSP';
    if (s.includes('nyse') || s.includes('xnys')) return 'XNYS';
    if (s.includes('nasdaq')) return 'XNAS';
    if (s.includes('london') || s.includes('lse')) return 'XLON';
    if (s.includes('xetra') || s.includes('frankfurt') || s.includes('fwb')) return 'XETR';
    if (s.includes('paris') || s.includes('euronext paris')) return 'XPAR';
    if (s.includes('tokyo') || s.includes('jpx')) return 'XTKS';
    if (s.includes('hong kong') || s.includes('hkex')) return 'XHKG';
    if (s.includes('shanghai') || s.includes('sse')) return 'XSHG';
    if (s.includes('shenzhen') || s.includes('szse')) return 'XSHE';
    if (s.includes('toronto') || s.includes('tsx')) return 'XTSE';
    if (s.includes('australian') || s.includes('asx') || s.includes('sydney')) return 'XASX';
    if (s.includes('madrid') || s.includes('bme')) return 'XMAD';
    if (s.includes('zurich') || s.includes('six')) return 'XSWX';
    if (s.includes('singapore') || s.includes('sgx')) return 'XSES';
    if (s.includes('mexico') || s.includes('bmv')) return 'XMEX';
    if (s.includes('johannesburg') || s.includes('jse')) return 'XJSE';
    if (s.includes('milan') || s.includes('borsa italiana') || s.includes('mib')) return 'XMIL';
    if (s.includes('xcbt') || s.includes('cbot')) return 'XCBT'; // Chicago Board of Trade
    if (s.includes('ice')) return 'IFUS'; // ICE Futures US (Coffee, Brent Oil, etc.)
    return null;
  }

  function exchangeCodeForItem(item) {
    const byBolsa = exchangeFromBolsa(item && item.bolsa);
    if (byBolsa) return byBolsa;
    const g = (item && item.grupo || '').toString();
    const f = (item && item.icone_bandeira || '').toString();
    if (g.includes('indice_brasileiro')) return 'XBSP';
    if (g.includes('indice_usa') || g.includes('big_tech') || g.includes('adrs')) return 'XNYS';
    if (g.includes('indice_europeu')) return 'XETR';
    if (g.includes('indice_asia')) return 'XTKS';
    if (f.includes('fi-br')) return 'XBSP';
    if (f.includes('fi-us') || f.includes('fi-um')) return 'XNYS';
    if (f.includes('fi-de')) return 'XETR';
    if (f.includes('fi-gb')) return 'XLON';
    if (f.includes('fi-fr')) return 'XPAR';
    if (f.includes('fi-jp')) return 'XTKS';
    if (f.includes('fi-hk') || f.includes('fi-cn')) return 'XHKG';
    return 'UNKNOWN';
  }

  function statusBubble(status, hora_status) {
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

    const isOpen = (
      s.includes('aberto') ||
      s.includes('market open') ||
      s === 'open' || s === 'opened'
    );
    const isClosed = (
      s.includes('fechado') ||
      s.includes('market closed') ||
      s === 'closed' || s === 'close'
    );
    const isPre = (
      s.includes('pré') || s.includes('pre ') || s.includes('pre-') || s === 'pre' || s.includes('pre mkt')
    );
    const isAfter = (
      s.includes('after') || s.includes('post-market') || s.includes('post market') || s.includes('after-hours')
    );
    const isDelayed = (
      s.includes('atraso') || s.includes('delay') || s.includes('delayed')
    );
    const isDadosAtuais = s.includes('dados atuais');

    if (isOpen) {
      desc = 'Mercado Aberto';
      cls = 'active';
    } else if (isPre) {
      desc = 'Pré-mercado';
      cls = 'pre-market';
    } else if (isAfter) {
      desc = 'Pós-mercado';
      cls = 'after-hours';
    } else if (isClosed) {
      desc = 'Mercado Fechado' + (cleanHour ? ', ' + cleanHour : '');
      cls = 'close';
    } else if (isDelayed) {
      desc = 'Dados em atraso' + (cleanHour ? ', ' + cleanHour : '');
      cls = 'after-hours';
    } else if (isDadosAtuais) {
      // Tratar como neutro/fechado (não aberto)
      desc = 'Dados atuais';
      cls = 'close';
    } else if (!s) {
      desc = 'Sem status';
      cls = 'close';
    } else {
      // Texto desconhecido: mostrar texto e aplicar close para não deixar sem cor
      desc = raw;
      cls = 'close';
    }

    return { desc, cls };
  }

  function escapeSelector(selector) {
    return selector.replace(/[!"#$%&'()*+,./:;<=>?@[\]^`{|}~]/g, "\\$&");
  }

  function escapeAttr(val) {
    try { return String(val).replace(/[&<>"]/g, s => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[s])); } catch (_) { return ''; }
  }

  /**
   * Atualiza o tooltip de um elemento de forma segura:
   * - Se o tooltip estiver visível, atualiza apenas o conteúdo interno (evita destruí-lo durante hover)
   * - Se o tooltip não estiver visível, destrói e recria com o novo conteúdo
   * - Segue o mesmo padrão da correção implementada em snapshot.js
   */
  function updateTooltipSafely(element, newTooltipText) {
    if (!element || !newTooltipText) return;

    try {
      // Atualizar o atributo data-tooltip para referência
      element.setAttribute('data-tooltip', newTooltipText);
      element.setAttribute('title', newTooltipText);

      // Tentar obter a instância existente do Bootstrap Tooltip
      const inst = bootstrap.Tooltip.getInstance(element);

      if (inst) {
        // Verificar se o tooltip está visível no momento
        let tipEl = null, isOpen = false;
        try {
          tipEl = (inst.getTipElement && inst.getTipElement());
          isOpen = !!(tipEl && tipEl.classList.contains('show'));
        } catch (_) { }

        if (isOpen) {
          // TOOLTIP VISÍVEL: Apenas atualizar o conteúdo interno sem destruir
          try {
            const inner = tipEl.querySelector('.tooltip-inner');
            if (inner) inner.textContent = newTooltipText;
          } catch (_) { }
        } else {
          // TOOLTIP NÃO VISÍVEL: Seguro destruir e recriar com novo conteúdo
          try {
            inst.dispose();
          } catch (_) { }

          // Recriar tooltip com novo conteúdo
          if (window.bootstrap && bootstrap.Tooltip) {
            new bootstrap.Tooltip(element, {
              container: 'body',
              boundary: 'viewport'
            });
          }
        }
      } else {
        // Não há instância existente: criar nova
        if (window.bootstrap && bootstrap.Tooltip) {
          new bootstrap.Tooltip(element, {
            container: 'body',
            boundary: 'viewport'
          });
        }
      }
    } catch (e) {
      console.error('Erro ao atualizar tooltip:', e);
    }
  }

  // Atualiza valores já renderizados
  function updateUIValues(id_api, last, pc, pcp, timestamp, status_mercado, status_hr) {
    if (!id_api) return;

    const safeId = escapeSelector(id_api);
    const percClean = (pcp || '').toString().replace(/\s+/g, '').replace('%', '').replace(',', '.');
    const numPerc = parseFloat(percClean);
    const { cls, color } = classFromPercent(numPerc);

    // Valor
    const $vlrTd = $(`.vlr_${safeId}`);
    const $vlrSpan = $vlrTd.find('.vlr-text');
    const $vlrTarget = $vlrSpan.length ? $vlrSpan : $vlrTd; // fallback para TD
    if ($vlrTarget.length) {
      const oldText = ($vlrTarget.text() || '').trim();
      const newText = (last ?? '').toString().trim();

      // Atualizar texto e atributos
      $vlrTarget.text(newText);
      $vlrTd.attr('last', newText || '0');

      // Tooltip removido: agora mostrado apenas pelo ícone de snapshot
      // if (pc && $vlrTd[0]) {
      //   updateTooltipSafely($vlrTd[0], pc);
      // }

      // Piscada somente quando o VALOR numérico muda de fato
      const prev = toNumber(oldText);
      const next = toNumber(newText);
      if (prev !== null && next !== null && prev !== next) {
        const el = $vlrTarget[0];
        flashTextColor(el, next > prev ? '#37ed00' : '#FF0000', 1000, `vlr_${safeId}`);
      }
    }

    // Percentual
    const $perc = $(`.perc_${safeId}`);
    if ($perc.length) {
      let rawP = (pcp ?? '').toString().trim();
      let newNum;
      if (rawP === '' || /^(-|—|UNCH)$/i.test(rawP)) {
        newNum = 0; rawP = '0.00%';
      } else {
        const tmpPerc = rawP.includes('%') ? rawP : rawP + '%';
        newNum = toNumber(tmpPerc);
      }
      // Exibir com 2 casas
      const disp = (newNum !== null && Number.isFinite(newNum)) ? `${newNum.toFixed(2)}%` : rawP;
      const oldNum = toNumber(($perc.text() || '').toString());
      if (disp) $perc.text(disp);

      // Tooltip inicializado pelo app.js via classe tooltip-target
      // if (pc && $perc[0]) {
      //   updateTooltipSafely($perc[0], pc);
      // }

      // Atualizar classes sempre que houver número válido; do contrário, mantém classes e cor atuais
      if (newNum !== null && Number.isFinite(newNum)) {
        $perc.removeClass('text-danger text-success text-neutral text-success-alt');
        const { cls: pcls, color: pcolor } = classFromPercent(newNum);
        if (pcls) $perc.addClass(pcls);
        if ($perc[0] && pcolor) {
          // Enforce inline color para não depender de variações de tema
          $perc[0].style.setProperty('color', pcolor, 'important');
          if (oldNum !== null && oldNum !== newNum) {
            const flashPercColor = (newNum > 0) ? '#00ff00' : (newNum < 0 ? '#ff3333' : pcolor);
            flashTextColor($perc[0], flashPercColor, 1000, `perc_${safeId}`);
          }
        }
        // Recompute average for this card
        try {
          const card = $perc.closest('.card');
          if (card && card.length) {
            const percCells = card.find('td.perc');
            let sum = 0, cnt = 0;
            percCells.each(function () {
              const t = ($(this).text() || '').toString();
              const n = toNumber(t.includes('%') ? t : (t + '%'));
              if (n !== null && Number.isFinite(n)) { sum += n; cnt++; }
            });
            const avg = cnt ? (sum / cnt) : 0;
            const el = card.find('.media-percentage');
            if (el && el.length) {
              const val = Number.isFinite(avg) ? parseFloat(avg.toFixed(2)) : 0;
              el.text(`${val.toFixed(2)}%`);
              el.removeClass('positive negative neutral');
              if (val > 0) el.addClass('positive');
              else if (val < 0) el.addClass('negative');
              else el.addClass('neutral');
            }
          }
        } catch (e) { }
      }
    }

    // Timestamp
    const $hr = $(`.hr_${safeId}`);
    if ($hr.length) {
      const { time, full } = formatTimeFromTimestamp(timestamp);
      $hr.text(last ? time : '');

      // Tooltip inicializado pelo app.js via classe tooltip-target-left
      // if (full && $hr[0]) {
      //   updateTooltipSafely($hr[0], full);
      // }
    }

    // Notificar listeners (ex.: snapshot) que a linha foi atualizada
    try { window.dispatchEvent(new CustomEvent('dashboardRowUpdated', { detail: { id: String(id_api) } })); } catch (_) { }
  }

  function calculateAveragePercentage(items) {
    if (!items || !items.length) return 0;
    let sum = 0, count = 0;
    items.forEach(item => {
      const str = item.pcp || '';
      if (str) {
        const normalized = str.replace(',', '.');
        const cleaned = normalized.replace(/[^0-9.-]/g, '');
        const val = parseFloat(cleaned);
        if (!isNaN(val)) { sum += val; count++; }
      }
    });
    return count ? (sum / count) : 0;
  }

  function updateMediaPercentages(dados) {
    if (!dados || !dados.length) return;
    const grupos = {
      'futuros': dados.filter(i => i.grupo?.includes('indices_futuros')),
      'norte-americanos': dados.filter(i => i.grupo?.includes('indice_usa')),
      'europeus': dados.filter(i => i.grupo?.includes('indice_europeu')),
      'asiaticos': dados.filter(i => i.grupo?.includes('indice_asia')),
      'sul-americanos': dados.filter(i => i.grupo?.includes('indice_brasileiro')),
      'adrs': dados.filter(i => i.grupo?.includes('adrs')),
      'cripto': dados.filter(i => i.grupo?.includes('cripto')),
      'mineradoras': dados.filter(i => i.grupo?.includes('mineradoras')),
      'petroleiras': dados.filter(i => i.grupo?.includes('petroleiras')),
      'big_tech': dados.filter(i => i.grupo?.includes('big_tech')),
      'juros': dados.filter(i => i.grupo?.includes('juros')),
      'agricolas': dados.filter(i => i.grupo?.includes('agricola')),
      'energia': dados.filter(i => i.grupo?.includes('energia')),
      'metais': dados.filter(i => i.grupo?.includes('metais')),
      'dolar': dados.filter(i => i.grupo?.includes('indice_dolar')),
      'mundo': dados.filter(i => i.grupo?.includes('mundo')),
      'emergentes': dados.filter(i => i.grupo?.includes('emergentes')),
      'vola': dados.filter(i => i.grupo?.includes('vola')),
      'outros': dados.filter(i => i.grupo?.includes('outros')),
      'futuros-ouro': dados.filter(i => i.grupo?.includes('futuros_ouro')),
      'gold-miners': dados.filter(i => i.grupo?.includes('gold_miners'))
    };

    Object.entries(grupos).forEach(([key, items]) => {
      const avg = calculateAveragePercentage(items);
      const el = document.getElementById(`media-${key}`);
      if (el) {
        const val = Number.isFinite(avg) ? parseFloat(avg.toFixed(2)) : 0;
        el.textContent = `${val.toFixed(2)}%`;
        // Classes de cor: positivo/negativo/neutro conforme valor
        el.classList.remove('positive', 'negative', 'neutral');
        if (val > 0) el.classList.add('positive');
        else if (val < 0) el.classList.add('negative');
        else el.classList.add('neutral');
      }
    });
  }

  function removeClasses() {
    $(".vlr_field, .perc").removeClass("flash-green flash-red");
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

  async function fetchListarComplemento() {
    const resp = await fetch(ENDPOINT, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'acao=listar_complemento'
    });
    if (!resp.ok) throw new Error('Falha ao carregar complementares');
    return resp.json();
  }

  function buildRow(item, classPerc, colorPerc, timeInfo) {
    const itemKey = (item.id_api || item.code || '').toString();
    const ex = exchangeCodeForItem(item);
    const statusHtml = `<div class="status-bubble ms-1 me-3 status_${itemKey}" data-exchange="${escapeAttr(ex)}" data-code="${escapeAttr(item.code || item.id_api || '')}"></div>`;
    const flagHtml = item.icone_bandeira ? `<span class="fi ${escapeAttr(item.icone_bandeira)} tooltip-target text-lg me-2" style="font-size: 13px" data-tooltip="${escapeAttr(item.bandeira || '')}"></span>` : '';
    // Remover (CFD) dos nomes
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

  function buildCryptoRow(item, classPerc, colorPerc, timeInfo) {
    const itemKey = (item.id_api || item.code || '').toString();
    // Usa PNG da pasta crypto-dashboard-flags, com fallback em ícone FA
    const slug = cryptoSlugFrom(item);
    const flagHtml = slug
      ? `<img src="/assets/images/crypto-dashboard-flags/${slug}.png" alt="${escapeAttr(slug)}" width="18" height="18" class="me-2" draggable="false" ondragstart="return false" style="user-select:none;-webkit-user-drag:none;pointer-events:none;image-rendering:auto;">`
      : '<i class="fas fa-coins me-2"></i>';
    const statusHtml = `<div class="status-bubble me-2 status_${itemKey}" data-exchange="CRYPTO" data-code="${escapeAttr(item.code || item.id_api || '')}"></div>`;

    // Percentual com 2 casas decimais (consistente com buildRow)
    let rawP = (item.pcp ?? '').toString().trim();
    let pNum;
    if (rawP === '' || /^(-|—|UNCH)$/i.test(rawP)) {
      pNum = 0;
    } else {
      const pcpStr = rawP.includes('%') ? rawP : (rawP + '%');
      pNum = toNumber(pcpStr);
    }
    const pDisp = (pNum !== null && Number.isFinite(pNum)) ? `${pNum.toFixed(2)}%` : (rawP || '0.00%');

    // Remover (CFD) dos nomes
    const cleanApelido = (item.apelido || '').replace(/\s*\(CFD\)\s*/gi, '').trim();
    const cleanNome = (item.nome || '').replace(/\s*\(CFD\)\s*/gi, '').trim();

    return `
      <tr order="${item.order_tabela ?? ''}" style="font-weight: 600 !important">
        <td width="50%">
          <div class="d-flex align-items-center">
            ${statusHtml}
            ${flagHtml}
            <span class="tooltip-target" data-tooltip="${escapeAttr(cleanNome)}">${escapeAttr(cleanApelido)}</span>
          </div>
        </td>
        <td class="text-right vlr_field vlr_${itemKey}" last="${escapeAttr(item.last ?? '0')}"><span class="vlr-text">${escapeAttr(item.last ?? '')}</span></td>
        <td class="text-right ${classPerc} perc_${itemKey} tooltip-target perc" data-tooltip="${escapeAttr(item.pc || '')}" data-tooltip-color="${colorPerc}" style="font-weight: 900 !important; color: ${colorPerc} !important;">${pDisp}</td>
        <td class="text-right text-muted hr_${itemKey} tooltip-target-left" data-tooltip="${escapeAttr(timeInfo.full)}">${item.last ? escapeAttr(timeInfo.time) : ''}</td>
      </tr>
    `;
  }

  async function init() {
    try {
      const res = await fetchListar();
      const dados = res.data || [];
      const nx = s => (s || '').toString().toLowerCase();
      dados.forEach(item => {
        const name = nx(item.apelido) || nx(item.nome);
        const code = (item.code || '').toString().toUpperCase();
        if ((name.includes('rio') && name.includes('tinto')) || code === 'RIO' || code === 'RIO.AX') {
          item.icone_bandeira = 'fi-au';
          item.bandeira = 'Sydney';
        }
      });

      let html = {
        futuros: '',
        norte: '',
        europeus: '',
        asia: '',
        brasil: '',
        adrs: '',
        cripto: '',
        juros: '',
        big_tech: '',
        agricolas: '',
        energia: '',
        metais: '',
        dolar: '',
        mundo: '',
        emergentes: '',
        vola: '',
        mineradoras: '',
        petroleiras: '',
        outros: '',
        futuros_ouro: '',
        gold_miners: ''
      };

      dados.forEach(item => {
        const { time, full } = formatTimeFromTimestamp(item.timestamp);
        const pcp = item.pcp ? item.pcp.toString().replace(/\s+/g, '').replace('%', '').replace(',', '.') : '0';
        const numPerc = parseFloat(pcp) || 0;
        const { cls, color } = classFromPercent(numPerc);
        const timeInfo = { time, full };

        if ((item.grupo || '').includes('indices_futuros')) {
          html.futuros += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('indice_usa')) {
          html.norte += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('indice_europeu')) {
          html.europeus += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('indice_asia')) {
          html.asia += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('indice_brasileiro')) {
          html.brasil += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('adrs')) {
          html.adrs += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('cripto')) {
          html.cripto += buildCryptoRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('juros')) {
          const code = (item.code || '').toUpperCase();
          if (RATES_WHITELIST.has(code)) {
            html.juros += buildRow(item, cls, color, timeInfo);
          }
        } else if ((item.grupo || '').includes('big_tech')) {
          html.big_tech += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('agricola')) {
          html.agricolas += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('energia')) {
          html.energia += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('metais')) {
          html.metais += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('indice_dolar')) {
          html.dolar += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('mundo')) {
          html.mundo += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('emergentes')) {
          html.emergentes += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('vola')) {
          html.vola += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('outros')) {
          html.outros += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('mineradoras')) {
          html.mineradoras += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('petroleiras')) {
          html.petroleiras += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('futuros_ouro')) {
          html.futuros_ouro += buildRow(item, cls, color, timeInfo);
        } else if ((item.grupo || '').includes('gold_miners')) {
          html.gold_miners += buildRow(item, cls, color, timeInfo);
        }
      });

      $(".tbody_indices_futuros").html(html.futuros);
      $(".tbody_indices_norte_americanos").html(html.norte);
      $(".tbody_indices_europeus").html(html.europeus);
      $(".tbody_indices_asia").html(html.asia);

      $(".tbody_brasileiros").html(html.brasil);
      $(".tbody_adrs").html(html.adrs);
      $("#table_criptomoedas").html(html.cripto);

      $(".tbody_juros").html(html.juros);
      $(".tbody_big_tech").html(html.big_tech);
      $(".tbody_agricolas").html(html.agricolas);
      $(".tbody_energia").html(html.energia);
      $(".tbody_metais").html(html.metais);

      $(".tbody_dolar").html(html.dolar);
      $(".tbody_mundo").html(html.mundo);
      $(".tbody_emergentes").html(html.emergentes);
      $(".tbody_vola").html(html.vola);

      $(".tbody_outros").html(html.outros);

      $(".tbody_mineradoras").html(html.mineradoras);
      $(".tbody_petroleiras").html(html.petroleiras);

      $(".tbody_futuros_ouro").html(html.futuros_ouro);
      $(".tbody_gold_miners").html(html.gold_miners);

      updateMediaPercentages(dados);
    } catch (err) {
      console.error('Erro ao carregar dados do dashboard:', err);
      if (window.TO?.utils) {
        window.TO.utils.showNotification('Erro ao carregar dados do mercado', 'warning');
      }
    }
  }

  async function updateLoop() {
    try {
      const res = await fetchListar();
      const dados = res.data || [];
      dados.forEach(item => {
        updateUIValues(
          (item.id_api || item.code || '').toString(),
          item.last,
          item.pc,
          item.pcp,
          item.timestamp,
          item.status_mercado,
          item.status_hr
        );
      });
      updateMediaPercentages(dados);
    } catch (e) {
      // Silently ignore to keep loop running
    }
  }

  function startComplementLoop() {
    if (COMP_TIMER) clearInterval(COMP_TIMER);
    COMP_TIMER = setInterval(async () => {
      try {
        const res = await fetchListarComplemento();
        const dados = res.data || [];
        dados.forEach(item => {
          updateUIValues(
            (item.id_api || item.code || '').toString(),
            item.last,
            item.pc,
            item.pcp,
            item.timestamp,
            item.status_mercado,
            item.status_hr
          );
        });
      } catch (e) { }
    }, 20000);
  }

  function stopComplementLoop() { if (COMP_TIMER) { clearInterval(COMP_TIMER); COMP_TIMER = null; } }
  function startFallbackLoop() { if (!FALLBACK_TIMER) { FALLBACK_TIMER = setInterval(updateLoop, 5000); } }
  function stopFallbackLoop() { if (FALLBACK_TIMER) { clearInterval(FALLBACK_TIMER); FALLBACK_TIMER = null; } }

  // Agendador alinhado a cada 5 minutos para garantir refresh exato de status de mercado
  function scheduleFiveMinuteAligned() {
    try {
      const now = new Date();
      const msToNext = ((5 - (now.getMinutes() % 5)) % 5) * 60000 - now.getSeconds() * 1000 - now.getMilliseconds();
      const delay = msToNext <= 0 ? 0 : msToNext;
      setTimeout(() => {
        // Atualiza imediatamente no marco e agenda a cada 5 minutos
        updateLoop();
        setInterval(updateLoop, 5 * 60 * 1000);
      }, delay);
    } catch (e) { /* noop */ }
  }

  function parseProxyMessage(raw) {
    try {
      if (!raw) return null;
      // Case 1: SockJS style: a["{\"message\":\"pid-<region>-<id>::{...}\"}"]
      if (typeof raw === 'string' && raw[0] === 'a') {
        const arr = JSON.parse(raw.slice(1));
        if (!Array.isArray(arr) || !arr.length) return null;
        const obj = JSON.parse(arr[0]);
        const msg = obj.message || '';
        const idx = msg.indexOf('::');
        if (idx === -1) return null;
        const payload = msg.slice(idx + 2);
        const data = JSON.parse(payload);
        // Generalize: pid or pidExt → extract region+digits and normalize to id_api = 'pid-<region>-<digits>'
        const mAny = msg.match(/pid(?:Ext)?-([a-z]+)-(\d+)/i);
        if (mAny) {
          const region = mAny[1];
          const digits = mAny[2];
          data.pid = digits;
          data.id_api = `pid-${region}-${digits}`;
        }
        return data;
      }
      // Case 2: plain JSON string
      if (typeof raw === 'string' && (raw[0] === '{' || raw[0] === '[')) {
        const obj = JSON.parse(raw);
        // If wrapped in {message: "pid-xx-123::..."}
        if (obj && typeof obj.message === 'string') {
          const idx = obj.message.indexOf('::');
          const payload = idx !== -1 ? obj.message.slice(idx + 2) : '';
          const data = payload ? JSON.parse(payload) : {};
          const mAny = obj.message.match(/pid(?:Ext)?-([a-z]+)-(\d+)/i);
          if (mAny) {
            const region = mAny[1];
            const digits = mAny[2];
            data.pid = digits;
            data.id_api = `pid-${region}-${digits}`;
          }
          return data;
        }
        // If already data-like
        if (obj && (obj.pid || obj.id_api || obj.code)) return obj;
        return null;
      }
      return null;
    } catch (e) { return null; }
  }

  function startWebsocketIfConfigured() {
    const cfg = (window.WEBSOCKET_CONFIG || window.WS_CONFIG) || {};
    if (!cfg.url || !cfg.apiKey) return;
    try {
      WS = new WebSocket(`${cfg.url}/?key=${encodeURIComponent(cfg.apiKey)}`);
      WS.onopen = () => { WS_OK = true; stopFallbackLoop(); };
      WS.onmessage = (ev) => {
        const q = parseProxyMessage(ev.data);
        if (!q) return;
        const candidates = [];
        if (q.id_api) candidates.push(q.id_api.toString());
        if (q.pid) candidates.push(q.pid.toString());
        if (q.code) candidates.push(q.code.toString());
        if (!candidates.length) return;
        // try { console.log('[WS][update]', { ids: candidates, sample: q }); } catch(e){}
        for (const key of candidates) {
          try {
            const sel = `.vlr_${(window.CSS && CSS.escape) ? CSS.escape(key) : escapeSelector(key)}`;
            const exists = !!document.querySelector(sel);
            // console.log('[WS][dom]', key, exists);
          } catch (e) { }
          updateUIValues(key, q.last, q.pc, q.pcp, q.timestamp, q.status_mercado, q.status_hr);
        }
      };
      WS.onclose = () => { WS_OK = false; startFallbackLoop(); setTimeout(startWebsocketIfConfigured, 3000); };
      WS.onerror = () => { };
    } catch (e) { }
  }

  $(document).ready(function () {
    init();
    startWebsocketIfConfigured();
    // Complement Loader ativo a cada 20s para campos complementares (CNBC/Yahoo)
    startComplementLoop();
    // Garantir refresh exato a cada 5 minutos, independente do WS/fallback
    scheduleFiveMinuteAligned();
  });

  // Expor helper global para testes manuais no console
  try { window.updateDashboardValue = function (id, last, pc, pcp, ts, sm, shr) { updateUIValues(String(id), last, pc, pcp, ts, sm, shr); }; } catch (e) { }
})();
