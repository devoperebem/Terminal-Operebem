<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\AuthService;

abstract class BaseController
{
    protected Application $app;
    protected AuthService $authService;

    public function __construct()
    {
        $this->app = Application::getInstance();
        $this->authService = new AuthService();
    }

    protected function view(string $view, array $data = []): void
    {
        // Adicionar dados globais
        $data['app'] = $this->app;
        if (!isset($data['user'])) {
            $data['user'] = $this->authService->getCurrentUser();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $data['csrf_token'] = $_SESSION['csrf_token'];
        
        // Extrair variáveis para o escopo da view
        extract($data);
        
        // Tentar obter caminho da view com fallback automático (dev -> prod)
        $viewPath = get_dev_view_path($view);
        
        // Fallback para caminho tradicional se helper não encontrar
        if ($viewPath === null) {
            $viewPath = dirname(__DIR__, 2) . "/src/Views/{$view}.php";
        }
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View '{$view}' não encontrada");
        }
        
        include $viewPath;
    }

    protected function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function validateCsrf(): bool
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        return !empty($token) && !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    protected function regenerateCsrf(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
