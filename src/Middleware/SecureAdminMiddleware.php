<?php

namespace App\Middleware;

use App\Services\JwtService;
use App\Core\Application;

class SecureAdminMiddleware
{
    public function handle(): bool
    {
        try {
            $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
            $wantsJson = stripos($accept, 'application/json') !== false || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $token = (string)($_COOKIE['adm_at'] ?? '');
            if ($token === '') {
                if ($wantsJson) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'admin auth required']); return false; }
                header('Location: /secure/adm/login', true, 302);
                return false;
            }
            $jwt = new JwtService();
            $claims = $jwt->decode($token);
            $role = (string)($claims['role'] ?? '');
            $typ = (string)($claims['typ'] ?? '');
            if ($role !== 'admin' || $typ !== 'access') {
                if ($wantsJson) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'forbidden']); return false; }
                header('Location: /secure/adm/login?error=session', true, 302);
                return false;
            }
            return true;
        } catch (\Throwable $t) {
            try { Application::getInstance()->logger()->warning('SecureAdminMiddleware error: '.$t->getMessage()); } catch (\Throwable $__) {}
            http_response_code(401);
            header('Location: /secure/adm/login', true, 302);
            return false;
        }
    }
}

