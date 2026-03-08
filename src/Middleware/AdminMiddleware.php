<?php

namespace App\Middleware;

use App\Services\AdminAuthService;

class AdminMiddleware
{
    private AdminAuthService $adminAuth;

    public function __construct()
    {
        $this->adminAuth = new AdminAuthService();
    }

    public function handle(): bool
    {
        if (!$this->adminAuth->isAuthenticated()) {
            header('Location: /admin/login');
            exit;
        }
        return true;
    }
}
