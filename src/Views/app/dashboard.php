<?php
$title = 'Dashboard - Terminal Operebem';
$csrf_token = $_SESSION['csrf_token'] ?? '';

// Verifica se o usuário tem media_card habilitado
$media_card_enabled = isset($user['media_card'])
    ? (bool) $user['media_card']
    : (isset($_SESSION['user_media_card']) ? (bool) $_SESSION['user_media_card'] : false);

// Verifica se o usuário tem advanced_snapshot habilitado (padrão: true)
$advanced_snapshot = isset($user['advanced_snapshot'])
    ? (bool) $user['advanced_snapshot']
    : (isset($_SESSION['user_advanced_snapshot']) ? (bool) $_SESSION['user_advanced_snapshot'] : true);

ob_start();
?>

<meta name="user-advanced-snapshot" content="<?= $advanced_snapshot ? '1' : '0' ?>">

<style>
/* Esconder copyright do TradingView */
.tradingview-widget-copyright { display: none !important; }

/* === LIGHT THEME === */
html.light .tradingview-widget-container#widget_long,
body.light .tradingview-widget-container#widget_long,
html.light .tradingview-widget-container#widget_long .tradingview-widget-container__widget,
body.light .tradingview-widget-container#widget_long .tradingview-widget-container__widget {
  background-color: #f4f4fa !important;
}

/* === DARK-BLUE THEME === */
html.dark-blue .tradingview-widget-container#widget_long,
body.dark-blue .tradingview-widget-container#widget_long,
html.dark-blue .tradingview-widget-container#widget_long .tradingview-widget-container__widget,
body.dark-blue .tradingview-widget-container#widget_long .tradingview-widget-container__widget {
  background-color: #000a22 !important;
}

/* === ALL-BLACK THEME === */
html.all-black .tradingview-widget-container#widget_long,
body.all-black .tradingview-widget-container#widget_long,
html.all-black .tradingview-widget-container#widget_long .tradingview-widget-container__widget,
body.all-black .tradingview-widget-container#widget_long .tradingview-widget-container__widget {
  background-color: #000 !important;
}

/* Ajustes para os cards nos diferentes temas */
html.light .card {
    background-color: #fff;
    border-color: rgba(0, 0, 0, 0.125);
}

html.dark-blue .card {
    background-color: #000a22;
    border-color: rgba(255, 255, 255, 0.125);
}

html.all-black .card {
    background-color: #000;
    border-color: rgba(255, 255, 255, 0.125);
}

/* Ajustes para o card-body do Fear & Greed */
html.light .card-body.bg-light {
    background-color: #f8f9fa !important;
}

html.dark-blue .card-body.bg-light {
    background-color: #001233 !important;
}

html.all-black .card-body.bg-light {
    background-color: #0a0a0a !important;
}

/* Ajustes para os títulos dos cards */
html.light .card-header.title-card {
    background-color: #f8f9fa;
    border-bottom-color: rgba(0, 0, 0, 0.125);
}

html.dark-blue .card-header.title-card {
    background-color: #001233;
    border-bottom-color: rgba(255, 255, 255, 0.125);
}

html.all-black .card-header.title-card {
    background-color: #0a0a0a;
    border-bottom-color: rgba(255, 255, 255, 0.125);
}

/* Estilo para a média dos cards (segue o título) */
.media-percentage {
    font-size: 1em; /* acompanha o font-size do header */
    padding: 2px 8px;
    border-radius: 8px;
    border: 1px solid rgba(0,0,0,0.1);
    background: transparent;
    position: absolute; /* à direita do título */
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
}

/* Desktop: posicionar média acima do horário (última coluna) */
@media (min-width: 768px) {
    .media-percentage {
        right: 60px;
    }
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

/* Headers dos cards de cotações: reduzir ~20% e deslocar levemente à direita */
.card_indices .card-header.title-card {
    position: relative;
    padding: 0.75rem 0.75rem 0.75rem 1.25rem; /* desloca o título para a direita */
    font-size: 1.2rem; /* 20% menor que 1.5rem */
}

/* Grid responsivo custom: forçar 3 colunas no range XXL padrão e 4 colunas somente >1700px */
@media (min-width: 1400px) and (max-width: 1699.98px) {
  .container-fluid .row > [class*="col-xxl-3"] {
    flex: 0 0 33.3333%;
    max-width: 33.3333%;
  }
}

@media (min-width: 1700px) {
  .container-fluid .row > [class*="col-xxl-3"] {
    flex: 0 0 25%;
    max-width: 25%;
  }
}

/* Padronização de fontes das tabelas de cotações */
.card_indices table td,
.card_indices table th {
    font-size: 12px !important;
}

/* (export excel) estilos do botão flutuante removidos: agora o acionamento fica no dropdown do usuário */

/* Ajustes para os widgets do TradingView */
.tradingview-widget-container {
    position: relative;
    width: 100%;
    height: 100%;
}

.tradingview-widget-container__widget {
    position: relative;
    width: 100%;
    height: 100%;
}

.tradingview-widget-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

/* Link "blue-text" dentro do widget nos temas escuros */
html.dark-blue .tradingview-widget-container#widget_long .blue-text,
body.dark-blue .tradingview-widget-container#widget_long .blue-text,
html.all-black .tradingview-widget-container#widget_long .blue-text {
  color: #f4f4fa !important;
}

/* Ajustes para os cards das corretoras nos diferentes temas */
html.light .partner-card {
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

html.dark-blue .partner-card {
    background: #001233;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

html.all-black .partner-card {
    background: #0a0a0a;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.partner-card {
    display: block;
    text-decoration: none;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
    max-width: 380px;
    margin: 0 auto;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.partner-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    border-color: var(--bs-primary);
    text-decoration: none;
}

.partner-card-content {
    display: flex;
    align-items: center;
    gap: 1.5rem;
}

.partner-logo {
    flex-shrink: 0;
}

.partner-logo img {
    width: 65px;
    height: 65px;
    object-fit: contain;
    border-radius: 10px;
}

.partner-info {
    text-align: left;
}

.partner-info .highlight {
    font-weight: 700;
    font-size: 1.1em;
    display: block;
    margin-bottom: 0.2rem;
}

.partner-title {
    font-size: 1.1em;
    font-weight: 600;
    line-height: 1.3;
}

/* Ajustes para o texto dos cards das corretoras */
html.light .partner-title {
    color: var(--bs-body-color);
}

html.dark-blue .partner-title,
html.all-black .partner-title {
    color: #fff !important;
}

/* Ajustes para o highlight dos cards */
html.light .partner-info .highlight {
    color: #2A54FF;
}

html.dark-blue .partner-info .highlight {
    color: #C02034;
}

html.all-black .partner-info .highlight {
    color: #BBF817;
}

/* Estilos para o US Market Barometer */
.barometer-square {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.barometer-square:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

html.light .barometer-text {
    color: inherit;
}

html.dark-blue .barometer-text,
html.all-black .barometer-text {
    color: #fff;
}

td.barometer-cell {
    padding: 1px !important;
}

/* Estilo para o select do barômetro adaptado aos temas */
.theme-select {
    background-color: transparent !important;
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
    height: auto;
    width: auto;
    border: none !important;
    outline: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 12px 10px;
}

html.light .theme-select {
    color: #000 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
}

html.dark-blue .theme-select,
html.all-black .theme-select {
    color: #fff !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
}

.theme-select:focus {
    box-shadow: none !important;
    border: none !important;
    outline: none !important;
}

/* Options do select adaptadas aos temas */
html.light .theme-select option {
    background-color: #ffffff;
    color: #000;
}

html.dark-blue .theme-select option {
    background-color: #001233;
    color: #fff;
}

html.all-black .theme-select option {
    background-color: #0a0a0a;
    color: #fff;
}

/* Texto dos quadradinhos do barometer */
html.light .barometer-text {
    color: #000;
}

html.dark-blue .barometer-text,
html.all-black .barometer-text {
    color: #000 !important;
}

/* Estilo para o ícone de ajuda (?) */
.help-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    font-size: 14px;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.2s ease;
}

html.light .help-icon {
    background-color: rgba(0, 0, 0, 0.1);
    color: #333;
}

html.light .help-icon:hover {
    background-color: rgba(0, 0, 0, 0.2);
}

html.dark-blue .help-icon {
    background-color: rgba(255, 255, 255, 0.2);
    color: #fff;
}

html.dark-blue .help-icon:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

html.all-black .help-icon {
    background-color: rgba(255, 255, 255, 0.2);
    color: #fff;
}

html.all-black .help-icon:hover {
    background-color: rgba(255, 255, 255, 0.3);
}

.last-update-text {
    font-size: 0.75rem;
    opacity: 0.8;
    white-space: nowrap;
}

html.light .last-update-text {
    color: #666;
}

html.dark-blue .last-update-text,
html.all-black .last-update-text {
    color: #ccc;
}

/* ---- Complementos de estilos para o dashboard antigo ---- */
@media (max-width: 575.98px) { /* shrink media badge and header on mobile */
  .card_indices .card-header.title-card { min-height: 48px; }
  .media-percentage { font-size: 0.82em; padding: 1px 6px; right: 8px; }
}

/* Ícone de câmera do snapshot */
.snapcam {
    transition: transform 0.2s, color 0.2s;
}

.snapcam:hover {
    transform: scale(1.2);
    filter: brightness(1.2);
}

html.light .snapcam {
    color: #0d6efd !important;
}

html.dark-blue .snapcam,
html.all-black .snapcam {
    color: #60a5fa !important;
}

/* Modal de snapshot */
#snapshotModal .modal-content {
    font-size: 0.95rem;
}

#snapshotModal .table {
    margin-bottom: 0;
}

/* Tooltip de snapshot avançada - LIGHT THEME */
.tooltip.snapshot-tip {
    opacity: 1 !important;
}

html.light .tooltip.snapshot-tip .tooltip-inner {
    max-width: 350px;
    padding: 0;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    text-align: left;
    border: 1px solid #dee2e6;
}

html.light .snap-head {
    font-size: 0.7rem;
    color: #6c757d;
    margin-bottom: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

html.light .snap-name {
    font-size: 1rem;
    color: #212529;
    font-weight: 700;
    margin-bottom: 14px;
}

html.light .snap-grid .lbl {
    font-size: 0.65rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
}

html.light .snap-grid .val {
    font-size: 0.95rem;
    color: #212529;
    font-weight: 700;
}

html.light .snap-grid .val.pos {
    color: #198754;
}

html.light .snap-grid .val.neg {
    color: #dc3545;
}

html.light .snap-grid .val.neu {
    color: #6c757d;
}

/* Tooltip de snapshot avançada - DARK-BLUE THEME */
html.dark-blue .tooltip.snapshot-tip .tooltip-inner {
    max-width: 350px;
    padding: 0;
    background: linear-gradient(135deg, #1a2332 0%, #0d1520 100%);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.6);
    text-align: left;
    border: 1px solid rgba(96, 165, 250, 0.2);
}

html.dark-blue .snap-head {
    font-size: 0.7rem;
    color: #94a3b8;
    margin-bottom: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

html.dark-blue .snap-name {
    font-size: 1rem;
    color: #f1f5f9;
    font-weight: 700;
    margin-bottom: 14px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

html.dark-blue .snap-grid .lbl {
    font-size: 0.65rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
}

html.dark-blue .snap-grid .val {
    font-size: 0.95rem;
    color: #e2e8f0;
    font-weight: 700;
}

html.dark-blue .snap-grid .val.pos {
    color: #34d399;
}

html.dark-blue .snap-grid .val.neg {
    color: #f87171;
}

html.dark-blue .snap-grid .val.neu {
    color: #94a3b8;
}

/* Tooltip de snapshot avançada - ALL-BLACK THEME */
html.all-black .tooltip.snapshot-tip .tooltip-inner {
    max-width: 350px;
    padding: 0;
    background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.8);
    text-align: left;
    border: 1px solid #333333;
}

html.all-black .snap-head {
    font-size: 0.7rem;
    color: #9ca3af;
    margin-bottom: 10px;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.8px;
}

html.all-black .snap-name {
    font-size: 1rem;
    color: #f9fafb;
    font-weight: 700;
    margin-bottom: 14px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

html.all-black .snap-grid .lbl {
    font-size: 0.65rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    font-weight: 600;
}

html.all-black .snap-grid .val {
    font-size: 0.95rem;
    color: #e5e7eb;
    font-weight: 700;
}

html.all-black .snap-grid .val.pos {
    color: #10b981;
}

html.all-black .snap-grid .val.neg {
    color: #ef4444;
}

html.all-black .snap-grid .val.neu {
    color: #9ca3af;
}

/* Estilos comuns a todos os temas */
.snap-tip {
    padding: 14px 18px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.snap-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.snap-grid > div {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

/* Espaçamento entre snapshot e preço */
.snapcam {
    margin-right: 8px;
}

.snap-simple {
    margin-right: 8px;
}

/* Ajustar posicionamento da média dos cards para a direita */
.media-percentage {
    right: 12px !important;
}

</style>

<!-- Export Excel agora é acionado via dropdown do usuário -->

<div class="container-fluid p-0">
    <div id="widget_long" class="tradingview-widget-container w-100 mb-4"></div>
</div>

<div class="container-fluid">
    <div class="row">
        <div id="dash-col-1" class="col-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3 mb-3">
            <div class="card w-100 card_indices mb-3 sw_futuros">
                <div class="card-header title-card">
                   Principais Índices Futuros
                   <?php if($media_card_enabled): ?>
                       <span class="media-percentage" id="media-futuros"></span>
                   <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_indices_futuros">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_americanos">
                <div class="card-header title-card">
                    Índices Norte Americanos
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-norte-americanos"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_indices_norte_americanos">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_europeus">
                <div class="card-header title-card">
                    Índices Europeus
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-europeus"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100 font-small">
                        <table class="table mb-0 font-small">
                            <tbody class="tbody_indices_europeus">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_asiaticos">
                <div class="card-header title-card">
                    Índices Asiáticos
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-asiaticos"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100 font-small">
                        <table class="table mb-0 font-small">
                            <tbody class="tbody_indices_asia">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_petroleiras">
                <div class="card-header title-card">
                    Petroleiras
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-petroleiras"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100 font-small">
                        <table class="table mb-0 font-small">
                            <tbody class="text-xl tbody_petroleiras">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Índice do Dólar (movido para a 1ª coluna para balancear) -->
            <div class="card mb-3 card_indices sw_dolar">
                <div class="card-header title-card">
                    Índice do Dólar
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-dolar"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_dolar"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="dash-col-2" class="col-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3 mb-3">
            <div class="card w-100 card_indices mb-3 sw_brasil">
                <div class="card-header title-card">
                    Índices Sul Americanos
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-sul-americanos"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_brasileiros">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_adrs">
                <div class="card-header title-card">
                    ADRs
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-adrs"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_adrs">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_cripto">
                <div class="card-header title-card">
                    Criptomoedas
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-cripto"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100 font-small">
                        <table class="table mb-0 font-small">
                            <tbody class="text-xl" id="table_criptomoedas">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_mineradoras">
                <div class="card-header title-card">
                    Mineradoras
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-mineradoras"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100 font-small">
                        <table class="table mb-0 font-small">
                            <tbody class="text-xl tbody_mineradoras">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Dólar Americano | Mundo (movido para 2ª coluna) -->
            <div class="card card_indices mb-3 sw_mundo">
                <div class="card-header title-card">
                    Dólar Americano | Mundo 
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-mundo"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_mundo">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Dólar Americano | Emergentes (movido para 2ª coluna) -->
            <div class="card w-100 mb-3 card_indices sw_emergentes">
                <div class="card-header title-card">
                    Dólar Americano | Emergentes
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-emergentes"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_emergentes">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div id="dash-col-3" class="col-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3 mb-3">
            <div class="card w-100 card_indices mb-3 sw_juros">
                <div class="card-header title-card">
                   Juros
                   <?php if($media_card_enabled): ?>
                       <span class="media-percentage" id="media-juros"></span>
                   <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_juros">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_big_tech">
                <div class="card-header title-card">
                   Magnificent 7
                   <?php if($media_card_enabled): ?>
                       <span class="media-percentage" id="media-big_tech"></span>
                   <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_big_tech">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_agricolas">
                <div class="card-header title-card">
                   Commodities - Agrícolas
                   <?php if($media_card_enabled): ?>
                       <span class="media-percentage" id="media-agricolas"></span>
                   <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_agricolas">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_energia">
                <div class="card-header title-card">
                   Commodities - Energia
                   <?php if($media_card_enabled): ?>
                       <span class="media-percentage" id="media-energia"></span>
                   <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_energia">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_metais">
                <div class="card-header title-card">
                   Commodities - Metais
                   <?php if($media_card_enabled): ?>
                       <span class="media-percentage" id="media-metais"></span>
                   <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_metais">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Volatilidade (ficará na 3ª coluna, antes do Barometer) -->
            <div class="card w-100 mb-3 card_indices sw_vola">
                <div class="card-header title-card">
                    Volatilidade
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-vola"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_vola">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Outros (abaixo de Volatilidade) -->
            <div class="card w-100 mb-3 card_indices sw_outros">
                <div class="card-header title-card">
                    Outros
                    <?php if($media_card_enabled): ?>
                        <span class="media-percentage" id="media-outros"></span>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="w-100">
                        <table class="table mb-0">
                            <tbody class="tbody_outros">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- US Market Barometer Card -->
            <div class="card w-100 card_indices mb-3 sw_usmb">
                <div class="card-header title-card d-flex justify-content-between align-items-center">
                    US Market Barometer
                    <select id="barometer-period" class="theme-select">
                        <option value="1d">1 Dia</option>
                        <option value="1m">1 Mês</option>
                        <option value="3m">3 Meses</option>
                        <option value="1y">1 Ano</option>
                        <option value="5y">5 Anos</option>
                        <option value="10y">10 Anos</option>
                    </select>
                </div>
                <div class="card-body p-0">
                    <div class="p-2">
                        <div class="d-flex justify-content-center">
                            <table class="w-auto">
                                <tbody id="barometer-grid">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Market Clock Widget -->
            <div class="card w-100 card_indices mb-3 sw_market_clock">
                <div class="card-header title-card">
                    Mercados Globais 24h
                </div>
                <div class="card-body">
                    <?php
                    $clockPath = __DIR__ . '/../../../widgets/market-clock.php';
                    if (is_file($clockPath)) {
                        include $clockPath;
                    } else {
                        echo '<div class="text-muted">Widget de Market Clock indisponível.</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="card w-100 card_indices mb-3 sw_econcal">
                <div class="card-header title-card d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <span>Calendário Econômico</span>
                    </div>
                    <div class="econcal-host-header-right d-flex align-items-center gap-2"></div>
                </div>
                <div class="card-body p-0">
                    <?php
                    $econPath = __DIR__ . '/../../../widgets/economic-calendar.php';
                    if (is_file($econPath)) {
                        include $econPath;
                    } else {
                        echo '<div class="text-muted">Widget de Calendário indisponível no servidor.</div>';
                    }
                    ?>
                </div>
            </div>

            
        </div>

        
    </div>
</div>

            

            

<!-- SheetJS Library for Excel Export -->
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

 

<div class="modal fade" id="dashboardFiltersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-filter me-2"></i>Filtrar cards</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="dashboard-filters-list" class="vstack gap-2"></div>
      </div>
      <div class="modal-footer">
        <button type="button" id="dashboard-filters-reset" class="btn btn-outline-secondary">Padrão</button>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
 </div>

 <!-- Modal de Filtros do Calendário Econômico -->
 <div class="modal fade" id="econcalFilterModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-dialog-scrollable modal-md">
     <div class="modal-content">
       <div class="modal-header">
         <h5 class="modal-title"><i class="fas fa-filter me-2"></i>Filtros do Calendário</h5>
         <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
       </div>
       <div class="modal-body" id="econcal-modal-body"></div>
     </div>
   </div>
 </div>

<!-- Modal de Snapshot Detalhado -->
<div class="modal fade" id="snapshotModal" tabindex="-1" aria-labelledby="snapshotModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="snapshotModalLabel">
          <i class="fas fa-camera me-2"></i>Snapshot
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body" id="snapshotModalBody">
        <!-- Conteúdo será preenchido via JavaScript -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

 

<?php
$content = ob_get_clean();
$isDashboard = true;
$scripts = '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>'
         . '<script src="/assets/js/snapshot.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/status-service.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/boot.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/tv-tape.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/barometer.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/dashboard-balance.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/console-silencer.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/dashboard-filters.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/economic-calendar.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/export-excel.js?v=' . time() . '"></script>'
         . '<script src="/assets/js/mobile-menu.js?v=' . time() . '"></script>';
include __DIR__ . '/../layouts/app.php';
?>
