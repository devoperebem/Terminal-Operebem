<?php

namespace App\Middleware;

/**
 * DevEnvironmentMiddleware
 * 
 * Detecta se a URL atual está em ambiente de desenvolvimento (/dev/* paths)
 * e configura flags globais para permitir comportamento diferenciado.
 * 
 * Quando detectado /dev/ no início da URI:
 * - Define constante global IS_DEV_ENVIRONMENT = true
 * - Armazena a URI original sem o prefixo /dev/ para roteamento
 * - Permite que views e controllers detectem o ambiente
 */
class DevEnvironmentMiddleware implements MiddlewareInterface
{
    public function handle()
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Detecta se a URL começa com /dev/
        if (str_starts_with($path, '/dev/')) {
            // Define constante global para ambiente de desenvolvimento
            if (!defined('IS_DEV_ENVIRONMENT')) {
                define('IS_DEV_ENVIRONMENT', true);
            }
            
            // Remove o prefixo /dev/ da URI para processamento normal do router
            $cleanPath = substr($path, 4); // Remove '/dev'
            
            // Armazena a URI original e a limpa
            $_SERVER['ORIGINAL_REQUEST_URI'] = $_SERVER['REQUEST_URI'];
            $_SERVER['REQUEST_URI'] = $cleanPath . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? '?' . $_SERVER['QUERY_STRING'] : '');
            
            // Adiciona header para fácil identificação no browser
            header('X-Dev-Environment: true');
        } else {
            // Produção
            if (!defined('IS_DEV_ENVIRONMENT')) {
                define('IS_DEV_ENVIRONMENT', false);
            }
        }
        
        return true;
    }
}
