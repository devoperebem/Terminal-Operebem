<?php

namespace App\Middleware;

use App\Core\Application;
use App\Services\AdminAuthService;

class DebugOnlyMiddleware
{
    private AdminAuthService $adminAuth;

    public function __construct()
    {
        $this->adminAuth = new AdminAuthService();
    }

    public function handle(): bool
    {
        $app = Application::getInstance();
        $isDebug = (bool) $app->config('app.debug');

        // Allow when APP_DEBUG=true or authenticated admin
        if ($isDebug || $this->adminAuth->isAuthenticated()) {
            return true;
        }

        // In production (non-debug) and non-admin, hide diagnostics
        http_response_code(404);
        echo 'Not Found';
        return false;
    }
}
