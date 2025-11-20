<?php

namespace App\Middleware;

/**
 * SecurityHeadersMiddleware
 * 
 * Adiciona headers de segurança essenciais para proteção contra:
 * - Clickjacking (X-Frame-Options)
 * - MIME Sniffing (X-Content-Type-Options)
 * - XSS (X-XSS-Protection, CSP)
 * - Man-in-the-Middle (HSTS)
 * - Information Leakage (Referrer-Policy)
 * - Unwanted APIs (Permissions-Policy)
 * 
 * Objetivo: Alcançar nota A+ em https://securityheaders.com/
 */
class SecurityHeadersMiddleware
{
    /**
     * Handle the request and add security headers
     */
    public function handle($request, $next)
    {
        // Continue with the request
        $response = $next($request);
        
        // Add security headers
        $this->addSecurityHeaders();
        
        return $response;
    }
    
    /**
     * Add all security headers
     */
    private function addSecurityHeaders(): void
    {
        // X-Frame-Options: Previne clickjacking
        // DENY = não permite iframe em nenhum domínio
        if (!headers_sent()) {
            header("X-Frame-Options: DENY", false);
        }
        
        // X-Content-Type-Options: Previne MIME sniffing
        // nosniff = navegador respeita o Content-Type declarado
        if (!headers_sent()) {
            header("X-Content-Type-Options: nosniff", false);
        }
        
        // X-XSS-Protection: Proteção XSS legada (ainda útil para navegadores antigos)
        // 1; mode=block = ativa proteção e bloqueia página se detectar XSS
        if (!headers_sent()) {
            header("X-XSS-Protection: 1; mode=block", false);
        }
        
        // Referrer-Policy: Controla informações de referrer enviadas
        // strict-origin-when-cross-origin = envia origin apenas em cross-origin HTTPS
        if (!headers_sent()) {
            header("Referrer-Policy: strict-origin-when-cross-origin", false);
        }
        
        // Permissions-Policy: Desabilita APIs perigosas
        // Bloqueia: geolocation, microphone, camera, payment, usb, etc
        if (!headers_sent()) {
            header("Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), speaker=()", false);
        }
        
        // HSTS: Força HTTPS por 1 ano (incluindo subdomínios)
        // CRÍTICO: Só adicionar se estiver em HTTPS
        if ($this->isHttps()) {
            if (!headers_sent()) {
                // max-age=31536000 = 1 ano
                // includeSubDomains = aplica a todos subdomínios
                // preload = permite inclusão na lista HSTS preload dos navegadores
                header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload", false);
            }
        }
        
        // Content-Security-Policy (CSP): Previne XSS e injection attacks
        $this->addCSPHeader();
    }
    
    /**
     * Add Content-Security-Policy header
     */
    private function addCSPHeader(): void
    {
        if (headers_sent()) {
            return;
        }
        
        // Gerar nonce único para scripts inline
        $nonce = $this->getOrCreateNonce();
        
        // Definir política CSP
        $csp = [
            // default-src: fallback para todas as diretivas não especificadas
            "default-src 'self'",
            
            // script-src: fontes permitidas para JavaScript
            // 'self' = mesmo domínio
            // 'nonce-xxx' = scripts inline com nonce específico
            // cdn.jsdelivr.net = Bootstrap, FontAwesome
            "script-src 'self' 'nonce-{$nonce}' cdn.jsdelivr.net https://cdn.jsdelivr.net",
            
            // style-src: fontes permitidas para CSS
            // 'unsafe-inline' = necessário para Bootstrap e estilos inline
            "style-src 'self' 'unsafe-inline' cdn.jsdelivr.net https://cdn.jsdelivr.net fonts.googleapis.com",
            
            // font-src: fontes permitidas para web fonts
            "font-src 'self' cdn.jsdelivr.net https://cdn.jsdelivr.net fonts.gstatic.com data:",
            
            // img-src: fontes permitidas para imagens
            // data: = imagens base64
            // https: = qualquer imagem HTTPS (avatars, etc)
            "img-src 'self' data: https: http://localhost:* i.pravatar.cc",
            
            // connect-src: fontes permitidas para AJAX, WebSocket, EventSource
            "connect-src 'self'",
            
            // frame-ancestors: quem pode embedar esta página em iframe
            // 'none' = ninguém (equivalente a X-Frame-Options: DENY)
            "frame-ancestors 'none'",
            
            // base-uri: restringe URLs que podem ser usadas em <base>
            "base-uri 'self'",
            
            // form-action: restringe URLs para onde forms podem submeter
            "form-action 'self'",
            
            // upgrade-insecure-requests: força upgrade de HTTP para HTTPS
            "upgrade-insecure-requests",
            
            // block-all-mixed-content: bloqueia conteúdo HTTP em página HTTPS
            "block-all-mixed-content"
        ];
        
        // Adicionar header
        header("Content-Security-Policy: " . implode("; ", $csp), false);
    }
    
    /**
     * Get or create CSP nonce
     */
    private function getOrCreateNonce(): string
    {
        // Verificar se já existe nonce na request
        if (isset($_SERVER['CSP_NONCE'])) {
            return $_SERVER['CSP_NONCE'];
        }
        
        // Gerar novo nonce (32 bytes = 64 caracteres hex)
        $nonce = bin2hex(random_bytes(32));
        
        // Armazenar para uso em views
        $_SERVER['CSP_NONCE'] = $nonce;
        
        return $nonce;
    }
    
    /**
     * Check if connection is HTTPS
     */
    private function isHttps(): bool
    {
        // Verificar HTTPS via $_SERVER
        if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        
        // Verificar porta 443
        if (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        
        // Verificar proxy headers (load balancer, Cloudflare, etc)
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            return true;
        }
        
        if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') {
            return true;
        }
        
        return false;
    }
}
