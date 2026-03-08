<?php
// Script de diagnóstico para verificar tabelas e erros
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "=== DIAGNÓSTICO ===\n\n";

// 1. Verificar conexão com banco
echo "1. Testando banco de dados...\n";
try {
    $db = new PDO(
        'pgsql:host=147.93.35.184;dbname=terminal_database',
        $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME'),
        $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD')
    );
    echo "   ✅ Conexão OK\n";
    
    // Verificar se tabelas existem
    $tables = ['subscriptions', 'subscription_plans', 'coupons', 'coupon_redemptions', 'payment_history', 'trial_extensions'];
    foreach ($tables as $table) {
        $result = $db->query("SELECT to_regclass('public.$table')")->fetchColumn();
        $status = $result ? '✅' : '❌';
        echo "   $status Tabela $table\n";
    }
} catch (Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n2. Testando autoload de classes...\n";
try {
    $service = new \App\Services\SubscriptionService();
    echo "   ✅ SubscriptionService OK\n";
} catch (Throwable $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n3. Testando AdminAuthService...\n";
try {
    $authService = new \App\Services\AdminAuthService();
    echo "   ✅ AdminAuthService OK\n";
} catch (Throwable $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
}

echo "\n4. Testando SubscriptionAdminController...\n";
try {
    $controller = new \App\Controllers\Admin\SubscriptionAdminController();
    echo "   ✅ SubscriptionAdminController OK\n";
} catch (Throwable $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIM DIAGNÓSTICO ===\n";
