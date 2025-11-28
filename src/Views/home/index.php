<?php
ob_start();
// $csrf_token é fornecido pelo BaseController->view(); não regenerar aqui para evitar inconsistências de CSRF durante o fluxo multi-step
?>

<style>
/* Esconder copyright do TradingView */
.tradingview-widget-copyright { display: none !important; }

/* Hero stats responsivos aos temas */
.hero-stat-card { transition: all 0.3s ease; }
.hero-stat-icon { transition: color 0.3s ease; }
.hero-stat-number { transition: color 0.3s ease; }
.hero-stat-label { transition: color 0.3s ease; }

/* Light theme (padrão já está ok) */
html.light .hero-stat-card,
html:not(.dark-blue):not(.all-black) .hero-stat-card {
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(0, 0, 0, 0.08);
}

/* Dark Blue theme */
html.dark-blue .hero-stat-card {
    background: rgba(13, 110, 253, 0.1);
    border: 1px solid rgba(13, 132, 255, 0.3);
}
html.dark-blue .hero-stat-icon { color: #0d84ff; }
html.dark-blue .hero-stat-number { color: #e4e6eb; }
html.dark-blue .hero-stat-label { color: #b0b3b8; }

/* All Black theme */
html.all-black .hero-stat-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
}
html.all-black .hero-stat-icon { color: #0d84ff; }
html.all-black .hero-stat-number { color: #ffffff; }
html.all-black .hero-stat-label { color: #9ca3af; }

/* Mobile: botões e stats lado a lado */
@media (max-width: 768px) {
    .hero-buttons {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 0.75rem !important;
        align-items: stretch !important;
    }
    .hero-buttons .btn {
        flex: 1 1 calc(50% - 0.375rem) !important;
        min-width: 140px !important;
        max-width: none !important;
        width: auto !important;
        margin: 0 !important;
        font-size: 0.9rem !important;
        padding: 0.625rem 0.75rem !important;
    }
    .hero-stats {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: wrap !important;
        gap: 0.75rem !important;
        align-items: stretch !important;
    }
    .hero-stat-card {
        flex: 1 1 calc(50% - 0.375rem) !important;
        min-width: 140px !important;
        max-width: none !important;
        padding: 0.85rem 1rem !important;
    }
}

/* Remover linha branca superior e brilho azul nos temas escuros */
html.dark-blue .hero-stat-card::before,
html.all-black .hero-stat-card::before {
    display: none !important;
}

html.dark-blue .hero-stat-icon,
html.all-black .hero-stat-icon {
    box-shadow: none !important;
}

/* Melhorar formatação dos stats no mobile */
@media (max-width: 768px) {
    .hero-stat-card {
        padding: 1rem !important;
    }
    .hero-stat-icon {
        width: 40px !important;
        height: 40px !important;
        font-size: 1.1rem !important;
        flex-shrink: 0;
    }
    .hero-stat-content {
        flex: 1;
        min-width: 0;
    }
    .hero-stat-number {
        font-size: 1.1rem !important;
        white-space: nowrap;
    }
    .hero-stat-label {
        font-size: 0.75rem !important;
        line-height: 1.2;
    }
}

/* Corrigir overflow horizontal dos cards de cotações */
.home-preview-cards {
    max-width: 100%;
    overflow: hidden;
}

.home-preview-cards .card {
    max-width: 100%;
    overflow: hidden;
}

.home-preview-cards .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    max-width: 100%;
}

.home-preview-cards table {
    width: 100%;
    table-layout: fixed;
    font-size: 0.875rem;
}

/* Alinhar cards de cotações da Home com os internos do sistema */
.home-preview-cards .card_indices table td,
.home-preview-cards .card_indices table th {
    font-size: 12px !important;
}
.home-preview-cards .card-header.title-card {
    position: relative;
    padding: 0.75rem 0.75rem 0.75rem 1.25rem;
    font-size: 1.2rem;
}
/* Position media-percentage badge to the far right on mobile */
@media (max-width: 576px) {
  .home-preview-cards .media-percentage { right: 8px !important; top: 50%; transform: translateY(-50%); }
}

@media (max-width: 768px) {
    .home-preview-cards table {
        font-size: 0.72rem;
        min-width: 100% !important; /* Sobrescrever min-width: 520px do app.css */
    }
    .home-preview-cards table td,
    .home-preview-cards table th {
        padding: 0.45rem 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    /* Ocultar ícones fa-list nos card headers no mobile para economizar espaço */
    .hide-icon-mobile {
        display: none !important;
    }
    /* Garantir que o container não ultrapasse a viewport */
    .hero-visual {
        max-width: 100%;
        overflow: hidden;
    }
    .container {
        overflow-x: hidden;
    }
    .home-preview-cards .card-body {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    /* Ajustar padding dos cards para não encostar nas bordas */
    .home-preview-cards .card {
        margin: 0 0 1rem 0 !important;
    }
}

/* Captcha button centering */
#captcha-login-container, #captcha-forgot-container {
    display: flex;
    justify-content: center;
    width: 100%;
}
</style>

<!-- Header/Navigation -->
<header class="modern-header">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <!-- Logo -->
            <a class="navbar-brand" href="/">
                <div class="brand-icon me-2">
                    <img src="/assets/images/favicon.png" alt="Terminal" width="32" height="32" style="border-radius: 6px;"/>
                </div>
                <span class="brand-text">Terminal Operebem</span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Navigation Menu -->
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Links de navegação removidos a pedido (Recursos/FAQ) -->
                
                <!-- Auth Buttons -->
                <div class="navbar-auth ms-auto d-flex align-items-center gap-2">
                    <button id="themeToggle" class="theme-toggle-btn" type="button" aria-label="Alternar tema">
                        <i class="fas fa-moon"></i>
                    </button>
                    <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fas fa-sign-in-alt me-1"></i>Entrar
                    </button>
                    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#registerModal">
                        <i class="fas fa-user-plus me-1"></i>Cadastrar
                    </button>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- TradingView Ticker Tape (Glass) abaixo do header -->
<div class="container-fluid tv-glass px-0">
    <div class="tradingview-widget-container" id="home_ticker_container">
      <div class="tradingview-widget-container__widget"></div>
    </div>
  </div>

<!-- ISO 27001 Modal -->
<div class="modal fade" id="iso27001Modal" tabindex="-1" aria-labelledby="iso27001ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="iso27001ModalLabel"><i class="fas fa-shield-alt me-2"></i>ISO 27001 — Segurança da Informação</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body" id="iso27001ModalDesc">
        <p>Adotamos práticas alinhadas à <strong>ISO 27001</strong> para proteger dados, reduzir riscos e assegurar confidencialidade, integridade e disponibilidade das informações.</p>
        <ul class="mb-0">
          <li>Gestão de riscos e controles técnicos/organizacionais;</li>
          <li>Proteção de dados pessoais (LGPD/GDPR) e registros sensíveis;</li>
          <li>Monitoramento, auditoria e resposta a incidentes;</li>
          <li>Criptografia em repouso e em trânsito; políticas de acesso mínimo.</li>
        </ul>
        <div class="d-flex flex-wrap gap-2 mt-3" aria-label="Validações de segurança">
          <?php $__host = $_SERVER['HTTP_HOST'] ?? 'terminal.operebem.com.br'; $hostQ = rawurlencode($__host); ?>
          <a class="badge bg-secondary d-inline-flex align-items-center gap-1" href="https://www.ssllabs.com/ssltest/analyze.html?d=<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fas fa-lock"></i> SSL</a>
          <a class="badge bg-secondary d-inline-flex align-items-center gap-1" href="https://transparencyreport.google.com/safe-browsing/search?url=<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fab fa-google"></i> Safe Browsing</a>
          <span class="badge bg-secondary d-inline-flex align-items-center gap-1"><i class="fas fa-robot"></i> Anti-bot</span>
          <a class="badge bg-secondary d-inline-flex align-items-center gap-1" href="/privacy" target="_blank" rel="noopener"><i class="fas fa-user-shield"></i> LGPD/GDPR</a>
          <a class="badge bg-success d-inline-flex align-items-center gap-1" href="https://securityheaders.com/?q=<?= $hostQ ?>&followRedirects=on" target="_blank" rel="noopener"><i class="fas fa-shield-alt"></i> Headers A+</a>
          <a class="badge bg-success d-inline-flex align-items-center gap-1" href="https://observatory.mozilla.org/analyze/<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fab fa-firefox-browser"></i> Observatory A+</a>
          <a class="badge bg-info text-dark d-inline-flex align-items-center gap-1" href="https://www.ssllabs.com/ssltest/analyze.html?d=<?= $hostQ ?>" target="_blank" rel="noopener"><i class="fas fa-lock"></i> TLS 1.3</a>
        </div>
      </div>
      <div class="modal-footer">
        <div class="d-flex justify-content-between align-items-center w-100">
          <a class="btn btn-outline-secondary" href="https://group.operebem.com.br/terminal/privacy-policy" target="_blank" rel="noopener">Política de Privacidade</a>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendi</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Hero Section -->
<div class="hero-section">
    <div class="container">
        <div class="row align-items-start">
            <div class="col-lg-6">
                <div class="hero-content">
                    <div class="hero-badge animate-fade-in-up animate-delay-100">
                        <i class="fas fa-star me-2"></i>
                        ACESSO ANTECIPADO + BÔNUS
                    </div>
                    <h1 class="hero-title animate-fade-in-up animate-delay-200">
                        Terminal Operebem
                        <span class="text-gradient">Alpha 1.2</span>
                    </h1>
                    <p class="hero-subtitle animate-fade-in-up animate-delay-300">
                        O melhor farol para Traders. Pare de operar no escuro. Tenha acesso a mais contexto e seja movido por dados!
                    </p>
                    <div class="hero-features animate-fade-in-up animate-delay-400">
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Cotações globais em tempo real</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Indicadores Técnicos, de Sentimento e Volatilidade</span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-check-circle text-primary"></i>
                            <span>Curadoria de Notícias (Bloomberg, Reuters, Bancos Centrais...)</span>
                        </div>
                    </div>
                    <div class="hero-buttons animate-fade-in-up animate-delay-500">
                        <button class="btn btn-primary btn-lg me-3" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-rocket me-2"></i>Começar Agora
                        </button>
                        <button class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                    <div class="hero-stats animate-fade-in-up animate-delay-600">
                        <div class="hero-stat-card" id="iso27001Stat" role="button" tabindex="0" data-bs-toggle="modal" data-bs-target="#iso27001Modal" aria-describedby="iso27001ModalDesc">
                            <div class="hero-stat-icon">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="hero-stat-content">
                                <div class="hero-stat-number">ISO 27001</div>
                                <div class="hero-stat-label">Segurança Garantida</div>
                            </div>
                        </div>
                        <div class="hero-stat-card">
                            <div class="hero-stat-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="hero-stat-content">
                                <div class="hero-stat-number">R$0,00</div>
                                <div class="hero-stat-label">Sem cartão de crédito</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="row g-3 home-preview-cards">
                        <div class="col-12">
                            <div class="card w-100 card_indices mb-3 sw_metais">
                                <div class="card-header title-card">
                                    Commodities - Metais
                                    <span class="media-percentage" id="media-como-home"></span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="w-100">
                                        <table class="table mb-0">
                                            <tbody id="home_tbody_como"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card w-100 card_indices mb-3 sw_adrs">
                                <div class="card-header title-card">
                                    ADRs
                                    <span class="media-percentage" id="media-adrs-home"></span>
                                </div>
                                <div class="card-body p-0">
                                    <div class="w-100">
                                        <table class="table mb-0">
                                            <tbody id="home_tbody_adrs"></tbody>
                                        </table>
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

<!-- Features Section -->
<section id="features" class="features-section">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title">Recursos Avançados</h2>
                <p class="section-subtitle">Tudo que você precisa para contexto no trading profissional</p>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Feature 1: Segurança Total -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Segurança Total</h3>
                    <p class="feature-description">
                        Proteção avançada com criptografia padrão militar, headers de segurança e sistemas contra Robôs e Ataques.
                    </p>
                    <div class="feature-highlight">
                        <span class="highlight-text">99.8% Uptime</span>
                    </div>
                </div>
            </div>

            <!-- Feature 2: Cotações em Tempo Real -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Cotações em Tempo Real</h3>
                    <p class="feature-description">
                        Acompanhe cotações globais e indicadores financeiros com dados atualizados a cada segundo.
                    </p>
                    <div class="feature-highlight">
                        <span class="highlight-text">Dados em tempo real</span>
                    </div>
                </div>
            </div>
            
            <!-- Novos recursos -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h3 class="feature-title">Sentimento do Mercado</h3>
                    <p class="feature-description">
                        Desde indicadores famosos como o CNN Fear Greed Index, até indicadores exclusivos e autorais como o Operebem Risk Momentum!
                    </p>
                    <div class="feature-highlight">
                        <span class="highlight-text">Análise de Sentimento Avançada</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <h3 class="feature-title">Notícias em Primeira Mão</h3>
                    <p class="feature-description">
                        Acompanhe as notícias mais relevantes do mercado, originais ou traduzidas, em tempo real.
                    </p>
                    <div class="feature-highlight">
                        <span class="highlight-text">Curadoria profissional</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-sliders-h"></i>
                    </div>
                    <h3 class="feature-title">Indicadores Técnicos</h3>
                    <p class="feature-description">
                        Indicadores de Tendência, Market Barometers e tudo que você precisa
                    </p>
                    <div class="feature-highlight">
                        <span class="highlight-text">Indicadores Profissionais</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="feature-title">Monitore Eventos Econômicos</h3>
                    <p class="feature-description">
                        Calendário Econômico Investing customizável, Fed Watch Tool completo.
                    </p>
                    <div class="feature-highlight">
                        <span class="highlight-text">Esteja sempre antecipado</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="statistics-section">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-5">
                <h2 class="section-title text-white">Nossos Números</h2>
                <p class="section-subtitle text-white">Transparência e confiança Operebem</p>
            </div>
        </div>
        
        <div class="row g-4">
            <!-- Stat 1: Usuários Ativos -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number" data-target="<?= $statistics['user_count'] ?>">0</div>
                    <div class="stat-label">Usuários Ativos</div>
                </div>
            </div>
            
            <!-- Stat 2: Ativos -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="stat-number" data-target="<?= $statistics['active_assets'] ?>">0</div>
                    <div class="stat-label">Ativos Disponíveis</div>
                </div>
            </div>
            
            <!-- Stat 3: Uptime -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-server"></i>
                    </div>
                    <div class="stat-number" data-target="<?= $statistics['uptime'] ?>" data-suffix="%" data-random-min="99.7" data-random-max="99.9" data-decimals="1">0</div>
                    <div class="stat-label">Uptime</div>
                </div>
            </div>
            
            <!-- Stat 4: Dados Processados -->
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="stat-number" data-counter-mode="gb-tb" data-target-tb="2">0</div>
                    <div class="stat-label">Processamento/mes</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section (custom carousel with peek effect) -->
<section class="reviews-section" id="reviews">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center mb-4">
                <h2 class="reviews-title">O que dizem nossos <span class="highlight">traders</span></h2>
                <p class="reviews-subtitle">Confiança comprovada por investidores do mundo todo</p>
            </div>
        </div>
        <div class="reviews-carousel-wrapper">
            <div class="reviews-peek-container" id="reviewsContainer">
                <!-- Skeleton loading -->
                <div class="review-card">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-text"></div>
                    <div class="skeleton-text short"></div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" id="reviewsPrev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Anterior</span>
            </button>
            <button class="carousel-control-next" type="button" id="reviewsNext">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Próximo</span>
            </button>
            <div class="reviews-dots" id="reviewsDots"></div>
        </div>
    </div>
    <style>
    .reviews-section{padding:80px 0;background:#f7f7f7}
    .reviews-title{font-size:2.2rem;font-weight:700;color:#1a1a1a;margin-bottom:10px}
    .reviews-title .highlight{color:#3b82f6}
    .reviews-subtitle{color:#6b7280}
    .reviews-carousel-wrapper{position:relative;overflow:visible;padding:20px 0}
    .reviews-carousel-wrapper .carousel-control-prev,
    .reviews-carousel-wrapper .carousel-control-next{display:none}
    .reviews-peek-container{display:flex;justify-content:center;align-items:center;gap:30px;min-height:400px;position:relative;touch-action:pan-y;user-select:none;-webkit-user-select:none}
    .review-card{background:#fff;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:28px;border:1px solid rgba(0,0,0,.08);min-width:320px;max-width:420px;flex-shrink:0;transition:all .6s cubic-bezier(0.4, 0, 0.2, 1);position:absolute;left:50%;transform:translateX(-50%) scale(0.85);filter:blur(3px);opacity:0.5;z-index:1;pointer-events:none}
    .review-card.active{transform:translateX(-50%) scale(1);filter:blur(0);opacity:1;z-index:3;pointer-events:auto}
    .review-card.prev{transform:translateX(calc(-50% - 380px)) scale(0.85);filter:blur(3px);opacity:0.5;z-index:2;pointer-events:auto;cursor:pointer}
    .review-card.next{transform:translateX(calc(-50% + 380px)) scale(0.85);filter:blur(3px);opacity:0.5;z-index:2;pointer-events:auto;cursor:pointer}
    .review-card.prev:hover,.review-card.next:hover{opacity:0.7;transform:translateX(calc(-50% - 380px)) scale(0.9)}
    .review-card.next:hover{transform:translateX(calc(-50% + 380px)) scale(0.9)}
    .review-card.hidden{opacity:0;pointer-events:none;z-index:0;transform:translateX(-50%) scale(0.7)}
    .review-text{font-size:1rem;line-height:1.7;color:#4b5563;margin-bottom:20px;font-style:italic}
    .review-author{display:flex;align-items:center;gap:14px}
    .review-avatar{width:56px;height:56px;border-radius:50%;overflow:hidden;border:3px solid #e5e7eb}
    .review-avatar img{width:100%;height:100%;object-fit:cover}
    .review-name{font-weight:600;color:#1f2937;margin:0}
    .review-country{font-size:.875rem;color:#9ca3af}
    .review-rating{color:#fbbf24}
    .skeleton-avatar{width:56px;height:56px;border-radius:50%;background:linear-gradient(90deg,#e5e7eb 25%,#f3f4f6 50%,#e5e7eb 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;margin-bottom:16px}
    .skeleton-text{height:16px;background:linear-gradient(90deg,#e5e7eb 25%,#f3f4f6 50%,#e5e7eb 75%);background-size:200% 100%;animation:shimmer 1.5s infinite;border-radius:4px;margin-bottom:12px}
    .skeleton-text.short{width:60%}
    @keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
    .reviews-dots{display:none;justify-content:center;align-items:center;gap:8px;margin-top:24px}
    .reviews-dot{width:8px;height:8px;border-radius:50%;background:rgba(0,0,0,.25);transition:all .3s ease;cursor:pointer}
    .reviews-dot.active{background:#3b82f6;transform:scale(1.3)}
    html.dark-blue .reviews-dot{background:rgba(255,255,255,.25)}
    html.dark-blue .reviews-dot.active{background:#0d84ff}
    html.all-black .reviews-dot{background:rgba(255,255,255,.25)}
    html.all-black .reviews-dot.active{background:#0d84ff}
    @media(max-width:992px){.reviews-dots{display:flex}.reviews-peek-container{gap:0;overflow:hidden;touch-action:pan-y;cursor:grab}.reviews-peek-container:active{cursor:grabbing}.review-card{min-width:85%;max-width:85%}.review-card.prev,.review-card.next{transform:translateX(-200%) scale(0.7);opacity:0;pointer-events:none}.review-card.active{pointer-events:none}.reviews-carousel-wrapper .carousel-control-prev,.reviews-carousel-wrapper .carousel-control-next{display:flex;width:44px;height:44px;opacity:0.85;background:rgba(0,0,0,.65);border-radius:50%;top:50%;transform:translateY(-50%);z-index:10}.reviews-carousel-wrapper .carousel-control-prev:active,.reviews-carousel-wrapper .carousel-control-next:active{background:rgba(0,0,0,.85)}.reviews-carousel-wrapper .carousel-control-prev{left:8px}.reviews-carousel-wrapper .carousel-control-next{right:8px}.reviews-carousel-wrapper .carousel-control-prev-icon,.reviews-carousel-wrapper .carousel-control-next-icon{filter:invert(1);width:20px;height:20px}}
    @media(max-width:768px){.reviews-section{padding:60px 0}.reviews-peek-container{min-height:350px}.review-card{min-width:90%;max-width:90%;padding:24px}.review-card.prev,.review-card.next{transform:translateX(-200%) scale(0.6)}}
    html.dark-blue .reviews-section{background:#001233}
    html.dark-blue .reviews-title{color:#fff}
    html.dark-blue .reviews-subtitle{color:#9ca3af}
    html.dark-blue .review-card{background:#002855;border-color:#003d7a}
    html.dark-blue .review-text{color:#d1d5db}
    html.all-black .reviews-section{background:#0a0a0a}
    html.all-black .reviews-title{color:#fff}
    html.all-black .reviews-subtitle{color:#9ca3af}
    html.all-black .review-card{background:#1a1a1a;border-color:#2a2a2a}
    html.all-black .review-text{color:#d1d5db}
    /* FAQ theme backgrounds */
    html.light .faq-section{background:#f8fafc}
    html.dark-blue .faq-section{background:#001233}
    html.all-black .faq-section{background:#0a0a0a}
    /* FAQ text visibility */
    .faq-section .title_login{color:#1a1a1a}
    .faq-section .accordion-button{color:#1a1a1a;background-color:#fff}
    .faq-section .accordion-button:not(.collapsed){color:#0d6efd;background-color:#e7f1ff}
    .faq-section .accordion-body{color:#4b5563}
    html.dark-blue .faq-section .title_login{color:#fff}
    html.dark-blue .faq-section .accordion-button{color:#fff;background-color:#002855}
    html.dark-blue .faq-section .accordion-button:not(.collapsed){color:#0d84ff;background-color:#003d7a}
    html.dark-blue .faq-section .accordion-body{color:#d1d5db}
    html.all-black .faq-section .title_login{color:#fff}
    html.all-black .faq-section .accordion-button{color:#fff;background-color:#1a1a1a}
    html.all-black .faq-section .accordion-button:not(.collapsed){color:#0d84ff;background-color:#2a2a2a}
    html.all-black .faq-section .accordion-body{color:#d1d5db}
    /* Captcha: hide scrollbars */
    #captcha-login-container,#captcha-forgot-container{overflow:hidden}
    #captcha-login-container::-webkit-scrollbar,#captcha-forgot-container::-webkit-scrollbar{width:0;height:0}
    #captcha-login-container{scrollbar-width:none;-ms-overflow-style:none}
    #captcha-forgot-container{scrollbar-width:none;-ms-overflow-style:none}
    </style>
    <script nonce="<?= $_SERVER['CSP_NONCE'] ?? '' ?>">
    (function(){
      var reviews = [];
      var currentIndex = 0;

      function escapeHtml(t){var d=document.createElement('div');d.textContent=t;return d.innerHTML}
      function stars(r){var f=Math.floor(r||0),h=((r||0)%1)>=.5,e=5-f-(h?1:0),s='';for(var i=0;i<f;i++)s+='<i class="fas fa-star"></i>';if(h)s+='<i class="fas fa-star-half-alt"></i>';for(var j=0;j<e;j++)s+='<i class="far fa-star"></i>';return s}

      function buildCard(rv){
        return '<div class="review-card">\
          <p class="review-text">'+escapeHtml(rv.review_text||'')+'</p>\
          <div class="review-author">\
            <div class="review-avatar"><img src="'+escapeHtml(rv.author_avatar||('https://i.pravatar.cc/150?img='+(Math.floor(Math.random()*70)+1)))+'" alt="'+escapeHtml(rv.author_name||'')+'" onerror="this.src=\'https://i.pravatar.cc/150?img=1\'"></div>\
            <div class="review-info">\
              <h3 class="review-name">'+escapeHtml(rv.author_name||'')+'</h3>\
              '+(rv.author_country?('<small class="review-country">'+escapeHtml(rv.author_country)+'</small>'):'')+'\
              <div class="review-rating">'+stars(rv.rating||0)+'</div>\
            </div>\
          </div>\
        </div>';
      }

      function updateCards(){
        var container = document.getElementById('reviewsContainer');
        if(!container) return;
        var cards = container.querySelectorAll('.review-card');
        var total = cards.length;
        if(total === 0) return;

        cards.forEach(function(card, idx){
          card.classList.remove('active','prev','next','hidden');
          card.onclick = null; // Remove handler anterior

          if(idx === currentIndex){
            card.classList.add('active');
          } else if(idx === (currentIndex - 1 + total) % total){
            card.classList.add('prev');
            card.onclick = function(){ prev(); };
          } else if(idx === (currentIndex + 1) % total){
            card.classList.add('next');
            card.onclick = function(){ next(); };
          } else {
            card.classList.add('hidden');
          }
        });

        // Update dots
        var dotsContainer = document.getElementById('reviewsDots');
        if(dotsContainer){
          var dots = dotsContainer.querySelectorAll('.reviews-dot');
          dots.forEach(function(dot, idx){
            if(idx === currentIndex){
              dot.classList.add('active');
            } else {
              dot.classList.remove('active');
            }
          });
        }
      }

      function renderDots(){
        var dotsContainer = document.getElementById('reviewsDots');
        if(!dotsContainer || reviews.length === 0) return;

        dotsContainer.innerHTML = '';
        reviews.forEach(function(_, idx){
          var dot = document.createElement('div');
          dot.className = 'reviews-dot' + (idx === 0 ? ' active' : '');
          dot.onclick = function(){
            currentIndex = idx;
            updateCards();
          };
          dotsContainer.appendChild(dot);
        });
      }

      function next(){
        if(reviews.length === 0) return;
        currentIndex = (currentIndex + 1) % reviews.length;
        updateCards();
      }

      function prev(){
        if(reviews.length === 0) return;
        currentIndex = (currentIndex - 1 + reviews.length) % reviews.length;
        updateCards();
      }

      // Swipe functionality
      var touchStartX = 0;
      var touchEndX = 0;
      var touchStartY = 0;
      var isSwiping = false;

      function handleSwipeEnd(){
        var deltaX = touchEndX - touchStartX;
        var deltaY = touchStartY - touchStartY;

        // Check if horizontal swipe (threshold: 30px)
        if(Math.abs(deltaX) > 30 && isSwiping){
          if(deltaX > 0){
            prev(); // Swipe right -> previous
          } else {
            next(); // Swipe left -> next
          }
        }
        isSwiping = false;
      }

      async function load(){
        try{
          var res=await fetch('/api/reviews');
          var j=await res.json();
          if(!j.success||!j.data||!j.data.length) return;

          reviews = j.data;
          var container=document.getElementById('reviewsContainer');
          if(!container) return;
          container.innerHTML = reviews.map(buildCard).join('');

          currentIndex = 0;
          updateCards();
          renderDots();

          var prevBtn = document.getElementById('reviewsPrev');
          var nextBtn = document.getElementById('reviewsNext');
          if(prevBtn) prevBtn.addEventListener('click', prev);
          if(nextBtn) nextBtn.addEventListener('click', next);

          // Add swipe support - attach to container
          container.addEventListener('touchstart', function(e){
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
            isSwiping = true;
          }, {passive: true});

          container.addEventListener('touchmove', function(e){
            if(!isSwiping) return;
            touchEndX = e.touches[0].clientX;
          }, {passive: true});

          container.addEventListener('touchend', function(e){
            if(!isSwiping) return;
            touchEndX = e.changedTouches[0].clientX;
            handleSwipeEnd();
          }, {passive: true});

          container.addEventListener('touchcancel', function(){
            isSwiping = false;
          }, {passive: true});
        }catch(e){/* noop */}
      }

      if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',load);} else {load();}
    })();
    </script>
</section>

<!-- Auto-open auth modals based on query param -->
  <script>
  (function(){
    function getParam(name){ try{ var u=new URL(window.location.href); return u.searchParams.get(name)||''; }catch(e){ return ''; } }
    function openModal(id){ try{ var el=document.getElementById(id); if(!el) return; var m=new bootstrap.Modal(el); m.show(); }catch(e){} }
    document.addEventListener('DOMContentLoaded', function(){
      var m = (getParam('modal')||'').toLowerCase();
      if (m === 'login') { openModal('loginModal'); }
      else if (m === 'register') { openModal('registerModal'); }
      else if (m === 'forgot') { openModal('forgotPasswordModal'); }
    });
  })();
  </script>

<!-- FAQ Section (após Reviews) -->
<section id="faq" class="py-5 faq-section">
    <div class="container">
        <div class="w-100 text-center mb-5 pb-3">
            <h3 class="title_login mb-0">Perguntas Frequentes</h3>
        </div>
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    As cotações são em tempo real?
                </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
                    <div class="accordion-body text-secondary">
                    Sim, a maioria das cotações são exibidas em tempo real. Nosso compromisso é fornecer a menor latência possível na atualização dos dados. No entanto, algumas cotações podem ter um atraso de 5 a 15 minutos devido a restrições regulatórias da bolsa, que controla a distribuição dessas informações.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                     Quais mercados estão disponíveis na plataforma?
                </button>
                </h2>
                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionExample">
                    <div class="accordion-body text-secondary">
                    Nossa plataforma cobre mercados globais, incluindo índices futuros e spot mundiais, ADRs brasileiras, criptoativos, commodities, juros, indice do dólar, dólar frente ao mundo e emergentes, principais petroleiras e mineradoras.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                Posso exportar os dados para o Excel?
                </button>
                </h2>
                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionExample">
                    <div class="accordion-body text-secondary">
                    Sim. Oferecemos download em Excel do Dashboard. No momento, o arquivo não recebe atualizações automáticas em tempo real; essa função será integrada em versões futuras.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                Posso acessar a plataforma pelo celular ou tablet?
                </button>
                </h2>
                <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionExample">
                    <div class="accordion-body text-secondary">
                    Sim. Nossa plataforma é responsiva e pode ser acessada tanto pelo computador quanto por dispositivos móveis, garantindo a melhor experiência para todos os usuários.
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFive">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                Quais as fontes das notícias que são fornecidas?
                </button>
                </h2>
                <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#accordionExample">
                    <div class="accordion-body text-secondary">
                    Fonte própria, Bloomberg, CoinDesk, Financial Times, OilPrice, CNBC, Investing, Money Times, InfoMoney, Yahoo Finance, The Wall Street Journal e Banco Central do Brasil.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Section -->
<section class="final-cta-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="cta-content">
                    <h2 class="cta-title">Pronto para Começar?</h2>
                    <p class="cta-subtitle">
                        Junte-se a outros traders que já confiam no Terminal Operebem para aprender e melhorar seus resultados.
                    </p>
                    <div class="cta-buttons">
                        <button class="btn btn-light btn-lg me-3" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fas fa-rocket me-2"></i>Criar Conta Gratuita
                        </button>
                        <button class="btn btn-outline-light btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fas fa-sign-in-alt me-2"></i>Já tenho conta
                        </button>
                    </div>
                    <div class="cta-features">
                        <div class="cta-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>100% Gratuito</span>
                        </div>
                        <div class="cta-feature">
                            <i class="fas fa-check-circle"></i>
                            <span>Sem compromisso</span>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de Login -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="loginModalLabel">
                    <i class="fas fa-sign-in-alt me-2"></i>Entrar
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="loginAlertContainer"></div>
                
                <form id="loginForm" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    
                    <div class="mb-4">
                        <label for="email_login" class="form-label">Email</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email_login" name="email" 
                                   placeholder="seu@email.com" required>
                        </div>
                        <div class="invalid-feedback">
                            Por favor, informe um email válido.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password_login" class="form-label">Senha</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-control" id="password_login" name="password" 
                                   placeholder="Sua senha" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordLoginHome" aria-label="Mostrar/ocultar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Por favor, informe sua senha.
                        </div>
                    </div>
                    
                    <!-- Captcha: container + hidden token -->
                    <div class="mb-3 d-none" id="captcha-login-wrap">
                        <div id="captcha-login-container"></div>
                        <input type="hidden" id="captcha_token_login" name="captcha_token" value="">
                        <input type="hidden" id="rc_token_login" name="rc_token" value="">
                        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-lg" id="loginSubmitBtn" disabled>
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <button type="button" class="btn btn-link" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">
                            <i class="fas fa-question-circle me-1"></i>Esqueci minha senha
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/register-modal.php'; ?>

<!-- Modal de Esqueci a Senha -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="forgotPasswordModalLabel">
                    <i class="fas fa-key me-2"></i>Recuperar Senha
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="forgotPasswordAlertContainer"></div>
                
                <p class="text-muted mb-4">
                    Digite seu email para receber instruções de recuperação de senha.
                    <br>
                    <span> Caso necessite, entre em contato com o 
                        <a href="/support" class="fw-bold" style="color: var(--secondary-color);">Suporte</a>.
                    </span>
                </p>
                
                <form id="forgotPasswordForm" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" id="rc_token_forgot" name="rc_token" value="">
                    
                    <div class="mb-4">
                        <label for="email_forgot" class="form-label">Email</label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">
                                <i class="fas fa-envelope"></i>
                            </span>
                            <input type="email" class="form-control" id="email_forgot" name="email" 
                                   placeholder="seu@email.com" autocomplete="email" required>
                        </div>
                        <div class="invalid-feedback">
                            Por favor, informe um email válido.
                        </div>
                    </div>
                    <div class="mb-3 d-none" id="captcha-forgot-wrap">
                        <div id="captcha-forgot-container"></div>
                        <input type="hidden" id="captcha_token_forgot" name="captcha_token" value="">
                        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-5000px;height:1px;width:1px;overflow:hidden;" aria-hidden="true">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg" id="forgotSubmitBtn">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$scripts = <<<'SCRIPTS'
<script src="/sdk/captcha-sdk.js"></script>
<script src="/assets/js/register-6steps.js"></script>
<script src="/assets/js/index-interactive.js"></script>
<script src="/assets/js/home-preview.js?v=<?= time() ?>"></script>
<script src="/assets/js/status-service.js?v=<?= time() ?>"></script>
<script src="/assets/js/home-websocket.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var iso = document.getElementById('iso27001Stat');
    if (iso) {
        iso.style.cursor = 'pointer';
        iso.addEventListener('click', function(){
            try {
                var m = new bootstrap.Modal(document.getElementById('iso27001Modal'));
                m.show();
            } catch(e) {
                var modalEl = document.getElementById('iso27001Modal');
                if (modalEl) modalEl.classList.add('show');
            }
        });
    }
});
document.addEventListener("DOMContentLoaded", function() {
    // Reveal captcha button only when user interacts or fields are prefilled
    (function() {
        var loginWrap = document.getElementById('captcha-login-wrap');
        var email = document.getElementById('email_login');
        var pwd = document.getElementById('password_login');
        function showLoginCaptcha(){ if (loginWrap) loginWrap.classList.remove('d-none'); }
        function maybeShowLogin(){ if (!email || !pwd) return; if ((email.value||'').trim() || (pwd.value||'').trim()) showLoginCaptcha(); }
        if (email) { email.addEventListener('focus', showLoginCaptcha); email.addEventListener('input', maybeShowLogin); }
        if (pwd) { pwd.addEventListener('focus', showLoginCaptcha); pwd.addEventListener('input', maybeShowLogin); }
        setTimeout(maybeShowLogin, 400); // catch autofill
    })();
    (function(){
        var forgotWrap = document.getElementById('captcha-forgot-wrap');
        var forgotEmail = document.getElementById('email_forgot');
        function showForgot(){ if (forgotWrap) forgotWrap.classList.remove('d-none'); }
        function maybeShowForgot(){ if (!forgotEmail) return; if ((forgotEmail.value||'').trim()) showForgot(); }
        if (forgotEmail) { forgotEmail.addEventListener('focus', showForgot); forgotEmail.addEventListener('input', maybeShowForgot); }
        setTimeout(maybeShowForgot, 400);
    })();
    // Login Form
    const loginForm = document.getElementById("loginForm");
    const loginSubmitBtn = document.getElementById('loginSubmitBtn');
    const captchaTokenInput = document.getElementById('captcha_token_login');
    const forgotTokenInput = document.getElementById('captcha_token_forgot');
    
    // Create/Recreate widgets adaptively (theme inferred by SDK)
    function initCaptchaLogin(){
        if (!window.OpereBemCaptcha) return;
        const el = document.getElementById('captcha-login-container');
        if (!el) return;
        try { if (window.__ob_captcha_login && typeof window.__ob_captcha_login.destroy === 'function') { window.__ob_captcha_login.destroy(); } } catch(e){}
        window.__ob_captcha_login = new OpereBemCaptcha({
            container: '#captcha-login-container',
            mode: 'modal',
            apiBase: '',
            onSuccess: function(token){
                if (captchaTokenInput) captchaTokenInput.value = token || '';
                if (loginSubmitBtn) loginSubmitBtn.disabled = !(!!token);
            },
            onExpired: function(){ if (captchaTokenInput) captchaTokenInput.value = ''; if (loginSubmitBtn) loginSubmitBtn.disabled = true; },
            onError: function(){ if (captchaTokenInput) captchaTokenInput.value = ''; if (loginSubmitBtn) loginSubmitBtn.disabled = true; }
        });
    }
    function initCaptchaForgot(){
        if (!window.OpereBemCaptcha) return;
        const el = document.getElementById('captcha-forgot-container');
        if (!el) return;
        try { if (window.__ob_captcha_forgot && typeof window.__ob_captcha_forgot.destroy === 'function') { window.__ob_captcha_forgot.destroy(); } } catch(e){}
        window.__ob_captcha_forgot = new OpereBemCaptcha({
            container: '#captcha-forgot-container',
            mode: 'modal',
            apiBase: '',
            onSuccess: function(token){ if (forgotTokenInput) forgotTokenInput.value = token || ''; },
            onExpired: function(){ if (forgotTokenInput) forgotTokenInput.value = ''; },
            onError: function(){ if (forgotTokenInput) forgotTokenInput.value = ''; }
        });
    }
    initCaptchaLogin();
    initCaptchaForgot();
    // Reinitialize on theme change
    window.addEventListener('themeChanged', function(){
        initCaptchaLogin();
        initCaptchaForgot();
    });
    // Password toggle no modal de login
    (function(){
        var toggleBtn = document.getElementById('togglePasswordLoginHome');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(){
                var pwd = document.getElementById('password_login');
                var icon = toggleBtn.querySelector('i');
                if (!pwd || !icon) return;
                if (pwd.type === 'password') { pwd.type = 'text'; icon.classList.replace('fa-eye','fa-eye-slash'); }
                else { pwd.type = 'password'; icon.classList.replace('fa-eye-slash','fa-eye'); }
            });
        }
    })();
    // reCAPTCHA v3 tokens para login e esqueci a senha
    (function(){
        function setRcLogin(){
            try {
                var el = document.getElementById('rc_token_login');
                if (!el) return;
                if (!window.grecaptcha || !window.__RECAPTCHA_V3_SITE_KEY){ setTimeout(setRcLogin, 250); return; }
                grecaptcha.ready(function(){ grecaptcha.execute(window.__RECAPTCHA_V3_SITE_KEY, {action:'user_login'}).then(function(tok){ el.value = tok||''; }); });
            } catch(e){ setTimeout(setRcLogin, 400); }
        }
        function setRcForgot(){
            try {
                var el = document.getElementById('rc_token_forgot');
                if (!el) return;
                if (!window.grecaptcha || !window.__RECAPTCHA_V3_SITE_KEY){ setTimeout(setRcForgot, 250); return; }
                grecaptcha.ready(function(){ grecaptcha.execute(window.__RECAPTCHA_V3_SITE_KEY, {action:'forgot_password'}).then(function(tok){ el.value = tok||''; }); });
            } catch(e){ setTimeout(setRcForgot, 400); }
        }
        document.addEventListener('DOMContentLoaded', function(){ setRcLogin(); setRcForgot(); });
    })();
    
    // Auto-open modals based on query parameter
    (function() {
        const params = new URLSearchParams(window.location.search);
        const modalParam = params.get('modal');
        if (modalParam) {
            let modalId = null;
            if (modalParam === 'login') modalId = 'loginModal';
            else if (modalParam === 'register') modalId = 'registerModal';
            else if (modalParam === 'forgot') modalId = 'forgotPasswordModal';
            
            if (modalId) {
                const modalEl = document.getElementById(modalId);
                if (modalEl && typeof bootstrap !== 'undefined') {
                    setTimeout(() => {
                        const modal = new bootstrap.Modal(modalEl);
                        modal.show();
                        // Clean URL without reload
                        const cleanUrl = window.location.pathname;
                        window.history.replaceState({}, document.title, cleanUrl);
                    }, 300);
                }
            }
        }
    })();
    
    if (loginForm) {
        loginForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            if (!loginForm.checkValidity()) {
                e.stopPropagation();
                loginForm.classList.add("was-validated");
                return;
            }
            // Require captcha token
            if (!captchaTokenInput || (captchaTokenInput.value||'') === '') {
                const alertContainer = document.getElementById("loginAlertContainer");
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-robot me-2"></i>Por favor, confirme que você não é um robô.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>`;
                }
                return;
            }
            
            const button = loginForm.querySelector("button[type=\"submit\"]");
            const originalText = button.innerHTML;
            
            // Mostrar loading
            button.disabled = true;
            button.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i>Entrando...";
            
            try {
                const formData = new FormData(loginForm);
                const response = await fetch("/login", {
                    method: "POST",
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    try { if (typeof gtag === 'function') { gtag('event', 'login', { method: 'password' }); } } catch(e){}
                    
                    // Transform modal into loading state
                    const modalContent = document.querySelector('#loginModal .modal-content');
                    if (modalContent) {
                        modalContent.innerHTML = `
                            <div class="modal-body text-center py-5">
                                <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                    <span class="visually-hidden">Carregando...</span>
                                </div>
                                <h5 class="mb-2">Entrando no Terminal</h5>
                                <p class="text-muted">Preparando seu dashboard...</p>
                            </div>
                        `;
                    }
                    
                    // Redirect after brief delay for UX
                    setTimeout(() => {
                        window.location.href = result.redirect || "/app/dashboard";
                    }, 800);
                } else {
                    // Mostrar erro
                    const alertContainer = document.getElementById("loginAlertContainer");
                    if (alertContainer) {
                        alertContainer.innerHTML = `
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                ${result.message || "Erro no login"}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                    }
                    // Se o servidor exigir reset de captcha, limpar token e reinicializar widget
                    try {
                        if (result.require_captcha_reset) {
                            if (captchaTokenInput) captchaTokenInput.value = '';
                            if (typeof initCaptchaLogin === 'function') { initCaptchaLogin(); }
                            if (loginSubmitBtn) loginSubmitBtn.disabled = true;
                        }
                    } catch(e){}
                }
            } catch (error) {
                console.error("Erro no login:", error);
                const alertContainer = document.getElementById("loginAlertContainer");
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erro de conexão. Tente novamente.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }
            } finally {
                // Restaurar botão
                button.disabled = false;
                button.innerHTML = originalText;
            }
        });
    }
    
    // Forgot Password Form
    const forgotPasswordForm = document.getElementById("forgotPasswordForm");
    const forgotSubmitBtn = document.getElementById('forgotSubmitBtn');
    let __forgotTimer = null;

    function setForgotBtnState(disabled, labelHtml){
        if (!forgotSubmitBtn) return;
        forgotSubmitBtn.disabled = !!disabled;
        if (disabled) { forgotSubmitBtn.classList.remove('btn-primary'); forgotSubmitBtn.classList.add('btn-secondary'); }
        else { forgotSubmitBtn.classList.add('btn-primary'); forgotSubmitBtn.classList.remove('btn-secondary'); }
        if (labelHtml) { forgotSubmitBtn.innerHTML = labelHtml; }
    }

    function startForgotCooldown(seconds){
        try { localStorage.setItem('forgotCooldownEnd', String(Date.now() + (seconds*1000))); } catch(e){}
        if (__forgotTimer) { clearInterval(__forgotTimer); __forgotTimer = null; }
        const tick = () => {
            let end = 0;
            try { end = parseInt(localStorage.getItem('forgotCooldownEnd')||'0', 10) || 0; } catch(e){ end = 0; }
            const now = Date.now();
            const remain = Math.max(0, Math.ceil((end - now)/1000));
            if (remain > 0) {
                setForgotBtnState(true, `<i class="fas fa-clock me-2"></i>Enviar novamente (${remain}s)`);
            } else {
                if (__forgotTimer) { clearInterval(__forgotTimer); __forgotTimer = null; }
                try { localStorage.removeItem('forgotCooldownEnd'); } catch(e){}
                setForgotBtnState(false, `<i class="fas fa-redo me-2"></i>Enviar novamente`);
            }
        };
        tick();
        __forgotTimer = setInterval(tick, 1000);
    }

    // Resume cooldown on load (but keep initial label if no cooldown)
    (function resumeForgot(){
        let end = 0;
        try { end = parseInt(localStorage.getItem('forgotCooldownEnd')||'0', 10) || 0; } catch(e){}
        if (end && end > Date.now()) {
            startForgotCooldown(Math.ceil((end - Date.now())/1000));
        }
        // Don't change button label on load - let it stay as "Enviar Email" initially
    })();

    if (forgotPasswordForm) {
        forgotPasswordForm.addEventListener("submit", async function(e) {
            e.preventDefault();
            
            if (!forgotPasswordForm.checkValidity()) {
                e.stopPropagation();
                forgotPasswordForm.classList.add("was-validated");
                return;
            }
            const forgotCaptchaTokenInput = document.getElementById('captcha_token_forgot');
            if (!forgotCaptchaTokenInput || (forgotCaptchaTokenInput.value||'') === '') {
                const alertContainer = document.getElementById("forgotPasswordAlertContainer");
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <i class="fas fa-robot me-2"></i>Por favor, confirme que você não é um robô.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>`;
                }
                return;
            }
            
            const button = forgotSubmitBtn || forgotPasswordForm.querySelector("button[type=\"submit\"]");
            const originalText = button ? button.innerHTML : '';
            
            // Mostrar loading
            if (button) { button.disabled = true; button.innerHTML = "<i class=\"fas fa-spinner fa-spin me-2\"></i>Enviando..."; }
            
            try {
                const formData = new FormData(forgotPasswordForm);
                const response = await fetch("/forgot-password", {
                    method: "POST",
                    body: formData
                });
                
                const result = await response.json();
                
                const alertContainer = document.getElementById("forgotPasswordAlertContainer");
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-${result.success ? "success" : "danger"} alert-dismissible fade show" role="alert">
                            <i class="fas fa-${result.success ? "check-circle" : "exclamation-circle"} me-2"></i>
                            ${result.message || "Erro ao enviar"}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }

                // Cooldown handling
                if (result && result.success) {
                    const cd = parseInt(result.cooldown || '0', 10);
                    startForgotCooldown(cd > 0 ? cd : 180);
                }
            } catch (error) {
                console.error("Erro ao enviar email:", error);
                const alertContainer = document.getElementById("forgotPasswordAlertContainer");
                if (alertContainer) {
                    alertContainer.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erro de conexão. Tente novamente.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    `;
                }
            } finally {
                var __endTs = 0;
                try { __endTs = parseInt(localStorage.getItem('forgotCooldownEnd')||'0', 10) || 0; } catch(e){ __endTs = 0; }
                if (!__endTs || __endTs <= Date.now()) {
                    if (button) { button.disabled = false; button.innerHTML = originalText; }
                }
            }
        });
    }

    (function(){
        function getCurrentTheme(){
            var c = document.documentElement.classList;
            if (c.contains('dark-blue') || c.contains('all-black')) return 'dark';
            return 'light';
        }
        var __tvThemeRendered = null; // null = nunca renderizado, string = tema renderizado
        var __tvRenderTimer = null;
        var __tvInitialized = false;
        function renderHomeTicker(){
            var outer = document.getElementById('home_ticker_container');
            if (!outer) return;
            var theme = getCurrentTheme();
            // Só bloquear re-render se já foi inicializado E o tema é o mesmo
            if (__tvInitialized && __tvThemeRendered === theme) return;
            __tvThemeRendered = theme;
            __tvInitialized = true;
            if (__tvRenderTimer) { clearTimeout(__tvRenderTimer); __tvRenderTimer = null; }
            while (outer.firstChild) { outer.removeChild(outer.firstChild); }
            var inner = document.createElement('div');
            inner.className = 'tradingview-widget-container__widget';
            outer.appendChild(inner);
            var cfg = {
                symbols: [
                    { proName: 'BMFBOVESPA:IBOV', title: 'IBOV' },
                    { proName: 'FX_IDC:USDBRL', title: 'USD/BRL' },
                    { proName: 'BMFBOVESPA:VALE3', title: 'VALE3' },
                    { proName: 'BMFBOVESPA:PETR4', title: 'PETR4' },
                    { proName: 'BINANCE:BTCUSDT', title: 'BTC' }
                ],
                showSymbolLogo: true,
                isTransparent: theme !== 'light',
                displayMode: 'adaptive',
                colorTheme: theme,
                locale: 'br'
            };
            __tvRenderTimer = setTimeout(function(){
              var s = document.createElement('script');
              s.type = 'text/javascript';
              s.async = true;
              s.src = 'https://s3.tradingview.com/external-embedding/embed-widget-ticker-tape.js';
              s.text = JSON.stringify(cfg);
              outer.appendChild(s);
            }, 100);
        }
        // Garantir que renderiza após DOM completo + pequeno delay
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function(){ setTimeout(renderHomeTicker, 150); });
        } else {
            setTimeout(renderHomeTicker, 150);
        }
        // Observar mudanças de tema
        new MutationObserver(function(m){
            m.forEach(function(x){ 
                if (x.attributeName === 'class') { 
                    __tvInitialized = false; 
                    renderHomeTicker(); 
                } 
            });
        }).observe(document.documentElement, { attributes: true });
    })();
    const captchaScript = document.getElementById('captcha-sdk-script');
    if (captchaScript) {
        const src = captchaScript.src;
        if (src.includes('?')) {
            captchaScript.src = `${src}&render=explicit`;
        } else {
            captchaScript.src = `${src}?render=explicit`;
        }
    }
    const isoModalFooter = document.getElementById('iso-modal-footer');
    if (isoModalFooter) {
        isoModalFooter.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="fas fa-lock me-2"></i>
                    <a href="#" class="text-decoration-none">Segurança</a>
                </div>
                <div class="d-flex align-items-center">
                    <i class="fas fa-shield-alt me-2"></i>
                    <a href="#" class="text-decoration-none">Privacidade</a>
                </div>
            </div>
        `;
    }
    const loginCaptchaContainer = document.getElementById('login-captcha-container');
    const forgotPasswordCaptchaContainer = document.getElementById('forgot-password-captcha-container');
    if (loginCaptchaContainer && forgotPasswordCaptchaContainer) {
        const loginInput = document.getElementById('login-input');
        const forgotPasswordInput = document.getElementById('forgot-password-input');
        if (loginInput && forgotPasswordInput) {
            loginInput.addEventListener('focus', function() {
                loginCaptchaContainer.classList.remove('d-none');
            });
            forgotPasswordInput.addEventListener('focus', function() {
                forgotPasswordCaptchaContainer.classList.remove('d-none');
            });
            loginInput.addEventListener('blur', function() {
                if (!loginInput.value) {
                    loginCaptchaContainer.classList.add('d-none');
                }
            });
            forgotPasswordInput.addEventListener('blur', function() {
                if (!forgotPasswordInput.value) {
                    forgotPasswordCaptchaContainer.classList.add('d-none');
                }
            });
        }
    }
});
</script>
SCRIPTS;
include __DIR__ . '/../layouts/app.php';
?>
