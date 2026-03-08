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
    
    echo "\n=== Estrutura da tabela clock ===\n\n";
    
    $stmt = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = 'clock'
        ORDER BY ordinal_position
    ");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $col) {
        echo "  • {$col['column_name']}: {$col['data_type']} (nullable: {$col['is_nullable']})\n";
    }
    
    echo "\n=== Dados atuais na tabela clock ===\n\n";
    $stmt = $pdo->query("SELECT * FROM clock LIMIT 5");
    $rows = $stmt->fetchAll();
    
    foreach ($rows as $row) {
        print_r($row);
        echo "\n";
    }
    
} catch (PDOException $e) {
    echo "ERRO: {$e->getMessage()}\n";
}
