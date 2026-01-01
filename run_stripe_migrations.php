<?php
/**
 * Script para executar migrations do Stripe
 * Execute: php run_stripe_migrations.php
 */

// Evitar output de erros HTML
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "=== Executando Migrations do Stripe ===\n\n";

// Carregar autoload
require __DIR__ . '/vendor/autoload.php';

// Carregar .env manualmente
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remover aspas
            $value = trim($value, '"\'');
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
    echo "✅ .env carregado\n";
} else {
    echo "❌ Arquivo .env não encontrado\n";
    exit(1);
}

// Conectar ao banco
$driver = $_ENV['DB_CONNECTION'] ?? 'pgsql';
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '5432';
$database = $_ENV['DB_DATABASE'] ?? '';
$username = $_ENV['DB_USERNAME'] ?? '';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    if ($driver === 'pgsql') {
        $dsn = "pgsql:host={$host};port={$port};dbname={$database}";
    } else {
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    }
    
    $db = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Conectado ao banco de dados ({$driver})\n\n";
} catch (PDOException $e) {
    echo "❌ Erro ao conectar: " . $e->getMessage() . "\n";
    exit(1);
}

// Migrations a executar
$migrations = [
    '020_create_subscriptions_table.sql',
    '021_create_payment_history_table.sql', 
    '022_create_subscription_plans_table.sql',
    '023_create_coupons_tables.sql',
    '024_create_trial_extensions_table.sql',
    '025_add_stripe_customer_id_to_users.sql',
];

foreach ($migrations as $file) {
    $path = __DIR__ . '/database/migrations/' . $file;
    
    if (!file_exists($path)) {
        echo "❌ Arquivo não encontrado: {$file}\n";
        continue;
    }
    
    echo "🔄 Executando: {$file} ... ";
    
    try {
        $sql = file_get_contents($path);
        $db->exec($sql);
        echo "✅ OK\n";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Ignorar erros de "já existe"
        if (strpos($msg, 'already exists') !== false || strpos($msg, 'duplicate') !== false) {
            echo "⚠️ Já existe\n";
        } else {
            echo "❌ Erro: " . $msg . "\n";
        }
    }
}

echo "\n=== Migrations concluídas ===\n\n";
echo "Verificando tabelas criadas:\n";

$tables = ['subscriptions', 'payment_history', 'subscription_plans', 'coupons', 'coupon_redemptions', 'trial_extensions'];

foreach ($tables as $table) {
    try {
        $result = $db->query("SELECT COUNT(*) as count FROM {$table}");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "✅ {$table}: {$row['count']} registros\n";
    } catch (PDOException $e) {
        echo "❌ {$table}: não existe ou erro\n";
    }
}

// Verificar se stripe_customer_id foi adicionado em users
try {
    $db->query("SELECT stripe_customer_id FROM users LIMIT 1");
    echo "✅ users.stripe_customer_id: OK\n";
} catch (PDOException $e) {
    echo "❌ users.stripe_customer_id: não existe\n";
}

echo "\n=== Finalizado ===\n";
