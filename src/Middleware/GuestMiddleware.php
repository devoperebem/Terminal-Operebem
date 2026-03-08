<?php

namespace App\Middleware;

use App\Services\AuthService;

class GuestMiddleware
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function handle(): bool
    {
        if ($this->authService->isAuthenticated()) {
            // Redirecionar para dashboard (rota canônica)
            header('Location: /dashboard');
            exit;
        }

        return true;
    }
}
