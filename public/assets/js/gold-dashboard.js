/**
 * Dashboard Ouro - Terminal Operebem
 * Consolidado: Ticker Tape, Cotações, Gráficos, Widgets TradingView
 */
(function () {
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

    // Converter para string e remover espaços
    var s = String(val).trim();

    // Detectar formato: se tem vírgula E ponto, identificar qual é decimal
    // Formato BR: 4.079,50 (ponto = separador de milhares, vírgula = decimal)
    // Formato US: 4,079.50 (vírgula = separador de milhares, ponto = decimal)

    var lastComma = s.lastIndexOf(',');
    var lastDot = s.lastIndexOf('.');

    // Se tem ambos, o último é o separador decimal
    if (lastComma > -1 && lastDot > -1) {
      if (lastComma > lastDot) {
        // Formato BR: 4.079,50 ou 1.234.567,89
        // Remover pontos (separador de milhares) e trocar vírgula por ponto
        s = s.replace(/\./g, '').replace(',', '.');
      } else {
        // Formato US: 4,079.50 ou 1,234,567.89
        // Remover vírgulas (separador de milhares)
        s = s.replace(/,/g, '');
      }
    } else if (lastComma > -1) {
      // Só tem vírgula - assumir formato BR (4079,50)
      s = s.replace(',', '.');
    }
    // Se só tem ponto ou nenhum separador, já está no formato correto

    // Remover qualquer caracter não numérico exceto ponto decimal e sinal
    s = s.replace(/[^\d.-]/g, '');

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
    } catch (_) { return '--'; }
  }

  function formatPercent(pct) {
    var num = toNumber(pct);
    if (num === null || isNaN(num)) return '--';
    var sign = num > 0 ? '+' : (num < 0 ? '-' : '');
    var abs = Math.abs(num);
    var s = abs.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
  function showLoader() { /* desabilitado */ }
  function hideLoaderSoon(delay) { /* desabilitado */ }
  function incPending() { /* desabilitado */ }
  function decPending() { /* desabilitado */ }

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
      script.onload = function () { setTimeout(function () { __tapeRendering = false; decPending(); }, 150); };
      script.onerror = function () { __tapeRendering = false; };
      container.appendChild(script);
    } catch (e) {
      __tapeRendering = false;
    }
  }

  // ============================================================================
  // COTAÇÕES (5 CARDS)
  // ============================================================================

  var ENDPOINT = '/api/quotes/gold-boot';
  var TARGETS = {
    gold: { codes: ['68', 'XAUUSD', 'XAU/USD'], names: ['OURO', 'GOLD'], keywords: [] },
    gold2: { codes: ['8830', 'GOLD'], names: ['OURO 2!', 'GOLD 2!'], keywords: [] },
    dxy: { codes: ['DXY', 'TVC:DXY', 'DX-Y.NYB', 'ICEUS:DXY'], names: ['DXY', 'DOLLAR INDEX'], keywords: ['DOLLAR', 'DÓLAR', 'INDEX'] },
    us10y: { codes: ['US10Y', '^TNX', 'UST10Y', 'US10Y.Y'], names: ['10Y', '10-Y', 'TREASURY'], keywords: ['10Y', 'UST', 'TREASURY'] },
    vix: { codes: ['VIX', '^VIX'], names: ['VIX', 'VOLATILITY'], keywords: ['VIX', 'VOLATILITY'] },
    gvz: { codes: ['GVZ', '^GVZ', 'GVOL'], names: ['GVZ', 'GOLD VOLATILITY'], keywords: ['GOLD', 'VOLATILITY', 'GVZ'] }
  };

  function findByCandidates(arr, spec) {
    var norm = function (v) { return String(v || '').toUpperCase().trim(); };
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
      var gg2 = data.gold2 || null;
      var dd = data.dxy || null;
      var uu = data.us10y || null;
      var vv = data.vix || null;
      var gvz = data.gvz || null;

      if (gg || gg2 || dd || uu || vv || gvz) {
        updateCard('gold', gg);
        updateCard('gold2', gg2);
        updateCard('dxy', dd);
        updateCard('us10y', uu);
        updateCard('vix', vv);
        updateCard('gvz', gvz);
      } else {
        var arr = Array.isArray(data) ? data : [];
        updateCard('gold', findByCandidates(arr, TARGETS.gold));
        updateCard('gold2', findByCandidates(arr, TARGETS.gold2));
        updateCard('dxy', findByCandidates(arr, TARGETS.dxy));
        updateCard('us10y', findByCandidates(arr, TARGETS.us10y));
        updateCard('vix', findByCandidates(arr, TARGETS.vix));
        updateCard('gvz', findByCandidates(arr, TARGETS.gvz));
      }

      renderFuturesGrid(futures, futuresAvg);
    } catch (e) {
      // Manter valores anteriores
    }
  }

  // ============================================================================
  // GRID FUTUROS (GC1! .. GC7!) + MÉDIA - Card único com gráficos interativos
  // ============================================================================
  function renderFuturesGrid(items, avg) {
    try {
      // O PHP já renderizou a tabela com os dados corretos (window.goldData).
      // Agora precisamos apenas renderizar os gráficos (Chart.js) nos canvas existentes.

      var data = window.goldData || [];
      if (data.length === 0) return;

      // Mapear dados para o formato esperado pelos gráficos
      var futuresData = data.map(function (item) {
        return {
          code: item.code,
          price: item.price,
          fair_value: item.fair_value,
          pct: null // A view nova não tem variação percentual, mas o gráfico de barras precisa. 
          // Se não tiver, o gráfico de barras ficará zerado ou podemos omitir.
          // O usuário pediu "Term Structure" (Curva). O gráfico de barras era de variação.
          // O request foca no "Term Structure". Vou manter a curva e talvez adaptar o de barras ou removê-lo se não fizer sentido.
          // O request diz: "O gráfico de linha precisa contar uma história sobre o tempo." (Term Structure).
          // Não mencionou o gráfico de barras.
          // Vou focar no gráfico de Curva (Term Structure) que é o solicitado.
        };
      });

      // Renderizar apenas a Curva de Futuros (Term Structure)
      // O layout PHP novo tem apenas a tabela. Onde colocar o gráfico?
      // O request diz: "Front-End: O Gráfico 'Term Structure'".
      // O layout anterior tinha 3 colunas: Tabela, Barras, Curva.
      // O meu PHP substituiu tudo por um card com tabela.
      // ERRO NO PLANO: Eu substituí o grid inteiro pela tabela, matando os canvas dos gráficos.
      // CORREÇÃO: Preciso reinserir o canvas do gráfico no layout PHP ou via JS.
      // O usuário pediu "Transforme a tabela atual nisto". E depois "Front-End: O Gráfico Term Structure".
      // Vou ajustar o layout via JS para incluir o gráfico ao lado ou abaixo da tabela, 
      // OU (melhor) ajustar o PHP para incluir o container do gráfico.
      // Como já editei o PHP, vou injetar o gráfico via JS no container 'gold_futures_grid' 
      // mas preservando a tabela que o PHP gerou? Não, o PHP gerou dentro de 'gold_futures_grid'.

      // Vamos ver o que o PHP gerou:
      // <div id="gold_futures_grid"><div class="col-12"><div class="card">...<table>...</table>...</div></div></div>

      // Vou adicionar uma nova coluna ou linha para o gráfico dentro desse card ou num novo card.
      // O melhor é colocar o gráfico em um card separado ou ao lado da tabela se houver espaço.
      // Vou criar um novo card para o Gráfico de Term Structure logo abaixo da tabela.

      var wrap = document.getElementById('gold_futures_grid');
      if (!wrap) return;

      // Verificar se já existe o container do gráfico, se não, criar
      if (!document.getElementById('term_structure_card')) {
        var chartCard = document.createElement('div');
        chartCard.className = 'col-12 mt-3';
        chartCard.id = 'term_structure_card';
        chartCard.innerHTML = ''
          + '<div class="card h-100">'
          + '<div class="card-header bg-transparent border-0">'
          + '<h5 class="card-title mb-0 fw-bold">Term Structure (Market vs Fair Value)</h5>'
          + '</div>'
          + '<div class="card-body">'
          + '<canvas id="gc_futures_curve" style="height: 400px; width: 100%;"></canvas>'
          + '</div>'
          + '</div>';
        wrap.appendChild(chartCard);
      }

      // Renderizar a Curva
      setTimeout(function () {
        renderFuturesCurve(futuresData);
      }, 100);

    } catch (e) { console.error('renderFuturesGrid error:', e); }
  }

  // ... (activateFuturesTooltips removido pois não é mais necessário para a tabela PHP estática)

  // Renderizar gráfico de barras (REMOVIDO - Foco no Term Structure)
  function renderFuturesChart(data) { /* ... */ }

  // Renderizar gráfico de curva dos futuros (term structure)
  function renderFuturesCurve(data) {
    try {
      var canvas = document.getElementById('gc_futures_curve');
      if (!canvas || !canvas.getContext) return;

      var ctx = canvas.getContext('2d');
      var dpr = window.devicePixelRatio || 1;
      var rect = canvas.getBoundingClientRect();

      canvas.width = rect.width * dpr;
      canvas.height = rect.height * dpr;
      ctx.scale(dpr, dpr);

      var width = rect.width;
      var height = rect.height;
      var padding = { top: 30, right: 30, bottom: 40, left: 55 };
      var chartWidth = width - padding.left - padding.right;
      var chartHeight = height - padding.top - padding.bottom;

      // Extrair preços
      var prices = [];
      var fairValues = [];

      for (var i = 0; i < data.length; i++) {
        prices.push(toNumber(data[i].price));
        fairValues.push(toNumber(data[i].fair_value));
      }

      // Escala Y (Min/Max de ambos os datasets)
      var allValues = prices.concat(fairValues).filter(function (v) { return v !== null; });
      if (allValues.length === 0) return;

      var minVal = Math.min.apply(null, allValues);
      var maxVal = Math.max.apply(null, allValues);
      var range = maxVal - minVal;
      if (range === 0) range = maxVal * 0.01;

      // Margem de 10%
      var yMin = minVal - range * 0.1;
      var yMax = maxVal + range * 0.1;
      var yRange = yMax - yMin;

      // Cores
      var isDark = document.documentElement.classList.contains('dark-blue') ||
        document.documentElement.classList.contains('all-black');
      var gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
      var textColor = isDark ? '#9ca3af' : '#6b7280';

      var marketColor = '#3b82f6'; // Azul Sólido
      var fairColor = '#10b981';   // Verde Tracejado

      // Limpar
      ctx.clearRect(0, 0, width, height);

      // Grade e Labels Y
      ctx.strokeStyle = gridColor;
      ctx.lineWidth = 1;
      ctx.setLineDash([2, 3]);
      var gridLines = 5;
      for (var g = 0; g <= gridLines; g++) {
        var gy = padding.top + (chartHeight / gridLines) * g;
        ctx.beginPath();
        ctx.moveTo(padding.left, gy);
        ctx.lineTo(width - padding.right, gy);
        ctx.stroke();

        var val = yMax - (yRange / gridLines) * g;
        ctx.fillStyle = textColor;
        ctx.font = '10px sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText(val.toFixed(1), padding.left - 8, gy + 4);
      }
      ctx.setLineDash([]);

      // Função auxiliar para plotar linha
      function drawLine(values, color, isDashed) {
        var points = [];
        var xStep = chartWidth / (data.length - 1);

        for (var i = 0; i < data.length; i++) {
          if (values[i] === null) continue;
          var x = padding.left + i * xStep;
          var y = padding.top + ((yMax - values[i]) / yRange) * chartHeight;
          points.push({ x: x, y: y });
        }

        if (points.length < 2) return;

        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (var i = 1; i < points.length; i++) {
          ctx.lineTo(points[i].x, points[i].y);
        }

        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        if (isDashed) ctx.setLineDash([5, 5]);
        else ctx.setLineDash([]);
        ctx.stroke();
        ctx.setLineDash([]); // Reset

        // Dots
        points.forEach(function (pt) {
          ctx.beginPath();
          ctx.arc(pt.x, pt.y, 3, 0, Math.PI * 2);
          ctx.fillStyle = color;
          ctx.fill();
        });

        return points;
      }

      // Desenhar Datasets e guardar pontos para tooltip
      var marketPoints = drawLine(prices, marketColor, false);      // Market (Azul Sólido)
      var fairPoints = drawLine(fairValues, fairColor, true);       // Fair Value (Verde Tracejado)

      // Labels X
      var xStep = chartWidth / (data.length - 1);
      ctx.fillStyle = textColor;
      ctx.textAlign = 'center';
      for (var i = 0; i < data.length; i++) {
        var x = padding.left + i * xStep;
        ctx.fillText(data[i].code, x, height - padding.bottom + 15);
      }

      // Legenda
      var legX = width - padding.right - 150;
      var legY = padding.top - 15;

      // Market Legend
      ctx.fillStyle = marketColor;
      ctx.fillRect(legX, legY, 10, 10);
      ctx.fillStyle = textColor;
      ctx.textAlign = 'left';
      ctx.fillText('Market', legX + 15, legY + 9);

      // Fair Value Legend
      ctx.fillStyle = fairColor;
      ctx.fillRect(legX + 60, legY, 10, 10);
      ctx.fillStyle = textColor;
      ctx.fillText('Fair Value', legX + 75, legY + 9);

      // Interatividade: Tooltip
      if (!canvas.__hasHoverListener) {
        canvas.__hasHoverListener = true;

        canvas.addEventListener('mousemove', function (e) {
          var rect = canvas.getBoundingClientRect();
          var mouseX = e.clientX - rect.left;
          var mouseY = e.clientY - rect.top;

          // Encontrar ponto mais próximo
          var allPoints = [];
          if (marketPoints) {
            marketPoints.forEach(function (pt, idx) {
              pt.code = data[idx].code;
              pt.price = prices[idx];
              pt.type = 'Market';
              allPoints.push(pt);
            });
          }
          if (fairPoints) {
            fairPoints.forEach(function (pt, idx) {
              pt.code = data[idx].code;
              pt.price = fairValues[idx];
              pt.type = 'Fair Value';
              allPoints.push(pt);
            });
          }

          var hoveredPoint = null;
          var minDist = 20; // Raio de detecção

          for (var i = 0; i < allPoints.length; i++) {
            var pt = allPoints[i];
            var dist = Math.sqrt(Math.pow(mouseX - pt.x, 2) + Math.pow(mouseY - pt.y, 2));
            if (dist < minDist) {
              minDist = dist;
              hoveredPoint = pt;
            }
          }

          // Remover tooltip anterior
          var oldTooltip = document.querySelector('.curve-tooltip');
          if (oldTooltip) oldTooltip.remove();

          if (hoveredPoint) {
            canvas.style.cursor = 'pointer';

            var tooltip = document.createElement('div');
            tooltip.className = 'curve-tooltip';
            tooltip.innerHTML = '<strong>' + hoveredPoint.code + '</strong> (' + hoveredPoint.type + ')<br>$' + hoveredPoint.price.toFixed(2);
            tooltip.style.cssText = 'position: fixed; background: rgba(0,0,0,0.9); color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; z-index: 10000; pointer-events: none;';

            tooltip.style.left = (e.clientX + 10) + 'px';
            tooltip.style.top = (e.clientY - 10) + 'px';

            document.body.appendChild(tooltip);
          } else {
            canvas.style.cursor = 'default';
          }
        });

        canvas.addEventListener('mouseleave', function () {
          var tooltip = document.querySelector('.curve-tooltip');
          if (tooltip) tooltip.remove();
          canvas.style.cursor = 'default';
        });
      }

    } catch (e) { console.error('renderFuturesCurve error:', e); }
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
    } catch (e) { }
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
    script.onload = function () { setTimeout(decPending, 800); };
    script.onerror = function () { decPending(); };
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
    document.addEventListener('DOMContentLoaded', function () {
      // Remover modal de login se existir (não necessário em dashboard autenticado)
      try { var lm = document.getElementById('loginModal'); if (lm && lm.parentNode) lm.parentNode.removeChild(lm); } catch (_) { }
      try { document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(function (b) { b.style.display = 'none'; }); } catch (_) { }
      renderAll();
      refreshQuotes();
      setInterval(refreshQuotes, 60000);
    });
  } else {
    try { var lm2 = document.getElementById('loginModal'); if (lm2 && lm2.parentNode) lm2.parentNode.removeChild(lm2); } catch (_) { }
    try { document.querySelectorAll('[data-bs-target="#loginModal"]').forEach(function (b) { b.style.display = 'none'; }); } catch (_) { }
    renderAll();
    refreshQuotes();
    setInterval(refreshQuotes, 60000);
  }

  // Re-render ao trocar tema (apenas quando o tema realmente muda)
  var __lastTheme = getCurrentTheme();
  var __rerenderTimer = null;
  new MutationObserver(function (muts) {
    if (!muts.some(function (m) { return m.attributeName === 'class'; })) return;
    var th = getCurrentTheme();
    if (th !== __lastTheme) {
      __lastTheme = th;
      clearTimeout(__rerenderTimer);
      __rerenderTimer = setTimeout(function () {
        renderAll();
        refreshQuotes(); // Re-renderizar futuros com novo tema
      }, 600);
    }
  }).observe(document.documentElement, { attributes: true });

  // Re-renderizar gráficos ao redimensionar janela
  var __resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(__resizeTimer);
    __resizeTimer = setTimeout(function () {
      if (window.__lastFuturesData) {
        // Resetar progresso das animações para re-renderizar
        window.__futuresChartProgress = 1; // Sem animação no resize
        window.__futuresCurveProgress = 1;

        renderFuturesChart(window.__lastFuturesData);
        renderFuturesCurve(window.__lastFuturesData);
      }
    }, 300);
  });

  // ============================================================================
  // CLIENT LOGGING -> Monolog (servidor)
  // ============================================================================
  function clientLog(level, message, metadata) {
    try {
      fetch('/api/client-log', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ level: level || 'error', message: String(message || ''), meta: metadata || {} })
      });
    } catch (_) { }
  }
  window.addEventListener('error', function (e) {
    try { clientLog('error', e && e.message ? e.message : 'window.error', { source: e && e.filename, lineno: e && e.lineno, colno: e && e.colno }); } catch (_) { }
  });
  window.addEventListener('unhandledrejection', function (e) {
    try { clientLog('error', 'unhandledrejection', { reason: (e && e.reason && (e.reason.stack || e.reason.message || String(e.reason))) }); } catch (_) { }
  });

})();
