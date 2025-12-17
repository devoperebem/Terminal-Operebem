<?php

/**
 * Helpers para ambiente de desenvolvimento (/dev/)
 * Sistema simples de fallback para views e assets
 */

if (!function_exists('is_dev_environment')) {
    /**
     * Verifica se está em ambiente de desenvolvimento (/dev/)
     * @return bool
     */
    function is_dev_environment(): bool
    {
        return defined('IS_DEV_ENVIRONMENT') && IS_DEV_ENVIRONMENT === true;
    }
}

if (!function_exists('get_dev_view_path')) {
    /**
     * Retorna o caminho correto da view baseado no ambiente
     * Se estiver em ambiente dev e existir versão dev, retorna versão dev
     * Caso contrário, retorna versão de produção
     * 
     * @param string $viewPath Caminho relativo da view (ex: 'app/dashboard-gold')
     * @return string Caminho completo da view ou null se não encontrar
     */
    function get_dev_view_path(string $viewPath): ?string
    {
        $basePath = dirname(__DIR__) . '/Views/';
        
        // Se estiver em ambiente dev, tenta versão dev primeiro
        if (is_dev_environment()) {
            $devPath = $basePath . 'dev/' . $viewPath . '.php';
            if (file_exists($devPath)) {
                return $devPath;
            }
        }
        
        // Fallback para versão de produção
        $prodPath = $basePath . $viewPath . '.php';
        if (file_exists($prodPath)) {
            return $prodPath;
        }
        
        return null;
    }
}
