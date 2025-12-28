<?php
/**
 * Verificar se todos os mercados do relógio estão mapeados e existem na tabela clock
 */

$pdo = new PDO('pgsql:host=147.93.35.184;port=5432;dbname=operebem_quotes', 'quotes_manager', 'ADM_58pIcMEPwKvL53Vq');

// Mapeamento do relógio (mesmo que widgets/market-clock.php)
$NAME_TO_CODE = [
    'B3' => 'XBSP',
    'NYSE' => 'XNYS',
    'NASDAQ' => 'XNAS',
    'TSX' => 'XTSE',
    'LSE' => 'XLON',
    'FWB' => 'XETR',
    'SIX' => 'XSWX',
    'JPX' => 'XTKS',
    'ASX' => 'XASX',
    'HKEX' => 'XHKG',
    'SSE' => 'XSHG',
    'SGX' => 'XSES',
    'NZX' => 'XNZX'
];

// Mercados do relógio com horários BRT
$MARKETS = [
    ['name' => 'B3', 'fullName' => 'B3 - Brasil Bolsa Balcão', 'brt' => [['10:00','17:55']]],
    ['name' => 'NYSE', 'fullName' => 'New York Stock Exchange', 'brt' => [['10:30','17:00']]],
    ['name' => 'NASDAQ', 'fullName' => 'NASDAQ', 'brt' => [['10:30','17:00']]],
    ['name' => 'TSX', 'fullName' => 'Toronto Stock Exchange', 'brt' => [['10:30','17:00']]],
    ['name' => 'LSE', 'fullName' => 'London Stock Exchange', 'brt' => [['04:00','12:30']]],
    ['name' => 'FWB', 'fullName' => 'Frankfurt Stock Exchange', 'brt' => [['04:00','12:30']]],
    ['name' => 'SIX', 'fullName' => 'SIX Swiss Exchange', 'brt' => [['04:00','12:30']]],
    ['name' => 'JPX', 'fullName' => 'Japan Exchange Group', 'brt' => [['21:00','03:00']]],
    ['name' => 'ASX', 'fullName' => 'Australian Securities Exchange', 'brt' => [['20:00','02:00']]],
    ['name' => 'HKEX', 'fullName' => 'Hong Kong Stock Exchange', 'brt' => [['22:30','01:00'], ['02:00','05:00']]],
    ['name' => 'SSE', 'fullName' => 'Shanghai Stock Exchange', 'brt' => [['22:30','04:00']]],
    ['name' => 'SGX', 'fullName' => 'Singapore Exchange', 'brt' => [['22:00','06:00']]],
    ['name' => 'NZX', 'fullName' => 'New Zealand Exchange', 'brt' => [['19:00','01:00']]]
];

// Buscar dados da tabela clock
$exchanges = [];
$r = $pdo->query('SELECT exchange_code, exchange_name, trading_days, open_time, close_time, timezone_name, timezone_utc FROM clock')->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) {
    $exchanges[$row['exchange_code']] = $row;
}

echo "=== VERIFICAÇÃO DO RELÓGIO DE MERCADOS GLOBAIS ===\n\n";
echo "Data/Hora atual: " . date('Y-m-d H:i:s') . " (horário do servidor)\n";
echo "Dia da semana: " . date('l') . " (numérico: " . (date('w') + 1) . ")\n\n";

echo str_repeat('=', 100) . "\n";
echo sprintf("%-8s | %-6s | %-12s | %-15s | %-15s | %-15s | %s\n",
    "Nome", "Código", "Na Clock?", "trading_days", "Horário Local", "Horário BRT", "Status"
);
echo str_repeat('-', 100) . "\n";

$problemas = [];

foreach ($MARKETS as $market) {
    $name = $market['name'];
    $code = $NAME_TO_CODE[$name] ?? 'N/A';
    $brt = $market['brt'];
    
    $naClock = isset($exchanges[$code]) ? 'SIM' : 'NÃO';
    $exchange = $exchanges[$code] ?? null;
    
    $tradingDays = $exchange ? ($exchange['trading_days'] ?? 'N/A') : 'N/A';
    $horarioLocal = $exchange ? ($exchange['open_time'] . '-' . $exchange['close_time']) : 'N/A';
    $horarioBrt = implode(', ', array_map(fn($h) => $h[0] . '-' . $h[1], $brt));
    
    $status = '✅ OK';
    
    // Verificar problemas
    if (!$exchange) {
        $status = '❌ Não existe na clock';
        $problemas[] = "- $name ($code): Não existe na tabela clock";
    } else {
        // Verificar se trading_days inclui domingo (1) - deveria ser closed hoje
        $hoje = date('w') + 1; // 1=Dom, 2=Seg, etc
        $diasArray = str_split($tradingDays);
        $hojeNoDias = in_array((string)$hoje, $diasArray);
        
        if ($hoje == 1 && $hojeNoDias) {
            $status = '⚠️ Inclui domingo';
            $problemas[] = "- $name ($code): trading_days=$tradingDays inclui domingo (1)";
        }
    }
    
    echo sprintf("%-8s | %-6s | %-12s | %-15s | %-15s | %-15s | %s\n",
        $name, $code, $naClock, $tradingDays, $horarioLocal, $horarioBrt, $status
    );
}

echo str_repeat('=', 100) . "\n\n";

// Verificar NZX especificamente
if (!isset($exchanges['XNZX'])) {
    $problemas[] = "- NZX (XNZX): Não existe na tabela clock - PRECISA SER ADICIONADO";
}

if (count($problemas) > 0) {
    echo "=== PROBLEMAS ENCONTRADOS ===\n\n";
    foreach ($problemas as $p) {
        echo "$p\n";
    }
} else {
    echo "✅ Todos os mercados estão configurados corretamente!\n";
}

echo "\n=== EXCHANGES NA TABELA CLOCK ===\n\n";
foreach ($exchanges as $code => $ex) {
    echo sprintf("%-8s: %-40s | dias: %-10s | %s-%s | %s\n",
        $code, 
        $ex['exchange_name'],
        $ex['trading_days'],
        $ex['open_time'],
        $ex['close_time'],
        $ex['timezone_name']
    );
}
