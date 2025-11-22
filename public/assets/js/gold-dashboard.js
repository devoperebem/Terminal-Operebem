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
    var sign = num > 0 ? '+' : '';
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
  // LOADER OVERLAY CONTROL
  // ============================================================================
  var __pendingWidgets = 0;
  var __hideLoaderTimer = null;
  function showLoader(){
    try{
      var el = document.getElementById('gold_loader');
      if (el) { el.classList.add('show'); }
    }catch(_){}
  }
  function hideLoaderSoon(delay){
    clearTimeout(__hideLoaderTimer);
    __hideLoaderTimer = setTimeout(function(){
      try{
        var el = document.getElementById('gold_loader');
        if (el) { el.classList.remove('show'); }
      }catch(_){ }
    }, typeof delay === 'number' ? delay : 300);
  }
  function incPending(){ __pendingWidgets++; showLoader(); }
  function decPending(){ __pendingWidgets = Math.max(0, __pendingWidgets-1); if (__pendingWidgets === 0) hideLoaderSoon(400); }

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
    var lastNum = toNumber(item.last ?? item.last_numeric);
    var closeNum = toNumber(item.last_close);
    if (lastNum !== null && closeNum !== null) {
      var change = lastNum - closeNum;
      nominalChange = (change >= 0 ? '+' : '') + change.toFixed(2);
    }

    setText(elPrice, price);
    setText(elChange, nominalChange);
    setClassByPct(elChange, pctText);
    setText(elPc, pctText);
    setClassByPct(elPc, pctText);
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
  // GRID FUTUROS (GC1! .. GC7!) + MÉDIA
  // ============================================================================
  function renderFuturesGrid(items, avg) {
    try {
      var wrap = document.getElementById('gold_futures_grid');
      if (!wrap) return;
      wrap.innerHTML = '';
      var makeCard = function(title, it){
        var pct = formatPercent(it && (it.pcp ?? it.pc));
        var cls = 'text-neutral';
        var num = toNumber(it && (it.pcp ?? it.pc));
        if (num !== null) {
          if (num > 0) cls = 'text-success'; else if (num < 0) cls = 'text-danger';
        }
        var timeTxt = it ? formatTime(it) : '--';
        var price = it ? (it.last ?? it.last_numeric ?? '--') : '--';
        var code = it ? (it.code || '') : '';
        var el = document.createElement('div');
        el.className = 'col-6 col-md-4 col-xl-2';
        el.innerHTML = (
          '<div class="card h-100">'
          + '<div class="card-body p-3">'
          + '<div class="text-uppercase small text-muted mb-1">' + title + '</div>'
          + '<div class="fs-6 fw-semibold mb-1">' + price + '</div>'
          + '<div class="d-flex align-items-center justify-content-between">'
          +   '<div class="small ' + cls + '">' + (it ? (toNumber(it.last_numeric) && toNumber(it.last_close) ? ((toNumber(it.last_numeric)-toNumber(it.last_close))>=0?'+':'') + (toNumber(it.last_numeric)-toNumber(it.last_close)).toFixed(2) : '--') : '--') + '</div>'
          +   '<div class="small fw-semibold ' + cls + '">' + pct + '</div>'
          + '</div>'
          + '<div class="small text-muted mt-1">' + (timeTxt) + '</div>'
          + '</div>'
          + '</div>'
        );
        wrap.appendChild(el);
      };

      // Criar cards para cada futuro em ordem de code GC1!..GC7!
      var order = ['GC1!','GC2!','GC3!','GC4!','GC5!','GC6!','GC7!'];
      var byCode = {};
      (items||[]).forEach(function(it){ if (it && it.code) byCode[String(it.code).toUpperCase()] = it; });
      for (var i=0;i<order.length;i++) {
        var c = order[i];
        makeCard(c, byCode[c]);
      }
      // Card de média
      var elAvg = document.createElement('div');
      elAvg.className = 'col-12 col-md-4 col-xl-2';
      var avgTxt = (avg !== null && avg !== undefined) ? avg.toLocaleString('pt-BR', { maximumFractionDigits: 2 }) : '--';
      elAvg.innerHTML = (
        '<div class="card h-100">'
        + '<div class="card-body p-3">'
        + '<div class="text-uppercase small text-muted mb-1">Média GC1–GC7</div>'
        + '<div class="fs-6 fw-semibold mb-1">' + avgTxt + '</div>'
        + '<div class="small text-muted mt-1">' + (new Date()).toLocaleTimeString('pt-BR', { hour:'2-digit', minute:'2-digit' }) + '</div>'
        + '</div>'
        + '</div>'
      );
      wrap.appendChild(elAvg);
    } catch(_){ }
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
  // CORRELAÇÕES (Advanced Chart embed com compareSymbols)
  // ============================================================================
  function renderCorrelation(containerId, baseSymbol, otherSymbol) {
    // Reutiliza o mesmo mecanismo de comparação (embed advanced chart)
    return renderComparison(containerId, baseSymbol, otherSymbol);
  }

  // ============================================================================
  // RENDERIZAÇÃO COMPLETA
  // ============================================================================
  
  function renderAll() {
    showLoader();
    renderTickerTape();
    renderGoldChart();

    // Comparações
    renderComparison('tv_compare_gold_dxy', 'OANDA:XAUUSD', 'CAPITALCOM:DXY');
    renderComparison('tv_compare_gold_btc', 'OANDA:XAUUSD', 'BITSTAMP:BTCUSD');

    // Razões
    renderRatio('tv_ratio_gold_miners', 'OANDA:XAUUSD/AMEX:GDX');
    renderRatio('tv_ratio_gold_btc', 'OANDA:XAUUSD/COINBASE:BTCUSD');

    // Técnicos
    renderTechnical('tv_tech_gold', 'OANDA:XAUUSD');
    renderTechnical('tv_tech_dxy', 'CAPITALCOM:DXY');
    renderTechnical('tv_tech_us10y', 'TVC:US10Y');
    renderTechnical('tv_tech_vix', 'CBOE:VIX');

    // Correlações
    if (typeof TradingView !== 'undefined' && TradingView.widget) {
      renderCorrelation('tv_corr_gold_dxy', 'OANDA:XAUUSD', 'CAPITALCOM:DXY');
      renderCorrelation('tv_corr_gold_us10y', 'OANDA:XAUUSD', 'TVC:US10Y');
      renderCorrelation('tv_corr_gold_btc', 'OANDA:XAUUSD', 'BITSTAMP:BTCUSD');
      renderCorrelation('tv_corr_gold_vix', 'OANDA:XAUUSD', 'TVC:VIX');
    }
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
      __rerenderTimer = setTimeout(renderAll, 600);
    }
  }).observe(document.documentElement, { attributes: true });

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
