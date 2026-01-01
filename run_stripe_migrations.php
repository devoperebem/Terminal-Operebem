<?php
/**
 * Script para executar migrations do Stripe
 * Execute: php run_stripe_migrations.php
 */

require __DIR__ . '/bootstrap.php';

use App\Core\Database;

echo "=== Executando Migrations do Stripe ===\n\n";

$migrations = [
    '020_create_subscriptions_table.sql',
    '021_create_payment_history_table.sql',
    '022_create_subscription_plans_table.sql',
    '023_create_coupons_tables.sql',
    '024_create_trial_extensions_table.sql',
    '025_add_stripe_customer_id_to_users.sql',
];

$db = Database::getConnection();

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
    } catch (Exception $e) {
        echo "❌ Erro: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Migrations concluídas ===\n";
echo "\nVerificando tabelas criadas:\n";

$tables = ['subscriptions', 'payment_history', 'subscription_plans', 'coupons', 'coupon_redemptions', 'trial_extensions'];

foreach ($tables as $table) {
    try {
        $result = $db->query("SELECT COUNT(*) as count FROM {$table}");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        echo "✅ {$table}: {$row['count']} registros\n";
    } catch (Exception $e) {
        echo "❌ {$table}: não existe ou erro\n";
    }
}

// Verificar se stripe_customer_id foi adicionado em users
try {
    $db->query("SELECT stripe_customer_id FROM users LIMIT 1");
    echo "✅ users.stripe_customer_id: OK\n";
} catch (Exception $e) {
    echo "❌ users.stripe_customer_id: não existe\n";
}
