<?php
/**
 * Front controller da raiz
 * Inclui o index do diretório public para manter a URL base em /
 */

// Não emitir redirecionamento: apenas inclui o front controller do public
require __DIR__ . '/public/index.php';
