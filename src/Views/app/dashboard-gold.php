<?php
$title = 'Dashboard Ouro - Terminal Operebem';
$csrf_token = $_SESSION['csrf_token'] ?? '';

ob_start();
?>

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
.card .card-body { overflow: hidden; }
.card .tradingview-widget-container { height: 100% !important; width: 100% !important; }
.card .tradingview-widget-container__widget { height: 100% !important; width: 100% !important; }
.card .tradingview-widget-container iframe { height: 100% !important; width: 100% !important; border: 0 !important; }

/* Estilos para tooltips de correlação */
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

/* Estilos para card de futuros */
#gold_futures_grid .table td {
  padding: 0.4rem 0.5rem;
  vertical-align: middle;
}

#gold_futures_grid canvas {
  width: 100% !important;
  height: auto !important;
}
</style>

<!-- Ticker Tape TradingView -->
<div class="container-fluid p-0">
  <div id="gold_ticker_tape" class="tradingview-widget-container w-100 mb-4"></div>
</div>

<div class="container-fluid mt-3">

  <!-- Seção: Ativos Principais -->
  <div class="row g-3 px-2 px-md-3 mb-3">
    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100">
        <div class="card-body p-3">
          <div class="text-uppercase small text-muted mb-1">
            Ouro
            <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Ativo principal: Preço do ouro à vista (XAU/USD). É o benchmark global para o mercado de ouro.">?</span>
          </div>
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
      <div class="card h-100">
        <div class="card-body p-3">
          <div class="text-uppercase small text-muted mb-1">
            DXY
            <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Correlação inversa: Quando o dólar fortalece (DXY sobe), o ouro tende a cair, pois fica mais caro para compradores estrangeiros.">?</span>
          </div>
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
      <div class="card h-100">
        <div class="card-body p-3">
          <div class="text-uppercase small text-muted mb-1">
            US10Y
            <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Correlação inversa: Juros altos (US10Y sobe) tornam títulos mais atrativos que ouro, que não paga juros.">?</span>
          </div>
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
      <div class="card h-100">
        <div class="card-body p-3">
          <div class="text-uppercase small text-muted mb-1">
            VIX
            <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Correlação positiva: VIX mede volatilidade/medo no mercado. Quando sobe, investidores buscam segurança no ouro.">?</span>
          </div>
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
      <div class="card h-100">
        <div class="card-body p-3">
          <div class="text-uppercase small text-muted mb-1">
            Gold Vol
            <span class="correlation-info" data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Volatilidade do ouro: GVZ mede a volatilidade esperada do preço do ouro, similar ao VIX para ações.">?</span>
          </div>
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

  <!-- Seção: Futuros de Ouro CME (GC1! - GC7!) -->
  <div class="row g-3 px-2 px-md-3 mt-1" id="gold_futures_grid"></div>

  <!-- Seção: Gráfico Principal do Ouro -->
  <div class="row g-3 px-2 px-md-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body p-2 p-md-3">
          <div id="tv_gold_chart" style="height: 680px; width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Seção: Comparações e Razões -->
  <div class="row g-3 px-2 px-md-3 mt-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 420px;">
          <div id="tv_compare_gold_dxy" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 420px;">
          <div id="tv_compare_gold_btc" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 420px;">
          <div id="tv_ratio_gold_miners" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 420px;">
          <div id="tv_ratio_gold_btc" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Seção: Indicadores Técnicos -->
  <div class="row g-3 px-2 px-md-3 mt-3">
    <div class="col-12 col-xl-3">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
          <div id="tv_tech_gold" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-3">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
          <div id="tv_tech_dxy" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-3">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
          <div id="tv_tech_us10y" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-xl-3">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
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
  . '<script src="/assets/js/gold-dashboard.js?v=' . time() . '"></script>'
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
include __DIR__ . '/../layouts/app.php';
?>
