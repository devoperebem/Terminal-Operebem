<?php

namespace App\Controllers;

use App\Services\MarketClockService;

/**
 * Controller para Market Clock API
 */
class MarketClockController extends BaseController
{
    private MarketClockService $clockService;
    
    public function __construct()
    {
        parent::__construct();
        $this->clockService = new MarketClockService();
    }
    
    /**
     * GET /api/market-clock
     * Retorna status atual de todas as bolsas principais
     */
    public function index(): void
    {
        try {
            $exchanges = $this->clockService->getMainExchanges();
            
            $this->json([
                'success' => true,
                'data' => $exchanges,
                'count' => count($exchanges),
                'timestamp' => time()
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao buscar status das bolsas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/market-clock/all
     * Retorna todas as bolsas com status
     */
    public function all(): void
    {
        try {
            $exchanges = $this->clockService->getExchangesWithStatus();
            
            $this->json([
                'success' => true,
                'data' => $exchanges,
                'count' => count($exchanges),
                'timestamp' => time()
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao buscar bolsas',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/market-clock/{code}
     * Retorna status de bolsa específica
     */
    public function show(string $code): void
    {
        try {
            $exchange = $this->clockService->getExchange($code);
            
            if (!$exchange) {
                $this->json([
                    'success' => false,
                    'error' => 'Bolsa não encontrada'
                ], 404);
                return;
            }
            
            $exchange['calculated_status'] = $this->clockService->calculateStatus($exchange);
            
            $this->json([
                'success' => true,
                'data' => $exchange
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao buscar bolsa',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/market-clock/update-statuses
     * Atualiza status de todas as bolsas (admin apenas, ou cron)
     */
    public function updateStatuses(): void
    {
        try {
            $updated = $this->clockService->updateAllStatuses();
            
            $this->json([
                'success' => true,
                'message' => 'Status atualizados',
                'updated' => $updated
            ]);
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Erro ao atualizar status',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
