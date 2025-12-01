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

    /**
     * Endpoint protegido específico do Dashboard Ouro.
     * Retorna apenas os ativos necessários (gold, dxy, us10y, vix),
     * com campos sanitizados e porcentagem unificada.
     */
    public function goldBoot(): void
    {
        header('Content-Type: application/json');
        try {
            $app = Application::getInstance();
            // Preferências ampliadas incluindo id_api específicos informados e GC futures
            // IMPORTANTE: 68 = GOLD principal (XAUUSD), 8830 = GOLD 2!
            $preferred = [
                // Ouro principal (68 = XAUUSD à vista, prioridade absoluta)
                '68','XAUUSD','XAU/USD',
                // Ouro 2! (8830 = alternativo)
                '8830','GOLD',
                // DXY
                '1224074','DXY','TVC:DXY','DX-Y.NYB','ICEUS:DXY',
                // US10Y
                'US10Y','^TNX','UST10Y',
                // VIX (SPVIX informado)
                '44336','SPVIX','VIX','^VIX',
                // GVZ
                'GVZ','^GVZ','GVOL','GOLD VOLATILITY',
                // GC futures grid (separados do gold principal)
                '1178340','1178341','1178342','1193189','1193190','1213656','1213657',
                'GC1!','GC2!','GC3!','GC4!','GC5!','GC6!','GC7!'
            ];
            $subset = $this->quotesService->getByIdsOrCodes($preferred);
            $subset = is_array($subset) ? $subset : [];

            $norm = function($v){ return strtoupper(trim((string)$v)); };
            $match = function(array $list, array $spec) use ($norm) {
                $codes = array_map($norm, $spec['codes'] ?? []);
                $names = array_map($norm, $spec['names'] ?? []);
                $keywords = array_map($norm, $spec['keywords'] ?? []);
                $best = null;
                foreach ($list as $it) {
                    $code = $norm($it['code'] ?? '');
                    $id   = $norm($it['id_api'] ?? '');
                    $nome = $norm($it['nome'] ?? '');
                    $ap   = $norm($it['apelido'] ?? '');
                    if (in_array($code, $codes, true) || in_array($id, $codes, true)) return $it;
                    if ($best === null && ($nome !== '' || $ap !== '')) {
                        foreach ($names as $n) { if ($n !== '' && (str_contains($nome, $n) || str_contains($ap, $n))) { $best = $it; break; } }
                        if ($best === null) {
                            foreach ($keywords as $k) { if ($k !== '' && (str_contains($nome, $k) || str_contains($ap, $k))) { $best = $it; break; } }
                        }
                    }
                }
                if ($best !== null) return $best;
                foreach ($list as $it) {
                    $code = $norm($it['code'] ?? '');
                    foreach ($codes as $c) { if ($c !== '' && str_contains($code, $c)) return $it; }
                }
                return null;
            };

            // IMPORTANTE: id_api 68 = GOLD principal (XAUUSD), 8830 = GOLD 2!
            $targets = [
                'gold'   => ['codes' => ['68','XAUUSD','XAU/USD'], 'names' => ['OURO','GOLD'], 'keywords' => []],
                'gold2'  => ['codes' => ['8830','GOLD'], 'names' => ['OURO 2!','GOLD 2!'], 'keywords' => []],
                'dxy'    => ['codes' => ['1224074','DXY','TVC:DXY','DX-Y.NYB','ICEUS:DXY'], 'names' => ['DXY','DOLLAR INDEX'], 'keywords' => ['DOLLAR','DÓLAR','INDEX']],
                'us10y'  => ['codes' => ['US10Y','^TNX','UST10Y'], 'names' => ['10Y','TREASURY'], 'keywords' => ['10Y','TREASURY']],
                'vix'    => ['codes' => ['44336','SPVIX','VIX','^VIX'], 'names' => ['VIX'], 'keywords' => ['VOLATILITY','VIX']],
                'gvz'    => ['codes' => ['GVZ','^GVZ','GVOL'], 'names' => ['GVZ','GOLD VOLATILITY'], 'keywords' => ['GOLD','VOLATILITY','GVZ']],
            ];

            $pick = [];
            foreach ($targets as $key => $spec) {
                $item = $match($subset, $spec);
                if ($item === null) {
                    // fallback: buscar em toda a tabela se não veio na query filtrada
                    try {
                        $all = $this->quotesService->getAllQuotes();
                        $item = $match(is_array($all) ? $all : [], $spec);
                    } catch (\Throwable $t) { $item = null; }
                }
                $pick[$key] = $item ? $this->unifyQuote($this->sanitizeGoldRow($item)) : null;
            }

            try { $app->logger()->info('[QUOTES] goldBoot', [ 'found' => array_map(fn($v)=> $v ? ($v['code'] ?? ($v['id_api'] ?? '')) : null, $pick) ]); } catch (\Throwable $t) {}

            // GC futures: GC1! ... GC7! por id_api
            $futuresIds = ['1178340','1178341','1178342','1193189','1193190','1213656','1213657'];
            $futures = [];
            try {
                $gc = $this->quotesService->getByIdsOrCodes($futuresIds);
                $gc = is_array($gc) ? $gc : [];
                foreach ($gc as $row) {
                    $futures[] = $this->unifyQuote($this->sanitizeGoldRow($row));
                }
            } catch (\Throwable $t) { $futures = []; }

            // média de last_numeric quando disponível
            $sum = 0.0; $cnt = 0;
            foreach ($futures as $r) {
                $ln = $r['last_numeric'] ?? null;
                if (is_numeric($ln)) { $sum += (float)$ln; $cnt++; }
                elseif (isset($r['last']) && is_numeric($r['last'])) { $sum += (float)$r['last']; $cnt++; }
            }
            $avg = $cnt > 0 ? round($sum / $cnt, 2) : null;

            // Gold Miners: AEM, Barrick (B), NEM, WPM + GDX variants (ASX, LSE, NYSE)
            $minersIds = ['13930', '13928', '8150', '8111', '962168', '956297', '40681'];
            $miners = [];
            try {
                $mn = $this->quotesService->getByIdsOrCodes($minersIds);
                $mn = is_array($mn) ? $mn : [];
                foreach ($mn as $row) {
                    $miners[] = $this->unifyQuote($this->sanitizeGoldRow($row));
                }
            } catch (\Throwable $t) { $miners = []; }

            echo json_encode(['data' => $pick, 'futures' => $futures, 'futures_avg' => $avg, 'miners' => $miners, 'error' => '']);
        } catch (\Throwable $e) {
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

    private function unifyQuote(array $row): array
    {
        $row = is_array($row) ? $row : [];
        $pc = $row['pc'] ?? null;
        if ($pc === null || $pc === '' || !is_numeric($pc)) {
            $last = $row['last'] ?? ($row['last_numeric'] ?? null);
            $close = $row['last_close'] ?? null;
            if (is_numeric($last) && is_numeric($close) && (float)$close != 0.0) {
                $pc = ((float)$last - (float)$close) / (float)$close * 100.0;
            }
        }
        if (is_numeric($pc)) {
            $row['pc'] = round((float)$pc, 2);
        }
        return $row;
    }

    private function sanitizeGoldRow(array $row): array
    {
        // Campos permitidos — sem id_api, grupo, order_tabela
        $allowed = [
            'code','apelido','nome',
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
}
