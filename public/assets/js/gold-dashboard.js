/**
 * Dashboard Ouro - Terminal Operebem
 * Consolidado: Ticker Tape, Cotações, Gráficos, Widgets TradingView
 */
(function(){
  'use strict';

  // ============================================================================
  // UTILITIES
  // ============================================================================
  
  function getCurrentTheme() {
    var cls = document.documentElement.classList;
    if (cls.contains('dark-blue') || cls.contains('all-black')) return 'dark';
    return 'light';
  }

  function getUserTimezone() {
    return (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'Etc/UTC';
  }

  function toNumber(val) {
    if (val === null || val === undefined || val === '') return null;
    var s = String(val).replace(/[^\d.,-]/g, '').replace(',', '.');
    var n = parseFloat(s);
    return isNaN(n) ? null : n;
  }

  function setText(el, txt) {
    if (!el) return;
    el.textContent = String(txt);
  }

  function formatTime(row) {
    try {
      var tz = getUserTimezone();
      var date = null;
      var tUtc = row && row.time_utc ? String(row.time_utc) : '';
      var ts = row && row.timestamp ? row.timestamp : null;
      if (tUtc) {
        // Try parsing full datetime with UTC suffix
        // Normalize: if ends with 'UTC', add 'Z'
        var norm = tUtc.replace(' UTC', 'Z');
        var d1 = new Date(norm);
        if (!isNaN(d1.getTime())) date = d1;
      }
      if (!date && (ts !== null && ts !== undefined && ts !== '')) {
        var n = Number(ts);
        if (!isNaN(n)) {
          if (n < 1e12) n = n * 1000; // epoch seconds -> ms
          var d2 = new Date(n);
          if (!isNaN(d2.getTime())) date = d2;
        }
      }
      if (!date) return '--';
      return date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', timeZone: tz });
    } catch(_) { return '--'; }
  }

  function formatPercent(pct) {
    var num = toNumber(pct);
    if (num === null || isNaN(num)) return '--';
    var sign = num > 0 ? '+' : (num < 0 ? '-' : '');
    var abs = Math.abs(num);
    var s = abs.toLocaleString('pt-BR', { maximumFractionDigits: 2 });
    return sign + s + '%';
  }

  function setClassByPct(el, pct) {
    if (!el) return;
    var num = toNumber(pct);
    el.classList.remove('text-success', 'text-danger', 'text-neutral');
    if (num > 0) el.classList.add('text-success');
    else if (num < 0) el.classList.add('text-danger');
    else el.classList.add('text-neutral');
  }

  // ============================================================================
  // LOADER OVERLAY CONTROL (DESABILITADO)
  // ============================================================================
  function showLoader(){ /* desabilitado */ }
  function hideLoaderSoon(delay){ /* desabilitado */ }
  function incPending(){ /* desabilitado */ }
  function decPending(){ /* desabilitado */ }

  // ============================================================================
  // TICKER TAPE (igual ao dashboard principal)
  // ============================================================================
  
  var __tapeRendering = false;
  var __tapeTimer = null;

  function renderTickerTape() {
    var container = document.getElementById('gold_ticker_tape');
    if (!container) return;

    if (__tapeRendering) {
      clearTimeout(__tapeTimer);
      __tapeTimer = setTimeout(renderTickerTape, 200);
      return;
    }

    __tapeRendering = true;

    try {
      container.innerHTML = '';
      var inner = document.createElement('div');
      inner.className = 'tradingview-widget-container__widget';
      container.appendChild(inner);

      var theme = getCurrentTheme();
      var cfg = {
        symbols: [
          { proName: "OANDA:XAUUSD", title: "OURO" },
          { proName: "CAPITALCOM:DXY", title: "DXY" },
          { proName: "VANTAGE:SP500", title: "SP500 Cash Vanguard" },
          { proName: "INDEX:BTCUSD", title: "Bitcoin" },
          { proName: "TVC:SILVER", title: "Silver" }
        ],
        showSymbolLogo: true,
        displayMode: "compact",
        locale: "br",
        colorTheme: theme,
        isTransparent: theme !== 'light'
      };

      var script = document.createElement('script');
      script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js';
      script.async = true;
      script.text = JSON.stringify(cfg);
      incPending();
      script.onload = function() { setTimeout(function() { __tapeRendering = false; decPending(); }, 150); };
      script.onerror = function() { __tapeRendering = false; };
      container.appendChild(script);
    } catch(e) {
      __tapeRendering = false;
    }
  }

  // ============================================================================
  // COTAÇÕES (5 CARDS)
  // ============================================================================
  
  var ENDPOINT = '/api/quotes/gold-boot';
  var TARGETS = {
    gold: { codes: ['XAUUSD','XAU/USD','GOLD','GOLDUSD','GC1!','TVC:GOLD'], names: ['OURO','GOLD'], keywords: ['OURO','GOLD'] },
    dxy: { codes: ['DXY','TVC:DXY','DX-Y.NYB','ICEUS:DXY'], names: ['DXY','DOLLAR INDEX'], keywords: ['DOLLAR','DÓLAR','INDEX'] },
    us10y: { codes: ['US10Y','^TNX','UST10Y','US10Y.Y'], names: ['10Y','10-Y','TREASURY'], keywords: ['10Y','UST','TREASURY'] },
    vix: { codes: ['VIX','^VIX'], names: ['VIX','VOLATILITY'], keywords: ['VIX','VOLATILITY'] },
    gvz: { codes: ['GVZ','^GVZ','GVOL'], names: ['GVZ','GOLD VOLATILITY'], keywords: ['GOLD','VOLATILITY','GVZ'] }
  };

  function findByCandidates(arr, spec) {
    var norm = function(v) { return String(v || '').toUpperCase().trim(); };
    var codes = (spec.codes || []).map(norm);
    var names = (spec.names || []).map(norm);
    var keywords = (spec.keywords || []).map(norm);

    for (var i = 0; i < arr.length; i++) {
      var it = arr[i];
      var code = norm(it.code || it.Code || '');
      var name = norm(it.nome || it.Name || it.apelido || '');

      for (var j = 0; j < codes.length; j++) {
        if (codes[j] !== '' && code === codes[j]) return it;
      }
      for (var k = 0; k < names.length; k++) {
        if (names[k] !== '' && name === names[k]) return it;
      }
      for (var l = 0; l < keywords.length; l++) {
        if (keywords[l] !== '' && (code.indexOf(keywords[l]) >= 0 || name.indexOf(keywords[l]) >= 0)) return it;
      }
    }
    return null;
  }

  function updateCard(prefix, item) {
    var elPrice = document.getElementById('q_' + prefix + '_price');
    var elChange = document.getElementById('q_' + prefix + '_change');
    var elPc = document.getElementById('q_' + prefix + '_pc');
    var elTime = document.getElementById('q_' + prefix + '_time');

    if (!item) {
      setText(elPrice, '--');
      setText(elChange, '--');
      setText(elPc, '--');
      setText(elTime, '--');
      return;
    }

    var price = (item.last ?? item.last_numeric ?? '--');
    var pctTextRaw = (item.pcp ?? item.pc ?? '--');
    var pctText = formatPercent(pctTextRaw);
    var ts = (item.timestamp ?? item.time_utc ?? null);

    // Cálculo da variação nominal
    var nominalChange = '--';
    var changeNum = 0;
    var lastNum = toNumber(item.last ?? item.last_numeric);
    var closeNum = toNumber(item.last_close);
    if (lastNum !== null && closeNum !== null) {
      changeNum = lastNum - closeNum;
      nominalChange = (changeNum >= 0 ? '+' : '') + changeNum.toFixed(2);
    }

    setText(elPrice, price);
    setText(elChange, nominalChange);
    // Usar o valor numérico para aplicar a classe, não a string formatada
    setClassByPct(elChange, changeNum);
    setText(elPc, pctText);
    // Usar o valor raw para aplicar a classe, não a string formatada
    setClassByPct(elPc, pctTextRaw);
    setText(elTime, formatTime(item));
  }

  async function refreshQuotes() {
    try {
      var res = await fetch(ENDPOINT, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: '',
        credentials: 'same-origin'
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      var json = await res.json();
      var data = json && json.data ? json.data : {};
      var futures = json && Array.isArray(json.futures) ? json.futures : [];
      var futuresAvg = json && json.futures_avg !== undefined ? json.futures_avg : null;

      var gg = data.gold || null;
      var dd = data.dxy || null;
      var uu = data.us10y || null;
      var vv = data.vix || null;
      var gvz = data.gvz || null;

      if (gg || dd || uu || vv || gvz) {
        updateCard('gold', gg);
        updateCard('dxy', dd);
        updateCard('us10y', uu);
        updateCard('vix', vv);
        updateCard('gvz', gvz);
      } else {
        var arr = Array.isArray(data) ? data : [];
        updateCard('gold', findByCandidates(arr, TARGETS.gold));
        updateCard('dxy', findByCandidates(arr, TARGETS.dxy));
        updateCard('us10y', findByCandidates(arr, TARGETS.us10y));
        updateCard('vix', findByCandidates(arr, TARGETS.vix));
        updateCard('gvz', findByCandidates(arr, TARGETS.gvz));
      }

      renderFuturesGrid(futures, futuresAvg);
    } catch(e) {
      // Manter valores anteriores
    }
  }

  // ============================================================================
  // GRID FUTUROS (GC1! .. GC7!) + MÉDIA - Card único com gráfico
  // ============================================================================
  function renderFuturesGrid(items, avg) {
    try {
      var wrap = document.getElementById('gold_futures_grid');
      if (!wrap) return;
      wrap.innerHTML = '';

      // Organizar dados por código
      var order = ['GC1!','GC2!','GC3!','GC4!','GC5!','GC6!','GC7!'];
      var byCode = {};
      (items||[]).forEach(function(it){ if (it && it.code) byCode[String(it.code).toUpperCase()] = it; });

      // Preparar dados para o card
      var futuresData = [];
      for (var i = 0; i < order.length; i++) {
        var code = order[i];
        var item = byCode[code];
        var pct = item ? toNumber(item.pcp ?? item.pc) : null;
        var price = item ? (item.last ?? item.last_numeric ?? '--') : '--';
        futuresData.push({ code: code, item: item, pct: pct, price: price });
      }

      // Criar card único
      var card = document.createElement('div');
      card.className = 'col-12';

      var avgTxt = (avg !== null && avg !== undefined) ? avg.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) : '--';

      // HTML do card com tabela e gráfico lado a lado
      var html = '<div class="card">'
        + '<div class="card-body p-3">'
        + '<div class="d-flex align-items-center justify-content-between mb-3">'
        + '<h6 class="mb-0 text-uppercase">Futuros de Ouro CME (GC1!-GC7!)</h6>'
        + '<div class="small text-muted">Média: <span class="fw-semibold">' + avgTxt + '</span></div>'
        + '</div>'
        + '<div class="row">'
        + '<div class="col-md-5">'
        + '<table class="table table-sm table-borderless mb-0">'
        + '<tbody>';

      // Adicionar linhas da tabela
      for (var j = 0; j < futuresData.length; j++) {
        var fd = futuresData[j];
        var pctText = fd.pct !== null ? formatPercent(fd.pct) : '--';
        var cls = fd.pct > 0 ? 'text-success' : (fd.pct < 0 ? 'text-danger' : 'text-muted');
        html += '<tr>'
          + '<td class="fw-semibold" style="width: 60px;">' + fd.code + '</td>'
          + '<td class="text-end" style="width: 100px;">' + fd.price + '</td>'
          + '<td class="text-end fw-semibold ' + cls + '" style="width: 80px;">' + pctText + '</td>'
          + '</tr>';
      }

      html += '</tbody></table></div>'
        + '<div class="col-md-7">'
        + '<canvas id="gc_futures_chart" style="max-height: 280px;"></canvas>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>';

      card.innerHTML = html;
      wrap.appendChild(card);

      // Renderizar gráfico após inserir no DOM
      setTimeout(function() {
        window.__lastFuturesData = futuresData; // Salvar para re-renderização
        renderFuturesChart(futuresData);
      }, 100);

    } catch(e){ console.error('renderFuturesGrid error:', e); }
  }

  // Renderizar gráfico de barras dos futuros
  function renderFuturesChart(data) {
    try {
      var canvas = document.getElementById('gc_futures_chart');
      if (!canvas || !canvas.getContext) return;

      window.__lastFuturesData = data; // Atualizar dados salvos

      var ctx = canvas.getContext('2d');
      var dpr = window.devicePixelRatio || 1;
      var rect = canvas.getBoundingClientRect();

      canvas.width = rect.width * dpr;
      canvas.height = rect.height * dpr;
      ctx.scale(dpr, dpr);

      var width = rect.width;
      var height = rect.height;
      var padding = { top: 20, right: 20, bottom: 40, left: 40 };
      var chartWidth = width - padding.left - padding.right;
      var chartHeight = height - padding.top - padding.bottom;

      // Encontrar valores min/max para escala
      var values = data.map(function(d) { return d.pct !== null ? d.pct : 0; });
      var maxVal = Math.max.apply(null, values.map(Math.abs));
      maxVal = Math.max(maxVal, 1); // mínimo de 1%
      var scale = chartHeight / (maxVal * 2.2);
      var zeroY = padding.top + chartHeight / 2;

      // Cores baseadas no tema
      var isDark = document.documentElement.classList.contains('dark-blue') ||
                   document.documentElement.classList.contains('all-black');
      var gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
      var textColor = isDark ? '#9ca3af' : '#6b7280';
      var positiveColor = '#10b981';
      var negativeColor = '#ef4444';

      // Limpar canvas
      ctx.clearRect(0, 0, width, height);

      // Desenhar linha zero
      ctx.beginPath();
      ctx.strokeStyle = gridColor;
      ctx.lineWidth = 1;
      ctx.moveTo(padding.left, zeroY);
      ctx.lineTo(width - padding.right, zeroY);
      ctx.stroke();

      // Desenhar barras
      var barWidth = chartWidth / data.length * 0.7;
      var barSpacing = chartWidth / data.length;

      for (var i = 0; i < data.length; i++) {
        var pct = data[i].pct !== null ? data[i].pct : 0;
        var barHeight = Math.abs(pct) * scale;
        var x = padding.left + i * barSpacing + (barSpacing - barWidth) / 2;
        var y = pct >= 0 ? (zeroY - barHeight) : zeroY;

        ctx.fillStyle = pct >= 0 ? positiveColor : negativeColor;
        ctx.fillRect(x, y, barWidth, barHeight);

        // Label do código
        ctx.fillStyle = textColor;
        ctx.font = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(data[i].code, x + barWidth / 2, height - padding.bottom + 20);
      }

    } catch(e){ console.error('renderFuturesChart error:', e); }
  }

  // ============================================================================
  // GRÁFICO PRINCIPAL (GOLD)
  // ============================================================================
  
  function renderGoldChart() {
    var el = document.getElementById('tv_gold_chart');
    if (!el) return;
    el.innerHTML = '';

    var tz = getUserTimezone();
    var theme = getCurrentTheme();

    if (typeof TradingView === 'undefined' || !TradingView.widget) {
      setTimeout(renderGoldChart, 250);
      return;
    }

    try {
      incPending();
      new TradingView.widget({
        autosize: true,
        symbol: 'OANDA:XAUUSD',
        interval: '60',
        timezone: tz,
        theme: theme,
        style: '1',
        locale: 'br',
        toolbar_bg: theme === 'light' ? '#ffffff' : '#111827',
        enable_publishing: false,
        hide_legend: false,
        hide_side_toolbar: false,
        allow_symbol_change: true,
        container_id: 'tv_gold_chart',
        withdateranges: true,
        details: true,
        calendar: true,
        hide_volume: false,
        watchlist: ['OANDA:XAUUSD', 'FOREXCOM:XAUUSD', 'TVC:GOLD', 'COMEX:GC1!']
      });
      setTimeout(decPending, 1400);
    } catch(e) {}
  }

  // ============================================================================
  // COMPARAÇÕES (Advanced Chart com compareSymbols)
  // ============================================================================
  
  function renderComparison(containerId, baseSymbol, compareSymbol) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '';

    var theme = getCurrentTheme();
    var tz = getUserTimezone();
    var bgColor = theme === 'light' ? '#FFFFFF' : '#0F0F0F';

    var wrapper = document.createElement('div');
    wrapper.className = 'tradingview-widget-container';
    wrapper.style.height = '100%';
    wrapper.style.width = '100%';

    var inner = document.createElement('div');
    inner.className = 'tradingview-widget-container__widget';
    inner.style.height = 'calc(100% - 32px)';
    inner.style.width = '100%';
    wrapper.appendChild(inner);

    var copyright = document.createElement('div');
    copyright.className = 'tradingview-widget-copyright';
    copyright.style.display = 'none';
    wrapper.appendChild(copyright);

    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js';
    script.async = true;

    var config = {
      autosize: true,
      symbol: baseSymbol,
      interval: '60',
      timezone: tz,
      theme: theme,
      style: '2',
      locale: 'br',
      allow_symbol_change: false,
      calendar: false,
      details: false,
      hide_side_toolbar: true,
      hide_top_toolbar: true,
      hide_legend: false,
      hide_volume: true,
      hotlist: false,
      save_image: true,
      backgroundColor: bgColor,
      gridColor: 'rgba(242, 242, 242, 0)',
      watchlist: [],
      withdateranges: false,
      compareSymbols: [{ symbol: compareSymbol, position: 'NewPriceScale' }],
      studies: []
    };

    script.innerHTML = JSON.stringify(config);
    wrapper.appendChild(script);
    el.appendChild(wrapper);
    incPending();
    script.onload = function(){ setTimeout(decPending, 800); };
    script.onerror = function(){ decPending(); };
  }

  // ============================================================================
  // RAZÕES (Ratio Charts)
  // ============================================================================
  
  function renderRatio(containerId, ratioSymbol) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '';

    var theme = getCurrentTheme();
    var tz = getUserTimezone();
    var bgColor = theme === 'light' ? '#FFFFFF' : '#0F0F0F';

    var wrapper = document.createElement('div');
    wrapper.className = 'tradingview-widget-container';
    wrapper.style.height = '100%';
    wrapper.style.width = '100%';

    var inner = document.createElement('div');
    inner.className = 'tradingview-widget-container__widget';
    inner.style.height = 'calc(100% - 32px)';
    inner.style.width = '100%';
    wrapper.appendChild(inner);

    var copyright = document.createElement('div');
    copyright.className = 'tradingview-widget-copyright';
    copyright.style.display = 'none';
    wrapper.appendChild(copyright);

    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js';
    script.async = true;

    var config = {
      autosize: true,
      symbol: ratioSymbol,
      interval: '60',
      timezone: tz,
      theme: theme,
      style: '2',
      locale: 'br',
      allow_symbol_change: false,
      calendar: false,
      details: false,
      hide_side_toolbar: true,
      hide_top_toolbar: true,
      hide_legend: false,
      hide_volume: true,
      hotlist: false,
      save_image: true,
      backgroundColor: bgColor,
      gridColor: 'rgba(242, 242, 242, 0)',
      watchlist: [],
      withdateranges: false,
      compareSymbols: [],
      studies: []
    };

    script.innerHTML = JSON.stringify(config);
    wrapper.appendChild(script);
    el.appendChild(wrapper);
  }

  // ============================================================================
  // INDICADORES TÉCNICOS (Technical Analysis Widget)
  // ============================================================================
  
  function renderTechnical(containerId, symbol) {
    var el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = '';

    var theme = getCurrentTheme();

    var wrapper = document.createElement('div');
    wrapper.className = 'tradingview-widget-container';
    wrapper.style.height = '100%';
    wrapper.style.width = '100%';

    var inner = document.createElement('div');
    inner.className = 'tradingview-widget-container__widget';
    inner.style.height = '100%';
    inner.style.width = '100%';
    wrapper.appendChild(inner);

    var copyright = document.createElement('div');
    copyright.className = 'tradingview-widget-copyright';
    copyright.style.display = 'none';
    wrapper.appendChild(copyright);

    var script = document.createElement('script');
    script.type = 'text/javascript';
    script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js';
    script.async = true;

    var config = {
      interval: '15m',
      width: '100%',
      height: '100%',
      isTransparent: false,
      symbol: symbol,
      showIntervalTabs: true,
      disableInterval: false,
      displayMode: 'single',
      locale: 'br',
      colorTheme: theme
    };

    script.innerHTML = JSON.stringify(config);
    wrapper.appendChild(script);
    el.appendChild(wrapper);
  }

  // ============================================================================
  // RENDERIZAÇÃO COMPLETA
  // ============================================================================

  function renderAll() {
    renderTickerTape();
    renderGoldChart();

    // Seção de Comparações
    renderComparison('tv_compare_gold_dxy', 'OANDA:XAUUSD', 'CAPITALCOM:DXY');
    renderComparison('tv_compare_gold_btc', 'OANDA:XAUUSD', 'BITSTAMP:BTCUSD');

    // Seção de Razões
    renderRatio('tv_ratio_gold_miners', 'OANDA:XAUUSD/AMEX:GDX');
    renderRatio('tv_ratio_gold_btc', 'OANDA:XAUUSD/COINBASE:BTCUSD');

    // Seção de Indicadores Técnicos
    renderTechnical('tv_tech_gold', 'OANDA:XAUUSD');
    renderTechnical('tv_tech_dxy', 'CAPITALCOM:DXY');
    renderTechnical('tv_tech_us10y', 'TVC:US10Y');
    renderTechnical('tv_tech_vix', 'CBOE:VIX');
  }

  // ============================================================================
  // INICIALIZAÇÃO
  // ============================================================================
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
      // Remover modal de login se existir (não necessário em dashboard autenticado)
      try { var lm = document.getElementById('loginModal'); if (lm && lm.parentNode) lm.parentNode.removeChild(lm); } catch(_){ }
      try { document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(function(b){ b.style.display='none'; }); } catch(_){ }
      renderAll();
      refreshQuotes();
      setInterval(refreshQuotes, 60000);
    });
  } else {
    try { var lm2 = document.getElementById('loginModal'); if (lm2 && lm2.parentNode) lm2.parentNode.removeChild(lm2); } catch(_){ }
    try { document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(function(b){ b.style.display='none'; }); } catch(_){ }
    renderAll();
    refreshQuotes();
    setInterval(refreshQuotes, 60000);
  }

  // Re-render ao trocar tema (apenas quando o tema realmente muda)
  var __lastTheme = getCurrentTheme();
  var __rerenderTimer = null;
  new MutationObserver(function(muts) {
    if (!muts.some(function(m) { return m.attributeName === 'class'; })) return;
    var th = getCurrentTheme();
    if (th !== __lastTheme) {
      __lastTheme = th;
      clearTimeout(__rerenderTimer);
      __rerenderTimer = setTimeout(function() {
        renderAll();
        refreshQuotes(); // Re-renderizar futuros com novo tema
      }, 600);
    }
  }).observe(document.documentElement, { attributes: true });

  // Re-renderizar gráfico ao redimensionar janela
  var __resizeTimer = null;
  window.addEventListener('resize', function() {
    clearTimeout(__resizeTimer);
    __resizeTimer = setTimeout(function() {
      var canvas = document.getElementById('gc_futures_chart');
      if (canvas && window.__lastFuturesData) {
        renderFuturesChart(window.__lastFuturesData);
      }
    }, 300);
  });

  // ============================================================================
  // CLIENT LOGGING -> Monolog (servidor)
  // ============================================================================
  function clientLog(level, message, metadata){
    try{
      fetch('/api/client-log', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ level: level || 'error', message: String(message || ''), meta: metadata || {} })
      });
    }catch(_){ }
  }
  window.addEventListener('error', function(e){
    try { clientLog('error', e && e.message ? e.message : 'window.error', { source: e && e.filename, lineno: e && e.lineno, colno: e && e.colno }); } catch(_){ }
  });
  window.addEventListener('unhandledrejection', function(e){
    try { clientLog('error', 'unhandledrejection', { reason: (e && e.reason && (e.reason.stack || e.reason.message || String(e.reason))) }); } catch(_){ }
  });

})();
