<?php
$title = 'Indicadores - Sentimento';
$csrf_token = $_SESSION['csrf_token'] ?? '';
ob_start();
?>

<div class="container-fluid p-0">
  <div id="widget_long" class="tradingview-widget-container w-100 mb-4">
    <div class="tradingview-widget-container__widget"></div>
    <div class="tradingview-widget-copyright" style="display:none;"></div>
  </div>
</div>

<style>
  #fg-barometer { position: relative; }
  .barometer-scale { height: 22px; overflow: hidden; border-radius: 4px; }
  .barometer-scale .seg { font-size: 0.75rem; line-height: 22px; color: #fff; }
  html.light .seg-ef { background: #ef4444; }
  html.light .seg-f  { background: #f59e0b; }
  html.light .seg-n  { background: #9ca3af; }
  html.light .seg-g  { background: #10b981; }
  html.light .seg-eg { background: #22c55e; }
  html.dark-blue .seg-ef, html.all-black .seg-ef { background: #b91c1c; }
  html.dark-blue .seg-f,  html.all-black .seg-f  { background: #b45309; }
  html.dark-blue .seg-n,  html.all-black .seg-n  { background: #6b7280; }
  html.dark-blue .seg-g,  html.all-black .seg-g  { background: #059669; }
  html.dark-blue .seg-eg, html.all-black .seg-eg { background: #16a34a; }
  .barometer-needle { position: absolute; top: -3px; left: 0%; width: 0; height: 26px; pointer-events: none; }
  .barometer-needle::after { content: ''; position: absolute; top: 0; left: -1px; width: 2px; height: 100%; background: #1e40af; border-radius: 2px; box-shadow: 0 0 0 1px rgba(0,0,0,0.06); }
  html.dark-blue .barometer-needle::after, html.all-black .barometer-needle::after { background: #60a5fa; box-shadow: 0 0 0 1px rgba(255,255,255,0.08); }
  /* Fonte: OpereBem Data - green blinking dot */
  .dot-online { display:inline-block; width:12px; height:12px; border-radius:50%; background:#10b981; box-shadow:0 0 0 0 rgba(16,185,129,.7); animation: pulse 1.6s infinite; }
  @keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(16,185,129,.7); }
    70% { box-shadow: 0 0 0 10px rgba(16,185,129,0); }
    100% { box-shadow: 0 0 0 0 rgba(16,185,129,0); }
  }
  .source-line { font-weight: 600; }
  html.light .source-line { color: #065f46; }
  html.dark-blue .source-line, html.all-black .source-line { color: #34d399; }
  /* Gauge (semicírculo) */
  .fg-gauge { width: 100%; max-width: 260px; margin: 4px auto 2px; }
  .fg-gauge svg { display: block; width: 100%; height: auto; }
  .fg-gauge .track { stroke: var(--border-color); fill: none; stroke-width: 12; stroke-linecap: round; opacity: .6; }
  .fg-gauge .progress { fill: none; stroke-width: 12; stroke-linecap: round; }
  .fg-gauge .needle { stroke: var(--text-primary); stroke-width: 2; }
  .fg-gauge .center-dot { fill: var(--text-primary); }
  /* Deltas */
  .fg-delta { font-weight: 600; }
  .fg-delta-up { color: #22c55e; }
  .fg-delta-down { color: #ef4444; }
  .fg-delta-zero { color: var(--text-secondary); }
  /* Cards compactos dos valores anteriores (abaixo do histórico) */
  .fg-prev-grid .mini-stat { border: 1px solid var(--border-color); border-radius: 10px; padding: .6rem .75rem; background: transparent; }
  .fg-prev-grid .mini-stat .label { color: var(--text-secondary); font-size: .8rem; }
  .fg-prev-grid .mini-stat .value-line { display: flex; align-items: center; gap: .5rem; }

  /* Accordion buttons theming (dark-blue / all-black) */
  html.dark-blue #fg-accordion .accordion-button,
  html.all-black #fg-accordion .accordion-button,
  html.dark-blue #orm-accordion .accordion-button,
  html.all-black #orm-accordion .accordion-button {
    background: rgba(255,255,255,0.06);
    color: #e5e7eb;
    border-color: rgba(255,255,255,0.15);
  }
  html.dark-blue #fg-accordion .accordion-button:not(.collapsed),
  html.all-black #fg-accordion .accordion-button:not(.collapsed),
  html.dark-blue #orm-accordion .accordion-button:not(.collapsed),
  html.all-black #orm-accordion .accordion-button:not(.collapsed) {
    background: rgba(255,255,255,0.09);
    color: #e5e7eb;
    box-shadow: none;
  }
  html.dark-blue #fg-accordion .accordion-item,
  html.all-black #fg-accordion .accordion-item,
  html.dark-blue #orm-accordion .accordion-item,
  html.all-black #orm-accordion .accordion-item {
    background: transparent;
    border-color: rgba(255,255,255,0.12);
  }
  html.all-black #fg-accordion .accordion-body,
  html.all-black #orm-accordion .accordion-body {
    background: #000000;
  }
  /* Table container as card */
  #orm-details-section .accordion-body .table-responsive {
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    background: transparent;
  }
  /* Tabela em temas escuros: fundo e bordas coerentes */
  #orm-details-table { background: transparent; }
  #orm-details-table thead th { background: transparent; color: var(--text-primary); }
  #orm-details-table tbody tr { background: transparent; }
  #orm-details-table td, #orm-details-table th { border-color: var(--border-color); }
  
  /* Mobile APENAS: Tabela responsiva com scroll horizontal suave */
  @media (max-width: 768px) {
    #orm-details-section .accordion-body {
      padding: 0.5rem;
    }
    #orm-details-section .accordion-body .table-responsive {
      overflow-x: auto;
      overflow-y: visible;
      -webkit-overflow-scrolling: touch;
      margin: 0 -0.5rem;
      border-left: none;
      border-right: none;
      border-radius: 0;
    }
    #orm-details-table {
      font-size: 0.8rem;
      min-width: 700px;
      white-space: nowrap;
    }
    #orm-details-table thead th {
      font-size: 0.75rem;
      padding: 0.5rem 0.4rem;
      position: sticky;
      top: 0;
      background: var(--card-bg);
      z-index: 10;
    }
    #orm-details-table td {
      padding: 0.5rem 0.4rem;
      font-size: 0.75rem;
    }
    #orm-details-table th:first-child,
    #orm-details-table td:first-child {
      position: sticky;
      left: 0;
      background: var(--card-bg);
      z-index: 5;
      box-shadow: 2px 0 4px rgba(0,0,0,0.1);
    }
    #orm-details-table thead th:first-child {
      z-index: 15;
    }
  }
  
  @media (max-width: 576px) {
    #orm-details-table {
      font-size: 0.75rem;
      min-width: 650px;
    }
    #orm-details-table thead th,
    #orm-details-table td {
      padding: 0.4rem 0.3rem;
    }
  }
  html.dark-blue #orm-details-table th, html.dark-blue #orm-details-table td,
  html.all-black #orm-details-table th, html.all-black #orm-details-table td { color: #e5e7eb; }
  html.dark-blue #orm-details-table td, html.dark-blue #orm-details-table th,
  html.all-black #orm-details-table td, html.all-black #orm-details-table th { border-color: rgba(255,255,255,0.15); }
  html.dark-blue #orm-details-section .table-responsive, html.all-black #orm-details-section .table-responsive { background: transparent; }
  html.dark-blue #orm-details-table .score-box, html.all-black #orm-details-table .score-box { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.18); color: #f3f4f6; }
  html.dark-blue .pill-on, html.all-black .pill-on { border-color: rgba(34,197,94,.55); background: rgba(16,185,129,.18); color: #a7f3d0; }
  html.dark-blue .pill-off, html.all-black .pill-off { border-color: rgba(239,68,68,.55); background: rgba(239,68,68,.18); color: #fecaca; }
  html.dark-blue #orm-details-table a, html.all-black #orm-details-table a { color: #e5e7eb; text-decoration: none; }
  html.dark-blue #orm-details-table .text-muted, html.all-black #orm-details-table .text-muted { color: #cbd5e1 !important; }
  html.dark-blue #orm-details-table .text-secondary, html.all-black #orm-details-table .text-secondary { color: #d1d5db !important; }
  html.dark-blue #orm-details-table tbody tr:hover, html.all-black #orm-details-table tbody tr:hover { background: rgba(255,255,255,0.06); }
  html.dark-blue #orm-details-table .btn-outline-secondary, html.all-black #orm-details-table .btn-outline-secondary { color: #e5e7eb; border-color: rgba(255,255,255,0.25); }
  /* ORM details table: responsive tweaks for mobile */
  @media (max-width: 576px) {
    #orm-details-table th, #orm-details-table td { font-size: 12px; }
    /* Hide less important columns on narrow screens: Tipo, Mínima, Máxima */
    #orm-details-table th:nth-child(2), #orm-details-table td:nth-child(2),
    #orm-details-table th:nth-child(6), #orm-details-table td:nth-child(6),
    #orm-details-table th:nth-child(7), #orm-details-table td:nth-child(7) { display: none; }
  }
  /* Fundo do gráfico histórico no container (chart é transparente) */
  #chart-container { position: relative; border-radius: 12px; overflow: hidden; --ts-h: 28px; }
  html.light #chart-container::before {
    content: ''; position: absolute; left: 0; right: 0; top: 0; bottom: var(--ts-h, 28px); pointer-events: none;
    background: linear-gradient(to top,
      rgba(239,68,68,0.12) 0%, rgba(239,68,68,0.12) 24%,
      rgba(245,158,11,0.10) 24%, rgba(245,158,11,0.10) 44%,
      rgba(107,114,128,0.08) 44%, rgba(107,114,128,0.08) 56%,
      rgba(16,185,129,0.10) 56%, rgba(16,185,129,0.10) 75%,
      rgba(34,197,94,0.10) 75%, rgba(34,197,94,0.10) 100%
    );
  }
  html.dark-blue #chart-container::before,
  html.all-black #chart-container::before {
    content: ''; position: absolute; left: 0; right: 0; top: 0; bottom: var(--ts-h, 28px); pointer-events: none;
    background: linear-gradient(to top,
      rgba(185,28,28,0.16) 0%, rgba(185,28,28,0.16) 24%,
      rgba(180,83,9,0.14) 24%, rgba(180,83,9,0.14) 44%,
      rgba(107,114,128,0.12) 44%, rgba(107,114,128,0.12) 56%,
      rgba(5,150,105,0.14) 56%, rgba(5,150,105,0.14) 75%,
      rgba(22,163,74,0.14) 75%, rgba(22,163,74,0.14) 100%
    );
  }

  /* Remove striped ruler, keep only single needle marker */
  .barometer-scale { position: relative; }
  .barometer-ruler, .barometer-classes { font-size: 11px; color: var(--text-secondary); }
  .barometer-ruler span, .barometer-classes span { width: 20%; text-align: center; display: inline-block; }
</style>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 m-0">Sentimento de Mercado (CNN Fear & Greed)</h1>
  </div>

  <div class="row g-3">
    <div class="col-12 col-lg-4">
      <div class="card h-100">
        <div class="card-header fw-semibold">Agora</div>
        <div class="card-body d-flex flex-column gap-3">
          <div class="d-flex flex-column align-items-center gap-2">
            <div style="font-size: 56px; line-height: 1; font-weight: 800;" id="fg-score">--</div>
            <div id="fg-rating" class="badge" style="font-size: 0.95rem; padding: 0.5rem 0.6rem;">--</div>
            <div class="small text-muted" id="fg-updated">&nbsp;</div>
          </div>
          <div id="fg-gauge-hc" class="fg-gauge" style="height: 240px;"></div>
          <div id="fg-barometer" class="my-2">
            <div class="barometer-scale d-flex">
              <div class="seg seg-ef flex-fill text-center">Medo Extremo</div>
              <div class="seg seg-f  flex-fill text-center">Medo</div>
              <div class="seg seg-n  flex-fill text-center">Neutro</div>
              <div class="seg seg-g flex-fill text-center">Ganância</div>
              <div class="seg seg-eg flex-fill text-center">Ganância Extrema</div>
            </div>
            <div class="barometer-needle" id="fg-needle"></div>
            <div class="barometer-ruler mt-1"><span>0</span><span>25</span><span>50</span><span>75</span><span>100</span></div>
            <div class="barometer-classes"><span>Medo Extremo</span><span>Medo</span><span>Neutro</span><span>Ganância</span><span>Ganância Extrema</span></div>
          </div>
          <div class="small">Índice varia de 0 (Medo Extremo) a 100 (Ganância Extrema).</div>
          <div id="fg-error" class="alert alert-danger d-none" role="alert"></div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-8">
      <div class="card h-100 d-flex flex-column">
        <div class="card-header fw-semibold">
          <div class="d-flex align-items-center justify-content-between">
            <span>Histórico</span>
            <small class="text-muted" id="fg-ping-label">Operebem Data</small>
          </div>
          <div id="legend-hdr" class="small text-muted mt-1">&nbsp;</div>
        </div>
        <div class="card-body p-0 d-flex flex-column">
          <div id="chart-container" style="flex:1 1 auto; height: 420px;"></div>
          <div class="d-flex align-items-center justify-content-end mt-2 small text-muted px-3 pb-2">
            <div id="range-label">&nbsp;</div>
          </div>
          <div class="fg-prev-grid border-top px-3 pb-3">
            <div class="row g-2 row-cols-2 row-cols-lg-4 mt-2">
              <div class="col">
                <div class="mini-stat">
                  <div class="label">Fechamento anterior</div>
                  <div class="value-line"><span id="fg-prev-close-val">--</span> <span class="badge" id="fg-prev-close-rating">--</span> <small id="fg-prev-close-delta" class="fg-delta ms-2"></small></div>
                </div>
              </div>
              <div class="col">
                <div class="mini-stat">
                  <div class="label">1 semana atrás</div>
                  <div class="value-line"><span id="fg-prev-week-val">--</span> <span class="badge" id="fg-prev-week-rating">--</span> <small id="fg-prev-week-delta" class="fg-delta ms-2"></small></div>
                </div>
              </div>
              <div class="col">
                <div class="mini-stat">
                  <div class="label">1 mês atrás</div>
                  <div class="value-line"><span id="fg-prev-month-val">--</span> <span class="badge" id="fg-prev-month-rating">--</span> <small id="fg-prev-month-delta" class="fg-delta ms-2"></small></div>
                </div>
              </div>
              <div class="col">
                <div class="mini-stat">
                  <div class="label">1 ano atrás</div>
                  <div class="value-line"><span id="fg-prev-year-val">--</span> <span class="badge" id="fg-prev-year-rating">--</span> <small id="fg-prev-year-delta" class="fg-delta ms-2"></small></div>
                </div>
              </div>
            </div>
          </div>
          <div id="hist-error" class="alert alert-danger d-none m-3" role="alert"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container py-3">
  <div class="accordion" id="fg-accordion">
    <div class="accordion-item">
      <h2 class="accordion-header" id="fg-indicators-heading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fg-indicators-collapse" aria-expanded="false" aria-controls="fg-indicators-collapse">
          Indicadores que compõem o Fear & Greed
        </button>
      </h2>
      <div id="fg-indicators-collapse" class="accordion-collapse collapse" aria-labelledby="fg-indicators-heading" data-bs-parent="#fg-accordion">
        <div class="accordion-body p-0">
          <div class="container py-3" id="indicators-section">
            <div class="row g-3" id="indicators-grid"></div>
            <div id="indicators-error" class="alert alert-danger d-none" role="alert"></div>
            <div class="mb-3"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="container py-3" id="orm-section">
  <div class="card">
    <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-2">
        <span>Operebem Risk Momentum (ORM)</span>
        <a id="orm-info" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" data-bs-placement="right" data-bs-html="true" title="<div style=&quot;text-align:left&quot;>Desenvolvido pela OpereBem, o ORM (OpereBem Risk Momentum) é um velocímetro de risco que mede a convergência entre ativos de risco e de proteção, refletindo o sentimento do mercado em tempo real. Quando há divergência, o ORM permanece neutro; quando há alinhamento total, sinaliza zonas de Extreme Risk Off, Risk Off, Risk On ou Extreme Risk On, sempre com zero atraso.</div>">?</a>
      </div>
      <small class="text-muted"><span class="dot-online"></span>Intraday • Operebem Data</small>
    </div>
    <div class="card-body d-flex flex-column flex-md-row align-items-stretch gap-4">
      <div class="text-center">
        <div id="orm-score" style="font-size: 48px; line-height:1; font-weight:800;">--</div>
        <div id="orm-rating" class="badge" style="font-size:.95rem; padding:.45rem .6rem;">--</div>
        <div id="orm-gauge-hc" class="fg-gauge mt-2" style="height: 240px;"></div>
        <div id="orm-barometer" class="my-2">
          <div class="barometer-scale d-flex">
            <div class="seg seg-ef flex-fill text-center">Extreme Risk Off</div>
            <div class="seg seg-f  flex-fill text-center">Risk Off</div>
            <div class="seg seg-n  flex-fill text-center">Neutro</div>
            <div class="seg seg-g  flex-fill text-center">Risk On</div>
            <div class="seg seg-eg flex-fill text-center">Extreme Risk On</div>
          </div>
          <div class="barometer-needle" id="orm-needle"></div>
          <div class="barometer-ruler mt-1"><span>0</span><span>25</span><span>50</span><span>75</span><span>100</span></div>
          <div class="barometer-classes"><span>Pânico</span><span>Risk Off</span><span>Neutro</span><span>Risk On</span><span>Euforia</span></div>
        </div>
      </div>
      <div class="flex-grow-1 d-flex flex-column justify-content-between w-100">
        <div id="orm-bar" style="height: 220px; width: 100%;"></div>
        <div id="orm-bubble" style="height: 220px; width: 100%;" class="mt-3"></div>
      </div>
    </div>
  </div>
</div>

<div class="container py-3" id="orm-details-section">
  <div class="accordion" id="orm-accordion">
    <div class="accordion-item">
      <h2 class="accordion-header" id="orm-details-heading">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-details-collapse" aria-expanded="false" aria-controls="orm-details-collapse">
          Composição do ORM
        </button>
      </h2>
      <div id="orm-details-collapse" class="accordion-collapse collapse" aria-labelledby="orm-details-heading" data-bs-parent="#orm-accordion">
        <div class="accordion-body">
          <div class="table-responsive">
            <table class="table table-sm align-middle" id="orm-details-table">
              <thead>
                <tr>
                  <th>Ativo</th>
                  <th>Tipo</th>
                  <th>Peso</th>
                  <th>Último</th>
                  <th>Oscilação</th>
                  <th>Mínima</th>
                  <th>Máxima</th>
                  <th>Score</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div id="orm-details-error" class="alert alert-danger d-none" role="alert"></div>
          <div class="mt-4">
            <div class="accordion" id="orm-faq-accordion">
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q1-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q1" aria-expanded="false" aria-controls="orm-faq-q1">
                    O que é o Operebem Risk Momentum (ORM)?
                  </button>
                </h2>
                <div id="orm-faq-q1" class="accordion-collapse collapse" aria-labelledby="orm-faq-q1-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    O Operebem Risk Momentum (ORM) é um índice proprietário que mede, em tempo real, a convergência entre ativos de risco e de proteção. O ORM varia de 0 a 100, onde 0 indica aversão extrema a risco e 100 indica apetite extremo a risco. É um velocímetro de fluxo intraday — não um preditor.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q2-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q2" aria-expanded="false" aria-controls="orm-faq-q2">
                    Como o ORM é calculado?
                  </button>
                </h2>
                <div id="orm-faq-q2" class="accordion-collapse collapse" aria-labelledby="orm-faq-q2-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    Para cada um dos 8 ativos, calculamos a posição do último preço dentro do range intraday (mínima→máxima). Para ativos Risk-On usamos a posição direta, para Risk-Off usamos o inverso (quanto mais próximo da mínima, maior o score). O ORM é a média simples das 8 pontuações (0–100).
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q3-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q3" aria-expanded="false" aria-controls="orm-faq-q3">
                    Por que o ORM considera o score individual de cada ativo?
                  </button>
                </h2>
                <div id="orm-faq-q3" class="accordion-collapse collapse" aria-labelledby="orm-faq-q3-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    Isso assegura comparabilidade (0–100), captura convergência/divergência entre classes de ativos e reduz dependência de uma única fonte. A média simples agrega diferentes dimensões do risco global.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q4-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q4" aria-expanded="false" aria-controls="orm-faq-q4">
                    Por que o US 10-Year (US10Y) é tratado como Risk-On?
                  </button>
                </h2>
                <div id="orm-faq-q4" class="accordion-collapse collapse" aria-labelledby="orm-faq-q4-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    No intraday, yields tendem a subir em dias de apetite a risco (venda de títulos para comprar ações) e cair em aversão (compra de títulos). O ORM captura esse fluxo de curto prazo.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q5-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q5" aria-expanded="false" aria-controls="orm-faq-q5">
                    O ORM usa VIX spot ou futuro?
                  </button>
                </h2>
                <div id="orm-faq-q5" class="accordion-collapse collapse" aria-labelledby="orm-faq-q5-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    Usamos VIX spot (à vista), pois reflete a expectativa imediata de volatilidade. Futuros carregam efeitos de term structure (contango/backwardation) que distorcem o sentimento atual.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q6-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q6" aria-expanded="false" aria-controls="orm-faq-q6">
                    Por que estes 8 ativos (ORM-8)?
                  </button>
                </h2>
                <div id="orm-faq-q6" class="accordion-collapse collapse" aria-labelledby="orm-faq-q6-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    Eles cobrem dimensões-chave do risco global (EUA, tecnologia, emergentes, cripto, juros, volatilidade, dólar e ouro), balanceando representatividade e simplicidade.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q7-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q7" aria-expanded="false" aria-controls="orm-faq-q7">
                    Como interpretar os valores do ORM?
                  </button>
                </h2>
                <div id="orm-faq-q7" class="accordion-collapse collapse" aria-labelledby="orm-faq-q7-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    0–20 Extreme Risk Off · 20–40 Risk Off · 40–60 Neutro · 60–80 Risk On · 80–100 Extreme Risk On.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q8-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q8" aria-expanded="false" aria-controls="orm-faq-q8">
                    Por que o ORM não usa dados históricos (médias, ATR, etc.)?
                  </button>
                </h2>
                <div id="orm-faq-q8" class="accordion-collapse collapse" aria-labelledby="orm-faq-q8-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    Para manter zero atraso e refletir o sentimento do dia, usamos apenas mínima, máxima e último intraday. Métricas históricas podem ser exibidas em painéis complementares.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q9-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q9" aria-expanded="false" aria-controls="orm-faq-q9">
                    O que são “dados anteriores (stale)”? 
                  </button>
                </h2>
                <div id="orm-faq-q9" class="accordion-collapse collapse" aria-labelledby="orm-faq-q9-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    São cotações cujo timestamp saiu da janela de recência configurada no backend. Exibimos aviso para transparência — ainda são úteis, mas podem não refletir o último fluxo.
                  </div>
                </div>
              </div>
              <div class="accordion-item">
                <h2 class="accordion-header" id="orm-faq-q10-h">
                  <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#orm-faq-q10" aria-expanded="false" aria-controls="orm-faq-q10">
                    Como o ORM lida com pré-mercado (ex.: EEM com mínimas zeradas)?
                  </button>
                </h2>
                <div id="orm-faq-q10" class="accordion-collapse collapse" aria-labelledby="orm-faq-q10-h" data-bs-parent="#orm-faq-accordion">
                  <div class="accordion-body text-secondary">
                    Quando não há mínima/máxima intraday válidas (muitos ativos em pré-mercado reportam 0), estimamos uma faixa sintética ao redor do fechamento anterior, ajustada pela variação percentual (quando disponível). Aplicamos um piso de amplitude mínima e marcamos a faixa como aproximada. Isso mantém coerência visual sem criar viés.
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
</div>

<style>
/* Flash feedback (valores atualizados) */
@keyframes flashUp { 0%{background:rgba(34,197,94,.22)} 100%{background:transparent} }
@keyframes flashDown { 0%{background:rgba(239,68,68,.22)} 100%{background:transparent} }
.flash-up { animation: flashUp .6s ease; }
.flash-down { animation: flashDown .6s ease; }

/* Pills e badges translúcidas */
.pill-on { border-radius:9999px; padding:.15rem .55rem; border:1px solid rgba(16,185,129,.45); background:rgba(16,185,129,.12); color:#10b981; font-weight:600; }
.pill-off{ border-radius:9999px; padding:.15rem .55rem; border:1px solid rgba(239,68,68,.45); background:rgba(239,68,68,.12); color:#ef4444; font-weight:600; }
.status-badge{ border-radius:9999px; padding:.15rem .5rem; border:1px solid transparent; font-weight:600; }

/* Barra de estados (CNN/ORM) */
.barometer-scale{ position:relative; border-radius:9999px; overflow:hidden; }
.barometer-scale .seg{ height:inherit; font-size:0; line-height:0; }
.seg-ef{ background:#ef4444; opacity:.25; }
.seg-f { background:#f59e0b; opacity:.25; }
.seg-n { background:#6b7280; opacity:.25; }
.seg-g { background:#10b981; opacity:.25; }
.seg-eg{ background:#22c55e; opacity:.25; }
.barometer-needle { position: absolute; top: -3px; left: 0%; width: 0; height: 26px; pointer-events: none; }
.barometer-needle::after { content: ''; position: absolute; top: 0; left: -1px; width: 2px; height: 100%; background: #1e40af; border-radius: 2px; box-shadow: 0 0 0 1px rgba(0,0,0,0.06); }
html.dark-blue .barometer-needle::after, html.all-black .barometer-needle::after { background: #60a5fa; box-shadow: 0 0 0 1px rgba(255,255,255,0.08); }
/* ORM score cell cosmetics */
#orm-details-table td.score-cell { vertical-align: middle; }
#orm-details-table .score-box { min-width: 42px; padding: .15rem .4rem; border:1px solid var(--border-color); border-radius: 8px; background: rgba(127,127,127,0.06); }
#orm-details-table .score-label { white-space: nowrap; }
</style>

<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
document.addEventListener('DOMContentLoaded', function(){
  const summaryUrl = '/api/fg/summary';
  const historicalUrl = '/api/fg/historical';
  const indicatorUrl = (key) => `/api/fg/indicator/${encodeURIComponent(key)}`;

  function loadScript(src){
    return new Promise((resolve, reject) => {
      const s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.onload = () => resolve();
      s.onerror = () => reject(new Error('Falha ao carregar ' + src));
      document.head.appendChild(s);
    });
  }

  let lwcReady;
  function ensureLwc(){
    if (window.LightweightCharts) return Promise.resolve();
    if (!lwcReady) {
      lwcReady = loadScript('/assets/js/lightweight-charts.standalone.production.js')
        .catch(() => loadScript('https://unpkg.com/lightweight-charts@4.1.1/dist/lightweight-charts.standalone.production.js'))
        .then(() => undefined);
    }
    return lwcReady;
  }

  function themeIsDark(){
    const html = document.documentElement;
    if (!html) return false;
    const cls = html.classList;
    return cls.contains('dark-blue') || cls.contains('all-black');
  }

  // Highcharts loader (gauge) com fallback local → CDN
  let hcReady;
  function ensureHighcharts(){
    if (window.Highcharts && Highcharts?.seriesTypes?.gauge) return Promise.resolve();
    if (!hcReady) {
      const LOCAL_BASE = '/assets/vendor/highcharts';
      const CDN_BASE = 'https://code.highcharts.com';
      const loadPair = (base) => loadScript(`${base}/highcharts.js`).then(() => loadScript(`${base}/highcharts-more.js`));
      hcReady = loadPair(LOCAL_BASE)
        .catch(() => loadPair(CDN_BASE))
        .then(() => { try { if (window.Highcharts) { Highcharts.setOptions({ accessibility: { enabled: false } }); } } catch(_){ } });
    }
    return hcReady;
  }

  function ratingFromScore(s){
    if (s <= 24) return { label: 'Medo Extremo', color: '#ef4444' };
    if (s <= 44) return { label: 'Medo', color: '#f59e0b' };
    if (s < 56) return { label: 'Neutro', color: '#6b7280' };
    if (s <= 75) return { label: 'Ganância', color: '#10b981' };
    return { label: 'Ganância Extrema', color: '#22c55e' };
  }

  function setBadge(el, txt, color){
    el.textContent = txt;
    el.style.backgroundColor = color;
    el.style.color = '#fff';
  }

  function renderBarometer(score){
    const n = document.getElementById('fg-needle');
    if (!n) return;
    let s = Number(score);
    if (!Number.isFinite(s)) s = 0;
    s = Math.max(0, Math.min(100, s));
    const pct = s; // 0-100
    n.style.left = pct + '%';
  }

  function renderOrmBarometer(score){
    const n = document.getElementById('orm-needle');
    if (!n) return;
    let s = Number(score);
    if (!Number.isFinite(s)) s = 0;
    s = Math.max(0, Math.min(100, s));
    n.style.left = s + '%';
  }

  // Highcharts gauge helpers
  let lastScore = null;
  let fgHcChart = null;
  let ormHcChart = null;
  let ormBarChart = null;
  let ormPieChart = null;
  let ormBubbleChart = null;
  let lastORMAssets = null;
  let ormAssetCache = {};
  let ormLastGood = {};
  let ormLoading = false;

  function hcGaugeOptions(title, dark){
    return {
      chart: { type: 'gauge', backgroundColor: 'transparent' },
      title: { text: title, style: { color: dark ? '#e5e7eb' : '#111827', fontSize: '12px' } },
      pane: { startAngle: -90, endAngle: 90, background: null, center: ['50%','80%'], size: '120%' },
      yAxis: {
        min: 0, max: 100, tickInterval: 25,
        minorTickInterval: 5,
        labels: { distance: 16, style: { color: dark ? '#d1d5db' : '#4b5563' } },
        tickColor: dark ? '#6b7280' : '#9ca3af',
        lineColor: 'transparent'
      },
      accessibility: { enabled: false },
      tooltip: { enabled: false },
      legend: { enabled: false },
      credits: { enabled: false },
      exporting: { enabled: false },
      series: [{
        name: title,
        data: [0],
        dial: { radius: '80%', backgroundColor: dark ? '#e5e7eb' : '#111827', baseLength: '0%', baseWidth: 8, rearLength: '0%' },
        dataLabels: { enabled: false }
      }]
    };
  }

  function applyFgBands(opts){
    opts.yAxis.plotBands = [
      { from: 0, to: 24, color: '#ef4444', thickness: 18 },
      { from: 24, to: 44, color: '#f59e0b', thickness: 18 },
      { from: 44, to: 56, color: '#6b7280', thickness: 18 },
      { from: 56, to: 75, color: '#10b981', thickness: 18 },
      { from: 75, to: 100, color: '#22c55e', thickness: 18 }
    ];
    return opts;
  }

  function applyOrmBands(opts){
    opts.yAxis.plotBands = [
      { from: 0, to: 20, color: '#ef4444', thickness: 18 },
      { from: 20, to: 40, color: '#f59e0b', thickness: 18 },
      { from: 40, to: 60, color: '#6b7280', thickness: 18 },
      { from: 60, to: 80, color: '#10b981', thickness: 18 },
      { from: 80, to: 100, color: '#22c55e', thickness: 18 }
    ];
    return opts;
  }

  function renderFgGaugeHC(value){
    const dark = themeIsDark();
    const base = applyFgBands(hcGaugeOptions('', dark));
    if (!fgHcChart) {
      fgHcChart = Highcharts.chart('fg-gauge-hc', base);
    } else {
      fgHcChart.update(base, true, true);
    }
    const v = Math.max(0, Math.min(100, Math.round(Number(value)||0)));
    fgHcChart.series[0].points[0].update(v, true, { duration: 300 });
  }

  function renderOrmGaugeHC(value){
    const dark = themeIsDark();
    const base = applyOrmBands(hcGaugeOptions('', dark));
    if (!ormHcChart) {
      ormHcChart = Highcharts.chart('orm-gauge-hc', base);
    } else {
      ormHcChart.update(base, true, true);
    }
    const v = Math.max(0, Math.min(100, Math.round(Number(value)||0)));
    ormHcChart.series[0].points[0].update(v, true, { duration: 300 });
  }

  function renderOrmCharts(assets){
    const dark = themeIsDark();
    const items = [
      { k: 'sp500_fut', n: 'S&P 500 Futuro',  on: true },
      { k: 'nasdaq_fut',n: 'Nasdaq Futuro',   on: true },
      { k: 'eem',       n: 'EEM',             on: true },
      { k: 'bitcoin',   n: 'Bitcoin',         on: true },
      { k: 'us10y',     n: 'US 10Y',          on: true },
      { k: 'vix',       n: 'VIX',             on: false },
      { k: 'dxy',       n: 'DXY',             on: false },
      { k: 'gold',      n: 'Gold',            on: false }
    ];
    const rows = [];
    let sumOn = 0, wOn = 0, sumOffPressure = 0, wOff = 0;
    (items || []).forEach(it => {
      const a = assets && assets[it.k];
      if (a && isFinite(a.last) && isFinite(a.high) && isFinite(a.low)) {
        // posDisp: orientação Risk-On para exibição unificada
        const posDisp = getMomentumScore(a.last, a.high, a.low, !!it.on);
        rows.push({ ...it, a, pos: posDisp });
        const w = Number(ORM_WEIGHTS[it.k] || 0);
        if (it.on) {
          // Força Risk-On (natural)
          const onComp = getMomentumScore(a.last, a.high, a.low, true);
          sumOn += onComp * w; wOn += w;
        } else {
          // Pressão Risk-Off (natural: pos*100) = 100 - invertido
          const offPress = 100 - getMomentumScore(a.last, a.high, a.low, false);
          sumOffPressure += offPress * w; wOff += w;
        }
      }
    });
    // Ordenar por peso (desc)
    const display = rows.slice().sort((a,b) => (Number(ORM_WEIGHTS[b.k]||0) - Number(ORM_WEIGHTS[a.k]||0)));
    const cats = [];
    const rangeData = [];
    const markerData = [];
    display.forEach((r, idx) => {
      cats.push(r.n);
      const bandColor = r.on ? 'rgba(16,185,129,0.12)' : 'rgba(239,68,68,0.12)';
      rangeData.push({ x: idx, low: 0, high: 100, color: bandColor, borderWidth: 0, custom: { name: r.n, risk: (r.on?'Risk On':'Risk Off'), last: r.a.last, high: r.a.high, low: r.a.low, pos: r.pos, stale: !!r.a.stale, approx: !!r.a.approx } });
      markerData.push({ x: idx, y: r.pos, marker: { symbol: 'circle', radius: 5, fillColor: '#fff', lineWidth: 2, lineColor: r.on ? '#10b981' : '#ef4444' }, custom: { name: r.n, risk: (r.on?'Risk On':'Risk Off'), last: r.a.last, high: r.a.high, low: r.a.low, pos: r.pos, stale: !!r.a.stale, approx: !!r.a.approx } });
    });
    const onAvg = wOn ? +(sumOn / wOn).toFixed(2) : 0;
    const offAvg = wOff ? +(sumOffPressure / wOff).toFixed(2) : 0;
    if (document.getElementById('orm-bar')) {
      const opts = {
        chart: { type: 'columnrange', inverted: true, backgroundColor: 'transparent' },
        title: { text: 'Posição Intraday Normalizada (0–100)', style: { color: dark ? '#e5e7eb' : '#111827', fontSize: '12px' } },
        subtitle: { text: 'Faixa 0–100 representa [low→high] do dia; o ponto marca o last (Risk Off invertido).', style: { color: dark ? '#9ca3af' : '#6b7280', fontSize: '11px' } },
        xAxis: { categories: cats, labels: { style: { color: dark ? '#e5e7eb' : '#111827' } }, tickLength: 0 },
        yAxis: { min: 0, max: 100, title: { text: null }, gridLineColor: dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)' },
        legend: { enabled: false }, credits: { enabled: false }, exporting: { enabled: false },
        tooltip: {
          shared: true, useHTML: true,
          formatter: function(){
            const scatter = (this.points || []).find(p => p.series.type === 'scatter');
            const rr = scatter ? scatter.point.custom : (this.point && this.point.custom ? this.point.custom : {});
            const stale = rr.stale ? '<br><em style=\"color:#f59e0b\">dados anteriores (stale: fora da janela de recência)</em>' : '';
            const approx = rr.approx ? '<br><em style="color:#6b7280">faixa intraday aproximada (estimada)</em>' : '';
            return `<div style="text-align:left">
              <strong>${rr.name || ''}</strong> · ${rr.risk || ''}${stale}<br>
              Score: <strong>${(rr.pos||0).toFixed(0)}</strong> (0–100)<br>
              Last: ${rr.last} · Range: ${rr.low} → ${rr.high}<br>
              Método: posição no range intraday${(rr.risk||'')==='Risk Off'?' (invertido)':''}${approx}
            </div>`;
          }
        },
        series: [
          { name: 'Faixa (0–100)', type: 'columnrange', data: rangeData, borderWidth: 0, tooltip: { pointFormat: '' } },
          { name: 'Posição (last)', type: 'scatter', data: markerData }
        ]
      };
      if (!ormBarChart) { ormBarChart = Highcharts.chart('orm-bar', opts); } else { ormBarChart.update(opts, true, true); }
    }
    // Removido o gráfico de Pressão Risk On / Risk Off
    // Bubble
    if (document.getElementById('orm-bubble')) {
      const yCats = ['Risk Off','Risk On'];
      function jitterFor(name){ const map={ 'S&P 500':0.06,'S&P 500 Futuro':-0.06,'Nasdaq 100':-0.05,'Nasdaq Futuro':0.05,'VTI':0.03,'EEM':-0.03,'Bitcoin':-0.04,'Ethereum':0.04,'US 10Y':0.02,'WTI':-0.01,'VIX':-0.02,'DXY':0.01,'Gold':-0.01 }; return map[name]||0; }
      const bubbleData = markerData.map((p,i) => {
        const c = p.custom || {};
        const ampRaw = (c.last && c.low && c.high) ? Math.max(0, ((c.high - c.low) / Math.max(1e-9, c.last)) * 100) : 0; // % amplitude
        const yIndex = (c.risk === 'Risk On') ? 1 : 0;
        return { x: p.y, y: yIndex + jitterFor(c.name), z: ampRaw, name: c.name, color: (c.risk==='Risk On')?'#10b981':'#ef4444', custom: c };
      });
      const zs = bubbleData.map(b => Number(b.z) || 0);
      let zMin = Math.min.apply(null, zs);
      let zMax = Math.max.apply(null, zs);
      if (!isFinite(zMin) || zMin < 0) zMin = 0;
      if (!isFinite(zMax) || zMax <= zMin) zMax = zMin + 1;
      const bubbleOpts = {
        chart: { type: 'bubble', backgroundColor: 'transparent', plotBorderWidth: 0 },
        title: { text: 'Bolhas: Score × Grupo, tamanho = amplitude intraday (%)', style: { color: dark ? '#e5e7eb' : '#111827', fontSize: '12px' } },
        xAxis: { min: 0, max: 100, title: { text: 'Score (0–100)' }, gridLineColor: dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)' },
        yAxis: { categories: yCats, title: { text: null }, labels: { style: { color: dark ? '#e5e7eb' : '#111827' } }, gridLineColor: dark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)' },
        legend: { enabled: false }, credits: { enabled: false }, exporting: { enabled: false },
        tooltip: {
          useHTML: true,
          formatter: function(){
            const c = this.point.custom || {};
            const stale = c.stale ? '<br><em style=\"color:#f59e0b\">dados anteriores (stale: fora da janela de recência)</em>' : '';
            return `<div style="text-align:left">
              <strong>${this.point.name}</strong> · ${c.risk}${stale}<br>
              Score: <strong>${(c.pos||0).toFixed(0)}</strong><br>
              Amplitude intraday: <strong>${(((c.high - c.low)/Math.max(1e-9,c.last))*100).toFixed(2)}%</strong><br>
              Last: ${c.last} · Range: ${c.low} → ${c.high}
            </div>`;
          }
        },
        series: [{ data: bubbleData, minSize: 8, maxSize: 60, zMin: zMin, zMax: zMax, sizeBy: 'area', zThreshold: 0 }]
      };
      if (!ormBubbleChart) { ormBubbleChart = Highcharts.chart('orm-bubble', bubbleOpts); } else { ormBubbleChart.update(bubbleOpts, true, true); }
    }
  }

  async function loadSummary(){
    const scoreEl = document.getElementById('fg-score');
    const ratingEl = document.getElementById('fg-rating');
    const updEl = document.getElementById('fg-updated');
    const errEl = document.getElementById('fg-error');
    errEl.classList.add('d-none');

    try {
      const res = await fetch(summaryUrl);
      const data = await res.json();
      if (!data || data.success !== true) throw new Error((data && data.message) || 'Falha ao carregar');
      const d = data.data || {};
      const score = Number(d.score ?? d.index ?? d.value ?? NaN);
      if (!Number.isFinite(score)) throw new Error('Dados inválidos');
      const rr = ratingFromScore(score);
      scoreEl.textContent = Math.round(score);
      setBadge(ratingEl, rr.label, rr.color);
      const ts = d.timestamp || d.updated_at || d.date || null;
      updEl.textContent = ts ? ('Atualizado: ' + ts) : '';

      renderBarometer(score);
      // Gauge (Highcharts)
      await ensureHighcharts();
      renderFgGaugeHC(score);
      lastScore = Math.round(score);

      const prevs = [
        { val: 'previous_close', idv: 'fg-prev-close-val', idr: 'fg-prev-close-rating', idd: 'fg-prev-close-delta' },
        { val: 'previous_1_week', idv: 'fg-prev-week-val', idr: 'fg-prev-week-rating', idd: 'fg-prev-week-delta' },
        { val: 'previous_1_month', idv: 'fg-prev-month-val', idr: 'fg-prev-month-rating', idd: 'fg-prev-month-delta' },
        { val: 'previous_1_year', idv: 'fg-prev-year-val', idr: 'fg-prev-year-rating', idd: 'fg-prev-year-delta' },
      ];
      function setDelta(elId, diff){
        const el = document.getElementById(elId);
        if (!el) return;
        const n = Math.round(diff);
        if (n > 0) { el.textContent = `▲ +${n}`; el.className = 'ms-2 fg-delta fg-delta-up'; }
        else if (n < 0) { el.textContent = `▼ ${n}`; el.className = 'ms-2 fg-delta fg-delta-down'; }
        else { el.textContent = '■ 0'; el.className = 'ms-2 fg-delta fg-delta-zero'; }
      }
      prevs.forEach(p => {
        const v = Number(d[p.val]);
        const elv = document.getElementById(p.idv);
        const elr = document.getElementById(p.idr);
        if (elv && Number.isFinite(v)) elv.textContent = Math.round(v);
        if (elr && Number.isFinite(v)) {
          const r = ratingFromScore(v);
          setBadge(elr, r.label, r.color);
        }
        if (Number.isFinite(v)) setDelta(p.idd, (score - v));
      });
    } catch(e) {
      errEl.textContent = String(e.message || e);
      errEl.classList.remove('d-none');
    }
  }

  function formatYmd(d){
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const da = String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${da}`;
  }

  function rangeFor(period){
    const end = new Date();
    let start = new Date(end);
    if (period === '1m') start.setMonth(end.getMonth()-1);
    else if (period === '3m') start.setMonth(end.getMonth()-3);
    else if (period === '1y') start.setFullYear(end.getFullYear()-1);
    else if (period === '5y') start.setFullYear(end.getFullYear()-5);
    return { start: formatYmd(start), end: formatYmd(end) };
  }

  let chart;
  let series;
  let histPoints = [];
  function setDeltaEl(elId, diff){
    const el = document.getElementById(elId);
    if (!el) return;
    const n = Math.round(diff);
    if (n > 0) { el.textContent = `▲ +${n}`; el.className = 'ms-2 fg-delta fg-delta-up'; }
    else if (n < 0) { el.textContent = `▼ ${n}`; el.className = 'ms-2 fg-delta fg-delta-down'; }
    else { el.textContent = '■ 0'; el.className = 'ms-2 fg-delta fg-delta-zero'; }
  }
  function applyPrevFromHistory(currentScore){
    if (!Array.isArray(histPoints) || histPoints.length < 2) return;
    const last = histPoints[histPoints.length-1];
    const prev = histPoints[histPoints.length-2];
    function findBefore(days){
      const target = (last.time||0) - (days*86400);
      for (let i=histPoints.length-1;i>=0;i--){ if ((histPoints[i].time||0) <= target) return histPoints[i].value; }
      return prev.value;
    }
    const vals = {
      previous_close: prev.value,
      previous_1_week: findBefore(7),
      previous_1_month: findBefore(30),
      previous_1_year: findBefore(365)
    };
    const map = [
      { k:'previous_close', v:'fg-prev-close-val', r:'fg-prev-close-rating', d:'fg-prev-close-delta' },
      { k:'previous_1_week', v:'fg-prev-week-val', r:'fg-prev-week-rating', d:'fg-prev-week-delta' },
      { k:'previous_1_month', v:'fg-prev-month-val', r:'fg-prev-month-rating', d:'fg-prev-month-delta' },
      { k:'previous_1_year', v:'fg-prev-year-val', r:'fg-prev-year-rating', d:'fg-prev-year-delta' },
    ];
    map.forEach(it => {
      const val = Number(vals[it.k]);
      const elv = document.getElementById(it.v);
      const elr = document.getElementById(it.r);
      if (elv && Number.isFinite(val)) elv.textContent = Math.round(val);
      if (elr && Number.isFinite(val)) { const rr = ratingFromScore(val); setBadge(elr, rr.label, rr.color); }
      if (Number.isFinite(val)) setDeltaEl(it.d, (Number(currentScore)||0) - val);
    });
  }

  function buildChartOptions(){
    const dark = themeIsDark();
    const allBlack = themeIsAllBlack();
    const bg = allBlack ? '#000000' : (dark ? '#0b1227' : '#ffffff');
    const transparent = 'rgba(0,0,0,0)';
    return {
      layout: {
        background: { color: 'transparent' },
        textColor: dark ? '#d1d5db' : '#1f2937',
      },
      grid: {
        vertLines: { color: transparent },
        horzLines: { color: transparent },
      },
      rightPriceScale: {
        borderColor: transparent,
        scaleMargins: { top: 0, bottom: 0 }
      },
      timeScale: { borderColor: transparent },
      crosshair: { mode: LightweightCharts.CrosshairMode.Magnet },
      localization: { priceFormatter: p => Math.round(p) },
      handleScroll: { mouseWheel: false, pressedMouseMove: false, horzTouchDrag: false, vertTouchDrag: false },
      handleScale: { axisPressedMouseMove: false, mouseWheel: false, pinch: false },
    };
  }

  function createTooltip(container){
    let t = container.querySelector('.chart-tooltip');
    if (!t) {
      t = document.createElement('div');
      t.className = 'chart-tooltip';
      t.style.position = 'absolute';
      t.style.zIndex = '10';
      t.style.pointerEvents = 'none';
      t.style.padding = '6px 8px';
      t.style.borderRadius = '4px';
      t.style.fontSize = '12px';
      t.style.background = 'rgba(0,0,0,0.7)';
      t.style.color = '#fff';
      t.style.display = 'none';
      container.style.position = 'relative';
      container.appendChild(t);
    }
    return t;
  }

  function formatYmdFromSec(sec){
    const d = new Date(Number(sec) * 1000);
    if (Number.isNaN(d.getTime())) return '';
    const y = d.getFullYear();
    const m = String(d.getMonth()+1).padStart(2,'0');
    const da = String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${da}`;
  }

  function createChart(){
    const container = document.getElementById('chart-container');
    if (!container) return;
    container.innerHTML = '';
    const width = Math.max((container && container.clientWidth) ? container.clientWidth : 0, 320);
    const height = Math.max((container && container.clientHeight) ? container.clientHeight : 0, 260);
    try {
      chart = window.LightweightCharts.createChart(container, Object.assign({ width: width, height: height }, buildChartOptions()));
      // Garantir escala 0-100 sem margens
      chart.applyOptions({ rightPriceScale: { scaleMargins: { top: 0, bottom: 0 } } });
      const dark = themeIsDark();
      series = chart.addLineSeries({
        color: dark ? '#22c55e' : '#0ea5e9',
        lineWidth: 2,
        priceLineVisible: false,
        autoscaleInfoProvider: () => ({ priceRange: { minValue: 0, maxValue: 100 }})
      });
      // Série sem margens verticais e com faixa 0-100 fixa
      series.priceScale().applyOptions({ scaleMargins: { top: 0, bottom: 0 } });
      new ResizeObserver(() => {
        const w = container.clientWidth || width;
        const h = container.clientHeight || height;
        chart.applyOptions({ width: w, height: h });
      }).observe(container);
      const tip = createTooltip(container);
      chart.subscribeCrosshairMove(param => {
        if (!param || !param.time || !param.point) {
          tip.style.display = 'none';
          return;
        }
        const price = param.seriesPrices ? param.seriesPrices.get(series) : undefined;
        if (price === undefined || price === null) { tip.style.display = 'none'; return; }
        tip.textContent = `${formatYmdFromSec(param.time)}  •  FGI: ${Math.round(Number(price))}`;
        tip.style.left = Math.max(0, param.point.x - 40) + 'px';
        tip.style.top = Math.max(0, param.point.y - 40) + 'px';
        tip.style.display = 'block';
      });
    } catch (e) {
      const errEl = document.getElementById('hist-error');
      if (errEl) {
        errEl.textContent = 'Erro ao criar gráfico: ' + String(e.message || e);
        errEl.classList.remove('d-none');
      }
    }
  }

  function setPing(ms){
    try{
      const el = document.getElementById('fg-ping-label');
      if(el){
        const latency = Math.max(0, Math.round(ms));
        el.innerHTML = `<span class="dot-online"></span>Operebem Data • ${latency} ms`;
      }
    }catch(_){ }
  }

  async function loadHistorical(period){
    const errEl = document.getElementById('hist-error');
    const lh = document.getElementById('legend-hdr');
    const rangeLabel = document.getElementById('range-label');
    errEl.classList.add('d-none');
    if (lh) lh.textContent = '';

    const { start, end } = rangeFor('1y');
    rangeLabel.textContent = `${start} → ${end}`;

    try {
      const t0 = performance.now();
      const url = `${historicalUrl}?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&limit=365`;
      const res = await fetch(url);
      const data = await res.json();
      setPing(performance.now() - t0);
      if (!data || data.success !== true) throw new Error((data && data.message) || 'Falha ao carregar');
      let arr = Array.isArray(data.data) ? data.data : (data && data.data && Array.isArray(data.data.data) ? data.data.data : []);
      if (!Array.isArray(arr)) {
        const v2hist = data && data.data && Array.isArray(data.data.historical) ? data.data.historical : null;
        if (v2hist) arr = v2hist;
      }
      if (!Array.isArray(arr)) {
        const maybe = data && data.data && data.data.fear_and_greed_historical && data.data.fear_and_greed_historical.data;
        if (Array.isArray(maybe)) arr = maybe; else arr = [];
      }
      const points = arr.map(it => {
        // Shape A: top-level fields (date/time/timestamp + score/value)
        let t = it.date || it.time || it.timestamp || it.day || it.dt;
        let v = (it.score ?? it.value ?? it.index ?? it.fgi ?? it.fear_greed);

        // Shape B: nested fear_and_greed object
        if ((t === undefined || v === undefined) && it && typeof it === 'object' && it.fear_and_greed) {
          const fg = it.fear_and_greed;
          v = (fg.score ?? fg.value ?? fg.index);
          t = fg.timestamp || fg.date || (fg.x !== undefined ? fg.x : undefined);
        }

        // Shape C: x/y pairs
        if ((t === undefined || v === undefined) && it && typeof it === 'object' && (it.x !== undefined && it.y !== undefined)) {
          v = it.y;
          t = it.x;
        }

        if (t === undefined || v === undefined) return null;
        const numV = Number(v);
        if (!Number.isFinite(numV)) return null;

        let sec;
        if (typeof t === 'number') {
          // V2 timestamp em segundos; se vier ms, converter
          sec = (t > 1e12) ? Math.floor(t / 1000) : Math.floor(t);
        } else {
          const ts = String(t);
          const d = new Date(ts.includes('T') ? ts : (ts + 'T00:00:00Z'));
          if (Number.isNaN(d.getTime())) return null;
          sec = Math.floor(d.getTime() / 1000);
        }
        const clamped = Math.max(0, Math.min(100, Math.round(numV)));
        return { time: sec, value: clamped };
      }).filter(Boolean);
      // Ordenar por tempo asc para evitar problemas de renderização
      points.sort((a,b) => (a.time||0) - (b.time||0));
      histPoints = points;
      if (!histPoints.length) {
        throw new Error('Sem dados históricos para o período selecionado');
      }
      await ensureLwc();
      createChart();
      if (!series) throw new Error('Série não inicializada');
      series.setData(points);
      // Fixar janela de tempo em 1 ano
      const startSec = Math.floor(new Date(start + 'T00:00:00Z').getTime() / 1000);
      const endSec = Math.floor(new Date(end + 'T23:59:59Z').getTime() / 1000);
      chart.timeScale().setVisibleRange({ from: startSec, to: endSec });
      if (points.length) {
        const last = points[points.length - 1];
        const color = themeIsDark() ? '#22c55e' : '#0ea5e9';
        const sw = `<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${color};margin-right:6px;vertical-align:middle;"></span>`;
        const legendHdr = document.getElementById('legend-hdr');
        if (legendHdr) legendHdr.innerHTML = `${sw}<span>Fear & Greed Index: <strong>${last.value}</strong></span>`;
        applyPrevFromHistory(last.value);
        // range já fixado em 1 ano
      }
    } catch(e) {
      errEl.textContent = String(e.message || e);
      errEl.classList.remove('d-none');
    }
  }

  // Period selector removido: histórico sempre 1 ano

  const htmlEl = document.documentElement;
  if (htmlEl) {
    new MutationObserver(muts => {
      if (muts.some(m => m.attributeName === 'class')) {
        // re-render histórico com pontos já carregados (se houver)
        if (histPoints && histPoints.length) {
          ensureLwc().then(() => {
            createChart();
            series.setData(histPoints);
            const { start, end } = rangeFor('1y');
            const startSec = Math.floor(new Date(start + 'T00:00:00Z').getTime() / 1000);
            const endSec = Math.floor(new Date(end + 'T23:59:59Z').getTime() / 1000);
            chart.timeScale().setVisibleRange({ from: startSec, to: endSec });
          });
        } else {
          loadHistorical();
        }
        renderWidgetLong();
        // atualizar visualizações no tema atual
        (async () => {
          await ensureHighcharts();
          if (lastScore !== null) renderFgGaugeHC(lastScore);
          if (lastORMScore !== null) renderOrmGaugeHC(lastORMScore);
          if (lastORMAssets) renderOrmCharts(lastORMAssets);
        })();
      }
    }).observe(htmlEl, { attributes: true });
  }

  function getCurrentTheme(){
    const html = document.documentElement;
    if (!html) return 'light';
    const cls = html.classList;
    if (cls.contains('dark-blue') || cls.contains('all-black')) return 'dark';
    return 'light';
  }

  function themeIsAllBlack(){
    const html = document.documentElement;
    return !!(html && html.classList && html.classList.contains('all-black'));
  }

  let tvTapeKey = '';
  let tvTapeRendering = false;
  function renderWidgetLong(){
    const container = document.getElementById('widget_long');
    if (!container) return;
    // Não limpar os filhos internos exigidos pelo widget
    const theme = getCurrentTheme();
    const cfg = {
      symbols: [
        { description: "Bitcoin",  proName: "CRYPTO:BTCUSD" },
        { description: "Etherium", proName: "CRYPTO:ETHUSD" },
        { description: "XRP",       proName: "CRYPTO:XRPUSD" },
        { description: "Solana",    proName: "CRYPTO:SOLUSD" },
        { description: "BNB",       proName: "CRYPTO:BNBUSD" },
        { description: "Dogecoin",  proName: "CRYPTO:DOGEUSD" },
        { description: "USDT",      proName: "COINBASE:USDTUSD" },
        { description: "USDC",      proName: "BITSTAMP:USDCUSD" },
        { description: "DXY",       proName: "INDEX:DXY" }
      ],
      showSymbolLogo: true,
      displayMode: "compact",
      locale: "br",
      colorTheme: theme,
      isTransparent: theme !== 'light'
    };
    // Resetar completamente o container para um único placeholder
    container.innerHTML = '<div class="tradingview-widget-container__widget"></div>';
    const inner = container.querySelector('.tradingview-widget-container__widget');
    // Remover scripts/iframes anteriores potencialmente persistentes
    Array.from(container.querySelectorAll('iframe, .tradingview-widget-copyright, a[href*="tradingview"]')).forEach(el => el.remove());
    const old = document.getElementById('tv-tape');
    if (old && old.parentElement) old.parentElement.removeChild(old);
    Array.from(container.querySelectorAll('script[src*="embed-widget-ticker-tape"], script[data-tvw="1"]')).forEach(el => el.remove());
    // Singleton by config key (theme)
    const newKey = theme;
    if (tvTapeRendering) {
      return; // evitar dupla inicialização em fluxo rápido
    }
    if (tvTapeKey === newKey && inner && inner.querySelector('iframe')) {
      return; // already rendered with same theme
    }
    tvTapeKey = newKey;
    const s = document.createElement('script');
    s.src = 'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js';
    s.async = true;
    s.text = JSON.stringify(cfg);
    s.setAttribute('data-tvw','1');
    s.id = 'tv-tape';
    // Adiar ligeiramente para garantir que o placeholder exista e injetar no inner container
    setTimeout(() => {
      if (!inner) return;
      tvTapeRendering = true;
      s.onload = s.onerror = () => { tvTapeRendering = false; };
      inner.appendChild(s);
    }, 0);
  }

  loadSummary();
  loadHistorical();
  renderWidgetLong();

  // ============================ ORM (Operebem Risk Momentum) ============================
  function ormRatingFromScore(s){
    if (s < 20) return { label: 'Pânico', color: '#ef4444' };
    if (s < 40) return { label: 'Risk Off', color: '#f59e0b' };
    if (s < 60) return { label: 'Neutro', color: '#6b7280' };
    if (s < 80) return { label: 'Risk On', color: '#10b981' };
    return { label: 'Euforia', color: '#22c55e' };
  }

  // Pesos do ORM (somam 1.00). Se um ativo faltar, normalizamos pelos pesos presentes
  const ORM_WEIGHTS = {
    sp500_fut: 0.125,
    nasdaq_fut: 0.125,
    eem: 0.125,
    bitcoin: 0.125,
    us10y: 0.125,
    vix: 0.125,
    dxy: 0.125,
    gold: 0.125,
  };

  function getMomentumScore(last, high, low, isRiskOn){
    const h = Number(high), l = Number(low), p = Number(last);
    const range = h - l;
    if (!isFinite(range) || range === 0) return 50;
    const pos = (p - l) / range; // 0..1
    const pct = isRiskOn ? (pos * 100) : ((1 - pos) * 100);
    return Math.max(0, Math.min(100, pct));
  }

  function computeORM(assets){
    const keys = Object.keys(assets || {});
    if (!keys.length) return null;
    let wsum = 0; let vsum = 0;
    keys.forEach(k => {
      const a = assets[k];
      if (!a) return;
      const v = getMomentumScore(a.last, a.high, a.low, !!a.is_risk_on);
      const w = Number(ORM_WEIGHTS[k] || 0);
      if (isFinite(v) && w > 0) { vsum += v * w; wsum += w; }
    });
    if (wsum <= 0) return null;
    return +(vsum / wsum).toFixed(2);
  }

  let lastORMScore = null;
  function renderORMGauge(score){
    const svg = document.getElementById('orm-gauge-svg');
    if (!svg) return;
    // base layers (track + progress + needle + dot)
    const cx = 100, cy = 100, r = 80;
    svg.innerHTML = '';
    const track = document.createElementNS('http://www.w3.org/2000/svg','path');
    track.setAttribute('d', arcPath(cx, cy, r, 180, 0));
    track.setAttribute('class','track');
    svg.appendChild(track);
    const progress = document.createElementNS('http://www.w3.org/2000/svg','path');
    progress.setAttribute('id','orm-progress');
    progress.setAttribute('class','progress');
    svg.appendChild(progress);
    const needle = document.createElementNS('http://www.w3.org/2000/svg','line');
    needle.setAttribute('id','orm-needle');
    needle.setAttribute('class','needle');
    svg.appendChild(needle);
    const dot = document.createElementNS('http://www.w3.org/2000/svg','circle');
    dot.setAttribute('cx', String(cx));
    dot.setAttribute('cy', String(cy));
    dot.setAttribute('r', '3');
    dot.setAttribute('class','center-dot');
    svg.appendChild(dot);
    updateORMGauge(score);
  }

  function updateORMGauge(score){
    const svg = document.getElementById('orm-gauge-svg');
    if (!svg) return;
    const progress = svg.querySelector('#orm-progress');
    const needle = svg.querySelector('#orm-needle');
    const cx = 100, cy = 100, r = 80;
    let s = Number(score);
    if (!Number.isFinite(s)) s = 0;
    s = Math.max(0, Math.min(100, s));
    const endDeg = 180 - (180 * (s / 100));
    if (progress) {
      const rr = ormRatingFromScore(s);
      progress.removeAttribute('stroke-dasharray');
      progress.removeAttribute('stroke-dashoffset');
      progress.setAttribute('stroke', rr.color);
      progress.setAttribute('d', arcPath(cx, cy, r, 180, endDeg));
    }
    if (needle) {
      const toRad = (deg) => (deg * Math.PI) / 180;
      const nx = cx + r * Math.cos(toRad(endDeg));
      const ny = cy - r * Math.sin(toRad(endDeg));
      needle.setAttribute('x1', String(cx));
      needle.setAttribute('y1', String(cy));
      needle.setAttribute('x2', nx.toFixed(2));
      needle.setAttribute('y2', ny.toFixed(2));
    }
  }

  function renderORM(assets, preScore){
    const score = (typeof preScore === 'number' && isFinite(preScore)) ? preScore : null;
    const elScore = document.getElementById('orm-score');
    const elRating = document.getElementById('orm-rating');
    if (score === null) { if (elScore) elScore.textContent = '--'; return; }
    if (elScore) {
      const old = (typeof lastORMScore === 'number') ? lastORMScore : null;
      elScore.textContent = String(score);
      if (old !== null && Number.isFinite(old)) {
        if (score > old) { elScore.classList.remove('flash-down'); elScore.classList.add('flash-up'); setTimeout(() => elScore.classList.remove('flash-up'), 650); }
        else if (score < old) { elScore.classList.remove('flash-up'); elScore.classList.add('flash-down'); setTimeout(() => elScore.classList.remove('flash-down'), 650); }
      }
    }
    const rr = ormRatingFromScore(score);
    if (elRating) { setBadge(elRating, rr.label, rr.color); }
    // cobertura
    try {
      const keys = Object.keys(assets||{}); let cov = 0; keys.forEach(k => { const a = assets[k]; if (a && isFinite(a.last) && isFinite(a.high) && isFinite(a.low)) cov++; });
      const covEl = document.getElementById('orm-coverage');
      if (covEl) covEl.textContent = `${cov}/${keys.length}`;
    } catch(_){}
    lastORMScore = score;
    // atualizar barra de estados
    renderOrmBarometer(score);
  }

  let ormTimer = null;
  function scheduleOrmNext(ttl){
    try { if (ormTimer) { clearTimeout(ormTimer); ormTimer = null; } } catch(_){}
    const ms = Math.max(5000, Math.min(30000, (Number(ttl)||10)*1000));
    ormTimer = setTimeout(loadORM, ms);
  }

  async function loadORM(){
    try {
      if (ormLoading) return; ormLoading = true;
      const r1 = await fetch('/api/orm', { headers: { 'Accept': 'application/json' } });
      if (!r1.ok) throw new Error('Falha ao carregar /api/orm');
      const j1 = await r1.json();
      if (!(j1 && j1.success && j1.data && j1.data.assets)) throw new Error('Payload inválido do /api/orm');
      const a1 = j1.data.assets;
      const prev = lastORMAssets || null;
      renderORM(a1, j1.data.orm);
      await ensureHighcharts();
      renderOrmGaugeHC(Number(j1.data.orm) || 0);
      renderOrmCharts(a1);
      renderOrmDetails(a1, prev);
      lastORMAssets = a1;
      ormLoading = false; scheduleOrmNext(j1.data.ttl); return;
    } catch(e) {
      const err = document.getElementById('hist-error');
      if (err) { err.textContent = String(e.message || e); err.classList.remove('d-none'); }
      ormLoading = false; scheduleOrmNext(10);
    }
  }

  // Renderização da tabela de composição do ORM
  const ORM_DETAIL = [
    { k:'sp500_fut',  n:'S&P 500 Futuro',     type:'Risk-On',  w:0.125, why:'Antecipador do sentimento; pré-mercado e fluxo intraday.' },
    { k:'nasdaq_fut', n:'Nasdaq Futuro',      type:'Risk-On',  w:0.125, why:'Fluxo tecnológico intraday; beta alto.' },
    { k:'eem',        n:'EEM',                type:'Risk-On',  w:0.125, why:'Mercados emergentes, apetite por risco global.' },
    { k:'bitcoin',    n:'Bitcoin',            type:'Risk-On',  w:0.125, why:'Risco alternativo; 24/7.' },
    { k:'us10y',      n:'US 10Y',             type:'Risk-On',  w:0.125, why:'Fluxo intraday dos yields correlacionado com Risk-On.' },
    { k:'vix',        n:'VIX',                type:'Risk-Off', w:0.125, why:'Medo/aversão a risco intraday.' },
    { k:'dxy',        n:'DXY',                type:'Risk-Off', w:0.125, why:'Voo para qualidade no USD.' },
    { k:'gold',       n:'Gold',               type:'Risk-Off', w:0.125, why:'Hedge clássico em stress.' },
  ];

  const ORM_TIPS = {
    sp500_fut: 'Contrato futuro do principal índice de ações dos EUA; reflete a expectativa de performance do mercado amplo e o fluxo intraday.',
    nasdaq_fut: 'Contrato futuro do índice de tecnologia; indica o sentimento em relação a ativos de crescimento e alta volatilidade (beta).',
    eem: 'ETF que acompanha mercados emergentes; mensura o fluxo de capital para ativos considerados de maior risco relativo.',
    bitcoin: 'Principal criptoativo; incluído como um indicador do apetite por risco em ativos digitais e especulativos.',
    us10y: 'Rendimento dos títulos do Tesouro Americano de 10 anos; sua variação intraday indica fluxos entre ativos de risco e refúgio.',
    vix: 'Índice de volatilidade implícita do S&P 500; mede a expectativa de instabilidade do mercado e a aversão ao risco.',
    dxy: 'Índice da força do dólar americano contra uma cesta de moedas; reflete a demanda por dólar como ativo de refúgio.',
    gold: 'Commodity (Ouro) utilizada como reserva de valor tradicional e ativo de refúgio (hedge) em períodos de incerteza.'
  };

  function fmtNum(n){ const v = Number(n); return Number.isFinite(v) ? v.toLocaleString(undefined,{maximumFractionDigits:2}) : '--'; }

  function renderOrmDetails(assets, prevAssets){
    const tbl = document.getElementById('orm-details-table');
    if (!tbl) return;
    const tb = tbl.querySelector('tbody');
    tb.innerHTML = '';
    function fmtPerc(n){ const v = Number(n); if (!Number.isFinite(v)) return '--'; return (v).toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + '%'; }
    function scoreBandLabel(s){ const v = Number(s); if (!Number.isFinite(v)) return '--'; if (v < 20) return 'Extreme Risk Off'; if (v < 40) return 'Risk Off'; if (v < 60) return 'Neutro'; if (v < 80) return 'Risk On'; return 'Extreme Risk On'; }
    const ordered = ORM_DETAIL.slice().sort((a,b) => (Number(ORM_WEIGHTS[b.k]||0) - Number(ORM_WEIGHTS[a.k]||0)));
    ordered.forEach(it => {
      const a = assets && assets[it.k];
      const p = prevAssets && prevAssets[it.k];
      let last='--', min='--', max='--', score='--', label='--', osc='--';
      let flashCls = '';
      if (a && isFinite(a.last) && isFinite(a.high) && isFinite(a.low)) {
        const s = getMomentumScore(a.last, a.high, a.low, it.type === 'Risk-On');
        last = fmtNum(a.last);
        min = fmtNum(a.low);
        max = fmtNum(a.high);
        score = Math.round(s).toString();
        label = scoreBandLabel(s);
        const prevLast = p && isFinite(p.last) ? Number(p.last) : null;
        if (prevLast !== null) {
          if (Number(a.last) > prevLast) flashCls = 'text-flash-up';
          else if (Number(a.last) < prevLast) flashCls = 'text-flash-down';
        }
        if (isFinite(a.pcp)) {
          const isDark = themeIsDark();
          const zeroColor = isDark ? '#ffffff' : 'var(--text-primary)';
          const color = a.pcp > 0 ? '#22c55e' : (a.pcp < 0 ? '#ef4444' : zeroColor);
          const pctText = (a.pcp).toLocaleString('pt-BR', { maximumFractionDigits: 2 }) + '%';
          osc = `<span style="font-weight:700;color:${color}">${pctText}</span>`;
        }
      }
      const tip = (ORM_TIPS[it.k] || '').replace(/"/g,'&quot;');
      const typePill = it.type === 'Risk-On'
        ? '<span class="pill-on">Risk-On</span>'
        : '<span class="pill-off">Risk-Off</span>';
      const peso = (it.w * 100).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '%';
      // status badge color (translúcido)
      let lbBg='rgba(107,114,128,.12)', lbBd='rgba(107,114,128,.45)', lbFg='#6b7280';
      if (label.indexOf('Extreme Risk Off') === 0) { lbBg='rgba(239,68,68,.12)'; lbBd='rgba(239,68,68,.45)'; lbFg='#ef4444'; }
      else if (label === 'Risk Off') { lbBg='rgba(245,158,11,.12)'; lbBd='rgba(245,158,11,.45)'; lbFg='#f59e0b'; }
      else if (label === 'Risk On') { lbBg='rgba(16,185,129,.12)'; lbBd='rgba(16,185,129,.45)'; lbFg='#10b981'; }
      else if (label.indexOf('Extreme Risk On') === 0 || label === 'Euforia') { lbBg='rgba(34,197,94,.12)'; lbBd='rgba(34,197,94,.45)'; lbFg='#22c55e'; }
      const labelHtml = `<span class="status-badge ms-1" style="background:${lbBg};border-color:${lbBd};color:${lbFg}">${label}</span>`;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-toggle="tooltip" data-bs-html="true" title="${tip}">?</button>${it.n}</td>
        <td>${typePill}</td>
        <td>${peso}</td>
        <td><span class="${flashCls==='text-flash-up'?'flash-up':(flashCls==='text-flash-down'?'flash-down':flashCls)}">${last}</span></td>
        <td>${osc}</td>
        <td>${min}</td>
        <td>${max}</td>
        <td class="score-cell">
          <div class="d-flex align-items-center">
            <div class="score-box text-center me-2"><strong>${score}</strong></div>
            <div class="score-label">${labelHtml}</div>
          </div>
        </td>
      `;
      tb.appendChild(tr);
    });
    try { document.querySelectorAll('#orm-details-table [data-bs-toggle="tooltip"]').forEach(el => { new bootstrap.Tooltip(el); }); } catch(_){ }
  }

  loadORM();

  // Inicializa tooltips Bootstrap (se disponível)
  try {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => { new bootstrap.Tooltip(el); });
  } catch(_){}

  const INDICATOR_GROUPS = [
    { id: 'market-momentum', title: 'Market Momentum', ptTitle: 'Momentum de Mercado', desc: 'É útil observar os níveis do mercado em comparação com os últimos meses. Quando o S&P 500 está acima de sua média móvel dos últimos 125 pregões, isso indica momentum positivo, ou seja, o mercado está em tendência de alta e os investidores estão confiantes. Por outro lado, se o índice está abaixo dessa média, isso mostra que os investidores estão ficando mais cautelosos ou temerosos. No Fear & Greed Index, o enfraquecimento do momentum é interpretado como sinal de medo, enquanto o aumento do momentum indica ganância, ou otimismo exagerado.', keys: ['market-momentum-sp500','market-momentum-sp125'] },
    { id: 'stock-price-strength', title: 'Stock Price Strength', ptTitle: 'Força dos Preços das Ações', desc: 'Algumas poucas ações muito grandes podem distorcer o desempenho geral do mercado, por isso é importante observar quantas ações estão se saindo bem em relação às que estão em queda. Esse indicador compara o número de ações da NYSE que atingiram máximas de 52 semanas com aquelas que registraram mínimas de 52 semanas. Quando há muito mais altas do que baixas, isso é um sinal altista, indicando ganância no mercado.', keys: ['stock-price-strength'] },
    { id: 'stock-price-breadth', title: 'Stock Price Breadth', ptTitle: 'Amplitude dos Preços das Ações', desc: 'O mercado é composto por milhares de ações sendo negociadas diariamente, e este indicador analisa o volume total de ações em alta comparado ao volume de ações em queda na NYSE. Quando o número é baixo ou negativo, é um sinal baixista, indicando que o sentimento geral está mais pessimista. O Fear & Greed Index interpreta a queda no volume de negociação como sinal de medo.', keys: ['stock-price-breadth'] },
    { id: 'put-call-options', title: 'Put and Call Options', ptTitle: 'Opções de Venda e Compra', desc: 'As opções são contratos que dão ao investidor o direito, mas não a obrigação, de comprar ou vender um ativo — como ações ou índices — a um preço e prazo determinados. As opções de venda (put) dão o direito de vender, enquanto as opções de compra (call) dão o direito de comprar. Quando a relação entre puts e calls, conhecida como put/call ratio, aumenta, isso indica que mais investidores estão se protegendo ou esperando quedas, um sinal de nervosismo. Uma relação acima de 1 é considerada baixista, e o Fear & Greed Index interpreta uma alta proporção de puts como sinal de medo.', keys: ['put-call-options'] },
    { id: 'market-volatility', title: 'Market Volatility', ptTitle: 'Volatilidade de Mercado', desc: 'A medida mais conhecida de sentimento de mercado é o Índice de Volatilidade da CBOE (VIX), que mede a volatilidade esperada das opções do S&P 500 para os próximos 30 dias. Geralmente, o VIX cai quando o mercado sobe e dispara quando as ações caem. Ao longo do tempo, ele tende a permanecer baixo em mercados de alta (bull markets) e elevado em mercados de queda (bear markets). O Fear & Greed Index considera o aumento da volatilidade como sinal de medo.', keys: ['market-volatility-vix','market-volatility-vix-50'] },
    { id: 'safe-haven-demand', title: 'Safe Haven Demand', ptTitle: 'Demanda por Ativos de Refúgio', desc: 'As ações são mais arriscadas que os títulos públicos, mas costumam oferecer maior retorno no longo prazo. Porém, em períodos curtos e de incerteza, os títulos podem superar as ações. O indicador Safe Haven Demand mede a diferença de desempenho entre títulos do Tesouro e ações nos últimos 20 pregões. Quando os títulos se saem melhor, significa que os investidores estão buscando segurança. No Fear & Greed Index, o aumento da demanda por ativos de refúgio é interpretado como sinal de medo.', keys: ['safe-haven-demand'] },
    { id: 'junk-bond-demand', title: 'Junk Bond Demand', ptTitle: 'Demanda por Títulos de Alto Risco', desc: 'Os títulos “junk”, ou de alto rendimento, têm maior risco de calote. Os rendimentos desses títulos caem quando os preços sobem e sobem quando os preços caem. Quando os investidores estão dispostos a comprar mais títulos de risco, o spread — diferença entre o rendimento dos junk bonds e os títulos do governo — diminui, indicando maior apetite ao risco. Por outro lado, um spread mais amplo mostra maior cautela. O Fear & Greed Index interpreta a demanda por junk bonds como sinal de ganância.', keys: ['junk-bond-demand'] },
  ];

  const PERCENT_KEYS = new Set(['stock-price-strength','safe-haven-demand','junk-bond-demand']);

  const indicatorPoints = {};

  function buildIndicatorCards(){
    const grid = document.getElementById('indicators-grid');
    if (!grid) return;
    grid.innerHTML = '';
    INDICATOR_GROUPS.forEach(g => {
      const col = document.createElement('div');
      col.className = 'col-12 col-md-6 col-lg-4';
      col.innerHTML = `
        <div class="card h-100 d-flex flex-column">
          <div class="card-header fw-semibold">
            <div class="d-flex align-items-center justify-content-between">
              <span>${g.title}</span>
              <div class="d-flex align-items-center gap-2">
                <small class="text-muted d-none d-md-inline"><span class="dot-online"></span>Operebem Data</small>
                <button type="button" class="btn btn-sm btn-outline-secondary ind-tip-btn" data-ind-tip="${(g.ptTitle + ' - ' + g.desc).replace(/"/g, '&quot;')}">?</button>
              </div>
            </div>
            <div class="small text-muted mt-1" id="ind-${g.id}-legendhdr">&nbsp;</div>
          </div>
          <div class="card-body p-0 d-flex flex-column">
            <div id="ind-${g.id}-chart" style="flex:1 1 auto; min-height: 260px;"></div>
            <div id="ind-${g.id}-error" class="alert alert-danger d-none mt-2 mx-2" role="alert"></div>
          </div>
        </div>`;
      grid.appendChild(col);
    });
    attachInfoTooltips();
  }

  function attachInfoTooltips(){
    const openTips = [];
    document.querySelectorAll('.ind-tip-btn').forEach(btn => {
      const text = btn.getAttribute('data-ind-tip') || '';
      let tip;
      function hide(){ if (tip){ tip.remove(); tip = null; } }
      function show(){
        hide();
        tip = document.createElement('div');
        tip.className = 'info-tip';
        tip.style.position = 'absolute';
        tip.style.zIndex = '20';
        tip.style.maxWidth = '320px';
        tip.style.padding = '8px 10px';
        tip.style.borderRadius = '6px';
        tip.style.fontSize = '12px';
        tip.style.lineHeight = '1.35';
        tip.style.background = 'rgba(0,0,0,0.85)';
        tip.style.color = '#fff';
        const rect = btn.getBoundingClientRect();
        tip.style.left = (window.scrollX + rect.left + rect.width + 8) + 'px';
        tip.style.top = (window.scrollY + rect.top - 4) + 'px';
        tip.textContent = text;
        document.body.appendChild(tip);
        openTips.push(tip);
      }
      btn.addEventListener('mouseenter', show);
      btn.addEventListener('mouseleave', hide);
      btn.addEventListener('click', (e) => { e.preventDefault(); if (tip) hide(); else show(); });
      window.addEventListener('scroll', hide, { passive: true });
    });
    document.addEventListener('click', (e) => {
      const target = e.target;
      if (!(target && target.classList && target.classList.contains('ind-tip-btn'))) {
        document.querySelectorAll('.info-tip').forEach(el => el.remove());
      }
    });
  }

  function labelForKey(k){
    if (k === 'market-momentum-sp500') return 'S&P 500';
    if (k === 'market-momentum-sp125') return 'MMA 125 Dias';
    if (k === 'market-volatility-vix') return 'VIX';
    if (k === 'market-volatility-vix-50') return 'MMA 50 Dias';
    if (k === 'stock-price-strength') return 'Força dos Preços';
    if (k === 'stock-price-breadth') return 'Amplitude de Preços';
    if (k === 'put-call-options') return 'Put/Call';
    if (k === 'safe-haven-demand') return 'Refúgio';
    if (k === 'junk-bond-demand') return 'Junk Bonds';
    return k.replaceAll('-', ' ');
  }

  function renderSeriesLegendHeader(containerId, refs){
    const el = document.getElementById(containerId);
    if (!el) return;
    const items = refs.map(r => {
      const sw = `<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:${r.color};margin-right:6px;vertical-align:middle;"></span>`;
      const lb = `<span>${labelForKey(r.key)}</span>`;
      return `<span class="me-3">${sw}${lb}</span>`;
    });
    el.innerHTML = items.join('');
  }

  function setLegendHeaderText(containerId, text){
    const el = document.getElementById(containerId);
    if (!el) return;
    el.textContent = text;
  }

  function legendDescForGroupId(id){
    if (id === 'stock-price-strength') return 'Novas máximas e mínimas de 52 semanas na NYSE (líquido)';
    if (id === 'stock-price-breadth') return 'Índice de Soma de Volume de McClellan';
    if (id === 'put-call-options') return 'Média de 5 dias do put/call ratio';
    if (id === 'safe-haven-demand') return 'Diferença entre os retornos de ações e títulos em 20 dias';
    if (id === 'junk-bond-demand') return 'Spread de rendimento: junk bonds vs. investment grade';
    return '';
  }

  function toUtcSeconds(ms){
    const n = Number(ms);
    if (!Number.isFinite(n)) return null;
    return Math.floor(n / 1000);
  }

  async function loadIndicatorsHistoricalOneYear(){
    try {
      const { start, end } = rangeFor('1y');
      const startSec = Math.floor(new Date(start + 'T00:00:00Z').getTime() / 1000);
      const endSec = Math.floor(new Date(end + 'T23:59:59Z').getTime() / 1000);
      const url = `/api/fg/indicators/historical?start_date=${encodeURIComponent(start)}&end_date=${encodeURIComponent(end)}&limit=365`;
      const res = await fetch(url);
      const json = await res.json();
      if (!json || json.success !== true) throw new Error((json && json.message) || 'Falha ao carregar indicadores');
      const series = (json.data && json.data.series) ? json.data.series : {};
      await ensureLwc();
      INDICATOR_GROUPS.forEach(g => {
        const errEl = document.getElementById(`ind-${g.id}-error`);
        if (errEl) errEl.classList.add('d-none');
        const container = document.getElementById(`ind-${g.id}-chart`);
        if (!container) return;
        const seriesData = g.keys.map(k => {
          const raw = series[k] || [];
          const pts = Array.isArray(raw) ? raw.map(p => {
            const sec = toUtcSeconds(p.x);
            const v = Number(p.y);
            if (!Number.isFinite(sec) || !Number.isFinite(v)) return null;
            return { time: sec, value: v };
          }).filter(Boolean) : [];
          indicatorPoints[k] = pts;
          return { key: k, points: pts };
        });
        container.innerHTML = '';
        const width = Math.max((container && container.clientWidth) ? container.clientWidth : 0, 280);
        const height = Math.max(container.clientHeight || 0, 260);
        const base = buildChartOptions();
        const isPercentGroup = g.keys.every(k => PERCENT_KEYS.has(k));
        if (isPercentGroup) {
          base.localization = Object.assign({}, base.localization, { priceFormatter: p => `${Number(p).toFixed(2)}%` });
        }
        const c = window.LightweightCharts.createChart(container, Object.assign({ width: width, height }, base));
        const refs = [];
        g.keys.forEach(k => {
          const color = k === 'market-momentum-sp125' ? '#f59e0b' : (k === 'market-volatility-vix-50' ? '#ef4444' : '#0ea5e9');
          const s = c.addLineSeries({ color: color, lineWidth: 2, priceLineVisible: false });
          const pts = indicatorPoints[k] || [];
          s.setData(pts);
          refs.push({ api: s, key: k, color });
        });
        c.timeScale().setVisibleRange({ from: startSec, to: endSec });
        new ResizeObserver(() => c.applyOptions({ width: container.clientWidth || width, height: container.clientHeight || height })).observe(container);
        const tip = createTooltip(container);
        c.subscribeCrosshairMove(param => {
          if (!param || !param.time || !param.point) { tip.style.display = 'none'; return; }
          const date = formatYmdFromSec(param.time);
          const parts = refs.map(r => {
            const v = param.seriesPrices ? param.seriesPrices.get(r.api) : undefined;
            if (v === undefined || v === null) return null;
            const label = labelForKey(r.key);
            const isPct = PERCENT_KEYS.has(r.key);
            const valStr = isPct ? `${Number(v).toFixed(2)}%` : `${Math.round(Number(v))}`;
            return `${label}: ${valStr}`;
          }).filter(Boolean);
          if (!parts.length) { tip.style.display = 'none'; return; }
          tip.textContent = `${date}  •  ${parts.join(' | ')}`;
          tip.style.left = Math.max(0, param.point.x - 60) + 'px';
          tip.style.top = Math.max(0, param.point.y - 40) + 'px';
          tip.style.display = 'block';
        });
        // Render legend in header: overlays show swatches; others show fixed description
        if (g.id === 'market-momentum' || g.id === 'market-volatility') {
          renderSeriesLegendHeader(`ind-${g.id}-legendhdr`, refs);
        } else {
          setLegendHeaderText(`ind-${g.id}-legendhdr`, legendDescForGroupId(g.id));
        }
      });
    } catch(e) {
      const grid = document.getElementById('indicators-grid');
      const err = document.getElementById('indicators-error');
      if (err) {
        err.textContent = String(e.message || e);
        err.classList.remove('d-none');
      }
      if (grid) grid.classList.add('opacity-50');
    }
  }

  function renderIndicatorsOnTheme(){
    INDICATOR_GROUPS.forEach(g => {
      const container = document.getElementById(`ind-${g.id}-chart`);
      if (!container) return;
      const anyPts = g.keys.some(k => (indicatorPoints[k] || []).length);
      if (!anyPts) return;
      ensureLwc().then(() => {
        container.innerHTML = '';
        const base = buildChartOptions();
        const height = Math.max(container.clientHeight || 0, 180);
        const width = container.clientWidth;
        const isPercentGroup = g.keys.every(k => PERCENT_KEYS.has(k));
        if (isPercentGroup) {
          base.localization = Object.assign({}, base.localization, { priceFormatter: p => `${Number(p).toFixed(2)}%` });
        }
        const c = window.LightweightCharts.createChart(container, Object.assign({ width, height }, base));
        const refs = [];
        g.keys.forEach(k => {
          const color = k === 'market-momentum-sp125' ? '#f59e0b' : (k === 'market-volatility-vix-50' ? '#ef4444' : '#0ea5e9');
          const s = c.addLineSeries({ color: color, lineWidth: 2, priceLineVisible: false });
          s.setData(indicatorPoints[k] || []);
          refs.push({ api: s, key: k, color });
        });
        const { start, end } = rangeFor('1y');
        const startSec = Math.floor(new Date(start + 'T00:00:00Z').getTime() / 1000);
        const endSec = Math.floor(new Date(end + 'T23:59:59Z').getTime() / 1000);
        c.timeScale().setVisibleRange({ from: startSec, to: endSec });
        new ResizeObserver(() => c.applyOptions({ width: container.clientWidth, height: container.clientHeight || height })).observe(container);
        const tip = createTooltip(container);
        c.subscribeCrosshairMove(param => {
          if (!param || !param.time || !param.point) { tip.style.display = 'none'; return; }
          const date = formatYmdFromSec(param.time);
          const parts = refs.map(r => {
            const v = param.seriesPrices ? param.seriesPrices.get(r.api) : undefined;
            if (v === undefined || v === null) return null;
            const label = labelForKey(r.key);
            const isPct = PERCENT_KEYS.has(r.key);
            const valStr = isPct ? `${Number(v).toFixed(2)}%` : `${Math.round(Number(v))}`;
            return `${label}: ${valStr}`;
          }).filter(Boolean);
          if (!parts.length) { tip.style.display = 'none'; return; }
          tip.textContent = `${date}  •  ${parts.join(' | ')}`;
          tip.style.left = Math.max(0, param.point.x - 60) + 'px';
          tip.style.top = Math.max(0, param.point.y - 40) + 'px';
          tip.style.display = 'block';
        });
        if (g.id === 'market-momentum' || g.id === 'market-volatility') {
          renderSeriesLegendHeader(`ind-${g.id}-legendhdr`, refs);
        } else {
          setLegendHeaderText(`ind-${g.id}-legendhdr`, legendDescForGroupId(g.id));
        }
      });
    });
  }

  buildIndicatorCards();
  loadIndicatorsHistoricalOneYear();

  // Ensure charts render correctly when the accordion is expanded
  try {
    const acc = document.getElementById('fg-indicators-collapse');
    if (acc) {
      acc.addEventListener('shown.bs.collapse', () => {
        renderIndicatorsOnTheme();
        // Also re-apply visible range for each chart
        const { start, end } = rangeFor('1y');
        const startSec = Math.floor(new Date(start + 'T00:00:00Z').getTime() / 1000);
        const endSec = Math.floor(new Date(end + 'T23:59:59Z').getTime() / 1000);
        // We can't access inner chart instances directly here; renderIndicatorsOnTheme recreates them and sets range.
      });
    }
  } catch(_){ }

  // Re-render indicadores ao trocar tema
  if (htmlEl) {
    new MutationObserver(muts => {
      if (muts.some(m => m.attributeName === 'class')) {
        renderIndicatorsOnTheme();
      }
    }).observe(htmlEl, { attributes: true });
  }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/app.php';
?>
