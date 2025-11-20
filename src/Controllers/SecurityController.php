<?php

namespace App\Controllers;

class SecurityController extends BaseController
{
    // Retorna o token CSRF atual; se inexistente, gera um novo
    public function token(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $this->regenerateCsrf();
        }
        // Evitar cache em proxies/navegador
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Expires: 0');
        $this->json(['token' => $_SESSION['csrf_token']]);
    }

    // Recebe relatórios de violações CSP (Content-Security-Policy)
    public function cspReport(): void
    {
        // Aceitar application/csp-report ou application/json
        $raw = file_get_contents('php://input') ?: '';
        $data = null;
        try { $data = json_decode($raw, true); } catch (\Throwable $t) { $data = null; }
        if (!is_array($data)) { $data = ['raw' => $raw]; }
        // Normalizar payload (alguns navegadores enviam em 'csp-report')
        if (isset($data['csp-report']) && is_array($data['csp-report'])) {
            $data = $data['csp-report'];
        }
        // Enriquecer com metadados de request
        $entry = [
            'ts' => date('c'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'path' => $_SERVER['REQUEST_URI'] ?? '',
            'report' => $data,
        ];
        try {
            $root = dirname(__DIR__, 2);
            $dir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'csp';
            if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
            $file = $dir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log';
            @file_put_contents($file, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
        } catch (\Throwable $t) { /* ignore */ }
        http_response_code(204);
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}
