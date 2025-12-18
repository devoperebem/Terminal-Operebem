<?php
$title = 'Dashboard Ouro [TESTE] - Terminal Operebem';
$csrf_token = $_SESSION['csrf_token'] ?? '';

ob_start();
?>

<!-- 🧪 BANNER DE AMBIENTE DE TESTE -->
<div class="alert alert-warning m-3 text-center fw-bold shadow-sm" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: #000; border: 3px dashed #ff5722; border-radius: 12px;">
  <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
    <span style="font-size: 2rem;">🧪</span>
    <div>
      <div class="fs-4">AMBIENTE DE TESTE</div>
      <div class="small mt-1">Esta é uma versão de desenvolvimento (/dev/). Alterações aqui NÃO afetam a produção.</div>
    </div>
    <span style="font-size: 2rem;">🧪</span>
  </div>
</div>

<?php include __DIR__ . '/../../partials/market-status-tooltip-styles.php'; ?>

<?php include __DIR__ . '/../../partials/market-status-tooltip-styles.php'; ?>

<style>
/* Esconder copyright do TradingView */
.tradingview-widget-copyright { display: none !important; }

/* Ajustes para os cards nos diferentes temas */
html.light .card {
    background-color: #fff;
    border-color: rgba(0, 0, 0, 0.125);
}

html.dark-blue .card {
    background-color: #001233;
    border-color: rgba(255, 255, 255, 0.125);
}

html.all-black .card {
    background-color: #000;
    border-color: rgba(255, 255, 255, 0.125);
}

/* Tema para o ticker tape */
html.light #gold_ticker_tape,
body.light #gold_ticker_tape {
  background-color: #f4f4fa !important;
}

html.dark-blue #gold_ticker_tape,
body.dark-blue #gold_ticker_tape {
  background-color: #000a22 !important;
}

html.all-black #gold_ticker_tape,
body.all-black #gold_ticker_tape {
  background-color: #000 !important;
}

/* Widgets ocupando 100% dos cards e sem scroll */
.card .tradingview-widget-container { height: 100% !important; width: 100% !important; }
.card .tradingview-widget-container__widget { height: 100% !important; width: 100% !important; }
.card .tradingview-widget-container iframe { height: 100% !important; width: 100% !important; border: 0 !important; }

/* Estilos para tooltips de correla├º├úo */
.correlation-info {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background-color: rgba(13, 110, 253, 0.1);
  color: #0d6efd;
  font-size: 12px;
  font-weight: bold;
  cursor: help;
  margin-left: 6px;
  transition: all 0.2s ease;
}

.correlation-info:hover {
  background-color: #0d6efd;
  color: white;
}

html.dark-blue .correlation-info,
html.all-black .correlation-info {
  background-color: rgba(13, 110, 253, 0.2);
  color: #5ea3ff;
}

html.dark-blue .correlation-info:hover,
html.all-black .correlation-info:hover {
  background-color: #0d6efd;
  color: white;
}

/* Estilos para card de futuros e miners */
#gold_futures_grid .table td,
#gold_miners_grid .table td {
  padding: 0.4rem 0.5rem;
  vertical-align: middle;
}

#gold_futures_grid canvas,
#gold_miners_grid canvas {
  width: 100% !important;
  height: auto !important;
}

/* Garantir que os cards tenham a mesma altura */
#gold_futures_grid .card,
#gold_miners_grid .card {
  height: 100%;
}

/* Estilo para os títulos dos cards de Futuros e Miners igual ao dashboard principal */
.card_indices .card-header.title-card {
  position: relative;
  padding: 0.75rem 0.75rem 0.75rem 1.25rem;
  font-size: 1.2rem;
}

.card_indices .card-header.title-card > span:first-child {
  font-size: 1.2rem !important;
  font-weight: 700 !important;
}

/* Média das variações com mesmo tamanho */
.card_indices .card-header.title-card .media-percentage {
  font-size: 1em;
  padding: 2px 8px;
  border-radius: 8px;
  border: 1px solid rgba(0,0,0,0.1);
  background: transparent;
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
}

.media-percentage.positive {
  color: #37ed00;
  border-color: rgba(55, 237, 0, 0.3);
}

.media-percentage.negative {
  color: #FF0000;
  border-color: rgba(255, 0, 0, 0.3);
}

.media-percentage.neutral {
  color: #666;
  border-color: rgba(102, 102, 102, 0.3);
}

/* Tema escuro: cor neutra para média zero */
html.dark-blue .media-percentage.neutral,
html.all-black .media-percentage.neutral {
  color: #ffffff;
  border-color: rgba(255, 255, 255, 0.3);
}
</style>

<!-- Ticker Tape TradingView -->
<div class="container-fluid p-0">
  <div id="gold_ticker_tape" class="tradingview-widget-container w-100 mb-4"></div>
</div>

<div class="container-fluid mt-3">

  <!-- Se├º├úo: Ativos Principais -->
  <div class="row g-3 px-2 px-md-3 mb-3">
    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2">
          <span class="fs-6 fw-bold">Ouro</span>
          <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Ouro ├á vista (XAU/USD): Principal refer├¬ncia mundial para o pre├ºo do ouro, negociado 24h no mercado spot.">?</span>
        </div>
        <div class="card-body p-3">
          <div class="fs-5 fw-semibold mb-1" id="q_gold_price">--</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" id="q_gold_change">--</div>
            <div class="fs-6 fw-semibold" id="q_gold_pc">--</div>
          </div>
          <div class="small text-muted mt-1" id="q_gold_time">--</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2">
          <span class="fs-6 fw-bold">Ouro 2!</span>
          <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Fonte alternativa de cota├º├úo do ouro ├á vista, ├║til para compara├º├úo e valida├º├úo de pre├ºos.">?</span>
        </div>
        <div class="card-body p-3">
          <div class="fs-5 fw-semibold mb-1" id="q_gold2_price">--</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" id="q_gold2_change">--</div>
            <div class="fs-6 fw-semibold" id="q_gold2_pc">--</div>
          </div>
          <div class="small text-muted mt-1" id="q_gold2_time">--</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2">
          <span class="fs-6 fw-bold">DXY</span>
          <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                title="├ìndice do D├│lar (DXY): Correla├º├úo inversa forte com o ouro. D├│lar forte = ouro mais caro internacionalmente = menor demanda.">?</span>
        </div>
        <div class="card-body p-3">
          <div class="fs-5 fw-semibold mb-1" id="q_dxy_price">--</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" id="q_dxy_change">--</div>
            <div class="fs-6 fw-semibold" id="q_dxy_pc">--</div>
          </div>
          <div class="small text-muted mt-1" id="q_dxy_time">--</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2">
          <span class="fs-6 fw-bold">US10Y</span>
          <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                title="T├¡tulos de 10 anos (US10Y): Correla├º├úo inversa. Juros altos reduzem atratividade do ouro, que n├úo paga rendimentos.">?</span>
        </div>
        <div class="card-body p-3">
          <div class="fs-5 fw-semibold mb-1" id="q_us10y_price">--</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" id="q_us10y_change">--</div>
            <div class="fs-6 fw-semibold" id="q_us10y_pc">--</div>
          </div>
          <div class="small text-muted mt-1" id="q_us10y_time">--</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2">
          <span class="fs-6 fw-bold">VIX</span>
          <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                title="├ìndice de Volatilidade (VIX): Correla├º├úo positiva. Medo e incerteza impulsionam investidores a buscar o ouro como porto seguro.">?</span>
        </div>
        <div class="card-body p-3">
          <div class="fs-5 fw-semibold mb-1" id="q_vix_price">--</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" id="q_vix_change">--</div>
            <div class="fs-6 fw-semibold" id="q_vix_pc">--</div>
          </div>
          <div class="small text-muted mt-1" id="q_vix_time">--</div>
        </div>
      </div>
    </div>

    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2">
          <span class="fs-6 fw-bold">Gold Vol</span>
          <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Volatilidade do Ouro (GVZ): Mede expectativa de flutua├º├úo de pre├ºo do ouro. Valores altos indicam maior incerteza no mercado.">?</span>
        </div>
        <div class="card-body p-3">
          <div class="fs-5 fw-semibold mb-1" id="q_gvz_price">--</div>
          <div class="d-flex align-items-center justify-content-between">
            <div class="small" id="q_gvz_change">--</div>
            <div class="fs-6 fw-semibold" id="q_gvz_pc">--</div>
          </div>
          <div class="small text-muted mt-1" id="q_gvz_time">--</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Se├º├úo: Gr├ífico Principal do Ouro -->
  <div class="row g-3 px-2 px-md-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body p-2 p-md-3">
          <div id="tv_gold_chart" style="height: 680px; width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Se├º├úo: Futuros de Ouro CME (GC1! - GC7!) e Gold Miners -->
  <div class="row g-3 px-2 px-md-3 mt-3">
    <div class="col-12 col-lg-6">
      <div class="card w-100 card_indices">
        <div class="card-header title-card">
          <span>Futuros de Ouro</span>
          <span class="media-percentage" id="media-futuros-ouro"></span>
        </div>
        <div class="card-body p-0">
          <div class="w-100">
            <table class="table mb-0">
              <tbody class="tbody_futuros_ouro"></tbody>
            </table>
            <!-- Gr├ífico de Curva de Futuros -->
            <div class="px-3 pb-3 mt-3 d-flex justify-content-center">
              <div style="position: relative; width: 550px; height: 250px; overflow: hidden;">
                <div class="text-center text-muted small fw-semibold mb-2">Curva de Futuros (Term Structure)</div>
                <canvas id="gc_futures_curve" style="width: 550px !important; height: 220px !important; display: block;"></canvas>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="col-12 col-lg-6">
      <div class="card w-100 card_indices">
        <div class="card-header title-card">
          <span>Gold Miners</span>
          <span class="media-percentage" id="media-gold-miners"></span>
        </div>
        <div class="card-body p-0">
          <div class="w-100">
            <table class="table mb-0">
              <tbody class="tbody_gold_miners"></tbody>
            </table>
            <!-- Gr├ífico TradingView GDX vs GOLD -->
            <div class="px-3 pb-3 mt-3 d-flex justify-content-center">
              <div style="width: 550px; height: 250px;">
                <div class="text-center text-muted small fw-semibold mb-2">GDX vs GOLD</div>
                <div id="tv_gold_miners_widget" style="width: 550px; height: 220px;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Se├º├úo: Compara├º├╡es e Raz├╡es -->
  <div class="row g-3 px-2 px-md-3 mt-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Ouro vs DXY</div>
        <div class="card-body p-0" style="height: 480px;">
          <div id="tv_compare_gold_dxy" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Ouro vs Bitcoin</div>
        <div class="card-body p-0" style="height: 480px;">
          <div id="tv_compare_gold_btc" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Razão Ouro / Miners (GDX)</div>
        <div class="card-body p-0" style="height: 480px;">
          <div id="tv_ratio_gold_miners" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Razão Ouro / Bitcoin</div>
        <div class="card-body p-0" style="height: 480px;">
          <div id="tv_ratio_gold_btc" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Se├º├úo: Indicadores T├⌐cnicos -->
  <div class="row g-3 px-2 px-md-3 mt-3">
    <div class="col-12 col-xl-3">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Análise Técnica: Ouro</div>
        <div class="card-body p-0" style="height: 460px;">
          <div id="tv_tech_gold" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-3">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Análise Técnica: DXY</div>
        <div class="card-body p-0" style="height: 460px;">
          <div id="tv_tech_dxy" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-3">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Análise Técnica: US10Y</div>
        <div class="card-body p-0" style="height: 460px;">
          <div id="tv_tech_us10y" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-3">
      <div class="card h-100 card_indices">
        <div class="card-header title-card py-1 px-2 fs-6 fw-bold">Análise Técnica: VIX</div>
        <div class="card-body p-0" style="height: 460px;">
          <div id="tv_tech_vix" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php
$content = ob_get_clean();
$scripts = ''
  . '<script src="https://s3.tradingview.com/tv.js"></script>'
  . '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>'
  . '<script src="/assets/js/boot.js?v=' . time() . '"></script>'
  . '<script src="/assets/dev/js.php?f=gold-dashboard.js&v=' . time() . '"></script>'
  . '<script src="/assets/js/mobile-menu.js?v=' . time() . '"></script>'
  . '<script>'
  . '  // Inicializar tooltips do Bootstrap'
  . '  document.addEventListener("DOMContentLoaded", function() {'
  . '    var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'));'
  . '    tooltipTriggerList.map(function(tooltipTriggerEl) {'
  . '      return new bootstrap.Tooltip(tooltipTriggerEl);'
  . '    });'
  . '  });'
  . '</script>';
include __DIR__ . '/../../layouts/app.php';
?>
