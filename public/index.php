<?php
// Não enviar nenhum output antes da inicialização
ob_start();

// Security Headers - Objetivo: A+ em securityheaders.com
if (!headers_sent()) {
    // CSP Nonce para scripts inline
    $nonce = bin2hex(random_bytes(32));
    $_SERVER['CSP_NONCE'] = $nonce;
    
    // Content Security Policy (enforced)
    $csp = "default-src 'self'; "
         . "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://www.googletagmanager.com https://www.google.com https://www.gstatic.com https://s3.tradingview.com https://code.jquery.com https://cdn.sheetjs.com; "
         . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://use.typekit.net; "
         . "img-src 'self' data: blob: https: http://localhost:* i.pravatar.cc; "
         . "font-src 'self' data: https://fonts.gstatic.com https://cdn.jsdelivr.net https://use.typekit.net; "
         . "connect-src 'self' https://news-operebem.up.railway.app https://www.google-analytics.com https://www.googletagmanager.com https://www.google.com https://cdn.jsdelivr.net https://servicodados.ibge.gov.br wss://vps1.operebem.com; "
         . "frame-src 'self' https://www.google.com https://www.tradingview-widget.com https://s.tradingview.com https://sslecal2.investing.com; "
         . "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; upgrade-insecure-requests; block-all-mixed-content";
    header('Content-Security-Policy: ' . $csp);
    
    // X-Content-Type-Options: Previne MIME sniffing
    header('X-Content-Type-Options: nosniff');
    
    // X-Frame-Options: Previne clickjacking (DENY para máxima segurança)
    header('X-Frame-Options: DENY');
    
    // X-XSS-Protection: Proteção XSS legada (ainda útil)
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer-Policy: Controla informações de referrer
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions-Policy: Desabilita APIs perigosas
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), speaker=()');
    
    // HSTS: Força HTTPS por 1 ano (CRÍTICO para A+)
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers/html_helpers.php';
require_once __DIR__ . '/../src/Helpers/dev_helpers.php';

use App\Core\Application;
use App\Core\Database;

try {
    // Inicializar aplicação (isso vai configurar a sessão)
    $app = Application::getInstance();
    
    // Configurar banco de dados
    Database::init($app->config('database'));
    
    // Carregar e executar rotas
    $router = require dirname(__DIR__) . '/routes/web.php';
    $router->resolve();
    
} catch (Exception $e) {
    // Log do erro
    if (isset($app)) {
        $app->logger()->error('Erro fatal: ' . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Mostrar erro em desenvolvimento ou página genérica em produção
    if (($_ENV['APP_DEBUG'] ?? false) === 'true') {
        echo "<pre>Erro: " . $e->getMessage() . "\n\n" . $e->getTraceAsString() . "</pre>";
    } else {
        http_response_code(500);
        echo "Erro interno do servidor. Tente novamente mais tarde.";
    }
}

// Enviar output buffer
ob_end_flush();
