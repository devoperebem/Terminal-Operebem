<?php
/**
 * Script para verificar e adicionar exchanges que estão faltando na tabela clock
 * Executar: php scripts/add_missing_exchanges.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Exchanges que estão sendo usadas no código (boot.js)
$exchangesUsadas = [
    'XBSP' => ['nome' => 'B3 - Bolsa do Brasil', 'timezone' => 'America/Sao_Paulo', 'utc' => '-3', 'open' => '10:00:00', 'close' => '17:00:00', 'pre' => '09:45:00', 'after_start' => '17:55:00', 'after_end' => '18:00:00', 'dias' => 'Mon-Fri'],
    'XNYS' => ['nome' => 'New York Stock Exchange', 'timezone' => 'America/New_York', 'utc' => '-5', 'open' => '09:30:00', 'close' => '16:00:00', 'pre' => '04:00:00', 'after_start' => '16:00:00', 'after_end' => '20:00:00', 'dias' => 'Mon-Fri'],
    'XNAS' => ['nome' => 'NASDAQ', 'timezone' => 'America/New_York', 'utc' => '-5', 'open' => '09:30:00', 'close' => '16:00:00', 'pre' => '04:00:00', 'after_start' => '16:00:00', 'after_end' => '20:00:00', 'dias' => 'Mon-Fri'],
    'XLON' => ['nome' => 'London Stock Exchange', 'timezone' => 'Europe/London', 'utc' => '0', 'open' => '08:00:00', 'close' => '16:30:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XETR' => ['nome' => 'Frankfurt Stock Exchange (XETRA)', 'timezone' => 'Europe/Berlin', 'utc' => '+1', 'open' => '09:00:00', 'close' => '17:30:00', 'pre' => '08:00:00', 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XPAR' => ['nome' => 'Euronext Paris', 'timezone' => 'Europe/Paris', 'utc' => '+1', 'open' => '09:00:00', 'close' => '17:30:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XTKS' => ['nome' => 'Tokyo Stock Exchange', 'timezone' => 'Asia/Tokyo', 'utc' => '+9', 'open' => '09:00:00', 'close' => '15:00:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XHKG' => ['nome' => 'Hong Kong Stock Exchange', 'timezone' => 'Asia/Hong_Kong', 'utc' => '+8', 'open' => '09:30:00', 'close' => '16:00:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XSHG' => ['nome' => 'Shanghai Stock Exchange', 'timezone' => 'Asia/Shanghai', 'utc' => '+8', 'open' => '09:30:00', 'close' => '15:00:00', 'pre' => '09:15:00', 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XSHE' => ['nome' => 'Shenzhen Stock Exchange', 'timezone' => 'Asia/Shanghai', 'utc' => '+8', 'open' => '09:30:00', 'close' => '15:00:00', 'pre' => '09:15:00', 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XTSE' => ['nome' => 'Toronto Stock Exchange', 'timezone' => 'America/Toronto', 'utc' => '-5', 'open' => '09:30:00', 'close' => '16:00:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XASX' => ['nome' => 'Australian Securities Exchange', 'timezone' => 'Australia/Sydney', 'utc' => '+10', 'open' => '10:00:00', 'close' => '16:00:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XMAD' => ['nome' => 'Bolsa de Madrid', 'timezone' => 'Europe/Madrid', 'utc' => '+1', 'open' => '09:00:00', 'close' => '17:30:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XSWX' => ['nome' => 'SIX Swiss Exchange', 'timezone' => 'Europe/Zurich', 'utc' => '+1', 'open' => '09:00:00', 'close' => '17:30:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XSES' => ['nome' => 'Singapore Exchange', 'timezone' => 'Asia/Singapore', 'utc' => '+8', 'open' => '09:00:00', 'close' => '17:00:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XMEX' => ['nome' => 'Bolsa Mexicana de Valores', 'timezone' => 'America/Mexico_City', 'utc' => '-6', 'open' => '08:30:00', 'close' => '15:00:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XJSE' => ['nome' => 'Johannesburg Stock Exchange', 'timezone' => 'Africa/Johannesburg', 'utc' => '+2', 'open' => '09:00:00', 'close' => '17:00:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XMIL' => ['nome' => 'Borsa Italiana (Milan)', 'timezone' => 'Europe/Rome', 'utc' => '+1', 'open' => '09:00:00', 'close' => '17:30:00', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Fri'],
    'XCBT' => ['nome' => 'Chicago Board of Trade', 'timezone' => 'America/Chicago', 'utc' => '-6', 'open' => '08:30:00', 'close' => '15:00:00', 'pre' => null, 'after_start' => '15:00:00', 'after_end' => '16:00:00', 'dias' => 'Mon-Fri'],
    'IFUS' => ['nome' => 'ICE Futures US', 'timezone' => 'America/New_York', 'utc' => '-5', 'open' => '08:00:00', 'close' => '17:00:00', 'pre' => null, 'after_start' => '17:00:00', 'after_end' => '20:00:00', 'dias' => 'Mon-Fri'],
    'CRYPTO' => ['nome' => 'Criptomoedas (24/7)', 'timezone' => 'UTC', 'utc' => '0', 'open' => '00:00:00', 'close' => '23:59:59', 'pre' => null, 'after_start' => null, 'after_end' => null, 'dias' => 'Mon-Sun'],
];

try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $_ENV['QUOTES_DB_HOST'] ?? '147.93.35.184',
        $_ENV['QUOTES_DB_PORT'] ?? '5432',
        $_ENV['QUOTES_DB_DATABASE'] ?? 'operebem_quotes'
    );
    
    $pdo = new PDO($dsn, $_ENV['QUOTES_DB_USERNAME'] ?? '', $_ENV['QUOTES_DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== CONEXÃO OK ===\n\n";
    
    // Verificar quais exchanges já existem
    $stmt = $pdo->query("SELECT exchange_code FROM clock");
    $existentes = array_column($stmt->fetchAll(), 'exchange_code');
    
    echo "Exchanges existentes: " . implode(', ', $existentes) . "\n\n";
    
    $faltando = [];
    foreach ($exchangesUsadas as $code => $dados) {
        if (!in_array($code, $existentes)) {
            $faltando[$code] = $dados;
        }
    }
    
    if (empty($faltando)) {
        echo "Todas as exchanges já estão cadastradas!\n";
    } else {
        echo "Exchanges FALTANDO: " . implode(', ', array_keys($faltando)) . "\n\n";
        
        // Inserir as exchanges que faltam
        $insertSql = "INSERT INTO clock (exchange_code, exchange_name, timezone_name, timezone_utc, open_time, close_time, pre_open_time, after_hours_start, after_hours_end, trading_days, current_status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'closed', 'manual')";
        $insertStmt = $pdo->prepare($insertSql);
        
        foreach ($faltando as $code => $dados) {
            try {
                $insertStmt->execute([
                    $code,
                    $dados['nome'],
                    $dados['timezone'],
                    $dados['utc'],
                    $dados['open'],
                    $dados['close'],
                    $dados['pre'],
                    $dados['after_start'],
                    $dados['after_end'],
                    $dados['dias']
                ]);
                echo "✅ Inserido: $code ({$dados['nome']})\n";
            } catch (Exception $e) {
                echo "❌ Erro ao inserir $code: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\nExchanges adicionadas com sucesso!\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
