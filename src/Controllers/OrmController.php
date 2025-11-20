<?php

namespace App\Controllers;

use App\Core\Application;
use App\Services\QuotesService;
use App\Services\Providers\CnbcUs10yProvider;

class OrmController
{
    private QuotesService $quotes;
    private static array $cache = [
        't' => 0,
        'payload' => null,
    ];

    // id_api mapping (TradingView/Investing IDs, conforme fornecido pelo usuário)
    private const ID_MAP = [
        'sp500_fut'  => '1175153',
        'nasdaq_fut' => '1175151',
        'eem'        => '505',
        'bitcoin'    => '1057391',
        'us10y'      => 'US10Y',
        'vix'        => '44336',
        'dxy'        => '1224074',
        'gold'       => '8830',
    ];

    // Grupo de risco (preferência do usuário: US10Y = Risk On)
    private const RISK_ON = ['sp500_fut','nasdaq_fut','eem','bitcoin','us10y'];

    // Pesos do ORM (devem somar 1.00)
    private const WEIGHTS = [
        'sp500_fut'  => 0.125,
        'nasdaq_fut' => 0.125,
        'eem'        => 0.125,
        'bitcoin'    => 0.125,
        'us10y'      => 0.125,
        'vix'        => 0.125,
        'dxy'        => 0.125,
        'gold'       => 0.125,
    ];

    // Recência aceitável para dados em segundos (após isso reduz o peso)
    private const STALE_SEC = 120;


    public function __construct()
    {
        $this->quotes = new QuotesService();
    }

    // Endpoint de debug (sempre ativo, mas requer auth)
    public function debug(): void
    {
        header('Content-Type: application/json');
        if (empty($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['success'=>false,'error'=>'Unauthorized']); return; }
        try {
            $ids = self::ID_MAP;
            $rows = $this->quotes->getByIdsOrCodes(array_values($ids));
            $by = [];
            foreach ($rows as $r) {
                $idKey = (string)($r['id_api'] ?? '');
                $codeKey = (string)($r['code'] ?? '');
                if ($idKey !== '') { $by[$idKey] = $r; }
                if ($codeKey !== '') { $by[$codeKey] = $r; }
            }
            $rep = [];
            foreach ($ids as $key => $id) {
                $row = $by[(string)$id] ?? null;
                if (!$row) { $rep[$key] = ['found'=>false]; continue; }
                $rl = $this->resolveLastEx($row);
                $last = $rl['last'];
                $high = $this->numPref($row['high_numeric'] ?? null, $row['high'] ?? null, null); $low = $this->numPref($row['low_numeric'] ?? null, $row['low'] ?? null, null); $approx=false;
                // Tratar valores 0.0 como ausentes quando last for válido (evitar mínimas 0)
                if ($last !== null) {
                    if ($high !== null && $high == 0.0 && abs($last) > 1e-12) { $high = null; }
                    if ($low  !== null && $low  == 0.0 && abs($last) > 1e-12) { $low  = null; }
                }
                if ($last !== null && ($high===null || $low===null)) { [$low,$high,$approx] = $this->syntheticRange($row,$last); }
                if ($last !== null && $high !== null && $low !== null) {
                    if ($high < $low) { $tmp = $high; $high = $low; $low = $tmp; }
                    if ($last < $low) { $low = $last; $approx = true; }
                    if ($last > $high) { $high = $last; $approx = true; }
                }
                $score = null; if ($last!==null && $high!==null && $low!==null) { $score = $this->score($last,$high,$low,in_array($key,self::RISK_ON,true)); }
                $rep[$key] = [
                    'found'=>true,
                    'id_api'=>$id,
                    'code'=>$row['code'] ?? null,
                    'raw'=>[ 'last'=>$row['last']??null, 'last_numeric'=>$row['last_numeric']??null, 'high'=>$row['high']??null, 'low'=>$row['low']??null, 'pcp'=>$row['pcp']??null, 'last_close'=>$row['last_close']??null ],
                    'parsed'=>[ 'last'=>$last, 'high'=>$high, 'low'=>$low, 'approx'=>$approx, 'chosen'=>$rl['source'] ?? null, 'pcp'=>$this->num($row['pcp'] ?? null), 'close'=>$this->num($row['last_close'] ?? null) ],
                    'score'=>$score,
                ];
            }
            try { Application::getInstance()->logger()->info('[ORM] debug report', $rep); } catch (\Throwable $t) {}
            echo json_encode(['success'=>true,'data'=>$rep], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            try { Application::getInstance()->logger()->error('[ORM] debug error', ['error'=>$e->getMessage()]); } catch (\Throwable $t) {}
            http_response_code(500); echo json_encode(['success'=>false,'error'=>'internal']);
        }
    }

    // Página de diagnóstico: cria índices, roda testes e exibe JSON com botão de copiar
    public function tools(): void
    {
        if (empty($_SESSION['user_id'])) { http_response_code(401); echo 'Unauthorized'; return; }
        $app = Application::getInstance();
        $pdo = $this->quotes->pdo();
        $result = [ 'steps'=>[], 'errors'=>[] ];
        try {
            // Criar colunas numéricas se não existirem
            $this->addColumn($pdo, 'high_numeric', 'double precision');
            $this->addColumn($pdo, 'low_numeric', 'double precision');
            $result['steps'][] = ['columns_ensured' => ['high_numeric','low_numeric']];

            $idxBefore = $this->listIndexes($pdo);
            $result['steps'][] = ['indexes_before'=>$idxBefore];
            $this->createIndex($pdo, 'idx_dicionario_id_api', 'id_api');
            $this->createIndex($pdo, 'idx_dicionario_code', 'code');
            $this->createIndex($pdo, 'idx_dicionario_origem_ativo', 'origem, ativo');
            $this->createIndex($pdo, 'idx_dicionario_ativo_order', 'ativo, order_tabela');
            $idxAfter = $this->listIndexes($pdo);
            $result['steps'][] = ['indexes_after'=>$idxAfter];
            $ids = array_values(self::ID_MAP);
            $t0 = microtime(true);
            $rows = $this->quotes->getByIdsOrCodes($ids);
            $t1 = microtime(true);
            $by = [];
            foreach ($rows as $r) {
                $idKey = (string)($r['id_api'] ?? '');
                $codeKey = (string)($r['code'] ?? '');
                if ($idKey !== '') { $by[$idKey] = $r; }
                if ($codeKey !== '') { $by[$codeKey] = $r; }
            }
            $assets = [];
            foreach (self::ID_MAP as $k=>$id) {
                $r = $by[(string)$id] ?? null;
                if (!$r) { $assets[$k] = ['found'=>false]; continue; }
                $last = $this->numPref($r['last_numeric'] ?? null, $r['last'] ?? null, $r['last_close'] ?? null);
                $high = $this->numPref($r['high_numeric'] ?? null, $r['high'] ?? null, null); $low = $this->numPref($r['low_numeric'] ?? null, $r['low'] ?? null, null); $approx=false;
                if ($last!==null && ($high===null || $low===null)) { [$low,$high,$approx] = $this->syntheticRange($r,$last); }
                $assets[$k] = [ 'found'=>true, 'parsed'=>['last'=>$last,'high'=>$high,'low'=>$low,'approx'=>$approx] ];
            }
            $result['steps'][] = ['select_ms'=>round(($t1-$t0)*1000,2), 'assets'=>$assets];
        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
        }
        try { $app->logger()->info('[ORM] tools run', $result); } catch (\Throwable $t) {}
        $json = json_encode($result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>ORM Diagnostics</title><style>body{font-family:system-ui,Segoe UI,Arial,sans-serif;padding:16px;background:#0b0f14;color:#e5e7eb}button{background:#2563eb;color:#fff;border:none;border-radius:8px;padding:10px 14px;cursor:pointer}pre{white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:12px;border-radius:8px;border:1px solid #1f2937}</style></head><body>';
        echo '<h3>ORM Diagnostics</h3><p>Criação de índices + validações. Clique para copiar o JSON.</p>';
        echo '<button onclick="navigator.clipboard.writeText(document.getElementById(\'out\').textContent)">Copiar JSON</button>';
        echo '<pre id="out">'.htmlspecialchars($json, ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8').'</pre>';
        echo '</body></html>';
    }

    private function listIndexes(\PDO $pdo): array
    {
        $stmt = $pdo->query("SELECT indexname,indexdef FROM pg_indexes WHERE tablename='dicionario'");
        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
    }

    private function createIndex(\PDO $pdo, string $name, string $col): void
    {
        $sql = "CREATE INDEX IF NOT EXISTS {$name} ON dicionario (".$col.")";
        $pdo->exec($sql);
    }

    private function addColumn(\PDO $pdo, string $name, string $type): void
    {
        $sql = "ALTER TABLE dicionario ADD COLUMN IF NOT EXISTS {$name} {$type}";
        $pdo->exec($sql);
    }

    public function data(): void
    {
        header('Content-Type: application/json');
        // Proteções leves contra scraping
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success'=>false,'error'=>'Unauthorized']);
            return;
        }
        // Rate limit simples por sessão (máx 8 reqs a cada 10s)
        $now = time();
        $_SESSION['orm_rl'] = $_SESSION['orm_rl'] ?? [];
        $_SESSION['orm_rl'] = array_filter((array)$_SESSION['orm_rl'], fn($t) => ($now - (int)$t) < 10);
        if (count($_SESSION['orm_rl']) >= 8) {
            http_response_code(429);
            echo json_encode(['success'=>false,'error'=>'Too Many Requests']);
            return;
        }
        $_SESSION['orm_rl'][] = $now;

        // Cache (5s) para aliviar DB
        if (($now - (int)self::$cache['t']) < 5 && self::$cache['payload']) {
            header('Cache-Control: private, max-age=5');
            echo json_encode(self::$cache['payload']);
            return;
        }

        try {
            // Buscar apenas os necessários por id_api (sem fallback/heurísticas)
            // Preferir payload pré-computado em custom_indices (coletor Python)
            $pdo = $this->quotes->pdo();
            $stmt = $pdo->prepare("SELECT payload::text FROM custom_indices WHERE name = 'ORM'");
            $stmt->execute();
            $rowCi = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($rowCi && isset($rowCi['payload'])) {
                $pl = json_decode((string)$rowCi['payload'], true);
                if (is_array($pl)) {
                    self::$cache = ['t' => $now, 'payload' => $pl];
                    header('Cache-Control: private, max-age=5');
                    echo json_encode($pl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    return;
                }
            }
            // Sem fallback: indisponível no momento
            header('Cache-Control: private, max-age=30');
            http_response_code(503);
            echo json_encode(['success'=>false,'error'=>'ORM data unavailable']);

        } catch (\Throwable $t) {
            try { Application::getInstance()->logger()->error('[ORM] data error', ['error'=>$t->getMessage()]); } catch (\Throwable $t2) {}
            http_response_code(500);
            echo json_encode(['success'=>false,'error'=>'internal']);
        }
    }

    // Preferir last_numeric; depois last; depois last_close
    private function numPref($n1, $n2, $n3): ?float
    {
        $a = $this->num($n1);
        if ($a !== null) return $a;
        $b = $this->num($n2);
        if ($b !== null) return $b;
        return $this->num($n3);
    }

    private function num($v): ?float
    {
        if ($v === null || $v === '') return null;
        if (is_numeric($v)) return (float)$v;
        if (!is_string($v)) return null;
        $s = trim($v);
        // remover qualquer coisa que não seja dígito, . , ou - ou %
        $s = preg_replace('/[^0-9,\.\-]/', '', $s);
        if ($s === '' || $s === '-' ) return null;
        $hasDot = strpos($s, '.') !== false;
        $hasComma = strpos($s, ',') !== false;
        if ($hasDot && $hasComma) {
            $lastDot = strrpos($s, '.');
            $lastComma = strrpos($s, ',');
            if ($lastComma > $lastDot) {
                // formato 1.234,56 -> decimal é vírgula
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                // formato 1,234.56 -> decimal é ponto
                $s = str_replace(',', '', $s);
            }
        } elseif ($hasComma) {
            // apenas vírgula -> tratar como decimal
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } // apenas ponto -> já está correto
        return is_numeric($s) ? (float)$s : null;
    }

    private function approxEq(float $a, float $b, float $rel = 0.0005): bool
    {
        $m = max(abs($a), abs($b), 1e-9);
        return abs($a - $b) <= ($rel * $m);
    }

    private function resolveLastEx(array $row): array
    {
        $last = $this->num($row['last'] ?? null);
        if ($last !== null) return ['last'=>$last, 'source'=>'last'];
        $lastN = $this->num($row['last_numeric'] ?? null);
        if ($lastN !== null) return ['last'=>$lastN, 'source'=>'last_numeric'];
        $close = $this->num($row['last_close'] ?? null);
        if ($close !== null) return ['last'=>$close, 'source'=>'last_close'];
        return ['last'=>null, 'source'=>null];
    }

    private function resolveLast(array $row): ?float
    {
        $r = $this->resolveLastEx($row);
        return $r['last'];
    }

    private function syntheticRange(array $row, float $last): array
    {
        // Tentar a partir do last_close e pcp
        $close = $this->num($row['last_close'] ?? null);
        $pcp   = $this->num($row['pcp'] ?? null); // %
        if ($close !== null) {
            $amp = null;
            if ($pcp !== null) {
                $amp = abs($pcp) / 100.0 * $close;
            }
            if ($amp === null || $amp == 0.0) {
                $amp = abs($last - $close);
            }
            if ($amp == 0.0) {
                $amp = max(0.005 * $last, 0.5); // 0.5% ou mínimo absoluto 0.5
            }
            return [$close - $amp, $close + $amp, true];
        }
        // fallback: faixa simétrica em torno do last
        $amp = max(0.005 * $last, 0.5);
        return [$last - $amp, $last + $amp, true];
    }

    private function score(float $last, float $high, float $low, bool $riskOn): float
    {
        $range = $high - $low;
        if ($range == 0.0) return 50.0;
        $pos = ($last - $low) / $range; // 0..1
        $pct = $riskOn ? ($pos * 100.0) : ((1.0 - $pos) * 100.0);
        if ($pct < 0) $pct = 0; if ($pct > 100) $pct = 100;
        return round($pct, 2);
    }

    // Composição: posição no range intraday + variação percentual vs close
    private function scoreComposite(float $last, float $high, float $low, ?float $pcp, ?float $close, bool $riskOn, bool $approx): float
    {
        $base = $this->score($last, $high, $low, $riskOn);
        // Estimar pcp se ausente
        if ($pcp === null && $close !== null && $close != 0.0) {
            $pcp = (($last - $close) / $close) * 100.0;
        }
        $pcpNorm = 0.0; // -1..+1
        if ($pcp !== null) {
            // Escala dinâmica usando amplitude do dia
            $range = $high - $low;
            $rangePct = ($last != 0.0) ? (($range / max(1e-9, $last)) * 100.0) : 0.0;
            $scale = max(1.5, min(8.0, 2.0 + 0.75 * $rangePct));
            $pcpNorm = tanh($pcp / $scale); // -1..+1
        }
        // Para Risk-On: pcp positivo aumenta score; Risk-Off: pcp positivo reduz score
        $pcpScore = 50.0 + 50.0 * ($riskOn ? $pcpNorm : -$pcpNorm);
        // Dar menos peso à posição se a faixa for estimada
        $wPos = $approx ? 0.30 : 0.60;
        $wPcp = 1.0 - $wPos;
        $s = $wPos * $base + $wPcp * $pcpScore;
        if ($s < 0) $s = 0; if ($s > 100) $s = 100;
        return round($s, 2);
    }

    private function rowTimestamp(array $row): ?int
    {
        // Tenta 'timestamp' numérico; se não, tenta 'time_utc' e por fim 'dt_alteracao'
        $ts = $row['timestamp'] ?? null;
        if ($ts !== null && $ts !== '') {
            $n = (int)$ts; if ($n > 1000000000) return $n; // epoch plausível
        }
        $tu = $row['time_utc'] ?? null;
        if (is_string($tu) && $tu !== '') {
            $n2 = strtotime($tu);
            if ($n2 !== false) return $n2;
        }
        $da = $row['dt_alteracao'] ?? null;
        if (is_string($da) && $da !== '') {
            $n3 = strtotime($da);
            if ($n3 !== false) return $n3;
        }
        return null;
    }
}
