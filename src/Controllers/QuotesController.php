<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\QuotesService;

class QuotesController
{
    private QuotesService $quotesService;

    public function __construct()
    {
        $this->quotesService = new QuotesService();
    }

    public function boot()
    {
        try {
            $app = Application::getInstance();
            $action = $_POST['acao'] ?? '';

            if ($action === 'listar') {
                $quotes = $this->quotesService->getAllQuotes();
                $san = array_map([$this, 'sanitizeRow'], is_array($quotes) ? $quotes : []);
                try { $app->logger()->info('[QUOTES] listar', ['count' => is_array($quotes) ? count($quotes) : 0, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']); } catch (\Throwable $t) {}
                echo json_encode(['data' => $san, 'error' => '']);
                return;
            }

            if ($action === 'listar_complemento') {
                // Incluir também origem 'barchart' (FEF1!/FEF2!) além de cnbc e yahoo
                $quotes = $this->quotesService->getActiveByOrigins(['cnbc','yahoo','barchart']);
                $san = array_map([$this, 'sanitizeRow'], is_array($quotes) ? $quotes : []);
                try { $app->logger()->info('[QUOTES] listar_complemento', ['count' => is_array($quotes) ? count($quotes) : 0, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']); } catch (\Throwable $t) {}
                echo json_encode(['data' => $san, 'error' => '']);
                return;
            }

            if ($action === 'update') {
                $data = json_decode($_POST['arr_dados'] ?? '[]', true);
                $result = $this->quotesService->updateQuotes($data);
                try { $app->logger()->info('[QUOTES] update', ['items' => is_array($data) ? count($data) : 0, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']); } catch (\Throwable $t) {}
                echo json_encode($result);
                return;
            }

            echo json_encode(['error' => 'Ação não reconhecida']);

        } catch (\Exception $e) {
            try { Application::getInstance()->logger()->error('[QUOTES] boot error: ' . $e->getMessage()); } catch (\Throwable $t) {}
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function sanitizeRow(array $row): array
    {
        $allowed = [
            'id_api','code','apelido','nome','grupo','order_tabela',
            'last','pc','pcp','last_close','high','low','bid','ask','last_numeric','last_dir','pc_col',
            'turnover','turnover_numeric',
            'timestamp','time_utc','status_mercado','status_hr',
            'icone_bandeira','bandeira','bolsa'
        ];
        $san = [];
        foreach ($allowed as $k) {
            if (array_key_exists($k, $row)) $san[$k] = $row[$k];
        }
        return $san;
    }

    /**
     * Endpoint público para a home listar cotações.
     * Não permite update, apenas retorna a lista.
     */
    public function listarPublic()
    {
        try {
            $all = $this->quotesService->getAllQuotes();
            $all = is_array($all) ? $all : [];
            $byOrder = function($a, $b){
                $oa = isset($a['order_tabela']) ? (int)$a['order_tabela'] : 9999;
                $ob = isset($b['order_tabela']) ? (int)$b['order_tabela'] : 9999;
                return $oa <=> $ob;
            };
            $isCommodity = function($g){ $g = (string)$g; return (str_contains($g, 'metais') || str_contains($g, 'energia') || str_contains($g, 'agricola')); };
            $commodities = array_values(array_filter($all, fn($d)=> $isCommodity($d['grupo'] ?? '')));
            usort($commodities, $byOrder);
            $commodities = array_slice($commodities, 0, 5);
            $adrs = array_values(array_filter($all, fn($d)=> str_contains((string)($d['grupo'] ?? ''), 'adrs')));
            usort($adrs, $byOrder);
            $adrs = array_slice($adrs, 0, 5);
            $subset = array_merge($commodities, $adrs);
            $san = array_map([$this, 'sanitizeRow'], $subset);
            echo json_encode(['data' => $san, 'error' => '']);
        } catch (\Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
