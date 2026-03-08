(function(){
  const CSV_BASE = window.BT_CSV_BASE || '/assets/storage/google-trends/b3-crypto-rendafixa/7-days';

  function qs(sel){ return document.querySelector(sel); }

  function fetchText(url){ return fetch(url, { cache: 'no-cache' }).then(r => { if(!r.ok) throw new Error('HTTP '+r.status); return r.text(); }); }

  function parseCSV(text){
    const rows = [];
    let row = [], field = '', inQ = false;
    const s = (text||'').replace(/\r\n?/g, '\n');
    for (let i=0;i<s.length;i++){
      const c = s[i];
      if (c === '"') {
        if (inQ && s[i+1] === '"') { field += '"'; i++; }
        else { inQ = !inQ; }
        continue;
      }
      if (!inQ && c === ',') { row.push(field); field=''; continue; }
      if (!inQ && c === '\n') { row.push(field); rows.push(row); row=[]; field=''; continue; }
      field += c;
    }
    if (field.length || row.length) { row.push(field); rows.push(row); }
    return rows;
  }

  function toNumber(x){ if (x==null) return null; const s=String(x).trim().replace('%','').replace(',','.'); const n=parseFloat(s); return Number.isFinite(n)?n:null; }

  function parseTimeline(rows){
    // skip headers like "Categoria..." and empty lines, find header with series names
    let headerIdx = -1;
    for (let i=0;i<rows.length;i++){
      const r = rows[i]; if (!r || r.length<2) continue; const a=(r[0]||'').toLowerCase();
      if (a === 'semana' || a === 'dia' || a === 'week' || a === 'date' || a.includes('tempo')) { headerIdx=i; break; }
      if (r[0] && (r[0].includes('-') || /\d{4}/.test(r[0]))) { headerIdx=i-1; break; }
    }
    if (headerIdx < 0) headerIdx = 0;
    const header = rows[headerIdx] || [];
    const nameA = header[1] || 'B3';
    const nameB = header[2] || 'Renda fixa';
    const nameC = header[3] || 'Criptomoeda';
    const labels = [], a=[], b=[], c=[];
    for (let i=headerIdx+1;i<rows.length;i++){
      const r = rows[i]; if (!r || r.length<4) continue;
      const v1 = toNumber(r[1]); const v2 = toNumber(r[2]); const v3 = toNumber(r[3]);
      if (v1==null && v2==null && v3==null) continue;
      labels.push(r[0]); a.push(v1||0); b.push(v2||0); c.push(v3||0);
    }
    return { labels, series: [ { label: nameA, data: a }, { label: nameB, data: b }, { label: nameC, data: c } ] };
  }

  function parseGeo(rows){
    // find header with 'Região'
    let headerIdx = rows.findIndex(r => (r[0]||'').toLowerCase().includes('regi'));
    if (headerIdx < 0) headerIdx = 0;
    const header = rows[headerIdx] || [];
    const nameA = header[1] || 'B3';
    const nameB = header[2] || 'Renda fixa';
    const nameC = header[3] || 'Criptomoeda';
    const list = [];
    for (let i=headerIdx+1;i<rows.length;i++){
      const r = rows[i]; if (!r || r.length<2) continue; const reg = (r[0]||'').trim(); if (!reg) continue;
      const v1 = toNumber(r[1])||0; const v2=toNumber(r[2])||0; const v3=toNumber(r[3])||0;
      list.push({ reg, a:v1, b:v2, c:v3 });
    }
    // top 15 by A (B3)
    list.sort((x,y)=>y.a-x.a);
    const top = list.slice(0, 15);
    const labels = top.map(x=>x.reg);
    return { labels, a: top.map(x=>x.a), b: top.map(x=>x.b), c: top.map(x=>x.c), nameA, nameB, nameC };
  }

  function mkLineChart(ctx, labels, datasets){
    if (window._bt_line) window._bt_line.destroy();
    window._bt_line = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets: datasets.map((d,i)=>({ label: d.label, data: d.data, borderColor: ['#2A54FF','#22c55e','#ef4444'][i%3], backgroundColor: 'transparent', tension: .25, pointRadius: 0 })) },
      options: { responsive: true, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, max: 100 } } }
    });
  }

  function mkBarChart(ctx, labels, nameA, a, nameB, b, nameC, c){
    if (window._bt_bar) window._bt_bar.destroy();
    window._bt_bar = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [
        { label: nameA, data: a, backgroundColor: '#2A54FF' },
        { label: nameB, data: b, backgroundColor: '#10b981' },
        { label: nameC, data: c, backgroundColor: '#ef4444' }
      ] },
      options: { indexAxis: 'y', scales: { x: { beginAtZero: true, max: 100 } } }
    });
  }

  function normalize(s){ return (s||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toUpperCase().replace(/[^A-Z0-9]/g,''); }

  async function fetchHCMapTopology(){
    const tries = [
      'https://cdn.jsdelivr.net/npm/highcharts@11/mapdata/countries/br/br-all.topo.json',
      'https://code.highcharts.com/mapdata/countries/br/br-all.topo.json'
    ];
    let lastErr;
    for (const u of tries){
      try {
        const r = await fetch(u, { cache: 'force-cache' });
        if (!r.ok) throw new Error('HTTP '+r.status);
        return await r.json();
      } catch(e){ lastErr = e; }
    }
    throw lastErr || new Error('Falha ao carregar mapa do Brasil');
  }

  function mkMapChartHC(containerId, topology, list){
    try {
      if (!window.Highcharts || !topology) return;
      const HC = window.Highcharts;
      const features = (HC.geojson ? HC.geojson(topology) : []);
      const byNorm = new Map();
      list.forEach(x => byNorm.set(normalize(x.reg), x));
      const COLORS = { b3: '#1d4ed8', rf: '#10b981', cr: '#ef4444' };
      const data = features.map(f => {
        const name = f.name || (f.properties && (f.properties.name || f.properties.nome)) || 'Estado';
        const key = normalize(name);
        const row = byNorm.get(key);
        const a = row ? (row.a||0) : 0;
        const b = row ? (row.b||0) : 0;
        const c = row ? (row.c||0) : 0;
        let topCat = 'b3'; let topVal = a;
        if (b > topVal) { topCat = 'rf'; topVal = b; }
        if (c > topVal) { topCat = 'cr'; topVal = c; }
        const color = COLORS[topCat] || COLORS.b3;
        return {
          'hc-key': f['hc-key'] || (f.properties && f.properties['hc-key']) || undefined,
          name, value: topVal, a, b, c, topCat, color
        };
      });

      if (window._bt_hc_map && typeof window._bt_hc_map.destroy === 'function') {
        window._bt_hc_map.destroy();
      }
      window._bt_hc_map = HC.mapChart(containerId, {
        chart: { map: topology, backgroundColor: 'transparent' },
        title: { text: '' },
        legend: { enabled: false },
        credits: { enabled: false },
        tooltip: {
          useHTML: true,
          formatter: function(){
            const p = this.point || {};
            const top = (p.topCat === 'rf') ? 'Renda Fixa' : (p.topCat === 'cr' ? 'Criptomoeda' : 'B3');
            return `<b>${HC.escapeHTML(p.name||'')}</b><br>`+
                   `<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${p.color};margin-right:6px;vertical-align:middle;"></span>`+
                   `<b>${HC.escapeHTML(top)}</b> dominante nos últimos 7 dias<br>`+
                   `<span style="opacity:.75">A cor do estado indica a categoria com maior interesse</span><br>`+
                   `B3: <b>${(p.a??0)}</b>% • `+
                   `Renda Fixa: <b>${(p.b??0)}</b>% • `+
                   `Criptomoeda: <b>${(p.c??0)}</b>%`;
          }
        },
        series: [{
          type: 'map',
          name: 'Interesse — 7 dias',
          data,
          colorKey: 'color',
          joinBy: ['hc-key','hc-key'],
          states: { hover: { color: '#22c55e' } },
          dataLabels: { enabled: false }
        }]
      });
    } catch(err){
      console.error('[BrasilTrends][HCMap]', err);
    }
  }

  function renderRegionsTable(list){
    const el = qs('#bt-regions-tbody');
    const countEl = qs('#bt-regions-count');
    if (!el) return;
    const showAll = !!(qs('#bt-regions-toggle')?.dataset?.all === '1');
    const rows = showAll ? list : list.slice(0, 5);
    if (countEl) countEl.textContent = `${rows.length} de ${list.length}`;
    el.innerHTML = rows.map(x => `<tr><td>${x.reg}</td><td>${x.a}</td><td>${x.b}</td><td>${x.c}</td></tr>`).join('');
  }

  async function refreshCSV(){
    const base = CSV_BASE.replace(/\/$/, '');
    const [tsCsv, geoCsv, topology] = await Promise.all([
      fetchText(base + '/multiTimeline.csv'),
      fetchText(base + '/geoMap.csv'),
      fetchHCMapTopology()
    ]);
    const ts = parseTimeline(parseCSV(tsCsv));
    mkLineChart(qs('#bt-timeseries'), ts.labels, ts.series);
    const geo = parseGeo(parseCSV(geoCsv));
    // build full list for table (sorted by B3 desc)
    const rowsAll = [];
    const rowsRaw = parseCSV(geoCsv);
    let idx = rowsRaw.findIndex(r => (r[0]||'').toLowerCase().includes('regi'));
    if (idx < 0) idx = 0;
    for (let i=idx+1;i<rowsRaw.length;i++){
      const r = rowsRaw[i]; if (!r || !r.length) continue;
      const reg = (r[0]||'').trim(); if (!reg) continue;
      rowsAll.push({ reg, a: toNumber(r[1])||0, b: toNumber(r[2])||0, c: toNumber(r[3])||0 });
    }
    rowsAll.sort((x,y)=>y.a-x.a);
    mkMapChartHC('bt-geomap-hc', topology, rowsAll);
    renderRegionsTable(rowsAll);
    const btn = qs('#bt-regions-toggle');
    if (btn && !btn._bound){
      btn._bound = true;
      btn.addEventListener('click', () => {
        btn.dataset.all = btn.dataset.all === '1' ? '0' : '1';
        btn.textContent = btn.dataset.all === '1' ? 'Ver top 5' : 'Ver todos';
        renderRegionsTable(rowsAll);
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    refreshCSV().catch(err => console.error('[BrasilTrends][CSV]', err));
  });
})();
