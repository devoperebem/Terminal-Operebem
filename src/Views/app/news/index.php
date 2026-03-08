<?php
$title = 'Notícias';
$csrf_token = $_SESSION['csrf_token'] ?? '';
ob_start();
?>

<style>
  .news-badge-source { display: inline-flex; align-items: center; justify-content: center; min-height: 22px; }
  .news-badge-source.priority { background: linear-gradient(135deg, #f59e0b, #d97706) !important; font-weight: 700; box-shadow: 0 2px 8px rgba(245,158,11,0.3); }
  .news-card.priority { border-left: 3px solid #f59e0b; background: linear-gradient(to right, rgba(245,158,11,0.05), transparent); }
  .filter-source-btn { transition: all 0.2s ease; }
  .filter-source-btn.active { background: var(--primary) !important; color: white !important; border-color: var(--primary) !important; }
  .filter-source-btn.priority-source { border: 2px solid #f59e0b; position: relative; }
  .filter-source-btn.priority-source::before { content: '⭐'; position: absolute; top: -6px; right: -6px; font-size: 12px; }
  @media (max-width: 767.98px) {
    #filterToggleBtn { display: inline-flex; }
  }
  @media (min-width: 768px) {
    #filterToggleBtn { display: none; }
  }

  /* Trusted Sources Section */
  .trusted-sources-section {
      padding: 30px 0 40px 0;
      background: transparent;
      position: relative;
      margin-bottom: 20px;
  }

  .trusted-sources-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.1) 50%, transparent 100%);
  }

  .sources-subtitle {
      font-size: 0.875rem;
      font-weight: 500;
      color: #6b7280;
      margin: 0 0 25px 0;
      letter-spacing: 0.5px;
      text-transform: uppercase;
  }

  .sources-marquee-wrapper {
      position: relative;
      overflow: hidden;
  }

  .sources-marquee-wrapper::before,
  .sources-marquee-wrapper::after {
      content: '';
      position: absolute;
      top: 0;
      bottom: 0;
      width: 100px;
      z-index: 2;
      pointer-events: none;
  }

  .sources-marquee-wrapper::before {
      left: 0;
      background: linear-gradient(90deg, var(--bg-fade-start) 0%, transparent 100%);
  }

  .sources-marquee-wrapper::after {
      right: 0;
      background: linear-gradient(270deg, var(--bg-fade-end) 0%, transparent 100%);
  }

  .sources-marquee {
      overflow: hidden;
      padding: 10px 0;
  }

  .sources-track {
      display: flex;
      width: max-content;
      animation: scroll-logos 50s linear infinite;
      gap: 60px;
  }

  .sources-track:hover {
      animation-play-state: paused;
  }

  .source-logo {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.9rem;
      font-weight: 600;
      color: #9ca3af;
      white-space: nowrap;
      transition: all 0.3s ease;
      position: relative;
      padding: 8px 16px;
      border-radius: 6px;
  }

  .source-logo::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 2px;
      background: linear-gradient(90deg, #3b82f6, #60a5fa);
      transition: width 0.3s ease;
  }

  .source-logo:hover {
      color: #3b82f6;
      transform: translateY(-2px);
  }

  .source-logo:hover::after {
      width: 80%;
  }

  @keyframes scroll-logos {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
  }

  /* Tema Light */
  html.light .trusted-sources-section::before {
      background: linear-gradient(90deg, transparent 0%, rgba(0,0,0,0.08) 50%, transparent 100%);
  }

  html.light {
      --bg-fade-start: #ffffff;
      --bg-fade-end: #ffffff;
  }

  html.light .sources-subtitle {
      color: #6b7280;
  }

  html.light .source-logo {
      color: #9ca3af;
  }

  html.light .source-logo:hover {
      color: #3b82f6;
      background: rgba(59, 130, 246, 0.05);
  }

  /* Tema Dark Blue */
  html.dark-blue {
      --bg-fade-start: #001233;
      --bg-fade-end: #001233;
  }

  html.dark-blue .trusted-sources-section::before {
      background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.1) 50%, transparent 100%);
  }

  html.dark-blue .sources-subtitle {
      color: #9ca3af;
  }

  html.dark-blue .source-logo {
      color: #6b7280;
  }

  html.dark-blue .source-logo:hover {
      color: #60a5fa;
      background: rgba(96, 165, 250, 0.1);
  }

  /* Tema All Black */
  html.all-black {
      --bg-fade-start: #0a0a0a;
      --bg-fade-end: #0a0a0a;
  }

  html.all-black .trusted-sources-section::before {
      background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.08) 50%, transparent 100%);
  }

  html.all-black .sources-subtitle {
      color: #9ca3af;
  }

  html.all-black .source-logo {
      color: #6b7280;
  }

  html.all-black .source-logo:hover {
      color: #60a5fa;
      background: rgba(96, 165, 250, 0.08);
  }

  /* Responsivo */
  @media (max-width: 768px) {
      .trusted-sources-section {
          padding: 25px 0 30px 0;
      }
      
      .sources-subtitle {
          font-size: 0.75rem;
          margin-bottom: 15px;
      }
      
      .sources-track {
          gap: 40px;
          animation-duration: 40s;
      }
      
      .source-logo {
          font-size: 0.8rem;
          padding: 6px 12px;
      }
      
      .sources-marquee-wrapper::before,
      .sources-marquee-wrapper::after {
          width: 50px;
      }
  }
</style>

<!-- Trusted Data Sources Section -->
<section class="trusted-sources-section">
    <div class="container">
        <div class="row align-items-center mb-3">
            <div class="col-12 text-center">
                <p class="sources-subtitle">Notícias em tempo real de fontes confiáveis</p>
            </div>
        </div>
        <div class="sources-marquee-wrapper">
            <div class="sources-marquee">
                <div class="sources-track">
                    <div class="source-logo">Bloomberg</div>
                    <div class="source-logo">CNBC</div>
                    <div class="source-logo">Investing.com</div>
                    <div class="source-logo">Yahoo Finance</div>
                    <div class="source-logo">Reuters</div>
                    <div class="source-logo">Financial Times</div>
                    <div class="source-logo">CoinDesk</div>
                    <div class="source-logo">InfoMoney</div>
                    <div class="source-logo">Money Times</div>
                    <div class="source-logo">The Wall Street Journal</div>
                    <div class="source-logo">Banco Central do Brasil</div>
                    <div class="source-logo">OilPrice</div>
                    <!-- Duplicar para loop infinito -->
                    <div class="source-logo">Bloomberg</div>
                    <div class="source-logo">CNBC</div>
                    <div class="source-logo">Investing.com</div>
                    <div class="source-logo">Yahoo Finance</div>
                    <div class="source-logo">Reuters</div>
                    <div class="source-logo">Financial Times</div>
                    <div class="source-logo">CoinDesk</div>
                    <div class="source-logo">InfoMoney</div>
                    <div class="source-logo">Money Times</div>
                    <div class="source-logo">The Wall Street Journal</div>
                    <div class="source-logo">Banco Central do Brasil</div>
                    <div class="source-logo">OilPrice</div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="container py-4">

  <div class="row g-3" id="statsRow">
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:rgba(34,197,94,0.15);color:#16a34a;">
            <i class="fas fa-newspaper"></i>
          </div>
          <div>
            <div class="text-muted small">Total de Notícias</div>
            <div class="fw-bold" id="totalNoticias">0</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:rgba(59,130,246,0.15);color:#0ea5e9;">
            <i class="fas fa-broadcast-tower"></i>
          </div>
          <div>
            <div class="text-muted small">Fontes Ativas</div>
            <div class="fw-bold" id="totalFontes">0</div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:rgba(250,204,21,0.15);color:#f59e0b;">
            <i class="fas fa-clock"></i>
          </div>
          <div>
            <div class="text-muted small">Última Atualização</div>
            <div class="fw-bold" id="ultimaAtualizacao">N/A</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card my-3">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
          <i class="fas fa-filter text-muted"></i>
          <div class="fw-semibold">Filtrar por Fonte</div>
          <button id="filterToggleBtn" class="btn btn-sm btn-outline-secondary d-md-none ms-2" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse" aria-expanded="false" aria-controls="filterCollapse">
            Fontes <i class="fas fa-chevron-down ms-1"></i>
          </button>
        </div>
        <div class="d-flex align-items-center gap-2">
          <button class="btn btn-sm btn-outline-secondary" id="langToggle" title="Alternar Tradução">PT</button>
          <button class="btn btn-sm btn-outline-secondary" id="soundToggle" title="Notificações Sonoras: desligado"><i class="fas fa-bell-slash"></i></button>
        </div>
      </div>
      <div id="filterCollapse" class="collapse">
        <div class="d-flex align-items-center gap-2 flex-wrap mt-2" id="filterButtons">
        </div>
      </div>
    </div>
  </div>

  <div id="newsContainer" class="mb-4">
    <div class="d-flex flex-column align-items-center justify-content-center py-5 text-center" id="newsLoading">
      <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
      <div class="text-muted small mt-2">Carregando notícias...</div>
    </div>
  </div>

  <div id="newsError" class="alert alert-danger d-none" role="alert"></div>
  <audio id="notiSound" preload="auto" src="/assets/songs/noti.mp3"></audio>
</div>

<script>
(function(){
  const API_URL = <?= json_encode($newsApiUrl ?? '') ?>;
  const API_KEY = <?= json_encode($newsApiKey ?? '') ?>;
  const REFRESH_MS = <?= (int)($newsRefreshMs ?? 30000) ?>;

  const newsContainer = document.getElementById('newsContainer');
  const loadingEl = document.getElementById('newsLoading');
  const errEl = document.getElementById('newsError');
  const filterButtons = document.getElementById('filterButtons');
  const langToggle = document.getElementById('langToggle');
  const soundToggle = document.getElementById('soundToggle');
  const audioEl = document.getElementById('notiSound');

  let allNoticias = [];
  let allFontes = [];
  let stats = null;
  let selectedFontes = new Set();
  try { localStorage.removeItem('news_selected_sources'); } catch(_){ }
  const seenFontes = new Set();
  const PRIORITY_SOURCES = ['Bloomberg', 'Banco Central', 'Banco Central do Brasil'];
  let showTranslation = (localStorage.getItem('news_show_translation') ?? '1') === '1';
  let soundEnabled = (localStorage.getItem('news_sound_enabled') ?? '0') === '1';
  const knownIds = new Set();
  const MAX_ITEMS = 200;
  let pollDelay = Math.max(5000, REFRESH_MS || 30000);
  let pollTimer = null;
  let abortCtrl = null;

  function showError(msg){
    errEl.textContent = msg;
    errEl.classList.remove('d-none');
  }
  function hideError(){ errEl.classList.add('d-none'); }

  function guardConfig(){
    if (!API_URL) { showError('Configuração ausente: NEWS_API_URL'); return false; }
    // Se usando proxy interno, não exigir API_KEY no cliente
    const internal = API_URL.startsWith('/api/news');
    if (!internal && !API_KEY) { showError('Configuração ausente: NEWS_API_KEY'); return false; }
    hideError();
    return true;
  }

  async function fetchAPI(path){
    const url = API_URL.replace(/\/$/, '') + path;
    const init = {
      method: 'GET',
      // Necessário para AuthMiddleware enviar cookies de sessão
      credentials: 'include',
      headers: { 'Accept': 'application/json', 'X-API-Key': API_KEY },
      signal: abortCtrl ? abortCtrl.signal : undefined
    };
    const res = await fetch(url, init).catch((e)=>({ ok:false, _err:e }));
    if (!res || !res.ok) {
      return { success: false, message: 'Falha de rede ao acessar API interna de notícias' };
    }
    const data = await res.json().catch(() => ({}));
    return data;
  }

  async function pollOnce(){
    if (!guardConfig()) return;
    if (abortCtrl) { try { abortCtrl.abort(); } catch(_){} }
    abortCtrl = new AbortController();
    try {
      const noticias = await fetchAPI('/noticias');

      if (loadingEl) loadingEl.remove();

      // Compute stats and fontes locally
      let computedFontes = [];
      let totalNoticias = allNoticias ? allNoticias.length : 0;
      let ultimaColeta = null;
      let newCount = 0;
      if (noticias && noticias.success) {
        const list = (noticias.noticias || []);
        // Track fontes seen in this batch to keep fonte list stable
        for (const n of list) {
          if (n && typeof n.fonte !== 'undefined') {
            seenFontes.add(n.fonte || 'Desconhecida');
          }
        }
        // Sort by data_coleta ascending to prepend in order
        const toInsert = [];
        for (const n of list) {
          const key = (n.link || '') + '|' + (n.data_coleta || n.data_publicacao || '');
          if (!knownIds.has(key)) {
            knownIds.add(key);
            toInsert.push(n);
            allNoticias.unshift(n);
            newCount++;
          }
          const ts = (n.data_coleta || n.data_publicacao);
          if (ts) {
            const t = new Date(ts).getTime();
            if (!ultimaColeta || t > ultimaColeta) ultimaColeta = t;
          }
        }
        // Trim arrays and knownIds if too big
        if (allNoticias.length > MAX_ITEMS) allNoticias.length = MAX_ITEMS;
        if (knownIds.size > MAX_ITEMS * 2) {
          // rebuild knownIds from current allNoticias
          const tmp = new Set();
          allNoticias.forEach(n => tmp.add((n.link||'') + '|' + (n.data_coleta || n.data_publicacao || '')));
          knownIds.clear();
          tmp.forEach(k => knownIds.add(k));
        }
        // compute fontes and stats from allNoticias
        const map = new Map();
        for (const n of allNoticias) { const f = n.fonte || 'Desconhecida'; map.set(f, (map.get(f)||0)+1); }
        computedFontes = Array.from(map.entries()).map(([nome, total]) => ({ nome, total })).sort((a,b)=>b.total-a.total);
        // Ensure seenFontes includes initial + newly discovered fontes
        computedFontes.forEach(f => seenFontes.add(f.nome));
        totalNoticias = allNoticias.length;
        // Build filters from persistent seenFontes with current counts (0 if absent)
        const fontesForFilters = Array.from(seenFontes).map(nome => ({ nome, total: map.get(nome) || 0 }))
          .sort((a,b)=> b.total - a.total || a.nome.localeCompare(b.nome));
        updateFilters(fontesForFilters, totalNoticias);
        updateStats({ total_noticias: totalNoticias, total_fontes: seenFontes.size, ultima_atualizacao: ultimaColeta ? new Date(ultimaColeta).toISOString() : null });
        // Insert into DOM honoring current filter
        if (toInsert.length) {
          // older first so latest ends up on top when prepending
          toInsert.sort((a,b) => new Date(a.data_coleta||a.data_publicacao||0) - new Date(b.data_coleta||b.data_publicacao||0));
          const frag = document.createDocumentFragment();
          const cards = [];
          for (const n of toInsert) {
            // Se há filtros ativos, mostrar apenas as fontes selecionadas
            if (selectedFontes.size > 0 && !selectedFontes.has(n.fonte)) continue;
            const card = buildNewsCard(n);
            cards.push(card);
          }
          // Prepend in order
          for (const c of cards) {
            newsContainer.insertBefore(c, newsContainer.firstChild);
          }
          if (newCount > 0 && soundEnabled) {
            try { audioEl.currentTime = 0; audioEl.play(); } catch(_){ }
          }
          // Enforce DOM limit
          while (newsContainer.children.length > MAX_ITEMS) {
            newsContainer.removeChild(newsContainer.lastElementChild);
          }
        }
      }
      // Reset backoff after success
      pollDelay = Math.max(5000, REFRESH_MS || 30000);
    } catch (e) {
      console.error('Erro no polling de notícias:', e);
      showError('Erro ao conectar com a API de Notícias.');
      // Backoff progressivo até 120s
      pollDelay = Math.min(Math.floor(pollDelay * 1.5), 120000);
    } finally {
      scheduleNext(pollDelay);
    }
  }

  function scheduleNext(ms){
    if (pollTimer) clearTimeout(pollTimer);
    pollTimer = setTimeout(pollOnce, ms);
  }

  function updateStats(s){
    document.getElementById('totalNoticias').textContent = (s && s.total_noticias) ? s.total_noticias : (allNoticias ? allNoticias.length : 0);
    document.getElementById('totalFontes').textContent = (s && s.total_fontes) ? s.total_fontes : (allFontes ? allFontes.length : 0);
    document.getElementById('ultimaAtualizacao').textContent = s && s.ultima_atualizacao ? tempoRelativo(s.ultima_atualizacao) : 'N/A';
  }

  function updateFilters(fontes, total){
    filterButtons.innerHTML = '';
    fontes.forEach(f => {
      const b = document.createElement('button');
      const isPriority = PRIORITY_SOURCES.includes(f.nome);
      b.className = 'btn btn-sm btn-outline-secondary filter-source-btn' + 
        (selectedFontes.has(f.nome) ? ' active' : '') +
        (isPriority ? ' priority-source' : '');
      b.setAttribute('data-fonte', f.nome);
      b.textContent = `${f.nome} (${f.total})`;
      b.addEventListener('click', () => toggleFilter(f.nome));
      filterButtons.appendChild(b);
    });
  }

  function toggleFilter(nome){
    if (selectedFontes.has(nome)) {
      selectedFontes.delete(nome);
    } else {
      selectedFontes.add(nome);
    }
    rebuildListFromAll();
    Array.from(filterButtons.querySelectorAll('button')).forEach(b => {
      const v = b.getAttribute('data-fonte');
      if (selectedFontes.has(v)) b.classList.add('active'); else b.classList.remove('active');
    });
  }

  function rebuildListFromAll(){
    // Clear list
    newsContainer.innerHTML = '';
    if (!allNoticias || !allNoticias.length) {
      newsContainer.innerHTML = '<div class="text-center text-muted py-5">Nenhuma notícia disponível</div>';
      return;
    }
    const frag = document.createDocumentFragment();
    let count = 0;
    for (const n of allNoticias) {
      // Se há filtros ativos, mostrar apenas as fontes selecionadas
      if (selectedFontes.size > 0 && !selectedFontes.has(n.fonte)) continue;
      frag.appendChild(buildNewsCard(n));
      count++;
      if (count >= MAX_ITEMS) break;
    }
    if (count === 0) {
      newsContainer.innerHTML = '<div class="text-center text-muted py-5">Nenhuma notícia encontrada com os filtros selecionados</div>';
    } else {
      newsContainer.appendChild(frag);
    }
  }

  function buildNewsCard(n){
    const card = document.createElement('div');
    const isPriority = PRIORITY_SOURCES.includes(n.fonte);
    card.className = 'card mb-3 news-card' + (isPriority ? ' priority' : '');
    card.dataset.titulo = n.titulo || '';
    card.dataset.titulopt = n.titulo_pt || '';
    card.dataset.descricao = n.descricao || '';
    card.dataset.descricaopt = n.descricao_pt || '';
    const title = escapeHtml((showTranslation && n.titulo_pt) ? n.titulo_pt : (n.titulo || ''));
    const desc = escapeHtml((showTranslation && n.descricao_pt) ? n.descricao_pt : (n.descricao || ''));
    const trad = (n.titulo_pt || n.descricao_pt) && showTranslation ? '<span class="badge rounded-pill text-bg-success ms-2">Traduzido</span>' : '';
    const linkHtml = n.link ? `<a class="mt-2 d-inline-flex align-items-center gap-2" href="${escapeAttr(n.link)}" target="_blank" rel="noopener noreferrer">Ler notícia completa <i class="fas fa-arrow-up-right-from-square"></i></a>` : '';
    card.innerHTML = `
      <div class="card-body">
        <div class="d-flex align-items-center flex-wrap gap-2 text-muted small mb-2">
          <span class="badge rounded-pill text-bg-primary news-badge-source${isPriority ? ' priority' : ''}">${escapeHtml(n.fonte||'')}</span>
          <span>${formatarData(n.data_publicacao)}</span>
          <span>• ${tempoRelativo(n.data_coleta)}</span>
          ${trad}
        </div>
        <div class="fw-semibold mb-1 news-title">${title}</div>
        ${desc ? `<div class="text-muted news-desc">${desc}</div>` : ''}
        ${linkHtml}
      </div>`;
    return card;
  }

  function updateAllCardTexts(){
    const cards = document.querySelectorAll('.news-card');
    cards.forEach(card => {
      const tit = showTranslation ? (card.dataset.titulopt || card.dataset.titulo || '') : (card.dataset.titulo || '');
      const desc = showTranslation ? (card.dataset.descricaopt || card.dataset.descricao || '') : (card.dataset.descricao || '');
      const titleEl = card.querySelector('.news-title');
      const descEl = card.querySelector('.news-desc');
      if (titleEl) titleEl.textContent = tit;
      if (descEl) {
        if (desc) { descEl.textContent = desc; }
        else { descEl.remove(); }
      }
      // Badge Traduzido
      const meta = card.querySelector('.card-body > .d-flex');
      if (meta) {
        const existing = meta.querySelector('.text-bg-success');
        const hasTrad = (card.dataset.titulopt || card.dataset.descricaopt);
        if (showTranslation && hasTrad) {
          if (!existing) {
            const span = document.createElement('span');
            span.className = 'badge rounded-pill text-bg-success ms-2';
            span.textContent = 'Traduzido';
            meta.appendChild(span);
          }
        } else {
          if (existing) existing.remove();
        }
      }
    });
  }

  function formatarData(iso){
    try {
      const d = new Date(iso);
      return d.toLocaleString('pt-BR', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
    } catch { return ''; }
  }
  function tempoRelativo(iso){
    try {
      const d = new Date(iso); const now = new Date(); const diff = Math.floor((now - d)/60000);
      if (diff < 1) return 'agora'; if (diff < 60) return `há ${diff} min`;
      const h = Math.floor(diff/60); if (h < 24) return `há ${h} h`;
      const days = Math.floor(h/24); return `há ${days} d`;
    } catch { return ''; }
  }
  function escapeHtml(s){
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    return String(s || '').replace(/[&<>"']/g, ch => map[ch]);
  }
  function escapeAttr(s){ return escapeHtml(s); }

  // UI Toggles
  function updateLangBtn(){ langToggle.textContent = showTranslation ? 'PT' : 'Original'; }
  function updateSoundBtn(){ soundToggle.innerHTML = soundEnabled ? '<i class="fas fa-bell"></i>' : '<i class="fas fa-bell-slash"></i>'; soundToggle.title = soundEnabled ? 'Notificações Sonoras: ligado' : 'Notificações Sonoras: desligado'; }
  langToggle.addEventListener('click', () => { showTranslation = !showTranslation; localStorage.setItem('news_show_translation', showTranslation ? '1' : '0'); updateLangBtn(); updateAllCardTexts(); });
  soundToggle.addEventListener('click', () => { soundEnabled = !soundEnabled; localStorage.setItem('news_sound_enabled', soundEnabled ? '1' : '0'); updateSoundBtn(); if (soundEnabled) { try { audioEl.play().then(()=>{audioEl.pause(); audioEl.currentTime=0;}).catch(()=>{}); } catch(_){} } });
  updateLangBtn();
  updateSoundBtn();

  

  // Handle mobile collapse: force open on desktop, collapsed on mobile
  function syncFilterCollapse(){
    const el = document.getElementById('filterCollapse');
    if (!el) return;
    const isDesktop = window.matchMedia('(min-width: 768px)').matches;
    if (isDesktop) el.classList.add('show'); else el.classList.remove('show');
  }
  window.addEventListener('resize', syncFilterCollapse);
  syncFilterCollapse();

  // Boot: initial full load and schedule polling
  (async function initial(){
    if (!guardConfig()) return;
    try {
      abortCtrl = new AbortController();
      const noticias = await fetchAPI('/noticias');
      if (loadingEl) loadingEl.remove();
      if (noticias && noticias.success) {
        const incoming = (noticias.noticias || []);
        allNoticias = incoming.slice(0, MAX_ITEMS);
        // Build knownIds from full incoming list (avoid treating older ones as novos no primeiro poll)
        for (const n of incoming) {
          const key = (n.link || '') + '|' + (n.data_coleta || n.data_publicacao || '');
          knownIds.add(key);
        }
        const frag = document.createDocumentFragment();
        let latest = null;
        for (const n of allNoticias) {
          // Inicialmente mostrar todas (selectedFontes vazio)
          if (selectedFontes.size === 0 || selectedFontes.has(n.fonte)) frag.appendChild(buildNewsCard(n));
          const ts = (n.data_coleta || n.data_publicacao);
          if (ts) {
            const t = new Date(ts).getTime();
            if (!latest || t > latest) latest = t;
          }
        }
        newsContainer.innerHTML = '';
        newsContainer.appendChild(frag);
        // compute fontes and stats from trimmed list; seed seenFontes from full incoming
        const map = new Map();
        for (const n of allNoticias) { const f = n.fonte || 'Desconhecida'; map.set(f, (map.get(f)||0)+1); }
        const uniqueIncomingSources = new Set(incoming.map(n => (n && n.fonte) ? n.fonte : 'Desconhecida'));
        uniqueIncomingSources.forEach(nome => seenFontes.add(nome));
        const fontesForFilters = Array.from(seenFontes).map(nome => ({ nome, total: map.get(nome) || 0 }))
          .sort((a,b)=> b.total - a.total || a.nome.localeCompare(b.nome));
        updateFilters(fontesForFilters, allNoticias.length);
        updateStats({ total_noticias: allNoticias.length, total_fontes: seenFontes.size, ultima_atualizacao: latest ? new Date(latest).toISOString() : null });
      } else {
        const msg = (noticias && noticias.message) ? noticias.message : 'Nenhuma notícia disponível (API retornou erro)';
        newsContainer.innerHTML = `<div class="text-center text-muted py-5">${escapeHtml(msg)}</div>`;
      }
    } catch(e) {
      console.error(e);
      showError('Erro ao carregar notícias.');
    } finally {
      scheduleNext(pollDelay);
    }
  })();
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
?>
