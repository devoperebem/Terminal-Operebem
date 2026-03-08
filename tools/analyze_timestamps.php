<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$dbConfig = [
    'host' => $_ENV['QUOTES_DB_HOST'] ?? '147.93.35.184',
    'port' => $_ENV['QUOTES_DB_PORT'] ?? '5432',
    'dbname' => $_ENV['QUOTES_DB_DATABASE'] ?? 'operebem_quotes',
    'user' => $_ENV['QUOTES_DB_USERNAME'] ?? 'quotes_manager',
    'password' => $_ENV['QUOTES_DB_PASSWORD'] ?? 'ADM_58pIcMEPwKvL53Vq'
];

try {
    $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", $dbConfig['host'], $dbConfig['port'], $dbConfig['dbname']);
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "\n=== ANÁLISE DE TIMESTAMPS E ESTRUTURA ===\n\n";
    
    // 1. Verificar estrutura da tabela dicionario
    echo "1. COLUNAS DA TABELA DICIONARIO:\n";
    $stmt = $pdo->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'dicionario' 
        ORDER BY ordinal_position
    ");
    foreach ($stmt->fetchAll() as $col) {
        echo "   • {$col['column_name']}: {$col['data_type']}\n";
    }
    
    // 2. Verificar FEF1 e FEF2
    echo "\n2. DADOS DOS FEF (COMPLETO):\n";
    $stmt = $pdo->query("
        SELECT code, origem, timestamp, last, pc, pcp, high, low, link, grupo, ativo, icone_bandeira
        FROM dicionario 
        WHERE code IN ('FEF1!', 'FEF2!')
    ");
    foreach ($stmt->fetchAll() as $fef) {
        echo "   {$fef['code']}:\n";
        echo "      origem: {$fef['origem']}\n";
        echo "      timestamp: {$fef['timestamp']} (tipo: " . gettype($fef['timestamp']) . ")\n";
        if (is_numeric($fef['timestamp'])) {
            echo "      timestamp convertido: " . date('Y-m-d H:i:s', $fef['timestamp']) . " UTC\n";
            echo "      timestamp UTC-3: " . date('Y-m-d H:i:s', $fef['timestamp'] - 3*3600) . "\n";
        }
        echo "      last: {$fef['last']}\n";
        echo "      pc: {$fef['pc']}\n";
        echo "      pcp: {$fef['pcp']}\n";
        echo "      high: {$fef['high']}\n";
        echo "      low: {$fef['low']}\n";
        echo "      link: {$fef['link']}\n";
        echo "      grupo: {$fef['grupo']}\n";
        echo "      ativo: {$fef['ativo']}\n";
        echo "      bandeira: {$fef['icone_bandeira']}\n";
    }
    
    // 3. Comparar com outros ativos
    echo "\n3. COMPARAÇÃO COM OUTROS ATIVOS (AMOSTRA):\n";
    $stmt = $pdo->query("
        SELECT code, origem, timestamp, link, grupo 
        FROM dicionario 
        WHERE ativo = 'S' 
        AND code IN ('AAPL', 'PETR4', 'BTCUSD', 'USDBRL')
        ORDER BY code
    ");
    foreach ($stmt->fetchAll() as $ativo) {
        echo "   {$ativo['code']}:\n";
        echo "      origem: {$ativo['origem']}\n";
        echo "      timestamp: {$ativo['timestamp']} (tipo: " . gettype($ativo['timestamp']) . ")\n";
        if (is_numeric($ativo['timestamp'])) {
            echo "      convertido UTC: " . date('Y-m-d H:i:s', $ativo['timestamp']) . "\n";
        }
        echo "      link: {$ativo['link']}\n";
        echo "      grupo: {$ativo['grupo']}\n";
    }
    
    // 4. Verificar BigTech
    echo "\n4. BIGTECH (MAG 7):\n";
    $stmt = $pdo->query("
        SELECT code, nome, grupo, ativo 
        FROM dicionario 
        WHERE code IN ('MSFT', 'META', 'GOOGL', 'TSLA', 'AAPL', 'AMZN', 'NVDA')
        ORDER BY code
    ");
    foreach ($stmt->fetchAll() as $bt) {
        echo "   • {$bt['code']}: grupo='{$bt['grupo']}', ativo='{$bt['ativo']}'\n";
    }
    
    // 5. Verificar clock SGX
    echo "\n5. CLOCK SGX:\n";
    $stmt = $pdo->query("
        SELECT * FROM clock WHERE exchange_code = 'XSES'
    ");
    $sgx = $stmt->fetch();
    if ($sgx) {
        echo "   Exchange: {$sgx['exchange_name']}\n";
        echo "   Timezone: {$sgx['timezone_name']} ({$sgx['timezone_utc']})\n";
        echo "   Open: {$sgx['open_time']} - Close: {$sgx['close_time']}\n";
        echo "   Status: {$sgx['current_status']}\n";
        echo "   Trading days: {$sgx['trading_days']}\n";
    } else {
        echo "   ⚠ SGX não encontrado no clock!\n";
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "ERRO: {$e->getMessage()}\n";
}
