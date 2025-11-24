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

        $goldData = $this->getGoldData();

        $this->view('app/dashboard-gold', [
            'user' => $user,
            'ws_config' => $wsConfig,
            'goldData' => $goldData
        ]);
    }

    private function getGoldData(): array
    {
        $host = '147.93.35.184';
        $db   = 'operebem_quotes';
        $user = 'operebem_services';
        $pass = 'USR_JUIlYE32gI1vPuNM';
        $port = 5432;

        $dsn = "pgsql:host=$host;port=$port;dbname=$db";
        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new \PDO($dsn, $user, $pass, $options);
            $stmt = $pdo->query("SELECT * FROM v_gold_analysis_complete ORDER BY code");
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            $this->app->logger()->error('Erro ao buscar dados do ouro: ' . $e->getMessage());
            return [];
        }
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
