<?php
/**
 * Servidor de JavaScript para ambiente /dev/
 * Serve arquivos .js com Content-Type correto
 */

// Obter nome do arquivo solicitado
$file = $_GET['f'] ?? '';

// Validar nome do arquivo (apenas letras, números, hífen e underscore)
if (!preg_match('/^[a-zA-Z0-9_-]+\.js$/', $file)) {
    http_response_code(400);
    die('Invalid file name');
}

// Caminho completo do arquivo
$filePath = __DIR__ . '/js/' . $file;

// Verificar se arquivo existe
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Definir headers corretos
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: public, max-age=3600');
header('X-Content-Type-Options: nosniff');

// Ler e enviar conteúdo
readfile($filePath);
