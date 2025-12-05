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

  function formatFullDateTime(row) {
    try {
      var tz = getUserTimezone();
      var date = null;
      var tUtc = row && row.time_utc ? String(row.time_utc) : '';
      var ts = row && row.timestamp ? row.timestamp : null;
      if (tUtc) {
        var norm = tUtc.replace(' UTC', 'Z');
        var d1 = new Date(norm);
        if (!isNaN(d1.getTime())) date = d1;
      }
      if (!date && (ts !== null && ts !== undefined && ts !== '')) {
        var n = Number(ts);
        if (!isNaN(n)) {
          if (n < 1e12) n = n * 1000;
          var d2 = new Date(n);
          if (!isNaN(d2.getTime())) date = d2;
        }
      }
      if (!date) return 'Data desconhecida';
      return date.toLocaleString('pt-BR', { timeZone: tz });
    } catch (_) { return 'Data desconhecida'; }
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
          { proName: "TVC:DXY", title: "DXY" },
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
      var goldMiners = json && Array.isArray(json.miners) ? json.miners : [];

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

      // Futuros e Miners agora são populados automaticamente pelo boot.js
      // Renderizar gráficos após boot.js carregar os dados
      setTimeout(function () {
        renderChartsFromBootData();
      }, 500);
    } catch (e) {
      // Manter valores anteriores
    }
  }

  // ============================================================================
  // Renderizar gráficos com dados do boot.js
  // ============================================================================
  function renderChartsFromBootData() {
    try {
      // Verificar se há dados de futuros no DOM
      var futuresRows = document.querySelectorAll('.tbody_futuros_ouro tr');
      if (futuresRows.length > 0) {
        // Extrair dados dos futuros das linhas do tbody
        var futuresData = [];
        futuresRows.forEach(function (row) {
          var cells = row.querySelectorAll('td');
          if (cells.length >= 3) {
            var code = cells[0].textContent.trim();
            var priceText = cells[1].textContent.trim();
            var pctText = cells[2].textContent.trim();

            var price = parseFloat(priceText.replace(/,/g, ''));
            var pct = parseFloat(pctText.replace(/%/g, '').replace(/,/g, '.'));

            if (!isNaN(price)) {
              futuresData.push({
                code: code,
                price: price,
                pct: isNaN(pct) ? null : pct
              });
            }
          }
        });

        if (futuresData.length > 0) {
          renderFuturesCurve(futuresData);
        }
      }

      // Renderizar gráfico TradingView dos Gold Miners
      if (document.getElementById('tv_gold_miners_widget')) {
        renderGoldMinersChart();
      }
    } catch (e) {
      console.error('renderChartsFromBootData error:', e);
    }
  }

  // ============================================================================
  // Renderizar gráfico de curva de futuros com Chart.js
  // ============================================================================
  function renderFuturesCurve(futuresData) {
    try {
      var canvas = document.getElementById('gc_futures_curve');
      if (!canvas) return;

      // Destruir gráfico anterior se existir
      if (window.__futuresCurveChart) {
        window.__futuresCurveChart.destroy();
      }

      var ctx = canvas.getContext('2d');

      // Extrair labels e valores
      var labels = futuresData.map(function (d) { return d.code; });
      var prices = futuresData.map(function (d) { return d.price; });

      // Configuração do gráfico
      window.__futuresCurveChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
            label: 'Preço',
            data: prices,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            pointRadius: 4,
            pointBackgroundColor: '#3b82f6',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            fill: true
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              padding: 12,
              titleFont: { size: 14 },
              bodyFont: { size: 13 },
              callbacks: {
                label: function (context) {
                  return 'Preço: ' + context.parsed.y.toFixed(2);
                }
              }
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                font: { size: 11 }
              }
            },
            y: {
              beginAtZero: false,
              grace: '10%',
              grid: {
                color: 'rgba(200, 200, 200, 0.2)',
                borderDash: [5, 5]
              },
              ticks: {
                font: { size: 10 },
                callback: function (value) {
                  return '$' + value.toFixed(2);
                }
              }
            }
          }
        }
      });
    } catch (e) {
      console.error('renderFuturesCurve error:', e);
    }
  }

  // ============================================================================
  // GRID FUTUROS (GC1! .. GC7!) + MÉDIA - Card único com gráficos interativos
  // ============================================================================
  function renderFuturesGrid(items, avg) {
    try {
      var wrap = document.getElementById('gold_futures_grid');
      if (!wrap) return;
      wrap.innerHTML = '';

      // Organizar dados por código
      var order = ['GC1!', 'GC2!', 'GC3!', 'GC4!', 'GC5!', 'GC6!', 'GC7!'];
      var byCode = {};
      (items || []).forEach(function (it) { if (it && it.code) byCode[String(it.code).toUpperCase()] = it; });

      // Preparar dados para o card
      var futuresData = [];
      var totalPct = 0;
      var countPct = 0;

      for (var i = 0; i < order.length; i++) {
        var code = order[i];
        var item = byCode[code];
        var pct = item ? toNumber(item.pcp ?? item.pc) : null;
        var price = item ? (item.last ?? item.last_numeric ?? '--') : '--';
        var nome = item ? (item.nome || item.apelido || code) : code;

        // Calcular variação nominal
        var nominalChange = '--';
        var lastNum = item ? toNumber(item.last ?? item.last_numeric) : null;
        var closeNum = item ? toNumber(item.last_close) : null;
        if (lastNum !== null && closeNum !== null) {
          var change = lastNum - closeNum;
          nominalChange = (change >= 0 ? '+' : '') + change.toFixed(2);
        }

        futuresData.push({
          code: code,
          item: item,
          pct: pct,
          price: price,
          nome: nome,
          nominalChange: nominalChange
        });

        if (pct !== null) {
          totalPct += pct;
          countPct++;
        }
      }

      // Calcular média de oscilação
      var avgPct = countPct > 0 ? (totalPct / countPct) : null;
      var avgPctText = avgPct !== null ? formatPercent(avgPct) : '--';
      var avgPctClass = avgPct > 0 ? 'text-success' : (avgPct < 0 ? 'text-danger' : 'text-muted');

      // Criar card único
      var card = document.createElement('div');
      card.className = '';

      var avgTxt = (avg !== null && avg !== undefined) ? avg.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '--';

      // Forçar ordenação correta dos dados para exibição
      var sortedFuturesData = [];
      var orderMap = {};
      futuresData.forEach(function (d) { orderMap[d.code] = d; });
      ['GC1!', 'GC2!', 'GC3!', 'GC4!', 'GC5!', 'GC6!', 'GC7!'].forEach(function (code) {
        if (orderMap[code]) sortedFuturesData.push(orderMap[code]);
      });
      // Se faltar algum (improvável), adicionar no final
      futuresData.forEach(function (d) {
        if (!sortedFuturesData.includes(d)) sortedFuturesData.push(d);
      });

      // HTML do card com tabela e gráfico
      var html = '<div class="card w-100 card_indices h-100">'
        + '<div class="card-header title-card py-2 px-3">'
        + '<span style="font-size: 18px !important; font-weight: 700 !important;">Futuros de Ouro *</span>'
        + '<div class="d-inline-block ms-3">'
        + '<span class="small text-muted">Média Preço: <span class="fw-semibold">' + avgTxt + '</span></span>'
        + '<span class="small text-muted ms-3">Média Osc.: <span class="fw-semibold">' + avgPctText + '</span></span>'
        + '</div>'
        + '</div>'
        + '<div class="card-body p-0">'
        + '<div class="row">'
        + '<div class="col-12">'
        + '<table class="table table-sm table-borderless mb-3 futures-table">'
        + '<thead>'
        + '<tr>'
        + '<th class="text-muted small fw-normal" style="width: 60px;">Código</th>'
        + '<th class="text-muted small fw-normal text-end" style="width: 90px;">Preço</th>'
        + '<th class="text-muted small fw-normal text-end" style="width: 70px;">Var. %</th>'
        + '<th class="text-muted small fw-normal text-end" style="width: 60px;">Hora</th>'
        + '</tr>'
        + '</thead>'
        + '<tbody>';

      // Adicionar linhas da tabela com tooltips (usando sortedFuturesData)
      for (var j = 0; j < sortedFuturesData.length; j++) {
        var fd = sortedFuturesData[j];
        var pctText = fd.pct !== null ? formatPercent(fd.pct) : '--';
        var cls = fd.pct > 0 ? 'text-success' : (fd.pct < 0 ? 'text-danger' : 'text-muted');
        var color = fd.pct > 0 ? '#10b981' : (fd.pct < 0 ? '#ef4444' : '');
        var timeText = formatTime(fd.item);
        var fullDate = formatFullDateTime(fd.item);
        html += '<tr>'
          + '<td class="fw-semibold has-tooltip" data-tooltip-text="' + fd.nome + '" style="width: 60px; cursor: help;">' + fd.code + '</td>'
          + '<td class="text-end" style="width: 90px;">' + fd.price + '</td>'
          + '<td class="text-end fw-semibold ' + cls + ' has-tooltip" data-tooltip-text="' + fd.nominalChange + '" style="width: 70px; cursor: help; color: ' + color + ' !important;">' + pctText + '</td>'
          + '<td class="text-end text-muted small has-tooltip" data-tooltip-text="' + fullDate + '" style="width: 60px; cursor: help;">' + timeText + '</td>'
          + '</tr>';
      }

      html += '</tbody></table>'
        + '<div class="mb-2">'
        + '<div class="text-center text-muted small fw-semibold">Curva de Futuros (Term Structure)</div>'
        + '</div>'
        + '<canvas id="gc_futures_curve" style="height: 180px;"></canvas>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>';

      card.innerHTML = html;
      wrap.appendChild(card);

      // Ativar tooltips customizados
      setTimeout(function () {
        activateFuturesTooltips();
      }, 50);

      // Renderizar gráfico após inserir no DOM
      setTimeout(function () {
        window.__lastFuturesData = futuresData;
        renderFuturesCurve(futuresData);
      }, 100);

    } catch (e) { console.error('renderFuturesGrid error:', e); }
  }

  // ============================================================================
  // GRID GOLD MINERS - Card com gráfico TradingView
  // ============================================================================
  function renderGoldMinersGrid(items) {
    try {
      var wrap = document.getElementById('gold_miners_grid');
      if (!wrap) return;
      wrap.innerHTML = '';

      // Preparar dados
      var minersData = [];
      var totalPct = 0;
      var countPct = 0;

      (items || []).forEach(function (item) {
        var pct = item ? toNumber(item.pcp ?? item.pc) : null;
        var price = item ? (item.last ?? item.last_numeric ?? '--') : '--';
        var code = item ? (item.code || item.Code || '--') : '--';
        var nome = item ? (item.apelido || item.nome || item.Name || code) : code;

        // Calcular variação nominal
        var nominalChange = '--';
        var lastNum = item ? toNumber(item.last ?? item.last_numeric) : null;
        var closeNum = item ? toNumber(item.last_close) : null;
        if (lastNum !== null && closeNum !== null) {
          var change = lastNum - closeNum;
          nominalChange = (change >= 0 ? '+' : '') + change.toFixed(2);
        }

        minersData.push({
          code: code,
          item: item,
          pct: pct,
          price: price,
          nome: nome,
          nominalChange: nominalChange
        });

        if (pct !== null) {
          totalPct += pct;
          countPct++;
        }
      });

      // Ordenação Personalizada: GDX (NYSE) -> GDX (LSE) -> GDX (ASX) -> Restante
      minersData.sort(function (a, b) {
        var nA = String(a.nome || '').toUpperCase();
        var nB = String(b.nome || '').toUpperCase();

        // Função para determinar prioridade de ordenação
        function getPriority(nome) {
          if (nome.indexOf('GDX') >= 0 && nome.indexOf('NYSE') >= 0) return 1; // Primeiro
          if (nome.indexOf('GDX') >= 0 && nome.indexOf('LSE') >= 0) return 2;  // Segundo
          if (nome.indexOf('GDX') >= 0 && nome.indexOf('ASX') >= 0) return 3;  // Terceiro
          if (nome.indexOf('GDX') >= 0) return 4; // Outros GDX
          return 5; // Restante
        }

        var prioA = getPriority(nA);
        var prioB = getPriority(nB);

        return prioA - prioB;
      });

      // Calcular média de oscilação e preço
      var avgPct = countPct > 0 ? (totalPct / countPct) : null;
      var avgPctText = avgPct !== null ? formatPercent(avgPct) : '--';

      // Calcular média de preço
      var totalPrice = 0;
      var countPrice = 0;
      for (var i = 0; i < minersData.length; i++) {
        var priceNum = toNumber(minersData[i].price);
        if (priceNum !== null) {
          totalPrice += priceNum;
          countPrice++;
        }
      }
      var avgPrice = countPrice > 0 ? (totalPrice / countPrice) : null;
      var avgPriceText = avgPrice !== null ? avgPrice.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '--';

      // Criar card único
      var card = document.createElement('div');
      card.className = '';

      // HTML do card com tabela e gráfico TradingView
      var html = '<div class="card w-100 card_indices h-100">'
        + '<div class="card-header title-card py-2 px-3">'
        + '<span style="font-size: 18px !important; font-weight: 700 !important;">Gold Miners *</span>'
        + '<div class="d-inline-block ms-3">'
        + '<span class="small text-muted">Média Preço: <span class="fw-semibold">' + avgPriceText + '</span></span>'
        + '<span class="small text-muted ms-3">Média Osc.: <span class="fw-semibold">' + avgPctText + '</span></span>'
        + '</div>'
        + '</div>'
        + '<div class="card-body p-0">'
        + '<div class="row">'
        + '<div class="col-12">'
        + '<table class="table table-sm table-borderless mb-3 miners-table">'
        + '<thead>'
        + '<tr>'
        + '<th class="text-muted small fw-normal" style="width: 60px;">Código</th>'
        + '<th class="text-muted small fw-normal text-end" style="width: 90px;">Preço</th>'
        + '<th class="text-muted small fw-normal text-end" style="width: 70px;">Var. %</th>'
        + '<th class="text-muted small fw-normal text-end" style="width: 60px;">Hora</th>'
        + '</tr>'
        + '</thead>'
        + '<tbody>';

      // Adicionar linhas da tabela
      for (var j = 0; j < minersData.length; j++) {
        var md = minersData[j];
        var pctText = md.pct !== null ? formatPercent(md.pct) : '--';
        var cls = md.pct > 0 ? 'text-success' : (md.pct < 0 ? 'text-danger' : 'text-muted');
        var color = md.pct > 0 ? '#10b981' : (md.pct < 0 ? '#ef4444' : '');
        var timeText = formatTime(md.item);
        var fullDate = formatFullDateTime(md.item);
        html += '<tr>'
          + '<td class="fw-semibold has-tooltip" data-tooltip-text="' + md.nome + '" style="width: 60px; cursor: help;">' + md.code + '</td>'
          + '<td class="text-end" style="width: 90px;">' + md.price + '</td>'
          + '<td class="text-end fw-semibold ' + cls + ' has-tooltip" data-tooltip-text="' + md.nominalChange + '" style="width: 70px; cursor: help; color: ' + color + ' !important;">' + pctText + '</td>'
          + '<td class="text-end text-muted small has-tooltip" data-tooltip-text="' + fullDate + '" style="width: 60px; cursor: help;">' + timeText + '</td>'
          + '</tr>';
      }

      html += '</tbody></table>'
        + '<div class="mb-2">'
        + '<div class="text-center text-muted small fw-semibold">GDX vs GOLD</div>'
        + '</div>'
        + '<div id="tv_gold_miners_widget" style="height: 280px;"></div>'
        + '</div>'
        + '</div>'
        + '</div>'
        + '</div>';

      card.innerHTML = html;
      wrap.appendChild(card);

      // Ativar tooltips customizados
      setTimeout(function () {
        activateMinersTooltips();
      }, 50);

      // Renderizar gráfico TradingView após inserir no DOM
      setTimeout(function () {
        renderGoldMinersChart();
      }, 100);

    } catch (e) { console.error('renderGoldMinersGrid error:', e); }
  }

  // Ativar tooltips customizados para a tabela de gold miners
  function activateMinersTooltips() {
    try {
      document.querySelectorAll('.miners-table .has-tooltip').forEach(function (el) {
        var tooltipText = el.getAttribute('data-tooltip-text');
        if (!tooltipText || tooltipText === '--' || tooltipText.trim() === '') return;

        el.addEventListener('mouseenter', function (e) {
          var existingTooltip = document.querySelector('.custom-tooltip');
          if (existingTooltip) existingTooltip.remove();

          var tooltip = document.createElement('div');
          tooltip.className = 'custom-tooltip';
          tooltip.textContent = tooltipText;
          tooltip.style.cssText = 'position: fixed; background: rgba(0,0,0,0.9); color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; z-index: 10000; pointer-events: none; white-space: nowrap; box-shadow: 0 2px 8px rgba(0,0,0,0.3);';

          document.body.appendChild(tooltip);

          var rect = el.getBoundingClientRect();
          var tooltipRect = tooltip.getBoundingClientRect();
          var left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
          var windowWidth = window.innerWidth;
          if (left < 10) left = 10;
          if (left + tooltipRect.width > windowWidth - 10) {
            left = windowWidth - tooltipRect.width - 10;
          }

          var top = rect.top - tooltipRect.height - 10;
          if (top < 10) {
            top = rect.bottom + 10;
          }

          tooltip.style.left = left + 'px';
          tooltip.style.top = top + 'px';

          el._customTooltip = tooltip;
        });

        el.addEventListener('mouseleave', function () {
          if (el._customTooltip) {
            el._customTooltip.remove();
            el._customTooltip = null;
          }
        });
      });
    } catch (e) { console.error('activateMinersTooltips error:', e); }
  }

  // Renderizar gráfico TradingView do GDX
  function renderGoldMinersChart() {
    try {
      var el = document.getElementById('tv_gold_miners_widget');
      if (!el) return;
      el.innerHTML = '';

      var theme = getCurrentTheme();
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
      copyright.innerHTML = '<a href="https://br.tradingview.com/symbols/AMEX-GDX/" rel="noopener nofollow" target="_blank"><span class="blue-text">Track all markets on TradingView</span></a>';
      wrapper.appendChild(copyright);

      var script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = 'https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js';
      script.async = true;

      var config = {
        allow_symbol_change: false,
        calendar: false,
        details: false,
        hide_side_toolbar: true,
        hide_top_toolbar: true,
        hide_legend: false,
        hide_volume: true,
        hotlist: false,
        interval: "15",
        locale: "br",
        save_image: false,
        style: "3",
        symbol: "AMEX:GDX",
        theme: theme,
        timezone: "Etc/UTC",
        backgroundColor: bgColor,
        gridColor: "rgba(242, 242, 242, 0)",
        watchlist: [],
        withdateranges: false,
        compareSymbols: [
          {
            symbol: "TVC:GOLD",
            position: "NewPriceScale"
          }
        ],
        studies: [],
        autosize: true
      };

      script.innerHTML = JSON.stringify(config);
      wrapper.appendChild(script);
      el.appendChild(wrapper);
      incPending();
      script.onload = function () { setTimeout(decPending, 800); };
      script.onerror = function () { decPending(); };
    } catch (e) { console.error('renderGoldMinersChart error:', e); }
  }

  // Ativar tooltips customizados para a tabela de futuros
  function activateFuturesTooltips() {
    try {
      document.querySelectorAll('.futures-table .has-tooltip').forEach(function (el) {
        var tooltipText = el.getAttribute('data-tooltip-text');
        if (!tooltipText || tooltipText === '--' || tooltipText.trim() === '') return;

        el.addEventListener('mouseenter', function (e) {
          // Remover qualquer tooltip existente
          var existingTooltip = document.querySelector('.custom-tooltip');
          if (existingTooltip) existingTooltip.remove();

          var tooltip = document.createElement('div');
          tooltip.className = 'custom-tooltip';
          tooltip.textContent = tooltipText;
          tooltip.style.cssText = 'position: fixed; background: rgba(0,0,0,0.9); color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; z-index: 10000; pointer-events: none; white-space: nowrap; box-shadow: 0 2px 8px rgba(0,0,0,0.3);';

          document.body.appendChild(tooltip);

          // Calcular posição após inserir no DOM (para ter dimensões corretas)
          var rect = el.getBoundingClientRect();
          var tooltipRect = tooltip.getBoundingClientRect();

          // Centralizar horizontalmente em relação ao elemento
          var left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);

          // Garantir que não saia da tela
          var windowWidth = window.innerWidth;
          if (left < 10) left = 10;
          if (left + tooltipRect.width > windowWidth - 10) {
            left = windowWidth - tooltipRect.width - 10;
          }

          // Posicionar acima do elemento
          var top = rect.top - tooltipRect.height - 10;

          // Se não couber em cima, mostrar embaixo
          if (top < 10) {
            top = rect.bottom + 10;
          }

          tooltip.style.left = left + 'px';
          tooltip.style.top = top + 'px';

          el._customTooltip = tooltip;
        });

        el.addEventListener('mouseleave', function () {
          if (el._customTooltip) {
            el._customTooltip.remove();
            el._customTooltip = null;
          }
        });
      });
    } catch (e) { console.error('activateFuturesTooltips error:', e); }
  }

  // Renderizar gráfico de barras animado e interativo dos futuros
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
      var values = data.map(function (d) { return d.pct !== null ? d.pct : 0; });
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
      var hoverColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';

      // Limpar canvas
      ctx.clearRect(0, 0, width, height);

      // Desenhar linhas de grade horizontais
      ctx.strokeStyle = gridColor;
      ctx.lineWidth = 1;
      ctx.setLineDash([2, 3]);
      for (var g = -2; g <= 2; g++) {
        if (g === 0) continue;
        var gy = zeroY + (g * scale * (maxVal / 2));
        ctx.beginPath();
        ctx.moveTo(padding.left, gy);
        ctx.lineTo(width - padding.right, gy);
        ctx.stroke();
      }
      ctx.setLineDash([]);

      // Desenhar linha zero (mais destacada)
      ctx.beginPath();
      ctx.strokeStyle = isDark ? 'rgba(255,255,255,0.2)' : 'rgba(0,0,0,0.2)';
      ctx.lineWidth = 2;
      ctx.moveTo(padding.left, zeroY);
      ctx.lineTo(width - padding.right, zeroY);
      ctx.stroke();

      // Calcular posições das barras
      var barWidth = chartWidth / data.length * 0.65;
      var barSpacing = chartWidth / data.length;

      // Animação: usar progresso salvo ou iniciar nova
      if (!window.__futuresChartProgress) {
        window.__futuresChartProgress = 0;
      }
      var progress = Math.min(window.__futuresChartProgress, 1);
      if (progress < 1) {
        window.__futuresChartProgress += 0.08;
        requestAnimationFrame(function () { renderFuturesChart(data); });
      }

      // Easing function para animação suave
      var easeOutCubic = function (t) { return 1 - Math.pow(1 - t, 3); };
      var animProgress = easeOutCubic(progress);

      // Desenhar barras com animação
      for (var i = 0; i < data.length; i++) {
        var pct = data[i].pct !== null ? data[i].pct : 0;
        var barHeight = Math.abs(pct) * scale * animProgress;
        var x = padding.left + i * barSpacing + (barSpacing - barWidth) / 2;
        var y = pct >= 0 ? (zeroY - barHeight) : zeroY;

        // Gradiente para barras
        var gradient = ctx.createLinearGradient(x, pct >= 0 ? y : zeroY, x, pct >= 0 ? zeroY : y + barHeight);
        var baseColor = pct >= 0 ? positiveColor : negativeColor;
        gradient.addColorStop(0, baseColor);
        gradient.addColorStop(1, baseColor + 'aa'); // mais transparente embaixo

        ctx.fillStyle = gradient;
        ctx.fillRect(x, y, barWidth, barHeight);

        // Borda sutil nas barras
        ctx.strokeStyle = pct >= 0 ? '#059669' : '#dc2626';
        ctx.lineWidth = 1;
        ctx.strokeRect(x, y, barWidth, barHeight);

        // Label do código
        ctx.fillStyle = textColor;
        ctx.font = 'bold 11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(data[i].code, x + barWidth / 2, height - padding.bottom + 20);

        // Valor percentual acima/abaixo da barra
        if (progress >= 0.8) { // mostrar após animação avançar
          var pctDisplay = formatPercent(pct);
          ctx.fillStyle = baseColor;
          ctx.font = 'bold 10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
          var labelY = pct >= 0 ? (y - 8) : (y + barHeight + 16);
          ctx.fillText(pctDisplay, x + barWidth / 2, labelY);
        }
      }

      // Labels dos eixos
      ctx.fillStyle = textColor;
      ctx.font = '12px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
      ctx.textAlign = 'right';
      ctx.fillText('+' + maxVal.toFixed(1) + '%', padding.left - 8, padding.top + 15);
      ctx.fillText('-' + maxVal.toFixed(1) + '%', padding.left - 8, height - padding.bottom - 5);

      // Interatividade via mousemove
      if (!canvas.__hasHoverListener) {
        canvas.__hasHoverListener = true;
        canvas.addEventListener('mousemove', function (e) {
          var rect = canvas.getBoundingClientRect();
          var mouseX = e.clientX - rect.left;
          var mouseY = e.clientY - rect.top;

          // Verificar se está sobre alguma barra
          var hoveredIndex = -1;
          for (var i = 0; i < data.length; i++) {
            var x = padding.left + i * barSpacing + (barSpacing - barWidth) / 2;
            if (mouseX >= x && mouseX <= x + barWidth) {
              hoveredIndex = i;
              break;
            }
          }

          if (hoveredIndex >= 0) {
            canvas.style.cursor = 'pointer';
            // Renderizar novamente com highlight (simplificado: apenas mudar cursor)
          } else {
            canvas.style.cursor = 'default';
          }
        });
      }

    } catch (e) { console.error('renderFuturesChart error:', e); }
  }

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
      var padding = { top: 10, right: 30, bottom: 40, left: 55 };
      var chartWidth = width - padding.left - padding.right;
      var chartHeight = height - padding.top - padding.bottom;

      // Extrair preços numéricos
      var prices = [];
      for (var i = 0; i < data.length; i++) {
        var p = toNumber(data[i].price);
        prices.push(p !== null ? p : null);
      }

      // Encontrar min/max para escala (ignorar nulls)
      var validPrices = prices.filter(function (p) { return p !== null; });
      if (validPrices.length === 0) return; // Sem dados válidos

      var minPrice = Math.min.apply(null, validPrices);
      var maxPrice = Math.max.apply(null, validPrices);
      var priceRange = maxPrice - minPrice;
      if (priceRange === 0) priceRange = maxPrice * 0.01; // Evitar divisão por zero
      var priceScale = chartHeight / (priceRange * 1.2);

      // Cores baseadas no tema
      var isDark = document.documentElement.classList.contains('dark-blue') ||
        document.documentElement.classList.contains('all-black');
      var gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)';
      var textColor = isDark ? '#9ca3af' : '#6b7280';
      var lineColor = '#3b82f6'; // Azul vibrante
      var fillColor = isDark ? 'rgba(59, 130, 246, 0.1)' : 'rgba(59, 130, 246, 0.15)';
      var dotColor = '#1d4ed8';

      // Limpar canvas
      ctx.clearRect(0, 0, width, height);

      // Desenhar grade horizontal
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

        // Label do preço
        var priceLabel = maxPrice - (priceRange * 1.2 / gridLines) * g;
        ctx.fillStyle = textColor;
        ctx.font = '10px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
        ctx.textAlign = 'right';
        ctx.fillText('$' + priceLabel.toFixed(2), padding.left - 8, gy + 4);
      }
      ctx.setLineDash([]);

      // Animação: usar progresso salvo ou iniciar nova
      if (!window.__futuresCurveProgress) {
        window.__futuresCurveProgress = 0;
      }
      var progress = Math.min(window.__futuresCurveProgress, 1);
      if (progress < 1) {
        window.__futuresCurveProgress += 0.06;
        requestAnimationFrame(function () { renderFuturesCurve(data); });
      }

      // Easing
      var easeOutQuad = function (t) { return t * (2 - t); };
      var animProgress = easeOutQuad(progress);

      // Calcular pontos da curva
      var points = [];
      var xStep = chartWidth / (data.length - 1);
      for (var i = 0; i < data.length; i++) {
        var price = prices[i];
        if (price === null) continue;

        var x = padding.left + i * xStep;
        var normalizedPrice = (maxPrice - price) / (priceRange * 1.2);
        var y = padding.top + normalizedPrice * chartHeight;

        // Aplicar animação: revelar da esquerda para direita
        if (i / (data.length - 1) <= animProgress) {
          points.push({ x: x, y: y, price: price, code: data[i].code });
        }
      }

      if (points.length < 2) return; // Precisa de pelo menos 2 pontos

      // Desenhar área preenchida sob a curva
      ctx.beginPath();
      ctx.moveTo(points[0].x, height - padding.bottom);
      ctx.lineTo(points[0].x, points[0].y);
      for (var i = 1; i < points.length; i++) {
        ctx.lineTo(points[i].x, points[i].y);
      }
      ctx.lineTo(points[points.length - 1].x, height - padding.bottom);
      ctx.closePath();
      ctx.fillStyle = fillColor;
      ctx.fill();

      // Desenhar linha da curva
      ctx.beginPath();
      ctx.moveTo(points[0].x, points[0].y);
      for (var i = 1; i < points.length; i++) {
        ctx.lineTo(points[i].x, points[i].y);
      }
      ctx.strokeStyle = lineColor;
      ctx.lineWidth = 3;
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.stroke();

      // Desenhar pontos (dots)
      for (var i = 0; i < points.length; i++) {
        var pt = points[i];

        // Círculo externo (borda branca)
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 5, 0, Math.PI * 2);
        ctx.fillStyle = isDark ? '#1f2937' : '#ffffff';
        ctx.fill();

        // Círculo interno (cor)
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, 3.5, 0, Math.PI * 2);
        ctx.fillStyle = dotColor;
        ctx.fill();
      }

      // Labels dos contratos (eixo X)
      ctx.fillStyle = textColor;
      ctx.font = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
      ctx.textAlign = 'center';
      for (var i = 0; i < data.length; i++) {
        var x = padding.left + i * xStep;
        ctx.fillText(data[i].code, x, height - padding.bottom + 20);
      }

      // Interatividade: mostrar tooltip ao passar sobre pontos
      if (!canvas.__hasHoverListener) {
        canvas.__hasHoverListener = true;
        canvas.addEventListener('mousemove', function (e) {
          var rect = canvas.getBoundingClientRect();
          var mouseX = e.clientX - rect.left;
          var mouseY = e.clientY - rect.top;

          // Verificar proximidade com algum ponto
          var hoveredPoint = null;
          for (var i = 0; i < points.length; i++) {
            var pt = points[i];
            var dist = Math.sqrt(Math.pow(mouseX - pt.x, 2) + Math.pow(mouseY - pt.y, 2));
            if (dist < 10) {
              hoveredPoint = pt;
              break;
            }
          }

          // Remover tooltip anterior
          var oldTooltip = document.querySelector('.curve-tooltip');
          if (oldTooltip) oldTooltip.remove();

          if (hoveredPoint) {
            canvas.style.cursor = 'pointer';

            // Criar tooltip
            var tooltip = document.createElement('div');
            tooltip.className = 'curve-tooltip';
            tooltip.innerHTML = '<strong>' + hoveredPoint.code + '</strong><br>$' + hoveredPoint.price.toFixed(2);
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
        symbol: 'TVC:GOLD',
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
        watchlist: ['OANDA:XAUUSD', 'FOREXCOM:XAUUSD', 'TVC:GOLD', 'COMEX:GC1!'],
        studies: [
          'STD;Bollinger_Bands',
          'STD;Cumulative%1Volume%1Delta'
        ]
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
    renderComparison('tv_compare_gold_dxy', 'OANDA:XAUUSD', 'TVC:DXY');
    renderComparison('tv_compare_gold_btc', 'OANDA:XAUUSD', 'BITSTAMP:BTCUSD');

    // Seção de Razões
    renderRatio('tv_ratio_gold_miners', 'OANDA:XAUUSD/AMEX:GDX');
    renderRatio('tv_ratio_gold_btc', 'OANDA:XAUUSD/COINBASE:BTCUSD');

    // Seção de Indicadores Técnicos
    renderTechnical('tv_tech_gold', 'OANDA:XAUUSD');
    renderTechnical('tv_tech_dxy', 'TVC:DXY');
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
