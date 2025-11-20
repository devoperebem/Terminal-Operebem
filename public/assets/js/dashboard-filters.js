(function(){
  const KEY = 'dash_cards_visibility_v1';
  const CARDS = [
    { key: 'sw_futuros', label: 'Índices Futuros (CFD)' },
    { key: 'sw_americanos', label: 'Índices Norte Americanos' },
    { key: 'sw_europeus', label: 'Índices Europeus' },
    { key: 'sw_asiaticos', label: 'Índices Asiáticos' },
    { key: 'sw_petroleiras', label: 'Petroleiras' },
    { key: 'sw_dolar', label: 'Índice do Dólar' },
    { key: 'sw_brasil', label: 'Índices Sul Americanos' },
    { key: 'sw_adrs', label: 'ADRs' },
    { key: 'sw_cripto', label: 'Criptomoedas' },
    { key: 'sw_mineradoras', label: 'Mineradoras' },
    { key: 'sw_mundo', label: 'Dólar Americano | Mundo' },
    { key: 'sw_emergentes', label: 'Dólar Americano | Emergentes' },
    { key: 'sw_juros', label: 'Juros' },
    { key: 'sw_big_tech', label: 'Magnificent 7' },
    { key: 'sw_agricolas', label: 'Commodities - Agrícolas' },
    { key: 'sw_energia', label: 'Commodities - Energia' },
    { key: 'sw_metais', label: 'Commodities - Metais' },
    { key: 'sw_vola', label: 'Volatilidade' },
    { key: 'sw_outros', label: 'Outros' },
    { key: 'sw_usmb', label: 'US Market Barometer' },
    { key: 'sw_market_clock', label: 'Mercados Globais 24h' },
    { key: 'sw_econcal', label: 'Calendário Econômico' }
  ];
  function loadState(){ try { return JSON.parse(localStorage.getItem(KEY)) || {}; } catch(e){ return {}; } }
  function saveState(s){ try { localStorage.setItem(KEY, JSON.stringify(s)); } catch(e){} }
  function applyState(s){
    CARDS.forEach(c => {
      document.querySelectorAll('.' + c.key).forEach(el => { el.classList.toggle('d-none', s[c.key] === false); });
    });
    if (window.reflowDashboard) window.reflowDashboard();
  }
  function renderList(){
    const list = document.getElementById('dashboard-filters-list');
    if (!list) return;
    list.innerHTML = '';
    const state = loadState();
    CARDS.forEach(c => {
      const id = 'chk_' + c.key;
      const wrap = document.createElement('div');
      wrap.className = 'form-check';
      const input = document.createElement('input');
      input.type = 'checkbox';
      input.className = 'form-check-input';
      input.id = id;
      input.checked = state[c.key] !== false;
      input.addEventListener('change', function(){
        const st = loadState();
        st[c.key] = this.checked;
        saveState(st);
        applyState(st);
      });
      const label = document.createElement('label');
      label.className = 'form-check-label';
      label.setAttribute('for', id);
      label.textContent = c.label;
      wrap.appendChild(input); wrap.appendChild(label);
      list.appendChild(wrap);
    });
  }
  document.addEventListener('DOMContentLoaded', function(){ applyState(loadState()); });
  const resetBtn = document.getElementById('dashboard-filters-reset');
  if (resetBtn) {
    resetBtn.addEventListener('click', function(){
      const allOn = {}; CARDS.forEach(c => allOn[c.key] = true);
      saveState(allOn); applyState(allOn); renderList();
    });
  }
  document.getElementById('dashboardFiltersModal')?.addEventListener('show.bs.modal', renderList);
})();
