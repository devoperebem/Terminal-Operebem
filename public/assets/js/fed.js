(function(){
  function fmtPct(n){ try{ var v = Number(n); if(!Number.isFinite(v)) return '-'; return (v*100).toFixed(1) + '%'; }catch(e){ return '-'; } }
  function clear(el){ while(el && el.firstChild){ el.removeChild(el.firstChild); } }
  function themeIsDark(){ var c = document.documentElement.classList; return c.contains('dark-blue') || c.contains('all-black'); }
  let hcReady;
  function ensureHighcharts(){
    if (window.Highcharts) return Promise.resolve();
    if (!hcReady) {
      hcReady = new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = 'https://code.highcharts.com/highcharts.js';
        s.async = true; s.onload = resolve; s.onerror = reject; document.head.appendChild(s);
      });
    }
    return hcReady;
  }
  function buildRow(range, prob){
    var tr = document.createElement('tr');
    var td1 = document.createElement('td'); td1.textContent = range;
    var td2 = document.createElement('td');
    var bar = document.createElement('div'); bar.className = 'bar'; var span = document.createElement('span'); span.style.width = (prob*100).toFixed(1) + '%'; bar.appendChild(span); td2.appendChild(bar);
    var td3 = document.createElement('td'); td3.className = 'text-end'; td3.textContent = fmtPct(prob);
    tr.appendChild(td1); tr.appendChild(td2); tr.appendChild(td3);
    return tr;
  }
  function renderProbChart(dist){
    var el = document.getElementById('fed-prob-chart'); if (!el) return;
    var cats = []; var vals = [];
    dist.forEach(function(d){ cats.push(d.range || d.target || ''); var p = Number(d.probability||0); vals.push(Number.isFinite(p) ? +(p*100).toFixed(2) : 0); });
    ensureHighcharts().then(function(){
      var dark = themeIsDark();
      window.Highcharts.chart('fed-prob-chart', {
        chart: { type: 'column', backgroundColor: 'transparent' },
        title: { text: null },
        xAxis: { categories: cats, labels: { style: { color: dark ? '#e5e7eb' : '#111827' } } },
        yAxis: { min: 0, max: 100, title: { text: null }, gridLineColor: dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)' },
        legend: { enabled: false }, credits: { enabled: false }, exporting: { enabled: false },
        series: [{ name: 'Probabilidade (%)', data: vals, color: '#0d6efd' }]
      });
    });
  }
  function prepPathChart(){ var el = document.getElementById('fed-path-chart'); if (!el) return; el.innerHTML = '<div class="text-muted">Aguardando back-end pyfedwatch (caminho implícito).</div>'; }
  function prepCalendarChart(){ var el = document.getElementById('fed-calendar-chart'); if (!el) return; el.innerHTML = '<div class="text-muted">Aguardando back-end pyfedwatch (reuniões FOMC).</div>'; }
  async function load(month){
    var table = document.getElementById('fed-table');
    var endpoint = (table && table.getAttribute('data-endpoint')) ? table.getAttribute('data-endpoint') : '/api/fed/probabilities';
    var u = endpoint + (month ? ('?date='+encodeURIComponent(month)) : '');
    var err = document.getElementById('fed-error'); if (err) { err.classList.add('d-none'); }
    try{
      var r = await fetch(u, { credentials: 'same-origin' });
      if (!r.ok){ throw new Error('HTTP ' + r.status); }
      var j = await r.json();
      if (!j || !j.ok) throw new Error('upstream');
      var data = j.data || {};
      var tb = table ? table.querySelector('tbody') : null;
      if (!tb) return;
      clear(tb);
      var dist = (data && data.probabilities) || data.targetProbabilities || [];
      var total = 0; dist.forEach(function(d){ total += Number(d.probability||0); });
      dist.forEach(function(d){ var p = Number(d.probability||0); if (total>0) p = p/total; var range = (d.range || d.target || ''); tb.appendChild(buildRow(range, p)); });
      var sum = dist.reduce(function(acc, d){ return acc + Number(d.probability||0); }, 0);
      var sumPct = (sum>0) ? ((sum/total)*100).toFixed(1) : '100.0';
      var sumEl = document.getElementById('fed-summary'); if (sumEl) sumEl.textContent = 'Distribuição total: ' + sumPct + '%';
      renderProbChart(dist);
      prepPathChart();
      prepCalendarChart();
    }catch(e){ if (err){ err.textContent = 'Falha ao carregar dados do FedWatch'; err.classList.remove('d-none'); } }
  }
  function initMonths(){
    var sel = document.getElementById('fed-month'); if (!sel) return;
    // preenche alguns meses futuros (CME normalmente mostra opções)
    var d = new Date(); for (var i=0;i<7;i++){ var m = new Date(d.getFullYear(), d.getMonth()+i, 1); var y = m.getFullYear(); var mm = String(m.getMonth()+1).padStart(2, '0'); var opt = document.createElement('option'); opt.value = y + '-' + mm; opt.textContent = y - 2000 + '-' + mm; sel.appendChild(opt); }
    sel.addEventListener('change', function(){ var v = sel.value || ''; load(v || undefined); });
  }
  document.addEventListener('DOMContentLoaded', function(){ initMonths(); load(); });
})();
