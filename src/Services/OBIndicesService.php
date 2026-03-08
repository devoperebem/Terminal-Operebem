<?php

namespace App\Services;

use App\Core\Application;
use App\Services\CustomIndicesService;

/**
 * OB Indices Service
 * Calcula os índices customizados da Operebem
 * 
 * - IFPV (Índice de Feeling para VALE3)
 * - IFPP (Índice de Feeling para PETR4)
 * - IDFE (Índice do Dólar Frente Emergentes)
 * - IDFE2 (Índice do Dólar Frente Emergentes ênfase Brasil)
 */
class OBIndicesService
{
    private QuotesService $quotesService;
    private CustomIndicesService $customIndices;
    
    /**
     * Mapeamento de IDs de ativos para os índices (usando id_api do banco)
     */
    private array $indicesMap = [
        // IFPV - Vale
        'IFPV' => [
            'FEF1!'  => ['weight' => 0.35, 'name' => 'Minério de Ferro (SGX FEF1!)'],
            '13059'  => ['weight' => 0.30, 'name' => 'Vale ADR'],
            '27407'  => ['weight' => 0.15, 'name' => 'FTSE 350 Mining'],
            '945574' => ['weight' => 0.10, 'name' => 'SZSE Mining Index'],
            '509'    => ['weight' => 0.10, 'name' => 'EWZ (Brazil ETF)'],
        ],
        // IFPP - Petrobras
        'IFPP' => [
            '8833'   => ['weight' => 0.35, 'name' => 'Petróleo Brent Futuro'],
            '8028'   => ['weight' => 0.30, 'name' => 'Petrobras ADR'],
            'MAJORS' => ['weight' => 0.15, 'name' => 'Majors', 'ids' => ['1153650','7888','240','23655','101123']],
            '509'    => ['weight' => 0.10, 'name' => 'EWZ (Brazil ETF)'],
            'IDFE2_INV' => ['weight' => 0.10, 'name' => 'IDFE2 (invertido)'],
        ],
        // IDFE - Dólar vs Emergentes (amplo)
        'IDFE' => [
            '1224074' => ['weight' => 0.05, 'name' => 'DXY (Índice Dólar)'],
            'EMERGENTES' => ['weight' => 0.95, 'name' => 'Cesta Emergentes', 'ids' => ['2103', '39', '2111', '160', '17', '18', '2112', '2110', '2122']],
        ],
        // IDFE2 - Dólar vs Emergentes (ênfase Brasil)
        'IDFE2' => [
            'USD|BRL' => ['weight' => 0.25, 'name' => 'USD|BRL', 'ids' => ['2103','1174711']],
            'EMERG_CORR' => ['weight' => 0.75, 'name' => 'Cesta Emergentes Correlacionada', 'ids' => ['39','2110','2112','17']],
        ],
    ];
    
    
    public function __construct()
    {
        $this->quotesService = new QuotesService();
        $this->customIndices = new CustomIndicesService();
    }

    private function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float)$v;
        if (!is_string($v)) return null;
        $s = trim($v);
        $s = preg_replace('/[^0-9,\.\-]/', '', $s);
        if ($s === '' || $s === '-') return null;
        $hasDot = strpos($s, '.') !== false;
        $hasComma = strpos($s, ',') !== false;
        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        return is_numeric($s) ? (float)$s : null;
    }

    private function numPref($n1, $n2, $n3): ?float
    {
        $a = $this->num($n1); if ($a !== null) return $a;
        $b = $this->num($n2); if ($b !== null) return $b;
        return $this->num($n3);
    }

    private function resolveLast(array $row): ?float
    {
        $n = $this->num($row['last_numeric'] ?? null);
        if ($n !== null) return $n;
        $l = $this->num($row['last'] ?? null);
        if ($l !== null) return $l;
        return $this->num($row['last_close'] ?? null);
    }

    private function computePcp(array $row): ?float
    {
        $pcp = $this->num($row['pcp'] ?? null);
        if ($pcp !== null) return $pcp;
        $last = $this->resolveLast($row);
        $close = $this->num($row['last_close'] ?? null);
        if ($last !== null && $close !== null && $close != 0.0) {
            return (($last - $close) / $close) * 100.0;
        }
        return null;
    }

    private function resolveUpdatedAt(array $row): ?string
    {
        // Obter timezone do usuário (padrão: America/Sao_Paulo)
        $userTimezone = \App\Services\TimezoneService::getUserTimezone();
        
        $ts = $row['timestamp'] ?? null;
        if ($ts !== null && $ts !== '') {
            if (is_numeric($ts)) {
                // Timestamp Unix - converter para timezone do usuário
                $t = (int)$ts; 
                if ($t > 0) {
                    $dt = new \DateTime('@' . $t, new \DateTimeZone('UTC'));
                    $dt->setTimezone(new \DateTimeZone($userTimezone));
                    return $dt->format('Y-m-d H:i:s');
                }
            } else {
                // String datetime - assumir UTC e converter para timezone do usuário
                $t = strtotime((string)$ts . ' UTC'); 
                if ($t !== false) {
                    $dt = new \DateTime('@' . $t, new \DateTimeZone('UTC'));
                    $dt->setTimezone(new \DateTimeZone($userTimezone));
                    return $dt->format('Y-m-d H:i:s');
                }
            }
        }
        $t2 = $row['time_utc'] ?? null;
        if ($t2 !== null && $t2 !== '') {
            $t = strtotime((string)$t2 . ' UTC'); 
            if ($t !== false) {
                $dt = new \DateTime('@' . $t, new \DateTimeZone('UTC'));
                $dt->setTimezone(new \DateTimeZone($userTimezone));
                return $dt->format('Y-m-d H:i:s');
            }
        }
        $t3 = $row['dt_alteracao'] ?? null;
        if ($t3 !== null && $t3 !== '') {
            // dt_alteracao - converter para timezone do usuário
            $t = strtotime((string)$t3);
            if ($t !== false) {
                $dt = new \DateTime('@' . $t, new \DateTimeZone('UTC'));
                $dt->setTimezone(new \DateTimeZone($userTimezone));
                return $dt->format('Y-m-d H:i:s');
            }
        }
        return null;
    }
    
    /**
     * Calcula todos os índices OB
     * 
     * @return array
     */
    public function calculateAllIndices(): array
    {
        Application::getInstance()->logger()->info('[OB_INDICES] Iniciando obtenção de índices');
        try {
            $fromDb = $this->customIndices->getAll();
            Application::getInstance()->logger()->info('[OB_INDICES] Lidos de custom_indices');
            return $fromDb;
        } catch (\Throwable $e) {
            Application::getInstance()->logger()->error('[OB_INDICES] Erro ao obter índices: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Calcula IFPV (Índice de Feeling para VALE3)
     * Fórmula: (pcp_961741 × 0.40) + (pcp_13059 × 0.25) + (pcp_27407 × 0.20) + (pcp_509 × 0.15)
     * 
     * @return array
     */
    public function calculateIFPV(): array
    {
        $components = [];
        $totalWeight = 0;
        $indexValue = 0;
        
        // Buscar todas as cotações por IDs + spot VALE3 (18814)
        $ids = array_keys($this->indicesMap['IFPV']);
        $ids[] = '18814';
        $quotes = $this->quotesService->getQuotesBySymbols($ids);
        
        foreach ($this->indicesMap['IFPV'] as $id => $config) {
            $quote = $quotes[$id] ?? null;
            if ($quote) {
                $pcp = $this->computePcp($quote);
                if ($pcp !== null) {
                    $contribution = $pcp * $config['weight'];
                    $indexValue += $contribution;
                    $totalWeight += $config['weight'];
                } else {
                    $contribution = 0.0;
                }
                $components[$id] = [
                    'id' => $id,
                    'name' => $config['name'],
                    'weight' => $config['weight'],
                    'pcp' => $pcp ?? 0.0,
                    'contribution' => $contribution,
                    'price' => $this->resolveLast($quote),
                    'updated_at' => $this->resolveUpdatedAt($quote),
                ];
            } else {
                // Stub sem quote
                $components[$id] = [
                    'id' => $id,
                    'name' => $config['name'],
                    'weight' => $config['weight'],
                    'pcp' => 0.0,
                    'contribution' => 0.0,
                    'price' => null,
                    'updated_at' => null,
                ];
            }
        }
        
        // Spot VALE3 (B3)
        $spotVale = $quotes['18814'] ?? null;
        $spot = [
            'id' => '18814',
            'name' => 'VALE3',
            'price' => $spotVale ? $this->resolveLast($spotVale) : null,
            'pcp' => $spotVale ? ($this->computePcp($spotVale) ?? null) : null,
            'updated_at' => $spotVale ? $this->resolveUpdatedAt($spotVale) : null,
        ];

        return [
            'name' => 'IFPV',
            'description' => 'Índice de Feeling para VALE3',
            'value' => round($indexValue, 2),
            'components' => $components,
            'total_weight' => $totalWeight,
            'status' => $totalWeight > 0.8 ? 'ok' : 'partial',
            'spot' => $spot,
        ];
    }
    
    /**
     * Calcula IFPP (Índice de Feeling para PETR4)
     * Fórmula: (pcp_8833 × 0.40) + (pcp_8028 × 0.25) + (Média[pcp_23655, pcp_101123] × 0.15) + (pcp_509 × 0.10) + (pcp_2103 × 0.10)
     * 
     * @return array
     */
    public function calculateIFPP(): array
    {
        $components = [];
        $totalWeight = 0;
        $indexValue = 0;
        
        // Coletar todos os IDs necessários + spot PETR4 (18750)
        $allIds = [];
        foreach ($this->indicesMap['IFPP'] as $key => $config) {
            if ($key === 'MAJORS') {
                $allIds = array_merge($allIds, $config['ids']);
            } elseif ($key === 'IDFE2_INV') {
                // calculado via índice, sem ID direto
            } else {
                $allIds[] = $key;
            }
        }
        // Spot PETR4
        $allIds[] = '18750';
        
        // Garantir existência dos IDs necessários (auto-cadastro se ausente)
        foreach ($allIds as $aid) {
            if (is_numeric($aid)) {
                if ((string)$aid === '1174711') {
                    $this->quotesService->ensureInvestingIdExists('1174711', 'USDTBRL', 'USDT/BRL');
                } else {
                    $this->quotesService->ensureInvestingIdExists((string)$aid, null, null);
                }
            }
        }
        // Garantir existência dos IDs necessários (auto-cadastro se ausente)
        foreach ($allIds as $aid) {
            if (is_numeric($aid)) {
                if ((string)$aid === '1174711') {
                    $this->quotesService->ensureInvestingIdExists('1174711', 'USDTBRL', 'USDT/BRL');
                } else {
                    $this->quotesService->ensureInvestingIdExists((string)$aid, null, null);
                }
            }
        }
        $quotes = $this->quotesService->getQuotesBySymbols($allIds);
        
        foreach ($this->indicesMap['IFPP'] as $key => $config) {
            if ($key === 'MAJORS') {
                // Calcular média dos Majors (Shell + PetroChina)
                $majorsPcp = [];
                $nameMap = [
                    '1153650' => 'Saudi Aramco',
                    '7888'    => 'Exxon Mobil',
                    '240'     => 'Chevron',
                    '23655'   => 'Shell',
                    '101123'  => 'PetroChina',
                ];
                foreach ($config['ids'] as $majorId) {
                    $row = $quotes[$majorId] ?? null;
                    if ($row) {
                        $pcpMajor = $this->computePcp($row);
                        if ($pcpMajor !== null) { $majorsPcp[] = $pcpMajor; }
                    }
                }
                if (!empty($majorsPcp)) {
                    $avgPcp = array_sum($majorsPcp) / count($majorsPcp);
                    $contribution = $avgPcp * $config['weight'];
                    $indexValue += $contribution;
                    $totalWeight += $config['weight'];
                    $sub = [];
                    $share = count($config['ids']) > 0 ? ($config['weight'] / count($config['ids'])) : 0.0;
                    foreach ($config['ids'] as $mid) {
                        $row = $quotes[$mid] ?? [];
                        $sub[] = [
                            'id' => $mid,
                            'name' => $nameMap[(string)$mid] ?? ($row['nome'] ?? ($row['code'] ?? 'N/A')),
                            'pcp' => $this->computePcp($row) ?? 0.0,
                            'weight_share' => $share,
                            'updated_at' => $this->resolveUpdatedAt($row),
                        ];
                    }
                    $components['MAJORS'] = [
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => $avgPcp,
                        'contribution' => $contribution,
                        'ids' => $config['ids'],
                        'subcomponents' => $sub,
                    ];
                } else {
                    // Stub MAJORS
                    $components['MAJORS'] = [
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => 0.0,
                        'contribution' => 0.0,
                        'ids' => $config['ids'],
                        'subcomponents' => [],
                    ];
                }
            } elseif ($key === 'IDFE2_INV') {
                // Usar valor do IDFE2 invertido
                $idfe2 = $this->calculateIDFE2();
                $pcp = -1 * ($idfe2['value'] ?? 0.0);
                $contribution = $pcp * $config['weight'];
                $indexValue += $contribution;
                $totalWeight += $config['weight'];
                $components[$key] = [
                    'id' => $key,
                    'name' => $config['name'],
                    'weight' => $config['weight'],
                    'pcp' => $pcp,
                    'contribution' => $contribution,
                ];
            } else {
                $quote = $quotes[$key] ?? null;
                if ($quote) {
                    $pcp = $this->computePcp($quote);
                    if ($pcp !== null) {
                        $contribution = $pcp * $config['weight'];
                        $indexValue += $contribution;
                        $totalWeight += $config['weight'];
                    } else {
                        $contribution = 0.0;
                    }
                    $components[$key] = [
                        'id' => $key,
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => $pcp ?? 0.0,
                        'contribution' => $contribution,
                        'price' => $this->resolveLast($quote),
                        'updated_at' => $this->resolveUpdatedAt($quote),
                    ];
                } else {
                    $components[$key] = [
                        'id' => $key,
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => 0.0,
                        'contribution' => 0.0,
                        'price' => null,
                        'updated_at' => null,
                    ];
                }
            }
        }
        // Spot PETR4 (B3)
        $spotPetr = $quotes['18750'] ?? null;
        $spot = [
            'id' => '18750',
            'name' => 'PETR4',
            'price' => $spotPetr ? $this->resolveLast($spotPetr) : null,
            'pcp' => $spotPetr ? ($this->computePcp($spotPetr) ?? null) : null,
            'updated_at' => $spotPetr ? $this->resolveUpdatedAt($spotPetr) : null,
        ];

        return [
            'name' => 'IFPP',
            'description' => 'Índice de Feeling para PETR4',
            'value' => round($indexValue, 2),
            'components' => $components,
            'total_weight' => $totalWeight,
            'status' => $totalWeight > 0.8 ? 'ok' : 'partial',
            'spot' => $spot,
        ];
    }
    
    /**
     * Calcula IDFE (Índice do Dólar Frente Emergentes - amplo)
     * Fórmula: (pcp_1224074 × 0.05) + (Média[9 moedas] × 0.95)
     * 
     * @return array
     */
    public function calculateIDFE(): array
    {
        $components = [];
        $totalWeight = 0;
        $indexValue = 0;
        
        // Coletar todos os IDs
        $allIds = [];
        foreach ($this->indicesMap['IDFE'] as $key => $config) {
            if ($key === 'EMERGENTES') {
                $allIds = array_merge($allIds, $config['ids']);
            } else {
                $allIds[] = $key;
            }
        }
        
        $quotes = $this->quotesService->getQuotesBySymbols($allIds);
        
        foreach ($this->indicesMap['IDFE'] as $key => $config) {
            if ($key === 'EMERGENTES') {
                // Calcular média dos emergentes
                $emergentesPcp = [];
                foreach ($config['ids'] as $emId) {
                    $row = $quotes[$emId] ?? null;
                    if ($row) {
                        $pcp = $this->computePcp($row);
                        if ($pcp !== null) { $emergentesPcp[] = $pcp; }
                    }
                }
                if (!empty($emergentesPcp)) {
                    $avgPcp = array_sum($emergentesPcp) / count($emergentesPcp);
                    $contribution = $avgPcp * $config['weight'];
                    $indexValue += $contribution;
                    $totalWeight += $config['weight'];
                    $sub = [];
                    $share = count($config['ids']) > 0 ? ($config['weight'] / count($config['ids'])) : 0.0;
                    foreach ($config['ids'] as $eid) {
                        $row = $quotes[$eid] ?? [];
                        $sub[] = [
                            'id' => $eid,
                            'name' => $row['nome'] ?? ($row['code'] ?? 'N/A'),
                            'pcp' => $this->computePcp($row) ?? 0.0,
                            'weight_share' => $share,
                            'updated_at' => $this->resolveUpdatedAt($row),
                        ];
                    }
                    $components['EMERGENTES'] = [
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => $avgPcp,
                        'contribution' => $contribution,
                        'count' => count($emergentesPcp),
                        'ids' => $config['ids'],
                        'subcomponents' => $sub,
                    ];
                } else {
                    $components['EMERGENTES'] = [
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => 0.0,
                        'contribution' => 0.0,
                        'count' => 0,
                        'ids' => $config['ids'],
                        'subcomponents' => [],
                    ];
                }
            } else {
                $quote = $quotes[$key] ?? null;
                if ($quote) {
                    $pcp = $this->computePcp($quote);
                    if ($pcp !== null) {
                        $contribution = $pcp * $config['weight'];
                        $indexValue += $contribution;
                        $totalWeight += $config['weight'];
                    } else {
                        $contribution = 0.0;
                    }
                    $components[$key] = [
                        'id' => $key,
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => $pcp ?? 0.0,
                        'contribution' => $contribution,
                        'updated_at' => $this->resolveUpdatedAt($quote),
                    ];
                } else {
                    $components[$key] = [
                        'id' => $key,
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => 0.0,
                        'contribution' => 0.0,
                    ];
                }
            }
        }
        
        return [
            'name' => 'IDFE',
            'description' => 'Índice do Dólar Frente Emergentes',
            'value' => round($indexValue, 2),
            'components' => $components,
            'total_weight' => $totalWeight,
            'status' => $totalWeight > 0.8 ? 'ok' : 'partial',
        ];
    }
    
    /**
     * Calcula IDFE2 (Índice do Dólar Frente Emergentes ênfase Brasil)
     * Fórmula: (pcp_1224074 × 0.05) + (pcp_2103 × 0.20) + (pcp_39 × 0.1875) + (pcp_2110 × 0.1875) + (pcp_2112 × 0.1875) + (pcp_17 × 0.1875)
     * 
     * @return array
     */
    public function calculateIDFE2(): array
    {
        $components = [];
        $totalWeight = 0;
        $indexValue = 0;
        
        // Coletar IDs (com cesta)
        $allIds = [];
        foreach ($this->indicesMap['IDFE2'] as $key => $cfg) {
            if (isset($cfg['ids'])) { $allIds = array_merge($allIds, $cfg['ids']); }
            else { $allIds[] = $key; }
        }
        // Garantir existência dos IDs necessários (auto-cadastro se ausente)
        foreach ($allIds as $aid) {
            if (is_numeric($aid)) {
                if ((string)$aid === '1174711') {
                    $this->quotesService->ensureInvestingIdExists('1174711', 'USDTBRL', 'USDT/BRL');
                } else {
                    $this->quotesService->ensureInvestingIdExists((string)$aid, null, null);
                }
            }
        }
        $quotes = $this->quotesService->getQuotesBySymbols($allIds);
        
        foreach ($this->indicesMap['IDFE2'] as $key => $config) {
            if (isset($config['ids'])) {
                $pcps = [];
                foreach ($config['ids'] as $cid) {
                    $row = $quotes[$cid] ?? null;
                    if ($row) {
                        $p = $this->computePcp($row);
                        if ($p !== null) $pcps[] = $p;
                    }
                }
                if (!empty($pcps)) {
                    $avg = array_sum($pcps) / count($pcps);
                    $contrib = $avg * $config['weight'];
                    $indexValue += $contrib;
                    $totalWeight += $config['weight'];
                    $sub = [];
                    $share = count($config['ids']) > 0 ? ($config['weight'] / count($config['ids'])) : 0.0;
                    foreach ($config['ids'] as $sid) {
                        $row = $quotes[$sid] ?? [];
                        $nm = $row['nome'] ?? ($row['code'] ?? 'N/A');
                        if ($nm === 'N/A' && (string)$sid === '1174711') { $nm = 'USDT/BRL'; }
                        $sub[] = [
                            'id' => $sid,
                            'name' => $nm,
                            'pcp' => $this->computePcp($row) ?? 0.0,
                            'weight_share' => $share,
                            'updated_at' => $this->resolveUpdatedAt($row),
                        ];
                    }
                    $components[$key] = [
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => $avg,
                        'contribution' => $contrib,
                        'count' => count($config['ids']),
                        'ids' => $config['ids'],
                        'subcomponents' => $sub,
                    ];
                } else {
                    $components[$key] = [
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => 0.0,
                        'contribution' => 0.0,
                        'count' => count($config['ids']),
                        'ids' => $config['ids'],
                        'subcomponents' => [],
                    ];
                }
            } else {
                $quote = $quotes[$key] ?? null;
                if ($quote) {
                    $pcp = $this->computePcp($quote);
                    if ($pcp !== null) {
                        $contrib = $pcp * $config['weight'];
                        $indexValue += $contrib;
                        $totalWeight += $config['weight'];
                    } else { $contrib = 0.0; }
                    $components[$key] = [
                        'id' => $key,
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => $pcp ?? 0.0,
                        'contribution' => $contrib,
                        'price' => $this->resolveLast($quote),
                        'updated_at' => $this->resolveUpdatedAt($quote),
                    ];
                } else {
                    $components[$key] = [
                        'id' => $key,
                        'name' => $config['name'],
                        'weight' => $config['weight'],
                        'pcp' => 0.0,
                        'contribution' => 0.0,
                        'price' => null,
                        'updated_at' => null,
                    ];
                }
            }
        }
        
        return [
            'name' => 'IDFE2',
            'description' => 'Índice do Dólar Frente Emergentes (ênfase Brasil)',
            'value' => round($indexValue, 2),
            'components' => $components,
            'total_weight' => $totalWeight,
            'status' => $totalWeight > 0.9 ? 'ok' : 'partial',
        ];
    }
    
    /**
     * Obtém detalhes de um índice específico
     * 
     * @param string $indexName
     * @return array|null
     */
    public function getIndexDetails(string $indexName): ?array
    {
        $name = strtoupper($indexName);
        try {
            $one = $this->customIndices->getOne($name);
            return is_array($one) ? $one : null;
        } catch (\Throwable $t) {
            return null;
        }
    }
}
