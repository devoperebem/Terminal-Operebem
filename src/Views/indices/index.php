<?php
ob_start();

$indices = $indices ?? null;
$error = $error ?? null;

// Preparar dados de sessão
$media_card_enabled = $_SESSION['user_media_card'] ?? false;
?>

<div class="container-fluid p-0 mb-3">
    <div id="widget_long" class="tradingview-widget-container w-100" data-symbols='[
      {"proName":"BMFBOVESPA:WIN1!","title":"WIN1!"},
      {"proName":"BMFBOVESPA:DOL1!","title":"DOL1!"},
      {"proName":"BINANCE:USDTBRL","title":"USDTBRL"},
      {"proName":"BMFBOVESPA:VALE3","title":"VALE3"},
      {"proName":"BMFBOVESPA:PETR4","title":"PETR4"}
    ]'></div>
    </div>

<style>
.index-card {
    transition: transform 0.2s, box-shadow 0.2s;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.index-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.dark-blue .index-card,
.all-black .index-card {
    background: #1f2937;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.chart-container {
    position: relative;
    height: 200px;
    margin: 1rem 0;
}

.index-value {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0.5rem 0;
}

.index-value.positive {
    color: #10b981;
}

.index-value.negative {
    color: #ef4444;
}

.dark-blue .index-value.positive,
.all-black .index-value.positive {
    color: #34d399;
}

.dark-blue .index-value.negative,
.all-black .index-value.negative {
    color: #f87171;
}

.component-item {
    padding: 0.75rem;
    border-left: 3px solid #667eea;
    margin-bottom: 0.75rem;
    background: rgba(102, 126, 234, 0.05);
    border-radius: 6px;
}

.dark-blue .component-item,
.all-black .component-item {
    background: rgba(102, 126, 234, 0.1);
}

.component-weight {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 600;
}

.dark-blue .component-weight,
.all-black .component-weight {
    color: #9ca3af;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.status-ok {
    background: #d1fae5;
    color: #065f46;
}

.status-partial {
    background: #fed7aa;
    color: #92400e;
}

.dark-blue .status-ok,
.all-black .status-ok {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
}

.dark-blue .status-partial,
.all-black .status-partial {
    background: rgba(251, 146, 60, 0.2);
    color: #fb923c;
}

.formula-box {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 1rem;
    font-family: 'Courier New', monospace;
    font-size: 0.9rem;
    overflow-x: auto;
}

.dark-blue .formula-box,
.all-black .formula-box {
    background: #1f2937;
    border-color: #374151;
    color: #e5e7eb;
}

.refresh-indicator {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Dropdown theming and help icon styling */
.index-card .dropdown-menu { border-radius: 10px; border: 1px solid var(--border-color); }
html.dark-blue .index-card .dropdown-menu,
html.all-black .index-card .dropdown-menu { background: rgba(255,255,255,0.08); color: #e5e7eb; }
html.dark-blue .index-card .dropdown-item,
html.all-black .index-card .dropdown-item { color: #e5e7eb; }
html.dark-blue .index-card .dropdown-item:hover,
html.all-black .index-card .dropdown-item:hover { background: rgba(255,255,255,0.12); color: #fff; }
.index-card .btn.btn-outline-secondary { color: var(--text-primary); border-color: var(--border-color); }
html.dark-blue .index-card .btn.btn-outline-secondary,
html.all-black .index-card .btn.btn-outline-secondary { color: #e5e7eb; border-color: rgba(255,255,255,0.35); }
.help-icon { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; border-radius:4px; font-size:13px; font-weight:700; }
html.light .help-icon { background-color: rgba(0,0,0,0.08); color:#333; }
html.dark-blue .help-icon, html.all-black .help-icon { background-color: rgba(255,255,255,0.18); color:#e5e7eb; }
/* Hide TradingView copyright mark */
.tradingview-widget-copyright { display: none !important; }
/* Equalize cards */
.row.g-4 > [class*='col-'] > .index-card { height: 100%; }
.index-card .card-body { display: flex; flex-direction: column; }
.index-card .ta-container { height: 320px; overflow: hidden; }
/* Equalize columns by flex */
.row.g-4 > [class*='col-'] { display: flex; }
.row.g-4 > [class*='col-'] > .index-card { flex: 1 1 auto; }
/* All-black accents for component items */
html.all-black .component-item { background: #0b0f1a; border-left-color: #1d4ed8; box-shadow: 0 6px 16px rgba(13,31,64,0.35); }
html.all-black .component-weight { color: #cbd5e1; }
/* TradingView TA widgets: fixed and no-scroll */
.tradingview-widget-container { position: relative; width: 100%; height: 100%; overflow: hidden; }
.tradingview-widget-container__widget { position: relative; width: 100%; height: 100%; overflow: hidden; }
.tradingview-widget-container iframe { position: absolute; top:0; left:0; width: 100%; height: 100%; border: none; }
</style>

<div class="container mt-4">
    <!-- Top Bar -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">OB Índices</h1>
        </div>
        <div class="text-end"></div>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($indices): ?>
        <!-- Timestamp -->
        <div class="text-muted small mb-3 text-end">
            <i class="fas fa-clock me-1"></i>
            <span id="lastUpdate">Última atualização: <?= htmlspecialchars($indices['datetime'] ?? date('Y-m-d H:i:s'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        

            <!-- Índices Grid -->
            <div class="row g-4">
                <?php $reasons = [
                    'IFPV' => [
                        '961741' => 'Minério de ferro (driver da VALE)',
                        '13059'  => 'ADR da Vale (proxy internacional)',
                        '27407'  => 'Setor mineradoras (FTSE 350 Mining)',
                        '945574' => 'SZSE Mining Index (China)',
                        '509'    => 'Humor Brasil (EWZ)'
                    ],
                    'IFPP' => [
                        '8833'      => 'Preço do petróleo (Brent)',
                        '8028'      => 'ADR Petrobras (proxy internacional)',
                        'MAJORS'    => 'Maiores petroleiras globais (Aramco, Exxon, Chevron, Shell, PetroChina)',
                        '509'       => 'Humor Brasil (EWZ)',
                        'IDFE2_INV' => 'IDFE2 invertido (dólar fraco é favorável)'
                    ],
                    'IDFE' => [
                        '1224074'    => 'Força global do dólar (DXY)',
                        'EMERGENTES' => 'Cesta de moedas emergentes'
                    ],
                    'IDFE2' => [
                        'USD|BRL'     => 'USDBRL + USDTBRL (cesta 25%)',
                        'EMERG_CORR'  => 'Cesta Emergentes Correlacionada'
                    ],
                ]; ?>
                <!-- IFPV - Índice de Feeling para VALE3 -->
                <?php if (isset($indices['IFPV'])): 
                    $ifpv = $indices['IFPV'];
                    $valueClass = ($ifpv['value'] ?? 0) >= 0 ? 'positive' : 'negative';
                ?>
                <div class="col-12 col-lg-6">
                    <div class="card index-card h-100" data-index="IFPV">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-mountain me-2" style="color: #3b82f6;"></i>
                                    IFPV
                                </h5>
                                <small class="text-muted d-inline-flex align-items-center">
                                    <?= htmlspecialchars($ifpv['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <a class="btn btn-sm btn-outline-secondary help-icon ms-2" data-bs-toggle="tooltip" title="IFPV acima de 0 indica vento favorável para VALE3 intraday; abaixo de 0, desfavorável.">?</a>
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="refreshDropdownIFPV" data-bs-toggle="dropdown" aria-expanded="false">
                                    Atualização: 1min
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="refreshDropdownIFPV">
                                    <li><button class="dropdown-item active" data-interval="60000">1 min</button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="30000" title="Disponível no Plano Profissional">30 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="5000" title="Disponível no Plano Profissional">5 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="1000" title="Disponível no Plano Profissional">1 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="index-value <?= $valueClass ?>">
                                    <?php 
                                        $ifpvValue = $ifpv['value'] ?? 0;
                                        $ifpvSign = $ifpvValue >= 0 ? '+' : '';
                                        echo $ifpvSign . number_format($ifpvValue, 2, ',', '.') . '%';
                                    ?>
                                </div>
                                <small class="text-muted">Peso total: <?php echo number_format(($ifpv['total_weight'] ?? 0) * 100, 0); ?>%</small>
                                <?php if (!empty($ifpv['spot']['price'])): ?>
                                <div class="small mt-1">
                                    <span class="text-muted">VALE3: R$ <?= number_format((float)$ifpv['spot']['price'], 2, ',', '.') ?></span>
                                    <?php if (isset($ifpv['spot']['pcp'])): $p = (float)$ifpv['spot']['pcp']; $cls = $p>=0?'text-success':'text-danger'; $sign = $p>=0?'+':''; ?>
                                    <span class="ms-2 <?= $cls ?>">Δ <?= $sign ?><?= number_format($p, 2, ',', '.') ?>%</span>
                                    <?php endif; ?>
                                    <?php if (!empty($ifpv['spot']['updated_at'])): ?>
                                    <?php $__t = (string)($ifpv['spot']['updated_at'] ?? ''); $____hhmm = strlen($__t) >= 16 ? substr($__t, 11, 5) : $__t; ?>
                                    <span class="text-muted ms-2">(<?= htmlspecialchars($____hhmm, ENT_QUOTES, 'UTF-8') ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            
                            <h6 class="mb-3 mt-3">Componentes:</h6>
                            <?php if (isset($ifpv['components']) && !empty($ifpv['components'])): ?>
                                <?php $indexKey = 'IFPV'; foreach ($ifpv['components'] as $key => $comp): ?>
                                    <div class="component-item" data-symbol="<?= htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($comp['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
                                                <?php $tip = $reasons[$indexKey][$key] ?? 'Compõe o índice'; ?>
                                                <a class="btn btn-sm btn-outline-secondary help-icon ms-2" data-bs-toggle="tooltip" title="<?= htmlspecialchars($tip, ENT_QUOTES, 'UTF-8') ?>">?</a>
                                                <span class="component-weight ms-2"><?php echo '(' . number_format(($comp['weight'] ?? 0) * 100, 0) . '%)'; ?></span>
                                            </div>
                                            <div class="text-end">
                                                <div class="<?= ($comp['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>" data-field="pcp">
                                                    <?= ($comp['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['pcp'] ?? 0, 2, ',', '.') ?>%
                                                </div>
                                                <small class="text-muted" data-field="contribution">Contrib: <?= ($comp['contribution'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['contribution'] ?? 0, 2, ',', '.') ?>%</small>
                                                <?php if (!empty($comp['updated_at'])): ?>
                                                <?php $__t = (string)($comp['updated_at'] ?? ''); $____hhmmss = strlen($__t) >= 19 ? substr($__t, 11, 8) : $__t; ?>
                                                <div class="small text-muted mt-1" data-field="updated_at">Atual: <?= htmlspecialchars($____hhmmss, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php if (!empty($comp['subcomponents'])): $subId = 'subIFPV' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)($key ?? '')); ?>
                                    <button class="btn btn-sm btn-outline-secondary mt-2" data-bs-toggle="collapse" data-bs-target="#<?= $subId ?>" aria-expanded="false">Ver composição</button>
                                    <div class="collapse mt-2" id="<?= $subId ?>">
                                        <?php foreach ($comp['subcomponents'] as $sub): ?>
                                        <div class="d-flex justify-content-between small py-1 border-top">
                                            <div>
                                                <span><?= htmlspecialchars($sub['name'] ?? ($sub['id'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="text-muted ms-2"><?= number_format(($sub['weight_share'] ?? 0) * 100, 2) ?>%</span>
                                            </div>
                                            <div class="text-end <?= ($sub['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= ($sub['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($sub['pcp'] ?? 0, 2, ',', '.') ?>%
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Nenhum componente disponível no momento.</p>
                            <?php endif; ?>

                            <div class="mt-4">
                                <small class="text-muted d-block mb-2">Fórmula:</small>
                                <div class="formula-box">
                                    IFPV = (Minério×35%) + (VALE ADR×30%) + (FTSE Mining×10%) + (SZSE Mining×10%) + (EWZ×15%)
                                </div>
                            </div>
                            <div class="mt-3 ta-container" style="height: 320px;">
                                <div class="tradingview-widget-container" style="height: 100%; width: 100%">
                                    <div class="tradingview-widget-container__widget" style="height: 100%; width: 100%"></div>
                                    <script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>" type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js" async>
                                    {
                                      "interval": "15m",
                                      "width": "100%",
                                      "height": 320,
                                      "isTransparent": true,
                                      "symbol": "BMFBOVESPA:VALE3",
                                      "showIntervalTabs": true,
                                      "displayMode": "single",
                                      "locale": "br",
                                      "colorTheme": "dark"
                                    }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- IFPP - Índice de Feeling para PETR4 -->
                <?php if (isset($indices['IFPP'])): 
                    $ifpp = $indices['IFPP'];
                    $valueClass = ($ifpp['value'] ?? 0) >= 0 ? 'positive' : 'negative';
                ?>
                <div class="col-12 col-lg-6">
                    <div class="card index-card h-100" data-index="IFPP">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-oil-can me-2" style="color: #f59e0b;"></i>
                                    IFPP
                                </h5>
                                <small class="text-muted d-inline-flex align-items-center">
                                    <?= htmlspecialchars($ifpp['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <a class="btn btn-sm btn-outline-secondary help-icon ms-2" data-bs-toggle="tooltip" title="IFPP acima de 0 sugere vento favorável para PETR4 intraday; abaixo de 0, desfavorável.">?</a>
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="refreshDropdownIFPP" data-bs-toggle="dropdown" aria-expanded="false">
                                    Atualização: 1min
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="refreshDropdownIFPP">
                                    <li><button class="dropdown-item active" data-interval="60000">1 min</button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="30000" title="Disponível no Plano Profissional">30 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="5000" title="Disponível no Plano Profissional">5 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="1000" title="Disponível no Plano Profissional">1 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="index-value <?= $valueClass ?>">
                                    <?php 
                                        $ifppValue = $ifpp['value'] ?? 0;
                                        $ifppSign = $ifppValue >= 0 ? '+' : '';
                                        echo $ifppSign . number_format($ifppValue, 2, ',', '.') . '%';
                                    ?>
                                </div>
                                <small class="text-muted">Peso total: <?php echo number_format(($ifpp['total_weight'] ?? 0) * 100, 0); ?>%</small>
                                <?php if (!empty($ifpp['spot']['price'])): ?>
                                <div class="small mt-1">
                                    <span class="text-muted">PETR4: R$ <?= number_format((float)$ifpp['spot']['price'], 2, ',', '.') ?></span>
                                    <?php if (isset($ifpp['spot']['pcp'])): $p = (float)$ifpp['spot']['pcp']; $cls = $p>=0?'text-success':'text-danger'; $sign = $p>=0?'+':''; ?>
                                    <span class="ms-2 <?= $cls ?>">Δ <?= $sign ?><?= number_format($p, 2, ',', '.') ?>%</span>
                                    <?php endif; ?>
                                    <?php if (!empty($ifpp['spot']['updated_at'])): ?>
                                    <?php $__t = (string)($ifpp['spot']['updated_at'] ?? ''); $____hhmm = strlen($__t) >= 16 ? substr($__t, 11, 5) : $__t; ?>
                                    <span class="text-muted ms-2">(<?= htmlspecialchars($____hhmm, ENT_QUOTES, 'UTF-8') ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>

                            <h6 class="mb-3 mt-3">Componentes:</h6>
                            <?php if (isset($ifpp['components']) && !empty($ifpp['components'])): ?>
                                <?php $indexKey = 'IFPP'; foreach ($ifpp['components'] as $symbol => $comp): ?>
                                    <div class="component-item" data-symbol="<?= htmlspecialchars((string)$symbol, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($comp['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
                                                <?php $tip = $reasons[$indexKey][$symbol] ?? 'Compõe o índice'; ?>
                                                <a class="btn btn-sm btn-outline-secondary help-icon ms-2" data-bs-toggle="tooltip" title="<?= htmlspecialchars($tip, ENT_QUOTES, 'UTF-8') ?>">?</a>
                                                <span class="component-weight ms-2"><?= number_format(($comp['weight'] ?? 0) * 100, 2) ?>%</span>
                                            </div>
                                            <div class="text-end">
                                                <div class="<?= ($comp['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>" data-field="pcp">
                                                    <?= ($comp['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['pcp'] ?? 0, 2, ',', '.') ?>%
                                                </div>
                                                <small class="text-muted" data-field="contribution">Contrib: <?= ($comp['contribution'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['contribution'] ?? 0, 2, ',', '.') ?>%</small>
                                                <?php if (!empty($comp['updated_at'])): ?>
                                                <?php $__t = (string)($comp['updated_at'] ?? ''); $____hhmmss = strlen($__t) >= 19 ? substr($__t, 11, 8) : $__t; ?>
                                                <div class="small text-muted mt-1" data-field="updated_at">Atual: <?= htmlspecialchars($____hhmmss, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($comp['subcomponents'])): $subId = 'subIFPP' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$symbol); ?>
                                        <button class="btn btn-sm btn-outline-secondary mt-2" data-bs-toggle="collapse" data-bs-target="#<?= $subId ?>" aria-expanded="false">Ver composição</button>
                                        <div class="collapse mt-2" id="<?= $subId ?>">
                                            <?php foreach ($comp['subcomponents'] as $sub): ?>
                                            <div class="d-flex justify-content-between small py-1 border-top">
                                                <div>
                                                    <span><?= htmlspecialchars($sub['name'] ?? ($sub['id'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="text-muted ms-2"><?= number_format(($sub['weight_share'] ?? 0) * 100, 2) ?>%</span>
                                                </div>
                                                <div class="text-end <?= ($sub['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= ($sub['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($sub['pcp'] ?? 0, 2, ',', '.') ?>%
                                                    <?php if (!empty($sub['updated_at'])): ?>
                                                    <?php $__t = (string)($sub['updated_at'] ?? ''); $____hhmmss = strlen($__t) >= 19 ? substr($__t, 11, 8) : $__t; ?>
                                                    <div class="small text-muted">Atual: <?= htmlspecialchars($____hhmmss, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Nenhum componente disponível no momento.</p>
                            <?php endif; ?>

                            <div class="mt-4">
                                <small class="text-muted d-block mb-2">Fórmula:</small>
                                <div class="formula-box">
                                    IFPP = (Brent×35%) + (PBR ADR×30%) + (Majors×15%) + (EWZ×10%) + (IDFE2 invertido×10%)
                                </div>
                            </div>
                            <div class="mt-3 ta-container" style="height: 320px;">
                                <div class="tradingview-widget-container" style="height: 100%; width: 100%">
                                    <div class="tradingview-widget-container__widget" style="height: 100%; width: 100%"></div>
                                    <script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>" type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js" async>
                                    {
                                      "interval": "15m",
                                      "width": "100%",
                                      "height": 320,
                                      "isTransparent": true,
                                      "symbol": "BMFBOVESPA:PETR4",
                                      "showIntervalTabs": true,
                                      "displayMode": "single",
                                      "locale": "br",
                                      "colorTheme": "dark"
                                    }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- IDFE - Índice do Dólar Frente Emergentes -->
                <?php if (isset($indices['IDFE'])): 
                    $idfe = $indices['IDFE'];
                    $valueClass = ($idfe['value'] ?? 0) >= 0 ? 'positive' : 'negative';
                ?>
                <div class="col-12 col-lg-6">
                    <div class="card index-card h-100" data-index="IDFE">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-globe-americas me-2" style="color: #0ea5e9;"></i>
                                    IDFE
                                </h5>
                                <small class="text-muted d-inline-flex align-items-center">
                                    <?= htmlspecialchars($idfe['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <a class="btn btn-sm btn-outline-secondary help-icon ms-2" data-bs-toggle="tooltip" title="IDFE positivo indica dólar fortalecendo frente a emergentes; negativo indica enfraquecimento.">?</a>
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="refreshDropdownIDFE" data-bs-toggle="dropdown" aria-expanded="false">
                                    Atualização: 1min
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="refreshDropdownIDFE">
                                    <li><button class="dropdown-item active" data-interval="60000">1 min</button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="30000" title="Disponível no Plano Profissional">30 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="5000" title="Disponível no Plano Profissional">5 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="1000" title="Disponível no Plano Profissional">1 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="index-value <?= $valueClass ?>">
                                    <?php 
                                        $idfeValue = $idfe['value'] ?? 0;
                                        $idfeSign = $idfeValue >= 0 ? '+' : '';
                                        echo $idfeSign . number_format($idfeValue, 2, ',', '.') . '%';
                                    ?>
                                </div>
                                <small class="text-muted">Peso total: <?php echo number_format(($idfe['total_weight'] ?? 0) * 100, 0); ?>%</small>
                            </div>

                            <h6 class="mb-3 mt-3">Componentes:</h6>
                            <?php if (isset($idfe['components']) && !empty($idfe['components'])): ?>
                                <?php $indexKey = 'IDFE'; foreach ($idfe['components'] as $symbol => $comp): ?>
                                    <div class="component-item" data-symbol="<?= htmlspecialchars((string)$symbol, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($comp['name'] ?? $symbol, ENT_QUOTES, 'UTF-8') ?></strong>
                                                <?php $tip = $reasons[$indexKey][$symbol] ?? 'Compõe o índice'; ?>
                                                <i class="fas fa-question-circle ms-2 text-muted" data-bs-toggle="tooltip" title="<?= htmlspecialchars($tip, ENT_QUOTES, 'UTF-8') ?>"></i>
                                                <span class="component-weight ms-2"><?= number_format(($comp['weight'] ?? 0) * 100, 0) ?>%</span>
                                                <?php if (isset($comp['count'])): ?>
                                                    <br><small class="text-muted"><?= $comp['count'] ?> moedas</small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-end">
                                                <div class="<?= ($comp['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>" data-field="pcp">
                                                    <?= ($comp['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['pcp'] ?? 0, 2, ',', '.') ?>%
                                                </div>
                                                <small class="text-muted" data-field="contribution">Contrib: <?= ($comp['contribution'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['contribution'] ?? 0, 2, ',', '.') ?>%</small>
                                                <?php if (!empty($comp['updated_at'])): ?>
                                                <?php $__t = (string)($comp['updated_at'] ?? ''); $____hhmmss = strlen($__t) >= 19 ? substr($__t, 11, 8) : $__t; ?>
                                                <div class="small text-muted mt-1" data-field="updated_at">Atual: <?= htmlspecialchars($____hhmmss, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($comp['subcomponents'])): $subId = 'subIDFE' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$symbol); ?>
                                        <button class="btn btn-sm btn-outline-secondary mt-2" data-bs-toggle="collapse" data-bs-target="#<?= $subId ?>" aria-expanded="false">Ver composição</button>
                                        <div class="collapse mt-2" id="<?= $subId ?>">
                                            <?php foreach ($comp['subcomponents'] as $sub): ?>
                                            <div class="d-flex justify-content-between small py-1 border-top">
                                                <div>
                                                    <span><?= htmlspecialchars($sub['name'] ?? ($sub['id'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="text-muted ms-2"><?= number_format(($sub['weight_share'] ?? 0) * 100, 2) ?>%</span>
                                                </div>
                                                <div class="text-end <?= ($sub['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= ($sub['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($sub['pcp'] ?? 0, 2, ',', '.') ?>%
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Nenhum componente disponível no momento.</p>
                            <?php endif; ?>

                            <div class="mt-4">
                                <small class="text-muted d-block mb-2">Fórmula:</small>
                                <div class="formula-box">
                                    IDFE = (DXY×5%) + (Média 9 Emergentes×95%)
                                </div>
                            </div>
                            <div class="mt-3 ta-container" style="height: 320px;">
                                <div class="tradingview-widget-container" style="height: 100%; width: 100%">
                                    <div class="tradingview-widget-container__widget" style="height: 100%; width: 100%"></div>
                                    <script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>" type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js" async>
                                    {
                                      "interval": "15m",
                                      "width": "100%",
                                      "height": 320,
                                      "isTransparent": true,
                                      "symbol": "TVC:DXY",
                                      "showIntervalTabs": true,
                                      "displayMode": "single",
                                      "locale": "br",
                                      "colorTheme": "dark"
                                    }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- IDFE2 - Índice do Dólar Frente Emergentes (ênfase Brasil) -->
                <?php if (isset($indices['IDFE2'])): 
                    $idfe2 = $indices['IDFE2'];
                    $valueClass = ($idfe2['value'] ?? 0) >= 0 ? 'positive' : 'negative';
                ?>
                <div class="col-12 col-lg-6">
                    <div class="card index-card h-100" data-index="IDFE2">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="mb-0">
                                    <i class="fas fa-flag me-2" style="color: #6366f1;"></i>
                                    IDFE2
                                </h5>
                                <small class="text-muted d-inline-flex align-items-center">
                                    <?= htmlspecialchars($idfe2['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                    <a class="btn btn-sm btn-outline-secondary help-icon ms-2" data-bs-toggle="tooltip" title="IDFE2 positivo indica dólar forte com ênfase no BRL; negativo indica perda de força.">?</a>
                                </small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="refreshDropdownIDFE2" data-bs-toggle="dropdown" aria-expanded="false">
                                    Atualização: 1min
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="refreshDropdownIDFE2">
                                    <li><button class="dropdown-item active" data-interval="60000">1 min</button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="30000" title="Disponível no Plano Profissional">30 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="5000" title="Disponível no Plano Profissional">5 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                    <li><button class="dropdown-item disabled" data-pro="1" data-interval="1000" title="Disponível no Plano Profissional">1 seg <span class="badge bg-warning text-dark ms-1">Pro</span></button></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="index-value <?= $valueClass ?>">
                                    <?php 
                                        $idfe2Value = $idfe2['value'] ?? 0;
                                        $idfe2Sign = $idfe2Value >= 0 ? '+' : '';
                                        echo $idfe2Sign . number_format($idfe2Value, 2, ',', '.') . '%';
                                    ?>
                                </div>
                                <small class="text-muted">Peso total: <?php echo number_format(($idfe2['total_weight'] ?? 0) * 100, 0); ?>%</small>
                            </div>

                            <h6 class="mb-3 mt-3">Componentes:</h6>
                            <?php if (isset($idfe2['components']) && !empty($idfe2['components'])): ?>
                                <?php $indexKey = 'IDFE2'; foreach ($idfe2['components'] as $symbol => $comp): ?>
                                    <div class="component-item" data-symbol="<?= htmlspecialchars((string)$symbol, ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= htmlspecialchars($comp['name'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
                                                <?php $tip = $reasons[$indexKey][$symbol] ?? 'Compõe o índice'; ?>
                                                <i class="fas fa-question-circle ms-2 text-muted" data-bs-toggle="tooltip" title="<?= htmlspecialchars($tip, ENT_QUOTES, 'UTF-8') ?>"></i>
                                                <span class="component-weight ms-2"><?= number_format(($comp['weight'] ?? 0) * 100, 2) ?>%</span>
                                            </div>
                                            <div class="text-end">
                                                <div class="<?= ($comp['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>" data-field="pcp">
                                                    <?= ($comp['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['pcp'] ?? 0, 2, ',', '.') ?>%
                                                </div>
                                                <small class="text-muted" data-field="contribution">Contrib: <?= ($comp['contribution'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($comp['contribution'] ?? 0, 2, ',', '.') ?>%</small>
                                                <?php if (!empty($comp['updated_at'])): ?>
                                                <?php $__t = (string)($comp['updated_at'] ?? ''); $____hhmmss = strlen($__t) >= 19 ? substr($__t, 11, 8) : $__t; ?>
                                                <div class="small text-muted mt-1" data-field="updated_at">Atual: <?= htmlspecialchars($____hhmmss, ENT_QUOTES, 'UTF-8') ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($comp['subcomponents'])): $subId = 'subIDFE2' . preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)$symbol); ?>
                                        <button class="btn btn-sm btn-outline-secondary mt-2" data-bs-toggle="collapse" data-bs-target="#<?= $subId ?>" aria-expanded="false">Ver composição</button>
                                        <div class="collapse mt-2" id="<?= $subId ?>">
                                            <?php foreach ($comp['subcomponents'] as $sub): ?>
                                            <div class="d-flex justify-content-between small py-1 border-top">
                                                <div>
                                                    <span><?= htmlspecialchars($sub['name'] ?? ($sub['id'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?></span>
                                                    <span class="text-muted ms-2"><?= number_format(($sub['weight_share'] ?? 0) * 100, 2) ?>%</span>
                                                </div>
                                                <div class="text-end <?= ($sub['pcp'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <?= ($sub['pcp'] ?? 0) >= 0 ? '+' : '' ?><?= number_format($sub['pcp'] ?? 0, 2, ',', '.') ?>%
                                                    <?php if (!empty($sub['updated_at'])): ?>
                                                    <?php $__t = (string)($sub['updated_at'] ?? ''); $____hhmmss = strlen($__t) >= 19 ? substr($__t, 11, 8) : $__t; ?>
                                                    <div class="small text-muted">Atual: <?= htmlspecialchars($____hhmmss, ENT_QUOTES, 'UTF-8') ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Nenhum componente disponível no momento.</p>
                            <?php endif; ?>

                            <div class="mt-4">
                                <small class="text-muted d-block mb-2">Fórmula:</small>
                                <div class="formula-box">
                                    IDFE2 = (USD|BRL×25%) + (Cesta Emergentes Correlacionada×75%)
                                </div>
                            </div>
                            <div class="mt-3 ta-container" style="height: 320px;">
                                <div class="tradingview-widget-container" style="height: 100%; width: 100%">
                                    <div class="tradingview-widget-container__widget" style="height: 100%; width: 100%"></div>
                                    <script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>" type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-technical-analysis.js" async>
                                    {
                                      "interval": "15m",
                                      "width": "100%",
                                      "height": 320,
                                      "isTransparent": true,
                                      "symbol": "BINANCE:USDTBRL",
                                      "showIntervalTabs": true,
                                      "displayMode": "single",
                                      "locale": "br",
                                      "colorTheme": "dark"
                                    }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script nonce="<?= htmlspecialchars($_SERVER['CSP_NONCE'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    if (window.bootstrap) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    const RATE_LIMIT_WINDOW = 20000; // 20s
    const MAX_REQUESTS = 2;
    let requestTimestamps = [];
    const AUTO_REFRESH_INTERVAL = 60000; // 1min

    function canMakeRequest() {
        const now = Date.now();
        requestTimestamps = requestTimestamps.filter(ts => now - ts < RATE_LIMIT_WINDOW);
        if (requestTimestamps.length >= MAX_REQUESTS) return false;
        requestTimestamps.push(now);
        return true;
    }

    async function refreshIndices() {
        if (!canMakeRequest()) return;
        try {
            const badge = document.getElementById('autoRefreshBadge');
            if (badge) badge.innerHTML = '<i class="fas fa-sync-alt fa-spin me-1"></i> Atualizando...';

            const res = await fetch('/api/indices?ts=' + Date.now(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const payload = await res.json();
            if (payload.success && payload.data) {
                updateIndicesDisplay(payload.data);
                updateTimestamp(payload.data.datetime);
            }
            if (badge) badge.innerHTML = '<i class="fas fa-sync-alt me-1"></i> Auto 1min';
        } catch (e) {
            console.error('[OB INDICES] refresh error', e);
        }
    }

    function updateIndicesDisplay(indices) {
        const fmtPct = (num) => {
            const n = Number(num || 0);
            const sign = n >= 0 ? '+' : '';
            return sign + n.toFixed(2).replace('.', ',') + '%';
        };

        ['IFPV', 'IFPP', 'IDFE', 'IDFE2'].forEach(indexName => {
            const idx = indices[indexName];
            if (!idx) return;
            const value = Number(idx.value || 0);
            const card = document.querySelector(`[data-index="${indexName}"]`);
            if (!card) return;
            const valueEl = card.querySelector('.index-value');
            if (valueEl) {
                const cls = value >= 0 ? 'positive' : 'negative';
                const sign = value >= 0 ? '+' : '';
                valueEl.className = `index-value ${cls}`;
                valueEl.textContent = `${sign}${value.toFixed(2).replace('.', ',')}%`;
            }

            const comps = (idx.components || {});
            card.querySelectorAll('.component-item').forEach(item => {
                const sym = item.getAttribute('data-symbol');
                if (!sym || !comps[sym]) return;
                const c = comps[sym];
                const pcpEl = item.querySelector('[data-field="pcp"]');
                if (pcpEl) {
                    const val = Number(c.pcp || 0);
                    pcpEl.classList.toggle('text-success', val >= 0);
                    pcpEl.classList.toggle('text-danger', val < 0);
                    pcpEl.textContent = fmtPct(val);
                }
                const contribEl = item.querySelector('[data-field="contribution"]');
                if (contribEl) {
                    const val = Number(c.contribution || 0);
                    const sign = val >= 0 ? '+' : '';
                    contribEl.textContent = 'Contrib: ' + sign + val.toFixed(2).replace('.', ',') + '%';
                }
                const upEl = item.querySelector('[data-field="updated_at"]');
                if (upEl) {
                    const s = String(c.updated_at || '');
                    const m = s.match(/\d{2}:\d{2}:\d{2}/);
                    upEl.textContent = 'Atual: ' + (m ? m[0] : s);
                }
            });
        });
    }

    function updateTimestamp(datetime) {
        const el = document.getElementById('lastUpdate');
        if (el && datetime) el.textContent = `Última atualização: ${datetime}`;
    }

    // Dropdowns (somente dos cards): manter somente 1min ativo; outros mostram aviso Pro
    document.querySelectorAll('.index-card .dropdown-menu .dropdown-item').forEach(btn => {
        btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            const isPro = btn.getAttribute('data-pro') === '1';
            if (isPro || btn.classList.contains('disabled')) {
                alert('Disponível apenas no Plano Profissional.');
                return;
            }
            // 1 min selecionado (padrão)
            const menu = btn.closest('.dropdown-menu');
            menu.querySelectorAll('.dropdown-item').forEach(it => it.classList.remove('active'));
            btn.classList.add('active');
            const trigger = menu.previousElementSibling;
            if (trigger) trigger.innerHTML = 'Atualização: 1min';
        });
    });

    setInterval(refreshIndices, AUTO_REFRESH_INTERVAL);
});
</script>

<?php
$content = ob_get_clean();
$scripts = '<script src="/assets/js/tv-tape.js?v=' . time() . '"></script>';
include __DIR__ . '/../layouts/app.php';
?>
