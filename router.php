<?php
/**
 * Router para servidor PHP embutido (php -S)
 * Serve arquivos estáticos corretamente da pasta public/
 * 
 * IMPORTANTE: Este arquivo só deve ser usado em ambiente LOCAL
 * Em produção, o Apache/Nginx usa o .htaccess
 * 
 * NÃO COMMITAR ESTE ARQUIVO NO GIT (adicionar ao .gitignore)
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file_path = __DIR__ . '/public' . $uri;

// Se o arquivo existe em public/, servir diretamente com content-type correto
if ($uri !== '/' && file_exists($file_path) && !is_dir($file_path)) {
    // Determinar content-type
    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
    $content_types = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject'
    ];
    
    $content_type = $content_types[$extension] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $content_type);
    header('Content-Length: ' . filesize($file_path));
    readfile($file_path);
    exit;
}

// Caso contrário, redirecionar para public/index.php
require_once __DIR__ . '/public/index.php';
