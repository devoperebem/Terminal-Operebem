<?php

namespace App\Controllers;

use App\Services\StatisticsService;
use App\Services\QuotesService;

class HomeController extends BaseController
{
    public function index(): void
    {
        // Se usuário já está logado, redirecionar para dashboard (rota canônica)
        if ($this->authService->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        // Gerar CSRF token se não existir
        if (!isset($_SESSION['csrf_token'])) {
            $this->regenerateCsrf();
        }

        // Obter estatísticas dinâmicas
        $statistics = [
            'user_count' => StatisticsService::getUserCount(),
            'active_assets' => StatisticsService::getActiveAssets(),
            'uptime' => StatisticsService::getUptime(),
            'data_processed' => StatisticsService::getDataProcessed()
        ];

        // Buscar cotações para o Hero
        $heroQuote = null;
        $heroAssets = [];
        try {
            $quotesService = new QuotesService();
            $all = $quotesService->getAllQuotes();
            // Prioridades (alinhado com o antigo): índices globais + DXY + BTC
            // Obs.: usamos tanto code quanto id_api, pois alguns itens podem estar no formato "BTC/USD" ou similar
            $preferred = ['US500', 'IBOV', 'DE40', 'JP225', 'DXY', 'BTC/USD'];
            foreach ($preferred as $code) {
                foreach ($all as $q) {
                    if (isset($q['code']) && strtoupper($q['code']) === $code) {
                        $heroQuote = $q;
                        break 2;
                    }
                }
            }
            // Montar lista de até 6 ativos para o hero
            foreach ($preferred as $code) {
                foreach ($all as $q) {
                    $codeUp = isset($q['code']) ? strtoupper($q['code']) : '';
                    $idUp = isset($q['id_api']) ? strtoupper($q['id_api']) : '';
                    if ($codeUp === strtoupper($code) || $idUp === strtoupper($code)) {
                        $heroAssets[] = $q;
                        break;
                    }
                }
                if (count($heroAssets) >= 6) break;
            }
            if (!$heroQuote && !empty($all)) {
                // fallback: primeiro índice futuro
                foreach ($all as $q) {
                    if (!empty($q['grupo']) && (strpos($q['grupo'], 'indice') !== false)) {
                        $heroQuote = $q; break;
                    }
                }
                $heroQuote = $heroQuote ?: $all[0];
            }
        } catch (\Throwable $e) {
            $heroQuote = null; // não bloquear a home
        }

        if ($heroQuote) {
            // Formatar
            $last = $heroQuote['last'] ?? '';
            $pcp = $heroQuote['pcp'] ?? '';
            $apelido = $heroQuote['apelido'] ?? ($heroQuote['nome'] ?? ($heroQuote['code'] ?? ''));
            $last_numeric = is_numeric(str_replace([',','.'], '', (string)$last)) ? (float)str_replace(',', '.', (string)$last) : null;
            $last_fmt = $last_numeric !== null ? number_format($last_numeric, 2, ',', '.') : ($last ?: '');
            $heroQuote = [
                'apelido' => $apelido,
                'last' => $last_fmt,
                'pcp' => is_string($pcp) && str_contains($pcp, '%') ? $pcp : ($pcp !== '' ? ($pcp.'%') : ''),
            ];
        }

        $this->view('home/index', [
            'statistics' => $statistics,
            'heroQuote' => $heroQuote,
            'heroAssets' => $heroAssets,
        ]);
    }
}
