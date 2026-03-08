<?php
// PHP Built-in server router file
// Serve arquivos estáticos diretamente; caso contrário, encaminha para index.php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$requested = __DIR__ . $uri;

if ($uri !== '/' && file_exists($requested) && !is_dir($requested)) {
    return false; // Deixa o PHP servir o arquivo estático
}

require __DIR__ . '/index.php';
