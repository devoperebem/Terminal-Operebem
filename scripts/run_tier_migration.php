<?php
/**
 * Script para executar migração de adição do campo tier
 * Executar: php scripts/run_tier_migration.php
 */

// Carregar .env
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

$driver = $_ENV['DB_CONNECTION'] ?? 'mysql';
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? ($driver === 'mysql' ? '3306' : '5432');
$database = $_ENV['DB_DATABASE'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "=== MIGRAÇÃO: Adicionar campo TIER ===\n\n";
echo "Driver: $driver\n";
echo "Host: $host:$port\n";
echo "Database: $database\n";
echo "Username: $username\n\n";

try {
    if ($driver === 'mysql') {
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    } else {
        $dsn = "pgsql:host=$host;port=$port;dbname=$database";
    }
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Conexão OK\n\n";
    
    // Verificar se a coluna tier já existe
    if ($driver === 'mysql') {
        $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'tier'";
        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([$database]);
    } else {
        $checkSql = "SELECT column_name FROM information_schema.columns 
                     WHERE table_name = 'users' AND column_name = 'tier'";
        $stmt = $pdo->query($checkSql);
    }
    
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "⚠️ A coluna 'tier' já existe na tabela users!\n";
        
        // Mostrar dados atuais
        $countSql = "SELECT tier, COUNT(*) as total FROM users GROUP BY tier";
        $stmt = $pdo->query($countSql);
        $counts = $stmt->fetchAll();
        echo "\nDistribuição atual de tiers:\n";
        foreach ($counts as $row) {
            echo "  - " . ($row['tier'] ?? 'NULL') . ": " . $row['total'] . " usuários\n";
        }
    } else {
        echo "Adicionando coluna 'tier'...\n";
        
        if ($driver === 'mysql') {
            $pdo->exec("ALTER TABLE users ADD COLUMN tier ENUM('FREE', 'PLUS', 'PRO') DEFAULT 'FREE' NOT NULL");
            $pdo->exec("ALTER TABLE users ADD COLUMN subscription_expires_at TIMESTAMP NULL");
        } else {
            // PostgreSQL não suporta ENUM diretamente, usar VARCHAR com CHECK
            $pdo->exec("ALTER TABLE users ADD COLUMN tier VARCHAR(10) DEFAULT 'FREE' NOT NULL CHECK (tier IN ('FREE', 'PLUS', 'PRO'))");
            $pdo->exec("ALTER TABLE users ADD COLUMN subscription_expires_at TIMESTAMP NULL");
        }
        
        echo "✅ Coluna 'tier' adicionada com sucesso!\n";
        echo "✅ Coluna 'subscription_expires_at' adicionada com sucesso!\n";
        
        // Criar índice
        try {
            $pdo->exec("CREATE INDEX idx_users_tier ON users(tier)");
            echo "✅ Índice 'idx_users_tier' criado!\n";
        } catch (Exception $e) {
            echo "⚠️ Índice já existe ou não pôde ser criado: " . $e->getMessage() . "\n";
        }
        
        // Contar usuários que receberam FREE por padrão
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE tier = 'FREE'");
        $count = $countStmt->fetch();
        echo "\n📊 " . $count['total'] . " usuários agora têm tier FREE (padrão)\n";
    }
    
    echo "\n✅ Migração concluída!\n";
    
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
