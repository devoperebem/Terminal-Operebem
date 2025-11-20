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
    
    echo "\n=== Verificando configuração SGX ===\n\n";
    
    // Verificar FEF1 e FEF2
    $stmt = $pdo->query("
        SELECT code, origem, timestamp, icone_bandeira, bandeira, grupo, ativo
        FROM dicionario 
        WHERE code IN ('FEF1!', 'FEF2!')
    ");
    $fefs = $stmt->fetchAll();
    
    echo "Ativos FEF:\n";
    foreach ($fefs as $fef) {
        echo "  • {$fef['code']}:\n";
        echo "      origem: {$fef['origem']}\n";
        echo "      timestamp: {$fef['timestamp']}\n";
        echo "      bandeira: {$fef['icone_bandeira']} ({$fef['bandeira']})\n";
        echo "      grupo: {$fef['grupo']}\n";
        echo "      ativo: {$fef['ativo']}\n";
    }
    
    // Verificar clock para SGX
    echo "\nHorários no clock para origem 'sgx':\n";
    $stmt = $pdo->query("
        SELECT * FROM clock 
        WHERE origem = 'sgx'
    ");
    $clocks = $stmt->fetchAll();
    
    if (empty($clocks)) {
        echo "  ⚠ Nenhum horário configurado para SGX!\n";
        echo "\n  Criando horário padrão para SGX...\n";
        
        // SGX opera de segunda a sexta, 08:45 - 19:00 SGT (UTC+8)
        // Convertendo para UTC: 00:45 - 11:00 UTC
        $pdo->exec("
            INSERT INTO clock (origem, dia_semana, hora_inicio, hora_fim, tipo_sessao)
            VALUES 
                ('sgx', 1, '00:45:00', '11:00:00', 'regular'),
                ('sgx', 2, '00:45:00', '11:00:00', 'regular'),
                ('sgx', 3, '00:45:00', '11:00:00', 'regular'),
                ('sgx', 4, '00:45:00', '11:00:00', 'regular'),
                ('sgx', 5, '00:45:00', '11:00:00', 'regular')
        ");
        echo "  ✓ Horários SGX criados com sucesso!\n";
    } else {
        foreach ($clocks as $clock) {
            $dias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            $dia = $dias[$clock['dia_semana']] ?? $clock['dia_semana'];
            echo "  • {$dia}: {$clock['hora_inicio']} - {$clock['hora_fim']} ({$clock['tipo_sessao']})\n";
        }
    }
    
    echo "\n";
    
} catch (PDOException $e) {
    echo "ERRO: {$e->getMessage()}\n";
}
