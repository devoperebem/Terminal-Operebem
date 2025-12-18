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

if (!function_exists('dev_script_tag')) {
    /**
     * Retorna tag de script com caminho correto baseado no ambiente
     * Em ambiente dev, inclui o JavaScript inline para evitar problemas de MIME type
     * 
     * @param string $scriptName Nome do arquivo JS (ex: 'gold-dashboard.js')
     * @return string Tag <script> completa
     */
    function dev_script_tag(string $scriptName): string
    {
        if (is_dev_environment()) {
            // Caminho para versão dev
            $devPath = dirname(__DIR__, 2) . '/public/assets/dev/js/' . $scriptName;
            
            if (file_exists($devPath)) {
                // Incluir inline para garantir que funcione
                $content = file_get_contents($devPath);
                return '<script>' . $content . '</script>';
            }
        }
        
        // Fallback para versão de produção normal
        $v = time();
        return '<script src="/assets/js/' . htmlspecialchars($scriptName) . '?v=' . $v . '"></script>';
    }
}
