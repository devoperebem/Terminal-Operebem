<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;

class TestController extends BaseController
{
    public function ping(): void
    {
        $this->json([
            'ok' => true,
            'time' => date('c'),
            'app_debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
            'is_https' => $this->app->isHttps(),
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? null,
            'php_version' => PHP_VERSION,
        ]);
    }

    public function page(): void
    {
        $file = dirname(__DIR__, 2) . '/public/testes/index.html';
        if (is_file($file)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($file);
            return;
        }
        http_response_code(404);
        echo 'Not Found';
    }

    public function assetJs(): void
    {
        $file = dirname(__DIR__, 2) . '/public/testes/testes.js';
        if (is_file($file)) {
            header('Content-Type: application/javascript; charset=UTF-8');
            readfile($file);
            return;
        }
        http_response_code(404);
        echo 'Not Found';
    }

    public function db(): void
    {
        $out = [
            'mysql' => [ 'ok' => false, 'error' => null ],
            'quotes' => [ 'ok' => false, 'error' => null ],
        ];

        try {
            $stmt = Database::query('SELECT 1 AS n', [], 'mysql');
            $row = $stmt->fetch();
            $out['mysql']['ok'] = isset($row['n']);
        } catch (\Throwable $e) {
            $out['mysql']['error'] = $e->getMessage();
        }

        try {
            $stmt = Database::query('SELECT 1 AS n', [], 'quotes');
            $row = $stmt->fetch();
            $out['quotes']['ok'] = isset($row['n']);
        } catch (\Throwable $e) {
            $out['quotes']['error'] = $e->getMessage();
        }

        $this->json($out);
    }

    public function session(): void
    {
        if (!isset($_SESSION['__test'])) {
            $_SESSION['__test'] = bin2hex(random_bytes(8));
        }
        $this->json([
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'session_id' => session_id(),
            'session_value' => $_SESSION['__test'] ?? null,
            'has_access_token_cookie_server' => isset($_COOKIE['access_token']),
            'csrf_token' => $_SESSION['csrf_token'] ?? null,
        ]);
    }

    public function csrfCheck(): void
    {
        // Só chega aqui se CsrfMiddleware validar
        $this->json(['ok' => true]);
    }
}
