<?php

namespace App\Middleware;

use App\Services\AuthService;
use App\Core\Application;

class AuthMiddleware
{
    public function handle(): bool
    {
        try {
            $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
            $wantsJson = stripos($accept, 'application/json') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $auth = new AuthService();
            if ($auth->isAuthenticated()) { return true; }
            // Se possui sessão de admin, empurre para área admin para evitar header duplicado
            if (!empty($_COOKIE['adm_at'])) {
                if ($wantsJson) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'admin session present']); return false; }
                header('Location: /secure/adm/index', true, 302);
                return false;
            }
            if ($wantsJson) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'auth required']); return false; }
            header('Location: /', true, 302);
            return false;
        } catch (\Throwable $t) {
            try { Application::getInstance()->logger()->warning('AuthMiddleware error: '.$t->getMessage()); } catch (\Throwable $__) {}
            header('Location: /', true, 302);
            return false;
        }
    }
}

