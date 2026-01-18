<!DOCTYPE html>
<html lang="pt-BR" class="<?= htmlspecialchars($user['theme'] ?? 'light', ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($title ?? 'Terminal Operebem', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" href="/assets/images/favicon.png" type="image/png">
    <link rel="shortcut icon" href="/assets/images/favicon.png" type="image/png">
    
    <!-- DNS Prefetch & Preconnect for Performance -->
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://code.jquery.com">
    <link rel="dns-prefetch" href="https://cdn.sheetjs.com">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
    <link rel="dns-prefetch" href="https://s3.tradingview.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://code.jquery.com" crossorigin>
    <link rel="preconnect" href="https://cdn.sheetjs.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <script src="/assets/js/app-head.js"></script>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Flag Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flag-icons@7.2.3/css/flag-icons.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'"><noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet"></noscript>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/app.css?v=<?= time() ?>">
    <!-- Theme CSS (split by theme for maintainability) -->
    <link rel="stylesheet" href="/assets/css/theme-light.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/css/theme-dark.css?v=<?= time() ?>">
    <!-- Support Page Themes -->
    <link rel="stylesheet" href="/assets/css/support-themes.css?v=<?= time() ?>">
    <!-- Alert Theme Optimizations -->
    <link rel="stylesheet" href="/assets/css/alert-themes.css?v=<?= time() ?>">
    
    <!-- reCAPTCHA v3 (async load) -->
    <?php $SITE_KEY_V3 = $_ENV['RECAPTCHA_V3_SITE_KEY'] ?? ''; ?>
    <meta name="recaptcha-site-key" content="<?= htmlspecialchars($SITE_KEY_V3, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($SITE_KEY_V3)): ?>
    <script src="https://www.google.com/recaptcha/api.js?render=<?= htmlspecialchars($SITE_KEY_V3, ENT_QUOTES, 'UTF-8') ?>" async defer></script>
    <?php endif; ?>
    <script src="/assets/js/recaptcha-v3.js" defer></script>
    
    <?php
      $GA_ID = $_ENV['GA_MEASUREMENT_ID'] ?? 'G-FFBFM4KN68';
      $APP_ENV = $_ENV['APP_ENV'] ?? 'production';
      $APP_DEBUG = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
      if (!empty($GA_ID) && $APP_ENV === 'production' && !$APP_DEBUG):
    ?>
    <meta name="ga-id" content="<?= htmlspecialchars($GA_ID, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (isset($user) && !empty($user['id'])): ?>
    <meta name="ga-user-id" content="<?= (int)$user['id'] ?>">
    <?php endif; ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= htmlspecialchars($GA_ID, ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="/assets/js/gtag-init.js" defer></script>
    <?php endif; ?>

    <!-- WebSocket configuration (meta to externalize inline script) -->
    <?php $WS_URL = $_ENV['WS_PROXY_URL'] ?? ''; $WS_KEY = $_ENV['WS_PROXY_KEY'] ?? ''; ?>
    <?php if (!empty($WS_URL)): ?>
    <meta name="ws-url" content="<?= htmlspecialchars($WS_URL, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php if (isset($user) && $user && !empty($WS_KEY)): ?>
    <meta name="ws-key" content="<?= htmlspecialchars($WS_KEY, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>
    <?php 
      $adminVariant = $footerVariant ?? '';
      $isAdminAuth = (!empty($adminVariant) && $adminVariant === 'admin-auth');
      $isAdminEntry = (!empty($adminVariant) && in_array($adminVariant, ['admin-login','admin-2fa'], true));
      if ($isAdminAuth || $isAdminEntry): ?>
    <nav class="navbar navbar-expand-lg navbar-light navbar-auth">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="<?= $isAdminAuth ? '/secure/adm/index' : '/secure/adm/login' ?>">
                <img src="/assets/images/favicon.png" alt="" aria-hidden="true" width="24" height="24" class="me-2 rounded"/>
                Secure Admin
            </a>
            <?php if ($isAdminAuth): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <?php $req = $_SERVER['REQUEST_URI'] ?? ''; $is = function($p) use ($req){ return strncmp($req, $p, strlen($p)) === 0 ? 'active' : ''; }; ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link <?= $is('/secure/adm/aluno') ?>" href="/secure/adm/aluno"><i class="fas fa-graduation-cap me-2 d-lg-none"></i>Portal do Aluno</a></li>
                    <li class="nav-item"><a class="nav-link <?= $is('/secure/adm/gamification') ?>" href="/secure/adm/gamification"><i class="fas fa-gamepad me-2 d-lg-none"></i>XP & Gamificação</a></li>
                    <li class="nav-item"><a class="nav-link <?= $is('/secure/adm/tickets') ?>" href="/secure/adm/tickets"><i class="fas fa-headset me-2 d-lg-none"></i>Tickets</a></li>
                    <li class="nav-item"><a class="nav-link <?= $is('/secure/adm/users') ?>" href="/secure/adm/users"><i class="fas fa-users me-2 d-lg-none"></i>Usuários</a></li>
                    <li class="nav-item"><a class="nav-link <?= $is('/secure/adm/crm') ?>" href="/secure/adm/crm"><i class="fas fa-address-card me-2 d-lg-none"></i>CRM</a></li>
                    <li class="nav-item"><a class="nav-link <?= $is('/secure/adm/reviews') ?>" href="/secure/adm/reviews"><i class="fas fa-star me-2 d-lg-none"></i>Reviews</a></li>
                    <li class="nav-item"><a class="nav-link <?= $is('/secure/adm/admins') ?>" href="/secure/adm/admins"><i class="fas fa-users-cog me-2 d-lg-none"></i>Admins</a></li>
                </ul>
                <div class="d-flex mt-3 mt-lg-0">
                    <form method="POST" action="/secure/adm/logout" class="w-100">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                        <button class="btn btn-outline-danger w-100 w-lg-auto" type="submit"><i class="fas fa-sign-out-alt me-2"></i>Sair</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    <?php elseif (isset($user) && $user): ?>
    <!-- Header de usuário normal será renderizado abaixo -->
    <?php endif; ?>
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        /* Theme variables are provided by external CSS: theme-light.css and theme-dark.css */

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-family: 'Roboto', sans-serif;
            transition: all 0.3s ease;
        }
        html, body { height: 100%; }
        body { min-height: 100vh; display: flex; flex-direction: column; }
        main { flex: 1 0 auto; }

        .card {
            background-color: var(--card-bg);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        /* Navbar autenticada (escopada por tema) */
        .navbar.navbar-auth { min-height: 64px; padding: .6rem 0; }
        .navbar.navbar-auth .navbar-brand img { width: 28px; height: 28px; }
        html.light .navbar.navbar-auth {
            background: rgba(255, 255, 255, 0.60) !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        html.light .navbar.navbar-auth .navbar-brand, html.light .navbar.navbar-auth .nav-link {
            color: #1e40af !important;
            font-weight: 600;
        }
        html.light .navbar.navbar-auth .nav-link:hover { background: rgba(30, 64, 175, 0.08); border-radius: 8px; }
        html.light .navbar.navbar-auth .navbar-toggler { border: none; color: #1e40af; padding: 0.25rem 0.5rem; }
        html.light .navbar.navbar-auth .navbar-toggler:focus { box-shadow: none; }
        html.light .navbar.navbar-auth .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%2830, 64, 175, 1%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        html.dark .navbar.navbar-auth,
        html.dark-blue .navbar.navbar-auth,
        html.all-black .navbar.navbar-auth {
            background: rgba(17, 24, 39, 0.70) !important;
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35) !important;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        html.dark-blue .navbar.navbar-auth { background: rgba(26, 37, 47, 0.75) !important; }
        html.all-black .navbar.navbar-auth { background: rgba(26, 26, 26, 0.75) !important; }

        html.dark .navbar.navbar-auth .navbar-brand,
        html.dark .navbar.navbar-auth .nav-link,
        html.dark-blue .navbar.navbar-auth .navbar-brand,
        html.dark-blue .navbar.navbar-auth .nav-link,
        html.all-black .navbar.navbar-auth .navbar-brand,
        html.all-black .navbar.navbar-auth .nav-link {
            color: var(--text-primary) !important;
            font-weight: 600;
        }
        html.dark .navbar.navbar-auth .nav-link:hover,
        html.dark-blue .navbar.navbar-auth .nav-link:hover,
        html.all-black .navbar.navbar-auth .nav-link:hover { background: rgba(255, 255, 255, 0.08); border-radius: 8px; }
        html.dark .navbar.navbar-auth .navbar-toggler,
        html.dark-blue .navbar.navbar-auth .navbar-toggler,
        html.all-black .navbar.navbar-auth .navbar-toggler { border: none; color: var(--text-primary); padding: 0.25rem 0.5rem; }
        html.dark .navbar.navbar-auth .navbar-toggler:focus,
        html.dark-blue .navbar.navbar-auth .navbar-toggler:focus,
        html.all-black .navbar.navbar-auth .navbar-toggler:focus { box-shadow: none; }
        html.dark .navbar.navbar-auth .navbar-toggler-icon,
        html.dark-blue .navbar.navbar-auth .navbar-toggler-icon,
        html.all-black .navbar.navbar-auth .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='%23ffffff' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .form-control {
            background-color: var(--bg-secondary);
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .form-control:focus {
            background-color: var(--bg-secondary);
            border-color: var(--secondary-color);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .border {
            border-color: var(--border-color) !important;
        }

        .dropdown-menu {
            background-color: var(--card-bg);
            border-color: var(--border-color);
        }

        .dropdown-item {
            color: var(--text-primary);
        }

        .dropdown-item:hover {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        .navbar .dropdown:hover > .dropdown-menu {
            display: block;
            margin-top: 0;
        }
        .nav-avatar { width: 28px; height: 28px; object-fit: cover; border-radius: 50%; border: 1px solid var(--border-color); }

        /* Mobile spacing improvements for navbar */
        @media (max-width: 576px) {
            .navbar.navbar-auth { min-height: 60px; padding: .5rem 0; }
            .navbar.navbar-auth .navbar-toggler { padding: .35rem .55rem; }
        }

        /* Dropdown – estética aprimorada e integração total com o tema */
        .dropdown-menu {
            border-radius: 12px;
            padding: 8px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            transform-origin: top;
            transition: opacity .15s ease, transform .15s ease;
            border: 1px solid var(--border-color);
        }
        html.light .dropdown-menu { background: rgba(255,255,255,0.96); }
        html.dark-blue .dropdown-menu { background: rgba(17,24,39,0.96); }
        html.all-black .dropdown-menu { background: rgba(17,17,17,0.96); }

        /* Admin Navbar Mobile Optimization */
        .navbar-auth .navbar-collapse {
            background: var(--card-bg, #fff);
            border-radius: 8px;
            margin-top: 0.5rem;
        }
        @media (max-width: 991.98px) {
            .navbar-auth .navbar-collapse {
                padding: 1rem;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .navbar-auth .nav-link {
                padding: 0.75rem 1rem;
                border-radius: 6px;
                margin-bottom: 0.25rem;
                transition: background-color 0.2s;
            }
            .navbar-auth .nav-link:hover {
                background-color: rgba(13, 110, 253, 0.1);
            }
            .navbar-auth .nav-link.active {
                background-color: rgba(13, 110, 253, 0.15);
                font-weight: 600;
            }
            .navbar-auth .btn-outline-danger {
                margin-top: 0.5rem;
            }
        }

        .dropdown-item { 
            color: var(--text-primary);
            border-radius: 8px; 
            padding: .55rem .75rem; 
            display: flex; 
            align-items: center; 
            gap: .5rem;
        }
        html.light .dropdown-item:hover { background: rgba(13,110,253,0.10); color: var(--text-primary); }
        html.dark-blue .dropdown-item:hover,
        html.all-black .dropdown-item:hover { background: rgba(255,255,255,0.08); color: var(--text-primary); }
        .dropdown-item:active { background: var(--secondary-color); color: #fff; }
        .dropdown-divider { border-top-color: var(--border-color); opacity: 1; }
        .dropdown-header { color: var(--text-secondary); font-weight: 600; }

        /* Footer – minimalista e profissional */
        .app-footer { 
            border-top: 1px solid var(--border-color); 
            background: linear-gradient(180deg, transparent, rgba(0,0,0,0.02)); 
            color: var(--text-secondary);
            padding: 22px 0;
        }
        html.dark-blue .app-footer,
        html.all-black .app-footer { background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.00)); }
        .app-footer .footer-links .nav-link { 
            color: var(--text-secondary); 
            padding: .25rem .5rem; 
            border-radius: 6px;
        }
        .app-footer .footer-links .nav-link:hover { background: rgba(127,127,127,0.10); color: var(--text-primary); }
        .app-footer .footer-brand { color: var(--text-primary); }
        .app-footer .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 3px rgba(34,197,94,0.15); display: inline-block; }
        .app-footer .footer-actions .btn { border-radius: 8px; padding: .4rem .75rem; }
        .app-footer .social a { color: var(--text-secondary); text-decoration: none; margin-right: .5rem; }
        .app-footer .social a:hover { color: var(--text-primary); }
        
        /* iOS Safe-area insets for notch devices */
        @supports (padding: max(0px)) {
            .app-footer { padding-bottom: max(22px, env(safe-area-inset-bottom)); }
            .navbar { padding-top: max(.25rem, env(safe-area-inset-top)); }
        }

        /* Footer mobile layout improvements */
        @media (max-width: 576px) {
            .app-footer .container { flex-direction: column !important; align-items: flex-start !important; gap: 10px !important; }
            .app-footer .footer-links .nav { flex-wrap: wrap; gap: 4px 8px; }
            .app-footer .footer-actions { width: 100%; display: flex; justify-content: space-between; }
            .app-footer .footer-actions .btn { flex: 1; font-size: 0.875rem; padding: 0.5rem 0.75rem; }
            .app-footer .footer-brand { flex-wrap: wrap; }
            .app-footer .social { margin-top: 0.5rem !important; }
            .app-footer .social a { font-size: 1.25rem; margin-right: 0.75rem; }
            .app-footer .recaptcha-legal { font-size: 0.75rem; line-height: 1.4; }
        }
        
        /* Footer tablet adjustments */
        @media (min-width: 577px) and (max-width: 768px) {
            .app-footer .footer-links .nav { flex-wrap: wrap; justify-content: center; }
            .app-footer .footer-links .nav-link { padding: 0.25rem 0.4rem; font-size: 0.9rem; }
            .app-footer .footer-actions .btn { font-size: 0.9rem; }
        }
        
        /* Footer extra small mobile */
        @media (max-width: 360px) {
            .app-footer .footer-brand span { font-size: 0.9rem; }
            .app-footer .footer-actions .btn { font-size: 0.8rem; padding: 0.4rem 0.6rem; }
            .app-footer .footer-links .nav-link { font-size: 0.85rem; padding: 0.2rem 0.3rem; }
        }
        
        /* Footer desktop - garantir visibilidade dos botões */
        @media (min-width: 768px) {
            .app-footer .col-md-3 { display: block !important; }
            .app-footer .footer-actions { 
                display: flex !important; 
                visibility: visible !important; 
                opacity: 1 !important;
                position: relative !important;
                z-index: 10 !important;
            }
            .app-footer .footer-actions .btn { 
                display: inline-block !important; 
                visibility: visible !important;
                opacity: 1 !important;
                width: auto !important;
                height: auto !important;
                position: relative !important;
            }
        }
        
        /* Offcanvas Mobile Menu - Tema adaptativo */
        .offcanvas { max-width: 320px; }
        html.light .offcanvas { background-color: #ffffff; color: #000; }
        html.dark-blue .offcanvas { background-color: #001233; color: #fff; }
        html.all-black .offcanvas { background-color: #0a0a0a; color: #fff; }
        
        html.light .offcanvas-header { border-bottom: 1px solid rgba(0,0,0,0.1); }
        html.dark-blue .offcanvas-header,
        html.all-black .offcanvas-header { border-bottom: 1px solid rgba(255,255,255,0.1); }
        
        html.light .offcanvas .nav-link { color: #000; }
        html.dark-blue .offcanvas .nav-link,
        html.all-black .offcanvas .nav-link { color: #fff; }
        
        html.light .offcanvas .nav-link:hover { background-color: rgba(0,0,0,0.05); }
        html.dark-blue .offcanvas .nav-link:hover,
        html.all-black .offcanvas .nav-link:hover { background-color: rgba(255,255,255,0.08); }
        
        html.light .offcanvas .text-muted { color: #6c757d !important; }
        html.dark-blue .offcanvas .text-muted,
        html.all-black .offcanvas .text-muted { color: #adb5bd !important; }
        
        html.light .offcanvas .border-bottom { border-color: rgba(0,0,0,0.1) !important; }
        html.dark-blue .offcanvas .border-bottom,
        html.all-black .offcanvas .border-bottom { border-color: rgba(255,255,255,0.1) !important; }
        
        html.light .offcanvas hr { border-color: rgba(0,0,0,0.1); }
        html.dark-blue .offcanvas hr,
        html.all-black .offcanvas hr { border-color: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php if (isset($user) && $user && !$isAdminAuth && !$isAdminEntry): ?>
    <?php
      // Monta nome curto: primeiro + último
      $displayName = '';
      if (!empty($user['name'])) {
        $normalized = trim(preg_replace('/\s+/', ' ', (string)$user['name']));
        $parts = $normalized !== '' ? explode(' ', $normalized) : [];
        if (count($parts) > 1) {
          $displayName = $parts[0] . ' ' . $parts[count($parts)-1];
        } else {
          $displayName = $parts[0] ?? '';
        }
      }
    ?>
    <?php
      $avatarHeaderUrl = '';
      if (!empty($user['id'])) {
        $root = dirname(__DIR__, 3);
        $docroot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), DIRECTORY_SEPARATOR);
        $publicPath = $docroot !== '' ? $docroot : ($root . DIRECTORY_SEPARATOR . 'public');
        $uploadsDir = $publicPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
        $cands = [
          $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.png',
          $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.jpg',
          $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.jpeg',
          $uploadsDir . DIRECTORY_SEPARATOR . $user['id'] . '.webp',
        ];
        foreach ($cands as $c) { if (is_file($c)) { $mtime = @filemtime($c) ?: time(); $avatarHeaderUrl = '/uploads/avatars/' . basename($c) . '?v=' . $mtime; break; } }
      }
      $avatarFallbackSvg = '/assets/images/user_image.png';
    ?>
    <nav class="navbar navbar-expand-lg navbar-light navbar-auth">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center" href="/app/dashboard">
                <img src="/assets/images/favicon.png" alt="" aria-hidden="true" width="24" height="24" class="me-2 rounded"/>
                Terminal Operebem
            </a>
            
            <!-- Desktop: collapse normal | Mobile: offcanvas -->
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Desktop Menu -->
            <div class="collapse navbar-collapse d-none d-lg-flex" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <?php if (!empty($isDashboard)): ?>
                    <li class="nav-item me-3 d-flex align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#dashboardFiltersModal" title="Filtrar cards">
                            <i class="fas fa-filter"></i>
                        </button>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="/app/dashboard" id="navDashboard" role="button" data-bs-toggle="dropdown">
                            Dashboard
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navDashboard">
                            <li><a class="dropdown-item" href="/app/dashboard">Principal</a></li>
                            <li><a class="dropdown-item" href="/app/dashboard/gold">Ouro</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="/app/indicators/feeling" id="navIndicators" role="button" data-bs-toggle="dropdown">
                            Indicadores
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navIndicators">
                            <li><a class="dropdown-item" href="/app/indicators/feeling">Sentimento</a></li>
                            <li><a class="dropdown-item" href="/app/indicators/fed">Federal Reserve (US)</a></li>
                            <li><a class="dropdown-item" href="/app/indicators/ob-indices">OB Índices</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" onclick="openMarketClockPopup(event)">Relógio de Mercados</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/app/news">Notícias</a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <img src="<?= htmlspecialchars($avatarHeaderUrl ?: $avatarFallbackSvg, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="nav-avatar"/>
                            <span><?= htmlspecialchars($displayName ?: ($user['name'] ?? 'Usuário')) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/app/profile"><i class="fas fa-cog me-2"></i>Perfil</a></li>
                            <li><a class="dropdown-item" href="/dev/subscription/manage"><i class="fas fa-crown me-2"></i>Minha Assinatura</a></li>
                            <li><a class="dropdown-item" href="/sso/start?return=%2Fcourses" target="_blank" rel="noopener noreferrer"><i class="fas fa-graduation-cap me-2"></i>Aulas</a></li>
                            <li><a class="dropdown-item" href="/app/community"><i class="fab fa-discord me-2"></i>Comunidade</a></li>
                            <li><a class="dropdown-item" href="/app/support"><i class="fas fa-headset me-2"></i>Suporte</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#feedbackModal"><i class="fas fa-comment-dots me-2"></i>Enviar Feedback</button></li>
                            <?php if (!empty($isDashboard)): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#" id="exportExcelMenu"><i class="fas fa-file-excel me-2"></i>Exportar Excel</a></li>
                            <li><a class="dropdown-item" href="#" id="toggleSnapshotMenu"><i class="fas fa-camera me-2"></i>Fazer snapshot</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#logoutModal"><i class="fas fa-sign-out-alt me-2"></i>Sair</button></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Mobile Offcanvas Menu -->
    <div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="mobileMenuLabel">
                <img src="/assets/images/favicon.png" alt="" width="24" height="24" class="me-2 rounded"/>
                Menu
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button>
        </div>
        <div class="offcanvas-body">
            <!-- User Info -->
            <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                <img src="<?= htmlspecialchars($avatarHeaderUrl ?: $avatarFallbackSvg, ENT_QUOTES, 'UTF-8') ?>" alt="Avatar" class="rounded-circle me-3" style="width: 48px; height: 48px; object-fit: cover;"/>
                <div>
                    <div class="fw-semibold"><?= htmlspecialchars($displayName ?: ($user['name'] ?? 'Usuário')) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="nav flex-column gap-1">
                <!-- Perfil e Comunidade no topo -->
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/profile">
                    <i class="fas fa-user-cog me-3" style="width: 20px;"></i>
                    <span>Perfil</span>
                </a>
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/dev/subscription/manage">
                    <i class="fas fa-crown me-3" style="width: 20px;"></i>
                    <span>Minha Assinatura</span>
                </a>
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/community">
                    <i class="fab fa-discord me-3" style="width: 20px;"></i>
                    <span>Comunidade</span>
                </a>
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/support">
                    <i class="fas fa-headset me-3" style="width: 20px;"></i>
                    <span>Suporte</span>
                </a>
                <button type="button" class="nav-link px-3 py-2 rounded text-start d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#feedbackModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-comment-dots me-3" style="width: 20px;"></i>
                    <span>Enviar Feedback</span>
                </button>
                
                <hr class="my-2">
                
                <!-- Navegação Principal -->
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/dashboard">
                    <i class="fas fa-chart-line me-3" style="width: 20px;"></i>
                    <span>Dashboard</span>
                </a>
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/dashboard/gold">
                    <i class="fas fa-coins me-3" style="width: 20px;"></i>
                    <span>Dashboard Ouro</span>
                </a>
                
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="/app/news">
                    <i class="fas fa-newspaper me-3" style="width: 20px;"></i>
                    <span>Notícias</span>
                </a>
                
                <!-- Indicadores Section -->
                <div class="mt-2">
                    <div class="px-3 py-2 text-muted small fw-semibold text-uppercase">
                        <i class="fas fa-chart-bar me-2"></i>Indicadores
                    </div>
                    <a class="nav-link px-3 py-2 rounded d-flex align-items-center ms-2" href="/app/indicators/feeling">
                        <i class="fas fa-smile me-3" style="width: 20px;"></i>
                        <span>Sentimento</span>
                    </a>
                    <a class="nav-link px-3 py-2 rounded d-flex align-items-center ms-2" href="/app/indicators/fed">
                        <i class="fas fa-landmark me-3" style="width: 20px;"></i>
                        <span>Federal Reserve</span>
                    </a>
                    <a class="nav-link px-3 py-2 rounded d-flex align-items-center ms-2" href="/app/indicators/ob-indices">
                        <i class="fas fa-chart-area me-3" style="width: 20px;"></i>
                        <span>OB Índices</span>
                    </a>
                </div>
                
                <?php if (!empty($isDashboard)): ?>
                <hr class="my-2">
                <div class="px-3 py-1 text-muted small fw-semibold text-uppercase">
                    <i class="fas fa-tools me-2"></i>Ferramentas
                </div>
                <button type="button" class="nav-link px-3 py-2 rounded text-start d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#dashboardFiltersModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-filter me-3" style="width: 20px;"></i>
                    <span>Filtrar Cards</span>
                </button>
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="#" id="exportExcelMenuMobile">
                    <i class="fas fa-file-excel me-3" style="width: 20px;"></i>
                    <span>Exportar Excel</span>
                </a>
                <a class="nav-link px-3 py-2 rounded d-flex align-items-center" href="#" id="toggleSnapshotMenuMobile">
                    <i class="fas fa-camera me-3" style="width: 20px;"></i>
                    <span>Snapshot</span>
                </a>
                <?php endif; ?>
                
                <hr class="my-2">
                <button type="button" class="nav-link px-3 py-2 rounded text-danger text-start d-flex align-items-center fw-semibold" data-bs-toggle="modal" data-bs-target="#logoutModal" data-bs-dismiss="offcanvas">
                    <i class="fas fa-sign-out-alt me-3" style="width: 20px;"></i>
                    <span>Sair</span>
                </button>
            </nav>
        </div>
    </div>
    <!-- Modal Confirmação de Logout -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="logoutModalLabel"><i class="fas fa-sign-out-alt me-2"></i>Confirmar Logout</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Tem certeza de que deseja sair da sua conta?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <form id="logoutForm" method="POST" action="/logout">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
              <button type="submit" class="btn btn-danger">Sair</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="feedbackModalLabel"><i class="fas fa-comment-dots me-2"></i>Enviar Feedback</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="feedbackForm" method="POST" action="/api/feedback/submit">
            <div class="modal-body">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
              
              <div class="mb-4">
                <label class="form-label fw-bold d-block text-center">Como você avalia nossa plataforma? *</label>
                <input type="hidden" name="rating" id="feedbackRating" required>
                <div class="d-flex justify-content-center align-items-center mb-2">
                  <div id="starRating" class="star-rating d-flex gap-1">
                    <i class="far fa-star" data-rating="1"></i>
                    <i class="far fa-star" data-rating="2"></i>
                    <i class="far fa-star" data-rating="3"></i>
                    <i class="far fa-star" data-rating="4"></i>
                    <i class="far fa-star" data-rating="5"></i>
                  </div>
                </div>
                <div class="text-center">
                  <span id="ratingLabel" class="badge bg-secondary">Selecione uma avaliação</span>
                </div>
              </div>
              
              <style>
                .star-rating {
                  cursor: pointer;
                  white-space: nowrap;
                }
                .star-rating i {
                  color: #d1d5db;
                  transition: all 0.2s ease;
                  cursor: pointer;
                  font-size: 2.5rem;
                  display: inline-block;
                }
                .star-rating i:hover,
                .star-rating i.hover {
                  color: #fbbf24;
                  transform: scale(1.1);
                }
                .star-rating i.selected {
                  color: #f59e0b;
                }
                @media (max-width: 576px) {
                  .star-rating i {
                    font-size: 2rem;
                  }
                }
                @media (max-width: 400px) {
                  .star-rating i {
                    font-size: 1.75rem;
                  }
                }
              </style>

              <div class="mb-3">
                <label class="form-label fw-bold">Conte-nos sobre sua experiência *</label>
                <textarea class="form-control" name="comment" rows="4" required maxlength="1000" placeholder="Compartilhe sua opinião sobre a plataforma..."></textarea>
                <small class="text-muted">Máximo 1000 caracteres</small>
              </div>

              <hr class="my-4">

              <h6 class="mb-3">Perguntas para nos ajudar a melhorar:</h6>

              <div class="mb-3">
                <label class="form-label">1. O que você mais gosta na plataforma?</label>
                <input type="text" class="form-control" name="q1_like_most" maxlength="255" placeholder="Ex: Interface intuitiva, conteúdo de qualidade...">
              </div>

              <div class="mb-3">
                <label class="form-label">2. O que podemos melhorar?</label>
                <input type="text" class="form-control" name="q2_improve" maxlength="255" placeholder="Ex: Adicionar mais recursos, melhorar performance...">
              </div>

              <div class="mb-3">
                <label class="form-label">3. Você recomendaria nossa plataforma para outras pessoas?</label>
                <select class="form-select" name="q3_recommend">
                  <option value="">Selecione...</option>
                  <option value="definitely">Definitivamente sim</option>
                  <option value="probably">Provavelmente sim</option>
                  <option value="maybe">Talvez</option>
                  <option value="probably_not">Provavelmente não</option>
                  <option value="definitely_not">Definitivamente não</option>
                </select>
              </div>

              <div class="mb-3">
                <label class="form-label">4. Qual funcionalidade você gostaria de ver implementada?</label>
                <input type="text" class="form-control" name="q4_feature_request" maxlength="255" placeholder="Ex: App mobile, notificações push...">
              </div>

              <div class="mb-3">
                <label class="form-label">5. Como você classifica a qualidade do suporte?</label>
                <select class="form-select" name="q5_support_quality">
                  <option value="">Selecione...</option>
                  <option value="excellent">Excelente</option>
                  <option value="good">Bom</option>
                  <option value="average">Regular</option>
                  <option value="poor">Ruim</option>
                  <option value="not_used">Não utilizei</option>
                </select>
              </div>

              <div class="alert alert-info small mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Seu feedback é anônimo e nos ajuda a melhorar continuamente nossos serviços. Obrigado!
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane me-2"></i>Enviar Feedback
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?= $content ?? '' ?>
    </main>

    <!-- Footer (variants: admin-login, admin-auth, default public/auth) -->
    <?php if (!empty($footerVariant) && $footerVariant === 'admin-login'): ?>
      <footer class="app-footer footer-admin-login mt-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
          <div class="footer-brand d-flex align-items-center gap-2">
            <img src="/assets/images/favicon.png" alt="" width="20" height="20" class="rounded"/>
            <span class="fw-semibold">Secure Admin</span>
            <span class="text-muted small">&copy; <?= date('Y') ?></span>
          </div>
          <ul class="footer-links nav">
            <li class="nav-item"><a class="nav-link" href="/support">Suporte</a></li>
            <li class="nav-item"><a class="nav-link" href="/privacy">Privacidade</a></li>
          </ul>
          <div class="footer-extra d-flex align-items-center gap-2">
            <span class="status-dot"></span>
            <span class="small text-muted">Status: Online</span>
          </div>
          <!-- recaptcha legal removed in admin-login footer -->
        </div>
      </footer>
    <?php elseif (!empty($footerVariant) && $footerVariant === 'admin-auth'): ?>
      <footer class="app-footer footer-admin-auth mt-4">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
          <div class="footer-brand d-flex align-items-center gap-2">
            <img src="/assets/images/favicon.png" alt="" width="20" height="20" class="rounded"/>
            <span class="fw-semibold">Secure Admin</span>
          </div>
          <ul class="footer-links nav">
            <li class="nav-item"><a class="nav-link" href="/secure/adm/tickets">Tickets</a></li>
            <li class="nav-item"><a class="nav-link" href="/secure/adm/users">Usuários</a></li>
            <li class="nav-item"><a class="nav-link" href="/secure/adm/admins">Administradores</a></li>
          </ul>
          <div class="footer-extra d-flex align-items-center gap-2">
            <span class="small text-muted">© <?= date('Y') ?></span>
          </div>
          <!-- recaptcha legal removed in admin-auth footer -->
        </div>
      </footer>
    <?php elseif (isset($user) && $user): ?>
      <footer class="app-footer footer-auth mt-5">
        <div class="container d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
          <div class="footer-brand d-flex align-items-center gap-2">
            <img src="/assets/images/favicon.png" alt="" width="20" height="20" class="rounded"/>
            <span class="fw-semibold">Terminal Operebem</span>
            <span class="text-muted small">&copy; <?= date('Y') ?></span>
          </div>
          <ul class="footer-links nav">
            <li class="nav-item"><a class="nav-link" href="/app/dashboard">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="/app/news">Notícias</a></li>
            <li class="nav-item"><a class="nav-link" href="/app/support">Suporte</a></li>
          </ul>
          <div class="footer-extra d-flex align-items-center gap-2">
            <span class="status-dot"></span>
            <span class="small text-muted">Status: Online</span>
          </div>
        </div>
      </footer>
    <?php else: ?>
      <footer class="app-footer footer-public mt-0">
        <div class="container py-2">
          <div class="row gy-3 align-items-center">
            <div class="col-12 col-md-4">
              <div class="footer-brand d-flex align-items-center gap-2 mb-1">
                <img src="/assets/images/favicon.png" alt="" width="20" height="20" class="rounded"/>
                <span class="fw-semibold">Terminal Operebem</span>
                <span class="text-muted small">&copy; <?= date('Y') ?></span>
              </div>
              <div class="small text-muted">Plataforma profissional para traders e investidores.</div>
              <div class="social mt-2">
                <a href="https://www.youtube.com/@OPEREBEM" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                <a href="https://www.instagram.com/operebem" target="_blank" rel="noopener" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.tiktok.com/@operebem" target="_blank" rel="noopener" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="https://group.operebem.com.br" target="_blank" rel="noopener" aria-label="Grupo"><i class="fas fa-users"></i></a>
              </div>
            </div>
            <div class="col-12 col-md-5">
              <ul class="footer-links nav justify-content-md-center">
                <li class="nav-item"><a class="nav-link" href="/support">Suporte</a></li>
                <li class="nav-item"><a class="nav-link" href="/terms">Termos de Uso</a></li>
                <li class="nav-item"><a class="nav-link" href="/privacy">Política de Privacidade</a></li>
                <li class="nav-item"><a class="nav-link" href="/risk">Aviso de Risco</a></li>
              </ul>
            </div>
            <div class="col-12 col-md-3">
              <div class="footer-actions d-flex justify-content-md-end gap-2">
                <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#loginModal"><i class="fas fa-sign-in-alt me-1"></i>Entrar</button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerModal"><i class="fas fa-rocket me-1"></i>Começar</button>
              </div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2 mt-3">
            <span class="status-dot"></span>
            <span class="small text-muted">Status: Online</span>
          </div>
          <div class="recaptcha-legal mt-2">Como complemento dos nossos sistemas de proteção, utilizamos a tecnologia reCAPTCHA. Consulte a <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Política de Privacidade</a> e os <a href="https://policies.google.com/terms" target="_blank" rel="noopener">Termos de Serviço</a> do Google.</div>
        </div>
      </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (required by boot.js) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    
    <!-- Custom JS -->
    <script src="/assets/js/app.js?v=<?= time() ?>"></script>
    
    <!-- Page specific scripts -->
    <script src="/assets/js/csrf-fetch.js" defer></script>
    <script src="/assets/js/ws-config.js" defer></script>
    
    <!-- User Timezone Configuration -->
    <script>
        // Injetar timezone do usuário para uso no JavaScript
        window.USER_TIMEZONE = <?= json_encode($user['timezone'] ?? 'America/Sao_Paulo') ?>;
    </script>
    
    <!-- Market Clock Popup Script -->
    <script>
    function openMarketClockPopup(event) {
        event.preventDefault();
        const width = 900;
        const height = 900;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;
        
        window.open(
            '/app/market-clock',
            'MarketClock',
            `width=${width},height=${height},left=${left},top=${top},toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=yes`
        );
    }

    // Feedback Form Handler
    <?php if (isset($user) && $user): ?>
    document.addEventListener('DOMContentLoaded', function() {
        // Sistema de estrelas interativo
        const starRating = document.getElementById('starRating');
        const feedbackRating = document.getElementById('feedbackRating');
        const ratingLabel = document.getElementById('ratingLabel');
        
        if (starRating) {
            const stars = starRating.querySelectorAll('i');
            let currentRating = 0;
            
            const ratingLabels = {
                1: 'Muito Ruim',
                2: 'Ruim',
                3: 'Regular',
                4: 'Bom',
                5: 'Excelente'
            };
            
            const ratingColors = {
                1: 'bg-danger',
                2: 'bg-warning',
                3: 'bg-info',
                4: 'bg-primary',
                5: 'bg-success'
            };
            
            stars.forEach((star, index) => {
                // Hover effect
                star.addEventListener('mouseenter', function() {
                    stars.forEach((s, i) => {
                        if (i <= index) {
                            s.classList.remove('far');
                            s.classList.add('fas', 'hover');
                        } else {
                            s.classList.remove('fas', 'hover');
                            s.classList.add('far');
                        }
                    });
                });
                
                // Click to select
                star.addEventListener('click', function() {
                    const rating = parseInt(this.getAttribute('data-rating'));
                    currentRating = rating;
                    feedbackRating.value = rating;
                    
                    // Update stars
                    stars.forEach((s, i) => {
                        s.classList.remove('hover');
                        if (i < rating) {
                            s.classList.remove('far');
                            s.classList.add('fas', 'selected');
                        } else {
                            s.classList.remove('fas', 'selected');
                            s.classList.add('far');
                        }
                    });
                    
                    // Update label
                    ratingLabel.textContent = ratingLabels[rating];
                    ratingLabel.className = 'badge ' + ratingColors[rating];
                });
            });
            
            // Reset on mouse leave
            starRating.addEventListener('mouseleave', function() {
                stars.forEach((s, i) => {
                    s.classList.remove('hover');
                    if (i < currentRating) {
                        s.classList.remove('far');
                        s.classList.add('fas', 'selected');
                    } else {
                        s.classList.remove('fas', 'selected');
                        s.classList.add('far');
                    }
                });
            });
        }
        
        const feedbackForm = document.getElementById('feedbackForm');
        if (feedbackForm) {
            feedbackForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
                
                try {
                    const formData = new FormData(this);
                    const response = await fetch('/api/feedback/submit', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        // Fechar modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('feedbackModal'));
                        if (modal) modal.hide();
                        
                        // Resetar formulário
                        this.reset();
                        
                        // Mostrar mensagem de sucesso
                        const alertDiv = document.createElement('div');
                        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                        alertDiv.style.zIndex = '9999';
                        alertDiv.innerHTML = `
                            <i class="fas fa-check-circle me-2"></i>${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        `;
                        document.body.appendChild(alertDiv);
                        
                        setTimeout(() => {
                            alertDiv.remove();
                        }, 5000);
                    } else {
                        alert(data.error || 'Erro ao enviar feedback. Tente novamente.');
                    }
                } catch (error) {
                    console.error('Erro ao enviar feedback:', error);
                    alert('Erro ao enviar feedback. Verifique sua conexão e tente novamente.');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }
    });
    <?php endif; ?>
    </script>
    
    <?= $scripts ?? '' ?>
</body>
</html>
