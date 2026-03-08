<?php

namespace App\Controllers;

use App\Core\Database;

class DashboardController extends BaseController
{
    public function index(): void
    {
        // Buscar dados das cotações do PostgreSQL
        $quotes = $this->getQuotesData();
        
        // Dados do usuário atual
        $user = $this->authService->getCurrentUser();
        
        $wsConfig = [
            'url' => $_ENV['WS_PROXY_URL'] ?? 'ws://vps1.operebem.com:8765',
            'key' => $_ENV['WS_PROXY_KEY'] ?? ''
        ];

        $this->view('app/dashboard', [
            'quotes' => $quotes,
            'user' => $user,
            'ws_config' => $wsConfig
        ]);
    }

    public function gold(): void
    {
        $user = $this->authService->getCurrentUser();
        $wsConfig = [
            'url' => $_ENV['WS_PROXY_URL'] ?? 'ws://vps1.operebem.com:8765',
            'key' => $_ENV['WS_PROXY_KEY'] ?? ''
        ];

        $this->view('app/dashboard-gold', [
            'user' => $user,
            'ws_config' => $wsConfig
        ]);
    }

    private function getQuotesData(): array
    {
        try {
            $quotes = Database::fetchAll(
                "SELECT id_api, code, apelido, nome FROM dicionario WHERE ativo = 'S' ORDER BY order_tabela LIMIT 10",
                [],
                'quotes'
            );
            
            return $quotes;
        } catch (\Exception $e) {
            $this->app->logger()->error('Erro ao buscar cotações: ' . $e->getMessage());
            return [];
        }
    }
}
