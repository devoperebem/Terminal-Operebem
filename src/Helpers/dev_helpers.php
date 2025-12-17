<?php

/**
 * Helper para detectar e trabalhar com ambiente de desenvolvimento
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

if (!function_exists('dev_view_exists')) {
    /**
     * Verifica se existe uma versão de desenvolvimento da view
     * @param string $viewPath Caminho relativo da view (ex: 'app/dashboard-gold')
     * @return bool
     */
    function dev_view_exists(string $viewPath): bool
    {
        $basePath = __DIR__ . '/../Views/dev/';
        $fullPath = $basePath . $viewPath . '.php';
        return file_exists($fullPath);
    }
}

if (!function_exists('get_view_path')) {
    /**
     * Retorna o caminho correto da view baseado no ambiente
     * Se estiver em ambiente dev e existir versão dev, retorna versão dev
     * Caso contrário, retorna versão de produção
     * 
     * @param string $viewPath Caminho relativo da view (ex: 'app/dashboard-gold')
     * @return string Caminho completo para incluir a view
     */
    function get_view_path(string $viewPath): string
    {
        $basePath = __DIR__ . '/../Views/';
        
        // Se estiver em ambiente dev, tenta versão dev primeiro
        if (is_dev_environment()) {
            $devPath = $basePath . 'dev/' . $viewPath . '.php';
            if (file_exists($devPath)) {
                return $devPath;
            }
        }
        
        // Fallback para versão de produção
        return $basePath . $viewPath . '.php';
    }
}

if (!function_exists('dev_asset')) {
    /**
     * Retorna o caminho do asset baseado no ambiente
     * Se estiver em ambiente dev e existir versão dev do asset, retorna versão dev
     * Caso contrário, retorna versão de produção
     * 
     * @param string $assetPath Caminho relativo do asset (ex: 'js/gold-dashboard.js')
     * @return string URL do asset
     */
    function dev_asset(string $assetPath): string
    {
        if (is_dev_environment()) {
            $devAssetPath = '/assets/dev/' . $assetPath;
            $fullPath = __DIR__ . '/../../public' . $devAssetPath;
            if (file_exists($fullPath)) {
                return $devAssetPath;
            }
        }
        
        return '/assets/' . $assetPath;
    }
}
