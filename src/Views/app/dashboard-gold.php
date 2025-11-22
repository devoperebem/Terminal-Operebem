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

/* Loader overlay */
#gold_loader {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.45);
  z-index: 1055;
  display: none;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(2px);
}
#gold_loader.show { display: flex; }
#gold_loader .spinner {
  width: 56px; height: 56px;
  border: 4px solid rgba(255,255,255,.25);
  border-top-color: #0d6efd;
  border-radius: 50%;
  animation: goldspin .9s linear infinite;
}
@keyframes goldspin { to { transform: rotate(360deg); } }

/* Widgets ocupando 100% dos cards e sem scroll */
.card .card-body { overflow: hidden; }
.card .tradingview-widget-container { height: 100% !important; width: 100% !important; }
.card .tradingview-widget-container__widget { height: 100% !important; width: 100% !important; }
.card .tradingview-widget-container iframe { height: 100% !important; width: 100% !important; border: 0 !important; }
</style>

<!-- Ticker Tape TradingView -->
<div id="gold_loader"><div class="spinner"></div></div>
<div class="container-fluid p-0">
  <div id="gold_ticker_tape" class="tradingview-widget-container w-100 mb-4"></div>
</div>

<div class="container-fluid mt-3">

  <div class="row g-3 px-2 px-md-3 mb-3">
    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100">
        <div class="card-body p-3">
          <div class="text-uppercase small text-muted mb-1">Ouro</div>
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
          <div class="text-uppercase small text-muted mb-1">DXY</div>
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
          <div class="text-uppercase small text-muted mb-1">US10Y</div>
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
          <div class="text-uppercase small text-muted mb-1">VIX</div>
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
          <div class="text-uppercase small text-muted mb-1">Gold Vol</div>
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

  <div class="row g-3 px-2 px-md-3 mt-1" id="gold_futures_grid"></div>

  <div class="row g-3 px-2 px-md-3">
    <div class="col-12">
      <div class="card">
        <div class="card-body p-2 p-md-3">
          <div id="tv_gold_chart" style="height: 680px; width: 100%;"></div>
        </div>
      </div>
    </div>
  </div>

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

  <div class="row g-3 px-2 px-md-3 mt-3">
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
          <div id="tv_corr_gold_dxy" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
          <div id="tv_corr_gold_us10y" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
          <div id="tv_corr_gold_btc" style="height: 100%; width: 100%;"></div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="card h-100">
        <div class="card-body p-0" style="height: 400px;">
          <div id="tv_corr_gold_vix" style="height: 100%; width: 100%;"></div>
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
  . '<script src="/assets/js/mobile-menu.js?v=' . time() . '"></script>';
include __DIR__ . '/../layouts/app.php';
?>
