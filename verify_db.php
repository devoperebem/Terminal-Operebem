<?php
require __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use App\Core\Database;

$app = Application::getInstance();
Database::init($app->config('database'));
$pdo = Database::connection();

echo "=== VERIFICACAO DO BANCO ===\n\n";

// Verificar tabela admin_audit_logs
$r = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'admin_audit_logs'");
echo "admin_audit_logs existe: " . ($r->fetchColumn() > 0 ? 'SIM' : 'NAO') . "\n";

// Contar registros
try {
    $r2 = $pdo->query('SELECT COUNT(*) FROM admin_audit_logs');
    echo "Registros em admin_audit_logs: " . $r2->fetchColumn() . "\n";
} catch (Exception $e) {
    echo "Erro ao contar registros: " . $e->getMessage() . "\n";
}

// Verificar trial_extended_days
$r3 = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'subscriptions' AND column_name = 'trial_extended_days'");
echo "subscriptions.trial_extended_days existe: " . ($r3->fetchColumn() > 0 ? 'SIM' : 'NAO') . "\n";

// Verificar deleted_at em coupons
$r4 = $pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'coupons' AND column_name = 'deleted_at'");
echo "coupons.deleted_at existe: " . ($r4->fetchColumn() > 0 ? 'SIM' : 'NAO') . "\n";

// Verificar subscription_plans
echo "\n=== SUBSCRIPTION PLANS ===\n";
try {
    $r5 = $pdo->query('SELECT id, slug, name, stripe_price_id, price_cents, is_active FROM subscription_plans ORDER BY id');
    while ($row = $r5->fetch(PDO::FETCH_ASSOC)) {
        echo "Plan #{$row['id']}: {$row['slug']} | {$row['name']} | price: {$row['price_cents']} | stripe: " . ($row['stripe_price_id'] ?: 'NULL') . " | active: " . ($row['is_active'] ? 'YES' : 'NO') . "\n";
    }
} catch (Exception $e) {
    echo "Erro ao listar plans: " . $e->getMessage() . "\n";
}

// Verificar migrations executadas
echo "\n=== MIGRATIONS ===\n";
$r6 = $pdo->query("SELECT filename FROM migrations WHERE filename LIKE '02%' ORDER BY filename");
while ($row = $r6->fetch(PDO::FETCH_ASSOC)) {
    echo "  " . $row['filename'] . "\n";
}
